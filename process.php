<?php
declare(strict_types=1);
/**
 * Net Auditor IA — Moteur d'analyse & scoring déterministe
 * Agre Agency — Production Build
 *
 * Flux d'exécution :
 *   1.  Méthode HTTP POST requise
 *   2.  Détection de la langue (fr|en) depuis le paramètre POST
 *   3.  Validation du fichier (présence, erreur PHP, taille ≤ 500 Ko, extension .txt, non-binaire)
 *   4.  Lecture du contenu
 *   5.  Appel API avec prompt expert + directive linguistique
 *   6.  Scoring déterministe PHP (override total de la valeur IA)
 *   7.  Injection de la date serveur réelle (override de la date IA)
 *   8.  Réponse JSON finale
 */

define('NET_AUDITOR_ACCESS', true);
require_once 'config.php';
require_once 'db.php';
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

// ─── 1. Méthode HTTP ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.', 'code' => 'METHOD_NOT_ALLOWED']);
    exit;
}

// ─── 1b. Vérification quota gratuit ──────────────────────────────────────────
if (!($_SESSION['is_premium'] ?? false)) {
    $auditCount = (int)($_SESSION['audit_count'] ?? 0);
    if ($auditCount >= 2) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Limite d\'essais gratuits atteinte. Veuillez activer la version Premium.',
            'code'  => 'TRIAL_LIMIT',
        ]);
        exit;
    }
}

// ─── 2. Langue ────────────────────────────────────────────────────────────────
$lang = match (strtolower(trim((string) ($_POST['lang'] ?? 'fr')))) {
    'en'    => 'en',
    default => 'fr',
};

// ─── 3. Présence & erreur upload PHP ─────────────────────────────────────────
if (!isset($_FILES['configFile']) || $_FILES['configFile']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (limite php.ini).',
        UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux (limite formulaire).',
        UPLOAD_ERR_PARTIAL    => 'Upload incomplet. Réessayez.',
        UPLOAD_ERR_NO_FILE    => 'Aucun fichier sélectionné.',
        UPLOAD_ERR_NO_TMP_DIR => 'Répertoire temporaire manquant.',
        UPLOAD_ERR_CANT_WRITE => 'Échec d\'écriture disque.',
        UPLOAD_ERR_EXTENSION  => 'Upload bloqué par une extension PHP.',
    ];
    $errCode = (int) ($_FILES['configFile']['error'] ?? UPLOAD_ERR_NO_FILE);
    http_response_code(400);
    echo json_encode([
        'error' => $uploadErrors[$errCode] ?? 'Erreur upload inconnue.',
        'code'  => 'UPLOAD_ERROR',
    ]);
    exit;
}

$file = $_FILES['configFile'];

// ─── 4. Taille ≤ 500 Ko ───────────────────────────────────────────────────────
if ((int) $file['size'] > MAX_FILE_SIZE) {
    http_response_code(413);
    echo json_encode([
        'error' => sprintf('Fichier trop volumineux : %s Ko reçus (limite : 500 Ko).', round((int) $file['size'] / 1024, 1)),
        'code'  => 'FILE_TOO_LARGE',
    ]);
    exit;
}

// ─── 5. Extension .txt ────────────────────────────────────────────────────────
$ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
    http_response_code(415);
    echo json_encode([
        'error' => 'Format non supporté. Seuls les fichiers .txt sont acceptés.',
        'code'  => 'INVALID_EXTENSION',
    ]);
    exit;
}

// ─── 5b. Validation MIME via finfo ───────────────────────────────────────────
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file((string) $file['tmp_name']);
if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
    http_response_code(415);
    echo json_encode([
        'error' => 'Type MIME invalide détecté. Seuls les fichiers texte brut (text/plain) sont acceptés.',
        'code'  => 'INVALID_MIME',
    ]);
    exit;
}

// ─── 6. Détection binaire ─────────────────────────────────────────────────────
$sample = file_get_contents((string) $file['tmp_name'], false, null, 0, 512);
if ($sample === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de lire le fichier.', 'code' => 'READ_ERROR']);
    exit;
}
$nonPrintableRatio = preg_match_all('/[^\x09\x0A\x0D\x20-\x7E]/', $sample) / max(strlen($sample), 1);
if (substr_count($sample, "\x00") > 0 || $nonPrintableRatio > 0.30) {
    http_response_code(415);
    echo json_encode(['error' => 'Fichier binaire détecté. Fournissez un fichier texte de configuration réseau (.txt) — Cisco, Juniper, Fortinet ou Huawei.', 'code' => 'NOT_PLAIN_TEXT']);
    exit;
}

