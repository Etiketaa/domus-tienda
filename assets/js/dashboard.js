document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- Selectores de Elementos --- //
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    const sections = document.querySelectorAll('.dashboard-section');
    const productForm = document.getElementById('product-form');
    const productIdInput = document.getElementById('product-id');
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    const priceInput = document.getElementById('price');
    const stockInput = document.getElementById('stock');
    const imageFileInput = document.getElementById('image_file');
    const additionalImagesInput = document.getElementById('additional_images');
    const additionalImagesContainer = document.getElementById('additional-images-container');
    const categoryInput = document.getElementById('category_name_input');
    const brandInput = document.getElementById('brand_name_input');
    const saveProductBtn = document.getElementById('save-product-btn');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const productsTableBody = document.querySelector('#products-table tbody');
    const ordersTableBody = document.querySelector('#orders-table tbody');
    const categoriesDatalist = document.getElementById('categories-datalist');
    const brandsDatalist = document.getElementById('brands-datalist');
    const productSearchBar = document.getElementById('product-search-bar');
    const productSearchButton = document.getElementById('product-search-button');

    // Carrusel Selectors
    const carouselUploadForm = document.getElementById('carousel-upload-form');
    const carouselImageFile = document.getElementById('carousel_image_file');
    const carouselImagesPreview = document.getElementById('carousel-images-preview');
    const uploadCarouselBtn = document.getElementById('upload-carousel-btn');

    let editingProductId = null;

    // --- Navegación --- //
    function changeSection(targetId) {
        sections.forEach(s => s.classList.remove('active'));
        navLinks.forEach(l => l.classList.remove('active'));
        document.getElementById(targetId)?.classList.add('active');
        document.querySelector(`.nav-link[data-section="${targetId}"]`)?.classList.add('active');

        if (targetId === 'products') {
            loadProducts();
            loadCategoriesAndBrands();
        } else if (targetId === 'orders') {
            loadOrders();
        } else if (targetId === 'carousel') {
            loadCarouselImages();
        }
    }

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // Si es un enlace externo, navegar directamente a su URL.
            if (link.classList.contains('nav-link-external')) {
                window.location.href = link.href;
                return;
            }

            // Si no, gestionarlo como un cambio de sección interno.
            e.preventDefault();
            const targetId = link.dataset.section;
            if (targetId) {
                changeSection(targetId);
            }
        });
    });

    // --- Gestión de Productos --- //
    async function loadProducts(searchTerm = '') {
        try {
            const url = `api/admin/products.php?search=${encodeURIComponent(searchTerm)}`;
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const products = await response.json();
            
            productsTableBody.innerHTML = '';
            products.forEach(product => {
                const row = productsTableBody.insertRow();
                const imageUrl = product.image_url || 'assets/images/placeholder.png';
                row.innerHTML = `
                    <td data-label="ID">${product.id}</td>
                    <td data-label="Imagen"><img src="${imageUrl}" alt="${product.name}" class="product-thumbnail"></td>
                    <td data-label="Nombre">${product.name}</td>
                    <td data-label="Precio">$${parseFloat(product.price).toFixed(2)}</td>
                    <td data-label="Stock">${product.stock}</td>
                    <td data-label="Categoría">${product.category || 'N/A'}</td>
                    <td data-label="Marca">${product.brand || 'N/A'}</td>
                    <td data-label="Acciones">
                        <div class="btn-group">
                            <button class="edit-btn" data-id="${product.id}">Editar</button>
                            <button class="delete-btn" data-id="${product.id}">Eliminar</button>
                        </div>
                    </td>
                `;
            });
            attachProductEventListeners();
        } catch (error) {
            console.error('Error al cargar productos:', error);
        }
    }

    productSearchButton.addEventListener('click', () => loadProducts(productSearchBar.value.trim()));
    productSearchBar.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') loadProducts(productSearchBar.value.trim());
    });

    async function loadCategoriesAndBrands() {
        try {
            const [catRes, brandRes] = await Promise.all([
                fetch('api/categories.php'),
                fetch('api/brands.php')
            ]);
            const categories = await catRes.json();
            const brands = await brandRes.json();
            categoriesDatalist.innerHTML = categories.map(c => `<option value="${c.category}"></option>`).join('');
            brandsDatalist.innerHTML = brands.map(b => `<option value="${b.brand}"></option>`).join('');
        } catch (error) {
            console.error('Error al cargar categorías y marcas:', error);
        }
    }

    function attachProductEventListeners() {
        document.querySelectorAll('.edit-btn').forEach(b => b.onclick = () => editProduct(b.dataset.id));
        document.querySelectorAll('.delete-btn').forEach(b => b.onclick = () => deleteProduct(b.dataset.id));
    }

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        saveProductBtn.disabled = true;
        saveProductBtn.textContent = 'Guardando...';

        const formData = new FormData();
        formData.append('name', nameInput.value);
        formData.append('description', descriptionInput.value);
        formData.append('price', priceInput.value);
        formData.append('stock', stockInput.value);
        formData.append('category', categoryInput.value);
        formData.append('brand', brandInput.value);

        if (imageFileInput.files[0]) {
            formData.append('image_file', imageFileInput.files[0]);
        }
        for (const file of additionalImagesInput.files) {
            formData.append('additional_images[]', file);
        }

        let url = 'api/admin/products.php';
        if (editingProductId) {
            formData.append('id', editingProductId);
            url += '?action=update'; // Acción explícita para actualizar
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData 
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                resetForm();
                loadProducts();
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error al guardar producto:', error);
            alert('Hubo un error de comunicación.');
        } finally {
            saveProductBtn.disabled = false;
            resetForm();
        }
    });

    function resetForm() {
        productForm.reset();
        editingProductId = null;
        additionalImagesContainer.innerHTML = '';
        saveProductBtn.textContent = 'Guardar Producto';
        cancelEditBtn.style.display = 'none';
    }

    cancelEditBtn.addEventListener('click', resetForm);

    async function editProduct(id) {
        try {
            const response = await fetch(`api/admin/products.php?id=${id}`);
            if (!response.ok) throw new Error('Producto no encontrado');
            const product = await response.json();

            editingProductId = product.id;
            nameInput.value = product.name;
            descriptionInput.value = product.description;
            priceInput.value = parseFloat(product.price);
            stockInput.value = parseInt(product.stock);
            categoryInput.value = product.category;
            brandInput.value = product.brand;

            displayAdditionalImages(product.additional_images || []);

            saveProductBtn.textContent = 'Actualizar Producto';
            cancelEditBtn.style.display = 'inline-block';
            window.scrollTo(0, 0);
        } catch (error) {
            console.error('Error al cargar producto para editar:', error);
            alert(error.message);
        }
    }

    function displayAdditionalImages(images) {
        additionalImagesContainer.innerHTML = '';
        images.forEach(image => {
            const wrapper = document.createElement('div');
            wrapper.className = 'additional-image-wrapper';
            wrapper.innerHTML = `
                <img src="${image.image_url}" alt="Imagen adicional">
                <button type="button" class="remove-image-btn" data-id="${image.id}">&times;</button>
            `;
            additionalImagesContainer.appendChild(wrapper);
        });
        attachImageDeleteListeners();
    }

    function attachImageDeleteListeners() {
        document.querySelectorAll('.remove-image-btn').forEach(button => {
            button.onclick = () => deleteImage(button.dataset.id);
        });
    }

    async function deleteImage(imageId) {
        if (!confirm('¿Estás seguro de eliminar esta imagen?')) return;

        try {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken); // Añadir token CSRF
            formData.append('image_id', imageId);

            const response = await fetch('api/admin/products.php?action=delete_image', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert(result.message);
                // Volver a cargar las imágenes del producto que se está editando
                if (editingProductId) {
                    editProduct(editingProductId);
                }
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error al eliminar imagen:', error);
            alert('Hubo un error de comunicación.');
        }
    }

    async function deleteProduct(id) {
        if (!confirm('¿Estás seguro de eliminar este producto? Se borrarán todas sus imágenes.')) return;

        try {
            const response = await fetch(`api/admin/products.php`, {
                method: 'DELETE',
                headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken, // Añadir el token CSRF
            },
            body: `id=${id}`
        });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                loadProducts(); // Recargar la lista de productos
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error al eliminar producto:', error);
            alert('Hubo un error de comunicación.');
        }
    }

    // --- Gestión de Pedidos ---
    async function loadOrders() {
        try {
            const response = await fetch('api/orders.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const orders = await response.json();
            
            ordersTableBody.innerHTML = '';
            if (orders.length === 0) {
                ordersTableBody.innerHTML = '<tr><td colspan="9" class="text-center">No hay pedidos para mostrar.</td></tr>';
                return;
            }

            orders.forEach(order => {
                const row = ordersTableBody.insertRow();
                const totalFormatted = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(order.total);
                const orderDate = new Date(order.order_date).toLocaleString('es-AR');
                const observaciones = order.observaciones ? order.observaciones.replace(/\n/g, '<br>') : '<em>Sin observaciones</em>';

                row.innerHTML = `
                    <td data-label="ID Pedido">${order.id}</td>
                    <td data-label="Cliente">${order.customer_name}</td>
                    <td data-label="Teléfono">${order.customer_phone}</td>
                    <td data-label="Dirección">${order.customer_address || 'N/A'}</td>
                    <td data-label="Observaciones">${observaciones}</td>
                    <td data-label="Total">${totalFormatted}</td>
                    <td data-label="Fecha">${orderDate}</td>
                    <td data-label="Estado">
                        <select class="form-select form-select-sm status-select" data-order-id="${order.id}">
                            <option value="pendiente" ${order.status === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                            <option value="cotizacion_pendiente" ${order.status === 'cotizacion_pendiente' ? 'selected' : ''}>Cotización Pendiente</option>
                            <option value="en_preparacion" ${order.status === 'en_preparacion' ? 'selected' : ''}>En Preparación</option>
                            <option value="enviado" ${order.status === 'enviado' ? 'selected' : ''}>Enviado</option>
                            <option value="entregado" ${order.status === 'entregado' ? 'selected' : ''}>Entregado</option>
                            <option value="cancelado" ${order.status === 'cancelado' ? 'selected' : ''}>Cancelado</option>
                        </select>
                    </td>
                    <td data-label="Acciones">
                        <button class="btn btn-sm btn-info view-details-btn" data-order-id="${order.id}">Ver Detalles</button>
                    </td>
                `;
            });

            attachOrderEventListeners();

        } catch (error) {
            console.error('Error al cargar pedidos:', error);
            ordersTableBody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error al cargar los pedidos.</td></tr>';
        }
    }

    function attachOrderEventListeners() {
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const orderId = e.target.dataset.orderId;
                const newStatus = e.target.value;
                updateOrderStatus(orderId, newStatus);
            });
        });
        // Aquí se podrían añadir listeners para el botón "Ver Detalles" en el futuro
    }

    async function updateOrderStatus(orderId, status) {
        try {
            const response = await fetch('api/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: orderId, status: status })
            });
            const result = await response.json();
            if (!result.success) {
                alert('Error al actualizar el estado: ' + result.message);
                loadOrders(); // Recargar para revertir el cambio visual
            }
        } catch (error) {
            console.error('Error updating order status:', error);
            alert('Error de conexión al actualizar el estado.');
            loadOrders();
        }
    }

    // --- Gestión de Carrusel --- //
    async function loadCarouselImages() {
        try {
            const response = await fetch('api/admin/carousel.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            if (result.success) {
                carouselImagesPreview.innerHTML = '';
                result.images.forEach(image => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'additional-image-wrapper'; // Reutilizar estilo
                    wrapper.innerHTML = `
                        <img src="${image.image_url}" alt="Imagen de carrusel">
                        <button type="button" class="remove-image-btn" data-id="${image.id}" data-type="carousel">&times;</button>
                    `;
                    carouselImagesPreview.appendChild(wrapper);
                });
                attachCarouselImageDeleteListeners();
            } else {
                console.error('Error al cargar imágenes del carrusel:', result.message);
            }
        } catch (error) {
            console.error('Error al cargar imágenes del carrusel:', error);
        }
    }

    carouselUploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        uploadCarouselBtn.disabled = true;
        uploadCarouselBtn.textContent = 'Subiendo...';

        const formData = new FormData();
        if (carouselImageFile.files[0]) {
            formData.append('image', carouselImageFile.files[0]);
        } else {
            alert('Por favor, selecciona una imagen para subir.');
            uploadCarouselBtn.disabled = false;
            uploadCarouselBtn.textContent = 'Subir Imagen';
            return;
        }

        try {
            const response = await fetch('api/admin/carousel.php?action=upload', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                carouselImageFile.value = ''; // Limpiar input de archivo
                loadCarouselImages();
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error al subir imagen del carrusel:', error);
            alert('Hubo un error de comunicación.');
        } finally {
            uploadCarouselBtn.disabled = false;
            uploadCarouselBtn.textContent = 'Subir Imagen';
        }
    });

    function attachCarouselImageDeleteListeners() {
        document.querySelectorAll('.remove-image-btn[data-type="carousel"]').forEach(button => {
            button.onclick = () => deleteCarouselImage(button.dataset.id);
        });
    }

    async function deleteCarouselImage(imageId) {
        if (!confirm('¿Estás seguro de eliminar esta imagen del carrusel?')) return;

        try {
            const response = await fetch(`api/admin/carousel.php?id=${imageId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${imageId}`
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                loadCarouselImages();
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error al eliminar imagen del carrusel:', error);
            alert('Hubo un error de comunicación.');
        }
    }

    // --- Carga de Estadísticas ---
    async function initializeStats() {
        try {
            const response = await fetch('api/chart_data.php');
            if (!response.ok) {
                throw new Error(`Error al cargar datos de estadísticas: ${response.statusText}`);
            }
            const statsData = await response.json();

            // Función auxiliar para crear la lista de estadísticas
            const createStatList = (elementId, items, valueName) => {
                const container = document.getElementById(elementId);
                if (!container) return;

                if (!items || items.labels.length === 0) {
                    container.innerHTML = '<p class="text-muted">No hay datos disponibles.</p>';
                    return;
                }

                const list = document.createElement('ul');
                for (let i = 0; i < items.labels.length; i++) {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <span class="stat-label">${items.labels[i]}</span>
                        <span class="stat-value">${items.data[i]} ${valueName}</span>
                    `;
                    list.appendChild(li);
                }
                container.innerHTML = '';
                container.appendChild(list);
            };

            // Renderizar cada lista de estadísticas
            createStatList('top-sold-list', statsData.topSold, 'vendidos');
            createStatList('low-stock-list', statsData.lowStock, 'en stock');
            createStatList('top-viewed-list', statsData.topViewed, 'vistas');
            createStatList('top-interacted-list', statsData.topInteracted, 'interacciones');

        } catch (error) {
            console.error('Error inicializando las estadísticas:', error);
        }
    }

    // --- Inicialización --- //
    initializeStats();
    changeSection('stats');
});