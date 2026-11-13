<?php
/**
 * Campaign persistence and normalization.
 *
 * @package DescuentoGlobalWoo
 */

defined( 'ABSPATH' ) || exit;

final class DGW_Campaign_Store {
	const OPTION_NAME   = 'dgw_campaigns';
	const LEGACY_OPTION = 'dgw_campaign_settings';

	/**
	 * Campaigns memoized for the current request.
	 *
	 * @var array<string,array>|null
	 */
	private $campaigns = null;

	/**
	 * Registers hooks that keep the memo in sync with the option.
	 */
	public function register_hooks() {
		add_action( 'add_option_' . self::OPTION_NAME, array( $this, 'reset_memo' ) );
		add_action( 'update_option_' . self::OPTION_NAME, array( $this, 'reset_memo' ) );
		add_action( 'delete_option_' . self::OPTION_NAME, array( $this, 'reset_memo' ) );
	}

	/**
	 * Forgets memoized campaigns.
	 */
	public function reset_memo() {
		$this->campaigns = null;
	}

	/**
	 * Returns the default shape of a campaign.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'id'                 => '',
			'name'               => '',
			'enabled'            => false,
			'percentage'         => 0.0,
			'start'              => 0,
			'end'                => 0,
			'scope'              => 'all',
			'include_categories' => array(),
			'exclude_categories' => array(),
			'created'            => 0,
			'modified'           => 0,
		);
	}

	/**
	 * Normalizes a raw campaign array.
	 *
	 * @param array $raw Raw campaign.
	 * @return array
	 */
	public static function normalize( $raw ) {
		$campaign = wp_parse_args( (array) $raw, self::defaults() );

		return array(
			'id'                 => sanitize_key( $campaign['id'] ),
			'name'               => sanitize_text_field( (string) $campaign['name'] ),
			'enabled'            => (bool) $campaign['enabled'],
			'percentage'         => max( 0.0, min( 100.0, (float) $campaign['percentage'] ) ),
			'start'              => absint( $campaign['start'] ),
			'end'                => absint( $campaign['end'] ),
			'scope'              => 'categories' === $campaign['scope'] ? 'categories' : 'all',
			'include_categories' => self::sanitize_ids( $campaign['include_categories'] ),
			'exclude_categories' => self::sanitize_ids( $campaign['exclude_categories'] ),
			'created'            => absint( $campaign['created'] ),
			'modified'           => absint( $campaign['modified'] ),
		);
	}

	/**
	 * Normalizes a list of term IDs.
	 *
	 * @param mixed $ids Raw IDs.
	 * @return int[]
	 */
	public static function sanitize_ids( $ids ) {
		return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	}

	/**
	 * Returns a campaign's state at a given time.
	 *
	 * @param array    $campaign  Normalized campaign.
	 * @param int|null $timestamp Optional timestamp.
	 * @return string disabled, scheduled, active or expired.
	 */
	public static function get_status( $campaign, $timestamp = null ) {
		$timestamp = null === $timestamp ? time() : absint( $timestamp );

		if ( empty( $campaign['enabled'] ) ) {
			return 'disabled';
		}
		if ( $campaign['start'] && $timestamp < $campaign['start'] ) {
			return 'scheduled';
		}
		if ( ! $campaign['end'] || $timestamp < $campaign['end'] ) {
			return 'active';
		}

		return 'expired';
	}

	/**
	 * Returns every campaign keyed by ID, migrating the single-campaign option once.
	 *
	 * @return array<string,array>
	 */
	public function get_all() {
		if ( null !== $this->campaigns ) {
			return $this->campaigns;
		}

		$stored = get_option( self::OPTION_NAME, null );
		if ( null === $stored ) {
			$stored = $this->migrate_legacy();
		}

		$campaigns = array();
		foreach ( (array) $stored as $raw ) {
			$campaign = self::normalize( $raw );
			if ( '' !== $campaign['id'] ) {
				$campaigns[ $campaign['id'] ] = $campaign;
			}
		}

		$this->campaigns = $campaigns;

		return $this->campaigns;
	}

