<?php

namespace Wetail\NoDups;

defined( __NAMESPACE__ . '\LNG') or die();

if(!class_exists(__NAMESPACE__ . '\_db_Controller')) :

    class _db_Controller{
        
        const trefix = 'wetail_nodups_'; //trigger prefix

        //Connection flag
        private $is_connected=false;

        //Mysqli-object
        private $mysql = null;

        //Word press prefix
        private $wpr = "wp_";

        //DB name we are connected to
        private $dbname = '';

        /**
         * Last query result to free on new query
         *
         * @var \mysqli_result $result
         */
        private $result = null;

        /**
         * Connecting using WP credentials
         */
        public function __construct() {
            $this->mysql = new \mysqli( \DB_HOST, \DB_USER, \DB_PASSWORD, \DB_NAME );
            if ($this->mysql->connect_error)
                die( 'Connect Error (' . $this->mysql->connect_errno . ') ' . $this->mysql->connect_error );
            else{
                $this->is_connected = true;
                $this->mysql->set_charset("utf8");
                $this->dbname = \DB_NAME;
                global $table_prefix;
                if(isset($table_prefix))
                    $this->wpr = $table_prefix;
                else{
                    global $wpdb;
                    if(isset($wpdb->prefix))
                        $this->wpr = $wpdb->prefix;
                }
            }
        }

        /**
         * Run a direct sql injection
         *
         * @param $sql
         * @return \mysqli_result | bool
         */
        public function query( $sql ){
            ($this->is_connected) or die('Not connected');
            if( is_callable( [ $this->result, 'free_result' ] ) )
                $this->result->free_result();
            try {
                $this->mysql->query( "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))" );  
                $this->result = $this->mysql->query( $sql, \MYSQLI_USE_RESULT );
            }catch( \Exception $e ){
                global $_log;
                $_log->write_log( '[FATAL SQL] CAUGHT AN EXCEPTION ON QUERY ['.$sql.'] '
                    . $e->getCode() . ' ' . $e->getMessage()
                );
                return false;
            }
            if($this->mysql->error){
                global $_log;
                $_log->write_log('[FATAL SQL] PROCESSING QUERY ['.$sql.'] MYSQL ERROR: ' . $this->mysql->error);
                return false;
            }
            return $this->result;
        }

        /**
         * Run a direct sql injections and return total affected rows
         *
         * @param array $sqls
         * @return int
         */
        public function get_query_res( $sqls ){
            ($this->is_connected) or die('Not connected');
            if(!is_array($sqls))
                $sqls = [$sqls];
            $total_affected = 0;
            foreach($sqls as $sql){
                if ($this->query( $sql ))
                    $total_affected += $this->mysql->affected_rows;
            }
            return $total_affected;
        }

        /**
         * Get all meta table names
         *
         * @return array
         */
        private function get_all_tables(){
            ($this->is_connected) or die('Not connected');
            $r = $this->query("SELECT `TABLE_NAME` as `name` 
                                   FROM `INFORMATION_SCHEMA`.`TABLES`
                                  WHERE `TABLE_SCHEMA` = '{$this->dbname}'
                                    AND `TABLE_NAME` like '%meta%'");
            return ( $r ? $r->fetch_all(\MYSQLI_ASSOC) : [] );
        }

        /**
         * Get all table fields for a definite table
         *
         * Used to create "INSERT" body query for logging on corresponding trigger
         *
         * @param $t_name
         * @return array
         */
        private function get_all_table_fields( $t_name ){
            ($this->is_connected) or die('Not connected');
            $sql = "SELECT `COLUMN_NAME` as `field` 
                      FROM `INFORMATION_SCHEMA`.`COLUMNS`
                     WHERE `TABLE_SCHEMA` = '{$this->dbname}'
                       AND `TABLE_NAME` = '$t_name'";
            $r = $this->query( $sql );
            if(!$r) return [];
            return array_map( function($a){ return $a['field']; }, $r->fetch_all(\MYSQLI_ASSOC) );
        }

        /**
         * Fetch total rows number
         *
         * @param $t_name
         * @return int
         */
        private function get_total_rows( $t_name ){
            $r = $this->query("SELECT COUNT(*) FROM `$t_name`");
            if(!$r) return 0;
            return $r->fetch_row()[0];
        }

        /**
         * Fetch all tables we may process
         *
         * @return array
         */
        public function get_tables(){
            $all_tables = $this->get_all_tables();
            $r = [];
            foreach( $all_tables as $t ){
                $t_name = $t['name'];
                $fields = $this->get_all_table_fields( $t_name );
                if ( count($fields) !== 4 || !( $fields[2] === 'meta_key' && $fields[3] === 'meta_value' ) ) continue;
                $r[$t_name] = [ 'fields' => $fields, 'total' => $this->get_total_rows( $t_name ) ];
            }
            return $r;
        }


        /**
         * Enable triggers
         *
         * @param array $selected_tables
         * @return bool
         */
        public function enable_triggers( $selected_tables ){
            ( $this->is_connected ) or die( 'Not connected' );
            try{
                foreach( $selected_tables as $t_name=>$fields ){
                    $trigger = self::trefix . $t_name;
                    $this->query( "DROP TRIGGER IF EXISTS `{$trigger}`" );
                    $this->query( "CREATE TRIGGER `{$trigger}` BEFORE INSERT ON `{$t_name}`
                               FOR EACH ROW
                               BEGIN
                                 DECLARE found INT;
                                 SELECT {$fields[0]} INTO found 
                                 FROM {$t_name} 
                                 WHERE {$fields[1]} = new.{$fields[1]} 
                                   AND {$fields[2]} = new.{$fields[2]} 
                                   AND {$fields[3]} = new.{$fields[3]};
                                 IF found THEN
                                     SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No duplicates allowed!';
                                 END IF;
                               END;" );
                }
            }catch( \Exception $e ){
                global $_log;
                $_log->write_log( '[FATAL] COULD NOT ENABLE TRIGGERS! ' . $e->getCode() . ' ' . $e->getMessage() );
                return false;
            }
            return true;
        }


        /**
         * Automatically remove all triggers
         *
         * @param $selected_tables
         * @return bool
         */
        public function disable_triggers( $selected_tables ){
            ($this->is_connected) or die('Not connected');
            try{
                foreach( $selected_tables as $t_name=>$dummy ) {
                    $this->query("DROP TRIGGER IF EXISTS `" . self::trefix . $t_name . "`");
                }
            }catch( \Exception $e ){
                global $_log;
                $_log->write_log( '[FATAL] COULD NOT DISABLE TRIGGERS! ' . $e->getCode() . ' ' . $e->getMessage() );
                return false;
            }
            return true;
        }

        /**
         * Get triggers state for selected table
         *
         * @param array $selected_tables
         * @return array
         */
        public function get_triggers_state( $selected_tables ){
            ($this->is_connected) or die('Not connected');
            $rz = [];
            foreach ($selected_tables as $t_name=>$dummy ){
                if( ! ( $r = $this->query("SHOW TRIGGERS WHERE `Trigger` = '" . self::trefix . $t_name . "'" ) ) ) {
                    $rz[$t_name] = false;
                    continue;
                }
                $rz[$t_name] = !empty($r->fetch_row());
            }
            return $rz;
        }

        /**
         * Check database duplicates via ajax request
         *
         * @param $selected_tables
         * @return array
         */
        public function check( $selected_tables ){
            ($this->is_connected) or die('Not connected');
            $res = [];
            foreach ( $selected_tables as $t_name=>$fields ) {
                $sql = "SELECT SUM( Duplicates) 
                         FROM ( 
                            SELECT *, count(*) as Duplicates 
                              FROM `{$t_name}` 
                             WHERE {$fields[0]} NOT IN ( 
                                SELECT {$fields[0]} FROM ( 
                                    SELECT MIN({$fields[0]}) as {$fields[0]}, {$fields[1]}, {$fields[2]}, {$fields[3]} 
                                      FROM `{$t_name}` 
                                  GROUP BY {$fields[1]}, {$fields[2]} 
                                ) p 
                             ) 
                             GROUP BY {$fields[1]}, {$fields[2]}, {$fields[3]} 
                               HAVING Duplicates > 0 
                         ) t1";
                $r = $this->query( $sql );
                if (!$r)
                    $res[$t_name] = 'ERROR IN RUNNING SQL: ' . $sql;
                else {
                    $rz = $r->fetch_row();
                    $res[$t_name] = ( $rz[0] ? $rz[0] : 0 ) ;
                }
            }
            return $res;
        }

        /**
         * Cleanup tables from duplicates
         *
         * @param $selected_tables
         * @return int
         */
        public function cleanup( $selected_tables ){
            ($this->is_connected) or die('Not connected');
            $sqls = [];
            foreach ( $selected_tables as $t_name=>$fields ) {
                $tmp_name = $t_name . '_nodups_tmp';
                $sqls[] = "DROP TABLE IF EXISTS `{$tmp_name}`";
                $sqls[] = "CREATE TABLE `{$tmp_name}` LIKE `{$t_name}`";
                $sqls[] = "INSERT INTO `{$tmp_name}`
                                SELECT MIN({$fields[0]}) as {$fields[0]}, {$fields[1]}, {$fields[2]}, {$fields[3]} 
                                  FROM `{$t_name}`
                              GROUP BY {$fields[1]}, {$fields[2]}";
                $sqls[] = "DROP TABLE `{$t_name}`";
                $sqls[] = "RENAME TABLE `{$tmp_name}` TO `{$t_name}`";
            }
            return $this->get_query_res( $sqls );
        }

    }

endif;
