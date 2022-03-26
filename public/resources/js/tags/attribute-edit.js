


/**
 * @param {FlowTag} tag
 * @param {?FlowTagAttribute} passed_attribute
 * @param {?FlowTagAttributeEditCallback} [callback_after_update]
 * @param {?FlowTagAttributeEditCallback} [callback_after_delete]
 * @param {?boolean} [b_view_only]
 */
function flow_attribute_show_editor(tag,passed_attribute,
                                    callback_after_update,
                                    callback_after_delete,
                                    b_view_only
                                    ){
    let modal;
    b_view_only = !!b_view_only;
    let b_editing = !b_view_only;

    /**
     * @type {FlowTagAttribute}
     */
    let attribute;

    if (passed_attribute) {
        attribute = passed_attribute;
    } else {
        attribute = create_proxy_attribute();
        attribute.flow_tag_guid = tag.flow_tag_guid
    }
    /**
     * @type {?GeneralSearchResult}
     */
    let attribute_points_to_search = null;

    let editing_div = $("div#flow-edit-tag-template-holder > div.attribute-edit-container ").clone();
    let editing_div_id = 'attribute-editor-'+uuid.v4();
    editing_div.attr('id',editing_div_id);
    let bare_select_control = editing_div.find('select.flow-edit-attribute-point-list');
    let attribute_name_input = editing_div.find('input.flow-edit-attribute-name');
    let attribute_integer_input = editing_div.find('input.flow-edit-attribute-integer');
    let attribute_text = editing_div.find('textarea.flow-edit-attribute-text-value');
    let tag_name_display = editing_div.find('.flow-edit-attribute-of-tag');
    let attribute_name_display = editing_div.find('.flow-edit-attribute-name-in-title');
    let points_to_group = editing_div.find('.flow-things-points-to-group');

    let b_is_saving = false;

    function update_attribute_display() {
        editing_div.data('attribute_guid',attribute.flow_tag_attribute_guid);
        editing_div.attr('data-attribute_guid',attribute.flow_tag_attribute_guid);
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
        attribute_integer_input.val(attribute.tag_attribute_long?? '');
        refresh_auto_formatted_times();

        if (attribute.flow_tag_guid && attribute.flow_tag_guid !== tag.flow_tag_guid) {
            attribute_name_input.attr('disabled',true);
            attribute_integer_input.attr('disabled',true);
            attribute_text.attr('disabled',true);
            points_to_group.hide();
        }

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
            if (b_editing && (attribute.flow_tag_guid === tag.flow_tag_guid)) {

                create_select_2_for_general_search(
                    bare_select_control, false, "Optionally select a parent", null);
            }

            refresh_auto_formatted_times();

        },
        onClose: function() {
            if (b_editing && attribute.flow_tag_guid === tag.flow_tag_guid) {
                utterly_destroy_select2(bare_select_control);
            }

            this.destroy();
        },
        beforeClose: function() {
            return !b_is_saving;

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
    let footer_button_text = 'Create Attribute';
    if (attribute.flow_tag_attribute_guid) {
        if (b_editing && attribute.flow_tag_guid === tag.flow_tag_guid) {
            footer_button_text = 'Update Attribute';
        } else {
            footer_button_text = 'Cannot Save Inherited Attribute';
        }

    }
    if (b_editing && attribute.flow_tag_guid === tag.flow_tag_guid) {
        modal.addFooterBtn(footer_button_text, 'tingle-btn tingle-btn--primary', function () {

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
            if (dat_int === '') {
                attribute.tag_attribute_long = null;
            } else {
                attribute.tag_attribute_long = parseInt(dat_int);
            }

            attribute.tag_attribute_text = attribute_text.val();
            if (attribute.tag_attribute_text === '') {
                attribute.tag_attribute_text = null;
            }
            attribute.tag_attribute_name = attribute_name_input.val();

            if (attribute.tag_attribute_name && attribute.flow_tag_attribute_guid) {
                b_is_saving = true;
                edit_attribute(tag, attribute
                    ,
                    function (response) {
                        console.log('updated attribute', response);
                        b_is_saving = false;
                        if (callback_after_update) {
                            callback_after_update(response.tag);
                        }
                        my_swal.fire(
                            'Updated Attribute',
                            'Bask in your success',
                            'success'
                        )
                        modal.close();
                    },
                    function (ret) {
                        b_is_saving = false;
                        my_swal.fire(
                            'Oh No!',
                            'The attribute could not saved <br>\n' + ret.message,
                            'error'
                        )
                    });
            } else if (attribute.tag_attribute_name && !attribute.flow_tag_attribute_guid) {

                create_attribute(tag, attribute
                    ,
                    function (response) {
                        console.log('created attribute', response);
                        if (callback_after_update) {
                            callback_after_update(response.tag);
                        }
                        my_swal.fire(
                            'Created Attribute',
                            'A new journey begins',
                            'success'
                        )
                        modal.close();
                    },
                    function (ret) {
                        my_swal.fire(
                            'Oh No!',
                            'The attribute could not created <br>\n ' + ret.message,
                            'error'
                        )
                    });
            }

        });
    } else {
        modal.addFooterBtn(footer_button_text, 'tingle-btn',function() {

        });
    }



    if (b_editing) {
        editing_div.find('button.flow-edit-attribute-delete-action').click(function() {
            if (b_is_saving) {return;}
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
                        if (callback_after_delete) { callback_after_delete(tag);}
                        return;
                    }
                    toggle_action_spinner(me,'loading');
                    b_is_saving = true;
                    delete_attribute(tag, attribute,
                        function(ret) {
                            b_is_saving = false;
                            modal.close();

                            toggle_action_spinner(me,'normal');

                            my_swal.fire(
                                'Attribute Deleted!',
                                'Its no more..',
                                'success'
                            );

                            if (callback_after_delete) { callback_after_delete(ret.tag);}
                        },
                        function(ret) {
                            toggle_action_spinner(me,'normal');
                            b_is_saving = false;
                            my_swal.fire(
                                'Oh No!',
                                'The Attribute could not be deleted <br>\n ' + ret.message,
                                'error'
                            )
                        })


                }
            });

        });
    } else {
        editing_div.find('button.flow-edit-attribute-delete-action').attr('disabled',true);
    }


    // open modal
    modal.open();
}