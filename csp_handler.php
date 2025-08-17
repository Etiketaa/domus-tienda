<?php
 /**
  * Establece la cabecera de la Política de Seguridad de Contenido (CSP).
  * Esta política ayuda a prevenir ataques de inyección de código, como Cross-Site Scripting
 (XSS).
  */
 function set_csp_header() {
     // Generar un nonce aleatorio único para cada petición.
     $nonce = base64_encode(random_bytes(16));

        // Fuentes permitidas. 'self' se refiere al propio dominio.
        $directives = [
            "default-src" => "'self'",
            // Se añade 'nonce-...' a script-src para permitir scripts inline seguros.
            "script-src" => "'self' 'nonce-$nonce' https://cdn.jsdelivr.net
 https://code.jquery.com",
            "style-src" => "'self' 'unsafe-inline' https://cdn.jsdelivr.net
 https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "img-src" => "'self' data:",
            "font-src" => "'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "connect-src" => "'self'",
            "frame-src" => "'none'",
            "object-src" => "'none'",
            "upgrade-insecure-requests" => "",
            "frame-ancestors" => "'none'"
        ];
   
        $csp_string = "";
        foreach ($directives as $directive => $value) {
            // Limpiar cualquier salto de línea dentro del valor antes de concatenar
            $value = str_replace(["\n", "\r"], '', $value);
            $csp_string .= "$directive $value; ";
        }
   
        header("Content-Security-Policy: " . trim($csp_string));
   
        // Devolver el nonce para que pueda ser usado en los tags de script.
        return $nonce;
    }
