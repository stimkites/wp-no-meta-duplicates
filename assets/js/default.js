let __wtnd = (function($){

    let runningTasks=0;

    const sleep = (milliseconds) => {
      return new Promise(resolve => setTimeout(resolve, milliseconds))
    }

    let render_list = function( data ){
            $('#list-content').html( data );
            $('#select-all-tables').off().change(function(){
                $('.t-selected').prop('checked', $(this).prop('checked'));
            });
        },

        get_tables = function(){
            $.ajax({
                url: 	ajaxurl,
                data:	{
                    action :   wtnd_ajax.action,
                    do     :   'get_tables',
                    nonce  :   wtnd_ajax.nonce
                },
                type:	'post',
                dataType: 'json',
                success: function(data){
                    if(data.error)
                        render_list( data.error );
                    else
                        render_list( data.result );
                },
                error: function(a,b,error){
                    if(error)
                        render_list( error );
                }
            });
        },

        collect = function(){
            let r = [];
            $('.t-selected:checked').each(function(){
                r.push( { t_name : this.id, t_fields : this.value.split("|") } );
            });
            return r;
        },

        disable_actions = function(){
            $('#actions-form select, #actions-form button').attr( 'disabled', 'disabled' );
        },

        enable_actions = function(){
            $('#actions-form select, #actions-form button').removeAttr( 'disabled' );
        },

        triggers_result = function( d ){
            if( d.error )
                alert( d.error );
            else {
                for (let i = 0; i < d.result.length; i++)
                    $('#' + d.result[i].t_name + '_trigger span')
                            .removeClass('spinner vis')
                            .addClass( 'trigger-state ' + ( d.result[i].state ? 'on' : 'off' ) );
            }
        },

        do_ajax = function(todo,tables,
                           on_complete=function(){},
                           on_success=function(d){},
                           on_error=function(a,b,error){}
                           ){
            $.ajax({
                url: 	ajaxurl,
                data:	{
                    action :   wtnd_ajax.action,
                    do     :   todo,
                    tables :   tables,
                    nonce  :   wtnd_ajax.nonce
                },
                type:	'post',
                dataType: 'json',
                complete: on_complete,
                success: on_success,
                error: on_error
            });

        },

        startTask = function(){
            runningTasks++;
            disable_actions();
        },

        endTask = function(){
            if( --runningTasks <= 0 ) {
                runningTasks=0;
                enable_actions();
            }
        },

        actions = {

            check_dups : function(do_sequentely = true){
                let selected = collect();
                if (!selected.length) return;
                for( let i = 0; i < selected.length; i ++ )
                {
                    let toDo = function () {
                        startTask();
                        $('#' + selected[i].t_name + '_dups').html('<span class="spinner vis"></span>');
                        $.ajax({
                            url: ajaxurl,
                            data: {
                                action: wtnd_ajax.action,
                                do: 'check_dups',
                                tables: [selected[i]],
                                nonce: wtnd_ajax.nonce
                            },
                            type: 'post',
                            dataType: 'json',
                            complete: function () {
                                endTask();
                            },
                            success: function (d) {
                                if (d.error)
                                    alert(d.error);
                                else {
                                    for (let i = 0; i < d.result.length; i++)
                                        $('#' + d.result[i].t_name + '_dups').html(d.result[i].dups);
                                }
                            },
                            error: function (a, b, error) {
                                if (error)
                                    alert(error);
                            }
                        });
                    }

                    if (do_sequentely) {
                        const process = async () => {
                            console.log("RT:" + runningTasks);
                            while (runningTasks > 0) {
                                await sleep(50);
                            }
                            toDo();
                        }
                        process();
                    }
                    else {
                        toDo();
                    }
                }
            },

            clean_dups : function(do_sequentely = true){
                let selected = collect();
                if (!selected.length) return;
                for( let i = 0; i < selected.length; i ++ )
                {
                    let toDo = function () {
                        startTask();
                        $('#' + selected[i].t_name + '_dups').html('<span class="spinner vis"></span>');
                        $.ajax({
                            url: ajaxurl,
                            data: {
                                action: wtnd_ajax.action,
                                do: 'clean_dups',
                                tables: [selected[i]],
                                nonce: wtnd_ajax.nonce
                            },
                            type: 'post',
                            dataType: 'json',
                            complete: function () {
                                endTask();
                            },
                            success: function (d) {
                                if (d.error)
                                    alert(d.error);
                                else {
                                    $('#cleanup-result').removeClass('hid');
                                    $('#cleanup-result-num').html(d.result);
                                    get_tables();
                                }
                            },
                            error: function (a, b, error) {
                                if (error)
                                    alert(error);
                            }
                        });
                    }

                    if (do_sequentely) {
                        const process = async () => {
                            console.log("RT:" + runningTasks);
                            while (runningTasks > 0) {
                                await sleep(50);
                            }
                            toDo();
                        }
                        process();
                    }
                    else {
                        toDo();
                    }
                }
            },

            check_triggers : function(do_sequentely = true){
                let selected = collect();
                if (!selected.length) return;
                for( let i = 0; i < selected.length; i ++ )
                {
                    startTask();
                    $('#' + selected[i].t_name + '_trigger span')
                        .removeClass('trigger-state on off unknown')
                        .addClass('spinner vis');
                    $.ajax({
                        url: 	ajaxurl,
                        data:	{
                            action :   wtnd_ajax.action,
                            do     :   'get_triggers',
                            tables :   [selected[i]],
                            nonce  :   wtnd_ajax.nonce
                        },
                        type:	'post',
                        dataType: 'json',
                        complete: function(){
                            endTask();
                        },
                        success: function( d ){
                            triggers_result( d );
                        },
                        error: function(a,b,error){
                            if(error)
                                alert(error);
                        }
                    });
                }
            },

            enable_triggers : function(do_sequentely = true){
                let selected = collect();
                if (!selected.length) return;
                for( let i = 0; i < selected.length; i ++ )
                {
                    startTask();
                    $('#' + selected[i].t_name + '_trigger span')
                        .removeClass('trigger-state on off unknown')
                        .addClass('spinner vis');
                    $.ajax({
                        url: 	ajaxurl,
                        data:	{
                            action :   wtnd_ajax.action,
                            do     :   'enable_triggers',
                            tables :   [selected[i]],
                            nonce  :   wtnd_ajax.nonce
                        },
                        type:	'post',
                        dataType: 'json',
                        complete: function(){
                            endTask();
                        },
                        success: function( d ){
                            triggers_result( d );
                        },
                        error: function(a,b,error){
                            if(error)
                                alert(error);
                        }
                    });
                }
            },

            disable_triggers : function(do_sequentely = true){
                let selected = collect();
                if (!selected.length) return;
                for( let i = 0; i < selected.length; i ++ )
                {
                    startTask();
                    $('#' + selected[i].t_name + '_trigger span')
                        .removeClass('trigger-state on off unknown')
                        .addClass('spinner vis');
                    $.ajax({
                        url: 	ajaxurl,
                        data:	{
                            action :   wtnd_ajax.action,
                            do     :   'disable_triggers',
                            tables :   [selected[i]],
                            nonce  :   wtnd_ajax.nonce
                        },
                        type:	'post',
                        dataType: 'json',
                        complete: function(){
                            endTask();
                        },
                        success: function( d ){
                            triggers_result( d );
                        },
                        error: function(a,b,error){
                            if(error)
                                alert(error);
                        }
                    });
                }
            }
        };

    return {

        init : function(){

            /**
             * Assign main events
             */
            $(document).ready(function(){

                setTimeout( get_tables, 500 );

                $('#do-table-action').click(function(e){
                    let f = $('#table-action').val();
                    let do_sequentely = $('#do-sequentely').is(":checked");
                    if( 'undefined' !== typeof actions[f] )
                    {
        	                actions[f](do_sequentely);
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });

            });
        }

    }

})(jQuery);

__wtnd.init();
