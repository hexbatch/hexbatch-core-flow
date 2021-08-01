CREATE TRIGGER trigger_after_update_flow_projects_20210708204104
    AFTER UPDATE ON flow_projects
    FOR EACH ROW
BEGIN
    -- add admin flag if admin changed

    IF (OLD.admin_flow_user_id <> NEW.admin_flow_user_id) THEN
    INSERT INTO flow_project_users(flow_project_id, flow_user_id, can_write, can_read, can_admin,flow_project_user_guid)
    VALUES (NEW.id, NEW.admin_flow_user_id,1,1,1,'dummy')
    ON DUPLICATE KEY UPDATE
                            can_read = values(can_read),
                            can_write = values(can_write),
                            can_admin = values(can_admin)
                            ;
    END IF;
END