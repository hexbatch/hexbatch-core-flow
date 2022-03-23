CREATE TRIGGER trigger_before_update_flow_tag_attribute_20210920100540
    BEFORE UPDATE ON flow_tag_attributes
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210920100540_flow_tag_attributes
    DECLARE number_parents INT DEFAULT 0;
    DECLARE number_pointees INT DEFAULT 0;
    DECLARE msg VARCHAR(255);


    IF NEW.flow_tag_id THEN
        SET number_parents = number_parents + 1;
    END IF;


    IF number_parents <> 1  THEN
        SET msg := CONCAT('Tag Attributes must have one parent of tag or applied tag: ',number_parents);
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = msg;
    END IF;


    IF NEW.points_to_entry_id THEN
        SET number_pointees = number_pointees + 1;
    END IF;

    IF NEW.points_to_user_id THEN
        SET number_pointees = number_pointees + 1;
    END IF;

    IF NEW.points_to_project_id THEN
        SET number_pointees = number_pointees + 1;
    END IF;

    IF number_pointees > 1 THEN
        SET msg := CONCAT('Tag Attributes can only point to one thing, not ',number_pointees);
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = msg;
    END IF;






END