<?php
/**
 * Cron tasks for the LLP plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles scheduled purging of LLP assets.
 */
class LLP_Cron {

    /**
     * Purge stored assets for WooCommerce orders completed before the
     * configured retention period.
     *
     * Queries WooCommerce for orders with status `completed` whose
     * completion date is older than the retention period. For each order,
     * attachment IDs stored in `_llp_asset_ids` order meta are deleted. The
     * order is then marked with `_llp_assets_purged` meta containing the IDs
     * of the removed attachments and a log entry is written for debugging.
     *
     * @return void
     */
    public function purge() {
        // Retention period in days; default to 30 days.
        $retention_days = absint( get_option( 'llp_retention_days', 30 ) );
        $cutoff         = gmdate( 'Y-m-d H:i:s', strtotime( sprintf( '-%d days', $retention_days ) ) );

        if ( ! function_exists( 'wc_get_orders' ) ) {
            return;
        }

        $orders = wc_get_orders(
            [
                'status'         => 'completed',
                'limit'          => -1,
                'return'         => 'ids',
                'date_completed' => '<=' . $cutoff,
            ]
        );

        foreach ( $orders as $order_id ) {
            $asset_ids = (array) get_post_meta( $order_id, '_llp_asset_ids', true );
            $purged    = [];

            foreach ( $asset_ids as $asset_id ) {
                if ( wp_delete_attachment( $asset_id, true ) ) {
                    $purged[] = $asset_id;
                }
            }

            if ( ! empty( $purged ) ) {
                update_post_meta( $order_id, '_llp_assets_purged', $purged );
                error_log( sprintf( 'LLP Cron purged assets [%s] for order %d', implode( ',', $purged ), $order_id ) );
            }
        }
    }
}
