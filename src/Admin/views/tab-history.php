<?php
/**
 * Vista: tab Historial — mensajes WhatsApp enviados.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 *
 * @var array{data: array<int,array<string,mixed>>, total: int, limit: int, offset: int}|null $log
 * @var string|null $error
 * @var int         $page
 * @var int         $limit
 * @var int         $offset
 */

declare(strict_types=1);

use WaNotifier\Settings;

/** @var string $slug */
$slug = 'wa-notifier';

$status_labels = [
    'sent'    => [ 'label' => 'Enviado',   'class' => 'waxap-badge--sent' ],
    'failed'  => [ 'label' => 'Error',     'class' => 'waxap-badge--failed' ],
    'skipped' => [ 'label' => 'Omitido',   'class' => 'waxap-badge--skipped' ],
    'queued'  => [ 'label' => 'En cola',   'class' => 'waxap-badge--queued' ],
];
?>

<div class="waxap-section">
    <h2><?php esc_html_e( 'Historial de mensajes WhatsApp', 'wa-notifier' ); ?></h2>
    <p class="description"><?php esc_html_e( 'Registro de notificaciones enviadas desde esta tienda.', 'wa-notifier' ); ?></p>

    <?php if ( $error !== null ) : ?>
        <div class="notice notice-error inline">
            <p><?php echo esc_html( $error ); ?></p>
        </div>
    <?php elseif ( $log === null || empty( $log['data'] ) ) : ?>
        <div class="notice notice-info inline">
            <p><?php esc_html_e( 'No hay mensajes registrados todavía.', 'wa-notifier' ); ?></p>
        </div>
    <?php else :
        $total      = (int) $log['total'];
        $rows       = $log['data'];
        $total_pages = (int) ceil( $total / $limit );
    ?>

        <table class="wp-list-table widefat fixed striped waxap-history-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Fecha', 'wa-notifier' ); ?></th>
                    <th><?php esc_html_e( 'Teléfono', 'wa-notifier' ); ?></th>
                    <th><?php esc_html_e( 'Pedido', 'wa-notifier' ); ?></th>
                    <th><?php esc_html_e( 'Estado pedido', 'wa-notifier' ); ?></th>
                    <th><?php esc_html_e( 'Resultado', 'wa-notifier' ); ?></th>
                    <th><?php esc_html_e( 'Mensaje / Error', 'wa-notifier' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rows as $row ) :
                    $status     = (string) ( $row['status'] ?? 'queued' );
                    $badge      = $status_labels[ $status ] ?? [ 'label' => $status, 'class' => 'waxap-badge--queued' ];
                    $created_at = ! empty( $row['createdAt'] )
                        ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( (string) $row['createdAt'] ) )
                        : '—';
                    $order_id   = (string) ( $row['orderId'] ?? '' );
                    $order_link = $order_id
                        ? get_edit_post_link( (int) $order_id )
                        : '';
                    $cell_text = '';
                    if ( $status === 'sent' || $status === 'failed' ) {
                        $cell_text = (string) ( $row['messageSent'] ?? '' );
                    }
                    if ( $status === 'failed' || $status === 'skipped' ) {
                        $skip = (string) ( $row['skipReason'] ?? '' );
                        if ( $skip !== '' ) {
                            $cell_text = $skip;
                        }
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html( $created_at ); ?></td>
                        <td><?php echo esc_html( (string) ( $row['customerPhone'] ?? '' ) ); ?></td>
                        <td>
                            <?php if ( $order_link && $order_id ) : ?>
                                <a href="<?php echo esc_url( $order_link ); ?>">#<?php echo esc_html( $order_id ); ?></a>
                            <?php elseif ( $order_id ) : ?>
                                #<?php echo esc_html( $order_id ); ?>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( (string) ( $row['orderStatus'] ?? '' ) ); ?></td>
                        <td>
                            <span class="waxap-badge <?php echo esc_attr( $badge['class'] ); ?>">
                                <?php echo esc_html( $badge['label'] ); ?>
                            </span>
                        </td>
                        <td class="waxap-history-msg">
                            <?php echo esc_html( $cell_text ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( $total_pages > 1 ) :
            $base_url = admin_url( 'admin.php?page=' . $slug . '&tab=history' );
        ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php
                    /* translators: %d: number of items */
                    printf( esc_html__( '%d mensajes', 'wa-notifier' ), $total );
                    ?>
                </span>
                <span class="pagination-links">
                    <?php if ( $page > 1 ) : ?>
                        <a class="first-page button" href="<?php echo esc_url( add_query_arg( 'paged', 1, $base_url ) ); ?>">«</a>
                        <a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1, $base_url ) ); ?>">‹</a>
                    <?php else : ?>
                        <span class="first-page button disabled">«</span>
                        <span class="prev-page button disabled">‹</span>
                    <?php endif; ?>

                    <span class="paging-input">
                        <?php echo esc_html( $page ); ?> / <?php echo esc_html( $total_pages ); ?>
                    </span>

                    <?php if ( $page < $total_pages ) : ?>
                        <a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1, $base_url ) ); ?>">›</a>
                        <a class="last-page button" href="<?php echo esc_url( add_query_arg( 'paged', $total_pages, $base_url ) ); ?>">»</a>
                    <?php else : ?>
                        <span class="next-page button disabled">›</span>
                        <span class="last-page button disabled">»</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<style>
.waxap-history-table { margin-top: 1rem; }
.waxap-history-msg { max-width: 320px; white-space: pre-wrap; word-break: break-word; font-size: 12px; color: #555; }
.waxap-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.waxap-badge--sent    { background: #d1fae5; color: #065f46; }
.waxap-badge--failed  { background: #fee2e2; color: #991b1b; }
.waxap-badge--skipped { background: #fef3c7; color: #92400e; }
.waxap-badge--queued  { background: #e5e7eb; color: #374151; }
</style>
