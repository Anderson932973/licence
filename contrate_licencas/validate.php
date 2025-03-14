<?php

// Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Função para log
function write_log($message) {
    $log_file = __DIR__ . '/validate.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

write_log('API iniciada');

// Lê dados do POST ou input JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

write_log('Dados recebidos: ' . $input);

// Se não for JSON, tenta POST normal
if (!$data) {
    $data = $_POST;
}

// Valida dados
if (!isset($data['license_key']) || !isset($data['domain']) || !isset($data['module'])) {
    write_log('Dados inválidos ou incompletos');
    http_response_code(400);
    die(json_encode([
        'valid' => false,
        'message' => 'Dados inválidos'
    ]));
}

$license_key = $data['license_key'];
$domain = $data['domain'];
$module = $data['module'];

write_log(sprintf(
    'Validando licença - Key: %s, Domain: %s, Module: %s',
    $license_key, $domain, $module
));

// Configurações do banco de dados
require_once(__DIR__ . '/../../application/config/app-config.php');

try {
    // Conecta ao banco
    $db = mysqli_connect(
        APP_DB_HOSTNAME,
        APP_DB_USERNAME,
        APP_DB_PASSWORD,
        APP_DB_NAME
    );

    if (!$db) {
        throw new Exception('Erro de conexão: ' . mysqli_connect_error());
    }

    mysqli_set_charset($db, 'utf8');
    
    // Consulta a licença
    $table = 'tbl_contrate_licencas';
    $query = "SELECT * FROM {$table} 
              WHERE license_key = ? 
              AND domain = ? 
              AND module_name = ? 
              AND status = 'active'";

    if (!($stmt = $db->prepare($query))) {
        throw new Exception('Erro ao preparar query: ' . $db->error);
    }

    if (!$stmt->bind_param('sss', $license_key, $domain, $module)) {
        throw new Exception('Erro ao vincular parâmetros: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $license = $result->fetch_assoc();
    
    write_log('Consulta executada com sucesso');

    if (!$license) {
        write_log('Licença não encontrada');
        http_response_code(404);
        die(json_encode([
            'valid' => false,
            'message' => 'Licença inválida ou não encontrada'
        ]));
    }

    // Verifica validade
    $now = strtotime('now');
    $end_date = strtotime($license['end_date']);

    if ($now > $end_date) {
        // Atualiza status para expirado
        $update = "UPDATE {$table} SET status = 'expired' WHERE id = ?";
        $stmt = $db->prepare($update);
        $stmt->bind_param('i', $license['id']);
        $stmt->execute();

        write_log('Licença expirada');
        http_response_code(403);
        die(json_encode([
            'valid' => false,
            'message' => sprintf('O período de uso do módulo expirou em %s', 
                date('d/m/Y H:i:s', $end_date))
        ]));
    }

    // Licença válida
    write_log('Licença válida');
    http_response_code(200);
    die(json_encode([
        'valid' => true,
        'message' => 'Licença válida',
        'expiry_date' => date('d/m/Y H:i:s', $end_date)
    ]));

} catch (Exception $e) {
    write_log('Erro: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'valid' => false,
        'message' => 'Erro ao validar licença'
    ]));
}
