<?php
/**
 * Price application for the active campaigns.
 *
 * @package DescuentoGlobalWoo
 */

defined( 'ABSPATH' ) || exit;

final class DGW_Campaign {
	const STATE_OPTION  = 'dgw_applied_state';
	const ON_SALE_CACHE = 'dgw_products_onsale';
	const BOUNDARY_HOOK = 'dgw_campaign_boundary';
	const DEFAULT_BADGE = '-{percentage}%';

	/**
	 * Campaign store.
	 *
	 * @var DGW_Campaign_Store
	 */
	private $store;

	/**
	 * Active campaigns memoized for the current request.
	 *
	 * @var array|null
	 */
	private $active = null;

	/**
	 * Winning discount per product ID for the current request.
	 *
	 * @var array<int,array{percentage:float,campaign:array|null}>
	 */
	private $discounts = array();

	/**
	 * Category IDs (with ancestors) per product ID for the current request.
	 *
	 * @var array<int,int[]>
	 */
	private $category_ids = array();

	/**
	 * Constructor.
	 *
	 * @param DGW_Campaign_Store $store Campaign store.
	 */
	public function __construct( $store ) {
		$this->store = $store;
	}

	/**
	 * Registers pricing, cache and scheduling hooks.
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_product_get_price', array( $this, 'filter_product_price' ), 99, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'filter_product_price' ), 99, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( $this, 'filter_product_price' ), 99, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'filter_product_price' ), 99, 2 );

		add_filter( 'woocommerce_variation_prices_price', array( $this, 'filter_variation_price' ), 99, 3 );
		add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'filter_variation_price' ), 99, 3 );
		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'filter_variation_prices_hash' ), 99, 3 );

		add_filter( 'woocommerce_product_is_on_sale', array( $this, 'filter_is_on_sale' ), 99, 2 );
		add_filter( 'woocommerce_sale_flash', array( $this, 'filter_sale_flash' ), 99, 3 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'refresh_cart_prices' ), 99 );

		// Campaign products join WooCommerce's on-sale list (Bricks "On sale", sale blocks and shortcodes).
		add_filter( 'pre_transient_wc_products_onsale', array( $this, 'filter_on_sale_ids' ) );
		add_action( 'woocommerce_delete_product_transients', array( $this, 'flush_on_sale_cache' ) );

		add_action( self::BOUNDARY_HOOK, array( $this, 'sync_status' ) );
		add_action( 'init', array( $this, 'sync_status' ), 20 );
		add_action( 'init', array( $this, 'ensure_scheduled_events' ) );

		foreach ( array( 'add_option_', 'update_option_', 'delete_option_' ) as $prefix ) {
			add_action( $prefix . DGW_Campaign_Store::OPTION_NAME, array( $this, 'reset_memo' ) );
		}
	}

	/**
	 * Forgets memoized campaigns and discounts after campaigns change.
	 */
	public function reset_memo() {
		$this->active    = null;
		$this->discounts = array();
		$this->store->reset_memo();
	}

	/**
	 * Returns the campaigns active right now.
	 *
	 * @return array[]
	 */
	public function get_active_campaigns() {
		if ( null === $this->active ) {
			$now          = time();
			$this->active = array_values(
				array_filter(
					$this->store->get_all(),
					function ( $campaign ) use ( $now ) {
						return 'active' === DGW_Campaign_Store::get_status( $campaign, $now ) && $campaign['percentage'] > 0;
					}
				)
			);
		}

		return $this->active;
	}

	/**
	 * Returns the product category IDs used for targeting, including ancestors.
	 * Variations inherit the categories of their parent product.
	 *
	 * @param WC_Product $product Product.
	 * @return int[]
	 */
	private function get_product_category_ids( $product ) {
		$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		if ( ! $product_id ) {
			return array();
		}
		if ( isset( $this->category_ids[ $product_id ] ) ) {
			return $this->category_ids[ $product_id ];
		}

		$term_ids = array_map( 'absint', wc_get_product_term_ids( $product_id, 'product_cat' ) );
		$all_ids  = $term_ids;
		foreach ( $term_ids as $term_id ) {
			$all_ids = array_merge( $all_ids, array_map( 'absint', get_ancestors( $term_id, 'product_cat', 'taxonomy' ) ) );
		}

		$this->category_ids[ $product_id ] = array_values( array_unique( $all_ids ) );

		return $this->category_ids[ $product_id ];
	}

