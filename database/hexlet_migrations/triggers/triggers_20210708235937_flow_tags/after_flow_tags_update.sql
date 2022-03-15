CREATE TRIGGER trigger_after_flow_tags_update_20210708204104
    AFTER UPDATE ON flow_tags
    FOR EACH ROW
BEGIN

    
    IF  @trigger_refresh_things OR
        NEW.flow_tag_name <> OLD.flow_tag_name OR
        OLD.flow_tag_name IS NULL AND NEW.flow_tag_name IS NOT NULL
    THEN
        UPDATE flow_things f SET f.thing_title = NEW.flow_tag_name WHERE f.thing_guid = NEW.flow_tag_guid;
    END IF;

    if (@trigger_refresh_things ) THEN
        BEGIN
            DECLARE user_guid CHAR(32) DEFAULT NULL;
            DECLARE project_guid CHAR(32) DEFAULT NULL;
            DECLARE project_is_public TINYINT DEFAULT 0;

            SELECT HEX(p.flow_project_guid), HEX(fu.flow_user_guid),p.is_public
            INTO project_guid,user_guid,project_is_public
            FROM flow_projects p
                     INNER JOIN flow_users fu on p.admin_flow_user_id = fu.id
            WHERE p.id = NEW.flow_project_id;

            UPDATE flow_things f
            SET
                f.owning_project_guid = UNHEX(project_guid),
                f.owning_user_guid = UNHEX(user_guid)
            WHERE f.thing_guid = NEW.flow_tag_guid;

            INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title,
                                    owning_user_guid,owning_project_guid,is_public)
            VALUES (NEW.id,'tag',NEW.flow_tag_guid,NEW.flow_tag_name, UNHEX(user_guid),UNHEX(project_guid),project_is_public)
            ON DUPLICATE KEY UPDATE
                 thing_type = 'tag',
                 thing_title =    NEW.flow_tag_name,
                 owning_project_guid = UNHEX(project_guid),
                 owning_user_guid = UNHEX(user_guid),
                is_public = project_is_public
            ;
        END;

    END IF;

END