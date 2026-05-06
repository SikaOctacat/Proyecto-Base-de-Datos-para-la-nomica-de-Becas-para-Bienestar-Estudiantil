# Manual del Sistema: Arquitectura y Especificaciones Técnicas

Este documento detalla la estructura técnica, lógica de programación y diseño de base de datos del Sistema Integrado de Becas.

---

## 🛠️ 1. Stack Tecnológico
- **Lenguaje**: PHP 8.x (Backend) y JavaScript ES6+ (Frontend).
- **Base de Datos**: MySQL / MariaDB mediante la extensión **PDO**.
- **Estilo**: Vanilla CSS con variables modernas y Glassmorphism.
- **Arquitectura**: SPA (Single Page Application) controlada por un motor de inyección dinámica en JS.

---

## 📂 2. Estructura de Archivos
- [index.php](file:///c:/xampp/htdocs/proyecto-elias/index.php): Punto de entrada único. Maneja el portal de inicio y el contenedor de la aplicación.
- [db.php](file:///c:/xampp/htdocs/proyecto-elias/db.php): Singleton para la conexión a la base de datos y gestión de sesiones.
- [main.js](file:///c:/xampp/htdocs/proyecto-elias/main.js): Lógica central. Maneja el estado global (`formDataStorage`), navegación ([loadStep](file:///c:/xampp/htdocs/proyecto-elias/main.js#114-199)) e inyección de HTML.
- [submit.php](file:///c:/xampp/htdocs/proyecto-elias/submit.php): Endpoint API que procesa los envíos mediante transacciones SQL.
- `Paginas/`: Directorio modular con las vistas (PHP) y scripts (JS) de cada paso del formulario.
- `admin/`: Módulo de gestión para usuarios con rol 'admin'.

---

## 🗄️ 3. Modelo de Datos (Base de Datos)
El sistema utiliza un esquema relacional con integridad referencial:
- **`usuarios`**: Almacena credenciales (Cédula/Usuario) y el `rol`.
- **`estudiantes`**: Perfil base vinculado a un `usuario_id`.
- **`familiares`, `trabajos`, `residencias`, `pnfs`**: Tablas relacionadas mediante `estudiante_id` con `ON DELETE CASCADE`.
- **`alertas`**: Tabla para el sistema de anuncios con soporte para URLs de imagen.

---

## 🧠 4. Lógica de Navegación Inteligente
El sistema implementa **validación en dos capas**:
1. **Cliente**: [main.js](file:///c:/xampp/htdocs/proyecto-elias/main.js) bloquea el salto de pasos si no se han completado los anteriores y maneja bifurcaciones (ej: salta el paso laboral si el usuario no trabaja).
2. **Servidor**: [submit.php](file:///c:/xampp/htdocs/proyecto-elias/submit.php) realiza una validación espejo antes de insertar para evitar inyecciones o datos incoherentes.

---

## 🔒 5. Seguridad
- **Hash de Contraseñas**: SHA-256 aplicado antes de almacenar en DB.
- **Control de Sesiones**: Bloqueo de caché en el panel administrativo y validación de rol persistente.
- **Prevención SQL Injection**: Uso estricto de **Sentencias Preparadas** en todas las consultas PDO.
