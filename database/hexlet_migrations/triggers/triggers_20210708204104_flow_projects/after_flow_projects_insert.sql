CREATE TRIGGER trigger_after_insert_flow_projects_20210708204104
    AFTER INSERT ON flow_projects
    FOR EACH ROW
BEGIN
    -- add admin flag for owner

    INSERT INTO flow_project_users(flow_project_id, flow_user_id, can_write, can_read, can_admin,flow_project_user_guid)
    VALUES (NEW.id, NEW.admin_flow_user_id,1,1,1,'dummy')
    ON DUPLICATE KEY UPDATE
                            can_read = values(can_read),
                            can_write = values(can_write),
                            can_admin = values(can_admin)
                            ;

    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title)
    VALUES (NEW.id,'project',NEW.flow_project_guid,NEW.flow_project_title);
END