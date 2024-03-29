<?php

namespace app\hexlet;

use app\helpers\Utilities;
use app\hexlet\hexlet_exceptions\BBException;
use app\hexlet\hexlet_exceptions\JsonHelperException;
use app\models\entry\entry_node\HexBatchBBCodeSet;
use app\models\entry\entry_node\IFlowEntryNode;
use Exception;
use Highlight\Highlighter;
use JBBCode\CodeDefinitionSet;
use JBBCode\Parser;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\Node\HtmlNode;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ContentLengthException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;
use PHPHtmlParser\Exceptions\UnknownChildTypeException;
use PHPHtmlParser\Options;

class BBHelper {

    public static function get_parsed_bb_code($original,?CodeDefinitionSet $bb_set = null) : ?Parser {

        $body = static::pre_process_bb_text($original);
        if (empty($body)) {return null;}


        if (!$bb_set) {$bb_set = new HexBatchBBCodeSet();}
        $parser = new Parser();
        $parser->addCodeDefinitionSet($bb_set);

        $parser->parse($body);
        return $parser;
    }

    protected static function pre_process_bb_text($original) : ?string {
        // will_send_to_error_log('original',$original);
        $safe_encoding = JsonHelper::to_utf8($original);
        // will_send_to_error_log('$safe_encoding',$safe_encoding);
        $trimmed = trim($safe_encoding);
        if (empty($trimmed)) {return null;}

        //replace flow_tag with closing
        $flow_tag_name = IFlowEntryNode::FLOW_TAG_BB_CODE_NAME;
        $trimmed = preg_replace(
            "/(?P<da_tag>\[$flow_tag_name\s+tag=(?P<tag>[\da-fA-F]+)\s*])/",
            "$1[/$flow_tag_name]",
            $trimmed);

        $trimmed = preg_replace(
            "/(?P<da_tag>\[$flow_tag_name\s+tag=(?P<tag>[\da-fA-F]+)\s+guid=(?P<guid>[\da-fA-F]+)\s*])/",
            "$1[/$flow_tag_name]",
            $trimmed);

        $trimmed = str_replace('[hr]','[hr][/hr]',$trimmed);//hr

        //add closing tag to [hr]
        $trimmed = preg_replace(
            "/(?P<da_tag>\[hr\s+guid=(?P<guid>[\da-fA-F]+)\s*])/",
            "$1[/hr]",
            $trimmed);

        $trimmed = str_replace('<?php','≺?php',$trimmed);//php
        $trimmed = str_replace('<?=','≺?=',$trimmed);//php
        $trimmed = str_replace('<=','≺?',$trimmed);//php

        //convert any p , br and non linux line returns to /n
        $lines_standardized = self::tags_to_n($trimmed,false,false);

        //  will_send_to_error_log('$lines_standardized',$lines_standardized);



        //remove any remaining tags
        $body = strip_tags($lines_standardized);



        $body = str_replace('] ',']&nbsp;',$body);//unicode space
        $body = str_replace('] ',']&nbsp;',$body);//regular space
        $body = str_replace('<','≺',$body);//php



        //will_send_to_error_log('after preg callback u space',$body);
        $body = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',$body);//tab


        //will_send_to_error_log('bb code after whitespace filter(2)',$body);


        //the parser will clip whitespace on the option, so temporarily rename the fonts generated by the js client when spaces in name
        $body = preg_replace_callback(
            "/(?P<da_tag>\[font=(?P<font>[\w -]+)\s*(?:guid=(?P<guid>[\da-fA-F]+))?\s*])/",
            function ($matches) {
                $guid = $matches['guid']??'';
                $guid_part = '';
                if ($guid) {
                    $guid_part = " guid=$guid";
                }
                $fontname = trim($matches['font']);
                $hyphenated_fontname = trim(str_replace(' ',BBHelper::SPACE_REPLACE,$fontname));

                return "[font=$hyphenated_fontname".$guid_part."]";
            },
            $body);


        return $body;
    }

    const SPACE_REPLACE = '---';

    public static function undo_safe_parse_for_bb_code($body) :string {
        $body = preg_replace_callback(
            "/(?P<da_tag>\[font=(?P<font>[\w -]+)\s*(?:guid=(?P<guid>[\da-fA-F]+))?\s*])/",
            function ($matches) {
                $guid = $matches['guid']??'';
                $guid_part = '';
                if ($guid) {
                    $guid_part = " guid=$guid";
                }
                $fontname = $matches['font'];
                $un_hyphenated_fontname = trim(str_replace(BBHelper::SPACE_REPLACE,' ',$fontname));

                return "[font=$un_hyphenated_fontname".$guid_part."]";
            },
            $body);


        $body = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;',"\t",$body);//tab

        $body = str_replace(']&nbsp;','] ',$body);//regular space


        return $body;
    }


