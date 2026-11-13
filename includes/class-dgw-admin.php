<?php
/**
 * WooCommerce administration screens: campaign list and campaign editor.
 *
 * @package DescuentoGlobalWoo
 */

defined( 'ABSPATH' ) || exit;

final class DGW_Admin {
	const PAGE_SLUG = 'descuento-global-woo';

	/**
	 * Campaign store.
	 *
	 * @var DGW_Campaign_Store
	 */
	private $store;

	/**
	 * Pricing engine.
	 *
	 * @var DGW_Campaign
	 */
	private $engine;

	/**
	 * Message key of the current request, shown under the screen header.
	 *
	 * @var string
	 */
	private $message = '';

	/**
	 * Constructor.
	 *
	 * @param DGW_Campaign_Store $store  Campaign store.
	 * @param DGW_Campaign       $engine Pricing engine.
	 */
	public function __construct( $store, $engine ) {
		$this->store  = $store;
		$this->engine = $engine;
	}

	/**
	 * Registers admin hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 60 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );

		foreach ( array( 'save', 'toggle', 'duplicate', 'delete' ) as $action ) {
			add_action( 'admin_post_dgw_' . $action . '_campaign', array( $this, 'handle_' . $action ) );
		}
	}

	/**
	 * Whether the current screen is this plugin's page.
	 *
	 * @return bool
	 */
	private function is_plugin_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && 'woocommerce_page_' . self::PAGE_SLUG === $screen->id;
	}

	/**
	 * Adds a body class used to tint the admin canvas on this page only.
	 *
	 * @param string $classes Body classes.
	 * @return string
	 */
	public function add_body_class( $classes ) {
		return $this->is_plugin_screen() ? $classes . ' dgw-screen' : $classes;
	}

	/**
	 * Loads assets only on this plugin's page.
	 *
	 * @param string $hook_suffix Current admin screen suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'woocommerce_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		// File modification time as version, so every change busts browser and CDN caches.
		$asset_version = function ( $file ) {
			$mtime = filemtime( DGW_PLUGIN_PATH . $file );
			return DGW_VERSION . '.' . ( $mtime ? $mtime : '0' );
		};

		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'dgw-admin', DGW_PLUGIN_URL . 'assets/admin.css', array(), $asset_version( 'assets/admin.css' ) );
		wp_enqueue_script( 'dgw-admin', DGW_PLUGIN_URL . 'assets/admin.js', array( 'jquery', 'wc-enhanced-select' ), $asset_version( 'assets/admin.js' ), true );
	}

	/**
	 * Adds the page under WooCommerce.
	 */
	public function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			esc_html__( 'Discount campaigns', 'descuento-global-woo' ),
			esc_html__( 'Discount campaigns', 'descuento-global-woo' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Builds a URL to this page.
	 *
	 * @param array $args Query arguments.
	 * @return string
	 */
	private function page_url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE_SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Returns translated status labels.
	 *
	 * @return array<string,string>
	 */
	private function get_status_labels() {
		return array(
			'active'    => __( 'Active', 'descuento-global-woo' ),
			'scheduled' => __( 'Scheduled', 'descuento-global-woo' ),
			'disabled'  => __( 'Off', 'descuento-global-woo' ),
			'expired'   => __( 'Ended', 'descuento-global-woo' ),
		);
	}

	/**
	 * Returns feedback copy for a redirect message.
	 *
	 * @param string $message Message key.
	 * @return array{type:string,title:string,text:string}|null
	 */
	private function get_feedback( $message ) {
		$messages = array(
			'created'            => array( 'success', __( 'Campaign created', 'descuento-global-woo' ), __( 'It applies according to its switch and dates.', 'descuento-global-woo' ) ),
			'saved'              => array( 'success', __( 'Campaign saved', 'descuento-global-woo' ), __( 'Prices in the store are already up to date.', 'descuento-global-woo' ) ),
			'enabled'            => array( 'success', __( 'Campaign turned on', 'descuento-global-woo' ), __( 'It applies within its dates.', 'descuento-global-woo' ) ),
			'disabled'           => array( 'success', __( 'Campaign turned off', 'descuento-global-woo' ), __( 'Its products are back to their usual prices.', 'descuento-global-woo' ) ),
			'duplicated'         => array( 'success', __( 'Campaign duplicated', 'descuento-global-woo' ), __( 'The copy is off. Review it and turn it on when it is ready.', 'descuento-global-woo' ) ),
			'deleted'            => array( 'success', __( 'Campaign deleted', 'descuento-global-woo' ), __( 'Product prices are not affected.', 'descuento-global-woo' ) ),
			'not_found'          => array( 'error', __( 'Campaign not found', 'descuento-global-woo' ), __( 'It may have been deleted. Choose another campaign from the list.', 'descuento-global-woo' ) ),
			'missing_name'       => array( 'error', __( 'Name the campaign', 'descuento-global-woo' ), __( 'A name helps you find it in the list, for example "Black Friday 2026".', 'descuento-global-woo' ) ),
			'invalid_percentage' => array( 'error', __( 'Invalid percentage', 'descuento-global-woo' ), __( 'Enter a percentage between 0.01 and 100.', 'descuento-global-woo' ) ),
			'invalid_dates'      => array( 'error', __( 'We could not read the dates', 'descuento-global-woo' ), __( 'Check the dates and times you entered and try again.', 'descuento-global-woo' ) ),
			'invalid_range'      => array( 'error', __( 'The date range is not valid', 'descuento-global-woo' ), __( 'The end date must be later than the start date.', 'descuento-global-woo' ) ),
			'missing_categories' => array( 'error', __( 'Choose at least one category', 'descuento-global-woo' ), __( 'To limit the campaign to categories, select the categories it applies to.', 'descuento-global-woo' ) ),
		);

		if ( ! isset( $messages[ $message ] ) ) {
			return null;
		}

		return array(
			'type'  => $messages[ $message ][0],
			'title' => $messages[ $message ][1],
			'text'  => $messages[ $message ][2],
		);
	}

	/**
	 * Returns product categories as an ID => label map, indented by depth.
	 *
	 * @return array<int,string>
	 */
	private function get_category_options() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$children = array();
		foreach ( $terms as $term ) {
			$children[ (int) $term->parent ][] = $term;
		}

		$options = array();
		$walk    = function ( $parent_id, $depth ) use ( &$walk, &$options, $children ) {
			if ( empty( $children[ $parent_id ] ) ) {
				return;
			}
			foreach ( $children[ $parent_id ] as $term ) {
				$options[ (int) $term->term_id ] = str_repeat( '— ', $depth ) . $term->name;
				$walk( (int) $term->term_id, $depth + 1 );
			}
		};
		$walk( 0, 0 );

		return $options;
	}

	/**
	 * Formats a timestamp in the site's timezone.
	 *
	 * @param int $timestamp UTC timestamp.
	 * @return string
	 */
	private function format_date( $timestamp ) {
		/* translators: compact date format for the campaign list, see https://www.php.net/manual/datetime.format.php */
		$date_format = _x( 'M j, Y', 'campaign list date', 'descuento-global-woo' );

		return wp_date( $date_format . ' · ' . get_option( 'time_format' ), $timestamp );
	}

	/**
	 * Formats a percentage without trailing zeros.
	 *
	 * @param float $percentage Percentage.
	 * @return string
	 */
	private function format_percentage( $percentage ) {
		return wc_format_localized_decimal( wc_format_decimal( $percentage, 2, true ) );
	}

	/**
	 * Describes which products a campaign targets.
	 *
	 * @param array $campaign   Campaign.
	 * @param array $categories Category options.
	 * @return string
	 */
	private function describe_scope( $campaign, $categories ) {
		$names = function ( $ids ) use ( $categories ) {
			$labels = array();
			foreach ( $ids as $id ) {
				if ( isset( $categories[ $id ] ) ) {
					$labels[] = ltrim( $categories[ $id ], '— ' );
				}
			}
			return implode( ', ', $labels );
		};

		$text = 'categories' === $campaign['scope'] && $campaign['include_categories']
			? $names( $campaign['include_categories'] )
			: __( 'Whole store', 'descuento-global-woo' );

		if ( $campaign['exclude_categories'] ) {
			/* translators: %s: comma-separated category names. */
			$text .= ' · ' . sprintf( __( 'except %s', 'descuento-global-woo' ), $names( $campaign['exclude_categories'] ) );
		}

		return $text;
	}

	/**
	 * Sorts campaigns: active, scheduled, off, ended.
	 *
	 * @param array[] $campaigns Campaigns.
	 * @return array[]
	 */
	private function sort_campaigns( $campaigns ) {
		$rank = array(
			'active'    => 0,
			'scheduled' => 1,
			'disabled'  => 2,
			'expired'   => 3,
		);
		$now  = time();

		usort(
			$campaigns,
			function ( $a, $b ) use ( $rank, $now ) {
				$status_a = DGW_Campaign_Store::get_status( $a, $now );
				$status_b = DGW_Campaign_Store::get_status( $b, $now );
				if ( $status_a !== $status_b ) {
					return $rank[ $status_a ] - $rank[ $status_b ];
				}
				if ( 'active' === $status_a ) {
					return ( $a['end'] ? $a['end'] : PHP_INT_MAX ) <=> ( $b['end'] ? $b['end'] : PHP_INT_MAX );
				}
				if ( 'scheduled' === $status_a ) {
					return $a['start'] <=> $b['start'];
				}
				return $b['modified'] <=> $a['modified'];
			}
		);

		return $campaigns;
	}

	/**
	 * Renders the page: list or editor.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage discount campaigns.', 'descuento-global-woo' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only view routing.
		$view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		$id      = isset( $_GET['campaign'] ) ? sanitize_key( wp_unslash( $_GET['campaign'] ) ) : '';
		$message = isset( $_GET['dgw_message'] ) ? sanitize_key( wp_unslash( $_GET['dgw_message'] ) ) : '';
		// phpcs:enable

		echo '<div class="wrap dgw-admin">';
		echo '<hr class="wp-header-end">'; // WordPress core notices land here, above the plugin header.
		$this->message = $message;

		if ( 'edit' === $view ) {
			$campaign = '' === $id ? DGW_Campaign_Store::defaults() : $this->store->get( $id );
			if ( null === $campaign ) {
				$this->message = 'not_found';
				$this->render_list();
			} else {
				$this->render_editor( $this->restore_form_input( $campaign ) );
			}
		} else {
			$this->render_list();
		}

		$this->render_signature();
		echo '</div>';
	}

	/**
	 * Renders a feedback banner.
	 *
	 * @param array|null $feedback Feedback data.
	 */
	private function render_feedback( $feedback ) {
		if ( ! $feedback ) {
			return;
		}
		?>
		<div class="dgw-feedback dgw-feedback--<?php echo esc_attr( $feedback['type'] ); ?>" role="<?php echo 'error' === $feedback['type'] ? 'alert' : 'status'; ?>">
			<span class="dashicons <?php echo 'error' === $feedback['type'] ? 'dashicons-warning' : 'dashicons-yes-alt'; ?>" aria-hidden="true"></span>
			<div class="dgw-feedback__copy">
				<strong><?php echo esc_html( $feedback['title'] ); ?></strong>
				<p><?php echo esc_html( $feedback['text'] ); ?></p>
			</div>
			<button class="dgw-feedback__dismiss" type="button" aria-label="<?php esc_attr_e( 'Dismiss message', 'descuento-global-woo' ); ?>">
				<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Renders a small POST form that runs one campaign action.
	 *
	 * @param string $action  Action name (toggle, duplicate, delete).
	 * @param string $id      Campaign ID.
	 * @param string $content Button inner HTML (already escaped).
	 * @param array  $attrs   Button attributes.
	 * @param array  $fields  Extra hidden fields.
	 */
	private function render_action_form( $action, $id, $content, $attrs = array(), $fields = array() ) {
		?>
		<form class="dgw-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( 'dgw_' . $action . '_campaign' ); ?>">
			<input type="hidden" name="campaign" value="<?php echo esc_attr( $id ); ?>">
			<?php foreach ( $fields as $name => $value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php endforeach; ?>
			<?php wp_nonce_field( 'dgw_' . $action . '_' . $id ); ?>
			<button type="submit"
			<?php
			foreach ( $attrs as $name => $value ) {
				echo ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}
			?>
			><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by callers. ?></button>
		</form>
		<?php
	}

	/**
	 * Renders the schedule cell: dates, time bar and relative time.
	 *
	 * @param array  $campaign Campaign.
	 * @param string $status   Campaign status.
	 */
	private function render_schedule( $campaign, $status ) {
		$now   = time();
		$start = $campaign['start'];
		$end   = $campaign['end'];

		if ( ! $start && ! $end ) {
			echo '<span class="dgw-schedule__dates">' . esc_html__( 'No dates: runs while it is on', 'descuento-global-woo' ) . '</span>';
			return;
		}

		$from = $start ? $this->format_date( $start ) : __( 'When turned on', 'descuento-global-woo' );
		$to   = $end ? $this->format_date( $end ) : __( 'No end date', 'descuento-global-woo' );
		echo '<span class="dgw-schedule__dates"><span>' . esc_html( $from ) . '</span><span class="dgw-schedule__arrow" aria-hidden="true">→</span><span>' . esc_html( $to ) . '</span></span>';

		$relative = '';
		if ( 'scheduled' === $status ) {
			/* translators: %s: human time difference, e.g. "3 days". */
			$relative = sprintf( __( 'Starts in %s', 'descuento-global-woo' ), human_time_diff( $now, $start ) );
		} elseif ( 'active' === $status && $end ) {
			/* translators: %s: human time difference, e.g. "3 days". */
			$relative = sprintf( __( 'Ends in %s', 'descuento-global-woo' ), human_time_diff( $now, $end ) );
		} elseif ( 'expired' === $status ) {
			/* translators: %s: human time difference, e.g. "3 days". */
			$relative = sprintf( __( 'Ended %s ago', 'descuento-global-woo' ), human_time_diff( $end, $now ) );
		}

		if ( $start && $end && in_array( $status, array( 'active', 'expired' ), true ) ) {
			$progress = $now <= $start ? 0 : ( $now >= $end ? 100 : (int) round( ( $now - $start ) / ( $end - $start ) * 100 ) );
			printf(
				'<span class="dgw-timeline" style="--dgw-progress:%1$d%%" role="img" aria-label="%2$s"><span class="dgw-timeline__fill"></span></span>',
				(int) $progress,
				/* translators: %d: percentage of the campaign period that has elapsed. */
				esc_attr( sprintf( __( '%d%% of the campaign period has elapsed', 'descuento-global-woo' ), $progress ) )
			);
		}

		if ( $relative ) {
			echo '<span class="dgw-schedule__relative">' . esc_html( $relative ) . '</span>';
		}
	}

	/**
	 * Renders the campaign list.
	 */
	private function render_list() {
		$campaigns  = $this->sort_campaigns( array_values( $this->store->get_all() ) );
		$categories = $this->get_category_options();
		$labels     = $this->get_status_labels();
		$new_url    = $this->page_url( array( 'view' => 'edit' ) );
		?>
		<header class="dgw-header">
			<div class="dgw-header__copy">
				<h1><?php esc_html_e( 'Discount campaigns', 'descuento-global-woo' ); ?></h1>
				<p><?php esc_html_e( 'Schedule percentage discounts for the whole store or selected categories. Saved product prices never change.', 'descuento-global-woo' ); ?></p>
			</div>
			<a class="dgw-button dgw-button--light" href="<?php echo esc_url( $new_url ); ?>">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
				<?php esc_html_e( 'New campaign', 'descuento-global-woo' ); ?>
			</a>
		</header>

		<?php $this->render_feedback( $this->get_feedback( $this->message ) ); ?>

		<?php if ( empty( $campaigns ) ) : ?>
			<section class="dgw-panel dgw-panel--coupon dgw-empty">
				<div class="dgw-panel__stub" aria-hidden="true">
					<span class="dgw-percent dgw-percent--blank">—<span class="dgw-percent__sign">%</span></span>
				</div>
				<div class="dgw-panel__body">
					<h2><?php esc_html_e( 'No campaigns yet', 'descuento-global-woo' ); ?></h2>
					<p><?php esc_html_e( 'A campaign sets a discount, the products it covers and when it runs. You can prepare it now and turn it on later.', 'descuento-global-woo' ); ?></p>
					<div><a class="dgw-button" href="<?php echo esc_url( $new_url ); ?>"><?php esc_html_e( 'Create your first campaign', 'descuento-global-woo' ); ?></a></div>
				</div>
			</section>
			<?php
			return;
		endif;
		?>

		<p class="dgw-rule">
			<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
			<?php esc_html_e( 'When several active campaigns include the same product, the biggest discount applies. While a campaign runs, it replaces the product\'s own sale price.', 'descuento-global-woo' ); ?>
		</p>

		<table class="dgw-coupons">
			<caption class="screen-reader-text"><?php esc_html_e( 'Discount campaigns', 'descuento-global-woo' ); ?></caption>
			<thead>
				<tr>
					<th scope="col" class="dgw-col-stub"><?php esc_html_e( 'Discount', 'descuento-global-woo' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Campaign', 'descuento-global-woo' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Schedule', 'descuento-global-woo' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'descuento-global-woo' ); ?></th>
					<th scope="col" class="dgw-col-switch"><?php esc_html_e( 'On', 'descuento-global-woo' ); ?></th>
					<th scope="col" class="dgw-col-actions"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'descuento-global-woo' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $campaigns as $campaign ) :
					$status   = DGW_Campaign_Store::get_status( $campaign );
					$edit_url = $this->page_url(
						array(
							'view'     => 'edit',
							'campaign' => $campaign['id'],
						)
					);
					$name     = '' !== $campaign['name'] ? $campaign['name'] : __( 'Untitled campaign', 'descuento-global-woo' );
					?>
					<tr class="dgw-coupon dgw-coupon--<?php echo esc_attr( $status ); ?>">
						<td class="dgw-coupon__stub">
							<span class="dgw-percent"><?php echo esc_html( $this->format_percentage( $campaign['percentage'] ) ); ?><span class="dgw-percent__sign">%</span></span>
						</td>
						<td class="dgw-coupon__name">
							<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $name ); ?></a>
							<span class="dgw-coupon__scope"><?php echo esc_html( $this->describe_scope( $campaign, $categories ) ); ?></span>
						</td>
						<td class="dgw-schedule"><?php $this->render_schedule( $campaign, $status ); ?></td>
						<td class="dgw-coupon__status"><span class="dgw-stamp dgw-stamp--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $labels[ $status ] ); ?></span></td>
						<td class="dgw-col-switch">
							<?php
							$this->render_action_form(
								'toggle',
								$campaign['id'],
								'<span class="dgw-switch__track" aria-hidden="true"><span></span></span><span class="dgw-switch__label" aria-hidden="true">' . esc_html( $this->get_switch_label( $campaign, $status ) ) . '</span>',
								array(
									'class'        => 'dgw-switch',
									'role'         => 'switch',
									'aria-checked' => $campaign['enabled'] ? 'true' : 'false',
									/* translators: %s: campaign name. */
									'aria-label'   => sprintf( __( 'Campaign %s on', 'descuento-global-woo' ), $name ),
								),
								array( 'enabled' => $campaign['enabled'] ? '0' : '1' )
							);
							?>
						</td>
						<td class="dgw-col-actions">
							<div class="dgw-row-actions">
								<a class="dgw-icon-button" href="<?php echo esc_url( $edit_url ); ?>" title="<?php esc_attr_e( 'Edit', 'descuento-global-woo' ); ?>">
									<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									<span class="screen-reader-text">
									<?php
									/* translators: %s: campaign name. */
									echo esc_html( sprintf( __( 'Edit %s', 'descuento-global-woo' ), $name ) );
									?>
									</span>
								</a>
								<?php
								$this->render_action_form(
									'duplicate',
									$campaign['id'],
									'<span class="dashicons dashicons-admin-page" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html(
										/* translators: %s: campaign name. */
										sprintf( __( 'Duplicate %s', 'descuento-global-woo' ), $name )
									) . '</span>',
									array(
										'class' => 'dgw-icon-button',
										'title' => __( 'Duplicate', 'descuento-global-woo' ),
									)
								);
								$this->render_action_form(
									'delete',
									$campaign['id'],
									'<span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html(
										/* translators: %s: campaign name. */
										sprintf( __( 'Delete %s', 'descuento-global-woo' ), $name )
									) . '</span>',
									array(
										'class'            => 'dgw-icon-button dgw-icon-button--danger',
										'title'            => __( 'Delete', 'descuento-global-woo' ),
										/* translators: %s: campaign name. */
										'data-dgw-confirm' => sprintf( __( 'Delete the campaign "%s"? This cannot be undone.', 'descuento-global-woo' ), $name ),
									)
								);
								?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Renders a category multi-select.
	 *
	 * @param string $id          Element ID.
	 * @param string $name        Field name.
	 * @param int[]  $selected    Selected term IDs.
	 * @param array  $options     Category options.
	 * @param string $describedby ID of the help text.
	 * @param bool   $is_invalid  Whether the field failed validation.
	 */
	private function render_category_select( $id, $name, $selected, $options, $describedby, $is_invalid = false ) {
		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>[]" class="wc-enhanced-select dgw-category-select" multiple="multiple" aria-describedby="<?php echo esc_attr( $describedby ); ?>"<?php echo $is_invalid ? ' aria-invalid="true"' : ''; ?> data-placeholder="<?php esc_attr_e( 'Search categories…', 'descuento-global-woo' ); ?>">
			<?php foreach ( $options as $term_id => $label ) : ?>
				<option value="<?php echo esc_attr( $term_id ); ?>" <?php selected( in_array( $term_id, $selected, true ) ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Renders a campaign datetime in the WordPress timezone.
	 *
	 * @param int $timestamp UTC timestamp.
	 * @return string
	 */
	private function format_datetime_input( $timestamp ) {
		return $timestamp ? wp_date( 'Y-m-d\\TH:i', $timestamp, wp_timezone() ) : '';
	}

	/**
	 * Renders the campaign editor.
	 *
	 * @param array $campaign Campaign (defaults for a new one).
	 */
	private function render_editor( $campaign ) {
		$is_new     = '' === $campaign['id'];
		$status     = DGW_Campaign_Store::get_status( $campaign );
		$labels     = $this->get_status_labels();
		$categories = $this->get_category_options();
		?>
		<header class="dgw-header dgw-header--editor">
			<div class="dgw-header__copy">
				<a class="dgw-back" href="<?php echo esc_url( $this->page_url() ); ?>">
					<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
					<?php esc_html_e( 'All campaigns', 'descuento-global-woo' ); ?>
				</a>
				<h1><?php echo $is_new ? esc_html__( 'New campaign', 'descuento-global-woo' ) : esc_html( $campaign['name'] ); ?></h1>
			</div>
			<?php if ( ! $is_new ) : ?>
				<span class="dgw-stamp dgw-stamp--<?php echo esc_attr( $status ); ?> dgw-stamp--on-dark"><?php echo esc_html( $labels[ $status ] ); ?></span>
			<?php endif; ?>
		</header>

		<?php
		$feedback = $this->get_feedback( $this->message );
		$this->render_feedback( $feedback );
		$invalid = $this->get_invalid_field( $this->message );
		?>

		<form class="dgw-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php echo $invalid ? ' data-dgw-invalid="' . esc_attr( $invalid ) . '"' : ''; ?>>
			<input type="hidden" name="action" value="dgw_save_campaign">
			<input type="hidden" name="campaign" value="<?php echo esc_attr( $campaign['id'] ); ?>">
			<?php wp_nonce_field( 'dgw_save_campaign', 'dgw_nonce' ); ?>

			<section class="dgw-panel dgw-panel--coupon">
				<div class="dgw-panel__stub">
					<label for="dgw-percentage"><?php esc_html_e( 'Discount', 'descuento-global-woo' ); ?></label>
					<div class="dgw-percent-input">
						<input id="dgw-percentage" name="percentage" type="number" inputmode="decimal" min="0.01" max="100" step="0.01" required aria-describedby="dgw-percentage-help"<?php $this->invalid_attr( 'dgw-percentage', $invalid ); ?> value="<?php echo esc_attr( $campaign['percentage'] ? wc_format_decimal( $campaign['percentage'], 2, true ) : '' ); ?>">
						<span aria-hidden="true">%</span>
					</div>
					<p id="dgw-percentage-help" class="dgw-panel__hint"><?php esc_html_e( 'From 0.01 to 100', 'descuento-global-woo' ); ?></p>
				</div>
				<div class="dgw-panel__body">
					<div class="dgw-field">
						<label for="dgw-name"><?php esc_html_e( 'Campaign name', 'descuento-global-woo' ); ?></label>
						<input id="dgw-name" name="name" type="text" required maxlength="80"<?php $this->invalid_attr( 'dgw-name', $invalid ); ?> placeholder="<?php esc_attr_e( 'Black Friday 2026', 'descuento-global-woo' ); ?>" value="<?php echo esc_attr( $campaign['name'] ); ?>">
					</div>
					<label class="dgw-toggle-field">
						<input type="checkbox" name="enabled" value="1" <?php checked( $campaign['enabled'] ); ?>>
						<span class="dgw-switch__track" aria-hidden="true"><span></span></span>
						<span class="dgw-toggle-field__copy">
							<strong><?php esc_html_e( 'Campaign on', 'descuento-global-woo' ); ?></strong>
							<?php esc_html_e( 'When it is on, it applies within its dates. You can save it off and turn it on later.', 'descuento-global-woo' ); ?>
						</span>
					</label>
				</div>
			</section>

			<section class="dgw-panel">
				<h2><?php esc_html_e( 'Products', 'descuento-global-woo' ); ?></h2>
				<p class="dgw-panel__intro"><?php esc_html_e( 'Variations follow the categories of their parent product.', 'descuento-global-woo' ); ?></p>
				<fieldset class="dgw-scope">
					<legend class="screen-reader-text"><?php esc_html_e( 'Campaign scope', 'descuento-global-woo' ); ?></legend>
					<label class="dgw-scope__option">
						<input type="radio" name="scope" value="all" <?php checked( 'all', $campaign['scope'] ); ?>>
						<span>
							<strong><?php esc_html_e( 'Whole store', 'descuento-global-woo' ); ?></strong>
							<?php esc_html_e( 'Every product, except the excluded categories.', 'descuento-global-woo' ); ?>
						</span>
					</label>
					<label class="dgw-scope__option">
						<input type="radio" name="scope" value="categories" <?php checked( 'categories', $campaign['scope'] ); ?>>
						<span>
							<strong><?php esc_html_e( 'Selected categories', 'descuento-global-woo' ); ?></strong>
							<?php esc_html_e( 'Only products in the categories you choose.', 'descuento-global-woo' ); ?>
						</span>
					</label>
				</fieldset>
				<div class="dgw-fields-grid">
					<div class="dgw-field<?php echo 'dgw-include-categories' === $invalid ? ' dgw-field--invalid' : ''; ?>" data-dgw-scope-field="categories">
						<label for="dgw-include-categories"><?php esc_html_e( 'Included categories', 'descuento-global-woo' ); ?></label>
						<?php $this->render_category_select( 'dgw-include-categories', 'include_categories', $campaign['include_categories'], $categories, 'dgw-include-help', 'dgw-include-categories' === $invalid ); ?>
						<p id="dgw-include-help">
							<?php if ( 'dgw-include-categories' === $invalid ) : ?>
								<strong class="dgw-field__error"><?php esc_html_e( 'Choose at least one category.', 'descuento-global-woo' ); ?></strong>
							<?php endif; ?>
							<?php esc_html_e( 'Products in any of these categories or their subcategories get the discount.', 'descuento-global-woo' ); ?>
						</p>
					</div>
					<div class="dgw-field">
						<label for="dgw-exclude-categories"><?php esc_html_e( 'Excluded categories', 'descuento-global-woo' ); ?> <span><?php esc_html_e( 'Optional', 'descuento-global-woo' ); ?></span></label>
						<?php $this->render_category_select( 'dgw-exclude-categories', 'exclude_categories', $campaign['exclude_categories'], $categories, 'dgw-exclude-help' ); ?>
						<p id="dgw-exclude-help"><?php esc_html_e( 'Products in these categories never get the discount, even if they are also in an included category.', 'descuento-global-woo' ); ?></p>
					</div>
				</div>
			</section>

			<section class="dgw-panel">
				<h2><?php esc_html_e( 'Schedule', 'descuento-global-woo' ); ?></h2>
				<p class="dgw-panel__intro">
					<?php
					/* translators: %s: WordPress timezone name. */
					echo esc_html( sprintf( __( 'Dates use the store timezone: %s.', 'descuento-global-woo' ), wp_timezone_string() ) );
					?>
				</p>
				<div class="dgw-fields-grid">
					<div class="dgw-field">
						<label for="dgw-start"><?php esc_html_e( 'Start', 'descuento-global-woo' ); ?> <span><?php esc_html_e( 'Optional', 'descuento-global-woo' ); ?></span></label>
						<input id="dgw-start" name="start" type="datetime-local" aria-describedby="dgw-start-help" value="<?php echo esc_attr( $this->format_datetime_input( $campaign['start'] ) ); ?>">
						<p id="dgw-start-help"><?php esc_html_e( 'Empty: starts as soon as the campaign is on.', 'descuento-global-woo' ); ?></p>
					</div>
					<div class="dgw-field">
						<label for="dgw-end"><?php esc_html_e( 'End', 'descuento-global-woo' ); ?> <span><?php esc_html_e( 'Optional', 'descuento-global-woo' ); ?></span></label>
						<input id="dgw-end" name="end" type="datetime-local" aria-describedby="dgw-end-help"<?php $this->invalid_attr( 'dgw-end', $invalid ); ?> value="<?php echo esc_attr( $this->format_datetime_input( $campaign['end'] ) ); ?>">
						<p id="dgw-end-help"><?php esc_html_e( 'Empty: runs until you turn it off.', 'descuento-global-woo' ); ?></p>
					</div>
				</div>
			</section>

			<div class="dgw-actions">
				<button type="submit" class="dgw-button"><?php echo $is_new ? esc_html__( 'Create campaign', 'descuento-global-woo' ) : esc_html__( 'Save changes', 'descuento-global-woo' ); ?></button>
				<a class="dgw-button dgw-button--ghost" href="<?php echo esc_url( $this->page_url() ); ?>"><?php esc_html_e( 'Cancel', 'descuento-global-woo' ); ?></a>
				<span class="dgw-actions__note"><?php esc_html_e( 'Saved product prices never change.', 'descuento-global-woo' ); ?></span>
			</div>
		</form>

		<?php if ( ! $is_new ) : ?>
			<section class="dgw-danger">
				<div>
					<h2><?php esc_html_e( 'Delete campaign', 'descuento-global-woo' ); ?></h2>
					<p><?php esc_html_e( 'Removes this campaign from the list. Product prices are not affected.', 'descuento-global-woo' ); ?></p>
				</div>
				<?php
				$this->render_action_form(
					'delete',
					$campaign['id'],
					'<span class="dashicons dashicons-trash" aria-hidden="true"></span>' . esc_html__( 'Delete campaign', 'descuento-global-woo' ),
					array(
						'class'            => 'dgw-button dgw-button--danger',
						/* translators: %s: campaign name. */
						'data-dgw-confirm' => sprintf( __( 'Delete the campaign "%s"? This cannot be undone.', 'descuento-global-woo' ), $campaign['name'] ),
					)
				);
				?>
			</section>
		<?php endif; ?>
		<?php
	}

	/**
	 * Returns the visible switch label, which never contradicts the status stamp.
	 *
	 * @param array  $campaign Campaign.
	 * @param string $status   Campaign status.
	 * @return string
	 */
	private function get_switch_label( $campaign, $status ) {
		if ( ! $campaign['enabled'] ) {
			return __( 'Off', 'descuento-global-woo' );
		}

		return 'expired' === $status ? __( 'On · ended', 'descuento-global-woo' ) : __( 'On', 'descuento-global-woo' );
	}

	/**
	 * Returns the ID of the field a validation message refers to.
	 *
	 * @param string $message Message key.
	 * @return string
	 */
	private function get_invalid_field( $message ) {
		$fields = array(
			'missing_name'       => 'dgw-name',
			'invalid_percentage' => 'dgw-percentage',
			'invalid_dates'      => 'dgw-end',
			'invalid_range'      => 'dgw-end',
			'missing_categories' => 'dgw-include-categories',
		);

		return isset( $fields[ $message ] ) ? $fields[ $message ] : '';
	}

	/**
	 * Prints aria-invalid when a field is the one that failed validation.
	 *
	 * @param string $field   Field ID.
	 * @param string $invalid Invalid field ID.
	 */
	private function invalid_attr( $field, $invalid ) {
		if ( $field === $invalid ) {
			echo ' aria-invalid="true"';
		}
	}

	/**
	 * Renders the author signature.
	 */
	private function render_signature() {
		?>
		<p class="dgw-signature">
			<?php
			printf(
				/* translators: 1: plugin name and version, 2: author link. */
				esc_html__( '%1$s · Made by %2$s', 'descuento-global-woo' ),
				esc_html( 'Descuento Global Woo ' . DGW_VERSION ),
				'<a href="https://maurouix.com/" target="_blank" rel="noopener">Mauricio Correa</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Key of the transient that keeps rejected form input for the current user.
	 *
	 * @return string
	 */
	private function form_transient_key() {
		return 'dgw_form_' . get_current_user_id();
	}

	/**
	 * Replaces campaign values with input rejected on the last save, if any.
	 *
	 * @param array $campaign Campaign.
	 * @return array
	 */
	private function restore_form_input( $campaign ) {
		$input = get_transient( $this->form_transient_key() );
		if ( is_array( $input ) && isset( $input['id'] ) && $input['id'] === $campaign['id'] ) {
			delete_transient( $this->form_transient_key() );
			return DGW_Campaign_Store::normalize( array_merge( $campaign, $input ) );
		}

		return $campaign;
	}

	/**
	 * Parses a strict datetime-local value in the WordPress timezone.
	 *
	 * @param string $value Input value.
	 * @return int|false
	 */
	private function parse_datetime( $value ) {
		$date   = DateTimeImmutable::createFromFormat( '!Y-m-d\\TH:i', $value, wp_timezone() );
		$errors = DateTimeImmutable::getLastErrors();

		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) || $date->format( 'Y-m-d\\TH:i' ) !== $value ) {
			return false;
		}

		return $date->getTimestamp();
	}

	/**
	 * Keeps only IDs of existing product categories from a POST field.
	 *
	 * @param string $field POST field name.
	 * @return int[]
	 */
	private function read_category_ids( $field ) {
		$raw = isset( $_POST[ $field ] ) ? wp_unslash( (array) $_POST[ $field ] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked by the caller; values cast to int below.

		return array_values(
			array_filter(
				DGW_Campaign_Store::sanitize_ids( $raw ),
				function ( $term_id ) {
					return (bool) term_exists( $term_id, 'product_cat' );
				}
			)
		);
	}

	/**
	 * Stops unless the user may manage campaigns.
	 */
	private function require_capability() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'descuento-global-woo' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Redirects to the list or editor with a result code.
	 *
	 * @param string $message Message code.
	 * @param array  $args    Extra query arguments.
	 */
	private function redirect( $message, $args = array() ) {
		wp_safe_redirect( $this->page_url( array_merge( $args, array( 'dgw_message' => $message ) ) ) );
		exit;
	}

	/**
	 * Returns the campaign ID posted with an action form.
	 *
	 * @return string
	 */
	private function posted_id() {
		return isset( $_POST['campaign'] ) ? sanitize_key( wp_unslash( $_POST['campaign'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by each handler.
	}

	/**
	 * Validates and saves a campaign.
	 */
	public function handle_save() {
		$this->require_capability();
		check_admin_referer( 'dgw_save_campaign', 'dgw_nonce' );

		$id = $this->posted_id();
		if ( '' !== $id && ! $this->store->get( $id ) ) {
			$this->redirect( 'not_found' );
		}

		$raw_percentage = isset( $_POST['percentage'] ) ? str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['percentage'] ) ) ) : '';
		$raw_start      = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
		$raw_end        = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';
		$start          = '' === $raw_start ? 0 : $this->parse_datetime( $raw_start );
		$end            = '' === $raw_end ? 0 : $this->parse_datetime( $raw_end );

		$campaign = array(
			'id'                 => $id,
			'name'               => isset( $_POST['name'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['name'] ) ), 0, 80 ) : '',
			'enabled'            => isset( $_POST['enabled'] ),
			'percentage'         => is_numeric( $raw_percentage ) ? (float) wc_format_decimal( $raw_percentage, 2 ) : 0,
			'start'              => (int) $start,
			'end'                => (int) $end,
			'scope'              => isset( $_POST['scope'] ) && 'categories' === sanitize_key( wp_unslash( $_POST['scope'] ) ) ? 'categories' : 'all',
			'include_categories' => $this->read_category_ids( 'include_categories' ),
			'exclude_categories' => $this->read_category_ids( 'exclude_categories' ),
		);

		$error = '';
		if ( '' === trim( $campaign['name'] ) ) {
			$error = 'missing_name';
		} elseif ( ! is_numeric( $raw_percentage ) || (float) $raw_percentage < 0.01 || (float) $raw_percentage > 100 ) {
			$error = 'invalid_percentage';
		} elseif ( false === $start || false === $end ) {
			$error = 'invalid_dates';
		} elseif ( $end && ( $start ? $start : time() ) >= $end ) {
			$error = 'invalid_range';
		} elseif ( 'categories' === $campaign['scope'] && empty( $campaign['include_categories'] ) ) {
			$error = 'missing_categories';
		}

		if ( $error ) {
			set_transient( $this->form_transient_key(), $campaign, 5 * MINUTE_IN_SECONDS );
			$this->redirect(
				$error,
				array(
					'view'     => 'edit',
					'campaign' => $id,
				)
			);
		}

		$this->store->save( $campaign );
		$this->engine->after_campaigns_changed();

		$this->redirect( '' === $id ? 'created' : 'saved' );
	}

	/**
	 * Turns a campaign on or off.
	 */
	public function handle_toggle() {
		$this->require_capability();
		$id = $this->posted_id();
		check_admin_referer( 'dgw_toggle_' . $id );

		$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_key( wp_unslash( $_POST['enabled'] ) );
		if ( ! $this->store->set_enabled( $id, $enabled ) ) {
			$this->redirect( 'not_found' );
		}
		$this->engine->after_campaigns_changed();

		$this->redirect( $enabled ? 'enabled' : 'disabled' );
	}

	/**
	 * Duplicates a campaign.
	 */
	public function handle_duplicate() {
		$this->require_capability();
		$id = $this->posted_id();
		check_admin_referer( 'dgw_duplicate_' . $id );

		if ( ! $this->store->duplicate( $id ) ) {
			$this->redirect( 'not_found' );
		}
		$this->engine->after_campaigns_changed();

		$this->redirect( 'duplicated' );
	}

	/**
	 * Deletes a campaign.
	 */
	public function handle_delete() {
		$this->require_capability();
		$id = $this->posted_id();
		check_admin_referer( 'dgw_delete_' . $id );

		if ( ! $this->store->delete( $id ) ) {
			$this->redirect( 'not_found' );
		}
		$this->engine->after_campaigns_changed();

		$this->redirect( 'deleted' );
	}
}
