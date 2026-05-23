<?php
/**
 * Manejadores AJAX para el wizard de onboarding (registro + activación Stripe).
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Admin;

use WaNotifier\Api\WrapperClient;
use WaNotifier\Settings;

final class Onboarding {

    public function register(): void {
        add_action( 'wp_ajax_wan_onboarding_register',     [ $this, 'ajax_register' ] );
        add_action( 'wp_ajax_wan_onboarding_poll',         [ $this, 'ajax_poll_activation' ] );
        add_action( 'wp_ajax_wan_onboarding_checkout_url', [ $this, 'ajax_checkout_url' ] );
    }

    public function ajax_register(): void {
        $this->verify_nonce();

        $email    = sanitize_email( (string) ( $_POST['email'] ?? '' ) );
        $password = (string) ( $_POST['password'] ?? '' );

        if ( ! $email || strlen( $password ) < 8 ) {
            wp_send_json_error( [ 'message' => 'Introduce un email válido y una contraseña de al menos 8 caracteres.' ] );
        }

        $client = new WrapperClient();
        $result = $client->register( $email, $password );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        $tenant_id = (string) ( $result['tenantId'] ?? '' );
        if ( ! $tenant_id ) {
            wp_send_json_error( [ 'message' => 'El servidor no devolvió un identificador de cuenta.' ] );
        }

        Settings::set( 'tenant_id', $tenant_id );

        wp_send_json_success( [ 'tenantId' => $tenant_id ] );
    }

    public function ajax_checkout_url(): void {
        $this->verify_nonce();

        $tenant_id = Settings::get( 'tenant_id' );
        if ( ! $tenant_id ) {
            wp_send_json_error( [ 'message' => 'No hay cuenta registrada. Completa el paso anterior.' ] );
        }

        $client   = new WrapperClient();
        $result   = $client->get_checkout_url( $tenant_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        $url = (string) ( $result['url'] ?? '' );
        if ( ! $url ) {
            wp_send_json_error( [ 'message' => 'No se pudo obtener el enlace de pago. Contacta con soporte.' ] );
        }

        wp_send_json_success( [ 'url' => $url ] );
    }

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

        if ( $status === 'active' ) {
            Settings::set( 'api_key',     (string) ( $result['apiKey'] ?? '' ) );
            Settings::set( 'hmac_secret', (string) ( $result['hmacSecret'] ?? '' ) );
        }

        wp_send_json_success( [ 'status' => $status ] );
    }

    private function verify_nonce(): void {
        if ( ! check_ajax_referer( 'wa_notifier_ajax', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Sesión expirada. Recarga la página.' ], 403 );
        }
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'No autorizado.' ], 403 );
        }
    }
}
