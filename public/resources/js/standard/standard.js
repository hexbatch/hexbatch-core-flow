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