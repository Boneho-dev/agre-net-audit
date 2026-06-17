<?php
declare(strict_types=1);
/**
 * Net Auditor IA — Historique des scores par hôte
 * Agre Agency — Production Build
 *
 * GET ?hostname=<nom_hote>
 * Retourne : { success: true, hostname: "...", data: [ {date_analyse, score_securite, ...}, ... ] }
 */

define('NET_AUDITOR_ACCESS', true);
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

// ─── Validation du paramètre ──────────────────────────────────────────────────
$hostname = trim((string)($_GET['hostname'] ?? ''));

if ($hostname === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parametre hostname manquant.', 'data' => []]);
    exit;
}

if (mb_strlen($hostname) > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Hostname invalide.', 'data' => []]);
    exit;
}

// ─── Requête historique ───────────────────────────────────────────────────────
try {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT
            created_at  AS date_analyse,
            score       AS score_securite,
            score_label,
            total,
            nb_critical,
            nb_warning,
            nb_info
        FROM  audits
        WHERE hostname = ?
        ORDER BY created_at ASC
        LIMIT 60
    ");
    $stmt->execute([$hostname]);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success'  => true,
        'hostname' => $hostname,
        'count'    => count($rows),
        'data'     => $rows,
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur base de donnees.',
        'data'  => [],
    ]);
}
exit;