    public static function html_from_bb_code($original,?CodeDefinitionSet $bb_set = null) : ?string {

      //  $original = str_replace("[hr]\n", "[hr]", $original);
        $original = preg_replace(
            "/(?P<da_tag>\[hr\s+(?:guid=(?P<guid>[\da-fA-F]+))?\s*])\n/",
            "$1",
            $original);


        $original = preg_replace_callback(
            "/(?P<da_tag>\[size=(?P<size>\d+)\s+(?:guid=(?P<guid>[\da-fA-F]+))?\s*])/",
            function ($matches) {
                $guid = $matches['guid']??'';
                $guid_part = '';
                if ($guid) {
                    $guid_part = " guid=$guid";
                }
                $sizes = [7,10,13,16,18,24,32,48];
                $int_size = (int)$matches['size'];
                if ($int_size < 0) { $int_size = 0;}
                if ($int_size > 7) { $int_size = 7;}
                $new_size = $sizes[$int_size];
                return "[size=$new_size".$guid_part."]";
            },
            $original);



        $parser = static::get_parsed_bb_code($original,$bb_set);
        if (empty($parser)) {return  null;}



        $post =  $parser->getAsHtml();


        //will_send_to_error_log('after parse ',$post);

        //add in image dimensions, if they exist
        $post = preg_replace('/alt="(\d+)x(\d+)"/', ' width="$1" height="$2" ', $post);

        //remove any trailing commas in font family
        $post = preg_replace(  '/(font-family:"[-_\w]+)"(,)?/', '"$1', $post);


        //put in br for each /n
        // will_send_to_error_log('before the A',$post);
        $post = str_replace("\n", "<br>", $post);
        //  will_send_to_error_log('after the A',$post);



        //rename the temp named fonts in the html
        $post = preg_replace_callback(
            '/(font-family:\s*(?P<da_font_name>[-_\w]+))/',
            function ($matches) {

                $fontname = $matches['da_font_name'];
                $un_hyphenated_fontname = trim(str_replace(BBHelper::SPACE_REPLACE,' ',$fontname));

                return "font-family:$un_hyphenated_fontname";
            },
            $post);


        //remove br from ul and ol, and table tr and td and code
        try {
            $dom = new Dom;
            $dom->loadStr( $post );
            $br_in_ul_array = $dom->find( 'ul br' );
            /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
            foreach ($br_in_ul_array as &$br ) {
                $br->delete();
                unset( $br );
            }

            $br_after_hr_array = $dom->find( 'hr + br' );
            /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
            foreach ($br_after_hr_array as &$br ) {
                $br->delete();
                unset( $br );
            }

            $br_in_ol_array = $dom->find( 'ol br' );
            /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
            foreach ($br_in_ol_array as &$br ) {
                $br->delete();
                unset( $br );
            }


            $highlight_these = $dom->find( 'pre.flow-code-highlight' );

            foreach ($highlight_these as $highlight_this ) {
                /**
                 * @var HtmlNode $highlight_this
                 */
                $inner_html = $highlight_this->innerHtml();
                $my_text = str_replace('<br />',"\n",$inner_html);

                $hl = new Highlighter();
                $preg_ret = preg_match_all('/#lang#(?P<language>.+)#lang#/', $my_text, $output_array);
                Utilities::throw_if_preg_error($preg_ret);

                if (array_key_exists('language',$output_array) && count($output_array['language'])) {
                    $lang = $output_array['language'][0];
                    $my_text = preg_replace('/#lang#(.+)#lang#/', '', $my_text);
                    Utilities::throw_if_preg_error($my_text);
                    $my_text = trim($my_text);
                    try {
                        $highlighted = $hl->highlight($lang, $my_text);
                    } catch (Exception $e) {
                        throw new BBException("[html_from_bb_code] error highlighting language". $e->getMessage(),$e->getCode(),$e);
                    }

                } else {
                    $hl->setAutodetectLanguages(['php', 'c++', 'html', 'css','python','sh','json','js']);
                    $my_text = trim($my_text);
                    try {
                        $highlighted = $hl->highlightAuto($my_text);
                    } catch (Exception $e) {
                        throw new BBException("[html_from_bb_code] error auto highlighting language". $e->getMessage(),$e->getCode(),$e);
                    }

                }

                $code_string = "<code class=\"hljs $highlighted->language\">".$highlighted->value."</code>";

                foreach ($highlight_this->getChildren() as $children) {
                    $children->delete();
                }

                $my_dom = new Dom;
                $options = new Options();
                $options->setPreserveLineBreaks(true);
                $my_dom->loadStr($code_string,$options);
                $new_tag = $my_dom->find('code')[0];

                $highlight_this->addChild($new_tag);
            }

            // the only way to preserve whitespace using this algorithm and deal with how the bbcode editor makes tables,
            // is to literally go through and remove the br after I converted them from newlines above
            // otherwise most browsers will dump the br above the table making whitespace layout around tables bad

            $return_before_td_fix = (string) $dom;
            $fixed_up_string = preg_replace('#</td>\s*<br />#','</td>',$return_before_td_fix);
            $fixed_up_string = preg_replace('#</tr>\s*<br />#','</tr>',$fixed_up_string);
            $return = $fixed_up_string;
        } catch (ChildNotFoundException|CircularException|StrictException|NotLoadedException|ContentLengthException|LogicalException|
        UnknownChildTypeException $e) {
            throw new JsonHelperException($e->getMessage(),$e->getCode(),$e);
        }



        return $return;
    }

