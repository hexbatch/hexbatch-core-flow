CREATE TRIGGER trigger_after_flow_tags_insert_20210708235937
    AFTER INSERT ON flow_tags
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708235937_flow_tags

    DECLARE user_guid CHAR(32) DEFAULT NULL;
    DECLARE project_guid CHAR(32) DEFAULT NULL;
    DECLARE project_is_public TINYINT DEFAULT 0;

    SELECT HEX(p.flow_project_guid), HEX(fu.flow_user_guid),p.is_public
    INTO project_guid,user_guid,project_is_public
    FROM flow_projects p
             INNER JOIN flow_users fu on p.admin_flow_user_id = fu.id
    WHERE p.id = NEW.flow_project_id;

    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title,owning_user_guid,owning_project_guid,is_public)
    VALUES (NEW.id,'tag',NEW.flow_tag_guid,NEW.flow_tag_name, UNHEX(user_guid),UNHEX(project_guid),project_is_public);
END