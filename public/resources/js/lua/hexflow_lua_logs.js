/**
 * @param {?FlowBasicLuaResponse}  feedback
 */
function lau_return_to_logs(feedback) {
    /**
     * @type {LuaLog[]}
     */
    this.info = [];

    /**
     * @type {LuaLog[]}
     */
    this.debug = [];

    /**
     * @type {LuaLog[]}
     */
    this.warning = [];

    /**
     * @type {LuaLog[]}
     */
    this.error = [];
    if (!feedback) {return;}

    for(let i = 0; i < feedback.logs.length ; i++) {
        let enter = feedback.logs[i];
        switch (enter.level) {
            case 'debug': {
                this.debug.push(enter);
                break;
            }
            case 'info': {
                this.info.push(enter);
                break;
            }
            case 'warning': {
                this.warning.push(enter);
                break;
            }
            case 'error': {
                this.error.push(enter);
                break;
            }
            default: {
                console.warn("Log type not recognized", enter);
            }
        }
    }

    if (feedback.hasOwnProperty('error')) {
        this.error.push({
            level:'error',
            pre: `${feedback.error.type?? 'No Type Error'} ${feedback.error.title?? 'No Title'} `,
            data: feedback.error
        });
    }

    if (feedback.hasOwnProperty('result')) {
        if (!_.isEmpty(feedback.result)) {
            this.error.push({
                level:'info',
                pre: `Script Returned`,
                data: feedback.result
            });
        }
    }

}

let log_view_prefs = {
    debug: true,
    info: true,
    warning: true,
    error: true,
}

/**
 * @type {lau_return_to_logs}
 */
let processed_logs = null;

function draw_logs() {
    let log_home = $(`.hexflow-lua-logs-holder`);
    log_home.html('');
    let panes = lau_logs_to_panels(processed_logs);
    for(let i = 0; i < panes.length ; i++) {
        log_home.append(panes[i]);
    }

    function do_badge(log_type) {
        let log_count = processed_logs[log_type].length;
        let badge = $(`#hexflow-show-${log_type}-logs + label .hexflow-number-logs .badge`);
        badge.text(log_count);
        if (log_count) {
            badge.show();
        } else {
            badge.hide();
        }
    }

    do_badge('debug');
    do_badge('info');
    do_badge('warning');
    do_badge('error');

}

/**
 *
 * @param {lau_return_to_logs} logs
 * @return {string[]}
 */
function lau_logs_to_panels(logs) {

    function make_div(card_class,title,body) {

        let pre_processed,body_processed;
        try {
            pre_processed = JSON.parse(body);
        } catch (error) {
            pre_processed = body;
        }
        try {
            body_processed = JSON.stringify(pre_processed, null, 2);
        } catch (error) {
            body_processed = pre_processed
        }

        let what =  `
            <div class="card ${card_class}">
                <div class="card-header">${title}</div>
                <div class="card-body"><pre>${body_processed}</pre></div>
            </div>
        `;
        return what;
    }

    /**
     *
     * @type {string[]}
     */
    let ret = [];

    if (log_view_prefs.debug) {
        for(let i = 0; i < logs.debug.length ; i++) {
            let da_log = logs.debug[i];
            let pane = make_div('text-white bg-dark',da_log.pre,da_log.data);
            ret.push(pane);
        }
    }


    if (log_view_prefs.info) {
        for(let i = 0; i < logs.info.length ; i++) {
            let da_log = logs.info[i];
            let pane = make_div('text-dark bg-info',da_log.pre,da_log.data);
            ret.push(pane);
        }
    }


    if (log_view_prefs.warning) {
        for(let i = 0; i < logs.warning.length ; i++) {
            let da_log = logs.warning[i];
            let pane = make_div('text-dark bg-warning',da_log.pre,da_log.data);
            ret.push(pane);
        }
    }

    if (log_view_prefs.error) {
        for(let i = 0; i < logs.error.length ; i++) {
            let da_log = logs.error[i];
            let pane = make_div('text-white bg-danger',da_log.pre,da_log.data);
            ret.push(pane);
        }
    }

    return ret;

}


jQuery(function($){
    $('input#hexflow-show-debug-logs').on('change',function() {
        log_view_prefs.debug = this.checked;
        draw_logs()
    });

    $('input#hexflow-show-info-logs').on('change',function() {
        log_view_prefs.info = this.checked;
        draw_logs()
    });

    $('input#hexflow-show-warning-logs').on('change',function() {
        log_view_prefs.warning = this.checked;
        draw_logs()
    });

    $('input#hexflow-show-error-logs').on('change',function() {
        log_view_prefs.error = this.checked;
        draw_logs()
    });
});




