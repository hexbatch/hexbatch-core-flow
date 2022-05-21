<?php
namespace app\models\project\levels;

use app\helpers\Utilities;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\project\IFlowProject;
use Exception;
use InvalidArgumentException;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use RuntimeException;


abstract class FlowProjectDataLevel extends FlowBase implements JsonSerializable, IFlowProject {

    protected ?int $id;
    protected ?int $created_at_ts;
    protected ?int $is_public;

    protected ?string $flow_project_guid;


    protected ?int $admin_flow_user_id;

    protected ?string $flow_project_type;
    protected ?string $flow_project_title;
    protected ?string $flow_project_blurb;
    protected ?string $flow_project_readme;
    protected ?string $flow_project_readme_bb_code;

    public function get_readme_bb_code() : ?string {return $this->flow_project_readme_bb_code;}
    public function get_project_blurb() : ?string {return $this->flow_project_blurb;}
    public function get_project_title() : ?string {return $this->flow_project_title;}
    public function get_project_guid() : ?string {return $this->flow_project_guid;}
    public function is_public() : ?bool {return !!$this->is_public;}
    public function get_created_ts() : ?int {return $this->created_at_ts;}
    public function get_id() : ?int {return $this->id;}


    public function set_project_blurb(?string $blurb) : void { $this->flow_project_blurb = $blurb;}
    public function set_project_title(?string $title) : void { $this->flow_project_title = $title;}
    public function set_project_type(?string $type) : void { $this->flow_project_type = $type;}
    public function set_public(bool $type) : void { $this->is_public = $type;}

    public function set_admin_user_id(?int $user_id) : void {
        $this->admin_flow_user_id = $user_id;
    }



    protected ?string $old_flow_project_title;
    protected ?string $old_flow_project_blurb;
    protected ?string $old_flow_project_readme_bb_code;

    protected bool $b_new_project;




    /**
     * @param object|array|null $object
     * @throws Exception
     */
    public function __construct(object|array $object=null){
        $this->b_new_project = false;

        if (empty($object)) {
            $this->admin_flow_user_id = null;
            $this->flow_project_blurb = null;
            $this->flow_project_title = null;
            $this->flow_project_readme = null;
            $this->flow_project_readme_bb_code = null;

            $this->flow_project_guid = null;
            $this->flow_project_type = null;
            $this->created_at_ts = null;
            $this->is_public = null;

            $this->old_flow_project_blurb = null;
            $this->old_flow_project_readme_bb_code = null;
            $this->old_flow_project_title = null;

            return;
        }
        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

        $this->old_flow_project_blurb = $this->flow_project_blurb;
        $this->old_flow_project_readme_bb_code = $this->flow_project_readme_bb_code;
        $this->old_flow_project_title = $this->flow_project_title;
        if (!$this->id) {
            $this->b_new_project = true;
        }
    }


    #[ArrayShape(['flow_project_title' => "null|string", 'flow_project_guid' => "null|string",
        'created_at_ts' => "int|null", 'flow_project_blurb' => "null|string"])]
    public function jsonSerialize() : array
    {
        return [
            'flow_project_title'=>$this->flow_project_title,
            'flow_project_guid'=>$this->flow_project_guid,
            'created_at_ts'=>$this->created_at_ts,
            'flow_project_blurb'=>$this->flow_project_blurb,

        ];
    }


    /**
     * @param bool $b_do_transaction default true
     * @param bool $b_commit_project
     * @return void
     * @throws Exception
     */
    public function save(bool $b_do_transaction = true,bool $b_commit_project = true) : void {
        WillFunctions::will_do_nothing($b_commit_project);
        $db = null;
        try {
            if (empty($this->flow_project_title)) {
                throw new InvalidArgumentException("Project Title cannot be empty");
            }
            if (mb_strlen($this->flow_project_title) > static::MAX_SIZE_TITLE) {
                throw new InvalidArgumentException("Project Title cannot be more than ".static::MAX_SIZE_TITLE." characters");
            }

            if (mb_strlen($this->flow_project_blurb) > static::MAX_SIZE_BLURB) {
                throw new InvalidArgumentException("Project Blurb cannot be more than ".static::MAX_SIZE_BLURB." characters");
            }
            if ($this->flow_project_blurb) {
                $this->flow_project_blurb = htmlspecialchars($this->flow_project_blurb,
                    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,'UTF-8',false);
            }

            $b_match = static::check_valid_title($this->flow_project_title);
            if (!$b_match) {
                throw new InvalidArgumentException(
                    "Project Title needs to be all alpha numeric or dash only. ".
                    "First character cannot be a number. Title Cannot be less than 3 or greater than 40. ".
                    " Title cannot be a hex number greater than 25 and cannot be a decimal number");
            }



            $db = static::get_connection();
            if ($b_do_transaction && !$db->inTransaction()) {
                $db->beginTransaction();
            }

            $this->flow_project_title = Utilities::to_utf8($this->flow_project_title);
            $this->flow_project_blurb = Utilities::to_utf8($this->flow_project_blurb);

            if ($this->flow_project_guid) {
                $db->update('flow_projects',[
                    'admin_flow_user_id' => $this->admin_flow_user_id,
                    'is_public' => $this->is_public,

                    'flow_project_type' => $this->flow_project_type,
                    'flow_project_title' => $this->flow_project_title,
                    'flow_project_blurb' => $this->flow_project_blurb,
                    'flow_project_readme' => $this->flow_project_readme,
                    'flow_project_readme_bb_code' => $this->flow_project_readme_bb_code,
                ],[
                    'id' => $this->id
                ]);
                $this->b_new_project = false;


            } else {
                $db->insert('flow_projects',[
                    'admin_flow_user_id' => $this->admin_flow_user_id,
                    'is_public' => $this->is_public,

                    'flow_project_type' => $this->flow_project_type,
                    'flow_project_title' => $this->flow_project_title,
                    'flow_project_blurb' => $this->flow_project_blurb,
                    'flow_project_readme' => $this->flow_project_readme,
                    'flow_project_readme_bb_code' => $this->flow_project_readme_bb_code,
                ]);
                $this->id = $db->lastInsertId();
                $this->b_new_project = true;
            }

            if (!$this->flow_project_guid) {
                $this->flow_project_guid = $db->cell("SELECT HEX(flow_project_guid) as flow_project_guid FROM flow_projects WHERE id = ?",$this->id);
                if (!$this->flow_project_guid) {
                    throw new RuntimeException("Could not get project guid using id of ". $this->id);
                }
            }

            //update the old to have the new, for next save
            $this->old_flow_project_readme_bb_code = $this->flow_project_readme_bb_code;
            $this->old_flow_project_blurb = $this->flow_project_blurb;
            $this->old_flow_project_title = $this->flow_project_title;


            if ($b_do_transaction && $db->inTransaction()) {
                $db->commit();
            }


        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            static::get_logger()->alert("FlowProjectData  cannot save ",['exception'=>$e]);
            throw $e;
        }

    }

    /**
     * @param bool $b_do_transaction
     * @return void
     * @throws Exception
     */
    public function destroy_project(bool $b_do_transaction = true): void {
        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) { $db->beginTransaction();}
            $db->delete(IFlowProject::TABLE_NAME,[
                'id' => $this->id
            ]);
            $this->id = null;
            $this->flow_project_guid = null;
            $this->admin_flow_user_id = null;

            if ($b_do_transaction) {
                if ($db->inTransaction()) {
                    $db->commit();
                }
            }
        } catch (Exception $e) {
            if ($b_do_transaction) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
            throw $e;
        }
    }
}