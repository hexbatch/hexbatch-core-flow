<?php
namespace app\hexlet;

use RuntimeException;
use ZipArchive;

/**
 * @author https://raw.githubusercontent.com/ttodua/useful-php-scripts/master/zip-folder.php Nicolas Heimann
 * slightly modified to allow top folder to be named and throw exception if error
 * @example new GoodZipArchive('path/to/input/folder',    'path/to/output_zip_file.zip') ;
 */
class GoodZipArchive extends ZipArchive
{
    public function __construct($a=false, $b=false,$c=null) { $this->create_func($a, $b,$c);  }

    public function create_func($input_folder=false, $output_zip_file=false,$top_folder_name = null)
    {
        if($input_folder !== false && $output_zip_file !== false)
        {
            if (empty($top_folder_name)) {
                $top_folder_name = basename($input_folder);
            }
            $res = $this->open($output_zip_file, ZipArchive::CREATE);
            if($res === true) 	{ $this->addDir($input_folder, $top_folder_name); $this->close(); }
            else  				{ throw new RuntimeException("Cannot create zip archive"); }
        }
    }

    // Add a Dir with Files and Sub dirs to the archive
    public function addDir($location, $name) {
        $this->addEmptyDir($name);
        $this->addDirDo($location, $name);
    }

    // Add Files & Dirs to archive
    private function addDirDo($location, $name) {
        $name .= '/';         $location .= '/';
        // Read all Files in Dir
        $dir = opendir ($location);
        while ($file = readdir($dir))    {
            if ($file == '.' || $file == '..') continue;
            // Rekursiv, If dir: GoodZipArchive::addDir(), else ::File();
            $do = (filetype( $location . $file) == 'dir') ? 'addDir' : 'addFile';
            $this->$do($location . $file, $name . $file);
        }
    }
}