<?php
/**
 * Campaign scheduling and price application.
 *
 * @package DescuentoGlobalWoo
 */

defined( 'ABSPATH' ) || exit;

final class DGW_Campaign {
	const OPTION_NAME = 'dgw_campaign_settings';
	const START_HOOK  = 'dgw_campaign_start';
	const END_HOOK    = 'dgw_campaign_end';

	/**
	 * Registers pricing, cache and presentation hooks.
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_product_get_price', array( $this, 'filter_product_price' ), 99, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'filter_product_price' ), 99, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( $this, 'filter_sale_price' ), 99, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'filter_sale_price' ), 99, 2 );

		add_filter( 'woocommerce_variation_prices_price', array( $this, 'filter_variation_price' ), 99, 3 );
		add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'filter_variation_price' ), 99, 3 );
		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'filter_variation_prices_hash' ), 99, 3 );

		add_filter( 'woocommerce_product_is_on_sale', array( $this, 'filter_is_on_sale' ), 99, 2 );
		add_filter( 'woocommerce_sale_flash', array( $this, 'filter_sale_flash' ), 99, 3 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'refresh_cart_prices' ), 99 );

		add_action( self::START_HOOK, array( $this, 'handle_campaign_start' ) );
		add_action( self::END_HOOK, array( $this, 'handle_campaign_end' ) );
		add_action( 'init', array( $this, 'ensure_scheduled_events' ) );
	}

	/**
	 * Returns normalized campaign settings.
	 *
	 * @return array{enabled:bool,percentage:float,start:int,end:int}
	 */
	public function get_settings() {
		$defaults = array(
			'enabled'    => false,
			'percentage' => 0.0,
			'start'      => 0,
			'end'        => 0,
		);
		$settings = wp_parse_args( get_option( self::OPTION_NAME, array() ), $defaults );

		return array(
			'enabled'    => (bool) $settings['enabled'],
			'percentage' => (float) $settings['percentage'],
			'start'      => absint( $settings['start'] ),
			'end'        => absint( $settings['end'] ),
		);
	}

	/**
	 * Returns the current campaign state.
	 *
	 * @param array|null $settings Optional settings.
	 * @param int|null   $timestamp Optional timestamp for deterministic checks.
	 * @return string disabled, scheduled, active or expired.
	 */
	public function get_status( $settings = null, $timestamp = null ) {
		$settings  = is_array( $settings ) ? $settings : $this->get_settings();
		$timestamp = null === $timestamp ? time() : absint( $timestamp );

		if ( empty( $settings['enabled'] ) ) {
			return 'disabled';
		}
		if ( $settings['start'] && $timestamp < $settings['start'] ) {
			return 'scheduled';
		}
		if ( ! $settings['end'] || $timestamp < $settings['end'] ) {
			return 'active';
		}

		return 'expired';
	}

	/**
	 * Whether the campaign is active now.
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'active' === $this->get_status();
	}

	/**
	 * Gets the effective percentage for a product.
	 *
	 * @param WC_Product $product Product being priced.
	 * @return float
	 */
	public function get_effective_percentage( $product ) {
		$settings   = $this->get_settings();
		$percentage = (float) $settings['percentage'];

		/**
		 * Filters the global discount percentage for a product.
		 *
		 * @param float      $percentage Campaign percentage.
		 * @param WC_Product $product    Product being priced.
		 * @param array      $settings   Campaign settings.
		 */
		$percentage = (float) apply_filters( 'dgw_effective_percentage', $percentage, $product, $settings );

		return max( 0.0, min( 100.0, $percentage ) );
	}

	/**
	 * Whether a product participates in the campaign.
	 *
	 * @param WC_Product $product Product being checked.
	 * @return bool
	 */
	public function is_product_eligible( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		/**
		 * Filters whether a product participates in the global campaign.
		 *
		 * @param bool       $eligible Product eligibility.
		 * @param WC_Product $product  Product being checked.
		 * @param array      $settings Campaign settings.
		 */
		return (bool) apply_filters( 'dgw_product_is_eligible', true, $product, $this->get_settings() );
	}

	/**
	 * Calculates a discounted, store-rounded price from a regular price.
	 *
	 * @param string|float $regular_price Regular price.
	 * @param WC_Product   $product       Product being priced.
	 * @return string|float
	 */
	private function calculate_price( $regular_price, $product ) {
		if ( ! $this->is_active() || ! $this->is_product_eligible( $product ) || '' === $regular_price || ! is_numeric( $regular_price ) ) {
			return $regular_price;
		}

		$percentage = $this->get_effective_percentage( $product );
		if ( $percentage <= 0 ) {
			return $regular_price;
		}

		$price = (float) $regular_price * ( 1 - ( $percentage / 100 ) );

		return wc_format_decimal( max( 0, $price ), wc_get_price_decimals() );
	}

	/**
	 * Filters a product's active price using its unfiltered regular price.
	 *
	 * @param string|float $price   Current price.
	 * @param WC_Product   $product Product.
	 * @return string|float
	 */
	public function filter_product_price( $price, $product ) {
		if ( ! $this->is_active() || ! $this->is_product_eligible( $product ) ) {
			return $price;
		}

		$regular_price = $product->get_regular_price( 'edit' );
		if ( '' === $regular_price || ! is_numeric( $regular_price ) ) {
			return $price;
		}

		return $this->calculate_price( $regular_price, $product );
	}

