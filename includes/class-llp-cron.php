<?php
/**
 * Handles LLP cron cleanup tasks.
 */
class LLP_Cron {
    /**
     * Purge assets for completed orders older than the retention period.
     */
    public function purge() {
        $retention_days = (int) get_option( 'llp_retention_days', 0 );
        if ( $retention_days <= 0 ) {
            return; // No retention period configured.
        }

        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

        $args = [
            'status'       => 'completed',
            'type'         => 'shop_order',
            'return'       => 'ids',
            'limit'        => -1,
            'date_created' => '<' . $cutoff,
            'meta_query'   => [
                [
                    'key'     => '_llp_purged',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        $order_ids = wc_get_orders( $args );
        if ( empty( $order_ids ) ) {
            return;
        }

        $logger  = wc_get_logger();
        $context = [ 'source' => 'llp-cron' ];

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }

            $asset_ids = (array) $order->get_meta( '_llp_assets' );
            foreach ( $asset_ids as $asset_id ) {
                if ( function_exists( 'llp_delete_asset' ) ) {
                    llp_delete_asset( $asset_id );
                }
                $logger->info( sprintf( 'Purged asset %s for order %s', $asset_id, $order_id ), $context );
            }

            $order->update_meta_data( '_llp_purged', '1' );
            $order->save();
        }
    }
}
