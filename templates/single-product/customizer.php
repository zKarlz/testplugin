<?php
/**
 * Customer-facing image customizer.
 *
 * @package WooLaserPhotoMockup
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="llp-customizer">
    <input type="file" id="llp-upload" accept="image/*" />
    <input type="hidden" name="_llp_asset_id" id="llp_asset_id" />
    <input type="hidden" name="_llp_transform" id="llp_transform" />
    <button type="button" id="llp-upload-btn"><?php esc_html_e('Upload Photo', 'llp'); ?></button>

    <div id="llp-editor">
        <img id="llp-base" class="llp-layer" alt="" />
        <canvas id="llp-canvas"></canvas>
        <img id="llp-mask" class="llp-layer" alt="" />
        <div class="llp-controls">
            <button type="button" id="llp-rotate-left">&larr;</button>
            <button type="button" id="llp-rotate-right">&rarr;</button>
            <button type="button" id="llp-finalize-btn"><?php esc_html_e('Finalize', 'llp'); ?></button>
        </div>
    </div>

    <div id="llp-preview"></div>
</div>

