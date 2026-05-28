<?php
/**
 * Plugin Name:       Waxap for WooCommerce
 * Plugin URI:        https://wanotifier.example
 * Description:       Send transactional WhatsApp notifications to your WooCommerce customers. Bring your own number, scan a QR, done.
 * Version:           0.4.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            drappsinfo
 * Author URI:        https://drappsinfo.com
 * Text Domain:       waxap-for-woocommerce
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 8.0
 * WC tested up to:   8.9
 * Requires Plugins:  woocommerce
 *
 * @package WaNotifier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WA_NOTIFIER_VERSION', '0.3.6' );
define( 'WA_NOTIFIER_FILE', __FILE__ );
define( 'WA_NOTIFIER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WA_NOTIFIER_URL', plugin_dir_url( __FILE__ ) );

// HPOS compatibility declaration (WooCommerce 8+).
// Debe registrarse a nivel de archivo porque before_woocommerce_init se dispara
// desde dentro del propio plugins_loaded de WooCommerce.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

// Boot dentro de plugins_loaded para garantizar que WooCommerce ya está cargado.
add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'WC' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>'
						. wp_kses(
							sprintf(
								/* translators: %s: WooCommerce plugin name as a link */
								__( '<strong>Waxap</strong> requiere %s para funcionar. Por favor, instala y activa WooCommerce primero.', 'waxap-for-woocommerce' ),
								'<a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>'
							),
							[ 'strong' => [], 'a' => [ 'href' => [] ] ]
						)
						. '</p></div>';
				}
			);
			return;
		}

		require_once WA_NOTIFIER_PATH . 'vendor/autoload.php';
		( new \WaNotifier\Plugin() )->boot();
	}
);
