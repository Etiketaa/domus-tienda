<?php

// --- Configuración del Rate Limiter ---
define('RATE_LIMITER_ATTEMPTS', 5); // Máximo de intentos fallidos
define('RATE_LIMITER_TIME_WINDOW', 15 * 60); // Ventana de tiempo en segundos (15 minutos)
define('RATE_LIMITER_LOG_DIR', __DIR__ . '/tmp/'); // Directorio para los logs

/**
 * Obtiene la dirección IP real del cliente.
 * @return string La dirección IP.
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Verifica si la IP actual está bloqueada por demasiados intentos.
 * @param string $ip La dirección IP a verificar.
 * @return bool True si la IP está bloqueada, false en caso contrario.
 */
function is_rate_limited($ip) {
    $log_file = RATE_LIMITER_LOG_DIR . md5($ip) . '.log';

    if (!file_exists($log_file)) {
        return false;
    }

    $attempts = json_decode(file_get_contents($log_file), true);
    if ($attempts === null) {
        return false; // Archivo de log corrupto
    }

    // Filtrar intentos que ya expiraron
    $valid_attempts = array_filter($attempts, function($timestamp) {
        return (time() - $timestamp) < RATE_LIMITER_TIME_WINDOW;
    });

    // Guardar el archivo de log limpio
    file_put_contents($log_file, json_encode(array_values($valid_attempts)));

    return count($valid_attempts) >= RATE_LIMITER_ATTEMPTS;
}

/**
 * Registra un intento fallido para la IP especificada.
 * @param string $ip La dirección IP que tuvo el intento fallido.
 */
function record_failed_attempt($ip) {
    $log_file = RATE_LIMITER_LOG_DIR . md5($ip) . '.log';

    $attempts = [];
    if (file_exists($log_file)) {
        $attempts = json_decode(file_get_contents($log_file), true) ?: [];
    }

    $attempts[] = time();
    file_put_contents($log_file, json_encode($attempts));
}

/**
 * Limpia todos los registros de intentos para una IP.
 * Se debe llamar después de un inicio de sesión exitoso.
 * @param string $ip La dirección IP a limpiar.
 */
function clear_attempts($ip) {
    $log_file = RATE_LIMITER_LOG_DIR . md5($ip) . '.log';
    if (file_exists($log_file)) {
        unlink($log_file);
    }
}

?>