<?php
/**
 * Plugin Name: Woo Laser Photo Mockup
 * Description: Variable product image upload + mockup generator with per-variation placement.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Text Domain: llp
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('LLP_VER', '1.0.0');
define('LLP_DIR', __DIR__ . '/');
define('LLP_URL', plugins_url('/', __FILE__));
// Upload dirs are calculated early so other classes can rely on them.
$uploads = wp_upload_dir(null, false);
define('LLP_UPLOAD_DIR', trailingslashit($uploads['basedir']) . 'llp/');
define('LLP_UPLOAD_URL', trailingslashit($uploads['baseurl']) . 'llp/');

// Simple autoloader for plugin classes and traits.
spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'LLP\\') !== 0) {
        return;
    }
    $parts = explode('\\', $class);
    $leaf  = array_pop($parts);
    $leaf  = strtolower(str_replace('_', '-', $leaf));
    if ($parts && 'Traits' === $parts[count($parts) - 1]) {
        $file = LLP_DIR . 'traits/trait-' . $leaf . '.php';
    } else {
        $file = LLP_DIR . 'includes/class-llp-' . $leaf . '.php';
    }
    if (file_exists($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, ['\LLP\Cron', 'activate']);
register_deactivation_hook(__FILE__, ['\LLP\Cron', 'deactivate']);

add_action('plugins_loaded', static function () {
    if (!class_exists('WooCommerce')) {
        return;
    }
    require_once LLP_DIR . 'includes/class-llp-plugin.php';
    \LLP\Plugin::instance()->boot();
});
