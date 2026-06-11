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

$key = trim((string)($_POST['key'] ?? ''));

if ($key === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Clé manquante.']);
    exit;
}

// Dérive les 4 premières lettres du nom d'exploitant (alpha uniquement, majuscules)
$username = (string)($_SESSION['username'] ?? '');
$letters  = preg_replace('/[^a-zA-Z]/u', '', $username);
$prefix   = strtoupper(mb_substr($letters, 0, 4));

if (mb_strlen($prefix) < 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Nom d\'exploitant invalide ou trop court (4 lettres minimum).']);
    exit;
}

$expectedKey = 'AGRE-' . $prefix . '2026';

if ($key === $expectedKey) {
    $_SESSION['is_premium'] = true;
    echo json_encode(['success' => true]);
} else {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Clé invalide.']);
}
exit;
