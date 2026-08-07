# Caja Menor / Mayor

Sistema de administración de caja menor y mayor con estructura MVC.

## Requisitos

- XAMPP u otro stack compatible con Apache, PHP y MySQL/MariaDB
- PHP con soporte para `PDO`, `mysqli` y `ZipArchive`
- Extensión `php_com_dotnet` habilitada en Windows para importar Excel
- Usuario MySQL `root` con contraseña vacía por el momento, o credenciales ajustadas en `app/Core/Database.php`

## Instalación

1. Copia el proyecto a `htdocs/Caja-menor1` o a la carpeta que uses en tu entorno local.
2. Abre `http://localhost/Caja-menor1/index.php`.
3. La primera vez, el sistema crea automáticamente la base de datos `caja_menor` y sus tablas principales.

## Estructura del proyecto

```text
app/
├── Core/          Conexión a BD, helpers, front controller y clase base
├── Controllers/   Lógica de cada módulo
├── Models/        Consultas y operaciones sobre la base de datos
└── Views/         Vistas HTML/PHP y modales reutilizables
assets/            CSS, JavaScript e imágenes públicas
documentos/        Plantillas y archivos de apoyo
includes/          Fragmentos reutilizables de la interfaz
uploads/           Archivos subidos por usuarios
vendor/            Dependencias instaladas con Composer
index.php          Punto único de entrada
schema.sql         Estructura base de la base de datos
caja_menor.sql     Volcado con datos y estructura completa
```

## Rutas principales

Las rutas se consumen desde `index.php?url=...`.

| URL | Función |
|---|---|
| `(vacío)` | Panel principal con cajas, empleados, proveedores, cargos y festivos |
| `historial` | Historial de cajas |
| `importar` | Importar desde Excel |
| `importar/plantilla` | Descargar la plantilla `CAJA_MENOR.xlsx` |
| `logo` | Gestión del logo |
| `imprimir` | Imprimir una caja |
| `soportes` | Imprimir soportes |
| `exportar/excel` | Exportar a Excel |
| `exportar/syscafe` | Exportar para SysCafe |

## Funcionalidades

- Crea automáticamente la base de datos y tablas si no existen
- Abre cajas menores o mayores con fecha automática
- Respeta domingos y festivos al calcular fechas de caja
- Registra gastos, reintegros y recalcula saldos
- Maneja empleados, proveedores, cargos y festivos
- Permite ordenar gastos por arrastre
- Importa y exporta información en Excel
- Descarga una plantilla oficial desde `documentos/CAJA_MENOR.xlsx`

## Exportación e impresión

### `exportar/excel`

Genera un archivo `.xls` con el detalle de las cajas y sus movimientos.

- Si envías `caja_id`, exporta una sola caja específica.
- Si no envías `caja_id`, exporta por `tipo` (`menor` o `mayor`).
- Soporta filtros opcionales por `estado`, `mes`, `anio`, `desde`, `hasta`, `ultimos` y `pagina`.
- El nombre del archivo cambia según el tipo de caja y el rango de fechas.
- Se basa en la vista `app/Views/exportar_excel.php`.

### `exportar/syscafe`

Genera un archivo `.xlsx` con formato compatible con SysCafe.

- Si envías `caja_id`, exporta una sola caja.
- Si no envías `caja_id`, exporta por `tipo` y respeta los filtros `estado`, `mes`, `anio`, `desde`, `hasta` y `ultimos`.
- Usa la plantilla `documentos/RCM.xls` como base.
- Inserta los gastos de cada caja en el formato esperado por SysCafe.
- El nombre del archivo se arma según el tipo de caja y el filtro aplicado.

### `imprimir`

Abre una vista para impresión de la caja.

- Si envías `caja_id`, imprime esa caja exacta.
- Si no envías `caja_id`, imprime la caja abierta más reciente del `tipo` indicado.
- Muestra gastos, reintegros, saldo y nota de la caja.
- Se basa en `app/Views/imprimir_caja.php`.

### `soportes`

Imprime los soportes asociados a una caja.

- Requiere `caja_id`.
- Reúne todos los soportes de los gastos de esa caja.
- Si un gasto tiene varios soportes, los muestra todos.
- Si un gasto solo tiene el campo `soporte` antiguo, también lo incluye.
- Permite elegir orientación horizontal o vertical con `orientacion=horizontal` o `orientacion=vertical`.
- Se basa en `app/Views/imprimir_soportes.php`.

### Desde dónde se exporta o imprime

- Desde el historial puedes exportar por tipo de caja usando los filtros visibles.
- Desde el historial también puedes abrir el detalle de una caja y desde allí imprimir o ver soportes.
- En una caja específica, las acciones trabajan sobre ese `caja_id`.
- Si solo usas `tipo=menor` o `tipo=mayor`, la acción toma la caja actual o las cajas filtradas según la ruta.

## Seguridad del historial

El historial pide una contraseña local para permitir acciones sensibles como:

- editar una caja cerrada
- agregar o editar gastos y reintegros en cajas cerradas
- eliminar cajas cerradas y sus movimientos

Por el momento la contraseña configurada en el sistema es:

```text
1234
```

Ese valor está definido en `app/Views/historial.php` y se puede cambiar allí si necesitas otra clave.

## Importación de Excel

### Requisitos del archivo

- Archivo en formato `.xls` o `.xlsx`
- Una hoja por cada día a importar
- El nombre de cada hoja debe incluir mes, día y año, por ejemplo: `MAYO 02 2026`
- En la columna D debe aparecer `CAJA MENOR` o `CAJA MAYOR`
- Debe existir una fila de encabezados antes del bloque de datos
- Desde la fila 12 en adelante deben existir datos de fecha, cédula, nombre, cargo, descripción, NIT, proveedor y valor
- No deben existir celdas combinadas, subtotales manuales ni filas vacías dentro del bloque de datos

### Plantilla

La plantilla oficial está en:

`documentos/CAJA_MENOR.xlsx`

Desde la vista de importación se puede descargar directamente.

### Mensajes de importación

El módulo de importación ahora muestra mensajes más claros cuando:

- el archivo no es válido
- el archivo está abierto o dañado
- no se detecta el tipo de caja
- el nombre de la hoja no permite leer la fecha
- ya existe una caja para la misma fecha y tipo
- no se encuentran gastos o reintegros válidos

## Historial

El historial usa contraseña local para acciones sensibles sobre cajas cerradas.

- Clave actual: `1234`
- Se usa para editar, agregar o eliminar movimientos en cajas cerradas
- También aplica para eliminar cajas cerradas y modificar datos protegidos
- La clave se define en `app/Views/historial.php`

## Configuración de conexión

Los datos de conexión están en `app/Core/Database.php`:

```php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$name = 'caja_menor';
```

## Notas

- `schema.sql` es la referencia limpia para recrear la base de datos.
- `caja_menor.sql` contiene un volcado más completo con datos de ejemplo o reales.
- `documentos/CAJA_MENOR.xlsx` es la plantilla de importación, no la base de datos.
