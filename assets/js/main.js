document.addEventListener('DOMContentLoaded', () => {
    // --- CACHED SELECTORS ---
    const cartItemsContainer = document.getElementById('cart-items');
    const cartTotalElement = document.getElementById('cart-total');
    const cartItemCountElement = document.querySelector('.cart-item-count');
    const customerForm = document.getElementById('customer-data-form');
    const productsGrid = document.querySelector('.products-grid');
    const categoryButtonsContainer = document.querySelector('.categories-container'); // Assuming a container for category buttons
    const searchBar = document.getElementById('search-bar');
    const searchButton = document.getElementById('search-button');
    const openCartBtn = document.getElementById('open-cart-btn');
    const checkoutBtn = document.getElementById('checkout-btn');
    const carouselInner = document.getElementById('carousel-inner-container');
    const carouselElement = document.getElementById('carouselExampleIndicators');
    const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));

    // Modal Product Details Selectors
    const productDetailsModal = {
        modal: $('#productDetailsBootstrapModal'),
        image: $('#modal-product-image'),
        name: $('#modal-product-name'),
        description: $('#modal-product-description'),
        price: $('#modal-product-price'),
        category: $('#modal-product-category'),
        brand: $('#modal-product-brand'),
        gallery: $('#modal-product-gallery'),
        addToCartBtn: $('#modal-add-to-cart-btn')
    };

    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // --- FUNCTIONS ---

    const formatPrice = (price) => {
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 2,
        }).format(price);
    };

    const logInteraction = async (productId, interactionType) => {
        try {
            await fetch('api/log_interaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, interaction_type: interactionType })
            });
        } catch (error) {
            console.error('Error logging interaction:', error);
        }
    };

    const saveCart = () => {
        localStorage.setItem('cart', JSON.stringify(cart));
    };

    const addToCart = (id, name, price, brand) => {
        const existingItem = cart.find(item => item.id === id);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({ id, name, price, quantity: 1, brand });
        }
        saveCart();
        renderCart();
        openCartBtn.classList.add('item-added');
        setTimeout(() => openCartBtn.classList.remove('item-added'), 600);
    };

    const updateQuantity = (id, action) => {
        const item = cart.find(item => item.id === id);
        if (!item) return;

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
    };

    const removeFromCart = (id) => {
        const itemIndex = cart.findIndex(item => item.id === id);
        if (itemIndex > -1) {
            cart.splice(itemIndex, 1);
            saveCart();
            renderCart();
        }
    };

    const renderCart = () => {
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
                        <span class="cart-item-price">${isAp ? 'A consultar' : formatPrice(item.price)}</span>
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
            cartTotalElement.textContent = formatPrice(total);
            checkoutBtn.textContent = 'Confirmar Pedido';
        }

        updateCartCount();
    };

    const updateCartCount = () => {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartItemCountElement.style.display = totalItems > 0 ? 'flex' : 'none';
        cartItemCountElement.textContent = totalItems > 9 ? '9+' : totalItems;
    };

    const fetchProducts = async (category = null, searchTerm = '') => {
        let url = 'api/products.php';
        const params = new URLSearchParams();

        if (category && category !== 'all') {
            params.append('category', category);
        }
        if (searchTerm) {
            params.append('search', searchTerm);
        }

        const paramString = params.toString();
        if (paramString) {
            url += `?${paramString}`;
        }

        try {
            const response = await fetch(url);
            const html = await response.text();
            if (productsGrid) {
                productsGrid.innerHTML = html;
                document.querySelectorAll('.product-card').forEach(card => {
                    const productId = parseInt(card.querySelector('.add-to-cart-btn')?.dataset.id);
                    if (productId) {
                        logInteraction(productId, 'view');
                    }
                });
            } else {
                console.error('Product grid container .products-grid not found.');
            }
        } catch (error) {
            console.error('Error fetching products:', error);
        }
    };

    const showProductDetails = async (id) => {
        try {
            const response = await fetch(`api/products.php?id=${id}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const product = await response.json();

            if (!product || !product.id) {
                alert('Producto no encontrado o datos inválidos.');
                return;
            }

            const isApBrand = product.brand && product.brand.toLowerCase() === 'ap';
            const mainImageUrl = product.image_url || 'assets/images/placeholder.png';

            productDetailsModal.image.attr('src', mainImageUrl).attr('loading', 'lazy');
            productDetailsModal.name.text(product.name);
            productDetailsModal.description.text(product.description || 'No hay descripción disponible.');
            productDetailsModal.price.html(isApBrand ? '<span class="price-ap">Precio a consultar por WhatsApp</span>' : formatPrice(product.price));
            productDetailsModal.category.text(product.category || 'N/A');
            productDetailsModal.brand.text(product.brand || 'N/A');

            // Populate gallery
            productDetailsModal.gallery.html('');
            productDetailsModal.gallery.append(`<img src="${mainImageUrl}" alt="${product.name}" class="active-thumb" loading="lazy">`);
            if (product.additional_images && product.additional_images.length > 0) {
                product.additional_images.forEach(img => {
                    productDetailsModal.gallery.append(`<img src="${img.image_url}" alt="${product.name}" loading="lazy">`);
                });
            }

            // Add to cart button
            if (product.stock > 0 && !isApBrand) {
                productDetailsModal.addToCartBtn.show().data({
                    id: product.id,
                    name: product.name,
                    price: product.price,
                    brand: product.brand
                });
            } else {
                productDetailsModal.addToCartBtn.hide();
            }

            productDetailsModal.modal.modal('show');
        } catch (error) {
            console.error('Error loading product details:', error);
            alert('Hubo un problema al cargar los detalles del producto.');
        }
    };

    const loadCarousel = async () => {
        try {
            const response = await fetch('api/carousel.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            if (carouselInner) {
                carouselInner.innerHTML = '';
                if (result.success && result.images.length > 0) {
                    result.images.forEach((image, index) => {
                        const item = document.createElement('div');
                        item.className = `carousel-item ${index === 0 ? 'active' : ''}`;
                        item.innerHTML = `<img src="${image.image_url}" class="d-block w-100" alt="Promoción" loading="lazy">`;
                        carouselInner.appendChild(item);
                    });

                    if (carouselElement) {
                        new bootstrap.Carousel(carouselElement, { interval: 5000 });
                    }
                } else {
                    carouselInner.innerHTML = '<p class="text-center text-muted">No hay imágenes en el carrusel.</p>';
                }
            }
        } catch (error) {
            console.error('Error loading carousel images:', error);
            if (carouselInner) {
                carouselInner.innerHTML = '<p class="text-center text-danger">Error al cargar el carrusel.</p>';
            }
        }
    };

    const handleCheckout = async () => {
        const customerName = customerForm.querySelector('#customer-name').value.trim();
        const customerPhone = customerForm.querySelector('#customer-phone').value.trim();
        const customerAddress = customerForm.querySelector('#customer-address').value.trim();

        if (cart.length === 0) {
            alert('Tu carrito está vacío.');
            return;
        }

        if (!customerName || !customerPhone) {
            alert('Por favor, completa tu nombre y teléfono.');
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

        try {
            const response = await fetch('api/create-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });
            const data = await response.json();
            if (data.success) {
                alert(`¡Pedido #${data.order_id} realizado con éxito!`);
                cart = [];
                saveCart();
                renderCart();
                customerForm.reset();
                cartModal.hide();
            } else {
                alert('Error al crear el pedido: ' + (data.message || 'Inténtalo de nuevo.'));
            }
        } catch (error) {
            console.error('Checkout fetch error:', error);
            alert('Hubo un problema de conexión.');
        } finally {
            checkoutBtn.classList.remove('loading');
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = 'Confirmar Pedido';
        }
    };


    // --- EVENT LISTENERS ---

    if (openCartBtn) openCartBtn.addEventListener('click', () => cartModal.show());
    if (checkoutBtn) checkoutBtn.addEventListener('click', handleCheckout);

    if (cartItemsContainer) {
        cartItemsContainer.addEventListener('click', (e) => {
            const target = e.target;
            const productId = parseInt(target.dataset.id);

            if (target.classList.contains('quantity-btn')) {
                const action = target.dataset.action;
                updateQuantity(productId, action);
            } else if (target.classList.contains('remove-item-btn')) {
                removeFromCart(productId);
            }
        });
    }

    if (productsGrid) {
        productsGrid.addEventListener('click', (e) => {
            const target = e.target;
            const productCard = target.closest('.product-card');
            if (!productCard) return;

            const productId = parseInt(target.dataset.id);

            if (target.classList.contains('add-to-cart-btn')) {
                const productName = productCard.querySelector('h3').textContent;
                const priceElement = productCard.querySelector('.price');
                const productPrice = priceElement ? parseFloat(priceElement.textContent.replace(/[^0-9,-]+/g,"").replace(",", ".")) : 0;
                const productBrand = productCard.querySelector('.brand')?.textContent || '';
                addToCart(productId, productName, productPrice, productBrand);
                logInteraction(productId, 'add_to_cart_click');
            } else if (target.classList.contains('details-btn')) {
                showProductDetails(productId);
                logInteraction(productId, 'detail_click');
            }
        });
    }

    if (categoryButtonsContainer) {
        categoryButtonsContainer.addEventListener('click', (e) => {
            const target = e.target.closest('.category-btn');
            if (!target) return;

            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
            target.classList.add('active');

            const categoryId = target.dataset.categoryId;
            fetchProducts(categoryId);

            const offcanvasElement = document.getElementById('offcanvasFilters');
            if (offcanvasElement) {
                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                if (offcanvas) {
                    offcanvas.hide();
                }
            }
        });
    }

    const performSearch = () => {
        const searchTerm = searchBar.value.trim();
        fetchProducts(null, searchTerm);
    };

    if (searchButton) searchButton.addEventListener('click', performSearch);
    if (searchBar) searchBar.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') performSearch();
    });

    productDetailsModal.gallery.on('mouseenter', 'img', function() {
        const newSrc = $(this).attr('src');
        productDetailsModal.image.css('opacity', 0);
        setTimeout(() => {
            productDetailsModal.image.attr('src', newSrc);
            productDetailsModal.image.css('opacity', 1);
        }, 300);
        productDetailsModal.gallery.find('img').removeClass('active-thumb');
        $(this).addClass('active-thumb');
    });

    productDetailsModal.addToCartBtn.on('click', function() {
        const { id, name, price, brand } = $(this).data();
        addToCart(id, name, price, brand);
        logInteraction(id, 'add_to_cart_click');
        productDetailsModal.modal.modal('hide');
    });


    // --- INITIALIZATION ---
    renderCart();
    fetchProducts();
    loadCarousel();
});