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

        // Modificar la plantilla de competición
        add_filter('template_include', [$this, 'modify_competition_template']);

        add_action('wp_ajax_analyze_gpx_sse', [$this, 'handle_analyze_gpx_sse']);
        add_action('wp_ajax_nopriv_analyze_gpx_sse', [$this, 'handle_analyze_gpx_sse']);

        //FAQ
        add_action('init', [$this, 'register_my_app_plugin_post_types']);
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
        add_action( 'admin_enqueue_scripts', [ $this, 'mi_app_enqueue_weather_icons' ] );

        //Desafios y Logros
        add_action('admin_menu', [$this, 'add_challenges_menu']);
        add_action('init', [$this, 'add_custom_capabilities']);
        add_action('wp_ajax_add_challenge', [$this, 'handle_ajax_add_challenge']);
        add_action('wp_ajax_nopriv_add_challenge', [$this, 'handle_ajax_add_challenge']);
        add_action('wp_ajax_edit_challenge', [$this, 'handle_ajax_edit_challenge']);
        add_action('wp_ajax_nopriv_edit_challenge', [$this, 'handle_ajax_edit_challenge']);
        add_action('wp_ajax_delete_challenge', [$this, 'handle_ajax_delete_challenge']);
        add_action('wp_ajax_nopriv_delete_challenge', [$this, 'handle_ajax_delete_challenge']);

    }

    public function add_custom_capabilities()
    {
        $role = get_role('editor');
        if ($role) {
            $role->add_cap('manage_mi_app_plugin');
        }
    }

    public function add_challenges_menu()
    {
        add_menu_page(
            'Gestión de Desafíos de Mi App',
            'Challenges Mi App',
            'edit_posts',
            'mi-app-challenges-management',
            [$this, 'render_challenges_management_page'],
            'dashicons-awards',
            8
        );
    }

    public function render_challenges_management_page($atts = [], $page = '')
    {
        $this->render_challenges_tab();
    }

    public function handle_ajax_add_faq()
    {
        check_ajax_referer('faq_nonce', 'nonce');
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);

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

    public function handle_ajax_add_challenge()
    {
        global $wpdb;
        check_ajax_referer('manage_challenges_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        $title = isset($_POST['challenge_title']) ? sanitize_text_field($_POST['challenge_title']) : '';
        $description = isset($_POST['challenge_description']) ? sanitize_textarea_field($_POST['challenge_description']) : '';
        $type = isset($_POST['challenge_type']) ? sanitize_text_field($_POST['challenge_type']) : 'custom';
        $target_value = isset($_POST['challenge_target']) ? absint($_POST['challenge_target']) : 0;
        $meta_value = isset($_POST['challenge_meta']) ? sanitize_text_field($_POST['challenge_meta']) : '';

        if (empty($title)) {
            wp_send_json_error('El título del desafío es obligatorio.');
        }

        $table_name = $wpdb->prefix . 'mi_app_challenges';
        $result = $wpdb->insert(
            $table_name,
            [
                'title' => $title,
                'description' => $description,
                'challenge_type' => $type,
                'target_value' => $target_value,
                'meta_value' => $meta_value,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($result) {
            wp_send_json_success(['message' => 'Desafío añadido correctamente.']);
        } else {
            wp_send_json_error('No se pudo añadir el desafío. Error de base de datos: ' . $wpdb->last_error);
        }

        wp_die();
    }

    public function handle_ajax_edit_challenge()
    {
        global $wpdb;
        check_ajax_referer('manage_challenges_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        $id = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : 0;
        if (!$id) {
            wp_send_json_error('ID de desafío no válido.');
        }

        $title = isset($_POST['challenge_title']) ? sanitize_text_field($_POST['challenge_title']) : '';
        $description = isset($_POST['challenge_description']) ? sanitize_textarea_field($_POST['challenge_description']) : '';
        $type = isset($_POST['challenge_type']) ? sanitize_text_field($_POST['challenge_type']) : 'custom';
        $target_value = isset($_POST['challenge_target']) ? absint($_POST['challenge_target']) : 0;
        $meta_value = isset($_POST['challenge_meta']) ? sanitize_text_field($_POST['challenge_meta']) : '';

        if (empty($title)) {
            wp_send_json_error('El título del desafío es obligatorio.');
        }

        $table_name = $wpdb->prefix . 'mi_app_challenges';
        $result = $wpdb->update(
            $table_name,
            [
                'title' => $title,
                'description' => $description,
                'challenge_type' => $type,
                'target_value' => $target_value,
                'meta_value' => $meta_value,
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%d', '%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Desafío actualizado correctamente.']);
        } else {
            wp_send_json_error('No se pudo actualizar el desafío. Error de base de datos: ' . $wpdb->last_error);
        }

        wp_die();
    }

    public function handle_ajax_delete_challenge()
    {
        global $wpdb;
        check_ajax_referer('manage_challenges_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        $id = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : 0;
        if (!$id) {
            wp_send_json_error('ID de desafío no válido.');
        }

        $table_name = $wpdb->prefix . 'mi_app_challenges';
        $result = $wpdb->delete(
            $table_name,
            ['id' => $id],
            ['%d']
        );

        if ($result) {
            wp_send_json_success(['message' => 'Desafío eliminado correctamente.']);
        } else {
            wp_send_json_error('No se pudo eliminar el desafío. Error de base de datos: ' . $wpdb->last_error);
        }

        wp_die();
    }

    public function add_faq_menu()
    {
        add_menu_page(
            'Gestión de FAQs de Mi App',
            'Carbo Cycling FAQ',
            'edit_posts',
            'mi-app-faq-management',
            [$this, 'render_faq_management_page'],
            'dashicons-editor-help',
            7
        );
    }

    public function render_faq_management_page()
    {
        $this->render_faq_management_tab();
    }

    public function activate()
    {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
        $this->create_competition_table();
        $this->create_recipe_table();
        $this->create_nutrition_plan_table();
        $this->create_challenges_tables();
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            'mi-app-plugin',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Mi App Plugin',
            'Carbo Cycling',
            'manage_options',
            'carbo-cycling-settings',
            [$this, 'render_admin_settings_page'],
            'dashicons-admin-generic',
            6
        );
    }

    public function register_settings()
    {
        register_setting('mi_app_visual_settings', 'mi_app_primary_color');
        register_setting('mi_app_visual_settings', 'mi_app_headings_font');
        register_setting('mi_app_visual_settings', 'mi_app_body_font');
        register_setting('mi_app_faq_settings', 'mi_app_faq_content');
        register_setting('mi_app_api_settings', 'mi_app_gemini_api_key');
        register_setting('mi_app_api_settings', 'mi_app_strava_client_id');
        register_setting('mi_app_api_settings', 'mi_app_strava_client_secret');
        register_setting('mi_app_api_settings', 'mi_app_openweathermap_api_key');
        register_setting('mi_app_api_settings', 'mi_app_weather_days');
        register_setting('mi_app_api_settings', 'mi_app_opencage_api_key');
    }

    public function enqueue_shared_plugin_styles()
    {
        if (is_page('mi-app-dashboard') || is_page('mi-app-competition') || is_page('mi-app-recetas') || is_page('mi-app-perfil') || is_page('faq')) {
            wp_enqueue_style(
                'mi-app-shared-styles',
                plugin_dir_url(__FILE__) . 'mi-app-styles.css',
                [],
                '1.0.0'
            );
        }
    }

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

    public function render_faq_management_tab()
    {
        ?>
        <h2>Gestión de Preguntas Frecuentes (FAQ)</h2>
        <p>Aquí puedes añadir y gestionar las preguntas individuales que aparecerán en la página de FAQ.</p>
        <a href="#" id="add-faq-btn" class="button button-primary">+ Añadir Nueva Pregunta</a>

        <div id="faq-list-container">
            <?php
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

            function refreshFaqList() {
                location.reload();
            }

            $('#add-faq-btn').on('click', function(e) {
                e.preventDefault();
                openModal('#add-faq-modal');
            });

            $('#faq-list-container').on('click', 'button', function(e) {
                e.preventDefault();
                var button = $(this);
                var faqItem = button.closest('.faq-item');

                if (button.hasClass('edit-faq-btn')) {
                    var faqId = faqItem.data('faq-id');
                    var faqTitle = faqItem.find('.faq-title').text();
                    var faqContent = faqItem.data('faq-content');

                    $('#edit-faq-id').val(faqId);
                    $('#edit-faq-title').val(faqTitle);

                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('edit-faq-content')) {
                        tinyMCE.get('edit-faq-content').setContent(faqContent);
                    } else {
                        $('#edit-faq-content').val(faqContent);
                    }
                    openModal('#edit-faq-modal');

                } else if (button.hasClass('delete-faq-btn')) {
                    var faqId = faqItem.data('faq-id');
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
        <div class="challenge-item"
             data-challenge-id="<?php echo $challenge->id; ?>"
             data-challenge-title="<?php echo esc_attr($challenge->title); ?>"
             data-challenge-description="<?php echo esc_attr($challenge->description); ?>"
             data-challenge-type="<?php echo esc_attr($challenge->challenge_type); ?>"
             data-challenge-target="<?php echo esc_attr($challenge->target_value); ?>"
             data-challenge-meta="<?php echo esc_attr($challenge->meta_value); ?>">
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

        <div id="challenge-modal" class="challenge-modal" style="display: none;">
            <div class="challenge-modal-content">
                <span class="challenge-close-modal">&times;</span>
                <h2 id="challenge-modal-title">Añadir Nuevo Desafío</h2>
                <form id="challenge-form">
                    <input type="hidden" id="challenge-id" name="challenge_id">
                    <div class="form-group">
                        <label for="challenge-title">Título del Desafío</label>
                        <input type="text" id="challenge-title" name="challenge_title" required>
                    </div>
                    <div class="form-group">
                        <label for="challenge-description">Descripción</label>
                        <textarea id="challenge-description" name="challenge_description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="challenge-type">Tipo de Desafío</label>
                        <select id="challenge-type" name="challenge_type">
                            <option value="distance">Distancia</option>
                            <option value="elevation">Desnivel</option>
                            <option value="rides">Número de Rutas</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="challenge-target">Valor del Objetivo</label>
                        <input type="number" id="challenge-target" name="challenge_target" required>
                        <p class="description">Ej: 100 (para km), 5000 (para metros), 30 (para rutas).</p>
                    </div>
                    <div class="form-group">
                        <label for="challenge-meta">Valor Meta (Opcional)</label>
                        <input type="text" id="challenge-meta" name="challenge_meta" placeholder="Ej: competition_ids: 1,5,12">
                        <p class="description">Un valor extra para lógica personalizada (ej. IDs de competiciones a completar).</p>
                    </div>
                    <button type="submit" class="button button-primary">Guardar Desafío</button>
                </form>
            </div>
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
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; margin-bottom: 5px; }
            .form-group input[type="text"], .form-group input[type="number"], .form-group textarea, .form-group select { width: 100%; }
        </style>

        <script>
            jQuery(document).ready(function($) {
                const modal = $('#challenge-modal');
                const modalTitle = $('#challenge-modal-title');
                const form = $('#challenge-form');
                const challengeIdField = $('#challenge-id');

                function openModal() {
                    modal.show();
                }

                function closeModal() {
                    modal.hide();
                    form[0].reset();
                    challengeIdField.val('');
                    modalTitle.text('Añadir Nuevo Desafío');
                }

                $('.challenge-close-modal').on('click', closeModal);

                $('#add-challenge-btn').on('click', function(e) {
                    e.preventDefault();
                    modalTitle.text('Añadir Nuevo Desafío');
                    openModal();
                });

                $('#challenges-list-container').on('click', '.edit-challenge-btn', function(e) {
                    e.preventDefault();
                    const item = $(this).closest('.challenge-item');
                    modalTitle.text('Editar Desafío');

                    challengeIdField.val(item.data('challenge-id'));
                    $('#challenge-title').val(item.data('challenge-title'));
                    $('#challenge-description').val(item.data('challenge-description'));
                    $('#challenge-type').val(item.data('challenge-type'));
                    $('#challenge-target').val(item.data('challenge-target'));
                    $('#challenge-meta').val(item.data('challenge-meta'));

                    openModal();
                });

                $('#challenges-list-container').on('click', '.delete-challenge-btn', function(e) {
                    e.preventDefault();
                    if (!confirm('¿Estás seguro de que quieres eliminar este desafío?')) {
                        return;
                    }

                    const item = $(this).closest('.challenge-item');
                    const challengeId = item.data('challenge-id');

                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'delete_challenge',
                        challenge_id: challengeId,
                        nonce: '<?php echo wp_create_nonce('manage_challenges_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Ocurrió un error inesperado.'));
                        }
                    });
                });

                form.on('submit', function(e) {
                    e.preventDefault();
                    const isEditing = !!challengeIdField.val();
                    const action = isEditing ? 'edit_challenge' : 'add_challenge';

                    const formData = {
                        action: action,
                        nonce: '<?php echo wp_create_nonce('manage_challenges_nonce'); ?>',
                        challenge_id: challengeIdField.val(),
                        challenge_title: $('#challenge-title').val(),
                        challenge_description: $('#challenge-description').val(),
                        challenge_type: $('#challenge-type').val(),
                        challenge_target: $('#challenge-target').val(),
                        challenge_meta: $('#challenge-meta').val()
                    };

                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
                        if (response.success) {
                            closeModal();
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Ocurrió un error inesperado.'));
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function handle_delete_faq_ajax()
    {
        if (!wp_verify_nonce($_POST['delete_faq_nonce'], 'delete_faq_' . $_POST['faq_id'])) {
            wp_send_json_error('Error de seguridad. Nonce inválido.');
        }

        if (!current_user_can('delete_posts')) {
            wp_send_json_error('No tienes permisos para eliminar contenido.');
        }

        $post_id = isset($_POST['faq_id']) ? intval($_POST['faq_id']) : 0;
        if ($post_id === 0) {
            wp_send_json_error('ID de FAQ no válido.');
        }

        $result = wp_delete_post($post_id, true);

        if ($result === false) {
            wp_send_json_error('No se pudo eliminar la FAQ.');
        }

        wp_send_json_success('FAQ eliminada correctamente.');
    }

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

    public function register_my_app_plugin_post_types()
    {
        register_post_type('mi_app_faq', [
            'label' => __('FAQs de Mi App', 'mi-app-plugin'),
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'excerpt'],
            'show_in_menu' => false,
            'rewrite' => ['slug' => 'mi-app-faq'],
            'show_ui' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

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
            'has_archive' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
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

    public function add_faq_meta_boxes()
    {
        add_meta_box('faq_status_meta_box', 'Estado de la Pregunta', 'mi_app_faq', 'side', 'default');
    }

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

    private function create_challenges_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_challenges = $wpdb->prefix . 'mi_app_challenges';
        $sql_challenges = "CREATE TABLE $table_challenges (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            challenge_type ENUM('distance', 'elevation', 'rides', 'custom') NOT NULL DEFAULT 'custom',
            target_value int NOT NULL,
            meta_value varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

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

    public function add_analysis_button_to_competition_page() {}

    public function handle_analyze_gpx()
    {
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

        $existing_analysis = $wpdb->get_row($wpdb->prepare(
            "SELECT plan_data FROM {$plan_table} WHERE user_id = %d AND competition_id = %d AND plan_type = %s",
            $user_id,
            $competition_id,
            'gpx_analysis'
        ));

        if ($existing_analysis) {
            wp_send_json_success(['html' => $existing_analysis->plan_data, 'has_plan' => true]);
        }

        $comp_table = $wpdb->prefix . 'mi_app_competiciones';
        $competition = $wpdb->get_row($wpdb->prepare("SELECT gpx_file_url, distancia, desnivel FROM {$comp_table} WHERE id = %d AND user_id = %d", $competition_id, $user_id));

        if (!$competition || !$competition->gpx_file_url) {
            wp_send_json_error('No se encontró el GPX para esta competición.');
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
        if (!file_exists($gpx_path)) {
            wp_send_json_error('El archivo GPX no existe en el servidor.');
        }
        $gpx_content = file_get_contents($gpx_path);

        $api_key = $this->get_gemini_api_key_for_user();
        if (empty($api_key)) {
            wp_send_json_error('La clave de la API de Gemini no está configurada.');
        }

        $prompt = $this->build_gpx_analysis_prompt($user_data, $gpx_content, $competition->distancia, $competition->desnivel);
        $response_text = $this->call_gemini_api($api_key, $prompt);

        if (is_wp_error($response_text)) {
            wp_send_json_error('Error al contactar la API de Gemini: ' . $response_text->get_error_message());
        }

        $html = $this->format_analysis_response($response_text);

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

        $has_nutrition_plan = !empty($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$plan_table} WHERE competition_id = %d AND user_id = %d AND plan_type = %s",
            $competition_id,
            $user_id,
            'nutrition_plan'
        )));

        wp_send_json_success(['html' => $html, 'has_plan' => $has_nutrition_plan]);

        wp_die();
    }

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
    }

    private function format_analysis_response($markdown_text)
    {
        $markdown_text = preg_replace('/^## (.+)$/m', '<h2 class="analysis-h2">$1</h2>', $markdown_text);
        $markdown_text = preg_replace('/^### (.+)$/m', '<h3 class="analysis-h3">$1</h3>', $markdown_text);
        $markdown_text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $markdown_text);
        $markdown_text = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', $markdown_text);
        $markdown_text = preg_replace('/(<li>.*<\/li>)/s', '<ul class="analysis-tips-list">$1</ul>', $markdown_text);
        $lines = explode("\n", $markdown_text);
        $in_table = false;
        $html_output = '';
        $table_rows = [];

        foreach ($lines as $line) {
            if (preg_match('/^\|(.+)\|$/', $line)) {
                $cells = array_map('trim', explode('|', $line));
                $cells = array_filter($cells);
                $table_rows[] = '<tr><td>' . implode('</td><td>', $cells) . '</td></tr>';
                $in_table = true;
            } else {
                if ($in_table) {
                    $html_output .= '<table class="analysis-table">' . implode('', $table_rows) . '</table>';
                    $table_rows = [];
                    $in_table = false;
                }
                $html_output .= $line . "\n";
            }
        }
        if ($in_table) {
            $html_output .= '<table class="analysis-table">' . implode('', $table_rows) . '</table>';
        }

        $html_output = nl2br($html_output);
        $final_html = '<div class="analysis-container">' . $html_output . '</div>';
        return $final_html;
    }

    // ... (rest of the file remains the same)
}

// Iniciar el plugin
new MiAppPlugin();
