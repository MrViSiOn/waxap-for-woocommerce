<?php
/**
 * Admin menu registration, tab rendering y handlers de formularios de settings.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Admin;

use WaNotifier\Api\WrapperClient;
use WaNotifier\Settings;

final class AdminMenu {

    private const SLUG = 'wa-notifier';

    /** @var array<string,string> */
    private const TABS = [
        'connection'    => 'Conexión',
        'phone'         => 'Número WhatsApp',
        'notifications' => 'Notificaciones',
        'email'         => 'Email branding',
        'history'       => 'Historial',
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
    }

    public function register_form_handlers(): void {
        add_action( 'admin_post_wa_notifier_save_notifications', [ $this, 'handle_save_notifications' ] );
        add_action( 'admin_post_wa_notifier_save_email',         [ $this, 'handle_save_email' ] );
        add_action( 'admin_post_wa_notifier_save_connection',    [ $this, 'handle_save_connection' ] );
        add_action( 'admin_post_wa_notifier_disconnect',         [ $this, 'handle_disconnect' ] );
        add_action( 'admin_post_wa_notifier_login',              [ $this, 'handle_login' ] );
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
                <?php
                $is_connected = Settings::is_connected();
                $visible_tabs = $is_connected ? self::TABS : [ 'connection' => self::TABS['connection'] ];
                foreach ( $visible_tabs as $slug => $label ) :
                ?>
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
        if ( $tab !== 'connection' && ! Settings::is_connected() ) {
            wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&tab=connection' ) );
            exit;
        }

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
            $status_meta = [
                'processing' => [ 'color' => '#2271b1', 'desc' => 'El cliente completó el pago. El pedido está en preparación.' ],
                'completed'  => [ 'color' => '#25d366', 'desc' => 'El pedido ha sido entregado o marcado como completado.' ],
                'on-hold'    => [ 'color' => '#f59e0b', 'desc' => 'Pago pendiente de confirmación (ej. transferencia bancaria).' ],
                'cancelled'  => [ 'color' => '#ef4444', 'desc' => 'El pedido fue cancelado por el cliente o la tienda.' ],
                'refunded'   => [ 'color' => '#8b5cf6', 'desc' => 'El importe fue devuelto al cliente.' ],
                'pending'    => [ 'color' => '#9ca3af', 'desc' => 'El pedido existe pero el cliente aún no ha pagado.' ],
                'failed'     => [ 'color' => '#6b7280', 'desc' => 'El pago no pudo completarse.' ],
            ];
            $statuses  = [];
            $templates = [];
            foreach ( wc_get_order_statuses() as $wc_key => $label ) {
                $key              = ltrim( $wc_key, 'wc-' );
                $meta             = $status_meta[ $key ] ?? [];
                $statuses[ $key ] = [
                    'label' => $label,
                    'color' => $meta['color'] ?? '#6b7280',
                    'desc'  => $meta['desc'] ?? '',
                ];
                $templates[ $key ] = Settings::get( 'template_' . $key );
            }
            $country_code = Settings::get( 'phone_country_code' ) ?: '34';
            include __DIR__ . '/views/tab-notifications.php';
            return;
        }

        if ( $tab === 'email' ) {
            $email_enabled  = '1' === Settings::get( 'email_button_enabled' );
            $email_text     = Settings::get( 'email_button_text' );
            $email_prefill  = Settings::get( 'email_button_prefill' );
            $has_phone      = '' !== Settings::get( 'phone_number' );
            include __DIR__ . '/views/tab-email.php';
            return;
        }

        if ( $tab === 'history' ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $page   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
            $limit  = 20;
            $offset = ( $page - 1 ) * $limit;

            $client  = new WrapperClient();
            $result  = $client->get_message_log( $limit, $offset );
            $log     = is_wp_error( $result ) ? null : $result;
            $error   = is_wp_error( $result ) ? $result->get_error_message() : null;
            include __DIR__ . '/views/tab-history.php';
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

        $valid    = array_map( fn( string $k ) => ltrim( $k, 'wc-' ), array_keys( wc_get_order_statuses() ) );
        $posted   = isset( $_POST['wan_notify_statuses'] ) && is_array( $_POST['wan_notify_statuses'] )
            ? $_POST['wan_notify_statuses']
            : [];
        $selected = array_values( array_filter(
            array_map( 'sanitize_key', $posted ),
            fn( string $s ) => in_array( $s, $valid, true )
        ) );

        Settings::set( 'notify_statuses', implode( ',', $selected ) );

        $country_code = preg_replace( '/\D/', '', (string) ( $_POST['phone_country_code'] ?? '34' ) );
        Settings::set( 'phone_country_code', $country_code ?: '34' );

        // Save message templates
        $posted_templates = isset( $_POST['wan_templates'] ) && is_array( $_POST['wan_templates'] )
            ? $_POST['wan_templates']
            : [];
        foreach ( $valid as $s ) {
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

    public function handle_login(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'No autorizado.' );
        }

        check_admin_referer( 'wa_notifier_login' );

        $email    = sanitize_email( (string) ( $_POST['email'] ?? '' ) );
        $password = (string) ( $_POST['password'] ?? '' );

        if ( ! $email || ! $password ) {
            wp_safe_redirect( add_query_arg( [
                'page'       => self::SLUG,
                'tab'        => 'connection',
                'login_error' => 'missing_fields',
            ], admin_url( 'admin.php' ) ) );
            exit;
        }

        $client = new WrapperClient();
        $result = $client->login( $email, $password );

        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( add_query_arg( [
                'page'        => self::SLUG,
                'tab'         => 'connection',
                'login_error' => urlencode( $result->get_error_message() ),
            ], admin_url( 'admin.php' ) ) );
            exit;
        }

        Settings::set( 'api_key',     $result['apiKey'] );
        Settings::set( 'tenant_id',   $result['tenantId'] );
        Settings::set( 'hmac_secret', $result['hmacSecret'] );

        wp_safe_redirect( add_query_arg( [
            'page'    => self::SLUG,
            'tab'     => 'phone',
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
