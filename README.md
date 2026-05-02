# CRUD de Animales y Especies

Proyecto realizado en clase de **Modelos de Computación** donde desarrollamos una aplicación web CRUD con **PHP, MySQL, JavaScript y Bootstrap**, ejecutada en **Docker** o en entorno local con **WAMP/LAMPP**.

La aplicación permite gestionar **animales** y **especies** mediante una interfaz dinámica con modales y búsqueda en tiempo real.

---

## Modulos

- **Animales** (`/nueva_tabla/index.html`) — CRUD completo con nombre, especie (FK), fecha de nacimiento y edad calculada
- **Especies** (`/nueva_tabla/especies.html`) — CRUD completo con nombre y descripción

---

## Tecnologias usadas

- **PHP 8.2** — API REST-like con PDO y consultas preparadas
- **MySQL 8.0 / MariaDB** — Base de datos relacional con claves foráneas
- **JavaScript (Vanilla ES6)** — IIFE, Fetch API, manipulación del DOM
- **Bootstrap 5** — Modales, formularios, tablas responsivas
- **AdminLTE 3** — Layout con sidebar, navbar y content-wrapper
- **Docker** — Apache + MySQL + phpMyAdmin (docker-compose)
- **WAMP / LAMPP** — Entorno local alternativo
- **Git & GitHub** — Control de versiones

---

## Temas visuales

- **Gestacion** (`estilos/gestacion.css`) — Tema infantil con colores pastel, bordes redondeados y tipografía amigable (Baloo 2 + Nunito)
- **Awwwards Animales** (`estilos/awwwards_animales.css`) — Estilo neo-brutalista con sombras agresivas, tipografía gruesa y huellas SVG de fondo

Ambos temas se cargan simultáneamente y se complementan.

---

## Base de datos

La base de datos `MCEJ1_BD` cuenta con dos tablas relacionadas:

```
especies (id, nombre, descripcion)
animales (id, nombre, especie_id → especies.id, fechanacimiento)
```

- `animales.especie_id` es clave foránea que referencia `especies.id` con restricción `ON UPDATE CASCADE`
- Las especies tienen un índice `UNIQUE` en el campo `nombre`
- El archivo `init.sql` incluye la estructura y datos de ejemplo

---

## API REST

El backend expone una API JSON en `nueva_tabla/api/algo.php` con las siguientes acciones:

| Accion | Metodo | Descripcion |
|--------|--------|-------------|
| `listar` | GET | Lista todos los animales con JOIN a especies |
| `obtener` | GET | Obtiene un animal por ID |
| `insertar` | POST | Crea un nuevo animal |
| `editar` | POST | Actualiza un animal existente |
| `eliminar` | POST | Elimina un animal |
| `listar_especies` | GET | Lista todas las especies |
| `obtener_especie` | GET | Obtiene una especie por ID |
| `insertar_especie` | POST | Crea una nueva especie |
| `editar_especie` | POST | Actualiza una especie existente |
| `eliminar_especie` | POST | Elimina una especie (bloqueado si tiene animales asociados) |

Todas las respuestas siguen el formato `{ ok, message, data }` con codigos HTTP apropiados.

---

## Estructura del proyecto

```
/
├── docker-compose.yml       # Servicios: web (Apache+PHP), db (MySQL), phpmyadmin
├── Dockerfile               # Imagen PHP 8.2 con mysqli, PDO, mod_rewrite
├── init.sql                 # DDL + datos iniciales
├── index.php                # Redirecciona a /nueva_tabla/
├── nueva_tabla/
│   ├── index.html           # CRUD Animales (AdminLTE + modales)
│   ├── especies.html        # CRUD Especies (AdminLTE + modales)
│   ├── config/
│   │   └── db.php           # Conexion PDO a MySQL
│   ├── api/
│   │   └── algo.php         # API REST con todas las acciones CRUD
│   ├── assets/
│   │   ├── script.js        # Logica frontend de Animales
│   │   ├── especies.js      # Logica frontend de Especies
│   │   ├── bootstrap/       # Bootstrap 5 (css + js)
│   │   └── adminlte/        # AdminLTE 3 (css, js, plugins: jQuery, FontAwesome)
│   └── estilos/
│       ├── gestacion.css    # Tema infantil pastel
│       └── awwwards_animales.css  # Tema neo-brutalista Awwwards
```

---

## Como ejecutarlo

### Con Docker (recomendado)

```bash
docker-compose up -d
```

- Aplicacion: http://localhost:8080/nueva_tabla/
- phpMyAdmin: http://localhost:8081/ (servidor: `db`, usuario: `kevin`, pass: `12345`)

### Con WAMP / LAMPP

1. Clonar el repositorio en `C:/wamp64/www/` o `/opt/lampp/htdocs/`
2. Importar `init.sql` en phpMyAdmin
3. Ajustar credenciales en `nueva_tabla/config/db.php` (cambiar `DB_HOST` a `127.0.0.1`)
4. Iniciar Apache y MySQL
5. Abrir http://localhost/crud-animales_WAMP/nueva_tabla/

---

## Lo que practicamos

- CRUD completo con dos tablas relacionadas (FK)
- API REST con PHP y PDO (consultas preparadas, seguridad contra inyeccion SQL)
- Frontend dinamico con JavaScript vanilla (Fetch API, IIFE, modales Bootstrap)
- Calculo de edad en cliente a partir de fecha de nacimiento
- Busqueda/filtrado en cliente sin recargar la pagina
- Integracion de plantilla AdminLTE con Bootstrap 5
- Contenedores Docker con multiples servicios
- Control de versiones con Git y GitHub

---

## Nota

Proyecto desarrollado como practica academica para entender el flujo completo de una aplicacion web con base de datos relacional, API REST y frontend dinamico.

Hecho en clase de Modelos de Computacion.
