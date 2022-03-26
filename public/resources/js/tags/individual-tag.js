/**
 *
 * @param {FlowTag} tag
 * @returns {Object}
 */
function get_tag_style(tag) {

    return tag.css;
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
 * @type {Object.<string, FlowTag>} remember_da_fallen_tags
 */
let remember_da_fallen_tags = {};

/**
 *
 * @param {FlowTag} tag
 */
function update_tag_name_and_style_in_page(tag) {
    let tag_map = {}
    tag_map[tag.flow_tag_guid] = tag;
    let victims = $(`.flow-tag-display[data-tag_guid="${tag.flow_tag_guid}"]`);
    add_tag_attributes_to_dom(tag_map,victims,false);

    victims.text(tag.flow_tag_name);

    if (tag.flow_tag_guid in remember_da_fallen_tags) {
        victims.addClass('text-decoration-line-through');
    }
}

/**
 *
 * @param {FlowTag} tag
 */
function mark_tag_as_deleted_in_page(tag) {

    let victims = $(`.flow-tag-display[data-tag_guid="${tag.flow_tag_guid}"]`);
    victims.addClass('text-decoration-line-through');
    remember_da_fallen_tags[tag.flow_tag_guid] = tag;
}

/**
 *
 * @param {Object<string, FlowTag>}  tag_map
 * @param {string|jQuery} tags_here_jquery_or_string
 * @param {boolean} b_allow_links
 */
function add_tag_attributes_to_dom(tag_map,tags_here_jquery_or_string,b_allow_links) {
    let tags_here = $(tags_here_jquery_or_string);
    if (!tags_here.length) {return;}

    tags_here.each(function() {
        let tag_dom = $(this);
        let tag_guid = tag_dom.data('tag_guid');
        if (!tag_guid) {return;}
        if (tag_guid in tag_map) {

            /**
             * @type {FlowTag} tag
             */
            let tag = tag_map[tag_guid];
            let tag_style = get_tag_style(tag);
            let tag_classes = get_tag_classes(tag);
            let tag_data = get_tag_data(tag);

            tag_dom.attr('style','');
            tag_dom.css(tag_style);

            tag_dom.addClass(tag_classes.join(' '));

            for(let data_key in tag_data) {
                if (!b_allow_links && (data_key === 'tag_link_url')) {continue;}
                tag_dom.data(data_key,tag_data);
                if (typeof tag_data === 'string' || tag_data instanceof String) {
                    tag_dom.attr('data-'+data_key,tag_data);
                }
            }

            if (tag.flow_tag_guid in remember_da_fallen_tags) {
                tag_dom.addClass('text-decoration-line-through');
            }
        }
    });
}