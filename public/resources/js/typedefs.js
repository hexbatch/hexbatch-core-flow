
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



