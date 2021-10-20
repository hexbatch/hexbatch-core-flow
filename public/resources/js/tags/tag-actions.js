/*
create , edit, delete tag
create, edit, delete attribute
create, delete applied
 */

/**
 *
 * @param {FlowTag} tag
 * @param on_success_callback
 */
function create_tag(tag,on_success_callback) {
    let url = project_base_url + '/tag/create';
    do_tag_action(url,tag,on_success_callback,'Created Tag','Cannot Create Tag');
}

/**
 *
 * @param {FlowTag} tag
 * @param on_success_callback
 */
function edit_tag(tag,on_success_callback) {
    let url = project_base_url + `/tag/${tag.flow_tag_guid}/edit`;

    /**
     *
     * @param {FlowTagResponse} data
     */
    function update_text(data) {
        if (data.tag) {
            data.text = data.tag.flow_tag_name;
        }

        if (on_success_callback) {on_success_callback(data);}
    }
    do_tag_action(url,tag,update_text,'Saved Tag','Cannot Save Tag');
}

/**
 *
 * @param {FlowTag} tag
 * @param on_success_callback
 */
function delete_tag(tag,on_success_callback) {
    let url = project_base_url + `/tag/${tag.flow_tag_guid}/delete`;
    do_tag_action(url,tag,on_success_callback,'Deleted Tag','Cannot Delete Tag');
}


/**
 * @param {FlowTag} tag
 * @param {FlowTagAttribute} attribute
 * @param on_success_callback
 */
function create_attribute(tag,attribute,on_success_callback) {
    let url = project_base_url + `/tag/${tag.flow_tag_guid}/attribute/create`;
    do_tag_action(url,attribute,on_success_callback,'Created Attribute','Cannot Create Attribute');
}

/**
 * @param {FlowTag} tag
 * @param {FlowTagAttribute} attribute
 * @param on_success_callback
 */
function edit_attribute(tag,attribute,on_success_callback) {
    let url = project_base_url + `/tag/${tag.flow_tag_guid}/attribute/${attribute.flow_tag_attribute_guid}/edit`;
    do_tag_action(url,attribute,on_success_callback,'Saved Attribute','Cannot Save Attribute');
}

/**
 * @param {FlowTag} tag
 * @param {FlowTagAttribute} attribute
 * @param on_success_callback
 */
function delete_attribute(tag,attribute,on_success_callback) {
    let url = project_base_url + `/tag/${tag.flow_tag_guid}/attribute/${attribute.flow_tag_attribute_guid}/delete`;
    do_tag_action(url,tag,on_success_callback,'Deleted Attribute','Cannot Delete Attribute');
}


/**
 * @param {FlowTag} tag
 * @param {FlowTagApplied} applied
 * @param on_success_callback
 */
function create_applied(tag,applied,on_success_callback) {
    let url = project_base_url + `/tag/${tag.flow_tag_guid}/applied/create`;
    do_tag_action(url,applied,on_success_callback,'Created Applied','Cannot Create Applied');
}

/**
 * @param {FlowTag} tag
 * @param {FlowTagApplied} applied
 * @param on_success_callback
 */
function delete_applied(tag,applied,on_success_callback) {
    let url = project_base_url + `/tag/${tag.flow_tag_guid}/applied/delete`;
    do_tag_action(url,applied,on_success_callback,'Deleted Applied','Cannot Delete Applied');
}


/**
 *
 * @param {string} url
 * @param {FlowTag|FlowTagAttribute|FlowTagApplied} data
 * @param {FlowTagActionSuccessCallback} on_success_callback
 * @param {string} success_title
 * @param {string} fail_title
 */
function do_tag_action(url,data,on_success_callback,success_title, fail_title) {
    let tag = JSON.parse(JSON.stringify(data));

    set_object_with_flow_ajax_token_data(tag);

    $.ajax({
        url: url,
        method: "POST",
        dataType: 'json',
        data : tag
    })
        .always(function( data ) {
            /**
             * @type {FlowAppliedResponse|FlowBasicResponse}
             * maybe basic if a failure
             */
            let ret = process_ajax_response(data,success_title,fail_title);

            if (ret.success) {
                if (on_success_callback) {on_success_callback(ret);}
            }



        });

}