// ─── 7. Lecture du contenu ────────────────────────────────────────────────────
$fileContent = file_get_contents((string) $file['tmp_name']);
if ($fileContent === false || trim($fileContent) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Fichier vide ou illisible.', 'code' => 'EMPTY_FILE']);
    exit;
}

// ─── 8. Répertoire uploads ────────────────────────────────────────────────────
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0750, true);
    file_put_contents(UPLOAD_DIR . '.htaccess', "Options -Indexes\nDeny from all\n");
}
$savedPath = UPLOAD_DIR . bin2hex(random_bytes(16)) . '.txt';
move_uploaded_file((string) $file['tmp_name'], $savedPath);

// ─── 9. Appel API ─────────────────────────────────────────────────────────────
$auditResult = callAnalysisApi($fileContent, (string) $file['name'], (int) $file['size'], $lang);

if (isset($auditResult['api_error'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Erreur du moteur d\'analyse : ' . $auditResult['api_error'], 'code' => 'API_ERROR']);
    exit;
}

// ─── 10. Scoring déterministe PHP (override total ─ l'IA ne calcule pas le score) ──
$scoring = computeSecurityScore($auditResult['vulnerabilities'] ?? []);

$auditResult['security_score'] = $scoring['score'];
$auditResult['score_label']    = $scoring['label'];

// Override des métadonnées avec les valeurs réelles du serveur
$auditResult['scan_metadata'] = array_merge(
    (array) ($auditResult['scan_metadata'] ?? []),
    [
        'scan_date'       => date('d/m/Y H:i:s'),   // Date PHP réelle — jamais celle de l'IA
        'file_analyzed'   => htmlspecialchars((string) $file['name'], ENT_QUOTES, 'UTF-8'),
        'file_size_bytes' => (int) $file['size'],
        'total_findings'  => count($auditResult['vulnerabilities'] ?? []),
        'critical_count'  => $scoring['counts']['CRITICAL'],
        'warning_count'   => $scoring['counts']['WARNING'],
        'info_count'      => $scoring['counts']['INFO'],
    ]
);

// ─── 11. Mise en session pour l'export PDF ───────────────────────────────────
$_SESSION['last_audit'] = $auditResult;

// ─── 11b. Incrément du compteur d'essais ─────────────────────────────────────
if (!($_SESSION['is_premium'] ?? false)) {
    $_SESSION['audit_count'] = (int)($_SESSION['audit_count'] ?? 0) + 1;
}

