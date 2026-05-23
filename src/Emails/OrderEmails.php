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
        add_action( 'woocommerce_email_after_order_table', [ $this, 'add_whatsapp_button' ], 10, 4 );
    }

    /**
     * @param WC_Order  $order
     * @param bool      $sent_to_admin
     * @param bool      $plain_text
     * @param \WC_Email $email
     */
    public function add_whatsapp_button( WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
        if ( $sent_to_admin || $plain_text ) {
            return;
        }

        if ( '1' !== Settings::get( 'email_button_enabled' ) ) {
            return;
        }

        $phone = Settings::get( 'phone_number' );
        if ( ! $phone ) {
            return;
        }

        $clean_phone  = preg_replace( '/\D/', '', $phone );
        $order_number = $order->get_order_number();

        $prefill = str_replace( '{pedido}', $order_number, Settings::get( 'email_button_prefill' ) );
        $url     = 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode( $prefill );

        printf(
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
