<?php
/**
 * Main plugin coordinator.
 *
 * @package DescuentoGlobalWoo
 */

defined( 'ABSPATH' ) || exit;

final class DGW_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var DGW_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Campaign service.
	 *
	 * @var DGW_Campaign
	 */
	private $campaign;

	/**
	 * Returns the singleton instance.
	 *
	 * @return DGW_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers plugin services.
	 */
	private function __construct() {
		load_plugin_textdomain( 'descuento-global-woo', false, dirname( plugin_basename( DGW_PLUGIN_FILE ) ) . '/languages' );

		$this->campaign = new DGW_Campaign();
		$this->campaign->register_hooks();

		if ( is_admin() ) {
			$admin = new DGW_Admin( $this->campaign );
			$admin->register_hooks();
		}
	}
}
