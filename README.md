# Domus Tienda - E-commerce de Productos Aromáticos

Domus Tienda es una solución de comercio electrónico diseñada para la venta de sahumerios, velas, difusores y otros productos aromáticos. La plataforma cuenta con un catálogo dinámico, un panel de administración completo y un flujo de compra simplificado a través de WhatsApp.

---

## Características Principales

### Para Clientes:

*   **Catálogo de Productos:** Interfaz limpia para explorar los productos.
*   **Búsqueda y Filtrado:** Búsqueda por nombre y filtro por categorías para encontrar productos fácilmente.
*   **Detalles del Producto:** Ventana modal con galería de imágenes, descripción, precio y stock.
*   **Carrito de Compras:** Un carrito persistente que guarda los productos seleccionados por el usuario.
*   **Checkout por WhatsApp:** Al confirmar la compra, se genera un mensaje de WhatsApp pre-llenado con los detalles del pedido, listo para ser enviado al vendedor.
*   **Diseño Responsivo:** Adaptado para una correcta visualización en dispositivos móviles y de escritorio.

### Para Administradores:

*   **Panel de Administración Seguro:** Acceso protegido por usuario y contraseña.
*   **Dashboard de Estadísticas:** Métricas clave sobre los productos más vistos e interactuados.
*   **Gestión de Productos (CRUD):** Sistema completo para crear, leer, actualizar y eliminar productos, incluyendo su imagen principal y galería de imágenes adicionales.
*   **Gestión de Categorías y Marcas:** Las categorías y marcas se crean y actualizan de forma dinámica al añadir o editar productos, sin necesidad de un panel separado.
*   **Gestión del Carrusel:** Permite subir y eliminar fácilmente las imágenes promocionales que aparecen en la página de inicio.
*   **Registro de Administradores:** Posibilidad de crear nuevas cuentas de administrador desde el panel.

---

## Tecnologías Utilizadas

*   **Frontend:**
    *   HTML5
    *   CSS3
    *   JavaScript (ES6+)
    *   Bootstrap 5
    *   jQuery (para algunas interacciones del modal)

*   **Backend:**
    *   PHP 8.2

*   **Base de Datos:**
    *   MySQL

*   **Servidor:**
    *   Apache (configurado comúnmente a través de XAMPP)

---

## Instalación y Puesta en Marcha

Para instalar el proyecto en un entorno de desarrollo local, sigue estos pasos:

1.  **Clonar el Repositorio:**
    ```bash
    git clone <URL_DEL_REPOSITORIO>
    cd domus-tienda
    ```

2.  **Base de Datos:**
    *   Crea una nueva base de datos en tu servidor MySQL (ej: `domus_tienda`).
    *   Importa los esquemas de las tablas necesarios. Los archivos `.sql` relevantes son:
        *   `schema.sql` (tabla de productos, usuarios, etc.)
        *   `product_images_schema.sql` (tabla para la galería de imágenes de productos)
        *   `orders_schema.sql` (aunque el sistema de pedidos ya no se usa, el esquema está disponible si se desea reactivar en el futuro).

3.  **Configuración:**
    *   Renombra el archivo `env.example` a `.env`.
    *   Abre el archivo `config.php` y ajusta las credenciales de conexión a la base de datos (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) para que coincidan con tu configuración local.

4.  **Servidor Web:**
    *   Asegúrate de que el directorio del proyecto esté ubicado dentro del `document root` de tu servidor Apache (ej: `C:/xampp/htdocs/domus-tienda`).
    *   Inicia los servicios de Apache y MySQL.

5.  **Acceso:**
    *   Abre tu navegador y ve a `http://localhost/domus-tienda/` para ver la tienda.
    *   Accede al panel de administración en `http://localhost/domus-tienda/login.php`.

---

## Estructura del Proyecto

```
/domus-tienda
|-- api/                # Scripts PHP para la lógica de negocio
|   |-- admin/          # Endpoints exclusivos para el dashboard
|-- assets/             # Archivos estáticos
|   |-- css/            # Hojas de estilo
|   |-- images/         # Imágenes de productos y carrusel
|   |-- js/             # Scripts de JavaScript
|-- img/                # Imágenes generales de la interfaz
|-- .gitignore
|-- config.php          # Configuración de la base de datos
|-- dashboard.php       # Panel de administración
|-- index.php           # Página de inicio y catálogo de productos
|-- login.php           # Página de inicio de sesión
|-- register_admin.php  # Página de registro de administradores
|-- README.md           # Este archivo
|-- *.sql               # Esquemas de la base de datos
```

---

## Autor

*   **Franco Paredes** - [LinkedIn](https://www.linkedin.com/in/francoparedes1992/)
