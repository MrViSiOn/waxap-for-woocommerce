# WA Notifier — Plugin WooCommerce

Plugin de WordPress que conecta WooCommerce con el SaaS WA Notifier para enviar notificaciones transaccionales por WhatsApp.

> **Este es un repositorio independiente** del monorepo principal (`WaNotifier/`), porque WordPress.org exige una estructura SVN propia para distribución pública.

## Estado

🚧 **No implementado todavía.** Esqueleto inicial pendiente de generar en Fase 0.

## Stack

- **PHP:** 8.1+
- **WordPress:** 6.2+
- **WooCommerce:** 8.0+
- **Licencia:** GPL v2 or later (exigido por WordPress.org)

## Estructura prevista (estándar WordPress)

```
wa-notifier-wp-plugin/
├── wa-notifier.php             Archivo principal (cabecera del plugin)
├── readme.txt                  Formato WordPress.org (NO markdown)
├── README.md                   Para desarrolladores (GitHub)
├── uninstall.php               Limpieza al desinstalar
├── composer.json
├── src/
│   ├── Plugin.php              Bootstrap principal
│   ├── Admin/                  Páginas de admin (settings, vinculación QR)
│   ├── Frontend/               Checkbox opt-in en checkout
│   ├── Hooks/                  Listeners de eventos WooCommerce
│   ├── Api/                    Cliente HTTP del wrapper SaaS
│   ├── Email/                  Inyección del botón wa.me en emails WC
│   └── WebSocket/              Cliente WebSocket para QR streaming
├── assets/
│   ├── js/
│   │   ├── admin.js            QR rendering + WebSocket
│   │   └── checkout.js
│   ├── css/
│   └── images/
└── languages/
    ├── wa-notifier.pot         Template para traducciones
    ├── wa-notifier-es_ES.po
    └── wa-notifier-pt_BR.po
```

## Hooks WooCommerce que escuchamos

| Hook | Evento |
|---|---|
| `woocommerce_new_order` | order.created |
| `woocommerce_order_status_pending_to_processing` | order.paid |
| `woocommerce_order_status_processing_to_completed` | order.completed |
| `woocommerce_order_status_changed` | order.status_changed |
| `woocommerce_order_refunded` | order.refunded |
| `woocommerce_cancelled_order` | order.cancelled |

## Endpoints del wrapper que llamamos

```
POST  /v1/auth/register             Crear cuenta SaaS desde el plugin
POST  /v1/auth/login                Login
POST  /v1/sessions                  Crear sesión + iniciar QR
GET   /v1/sessions/:id              Estado sesión
WS    /v1/sessions/:id/qr-stream    QR streaming
POST  /v1/events                    Enviar eventos WC al wrapper
```

## Desarrollo local

```bash
cd wa-notifier-wp-plugin/
composer install
# Symlinkar a un wp-content/plugins/ local
ln -s $PWD /path/to/wordpress/wp-content/plugins/wa-notifier
```

## Build y publicación en WordPress.org

Pendiente de scripts. Plan: GitHub Action que sincronice `trunk/` y `tags/v*` con SVN.

## Licencia

GPL v2 or later (`LICENSE`).
