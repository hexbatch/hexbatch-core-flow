CREATE TRIGGER trigger_after_insert_flow_projects_20210708204104
    AFTER INSERT ON flow_projects
    FOR EACH ROW
BEGIN

    DECLARE user_guid CHAR(32) DEFAULT NULL;


    -- add admin flag for owner

    INSERT INTO flow_project_users(flow_project_id, flow_user_id, can_write, can_read, can_admin,flow_project_user_guid)
    VALUES (NEW.id, NEW.admin_flow_user_id,1,1,1,'dummy')
    ON DUPLICATE KEY UPDATE
                            can_read = values(can_read),
                            can_write = values(can_write),
                            can_admin = values(can_admin)
                            ;

    SELECT  HEX(fu.flow_user_guid)
    INTO user_guid
    FROM flow_users fu
    WHERE fu.id = NEW.admin_flow_user_id;

    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title,thing_blurb,
                            is_public,owning_user_guid,owning_project_guid)
    VALUES (NEW.id,'project',NEW.flow_project_guid,NEW.flow_project_title,NEW.flow_project_blurb,
            NEW.is_public, UNHEX(user_guid),NEW.flow_project_guid);
END