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
                            <select class="form-select" name="cargo_id" id="empleadoCargoId">
                                <option value="">Sin cargo</option>
                                <?php foreach ($cargos as $cargo): ?>
                                    <option value="<?= $cargo['id'] ?>"><?= htmlspecialchars($cargo['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input class="form-control" name="telefono" id="empleadoTelefono">
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
