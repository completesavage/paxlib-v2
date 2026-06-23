<?php
/**
 * Configuration file for Paxton Carnegie Library Kiosk
 * 
 * Copy this file to config.php and fill in your credentials
 */

// Polaris API credentials (for leap_proxy.php)
$username = 'YOUR_POLARIS_USERNAME';  // e.g., 'DOMAIN\\username'
$password = 'YOUR_POLARIS_PASSWORD';

// No-cover image path (displayed when movie has no cover art)
define('NO_COVER_PATH', '/img/no-cover.svg');

// Optional: Syndetics client ID for cover images
define('SYNDETICS_CLIENT', 'ilheartland');

// Optional: Session timeout in seconds (default 90)
define('SESSION_TIMEOUT', 90);

// Polaris record set ID for the kiosk DVD collection
define('DVD_RECORDSET_ID', 473530);

// OMDb API key (https://www.omdbapi.com/apikey.aspx)
define('OMDB_API_KEY', 'YOUR_OMDB_API_KEY');

// Optional: low-cost AI for movie overviews & patron Q&A (Groq: https://console.groq.com)
// define('GROQ_API_KEY', 'your-groq-api-key');
// define('GROQ_MODEL', 'llama-3.1-8b-instant');
