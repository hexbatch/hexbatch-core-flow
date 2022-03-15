CREATE TRIGGER trigger_after_update_flow_users_20210708200853
    AFTER UPDATE ON flow_users
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708200853_flow_users

    if (@trigger_refresh_things OR NEW.flow_user_name <> OLD.flow_user_name) THEN
        UPDATE flow_things f SET f.thing_title = NEW.flow_user_name WHERE f.thing_guid = NEW.flow_user_guid;
    END IF;

    if (@trigger_refresh_things ) THEN

        INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title,is_public)
        VALUES (NEW.id,'user',NEW.flow_user_guid,NEW.flow_user_name,1)
        ON DUPLICATE KEY UPDATE
             thing_type = 'user',
             thing_title =    NEW.flow_user_name,
             owning_user_guid = NEW.flow_user_guid;
    END IF;

END