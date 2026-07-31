# Caja Menor / Mayor

Instala el sistema desde `index.php` y crea la base de datos con `init_db.php`.

## Pasos

1. Copia el proyecto al directorio de XAMPP: `htdocs/Caja-menor`
2. Abre el navegador en: `http://localhost/Caja-menor/init_db.php`
3. Accede a `http://localhost/Caja-menor/index.php`

## Funcionalidades

- Crea la base de datos y tablas MySQL
- Crea cajas abiertas con fecha automática
- Registra gastos que el sistema asigna a caja menor o mayor
- Registra reintegros y calcula saldo automático
- Agrega empleados, proveedores, cargos y festivos
- Lista registros en tablas con acciones de ver, editar e inactivar
- Carga datos de ejemplo con reintegro de `232.976,00`

## Páginas de administración

- `empleados.php`
- `proveedores.php`
- `cargos.php`
- `seed_data.php`

## Recomendación

- Ajusta `db.php` si tu usuario/contraseña de MySQL no es `root` sin contraseña.
