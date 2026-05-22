#!/usr/bin/env bash
# ============================================================
# Bootstrap del entorno de desarrollo: instala WP + WooCommerce
# + activa el plugin WA Notifier + crea datos de prueba mínimos.
#
# Uso:
#   docker compose up -d
#   ./bin/bootstrap-wp.sh
#
# Reset completo + bootstrap:
#   docker compose down -v && docker compose up -d
#   ./bin/bootstrap-wp.sh
# ============================================================

set -euo pipefail

# Esperar a que WP esté listo
echo "==> Esperando a WordPress..."
until docker compose run --rm wpcli wp core is-installed --url=http://localhost:8080 2>/dev/null; do
    if docker compose run --rm wpcli test -f /var/www/html/wp-config.php 2>/dev/null \
        && docker compose run --rm wpcli wp core version 2>/dev/null; then
        # WP copiado y configurado, pero todavía no instalado: continuar al setup
        break
    fi
    echo "    ... aún arrancando"
    sleep 2
done

# Si WP ya está instalado, saltamos
if docker compose run --rm wpcli wp core is-installed --url=http://localhost:8080 2>/dev/null; then
    echo "==> WordPress ya está instalado. Saltando setup inicial."
else
    echo "==> Instalando WordPress..."
    docker compose run --rm wpcli wp core install \
        --url="http://localhost:8080" \
        --title="WA Notifier Dev Store" \
        --admin_user="admin" \
        --admin_password="admin" \
        --admin_email="admin@localhost.test" \
        --skip-email
fi

echo "==> Instalando WooCommerce..."
docker compose run --rm wpcli wp plugin install woocommerce --activate

echo "==> Activando plugin WA Notifier..."
docker compose run --rm wpcli wp plugin activate wa-notifier || \
    echo "    (todavía sin código real, no pasa nada)"

echo "==> Habilitando HPOS (High-Performance Order Storage)..."
docker compose run --rm wpcli wp option update woocommerce_custom_orders_table_enabled yes

echo "==> Saltando wizard de WooCommerce..."
docker compose run --rm wpcli wp option update woocommerce_onboarding_profile '{"completed":true}' --format=json

echo "==> Creando producto de prueba..."
docker compose run --rm wpcli wp wc product create \
    --name="Producto de prueba" \
    --type=simple \
    --regular_price=19.90 \
    --manage_stock=true \
    --stock_quantity=100 \
    --user=1 \
    2>/dev/null || echo "    (ya existe o WC-CLI no disponible)"

echo ""
echo "============================================================"
echo "  ✅ Setup completado"
echo "============================================================"
echo "  WordPress:    http://localhost:8080"
echo "  Admin:        http://localhost:8080/wp-admin"
echo "                user: admin / pass: admin"
echo "  MailHog:      http://localhost:8025"
echo "  MySQL host:   localhost:3307 (user: wordpress / pass: wordpress)"
echo "============================================================"
