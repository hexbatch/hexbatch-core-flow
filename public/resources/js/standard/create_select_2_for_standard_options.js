

function Select2FlowStandardData(standard,tag,id) {
    this.standard = standard;
    this.flow_tag = tag;
    this.id = id;
    this.text = standard.standard_guid;
    this.selected = false;
}

/**
 * 
 * @param bare_select_control
 * @param {Select2FlowStandardData[]} selectable_standards
 */
function create_select_2_for_standard_options(bare_select_control,selectable_standards) {



    /**
     *
     * @param {Select2FlowStandardData} standard
     * @param {string} [extra_class]
     * @return {string}
     */
    function format_standard(standard,extra_class) {
        extra_class = extra_class??'';
        if (!(standard.id || standard.text)) {return '';}

        let display = jQuery(`<span `+
            `class=" ${extra_class} " `+
            `data-standard_guid="${standard.standard.guid}"`+
            `data-tag_guid="${standard.flow_tag.guid}"`+
            `></span>`);

        flow_standards_generate_view(standard.standard.standard_name,standard.flow_tag,display);
        return display;
    }

    /**
     *
     * @param {Select2FlowStandardData} tag
     * @returns {string}
     */
    function format_standard_in_dropdown (tag) {
        return format_standard(tag);

    }

    /**
     *
     * @param {Select2FlowStandardData} tag
     * @return {string}
     */
    function format_selected_standard (tag) {
        return format_standard(tag,'flow-tag-no-borders-padding');
    }


    bare_select_control.select2({
        data: selectable_standards,
        minimumInputLength: 1,
        templateResult: format_standard_in_dropdown,
        templateSelection: format_selected_standard,
        placeholder: placeholder?? '',
        allowClear: true,
        multiple: false,
        selectionCssClass: ':all:',
    });
}