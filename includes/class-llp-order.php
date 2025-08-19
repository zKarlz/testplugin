<?php
/**
 * Order integration and persistence.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Order {
    use Singleton;

    private function __construct() {
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'persist_order_item_meta'], 10, 4);
        add_action('woocommerce_email_after_order_table', [$this, 'email_thumbs'], 10, 4);
        add_action('add_meta_boxes', [$this, 'meta_box']);
        add_action('admin_post_llp_purge_order', [$this, 'handle_purge']);
    }

    /**
     * Persist order item meta on checkout.
     */
    public function persist_order_item_meta($item, string $cart_item_key, array $values, $order_id): void {
        $keys = ['_llp_asset_id', '_llp_transform', '_llp_original_url', '_llp_composite_url', '_llp_thumb_url'];
        foreach ($keys as $key) {
            if (isset($values[$key])) {
                $item->add_meta_data($key, $values[$key], true);
            }
        }
    }

    /**
     * Output thumbnails in emails.
     */
    public function email_thumbs($order, $sent_to_admin, $plain_text, $email): void {
        if ($plain_text) {
            return;
        }
        $sec = Security::instance();
        foreach ($order->get_items() as $item) {
            $asset_id = $item->get_meta('_llp_asset_id');
            if ($asset_id) {
                $thumb = $sec->sign_url($asset_id, 'thumb.jpg');
                wc_get_template('emails/line-item-preview.php', ['thumb_url' => $thumb], '', LLP_DIR . 'templates/');
            }
        }
    }

    /**
     * Add order meta box for asset preview and purge.
     */
    public function meta_box(): void {
        add_meta_box('llp_assets', __('Laser Photo Assets', 'llp'), [$this, 'render_meta_box'], 'shop_order', 'side');
    }

    /**
     * Render the order meta box.
     */
    public function render_meta_box($post): void {
        $order = wc_get_order($post->ID);
        if (!$order) {
            return;
        }
        $sec = Security::instance();
        foreach ($order->get_items() as $item) {
            $asset_id = $item->get_meta('_llp_asset_id');
            if ($asset_id) {
                $thumb = $sec->sign_url($asset_id, 'thumb.jpg');
                echo '<p><img src="' . esc_url($thumb) . '" style="max-width:100%;" /></p>';
            }
        }
        $url = wp_nonce_url(admin_url('admin-post.php?action=llp_purge_order&order_id=' . $post->ID), 'llp_purge_order');
        echo '<p><a class="button" href="' . esc_url($url) . '">' . esc_html__('Delete customer assets', 'llp') . '</a></p>';
    }

    /**
     * Handle purge request from order screen.
     */
    public function handle_purge(): void {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('Permission denied', 'llp'));
        }
        $order_id = absint($_GET['order_id'] ?? 0);
        if (!$order_id || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'llp_purge_order')) {
            wp_die(__('Invalid request', 'llp'));
        }
        $order = wc_get_order($order_id);
        if ($order) {
            foreach ($order->get_items() as $item) {
                $asset_id = $item->get_meta('_llp_asset_id');
                if ($asset_id) {
                    Storage::instance()->delete($asset_id);
                }
            }
        }
        wp_safe_redirect(wp_get_referer() ?: admin_url('post.php?post=' . $order_id . '&action=edit'));
        exit;
    }
}
