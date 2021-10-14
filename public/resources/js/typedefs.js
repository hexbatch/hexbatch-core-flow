
/**
 *
 * @typedef {Object} FlowProjectUser
 * @property {string} flow_project_user_guid
 * @property {number} flow_project_user_created_at_ts
 * @property {string} flow_user_name
 * @property {string} flow_user_guid
 * @property {string} flow_project_guid
 * @property {string} flow_project_title
 * @property {string} flow_project_type
 * @property {boolean} is_project_public
 * @property {boolean} can_read
 * @property {boolean} can_write
 * @property {boolean} can_admin
 */



/**
 *
 * @typedef {Object} FlowUser
 * @property {number} [id]
 * @property {string} [text]
 * @property {string} flow_user_guid
 * @property {string} flow_user_name
 * @property {string} flow_user_email
 * @property {number} flow_user_created_at_ts
 * @property {FlowProjectUser[]} permissions
 */


/**
 *
 * @typedef {Object} FlowToken
 * @property {string} _CSRF_INDEX
 * @property {string} _CSRF_TOKEN
 */

/**
 *
 * @typedef {Object} FlowEditPermissionResponse
 * @property {boolean} success
 * @property {string} message
 * @property {?FlowToken} token
 */


/**
 *
 * @typedef {Object} FlowHistoryFileDiffResponse
 * @property {boolean} success
 * @property {string} message
 * @property {?string} diff
 * @property {?FlowToken} token
 */


/**
 * @typedef {Object} FlowTagApplied
 * @property {string} flow_applied_tag_guid
 * @property {string} tagged_flow_entry_guid
 * @property {string} tagged_flow_user_guid
 * @property {string} tagged_flow_project_guid
 * @property {string} tagged_title
 * @property {number} created_at_ts
 */




/**
 *
 * @typedef {Object} FlowTagAttribute
 * @property {number} [id]
 * @property {string} [text]
 * @property {string} flow_tag_attribute_guid
 * @property {string} flow_tag_guid
 * @property {string} flow_applied_tag_guid
 * @property {string} points_to_flow_entry_guid
 * @property {string} points_to_flow_user_guid
 * @property {string} points_to_flow_project_guid
 * @property {string} tag_attribute_name
 * @property {number} tag_attribute_long
 * @property {string} tag_attribute_text
 * @property {number} created_at_ts
 * @property {number} updated_at_ts
 * @property {boolean} is_standard_attribute
 */


/**
 *
 * @typedef {Object} FlowTagStandardAttributes
 * @property {string?} color
 * @property {string?} background_color

 */


/**
 *
 * @typedef {Object} FlowTag
 * @property {number} [id]
 * @property {string} [text]
 * @property {string} flow_tag_guid
 * @property {string} parent_tag_guid
 * @property {string} flow_project_guid
 * @property {string} flow_tag_name
 * @property {number} created_at_ts
 * @property {number} updated_at_ts
 * @property {Object.<string, FlowTagAttribute>} attributes
 * @property {Object.<string, FlowTagStandardAttributes>} standard_attributes
 * @property {?FlowTag} flow_tag_parent
 * @property {FlowTagApplied[]} applied
 */


/**
 *
 * @typedef {Object} FlowSetTagResponse
 * @property {boolean} success
 * @property {string} message
 * @property {?FlowTag} tag
 * @property {?FlowToken} token
 */






