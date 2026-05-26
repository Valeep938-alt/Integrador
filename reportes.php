<?php
require_once 'config.php';
$reporte  = apiRequest('/registros/reporte');
$total    = $reporte['totalDia']        ?? 0;
$cantidad = $reporte['cantidadSalidas'] ?? 0;
$fecha    = $reporte['fecha']           ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte del Día - Parqueadero Boyacá</title>
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
            <a href="historial.php" class="nav-link">📋 Historial</a>
            <a href="vehiculos.php" class="nav-link">🚗 Vehículos</a>
            <a href="reportes.php" class="nav-link active">📊 Reportes</a>
        </nav>
    </div>
</header>

<main class="container">

    <h1 class="titulo-pagina">📊 Reporte de Ingresos del Día</h1>

    <?php if (isset($reporte['error'])): ?>
        <div class="alerta alerta-error">
            ❌ No se pudo conectar con el servidor Java: <?= htmlspecialchars($reporte['error']) ?>
        </div>
    <?php else: ?>

    <!-- Tarjetas de resumen -->
    <div class="tarjetas-grid">
        <div class="tarjeta tarjeta-verde">
            <div class="tarjeta-icono">💰</div>
            <div class="tarjeta-numero">$<?= number_format($total, 0, ',', '.') ?></div>
            <div class="tarjeta-label">Total recaudado hoy (COP)</div>
        </div>
        <div class="tarjeta tarjeta-azul">
            <div class="tarjeta-icono">🚗</div>
            <div class="tarjeta-numero"><?= $cantidad ?></div>
            <div class="tarjeta-label">Vehículos atendidos hoy</div>
        </div>
        <div class="tarjeta tarjeta-naranja">
            <div class="tarjeta-icono">📅</div>
            <div class="tarjeta-numero"><?= date('d/m/Y', strtotime($fecha)) ?></div>
            <div class="tarjeta-label">Fecha del reporte</div>
        </div>
    </div>

    <!-- Tabla resumen -->
    <section class="seccion">
        <h2>📈 Resumen estadístico</h2>
        <div class="tabla-container">
            <table class="tabla">
                <thead>
                    <tr><th>Indicador</th><th>Valor</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>💰 Total recaudado hoy</td>
                        <td><strong>$<?= number_format($total, 0, ',', '.') ?> COP</strong></td>
                    </tr>
                    <tr>
                        <td>🚗 Vehículos atendidos</td>
                        <td><?= $cantidad ?></td>
                    </tr>
                    <tr>
                        <td>📊 Promedio por vehículo</td>
                        <td>
                            <?php if ($cantidad > 0): ?>
                                $<?= number_format($total / $cantidad, 0, ',', '.') ?> COP
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>📅 Fecha</td>
                        <td><?= date('d/m/Y', strtotime($fecha)) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if ($cantidad === 0): ?>
        <div class="mensaje-vacio" style="margin-top:1rem">
            📭 No hay vehículos atendidos hoy todavía.
            <a href="entrada.php" class="enlace">Registrar primera entrada →</a>
        </div>
        <?php endif; ?>
    </section>

    <?php endif; ?>

</main>

<footer class="footer">
    <p>SENA CIMM · ADSO 228118 · Regional Boyacá · <?= date('Y') ?></p>
    <p class="footer-tech">Frontend: <strong>PHP (Apache)</strong> → Backend: <strong>Java Servlets (Tomcat)</strong></p>
</footer>

<script src="js/app.js"></script>
</body>
</html>