// ─── 12. Sauvegarde en base de données ───────────────────────────────────────
$auditId = null;
try {
    $db   = getDB();
    $meta = $auditResult['scan_metadata'] ?? [];

    $stmtAudit = $db->prepare("
        INSERT INTO audits
          (username, filename, hostname, score, score_label, total, nb_critical, nb_warning, nb_info)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtAudit->execute([
        (string) ($_SESSION['username']         ?? ''),
        htmlspecialchars((string) $file['name'], ENT_QUOTES, 'UTF-8'),
        (string) ($meta['hostname']             ?? 'Unknown'),
        (int)    $auditResult['security_score'],
        (string) $auditResult['score_label'],
        (int)    ($meta['total_findings']       ?? 0),
        (int)    ($meta['critical_count']       ?? 0),
        (int)    ($meta['warning_count']        ?? 0),
        (int)    ($meta['info_count']           ?? 0),
    ]);
    $auditId = (int) $db->lastInsertId();

    $stmtFinding = $db->prepare("
        INSERT INTO findings
          (audit_id, vuln_id, title, severity, affected_line, description, impact, remediation)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($auditResult['vulnerabilities'] ?? [] as $v) {
        $stmtFinding->execute([
            $auditId,
            (string) ($v['vuln_id']       ?? ''),
            (string) ($v['title']         ?? ''),
            (string) ($v['severity']      ?? 'INFO'),
            (string) ($v['affected_line'] ?? ''),
            (string) ($v['description']   ?? ''),
            (string) ($v['impact']        ?? ''),
            (string) ($v['remediation']   ?? ''),
        ]);
    }
} catch (\PDOException $e) {
    error_log('[NetAuditor] DB save failed: ' . $e->getMessage());
}

// ─── 13. Réponse finale ───────────────────────────────────────────────────────
echo json_encode([
    'success'     => true,
    'filename'    => htmlspecialchars((string) $file['name'], ENT_QUOTES, 'UTF-8'),
    'filesize'    => (int) $file['size'],
    'audit'       => $auditResult,
    'audit_id'    => $auditId,
    'trial_count' => (int)($_SESSION['audit_count'] ?? 0),
    'is_premium'  => (bool)($_SESSION['is_premium']  ?? false),
]);
exit;


// =============================================================================
// FUNCTION : computeSecurityScore
// Algorithme pondéré proportionnel — le score ne peut atteindre 0 que si
// la totalité des findings est CRITICAL, ce qui évite l'effondrement brutal.
//
// Poids par sévérité : CRITICAL = 10  |  WARNING = 5  |  INFO = 1
//
// Formule :
//   somme_poids  = Σ poids(sévérité_i)
//   score = 100 − round( (somme_poids / (nb_findings × 10)) × 100 )
//   Résultat borné entre 0 et 100.
//
// Exemples pour 9 findings (3C + 3W + 3I) :
//   somme = 30+15+3 = 48  |  dénominateur = 90  |  score = 100−53 = 47
// Exemples pour 3 findings tous CRITICAL :
//   somme = 30  |  dénominateur = 30  |  score = 100−100 = 0
// Exemples pour 3 findings tous INFO :
//   somme = 3   |  dénominateur = 30  |  score = 100−10 = 90
// =============================================================================
function computeSecurityScore(array $vulnerabilities): array
{
    $counts = ['CRITICAL' => 0, 'WARNING' => 0, 'INFO' => 0];
    $weights = ['CRITICAL' => 10, 'WARNING' => 5, 'INFO' => 1];

    $sumWeights  = 0;
    $totalFindings = count($vulnerabilities);

    foreach ($vulnerabilities as $vuln) {
        $sev = strtoupper(trim((string) ($vuln['severity'] ?? 'INFO')));
        if (!array_key_exists($sev, $counts)) {
            $sev = 'INFO';
        }
        $counts[$sev]++;
        $sumWeights += $weights[$sev];
    }

    // Division par zéro impossible : score parfait si aucune faille
    if ($totalFindings === 0) {
        $score = 100;
    } else {
        // Le dénominateur représente le pire cas possible (tout CRITICAL)
        $worstCase = $totalFindings * $weights['CRITICAL'];
        $score = (int) round(100 - ($sumWeights / $worstCase) * 100);
        $score = max(0, min(100, $score));
    }

    $label = match (true) {
        $score >= 81 => 'SECURE',
        $score >= 61 => 'LOW_RISK',
        $score >= 41 => 'MODERATE_RISK',
        $score >= 21 => 'HIGH_RISK',
        default      => 'CRITICAL_RISK',
    };

    return compact('score', 'label', 'counts');
}


// =============================================================================
// FUNCTION : callAnalysisApi
// Envoi au moteur d'analyse — retourne le JSON brut (sans score ni date).
// Le scoring et la date sont gérés exclusivement par computeSecurityScore() et PHP.
// Supporte : Cisco IOS/IOS-XE | Juniper Junos | Fortinet FortiOS | Huawei VRP
// =============================================================================
function callAnalysisApi(string $configContent, string $originalFilename, int $fileSize, string $lang): array
{
    // ── Directive linguistique (injectée en tête du system prompt) ─────────────
    $langDirective = $lang === 'en'
        ? "MANDATORY LANGUAGE DIRECTIVE: Write ALL text content (title, description, impact, remediation fields) EXCLUSIVELY in English. This is non-negotiable."
        : "DIRECTIVE LINGUISTIQUE ABSOLUE : Rédigez TOUS les contenus textuels (title, description, impact, remediation) EXCLUSIVEMENT en français. C'est non négociable.";

    // ── System Prompt multi-constructeurs ──────────────────────────────────────
    $systemPrompt = $langDirective . "\n\n" . <<<'PROMPT'
You are an elite multi-vendor network security auditor with 15+ years of field experience covering Cisco IOS/IOS-XE, Juniper Junos, Fortinet FortiOS, and Huawei VRP. Your expertise spans CIS Benchmarks L1/L2, NIST SP 800-115, NSA Router/Switch Security Guides, PCI-DSS, and all vendor-specific hardening guides.

YOUR SOLE OUTPUT IS A SINGLE VALID JSON OBJECT. No markdown, no commentary, no preamble — pure JSON only.
DO NOT include security_score, score_label, or scan_date in your response. These fields are computed server-side and will be overridden.

════════════════════════════════════════════════════════════════════
STEP 1 — MANDATORY VENDOR DETECTION (execute before any analysis)
════════════════════════════════════════════════════════════════════
Scan the first 40 lines of the configuration file and identify the vendor using these signatures:

CISCO IOS / IOS-XE
  Signatures: flat command syntax | lines starting with "version 12/15/16/17" | "no ip domain-lookup" |
              "interface GigabitEthernet" | "access-list" | "ip route" | "enable secret" | "line vty"
  Hostname:   "hostname <name>"

JUNIPER JUNOS
  Signatures: hierarchical brace blocks ("system {", "interfaces {", "security {") OR
              set-style ("set system host-name", "set interfaces") | comment lines starting "## " |
              version strings like "version 20.4R3" or "version 22.2"
  Hostname:   "set system host-name <name>" or "host-name <name>;" inside system { block }

FORTINET FORTIIOS
  Signatures: "config system global" | "config system interface" | "config firewall policy" |
              "set hostname" inside config blocks | "end" as block terminator | FortiGate/FortiOS strings
  Hostname:   "set hostname <name>" inside "config system global"

HUAWEI VRP
  Signatures: "#" as section separator lines | "sysname <name>" | "interface GigabitEthernet0/0/0" (triple-zero) |
              "user-interface vty" | "local-user" | "aaa" | "authentication-mode"
  Hostname:   "sysname <name>"

DEFAULT: If no clear signature matches, treat as Cisco IOS.

════════════════════════════════════════════════════════════════════
STEP 2 — VENDOR-SPECIFIC SEVERITY GRID
════════════════════════════════════════════════════════════════════
Apply ONLY the rules matching the vendor detected in Step 1. Do not mix rules from different vendors.

┌─────────────────────────────────────────────────────────────────┐
│ CISCO IOS / IOS-XE                                              │
└─────────────────────────────────────────────────────────────────┘
CRITICAL:
  • Cleartext or Type-7 password ("password <plain>" | "password 7 <hash>" — Type-7 is trivially reversible)
  • Telnet on VTY ("transport input telnet" | "transport input all")
  • HTTP without HTTPS ("ip http server" present, "ip http secure-server" absent)
  • Missing enable secret (only "enable password" or neither present)
  • WAN/external interface with no inbound ACL applied
WARNING:
  • SSH v2 not enforced ("ip ssh version 2" absent)
  • VTY lines missing "access-class" ACL restriction
  • No exec-timeout on VTY or console lines
  • SNMP default community strings ("public" | "private" | "cisco")
INFO:
  • No login/MOTD/exec banner configured
  • No syslog server ("logging host" absent)
  • No NTP server ("ntp server" absent)
  • DNS lookup not disabled ("no ip domain-lookup" absent)
  • Proxy-ARP active on interfaces
  • TCP/UDP small servers not explicitly disabled

┌─────────────────────────────────────────────────────────────────┐
│ JUNIPER JUNOS                                                   │
└─────────────────────────────────────────────────────────────────┘
CRITICAL:
  • Telnet service enabled ("set system services telnet" or "telnet;" inside services block)
  • Root authentication missing or using plain password (not $9$ Juniper hash format)
  • Super-user login class with no access restrictions
  • FTP service enabled ("set system services ftp")
WARNING:
  • SSH not restricted to v2 only ("set system services ssh protocol-version v2" absent)
  • SNMP community "public" or "private" configured
  • No connection-limit or rate-limit on SSH
  • No idle-timeout on interactive sessions
INFO:
  • No syslog host configured ("set system syslog host" absent)
  • No NTP server ("set system ntp server" absent)
  • No login banner ("set system login message" absent)
  • NETCONF/REST API enabled without source address restriction

┌─────────────────────────────────────────────────────────────────┐
│ FORTINET FORTIIOS                                               │
└─────────────────────────────────────────────────────────────────┘
CRITICAL:
  • Admin account with empty or default password ("set password" empty or default ENC string)
  • Telnet management enabled ("set admin-telnet enable")
  • HTTP management enabled without HTTPS-only restriction ("set admin-server-cert" / allow-access includes http)
  • Admin trusted-hosts not configured ("set trusthost1" absent — exposes GUI/SSH to any IP)
WARNING:
  • Admin session timeout too long or absent ("set admintimeout" > 15 min or absent)
  • SNMP v1/v2c with "public" or "private" community
  • SSH enabled on interface without source IP restriction
  • Minimum password length below 8 characters
INFO:
  • No remote syslog server ("config log syslogd setting" absent or disabled)
  • No NTP server ("config system ntp" absent or empty)
  • No admin login disclaimer banner ("set admin-disclaimer" absent)
  • No DNS server configured

┌─────────────────────────────────────────────────────────────────┐
│ HUAWEI VRP                                                      │
└─────────────────────────────────────────────────────────────────┘
CRITICAL:
  • User password in plain text or simple cipher ("password simple" — use "password irreversible-cipher")
  • Telnet enabled on VTY ("protocol inbound telnet" or "protocol inbound all")
  • HTTP server active without HTTPS ("ip http enable" without "ip https enable")
  • Console line with authentication disabled ("authentication-mode none" on console 0)
WARNING:
  • SSH v1 allowed ("ssh version 2" not enforced via "stelnet server enable" only)
  • SNMP v1/v2c with default communities ("public" | "private")
  • No idle-timeout on VTY lines ("idle-timeout 0 0" or absent)
  • No privilege-level separation between operator and admin users
INFO:
  • No syslog host ("info-center loghost" absent)
  • No NTP ("ntp-service unicast-server" absent)
  • No login banner ("header shell" or "header login" absent)
  • No DNS server ("ip dns-server" absent)

════════════════════════════════════════════════════════════════════
STEP 3 — REMEDIATION CLI SYNTAX (use ONLY the detected vendor's syntax)
════════════════════════════════════════════════════════════════════
CISCO IOS — flat imperative syntax, configure terminal / end / write memory:
  configure terminal
   [commands]
  end
  write memory

JUNIPER JUNOS — edit + set/delete statements + commit:
  edit
  set|delete [hierarchy path] [value]
  commit

FORTINET FORTIIOS — config <object> / set <key> <value> / end:
  config [object-path]
      set [key] [value]
  end

HUAWEI VRP — system-view + commands + return + save:
  system-view
   [commands]
  return
  save

════════════════════════════════════════════════════════════════════
STEP 4 — MANDATORY JSON OUTPUT FORMAT (identical structure, always)
════════════════════════════════════════════════════════════════════
Return EXACTLY this JSON structure. Do not add or remove root-level keys.
The "vendor" and "os_version" fields in scan_metadata are informational extras.

{
  "scan_metadata": {
    "hostname": "<hostname extracted from config or 'Unknown'>",
    "vendor": "<Cisco IOS | Cisco IOS-XE | Juniper Junos | Fortinet FortiOS | Huawei VRP>",
    "os_version": "<version string detected or 'Unknown'>"
  },
  "vulnerabilities": [
    {
      "vuln_id": "VULN-001",
      "title": "<actionable title, max 60 chars>",
      "severity": "<CRITICAL|WARNING|INFO>",
      "affected_line": "<exact config line(s) triggering the finding>",
      "description": "<technical explanation, 1-2 sentences>",
      "impact": "<concrete real-world risk>",
      "remediation": "<exact CLI commands in the detected vendor syntax>"
    }
  ]
}
PROMPT;

    // ── Message utilisateur ────────────────────────────────────────────────────
    $userMessage = sprintf(
        "Audit this network device configuration.\nFilename: %s | Size: %d bytes\n\n" .
        "=== CONFIGURATION START ===\n%s\n=== CONFIGURATION END ===\n\n" .
        "First detect the vendor (Step 1), then apply its specific security rules (Step 2), " .
        "generate remediations in its exact CLI syntax (Step 3), and return ONLY a raw JSON object (Step 4). " .
        "No markdown fences, no introduction, no conclusion. Response MUST start with { and end with }.",
        $originalFilename,
        $fileSize,
        $configContent
    );

    // ── Payload ────────────────────────────────────────────────────────────────
    $payload = [
        'model'      => CLAUDE_MODEL,
        'max_tokens' => CLAUDE_MAX_TOKENS,
        'system'     => $systemPrompt,
        'messages'   => [
            ['role' => 'user', 'content' => $userMessage],
        ],
    ];

    // ── cURL ───────────────────────────────────────────────────────────────────
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => API_TIMEOUT,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: '        . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError   = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['api_error' => 'Réseau : ' . $curlError];
    }

    $apiResponse = json_decode($rawResponse, true);

    if ($httpCode !== 200) {
        return ['api_error' => $apiResponse['error']['message'] ?? ('HTTP ' . $httpCode)];
    }

    // ── Extraction & nettoyage défensif ────────────────────────────────────────
    $rawText    = (string) ($apiResponse['content'][0]['text'] ?? '');
    $jsonString = trim($rawText);

    // Supprime les fences Markdown si présentes
    $jsonString = preg_replace('/^```(?:json)?\s*/i', '', $jsonString);
    $jsonString = preg_replace('/```\s*$/m',          '', $jsonString);
    $jsonString = trim($jsonString);

    // Extrait uniquement la portion entre le premier '{' et le dernier '}'
    $start = strpos($jsonString, '{');
    $end   = strrpos($jsonString, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $jsonString = substr($jsonString, $start, $end - $start + 1);
    }

    $parsed = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['api_error' => 'Réponse JSON invalide : ' . json_last_error_msg()];
    }

    return $parsed;
}
