<?php
/**
 * Plugin settings handler.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Settings {
    use Singleton;

    /**
     * Default settings.
     *
     * @var array
     */
    private array $defaults = [
        'max_file_size'   => 15 * 1024 * 1024,
        'allowed_mimes'   => ['image/jpeg', 'image/png', 'image/webp'],
        'storage'         => 'private',
        'auto_purge_days' => 0,
    ];

    private function __construct() {
        add_action('admin_init', [$this, 'register']);
        add_action('admin_menu', [$this, 'menu']);
    }

    /**
     * Register setting.
     */
    public function register(): void {
        register_setting('llp_settings', 'llp_settings', [$this, 'sanitize']);
    }

    /**
     * Add menu page under WooCommerce.
     */
    public function menu(): void {
        add_submenu_page(
            'woocommerce',
            __('Laser Photo Mockup', 'llp'),
            __('Laser Photo Mockup', 'llp'),
            'manage_woocommerce',
            'llp-settings',
            [$this, 'render_page']
        );
    }

    /**
     * Render settings page.
     */
    public function render_page(): void {
        $opts = $this->get();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Laser Photo Mockup Settings', 'llp'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('llp_settings');
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Max file size (bytes)', 'llp'); ?></th>
                        <td><input type="number" name="llp_settings[max_file_size]" value="<?php echo esc_attr($opts['max_file_size']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Allowed MIME types', 'llp'); ?></th>
                        <td><input type="text" name="llp_settings[allowed_mimes]" value="<?php echo esc_attr(implode(',', $opts['allowed_mimes'])); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Storage mode', 'llp'); ?></th>
                        <td>
                            <select name="llp_settings[storage]">
                                <option value="private" <?php selected($opts['storage'], 'private'); ?>><?php esc_html_e('Private', 'llp'); ?></option>
                                <option value="public" <?php selected($opts['storage'], 'public'); ?>><?php esc_html_e('Public', 'llp'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto purge days', 'llp'); ?></th>
                        <td><input type="number" name="llp_settings[auto_purge_days]" value="<?php echo esc_attr($opts['auto_purge_days']); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize settings input.
     */
    public function sanitize(array $input): array {
        $clean = [
            'max_file_size'   => absint($input['max_file_size'] ?? $this->defaults['max_file_size']),
            'allowed_mimes'   => array_filter(array_map('sanitize_text_field', explode(',', $input['allowed_mimes'] ?? ''))),
            'storage'         => in_array($input['storage'] ?? 'private', ['private', 'public'], true) ? $input['storage'] : 'private',
            'auto_purge_days' => absint($input['auto_purge_days'] ?? 0),
        ];
        return $clean;
    }

    /**
     * Get setting value or all settings.
     */
    public function get(?string $key = null) {
        $opts = wp_parse_args(get_option('llp_settings', []), $this->defaults);
        if ($key) {
            return $opts[$key] ?? null;
        }
        return $opts;
    }
}
