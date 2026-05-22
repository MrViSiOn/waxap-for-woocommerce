<?php
/**
 * Inyecta el botón wa.me en los emails transaccionales de WooCommerce.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Emails;

use WaNotifier\Settings;
use WC_Order;

final class OrderEmails {

    public function register(): void {
        // Fires after the order table in customer-facing HTML emails
        add_action( 'woocommerce_email_after_order_table', [ $this, 'add_whatsapp_button' ], 10, 4 );
    }

    /**
     * @param WC_Order  $order
     * @param bool      $sent_to_admin
     * @param bool      $plain_text
     * @param \WC_Email $email
     */
    public function add_whatsapp_button( WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
        // Only customer-facing HTML emails
        if ( $sent_to_admin || $plain_text ) {
            return;
        }

        $phone = Settings::get( 'phone_number' );
        if ( ! $phone ) {
            return;
        }

        $clean_phone  = preg_replace( '/\D/', '', $phone );
        $order_number = $order->get_order_number();

        /* translators: %s: order number */
        $prefill = sprintf(
            __( 'Hola, tengo una consulta sobre mi pedido #%s', 'wa-notifier' ),
            $order_number
        );

        $url = 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode( $prefill );

        printf(
            '<p style="text-align:center;margin:24px 0 8px;">
                <a href="%1$s"
                   style="background-color:#25d366;color:#ffffff;padding:12px 28px;border-radius:4px;
                          text-decoration:none;font-size:14px;font-weight:bold;display:inline-block;">
                    %2$s
                </a>
            </p>',
            esc_url( $url ),
            esc_html__( '¿Tienes dudas? Escríbenos por WhatsApp', 'wa-notifier' )
        );
    }
}
