<?php
// Obtenemos el usuario actual
 $current_user = wp_get_current_user();
 $user_id = $current_user->ID;

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar si el usuario está logueado
 $this->check_user_logged_in();

//get_header();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>FAQ - Carbo Cycling</title>
    <?php wp_head(); ?>
</head>
<body>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="mi-app-container">
            <article class="faq-page">
                <header class="entry-header">
                    <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a><br>
                    <h1 class="entry-title">Preguntas Frecuentes (FAQ)</h1>
                </header>
                <div class="faq-content">
                    <?php
                        // Obtenemos el contenido de la FAQ principal desde las opciones del plugin
                        $main_faq_content = get_option('mi_app_faq_content', '');
                        if (!empty($main_faq_content)) {
                            // Aplicamos the_content_filter para que se procesen shortcodes, etc.
                            echo '<div class="faq-item faq-main-content">';
                            echo '<button class="faq-question-toggle"><span class="faq-icon">+</span>' . esc_html__('Preguntas Generales', 'mi-app-plugin') . '</button>';
                            echo '<div class="faq-answer-content">' . apply_filters('the_content', $main_faq_content) . '</div>';
                            echo '</div>';
                        }
                    ?>

                    <?php
                    // Obtenemos las FAQs individuales (Custom Post Type)
                    $args = [
                        'post_type'      => 'mi_app_faq',
                        'posts_per_page' => -1,
                        'post_status'    => 'publish',
                    ];
                    
                    $faqs_query = new WP_Query($args);

                    if ($faqs_query->have_posts()) :
                        while ($faqs_query->have_posts()) : $faqs_query->the_post();
                    ?>
                            <div class="faq-item">
                                <button class="faq-question-toggle">
                                    <span class="faq-icon">+</span>
                                    <?php echo esc_html(get_the_title()); ?>
                                </button>
                                <div class="faq-answer-content">
                                    <?php
                                    // Usamos the_content() para aplicar los filtros de WordPress y mantener el formato
                                    echo apply_filters('the_content', get_the_content());
                                    ?>
                                </div>
                            </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<p>No hay preguntas frecuentes disponibles en este momento.</p>';
                    endif;
                    ?>
                </div>
                <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a>
            </article>
        </div>
    </main>
</div>

<style>
/* Estilos para el efecto Acordeón */
.back-link { 
    display: inline-block; 
    margin-top: 2rem; 
    padding: 0.5rem 1rem; 
    background-color: #6c757d; 
    color: white; 
    text-decoration: none; 
    border-radius: 4px; 
}

.faq-item {
    border: 1px solid #ddd;
    margin-bottom: 10px;
    border-radius: 5px;
    background-color: #f9f9f9;
}

.faq-question-toggle {
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    padding: 15px;
    font-size: 1.1em;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.2s ease;
}

.faq-question-toggle:hover {
    background-color: #e9e9e9;
}

.faq-icon {
    font-size: 1.2em;
    font-weight: bold;
    color: #0073aa;
    transition: transform 0.2s ease;
}

.faq-answer-content {
    padding: 0 20px 20px;
    display: none; /* Oculto por defecto */
    border-top: 1px solid #ddd;
}

/* Cuando el item está abierto, cambiamos el icono */
.faq-item.is-open .faq-icon {
    transform: rotate(45deg);
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

<script>
jQuery(document).ready(function($) {
    // Manejar el clic en cualquier pregunta de la FAQ
    $('.faq-question-toggle').on('click', function() {
        var $button = $(this);
        var $content = $button.next('.faq-answer-content');
        var $item = $button.closest('.faq-item');

        // Alternar la visibilidad del contenido con una animación suave
        $content.slideToggle(300); // 300ms para la animación

        // Alternar la clase 'is-open' para cambiar el icono
        $item.toggleClass('is-open');
    });
});
</script>

<?php get_footer(); ?>

</body>
</html>