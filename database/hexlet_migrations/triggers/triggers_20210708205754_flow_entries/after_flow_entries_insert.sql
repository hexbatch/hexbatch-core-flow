CREATE TRIGGER trigger_after_insert_flow_entries_20210708205754
    AFTER INSERT ON flow_entries
    FOR EACH ROW
BEGIN

    INSERT INTO flow_things(thing_id,  thing_type, thing_guid, thing_title)
    VALUES (NEW.id,'entry',NEW.flow_entry_guid,NEW.flow_entry_title);

END