CREATE TRIGGER trigger_before_update_flow_tags_20210708235937
    BEFORE UPDATE ON flow_tags
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708235937_flow_tags

    DECLARE bad_pointer INT DEFAULT NULL;
    DECLARE msg VARCHAR(255);

    -- don't allow new parent to be something that points to this (no recursive ancestor chains)
    -- no chains where we have the ancestry of this including something that points back

    if (NEW.parent_tag_id IS NOT NULL)
    THEN
        SET bad_pointer :=
                (WITH RECURSIVE object_paths AS (
                    SELECT id, parent_tag_id, 0 as da_count
                    FROM flow_tags
                    where id = NEW.parent_tag_id
                    UNION ALL
                    SELECT par.id, par.parent_tag_id, op.da_count + 1
                    FROM flow_tags par
                             INNER JOIN object_paths op ON op.parent_tag_id = par.id
                    WHERE op.id IS NOT NULL
                      AND op.da_count < 500
                )

                 SELECT fo.id
                 FROM object_paths fo
                 WHERE fo.parent_tag_id = NEW.id
                 ORDER BY fo.id
                 LIMIT 1);


        IF bad_pointer IS NOT NULL
        THEN
            SET msg := CONCAT('Cyclical Ancestry is not allowed in flow_tags, id [', bad_pointer,
                              '] is a child of id [', NEW.id, ']');
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = msg;
        END IF;
    END IF;

END