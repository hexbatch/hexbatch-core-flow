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


/**
 * @param {FlowTag} tag
 * @param {string} standard_name
 * @param {FlowStandardCallback} on_success_callback
 * @param {FlowStandardCallback} on_fail_callback
 */
function flow_delete_standard(tag,standard_name,
                              on_success_callback,on_fail_callback) {
    let url = project_base_url + `/standard/${tag.flow_tag_guid}/${standard_name}/delete`;

    do_flow_ajax_action(url,{},on_success_callback,on_fail_callback,
        'Deleted Standard','Could not delete standard');
}

/**
 * @type {StandardGit}
 * @constructor
 */
function ProxyStandardGit() {

    this.git_url = null;
    this.git_ssh_key = null;
    this.git_branch = null;
    this.git_notes = null;
    this.git_web_page = null;
    will_do_nothing(this.git_url,this.git_ssh_key,this.git_branch,this.git_notes,this.git_web_page)
}


/**
 *
 * @returns {StandardGit}
 */
function create_proxy_standard_git() {
    return new ProxyStandardGit();
}


/**
 * @type {StandardMeta}
 * @constructor
 */
function ProxyStandardMeta() {

    this.meta_version = null;
    this.meta_date_time = null;
    this.meta_author = null;
    this.meta_first_name = null;
    this.meta_last_name = null;
    this.meta_public_email = null;
    this.meta_picture_url = null;
    this.meta_website = null;

    will_do_nothing(this.meta_version,this.meta_date_time,this.meta_author,this.meta_first_name,
        this.meta_last_name,this.meta_public_email,this.meta_picture_url,this.meta_website
        )
}


/**
 *
 * @returns {StandardMeta}
 */
function create_proxy_standard_meta() {
    return new ProxyStandardMeta();
}



/**
 * @type {StandardCss}
 * @constructor
 */
function ProxyStandardCss() {

    this.css = null;
    this.color = null;
    this.backgroundColor = null;
    this.fontFamily = null;


    will_do_nothing(this.fontFamily,this.backgroundColor,this.color,this.css)
}

/**
 *
 * @returns {StandardCss}
 */
function create_proxy_standard_css() {
    return new ProxyStandardCss();
}

function FlowInheritedAttribute() {

    /**
     * @type {?string}
     */
    this.attribute_name = null;

    /**
     * @type {?string}
     */
    this.ancestor_guid = null;

    /**
     * @type {?string}
     */
    this.standard_name = null;


    /**
     *
     * @type {?FlowTag} ancestor_tag
     */
    this.ancestor_tag = null;
}

/**
 *
 * @param {string} standard_name
 * @param {FlowTag} tag
 * @return {Object.<string, FlowInheritedAttribute>}
 */
function flow_standards_get_inherited(standard_name,tag) {
    if (!tag.standard_attributes.hasOwnProperty(standard_name)) {
        return {};
    }

    /**
     *
     * @type {Object.<string, FlowInheritedAttribute>} ret
     */
    let ret = {};

    let standard = tag.standard_attributes[standard_name];
    for(let standard_attribute in standard) {
        if (!standard.hasOwnProperty(standard_attribute)) {return {};}
        if (tag.attributes.hasOwnProperty(standard_attribute)) {
            let attribute = tag.attributes[standard_attribute];
            if (attribute.is_inherited) {

                //find the parent who owns this attribute
                let parent_for_attribute = tag.flow_tag_parent;
                while (parent_for_attribute) {
                    if (parent_for_attribute.flow_tag_guid === attribute.flow_tag_guid) {
                        break;
                    }
                    parent_for_attribute = parent_for_attribute.flow_tag_parent;
                }
                let node = new FlowInheritedAttribute();
                node.ancestor_guid = parent_for_attribute.flow_tag_guid;
                node.attribute_name = attribute.tag_attribute_name;
                node.standard_name = standard_name;
                node.ancestor_tag = parent_for_attribute;
                ret[attribute.tag_attribute_name] = node;

            }
        }
    }
    return ret;
}




/**
 * @param {string} setting_name
 * @param {?string} tag_guid
 * @param {FlowSetProjectSettingResponseCallback} on_success_callback
 * @param {FlowSetProjectSettingResponseCallback} on_fail_callback
 */
function flow_set_project_setting(setting_name,tag_guid,
                              on_success_callback,on_fail_callback) {
    let url = project_base_url + `/set_project_setting/${setting_name}`;

    do_flow_ajax_action(url,{tag_guid:tag_guid},on_success_callback,on_fail_callback,
        `Updated project setting of ${setting_name}`,`Could not update project setting of ${setting_name}`);
}