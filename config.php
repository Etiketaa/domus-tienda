<?php

// Configuración de la base de datos
$db_host = 'localhost'; // Host de tu base de datos local
$db_user = 'root'; // Usuario de tu base de datos local
$db_pass = ''; // Contraseña de tu base de datos local (vacía si no has configurado una)
$db_name = 'domus-tienda'; // Nombre de la base de datos que creaste en phpMyAdmin

// Crear conexión
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    // En un entorno de producción, es mejor registrar el error que mostrarlo
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    // Mostrar un mensaje genérico al usuario
    header('Content-Type: application/json');
    http_response_code(503); // Service Unavailable
    echo json_encode(['success' => false, 'message' => 'El servicio no está disponible temporalmente. Intente más tarde.']);
    exit();
}

// Establecer el charset a UTF-8
$conn->set_charset("utf8");