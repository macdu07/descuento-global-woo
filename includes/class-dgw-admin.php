<?php
/**
 * WooCommerce administration screen.
 *
 * @package DescuentoGlobalWoo
 */

defined( 'ABSPATH' ) || exit;

final class DGW_Admin {
	/**
	 * Campaign service.
	 *
	 * @var DGW_Campaign
	 */
	private $campaign;

	/**
	 * Constructor.
	 *
	 * @param DGW_Campaign $campaign Campaign service.
	 */
	public function __construct( $campaign ) {
		$this->campaign = $campaign;
	}

	/**
	 * Registers admin hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 60 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_dgw_save_campaign', array( $this, 'save_campaign' ) );
		add_action( 'admin_post_dgw_clear_campaign', array( $this, 'clear_campaign' ) );
	}

	/**
	 * Loads styles only on this plugin's settings screen.
	 *
	 * @param string $hook_suffix Current admin screen suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'woocommerce_page_descuento-global-woo' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'dgw-admin', DGW_PLUGIN_URL . 'assets/admin.css', array(), DGW_VERSION );
		wp_enqueue_script( 'dgw-admin', DGW_PLUGIN_URL . 'assets/admin.js', array(), DGW_VERSION, true );
	}

	/**
	 * Adds the settings page under WooCommerce.
	 */
	public function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			esc_html__( 'Descuento global', 'descuento-global-woo' ),
			esc_html__( 'Descuento global', 'descuento-global-woo' ),
			'manage_woocommerce',
			'descuento-global-woo',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders a campaign datetime in the WordPress timezone.
	 *
	 * @param int $timestamp UTC timestamp.
	 * @return string
	 */
	private function format_datetime_input( $timestamp ) {
		if ( ! $timestamp ) {
			return '';
		}

		return wp_date( 'Y-m-d\\TH:i', $timestamp, wp_timezone() );
	}

	/**
	 * Returns translated status presentation data.
	 *
	 * @param string $status Status key.
	 * @return array{label:string,color:string}
	 */
	private function get_status_presentation( $status ) {
		$statuses = array(
			'disabled' => array( 'label' => __( 'Deshabilitada', 'descuento-global-woo' ) ),
			'scheduled' => array( 'label' => __( 'Programada', 'descuento-global-woo' ) ),
			'active' => array( 'label' => __( 'Activa', 'descuento-global-woo' ) ),
			'expired' => array( 'label' => __( 'Finalizada', 'descuento-global-woo' ) ),
		);

		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['disabled'];
	}

	/**
	 * Returns feedback copy and presentation for a redirect message.
	 *
	 * @param string $message Message key.
	 * @return array{type:string,title:string,text:string}|null
	 */
	private function get_feedback( $message ) {
		$messages = array(
			'saved' => array(
				'type'  => 'success',
				'title' => __( 'Campaña guardada', 'descuento-global-woo' ),
				'text'  => __( 'La programación y el descuento ya están actualizados.', 'descuento-global-woo' ),
			),
			'cleared' => array(
				'type'  => 'success',
				'title' => __( 'Configuración limpia', 'descuento-global-woo' ),
				'text'  => __( 'La campaña se deshabilitó y todos sus valores fueron eliminados.', 'descuento-global-woo' ),
			),
			'invalid_percentage' => array(
				'type'  => 'error',
				'title' => __( 'Porcentaje no válido', 'descuento-global-woo' ),
				'text'  => __( 'Introduce un porcentaje entre 0,01 y 100.', 'descuento-global-woo' ),
			),
			'invalid_dates' => array(
				'type'  => 'error',
				'title' => __( 'No pudimos leer las fechas', 'descuento-global-woo' ),
				'text'  => __( 'Revisa las fechas y horas introducidas e inténtalo nuevamente.', 'descuento-global-woo' ),
			),
			'invalid_range' => array(
				'type'  => 'error',
				'title' => __( 'El intervalo no es válido', 'descuento-global-woo' ),
				'text'  => __( 'La fecha final debe ser posterior a la fecha inicial.', 'descuento-global-woo' ),
			),
		);

		return isset( $messages[ $message ] ) ? $messages[ $message ] : null;
	}

