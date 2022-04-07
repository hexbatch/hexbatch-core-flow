/**
 * @param {FlowTag} tag
 * @param {string} standard_name
 * @param {Object} meta_data
 * @param {FlowStandardCallback} on_success_callback
 * @param {FlowStandardCallback} on_fail_callback
 */
function flow_update_standard(tag,standard_name,meta_data,
                            on_success_callback,on_fail_callback) {
    let url = project_base_url + `/standard/${tag.flow_tag_guid}/${standard_name}/update`;

    do_flow_ajax_action(url,meta_data,on_success_callback,on_fail_callback,
        'Edited Standard','Could Not change standard');
}