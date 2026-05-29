<?php
/**
 * Gestión de opciones en wp_options.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestiona las opciones del plugin en wp_options con prefijo y valores por defecto.
 */
final class Settings {

	private const PREFIX = 'wa_notifier_';

	/**
	 * Valores por defecto de las opciones del plugin.
	 *
	 * @var array<string,string>
	 */
	private const DEFAULTS = [
		'wrapper_url'          => 'https://api.waxap.shop',
		'api_key'              => '',
		'tenant_id'            => '',
		'claim_token'          => '',
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

	/**
	 * Obtiene el valor de una opción del plugin.
	 *
	 * @param string $key Clave de la opción.
	 * @return string Valor almacenado o el valor por defecto.
	 */
	public static function get( string $key ): string {
		$default = self::DEFAULTS[ $key ] ?? '';
		return (string) get_option( self::PREFIX . $key, $default );
	}

	/**
	 * Corrige datos guardados con ltrim() en lugar de substr() para claves de estado WC.
	 * ltrim('wc-completed','wc-') devuelve 'ompleted'; ltrim('wc-cancelled','wc-') devuelve 'ancelled'.
	 * Solo se ejecuta una vez (guarda una flag en wp_options).
	 */
	public static function maybe_migrate_status_keys(): void {
		if ( get_option( self::PREFIX . 'migrated_status_keys_v1' ) ) {
			return;
		}

		$bad_good = [
			'ompleted' => 'completed',
			'ancelled' => 'cancelled',
		];

		// Fix notify_statuses.
		$raw      = (string) get_option( self::PREFIX . 'notify_statuses', '' );
		$statuses = array_filter( explode( ',', $raw ) );
		$fixed    = array_map( fn( $s ) => $bad_good[ $s ] ?? $s, $statuses );
		if ( $statuses !== $fixed ) {
			update_option( self::PREFIX . 'notify_statuses', implode( ',', $fixed ), false );
		}

		// Fix orphaned template keys.
		foreach ( $bad_good as $bad => $good ) {
			$bad_val  = get_option( self::PREFIX . 'template_' . $bad, null );
			$good_val = get_option( self::PREFIX . 'template_' . $good, null );
			if ( null !== $bad_val ) {
				if ( null === $good_val || '' === $good_val ) {
					update_option( self::PREFIX . 'template_' . $good, $bad_val, false );
				}
				delete_option( self::PREFIX . 'template_' . $bad );
			}
		}

		update_option( self::PREFIX . 'migrated_status_keys_v1', '1', false );
	}

	/**
	 * Guarda el valor de una opción del plugin.
	 *
	 * @param string $key   Clave de la opción.
	 * @param string $value Valor a almacenar.
	 */
	public static function set( string $key, string $value ): void {
		update_option( self::PREFIX . $key, $value, false );
	}

	/**
	 * Elimina una opción del plugin de wp_options.
	 *
	 * @param string $key Clave de la opción a eliminar.
	 */
	public static function delete( string $key ): void {
		delete_option( self::PREFIX . $key );
	}

	/** Indica si el plugin está conectado a un tenant (tiene API key). */
	public static function is_connected(): bool {
		return '' !== self::get( 'api_key' );
	}

	/** Elimina todas las credenciales del plugin (desconexión completa). */
	public static function disconnect(): void {
		foreach ( [ 'api_key', 'tenant_id', 'claim_token', 'session_id', 'hmac_secret', 'phone_number' ] as $key ) {
			self::set( $key, '' );
		}
	}

	/** Indica si existe una sesión WhatsApp activa guardada. */
	public static function has_session(): bool {
		return '' !== self::get( 'session_id' );
	}
}
