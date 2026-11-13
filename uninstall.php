<?php
/**
 * Removes plugin-owned data. Product prices are never persisted by this plugin.
 *
 * @package DescuentoGlobalWoo
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'dgw_campaigns', 'dgw_applied_state', 'dgw_campaign_settings', 'dgw_applied_status' ) as $dgw_option ) {
	delete_option( $dgw_option );
}
delete_transient( 'dgw_products_onsale' );
wp_unschedule_hook( 'dgw_campaign_boundary' );
wp_clear_scheduled_hook( 'dgw_campaign_start' );
wp_clear_scheduled_hook( 'dgw_campaign_end' );
