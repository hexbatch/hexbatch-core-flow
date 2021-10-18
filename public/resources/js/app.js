// app.js


function flow_check_if_promise (obj) {
    const normal = !!obj && typeof obj === 'object' &&
        ((obj.constructor && obj.constructor.name === 'Promise') || typeof obj.then === 'function');

    const fnForm = !!obj  && (typeof obj === 'function') &&
        ((obj.name === 'Promise') || (typeof obj.resolve === 'function') && (typeof obj.reject === 'function'));

    return normal || fnForm;
}

jQuery(function($){
    $('.flow-long-date-time').each(function() {
        let that = $(this);
        let timestamp = that.data('ts');
        let date = new Date(timestamp*1000);
        let options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',hour: 'numeric', minute: 'numeric' };
        let ss = new Intl.DateTimeFormat('en-US',options);
        let words = ss.format(date);
        that.text(words);

    })

});

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