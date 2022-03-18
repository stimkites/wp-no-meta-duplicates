<?php

namespace Wetail\NoDups;

defined( __NAMESPACE__ . '\LNG') or die();

/**
 * Class _Ajax
 *
 * Handling ajax requests
 */
if(!class_exists(__NAMESPACE__ . '\_Ajax')) :

    class _Ajax{

        /**
         * Initialization
         */
        public static function init(){
            add_action( 'wp_ajax_' . AJAX_H, [ __CLASS__, 'handle_requests' ] );
        }

        private static function auth(){
            if( !wp_verify_nonce( $_POST['nonce'], LNG ) )
                self::response( ['error' => __('Nonce check failure', LNG) ] );
        }

        private static function response( $data ){
            die( json_encode( $data ) );
        }

        /**
         * Handle all incoming AJAX requests
         */
        public static function handle_requests(){
            //check nonce
            self::auth();

            $t = [];

            if($_POST['do'] !== 'get_tables') {
                //prepare assoc array of selected tables
                if (empty($_POST['tables'])) self::response(['error' => __('No data to process!', LNG)]);

                foreach ($_POST['tables'] as $table)
                    $t[$table['t_name']] = $table['t_fields'];
            }

            //switch between actions
            switch($_POST['do']){

                case 'get_tables':
                    ob_start();
                    include PATH . '/templates/tables.php';
                    self::response(['error'=>'','result'=>ob_get_clean()]);
                break;

                case 'check_dups' :
                    global $_db;
                    $r = $_db->check( $t );
                    if(empty($r))
                        self::response( [ 'error' => __('Something went wrong with SQL', LNG)
                            .' '. print_r( $_POST['tables'], true ) ] );
                    else {
                        //back to normal array
                        $rr = [];
                        foreach($r as $t_name=>$dups)
                            $rr[] = [ 't_name' => $t_name, 'dups' => $dups ];
                        self::response(['error' => '', 'result' => $rr]);
                    }
                break;

                case 'clean_dups' :
                    global $_db;
                    self::response(['error' => '', 'result' => $_db->cleanup( $t )]);
                break;

                case 'enable_triggers' :
                    global $_db;
                    if(!$_db->enable_triggers( $t ))
                        self::response(['error'=>__('Triggers could not be enabled!', LNG)]);

                case 'disable_triggers':
                    if($_POST['do'] === 'disable_triggers'){
                        global $_db;
                        if(!$_db->disable_triggers( $t ))
                            self::response(['error'=>__('Triggers could not be disabled!', LNG)]);
                    }

                case 'get_triggers' :
                    global $_db;
                    $r = $_db->get_triggers_state( $t );
                    if(empty($r))
                        self::response( [ 'error' => __('Something went wrong with SQL', LNG)
                            .' '. print_r( $_POST['tables'], true ) ] );
                    else {
                        //back to normal array
                        $rr = [];
                        foreach($r as $t_name=>$state)
                            $rr[] = [ 't_name' => $t_name, 'state' => $state ];
                        self::response(['error' => '', 'result' => $rr]);
                    }
                break;

            }
            self::response(['error' => sprintf( __( 'No ajax action found for the request "%s"', LNG ), $_POST['do'] ) ]);
        }

    }

endif;