	/**
	 * Whether a product matches a campaign's scope and exclusions.
	 *
	 * @param WC_Product $product  Product.
	 * @param array      $campaign Campaign.
	 * @return bool
	 */
	public function matches_campaign( $product, $campaign ) {
		if ( 'all' === $campaign['scope'] && empty( $campaign['exclude_categories'] ) ) {
			return true;
		}

		$category_ids = $this->get_product_category_ids( $product );

		if ( $campaign['exclude_categories'] && array_intersect( $category_ids, $campaign['exclude_categories'] ) ) {
			return false;
		}
		if ( 'categories' === $campaign['scope'] ) {
			return (bool) array_intersect( $category_ids, $campaign['include_categories'] );
		}

		return true;
	}

	/**
	 * Returns the biggest discount among the active campaigns that match a product.
	 *
	 * @param WC_Product $product Product.
	 * @return array{percentage:float,campaign:array|null}
	 */
	public function get_product_discount( $product ) {
		$none = array(
			'percentage' => 0.0,
			'campaign'   => null,
		);
		if ( ! $product instanceof WC_Product ) {
			return $none;
		}

		$product_id = $product->get_id();
		if ( $product_id && isset( $this->discounts[ $product_id ] ) ) {
			return $this->discounts[ $product_id ];
		}

		$best = $none;
		foreach ( $this->get_active_campaigns() as $campaign ) {
			/**
			 * Filters whether a product participates in a campaign.
			 *
			 * @param bool       $eligible Result of the campaign's category rules.
			 * @param WC_Product $product  Product.
			 * @param array      $campaign Campaign.
			 */
			if ( ! apply_filters( 'dgw_product_is_eligible', $this->matches_campaign( $product, $campaign ), $product, $campaign ) ) {
				continue;
			}

			/**
			 * Filters a campaign's percentage for a product.
			 *
			 * @param float      $percentage Campaign percentage.
			 * @param WC_Product $product    Product.
			 * @param array      $campaign   Campaign.
			 */
			$percentage = max( 0.0, min( 100.0, (float) apply_filters( 'dgw_effective_percentage', $campaign['percentage'], $product, $campaign ) ) );

			if ( $percentage > $best['percentage'] ) {
				$best = array(
					'percentage' => $percentage,
					'campaign'   => $campaign,
				);
			}
		}

		if ( $product_id ) {
			$this->discounts[ $product_id ] = $best;
		}

		return $best;
	}

	/**
	 * Returns the discounted, store-rounded price for a product, or null when no campaign applies.
	 *
	 * @param WC_Product $product Product.
	 * @return string|null
	 */
	private function get_discounted_price( $product ) {
		$discount = $this->get_product_discount( $product );
		if ( $discount['percentage'] <= 0 ) {
			return null;
		}

		$regular_price = $product->get_regular_price( 'edit' );
		if ( '' === $regular_price || ! is_numeric( $regular_price ) ) {
			return null;
		}

		$price = (float) $regular_price * ( 1 - ( $discount['percentage'] / 100 ) );

		return wc_format_decimal( max( 0, $price ), wc_get_price_decimals() );
	}

	/**
	 * Filters a product's active and sale price from its unfiltered regular price.
	 *
	 * @param string|float $price   Current price.
	 * @param WC_Product   $product Product.
	 * @return string|float
	 */
	public function filter_product_price( $price, $product ) {
		$discounted = $this->get_discounted_price( $product );

		return null === $discounted ? $price : $discounted;
	}

