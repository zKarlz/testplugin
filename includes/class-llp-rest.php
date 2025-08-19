<?php
/**
 * REST API endpoints.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class REST {
    use Singleton;

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('llp/v1', '/upload', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_upload'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('llp/v1', '/finalize', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_finalize'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('llp/v1', '/file/(?P<asset_id>[a-f0-9\-]+)/(?P<file>[\w\.]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'serve_file'],
            'permission_callback' => [$this, 'file_permission_check'],
            'args'                => [
                'asset_id' => ['sanitize_callback' => 'sanitize_text_field'],
                'file'     => ['sanitize_callback' => 'sanitize_file_name'],
            ],
        ]);

        register_rest_route('llp/v1', '/order/(?P<order_id>\d+)/purge', [
            'methods'             => 'POST',
            'callback'            => [$this, 'purge_order'],
            'permission_callback' => function () {
                return current_user_can('edit_shop_orders');
            },
            'args' => [
                'order_id' => ['sanitize_callback' => 'absint'],
            ],
        ]);
    }

    /**
     * Basic IP/user based rate limiting.
     */
    private function rate_limit(string $action) {
        $ip    = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
        $user  = is_user_logged_in() ? 'user_' . get_current_user_id() : 'ip_' . md5($ip);
        $key   = 'llp_rl_' . $action . '_' . $user;
        $count = (int) get_transient($key);
        if ($count >= 10) {
            return new \WP_Error('rate_limited', __('Too many requests', 'llp'), ['status' => 429]);
        }
        set_transient($key, $count + 1, MINUTE_IN_SECONDS);
        return true;
    }

    /**
     * Permission check for mutating requests.
     */
    public function permission_check(\WP_REST_Request $request) {
        if (!wp_verify_nonce($request->get_param('nonce'), 'llp')) {
            return new \WP_Error('invalid_nonce', __('Invalid nonce', 'llp'), ['status' => 403]);
        }
        $settings = Settings::instance();
        if ('private' === $settings->get('storage')) {
            if (!is_user_logged_in() || !current_user_can('upload_files')) {
                return new \WP_Error('forbidden', __('Authentication required.', 'llp'), ['status' => 401]);
            }
        }
        return true;
    }

    /**
     * Permission check for serving files.
     */
    public function file_permission_check(\WP_REST_Request $request) {
        $asset_id = $request['asset_id'];
        $file     = $request['file'];
        $token    = $request->get_param('token');
        $expires  = (int) $request->get_param('expires');
        $sec      = Security::instance();
        if (!$token || !$sec->verify($asset_id, $file, $expires, $token)) {
            return new \WP_Error('forbidden', __('Invalid token.', 'llp'), ['status' => 403]);
        }
        return true;
    }

    /**
     * Handle file upload to temp storage.
     */
    public function handle_upload(\WP_REST_Request $request) {
        $limited = $this->rate_limit('upload');
        if (is_wp_error($limited)) {
            return $limited;
        }
        $variation_id = absint($request->get_param('variation_id'));
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new \WP_Error('no_file', __('No file provided', 'llp'), ['status' => 400]);
        }
        $asset_id = wp_generate_uuid4();
        $dir      = Storage::instance()->temp_dir($asset_id);
        $dest     = $dir . 'original.png';
        $sec      = Security::instance();
        $info     = $sec->process_upload($files['file'], $dest, $variation_id);
        if (is_wp_error($info)) {
            return $info;
        }
        return rest_ensure_response([
            'asset_id' => $asset_id,
            'width'    => $info['width'],
            'height'   => $info['height'],
        ]);
    }

    /**
     * Finalize upload and render composites.
     */
    public function handle_finalize(\WP_REST_Request $request) {
        $limited = $this->rate_limit('finalize');
        if (is_wp_error($limited)) {
            return $limited;
        }
        $asset_id     = sanitize_text_field($request->get_param('asset_id'));
        $variation_id = absint($request->get_param('variation_id'));
        $transform    = json_decode($request->get_param('transform', ''), true);

        if (!$asset_id || !$variation_id || !is_array($transform)) {
            return new \WP_Error('missing_data', __('Missing data', 'llp'), ['status' => 400]);
        }

        foreach (['scale', 'rotation', 'tx', 'ty', 'crop'] as $key) {
            if (!array_key_exists($key, $transform)) {
                return new \WP_Error('invalid_transform', __('Invalid transform data', 'llp'), ['status' => 400]);
            }
        }

        if (!is_array($transform['crop'])) {
            return new \WP_Error('invalid_transform', __('Invalid crop data', 'llp'), ['status' => 400]);
        }
        foreach (['x', 'y', 'w', 'h'] as $key) {
            if (!array_key_exists($key, $transform['crop'])) {
                return new \WP_Error('invalid_transform', __('Invalid crop data', 'llp'), ['status' => 400]);
            }
            $transform['crop'][$key] = (int) $transform['crop'][$key];
        }
        if ($transform['crop']['w'] <= 0 || $transform['crop']['h'] <= 0) {
            return new \WP_Error('invalid_transform', __('Invalid crop dimensions', 'llp'), ['status' => 400]);
        }

        $transform['scale']    = (float) $transform['scale'];
        if ($transform['scale'] <= 0 || $transform['scale'] > 10) {
            return new \WP_Error('invalid_transform', __('Invalid scale value', 'llp'), ['status' => 400]);
        }
        $transform['rotation'] = max(min((float) $transform['rotation'], 360), 0);
        $transform['tx']       = (float) $transform['tx'];
        $transform['ty']       = (float) $transform['ty'];

        $storage   = Storage::instance();
        $tmp_dir   = $storage->temp_dir($asset_id);
        $final_dir = $storage->asset_dir($asset_id);
        if (!@rename($tmp_dir . 'original.png', $final_dir . 'original.png')) {
            return new \WP_Error('move_failed', __('Could not move uploaded file', 'llp'), ['status' => 500]);
        }

        $base_id   = (int) get_post_meta($variation_id, '_llp_base_image_id', true);
        $mask_id   = (int) get_post_meta($variation_id, '_llp_mask_image_id', true);
        $bounds    = json_decode((string) get_post_meta($variation_id, '_llp_bounds', true), true) ?: [];
        $dpi       = (int) get_post_meta($variation_id, '_llp_output_dpi', true) ?: 300;

        if (!$base_id || empty($bounds)) {
            return new \WP_Error('missing_meta', __('Variation not configured', 'llp'), ['status' => 400]);
        }
        foreach (['x', 'y', 'w', 'h'] as $key) {
            if (!isset($bounds[$key])) {
                return new \WP_Error('missing_meta', __('Bounds incomplete', 'llp'), ['status' => 400]);
            }
        }

        $base_path = get_attached_file($base_id);
        if (!$base_path || !file_exists($base_path)) {
            return new \WP_Error('missing_base', __('Base image not found', 'llp'), ['status' => 400]);
        }

        $mask_path = null;
        if ($mask_id) {
            $mask_path = get_attached_file($mask_id);
            if (!$mask_path || !file_exists($mask_path)) {
                return new \WP_Error('missing_mask', __('Mask image not found', 'llp'), ['status' => 400]);
            }
        }

        $renderer = Renderer::instance();
        $result = $renderer->render([
            'base_path'    => $base_path,
            'mask_path'    => $mask_path,
            'user_img'     => $final_dir . 'original.png',
            'bounds'       => $bounds,
            'transform'    => $transform,
            'output_dpi'   => $dpi,
            'out_composite'=> $final_dir . 'composite.png',
            'out_thumb'    => $final_dir . 'thumb.jpg',
        ]);

        if (!$result) {
            return new \WP_Error('render_failed', __('Rendering failed', 'llp'), ['status' => 500]);
        }

        $sec = Security::instance();
        return rest_ensure_response([
            'asset_id'      => $asset_id,
            'original_url'  => $sec->file_url($asset_id, 'original.png'),
            'composite_url' => $sec->file_url($asset_id, 'composite.png'),
            'thumb_url'     => $sec->file_url($asset_id, 'thumb.jpg'),
        ]);
    }

    /**
     * Serve a protected file if token is valid.
     */
    public function serve_file(\WP_REST_Request $request) {
        $asset_id = $request['asset_id'];
        $file     = $request['file'];
        $path = Storage::instance()->asset_dir($asset_id) . $file;
        if (!file_exists($path)) {
            return new \WP_Error('not_found', __('File not found', 'llp'), ['status' => 404]);
        }
        $mime = wp_check_filetype($path); 
        $data = file_get_contents($path);
        return new \WP_REST_Response($data, 200, ['Content-Type' => $mime['type'] ?: 'application/octet-stream']);
    }

    /**
     * Purge assets for an order.
     */
    public function purge_order(\WP_REST_Request $request) {
        if (!wp_verify_nonce($request->get_param('nonce'), 'llp')) {
            return new \WP_Error('invalid_nonce', __('Invalid nonce', 'llp'), ['status' => 403]);
        }
        $order_id = (int) $request['order_id'];
        $order    = wc_get_order($order_id);
        if (!$order) {
            return new \WP_Error('not_found', __('Order not found', 'llp'), ['status' => 404]);
        }
        foreach ($order->get_items() as $item) {
            $asset_id = $item->get_meta('_llp_asset_id');
            if ($asset_id) {
                Storage::instance()->delete($asset_id);
            }
        }
        return rest_ensure_response(['purged' => true]);
    }
}
