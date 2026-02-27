<?php
require_once '../database/db_connect.php';
header('Content-Type: application/json');

// Buscar configurações
$config_query = $conn->query("SELECT * FROM configuracoes LIMIT 1");
$config = $config_query->fetch(PDO::FETCH_ASSOC);

$evolution_api_url = $config['evolution_api_url'] ?? '';
$evolution_api_token = $config['evolution_api_token'] ?? '';
$instance_id = $config['evolution_instance_id'] ?? '';

function evolution_api($endpoint, $method = 'GET', $data = [], $query = []) {
    global $evolution_api_url, $evolution_api_token;
    $url = rtrim($evolution_api_url, '/') . $endpoint;
    if (!empty($query)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
    }
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $evolution_api_token
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return [
        'body' => $response,
        'http_code' => $http_code,
        'error' => $error
    ];
}

$action = $_GET['action'] ?? '';

// Buscar do banco
$instance_id = $config['evolution_instance_id'] ?? '';
$instance_name = $config['evolution_instance_name'] ?? '';

// 0. Deletar/desconectar instância
if ($action === 'delete' && $instance_name) {
    $result = evolution_api('/instance/delete/' . $instance_name, 'DELETE');
    $data = json_decode($result['body'], true);
    if (isset($data['status']) && $data['status'] === 'SUCCESS' && !$data['error']) {
        // Limpa os campos no banco
        $stmt = $conn->prepare("UPDATE configuracoes SET evolution_instance_id = NULL, evolution_instance_name = NULL WHERE id = 1");
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Instância desconectada e removida com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao deletar instância: ' . $result['body']]);
    }
    exit;
}

// 1. Se ambos existem, só consulta status/QR Code, nunca cria nova
if ($action === 'qrcode' && $instance_id && $instance_name) {
    $result = evolution_api('/instance/connectionState/' . $instance_name, 'GET');
    file_put_contents(__DIR__ . '/debug_evo_status.log', $result['body']);
    $data = json_decode($result['body'], true);
    $state = $data['instance']['state'] ?? 'unknown';
    if ($state === 'open' || $state === 'connected') {
        echo json_encode([
            'success' => true,
            'already_connected' => true,
            'message' => 'Você já está conectado ao WhatsApp!',
            'instance_id' => $instance_id,
            'instance_name' => $instance_name
        ]);
        exit;
    }
    $qrcode = '';
    if (isset($data['qrcode']['base64'])) {
        $qrcode = $data['qrcode']['base64'];
    } elseif (isset($data['qrcode'])) {
        $qrcode = $data['qrcode'];
    }
    echo json_encode([
        'success' => true,
        'qrcode' => $qrcode,
        'instance_id' => $instance_id,
        'instance_name' => $instance_name
    ]);
    exit;
}

// 2. Se ambos NULL, só então criar nova instância
if ($action === 'qrcode' && !$instance_id && !$instance_name) {
    $nome_instancia = 'loja_' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $payload = [
        "instanceName" => $nome_instancia,
        "qrcode" => true,
        "integration" => "WHATSAPP-BAILEYS"
    ];
    $result = evolution_api('/instance/create', 'POST', $payload);
    $log_content = "HTTP_CODE: " . $result['http_code'] . "\nERROR: " . $result['error'] . "\nBODY: " . $result['body'];
    file_put_contents(__DIR__ . '/debug_evo_create.log', $log_content);
    $data = json_decode($result['body'], true);
    $instance_id = $data['instance']['instanceId'] ?? '';
    $instance_name = $data['instance']['instanceName'] ?? '';
    file_put_contents(__DIR__ . '/debug_evo_create.log', 'instance_id: ' . $instance_id . ' | instance_name: ' . $instance_name . ' | body: ' . $result['body']);
    if ($instance_id && $instance_name) {
        $stmt = $conn->prepare("UPDATE configuracoes SET evolution_instance_id = ?, evolution_instance_name = ? WHERE id = 1");
        $stmt->execute([$instance_id, $instance_name]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao criar instância: ' . $result['body']]);
        exit;
    }
    $qrcode = $data['qrcode']['base64'] ?? '';
    echo json_encode([
        'success' => true,
        'qrcode' => $qrcode,
        'instance_id' => $instance_id,
        'instance_name' => $instance_name
    ]);
    exit;
}

// 3. Só consulta status se já existir instance_name
if ($action === 'status' && $instance_name) {
    $result = evolution_api('/instance/connectionState/' . $instance_name, 'GET');
    $data = json_decode($result['body'], true);
    $state = $data['instance']['state'] ?? 'unknown';
    echo json_encode([
        'success' => true,
        'status' => $state,
        'instance_id' => $instance_id,
        'instance_name' => $instance_name
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Ação inválida ou instância não criada']);