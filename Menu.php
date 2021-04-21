<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DBHealth;

use Piwik\Common;
use Piwik\Menu\MenuTop;
use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

/**
 * This class allows you to add, remove or rename menu items.
 * To configure a menu (such as Admin Menu, Top Menu, User Menu...) simply call the corresponding methods as
 * described in the API-Reference http://developer.piwik.org/api-reference/Piwik/Menu/MenuAbstract
 */
class Menu extends \Piwik\Plugin\Menu
{


    public function configureAdminMenu(MenuAdmin $menu)
    {
    if (Piwik::hasUserSuperUserAccess()) {

        $menu->addItem('DB Health', "Status variables", $this->urlForAction('getMysqlStatus'));
        $menu->addItem('DB Health', "Seting variables", $this->urlForAction('getMysqlVariableData'));
        $menu->addItem('DB Health', "Show Table status", $this->urlForAction('getMysqlTableStatus'));
        $menu->addItem('DB Health', "Run performance analyzes", $this->urlForAction('getPerfChecks'));
    }

    }
}
