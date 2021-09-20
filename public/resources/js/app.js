// app.js


function flow_check_if_promise (obj) {
    const normal = !!obj && typeof obj === 'object' &&
        ((obj.constructor && obj.constructor.name === 'Promise') || typeof obj.then === 'function');

    const fnForm = !!obj  && (typeof obj === 'function') &&
        ((obj.name === 'Promise') || (typeof obj.resolve === 'function') && (typeof obj.reject === 'function'));

    return normal || fnForm;
}

jQuery(function(){
    refresh_auto_formatted_times();
});

function refresh_auto_formatted_times() {
    $('.flow-long-date-time').each(function() {
        let that = $(this);
        let timestamp = that.data('ts');
        if (!timestamp ) {return;}
        let number_of_mine = parseInt(timestamp.toString());
        if (!number_of_mine || number_of_mine < 0) {return;}
        let date = new Date(number_of_mine*1000);
        let options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',hour: 'numeric', minute: 'numeric' };
        let ss = new Intl.DateTimeFormat('en-US',options);
        let words = ss.format(date);
        that.text(words);

    })
}

/**
 *
 * @param {FlowToken} tok
 */
function update_root_flow_ajax_token(tok) {
    let da_div = $("div#flow-ajax-token");
    let token_csrf_index_input = da_div.find('input[name="_CSRF_INDEX"]');
    let token_csrf_token_input = da_div.find('input[name="_CSRF_TOKEN"]');

    token_csrf_index_input.val(tok._CSRF_INDEX);
    token_csrf_token_input.val(tok._CSRF_TOKEN);
}

/**
 *
 * @param {Object} obj
 */
function set_object_with_flow_ajax_token_data(obj) {
    let da_div = $("div#flow-ajax-token");
    let token_csrf_index_input = da_div.find('input[name="_CSRF_INDEX"]');
    let token_csrf_token_input = da_div.find('input[name="_CSRF_TOKEN"]');
    obj._CSRF_INDEX = token_csrf_index_input.val();
    obj._CSRF_TOKEN = token_csrf_token_input.val();
}

/**
 *
 * @param data
 * @param {?string} success_title
 * @param {?string} fail_title
 * @returns {FlowBasicResponse}
 */
function process_ajax_response(data,success_title,fail_title) {
    /**
     * @type {FlowBasicResponse}
     */
    let ret;

    if (flow_check_if_promise(data)) {
        //console.debug('promise passed in for process_response_if_error',data);
        if ('responseJSON' in data ) {
            ret = data.responseJSON;
            ret.success = false;
            if (!ret.message) {
                ret.message = '';
                if ("error" in ret) {

                    if ("type" in ret.error) {
                        ret.message += ret.error.type + " ";
                    }

                    if ("title" in ret.error) {
                        ret.message += ret.error.title + ": ";
                    }

                    if ("description" in ret.error) {
                        ret.message += "<br>" + ret.error.description;
                    }
                }
            } //end making message if not exists

            if (!ret.token) { ret.token = null; }
        } else if ( 'responseText' in data ) {
            try {
                ret = JSON.parse(data.responseText);
                ret.success = false;
                if (!ret.message) {
                    ret.message = data.responseText;
                }
            } catch (err) {
                ret = {success: false, message: data.responseText,tag: null, token: null}
            }
        }

        else {
            ret = {
                success: false,
                message: data.statusText,
                tag: null,
                token: null
            };
        }


    } else {
        ret = data;
    }

    if (ret && ret.token) {
        update_root_flow_ajax_token(ret.token);
    }

    if (ret.success && success_title) {
        do_toast({
            title:success_title,
            content: ret.message,
            delay:5000,
            type:'success'
        });
    } else {
        if (fail_title) {
            do_toast({
                title:fail_title,
                subtitle:'There was an issue with the ajax',
                content: ret.message,
                delay:20000,
                type:'error'
            });
        }
        console.warn(ret);
    }

    return ret;
}

function utterly_destroy_select2(bare_select_control) {

    bare_select_control.val(null).trigger('change');

    bare_select_control.select2('destroy');

    // Unbind the event
    bare_select_control.off('select2:select');

    //clear the options
    $('select#flow-find-user-list').empty();
}

function toggle_action_spinner(me,state) {
    if (state === 'loading') {
        me.find('.flow-action').hide();
        me.find('.flow-spinner').show();
    } else if (state === 'normal') {
        me.find('.flow-action').show();
        me.find('.flow-spinner').hide();
    } else {
        console.warn("invalid state for spinner things");
    }
}

/**
 * {Swal}
 */
let my_swal = null;

jQuery(function() {
   // noinspection JSUnresolvedVariable
    my_swal = SweetAlert;

    let clipboard = new ClipboardJS('.clipy-btn');

    clipboard.on('success', function(e) {
        //e.text e.action

        let message = $(e.trigger).data('popover_on_success');
        let popover = new bootstrap.Popover(e.trigger,{
            content: message
        });
        popover.show();
        setTimeout(function() {
            popover.dispose();
        },2000);
        e.clearSelection();
    });

    clipboard.on('error', function(e) {
        //e.text e.action

        let message = $(e.trigger).data('popover_on_fail');
        let popover = new bootstrap.Popover(e.trigger,{
            content: message
        });
        popover.show();
        setTimeout(function() {
             popover.dispose();
        },2000);
        e.clearSelection();
    });
});