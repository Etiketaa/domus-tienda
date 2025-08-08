<?php
// Este script se debe incluir al principio de las páginas protegidas.

// Iniciar la sesión si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Si el usuario no está logueado en la sesión, pero tiene una cookie "recordarme"
if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_me'])) {
    require_once 'config.php'; // Asegurarse de que la conexión a la BD esté disponible

    $token = $_COOKIE['remember_me'];

    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE remember_token = ? AND remember_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // El token es válido, iniciar sesión para el usuario
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Opcional: Refrescar la cookie por otros 30 días
        setcookie('remember_me', $token, time() + (86400 * 30), "/", "", false, true);
    }

    $stmt->close();
}
?>