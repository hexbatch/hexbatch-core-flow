// noinspection JSValidateTypes,JSUnusedGlobalSymbols

function ProxyAttribute() {

    this.flow_tag_attribute_guid = null;
    this.flow_tag_guid = null;
    this.points_to_flow_entry_guid = null;
    this.points_to_flow_user_guid = null;
    this.points_to_flow_project_guid = null;
    this.points_to_flow_tag_guid = null;
    this.tag_attribute_name = null;
    this.tag_attribute_long = null;
    this.tag_attribute_text = null;
    this.created_at_ts = null;
    this.updated_at_ts = null;
    this.is_inherited = null;
    this.points_to_title = null;
    this.project_guid_of_pointee = null;
    this.project_admin_guid_of_pointee = null;
    this.project_admin_name_of_pointee = null;
    this.points_to_url = null;

}

/**
 *
 * @returns {FlowTagAttribute}
 */
function create_proxy_attribute() {
    return new ProxyAttribute();
}