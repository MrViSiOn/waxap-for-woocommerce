<?php
/**
 * Vista: tab Conexión — credenciales del servidor Waxap.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 *
 * @var bool   $is_connected
 * @var string $wrapper_url
 * @var string $api_key
 * @var string $tenant_id
 */

declare(strict_types=1);

if ( isset( $_GET['updated'] ) ) : ?>
    <div class="waxap-updated"><?php esc_html_e( 'Configuración guardada.', 'wa-notifier' ); ?></div>
<?php elseif ( isset( $_GET['disconnected'] ) ) : ?>
    <div class="waxap-notice-warning"><?php esc_html_e( 'Cuenta desconectada. Introduce tus credenciales para volver a conectar.', 'wa-notifier' ); ?></div>
<?php endif; ?>

<?php if ( $is_connected ) : ?>

    <div class="waxap-section-header">
        <h2>
            <?php esc_html_e( 'Servidor Waxap', 'wa-notifier' ); ?>
            <span class="wan-connection-badge wan-connection-badge--ok"><?php esc_html_e( 'Conectado', 'wa-notifier' ); ?></span>
        </h2>
        <p><?php esc_html_e( 'Tu tienda está conectada al servidor. Puedes actualizar tu API Key si es necesario.', 'wa-notifier' ); ?></p>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
        <input type="hidden" name="action" value="wa_notifier_save_connection">
        <?php wp_nonce_field( 'wa_notifier_save_connection' ); ?>

        <div class="wan-field-rows" style="max-width:620px;">
            <div class="wan-field-row">
                <label for="wan-api-key" class="wan-field-label">
                    <?php esc_html_e( 'API Key', 'wa-notifier' ); ?>
                </label>
                <input type="text" id="wan-api-key" name="api_key"
                       value="<?php echo esc_attr( $api_key ); ?>"
                       class="regular-text wan-field-input" autocomplete="off">
            </div>

            <?php if ( $tenant_id ) : ?>
            <div class="wan-field-row">
                <label class="wan-field-label"><?php esc_html_e( 'Tenant ID', 'wa-notifier' ); ?></label>
                <input type="text" value="<?php echo esc_attr( $tenant_id ); ?>"
                       class="regular-text wan-field-input" autocomplete="off" readonly>
                <input type="hidden" name="tenant_id" value="<?php echo esc_attr( $tenant_id ); ?>">
            </div>
            <?php endif; ?>
        </div>

        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <button type="submit" class="button button-primary waxap-btn-primary">
                <?php esc_html_e( 'Guardar cambios', 'wa-notifier' ); ?>
            </button>
        </div>
    </form>

    <div class="wan-danger-zone">
        <h3><?php esc_html_e( 'Desconectar', 'wa-notifier' ); ?></h3>
        <p><?php esc_html_e( 'Elimina las credenciales y desvincula la sesión WhatsApp. La tienda dejará de enviar notificaciones.', 'wa-notifier' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
              onsubmit="return confirm('<?php esc_attr_e( '¿Seguro que quieres desconectar? Se borrarán las credenciales y la sesión WhatsApp.', 'wa-notifier' ); ?>')">
            <input type="hidden" name="action" value="wa_notifier_disconnect">
            <?php wp_nonce_field( 'wa_notifier_disconnect' ); ?>
            <button type="submit" class="button wan-btn-danger">
                <?php esc_html_e( 'Desconectar cuenta', 'wa-notifier' ); ?>
            </button>
        </form>
    </div>

<?php else : ?>

    <div class="waxap-section-header">
        <h2>
            <?php esc_html_e( 'Servidor Waxap', 'wa-notifier' ); ?>
            <span class="wan-connection-badge wan-connection-badge--off"><?php esc_html_e( 'No conectado', 'wa-notifier' ); ?></span>
        </h2>
        <p><?php esc_html_e( 'Introduce tu API Key para conectar la tienda con Waxap.', 'wa-notifier' ); ?></p>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
        <input type="hidden" name="action" value="wa_notifier_save_connection">
        <?php wp_nonce_field( 'wa_notifier_save_connection' ); ?>

        <div class="wan-field-rows" style="max-width:620px;">
            <div class="wan-field-row">
                <label for="wan-api-key" class="wan-field-label">
                    <?php esc_html_e( 'API Key', 'wa-notifier' ); ?>
                    <span class="wan-field-hint"><?php esc_html_e( 'La encontrarás en tu panel de Waxap.', 'wa-notifier' ); ?></span>
                </label>
                <input type="text" id="wan-api-key" name="api_key"
                       value="" placeholder="wax_••••••••••••••••"
                       class="regular-text wan-field-input" autocomplete="off" required>
            </div>

            <div class="wan-field-row">
                <label for="wan-tenant-id" class="wan-field-label">
                    <?php esc_html_e( 'Tenant ID', 'wa-notifier' ); ?>
                    <span class="wan-field-hint"><?php esc_html_e( 'Identificador de tu cuenta en el servidor.', 'wa-notifier' ); ?></span>
                </label>
                <input type="text" id="wan-tenant-id" name="tenant_id"
                       value="" class="regular-text wan-field-input" autocomplete="off">
            </div>
        </div>

        <button type="submit" class="button button-primary waxap-btn-primary">
            <?php esc_html_e( 'Conectar', 'wa-notifier' ); ?>
        </button>
    </form>

<?php endif; ?>
