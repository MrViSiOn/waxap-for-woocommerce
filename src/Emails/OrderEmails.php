<?php
/**
 * Inyecta el botón wa.me en los emails transaccionales de WooCommerce.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Settings;
use WC_Order;

/**
 * Inyecta el botón wa.me en los emails transaccionales de WooCommerce.
 */
final class OrderEmails {

	/** Registra el hook para inyectar el botón WhatsApp en los emails de pedido. */
	public function register(): void {
		add_action( 'woocommerce_email_after_order_table', [ $this, 'add_whatsapp_button' ], 10, 4 );
	}

	/** Registra el shortcode [waxap_whatsapp_button]. */
	public function register_shortcode(): void {
		add_shortcode( 'waxap_whatsapp_button', [ $this, 'handle_shortcode' ] );
	}

	/**
	 * Handler del shortcode [waxap_whatsapp_button].
	 *
	 * Atributos opcionales:
	 *   order — número de pedido para sustituir {pedido} en el prefill.
	 *
	 * @param array<string,string>|string $atts Atributos del shortcode.
	 * @return string HTML del botón (vacío si el botón está desactivado o sin número).
	 */
	public function handle_shortcode( array|string $atts ): string {
		$a = shortcode_atts( [ 'order' => '' ], $atts, 'waxap_whatsapp_button' );
		return $this->build_button_html( '' !== $a['order'] ? (string) $a['order'] : null );
	}

	/**
	 * Añade el botón WhatsApp al final de la tabla de pedido en los emails al cliente.
	 *
	 * @param WC_Order $order         Objeto del pedido.
	 * @param bool     $sent_to_admin Indica si el email va dirigido al administrador.
	 * @param bool     $plain_text    Indica si el email es en formato texto plano.
	 */
	public function add_whatsapp_button( WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
		if ( $sent_to_admin || $plain_text ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — already escaped inside build_button_html.
		echo $this->build_button_html( (string) $order->get_order_number() );
	}

	/**
	 * Construye el HTML del botón wa.me.
	 *
	 * @param string|null $order_number Número de pedido para sustituir {pedido}; null lo elimina.
	 * @return string HTML listo para imprimir (cadena vacía si el botón está desactivado).
	 */
	private function build_button_html( ?string $order_number ): string {
		if ( '1' !== Settings::get( 'email_button_enabled' ) ) {
			return '';
		}

		$phone = Settings::get( 'phone_number' );
		if ( ! $phone ) {
			return '';
		}

		$clean_phone = preg_replace( '/\D/', '', $phone );
		$prefill_raw = Settings::get( 'email_button_prefill' );
		$prefill     = null !== $order_number
			? str_replace( '{pedido}', $order_number, $prefill_raw )
			: str_replace( '{pedido}', '', $prefill_raw );
		$prefill     = trim( $prefill );
		$url         = 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode( $prefill );

		return sprintf(
			'<p style="text-align:center;margin:24px 0 8px;">
                <a href="%1$s"
                   style="background-color:#25d366;color:#ffffff;padding:12px 28px;border-radius:4px;
                          text-decoration:none;font-size:14px;font-weight:bold;display:inline-block;">
                    %2$s
                </a>
            </p>',
			esc_url( $url ),
			esc_html( Settings::get( 'email_button_text' ) )
		);
	}
}
