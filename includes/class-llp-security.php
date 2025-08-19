<?php
/**
 * Security helpers.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Security {
    use Singleton;

    /**
     * Generate a nonce field for forms.
     */
    public function nonce_field(): void {
        wp_nonce_field('llp', '_llp_nonce');
    }

    /**
     * Validate and move uploaded file.
     *
     * @return array|\WP_Error { width, height, sha256 }
     */
    public function process_upload(array $file, string $dest, int $variation_id) {
        $settings = Settings::instance();
        $allowed  = $settings->get('allowed_mimes');
        $max_size = (int) $settings->get('max_file_size');
        if ($file['error'] || $file['size'] > $max_size) {
            return new \WP_Error('upload_error', __('Upload failed.', 'llp'));
        }
        $type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (!$type['type'] || !in_array($type['type'], $allowed, true)) {
            return new \WP_Error('invalid_type', __('Invalid file type.', 'llp'));
        }
        $editor = wp_get_image_editor($file['tmp_name']);
        if (is_wp_error($editor)) {
            return $editor;
        }
        $editor->set_quality(100);
        $saved = $editor->save($dest, 'image/png');
        if (is_wp_error($saved)) {
            return $saved;
        }
        $size = getimagesize($dest);
        $hash = hash_file('sha256', $dest);
        return ['width' => $size[0], 'height' => $size[1], 'sha256' => $hash];
    }

    /**
     * Generate a signed URL for downloading an asset.
     */
    public function sign_url(string $asset_id, string $file, int $expires = 0): string {
        $expires = $expires ?: time() + DAY_IN_SECONDS;
        $secret  = wp_salt('llp');
        $token   = hash_hmac('sha256', $asset_id . '|' . $file . '|' . $expires, $secret);
        $base    = rest_url(sprintf('llp/v1/file/%s/%s', $asset_id, $file));
        return add_query_arg([
            'token'   => $token,
            'expires' => $expires,
        ], $base);
    }

    /**
     * Verify a signed URL token.
     */
    public function verify(string $asset_id, string $file, int $expires, string $token): bool {
        if ($expires < time()) {
            return false;
        }
        $secret  = wp_salt('llp');
        $expected = hash_hmac('sha256', $asset_id . '|' . $file . '|' . $expires, $secret);
        return hash_equals($expected, $token);
    }
}
