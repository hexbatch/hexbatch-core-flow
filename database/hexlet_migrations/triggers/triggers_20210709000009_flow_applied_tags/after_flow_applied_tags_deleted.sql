CREATE TRIGGER trigger_after_flow_applied_tags_deleted_20210709000009
    AFTER DELETE ON flow_applied_tags
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210709000009_flow_applied_tags
    DECLARE target_guid CHAR(32) DEFAULT NULL;
    DECLARE tag_guid CHAR(32) DEFAULT NULL;
    DECLARE target_already_inserted_id INT DEFAULT NULL;


    IF OLD.tagged_flow_entry_id IS NOT NULL THEN

        SELECT
            HEX(e.flow_entry_guid)
        INTO
            target_guid
        FROM flow_entries e
        WHERE e.id = OLD.tagged_flow_entry_id;

    ELSEIF OLD.tagged_flow_user_id IS NOT NULL THEN

        SELECT
            HEX(u.flow_user_guid)
        INTO
            target_guid
        FROM flow_users u
        WHERE u.id = OLD.tagged_flow_user_id;

    ELSEIF OLD.tagged_flow_project_id IS NOT NULL THEN

        SELECT
            HEX(p.flow_project_guid)
        INTO
            target_guid
        FROM flow_projects p
        WHERE p.id = OLD.tagged_flow_project_id;

    END IF;


    IF target_guid IS NOT NULL THEN
        SELECT
            HEX(t.flow_tag_guid)
        INTO
            tag_guid
        FROM  flow_tags t
        WHERE t.id = OLD.flow_tag_id;


        SELECT f.id INTO target_already_inserted_id
        FROM flow_things f WHERE
                f.thing_guid  = UNHEX(tag_guid)   AND
            JSON_SEARCH(f.tag_used_by_json,'one',target_guid) IS NOT NULL ;

        IF (target_already_inserted_id IS NOT NULL) THEN
            UPDATE flow_things f
            SET f.tag_used_by_json =
                    JSON_REMOVE(
                            f.tag_used_by_json,
                            JSON_UNQUOTE(
                                    JSON_SEARCH(f.tag_used_by_json,'one',target_guid)
                                )
                        )
            WHERE JSON_SEARCH(f.tag_used_by_json,'one',target_guid) IS NOT NULL
              AND  f.thing_guid  = UNHEX(tag_guid);

        END IF;

    END IF;



END