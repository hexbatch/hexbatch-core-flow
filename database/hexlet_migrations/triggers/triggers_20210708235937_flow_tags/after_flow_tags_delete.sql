CREATE TRIGGER trigger_after_flow_tags_delete_20210708235937
    AFTER DELETE ON flow_tags
    FOR EACH ROW
BEGIN
    -- trigger for triggers_20210708235937_flow_tags

    DELETE FROM  flow_things WHERE thing_type = 'tag' AND thing_id = OLD.id;
    DELETE FROM  flow_things WHERE thing_type = 'tag' AND thing_guid = OLD.flow_tag_guid;
END