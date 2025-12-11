<?php
// Obtenemos el usuario actual
 $current_user = wp_get_current_user();
 $user_id = $current_user->ID;

// --- LÓGICA PARA GUARDAR EL PERFIL (sin cambios) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mi_app_save_profile_nonce'])) {
    if (!wp_verify_nonce($_POST['mi_app_save_profile_nonce'], 'mi_app_save_profile_action')) {
        $error_message = "Error de seguridad. Por favor, inténtalo de nuevo.";
    } else {
        update_user_meta($user_id, 'mi_app_peso', floatval($_POST['peso']));
        update_user_meta($user_id, 'mi_app_altura', intval($_POST['altura']));
        update_user_meta($user_id, 'mi_app_edad', intval($_POST['edad']));
        update_user_meta($user_id, 'mi_app_genero', sanitize_text_field($_POST['genero']));
        update_user_meta($user_id, 'mi_app_ftp', intval($_POST['ftp']));
        update_user_meta($user_id, 'mi_app_fcmax', intval($_POST['fcmax']));
        update_user_meta($user_id, 'mi_app_experiencia', sanitize_text_field($_POST['experiencia']));

        if (isset($_POST['mi_app_ciudad'])) {
        update_user_meta($user_id, 'mi_app_ciudad', sanitize_text_field($_POST['mi_app_ciudad']));
        }

        if (isset($_POST['mi_app_pais'])) {
            update_user_meta($user_id, 'mi_app_pais', sanitize_text_field($_POST['mi_app_pais']));
        }

        if (isset($_POST['mi_app_user_gemini_api_key'])) {
        // Sanitiza el valor antes de guardarlo
        $user_gemini_key = sanitize_text_field($_POST['mi_app_user_gemini_api_key']);
        
        // Obtiene el ID del usuario actual y guarda el dato
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'mi_app_user_gemini_api_key', $user_gemini_key);
        }

        // --- PASO 3: Guardar los datos de Celiaco y Diabético ---
        // Guardar la opción de ser Celiaco
        if (isset($_POST['celiaco'])) {
            $es_celiaco = sanitize_text_field($_POST['celiaco']);
            $result_celiaco = update_user_meta($user_id, 'mi_app_celiaco', $es_celiaco);
        } else {
            error_log('El campo mi_app_celiaco NO se envió en el POST.');
        }

        // Guardar la opción de ser Diabético
        if (isset($_POST['diabetico'])) {
            $es_diabetico = sanitize_text_field($_POST['diabetico']);
            $result_diabetico = update_user_meta($user_id, 'mi_app_diabetico', $es_diabetico);
        } else {
            error_log('El campo mi_app_diabetico NO se envió en el POST.');
        }

        $success_message = "¡Tu perfil ha sido guardado correctamente!";
    }
}

// --- LÓGICA PARA STRAVA ---

// 1. Manejar la desconexión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'disconnect_strava') {
    if (wp_verify_nonce($_POST['strava_disconnect_nonce'], 'strava_disconnect_action')) {
        delete_user_meta($user_id, 'strava_access_token');
        delete_user_meta($user_id, 'strava_refresh_token');
        delete_user_meta($user_id, 'strava_expires_at');
        delete_user_meta($user_id, 'strava_athlete');
        $strava_success_message = "La conexión con Strava ha sido eliminada.";
    }
}

// 2. Comprobar si ya hay una conexión activa
 $strava_access_token = get_user_meta($user_id, 'strava_access_token', true);
 $is_strava_connected = !empty($strava_access_token);

// 3. Manejar mensajes de la URL (después del callback de OAuth)
if (isset($_GET['strava_connected'])) {
    $strava_success_message = "¡Te has conectado con Strava correctamente!";
} elseif (isset($_GET['strava_error'])) {
    $strava_error_message = "Hubo un error al conectar con Strava. Inténtalo de nuevo.";
}

// --- CARGAR DATOS DEL PERFIL (sin cambios) ---
 $stored_peso = get_user_meta($user_id, 'mi_app_peso', true);
 $stored_altura = get_user_meta($user_id, 'mi_app_altura', true);
 $stored_edad = get_user_meta($user_id, 'mi_app_edad', true);
 $stored_genero = get_user_meta($user_id, 'mi_app_genero', true);
 $stored_ftp = get_user_meta($user_id, 'mi_app_ftp', true);
 $stored_fcmax = get_user_meta($user_id, 'mi_app_fcmax', true);
 $stored_experiencia = get_user_meta($user_id, 'mi_app_experiencia', true);
 $stored_celiaco = get_user_meta($user_id, 'mi_app_celiaco', true);
 $stored_diabetico = get_user_meta($user_id, 'mi_app_diabetico', true);


