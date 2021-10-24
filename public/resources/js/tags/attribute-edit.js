


/**
 * @param {FlowTag} tag
 * @param {FlowTagAttribute|ProxyAttribute} attribute
 * @param {FlowTagAttributeUpdateCallback} callback_after_update
 */
function flow_attribute_show_editor(tag,attribute,callback_after_update){
    let modal;

    if (!attribute) {
        attribute = new ProxyAttribute();
    }
    /**
     * @type {?GeneralSearchResult}
     */
    let attribute_points_to_search = null;

    let editing_div = $("div#flow-edit-tag-template-holder > div.attribute-edit-container ").clone();
    let bare_select_control = editing_div.find('select.flow-edit-attribute-point-list');
    let attribute_name_input = editing_div.find('input.flow-edit-attribute-name');
    let attribute_integer_input = editing_div.find('input.flow-edit-attribute-integer');
    let attribute_text = editing_div.find('textarea.flow-edit-attribute-text-value');
    let tag_name_display = editing_div.find('.flow-edit-attribute-of-tag');
    let attribute_name_display = editing_div.find('.flow-edit-attribute-name-in-title');

    function update_attribute_display() {
        editing_div.data('attribute_guid',attribute.flow_tag_attribute_guid);
        editing_div.attr('data-attribute_guid',tag.flow_tag_guid);
        attribute_name_input.val(attribute.tag_attribute_name);
        attribute_name_display.val(attribute.tag_attribute_name);
        tag_name_display.text(tag.flow_tag_name);
        tag_name_display.data('tag_guid',tag.flow_tag_guid);
        let tag_map = {}
        tag_map[tag.flow_tag_guid] = tag;
        add_tag_attributes_to_dom(tag_map,tag_name_display,true);
        editing_div.find('.flow-edit-attribute-guid').text(attribute.flow_tag_attribute_guid);
        editing_div.find('.flow-edit-attribute-created-at').data('ts',attribute.created_at_ts).attr('data-ts',attribute.created_at_ts);
        editing_div.find('.flow-edit-attribute-modified-at').data('ts',attribute.updated_at_ts).attr('data-ts',attribute.updated_at_ts);
        editing_div.find('.flow-edit-attribute-link').attr('href',attribute.points_to_url);
        editing_div.find('.flow-attribute-link-title').html(attribute.points_to_title);
        attribute_text.val(attribute.tag_attribute_text?? '');
        refresh_auto_formatted_times();
    }

    update_attribute_display();


    // noinspection JSPotentiallyInvalidConstructorUsage,JSUnusedGlobalSymbols
    modal = new tingle.modal({
        footer: true,
        stickyFooter: false,
        closeMethods: ['overlay', 'button', 'escape'],
        closeLabel: "Close",
        cssClass: ['flow-attribute-edit-tingle'],
        onOpen: function() {

            create_select_2_for_general_search(
                bare_select_control,false,"Optionally select a parent", null);

            refresh_auto_formatted_times();

        },
        onClose: function() {
            utterly_destroy_select2(bare_select_control);
            this.destroy();
        },
        beforeClose: function() {
            return true; // close the modal
            // return false; // nothing happens
        }
    });

    modal.setContent(editing_div[0]);


    bare_select_control.on('select2:select', function () {
        let data_array = bare_select_control.select2("data");
        if (data_array.length) {
            attribute_points_to_search = data_array[0];
        } else {
            attribute_points_to_search = null;
        }
        console.debug('woke general',attribute_points_to_search);
    });

    bare_select_control.on('select2:unselecting', function () {
        attribute_points_to_search = null;
        console.debug('sleep general',attribute_points_to_search);
    });

    // add a button
    modal.addFooterBtn('Create Attribute', 'tingle-btn tingle-btn--primary', function() {

        if (attribute_points_to_search) {
            switch (attribute_points_to_search.type) {
                case 'user': {
                    attribute.points_to_flow_user_guid = attribute_points_to_search.guid;
                    break;
                }
                case 'entry': {
                    attribute.points_to_flow_entry_guid = attribute_points_to_search.guid;
                    break;
                }
                case 'project': {
                    attribute.points_to_flow_project_guid = attribute_points_to_search.guid;
                    break;
                }
            }
        }
        let dat_int = attribute.tag_attribute_long = attribute_integer_input.val();
        if (dat_int === '') { attribute.tag_attribute_long = null;}
        else { attribute.tag_attribute_long = parseInt(dat_int);}

        attribute.tag_attribute_text = attribute_text.val();
        if (attribute.tag_attribute_text === '') { attribute.tag_attribute_text = null;}
        attribute.tag_attribute_name = attribute_name_input.val();

        if (attribute.tag_attribute_name && attribute.flow_tag_attribute_guid) {

            edit_attribute(tag, attribute
                ,
                function(response) {
                    console.log('updated attribute',response);
                    if (callback_after_update) {
                        callback_after_update(response.attribute);
                    }
                    my_swal.fire(
                        'Updated Attribute',
                        'Bask in your success',
                        'success'
                    )
                    modal.close();
                },
                function(ret) {
                    my_swal.fire(
                        'Oh No!',
                        'The attribute could not saved <br>\n' + ret.message,
                        'error'
                    )
                });
        } else if (attribute.tag_attribute_name && !attribute.flow_tag_attribute_guid) {

            create_attribute(tag, attribute
                ,
                function(response) {
                    console.log('created attribute',response);
                    if (callback_after_update) {
                        callback_after_update(response.attribute);
                    }
                    my_swal.fire(
                        'Created Attribute',
                        'A new journey begins',
                        'success'
                    )
                    modal.close();
                },
                function(ret) {
                    my_swal.fire(
                        'Oh No!',
                        'The attribute could not created <br>\n ' + ret.message,
                        'error'
                    )
                });
        }

    });



    editing_div.find('button.flow-edit-attribute-delete-action').click(function() {
        let me = $(this);
        my_swal.fire({
            title: 'Are you sure?',
            text: `Going to delete the attribute ${attribute.tag_attribute_name?? 'unnamed'}` ,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                if (!attribute.flow_tag_attribute_guid) {
                    modal.close();
                    return;
                }
                toggle_action_spinner(me,'loading');

                delete_attribute(tag, attribute,
                    function(ret) {
                        tag = ret.tag;

                        modal.close();

                        toggle_action_spinner(me,'normal');

                        my_swal.fire(
                            'Attribute Deleted!',
                            'Its no more..',
                            'success'
                        )
                    },
                    function(ret) {
                        toggle_action_spinner(me,'normal');
                        my_swal.fire(
                            'Oh No!',
                            'The Attribute could not be deleted <br>\n ' + ret.message,
                            'error'
                        )
                    })


            }
        });

    });

    // open modal
    modal.open();
}