# Prueba técnica CLiCKO · API de usuarios

API REST en Laravel con:

- CRUD de usuarios.
- Endpoint con los 3 dominios de email más usados.
- Seeder con 20 usuarios.
- Autenticación por sesión de Laravel.

Está pensada para consumirse desde Postman y no tiene interfaz gráfica.

## Índice

- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Particularidades](#particularidades)
- [Autenticación](#autenticación)
- [Endpoints](#endpoints)
- [Postman](#postman)
- [Tests](#tests)
- [Estructura del código](#estructura-del-código)

## Requisitos

- PHP **8.3 o superior** con las extensiones `pdo_sqlite` y `sqlite3` (vienen activadas por defecto en la mayoría de instalaciones).
- Composer 2.
- No hace falta Node ni npm: el proyecto es solo API.

## Instalación

```bash
git clone https://github.com/InakiGalanDev/prueba-tecnica-clicko.git
cd prueba-tecnica-clicko

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite  
php artisan migrate --seed

php artisan serve
```

La API queda disponible en `http://localhost:8000/api`.

Para volver a dejar la base de datos como al principio (borra todos los datos):

```bash
php artisan migrate:fresh --seed
```

## Particularidades

- **Base de datos SQLite:** el `.env.example` viene configurado con `DB_CONNECTION=sqlite`, así que no hace falta instalar ningún servidor de base de datos. El archivo `database/database.sqlite` está en `.gitignore`, por eso hay que crearlo con `touch` antes de migrar.
- **Usar MySQL en su lugar:** descomenta las variables `DB_*` del `.env`, pon `DB_CONNECTION=mysql`, crea la base de datos y ejecuta `php artisan migrate --seed`. La consulta del top de dominios funciona igual en SQLite y en MySQL.
- **Sesiones en base de datos:** las sesiones se guardan en la tabla `sessions` (`SESSION_DRIVER=database`), que se crea con las migraciones. Si no se ha migrado, el login falla.
- **Cabecera `Accept: application/json`:** hay que enviarla en todas las peticiones. Así los errores (401, 404, 422) llegan siempre en JSON. Las rutas `api/*` ya fuerzan JSON en los errores, pero es buena práctica enviarla igualmente.
- **CSRF desactivado en `api/*`:** las rutas de la API usan el middleware `web` para tener sesión, pero la verificación CSRF está excluida en `bootstrap/app.php` para poder hacer peticiones POST, PUT y DELETE desde Postman sin token.
- **Errores con traza:** con `APP_DEBUG=true` (el valor por defecto en local), los errores devuelven la traza completa. En producción debe ser `APP_DEBUG=false`.

## Autenticación

La API usa la **autenticación de sesión de Laravel**: no hay tokens. Funciona así:

1. Haz login con `POST /api/login`.
2. Laravel devuelve una cookie de sesión. Postman la guarda y la reenvía automáticamente en las siguientes peticiones al mismo dominio.
3. Todas las rutas, salvo el login, requieren esa sesión. Sin ella responden `401 Unauthenticated`.

El seeder crea un usuario para hacer login:

| Email | Contraseña |
|---|---|
| `admin@clicko.es` | `password` |

## Endpoints

URL base: `http://localhost:8000/api`

| Método | Ruta | Descripción | Requiere sesión |
|---|---|---|---|
| `POST` | `/login` | Iniciar sesión | No |
| `POST` | `/logout` | Cerrar sesión | Sí |
| `GET` | `/users` | Listar usuarios (paginado) | Sí |
| `POST` | `/users` | Crear usuario | Sí |
| `GET` | `/users/{id}` | Obtener un usuario | Sí |
| `PUT` / `PATCH` | `/users/{id}` | Actualizar un usuario | Sí |
| `DELETE` | `/users/{id}` | Borrar un usuario | Sí |
| `GET` | `/users/email-domains/top` | Top 3 de dominios de email | Sí |

### Formato de un usuario

Todas las respuestas que devuelven usuarios usan este formato. La contraseña **nunca** se devuelve; se guarda hasheada.

```json
{
  "id": 1,
  "name": "Admin",
  "email": "admin@clicko.es",
  "created_at": "2026-10-28T10:00:00.000000Z",
  "updated_at": "2026-10-28T10:00:00.000000Z"
}
```

---

### `POST /api/login`

Inicia sesión y devuelve el usuario autenticado.

**Body (JSON)**

| Campo | Tipo | Reglas |
|---|---|---|
| `email` | string | Obligatorio, email válido |
| `password` | string | Obligatorio |

```json
{
  "email": "admin@clicko.es",
  "password": "password"
}
```

**Respuestas**

- `200 OK`: devuelve `{ "data": { ...usuario } }`.
- `401 Unauthorized`: credenciales incorrectas.
- `422 Unprocessable Entity`: faltan campos o el email no es válido.

---

### `POST /api/logout`

Cierra la sesión actual. No necesita body.

**Respuestas**

- `204 No Content`
- `401 Unauthorized`: no había sesión iniciada.

---

### `GET /api/users`

Lista los usuarios paginados, 15 por página.

**Parámetros de query (opcionales)**

| Parámetro | Descripción | Ejemplo |
|---|---|---|
| `page` | Número de página | `/api/users?page=2` |

**Respuesta `200 OK`**

```json
{
  "data": [
    { "id": 1, "name": "Admin", "email": "admin@clicko.es", "created_at": "...", "updated_at": "..." }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 2, "per_page": 15, "total": 20 }
}
```

---

### `POST /api/users`

Crea un usuario.

**Body (JSON)**

| Campo | Tipo | Reglas |
|---|---|---|
| `name` | string | Obligatorio, máximo 255 caracteres |
| `email` | string | Obligatorio, email válido, máximo 255 caracteres, **único** |
| `password` | string | Obligatorio, mínimo 8 caracteres |

```json
{
  "name": "Ana García",
  "email": "ana@clicko.es",
  "password": "secret123"
}
```

**Respuestas**

- `201 Created`: devuelve `{ "data": { ...usuario creado } }`.
- `422 Unprocessable Entity`: errores de validación, por ejemplo un email ya registrado:

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### `GET /api/users/{id}`

Devuelve un usuario por su ID.

**Parámetros de ruta**

| Parámetro | Descripción |
|---|---|
| `id` | ID del usuario |

**Respuestas**

- `200 OK`: devuelve `{ "data": { ...usuario } }`.
- `404 Not Found`: el usuario no existe.

---

### `PUT /api/users/{id}` · `PATCH /api/users/{id}`

Actualiza un usuario. **Todos los campos son opcionales:** solo se modifican los que se envían.

**Parámetros de ruta**

| Parámetro | Descripción |
|---|---|
| `id` | ID del usuario |

**Body (JSON)**

| Campo | Tipo | Reglas (si se envía) |
|---|---|---|
| `name` | string | No vacío, máximo 255 caracteres |
| `email` | string | Email válido, máximo 255 caracteres, único (puede mantener su propio email) |
| `password` | string | Mínimo 8 caracteres |

```json
{
  "name": "Ana García López"
}
```

**Respuestas**

- `200 OK`: devuelve `{ "data": { ...usuario actualizado } }`.
- `404 Not Found`: el usuario no existe.
- `422 Unprocessable Entity`: errores de validación.

---

### `DELETE /api/users/{id}`

Borra un usuario.

**Parámetros de ruta**

| Parámetro | Descripción |
|---|---|
| `id` | ID del usuario |

**Respuestas**

- `204 No Content`
- `404 Not Found`: el usuario no existe.

---

### `GET /api/users/email-domains/top`

Devuelve los **3 dominios de email más usados** por los usuarios, ordenados de más a menos usuarios. No necesita parámetros.

- El cálculo se hace en la base de datos (`GROUP BY` sobre la parte del email que va después de la `@`), sin cargar los usuarios en memoria.
- Los dominios se comparan sin distinguir mayúsculas: `Gmail.com` y `gmail.com` cuentan como el mismo.
- Si dos dominios empatan, se ordenan alfabéticamente.

**Respuesta `200 OK`** (con los datos del seeder)

```json
{
  "clicko.es": 11,
  "gmail.com": 4,
  "outlook.es": 2
}
```

## Postman

La colección y el entorno están en la carpeta [`postman/`](postman/), en el formato de archivos de Postman. Para usarlos:

1. Abre Postman con un *Local workspace* apuntando a la carpeta del proyecto, o importa la carpeta `postman/`.
2. Selecciona el entorno **Local**. Ya trae configurados:
   - `base_url`: `http://localhost:8000`
   - `login_email`: `admin@clicko.es`
   - `login_password`: `password`
3. Con el servidor arrancado (`php artisan serve`), ejecuta la colección completa: clic derecho → **Run collection**.

La colección está organizada en este orden:

| Carpeta | Contenido |
|---|---|
| `01 · Auth` | Acceso sin sesión (401), errores de login (422, 401) y login correcto |
| `02 · Users` | Listar, crear, errores de validación, ver, actualizar y borrar |
| `03 · Stats` | Top 3 de dominios |
| `99 · Logout` | Logout y comprobación de que ya no hay acceso (401) |

Cada petición lleva en el nombre el código HTTP esperado, por ejemplo `POST Crear usuario → 201`. Un script de la colección comprueba automáticamente que la respuesta tiene ese código y añade la cabecera `Accept: application/json` a todas las peticiones.

El ID y el email del usuario creado se guardan en variables de la colección (`user_id`, `user_email`), así que las peticiones de ver, actualizar, borrar y "email duplicado" funcionan sin tener que editar nada.

## Tests

```bash
php artisan test
```

Los tests usan SQLite en memoria (configurado en `phpunit.xml`), así que no tocan la base de datos local. Cubren:

| Archivo | Qué comprueba |
|---|---|
| `tests/Feature/UserControllerTest.php` | CRUD completo, validaciones, 404 y acceso sin sesión |
| `tests/Feature/AuthControllerTest.php` | Login correcto, credenciales incorrectas, validación y logout |
| `tests/Feature/TopEmailDomainTest.php` | Top 3, orden descendente, mayúsculas y acceso sin sesión |
| `tests/Feature/DatabaseSeederTest.php` | El seeder crea 20 usuarios (incluido el admin) y el top esperado |

## Estructura del código

| Archivo | Función |
|---|---|
| `routes/api.php` | Definición de las rutas |
| `app/Http/Controllers/Api/AuthController.php` | Login y logout por sesión |
| `app/Http/Controllers/Api/UserController.php` | CRUD de usuarios |
| `app/Http/Controllers/Api/TopEmailDomainController.php` | Top 3 de dominios (controlador de una sola acción) |
| `app/Http/Requests/StoreUserRequest.php` | Validación al crear |
| `app/Http/Requests/UpdateUserRequest.php` | Validación al actualizar (campos opcionales y email único que ignora al propio usuario) |
| `app/Http/Resources/UserResource.php` | Formato JSON del usuario (sin contraseña) |
| `database/seeders/UserSeeder.php` | 20 usuarios: el admin y 19 repartidos en 6 dominios |
| `bootstrap/app.php` | Errores en JSON para `api/*` y CSRF excluido en `api/*` |

**Nota sobre las rutas:** `GET /users/email-domains/top` se declara antes que `apiResource('users')`. Si fuera después, Laravel interpretaría `email-domains` como el `{id}` de `GET /users/{id}` y devolvería un 404.