// --- DATOS PARA EL BOTÓN DE STRAVA ---
 $strava_client_id = get_option('mi_app_strava_client_id');
 //$strava_client_id = MI_APP_STRAVA_CLIENT_ID;
 $redirect_uri = home_url('/mi-app/strava-callback/');
 $scope = 'read,activity:read_all'; // Permisos que solicitamos
 $strava_auth_url = "https://www.strava.com/oauth/authorize?client_id={$strava_client_id}&response_type=code&redirect_uri={$redirect_uri}&scope={$scope}&approval_prompt=auto";

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Perfil - Carbo Cycling</title>
    <?php wp_head(); ?>
    <style>
        /* ... (estilos existentes sin cambios) ... */
        body { font-family: sans-serif; background-color: #f4f4f4; margin: 0; padding: 2rem; }
        .page-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 0.5rem; }
        .profile-form .form-group { margin-bottom: 1.5rem; }
        .profile-form label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: bold; }
        .profile-form input[type="number"], .profile-form select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .save-button { display: block; width: 100%; padding: 1rem; background-color: #28a745; color: white; border: none; border-radius: 4px; font-size: 1.1rem; cursor: pointer; transition: background-color 0.3s; margin-bottom: 2rem; }
        .save-button:hover { background-color: #218838; }
        .back-link { display: inline-block; margin-top: 2rem; padding: 0.5rem 1rem; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        .success-message, .error-message { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; text-align: center; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Estilos para la sección de Strava */
        .strava-section { border-top: 1px solid #eee; padding-top: 2rem; margin-top: 2rem; }
        .strava-section h2 { color: #555; font-size: 1.2rem; }
        .strava-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #FC4C02;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .strava-button:hover { background-color: #e44302; }
        .strava-button img { height: 20px; margin-right: 10px; }
        .disconnect-form { display: inline; }

        .checkbox-group label {
         margin-right: 15px;
        font-weight: normal;
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
    <div class="page-container">
        <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a>
        <h1>Mi Perfil</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo esc_html($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo esc_html($error_message); ?></div>
        <?php endif; ?>

        <form method="post" class="profile-form">
            <?php wp_nonce_field('mi_app_save_profile_action', 'mi_app_save_profile_nonce'); ?>
            <!-- ... (campos del formulario sin cambios) ... -->
            <div class="form-group"><label for="peso">Peso (kg)</label><input type="number" step="0.1" name="peso" id="peso" value="<?php echo esc_attr($stored_peso); ?>" required></div>
            <div class="form-group"><label for="altura">Altura (cm)</label><input type="number" name="altura" id="altura" value="<?php echo esc_attr($stored_altura); ?>" required></div>
            <div class="form-group"><label for="edad">Edad (años)</label><input type="number" name="edad" id="edad" value="<?php echo esc_attr($stored_edad); ?>" required></div>
            <div class="form-group"><label for="genero">Género</label><select name="genero" id="genero" required><option value="">-- Selecciona --</option><option value="masculino" <?php selected($stored_genero, 'masculino'); ?>>Masculino</option><option value="femenino" <?php selected($stored_genero, 'femenino'); ?>>Femenino</option></select></div>
            <div class="form-group"><label for="ftp">FTP (Watts)</label><input type="number" name="ftp" id="ftp" value="<?php echo esc_attr($stored_ftp); ?>" placeholder="160 por defecto"></div>
            <div class="form-group"><label for="mi_app_ciudad">Población</label><?php
                        $ciudad_guardada = get_user_meta(get_current_user_id(), 'mi_app_ciudad', true);
                        // Si no hay valor guardado, usa 'Sentmenat'
                        $ciudad_mostrar = empty($ciudad_guardada) ? 'Sentmenat' : $ciudad_guardada;
                        ?>
                <input type="text" id="mi_app_ciudad" name="mi_app_ciudad" value="<?php echo esc_attr($ciudad_mostrar); ?>" placeholder="Por defecto Sentmenat"></div>

            <div class="form-group"><label for="mi_app_pais">Código de País</label><?php
                        $pais_guardado = get_user_meta(get_current_user_id(), 'mi_app_pais', true);
                        // Si no hay valor guardado, usa 'ES'
                        $pais_mostrar = empty($pais_guardado) ? 'ES' : $pais_guardado;
                        ?>
                <input type="text" id="mi_app_pais" name="mi_app_pais" value="<?php echo esc_attr($pais_mostrar); ?>" placeholder="Por defecto España.">
            <p class="description">Código de dos letras, como "ES" para España, "FR" para Francia. <a href="https://es.wikipedia.org/wiki/ISO_3166-1_alfa-2#Elementos_de_c%C3%B3digo_asignados_oficialmente" target="_blank">Ver lista de códigos</a>.</p></div>
            <div class="form-group"><label for="fc_max">FC MÁX (Frecuencia Cardíaca Máxima)</label><input type="number" id="fcmax" name="fcmax" value="<?php echo esc_attr($stored_fcmax); ?>" placeholder="180 por defecto"></div>
            <div class="form-group"><label for="experiencia">Experiencia</label><select name="experiencia" id="experiencia" required><option value="">-- Selecciona --</option><option value="principiante" <?php selected($stored_experiencia, 'principiante'); ?>>Principiante</option><option value="intermedio" <?php selected($stored_experiencia, 'intermedio'); ?>>Intermedio</option><option value="avanzado" <?php selected($stored_experiencia, 'avanzado'); ?>>Avanzado</option><option value="elite" <?php selected($stored_experiencia, 'elite'); ?>>Elite</option></select></div>
            <!-- Campo de Celiaco -->
             <div class="form-group"><label for="celiaco">¿Eres Celiaco?</label><select name="celiaco" id="celiaco" required><option value="">-- Selecciona --</option><option value="no" <?php selected($stored_celiaco, 'no'); ?>>No</option><option value="si" <?php selected($stored_celiaco, 'si'); ?>>Si</option></select>


             <p class="description">Importante para la generación de planes de nutrición sin gluten.</p>
             </div>

             <!-- Campo de Diabético -->
             <div class="form-group"><label for="diabetico">¿Eres Diabético?</label>
             <select name="diabetico" id="diabetico" required><option value="">-- Selecciona --</option><option value="no" <?php selected($stored_diabetico, 'no'); ?>>No</option><option value="si" <?php selected($stored_diabetico, 'si'); ?>>Si</option></select>
             <p class="description">Importante para adaptar las recomendaciones de azúcares y carbohidratos.</p>
             </div>
            <div class="form-group"><label for="mi_app_user_gemini_api_key">Clave Personal API (Google Gemini)</label>
            <input type="password" id="mi_app_user_gemini_api_key" name="mi_app_user_gemini_api_key" value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'mi_app_user_gemini_api_key', true)); ?>" class="regular-text" placeholder="Introduce tu clave personal de API aquí">
            <p class="description">Clave de API para la funcionalidad de IA. Tu clave es privada y no visible.Obtén tu API key gratuita en <a target="blank" href="https://makersuite.google.com/app/apikey">Google AI Studio</a></p>
            </div>

            <button type="submit" class="save-button">Guardar Perfil</button>
        </form>

        <!-- SECCIÓN DE STRAVA -->
        <div class="strava-section">
            <h2>Conexión con Strava</h2>
            
            <?php if (isset($strava_success_message)): ?>
                <div class="success-message"><?php echo esc_html($strava_success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($strava_error_message)): ?>
                <div class="error-message"><?php echo esc_html($strava_error_message); ?></div>
            <?php endif; ?>

            <?php if ($is_strava_connected): ?>
                <p>✅ Tu cuenta está conectada con Strava.</p>
                <form method="post" class="disconnect-form">
                    <?php wp_nonce_field('strava_disconnect_action', 'strava_disconnect_nonce'); ?>
                    <input type="hidden" name="action" value="disconnect_strava">
                    <button type="submit" class="strava-button" style="background-color: #dc3545;">
                        Eliminar Conexión
                    </button>
                </form>
            <?php else: ?>
                <p>Conecta tu cuenta para obtener planes personalizados basados en tu entrenamiento real.</p>
                <a href="<?php echo esc_url($strava_auth_url); ?>" class="strava-button">
                    <!--<img src="https://z-cdn-media.chatglm.cn/files/40cfac36-0426-4fb2-9ba3-221c58f06e7b_pasted_image_1763237290215.png?auth_key=1863237305-adf41baef83d4edcaace24c78b5d4e5f-0-9fd72932d5d1396338e757d4dcd292e0" alt="Strava Logo">-->
                    Conectar con Strava
                </a>
            <?php endif; ?>
        </div>

        <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a>
    </div>
    <?php wp_footer(); ?>
</body>
</html>