<?php
declare(strict_types=1);
/**
 * Net Auditor IA — Générateur de rapport PDF
 * Agre Agency — Production Build
 *
 * Dépendance : lib/fpdf.php (FPDF v1.86 — https://www.fpdf.org)
 * Données     : $_SESSION['last_audit'] posé par process.php après chaque analyse
 */

define('NET_AUDITOR_ACCESS', true);
require_once 'config.php';
session_start();

if (empty($_SESSION['last_audit'])) {
    header('Location: index.php');
    exit;
}

if (!file_exists(__DIR__ . '/lib/fpdf.php')) {
    http_response_code(500);
    echo '<p style="font-family:monospace;padding:2rem;color:#dc2626;">
            <strong>Erreur : librairie FPDF manquante.</strong><br>
            Téléchargez <code>fpdf.php</code> sur
            <a href="https://www.fpdf.org" target="_blank">fpdf.org</a>
            et placez-le dans <code>lib/fpdf.php</code>.
          </p>';
    exit;
}

require_once __DIR__ . '/lib/fpdf.php';

// ─── Sortie propre : vide tout buffer avant que FPDF envoie ses headers ──────
ob_start();

// ─── Capture les exceptions FPDF non catchées (erreurs de génération) ─────────
set_exception_handler(static function (\Throwable $e): void {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    echo '<p style="font-family:monospace;padding:2rem;background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;border-radius:8px;">'
        . '<strong>Erreur lors de la génération du PDF :</strong><br>'
        . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        . '</p>';
});

// ─── Données de l'audit ───────────────────────────────────────────────────────
$audit    = $_SESSION['last_audit'];
$username = (string)($_SESSION['username']  ?? 'Inconnu');
$meta     = $audit['scan_metadata']         ?? [];
$vulns    = $audit['vulnerabilities']       ?? [];
$score    = (int)($audit['security_score']  ?? 0);
$label    = (string)($audit['score_label']  ?? 'CRITICAL_RISK');
$hostname  = (string)($meta['hostname']      ?? 'Unknown');
$filename  = (string)($meta['file_analyzed'] ?? '');
$scanDate  = (string)($meta['scan_date']     ?? date('d/m/Y H:i:s'));
$total     = (int)($meta['total_findings']   ?? count($vulns));
$nbCrit    = (int)($meta['critical_count']   ?? 0);
$nbWarn    = (int)($meta['warning_count']    ?? 0);
$nbInfo    = (int)($meta['info_count']       ?? 0);
$vendor    = trim((string)($meta['vendor']    ?? '')) ?: 'Inconnu';
$osVersion = trim((string)($meta['os_version'] ?? '')) ?: 'Inconnu';

