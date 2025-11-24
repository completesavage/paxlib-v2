<?php
/**
 * Paxton Carnegie Library - Movie Kiosk
 * 
 * This redirects to the appropriate interface:
 * - /kiosk.php - Public kiosk interface
 * - /staff.php - Staff panel for requests
 */

// Default redirect to kiosk
header('Location: kiosk.php');
exit;