	/**
	 * Renders the admin page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para administrar esta campaña.', 'descuento-global-woo' ) );
		}

		$settings     = $this->campaign->get_settings();
		$status       = $this->get_status_presentation( $this->campaign->get_status( $settings ) );
		$message      = isset( $_GET['dgw_message'] ) ? sanitize_key( wp_unslash( $_GET['dgw_message'] ) ) : '';
		$feedback     = $this->get_feedback( $message );
		?>
		<div class="wrap dgw-admin">
			<?php if ( $feedback ) : ?>
				<div class="dgw-feedback dgw-feedback--<?php echo esc_attr( $feedback['type'] ); ?>" role="<?php echo 'error' === $feedback['type'] ? 'alert' : 'status'; ?>">
					<span class="dashicons <?php echo 'error' === $feedback['type'] ? 'dashicons-warning' : 'dashicons-yes-alt'; ?>" aria-hidden="true"></span>
					<div class="dgw-feedback__copy">
						<strong><?php echo esc_html( $feedback['title'] ); ?></strong>
						<p><?php echo esc_html( $feedback['text'] ); ?></p>
					</div>
					<button class="dgw-feedback__dismiss" type="button" aria-label="<?php esc_attr_e( 'Cerrar mensaje', 'descuento-global-woo' ); ?>">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
					</button>
				</div>
			<?php endif; ?>

			<div class="dgw-hero">
				<div class="dgw-hero__icon" aria-hidden="true"><span class="dashicons dashicons-tag"></span></div>
				<div class="dgw-hero__content">
					<h1><?php esc_html_e( 'Descuento Global Woo', 'descuento-global-woo' ); ?></h1>
					<p><?php esc_html_e( 'Programa una oferta para toda la tienda sin cambiar los precios guardados de tus productos.', 'descuento-global-woo' ); ?></p>
				</div>
				<div class="dgw-status dgw-status--<?php echo esc_attr( $this->campaign->get_status( $settings ) ); ?>">
					<span class="dgw-status__dot" aria-hidden="true"></span>
					<span><?php echo esc_html( $status['label'] ); ?></span>
				</div>
			</div>

			<form class="dgw-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dgw_save_campaign">
				<?php wp_nonce_field( 'dgw_save_campaign', 'dgw_nonce' ); ?>

				<section class="dgw-card dgw-card--toggle">
					<div>
						<h2><?php esc_html_e( 'Estado de la campaña', 'descuento-global-woo' ); ?></h2>
						<p><?php esc_html_e( 'Actívala cuando la configuración esté lista. Las fechas determinarán cuándo se aplica.', 'descuento-global-woo' ); ?></p>
					</div>
					<label class="dgw-switch">
						<input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
						<span class="dgw-switch__track" aria-hidden="true"><span></span></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Habilitar campaña', 'descuento-global-woo' ); ?></span>
					</label>
				</section>

				<section class="dgw-card">
					<div class="dgw-section-heading">
						<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
						<div>
							<h2><?php esc_html_e( 'Descuento', 'descuento-global-woo' ); ?></h2>
							<p><?php esc_html_e( 'Se calcula sobre el precio regular y reemplaza temporalmente cualquier oferta existente.', 'descuento-global-woo' ); ?></p>
						</div>
					</div>
					<div class="dgw-field dgw-field--percentage">
						<label for="dgw-percentage"><?php esc_html_e( 'Porcentaje global', 'descuento-global-woo' ); ?></label>
						<div class="dgw-input-suffix">
							<input id="dgw-percentage" name="percentage" type="number" min="0.01" max="100" step="0.01" required value="<?php echo esc_attr( $settings['percentage'] ?: '' ); ?>">
							<span>%</span>
						</div>
					</div>
				</section>

				<section class="dgw-card">
					<div class="dgw-section-heading">
						<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
						<div>
							<h2><?php esc_html_e( 'Programación', 'descuento-global-woo' ); ?></h2>
							<p>
								<?php
								printf(
									/* translators: %s: WordPress timezone name. */
									esc_html__( 'Zona horaria: %s', 'descuento-global-woo' ),
									esc_html( wp_timezone_string() )
								);
								?>
							</p>
						</div>
					</div>
					<div class="dgw-fields-grid">
						<div class="dgw-field">
							<label for="dgw-start"><?php esc_html_e( 'Fecha de inicio', 'descuento-global-woo' ); ?> <span><?php esc_html_e( 'Opcional', 'descuento-global-woo' ); ?></span></label>
							<input id="dgw-start" name="start" type="datetime-local" value="<?php echo esc_attr( $this->format_datetime_input( $settings['start'] ) ); ?>">
							<p><?php esc_html_e( 'Si queda vacía, el descuento comienza inmediatamente al guardar.', 'descuento-global-woo' ); ?></p>
						</div>
						<div class="dgw-field">
							<label for="dgw-end"><?php esc_html_e( 'Fecha de finalización', 'descuento-global-woo' ); ?> <span><?php esc_html_e( 'Opcional', 'descuento-global-woo' ); ?></span></label>
							<input id="dgw-end" name="end" type="datetime-local" value="<?php echo esc_attr( $this->format_datetime_input( $settings['end'] ) ); ?>">
							<p><?php esc_html_e( 'Si queda vacía, el descuento continúa activo hasta que lo deshabilites.', 'descuento-global-woo' ); ?></p>
						</div>
					</div>
				</section>

				<div class="dgw-actions">
					<?php submit_button( __( 'Guardar campaña', 'descuento-global-woo' ), 'primary', 'submit', false ); ?>
					<span><?php esc_html_e( 'Los precios originales nunca se modifican.', 'descuento-global-woo' ); ?></span>
				</div>
			</form>

			<section class="dgw-reset-zone">
				<div>
					<h2><?php esc_html_e( 'Limpiar configuración', 'descuento-global-woo' ); ?></h2>
					<p><?php esc_html_e( 'Deshabilita la campaña y elimina el porcentaje y las fechas. Los precios de los productos no se modifican.', 'descuento-global-woo' ); ?></p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="dgw_clear_campaign">
					<?php wp_nonce_field( 'dgw_clear_campaign', 'dgw_clear_nonce' ); ?>
					<button class="button dgw-reset-button" type="submit" data-dgw-confirm="<?php esc_attr_e( '¿Seguro que quieres limpiar toda la configuración de la campaña?', 'descuento-global-woo' ); ?>">
						<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						<?php esc_html_e( 'Limpiar todo', 'descuento-global-woo' ); ?>
					</button>
				</form>
			</section>
		</div>
		<?php
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
	 * Redirects back to the settings screen with a result code.
	 *
	 * @param string $message Message code.
	 */
	private function redirect_with_message( $message ) {
		$url = add_query_arg(
			array(
				'page'        => 'descuento-global-woo',
				'dgw_message' => $message,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Validates and saves campaign settings.
	 */
	public function save_campaign() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(
				esc_html__( 'No tienes permisos para realizar esta acción.', 'descuento-global-woo' ),
				'',
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'dgw_save_campaign', 'dgw_nonce' );

		$raw_percentage = isset( $_POST['percentage'] ) ? sanitize_text_field( wp_unslash( $_POST['percentage'] ) ) : '';
		$raw_percentage = str_replace( ',', '.', $raw_percentage );
		if ( ! is_numeric( $raw_percentage ) || (float) $raw_percentage < 0.01 || (float) $raw_percentage > 100 ) {
			$this->redirect_with_message( 'invalid_percentage' );
		}

		$raw_start = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
		$raw_end   = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';
		$start     = '' === $raw_start ? 0 : $this->parse_datetime( $raw_start );
		$end       = '' === $raw_end ? 0 : $this->parse_datetime( $raw_end );

		if ( false === $start || false === $end ) {
			$this->redirect_with_message( 'invalid_dates' );
		}
		$effective_start = $start ? $start : time();
		if ( $end && $effective_start >= $end ) {
			$this->redirect_with_message( 'invalid_range' );
		}

		$settings = array(
			'enabled'    => isset( $_POST['enabled'] ),
			'percentage' => (float) wc_format_decimal( $raw_percentage, 2 ),
			'start'      => $start,
			'end'        => $end,
		);

		update_option( DGW_Campaign::OPTION_NAME, $settings, false );
		$this->campaign->schedule_events( $settings );
		wc_delete_product_transients();

		$this->redirect_with_message( 'saved' );
	}

	/**
	 * Removes all campaign settings and scheduled boundary events.
	 */
	public function clear_campaign() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(
				esc_html__( 'No tienes permisos para realizar esta acción.', 'descuento-global-woo' ),
				'',
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'dgw_clear_campaign', 'dgw_clear_nonce' );
		delete_option( DGW_Campaign::OPTION_NAME );
		wp_clear_scheduled_hook( DGW_Campaign::START_HOOK );
		wp_clear_scheduled_hook( DGW_Campaign::END_HOOK );
		wc_delete_product_transients();

		$this->redirect_with_message( 'cleared' );
	}
}
