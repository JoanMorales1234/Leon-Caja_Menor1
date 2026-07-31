document.addEventListener('DOMContentLoaded', function () {

    // === MODAL GASTO (crear/editar) ===
    const modalGasto = document.getElementById('modalGasto');
    const gastoValor = document.getElementById('gastoValor');
    const gastoValorMsg = document.getElementById('gastoValorMsg');
    const gastoFecha = document.getElementById('gastoFecha');
    const gastoSoporteFoto = document.getElementById('gastoSoporteFoto');
    const gastoFotoPreview = document.getElementById('gastoFotoPreview');
    let gastoTipoActual = 'menor';

    function validarValorGasto() {
        if (!gastoValor || !gastoValorMsg) return;
        const v = parseFloat(gastoValor.value);
        if (isNaN(v) || v <= 0) {
            gastoValorMsg.textContent = '';
            gastoValorMsg.className = 'form-text';
            return;
        }
        if (gastoTipoActual === 'menor' && v > 50000) {
            gastoValorMsg.textContent = '⚠ El valor excede $50.000. Usa Caja Mayor.';
            gastoValorMsg.className = 'form-text text-danger';
        } else if (gastoTipoActual === 'mayor' && v <= 50000) {
            gastoValorMsg.textContent = '⚠ El valor debe ser mayor a $50.000. Usa Caja Menor.';
            gastoValorMsg.className = 'form-text text-danger';
        } else {
            gastoValorMsg.textContent = gastoTipoActual === 'menor' ? '✓ Válido para Caja Menor' : '✓ Válido para Caja Mayor';
            gastoValorMsg.className = 'form-text text-success';
        }
    }

    if (modalGasto) {
        let cropper = null;
        let currentCropInput = null;
        let currentCropPreview = null;
        const formFields = document.getElementById('formFields');
        const cropContainer = document.getElementById('cropContainer');
        const imagenRecortar = document.getElementById('imagenRecortar');
        const previewCanvas = document.getElementById('previewCanvas');
        const rotarIzquierda = document.getElementById('rotarIzquierda');
        const rotarDerecha = document.getElementById('rotarDerecha');
        const btnConfirmarCrop = document.getElementById('btnConfirmarCrop');
        const btnCancelarCrop = document.getElementById('btnCancelarCrop');

        function actualizarPreview(instance) {
            instance = instance || cropper;
            if (!instance || !previewCanvas) return;
            try {
                var canvas = instance.getCroppedCanvas({ width: 400, fillColor: '#fff' });
                var ctx = previewCanvas.getContext('2d');
                previewCanvas.width = canvas.width;
                previewCanvas.height = canvas.height;
                ctx.clearRect(0, 0, previewCanvas.width, previewCanvas.height);
                ctx.drawImage(canvas, 0, 0);
            } catch (e) {}
        }

        function mostrarCropContainer() {
            if (formFields) formFields.style.display = 'none';
            if (cropContainer) cropContainer.style.display = 'block';
        }

        function ocultarCropContainer() {
            if (formFields) formFields.style.display = '';
            if (cropContainer) cropContainer.style.display = 'none';
        }

        function initCropperOnInput(fileInput, previewContainer) {
            if (!fileInput || !previewContainer) return;
            fileInput.addEventListener('change', function (e) {
                var file = e.target.files[0];
                if (!file) { previewContainer.innerHTML = ''; return; }
                currentCropInput = fileInput;
                currentCropPreview = previewContainer;
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function (ev) {
                        mostrarCropContainer();
                        if (cropper) { cropper.destroy(); cropper = null; }
                        imagenRecortar.onload = function () {
                            if (cropper) { cropper.destroy(); cropper = null; }
                            cropper = new Cropper(imagenRecortar, {
                                aspectRatio: NaN, viewMode: 1, autoCropArea: 1,
                                responsive: true, checkCrossOrigin: false,
                                ready: function () { actualizarPreview(this); },
                                cropend: function () { actualizarPreview(this); }
                            });
                        };
                        imagenRecortar.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    var blobUrl = URL.createObjectURL(file);
                    previewContainer.innerHTML = '<img src="' + blobUrl + '" class="img-thumbnail" style="max-height:60px" alt="Archivo">';
                }
            });
        }

        function mostrarPreviewEn(input, preview, blobUrl) {
            preview.innerHTML = '<div style="position:relative;display:inline-block;">'
                + '<img src="' + blobUrl + '" class="img-thumbnail" style="max-height:60px" alt="Foto">'
                + '<div class="mt-1">'
                + '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-quitar-foto" title="Quitar"><i class="bi bi-trash"></i></button>'
                + '</div></div>';
            preview.querySelector('.btn-quitar-foto').addEventListener('click', function () {
                preview.innerHTML = '';
                input.value = '';
            });
        }

        function iniciarCropperEnInput(fileInput, previewContainer) {
            if (!fileInput || !previewContainer) return;
            fileInput.addEventListener('change', function (e) {
                var file = e.target.files[0];
                if (!file) { previewContainer.innerHTML = ''; return; }
                currentCropInput = fileInput;
                currentCropPreview = previewContainer;
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function (ev) {
                        mostrarCropContainer();
                        if (cropper) { cropper.destroy(); cropper = null; }
                        imagenRecortar.onload = function () {
                            if (cropper) { cropper.destroy(); cropper = null; }
                            cropper = new Cropper(imagenRecortar, {
                                aspectRatio: NaN, viewMode: 1, autoCropArea: 1,
                                responsive: true, checkCrossOrigin: false,
                                ready: function () { actualizarPreview(this); },
                                cropend: function () { actualizarPreview(this); }
                            });
                        };
                        imagenRecortar.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    var blobUrl = URL.createObjectURL(file);
                    mostrarPreviewEn(fileInput, previewContainer, blobUrl);
                }
            });
        }

        modalGasto.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            gastoTipoActual = btn.getAttribute('data-tipo');
            const cajaId = btn.getAttribute('data-caja-id');
            const fechaCaja = btn.getAttribute('data-fecha-caja');
            const isEdit = btn.getAttribute('data-edit') === 'true';

            document.getElementById('gastoCajaId').value = cajaId;
            document.getElementById('gastoTipoCaja').value = gastoTipoActual;
            document.getElementById('gastoAction').value = isEdit ? 'editar_gasto' : 'agregar_gasto';

            const titulo = isEdit ? 'Editar gasto' : 'Agregar gasto';
            document.getElementById('modalGastoLabel').textContent = titulo + ' - Caja ' + (gastoTipoActual === 'menor' ? 'Menor' : 'Mayor');

            if (gastoFecha) {
                gastoFecha.value = fechaCaja || new Date().toISOString().split('T')[0];
            }

            var sopExistentes = document.getElementById('soportesExistentes');
            var sopLista = document.getElementById('soportesLista');
            sopLista.innerHTML = '';
            if (isEdit) {
                var soportesData = btn.getAttribute('data-soportes');
                var soportes = [];
                try { soportes = JSON.parse(soportesData); } catch(e) {}
                if (soportes.length > 0) {
                    sopExistentes.style.display = '';
                    soportes.forEach(function(s) {
                        var html = '<div class="form-check mb-1">';
                        html += '<input class="form-check-input" type="checkbox" name="eliminar_soporte[]" value="' + s.id + '" id="delSop' + s.id + '">';
                        html += '<label class="form-check-label" for="delSop' + s.id + '">';
                        if (s.archivo) {
                            html += '<img src="' + s.archivo + '" class="img-thumbnail me-1" style="max-height:28px" alt=""> ';
                        }
                        html += (s.tipo || 'otro') + (s.descripcion ? ' - ' + s.descripcion : '');
                        html += ' <a href="' + (s.archivo || '#') + '" target="_blank" class="text-muted"><i class="bi bi-box-arrow-up-right"></i></a>';
                        html += '</label></div>';
                        sopLista.innerHTML += html;
                    });
                } else {
                    sopExistentes.style.display = 'none';
                }
                document.getElementById('gastoId').value = btn.getAttribute('data-id');
                setSearchDropdown('gastoEmpleado', btn.getAttribute('data-empleado-id') || '', btn.getAttribute('data-empleado-nombre') || '');
                setSearchDropdown('gastoProveedor', btn.getAttribute('data-proveedor-id') || '', btn.getAttribute('data-proveedor-nombre') || '');
                document.getElementById('gastoDescripcion').value = btn.getAttribute('data-descripcion');
                document.getElementById('gastoValor').value = btn.getAttribute('data-valor');
            } else {
                sopExistentes.style.display = 'none';
                document.getElementById('gastoId').value = 0;
                document.getElementById('gastoForm') && document.getElementById('gastoForm').reset();
                document.getElementById('gastoDescripcion').value = '';
                document.getElementById('gastoValor').value = '';
                setSearchDropdown('gastoEmpleado', '', '');
                setSearchDropdown('gastoProveedor', '', '');
                if (gastoSoporteFoto) gastoSoporteFoto.value = '';
                if (gastoFotoPreview) gastoFotoPreview.innerHTML = '';
            }
            limpiarSoportesAdicionales();
            validarValorGasto();
        });

        modalGasto.addEventListener('hidden.bs.modal', function () {
            document.getElementById('gastoId').value = 0;
            if (gastoFotoPreview) gastoFotoPreview.innerHTML = '';
            limpiarSoportesAdicionales();
            ocultarCropContainer();
            if (cropper) { cropper.destroy(); cropper = null; }
            currentCropInput = null;
            currentCropPreview = null;
        });

        if (gastoValor) gastoValor.addEventListener('input', validarValorGasto);

        // === Dynamic additional soportes ===
        function limpiarSoportesAdicionales() {
            var container = document.getElementById('soportesAdicionales');
            if (container) container.innerHTML = '';
        }

        function agregarFilaSoporte() {
            var container = document.getElementById('soportesAdicionales');
            if (!container) return;
            var idx = container.children.length;
            var html = '<div class="row g-2 mb-2 soporte-adicional-row">';
            html += '<div class="col-md-5">';
            html += '<input type="file" class="form-control form-control-sm soporte-extra-file" name="soporte_extra[]" accept="image/*">';
            html += '<div class="soporte-extra-preview mt-1"></div>';
            html += '</div>';
            html += '<div class="col-md-5">';
            html += '<input type="text" class="form-control form-control-sm" name="soporte_extra_desc[]" placeholder="Descripci\u00f3n (opcional)">';
            html += '</div>';
            html += '<div class="col-md-2">';
            html += '<button type="button" class="btn btn-sm btn-outline-danger btn-quitar-soporte"><i class="bi bi-x"></i></button>';
            html += '</div></div>';
            container.insertAdjacentHTML('beforeend', html);
            var row = container.lastElementChild;
            row.querySelector('.btn-quitar-soporte').addEventListener('click', function() {
                row.remove();
            });
            var fileInput = row.querySelector('.soporte-extra-file');
            var previewDiv = row.querySelector('.soporte-extra-preview');
            iniciarCropperEnInput(fileInput, previewDiv);
        }

        document.getElementById('btnAgregarSoporte') && document.getElementById('btnAgregarSoporte').addEventListener('click', function() {
            agregarFilaSoporte();
        });

        // Init cropper on primary input
        iniciarCropperEnInput(gastoSoporteFoto, gastoFotoPreview);

        if (rotarIzquierda) {
            rotarIzquierda.addEventListener('click', function () {
                if (cropper) { cropper.rotate(-90); actualizarPreview(); }
            });
        }
        if (rotarDerecha) {
            rotarDerecha.addEventListener('click', function () {
                if (cropper) { cropper.rotate(90); actualizarPreview(); }
            });
        }

        if (btnConfirmarCrop) {
            btnConfirmarCrop.addEventListener('click', function () {
                if (cropper && currentCropInput && currentCropPreview) {
                    var canvas = cropper.getCroppedCanvas({ fillColor: '#fff' });
                    canvas.toBlob(function (blob) {
                        var croppedFile = new File([blob], 'recorto_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                        var dataTransfer = new DataTransfer();
                        dataTransfer.items.add(croppedFile);
                        currentCropInput.files = dataTransfer.files;
                        var previewUrl = URL.createObjectURL(croppedFile);
                        mostrarPreviewEn(currentCropInput, currentCropPreview, previewUrl);
                        cropper.destroy();
                        cropper = null;
                        ocultarCropContainer();
                    }, 'image/jpeg', 0.9);
                }
            });
        }

        if (btnCancelarCrop) {
            btnCancelarCrop.addEventListener('click', function () {
                if (cropper) { cropper.destroy(); cropper = null; }
                if (currentCropInput) currentCropInput.value = '';
                ocultarCropContainer();
            });
        }
    }

    // === MODAL REINTEGRO (crear/editar) ===
    const modalReintegro = document.getElementById('modalReintegro');
    if (modalReintegro) {
        modalReintegro.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const tipo = btn.getAttribute('data-tipo');
            const cajaId = btn.getAttribute('data-caja-id');
            const fechaCaja = btn.getAttribute('data-fecha-caja');
            const isEdit = btn.getAttribute('data-edit') === 'true';

            document.getElementById('reintegroCajaId').value = cajaId;
            document.getElementById('reintegroAction').value = isEdit ? 'editar_reintegro' : 'agregar_reintegro';

            const titulo = isEdit ? 'Editar reintegro' : 'Agregar reintegro';
            document.getElementById('modalReintegroLabel').textContent = titulo + ' - Caja ' + (tipo === 'menor' ? 'Menor' : 'Mayor');

            // Always set fecha from caja's fecha_caja (locked)
            const reintegroFecha = document.getElementById('reintegroFecha');
            if (reintegroFecha) {
                reintegroFecha.value = fechaCaja || new Date().toISOString().split('T')[0];
            }

            if (isEdit) {
                document.getElementById('reintegroId').value = btn.getAttribute('data-id');
                document.getElementById('reintegroValor').value = btn.getAttribute('data-valor');
                document.getElementById('reintegroDescripcion').value = btn.getAttribute('data-descripcion') || '';
                document.getElementById('reintegroSoporte').value = btn.getAttribute('data-soporte') || '';
            } else {
                document.getElementById('reintegroId').value = 0;
                document.getElementById('reintegroValor').value = '';
                document.getElementById('reintegroDescripcion').value = '';
                document.getElementById('reintegroSoporte').value = '';
            }
        });

        modalReintegro.addEventListener('hidden.bs.modal', function () {
            document.getElementById('reintegroId').value = 0;
        });
    }

    // === MODAL EMPLEADO ===
    const modalEmpleado = document.getElementById('modalEmpleado');
    if (modalEmpleado) {
        modalEmpleado.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const isEdit = btn.getAttribute('data-edit') === 'true';

            document.getElementById('empleadoAction').value = isEdit ? 'editar_empleado' : 'nuevo_empleado';
            document.getElementById('modalEmpleadoLabel').textContent = isEdit ? 'Editar empleado' : 'Nuevo empleado';

            if (isEdit) {
                document.getElementById('empleadoId').value = btn.getAttribute('data-id');
                document.getElementById('empleadoCedula').value = btn.getAttribute('data-cedula');
                document.getElementById('empleadoNombres').value = btn.getAttribute('data-nombres');
                document.getElementById('empleadoApellidos').value = btn.getAttribute('data-apellidos');
                setSearchDropdown('empleadoCargoId', btn.getAttribute('data-cargo-id') || '', btn.getAttribute('data-cargo-nombre') || '');
                setSearchDropdown('empleadoEstado', btn.getAttribute('data-estado') || 'activo', btn.getAttribute('data-estado') === 'inactivo' ? 'Inactivo' : 'Activo');
                document.getElementById('empleadoTelefono').value = btn.getAttribute('data-telefono') || '';
            } else {
                resetForm(modalEmpleado);
                document.getElementById('empleadoId').value = 0;
                setSearchDropdown('empleadoCargoId', '', '');
                setSearchDropdown('empleadoEstado', 'activo', 'Activo');
            }
        });

        modalEmpleado.addEventListener('hidden.bs.modal', function () {
            document.getElementById('empleadoId').value = 0;
        });
    }

    // === MODAL PROVEEDOR ===
    const modalProveedor = document.getElementById('modalProveedor');
    if (modalProveedor) {
        modalProveedor.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const isEdit = btn.getAttribute('data-edit') === 'true';

            document.getElementById('proveedorAction').value = isEdit ? 'editar_proveedor' : 'nuevo_proveedor';
            document.getElementById('modalProveedorLabel').textContent = isEdit ? 'Editar proveedor' : 'Nuevo proveedor';

            if (isEdit) {
                document.getElementById('proveedorId').value = btn.getAttribute('data-id');
                document.getElementById('proveedorNit').value = btn.getAttribute('data-nit');
                document.getElementById('proveedorNombre').value = btn.getAttribute('data-nombre');
                document.getElementById('proveedorTelefono').value = btn.getAttribute('data-telefono') || '';
                document.getElementById('proveedorDireccion').value = btn.getAttribute('data-direccion') || '';
                setSearchDropdown('proveedorEstado', btn.getAttribute('data-estado') || 'activo', btn.getAttribute('data-estado') === 'inactivo' ? 'Inactivo' : 'Activo');
            } else {
                resetForm(modalProveedor);
                document.getElementById('proveedorId').value = 0;
                setSearchDropdown('proveedorEstado', 'activo', 'Activo');
            }
        });

        modalProveedor.addEventListener('hidden.bs.modal', function () {
            document.getElementById('proveedorId').value = 0;
        });
    }

    // === MODAL CARGO ===
    const modalCargo = document.getElementById('modalCargo');
    if (modalCargo) {
        modalCargo.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const isEdit = btn.getAttribute('data-edit') === 'true';

            document.getElementById('cargoAction').value = isEdit ? 'editar_cargo' : 'nuevo_cargo';
            document.getElementById('modalCargoLabel').textContent = isEdit ? 'Editar cargo' : 'Nuevo cargo';

            if (isEdit) {
                document.getElementById('cargoId').value = btn.getAttribute('data-id');
                document.getElementById('cargoNombre').value = btn.getAttribute('data-nombre');
            } else {
                document.getElementById('cargoId').value = 0;
                document.getElementById('cargoNombre').value = '';
            }
        });

        modalCargo.addEventListener('hidden.bs.modal', function () {
            document.getElementById('cargoId').value = 0;
        });
    }

    // === SET YESTERDAY DATE ON CREAR CAJA (client-side) ===
    function getYesterdayDate() {
        var d = new Date();
        var day = d.getDay();
        if (day === 1) {
            d.setDate(d.getDate() - 2);
        } else {
            d.setDate(d.getDate() - 1);
        }
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    document.querySelectorAll('.form-crear-caja').forEach(function (form) {
        var input = form.querySelector('.cliente-fecha');
        if (input) {
            input.value = getYesterdayDate();
            form.addEventListener('submit', function () {
                input.value = getYesterdayDate();
            });
        }
    });

    function resetForm(modal) {
        const inputs = modal.querySelectorAll('input:not([type="hidden"]), textarea');
        inputs.forEach(function (el) { el.value = ''; });
        const selects = modal.querySelectorAll('select');
        selects.forEach(function (el) { el.selectedIndex = 0; });
    }

    // === INIT PAGINATION FOR ALL TABLES ===
    ['tablaGastosMenor','tablaReintegrosMenor','tablaGastosMayor','tablaReintegrosMayor','tablaEmpleados','tablaProveedores','tablaCargos'].forEach(function(tid) {
        initPaginacion(tid);
    });

    // === SCROLL POSITION PRESERVATION ===
    if (sessionStorage.getItem('scrollPos')) {
        window.scrollTo(0, parseInt(sessionStorage.getItem('scrollPos')));
        sessionStorage.removeItem('scrollPos');
    }
    document.querySelectorAll('form[method="post"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            sessionStorage.setItem('scrollPos', window.scrollY);
        });
    });

    // === RE-OPEN LAST ACTIVE TAB AFTER RELOAD ===
    const activeTab = sessionStorage.getItem('activeTab');
    if (activeTab) {
        const tab = document.querySelector('[data-bs-target="' + activeTab + '"]');
        if (tab) {
            var bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            sessionStorage.setItem('activeTab', e.target.dataset.bsTarget);
        });
    });

    // === DRAG & DROP REORDER GASTOS (mouse-based) ===
    (function () {
        var draggedRow = null;
        var dragTbody = null;
        var dragGhost = null;
        var ghostOffsetY = 0;

        function createGhost(row, e) {
            var ghost = row.cloneNode(true);
            ghost.style.position = 'fixed';
            ghost.style.pointerEvents = 'none';
            ghost.style.zIndex = '9999';
            ghost.style.opacity = '0.7';
            ghost.style.width = row.offsetWidth + 'px';
            ghost.style.backgroundColor = '#fff';
            ghost.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            ghost.style.borderRadius = '4px';
            ghost.style.transform = 'rotate(2deg)';
            document.body.appendChild(ghost);
            return ghost;
        }

        document.addEventListener('mousedown', function (e) {
            var handle = e.target.closest('.drag-handle');
            if (!handle) return;
            var row = handle.closest('tr');
            if (!row) return;
            var tbody = row.closest('tbody');
            if (!tbody) return;
            var table = tbody.closest('.table-gastos');
            if (!table) return;
            draggedRow = row;
            dragTbody = tbody;
            row.classList.add('dragging');
            document.body.classList.add('dragging-active');
            ghostOffsetY = e.clientY - row.getBoundingClientRect().top;
            dragGhost = createGhost(row, e);
            e.preventDefault();
        });

        document.addEventListener('mousemove', function (e) {
            if (!draggedRow || !dragTbody) return;
            if (dragGhost) {
                dragGhost.style.top = (e.clientY - ghostOffsetY) + 'px';
                dragGhost.style.left = (draggedRow.getBoundingClientRect().left) + 'px';
            }
            dragTbody.querySelectorAll('tr').forEach(function (r) { r.classList.remove('drag-over'); });
            var tr = e.target.closest('tr');
            if (!tr || tr === draggedRow || tr.parentNode !== dragTbody) return;
            tr.classList.add('drag-over');
        });

        document.addEventListener('mouseup', function (e) {
            finishDrag(e);
        });

        document.addEventListener('mouseleave', function () {
            if (draggedRow) finishDrag(null);
        });

        function finishDrag(e) {
            if (!draggedRow || !dragTbody) return;
            draggedRow.classList.remove('dragging');
            document.body.classList.remove('dragging-active');
            if (dragGhost) { dragGhost.remove(); dragGhost = null; }
            dragTbody.querySelectorAll('tr').forEach(function (r) { r.classList.remove('drag-over'); });
            if (e) {
                var targetRow = e.target.closest('tr');
                if (targetRow && targetRow.parentNode === dragTbody && targetRow !== draggedRow) {
                    var rect = targetRow.getBoundingClientRect();
                    if (e.clientY < rect.top + rect.height / 2) {
                        targetRow.parentNode.insertBefore(draggedRow, targetRow);
                    } else {
                        targetRow.parentNode.insertBefore(draggedRow, targetRow.nextSibling);
                    }
                    var table = dragTbody.closest('.table-gastos');
                    if (table) saveGastoOrder(dragTbody, table.dataset.cajaId);
                }
            }
            draggedRow = null;
            dragTbody = null;
        }

        function saveGastoOrder(tbody, cajaId) {
            var ids = Array.from(tbody.querySelectorAll('tr')).map(function (row) { return row.dataset.id; });
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'reordenar_gastos', caja_id: cajaId, orden: JSON.stringify(ids) })
            });
        }
    })();
});

