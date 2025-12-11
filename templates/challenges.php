<?php
/**
 * Template Name: Mi App - Challenges
 */

get_header();

// Instanciar el plugin para acceder a sus métodos
$mi_app_plugin = new MiAppPlugin();
$user_id = get_current_user_id();
$strava_activities = [];
$strava_error = '';

if ($user_id) {
    $activities = $mi_app_plugin->get_strava_activities($user_id, 100); // Get last 100 activities
    if (is_wp_error($activities)) {
        $strava_error = 'No se pudieron cargar los datos de Strava. Asegúrate de que tu cuenta esté conectada.';
    } else {
        $strava_activities = $activities;
    }
}

// Procesar datos de Strava
$total_distance = 0;
$total_elevation = 0;
$total_rides = 0;

foreach ($strava_activities as $activity) {
    if ($activity->type === 'Ride') {
        $total_rides++;
        $total_distance += $activity->distance;
        $total_elevation += $activity->total_elevation_gain;
    }
}
$total_distance_km = $total_distance / 1000;

?>

<div class="mi-app-container">
    <div class="mi-app-content">
        <h1>Desafíos y Logros</h1>
        <p>Completa los siguientes desafíos para ganar puntos y demostrar tu progreso.</p>

        <?php if ($strava_error): ?>
            <div class="strava-error"><?php echo esc_html($strava_error); ?></div>
        <?php endif; ?>

        <div class="challenges-list">
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'mi_app_challenges';
            $challenges = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC");

            if ($challenges):
                foreach ($challenges as $challenge):
                    $is_complete = false;
                    $current_progress = 0;

                    switch ($challenge->challenge_type) {
                        case 'distance':
                            $current_progress = $total_distance_km;
                            if ($current_progress >= $challenge->target_value) {
                                $is_complete = true;
                            }
                            break;
                        case 'elevation':
                            $current_progress = $total_elevation;
                            if ($current_progress >= $challenge->target_value) {
                                $is_complete = true;
                            }
                            break;
                        case 'rides':
                            $current_progress = $total_rides;
                            if ($current_progress >= $challenge->target_value) {
                                $is_complete = true;
                            }
                            break;
                    }
                    $progress_percentage = ($challenge->target_value > 0) ? ($current_progress / $challenge->target_value) * 100 : 0;
                    $progress_percentage = min(100, $progress_percentage);
            ?>
                    <div class="challenge-card <?php echo $is_complete ? 'completed' : ''; ?>">
                        <div class="challenge-icon">
                            <span class="dashicons dashicons-awards"></span>
                        </div>
                        <div class="challenge-details">
                            <h3 class="challenge-title"><?php echo esc_html($challenge->title); ?></h3>
                            <p class="challenge-description"><?php echo esc_html($challenge->description); ?></p>
                            <div class="challenge-progress">
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%;"></div>
                                </div>
                                <span class="progress-label">
                                    <?php echo esc_html(round($current_progress, 1)); ?> / <?php echo esc_html($challenge->target_value); ?>
                                    (<?php echo esc_html(ucfirst($challenge->challenge_type)); ?>)
                                </span>
                            </div>
                        </div>
                        <div class="challenge-status">
                            <span class="status-badge <?php echo $is_complete ? 'complete' : 'incomplete'; ?>">
                                <?php echo $is_complete ? 'Completo' : 'Incompleto'; ?>
                            </span>
                        </div>
                    </div>
            <?php
                endforeach;
            else:
                echo '<p>No hay desafíos disponibles en este momento. ¡Vuelve pronto!</p>';
            endif;
            ?>
        </div>
    </div>
</div>

<style>
    .mi-app-container {
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    .strava-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .challenges-list {
        margin-top: 30px;
    }
    .challenge-card {
        display: flex;
        align-items: center;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.2s ease-in-out;
    }
    .challenge-card.completed {
        background-color: #f0fff0;
        border-left: 5px solid #5cb85c;
    }
    .challenge-icon {
        font-size: 48px;
        color: #0073aa;
        margin-right: 20px;
    }
    .challenge-details {
        flex-grow: 1;
    }
    .challenge-title {
        margin: 0 0 5px 0;
        font-size: 1.5em;
    }
    .challenge-description {
        margin: 0 0 15px 0;
        color: #555;
    }
    .progress-bar-container {
        height: 10px;
        background-color: #eee;
        border-radius: 5px;
        margin-bottom: 5px;
        overflow: hidden;
    }
    .progress-bar {
        height: 100%;
        background-color: #0073aa;
        border-radius: 5px;
        transition: width 0.5s ease-in-out;
    }
    .challenge-card.completed .progress-bar {
        background-color: #5cb85c;
    }
    .progress-label {
        font-size: 0.9em;
        color: #777;
    }
    .challenge-status {
        margin-left: 20px;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-weight: bold;
        color: #fff;
        font-size: 0.9em;
    }
    .status-badge.incomplete {
        background-color: #d9534f;
    }
    .status-badge.complete {
        background-color: #5cb85c;
    }
</style>

<?php
get_footer();
?>
