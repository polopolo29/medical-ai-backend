<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div id="shorts-automator-pro-app">

        <!-- Pestañas de Navegación -->
        <h2 class="nav-tab-wrapper">
            <a href="#profiles-management" class="nav-tab nav-tab-active"><?php _e( 'Gestión de Perfiles', 'shorts-automator-pro' ); ?></a>
            <a href="#upload-shorts" class="nav-tab"><?php _e( 'Subir Shorts', 'shorts-automator-pro' ); ?></a>
            <a href="#scheduling" class="nav-tab"><?php _e( 'Programación', 'shorts-automator-pro' ); ?></a>
            <a href="#analytics" class="nav-tab"><?php _e( 'Analíticas', 'shorts-automator-pro' ); ?></a>
        </h2>

        <!-- Contenido de las Pestañas -->
        <div class="tab-content">

            <!-- Pestaña: Gestión de Perfiles -->
            <div id="profiles-management" class="tab-pane active">
                <h2><?php _e( 'Gestión de Perfiles', 'shorts-automator-pro' ); ?></h2>
                <p><?php _e( 'Crea y administra tus perfiles de publicación. Cada perfil puede tener sus propias conexiones a plataformas.', 'shorts-automator-pro' ); ?></p>

                <div id="col-container">

                    <!-- Columna Izquierda: Crear Nuevo Perfil -->
                    <div id="col-left">
                        <div class="col-wrap">
                            <h3><?php _e( 'Crear Nuevo Perfil', 'shorts-automator-pro' ); ?></h3>
                            <form id="create-profile-form" method="post">
                                <div class="form-field">
                                    <label for="profile-name"><?php _e( 'Nombre del Perfil', 'shorts-automator-pro' ); ?></label>
                                    <input type="text" id="profile-name" name="profile_name" required>
                                    <p><?php _e( 'Ej: "Canal de Fútbol", "Canal de Cine", "Marketing Digital".', 'shorts-automator-pro' ); ?></p>
                                </div>
                                <?php wp_nonce_field( 'sap_create_profile_nonce', 'sap_nonce' ); ?>
                                <button type="submit" class="button button-primary"><?php _e( 'Crear Perfil', 'shorts-automator-pro' ); ?></button>
                            </form>
                        </div>
                    </div>

                    <!-- Columna Derecha: Lista de Perfiles -->
                    <div id="col-right">
                        <div class="col-wrap">
                            <h3><?php _e( 'Perfiles Existentes', 'shorts-automator-pro' ); ?></h3>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php _e( 'Nombre', 'shorts-automator-pro' ); ?></th>
                                        <th scope="col"><?php _e( 'Fecha de Creación', 'shorts-automator-pro' ); ?></th>
                                        <th scope="col"><?php _e( 'Acciones', 'shorts-automator-pro' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="profiles-list">
                                    <?php
                                    $profiles = Shorts_Automator_Pro_Profile_Manager::get_profiles();
                                    if ( ! empty( $profiles ) ) :
                                        foreach ( $profiles as $profile ) :
                                    ?>
                                    <tr data-profile-id="<?php echo esc_attr( $profile->id ); ?>">
                                        <td><?php echo esc_html( $profile->name ); ?></td>
                                        <td><?php echo esc_html( $profile->created_at ); ?></td>
                                        <td>
                                            <button class="button button-primary manage-connections"><?php _e( 'Gestionar Conexiones', 'shorts-automator-pro' ); ?></button>
                                            <button class="button button-secondary edit-profile"><?php _e( 'Editar', 'shorts-automator-pro' ); ?></button>
                                            <button class="button button-danger delete-profile"><?php _e( 'Eliminar', 'shorts-automator-pro' ); ?></button>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;
                                    else :
                                    ?>
                                    <tr>
                                        <td colspan="3"><?php _e( 'No se encontraron perfiles. ¡Crea uno!', 'shorts-automator-pro' ); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Pestaña: Subir Shorts -->
            <div id="upload-shorts" class="tab-pane" style="display:none;">
                <h2><?php _e( 'Subir y Programar Shorts', 'shorts-automator-pro' ); ?></h2>
                <form id="upload-shorts-form">
                    <div class="form-section">
                        <h3>1. <?php _e( 'Selecciona el Video', 'shorts-automator-pro' ); ?></h3>
                        <div id="video-drop-zone">
                            <button type="button" class="button" id="select-video-button"><?php _e( 'Seleccionar Video de la Biblioteca', 'shorts-automator-pro' ); ?></button>
                            <div id="video-preview-container" style="display:none;">
                                <video id="video-preview" controls style="max-width:300px; margin-top:10px;"></video>
                                <input type="hidden" id="selected-video-id" name="video_id">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>2. <?php _e( 'Selecciona el Perfil de Destino', 'shorts-automator-pro' ); ?></h3>
                        <select id="profile-selector" name="profile_id" required>
                            <option value=""><?php _e( 'Selecciona un perfil...', 'shorts-automator-pro' ); ?></option>
                            <?php foreach ( $profiles as $profile ) : ?>
                                <option value="<?php echo esc_attr( $profile->id ); ?>"><?php echo esc_html( $profile->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-section">
                        <h3>3. <?php _e( 'Selecciona las Plataformas', 'shorts-automator-pro' ); ?></h3>
                        <div id="platforms-checkboxes">
                            <!-- Checkboxes se cargarán dinámicamente aquí -->
                            <p><?php _e( 'Selecciona un perfil para ver las plataformas conectadas.', 'shorts-automator-pro' ); ?></p>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>4. <?php _e( 'Programa la Publicación', 'shorts-automator-pro' ); ?></h3>
                        <input type="datetime-local" id="publish-time" name="publish_time" required>
                    </div>

                    <div class="form-section">
                        <h3>5. <?php _e( 'Añade los Metadatos', 'shorts-automator-pro' ); ?></h3>
                        <div class="form-field">
                            <label for="video-title"><?php _e( 'Título', 'shorts-automator-pro' ); ?></label>
                            <input type="text" id="video-title" name="title" required>
                        </div>
                        <div class="form-field">
                            <label for="video-description"><?php _e( 'Descripción', 'shorts-automator-pro' ); ?></label>
                            <textarea id="video-description" name="description"></textarea>
                        </div>
                    </div>

                    <?php wp_nonce_field( 'sap_schedule_short_nonce', 'sap_schedule_nonce' ); ?>
                    <button type="submit" class="button button-primary"><?php _e( 'Programar Short', 'shorts-automator-pro' ); ?></button>
                </form>
            </div>

        </div> <!-- .tab-content -->

    </div> <!-- #shorts-automator-pro-app -->

    <!-- Modal para Editar Perfil -->
    <div id="edit-profile-modal" class="sap-modal" style="display:none;">
        <div class="sap-modal-content">
            <span class="sap-modal-close">&times;</span>
            <h2><?php _e( 'Editar Perfil', 'shorts-automator-pro' ); ?></h2>
            <form id="edit-profile-form">
                <input type="hidden" id="edit-profile-id" name="profile_id">
                <div class="form-field">
                    <label for="edit-profile-name"><?php _e( 'Nuevo Nombre del Perfil', 'shorts-automator-pro' ); ?></label>
                    <input type="text" id="edit-profile-name" name="profile_name" required>
                </div>
                <button type="submit" class="button button-primary"><?php _e( 'Guardar Cambios', 'shorts-automator-pro' ); ?></button>
            </form>
        </div>
    </div>

    <!-- Modal para Gestionar Conexiones -->
    <div id="connections-modal" class="sap-modal" style="display:none;">
        <div class="sap-modal-content">
            <span class="sap-modal-close">&times;</span>
            <h2><?php _e( 'Gestionar Conexiones para', 'shorts-automator-pro' ); ?> <span id="connections-profile-name"></span></h2>
            <input type="hidden" id="connections-profile-id">

            <div id="platforms-list">
                <!-- Aquí se cargarán las plataformas dinámicamente -->
            </div>
        </div>
    </div>

</div>
