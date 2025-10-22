<div class="wrap">
    <h1><?php _e('Configuración del Sistema Médico AvatarMX', 'avatarmx'); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('avatarmx_settings');
        do_settings_sections('avatarmx_settings');
        submit_button();
        ?>
    </form>
</div>
