<?php

include '../../../inc/includes.php';

if (!isset($_GET['id'])) {
    $_GET['id'] = '';
}

$subnet = new PluginIpsuperviewSubnet();

if (isset($_POST['add'])) {
    $subnet->check(-1, CREATE, $_POST);
    $new_id = $subnet->add($_POST);
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($subnet->getFormURL() . '?id=' . $new_id);
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    $subnet->check($_POST['id'], UPDATE);
    $subnet->update($_POST);
    Html::back();
} elseif (isset($_POST['delete'])) {
    $subnet->check($_POST['id'], DELETE);
    $subnet->delete($_POST);
    $subnet->redirectToList();
} elseif (isset($_POST['restore'])) {
    $subnet->check($_POST['id'], PURGE);
    $subnet->restore($_POST);
    $subnet->redirectToList();
} elseif (isset($_POST['purge'])) {
    $subnet->check($_POST['id'], PURGE);
    $subnet->delete($_POST, 1);
    $subnet->redirectToList();
} elseif (isset($_POST['refreshscan'])) {
    $subnet->check($_POST['id'], UPDATE);
    $subnet->getFromDB($_POST['id']);
    $scanResult = $subnet->refreshLiveScan();
    if (!empty($scanResult['ok'])) {
        Session::addMessageAfterRedirect(
            plugin_ipsuperview_t(
                '实扫完成：发现在线地址 %d 个，耗时 %s 秒。',
                'Scan finished: found %d live addresses in %s seconds.',
                [
                    (int) $scanResult['alive_count'],
                    number_format(((int) $scanResult['duration_ms']) / 1000, 1),
                ]
            ),
            false,
            INFO
        );
    } else {
        Session::addMessageAfterRedirect(
            plugin_ipsuperview_t(
                '实扫失败：%s',
                'Scan failed: %s',
                [
                    !empty($scanResult['message'])
                        ? $scanResult['message']
                        : plugin_ipsuperview_t('请检查服务器上的 fping / shell_exec 权限。', 'Please check fping / shell_exec permissions on the server.'),
                ]
            ),
            false,
            ERROR
        );
    }
    Html::back();
} elseif (isset($_POST['saveiplabel'])) {
    $subnet->check($_POST['id'], UPDATE);
    $subnet->getFromDB($_POST['id']);
    $result = $subnet->saveIpLabel($_POST);
    Session::addMessageAfterRedirect($result['message'], false, $result['ok'] ? INFO : ERROR);
    Html::back();
} else {
    Html::header(PluginIpsuperviewSubnet::getTypeName(2), '', 'tools', 'pluginipsuperviewsubnet');

    $itemId = (int) $_GET['id'];
    if ($itemId === 0 && !PluginIpsuperviewSubnet::canCreate()) {
        Html::displayRightError();
        Html::footer();
        return;
    }

    $subnet->checkGlobal(READ);
    $subnet->display($_GET);
    Html::footer();
}
