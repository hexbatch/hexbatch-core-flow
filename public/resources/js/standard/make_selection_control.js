


/**
 *
 * @param {FlowStandardSettingsSetup} setup
 */
function make_flow_standard_selection_control(setup) {

    /**
     * @type {FlowTag[] }
     */
    let tag_list = setup.hasOwnProperty('tag_list') ? setup.tag_list : [];

    /**
     * @type {?FlowTag}
     */
    let tag_setting =  setup.hasOwnProperty('tag_setting') ? setup.tag_setting : null;

    /**
     * @type {?string}
     */
    let setting_name = setup.hasOwnProperty('setting_name') ? setup.setting_name : null;

    /**
     * @type {?string}
     */
    let selected_label = setup.hasOwnProperty('selected_label') ? setup.selected_label : null;


    /**
     * @type {?string}
     */
    let standard_name = setup.hasOwnProperty('standard_name') ? setup.standard_name : null;

    /**
     *
     * @type {FlowStandardSettingCallback}
     */
    let on_change_callback = setup.hasOwnProperty('on_change_callback') ? setup.on_change_callback : null;

    /**
     *
     * @type {FlowStandardSettingCallback}
     */
    let on_cancel_callback = setup.hasOwnProperty('on_cancel_callback') ? setup.on_cancel_callback : null;

    /**
     *
     * @type {FlowStandardSettingCallback}
     */
    let on_error_callback = setup.hasOwnProperty('on_error_callback') ? setup.on_error_callback : null;

    if (tag_list.length === 0) { throw new Error("Need tag list array of at least one element")}
    if (!standard_name) { throw new Error("Need standard name")}


}