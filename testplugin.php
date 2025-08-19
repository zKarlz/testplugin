<?php
/**
 * Plugin Name: Test Plugin
 * Description: Demonstration plugin for LLP order meta box actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/includes/class-llp-order.php';

// Instantiate the order helper.
new LLP_Order();

/**
 * Handle re-render request.
 */
function llp_handle_rerender_composite() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized', 'llp' ) );
    }

    check_admin_referer( 'llp_rerender_composite' );

    $order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $transform  = get_post_meta( $order_id, '_llp_transform_json', true );
    $variations = get_post_meta( $order_id, '_llp_variations', true );

    $success = llp_render_composite( $transform, $variations );
    $msg     = $success ? 'rerender_success' : 'rerender_fail';

    $redirect = add_query_arg( array(
        'post'        => $order_id,
        'action'      => 'edit',
        'llp_message' => $msg,
    ), admin_url( 'post.php' ) );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_post_llp_rerender_composite', 'llp_handle_rerender_composite' );

/**
 * Handle purge request.
 */
function llp_handle_purge_composite() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized', 'llp' ) );
    }

    check_admin_referer( 'llp_purge_composite' );

    $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

    // Purge logic would go here.
    $msg = 'purge_success';

    $redirect = add_query_arg( array(
        'post'        => $order_id,
        'action'      => 'edit',
        'llp_message' => $msg,
    ), admin_url( 'post.php' ) );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_post_llp_purge_composite', 'llp_handle_purge_composite' );

/**
 * Show admin notices for LLP actions.
 */
function llp_admin_notices() {
    if ( empty( $_GET['llp_message'] ) ) {
        return;
    }

    $message = sanitize_text_field( wp_unslash( $_GET['llp_message'] ) );

    switch ( $message ) {
        case 'rerender_success':
            $class   = 'updated';
            $content = __( 'Composite re-rendered.', 'llp' );
            break;
        case 'rerender_fail':
            $class   = 'error';
            $content = __( 'Failed to re-render composite.', 'llp' );
            break;
        case 'purge_success':
            $class   = 'updated';
            $content = __( 'Composite purged.', 'llp' );
            break;
        default:
            $class   = 'updated';
            $content = esc_html( $message );
            break;
    }

    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $content ) );
}
add_action( 'admin_notices', 'llp_admin_notices' );

/**
 * Placeholder for the re-rendering logic.
 *
 * @param string $transform_json  Stored transform JSON.
 * @param mixed  $variation_settings Current variation settings.
 * @return bool Whether rendering succeeded.
 */
function llp_render_composite( $transform_json, $variation_settings ) {
    // Real rendering would happen here.
    return ! empty( $transform_json ) && ! empty( $variation_settings );
}
