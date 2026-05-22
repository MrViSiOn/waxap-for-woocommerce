<?php
/**
 * Plugin Name: WA Notifier · Dev · Force SMTP to MailHog
 * Description: En desarrollo, redirige todo el correo de WordPress a MailHog. Inspecciona los emails en http://localhost:8025
 *
 * Este es un "Must-Use" plugin: se carga automáticamente en cada request,
 * sin necesidad de activarlo desde el admin. Vive en wp-content/mu-plugins/
 * y NO debe llegar nunca a producción.
 *
 * @package WaNotifierDev
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'phpmailer_init', function ( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'mailhog';
    $phpmailer->Port       = 1025;
    $phpmailer->SMTPAuth   = false;
    $phpmailer->SMTPSecure = '';
    $phpmailer->From       = 'wordpress@localhost.test';
    $phpmailer->FromName   = 'WordPress (dev)';
} );

// Banner discreto en el admin para recordar que estamos en dev.
add_action( 'admin_notices', function () {
    echo '<div class="notice notice-info"><p>'
        . '<strong>WA Notifier dev:</strong> el correo se está enviando a MailHog. '
        . 'Inspecciona los emails en <a href="http://localhost:8025" target="_blank">http://localhost:8025</a>.'
        . '</p></div>';
} );
