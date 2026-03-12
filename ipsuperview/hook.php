<?php

function plugin_ipsuperview_install()
{
    global $DB;

    include_once PLUGIN_IPSUPERVIEW_DIR . '/inc/profile.class.php';

    if (!$DB->tableExists('glpi_plugin_ipsuperview_subnets')) {
        $DB->runFile(PLUGIN_IPSUPERVIEW_DIR . '/sql/empty-1.0.0.sql');
    }

    PluginIpsuperviewProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

    return true;
}

function plugin_ipsuperview_uninstall()
{
    include_once PLUGIN_IPSUPERVIEW_DIR . '/inc/profile.class.php';

    $migration = new Migration('1.0.0');
    $migration->dropTable('glpi_plugin_ipsuperview_subnets');

    $itemtypes = ['DisplayPreference', 'SavedSearch'];
    foreach ($itemtypes as $itemtype) {
        $item = new $itemtype();
        $item->deleteByCriteria(['itemtype' => 'PluginIpsuperviewSubnet']);
    }

    $profileRight = new ProfileRight();
    foreach (PluginIpsuperviewProfile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    PluginIpsuperviewProfile::removeRightsFromSession();

    return true;
}
