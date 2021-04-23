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




    /**
     * Return Database PERFORMANCE_SCHEMA variables
     *
     * @return array
     */
    public function showPerformanceSchemaStatus()
    {
        $db = new Db();
        $query = "SHOW ENGINE PERFORMANCE_SCHEMA STATUS";
        return $db::fetchAll($query);
    }

    /**
     * Return Calculation od Database index length for InnoDB tables
     *
     * @return array
     */
    public function showInnodbIndexes()
    {
        $db = new Db();
        $query = "SELECT SUM(INDEX_LENGTH) var FROM information_schema.tables WHERE ENGINE='InnoDB'";
        return $db::fetchAll($query);
    }

    /**
     * Return Calculation od Database size for InnoDB tables
     *
     * @return array
     */
    public function showInnodbData()
    {
        $db = new Db();
        $query = "SELECT SUM(DATA_LENGTH) var FROM information_schema.tables WHERE ENGINE='InnoDB'";
        return $db::fetchAll($query);
    }

    /**
     * Return Database Setting variables
     *
     * @return array
     */
    public function showVariables()
    {
        $db = new Db();
        $query = "SHOW VARIABLES";
        return $db::fetchAll($query);
    }

    /**
     * Return Database Status variables
     *
     * @return array
     */
    public function showStatus()
    {
        $db = new Db();
        $query = "SHOW STATUS";
        return $db::fetchAll($query);
    }

    /**
     * Calculate Mem usage in DB
     *
     * @return object
     */
    public function memUsage()
    {
        $variables = $this->showVariables();
        $stat = $this->showStatus();


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


        $innodb_buffer_pool_size;
        $innodb_additional_mem_pool_size;
        $innodb_log_buffer_size;
        $key_buffer_size;
        $query_cache_size;
        //Status
        $max_used_connections = 0;

        //Calculations
        $per_thread_buffers = 0;
        $per_thread_max_buffers = 0;
        $effective_tmp_table_size = 0;

        //Variables
        foreach ($variables as $item) {
            if($item['Variable_name'] == 'innodb_buffer_pool_size') {
                $innodb_buffer_pool_size = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_additional_mem_pool_size') {
                $innodb_additional_mem_pool_size = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_log_buffer_size') {
                $innodb_log_buffer_size = $item['Value'];
            }
            if($item['Variable_name'] == 'key_buffer_size') {
                $innodb_additional_mem_pool_size = $item['Value'];
            }
            if($item['Variable_name'] == 'query_cache_size') {
                $query_cache_size = $item['Value'];
            }
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
                $tmp_table_size = $item['Value'];
            }
            if($item['Variable_name'] == 'binlog_cache_size') {
                $binlog_cache_size = $item['Value'];
            }
        }
        foreach ($stat as $item) {
            if($item['Variable_name'] == 'Max_used_connections') {
                $max_used_connections = $item['Value'];
            }
        }

        if ($max_heap_table_size < $tmp_table_size)
            $effective_tmp_table_size=$max_heap_table_size;
        else
            $effective_tmp_table_size=$tmp_table_size;


        $per_thread_buffers = ($read_buffer_size + $read_rnd_buffer_size + $sort_buffer_size + $thread_stack + $join_buffer_size + $binlog_cache_size) * $max_connections;
        $per_thread_max_buffers = ($read_buffer_size + $read_rnd_buffer_size + $sort_buffer_size + $thread_stack + $join_buffer_size + $binlog_cache_size) * $max_used_connections;


        $global_buffers= $innodb_buffer_pool_size + $innodb_additional_mem_pool_size + $innodb_log_buffer_size + $key_buffer_size+$query_cache_size;

        $max_memory=$global_buffers+$per_thread_max_buffers;
        $total_memory=$global_buffers+$per_thread_buffers;


        $msg1 = "Max Memory Ever Allocated : " .  round($max_memory/ 1024 / 1024,2) . " MB<br>";
        $msg1 .= "Configured Max Per-thread Buffers : " . round($per_thread_buffers/ 1024 / 1024,2) . " MB<br>";
        $msg1 .= "Configured Max Global Buffers : " . round($global_buffers/ 1024 / 1024,2) . " MB<br>";
        $msg1 .= "Configured Max Memory Limit : " . round($total_memory/ 1024 / 1024,2) . " MB<br>";
        $msg1 .= "Plus " . round($effective_tmp_table_size/ 1024 / 1024,2) . " MB per temporary table created<br>";
        $msg1 .= "Note: Max memory limit shoould not exceeds 90% of your physical memory";

        $result = [ "msg1" => $msg1];


        return $result;


    }

    /**
     * Calculate  Query Cache usage in DB
     *
     * @return object
     */
    public function queryCacheCheck()
    {
        $variables = $this->showVariables();
        $stat = $this->showStatus();
        $msgFrag = "";
        $msgUnused = "Memory usage ratio is fine";
        $msgSize = "Memory seems high enough";
        $msg = "";


        //Variables (settings)
        $query_cache_size = 0;
        $query_cache_limit = 0;
        $query_cache_min_res_unit = 0;

        //Status
        $qcache_free_memory = 0;
        $qcache_used_memory = 0;
        $qcache_free_blocks = 0;
        $qcache_lowmem_prunes = 0;

        //Calculations
        $qcache_mem_fill_ratio = 0;
        $qcache_used_memory= 0 ;
        $qcache_mem_fill_ratioHR = 0;
        $qcache_percent_fragmented = 0;
        $qcache_percent_fragmentedHR = 0;
        //Variables
        foreach ($variables as $item) {
            if($item['Variable_name'] == 'query_cache_size') {
                $query_cache_size = $item['Value'];
            }
            if($item['Variable_name'] == 'query_cache_limit') {
                $query_cache_limit = $item['Value'];
            }                if($item['Variable_name'] == 'query_cache_min_res_unit') {
                $query_cache_min_res_unit = $item['Value'];
            }

        }
        //Status
        foreach ($stat as $item) {
            if($item['Variable_name'] == 'Qcache_free_memory') {
                $qcache_free_memory = $item['Value'];
            }
            if($item['Variable_name'] == 'Qcache_used_memory') {
                $qcache_used_memory = $item['Value'];
            }
            if($item['Variable_name'] == 'Qcache_free_blocks') {
                $qcache_free_blocks = $item['Value'];
            }
            if($item['Variable_name'] == 'Qcache_total_blocks') {
                $qcache_total_blocks = $item['Value'];
            }

        }
        if ($query_cache_size == 0) {
            $msg = "Query cache is supported but not enabled <br>Perhaps you should set the query_cache_size <b>(" . $query_cache_size . ")</b>";
            $msgFrag = "";
            $msgUnused = "";
            $msgSize = "";
        }
        else {
            $qcache_used_memory = $query_cache_size - $qcache_free_memory;
            $qcache_mem_fill_ratio =  $qcache_used_memory * 100 / $query_cache_size;
            $qcache_mem_fill_ratioHR = $qcache_mem_fill_ratio / 1;

            if ($qcache_free_blocks > 2  && $qcache_total_blocks > 0)  {
                $qcache_percent_fragmented = $qcache_free_blocks * 100 / $qcache_total_blocks;
                $qcache_percent_fragmentedHR = $qcache_percent_fragmented / 1;

            }
            if ($qcache_percent_fragmentedHR > 20) {
                $msgFrag = "Query Cache is " . round($qcache_percent_fragmentedHR,2) . " % fragmented<br>";
                $msgFrag = $msgFrag . "Run <b>FLUSH QUERY CACHE</b> periodically to defragment the query cache memory<br>";
                $msgFrag = $msgFrag . "If you have many small queries lower 'query_cache_min_res_unit' to reduce fragmentation.";
            }
            else {
                $msgFrag = "Fragmentation seems fine, Query Cache is " . round($qcache_percent_fragmented,2) . " % fragmented";

            }
            if ( $qcache_mem_fill_ratioHR <= 25) {
                $msgUnused = "Your query_cache_size (".  round($query_cache_size/ 1024 / 1024,2) . " MB) might to be too high (but if your system was recently restarded you might want to wait and see what happends) <br>";
                $msgUnused = $msgUnused . "The memory fill ratio is " . round($qcache_mem_fill_ratioHR,2) . " %<br>";
                $msgUnused = $msgUnused . "Perhaps you can use these resources elsewhere";
            }
            if ($qcache_lowmem_prunes >= 50 && $qcache_mem_fill_ratioHR >= 80) {
                $msgSize = "However " . $qcache_lowmem_prunes . " queries have been removed from the query cache<br>";
                $msgSize = $msgSize ."due to lack of memory. <br> Perhaps you should raise query_cache_size";

            }
            else {
                $msgSize = "Number of querys that has been removed from cache due to lack of memory (Qcache_lowmem_prunes) = " . $qcache_lowmem_prunes;
            }
            $msg = "Query_cache_size : " .  round($query_cache_size/ 1024 / 1024,2) . " MB<br>";
            $msg .= "Qcache_free_memory : " .  round($qcache_free_memory/ 1024 / 1024,2) . " MB<br>";


        }
        $result = [ "msg" => $msg,
                   "msgFrag" => $msgFrag,
                   "msgUnused" => $msgUnused,
                   "msgSize"  => $msgSize];


        return $result;

    }

    /**
     * Calculate Database Disk usage usage in DB
     *
     * @return object
     */
    public function diskCheck()
    {
        $variables = $this->showVariables();
        $stat = $this->showStatus();

        //TMP Table tests
        $tmp_disk_tables = 0;
        $created_tmp_tables = 0;
        $tmp_disk_tables_ratio = 0;
        $tmp_table_message = "";

        //Status
        foreach ($stat as $item) {
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


        //Calc $tmp_disk_tables_ratio ratio
        if ($tmp_disk_tables == 0 )
            $tmp_disk_tables_ratio = 0;
        else
            $tmp_disk_tables_ratio = (($tmp_disk_tables * 100 / ( $created_tmp_tables + $tmp_disk_tables)));


        //Report
        if($tmp_disk_tables_ratio > 25)
            $tmp_table_message = "Perhaps you should increase your tmp_table_size and/or max_heap_table_size to reduce the number of disk-based temporary tables <br>
                <b>Note:</b> BLOB and TEXT</b> columns are not allow in memory tables. If you are using these columns raising these values might not impact your  ratio of on disk temp tables.";
        else
            $tmp_table_message = "Created disk tmp tables ratio seems fine";

        $tmp_table_message .= "<br>Created tmp_disk_tables: " . $tmp_disk_tables . "<br>";
        $tmp_table_message .= "Created created_tmp_tables: " . $created_tmp_tables;


        $result = ["msg" => $tmp_table_message];


        return $result;
    }

    /**
     * Calculate Innodb Bufferpool usage in DB
     *
     * @return object
     */
    public function getBufferpoolTest() {

        $variables = $this->showVariables();
        $stat = $this->showStatus();
        $message1 = "";
        $message2 = "";

        //Status
        $innodb_buffer_pool_pages_data = 0;
        $innodb_buffer_pool_pages_misc = 0;
        $innodb_buffer_pool_pages_free = 0;
        $innodb_buffer_pool_pages_total = 0;
        $innodb_buffer_pool_read_ahead_seq = 0;
        $innodb_buffer_pool_read_requests = 0;
        $innodb_os_log_pending_fsyncs = 0;
        $innodb_os_log_pending_writes = 0;
        $innodb_log_waits = 0;
        $innodb_row_lock_time = 0;
        $innodb_row_lock_waits = 0;
        //Variables
        $innodb_buffer_pool_size = 0;
        $innodb_additional_mem_pool_size = 0;
        $innodb_fast_shutdown = 0;
        $innodb_flush_log_at_trx_commit = 0;
        $innodb_locks_unsafe_for_binlog = 0;
        $innodb_log_buffer_size = 0;
        $innodb_log_file_size = 0;
        $innodb_log_files_in_group = 0;
        $innodb_safe_binlog = 0;
        $innodb_thread_concurrency = 0;


        //Calculations
        $percent_innodb_buffer_pool_free = 0;

        foreach ($variables as $item) {
            if($item['Variable_name'] == 'innodb_buffer_pool_size') {
                $innodb_buffer_pool_size = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_additional_mem_pool_size') {
                $innodb_additional_mem_pool_size = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_fast_shutdown') {
                $innodb_fast_shutdown = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_flush_log_at_trx_commit') {
                $innodb_flush_log_at_trx_commit = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_locks_unsafe_for_binlog') {
                $innodb_locks_unsafe_for_binlog = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_log_buffer_size') {
                $innodb_log_buffer_size = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_log_file_size') {
                $innodb_log_file_size = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_log_files_in_group') {
                $innodb_log_files_in_group = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_safe_binlog') {
                $innodb_safe_binlog = $item['Value'];
            }
            if($item['Variable_name'] == 'innodb_thread_concurrency') {
                $innodb_thread_concurrency = $item['Value'];
            }
        }
        foreach ($stat as $item) {
            if($item['Variable_name'] == 'Innodb_buffer_pool_pages_data') {
                $innodb_buffer_pool_pages_data = $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_buffer_pool_pages_misc') {
                $innodb_buffer_pool_pages_misc = $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_buffer_pool_pages_free') {
                $innodb_buffer_pool_pages_free = (int) $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_buffer_pool_pages_total') {
                $innodb_buffer_pool_pages_total = (int) $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_buffer_pool_read_ahead_seq') {
                $innodb_buffer_pool_read_ahead_seq = (int) $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_buffer_pool_read_requests') {
                $innodb_buffer_pool_read_requests = (int) $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_os_log_pending_fsyncs') {
                $innodb_os_log_pending_fsyncs = (int) $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_log_waits') {
                $innodb_log_waits = (int) $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_row_lock_time') {
                $innodb_row_lock_time = (int) $item['Value'];
            }
            if($item['Variable_name'] == 'Innodb_row_lock_waits') {
                $innodb_row_lock_waits = (int) $item['Value'];
            }
        }

        $percent_innodb_buffer_pool_free = $innodb_buffer_pool_pages_free * 100 / $innodb_buffer_pool_pages_total;


        $innodb_indexes = (int) $this->showInnodbIndexes()[0]['var'];
        $innodb_data = (int) $this->showInnodbData()[0]['var'];

        $message1 = "Current InnoDB index space = " . round($innodb_indexes/ 1024 / 1024,2) ." MB<br>";
        $message1 .=  "Current InnoDB data space = " . round($innodb_data/ 1024 / 1024,2) . " MB<br>";
        $message1 .= "Current InnoDB buffer pool free = " . $percent_innodb_buffer_pool_free ." %<br>";
        $message1 .= "Current innodb_buffer_pool_size = " . round($innodb_buffer_pool_size/ 1024 / 1024,2) ." MB<br>";
        $message1 .= "Depending on how much space your innodb indexes take up it may be safe<br>";
        $message1 .= "to increase innodb_buffer_pool_size value to up to 2 / 3 of total system memory<br>";
        $message2 = $this->showPerformanceSchemaStatus();
        $result = ["message1" => $message1, "message2" => $message2];
        return $result;

    }

    /**
     * Run DB tests and Visualize
     *
     * @return object
     */
    public function getPerfChecks() {
        $api = new DBHealthAPI();

        return $this->renderTemplate(
            'perfreport',
            [   'db_connection' =>  $this->dbStatus(),
             'msg' => $this->diskCheck()["msg"],
             'tmp_disk_tables' => $this->diskCheck()["tmp_disk_tables"],
             'created_tmp_tables' => $this->diskCheck()["created_tmp_tables"],
             'queryCacheCheck' => $this->queryCacheCheck(),
             'getBufferpoolTest' => $this->getBufferpoolTest(),
             'memUsage' => $this->memUsage()
            ]
        );

    }

    /**
     * Test db connection time and return time
     *
     * @return int
     */
    public function dbStatus() {
        $time_start = hrtime(true);
        // Get DB connection
        Db::get();
        $time_end = hrtime(true);
        $time = $time_end  - $time_start;
        return  $time/1e+6;
    }

      /**
     * Visualize Table Status Variables
     *
     * @return object
     */
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

      /**
     * Visualize Setting Variables
     *
     * @return object
     */
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
      /**
     * Visualize Status Variables
     *
     * @return object
     */
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
