


/**
 * @param {FlowTag} tag
 * @param {?FlowTagAppliedCreateCallback} [callback_after_create]
 * @param {?boolean} [b_view_only]
 */
function flow_create_applied_show_editor(tag,
                                    callback_after_create,
                                    b_view_only
                                    ){
    let modal;
    b_view_only = !!b_view_only;
    if (b_view_only) {return;}

    let new_applied = new ProxyApplied();
    new_applied.flow_tag_guid = tag.flow_tag_guid;

    /**
     * @type {?GeneralSearchResult}
     */
    let applied_target_search = null;

    let editing_div = $("div#flow-edit-tag-template-holder > div.create-applied-container ").clone();
    let editing_div_id = 'create-applied-'+uuid.v4();
    editing_div.attr('id',editing_div_id);
    let bare_select_control = editing_div.find('select.flow-create-applied-of-list');

    let b_is_saving = false;

    let tag_name_display = editing_div.find('.flow-create-applied-of-tag');

    function update_applied_display() {

        tag_name_display.text(tag.flow_tag_name);
        tag_name_display.data('tag_guid',tag.flow_tag_guid);
        let tag_map = {}
        tag_map[tag.flow_tag_guid] = tag;
        add_tag_attributes_to_dom(tag_map,tag_name_display,true);

    }

    update_applied_display();


    // noinspection JSPotentiallyInvalidConstructorUsage,JSUnusedGlobalSymbols
    modal = new tingle.modal({
        footer: true,
        stickyFooter: false,
        closeMethods: ['overlay', 'button', 'escape'],
        closeLabel: "Close",
        cssClass: ['flow-create-applied-tingle'],
        onOpen: function() {
            create_select_2_for_general_search(
                bare_select_control, false, "Select a target", null);

        },
        onClose: function() {
            utterly_destroy_select2(bare_select_control);
            this.destroy();
        },
        beforeClose: function() {
            return !b_is_saving;
        }
    });

    modal.setContent(editing_div[0]);


    bare_select_control.on('select2:select', function () {
        let data_array = bare_select_control.select2("data");
        if (data_array.length) {
            applied_target_search = data_array[0];
        } else {
            applied_target_search = null;
        }
        console.debug('woke general',applied_target_search);
    });

    bare_select_control.on('select2:unselecting', function () {
        applied_target_search = null;
        console.debug('sleep general',applied_target_search);
    });

    // add a button
    let footer_button_text = 'Create Applied';

    modal.addFooterBtn(footer_button_text, 'tingle-btn tingle-btn--primary', function () {

        if (applied_target_search) {
            switch (applied_target_search.type) {
                case 'user': {
                    new_applied.tagged_flow_user_guid = applied_target_search.guid;
                    break;
                }
                case 'entry': {
                    new_applied.tagged_flow_entry_guid = applied_target_search.guid;
                    break;
                }
                case 'project': {
                    new_applied.tagged_flow_project_guid = applied_target_search.guid;
                    break;
                }
            }
        }


        b_is_saving = true;

        create_applied(tag, new_applied
            ,
            function (response) {
                b_is_saving = false;
                console.log('created applied', response);
                if (callback_after_create) {
                    callback_after_create(response.tag);
                }
                my_swal.fire(
                    'Created Applied',
                    'Another link made',
                    'success'
                )
                modal.close();
            },
            function (ret) {
                b_is_saving = false;
                my_swal.fire(
                    'Drat!',
                    'The applied could not created <br>\n ' + ret.message,
                    'error'
                )
            });


    });


    // open modal
    modal.open();
}