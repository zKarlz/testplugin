<?php
/**
 * Simple plugin file providing Security class for upload processing.
 */

class Security
{
    /**
     * Process an uploaded image.
     *
     * @param string $file      Absolute path to the uploaded file.
     * @param int    $order_id  Optional WooCommerce order ID for storing metadata.
     *
     * @return array Information about the stored file.
     * @throws RuntimeException On validation failures.
     */
    public static function process_upload(string $file, int $order_id = 0): array
    {
        if (!file_exists($file)) {
            throw new RuntimeException('File does not exist.');
        }

        // Detect mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file);
        finfo_close($finfo);

        // Ensure this is an image
        $image_info = getimagesize($file);
        if (!$image_info) {
            throw new RuntimeException('Not a valid image.');
        }
        [$width, $height] = $image_info;

        // Orientation and stripping metadata
        self::orient_and_strip($file, $mime);

        // Enforce minimum resolution
        $min_res = self::parse_resolution(get_option('_llp_min_resolution', '0x0'));
        if ($width < $min_res[0] || $height < $min_res[1]) {
            throw new RuntimeException('Image resolution too small.');
        }

        // Enforce aspect ratio if configured
        $ar = get_option('_llp_aspect_ratio', '');
        if ($ar) {
            [$ar_w, $ar_h] = array_map('floatval', explode(':', $ar));
            $ratio = $width / $height;
            $expected = $ar_w / $ar_h;
            if (abs($ratio - $expected) > 0.01) {
                throw new RuntimeException('Invalid aspect ratio.');
            }
        }

        // Generate new filename
        $ext      = pathinfo($file, PATHINFO_EXTENSION);
        $new_name = bin2hex(random_bytes(16)) . ($ext ? '.' . strtolower($ext) : '');
        $dir      = dirname($file);
        $new_path = $dir . DIRECTORY_SEPARATOR . $new_name;
        rename($file, $new_path);

        // Compute hash
        $hash = hash_file('sha256', $new_path);

        // Persist metadata in meta.json
        self::store_meta($new_name, $hash, $width, $height, $mime);

        // Store hash in cart and order meta when WooCommerce is available
        if (function_exists('WC')) {
            $cart = WC()->cart;
            if ($cart) {
                foreach ($cart->get_cart() as $key => $item) {
                    $cart->cart_contents[$key]['llp_hash'] = $hash;
                }
            }
        }
        if ($order_id && function_exists('update_post_meta')) {
            update_post_meta($order_id, '_llp_hash', $hash);
        }

        return [
            'path' => $new_path,
            'hash' => $hash,
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
        ];
    }

    /**
     * Auto-orient an image and remove all metadata.
     */
    private static function orient_and_strip(string $file, string $mime): void
    {
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file);
            $orientation = $exif['Orientation'] ?? 1;
            $image = imagecreatefromjpeg($file);
            switch ($orientation) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
            imagejpeg($image, $file, 90); // Re-save strips metadata
            imagedestroy($image);
        } elseif ($mime === 'image/png') {
            $image = imagecreatefrompng($file);
            imagepng($image, $file);
            imagedestroy($image);
        }
    }

    private static function parse_resolution(string $value): array
    {
        if (strpos($value, 'x') === false) {
            return [0, 0];
        }
        return array_map('intval', explode('x', strtolower($value)));
    }

    private static function store_meta(string $filename, string $hash, int $width, int $height, string $mime): void
    {
        $meta_file = __DIR__ . '/meta.json';
        $data = [];
        if (file_exists($meta_file)) {
            $json = file_get_contents($meta_file);
            $data = json_decode($json, true) ?: [];
        }
        $data[$filename] = [
            'hash' => $hash,
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
        ];
        file_put_contents($meta_file, json_encode($data, JSON_PRETTY_PRINT));
    }
}

// Provide stubs for WordPress functions when running outside of WP
if (!function_exists('get_option')) {
    function get_option(string $name, $default = null) {
        return $default;
    }
}

?>
