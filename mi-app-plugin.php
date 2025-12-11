<?php
/**
 * Plugin Name:       Carbo Cycling
 * Description:       Plugin para el control de nutrición y carbohidratos en salidas y competiciones de ciclismo mediante IA
 * Version:           1.0.15
 * Author:            Mikigarcia - SentmenatBici
 * Author URI:        https://sentmenatbici.cat
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mi-app-plugin
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class MiAppPlugin
{
    /**
     * Constructor. Engancha las acciones y filtros de WordPress.
     */
    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_template_redirect']);

        // Ganchos para las competiciones
        add_action('init', [$this, 'handle_competition_submission']);
        add_action('init', [$this, 'handle_competition_deletion']);

        // FILTRO PARA PERMITIR ARCHIVOS GPX
        add_filter('upload_mimes', [$this, 'add_gpx_mime_type']);

        // Crea el menu Backend
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        // Gancho para las Recetas
        add_action('wp_ajax_generate_nutrition_plan', [$this, 'handle_generate_nutrition_plan']);
        add_action('wp_ajax_nopriv_generate_nutrition_plan', [$this, 'handle_generate_nutrition_plan']);
        add_action('wp_ajax_generate_full_meal_plan', [$this, 'handle_generate_full_meal_plan']);
        add_action('wp_ajax_nopriv_generate_full_meal_plan', [$this, 'handle_generate_full_meal_plan']);
        add_action('wp_ajax_save_recipe', [$this, 'handle_save_recipe']);
        add_action('wp_ajax_nopriv_save_recipe', [$this, 'handle_save_recipe']);
        add_action('wp_ajax_get_saved_nutrition_plan', [$this, 'handle_get_saved_nutrition_plan']);
        add_action('wp_ajax_nopriv_get_saved_nutrition_plan', [$this, 'handle_get_saved_nutrition_plan']);  

        // Gancho para Strava
        add_action('wp_ajax_get_strava_insights', [$this, 'handle_get_strava_insights']);
        add_action('wp_ajax_nopriv_get_strava_insights', [$this, 'handle_get_strava_insights']);
        add_action('wp_ajax_get_strava_chart_data', [$this, 'handle_get_strava_chart_data']);
        add_action('wp_ajax_nopriv_get_strava_chart_data', [$this, 'handle_get_strava_chart_data']);
        add_action('wp_ajax_get_pmc_chart_data', [$this, 'handle_get_pmc_chart_data']);
        add_action('wp_ajax_nopriv_get_pmc_chart_data', [$this, 'handle_get_pmc_chart_data']);
        add_action('wp_ajax_get_pdc_chart_data', [$this, 'handle_get_pdc_chart_data']);
        add_action('wp_ajax_nopriv_get_pdc_chart_data', [$this, 'handle_get_pdc_chart_data']);
        add_action('wp_ajax_get_zones_chart_data', [$this, 'handle_get_zones_chart_data']);
        add_action('wp_ajax_nopriv_get_zones_chart_data', [$this, 'handle_get_zones_chart_data']);

        // Traducciones
        add_action('plugins_loaded', [$this, 'load_textdomain']);

        // PDFs
        add_action('wp_ajax_download_nutrition_plan_pdf', [$this, 'handle_download_nutrition_plan_pdf']);
        add_action('wp_ajax_nopriv_download_nutrition_plan_pdf', [$this, 'handle_download_nutrition_plan_pdf']);
        add_action('wp_ajax_download_nutrition_gpx', [$this, 'handle_download_nutrition_gpx']);
        add_action('wp_ajax_nopriv_download_nutrition_gpx', [$this, 'handle_download_nutrition_gpx']);


        add_action('wp_ajax_analyze_gpx', [$this, 'handle_analyze_gpx']);
        add_action('wp_ajax_nopriv_analyze_gpx', [$this, 'handle_analyze_gpx']);      

        add_action('wp_enqueue_scripts', [$this, 'enqueue_analysis_styles']);  

        // Añadir las nuevas acciones
        //add_action('show_user_profile', [$this, 'add_user_profile_fields']);
        //add_action('edit_user_profile', [$this, 'add_user_profile_fields']);
        //add_action('personal_options_update', [$this, 'save_user_profile_fields']);
        //add_action('edit_user_profile_update', [$this, 'save_user_profile_fields']);
        
        // Modificar la plantilla de competición
        add_filter('template_include', [$this, 'modify_competition_template']);      

        add_action('wp_ajax_analyze_gpx_sse', [$this, 'handle_analyze_gpx_sse']);
        add_action('wp_ajax_nopriv_analyze_gpx_sse', [$this, 'handle_analyze_gpx_sse']);   

        //FAQ
        add_action('init', [$this, 'register_my_app_plugin_post_types']);
        //add_action('init', [$this, 'register_faq_post_type']);     
        add_action('add_meta_boxes', [$this, 'add_faq_meta_boxes']);
        add_action('save_post', [$this, 'save_faq_post_meta']);
        add_action('admin_menu', [$this, 'add_faq_menu']);

        add_action('wp_ajax_add_faq', [$this, 'handle_ajax_add_faq']);
        add_action('wp_ajax_nopriv_add_faq', [$this, 'handle_ajax_add_faq']);
        add_action('wp_ajax_edit_faq', [$this, 'handle_ajax_edit_faq']);
        add_action('wp_ajax_nopriv_edit_faq', [$this, 'handle_ajax_edit_faq']);
        add_action('wp_ajax_delete_faq', [$this, 'handle_ajax_delete_faq']);
        add_action('wp_ajax_nopriv_delete_faq', [$this, 'handle_ajax_delete_faq']);        

        //CSS
        add_action('wp_enqueue_scripts', [$this, 'enqueue_shared_plugin_styles']);

        //WEATHER
        add_shortcode('weather_dashboard_widget', [$this, 'handle_get_weather_dashboard_widget']);
        add_action( 'wp_enqueue_scripts', [ $this, 'mi_app_enqueue_weather_icons' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'mi_app_enqueue_weather_icons' ] ); // Opcional: para el dashboard de administración

        //Desafios y Logros
        add_action('admin_menu', [$this, 'add_faq_menu']);
        add_action('admin_menu', [$this, 'add_challenges_menu']);
        add_action('init', [$this, 'add_custom_capabilities']);
        add_action('wp_ajax_add_challenge', [$this, 'handle_ajax_add_challenge']);
        add_action('wp_ajax_nopriv_add_challenge', [$this, 'handle_ajax_add_challenge']);
        add_action('wp_ajax_edit_challenge', [$this, 'handle_ajax_edit_challenge']);
        add_action('wp_ajax_nopriv_edit_challenge', [$this, 'handle_ajax_edit_challenge']);
        add_action('wp_ajax_delete_challenge', [$this, 'handle_ajax_delete_challenge']);
        add_action('wp_ajax_nopriv_delete_challenge', [$this, 'handle_ajax_delete_challenge']);    
        add_action('init', [$this, 'add_custom_capabilities']);    

    }

    public function add_custom_capabilities()
    {
        $role = get_role('editor'); // Obtenemos el rol "Editor"
        if ($role) {
            $role->add_cap('manage_mi_app_plugin'); // Le damos la capacidad al rol "Editor"
        }
        // Repite para otros roles si es necesario (ej. 'author')
    
    }    


    /**
     * Añade el menú de gestión de Desafíos al panel de administración.
     */
    public function add_challenges_menu()
    {
        add_menu_page(
            'Gestión de Desafíos de Mi App', // Título de la página
            'Challenges Mi App',                 // Texto del menú
            'edit_posts',                   // Capacidad más permisiva (Editores, Autores, etc.)
            'mi-app-challenges-management', // Slug del menú (único)
            [$this, 'render_challenges_management_page'], // Función que renderiza la página
            'dashicons-awards',          // Icono de trofeo/logros
            8                              // Posición en el menú (debajo del principal)
        );
    }

    /**
     * Renderiza la página de gestión de Desafíos.
     */
    public function render_challenges_management_page($atts = [], $page = '')
    {

        // Llama a la función que ya creaste para mostrar el contenido de la pestaña de Challenges
        $this->render_challenges_tab();

    }


    /**
     * Maneja la llamada AJAX para añadir una FAQ.
     */
    public function handle_ajax_add_faq()
    {
        check_ajax_referer('faq_nonce', 'nonce');
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']); // Permite HTML seguro

        $new_faq = [
            'post_title'  => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type'   => 'mi_app_faq',
        ];

        $post_id = wp_insert_post($new_faq);

        if ($post_id) {
            wp_send_json_success();
        } else {
            wp_send_json_error('No se pudo crear la pregunta.');
        }
    }

    /**
     * Maneja la llamada AJAX para editar una FAQ.
     */
    public function handle_ajax_edit_faq()
    {
        check_ajax_referer('faq_nonce', 'nonce');
        $faq_id = intval($_POST['faq_id']);
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);

        $updated_faq = [
            'ID'           => $faq_id,
            'post_title'  => $title,
            'post_content' => $content,
        ];

        $result = wp_update_post($updated_faq);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('No se pudo actualizar la pregunta.');
        }
    }

    /**
     * Maneja la llamada AJAX para eliminar una FAQ.
     */
    public function handle_ajax_delete_faq()
    {
        check_ajax_referer('faq_nonce', 'nonce');
        $faq_id = intval($_POST['faq_id']);
        $result = wp_trash_post($faq_id);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('No se pudo eliminar la pregunta.');
        }
    }


    /**
     * Maneja la llamada AJAX para añadir un nuevo desafío desde el backend.
     */
    public function handle_ajax_add_challenge()
    {
        // 1. Verificar el nonce de seguridad
        check_ajax_referer('manage_challenges_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        // 2. Sanitizar y validar los datos recibidos
        $title = isset($_POST['challenge_title']) ? sanitize_text_field($_POST['challenge_title']) : '';
        $description = isset($_POST['challenge_description']) ? wp_kses_post($_POST['challenge_description'], 'post') : '';
        $type = 'custom'; // Valor por defecto

            if (isset($_POST['challenge_type'])) {
            $allowed_types = ['distance', 'elevation', 'rides', 'custom'];
            $submitted_type = sanitize_text_field($_POST['challenge_type']);

            if (in_array($submitted_type, $allowed_types)) {
            $type = $submitted_type;
            }
            }


        $target_value = isset($_POST['challenge_target']) ? absint($_POST['challenge_target']) : 0;
        $meta_value = isset($_POST['challenge_meta']) ? sanitize_text_field($_POST['challenge_meta']) : '';

        if (empty($title)) {
            wp_send_json_error('El título del desafío es obligatorio.');
        }

        // 3. Preparar los datos para insertar en la base de datos
        $new_challenge = [
            'post_title'  => $title,
            'post_content' => $description,
            'post_status'    => 'publish',
            'post_type'      => 'mi_app_challenge',
            'meta_input'    => [
                'challenge_type'  => $type,
                'target_value'   => $target_value,
                'meta_value'     => $meta_value,
            ],
        ];

        // 4. Insertar el nuevo desafío y obtener su ID
        $post_id = wp_insert_post($new_challenge);

        // 5. Enviar una respuesta JSON al frontend
        if ($post_id) {
            wp_send_json_success(['message' => 'Desafío añadido correctamente.', 'post_id' => $post_id]);
        } else {
            wp_send_json_error('No se pudo añadir el desafío. Inténtalo de nuevo.');
        }

        wp_die(); // Terminar la ejecución
    }

    /**
     * Maneja la llamada AJAX para editar un desafío existente desde el backend.
     */
    public function handle_ajax_edit_challenge()
    {
        check_ajax_referer('manage_challenges_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        $post_id = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('ID de desafío no válido.');
        }

        $title = isset($_POST['challenge_title']) ? sanitize_text_field($_POST['challenge_title']) : '';
        $description = isset($_POST['challenge_description']) ? wp_kses_post($_POST['challenge_description'], 'post') : '';
        $type = 'custom'; // Valor por defecto

            if (isset($_POST['challenge_type'])) {
            $allowed_types = ['distance', 'elevation', 'rides', 'custom'];
            $submitted_type = sanitize_text_field($_POST['challenge_type']);

            if (in_array($submitted_type, $allowed_types)) {
            $type = $submitted_type;
            }
            }
        $target_value = isset($_POST['challenge_target']) ? absint($_POST['challenge_target']) : 0;
        $meta_value = isset($_POST['challenge_meta']) ? sanitize_text_field($_POST['challenge_meta']) : '';

        if (empty($title)) {
            wp_send_json_error('El título del desafío es obligatorio.');
        }

        $updated_post = [
            'ID'           => $post_id,
            'post_title'   => $title,
            'post_content' => $description,
            'meta_input'   => [
                'challenge_type'  => $type,
                'target_value'   => $target_value,
                'meta_value'     => $meta_value,
            ],
        ];

        $result = wp_update_post($updated_post);

        if ($result) {
            wp_send_json_success(['message' => 'Desafío actualizado correctamente.']);
        } else {
            wp_send_json_error('No se pudo actualizar el desafío. Inténtalo de nuevo.');
        }

        wp_die();
    }

    /**
     * Maneja la llamada AJAX para eliminar un desafío desde el backend.
     */
    public function handle_ajax_delete_challenge()
    {
        check_ajax_referer('manage_challenges_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        $post_id = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('ID de desafío no válido.');
        }

        $result = wp_delete_post($post_id, true); // `true` fuerza la eliminación permanente (va a la papelera)

        if ($result) {
            wp_send_json_success(['message' => 'Desafío eliminado correctamente.']);
        } else {
            wp_send_json_error('No se pudo eliminar el desafío. Inténtalo de nuevo.');
        }

        wp_die();
    }


    /**
     * Añade el menú de gestión de FAQs al panel de administración.
     */
    public function add_faq_menu()
    {
        add_menu_page(
            'Gestión de FAQs de Mi App', // Título de la página
            'Carbo Cycling FAQ',                 // Texto del menú
            'edit_posts',                   // <-- Capacidad más permisiva (Editores, Autores, etc.)
            'mi-app-faq-management',    // Slug del menú (único)
            [$this, 'render_faq_management_page'], // Función que renderiza la página
            'dashicons-editor-help',      // Icono de ayuda
            7                              // Posición en el menú (debajo del principal)
        );
    }

    /**
     * Renderiza la página de gestión de FAQs.
     */
    public function render_faq_management_page()
    {
        // Llama a la función que ya creaste para mostrar el contenido de la pestaña
        $this->render_faq_management_tab();
    }

    /**
     * Función que se ejecuta al activar el plugin.
     */
    public function activate()
    {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
        $this->create_competition_table();
        $this->create_recipe_table();
        $this->create_nutrition_plan_table();
        $this->create_challenges_tables();
    }

    /**
     * Carga el dominio de texto del plugin para la traducción.
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'mi-app-plugin',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Crea el menú de administración para el plugin.
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'Mi App Plugin',           // Título de la página
            'Carbo Cycling',                 // Texto del menú
            'manage_options',          // Capacidad requerida
            'carbo-cycling-settings', // Slug del menú
            [$this, 'render_admin_settings_page'], // Función que renderiza la página
            'dashicons-admin-generic', // Icono
            6                         // Posición en el menú
        );
    }

    /**
     * Registra las opciones del plugin usando la API de Ajustes de WordPress.
     */
    public function register_settings()
    {
        // Opciones de Personalización Visual
        register_setting('mi_app_visual_settings', 'mi_app_primary_color');
        register_setting('mi_app_visual_settings', 'mi_app_headings_font');
        register_setting('mi_app_visual_settings', 'mi_app_body_font');

        //FAQ
        register_setting('mi_app_faq_settings', 'mi_app_faq_content');
        
        // Opciones de Configuración de API GEMINI IA
        register_setting('mi_app_api_settings', 'mi_app_gemini_api_key');
        
        // Opciones de Configuración API Strava    
        register_setting('mi_app_api_settings', 'mi_app_strava_client_id');
        register_setting('mi_app_api_settings', 'mi_app_strava_client_secret');

        // AÑADE ESTA LÍNEA PARA WEATHERAPI
        register_setting('mi_app_api_settings', 'mi_app_openweathermap_api_key');
        register_setting('mi_app_api_settings', 'mi_app_weather_days');

        //AÑADE LA API DE OPENCAGE PARA EL TIEMPO
        register_setting('mi_app_api_settings', 'mi_app_opencage_api_key');
    }

    /**
     * Encola la hoja de estilos compartida para todas las páginas del plugin.
     */
     public function enqueue_shared_plugin_styles()
     {
         // Solo cargar en las páginas de nuestro plugin
         if (is_page('mi-app-dashboard') || is_page('mi-app-competition') || is_page('mi-app-recetas') || is_page('mi-app-perfil') || is_page('faq')) {
         wp_enqueue_style(
         'mi-app-shared-styles',
         plugin_dir_url(__FILE__) . 'mi-app-styles.css',
         [],
         '1.0.0'
         );
         }
     }


    /**
     * Renderiza la página principal de ajustes con pestañas.
     */
    public function render_admin_settings_page()
    {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'visual';
        ?>
        <div class="wrap">
            <h1>Panel de Opciones </h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=carbo-cycling-settings&tab=visual" class="nav-tab <?php echo $active_tab == 'visual' ? 'nav-tab-active' : ''; ?>">
                    Personalización Visual
                </a>
                <a href="?page=carbo-cycling-settings&tab=users" class="nav-tab <?php echo $active_tab == 'users' ? 'nav-tab-active' : ''; ?>">
                    Gestión de Usuarios
                </a>
                <a href="?page=carbo-cycling-settings&tab=api" class="nav-tab <?php echo $active_tab == 'api' ? 'nav-tab-active' : ''; ?>">
                    Configuración de API
                </a>
                
            </nav>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'visual':
                        $this->render_visual_tab();
                        break;
                    case 'users':
                        $this->render_users_tab();
                        break;
                    case 'api':
                        $this->render_api_tab();
                        break;
                    case 'faq':
                        $this->render_faq_tab();
                        break;
                    case 'challenges':
                        $this->render_challenges_tab();
                        break;
                    
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el contenido de la pestaña de Gestión de FAQ con JavaScript funcional.
     */
    public function render_faq_management_tab()
    {
        ?>
        <h2>Gestión de Preguntas Frecuentes (FAQ)</h2>
        <p>Aquí puedes añadir y gestionar las preguntas individuales que aparecerán en la página de FAQ.</p>
        <a href="#" id="add-faq-btn" class="button button-primary">+ Añadir Nueva Pregunta</a>

        <div id="faq-list-container">
            <?php
            // Usamos WP_Query para obtener las FAQs
            $args = [
                'post_type'      => 'mi_app_faq',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ];
            
            $faqs_query = new WP_Query($args);

            if ($faqs_query->have_posts()) :
                while ($faqs_query->have_posts()) : $faqs_query->the_post();
                    $faq_id = get_the_ID();
                    $faq_title = get_the_title();
                    $faq_content = get_the_content();
            ?>
                    <div class="faq-item" data-faq-id="<?php echo $faq_id; ?>" data-faq-content="<?php echo esc_attr(wpautop($faq_content)); ?>">
                        <h3 class="faq-title"><?php echo esc_html($faq_title); ?></h3>
                        <div class="faq-actions">
                            <button class="button edit-faq-btn">Editar</button>
                            <button class="button delete-faq-btn">Eliminar</button>
                        </div>
                    </div>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p>No hay preguntas frecuentes creadas o no se han podido encontrar.</p>';
            endif;
            ?>
        </div>

        <!-- Modal para Añadir FAQ -->
        <div id="add-faq-modal" class="faq-modal" style="display: none;">
            <div class="faq-modal-content">
                <span class="faq-close-modal">&times;</span>
                <h2>Añadir Nueva Pregunta</h2>
                <form id="add-faq-form">
                    <label for="new-faq-title">Título de la Pregunta</label>
                    <input type="text" id="new-faq-title" required>
                    <label for="new-faq-content">Respuesta</label>
                    <?php
                    wp_editor('', 'new-faq-content', [
                        'textarea_name' => 'new_faq_content',
                        'media_buttons' => false,
                        'textarea_rows' => 10,
                        'teeny'         => true,
                    ]);
                    ?>
                    <button type="submit" class="button button-primary">Guardar Pregunta</button>
                </form>
            </div>
        </div>

        <!-- Modal para Editar FAQ -->
        <div id="edit-faq-modal" class="faq-modal" style="display: none;">
            <div class="faq-modal-content">
                <span class="faq-close-modal">&times;</span>
                <h2>Editar Pregunta</h2>
                <form id="edit-faq-form">
                    <input type="hidden" id="edit-faq-id">
                    <label for="edit-faq-title">Título de la Pregunta</label>
                    <input type="text" id="edit-faq-title" required>
                    <label for="edit-faq-content">Respuesta</label>
                    <?php
                    wp_editor('', 'edit-faq-content', [
                        'textarea_name' => 'edit_faq_content',
                        'media_buttons' => false,
                        'textarea_rows' => 10,
                        'teeny'         => true,
                    ]);
                    ?>
                    <button type="submit" class="button button-primary">Actualizar Pregunta</button>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            console.log('FAQ JS: Script cargado y listeners listos.'); // Mensaje de depuración

            // 1. MANEJO DE MODALES (común para todos)
            function openModal(modalId) {
                $(modalId).show();
            }
            function closeModal(modalId) {
                $(modalId).hide();
            }
            $('.faq-close-modal').on('click', function() {
                closeModal('#add-faq-modal');
                closeModal('#edit-faq-modal');
            });

            // 2. FUNCIÓN PARA RECARGAR LA LISTA
            function refreshFaqList() {
                console.log('FAQ JS: Recargando la página para mostrar los cambios...');
                location.reload();
            }

            // 3. LISTENER ESPECÍFICO PARA EL BOTÓN "AÑADIR" (que está fuera del contenedor)
            $('#add-faq-btn').on('click', function(e) {
                e.preventDefault();
                console.log('FAQ JS: Clic en Añadir FAQ.');
                openModal('#add-faq-modal');
            });

            // 4. LISTENER DE DELEGACIÓN PARA "EDITAR" y "ELIMINAR" (que están dentro del contenedor)
            $('#faq-list-container').on('click', 'button', function(e) {
                e.preventDefault();
                var button = $(this);
                var faqItem = button.closest('.faq-item');

                if (button.hasClass('edit-faq-btn')) {
                    var faqId = faqItem.data('faq-id');
                    var faqTitle = faqItem.find('.faq-title').text();
                    var faqContent = faqItem.data('faq-content');

                    console.log('FAQ JS: Clic en Editar FAQ ID ' + faqId);

                    $('#edit-faq-id').val(faqId);
                    $('#edit-faq-title').val(faqTitle);
                    
                    // Para el editor de WordPress, necesitamos un pequeño truco
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('edit-faq-content')) {
                        tinyMCE.get('edit-faq-content').setContent(faqContent);
                    } else {
                        $('#edit-faq-content').val(faqContent);
                    }
                    openModal('#edit-faq-modal');

                } else if (button.hasClass('delete-faq-btn')) {
                    var faqId = faqItem.data('faq-id');
                    console.log('FAQ JS: Clic en Eliminar FAQ ID ' + faqId);

                    if (confirm('¿Estás seguro de que quieres eliminar esta pregunta?')) {
                        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                            action: 'delete_faq',
                            faq_id: faqId,
                            nonce: '<?php echo wp_create_nonce('faq_nonce'); ?>'
                        }, function(response) {
                            if (response.success) {
                                refreshFaqList();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        });
                    }
                }
            });

            // 5. MANEJO DE FORMULARIOS (AJAX)
            $('#add-faq-form').on('submit', function(e) {
                e.preventDefault();
                var content = (typeof tinyMCE !== 'undefined' && tinyMCE.get('new-faq-content')) ? tinyMCE.get('new-faq-content').getContent() : $('#new-faq-content').val();
                var formData = {
                    action: 'add_faq',
                    title: $('#new-faq-title').val(),
                    content: content,
                    nonce: '<?php echo wp_create_nonce('faq_nonce'); ?>'
                };
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
                    if (response.success) {
                        closeModal('#add-faq-modal');
                        refreshFaqList();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            $('#edit-faq-form').on('submit', function(e) {
                e.preventDefault();
                var content = (typeof tinyMCE !== 'undefined' && tinyMCE.get('edit-faq-content')) ? tinyMCE.get('edit-faq-content').getContent() : $('#edit-faq-content').val();
                var formData = {
                    action: 'edit_faq',
                    faq_id: $('#edit-faq-id').val(),
                    title: $('#edit-faq-title').val(),
                    content: content,
                    nonce: '<?php echo wp_create_nonce('faq_nonce'); ?>'
                };
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
                    if (response.success) {
                        closeModal('#edit-faq-modal');
                        refreshFaqList();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
    </script>

        <style>
        .faq-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .faq-title { margin: 0 0 10px 0; }
        .faq-actions { margin-top: 10px; }
        .faq-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .faq-modal-content { background-color: #fff; margin: 5% auto; padding: 20px; width: 90%; max-width: 700px; border-radius: 8px; }
        .faq-close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        </style>
        <?php
    }


/**
 * Renderiza el contenido de la pestaña de Gestión de Desafíos (versión con depuración forzada).
 */
 public function render_challenges_tab()
 {
 if (!current_user_can('manage_options')) {
    wp_die('Lo siento, no tienes permisos para acceder a esta página.');
}
     ?>
     <h2>Gestión de Desafíos y Logros</h2>
     <p>Aquí puedes crear y gestionar los desafíos para motivar a los usuarios.</p>

     <a href="#" id="add-challenge-btn" class="button button-primary">+ Añadir Nuevo Desafío</a>

     <div id="challenges-list-container">
     <?php
     global $wpdb;
     $table_name = $wpdb->prefix . 'mi_app_challenges';
     $challenges = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC");

     if ($challenges):
     foreach ($challenges as $challenge):
     ?>
     <div class="challenge-item" data-challenge-id="<?php echo $challenge->id; ?>">
     <h3 class="challenge-title"><?php echo esc_html($challenge->title); ?></h3>
     <p class="challenge-description"><?php echo esc_html($challenge->description); ?></p>
     <div class="challenge-meta">
     <span class="challenge-type">Tipo: <?php echo esc_html(ucfirst($challenge->challenge_type)); ?></span>
     <span class="challenge-target">Objetivo: <?php echo esc_html($challenge->target_value); ?></span>
     </div>
     <div class="challenge-actions">
     <button class="button edit-challenge-btn">Editar</button>
     <button class="button delete-challenge-btn">Eliminar</button>
     </div>
     </div>
     <?php
     endforeach;
     else:
     echo '<p>No hay desafíos creados.</p>';
     endif;
     ?>
     </div>

     <!-- Modal para Añadir/Editar Desafío -->
     <div id="challenge-modal" class="challenge-modal" style="display: none;">
     <div class="challenge-modal-content">
     <span class="challenge-close-modal">&times;</span>
     <h2>Añadir Nuevo Desafío</h2>
     <form id="challenge-form">
     <input type="hidden" id="challenge-id" name="challenge_id">
     <div class="form-group">
     <label for="challenge-title">Título del Desafío</label>
     <input type="text" id="challenge-title" name="challenge-title" required>
     </div>
     <div class="form-group">
     <label for="challenge-description">Descripción</label>
     <textarea id="challenge-description" name="challenge-description" rows="4" required></textarea>
     </div>
     <div class="form-group">
     <label for="challenge-type">Tipo de Desafío</label>
     <select id="challenge-type" name="challenge-type">
     <option value="distance">Distancia</option>
     <option value="elevation">Desnivel</option>
     <option value="rides">Número de Rutas</option>
     <option value="custom">Personalizado</option>
     </select>
     </div>
     <div class="form-group">
     <label for="challenge-target">Valor del Objetivo</label>
     <input type="number" id="challenge-target" name="challenge-target" required>
     <p class="description">Ej: 100 (para km), 5000 (para metros), 30 (para rutas).</p>
     </div>
     <div class="form-group">
     <label for="challenge-meta">Valor Meta (Opcional)</label>
     <input type="text" id="challenge-meta" name="challenge-meta" placeholder="Ej: competition_ids: 1,5,12">
     <p class="description">Un valor extra para lógica personalizada (ej. IDs de competiciones a completar).</p>
     </div>
     <button type="submit" class="button button-primary">Guardar Desafío</button>
     </form>
     </div>
         <!-- Cargar el JavaScript compartido para los desafíos -->
    <script src="<?php echo plugin_dir_url(__FILE__); ?>assets/challenges.js" defer></script>
     </div>

     <style>
     .challenge-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; background-color: #f9f9f9; }
     .challenge-title { margin: 0 0 10px 0; }
     .challenge-description { margin-bottom: 10px; }
     .challenge-meta { font-size: 0.9em; color: #666; }
     .challenge-actions { margin-top: 15px; }
     .challenge-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
     .challenge-modal-content { background-color: #fff; margin: 5% auto; padding: 20px; width: 90%; max-width: 600px; border-radius: 8px; }
     .challenge-close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
     </style>
     <?php
     }

    /**
     * Maneja la llamada AJAX para eliminar una FAQ.
     */
     public function handle_delete_faq_ajax()
     {
         // 1. Verificar el nonce de seguridad
         if (!wp_verify_nonce($_POST['delete_faq_nonce'], 'delete_faq_' . $_POST['faq_id'])) {
         wp_send_json_error('Error de seguridad. Nonce inválido.');
         }

         // 2. Verificar permisos del usuario
         if (!current_user_can('delete_posts')) {
         wp_send_json_error('No tienes permisos para eliminar contenido.');
         }

         // 3. Obtener y validar el ID del post a eliminar
         $post_id = isset($_POST['faq_id']) ? intval($_POST['faq_id']) : 0;
         if ($post_id === 0) {
         wp_send_json_error('ID de FAQ no válido.');
         }

         // 4. Eliminar el post
         $result = wp_delete_post($post_id, true); // true para enviarlo a la papelera de forma permanente

         if ($result === false) {
         wp_send_json_error('No se pudo eliminar la FAQ.');
         }

         // 5. Enviar respuesta de éxito
         wp_send_json_success('FAQ eliminada correctamente.');
    }


    /**
     * Renderiza el contenido de la pestaña de Personalización Visual.
     */
    public function render_visual_tab()
    {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('mi_app_visual_settings'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="mi_app_primary_color">Color Principal</label>
                        </th>
                        <td>
                            <?php $this->render_color_field(); ?>
                            <p class="description">Color para botones, enlaces y elementos destacados.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_app_headings_font">Fuente de Títulos</label>
                        </th>
                        <td>
                            <?php $this->render_font_field(['label_for' => 'mi_app_headings_font']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_app_body_font">Fuente de Textos</label>
                        </th>
                        <td>
                            <?php $this->render_font_field(['label_for' => 'mi_app_body_font']); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button('Guardar Cambios Visuales'); ?>
        </form>
        <?php
    }

    /**
     * Renderiza el contenido de la pestaña de Gestión de Usuarios.
     */
    public function render_users_tab()
    {
        ?>
        <h2>Gestión de Usuarios del Plugin</h2>
        <p>Aquí puedes ver todos los usuarios registrados y su estado dentro de la aplicación.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Estado en la App</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = get_users(['orderby' => 'display_name']);
                foreach ($users as $user) {
                    $is_strava_connected = get_user_meta($user->ID, 'strava_access_token', true) ? 'Conectado' : 'No Conectado';
                    echo "<tr>";
                    echo "<td>{$user->ID}</td>";
                    echo "<td>{$user->display_name}</td>";
                    echo "<td>{$user->user_email}</td>";
                    echo "<td><span class='dashboard-" . ($is_strava_connected == 'Conectado' ? 'success' : 'error') . "'>" . $is_strava_connected . "</span></td>";
                    echo "<td><a href='" . get_edit_user_link($user->ID) . "' class='button'>Editar Perfil WP</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <?php
    }

        /**
         * Registra los tipos de contenido personalizados para el plugin.
         */
         public function register_my_app_plugin_post_types()
         {
             // Registrar el tipo de contenido para las FAQs
             register_post_type('mi_app_faq', [
             'label' => __('FAQs de Mi App', 'mi-app-plugin'),
             'public' => true,
             'has_archive' => true,
             'supports' => ['title', 'editor', 'excerpt'],
             'show_in_menu' => false, // No lo mostramos en el menú principal, lo gestionamos desde nuestra página
             'rewrite' => ['slug' => 'mi-app-faq'],
             'show_ui' => true, // Lo mostramos en nuestra página de administración
             'capability_type' => 'post',
             'map_meta_cap' => true,
             ]);
         }


    /**
     * Registra el "Custom Post Type" para las FAQs.
     */
    public function register_faq_post_type()
    {
        $labels = [
            'name' => _x('Preguntas Frecuentes', 'mi-app-plugin'),
            'singular_name' => _x('Pregunta Frecuente', 'mi-app-plugin'),
            'menu_name' => _x('FAQs de Mi App', 'mi-app-plugin'),
            'name_admin_bar' => _x('FAQ', 'mi-app-plugin'),
            'add_new' => _x('Añadir Nueva', 'FAQ', 'mi-app-plugin'),
            'add_new_item' => _x('Añadir Nueva Pregunta', 'FAQ', 'mi-app-plugin'),
            'edit_item' => _x('Editar Pregunta', 'FAQ', 'mi-app-plugin'),
            'new_item' => _x('Nueva Pregunta', 'FAQ', 'mi-app-plugin'),
            'view_item' => _x('Ver Pregunta', 'FAQ', 'mi-app-plugin'),
            'search_items' => _x('Buscar Preguntas', 'FAQ', 'mi-app-plugin'),
            'not_found' => _x('No se encontraron preguntas.', 'mi-app-plugin'),
            'not_found_in_trash' => _x('No se encontraron preguntas en la papelera.', 'mi-app-plugin'),
        ];

        $args = [
            'label' => 'FAQs de Mi App',
            'labels' => $labels,
            'public' => true,
            'has_archive' => false, // No queremos una página de archivo, usamos nuestra propia página
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true, // Mostrar en el menú de admin de WordPress
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => ['slug' => 'mi-app-faq', 'with_front' => false],
            'query_var' => true,
            'supports' => ['title', 'editor', 'excerpt'],
            'menu_icon' => 'dashicons-format-status',
            'show_in_rest' => true,
        ];

        register_post_type('mi_app_faq', $args);
    }

    /**
     * Añade un meta box para el estado de la FAQ (Activa/Inactiva).
     */
    public function add_faq_meta_boxes()
    {
        add_meta_box('faq_status_meta_box', 'Estado de la Pregunta', 'mi_app_faq', 'side', 'default');
    }

    /**
     * Muestra el contenido del meta box.
     */
    public function show_faq_status_meta_box($post)
    {
        $status = get_post_meta($post->ID, '_faq_active_status', true);
        wp_nonce_field('faq_status_nonce', 'faq_status_nonce');
        ?>
        <label>
            <input type="checkbox" name="faq_active_status" value="1" <?php checked($status, '1'); ?> />
            Publicar esta pregunta en la página de FAQ
        </label>
        <?php
    }

    /**
     * Guarda el estado de la FAQ al guardar el post.
     */
    public function save_faq_post_meta($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['faq_status_nonce']) || !wp_verify_nonce($_POST['faq_status_nonce'], 'faq_status_nonce')) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['faq_active_status'])) {
            update_post_meta($post_id, '_faq_active_status', '1');
        } else {
            update_post_meta($post_id, '_faq_active_status', '0');
        }
    }

    /**
     * Renderiza el contenido de la pestaña de Gestión de FAQ.
     */
    public function render_faq_tab()
    {
        ?>
        <h2>Gestión de Preguntas Frecuentes (FAQ)</h2>
        <p>Desde aquí puedes gestionar las preguntas que aparecerán en la página de FAQ.</p>

        <h3>Contenido Principal de la Página</h3>
        <form method="post" action="options.php">
            <?php
            settings_fields('mi_app_faq_settings');
            submit_button('Guardar Contenido Principal', 'primary');
            ?>
        </form>

        <hr>

        <h3>Añadir Nueva Pregunta</h3>
        <p>Haz clic en el siguiente botón para crear una nueva pregunta frecuente. Podrás activarla o desactivarla más tarde.</p>
        <a href="<?php echo admin_url('post-new.php?post_type=mi_app_faq'); ?>" class="button button-primary">+ Añadir Nueva FAQ</a>
        <?php
    }



    /**
     * Renderiza el contenido de la pestaña de Configuración de API.
     */
    public function render_api_tab()
    {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('mi_app_api_settings'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="mi_app_gemini_api_key">Clave de API (Google Gemini)</label>
                        </th>
                        <td>
                            <?php $this->render_api_key_field(); ?>
                            <p class="description">Introduce tu clave de API para la funcionalidad de IA.</p>
                            <p class="description">Obtén tu API key gratuita en <a target="blank" href="https://makersuite.google.com/app/apikey">Google AI Studio</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_app_strava_client_id">Client ID (Strava)</label>
                        </th>
                        <td>
                            <?php $this->render_strava_client_id_field(); ?>
                            <p class="description">Introduce el Client ID de tu aplicación de Strava.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_app_strava_client_secret">Client Secret (Strava)</label>
                        </th>
                        <td>
                            <?php $this->render_strava_client_secret_field(); ?>
                            <p class="description">Introduce el Client Secret de tu aplicación de Strava.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_app_openweathermap_api_key">Clave API (OpenCage Geocoder)</label>
                        </th>
                        <td>
                        <?php $this->render_opencage_key_field(); ?> 
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                        <label for="mi_app_weather_days">Días del Pronóstico del Tiempo</label>
                        </th>
                        <td>
                        <?php $this->render_weather_days_field(); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mi_app_openweathermap_api_key">Clave de API (WeatherAPI)</label>
                        </th>
                        <td>
                            <?php $this->render_openweathermap_key_field(); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button('Guardar Configuración de API'); ?>
        </form>
        <?php
    }

    /**
     * Funciones para renderizar los campos de formulario.
     */
    public function render_color_field()
    {
        $color = get_option('mi_app_primary_color', '#0073aa');
        echo "<input type='color' id='mi_app_primary_color' name='mi_app_primary_color' value='" . esc_attr($color) . "' class='regular-text'>";
    }

    public function render_font_field($args)
    {
        $field_name = $args['label_for'];
        $font = get_option($field_name, 'Arial, sans-serif');
        $fonts = [
            'Arial, sans-serif' => 'Arial',
            'Helvetica, sans-serif' => 'Helvetica',
            'Georgia, serif' => 'Georgia',
            'Times New Roman, serif' => 'Times New Roman',
            'Courier New, monospace' => 'Courier New',
            'Verdana, sans-serif' => 'Verdana',
            'Roboto, sans-serif' => 'Roboto',
            'Open Sans, sans-serif' => 'Open Sans',
            'Lato, sans-serif' => 'Lato',
            'Montserrat, sans-serif' => 'Montserrat',
        ];
        echo "<select id='" . esc_attr($field_name) . "' name='" . esc_attr($field_name) . "' class='regular-text'>";
        foreach ($fonts as $value => $label) {
            echo "<option value='" . esc_attr($value) . "' " . selected($font, $value, false) . ">" . esc_html($label) . "</option>";
        }
        echo "</select>";
    }

    public function render_api_key_field()
    {
        $key = get_option('mi_app_gemini_api_key');
        echo "<input type='password' id='mi_app_gemini_api_key' name='mi_app_gemini_api_key' value='" . esc_attr($key) . "' class='regular-text' placeholder='Introduce tu clave de API aquí'>";
    }

    public function render_strava_client_id_field()
    {
        $client_id = get_option('mi_app_strava_client_id');
        echo "<input type='text' id='mi_app_strava_client_id' name='mi_app_strava_client_id' value='" . esc_attr($client_id) . "' class='regular-text' placeholder='Introduce tu Client ID de Strava aquí'>";
    }

    public function render_strava_client_secret_field()
    {
        $client_secret = get_option('mi_app_strava_client_secret');
        echo "<input type='password' id='mi_app_strava_client_secret' name='mi_app_strava_client_secret' value='" . esc_attr($client_secret) . "' class='regular-text' placeholder='Introduce tu Client Secret de Strava aquí'>";
    }

    /**
     * Crea la tabla personalizada para las competiciones si no existe.
     */
    private function create_competition_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_app_competiciones';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            nombre varchar(255) NOT NULL,
            fecha date NOT NULL,
            distancia DECIMAL(10, 3) NOT NULL,          
            desnivel int NOT NULL,
            total_distance_meters DECIMAL(10, 3) DEFAULT 0 NOT NULL,                                        
            total_time_seconds INT DEFAULT 0 NOT NULL,                       
            gpx_file_url varchar(255) DEFAULT '' NULL,
            gpx_name varchar(255) DEFAULT '' NULL,
            gpx_min_elevation float DEFAULT NULL,
            gpx_max_elevation float DEFAULT NULL,   
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Crea la tabla personalizada para las recetas si no existe.
     */
    private function create_recipe_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_app_recipes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NULL,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            ingredients text DEFAULT NULL,
            instructions text DEFAULT NULL,
            carbs float DEFAULT NULL,
            protein float DEFAULT NULL,
            fats float DEFAULT NULL,
            calories int DEFAULT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }


    /**
     * Crea las tablas personalizadas para los Desafíos y el progreso de los usuarios.
     */
     private function create_challenges_tables()
     {
     global $wpdb;
     $charset_collate = $wpdb->get_charset_collate();

     // Tabla 1: wp_mi_app_challenges (para guardar los desafíos)
     $table_challenges = $wpdb->prefix . 'mi_app_challenges';
     $sql_challenges = "CREATE TABLE $table_challenges (
     id mediumint(9) NOT NULL AUTO_INCREMENT,
     title varchar(255) NOT NULL,
     description text NOT NULL,
     challenge_type ENUM('distance', 'elevation', 'rides', 'custom') NOT NULL DEFAULT 'custom',
     target_value int NOT NULL, -- Ej: 100 km, 5000m, 30 rides
     meta_value varchar(255) DEFAULT NULL, -- Un valor extra, ej: 'competition_ids'
     created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
     PRIMARY KEY (id)
     ) $charset_collate;";

     // Tabla 2: wp_mi_app_user_challenge_progress (para guardar qué desafío ha completado cada usuario)
     $table_progress = $wpdb->prefix . 'mi_app_user_challenge_progress';
     $sql_progress = "CREATE TABLE $table_progress (
     id mediumint(9) NOT NULL AUTO_INCREMENT,
     user_id bigint(20) UNSIGNED NOT NULL,
     challenge_id mediumint(9) NOT NULL,
     completed_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
     PRIMARY KEY (id),
     UNIQUE KEY user_challenge_unique (user_id, challenge_id),
     KEY challenge_id (challenge_id),
     FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
     FOREIGN KEY (challenge_id) REFERENCES {$table_challenges}(id) ON DELETE CASCADE
     ) $charset_collate;";

     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
     dbDelta($sql_challenges);
     dbDelta($sql_progress);
     }


    /**
     * Añade el botón de "Análisis" en la página de competición
     */
    public function add_analysis_button_to_competition_page()
    {
        // Esta función ahora está vacía porque hemos movido toda la lógica a get_analysis_script_inline()
        // Pero la mantenemos para evitar errores si se llama desde otro lugar
    }

    /**
     * Maneja la llamada AJAX para analizar un GPX con Gemini
     */
    public function handle_analyze_gpx()
    {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'analyze_gpx_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
        }

        $user_id = get_current_user_id();
        $competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;

        if (!$user_id || !$competition_id) {
            wp_send_json_error('Faltan datos del usuario o de la competición.');
        }

        global $wpdb;
        $plan_table = $wpdb->prefix . 'mi_app_nutrition_plans';

        // *** NUEVA LÓGICA: Comprobar si ya existe un análisis guardado ***
        $existing_analysis = $wpdb->get_row($wpdb->prepare(
            "SELECT plan_data FROM {$plan_table} WHERE user_id = %d AND competition_id = %d AND plan_type = %s",
            $user_id,
            $competition_id,
            'gpx_analysis'
        ));

        if ($existing_analysis) {
            // Si existe, devolver el análisis guardado inmediatamente
            wp_send_json_success(['html' => $existing_analysis->plan_data, 'has_plan' => true]);
        }

        // *** Si no existe, continuar con el proceso original ***
        // Obtener datos de la competición
        $comp_table = $wpdb->prefix . 'mi_app_competiciones';
        $competition = $wpdb->get_row($wpdb->prepare("SELECT gpx_file_url, distancia, desnivel FROM {$comp_table} WHERE id = %d AND user_id = %d", $competition_id, $user_id));

        if (!$competition || !$competition->gpx_file_url) {
            wp_send_json_error('No se encontró el GPX para esta competición.');
        }

        // Obtener datos del usuario
        $user_data = [
            'peso' => get_user_meta($user_id, 'mi_app_peso', true),
            'altura' => get_user_meta($user_id, 'mi_app_altura', true),
            'edad' => get_user_meta($user_id, 'mi_app_edad', true),
            'genero' => get_user_meta($user_id, 'mi_app_genero', true),
            'ftp' => get_user_meta($user_id, 'mi_app_ftp', true),
            'nivel_forma' => get_user_meta($user_id, 'mi_app_nivel_forma', true),
            'objetivo' => get_user_meta($user_id, 'mi_app_objetivo', true),
            'consumo_objetivo' => get_user_meta($user_id, 'mi_app_consumo_objetivo', true),
            'es_celiaco' => get_user_meta($user_id, 'mi_app_celiaco', true),
            'es_diabetico' => get_user_meta($user_id, 'mi_app_diabetico', true),
        ];

        // Leer el contenido del GPX
        $gpx_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $competition->gpx_file_url);
        if (!file_exists($gpx_path)) {
            wp_send_json_error('El archivo GPX no existe en el servidor.');
        }
        $gpx_content = file_get_contents($gpx_path);

        // Obtener la clave de la API de Gemini
        //$api_key = get_option('mi_app_gemini_api_key');
        $api_key = $this->get_gemini_api_key_for_user();
        if (empty($api_key)) {
            wp_send_json_error('La clave de la API de Gemini no está configurada.');
        }

        // Construir el prompt
        $prompt = $this->build_gpx_analysis_prompt($user_data, $gpx_content, $competition->distancia, $competition->desnivel);

        // Llamar a la API de Gemini
        $response_text = $this->call_gemini_api($api_key, $prompt);

        if (is_wp_error($response_text)) {
            wp_send_json_error('Error al contactar la API de Gemini: ' . $response_text->get_error_message());
        }

        // Formatear la respuesta para mostrarla en HTML
        $html = $this->format_analysis_response($response_text);

        // *** NUEVA LÓGICA: Guardar el análisis recién creado en la base de datos ***
        $wpdb->insert(
            $plan_table,
            [
                'user_id'       => $user_id,
                'competition_id'=> $competition_id,
                'plan_name'     => 'Análisis de Nutrición para ' . $competition->nombre,
                'plan_type'     => 'gpx_analysis',
                'plan_data'     => $html,
                'created_at'    => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        // Comprobar si hay un plan de nutrición guardado para el botón "Generar GPX Nutrición"
        $has_nutrition_plan = !empty($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$plan_table} WHERE competition_id = %d AND user_id = %d AND plan_type = %s",
            $competition_id,
            $user_id,
            'nutrition_plan'
        )));

        wp_send_json_success(['html' => $html, 'has_plan' => $has_nutrition_plan]);

        wp_die();
    }

    /**
     * Construye el prompt para analizar un GPX con Gemini
     */
    private function build_gpx_analysis_prompt($user_data, $gpx_content, $distance, $elevation_gain)
    {
       
        $prompt = "Nutricionista Ciclismo. Genera plan nutricional (paso a paso, Responde únicamente en formato Markdown, Usa encabezados (##, ###), tablas, listas y negritas para estructurar la información) para la ruta GPX adjunta y los datos del ciclista.

RUTA:
- {$distance} km, {$elevation_gain}m D+

DATOS DEL CICLISTA:
- {$user_data['peso']} kg, {$user_data['altura']} cm, {$user_data['edad']} años, {$user_data['genero']}, {$user_data['ftp']} Watts
- Restricciones Celiaco: {$user_data['es_celiaco']} , Diabético: {$user_data['es_diabetico']}
 Objetivo: {$user_data['consumo_objetivo']} CH/h (ajustado a la intensidad), evitar la pájara y optimizar el rendimiento.


INSTRUCCIONES DE ANÁLISIS:
1. Usa los valores de RUTA para distancia y desnivel.
2. Analiza el GPX para determinar: Duración total y Perfil de altitud (identifica las subidas duras y las bajadas de descanso).
3. Calcula la intensidad estimada (METs) según desnivel y peso del ciclista.

FORMATO DE SALIDA (ESTRUCTURA MARKDOWN):

** Análisis y Nutrición para {$competition->nombre} **
Empieza con un párrafo breve y amigable, explicando el objetivo del plan.

**## 🚴 Resumen Técnico según GPX**
Crea una tabla con las siguientes columnas: Dato, Valor, Nota del Nutricionista.
Incluye filas para: Distancia Total, Desnivel Positivo, Tiempo Estimado, Intensidad Estimada (METs), y Consumo Total CH Objetivo.

**## 🍽️ Plan de Nutrición (Cada 30 Min.)**
Añade un breve párrafo explicando la estrategia clave (comida sólida al principio, geles al final, etc.).
Crea una tabla con las siguientes columnas: Tiempo Transcurrido: (00:30, 01:00, etc.), Situación de Carrera: Terreno/Situación, Gramos CH Objetivo (30 min): La cantidad recomendada para ese intervalo. (Sube la cantidad ANTES de las subidas, bájala en descensos pasivos)., Ingesta (Solo nombra el producto, ej: 1 Barrita + Isotónico).

**## Consejos Adicionales**
Usa una lista numerada para dar 1 o 2 consejos clave (ej. entrenamiento intestinal, preparación previa, etc.).


INSTRUCCIONES ADICIONALES:
- Si la ruta es muy larga (>4h), debes priorizar comida sólida al principio y geles/rápida absorción al final.

AQUÍ ESTÁ EL CONTENIDO DEL ARCHIVO GPX:
" . $gpx_content;

        return $prompt;
        //error_log('Prompt: ' . $prompt);
    }



     /**
     * Formatea la respuesta de Gemini para mostrarla en HTML
     */
    /**
     * Convierte el texto Markdown de Gemini en HTML con estilos.
     */
    private function format_analysis_response($markdown_text)
    {
        // Convertir encabezados H2 y H3
        $markdown_text = preg_replace('/^## (.+)$/m', '<h2 class="analysis-h2">$1</h2>', $markdown_text);
        $markdown_text = preg_replace('/^### (.+)$/m', '<h3 class="analysis-h3">$1</h3>', $markdown_text);

        // Convertir negritas
        $markdown_text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $markdown_text);

        // Convertir listas numeradas
        $markdown_text = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', $markdown_text);
        $markdown_text = preg_replace('/(<li>.*<\/li>)/s', '<ul class="analysis-tips-list">$1</ul>', $markdown_text);

        // Convertir tablas
        $lines = explode("\n", $markdown_text);
        $in_table = false;
        $html_output = '';
        $table_rows = [];

        foreach ($lines as $line) {
            if (preg_match('/^\|(.+)\|$/', $line)) {
                $cells = array_map('trim', explode('|', $line));
                $cells = array_filter($cells); // Remove empty elements
                $table_rows[] = '<tr><td>' . implode('</td><td>', $cells) . '</td></tr>';
                $in_table = true;
            } else {
                if ($in_table) {
                    // End of table
                    $html_output .= '<table class="analysis-table">' . implode('', $table_rows) . '</table>';
                    $table_rows = [];
                    $in_table = false;
                }
                $html_output .= $line . "\n";
            }
        }
        // Handle case where file ends with a table
        if ($in_table) {
            $html_output .= '<table class="analysis-table">' . implode('', $table_rows) . '</table>';
        }

        // Convertir saltos de línea restantes a <br>
        $html_output = nl2br($html_output);

        // Envolver todo en un contenedor principal
        $final_html = '<div class="analysis-container">' . $html_output . '</div>';

        return $final_html;
    }

    /**
     * Añade los campos adicionales al perfil de usuario
     */
    public function add_user_profile_fields()
    {
        ?>
        <h3>Datos Físicos y Deportivos</h3>
        <table class="form-table">
            <tr>
                <th><label for="mi_app_altura">Altura (cm)</label></th>
                <td>
                    <input type="number" id="mi_app_altura" name="mi_app_altura" value="<?php echo esc_attr(get_the_author_meta('mi_app_altura', $user->ID)); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="mi_app_genero">Género</label></th>
                <td>
                    <select id="mi_app_genero" name="mi_app_genero">
                        <option value="Masculino" <?php selected(get_the_author_meta('mi_app_genero', $user->ID), 'Masculino'); ?>>Masculino</option>
                        <option value="Femenino" <?php selected(get_the_author_meta('mi_app_genero', $user->ID), 'Femenino'); ?>>Femenino</option>
                        <option value="Otro" <?php selected(get_the_author_meta('mi_app_genero', $user->ID), 'Otro'); ?>>Otro</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="mi_app_nivel_forma">Nivel de Forma</label></th>
                <td>
                    <select id="mi_app_nivel_forma" name="mi_app_nivel_forma">
                        <option value="Principiante" <?php selected(get_the_author_meta('mi_app_nivel_forma', $user->ID), 'Principiante'); ?>>Principiante</option>
                        <option value="Intermedio" <?php selected(get_the_author_meta('mi_app_nivel_forma', $user->ID), 'Intermedio'); ?>>Intermedio</option>
                        <option value="Avanzado" <?php selected(get_the_author_meta('mi_app_nivel_forma', $user->ID), 'Avanzado'); ?>>Avanzado</option>
                        <option value="Profesional" <?php selected(get_the_author_meta('mi_app_nivel_forma', $user->ID), 'Profesional'); ?>>Profesional</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="mi_app_objetivo">Objetivo</label></th>
                <td>
                    <select id="mi_app_objetivo" name="mi_app_objetivo">
                        <option value="Mantener energía" <?php selected(get_the_author_meta('mi_app_objetivo', $user->ID), 'Mantener energía'); ?>>Mantener energía</option>
                        <option value="Evitar hipoglucemia" <?php selected(get_the_author_meta('mi_app_objetivo', $user->ID), 'Evitar hipoglucemia'); ?>>Evitar hipoglucemia</option>
                        <option value="Optimizar rendimiento" <?php selected(get_the_author_meta('mi_app_objetivo', $user->ID), 'Optimizar rendimiento'); ?>>Optimizar rendimiento</option>
                        <option value="Perder peso" <?php selected(get_the_author_meta('mi_app_objetivo', $user->ID), 'Perder peso'); ?>>Perder peso</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="mi_app_consumo_objetivo">Consumo Objetivo de Carbohidratos</label></th>
                <td>
                    <input type="text" id="mi_app_consumo_objetivo" name="mi_app_consumo_objetivo" value="<?php echo esc_attr(get_the_author_meta('mi_app_consumo_objetivo', $user->ID)); ?>" class="regular-text" placeholder="Ej: 45g - 60g por hora" />
                </td>
            </tr>
        </table>
        <?php
    }    


    /**
     * Guarda los campos adicionales del perfil de usuario
     */
    public function save_user_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        update_user_meta($user_id, 'mi_app_altura', $_POST['mi_app_altura']);
        update_user_meta($user_id, 'mi_app_genero', $_POST['mi_app_genero']);
        update_user_meta($user_id, 'mi_app_nivel_forma', $_POST['mi_app_nivel_forma']);
        update_user_meta($user_id, 'mi_app_objetivo', $_POST['mi_app_objetivo']);
        update_user_meta($user_id, 'mi_app_fcmax', $_POST['mi_app_fcmax']);
        update_user_meta($user_id, 'mi_app_consumo_objetivo', $_POST['mi_app_consumo_objetivo']);
    }

    /**
     * Modifica la plantilla de competición para añadir el botón de análisis
     */
    public function modify_competition_template($template_path)
    {
        // Verificar si estamos en la página de competición
        if (get_query_var('mi_app_page') === 'competicion') {
            // Registrar en el log para depuración
            $this->debug_log('Modificando plantilla de competición');
            
            // Añadir el script para el botón de análisis
            add_action('wp_footer', [$this, 'add_analysis_button_to_competition_page']);
            
            // También añadir el script directamente en el pie de página
            add_action('wp_enqueue_scripts', [$this, 'enqueue_analysis_script']);
        }
        
        return $template_path;
    }

    /**
     * Encola el script de análisis en la página de competición
     */
    public function enqueue_analysis_script()
    {
        // Verificar si estamos en la página de competición
        if (get_query_var('mi_app_page') === 'competicion') {
            // Obtener el ID de la competición actual
            $competition_id = isset($_GET['competition_id']) ? intval($_GET['competition_id']) : 0;
            
            // Crear el nonce para seguridad
            $nonce = wp_create_nonce('analyze_gpx_nonce');
            
            // Registrar el script con un identificador único
            wp_register_script(
                'competition-analysis-script', // Cambiado el identificador para evitar conflictos
                '', // Dejamos vacío porque usaremos inline script
                ['jquery'],
                '1.0.1', // Incrementamos la versión
                true
            );
            
            // Localizar el script para pasar variables de PHP a JavaScript
            wp_localize_script(
                'competition-analysis-script',
                'competition_analysis_vars',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'competition_id' => $competition_id,
                    'nonce' => $nonce,
                ]
            );
            
            // Encolar el script
            wp_enqueue_script('competition-analysis-script');
            
            // Añadir el script inline y los estilos básicos para el modal
            wp_add_inline_script('competition-analysis-script', $this->get_analysis_script_inline());
            wp_add_inline_style('competition-analysis-style', $this->get_analysis_modal_styles());
        }
    }

    /**
     * Devuelve los estilos CSS para el modal de análisis
     */
    private function get_analysis_modal_styles()
    {
        ob_start();
        ?>
        #analysis-modal {
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

        #analysis-modal .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 25px;
            border: 1px solid #888;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }

        #analysis-modal .close-modal {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
        }

        #analysis-modal .close-modal:hover,
        #analysis-modal .close-modal:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        
        #analysis-results {
            margin-top: 20px;
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }
        
        #analysis-results table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        #analysis-results th, #analysis-results td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        #analysis-results th {
            background-color: #f2f2f2;
        }
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene el código JavaScript inline para el análisis
     */
    private function get_analysis_script_inline()
    {
        ob_start();
        ?>
        jQuery(document).ready(function($) {
            // Crear el modal para mostrar los resultados si no existe
            if (!$('#analysis-modal').length) {
                $('body').append(`
                    <div id="analysis-modal">
                        <div class="modal-content">
                            <span class="close-modal">&times;</span>
                            <h2>Análisis</h2>
                            <div id="analysis-results">
                                <!-- Aquí se mostrarán los resultados del análisis -->
                            </div>
                        </div>
                    </div>
                `);
            }

            // Función para añadir el botón de análisis
            function addAnalysisButton() {
                // Si el botón ya existe, no hacer nada
                if ($('.analysis-button').length) {
                    return;
                }
                
                // Crear el botón de análisis
                var analysisButton = $('<button type="button" class="button analysis-button">Análisis</button>');
                
                // Intentar añadir después del botón de mapa o antes del de GPX Nutrición
                var mapButton = $('.view-map-button');
                var nutritionGpxButton = $('#generate-nutrition-gpx-btn');

                if (mapButton.length && nutritionGpxButton.length) {
                    // Si ambos existen, ponlo en medio
                    mapButton.after(analysisButton);
                } else if (mapButton.length) {
                    // Si solo existe el de mapa, ponlo después
                    mapButton.after(analysisButton);
                } else if (nutritionGpxButton.length) {
                    // Si solo existe el de GPX, ponlo antes
                    nutritionGpxButton.before(analysisButton);
                } else {
                    // Si no encuentra ninguno, añadir al final de la tabla de información
                    $('.competition-info table').append('<tr><td colspan="2"></td></tr>');
                    $('.competition-info table tr:last td').append(analysisButton);
                }
            }
            
            // Usar MutationObserver para detectar cuando los botones se añaden al DOM
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        addAnalysisButton();
                    }
                });
            });

            var config = { childList: true, subtree: true };
            observer.observe(document.body, config);

            // Intentar añadir el botón inmediatamente
            addAnalysisButton();
            
            // Manejar el clic en el botón de análisis
            $(document).on('click', '.analysis-button', function(e) {
                e.preventDefault();
                
                var competitionId = competition_analysis_vars.competition_id;
                var nonce = competition_analysis_vars.nonce;
                
                if (!competitionId) {
                    alert('Error: No se pudo identificar el ID de la competición.');
                    return;
                }
                
                // Mostrar loader
                $('#analysis-results').html('<p>Analizando la ruta con IA, por favor espera...</p>');
                $('#analysis-modal').show();
                
                // Realizar la llamada AJAX
                $.ajax({
                    url: competition_analysis_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'analyze_gpx',
                        competition_id: competitionId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#analysis-results').html(response.data.html);
                        } else {
                            $('#analysis-results').html('<div class="error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', status, error);
                        $('#analysis-results').html('<div class="error"><p>Error de conexión con el servidor. Por favor, inténtalo de nuevo.</p></div>');
                    }
                });
            });
            
            // Cerrar el modal
            $(document).on('click', '.close-modal', function() {
                $('#analysis-modal').hide();
            });

            // Cerrar el modal si se hace clic fuera del contenido
            $(window).on('click', function(event) {
                if ($(event.target).is('#analysis-modal')) {
                    $('#analysis-modal').hide();
                }
            });
        });
        <?php
        return ob_get_clean();
    }


    //****************************************************/

    /**
     * Crea la tabla personalizada para los planes nutricionales si no existe.
     */
    private function create_nutrition_plan_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_app_nutrition_plans';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            competition_id mediumint(9) NOT NULL,
            plan_name varchar(255) NOT NULL,
            plan_type ENUM('nutrition_plan', 'gpx_analysis') NOT NULL DEFAULT 'nutrition_plan',
            plan_data longtext NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_comp_unique (user_id, competition_id, plan_type), -- Asegura que solo haya un plan por usuario/competición
            KEY competition_id (competition_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Añade las reglas de reescritura para nuestras páginas virtuales.
     */
    public function add_rewrite_rules()
    {
        add_rewrite_rule(
            '^mi-app/login/?$',
            'index.php?mi_app_page=login',
            'top'
        );
        add_rewrite_rule(
            '^mi-app/dashboard/?$',
            'index.php?mi_app_page=dashboard',
            'top'
        );
        add_rewrite_rule(
            '^mi-app/competicion/?$',
            'index.php?mi_app_page=competicion',
            'top'
        );
        add_rewrite_rule(
            '^mi-app/recetas/?$',
            'index.php?mi_app_page=recetas',
            'top'
        );
        add_rewrite_rule(
            '^mi-app/perfil/?$',
            'index.php?mi_app_page=perfil',
            'top'
        );
        add_rewrite_rule(
            '^mi-app/strava-callback/?$',
            'index.php?mi_app_page=strava-callback',
            'top'
        );
        add_rewrite_rule(
            '^mi-app/mapa/?$',
            'index.php?mi_app_page=mapa',
            'top'
        );
        add_rewrite_rule(
            '^mi-app/strava-activities/?$',
            'index.php?mi_app_page=strava-activities',
            'top'
        );
        add_rewrite_rule(
            '^mi-app/faq/?$', 
            'index.php?mi_app_page=faq', 
            'top'
        );
        add_rewrite_rule(
            '^mi-app/challenges/?$', // La URL que queremos
            'index.php?mi_app_page=challenges', // A qué variable de query interna la mapea
            'top'
        );
    }

    /**
     * Añade nuestras variables de query personalizadas a la lista de variables reconocidas por WordPress.
     */
    public function add_query_vars($query_vars)
    {
        $query_vars[] = 'mi_app_page';
        $query_vars[] = 'competition_id';
        return $query_vars;
    }

    /**
     * Maneja la lógica de qué plantilla mostrar según la URL.
     */
    public function handle_template_redirect()
    {
        $page = get_query_var('mi_app_page');
        if (!$page) {
            return;
        }

        $template_path = plugin_dir_path(__FILE__) . 'templates/';

        switch ($page) {
            case 'login':
                if (is_user_logged_in()) {
                    wp_redirect(home_url('/mi-app/dashboard/'));
                    exit;
                }
                $this->handle_login_form_submission();
                include($template_path . 'login.php');
                break;

            case 'dashboard':
                $this->check_user_logged_in();
                include($template_path . 'dashboard.php');
                break;

            case 'competicion':
                $this->check_user_logged_in();
                include($template_path . 'competicion.php');
                break;

            case 'recetas':
                $this->check_user_logged_in();
                include($template_path . 'recetas.php');
                break;

            case 'perfil':
                $this->check_user_logged_in();
                include($template_path . 'perfil.php');
                break;

            case 'strava-callback':
                $this->handle_strava_callback();
                break;

            case 'mapa':
                $this->check_user_logged_in();
                include($template_path . 'mapa.php');
                break;

            case 'strava-activities':
                $this->check_user_logged_in();
                include($template_path . 'strava-activities.php');
                break;

            case 'faq':
                $this->check_user_logged_in();
                include($template_path . 'faq.php');
                break;

            case 'challenges':
                $this->check_user_logged_in();
                include($template_path . 'challenges.php');
                break;

            default:
                return;
        }

        exit;
    }

    /**
     * Verifica si el usuario está logueado. Si no, lo redirige a la página de login.
     */
    private function check_user_logged_in()
    {
        if (!is_user_logged_in()) {
            $redirect_to = home_url($_SERVER['REQUEST_URI']);
            wp_redirect(home_url("/mi-app/login/?redirect_to=" . urlencode($redirect_to)));
            exit;
        }
    }

    /**
     * Función para manejar el callback de OAuth de Strava.
     */
    private function handle_strava_callback()
    {

        if (!get_option('mi_app_strava_client_id') || !get_option('mi_app_strava_client_secret')) {
        wp_die('Error: Las credenciales de Strava no están configuradas.');
        }

        if (!is_user_logged_in()) {
            wp_die('Error: Debes estar logueado para conectar con Strava.');
        }

        if (isset($_GET['error'])) {
            $redirect_url = home_url('/mi-app/perfil/?strava_error=access_denied');
            wp_redirect($redirect_url);
            exit;
        }

        if (!isset($_GET['code'])) {
            $redirect_url = home_url('/mi-app/perfil/?strava_error=no_code');
            wp_redirect($redirect_url);
            exit;
        }

        $response = wp_remote_post('https://www.strava.com/oauth/token', [
            'method'    => 'POST',
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode([
                'client_id'     => get_option('mi_app_strava_client_id'),
                'client_secret' => get_option('mi_app_strava_client_secret'),
                'code'          => $_GET['code'],
                'grant_type'    => 'authorization_code'
            ]),
        ]);

        if (is_wp_error($response)) {
            $redirect_url = home_url('/mi-app/perfil/?strava_error=api_failed');
            wp_redirect($redirect_url);
            exit;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['access_token']) && isset($data['athlete'])) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'strava_access_token', $data['access_token']);
            update_user_meta($user_id, 'strava_refresh_token', $data['refresh_token']);
            update_user_meta($user_id, 'strava_expires_at', $data['expires_at']);
            update_user_meta($user_id, 'strava_athlete', json_encode($data['athlete']));

            $redirect_url = home_url('/mi-app/perfil/?strava_connected=1');
            wp_redirect($redirect_url);
        } else {
            $redirect_url = home_url('/mi-app/perfil/?strava_error=invalid_response');
            wp_redirect($redirect_url);
        }
        exit;
    }


    /**
     * Maneja la llamada AJAX para generar y descargar el PDF del plan nutricional.
     */
    public function handle_download_nutrition_plan_pdf()
    {
        //error_log('--- INICIO DEPURACIÓN PDF ---');

        // 1. VERIFICAR NONCE
        if (!wp_verify_nonce($_POST['nonce'], 'download_plan_pdf_nonce')) {
            //error_log('ERROR PDF: Nonce inválido.');
            wp_die('Error de seguridad. Nonce inválido.');
        }

        $user_id = get_current_user_id();
        $competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;

        if (!$user_id || !$competition_id) {
            //error_log('ERROR PDF: Faltan datos del usuario o de la competición.');
            wp_die('Faltan datos del usuario o de la competición.');
        }

        // 2. VERIFICAR QUE DOMPDF EXISTE
        $autoloader_path = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoloader_path)) {
            //error_log('ERROR PDF: El autoloader de Dompdf no existe en: ' . $autoloader_path);
            wp_die('La librería para generar PDF no está instalada correctamente. Contacte con el administrador.');
        }

        require_once $autoloader_path;

        if (!class_exists('Dompdf\Dompdf')) {
            //error_log('ERROR PDF: La clase Dompdf\Dompdf no se encontró después de cargar el autoloader.');
            wp_die('La librería para generar PDF no está instalada correctamente. Contacte con el administrador.');
        }
        
        //error_log('DEPURACIÓN PDF: Dompdf cargado correctamente.');

        // 3. OBTENER EL PLAN DESDE LA BASE DE DATOS
        global $wpdb;
        $plan_table = $wpdb->prefix . 'mi_app_nutrition_plans';
        $plan_row = $wpdb->get_row($wpdb->prepare(
            "SELECT plan_data, plan_name FROM {$plan_table} WHERE user_id = %d AND competition_id = %d AND plan_type = %s",
            $user_id,
            $competition_id,
            'nutrition_plan'
        ));

        if (!$plan_row) {
            //error_log('ERROR PDF: No se encontró un plan guardado para esta competición.');
            wp_die('No se encontró un plan guardado para esta competición.');
        }

        //error_log('DEPURACIÓN PDF: Plan encontrado. Nombre: ' . $plan_row->plan_name);

        $plan_data = json_decode($plan_row->plan_data, true);
        $plan_name = $plan_row->plan_name;

        // 4. GENERAR EL HTML
        //error_log('DEPURACIÓN PDF: Generando HTML...');
        $html_for_pdf = $this->generate_pdf_html($plan_data, $plan_name);

        // 5. INTENTAR GENERAR EL PDF CON UN BLOQUE TRY...CATCH
        try {
            //error_log('DEPURACIÓN PDF: Creando instancia de Dompdf...');
            $options = new Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf\Dompdf($options);

            //error_log('DEPURACIÓN PDF: Cargando HTML en Dompdf...');
            $dompdf->loadHtml($html_for_pdf);
            $dompdf->setPaper('A4', 'portrait');

            //error_log('DEPURACIÓN PDF: Renderizando el PDF...');
            $dompdf->render();

            //error_log('DEPURACIÓN PDF: Enviando el PDF al navegador...');
            $filename = sanitize_file_name($plan_name . '.pdf');
            $dompdf->stream($filename, ['Attachment' => true]);

        } catch (Exception $e) {
            //error_log('ERROR PDF: Excepción capturada al generar el PDF: ' . $e->getMessage());
            //error_log('Stack Trace: ' . $e->getTraceAsString());
            wp_die('Se ha producido un error al generar el PDF. Error: ' . $e->getMessage());
        }

        //error_log('--- FIN DEPURACIÓN PDF ---');
        exit;
    }

    /**
     * Genera el código HTML para el PDF a partir de los datos del plan.
     * @param array $plan_data El array con los datos del plan.
     * @param string $plan_name El nombre del plan.
     * @return string El HTML formateado para el PDF.
     */
    private function generate_pdf_html($plan_data, $plan_name)
    {
        $html = '
        <html>
        <head>
            <meta charset="utf-8"/>
            <title>' . esc_html($plan_name) . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                h1 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
                h2 { color: #555; margin-top: 30px; }
                h3 { color: #fc4c02; }
                .meal { margin-bottom: 20px; border-left: 3px solid #ddd; padding-left: 15px; }
                .meal-name { font-weight: bold; font-size: 1.1em; }
                .ingredients, .instructions { margin-top: 5px; }
                .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 0.8em; color: #999; }
                .page-break { page-break-before: always; }
            </style>
        </head>
        <body>
            <h1>' . esc_html($plan_name) . '</h1>';

        // Añadir insights de Strava si existen
        if (isset($plan_data['strava_insights']) && $plan_data['strava_insights']['summary']) {
            $html .= '
            <div class="insights">
                <h2>Justificación del Plan Nutricional</h2>
                <p>' . esc_html($plan_data['strava_insights']['summary']) . '</p>
            </div>';
        }

        // Añadir los días y comidas
        foreach ($plan_data['days'] as $day) {
            $html .= '<div class="page-break"><h2>Plan para el ' . esc_html($day['day']) . '</h2>';
            foreach ($day['meals'] as $meal) {
                $html .= '
                <div class="meal">
                    <h3 class="meal-name">' . ucfirst(esc_html($meal['type'])) . '</h3>
                    <p><strong>Receta:</strong> ' . esc_html($meal['name']) . '</p>
                    <p class="ingredients"><strong>Ingredientes:</strong> ' . nl2br(esc_html($meal['ingredients'])) . '</p>
                    <p class="instructions"><strong>Instrucciones:</strong> ' . nl2br(esc_html($meal['instructions'])) . '</p>
                    <div class="meal-stats">
                        <span>Carbs: ' . esc_html($meal['carbs']) . 'g</span> |
                        <span>Proteína: ' . esc_html($meal['protein']) . 'g</span> |
                        <span>Grasas: ' . esc_html($meal['fats']) . 'g</span> |
                        <span>Calorías: ' . esc_html($meal['calories']) . 'kcal</span>
                    </div>  
                </div>';
            }
            $html .= '</div>'; // Fin del día
        }

        $html .= '
            <div class="footer">
                <p>Generado por Sentmenat Bici - ' . date('d/m/Y') . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }


    /**
     * Maneja la llamada AJAX para obtener los datos de Tiempo en Zonas.
     */
    public function handle_get_zones_chart_data()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'get_zones_chart_data_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
            wp_die();
        }

        $user_id = get_current_user_id();
        $ftp = (float) get_user_meta($user_id, 'mi_app_ftp', true);

        if (!$user_id || !$ftp) {
            wp_send_json_error('Se requiere el FTP del usuario para calcular las zonas.');
            wp_die();
        }

        $activities = $this->get_strava_activities($user_id, 30); // Actividades del último mes
        if (is_wp_error($activities)) {
            wp_send_json_error('No se pudieron obtener las actividades de Strava.');
            wp_die();
        }

        $zones_data = $this->calculate_zones_data($activities, $ftp);
        wp_send_json_success($zones_data);
        wp_die();
    }

        /**
         * Calcula la distribución del tiempo de entrenamiento por zonas de potencia.
         * SOLUCIÓN FINAL: Clasifica explícitamente cualquier potencia 0 o nula en la Z1.
         * @param array $activities Array de objetos de actividad.
         * @param float $ftp FTP del usuario.
         * @return array Datos para el gráfico de Zonas, incluyendo tiempo y porcentaje.
         */
        private function calculate_zones_data($activities, $ftp)
        {
            $ftp_value = (float) $ftp; 

            // Definición de Zonas de Coggan (continuas)
            $zones = [
                'Z1 Recuperación (<55%)' => ['min' => 0.00 * $ftp_value, 'max' => 0.55 * $ftp_value, 'time' => 0],
                'Z2 Resistencia (55-75%)' => ['min' => 0.55 * $ftp_value, 'max' => 0.75 * $ftp_value, 'time' => 0],
                'Z3 Tempo (75-90%)'       => ['min' => 0.75 * $ftp_value, 'max' => 0.90 * $ftp_value, 'time' => 0],
                'Z4 Umbral (90-105%)'     => ['min' => 0.90 * $ftp_value, 'max' => 1.05 * $ftp_value, 'time' => 0],
                'Z5 VO2 Máx (105-120%)'   => ['min' => 1.05 * $ftp_value, 'max' => 1.20 * $ftp_value, 'time' => 0],
                'Z6 Anaeróbica (>120%)'   => ['min' => 1.20 * $ftp_value, 'max' => PHP_FLOAT_MAX, 'time' => 0],
            ];

            $total_moving_time = 0;

            foreach ($activities as $activity) {
                if ($activity->type === 'Ride' && isset($activity->moving_time) && $activity->moving_time > 0) {
                    
                    $time_seconds = $activity->moving_time;
                    $total_moving_time += $time_seconds;

                    // --- DETERMINAR LA MÉTRICA DE CLASIFICACIÓN (NP o Media) ---
                    $avg_power_metric = 0; 
                    
                    if (isset($activity->weighted_average_watts) && $activity->weighted_average_watts > 0) {
                        $avg_power_metric = $activity->weighted_average_watts;
                    } else if (isset($activity->average_watts)) {
                         $avg_power_metric = $activity->average_watts;
                    }

                    // --- CLASIFICACIÓN (LÓGICA CORREGIDA) ---
                    
                    if ($avg_power_metric <= 0) {
                        // 💡 CLAVE: Si la potencia es CERO o negativa, se asigna forzosamente a Z1.
                        // Esto absorbe los 3419 minutos perdidos.
                        $zones['Z1 Recuperación (<55%)']['time'] += $time_seconds;
                        continue; // Pasa a la siguiente actividad
                    }
                    
                    // Si la potencia es > 0, se clasifica en las zonas > Z1
                    foreach ($zones as $key => &$zone) {
                        
                        if ($key === 'Z1 Recuperación (<55%)') {
                            // La potencia 0 ya se ha manejado, aquí solo consideramos (0, 55%]
                            if ($avg_power_metric > $zone['min'] && $avg_power_metric <= $zone['max']) {
                                $zone['time'] += $time_seconds;
                                break;
                            }
                        }
                        else if ($key !== 'Z6 Anaeróbica (>120%)') {
                            // Para Z2 a Z5: [min, max)
                            if ($avg_power_metric >= $zone['min'] && $avg_power_metric < $zone['max']) {
                                $zone['time'] += $time_seconds;
                                break;
                            }
                        } else {
                            // Para Z6: [min, MAX)
                            if ($avg_power_metric >= $zone['min']) {
                                 $zone['time'] += $time_seconds;
                                 break;
                            }
                        }
                    }
                }
            }

            // ... (Preparación de datos para el gráfico, que ya está correcta) ...

            $labels = array_keys($zones);
            $data_percentage = [];

            foreach ($zones as $zone) {
                $percentage = ($total_moving_time > 0) ? round(($zone['time'] / $total_moving_time) * 100, 1) : 0;
                $data_percentage[] = $percentage;
            }

            return [
                'labels' => $labels,
                'total_minutes' => round($total_moving_time / 60),
                'data_percentage' => $data_percentage,
            ];
        }

    /**
     * Maneja la llamada AJAX para obtener los datos de la Curva de Potencia (PDC).
     */
    public function handle_get_pdc_chart_data()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'get_pdc_chart_data_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
            wp_die();
        }

        $user_id = get_current_user_id();
        $ftp = (float) get_user_meta($user_id, 'mi_app_ftp', true);
        $weight_kg = (float) get_user_meta($user_id, 'mi_app_peso', true);

        if (!$user_id || !$ftp) {
            wp_send_json_error('Se requiere el FTP del usuario para calcular la curva de potencia.');
            wp_die();
        }

        $activities = $this->get_strava_activities($user_id, 30); // Analizamos más actividades
        if (is_wp_error($activities)) {
            wp_send_json_error('No se pudieron obtener las actividades de Strava.');
            wp_die();
        }

        $pdc_data = $this->calculate_pdc_data($activities, $ftp, $weight_kg);
        wp_send_json_success($pdc_data);
        wp_die();
    }

    /**
     * Calcula los datos de la Curva de Potencia (PDC) con aproximación.
     * @param array $activities Array de objetos de actividad.
     * @param float $ftp FTP del usuario (en Vatios).
     * @param float $weight_kg Peso del usuario (en Kilogramos). 
     * @return array Datos para el gráfico PDC, incluyendo W/kg y %FTP.
     */
    private function calculate_pdc_data($activities, $ftp, $weight_kg)
    {
        // Definimos los rangos de tiempo estándar (en segundos)
        $time_periods = [
            '5s' => 5, '15s' => 15, '30s' => 30, '1m' => 60, 
            '5m' => 300, '10m' => 600, '20m' => 1200, '1h' => 3600
        ];
        
        // Inicializamos los récords
        $best_watts = array_fill_keys(array_keys($time_periods), 0);
        
        // --- LÓGICA CLAVE DE LA APROXIMACIÓN DE RÉCORDS (Corregida) ---
        foreach ($activities as $activity) {
            if ($activity->type === 'Ride' && isset($activity->average_watts) && $activity->average_watts > 0) {
                $time_seconds = $activity->moving_time;
                $avg_power = $activity->average_watts;

                foreach ($time_periods as $label => $seconds) {
                    // Si la duración de la actividad es IGUAL O MAYOR al período del récord,
                    // la potencia media de esa actividad es un candidato para el récord.
                    // Cuanto más larga sea la actividad, más relevante es su media para los récords largos.
                    if ($time_seconds >= $seconds) {
                        if ($avg_power > $best_watts[$label]) {
                            $best_watts[$label] = $avg_power;
                        }
                    }
                }
                
                // Lógica especial para duraciones muy cortas (como 5s, 15s)
                // Si la actividad dura, por ejemplo, 40s, su potencia media debería compararse
                // con los récords de 5s, 15s, y 30s. Esta lógica está cubierta por el bucle superior,
                // pero si la actividad fuera de 15s, solo actualizaría hasta 15s.
            }
        }

        // --- GENERACIÓN DE DATOS PARA EL GRÁFICO (W/kg y %FTP) ---
        
        $labels = array_keys($best_watts);
        $current_watts = array_values($best_watts);

        $current_w_per_kg = [];
        $current_percent_ftp = [];
        
        $weight_kg = (float)$weight_kg;
        $ftp = (float)$ftp;

        foreach ($current_watts as $watts) {
            // 1. Potencia por Kilogramo (W/kg)
            $w_per_kg = ($weight_kg > 0) ? ($watts / $weight_kg) : 0;
            $current_w_per_kg[] = round($w_per_kg, 2);

            // 2. Porcentaje de FTP (%FTP)
            $percent_ftp = ($ftp > 0) ? ($watts / $ftp) * 100 : 0;
            $current_percent_ftp[] = round($percent_ftp, 1);
        }

        // El "best_historical" se refiere a los récords de potencia más altos de la historia del usuario.
        // Aquí se utiliza el actual como marcador de posición.
        $best_historical_watts = $current_watts; 

        return [
            'labels' => $labels,
            
            'watts' => [
                'current' => $current_watts,
                'best_historical' => $best_historical_watts, // Datos para comparación
            ],
            
            'w_per_kg' => [
                'current' => $current_w_per_kg,
            ],
            
            'percent_ftp' => [
                'current' => $current_percent_ftp,
            ],
        ];
    }


    /**
     * Maneja la llamada AJAX para obtener los datos del gráfico de Rendimiento (PMC).
     */
    public function handle_get_pmc_chart_data()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'get_pmc_chart_data_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
            wp_die();
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Usuario no logueado.');
            wp_die();
        }

        $access_token = get_user_meta($user_id, 'strava_access_token', true);
        if (empty($access_token)) {
            wp_send_json_error('No hay conexión con Strava.');
            wp_die();
        }

        // Obtenemos más actividades para un historial más significativo (ej. últimas 60)
        $activities = $this->get_strava_activities($user_id, 30);
        if (is_wp_error($activities)) {
            wp_send_json_error('No se pudieron obtener las actividades de Strava.');
            wp_die();
        }

        // Procesamos los datos para el gráfico PMC
        $pmc_data = $this->calculate_pmc_data($activities);

        wp_send_json_success($pmc_data);
        wp_die();
    }

    /**
     * Calcula los datos de Fitness (CTL), Fatiga (ATL) y Forma (TSB) a partir de las actividades.
     * @param array $activities Array de objetos de actividad de Strava.
     * @return array Datos listos para el gráfico PMC.
     */
    private function calculate_pmc_data($activities)
    {
        // Transformamos las actividades de Strava al formato que la lógica de cálculo espera
        $historial_actividades = [];
        foreach ($activities as $activity) {
            // Nos enfocamos en las salidas en bici que tienen datos de potencia
            if ($activity->type === 'Ride' && isset($activity->average_watts) && $activity->average_watts > 0) {
                $historial_actividades[] = [
                    'fecha' => date('Y-m-d', strtotime($activity->start_date_local)),
                    'segundos' => $activity->moving_time,
                    //'np' => $activity->average_watts // Usamos la potencia media como proxy de la potencia normalizada (NP)
                    'np' => $activity->weighted_average_watts
                ];
            }
        }

        if (empty($historial_actividades)) {
            return [
                'fechas' => [],
                'ctl' => [],
                'atl' => [],
                'tsb' => []
            ];
        }

        // Lógica de cálculo (adaptada de Graficos.txt)
        $ctl_time_constant = 42; 
        $atl_time_constant = 7;
        $ctl_ayer = 50; // Semilla inicial
        $atl_ayer = 50;

        $fechas = [];
        $data_ctl = [];
        $data_atl = [];
        $data_tsb = [];

        // Crear rango de fechas para los últimos 45 días para una visualización clara
        $fecha_fin = new DateTime();
        $fecha_inicio = new DateTime();
        $fecha_inicio->modify("-45 days");
        $periodo = new DatePeriod($fecha_inicio, new DateInterval('P1D'), $fecha_fin->modify('+1 day'));

        // Indexar actividades por fecha para acceso rápido
        $mapa_actividades = [];
        foreach ($historial_actividades as $act) {
        $fecha_actividad = $act['fecha'];
        
        // --- LÓGICA CLAVE: BÚSQUEDA DE FTP DINÁMICO ---
        $ftp_a_usar = 250; // Valor por defecto si no se encuentra historial
        
        // Recorrer el historial de FTPs para encontrar el más reciente ANTES o EN la fecha de la actividad
        foreach ($ftp_historial as $ftp_registro) {
            if ($ftp_registro['fecha'] <= $fecha_actividad) {
                // Este FTP es válido para esta fecha o una anterior. Lo guardamos.
                $ftp_a_usar = $ftp_registro['ftp'];
            } else {
                // Los registros posteriores ya no aplican. Detenemos la búsqueda.
                break;
            }
        }
        // -----------------------------------------------

        // Ahora usamos el $ftp_a_usar dinámico en lugar de la constante '250'
        $tss = $this->calcularTSS($act['segundos'], $act['np'], $ftp_a_usar);
        
        if (!isset($mapa_actividades[$fecha_actividad])) $mapa_actividades[$fecha_actividad] = 0;
        $mapa_actividades[$fecha_actividad] += $tss;
        }

        // Bucle Día a Día
        foreach ($periodo as $dt) {
            $fecha_str = $dt->format('Y-m-d');
            $fechas[] = $dt->format('d M');
            $tss_hoy = isset($mapa_actividades[$fecha_str]) ? $mapa_actividades[$fecha_str] : 0;

            $ctl_hoy = $ctl_ayer + ($tss_hoy - $ctl_ayer) / $ctl_time_constant;
            $atl_hoy = $atl_ayer + ($tss_hoy - $atl_ayer) / $atl_time_constant;
            $tsb_hoy = $ctl_hoy - $atl_hoy;

            $data_ctl[] = round($ctl_hoy, 1);
            $data_atl[] = round($atl_hoy, 1);
            $data_tsb[] = round($tsb_hoy, 1);

            $ctl_ayer = $ctl_hoy;
            $atl_ayer = $atl_hoy;
        }

        return [
            'fechas' => $fechas,
            'ctl' => $data_ctl,
            'atl' => $data_atl,
            'tsb' => $data_tsb
        ];
    }

    /**
     * Calcula el TSS de una actividad individual.
     * @param int $duracion_segundos Duración en segundos.
     * @param float $potencia_normalizada Potencia normalizada.
     * @param float $ftp Umbral funcional de potencia del usuario.
     * @return float El TSS calculado.
     */
    private function calcularTSS($duracion_segundos, $potencia_normalizada, $ftp)
    {
        if ($potencia_normalizada == 0 || $ftp == 0) return 0;
        
        $intensity_factor = $potencia_normalizada / $ftp;
        $tss = ($duracion_segundos * $potencia_normalizada * $intensity_factor) / ($ftp * 3600) * 100;
        
        return $tss;
    }

    /**
     * Maneja la llamada AJAX para obtener los datos de carga y fatiga para el gráfico.
     */
    public function handle_get_strava_chart_data()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'get_strava_chart_data_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
            wp_die();
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Usuario no logueado.');
            wp_die();
        }

        $access_token = get_user_meta($user_id, 'strava_access_token', true);
        if (empty($access_token)) {
            wp_send_json_error('No hay conexión con Strava.');
            wp_die();
        }

        // Obtenemos más actividades para tener un historial más rico (ej. últimas 30)
        $activities = $this->get_strava_activities($user_id, 30);
        if (is_wp_error($activities)) {
            wp_send_json_error('No se pudieron obtener las actividades de Strava. Error: ' . $activities->get_error_message());
            wp_die();
        }

        // Procesamos los datos para el gráfico
        $chart_data = $this->process_activities_for_chart($activities);

        wp_send_json_success($chart_data);
        wp_die();
    }

    /**
     * Procesa un array de actividades para calcular la carga y fatiga diarias.
     * Utiliza una media móvil de 7 días para suavizar los datos.
     *
     * @param array $activities Array de objetos de actividad de Strava.
     * @return array Datos listos para el gráfico.
     */
    private function process_activities_for_chart($activities)
    {
        // --- DEPURACIÓN: Ver cuántas actividades llegan ---
        error_log('[MI-APP-DEBUG] process_activities_for_chart: Se recibieron ' . count($activities) . ' actividades.');

        if (empty($activities)) {
            error_log('[MI-APP-DEBUG] process_activities_for_chart: El array de actividades está vacío.');
            return [];
        }

        $daily_metrics = [];
        // Eliminamos el filtro de tipos para analizar TODO, como solicitaste.
        // $contributing_activity_types = ['Ride', 'Run', 'Walk', ...];

        // 1. Agrupar la carga y fatiga por día
        foreach ($activities as $activity) {
            // Ya no filtramos por tipo, procesamos todo.
            // if (in_array($activity->type, $contributing_activity_types)) { ... }

            $date = date('Y-m-d', strtotime($activity->start_date_local));
            $time_hours = $activity->moving_time / 3600;
            $distance_km = $activity->distance / 1000;

            // Cálculo de carga (más simple y genérico)
            $daily_load = ($time_hours * 1.2) + ($distance_km * 0.1);

            // Cálculo de fatiga
            $intensity_factor = 1;
            if (isset($activity->average_watts) && $activity->average_watts > 0) {
                $intensity_factor = $activity->average_watts / 200;
            } elseif (isset($activity->average_heartrate) && $activity->average_heartrate > 0) {
                $intensity_factor = $activity->average_heartrate / 150;
            }
            $daily_fatigue = $time_hours * $intensity_factor;

            if (!isset($daily_metrics[$date])) {
                $daily_metrics[$date] = ['load' => 0, 'fatigue' => 0];
            }
            $daily_metrics[$date]['load'] += $daily_load;
            $daily_metrics[$date]['fatigue'] += $daily_fatigue;
        }

        // --- DEPURACIÓN: Ver los datos diarios procesados ---
        error_log('[MI-APP-DEBUG] process_activities_for_chart: Datos diarios procesados: ' . print_r($daily_metrics, true));

        if (empty($daily_metrics)) {
            error_log('[MI-APP-DEBUG] process_activities_for_chart: No se pudieron agrupar métricas diarias.');
            return [];
        }

        // 2. Calcular la media móvil de 7 días y formatear para el gráfico
        ksort($daily_metrics); // Ordenar por fecha
        $chart_data = [];
        $dates = array_keys($daily_metrics);

        foreach ($dates as $date) {
            $rolling_load = 0;
            $rolling_fatigue = 0;

            // Sumar los valores de los últimos 7 días (incluyendo el día actual)
            for ($i = 6; $i >= 0; $i--) {
                $past_date = date('Y-m-d', strtotime($date . ' -' . $i . ' days'));
                if (isset($daily_metrics[$past_date])) {
                    $rolling_load += $daily_metrics[$past_date]['load'];
                    $rolling_fatigue += $daily_metrics[$past_date]['fatigue'];
                }
            }
            
            $chart_data[] = [
                'date' => $date,
                'load' => round($rolling_load, 2),
                'fatigue' => round($rolling_fatigue, 2)
            ];
        }

        // --- DEPURACIÓN: Ver el resultado final para el gráfico ---
        error_log('[MI-APP-DEBUG] process_activities_for_chart: Datos finales para el gráfico: ' . print_r($chart_data, true));

        return $chart_data;
    }



    /**
     * Maneja el envío del formulario de login.
     */
    private function handle_login_form_submission()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['mi_app_login_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['mi_app_login_nonce'], 'mi_app_login_action')) {
            $error_message = "Error de seguridad. Por favor, inténtalo de nuevo.";
            return;
        }

        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];

        $user = wp_signon([
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => isset($_POST['rememberme']),
        ]);

        if (is_wp_error($user)) {
            $error_message = $user->get_error_message();
        } else {
            $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : home_url('/mi-app/dashboard/');
            wp_safe_redirect($redirect_to);
            exit;
        }
    }

    /**
     * Refresca el token de acceso de Strava usando el refresh_token.
     */
    private function refresh_strava_token($user_id)
    {
        $refresh_token = get_user_meta($user_id, 'strava_refresh_token', true);

        if (empty($refresh_token)) {
            return false;
        }

        $response = wp_remote_post('https://www.strava.com/oauth/token', [
            'method'    => 'POST',
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode([
                'client_id'     => get_option('mi_app_strava_client_id'),
                'client_secret' => get_option('mi_app_strava_client_secret'),
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refresh_token,
            ]),
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $tokens = json_decode($body, true);

        if (isset($tokens['access_token'])) {
            update_user_meta($user_id, 'strava_access_token', $tokens['access_token']);
            update_user_meta($user_id, 'strava_refresh_token', $tokens['refresh_token']);
            update_user_meta($user_id, 'strava_expires_at', time() + $tokens['expires_in']);
            return $tokens;
        }

        return false;
    }

    /**
     * Añade el tipo de archivo GPX a la lista de MIME types permitidos por WordPress.
     */
    public function add_gpx_mime_type($mimes)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            trigger_error('El filtro upload_mimes se está ejecutando.', E_USER_NOTICE);
        }

        $mimes['gpx'] = 'application/gpx+xml';
        return $mimes;
    }

    /**
     * Analiza el contenido de un archivo GPX y extrae información básica.
     */
    private function analyzeGpxFileContent($gpx_content)
    {
        $xml = @simplexml_load_string($gpx_content);
        
        if (!$xml) {
            return ['status' => 'error', 'message' => 'No se pudo parsear el archivo GPX'];
        }

        $result = [
            'status' => 'success',
            'name' => (string)($xml->trk->name ?? 'Sin nombre'),
            'total_distance_meters' => 0,
            'total_time_seconds' => 0,
            'min_elevation' => null,
            'max_elevation' => null,
        ];

        // Calcular distancia total
        $track_points = [];
        if (isset($xml->trk->trkseg->trkpt)) {
            foreach ($xml->trk->trkseg->trkpt as $point) {
                $track_points[] = [
                    'lat' => (float)$point['lat'],
                    'lon' => (float)$point['lon'],
                    'ele' => isset($point->ele) ? (float)$point->ele : null,
                    'time' => isset($point->time) ? (string)$point->time : null,
                ];
            }
        }

        // Calcular distancia usando la fórmula de Haversine
        $total_distance = 0;
        for ($i = 1; $i < count($track_points); $i++) {
            $total_distance += $this->calculateDistance(
                $track_points[$i-1]['lat'], $track_points[$i-1]['lon'],
                $track_points[$i]['lat'], $track_points[$i]['lon']
            );
        }
        $result['total_distance_meters'] = $total_distance;

        // Calcular tiempo total
        if (count($track_points) >= 2 && !empty($track_points[0]['time']) && !empty(end($track_points)['time'])) {
            $start_time = strtotime($track_points[0]['time']);
            $end_time = strtotime(end($track_points)['time']);
            $result['total_time_seconds'] = $end_time - $start_time;
        }

        // Encontrar elevación mínima y máxima
        $elevations = array_filter(array_column($track_points, 'ele'));
        if (!empty($elevations)) {
            $result['min_elevation'] = min($elevations);
            $result['max_elevation'] = max($elevations);
        }

        return $result;
    }

    /**
     * Calcula la distancia entre dos puntos geográficos usando la fórmula de Haversine.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earth_radius = 6371000; // Radio de la Tierra en metros
        
        $lat1_rad = deg2rad($lat1);
        $lon1_rad = deg2rad($lon1);
        $lat2_rad = deg2rad($lat2);
        $lon2_rad = deg2rad($lon2);
        
        $dlat = $lat2_rad - $lat1_rad;
        $dlon = $lon2_rad - $lon1_rad;
        
        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1_rad) * cos($lat2_rad) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }

    /**
     * Maneja el envío del formulario para añadir o editar una competición.
     */
    public function handle_competition_submission()
    {
        if (isset($_POST['add_competition_nonce']) && wp_verify_nonce($_POST['add_competition_nonce'], 'add_competition_action')) {
            
            if (!is_user_logged_in()) {
                wp_die('Debes estar logueado para añadir una competición.');
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'mi_app_competiciones';
            $user_id = get_current_user_id();
            $is_edit = isset($_POST['competition_id']) && !empty($_POST['competition_id']);
            $competition_id = $is_edit ? intval($_POST['competition_id']) : null;

            $gpx_file_url = '';
            $gpx_name = '';
            $gpx_min_elevation = null;
            $gpx_max_elevation = null;
            $upload_error = '';

            if (isset($_FILES['gpx_file']) && $_FILES['gpx_file']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gpx_file']['name'])) {
                
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploadedfile = $_FILES['gpx_file'];
                $upload_overrides = array('test_form' => false, 'mimes' => array('gpx' => 'application/gpx+xml'));
                $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

                $file_path_to_read = null;
                if ($movefile && !isset($movefile['error'])) {
                    $gpx_file_url = $movefile['url'];
                    $file_path_to_read = $movefile['file'];
                } else {
                    $file_info = pathinfo($uploadedfile['name']);
                    if (strtolower($file_info['extension']) === 'gpx') {
                        $wp_upload_dir = wp_upload_dir();
                        $filename = time() . '_' . sanitize_file_name($uploadedfile['name']);
                        $target_path = $wp_upload_dir['path'] . '/' . $filename;
                        if (move_uploaded_file($uploadedfile['tmp_name'], $target_path)) {
                            $gpx_file_url = $wp_upload_dir['url'] . '/' . $filename;
                            $file_path_to_read = $target_path;
                        } else {
                            $upload_error = 'Error: No se pudo mover el archivo subido manualmente.';
                        }
                    } else {
                        $upload_error = 'Error: El archivo no tiene una extensión .gpx válida.';
                    }
                }

                if ($file_path_to_read) {
                    $gpx_content = file_get_contents($file_path_to_read);
                    $gpx_analysis = $this->analyzeGpxFileContent($gpx_content);
                    
                    if ($gpx_analysis['status'] === 'success') {
                        $gpx_name = $gpx_analysis['name'];
                        $gpx_min_elevation = $gpx_analysis['min_elevation'];
                        $gpx_max_elevation = $gpx_analysis['max_elevation'];
                    }
                }
            } 
            elseif ($is_edit) {
                $old_comp = $wpdb->get_row($wpdb->prepare("SELECT gpx_file_url, gpx_name, gpx_min_elevation, gpx_max_elevation FROM $table_name WHERE id = %d AND user_id = %d", $competition_id, $user_id));
                if($old_comp) {
                    $gpx_file_url = $old_comp->gpx_file_url;
                    $gpx_name = $old_comp->gpx_name;
                    $gpx_min_elevation = $old_comp->gpx_min_elevation;
                    $gpx_max_elevation = $old_comp->gpx_max_elevation;
                }
            }

            $form_desnivel = isset($_POST['desnivel']) && $_POST['desnivel'] !== '' ? intval($_POST['desnivel']) : null;
            
            if ($form_desnivel === null && $gpx_max_elevation !== null && $gpx_min_elevation !== null) {
                $final_desnivel = intval($gpx_max_elevation - $gpx_min_elevation);
            } else {
                $final_desnivel = $form_desnivel;
            }

            $data = [
                'nombre' => sanitize_text_field($_POST['nombre']),
                'fecha' => $_POST['fecha'],
                'distancia' => floatval($_POST['distancia']),
                'desnivel' => $final_desnivel,
                'gpx_file_url' => $gpx_file_url,
                'gpx_name' => sanitize_text_field($gpx_name),
                'gpx_min_elevation' => $gpx_min_elevation,
                'gpx_max_elevation' => $gpx_max_elevation,
                'total_distance_meters' => isset($gpx_analysis['total_distance_meters']) ? $gpx_analysis['total_distance_meters'] : 0,
                'total_time_seconds' => isset($gpx_analysis['total_time_seconds']) ? $gpx_analysis['total_time_seconds'] : 0,
            ];
            $format = ['%s', '%s', '%f', '%d', '%s', '%s', '%f', '%f', '%f', '%f'];

            if ($is_edit) {
                $result = $wpdb->update($table_name, $data, ['id' => $competition_id, 'user_id' => $user_id], $format, ['%d', '%d']);
                $query_arg = ($result !== false) ? 'competition_updated=1' : 'competition_error=update_failed';
            } else {
                $data['user_id'] = $user_id;
                $data['created_at'] = current_time('mysql');
                $format[] = '%d';
                $format[] = '%s';
                $result = $wpdb->insert($table_name, $data, $format);
                $query_arg = ($result !== false) ? 'competition_added=1' : 'competition_error=insert_failed';
            }

            if (!empty($upload_error)) {
                $query_arg .= '&upload_error=' . urlencode($upload_error);
            }

            $competition_id = $wpdb->insert_id;
            // LÓGICA PARA COMPLETAR DESAFÍOS AUTOMÁTICAMENTE
            $this->check_and_complete_challenges($user_id, 'competition', $competition_id);

            $redirect_url = add_query_arg($query_arg, home_url('/mi-app/competicion/'));
            wp_redirect($redirect_url);
            exit;
        }
    }


     /**
     * Comprueba si completar una acción (ej. añadir competición) cumple algún desafío.
     * @param int $user_id ID del usuario.
     * @param string $action_type Tipo de acción ('competition').
     * @param int $object_id ID del objeto relacionado (ej. ID de la competición).
     */
     private function check_and_complete_challenges($user_id, $action_type, $object_id)
     {
         global $wpdb;
         $table_challenges = $wpdb->prefix . 'mi_app_challenges';
         $table_progress = $wpdb->prefix . 'mi_app_user_challenge_progress';

         // Buscar desafíos que puedan coincidir
         // Ejemplo: buscar desafíos de tipo 'rides' con meta_value que contenga el ID de esta competición
         $potential_challenges = $wpdb->get_results($wpdb->prepare(
         "SELECT * FROM {$table_challenges} WHERE challenge_type = %s",
         $action_type
         ));

         foreach ($potential_challenges as $challenge) {
             $is_met = false;
             if ($action_type === 'competition' && $challenge->meta_value) {
                 // Comprobar si el ID de la competición está en la lista de metas
                 $competition_ids = explode(',', $challenge->meta_value);
             if (in_array($object_id, $competition_ids)) {
                $is_met = true;
             }
             }

             // Si el desafío se cumple, marcarlo como completado si no lo estaba ya
             if ($is_met) {
                 $already_completed = $wpdb->get_var($wpdb->prepare(
                 "SELECT COUNT(*) FROM {$table_progress} WHERE user_id = %d AND challenge_id = %d",
                 $user_id,
                 $challenge->id
                ));

             if (!$already_completed) {
                 $wpdb->insert(
                 $table_progress,
                 [
                 'user_id' => $user_id,
                 'challenge_id' => $challenge->id,
                 'completed_at' => current_time('mysql'),
                 ],
                 ['%d', '%d', '%s']
                );
            }
            }
        }
     }


    /**
     * Maneja la solicitud para eliminar una competición.
     */
    public function handle_competition_deletion()
    {
        if (isset($_POST['action']) && $_POST['action'] === 'delete_competition' && isset($_POST['competition_id'])) {
            
            if (!wp_verify_nonce($_POST['delete_competition_nonce'], 'delete_competition_action_' . intval($_POST['competition_id']))) {
                wp_die('Error de seguridad. Nonce inválido.');
            }

            if (!is_user_logged_in()) {
                wp_die('Debes estar logueado para realizar esta acción.');
            }

            $user_id = get_current_user_id();
            $competition_id = intval($_POST['competition_id']);

            global $wpdb;
            $table_name = $wpdb->prefix . 'mi_app_competiciones';

            $competition = $wpdb->get_row($wpdb->prepare("SELECT gpx_file_url FROM $table_name WHERE id = %d AND user_id = %d", $competition_id, $user_id));

            if ($competition && $competition->gpx_file_url) {
                $gpx_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $competition->gpx_file_url);
                if (file_exists($gpx_path)) {
                    unlink($gpx_path);
                }
            }

            $deleted = $wpdb->delete(
                $table_name,
                ['id' => $competition_id, 'user_id' => $user_id],
                ['%d', '%d']
            );

            if ($deleted) {
                $redirect_url = add_query_arg('competition_deleted', '1', home_url('/mi-app/competicion/'));
                wp_redirect($redirect_url);
            } else {
                $redirect_url = add_query_arg('competition_error', 'delete_failed', home_url('/mi-app/competicion/'));
                wp_redirect($redirect_url);
            }
            exit;
        }
    }

    /**
     * Maneja la llamada AJAX para generar el plan nutricional.
     */
    public function handle_generate_nutrition_plan()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'generate_nutrition_plan_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
        }

        $user_id = get_current_user_id();
        $competition_id = intval($_POST['competition_id']);

        if (!$user_id || !$competition_id) {
            wp_send_json_error('Faltan datos del usuario o la competición.');
        }

        $user_data = [
            'peso' => get_user_meta($user_id, 'mi_app_peso', true),
            'edad' => get_user_meta($user_id, 'mi_app_edad', true),
            'ftp' => get_user_meta($user_id, 'mi_app_ftp', true),
            'experiencia' => get_user_meta($user_id, 'mi_app_experiencia', true),
            'es_celiaco' => get_user_meta($user_id, 'mi_app_celiaco', true),
            'es_diabetico' => get_user_meta($user_id, 'mi_app_diabetico', true),
        ];

        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_app_competiciones';
        $competition = $wpdb->get_row($wpdb->prepare("SELECT nombre, distancia, desnivel FROM $table_name WHERE id = %d AND user_id = %d", $competition_id, $user_id));

        if (!$competition) {
            wp_send_json_error('Competición no encontrada.');
        }

        //$api_key = get_option('mi_app_gemini_api_key');
        $api_key = $this->get_gemini_api_key_for_user();
        if (empty($api_key)) {
            wp_send_json_error('La clave de la API de Gemini no está configurada en el panel de administración.');
        }

        $prompt = $this->build_nutrition_prompt($user_data, $competition);
        //$response = $this->call_gemini_api($api_key, $prompt); //3
        $response = $this->call_gemini_api($api_key, $prompt, 'text');

        if (is_wp_error($response)) {
            wp_send_json_error('Error al contactar la API de Gemini: ' . $response->get_error_message());
        }

        wp_send_json_success($response);
    }

    /**
     * Construye el prompt detallado para la IA.
     */
    private function build_nutrition_prompt($user_data, $competition)
    {
       
        return "Nutricionista ciclismo. Genera plan nutricional para los 3 días previos para los datos del evento y del ciclista adjuntos.

        DATOS DEL USUARIO:
        - {$user_data['peso']} kg, {$user_data['edad']} años, {$user_data['ftp']} W
        - Restricciones Celiaco: {$user_data['es_celiaco']} , Diabético: {$user_data['es_diabetico']}


        DATOS DEL EVENTO:
        - Nombre: {$competition->nombre}
        - Distancia: {$competition->distancia} km
        - Desnivel: {$competition->desnivel} m

        OBJETIVO:
        Maximizar el rendimiento durante el evento.

        INSTRUCCIONES:
        Calcula las necesidades nutricionales y devuelve la respuesta ÚNICAMENTE en formato JSON, sin ningún texto adicional. El JSON debe tener la siguiente estructura exacta:
        {
            \"carb_loading_plan\": {
                \"D-3\": \"gramos de carbohidratos\",
                \"D-2\": \"gramos de carbohidratos\",
                \"D-1\": \"gramos de carbohidratos\"
            },
            \"macronutrient_analysis\": {
                \"carbohydrates\": valor_numérico_en_gramos,
                \"proteins\": valor_numérico_en_gramos,
                \"fats\": valor_numérico_en_gramos
            },
            \"total_calories\": valor_numérico_total_calorías_diarias
        }";
    }


    /**
     * Añade estilos CSS para el análisis
     */
    public function enqueue_analysis_styles()
    {
        if (get_query_var('mi_app_page') === 'competicion') {
            $custom_css = "
                .loader {
                    border: 5px solid #f3f3f3;
                    border-top: 5px solid #3498db;
                    border-radius: 50%;
                    width: 30px;
                    height: 30px;
                    animation: spin 1s linear infinite;
                    margin: 20px auto;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .error {
                    color: #D63638;
                    padding: 10px;
                    background-color: #fef7f7;
                    border-left: 4px solid #D63638;
                    margin: 10px 0;
                }
            ";
            
            wp_add_inline_style('wp-admin', $custom_css);
        }
    }    


    /**
     * Obtiene la clave de API de Gemini correcta para el usuario actual.
     * Prioriza la clave personal del usuario sobre la global del plugin.
     * @return string|null La clave de API o null si no se encuentra ninguna.
     */
     private function get_gemini_api_key_for_user()
     {
     // 1. Obtener el ID del usuario actual
     $user_id = get_current_user_id();
     if (!$user_id) {
     return null; // No hay usuario logueado
     }

     // 2. Intentar obtener la clave personal del usuario
     $user_key = get_user_meta($user_id, 'mi_app_user_gemini_api_key', true);

     // 3. Si el usuario tiene una clave, devolverla
     if (!empty($user_key)) {
     return $user_key;
     }

     // 4. Si no, devolver la clave global configurada en el backend
     return get_option('mi_app_gemini_api_key');
     }


    /**
     * Función para renderizar el campo de la API Key de OpenWeatherMap.
     */
    public function render_openweathermap_key_field()
    {
        $key = get_option('mi_app_openweathermap_api_key');
        echo "<input type='password' id='mi_app_openweathermap_api_key' name='mi_app_openweathermap_api_key' value='" . esc_attr($key) . "' class='regular-text' placeholder='Introduce tu clave de API aquí'>";
        echo "<p class='description'>Introduce tu clave de la API de WeatherAPI. Puedes obtener una gratuita en <a target='blank' href='https://www.weatherapi.com/'>su página web</a>.</p>";
    }

    public function render_opencage_key_field()
    {
        $key = get_option('mi_app_opencage_api_key');
        echo " 
        <input type='password' id='mi_app_opencage_api_key' name='mi_app_opencage_api_key' value='" . esc_attr($key) . "' class='regular-text' placeholder='Introduce tu clave de API aquí'>"; 
        echo "<p class='description'>Introduce tu clave de la API de OpenCage para obtener la ubicación. Puedes obtener una gratuita en <a target='blank' href='https://opencagedata.com/''>su página web</a>.</p>";

    }

    /**
     * Encola el CSS de la librería Weather Icons desde un CDN.
     */
    public function mi_app_enqueue_weather_icons() {
        // URL del CDN de Weather Icons
        $cdn_url = 'https://cdnjs.cloudflare.com/ajax/libs/weather-icons/2.0.10/css/weather-icons.min.css';
        
        // Encolar la hoja de estilos. 
        // 'mi-app-weather-icons' es el handle (nombre único).
        wp_enqueue_style( 'mi-app-weather-icons', $cdn_url, [], '2.0.10' );
    }

    /**
     * Mapea el código de condición de WeatherAPI (ej. 1000) a una clase de Weather Icons (wi).
     *
     * @param int $weatherapi_code Código numérico de condición de WeatherAPI.
     * @return string Clase de icono de Weather Icons.
     */
    private function map_weatherapi_code_to_weather_icon($weatherapi_code)
    {
        $mapping = [
            // Sol / Despejado
            1000 => 'wi-day-sunny', // 1000: Clear / Sunny
            
            // Parcialmente Nublado / Nublado
            1003 => 'wi-day-cloudy', // 1003: Partially cloudy
            1006 => 'wi-cloudy',     // 1006: Cloudy
            1009 => 'wi-cloudy-gusts', // 1009: Overcast

            // Niebla / Bruma
            1030 => 'wi-fog',        // 1030: Mist
            1135 => 'wi-fog',        // 1135: Fog
            1147 => 'wi-fog',        // 1147: Freezing fog

            // Lluvia
            1063 => 'wi-day-showers', // 1063: Patchy light rain
            1150 => 'wi-sprinkle',   // 1150: Light drizzle
            1153 => 'wi-sprinkle',   // 1153: Light drizzle
            1180 => 'wi-showers',    // 1180: Patchy light rain
            1183 => 'wi-rain',       // 1183: Light rain
            1186 => 'wi-day-rain',   // 1186: Moderate rain at times
            1189 => 'wi-rain',       // 1189: Moderate rain
            1192 => 'wi-day-rain',   // 1192: Heavy rain at times
            1195 => 'wi-rain',       // 1195: Heavy rain
            1240 => 'wi-showers',    // 1240: Light rain shower

            // Nieve / Aguanieve
            1069 => 'wi-sleet',      // 1069: Patchy light sleet
            1213 => 'wi-snow',       // 1213: Light snow
            1216 => 'wi-day-snow',   // 1216: Patchy moderate snow
            1219 => 'wi-snow',       // 1219: Moderate snow

            // Tormenta
            1087 => 'wi-thunderstorm', // 1087: Thundery outbreaks possible
            1273 => 'wi-thunderstorm', // 1273: Patchy light rain with thunder
            1276 => 'wi-storm-showers', // 1276: Moderate or heavy rain with thunder
            
            // Códigos no cubiertos o desconocidos
            //default => 'wi-na',
        ];

        return $mapping[$weatherapi_code] ?? 'wi-na'; // Devolver 'wi-na' si el código no se encuentra.
    }

/**
     * Maneja la lógica del widget de tiempo con caché (Transients).
     */
    public function handle_get_weather_dashboard_widget($atts = [])
    {
        try {
            // --- PASO 1: OBTENER DATOS DEL USUARIO DE FORMA SEGURA ---
            $user_id = get_current_user_id();
            $ciudad = get_user_meta($user_id, 'mi_app_ciudad', true);
            $pais = get_user_meta($user_id, 'mi_app_pais', true);
            
            $api_key = get_option('mi_app_openweathermap_api_key'); // WeatherAPI Key
            $api_key_open = get_option('mi_app_opencage_api_key'); // OpenCageData Key

            // --- VALIDACIONES BÁSICAS ---
            if (empty($ciudad) || empty($pais) || empty($api_key) || empty($api_key_open)) {
                // Mensajes de error simplificados, se asume que las claves y ubicación están configuradas
                if (empty($ciudad) || empty($pais)) {
                    return '<div class="weather-widget"><p>Configura tu ciudad y país para ver el tiempo.</p></div>';
                }
                return '<div class="weather-widget"><p>Error: Falta configuración de API Key.</p></div>';
            }

            // --- 🔑 GESTIÓN DE CACHÉ (TRANSIENT) ---
            $cache_key = 'mi_app_weather_' . sanitize_title($ciudad . '-' . $pais);
            $cache_duration = 60 * 60; // 1 hora
            
            // 1. Intentar obtener el resultado de la caché
            $cached_html = get_transient($cache_key);

            if ($cached_html !== false) {
                error_log('CACHÉ: Pronóstico cargado desde Transient para ' . $ciudad . '.');
                return $cached_html; // Devolver la versión cacheadada y terminar
            }
            
            error_log('CACHÉ: No se encontró caché. Ejecutando llamada(s) API para ' . $ciudad . '...');
            // --- FIN GESTIÓN DE CACHÉ ---


            // --- PASO 2: OBTENER COORDENADAS CON OPENCAGEDATA ---
            $geocode_url = 'https://api.opencagedata.com/geocode/v1/json?q=' . rawurlencode($ciudad . ', ' . $pais) . '&key=' . $api_key_open . '&limit=1&pretty=1';
            
            $geocode_response = wp_remote_get($geocode_url);
            if (is_wp_error($geocode_response)) {
                error_log('ERROR en wp_remote_get (OpenCage): ' . $geocode_response->get_error_message());
                return '<div class="weather-widget"><p>Error de conexión al buscar la ubicación.</p></div>';
            }
            $location_data = json_decode(wp_remote_retrieve_body($geocode_response), true);
            
            if (!isset($location_data['results'][0]['geometry'])) {
                 error_log('ERROR: Fallo al encontrar coordenadas con OpenCage.');
                 return '<div class="weather-widget"><p>No se han podido encontrar las coordenadas para "' . esc_html($ciudad) . '".</p></div>';
            }

            // --- PASO 3: EXTRAER COORDENADAS ---
            $lat = $location_data['results'][0]['geometry']['lat'];
            $lon = $location_data['results'][0]['geometry']['lng'];
            
            // --- PASO 4: OBTENER PREVISIÓN DEL TIEMPO DE WEATHERAPI.COM (USANDO COORDENADAS) ---
            $location_query = "{$lat},{$lon}";
            $days_count = get_option('mi_app_weather_days', 3); 
            $weather_url = "https://api.weatherapi.com/v1/forecast.json?key={$api_key}&q={$location_query}&days={$days_count}&aqi=no&alerts=no&lang=es";

            $weather_response = wp_remote_get($weather_url);
            $api_data = json_decode(wp_remote_retrieve_body($weather_response), true);
            
            if (is_wp_error($weather_response) || empty($api_data) || !isset($api_data['forecast']['forecastday'])) {
                error_log('ERROR: Fallo al obtener o procesar datos de WeatherAPI.com.');
                return '<div class="weather-widget"><p>No se ha podido procesar la respuesta del tiempo.</p></div>';
            }
            
            $ciudad_mostrada = isset($api_data['location']['name']) ? $api_data['location']['name'] : $ciudad;

            // --- PASO 5: PROCESAR DATOS ---
            $daily_forecasts = [];
            foreach ($api_data['forecast']['forecastday'] as $forecast_day) {
                
                $date_time = strtotime($forecast_day['date']);
                $day_data = $forecast_day['day']; 

                $daily_forecasts[] = [
                    'date' => $forecast_day['date'],
                    'day_name' => date_i18n('l', $date_time), 
                    'temp_min' => round($day_data['mintemp_c']), 
                    'temp_max' => round($day_data['maxtemp_c']), 
                    'description' => $day_data['condition']['text'], 
                    'icon' => $day_data['condition']['code'], 
                    'icon_url' => $day_data['condition']['icon'],
                    //'icon_class' =>$this->map_weatherapi_code_to_weather_icon($day_data['condition']['code']),
                ];
            }

            if (empty($daily_forecasts)) {
                return '<div class="weather-widget"><p>No se han podido obtener datos de la previsión.</p></div>';
            }

            // --- PASO 6: CONSTRUIR HTML ---
            ob_start();
            ?>
            <div class="weather-widget">
                <h4>Previsión del Tiempo en <?php echo esc_html($ciudad_mostrada); ?></h4>
                <div class="weather-forecast">
                    <?php foreach ($daily_forecasts as $day): ?>
                        <div class="weather-day">
                            <div class="weather-date">
                                <span class="day-name"><?php echo esc_html($day['day_name']); ?></span>
                                <span class="day-date"><?php echo esc_html(date_i18n('d/m', strtotime($day['date']))); ?></span>
                            </div>
                            <div class="weather-details">
                                <!--<i class="wi <?php echo esc_attr($day['icon_class']); ?>" aria-hidden="true"></i>-->
                                <img src="https:<?php echo esc_url($day['icon_url']); ?>" alt="<?php echo esc_attr($day['description']); ?>">
                                <div class="temps">
                                    <span class="temp-max"><?php echo $day['temp_max']; ?>°C</span>
                                    <span class="temp-min"><?php echo $day['temp_min']; ?>°C</span>
                                    <span class="description"><?php echo esc_attr($day['description']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $output_html = ob_get_clean();

            // 2. Guardar el resultado HTML en caché
            set_transient($cache_key, $output_html, $cache_duration);
            error_log('CACHÉ: Pronóstico guardado en Transient por ' . $cache_duration . ' segundos.');

            return $output_html;

        } catch (Exception $e) {
            error_log('EXCEPCIÓN CAPTURADA: ' . $e->getMessage());
            return '<div class="weather-widget"><p>Ha ocurrido un error inesperado al cargar el tiempo.</p></div>';
        }
    }

     /**
     * Función para renderizar el campo del número de días del pronóstico.
     */
     public function render_weather_days_field()
     {
     $days = get_option('mi_app_weather_days', 3); // 3 es el valor por defecto
     echo "<input type='number' id='mi_app_weather_days' name='mi_app_weather_days' value='" . esc_attr($days) . "' class='regular-text' min='1' max='7' step='1'>";
     echo "<p class='description'>Número de días del pronóstico del tiempo a mostrar en el dashboard (1-7).</p>";
     }    


    /**
     * Obtiene y formatea la previsión del tiempo para el dashboard, usando caché.
     * @return string El HTML de la previsión o un string vacío si hay un error.
     */
    public function get_weather_forecast_html()
    {
        $user_id = get_current_user_id();
        
        // 1. Obtener la ubicación del usuario
        $ciudad = get_user_meta($user_id, 'mi_app_ciudad', true);
        $pais = get_user_meta($user_id, 'mi_app_pais', true);
        

        //error_log("Ciudad: " . $ciudad);
        //error_log("Pais: " . $pais);

        if (empty($ciudad) || empty($pais)) {
            return '<div class="weather-forecast-error">Para ver la previsión, por favor, configura tu ciudad y país en tu <a href="' . home_url('/mi-app/perfil/') . '">perfil</a>.</div>';
        }

        // 2. Comprobar el caché (usamos Transients de WordPress)
        $cache_key = 'mi_app_weather_cache_' . $user_id;
        $cached_html = get_transient($cache_key);

        if (false !== $cached_html) {
            return $cached_html;
        }

        // 3. Obtener la API Key de OpenWeatherMap
        $api_key = get_option('mi_app_openweathermap_api_key');
        if (empty($api_key)) {
            return '<div class="weather-forecast-error">La API de Weatherapi no está configurada por el administrador.</div>';
        }

        // 4. Llamar a la API de OpenWeatherMap (5 day / 3 hour forecast)
        $api_url = "https://api.openweathermap.org/data/2.5/forecast?q=" . rawurlencode($ciudad . ',' . $pais) . "&appid={$api_key}&units=metric&lang=es";

        //$api_url = "https://api.openweathermap.org/data/2.5/forecast?q={$ciudad}&appid={$api_key}&units=metric&lang=es&cnt={$days}";

        //error_log("API URL Weather: " . $api_url);

        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) {
            return '<div class="weather-forecast-error">No se ha podido conectar con el servicio de previsión meteorológica.</div>';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data->list)) {
            return '<div class="weather-forecast-error">Error al procesar los datos de la previsión.</div>';
        }

        // 5. Formatear la respuesta en HTML
        $html = '<div class="weather-forecast-container"><h3>Previsión del Tiempo para ' . esc_html($ciudad) . '</h3><div class="weather-cards">';
        
        $daily_forecasts = [];
        foreach ($data->list as $entry) {
            $date = date('Y-m-d', strtotime($entry->dt_txt));
            if (!isset($daily_forecasts[$date])) {
                $daily_forecasts[$date] = [
                    'temp_max' => -273, // Inicializamos con valores imposibles
                    'temp_min' => 999,
                    'description' => $entry->weather[0]->description,
                    'icon' => $entry->weather[0]->icon,
                ];
            }
            // Actualizamos temperaturas máximas y mínimas para ese día
            $daily_forecasts[$date]['temp_max'] = max($daily_forecasts[$date]['temp_max'], $entry->main->temp_max);
            $daily_forecasts[$date]['temp_min'] = min($daily_forecasts[$date]['temp_min'], $entry->main->temp_min);
        }

        foreach ($daily_forecasts as $date => $forecast) {
            $day_name = date('l', strtotime($date)); // Nombre del día (Lunes, Martes...)
            $icon_url = 'https://openweathermap.org/img/wn/' . $forecast['icon'] . '@2x.png';
            $html .= <<<HTML
                <div class="weather-card">
                    <div class="weather-card-header">
                        <span class="weather-day-name">{$day_name}</span>
                        <span class="weather-date">{$date}</span>
                    </div>
                    <div class="weather-card-body">
                        <img src="{$icon_url}" alt="{$forecast['description']}" class="weather-icon">
                        <div class="weather-temps">
                            <span class="temp-max">{$forecast['temp_max']}°C</span>
                            <span class="temp-min">{$forecast['temp_min']}°C</span>
                        </div>
                    </div>
                </div>
HTML;
        }

        $html .= '</div></div>';

        // 6. Guardar el resultado en el caché por 6 horas (21600 segundos)
        set_transient($cache_key, $html, 6 * HOUR_IN_SECONDS);

        return $html;
    }


    /**
     * Realiza una llamada a la API de Google Gemini.
     *
     * @param string $api_key La clave de la API de Gemini.
     * @param string $prompt El texto del prompt a enviar.
     * @param string $model_name El nombre del modelo a usar (ej: 'gemini-pro', 'gemini-2.5-flash-lite'). Por defecto 'gemini-pro'.
     * @param string $return_type El tipo de retorno esperado: 'text' para texto plano o 'json_array' para un array PHP. Por defecto 'text'.
     * @return string|array|WP_Error El texto generado, un array PHP, o un objeto WP_Error en caso de fallo.
     */
    private function call_gemini_api($api_key, $prompt, $return_type = 'text')
    {
        $model_name = 'gemini-2.5-flash-lite';
        //$model_name = 'gemini-2.0-flash-lite';
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model_name . ':generateContent?key=' . $api_key;
        //$api_url = 'https://generativelanguage.googleapis.com/v1/models/' . $model_name . ':generateContent?key=' . $api_key;
        error_log('prompt: ' . $api_url);

        $body = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        $response = wp_remote_post($api_url, [
            'method'    => 'POST',
            'headers'   => [
                'Content-Type' => 'application/json',
            ],
            'body'      => $body,
            'timeout'   => 180, // Un timeout de 60 segundos para análisis largos
        ]);

        if (is_wp_error($response)) {
            $this->debug_log('Error en wp_remote_post: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        $this->debug_log('Respuesta cruda de la API de Gemini (Código: ' . $response_code . '): ' . $response_body);

        if ($response_code !== 200) {
            $error_message = "Error en la API de Gemini. Código: {$response_code}. Respuesta: {$response_body}";
            $this->debug_log($error_message);
            return new WP_Error('api_error', $error_message);
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = "Error al decodificar la respuesta JSON de Gemini: " . json_last_error_msg();
            $this->debug_log($error_message);
            return new WP_Error('json_decode_error', $error_message);
        }

        // La respuesta de Gemini está anidada. Extraemos el texto.
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $error_message = "La respuesta de Gemini no tiene el formato esperado. Respuesta: " . $response_body;
            $this->debug_log($error_message);
            return new WP_Error('invalid_response_format', $error_message);
        }

        $raw_text = $data['candidates'][0]['content']['parts'][0]['text'];

        // Manejamos el tipo de retorno
        if ($return_type === 'json_array') {
            // Quitamos los bloques de código ```json ... ```
            $json_string = preg_replace('/^\s*```json\s*|\s*```\s*$/', '', $raw_text);
            $plan = json_decode($json_string, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $plan;
            } else {
                $error_message = "La respuesta de la IA no contenía un JSON válido. Error: " . json_last_error_msg() . ". Texto recibido: " . $raw_text;
                $this->debug_log($error_message);
                return new WP_Error('json_decode_error', $error_message);
            }
        }

        // Por defecto, devolvemos el texto plano
        return $raw_text;
    }



    /**
     * Maneja la llamada AJAX para generar un plan de comidas completo.
     */
    public function handle_generate_full_meal_plan()
    {
        if (!wp_verify_nonce($_POST['generate_full_meal_plan_security'], 'generate_full_meal_plan_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
            wp_die();
        }

        $user_id = get_current_user_id();
        $competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
        if (!$user_id || !$competition_id) {
            wp_send_json_error('Faltan datos del usuario o de la competición.');
            wp_die();
        }

        $cache_key = $this->get_cache_key("nutrition_plan_{$competition_id}");
        $cached_plan = get_transient($cache_key);

        if ($cached_plan !== false) {
            wp_send_json_success($cached_plan);
            wp_die();
        }

        $user_data = [
            'peso' => get_user_meta($user_id, 'mi_app_peso', true),
            'edad' => get_user_meta($user_id, 'mi_app_edad', true),
            'ftp' => get_user_meta($user_id, 'mi_app_ftp', true),
            'experiencia' => get_user_meta($user_id, 'mi_app_experiencia', true),
            'es_celiaco' => get_user_meta($user_id, 'mi_app_celiaco', true),
            'es_diabetico' => get_user_meta($user_id, 'mi_app_diabetico', true),
        ];

        global $wpdb;
        $comp_table = $wpdb->prefix . 'mi_app_competiciones';
        $competition = $wpdb->get_row($wpdb->prepare("SELECT nombre, distancia, desnivel FROM $comp_table WHERE id = %d AND user_id = %d", $competition_id, $user_id));

        if (!$competition) {
            wp_send_json_error('La competición no se encontró o no tienes permiso para acceder a ella.');
            wp_die();
        }

        //$api_key = get_option('mi_app_gemini_api_key');
        $api_key = $this->get_gemini_api_key_for_user();
        if (empty($api_key)) {
            wp_send_json_error('La clave de la API de Gemini no está configurada en el panel de administración.');
            wp_die();
        }

        $strava_insights = null;
        $access_token = get_user_meta($user_id, 'strava_access_token', true);
        if ($access_token) {
            $activities = $this->get_strava_activities($user_id, 30);
            if (!is_wp_error($activities)) {
                $strava_insights = $this->analyze_strava_data($activities);
            }
        }

        $prompt = $this->build_full_meal_plan_prompt($user_data, $competition, $strava_insights);
        //$plan_data = $this->call_gemini_api($api_key, $prompt);
        $plan_data = $this->call_gemini_api($api_key, $prompt, 'json_array');

        if (is_wp_error($plan_data)) {
            wp_send_json_error('Error al procesar la respuesta de la IA: ' . $plan_data->get_error_message());
        } else {
            set_transient($cache_key, $plan_data, 30 * DAY_IN_SECONDS);

            global $wpdb;
            $plan_table = $wpdb->prefix . 'mi_app_nutrition_plans';

            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$plan_table} (user_id, competition_id, plan_name, plan_data, created_at) 
                     VALUES (%d, %d, %s, %s, %s)
                     ON DUPLICATE KEY UPDATE 
                     plan_name = VALUES(plan_name), 
                     plan_data = VALUES(plan_data), 
                     created_at = VALUES(created_at)",
                    $user_id,
                    $competition_id,
                    $plan_data['plan_name'],
                    json_encode($plan_data),
                    current_time('mysql')
                )
            );

            wp_send_json_success($plan_data);
        }

        wp_die();
    }

    /**
     * Construye el prompt detallado para la IA, incluyendo insights de Strava.
     */
    private function build_full_meal_plan_prompt($user_data, $competition, $strava_insights = null)
    {
        $celiaco = get_user_meta($user_id, 'mi_app_celiaco', true);
        $diabetico = get_user_meta($user_id, 'mi_app_diabetico', true);
        $prompt = "Nutricionista ciclismo. Genera plan de comidas completo para los 3 días previos según los datos adjuntos

        DATOS DEL USUARIO:
        - {$user_data['peso']} kg, {$user_data['edad']} años, {$user_data['ftp']} W, Celiaco: {$user_data['es_celiaco']} , Diabético: {$user_data['es_diabetico']}

        DATOS DEL EVENTO:
        - {$competition->nombre}, {$competition->distancia} km, {$competition->desnivel}m D+";

        if ($strava_insights && isset($strava_insights['summary'])) {
            $prompt .= "

        DATOS DE RENDIMIENTO RECIENTE (DE STRAVA):
        - Resumen: {$strava_insights['summary']}
        - Carga de Entrenamiento (escala 1-10): {$strava_insights['training_load']}
        - Fatiga Acumulada (escala 1-10): {$strava_insights['fatiga']}

        INSTRUCCIONES ESPECÍFICAS BASADAS EN STRAVA:
        - Fatiga >7: Prioriza Rec. +P, CH fácil digestión.
        - Carga Alta/Fatiga Baja: Carga CH robusta.
        - Ambos Bajos: Estándar..";
        }

        $prompt .= "

        OBJETIVO:
        Maximizar el rendimiento durante el evento.

        Plan Carga (D-3, D-2, D-1): Desayuno, Almuerzo, Cena + Snack.
        Crea Receta Detallada por comida.
        Calcula Macronutrientes/Calorías por receta. Output: SOLO JSON (Sin texto adicional).
        [Estructura JSON Mantenida]:";

        $json_structure = '{
            "plan_name": "Nombre sugerido para el plan",';

        if ($strava_insights && isset($strava_insights['summary'])) {
            $json_structure .= '
            "strava_insights": {
                "summary": "Explica brevemente cómo los datos de Strava (carga y fatiga) han influido en las decisión nutricional. Sé específico.",
                "training_load": ' . intval($strava_insights['training_load']) . ',
                "fatiga": ' . intval($strava_insights['fatiga']) . '
            },';
        }

        $json_structure .= '
            "days": [
                {
                    "day": "D-3",
                    "meals": [
                        { 
                            "type": "desayuno", 
                            "name": "", 
                            "ing": "", 
                            "inst": "",
                            "carbs": 0, "protein": 0, "fats": 0, "calories": 0
                        },
                        { 
                            "type": "almuerzo", 
                            "name": "", 
                            "ingredients": "", 
                            "instructions": "",
                            "carbs": , "protein": , "fats": , "calories":
                        },
                        { 
                            "type": "cena", 
                            "name": "", 
                            "ingredients": "", 
                            "instructions": "",
                            "carbs": , "protein": , "fats": , "calories": 
                        },
                        { 
                            "type": "snack", 
                            "name": "", 
                            "ingredients": "", 
                            "instructions": "",
                            "carbs": , "protein": , "fats": , "calories": 
                        }
                    ]
                },
                {
                    "day": "D-2",
                    "meals": [
                        // (repetir la estructura para D-2 y D-1)
                    ]
                },
                {
                    "day": "D-1",
                    "meals": [
                        // (repetir la estructura para D-2 y D-1)
                    ]
                }
            ]
        }';

        $prompt .= $json_structure;

        return $prompt;
        //error_log('Prompt: ' . $prompt);
    }

    /**
     * Maneja la llamada AJAX para guardar una receta.
     */
    public function handle_save_recipe()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'save_recipe_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Debes estar logueado.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_app_recipes';

        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'name' => sanitize_text_field($_POST['name']),
                'type' => sanitize_text_field($_POST['type']),
                'ingredients' => sanitize_textarea_field($_POST['ingredients']),
                'instructions' => sanitize_textarea_field($_POST['instructions']),
                'carbs' => floatval($_POST['carbs']),
                'protein' => floatval($_POST['protein']),
                'fats' => floatval($_POST['fats']),
                'calories' => intval($_POST['calories']),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%s']
        );

        wp_send_json_success('Receta guardada correctamente.');
    }

    /**
     * Maneja la llamada AJAX para obtener un plan nutricional guardado.
     */
    public function handle_get_saved_nutrition_plan()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'get_saved_plan_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
        }

        $user_id = get_current_user_id();
        $competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;

        if (!$user_id || !$competition_id) {
            wp_send_json_error('Faltan datos.');
        }

        global $wpdb;
        $plan_table = $wpdb->prefix . 'mi_app_nutrition_plans';

        $saved_plan = $wpdb->get_row($wpdb->prepare(
            "SELECT plan_data FROM {$plan_table} WHERE user_id = %d AND competition_id = %d",
            $user_id,
            $competition_id
        ));

        if ($saved_plan) {
            $plan_data = json_decode($saved_plan->plan_data, true);
            wp_send_json_success($plan_data);
        } else {
            wp_send_json_error('No se encontró un plan guardado para esta competición.');
        }

        wp_die();
    }

    /**
     * Crea una clave de caché única para el usuario actual.
     */
    private function get_cache_key($identifier)
    {
        $user_id = get_current_user_id();
        return 'mi_app_' . $identifier . '_user_' . $user_id;
    }

    /**
     * Analiza un array de actividades de Strava y calcula la carga y la fatiga.
     */
    private function analyze_strava_data($activities)
    {
        $training_load = 0;
        $fatigue = 0;

        $total_training_stress_score = 0; // Se usará como base para 'training_load'
        $total_fatigue_impulse = 0;      // Se usará como base para 'fatigue'
        $total_time_seconds = 0;
        $total_distance_meters = 0;
        $recent_activities_count = 0;


        // Umbrales de intensidad base (aproximados, usados para IF - Intensity Factor)

        // 1. Obtener el FTP del usuario actual para un cálculo preciso
         $current_user_id = get_current_user_id();
         $user_ftp = get_user_meta($current_user_id, 'mi_app_ftp', true);
         $user_fcmax = get_user_meta($current_user_id, 'mi_app_fcmax', true);

        // 2. Establecer el umbral de vatios base (FTP) con un valor por defecto
        // Usamos 160 como fallback si el usuario no ha configurado su FTP.
         $BASE_WATTS = (!empty($user_ftp) && is_numeric($user_ftp)) ? (int)$user_ftp : 160;

        // Umbrales de intensidad base (aproximados, usados para IF - Intensity Factor)
        // Usamos 180 como fallback si el usuario no ha configurado su FC MÁX.
         $BASE_HEARTRATE = (!empty($user_fcmax) && is_numeric($user_fcmax)) ? (int)$user_fcmax : 180;

        $contributing_activity_types = ['Ride', 'Run', 'Walk', 'Hike', 'Swim', 'Workout', 'AlpineSki', 'BackcountrySki'];

        foreach ($activities as $activity) {
            if (in_array($activity->type, $contributing_activity_types)) {
                $recent_activities_count++;
                $time_hours = $activity->moving_time / 3600;
                //$distance_km = $activity->distance / 1000;

            // 1. CÁLCULO DEL FACTOR DE INTENSIDAD (IF)
            // Se prioriza la potencia, luego el ritmo cardíaco, sino IF = 1 (esfuerzo estándar).
            $intensity_factor = 1.0;
            if (isset($activity->average_watts) && $activity->average_watts > 0) {
                // Cálculo simple de intensidad basado en vatios base
                $intensity_factor = $activity->average_watts / $BASE_WATTS; 
            } elseif (isset($activity->average_heartrate) && $activity->average_heartrate > 0) {
                // Cálculo simple de intensidad basado en ritmo cardíaco base
                $intensity_factor = $activity->average_heartrate / $BASE_HEARTRATE;
            }
            
            // 2. CÁLCULO DE LA CARGA (TSS Estimado o Ponderado)
            // Usamos una fórmula similar al TSS, donde la carga es (Tiempo * IF^2).
            // Esto es mucho más preciso que solo tiempo/distancia.
            $activity_stress_score = ($time_hours * ($intensity_factor * $intensity_factor)) * 100;
            
            // 3. PONDERACIÓN POR TIPO DE DEPORTE (TRIMPS/RPE)
            // Aunque TSS es específico para Ciclismo/Carrera, usamos tus factores para ponderar.
            $activity_load_factor = 1.0;
                switch ($activity->type) {
                    case 'Ride':
                    case 'Run':
                        $activity_load_factor = 1.5;
                        break;
                    case 'Swim':
                        $activity_load_factor = 1.8;
                        break;
                    case 'Hike':
                    case 'AlpineSki':
                    case 'BackcountrySki':
                        $activity_load_factor = 1.3;
                        break;
                    case 'Walk':
                    case 'Workout':
                        $activity_load_factor = 0.8;
                        break;
                }
            
            // Acumulación: La carga total (training_load) usa el Stress Score.
            $total_training_stress_score += $activity_stress_score;
            
            // La Fatiga (fatigue) se calcula con una fórmula simple de impulso: tiempo * IF * factor de deporte.
            $total_fatigue_impulse += $time_hours * $intensity_factor * $activity_load_factor; 

            $total_time_seconds += $activity->moving_time;
            $total_distance_meters += $activity->distance;

            }
        }

        if ($recent_activities_count === 0) {
            return null;
        }

        // 4. NORMALIZACIÓN (Corregida)
        // Se utiliza el valor acumulado ($total_training_stress_score y $total_fatigue_impulse).
        
        // Normalización de la Carga (Acute Training Load - ATL):
        // La carga aguda se basa en las últimas 7-14 actividades. Usamos 700 como un umbral alto de TSS acumulado.
        $normalized_load = min(10, max(1, round($total_training_stress_score / 70))); 

        // Normalización de la Fatiga (Chronic Training Load - CTL):
        // La fatiga crónica se basa en la media móvil de las últimas 28-42 actividades. 
        // Usamos $total_fatigue_impulse / 15 para un umbral alto de impulso.
        $normalized_fatigue = min(10, max(1, round($total_fatigue_impulse / 15)));

        return [
            'training_load' => $normalized_load, // Carga (basada en TSS)
            'fatiga' => $normalized_fatigue,     // Fatiga (basada en impulso)
            'summary' => sprintf(
                "En tus últimas %d actividades, has acumulado un total de %d km y %d horas.",
                $recent_activities_count,
                round($total_distance_meters / 1000),
                round($total_time_seconds / 3600)
            )
        ];
    }

    /**
     * Obtiene las actividades del usuario desde la API de Strava.
     * Obtiene la lista resumida y luego hace llamadas detalladas (activities/{id}) 
     * para obtener datos detallados como 'weighted_average_watts' (NP) para las Rides.
     * * @param int $user_id ID del usuario en WordPress.
     * @param int $limit Número de actividades a obtener (últimas N).
     * @return array|WP_Error Array de objetos de actividad detallados o error.
     */
    public function get_strava_activities($user_id, $limit = 30)
    {
        // --- 1. CONFIGURACIÓN INICIAL Y CACHÉ DETALLADA ---
        $cache_key = $this->get_cache_key("strava_activities_detailed_{$limit}");
        $cached_activities = get_transient($cache_key_detailed);

        // Devolver datos de la caché si están disponibles
        if ($cached_activities !== false) {
            return $cached_activities;
        }

        // Obtener y refrescar token si es necesario (asume que $this->refresh_strava_token existe)
        $access_token = get_user_meta($user_id, 'strava_access_token', true);
        $expires_at = get_user_meta($user_id, 'strava_expires_at', true);

        if (empty($access_token)) {
            return new WP_Error('no_token', 'No hay un token de acceso de Strava.');
        }

        if (time() > $expires_at) {
            $new_tokens = $this->refresh_strava_token($user_id);
            if (is_wp_error($new_tokens) || !$new_tokens) {
                return new WP_Error('token_refresh_failed', 'No se pudo refrescar el token de Strava. Por favor, vuelve a conectar tu cuenta.');
            }
            $access_token = $new_tokens['access_token'];
        }

        // --- 2. OBTENER LA LISTA RESUMIDA ---
        $url_summary = add_query_arg(['per_page' => $limit], 'https://www.strava.com/api/v3/athlete/activities');
        $response_summary = wp_remote_get($url_summary, [
            'headers' => ['Authorization' => 'Bearer ' . $access_token],
        ]);

        if (is_wp_error($response_summary) || wp_remote_retrieve_response_code($response_summary) !== 200) {
            return new WP_Error('api_error', 'Error al obtener la lista de actividades de Strava.');
        }

        $activities_summary = json_decode(wp_remote_retrieve_body($response_summary), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', 'Error al decodificar la respuesta resumida de Strava.');
        }

        // --- 3. ITERAR Y OBTENER DETALLES (NP) ---
        $activities = [];
        $base_headers = ['Authorization' => 'Bearer ' . $access_token];

        foreach ($activities_summary as $activity_summary) {
            
            // Solo obtener detalles para 'Ride' (actividades que pueden tener NP)
            if ($activity_summary['type'] === 'Ride') {
                $activity_id = $activity_summary['id'];
                $url_detail = "https://www.strava.com/api/v3/activities/{$activity_id}";

                // Llamada a la API para obtener la vista detallada
                $detail_response = wp_remote_get($url_detail, ['headers' => $base_headers]);
                
                if (!is_wp_error($detail_response) && wp_remote_retrieve_response_code($detail_response) === 200) {
                    $activity_detail = json_decode(wp_remote_retrieve_body($detail_response), true);
                    
                    // Fusionar el resumen con los detalles para obtener weighted_average_watts
                    $merged_activity = array_merge($activity_summary, $activity_detail);
                    $activities[] = (object) $merged_activity;
                } else {
                    // Si la llamada detallada falla, usamos solo el resumen
                    $activities[] = (object) $activity_summary; 
                }
            } else {
                // Actividades que no son Ride se pasan sin cambios
                $activities[] = (object) $activity_summary;
            }
        }

        // --- 4. CACHÉ FINAL ---
        // Se recomienda usar un tiempo de caché más largo para esta función debido a las múltiples llamadas API
        set_transient($cache_key_detailed, $activities, 8 * HOUR_IN_SECONDS);

        return $activities;
    }


    /**
     * Maneja la llamada AJAX para generar y descargar un GPX con waypoints de nutrición.
     */
    public function handle_download_nutrition_gpx()
    {
        // 1. Verificar el nonce de seguridad
        if (!wp_verify_nonce($_POST['nonce'], 'download_nutrition_gpx_nonce')) {
            wp_die('Error de seguridad. Nonce inválido.');
        }

        $user_id = get_current_user_id();
        $competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;

        if (!$user_id || !$competition_id) {
            wp_die('Faltan datos del usuario o de la competición.');
        }

        global $wpdb;

        // 2. Obtener el análisis de nutrición guardado
        $plan_table = $wpdb->prefix . 'mi_app_nutrition_plans';
        $analysis = $wpdb->get_row($wpdb->prepare(
            "SELECT plan_data FROM {$plan_table} WHERE user_id = %d AND competition_id = %d AND plan_type = %s",
            $user_id,
            $competition_id,
            'gpx_analysis'
        ));

        if (!$analysis) {
            wp_die('No se encontró un análisis de nutrición para esta competición. Por favor, genera un análisis primero.');
        }

        // 3. Obtener la URL del archivo GPX original
        $comp_table = $wpdb->prefix . 'mi_app_competiciones';
        $competition = $wpdb->get_row($wpdb->prepare("SELECT gpx_file_url, nombre FROM {$comp_table} WHERE id = %d AND user_id = %d", $competition_id, $user_id));

        if (!$competition || !$competition->gpx_file_url) {
            wp_die('No se encontró el archivo GPX original para esta competición.');
        }

        // 4. Cargar el contenido del GPX original
        $gpx_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $competition->gpx_file_url);
        if (!file_exists($gpx_path)) {
            wp_die('El archivo GPX original no existe en el servidor.');
        }

        // Requerir extensión SimpleXML
        if (!extension_loaded('simplexml')) {
            wp_die('La extensión SimpleXML de PHP es necesaria para esta función pero no está activada en tu servidor.');
        }

        $original_gpx = simplexml_load_file($gpx_path);
        if ($original_gpx === false) {
            wp_die('No se pudo leer el archivo GPX original. Puede que esté corrupto.');
        }

        // 5. Extraer los puntos de nutrición del análisis HTML
        $nutrition_plan = $this->extract_nutrition_from_html($analysis->plan_data);
        if (empty($nutrition_plan)) {
            wp_die('No se pudo extraer el plan de nutrición del análisis.');
        }

        // 6. Crear el nuevo GPX con waypoints
         $new_gpx_content = $this->create_enriched_gpx($gpx_path, $nutrition_plan);

        if ($new_gpx_content === false) {
            wp_die('Ocurrió un error al generar el archivo GPX enriquecido. Por favor, revisa los logs del servidor.');
        }


        // 7. Servir el archivo para su descarga
        $filename = sanitize_file_name($competition->nombre . '_con_nutricion.gpx');

        header('Content-Type: application/gpx+xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        echo $new_gpx_content;
        exit;
    }


     public function handle_analyze_gpx_sse()
     {
         // --- LÍNEA DE DEPURACIÓN CLAVE ---
         // Registremos todo lo que el servidor recibe para ver si 'competition_id' llega.
         error_log('SSE Debug: Parámetros GET recibidos: ' . print_r($_GET, true));

         // 1. Verificar el nonce de seguridad
         if (!wp_verify_nonce($_GET['nonce'], 'analyze_gpx_sse_nonce')) {
         $this->send_sse_message(['status' => 'error', 'message' => 'Error de seguridad. Nonce inválido.']);
         exit;
         }

         $user_id = get_current_user_id();
         $competition_id = isset($_GET['competition_id']) ? intval($_GET['competition_id']) : 0;

         if (!$user_id || !$competition_id) {
         $this->send_sse_message(['status' => 'error', 'message' => 'Faltan datos del usuario o de la competición.']);
         exit;
         }

         // Establecer las cabeceras para SSE
         header('Content-Type: text/event-stream');
         header('Cache-Control: no-cache');
         header('Connection: keep-alive');
         if (ob_get_level()) {
         ob_end_clean();
         }

         // Ejecutar el proceso de análisis paso a paso, enviando actualizaciones
         try {
         $this->send_sse_message(['status' => 'progress', 'message' => '🔍 Verificando si ya existe un análisis guardado...']);

         // Comprobar si ya existe un análisis
         $existing_analysis = $this->get_saved_analysis($competition_id, $user_id);
         if ($existing_analysis) {
         $this->send_sse_message(['status' => 'complete', 'html' => $existing_analysis]);
         exit;
         }

         $this->send_sse_message(['status' => 'progress', 'message' => '📁 Cargando datos de la competición y el archivo GPX...']);
         $data = $this->get_analysis_data($competition_id, $user_id);

         $this->send_sse_message(['status' => 'progress', 'message' => '🤖 Contactando a la IA para generar el análisis... Esto puede tardar un momento.']);
         $analysis_text = $this->perform_gemini_analysis($data);

         $this->send_sse_message(['status' => 'progress', 'message' => '✅ Dando formato al resultado...']);
         $html = $this->format_analysis_response($analysis_text);

         $this->send_sse_message(['status' => 'progress', 'message' => '💾 Guardando el análisis para futuras consultas...']);
         $this->save_analysis($competition_id, $user_id, $data['competition']->nombre, $html);

         $has_nutrition_plan = $this->check_if_nutrition_plan_exists($competition_id, $user_id);

         // Enviar el resultado final
         $this->send_sse_message(['status' => 'complete', 'html' => $html, 'has_plan' => $has_nutrition_plan]);

         } catch (Exception $e) {
         $this->send_sse_message(['status' => 'error', 'message' => 'Ocurrió un error inesperado: ' . $e->getMessage()]);
         }

         exit;
     }

    /**
     * Obtiene los datos necesarios para el análisis.
     */
    private function get_analysis_data($competition_id, $user_id)
    {
        global $wpdb;
        $comp_table = $wpdb->prefix . 'mi_app_competiciones';
        $competition = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$comp_table} WHERE id = %d AND user_id = %d", $competition_id, $user_id));

        if (!$competition || !$competition->gpx_file_url) {
            throw new Exception('No se encontró la competición o el GPX asociado.');
        }

        $user_data = [
            'peso' => get_user_meta($user_id, 'mi_app_peso', true),
            'altura' => get_user_meta($user_id, 'mi_app_altura', true),
            'edad' => get_user_meta($user_id, 'mi_app_edad', true),
            'genero' => get_user_meta($user_id, 'mi_app_genero', true),
            'ftp' => get_user_meta($user_id, 'mi_app_ftp', true),
            'nivel_forma' => get_user_meta($user_id, 'mi_app_nivel_forma', true),
            'objetivo' => get_user_meta($user_id, 'mi_app_objetivo', true),
            'consumo_objetivo' => get_user_meta($user_id, 'mi_app_consumo_objetivo', true),
            'es_celiaco' => get_user_meta($user_id, 'mi_app_celiaco', true),
            'es_diabetico' => get_user_meta($user_id, 'mi_app_diabetico', true),
        ];

        $gpx_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $competition->gpx_file_url);
        $gpx_content = file_get_contents($gpx_path);

        return [
            'competition' => $competition,
            'user' => $user_data,
            'gpx_content' => $gpx_content,
        ];
    }

    /**
     * Obtiene un análisis guardado de la base de datos.
     */
    private function get_saved_analysis($competition_id, $user_id)
    {
        global $wpdb;
        $plan_table = $wpdb->prefix . 'mi_app_nutrition_plans';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT plan_data FROM {$plan_table} WHERE user_id = %d AND competition_id = %d AND plan_type = %s",
            $user_id,
            $competition_id,
            'gpx_analysis'
        ));
    }

    /**
     * Realiza la llamada a la API de Gemini.
     */
    private function perform_gemini_analysis($data)
    {
        //$api_key = get_option('mi_app_gemini_api_key');
        $api_key = $this->get_gemini_api_key_for_user();
        if (empty($api_key)) {
            throw new Exception('La clave de la API de Gemini no está configurada.');
        }

        $prompt = $this->build_gpx_analysis_prompt(
            $data['user'],
            $data['gpx_content'],
            $data['competition']->distancia,
            $data['competition']->desnivel
        );

        $response_text = $this->call_gemini_api($api_key, $prompt);

        if (is_wp_error($response_text)) {
            throw new Exception('Error al contactar la API de Gemini: ' . $response_text->get_error_message());
        }

        return $response_text;
    }

    /**
     * Guarda el análisis en la base de datos.
     */
    private function save_analysis($competition_id, $user_id, $competition_name, $html)
    {
        global $wpdb;
        $plan_table = $wpdb->prefix . 'mi_app_nutrition_plans';
        $wpdb->insert(
            $plan_table,
            [
                'user_id'       => $user_id,
                'competition_id'=> $competition_id,
                'plan_name'     => 'Análisis de Nutrición para ' . $competition_name,
                'plan_type'     => 'gpx_analysis',
                'plan_data'     => $html,
                'created_at'    => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Comprueba si existe un plan de nutrición.
     */
    private function check_if_nutrition_plan_exists($competition_id, $user_id)
    {
        global $wpdb;
        $plan_table = $wpdb->prefix . 'mi_app_nutrition_plans';
        return !empty($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$plan_table} WHERE competition_id = %d AND user_id = %d AND plan_type = %s",
            $competition_id,
            $user_id,
            'nutrition_plan'
        )));
    }


    /**
     * Función auxiliar para enviar un mensaje formateado para SSE.
     */
     private function send_sse_message($data)
     {
         echo "data: " . json_encode($data) . "\n\n";

         // Forzar el vaciado del búfer de salida a todos los niveles
         // Esto es más robusto que un simple flush()
         while (ob_get_level() > 0) {
         ob_end_flush();
         }
         flush();
     }


    /**
     * Extrae el plan de nutrición del HTML usando expresiones regulares.
     * @param string $html El HTML del análisis.
     * @return array Un array asociativo ['HH:MM' => 'Qué tomar'].
     */
    private function extract_nutrition_from_html($html)
    {
        $nutrition_plan = [];

        // 1. Buscar la tabla de nutrición específica usando una expresión regular
        // Busca desde el H2 de nutrición hasta el cierre de la tabla
        $pattern_table = '/<h2[^>]*>.*?Plan de[^<]*<\/h2>.*?<table[^>]*>(.*?)<\/table>/s';
        if (preg_match($pattern_table, $html, $table_match)) {
            $table_content = $table_match[1];

            // 2. Extraer todas las filas (<tr>...</tr>) de esa tabla
            $pattern_rows = '/<tr>(.*?)<\/tr>/s';
            if (preg_match_all($pattern_rows, $table_content, $row_matches)) {
                foreach ($row_matches[1] as $row_html) {
                    // 3. Extraer el tiempo y la comida de cada fila
                    // Ignoramos filas que contienen '---' o 'Tiempo Transcurrido'
                    if (strpos($row_html, '---') !== false || strpos($row_html, 'Tiempo Transcurrido') !== false) {
                        continue;
                    }

                    $pattern_cells = '/<td>(.*?)<\/td>/s';
                    if (preg_match_all($pattern_cells, $row_html, $cell_matches, PREG_SET_ORDER)) {
                        if (count($cell_matches) >= 4) {
                            $time = trim(strip_tags($cell_matches[0][1]));
                            $food = trim(strip_tags($cell_matches[3][1]));

                            // Limpiamos el tiempo para que sea solo "HH:MM"
                            $time = preg_replace('/\s*-\s*\d{2}:\d{2}/', '', $time);

                            if (!empty($time) && !empty($food)) {
                                $nutrition_plan[$time] = $food;
                            }
                        }
                    }
                }
            }
        }

        return $nutrition_plan;
    }

    /**
     * Modifica el GPX original añadiendo waypoints al final, con depuración.
     * @param string $original_gpx_path Ruta al archivo GPX original.
     * @param array $nutrition_plan El plan de nutrición.
     * @return string|false El contenido del nuevo GPX o false en caso de error.
     */
    private function create_enriched_gpx($original_gpx_path, $nutrition_plan)
    {
        $gpx_content = file_get_contents($original_gpx_path);
        if ($gpx_content === false) {
            error_log('No se pudo leer el archivo GPX original: ' . $original_gpx_path);
            return false;
        }

        // Extraer puntos del track para el cálculo de tiempos
        $track_points = [];
        $original_gpx_simple = simplexml_load_file($original_gpx_path);
        if (isset($original_gpx_simple->trk->trkseg)) {
            foreach ($original_gpx_simple->trk->trkseg->trkpt as $point) {
                if (isset($point->time)) {
                    $track_points[] = [
                        'time' => (string)$point->time,
                        'lat' => (float)$point['lat'],
                        'lon' => (float)$point['lon']
                    ];
                }
            }
        }
        
        if (empty($track_points)) {
            error_log('El GPX original no contiene puntos de track con información de tiempo.');
            return false;
        }

        $start_time = new DateTime($track_points[0]['time']);

        // Generar los waypoints como un solo bloque de texto
        $waypoints_xml = "\n"; // Añadimos un salto de línea para separar
        foreach ($nutrition_plan as $time_str => $food_desc) {
            try {
                $target_time = clone $start_time;
                list($hours, $minutes) = explode(':', $time_str);
                $target_time->add(new DateInterval("PT{$hours}H{$minutes}M"));

                $closest_point = $this->find_closest_gpx_point($track_points, $target_time->format(DateTime::ATOM));

                if ($closest_point) {
                    $lat = number_format($closest_point['lat'], 6);
                    $lon = number_format($closest_point['lon'], 6);
                    $name = "Nutrición: {$time_str}";
                    $desc = htmlspecialchars($food_desc, ENT_XML1, 'UTF-8');
                    
                    // Usamos HEREDOC para una estructura limpia
                    $waypoints_xml .= <<<XML
    <wpt lat="{$lat}" lon="{$lon}">
        <name>{$name}</name>
        <desc>{$desc}</desc>
        <sym>Restaurant</sym>
    </wpt>
XML;
                }
            } catch (Exception $e) {
                error_log("Error procesando tiempo de nutrición '{$time_str}': " . $e->getMessage());
            }
        }

        // *** CAMBIO CLAVE: Buscar el punto de inserción AL FINAL del track ***
        $insertion_point = strrpos($gpx_content, '</trk>');
        if ($insertion_point === false) {
            // Si no hay </trk>, buscamos </gpx>
            $insertion_point = strrpos($gpx_content, '</gpx>');
            if ($insertion_point === false) {
                error_log('No se pudo encontrar un punto de inserción en el archivo GPX.');
                return false;
            }
        }

        // Insertar los nuevos waypoints DESPUÉS de </trk>
        $new_gpx_content = substr_replace($gpx_content, $waypoints_xml, $insertion_point + strlen('</trk>'), 0);

        // *** PASO DE DEPURACIÓN: Guardar el archivo generado en el servidor ***
        // Guardaremos el archivo en el directorio de subidas para poder descargarlo
        $upload_dir = wp_upload_dir();
        $debug_file_path = $upload_dir['basedir'] . '/debug_nutrition_gpx.gpx';
        file_put_contents($debug_file_path, $new_gpx_content);
        error_log("GPX de nutrición guardado para depuración en: " . $debug_file_path);

        return $new_gpx_content;
    }



    /**
     * Encuentra el punto más cercano en un array de puntos de track a un tiempo objetivo.
     * @param array $track_points Array de puntos de track.
     * @param string $target_time_iso Tiempo objetivo en formato ISO 8601.
     * @return array|null El punto más cercano o null si no se encuentra.
     */
    private function find_closest_gpx_point($track_points, $target_time_iso)
    {
        $closest_point = null;
        $min_diff = PHP_FLOAT_MAX;

        foreach ($track_points as $point) {
            $diff = abs(strtotime($point['time']) - strtotime($target_time_iso));
            if ($diff < $min_diff) {
                $min_diff = $diff;
                $closest_point = $point;
            }
        }
        return $closest_point;
    }


    /**
     * Encuentra el punto más cercano en un array de puntos de track a un tiempo objetivo.
     * @param array $track_points Array de puntos de track.
     * @param string $target_time_iso Tiempo objetivo en formato ISO 8601.
     * @return array|null El punto más cercano o null si no se encuentra.
     */
    private function find_closest_gpx_point_dom($track_points, $target_time_iso)
    {
        $closest_point = null;
        $min_diff = PHP_FLOAT_MAX;

        foreach ($track_points as $point) {
            $diff = abs(strtotime($point['time']) - strtotime($target_time_iso));
            if ($diff < $min_diff) {
                $min_diff = $diff;
                $closest_point = $point;
            }
        }
        return $closest_point;
    }

    /**
     * Crea un nuevo contenido GPX añadiendo waypoints de carbohidratos cada 30 minutos.
     *
     * @param string $original_gpx_content El contenido XML del GPX original.
     * @param array $user_data Datos del perfil del usuario.
     * @param array $strava_insights Estadísticas de Strava.
     * @param int $total_time_seconds Duración total en segundos.
     * @return string El contenido del nuevo GPX.
     */
    private function create_gpx_with_carb_waypoints($original_gpx_content, $user_data, $strava_insights, $total_time_seconds)
    {
        // 1. Extraer todos los puntos del track original (<trkpt>)
        $xml = @simplexml_load_string($original_gpx_content);
        if (!$xml || !isset($xml->trk->trkseg->trkpt)) {
            error_log('[MI-APP-DEBUG] No se pudo parsear el XML o no hay puntos de track. Devolviendo GPX original.');
            return $original_gpx_content;
        }

        $track_points = [];
        foreach ($xml->trk->trkseg->trkpt as $point) {
            $track_points[] = [
                'lat' => (float)$point['lat'],
                'lon' => (float)$point['lon'],
                'time' => isset($point->time) ? (string)$point->time : null
            ];
        }

        if (empty($track_points)) {
            error_log('[MI-APP-DEBUG] El array de puntos del track está vacío. Devolviendo GPX original.');
            return $original_gpx_content;
        }

        // 2. Calcular duración total y waypoints necesarios
        $start_time = strtotime($track_points[0]['time']);
        $end_time = strtotime(end($track_points)['time']);
        $calculated_duration = $end_time - $start_time;
        
        // Usar la duración calculada del GPX si no se proporciona
        $duration_seconds = ($total_time_seconds > 0) ? $total_time_seconds : $calculated_duration;

        $interval_seconds = 30 * 60; // 30 minutos
        $num_waypoints = floor($duration_seconds / $interval_seconds);
        error_log('[MI-APP-DEBUG] Duración total: ' . $duration_seconds . 's. Waypoints a crear: ' . $num_waypoints);

        // 3. Calcular carbohidratos por intervalo
        $carbs_per_interval = $this->calculate_carbs_per_interval($user_data, $strava_insights, $duration_seconds);

        // 4. Crear un nuevo objeto SimpleXMLElement para el GPX de salida
        $gpx_ns = 'http://www.topografix.com/GPX/1/1';
        $xsi_ns = 'http://www.w3.org/2001/XMLSchema-instance';
        
        $new_gpx = new SimpleXMLElement('<gpx version="1.1" creator="Mi App Plugin" xmlns:xsi="' . $xsi_ns . '" xsi:schemaLocation="' . $gpx_ns . ' ' . $gpx_ns . '/gpx.xsd" xmlns="' . $gpx_ns . '"></gpx>');

        // 5. Copiar los metadatos del original si existen
        if (isset($xml->metadata)) {
            $new_metadata = $new_gpx->addChild('metadata');
            $this->sxml_append($new_metadata, $xml->metadata);
        }

        // 6. Añadir los waypoints de carbohidratos
        for ($i = 1; $i <= $num_waypoints; $i++) {
            $target_time = $start_time + ($i * $interval_seconds);
            
            // Encontrar el punto del track más cercano en tiempo
            $closest_point = $track_points[0];
            foreach ($track_points as $point) {
                if (abs(strtotime($point['time']) - $target_time) < abs(strtotime($closest_point['time']) - $target_time)) {
                    $closest_point = $point;
                }
            }

            // Calcular carbohidratos para este intervalo
            $carbs = round($carbs_per_interval);
            
            error_log('[MI-APP-DEBUG] Creando waypoint para el intervalo ' . $i . ' con ' . $carbs . 'g de carbohidratos.');

            // Añadir waypoint
            $wpt = $new_gpx->addChild('wpt');
            $wpt->addAttribute('lat', $closest_point['lat']);
            $wpt->addAttribute('lon', $closest_point['lon']);
            $wpt->addChild('name', $carbs . 'g carbo');
            $wpt->addChild('desc', 'Carbohidratos a consumir en el minuto ' . ($i * 30));
            $wpt->addChild('sym', 'Restaurant');
        }

        // 7. Copiar las rutas (tracks) del original DESPUÉS de los waypoints
        if (isset($xml->trk)) {
            $this->sxml_append($new_gpx, $xml->trk);
        }
        if (isset($xml->rte)) {
            $this->sxml_append($new_gpx, $xml->rte);
        }

        // 8. Formatear el XML a una cadena
        $dom = dom_import_simplexml($new_gpx)->ownerDocument;
        $dom->formatOutput = true;
        $result = $dom->saveXML();
        
        error_log('[MI-APP-DEBUG] GPX construido con éxito.');
        return $result;
    }

    /**
     * Calcula la cantidad de carbohidratos a consumir cada 30 minutos.
     *
     * @param array $user_data Datos del perfil del usuario.
     * @param array $strava_insights Estadísticas de Strava.
     * @param int $duration_seconds Duración total en segundos.
     * @return float Carbohidratos por intervalo de 30 minutos.
     */
    private function calculate_carbs_per_interval($user_data, $strava_insights, $duration_seconds)
    {
        // 1. Calcular carbohidratos totales necesarios para la actividad
        $peso = floatval($user_data['peso']);
        $ftp = floatval($user_data['ftp']);
        $experiencia = $user_data['experiencia'];
        
        // Base de carbohidratos por hora (ajustable según experiencia)
        $carbs_per_hour_base = 40; // Valor base para principiantes
        
        // Ajustar según experiencia
        switch ($experiencia) {
            case 'principiante':
                $carbs_per_hour_base = 40;
                break;
            case 'intermedio':
                $carbs_per_hour_base = 50;
                break;
            case 'avanzado':
                $carbs_per_hour_base = 60;
                break;
            case 'profesional':
                $carbs_per_hour_base = 70;
                break;
        }
        
        // Ajustar según peso (más peso = más carbohidratos)
        if ($peso > 0) {
            $peso_factor = $peso / 70; // 70kg como referencia
            $carbs_per_hour_base *= $peso_factor;
        }
        
        // Ajustar según FTP (más FTP = más carbohidratos)
        if ($ftp > 0) {
            $ftp_factor = $ftp / 200; // 200W como referencia
            $carbs_per_hour_base *= $ftp_factor;
        }
        
        // Ajustar según estadísticas de Strava
        if ($strava_insights && isset($strava_insights['training_load'])) {
            $training_load = $strava_insights['training_load'];
            
            // Más carga de entrenamiento = más carbohidratos
            if ($training_load > 7) {
                $carbs_per_hour_base *= 1.3; // 30% más
            } elseif ($training_load > 5) {
                $carbs_per_hour_base *= 1.15; // 15% más
            } elseif ($training_load < 3) {
                $carbs_per_hour_base *= 0.85; // 15% menos
            }
        }
        
        // 2. Calcular carbohidratos por intervalo (30 minutos)
        $carbs_per_interval = $carbs_per_hour_base / 2; // 30 minutos = 0.5 horas
        
        // 3. Ajustar según duración total (actividades más largas necesitan más carbohidratos por hora)
        $duration_hours = $duration_seconds / 3600;
        if ($duration_hours > 4) {
            $carbs_per_interval *= 1.2; // 20% más para actividades muy largas
        } elseif ($duration_hours > 2) {
            $carbs_per_interval *= 1.1; // 10% más para actividades largas
        }
        
        // 4. Asegurar un mínimo y máximo
        $carbs_per_interval = max(15, min(60, $carbs_per_interval));
        
        error_log('[MI-APP-DEBUG] Carbohidratos calculados por intervalo (30 min): ' . $carbs_per_interval);
        
        return $carbs_per_interval;
    }




    /**
     * Crea un nuevo contenido GPX añadiendo waypoints de nutrición cada 30 minutos.
     *
     * @param string $original_gpx_content El contenido XML del GPX original.
     * @param array $plan_data Los datos del plan nutricional.
     * @param int $total_time_seconds Duración total en segundos.
     * @return string El contenido del nuevo GPX con waypoints.
     */
    private function create_gpx_with_nutrition_waypoints($original_gpx_content, $plan_data, $total_time_seconds)
    {
        // 1. Extraer todos los puntos del track original (<trkpt>)
        $xml = @simplexml_load_string($original_gpx_content);
        if (!$xml || !isset($xml->trk->trkseg->trkpt)) {
            error_log('[MI-APP-DEBUG] No se pudo parsear el XML o no hay puntos de track. Devolviendo GPX original.');
            return $original_gpx_content;
        }

        $track_points = [];
        foreach ($xml->trk->trkseg->trkpt as $point) {
            $track_points[] = [
                'lat' => (float)$point['lat'],
                'lon' => (float)$point['lon'],
                'time' => isset($point->time) ? (string)$point->time : null
            ];
        }

        if (empty($track_points)) {
            error_log('[MI-APP-DEBUG] El array de puntos del track está vacío. Devolviendo GPX original.');
            return $original_gpx_content;
        }

        // 2. Extraer todas las comidas del plan en una lista simple
        $meals_list = [];
        if (isset($plan_data['days'])) {
            foreach ($plan_data['days'] as $day) {
                if (isset($day['meals'])) {
                    foreach ($day['meals'] as $meal) {
                        $meals_list[] = $meal;
                    }
                }
            }
        }

        if (empty($meals_list)) {
            error_log('[MI-APP-DEBUG] La lista de comidas está VACÍA. Devolviendo GPX original sin waypoints.');
            return $original_gpx_content;
        }

        // 3. Calcular waypoints cada 30 minutos
        $interval_seconds = 30 * 60; // 30 minutos
        $start_time = strtotime($track_points[0]['time']);
        $end_time = strtotime(end($track_points)['time']);
        $total_duration_seconds = $end_time - $start_time;
        
        // Si no tenemos tiempo total, usar el proporcionado
        if ($total_duration_seconds <= 0 && $total_time_seconds > 0) {
            $total_duration_seconds = $total_time_seconds;
        }
        
        $num_waypoints = floor($total_duration_seconds / $interval_seconds);
        error_log('[MI-APP-DEBUG] Duración total: ' . $total_duration_seconds . 's. Waypoints a crear: ' . $num_waypoints);

        // 4. Crear un nuevo objeto SimpleXMLElement para el GPX de salida
        $gpx_ns = 'http://www.topografix.com/GPX/1/1';
        $xsi_ns = 'http://www.w3.org/2001/XMLSchema-instance';
        
        $new_gpx = new SimpleXMLElement('<gpx version="1.1" creator="Mi App Plugin" xmlns:xsi="' . $xsi_ns . '" xsi:schemaLocation="' . $gpx_ns . ' ' . $gpx_ns . '/gpx.xsd" xmlns="' . $gpx_ns . '"></gpx>');

        // 5. Copiar los metadatos del original si existen
        if (isset($xml->metadata)) {
            $new_metadata = $new_gpx->addChild('metadata');
            $this->sxml_append($new_metadata, $xml->metadata);
        }

        // 6. Añadir los waypoints de nutrición
        for ($i = 1; $i <= $num_waypoints; $i++) {
            $target_time = $start_time + ($i * $interval_seconds);
            $meal_index = ($i - 1) % count($meals_list);
            $meal = $meals_list[$meal_index];

            // Encontrar el punto del track más cercano en tiempo (interpolación simple)
            $closest_point = $track_points[0];
            foreach ($track_points as $point) {
                if (abs(strtotime($point['time']) - $target_time) < abs(strtotime($closest_point['time']) - $target_time)) {
                    $closest_point = $point;
                }
            }

            $carbs = (int)($meal['carbs'] ?? 0);
            error_log('[MI-APP-DEBUG] Creando waypoint para la comida: ' . $meal['name'] . ' con ' . $carbs . 'g de carbohidratos.');

            // Añadir waypoint
            $wpt = $new_gpx->addChild('wpt');
            $wpt->addAttribute('lat', $closest_point['lat']);
            $wpt->addAttribute('lon', $closest_point['lon']);
            $wpt->addChild('name', htmlspecialchars($meal['name']));
            $wpt->addChild('desc', $carbs . 'g carbohidratos');
            $wpt->addChild('sym', 'Restaurant');
        }

        // 7. Copiar las rutas (tracks) del original DESPUÉS de los waypoints
        if (isset($xml->trk)) {
            $this->sxml_append($new_gpx, $xml->trk);
        }
        if (isset($xml->rte)) {
            $this->sxml_append($new_gpx, $xml->rte);
        }

        // 8. Formatear el XML a una cadena
        $dom = dom_import_simplexml($new_gpx)->ownerDocument;
        $dom->formatOutput = true;
        $result = $dom->saveXML();
        
        error_log('[MI-APP-DEBUG] GPX construido con éxito. Longitud: ' . strlen($result));
        return $result;
    }


    /**
     * Crea un nuevo contenido GPX añadiendo waypoints de nutrición cada 30 minutos.
     * VERSIÓN CON DEPURACIÓN PARA IDENTIFICAR EL PROBLEMA.
     *
     * @param string $original_gpx_content El contenido XML del GPX original.
     * @param array $plan_data
     * @return string
     */
    private function create_gpx_with_waypoints($original_gpx_content, $plan_data)
    {
        // --- INICIO DE DEPURACIÓN ---
        //error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: Iniciando función.');
        //error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: Datos del plan: ' . print_r($plan_data, true));
        // --- FIN DE DEPURACIÓN ---

        // 1. Extraer todos los puntos del track original (<trkpt>)
        $xml = @simplexml_load_string($original_gpx_content);
        if (!$xml || !isset($xml->trk->trkseg->trkpt)) {
            error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: No se pudo parsear el XML o no hay puntos de track. Devolviendo GPX original.');
            return $original_gpx_content;
        }

        $track_points = [];
        foreach ($xml->trk->trkseg->trkpt as $point) {
            $track_points[] = [
                'lat' => (float)$point['lat'],
                'lon' => (float)$point['lon'],
                'time' => (string)$point['time']
            ];
        }

        if (empty($track_points)) {
            error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: El array de puntos del track está vacío. Devolviendo GPX original.');
            return $original_gpx_content;
        }

        // 2. Calcular duración total y waypoints necesarios
        $start_time = strtotime($track_points[0]['time']);
        $end_time = strtotime(end($track_points)['time']);
        $total_duration_seconds = $end_time - $start_time;

        $interval_seconds = 30 * 60; // 30 minutos
        $num_waypoints = floor($total_duration_seconds / $interval_seconds);
        error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: Duración total: ' . $total_duration_seconds . 's. Waypoints a crear: ' . $num_waypoints);

        // 3. Extraer todas las comidas del plan en una lista simple
        $meals_list = [];
        if (isset($plan_data['days'])) {
            foreach ($plan_data['days'] as $day) {
                if (isset($day['meals'])) {
                    foreach ($day['meals'] as $meal) {
                        $meals_list[] = $meal;
                    }
                }
            }
        }

        error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: Lista de comidas extraída: ' . print_r($meals_list, true));

        if (empty($meals_list)) {
            error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: La lista de comidas está VACÍA. Devolviendo GPX original sin waypoints.');
            return $original_gpx_content;
        }

        // 4. Construir el nuevo GPX como una cadena de texto
        $gpx_string = '<?xml version="1.0" encoding="UTF-8"?>';
        $gpx_string .= '<gpx version="1.1" creator="Mi App Plugin" xmlns="http://www.topografix.com/GPX/1/1"';
        $gpx_string .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $gpx_string .= ' xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">';
        
        // Añadir Waypoints de Nutrición
        for ($i = 1; $i <= $num_waypoints; $i++) {
            $target_time = $start_time + ($i * $interval_seconds);
            $meal_index = ($i - 1) % count($meals_list);
            $meal = $meals_list[$meal_index];

            // Encontrar el punto del track más cercano en tiempo (interpolación simple)
            $closest_point = $track_points[0];
            foreach ($track_points as $point) {
                if (abs(strtotime($point['time']) - $target_time) < abs(strtotime($closest_point['time']) - $target_time)) {
                    $closest_point = $point;
                }
            }

            $carbs = (int)($meal['carbs'] ?? 0);
            error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: Creando waypoint para la comida: ' . $meal['name'] . ' con ' . $carbs . 'g de carbohidratos.');

            $gpx_string .= sprintf(
                '<wpt lat="%s" lon="%s"><name>%s</name><desc>%dgr carbo</desc><sym>Restaurant</sym></wpt>',
                $closest_point['lat'],
                $closest_point['lon'],
                htmlspecialchars($meal['name']),
                $carbs
            );
        }

        // Copiar el track original completo
        $trk_start_pos = strpos($original_gpx_content, '<trk>');
        if ($trk_start_pos !== false) {
            $trk_section = substr($original_gpx_content, $trk_start_pos);
            $gpx_string .= $trk_section;
        }

        $gpx_string .= '</gpx>';

        error_log('[MI-APP-DEBUG] create_gpx_with_waypoints: GPX construido con éxito.');
        return $gpx_string;
    }

    /**
     * Genera los waypoints de nutrición basados en el plan y la duración total.
     * @param array $plan_data
     * @param int $total_time_seconds Duración total en segundos.
     * @return array
     */
    private function generate_nutrition_waypoints($plan_data, $total_time_seconds)
    {
        $waypoints = [];
        
        // Extraer todas las comidas del plan en una lista simple
        $meals_list = [];
        if (isset($plan_data['days'])) {
            foreach ($plan_data['days'] as $day) {
                if (isset($day['meals'])) {
                    foreach ($day['meals'] as $meal) {
                        $meals_list[] = $meal;
                    }
                }
            }
        }

        if (empty($meals_list) || $total_time_seconds == 0) {
            return $waypoints;
        }

        // Distribuimos los waypoints cada 30 minutos
        $interval_seconds = 30 * 60; // 30 minutos
        $num_waypoints = floor($total_time_seconds / $interval_seconds);
        
        // Extraer puntos del track para interpolar posiciones
        $xml = @simplexml_load_string($original_gpx_content);
        $track_points = [];
        
        if ($xml && isset($xml->trk->trkseg->trkpt)) {
            foreach ($xml->trk->trkseg->trkpt as $point) {
                $track_points[] = [
                    'lat' => (float)$point['lat'],
                    'lon' => (float)$point['lon'],
                    'time' => isset($point->time) ? (string)$point->time : null
                ];
            }
        }

        if (empty($track_points)) {
            return $waypoints;
        }

        $start_time = strtotime($track_points[0]['time']);
        
        for ($i = 1; $i <= $num_waypoints; $i++) {
            $target_time = $start_time + ($i * $interval_seconds);
            $meal_index = ($i - 1) % count($meals_list);
            $meal = $meals_list[$meal_index];

            // Encontrar el punto del track más cercano en tiempo
            $closest_point = $track_points[0];
            foreach ($track_points as $point) {
                if (abs(strtotime($point['time']) - $target_time) < abs(strtotime($closest_point['time']) - $target_time)) {
                    $closest_point = $point;
                }
            }

            $carbs = (int)($meal['carbs'] ?? 0);
            
            $waypoints[] = [
                'lat' => $closest_point['lat'],
                'lon' => $closest_point['lon'],
                'name' => $meal['name'],
                'desc' => $carbs . 'g carbohidratos',
                'sym' => 'Restaurant'
            ];
        }

        return $waypoints;
    }

    /**
     * Construye el contenido XML del nuevo GPX con los waypoints de nutrición.
     *
     * @param string $original_gpx_content El contenido XML del GPX original.
     * @param array $waypoints Array de waypoints de nutrición.
     * @return string El contenido XML del nuevo GPX.
     */
    private function build_nutrition_gpx($original_gpx_content, $waypoints)
    {
        // 1. Cargar el GPX original para extraer sus datos
        $original_xml = @simplexml_load_string($original_gpx_content);
        if (!$original_xml) {
            return $original_gpx_content; // Devolver original si no se puede parsear
        }

        // 2. Crear un nuevo objeto XML para el GPX de salida, con namespaces correctos
        $gpx_ns = 'http://www.topografix.com/GPX/1/1';
        $xsi_ns = 'http://www.w3.org/2001/XMLSchema-instance';
        
        $new_gpx = new SimpleXMLElement('<gpx version="1.1" creator="Mi App Plugin" xmlns:xsi="' . $xsi_ns . '" xsi:schemaLocation="' . $gpx_ns . ' ' . $gpx_ns . '/gpx.xsd" xmlns="' . $gpx_ns . '"></gpx>');

        // 3. Copiar los metadatos del original si existen
        if (isset($original_xml->metadata)) {
            $new_metadata = $new_gpx->addChild('metadata');
            $this->sxml_append($new_metadata, $original_xml->metadata);
        }

        // 4. Añadir los waypoints de nutrición ANTES de las rutas/tracks
        foreach ($waypoints as $wp) {
            $wpt = $new_gpx->addChild('wpt');
            $wpt->addAttribute('lat', $wp['lat']);
            $wpt->addAttribute('lon', $wp['lon']);
            $wpt->addChild('name', htmlspecialchars($wp['name']));
            $wpt->addChild('desc', htmlspecialchars($wp['desc']));
            $wpt->addChild('sym', $wp['sym']);
        }

        // 5. Copiar las rutas (tracks) del original DESPUÉS de los waypoints
        if (isset($original_xml->trk)) {
            $this->sxml_append($new_gpx, $original_xml->trk);
        }
        if (isset($original_xml->rte)) {
            $this->sxml_append($new_gpx, $original_xml->rte);
        }

        // 6. Formatear el XML a una cadena
        $dom = dom_import_simplexml($new_gpx)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    /**
     * Función auxiliar para añadir un SimpleXMLElement a otro de forma recursiva.
     * Esto es necesario para copiar namespaces correctamente.
     * @param SimpleXMLElement $to
     * @param SimpleXMLElement $from
     */
    private function sxml_append(SimpleXMLElement $to, SimpleXMLElement $from)
    {
        $toDom = dom_import_simplexml($to);
        $fromDom = dom_import_simplexml($from);
        $node = $toDom->ownerDocument->importNode($fromDom, true);
        $toDom->appendChild($node);
    }


    /**
     * Función de depuración para registrar información en el log
     */
    private function debug_log($message) {
        if (WP_DEBUG === true) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log($message);
            }
        }
    }


    /**
     * Maneja la llamada AJAX para obtener insights de Strava (Carga y Fatiga).
     */
    public function handle_get_strava_insights()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'get_strava_insights_nonce')) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
            wp_die();
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Usuario no logueado.');
            wp_die();
        }


        // 1. Comprobar si hay conexión con Strava
        $access_token = get_user_meta($user_id, 'strava_access_token', true);
        if (empty($access_token)) {
            wp_send_json_error('No hay conexión con Strava.');
            wp_die();
        }

        // 2. Obtener las últimas actividades (ej. las últimas 10)
        $activities = $this->get_strava_activities($user_id, 30);

        if (is_wp_error($activities)) {
            error_log('[MI-APP-DEBUG] Error al obtener actividades: ' . $activities->get_error_message());
            wp_send_json_error('No se pudieron obtener las actividades de Strava.');
            wp_die();
        }

        $insights = $this->analyze_strava_data($activities);

        if ($insights === null) {
            wp_send_json_error('No se encontraron actividades recientes para analizar.');
            wp_die();
        }

        wp_send_json_success($insights);
        wp_die();

        // 3. Simular el análisis de Carga y Fatiga
        $training_load = 0;
        $fatigue = 0;
        $total_time_seconds = 0;
        $total_distance_meters = 0;
        $recent_rides_count = 0;

        foreach ($activities as $activity) {
            if ($activity->type === 'Ride') {
                $recent_rides_count++;
                $time_hours = $activity->moving_time / 3600;
                $distance_km = $activity->distance / 1000;

                // Carga de Entrenamiento: simple cálculo basado en tiempo y distancia
                $training_load += ($time_hours * 1.5) + ($distance_km * 0.1);

                // Fatiga: simulación basada en la intensidad (si hay datos de potencia) o solo en el tiempo
                $intensity_factor = 1; // Factor base
                if (isset($activity->average_watts) && $activity->average_watts > 0) {
                    // Usamos los vatios medios como proxy de intensidad (ej: 200W es un esfuerzo moderado)
                    $intensity_factor = $activity->average_watts / 200;
                }
                $fatigue += $time_hours * $intensity_factor;

                $total_time_seconds += $activity->moving_time;
                $total_distance_meters += $activity->distance;
            }
        }

        // Normalizar los valores a una escala de 1-100 para que sean más fáciles de interpretar
        $normalized_load = min(100, max(1, round($training_load / 5))); // Ajusta el divisor según sea necesario
        $normalized_fatigue = min(100, max(1, round($fatigue / 3))); // Ajusta el divisor según sea necesario

        $insights = [
            'training_load' => $normalized_load,
            'fatigue' => $normalized_fatigue,
            'summary' => sprintf(
                "En tus últimas %d salidas en bici, has acumulado un total de %d km y %d horas con una carga de entrenamiento de %d/100 y una fatiga de %d/100",
                $recent_rides_count,
                round($total_distance_meters / 1000),
                round($total_time_seconds / 3600),
                $normalized_load,
                $normalized_fatigue
            )
        ];

        wp_send_json_success($insights);
        wp_die();
    }


}

// Iniciar el plugin
new MiAppPlugin();