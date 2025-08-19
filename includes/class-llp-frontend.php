<?php
/**
 * Frontend logic for LLP plugin.
 */

class LLP_Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'woocommerce_single_product_summary', [ $this, 'render_customizer' ], 35 );
        add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_add_to_cart' ], 10, 2 );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'llp-frontend', plugins_url( '../assets/js/frontend.js', __FILE__ ), [], '1.0', true );
    }

    public function render_customizer() {
        if ( ! function_exists( 'wc_get_template' ) ) {
            return;
        }
        global $product;
        if ( ! $product ) {
            return;
        }
        $mockup_url = get_post_meta( $product->get_id(), '_llp_mockup', true );
        $mask_url   = get_post_meta( $product->get_id(), '_llp_mask', true );
        $variations = method_exists( $product, 'get_available_variations' ) ? $product->get_available_variations() : [];
        wc_get_template( 'single-product/customizer.php', [
            'mockup_url' => $mockup_url,
            'mask_url'   => $mask_url,
            'variations' => $variations,
        ], '', plugin_dir_path( __DIR__ ) . 'templates/' );
    }

    public function validate_add_to_cart( $passed, $product_id ) {
        if ( empty( $_POST['llp_transform'] ) ) {
            if ( function_exists( 'wc_add_notice' ) ) {
                wc_add_notice( __( 'Please finalize your design before adding to cart.', 'llp' ), 'error' );
            }
            return false;
        }
        return $passed;
    }
}
