<?php
/**
 * Product customizer template.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = isset( $settings ) ? $settings : array();
$bounds   = isset( $settings['bounds'] ) ? $settings['bounds'] : array( 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 300 );
?>
<div id="llp-customizer" data-settings='<?php echo esc_attr( wp_json_encode( $settings ) ); ?>'>
    <div class="llp-canvas-wrapper" style="position:relative;">
        <?php if ( ! empty( $settings['base'] ) ) : ?>
            <img src="<?php echo esc_url( $settings['base'] ); ?>" class="llp-base" style="position:absolute; top:0; left:0;" />
        <?php endif; ?>
        <canvas id="llp-canvas" style="position:absolute; left:<?php echo esc_attr( $bounds['x'] ); ?>px; top:<?php echo esc_attr( $bounds['y'] ); ?>px;"></canvas>
        <?php if ( ! empty( $settings['mask'] ) ) : ?>
            <img src="<?php echo esc_url( $settings['mask'] ); ?>" class="llp-mask" style="position:absolute; top:0; left:0;" />
        <?php endif; ?>
    </div>
    <input type="file" id="llp-upload" accept="image/*" />
    <button type="button" id="llp-finalize"><?php esc_html_e( 'Finalize', 'llp' ); ?></button>
    <button type="submit" class="single_add_to_cart_button button alt" disabled><?php esc_html_e( 'Add to cart', 'llp' ); ?></button>
    <input type="hidden" name="llp_finalized" id="llp_finalized" value="0" />
</div>
