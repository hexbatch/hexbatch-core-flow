<?php

namespace app\models\tag;

use app\helpers\Utilities;
use app\models\base\FlowBase;
use app\models\multi\GeneralSearch;
use app\models\tag\brief\BriefFlowAppliedTag;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;
use Slim\Interfaces\RouteParserInterface;

class FlowAppliedTag extends FlowBase implements JsonSerializable, IFlowAppliedTag
{

    protected ?int $id;
    protected ?int $flow_tag_id;
    protected ?int $created_at_ts;
    protected ?string $flow_applied_tag_guid;


    protected ?int $tagged_pointer_id;
    protected ?int $tagged_flow_entry_node_id;
    protected ?int $tagged_flow_entry_id;
    protected ?int $tagged_flow_user_id;
    protected ?int $tagged_flow_project_id;




    protected ?string $flow_tag_guid; 
    protected ?string $tagged_flow_entry_guid; 
    protected ?string $tagged_pointer_guid; 
    protected ?string $tagged_flow_entry_node_guid; 
    protected ?string $tagged_flow_user_guid; 
    protected ?string $tagged_flow_project_guid; 

    protected ?string $z_tagged_title;
    protected ?string $z_owned_user_guid;
    protected ?string $z_owned_user_name;
    protected ?string $z_owned_project_guid;
    protected ?string $z_tagged_url;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getParentTagId(): ?int
    {
        return $this->flow_tag_id;
    }

    /**
     * @param int|null $flow_tag_id
     */
    public function setParentTagId(?int $flow_tag_id): void
    {
        $this->flow_tag_id = $flow_tag_id;
    }

    /**
     * @return int|null
     */
    public function getXPointerId(): ?int
    {
        return $this->tagged_pointer_id;
    }

    /**
     * @param int|null $tagged_pointer_id
     */
    public function setXPointerId(?int $tagged_pointer_id): void
    {
        $this->tagged_pointer_id = $tagged_pointer_id;
    }

    /**
     * @return int|null
     */
    public function getXNodeId(): ?int
    {
        return $this->tagged_flow_entry_node_id;
    }

    /**
     * @param int|null $tagged_flow_entry_node_id
     */
    public function setXNodeId(?int $tagged_flow_entry_node_id): void
    {
        $this->tagged_flow_entry_node_id = $tagged_flow_entry_node_id;
    }

    /**
     * @return int|null
     */
    public function getXEntryId(): ?int
    {
        return $this->tagged_flow_entry_id;
    }

    /**
     * @param int|null $tagged_flow_entry_id
     */
    public function setXEntryId(?int $tagged_flow_entry_id): void
    {
        $this->tagged_flow_entry_id = $tagged_flow_entry_id;
    }

    /**
     * @return int|null
     */
    public function getXUserId(): ?int
    {
        return $this->tagged_flow_user_id;
    }

    /**
     * @param int|null $tagged_flow_user_id
     */
    public function setXUserId(?int $tagged_flow_user_id): void
    {
        $this->tagged_flow_user_id = $tagged_flow_user_id;
    }

    /**
     * @return int|null
     */
    public function getXProjectId(): ?int
    {
        return $this->tagged_flow_project_id;
    }

    /**
     * @param int|null $tagged_flow_project_id
     */
    public function setXProjectId(?int $tagged_flow_project_id): void
    {
        $this->tagged_flow_project_id = $tagged_flow_project_id;
    }

    /**
     * @return string|null
     */
    public function getZOwnerProjectGuid(): ?string
    {
        return $this->z_owned_project_guid;
    }

 

    /**
     * @return int|null
     */
    public function getCreatedAtTs(): ?int
    {
        return $this->created_at_ts;
    }

    /**
     * @param int|null $created_at_ts
     */
    public function setCreatedAtTs(?int $created_at_ts): void
    {
        $this->created_at_ts = $created_at_ts;
    }

    /**
     * @return string|null
     */
    public function getGuid(): ?string
    {
        return $this->flow_applied_tag_guid;
    }

