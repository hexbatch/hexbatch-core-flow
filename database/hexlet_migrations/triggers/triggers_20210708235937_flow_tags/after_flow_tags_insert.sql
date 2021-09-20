CREATE TRIGGER trigger_after_flow_tags_insert_20210708235937
    AFTER INSERT ON flow_tags
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708235937_flow_tags

    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title)
    VALUES (NEW.id,'tag',NEW.flow_tag_guid,NEW.flow_tag_name);
END