	/**
	 * Filters prices stored in WooCommerce's variation price arrays.
	 *
	 * @param string|float $price     Current variation price.
	 * @param WC_Product   $variation Variation product.
	 * @param WC_Product   $parent    Parent variable product.
	 * @return string|float
	 */
	public function filter_variation_price( $price, $variation, $parent ) {
		unset( $parent );

		return $this->filter_product_price( $price, $variation );
	}

	/**
	 * Adds the active campaigns to WooCommerce's variation price cache key.
	 *
	 * @param array      $hash        Existing hash data.
	 * @param WC_Product $product     Variable product.
	 * @param bool       $for_display Whether tax display prices are requested.
	 * @return array
	 */
	public function filter_variation_prices_hash( $hash, $product, $for_display ) {
		unset( $for_display );
		$discount = $this->get_product_discount( $product );

		$hash['dgw_campaigns'] = array(
			'active'     => md5( wp_json_encode( $this->get_active_campaigns() ) ),
			'percentage' => $discount['percentage'],
			'version'    => DGW_VERSION,
		);

		return $hash;
	}

	/**
	 * Marks discounted products as on sale so WooCommerce shows the struck-through price.
	 *
	 * @param bool       $on_sale Existing state.
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public function filter_is_on_sale( $on_sale, $product ) {
		if ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) {
			return $on_sale;
		}

		$discounted = $this->get_discounted_price( $product );
		if ( null === $discounted ) {
			return $on_sale;
		}

		return (float) $discounted < (float) $product->get_regular_price( 'edit' );
	}

	/**
	 * Builds the badge text. Themes and page builders usually render their own
	 * badge; developers can change this text with the dgw_sale_badge_text filter.
	 *
	 * @param float      $percentage Effective percentage.
	 * @param WC_Product $product    Product.
	 * @return string
	 */
	public function get_badge_text( $percentage, $product ) {
		$text = str_replace( '{percentage}', wc_format_localized_decimal( $percentage ), self::DEFAULT_BADGE );

		/**
		 * Filters the text displayed in the campaign sale badge.
		 *
		 * @param string     $text       Badge text.
		 * @param float      $percentage Effective percentage.
		 * @param WC_Product $product    Product.
		 */
		return (string) apply_filters( 'dgw_sale_badge_text', $text, $percentage, $product );
	}

