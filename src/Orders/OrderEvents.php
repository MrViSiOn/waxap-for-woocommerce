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
        error_log( "[WaNotifier] on_status_changed #{$order_id}: {$from_status} → {$to_status}" );

        if ( ! Settings::is_connected() || ! Settings::has_session() ) {
            error_log( '[WaNotifier] SKIP: not connected or no session. connected=' . ( Settings::is_connected() ? '1' : '0' ) . ' session=' . ( Settings::has_session() ? '1' : '0' ) );
            return;
        }

        $notify_raw = Settings::get( 'notify_statuses' );
        $enabled    = array_filter( explode( ',', $notify_raw ) );
        error_log( "[WaNotifier] notify_statuses raw='{$notify_raw}' enabled=" . implode( ',', $enabled ) );

        if ( ! empty( $enabled ) && ! in_array( $to_status, $enabled, true ) ) {
            error_log( "[WaNotifier] SKIP: '{$to_status}' not in enabled list" );
            return;
        }

        $phone = self::normalize_phone( $order->get_billing_phone() );
        error_log( "[WaNotifier] phone raw='{$order->get_billing_phone()}' normalized='{$phone}'" );
        if ( ! $phone ) {
            error_log( '[WaNotifier] SKIP: empty phone after normalize' );
            return;
        }

        // Pedidos nuevos llevan el meta del checkbox del checkout.
        // Pedidos anteriores a la activación del checkbox no tienen el meta → asumimos opt-in.
        $opt_in_meta = $order->get_meta( '_wa_notifier_opt_in', true );
        $opt_in      = ( '' === $opt_in_meta ) ? true : ( '1' === $opt_in_meta );

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

        $message = $this->resolve_template( $order, $to_status );
        if ( $message ) {
            $payload['message'] = $message;
        }

        // Incluir ventana 24h solo si el cliente escribió antes
        $last_inbound = $order->get_meta( '_wa_notifier_last_inbound_at', true );
        if ( $last_inbound ) {
            $payload['lastInboundAt'] = $last_inbound;
        }

        error_log( '[WaNotifier] send_event payload=' . wp_json_encode( $payload ) );
        $result = ( new WrapperClient() )->send_event( $payload );
        if ( is_wp_error( $result ) ) {
            error_log( '[WaNotifier] send_event ERROR: ' . $result->get_error_message() );
        } else {
            error_log( '[WaNotifier] send_event OK' );
        }
    }

    private static function normalize_phone( string $raw ): string {
        $digits = preg_replace( '/\D/', '', $raw );
        if ( ! $digits ) {
            return '';
        }
        $digits       = ltrim( $digits, '0' );
        $country_code = Settings::get( 'phone_country_code' ) ?: '34';
        if ( ! str_starts_with( $digits, $country_code ) ) {
            $digits = $country_code . $digits;
        }
        return $digits;
    }

    private function resolve_template( WC_Order $order, string $status ): string {
        $template = Settings::get( 'template_' . $status );
        if ( '' === $template ) {
            return '';
        }

        $statuses     = wc_get_order_statuses();
        $status_label = $statuses[ 'wc-' . $status ] ?? $status;

        return str_replace(
            [ '{nombre}', '{pedido}', '{estado}', '{total}', '{enlace}' ],
            [
                trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ?: 'Cliente',
                (string) $order->get_id(),
                $status_label,
                html_entity_decode( strip_tags( wc_price( $order->get_total() ) ) ),
                $order->get_view_order_url(),
            ],
            $template
        );
    }
}
