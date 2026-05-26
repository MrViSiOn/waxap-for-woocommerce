<?php
/**
 * Vista: tab Mensajes — bandeja de entrada WhatsApp.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 *
 * @var array<int,array<string,mixed>>|null $conversations Lista de conversaciones o null si hay error.
 * @var string|null                         $error         Mensaje de error de la API o null.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="waxap-section">
	<h2><?php esc_html_e( 'Mensajes', 'waxap-for-woocommerce' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Bandeja de entrada de WhatsApp. Responde a tus clientes directamente desde aquí.', 'waxap-for-woocommerce' ); ?></p>

	<?php if ( null !== $error ) : ?>
		<div class="notice notice-error inline">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php else : ?>

	<div id="waxap-inbox">

		<!-- Sidebar: lista de conversaciones -->
		<div class="waxap-inbox-sidebar">
			<div id="waxap-conv-list">
				<?php if ( empty( $conversations ) ) : ?>
					<div class="waxap-conv-empty"><?php esc_html_e( 'Sin conversaciones todavía.', 'waxap-for-woocommerce' ); ?></div>
				<?php else : ?>
					<?php foreach ( $conversations as $conv ) : ?>
						<?php
						$phone       = (string) ( $conv['phone'] ?? '' );
						$unread      = (int) ( $conv['unreadCount'] ?? 0 );
						$last_msg    = is_array( $conv['lastMessage'] ?? null ) ? $conv['lastMessage'] : null;
						$preview_raw = $last_msg ? (string) ( $last_msg['body'] ?? '' ) : '';
						$preview     = mb_strlen( $preview_raw ) > 45
							? mb_substr( $preview_raw, 0, 45 ) . '…'
							: ( $preview_raw ?: '—' );
						$time_iso    = $last_msg ? (string) ( $last_msg['createdAt'] ?? '' ) : '';
						$initial     = mb_strtoupper( mb_substr( $phone, 0, 1 ) );
						?>
						<div class="waxap-conv-item" data-phone="<?php echo esc_attr( $phone ); ?>">
							<div class="waxap-conv-avatar"><?php echo esc_html( $initial ); ?></div>
							<div class="waxap-conv-info">
								<div class="waxap-conv-phone">+<?php echo esc_html( $phone ); ?></div>
								<div class="waxap-conv-preview"><?php echo esc_html( $preview ); ?></div>
							</div>
							<div class="waxap-conv-meta">
								<?php if ( $time_iso ) : ?>
									<span class="waxap-conv-time" data-iso="<?php echo esc_attr( $time_iso ); ?>"></span>
								<?php endif; ?>
								<?php if ( $unread > 0 ) : ?>
									<span class="waxap-unread-badge"><?php echo esc_html( (string) $unread ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<!-- Panel principal: hilo de mensajes -->
		<div class="waxap-inbox-main">
			<div id="waxap-inbox-empty">
				<span class="dashicons dashicons-format-chat"></span>
				<span><?php esc_html_e( 'Selecciona una conversación para verla', 'waxap-for-woocommerce' ); ?></span>
			</div>

			<div id="waxap-thread" style="display:none">
				<div class="waxap-thread-header">
					<span class="dashicons dashicons-whatsapp"></span>
					<span id="waxap-thread-header-phone"></span>
				</div>
				<div id="waxap-thread-messages"></div>
				<div class="waxap-thread-form">
					<textarea id="waxap-send-text" placeholder="<?php esc_attr_e( 'Escribe un mensaje… (Ctrl+Enter para enviar)', 'waxap-for-woocommerce' ); ?>"></textarea>
					<button id="waxap-send-btn" type="button"><?php esc_html_e( 'Enviar', 'waxap-for-woocommerce' ); ?></button>
				</div>
			</div>
		</div>

	</div><!-- #waxap-inbox -->

	<?php endif; ?>
</div>

<style>
#waxap-inbox {
	display: flex;
	height: 620px;
	border: 1px solid #dcdcde;
	border-radius: 4px;
	overflow: hidden;
	background: #fff;
	margin-top: 1rem;
}
.waxap-inbox-sidebar {
	width: 280px;
	border-right: 1px solid #dcdcde;
	overflow-y: auto;
	flex-shrink: 0;
	background: #fafafa;
}
.waxap-inbox-main {
	flex: 1;
	display: flex;
	flex-direction: column;
	min-width: 0;
}
.waxap-conv-item {
	padding: 12px 14px;
	border-bottom: 1px solid #f0f0f0;
	cursor: pointer;
	display: flex;
	align-items: flex-start;
	gap: 10px;
	transition: background 0.1s;
}
.waxap-conv-item:hover { background: #f0f0f1; }
.waxap-conv-item.active { background: #e8f5e9; }
.waxap-conv-avatar {
	width: 38px;
	height: 38px;
	border-radius: 50%;
	background: #25d366;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-weight: 700;
	flex-shrink: 0;
	font-size: 15px;
}
.waxap-conv-info { flex: 1; min-width: 0; }
.waxap-conv-phone { font-weight: 600; font-size: 13px; color: #1d2327; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.waxap-conv-preview { font-size: 12px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
.waxap-conv-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.waxap-conv-time { font-size: 11px; color: #aaa; }
.waxap-unread-badge {
	background: #25d366;
	color: #fff;
	border-radius: 10px;
	padding: 1px 7px;
	font-size: 11px;
	font-weight: 700;
}
.waxap-conv-empty { padding: 20px 14px; color: #888; font-size: 13px; }
#waxap-inbox-empty {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	color: #aaa;
	font-size: 14px;
	gap: 10px;
}
#waxap-inbox-empty .dashicons { font-size: 52px; width: 52px; height: 52px; color: #ddd; }
#waxap-thread { display: flex; flex-direction: column; flex: 1; }
.waxap-thread-header {
	padding: 12px 16px;
	border-bottom: 1px solid #dcdcde;
	background: #f9fafb;
	font-weight: 600;
	font-size: 14px;
	display: flex;
	align-items: center;
	gap: 8px;
	color: #1d2327;
	flex-shrink: 0;
}
.waxap-thread-header .dashicons { color: #25d366; }
#waxap-thread-messages {
	flex: 1;
	overflow-y: auto;
	padding: 16px;
	display: flex;
	flex-direction: column;
	gap: 6px;
	background: #ece5dd;
}
.waxap-msg {
	max-width: 65%;
	padding: 7px 12px;
	border-radius: 8px;
	font-size: 13px;
	line-height: 1.5;
	word-break: break-word;
}
.waxap-msg-inbound {
	background: #fff;
	border-radius: 0 8px 8px 8px;
	align-self: flex-start;
	box-shadow: 0 1px 1px rgba(0,0,0,.06);
}
.waxap-msg-outbound {
	background: #dcf8c6;
	border-radius: 8px 0 8px 8px;
	align-self: flex-end;
	box-shadow: 0 1px 1px rgba(0,0,0,.06);
}
.waxap-msg-time { font-size: 10px; color: #999; margin-top: 3px; text-align: right; }
.waxap-thread-form {
	padding: 10px 14px;
	border-top: 1px solid #dcdcde;
	display: flex;
	gap: 8px;
	background: #fff;
	flex-shrink: 0;
	align-items: flex-end;
}
.waxap-thread-form textarea {
	flex: 1;
	resize: none;
	padding: 8px 10px;
	border: 1px solid #dcdcde;
	border-radius: 4px;
	font-size: 13px;
	height: 56px;
	font-family: inherit;
	line-height: 1.4;
}
.waxap-thread-form textarea:focus { border-color: #25d366; outline: none; box-shadow: 0 0 0 1px #25d366; }
#waxap-send-btn {
	padding: 8px 18px;
	background: #25d366;
	color: #fff;
	border: none;
	border-radius: 4px;
	cursor: pointer;
	font-weight: 600;
	font-size: 13px;
	height: 56px;
	white-space: nowrap;
}
#waxap-send-btn:hover { background: #20bc5a; }
#waxap-send-btn:disabled { background: #a5d6b7; cursor: default; }
.waxap-thread-loading, .waxap-thread-error, .waxap-thread-empty-msgs {
	text-align: center;
	padding: 20px;
	color: #888;
	font-size: 13px;
}
</style>

<script>
// Formatea los timestamps ISO a hora local al cargar la página (server render)
document.addEventListener( 'DOMContentLoaded', function() {
	document.querySelectorAll( '.waxap-conv-time[data-iso]' ).forEach( function( el ) {
		var iso = el.getAttribute( 'data-iso' );
		if ( ! iso ) return;
		try {
			var d = new Date( iso );
			var now = new Date();
			var isToday = d.toDateString() === now.toDateString();
			el.textContent = isToday
				? d.toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit' } )
				: d.toLocaleDateString( [], { day: '2-digit', month: '2-digit' } );
		} catch ( e ) {}
	} );
} );
</script>
