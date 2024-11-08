<?php

/**
 * Plugin Name: Delete Unattached Media
 * Description: Excluir mídias desanexadas dentro de um intervalo de data especificado
 * Version: 0.2 Beta
 * Author: nome autor
 */

// Evitar acesso direto ao arquivo
if (!defined('ABSPATH')) {
  exit;
}

// Função chamada na ativação do plugin
function delete_unattached_media_activate() {
  // Verifica se o diretório de logs existe e cria, caso necessário
  $log_dir = plugin_dir_path(__FILE__) . 'logs/';
  if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true); // Cria o diretório com permissões adequadas
  }
  write_custom_log('Plugin ativado. Preparando para uso.', 'plugin_activation.log');
}

// Registrar a função de ativação
register_activation_hook(__FILE__, 'delete_unattached_media_activate');

// Função para escrever no log
function write_custom_log($message, $log_file) {
  // Defina o diretório de logs dentro da função
  $log_dir = plugin_dir_path(__FILE__) . 'logs'; // Diretório de logs
  $formatted_message = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

  // Verifica se o diretório de logs existe
  if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true); // Cria o diretório de logs, se necessário
  }

  file_put_contents($log_dir . '/' . $log_file, $formatted_message, FILE_APPEND);
}


// Função para excluir mídias desanexadas
function delete_unattached_media($start_date, $end_date) {
  global $wpdb;

  $start_timestamp = strtotime($start_date);
  $end_timestamp = strtotime($end_date);

  // Ajuste para lidar com o mesmo dia
  if ($start_date === $end_date) {
    $start_datetime = date('Y-m-d 00:00:00', $start_timestamp);
    $end_datetime = date('Y-m-d 23:59:59', $end_timestamp);
  } else {
    $start_datetime = date('Y-m-d H:i:s', $start_timestamp);
    $end_datetime = date('Y-m-d H:i:s', $end_timestamp);
  }

// Gerar um nome único para o arquivo de log baseado nas datas
$log_file_name = 'delete_media_' . date('Y-m-d') . '-start-' . sanitize_title($start_date) . '-end-' . sanitize_title($end_date) . '.log';


  // Iniciar o array de mensagens de log
  $log_messages = [];

  // Obter os IDs das mídias desanexadas
  $unattached_media_ids = $wpdb->get_col($wpdb->prepare("
    SELECT ID FROM {$wpdb->posts}
    WHERE post_type = 'attachment'
    AND post_parent = 0
    AND post_date >= %s
    AND post_date <= %s
  ", $start_datetime, $end_datetime));

  // Verificar se existem mídias desanexadas
  if (!empty($unattached_media_ids)) {
    foreach ($unattached_media_ids as $attachment_id) {
      $attachment_url = wp_get_attachment_url($attachment_id);
      $file_path = get_attached_file($attachment_id);
      $file_exists = file_exists($file_path);
      $deleted = wp_delete_attachment($attachment_id, true);

      if ($deleted instanceof WP_Post) {
        $message = $file_exists ? 'Mídia excluída com sucesso: ' : 'Registro de mídia excluído, mas o arquivo não foi encontrado: ';
        $full_message = $message . $attachment_url;
        write_custom_log($full_message, $log_file_name);
        $log_messages[] = $full_message; // Armazena a mensagem para exibição
      } else if ($deleted === false) {
        $full_message = "Erro ao excluir a mídia: " . $attachment_url;
        write_custom_log($full_message, $log_file_name);
        $log_messages[] = $full_message;
      } else if ($deleted === null) {
        $full_message = "Erro ao excluir a mídia id: " . $attachment_id . ' não corresponde à mídia url: ' . $attachment_url;
        write_custom_log($full_message, $log_file_name);
        $log_messages[] = $full_message;
      }
    }
  } else {
    $full_message = "Nenhuma mídia desacompanhada encontrada para exclusão no período especificado.";
    write_custom_log($full_message, $log_file_name);
    $log_messages[] = $full_message;
  }

  return $log_messages; // Retorna o array de mensagens de log
}

// Função para exibir o formulário
function delete_unattached_media_form() {
  $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
  $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
  $log_messages = [];

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($start_date) && !empty($end_date)) {
    // Validação de datas
    if (strtotime($start_date) === false || strtotime($end_date) === false) {
      echo '<div class="notice notice-error is-dismissible"><p>Por favor, insira datas válidas.</p></div>';
      return;
    }

    $log_messages = delete_unattached_media($start_date, $end_date);

    add_action('admin_notices', function () use ($log_messages) {
      // Verifique se há mensagens de log
      if (isset($log_messages) && !empty($log_messages)) {
        // Exibe o sucesso se houver mensagens
        echo '<div class="notice notice-success is-dismissible"><p>' . count($log_messages) . ' mídias desanexadas excluídas com sucesso!</p></div>';
      } else {
        // Caso contrário, exibe um aviso de que nenhuma mídia foi encontrada
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
    <?php if (!empty($log_messages)): ?>
      <h2>Relatório de Exclusão:</h2>
      <ul>
        <?php foreach ($log_messages as $message): ?>
          <li><?php echo esc_html($message); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php
}

// Função para adicionar o menu do plugin
function delete_unattached_media_menu() {
  add_menu_page(
    'Excluir Mídias Desanexadas',
    'Excluir Mídias Desanexadas',
    'manage_options',
    'delete_unattached_media',
    'delete_unattached_media_form'
  );
}

add_action('admin_menu', 'delete_unattached_media_menu');