    /**
     * @param string|null $flow_applied_tag_guid
     */
    public function setGuid(?string $flow_applied_tag_guid): void
    {
        Utilities::throw_if_not_valid_guid_format($flow_applied_tag_guid);
        $this->flow_applied_tag_guid = $flow_applied_tag_guid;
    }

    /**
     * @return string|null
     */
    public function getParentTagGuid(): ?string
    {
        return $this->flow_tag_guid;
    }

    /**
     * @param string|null $flow_tag_guid
     */
    public function setParentTagGuid(?string $flow_tag_guid): void
    {
        Utilities::throw_if_not_valid_guid_format($flow_tag_guid);
        $this->flow_tag_guid = $flow_tag_guid;
    }

    /**
     * @return string|null
     */
    public function getXEntryGuid(): ?string
    {
        return $this->tagged_flow_entry_guid;
    }

    /**
     * @param string|null $tagged_flow_entry_guid
     */
    public function setXEntryGuid(?string $tagged_flow_entry_guid): void
    {
        Utilities::throw_if_not_valid_guid_format($tagged_flow_entry_guid);
        $this->tagged_flow_entry_guid = $tagged_flow_entry_guid;
    }

    /**
     * @return string|null
     */
    public function getPointerGuid(): ?string
    {
        return $this->tagged_pointer_guid;
    }

    /**
     * @param string|null $tagged_pointer_guid
     */
    public function setXPointerGuid(?string $tagged_pointer_guid): void
    {
        Utilities::throw_if_not_valid_guid_format($tagged_pointer_guid);
        $this->tagged_pointer_guid = $tagged_pointer_guid;
    }

    /**
     * @return string|null
     */
    public function getXNodeGuid(): ?string
    {
        return $this->tagged_flow_entry_node_guid;
    }

    /**
     * @param string|null $tagged_flow_entry_node_guid
     */
    public function setXNodeGuid(?string $tagged_flow_entry_node_guid): void
    {
        Utilities::throw_if_not_valid_guid_format($tagged_flow_entry_node_guid);
        $this->tagged_flow_entry_node_guid = $tagged_flow_entry_node_guid;
    }

    /**
     * @return string|null
     */
    public function getXUserGuid(): ?string
    {
        return $this->tagged_flow_user_guid;
    }

    /**
     * @param string|null $tagged_flow_user_guid
     */
    public function setXUserGuid(?string $tagged_flow_user_guid): void
    {
        Utilities::throw_if_not_valid_guid_format($tagged_flow_user_guid);
        $this->tagged_flow_user_guid = $tagged_flow_user_guid;
    }

    /**
     * @return string|null
     */
    public function getXProjectGuid(): ?string
    {
        return $this->tagged_flow_project_guid;
    }

    /**
     * @param string|null $tagged_flow_project_guid
     */
    public function setXProjectGuid(?string $tagged_flow_project_guid): void
    {
        Utilities::throw_if_not_valid_guid_format($tagged_flow_project_guid);
        $this->tagged_flow_project_guid = $tagged_flow_project_guid;
    }

    /**
     * @return string|null
     */
    public function getZTaggedTitle(): ?string
    {
        return $this->z_tagged_title;
    }



    /**
     * @return string|null
     */
    public function getZOwnerUserGuid(): ?string
    {
        return $this->z_owned_user_guid;
    }



    /**
     * @return string|null
     */
    public function getZOwnerUserName(): ?string
    {
        return $this->z_owned_user_name;
    }



    /**
     * @return string|null
     */
    public function getZTaggedUrl(): ?string
    {
        return $this->z_tagged_url;
    }

    

