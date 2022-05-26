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
            } else if (general.type === 'entry') {
                icon = `<i class="bi bi-columns"></i>`;
            } else if (general.type === 'node') {
                icon = `<i class="bi bi-type"></i>`;
                extra_class += " fst-italic ";
            }

            let display = `<span class="d-inline p-1">${icon} <span class="${extra_class}">${general.title??general.words}</span></span>`;
            let tode = jQuery(display);
            if(!_.isEmpty(general.css_object)) {
                tode.css(general.css_object);
            }

            return tode;
        } else {
            let display = `<span class="${extra_class}">${general.text}</span>`;
            return jQuery(display);
        }
    }

    /**
     *
     * @param {GeneralSearchResult} general
     * @return {string}
     */
    function get_detail(general) {
        switch (general.type) {
            case 'project': {
                let security;
                let allowed_users = '';

                if (general.is_public) {
                    security = `<span class="text-success"><i class="fas fa-lock-open"></i></span><span class="text-success ms-1" >Public  </span>`;
                } else {
                    security = `<span class="text-info"><i class="fas fa-lock "></i></span>`;
                    allowed_users += `<span>`;
                    for(let i = 0; i < general.allowed_readers_results.length; i++) {
                        allowed_users += `<i class="text-black-50 ms-1"> ${general.allowed_readers_results[i].title} </i>`;
                    }
                    allowed_users += `</span>`;
                }

                return jQuery(`<span class="d-inline">
                            ${security} ${allowed_users}
                            <span class="d-block">
                                ${general.blurb}
                            </span>
                        </span>`);

            }
            case 'entry': {

                let security;
                if (general.is_public) {
                    security = `<span class="text-success"><i class="fas fa-lock-open"></i></span>`;
                } else {
                    security = `<span class="text-info"><i class="fas fa-lock "></i></span>`;
                }

                return jQuery(`<span class="d-inline">
                            
                            <span class="d-block">
                                <span class="fw-bold d-inline me-1">Entry</span>
                                ${general.blurb}
                                <span class="d-block ms-1">
                                    ${security} ${general.owning_project_result.title}
                                </span>
                            </span>
                        </span>`);
            }
            case 'node': {


                return jQuery(`<span class="d-inline">
                            
                            <span class="d-block">
                                <span class="fw-bold d-inline me-1">From ${general.owning_entry_title}</span>
                            </span>
                        </span>`);
            }
            case 'tag': {
                let security;
                if (general.is_public) {
                    security = `<span class="text-success"><i class="fas fa-lock-open"></i></span>`;
                } else {
                    security = `<span class="text-info"><i class="fas fa-lock "></i></span>`;
                }

                let used_by = '';
                used_by += `<span>`;
                for(let i = 0; i < general.tag_used_by_results.length; i++) {
                    used_by += `<i class="text-black-50 ms-1"> ${general.tag_used_by_results[i].title} </i>`;
                }
                used_by += `</span>`;
                return jQuery(`<span class="d-inline">
                            
                            <span class="d-block">
                                ${used_by}
                            </span>
                             <span class="d-block ms-1">
                                    ${security} ${general.owning_project_result.title}
                            </span>
                        </span>`);
            }
            default: {
                return jQuery('');
            }
        }
    }

    function format_general_in_dropdown (attribute) {
        let first_part =  format_general(attribute);
        let second_part = get_detail(attribute);

        let parent =  jQuery(`<span class="d-inline"></span>`);
        parent.append(first_part);
        parent.append(second_part);
        return parent;
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