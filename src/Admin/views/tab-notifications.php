<?php
/**
 * Vista: tab Notificaciones — selector de estados de pedido.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 *
 * @var string[] $enabled_statuses  Estados activos (inyectados por AdminMenu::render_tab).
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
    <p><?php esc_html_e( 'Activa los estados de pedido que enviarán un mensaje de WhatsApp automático al cliente.', 'wa-notifier' ); ?></p>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <input type="hidden" name="action" value="wa_notifier_save_notifications">
    <?php wp_nonce_field( 'wa_notifier_save_notifications' ); ?>

    <div class="wan-status-list">
        <?php foreach ( $statuses as $key => $status ) :
            $is_enabled = in_array( $key, $enabled_statuses, true );
            $input_id   = 'wan-status-' . esc_attr( $key );
        ?>
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
        <?php endforeach; ?>
    </div>

    <p class="submit" style="padding:0;margin:0;">
        <button type="submit" class="button button-primary waxap-btn-primary">
            <?php esc_html_e( 'Guardar cambios', 'wa-notifier' ); ?>
        </button>
    </p>
</form>
