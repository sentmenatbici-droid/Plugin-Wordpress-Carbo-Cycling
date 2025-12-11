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
                        location.reload();
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

    // Cerrar modales al hacer clic en la 'X'
    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', function() {
            closeModal('#add-challenge-modal');
            closeModal('#edit-challenge-modal');
        });
    });
});