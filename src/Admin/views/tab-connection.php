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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
============================================================
	ESTADO A: CONECTADO (tiene api_key)
============================================================
*/
if ( $is_connected ) : ?>

	<?php if ( isset( $_GET['ob'] ) && 'done' === $_GET['ob'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="waxap-updated">
			<?php esc_html_e( '¡Cuenta activada! Ahora vincula tu número de WhatsApp en el paso siguiente.', 'waxap-for-woocommerce' ); ?>
		</div>
	<?php elseif ( isset( $_GET['updated'] ) ) : ?>
		<div class="waxap-updated"><?php esc_html_e( 'Configuración guardada.', 'waxap-for-woocommerce' ); ?></div>
	<?php elseif ( isset( $_GET['disconnected'] ) ) : ?>
		<div class="waxap-notice-warning"><?php esc_html_e( 'Cuenta desconectada.', 'waxap-for-woocommerce' ); ?></div>
	<?php endif; ?>

	<div class="waxap-section-header">
		<h2>
			<?php esc_html_e( 'Servidor Waxap', 'waxap-for-woocommerce' ); ?>
			<span class="wan-connection-badge wan-connection-badge--ok"><?php esc_html_e( 'Conectado', 'waxap-for-woocommerce' ); ?></span>
		</h2>
		<p><?php esc_html_e( 'Tu tienda está conectada. Puedes actualizar tu API Key si es necesario.', 'waxap-for-woocommerce' ); ?></p>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
		<input type="hidden" name="action" value="wa_notifier_save_connection">
		<?php wp_nonce_field( 'wa_notifier_save_connection' ); ?>

		<div class="wan-field-rows" style="max-width:620px;">
			<div class="wan-field-row">
				<label for="wan-api-key" class="wan-field-label">
					<?php esc_html_e( 'API Key', 'waxap-for-woocommerce' ); ?>
				</label>
				<input type="text" id="wan-api-key" name="api_key"
						value="<?php echo esc_attr( $api_key ); ?>"
						class="regular-text wan-field-input" autocomplete="off">
			</div>
			<?php if ( $tenant_id ) : ?>
			<div class="wan-field-row">
				<label class="wan-field-label"><?php esc_html_e( 'Tenant ID', 'waxap-for-woocommerce' ); ?></label>
				<input type="text" value="<?php echo esc_attr( $tenant_id ); ?>"
						class="regular-text wan-field-input" readonly>
				<input type="hidden" name="tenant_id" value="<?php echo esc_attr( $tenant_id ); ?>">
			</div>
			<?php endif; ?>
		</div>

		<button type="submit" class="button button-primary waxap-btn-primary">
			<?php esc_html_e( 'Guardar cambios', 'waxap-for-woocommerce' ); ?>
		</button>
	</form>

	<?php
	if ( null !== $usage ) :
		$waxap_used       = (int) ( $usage['used'] ?? 0 );
		$waxap_quota      = (int) ( $usage['quota'] ?? 100 );
		$waxap_sub_status = (string) ( $usage['status'] ?? 'active' );
		$waxap_reset_at   = ! empty( $usage['quotaResetAt'] ) ? new DateTime( $usage['quotaResetAt'] ) : null;
		$waxap_pct        = $waxap_quota > 0 ? min( 100, (int) round( $waxap_used / $waxap_quota * 100 ) ) : 0;
		$waxap_remaining  = $waxap_quota - $waxap_used;
		$waxap_warning    = $waxap_remaining < 20;
		$waxap_portal_url = admin_url( 'admin.php?page=waxap&tab=connection' );
		?>
	<div class="wan-usage-card" style="margin-top:24px;padding:16px 20px;border:1px solid #e5e7eb;border-radius:8px;max-width:620px;">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
			<strong style="font-size:14px;"><?php esc_html_e( 'Plan Waxap', 'waxap-for-woocommerce' ); ?></strong>
			<?php if ( 'active' === $waxap_sub_status ) : ?>
				<span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:10px;font-size:12px;font-weight:600;"><?php esc_html_e( 'Activo', 'waxap-for-woocommerce' ); ?></span>
			<?php elseif ( 'suspended' === $waxap_sub_status ) : ?>
				<span style="background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:10px;font-size:12px;font-weight:600;"><?php esc_html_e( 'Suspendido', 'waxap-for-woocommerce' ); ?></span>
			<?php else : ?>
				<span style="background:#e5e7eb;color:#374151;padding:2px 10px;border-radius:10px;font-size:12px;font-weight:600;"><?php echo esc_html( $waxap_sub_status ); ?></span>
			<?php endif; ?>
		</div>

		<div style="margin-bottom:6px;font-size:13px;color:#555;">
			<?php
			printf(
				/* translators: %1$d: mensajes usados, %2$d: quota total */
				esc_html__( '%1$d / %2$d mensajes usados este mes', 'waxap-for-woocommerce' ),
				(int) $waxap_used,
				(int) $waxap_quota
			);
			?>
		</div>
		<div style="background:#e5e7eb;border-radius:4px;height:8px;overflow:hidden;">
			<div style="background:<?php echo $waxap_warning ? '#ef4444' : '#25d366'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded hex color values ?>;height:100%;width:<?php echo esc_attr( $waxap_pct ); ?>%;transition:width .3s;"></div>
		</div>

		<?php if ( $waxap_reset_at ) : ?>
		<p style="font-size:12px;color:#9ca3af;margin:6px 0 0;">
			<?php
			printf(
				/* translators: %s: fecha de renovación */
				esc_html__( 'Renovación el %s', 'waxap-for-woocommerce' ),
				esc_html( wp_date( get_option( 'date_format' ), $waxap_reset_at->getTimestamp() + 30 * DAY_IN_SECONDS ) )
			);
			?>
		</p>
		<?php endif; ?>

		<?php if ( $waxap_warning && 'active' === $waxap_sub_status ) : ?>
		<p style="margin:10px 0 0;padding:8px 12px;background:#fef3c7;border-radius:6px;font-size:13px;color:#92400e;">
			⚠️
			<?php
			printf(
				/* translators: %d: mensajes restantes */
				esc_html__( 'Te quedan %d mensajes este mes. Considera actualizar tu plan.', 'waxap-for-woocommerce' ),
				(int) $waxap_remaining
			);
			?>
		</p>
		<?php endif; ?>

		<?php if ( in_array( $waxap_sub_status, [ 'suspended', 'cancelled' ], true ) ) : ?>
		<p style="margin:10px 0 0;padding:8px 12px;background:#fee2e2;border-radius:6px;font-size:13px;color:#991b1b;">
			🔴 <?php esc_html_e( 'Tu suscripción no está activa. La tienda no enviará notificaciones.', 'waxap-for-woocommerce' ); ?>
		</p>
		<?php endif; ?>

		<p style="margin:12px 0 0;">
			<a href="#" id="wan-portal-btn" style="font-size:13px;">
				<?php esc_html_e( 'Gestionar suscripción →', 'waxap-for-woocommerce' ); ?>
			</a>
			<span id="wan-portal-spinner" style="display:none;font-size:12px;color:#9ca3af;margin-left:8px;"><?php esc_html_e( 'Cargando…', 'waxap-for-woocommerce' ); ?></span>
		</p>
	</div>

	<script>
	(function () {
		var btn = document.getElementById('wan-portal-btn');
		var spinner = document.getElementById('wan-portal-spinner');
		if (!btn) return;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			btn.style.pointerEvents = 'none';
			spinner.style.display = 'inline';
			fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'wa_notifier_billing_portal',
					nonce: '<?php echo esc_js( wp_create_nonce( 'wa_notifier_billing_portal' ) ); ?>',
				})
			})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.success && data.data && data.data.url) {
					window.open(data.data.url, '_blank');
				} else {
					alert(data.data || '<?php echo esc_js( __( 'Error al obtener el enlace.', 'waxap-for-woocommerce' ) ); ?>');
				}
			})
			.catch(function () {
				alert('<?php echo esc_js( __( 'Error de conexión.', 'waxap-for-woocommerce' ) ); ?>');
			})
			.finally(function () {
				btn.style.pointerEvents = '';
				spinner.style.display = 'none';
			});
		});
	})();
	</script>
	<?php endif; ?>

	<div class="wan-danger-zone">
		<h3><?php esc_html_e( 'Desconectar', 'waxap-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Elimina las credenciales y desvincula la sesión WhatsApp. La tienda dejará de enviar notificaciones.', 'waxap-for-woocommerce' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				onsubmit="return confirm('<?php esc_attr_e( '¿Seguro? Se eliminarán las credenciales del plugin. Tu número de WhatsApp seguirá conectado — puedes desvincularlo desde la pestaña Número WhatsApp.', 'waxap-for-woocommerce' ); ?>')">
			<input type="hidden" name="action" value="wa_notifier_disconnect">
			<?php wp_nonce_field( 'wa_notifier_disconnect' ); ?>
			<button type="submit" class="button wan-btn-danger">
				<?php esc_html_e( 'Desconectar cuenta', 'waxap-for-woocommerce' ); ?>
			</button>
		</form>
	</div>

	<?php
	/*
	============================================================
	ESTADOS B y C: WIZARD DE ONBOARDING
	============================================================
	*/
else :
	// Estado B: tenant registrado, pago pendiente.
	// Estado C (= A sin $tenant_id): instalación nueva.
	$waxap_ob_step = $tenant_id ? '2' : '1';
	?>

	<!-- Indicador de pasos -->
	<div class="wan-ob-steps-nav">
		<span class="wan-ob-step-dot <?php echo '1' === $waxap_ob_step ? 'active' : 'done'; ?>" data-step="1">1</span>
		<span class="wan-ob-step-line"></span>
		<span class="wan-ob-step-dot <?php echo '2' === $waxap_ob_step ? 'active' : ''; ?>" data-step="2">2</span>
		<span class="wan-ob-step-line"></span>
		<span class="wan-ob-step-dot" data-step="3">3</span>
	</div>

	<!-- PASO 1: Crear cuenta -->
	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$waxap_login_error = isset( $_GET['login_error'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['login_error'] ) ) : '';
	$waxap_show_login  = ( '' !== $waxap_login_error ) || ! empty( $_GET['show_login'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div id="wan-ob-step-1" class="wan-ob-step" <?php echo '1' !== $waxap_ob_step ? 'style="display:none"' : ''; ?>>
		<div class="waxap-section-header">
			<h2><?php esc_html_e( 'Crea tu cuenta Waxap', 'waxap-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'Introduce tu email y una contraseña para registrarte. Después elegirás tu plan.', 'waxap-for-woocommerce' ); ?></p>
		</div>

		<form id="wan-ob-register-form" autocomplete="off" <?php echo $waxap_show_login ? 'style="display:none;"' : ''; ?>>
			<div class="wan-field-rows" style="max-width:480px;">
				<div class="wan-field-row">
					<label for="wan-ob-email" class="wan-field-label">
						<?php esc_html_e( 'Email', 'waxap-for-woocommerce' ); ?>
					</label>
					<input type="email" id="wan-ob-email" name="email"
							class="regular-text wan-field-input" required
							placeholder="tienda@ejemplo.com" autocomplete="off">
				</div>
				<div class="wan-field-row">
					<label for="wan-ob-password" class="wan-field-label">
						<?php esc_html_e( 'Contraseña', 'waxap-for-woocommerce' ); ?>
						<span class="wan-field-hint"><?php esc_html_e( 'Mínimo 8 caracteres', 'waxap-for-woocommerce' ); ?></span>
					</label>
					<input type="password" id="wan-ob-password" name="password"
							class="regular-text wan-field-input" required
							minlength="8" autocomplete="new-password">
				</div>
			</div>

			<p id="wan-ob-register-error" class="wan-error" style="display:none;color:#cc1818;margin:8px 0 0;"></p>

			<button type="submit" class="button button-primary waxap-btn-primary" style="margin-top:12px;">
				<?php esc_html_e( 'Crear cuenta', 'waxap-for-woocommerce' ); ?>
			</button>
		</form>

		<!-- Toggle: ya tengo cuenta -->
		<p id="wan-toggle-login-wrap" style="margin-top:20px;border-top:1px solid #e5e7eb;padding-top:16px;<?php echo $waxap_show_login ? 'display:none;' : ''; ?>">
			<a href="#" id="wan-toggle-login" style="font-size:13px;color:#666;text-decoration:none;">
				<?php esc_html_e( '¿Ya tienes cuenta? Inicia sesión →', 'waxap-for-woocommerce' ); ?>
			</a>
		</p>

		<div id="wan-login-form-wrap" <?php echo $waxap_show_login ? '' : 'style="display:none;"'; ?>>
			<?php if ( $waxap_login_error ) : ?>
				<p class="wan-inline-notice wan-inline-notice--error" style="margin-bottom:12px;">
					<?php echo esc_html( 'missing_fields' === $waxap_login_error ? __( 'Rellena todos los campos.', 'waxap-for-woocommerce' ) : $waxap_login_error ); ?>
				</p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
				<input type="hidden" name="action" value="wa_notifier_login">
				<?php wp_nonce_field( 'wa_notifier_login' ); ?>

				<div class="wan-field-rows" style="max-width:480px;">
					<div class="wan-field-row">
						<label for="wan-login-email" class="wan-field-label">
							<?php esc_html_e( 'Email', 'waxap-for-woocommerce' ); ?>
						</label>
						<input type="email" id="wan-login-email" name="email"
								class="regular-text wan-field-input" required autocomplete="off"
								placeholder="tienda@ejemplo.com">
					</div>
					<div class="wan-field-row">
						<label for="wan-login-password" class="wan-field-label">
							<?php esc_html_e( 'Contraseña', 'waxap-for-woocommerce' ); ?>
						</label>
						<input type="password" id="wan-login-password" name="password"
								class="regular-text wan-field-input" required minlength="8">
					</div>
				</div>

				<button type="submit" class="button button-primary waxap-btn-primary" style="margin-top:12px;">
					<?php esc_html_e( 'Iniciar sesión', 'waxap-for-woocommerce' ); ?>
				</button>
				<a href="#" id="wan-cancel-login" style="margin-left:12px;font-size:13px;color:#666;">
					<?php esc_html_e( 'Cancelar', 'waxap-for-woocommerce' ); ?>
				</a>
			</form>
		</div>

		<script>
		(function () {
			var toggleWrap = document.getElementById('wan-toggle-login-wrap');
			var toggle     = document.getElementById('wan-toggle-login');
			var cancel     = document.getElementById('wan-cancel-login');
			var wrap       = document.getElementById('wan-login-form-wrap');
			var regForm    = document.getElementById('wan-ob-register-form');
			if (!toggle || !wrap) return;
			toggle.addEventListener('click', function (e) {
				e.preventDefault();
				wrap.style.display = 'block';
				regForm.style.display = 'none';
				toggleWrap.style.display = 'none';
			});
			if (cancel) {
				cancel.addEventListener('click', function (e) {
					e.preventDefault();
					wrap.style.display = 'none';
					regForm.style.display = 'block';
					toggleWrap.style.display = '';
				});
			}
		})();
		</script>
	</div>

	<!-- PASO 2: Suscribirse -->
	<div id="wan-ob-step-2" class="wan-ob-step" <?php echo '2' !== $waxap_ob_step ? 'style="display:none"' : ''; ?>>
		<div class="waxap-section-header">
			<h2><?php esc_html_e( 'Elige tu plan', 'waxap-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'Selecciona el plan que mejor se adapta a tu tienda.', 'waxap-for-woocommerce' ); ?></p>
		</div>

		<div class="wan-plan-select-wrap">
			<select id="wan-plan-select" name="wan_plan" class="wan-plan-select">
				<option value="basic">⚡ Básico — 6 €/mes · 100 mensajes al mes</option>
				<option value="pro">🚀 Pro — 12 €/mes · 200 mensajes al mes</option>
				<option value="lifetime">✨ Vitalicio — 200 € pago único · Mensajes ilimitados para siempre</option>
			</select>
			<p id="wan-plan-desc" class="wan-plan-desc-text"></p>
		</div>

		<?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['payment'] ) && 'cancelled' === $_GET['payment'] ) :
			?>
		<p class="wan-inline-notice wan-inline-notice--warning" style="margin-bottom:12px;">
			<?php esc_html_e( 'Pago cancelado. Puedes intentarlo de nuevo cuando quieras.', 'waxap-for-woocommerce' ); ?>
		</p>
		<?php endif; ?>

		<p id="wan-ob-pay-error" class="wan-error" style="display:none;color:#cc1818;margin:0 0 12px;"></p>

		<button id="wan-ob-pay-btn" class="button button-primary waxap-btn-primary" style="font-size:15px;padding:6px 20px;margin-top:8px;">
			<?php esc_html_e( 'Ir a pagar →', 'waxap-for-woocommerce' ); ?>
		</button>

		<div id="wan-ob-polling-wrap" style="display:none;margin-top:20px;">
			<p id="wan-ob-polling-status" style="color:#666;font-style:italic;">
				<?php esc_html_e( 'Esperando confirmación del pago…', 'waxap-for-woocommerce' ); ?>
			</p>
			<p style="font-size:12px;color:#999;margin-top:4px;">
				<?php esc_html_e( '¿Ya pagaste pero no se activa?', 'waxap-for-woocommerce' ); ?>
				<a href="#" id="wan-ob-already-paid"><?php esc_html_e( 'Verificar ahora', 'waxap-for-woocommerce' ); ?></a>
			</p>
		</div>

		<p style="margin-top:24px;border-top:1px solid #e5e7eb;padding-top:16px;">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
				<input type="hidden" name="action" value="wa_notifier_cancel_registration">
				<?php wp_nonce_field( 'wa_notifier_cancel_registration' ); ?>
				<button type="submit" class="button-link" style="font-size:13px;color:#666;cursor:pointer;">
					<?php esc_html_e( '← Conectar con otra cuenta', 'waxap-for-woocommerce' ); ?>
				</button>
			</form>
		</p>
	</div>

	<!-- PASO 3: ¡Activado! -->
	<div id="wan-ob-step-3" class="wan-ob-step" style="display:none">
		<div class="waxap-section-header">
			<h2><?php esc_html_e( '¡Cuenta activada! 🎉', 'waxap-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'Tu suscripción está activa. Ahora vincula tu número de WhatsApp para empezar a enviar notificaciones.', 'waxap-for-woocommerce' ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=waxap&tab=phone&ob=done' ) ); ?>"
			class="button button-primary waxap-btn-primary" style="font-size:15px;padding:6px 20px;">
			<?php esc_html_e( 'Vincular número WhatsApp →', 'waxap-for-woocommerce' ); ?>
		</a>
	</div>

<?php endif; ?>
