/**
 *
 * @param {FlowTag} tag
 */
function flow_tag_show_editor(tag){
    let modal;
    let editing_div = $("div#flow-edit-tag-template-holder > div.flow-edit-container ").clone();
    let bare_select_control = editing_div.find('select.flow-edit-tag-parent-list');
    let tag_name_input = editing_div.find('input.flow-edit-tag-name');
    let body = $('body');

    editing_div.data('tag_guid',tag.flow_tag_guid);
    editing_div.attr('data-tag_guid',tag.flow_tag_guid);
    tag_name_input.val(tag.flow_tag_name);

    let parent_display_breadcumbs = editing_div.find('.flow-edit-tag-parent-display ol');
    let current_parent = tag.flow_tag_parent;

    /**
     *
     * @type {FlowTag[]}
     */
    let parent_list = [];
    let tag_map = {};
    while(current_parent) {
        parent_list.push(current_parent);
        tag_map[current_parent.flow_tag_guid] = current_parent;
        current_parent = current_parent.flow_tag_parent;
    }
    parent_list.reverse();


    while(parent_list.length > 0) {
        let parent_here = parent_list.shift();
        if (!parent_here) {continue;}
        let parent_name_thing =
            `<li class="breadcrumb-item ">
                <span   class="flow-tag-list-name flow-tag-display"
                        data-tag_guid="${ parent_here.flow_tag_guid }">${ parent_here.flow_tag_name }</span>
            </li>`
        parent_display_breadcumbs.append(parent_name_thing);
    }
    if (tag.flow_tag_parent) {
        let tags_here = parent_display_breadcumbs.find('.flow-tag-display');
        add_tag_attributes_to_dom(tag_map,tags_here,false);
        tags_here.show();
        parent_display_breadcumbs.closest('div.flow-edit-tag-parent-display').show();
    } else {
        parent_display_breadcumbs.closest('div.flow-edit-tag-parent-display').hide();
    }

    editing_div.find('.flow-edit-tag-guid').text(tag.flow_tag_guid);
    editing_div.find('.flow-edit-tag-created-at').data('ts',tag.created_at_ts).attr('data-ts',tag.created_at_ts);
    editing_div.find('.flow-edit-tag-modified-at').data('ts',tag.updated_at_ts).attr('data-ts',tag.created_at_ts);


    let tabs = editing_div.find('.nav-tabs');
    tabs.tab();
    tabs.find('a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
    /**
     * @type {?FlowTag}
     */
    let parent_tag = null;


    // noinspection JSPotentiallyInvalidConstructorUsage,JSUnusedGlobalSymbols
    modal = new tingle.modal({
        footer: true,
        stickyFooter: false,
        closeMethods: ['overlay', 'button', 'escape'],
        closeLabel: "Close",
        cssClass: ['flow-tag-edit-tingle'],
        onOpen: function() {
            //clear out older values
            tag_name_input.val();
            create_select_2_for_tag_search(bare_select_control,false,"Optionally select a parent",
                false,tag.flow_tag_guid);
            if (tag.flow_tag_parent) {
                // let parent_to_set = JSON.parse(JSON.stringify(tag.flow_tag_parent));
                // if (!parent_to_set.text) { parent_to_set.text = parent_to_set.flow_tag_name}
                // parent_to_set.id = 1;
                // let option = new Option(parent_to_set.text, parent_to_set.id, true, true);
                // bare_select_control.append(option).trigger('change');
                //
                // // manually trigger the `select2:select` event
                // bare_select_control.trigger({
                //     type: 'select2:select',
                //     params: {
                //         data: parent_to_set
                //     }
                // });

            }
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
            parent_tag = data_array[0];
        } else {
            parent_tag = null;
        }
        console.debug('woke',parent_tag);
    });

    bare_select_control.on('select2:unselecting', function () {
        parent_tag = null;
        console.debug('sleep',parent_tag);
    });

    // open modal
    modal.open();




    body.on('click', 'button#flow-new-tag-parent-goto', function () {
        //todo add go to parent from new
    });



}

