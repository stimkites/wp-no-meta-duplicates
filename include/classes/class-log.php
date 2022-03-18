<?php

namespace Wetail\NoDups;

defined( __NAMESPACE__ . '\LNG') or die();

if(!class_exists(__NAMESPACE__ . '\_Log')) :

    /**
     * Basic Log writer
     */
    class _Log {

        //path to log file
        const LOGPATH = PATH . '/logs/';

        //log file maximum size
        const MAXSIZE = 3145728;            //3 Mb

        //log file name and log time
        private $log_file_name, $log_time;

        /**
         * Amount of log files kept on the server (amount of days)
         */
        const log_days = 7; //week

        /**
         * _Log constructor.
         *
         * Making log file name and checking if file size exceeds the maximum size
         */
        public function __construct(){
            self::check_logs_num();
            $this->log_file_name = 'log_'.strftime("%d_%m_%Y").'.txt';
            $this->log_time = strftime("%d_%m_%Y %H:%M:%S");
            //check log file size
            $add_suffix = '_';
            do{
                $check = 0;
                if(file_exists($this->log_file_name))
                    if(filesize($this->log_file_name)>self::MAXSIZE) {
                        $this->log_file_name = 'log_'.strftime("%d_%m_%Y").$add_suffix.'.txt';
                        $check = 1;
                        $add_suffix.=$add_suffix;
                    }
            }while ($check);
            $this->log_file_name = self::LOGPATH.$this->log_file_name;
        }

        /**
         * Prevent from trashing the server with too many log files
         */
        private static function check_logs_num(){
            $mask = self::LOGPATH . 'log_*.txt';
            $all_files = glob( $mask );
            sort( $all_files, SORT_STRING );
            while(count($all_files) > self::log_days){
                unlink( $all_files[0] );
                $all_files = glob( $mask );
            }
        }


        /**
         * Writer
         *
         * @param $data
         */
        public function write_log($data ){
            file_put_contents( $this->log_file_name,'['.$this->log_time .
                ' MEM: ' . round(memory_get_usage(true)/1024/1024) .' Mb] '.$data."\r\n\r\n", FILE_APPEND);
        }

        /**
         * Cleaner
         *
         * @param $file_name
         */
        public function clear_log($file_name ){
            unlink( $file_name );
            file_put_contents( $file_name, '');
        }

        /**
         * Deleting log
         *
         * @param $file_name
         */
        public function remove_log($file_name ){
            unlink( $file_name );
        }

    }

endif;