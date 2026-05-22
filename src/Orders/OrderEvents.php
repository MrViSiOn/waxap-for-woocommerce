<?php
/**
 * Engancha los cambios de estado de pedido WooCommerce y los envía al wrapper.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Orders;

use WaNotifier\Api\WrapperClient;
use WaNotifier\Settings;
use WC_Order;

final class OrderEvents {

    public function register(): void {
        add_action( 'woocommerce_order_status_changed', [ $this, 'on_status_changed' ], 10, 4 );
    }

    /**
     * @param int      $order_id
     * @param string   $from_status Estado anterior (sin prefijo 'wc-')
     * @param string   $to_status   Estado nuevo (sin prefijo 'wc-')
     * @param WC_Order $order
     */
    public function on_status_changed( int $order_id, string $from_status, string $to_status, WC_Order $order ): void {
        if ( ! Settings::is_connected() || ! Settings::has_session() ) {
            return;
        }

        $phone = preg_replace( '/\D/', '', $order->get_billing_phone() );
        if ( ! $phone ) {
            return;
        }

        // MVP: si el meta no está definido, asumimos opt-in true para las tiendas piloto.
        // Fase 1 añadirá checkbox de consentimiento en el checkout.
        $opt_in_meta = $order->get_meta( '_wa_notifier_opt_in', true );
        $opt_in      = ( $opt_in_meta === '' ) ? true : (bool) $opt_in_meta;

        $first = $order->get_billing_first_name();
        $last  = $order->get_billing_last_name();
        $name  = trim( $first . ' ' . $last ) ?: 'Cliente';

        $payload = [
            'orderId'       => (string) $order_id,
            'orderStatus'   => $to_status,
            'customerPhone' => $phone,
            'customerName'  => $name,
            'whatsappOptIn' => $opt_in,
        ];

        // Incluir ventana 24h solo si el cliente escribió antes
        $last_inbound = $order->get_meta( '_wa_notifier_last_inbound_at', true );
        if ( $last_inbound ) {
            $payload['lastInboundAt'] = $last_inbound;
        }

        ( new WrapperClient() )->send_event( $payload );
    }
}
