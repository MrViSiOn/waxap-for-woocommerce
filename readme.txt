=== Waxap for WooCommerce ===
Contributors: drappsinfo
Tags: woocommerce, whatsapp, notifications, order-status, transactional-messaging
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send transactional WhatsApp notifications to your WooCommerce customers about their order status. The merchant brings their own number.

== Description ==

Waxap sends WhatsApp notifications to your customers about order status changes (paid, shipped, delivered, etc.) without needing the official WhatsApp Business API.

* **Bring your own number.** You scan a QR with the WhatsApp account you want to use. No SIM cards needed.
* **15-minute setup.** Everything inside your WordPress admin.
* **Customer-initiated conversations.** The plugin adds a wa.me button to your WC transactional emails. Your customers tap it and start the chat — reduces ban risk dramatically.
* **Multilingual.** Spanish, English, Portuguese-BR included.

== Installation ==

1. Install the plugin from the WordPress repository.
2. Create your Waxap account from the plugin (or paste an existing API key).
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

= What data does the plugin send externally? =
The plugin sends order metadata (order ID, status) and customer phone numbers to the Waxap SaaS service (api.waxap.shop). Customer phone numbers are only transmitted when the customer has opted in via the checkout consent checkbox.

== Changelog ==

= 0.3.2 =
* New: GitHub-based auto-updates — WordPress will notify you when a new version is available.
* New: WP.org listing assets (banner and icon images).
* Fix: Privacy policy URL updated.

= 0.3.1 =
* Fix: Stripe billing portal AJAX handler now uses correct nonce action.
* Fix: Usage card date calculation corrected.

= 0.3.0 =
* New: Billing portal integration — manage your Waxap subscription from the plugin.
* New: Usage card showing messages used / quota in the Connection tab.
* New: Settings migration for malformed status keys created by a previous bug.
* Improvement: Onboarding wizard now polls for payment confirmation automatically.

= 0.2.0 =
* New: Onboarding wizard with account registration, plan selection and Stripe checkout.
* New: Message history tab with paginated log from the Waxap API.
* New: WhatsApp opt-in checkbox at WooCommerce checkout (GDPR-friendly).
* New: Email branding tab — add a WhatsApp contact button to WC transactional emails.
* Improvement: Session page redesigned with cleaner QR flow and status badges.

= 0.1.0 =
* Initial release: connect to Waxap API, link a WhatsApp session via QR, configure notification templates per order status.

== Privacy ==

This plugin sends order metadata and customer phone numbers to the Waxap SaaS service (api.waxap.shop) to deliver WhatsApp notifications. The customer must opt-in via a checkbox at checkout before their phone number is transmitted. See our Privacy Policy: https://waxap.shop/privacidad/
