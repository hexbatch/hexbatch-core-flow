CREATE TRIGGER after_node_delete_20220509154921
    AFTER DELETE
    ON flow_entry_nodes
    FOR EACH ROW
BEGIN
    DELETE FROM  flow_things WHERE thing_type  in ('node','node_tag') AND thing_guid = OLD.entry_node_guid;
END