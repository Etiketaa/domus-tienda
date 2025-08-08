<?php

// Manejo de errores y excepciones para devolver siempre JSON
set_exception_handler(function ($exception) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unhandled Exception: ' . $exception->getMessage(),
    ]);
    exit();
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $message,
    ]);
    exit();
});

require_once '../../config.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        if ($action === 'upload') {
            handleUpload($conn);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción POST no válida.']);
        }
        break;
    case 'DELETE':
        verify_csrf_and_exit();
        handleDelete($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        break;
}

function handleGet($conn) {
    $stmt = $conn->prepare("SELECT id, image_url FROM carousel_images ORDER BY order_index ASC, created_at ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    echo json_encode(['success' => true, 'images' => $images]);
}

function handleUpload($conn) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen o hubo un error en la subida.']);
        return;
    }

    // Verificar límite de 5 imágenes
    $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM carousel_images");
    $count_stmt->execute();
    $current_count = $count_stmt->get_result()->fetch_assoc()['count'];

    if ($current_count >= 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Se ha alcanzado el límite de 5 imágenes para el carrusel. Elimina una para subir otra.']);
        return;
    }

    $upload_result = uploadImage($_FILES['image']);
    if (!$upload_result['success']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al subir la imagen: ' . $upload_result['message']]);
        return;
    }

    $image_url = $upload_result['path'];

    // Obtener el siguiente order_index
    $max_order_stmt = $conn->prepare("SELECT MAX(order_index) as max_order FROM carousel_images");
    $max_order_stmt->execute();
    $max_order = $max_order_stmt->get_result()->fetch_assoc()['max_order'];
    $new_order_index = ($max_order === null) ? 0 : $max_order + 1;

    $stmt = $conn->prepare("INSERT INTO carousel_images (image_url, order_index) VALUES (?, ?)");
    $stmt->bind_param("si", $image_url, $new_order_index);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Imagen subida con éxito.', 'image_id' => $conn->insert_id, 'image_url' => $image_url]);
    } else {
        // Si falla la inserción, intentar eliminar el archivo subido
        if (file_exists('../../' . $image_url)) {
            unlink('../../' . $image_url);
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen en la base de datos.']);
    }
}

function handleDelete($conn) {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $image_id = filter_var($_DELETE['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$image_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de imagen inválido.']);
        return;
    }

    // Obtener URL para borrar archivo
    $stmt_get = $conn->prepare("SELECT image_url FROM carousel_images WHERE id = ?");
    $stmt_get->bind_param("i", $image_id);
    $stmt_get->execute();
    $image_data = $stmt_get->get_result()->fetch_assoc();

    if (!$image_data) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada.']);
        return;
    }

    $image_url = $image_data['image_url'];

    // Eliminar de la base de datos
    $stmt_delete = $conn->prepare("DELETE FROM carousel_images WHERE id = ?");
    $stmt_delete->bind_param("i", $image_id);
    
    if ($stmt_delete->execute()) {
        // Eliminar archivo del servidor
        if ($image_url && file_exists('../../' . $image_url)) {
            unlink('../../' . $image_url);
        }
        echo json_encode(['success' => true, 'message' => 'Imagen eliminada con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la imagen de la base de datos.']);
    }
}

// Función de utilidad para subir imágenes (similar a la de productos, pero con ruta específica)
function uploadImage($file) {
    $target_dir = "../../assets/images/carousel/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_name = uniqid('carousel_', true) . '.' . $imageFileType;
    $target_file_path = $target_dir . $unique_name;

    // Mover el archivo temporal a la ubicación final antes de procesar
    if (!move_uploaded_file($file["tmp_name"], $target_file_path)) {
        return ['success' => false, 'message' => 'Error al mover el archivo subido.'];
    }

    // Obtener información de la imagen, incluyendo el tipo MIME real
    $image_info = getimagesize($target_file_path);
    if ($image_info === false) {
        unlink($target_file_path); // Eliminar archivo si no es una imagen válida
        return ['success' => false, 'message' => 'El archivo subido no es una imagen válida.'];
    }

    $mime = $image_info['mime'];
    list($width, $height) = $image_info;

    $new_width = 800;
    $new_height = 450;

    $source = null;
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($target_file_path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($target_file_path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($target_file_path);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($target_file_path);
            break;
        default:
            unlink($target_file_path); // Eliminar archivo si el tipo no es soportado
            return ['success' => false, 'message' => 'Tipo de imagen no soportado para redimensionamiento. Solo JPG, PNG, GIF, WEBP.'];
    }

    if (!$source) {
        unlink($target_file_path); // Eliminar archivo si no se pudo crear la imagen
        return ['success' => false, 'message' => 'Error al cargar la imagen para redimensionar.'];
    }

    $thumb = imagecreatetruecolor($new_width, $new_height);

    // Preservar la transparencia para PNG y GIF
    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $new_width, $new_height, $transparent);
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Guardar la imagen redimensionada, sobrescribiendo la original
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($thumb, $target_file_path, 90);
            break;
        case 'image/png':
            imagepng($thumb, $target_file_path, 9);
            break;
        case 'image/gif':
            imagegif($thumb, $target_file_path);
            break;
        case 'image/webp':
            imagewebp($thumb, $target_file_path, 90);
            break;
    }

    imagedestroy($thumb);
    imagedestroy($source);

    return ['success' => true, 'path' => 'assets/images/carousel/' . $unique_name];
}

?>