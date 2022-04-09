

/**
 *
 * @typedef {Object} FlowProject
 * @property {?FlowUser} admin_user
 * @property {?string} flow_project_title
 * @property {?string} flow_project_guid
 * @property {?string} flow_project_blurb
 * @property {?number} created_at_ts


 */

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
 * @property {?string} flow_applied_tag_guid
 * @property {?string} flow_tag_guid
 * @property {string} tagged_flow_entry_guid
 * @property {string} tagged_flow_user_guid
 * @property {string} tagged_flow_project_guid
 * @property {?string} tagged_url
 * @property {string} [tagged_title]
 * @property {number} [created_at_ts]
 */




/**
 *
 * @typedef {Object} FlowTagAttribute
 * @property {number} [id]
 * @property {string} [text]
 * @property {?string} flow_tag_attribute_guid
 * @property {?string} flow_tag_guid
 * @property {?string} points_to_flow_entry_guid
 * @property {?string} points_to_flow_user_guid
 * @property {?string} points_to_flow_project_guid
 * @property {?string} tag_attribute_name
 * @property {?number} tag_attribute_long
 * @property {?string} tag_attribute_text
 * @property {?number} created_at_ts
 * @property {?number} updated_at_ts
 * @property {?boolean} is_inherited
 * @property {?string} points_to_title
 * @property {?string} points_to_admin_guid
 * @property {?string} points_to_admin_name
 * @property {?string} points_to_url
 */


/**
 *
 * @typedef {Object} FlowStandardAttributes
 * @property {string?} standard_name
 * @property {Object?} standard_value
 * @property {int?} standard_updated_ts
 * @property {string?} tag_guid
 * @property {string?} project_guid
 * @property {string?} standard_guid

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
 * @property {Object.<string, FlowStandardAttributes>} standard_attributes
 * @property {Object.<string, string>} css
 * @property {?FlowTag} flow_tag_parent
 * @property {FlowTagApplied[]} applied
 * @property {?FlowProject} flow_project
 */






/**
 *
 * @typedef {Object} FlowBasicResponse
 * @property {boolean} success
 * @property {string} message
 * @property {?FlowToken} token
 */

/**
 *
 * @typedef {FlowBasicResponse} FlowTagResponse
 * @property {?FlowTag} tag
 */

/**
 *
 * @typedef {FlowTagResponse} FlowAttributeResponse
 * @property {?FlowTagAttribute} [attribute]
 */

/**
 *
 * @typedef {FlowAttributeResponse} FlowAppliedResponse
 * @property {FlowTagApplied} [applied]
 */

/**
 *
 * @callback FlowTagActionCallback
 * @param {FlowTagResponse|FlowAttributeResponse|FlowAppliedResponse} data
 */


/**
 *
 * @callback FlowTagAttributeEditCallback
 * @param {FlowTag} data
 */


/**
 *
 * @callback FlowTagAppliedCreateCallback
 * @param {FlowTag} data
 */

/**
 *
 * @callback FlowTagEditCallback
 * @param {FlowTag} data
 */






/**
 *
 * @typedef {Object} FlowPagination
 * @property {boolean} more
 * @property {number} page
 */

/**
 *
 * @typedef {Object} FlowTagSearchResponse
 * @property {pagination} FlowPagination
 * @property {FlowTag[]} results
 */

/**
 *
 * @callback FlowTagSearchCallback
 * @param {FlowTag} tag
 */

/**
 *
 * @callback FlowTagsSearchCallback
 * @param {FlowTag[]} tags
 */


/**
 * @typedef {Object} GeneralSearchResult
 * @property {string} guid
 * @property {number} created_at_ts
 * @property {number} updated_at_ts
 * @property {string} title
 * @property {string} blurb
 * @property {string} type
 * @property {string} url
 * @property {boolean} is_public
 * @property {Object} css_object
 * @property {Array} tag_used_by
 * @property {Array} allowed_readers
 * @property {string} owning_project_guid
 * @property {string} owning_user_guid
 * @property {GeneralSearchResult[]} allowed_readers_results
 * @property {GeneralSearchResult[]} tag_used_by_results
 * @property {?GeneralSearchResult} owning_user_result
 * @property {?GeneralSearchResult} owning_project_result
 * @property {number} [id]
 * @property {string} [text]
 */


/**
 *
 * @typedef {FlowBasicResponse} FlowStandardResponse
 * @property {?FlowTag} tag
 * @property {?string} action
 * @property {?string} standard_name
 * @property {?Object} standard_data
 */


/**
 *
 * @typedef {FlowBasicResponse} FlowUploadResourceResponse
 * @property {?string} action
 * @property {?string} file_name
 * @property {?string} new_file_path
 * @property {?string} new_file_url
 */


/**
 *
 * @callback FlowStandardCallback
 * @param {FlowStandardResponse} data
 */

//  meta standard

/**
 *
 * @typedef {Object} StandardMeta
 * @property {?string} meta_version
 * @property {?string} meta_date_time
 * @property {?string} meta_author
 * @property {?string} meta_first_name
 * @property {?string} meta_last_name
 * @property {?string} meta_public_email
 * @property {?string} meta_picture_url
 * @property {?string} meta_website
 */


//  git standard

/**
 *
 * @typedef {Object} StandardGit
 * @property {?string} git_url
 * @property {?string} git_ssh_key
 * @property {?string} git_branch
 * @property {?string} git_notes
 * @property {?string} git_web_page
 */

//css standard


/**
 *
 * @typedef {Object} StandardCss
 * @property {?string} fontFamily
 * @property {?string} css
 * @property {?string} backgroundColor
 * @property {?string} color
 */





