<?php
require_once 'csp_handler.php';
$nonce = set_csp_header();

require_once 'check_remember_me.php';
require_once 'csrf_handler.php';
$csrf_token = generate_csrf_token();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'config.php'; // Conectar a la BD
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <title>Domus Tienda - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
        // Determina la sección activa para mostrar el contenido correcto y resaltar el enlace en el menú.
        $active_section = htmlspecialchars($_GET['section'] ?? 'stats');
    ?>

    <!-- Barra de Navegación Superior -->
    <header class="bg-light shadow-sm sticky-top">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <img src="./img/domus-logo.png" alt="Domus Logo" style="height: 40px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardNavbar" aria-controls="dashboardNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="dashboardNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php if($active_section === 'stats') echo 'active'; ?>" href="dashboard.php?section=stats">Estadísticas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if($active_section === 'products') echo 'active'; ?>" href="dashboard.php?section=products">Productos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if($active_section === 'carousel') echo 'active'; ?>" href="dashboard.php?section=carousel">Carrusel</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="register_admin.php">Registrar Admin</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php">Volver a Tienda</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Contenido Principal -->
    <main class="container-fluid my-4">
        
        <!-- Sección de Estadísticas -->
        <section id="stats" style="display: <?= ($active_section === 'stats') ? 'block' : 'none' ?>;">
            <h2>Estadísticas Clave</h2>
            <div class="stats-grid">
                 <div class="stat-card">
                    <h4>Top 5 Productos Más Vendidos</h4>
                    <div id="top-sold-list" class="stat-list"></div>
                </div>
                <div class="stat-card">
                    <h4>Productos con Bajo Stock (<5)</h4>
                    <div id="low-stock-list" class="stat-list"></div>
                </div>
                <div class="stat-card">
                    <h4>Top 5 Productos Más Vistos</h4>
                    <div id="top-viewed-list" class="stat-list"></div>
                </div>
                <div class="stat-card">
                    <h4>Top 5 Productos con Más Interacción</h4>
                    <div id="top-interacted-list" class="stat-list"></div>
                </div>
            </div>
        </section>

        <!-- Sección de Gestión de Productos -->
        <section id="products" style="display: <?= ($active_section === 'products') ? 'block' : 'none' ?>;">
            <h2>Gestión de Productos</h2>
            <div class="product-form-container">
                <h3>Añadir/Editar Producto</h3>
                <form id="product-form">
                    <input type="hidden" id="product-id">
                    <div class="form-group full-width"><label for="name">Nombre del Producto:</label><input type="text" id="name" required></div>
                    <div class="form-group full-width"><label for="description">Descripción:</label><textarea id="description"></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label for="price">Precio:</label><input type="number" id="price" step="0.01" required></div>
                        <div class="form-group"><label for="stock">Stock:</label><input type="number" id="stock" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="category_name_input">Categoría:</label><input type="text" id="category_name_input" list="categories-datalist"><datalist id="categories-datalist"></datalist></div>
                        <div class="form-group"><label for="brand_name_input">Marca:</label><input type="text" id="brand_name_input" list="brands-datalist"><datalist id="brands-datalist"></datalist></div>
                    </div>
                    <div class="form-group full-width"><label for="image_file">Imagen Principal:</label><input type="file" id="image_file" accept="image/*"><small>Esta es la imagen que se mostrará en la cuadrícula principal.</small></div>
                    <div class="form-group full-width"><label for="additional_images">Imágenes Adicionales:</label><input type="file" id="additional_images" accept="image/*" multiple><small>Puedes seleccionar varias imágenes para la galería del producto.</small></div>
                    <div id="additional-images-container" class="additional-images-preview full-width"></div>
                    <div class="form-buttons full-width"><button type="submit" id="save-product-btn">Guardar Producto</button><button type="button" id="cancel-edit-btn" style="display:none;">Cancelar Edición</button></div>
                </form>
            </div>
            <div class="product-list-container">
                <h3>Listado de Productos</h3>
                <div class="search-bar-dashboard">
                    <form action="dashboard.php" method="GET" class="d-flex">
                        <input type="hidden" name="section" value="products">
                        <input type="text" id="product-search-bar" name="search" class="form-control" placeholder="Buscar productos..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button id="product-search-button" type="submit" class="btn btn-primary ms-2"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <table id="products-table">
                    <thead><tr><th>ID</th><th>Imagen</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Categoría</th><th>Marca</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php
                        $sql = "SELECT id, name, price, stock, image_url, category, brand FROM products";
                        $search_term = $_GET['search'] ?? '';
                        if (!empty($search_term)) { $sql .= " WHERE name LIKE ? OR category LIKE ? OR brand LIKE ?"; }
                        $sql .= " ORDER BY id DESC";
                        $stmt = $conn->prepare($sql);
                        if (!empty($search_term)) { $like_term = "%{$search_term}%"; $stmt->bind_param("sss", $like_term, $like_term, $like_term); }
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result === false) {
                            echo "<tr><td colspan='8'><strong>Error:</strong> " . htmlspecialchars($stmt->error) . "</td></tr>";
                        } else {
                            if ($result->num_rows > 0) {
                                while ($product = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($product['id']) . "</td>";
                                    echo "<td><img src='" . htmlspecialchars($product['image_url']) . "' alt='" . htmlspecialchars($product['name']) . "' width='50'></td>";
                                    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                                    echo "<td>$" . htmlspecialchars(number_format($product['price'], 2)) . "</td>";
                                    echo "<td>" . htmlspecialchars($product['stock']) . "</td>";
                                    echo "<td>" . htmlspecialchars($product['category']) . "</td>";
                                    echo "<td>" . htmlspecialchars($product['brand']) . "</td>";
                                    echo "<td><div class='d-flex gap-1'>" . 
                                         "<button class='btn btn-primary btn-sm edit-product-btn' data-id='" . $product['id'] . "'>Editar</button>" . 
                                         "<form action='api/admin/products.php' method='POST' class='delete-product-form'><input type='hidden' name='action' value='delete'><input type='hidden' name='product_id' value='" . $product['id'] . "'><button type='submit' class='btn btn-danger btn-sm'>Eliminar</button></form>" . 
                                         "</div></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8'>No se encontraron productos.</td></tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Sección de Gestión de Carrusel -->
        <section id="carousel" style="display: <?= ($active_section === 'carousel') ? 'block' : 'none' ?>;">
            <h2>Gestión de Carrusel</h2>
            <div class="product-form-container">
                <h3>Subir Imágenes para Carrusel</h3>
                <form id="carousel-upload-form">
                    <div class="form-group full-width"><label for="carousel_image_file">Seleccionar Imagen:</label><input type="file" id="carousel_image_file" accept="image/*" required><small>Se recomienda un tamaño de 800x450 píxeles para flyers. Máximo 5 imágenes en total.</small></div>
                    <div class="form-buttons full-width"><button type="submit" id="upload-carousel-btn">Subir Imagen</button></div>
                </form>
            </div>
            <div class="product-list-container">
                <h3>Imágenes del Carrusel</h3>
                <div id="carousel-images-preview" class="additional-images-preview"></div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="https://kit.fontawesome.com/3b33d35414.js" crossorigin="anonymous"></script>
</body>
</html>