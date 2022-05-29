
/**
 *
 * @param {string} url
 * @param {string} code
 * @param {FlowBasicLuaResponseCallback} on_code_success_callback
 * @param {FlowBasicLuaResponseCallback} on_code_fail_callback
 * @param {FlowBasicLuaResponseCallback} on_other_fail_callback
 */
function do_lua_action(url,code,on_code_success_callback,
                       on_code_fail_callback,on_other_fail_callback)
{

    /**
     * @param {FlowBasicLuaResponse} ret
     */
    function local_success_callback(ret) {
        if (ret.code > 0) {
            if (on_code_fail_callback) {on_code_fail_callback(ret);}
            else if (on_other_fail_callback) {on_other_fail_callback(ret);}
        } else {
            if (on_code_success_callback) {on_code_success_callback(ret);}
        }

    }
    do_flow_ajax_action(url,data,local_success_callback,on_other_fail_callback,null,null);

}