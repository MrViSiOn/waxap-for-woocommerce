<?php
/**
 * Vista: tab Conexión — wizard de onboarding o estado conectado.
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

/* ============================================================
   ESTADO A: CONECTADO (tiene api_key)
   ============================================================ */
if ( $is_connected ) : ?>

    <?php if ( isset( $_GET['ob'] ) && $_GET['ob'] === 'done' ) : ?>
        <div class="waxap-updated">
            <?php esc_html_e( '¡Cuenta activada! Ahora vincula tu número de WhatsApp en el paso siguiente.', 'wa-notifier' ); ?>
        </div>
    <?php elseif ( isset( $_GET['updated'] ) ) : ?>
        <div class="waxap-updated"><?php esc_html_e( 'Configuración guardada.', 'wa-notifier' ); ?></div>
    <?php elseif ( isset( $_GET['disconnected'] ) ) : ?>
        <div class="waxap-notice-warning"><?php esc_html_e( 'Cuenta desconectada.', 'wa-notifier' ); ?></div>
    <?php endif; ?>

    <div class="waxap-section-header">
        <h2>
            <?php esc_html_e( 'Servidor Waxap', 'wa-notifier' ); ?>
            <span class="wan-connection-badge wan-connection-badge--ok"><?php esc_html_e( 'Conectado', 'wa-notifier' ); ?></span>
        </h2>
        <p><?php esc_html_e( 'Tu tienda está conectada. Puedes actualizar tu API Key si es necesario.', 'wa-notifier' ); ?></p>
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
                       class="regular-text wan-field-input" readonly>
                <input type="hidden" name="tenant_id" value="<?php echo esc_attr( $tenant_id ); ?>">
            </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="button button-primary waxap-btn-primary">
            <?php esc_html_e( 'Guardar cambios', 'wa-notifier' ); ?>
        </button>
    </form>

    <div class="wan-danger-zone">
        <h3><?php esc_html_e( 'Desconectar', 'wa-notifier' ); ?></h3>
        <p><?php esc_html_e( 'Elimina las credenciales y desvincula la sesión WhatsApp. La tienda dejará de enviar notificaciones.', 'wa-notifier' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
              onsubmit="return confirm('<?php esc_attr_e( '¿Seguro? Se borrarán las credenciales y la sesión WhatsApp.', 'wa-notifier' ); ?>')">
            <input type="hidden" name="action" value="wa_notifier_disconnect">
            <?php wp_nonce_field( 'wa_notifier_disconnect' ); ?>
            <button type="submit" class="button wan-btn-danger">
                <?php esc_html_e( 'Desconectar cuenta', 'wa-notifier' ); ?>
            </button>
        </form>
    </div>

<?php
/* ============================================================
   ESTADOS B y C: WIZARD DE ONBOARDING
   ============================================================ */
else :
    // Estado B: tenant registrado, pago pendiente
    // Estado C (= A sin $tenant_id): instalación nueva
    $ob_step = $tenant_id ? '2' : '1';
?>

    <!-- Indicador de pasos -->
    <div class="wan-ob-steps-nav">
        <span class="wan-ob-step-dot <?php echo $ob_step === '1' ? 'active' : 'done'; ?>" data-step="1">1</span>
        <span class="wan-ob-step-line"></span>
        <span class="wan-ob-step-dot <?php echo $ob_step === '2' ? 'active' : ''; ?>" data-step="2">2</span>
        <span class="wan-ob-step-line"></span>
        <span class="wan-ob-step-dot" data-step="3">3</span>
    </div>

    <!-- PASO 1: Crear cuenta -->
    <div id="wan-ob-step-1" class="wan-ob-step" <?php echo $ob_step !== '1' ? 'style="display:none"' : ''; ?>>
        <div class="waxap-section-header">
            <h2><?php esc_html_e( 'Crea tu cuenta Waxap', 'wa-notifier' ); ?></h2>
            <p><?php esc_html_e( 'Introduce tu email y una contraseña para registrarte. Después elegirás tu plan.', 'wa-notifier' ); ?></p>
        </div>

        <form id="wan-ob-register-form" autocomplete="off">
            <div class="wan-field-rows" style="max-width:480px;">
                <div class="wan-field-row">
                    <label for="wan-ob-email" class="wan-field-label">
                        <?php esc_html_e( 'Email', 'wa-notifier' ); ?>
                    </label>
                    <input type="email" id="wan-ob-email" name="email"
                           class="regular-text wan-field-input" required
                           placeholder="tienda@ejemplo.com" autocomplete="off">
                </div>
                <div class="wan-field-row">
                    <label for="wan-ob-password" class="wan-field-label">
                        <?php esc_html_e( 'Contraseña', 'wa-notifier' ); ?>
                        <span class="wan-field-hint"><?php esc_html_e( 'Mínimo 8 caracteres', 'wa-notifier' ); ?></span>
                    </label>
                    <input type="password" id="wan-ob-password" name="password"
                           class="regular-text wan-field-input" required
                           minlength="8" autocomplete="new-password">
                </div>
            </div>

            <p id="wan-ob-register-error" class="wan-error" style="display:none;color:#cc1818;margin:8px 0 0;"></p>

            <button type="submit" class="button button-primary waxap-btn-primary" style="margin-top:12px;">
                <?php esc_html_e( 'Crear cuenta', 'wa-notifier' ); ?>
            </button>
        </form>

        <!-- Toggle: ya tengo cuenta -->
        <?php
        $login_error = isset( $_GET['login_error'] ) ? sanitize_text_field( (string) $_GET['login_error'] ) : '';
        $show_login  = $login_error !== '';
        ?>
        <p style="margin-top:20px;border-top:1px solid #e5e7eb;padding-top:16px;">
            <a href="#" id="wan-toggle-login" style="font-size:13px;color:#666;text-decoration:none;">
                <?php esc_html_e( '¿Ya tienes cuenta? Inicia sesión →', 'wa-notifier' ); ?>
            </a>
        </p>

        <div id="wan-login-form-wrap" <?php echo $show_login ? '' : 'style="display:none;"'; ?>>
            <?php if ( $login_error ) : ?>
                <p class="wan-inline-notice wan-inline-notice--error" style="margin-bottom:12px;">
                    <?php echo esc_html( $login_error === 'missing_fields' ? __( 'Rellena todos los campos.', 'wa-notifier' ) : $login_error ); ?>
                </p>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
                <input type="hidden" name="action" value="wa_notifier_login">
                <?php wp_nonce_field( 'wa_notifier_login' ); ?>

                <div class="wan-field-rows" style="max-width:480px;">
                    <div class="wan-field-row">
                        <label for="wan-login-email" class="wan-field-label">
                            <?php esc_html_e( 'Email', 'wa-notifier' ); ?>
                        </label>
                        <input type="email" id="wan-login-email" name="email"
                               class="regular-text wan-field-input" required autocomplete="off"
                               placeholder="tienda@ejemplo.com">
                    </div>
                    <div class="wan-field-row">
                        <label for="wan-login-password" class="wan-field-label">
                            <?php esc_html_e( 'Contraseña', 'wa-notifier' ); ?>
                        </label>
                        <input type="password" id="wan-login-password" name="password"
                               class="regular-text wan-field-input" required minlength="8">
                    </div>
                </div>

                <button type="submit" class="button button-primary waxap-btn-primary" style="margin-top:12px;">
                    <?php esc_html_e( 'Iniciar sesión', 'wa-notifier' ); ?>
                </button>
                <a href="#" id="wan-cancel-login" style="margin-left:12px;font-size:13px;color:#666;">
                    <?php esc_html_e( 'Cancelar', 'wa-notifier' ); ?>
                </a>
            </form>
        </div>

        <script>
        (function () {
            var toggle  = document.getElementById('wan-toggle-login');
            var cancel  = document.getElementById('wan-cancel-login');
            var wrap    = document.getElementById('wan-login-form-wrap');
            var regForm = document.getElementById('wan-ob-register-form');
            if (!toggle || !wrap) return;
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                wrap.style.display = 'block';
                regForm.style.display = 'none';
                toggle.style.display = 'none';
            });
            if (cancel) {
                cancel.addEventListener('click', function (e) {
                    e.preventDefault();
                    wrap.style.display = 'none';
                    regForm.style.display = 'block';
                    toggle.style.display = '';
                });
            }
        })();
        </script>
    </div>

    <!-- PASO 2: Suscribirse -->
    <div id="wan-ob-step-2" class="wan-ob-step" <?php echo $ob_step !== '2' ? 'style="display:none"' : ''; ?>>
        <div class="waxap-section-header">
            <h2><?php esc_html_e( 'Activa tu suscripción', 'wa-notifier' ); ?></h2>
            <p>
                <?php esc_html_e( 'Plan Waxap: ', 'wa-notifier' ); ?>
                <strong><?php esc_html_e( '5 €/mes · 100 mensajes al mes', 'wa-notifier' ); ?></strong>.
                <?php esc_html_e( 'Cancela cuando quieras.', 'wa-notifier' ); ?>
            </p>
        </div>

        <p id="wan-ob-pay-error" class="wan-error" style="display:none;color:#cc1818;margin:0 0 12px;"></p>

        <button id="wan-ob-pay-btn" class="button button-primary waxap-btn-primary" style="font-size:15px;padding:6px 20px;">
            <?php esc_html_e( 'Ir a pagar →', 'wa-notifier' ); ?>
        </button>

        <div id="wan-ob-polling-wrap" style="display:none;margin-top:20px;">
            <p id="wan-ob-polling-status" style="color:#666;font-style:italic;">
                <?php esc_html_e( 'Esperando confirmación del pago…', 'wa-notifier' ); ?>
            </p>
            <p style="font-size:12px;color:#999;margin-top:4px;">
                <?php esc_html_e( '¿Ya pagaste pero no se activa?', 'wa-notifier' ); ?>
                <a href="#" id="wan-ob-already-paid"><?php esc_html_e( 'Verificar ahora', 'wa-notifier' ); ?></a>
            </p>
        </div>
    </div>

    <!-- PASO 3: ¡Activado! -->
    <div id="wan-ob-step-3" class="wan-ob-step" style="display:none">
        <div class="waxap-section-header">
            <h2><?php esc_html_e( '¡Cuenta activada! 🎉', 'wa-notifier' ); ?></h2>
            <p><?php esc_html_e( 'Tu suscripción está activa. Ahora vincula tu número de WhatsApp para empezar a enviar notificaciones.', 'wa-notifier' ); ?></p>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-notifier&tab=phone&ob=done' ) ); ?>"
           class="button button-primary waxap-btn-primary" style="font-size:15px;padding:6px 20px;">
            <?php esc_html_e( 'Vincular número WhatsApp →', 'wa-notifier' ); ?>
        </a>
    </div>

<?php endif; ?>
