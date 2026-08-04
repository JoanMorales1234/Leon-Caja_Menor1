<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>CAJA <?= $tipoMay ?> — <?= $dia ?> DE <?= $nomMes ?> <?= $anio ?></title>
    <style>
        @page { margin: 0; size: letter landscape; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8.5pt; color: #000; padding: 1.5cm; }
        .header { margin-bottom: 20px; display: flex; align-items: center; justify-content: center; position: relative; min-height: 70px; }
        .header-logo { position: absolute; left: 0; top: 50%; transform: translateY(-50%); }
        .header-logo img { max-width: 200px; max-height: 180px; display: block; filter: brightness(0.6) contrast(1.3); }
        .header-text { text-align: center; flex: 1; }
        .header-text h1 { font-family: 'Courier New', Courier, monospace; font-size: 38pt; font-weight: bold; letter-spacing: 2px; margin-bottom: 1px; }
        .header-text h2 { font-family: 'Courier New', Courier, monospace; font-size: 11pt; font-weight: normal; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; font-size: <?= $fontSize ?>pt; }
        th { border: 1px solid #000; padding: 3px 4px; text-align: center; font-weight: bold; background: #e0e0e0; font-family: Tahoma, sans-serif; font-size: <?= min($fontSize + 1.5, 8) ?>pt; text-transform: uppercase; }
        td { border: 1px solid #000; padding: 2px 4px; vertical-align: middle; font-family: Arial, sans-serif; font-size: <?= $fontSize ?>pt; white-space: nowrap; }
        .num { text-align: right; }
        .fecha { text-align: center; }
        .cedula { text-align: center; }
        .col-valor { text-align: right; font-size: 9pt; }
        th.col-valor { text-align: center; }
        .col-valor .simbolo { float: left; }
        .saldo-anterior td { font-weight: bold; background: #D9E2F3; font-family: Arial, sans-serif; font-size: 10pt; padding: 5px 5px; vertical-align: middle; }
        .reintegro-row td { font-weight: bold; background: #E2EFDA; color: #006100; font-family: Arial, sans-serif; font-size: 10pt; padding: 5px 5px; vertical-align: middle; }
        .saldo-actual td { font-weight: bold; background: #D9E2F3; font-size: 10pt; font-family: Arial, sans-serif; padding: 5px 5px; vertical-align: middle; }
        .total-gastos td { font-weight: bold; background: #FCE4D6; font-family: Arial, sans-serif; font-size: 9pt; padding: 5px 5px; vertical-align: middle; }
        .nuevo-saldo td { font-weight: bold; background: #E2EFDA; font-size: 10pt; font-family: Arial, sans-serif; padding: 5px 5px; vertical-align: middle; }
        .nota-caja { margin-top: 10px; padding: 6px 8px; border: 1px solid #000; font-size: 8pt; font-family: Arial, sans-serif; }
        .nota-caja .label { font-weight: bold; margin-bottom: 2px; }
        .footer { margin-top: 20px; text-align: center; font-size: 8pt; font-family: Arial, sans-serif; }
        .footer .firma { display: inline-block; margin: 0 20px; text-align: center; }
        .footer .firma .linea { border-top: 1px solid #000; width: 160px; margin-top: 35px; padding-top: 5px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-page { padding: 1.5cm; page-break-after: always; }
            .print-page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;text-align:center">
        <button onclick="window.print()" style="padding:8px 24px;font-size:14px;cursor:pointer;background:#0d6efd;color:#fff;border:none;border-radius:4px;">&#128424; Imprimir</button>
        <button onclick="window.close()" style="padding:8px 24px;font-size:14px;cursor:pointer;background:#6c757d;color:#fff;border:none;border-radius:4px;margin-left:8px;">Cerrar</button>
    </div>

    <?php foreach ($gastosChunks as $pagIdx => $chunk):
        $esPrimera = ($pagIdx === 0);
        $esUltima = ($pagIdx === $totalPaginas - 1);
    ?>
    <div class="print-page">
        <div class="header">
            <?php if ($tieneLogo): ?>
                <div class="header-logo">
                    <img src="<?= asset('uploads/logo.png') ?>?t=<?= time() ?>" alt="Logo">
                </div>
            <?php endif; ?>
            <div class="header-text">
                <h1>CAJA <?= $tipoMay ?></h1>
                <h2><?= $dia ?> DE <?= $nomMes ?> <?= $anio ?></h2>
            </div>
        </div>

        <table>
            <?php if ($esPrimera): ?>
            <tr class="saldo-anterior">
                <td colspan="7" style="text-align:right;">SALDO ANTERIOR</td>
                <td class="col-valor"><span class="simbolo">$</span><?= number_format($saldoAnterior, 2, ',', '.') ?></td>
            </tr>
            <?php if ($totalReintegros > 0): ?>
            <tr class="reintegro-row">
                <td colspan="7" style="text-align:right;">REINTEGRO</td>
                <td class="col-valor"><span class="simbolo">$</span><?= number_format($totalReintegros, 2, ',', '.') ?></td>
            </tr>
            <tr class="saldo-actual">
                <td colspan="7" style="text-align:right;">SALDO ACTUAL</td>
                <td class="col-valor"><span class="simbolo">$</span><?= number_format($saldoActual, 2, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
            <tr>
                <th class="fecha">FECHA</th>
                <th class="cedula">CEDULA(NIT)</th>
                <th class="col-nombre">NOMBRE</th>
                <th class="col-func">FUNCIONARIO</th>
                <th class="col-desc">DESCRIPCION O DETALLE</th>
                <th class="col-nit">NIT</th>
                <th class="col-prov">PROVEEDOR</th>
                <th class="col-valor">VALOR</th>
            </tr>
            <?php if (empty($gastosData)): ?>
                <tr><td colspan="8" style="text-align:center;font-style:italic;">Sin movimientos</td></tr>
            <?php else:
                foreach ($chunk as $g):
                    $cedula = $g['cedula'] ?? '';
                    $nombre = trim(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? ''));
                    $cargo = $g['cargo'] ?? '';
                    $desc = $g['descripcion'] ?? '';
                    $provNit = $g['proveedor_nit'] ?? '';
                    $provNombre = $g['proveedor_nombre'] ?? '';
            ?>
                <tr>
                    <td class="fecha"><?= date('j/n/Y', strtotime($g['fecha_gasto'])) ?></td>
                    <td class="cedula"><?= htmlspecialchars($cedula) ?></td>
                    <td class="col-nombre"><?= htmlspecialchars($nombre) ?></td>
                    <td class="col-func"><?= htmlspecialchars($cargo) ?></td>
                    <td class="col-desc"><?= htmlspecialchars($desc) ?></td>
                    <td class="col-nit cedula"><?= htmlspecialchars($provNit) ?></td>
                    <td class="col-prov"><?= htmlspecialchars($provNombre) ?></td>
                    <td class="col-valor"><span class="simbolo">$</span><?= number_format($g['valor'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($esUltima): ?>
            <tr class="total-gastos">
                <td colspan="7" style="text-align:right;">TOTAL GASTOS</td>
                <td class="col-valor"><span class="simbolo">$</span><?= number_format($totalGastos, 2, ',', '.') ?></td>
            </tr>
            <tr class="nuevo-saldo">
                <td colspan="7" style="text-align:right;">NUEVO SALDO</td>
                <td class="col-valor"><span class="simbolo">$</span><?= number_format($nuevoSaldo, 2, ',', '.') ?></td>
            </tr>
            <?php else: ?>
            <tr class="continua-row">
                <td colspan="8" style="text-align:center;font-style:italic;padding:6px;font-size:8pt;">CONTINÚA EN LA SIGUIENTE PÁGINA</td>
            </tr>
            <?php endif; ?>
        </table>

        <?php if ($esUltima): ?>
            <?php if ($notaGuardada): ?>
            <div class="nota-caja">
                <div class="label">NOTA:</div>
                <div><?= nl2br(htmlspecialchars($notaGuardada)) ?></div>
            </div>
            <?php endif; ?>
            <div class="footer">
                <div style="border-top:1px solid #000;margin:15px 0 8px;"></div>
                <div class="firma">
                    <div class="linea">FIRMA RESPONSABLE</div>
                </div>
                <div class="firma">
                    <div class="linea">FIRMA AUTORIZA</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="no-print" style="margin-top:12px;padding:10px;background:#f8f9fa;border:1px solid #ddd;border-radius:4px;">
        <form method="post" style="display:flex;gap:6px;align-items:flex-start;">
            <textarea name="nota_caja" rows="2" style="flex:1;padding:6px;font-size:13px;border:1px solid #ccc;border-radius:4px;" placeholder="Escribe una nota general para esta caja..."><?= htmlspecialchars($notaGuardada) ?></textarea>
            <button type="submit" name="guardar_nota" style="padding:6px 16px;font-size:13px;cursor:pointer;background:#198754;color:#fff;border:none;border-radius:4px;white-space:nowrap;">Guardar nota</button>
            <?php if ($notaGuardada): ?>
            <button type="submit" name="eliminar_nota" style="padding:6px 16px;font-size:13px;cursor:pointer;background:#dc3545;color:#fff;border:none;border-radius:4px;white-space:nowrap;" onclick="return confirm('Eliminar la nota?')">Eliminar nota</button>
            <?php endif; ?>
        </form>
    </div>

    <script>
        window.onload = function() {
            var printBtn = document.querySelector('.no-print');
            if (printBtn && window.print) {
                setTimeout(function() { window.print(); }, 500);
            }
        };
    </script>
</body>
</html>