// Tri CRITICAL > WARNING > INFO
usort($vulns, static function (array $a, array $b): int {
    $order = ['CRITICAL' => 0, 'WARNING' => 1, 'INFO' => 2];
    return ($order[strtoupper($a['severity'] ?? 'INFO')] ?? 9)
         - ($order[strtoupper($b['severity'] ?? 'INFO')] ?? 9);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

/** Convertit UTF-8 → ISO-8859-1 (FPDF ne supporte pas nativement UTF-8) */
function u(string $str): string
{
    return (string) iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $str);
}

/** RGB du score global */
function scoreRgb(int $score): array
{
    return match (true) {
        $score >= 81 => [34,  197, 94],
        $score >= 61 => [132, 204, 22],
        $score >= 41 => [245, 158, 11],
        $score >= 21 => [249, 115, 22],
        default      => [239,  68, 68],
    };
}

/** Libellé du score en français */
function scoreLbl(string $label): string
{
    return match ($label) {
        'SECURE'        => 'SECURISE',
        'LOW_RISK'      => 'RISQUE FAIBLE',
        'MODERATE_RISK' => 'RISQUE MODERE',
        'HIGH_RISK'     => 'RISQUE ELEVE',
        'CRITICAL_RISK' => 'RISQUE CRITIQUE',
        default         => $label,
    };
}

/**
 * Couleurs par sévérité : [bg_rgb, accent_rgb, text_rgb]
 * @return array{0:array, 1:array, 2:array}
 */
function sevPalette(string $sev): array
{
    return match ($sev) {
        'CRITICAL' => [[255,241,242], [220, 38, 38], [159, 18, 57]],
        'WARNING'  => [[255,251,235], [217,119,  6], [146, 64, 14]],
        default    => [[248,250,252], [100,116,139], [ 71, 85,105]],
    };
}

// ─── Classe PDF personnalisée ──────────────────────────────────────────────────
class NetAuditorPDF extends FPDF
{
    public string $operator = '';
    public string $genDate  = '';

    public function Header(): void
    {
        // Bandeau dark-navy
        $this->SetFillColor(15, 23, 42);
        $this->Rect(0, 0, 210, 27, 'F');
        // Liseré cyan
        $this->SetFillColor(56, 189, 248);
        $this->Rect(0, 26.5, 210, 0.7, 'F');

        // Titre (gauche)
        $this->SetXY(15, 6.5);
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(241, 245, 249);
        $this->Cell(100, 6, 'NET AUDITOR IA', 0, 0, 'L');
        // Date générée (droite)
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(80, 6, u($this->genDate), 0, 1, 'R');

        // Sous-titre (gauche)
        $this->SetX(15);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(100, 5, "Rapport d'Audit de Securite Reseau | Agre Agency", 0, 0, 'L');
        // Opérateur (droite)
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetTextColor(56, 189, 248);
        $this->Cell(80, 5, u('Operateur : ' . $this->operator), 0, 1, 'R');

        $this->SetY(31);
    }

    public function Footer(): void
    {
        $y = $this->GetPageHeight() - 11;
        $this->SetY($y);
        $this->SetFillColor(15, 23, 42);
        $this->Rect(0, $y, 210, 11, 'F');
        $this->SetFillColor(56, 189, 248);
        $this->Rect(0, $y, 210, 0.6, 'F');

        $this->SetFont('Helvetica', '', 6.5);
        $this->SetTextColor(71, 85, 105);
        $this->SetX(15);
        $this->Cell(130, 8, u('Net Auditor IA v' . APP_VERSION . ' — Agre Agency — Document confidentiel'), 0, 0, 'L');
        $this->Cell(50, 8, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
}

// ─── Init PDF ─────────────────────────────────────────────────────────────────
$pdf           = new NetAuditorPDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->operator = $username;
$pdf->genDate  = 'Genere le ' . date('d/m/Y a H:i');
$pdf->SetMargins(15, 31, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// ─── Helper : en-tête de section ──────────────────────────────────────────────
$sectionTitle = static function (string $text) use ($pdf): void {
    $pdf->SetFont('Helvetica', 'B', 6.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetX(15);
    $tw = $pdf->GetStringWidth($text) + 2;
    $pdf->Cell($tw, 5, u($text), 0, 0, 'L');
    $pdf->SetDrawColor(226, 232, 240);
    $lx = 15 + $tw + 2;
    $y  = $pdf->GetY() + 2.5;
    $pdf->Line($lx, $y, 195, $y);
    $pdf->Ln(8);
};

// ─── Helper : champ label + valeur simple ─────────────────────────────────────
$metaField = static function (float $x, float $y, float $w, string $label, string $value) use ($pdf): void {
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->Rect($x, $y, $w, 13, 'DF');

    $pdf->SetXY($x + 3, $y + 2);
    $pdf->SetFont('Helvetica', 'B', 5.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell($w - 6, 4, u($label), 0, 0, 'L');

    $pdf->SetXY($x + 3, $y + 6.5);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell($w - 6, 5, u(mb_strimwidth($value, 0, 45, '...')), 0, 0, 'L');
};


// =============================================================================
// SECTION 1 — RÉSUMÉ EXÉCUTIF
// =============================================================================
$sectionTitle('RESUME EXECUTIF');

$sc  = scoreRgb($score);
$y0  = (float)$pdf->GetY();
$rH  = 40.0;

// ── Carte Score (x=15, w=55) ──────────────────────────────────────────────────
$pdf->SetFillColor(248, 250, 252);
$pdf->SetDrawColor(226, 232, 240);
$pdf->Rect(15, $y0, 55, $rH, 'DF');
// Top accent band
$pdf->SetFillColor($sc[0], $sc[1], $sc[2]);
$pdf->Rect(15, $y0, 55, 2.5, 'F');

// Label
$pdf->SetXY(15, $y0 + 4.5);
$pdf->SetFont('Helvetica', 'B', 5.5);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(55, 4, 'SCORE DE SECURITE', 0, 0, 'C');

// Chiffre
$pdf->SetXY(15, $y0 + 9);
$pdf->SetFont('Helvetica', 'B', 22);
$pdf->SetTextColor($sc[0], $sc[1], $sc[2]);
$pdf->Cell(55, 12, (string)$score, 0, 0, 'C');

// /100
$pdf->SetXY(15, $y0 + 21);
$pdf->SetFont('Helvetica', '', 6.5);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(55, 4, '/100', 0, 0, 'C');

// Libellé score
$pdf->SetXY(15, $y0 + 26);
$pdf->SetFont('Helvetica', 'B', 6.5);
$pdf->SetTextColor($sc[0], $sc[1], $sc[2]);
$pdf->Cell(55, 4, u(scoreLbl($label)), 0, 0, 'C');

// Barre de progression
$bx = 20.0; $by = (float)($y0 + 32.5); $bw = 45.0; $bh = 3.0;
$pdf->SetFillColor(226, 232, 240);
$pdf->Rect($bx, $by, $bw, $bh, 'F');
$pdf->SetFillColor($sc[0], $sc[1], $sc[2]);
$filled = max(1.0, round($bw * $score / 100, 1));
$pdf->Rect($bx, $by, $filled, $bh, 'F');

// ── Cartes Sévérité (3 cartes x=74, 115, 156 — w=37) ─────────────────────────
$sevCards = [
    ['CRITIQUE',      $nbCrit, [254,226,226], [220, 38, 38],  74.0],
    ['AVERTISSEMENT', $nbWarn, [255,251,235], [217,119,  6], 115.0],
    ['INFORMATION',   $nbInfo, [248,250,252], [100,116,139], 156.0],
];
$totalVulns = max(1, $nbCrit + $nbWarn + $nbInfo);

foreach ($sevCards as [$cl, $cnt, $cbg, $cacc, $cx]) {
    $cw = 37.0;
    $pdf->SetFillColor($cbg[0], $cbg[1], $cbg[2]);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->Rect($cx, $y0, $cw, $rH, 'DF');
    $pdf->SetFillColor($cacc[0], $cacc[1], $cacc[2]);
    $pdf->Rect($cx, $y0, $cw, 2.5, 'F');

    $pdf->SetXY($cx, $y0 + 4.5);
    $pdf->SetFont('Helvetica', 'B', 5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell($cw, 4, u($cl), 0, 0, 'C');

    $pdf->SetXY($cx, $y0 + 9);
    $pdf->SetFont('Helvetica', 'B', 20);
    $pdf->SetTextColor($cacc[0], $cacc[1], $cacc[2]);
    $pdf->Cell($cw, 11, (string)$cnt, 0, 0, 'C');

    $pdf->SetXY($cx, $y0 + 21.5);
    $pdf->SetFont('Helvetica', '', 5.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell($cw, 4, $cnt . ' / ' . $total . ' findings', 0, 0, 'C');

    // Mini barre de proportion
    $mbx = $cx + 5; $mby = (float)($y0 + 28); $mbw = $cw - 10; $mbh = 2.5;
    $pdf->SetFillColor(226, 232, 240);
    $pdf->Rect($mbx, $mby, $mbw, $mbh, 'F');
    $pdf->SetFillColor($cacc[0], $cacc[1], $cacc[2]);
    $pct = $cnt / $totalVulns;
    $pdf->Rect($mbx, $mby, max(1.0, round($mbw * $pct, 1)), $mbh, 'F');

    $pdf->SetXY($cx, $y0 + 33.5);
    $pdf->SetFont('Helvetica', '', 5);
    $pdf->SetTextColor(100, 116, 139);
    $pctStr = $total > 0 ? round($cnt / $total * 100) . '%' : '0%';
    $pdf->Cell($cw, 4, $pctStr . ' des alertes', 0, 0, 'C');
}

$pdf->SetY($y0 + $rH + 7);


// =============================================================================
// SECTION 2 — INFORMATIONS DE L'AUDIT
// =============================================================================
$sectionTitle("INFORMATIONS DE L'AUDIT");

$y1 = (float)$pdf->GetY();
$metaField(15,  $y1, 85, 'HOTE ANALYSE',        $hostname);
$metaField(105, $y1, 90, 'FICHIER SOURCE',       $filename);
$y2 = $y1 + 15;
$metaField(15,  $y2, 85, "DATE D'ANALYSE",       $scanDate);
$metaField(105, $y2, 90, 'MOTEUR D\'ANALYSE',    'Claude AI | ' . CLAUDE_MODEL);
$y3 = $y2 + 15;
$metaField(15,  $y3, 85, 'CONSTRUCTEUR DETECTE', $vendor);
$metaField(105, $y3, 90, 'VERSION OS',            $osVersion);

$pdf->SetY($y3 + 20);


// =============================================================================
// SECTION 3 — DÉTAIL DES VULNÉRABILITÉS
// =============================================================================
$sectionTitle('DETAIL DES VULNERABILITES (' . $total . ' finding' . ($total > 1 ? 's' : '') . ' detecte' . ($total > 1 ? 's' : '') . ')');

if (empty($vulns)) {
    $yOk = (float)$pdf->GetY();
    $pdf->SetFillColor(240, 253, 244);
    $pdf->SetDrawColor(134, 239, 172);
    $pdf->Rect(15, $yOk, 180, 14, 'DF');
    $pdf->SetXY(15, $yOk + 4);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(22, 163, 74);
    $pdf->Cell(180, 6, u('Aucune vulnerabilite detectee — Configuration conforme aux bonnes pratiques.'), 0, 0, 'C');
} else {
    foreach ($vulns as $i => $v) {
        $sev   = strtoupper(trim((string)($v['severity'] ?? 'INFO')));
        if (!in_array($sev, ['CRITICAL','WARNING','INFO'], true)) $sev = 'INFO';
        [$bg, $acc, $txt] = sevPalette($sev);

        $vid   = (string)($v['vuln_id']       ?? 'VULN-' . str_pad((string)($i+1), 3, '0', STR_PAD_LEFT));
        $title = (string)($v['title']         ?? '');
        $aline = (string)($v['affected_line'] ?? '');
        $desc  = (string)($v['description']   ?? '');
        $imp   = (string)($v['impact']        ?? '');
        $rem   = (string)($v['remediation']   ?? '');

        // ── Vérification espace disponible ───────────────────────────────────
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
        }

        // ── Bandeau coloré (titre du finding) ────────────────────────────────
        $banY = (float)$pdf->GetY();
        $pdf->SetFillColor($acc[0], $acc[1], $acc[2]);
        $pdf->Rect(15, $banY, 180, 8.5, 'F');

        // Badge sévérité dans le bandeau
        $badgeW = 18.0;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(18, $banY + 1.5, $badgeW, 5.5, 'F');
        $pdf->SetXY(18, $banY + 2.5);
        $pdf->SetFont('Helvetica', 'B', 6);
        $pdf->SetTextColor($txt[0], $txt[1], $txt[2]);
        $pdf->Cell($badgeW, 3.5, u($sev), 0, 0, 'C');

        // ID + titre dans le bandeau
        $pdf->SetXY(39, $banY + 1.8);
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(20, 5, u($vid), 0, 0, 'L');

        $pdf->SetXY(57, $banY + 1.8);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(133, 5, u(mb_strimwidth($title, 0, 65, '...')), 0, 0, 'L');

        $pdf->SetY($banY + 10);

        // ── Ligne affectée ────────────────────────────────────────────────────
        if ($aline !== '') {
            $pdf->SetX(15);
            $pdf->SetFont('Helvetica', 'B', 5.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(180, 4.5, u('LIGNE DE CONFIGURATION AFFECTEE'), 0, 1, 'L');

            $pdf->SetFillColor(255, 251, 235);
            $pdf->SetDrawColor(253, 230, 138);
            $pdf->Rect(15, (float)$pdf->GetY(), 180, 7.5, 'DF');
            $pdf->SetXY(18, (float)$pdf->GetY() + 1.5);
            $pdf->SetFont('Courier', '', 7.5);
            $pdf->SetTextColor(146, 64, 14);
            $pdf->Cell(174, 5, u(mb_strimwidth($aline, 0, 95, '...')), 0, 1, 'L');
            $pdf->Ln(2);
        }

        // ── Description ───────────────────────────────────────────────────────
        if ($desc !== '') {
            $pdf->SetX(15);
            $pdf->SetFont('Helvetica', 'B', 5.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(180, 4.5, u('DESCRIPTION'), 0, 1, 'L');

            $pdf->SetX(15);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->MultiCell(180, 4.5, u($desc), 0, 'L');
            $pdf->Ln(2);
        }

        // ── Impact ────────────────────────────────────────────────────────────
        if ($imp !== '') {
            $pdf->SetX(15);
            $pdf->SetFont('Helvetica', 'B', 5.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(180, 4.5, u('IMPACT'), 0, 1, 'L');

            $pdf->SetX(15);
            $pdf->SetFont('Helvetica', 'I', 7.5);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->MultiCell(180, 4.2, u($imp), 0, 'L');
            $pdf->Ln(2);
        }

        // ── Remédiation (dark code block) ────────────────────────────────────
        if ($rem !== '') {
            $pdf->SetX(15);
            $pdf->SetFont('Helvetica', 'B', 5.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(180, 4.5, u('COMMANDES DE REMEDIATION CISCO CLI'), 0, 1, 'L');

            // Estimation hauteur du bloc
            $remLines  = max(1, substr_count(trim($rem), "\n") + 1);
            $charsLine = max(1, (int)floor(172 / 2.1));
            $wrapLines = 0;
            foreach (explode("\n", $rem) as $rl) {
                $wrapLines += max(1, (int)ceil(mb_strlen($rl) / $charsLine));
            }
            $blockH = max($remLines, $wrapLines) * 4.2 + 6;

            // Page break préventif
            if ((float)$pdf->GetY() + $blockH > 268) {
                $pdf->AddPage();
            }

            $remY = (float)$pdf->GetY();
            // Fond dark
            $pdf->SetFillColor(15, 23, 42);
            $pdf->Rect(15, $remY, 180, $blockH, 'F');
            // Liseré gauche cyan
            $pdf->SetFillColor(56, 189, 248);
            $pdf->Rect(15, $remY, 2.5, $blockH, 'F');
            // Texte code
            $pdf->SetXY(20, $remY + 3);
            $pdf->SetFont('Courier', '', 7.5);
            $pdf->SetTextColor(56, 189, 248);
            $pdf->MultiCell(172, 4.2, u($rem), 0, 'L');

            $afterRem = (float)$pdf->GetY();
            $pdf->SetY(max($afterRem, $remY + $blockH) + 1);
        }

        // ── Séparateur entre findings ─────────────────────────────────────────
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(15, (float)$pdf->GetY() + 1, 195, (float)$pdf->GetY() + 1);
        $pdf->Ln(5);
    }
}


// ─── Sortie du PDF ────────────────────────────────────────────────────────────
$safeHost = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $hostname ?: 'audit');
$safeDate = date('Ymd_His');
$pdfName  = 'NetAudit_' . $safeHost . '_' . $safeDate . '.pdf';

// Vide le buffer AVANT d'envoyer les headers PDF
while (ob_get_level()) {
    ob_end_clean();
}

$pdf->Output('D', $pdfName);
exit;
