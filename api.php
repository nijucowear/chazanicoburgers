<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$business = isset($_GET['business']) ? $_GET['business'] : '';
if ($business !== 'chaza' && $business !== 'vaqui') {
    http_response_code(400);
    echo json_encode(['error' => 'Negocio no valido (chaza o vaqui)']);
    exit();
}

$file_name = "data_{$business}.json";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($file_name)) {
        echo file_get_contents($file_name);
    } else {
        echo json_encode(['menu' => null, 'salesHistory' => []]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents('php://input');
    
    // File locking para evitar problemas de concurrencia al guardar
    $fp = fopen($file_name, 'c+');
    if ($fp) {
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, $data);
            fflush($fp);
            flock($fp, LOCK_UN);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo bloquear el archivo']);
            fclose($fp);
            exit();
        }
        fclose($fp);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo abrir el archivo']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo no permitido']);
}
