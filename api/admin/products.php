<?php

// Manejo de errores y excepciones para devolver siempre JSON
set_exception_handler(function ($exception) {
    error_log('Unhandled Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error inesperado. Por favor, intente nuevamente más tarde.'
    ]);
    exit();
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log('PHP Error: ' . $message . ' in ' . $file . ' on line ' . $line);
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error inesperado. Por favor, intente nuevamente más tarde.'
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
        if ($action === 'update') {
            handleUpdate($conn);
        } elseif ($action === 'delete_image') {
            handleDeleteImage($conn);
        } else {
            handleCreate($conn);
        }
        break;
    case 'DELETE':
        verify_csrf_and_exit();
        handleDeleteProduct($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        break;
}

// --- Funciones de Manejo ---

function handleGet($conn) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        // Obtener un solo producto con sus imágenes
        $product = getProductById($conn, $id);
        if ($product) {
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        }
    } else {
        // Obtener todos los productos
        $search_term = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8');
        $products = getAllProducts($conn, $search_term);
        echo json_encode($products);
    }
}

function handleCreate($conn) {
    $validation = validateProductData($_POST);
    if (!$validation['success']) {
        http_response_code(400);
        echo json_encode($validation);
        return;
    }
    extract($validation['data']);

    $conn->begin_transaction();

    try {
        // Subir imagen principal
        $image_url = null;
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
            $upload_result = uploadImage($_FILES['image_file']);
            if ($upload_result['success']) {
                $image_url = $upload_result['path'];
            } else {
                throw new Exception($upload_result['message']);
            }
        }

        // Insertar producto
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, image_url, category, brand) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdisss", $name, $description, $price, $stock, $image_url, $category, $brand);
        $stmt->execute();
        $product_id = $conn->insert_id;

        // Subir imágenes adicionales
        if (isset($_FILES['additional_images'])) {
            uploadAdditionalImages($conn, $product_id, $_FILES['additional_images']);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Producto creado con éxito.', 'id' => $product_id]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear el producto: ' . $e->getMessage()]);
    }
}

function handleUpdate($conn) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
        return;
    }

    // First, check if the product exists
    $stmt_check = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'El producto que intenta actualizar no existe.']);
        return;
    }
    $current_image_url = $result_check->fetch_assoc()['image_url'];
    $stmt_check->close();


    $validation = validateProductData($_POST);
    if (!$validation['success']) {
        http_response_code(400);
        echo json_encode($validation);
        return;
    }
    extract($validation['data']);

    $conn->begin_transaction();

    try {
        $image_url = $current_image_url;

        // Subir nueva imagen principal si se proporciona
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
            $upload_result = uploadImage($_FILES['image_file']);
            if ($upload_result['success']) {
                $image_url = $upload_result['path'];
                // Opcional: eliminar imagen anterior
                if ($current_image_url && file_exists('../../' . $current_image_url)) {
                    unlink('../../' . $current_image_url);
                }
            } else {
                throw new Exception($upload_result['message']);
            }
        }

        // Actualizar producto
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image_url = ?, category = ?, brand = ? WHERE id = ?");
        $stmt->bind_param("ssdisssi", $name, $description, $price, $stock, $image_url, $category, $brand, $id);
        $stmt->execute();

        // Subir imágenes adicionales
        if (isset($_FILES['additional_images'])) {
            uploadAdditionalImages($conn, $id, $_FILES['additional_images']);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Producto actualizado con éxito.']);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto: ' . $e->getMessage()]);
    }
}

