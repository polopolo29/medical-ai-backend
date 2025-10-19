jQuery(document).ready(function($) {
    'use strict';

    // Manejar el envío del formulario para crear un nuevo perfil
    $('#create-profile-form').on('submit', function(e) {
        e.preventDefault();

        var profileName = $('#profile-name').val();
        var nonce = $('#sap_nonce').val();

        if (!profileName) {
            alert('Por favor, introduce un nombre para el perfil.');
            return;
        }

        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sap_create_profile',
                name: profileName,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Recargar para mostrar el nuevo perfil
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.');
            }
        });
    });

    // Manejar el clic en el botón de eliminar perfil
    $('#profiles-list').on('click', '.delete-profile', function(e) {
        e.preventDefault();

        if (!confirm('¿Estás seguro de que quieres eliminar este perfil? Esta acción no se puede deshacer.')) {
            return;
        }

        var profileRow = $(this).closest('tr');
        var profileId = profileRow.data('profile-id');

        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sap_delete_profile',
                id: profileId,
                nonce: sap_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    profileRow.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.');
            }
        });
    });

    // Abrir el modal de edición
    $('#profiles-list').on('click', '.edit-profile', function(e) {
        e.preventDefault();
        var profileRow = $(this).closest('tr');
        var profileId = profileRow.data('profile-id');
        var profileName = profileRow.find('td:first').text();

        $('#edit-profile-id').val(profileId);
        $('#edit-profile-name').val(profileName);
        $('#edit-profile-modal').show();
    });

    // Cerrar el modal
    $('.sap-modal-close').on('click', function() {
        $('#edit-profile-modal').hide();
    });

    // Cerrar el modal si se hace clic fuera del contenido
    $(window).on('click', function(e) {
        if ($(e.target).is('#edit-profile-modal')) {
            $('#edit-profile-modal').hide();
        }
    });

    // Manejar el envío del formulario de edición
    $('#edit-profile-form').on('submit', function(e) {
        e.preventDefault();

        var profileId = $('#edit-profile-id').val();
        var newProfileName = $('#edit-profile-name').val();

        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sap_update_profile',
                id: profileId,
                name: newProfileName,
                nonce: sap_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#edit-profile-modal').hide();
                    $('tr[data-profile-id="' + profileId + '"]').find('td:first').text(newProfileName);
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.');
            }
        });
    });
});