// === FILTRO DE TABLAS ===
function filtrarTabla(input, tableId) {
    var filter = input.value.toUpperCase();
    var rows = document.getElementById(tableId).querySelectorAll('tbody tr');
    rows.forEach(function (row) {
        var match = row.textContent.toUpperCase().includes(filter);
        row.dataset.fs = match ? '1' : '0';
    });
    sessionStorage.setItem('busqueda_' + tableId, input.value);
    setPagina(tableId, 1);
    aplicarFiltrosColumna(tableId);
    aplicarPaginacion(tableId);
    actualizarInfoPagina(tableId);
}

function aplicarFiltrosColumna(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var rows = table.querySelectorAll('tbody tr');
    rows.forEach(function (row) {
        var matchSearch = row.dataset.fs !== '0';
        var matchCols = true;
        row.querySelectorAll('[data-cf]').forEach(function (el) {
            if (el.dataset.cf === '0') matchCols = false;
        });
        row.style.display = (matchSearch && matchCols) ? '' : 'none';
    });
}

function guardarFiltroColumna(row, colIndex, match) {
    var cols = row.querySelectorAll('td');
    if (cols[colIndex]) {
        cols[colIndex].dataset.cf = match ? '1' : '0';
    }
}

function filtrarColumna(input, tableId, colIndex) {
    var filter = input.value.toUpperCase();
    var rows = document.getElementById(tableId).querySelectorAll('tbody tr');
    rows.forEach(function (row) {
        var cols = row.querySelectorAll('td');
        if (cols[colIndex]) {
            var match = cols[colIndex].textContent.toUpperCase().includes(filter);
            guardarFiltroColumna(row, colIndex, match);
        }
    });
    setPagina(tableId, 1);
    aplicarFiltrosColumna(tableId);
    aplicarPaginacion(tableId);
    actualizarInfoPagina(tableId);
}

