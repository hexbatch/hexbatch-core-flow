

function Select2FlowStandardData(standard,tag,id,standard_name) {
    this.standard = standard;
    this.flow_tag = tag;
    this.id = id;
    this.text = null;
    if (tag) {
        this.text = standard.standard_guid;
    }
    this.selected = false;
    this.standard_name = standard_name;
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
        if (!(standard.flow_tag)) {
            let text_display = jQuery(`<span class="flow-empty-view-selection" ></span>`);
            text_display.text(standard.text);
            return text_display;
        }

        let display = jQuery(`<span `+
            `class=" ${extra_class} " `+
            `data-tag_guid="${standard.flow_tag.flow_tag_guid}"`+
            `></span>`);

        flow_standards_generate_view(standard.standard_name,standard.flow_tag,display);
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


    function matchCustom(params, data) {
        // If there are no search terms, return all of the data
        if ($.trim(params.term) === '') {
            return data;
        }

        // Do not display the item if there is no 'text' property
        if (typeof data.text === 'undefined') {
            return null;
        }

        let my_data = $.extend({}, data, true);
        my_data.index = null;
        const json = JSON.stringify(my_data).toLowerCase();
        if (json.indexOf(params.term.toLowerCase()) > -1) {
           return data;
        }

        // Return `null` if the term should not be displayed
        return null;
    }

    if (selectable_standards.length && selectable_standards[0].standard_name) {
        let first_node = new Select2FlowStandardData(null,null,1,null);
        first_node.text = "Nothing Selected";

        let b_set_empty_to_selected = true;
        for(let i =0; i < selectable_standards.length ; i++) {
            let node = selectable_standards[i];
            if (node.selected) {
                b_set_empty_to_selected = false;
                break;
            }
        }

        first_node.selected = b_set_empty_to_selected;
        selectable_standards.unshift(first_node);
    }


    bare_select_control.select2({
        data: selectable_standards,
        minimumInputLength: 0,
        templateResult: format_standard_in_dropdown,
        templateSelection: format_selected_standard,
        multiple: false,
        selectionCssClass: ':all:',
        matcher: matchCustom
    });

    //select2:open
    bare_select_control.on('select2:open', function () {
        $(`.select2-container--default .select2-results>.select2-results__options`).toggleClass('flow-no-max-hight');
    });

    bare_select_control.on('select2:close', function () {
        $(`.select2-container--default .select2-results>.select2-results__options`).toggleClass('flow-no-max-hight');
    });
}