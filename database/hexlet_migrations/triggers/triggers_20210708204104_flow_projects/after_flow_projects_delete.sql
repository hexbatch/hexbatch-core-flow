CREATE TRIGGER trigger_after_flow_projects_delete_20210708204104
    AFTER DELETE ON flow_projects
    FOR EACH ROW
BEGIN
    DELETE FROM  flow_things WHERE thing_type = 'project' AND thing_guid = OLD.flow_project_guid;
    DELETE FROM  flow_things WHERE owning_project_guid =  OLD.flow_project_guid;
END