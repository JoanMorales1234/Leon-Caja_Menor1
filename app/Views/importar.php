<div class="container py-4">
    <h1 class="h3 mb-3"><i class="bi bi-file-earmark-excel"></i> Importar desde Excel</h1>
    <?php if ($message): ?>
        <?= flash($message, $type) ?>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted">Selecciona un archivo .xls o .xlsx con formato de caja menor o caja mayor, usando una hoja por cada día.</p>
            <div class="mb-3">
                <a href="<?= url('importar/plantilla') ?>" class="btn btn-outline-success">
                    <i class="bi bi-download"></i> Descargar plantilla CAJA_MENOR.xlsx
                </a>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="archivo" class="form-label">Archivo Excel</label>
                    <input class="form-control" type="file" id="archivo" name="archivo" accept=".xls,.xlsx" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Importar</button>
                <a href="<?= url('index') ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white"><h5 class="mb-0">Requisitos del archivo</h5></div>
        <div class="card-body">
            <ul class="mb-0">
                <li>Archivo en formato <strong>.xls</strong> o <strong>.xlsx</strong>.</li>
                <li>Una hoja por cada día a importar.</li>
                <li>El nombre de la hoja debe incluir mes, día y año, por ejemplo: <strong>MAYO 02 2026</strong>.</li>
                <li>En la columna D debe aparecer <strong>CAJA MENOR</strong> o <strong>CAJA MAYOR</strong>.</li>
                <li>Debe existir una fila de encabezados antes del bloque de datos.</li>
                <li>Desde la fila 12 en adelante deben existir datos de fecha, cédula, nombre, cargo, descripción, NIT, proveedor y valor.</li>
                <li>Si un empleado, cargo o proveedor no existe, el sistema intentará crearlo automáticamente.</li>
                <li>No deben existir celdas combinadas, subtotales manuales ni filas vacías dentro del bloque de datos.</li>
            </ul>
        </div>
    </div>
</div>
