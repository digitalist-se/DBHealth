<?php
    /**
     * Piwik - free/libre analytics platform
     *
     * @link https://matomo.org
     * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
     *
     */
namespace Piwik\Plugins\DBHealth;
use Piwik\Db;
use Piwik\Log;
use Piwik\Piwik;
use Piwik\View;
use Piwik\Access;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\DataTable\BaseFilter;
use Piwik\Plugin\ControllerAdmin;
use Piwik\DataTable\Row;

/**
 * API for plugin DBHealth
 *
 * @method static \Piwik\Plugins\DBHealth\API getInstance()
 */
class API extends \Piwik\Plugin\API
{

    /**
     * Return Database Status variables
     *
     * @return DataTable
     */

    public function getMysqlStatusData()
    {
        if (!Piwik::hasUserSuperUserAccess()) {
            throw new \Exception("No access to DBHealth");
            Piwik::checkUserHasAdminAccess($initialIdSite);
        }

        try {
            $dataTable = new DataTable();

            $query = "SHOW STATUS";
            $result = $this->getDb()->fetchAssoc($query);

            foreach ($result as $item) {
                $dataTable->addRowsFromSimpleArray(array(
                    array('Name' => $item['Variable_name'], 'Value' => $item['Value'])
                ));

            }
            return $dataTable;

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * Return Database Table Status data
     *
     * @return DataTable
     */
    public function getMysqlTableStatus() {
        if (!Piwik::hasUserSuperUserAccess()) {
            throw new \Exception("No access to DBHealth");
            Piwik::checkUserHasAdminAccess($initialIdSite);
        }
        try {
            $dataTable = new DataTable();

            $query = "SHOW TABLE STATUS";
            $result = $this->getDb()->fetchAssoc($query);

            foreach ($result as $item) {
                $dataTable->addRowsFromSimpleArray(array(
                    array('Name' => $item['Name'],
                          'Collation' => $item['Collation'],
                          'Engine' => $item['Engine'],
                          'Rows' => $item['Rows'],
                          'Avg_row_length' => $item['Avg_row_length'],
                          'Data_length' => $item['Data_length'],
                          'Max_data_length' => $item['Max_data_length'],
                          'Index_length' => $item['Index_length'],
                          'Data_free' => $item['Data_free'],
                          'Checksum' => $item['Checksum']
                         )
                ));

            }
            return $dataTable;

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }
    /**
     * Return Database Settings Variable data
     *
     * @return DataTable
     */
    public function getMysqlVariableData()
    {
        if (!Piwik::hasUserSuperUserAccess()) {
            throw new \Exception("No access to DBHealth");
            Piwik::checkUserHasAdminAccess($initialIdSite);
        }
        try {
            $dataTable = new DataTable();

            $query = "SHOW VARIABLES";
            $result = $this->getDb()->fetchAssoc($query);

            foreach ($result as $item) {
                $dataTable->addRowsFromSimpleArray(array(
                    array('Name' => $item['Variable_name'], 'Value' => $item['Value'])
                ));

            }
            return $dataTable;

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }


    private function getDb()
    {
        return Db::get();
    }

}
