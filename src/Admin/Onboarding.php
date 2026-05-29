<?php
/**
 * Manejadores AJAX para el wizard de onboarding (registro + activación Stripe).
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
 * Manejadores AJAX para el wizard de onboarding: registro de cuenta y activación de plan.
 */
final class Onboarding {

	/** Registra los hooks AJAX del wizard de onboarding. */
	public function register(): void {
		add_action( 'wp_ajax_wan_onboarding_register', [ $this, 'ajax_register' ] );
		add_action( 'wp_ajax_wan_onboarding_poll', [ $this, 'ajax_poll_activation' ] );
		add_action( 'wp_ajax_wan_onboarding_checkout_url', [ $this, 'ajax_checkout_url' ] );
	}

	/** Registra una nueva cuenta de tienda en el wrapper. */
	public function ajax_register(): void {
		$this->verify_nonce();

		$email    = sanitize_email( wp_unslash( (string) ( $_POST['email'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$password = wp_unslash( (string) ( $_POST['password'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- passwords must not be sanitized

		if ( ! $email || strlen( $password ) < 8 ) {
			wp_send_json_error( [ 'message' => 'Introduce un email válido y una contraseña de al menos 8 caracteres.' ] );
		}

		$client = new WrapperClient();
		$result = $client->register( $email, $password );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$tenant_id   = (string) ( $result['tenantId'] ?? '' );
		$claim_token = (string) ( $result['claimToken'] ?? '' );
		if ( ! $tenant_id ) {
			wp_send_json_error( [ 'message' => 'El servidor no devolvió un identificador de cuenta.' ] );
		}

		Settings::set( 'tenant_id', $tenant_id );
		Settings::set( 'claim_token', $claim_token );

		wp_send_json_success( [ 'tenantId' => $tenant_id ] );
	}

	/** Devuelve la URL de checkout de Stripe para el plan seleccionado. */
	public function ajax_checkout_url(): void {
		$this->verify_nonce();

		$tenant_id = Settings::get( 'tenant_id' );
		if ( ! $tenant_id ) {
			wp_send_json_error( [ 'message' => 'No hay cuenta registrada. Completa el paso anterior.' ] );
		}

		$allowed = [ 'basic', 'pro', 'lifetime' ];
		$plan    = sanitize_key( (string) ( $_POST['plan'] ?? 'basic' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! in_array( $plan, $allowed, true ) ) {
			$plan = 'basic';
		}

		$base        = admin_url( 'admin.php?page=waxap&tab=connection' );
		$success_url = add_query_arg( 'payment', 'success', $base );
		$cancel_url  = add_query_arg( 'payment', 'cancelled', $base );

		$client = new WrapperClient();
		$result = $client->get_checkout_url( $tenant_id, $plan, $success_url, $cancel_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$url = (string) ( $result['url'] ?? '' );
		if ( ! $url ) {
			wp_send_json_error( [ 'message' => 'No se pudo obtener el enlace de pago. Contacta con soporte.' ] );
		}

		wp_send_json_success( [ 'url' => $url ] );
	}

	/** Consulta el estado de activación de la cuenta tras el pago. */
	public function ajax_poll_activation(): void {
		$this->verify_nonce();

		$tenant_id = Settings::get( 'tenant_id' );
		if ( ! $tenant_id ) {
			wp_send_json_error( [ 'message' => 'No hay cuenta registrada.' ] );
		}

		$client = new WrapperClient();
		$result = $client->get_auth_status( $tenant_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$status = (string) ( $result['status'] ?? 'pending_payment' );

		if ( 'active' === $status ) {
			$claim_token = Settings::get( 'claim_token' );
			if ( $claim_token ) {
				$credentials = $client->claim_credentials( $tenant_id, $claim_token );
				if ( is_wp_error( $credentials ) ) {
					wp_send_json_error( [ 'message' => $credentials->get_error_message() ] );
				}
				Settings::set( 'api_key', (string) ( $credentials['apiKey'] ?? '' ) );
				Settings::set( 'hmac_secret', (string) ( $credentials['hmacSecret'] ?? '' ) );
				Settings::set( 'claim_token', '' );
			}
		}

		wp_send_json_success( [ 'status' => $status ] );
	}

	/** Verifica el nonce y los permisos del usuario antes de procesar la petición AJAX. */
	private function verify_nonce(): void {
		if ( ! check_ajax_referer( 'wa_notifier_ajax', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Sesión expirada. Recarga la página.' ], 403 );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => 'No autorizado.' ], 403 );
		}
	}
}
