jQuery(document).ready(function($) {
    'use strict';

    // Función para mostrar notificaciones
    function showNotification(message, type = 'success') {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notificationHtml = '<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>';

        var notification = $(notificationHtml);
        $('#sap-notifications').html(notification);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notification.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Manejo de pestañas
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).attr('href');
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-pane').hide();
        $(tab).show();
    });

    // Manejar el envío del formulario para crear un nuevo perfil
    $('#create-profile-form').on('submit', function(e) {
        e.preventDefault();

        var profileName = $('#profile-name').val();
        var nonce = $('#sap_nonce').val();

        if (!profileName) {
            showNotification('Por favor, introduce un nombre para el perfil.', 'error');
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
                    showNotification('Perfil creado con éxito. Recargando...', 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.', 'error');
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
                    showNotification('Perfil eliminado con éxito.', 'success');
                    profileRow.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.', 'error');
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
        if ($(e.target).is('.sap-modal')) {
            $(e.target).hide();
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
                    showNotification('Perfil actualizado con éxito.', 'success');
                    $('#edit-profile-modal').hide();
                    $('tr[data-profile-id="' + profileId + '"]').find('td:first').text(newProfileName);
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.', 'error');
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
        $('#connections-modal').show();

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
                } else {
                    showNotification('Error al cargar las conexiones.', 'error');
                }
            },
            error: function() {
                showNotification('Ha ocurrido un error inesperado.', 'error');
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
                    showNotification('Conexión guardada correctamente.', 'success');
                    $('#connections-modal').hide();
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Ha ocurrido un error inesperado.', 'error');
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
                    showNotification('Plataforma desconectada correctamente.', 'success');
                    $('#connections-modal').hide();
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.', 'error');
            }
        });
    });

    // Lógica de subida de videos
    var mediaUploader;
    $('#select-video-button').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: 'Elige un Video',
            button: { text: 'Elegir este Video' },
            multiple: false,
            library: { type: 'video' }
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#selected-video-id').val(attachment.id);
            $('#video-preview').attr('src', attachment.url);
            $('#video-preview-container').show();
        });
        mediaUploader.open();
    });

    // Cargar plataformas al cambiar de perfil
    $('#profile-selector').on('change', function() {
        var profileId = $(this).val();
        var container = $('#platforms-checkboxes');

        if (!profileId) {
            container.html('<p>Selecciona un perfil para ver las plataformas conectadas.</p>');
            return;
        }
        container.html('Cargando...');
        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: { action: 'sap_get_connections', profile_id: profileId, nonce: sap_ajax.nonce },
            success: function(response) {
                if (response.success) {
                    var checkboxesHtml = $(response.data.html).find('.platform-connection-item').map(function() {
                        if ($(this).find('.status-connected').length > 0) {
                            var platformName = $(this).find('h4').text().toLowerCase().trim();
                            return '<label><input type="checkbox" name="platforms[]" value="' + platformName + '"> ' + platformName.charAt(0).toUpperCase() + platformName.slice(1) + '</label>';
                        }
                    }).get().join('');
                    container.html(checkboxesHtml || '<p>No hay plataformas conectadas para este perfil.</p>');
                } else {
                    container.html('<p>Error al cargar las plataformas.</p>');
                }
            },
            error: function() {
                container.html('<p>Error inesperado.</p>');
            }
        });
    });

    // Manejar el envío del formulario de programación
    $('#upload-shorts-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serializeArray().reduce(function(obj, item) {
            if (item.name.endsWith('[]')) {
                var key = item.name.slice(0, -2);
                if (!obj[key]) { obj[key] = []; }
                obj[key].push(item.value);
            } else {
                obj[item.name] = item.value;
            }
            return obj;
        }, {});

        formData.action = 'sap_schedule_short';
        formData.nonce = $('#sap_schedule_nonce').val();

        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    $('#upload-shorts-form')[0].reset();
                    $('#video-preview-container').hide();
                    $('#platforms-checkboxes').html('<p>Selecciona un perfil para ver las plataformas conectadas.</p>');
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Ha ocurrido un error inesperado.', 'error');
            }
        });
    });

    // Cancelar programación
    $('#queue-list').on('click', '.cancel-schedule', function(e) {
        e.preventDefault();
        if (!confirm('¿Estás seguro de que quieres cancelar esta programación?')) {
            return;
        }
        var row = $(this).closest('tr');
        var queueId = row.data('queue-id');

        $.ajax({
            url: sap_ajax.ajax_url,
            type: 'POST',
            data: { action: 'sap_cancel_schedule', queue_id: queueId, nonce: sap_ajax.nonce },
            success: function(response) {
                if (response.success) {
                    showNotification('Programación cancelada con éxito.', 'success');
                    row.fadeOut(300, function() { $(this).remove(); });
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Ha ocurrido un error inesperado.', 'error');
            }
        });
    });
});
