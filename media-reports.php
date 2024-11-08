<?php
// Verifica se o código está sendo carregado diretamente e impede isso
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Impede o acesso direto
}

// Função para exibir a aba de relatórios de mídias desanexadas
function media_reports_menu() {
    add_menu_page(
        'Relatório de Mídias Desanexadas',
        'Relatório de Mídias Desanexadas',
        'manage_options',
        'media-reports',
        'media_reports_page',
        'dashicons-chart-line',
        30
    );
}
add_action('admin_menu', 'media_reports_menu');

// Função para exibir a página de relatórios
function media_reports_page() {
    global $wpdb;
    $selected_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : '';
    $selected_year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : '';

    // Consulta para obter a contagem de mídias desanexadas por mês/ano
    $query = "
        SELECT DATE_FORMAT(post_date, '%Y-%m') AS month_year, COUNT(*) AS total
        FROM {$wpdb->posts}
        WHERE post_type = 'attachment'
        AND post_parent = 0
        GROUP BY DATE_FORMAT(post_date, '%Y-%m')
        ORDER BY post_date DESC
    ";
    
    $media_counts = $wpdb->get_results($query);
    ?>
    <div class="wrap">
        <h1>Relatório de Mídias Desanexadas por Mês</h1>
        <form method="GET" action="">
            <input type="hidden" name="page" value="media-reports">
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
                $years = range(date('Y'), 2000); // Gera um range de anos do ano atual até 2000
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
                        $month_year = $media->month_year;
                        if ($selected_month && $selected_year) {
                            if (strpos($month_year, "$selected_year-$selected_month") !== false) {
                                echo '<tr>';
                                echo '<td>' . esc_html($month_year) . '</td>';
                                echo '<td>' . esc_html($media->total) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr>';
                            echo '<td>' . esc_html($month_year) . '</td>';
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

