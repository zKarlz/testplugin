<?php
/**
 * Email thumbnail preview template.
 *
 * @package WooLaserPhotoMockup
 */

if (!defined('ABSPATH')) {
    exit;
}

$thumb_url = $thumb_url ?? '';
echo '<div class="llp-email-thumb"><img src="' . esc_url($thumb_url) . '" alt="" style="max-width:120px;" /></div>';
