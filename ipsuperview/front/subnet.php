<?php

include '../../../inc/includes.php';

Html::header(PluginIpsuperviewSubnet::getTypeName(2), '', 'tools', 'pluginipsuperviewsubnet');

$subnet = new PluginIpsuperviewSubnet();

if ($subnet->canView()) {
    echo "<div class='ipsuperview-listintro'>";
    echo "<div class='ipsuperview-listintro__body'>";
    echo "<div class='ipsuperview-listintro__eyebrow'>IP SuperView</div>";
    echo "<h2 class='ipsuperview-listintro__title'>" . plugin_ipsuperview_t('固定 IP 网段总览', 'Fixed IP Subnet Overview') . "</h2>";
    echo "<p class='ipsuperview-listintro__text'>" . plugin_ipsuperview_t(
        '这里不是资产清单，而是网段视角。先选一个网段，再看已占用、在线未登记、冲突和导出报表。',
        'This is a subnet view, not an asset list. Pick a subnet first, then inspect occupied IPs, live but unregistered devices, conflicts, and exports.'
    ) . "</p>";
    echo "<div class='ipsuperview-listintro__chips'>";
    echo "<span class='ipsuperview-chip'>" . plugin_ipsuperview_t('固定IP核对', 'Fixed IP audit') . "</span>";
    echo "<span class='ipsuperview-chip'>" . plugin_ipsuperview_t('在线未登记设备', 'Live unregistered devices') . "</span>";
    echo "<span class='ipsuperview-chip'>" . plugin_ipsuperview_t('CSV 报表导出', 'CSV export') . "</span>";
    echo "</div>";
    echo "</div>";

    if (PluginIpsuperviewSubnet::canCreate()) {
        echo "<div class='ipsuperview-listintro__actions'>";
        echo "<a class='btn btn-primary' href='" . PluginIpsuperviewSubnet::getFormURL() . "'>" . plugin_ipsuperview_t('新建网段', 'Create subnet') . "</a>";
        echo "</div>";
    }

    echo "</div>";

    echo "<div class='ipsuperview-searchwrap'>";
    Search::show('PluginIpsuperviewSubnet');
    echo "</div>";
} else {
    Html::displayRightError();
}

Html::footer();
