CREATE TRIGGER trigger_before_insert_flow_project_users_20210708235839
    BEFORE INSERT ON flow_project_users
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708235839_flow_project_users
    SET NEW.flow_project_user_guid = UUID_TO_BIN(UUID(),1); -- swap out the quicker time parts for faster indexing with the 1
    SET NEW.created_at_ts = UNIX_TIMESTAMP(NOW());
END