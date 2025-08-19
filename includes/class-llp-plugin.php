<?php
/**
 * Core plugin bootstrap.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Plugin {
    use Singleton;

    /**
     * Boot plugin services.
     */
    public function boot(): void {
        // Register hooks or instantiate core classes.
        Settings::instance();
        Frontend::instance();
        Variation_Fields::instance();
        Order::instance();
        REST::instance();
        Cron::instance();
        Renderer::instance();
        Storage::instance();
        Security::instance();
    }
}
