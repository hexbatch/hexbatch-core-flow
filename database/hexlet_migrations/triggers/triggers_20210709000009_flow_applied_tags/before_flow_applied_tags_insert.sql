CREATE TRIGGER trigger_before_insert_flow_applied_tags_20210709000009
    BEFORE INSERT ON flow_applied_tags
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210709000009_flow_applied_tags
    DECLARE number_fields INT DEFAULT 0;
    DECLARE msg VARCHAR(255);

    IF  NEW.flow_applied_tag_guid IS NULL THEN
        SET NEW.flow_applied_tag_guid = UUID_TO_BIN(UUID(),1); -- swap out the quicker time parts for faster indexing with the 1
    END IF;

    IF  NEW.created_at_ts IS NULL THEN
        SET NEW.created_at_ts = UNIX_TIMESTAMP(NOW());
    END IF;

    IF NEW.tagged_flow_entry_id THEN
        SET number_fields = number_fields + 1;
    END IF;

    IF NEW.tagged_flow_user_id THEN
        SET number_fields = number_fields + 1;
    END IF;

    IF NEW.tagged_flow_project_id THEN
        SET number_fields = number_fields + 1;
    END IF;

    IF NEW.tagged_flow_entry_node_id THEN
        SET number_fields = number_fields + 1;
    END IF;

    IF NEW.tagged_pointer_id THEN
        SET number_fields = number_fields + 1;
    END IF;

    IF number_fields > 1 THEN
        SET msg := CONCAT('Tags can only point to one thing, not ',number_fields);
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = msg;
    END IF;




END