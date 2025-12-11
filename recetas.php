<?php
// Obtenemos el usuario actual
 $current_user = wp_get_current_user();
 $user_id = $current_user->ID;

// Mensajes de éxito o error
if (isset($_GET['plan_generated'])) {
    $success_message = "¡El plan de comidas se ha guardado correctamente!";
} elseif (isset($_GET['plan_error'])) {
    $error_message = "Hubo un error al intentar guardar el plan.";
}

// Obtenemos las competiciones del usuario para el desplegable
global $wpdb;
 $table_name = $wpdb->prefix . 'mi_app_competiciones';
 $competiciones = $wpdb->get_results(
    $wpdb->prepare("SELECT id, nombre, distancia FROM $table_name WHERE user_id = %d ORDER BY fecha DESC", $user_id)
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Nutricion - Carbo Cycling</title>
    <?php wp_head(); ?>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; margin: 0; padding: 2rem; }
        .page-container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1, h2, h3, h4 { color: #333; }
        .back-link { display: inline-block; margin-top: 2rem; padding: 0.5rem 1rem; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: bold; }
        .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .generate-btn { display: block; width: 100%; padding: 1rem; background-color: #0073aa; color: white; border: none; border-radius: 4px; font-size: 1.1rem; cursor: pointer; transition: background-color 0.3s; }
        .generate-btn:hover { background-color: #005a87; }
        .plan-container { margin-top: 2rem; }
        .plan-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; background-color: #fdfdfd; }
        .plan-card h3 { margin-top: 0; color: #0073aa; }
        .day-plan { margin-bottom: 1.5rem; }
        .day-plan h4 { margin-top: 0; color: #555; }
        .meal-card { background-color: #f9f9f9; border-left: 5px solid #0073aa; padding: 1rem; margin-bottom: 1rem; border-radius: 0 4px 4px 0 8px; }
        .meal-card h4 { margin-top: 0; margin-bottom: 0.5rem; }
        .meal-stats { font-size: 0.8em; color: #555; }
        .error-card { border: 1px solid #f8d7da; background-color: #f8d7da; padding: 1rem; border-radius: 4px; }
        .error-card h3 { color: #721c24; }


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
    <div class="page-container">
        <h1>Recetas y Planes Nutricionales</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo esc_html($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="error-card">
                <h3>Error al Guardar el Plan</h3>
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <div class="plan-container">
            <h2>Generar un Nuevo Plan</h2>
            <form id="generate-plan-form" method="post">
                <?php wp_nonce_field('generate_full_meal_plan_nonce', 'generate_full_meal_plan_security'); ?>
                <div class="form-group">
                    <label for="competition-select">Selecciona una Competición</label>
                    <select name="competition_id" id="competition-select" required>
                        <option value="">-- Selecciona una competición --</option>
                        <?php if ($competiciones): ?>
                            <?php foreach ($competiciones as $comp): ?>
                                <option value="<?php echo esc_attr($comp->id); ?>">
                                    <?php echo esc_html($comp->nombre); ?> (<?php echo esc_html($comp->distancia); ?> km)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit" name="generate_full_meal_plan" class="generate-btn">Generar Plan Nutricional Completo</button>
            </form>
        </div>

        <div class="plan-container">
            <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a>
            <h2>Mis Planes Guardados</h2>
            <?php if ($competiciones): ?>
                <div class="day-plan">
                    <h3>Plan para la competición: <strong id="selected-competition-name">N/A</strong></h3>
                    <div id="plan-results-container">
                        <!-- El plan se cargará aquí dinámicamente con JavaScript -->
                    </div>
                </div>
            <?php else: ?>
                <p>Aún no tienes competiciones. Añade una para poder generar un plan.</p>
            <?php endif; ?>
        </div>

        <!--<a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a>-->
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('generate-plan-form');
            const resultsContainer = document.getElementById('plan-results-container');
            const competitionSelect = document.getElementById('competition-select');
            const selectedNameElement = document.getElementById('selected-competition-name');
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Añadimos un contenedor para los insights de Strava
            const insightsContainer = document.createElement('div');
            insightsContainer.id = 'strava-insights-container';
            insightsContainer.style.marginTop = '1.5rem';
            form.parentNode.insertBefore(insightsContainer, form.nextSibling);

            function checkForExistingPlan(competitionId) {
                if (!competitionId) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                    insightsContainer.innerHTML = '';
                    insightsContainer.style.display = 'none';
                    submitButton.textContent = 'Generar Plan Nutricional Completo';
                    submitButton.disabled = true;
                    return;
                }

                submitButton.textContent = 'Cargando...';
                submitButton.disabled = true;

                const planFormData = new FormData();
                planFormData.append('action', 'get_saved_nutrition_plan');
                planFormData.append('competition_id', competitionId);
                planFormData.append('nonce', '<?php echo wp_create_nonce('get_saved_plan_nonce'); ?>');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: planFormData
                })
                .then(response => response.json())
                .then(planData => {
                    let planExists = false;
                    if (planData.success) {
                        displayPlan(planData.data, resultsContainer);
                        resultsContainer.style.display = 'block';
                        submitButton.textContent = 'Generar un Nuevo Plan (sobreescribirá el actual)';
                        planExists = true;
                    } else {
                        resultsContainer.innerHTML = '';
                        resultsContainer.style.display = 'none';
                        submitButton.textContent = 'Generar Plan Nutricional Completo';
                    }
                    
                    getStravaInsights(competitionId, planExists);
                })
                .catch(error => {
                    console.error('Error al buscar plan guardado:', error);
                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                    submitButton.textContent = 'Generar Plan Nutricional Completo';
                    submitButton.disabled = false;
                    getStravaInsights(competitionId, false);
                });
            }
            
            function getStravaInsights(competitionId, planExists) {
                const insightsFormData = new FormData();
                insightsFormData.append('action', 'get_strava_insights');
                insightsFormData.append('nonce', '<?php echo wp_create_nonce('get_strava_insights_nonce'); ?>');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: insightsFormData
                })
                .then(response => response.json())
                .then(insightsData => {
                    if (insightsData.success) {
                        displayStravaInsights(insightsData.data, insightsContainer);
                        insightsContainer.style.display = 'block';
                    } else {
                        insightsContainer.innerHTML = '';
                        insightsContainer.style.display = 'none';
                    }
                    submitButton.disabled = false;
                })
                .catch(error => {
                    console.error('Error al obtener insights de Strava:', error);
                    insightsContainer.innerHTML = '';
                    insightsContainer.style.display = 'none';
                    submitButton.disabled = false;
                });
            }

            competitionSelect.addEventListener('change', function() {
                const competitionId = this.value;
                selectedNameElement.textContent = this.options[this.selectedIndex].text;
                checkForExistingPlan(competitionId);
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                formData.append('action', 'generate_full_meal_plan');

                submitButton.textContent = 'Generando...';
                submitButton.disabled = true;

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error en la respuesta del servidor: ${response.status} ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayPlan(data.data, resultsContainer);
                        resultsContainer.style.display = 'block';
                        submitButton.textContent = 'Generar un Nuevo Plan (sobreescribirá el actual)';
                    } else {
                        throw new Error(data.data || 'Error desconocido del servidor.');
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    const errorCard = document.createElement('div');
                    errorCard.className = 'error-card';
                    errorCard.innerHTML = `<h3>Error al Generar el Plan</h3><p>${error.message}</p>`;
                    resultsContainer.innerHTML = '';
                    resultsContainer.appendChild(errorCard);
                    resultsContainer.style.display = 'block';
                })
                .finally(() => {
                    submitButton.disabled = false;
                });
            });

            // --- INICIO DE LA LÓGICA DEL BOTÓN PDF (CORREGIDA) ---
            // Usamos event delegation en el contenedor principal para mayor eficiencia
            document.querySelector('.page-container').addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('download-pdf-btn')) {
                    e.preventDefault();

                    const competitionId = competitionSelect.value; // <-- AHORA SÍ FUNCIONA
                    if (!competitionId) {
                        alert('Por favor, selecciona una competición primero.');
                        return;
                    }

                    const downloadBtn = e.target;
                    const originalText = downloadBtn.textContent;
                    downloadBtn.textContent = 'Generando PDF...';
                    downloadBtn.disabled = true;

                    const formData = new FormData();
                    formData.append('action', 'download_nutrition_plan_pdf');
                    formData.append('competition_id', competitionId);
                    formData.append('nonce', '<?php echo wp_create_nonce('download_plan_pdf_nonce'); ?>');

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al generar el PDF.');
                        }
                        return response.blob();
                    })
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'plan-nutricional.pdf';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Hubo un error al descargar el PDF. Por favor, inténtalo de nuevo.');
                    })
                    .finally(() => {
                        downloadBtn.textContent = originalText;
                        downloadBtn.disabled = false;
                    });
                }
            });
            // --- FIN DE LA LÓGICA DEL BOTÓN PDF ---

        });

        function displayStravaInsights(insights, container) {
            let html = `
                <div class="plan-card" style="border-color: #fc4c02;">
                    <h3 style="color: #fc4c02;">Insights de Strava</h3>
                    <p><strong>${insights.summary}</strong></p>
                    <div class="meal-stats">
                        <span>Carga de Entrenamiento: ${insights.training_load}/10</span> |
                        <span>Fatiga Acumulada: ${insights.fatiga}/10</span>
                    </div>
                </div>
            `;
            container.innerHTML = html;
        }

        function displayPlan(plan, container) {
            container.innerHTML = '';
            
            if (plan && plan.days && Array.isArray(plan.days)) {
                if (plan.strava_insights && plan.strava_insights.summary) {
                    const insightsCard = document.createElement('div');
                    insightsCard.className = 'plan-card';
                    insightsCard.style.borderLeft = '5px solid #28a745';
                    insightsCard.innerHTML = `
                        <h3>Justificación del Plan Nutricional</h3>
                        <p>${plan.strava_insights.summary}</p>
                    `;
                    container.appendChild(insightsCard);
                }

                const planName = plan.plan_name || 'Plan Nutricional';
                const planCard = document.createElement('div');
                planCard.className = 'plan-card';
                planCard.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="margin: 0;">${planName}</h3>
                        <button class="download-pdf-btn" style="padding: 0.5rem 1rem; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em;">
                            📄 Descargar PDF
                        </button>
                    </div>`;
                container.appendChild(planCard);

                plan.days.forEach(day => {
                    let mealsHtml = '';
                    if (day.meals && Array.isArray(day.meals)) {
                        mealsHtml += `<h4>Plan para el ${day.day}</h4>`;
                        day.meals.forEach(meal => {
                            mealsHtml += `
                                <div class="meal-card">
                                    <h5>${meal.type.charAt(0).toUpperCase() + meal.type.slice(1)}</h5>
                                    <p><strong>Receta:</strong> ${meal.name || 'N/A'}</p>
                                    <p><strong>Ingredientes:</strong> ${meal.ingredients || 'N/A'}</p>
                                    <p><strong>Instrucciones:</strong> ${meal.instructions || 'N/A'}</p>
                                    <div class="meal-stats">
                                        <span>Carbs: ${meal.carbs || 'N/A'}g</span> |
                                        <span>Proteína: ${meal.protein || 'N/A'}g</span> |
                                        <span>Grasas: ${meal.fats || 'N/A'}g</span> |
                                        <span>Calorías: ${meal.calories || 'N/A'} kcal</span>
                                    </div>                                
                                </div>
                            `;
                        });
                    }
                    const dayCard = document.createElement('div');
                    dayCard.className = 'day-plan';
                    dayCard.innerHTML = `<h4>${day.day}</h4>${mealsHtml}`;
                    container.appendChild(dayCard);
                });
            } else {
                 const errorCard = document.createElement('div');
                 errorCard.className = 'error-card';
                 errorCard.innerHTML = `<h3>Error en el Formato del Plan</h3><p>El plan recibido no tiene un formato válido.</p>`;
                 container.innerHTML = '';
                 container.appendChild(errorCard);
            }
        }
    </script>
    <?php wp_footer(); ?>
</body>
</html>