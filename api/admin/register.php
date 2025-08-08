<?php
require_once '../../config.php';

header('Content-Type: application/json');
session_start();

// --- Seguridad: Solo administradores pueden registrar nuevos administradores ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}
// --------------------------------------------------------------------------

$data = json_decode(file_get_contents('php://input'), true);

// Validar que los datos no estén vacíos
if (!isset($data['username'], $data['email'], $data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos.']);
    exit();
}

// Limpiar y validar datos
$username = trim($data['username']);
$email = trim($data['email']);
$password = trim($data['password']);

if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Ningún campo puede estar vacío.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
    exit();
}

// Hashear la contraseña
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin'; // Todos los usuarios registrados desde aquí serán administradores

try {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Administrador registrado exitosamente.']);
    } else {
        if ($conn->errno === 1062) {
            echo json_encode(['success' => false, 'message' => 'El nombre de usuario o el email ya existen.']);
        } else {
            throw new Exception($stmt->error);
        }
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log('Error en registro de admin: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor al intentar registrar el usuario.']);
}
?>