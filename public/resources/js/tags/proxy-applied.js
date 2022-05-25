// noinspection JSValidateTypes,JSUnusedGlobalSymbols

function ProxyApplied() {

    this.flow_applied_tag_guid = null;
    this.flow_tag_guid = null;
    this.tagged_flow_entry_guid = null;
    this.tagged_flow_user_guid = null;
    this.tagged_flow_project_guid = null;
    this.tagged_flow_entry_node_guid = null;
    this.tagged_pointer_guid = null;
    this.tagged_url = null;
    this.tagged_title = null;
    this.created_at_ts = null;

}


/**
 *
 * @returns {FlowTagApplied}
 */
function create_proxy_applied() {
    return new ProxyApplied();
}