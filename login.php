<?php
require_once 'csp_handler.php';
$nonce = set_csp_header();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domus Tienda - Iniciar Sesión</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión - Domus Tienda</h2>
        <form id="login-form">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <div class="show-password-container">
                <input type="checkbox" id="show-password">
                <label for="show-password">Mostrar contraseña</label>
            </div>

            <div class="remember-me-container">
                <input type="checkbox" id="remember-me" name="remember_me">
                <label for="remember-me">Recordarme</label>
            </div>

            <button type="submit">Iniciar Sesión</button>
        </form>
        <p id="login-error" class="error-message" style="display:none;"></p>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>