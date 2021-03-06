CREATE TRIGGER after_node_insert_20220509154921
    AFTER INSERT ON flow_entry_nodes
    FOR EACH ROW
BEGIN
    IF(
        (NEW.bb_tag_name = 'text' AND NEW.entry_node_words NOT REGEXP '^[:space:]*$' )
            OR
        NEW.bb_tag_name = 'flow_tag'
    ) THEN
        BEGIN
            DECLARE user_guid CHAR(32) DEFAULT NULL;
            DECLARE project_guid CHAR(32) DEFAULT NULL;
            DECLARE entry_guid CHAR(32) DEFAULT NULL;
            DECLARE project_is_public TINYINT DEFAULT 0;
            DECLARE thing_type_deduced VARCHAR(10) DEFAULT 'node';

            if (NEW.bb_tag_name = 'flow_tag') THEN
                SET thing_type_deduced := 'node_tag';
            END IF;

            SELECT HEX(fp.flow_project_guid), HEX(fu.flow_user_guid),fp.is_public, HEX(e.flow_entry_guid)
            INTO project_guid,user_guid,project_is_public, entry_guid
            FROM flow_entries e
                     INNER JOIN flow_projects fp on e.flow_project_id = fp.id
                     INNER JOIN flow_users fu on fp.admin_flow_user_id = fu.id

            WHERE e.id = NEW.flow_entry_id;


            INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_text,
                                    owning_user_guid,owning_project_guid,owning_entry_guid,is_public)
            VALUES (NEW.id,thing_type_deduced,NEW.entry_node_guid,NEW.entry_node_words,
                    UNHEX(user_guid),UNHEX(project_guid),UNHEX(entry_guid),project_is_public);
        END;
    END IF;

END