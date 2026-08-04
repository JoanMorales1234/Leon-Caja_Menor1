# Caja Menor / Mayor

Sistema de administración de caja menor y mayor con estructura MVC.

## Requisitos

- XAMPP (Apache + PHP + MySQL) u otro stack compatible
- MySQL con usuario `root` y sin contraseña (configurable en `app/Core/Database.php`)

## Instalación

1. Copia el proyecto a `htdocs/Caja-menor1` (o el nombre de carpeta que uses).
2. Abre en el navegador: `http://localhost/Caja-menor1/index.php`
3. La primera vez el sistema crea automáticamente la base de datos `caja_menor` y sus tablas.
4. Para cargar datos de ejemplo usa el enlace **Datos ejemplo** del menú (o `?url=datos/ejemplo`).

## Estructura del proyecto (MVC)

```
app/
├── Core/          # Front controller, enrutador, conexión BD y helpers
│   ├── App.php
│   ├── Controller.php
│   ├── Database.php
│   └── helpers.php
├── Controllers/   # Lógica por sección
├── Models/        # Consultas a la base de datos
└── Views/         # Plantillas (incluye layout/ y modals/)
assets/            # CSS y JS
index.php          # Único punto de entrada (front controller)
```

## Rutas (via `index.php?url=...`)

| URL            | Sección                          |
|----------------|----------------------------------|
| (vacío)        | Panel principal (cajas/empleados/proveedores/cargos/festivos) |
| `historial`    | Historial de cajas               |
| `importar`     | Importar desde Excel             |
| `logo`         | Gestión del logo                 |
| `imprimir`     | Imprimir caja                    |
| `soportes`     | Imprimir soportes                |
| `exportar/excel` | Exportar a Excel (.xls)        |
| `exportar/syscafe` | Exportar para SysCafe         |
| `datos/ejemplo`| Cargar datos de ejemplo          |
| `datos/reset`  | Reiniciar la base de datos       |
| `instalar`     | Página de instalación            |

## Funcionalidades

- Crea la base de datos y tablas MySQL automáticamente
- Crea cajas abiertas con fecha automática (teniendo en cuenta domingos y festivos)
- Registra gastos asignados a caja menor (hasta $50.000) o caja mayor
- Registra reintegros y recalcula el saldo automáticamente
- Administra empleados, proveedores, cargos y festivos
- Ordena los gastos por arrastre y persiste el orden
- Importa y exporta datos en Excel

## Configuración de conexión

Los datos de conexión están en `app/Core/Database.php`:

```php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$name = 'caja_menor';
```
