<div class="container py-4">
    <h1 class="h3 mb-3"><i class="bi bi-file-earmark-excel"></i> Importar desde Excel</h1>
    <?php if ($message): ?>
        <?= flash($message, $type) ?>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted">Selecciona un archivo .xls o .xlsx con el formato de CAJA MENOR/MAYOR (una hoja por día).</p>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="archivo" class="form-label">Archivo Excel</label>
                    <input class="form-control" type="file" id="archivo" name="archivo" accept=".xls,.xlsx" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Filtrar por mes</label>
                    <select class="form-select" name="mes_filtro">
                        <option value="0" selected>Todos los meses</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                    <small class="form-text text-muted">Si seleccionas un mes, solo se importarán las hojas de ese mes. Si ya existe una caja para una fecha, se salta automáticamente.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Importar</button>
                <a href="<?= url('index') ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white"><h5 class="mb-0">Formato esperado</h5></div>
        <div class="card-body">
            <ul class="mb-0">
                <li>Una hoja por día, nombre de hoja = fecha (ej: "MAYO 02 2026")</li>
                <li>Fila 4: "CAJA MENOR" o "CAJA MAYOR" (columna D)</li>
                <li>Fila 7: Encabezados</li>
                <li>Filas 12+: Datos con fecha, cédula, nombre, funcionario, descripción, NIT, proveedor, valor</li>
                <li>Empleados y proveedores nuevos se crean automáticamente</li>
            </ul>
        </div>
    </div>
</div>
