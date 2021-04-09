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
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getMysqlStatusData()
    {
        try {
            $dataTable = new DataTable();

            $query = "SHOW STATUS";
            $result = $this->getDb()->fetchAssoc($query);

            foreach ($result as $item) {
                $dataTable->addRowsFromSimpleArray(array(
                    array('Variable_name' => $item['Variable_name'], 'Value' => $item['Value'])
                ));

            }
            return $dataTable;

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }


    public function getMysqlTableStatus() {
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
                          'Checksum' => $item['Checksum'],
                          'Max_index_length' => $item['Max_index_length'],
                          'Temporary' => $item['Temporary']
                         )
                ));

            }
            return $dataTable;

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }
    public function getMysqlVariableData()
    {
        try {
            $dataTable = new DataTable();

            $query = "SHOW VARIABLES";
            $result = $this->getDb()->fetchAssoc($query);

            foreach ($result as $item) {
                $dataTable->addRowsFromSimpleArray(array(
                    array('Variable_name' => $item['Variable_name'], 'Value' => $item['Value'])
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
