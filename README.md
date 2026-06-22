# Waxap for WooCommerce — Plugin

Plugin de WordPress que conecta WooCommerce con el SaaS **Waxap** para enviar notificaciones transaccionales por WhatsApp. El comerciante aporta su propio número (lo vincula por QR desde el admin) y los emails de WooCommerce incluyen un botón `wa.me` para que sea el cliente quien inicia la conversación.

> **Repositorio independiente** del monorepo principal (`WaNotifier/`), porque WordPress.org exige licencia GPL y estructura SVN propias para distribución pública. Slug WP.org: `waxap-for-woocommerce`.

## Estado

✅ **Implementado y en producción.** Versión actual: ver cabecera de `waxap-for-woocommerce.php` (0.4.x).

## Stack

- **PHP:** 8.1+ (`declare(strict_types=1)`, type hints, PSR-12)
- **WordPress:** 6.2+
- **WooCommerce:** 8.0+ (HPOS)
- **Autoloader:** Composer PSR-4 (`WaNotifier\` → `src/`)
- **Licencia:** GPL v2 or later (exigido por WordPress.org)

## Estructura (`src/`)

```
src/
├── Plugin.php          Bootstrap (registra menú, hooks, ajax, handlers)
├── Settings.php        Acceso tipado a wp_options (credenciales, plantillas, config)
├── Admin/              Menú y pestañas (Conexión, Número, Notificaciones,
│                       Email branding, Historial, Mensajes); onboarding wizard
├── Ajax/               Handlers WP-AJAX (sesión/QR, inbox)
├── Api/                WrapperClient: cliente HTTP del wrapper SaaS
├── Checkout/           Checkbox opt-in WhatsApp (GDPR) en el checkout
├── Emails/             Inyección del botón wa.me en emails transaccionales de WC
└── Orders/             Listener de cambios de estado → POST /v1/events (HMAC + idempotente)
```

Assets JS en `assets/js/`: `admin-onboarding.js` (wizard registro→pago→QR), `admin-session.js` (vinculación/estado), `admin-inbox.js` (buzón).

## Flujo de onboarding (canónico)

El alta vive en la pestaña **Conexión** (`Admin/Onboarding.php` + `admin-onboarding.js`):

1. Crear cuenta (`POST /v1/auth/register` → devuelve `tenantId` + `claimToken`).
2. Pagar €5/mes (Stripe Checkout).
3. Tras la activación, el plugin canjea el `claimToken` (`POST /v1/auth/claim`) para obtener `apiKey` + `hmacSecret` — las credenciales **no** viajan por canales no autenticados.
4. Vincular el número por QR (pestaña "Número WhatsApp").

> No existe un alta manual por formulario de credenciales: ese flujo legacy se eliminó (DRAPPS-290).

## Hooks de WooCommerce

| Hook | Uso |
|---|---|
| `woocommerce_order_status_changed` | Envía evento al wrapper si el estado está en la lista a notificar y hay opt-in. Idempotente por `_waxap_notified_<status>`. |
| `woocommerce_email_after_order_table` (branding) | Inyecta el botón `wa.me` en los emails de cliente. |
| checkout | Checkbox de opt-in → meta `_wa_notifier_opt_in`. |

## Desarrollo local

Entorno WP + WC + MailHog vía `docker-compose.yml` del propio repo (ver `bin/bootstrap-wp.sh`). El plugin apunta al wrapper en `http://host.docker.internal:3000`. Guía completa en [`../DEV.md`](../DEV.md).

```bash
composer install
# PHPCS (WordPress Coding Standards)
composer lint        # vendor/bin/phpcs --standard=phpcs.xml.dist
composer lint:fix    # phpcbf
```

## Publicación en WordPress.org

Prerequisito: PHPCS limpio (ver DRAPPS-293). Plan: GitHub Action que sincronice `trunk/` y `tags/v*` con SVN.

## Licencia

GPL v2 or later (`LICENSE`).
