CREATE TRIGGER trigger_after_delete_flow_user_20210708200853
    AFTER DELETE ON flow_users
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708200853_flow_users
    DELETE FROM  flow_things WHERE thing_type = 'user' AND thing_id = OLD.id;
    DELETE FROM  flow_things WHERE thing_type = 'user' AND thing_guid = OLD.flow_user_guid;
END