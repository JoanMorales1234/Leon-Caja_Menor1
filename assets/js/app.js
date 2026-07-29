document.addEventListener('DOMContentLoaded', function () {

    // === MODAL GASTO (crear/editar) ===
    const modalGasto = document.getElementById('modalGasto');
    const gastoValor = document.getElementById('gastoValor');
    const gastoValorMsg = document.getElementById('gastoValorMsg');
    const gastoFecha = document.getElementById('gastoFecha');
    const gastoSoporteFoto = document.getElementById('gastoSoporteFoto');
    const gastoFotoPreview = document.getElementById('gastoFotoPreview');
    const gastoSoporte = document.getElementById('gastoSoporte');
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
        // === VARIABLES DE CROP - Declarar al principio ===
        let cropper = null;
        let croppedFile = null;
        let currentFile = null;
        const formFields = document.getElementById('formFields');
        const cropContainer = document.getElementById('cropContainer');
        const imagenRecortar = document.getElementById('imagenRecortar');
        const previewCanvas = document.getElementById('previewCanvas');
        const rotarIzquierda = document.getElementById('rotarIzquierda');
        const rotarDerecha = document.getElementById('rotarDerecha');
        const btnConfirmarCrop = document.getElementById('btnConfirmarCrop');
        const btnCancelarCrop = document.getElementById('btnCancelarCrop');

        // === FUNCIONES DE HELPERS ===
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

        function abrirCropperDesdeSrc(src) {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            croppedFile = null;
            mostrarCropContainer();
            imagenRecortar.onload = function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                cropper = new Cropper(imagenRecortar, {
                    aspectRatio: NaN,
                    viewMode: 1,
                    autoCropArea: 1,
                    responsive: true,
                    checkCrossOrigin: false,
                    ready: function () {
                        actualizarPreview(this);
                    },
                    cropend: function () {
                        actualizarPreview(this);
                    }
                });
            };
            imagenRecortar.src = src;
        }

        function mostrarPreviewConEliminar(src) {
            if (!gastoFotoPreview) return;
            gastoFotoPreview.innerHTML = '<div style="position:relative;display:inline-block;">'
                + '<img src="' + src + '" class="img-thumbnail" style="max-height:100px" alt="Foto recibo">'
                + '<div class="mt-1 d-flex gap-1">'
                + '<button type="button" id="btnRecortarFoto" class="btn btn-sm btn-outline-primary py-0 px-1" title="Volver a recortar"><i class="bi bi-crop"></i> Recortar</button>'
                + '<button type="button" id="btnEliminarFoto" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar foto"><i class="bi bi-trash"></i></button>'
                + '</div>'
                + '</div>';
            var btnRecortar = document.getElementById('btnRecortarFoto');
            if (btnRecortar) {
                btnRecortar.addEventListener('click', function () {
                    abrirCropperDesdeSrc(src);
                });
            }
            var btnEliminar = document.getElementById('btnEliminarFoto');
            if (btnEliminar) {
                btnEliminar.addEventListener('click', function () {
                    gastoFotoPreview.innerHTML = '';
                    if (gastoSoporteFoto) gastoSoporteFoto.value = '';
                    croppedFile = null;
                    currentFile = null;
                });
            }
        }

        function mostrarCropContainer() {
            if (formFields) formFields.style.display = 'none';
            if (cropContainer) cropContainer.style.display = 'block';
        }

        function ocultarCropContainer() {
            if (formFields) formFields.style.display = '';
            if (cropContainer) cropContainer.style.display = 'none';
        }

        // === EVENT LISTENERS ===
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

            // Always set fecha from caja's fecha_caja (locked)
            if (gastoFecha) {
                gastoFecha.value = fechaCaja || new Date().toISOString().split('T')[0];
            }

            if (isEdit) {
                document.getElementById('gastoId').value = btn.getAttribute('data-id');
                document.getElementById('gastoEmpleado').value = btn.getAttribute('data-empleado-id') || '';
                document.getElementById('gastoProveedor').value = btn.getAttribute('data-proveedor-id') || '';
                document.getElementById('gastoDescripcion').value = btn.getAttribute('data-descripcion');
                document.getElementById('gastoValor').value = btn.getAttribute('data-valor');
                document.getElementById('gastoTipoSoporte').value = btn.getAttribute('data-tipo-soporte') || 'factura';
                document.getElementById('gastoSoporte').value = btn.getAttribute('data-soporte') || '';
            } else {
                document.getElementById('gastoId').value = 0;
                document.getElementById('gastoForm') && document.getElementById('gastoForm').reset();
                document.getElementById('gastoDescripcion').value = '';
                document.getElementById('gastoValor').value = '';
                document.getElementById('gastoSoporte').value = '';
                document.getElementById('gastoEmpleado').value = '';
                document.getElementById('gastoProveedor').value = '';
                document.getElementById('gastoTipoSoporte').value = 'factura';
                if (gastoSoporteFoto) gastoSoporteFoto.value = '';
                if (gastoFotoPreview) gastoFotoPreview.innerHTML = '';
            }
            validarValorGasto();
        });

        modalGasto.addEventListener('hidden.bs.modal', function () {
            document.getElementById('gastoId').value = 0;
            if (gastoFotoPreview) gastoFotoPreview.innerHTML = '';
            ocultarCropContainer();
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        });

        if (gastoValor) gastoValor.addEventListener('input', validarValorGasto);

        if (gastoSoporteFoto && gastoFotoPreview) {
            gastoSoporteFoto.addEventListener('change', function (e) {
                const file = e.target.files[0];
                currentFile = file;
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        croppedFile = null;
                        mostrarCropContainer();
                        imagenRecortar.onload = function () {
                            if (cropper) {
                                cropper.destroy();
                                cropper = null;
                            }
                            cropper = new Cropper(imagenRecortar, {
                                aspectRatio: NaN,
                                viewMode: 1,
                                autoCropArea: 1,
                                responsive: true,
                                checkCrossOrigin: false,
                                ready: function () {
                                    actualizarPreview(this);
                                },
                                cropend: function () {
                                    actualizarPreview(this);
                                }
                            });
                        };
                        imagenRecortar.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                } else if (file) {
                    var blobUrl = URL.createObjectURL(file);
                    mostrarPreviewConEliminar(blobUrl);
                } else {
                    gastoFotoPreview.innerHTML = '';
                }
            });
        }

        if (rotarIzquierda) {
            rotarIzquierda.addEventListener('click', function () {
                if (cropper) {
                    cropper.rotate(-90);
                    actualizarPreview();
                }
            });
        }
        if (rotarDerecha) {
            rotarDerecha.addEventListener('click', function () {
                if (cropper) {
                    cropper.rotate(90);
                    actualizarPreview();
                }
            });
        }

        if (btnConfirmarCrop) {
            btnConfirmarCrop.addEventListener('click', function () {
                if (cropper) {
                    var canvas = cropper.getCroppedCanvas({ fillColor: '#fff' });
                    canvas.toBlob(function (blob) {
                        croppedFile = new File([blob], 'recorto_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                        var dataTransfer = new DataTransfer();
                        dataTransfer.items.add(croppedFile);
                        gastoSoporteFoto.files = dataTransfer.files;
                        var previewUrl = URL.createObjectURL(croppedFile);
                        mostrarPreviewConEliminar(previewUrl);
                        cropper.destroy();
                        cropper = null;
                        ocultarCropContainer();
                    }, 'image/jpeg', 0.9);
                }
            });
        }

        if (btnCancelarCrop) {
            btnCancelarCrop.addEventListener('click', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                if (gastoSoporteFoto) gastoSoporteFoto.value = '';
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
                document.getElementById('empleadoCargoId').value = btn.getAttribute('data-cargo-id') || '';
                document.getElementById('empleadoTelefono').value = btn.getAttribute('data-telefono') || '';
            } else {
                resetForm(modalEmpleado);
                document.getElementById('empleadoId').value = 0;
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
            } else {
                resetForm(modalProveedor);
                document.getElementById('proveedorId').value = 0;
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
        sessionStorage.removeItem('activeTab');
    }
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            sessionStorage.setItem('activeTab', e.target.dataset.bsTarget);
        });
    });
});

// === FILTRO DE TABLAS ===
function filtrarTabla(input, tableId) {
    var filter = input.value.toUpperCase();
    var rows = document.getElementById(tableId).querySelectorAll('tbody tr');
    rows.forEach(function (row) {
        row.style.display = row.textContent.toUpperCase().includes(filter) ? '' : 'none';
    });
}
