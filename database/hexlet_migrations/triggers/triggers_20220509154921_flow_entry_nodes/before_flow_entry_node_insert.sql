CREATE TRIGGER trigger_before_flow_entry_node_insert_20220509154921
    BEFORE INSERT ON flow_entry_nodes
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20220509154921_flow_entry_nodes

    IF  NEW.entry_node_guid IS NULL THEN
        SET NEW.entry_node_guid = UUID_TO_BIN(UUID(),1); -- swap out the quicker time parts for faster indexing with the 1
    END IF;




END