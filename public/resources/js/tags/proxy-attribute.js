// noinspection JSValidateTypes,JSUnusedGlobalSymbols

function ProxyAttribute() {

    this.flow_tag_attribute_guid = null;
    this.flow_tag_guid = null;
    this.flow_applied_tag_guid = null;
    this.points_to_flow_entry_guid = null;
    this.points_to_flow_user_guid = null;
    this.points_to_flow_project_guid = null;
    this.tag_attribute_name = null;
    this.tag_attribute_long = null;
    this.tag_attribute_text = null;
    this.created_at_ts = null;
    this.updated_at_ts = null;
    this.is_standard_attribute = null;
    this.is_inherited = null;
    this.points_to_title = null;
    this.points_to_admin_guid = null;
    this.points_to_admin_name = null;
    this.points_to_url = null;

}

/**
 *
 * @returns {FlowTagAttribute}
 */
function create_proxy_attribute() {
    return new ProxyAttribute();
}