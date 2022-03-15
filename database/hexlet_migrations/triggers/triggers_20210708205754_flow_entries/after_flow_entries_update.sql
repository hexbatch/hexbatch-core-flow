CREATE TRIGGER trigger_after_flow_entries_update_20210708204104
    AFTER UPDATE ON flow_entries
    FOR EACH ROW
BEGIN
    
    IF @trigger_refresh_things OR
        NEW.flow_entry_title <> OLD.flow_entry_title OR
        OLD.flow_entry_title IS NULL AND NEW.flow_entry_title IS NOT NULL
    THEN
        UPDATE flow_things f SET f.thing_title = NEW.flow_entry_title WHERE f.thing_guid = NEW.flow_entry_guid;
    END IF;

    IF @trigger_refresh_things OR
        NEW.flow_entry_blurb <> OLD.flow_entry_blurb OR
        OLD.flow_entry_blurb IS NULL AND NEW.flow_entry_blurb IS NOT NULL
    THEN
        UPDATE flow_things f SET f.thing_blurb = NEW.flow_entry_blurb WHERE f.thing_guid = NEW.flow_entry_guid;
    END IF;

    if (@trigger_refresh_things ) THEN
        BEGIN
            DECLARE user_guid CHAR(32) DEFAULT NULL;
            DECLARE project_guid CHAR(32) DEFAULT NULL;
            DECLARE project_is_public TINYINT DEFAULT 0;


            SELECT HEX(p.flow_project_guid), HEX(fu.flow_user_guid),p.is_public
            INTO project_guid,user_guid,project_is_public
            FROM flow_entries e
                     INNER JOIN  flow_projects p ON p.id = e.flow_project_id
                     INNER JOIN flow_users fu on p.admin_flow_user_id = fu.id
            WHERE e.id = NEW.id;

            INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title,thing_blurb,
                                    owning_user_guid,owning_project_guid,is_public)
            VALUES (NEW.id,'entry',NEW.flow_entry_guid,NEW.flow_entry_title,NEW.flow_entry_blurb,
                    UNHEX(user_guid),UNHEX(project_guid),project_is_public)
            ON DUPLICATE KEY UPDATE
                 thing_type = 'entry',
                 thing_title =    NEW.flow_entry_title,
                 thing_blurb =    NEW.flow_entry_blurb,
                 owning_project_guid = UNHEX(project_guid),
                 owning_user_guid = UNHEX(user_guid),
                 is_public = project_is_public
            ;
        END;

    END IF;


END