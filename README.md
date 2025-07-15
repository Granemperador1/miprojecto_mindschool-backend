# Backend - MindSchool

## Requisitos Previos

- PHP 8.2 o superior
- Composer
- Node.js 18+ (para Vite)
- PostgreSQL o MySQL
- Redis

## Instalación y Despliegue con Docker + PostgreSQL

1. **Copia el archivo de ejemplo de variables de entorno y ajústalo si es necesario:**
```bash
cp .env.example .env
```

2. **Levanta todo el entorno (backend, frontend, PostgreSQL, MongoDB, Redis, Nginx) con Docker Compose:**
```bash
docker-compose up -d
```

3. **Accede a la aplicación:**
- Frontend: http://localhost:5173
- Backend/API: http://localhost:8000

4. **(Opcional) Ejecuta migraciones y seeders:**
```bash
docker-compose exec backend php artisan migrate --seed
```

---

## Variables de entorno principales para PostgreSQL

```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=mindschool
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

---

## Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/          # Controladores de API
│   │   └── Auth/         # Controladores de autenticación
│   ├── Middleware/       # Middleware personalizado
│   └── Validators/       # Validadores de entrada
├── Models/               # Modelos Eloquent
├── Repositories/         # Patrón Repository
├── Services/            # Lógica de negocio
└── Traits/              # Traits reutilizables
```

## API Endpoints

La documentación completa de la API está disponible en:

- `docs/API_ROUTES_DOCUMENTATION.md`
- `docs/api_routes.txt`

## Roles y Permisos

- **admin**: Acceso completo al sistema
- **profesor**: Gestión de cursos y estudiantes
- **estudiante**: Acceso a cursos inscritos

## Logging

El sistema utiliza múltiples canales de logging:
- `api`: Logs de endpoints de API
- `critical`: Errores críticos
- `audit`: Acciones importantes de usuarios

## Caché

Se utiliza Redis para caché de:
- Listados de cursos (15 minutos)
- Detalles de cursos (30 minutos)
- Estadísticas (15 minutos)

## Scripts útiles

- `start_servers.bat` (Windows): Inicia backend y frontend automáticamente.
- `start_servers.sh` (Linux/Mac): Inicia backend y frontend automáticamente.
- `start.sh`: Levanta todo el entorno usando Docker Compose.
- `optimize.sh`: Limpia y optimiza el proyecto.
