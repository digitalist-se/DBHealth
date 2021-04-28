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
        Piwik::checkUserHasSomeAdminAccess();
        if (!Piwik::hasUserSuperUserAccess()) {
                throw new \Exception("No access to DBHealth");
            Piwik::checkUserHasAdminAccess($initialIdSite);
        }
    if (Piwik::hasUserSuperUserAccess()) {

        $menu->addItem('DB Health', "DB Status variables", $this->urlForAction('getMysqlStatus'));
        $menu->addItem('DB Health', "DB Setting variables", $this->urlForAction('getMysqlVariableData'));
        $menu->addItem('DB Health', "Show database table status", $this->urlForAction('getMysqlTableStatus'));
        $menu->addItem('DB Health', "Run performance calculations", $this->urlForAction('getPerfChecks'));
    }

    }
}
