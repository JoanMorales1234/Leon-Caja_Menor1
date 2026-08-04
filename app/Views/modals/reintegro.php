<!-- MODAL REINTEGRO -->
<div class="modal fade" id="modalReintegro" tabindex="-1" aria-labelledby="modalReintegroLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReintegroLabel">Agregar reintegro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="reintegroAction" value="agregar_reintegro">
                    <input type="hidden" name="id" id="reintegroId" value="0">
                    <input type="hidden" name="caja_id" id="reintegroCajaId" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha reintegro</label>
                            <input class="form-control" type="date" name="fecha_reintegro" id="reintegroFecha">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor</label>
                            <input class="form-control" type="number" name="valor_reintegro" id="reintegroValor" step="0.01" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion_reintegro" id="reintegroDescripcion" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Soporte</label>
                            <input class="form-control" type="text" name="soporte_reintegro" id="reintegroSoporte" placeholder="Documento, referencia">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Guardar reintegro</button>
                </div>
            </form>
        </div>
    </div>
</div>