    public function __construct($object=null){

        $this->id = null;
        $this->flow_tag_id = null;
        $this->flow_tag_guid = null;
        $this->tagged_flow_entry_id = null;
        $this->tagged_flow_entry_node_id = null;
        $this->tagged_pointer_id = null;
        $this->tagged_flow_user_id = null;
        $this->tagged_flow_project_id = null;
        $this->z_owned_project_guid = null;
        $this->created_at_ts = null;
        $this->flow_applied_tag_guid = null;
        $this->tagged_flow_entry_node_guid = null;
        $this->tagged_flow_entry_guid = null;
        $this->tagged_pointer_guid = null;
        $this->tagged_flow_user_guid = null;
        $this->tagged_flow_project_guid = null;
        $this->z_tagged_title = null;
        $this->z_owned_user_guid = null;
        $this->z_owned_user_name = null;
        $this->z_tagged_url = null;

        if (empty($object)) {
            return;
        }
        if ($object instanceof BriefFlowAppliedTag ) {
            $this->flow_applied_tag_guid = $object->getGuid();
            $this->flow_tag_guid = $object->getTagGuid();
            $this->created_at_ts = $object->getCreatedAtTs();
            $this->tagged_flow_entry_guid = $object->getTaggedFlowEntryGuid();
            $this->tagged_flow_user_guid = $object->getTaggedFlowUserGuid();
            $this->tagged_flow_project_guid = $object->getTaggedFlowProjectGuid();
            $this->tagged_flow_entry_node_guid = $object->getTaggedFlowEntryNodeGuid();
            $this->tagged_pointer_guid = $object->getTaggedPointerGuid();
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $careful_value = $val;
                if ($val === '') {$careful_value = null;}
                $this->$key = $careful_value;
            }
        }
    }



    
    public function jsonSerialize(): array
    {
        if ($this->get_brief_json_flag()) {
            $brief = new BriefFlowAppliedTag($this);
            return $brief->to_array();
        } else {
            return [
                "flow_applied_tag_guid" => $this->flow_applied_tag_guid,
                "flow_tag_guid" => $this->flow_tag_guid,
                "tagged_flow_entry_guid" => $this->tagged_flow_entry_guid,
                "tagged_pointer_guid" => $this->tagged_pointer_guid,
                "tagged_flow_entry_node_guid" => $this->tagged_flow_entry_node_guid,
                "tagged_flow_user_guid" => $this->tagged_flow_user_guid,
                "tagged_flow_project_guid" => $this->tagged_flow_project_guid,
                "created_at_ts" => $this->created_at_ts,
                "tagged_title" => $this->z_tagged_title,
                "flow_applied_tag_owner_user_guid" => $this->z_owned_user_guid,
                "flow_applied_tag_owner_user_name" => $this->z_owned_user_name,
                "tagged_url" => $this->z_tagged_url,
                "flow_project_guid" => $this->z_owned_project_guid,
            ];
        }

    }

    public function set_link_for_tagged(RouteParserInterface $routeParser) {

        if ($this->tagged_flow_project_guid) {
            $this->z_tagged_url = $routeParser->urlFor('single_project_home',[
                "user_name" => $this->z_owned_user_name,
                "project_name" => $this->z_tagged_title
            ]);
        } elseif ( $this->tagged_flow_user_guid) {
            $this->z_tagged_url = $routeParser->urlFor('user_page',[
                "user_name" => $this->z_tagged_title,
            ]);
        }
        elseif ( $this->tagged_flow_entry_guid) {
            $this->z_tagged_url = $routeParser->urlFor('show_entry',[
                "user_name" => $this->z_owned_user_guid,
                "project_name" => $this->z_owned_project_guid,
                "entry_name" => $this->tagged_flow_entry_guid,
            ]);
        }elseif ( $this->tagged_pointer_guid) {

            $this->z_tagged_url = $routeParser->urlFor('show_tag',
                [
                    "user_name" => $this->z_owned_user_guid,
                    "project_name" => $this->z_owned_project_guid,
                    "tag_name" => $this->tagged_pointer_guid
                ]
            );
        }
        elseif ( $this->tagged_flow_entry_node_guid) {
            //todo add an hash or navigation to this url to highlight where tag is at!
            $this->z_tagged_url = $routeParser->urlFor('show_entry',[
                "user_name" => $this->z_owned_user_guid,
                "project_name" => $this->z_owned_project_guid,
                "entry_name" => $this->tagged_flow_entry_guid,
            ]);
        }
        else {
            static::get_logger()->warning("Not able to genenerate a link for applied");
        }

    }

    /**
     * @param int[] $tag_id_array
     * @param string|null $match_only_applied_guid , if given, will only return the applied that fits the tag_id and matches guid
     * @param int|null $match_only_applied_id , if given, will only return the applied that fits the tag_id and matches the id
     * @return array<string,IFlowAppliedTag[]>
     */
    public static function get_applied_tags(array $tag_id_array,?string $match_only_applied_guid=null,
                                            ?int $match_only_applied_id=null) : array {

        if (empty($tag_id_array)) { return [];}
        $tag_id_ints = [];
        foreach ($tag_id_array as $raw_int) {
            $maybe_int = intval($raw_int);
            if ($maybe_int) {$tag_id_ints[] = $maybe_int;}
        }

        if (empty($tag_id_ints)) {
            throw new InvalidArgumentException("Need at last one integer in the tag id array to get applied tags");
        }
        $comma_delimited_tag_ids = implode(",",$tag_id_ints);
        $db = static::get_connection();

        $I = function($v) { return $v; };

        $args = [];
        $where_match_guid = 16;
        if ($match_only_applied_guid) {
            $where_match_guid = " app.flow_applied_tag_guid = UNHEX(?) ";
            $args[] = $match_only_applied_guid;
        }

        $where_match_id = 32;
        if (intval($match_only_applied_id)) {
            $where_match_id = " app.id = ? ";
            $args[] = $match_only_applied_id;
        }

        $sql = "
            SELECT
               GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
               GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,    
               GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
               GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list, 
               GROUP_CONCAT( HEX(fp_owner.flow_project_guid) order by app.id) as owning_project_guid_list,   
               GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,  
               NULL as owning_entry_guid_list,
               GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,     
               GROUP_CONCAT( app.id order by app.id) as applied_id_list,
               app.tagged_flow_project_id as taggee_id,
               HEX(fp.flow_project_guid) as taggee_guid,
               fp.flow_project_title as tagged_title,
               '{$I(GeneralSearch::TYPE_PROJECT)}' as taggie_type
            FROM flow_applied_tags app
                INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                INNER JOIN flow_projects fp on app.tagged_flow_project_id = fp.id
                INNER JOIN flow_projects fp_owner on retag.flow_project_id = fp_owner.id
                INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND 
                  app.tagged_flow_project_id IS NOT NULL AND
                  $where_match_guid AND $where_match_id
            
            GROUP BY app.tagged_flow_project_id
        UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,
                GROUP_CONCAT( HEX(fp.flow_project_guid) order by app.id) as owning_project_guid_list,
                GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,  
                NULL as owning_entry_guid_list,
                GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,    
                GROUP_CONCAT( app.id order by app.id) as applied_id_list,   
                app.tagged_flow_user_id as taggee_id,
                HEX(fu.flow_user_guid) as taggee_guid,
                fu.flow_user_name as tagged_title,
                '{$I(GeneralSearch::TYPE_USER)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_users fu on app.tagged_flow_user_id = fu.id
                     INNER JOIN flow_projects fp on retag.flow_project_id = fp.id
                     INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_user_id IS NOT NULL
            GROUP BY app.tagged_flow_user_id
        UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,   
                GROUP_CONCAT( HEX(fp.flow_project_guid) order by app.id) as owning_project_guid_list,   
                GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,  
                NULL as owning_entry_guid_list,
                GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,   
                GROUP_CONCAT( app.id order by app.id) as applied_id_list, 
                app.tagged_flow_entry_id as taggee_id,
                HEX(fe.flow_entry_guid) as taggee_guid,      
                fe.flow_entry_title as tagged_title,   
                '{$I(GeneralSearch::TYPE_ENTRY)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_entries fe on app.tagged_flow_entry_id = fe.id
                     INNER JOIN flow_projects fp on fe.flow_project_id = fp.id
                     INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_entry_id IS NOT NULL
            GROUP BY tagged_flow_entry_id
            UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,   
                GROUP_CONCAT( HEX(fp.flow_project_guid) order by app.id) as owning_project_guid_list,   
                GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,  
                NULL as owning_entry_guid_list,
                GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,   
                GROUP_CONCAT( app.id order by app.id) as applied_id_list, 
                app.tagged_pointer_id as taggee_id,
                HEX(pointee.flow_tag_guid) as taggee_guid,      
                pointee.flow_tag_name as tagged_title,   
                '{$I(GeneralSearch::TYPE_TAG_POINTER)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_tags pointee on app.tagged_pointer_id = pointee.id
                     INNER JOIN flow_projects fp on pointee.flow_project_id = fp.id
                     INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_entry_id IS NOT NULL
            GROUP BY tagged_pointer_id
        UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,   
                GROUP_CONCAT( HEX(fp.flow_project_guid) order by app.id) as owning_project_guid_list,   
                GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,
                GROUP_CONCAT( HEX(fe.flow_entry_guid) order by app.id) as owning_entry_guid_list,
                GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,   
                GROUP_CONCAT( app.id order by app.id) as applied_id_list, 
                app.tagged_flow_entry_node_id as taggee_id,
                HEX(ne.entry_node_guid) as taggee_guid,      
                ne.bb_tag_name as tagged_title,   
                '{$I(GeneralSearch::TYPE_ENTRY_NODE)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_entry_nodes ne on app.tagged_flow_entry_node_id = ne.id
                     INNER JOIN flow_entries fe on ne.flow_entry_id = fe.id
                     INNER JOIN flow_projects fp on fe.flow_project_id = fp.id
                     INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_entry_node_id IS NOT NULL
            GROUP BY tagged_flow_entry_node_id
        ";

        $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
        $ret = [];
        foreach ($res as $row) {
            $tag_guid_array = explode(",",$row->tag_guid_list);
            $tag_id_array = explode(",",$row->tag_id_list);
            $created_at_array = explode(",",$row->tagged_at_ts);
            $applied_guid_array = explode(",",$row->applied_guid_list);
            $applied_id_array = explode(",",$row->applied_id_list);
            $owning_project_guid_array = explode(",",$row->owning_project_guid_list);
            $owning_user_name_list_array = explode(",",$row->owning_user_name_list);
            $owning_user_guid_list_array = explode(",",$row->owning_user_guid_list);
            $owning_entry_guid_list_array = $row->owning_entry_guid_list? explode(",",$row->owning_entry_guid_list) : [];
            if (count($tag_guid_array) !== count($created_at_array) ||
                count($tag_guid_array) !== count($tag_id_array) ||
                count($applied_id_array) !== count($tag_id_array) ||
                count($tag_guid_array) !== count($applied_guid_array) ||
                count($tag_guid_array) !== count($owning_user_guid_list_array) ||
                count($tag_guid_array) !== count($owning_user_name_list_array) ||
                count($tag_guid_array) !== count($owning_project_guid_array)
            ) {
                throw new RuntimeException(
                    "[get_applied_tags] guid, created_at , id, applied_id list does not have same numbers, check nulls");
            }
            for($i = 0; $i< count($applied_guid_array); $i++) {
                $node = new FlowAppliedTag();
                $node->created_at_ts = (int)$created_at_array[$i];
                $node->flow_tag_guid = $tag_guid_array[$i];
                $node->flow_tag_id = $tag_id_array[$i];
                $node->flow_applied_tag_guid = $applied_guid_array[$i];
                $node->id = $applied_id_array[$i];
                $node->z_tagged_title = $row->tagged_title;
                $node->z_owned_user_guid = $owning_user_guid_list_array[$i];
                $node->z_owned_project_guid = $owning_project_guid_array[$i];
                $node->z_owned_user_name = $owning_user_name_list_array[$i];

                switch ($row->taggie_type) {
                    case GeneralSearch::TYPE_ENTRY: {
                        $node->tagged_flow_entry_guid = $row->taggee_guid;
                        $node->tagged_flow_entry_id = $row->taggee_id;
                        break;
                    }

                    case GeneralSearch::TYPE_TAG_POINTER: {
                        $node->tagged_pointer_guid = $row->taggee_guid;
                        $node->tagged_pointer_id = $row->taggee_id;
                        break;
                    }

                    case GeneralSearch::TYPE_ENTRY_NODE: {
                        $node->tagged_flow_entry_node_guid = $row->taggee_guid;
                        $node->tagged_flow_entry_node_id = $row->taggee_id;
                        $node->tagged_flow_entry_guid = $owning_entry_guid_list_array[0]?? null;
                        if (!$node->tagged_flow_entry_guid) {
                            throw new RuntimeException(
                                "[get_applied_tags] Could not get owning entry guid for node ".$node->tagged_flow_entry_node_id);
                        }
                        break;
                    }
                    case GeneralSearch::TYPE_USER: {
                        $node->tagged_flow_user_guid = $row->taggee_guid;
                        $node->tagged_flow_user_id = $row->taggee_id;
                        break;
                    }
                    case GeneralSearch::TYPE_PROJECT:  {
                        $node->tagged_flow_project_guid = $row->taggee_guid;
                        $node->tagged_flow_project_id = $row->taggee_id;
                        break;
                    }


                    default: {
                        throw new RuntimeException(
                            "[get_applied_tags] got unknown taggie type of ".$row->taggie_type );
                    }
                }

                if (!array_key_exists($node->flow_tag_guid,$ret)) {
                    $ret[$node->flow_tag_guid] = [] ;
                }
                $ret[$node->flow_tag_guid][] = $node ;
            }
        }

        return $ret;
    }

    /**
     * @throws Exception
     */
    public function save() :void {
        $db = static::get_connection();

        if(  !$this->flow_tag_id) {
            throw new InvalidArgumentException("When saving an applied, need a tag_id");
        }


        if (!$this->tagged_flow_entry_id && $this->tagged_flow_entry_guid) {
            $this->tagged_flow_entry_id = $db->cell(
                "SELECT id  FROM flow_entries WHERE flow_entry_guid = UNHEX(?)",
                $this->tagged_flow_entry_guid);
        }

        if (!$this->tagged_pointer_id && $this->tagged_pointer_guid) {
            $this->tagged_pointer_id = $db->cell(
                "SELECT id  FROM flow_tags WHERE flow_tag_guid = UNHEX(?)",
                $this->tagged_pointer_guid);
        }

        if (!$this->tagged_flow_entry_node_id && $this->tagged_flow_entry_node_guid) {
            $this->tagged_flow_entry_node_id = $db->cell(
                "SELECT id  FROM flow_entry_nodes WHERE entry_node_guid = UNHEX(?)",
                $this->tagged_flow_entry_node_guid);
        }

        if (!$this->tagged_flow_project_id && $this->tagged_flow_project_guid) {
            $this->tagged_flow_project_id = $db->cell(
                "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                $this->tagged_flow_project_guid);
        }

        if (!$this->tagged_flow_user_id && $this->tagged_flow_user_guid) {
            $this->tagged_flow_user_id = $db->cell(
                "SELECT id  FROM flow_users WHERE flow_user_guid = UNHEX(?)",
                $this->tagged_flow_user_guid);
        }

        if (!($this->tagged_flow_user_id || $this->tagged_flow_project_id ||
                $this->tagged_flow_entry_id || $this->tagged_flow_entry_node_id || $this->tagged_pointer_id)
        ) {
            throw new InvalidArgumentException("When saving an applied, it needs to be tagging something" );
        }

        $saving_info = [
            'flow_tag_id' => $this->flow_tag_id ,
            'tagged_flow_entry_id' => $this->tagged_flow_entry_id ,
            'tagged_pointer_id' => $this->tagged_pointer_id ,
            'tagged_flow_entry_node_id' => $this->tagged_flow_entry_node_id ,
            'tagged_flow_project_id' => $this->tagged_flow_project_id ,
            'tagged_flow_user_id' => $this->tagged_flow_user_id
        ];

        if ($this->id) {

            $db->update('flow_applied_tags',$saving_info,[
                'id' => $this->id
            ]);

        }
        elseif ($this->flow_applied_tag_guid) {
            $insert_sql = "
                    INSERT INTO flow_applied_tags(flow_tag_id, tagged_flow_entry_id,tagged_pointer_id,
                                                  tagged_flow_entry_node_id, tagged_flow_user_id,
                                                  tagged_flow_project_id, created_at_ts, flow_applied_tag_guid)  
                    VALUES (?,?,?,?,?,?,?,UNHEX(?))  
                    ON DUPLICATE KEY UPDATE 
                                            flow_tag_id             = VALUES(flow_tag_id) ,       
                                            tagged_flow_entry_id    = VALUES(tagged_flow_entry_id) ,       
                                            tagged_pointer_id    = VALUES(tagged_pointer_id) ,       
                                            tagged_flow_entry_node_id    = VALUES(tagged_flow_entry_node_id) ,       
                                            tagged_flow_user_id     = VALUES(tagged_flow_user_id) ,       
                                            tagged_flow_project_id  = VALUES(tagged_flow_project_id)        
                ";
            $insert_params = [
                $this->flow_tag_id,
                $this->tagged_flow_entry_id,
                $this->tagged_pointer_id,
                $this->tagged_flow_entry_node_id,
                $this->tagged_flow_user_id,
                $this->tagged_flow_project_id,
                $this->created_at_ts,
                $this->flow_applied_tag_guid
            ];
            $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
            $this->flow_tag_id = $db->lastInsertId();
        }
        else {
            $db->insert('flow_applied_tags',$saving_info);
            $this->id = $db->lastInsertId();
        }

        if (!$this->flow_applied_tag_guid) {
            $this->flow_applied_tag_guid = $db->cell(
                "SELECT HEX(flow_applied_tag_guid) as guid FROM flow_applied_tags WHERE id = ?",
                $this->id);

            if (!$this->flow_applied_tag_guid) {
                throw new RuntimeException("Could not get applied guid using id of ". $this->id);
            }
        }
    }//end function save


    public function delete_applied() : void {
        $db = static::get_connection();
        if ($this->id) {
            $db->delete('flow_applied_tags',['id'=>$this->id]);
        } else if($this->flow_applied_tag_guid) {
            $sql = "DELETE FROM flow_applied_tags WHERE flow_applied_tag_guid = UNHEX(?)";
            $params = [$this->flow_applied_tag_guid];
            $db->safeQuery($sql, $params, PDO::FETCH_BOTH, true);
        } else {
            throw new LogicException("Cannot delete flow_applied_tags without an id or guid");
        }


    } //end function delete


    public static function reconstitute($args,FlowTag $parent_tag) : IFlowAppliedTag {

        if (is_numeric($args)) { //expected to be applied id
            $ret_array = static::get_applied_tags([$parent_tag->getID()],null,$args);
            if (empty($ret_array) || empty($ret_array[$parent_tag->getGuid()])) {
                throw new InvalidArgumentException("cannot find applied using the id of $args");
            }
            $ret = $ret_array[$parent_tag->getGuid()][0]; //only one
        } elseif (is_string($args)) { //args expected to be applied guid
            $ret_array = static::get_applied_tags([$parent_tag->getID()],$args);
            if (empty($ret_array) || empty($ret_array[$parent_tag->getGuid()])) {
                throw new InvalidArgumentException("cannot find applied using the guid of $args");
            }
            $ret = $ret_array[$parent_tag->getGuid()][0]; //only one
        } elseif (is_array($args) || is_object($args)) {
            $node = new FlowAppliedTag($args);
            if ($node->flow_applied_tag_guid) {
                $ret_array = static::get_applied_tags([$parent_tag->getID()],$node->flow_applied_tag_guid);
                if (empty($ret_array) || empty($ret_array[$parent_tag->getGuid()])) {
                    throw new InvalidArgumentException("cannot find applied using the guid of $node->flow_applied_tag_guid");
                }
                $ret = $ret_array[$parent_tag->getGuid()][0];
                $ret->tagged_flow_entry_id = $node->tagged_flow_entry_id;
                $ret->tagged_flow_entry_guid = $node->tagged_flow_entry_guid;

                $ret->tagged_pointer_id = $node->tagged_pointer_id;
                $ret->tagged_pointer_guid = $node->tagged_pointer_guid;

                $ret->tagged_flow_entry_node_id = $node->tagged_flow_entry_node_id;
                $ret->tagged_flow_entry_node_guid = $node->tagged_flow_entry_node_guid;

                $ret->tagged_flow_project_id = $node->tagged_flow_project_id;
                $ret->tagged_flow_project_guid = $node->tagged_flow_project_guid;

                $ret->tagged_flow_user_id = $node->tagged_flow_user_id;
                $ret->tagged_flow_user_guid = $node->tagged_flow_user_guid;
            } else {
                $ret = $node;
            }

        } else {
            throw new InvalidArgumentException("Could not figure out Applied from the data of $args");
        }

        return $ret;
    }

    /**
     * @param string[] $guid_list
     * @return bool
     */
    public function has_at_least_one_of_these_tagged_guid(array $guid_list) :bool{
        foreach ($guid_list as $guid) {
            if ($this->tagged_flow_user_guid === $guid) {return true;}
            if ($this->tagged_flow_project_guid === $guid) {return true;}
            if ($this->tagged_flow_entry_guid === $guid) {return true;}
            if ($this->tagged_flow_entry_node_guid === $guid) {return true;}
            if ($this->tagged_pointer_guid === $guid) {return true;}
        }
        return false;
    }


    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids() : array
    {
        $ret = [];
        if (empty($this->flow_tag_id) && $this->flow_tag_guid) { $ret[] = $this->flow_tag_guid;}
        if (empty($this->tagged_flow_entry_id) && $this->tagged_flow_entry_guid) { $ret[] = $this->tagged_flow_entry_guid;}
        if (empty($this->tagged_pointer_id) && $this->tagged_pointer_guid) { $ret[] = $this->tagged_pointer_guid;}
        if (empty($this->tagged_flow_entry_node_id) && $this->tagged_flow_entry_node_guid) { $ret[] = $this->tagged_flow_entry_node_guid;}
        if (empty($this->tagged_flow_user_id) && $this->tagged_flow_user_guid) { $ret[] = $this->tagged_flow_user_guid;}
        if (empty($this->tagged_flow_project_id) && $this->tagged_flow_project_guid) { $ret[] = $this->tagged_flow_project_guid;}

        return $ret;
    }

    /**
     * @@param  array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids)
    {
        if (empty($this->flow_tag_id) && $this->flow_tag_guid) {
            $this->flow_tag_id = $guid_map_to_ids[$this->flow_tag_guid] ?? null;}
        if (empty($this->tagged_flow_entry_id) && $this->tagged_flow_entry_guid) {
            $this->tagged_flow_entry_id = $guid_map_to_ids[$this->tagged_flow_entry_guid] ?? null;}
        if (empty($this->tagged_pointer_id) && $this->tagged_pointer_guid) {
            $this->tagged_pointer_id = $guid_map_to_ids[$this->tagged_pointer_guid] ?? null;}
        if (empty($this->tagged_flow_entry_node_id) && $this->tagged_flow_entry_node_guid) {
            $this->tagged_flow_entry_node_id = $guid_map_to_ids[$this->tagged_flow_entry_node_guid] ?? null;}
        if (empty($this->tagged_flow_user_id) && $this->tagged_flow_user_guid) {
            $this->tagged_flow_user_id = $guid_map_to_ids[$this->tagged_flow_user_guid] ?? null;}
        if (empty($this->tagged_flow_project_id) && $this->tagged_flow_project_guid) {
            $this->tagged_flow_project_id= $guid_map_to_ids[$this->tagged_flow_project_guid] ?? null;}
    }


}