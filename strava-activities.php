<?php
// Obtenemos el usuario actual
 $current_user = wp_get_current_user();
 $user_id = $current_user->ID;

// Llamamos a nuestra función para obtener las actividades
 $activities = $this->get_strava_activities($user_id, 30);
 $is_strava_connected = !is_wp_error($activities) || !empty($activities);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Strava - Carbo Cycling</title>
    <?php wp_head(); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --strava-orange: #fc4c02; --dark-bg: #2c2c2c; --light-gray: #f4f4f9; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--light-gray); margin: 0; padding: 20px; }
        
        .page-container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        /* Header */
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--strava-orange); padding-bottom: 10px; }
        h1 { color: #333; margin: 0; font-size: 1.5rem; }
        .stat-pill { background: #333; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; }
        .back-link { display: inline-block; margin-bottom: 2rem; padding: 0.5rem 1rem; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; }

        /* Grid System */
        .dashboard-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #555; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }
        .col-full { grid-column: span 12; }
        .col-half { grid-column: span 6; }
        @media (max-width: 768px) { .col-half { grid-column: span 12; } }
        canvas { max-height: 350px; }
        
        /* Tabla de Actividades */
        .activities-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .activities-table th, .activities-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .activities-table th { background-color: #f2f2f2; font-weight: bold; }
        .activity-type { font-weight: bold; text-transform: capitalize; }
        .error-message { background-color: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 1rem; text-align: center; }


        /* ==========================================================================
           ESTILOS RESPONSIVOS PARA MÓVILES Y TABLETS
           ========================================================================== */

        /* --- Tablets y Móviles (pantallas de 768px o menos) --- */
        @media (max-width: 768px) {

            /* --- Contenedor Principal para Móvil --- */
            .mi-app-container {
                padding: 1rem; /* Reducimos el padding para usar más ancho de pantalla */
            }

            /* --- Gráfico de Fitness, Fatiga & Forma (PMC) --- */
            .dashboard-grid .col-half:first-child {
                width: 100%; /* Ocupa todo el ancho en móvil */
                margin-bottom: 20px;
            }

            /* --- Contenedor de los otros dos gráficos (PDC y Zonas) --- */
            .dashboard-grid .col-half:last-child {
                width: 100%; /* Ocupa todo el ancho en móvil */
                display: flex;
                flex-direction: column; /* <-- CLAVE: Apilamos los elementos verticalmente */
            }

            /* --- Gráficos Individuales (PDC y Zonas) --- */
            .dashboard-grid .col-half:last-child .chart-container {
                width: 100%; /* Cada gráfico ocupa todo el ancho disponible */
                margin-bottom: 20px; /* Añadimos espacio entre los gráficos apilados */
            }

        }

    </style>
</head>
<body>
    <div class="page-container">
        <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a>
        
        <header>
            <h1>🚴 Análisis de Rendimiento</h1>
            <div class="stat-pill">FTP: <?php echo esc_html(get_user_meta($user_id, 'mi_app_ftp', true) ?: 'N/A'); ?>w</div>
        </header>

        <?php if (is_wp_error($activities)): ?>
            <div class="error-message">
                <p>Error al obtener las actividades de Strava:</p>
                <p>Vaya a la página de Perfil y realice la Conexión con Strava<p>
                <p><strong><?php echo esc_html($activities->get_error_message()); ?></strong></p>
            </div>
        <?php elseif (empty($activities)): ?>
            <p>No se encontraron actividades en el último año o tu cuenta de Strava está privada.</p>
        <?php else: ?>
            <div class="dashboard-grid">
                <!-- Gráfico 1: PMC (Fitness & Freshness) -->
                <div class="card col-full">
                    <h4>Fitness, Fatiga & Forma (PMC)</h4>
                    <div style="position: relative; height:40vh; width:100%;">
                        <canvas id="chartPMC"></canvas>
                    </div>
                    <p style="font-size: 1rem; color: #666; text-align: center; margin-top:10px;">
                        La línea azul es tu base (Fitness). Es una media móvil ponderada exponencial de 42 días de carga de entrenamiento.<br>La rosa es tu cansancio actual. Es una media móvil ponderada exponencial de 7 días de tu carga de entrenamiento.<br>
                        Las barras indican tu forma para competir. Es tu aptitud menos la fatiga. Para estar más en forma hay que crear estrés aumentando la carga de entrenamiento (fatiga superior al fitness), lo que da lugar a una forma negativa.
                    </p>
                </div>

                <!-- Gráfico 2: Curva de Potencia -->
                <div class="card col-half">
                    <h4>Curva de Potencia Máxima</h4><h3>muestra la relación entre duración del esfuerzo y potencia máxima sostenida durante ese tiempo</h3>
                    <div style="position: relative; height:40vh; width:100%;">
                        <canvas id="chartPDC"></canvas>
                    </div>
                    <p style="font-size: 1rem; color: #666; text-align: center; margin-top:10px;">* Pico Alto en Duraciones Cortas (5s - 30s): Indica una gran capacidad anaeróbica y de sprint.<br>
                    * Pico Alto en Duraciones Medias (1m - 5m): Indica una excelente capacidad de VO2 Máx (máximo consumo de oxígeno).<br>
                    * Valor Alto en Duraciones Largas (20m - 1h): Indica un alto Umbral de Potencia Funcional (FTP) y gran resistencia.
                </p>
                </div>

                <!-- Gráfico 3: Tiempo en Zonas -->
                <div class="card col-half">
                    <h4>Distribución de Potencia Media de cada actividad por zona (Últimas 30 actividades)</h4>
                    <div style="position: relative; height:40vh; width:100%;">
                        <canvas id="chartZones"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabla de actividades -->
            <div class="card col-full">
                <h3>Detalle de Actividades Recientes</h3>
                <div class="table-responsive">
                    <table class="activities-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Distancia (km)</th>
                                <th>Tiempo</th>
                                <th>Desnivel (m)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo esc_html($activity->name); ?></td>
                                    <td><?php echo esc_html(date('d/m/Y H:i', strtotime($activity->start_date_local))); ?></td>
                                    <td><span class="activity-type"><?php echo esc_html($activity->type); ?></span></td>
                                    <td><?php echo number_format($activity->distance / 1000, 2); ?></td>
                                    <td><?php echo esc_html(gmdate('H:i:s', $activity->moving_time)); ?></td>
                                    <td><?php echo esc_html($activity->total_elevation_gain); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!is_wp_error($activities) && !empty($activities)): ?>
            <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- 1. GENERAR LOS NONCES DE SEGURIDAD EN PHP ---
            // Esto debe hacerse en el ámbito principal, no dentro de una función.
            const pmcNonce = '<?php echo wp_create_nonce('get_pmc_chart_data_nonce'); ?>';
            const pdcNonce = '<?php echo wp_create_nonce('get_pdc_chart_data_nonce'); ?>';
            const zonesNonce = '<?php echo wp_create_nonce('get_zones_chart_data_nonce'); ?>';

            // --- 2. OBTENER LOS CONTEXTOS DE LOS CANVAS ---
            const pmcCtx = document.getElementById('chartPMC').getContext('2d');
            const pdcCtx = document.getElementById('chartPDC').getContext('2d');
            const zonesCtx = document.getElementById('chartZones').getContext('2d');

            // --- 3. LLAMAR A LAS FUNCIONES PARA CADA GRÁFICO ---
            // Ahora pasamos el nonce correcto a cada llamada.
            fetchChartData('get_pmc_chart_data', pmcNonce, pmcCtx, (data) => {
                renderPMCChart(pmcCtx, data.fechas, data.ctl, data.atl, data.tsb);
            });

            fetchChartData('get_pdc_chart_data', pdcNonce, pdcCtx, (data) => {
                //renderPDCChart(pdcCtx, data.labels, data.current, data.best);
                renderPDCChart(pdcCtx, data.labels, data.watts.current, data.watts.best_historical);
            });

            fetchChartData('get_zones_chart_data', zonesNonce, zonesCtx, (data) => {
                renderZonesChart(zonesCtx, data.labels, data.total_minutes, data.data_percentage);
            });
        });

        /**
         * Función genérica para obtener datos y renderizar un gráfico.
         * Ahora recibe el nonce como un parámetro.
         */
        function fetchChartData(action, nonce, ctx, renderCallback) {
            ctx.canvas.parentNode.style.opacity = '0.5'; // Indicador de carga

            const formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', nonce); // Usamos el nonce que pasamos a la función

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                ctx.canvas.parentNode.style.opacity = '1';
                if (response.success) {
                    renderCallback(response.data);
                } else {
                    // Mostramos el error del servidor para una mejor depuración
                    ctx.canvas.parentNode.innerHTML = `<p>Error: ${response.data}</p>`;
                }
            })
            .catch(error => {
                console.error(`Error al cargar datos para ${action}:`, error);
                ctx.canvas.parentNode.innerHTML = '<p>Error al cargar los datos del gráfico. Revisa la consola para más detalles.</p>';
            });
        }

        /**
         * Renderiza el gráfico PMC.
         */
        function renderPMCChart(ctx, fechas, ctl, atl, tsb) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: fechas,
                    datasets: [
                        { label: 'Fitness (CTL)', data: ctl, borderColor: '#007bff', backgroundColor: 'rgba(0, 123, 255, 0.1)', fill: true, yAxisID: 'y', tension: 0.4, pointRadius: 0 },
                        { label: 'Fatiga (ATL)', data: atl, borderColor: '#e83e8c', borderDash: [5, 5], yAxisID: 'y', tension: 0.4, pointRadius: 0 },
                        { label: 'Forma (TSB)', type: 'bar', data: tsb, backgroundColor: (c) => c.raw >= 0 ? 'rgba(40, 167, 69, 0.5)' : 'rgba(255, 193, 7, 0.5)', yAxisID: 'yB', barPercentage: 0.5 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { title: { display: true, text: 'Carga (TSS)' }, position: 'left' },
                        yB: { title: { display: true, text: 'Forma' }, position: 'right', grid: { drawOnChartArea: false } }
                    }
                }
            });
        }

        /**
         * Renderiza el gráfico de Curva de Potencia.
         */
        function renderPDCChart(ctx, labels, current, best) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Actual (6 sem)', data: current, borderColor: '#fc4c02', backgroundColor: '#fc4c02', borderWidth: 2, tension: 0.2 },
                        { label: 'Mejor Histórico', data: best, borderColor: '#adb5bd', borderDash: [5, 5], borderWidth: 2, pointRadius: 0, tension: 0.2 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: { title: { display: true, text: 'Duración' } },
                        y: { title: { display: true, text: 'Vatios (W)' } }
                    }
                }
            });
        }

        /**
         * Renderiza el gráfico de Zonas.
         */
        function renderZonesChart(ctx, labels, totalMinutes, percentages) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: `Tiempo Total: ${totalMinutes} min`,
                        data: percentages,
                        backgroundColor: [
                            '#cfd8dc', '#90caf9', '#ffcc80', '#ffab91', '#ff8a65', '#e53935'
                        ],
                        //borderRadius: 4
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            min: 0, // Asegúrate de que el mínimo es 0
                            max: 100, // Fuerza el máximo a 100%
                            title: {
                                display: true,
                                text: 'Porcentaje de Tiempo (%)'
                                }
                            }
                        }
                    }
            });
        }
    </script>
    <?php endif; ?>

    <?php wp_footer(); ?>
</body>
</html>