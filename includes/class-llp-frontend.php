<?php
/**
 * Frontend handlers for LLP plugin.
 */
class LLP_Frontend {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'woocommerce_single_product_summary', array( $this, 'render_customizer' ), 25 );
        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_before_add' ), 10, 6 );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'llp-frontend', plugins_url( 'assets/js/frontend.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.0', true );
    }

    private function get_variation_settings( $variation_id ) {
        return array(
            'base'          => get_post_meta( $variation_id, '_llp_base', true ),
            'mask'          => get_post_meta( $variation_id, '_llp_mask', true ),
            'bounds'        => get_post_meta( $variation_id, '_llp_bounds', true ),
            'aspect_ratio'  => get_post_meta( $variation_id, '_llp_aspect_ratio', true ),
            'min_resolution'=> get_post_meta( $variation_id, '_llp_min_resolution', true ),
        );
    }

    public function render_customizer() {
        global $product;
        if ( ! $product ) {
            return;
        }
        $settings = $this->get_variation_settings( $product->get_id() );
        wc_get_template( 'single-product/customizer.php', array( 'settings' => $settings ), '', plugin_dir_path( dirname( __FILE__ ) ) . 'templates/' );
    }

    public function validate_before_add( $passed, $product_id, $quantity, $variation_id = 0, $variations = array(), $cart_item_data = array() ) {
        if ( empty( $_POST['llp_finalized'] ) || '1' !== $_POST['llp_finalized'] ) {
            wc_add_notice( __( 'Please finalize your design before adding to cart.', 'llp' ), 'error' );
            return false;
        }
        return $passed;
    }
}
