<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields( 'avatarmx_settings' );
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">URL del Servidor</th>
                <td><input type="text" name="avatarmx_server_url" value="<?php echo esc_attr( get_option('avatarmx_server_url') ); ?>" class="regular-text"/></td>
            </tr>
            <tr valign="top">
                <th scope="row">API Key</th>
                <td><input type="text" name="avatarmx_api_key" value="<?php echo esc_attr( get_option('avatarmx_api_key') ); ?>" class="regular-text"/></td>
            </tr>
        </table>
        <?php
        submit_button();
        ?>
    </form>
</div>