    /**
     * Converts <br> and <p> into \n characters while stripping out all previous newline characters
     * Only deals with these two tags so need to remove other tags before or afterwards (preferably before)
     *
     * The algorithm is simple and does not rely on p tags being aligned (so can deal with mismatched and broke html code too)
     *   1) remove optionally the nobreak character
     *   2) remove all existing newlines for all different operating systems
     *   3) for each br tag replace it with \n
     *   4) for each closing p tag replace it with \n
     *   5) remove all opening p tags
     *   6) remove space between newlines
     * @param string $string <p>
     *   the string to be converted, assumes with the unicode replacement that the string is not encoding mangled
     *    so need to make sure that the php and html are talking enough in utf8 ( I did here for when I applied it)
     * <p>
     *
     * @param bool $replace_nobreak_space <p>
     * the default is false, and means no replacement
     *  otherwise the nobreak unicode character will be replaced by the value of this param
     *  This is here because the ckeditor will put this between p tags and so will add spaces that are not intended
     *   when this function gets done
     *   I added this as an option so this function can be used in different places in the code and sometimes
     *   a no break character is desired
     * </p>
     *
     * @param bool $b_remove_existing_newlines, if true (by default) will remove all existing newlines first
     * @return string
     * @throws JsonHelperException  if one of the operations fail
     */
    public static function tags_to_n(string $string, bool $replace_nobreak_space= false, bool $b_remove_existing_newlines = true): string
    {

        //note: if having trouble with a mangled utf8 string then use
        //$string = ForceUTF8\Encoding::toUTF8($string);
        // after loading in the vendor file vendor/neitanod/forceutf8/src/ForceUTF8/Encoding.php
        // I did not have to this here, after changing
        // the output headers and charsets in the forms and internal encoding and db connections
        // for all the setting pages

        if (empty($string) && !is_numeric($string)) {return '';}



        //optionally remove the nobreak space, which is not picked up in the regular php replace functions
        if ($replace_nobreak_space !== false) {
            $string = mb_ereg_replace(' ',$replace_nobreak_space,$string); //the empty  is not a regular space but a unicode space!
            //this is a unicode NO-BREAK in the quotes

            if ($string === false) {
                throw new JsonHelperException("Error in mb_ereg_replace in tags_to_n");
            }
        }

        if ($b_remove_existing_newlines) {
            //strip out all newlines (mac, windows, and linux types) \r\n  \n \r
            $string = str_replace("\r\n", '', $string);
            $string = str_replace("\r", '', $string);
            $string = str_replace("\n", '', $string);
        } else {
            $string = str_replace("\r\n", "\n", $string);
            $string = str_replace("\r", "\n", $string);
        }


        // replace <br> <br/> and all whitespace variants  etc with /n
        $string = preg_replace(/** @lang text */
            '#<br */? *>\s*#i', "\n", $string);
        if ($string === null) {
            throw new JsonHelperException("Error in mb_ereg_replace in tags_to_n");
        }

        // replace </p>  and all whitespace variants with /n

        $string = preg_replace('#</p *>#i', "\n", $string);
        if ($string === null) {
            throw new JsonHelperException("Error in mb_ereg_replace in tags_to_n");
        }

        // strip out <p> and all newline variants
        $string = preg_replace(/** @lang text */'#<p *>#i', "", $string);
        if ($string === null) {
            throw new JsonHelperException("Error in mb_ereg_replace in tags_to_n");
        }

        //remove whitespace between two newlines
        $string = preg_replace('#\n +\n#im', "\n\n", $string);
        if ($string === null) {
            throw new JsonHelperException("Error in mb_ereg_replace in tags_to_n");
        }

        return $string;
    }


}