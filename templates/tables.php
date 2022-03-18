<?php

namespace Wetail\NoDups;

defined( __NAMESPACE__ . '\LNG') or die();

/**
 * Template: tables list
 */

?>
<table class="widefat listing">
    <thead>
    <tr>
        <th>
            <input type="checkbox" id="select-all-tables" title="<?php _e('Select all tables', LNG) ?>"/>
        </th>
        <th width="40%">
            <?php _e('Table', LNG) ?>
        </th>
        <th>
            <?php _e('Rows', LNG) ?>
        </th>
        <th>
            <?php _e('Duplicates found') ?>
        </th>
        <th>
            <?php _e('Trigger state', LNG) ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php
        global $_db;
        $tables_found = $_db->get_tables();
        if($tables_found) {
            foreach ( $tables_found as $t_name => $t_item ) {
                echo '<tr>
                        <td>
                            <input type="checkbox" class="t-selected" value="' . implode('|',$t_item['fields']) . '" id="' . $t_name . '" /> 
                        </td>
                        <td>
                            ' . $t_name . '
                        </td>
                        <td>
                            ' . $t_item['total'] .' 
                        </td>
                        <td>
                            <span id="'.$t_name.'_dups" class="dups-count">' . __('Not checked',LNG) . '</span>
                        </td>
                        <td id="'.$t_name.'_trigger">
                            <span class="trigger-state unknown"></span>
                        </td>
                      </tr>';
            }
        } else {
            echo '<tr><td colspan="100%">'.__('No meta tables found!', LNG).'</td></tr>';
        }
    ?>
    </tbody>
</table>
