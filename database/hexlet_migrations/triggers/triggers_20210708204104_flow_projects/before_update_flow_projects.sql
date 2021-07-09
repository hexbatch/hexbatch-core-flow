CREATE TRIGGER trigger_before_update_flow_projects_20210708204104
    BEFORE UPDATE ON flow_projects
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708204104_flow_projects

    DECLARE bad_pointer INT DEFAULT NULL;
    DECLARE msg VARCHAR(255);

    -- don't allow new parent to be something that points to this (no recursive ancestor chains)
    -- no chains where we have the ancestry of this including something that points back

    if (NEW.parent_flow_project_id IS NOT NULL)
    THEN
        SET bad_pointer :=
                (WITH RECURSIVE object_paths AS (
                    SELECT id, parent_flow_project_id, 0 as da_count
                    FROM flow_projects
                    where id = NEW.parent_flow_project_id
                    UNION ALL
                    SELECT par.id, par.parent_flow_project_id, op.da_count + 1
                    FROM flow_projects par
                             INNER JOIN object_paths op ON op.parent_flow_project_id = par.id
                    WHERE op.id IS NOT NULL
                      AND op.da_count < 500
                )

                 SELECT fo.id
                 FROM object_paths fo
                 WHERE fo.parent_flow_project_id = NEW.id
                 ORDER BY fo.id
                 LIMIT 1);


        IF bad_pointer IS NOT NULL
        THEN
            SET msg := CONCAT('Cyclical Ancestry is not allowed in flow_projects, id [', bad_pointer,
                              '] is a child of id [', NEW.id, ']');
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = msg;
        END IF;
    END IF;
END