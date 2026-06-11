<?php

/**
 * Net Auditor IA — Configuration centrale
 *
 * Ce fichier centralise toutes les constantes de l'application.
 * NE JAMAIS exposer ce fichier publiquement (hors webroot si possible).
 */

if (!defined('NET_AUDITOR_ACCESS')) {
    define('NET_AUDITOR_ACCESS', true);
}

// ─── Clé API Anthropic ────────────────────────────────────────────────────────
// Remplacez 'votre_cle_ici' par votre clé sur console.anthropic.com
define('ANTHROPIC_API_KEY', 'VOTRE_CLE_ANTHROPIC_ICI');

// ─── Identité de l'application ───────────────────────────────────────────────
define('APP_NAME',    'Net Auditor IA');
define('APP_VERSION', '1.0.0');

// ─── Modèle Claude ───────────────────────────────────────────────────────────
define('CLAUDE_MODEL',      'claude-sonnet-4-6'); // Anthropic Sonnet 4.6
define('CLAUDE_MAX_TOKENS', 4096);
define('API_TIMEOUT',       90);                  // secondes (l'analyse peut être longue)

// ─── Contraintes fichier ─────────────────────────────────────────────────────
define('MAX_FILE_SIZE',       500 * 1024);   // 500 Ko strict
define('ALLOWED_EXTENSIONS',  ['txt']);
define('ALLOWED_MIME_TYPES',  ['text/plain']);
define('UPLOAD_DIR',          __DIR__ . '/uploads/');

// ─── Mode debug ──────────────────────────────────────────────────────────────
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
