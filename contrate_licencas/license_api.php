<?php

// Ativa exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Função para log
function write_log($message) {
    $log_file = __DIR__ . '/license_api.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

// Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

write_log('API iniciada');

// Verifica método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    write_log('Método não permitido: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    die(json_encode([
        'valid' => false,
        'message' => 'Método não permitido'
    ]));
}

// Lê dados do POST
$input = file_get_contents('php://input');
write_log('Dados brutos recebidos: ' . $input);

// Tenta primeiro como JSON
$data = json_decode($input, true);

// Se não for JSON, tenta como form-data
if (!$data) {
    parse_str($input, $data);
}

// Se ainda não tiver dados, tenta $_POST
if (!$data) {
    $data = $_POST;
}

write_log('Dados processados: ' . json_encode($data));

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

write_log(sprintf('Validando licença - Key: %s, Domain: %s, Module: %s', 
    $license_key, $domain, $module));

// Configurações do banco de dados
$db_config = [
    'hostname' => 'localhost',
    'username' => 'sql_contratecrm_',
    'password' => 'CAeTAcL5Drycf3Ap',
    'database' => 'sql_contratecrm_',
    'dbprefix' => 'tbl_'
];

write_log('Tentando conectar ao banco...');
write_log('Host: ' . $db_config['hostname']);
write_log('DB: ' . $db_config['database']);

try {
    // Conecta ao banco
    write_log('Conectando ao banco: ' . $db_config['database']);
    $db = mysqli_connect(
        $db_config['hostname'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database']
    );

    if (!$db) {
        throw new Exception('Erro de conexão: ' . mysqli_connect_error());
    }

    write_log('Conexão estabelecida');
    mysqli_set_charset($db, 'utf8');
} catch (Exception $e) {
    write_log('Erro: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'valid' => false,
        'message' => 'Erro ao validar licença'
    ]));
}

try {
    // Consulta a licença
    $table = $db_config['dbprefix'] . 'contrate_licencas';
    write_log('Consultando tabela: ' . $table);
    
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
} catch (Exception $e) {
    write_log('Erro: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'valid' => false,
        'message' => 'Erro ao validar licença'
    ]));
}

if (!$license) {
    echo json_encode([
        'valid' => false,
        'message' => 'Licença inválida ou não encontrada'
    ]);
    exit;
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

    echo json_encode([
        'valid' => false,
        'message' => sprintf('O período de uso do módulo expirou em %s', 
            date('d/m/Y H:i:s', $end_date))
    ]);
    exit;
}

// Licença válida
echo json_encode([
    'valid' => true,
    'message' => 'Licença válida',
    'expiry_date' => date('d/m/Y H:i:s', $end_date)
]);

$db->close();
