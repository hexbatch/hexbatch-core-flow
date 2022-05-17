<?php
namespace app\controllers\entry;

use app\hexlet\JsonHelper;
use app\models\entry\entry_node\EntryNodeDocument;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\entry\IFlowEntry;
use app\models\project\IFlowProject;
use app\models\tag\FlowTag;
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


        $from_tag_name = $params['from_tags']??null;
        $target_doc_name = $params['target_entry']??null;
        $target_tag_name = $params['target_tag']??null;

        if ($from_tag_name) {
            $tag_params = new FlowTagSearchParams();
            $tag_params->addGuidsOrNames($from_tag_name)->addGuidsOrNames($target_tag_name)->
            setOwningProjectGuid($project?->get_project_guid());
            $tag_search = new FlowTagSearch();
            $all_tags = $tag_search->get_tags($tag_params)->get_direct_match_tags();
            /**
             * @var FlowTag|null $from_tag
             */
            $from_tag = null;
            $target_tag = null;
            foreach ($all_tags as $a_tag) {
                if ($a_tag->flow_tag_name === $from_tag_name) { $from_tag = $a_tag;}
                if ($a_tag->flow_tag_name === $target_tag_name) { $target_tag = $a_tag;}
            }

            $entry_params = new FlowEntrySearchParams();
            $entry_params->addGuidsOrNames($target_doc_name);
            $found_entries =  FlowEntrySearch::search($entry_params);
            $target_doc = null;
            $target_entry = $found_entries[0]??null;
            if ($target_entry) { $target_doc = new EntryNodeDocument($target_entry);}
            $bb_code =  $doc->insert_at(from_tags: [$from_tag],target_doc: $target_doc,target_tag: $target_tag);
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