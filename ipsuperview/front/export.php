<?php

include '../../../inc/includes.php';

Session::checkRight('plugin_ipsuperview', READ);

$subnet = new PluginIpsuperviewSubnet();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0 || !$subnet->getFromDB($id)) {
    Html::displayErrorAndDie(__('Item not found'));
}

$subnet->check($id, READ);

$filename = $subnet->getReportFilename();
$csv = $subnet->buildCsvReport();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Cache-Control: private, max-age=0, must-revalidate');

echo "\xEF\xBB\xBF";
echo $csv;
exit;
