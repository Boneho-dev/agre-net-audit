<?php
declare(strict_types=1);
define('NET_AUDITOR_ACCESS', true);
require_once 'config.php';
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$name = trim(strip_tags((string)($_POST['username'] ?? '')));

if ($name === '' || mb_strlen($name) > 80) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nom invalide.']);
    exit;
}

$_SESSION['username'] = $name;
echo json_encode(['success' => true, 'username' => $name]);
exit;
