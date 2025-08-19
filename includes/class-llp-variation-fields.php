<?php
/**
 * Variation fields for LLP editor.
 */
class LLP_Variation_Fields {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation_fields' ), 10, 3 );
        add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ), 10, 2 );
    }

    /**
     * Enqueue admin script for variation editor.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_script( 'llp-admin-variation', plugins_url( '../assets/js/admin-variation.js', __FILE__ ), array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-resizable' ), '1.0', true );
        wp_enqueue_media();
    }

    /**
     * Output custom fields for each variation.
     */
    public function variation_fields( $loop, $variation_data, $variation ) {
        $base_id   = get_post_meta( $variation->ID, '_llp_base_image_id', true );
        $mask_id   = get_post_meta( $variation->ID, '_llp_mask_image_id', true );
        $rotation  = get_post_meta( $variation->ID, '_llp_rotation', true );
        $dpi       = get_post_meta( $variation->ID, '_llp_dpi', true );
        $ratio     = get_post_meta( $variation->ID, '_llp_aspect_ratio', true );
        $min_res   = get_post_meta( $variation->ID, '_llp_min_resolution', true );
        $config    = get_post_meta( $variation->ID, '_llp_editor_config', true );
        ?>
        <div class="llp-variation-fields">
            <p class="form-field">
                <label><?php esc_html_e( 'Base Image', 'llp' ); ?></label>
                <input type="hidden" class="llp-base-image-id" name="llp_base_image_id[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $base_id ); ?>" />
                <span class="llp-base-image-preview">
                    <?php if ( $base_id ) { echo wp_get_attachment_image( $base_id, 'thumbnail' ); } ?>
                </span>
                <button class="button llp-base-image-upload"><?php esc_html_e( 'Select image', 'llp' ); ?></button>
            </p>
            <p class="form-field">
                <label><?php esc_html_e( 'Mask Image', 'llp' ); ?></label>
                <input type="hidden" class="llp-mask-image-id" name="llp_mask_image_id[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $mask_id ); ?>" />
                <span class="llp-mask-image-preview">
                    <?php if ( $mask_id ) { echo wp_get_attachment_image( $mask_id, 'thumbnail' ); } ?>
                </span>
                <button class="button llp-mask-image-upload"><?php esc_html_e( 'Select image', 'llp' ); ?></button>
            </p>
            <div class="llp-editor-canvas" data-loop="<?php echo esc_attr( $loop ); ?>">
                <div class="llp-selection">
                    <img class="llp-mask-overlay" style="display:none;" src="<?php echo $mask_id ? esc_url( wp_get_attachment_url( $mask_id ) ) : ''; ?>" />
                </div>
                <input type="number" class="llp-width" name="llp_width[<?php echo esc_attr( $loop ); ?>]" value="" placeholder="Width" />
                <input type="number" class="llp-height" name="llp_height[<?php echo esc_attr( $loop ); ?>]" value="" placeholder="Height" />
                <input type="number" class="llp-rotation" name="llp_rotation[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $rotation ); ?>" placeholder="Rotation" />
                <label><input type="checkbox" class="llp-toggle-mask" /> <?php esc_html_e( 'Show mask overlay', 'llp' ); ?></label>
            </div>
            <?php
            woocommerce_wp_text_input( array(
                'id'    => "llp_dpi[$loop]",
                'label' => __( 'DPI', 'llp' ),
                'value' => $dpi,
                'type'  => 'number',
            ) );
            woocommerce_wp_text_input( array(
                'id'    => "llp_aspect_ratio[$loop]",
                'label' => __( 'Aspect Ratio', 'llp' ),
                'value' => $ratio,
                'type'  => 'text',
            ) );
            woocommerce_wp_text_input( array(
                'id'    => "llp_min_resolution[$loop]",
                'label' => __( 'Min Resolution', 'llp' ),
                'value' => $min_res,
                'type'  => 'text',
            ) );
            ?>
            <input type="hidden" name="llp_editor_config[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $config ); ?>" />
        </div>
        <?php
    }

    /**
     * Save variation fields.
     */
    public function save_variation_fields( $variation_id, $i ) {
        $base = isset( $_POST['llp_base_image_id'][ $i ] ) ? absint( $_POST['llp_base_image_id'][ $i ] ) : '';
        $mask = isset( $_POST['llp_mask_image_id'][ $i ] ) ? absint( $_POST['llp_mask_image_id'][ $i ] ) : '';
        $rotation = isset( $_POST['llp_rotation'][ $i ] ) ? floatval( $_POST['llp_rotation'][ $i ] ) : '';
        $dpi = isset( $_POST['llp_dpi'][ $i ] ) ? intval( $_POST['llp_dpi'][ $i ] ) : '';
        $ratio = isset( $_POST['llp_aspect_ratio'][ $i ] ) ? sanitize_text_field( $_POST['llp_aspect_ratio'][ $i ] ) : '';
        $min_res = isset( $_POST['llp_min_resolution'][ $i ] ) ? sanitize_text_field( $_POST['llp_min_resolution'][ $i ] ) : '';
        $config = isset( $_POST['llp_editor_config'][ $i ] ) ? wp_unslash( $_POST['llp_editor_config'][ $i ] ) : '';

        update_post_meta( $variation_id, '_llp_base_image_id', $base );
        update_post_meta( $variation_id, '_llp_mask_image_id', $mask );
        update_post_meta( $variation_id, '_llp_rotation', $rotation );
        update_post_meta( $variation_id, '_llp_dpi', $dpi );
        update_post_meta( $variation_id, '_llp_aspect_ratio', $ratio );
        update_post_meta( $variation_id, '_llp_min_resolution', $min_res );

        $decoded = json_decode( $config, true );
        if ( null !== $decoded && JSON_ERROR_NONE === json_last_error() ) {
            update_post_meta( $variation_id, '_llp_editor_config', wp_slash( $config ) );
        }
    }
}
