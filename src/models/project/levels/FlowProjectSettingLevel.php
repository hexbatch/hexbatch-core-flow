<?php
namespace app\models\project\levels;

use app\helpers\ProjectHelper;
use app\models\project\FlowProjectUser;
use app\models\project\IFlowProject;
use app\models\project\setting_models\FlowProjectGitSettings;
use app\models\standard\IFlowTagStandardAttribute;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagSearch;
use app\models\tag\FlowTagSearchParams;
use Exception;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

abstract class FlowProjectSettingLevel extends FlowProjectTagLevel {


    protected array $setting_cache = [];

    /**
     * @param null|array|object $object
     * @throws Exception
     */
    public function __construct($object=null)
    {
        parent::__construct($object);
        $this->setting_cache = [];
    }

    /**
     * Gets the tag that points to the tag with the setting data
     * @param string $setting_name
     * @return FlowTag|null
     * @throws Exception
     */
    public function get_setting_holder_tag(string $setting_name) : ?FlowTag {
        if (!isset(IFlowProject::STANDARD_SETTINGS[$setting_name])) {
            throw new InvalidArgumentException("[get_setting_tag] Unknown project setting: $setting_name");
        }
        $setting_node = IFlowProject::STANDARD_SETTINGS[$setting_name];
        if (!isset($setting_node['tag_name'])) {
            throw new LogicException("[get_setting_tag] badly formed setting: $setting_name");
        }
        $tag_name_for_setting = $setting_node['tag_name'];

        $pointee_tag = $this->get_tag_by_name($tag_name_for_setting);
        return $pointee_tag;
    }

    /**
     * Gets the tag that holds the setting data. This may be from another project
     * @param string $setting_name
     * @return FlowTag|null
     * @throws Exception
     */
    public function get_setting_tag(string $setting_name) : ?FlowTag {

        $pointee_tag = $this->get_setting_holder_tag($setting_name);
        $pointee_attribute = $pointee_tag->get_or_create_attribute($setting_name);
        $setting_tag_guid = $pointee_attribute->getPointsToFlowTagGuid();


        if (!$setting_tag_guid) { return null;  }

        $tag_params = new FlowTagSearchParams();
        $tag_params->tag_guids[] = $setting_tag_guid;
        $pointee_tag_array = FlowTagSearch::get_tags($tag_params);

        if (empty($pointee_tag_array)) {
            throw new LogicException("[get_setting_tag] pointee for $setting_name holds invalid pointer: $setting_tag_guid");
        }
        $pointee_tag = $pointee_tag_array[0];

        if ($pointee_tag->flow_project_guid !== $this->flow_project_guid) {
            //check for read permission
            $other_project = ProjectHelper::get_project_helper()->get_project_with_permissions(
                null,$pointee_tag->flow_project_admin_user_guid,$pointee_tag->flow_project_guid,
                FlowProjectUser::PERMISSION_COLUMN_READ
            );
            if (!$other_project) {
                throw new RuntimeException("[get_setting_tag] No permissions to read tag $setting_tag_guid for setting $setting_name");
            }
        }
        return $pointee_tag;
    }

    /**
     * @param string $setting_name
     * @return IFlowTagStandardAttribute|null
     * @throws Exception
     */
    protected function get_setting_value(string $setting_name) : ?IFlowTagStandardAttribute {

        $tag = $this->get_setting_tag($setting_name);
        if (!$tag) {return null;}

        $setting_node = IFlowProject::STANDARD_SETTINGS[$setting_name];
        if (!isset($setting_node['standard_attribute_name'])) {
            throw new LogicException("[get_setting_tag] badly formed setting (standard name): $setting_name");
        }
        $standard_name = $setting_node['standard_attribute_name'];

        $attributes =  $tag->getStandardAttributes();
        foreach ($attributes as $standard) {
            if ($standard->getStandardName() === $standard_name) {
                return $standard;
            }
        }
        return null;
    }


    /**
     * @param string $setting_name
     * @param bool $was_cached
     * @return FlowProjectGitSettings
     * @throws Exception
     */
    protected function findGitSetting(string $setting_name, ?bool &$was_cached) : FlowProjectGitSettings {
        if (array_key_exists($setting_name,$this->setting_cache)) {
            $was_cached = true;
            return $this->setting_cache[$setting_name];
        }
        $was_cached = false;
        $maybe_standard = $this->get_setting_value($setting_name);
        if (!$maybe_standard) {
            $node = new FlowProjectGitSettings();
            $this->setting_cache[$setting_name] = $node;
            return $node;
        }
        $da_truthful_data = $maybe_standard->getStandardValue();
        $ret =  new FlowProjectGitSettings($da_truthful_data);
        $this->setting_cache[$setting_name] = $ret;
        return $ret;
    }

}