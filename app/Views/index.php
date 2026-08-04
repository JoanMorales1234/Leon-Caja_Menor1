<div class="container py-4">

    <div class="mb-3">
        <h1 class="h3 mb-0">Sistema de Caja Menor / Mayor</h1>
        <p class="text-muted mb-0">Administra gastos, reintegros y catálogos en un solo lugar.</p>
    </div>

    <?php if ($message): ?>
        <?= flash($message, $type) ?>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="mainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="menor-tab" data-bs-toggle="tab" data-bs-target="#menor" type="button">
                <i class="bi bi-wallet2"></i> Caja Menor
                <?php if ($cajaMenor): ?>
                    <span class="badge bg-info ms-1"><?= number_format($cajaMenor['saldo_actual'], 0, ',', '.') ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="mayor-tab" data-bs-toggle="tab" data-bs-target="#mayor" type="button">
                <i class="bi bi-safe"></i> Caja Mayor
                <?php if ($cajaMayor): ?><span class="badge bg-warning ms-1"><?= number_format($cajaMayor['saldo_actual'], 0, ',', '.') ?></span><?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="empleados-tab" data-bs-toggle="tab" data-bs-target="#empleados-pane" type="button">
                <i class="bi bi-people"></i> Empleados <span class="badge bg-secondary ms-1"><?= count($todosEmpleados) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="proveedores-tab" data-bs-toggle="tab" data-bs-target="#proveedores-pane" type="button">
                <i class="bi bi-truck"></i> Proveedores <span class="badge bg-secondary ms-1"><?= count($todosProveedores) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cargos-tab" data-bs-toggle="tab" data-bs-target="#cargos-pane" type="button">
                <i class="bi bi-briefcase"></i> Cargos <span class="badge bg-secondary ms-1"><?= count($todosCargos) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="festivos-tab" data-bs-toggle="tab" data-bs-target="#festivos-pane" type="button">
                <i class="bi bi-calendar-event"></i> Festivos
            </button>
        </li>
    </ul>

    <div class="tab-content" id="mainTabsContent">

        <!-- ==================== TAB CAJA MENOR ==================== -->
        <div class="tab-pane fade show active" id="menor" role="tabpanel">
            <?php if ($cajaMenor): ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Valor inicial</small>
                                <span class="h5"><?= number_format($cajaMenor['valor_inicial'], 2, ',', '.') ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Total gastos</small>
                                <span class="h5 text-danger"><?= number_format($cajaMenor['total_gastos'], 2, ',', '.') ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Total reintegros</small>
                                <span class="h5 text-success"><?= number_format($cajaMenor['total_reintegros'], 2, ',', '.') ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Saldo actual</small>
                                <span class="h5 fw-bold <?= $cajaMenor['saldo_actual'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($cajaMenor['saldo_actual'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGasto" data-tipo="menor" data-caja-id="<?= $cajaMenor['id'] ?>" data-fecha-caja="<?= $cajaMenor['fecha_caja'] ?>">
                                <i class="bi bi-plus-lg"></i> Agregar gasto
                            </button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalReintegro" data-tipo="menor" data-caja-id="<?= $cajaMenor['id'] ?>" data-fecha-caja="<?= $cajaMenor['fecha_caja'] ?>">
                                <i class="bi bi-plus-lg"></i> Agregar reintegro
                            </button>
                            <a class="btn btn-outline-dark btn-sm" href="<?= url('imprimir', ['caja_id' => $cajaMenor['id']]) ?>" target="_blank">
                                <i class="bi bi-printer"></i> Imprimir
                            </a>
                            <form method="post" class="d-inline" onsubmit="return confirm('¿Cerrar la caja menor? Los movimientos quedarán guardados.')">
                                <input type="hidden" name="action" value="cerrar_caja">
                                <input type="hidden" name="caja_id" value="<?= $cajaMenor['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Cerrar caja</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 g-3">
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                <span><i class="bi bi-cart3"></i> Gastos</span>
                                <div class="d-flex gap-1 align-items-center">
                                    <input type="text" class="form-control form-control-sm" style="width:160px" placeholder="Buscar..." onkeyup="filtrarTabla(this, 'tablaGastosMenor')">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="quitarFiltros('tablaGastosMenor')" title="Quitar filtros"><i class="bi bi-x-circle"></i></button>
                                    <select class="form-select form-select-sm" style="width:auto" onchange="cambiarPagina(this.value,'tablaGastosMenor')">
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($movimientosMenor['gastos'])): ?>
                                    <p class="text-muted p-3 mb-0">Sin gastos registrados.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0 table-gastos" id="tablaGastosMenor" data-caja-id="<?= $cajaMenor['id'] ?>">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:32px"><i class="bi bi-grip-vertical"></i></th>
                                                    <th>Fecha</th>
                                                    <th>Descripción</th>
                                                    <th>Valor</th>
                                                    <th>Empleado</th>
                                                    <th style="width:110px">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($movimientosMenor['gastos'] as $gasto): ?>
                                                    <tr data-id="<?= $gasto['id'] ?>">
                                                        <td class="drag-handle text-center"><i class="bi bi-grip-vertical"></i></td>
                                                        <td><?= htmlspecialchars($gasto['fecha_gasto']) ?></td>
                                                        <td><?= htmlspecialchars($gasto['descripcion']) ?>
                                                            <?php if (!empty($gasto['soportes'])): ?>
                                                                <br><div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ($gasto['soportes'] as $sop): ?>
                                                                    <?php if ($sop['archivo']): ?>
                                                                        <a href="<?= asset($sop['archivo']) ?>" target="_blank" title="Ver soporte"><img src="<?= asset($sop['archivo']) ?>" class="img-thumbnail" style="max-height:35px" alt="Soporte"></a>
                                                                    <?php elseif ($sop['descripcion']): ?>
                                                                        <small class="text-muted"><?= htmlspecialchars($sop['descripcion']) ?></small>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-danger fw-semibold"><?= number_format($gasto['valor'], 2, ',', '.') ?></td>
                                                        <td><small><?= htmlspecialchars(($gasto['nombres'] ?? '') . ' ' . ($gasto['apellidos'] ?? '-')) ?></small></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-success py-0 px-1" title="Editar"
                                                                data-bs-toggle="modal" data-bs-target="#modalGasto"
                                                                data-tipo="menor" data-caja-id="<?= $cajaMenor['id'] ?>"
                                                                data-fecha-caja="<?= $cajaMenor['fecha_caja'] ?>"
                                                                data-edit="true" data-id="<?= $gasto['id'] ?>"
                                                                data-empleado-id="<?= $gasto['empleado_id'] ?>"
                                                                data-empleado-nombre="<?= htmlspecialchars(trim(($gasto['nombres'] ?? '') . ' ' . ($gasto['apellidos'] ?? '')), ENT_QUOTES) ?>"
                                                                data-proveedor-id="<?= $gasto['proveedor_id'] ?>"
                                                                data-proveedor-nombre="<?= htmlspecialchars($gasto['proveedor_nombre'] ?? '', ENT_QUOTES) ?>"
                                                                data-fecha="<?= $gasto['fecha_gasto'] ?>"
                                                                data-descripcion="<?= htmlspecialchars($gasto['descripcion'], ENT_QUOTES) ?>"
                                                                data-valor="<?= $gasto['valor'] ?>"
                                                                data-soportes='<?= htmlspecialchars(json_encode($gasto['soportes']), ENT_QUOTES) ?>'>
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este gasto?')">
                                                                <input type="hidden" name="action" value="eliminar_gasto">
                                                                <input type="hidden" name="id" value="<?= $gasto['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div class="d-flex justify-content-between align-items-center px-2 py-1 border-top">
                                            <span class="pag-info text-muted small"></span>
                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaGastosMenor',-1)">‹</button>
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaGastosMenor',1)">›</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                <span><i class="bi bi-arrow-return-left"></i> Reintegros</span>
                                <div class="d-flex gap-1 align-items-center">
                                    <input type="text" class="form-control form-control-sm" style="width:160px" placeholder="Buscar..." onkeyup="filtrarTabla(this, 'tablaReintegrosMenor')">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="quitarFiltros('tablaReintegrosMenor')" title="Quitar filtros"><i class="bi bi-x-circle"></i></button>
                                    <select class="form-select form-select-sm" style="width:auto" onchange="cambiarPagina(this.value,'tablaReintegrosMenor')">
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($movimientosMenor['reintegros'])): ?>
                                    <p class="text-muted p-3 mb-0">Sin reintegros registrados.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0" id="tablaReintegrosMenor">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Descripción</th>
                                                    <th>Valor</th>
                                                    <th style="width:110px">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($movimientosMenor['reintegros'] as $reintegro): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($reintegro['fecha_reintegro']) ?></td>
                                                        <td><?= htmlspecialchars($reintegro['descripcion']) ?>
                                                            <?php if ($reintegro['soporte']): ?><br><small class="text-muted"><?= htmlspecialchars($reintegro['soporte']) ?></small><?php endif; ?>
                                                        </td>
                                                        <td class="text-success fw-semibold"><?= number_format($reintegro['valor'], 2, ',', '.') ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-success py-0 px-1" title="Editar"
                                                                data-bs-toggle="modal" data-bs-target="#modalReintegro"
                                                                data-tipo="menor" data-caja-id="<?= $cajaMenor['id'] ?>"
                                                                data-fecha-caja="<?= $cajaMenor['fecha_caja'] ?>"
                                                                data-edit="true" data-id="<?= $reintegro['id'] ?>"
                                                                data-fecha="<?= $reintegro['fecha_reintegro'] ?>"
                                                                data-descripcion="<?= htmlspecialchars($reintegro['descripcion'] ?? '', ENT_QUOTES) ?>"
                                                                data-valor="<?= $reintegro['valor'] ?>"
                                                                data-soporte="<?= htmlspecialchars($reintegro['soporte'] ?? '', ENT_QUOTES) ?>">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este reintegro?')">
                                                                <input type="hidden" name="action" value="eliminar_reintegro">
                                                                <input type="hidden" name="id" value="<?= $reintegro['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div class="d-flex justify-content-between align-items-center px-2 py-1 border-top">
                                            <span class="pag-info text-muted small"></span>
                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaReintegrosMenor',-1)">‹</button>
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaReintegrosMenor',1)">›</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-info-circle"></i> No hay caja menor abierta.</span>
                    <form method="post" class="m-0 form-crear-caja">
                        <input type="hidden" name="action" value="nueva_caja">
                        <input type="hidden" name="tipo" value="menor">
                        <input type="hidden" name="cliente_fecha" class="cliente-fecha" value="">
                        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Abrir Caja Menor</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==================== TAB CAJA MAYOR ==================== -->
        <div class="tab-pane fade" id="mayor" role="tabpanel">
            <?php if ($cajaMayor): ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Valor inicial</small>
                                <span class="h5"><?= number_format($cajaMayor['valor_inicial'], 2, ',', '.') ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Total gastos</small>
                                <span class="h5 text-danger"><?= number_format($cajaMayor['total_gastos'], 2, ',', '.') ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Total reintegros</small>
                                <span class="h5 text-success"><?= number_format($cajaMayor['total_reintegros'], 2, ',', '.') ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Saldo actual</small>
                                <span class="h5 fw-bold <?= $cajaMayor['saldo_actual'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($cajaMayor['saldo_actual'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGasto" data-tipo="mayor" data-caja-id="<?= $cajaMayor['id'] ?>" data-fecha-caja="<?= $cajaMayor['fecha_caja'] ?>">
                                <i class="bi bi-plus-lg"></i> Agregar gasto
                            </button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalReintegro" data-tipo="mayor" data-caja-id="<?= $cajaMayor['id'] ?>" data-fecha-caja="<?= $cajaMayor['fecha_caja'] ?>">
                                <i class="bi bi-plus-lg"></i> Agregar reintegro
                            </button>
                            <a class="btn btn-outline-dark btn-sm" href="<?= url('imprimir', ['caja_id' => $cajaMayor['id']]) ?>" target="_blank">
                                <i class="bi bi-printer"></i> Imprimir
                            </a>
                            <form method="post" class="d-inline" onsubmit="return confirm('¿Cerrar la caja mayor? Los movimientos quedarán guardados.')">
                                <input type="hidden" name="action" value="cerrar_caja">
                                <input type="hidden" name="caja_id" value="<?= $cajaMayor['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Cerrar caja</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 g-3">
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                <span><i class="bi bi-cart3"></i> Gastos</span>
                                <div class="d-flex gap-1 align-items-center">
                                    <input type="text" class="form-control form-control-sm" style="width:160px" placeholder="Buscar..." onkeyup="filtrarTabla(this, 'tablaGastosMayor')">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="quitarFiltros('tablaGastosMayor')" title="Quitar filtros"><i class="bi bi-x-circle"></i></button>
                                    <select class="form-select form-select-sm" style="width:auto" onchange="cambiarPagina(this.value,'tablaGastosMayor')">
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($movimientosMayor['gastos'])): ?>
                                    <p class="text-muted p-3 mb-0">Sin gastos registrados.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0 table-gastos" id="tablaGastosMayor" data-caja-id="<?= $cajaMayor['id'] ?>">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:32px"><i class="bi bi-grip-vertical"></i></th>
                                                    <th>Fecha</th>
                                                    <th>Descripción</th>
                                                    <th>Valor</th>
                                                    <th>Empleado</th>
                                                    <th style="width:110px">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($movimientosMayor['gastos'] as $gasto): ?>
                                                    <tr data-id="<?= $gasto['id'] ?>">
                                                        <td class="drag-handle text-center"><i class="bi bi-grip-vertical"></i></td>
                                                        <td><?= htmlspecialchars($gasto['fecha_gasto']) ?></td>
                                                        <td><?= htmlspecialchars($gasto['descripcion']) ?>
                                                            <?php if (!empty($gasto['soportes'])): ?>
                                                                <br><div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ($gasto['soportes'] as $sop): ?>
                                                                    <?php if ($sop['archivo']): ?>
                                                                        <a href="<?= asset($sop['archivo']) ?>" target="_blank" title="Ver soporte"><img src="<?= asset($sop['archivo']) ?>" class="img-thumbnail" style="max-height:35px" alt="Soporte"></a>
                                                                    <?php elseif ($sop['descripcion']): ?>
                                                                        <small class="text-muted"><?= htmlspecialchars($sop['descripcion']) ?></small>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-danger fw-semibold"><?= number_format($gasto['valor'], 2, ',', '.') ?></td>
                                                        <td><small><?= htmlspecialchars(($gasto['nombres'] ?? '') . ' ' . ($gasto['apellidos'] ?? '-')) ?></small></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-success py-0 px-1" title="Editar"
                                                                data-bs-toggle="modal" data-bs-target="#modalGasto"
                                                                data-tipo="mayor" data-caja-id="<?= $cajaMayor['id'] ?>"
                                                                data-fecha-caja="<?= $cajaMayor['fecha_caja'] ?>"
                                                                data-edit="true" data-id="<?= $gasto['id'] ?>"
                                                                data-empleado-id="<?= $gasto['empleado_id'] ?>"
                                                                data-empleado-nombre="<?= htmlspecialchars(trim(($gasto['nombres'] ?? '') . ' ' . ($gasto['apellidos'] ?? '')), ENT_QUOTES) ?>"
                                                                data-proveedor-id="<?= $gasto['proveedor_id'] ?>"
                                                                data-proveedor-nombre="<?= htmlspecialchars($gasto['proveedor_nombre'] ?? '', ENT_QUOTES) ?>"
                                                                data-fecha="<?= $gasto['fecha_gasto'] ?>"
                                                                data-descripcion="<?= htmlspecialchars($gasto['descripcion'], ENT_QUOTES) ?>"
                                                                data-valor="<?= $gasto['valor'] ?>"
                                                                data-soportes='<?= htmlspecialchars(json_encode($gasto['soportes']), ENT_QUOTES) ?>'>
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este gasto?')">
                                                                <input type="hidden" name="action" value="eliminar_gasto">
                                                                <input type="hidden" name="id" value="<?= $gasto['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div class="d-flex justify-content-between align-items-center px-2 py-1 border-top">
                                            <span class="pag-info text-muted small"></span>
                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaGastosMayor',-1)">‹</button>
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaGastosMayor',1)">›</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                <span><i class="bi bi-arrow-return-left"></i> Reintegros</span>
                                <div class="d-flex gap-1 align-items-center">
                                    <input type="text" class="form-control form-control-sm" style="width:160px" placeholder="Buscar..." onkeyup="filtrarTabla(this, 'tablaReintegrosMayor')">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="quitarFiltros('tablaReintegrosMayor')" title="Quitar filtros"><i class="bi bi-x-circle"></i></button>
                                    <select class="form-select form-select-sm" style="width:auto" onchange="cambiarPagina(this.value,'tablaReintegrosMayor')">
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($movimientosMayor['reintegros'])): ?>
                                    <p class="text-muted p-3 mb-0">Sin reintegros registrados.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0" id="tablaReintegrosMayor">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Descripción</th>
                                                    <th>Valor</th>
                                                    <th style="width:110px">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($movimientosMayor['reintegros'] as $reintegro): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($reintegro['fecha_reintegro']) ?></td>
                                                        <td><?= htmlspecialchars($reintegro['descripcion']) ?>
                                                            <?php if ($reintegro['soporte']): ?><br><small class="text-muted"><?= htmlspecialchars($reintegro['soporte']) ?></small><?php endif; ?>
                                                        </td>
                                                        <td class="text-success fw-semibold"><?= number_format($reintegro['valor'], 2, ',', '.') ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-success py-0 px-1" title="Editar"
                                                                data-bs-toggle="modal" data-bs-target="#modalReintegro"
                                                                data-tipo="mayor" data-caja-id="<?= $cajaMayor['id'] ?>"
                                                                data-fecha-caja="<?= $cajaMayor['fecha_caja'] ?>"
                                                                data-edit="true" data-id="<?= $reintegro['id'] ?>"
                                                                data-fecha="<?= $reintegro['fecha_reintegro'] ?>"
                                                                data-descripcion="<?= htmlspecialchars($reintegro['descripcion'] ?? '', ENT_QUOTES) ?>"
                                                                data-valor="<?= $reintegro['valor'] ?>"
                                                                data-soporte="<?= htmlspecialchars($reintegro['soporte'] ?? '', ENT_QUOTES) ?>">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este reintegro?')">
                                                                <input type="hidden" name="action" value="eliminar_reintegro">
                                                                <input type="hidden" name="id" value="<?= $reintegro['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div class="d-flex justify-content-between align-items-center px-2 py-1 border-top">
                                            <span class="pag-info text-muted small"></span>
                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaReintegrosMayor',-1)">‹</button>
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaReintegrosMayor',1)">›</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-info-circle"></i> No hay caja mayor abierta.</span>
                    <form method="post" class="m-0 form-crear-caja">
                        <input type="hidden" name="action" value="nueva_caja">
                        <input type="hidden" name="tipo" value="mayor">
                        <input type="hidden" name="cliente_fecha" class="cliente-fecha" value="">
                        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Abrir Caja Mayor</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==================== TAB EMPLEADOS ==================== -->
        <div class="tab-pane fade" id="empleados-pane" role="tabpanel">
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Empleados</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEmpleado" data-edit="false">
                        <i class="bi bi-plus-lg"></i> Nuevo empleado
                    </button>
                </div>
                <?php if (empty($todosEmpleados)): ?>
                    <div class="alert alert-light">No hay empleados registrados.</div>
                <?php else: ?>
                    <div class="mb-2 d-flex gap-2 align-items-center">
                        <input type="text" class="form-control form-control-sm" style="width:250px" placeholder="Buscar..." onkeyup="filtrarTabla(this, 'tablaEmpleados')">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quitarFiltros('tablaEmpleados')"><i class="bi bi-x-circle"></i> Quitar filtros</button>
                        <select class="form-select form-select-sm ms-auto" style="width:auto" onchange="cambiarPagina(this.value,'tablaEmpleados')">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted small">por página</span>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0" id="tablaEmpleados">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cédula<br><input type="text" class="form-control form-control-sm" placeholder="Filtrar..." onkeyup="filtrarColumna(this,'tablaEmpleados',0)" style="width:100%;"></th>
                                            <th>Nombre<br><input type="text" class="form-control form-control-sm" placeholder="Filtrar..." onkeyup="filtrarColumna(this,'tablaEmpleados',1)" style="width:100%;"></th>
                                            <th>Cargo<br><select class="form-select form-select-sm" onchange="filtrarColumnaSelect(this,'tablaEmpleados',2)" style="width:100%;">
                                                <option value="">Todos</option>
                                                <?php foreach ($cargos as $c): ?>
                                                    <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select></th>
                                            <th>Teléfono<br><input type="text" class="form-control form-control-sm" placeholder="Filtrar..." onkeyup="filtrarColumna(this,'tablaEmpleados',3)" style="width:100%;"></th>
                                            <th>Estado<br><select class="form-select form-select-sm" onchange="filtrarColumnaSelect(this,'tablaEmpleados',4)" style="width:100%;">
                                                <option value="">Todos</option>
                                                <option value="activo">Activo</option>
                                                <option value="inactivo">Inactivo</option>
                                            </select></th>
                                            <th style="width:160px">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todosEmpleados as $emp): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($emp['cedula']) ?></td>
                                                <td><?= htmlspecialchars($emp['nombres'] . ' ' . $emp['apellidos']) ?></td>
                                                <td><?= htmlspecialchars($emp['cargo_nombre'] ?: '-') ?></td>
                                                <td><?= htmlspecialchars($emp['telefono'] ?: '-') ?></td>
                                                <td><span class="badge bg-<?= $emp['estado'] === 'activo' ? 'success' : 'secondary' ?>"><?= $emp['estado'] ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-success py-0 px-1" title="Editar"
                                                        data-bs-toggle="modal" data-bs-target="#modalEmpleado"
                                                        data-edit="true" data-id="<?= $emp['id'] ?>"
                                                        data-cedula="<?= htmlspecialchars($emp['cedula'], ENT_QUOTES) ?>"
                                                        data-nombres="<?= htmlspecialchars($emp['nombres'], ENT_QUOTES) ?>"
                                                        data-apellidos="<?= htmlspecialchars($emp['apellidos'], ENT_QUOTES) ?>"
                                                        data-cargo-id="<?= $emp['cargo_id'] ?>"
                                                        data-cargo-nombre="<?= htmlspecialchars($emp['cargo_nombre'] ?? '', ENT_QUOTES) ?>"
                                                        data-estado="<?= htmlspecialchars($emp['estado'] ?? 'activo', ENT_QUOTES) ?>"
                                                        data-telefono="<?= htmlspecialchars($emp['telefono'] ?? '', ENT_QUOTES) ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($emp['estado'] === 'activo'): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Inactivar este empleado?')">
                                                            <input type="hidden" name="action" value="inactivar_empleado">
                                                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning py-0 px-1" title="Inactivar"><i class="bi bi-person-x"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Activar este empleado?')">
                                                            <input type="hidden" name="action" value="activar_empleado">
                                                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success py-0 px-1" title="Activar"><i class="bi bi-person-check"></i></button>
                                                        </form>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente este empleado?')">
                                                            <input type="hidden" name="action" value="eliminar_empleado">
                                                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-between align-items-center px-2 py-1 border-top">
                                    <span class="pag-info text-muted small"></span>
                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaEmpleados',-1)">‹</button>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaEmpleados',1)">›</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== TAB PROVEEDORES ==================== -->
        <div class="tab-pane fade" id="proveedores-pane" role="tabpanel">
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0"><i class="bi bi-truck"></i> Proveedores</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalProveedor" data-edit="false">
                        <i class="bi bi-plus-lg"></i> Nuevo proveedor
                    </button>
                </div>
                <?php if (empty($todosProveedores)): ?>
                    <div class="alert alert-light">No hay proveedores registrados.</div>
                <?php else: ?>
                    <div class="mb-2 d-flex gap-2 align-items-center">
                        <input type="text" class="form-control form-control-sm" style="width:250px" placeholder="Buscar..." onkeyup="filtrarTabla(this, 'tablaProveedores')">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quitarFiltros('tablaProveedores')"><i class="bi bi-x-circle"></i> Quitar filtros</button>
                        <select class="form-select form-select-sm ms-auto" style="width:auto" onchange="cambiarPagina(this.value,'tablaProveedores')">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted small">por página</span>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0" id="tablaProveedores">
                                    <thead class="table-light">
                                        <tr>
                                            <th>NIT</th>
                                            <th>Nombre</th>
                                            <th>Teléfono</th>
                                            <th>Dirección</th>
                                            <th>Estado</th>
                                            <th style="width:160px">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todosProveedores as $prov): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($prov['nit']) ?></td>
                                                <td><?= htmlspecialchars($prov['nombre']) ?></td>
                                                <td><?= htmlspecialchars($prov['telefono'] ?: '-') ?></td>
                                                <td><?= htmlspecialchars($prov['direccion'] ?: '-') ?></td>
                                                <td><span class="badge bg-<?= $prov['estado'] === 'activo' ? 'success' : 'secondary' ?>"><?= $prov['estado'] ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-success py-0 px-1" title="Editar"
                                                        data-bs-toggle="modal" data-bs-target="#modalProveedor"
                                                        data-edit="true" data-id="<?= $prov['id'] ?>"
                                                        data-nit="<?= htmlspecialchars($prov['nit'], ENT_QUOTES) ?>"
                                                        data-nombre="<?= htmlspecialchars($prov['nombre'], ENT_QUOTES) ?>"
                                                        data-telefono="<?= htmlspecialchars($prov['telefono'] ?? '', ENT_QUOTES) ?>"
                                                        data-direccion="<?= htmlspecialchars($prov['direccion'] ?? '', ENT_QUOTES) ?>"
                                                        data-estado="<?= htmlspecialchars($prov['estado'] ?? 'activo', ENT_QUOTES) ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($prov['estado'] === 'activo'): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Inactivar este proveedor?')">
                                                            <input type="hidden" name="action" value="inactivar_proveedor">
                                                            <input type="hidden" name="id" value="<?= $prov['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning py-0 px-1" title="Inactivar"><i class="bi bi-building-x"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Activar este proveedor?')">
                                                            <input type="hidden" name="action" value="activar_proveedor">
                                                            <input type="hidden" name="id" value="<?= $prov['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success py-0 px-1" title="Activar"><i class="bi bi-building-check"></i></button>
                                                        </form>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente este proveedor?')">
                                                            <input type="hidden" name="action" value="eliminar_proveedor">
                                                            <input type="hidden" name="id" value="<?= $prov['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-between align-items-center px-2 py-1 border-top">
                                    <span class="pag-info text-muted small"></span>
                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaProveedores',-1)">‹</button>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaProveedores',1)">›</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== TAB CARGOS ==================== -->
        <div class="tab-pane fade" id="cargos-pane" role="tabpanel">
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0"><i class="bi bi-briefcase"></i> Cargos</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCargo" data-edit="false">
                        <i class="bi bi-plus-lg"></i> Nuevo cargo
                    </button>
                </div>
                <?php if (empty($todosCargos)): ?>
                    <div class="alert alert-light">No hay cargos registrados.</div>
                <?php else: ?>
                    <div class="mb-2 d-flex gap-2 align-items-center">
                        <input type="text" class="form-control form-control-sm" style="width:250px" placeholder="Buscar..." onkeyup="filtrarTabla(this, 'tablaCargos')">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quitarFiltros('tablaCargos')"><i class="bi bi-x-circle"></i> Quitar filtros</button>
                        <select class="form-select form-select-sm ms-auto" style="width:auto" onchange="cambiarPagina(this.value,'tablaCargos')">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted small">por página</span>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0" id="tablaCargos">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Estado</th>
                                            <th style="width:160px">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todosCargos as $cargo): ?>
                                            <tr>
                                                <td><?= $cargo['id'] ?></td>
                                                <td><?= htmlspecialchars($cargo['nombre']) ?></td>
                                                <td><span class="badge bg-<?= $cargo['estado'] === 'activo' ? 'success' : 'secondary' ?>"><?= $cargo['estado'] ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-success py-0 px-1" title="Editar"
                                                        data-bs-toggle="modal" data-bs-target="#modalCargo"
                                                        data-edit="true" data-id="<?= $cargo['id'] ?>"
                                                        data-nombre="<?= htmlspecialchars($cargo['nombre'], ENT_QUOTES) ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($cargo['estado'] === 'activo'): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Inactivar este cargo?')">
                                                            <input type="hidden" name="action" value="inactivar_cargo">
                                                            <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning py-0 px-1" title="Inactivar"><i class="bi bi-slash-circle"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Activar este cargo?')">
                                                            <input type="hidden" name="action" value="activar_cargo">
                                                            <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success py-0 px-1" title="Activar"><i class="bi bi-check-circle"></i></button>
                                                        </form>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente este cargo?')">
                                                            <input type="hidden" name="action" value="eliminar_cargo">
                                                            <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-between align-items-center px-2 py-1 border-top">
                                    <span class="pag-info text-muted small"></span>
                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaCargos',-1)">‹</button>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="irPagina('tablaCargos',1)">›</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== TAB FESTIVOS ==================== -->
        <div class="tab-pane fade" id="festivos-pane" role="tabpanel">
            <div class="mt-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-calendar-plus"></i> Agregar festivo</h5>
                        <form method="post" class="row g-3">
                            <input type="hidden" name="action" value="agregar_festivo">
                            <div class="col-md-4">
                                <label class="form-label">Nombre</label>
                                <input class="form-control" name="nombre_festivo" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha</label>
                                <input class="form-control" type="date" name="fecha_festivo" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100"><i class="bi bi-save"></i> Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-white"><i class="bi bi-calendar-event"></i> Festivos activos</div>
                    <div class="card-body p-0">
                        <?php if (empty($festivosActivos)): ?>
                            <p class="text-muted p-3 mb-0">No hay festivos registrados.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($festivosActivos as $festivo): ?>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span><?= htmlspecialchars($festivo['fecha']) ?> — <?= htmlspecialchars($festivo['nombre']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include APP_PATH . '/Views/modals/gasto.php'; ?>
<?php include APP_PATH . '/Views/modals/reintegro.php'; ?>
<?php include APP_PATH . '/Views/modals/empleado.php'; ?>
<?php include APP_PATH . '/Views/modals/proveedor.php'; ?>
<?php include APP_PATH . '/Views/modals/cargo.php'; ?>
