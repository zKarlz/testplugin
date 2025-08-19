<?php
/**
 * Frontend class for the test plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'LLP_Frontend' ) ) {
    class LLP_Frontend {
        public function __construct() {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_customizer' ) );
            add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_composite' ), 10, 5 );
        }

        public function enqueue_scripts() {
            wp_enqueue_script(
                'llp-frontend',
                plugins_url( 'assets/js/frontend.js', dirname( __DIR__ ) ),
                array(),
                '1.0',
                true
            );
        }

        public function render_customizer() {
            $variation = array(
                'bounds' => array( 'width' => 500, 'height' => 500 ),
                'aspect' => 1,
                'base'   => 'https://via.placeholder.com/500x500.png?text=Base',
                'mockup' => 'https://via.placeholder.com/500x500.png?text=Mockup',
                'mask'   => 'https://via.placeholder.com/500x500.png?text=Mask',
            );

            wc_get_template(
                'single-product/customizer.php',
                array( 'variation' => $variation ),
                '',
                plugin_dir_path( __FILE__ ) . '../templates/'
            );
        }

        public function validate_composite( $passed, $product_id, $quantity, $variation_id = '', $variations = array() ) {
            if ( empty( $_POST['llp_composite'] ) ) {
                wc_add_notice( __( 'Please finalize your design before adding to cart.', 'testplugin' ), 'error' );
                return false;
            }
            return $passed;
        }
    }
}