function filtrarColumnaSelect(select, tableId, colIndex) {
    var filter = select.value.toUpperCase();
    var rows = document.getElementById(tableId).querySelectorAll('tbody tr');
    rows.forEach(function (row) {
        var cols = row.querySelectorAll('td');
        if (!filter) { guardarFiltroColumna(row, colIndex, true); return; }
        if (cols[colIndex]) {
            var match = cols[colIndex].textContent.trim().toUpperCase() === filter;
            guardarFiltroColumna(row, colIndex, match);
        }
    });
    setPagina(tableId, 1);
    aplicarFiltrosColumna(tableId);
    aplicarPaginacion(tableId);
    actualizarInfoPagina(tableId);
}

function quitarFiltros(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var container = table.closest('.tab-pane') || table.closest('.card') || table.parentElement;
    var searchInputs = container ? container.querySelectorAll('input[placeholder="Buscar..."], input[placeholder="Filtrar..."]') : [];
    searchInputs.forEach(function(s) { s.value = ''; });
    var filterSelects = container ? container.querySelectorAll('select[onchange*="filtrarColumnaSelect"]') : [];
    filterSelects.forEach(function(s) { s.selectedIndex = 0; });
    var rows = table.querySelectorAll('tbody tr');
    rows.forEach(function (row) {
        row.style.display = '';
        row.dataset.fs = '1';
        row.querySelectorAll('[data-cf]').forEach(function (el) { delete el.dataset.cf; });
    });
    sessionStorage.setItem('pagina_' + tableId, '1');
    sessionStorage.setItem('busqueda_' + tableId, '');
    aplicarFiltrosColumna(tableId);
    aplicarPaginacion(tableId);
    actualizarInfoPagina(tableId);
}

