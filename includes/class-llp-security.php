<?php
/**
 * Security helpers for LLP uploads.
 */

class LLP_Security {
    /**
     * Process an uploaded image ensuring it is safe and meets requirements.
     *
     * @param array $file         Uploaded file array from $_FILES.
     * @param int   $variation_id Product variation ID containing requirements.
     * @param string|null $cart_item_key Optional cart item key for WooCommerce cart meta.
     * @param int|null    $order_id      Optional order ID for storing hash meta.
     * @return array|WP_Error           Array of file data or WP_Error on failure.
     */
    public static function process_upload( $file, $variation_id, $cart_item_key = null, $order_id = null ) {
        if ( ! empty( $file['error'] ) ) {
            return new WP_Error( 'upload_error', __( 'File failed to upload.', 'llp' ) );
        }

        $tmp_file = $file['tmp_name'];

        // Validate MIME type with finfo.
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime  = finfo_file( $finfo, $tmp_file );
        finfo_close( $finfo );
        $allowed = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
        if ( false === $mime || ! in_array( $mime, $allowed, true ) ) {
            return new WP_Error( 'invalid_mime', __( 'Invalid image type.', 'llp' ) );
        }

        // Validate that the file is a real image and grab dimensions.
        $image_info = getimagesize( $tmp_file );
        if ( false === $image_info ) {
            return new WP_Error( 'invalid_image', __( 'Uploaded file is not a valid image.', 'llp' ) );
        }
        list( $width, $height ) = $image_info;

        // Normalize orientation and strip metadata using GD.
        $image = null;
        switch ( $mime ) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg( $tmp_file );
                if ( function_exists( 'exif_read_data' ) ) {
                    $exif = @exif_read_data( $tmp_file );
                    if ( ! empty( $exif['Orientation'] ) ) {
                        switch ( $exif['Orientation'] ) {
                            case 3:
                                $image = imagerotate( $image, 180, 0 );
                                break;
                            case 6:
                                $image = imagerotate( $image, -90, 0 );
                                break;
                            case 8:
                                $image = imagerotate( $image, 90, 0 );
                                break;
                        }
                    }
                }
                imagejpeg( $image, $tmp_file, 90 );
                break;
            case 'image/png':
                $image = imagecreatefrompng( $tmp_file );
                imagepng( $image, $tmp_file );
                break;
            case 'image/gif':
                $image = imagecreatefromgif( $tmp_file );
                imagegif( $image, $tmp_file );
                break;
            case 'image/webp':
                if ( function_exists( 'imagecreatefromwebp' ) ) {
                    $image = imagecreatefromwebp( $tmp_file );
                    imagewebp( $image, $tmp_file );
                }
                break;
        }
        if ( $image ) {
            $width  = imagesx( $image );
            $height = imagesy( $image );
            imagedestroy( $image );
        }

        // Enforce variation resolution requirements.
        $min_res = get_post_meta( $variation_id, '_llp_min_resolution', true );
        if ( $min_res ) {
            list( $min_w, $min_h ) = array_map( 'intval', explode( 'x', strtolower( $min_res ) ) );
            if ( $width < $min_w || $height < $min_h ) {
                return new WP_Error( 'low_resolution', sprintf( __( 'Image must be at least %1$dx%2$d pixels.', 'llp' ), $min_w, $min_h ) );
            }
        }

        $aspect = get_post_meta( $variation_id, '_llp_aspect_ratio', true );
        if ( $aspect ) {
            $desired_ratio = 0;
            if ( strpos( $aspect, ':' ) !== false ) {
                list( $ar_w, $ar_h ) = array_map( 'floatval', explode( ':', $aspect ) );
                $desired_ratio      = $ar_w / $ar_h;
            } else {
                $desired_ratio = (float) $aspect;
            }
            $actual_ratio = $width / $height;
            if ( abs( $actual_ratio - $desired_ratio ) > 0.01 ) {
                return new WP_Error( 'wrong_aspect', sprintf( __( 'Image must have an aspect ratio of %s.', 'llp' ), $aspect ) );
            }
        }

        // Prepare destination with randomized filename.
        $upload_dir = wp_upload_dir();
        if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
            return new WP_Error( 'mkdir_failed', __( 'Could not create upload directory.', 'llp' ) );
        }

        $extension  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $filename   = wp_generate_password( 20, false ) . '.' . $extension;
        $filename   = wp_unique_filename( $upload_dir['path'], $filename );
        $dest_path  = trailingslashit( $upload_dir['path'] ) . $filename;
        $dest_url   = trailingslashit( $upload_dir['url'] ) . $filename;

        if ( ! @move_uploaded_file( $tmp_file, $dest_path ) ) {
            return new WP_Error( 'move_failed', __( 'Could not save uploaded file.', 'llp' ) );
        }

        // Compute SHA-256 hash and store in meta.json.
        $hash      = hash_file( 'sha256', $dest_path );
        $meta_file = dirname( __DIR__ ) . '/meta.json';
        $meta      = array();
        if ( file_exists( $meta_file ) ) {
            $meta = json_decode( file_get_contents( $meta_file ), true );
            if ( ! is_array( $meta ) ) {
                $meta = array();
            }
        }
        $meta[ $filename ] = $hash;
        file_put_contents( $meta_file, wp_json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

        // Record hash in cart and order meta for integrity checks.
        if ( function_exists( 'WC' ) ) {
            if ( $cart_item_key && isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
                WC()->cart->cart_contents[ $cart_item_key ]['llp_hash'] = $hash;
            }
            if ( $order_id ) {
                update_post_meta( $order_id, '_llp_hash', $hash );
            }
        }

        return array(
            'file' => $dest_path,
            'url'  => $dest_url,
            'hash' => $hash,
            'width' => $width,
            'height' => $height,
        );
    }
}
