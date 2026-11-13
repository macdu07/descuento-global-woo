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
		add_action( 'init', array( $this, 'load_textdomain' ) );

		$store = new DGW_Campaign_Store();
		$store->register_hooks();

		$this->campaign = new DGW_Campaign( $store );
		$this->campaign->register_hooks();

		if ( is_admin() ) {
			$admin = new DGW_Admin( $store, $this->campaign );
			$admin->register_hooks();
		}
	}

	/**
	 * Loads bundled translations once WordPress is ready for them.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'descuento-global-woo', false, dirname( plugin_basename( DGW_PLUGIN_FILE ) ) . '/languages' );
	}
}
