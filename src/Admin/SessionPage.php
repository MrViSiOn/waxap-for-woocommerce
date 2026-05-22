<?php
/**
 * Página "Número WhatsApp" — vinculación QR y estado de sesión.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Admin;

use WaNotifier\Settings;

final class SessionPage {

    public function render(): void {
        if ( ! Settings::is_connected() ) {
            $this->render_register_form();
        } elseif ( ! Settings::has_session() ) {
            $this->render_link_button();
        } else {
            $this->render_session_status();
        }

        $this->render_modal();
    }

    private function render_register_form(): void {
        $wrapper_url = Settings::get( 'wrapper_url' );
        ?>
        <h2><?php esc_html_e( 'Conecta tu tienda con WA Notifier', 'wa-notifier' ); ?></h2>
        <p><?php esc_html_e( 'Introduce la URL del servidor y crea tu cuenta para empezar.', 'wa-notifier' ); ?></p>

        <form id="wa-notifier-register-form" method="post">
            <?php wp_nonce_field( 'wa_notifier_register', 'wa_notifier_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="wan-wrapper-url"><?php esc_html_e( 'URL del servidor', 'wa-notifier' ); ?></label>
                    </th>
                    <td>
                        <input type="url" id="wan-wrapper-url" name="wrapper_url"
                               value="<?php echo esc_attr( $wrapper_url ); ?>"
                               class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'Ejemplo: http://localhost:3000 o https://api.tudominio.com', 'wa-notifier' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wan-email"><?php esc_html_e( 'Email', 'wa-notifier' ); ?></label>
                    </th>
                    <td>
                        <input type="email" id="wan-email" name="email" class="regular-text" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wan-password"><?php esc_html_e( 'Contraseña', 'wa-notifier' ); ?></label>
                    </th>
                    <td>
                        <input type="password" id="wan-password" name="password" class="regular-text" required minlength="8" />
                    </td>
                </tr>
            </table>

            <p id="wa-notifier-register-error" class="notice notice-error" style="display:none;padding:8px 12px;"></p>

            <p class="submit">
                <button type="submit" class="button button-primary" id="wa-notifier-register-btn">
                    <?php esc_html_e( 'Crear cuenta y conectar', 'wa-notifier' ); ?>
                </button>
                <span class="wa-notifier-spinner spinner" style="float:none;margin:4px 8px;"></span>
            </p>
        </form>
        <?php
    }

    private function render_link_button(): void {
        ?>
        <h2><?php esc_html_e( 'Vincular número WhatsApp', 'wa-notifier' ); ?></h2>
        <p><?php esc_html_e( 'Tu tienda está conectada al servidor. Ahora vincula tu número WhatsApp escaneando un QR.', 'wa-notifier' ); ?></p>
        <p>
            <button type="button" class="button button-primary" id="wa-notifier-link-btn">
                📱 <?php esc_html_e( 'Vincular WhatsApp', 'wa-notifier' ); ?>
            </button>
            <span class="wa-notifier-spinner spinner" style="float:none;margin:4px 8px;"></span>
        </p>
        <p id="wa-notifier-link-error" class="notice notice-error" style="display:none;padding:8px 12px;"></p>
        <?php
    }

    private function render_session_status(): void {
        ?>
        <h2><?php esc_html_e( 'Estado de la sesión WhatsApp', 'wa-notifier' ); ?></h2>
        <div id="wa-notifier-status-wrap">
            <p>
                <span class="wa-notifier-status-dot"></span>
                <span id="wa-notifier-status-text"><?php esc_html_e( 'Comprobando…', 'wa-notifier' ); ?></span>
            </p>
        </div>
        <p>
            <button type="button" class="button button-primary" id="wa-notifier-link-btn" style="display:none;">
                📱 <?php esc_html_e( 'Volver a vincular', 'wa-notifier' ); ?>
            </button>
            <button type="button" class="button" id="wa-notifier-unlink-btn">
                <?php esc_html_e( 'Desvincular', 'wa-notifier' ); ?>
            </button>
        </p>
        <p id="wa-notifier-link-error" class="notice notice-error" style="display:none;padding:8px 12px;"></p>

        <div id="wa-notifier-test-wrap" style="display:none;">
            <hr />
            <h3><?php esc_html_e( 'Enviar mensaje de prueba', 'wa-notifier' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Verifica que la conexión funciona enviando un mensaje a cualquier número WhatsApp.', 'wa-notifier' ); ?></p>
            <form id="wa-notifier-test-form">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="wan-test-phone"><?php esc_html_e( 'Número de teléfono', 'wa-notifier' ); ?></label>
                        </th>
                        <td>
                            <input type="tel" id="wan-test-phone" name="to"
                                   placeholder="+34612345678" class="regular-text" required />
                            <p class="description"><?php esc_html_e( 'Formato internacional: +34612345678', 'wa-notifier' ); ?></p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-secondary" id="wa-notifier-test-btn">
                        💬 <?php esc_html_e( 'Enviar mensaje de prueba', 'wa-notifier' ); ?>
                    </button>
                    <span class="wa-notifier-spinner spinner" style="float:none;margin:4px 8px;"></span>
                </p>
            </form>
            <p id="wa-notifier-test-result" class="notice" style="display:none;padding:8px 12px;"></p>
        </div>
        <?php
    }

    private function render_modal(): void {
        ?>
        <div id="wa-notifier-modal" aria-hidden="true" role="dialog" aria-modal="true"
             aria-labelledby="wa-notifier-modal-title" style="display:none;">
            <div id="wa-notifier-modal-overlay">
                <div id="wa-notifier-modal-content" class="wa-notifier-modal-box">
                    <button type="button" id="wa-notifier-modal-close" aria-label="<?php esc_attr_e( 'Cerrar', 'wa-notifier' ); ?>">&times;</button>
                    <h2 id="wa-notifier-modal-title"><?php esc_html_e( 'Escanea el QR con WhatsApp', 'wa-notifier' ); ?></h2>
                    <div id="wa-notifier-qr-wrap">
                        <img id="wa-notifier-qr-img" src="" alt="<?php esc_attr_e( 'QR WhatsApp', 'wa-notifier' ); ?>" />
                        <p id="wa-notifier-qr-loading"><?php esc_html_e( 'Generando QR…', 'wa-notifier' ); ?></p>
                    </div>
                    <p class="description">
                        <?php esc_html_e( 'WhatsApp → Dispositivos vinculados → Vincular un dispositivo', 'wa-notifier' ); ?>
                    </p>
                    <p id="wa-notifier-modal-error" class="notice notice-error" style="display:none;padding:8px 12px;"></p>
                </div>
            </div>
        </div>
        <?php
    }
}
