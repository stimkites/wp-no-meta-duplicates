<?php

namespace Wetail\NoDups;

defined( __NAMESPACE__ . '\LNG') or die();

if(!class_exists(__NAMESPACE__ . '\_Admin')) :
    
     /**
      * Class _Admin
      *
      * Back-end plugin UI
      */
     class _Admin{

         const setting = 'wetail-nodups-settings'; //settings page slug in back-end
    
         /**
          * Adding actions
          */
         public static function init(){
             add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
             add_action( 'admin_enqueue_scripts', [ __CLASS__, 'add_scripts' ], 999 );
             add_filter( 'plugin_action_links_' . plugin_basename( INDEX ), [ __CLASS__, 'setting_link' ] );
         }

         /**
          * Link to backend settings
          *
          * @param $l
          * @return array
          */
         public static function setting_link( $l ) {
             $plugin_links = array(
                 '<a href="' . admin_url( 'options-general.php?page=' . self::setting ) . '">' . __( 'Clean DB', LNG ) . '</a>'
             );
             return array_merge( $plugin_links, $l );
         }
    
         /**
          * Add styles and scripts to back-end
          */
         public static function add_scripts(){
             if( !isset($_GET['page']) || self::setting !== $_GET['page'] ) return;
             wp_enqueue_style(   'wtndups_be_css', URL . '/assets/css/styles.css', null, time() );
             wp_register_script( 'wtndups_be_js',  URL . '/assets/js/default.js', ['jquery'] );
             wp_enqueue_script(  'wtndups_be_js',  URL . '/assets/js/default.js', ['jquery'], time(), false );
             wp_localize_script( 'wtndups_be_js', 'wtnd_ajax', [
                 'nonce'        => wp_create_nonce( LNG ),
                 'action'       => AJAX_H
             ] );
             //just in case
             if( !wp_style_is( 'woocommerce_admin_styles' ) ) wp_enqueue_style( 'woocommerce_admin_styles' );
             if( !wp_script_is( 'wc-enhanced-select' ) ) wp_enqueue_script( 'wc-enhanced-select' );
         }
    
         /**
          * Adding admin menus
          */
         public static function add_admin_menu(){
    
             $admin_page = add_management_page(
                 __( 'Clean DB Duplicates', LNG),
                 __( 'Clean DB Duplicates', LNG),
                 'manage_options',
                 self::setting,
                 function (){
                    include PATH . '/templates/plugin-settings.php';
                 }
             );

             //Load help
             add_action( 'load-'.$admin_page,	[ __CLASS__, 'load_help_tab' ] );

         }

         /**
          * Load help file into help tab
          */
         public static function load_help_tab(){
             $screen = get_current_screen();
             $help = file_get_contents( PATH . "/assets/help/help.html" );
             $ti = 0;
             $pos = strpos( $help, '<tab>' );
             while( false !== $pos ){
                 $title_start = strpos( $help, '<h3>', $pos ) + 4;
                 $title = substr( $help, $title_start, strpos( $help, '</h3>', $title_start ) - $title_start );
                 $end_content = strpos( $help, '</tab>', $pos + 5 );
                 $content = substr( $help, $pos, $end_content - $pos );
                 $screen->add_help_tab( [
                     'id'	=> 'wtndups_help_tab_' . ++$ti,
                     'title'	=> $title,
                     'content'	=> $content
                 ] );
                 $pos = strpos( $help, '<tab>', $end_content + 6 );
             }
         }

     }

 endif;