<?php

define('PLUGIN_IPSUPERVIEW_VERSION', '1.0.0');

require_once __DIR__ . '/inc/i18n.php';

if (!defined('PLUGIN_IPSUPERVIEW_DIR')) {
    define('PLUGIN_IPSUPERVIEW_DIR', Plugin::getPhpDir('ipsuperview'));
    define('PLUGIN_IPSUPERVIEW_DIR_NOFULL', Plugin::getPhpDir('ipsuperview', false));
    define('PLUGIN_IPSUPERVIEW_WEBDIR', Plugin::getWebDir('ipsuperview'));
}

function plugin_init_ipsuperview()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['ipsuperview'] = true;
    $PLUGIN_HOOKS['change_profile']['ipsuperview'] = ['PluginIpsuperviewProfile', 'initProfile'];

    Plugin::registerClass(
        'PluginIpsuperviewProfile',
        ['addtabon' => ['Profile']]
    );

    if (Session::getLoginUserID()) {
        if (Session::haveRight('plugin_ipsuperview', READ)) {
            $PLUGIN_HOOKS['menu_toadd']['ipsuperview'] = ['tools' => 'PluginIpsuperviewSubnet'];
        }

        if (isset($_SESSION['glpiactiveprofile']['interface'])
            && $_SESSION['glpiactiveprofile']['interface'] === 'central') {
            $PLUGIN_HOOKS['add_css']['ipsuperview'] = 'ipsuperview.css';
            $PLUGIN_HOOKS['add_javascript']['ipsuperview'] = 'ipsuperview.js';
        }
    }
}

function plugin_version_ipsuperview()
{
    return [
        'name'         => __('IP SuperView', 'ipsuperview'),
        'version'      => PLUGIN_IPSUPERVIEW_VERSION,
        'author'       => 'OpenAI Codex',
        'license'      => 'GPLv2+',
        'homepage'     => 'https://glpi-project.org/',
        'requirements' => [
            'glpi' => [
                'min' => '10.0',
                'max' => '11.0',
            ],
        ],
    ];
}

function plugin_ipsuperview_check_prerequisites()
{
    return true;
}

function plugin_ipsuperview_check_config($verbose = false)
{
    return true;
}
