<?php
/**
 * Handle LLP order meta box display.
 */
class LLP_Order {
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
    }

    /**
     * Register meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box( 'llp_order_meta', __( 'LLP Files', 'llp' ), array( $this, 'render_meta_box' ), 'shop_order', 'side' );
    }

    /**
     * Render meta box content.
     *
     * @param WP_Post $post Order post object.
     */
    public function render_meta_box( $post ) {
        $original  = get_post_meta( $post->ID, '_llp_original_url', true );
        $composite = get_post_meta( $post->ID, '_llp_composite_url', true );

        $original_url  = $this->get_signed_url( $original );
        $composite_url = $this->get_signed_url( $composite );
        ?>
        <p>
            <?php if ( $original_url ) : ?>
                <a class="button" href="<?php echo esc_url( $original_url ); ?>" target="_blank"><?php esc_html_e( 'Open Original', 'llp' ); ?></a>
            <?php endif; ?>
            <?php if ( $composite_url ) : ?>
                <a class="button" href="<?php echo esc_url( $composite_url ); ?>" target="_blank"><?php esc_html_e( 'Open Composite', 'llp' ); ?></a>
            <?php endif; ?>
        </p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'llp_rerender_composite' ); ?>
            <input type="hidden" name="action" value="llp_rerender_composite" />
            <input type="hidden" name="order_id" value="<?php echo esc_attr( $post->ID ); ?>" />
            <p>
                <input type="submit" class="button" value="<?php esc_attr_e( 'Re-render composite', 'llp' ); ?>" />
            </p>
        </form>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to purge the composite?', 'llp' ) ); ?>');">
            <?php wp_nonce_field( 'llp_purge_composite' ); ?>
            <input type="hidden" name="action" value="llp_purge_composite" />
            <input type="hidden" name="order_id" value="<?php echo esc_attr( $post->ID ); ?>" />
            <p>
                <input type="submit" class="button" value="<?php esc_attr_e( 'Purge composite', 'llp' ); ?>" />
            </p>
        </form>
        <?php
    }

    /**
     * Get a signed URL for a file.
     *
     * @param string $url File URL.
     * @return string Signed URL.
     */
    private function get_signed_url( $url ) {
        if ( empty( $url ) ) {
            return '';
        }
        return wp_nonce_url( $url, 'llp_file' );
    }
}