// === PAGINACION ===
function getPagina(tableId) {
    return parseInt(sessionStorage.getItem('pagina_' + tableId)) || 1;
}
function setPagina(tableId, p) {
    sessionStorage.setItem('pagina_' + tableId, p.toString());
}
function getPageSize(tableId) {
    return parseInt(sessionStorage.getItem('pagSize_' + tableId)) || 20;
}
function setPageSize(tableId, s) {
    sessionStorage.setItem('pagSize_' + tableId, s.toString());
}

function cambiarPagina(pageSize, tableId) {
    setPageSize(tableId, pageSize);
    setPagina(tableId, 1);
    aplicarPaginacion(tableId);
    actualizarInfoPagina(tableId);
}

function irPagina(tableId, delta) {
    var total = contarVisibles(tableId);
    var size = getPageSize(tableId);
    var maxPage = Math.max(1, Math.ceil(total / size));
    var p = Math.min(maxPage, Math.max(1, getPagina(tableId) + delta));
    setPagina(tableId, p);
    aplicarPaginacion(tableId);
    actualizarInfoPagina(tableId);
}

function contarVisibles(tableId) {
    var rows = document.getElementById(tableId).querySelectorAll('tbody tr');
    var count = 0;
    rows.forEach(function (r) { if (r.style.display !== 'none') count++; });
    return count;
}

