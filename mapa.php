<?php
// Obtenemos el ID de la competición desde la URL
 $competition_id = intval(get_query_var('competition_id'));
 $current_user = wp_get_current_user();
 $user_id = $current_user->ID;

// Verificamos que el ID sea válido
if (!$competition_id) {
    wp_die('ID de competición no especificado.');
}

// Obtenemos los datos de la competición desde la base de datos
global $wpdb;
 $table_name = $wpdb->prefix . 'mi_app_competiciones';
 $competition = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND user_id = %d", $competition_id, $user_id)
);

// Verificamos que la competición exista y pertenezca al usuario
if (!$competition || !$competition->gpx_file_url) {
    wp_die('Competición no encontrada o no tiene un archivo GPX asociado.');
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Mapa de <?php echo esc_html($competition->nombre); ?> - Carbo Cycling</title>
    <?php wp_head(); ?>
    
    <!-- CSS de Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        /* Aseguramos que el mapa ocupe toda la altura disponible */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        #map {
            height: 100%;
            width: 100%;
        }
        .info-panel {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-family: sans-serif;
        }

        /* ==========================================================================
           ESTILOS RESPONSIVOS PARA MÓVILES Y TABLETS
           ========================================================================== */

        /* --- Estilos Generales y Mobile-First --- */
        * {
            box-sizing: border-box;
        }

        /* Ajustes para Tablets (pantallas de 768px o menos) */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .page-container {
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.4rem;
            }

            h2 {
                font-size: 1.2rem;
            }

            h3 {
                font-size: 1.1rem;
            }

            .stat-pill {
                font-size: 0.8rem;
                padding: 4px 12px;
            }

            /* --- Grid System (para strava-activities.php) --- */
            .dashboard-grid {
                grid-template-columns: 1fr; /* Apila las columnas verticalmente */
                gap: 15px;
            }
            .col-half {
                grid-column: span 1; /* Asegura que las medias columnas ocupen todo el ancho */
            }

            /* --- Tablas de Actividades --- */
            /* La mejor solución para tablas complejas en móvil es hacerlas scrollables horizontalmente */
            .table-responsive {
                overflow-x: auto;
                display: block;
                width: 100%;
                -webkit-overflow-scrolling: touch; /* Para un scroll suave en iOS */
            }
            
            .activities-table {
                min-width: 600px; /* Define un ancho mínimo para que la tabla no se rompa */
                font-size: 0.9rem;
            }
            
            .activities-table th, .activities-table td {
                padding: 8px;
                white-space: nowrap; /* Evita que el texto se parta en varias líneas */
            }

            /* --- Formularios (para recetas.php, perfil.php, competicion.php) --- */
            .form-group label {
                margin-bottom: 0.4rem;
            }

            .form-group select,
            .form-group input[type="text"],
            .form-group input[type="number"],
            .form-group input[type="password"],
            .form-group input[type="email"],
            .form-group input[type="date"] {
                padding: 0.6rem;
                font-size: 16px; /* Evita el zoom automático en iOS */
            }

            .generate-btn, .button {
                padding: 0.8rem 1rem;
                font-size: 1rem;
            }

            /* --- Tarjetas de Planes y Recetas (para recetas.php) --- */
            .plan-card, .meal-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .meal-stats {
                font-size: 0.75em;
                flex-wrap: wrap; /* Permite que los spans se separen en varias líneas */
            }
            
            .meal-stats span {
                margin-right: 10px;
                margin-bottom: 5px;
            }
        }

        /* --- Ajustes para Móviles (pantallas de 480px o menos) --- */
        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }

            /* Ajuste para el contenedor principal del dashboard */
            .dashboard-container {
                padding: 1rem;
                border-radius: 0;
                max-width: 100%; /* Asegura que ocupe el 100% del ancho en pantallas muy pequeñas */
            }

            .page-container {
                padding: 1rem;
                border-radius: 0;
                box-shadow: none;
            }

            h1 {
                font-size: 1.25rem;
                text-align: center;
            }
            
            .back-link {
                display: block;
                text-align: center;
                margin-bottom: 1rem;
            }

            header {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }

            .activities-table {
                font-size: 0.8rem;
            }
            
            .activities-table th, .activities-table td {
                padding: 6px;
            }
        }
        
    </style>
</head>
<body>

    <div id="map"></div>
    <div class="info-panel">
        <h3><?php echo esc_html($competition->nombre); ?></h3>
        <p><strong>Fecha:</strong> <?php echo esc_html(date('d/m/Y', strtotime($competition->fecha))); ?></p>
        <p><strong>Distancia:</strong> <?php echo esc_html($competition->distancia); ?> km</p>
        <p><strong>Desnivel:</strong> <?php echo esc_html($competition->desnivel); ?> m</p>
        <a href="<?php echo home_url('/mi-app/competicion/'); ?>" class="back-link" style="display:inline-block; margin-top:10px; padding: 5px 10px; background:#0073aa; color:white; text-decoration:none; border-radius:3px;">← Volver</a>
    </div>

    <!-- JavaScript de Leaflet y GPXParser -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/gh/Luuka/GPXParser.js/dist/GPXParser.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Inicializamos el mapa, centrado en una vista general del mundo
            const map = L.map('map').setView([40, -3], 5);

            // Añadimos la capa de teselas (el mapa base)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const gpxUrl = '<?php echo esc_url($competition->gpx_file_url); ?>';

            // Usamos fetch para descargar el contenido del archivo GPX
            fetch(gpxUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('No se pudo descargar el archivo GPX.');
                    }
                    return response.text();
                })
                .then(gpxData => {
                    // Parseamos el contenido GPX
                    const gpx = new gpxParser();
                    gpx.parse(gpxData);

                    if (gpx.tracks.length > 0) {
                        const track = gpx.tracks[0];
                        const latlngs = [];

                        // Extraemos todos los puntos de la primera pista para crear la línea
                        track.points.forEach(point => {
                            latlngs.push([point.lat, point.lon]);
                        });

                        // Dibujamos la línea en el mapa
                        const polyline = L.polyline(latlngs, { color: 'blue' }).addTo(map);

                        // Ajustamos la vista del mapa para que quepa toda la ruta
                        map.fitBounds(polyline.getBounds());

                        // Añadimos marcadores para el inicio y el final
                        if (latlngs.length > 0) {
                            const startMarker = L.marker(latlngs[0]).addTo(map)
                                .bindPopup('Inicio');
                            const endMarker = L.marker(latlngs[latlngs.length - 1]).addTo(map)
                                .bindPopup('Fin');
                        }
                    } else {
                        alert('El archivo GPX no contiene rutas válidas.');
                    }
                })
                .catch(error => {
                    console.error('Error al cargar el GPX:', error);
                    alert('Hubo un error al cargar el mapa: ' + error.message);
                });
        });
    </script>

    <?php wp_footer(); ?>
</body>
</html>