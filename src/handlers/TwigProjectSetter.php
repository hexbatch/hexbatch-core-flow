<?php
namespace app\handlers;

use app\models\entry\IFlowEntry;
use app\models\project\IFlowProject;
use app\models\tag\FlowTag;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TwigProjectSetter extends Twig {
    /**
     * Fetch rendered template
     *
     * @param  string               $template Template pathname relative to templates directory
     * @param  array<string, mixed> $data     Associative array of template variables
     *
     * @throws LoaderError  When the template cannot be found
     * @throws SyntaxError  When an error occurred during compilation
     * @throws RuntimeError When an error occurred during rendering
     *
     * @return string
     */
    public function fetch(string $template, array $data = []): string
    {
        /**
         * @var IFlowProject|null  $found_project
         */
        $found_project = null;
        if (array_key_exists('project',$data)) {
            if ($data['project'] instanceof IFlowProject) {
                $found_project = $data['project'];
            }

        } elseif (array_key_exists('tag',$data)) {
            if ($data['tag'] instanceof FlowTag) {
                $found_project = $data['tag']->getProject();
            }
        } elseif (array_key_exists('entry',$data)) {
            if ($data['entry'] instanceof IFlowEntry) {
                $found_project = $data['entry']->get_project();
            }
        }
        $data['flow_project_git_hash'] = null;
        if ($found_project && empty($data['flow_project_git_hash']??null)) {
            $data['flow_project_git_hash'] =  $found_project->get_head_commit_hash();
        }
        return parent::fetch($template,$data);
    }
}