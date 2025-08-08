document.addEventListener('DOMContentLoaded', () => {
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
            if (link.classList.contains('nav-link-external')) {
                return;
            }

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

    // --- Gestión de Pedidos (sin cambios) --- //
    async function loadOrders() {
        // ... (código existente)
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

    // --- Gestión de Pedidos (sin cambios) --- //
    async function loadOrders() {
        // ... (código existente)
    }

    // --- Inicialización --- //
    changeSection('stats');
});