<?php

namespace Wetail\NoDups;

defined( __NAMESPACE__ . '\LNG') or die();

/**
 * Template: clean duplicates action form
 */

?>

<!-- basic settings !-->
<p class="clear"></p>

<div class="wrap w-nodups-wrap">

    <hr class="wp-header-end">

    <h1 class="inline-header"><?php _e('Clean Database from Meta duplicates',LNG); ?></h1>

    <div class="actions">
        <form method="post" id="actions-form" action=" " enctype="multipart/form-data">
            <label><?php _e('Action', LNG) ?>
                <select id="table-action" class="table-action">
                    <option selected disabled value="-1">...</option>
                    <option value="check_dups"><?php _e('Check duplicates', LNG) ?></option>
                    <option value="clean_dups"><?php _e('Remove all duplicates', LNG) ?></option>
                    <option value="check_triggers"><?php _e('Check triggers', LNG) ?></option>
                    <option value="enable_triggers"><?php _e('Enable triggers', LNG) ?></option>
                    <option value="disable_triggers"><?php _e('Disable triggers', LNG) ?></option>
                </select>
            </label>
            <button class="button button-primary" id="do-table-action"><?php _e('Perform', LNG) ?></button>
            <input type=checkbox name="do-sequentely" id="do-sequentely"> <label for="do-sequentely"> Do sequentely</label>
        </form>
    </div>

    <p><?php _e( 'To perform an action select desired tables listed below. Select all if not sure. '
                .'Refer to "Help" if you have more questions.', LNG) ?></p>

    <p class="hint"><?php _e('It is strongly recommended to make a database backup before cleaning!', LNG) ?></p>

    <div class="clear"></div>

    <div id="cleanup-result" class="notice notice-is-dismissible notice-warning hid">
        <p><?php _e('Database was cleaned. Total rows processed:', LNG) ?> <span id="cleanup-result-num">0</span></p>
    </div>

    <div class="tables-list">
        <div id="list-content">
            <p><span class="spinner vis"></span><?php _e('Fetching all existing meta tables...', LNG) ?></p>
        </div>
        <div class="too-long hid">
            <?php _e('Action is longer than usual..', LNG); ?>
            <a onclick="location.reload(true)"><?php _e('Reload the page now', LNG) ?></a>
        </div>
    </div>

    <div class="clear"></div>

    <hr/>

</div>
