<?php
/**
 * Cliente HTTP para la API del wrapper WA Notifier.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Settings;
use WP_Error;

/**
 * Cliente HTTP para comunicarse con la API REST del wrapper Waxap.
 */
final class WrapperClient {

	/**
	 * URL base del wrapper (sin barra final).
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * API key del tenant autenticado.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * ID del tenant en el wrapper.
	 *
	 * @var string
	 */
	private string $tenant_id;

	/** Inicializa el cliente con las credenciales almacenadas en Settings. */
	public function __construct() {
		$this->base_url  = rtrim( Settings::get( 'wrapper_url' ), '/' );
		$this->api_key   = Settings::get( 'api_key' );
		$this->tenant_id = Settings::get( 'tenant_id' );
	}

	/**
	 * Registra la tienda y devuelve tenantId + claimToken de un solo uso.
	 *
	 * @param string $email    Email del administrador de la tienda.
	 * @param string $password Contraseña de la cuenta.
	 * @return array{tenantId:string,claimToken:string}|WP_Error
	 */
	public function register( string $email, string $password ): array|WP_Error {
		return $this->request(
			'POST',
			'/v1/auth/register',
			[
				'email'    => $email,
				'password' => $password,
			]
		);
	}

	/**
	 * Crea una sesión en el wrapper y la arranca.
	 *
	 * @param string $name      Nombre identificador de la sesión.
	 * @param string $store_url URL base de la tienda WooCommerce (plan Agency).
	 * @return array{id:string,status:string,...}|WP_Error
	 */
	public function create_session( string $name, string $store_url = '' ): array|WP_Error {
		$body = [ 'name' => $name ];
		if ( $store_url ) {
			$body['storeUrl'] = $store_url;
		}
		return $this->request( 'POST', '/v1/sessions', $body, auth: true );
	}

	/**
	 * Lista todas las sesiones del tenant.
	 *
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	public function get_sessions(): array|WP_Error {
		return $this->request( 'GET', '/v1/sessions', auth: true );
	}

	/**
	 * Obtiene el estado actual de la sesión.
	 *
	 * @param string $session_id ID de la sesión a consultar.
	 * @return array{id:string,status:string,...}|WP_Error
	 */
	public function get_session( string $session_id ): array|WP_Error {
		return $this->request( 'GET', "/v1/sessions/{$session_id}", auth: true );
	}

	/**
	 * Obtiene el QR actual (para polling).
	 *
	 * @param string $session_id ID de la sesión de la que obtener el QR.
	 * @return array{sessionId:string,qr:string}|WP_Error
	 */
	public function get_qr( string $session_id ): array|WP_Error {
		return $this->request( 'GET', "/v1/sessions/{$session_id}/qr", auth: true );
	}

	/**
	 * Envía un mensaje de prueba a un número de teléfono.
	 *
	 * @param string $session_id ID de la sesión desde la que enviar.
	 * @param string $to         Número de destino en formato internacional.
	 * @param string $message    Texto del mensaje (opcional).
	 * @return array{messageId:string}|WP_Error
	 */
	public function send_test( string $session_id, string $to, string $message = '' ): array|WP_Error {
		$body = [ 'to' => $to ];
		if ( '' !== $message ) {
			$body['message'] = $message;
		}
		return $this->request( 'POST', "/v1/sessions/{$session_id}/send-test", $body, auth: true );
	}

