# ============================================================
# WA Notifier - Bootstrap del entorno WordPress para Windows
# ============================================================
#
# Instala WordPress + WooCommerce, activa el plugin WA Notifier y crea
# datos de prueba minimos usando el servicio wpcli del docker-compose.yml.
#
# Pre-requisitos:
#   - Docker Desktop corriendo
#   - Red Docker 'wa-notifier-dev' creada:
#       docker network create wa-notifier-dev
#   - Contenedores levantados:
#       docker compose up -d
#
# Uso desde PowerShell:
#   .\bin\bootstrap-wp.ps1
#
# Reset completo + bootstrap:
#   docker compose down -v
#   docker compose up -d
#   .\bin\bootstrap-wp.ps1
# ============================================================

$ErrorActionPreference = 'Stop'

# --- Banner inicial ---
Write-Host ""
Write-Host "============================================================"
Write-Host "  WA Notifier - Bootstrap del entorno WP local"
Write-Host "============================================================"
Write-Host "  Directorio: $((Get-Location).Path)"
Write-Host ""

# --- Pre-checks ---
if (-not (Test-Path 'docker-compose.yml')) {
    throw "No se encuentra docker-compose.yml en el directorio actual. Ejecuta el script desde la raiz de wa-notifier-wp-plugin/."
}

$networkExists = docker network ls --filter "name=^wa-notifier-dev$" --format "{{.Name}}"
if (-not $networkExists) {
    throw "La red Docker 'wa-notifier-dev' no existe. Crea con: docker network create wa-notifier-dev"
}

# --- Helpers ---
function Invoke-ComposeChecked {
    param(
        [Parameter(Mandatory = $true)]
        [string[]] $Arguments
    )

    & docker compose @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Comando fallido (exit $LASTEXITCODE): docker compose $($Arguments -join ' ')"
    }
}

function Test-WpInstalled {
    & docker compose run --rm wpcli wp core is-installed *> $null
    return $LASTEXITCODE -eq 0
}

# --- Esperar a que WordPress (Apache + PHP + BD) este operativo ---
Write-Host "==> Esperando a que el stack este operativo (max 120s)..."
$ready = $false
$lastError = $null

for ($attempt = 1; $attempt -le 60; $attempt++) {
    # Intentamos un comando inocuo: 'wp option get siteurl'.
    # Si la BD esta caida o WP no esta instalado todavia, devuelve != 0.
    # Si responde con error de "no instalado", igualmente significa que
    # el contenedor de WP esta levantado, asi que tambien lo consideramos listo.

    if (Test-WpInstalled) {
        $ready = $true
        break
    }

    # Comprobacion alternativa: si MySQL responde, podemos proceder al 'core install'.
    # Usamos 'exec' sobre el contenedor mysql ya corriendo (no crea un contenedor nuevo)
    # porque 'wp db check' falla cuando aun no existen las tablas de WP.
    & docker compose exec mysql mysqladmin ping -h localhost -u root -prootpassword *> $null
    if ($LASTEXITCODE -eq 0) {
        $ready = $true
        break
    }

    if ($attempt -eq 1) {
        Write-Host "    Primer intento fallido. Esto es normal: WP tarda 20-40s en arrancar la primera vez."
    } elseif ($attempt % 5 -eq 0) {
        Write-Host "    ... aun arrancando (intento $attempt/60)"
    }

    Start-Sleep -Seconds 2
}

if (-not $ready) {
    Write-Host ""
    Write-Host "ERROR: el stack no esta listo despues de 120 segundos." -ForegroundColor Red
    Write-Host "Diagnostico:"
    Write-Host "  docker compose ps"
    Write-Host "  docker compose logs --tail=50 wordpress"
    Write-Host "  docker compose logs --tail=50 mysql"
    throw "Timeout esperando al stack."
}

# --- WordPress core install (solo si no estaba ya instalado) ---
if (Test-WpInstalled) {
    Write-Host "==> WordPress ya esta instalado. Saltando 'core install'."
} else {
    Write-Host "==> Instalando WordPress..."
    # NOTA: credenciales hardcodeadas porque esto es solo entorno de
    # desarrollo local. NUNCA usar este script en staging/produccion.
    Invoke-ComposeChecked @(
        'run', '--rm', 'wpcli', 'wp', 'core', 'install',
        '--url=http://localhost:8080',
        '--title=WA Notifier Dev Store',
        '--admin_user=admin',
        '--admin_password=admin',
        '--admin_email=admin@localhost.test',
        '--skip-email'
    )
}

