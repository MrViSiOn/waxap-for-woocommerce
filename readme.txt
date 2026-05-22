=== WA Notifier for WooCommerce ===
Contributors: drappsinfo
Tags: woocommerce, whatsapp, notifications, order-status, transactional-messaging
Requires at least: 6.2
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send transactional WhatsApp notifications to your WooCommerce customers about their order status. The merchant brings their own number.

== Description ==

WA Notifier sends WhatsApp notifications to your customers about order status changes (paid, shipped, delivered, etc.) without needing the official WhatsApp Business API.

* **Bring your own number.** You scan a QR with the WhatsApp account you want to use. No SIM cards needed.
* **15-minute setup.** Everything inside your WordPress admin.
* **Customer-initiated conversations.** The plugin adds a wa.me button to your WC transactional emails. Your customers tap it and start the chat → reduces ban risk dramatically.
* **Multilingual.** Spanish, English, Portuguese-BR included.

== Installation ==

1. Install the plugin from the WordPress repository.
2. Create your WA Notifier account from the plugin (or paste an existing API key).
3. Click "Link WhatsApp number" and scan the QR with your phone.
4. Customize your message templates.
5. Done. Status changes in WooCommerce will trigger WhatsApp notifications.

== Frequently Asked Questions ==

= Do I need a WhatsApp Business API account? =
No. You use any WhatsApp number you already own.

= Is there a risk of being banned by WhatsApp? =
This plugin uses an unofficial WhatsApp gateway. There is a small risk of being banned, mitigated by our "customer-initiated" pattern. We recommend using a dedicated business number, not your personal one.

= Is the SaaS service free? =
The plugin is free. The SaaS service has a free 14-day trial and paid plans from €9/month.

== Changelog ==

= 0.1.0 =
* Initial release (planned).

== Privacy ==

This plugin sends order metadata and customer phone numbers to the WA Notifier SaaS service. The customer must opt-in via a checkbox at checkout. See our Privacy Policy: https://wanotifier.example/privacy
