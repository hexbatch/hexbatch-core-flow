/**
 *
 * @param  bare_select_control
 * @param {boolean} b_multi
 * @param {?string} placeholder
 * @param {?string} not_guid
 * @param {?string} [search_types]  project|user|tag|entry|all|not-tags
 */
function create_select_2_for_general_search(bare_select_control,b_multi,
                                        placeholder,not_guid,search_types) {

    if (!search_types) { search_types = 'not-tags';}
    /**
     *
     * @param {GeneralSearchResult} general
     * @param {string} [extra_class]
     * @return {string}
     */
    function format_general(general,extra_class) {
        extra_class = extra_class??'';
        if (!(general.guid || general.text)) {return '';}
        if (general.guid) {
            let icon = '';
            if (general.type === 'project') {
                icon = `<i class="bi bi-box"></i>`;
            } else if (general.type === 'user') {
                icon = `<i class="bi-person-circle"></i>`;
            } else if (general.type === 'tag') {
                icon = `<i class="bi bi-tag"></i>`;
            }
            let display = `${icon} <span class="${extra_class}">${general.title}</span>`;

            return jQuery(display);
        } else {
            let display = `<span class="${extra_class}">${general.text}</span>`;
            return jQuery(display);
        }
    }

    function format_general_in_dropdown (attribute) {
        return format_general(attribute);

    }

    /**
     *
     * @param {GeneralSearchResult} general
     * @return {string}
     */
    function format_selected_tag (general) {
        return format_general(general);
    }



    function process_results_for_dropdown (data) {

        let id_thing = 10;
        let ret = [];
        for(let i in data.results) {
            /**
             * @type {GeneralSearchResult}
             */
            let general = data.results[i];
            if (not_guid) {
                if (general.guid === not_guid) { continue;}
            }

            general.id = id_thing ++;
            general.text = general.title + '';
            ret.push(general);
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
            search: {term: params.term || null, types : search_types},
            page: params.page || 1
        }
        //console.log("params",params);

        // Query parameters will be ?search=[term]&page=[page]
        return query;
    }

    bare_select_control.select2({
        ajax: {
            url: general_search_url,
            delay: 250 ,// wait 250 milliseconds before triggering the request
            data: prepare_query_for_dropdown,
            processResults: process_results_for_dropdown,
        },
        minimumInputLength: 1,
        templateResult: format_general_in_dropdown,
        templateSelection: format_selected_tag,

        placeholder: placeholder?? '',
        allowClear: true,
        multiple: !!b_multi,
        selectionCssClass: ':all:',
    });
}