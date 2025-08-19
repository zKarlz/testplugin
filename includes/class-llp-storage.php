<?php
/**
 * File storage handler.
 *
 * @package WooLaserPhotoMockup
 */

namespace LLP;

use LLP\Traits\Singleton;

class Storage {
    use Singleton;

    /**
     * Ensure directory exists.
     */
    private function ensure(string $path): void {
        if (!file_exists($path)) {
            wp_mkdir_p($path);
        }
    }

    /**
     * Base directory for assets.
     */
    public function base_dir(): string {
        $dir = LLP_UPLOAD_DIR;
        $this->ensure($dir);

        if ('private' === Settings::instance()->get('storage')) {
            $htaccess = $dir . '.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Require all denied\n");
            }
            $webconfig = $dir . 'web.config';
            if (!file_exists($webconfig)) {
                $deny = '<configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>';
                file_put_contents($webconfig, $deny);
            }
        }

        return $dir;
    }

    /**
     * Base URL for assets.
     */
    public function base_url(): string {
        return LLP_UPLOAD_URL;
    }

    /**
     * Temporary directory for upload.
     */
    public function temp_dir(string $asset_id): string {
        $dir = trailingslashit($this->base_dir() . 'tmp/' . $asset_id);
        $this->ensure($dir);
        return $dir;
    }

    /**
     * Final asset directory.
     */
    public function asset_dir(string $asset_id): string {
        $dir = trailingslashit($this->base_dir() . $asset_id);
        $this->ensure($dir);
        return $dir;
    }

    /**
     * Asset URL.
     */
    public function asset_url(string $asset_id, string $file): string {
        return trailingslashit($this->base_url()) . $asset_id . '/' . $file;
    }

    /**
     * Delete all files for an asset.
     */
    public function delete(string $asset_id): void {
        $dir = trailingslashit($this->base_dir() . $asset_id);
        if (!file_exists($dir)) {
            return;
        }
        foreach (glob($dir . '*') as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
