CREATE TRIGGER trigger_after_insert_flow_users_20210708200853
    AFTER INSERT ON flow_users
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708200853_flow_users
    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title,is_public,owning_user_guid)
    VALUES (NEW.id,'user',NEW.flow_user_guid,NEW.flow_user_name,1,NEW.flow_user_guid);
END