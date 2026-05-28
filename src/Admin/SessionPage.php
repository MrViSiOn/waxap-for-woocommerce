<?php
/**
 * Página "Número WhatsApp" — vinculación QR y estado de sesión.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Api\WrapperClient;
use WaNotifier\Settings;

/**
 * Renderiza la pestaña "Número WhatsApp" con el flujo de vinculación QR.
 */
final class SessionPage {

	/** Renderiza el contenido de la pestaña según el estado actual de conexión. */
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

	/** Muestra el formulario de registro/credenciales cuando no hay conexión al wrapper. */
	private function render_register_form(): void {
		$wrapper_url = Settings::get( 'wrapper_url' );
		?>
		<div class="waxap-section-header">
			<h2><?php esc_html_e( 'Conecta tu número WhatsApp', 'waxap-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'Introduce tus credenciales de servidor para empezar a enviar notificaciones.', 'waxap-for-woocommerce' ); ?></p>
		</div>

		<form id="wa-notifier-register-form" method="post" autocomplete="off">
			<?php wp_nonce_field( 'wa_notifier_register', 'wa_notifier_nonce' ); ?>

			<div class="wan-field-rows" style="max-width:480px;">
				<div class="wan-field-row">
					<label for="wan-wrapper-url" class="wan-field-label">
						<?php esc_html_e( 'URL del servidor', 'waxap-for-woocommerce' ); ?>
					</label>
					<input type="url" id="wan-wrapper-url" name="wrapper_url"
							value="<?php echo esc_attr( $wrapper_url ); ?>"
							class="regular-text wan-field-input" autocomplete="off" required>
				</div>
				<div class="wan-field-row">
					<label for="wan-email" class="wan-field-label">
						<?php esc_html_e( 'Email', 'waxap-for-woocommerce' ); ?>
					</label>
					<input type="email" id="wan-email" name="email"
							class="regular-text wan-field-input" required>
				</div>
				<div class="wan-field-row">
					<label for="wan-password" class="wan-field-label">
						<?php esc_html_e( 'Contraseña', 'waxap-for-woocommerce' ); ?>
					</label>
					<input type="password" id="wan-password" name="password"
							class="regular-text wan-field-input" required minlength="8">
				</div>
			</div>

			<p id="wa-notifier-register-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;"></p>

			<p class="wan-action-row">
				<button type="submit" class="button button-primary waxap-btn-primary" id="wa-notifier-register-btn">
					<?php esc_html_e( 'Crear cuenta y conectar', 'waxap-for-woocommerce' ); ?>
				</button>
				<span class="wa-notifier-spinner spinner" style="float:none;margin:0;"></span>
			</p>
		</form>
		<?php
	}

	/** Muestra el botón para iniciar el flujo de vinculación QR. */
	private function render_link_button(): void {
		?>
		<div class="waxap-section-header">
			<h2><?php esc_html_e( 'Vincula tu número WhatsApp', 'waxap-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'Tu tienda está conectada al servidor. Escanea el código QR con WhatsApp para vincular tu número.', 'waxap-for-woocommerce' ); ?></p>
		</div>

		<p class="wan-action-row">
			<button type="button" class="button button-primary waxap-btn-primary" id="wa-notifier-link-btn">
				<?php esc_html_e( 'Vincular WhatsApp', 'waxap-for-woocommerce' ); ?>
			</button>
			<span class="wa-notifier-spinner spinner" style="float:none;margin:0;"></span>
		</p>
		<p id="wa-notifier-link-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;margin-top:8px;"></p>
		<?php
	}

	/** Muestra el estado actual de la sesión WhatsApp vinculada y el formulario de prueba. */
	private function render_session_status(): void {
		$stored_phone  = Settings::get( 'phone_number' );
		$display_phone = $stored_phone ? '+' . ltrim( $stored_phone, '+' ) : '';
		?>
		<div class="waxap-section-header">
			<h2><?php esc_html_e( 'Número WhatsApp', 'waxap-for-woocommerce' ); ?></h2>
		</div>

		<div class="wan-phone-display" id="wan-phone-display" <?php echo $display_phone ? '' : 'style="display:none;"'; ?>>
			<span class="wan-phone-display-icon">📱</span>
			<div class="wan-phone-display-info">
				<span class="wan-phone-display-label"><?php esc_html_e( 'Número vinculado', 'waxap-for-woocommerce' ); ?></span>
				<strong class="wan-phone-display-number" id="wan-phone-display-number"><?php echo esc_html( $display_phone ); ?></strong>
			</div>
		</div>

		<div id="wa-notifier-status-wrap" class="wan-session-status-card">
			<span class="wa-notifier-status-dot wa-notifier-status-dot"></span>
			<span id="wa-notifier-status-text"><?php esc_html_e( 'Comprobando…', 'waxap-for-woocommerce' ); ?></span>
		</div>

		<p class="wan-action-row">
			<button type="button" class="button button-primary waxap-btn-primary" id="wa-notifier-link-btn" style="display:none;">
				<?php esc_html_e( 'Volver a vincular', 'waxap-for-woocommerce' ); ?>
			</button>
			<span class="wa-notifier-spinner spinner" style="float:none;margin:0;"></span>
		</p>
		<p style="margin-top:8px;">
			<button type="button" class="button wan-btn-outline-danger" id="wa-notifier-unlink-btn">
				<?php esc_html_e( 'Desvincular número', 'waxap-for-woocommerce' ); ?>
			</button>
		</p>
		<p id="wa-notifier-link-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;margin-top:8px;"></p>

		<?php $this->render_sessions_list(); ?>

		<div id="wa-notifier-test-wrap" style="display:none;">
			<div class="wan-template-section-header waxap-section-header">
				<h2><?php esc_html_e( 'Mensaje de prueba', 'waxap-for-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Verifica que la conexión funciona enviando un mensaje a cualquier número WhatsApp.', 'waxap-for-woocommerce' ); ?></p>
			</div>

			<form id="wa-notifier-test-form" autocomplete="off">
				<div class="wan-field-rows" style="max-width:480px;">
					<div class="wan-field-row">
						<label for="wan-test-phone" class="wan-field-label">
							<?php esc_html_e( 'Número de teléfono', 'waxap-for-woocommerce' ); ?>
							<span class="wan-field-hint"><?php esc_html_e( 'Formato internacional: +34612345678', 'waxap-for-woocommerce' ); ?></span>
						</label>
						<input type="tel" id="wan-test-phone" name="to"
								placeholder="+34612345678"
								class="regular-text wan-field-input" autocomplete="off" required>
					</div>
				</div>

				<p class="wan-action-row">
					<button type="submit" class="button button-primary waxap-btn-primary" id="wa-notifier-test-btn">
						<?php esc_html_e( 'Enviar mensaje de prueba', 'waxap-for-woocommerce' ); ?>
					</button>
					<span class="wa-notifier-spinner spinner" style="float:none;margin:0;"></span>
				</p>
			</form>
			<p id="wa-notifier-test-result" class="wan-inline-notice" style="display:none;margin-top:8px;"></p>
		</div>
		<?php
	}

	/** Renderiza la lista de todas las sesiones del tenant con opción de desvincular. */
	private function render_sessions_list(): void {
		$client   = new WrapperClient();
		$sessions = $client->get_sessions();

		if ( is_wp_error( $sessions ) || empty( $sessions ) ) {
			return;
		}

		$status_labels = [
			'ready'          => [ 'label' => __( 'Conectado', 'waxap-for-woocommerce' ), 'color' => '#065f46', 'bg' => '#d1fae5' ],
			'qr_ready'       => [ 'label' => __( 'Esperando QR', 'waxap-for-woocommerce' ), 'color' => '#92400e', 'bg' => '#fef3c7' ],
			'initializing'   => [ 'label' => __( 'Iniciando', 'waxap-for-woocommerce' ), 'color' => '#92400e', 'bg' => '#fef3c7' ],
			'authenticating' => [ 'label' => __( 'Autenticando', 'waxap-for-woocommerce' ), 'color' => '#92400e', 'bg' => '#fef3c7' ],
			'disconnected'   => [ 'label' => __( 'Desconectado', 'waxap-for-woocommerce' ), 'color' => '#991b1b', 'bg' => '#fee2e2' ],
			'failed'         => [ 'label' => __( 'Error', 'waxap-for-woocommerce' ), 'color' => '#991b1b', 'bg' => '#fee2e2' ],
		];
		?>
		<div style="margin-top:28px;">
			<h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:#1f2937;">
				<?php esc_html_e( 'Sesiones WhatsApp', 'waxap-for-woocommerce' ); ?>
			</h3>
			<div style="display:flex;flex-direction:column;gap:10px;max-width:620px;" id="wan-sessions-list">
			<?php foreach ( $sessions as $session ) :
				$sid         = (string) ( $session['id'] ?? '' );
				$phone       = (string) ( $session['phoneNumber'] ?? '' );
				$status_key  = (string) ( $session['status'] ?? 'disconnected' );
				$store_url   = (string) ( $session['storeUrl'] ?? '' );
				$badge       = $status_labels[ $status_key ] ?? [ 'label' => esc_html( $status_key ), 'color' => '#374151', 'bg' => '#e5e7eb' ];
				$phone_label = $phone ? '+' . ltrim( $phone, '+' ) : '—';
				$store_label = $store_url ? wp_parse_url( $store_url, PHP_URL_HOST ) : '—';
				if ( ! $sid ) continue;
				?>
				<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;" data-session-id="<?php echo esc_attr( $sid ); ?>">
					<div style="display:flex;flex-direction:column;gap:3px;">
						<span style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html( $phone_label ); ?></span>
						<?php if ( $store_label && '—' !== $store_label ) : ?>
						<span style="font-size:11px;color:#6b7280;"><?php echo esc_html( $store_label ); ?></span>
						<?php endif; ?>
					</div>
					<div style="display:flex;align-items:center;gap:10px;">
						<span style="background:<?php echo esc_attr( $badge['bg'] ); ?>;color:<?php echo esc_attr( $badge['color'] ); ?>;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:600;">
							<?php echo esc_html( $badge['label'] ); ?>
						</span>
						<button type="button"
								class="button wan-delete-session-btn"
								data-session-id="<?php echo esc_attr( $sid ); ?>"
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'wa_notifier_ajax' ) ); ?>"
								style="padding:2px 10px;font-size:12px;color:#dc2626;border-color:#fca5a5;">
							<?php esc_html_e( 'Desvincular', 'waxap-for-woocommerce' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
			</div>
		</div>

		<script>
		(function () {
			document.querySelectorAll('.wan-delete-session-btn').forEach(function (btn) {
				btn.addEventListener('click', function () {
					if ( ! confirm('<?php echo esc_js( __( '¿Desvincular esta sesión? Se cerrará la conexión WhatsApp de este número.', 'waxap-for-woocommerce' ) ); ?>') ) return;
					var sid   = btn.getAttribute('data-session-id');
					var nonce = btn.getAttribute('data-nonce');
					btn.disabled = true;
					btn.textContent = '<?php echo esc_js( __( 'Eliminando…', 'waxap-for-woocommerce' ) ); ?>';
					fetch(ajaxurl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({ action: 'wa_notifier_delete_session', nonce: nonce, session_id: sid })
					})
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data.success) {
							var row = document.querySelector('[data-session-id="' + sid + '"]');
							if (row) row.remove();
						} else {
							alert(data.data && data.data.message ? data.data.message : '<?php echo esc_js( __( 'Error al desvincular.', 'waxap-for-woocommerce' ) ); ?>');
							btn.disabled = false;
							btn.textContent = '<?php echo esc_js( __( 'Desvincular', 'waxap-for-woocommerce' ) ); ?>';
						}
					})
					.catch(function () {
						alert('<?php echo esc_js( __( 'Error de conexión.', 'waxap-for-woocommerce' ) ); ?>');
						btn.disabled = false;
						btn.textContent = '<?php echo esc_js( __( 'Desvincular', 'waxap-for-woocommerce' ) ); ?>';
					});
				});
			});
		})();
		</script>
		<?php
	}

	/** Renderiza el modal de escaneo QR de WhatsApp. */
	private function render_modal(): void {
		?>
		<div id="wa-notifier-modal" aria-hidden="true" role="dialog" aria-modal="true"
			aria-labelledby="wa-notifier-modal-title" style="display:none;">
			<div id="wa-notifier-modal-overlay">
				<div id="wa-notifier-modal-content" class="wa-notifier-modal-box">
					<button type="button" id="wa-notifier-modal-close" aria-label="<?php esc_attr_e( 'Cerrar', 'waxap-for-woocommerce' ); ?>">&times;</button>
					<h2 id="wa-notifier-modal-title"><?php esc_html_e( 'Escanea el QR con WhatsApp', 'waxap-for-woocommerce' ); ?></h2>
					<div id="wa-notifier-qr-wrap">
						<img id="wa-notifier-qr-img" src="" alt="<?php esc_attr_e( 'QR WhatsApp', 'waxap-for-woocommerce' ); ?>" />
						<p id="wa-notifier-qr-loading"><?php esc_html_e( 'Generando QR…', 'waxap-for-woocommerce' ); ?></p>
					</div>
					<p class="description">
						<?php esc_html_e( 'WhatsApp → Dispositivos vinculados → Vincular un dispositivo', 'waxap-for-woocommerce' ); ?>
					</p>
					<p id="wa-notifier-modal-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;"></p>
				</div>
			</div>
		</div>
		<?php
	}
}
