<?php
/**
 * Admin menu registration and tab rendering.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Admin;

final class AdminMenu {

    private const SLUG = 'wa-notifier';

    /** @var array<string,string> */
    private const TABS = [
        'connection' => 'Conexión',
        'phone'      => 'Número WhatsApp',
        'templates'  => 'Plantillas',
        'email'      => 'Email branding',
    ];

    public function register(): void {
        add_submenu_page(
            'woocommerce',
            __( 'WA Notifier', 'wa-notifier' ),
            __( 'WA Notifier', 'wa-notifier' ),
            'manage_woocommerce',
            self::SLUG,
            [ $this, 'render' ],
        );
    }

    public function render(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $raw     = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'connection';
        $current = array_key_exists( $raw, self::TABS ) ? $raw : 'connection';
        ?>
        <div class="wrap woocommerce">
            <h1><?php esc_html_e( 'WA Notifier', 'wa-notifier' ); ?></h1>

            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <?php foreach ( self::TABS as $slug => $label ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&tab=' . $slug ) ); ?>"
                       class="nav-tab <?php echo $current === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( __( $label, 'wa-notifier' ) ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="wa-notifier-tab-content" style="margin-top:1.5em;">
                <?php $this->render_tab( $current ); ?>
            </div>
        </div>
        <?php
    }

    private function render_tab( string $tab ): void {
        $view = __DIR__ . '/views/tab-' . $tab . '.php';
        if ( file_exists( $view ) ) {
            include $view;
        }
    }
}
