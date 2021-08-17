//for project history

jQuery(function ($){


    function show_file_changes(file_path,commit,on_success_callback) {


        let data_to_server = {
            file_path: file_path,
            commit: commit
        }


        $.ajax({
            url: flow_project_get_file_change_ajax_url,
            method: "POST",
            dataType: 'json',
            data : data_to_server
        })
            .always(function( data ) {

                /**
                 * @type {FlowHistoryFileDiffResponse}
                 */
                let ret;

                if (flow_check_if_promise(data)) {
                    if (data.hasOwnProperty('responseJSON')) {
                        ret = data.responseJSON;
                    } else {
                        ret = {
                            success: false,
                            message: data.statusText,
                            diff:null,
                            token: null
                        };
                    }


                } else {
                    ret = data;
                }

                if (ret.success) {
                    if (on_success_callback) {on_success_callback(ret);}
                    console.log('file change data',ret.diff);
                } else {
                    do_toast({
                        title:'Cannot Get File Change',
                        subtitle:'was trying to get file ' + file_path,
                        content: ret.message,
                        delay:20000,
                        type:'error'
                    });
                }

            });

    }

    let body = $('body');
    body.on('click', 'button.flow-show-file-diff', function () {
        let button = $(this);
        let file_path = button.closest('li').data('file_path')
        let commit = button.closest('li').data('commit')
        show_file_changes(file_path,commit);
    });


});