# --- WooCommerce ---
Write-Host "==> Instalando WooCommerce (si no esta ya)..."
& docker compose run --rm wpcli wp plugin is-installed woocommerce *> $null
if ($LASTEXITCODE -ne 0) {
    Invoke-ComposeChecked @('run', '--rm', 'wpcli', 'wp', 'plugin', 'install', 'woocommerce', '--activate')
} else {
    Invoke-ComposeChecked @('run', '--rm', 'wpcli', 'wp', 'plugin', 'activate', 'woocommerce')
}

# --- Plugin propio (puede no existir todavia, no es fatal) ---
Write-Host "==> Activando plugin WA Notifier..."
& docker compose run --rm wpcli wp plugin activate wa-notifier *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Host "    (plugin aun sin codigo PHP valido, se omite)"
}

# --- HPOS ---
Write-Host "==> Habilitando HPOS (High-Performance Order Storage)..."
Invoke-ComposeChecked @(
    'run', '--rm', 'wpcli', 'wp', 'option', 'update',
    'woocommerce_custom_orders_table_enabled', 'yes'
)
# Desactivar sincronizacion con tablas legacy: en dev no la necesitamos
# y ahorra escrituras dobles.
Invoke-ComposeChecked @(
    'run', '--rm', 'wpcli', 'wp', 'option', 'update',
    'woocommerce_custom_orders_table_data_sync_enabled', 'no'
)

# --- Saltar wizard de WC ---
Write-Host "==> Saltando wizard de WooCommerce..."
Invoke-ComposeChecked @(
    'run', '--rm', 'wpcli', 'wp', 'option', 'update',
    'woocommerce_onboarding_profile',
    '{"completed":true}',
    '--format=json'
)

# --- Producto de prueba ---
# Nota: 'wp wc product create' requiere el subpaquete wc-cli, que NO viene
# con la imagen oficial 'wordpress:cli'. Creamos el producto via 'wp post create'
# + meta directos, que funciona siempre.
Write-Host "==> Creando producto de prueba..."

# Comprobar si ya existe (busqueda por slug)
$existingId = & docker compose run --rm -T wpcli wp post list --post_type=product --name=producto-de-prueba --format=ids 2>$null
$existingId = ($existingId | Out-String).Trim()

if ([string]::IsNullOrWhiteSpace($existingId)) {
    $productId = & docker compose run --rm -T wpcli wp post create `
        --post_type=product `
        --post_title="Producto de prueba" `
        --post_name=producto-de-prueba `
        --post_status=publish `
        --porcelain 2>$null
    $productId = ($productId | Out-String).Trim()

    if (-not [string]::IsNullOrWhiteSpace($productId)) {
        Invoke-ComposeChecked @('run', '--rm', 'wpcli', 'wp', 'post', 'meta', 'update', $productId, '_price', '19.90')
        Invoke-ComposeChecked @('run', '--rm', 'wpcli', 'wp', 'post', 'meta', 'update', $productId, '_regular_price', '19.90')
        Invoke-ComposeChecked @('run', '--rm', 'wpcli', 'wp', 'post', 'meta', 'update', $productId, '_manage_stock', 'yes')
        Invoke-ComposeChecked @('run', '--rm', 'wpcli', 'wp', 'post', 'meta', 'update', $productId, '_stock', '100')
        Invoke-ComposeChecked @('run', '--rm', 'wpcli', 'wp', 'post', 'meta', 'update', $productId, '_stock_status', 'instock')
        Write-Host "    Producto creado con ID $productId."
    } else {
        Write-Host "    No se pudo crear el producto (revisa logs)."
    }
} else {
    Write-Host "    Producto ya existe (ID $existingId). Saltando."
}

# --- Resumen final ---
Write-Host ""
Write-Host "============================================================"
Write-Host "  Setup completado"
Write-Host "============================================================"
Write-Host "  WordPress:    http://localhost:8080"
Write-Host "  Admin:        http://localhost:8080/wp-admin"
Write-Host "                user: admin / pass: admin"
Write-Host "  MailHog:      http://localhost:8025"
Write-Host "  MySQL host:   localhost:3307 (user: wordpress / pass: wordpress)"
Write-Host "============================================================"
Write-Host ""