<?php
/**
 * Vista: tab Notificaciones — selector de estados y plantillas de mensaje.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 *
 * @var string[] $enabled_statuses  Estados activos (inyectados por AdminMenu::render_tab).
 * @var string[] $templates         Plantillas por estado (inyectados por AdminMenu::render_tab).
 */

declare(strict_types=1);

$statuses = [
    'processing' => [
        'label' => 'Procesando',
        'desc'  => 'El cliente completó el pago. El pedido está en preparación.',
        'color' => '#2271b1',
    ],
    'completed'  => [
        'label' => 'Completado',
        'desc'  => 'El pedido ha sido entregado o marcado como completado.',
        'color' => '#25d366',
    ],
    'on-hold'    => [
        'label' => 'En espera',
        'desc'  => 'Pago pendiente de confirmación (ej. transferencia bancaria).',
        'color' => '#f59e0b',
    ],
    'cancelled'  => [
        'label' => 'Cancelado',
        'desc'  => 'El pedido fue cancelado por el cliente o la tienda.',
        'color' => '#ef4444',
    ],
    'refunded'   => [
        'label' => 'Reembolsado',
        'desc'  => 'El importe fue devuelto al cliente.',
        'color' => '#8b5cf6',
    ],
    'pending'    => [
        'label' => 'Pendiente de pago',
        'desc'  => 'El pedido existe pero el cliente aún no ha pagado.',
        'color' => '#9ca3af',
    ],
    'failed'     => [
        'label' => 'Fallido',
        'desc'  => 'El pago no pudo completarse.',
        'color' => '#6b7280',
    ],
];

if ( isset( $_GET['updated'] ) ) : ?>
    <div class="waxap-updated"><?php esc_html_e( 'Configuración guardada.', 'wa-notifier' ); ?></div>
<?php endif; ?>

<div class="waxap-section-header">
    <h2><?php esc_html_e( '¿En qué momentos avisas a tus clientes?', 'wa-notifier' ); ?></h2>
    <p><?php esc_html_e( 'Activa los estados que enviarán un WhatsApp. Usa el lápiz para personalizar el mensaje.', 'wa-notifier' ); ?></p>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
    <input type="hidden" name="action" value="wa_notifier_save_notifications">
    <?php wp_nonce_field( 'wa_notifier_save_notifications' ); ?>

    <div class="wan-status-list">
        <?php foreach ( $statuses as $key => $status ) :
            $is_enabled  = in_array( $key, $enabled_statuses, true );
            $input_id    = 'wan-status-' . esc_attr( $key );
            $textarea_id = 'wan-tpl-' . esc_attr( $key );
            $panel_id    = 'wan-tpl-panel-' . esc_attr( $key );
        ?>
        <div class="wan-status-item">

            <div class="wan-status-card-row">
                <label class="wan-status-card" for="<?php echo $input_id; ?>">
                    <span class="wan-status-dot-indicator"
                          style="background-color: <?php echo esc_attr( $status['color'] ); ?>;"></span>

                    <div class="wan-status-info">
                        <strong><?php echo esc_html( $status['label'] ); ?></strong>
                        <span><?php echo esc_html( $status['desc'] ); ?></span>
                    </div>

                    <div class="wan-toggle">
                        <input
                            type="checkbox"
                            id="<?php echo $input_id; ?>"
                            name="wan_notify_statuses[]"
                            value="<?php echo esc_attr( $key ); ?>"
                            class="wan-toggle-input"
                            <?php checked( $is_enabled ); ?>
                        >
                        <span class="wan-toggle-track"></span>
                        <span class="wan-toggle-thumb"></span>
                    </div>
                </label>

                <button
                    type="button"
                    class="wan-edit-tpl-btn"
                    data-panel="<?php echo $panel_id; ?>"
                    aria-expanded="false"
                    aria-label="<?php esc_attr_e( 'Editar plantilla de mensaje', 'wa-notifier' ); ?>"
                    title="<?php esc_attr_e( 'Editar mensaje', 'wa-notifier' ); ?>"
                >
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
                         width="15" height="15" aria-hidden="true">
                        <path d="M11.5 1.5l3 3-9 9H2.5v-3l9-9z"/>
                    </svg>
                </button>
            </div>

            <div class="wan-tpl-panel" id="<?php echo $panel_id; ?>">
                <div class="wan-tpl-panel-inner">
                    <textarea
                        name="wan_templates[<?php echo esc_attr( $key ); ?>]"
                        id="<?php echo $textarea_id; ?>"
                        class="wan-template-textarea wan-template-textarea--inline"
                        rows="3"
                    ><?php echo esc_textarea( $templates[ $key ] ?? '' ); ?></textarea>
                    <div class="wan-template-footer">
                        <?php foreach ( [ '{nombre}', '{pedido}', '{estado}', '{total}', '{enlace}' ] as $var ) : ?>
                        <span class="wan-var-chip" data-target="<?php echo $textarea_id; ?>"><?php echo esc_html( $var ); ?></span>
                        <?php endforeach; ?>
                        <span class="wan-char-count" id="wan-count-<?php echo esc_attr( $key ); ?>">0</span>
                    </div>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

    <p class="submit" style="padding:0;margin:0;">
        <button type="submit" class="button button-primary waxap-btn-primary">
            <?php esc_html_e( 'Guardar cambios', 'wa-notifier' ); ?>
        </button>
    </p>
</form>

<script>
(function () {
    // Accordion: pencil button toggles template panel
    document.querySelectorAll('.wan-edit-tpl-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel = document.getElementById(btn.dataset.panel);
            if (!panel) return;
            var open = btn.classList.toggle('is-active');
            btn.setAttribute('aria-expanded', String(open));
            panel.classList.toggle('is-open', open);
        });
    });

    // Char counters
    document.querySelectorAll('.wan-template-textarea').forEach(function (ta) {
        var key     = ta.id.replace('wan-tpl-', '');
        var counter = document.getElementById('wan-count-' + key);
        if (!counter) return;
        function update() { counter.textContent = ta.value.length; }
        update();
        ta.addEventListener('input', update);
    });

    // Variable chip insertion
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
