//for tags

/**
 * @type {?FlowTag}
 */
let working_tag = null;


jQuery(function ($){

    /**
     * @type {?FlowTag}
     */
    let selected_tag = null;

    let b_open = true;

    let bare_select_control = $('select#flow-select-tags');

    /**
     *
     * @param {FlowTag} tag
     * @param {string} [extra_class]
     * @return {string}
     */
    function format_tag(tag,extra_class) {
        extra_class = extra_class??'';
        if (!(tag.flow_tag_guid || tag.text)) {return '';}
        if (tag.flow_tag_guid) {
            let color = tag.standard_attributes.color ?? '#000000';
            let bg_color = tag.standard_attributes.background_color ?? 'inherit';


            let display = `<span `+
                                `style="color:${color};background-color: ${bg_color}" `+
                                `class="flow-tag-display ${extra_class}" `+
                                `data-guid="${tag.flow_tag_guid}"`+
                            `>`+
                                `${tag.flow_tag_name}`+
                            `</span>`;

            return jQuery(display);
        } else {
            let display = `<span `+
                                `class="flow-tag-display ${extra_class}" `+
                                `data-text="${tag.text}"`+
                                `>`+
                                    `${tag.text}`+
                            `</span>`;
            return jQuery(display);
        }
    }

    function format_tag_in_dropdown (tag) {
        return format_tag(tag);

    }

    /**
     *
     * @param {FlowTag} tag
     * @return {string}
     */
    function format_selected_tag (tag) {
        return format_tag(tag);
    }

    function  create_tag (params) {
        let term = $.trim(params.term);

        if (term === '') {
            return null;
        }

        return {
            id: term,
            text: term,
            is_new_tag: true,
            flow_tag_guid: null,
            parent_tag_guid: null,
            flow_project_guid: null,
            flow_tag_name: term,
            created_at_ts: null,
            attributes: [],
            standard_attributes: []

        }

    }

    function process_results_for_dropdown (data) {
        // Transforms the top-level key of the response object from 'items' to 'results'

        let id_thing = 1;
        for(let i in data.results) {
            /**
             * @type {FlowTag}
             */
            let tag = data.results[i];
            tag.id = id_thing ++;
            tag.text = tag.flow_tag_name;
        }
        return data;
    }

    /**
     *
     * @param  params
     */
    function prepare_query_for_dropdown(params) {
        let query = {
            search: {},
            page: params.page || 1
        }

        // Query parameters will be ?search=[term]&page=[page]
        return query;
    }

    bare_select_control.select2({
        ajax: {
            url: get_tags_ajax_url,
            delay: 250 ,// wait 250 milliseconds before triggering the request
            data: prepare_query_for_dropdown,
            processResults: process_results_for_dropdown,
        },
        templateResult: format_tag_in_dropdown,
        templateSelection: format_selected_tag,

        placeholder: "Select or make a tag",
        allowClear: true,
        multiple: true,
        tags: true,
        createTag: create_tag
    });




    bare_select_control.on('select2:select', function () {
        let data_array = bare_select_control.select2("data");
        console.log('tag data array on select',data_array)
    });


    //use the open flag to allow open or not
    bare_select_control.on('select2:opening', function (event) {
        if (b_open) {return true;}
        event.preventDefault();
        return false;
    });


    //anywhere else on the selection input or textarea will allow the select box to open
    $('span.select2-selection.select2-selection--multiple').click(function() {
        b_open = true;
        selected_tag = null;
    });

    //if select a tag, flag to not open
    $('span.select2-selection.select2-selection--multiple ul.select2-selection__rendered').click(function(e) {
        let span = $(e.target);
        const guid = span.data('guid');
        const da_text = span.data('text');
        if (!(guid || da_text)) {return;}

        selected_tag = null;

        if (guid) {
            /**
             * @type {FlowTag[]} data_array
             */
            let data_array = bare_select_control.select2("data");
            for (let i = 0; i < data_array.length; i++) {
                if (guid === data_array[i].flow_tag_guid) {
                    selected_tag = data_array[i];
                    send_selected_to_editor(selected_tag);
                    break;
                }
            }
        } else if (da_text) {
            /**
             * @type {FlowTag[]} data_array
             */
            let data_array = bare_select_control.select2("data");
            for (let i = 0; i < data_array.length; i++) {
                if (da_text === data_array[i].text) {
                    selected_tag = data_array[i];
                    send_selected_to_editor(selected_tag);
                    break;
                }
            }
        }

        b_open = false;
    });


    /**
     *
     * @param {FlowTag} tag
     */
    function send_selected_to_editor(tag) {
        console.log('editing',tag);
        working_tag = tag;
        if (working_tag.flow_tag_name) {
            $('span#flow-tag-name').text(working_tag.flow_tag_name)
        } else {
            $('span#flow-tag-name').text("no name")
        }

        if (working_tag.flow_tag_guid) {
            $('code#flow-tag-guid').text(working_tag.flow_tag_guid)
        } else {
            $('code#flow-tag-guid').text("no guid")
        }
    }

    $("button#flow-tag-save").click(function() {
        if (working_tag) {
            set_tag_action(working_tag,function(saved_tag) {
               console.log("Updated tag",saved_tag)
            });
        }
    });

});

function set_tag_action(data,on_success_callback) {
    let token_div = $('#flow-set-tags-ajax-tokens');
    let token_csrf_index_input = token_div.find ('input[name="_CSRF_INDEX"]');
    let token_csrf_token_input = token_div.find('input[name="_CSRF_TOKEN"]');

    data._CSRF_INDEX = token_csrf_index_input.val();
    data._CSRF_TOKEN = token_csrf_token_input.val();

    $.ajax({
        url: set_tags_ajax_url,
        method: "POST",
        dataType: 'json',
        data : data
    })
        .always(function( data ) {

            /**
             * @type {FlowSetTagResponse}
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
                        tag: null,
                        token: null
                    };
                }


            } else {
                ret = data;
            }

            if (ret.success) {
                if (on_success_callback) {on_success_callback(ret.tag);}
                do_toast({
                    title:'Saved Tag',
                    delay:5000,
                    type:'success'
                });
            } else {
                do_toast({
                    title:'Cannot Save Tag',
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

