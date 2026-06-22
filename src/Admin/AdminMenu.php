<?php
/**
 * Admin menu registration, tab rendering y handlers de formularios de settings.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Api\WrapperClient;
use WaNotifier\Settings;

/**
 * Registra y renderiza el menú de administración de Waxap en el panel de WordPress.
 */
final class AdminMenu {

	private const SLUG = 'waxap';

	/**
	 * Tabs del panel de administración.
	 *
	 * @var array<string,string>
	 */
	private const TABS = [
		'connection'    => 'Conexión',
		'phone'         => 'Número WhatsApp',
		'notifications' => 'Notificaciones',
		'email'         => 'Email branding',
		'history'       => 'Historial',
		'messages'      => 'Mensajes',
	];

	/** Registra el submenú de Waxap bajo WooCommerce. */
	public function register(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Waxap', 'waxap-for-woocommerce' ),
			__( 'Waxap', 'waxap-for-woocommerce' ),
			'manage_woocommerce',
			self::SLUG,
			[ $this, 'render' ],
		);
	}

	/** Registra los handlers de formulario de admin-post para cada acción. */
	public function register_form_handlers(): void {
		add_action( 'admin_post_wa_notifier_save_notifications', [ $this, 'handle_save_notifications' ] );
		add_action( 'admin_post_wa_notifier_save_email', [ $this, 'handle_save_email' ] );
		add_action( 'admin_post_wa_notifier_save_connection', [ $this, 'handle_save_connection' ] );
		add_action( 'admin_post_wa_notifier_disconnect', [ $this, 'handle_disconnect' ] );
		add_action( 'admin_post_wa_notifier_login', [ $this, 'handle_login' ] );
		add_action( 'admin_post_wa_notifier_cancel_registration', [ $this, 'handle_cancel_registration' ] );
	}

	/** Renderiza la página principal de administración con el sistema de tabs. */
	public function render(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw     = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'connection';
		$current = array_key_exists( $raw, self::TABS ) ? $raw : 'connection';
		?>
		<div class="wrap waxap-admin-wrap">

			<header class="waxap-header">
				<span class="waxap-logo-mark">W</span>
				<div class="waxap-header-text">
					<h1><?php esc_html_e( 'Waxap', 'waxap-for-woocommerce' ); ?></h1>
					<p><?php esc_html_e( 'Notificaciones WhatsApp para WooCommerce', 'waxap-for-woocommerce' ); ?></p>
				</div>
			</header>

			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<?php
				$is_connected = Settings::is_connected();
				$visible_tabs = $is_connected ? self::TABS : [ 'connection' => self::TABS['connection'] ];
				foreach ( $visible_tabs as $slug => $label ) :
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&tab=' . $slug ) ); ?>"
						class="nav-tab <?php echo $current === $slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Labels defined as translatable constants in TABS array ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="waxap-tab-content">
				<?php $this->render_tab( $current ); ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Incluye la vista correspondiente a la pestaña activa.
	 *
	 * @param string $tab Slug de la pestaña a renderizar.
	 */
	private function render_tab( string $tab ): void {
		if ( 'connection' !== $tab && ! Settings::is_connected() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&tab=connection' ) );
			exit;
		}

		if ( 'connection' === $tab ) {
			$is_connected = Settings::is_connected();

			// Usuario vuelve de Stripe con pago completado y webhook ya procesado:
			// redirigir a la vista de "cuenta activada" limpiando el parámetro payment.
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['payment'] ) && 'success' === $_GET['payment'] && $is_connected ) {
				wp_safe_redirect(
					add_query_arg(
						[
							'page' => self::SLUG,
							'tab'  => 'connection',
							'ob'   => 'done',
						],
						admin_url( 'admin.php' )
					)
				);
				exit;
			}

			$wrapper_url = Settings::get( 'wrapper_url' );
			$api_key     = Settings::get( 'api_key' );
			$tenant_id   = Settings::get( 'tenant_id' );
			$usage       = null;
			if ( $is_connected ) {
				$client = new WrapperClient();
				$result = $client->get_usage();
				if ( ! is_wp_error( $result ) ) {
					$usage = $result;
				}
			}
			include __DIR__ . '/views/tab-connection.php';
			return;
		}

		if ( 'notifications' === $tab ) {
			$enabled_statuses = array_filter(
				explode( ',', Settings::get( 'notify_statuses' ) )
			);
			$status_meta      = [
				'processing' => [
					'color' => '#2271b1',
					'desc'  => 'El cliente completó el pago. El pedido está en preparación.',
				],
				'completed'  => [
					'color' => '#25d366',
					'desc'  => 'El pedido ha sido entregado o marcado como completado.',
				],
				'on-hold'    => [
					'color' => '#f59e0b',
					'desc'  => 'Pago pendiente de confirmación (ej. transferencia bancaria).',
				],
				'cancelled'  => [
					'color' => '#ef4444',
					'desc'  => 'El pedido fue cancelado por el cliente o la tienda.',
				],
				'refunded'   => [
					'color' => '#8b5cf6',
					'desc'  => 'El importe fue devuelto al cliente.',
				],
				'pending'    => [
					'color' => '#9ca3af',
					'desc'  => 'El pedido existe pero el cliente aún no ha pagado.',
				],
				'failed'     => [
					'color' => '#6b7280',
					'desc'  => 'El pago no pudo completarse.',
				],
			];
			$statuses         = [];
			$templates        = [];
			foreach ( wc_get_order_statuses() as $wc_key => $label ) {
				$key               = substr( $wc_key, 3 );
				$meta              = $status_meta[ $key ] ?? [];
				$statuses[ $key ]  = [
					'label' => $label,
					'color' => $meta['color'] ?? '#6b7280',
					'desc'  => $meta['desc'] ?? '',
				];
				$templates[ $key ] = Settings::get( 'template_' . $key );
			}
			$country_code_val = Settings::get( 'phone_country_code' );
			$country_code     = $country_code_val ? $country_code_val : '34';
			include __DIR__ . '/views/tab-notifications.php';
			return;
		}

		if ( 'email' === $tab ) {
			$email_enabled = '1' === Settings::get( 'email_button_enabled' );
			$email_text    = Settings::get( 'email_button_text' );
			$email_prefill = Settings::get( 'email_button_prefill' );
			$has_phone     = ( '' !== Settings::get( 'phone_number' ) );
			include __DIR__ . '/views/tab-email.php';
			return;
		}

		if ( 'history' === $tab ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cast to int immediately
			$page   = max( 1, (int) wp_unslash( $_GET['paged'] ?? 1 ) );
			$limit  = 20;
			$offset = ( $page - 1 ) * $limit;

			$client = new WrapperClient();
			$result = $client->get_message_log( $limit, $offset );
			$log    = is_wp_error( $result ) ? null : $result;
			$error  = is_wp_error( $result ) ? $result->get_error_message() : null;

			$stats_result = $client->get_message_stats();
			$stats        = is_wp_error( $stats_result ) ? null : $stats_result;
			include __DIR__ . '/views/tab-history.php';
			return;
		}

		if ( 'messages' === $tab ) {
			$client        = new WrapperClient();
			$result        = $client->get_inbox_conversations( 30 );
			$conversations = is_wp_error( $result ) ? null : ( $result['data'] ?? [] );
			$error         = is_wp_error( $result ) ? $result->get_error_message() : null;
			include __DIR__ . '/views/tab-messages.php';
			return;
		}

		$view = __DIR__ . '/views/tab-' . $tab . '.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}

	/** Procesa el guardado de los estados y plantillas de notificación. */
	public function handle_save_notifications(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'No autorizado.' );
		}

		check_admin_referer( 'wa_notifier_save_notifications' );

		$valid = array_map( fn( string $k ) => substr( $k, 3 ), array_keys( wc_get_order_statuses() ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-element via sanitize_key() below
		$posted   = isset( $_POST['wan_notify_statuses'] ) && is_array( $_POST['wan_notify_statuses'] )
			? wp_unslash( $_POST['wan_notify_statuses'] )
			: [];
		$selected = array_values(
			array_filter(
				array_map( 'sanitize_key', $posted ),
				fn( string $s ) => in_array( $s, $valid, true )
			)
		);

		Settings::set( 'notify_statuses', implode( ',', $selected ) );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via preg_replace removing all non-digits
		$country_code = preg_replace( '/\D/', '', wp_unslash( (string) ( $_POST['phone_country_code'] ?? '34' ) ) );
		Settings::set( 'phone_country_code', $country_code ? $country_code : '34' );

		// Save message templates.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-element via sanitize_textarea_field() below
		$posted_templates = isset( $_POST['wan_templates'] ) && is_array( $_POST['wan_templates'] )
			? wp_unslash( $_POST['wan_templates'] )
			: [];
		foreach ( $valid as $s ) {
			$tpl = sanitize_textarea_field( (string) ( $posted_templates[ $s ] ?? '' ) );
			Settings::set( 'template_' . $s, $tpl );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::SLUG,
					'tab'     => 'notifications',
					'updated' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/** Procesa el guardado de la configuración de email branding. */
	public function handle_save_email(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'No autorizado.' );
		}

		check_admin_referer( 'wa_notifier_save_email' );

		Settings::set( 'email_button_enabled', isset( $_POST['email_button_enabled'] ) ? '1' : '0' );
		Settings::set( 'email_button_text', sanitize_text_field( wp_unslash( (string) ( $_POST['email_button_text'] ?? '' ) ) ) );
		Settings::set( 'email_button_prefill', sanitize_textarea_field( wp_unslash( (string) ( $_POST['email_button_prefill'] ?? '' ) ) ) );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::SLUG,
					'tab'     => 'email',
					'updated' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/** Procesa el guardado manual de las credenciales de conexión. */
	public function handle_save_connection(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'No autorizado.' );
		}

		check_admin_referer( 'wa_notifier_save_connection' );

		$wrapper_url = esc_url_raw( wp_unslash( (string) ( $_POST['wrapper_url'] ?? '' ) ) );
		$api_key     = sanitize_text_field( wp_unslash( (string) ( $_POST['api_key'] ?? '' ) ) );
		$tenant_id   = sanitize_text_field( wp_unslash( (string) ( $_POST['tenant_id'] ?? '' ) ) );

		if ( $wrapper_url ) {
			Settings::set( 'wrapper_url', $wrapper_url );
		}
		Settings::set( 'api_key', $api_key );
		Settings::set( 'tenant_id', $tenant_id );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::SLUG,
					'tab'     => 'connection',
					'updated' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/** Procesa el inicio de sesión con email y contraseña. */
	public function handle_login(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'No autorizado.' );
		}

		check_admin_referer( 'wa_notifier_login' );

		$email    = sanitize_email( wp_unslash( (string) ( $_POST['email'] ?? '' ) ) );
		$password = wp_unslash( (string) ( $_POST['password'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- passwords must not be sanitized

		if ( ! $email || ! $password ) {
			wp_safe_redirect(
				add_query_arg(
					[
						'page'        => self::SLUG,
						'tab'         => 'connection',
						'login_error' => 'missing_fields',
					],
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$client = new WrapperClient();
		$result = $client->login( $email, $password );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					[
						'page'        => self::SLUG,
						'tab'         => 'connection',
						'login_error' => rawurlencode( $result->get_error_message() ),
					],
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		Settings::set( 'api_key', $result['apiKey'] );
		Settings::set( 'tenant_id', $result['tenantId'] );
		Settings::set( 'hmac_secret', $result['hmacSecret'] );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::SLUG,
					'tab'     => 'phone',
					'updated' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/** Cancela el registro en curso y devuelve al formulario de login. */
	public function handle_cancel_registration(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'No autorizado.' );
		}

		check_admin_referer( 'wa_notifier_cancel_registration' );

		Settings::set( 'tenant_id', '' );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'       => self::SLUG,
					'tab'        => 'connection',
					'show_login' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/** Desconecta la cuenta borrando todas las credenciales almacenadas. */
	public function handle_disconnect(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'No autorizado.' );
		}

		check_admin_referer( 'wa_notifier_disconnect' );

		foreach ( [ 'api_key', 'tenant_id', 'session_id', 'hmac_secret', 'phone_number' ] as $key ) {
			Settings::set( $key, '' );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'         => self::SLUG,
					'tab'          => 'connection',
					'disconnected' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
