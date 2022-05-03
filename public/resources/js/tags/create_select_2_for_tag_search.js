/**
 *
 * @param  bare_select_control
 * @param {boolean} b_multi
 * @param {?string} placeholder
 * @param {boolean} b_tags
 * @param {?string} not_tag_guid
 * @param {?string[]} only_applied_to_guids
 * @param {?string[]} not_applied_to_guids
 */
function create_select_2_for_tag_search(bare_select_control,b_multi,
                                        placeholder,b_tags,not_tag_guid,
                                        only_applied_to_guids,not_applied_to_guids,) {
    /**
     *
     * @param {FlowTag} tag
     * @param {string} [extra_class]
     * @return {string}
     */
    function format_tag(tag,extra_class) {
        extra_class = extra_class??'';
        if (!(tag.flow_tag_guid || tag.text)) {return '';}
        if (tag.flow_tag_guid) {

            let tag_style_object = get_tag_style(tag);
            let tag_classes = get_tag_classes(tag).join(' ');

            let display = `<span `+
                `class="flow-tag-display ${extra_class} ${tag_classes}" `+
                `data-guid="${tag.flow_tag_guid}"`+
                `>`+
                `${tag.flow_tag_name}`+
                `</span>`;

            let tode = jQuery(display);
            tode.css(tag_style_object);
            return tode;
        } else {
            let display = `<span `+
                `class="flow-tag-display ${extra_class}" `+
                `data-text="${tag.text}"`+
                `>`+
                `${tag.text}`+
                `</span>`;
            return jQuery(display);
        }
    }

    function format_tag_in_dropdown (tag) {
        return format_tag(tag);

    }

    /**
     *
     * @param {FlowTag} tag
     * @return {string}
     */
    function format_selected_tag (tag) {
        return format_tag(tag,'flow-tag-no-borders-padding');
    }

    function  select2_create_tag (params) {
        let term = $.trim(params.term);

        if (term === '') {
            return null;
        }

        return {
            id: term,
            text: term,
            is_new_tag: true,
            flow_tag_guid: null,
            parent_tag_guid: null,
            flow_project_guid: null,
            flow_tag_name: term,
            created_at_ts: null,
            attributes: [],
            standard_attributes: []

        }

    }

    function process_results_for_dropdown (data) {

        let id_thing = 10;
        let ret = [];
        for(let i in data.results) {
            /**
             * @type {FlowTag}
             */
            let tag = data.results[i];
            if (not_tag_guid) {
                if (tag.flow_tag_guid === not_tag_guid) { continue;}
            }

            tag.id = id_thing ++;
            tag.text = tag.flow_tag_name;
            ret.push(tag);
        }
        data.results = ret;
        return data;
    }

    /**
     *
     * @param  params
     */
    function prepare_query_for_dropdown(params) {
        let query = {
            search: {
                term: params.term || null,
                only_applied_to_guids: only_applied_to_guids,
                not_applied_to_guids: not_applied_to_guids
            },
            page: params.page || 1
        }
        //console.log("params",params);

        // Query parameters will be ?search=[term]&page=[page]
        return query;
    }

    bare_select_control.select2({
        ajax: {
            url: get_tags_ajax_url,
            delay: 250 ,// wait 250 milliseconds before triggering the request
            data: prepare_query_for_dropdown,
            processResults: process_results_for_dropdown,
        },
        minimumInputLength: 1,
        templateResult: format_tag_in_dropdown,
        templateSelection: format_selected_tag,

        placeholder: placeholder?? '',
        allowClear: true,
        multiple: !!b_multi,
        tags: !!b_tags,
        createTag: select2_create_tag,
        selectionCssClass: ':all:',
    });
}