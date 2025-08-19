<?php
/**
 * Variation fields for mockup configuration.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Variation_Fields {
    use Singleton;

    private function __construct() {
        add_action('woocommerce_product_after_variable_attributes', [$this, 'render'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'scripts']);
    }

    /**
     * Enqueue admin assets.
     */
    public function scripts($hook): void {
        if (!function_exists('get_current_screen')) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || 'product' !== $screen->id) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('llp-admin-variation', LLP_URL . 'assets/js/admin-variation.js', [], LLP_VER, true);
        wp_localize_script('llp-admin-variation', 'llpAdmin', [
            'closeText'  => __('Close', 'llp'),
            'widthText'  => __('Width (px)', 'llp'),
            'heightText' => __('Height (px)', 'llp'),
        ]);
        wp_enqueue_style('llp-admin', LLP_URL . 'assets/css/admin.css', [], LLP_VER);
    }

    /**
     * Render fields in variation edit screen.
     */
    public function render(int $loop, array $variation_data, $variation): void {
        $base  = get_post_meta($variation->ID, '_llp_base_image_id', true);
        $mask  = get_post_meta($variation->ID, '_llp_mask_image_id', true);
        $bounds = json_decode((string) get_post_meta($variation->ID, '_llp_bounds', true), true) ?: [];
        $aspect = get_post_meta($variation->ID, '_llp_aspect_ratio', true);
        $minres = json_decode((string) get_post_meta($variation->ID, '_llp_min_resolution', true), true) ?: [];
        $dpi    = get_post_meta($variation->ID, '_llp_output_dpi', true);
        $dpi    = apply_filters('llp_output_dpi', $dpi, $variation->ID);

        echo '<div class="llp-variation-fields">';
        echo '<p class="form-field"><label for="llp_base_image_id_' . $loop . '">' . esc_html__('Base image ID', 'llp') . '</label>';
        echo '<input type="text" class="short" id="llp_base_image_id_' . $loop . '" name="llp_base_image_id[' . $loop . ']" value="' . esc_attr($base) . '" /> ';
        echo '<button type="button" class="button llp-media-select" data-target="llp_base_image_id_' . $loop . '">' . esc_html__('Select', 'llp') . '</button></p>';

        echo '<p class="form-field"><label for="llp_mask_image_id_' . $loop . '">' . esc_html__('Mask image ID', 'llp') . '</label>';
        echo '<input type="text" class="short" id="llp_mask_image_id_' . $loop . '" name="llp_mask_image_id[' . $loop . ']" value="' . esc_attr($mask) . '" /> ';
        echo '<button type="button" class="button llp-media-select" data-target="llp_mask_image_id_' . $loop . '">' . esc_html__('Select', 'llp') . '</button></p>';

        woocommerce_wp_text_input([
            'id'          => "llp_bounds_x_{$loop}",
            'name'        => "llp_bounds_x[{$loop}]",
            'label'       => __('Bounds X', 'llp'),
            'value'       => $bounds['x'] ?? '',
            'type'        => 'number',
        ]);
        woocommerce_wp_text_input([
            'id'          => "llp_bounds_y_{$loop}",
            'name'        => "llp_bounds_y[{$loop}]",
            'label'       => __('Bounds Y', 'llp'),
            'value'       => $bounds['y'] ?? '',
            'type'        => 'number',
        ]);
        woocommerce_wp_text_input([
            'id'          => "llp_bounds_w_{$loop}",
            'name'        => "llp_bounds_w[{$loop}]",
            'label'       => __('Bounds Width', 'llp'),
            'value'       => $bounds['w'] ?? '',
            'type'        => 'number',
        ]);
        woocommerce_wp_text_input([
            'id'          => "llp_bounds_h_{$loop}",
            'name'        => "llp_bounds_h[{$loop}]",
            'label'       => __('Bounds Height', 'llp'),
            'value'       => $bounds['h'] ?? '',
            'type'        => 'number',
        ]);
        woocommerce_wp_text_input([
            'id'          => "llp_bounds_rotation_{$loop}",
            'name'        => "llp_bounds_rotation[{$loop}]",
            'label'       => __('Bounds Rotation', 'llp'),
            'value'       => $bounds['rotation'] ?? '',
            'type'        => 'number',
            'custom_attributes' => ['step' => 'any'],
        ]);

        echo '<p class="form-field"><button type="button" class="button llp-open-editor" data-loop="' . $loop . '">' . esc_html__('Open Placement Editor', 'llp') . '</button></p>';

        woocommerce_wp_text_input([
            'id'          => "llp_aspect_ratio_{$loop}",
            'name'        => "llp_aspect_ratio[{$loop}]",
            'label'       => __('Aspect ratio (W:H)', 'llp'),
            'value'       => $aspect,
        ]);
        woocommerce_wp_text_input([
            'id'          => "llp_min_res_w_{$loop}",
            'name'        => "llp_min_res_w[{$loop}]",
            'label'       => __('Min width (px)', 'llp'),
            'value'       => $minres['min_w'] ?? '',
            'type'        => 'number',
        ]);
        woocommerce_wp_text_input([
            'id'          => "llp_min_res_h_{$loop}",
            'name'        => "llp_min_res_h[{$loop}]",
            'label'       => __('Min height (px)', 'llp'),
            'value'       => $minres['min_h'] ?? '',
            'type'        => 'number',
        ]);
        woocommerce_wp_text_input([
            'id'          => "llp_output_dpi_{$loop}",
            'name'        => "llp_output_dpi[{$loop}]",
            'label'       => __('Output DPI', 'llp'),
            'value'       => $dpi,
            'type'        => 'number',
        ]);
        echo '</div>';
    }

    /**
     * Save variation meta.
     */
    public function save(int $variation_id, int $i): void {
        $base = absint($_POST['llp_base_image_id'][$i] ?? 0);
        update_post_meta($variation_id, '_llp_base_image_id', $base);

        $mask = absint($_POST['llp_mask_image_id'][$i] ?? 0);
        update_post_meta($variation_id, '_llp_mask_image_id', $mask);

        $bounds = [
            'x'        => absint($_POST['llp_bounds_x'][$i] ?? 0),
            'y'        => absint($_POST['llp_bounds_y'][$i] ?? 0),
            'w'        => absint($_POST['llp_bounds_w'][$i] ?? 0),
            'h'        => absint($_POST['llp_bounds_h'][$i] ?? 0),
            'rotation' => floatval($_POST['llp_bounds_rotation'][$i] ?? 0),
        ];
        update_post_meta($variation_id, '_llp_bounds', wp_json_encode($bounds));

        $aspect = sanitize_text_field($_POST['llp_aspect_ratio'][$i] ?? '');
        update_post_meta($variation_id, '_llp_aspect_ratio', $aspect);

        $minres = [
            'min_w' => absint($_POST['llp_min_res_w'][$i] ?? 0),
            'min_h' => absint($_POST['llp_min_res_h'][$i] ?? 0),
        ];
        update_post_meta($variation_id, '_llp_min_resolution', wp_json_encode($minres));

        $dpi = absint($_POST['llp_output_dpi'][$i] ?? 300);
        update_post_meta($variation_id, '_llp_output_dpi', $dpi);
    }
}
