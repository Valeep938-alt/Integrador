<?php
require_once 'config.php';

$desde = $_GET['desde'] ?? '';
$hasta  = $_GET['hasta']  ?? '';
$tipo   = $_GET['tipo']   ?? '';

// Construir endpoint con filtros
$endpoint = '/registros?estado=FINALIZADO';
if ($desde) $endpoint .= '&desde=' . urlencode($desde);
if ($hasta)  $endpoint .= '&hasta='  . urlencode($hasta);
if ($tipo)   $endpoint .= '&tipo='   . urlencode($tipo);

$registros = apiRequest($endpoint);
$total = 0;
if (is_array($registros) && !isset($registros['error'])) {
    foreach ($registros as $r) {
        $total += $r['tarifa'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - Parqueadero Boyacá</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">🅿️ Parqueadero Boyacá</div>
        <nav>
            <a href="index.php" class="nav-link">🏠 Inicio</a>
            <a href="entrada.php" class="nav-link">⬇️ Registrar Entrada</a>
            <a href="historial.php" class="nav-link active">📋 Historial</a>
            <a href="vehiculos.php" class="nav-link">🚗 Vehículos</a>
            <a href="reportes.php" class="nav-link">📊 Reportes</a>
        </nav>
    </div>
</header>

<main class="container">

    <h1 class="titulo-pagina">📜 Historial de Salidas</h1>

    <!-- Formulario de filtros -->
    <section class="seccion">
        <div class="seccion-card seccion-verde">
            <h2>🔍 Filtrar registros</h2>
            <form method="GET" action="historial.php">
                <div class="form-inline">
                    <div class="campo">
                        <label class="campo">Fecha inicio</label>
                        <input type="date" name="desde" class="input-texto"
                               value="<?= htmlspecialchars($desde) ?>">
                    </div>
                    <div class="campo">
                        <label>Fecha fin</label>
                        <input type="date" name="hasta" class="input-texto"
                               value="<?= htmlspecialchars($hasta) ?>">
                    </div>
                    <div class="campo">
                        <label>Tipo de vehículo</label>
                        <select name="tipo" class="input-texto">
                            <option value="">-- Todos --</option>
                            <option value="CARRO"  <?= $tipo === 'CARRO'  ? 'selected' : '' ?>>Carro</option>
                            <option value="MOTO"   <?= $tipo === 'MOTO'   ? 'selected' : '' ?>>Moto</option>
                            <option value="CAMION" <?= $tipo === 'CAMION' ? 'selected' : '' ?>>Camión</option>
                        </select>
                    </div>
                    <div class="campo" style="justify-content:flex-end">
                        <label>&nbsp;</label>
                        <div style="display:flex;gap:0.5rem">
                            <button type="submit" class="btn btn-azul">🔍 Filtrar</button>
                            <a href="historial.php" class="btn btn-verde">✖ Limpiar</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Tabla de resultados -->
    <section class="seccion">
        <h2>📋 Registros encontrados</h2>

        <?php if (isset($registros['error'])): ?>
            <div class="alerta alerta-error">
                ❌ <?= htmlspecialchars($registros['error']) ?>
            </div>
        <?php elseif (empty($registros)): ?>
            <div class="mensaje-vacio">
                📭 No se encontraron registros con esos filtros.
                <a href="historial.php" class="enlace">Ver todos →</a>
            </div>
        <?php else: ?>
            <div class="tabla-container">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Placa</th>
                            <th>Tipo</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Tarifa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['id']) ?></td>
                            <td><strong class="placa"><?= htmlspecialchars($r['placa']) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= strtolower($r['tipo']) ?>">
                                    <?= htmlspecialchars($r['tipo']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r['entrada']) ?></td>
                            <td><?= htmlspecialchars($r['salida']) ?></td>
                            <td><strong>$<?= number_format($r['tarifa'], 0, ',', '.') ?> COP</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5"><strong>💰 Total acumulado</strong></td>
                            <td><strong>$<?= number_format($total, 0, ',', '.') ?> COP</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </section>

</main>

<footer class="footer">
    <p>SENA CIMM · ADSO 228118 · Regional Boyacá · <?= date('Y') ?></p>
    <p class="footer-tech">Frontend: <strong>PHP (Apache)</strong> → Backend: <strong>Java Servlets (Tomcat)</strong></p>
</footer>

<script src="js/app.js"></script>
</body>
</html>