	/**
	 * Converts the 1.2 single-campaign option into the first list entry.
	 *
	 * @return array
	 */
	private function migrate_legacy() {
		$legacy = get_option( self::LEGACY_OPTION, null );
		if ( ! is_array( $legacy ) ) {
			return array();
		}

		$campaign         = self::normalize( $legacy );
		$campaign['id']   = self::new_id();
		$campaign['name'] = __( 'Global discount', 'descuento-global-woo' );
		$now              = time();
		$campaign['created']  = $now;
		$campaign['modified'] = $now;

		$campaigns = array( $campaign['id'] => $campaign );
		update_option( self::OPTION_NAME, $campaigns, true );
		delete_option( self::LEGACY_OPTION );

		return $campaigns;
	}

	/**
	 * Returns one campaign or null.
	 *
	 * @param string $id Campaign ID.
	 * @return array|null
	 */
	public function get( $id ) {
		$campaigns = $this->get_all();
		$id        = sanitize_key( $id );

		return isset( $campaigns[ $id ] ) ? $campaigns[ $id ] : null;
	}

	/**
	 * Generates a new campaign ID.
	 *
	 * @return string
	 */
	public static function new_id() {
		return 'c' . strtolower( wp_generate_password( 10, false, false ) );
	}

	/**
	 * Persists the whole list.
	 *
	 * @param array<string,array> $campaigns Campaigns keyed by ID.
	 */
	private function persist( $campaigns ) {
		update_option( self::OPTION_NAME, $campaigns, true );
		$this->campaigns = $campaigns;
	}

	/**
	 * Creates or updates a campaign and returns it.
	 *
	 * @param array $campaign Campaign data; an empty ID creates a new campaign.
	 * @return array
	 */
	public function save( $campaign ) {
		$campaigns = $this->get_all();
		$campaign  = self::normalize( $campaign );
		$now       = time();

		if ( '' === $campaign['id'] || ! isset( $campaigns[ $campaign['id'] ] ) ) {
			$campaign['id']      = self::new_id();
			$campaign['created'] = $now;
		} else {
			$campaign['created'] = $campaigns[ $campaign['id'] ]['created'];
		}
		$campaign['modified'] = $now;

		$campaigns[ $campaign['id'] ] = $campaign;
		$this->persist( $campaigns );

		return $campaign;
	}

	/**
	 * Turns a campaign on or off.
	 *
	 * @param string $id      Campaign ID.
	 * @param bool   $enabled New state.
	 * @return bool Whether the campaign exists.
	 */
	public function set_enabled( $id, $enabled ) {
		$campaign = $this->get( $id );
		if ( ! $campaign ) {
			return false;
		}
		$campaign['enabled'] = (bool) $enabled;
		$this->save( $campaign );

		return true;
	}

	/**
	 * Copies a campaign as a new, switched-off campaign.
	 *
	 * @param string $id Campaign ID.
	 * @return array|null The copy.
	 */
	public function duplicate( $id ) {
		$campaign = $this->get( $id );
		if ( ! $campaign ) {
			return null;
		}

		$campaign['id']      = '';
		$campaign['enabled'] = false;
		/* translators: %s: original campaign name. */
		$campaign['name'] = sprintf( __( '%s (copy)', 'descuento-global-woo' ), $campaign['name'] );

		return $this->save( $campaign );
	}

	/**
	 * Deletes a campaign.
	 *
	 * @param string $id Campaign ID.
	 * @return bool Whether it existed.
	 */
	public function delete( $id ) {
		$campaigns = $this->get_all();
		$id        = sanitize_key( $id );
		if ( ! isset( $campaigns[ $id ] ) ) {
			return false;
		}
		unset( $campaigns[ $id ] );
		$this->persist( $campaigns );

		return true;
	}
}
