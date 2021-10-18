CREATE TRIGGER trigger_before_insert_flow_users_20210708200853
    BEFORE INSERT ON flow_users
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708200853_flow_users
    SET NEW.flow_user_guid = UUID_TO_BIN(UUID(),1); -- swap out the quicker time parts for faster indexing with the 1
    SET NEW.created_at_ts = UNIX_TIMESTAMP(NOW());

    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title)
    VALUES (NEW.id,'user',NEW.flow_user_guid,NEW.flow_user_name);
END