<?php
session_start();

// Proteger la página: solo accesible para administradores logueados
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Administrador - Domus Tienda</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="register-container">
        <h2>Registrar Nuevo Administrador</h2>
        <form id="register-admin-form">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Registrar Administrador</button>
        </form>
        <div id="form-message" class="message" style="display:none;"></div>
        <a href="dashboard.php" class="back-link">Volver al Dashboard</a>
    </div>

    <script src="assets/js/register_admin.js"></script>
</body>
</html>