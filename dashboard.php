<?php
require_once 'csp_handler.php';
$nonce = set_csp_header();

require_once 'check_remember_me.php';

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
<body class="dashboard-body">
    <div class="dashboard-container">
        <aside class="dashboard-sidebar d-none d-md-block">
            <div class="sidebar-header">
                <a href="index.php"><img src="./img/domus-logo.png" alt="Domus Logo" class="header-logo"></a>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-link active" data-section="stats"><i class="fas fa-chart-bar"></i> Estadísticas</a>
                <a href="#" class="nav-link" data-section="products"><i class="fas fa-box"></i> Productos</a>
                <a href="#" class="nav-link" data-section="orders"><i class="fas fa-receipt"></i> Pedidos</a>
                <a href="#" class="nav-link" data-section="carousel"><i class="fas fa-images"></i> Carrusel</a>
                <a href="register_admin.php" class="nav-link nav-link-external"><i class="fas fa-user-plus"></i> Registrar Admin</a>
                <a href="index.php" class="nav-link nav-link-external"><i class="fas fa-store"></i> Volver a Tienda</a>
            </nav>
        </aside>

        <!-- Offcanvas para el menú en móviles -->
        <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="offcanvasDashboard" aria-labelledby="offcanvasDashboardLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasDashboardLabel">Menú de Administración</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <nav class="sidebar-nav">
                    <a href="#" class="nav-link active" data-section="stats" data-bs-dismiss="offcanvas"><i class="fas fa-chart-bar"></i> Estadísticas</a>
                    <a href="#" class="nav-link" data-section="products" data-bs-dismiss="offcanvas"><i class="fas fa-box"></i> Productos</a>
                    <a href="#" class="nav-link" data-section="orders" data-bs-dismiss="offcanvas"><i class="fas fa-receipt"></i> Pedidos</a>
                    <a href="register_admin.php" class="nav-link nav-link-external" data-bs-dismiss="offcanvas"><i class="fas fa-user-plus"></i> Registrar Admin</a>
                    <a href="index.php" class="nav-link nav-link-external" data-bs-dismiss="offcanvas"><i class="fas fa-store"></i> Volver a Tienda</a>
                </nav>
            </div>
        </div>

        <main class="dashboard-main-content">
            <!-- Botón para abrir el offcanvas en móviles -->
            <button class="btn btn-primary d-md-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDashboard" aria-controls="offcanvasDashboard">
                <i class="fas fa-bars"></i> Menú
            </button>

            <!-- Sección de Estadísticas -->
            <section id="stats" class="dashboard-section active">
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
            <section id="products" class="dashboard-section">
                <h2>Gestión de Productos</h2>
                <div class="product-form-container">
                    <h3>Añadir/Editar Producto</h3>
                    <form id="product-form">
                        <input type="hidden" id="product-id">
                        
                        <div class="form-group full-width">
                            <label for="name">Nombre del Producto:</label>
                            <input type="text" id="name" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Descripción:</label>
                            <textarea id="description"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Precio:</label>
                                <input type="number" id="price" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="stock">Stock:</label>
                                <input type="number" id="stock" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_name_input">Categoría:</label>
                                <input type="text" id="category_name_input" list="categories-datalist">
                                <datalist id="categories-datalist"></datalist>
                            </div>
                            <div class="form-group">
                                <label for="brand_name_input">Marca:</label>
                                <input type="text" id="brand_name_input" list="brands-datalist">
                                <datalist id="brands-datalist"></datalist>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="image_file">Imagen Principal:</label>
                            <input type="file" id="image_file" accept="image/*">
                            <small>Esta es la imagen que se mostrará en la cuadrícula principal.</small>
                        </div>

                        <div class="form-group full-width">
                            <label for="additional_images">Imágenes Adicionales:</label>
                            <input type="file" id="additional_images" accept="image/*" multiple>
                            <small>Puedes seleccionar varias imágenes para la galería del producto.</small>
                        </div>

                        <div id="additional-images-container" class="additional-images-preview full-width">
                            <!-- Las imágenes adicionales existentes se mostrarán aquí -->
                        </div>

                        <div class="form-buttons full-width">
                            <button type="submit" id="save-product-btn">Guardar Producto</button>
                            <button type="button" id="cancel-edit-btn" style="display:none;">Cancelar Edición</button>
                        </div>
                    </form>
                </div>
                <div class="product-list-container">
                    <h3>Listado de Productos</h3>
                    <div class="search-bar-dashboard">
                        <input type="text" id="product-search-bar" placeholder="Buscar productos...">
                        <button id="product-search-button"><i class="fas fa-search"></i></button>
                    </div>
                    <table id="products-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Categoría</th>
                                <th>Marca</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filas de productos se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Sección de Gestión de Pedidos -->
            <section id="orders" class="dashboard-section">
                <h2>Gestión de Pedidos</h2>
                <div class="order-list-container">
                    <h3>Listado de Pedidos</h3>
                    <table id="orders-table">
                        <thead>
                            <tr>
                                <th>ID Pedido</th>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Observaciones</th>
                                <th>Total</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filas de pedidos se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Sección de Gestión de Carrusel -->
            <section id="carousel" class="dashboard-section">
                <h2>Gestión de Carrusel</h2>
                <div class="product-form-container">
                    <h3>Subir Imágenes para Carrusel</h3>
                    <form id="carousel-upload-form">
                        <div class="form-group full-width">
                            <label for="carousel_image_file">Seleccionar Imagen:</label>
                            <input type="file" id="carousel_image_file" accept="image/*" required>
                            <small>Se recomienda un tamaño de 800x450 píxeles para flyers. Máximo 5 imágenes en total.</small>
                        </div>
                        <div class="form-buttons full-width">
                            <button type="submit" id="upload-carousel-btn">Subir Imagen</button>
                        </div>
                    </form>
                </div>
                <div class="product-list-container">
                    <h3>Imágenes del Carrusel</h3>
                    <div id="carousel-images-preview" class="additional-images-preview">
                        <!-- Las imágenes del carrusel se cargarán aquí -->
                    </div>
                </div>
            </section>
        </main>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>