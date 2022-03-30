CREATE TRIGGER trigger_before_flow_standard_attribute_insert_202203128077010
    BEFORE INSERT ON flow_standard_attributes
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210920100540_flow_tag_attributes

    IF  NEW.standard_guid IS NULL THEN
        SET NEW.standard_guid = UUID_TO_BIN(UUID(),1); -- swap out the quicker time parts for faster indexing with the 1
    END IF;




END