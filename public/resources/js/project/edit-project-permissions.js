//for editing projects

//permissions

/**
 * @type {?FlowUser}
 */
let selected_user = null;

jQuery(function ($){

    let modal;
    let editing_div = $("div#flow-find-user");

    let body = $('body');
    let bare_select_control = $('select#flow-find-user-list');

    let current_role;

    // init the select 2

    /**
     *
     * @param {FlowUser}user
     * @return {string}
     */
    function format_user_in_dropdown (user) {
        if (!user.flow_user_guid) {
            return '';
        }
        let creation_date = new Date(user.flow_user_created_at_ts*1000);
        let display = `<span>${user.flow_user_name}</span> <span class="float-end bg-info">${creation_date.toLocaleString()}</span>`;
        return jQuery(display);
    }

    /**
     *
     * @param {FlowUser}user
     * @return {string}
     */
    function format_selected_user (user) {
        if (!user.flow_user_guid) {
            return '';
        }
        let display = `<span>${user.flow_user_name}</span>`;
        return jQuery(display);
    }

    function process_results_for_dropdown (data) {
        // Transforms the top-level key of the response object from 'items' to 'results'

        let id_thing = 1;
        for(let i in data.results) {
            /**
             * @type {FlowUser}
             */
            let user = data.results[i];
            user.id = id_thing ++;
            user.text = user.flow_user_name;
        }
        return data;
    }

    /**
     *
     * @param  params
     */
    function prepare_query_for_dropdown(params) {
        let query = {
            term: params.term,
            page: params.page || 1,
            project_guid: flow_project_guid,
            role_in_project: current_role,
            in_project: 0
        }

        // Query parameters will be ?search=[term]&page=[page]
        return query;
    }

    $('#flow-find-user-list').select2({
        ajax: {
            url: flow_project_user_search_url,
            delay: 250 ,// wait 250 milliseconds before triggering the request
            data: prepare_query_for_dropdown,
            processResults: process_results_for_dropdown,
        },
        templateResult: format_user_in_dropdown,
        templateSelection: format_selected_user,

        placeholder: "Select a user",
        allowClear: true,
    });


    body.on('select2:select', 'select#flow-find-user-list', function () {
        let data_array = $('select#flow-find-user-list').select2("data");
        if (Array.isArray(data_array) && data_array.length) {
            selected_user = data_array[0];
        }
    });



    //set up popup box
    // noinspection JSPotentiallyInvalidConstructorUsage,JSUnusedGlobalSymbols
    modal = new tingle.modal({
        footer: false,
        stickyFooter: false,
        closeMethods: ['overlay', 'button', 'escape'],
        closeLabel: "Close",
        cssClass: ['flow-find-user-popup'],
        onOpen: function() {
            bare_select_control.select2({
                ajax: {
                    url: flow_project_user_search_url,
                    delay: 250 ,// wait 250 milliseconds before triggering the request
                    data: prepare_query_for_dropdown,
                    processResults: process_results_for_dropdown,
                },
                templateResult: format_user_in_dropdown,
                templateSelection: format_selected_user,

                placeholder: "Select a user",
                allowClear: true,
                dropdownParent: $('div.tingle-modal.flow-find-user-popup')
            });


        },
        onClose: function() {
            //do not destroy the tingle
            //but do destroy with extreme hatred the select2
            // Destroy Select2

            selected_user = null;
            utterly_destroy_select2(bare_select_control);

        },
        beforeClose: function() {
            return true; // close the modal
            // return false; // nothing happens
        }
    });


    modal.setContent(editing_div[0]);




    $('button#flow-add-reader').on("click", function() {
        current_role = 'read';
        $("button#flow-find-user-add").attr('data-role',current_role);
        $("#flow-find-user-title").text("Add someone to read")
        // open modal
        modal.open();
    }) ;

    $('button#flow-add-editor').on("click",function() {
        current_role = 'write';
        $("button#flow-find-user-add").attr('data-role',current_role);
        $("#flow-find-user-title").text("Add someone to write")
        // open modal
        modal.open();
    }) ;

    $('button#flow-add-admin').on("click",function() {
        current_role = 'admin';
        $("button#flow-find-user-add").attr('data-role',current_role);
        $("#flow-find-user-title").text("Add someone to admin")
        // open modal
        modal.open();
    }) ;

    $('#flow-set-public').on("change",function() {
        let is_public;
        if (this.checked) {
            is_public = 1;
        } else {
            is_public = 0;
        }

        let data = {
            action: 'permission_public_set',
            is_public: is_public
        }
        do_permission_action(data,function() {
            //disable or enable the readers
            if (is_public) {
                $('div.flow-reader-area').addClass('flow-disabled');
            } else {
                $('div.flow-reader-area').removeClass('flow-disabled');
            }
            modal.close();
        });

    });

    /**
     *
     * @param {FlowUser} user
     * @param {string} role
     */
    function add_user_to_display(user,role) {

        //see if user is already in the area
        let ul = $(`li button[data-role="${role}"]`).closest('ul');
        let maybe_already_here = ul.find(`li[data-guid="${user.flow_user_guid}"]`);
        if (maybe_already_here.length) {return;}

        //add name to row
        let li = $(`
                <li class="list-group-item d-flex justify-content-between align-items-start "
                    data-guid="${user.flow_user_guid}"
                >
                    <div >
                        ${user.flow_user_name}
                    </div>
                    <button class="btn btn-light text-light btn-sm bg-danger flow-remove-permission"
                            data-role="${role}"
                            data-guid="${user.flow_user_guid}"
                    >
                        <i class="fas fa-minus-circle"></i>
                    </button>
                </li>
            `);
        ul.find('li:last').before(li);
    }

    $('button#flow-find-user-add').on("click",function() {
        if (!selected_user) {return;}
        let role = $(this).attr('data-role');
        let action;
        switch (role) {
            case 'read': {
                action = 'permission_read_add'; break;
            }
            case 'write': {
                action = 'permission_write_add'; break;
            }
            case 'admin': {
                action = 'permission_admin_add'; break;
            }
        }
        let data = {
            action: action,
            user_guid : selected_user.flow_user_guid
        }
        do_permission_action(data,function() {
            add_user_to_display(selected_user,role);
            if (role === 'admin') {
                add_user_to_display(selected_user,'write');
                add_user_to_display(selected_user,'read');
            }
            if (role === 'write') {
                add_user_to_display(selected_user,'read');
            }
            modal.close();
        });
    }) ;


    body.on('click', 'button.flow-remove-permission', function () {
       let role = $(this).data('role');
       let user_guid = $(this).data('guid');
       let action;
       switch (role) {
           case 'read': {
               action = 'permission_read_remove'; break;
           }
           case 'write': {
               action = 'permission_write_remove'; break;
           }
           case 'admin': {
               action = 'permission_admin_remove'; break;
           }
       }

       let that = this;

        let data = {
            action: action,
            user_guid : user_guid
        }
       do_permission_action(data,function() {
           //remove name from row
           $(that).closest('li').remove();
       });
    });

    function do_permission_action(data,on_success_callback) {
        let token_csrf_index_input = $('input[name="_CSRF_INDEX"]');
        let token_csrf_token_input = $('input[name="_CSRF_TOKEN"]');

        data._CSRF_INDEX = token_csrf_index_input.val();
        data._CSRF_TOKEN = token_csrf_token_input.val();
        data.project_guid = flow_project_guid;

        $.ajax({
            url: flow_project_edit_permission_ajax_url,
            method: "POST",
            dataType: 'json',
            data : data
        })
            .always(function( data ) {

                /**
                 * @type {FlowEditPermissionResponse}
                 */
                let ret;

                if (flow_check_if_promise(data)) {
                    console.debug('promise passed in for edit permissions',data);
                    if (data.hasOwnProperty('responseJSON')) {
                        ret = data.responseJSON;
                    } else {
                        ret = {
                            success: false,
                            message: data.statusText,
                            token: null
                        };
                    }


                } else {
                    ret = data;
                }

                if (ret.success) {
                    if (on_success_callback) {on_success_callback();}
                    do_toast({
                        title:'Updated Permissions',
                        delay:5000,
                        type:'success'
                    });
                } else {
                    do_toast({
                        title:'Cannot Change Permissions',
                        subtitle:'There was an issue with the ajax',
                        content: ret.message,
                        delay:20000,
                        type:'error'
                    });
                }

                if (ret && ret.token) {
                    token_csrf_index_input.val(ret.token._CSRF_INDEX);
                    token_csrf_token_input.val(ret.token._CSRF_TOKEN);
                }
            });

    }

});

