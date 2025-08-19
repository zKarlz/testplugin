<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WooLaserPhotoMockup
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$uploads = wp_upload_dir(null, false);
define('LLP_UPLOAD_DIR', trailingslashit($uploads['basedir']) . 'llp/');

$settings = get_option('llp_settings', []);
delete_option('llp_settings');

if (!empty($settings['delete_on_uninstall'])) {
    $dir = LLP_UPLOAD_DIR;
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
        @rmdir($dir);
    }
}