	/**
	 * Supplies the campaign price as the temporary sale price.
	 *
	 * @param string|float $sale_price Current sale price.
	 * @param WC_Product   $product    Product.
	 * @return string|float
	 */
	public function filter_sale_price( $sale_price, $product ) {
		if ( ! $this->is_active() || ! $this->is_product_eligible( $product ) ) {
			return $sale_price;
		}

		$regular_price = $product->get_regular_price( 'edit' );
		if ( '' === $regular_price || ! is_numeric( $regular_price ) ) {
			return $sale_price;
		}

		return $this->calculate_price( $regular_price, $product );
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
		if ( ! $this->is_active() || ! $this->is_product_eligible( $variation ) ) {
			return $price;
		}

		$regular_price = $variation->get_regular_price( 'edit' );
		if ( '' === $regular_price || ! is_numeric( $regular_price ) ) {
			return $price;
		}

		return $this->calculate_price( $regular_price, $variation );
	}

	/**
	 * Adds campaign state and percentage to WooCommerce's variation cache key.
	 *
	 * @param array      $hash        Existing hash data.
	 * @param WC_Product $product     Variable product.
	 * @param bool       $for_display Whether tax display prices are requested.
	 * @return array
	 */
	public function filter_variation_prices_hash( $hash, $product, $for_display ) {
		unset( $for_display );
		$hash['dgw_campaign'] = array(
			'status'     => $this->get_status(),
			'percentage' => $this->get_effective_percentage( $product ),
			'eligible'   => $this->is_product_eligible( $product ),
			'version'    => DGW_VERSION,
		);

		return $hash;
	}

	/**
	 * Ensures price HTML uses WooCommerce's native sale formatting.
	 *
	 * @param bool       $on_sale Existing state.
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public function filter_is_on_sale( $on_sale, $product ) {
		if ( ! $this->is_active() || ! $this->is_product_eligible( $product ) ) {
			return $on_sale;
		}

		if ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) {
			return $on_sale;
		}

		$regular = $product->get_regular_price( 'edit' );
		return '' !== $regular && (float) $this->calculate_price( $regular, $product ) < (float) $regular;
	}

	/**
	 * Replaces WooCommerce's sale flash with the global percentage badge.
	 *
	 * @param string     $html    Existing badge HTML.
	 * @param WP_Post    $post    Product post.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public function filter_sale_flash( $html, $post, $product ) {
		unset( $post );
		if ( ! $this->is_active() || ! $this->is_product_eligible( $product ) || ! $product->is_on_sale() ) {
			return $html;
		}

		$percentage = $this->get_effective_percentage( $product );
		$number      = wc_format_localized_decimal( $percentage );
		$text        = sprintf( '-%s%%', $number );

		/**
		 * Filters the text displayed in the global discount badge.
		 *
		 * @param string     $text       Badge text.
		 * @param float      $percentage Effective percentage.
		 * @param WC_Product $product    Product.
		 */
		$text = (string) apply_filters( 'dgw_sale_badge_text', $text, $percentage, $product );

		return '<span class="onsale dgw-global-discount-badge">' . esc_html( $text ) . '</span>';
	}

	/**
	 * Refreshes cart item objects from persistent price data before totals run.
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

			$product = $cart_item['data'];
			if ( $this->is_active() && $this->is_product_eligible( $product ) ) {
				$regular_price = $product->get_regular_price( 'edit' );
				if ( '' !== $regular_price && is_numeric( $regular_price ) ) {
					$product->set_price( $this->calculate_price( $regular_price, $product ) );
				}
			} else {
				$product->set_price( $product->get_price( 'edit' ) );
			}
		}
	}

	/**
	 * Replaces scheduled boundary events for the supplied settings.
	 *
	 * @param array $settings Validated settings.
	 */
	public function schedule_events( $settings ) {
		wp_clear_scheduled_hook( self::START_HOOK );
		wp_clear_scheduled_hook( self::END_HOOK );

		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		$now = time();
		if ( $settings['start'] > $now ) {
			wp_schedule_single_event( $settings['start'], self::START_HOOK );
		}
		if ( $settings['end'] > $now ) {
			wp_schedule_single_event( $settings['end'], self::END_HOOK );
		}
	}

	/**
	 * Restores future boundary events after plugin reactivation or cron loss.
	 */
	public function ensure_scheduled_events() {
		$settings = $this->get_settings();
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		$now = time();
		if ( $settings['start'] > $now && ! wp_next_scheduled( self::START_HOOK ) ) {
			wp_schedule_single_event( $settings['start'], self::START_HOOK );
		}
		if ( $settings['end'] > $now && ! wp_next_scheduled( self::END_HOOK ) ) {
			wp_schedule_single_event( $settings['end'], self::END_HOOK );
		}
	}

	/**
	 * Clears price caches at campaign boundaries.
	 */
	private function clear_price_caches() {
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}
	}

	/**
	 * Handles the campaign start boundary.
	 */
	public function handle_campaign_start() {
		$this->clear_price_caches();

		if ( $this->is_active() ) {
			/** Fires when a scheduled global campaign starts. */
			do_action( 'dgw_campaign_started', $this->get_settings() );
		}
	}

	/**
	 * Handles the campaign end boundary.
	 */
	public function handle_campaign_end() {
		$this->clear_price_caches();

		if ( 'expired' === $this->get_status() ) {
			/** Fires when a scheduled global campaign ends. */
			do_action( 'dgw_campaign_ended', $this->get_settings() );
		}
	}
}
