<?php
// Obtenemos el usuario actual
 $current_user = wp_get_current_user();
 $user_id = $current_user->ID;

 ?>
<script>
console.log('Cargando página de competición');
</script>
<?php

// Mensajes de éxito o error
if (isset($_GET['competition_added']) && $_GET['competition_added'] == '1') {
    $success_message = "¡La competición se ha añadido correctamente!";
} elseif (isset($_GET['competition_updated']) && $_GET['competition_updated'] == '1') {
    $success_message = "¡La competición se ha actualizado correctamente!";
} elseif (isset($_GET['competition_deleted']) && $_GET['competition_deleted'] == '1') {
    $success_message = "¡La competición se ha eliminado correctamente!";
} elseif (isset($_GET['competition_error'])) {
    $error_message = "Hubo un error al realizar la acción. Por favor, inténtalo de nuevo.";
}

// Mensaje de error específico para la subida de archivos
if (isset($_GET['upload_error'])) {
    $upload_error_message = "Error al subir el archivo GPX: " . urldecode($_GET['upload_error']);
}

// Lógica para la edición: si hay un ID en la URL, obtenemos los datos de esa competición
 $editing_competition = null;
if (isset($_GET['edit'])) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mi_app_competiciones';
    $editing_competition = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND user_id = %d", intval($_GET['edit']), $user_id));
}

// Obtener todas las competiciones del usuario
global $wpdb;
 $table_name = $wpdb->prefix . 'mi_app_competiciones';
// CORRECCIÓN: Esta consulta incluye TODAS las columnas necesarias
 $competiciones = $wpdb->get_results(
    $wpdb->prepare("
        SELECT * FROM {$table_name}
        WHERE user_id = %d
        AND fecha >= CURDATE() 
        ORDER BY fecha ASC", 
        $user_id
    )
);

 $competiciones2 = $wpdb->get_results(
     $wpdb->prepare("
         SELECT c.id, c.nombre, c.fecha, c.distancia, c.desnivel, c.gpx_file_url, p.plan_data
         FROM {$table_name} c
         LEFT JOIN {$wpdb->prefix}mi_app_nutrition_plans p ON c.id = p.competition_id AND c.user_id = p.user_id
         WHERE c.user_id = %d
         ORDER BY c.fecha ASC",
         $user_id
    )
);

?>



