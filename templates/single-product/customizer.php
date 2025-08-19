<?php
/**
 * Product customizer template.
 *
 * @var array $args Template arguments.
 */

$mockup_url = isset($mockup_url) ? $mockup_url : '';
$mask_url   = isset($mask_url) ? $mask_url : '';
$variations = isset($variations) ? $variations : [];
?>
<div class="llp-customizer">
  <?php if ( $mockup_url ) : ?>
    <img src="<?php echo esc_url( $mockup_url ); ?>" alt="mockup" class="llp-mockup" />
  <?php endif; ?>
  <div class="llp-canvas-wrapper">
    <canvas id="llp-canvas" width="400" height="400"></canvas>
    <?php if ( $mask_url ) : ?>
      <img src="<?php echo esc_url( $mask_url ); ?>" alt="mask" class="llp-mask" />
    <?php endif; ?>
  </div>
  <input type="file" id="llp-upload" accept="image/*" />
  <button id="llp-finalize" class="button">Finalize</button>
  <input type="hidden" name="llp_transform" id="llp_transform" />
  <button type="submit" class="single_add_to_cart_button button alt" id="add-to-cart" disabled>Add to cart</button>
</div>
