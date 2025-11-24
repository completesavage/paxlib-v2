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
