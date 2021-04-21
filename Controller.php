<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DBHealth;
use Piwik\Db;
use Piwik\Log;
use Piwik\Piwik;
use Piwik\Config;
use Piwik\View;
use Piwik\Access;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\DataTable\BaseFilter;
use Piwik\Plugin\ControllerAdmin;
use Piwik\Plugins\DBHealth\API as DBHealthAPI;


/**
 * A controller lets you for example create a page that can be added to a menu. For more information read our guide
 * http://developer.piwik.org/guides/mvc-in-piwik or have a look at the our API references for controller and view:
 * http://developer.piwik.org/api-reference/Piwik/Plugin/Controller and
 * http://developer.piwik.org/api-reference/Piwik/View
 */
class Controller extends \Piwik\Plugin\Controller
{

    public function index()
    {
        return null;
    }


    public function showStatus()
    {
        $db = new Db();
        $query = "SHOW STATUS";
        return $db::fetchAll($query);
    }

    public function showVariables()
    {
        $db = new Db();
        $query = "SHOW VARIABLES";
        return $db::fetchAll($query);
    }
    public function test($where = null)
    {

        $db = new Db();

        $tableName = Common::prefixTable('user_feedback_results');
        $statement = $where? "WHERE {$where}" : "";
        $query = "SELECT * FROM {$tableName} as feedbacks {$statement}";
        return $db::fetchAll($query);
    }

       public function diskCheck()
    {
           var = $this->showVariables();
           stat = $this->showStatus();
    }


    public function getPerfChecks() {
        $api = new DBHealthAPI();
        Piwik::checkUserHasSuperUserAccess();
        //echo "DB Connection " . $api->dbConnectTest() . " milliseconds\n";
        //print_r($this->test());

        return $this->renderTemplate('perfreport',
            [   'db_connection' =>  $api->dbConnectTest(),
                'status' => $this->showStatus(),
                'variables' => $this->showVariables()
            ]
        );




        ;
    }

    public function getMysqlTableStatus() {
        //Log::debug("A user accessed getMysqlVariableData()");
        try {
        $api = new DBHealthAPI();
        return $this->renderTemplate(
                'index',
                [
                  'dataTable' =>  $api->getMysqlTableStatus()

                ]
            );

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function getMysqlVariableData() {
        //Log::debug("A user accessed getMysqlVariableData()");
        try {
        $api = new DBHealthAPI();
        return $this->renderTemplate(
                'index',
                [
                  'dataTable' =>  $api->getMysqlVariableData()

                ]
            );

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function getMysqlStatus() {
        //Log::debug("A user accessed getMysqlstatus()");
        try {
        $api = new DBHealthAPI();
        return $this->renderTemplate(
                'index',
                [
                  'dataTable' =>  $api->getMysqlStatusData()

                ]
            );

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
