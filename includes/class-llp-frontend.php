<?php
/**
 * Frontend hooks and rendering.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Frontend {
    use Singleton;

    private function __construct() {
        add_action('woocommerce_before_add_to_cart_button', [$this, 'render_customizer']);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_before_add'], 10, 5);
        add_filter('woocommerce_add_cart_item_data', [$this, 'attach_cart_item_data'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'restore_cart_item'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'scripts']);
    }

    /**
     * Render the customer customizer template.
     */
    public function render_customizer(): void {
        wc_get_template('single-product/customizer.php', [], '', LLP_DIR . 'templates/');
    }

    /**
     * Enqueue frontend scripts.
     */
    public function scripts(): void {
        if (!is_product()) {
            return;
        }
        wp_enqueue_style('llp-frontend', LLP_URL . 'assets/css/frontend.css', [], LLP_VER);
        wp_enqueue_script('llp-frontend', LLP_URL . 'assets/js/frontend.js', [], LLP_VER, true);

        global $product;
        $variation_data = [];
        if ($product instanceof \WC_Product && $product->is_type('variable')) {
            foreach ($product->get_children() as $vid) {
                $bounds  = json_decode((string) get_post_meta($vid, '_llp_bounds', true), true) ?: [];
                $base_id = (int) get_post_meta($vid, '_llp_base_image_id', true);
                $mask_id = (int) get_post_meta($vid, '_llp_mask_image_id', true);
                $base    = wp_get_attachment_image_src($base_id, 'full');
                $mask    = wp_get_attachment_image_src($mask_id, 'full');
                $variation_data[$vid] = [
                    'bounds' => $bounds,
                    'base'   => $base[0] ?? '',
                    'base_w' => $base[1] ?? 0,
                    'base_h' => $base[2] ?? 0,
                    'mask'   => $mask[0] ?? '',
                ];
            }
        }

        wp_localize_script('llp-frontend', 'llp_frontend', [
            'nonce'        => wp_create_nonce('llp'),
            'upload_url'   => rest_url('llp/v1/upload'),
            'finalize_url' => rest_url('llp/v1/finalize'),
            'variations'   => $variation_data,
        ]);
    }

    /**
     * Validate before add to cart.
     */
    public function validate_before_add(bool $passed, int $product_id, int $quantity, $variation_id = 0, $variations = null): bool {
        if (empty($_POST['_llp_asset_id']) || empty($_POST['_llp_transform'])) {
            wc_add_notice(__('Please upload and position your photo.', 'llp'), 'error');
            return false;
        }

        $asset_dir = Storage::instance()->asset_dir(sanitize_text_field($_POST['_llp_asset_id']));
        if (!file_exists($asset_dir . 'composite.png')) {
            wc_add_notice(__('Unable to locate uploaded image. Please try again.', 'llp'), 'error');
            return false;
        }
        return $passed;
    }

    /**
     * Attach LLP data to cart item.
     */
    public function attach_cart_item_data(array $cart_item_data, int $product_id, int $variation_id): array {
        if (isset($_POST['_llp_asset_id'])) {
            $cart_item_data['_llp_asset_id']  = sanitize_text_field(wp_unslash($_POST['_llp_asset_id']));
            $cart_item_data['_llp_transform'] = wp_unslash($_POST['_llp_transform']);
            $asset_id = $cart_item_data['_llp_asset_id'];
            $sec      = Security::instance();
            $cart_item_data['_llp_original_url']  = $sec->sign_url($asset_id, 'original.png');
            $cart_item_data['_llp_composite_url'] = $sec->sign_url($asset_id, 'composite.png');
            $cart_item_data['_llp_thumb_url']     = $sec->sign_url($asset_id, 'thumb.jpg');

            $meta = json_decode(@file_get_contents(Storage::instance()->asset_dir($asset_id) . 'meta.json'), true) ?: [];
            $cart_item_data['_llp_original_sha256'] = $meta['sha256'] ?? '';
            $cart_item_data['_llp_processor']       = $meta['renderer'] ?? '';

            $keys = ['_llp_base_image_id', '_llp_mask_image_id', '_llp_bounds', '_llp_output_dpi', '_llp_aspect_ratio', '_llp_min_resolution'];
            $settings = [];
            foreach ($keys as $key) {
                $settings[$key] = get_post_meta($variation_id, $key, true);
            }
            $cart_item_data['_llp_variation_settings'] = $settings;
        }
        return $cart_item_data;
    }

    /**
     * Display item data in cart/checkout.
     */
    public function display_cart_item_data(array $item_data, array $cart_item): array {
        if (!empty($cart_item['_llp_asset_id'])) {
            $sec   = Security::instance();
            $thumb = $sec->sign_url($cart_item['_llp_asset_id'], 'thumb.jpg');
            $item_data[] = [
                'name'  => __('Preview', 'llp'),
                'value' => '<img src="' . esc_url($thumb) . '" alt="" style="max-width:80px;" />',
            ];
        }
        return $item_data;
    }

    /**
     * Restore cart item from session.
     */
    public function restore_cart_item(array $cart_item, array $session_values): array {
        foreach (['_llp_asset_id', '_llp_transform', '_llp_original_url', '_llp_composite_url', '_llp_thumb_url', '_llp_original_sha256', '_llp_processor', '_llp_variation_settings'] as $key) {
            if (isset($session_values[$key])) {
                $cart_item[$key] = $session_values[$key];
            }
        }
        if (!empty($cart_item['_llp_asset_id'])) {
            $sec      = Security::instance();
            $asset_id = $cart_item['_llp_asset_id'];
            $cart_item['_llp_original_url']  = $sec->sign_url($asset_id, 'original.png');
            $cart_item['_llp_composite_url'] = $sec->sign_url($asset_id, 'composite.png');
            $cart_item['_llp_thumb_url']     = $sec->sign_url($asset_id, 'thumb.jpg');
        }
        return $cart_item;
    }
}