	/**
	 * Elimina la sesión del wrapper.
	 *
	 * @param string $session_id ID de la sesión a eliminar.
	 * @return true|WP_Error
	 */
	public function delete_session( string $session_id ): true|WP_Error {
		$result = $this->request( 'DELETE', "/v1/sessions/{$session_id}", auth: true );
		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Canjea el claim token de un solo uso para obtener las credenciales del tenant.
	 *
	 * Solo funciona una vez: el wrapper marca el token como consumido en la primera
	 * llamada exitosa. Reintentos posteriores reciben 401.
	 *
	 * @param string $tenant_id   ID del tenant.
	 * @param string $claim_token Token de reclamación emitido por register().
	 * @return array{apiKey:string,hmacSecret:string}|WP_Error
	 */
	public function claim_credentials( string $tenant_id, string $claim_token ): array|WP_Error {
		return $this->request(
			'POST',
			'/v1/auth/claim',
			[
				'tenantId'   => $tenant_id,
				'claimToken' => $claim_token,
			]
		);
	}

	/**
	 * Consulta el estado de activación de un tenant (polling post-pago Stripe).
	 *
	 * @param string $tenant_id ID del tenant a consultar.
	 * @return array{status:string}|WP_Error
	 */
	public function get_auth_status( string $tenant_id ): array|WP_Error {
		return $this->request( 'GET', "/v1/auth/status/{$tenant_id}" );
	}

	/**
	 * Crea una Stripe Checkout Session y devuelve la URL de pago.
	 *
	 * @param string $tenant_id   ID del tenant que realiza el pago.
	 * @param string $plan        Plan a contratar: 'basic', 'pro' o 'lifetime'.
	 * @param string $success_url URL de redirección tras pago exitoso.
	 * @param string $cancel_url  URL de redirección si el pago es cancelado.
	 * @return array{url:string}|WP_Error
	 */
	public function get_checkout_url( string $tenant_id, string $plan = 'basic', string $success_url = '', string $cancel_url = '' ): array|WP_Error {
		if ( ! $success_url ) {
			$success_url = add_query_arg(
				[
					'page'    => 'waxap',
					'tab'     => 'connection',
					'payment' => 'success',
				],
				admin_url( 'admin.php' )
			);
		}
		if ( ! $cancel_url ) {
			$cancel_url = add_query_arg(
				[
					'page'    => 'waxap',
					'tab'     => 'connection',
					'payment' => 'cancelled',
				],
				admin_url( 'admin.php' )
			);
		}
		return $this->request(
			'POST',
			'/v1/billing/checkout',
			[
				'tenantId'   => $tenant_id,
				'plan'       => $plan,
				'successUrl' => $success_url,
				'cancelUrl'  => $cancel_url,
			]
		);
	}

	/**
	 * Devuelve el uso mensual y estado de suscripción del tenant.
	 *
	 * @return array{status:string,used:int,quota:int,quotaResetAt:string|null}|WP_Error
	 */
	public function get_usage(): array|WP_Error {
		return $this->request( 'GET', '/v1/billing/usage', auth: true );
	}

	/**
	 * Obtiene la URL del portal de cliente Stripe para gestionar la suscripción.
	 *
	 * @param string $return_url URL de retorno tras salir del portal (opcional).
	 * @return array{url:string}|WP_Error
	 */
	public function get_billing_portal_url( string $return_url = '' ): array|WP_Error {
		$body = $return_url ? [ 'returnUrl' => $return_url ] : [];
		return $this->request( 'POST', '/v1/billing/portal', $body, auth: true );
	}

	/**
	 * Inicia sesión con email y contraseña y devuelve las credenciales del tenant.
	 *
	 * @param string $email    Email del administrador.
	 * @param string $password Contraseña de la cuenta.
	 * @return array{tenantId:string,apiKey:string,hmacSecret:string}|WP_Error
	 */
	public function login( string $email, string $password ): array|WP_Error {
		return $this->request(
			'POST',
			'/v1/auth/login',
			[
				'email'    => $email,
				'password' => $password,
			]
		);
	}

	/**
	 * Devuelve el historial de mensajes WhatsApp enviados por este tenant.
	 *
	 * @param int $limit  Número máximo de registros a devolver.
	 * @param int $offset Desplazamiento para paginación.
	 * @return array{data: array<int,array<string,mixed>>, total: int, limit: int, offset: int}|WP_Error
	 */
	public function get_message_log( int $limit = 20, int $offset = 0 ): array|WP_Error {
		return $this->request(
			'GET',
			'/v1/messages',
			query: [
				'limit'  => (string) $limit,
				'offset' => (string) $offset,
			],
			auth: true
		);
	}

	/**
	 * Devuelve los contadores de envío agregados del tenant: totales por estado
	 * y desglose de los omitidos por motivo (skipReason).
	 *
	 * @param int $days Ventana temporal en días (1-365).
	 * @return array{windowDays:int, totals:array<string,int>, skipReasons:array<string,int>}|WP_Error
	 */
	public function get_message_stats( int $days = 30 ): array|WP_Error {
		return $this->request(
			'GET',
			'/v1/messages/stats',
			query: [
				'days' => (string) $days,
			],
			auth: true
		);
	}

	/**
	 * Lista las conversaciones WhatsApp de la bandeja de entrada del tenant.
	 *
	 * @param int $limit  Número máximo de conversaciones a devolver.
	 * @param int $offset Desplazamiento para paginación.
	 * @return array{data: array<int,array<string,mixed>>, total: int, limit: int, offset: int}|WP_Error
	 */
	public function get_inbox_conversations( int $limit = 20, int $offset = 0 ): array|WP_Error {
		return $this->request(
			'GET',
			'/v1/inbox/conversations',
			query: [
				'limit'  => (string) $limit,
				'offset' => (string) $offset,
			],
			auth: true
		);
	}

	/**
	 * Devuelve el hilo de mensajes con un número de teléfono.
	 *
	 * @param string $phone  Número de teléfono sin prefijo + (solo dígitos).
	 * @param int    $limit  Número máximo de mensajes a devolver.
	 * @param int    $offset Desplazamiento para paginación.
	 * @return array{data: array<int,array<string,mixed>>, total: int, limit: int, offset: int}|WP_Error
	 */
	public function get_inbox_thread( string $phone, int $limit = 50, int $offset = 0 ): array|WP_Error {
		return $this->request(
			'GET',
			'/v1/inbox/conversations/' . rawurlencode( $phone ) . '/messages',
			query: [
				'limit'  => (string) $limit,
				'offset' => (string) $offset,
			],
			auth: true
		);
	}

	/**
	 * Envía un mensaje WhatsApp desde la bandeja de entrada.
	 *
	 * @param string $phone Número de teléfono destino (solo dígitos).
	 * @param string $text  Texto del mensaje.
	 * @return array<string,mixed>|WP_Error
	 */
	public function send_inbox_message( string $phone, string $text ): array|WP_Error {
		return $this->request(
			'POST',
			'/v1/inbox/conversations/' . rawurlencode( $phone ) . '/send',
			[ 'text' => $text ],
			auth: true
		);
	}

	/**
	 * Marca una conversación como leída (resetea unreadCount).
	 *
	 * @param string $phone Número de teléfono de la conversación.
	 * @return true|WP_Error
	 */
	public function mark_inbox_read( string $phone ): true|WP_Error {
		$result = $this->request(
			'POST',
			'/v1/inbox/conversations/' . rawurlencode( $phone ) . '/read',
			auth: true
		);
		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Envía un evento de cambio de estado de pedido al wrapper, firmado con HMAC.
	 *
	 * @param array<string,mixed> $payload Datos del evento a enviar.
	 * @return true|WP_Error
	 */
	public function send_event( array $payload ): true|WP_Error {
		$secret    = Settings::get( 'hmac_secret' );
		$timestamp = (string) time();
		$body      = (string) wp_json_encode( $payload );
		$signature = 'sha256=' . hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );

		$result = $this->request(
			'POST',
			'/v1/events',
			$payload,
			extra_headers: [
				'x-tenant-id' => $this->tenant_id,
				'x-timestamp' => $timestamp,
				'x-signature' => $signature,
			]
		);

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Realiza una petición HTTP a la API del wrapper.
	 *
	 * @param string               $method        Método HTTP (GET, POST, DELETE…).
	 * @param string               $path          Ruta del endpoint (p.ej. '/v1/sessions').
	 * @param array<string,mixed>  $body          Cuerpo de la petición (se serializa como JSON).
	 * @param bool                 $auth          Si es true, adjunta las cabeceras de autenticación.
	 * @param array<string,string> $extra_headers Cabeceras adicionales a incluir en la petición.
	 * @param array<string,string> $query         Parámetros de query string para peticiones GET.
	 * @return array<string,mixed>|WP_Error
	 */
	private function request(
		string $method,
		string $path,
		array $body = [],
		bool $auth = false,
		array $extra_headers = [],
		array $query = [],
	): array|WP_Error {
		$url = $this->base_url . $path;
		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}
		$args = [
			'method'  => $method,
			'timeout' => 10,
			'headers' => [ 'Content-Type' => 'application/json' ],
		];

		if ( $auth ) {
			$args['headers']['x-tenant-id'] = $this->tenant_id;
			$args['headers']['x-api-key']   = $this->api_key;
		}

		if ( ! empty( $extra_headers ) ) {
			$args['headers'] = array_merge( $args['headers'], $extra_headers );
		}

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$message = is_array( $data ) ? ( $data['message'] ?? 'Error desconocido' ) : 'Error desconocido';

			// Si la API rechaza las credenciales en una request autenticada, el tenant
			// fue cancelado/desactivado desde el servidor. Limpiamos las credenciales locales.
			if ( $auth && 401 === $code && Settings::is_connected() ) {
				Settings::disconnect();
			}

			return new WP_Error( 'wrapper_error', $message, [ 'status' => $code ] );
		}

		return is_array( $data ) ? $data : [];
	}
}
