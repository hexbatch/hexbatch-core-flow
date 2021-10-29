CREATE TRIGGER trigger_after_insert_flow_users_20210708200853
    AFTER INSERT ON flow_users
    FOR EACH ROW
BEGIN

    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title)
    VALUES (NEW.id,'user',NEW.flow_user_guid,NEW.flow_user_name);
END