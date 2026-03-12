<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

function plugin_ipsuperview_current_language()
{
    $lang = '';
    if (isset($_SESSION['glpilanguage'])) {
        $lang = (string) $_SESSION['glpilanguage'];
    } elseif (defined('GLPI_LANGUAGE')) {
        $lang = (string) GLPI_LANGUAGE;
    }

    $lang = strtolower(str_replace('-', '_', $lang));
    if (strpos($lang, 'zh') === 0) {
        return 'zh_CN';
    }

    return 'en_US';
}

function plugin_ipsuperview_is_chinese()
{
    return plugin_ipsuperview_current_language() === 'zh_CN';
}

function plugin_ipsuperview_t($zh, $en = null, array $params = [])
{
    $text = plugin_ipsuperview_is_chinese() ? $zh : ($en ?? $zh);
    if (!empty($params)) {
        $text = vsprintf($text, $params);
    }

    return $text;
}
