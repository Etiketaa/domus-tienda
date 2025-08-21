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

    // La navegación ahora es manejada por PHP con recargas de página.

    // --- Gestión de Productos (MODIFICADO) --- //

    /*
    La carga de productos y la función de eliminación ahora se manejan con PHP para simplificar.
    El siguiente código JavaScript ha sido desactivado o modificado para reflejar estos cambios.
    */

    // La función loadProducts() está desactivada. La tabla se genera directamente en dashboard.php.
    // async function loadProducts(searchTerm = '') { ... }

    // Los listeners de búsqueda están desactivados.
    // productSearchButton.addEventListener('click', () => loadProducts(productSearchBar.value.trim()));
    // productSearchBar.addEventListener('keyup', (e) => { ... });

    // Carga las categorías y marcas en los datalists del formulario, esta función se mantiene.
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

    // Adjunta los listeners a los botones de editar generados por PHP y a los formularios de eliminación.
    function attachProductEventListeners() {
        // Listener para botones de Editar
        document.querySelectorAll('.edit-product-btn').forEach(b => b.onclick = () => editProduct(b.dataset.id));

        // Listener para formularios de Eliminar
        document.querySelectorAll('.delete-product-form').forEach(form => {
            form.addEventListener('submit', function(event) {
                const confirmation = confirm('¿Estás seguro de que quieres eliminar este producto? Se borrarán también todas sus imágenes.');
                if (!confirmation) {
                    event.preventDefault(); // Cancela el envío del formulario si el usuario dice 'No'
                }
            });
        });
    }
    // Se llama una vez para que los botones de la tabla inicial funcionen.
    attachProductEventListeners();

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
            url += '?action=update';
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData 
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                // En lugar de recargar con JS, se recarga toda la página
                // para mostrar la tabla actualizada desde PHP.
                location.reload();
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error al guardar producto:', error);
            alert('Hubo un error de comunicación.');
        } finally {
            saveProductBtn.disabled = false;
            // El reset del formulario se maneja con el reload de la página.
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
            formData.append('csrf_token', csrfToken);
            formData.append('image_id', imageId);

            const response = await fetch('api/admin/products.php?action=delete_image', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert(result.message);
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

    // La función deleteProduct() está desactivada. La eliminación se hace con un formulario PHP.
    // async function deleteProduct(id) { ... }

    

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

    // --- Inicialización ---
    initializeStats();
    // changeSection('stats'); // Se desactiva para que PHP controle la pestaña activa al cargar la página.
});