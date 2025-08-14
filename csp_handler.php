<?php

/**
 * Establece la cabecera de la Política de Seguridad de Contenido (CSP).
 * Esta política ayuda a prevenir ataques de inyección de código, como Cross-Site Scripting (XSS).
 */
function set_csp_header() {
    // Fuentes permitidas. 'self' se refiere al propio dominio.
    $directives = [
        // Por defecto, solo se permite contenido del propio dominio.
        "default-src" => "'self'",

        // Scripts permitidos: del propio dominio y de las CDNs de Bootstrap/jQuery.
        "script-src" => "'self' https://cdn.jsdelivr.net https://code.jquery.com",

        // Estilos permitidos: del propio dominio y de las CDNs de Bootstrap y Google Fonts.
        // 'unsafe-inline' es necesario para los estilos en línea de Bootstrap.
        "style-src" => "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com",

        // Imágenes permitidas: del propio dominio y de data URIs (para imágenes incrustadas).
        "img-src" => "'self' data:",

        // Fuentes (tipografías) permitidas: del propio dominio y de Google Fonts.
        "font-src" => "'self' https://fonts.gstatic.com",

        // Conexiones permitidas (AJAX, WebSockets): solo al propio dominio.
        "connect-src" => "'self'",

        // Orígenes permitidos para iframes.
        "frame-src" => "'none'", // No se permiten iframes

        // Objetos (Flash, etc.) no permitidos.
        "object-src" => "'none'",

        // Actualiza las peticiones HTTP inseguras a HTTPS.
        "upgrade-insecure-requests" => "",

        // Restringe dónde se puede embeber el sitio (previene clickjacking).
        "frame-ancestors" => "'none'" // No se puede embeber en un iframe
    ];

    $csp_string = "";
    foreach ($directives as $directive => $value) {
        $csp_string .= $directive . " " . $value . "; ";
    }

    header("Content-Security-Policy: " . trim($csp_string));
}

?>