<?php
// Verifica se o código está sendo carregado diretamente e impede isso
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Impede o acesso direto
}

// Função para exibir a página de relatórios
function media_reports_page() {
    global $wpdb;
    $selected_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : '';
    $selected_year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : '';
    $selected_day = isset($_GET['day']) ? sanitize_text_field($_GET['day']) : '';

    // Consulta para obter a contagem de mídias desanexadas por dia/mês/ano
    $query = "
        SELECT DATE_FORMAT(post_date, '%Y-%m-%d') AS day, COUNT(*) AS total
        FROM {$wpdb->posts}
        WHERE post_type = 'attachment'
        AND post_parent = 0
        GROUP BY DATE_FORMAT(post_date, '%Y-%m-%d')
        ORDER BY post_date ASC
    ";
    
    $media_counts = $wpdb->get_results($query);
    ?>
    <div class="wrap">
        <h1>Relatório de Mídias Desanexadas por Dia</h1>
        <form method="GET" action="">
            <input type="hidden" name="page" value="delete-unattached-media-report">
            <label for="day">Dia:</label>
            <select name="day" id="day">
                <option value="">Todos os dias</option>
                <?php
                if ($selected_month && $selected_year) {
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $day_value = str_pad($day, 2, '0', STR_PAD_LEFT);
                        echo '<option value="' . $day_value . '"' . selected($selected_day, $day_value, false) . '>' . $day_value . '</option>';
                    }
                }
                ?>
            </select>
            
            <label for="month">Mês:</label>
            <select name="month" id="month">
                <?php
                $months = [
                    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
                    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
                ];
                foreach ($months as $key => $value) {
                    echo '<option value="' . $key . '"' . selected($selected_month, $key, false) . '>' . $value . '</option>';
                }
                ?>
            </select>
            
            <label for="year">Ano:</label>
            <select name="year" id="year">
                <?php
                $years = range(date('Y'), 2021); // Gera um range de anos do ano atual até 2000
                foreach ($years as $year) {
                    echo '<option value="' . $year . '"' . selected($selected_year, $year, false) . '>' . $year . '</option>';
                }
                ?>
            </select>

            <input type="submit" value="Filtrar" class="button">
        </form>

        <h2>Resultados</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Quantidade de Mídias Desanexadas</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($media_counts)) {
                    foreach ($media_counts as $media) {
                        $day = date('d-m-Y', strtotime($media->day));
                        if ($selected_month && $selected_year) {
                            if (strpos($day, "$selected_year-$selected_month") !== false) {
                                if ($selected_day && strpos($day, "$selected_year-$selected_month-$selected_day") !== false) {
                                    echo '<tr>';
                                    echo '<td>' . esc_html($day) . '</td>';
                                    echo '<td>' . esc_html($media->total) . '</td>';
                                    echo '</tr>';
                                } elseif (!$selected_day) {
                                    echo '<tr>';
                                    echo '<td>' . esc_html($day) . '</td>';
                                    echo '<td>' . esc_html($media->total) . '</td>';
                                    echo '</tr>';
                                }
                            }
                        } else {
                            echo '<tr>';
                            echo '<td>' . esc_html($day) . '</td>';
                            echo '<td>' . esc_html($media->total) . '</td>';
                            echo '</tr>';
                        }
                    }
                } else {
                    echo '<tr><td colspan="2">Nenhum dado encontrado.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}