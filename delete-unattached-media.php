<?php

/**
 * Plugin Name: Delete Unattached Media
 * Description: Excluir midias desanexadas dentro de um intervalo de data especificado
 * Version 0.1 Beta
 * Author: nome autor
 */

if (!defined('ABSPATH')) {
  exit;
}

function write_custom_log($message) {
  $log_file = plugin_dir_path(__FILE__) . 'logs/debug.log';
  $formatted_message = '[' . date('Y-m-d H:i:s') . ']' . $message . PHP_EOL;
  file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

function delete_unattached_media($start_date, $end_date) {
  global $wpdb;

  $start_timestamp = strtotime($start_date);
  $end_timestamp = strtotime($end_date);

  $unattached_media_ids = $wpdb->get_col($wpdb->prepare("
  SELECT ID FROM {$wpdb->posts}
  WHERE post_type = 'attachment'
  AND post_parent = 0
  AND post_date >= %s
  AND post_date <= %s
  ", date('Y-m-d H:i:s', $start_timestamp), date('Y-m-d H:i:s', $end_timestamp)));

  if (!empty($unattached_media_ids)) {
    foreach ($unattached_media_ids as $key =>  $attachment_id) {
      $attachment_url = wp_get_attachment_url($attachment_id);
      $file_path = get_attached_file($attachment_id);
      $file_exists = file_exists($file_path);
      $files_exclude[] = $attachment_url;
      $deleted = wp_delete_attachment($attachment_id, true);
      // \WP_Post: Retorna o objeto WP_Post se a exclusão foi bem-sucedida.
      if ($deleted instanceof WP_Post) {
        $message = $file_exists ? 'Mídia excluída com sucesso: ' : 'Registro de mídia excluído, mas o arquivo não foi encontrado: ';
        write_custom_log($message . $attachment_url);
        $files_exclude[] = $attachment_url; // Armazena o URL para o retorno

      } else if ($deleted === false) { // false: Retorna false se a exclusão falhou. 
        write_custom_log("Erro ao excluir a mídia: " . $attachment_url);
      } else if ($deleted === null) { // null: Pode retornar null se o ID passado não corresponde a um anexo existente.      
        write_custom_log("Erro ao excluir a mídia id: " . $attachment_id . ' não corresponde à mídia url: ' . $attachment_url);
      }
    }
  } else {
    write_custom_log("Nenhuma mídia desacompanhada encontrada para exclusão no período especificado.");
    return false;
  }
  return $files_exclude;
}

function delete_unattached_media_form() {
  $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
  $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
  $deleted = [];

  // Executa a exclusão se o formulário foi enviado
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($start_date) && !empty($end_date)) {
    $deleted/* eu gostaria de exbir em tela as midias que foram excluidas*/ = delete_unattached_media($start_date, $end_date);

    // Adiciona a mensagem de sucesso ou aviso
    add_action('admin_notices', function () use ($deleted) {
      if (!empty($deleted)) {
        echo '<div class="notice notice-success is-dismissible"><p>Mídias desanexadas excluídas com sucesso!</p></div>';
      } else {
        echo '<div class="notice notice-warning is-dismissible"><p>Nenhuma mídia desanexada encontrada para exclusão no período especificado.</p></div>';
      }
    });
  }
?>
  <div class="wrap">
    <h1>Excluir Mídias Desanexadas</h1>
    <form method="POST" action="">
      <label for="start_date">Data de início:</label>
      <input type="date" name="start_date" id="start_date" required />
      <label for="end_date">Data de fim:</label>
      <input type="date" name="end_date" id="end_date" required />
      <input type="submit" value="Excluir Mídias" class="button">
    </form>
  </div>
<?php
}

function delete_unattached_media_menu() {
  add_menu_page(
    'Excluír Mídias Desanexadas',
    'Excluír Mídias Desanexadas',
    'manage_options',
    'delete_unattached_media',
    'delete_unattached_media_form'
  );
}

add_action('admin_menu', 'delete_unattached_media_menu');
