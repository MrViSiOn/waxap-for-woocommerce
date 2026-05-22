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
        'wrapper_url'  => 'http://host.docker.internal:3000',
        'api_key'      => '',
        'tenant_id'    => '',
        'session_id'   => '',
        'hmac_secret'  => '',
        'phone_number' => '',
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

    public static function has_session(): bool {
        return '' !== self::get( 'session_id' );
    }
}
