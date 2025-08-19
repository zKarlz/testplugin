<?php
/**
 * Handles custom variation fields for Live Label Preview.
 */
class LLP_Variation_Fields {

    public function __construct() {
        // Render custom fields in variation editor
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render_fields' ), 10, 3 );
        // Save values when variation is saved
        add_action( 'woocommerce_save_product_variation', array( $this, 'save' ), 10, 2 );
    }

    /**
     * Output editor container with data attributes and numeric inputs.
     */
    public function render_fields( $loop, $variation_data, $variation ) {
        $fields = array( 'x', 'y', 'width', 'height', 'rotation', 'dpi' );
        $values = array();
        foreach ( $fields as $field ) {
            $values[ $field ] = get_post_meta( $variation->ID, '_llp_' . $field, true );
        }
        printf( '<div class="llp-variation-editor" data-x="%1$s" data-y="%2$s" data-width="%3$s" data-height="%4$s" data-rotation="%5$s" data-dpi="%6$s">',
            esc_attr( $values['x'] ),
            esc_attr( $values['y'] ),
            esc_attr( $values['width'] ),
            esc_attr( $values['height'] ),
            esc_attr( $values['rotation'] ),
            esc_attr( $values['dpi'] )
        );
        echo '<img class="variation-preview" src="" alt="" />';
        echo '<div class="llp-mask" style="display:none"></div>';
        foreach ( $fields as $field ) {
            printf(
                '<p class="form-row form-row-full"><label>%1$s</label><input type="number" step="any" class="llp-variation-field" data-field="%2$s" name="llp_variation[%3$d][%2$s]" value="%4$s" /></p>',
                esc_html( ucfirst( $field ) ),
                esc_attr( $field ),
                absint( $loop ),
                esc_attr( $values[ $field ] )
            );
        }
        echo '<p class="form-row form-row-full"><label><input type="checkbox" class="toggle-mask" /> ' . esc_html__( 'Show mask', 'llp' ) . '</label></p>';
        echo '</div>';
    }

    /**
     * Save the posted variation fields.
     */
    public function save( $variation_id, $i ) {
        if ( empty( $_POST['llp_variation'][ $i ] ) ) {
            return;
        }
        $fields = array( 'x', 'y', 'width', 'height', 'rotation', 'dpi' );
        foreach ( $fields as $field ) {
            if ( isset( $_POST['llp_variation'][ $i ][ $field ] ) ) {
                update_post_meta( $variation_id, '_llp_' . $field, wc_clean( wp_unslash( $_POST['llp_variation'][ $i ][ $field ] ) ) );
            }
        }
    }
}

new LLP_Variation_Fields();
