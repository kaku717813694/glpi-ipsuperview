<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginIpsuperviewProfile extends Profile
{
    public static $rightname = 'profile';

    private static function t($zh, $en = null, array $params = [])
    {
        return plugin_ipsuperview_t($zh, $en, $params);
    }

    public static function getAllRights()
    {
        return [
            [
                'itemtype' => 'PluginIpsuperviewSubnet',
                'label'    => self::t('查看 IP 网段总览', 'View IP subnet overview'),
                'field'    => 'plugin_ipsuperview',
            ],
        ];
    }

    public function showForm($profiles_id = 0, $openform = true, $closeform = true)
    {
        echo "<div class='firstbloc'>";

        $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);
        if ($canedit && $openform) {
            $profile = new Profile();
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        $profile = new Profile();
        $profile->getFromDB($profiles_id);
        $profile->displayRightsChoiceMatrix(
            self::getAllRights(),
            [
                'canedit'       => $canedit,
                'default_class' => 'tab_bg_2',
                'title'         => __('General'),
            ]
        );

        if ($canedit && $closeform) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
            Html::closeForm();
        }

        echo '</div>';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() === 'Profile' && $item->getField('interface') === 'central') {
            return self::t('IP 网段总览', 'IP SuperView');
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() === 'Profile') {
            self::addDefaultProfileInfos($item->getID(), ['plugin_ipsuperview' => 0]);

            $profile = new self();
            $profile->showForm($item->getID());
        }

        return true;
    }

    public static function addDefaultProfileInfos($profiles_id, $rights)
    {
        $profileRight = new ProfileRight();
        $dbu = new DbUtils();

        foreach ($rights as $right => $value) {
            if (!$dbu->countElementsInTable('glpi_profilerights', ['profiles_id' => $profiles_id, 'name' => $right])) {
                $profileRight->add([
                    'profiles_id' => $profiles_id,
                    'name'        => $right,
                    'rights'      => $value,
                ]);

                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }

    public static function createFirstAccess($profiles_id)
    {
        self::addDefaultProfileInfos($profiles_id, ['plugin_ipsuperview' => ALLSTANDARDRIGHT]);
    }

    public static function initProfile()
    {
        global $DB;

        foreach ($DB->request(
            "SELECT *
             FROM `glpi_profilerights`
             WHERE `profiles_id` = '" . $_SESSION['glpiactiveprofile']['id'] . "'
               AND `name` LIKE '%plugin_ipsuperview%'"
        ) as $prof) {
            if (isset($_SESSION['glpiactiveprofile'])) {
                $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
            }
        }
    }

    public static function removeRightsFromSession()
    {
        if (isset($_SESSION['glpiactiveprofile']['plugin_ipsuperview'])) {
            unset($_SESSION['glpiactiveprofile']['plugin_ipsuperview']);
        }
    }
}
