<?php

namespace app\helpers;

use app\models\standard\FlowTagStandardAttribute;
use DI\DependencyException;
use DI\NotFoundException;
use InvalidArgumentException;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class StandardHelper extends BaseHelper
{
    const TEMPLATE_MODES = ['view','edit'];

    const STANDARD_TEMPLATE_PATH = HEXLET_TWIG_TEMPLATE_PATH . DIRECTORY_SEPARATOR .
                                        'pages' .  DIRECTORY_SEPARATOR . 'standard';

    public static function get_standard_helper(): StandardHelper
    {
        try {
            return static::get_container()->get('standardHelper');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }
    }

    /**
     *
     * @return string[]
     */
    public function get_viewable_standards() : array  {
        $ret = [];
        foreach (FlowTagStandardAttribute::getStandardAttributeNames() as $standard_name) {
            $t = $this->get_viewable_template_for_standard($standard_name);
            if ($t) {$ret[] = $standard_name;}
        }
        return $ret;
    }

    /**
     * @return string[]
     */
    public function get_editable_standards() : array  {
        $ret = [];
        foreach (FlowTagStandardAttribute::getStandardAttributeNames() as $standard_name) {
            $t = $this->get_editable_template_for_standard($standard_name);
            if ($t) {$ret[] = $standard_name;}
        }
        return $ret;
    }

    public function get_viewable_template_for_standard(string $standard,bool $b_frame=false) : ?string {
       return $this->get_template_for_standard($standard,'view',$b_frame);
    }

    public function get_editable_template_for_standard(string $standard,bool $b_frame=false) : ?string {
        return $this->get_template_for_standard($standard,'edit',$b_frame);
    }


    public function get_template_for_standard(string $standard,string $mode,bool $b_frame) : ?string {
        if (!in_array($standard,FlowTagStandardAttribute::getStandardAttributeNames())) {
            throw new InvalidArgumentException("Not a standard type ".$standard);
        }

        if (!in_array($mode,static::TEMPLATE_MODES)) {
            throw new InvalidArgumentException("Not a template mode: ". $mode);
        }

        $full_folder_path  = static::STANDARD_TEMPLATE_PATH. DIRECTORY_SEPARATOR . $standard .
            DIRECTORY_SEPARATOR . $mode  ;

        $real_folder_path = realpath($full_folder_path);
        if (!$real_folder_path) {return null;}
        if (!is_readable($real_folder_path)) {return null;}
        $dir = new RecursiveDirectoryIterator($real_folder_path);
        $ite = new RecursiveIteratorIterator($dir);
        if ($b_frame) {
            $pattern = '/.*\.frame.html.twig/';
        } else {
            $pattern = '/.*\.js.twig/';
        }
        $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
        $fileList = [];
        foreach($files as $file) {
            $fileList = array_merge($fileList, $file);
        }
        if (empty($fileList)) {return null;}
        return str_replace(HEXLET_TWIG_TEMPLATE_PATH. DIRECTORY_SEPARATOR,'', $fileList[0]);
    }





}