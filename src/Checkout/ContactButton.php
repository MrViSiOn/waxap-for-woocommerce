<?php
/**
 * Botón wa.me "Ponte en contacto con nosotros" en el checkout y la página de gracias.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Checkout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WaNotifier\Settings;

/**
 * Inyecta un botón wa.me en el checkout y en la thank-you page cuando la opción
 * `contact_button_enabled` está activa. Refuerza el patrón "el cliente inicia la
 * conversación". Reutiliza el texto y el mensaje prefabricado configurados para el
 * botón de los emails (`email_button_text` / `email_button_prefill`).
 */
final class ContactButton {

	/** Registra los hooks de checkout y thank-you de WooCommerce. */
	public function register(): void {
		add_action( 'woocommerce_after_checkout_form', [ $this, 'render_on_checkout' ], 20 );
		add_action( 'woocommerce_thankyou', [ $this, 'render_on_thankyou' ], 20 );
	}

	/** Renderiza el botón tras el formulario de checkout (sin número de pedido). */
	public function render_on_checkout(): void {
		// build_html escapa los datos dinámicos (esc_url/esc_html); wp_kses_post
		// sanea además la estructura HTML estática.
		echo wp_kses_post( $this->build_html( null ) );
	}

	/**
	 * Renderiza el botón en la página de confirmación del pedido.
	 *
	 * @param int $order_id ID del pedido recién realizado.
	 */
	public function render_on_thankyou( int $order_id ): void {
		$order        = wc_get_order( $order_id );
		$order_number = $order ? (string) $order->get_order_number() : null;
		echo wp_kses_post( $this->build_html( $order_number ) );
	}

	/**
	 * Construye el HTML del botón wa.me para el front.
	 *
	 * @param string|null $order_number Número de pedido para sustituir {pedido}; null lo elimina.
	 * @return string HTML listo para imprimir (cadena vacía si la opción está desactivada o falta config).
	 */
	private function build_html( ?string $order_number ): string {
		if ( '1' !== Settings::get( 'contact_button_enabled' ) ) {
			return '';
		}
		if ( ! Settings::is_connected() || ! Settings::has_session() ) {
			return '';
		}

		$phone = Settings::get( 'phone_number' );
		if ( ! $phone ) {
			return '';
		}

		$clean_phone = preg_replace( '/\D/', '', $phone );
		$prefill     = trim( str_replace( '{pedido}', $order_number ?? '', Settings::get( 'email_button_prefill' ) ) );
		$url         = 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode( $prefill );

		return sprintf(
			'<div class="waxap-contact-button" style="text-align:center;margin:24px 0;">
				<a href="%1$s" target="_blank" rel="noopener"
				   style="background-color:#25d366;color:#ffffff;padding:12px 28px;border-radius:6px;
				          text-decoration:none;font-size:15px;font-weight:bold;display:inline-block;">
					%2$s
				</a>
			</div>',
			esc_url( $url ),
			esc_html( Settings::get( 'email_button_text' ) )
		);
	}
}
