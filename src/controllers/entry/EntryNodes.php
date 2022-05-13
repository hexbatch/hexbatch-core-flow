<?php
namespace app\controllers\entry;

use app\hexlet\JsonHelper;
use app\models\entry\entry_node\EntryNodeDocument;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\entry\IFlowEntry;
use app\models\project\IFlowProject;
use app\models\tag\FlowTagSearch;
use app\models\tag\FlowTagSearchParams;
use Exception;
use Psr\Http\Message\ServerRequestInterface;

class EntryNodes extends EntryBase {

    /**
     * @param ServerRequestInterface $request
     * @param IFlowEntry|null $entry
     * @param IFlowProject|null $project
     * @return string|null
     * @throws Exception
     */
    public function navigate_node_commands(ServerRequestInterface $request,
                                           ?IFlowEntry $entry=null,?IFlowProject $project = null) :
    ?string
    {
        $params = $request->getQueryParams();

        if (!$entry && !empty($params['from_entry'])) {
            $entry_search_params = new FlowEntrySearchParams();
            $entry_search_params->addGuidsOrNames($params['from_entry'])->setOwningProjectGuid($project?->get_project_guid());
            $entry_array = FlowEntrySearch::search($entry_search_params);
            if (count($entry_array)) {$entry = $entry_array[0];}
        }
        if (!$entry) {return null;}

        $doc = new EntryNodeDocument($entry);

        if (JsonHelper::var_to_boolean($params['echo']??0) && $params['from_tags']??null) {
            $tag_params = new FlowTagSearchParams();
            $tag_params->addGuidsOrNames($params['from_tags']??null)->setOwningProjectGuid($project?->get_project_guid());
            $tag_search = new FlowTagSearch();
            $from_tags = $tag_search->get_tags($tag_params)->get_direct_match_tags();
            $bb_code =  $doc->insert_at(from_tags: $from_tags);
            return $this->process_bb_code($request,$bb_code);
        }
        return null;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string|null $bb_code
     * @return string|null
     */
    public function process_bb_code(ServerRequestInterface $request,?string $bb_code): ?string  {
        if ($request->getQueryParams()['bb_code']??null) {return $bb_code;}
        $html = JsonHelper::html_from_bb_code($bb_code);
        return $html;
    }
}