function aplicarPaginacion(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var rows = table.querySelectorAll('tbody tr');
    var size = getPageSize(tableId);
    var page = getPagina(tableId);
    var visible = [];
    rows.forEach(function (r) { if (r.style.display !== 'none') visible.push(r); });
    var totalPages = Math.max(1, Math.ceil(visible.length / size));
    if (page > totalPages) { page = totalPages; setPagina(tableId, page); }
    var start = (page - 1) * size;
    var end = Math.min(start + size, visible.length);
    visible.forEach(function (r, i) {
        r.style.display = (i >= start && i < end) ? '' : 'none';
    });
}

function actualizarInfoPagina(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var container = table.closest('.tab-pane') || table.closest('.card') || table.parentElement;
    if (!container) return;
    var infoSpan = container.querySelector('.pag-info');
    if (!infoSpan) return;
    var total = contarVisibles(tableId);
    var size = getPageSize(tableId);
    var page = getPagina(tableId);
    var from = total === 0 ? 0 : (page - 1) * size + 1;
    var to = Math.min(page * size, total);
    infoSpan.textContent = total > 0 ? 'Mostrando ' + from + '–' + to + ' de ' + total : '0 resultados';
}

function initPaginacion(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var container = table.closest('.tab-pane') || table.closest('.card') || table.parentElement;
    var savedSize = sessionStorage.getItem('pagSize_' + tableId);
    var sizeSel = container ? container.querySelector('select[onchange*="' + tableId + '"]') : null;
    if (sizeSel && savedSize) sizeSel.value = savedSize;
    var searchVal = sessionStorage.getItem('busqueda_' + tableId);
    if (searchVal) {
        var searchInput = container ? container.querySelector('input[placeholder="Buscar..."]') : null;
        if (searchInput) { searchInput.value = searchVal; filtrarTabla(searchInput, tableId); }
    } else {
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
            row.dataset.fs = '1';
            row.querySelectorAll('[data-cf]').forEach(function (el) { delete el.dataset.cf; });
        });
        aplicarFiltrosColumna(tableId);
        aplicarPaginacion(tableId);
        actualizarInfoPagina(tableId);
    }
}

