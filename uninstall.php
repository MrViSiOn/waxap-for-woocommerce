<?php
/**
 * Plugin uninstall hook — removes all plugin data from wp_options.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$waxap_option_keys = array(
	'wrapper_url',
	'api_key',
	'tenant_id',
	'session_id',
	'hmac_secret',
	'phone_number',
	'phone_country_code',
	'notify_statuses',
	'email_button_enabled',
	'email_button_text',
	'email_button_prefill',
	'migrated_status_keys_v1',
	'template_processing',
	'template_completed',
	'template_on-hold',
	'template_cancelled',
	'template_refunded',
	'template_pending',
	'template_failed',
);

foreach ( $waxap_option_keys as $waxap_key ) {
	delete_option( 'wa_notifier_' . $waxap_key );
}
