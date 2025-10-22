<div class="wrap">
    <h1><?php _e('Estadísticas del Sistema Médico', 'avatarmx'); ?></h1>

    <div id="avatarmx-stats-container">
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Métrica</th>
                    <th id="columnname" class="manage-column column-columnname" scope="col">Valor</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Consultas Totales</td>
                    <td id="stats-consultas-totales">Cargando...</td>
                </tr>
                <tr class="alternate">
                    <td>Usuarios Activos</td>
                    <td id="stats-usuarios-activos">Cargando...</td>
                </tr>
                <tr>
                    <td>PDFs Procesados</td>
                    <td id="stats-pdfs-procesados">Cargando...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // En una implementación real, esto vendría de una llamada AJAX al servidor
    $('#stats-consultas-totales').text('150');
    $('#stats-usuarios-activos').text('25');
    $('#stats-pdfs-procesados').text('10');
});
</script>
