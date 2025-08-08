<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
require_once '../config.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Error: Datos JSON inválidos recibidos.']);
    exit();
}

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// Basic validation and sanitization
$username = htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8');

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos.']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Regenerar ID de sesión para prevenir fijación de sesión
            session_regenerate_id(true);

            // Authentication successful, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            // --- Funcionalidad de Recordarme ---
            if (isset($data['remember']) && $data['remember'] === true) {
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 días

                $stmt_token = $conn->prepare("UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE id = ?");
                $stmt_token->bind_param("ssi", $token, $expiry, $user['id']);
                $stmt_token->execute();
                $stmt_token->close();

                // Enviar cookie al navegador
                setcookie('remember_me', $token, time() + (86400 * 30), "/", "", false, true);
            }
            // ----------------------------------

            echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
        }
    }  else {
        record_failed_attempt($client_ip); // Registrar intento fallido
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor. Inténtalo de nuevo más tarde.']);
} 
?>