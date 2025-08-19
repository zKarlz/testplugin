<?php

class LLP_Cron {
    /**
     * Purge expired assets attached to completed orders.
     */
    public static function purge() {
        $retention_days = absint( get_option( 'llp_asset_retention_days', 30 ) );
        if ( ! $retention_days ) {
            return;
        }

        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $retention_days . ' days', current_time( 'timestamp', true ) ) );

        $orders = wc_get_orders( array(
            'status'        => 'completed',
            'date_completed'=> '<' . $cutoff,
            'limit'         => -1,
            'return'        => 'ids',
        ) );

        if ( empty( $orders ) ) {
            return;
        }

        $logger = wc_get_logger();
        foreach ( $orders as $order_id ) {
            $asset_ids = (array) get_post_meta( $order_id, '_llp_asset_ids', true );
            if ( empty( $asset_ids ) ) {
                continue;
            }

            foreach ( $asset_ids as $asset_id ) {
                wp_delete_attachment( $asset_id, true );
            }

            update_post_meta( $order_id, '_llp_purged_assets', $asset_ids );
            $logger->info( sprintf( 'Purged LLP assets for order %d: %s', $order_id, implode( ',', $asset_ids ) ), array( 'source' => 'llp-cron' ) );
        }
    }
}
