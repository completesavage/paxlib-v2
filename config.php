<?php
/**
 * Configuration file for Paxton Carnegie Library Kiosk
 */

// Polaris API credentials (for leap_proxy.php)
$username = 'aidan.garza@share.ihls.lcl';
$password = 'imsvg137';

define('NO_COVER_PATH', '/img/no-cover.svg');
define('SYNDETICS_CLIENT', 'ilheartland');
define('SESSION_TIMEOUT', 90);
define('DVD_RECORDSET_ID', 473530);

// OMDb API (movie metadata & poster fallback)
define('OMDB_API_KEY', 'bb52036c');

// Optional: low-cost AI for overviews & patron Q&A (Groq free tier: https://console.groq.com)
define('GROQ_API_KEY', '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');
define('GROQ_MODEL', 'llama-3.1-8b-instant');
