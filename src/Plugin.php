<?php
/**
 * Plugin bootstrap.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Admin\AdminMenu;
use WaNotifier\Admin\Onboarding;
use WaNotifier\Ajax\InboxAjax;
use WaNotifier\Ajax\SessionAjax;
use WaNotifier\Api\WrapperClient;
use WaNotifier\Checkout\CheckoutOptIn;
use WaNotifier\Emails\OrderEmails;
use WaNotifier\Orders\OrderEvents;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Clase principal del plugin. Inicializa todos los componentes y registra los hooks de WordPress.
 */
final class Plugin {

	/** Inicializa todos los componentes del plugin y registra los hooks de WordPress. */
	public function boot(): void {
		$this->setup_updater();
		Settings::maybe_migrate_status_keys();

		if ( is_admin() ) {
			$admin_menu = new AdminMenu();
			add_action( 'admin_menu', [ $admin_menu, 'register' ] );
			add_action( 'admin_init', [ $admin_menu, 'register_form_handlers' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
			add_filter(
				'plugin_action_links_' . plugin_basename( WA_NOTIFIER_FILE ),
				[ $this, 'add_settings_link' ]
			);
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_checkout_assets' ] );

		$ajax = new SessionAjax();
		$ajax->register();

		$inbox_ajax = new InboxAjax();
		$inbox_ajax->register();

		add_action(
			'wp_ajax_wa_notifier_billing_portal',
			function () {
				check_ajax_referer( 'wa_notifier_billing_portal', 'nonce' );
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_send_json_error( 'No autorizado.', 403 );
				}
				$return_url = admin_url( 'admin.php?page=waxap&tab=connection' );
				$result     = ( new WrapperClient() )->get_billing_portal_url( $return_url );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( $result->get_error_message() );
				}
				wp_send_json_success( [ 'url' => $result['url'] ] );
			}
		);

		$onboarding = new Onboarding();
		$onboarding->register();

		$order_emails = new OrderEmails();
		$order_emails->register_shortcode();

		$init_wc_components = function () use ( $order_emails ) {
			( new OrderEvents() )->register();
			$order_emails->register();
			( new CheckoutOptIn() )->register();
		};

		// WooCommerce 10+ dispara woocommerce_loaded en plugins_loaded con prioridad -1,
		// antes de que nuestro callback (prioridad 10) se ejecute. Si ya se disparó, llamamos
		// directamente; si no, esperamos al hook.
		if ( did_action( 'woocommerce_loaded' ) ) {
			$init_wc_components();
		} else {
			add_action( 'woocommerce_loaded', $init_wc_components );
		}
	}

	/**
	 * Añade un enlace de ajustes en la lista de plugins instalados.
	 *
	 * @param array<int|string,string> $links Lista de enlaces de acción del plugin.
	 * @return array<int|string,string>
	 */
	public function add_settings_link( array $links ): array {
		$url  = esc_url( admin_url( 'admin.php?page=waxap' ) );
		$text = esc_html__( 'Ajustes', 'waxap-for-woocommerce' );
		array_unshift( $links, "<a href=\"{$url}\">{$text}</a>" );
		return $links;
	}

	/** Encola los estilos del opt-in de WhatsApp en la página de checkout. */
	public function enqueue_checkout_assets(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		$ver = defined( 'WP_DEBUG' ) && WP_DEBUG ? (string) time() : WA_NOTIFIER_VERSION;

		wp_enqueue_style(
			'wa-notifier-checkout',
			WA_NOTIFIER_URL . 'assets/css/checkout.css',
			[],
			$ver,
		);
	}

	/**
	 * Encola los scripts y estilos del panel de administración.
	 *
	 * @param string $hook Nombre del hook de la página actual.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Solo en la página de WA Notifier.
		if ( 'woocommerce_page_waxap' !== $hook ) {
			return;
		}

		$ver = defined( 'WP_DEBUG' ) && WP_DEBUG ? (string) time() : WA_NOTIFIER_VERSION;

		wp_enqueue_style(
			'wa-notifier-admin',
			WA_NOTIFIER_URL . 'assets/css/admin.css',
			[],
			$ver,
		);

		wp_enqueue_script(
			'wa-notifier-admin',
			WA_NOTIFIER_URL . 'assets/js/admin-session.js',
			[ 'jquery' ],
			$ver,
			true,
		);

		wp_localize_script(
			'wa-notifier-admin',
			'waNotifierData',
			[
				'nonce'         => wp_create_nonce( 'wa_notifier_ajax' ),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'confirmUnlink' => __( '¿Seguro que quieres desvincular el número WhatsApp?', 'waxap-for-woocommerce' ),
				'hasSession'    => Settings::has_session() ? '1' : '0',
				'sessionId'     => Settings::get( 'session_id' ),
			],
		);

		// Inbox de mensajes (solo en la pestaña Mensajes con cuenta conectada).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'connection';
		if ( 'messages' === $current_tab && Settings::is_connected() ) {
			wp_enqueue_script(
				'wa-notifier-inbox',
				WA_NOTIFIER_URL . 'assets/js/admin-inbox.js',
				[ 'jquery' ],
				$ver,
				true,
			);
			wp_localize_script(
				'wa-notifier-inbox',
				'waxapInbox',
				[
					'nonce'   => wp_create_nonce( 'wa_notifier_ajax' ),
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				]
			);
		}

		// Wizard de onboarding (solo cuando no está conectado).
		if ( ! Settings::is_connected() ) {
			wp_enqueue_script(
				'wa-notifier-onboarding',
				WA_NOTIFIER_URL . 'assets/js/admin-onboarding.js',
				[ 'jquery' ],
				$ver,
				true,
			);

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$payment_returned = isset( $_GET['payment'] ) && 'success' === $_GET['payment'];

			wp_localize_script(
				'wa-notifier-onboarding',
				'waxapOnboarding',
				[
					'nonce'           => wp_create_nonce( 'wa_notifier_ajax' ),
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'step'            => Settings::get( 'tenant_id' ) ? '2' : '1',
					'paymentReturned' => $payment_returned ? '1' : '0',
				],
			);
		}
	}

	/**
	 * Registra el checker de actualizaciones automáticas desde GitHub.
	 * Una vez el plugin esté en WordPress.org, WP usará el canal oficial en su lugar.
	 */
	private function setup_updater(): void {
		if ( ! class_exists( PucFactory::class ) ) {
			return;
		}
		$checker = PucFactory::buildUpdateChecker(
			'https://github.com/MrViSiOn/waxap-for-woocommerce/',
			WA_NOTIFIER_FILE,
			'waxap-for-woocommerce'
		);
		// Descargar el zip adjunto al GitHub Release en lugar del zip de fuentes.
		$checker->getVcsApi()->enableReleaseAssets();
	}
}
