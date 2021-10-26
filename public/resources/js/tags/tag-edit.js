/**
 *
 * @param {FlowTag} tag
 *
 * @param {?FlowTagEditCallback} [callback_after_update]
 * @param {?FlowTagEditCallback} [callback_after_delete]
 */
function flow_tag_show_editor(tag,
                              callback_after_update,
                              callback_after_delete) {
    let modal;
    /**
     * @type {?FlowTag}
     */
    let parent_tag = null;

    let editing_div = $("div#flow-edit-tag-template-holder > div.flow-edit-container ").clone();
    let editing_div_id = 'tag-editor-' + uuid.v4();
    editing_div.attr('id',editing_div_id);

    let bare_select_control = editing_div.find('select.flow-edit-tag-parent-list');
    let tag_name_input = editing_div.find('input.flow-edit-tag-name');
    let tag_name_display = editing_div.find('.flow-edit-tag-name-in-title');

    function update_tag_display() {
        editing_div.data('tag_guid', tag.flow_tag_guid);
        editing_div.attr('data-tag_guid', tag.flow_tag_guid);
        tag_name_input.val(tag.flow_tag_name);
        tag_name_display.text(tag.flow_tag_name);
        tag_name_display.data('tag_guid', tag.flow_tag_guid);
        let tag_map = {}
        tag_map[tag.flow_tag_guid] = tag;
        add_tag_attributes_to_dom(tag_map, tag_name_display, true);
        editing_div.find('.flow-edit-tag-guid').text(tag.flow_tag_guid);
        editing_div.find('.flow-edit-tag-created-at').data('ts', tag.created_at_ts).attr('data-ts', tag.created_at_ts);
        editing_div.find('.flow-edit-tag-modified-at').data('ts', tag.updated_at_ts).attr('data-ts', tag.created_at_ts);
        generate_parent_nav_list();
        fill_attribute_tab();
        fill_applied_tab();
        refresh_auto_formatted_times();
    }

    update_tag_display();
    create_tabs();


    editing_div.find('button.flow-edit-tag-title-save').click(function () {
        let me = $(this);
        let new_name = tag_name_input.val();
        if (new_name) {
            toggle_action_spinner(me, 'loading');
            tag.flow_tag_name = new_name;
            edit_tag(tag,
                function (ret) {
                    tag = ret.tag;
                    update_tag_display();
                    update_tag_name_and_style_in_page(tag);
                    toggle_action_spinner(me, 'normal');
                    if (callback_after_update) {callback_after_update(tag)}
                },
                function () {
                    toggle_action_spinner(me, 'normal');
                })
        }
    });

    editing_div.find('button.flow-edit-tag-delete-action').click(function () {
        let me = $(this);
        my_swal.fire({
            title: 'Are you sure?',
            text: `Going to delete the tag ${tag.flow_tag_name}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {

                toggle_action_spinner(me, 'loading');

                delete_tag(tag,
                    function (ret) {
                        tag = ret.tag;
                        mark_tag_as_deleted_in_page(tag);
                        modal.close();

                        toggle_action_spinner(me, 'normal');

                        my_swal.fire(
                            'Deleted!',
                            'That pesky tag is gone',
                            'success'
                        );

                        if (callback_after_delete) {callback_after_delete(ret.tag);}
                    },
                    function (ret) {
                        toggle_action_spinner(me, 'normal');
                        my_swal.fire(
                            'Oh No!',
                            'The tag could not be deleted <br>\n ' + ret.message,
                            'error'
                        )
                    })


            }
        });

    });


    editing_div.find('button.flow-edit-tag-parent-save').click(function () {
        let me = $(this);
        if (parent_tag) {
            toggle_action_spinner(me, 'loading');
            tag.parent_tag_guid = parent_tag.flow_tag_guid;
            edit_tag(tag,
                function (ret) {
                    tag = ret.tag;
                    update_tag_display();
                    toggle_action_spinner(me, 'normal');
                    if (callback_after_update) {callback_after_update(tag)}
                },
                function (ret) {
                    toggle_action_spinner(me, 'normal');
                    my_swal.fire(
                        'Oh No!',
                        'The tag could not be deleted \n<br> ' + ret.message,
                        'error'
                    )
                })
        }
    });


    function generate_parent_nav_list() {
        let parent_display_breadcumbs = editing_div.find('.flow-edit-tag-parent-display ol');
        parent_display_breadcumbs.html('');
        let current_parent = tag.flow_tag_parent;
        /**
         *
         * @type {FlowTag[]}
         */
        let parent_list = [];
        let tag_map = {};
        while (current_parent) {
            parent_list.push(current_parent);
            tag_map[current_parent.flow_tag_guid] = current_parent;
            current_parent = current_parent.flow_tag_parent;
        }
        parent_list.reverse();


        while (parent_list.length > 0) {
            let parent_here = parent_list.shift();
            if (!parent_here) {
                continue;
            }
            let parent_name_thing =
                `<li class="breadcrumb-item ">
                <span   class="flow-tag-list-name flow-tag-display flow-tag-show-edit-on-click"
                        data-tag_guid="${parent_here.flow_tag_guid}">${parent_here.flow_tag_name}</span>
            </li>`
            parent_display_breadcumbs.append(parent_name_thing);
        }
        if (tag.flow_tag_parent) {
            let tags_here = parent_display_breadcumbs.find('.flow-tag-display');
            add_tag_attributes_to_dom(tag_map, tags_here, false);
            tags_here.show();
            parent_display_breadcumbs.closest('div.flow-edit-tag-parent-display').show();
        } else {
            parent_display_breadcumbs.closest('div.flow-edit-tag-parent-display').hide();
        }
    }

    function create_tabs() {
        let tab_list = editing_div.find('.flow-edit-tag-tabs button');

        let triggers = {}
        tab_list.each(function () {
            let tabTrigger = new bootstrap.Tab(this);
            let that = $(this);
            if (that.hasClass('flow-edit-tag-attribute-tab')) {
                triggers.attributes = tabTrigger;
            } else if (that.hasClass('flow-edit-tag-applied-tab')) {
                triggers.applied = tabTrigger;
            }

        });

        tab_list.click(function () {
            let that = $(this);
            if (that.hasClass('flow-edit-tag-attribute-tab')) {
                triggers.attributes.show();
                editing_div.find('.flow-edit-tag-attribute-content').addClass('show active');
                editing_div.find('.flow-edit-tag-applied-content').removeClass('show active');
            } else if (that.hasClass('flow-edit-tag-applied-tab')) {
                triggers.applied.show();
                editing_div.find('.flow-edit-tag-attribute-content').removeClass('show active');
                editing_div.find('.flow-edit-tag-applied-content').addClass('show active');
            }

        });
    }

    function fill_attribute_tab() {
        let ul_home = editing_div.find('.flow-edit-tag-attribute-content ul');
        ul_home.html('');
        for (let attribute_name in tag.attributes) {
            let attribute = tag.attributes[attribute_name];
            let li = $("ul#flow-attribute-one-line-template-holder > li.flow-attribute-summary-line ").clone();
            li.find('.flow-attribute-name').text(attribute.tag_attribute_name)
                .data('attribute_guid', attribute.flow_tag_attribute_guid)
                .attr("data-attribute_guid", attribute.flow_tag_attribute_guid);

            if (attribute.is_inherited) {

                //find the parent who owns this attribute
                let parent_for_attribute = tag.flow_tag_parent;
                while (parent_for_attribute) {
                    if (parent_for_attribute.flow_tag_guid === attribute.flow_tag_guid) {
                        break;
                    }
                    parent_for_attribute = parent_for_attribute.flow_tag_parent;
                }


                let tag_name_display = li.find('.flow-attribute-inherited-from-tag');
                tag_name_display.text(parent_for_attribute.flow_tag_name);
                tag_name_display.data('tag_guid', parent_for_attribute.flow_tag_guid);
                let tag_map = {}
                tag_map[parent_for_attribute.flow_tag_guid] = parent_for_attribute;
                add_tag_attributes_to_dom(tag_map, tag_name_display, true);
            }


            li.find('.flow-attribute-link').attr('href', attribute.points_to_url);
            li.find('.flow-attribute-link-title').text(attribute.points_to_title);
            li.find('.flow-attribute-number-value').text(attribute.tag_attribute_long ?? '');
            li.find('.flow-attribute-text-value-preview').html(attribute.tag_attribute_text ?? '');

            ul_home.append(li);
        }
    }


    function fill_applied_tab() {
        let ul_home = editing_div.find('.flow-edit-tag-applied-content ul');
        ul_home.html('');

        for (let i = 0; i < tag.applied.length; i++) {

            let applied = tag.applied[i]
            let li = $("ul#flow-applied-one-line-template-holder > li.flow-applied-summary-line ").clone();
            let applied_type = '';
            if (applied.tagged_flow_project_guid) {
                li.find('.flow-applied-icon-project').show();
                applied_type = 'Project';
            } else if (applied.tagged_flow_user_guid) {
                li.find('.flow-applied-icon-user').show();
                applied_type = 'User';
            } else if (applied.tagged_flow_entry_guid) {
                li.find('.flow-applied-icon-entry').show();
                applied_type = 'Entry';
            }
            li.find('.flow-applied-type').text(applied_type);


            li.find('a.flow-applied-link').attr('href', applied.tagged_url);
            li.find('.flow-tag-applied-target-title').text(applied.tagged_title).attr('applied_guid', applied.flow_applied_tag_guid);

            let last_ts = applied.created_at_ts;
            if (last_ts) {
                li.find('.flow-applied-created-at').data('ts', last_ts).attr('data-ts', last_ts);
            }


            ul_home.append(li);

            li.find('button.flow-applied-delete-action').click(function () {
                let me = $(this);
                my_swal.fire({
                    title: 'Are you sure?',
                    text: `Going to delete the Applied ${applied.tagged_title}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {

                        toggle_action_spinner(me, 'loading');
                        delete_applied(tag, applied,
                            function (ret) {
                                tag = ret.tag;
                                li.remove();
                                let victims = $(`.flow-tag-applied-target-title[data-applied_guid="${applied.flow_applied_tag_guid}"]`);
                                victims.addClass('text-decoration-line-through');
                                toggle_action_spinner(me, 'normal');

                                my_swal.fire(
                                    'Un Applied !',
                                    'Its a secret now what happened',
                                    'success'
                                )
                            },
                            function (ret) {
                                toggle_action_spinner(me, 'normal');
                                my_swal.fire(
                                    'Oh No!',
                                    'The applied could not be deleted ' + ret.message,
                                    'error'
                                )
                            })


                    }
                });
            });
        }
    }


    // noinspection JSPotentiallyInvalidConstructorUsage,JSUnusedGlobalSymbols
    modal = new tingle.modal({
        footer: true,
        stickyFooter: false,
        closeMethods: ['overlay', 'button', 'escape'],
        closeLabel: "Close",
        cssClass: ['flow-tag-edit-tingle'],
        onOpen: function () {
            //clear out older values
            tag_name_input.val();
            create_select_2_for_tag_search(bare_select_control, false, "Optionally select a parent",
                false, tag.flow_tag_guid,null,null);

            refresh_auto_formatted_times();

        },
        onClose: function () {
            utterly_destroy_select2(bare_select_control);
            this.destroy();
            $('body').off("click", `div#${editing_div_id} .flow-attribute-show-edit-on-click`, edit_attribute);
            if (callback_after_update) {callback_after_update(tag);}
        },

        beforeClose: function () {
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
        console.debug('woke', parent_tag);
    });

    bare_select_control.on('select2:unselecting', function () {
        parent_tag = null;
        console.debug('sleep', parent_tag);
    });

    // open modal
    modal.open();

    editing_div.find('button.flow-edit-tag-parent-goto').click(function () {
        if (parent_tag) {
            flow_tag_show_editor(parent_tag);
        }
    });



    /**
     *
     * @param guid
     * @returns {?FlowTagAttribute}
     */
    function find_attribute_by_guid(guid) {
        if (!guid) {return null;}
        for (let attribute_name in tag.attributes) {
            let attribute = tag.attributes[attribute_name];
            if (attribute.flow_tag_attribute_guid === guid) {
                return attribute;
            }
        }
        console.warn("could not find attribute for guid ", guid);
        return null;
    }



    function edit_attribute() {


        let guid_found = $(this).data('attribute_guid');
        if (guid_found) {
            //find attribute
            let found_attribute = find_attribute_by_guid(guid_found);
            let older_name = found_attribute.tag_attribute_name;
            flow_attribute_show_editor(tag, found_attribute,
                function (updated_attribute) {
                    let older_attribute = find_attribute_by_guid(updated_attribute.flow_tag_attribute_guid);
                    if (older_attribute) {
                        if (older_name === updated_attribute.tag_attribute_name) {
                            tag.attributes[updated_attribute.tag_attribute_name] = updated_attribute;
                        } else {
                            delete tag.attributes[older_name];
                            tag.attributes[updated_attribute.tag_attribute_name] = updated_attribute;
                        }
                    } else {
                        tag.attributes[updated_attribute.tag_attribute_name] = updated_attribute;
                    }
                    fill_attribute_tab();
                    console.debug("got updated attribute of ", updated_attribute);
                },
                function(deleted_attribute) {
                    let found_deleted_attribute = find_attribute_by_guid(deleted_attribute.flow_tag_attribute_guid);
                    if (found_deleted_attribute) {
                        delete tag.attributes[found_deleted_attribute.tag_attribute_name];
                        fill_attribute_tab();
                    }
                }
            );
        }
    }


    editing_div.find('button.flow-create-attribute-action').click(function () {

        flow_attribute_show_editor(tag, null,
            function (new_attribute) {
                //see if attribute is in the list, if not add
                let found_attribute =  find_attribute_by_guid(new_attribute.flow_tag_attribute_guid);

                if (!found_attribute) {
                    tag.attributes[new_attribute.tag_attribute_name] = new_attribute;
                }
                fill_attribute_tab();
                console.debug("got new attribute of ", new_attribute);
            }
        );

    });

    $('body').on("click", `div#${editing_div_id} .flow-attribute-show-edit-on-click`, edit_attribute);
}

jQuery(function($) {
   $('body').on("click",'.flow-tag-show-edit-on-click',function(){
       let guid = $(this).data('tag_guid');
       if (guid) {
               get_tag_by_guid(
                   guid,
                   function(found_tag) {
                        flow_tag_show_editor(found_tag);
                   }
               );
       }

   }) ;
});

