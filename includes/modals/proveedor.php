<!-- MODAL PROVEEDOR -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProveedorLabel">Nuevo proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="proveedorAction" value="nuevo_proveedor">
                    <input type="hidden" name="id" id="proveedorId" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NIT</label>
                            <input class="form-control" name="nit" id="proveedorNit" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" name="nombre" id="proveedorNombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input class="form-control" name="telefono" id="proveedorTelefono">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dirección</label>
                            <input class="form-control" name="direccion" id="proveedorDireccion">
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
