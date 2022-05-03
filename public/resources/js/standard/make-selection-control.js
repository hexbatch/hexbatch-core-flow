


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
    let setting_description = setup.hasOwnProperty('setting_description') ? setup.setting_description : null;

    /**
     * @type {?string}
     */
    let setting_label = setup.hasOwnProperty('setting_label') ? setup.setting_label : null;


    /**
     * @type {?string}
     */
    let standard_name = setup.hasOwnProperty('standard_name') ? setup.standard_name : null;

    /**
     * @type {?string}
     */
    let setting_name = setup.hasOwnProperty('setting_name') ? setup.setting_name : null;

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



    if (!standard_name) { throw new Error("Need standard name")}

    /**
     * @type {?FlowStandardAttribute} chosen_standard
     */
    let chosen_standard = null;
    if (tag_setting) {
        if (tag_setting.standard_attributes.hasOwnProperty(standard_name)) {
            chosen_standard = tag_setting.standard_attributes[standard_name];
        }
    }

    /**
     * @type {Select2FlowStandardData[]} data
     */
    let select2_data_array = [];

    let dummy = {cant_touch_this: uuid.v4()};

    /**
     * @type {FlowStandardAttribute[]} selectable_standards
     */
    let selectable_standards = [];
    let selection_made = false;
    for(let i = 0; i < tag_list.length; i++) {
        let tag_in_list = tag_list[i];
        if (tag_in_list.standard_attributes.hasOwnProperty(standard_name)) {
            let standard_found = tag_in_list.standard_attributes[standard_name];
            selectable_standards.push(standard_found)
            let node = new Select2FlowStandardData(standard_found,tag_in_list,i + 2,standard_name);
            if (!selection_made && _.isMatch(standard_found??dummy,chosen_standard??dummy)) {
                node.selected = true;
                selection_made = true;
            }
            select2_data_array.push(node);
        }
    }




    let view_div_id = 'selection-view-' + uuid.v4();

    function make_view_div() {
        let view_div = $("div#flow-selection-option-template > div.flow-selection-option-card ").clone();


        view_div.attr('id',view_div_id);

        view_div.find('.flow-selection-option-label').text(setting_label);
        view_div.find('.flow-selection-option-description').text(setting_description);


        //if something selected, get its view
        if (chosen_standard) {
            let here = view_div.find('.flow-selected-standard-view-here');
            flow_standards_generate_view(standard_name,tag_setting,here);
            here.css(tag_setting.css);
        }


        if (!chosen_standard && selectable_standards.length === 0) {
            view_div.find('.flow-selection-option-warning').removeClass('d-none');
        } else if (!chosen_standard) {
            view_div.find('.flow-selection-option-not-set').removeClass('d-none');
        }

        let qj_body = $('body');
        if (selectable_standards.length > 0) {
            qj_body.on("click", `div#${view_div_id}`, display_selection_dialog);
        }


        return view_div;
    }




    function display_selection_dialog() {


        let modal;
        let edit_div = $("div#flow-selection-option-template > div.flow-selection-dialog-card ").clone();
        edit_div.find('.flow-selection-option-label').text(setting_label);
        let bare_select_control = edit_div.find('select.flow-selection-option-list');

        /**
         * @type {?Select2FlowStandardData} selected_data
         */
        let selected_data = null;

        /**
         * @type {?FlowStandardAttribute} chosen_standard
         */
        let selected_option = chosen_standard;

        modal = new tingle.modal({
            footer: true,
            stickyFooter: false,
            closeMethods: ['overlay', 'button', 'escape'],
            closeLabel: "Close",
            cssClass: ['flow-select-option-tingle'],
            onOpen: function () {
                create_select_2_for_standard_options(bare_select_control,select2_data_array);
                refresh_auto_formatted_times();
            },
            onClose: function () {
                utterly_destroy_select2(bare_select_control);
                this.destroy();
            },

            beforeClose: function () {
                return true;
            }
        });

        modal.setContent(edit_div[0]);

        if (on_cancel_callback ) {
            on_cancel_callback({
                tag_setting:tag_setting,
                standard_name: standard_name,
                standard_value:selected_option,
                setting_name: setting_name
            })
        }

        modal.addFooterBtn('Clear Setting', 'tingle-btn tingle-btn--danger tingle-btn--pull-right', function () {
            selected_option = null;
            selected_data = null;
            do_save_stuff();
            modal.close();
        });

        modal.addFooterBtn('Save Setting', 'tingle-btn tingle-btn--primary', function () {
            do_save_stuff();
            modal.close();
        });

        function do_save_stuff() {
            let view_thing = $(`div#${view_div_id}`);
            let here = view_thing.find('.flow-selected-standard-view-here');
            here.html('');

            if (selected_data) {
                view_thing.find('.flow-selection-option-not-set').addClass('d-none');
                flow_standards_generate_view(standard_name,selected_data.flow_tag,here);
            } else {

                view_thing.find('.flow-selection-option-not-set').removeClass('d-none');
            }

            if (on_change_callback && !_.isMatch(chosen_standard??dummy,selected_option??dummy)  ) {
                //update the view
                let flow_tag = null;
                if (selected_data) {flow_tag = selected_data.flow_tag;}
                on_change_callback({
                    tag_setting:tag_setting,
                    standard_name: standard_name,
                    standard_value:selected_option,
                    chosen_tag: flow_tag,
                    setting_name: setting_name
                });
            }
        }

        bare_select_control.on('select2:select', function () {
            let data_array = bare_select_control.select2("data");
            if (data_array.length) {
                /**
                 * @type {Select2FlowStandardData} data
                 */
                let data = data_array[0];
                if (data.flow_tag) {
                    selected_data = data;
                    selected_option = data.standard;
                } else {
                    selected_data = null;
                    selected_option = null;
                }

            } else {
                selected_data = null;
                selected_option = null;
            }
            console.debug('standard selected', selected_option,selected_data);
        });

        bare_select_control.on('select2:unselecting', function () {
            selected_option = null;
            selected_data = null;
            console.debug('standard un-selected', selected_option);
        });

        // open modal
        modal.open();


    }



    return make_view_div();
}