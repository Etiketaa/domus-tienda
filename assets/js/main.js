document.addEventListener('DOMContentLoaded', () => {
    const cartItemsContainer = document.getElementById('cart-items');
    const cartTotalElement = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('checkout-btn');
    const customerForm = document.getElementById('customer-data-form');
    const openCartBtn = document.getElementById('open-cart-btn');
    const closeCartBtn = document.getElementById('close-cart-btn');
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartItemCountElement = document.querySelector('.cart-item-count');

    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Función para registrar interacciones
    async function logInteraction(productId, interactionType) {
        try {
            await fetch('api/log_interaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, interaction_type: interactionType })
            });
        } catch (error) {
            console.error('Error al registrar interacción:', error);
        }
    }

    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
    }

    function setupProductButtons() {
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        addToCartButtons.forEach(button => {
            button.removeEventListener('click', addToCartHandler); // Evitar duplicados
            button.addEventListener('click', addToCartHandler);
        });

        const detailsButtons = document.querySelectorAll('.details-btn');
        detailsButtons.forEach(button => {
            button.removeEventListener('click', showProductDetailsHandler);
            button.addEventListener('click', showProductDetailsHandler);
        });
    }

    function addToCartHandler(e) {
        const productCard = e.target.closest('.product-card');
        const productId = parseInt(e.target.dataset.id);
        const productName = productCard.querySelector('h3').textContent;
        const priceElement = productCard.querySelector('.price');
        const productPrice = priceElement ? parseFloat(priceElement.textContent.replace('$', '')) : 0;
        const productBrand = productCard.querySelector('.brand') ? productCard.querySelector('.brand').textContent : '';
        addToCart(productId, productName, productPrice, productBrand);
        logInteraction(productId, 'add_to_cart_click');
    }

    async function showProductDetailsHandler(e) {
        const productId = e.target.dataset.id;
        await showProductDetails(productId);
        logInteraction(productId, 'detail_click');
    }

    function addToCart(id, name, price, brand) {
        const existingItem = cart.find(item => item.id === id);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({ id, name, price, quantity: 1, brand });
        }
        saveCart();
        renderCart();
        // Animación para el botón del carrito
        openCartBtn.classList.add('item-added');
        setTimeout(() => {
            openCartBtn.classList.remove('item-added');
        }, 600);
    }

    function renderCart() {
        cartItemsContainer.innerHTML = '';
        let total = 0;
        let containsApBrand = false;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p class="empty-cart-message">Tu carrito está vacío.</p>';
        } else {
            cart.forEach(item => {
                const isAp = item.brand && item.brand.toLowerCase() === 'ap';
                if (isAp) containsApBrand = true;

                const itemElement = document.createElement('div');
                itemElement.classList.add('cart-item');
                itemElement.innerHTML = `
                    <div class="cart-item-info">
                        <span class="cart-item-name">${item.name}</span>
                        <span class="cart-item-price">${isAp ? 'A consultar' : '$ ' + item.price.toFixed(2)}</span>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" data-id="${item.id}" data-action="decrease">-</button>
                        <span>${item.quantity}</span>
                        <button class="quantity-btn" data-id="${item.id}" data-action="increase">+</button>
                        <button class="remove-item-btn" data-id="${item.id}" title="Eliminar">&times;</button>
                    </div>
                `;
                cartItemsContainer.appendChild(itemElement);
                if (!isAp) {
                    total += item.price * item.quantity;
                }
            });
        }

        if (containsApBrand) {
            cartTotalElement.innerHTML = '<span class="price-ap">Total a coordinar</span>';
            checkoutBtn.textContent = 'Solicitar Cotización';
        } else {
            cartTotalElement.textContent = total.toFixed(2);
            checkoutBtn.textContent = 'Confirmar Pedido';
        }

        updateCartCount();
        setupCartActionButtons();
    }

    function setupCartActionButtons() {
        cartItemsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('quantity-btn')) {
                const productId = parseInt(e.target.dataset.id);
                const action = e.target.dataset.action;
                updateQuantity(productId, action);
            } else if (e.target.classList.contains('remove-item-btn')) {
                const productId = parseInt(e.target.dataset.id);
                removeFromCart(productId);
            }
        });
    }

    function updateQuantity(id, action) {
        const item = cart.find(item => item.id === id);
        if (item) {
            if (action === 'increase') {
                item.quantity++;
            } else if (action === 'decrease') {
                item.quantity--;
                if (item.quantity <= 0) {
                    removeFromCart(id);
                    return;
                }
            }
            saveCart();
            renderCart();
        }
    }

    function removeFromCart(id) {
        const itemIndex = cart.findIndex(item => item.id === id);
        if (itemIndex > -1) {
            cart.splice(itemIndex, 1);
            saveCart();
            renderCart();
        }
    }

    function updateCartCount() {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        if (totalItems > 0) {
            cartItemCountElement.style.display = 'flex';
            cartItemCountElement.textContent = totalItems > 9 ? '9+' : totalItems;
        } else {
            cartItemCountElement.style.display = 'none';
        }
    }

    checkoutBtn.addEventListener('click', () => {
        const customerName = customerForm.querySelector('#customer-name').value.trim();
        const customerPhone = customerForm.querySelector('#customer-phone').value.trim();
        const customerAddress = customerForm.querySelector('#customer-address').value.trim();

        if (cart.length === 0) {
            alert('Tu carrito está vacío. Añade productos antes de confirmar.');
            return;
        }

        if (!customerName || !customerPhone) {
            alert('Por favor, completa tu nombre y teléfono para continuar.');
            return;
        }

        checkoutBtn.classList.add('loading');
        checkoutBtn.disabled = true;
        checkoutBtn.textContent = 'Procesando...';

        const orderData = {
            customer: { name: customerName, phone: customerPhone, address: customerAddress },
            items: cart,
            total: parseFloat(cartTotalElement.textContent)
        };

        fetch('api/create-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`¡Pedido #${data.order_id} realizado con éxito! Te contactaremos por WhatsApp para coordinar el pago y la entrega.`);
                cart = [];
                saveCart();
                renderCart();
                customerForm.reset();
                cartSidebar.classList.remove('open');
            } else {
                alert('Error al crear el pedido: ' + (data.message || 'Inténtalo de nuevo más tarde.'));
            }
        })
        .catch(error => {
            console.error('Error en el fetch:', error);
            alert('Hubo un problema de conexión. Por favor, revisa tu red y vuelve a intentarlo.');
        })
        .finally(() => {
            checkoutBtn.classList.remove('loading');
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = 'Confirmar Pedido';
        });
    });

    openCartBtn.addEventListener('click', () => cartSidebar.classList.add('open'));
    closeCartBtn.addEventListener('click', () => cartSidebar.classList.remove('open'));

    function fetchProducts(category = null, searchTerm = '') {
        let url = 'api/products.php';
        const params = new URLSearchParams();

        if (category && category !== 'all') {
            params.append('category', category);
        }
        if (searchTerm) {
            params.append('search', searchTerm);
        }

        if (params.toString()) {
            url += `?${params.toString()}`;
        }

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const productsGrid = document.querySelector('.products-grid');
                if (productsGrid) {
                    productsGrid.innerHTML = html;
                    setupProductButtons(); // Re-attach event listeners after new products are loaded
                    document.querySelectorAll('.product-card').forEach(card => {
                        const productId = parseInt(card.querySelector('.add-to-cart-btn').dataset.id);
                        if (productId) {
                            logInteraction(productId, 'view');
                        }
                    });
                } else {
                    console.error('El contenedor de productos .products-grid no se encontró.');
                }
            })
            .catch(error => console.error('Error fetching products:', error));
    }

    function setupCategoryButtons() {
        const categoryButtons = document.querySelectorAll('.category-btn');
        categoryButtons.forEach(button => {
            button.addEventListener('click', () => {
                const categoryId = button.dataset.categoryId;
                
                // Manejar la clase activa para los botones
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                fetchProducts(categoryId);

                // Si estamos en vista móvil, cerramos el offcanvas
                const offcanvasElement = document.getElementById('offcanvasFilters');
                if (offcanvasElement) {
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                }
            });
        });
    }

    const searchBar = document.getElementById('search-bar');
    const searchButton = document.getElementById('search-button');
    if (searchButton) {
        searchButton.addEventListener('click', () => {
            const searchTerm = searchBar.value.trim();
            fetchProducts(null, searchTerm);
        });
    }
    if (searchBar) {
        searchBar.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                const searchTerm = searchBar.value.trim();
                fetchProducts(null, searchTerm);
            }
        });
    }

    async function showProductDetails(id) {
        try {
            const response = await fetch(`api/admin/products.php?id=${id}`); // Usar el endpoint de admin que devuelve más datos
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const product = await response.json();

            if (product && product.id) {
                const isApBrand = product.brand && product.brand.toLowerCase() === 'ap';
                const mainImageUrl = product.image_url || 'assets/images/placeholder.png';

                const modalImage = $('#modal-product-image');
                modalImage.attr('src', mainImageUrl).attr('loading', 'lazy');
                
                $('#modal-product-name').text(product.name);
                $('#modal-product-description').text(product.description || 'No hay descripción disponible.');
                
                if (isApBrand) {
                    $('#modal-product-price').html('<span class="price-ap">Precio a consultar por WhatsApp</span>');
                } else {
                    $('#modal-product-price').text(`${parseFloat(product.price).toFixed(2)}`);
                }

                $('#modal-product-category').text(product.category || 'N/A');
                $('#modal-product-brand').text(product.brand || 'N/A');

                // Poblar la galería de miniaturas
                const gallery = $('#modal-product-gallery');
                gallery.html(''); // Limpiar galería anterior

                // Añadir la imagen principal como la primera miniatura
                gallery.append(`<img src="${mainImageUrl}" alt="${product.name}" class="active-thumb" loading="lazy">`);

                // Añadir imágenes adicionales
                if (product.additional_images && product.additional_images.length > 0) {
                    product.additional_images.forEach(img => {
                        gallery.append(`<img src="${img.image_url}" alt="${product.name}" loading="lazy">`);
                    });
                }

                // Evento para cambiar imagen al pasar el mouse
                $('.product-gallery-thumbnails img').on('mouseenter', function() {
                    const newSrc = $(this).attr('src');
                    modalImage.css('opacity', 0);
                    setTimeout(() => {
                        modalImage.attr('src', newSrc);
                        modalImage.css('opacity', 1);
                    }, 300);
                    $('.product-gallery-thumbnails img').removeClass('active-thumb');
                    $(this).addClass('active-thumb');
                });

                const addToCartBtn = $('#modal-add-to-cart-btn');
                if (product.stock > 0 && !isApBrand) {
                    addToCartBtn.show().data('id', product.id).data('name', product.name).data('price', product.price).data('brand', product.brand);
                } else {
                    addToCartBtn.hide();
                }

                $('#productDetailsBootstrapModal').modal('show');
            } else {
                alert('Producto no encontrado o datos inválidos.');
            }
        } catch (error) {
            console.error('Error al cargar detalles del producto:', error);
            alert('Hubo un problema al cargar los detalles del producto.');
        }
    }

    $('#modal-add-to-cart-btn').on('click', function() {
        const productId = $(this).data('id');
        const productName = $(this).data('name');
        const productPrice = $(this).data('price');
        const productBrand = $(this).data('brand');
        addToCart(productId, productName, productPrice, productBrand);
        logInteraction(productId, 'add_to_cart_click');
        $('#productDetailsBootstrapModal').modal('hide');
    });

    async function loadCarousel() {
        try {
            const response = await fetch('api/admin/carousel.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            const carouselInner = document.getElementById('carousel-inner-container');
            carouselInner.innerHTML = ''; // Clear existing content

            if (result.success && result.images.length > 0) {
                result.images.forEach((image, index) => {
                    const carouselItem = document.createElement('div');
                    carouselItem.classList.add('carousel-item');
                    if (index === 0) {
                        carouselItem.classList.add('active');
                    }
                    carouselItem.innerHTML = `<img src="${image.image_url}" class="d-block w-100" alt="Promoción de sahumerios y velas" loading="lazy">`;
                    carouselInner.appendChild(carouselItem);
                });

                // Initialize Bootstrap Carousel manually if needed (it should auto-init with data-bs-ride)
                const carouselElement = document.getElementById('carouselExampleIndicators');
                if (carouselElement) {
                    new bootstrap.Carousel(carouselElement, { interval: 5000 }); // Auto-advance every 5 seconds
                }

            } else {
                carouselInner.innerHTML = '<p class="text-center text-muted">No hay imágenes en el carrusel.</p>';
            }
        } catch (error) {
            console.error('Error al cargar imágenes del carrusel:', error);
            const carouselInner = document.getElementById('carousel-inner-container');
            carouselInner.innerHTML = '<p class="text-center text-danger">Error al cargar el carrusel.</p>';
        }
    }

    // Inicialización
    setupProductButtons();
    renderCart();
    setupCategoryButtons();
    loadCarousel();
});