CREATE TRIGGER trigger_before_update_flow_entries_20210708205754
    BEFORE UPDATE
    ON flow_entries
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708205754_flow_entries

    DECLARE bad_pointer INT DEFAULT NULL;
    DECLARE msg VARCHAR(255);

    -- don't allow new parent to be something that points to this (no recursive ancestor chains)
    -- no chains where we have the ancestry of this including something that points back

    if (NEW.flow_entry_parent_id IS NOT NULL)
    THEN
        SET bad_pointer :=
                (WITH RECURSIVE object_paths AS (
                    SELECT id, flow_entry_parent_id, 0 as da_count
                    FROM flow_entries
                    where id = NEW.flow_entry_parent_id
                    UNION ALL
                    SELECT par.id, par.flow_entry_parent_id, op.da_count + 1
                    FROM flow_entries par
                             INNER JOIN object_paths op ON op.flow_entry_parent_id = par.id
                    WHERE op.id IS NOT NULL
                      AND op.da_count < 500
                )

                 SELECT fo.id
                 FROM object_paths fo
                 WHERE fo.flow_entry_parent_id = NEW.id
                 ORDER BY fo.id
                 LIMIT 1);


        IF bad_pointer IS NOT NULL
        THEN
            SET msg := CONCAT('Cyclical Ancestry is not allowed in flow_entries, id [', bad_pointer,
                              '] is a child of id [', NEW.id, ']');
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = msg;
        END IF;
    END IF;

    IF NEW.flow_entry_title <> OLD.flow_entry_title OR
       OLD.flow_entry_title IS NULL AND NEW.flow_entry_title IS NOT NULL THEN
        UPDATE flow_things f SET f.thing_title = NEW.flow_entry_title WHERE f.thing_guid = NEW.flow_entry_guid;
    END IF;
END