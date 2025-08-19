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
        add_action('admin_post_llp_rerender', [$this, 'handle_rerender']);
    }

    /**
     * Persist order item meta on checkout.
     */
    public function persist_order_item_meta($item, string $cart_item_key, array $values, $order_id): void {
        $keys = ['_llp_asset_id', '_llp_transform', '_llp_original_url', '_llp_composite_url', '_llp_thumb_url', '_llp_original_sha256', '_llp_processor', '_llp_variation_settings'];
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
        foreach ($order->get_items() as $item) {
            $asset_id = $item->get_meta('_llp_asset_id');
            if ($asset_id) {
                $thumb = Security::instance()->sign_url($asset_id, 'thumb.jpg');
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
        foreach ($order->get_items() as $item) {
            $asset_id = $item->get_meta('_llp_asset_id');
            if ($asset_id) {
                $sec   = Security::instance();
                $thumb = $sec->sign_url($asset_id, 'thumb.jpg');
                $orig  = $sec->sign_url($asset_id, 'original.png');
                $comp  = $sec->sign_url($asset_id, 'composite.png');
                echo '<p><img src="' . esc_url($thumb) . '" style="max-width:100%;" /></p>';
                $rerender_url = wp_nonce_url(
                    admin_url('admin-post.php?action=llp_rerender&order_id=' . $post->ID . '&item_id=' . $item->get_id()),
                    'llp_rerender_order'
                );
                echo '<p>';
                echo '<a class="button" href="' . esc_url($orig) . '" target="_blank">' . esc_html__('Original', 'llp') . '</a> ';
                echo '<a class="button" href="' . esc_url($comp) . '" target="_blank">' . esc_html__('Composite', 'llp') . '</a> ';
                echo '<a class="button" href="' . esc_url($rerender_url) . '">' . esc_html__('Re-render', 'llp') . '</a>';
                echo '</p>';
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

    /**
     * Handle re-render request from order screen.
     */
    public function handle_rerender(): void {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('Permission denied', 'llp'));
        }
        $order_id = absint($_GET['order_id'] ?? 0);
        $item_id  = absint($_GET['item_id'] ?? 0);
        if (!$order_id || !$item_id || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'llp_rerender_order')) {
            wp_die(__('Invalid request', 'llp'));
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_die(__('Invalid order', 'llp'));
        }
        $item = $order->get_item($item_id);
        if (!$item) {
            wp_die(__('Invalid item', 'llp'));
        }
        $asset_id  = $item->get_meta('_llp_asset_id');
        $transform = json_decode((string) $item->get_meta('_llp_transform'), true) ?: [];
        if (!$asset_id || empty($transform)) {
            wp_die(__('Missing data', 'llp'));
        }

        $variation_id = $item->get_variation_id() ?: $item->get_product_id();
        $base_id = (int) get_post_meta($variation_id, '_llp_base_image_id', true);
        $mask_id = (int) get_post_meta($variation_id, '_llp_mask_image_id', true);
        $bounds  = json_decode((string) get_post_meta($variation_id, '_llp_bounds', true), true) ?: [];
        $dpi     = (int) get_post_meta($variation_id, '_llp_output_dpi', true) ?: 300;
        if (!$base_id || empty($bounds)) {
            wp_die(__('Variation not configured', 'llp'));
        }
        $base_path = get_attached_file($base_id);
        if (!$base_path || !file_exists($base_path)) {
            wp_die(__('Base image not found', 'llp'));
        }
        $mask_path = null;
        if ($mask_id) {
            $mask_path = get_attached_file($mask_id);
            if (!$mask_path || !file_exists($mask_path)) {
                $mask_path = null;
            }
        }

        $dir = Storage::instance()->asset_dir($asset_id);
        Renderer::instance()->render([
            'base_path'    => $base_path,
            'mask_path'    => $mask_path,
            'user_img'     => $dir . 'original.png',
            'bounds'       => $bounds,
            'transform'    => $transform,
            'output_dpi'   => $dpi,
            'out_composite'=> $dir . 'composite.png',
            'out_thumb'    => $dir . 'thumb.jpg',
        ]);

        $sec = Security::instance();
        $item->update_meta_data('_llp_composite_url', $sec->sign_url($asset_id, 'composite.png'));
        $item->update_meta_data('_llp_thumb_url', $sec->sign_url($asset_id, 'thumb.jpg'));
        $item->save_meta_data();

        wp_safe_redirect(wp_get_referer() ?: admin_url('post.php?post=' . $order_id . '&action=edit'));
        exit;
    }
}
