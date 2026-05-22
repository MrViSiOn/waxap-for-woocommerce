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
use WaNotifier\Ajax\SessionAjax;
use WaNotifier\Emails\OrderEmails;
use WaNotifier\Orders\OrderEvents;

final class Plugin {

    public function boot(): void {
        if ( is_admin() ) {
            add_action( 'admin_menu', [ new AdminMenu(), 'register' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        }

        $ajax = new SessionAjax();
        $ajax->register();

        add_action( 'woocommerce_loaded', function () {
            ( new OrderEvents() )->register();
            ( new OrderEmails() )->register();
        } );
    }

    public function enqueue_admin_assets( string $hook ): void {
        // Solo en la página de WA Notifier
        if ( 'woocommerce_page_wa-notifier' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'wa-notifier-admin',
            WA_NOTIFIER_URL . 'assets/css/admin-session.css',
            [],
            WA_NOTIFIER_VERSION,
        );

        wp_enqueue_script(
            'wa-notifier-admin',
            WA_NOTIFIER_URL . 'assets/js/admin-session.js',
            [ 'jquery' ],
            WA_NOTIFIER_VERSION,
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
    }
}
