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
    create_select_2_for_tag_search(bare_select_control,true,"Select or make a tag", true,null);





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
            if (working_tag.flow_tag_guid) {
                edit_tag(working_tag,function(response) {
                    console.log("Updated tag",response)
                });
            } else {
                create_tag(working_tag,function(response) {
                    console.log("created tag",response)
                });
            }

        }
    });

});


