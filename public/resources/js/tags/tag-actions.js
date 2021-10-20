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
    do_tag_action(url,tag,on_success_callback,'Saved Tag','Cannot Save Tag');
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
    do_tag_action(url,tag,on_success_callback,'Saved Attribute','Cannot Save Attribute');
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




function do_tag_action(url,data,on_success_callback,success_title, fail_title) {
    let tag = JSON.parse(JSON.stringify(data));

    set_object_with_flow_ajax_token_data(tag);

    $.ajax({
        url: create_tag_ajax_url,
        method: "POST",
        dataType: 'json',
        data : tag
    })
        .always(function( data ) {

            /**
             * @type {FlowSetTagResponse}
             */
            let ret;

            if (flow_check_if_promise(data)) {
                console.debug('promise passed in for do_tag_action',data);
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
                if (on_success_callback) {on_success_callback(ret);}
                do_toast({
                    title:success_title,
                    delay:5000,
                    type:'success'
                });
            } else {
                do_toast({
                    title:fail_title,
                    subtitle:'There was an issue with the ajax',
                    content: ret.message,
                    delay:20000,
                    type:'error'
                });
            }

            if (ret && ret.token) {
                update_root_flow_ajax_token(ret.token);
            }
        });

}