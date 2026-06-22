<?php
/**
 * Manejadores WP AJAX para la vinculación de sesión WhatsApp.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Api\WrapperClient;
use WaNotifier\Settings;

/**
 * Manejadores WP AJAX para el flujo de vinculación y gestión de sesión WhatsApp.
 */
final class SessionAjax {

	/** Registra los hooks AJAX del plugin. */
	public function register(): void {
		foreach ( [ 'create_session', 'poll_session', 'disconnect', 'send_test', 'delete_session' ] as $action ) {
			add_action( "wp_ajax_wa_notifier_{$action}", [ $this, "handle_{$action}" ] );
		}
	}

	/** Crea una nueva sesión WhatsApp en el wrapper. */
	public function handle_create_session(): void {
		$this->verify_nonce();

		$client    = new WrapperClient();
		$site_name = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! $site_name ) {
			$site_name = 'tienda';
		}
		$session = $client->create_session( $site_name, home_url() );

		if ( is_wp_error( $session ) ) {
			wp_send_json_error( [ 'message' => $session->get_error_message() ] );
		}

		$session_id = (string) ( $session['id'] ?? '' );
		Settings::set( 'session_id', $session_id );

		wp_send_json_success( [ 'sessionId' => $session_id ] );
	}

	/** Consulta el estado de la sesión activa y retorna el QR si corresponde. */
	public function handle_poll_session(): void {
		$this->verify_nonce();

		$session_id = Settings::get( 'session_id' );
		if ( ! $session_id ) {
			wp_send_json_error( [ 'message' => 'No hay sesión activa.' ] );
		}

		$client = new WrapperClient();
		$status = $client->get_session( $session_id );

		if ( is_wp_error( $status ) ) {
			wp_send_json_error( [ 'message' => $status->get_error_message() ] );
		}

		$current_status = (string) ( $status['status'] ?? 'unknown' );
		$qr             = null;

		// Solicitar QR si la sesión está esperando escaneo.
		if ( in_array( $current_status, [ 'initializing', 'qr_ready' ], true ) ) {
			$qr_response = $client->get_qr( $session_id );
			if ( ! is_wp_error( $qr_response ) ) {
				$qr = $qr_response['qr'] ?? null;
			}
		}

		$phone = (string) ( $status['phoneNumber'] ?? '' );

		// Persistir número vinculado al alcanzar estado ready.
		if ( 'ready' === $current_status && $phone ) {
			Settings::set( 'phone_number', $phone );
		}

		wp_send_json_success(
			[
				'status' => $current_status,
				'qr'     => $qr,
				'phone'  => $phone ? $phone : null,
			]
		);
	}

	/** Desconecta la sesión activa y limpia las credenciales almacenadas. */
	public function handle_disconnect(): void {
		$this->verify_nonce();

		$session_id = Settings::get( 'session_id' );
		if ( $session_id ) {
			$client = new WrapperClient();
			$client->delete_session( $session_id );
		}

		Settings::delete( 'session_id' );
		Settings::delete( 'phone_number' );

		wp_send_json_success();
	}

	/** Envía un mensaje de prueba al número indicado. */
	public function handle_send_test(): void {
		$this->verify_nonce();

		$to = sanitize_text_field( wp_unslash( (string) ( $_POST['to'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $to ) {
			wp_send_json_error( [ 'message' => __( 'El número de teléfono es obligatorio.', 'waxap-for-woocommerce' ) ] );
		}

		$session_id = Settings::get( 'session_id' );
		if ( ! $session_id ) {
			wp_send_json_error( [ 'message' => __( 'No hay sesión activa.', 'waxap-for-woocommerce' ) ] );
		}

		$message = sanitize_text_field( wp_unslash( (string) ( $_POST['message'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$client = new WrapperClient();
		$result = $client->send_test( $session_id, $to, $message );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'messageId' => $result['messageId'] ?? '' ] );
	}

	/** Elimina una sesión concreta (por ID) del wrapper. */
	public function handle_delete_session(): void {
		$this->verify_nonce();

		$session_id = sanitize_text_field( wp_unslash( (string) ( $_POST['session_id'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $session_id ) {
			wp_send_json_error( [ 'message' => __( 'ID de sesión requerido.', 'waxap-for-woocommerce' ) ] );
		}

		$client = new WrapperClient();
		$result = $client->delete_session( $session_id );

		if ( Settings::get( 'session_id' ) === $session_id ) {
			Settings::delete( 'session_id' );
			Settings::delete( 'phone_number' );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success();
	}

	/** Verifica el nonce y los permisos del usuario antes de procesar cualquier petición. */
	private function verify_nonce(): void {
		if ( ! check_ajax_referer( 'wa_notifier_ajax', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Sesión expirada. Recarga la página.' ], 403 );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => 'No autorizado.' ], 403 );
		}
	}
}
