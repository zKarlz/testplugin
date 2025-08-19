<?php
/**
 * Variation fields editor output.
 *
 * Provides inputs and container expected by assets/js/admin-variation.js.
 */
class LLP_Variation_Fields {
    /**
     * Render the variation editor.
     *
     * @param array $args Arguments: image, mask, dpi, aspect_ratio, min_resolution.
     * @return void
     */
    public static function render( $args = array() ) {
        $image         = isset( $args['image'] ) ? esc_url( $args['image'] ) : '';
        $mask          = isset( $args['mask'] ) ? esc_url( $args['mask'] ) : '';
        $dpi           = isset( $args['dpi'] ) ? intval( $args['dpi'] ) : 72;
        $aspect_ratio  = isset( $args['aspect_ratio'] ) ? esc_attr( $args['aspect_ratio'] ) : '';
        $min_resolution = isset( $args['min_resolution'] ) ? esc_attr( $args['min_resolution'] ) : '';
        ?>
        <div class="llp-variation-editor">
            <div class="llp-image-wrapper" style="position:relative; display:inline-block;">
                <?php if ( $image ) : ?>
                    <img id="llp-base-image" src="<?php echo $image; ?>" alt="" />
                <?php endif; ?>
                <?php if ( $mask ) : ?>
                    <img id="llp-mask" src="<?php echo $mask; ?>" alt="" />
                <?php endif; ?>
                <div id="llp-bounds"></div>
            </div>
            <div class="llp-editor-fields" style="margin-top:10px;">
                <label>DPI <input type="number" id="llp_dpi" name="llp_dpi" value="<?php echo $dpi; ?>" /></label>
                <label>Aspect Ratio <input type="text" id="llp_aspect_ratio" name="llp_aspect_ratio" value="<?php echo $aspect_ratio; ?>" /></label>
                <label>Min Resolution <input type="text" id="llp_min_resolution" name="llp_min_resolution" value="<?php echo $min_resolution; ?>" /></label>
            </div>
            <input type="hidden" id="llp_bound_x" name="llp_bound_x" />
            <input type="hidden" id="llp_bound_y" name="llp_bound_y" />
            <input type="hidden" id="llp_bound_width" name="llp_bound_width" />
            <input type="hidden" id="llp_bound_height" name="llp_bound_height" />
            <input type="hidden" id="llp_bound_rotation" name="llp_bound_rotation" />
        </div>
        <?php
    }
}
