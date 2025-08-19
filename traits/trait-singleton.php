<?php
/**
 * Simple singleton trait.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP\Traits;

trait Singleton {
    private static ?self $instance = null;

    final public static function instance(): self {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    private function __clone() {}
    public function __wakeup() {}
}
