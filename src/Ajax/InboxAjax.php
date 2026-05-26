<?php
/**
 * Handlers AJAX para la bandeja de entrada WhatsApp.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Api\WrapperClient;

/**
 * Registra y gestiona las acciones AJAX del inbox de mensajes.
 */
final class InboxAjax {

	/** Registra todas las acciones AJAX del inbox. */
	public function register(): void {
		add_action( 'wp_ajax_wa_notifier_inbox_conversations', [ $this, 'get_conversations' ] );
		add_action( 'wp_ajax_wa_notifier_inbox_thread', [ $this, 'get_thread' ] );
		add_action( 'wp_ajax_wa_notifier_inbox_send', [ $this, 'send_message' ] );
		add_action( 'wp_ajax_wa_notifier_inbox_read', [ $this, 'mark_read' ] );
	}

	/** Devuelve la lista de conversaciones actualizada (usada por el polling). */
	public function get_conversations(): void {
		check_ajax_referer( 'wa_notifier_ajax' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'No autorizado.', 403 );
		}
		$result = ( new WrapperClient() )->get_inbox_conversations( 30 );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success( $result['data'] ?? [] );
	}

	/** Devuelve el hilo de mensajes con un número de teléfono. */
	public function get_thread(): void {
		check_ajax_referer( 'wa_notifier_ajax' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'No autorizado.', 403 );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized immediately
		$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		if ( ! $phone ) {
			wp_send_json_error( 'Teléfono requerido.' );
		}
		$result = ( new WrapperClient() )->get_inbox_thread( $phone );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success( $result['data'] ?? [] );
	}

	/** Envía un mensaje WhatsApp. */
	public function send_message(): void {
		check_ajax_referer( 'wa_notifier_ajax' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'No autorizado.', 403 );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized immediately
		$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$text  = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! $phone || ! $text ) {
			wp_send_json_error( 'Teléfono y mensaje requeridos.' );
		}
		$result = ( new WrapperClient() )->send_inbox_message( $phone, $text );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success();
	}

	/** Marca una conversación como leída. */
	public function mark_read(): void {
		check_ajax_referer( 'wa_notifier_ajax' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'No autorizado.', 403 );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized immediately
		$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		if ( ! $phone ) {
			wp_send_json_error( 'Teléfono requerido.' );
		}
		( new WrapperClient() )->mark_inbox_read( $phone );
		wp_send_json_success();
	}
}
