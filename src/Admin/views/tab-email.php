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
 */

declare(strict_types=1);

if ( isset( $_GET['updated'] ) ) : ?>
    <div class="waxap-updated"><?php esc_html_e( 'Configuración guardada.', 'wa-notifier' ); ?></div>
<?php endif; ?>

<div class="waxap-section-header">
    <h2><?php esc_html_e( 'Botón WhatsApp en emails', 'wa-notifier' ); ?></h2>
    <p><?php esc_html_e( 'Añade un botón wa.me en los emails transaccionales de WooCommerce para que el cliente pueda escribirte directamente.', 'wa-notifier' ); ?></p>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
    <input type="hidden" name="action" value="wa_notifier_save_email">
    <?php wp_nonce_field( 'wa_notifier_save_email' ); ?>

    <div class="wan-field-group">
        <label class="wan-status-card" for="wan-email-enabled" style="max-width:620px;">
            <div class="wan-status-info">
                <strong><?php esc_html_e( 'Activar botón WhatsApp', 'wa-notifier' ); ?></strong>
                <span><?php esc_html_e( 'Muestra el botón en todos los emails de pedido enviados al cliente.', 'wa-notifier' ); ?></span>
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
                <?php esc_html_e( 'Texto del botón', 'wa-notifier' ); ?>
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
                <?php esc_html_e( 'Mensaje prefabricado', 'wa-notifier' ); ?>
                <span class="wan-field-hint"><?php esc_html_e( 'El cliente verá este texto en WhatsApp al hacer clic.', 'wa-notifier' ); ?></span>
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

    <div class="wan-email-preview-wrap">
        <p class="wan-field-label"><?php esc_html_e( 'Vista previa', 'wa-notifier' ); ?></p>
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
            <?php esc_html_e( 'Guardar cambios', 'wa-notifier' ); ?>
        </button>
    </p>
</form>

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
