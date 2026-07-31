<!-- MODAL EMPLEADO -->
<div class="modal fade" id="modalEmpleado" tabindex="-1" aria-labelledby="modalEmpleadoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEmpleadoLabel">Nuevo empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="empleadoAction" value="nuevo_empleado">
                    <input type="hidden" name="id" id="empleadoId" value="0">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Cédula</label>
                            <input class="form-control" name="cedula" id="empleadoCedula" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombres</label>
                            <input class="form-control" name="nombres" id="empleadoNombres" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellidos</label>
                            <input class="form-control" name="apellidos" id="empleadoApellidos" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cargo</label>
                            <div class="search-dropdown" data-select="empleadoCargoId" style="position:relative;">
                                <input type="text" class="form-control form-control-sm search-dropdown-input" placeholder="Buscar y seleccionar..." autocomplete="off">
                                <input type="hidden" name="cargo_id" id="empleadoCargoId" value="">
                                <div class="search-dropdown-list" style="display:none;max-height:150px;overflow-y:auto;border:1px solid #ccc;position:absolute;background:#fff;z-index:1050;width:100%;border-radius:4px;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                                    <div class="search-dropdown-item" data-value="">Sin cargo</div>
                                    <?php foreach ($cargos as $cargo): ?>
                                        <div class="search-dropdown-item" data-value="<?= $cargo['id'] ?>"><?= htmlspecialchars($cargo['nombre']) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input class="form-control" name="telefono" id="empleadoTelefono">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <div class="search-dropdown" data-select="empleadoEstado" style="position:relative;">
                                <input type="text" class="form-control form-control-sm search-dropdown-input" placeholder="Buscar y seleccionar..." autocomplete="off">
                                <input type="hidden" name="estado" id="empleadoEstado" value="activo">
                                <div class="search-dropdown-list" style="display:none;max-height:120px;overflow-y:auto;border:1px solid #ccc;position:absolute;background:#fff;z-index:1050;width:100%;border-radius:4px;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                                    <div class="search-dropdown-item" data-value="activo">Activo</div>
                                    <div class="search-dropdown-item" data-value="inactivo">Inactivo</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
