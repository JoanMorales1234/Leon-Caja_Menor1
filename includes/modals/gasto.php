<!-- MODAL GASTO -->
<div class="modal fade" id="modalGasto" tabindex="-1" aria-labelledby="modalGastoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGastoLabel">Agregar gasto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="formFields">
                        <input type="hidden" name="action" id="gastoAction" value="agregar_gasto">
                        <input type="hidden" name="id" id="gastoId" value="0">
                        <input type="hidden" name="caja_id" id="gastoCajaId" value="0">
                        <input type="hidden" name="tipo_caja" id="gastoTipoCaja" value="">
                        <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Empleado</label>
                                <select class="form-select" name="empleado_id" id="gastoEmpleado">
                                    <option value="">Sin empleado</option>
                                    <?php foreach ($empleados as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Proveedor</label>
                                <select class="form-select" name="proveedor_id" id="gastoProveedor">
                                    <option value="">Sin proveedor</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha del gasto</label>
                                <input class="form-control" type="date" name="fecha_gasto" id="gastoFecha" readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" id="gastoDescripcion" rows="2" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Valor ($)</label>
                                <input class="form-control" type="number" name="valor" id="gastoValor" step="0.01" min="0" required>
                                <div id="gastoValorMsg" class="form-text"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo soporte</label>
                                <select class="form-select" name="tipo_soporte" id="gastoTipoSoporte">
                                    <option value="factura">Factura</option>
                                    <option value="recibo">Recibo</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Foto del recibo</label>
                                <input class="form-control" type="file" name="soporte_foto" id="gastoSoporteFoto" accept="image/*" capture="environment">
                                <input type="hidden" name="soporte" id="gastoSoporte">
                                <small class="form-text text-muted">Toma una foto o selecciona una imagen</small>
                                <div id="gastoFotoPreview" class="mt-1"></div>
                            </div>
                        </div>
                    </div>
                    <div id="cropContainer" style="display:none;">
                        <div class="row">
                            <div class="col-md-8">
                                <div style="max-height:500px;overflow:hidden;">
                                    <img id="imagenRecortar" src="" alt="Foto para recortar" style="max-width:100%;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-2">Vista previa</h6>
                                <div style="width:100%;height:120px;overflow:hidden;border:1px solid #ddd;border-radius:4px;display:flex;align-items:center;justify-content:center;background:#f8f9fa;">
                                    <canvas id="previewCanvas" style="max-width:100%;max-height:100%;"></canvas>
                                </div>
                                <hr>
                                <div class="d-grid gap-2">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="rotarIzquierda" title="Rotar izquierda 90°"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="rotarDerecha" title="Rotar derecha 90°"><i class="bi bi-arrow-clockwise"></i></button>
                                    </div>
                                    <button type="button" class="btn btn-success" id="btnConfirmarCrop">
                                        <i class="bi bi-check-lg"></i> Aplicar recorte
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="btnCancelarCrop">
                                        <i class="bi bi-x-lg"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar gasto</button>
                </div>
            </form>
        </div>
    </div>
</div>