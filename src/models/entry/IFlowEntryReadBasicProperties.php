<?php

namespace app\models\entry;


interface IFlowEntryReadBasicProperties {

    public function get_parent_guid() : ?string;
    public function get_parent_id() : ?int;


    public function get_created_at_ts() : ?int;
    public function get_updated_at_ts() : ?int;

    public function get_id() : ?int;
    public function get_guid() : ?string;
    public function get_title() : ?string;
    public function get_blurb() : ?string;





    public function get_project_guid() : ?string;
    public function get_project_id() : ?int;

}