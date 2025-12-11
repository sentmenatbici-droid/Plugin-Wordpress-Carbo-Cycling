<?php
// Obtenemos el usuario actual
 $current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Dashboard - Carbo Cycling</title>
    <?php wp_head(); ?>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; margin: 0; padding: 2rem; }
        .dashboard-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .user-info { background: #eaf2f8; padding: 1rem; border-radius: 4px; margin-bottom: 2rem; }
        .buttons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .nav-button { display: block; padding: 1.5rem; background-color: #0073aa; color: white; text-decoration: none; text-align: center; border-radius: 8px; font-size: 1.1rem; transition: background-color 0.3s; }
        .nav-button:hover { background-color: #005a87; }
        .logout-link { display: inline-block; margin-top: 2rem; color: #dc3545; }

        /* ==========================================================================
           ESTILOS PARA EL WIDGET DEL TIEMPO (DISEÑO RESPONSIVO)
           ========================================================================== */

        .weather-widget {
            margin-top: 2rem;
            border: 1px solid #ddd;
            padding: 1.5rem;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .weather-widget h4 {
            margin-top: 0;
            margin-bottom: 1rem;
            text-align: center;
            color: #333;
        }

        /* --- Contenedor de la Previsión del Tiempo --- */
        .weather-forecast {
            display: grid; /* Activa el modo de cuadrícula */
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Crea columnas responsivas. Cada día ocupará al menos 280px y se adaptarán las que quepan */
            gap: 20px; /* Espacio entre los días */
        }

        /* --- Cada Día Individual --- */
        .weather-day {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .weather-day .weather-date {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .weather-day .weather-details {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }

        .weather-day .temps {
            font-size: 1.1em;
            font-weight: bold;
        }

        .weather-day .temp-max {
            color: #d9534f; /* Rojo para la máxima */
        }

        .weather-day .temp-min {
            color: #3498db; /* Azul para la mínima */
        }

        /* --- En pantallas más pequeñas (móviles), apilamos los días --- */
        @media (max-width: 768px) {
            .weather-forecast {
                grid-template-columns: 1fr; /* Una sola columna */
                gap: 15px;
            }
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
    <div class="dashboard-container">
        <h1>Panel Principal</h1>
        <div class="user-info">
            <p>¡Bienvenido, <strong><?php echo esc_html($current_user->display_name); ?></strong>!</p>
        </div>
        
        
        <div class="buttons-grid">
            <a href="<?php echo home_url('/mi-app/competicion/'); ?>" class="nav-button">Competicion</a>
            <a href="<?php echo home_url('/mi-app/recetas/'); ?>" class="nav-button">Nutrición</a>
            <a href="<?php echo home_url('/mi-app/strava-activities/'); ?>" class="nav-button">Mi Strava</a>
            <a href="<?php echo home_url('/mi-app/perfil/'); ?>" class="nav-button">Perfil</a>
            <a href="<?php echo home_url('/mi-app/challenges/'); ?>" class="nav-button">Desafíos y Logros</a>
            <a href="<?php echo home_url('/mi-app/faq/'); ?>" class="nav-button">Preguntas Frecuentes</a>

        </div>

        <?php echo do_shortcode('[weather_dashboard_widget days="3"]'); ?>

        <!--<a href="<?php //echo wp_logout_url(home_url('/mi-app/login/')); ?>" class="logout-link">Cerrar sesión</a>-->
        <a href="<?php echo wp_logout_url(home_url('/')); ?>" class="logout-link">Cerrar sesión</a>
    </div>
    <?php wp_footer(); ?>
</body>
</html>