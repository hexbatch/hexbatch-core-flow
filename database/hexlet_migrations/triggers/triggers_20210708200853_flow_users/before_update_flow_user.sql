CREATE TRIGGER trigger_before_update_flow_user_20210708200853
    BEFORE UPDATE ON flow_users
    FOR EACH ROW
BEGIN

    IF @trigger_refresh_things OR NEW.flow_user_name <> OLD.flow_user_name OR
       OLD.flow_user_name IS NULL AND NEW.flow_user_name IS NOT NULL
    THEN
        UPDATE flow_things f SET f.thing_title = NEW.flow_user_name WHERE f.thing_guid = NEW.flow_user_guid;
    END IF;

END