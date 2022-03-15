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

    IF @trigger_refresh_things OR NEW.flow_project_title <> OLD.flow_project_title OR
       OLD.flow_project_title IS NULL AND NEW.flow_project_title IS NOT NULL THEN
        UPDATE flow_things f SET f.thing_title = NEW.flow_project_title WHERE f.thing_guid = NEW.flow_project_guid;
    END IF;

    IF @trigger_refresh_things OR NEW.flow_project_blurb <> OLD.flow_project_blurb OR
       OLD.flow_project_blurb IS NULL AND NEW.flow_project_blurb IS NOT NULL THEN
        UPDATE flow_things f SET f.thing_blurb = NEW.flow_project_blurb WHERE f.thing_guid = NEW.flow_project_guid;
    END IF;

    IF @trigger_refresh_things OR NEW.is_public <> OLD.is_public  THEN
        UPDATE flow_things f SET f.is_public = NEW.is_public WHERE f.thing_guid = NEW.flow_project_guid;
    END IF;

    if (@trigger_refresh_things ) THEN
        BEGIN
            DECLARE user_guid CHAR(32) DEFAULT NULL;

            SELECT  HEX(fu.flow_user_guid)
            INTO user_guid
            FROM flow_users fu
            WHERE fu.id = NEW.admin_flow_user_id;

            UPDATE flow_things f
            SET
                f.owning_project_guid = NEW.flow_project_guid ,
                f.owning_user_guid = UNHEX(user_guid)

            WHERE f.thing_guid = NEW.flow_project_guid;

            INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title,thing_blurb,
                                    is_public,owning_user_guid,owning_project_guid)
                VALUES (NEW.id,'project',NEW.flow_project_guid,NEW.flow_project_title,NEW.flow_project_blurb,
                        NEW.is_public, UNHEX(user_guid), NEW.flow_project_guid)
            ON DUPLICATE KEY UPDATE
                 thing_type = 'project',
                 thing_title =    NEW.flow_project_title,
                 thing_blurb =    NEW.flow_project_blurb,
                 is_public = NEW.is_public,
                 owning_user_guid = UNHEX(user_guid);
        END;

    END IF;
END