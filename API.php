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
     * Return Database Table Status data
     *
     * @return DataTable
     */
    function getProblematicSegments() {
        if (!Piwik::hasUserSuperUserAccess()) {
            throw new \Exception("No access to DBHealth");
            Piwik::checkUserHasAdminAccess($initialIdSite);
        }
        try {
            $dataTable = new DataTable();

            $query = "SELECT name,idsegment,definition,enable_only_idsite,ts_created,ts_last_edit FROM `matomo_segment` where definition like '%@%' and deleted not like '1' ";
            $result = $this->getDb()->fetchAssoc($query);

            foreach ($result as $item) {
                $dataTable->addRowsFromSimpleArray(array(
                    array('Name' => $item['name'],
                          'Idsegment' => $item['idsegment'],
                          'Definition' => $item['definition'],
                          'Used on siteId (0=all)' => $item['enable_only_idsite'],
                          'Creation date' => $item['ts_created'],
                          'Last updated' => $item['ts_last_edit']
                         )
                ));

            }
            return $dataTable;

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }



    /**
     * Return Database Status variables
     *
     * @return DataTable
     */
    function getMysqlStatusData()
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
    function getMysqlTableStatus() {
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
    function getMysqlVariableData()
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
