CREATE TRIGGER trigger_after_insert_flow_entries_20210708205754
    AFTER INSERT ON flow_entries
    FOR EACH ROW
BEGIN
    DECLARE user_guid CHAR(32) DEFAULT NULL;
    DECLARE project_guid CHAR(32) DEFAULT NULL;
    DECLARE project_is_public TINYINT DEFAULT 0;

    SELECT HEX(p.flow_project_guid), HEX(fu.flow_user_guid),p.is_public
    INTO project_guid,user_guid,project_is_public
    FROM flow_projects p
    INNER JOIN flow_users fu on p.admin_flow_user_id = fu.id
    WHERE p.id = NEW.flow_project_id;

    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title,thing_blurb,
                            owning_user_guid,owning_project_guid,is_public)
    VALUES (NEW.id,'entry',NEW.flow_entry_guid,NEW.flow_entry_title,NEW.flow_entry_blurb,
            UNHEX(user_guid),UNHEX(project_guid),project_is_public);

END