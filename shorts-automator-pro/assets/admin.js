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
        $(this).closest('.sap-modal').hide();
    });

    // Cerrar el modal si se hace clic fuera del contenido
    $(window).on('click', function(e) {
        if ($(e.target).is('#edit-profile-modal')) {
            $('#edit-profile-modal').hide();
        }
        if ($(e.target).is('#connections-modal')) {
            $('#connections-modal').hide();
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

    // Abrir el modal de conexiones
    $('#profiles-list').on('click', '.manage-connections', function(e) {
        e.preventDefault();
        var profileRow = $(this).closest('tr');
        var profileId = profileRow.data('profile-id');
        var profileName = profileRow.find('td:first').text();

        $('#connections-profile-id').val(profileId);
        $('#connections-profile-name').text(profileName);
        $('#platforms-list').html('Cargando...');

        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sap_get_connections',
                profile_id: profileId,
                nonce: sap_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#platforms-list').html(response.data.html);
                    $('#connections-modal').show();
                } else {
                    alert('Error al cargar las conexiones.');
                }
            },
            error: function() {
                alert('Ha ocurrido un error inesperado.');
            }
        });
    });

    // Mostrar/ocultar formulario de conexión
    $('#platforms-list').on('click', '.toggle-connection-form', function(e) {
        e.preventDefault();
        $(this).siblings('.connection-form').slideToggle();
    });

    // Guardar conexión
    $('#platforms-list').on('submit', '.save-connection-form', function(e) {
        e.preventDefault();

        var form = $(this);
        var profileId = $('#connections-profile-id').val();
        var platform = form.data('platform');
        var credentials = form.serialize();

        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sap_save_connection',
                profile_id: profileId,
                platform: platform,
                credentials: credentials,
                nonce: sap_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Conexión guardada correctamente.');
                    // Recargar el modal para mostrar el nuevo estado
                    $('.manage-connections[data-profile-id="' + profileId + '"]').click();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Ha ocurrido un error inesperado.');
            }
        });
    });

    // Desconectar plataforma
    $('#platforms-list').on('click', '.disconnect-platform', function(e) {
        e.preventDefault();

        if (!confirm('¿Estás seguro de que quieres desconectar esta plataforma?')) {
            return;
        }

        var button = $(this);
        var profileId = $('#connections-profile-id').val();
        var platform = button.data('platform');

        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sap_disconnect_platform',
                profile_id: profileId,
                platform: platform,
                nonce: sap_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Plataforma desconectada correctamente.');
                    // Recargar el modal para mostrar el nuevo estado
                    $('.manage-connections[data-profile-id="' + profileId + '"]').click();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Ha ocurrido un error inesperado.');
            }
        });
    });
});
