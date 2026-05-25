<?php
/**
 * Cliente HTTP para la API del wrapper WA Notifier.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Api;

use WaNotifier\Settings;
use WP_Error;

final class WrapperClient {

    private string $base_url;
    private string $api_key;
    private string $tenant_id;

    public function __construct() {
        $this->base_url  = rtrim( Settings::get( 'wrapper_url' ), '/' );
        $this->api_key   = Settings::get( 'api_key' );
        $this->tenant_id = Settings::get( 'tenant_id' );
    }

    /**
     * Registra la tienda y devuelve apiKey + tenantId.
     *
     * @return array{apiKey:string,tenantId:string}|WP_Error
     */
    public function register( string $email, string $password ): array|WP_Error {
        return $this->request( 'POST', '/v1/auth/register', [
            'email'    => $email,
            'password' => $password,
        ] );
    }

    /**
     * Crea una sesión en el wrapper y la arranca.
     *
     * @return array{id:string,status:string,...}|WP_Error
     */
    public function create_session( string $name ): array|WP_Error {
        return $this->request( 'POST', '/v1/sessions', [ 'name' => $name ], auth: true );
    }

    /**
     * Obtiene el estado actual de la sesión.
     *
     * @return array{id:string,status:string,...}|WP_Error
     */
    public function get_session( string $session_id ): array|WP_Error {
        return $this->request( 'GET', "/v1/sessions/{$session_id}", auth: true );
    }

    /**
     * Obtiene el QR actual (para polling).
     *
     * @return array{sessionId:string,qr:string}|WP_Error
     */
    public function get_qr( string $session_id ): array|WP_Error {
        return $this->request( 'GET', "/v1/sessions/{$session_id}/qr", auth: true );
    }

    /**
     * Envía un mensaje de prueba a un número de teléfono.
     *
     * @return array{messageId:string}|WP_Error
     */
    public function send_test( string $session_id, string $to, string $message = '' ): array|WP_Error {
        $body = [ 'to' => $to ];
        if ( $message !== '' ) {
            $body['message'] = $message;
        }
        return $this->request( 'POST', "/v1/sessions/{$session_id}/send-test", $body, auth: true );
    }

    /**
     * Elimina la sesión.
     */
    public function delete_session( string $session_id ): true|WP_Error {
        $result = $this->request( 'DELETE', "/v1/sessions/{$session_id}", auth: true );
        return is_wp_error( $result ) ? $result : true;
    }

    /**
     * Consulta el estado de activación de un tenant (polling post-pago Stripe).
     *
     * @return array{status:string,apiKey?:string,hmacSecret?:string}|WP_Error
     */
    public function get_auth_status( string $tenant_id ): array|WP_Error {
        return $this->request( 'GET', "/v1/auth/status/{$tenant_id}" );
    }

    /**
     * Crea una Stripe Checkout Session y devuelve la URL de pago.
     *
     * @return array{url:string}|WP_Error
     */
    public function get_checkout_url( string $tenant_id ): array|WP_Error {
        $success_url = add_query_arg(
            [ 'page' => 'wa-notifier', 'tab' => 'connection', 'payment' => 'success' ],
            admin_url( 'admin.php' )
        );
        $cancel_url = add_query_arg(
            [ 'page' => 'wa-notifier', 'tab' => 'connection', 'payment' => 'cancelled' ],
            admin_url( 'admin.php' )
        );
        return $this->request( 'POST', '/v1/billing/checkout', [
            'tenantId'   => $tenant_id,
            'successUrl' => $success_url,
            'cancelUrl'  => $cancel_url,
        ] );
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
     * @return array{url:string}|WP_Error
     */
    public function get_billing_portal_url( string $return_url = '' ): array|WP_Error {
        $body = $return_url ? [ 'returnUrl' => $return_url ] : [];
        return $this->request( 'POST', '/v1/billing/portal', $body, auth: true );
    }

    /**
     * Inicia sesión con email y contraseña y devuelve las credenciales del tenant.
     *
     * @return array{tenantId:string,apiKey:string,hmacSecret:string}|WP_Error
     */
    public function login( string $email, string $password ): array|WP_Error {
        return $this->request( 'POST', '/v1/auth/login', [
            'email'    => $email,
            'password' => $password,
        ] );
    }

    /**
     * Devuelve el historial de mensajes WhatsApp enviados por este tenant.
     *
     * @return array{data: array<int,array<string,mixed>>, total: int, limit: int, offset: int}|WP_Error
     */
    public function get_message_log( int $limit = 20, int $offset = 0 ): array|WP_Error {
        return $this->request( 'GET', '/v1/messages', query: [
            'limit'  => (string) $limit,
            'offset' => (string) $offset,
        ], auth: true );
    }

    /**
     * Envía un evento de cambio de estado de pedido al wrapper, firmado con HMAC.
     *
     * @param array<string,mixed> $payload
     * @return true|WP_Error
     */
    public function send_event( array $payload ): true|WP_Error {
        $secret    = Settings::get( 'hmac_secret' );
        $timestamp = (string) time();
        $body      = (string) wp_json_encode( $payload );
        $signature = 'sha256=' . hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );

        $result = $this->request( 'POST', '/v1/events', $payload, extra_headers: [
            'x-tenant-id' => $this->tenant_id,
            'x-timestamp' => $timestamp,
            'x-signature' => $signature,
        ] );

        return is_wp_error( $result ) ? $result : true;
    }

    /**
     * @param array<string,mixed>  $body
     * @param array<string,string> $extra_headers
     * @param array<string,string> $query  Parámetros de query string para peticiones GET.
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
            if ( $auth && $code === 401 && Settings::is_connected() ) {
                Settings::disconnect();
            }

            return new WP_Error( 'wrapper_error', $message, [ 'status' => $code ] );
        }

        return is_array( $data ) ? $data : [];
    }
}
