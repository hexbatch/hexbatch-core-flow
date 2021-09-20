/**
 *
 * @param {string} parent_id
 * @param {FlowTag[]} tag_array_given
 */
function applied_control(parent_id,tag_array_given) {
    /*
      fills in the list, and updates it when applied added or removed
     */

    let parent_div = $(`#${parent_id}`);
    let applied_target_guid = parent_div.data('applied_target_guid');
    let applied_target_type = parent_div.data('applied_target_type');

    let bare_select_control = parent_div.find('select.flow-add-applied-tag-list');

    /**
     *
     * @type {FlowTag[]}
     */
    let tags_of_applied_array = tag_array_given;

    /**
     *
     * @type {?FlowTag} tag_to_apply
     */
    let tag_to_apply = null;

    let body = $('body');

    /**
     *
     * @param {?string} tag_guid
     * @returns {?FlowTag}
     */
    function find_tag_by_guid(tag_guid) {
        if (!tag_guid) {return null;}
        //
        for ( let i = 0; i < tags_of_applied_array.length; i++) {
            let tag = tags_of_applied_array[i];
            if (tag.flow_tag_guid === tag_guid) {return tag;}
        }

        console.warn("could not find tag using guid ", tag_guid);
        return null;
    }

    /**
     *
     * @param {?string} tag_guid
     * @returns {number}
     */
    function find_index_of_tag_by_guid(tag_guid) {
        if (!tag_guid) {return -1;}
        //
        for ( let i = 0; i < tags_of_applied_array.length; i++) {
            let tag = tags_of_applied_array[i];
            if (tag.flow_tag_guid === tag_guid) {return i;}
        }

        console.warn("could not find index of tag using guid ", tag_guid);
        return -1;
    }


    /**
     *
     * @param {?string} tag_guid
     * @returns {?FlowTagApplied}
     */
    function find_applied_by_guid_of_tag(tag_guid) {
        if (!tag_guid) {return null;}
        //
        for ( let i = 0; i < tags_of_applied_array.length; i++) {
            let tag = tags_of_applied_array[i];
            if (tag.flow_tag_guid !== tag_guid) {continue;}
            let applied = find_applied_in_tag(tag);
            if (applied) {return applied;}
        }

        console.warn("could not find applied for tag guid, given the target guid ", tag_guid,applied_target_guid);
        return null;
    }

    /**
     *
     * @param {?FlowTag} dat_tag
     * @returns {?FlowTagApplied}
     */
    function find_applied_in_tag(dat_tag) {
        if (!dat_tag) {return null;}
        for (let a = 0; a < dat_tag.applied.length ; a++  ) {
            let applied = dat_tag.applied[a];
            if (
                applied.tagged_flow_project_guid === applied_target_guid
                ||
                applied.tagged_flow_user_guid === applied_target_guid
                ||
                applied.tagged_flow_entry_guid === applied_target_guid
            ) {
                return applied;
            }
        }

        return null;
    }

    /*
        Allow editing (permissions checked on server)
     */
    function edit_tag_of_applied() {
        let that = $(this);
        let tag_guid = that.data('tag_guid');
        let tag = find_tag_by_guid(tag_guid);

        flow_tag_show_editor(tag,
            function (updated_tag) {
                let index = find_index_of_tag_by_guid(updated_tag.flow_tag_guid);
                if (index >=0) {
                    //see if still there in updated_tag
                    let applied_found = find_applied_in_tag(updated_tag);
                    if (applied_found) {
                        tags_of_applied_array[index] = updated_tag;
                    } else {
                        tags_of_applied_array.splice(index, 1)
                    }

                    refresh_tags_of_applied()
                }

            },
            function(deleted_tag) {

                let index = find_index_of_tag_by_guid(deleted_tag.flow_tag_guid);
                if (index >=0) {
                    tags_of_applied_array.splice(index, 1)
                    refresh_tags_of_applied()
                }
            }
        );
    }

    /*
        listens to deletes for applied, will call for removing it for server, and update list
    */
    function delete_attribute_for_applied_tag() {
        let that = $(this);
        let tag_guid = that.closest('li').find('.flow-tag-display').data('tag_guid');
        let tag = find_tag_by_guid(tag_guid);
        let applied = find_applied_by_guid_of_tag(tag_guid);
        delete_applied(tag,applied,
            function(){
                let index = find_index_of_tag_by_guid(tag.flow_tag_guid);
                if (index >=0) {
                    tags_of_applied_array.splice(index, 1)
                    refresh_tags_of_applied()
                }
                my_swal.fire(
                    'Applied removed',
                    `Removed ${tag.flow_tag_name}`,
                    'success'
                );
            },
            function(ret) {
                my_swal.fire(
                    'Oh No!',
                    'The applied could not be removed \n<br> ' + ret.message,
                    'error'
                )
            });
    }

    function refresh_tags_of_applied() {
        let ul = parent_div.find('ul.flow-tags-of-applied-list');
        body.off("click", `div#${parent_id} .flow-applied-show-edit-on-click`, edit_tag_of_applied);
        body.off("click", `div#${parent_id} .flow-remove-applied-tag`, delete_attribute_for_applied_tag);
        ul.html('');

        /**
         *
         * @type {Object<string, FlowTag>}
         */
        let tag_map = {};
        for ( let i = 0; i < tags_of_applied_array.length; i++) {
            let tag = tags_of_applied_array[i];
            tag_map[tag.flow_tag_guid] = tag;
        }


        for ( let i = 0; i < tags_of_applied_array.length; i++) {
            let tag = tags_of_applied_array[i];

            let li_thing =

            `<li class="list-group-item list-group-item-secondary ">
                <span   class="flow-tag-list-name flow-tag-display flow-applied-show-edit-on-click"
                        style="display: none"
                        data-tag_guid="${tag.flow_tag_guid}">${tag.flow_tag_name}</span>
                <button type="button" class="btn-close small ms-1 flow-remove-applied-tag"  aria-label="Close"></button>        
            </li>`
            ul.append(li_thing);
        }
        let tags_here = ul.find('.flow-tag-display');
        add_tag_attributes_to_dom(tag_map, tags_here, false);
        tags_here.show();


        body.on("click", `div#${parent_id} .flow-applied-show-edit-on-click`, edit_tag_of_applied);
        body.on("click", `div#${parent_id} .flow-remove-applied-tag`, delete_attribute_for_applied_tag);
    }

    refresh_tags_of_applied();




    /*
        creates the select and calls make applied for any tag selected, if its not in the array already
     */

    create_select_2_for_tag_search(bare_select_control, false, "Add More applied",
        false, null,null,[applied_target_guid]);


    bare_select_control.on('select2:select', function () {
        let data_array = bare_select_control.select2("data");
        if (data_array.length) {
            tag_to_apply = data_array[0];
        } else {
            tag_to_apply = null;
        }
        console.debug('woke tag to apply', tag_to_apply);
    });

    bare_select_control.on('select2:unselecting', function () {
        tag_to_apply = null;
        console.debug('sleep tag to apply', tag_to_apply);
    });

    function add_new_applied() {
        if (!tag_to_apply) {
            my_swal.fire(
                'Oh No!',
                'select a tag to apply ',
                'warning'
            );
            return;
        }

        let applied = create_proxy_applied();
        applied.flow_tag_guid = tag_to_apply.flow_tag_guid;
        switch (applied_target_type) {
            case 'project': {
                applied.tagged_flow_project_guid = applied_target_guid;
                break;
            }
            case 'user': {
                applied.tagged_flow_user_guid = applied_target_guid;
                break;
            }
            case 'entry': {
                applied.tagged_flow_entry_guid = applied_target_guid;
                break;
            }
            default: {
                console.error("Cannot figure out type of applied",applied_target_type);
                return;
            }
        }
        create_applied(tag_to_apply,applied,
            function(ret){
                let index = find_index_of_tag_by_guid(ret.tag.flow_tag_guid);
                if (index >=0) {
                    tags_of_applied_array[index] = ret.tag;
                } else {
                    tags_of_applied_array.push(ret.tag);
                }
                refresh_tags_of_applied();
                bare_select_control.val(null).trigger('change');
                my_swal.fire(
                    'Applied Added',
                    `Added ${ret.tag.flow_tag_name}`,
                    'success'
                );
            },
            function(ret) {
                my_swal.fire(
                    'Oh No!',
                    'The applied could not be created \n<br> ' + ret.message,
                    'error'
                )
            });
    }

    parent_div.find('button.flow-add-applied-tag-action').click(add_new_applied);


}