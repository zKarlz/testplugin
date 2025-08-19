<?php
/**
 * Image rendering utilities.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Renderer {
    use Singleton;

    /**
     * Render composite image based on settings.
     *
     * @param array $args Rendering arguments.
     */
    public function render(array $args): bool {
        $base_path    = $args['base_path'];
        $mask_path    = $args['mask_path'] ?? null;
        $user_path    = $args['user_img'];
        $bounds       = $args['bounds'];
        $transform    = $args['transform'];
        $output_dpi   = $args['output_dpi'] ?? 300;
        $composite_out= $args['out_composite'];
        $thumb_out    = $args['out_thumb'];

        if (class_exists('Imagick')) {
            try {
                $base = new \Imagick($base_path);
                $user = new \Imagick($user_path);
                $base->setImageUnits(\Imagick::RESOLUTION_PIXELSPERINCH);
                $base->setImageResolution($output_dpi, $output_dpi);

                $user->autoOrient();
                $user->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
                $user->stripImage();

                if (!empty($transform['crop'])) {
                    $user->cropImage($transform['crop']['w'], $transform['crop']['h'], $transform['crop']['x'], $transform['crop']['y']);
                }

                $user->resizeImage($bounds['w'] * $transform['scale'], $bounds['h'] * $transform['scale'], \Imagick::FILTER_LANCZOS, 1);
                $user->rotateImage(new \ImagickPixel('transparent'), $transform['rotation']);

                $canvas = $base->clone();

                $px = $bounds['x'] + ($transform['tx'] ?? 0);
                $py = $bounds['y'] + ($transform['ty'] ?? 0);

                if ($mask_path) {
                    $layer = new \Imagick();
                    $layer->newImage($base->getImageWidth(), $base->getImageHeight(), new \ImagickPixel('transparent'), 'png');
                    $layer->compositeImage($user, \Imagick::COMPOSITE_DEFAULT, $px, $py);
                    $mask = new \Imagick($mask_path);
                    $layer->compositeImage($mask, \Imagick::COMPOSITE_DSTIN, 0, 0);
                    $canvas->compositeImage($layer, \Imagick::COMPOSITE_DEFAULT, 0, 0);
                } else {
                    $canvas->compositeImage($user, \Imagick::COMPOSITE_DEFAULT, $px, $py);
                }

                $canvas->setImageFormat('png');
                $canvas->writeImage($composite_out);

                $thumb = $canvas->clone();
                $thumb->thumbnailImage(800, 0);
                $thumb->setImageFormat('jpeg');
                $thumb->writeImage($thumb_out);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        // GD fallback (very basic)
        $base = imagecreatefromstring(file_get_contents($base_path));
        $user = imagecreatefromstring(file_get_contents($user_path));
        if (!$base || !$user) {
            return false;
        }
        $canvas   = $base;
        $scaled_w = (int) ($bounds['w'] * $transform['scale']);
        $scaled_h = (int) ($bounds['h'] * $transform['scale']);
        $tmp      = imagecreatetruecolor($scaled_w, $scaled_h);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        imagecopyresampled(
            $tmp,
            $user,
            0,
            0,
            $transform['crop']['x'] ?? 0,
            $transform['crop']['y'] ?? 0,
            $scaled_w,
            $scaled_h,
            $transform['crop']['w'] ?? imagesx($user),
            $transform['crop']['h'] ?? imagesy($user)
        );
        if (!empty($transform['rotation'])) {
            $tmp = imagerotate($tmp, -$transform['rotation'], 0);
        }
        $px = $bounds['x'] + ($transform['tx'] ?? 0);
        $py = $bounds['y'] + ($transform['ty'] ?? 0);
        if ($mask_path) {
            $layer = imagecreatetruecolor(imagesx($base), imagesy($base));
            imagealphablending($layer, false);
            imagesavealpha($layer, true);
            $transparent = imagecolorallocatealpha($layer, 0, 0, 0, 127);
            imagefill($layer, 0, 0, $transparent);
            imagecopy($layer, $tmp, $px, $py, 0, 0, imagesx($tmp), imagesy($tmp));
            $mask = imagecreatefromstring(@file_get_contents($mask_path));
            if ($mask) {
                if (imagesx($mask) !== imagesx($layer) || imagesy($mask) !== imagesy($layer)) {
                    $resized_mask = imagecreatetruecolor(imagesx($layer), imagesy($layer));
                    imagealphablending($resized_mask, false);
                    imagesavealpha($resized_mask, true);
                    imagecopyresampled(
                        $resized_mask,
                        $mask,
                        0,
                        0,
                        0,
                        0,
                        imagesx($layer),
                        imagesy($layer),
                        imagesx($mask),
                        imagesy($mask)
                    );
                    imagedestroy($mask);
                    $mask = $resized_mask;
                }
                for ($x = 0; $x < imagesx($layer); $x++) {
                    for ($y = 0; $y < imagesy($layer); $y++) {
                        $layer_color = imagecolorat($layer, $x, $y);
                        $mask_color  = imagecolorat($mask, $x, $y);
                        $lrgb = imagecolorsforindex($layer, $layer_color);
                        $mrgb = imagecolorsforindex($mask, $mask_color);
                        $dest_opacity = 127 - $lrgb['alpha'];
                        $mask_opacity = 127 - $mrgb['alpha'];
                        $final_opacity = (int) ($dest_opacity * $mask_opacity / 127);
                        $new_alpha = 127 - $final_opacity;
                        $color = imagecolorallocatealpha($layer, $lrgb['red'], $lrgb['green'], $lrgb['blue'], $new_alpha);
                        imagesetpixel($layer, $x, $y, $color);
                    }
                }
                imagedestroy($mask);
            }
            imagecopy($canvas, $layer, 0, 0, 0, 0, imagesx($layer), imagesy($layer));
            imagedestroy($layer);
        } else {
            imagecopy($canvas, $tmp, $px, $py, 0, 0, imagesx($tmp), imagesy($tmp));
        }
        imagepng($canvas, $composite_out);
        $thumb = imagescale($canvas, 800);
        imagejpeg($thumb, $thumb_out, 90);
        imagedestroy($tmp);
        imagedestroy($thumb);
        imagedestroy($user);
        imagedestroy($base);
        return true;
    }
}
