<?php
namespace app\models\project\setting_models;

use app\models\base\FlowSimpleModel;
use JsonException;

class FlowProjectGitSettings extends FlowSimpleModel{


    protected ?string $git_ssh_key;
    protected ?string $git_url;
    protected ?string $git_branch;
    protected ?string $git_notes;
    protected ?string $git_web_page;
    protected ?string $git_automate_push;


    /**
     * @param null $object |null $object
     * @throws JsonException
     */
    public function __construct($object=null)
    {
        $this->git_ssh_key = null;
        $this->git_url = null;
        $this->git_branch = null;
        $this->git_notes = null;
        $this->git_web_page = null;
        $this->git_automate_push = null;
        parent::__construct($object);
    }

    /**
     * @return string|null
     */
    public function getGitSshKey(): ?string
    {
        return $this->git_ssh_key;
    }

    /**
     * @return string|null
     */
    public function getGitUrl(): ?string
    {
        return $this->git_url;
    }

    /**
     * @return string|null
     */
    public function getGitBranch(): ?string
    {
        return $this->git_branch;
    }

    /**
     * @return string|null
     */
    public function getGitNotes(): ?string
    {
        return $this->git_notes;
    }

    /**
     * @return string|null
     */
    public function getGitWebPage(): ?string
    {
        return $this->git_web_page;
    }

    /**
     * @return bool
     */
    public function isGitAutomatePush(): bool
    {
        return !!$this->git_automate_push;
    }


}