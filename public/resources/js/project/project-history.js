//for project history

jQuery(function ($){
    let modal;

    let da_diff = '';

    // noinspection JSPotentiallyInvalidConstructorUsage,JSUnusedGlobalSymbols
    modal = new tingle.modal({
        footer: false,
        stickyFooter: false,
        closeMethods: ['overlay', 'button', 'escape'],
        closeLabel: "Close",
        cssClass: ['flow-find-user-popup'],
        onOpen: function() {

            let configuration = {
                drawFileList: true,
                fileListToggle: false,
                fileListStartVisible: false,
                fileContentToggle: false,
                matching: 'lines',
                outputFormat: 'side-by-side',
                synchronisedScroll: true,
                highlight: true,
                renderNothingWhenEmpty: false,
                diffStyle: 'word'
            };
            let targetElement = document.getElementById('flow-diff-viewer');
            let diff2htmlUi = new Diff2HtmlUI(targetElement, da_diff, configuration);
            diff2htmlUi.draw();
            diff2htmlUi.highlightCode();
        },
        onClose: function() {
            //do not destroy the tingle
        },
        beforeClose: function() {
            return true; // close the modal
            // return false; // nothing happens
        }
    });


    function show_diff(diff) {
        da_diff = diff;
        modal.setContent(`<div id="flow-diff-viewer"></div>`);

        modal.open();
    }

    function show_file_changes(file_path,commit,on_success_callback) {


        let data_to_server = {
            file_path: file_path,
            commit: commit,
            show_all: 1
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
                    if (on_success_callback) {on_success_callback(ret.diff);}
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
        show_file_changes(file_path,commit,function(diff) {
            show_diff(diff);
        });
    });

    body.on('click', 'button.flow-show-commit-diff', function () {
        let button = $(this);
        let commit = button.data('commit')
        show_file_changes(null,commit,function(diff) {
            show_diff(diff);
        });
    });




    function git_action(url,success_title,fail_title,on_success) {
        let data = {};
        do_flow_ajax_action(url,data,

            /**
             * @param {FlowGitActionResponse} ret
             */
            function(ret) {

                let out = ret.git_output.join("\n <br>");
                do_toast({
                    title:success_title,
                    content: out,
                    delay:5000,
                    type:'success'
                });
                if (on_success) {on_success();}
            },

            /**
             * @param {FlowGitActionResponse} ret
             */
            function(ret) {
                let out = ret.message;
                if (ret.hasOwnProperty('git_output')) {
                    out += "\n<br>" + ret.git_output.join("\n <br>");
                }
                my_swal.fire(
                    fail_title,
                    ret.message +  out,
                    'error'
                )
            },
            null,
            null
        );
    }

    body.on('click', 'button.flow-git-push-now', function () {
        let button = $(this);
        let url = button.data('url');
        git_action(url,'Pushed!','Cannot Push');
    });

    body.on('click', 'button.flow-git-pull-now', function () {
        let button = $(this);
        let url = button.data('url');
        git_action(url,'Pulled!','Cannot Pull',function() {
            setTimeout(function() {
                window.location.reload();
            },100);

        });
    });


});

