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

    /**
     * Using logic from tuning primer to check disk health
     * https://github.com/BMDan/tuning-primer.sh/blob/master/tuning-primer.sh
     */
    public function getCheckTmpDiskTables()
    {

            $dataTable = new DataTable();

            $query = "SHOW STATUS";
            $result = $this->getDb()->fetchAssoc($query);

            //TMP Table tests
            $tmp_disk_tables = 0;
            $created_tmp_tables = 0;
            $tmp_disk_tables_ratio = 0;
            $tmp_table_message = "";
            $tmp_disk_tables_ratio = 0;

            //Inno DB Bufferpool tests
            $percent_innodb_buffer_pool_free = 0;
            $innodb_buffer_pool_pages_free = 0;
            $innodb_buffer_pool_pages_total = 0;

            //Memory usage
                //Variables
                $read_buffer_size = 0;
                $read_rnd_buffer_size = 0;
                $sort_buffer_size = 0;
                $thread_stack = 0;
                $max_connections = 0;
                $join_buffer_size = 0;
                $tmp_table_size = 0;
                $max_heap_table_size = 0;
                $log_bin = 0;
                $binlog_cache_size = 0;
                //Status
                $max_used_connections = 0;

            //Get values form STATUS
            foreach ($result as $item) {
                if($item['Variable_name'] == 'Created_tmp_disk_tables') {
                    $tmp_disk_tables = $item['Value'];
                }
                if($item['Variable_name'] == 'Created_tmp_tables') {
                    $created_tmp_tables = $item['Value'];
                }
                if($item['Variable_name'] == 'Percent_innodb_buffer_pool_free') {
                    $percent_innodb_buffer_pool_free = (int) $item['Value'];
                }
                if($item['Variable_name'] == 'Innodb_buffer_pool_pages_free') {
                    $innodb_buffer_pool_pages_free = (int) $item['Value'];
                }
                if($item['Variable_name'] == 'Innodb_buffer_pool_pages_total') {
                    $innodb_buffer_pool_pages_total = (int) $item['Value'];
                }
                if($item['Variable_name'] == 'Max_used_connections') {
                    $max_used_connections = (int) $item['Value'];
                }
            }
            $query = "SHOW VARIABLES";
            $result = $this->getDb()->fetchAssoc($query);
            foreach ($result as $item) {
                if($item['Variable_name'] == 'read_buffer_size') {
                    $read_buffer_size = $item['Value'];
                }
                if($item['Variable_name'] == 'read_rnd_buffer_size') {
                    $read_rnd_buffer_size = $item['Value'];
                }
                if($item['Variable_name'] == 'sort_buffer_size') {
                    $sort_buffer_size = $item['Value'];
                }
                if($item['Variable_name'] == 'thread_stack') {
                    $thread_stack = $item['Value'];
                }
                if($item['Variable_name'] == 'max_connections') {
                    $max_connections = $item['Value'];
                }
                if($item['Variable_name'] == 'join_buffer_size') {
                    $join_buffer_size = $item['Value'];
                }
                if($item['Variable_name'] == 'tmp_table_size') {
                    $tmp_table_size = $item['Value'];
                }
                if($item['Variable_name'] == 'max_heap_table_size') {
                    $max_heap_table_size = $item['Value'];
                }
                if($item['Variable_name'] == 'log_bin') {
                    $log_bin = $item['Value'];
                }
                if($item['Variable_name'] == 'binlog_cache_size') {
                    $binlog_cache_size = $item['Value'];
                }


            }

            /**
            * TMP disk tests
            */
            if ($tmp_disk_tables == 0 )
                $tmp_disk_tables_ratio = 0;
            else
                $tmp_disk_tables_ratio = (($tmp_disk_tables * 100 / ( $tmp_tables + $tmp_disk_tables)));

            if($tmp_disk_tables_ratio > 25)
                $tmp_table_message = "Perhaps you should increase your tmp_table_size and/or max_heap_table_size to reduce the number of disk-based temporary tables Note! BLOB and TEXT columns are not allow in memory tables. If you are using these columns raising these values might not impact your  ratio of on disk temp tables.";
            else
                $tmp_table_message = "Created disk tmp tables ratio seems fine";


            //tmp table test data
            $dataTable->addRowsFromSimpleArray(array(
                array('Test' => "Checking temp file usage",
                      'Message' => $tmp_table_message,
                      'Values' => "tmp_disk_tables: " . $tmp_disk_tables . " Created tmp tables: " . $created_tmp_tables
                )
            ));
            /**
            * InnoDB bufferpool test
            */
            $percent_innodb_buffer_pool_free = (($innodb_buffer_pool_pages_free * 100 / $innodb_buffer_pool_pages_total));

            $dataTable->addRowsFromSimpleArray(array(
                array('Test' => "InnoDB bufferpool test",
                      'Message' => "Current InnoDB buffer pool free " . round($percent_innodb_buffer_pool_free) ." %" ,
                      'Values' => "innodb_buffer_pool_pages_free: " . $innodb_buffer_pool_pages_free . " innodb_buffer_pool_pages_total: " . $innodb_buffer_pool_pages_total
                )
            ));
            /**
            * Memory usage
            */
        $per_thread_buffers = ($read_buffer_size + $read_rnd_buffer_size + $sort_buffer_size + $thread_stack + $join_buffer_size + $binlog_cache_size) * $max_connections;

        $per_thread_max_buffers = ($read_buffer_size + $read_rnd_buffer_size + $sort_buffer_size + $thread_stack + $join_buffer_size + $binlog_cache_size) * $max_used_connections;

        $dataTable->addRowsFromSimpleArray(array(
                array('Test' => "Memory usage",
                      'Message' => "Per thread buffer: " . round($per_thread_buffers / 1024 / 1024,4,0) . " MB<br> Per thread max used buffers: " . round($per_thread_max_buffers / 1024 / 1024,4,0) . " MB",
                      'Values' => "read_buffer_size: " . $read_buffer_size . " read_rnd_buffer_size: " . $read_rnd_buffer_size . " sort_buffer_size: " . $sort_buffer_size . " thread_stack: " . $thread_stack . " join_buffer_size: " . $join_buffer_size
                )
            ));

        return $dataTable;


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
    public function dbConnectTest() {
        $time_start = hrtime(true);
        // Get DB connection
        Db::get();
        $time_end = hrtime(true);
        $time = $time_end  - $time_start;
        return  $time/1e+6;
    }
    public function host_ping() {
    global $db_host;

    /* ICMP ping packet with a pre-calculated checksum */
    $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
    $socket  = socket_create(AF_INET, SOCK_RAW, 1);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 0));
    socket_connect($socket, $db_host, null);

    $ts = microtime(true);
    socket_send($socket, $package, strLen($package), 0);
    if (socket_read($socket, 255))
            $result = microtime(true) - $ts;
    else    $result = false;
    socket_close($socket);

    return $result;
} // host_ping()


    private function getDb()
    {
        return Db::get();
    }

}
