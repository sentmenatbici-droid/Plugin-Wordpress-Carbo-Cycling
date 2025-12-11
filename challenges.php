<?php
 /**
 * Template Name: Mi App - Página de Desafíos
 */
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Logros - Carbo Cycling</title>
    <?php wp_head(); ?>
    <style>
    	.back-link { 
		    display: inline-block; 
		    margin-top: 2rem; 
		    padding: 0.5rem 1rem; 
		    background-color: #6c757d; 
		    color: white; 
		    text-decoration: none; 
		    border-radius: 4px;
	 .challenges-page { max-width: 800px; margin: 0 auto; }
	 .challenge-card { border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; overflow: hidden; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
	 .challenge-card-header { background-color: #0073aa; color: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
	 .challenge-card-header h3 { margin: 0; font-size: 1.2em; }
	 .challenge-status { background: #ffc107; color: #333; padding: 5px 10px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
	 .challenge-card-body { padding: 20px; }
	 .challenge-progress { margin-top: 15px; }
	 .progress-bar-container { width: 100%; background-color: #e0e0e0; border-radius: 5px; }
	 .progress-bar { height: 20px; background-color: #28a745; border-radius: 5px; transition: width 0.5s ease-in-out; }
	 .challenge-card.completed .challenge-status { background-color: #28a745; }
	 </style>
</head>
<body>    
 <div id="primary" class="content-area">
 <main id="main" class="site-main">
 <div class="mi-app-container">
 <article class="challenges-page">
 <header class="entry-header">
 	<a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a><br>
 <h1 class="entry-title">Desafíos y Logros</h1>
 </header>
 <div class="challenges-list">
 <?php
 global $wpdb;
 $user_id = get_current_user_id();
 $table_challenges = $wpdb->prefix . 'mi_app_challenges';
 $table_progress = $wpdb->prefix . 'mi_app_user_challenge_progress';

 // Obtenemos todos los desafíos
 $all_challenges = $wpdb->get_results("SELECT * FROM {$table_challenges}");

 // Obtenemos los IDs de los desafíos completados por el usuario actual
 $completed_challenge_ids = $wpdb->get_col("SELECT challenge_id FROM {$table_progress} WHERE user_id = %d", $user_id);

 if ($all_challenges):
 foreach ($all_challenges as $challenge):
 $is_completed = in_array($challenge->id, $completed_challenge_ids);
 $status_class = $is_completed ? 'completed' : 'in-progress';
 $status_text = $is_completed ? '¡Completado!' : 'En Progreso';
 ?>
 <div class="challenge-card <?php echo esc_attr($status_class); ?>">
 <div class="challenge-card-header">
 <h3><?php echo esc_html($challenge->title); ?></h3>
 <span class="challenge-status"><?php echo esc_html($status_text); ?></span>
 </div>
 <div class="challenge-card-body">
 <p><?php echo esc_html($challenge->description); ?></p>
 <div class="challenge-progress">
 <div class="progress-bar-container">
 <div class="progress-bar" style="width: <?php echo $is_completed ? 100 : 0; ?>%;"></div>
 </div>
 </div>
 </div>
 </div>
 <?php
 endforeach;
 else:
 echo '<p>Aún no hay desafíos disponibles. ¡Vuelve pronto!</p>';
 endif;
 ?>
 </div>
 <a href="<?php echo home_url('/mi-app/dashboard/'); ?>" class="back-link">← Volver al Panel Principal</a><br>
 </article>
 </div>
 </main>
 </div>
<script>
 // Script para manejar los desafíos y logros
document.addEventListener('DOMContentLoaded', function() {
    // Usamos event delegation para manejar los clics en cualquier botón dentro de la página
    document.querySelector('.mi-app-container').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('add-challenge-btn')) {
            e.preventDefault();
            openModal('#add-challenge-modal');
        }

        if (e.target && e.target.classList.contains('edit-challenge-btn')) {
            e.preventDefault();
            const challengeId = e.target.dataset.challengeId;
            const challengeTitle = e.target.dataset.challengeTitle;
            const challengeType = e.target.dataset.challengeType;
            const challengeTarget = e.target.dataset.challengeTarget;
            const challengeMeta = e.target.dataset.challengeMeta;

            // Rellenar el formulario de edición
            document.getElementById('edit-challenge-id').value = challengeId;
            document.getElementById('edit-challenge-title').value = challengeTitle;
            document.getElementById('edit-challenge-type').value = challengeType;
            document.getElementById('edit-challenge-target').value = challengeTarget;
            document.getElementById('edit-challenge-meta').value = challengeMeta;
            
            openModal('#edit-challenge-modal');
        }

        if (e.target && e.target.classList.contains('delete-challenge-btn')) {
            e.preventDefault();
            if (confirm('¿Estás seguro de que quieres eliminar este desafío?')) {
                const challengeId = e.target.dataset.challengeId;
                const formData = {
                    action: 'delete_challenge',
                    challenge_id: challengeId,
                    nonce: '<?php echo wp_create_nonce('manage_challenges_nonce'); ?>'
                };
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Recarga la página para mostrar los cambios
                    } else {
                        alert('Error: ' + data.data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error inesperado.');
                });
            }
        }
    });

    // Funciones para manejar los modales
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    document.querySelector('.close-modal').addEventListener('click', function() {
        closeModal('#add-challenge-modal');
        closeModal('#edit-challenge-modal');
    });
});
</Script>

 
 <?php get_footer(); ?>

 </body>
</html>