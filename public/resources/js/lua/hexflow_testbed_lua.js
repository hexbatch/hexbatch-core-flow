

jQuery(function($){


    const STORAGE_KEY_FOR_CODE = 'hexflow_testbed_code';
    let code_textarea=document.querySelector("#hexflow-code-test-lua");
    let rememberHack=function(){
        localStorage.setItem(STORAGE_KEY_FOR_CODE,code_textarea.value);
    };
    code_textarea.addEventListener("input",rememberHack);
    code_textarea.addEventListener("change",rememberHack);
    code_textarea.value = localStorage.getItem(STORAGE_KEY_FOR_CODE);

    processed_logs = new lau_return_to_logs(null);
    draw_logs();

    $(`button#hexflow-run-test-lua`).on('click',function() {
        let code = $(`textarea#hexflow-code-test-lua`).val();
        let code_name = $(`input#hexflow-code-test-name`).val();

        const ALL_PANEL_COLOR_CLASSES = 'text-white text-dark bg-success bg-warning bg-danger';

        let code_panel = $(`.hexflow-lua-editor`);
        code_panel.removeClass(ALL_PANEL_COLOR_CLASSES);



        /**
         * @param {FlowBasicLuaResponse} ret
         */
        function on_normal(ret) {
            code_panel.addClass('text-white bg-success');
            process_logs(ret);
        }

        /**
         * @param {FlowBasicLuaResponse} ret
         */
        function on_problem(ret) {
            code_panel.addClass('text-dark bg-warning');
            process_logs(ret);
        }

        /**
         * @param {FlowBasicLuaResponse} ret
         */
        function on_error(ret) {
            code_panel.addClass('text-white bg-danger');
            process_logs(ret);
        }

        /**
         * @param {FlowBasicLuaResponse} info_back
         */
        function process_logs(info_back) {
            processed_logs = new lau_return_to_logs(info_back);
            draw_logs();
        }

        do_lua_action(TEST_LUA_CODE_URL,code,code_name,on_normal,on_problem,on_error)
    });
});