// === SEARCH DROPDOWN ===
document.addEventListener('click', function (e) {
    var dd = e.target.closest('.search-dropdown');
    document.querySelectorAll('.search-dropdown-list').forEach(function (l) {
        if (!dd || !dd.contains(l)) l.style.display = 'none';
    });
    if (!dd) return;
    var list = dd.querySelector('.search-dropdown-list');
    if (e.target.closest('.search-dropdown-input')) {
        list.style.display = list.style.display === 'none' ? '' : 'none';
        if (list.style.display !== 'none') filtrarDropdownList(dd);
    }
});

document.addEventListener('click', function (e) {
    var item = e.target.closest('.search-dropdown-item');
    if (!item) return;
    var dd = item.closest('.search-dropdown');
    if (!dd) return;
    var input = dd.querySelector('.search-dropdown-input');
    var hidden = dd.querySelector('input[type="hidden"]');
    hidden.value = item.getAttribute('data-value');
    input.value = item.textContent;
    dd.querySelector('.search-dropdown-list').style.display = 'none';
});

function filtrarDropdownList(dd) {
    var input = dd.querySelector('.search-dropdown-input');
    var filter = input.value.toUpperCase();
    dd.querySelectorAll('.search-dropdown-item').forEach(function (item) {
        var searchText = (item.getAttribute('data-search') || item.textContent).toUpperCase();
        item.style.display = searchText.includes(filter) ? '' : 'none';
    });
}

document.addEventListener('input', function (e) {
    if (e.target.closest('.search-dropdown-input')) {
        filtrarDropdownList(e.target.closest('.search-dropdown'));
    }
});

function setSearchDropdown(selectId, value, text) {
    var dd = document.querySelector('.search-dropdown[data-select="' + selectId + '"]');
    if (!dd) return;
    var hidden = dd.querySelector('input[type="hidden"]');
    var input = dd.querySelector('.search-dropdown-input');
    hidden.value = value;
    input.value = text;
}
