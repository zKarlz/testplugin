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
        $width  = $size[0];
        $height = $size[1];

        $minres = json_decode((string) get_post_meta($variation_id, '_llp_min_resolution', true), true) ?: [];
        $min_w  = isset($minres['min_w']) ? (int) $minres['min_w'] : 0;
        $min_h  = isset($minres['min_h']) ? (int) $minres['min_h'] : 0;
        if (($min_w && $width < $min_w) || ($min_h && $height < $min_h)) {
            return new \WP_Error(
                'image_too_small',
                sprintf(__('Image must be at least %1$d x %2$d pixels.', 'llp'), $min_w, $min_h)
            );
        }

        $aspect = get_post_meta($variation_id, '_llp_aspect_ratio', true);
        if ($aspect) {
            $parts = array_map('floatval', explode(':', $aspect));
            if (2 === count($parts) && $parts[0] > 0 && $parts[1] > 0) {
                $expected = $parts[0] / $parts[1];
                $actual   = $width / $height;
                if (abs($expected - $actual) > 0.01) {
                    return new \WP_Error(
                        'invalid_aspect_ratio',
                        sprintf(__('Image must have an aspect ratio of %s.', 'llp'), $aspect)
                    );
                }
            }
        }

        $hash = hash_file('sha256', $dest);
        return ['width' => $width, 'height' => $height, 'sha256' => $hash];
    }

    /**
     * Generate a URL for downloading an asset.
     *
     * Uses signed REST URLs when storage is private, otherwise returns the
     * public asset URL.
     */
    public function sign_url(string $asset_id, string $file, int $expires = 0): string {
        $settings = Settings::instance();
        if ('public' === $settings->get('storage')) {
            return Storage::instance()->asset_url($asset_id, $file);
        }

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
