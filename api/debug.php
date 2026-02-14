<?php
require_once __DIR__.'/polaris.php';

$api = new PolarisAPI();

// Replace with your real values
$patronBarcode = '21783000087144';
$bibRecordId   = '6805386';

// Lookup patron
$patron = $api->getPatronByBarcode($patronBarcode);
var_dump($patron);

// Lookup item (optional)
$item = $api->getItemByBib($bibRecordId); // if your API has this
var_dump($item);
