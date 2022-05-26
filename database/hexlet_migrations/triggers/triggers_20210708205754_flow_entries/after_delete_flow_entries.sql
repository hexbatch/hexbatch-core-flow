CREATE TRIGGER trigger_after_delete_flow_entries_20210708205754
    AFTER DELETE
    ON flow_entries
    FOR EACH ROW
BEGIN
    DELETE FROM  flow_things WHERE thing_type = 'entry' AND thing_guid = OLD.flow_entry_guid;
END