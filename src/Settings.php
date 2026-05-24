<?php
/**
 * Gestión de opciones en wp_options.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier;

final class Settings {

    private const PREFIX = 'wa_notifier_';

    /** @var array<string,string> Valores por defecto. */
    private const DEFAULTS = [
        'wrapper_url'          => 'https://api.waxap.shop',
        'api_key'              => '',
        'tenant_id'            => '',
        'session_id'           => '',
        'hmac_secret'          => '',
        'phone_number'         => '',
        'phone_country_code'   => '34',
        'notify_statuses'      => 'processing,completed',
        'email_button_enabled' => '1',
        'email_button_text'    => '¿Tienes dudas? Escríbenos por WhatsApp',
        'email_button_prefill' => 'Hola, tengo una consulta sobre mi pedido #{pedido}',
        'template_processing'  => '¡Hola {nombre}! Hemos recibido tu pedido #{pedido} y lo estamos preparando. 🛒',
        'template_completed'   => '¡Hola {nombre}! Tu pedido #{pedido} ha sido completado. ✅ ¡Gracias por tu compra!',
        'template_on-hold'     => '¡Hola {nombre}! Tu pedido #{pedido} está pendiente de confirmación de pago. ⏳',
        'template_cancelled'   => '¡Hola {nombre}! Tu pedido #{pedido} ha sido cancelado. Si tienes dudas, contáctanos. ❌',
        'template_refunded'    => '¡Hola {nombre}! Hemos procesado el reembolso de tu pedido #{pedido}. 💰',
        'template_pending'     => '¡Hola {nombre}! Tu pedido #{pedido} está pendiente de pago. Complétalo para que podamos procesarlo. ⏳',
        'template_failed'      => '¡Hola {nombre}! El pago de tu pedido #{pedido} no se completó. Por favor, inténtalo de nuevo. ❌',
    ];

    public static function get( string $key ): string {
        $default = self::DEFAULTS[ $key ] ?? '';
        return (string) get_option( self::PREFIX . $key, $default );
    }

    public static function set( string $key, string $value ): void {
        update_option( self::PREFIX . $key, $value, false );
    }

    public static function delete( string $key ): void {
        delete_option( self::PREFIX . $key );
    }

    public static function is_connected(): bool {
        return '' !== self::get( 'api_key' );
    }

    public static function disconnect(): void {
        foreach ( [ 'api_key', 'tenant_id', 'session_id', 'hmac_secret', 'phone_number' ] as $key ) {
            self::set( $key, '' );
        }
    }

    public static function has_session(): bool {
        return '' !== self::get( 'session_id' );
    }
}
