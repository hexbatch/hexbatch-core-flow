CREATE TRIGGER trigger_before_update_flow_applied_tags_20210709000009
    BEFORE UPDATE ON flow_applied_tags
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210709000009_flow_applied_tags
    DECLARE number_fields INT DEFAULT 0;
    DECLARE msg VARCHAR(255);

    IF NEW.tagged_flow_entry_id THEN
        SET number_fields = number_fields + 1;
    END IF;

    IF NEW.tagged_flow_user_id THEN
        SET number_fields = number_fields + 1;
    END IF;

    IF NEW.tagged_flow_project_id THEN
        SET number_fields = number_fields + 1;
    END IF;

    IF number_fields > 1 THEN
        SET msg := CONCAT('Tags can only point to one thing, not ',number_fields);
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = msg;
    END IF;




END