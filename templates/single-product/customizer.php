<?php
/**
 * Product customizer template
 *
 * @var array $variation Variation data passed from LLP_Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="llp-customizer"
     data-bounds-width="<?php echo esc_attr( $variation['bounds']['width'] ); ?>"
     data-bounds-height="<?php echo esc_attr( $variation['bounds']['height'] ); ?>"
     data-aspect="<?php echo esc_attr( $variation['aspect'] ); ?>">
    <div class="llp-canvas-stack">
        <img src="<?php echo esc_url( $variation['base'] ); ?>" class="llp-base" alt="" />
        <canvas width="<?php echo esc_attr( $variation['bounds']['width'] ); ?>" height="<?php echo esc_attr( $variation['bounds']['height'] ); ?>"></canvas>
        <img src="<?php echo esc_url( $variation['mockup'] ); ?>" class="llp-mockup" alt="" />
        <img src="<?php echo esc_url( $variation['mask'] ); ?>" class="llp-mask" alt="" style="display:none;" />
    </div>
    <input type="file" id="llp-upload" accept="image/*" />
    <button type="button" class="llp-finalize"><?php esc_html_e( 'Finalize', 'testplugin' ); ?></button>
    <input type="hidden" name="llp_composite" id="llp-composite" value="" />
</div>
