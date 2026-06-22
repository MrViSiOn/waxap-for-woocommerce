<?php
/**
 * Engancha los cambios de estado de pedido WooCommerce y los envía al wrapper.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Orders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Api\WrapperClient;
use WaNotifier\Settings;
use WC_Order;

/**
 * Escucha cambios de estado en pedidos WooCommerce y los envía al wrapper Waxap.
 */
final class OrderEvents {

	/** Registra el hook de cambio de estado de pedido. */
	public function register(): void {
		add_action( 'woocommerce_order_status_changed', [ $this, 'on_status_changed' ], 10, 4 );
	}

	/**
	 * Procesa el cambio de estado y envía el evento al wrapper si corresponde.
	 *
	 * @param int      $order_id    ID del pedido.
	 * @param string   $from_status Estado anterior (sin prefijo 'wc-').
	 * @param string   $to_status   Estado nuevo (sin prefijo 'wc-').
	 * @param WC_Order $order       Objeto del pedido.
	 */
	public function on_status_changed( int $order_id, string $from_status, string $to_status, WC_Order $order ): void {
		if ( ! Settings::is_connected() || ! Settings::has_session() ) {
			return;
		}

		$notify_raw = Settings::get( 'notify_statuses' );
		$enabled    = array_filter( explode( ',', $notify_raw ) );

		if ( ! empty( $enabled ) && ! in_array( $to_status, $enabled, true ) ) {
			return;
		}

		$phone = self::normalize_phone( $order->get_billing_phone() );
		if ( ! $phone ) {
			return;
		}

		// Pedidos nuevos llevan el meta del checkbox del checkout.
		// Pedidos anteriores a la activación del checkbox no tienen el meta → asumimos opt-in.
		$opt_in_meta = $order->get_meta( '_wa_notifier_opt_in', true );
		$opt_in      = ( '' === $opt_in_meta ) ? true : ( '1' === $opt_in_meta );

		$first    = $order->get_billing_first_name();
		$last     = $order->get_billing_last_name();
		$name_raw = trim( $first . ' ' . $last );
		$name     = $name_raw ? $name_raw : 'Cliente';

		$payload = [
			'orderId'       => (string) $order_id,
			'orderStatus'   => $to_status,
			'customerPhone' => $phone,
			'customerName'  => $name,
			'whatsappOptIn' => $opt_in,
			'siteUrl'       => home_url(),
		];

		$message = $this->resolve_template( $order, $to_status );
		if ( $message ) {
			$payload['message'] = $message;
		}

		// Incluir ventana 24h solo si el cliente escribió antes.
		$last_inbound = $order->get_meta( '_wa_notifier_last_inbound_at', true );
		if ( $last_inbound ) {
			$payload['lastInboundAt'] = $last_inbound;
		}

		// Defensa cliente: no reenviar si ya notificamos este estado para este pedido.
		$sent_meta = '_waxap_notified_' . $to_status;
		if ( $order->get_meta( $sent_meta, true ) ) {
			return;
		}

		$result = ( new WrapperClient() )->send_event( $payload );
		if ( ! is_wp_error( $result ) ) {
			$order->update_meta_data( $sent_meta, '1' );
			$order->save();
			return;
		}

		// No silenciar el fallo: dejar traza en WooCommerce → Estado → Registros (source "waxap").
		// El meta de idempotencia NO se marca, de modo que un reintento manual del estado
		// volverá a intentar el envío.
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error(
				sprintf(
					'Fallo al notificar el pedido #%1$d (estado "%2$s"): %3$s',
					$order_id,
					$to_status,
					$result->get_error_message()
				),
				[ 'source' => 'waxap' ]
			);
		}
	}

	/**
	 * Normaliza un número de teléfono añadiendo el prefijo de país si es necesario.
	 *
	 * @param string $raw Número de teléfono en crudo.
	 * @return string Número normalizado solo con dígitos, o cadena vacía si inválido.
	 */
	private static function normalize_phone( string $raw ): string {
		$digits = preg_replace( '/\D/', '', $raw );
		if ( ! $digits ) {
			return '';
		}
		$digits           = ltrim( $digits, '0' );
		$country_code_val = Settings::get( 'phone_country_code' );
		$country_code     = $country_code_val ? $country_code_val : '34';
		if ( ! str_starts_with( $digits, $country_code ) ) {
			$digits = $country_code . $digits;
		}
		return $digits;
	}

	/**
	 * Resuelve la plantilla de mensaje para el estado de pedido dado.
	 *
	 * @param WC_Order $order  Objeto del pedido.
	 * @param string   $status Clave de estado del pedido (sin prefijo 'wc-').
	 * @return string Mensaje con variables sustituidas, o cadena vacía si no hay plantilla.
	 */
	private function resolve_template( WC_Order $order, string $status ): string {
		$template = Settings::get( 'template_' . $status );
		if ( '' === $template ) {
			return '';
		}

		$statuses     = wc_get_order_statuses();
		$status_label = $statuses[ 'wc-' . $status ] ?? $status;

		$full_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		return str_replace(
			[ '{nombre}', '{pedido}', '{estado}', '{total}', '{enlace}' ],
			[
				$full_name ? $full_name : 'Cliente',
				(string) $order->get_id(),
				$status_label,
				html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total() ) ) ),
				$order->get_view_order_url(),
			],
			$template
		);
	}
}
