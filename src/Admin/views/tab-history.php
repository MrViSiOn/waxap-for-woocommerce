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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Settings;

/* @var string $waxap_slug Slug de la página de administración del plugin. */
$waxap_slug = 'waxap';

$waxap_status_labels = [
	'sent'    => [
		'label' => 'Enviado',
		'class' => 'waxap-badge--sent',
	],
	'failed'  => [
		'label' => 'Error',
		'class' => 'waxap-badge--failed',
	],
	'skipped' => [
		'label' => 'Omitido',
		'class' => 'waxap-badge--skipped',
	],
	'queued'  => [
		'label' => 'En cola',
		'class' => 'waxap-badge--queued',
	],
];
?>

<div class="waxap-section">
	<h2><?php esc_html_e( 'Historial de mensajes WhatsApp', 'waxap-for-woocommerce' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Registro de notificaciones enviadas desde esta tienda.', 'waxap-for-woocommerce' ); ?></p>

	<?php if ( null !== $error ) : ?>
		<div class="notice notice-error inline">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php elseif ( null === $log || empty( $log['data'] ) ) : ?>
		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'No hay mensajes registrados todavía.', 'waxap-for-woocommerce' ); ?></p>
		</div>
		<?php
	else :
		$waxap_total       = (int) $log['total'];
		$waxap_rows        = $log['data'];
		$waxap_total_pages = (int) ceil( $waxap_total / $limit );
		?>

		<table class="wp-list-table widefat fixed striped waxap-history-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Fecha', 'waxap-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Teléfono', 'waxap-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Pedido', 'waxap-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Estado pedido', 'waxap-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Resultado', 'waxap-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Mensaje / Error', 'waxap-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $waxap_rows as $waxap_row ) :
					$waxap_row_stat   = (string) ( $waxap_row['status'] ?? 'queued' );
					$waxap_badge      = $waxap_status_labels[ $waxap_row_stat ] ?? [
						'label' => $waxap_row_stat,
						'class' => 'waxap-badge--queued',
					];
					$waxap_created_at = ! empty( $waxap_row['createdAt'] )
						? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( (string) $waxap_row['createdAt'] ) )
						: '—';
					$waxap_order_id   = (string) ( $waxap_row['orderId'] ?? '' );
					$waxap_order_link = $waxap_order_id
						? get_edit_post_link( (int) $waxap_order_id )
						: '';
					$waxap_cell_text  = '';
					if ( 'sent' === $waxap_row_stat || 'failed' === $waxap_row_stat ) {
						$waxap_cell_text = trim( (string) ( $waxap_row['messageSent'] ?? '' ) );
					}
					if ( 'failed' === $waxap_row_stat || 'skipped' === $waxap_row_stat ) {
						$waxap_skip = trim( (string) ( $waxap_row['skipReason'] ?? '' ) );
						if ( '' !== $waxap_skip ) {
							$waxap_cell_text = $waxap_skip;
						}
					}
					?>
					<tr>
						<td><?php echo esc_html( $waxap_created_at ); ?></td>
						<td><?php echo esc_html( (string) ( $waxap_row['customerPhone'] ?? '' ) ); ?></td>
						<td>
							<?php if ( $waxap_order_link && $waxap_order_id ) : ?>
								<a href="<?php echo esc_url( $waxap_order_link ); ?>">#<?php echo esc_html( $waxap_order_id ); ?></a>
							<?php elseif ( $waxap_order_id ) : ?>
								#<?php echo esc_html( $waxap_order_id ); ?>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( (string) ( $waxap_row['orderStatus'] ?? '' ) ); ?></td>
						<td>
							<span class="waxap-badge <?php echo esc_attr( $waxap_badge['class'] ); ?>">
								<?php echo esc_html( $waxap_badge['label'] ); ?>
							</span>
						</td>
						<td class="waxap-history-msg">
							<?php echo esc_html( $waxap_cell_text ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php
		if ( $waxap_total_pages > 1 ) :
			$waxap_base_url = admin_url( 'admin.php?page=' . $waxap_slug . '&tab=history' );
			?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					/* translators: %d: number of items */
					printf( esc_html__( '%d mensajes', 'waxap-for-woocommerce' ), (int) $waxap_total );
					?>
				</span>
				<span class="pagination-links">
					<?php if ( $page > 1 ) : ?>
						<a class="first-page button" href="<?php echo esc_url( add_query_arg( 'paged', 1, $waxap_base_url ) ); ?>">«</a>
						<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1, $waxap_base_url ) ); ?>">‹</a>
					<?php else : ?>
						<span class="first-page button disabled">«</span>
						<span class="prev-page button disabled">‹</span>
					<?php endif; ?>

					<span class="paging-input">
						<?php echo esc_html( $page ); ?> / <?php echo esc_html( $waxap_total_pages ); ?>
					</span>

					<?php if ( $page < $waxap_total_pages ) : ?>
						<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1, $waxap_base_url ) ); ?>">›</a>
						<a class="last-page button" href="<?php echo esc_url( add_query_arg( 'paged', $waxap_total_pages, $waxap_base_url ) ); ?>">»</a>
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
.waxap-history-msg { max-width: 320px; white-space: normal !important; word-break: break-word; font-size: 12px; color: #555; text-align: left !important; vertical-align: top !important; }
.waxap-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.waxap-badge--sent    { background: #d1fae5; color: #065f46; }
.waxap-badge--failed  { background: #fee2e2; color: #991b1b; }
.waxap-badge--skipped { background: #fef3c7; color: #92400e; }
.waxap-badge--queued  { background: #e5e7eb; color: #374151; }
</style>