<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Competicion - Carbo Cycling</title>
    <?php wp_head(); ?>
    <style>
        /* ==========================================================================
           ESTILOS GENERALES Y RESPONSIVOS PARA MI APP PLUGIN
           ========================================================================== */

        /* --- Reset y Configuración Base --- */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 1rem; /* Padding más pequeño para móviles */
            color: #333;
            line-height: 1.6;
        }

        /* --- Contenedor Principal --- */
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* --- Tipografía --- */
        h1, h2, h3 {
            color: #333;
            line-height: 1.2;
        }

        h1 { font-size: 2rem; }
        h2 { font-size: 1.5rem; margin-top: 2rem; }
        h3 { font-size: 1.2rem; }

        /* --- Botones y Enlaces de Acción --- */
        .button-action, .add-competition-btn, .submit-btn, .back-link {
            display: inline-block;
            padding: 0.6em 1.2em;
            margin: 0.2em;
            font-size: 1rem;
            line-height: 1.4;
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .add-competition-btn { background-color: #0073aa; color: white; border-color: #005a87; }
        .add-competition-btn:hover { background-color: #005a87; }

        .submit-btn { background-color: #28a745; color: white; border-color: #1e7e34; width: 100%; }
        .submit-btn:hover { background-color: #1e7e34; }

        .back-link { background-color: #6c757d; color: white; border-color: #545b62; }
        .back-link:hover { background-color: #545b62; }

        .button-action {
            background-color: #0073aa;
            color: #fff;
            border-color: #005a87;
            font-size: 0.9rem;
            padding: 0.4em 0.8em;
        }

        .button-action:hover { background-color: #005a87; }

        .view-map-button {
            background-color: #5cb85c:;
            border-color: #4cae4c;
        }

        .view-map-button:hover { background-color: #4cae4c; }

        .button-success-sm {
            background-color: #5cb85c;
            border-color: #4cae4c;
        }
        .button-success-sm:hover { background-color: #4cae4c; }

        .edit-link { background-color: #ffc107; color: #212529; border-color: #d39e00; }
        .edit-link:hover { background-color: #d39e00; }

        .delete-link { background-color: #dc3545; color: white; border-color: #bd2130; }
        .delete-link:hover { background-color: #bd2130; }


        /* --- Sección de Cabecera --- */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            flex-wrap: wrap; /* Permite que los elementos se envuelvan en pantallas pequeñas */
            gap: 1rem;
        }

        /* --- Mensajes de Éxito y Error --- */
        .success-message, .error-message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }


        /* --- Tablas (Solución Responsive) --- */
        .table-responsive {
            overflow-x: auto; /* Clave para hacer las tablas scrollables horizontalmente en móvil */
            -webkit-overflow-scrolling: touch; /* Scroll suave en iOS */
        }

        .competition-list table {
            width: 100%;
            min-width: 600px; /* Ancho mínimo para que la tabla no se rompa */
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .competition-list th, .competition-list td {
            padding: 12px 8px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }

        .competition-list th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        /* --- Celda de Acciones --- */
        .actions {
            white-space: nowrap; /* Evita que los botones se partan en líneas */
        }
        .actions .button-action {
            margin-right: 5px;
            margin-bottom: 5px;
        }


        /* --- Modal --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* Margen superior adaptable */
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 675px;
            border-radius: 8px;
            position: relative;
        }

        .close, .close-modal {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover, .close:focus, .close-modal:hover { color: black; }


        /* --- Formularios --- */
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px; /* Evita el zoom automático en iOS */
        }


        /* --- Loader y Errores --- */
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .error { color: #D63638; background-color: #fef7f7; border-left: 4px solid #D63638; padding: 10px; margin: 10px 0; }

        /* Estilos para la lista de progreso del análisis */
        .progress-list {
         list-style-type: none;
         padding: 0;
         margin: 0;
         }
        
        .progress-list li {
         background-color: #f0f0f0;
         border-left: 4px solid #0073aa;
         padding: 10px;
         margin-bottom: 5px;
         font-size: 0.9em;
         }

        /* ==========================================================================
           MEDIA QUERIES PARA RESPONSIVE DESIGN
           ========================================================================== */

        /* --- Tablets y Phablets (pantallas de 768px o menos) --- */
        @media (max-width: 768px) {
            /* Envuelve la tabla en un contenedor desplazable */
            .table-responsive-competiciones {
                overflow-x: auto;
                display: block;
                width: 100%;
                -webkit-overflow-scrolling: touch; 
            }

            /* Define un ancho mínimo para la tabla real para que no se rompa */
            .competitions-table {
                min-width: 600px; 
                font-size: 0.9rem;
            }

            /* Asegura que el contenido de las celdas no se parta */
            .competitions-table th, .competitions-table td {
                padding: 8px;
                white-space: nowrap; 
            }

            /* El contenedor principal del contenido del modal */
            .modal-content {
                width: 95%; /* Hace que el modal ocupe el 90% del ancho de la pantalla */
                margin: 10% auto; /* Ajusta el margen superior e inferior */
            }
            
            /* El cuerpo del formulario dentro del modal */
            .modal-body {
                padding: 0.85rem; /* Reduce el padding interno */
            }

            /* Los campos de formulario para evitar el desplazamiento */
            .form-group input[type="text"],
            .form-group select {
                width: 100%; /* Asegura que los inputs ocupen todo el ancho disponible del modal */
                box-sizing: border-box; /* Incluye el padding y border en el ancho total */
            }            

            body { padding: 0.5rem; }
            .page-container { padding: 1.5rem; }
            h1 { font-size: 1.75rem; }
            h2 { font-size: 1.4rem; }
            
            .header-section {
                flex-direction: column;
                align-items: stretch; /* Los botones ocupan todo el ancho */
                text-align: center;
            }
            
            .actions {
                display: flex;
                flex-direction: column;
                white-space: normal;
            }
            .actions .button-action {
                margin-bottom: 10px;
                text-align: center;
            }
        }

        /* --- Móviles (pantallas de 480px o menos) --- */
        @media (max-width: 480px) {
            /* Ajuste para el contenedor principal del dashboard */
            .dashboard-container {
                padding: 1rem;
                border-radius: 0;
                max-width: 100%; /* Asegura que ocupe el 100% del ancho en pantallas muy pequeñas */
            }            
            
            /* Si tienes botones de acción al pie del modal */
            .modal-footer {
                flex-direction: column; /* Apila los botones de guardar/cancelar verticalmente */
            }

            .page-container {
                padding: 1rem;
                border-radius: 0;
                box-shadow: none;
            }
            h1 { font-size: 1.5rem; }
            
            .modal-content {
                width: 97%;
                margin: 5% auto;
                margin-top: 5%; /* Lo sube un poco para dejar espacio para la interacción */
                padding: 15px;
            }
        }

        /* ==========================================================================
           ESTILOS PARA EL ANÁLISIS DE NUTRICIÓN (MODAL)
           ========================================================================== */

        .analysis-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.7;
            color: #333;
        }

        .analysis-container h2.analysis-h2 {
            color: #0073aa;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
            margin-top: 2.5em;
            margin-bottom: 1.5em;
            font-size: 1.5em;
        }

        .analysis-container h3.analysis-h3 {
            color: #555;
            margin-top: 2em;
            margin-bottom: 1em;
            font-size: 1.2em;
        }

        .analysis-container strong {
            color: #000;
        }

        .analysis-table {
            width: 95%;
            border-collapse: collapse;
            margin: 1.5em 0;
            font-size: 0.95em;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .analysis-table th,
        .analysis-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .analysis-table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #444;
            border-bottom: 2px solid #dee2e6;
        }

        .analysis-container ul.analysis-tips-list {
            list-style-type: none;
            padding-left: 0;
        }

        .analysis-container ul.analysis-tips-list li {
            background-color: #f8f9fa;
            border-left: 4px solid #0073aa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }

        .analysis-container ul.analysis-tips-list li::before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }

    </style>



    <!-- Carga de la librería GPXParser.js -->
    <script src="https://cdn.jsdelivr.net/gh/Luuka/GPXParser.js/dist/GPXParser.min.js"></script>
</head>
<body>
    <div class="page-container">
        <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a>

        <div class="header-section">
            <h1>Mis Competiciones</h1>
            <button id="addCompetitionBtn" class="add-competition-btn">+ Añadir Nueva</button>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo esc_html($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo esc_html($error_message); ?></div>
        <?php endif; ?>
        <?php if (isset($upload_error_message)): ?>
            <div class="error-message"><?php echo esc_html($upload_error_message); ?></div>
        <?php endif; ?>

        <div class="competition-list">
            <h2>Competiciones Guardadas</h2>
            <?php if ($competiciones): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th><center>Fecha</center></th>
                            <th>Distancia (Km)</th>
                            <th>Desnivel (Acum)</th>
                            <th><center>Desniveles</center></th>
                            <th><center>Informes</center></th>
                            <th><center>Acciones</center></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competiciones as $comp): ?>
                            <tr>
                                <td><?php echo esc_html($comp->nombre); ?></td>
                                <td><?php echo esc_html(date('d/m/Y', strtotime($comp->fecha))); ?></td>
                                <td><center><?php echo esc_html($comp->distancia); ?></center></td>
                                <td><center><?php echo esc_html($comp->desnivel); ?></center></td>
                                <td>
                                    <?php if (!empty($comp->gpx_name)): ?>
                                        <!--<strong><?php echo esc_html($comp->gpx_name); ?></strong><br>-->
                                        <small>Min: <?php echo esc_html(round($comp->gpx_min_elevation, 1)); ?>m | Max: <?php echo esc_html(round($comp->gpx_max_elevation, 1)); ?>m</small><br>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <!-- CORRECCIÓN: Usamos $comp en lugar de $competition -->
                                    <?php if (!empty($comp->gpx_file_url)) : ?>
                                        <div class="competition-actions" style="display: inline-block;">
                                            <a href="<?php echo esc_url(add_query_arg('competition_id', $comp->id, home_url('/mi-app/mapa/'))); ?>" class="button-action button-success-sm" title="Ver la ruta de la competición en el mapa.">Ver Ruta</a>

                                            <!-- El botón de "Análisis" se insertará aquí dinámicamente con JavaScript -->
                                            <button type="button" class="button-action button-success-sm analysis-button" data-competition-id="<?php echo $comp->id; ?>" title="Crea un analisis de la ruta con IA">Análisis</button>

                                            <?php 
                                            // *** NUEVA LÓGICA: Comprobar si existe un PLAN DE NUTRICIÓN ***
                                             $has_nutrition_plan = $wpdb->get_var($wpdb->prepare(
                                             "SELECT COUNT(*) FROM {$wpdb->prefix}mi_app_nutrition_plans
                                             WHERE competition_id = %d AND user_id = %d AND plan_type = %s",
                                             $comp->id,
                                             $user_id,
                                             'nutrition_plan'
                                             ));
                                             ?>

                                             <?php if ($has_nutrition_plan > 0): ?>
                                             <button type="button" class="button-action button-success-sm" data-competition-id="<?php echo $comp->id; ?>" title="Crea GPX de la ruta con puntos de nutrición según la IA">Generar GPX Nutrición</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php else : ?>
                                        <p><em>No se ha subido un archivo GPX.</em></p>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="?edit=<?php echo $comp->id; ?>" class="button-action edit-link" >Editar</a>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('delete_competition_action_' . $comp->id, 'delete_competition_nonce'); ?>
                                        <input type="hidden" name="action" value="delete_competition">
                                        <input type="hidden" name="competition_id" value="<?php echo $comp->id; ?>">
                                        <a href="#" class="button-action delete-link" onclick="if(confirm('¿Estás seguro de que quieres eliminar esta competición?')){ this.closest('form').submit(); } return false;" >Eliminar</a>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No tienes competiciones guardadas. ¡Añade una haciendo clic en el botón de arriba!</p>
            <?php endif; ?>
        </div>

        <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a>
    </div>

    <!-- El Modal para Añadir/Editar -->
    <div id="competitionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><?php echo $editing_competition ? 'Editar Competición' : 'Añadir Nueva Competición'; ?></h2>
            <form id="addCompetitionForm" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('add_competition_action', 'add_competition_nonce'); ?>
                <?php if ($editing_competition): ?>
                    <input type="hidden" name="competition_id" value="<?php echo $editing_competition->id; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nombre">Nombre de la competición</label>
                    <input type="text" name="nombre" id="nombre" value="<?php echo $editing_competition ? esc_attr($editing_competition->nombre) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="fecha">Fecha</label>
                    <input type="date" name="fecha" id="fecha" value="<?php echo $editing_competition ? esc_attr($editing_competition->fecha) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="distancia">Distancia (Km)</label>
                    <input type="number" step="0.001" name="distancia" id="distancia" value="<?php echo $editing_competition ? esc_attr($editing_competition->distancia) : ''; ?>" required>
                    <div id="gpx-distance-info" class="gpx-analysis-info"></div>
                </div>
                <div class="form-group">
                    <label for="desnivel">Desnivel (m)</label>
                    <input type="number" name="desnivel" id="desnivel" value="<?php echo $editing_competition ? esc_attr($editing_competition->desnivel) : ''; ?>" required>
                    <div id="gpx-elevation-info" class="gpx-analysis-info"></div>
                </div>

                <!-- Campos ocultos para min/max elevación -->
                <input type="hidden" name="gpx_min_elevation" id="gpx_min_elevation" value="">
                <input type="hidden" name="gpx_max_elevation" id="gpx_max_elevation" value="">

                <div class="form-group">
                    <label for="gpx_file">Archivo GPX (Opcional. Al seleccionar uno, se calculará la distancia y el desnivel automáticamente.)</label>
                    <input type="file" name="gpx_file" id="gpx_file" accept=".gpx" onchange="handleGpxFileSelect(event)">
                </div>
                <button type="submit" class="submit-btn"><?php echo $editing_competition ? 'Actualizar Competición' : 'Guardar Competición'; ?></button>
            </form>
        </div>
    </div>

    <script>
        // Script para manejar el modal
        var modal = document.getElementById("competitionModal");
        var btn = document.getElementById("addCompetitionBtn");
        var span = document.getElementsByClassName("close")[0];

        // Función para resetear el formulario y abrir el modal para "Añadir"
        function resetFormAndOpenModal() {
            document.getElementById("addCompetitionForm").reset();
            const hiddenId = document.querySelector("input[name='competition_id']");
            if (hiddenId) hiddenId.remove();
            document.querySelector("#competitionModal h2").innerText = "Añadir Nueva Competición";
            document.querySelector(".submit-btn").innerText = "Guardar Competición";
            document.getElementById('gpx-distance-info').textContent = '';
            document.getElementById('gpx-elevation-info').textContent = '';
            modal.style.display = "block";
        }

        btn.onclick = function() {
            resetFormAndOpenModal();
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Si se está editando, abrir el modal automáticamente al cargar la página
        <?php if ($editing_competition): ?>
            window.onload = function() {
                modal.style.display = "block";
            }
        <?php endif; ?>

        /**
         * Maneja la selección del archivo GPX, lo analiza y rellena los campos del formulario.
         */
        function handleGpxFileSelect(event) {
            const file = event.target.files[0];
            if (!file) {
                // Limpiar mensajes si se deselecciona el archivo
                document.getElementById('gpx-distance-info').textContent = '';
                document.getElementById('gpx-elevation-info').textContent = '';
                return;
            }

            const reader = new FileReader();
            const distanceInfoEl = document.getElementById('gpx-distance-info');
            const elevationInfoEl = document.getElementById('gpx-elevation-info');

            distanceInfoEl.textContent = 'Analizando archivo...';
            elevationInfoEl.textContent = 'Analizando archivo...';

            reader.onload = function(e) {
                const gpxData = e.target.result;
                try {
                    const gpx = new gpxParser();
                    gpx.parse(gpxData);

                    if (gpx.tracks.length > 0) {
                        const track = gpx.tracks[0];
                        
                        // 1. Procesar Distancia
                        if (track.distance && track.distance.total) {
                            const distanceKm = (track.distance.total / 1000).toFixed(3);
                            document.getElementById('distancia').value = distanceKm;
                            distanceInfoEl.textContent = `Distancia calculada desde GPX: ${distanceKm} km`;
                        } else {
                            distanceInfoEl.textContent = 'No se pudo calcular la distancia.';
                        }
                        
                        // 2. Procesar Desnivel (Ganancia Positiva)
                        if (track.elevation && track.elevation.pos !== undefined) {
                            const elevationGain = track.elevation.pos.toFixed(0);
                            document.getElementById('desnivel').value = elevationGain;
                        } else {
                            document.getElementById('desnivel').value = '';
                        }

                        // 3. Procesar y Guardar Elevación Mínima y Máxima en los campos ocultos
                        let minElev = null;
                        let maxElev = null;

                        if (track.elevation && track.elevation.min !== undefined && track.elevation.max !== undefined) {
                            minElev = track.elevation.min.toFixed(0);
                            maxElev = track.elevation.max.toFixed(0);
                        }
                        
                        // Guardamos los valores en los campos ocultos
                        document.getElementById('gpx_min_elevation').value = minElev;
                        document.getElementById('gpx_max_elevation').value = maxElev;

                        // Mostramos el desnivel positivo y los min/max juntos para el usuario
                        elevationInfoEl.innerHTML = `
                            <strong>Desnivel positivo:</strong> ${track.elevation && track.elevation.pos !== undefined ? track.elevation.pos.toFixed(0) + ' m' : 'N/A'}<br>
                            <strong>Elevación Mín/Max:</strong> ${minElev !== null && maxElev !== null ? `${minElev} m / ${maxElev} m` : 'N/A'}
                        `;

                    } else {
                        distanceInfoEl.textContent = 'El archivo GPX no contiene pistas válidas.';
                        elevationInfoEl.textContent = 'El archivo GPX no contiene pistas válidas.';
                    }
                } catch (error) {
                    console.error("Error al parsear GPX:", error);
                    distanceInfoEl.textContent = 'Error al procesar el archivo.';
                    elevationInfoEl.textContent = 'Error al procesar el archivo.';
                }
            };

            reader.onerror = function(e) {
                distanceInfoEl.textContent = 'Error al leer el archivo.';
                elevationInfoEl.textContent = 'Error al leer el archivo.';
            };

            reader.readAsText(file);
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Event delegation para manejar el clic en el botón de GPX
            document.querySelector('.page-container').addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('download-nutrition-gpx-btn')) {
                    e.preventDefault();

                    const competitionId = e.target.dataset.competitionId;
                    if (!competitionId) {
                        alert('Error: No se pudo identificar la competición.');
                        return;
                    }

                    const downloadBtn = e.target;
                    const originalText = downloadBtn.textContent;
                    downloadBtn.textContent = 'Generando GPX...';
                    downloadBtn.disabled = true;

                    const formData = new FormData();
                    formData.append('action', 'download_nutrition_gpx');
                    formData.append('competition_id', competitionId);
                    formData.append('nonce', '<?php echo wp_create_nonce('download_nutrition_gpx_nonce'); ?>');

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al generar el archivo GPX.');
                        }
                        return response.blob();
                    })
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'plan-nutricion.gpx';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Hubo un error al descargar el GPX. Por favor, inténtalo de nuevo.');
                    })
                    .finally(() => {
                        downloadBtn.textContent = originalText;
                        downloadBtn.disabled = false;
                    });
                }
            });
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Define las variables necesarias para la conexión AJAX/SSE
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const sseNonce = '<?php echo wp_create_nonce('analyze_gpx_sse_nonce'); ?>';

        // 2. Usamos event delegation para manejar el clic en cualquier botón con la clase 'analysis-button'
        document.querySelector('.page-container').addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('analysis-button')) {
                e.preventDefault();

                const competitionId = e.target.dataset.competitionId;
                if (!competitionId) {
                    alert('Error: No se pudo identificar el ID de la competición.');
                    return;
                }
                
                const analysisResultsDiv = document.getElementById('analysis-results');
                const analysisModal = document.getElementById('analysis-modal');
                
                analysisResultsDiv.innerHTML = '<p>Iniciando análisis...</p>';
                analysisModal.style.display = 'block';

                // 3. Creamos la URL de forma segura usando las variables definidas
                const eventSourceUrl = `${ajaxurl}?action=analyze_gpx_sse&competition_id=${competitionId}&nonce=${encodeURIComponent(sseNonce)}`;
                console.log('Conectando a SSE en:', eventSourceUrl); // Log para depuración

                const eventSource = new EventSource(eventSourceUrl);

                eventSource.onmessage = function(event) {
                     const data = JSON.parse(event.data);

                     if (data.status === 'complete') {
                     // El análisis ha terminado, mostramos el resultado final y limpiamos el historial
                     analysisResultsDiv.innerHTML = data.html;
                     eventSource.close();
                     } else if (data.status === 'error') {
                     // Si hay un error, lo mostramos y limpiamos el historial
                     analysisResultsDiv.innerHTML = `<div class="error"><p>Error: ${data.message}</p></div>`;
                     eventSource.close();
                     } else {
                     // Es un mensaje de progreso, lo añadimos a una lista
                     // Si no existe la lista, la creamos
                     if (!analysisResultsDiv.querySelector('.progress-list')) {
                     analysisResultsDiv.innerHTML = '<ul class="progress-list"></ul>';
                     }
                     const list = analysisResultsDiv.querySelector('.progress-list');
                     const listItem = document.createElement('li');
                     listItem.textContent = data.message;
                     list.appendChild(listItem);
                     }
                 };

                eventSource.onerror = function(err) {
                    console.error("Error en EventSource:", err);
                    analysisResultsDiv.innerHTML = '<div class="error"><p>Error de conexión con el servidor. Por favor, inténtalo de nuevo.</p></div>';
                    eventSource.close();
                };
            }
        });

        // Función para cerrar el modal de análisis
        function closeAnalysisModal() {
            document.getElementById('analysis-modal').style.display = 'none';
        }

        // Event listener para el botón de cerrar (X)
        document.querySelector('.close-modal').addEventListener('click', closeAnalysisModal);

        // Event listener para cerrar el modal si se hace clic fuera de él
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('analysis-modal');
            if (event.target === modal) {
                closeAnalysisModal();
            }
        });
    });
    </script>

    <!-- Modal para mostrar los resultados del análisis -->
    <div id="analysis-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Análisis de la Ruta</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div id="analysis-results">
                <!-- Aquí se mostrarán los resultados del análisis -->
            </div>
        </div>
    </div>


    <?php wp_footer(); ?>
</body>
</html>