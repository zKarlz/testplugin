<?php
/**
 * Customer-facing image customizer.
 *
 * @package WooLaserPhotoMockup
 */

if (!defined('ABSPATH')) {
    exit;
}

echo '<div id="llp-customizer">';
?>
<input type="file" id="llp-upload" accept="image/*" />
<input type="hidden" name="_llp_asset_id" id="llp_asset_id" />
<input type="hidden" name="_llp_transform" id="llp_transform" />
<button type="button" id="llp-upload-btn"><?php esc_html_e('Upload Photo', 'llp'); ?></button>
<div id="llp-preview"></div>
</div>
