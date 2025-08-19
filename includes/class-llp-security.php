<?php
class LLP_Security {
    public static function process_upload(array $file) {
        if (!isset($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            throw new \Exception('No file uploaded.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $imgInfo = @getimagesize($file['tmp_name']);
        if (!$imgInfo || $imgInfo['mime'] !== $mime) {
            throw new \Exception('File is not a valid image.');
        }
        $width = $imgInfo[0];
        $height = $imgInfo[1];

        $minRes = function_exists('get_option') ? get_option('_llp_min_resolution', '0x0') : '0x0';
        list($minW, $minH) = array_map('intval', explode('x', $minRes));
        if ($width < $minW || $height < $minH) {
            throw new \Exception('Image resolution too small.');
        }

        $aspect = function_exists('get_option') ? get_option('_llp_aspect_ratio', '') : '';
        if (!empty($aspect) && strpos($aspect, ':') !== false) {
            list($arW, $arH) = array_map('intval', explode(':', $aspect));
            $expectedRatio = $arW / $arH;
            $actualRatio = $width / $height;
            if (abs($actualRatio - $expectedRatio) > 0.01) {
                throw new \Exception('Invalid aspect ratio.');
            }
        }

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($file['tmp_name']);
                    if (!empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3: $image = imagerotate($image, 180, 0); break;
                            case 6: $image = imagerotate($image, -90, 0); break;
                            case 8: $image = imagerotate($image, 90, 0); break;
                        }
                    }
                }
                $ext = 'jpg';
                break;
            case 'image/png':
                $image = imagecreatefrompng($file['tmp_name']);
                $ext = 'png';
                break;
            default:
                throw new \Exception('Unsupported image type.');
        }

        $plugin_dir = dirname(__DIR__);
        $upload_dir = $plugin_dir . '/uploads';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $upload_dir . '/' . $filename;
        if ($ext === 'jpg') {
            imagejpeg($image, $dest, 90);
        } else {
            imagepng($image, $dest);
        }
        imagedestroy($image);
        unlink($file['tmp_name']);

        $hash = hash_file('sha256', $dest);

        $meta_file = $plugin_dir . '/meta.json';
        $meta = [];
        if (file_exists($meta_file)) {
            $data = json_decode(file_get_contents($meta_file), true);
            if (is_array($data)) {
                $meta = $data;
            }
        }
        $meta[$filename] = $hash;
        file_put_contents($meta_file, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return ['file' => $filename, 'hash' => $hash];
    }
}
