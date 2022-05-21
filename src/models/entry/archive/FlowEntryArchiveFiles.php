<?php

namespace app\models\entry\archive;


use RuntimeException;


abstract class FlowEntryArchiveFiles extends FlowEntryArchiveBase {



    /**
     * @param array $put_issues_here OUTREF
     * @return int returns 1 or 0
     * @throws
     */
    public function has_minimal_information(array &$put_issues_here = []) : int {

        $path = $this->get_entry()->get_entry_folder();
        if (!is_readable($path)) {
            $put_issues_here[] = "entry folder not found at $path";
            return 0;
        }

        $bb_code_path = $path. DIRECTORY_SEPARATOR . static::BB_CODE_FILE_NAME;
        if (!is_readable($bb_code_path)) {
            $put_issues_here[] = "bbcode not found at $bb_code_path";
            return 0;
        }

        $blurb_path = $path. DIRECTORY_SEPARATOR . static::BLURB_FILE_NAME;
        if (!is_readable($blurb_path)) {
            $put_issues_here[] = "blurb not found at $blurb_path";
            return 0;
        }

        $title_path = $path. DIRECTORY_SEPARATOR . static::TITLE_FILE_NAME;
        if (!is_readable($title_path)) {
            $put_issues_here[] = "title not found at $title_path";
            return 0;
        }


        return 1;
    }


    /**
     * Writes the entry, and its children , to the archive
     * @throws
     */
    public function write_archive() : void {
        parent::write_archive();
        $path = $this->get_entry()->get_entry_folder();

        $bb_code_path = $path. DIRECTORY_SEPARATOR . static::BB_CODE_FILE_NAME;
        $bb_code = $this->get_entry()->get_bb_code();
        $b_ok = file_put_contents($bb_code_path,$bb_code);
        if ($b_ok === false) {
            throw new RuntimeException("Could not write entry bbcode to $bb_code_path");
        }

        $blurb_path = $path. DIRECTORY_SEPARATOR . static::BLURB_FILE_NAME;
        $b_ok = file_put_contents($blurb_path,$this->get_entry()->get_blurb());
        if ($b_ok === false) {
            throw new RuntimeException("Could not write entry blurb to $blurb_path");
        }


        $title_path = $path. DIRECTORY_SEPARATOR . static::TITLE_FILE_NAME;
        $b_ok = file_put_contents($title_path,$this->get_entry()->get_title());
        if ($b_ok === false) {
            throw new RuntimeException("Could not write entry title to $title_path");
        }

        $html_path = $this->get_entry()->get_html_path();
        $html = $this->get_entry()->get_html(false);
        $b_ok = file_put_contents($html_path,$html);
        if ($b_ok === false) {
            throw new RuntimeException("Could not write entry html to $html_path");
        }

    }


    /**
     * sets any data found in archive into this, over-writing data in entry object
     * @throws
     */
    public function read_archive() : void  {
        parent::read_archive();

        $path = $this->get_entry()->get_entry_folder();

        $bb_code_path = $path. DIRECTORY_SEPARATOR . static::BB_CODE_FILE_NAME;
        $stuff = file_get_contents($bb_code_path);
        if ($stuff === false) {
            throw new RuntimeException("Could not read entry bbcode to $bb_code_path");
        }
        $this->get_entry()->set_body_bb_code($stuff);

        $blurb_path = $path. DIRECTORY_SEPARATOR . static::BLURB_FILE_NAME;
        $stuff = file_get_contents($blurb_path);
        if ($stuff === false) {
            throw new RuntimeException("Could not read entry blurb to $blurb_path");
        }
        $this->get_entry()->set_blurb($stuff);


        $title_path = $path. DIRECTORY_SEPARATOR . static::TITLE_FILE_NAME;
        $stuff = file_get_contents($title_path);
        if ($stuff === false) {
            throw new RuntimeException("Could not read entry title to $title_path");
        }
        $this->get_entry()->set_title($stuff);
    }


}