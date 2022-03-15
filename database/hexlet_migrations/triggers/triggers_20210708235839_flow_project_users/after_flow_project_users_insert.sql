CREATE TRIGGER trigger_after_flow_project_users_insert_20210708235839
    AFTER INSERT ON flow_project_users
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708235839_flow_project_users
    DECLARE reader_user_guid CHAR(32) DEFAULT NULL;
    DECLARE project_guid CHAR(32) DEFAULT NULL;
    DECLARE reader_already_inserted_id INT DEFAULT NULL;
    DECLARE msg VARCHAR(255);

    IF NEW.can_read = 1 THEN

        SELECT
            HEX(u.flow_user_guid)
        INTO
            reader_user_guid
        FROM flow_users u
        WHERE u.id = NEW.flow_user_id;

        SELECT
            HEX(p.flow_project_guid)
        INTO
            project_guid
        FROM  flow_projects p
        WHERE p.id = NEW.flow_project_id;

        IF (reader_user_guid IS NULL OR project_guid IS NULL) THEN
            SET msg := CONCAT('Flow Project Users cannot find the user and project guids!, id [', NEW.id,
                              '] user [', IF(reader_user_guid IS NULL, '{null}',reader_user_guid),
                              '] project [', IF(project_guid IS NULL, '{null}',project_guid), ']');
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = msg;
        END IF;

        SELECT f.id INTO reader_already_inserted_id
        FROM flow_things f WHERE
                f.thing_guid  = UNHEX(project_guid)   AND
            JSON_SEARCH(f.allowed_readers_json,'one',reader_user_guid) IS NOT NULL ;

        IF (reader_already_inserted_id IS NULL) THEN
            UPDATE flow_things f
            SET f.allowed_readers_json = JSON_ARRAY_APPEND(f.allowed_readers_json, '$', reader_user_guid)
            where f.thing_guid  = UNHEX(project_guid) ;
        END IF;
    END IF;


END