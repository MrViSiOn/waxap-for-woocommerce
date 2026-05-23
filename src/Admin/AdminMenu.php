<?php
/**
 * Admin menu registration, tab rendering y handlers de formularios de settings.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Admin;

use WaNotifier\Settings;

final class AdminMenu {

    private const SLUG = 'wa-notifier';

    /** @var array<string,string> */
    private const TABS = [
        'connection'    => 'Conexión',
        'phone'         => 'Número WhatsApp',
        'notifications' => 'Notificaciones',
        'email'         => 'Email branding',
    ];

    public function register(): void {
        add_submenu_page(
            'woocommerce',
            __( 'Waxap', 'wa-notifier' ),
            __( 'Waxap', 'wa-notifier' ),
            'manage_woocommerce',
            self::SLUG,
            [ $this, 'render' ],
        );

        add_action( 'admin_post_wa_notifier_save_notifications', [ $this, 'handle_save_notifications' ] );
        add_action( 'admin_post_wa_notifier_save_email',         [ $this, 'handle_save_email' ] );
        add_action( 'admin_post_wa_notifier_save_connection',    [ $this, 'handle_save_connection' ] );
        add_action( 'admin_post_wa_notifier_disconnect',         [ $this, 'handle_disconnect' ] );
    }

    public function render(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $raw     = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'connection';
        $current = array_key_exists( $raw, self::TABS ) ? $raw : 'connection';
        ?>
        <div class="wrap waxap-admin-wrap">

            <header class="waxap-header">
                <span class="waxap-logo-mark">W</span>
                <div class="waxap-header-text">
                    <h1><?php esc_html_e( 'Waxap', 'wa-notifier' ); ?></h1>
                    <p><?php esc_html_e( 'Notificaciones WhatsApp para WooCommerce', 'wa-notifier' ); ?></p>
                </div>
            </header>

            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <?php foreach ( self::TABS as $slug => $label ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&tab=' . $slug ) ); ?>"
                       class="nav-tab <?php echo $current === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( __( $label, 'wa-notifier' ) ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="waxap-tab-content">
                <?php $this->render_tab( $current ); ?>
            </div>

        </div>
        <?php
    }

    private function render_tab( string $tab ): void {
        if ( $tab === 'connection' ) {
            $is_connected = Settings::is_connected();
            $wrapper_url  = Settings::get( 'wrapper_url' );
            $api_key      = Settings::get( 'api_key' );
            $tenant_id    = Settings::get( 'tenant_id' );
            include __DIR__ . '/views/tab-connection.php';
            return;
        }

        if ( $tab === 'notifications' ) {
            $enabled_statuses = array_filter(
                explode( ',', Settings::get( 'notify_statuses' ) )
            );
            $templates = [];
            foreach ( [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ] as $s ) {
                $templates[ $s ] = Settings::get( 'template_' . $s );
            }
            include __DIR__ . '/views/tab-notifications.php';
            return;
        }

        if ( $tab === 'email' ) {
            $email_enabled  = '1' === Settings::get( 'email_button_enabled' );
            $email_text     = Settings::get( 'email_button_text' );
            $email_prefill  = Settings::get( 'email_button_prefill' );
            include __DIR__ . '/views/tab-email.php';
            return;
        }

        $view = __DIR__ . '/views/tab-' . $tab . '.php';
        if ( file_exists( $view ) ) {
            include $view;
        }
    }

    public function handle_save_notifications(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'No autorizado.' );
        }

        check_admin_referer( 'wa_notifier_save_notifications' );

        $valid    = [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ];
        $posted   = isset( $_POST['wan_notify_statuses'] ) && is_array( $_POST['wan_notify_statuses'] )
            ? $_POST['wan_notify_statuses']
            : [];
        $selected = array_values( array_filter(
            array_map( 'sanitize_key', $posted ),
            fn( string $s ) => in_array( $s, $valid, true )
        ) );

        Settings::set( 'notify_statuses', implode( ',', $selected ) );

        // Save message templates
        $valid_statuses    = [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ];
        $posted_templates  = isset( $_POST['wan_templates'] ) && is_array( $_POST['wan_templates'] )
            ? $_POST['wan_templates']
            : [];
        foreach ( $valid_statuses as $s ) {
            $tpl = sanitize_textarea_field( (string) ( $posted_templates[ $s ] ?? '' ) );
            Settings::set( 'template_' . $s, $tpl );
        }

        wp_safe_redirect( add_query_arg( [
            'page'    => self::SLUG,
            'tab'     => 'notifications',
            'updated' => '1',
        ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_save_email(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'No autorizado.' );
        }

        check_admin_referer( 'wa_notifier_save_email' );

        Settings::set( 'email_button_enabled', isset( $_POST['email_button_enabled'] ) ? '1' : '0' );
        Settings::set( 'email_button_text', sanitize_text_field( (string) ( $_POST['email_button_text'] ?? '' ) ) );
        Settings::set( 'email_button_prefill', sanitize_textarea_field( (string) ( $_POST['email_button_prefill'] ?? '' ) ) );

        wp_safe_redirect( add_query_arg( [
            'page'    => self::SLUG,
            'tab'     => 'email',
            'updated' => '1',
        ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_save_connection(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'No autorizado.' );
        }

        check_admin_referer( 'wa_notifier_save_connection' );

        $wrapper_url = esc_url_raw( (string) ( $_POST['wrapper_url'] ?? '' ) );
        $api_key     = sanitize_text_field( (string) ( $_POST['api_key'] ?? '' ) );
        $tenant_id   = sanitize_text_field( (string) ( $_POST['tenant_id'] ?? '' ) );

        if ( $wrapper_url ) {
            Settings::set( 'wrapper_url', $wrapper_url );
        }
        Settings::set( 'api_key', $api_key );
        Settings::set( 'tenant_id', $tenant_id );

        wp_safe_redirect( add_query_arg( [
            'page'    => self::SLUG,
            'tab'     => 'connection',
            'updated' => '1',
        ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_disconnect(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'No autorizado.' );
        }

        check_admin_referer( 'wa_notifier_disconnect' );

        foreach ( [ 'api_key', 'tenant_id', 'session_id', 'hmac_secret', 'phone_number' ] as $key ) {
            Settings::set( $key, '' );
        }

        wp_safe_redirect( add_query_arg( [
            'page'         => self::SLUG,
            'tab'          => 'connection',
            'disconnected' => '1',
        ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
