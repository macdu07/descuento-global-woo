<?php
/**
 * Plugin Name:       Descuento Global Woo
 * Description:       Schedules store-wide or category discount campaigns without changing the prices saved in WooCommerce.
 * Version:           1.3.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Mauricio Correa
 * Author URI:        https://maurouix.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       descuento-global-woo
 * Domain Path:       /languages
 * WC requires at least: 11.0
 * WC tested up to:   11.3
 *
 * @package DescuentoGlobalWoo
 */

defined( 'ABSPATH' ) || exit;

define( 'DGW_VERSION', '1.3.0' );
define( 'DGW_PLUGIN_FILE', __FILE__ );
define( 'DGW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DGW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declares compatibility with WooCommerce features before WooCommerce initializes.
 */
function dgw_declare_woocommerce_compatibility() {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', DGW_PLUGIN_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', DGW_PLUGIN_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'dgw_declare_woocommerce_compatibility' );

/**
 * Displays a dependency notice when WooCommerce is unavailable.
 */
function dgw_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Descuento Global Woo requires WooCommerce to be installed and active.', 'descuento-global-woo' ); ?></p>
	</div>
	<?php
}

/**
 * Loads the plugin after all dependencies are available.
 */
function dgw_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'dgw_woocommerce_missing_notice' );
		return;
	}

	require_once DGW_PLUGIN_PATH . 'includes/class-dgw-campaign-store.php';
	require_once DGW_PLUGIN_PATH . 'includes/class-dgw-campaign.php';
	require_once DGW_PLUGIN_PATH . 'includes/class-dgw-admin.php';
	require_once DGW_PLUGIN_PATH . 'includes/class-dgw-plugin.php';

	DGW_Plugin::instance();
}
add_action( 'plugins_loaded', 'dgw_bootstrap', 20 );

/**
 * Clears scheduled campaign events when the plugin is deactivated.
 */
function dgw_deactivate() {
	wp_unschedule_hook( 'dgw_campaign_boundary' );
	wp_clear_scheduled_hook( 'dgw_campaign_start' );
	wp_clear_scheduled_hook( 'dgw_campaign_end' );

	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients();
	}
}
register_deactivation_hook( __FILE__, 'dgw_deactivate' );
