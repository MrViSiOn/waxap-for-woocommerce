=== Waxap for WooCommerce ===
Contributors: drappsinfo
Tags: woocommerce, whatsapp, notifications, order-status, transactional-messaging
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.4.7
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

= 0.4.7 =
* New: Optional "Contact us on WhatsApp" button on the checkout and order-confirmation (thank-you) pages. Enable it in Email branding; it reuses the same button text and prefilled message. Off by default.

= 0.4.6 =
* Change: Hidden the "Messages" (inbox) tab. Incoming messages showed the raw phone number, which doesn't match the merchant's saved contacts and caused confusion. The feature may return in a future version.

= 0.4.5 =
* Fix: "Visit plugin site" link now points to https://waxap.shop instead of a placeholder domain.

= 0.4.4 =
* Internal: order events now report their source platform (woocommerce) to the backend for per-platform metrics. No change in behaviour for merchants.

= 0.4.3 =
* New: The History tab now explains why a notification was skipped (no opt-in, monthly quota exceeded, outside the 24h window, no template) in plain language, plus send counters for the last 30 days and an "upgrade plan" call-to-action when the quota is exhausted.
* Fix: Linking a WhatsApp number no longer shows a false "session failed" message during the brief reconnect right after scanning the QR.

= 0.4.2 =
* Fix: Plugin icon now shows correctly in the WordPress updates screen (using waxap.shop favicon via PUC filter).

= 0.4.1 =
* New: Shortcode [waxap_whatsapp_button] — inserta el botón wa.me en cualquier página o entrada. Acepta atributo order para sustituir {pedido} en el mensaje prefabricado.
* Improvement: documentación del shortcode añadida en el tab Email branding del plugin.

= 0.4.0 =
* New: Agency plan support — one API key can power multiple WooCommerce stores, each with its own WhatsApp number.
* New: Sessions management UI — accounts with 2+ sessions see a list with status badges and per-session disconnect.
* New: Plugin sends site_url on session creation and order events for automatic store-to-session routing.

= 0.3.3 =
* Fix: Modal de QR no se abría al volver a la pestaña "Número WhatsApp" con sesión en estado intermedio — el body quedaba con overflow:hidden bloqueado.

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
