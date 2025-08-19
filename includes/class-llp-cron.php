<?php
/**
 * Scheduled cleanup tasks.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Cron {
    use Singleton;

    private function __construct() {
        add_action('llp_daily_purge', [$this, 'purge']);
    }

    public static function activate(): void {
        if (!wp_next_scheduled('llp_daily_purge')) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'llp_daily_purge');
        }
    }

    public static function deactivate(): void {
        $timestamp = wp_next_scheduled('llp_daily_purge');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'llp_daily_purge');
        }
    }

    /**
     * Purge assets older than configured days.
     */
    public function purge(): void {
        $days = (int) Settings::instance()->get('auto_purge_days');
        if ($days <= 0) {
            return;
        }
        $cutoff = time() - DAY_IN_SECONDS * $days;
        $dir = Storage::instance()->base_dir();
        foreach (glob($dir . '*/', GLOB_ONLYDIR) as $asset_dir) {
            if (filemtime($asset_dir) < $cutoff) {
                foreach (glob($asset_dir . '*') as $file) {
                    @unlink($file);
                }
                @rmdir($asset_dir);
            }
        }
    }
}
