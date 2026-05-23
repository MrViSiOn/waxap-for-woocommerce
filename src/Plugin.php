<?php
/**
 * Plugin bootstrap.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier;

use WaNotifier\Admin\AdminMenu;
use WaNotifier\Admin\Onboarding;
use WaNotifier\Ajax\SessionAjax;
use WaNotifier\Checkout\CheckoutOptIn;
use WaNotifier\Emails\OrderEmails;
use WaNotifier\Orders\OrderEvents;

final class Plugin {

    public function boot(): void {
        if ( is_admin() ) {
            $admin_menu = new AdminMenu();
            add_action( 'admin_menu', [ $admin_menu, 'register' ] );
            add_action( 'admin_init', [ $admin_menu, 'register_form_handlers' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        }

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_checkout_assets' ] );

        $ajax = new SessionAjax();
        $ajax->register();

        $onboarding = new Onboarding();
        $onboarding->register();

        add_action( 'woocommerce_loaded', function () {
            ( new OrderEvents() )->register();
            ( new OrderEmails() )->register();
            ( new CheckoutOptIn() )->register();
        } );
    }

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

    public function enqueue_admin_assets( string $hook ): void {
        // Solo en la página de WA Notifier
        if ( 'woocommerce_page_wa-notifier' !== $hook ) {
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
                'nonce'          => wp_create_nonce( 'wa_notifier_ajax' ),
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'confirmUnlink'  => __( '¿Seguro que quieres desvincular el número WhatsApp?', 'wa-notifier' ),
                'hasSession'     => Settings::has_session() ? '1' : '0',
                'sessionId'      => Settings::get( 'session_id' ),
            ],
        );

        // Wizard de onboarding (solo cuando no está conectado)
        if ( ! Settings::is_connected() ) {
            wp_enqueue_script(
                'wa-notifier-onboarding',
                WA_NOTIFIER_URL . 'assets/js/admin-onboarding.js',
                [ 'jquery' ],
                $ver,
                true,
            );

            wp_localize_script(
                'wa-notifier-onboarding',
                'waxapOnboarding',
                [
                    'nonce'   => wp_create_nonce( 'wa_notifier_ajax' ),
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'step'    => Settings::get( 'tenant_id' ) ? '2' : '1',
                ],
            );
        }
    }
}
