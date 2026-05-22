<?php
/**
 * Manejadores WP AJAX para la vinculación de sesión WhatsApp.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Ajax;

use WaNotifier\Api\WrapperClient;
use WaNotifier\Settings;

final class SessionAjax {

    public function register(): void {
        foreach ( [ 'register', 'create_session', 'poll_session', 'disconnect', 'send_test' ] as $action ) {
            add_action( "wp_ajax_wa_notifier_{$action}", [ $this, "handle_{$action}" ] );
        }
    }

    public function handle_register(): void {
        $this->verify_nonce();

        $wrapper_url = sanitize_url( (string) ( $_POST['wrapper_url'] ?? '' ) );
        $email       = sanitize_email( (string) ( $_POST['email'] ?? '' ) );
        $password    = (string) ( $_POST['password'] ?? '' );

        if ( ! $wrapper_url || ! $email || ! $password ) {
            wp_send_json_error( [ 'message' => __( 'Todos los campos son obligatorios.', 'wa-notifier' ) ] );
        }

        Settings::set( 'wrapper_url', $wrapper_url );

        $client = new WrapperClient();
        $result = $client->register( $email, $password );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        Settings::set( 'api_key', (string) ( $result['apiKey'] ?? '' ) );
        Settings::set( 'tenant_id', (string) ( $result['tenantId'] ?? '' ) );
        Settings::set( 'hmac_secret', (string) ( $result['hmacSecret'] ?? '' ) );

        wp_send_json_success();
    }

    public function handle_create_session(): void {
        $this->verify_nonce();

        $client     = new WrapperClient();
        $site_name  = sanitize_key( get_bloginfo( 'name' ) ) ?: 'tienda';
        $session    = $client->create_session( $site_name );

        if ( is_wp_error( $session ) ) {
            wp_send_json_error( [ 'message' => $session->get_error_message() ] );
        }

        $session_id = (string) ( $session['id'] ?? '' );
        Settings::set( 'session_id', $session_id );

        wp_send_json_success( [ 'sessionId' => $session_id ] );
    }

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

        // Solicitar QR si la sesión está esperando escaneo
        if ( in_array( $current_status, [ 'initializing', 'qr_ready' ], true ) ) {
            $qr_response = $client->get_qr( $session_id );
            if ( ! is_wp_error( $qr_response ) ) {
                $qr = $qr_response['qr'] ?? null;
            }
        }

        $phone = (string) ( $status['phoneNumber'] ?? '' );

        // Persistir número vinculado al alcanzar estado ready
        if ( $current_status === 'ready' && $phone ) {
            Settings::set( 'phone_number', $phone );
        }

        wp_send_json_success( [
            'status' => $current_status,
            'qr'     => $qr,
            'phone'  => $phone ?: null,
        ] );
    }

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

    public function handle_send_test(): void {
        $this->verify_nonce();

        $to = sanitize_text_field( (string) ( $_POST['to'] ?? '' ) );
        if ( ! $to ) {
            wp_send_json_error( [ 'message' => __( 'El número de teléfono es obligatorio.', 'wa-notifier' ) ] );
        }

        $session_id = Settings::get( 'session_id' );
        if ( ! $session_id ) {
            wp_send_json_error( [ 'message' => __( 'No hay sesión activa.', 'wa-notifier' ) ] );
        }

        $message = sanitize_text_field( (string) ( $_POST['message'] ?? '' ) );

        $client = new WrapperClient();
        $result = $client->send_test( $session_id, $to, $message );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( [ 'messageId' => $result['messageId'] ?? '' ] );
    }

    private function verify_nonce(): void {
        if ( ! check_ajax_referer( 'wa_notifier_ajax', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Nonce inválido.' ], 403 );
        }
    }
}
