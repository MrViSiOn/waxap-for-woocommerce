<?php
/**
 * Vista: tab Email branding — botón wa.me en emails de WooCommerce.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 *
 * @var bool   $email_enabled
 * @var string $email_text
 * @var string $email_prefill
 * @var bool   $contact_enabled
 * @var bool   $has_phone
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_GET['updated'] ) ) : ?>
	<div class="waxap-updated"><?php esc_html_e( 'Configuración guardada.', 'waxap-for-woocommerce' ); ?></div>
<?php endif; ?>

<?php if ( ! $has_phone ) : ?>
	<div class="waxap-notice-warning">
		<?php
		printf(
			wp_kses(
				/* translators: %s: enlace a la pestaña de número de WhatsApp */
				__( 'El botón no aparecerá en los emails hasta que vincules tu número WhatsApp en el tab <a href="%s">Número WhatsApp</a>.', 'waxap-for-woocommerce' ),
				[ 'a' => [ 'href' => [] ] ]
			),
			esc_url( admin_url( 'admin.php?page=waxap&tab=phone' ) )
		);
		?>
	</div>
<?php endif; ?>

<div class="waxap-section-header">
	<h2><?php esc_html_e( 'Botón WhatsApp en emails', 'waxap-for-woocommerce' ); ?></h2>
	<p><?php esc_html_e( 'Añade un botón wa.me en los emails transaccionales de WooCommerce para que el cliente pueda escribirte directamente.', 'waxap-for-woocommerce' ); ?></p>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
	<input type="hidden" name="action" value="wa_notifier_save_email">
	<?php wp_nonce_field( 'wa_notifier_save_email' ); ?>

	<div class="wan-field-group">
		<label class="wan-status-card" for="wan-email-enabled" style="max-width:620px;">
			<div class="wan-status-info">
				<strong><?php esc_html_e( 'Activar botón WhatsApp', 'waxap-for-woocommerce' ); ?></strong>
				<span><?php esc_html_e( 'Muestra el botón en todos los emails de pedido enviados al cliente.', 'waxap-for-woocommerce' ); ?></span>
			</div>
			<div class="wan-toggle">
				<input
					type="checkbox"
					id="wan-email-enabled"
					name="email_button_enabled"
					value="1"
					class="wan-toggle-input"
					<?php checked( $email_enabled ); ?>
				>
				<span class="wan-toggle-track"></span>
				<span class="wan-toggle-thumb"></span>
			</div>
		</label>
	</div>

	<div class="wan-field-rows" style="max-width:620px;margin-top:24px;">
		<div class="wan-field-row">
			<label for="wan-email-text" class="wan-field-label">
				<?php esc_html_e( 'Texto del botón', 'waxap-for-woocommerce' ); ?>
			</label>
			<input
				type="text"
				id="wan-email-text"
				name="email_button_text"
				value="<?php echo esc_attr( $email_text ); ?>"
				class="regular-text wan-field-input"
				autocomplete="off"
				maxlength="100"
			>
		</div>

		<div class="wan-field-row">
			<label for="wan-email-prefill" class="wan-field-label">
				<?php esc_html_e( 'Mensaje prefabricado', 'waxap-for-woocommerce' ); ?>
				<span class="wan-field-hint"><?php esc_html_e( 'El cliente verá este texto en WhatsApp al hacer clic.', 'waxap-for-woocommerce' ); ?></span>
			</label>
			<div class="wan-template-item" style="border-radius:var(--wax-radius-sm);">
				<textarea
					id="wan-email-prefill"
					name="email_button_prefill"
					class="wan-template-textarea"
					rows="2"
				><?php echo esc_textarea( $email_prefill ); ?></textarea>
				<div class="wan-template-footer">
					<span class="wan-var-chip" data-target="wan-email-prefill">{pedido}</span>
					<span class="wan-char-count" id="wan-count-prefill">0</span>
				</div>
			</div>
		</div>
	</div>

	<div class="wan-field-group" style="margin-top:24px;">
		<label class="wan-status-card" for="wan-contact-enabled" style="max-width:620px;">
			<div class="wan-status-info">
				<strong><?php esc_html_e( 'Mostrar también en el checkout y la página de gracias', 'waxap-for-woocommerce' ); ?></strong>
				<span><?php esc_html_e( 'Añade el mismo botón wa.me tras el formulario de pago y en la confirmación del pedido. Usa el texto y el mensaje configurados arriba.', 'waxap-for-woocommerce' ); ?></span>
			</div>
			<div class="wan-toggle">
				<input
					type="checkbox"
					id="wan-contact-enabled"
					name="contact_button_enabled"
					value="1"
					class="wan-toggle-input"
					<?php checked( $contact_enabled ); ?>
				>
				<span class="wan-toggle-track"></span>
				<span class="wan-toggle-thumb"></span>
			</div>
		</label>
	</div>

	<div class="wan-email-preview-wrap">
		<p class="wan-field-label"><?php esc_html_e( 'Vista previa', 'waxap-for-woocommerce' ); ?></p>
		<div class="wan-email-preview">
			<div class="wan-email-preview-body">
				<div class="wan-email-preview-row"></div>
				<div class="wan-email-preview-row wan-email-preview-row--short"></div>
				<div class="wan-email-preview-row wan-email-preview-row--shorter"></div>
				<div class="wan-email-preview-btn-wrap">
					<a id="wan-preview-btn" class="wan-email-preview-btn" href="#">
						<?php echo esc_html( $email_text ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<p class="submit" style="padding:0;margin:0;">
		<button type="submit" class="button button-primary waxap-btn-primary">
			<?php esc_html_e( 'Guardar cambios', 'waxap-for-woocommerce' ); ?>
		</button>
	</p>
</form>

<hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">

<div class="waxap-section-header">
	<h2><?php esc_html_e( 'Usar el botón en otras páginas', 'waxap-for-woocommerce' ); ?></h2>
	<p><?php esc_html_e( 'Puedes insertar el mismo botón en cualquier página o entrada de WordPress usando el shortcode:', 'waxap-for-woocommerce' ); ?></p>
</div>

<div style="max-width:620px;">
	<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:16px 20px;margin-bottom:16px;">
		<code style="font-size:13px;font-family:'Courier New',monospace;color:#1e293b;">[waxap_whatsapp_button]</code>
	</div>
	<p style="margin:0 0 8px;color:#6b7280;font-size:13px;">
		<?php esc_html_e( 'Utiliza el texto y el mensaje prefabricado configurados en el formulario de arriba.', 'waxap-for-woocommerce' ); ?>
	</p>
	<p style="margin:0 0 16px;color:#6b7280;font-size:13px;">
		<?php esc_html_e( 'Si usas el shortcode en una página de confirmación de pedido y quieres incluir el número de pedido en el mensaje, añade el atributo order:', 'waxap-for-woocommerce' ); ?>
	</p>
	<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:16px 20px;margin-bottom:16px;">
		<code style="font-size:13px;font-family:'Courier New',monospace;color:#1e293b;">[waxap_whatsapp_button order="1234"]</code>
	</div>
	<p style="margin:0;color:#6b7280;font-size:13px;">
		<?php
		echo wp_kses(
			/* translators: %s: ejemplo de variable {pedido} */
			sprintf(
				__( 'El valor de <code>order</code> sustituirá la variable <code>{pedido}</code> que hayas puesto en el mensaje prefabricado.', 'waxap-for-woocommerce' )
			),
			[ 'code' => [] ]
		);
		?>
	</p>
</div>

<script>
(function () {
	var textInput  = document.getElementById('wan-email-text');
	var previewBtn = document.getElementById('wan-preview-btn');
	var prefillTa  = document.getElementById('wan-email-prefill');
	var counter    = document.getElementById('wan-count-prefill');

	if (textInput && previewBtn) {
		textInput.addEventListener('input', function () {
			previewBtn.textContent = textInput.value || ' ';
		});
	}

	if (prefillTa && counter) {
		function update() { counter.textContent = prefillTa.value.length; }
		update();
		prefillTa.addEventListener('input', update);
	}

	document.querySelectorAll('.wan-var-chip').forEach(function (chip) {
		chip.addEventListener('click', function () {
			var ta = document.getElementById(chip.dataset.target);
			if (!ta) return;
			var s = ta.selectionStart, e = ta.selectionEnd;
			ta.value = ta.value.slice(0, s) + chip.textContent + ta.value.slice(e);
			ta.selectionStart = ta.selectionEnd = s + chip.textContent.length;
			ta.focus();
			ta.dispatchEvent(new Event('input'));
		});
	});
}());
</script>
