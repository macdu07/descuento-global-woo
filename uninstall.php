<?php
/**
 * Removes plugin-owned data. Product prices are never persisted by this plugin.
 *
 * @package DescuentoGlobalWoo
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'dgw_campaign_settings' );
wp_clear_scheduled_hook( 'dgw_campaign_start' );
wp_clear_scheduled_hook( 'dgw_campaign_end' );