	/**
	 * Replaces WooCommerce's sale flash with the campaign percentage badge.
	 *
	 * @param string     $html    Existing badge HTML.
	 * @param WP_Post    $post    Product post.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public function filter_sale_flash( $html, $post, $product ) {
		unset( $post );
		$discount = $this->get_product_discount( $product );
		if ( $discount['percentage'] <= 0 || ! $product->is_on_sale() ) {
			return $html;
		}

		return '<span class="onsale dgw-global-discount-badge">' . esc_html( $this->get_badge_text( $discount['percentage'], $product ) ) . '</span>';
	}

	/**
	 * Sets campaign prices on cart items before totals run.
	 *
	 * @param WC_Cart $cart Cart instance.
	 */
	public function refresh_cart_prices( $cart ) {
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
				continue;
			}
			$discounted = $this->get_discounted_price( $cart_item['data'] );
			if ( null !== $discounted ) {
				$cart_item['data']->set_price( $discounted );
			}
		}
	}

	/**
	 * Adds products discounted by active campaigns to wc_get_product_ids_on_sale().
	 *
	 * WooCommerce builds that list from sale prices stored in the database, which
	 * this plugin never writes, so queries such as Bricks' "On sale" option, the
	 * On Sale Products block and [sale_products] would otherwise miss them.
	 *
	 * @param mixed $pre Short-circuit value; false lets WooCommerce compute its own list.
	 * @return mixed
	 */
	public function filter_on_sale_ids( $pre ) {
		if ( false !== $pre ) {
			return $pre;
		}

		$active = $this->get_active_campaigns();
		if ( empty( $active ) ) {
			return $pre;
		}

		$hash   = md5( wp_json_encode( $active ) . DGW_VERSION );
		$cached = get_transient( self::ON_SALE_CACHE );
		if ( is_array( $cached ) && isset( $cached['hash'], $cached['ids'] ) && $hash === $cached['hash'] ) {
			return $cached['ids'];
		}

		// WooCommerce's own list, read from the data store to avoid re-entering this filter.
		$ids = array();
		foreach ( WC_Data_Store::load( 'product' )->get_on_sale_products() as $row ) {
			$ids[] = (int) $row->id;
			$ids[] = (int) $row->parent_id;
		}

		$ids = array_merge( $ids, $this->get_campaign_product_ids( $active ) );
		$ids = array_values( array_unique( array_filter( $ids ) ) );

		set_transient(
			self::ON_SALE_CACHE,
			array(
				'hash' => $hash,
				'ids'  => $ids,
			),
			DAY_IN_SECONDS
		);

		return $ids;
	}

	/**
	 * Returns IDs of published products (and their variations) that an active campaign puts on sale.
	 *
	 * @param array[] $active Active campaigns.
	 * @return int[]
	 */
	private function get_campaign_product_ids( $active ) {
		$candidates = array();

		foreach ( $active as $campaign ) {
			$args = array(
				'post_type'        => 'product',
				'post_status'      => 'publish',
				'fields'           => 'ids',
				'posts_per_page'   => -1,
				'no_found_rows'    => true,
				'suppress_filters' => true,
			);

			$tax_query = array();
			if ( 'categories' === $campaign['scope'] ) {
				$tax_query[] = array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $campaign['include_categories'],
					'include_children' => true,
				);
			}
			if ( $campaign['exclude_categories'] ) {
				$tax_query[] = array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $campaign['exclude_categories'],
					'operator'         => 'NOT IN',
					'include_children' => true,
				);
			}
			if ( $tax_query ) {
				$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Cached for a day.
			}

			$candidates = array_merge( $candidates, get_posts( $args ) );
		}

		// Confirm each candidate really shows a discount (filters, empty prices) and add its variations.
		$ids = array();
		foreach ( array_unique( $candidates ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->is_on_sale() ) {
				continue;
			}
			$ids[] = (int) $product_id;
			foreach ( $product->get_children() as $child_id ) {
				$ids[] = (int) $child_id;
			}
		}

		return $ids;
	}

	/**
	 * Forgets the cached on-sale list (products or campaigns changed).
	 */
	public function flush_on_sale_cache() {
		delete_transient( self::ON_SALE_CACHE );
	}

	/**
	 * Returns future start and end timestamps of switched-on campaigns.
	 *
	 * @return int[]
	 */
	private function get_future_boundaries() {
		$now        = time();
		$boundaries = array();
		foreach ( $this->store->get_all() as $campaign ) {
			if ( empty( $campaign['enabled'] ) ) {
				continue;
			}
			foreach ( array( $campaign['start'], $campaign['end'] ) as $timestamp ) {
				if ( $timestamp > $now ) {
					$boundaries[] = $timestamp;
				}
			}
		}

		return array_values( array_unique( $boundaries ) );
	}

	/**
	 * Replaces all scheduled boundary events.
	 */
	public function schedule_events() {
		wp_unschedule_hook( self::BOUNDARY_HOOK );
		foreach ( $this->get_future_boundaries() as $timestamp ) {
			wp_schedule_single_event( $timestamp, self::BOUNDARY_HOOK, array( $timestamp ) );
		}
	}

	/**
	 * Restores future boundary events after plugin reactivation or cron loss.
	 */
	public function ensure_scheduled_events() {
		foreach ( $this->get_future_boundaries() as $timestamp ) {
			if ( ! wp_next_scheduled( self::BOUNDARY_HOOK, array( $timestamp ) ) ) {
				wp_schedule_single_event( $timestamp, self::BOUNDARY_HOOK, array( $timestamp ) );
			}
		}
	}

	/**
	 * Purges the full-page cache so visitors see current prices.
	 *
	 * Supports CloudPanel's Varnish (CLP Varnish Cache). The purge request is sent
	 * here instead of through the CLP manager, whose purge() calls exit() on failure.
	 *
	 * @return bool Whether a page cache was purged.
	 */
	public function purge_page_cache() {
		$purged = false;

		// CLP Varnish Cache only loads its classes in wp-admin; cron runs on the front end.
		$clp_manager_file = WP_PLUGIN_DIR . '/clp-varnish-cache/class.varnish-cache-manager.php';
		if ( ! class_exists( 'ClpVarnishCacheManager' ) && defined( 'CLP_VARNISH_VERSION' ) && is_readable( $clp_manager_file ) ) {
			require_once $clp_manager_file;
		}

		if ( class_exists( 'ClpVarnishCacheManager' ) ) {
			$manager = new ClpVarnishCacheManager();
			$server  = $manager->is_enabled() ? (string) $manager->get_server() : '';
			$host    = (string) wp_parse_url( home_url(), PHP_URL_HOST );

			if ( '' !== $server && '' !== $host ) {
				$response = wp_remote_request(
					'http://' . $server,
					array(
						'method'    => 'PURGE',
						'headers'   => array( 'Host' => $host ),
						'timeout'   => 5,
						'sslverify' => false,
					)
				);
				$purged   = ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response );
			}
		}

		/**
		 * Fires after campaign prices change so other page caches can be purged.
		 *
		 * @param bool $purged Whether the Varnish cache was purged.
		 */
		do_action( 'dgw_purge_page_cache', $purged );

		return $purged;
	}

	/**
	 * Clears WooCommerce price transients and the full-page cache.
	 */
	public function clear_caches() {
		$this->flush_on_sale_cache();
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}
		$this->purge_page_cache();
	}

	/**
	 * Returns the current status of every campaign, keyed by ID.
	 *
	 * @return array<string,string>
	 */
	private function get_status_map() {
		$now = time();
		$map = array();
		foreach ( $this->store->get_all() as $id => $campaign ) {
			$map[ $id ] = DGW_Campaign_Store::get_status( $campaign, $now );
		}
		ksort( $map );

		return $map;
	}

	/**
	 * Detects campaign starts and ends on any request that reaches PHP.
	 *
	 * Prices are always computed live, so they are never wrong. Caches, however,
	 * must be cleared when a status changes. WP-Cron alone may run late (or never,
	 * on sites without traffic that reaches PHP), so the last applied statuses are
	 * stored and compared on every request: admin, cart, checkout and AJAX
	 * requests bypass page caches and trigger the change. Cron events remain as a
	 * punctual nudge when available.
	 */
	public function sync_status() {
		$current = $this->get_status_map();
		$applied = get_option( self::STATE_OPTION, null );

		if ( $current === $applied ) {
			return;
		}

		update_option( self::STATE_OPTION, $current, true );
		if ( null === $applied ) {
			return; // First run: nothing changed for visitors yet.
		}

		$this->clear_caches();
		$applied = (array) $applied;

		foreach ( $current as $id => $status ) {
			$previous = isset( $applied[ $id ] ) ? $applied[ $id ] : '';
			if ( $previous === $status ) {
				continue;
			}
			$campaign = $this->store->get( $id );
			if ( 'active' === $status ) {
				/** Fires when a campaign becomes active. */
				do_action( 'dgw_campaign_started', $campaign );
			} elseif ( 'expired' === $status && 'active' === $previous ) {
				/** Fires when an active campaign reaches its end date. */
				do_action( 'dgw_campaign_ended', $campaign );
			}
		}
	}

	/**
	 * Clears caches and records the current statuses after an admin change.
	 */
	public function after_campaigns_changed() {
		$this->reset_memo();
		$this->schedule_events();
		$this->clear_caches();
		update_option( self::STATE_OPTION, $this->get_status_map(), true );
	}
}