function handleDeleteProduct($conn) {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $id = filter_var($_DELETE['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // 1. Obtener todas las URLs de las imágenes (principal y adicionales)
        $stmt_main = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt_main->bind_param("i", $id);
        $stmt_main->execute();
        $result = $stmt_main->get_result();
        $main_image_data = $result->fetch_assoc();
        $main_image_url = $main_image_data['image_url'] ?? null;

        $stmt_additional = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
        $stmt_additional->bind_param("i", $id);
        $stmt_additional->execute();
        $additional_images_result = $stmt_additional->get_result();

        // 2. Eliminar imágenes del servidor
        if ($main_image_url && file_exists('../../' . $main_image_url)) {
            unlink('../../' . $main_image_url);
        }
        while ($row = $additional_images_result->fetch_assoc()) {
            if ($row['image_url'] && file_exists('../../' . $row['image_url'])) {
                unlink('../../' . $row['image_url']);
            }
        }

        // 3. Eliminar imágenes adicionales de la base de datos
        $stmt_delete_additional = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt_delete_additional->bind_param("i", $id);
        $stmt_delete_additional->execute();

        // 4. Eliminar el producto de la base de datos
        $stmt_delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt_delete_product->bind_param("i", $id);
        $stmt_delete_product->execute();

        if ($stmt_delete_product->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Producto y todas sus imágenes eliminados con éxito.']);
        } else {
            throw new Exception('No se encontró el producto para eliminar.');
        }

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el producto: ' . $e->getMessage()]);
    }
}

function handleDeleteImage($conn) {
    $image_id = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);
    if (!$image_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de imagen inválido.']);
        return;
    }

    // Obtener URL para borrar archivo
    $stmt_get = $conn->prepare("SELECT image_url FROM product_images WHERE id = ?");
    $stmt_get->bind_param("i", $image_id);
    $stmt_get->execute();
    $image_url = $stmt_get->get_result()->fetch_assoc()['image_url'];

    // Eliminar de la base de datos
    $stmt_delete = $conn->prepare("DELETE FROM product_images WHERE id = ?");
    $stmt_delete->bind_param("i", $image_id);
    
    if ($stmt_delete->execute()) {
        // Eliminar archivo del servidor
        if ($image_url && file_exists('../../' . $image_url)) {
            unlink('../../' . $image_url);
        }
        echo json_encode(['success' => true, 'message' => 'Imagen eliminada con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la imagen.']);
    }
}

// --- Funciones de Ayuda ---

function getProductById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $stmt_images = $conn->prepare("SELECT id, image_url FROM product_images WHERE product_id = ?");
        $stmt_images->bind_param("i", $id);
        $stmt_images->execute();
        $images_result = $stmt_images->get_result();
        $product['additional_images'] = [];
        while ($row = $images_result->fetch_assoc()) {
            $product['additional_images'][] = $row;
        }
    }
    return $product;
}

function getAllProducts($conn, $search_term = null) {
    $sql = "SELECT id, name, price, stock, image_url, category, brand FROM products";
    $params = [];
    $types = "";

    if ($search_term) {
        $sql .= " WHERE name LIKE ? OR category LIKE ? OR brand LIKE ?";
        $search_param = "%" . $search_term . "%";
        $params = [$search_param, $search_param, $search_param];
        $types = "sss";
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function uploadImage($file) {
    $target_dir = "../../assets/images/products/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_name = uniqid('img_', true) . '.' . $imageFileType;
    $target_file = $target_dir . $unique_name;

    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return ['success' => false, 'message' => 'Formato de imagen no permitido.'];
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'path' => 'assets/images/products/' . $unique_name];
    } else {
        return ['success' => false, 'message' => 'Error al mover el archivo subido.'];
    }
}

function uploadAdditionalImages($conn, $product_id, $files) {
    $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
    
    foreach ($files['tmp_name'] as $key => $tmp_name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $files['name'][$key],
                'tmp_name' => $tmp_name,
                'error' => $files['error'][$key],
            ];
            $upload_result = uploadImage($file);
            if ($upload_result['success']) {
                $stmt->bind_param("is", $product_id, $upload_result['path']);
                $stmt->execute();
            } else {
                // Opcional: registrar un error pero continuar con las demás imágenes
                error_log('Error al subir imagen adicional: ' . $upload_result['message']);
            }
        }
    }
}

function validateProductData($data) {
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = filter_var($data['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($data['stock'] ?? 0, FILTER_VALIDATE_INT);
    $category = trim($data['category'] ?? '');
    $brand = trim($data['brand'] ?? '');

    if (empty($name)) return ['success' => false, 'message' => 'El nombre es requerido.'];
    if ($price === false || $price < 0) return ['success' => false, 'message' => 'El precio es inválido.'];
    if ($stock === false || $stock < 0) return ['success' => false, 'message' => 'El stock es inválido.'];

    return [
        'success' => true,
        'data' => compact('name', 'description', 'price', 'stock', 'category', 'brand')
    ];
}

?>