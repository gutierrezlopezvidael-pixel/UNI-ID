# Uni-ID - Instalación

## Base de datos

Ejecuta **una sola vez** el archivo `install.sql`:

```bash
mysql -u root < install.sql
```

O desde phpMyAdmin: importa el archivo `install.sql`.

## Accesos por defecto

| Usuario | Contraseña |
|---------|------------|
| **Admin** | admin / admin123 |
| **Docente** | docente / docente123 |

## Requisitos

- PHP 7.4+ con extensiones: pdo_mysql, json, mbstring
- MySQL 5.7+ o MariaDB 10.3+
- Apache (XAMPP incluye todo)
