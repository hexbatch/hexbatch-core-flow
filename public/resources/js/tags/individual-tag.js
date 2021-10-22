/**
 *
 * @param {FlowTag} tag
 * @param {boolean} js_css
 * @returns {Object}
 */
function get_tag_style(tag,js_css) {

    let per_attribute = {};

    for(let attribute_key in tag.standard_attributes) {
        let attribute_value = tag.standard_attributes[attribute_key];
        switch (attribute_key) {
            case 'background-color': {
                if (attribute_value) {
                    let rule = `${attribute_value}`;
                    let rule_key = attribute_key;
                    if (js_css) {
                        rule_key = 'background-color';
                    }
                    per_attribute[rule_key] =  rule;
                }
                break;
            }
            case 'color': {
                if (attribute_value) {
                    let rule = `${attribute_value}`;
                    per_attribute[attribute_key] =  rule;
                }
                break;
            }
        }
    }
    return per_attribute;
}

/**
 *
 * @param {FlowTag} tag
 * @returns {string[]}
 */
function get_tag_classes(tag) {

    let per_attribute = [];

    for(let attribute_key in tag.standard_attributes) {
        switch (attribute_key) {
            case 'link': {
                if (attribute_key) {
                    per_attribute.push('fl-tag-link') ;
                }
                break;
            }
        }
    }
    return per_attribute;
}

/**
 *
 * @param {FlowTag} tag
 * @returns {Object}
 */
function get_tag_data(tag) {

    let per_attribute = {};

    for(let attribute_key in tag.standard_attributes) {
        let attribute_value = tag.standard_attributes[attribute_key];
        switch (attribute_key) {
            case 'link': {
                if (attribute_key) {
                    per_attribute['tag_link_url'] =  `${attribute_value}`;
                }
                break;
            }
        }
    }
    return per_attribute;
}

/**
 *
 * @param {Object.<string, FlowTag>}  tag_map
 * @param {string|jQuery} tags_here_jquery_or_string
 */
function add_tag_attributes_to_dom(tag_map,tags_here_jquery_or_string) {
    let tags_here = $(tags_here_jquery_or_string);
    if (!tags_here.length) {return;}

    tags_here.each(function() {
        let tag_dom = $(this);
        let tag_guid = tag_dom.data('tag_guid');
        if (!tag_guid) {console.warn("No tag guid found for ",tag_dom); return;}
        if (tag_guid in tag_map) {

            /**
             * @type {FlowTag} tag
             */
            let tag = tag_map[tag_guid];
            let tag_style = get_tag_style(tag,true);
            let tag_classes = get_tag_classes(tag);
            let tag_data = get_tag_data(tag);

            tag_dom.css(tag_style);

            tag_dom.addClass(tag_classes.join(' '));

            for(let data_key in tag_data) {
                tag_dom.data(data_key,tag_data);
                if (typeof tag_data === 'string' || tag_data instanceof String) {
                    tag_dom.attr(data_key,tag_data);
                }
            }
        }
    });
}