<?php

namespace app\models\entry\entry_node;

use app\hexlet\WillFunctions;
use JBBCode\CodeDefinitionSet;
use JBBCode\validators\CssColorValidator;
use JBBCode\validators\FnValidator;
use JBBCode\validators\UrlValidator;

class HexBatchBBCodeSet implements CodeDefinitionSet{

    /** @var HexBatchCodeDefinition[] The default code definitions in this set. */
    protected array $definitions = array();


    /**
     * Constructs the default code definitions.
     */
    public function __construct()
    {


        /* [b] bold tag */
        $builder = new HexBatchCodeBuilder('b', '<strong data-guid="{guid}" >{param}</strong>');
        $this->definitions[] = $builder->build();

        /* [i] italics tag */
        $builder = new HexBatchCodeBuilder('i', '<em data-guid="{guid}" >{param}</em>');
        $this->definitions[] = $builder->build();
        

        /* [u] underline tag */
        $builder = new HexBatchCodeBuilder('u', '<u data-guid="{guid}" >{param}</u>');
        $this->definitions[] = $builder->build();

        $urlValidator = new UrlValidator();

        /* [url] link tag */
        $builder = new HexBatchCodeBuilder('url', '<a href="{param}" data-guid="{guid}" >{param}</a>');
        $builder->setParseContent(false)->setBodyValidator($urlValidator);
        $this->definitions[] = $builder->build();

        /* [url=http://example.com] link tag */
        $builder = new HexBatchCodeBuilder('url', '<a href="{option}" data-guid="{guid}" >{param}</a>');
        $builder->setUseOption(true)->setParseContent(true)->setOptionValidator($urlValidator);
        $this->definitions[] = $builder->build();

        /* [img] image tag */
        $builder = new HexBatchCodeBuilder('img', '<img src="{param}"  data-guid="{guid}" />');
        $builder->setUseOption(false)->setParseContent(false)->setBodyValidator($urlValidator);
        $this->definitions[] = $builder->build();

        /* [img=alt text] image tag */
        $builder = new HexBatchCodeBuilder('img', '<img src="{param}" alt="{option}"  data-guid="{guid}" />');
        $builder->setUseOption(true)->setParseContent(false)->setBodyValidator($urlValidator);
        $this->definitions[] = $builder->build();

        /* [color] color tag */
        $builder = new HexBatchCodeBuilder('color', '<span style="color: {option}" data-guid="{guid}" >{param}</span>');
        $builder->setUseOption(true)->setOptionValidator(new CssColorValidator());
        $this->definitions[] = $builder->build();

        //end default definitions above, below is expanded so both forms will be added for each tag



        /* [sub] subtext tag */
        $builder = new HexBatchCodeBuilder('sub', '<sub data-guid="{guid}" >{param}</sub>');
        $this->definitions[] = $builder->build();

        /* [sup] */
        $builder = new HexBatchCodeBuilder('sup', '<sup data-guid="{guid}" >{param}</sup>');
        $this->definitions[] = $builder->build();

        /* [ul] unorganized list */
        $builder = new HexBatchCodeBuilder('ul', '<ul data-guid="{guid}" >{param}</ul>');
        $this->definitions[] = $builder->build();

        /* [ol] organized list */
        $builder = new HexBatchCodeBuilder('ol', '<ol data-guid="{guid}" >{param}</ol>');
        $this->definitions[] = $builder->build();

        /* [li] list element */
        $builder = new HexBatchCodeBuilder('li', '<li data-guid="{guid}" >{param}</li>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder('table', '<table data-guid="{guid}" >{param}</table>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder('th', '<th data-guid="{guid}" >{param}</th>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder('tr', '<tr data-guid="{guid}" >{param}</tr>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder('td', '<td data-guid="{guid}" >{param}</td>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder("font", '<span style="font-family:{option}" data-guid="{guid}" >{param}</span>');
        $builder->setUseOption(true);
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder("size",
            '<span style="font-size:{option}px" data-guid="{guid}" >{param}</span>');
        $builder->setUseOption(true);
        $this->definitions[] = $builder->build();

        //strikethrough
        $builder = new HexBatchCodeBuilder("s",
            '<span style="text-decoration: line-through" data-guid="{guid}" >{param}</span>');
        $this->definitions[] = $builder->build();

        // alignment
        $builder = new HexBatchCodeBuilder("center",
            '<div style="text-align: center;display: inline-block" data-guid="{guid}"  class="flow-center" >{param}</div>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder("left",
            '<div style="text-align: left;display: inline-block" data-guid="{guid}" class="flow-left" >{param}</div>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder("right",
            '<div style="text-align: right;display: inline-block" data-guid="{guid}"  class="flow-right" >{param}</div>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder("justify",
            '<div style="text-align: justify;display: inline-block" data-guid="{guid}"  class="flow-justify" >{param}</div>');
        $this->definitions[] = $builder->build();

        $builder = new HexBatchCodeBuilder("quote",
            '<div  class="flow-quote" data-guid="{guid}" >{param}</div>');
        $this->definitions[] = $builder->build();


        $builder = new HexBatchCodeBuilder("code", '<pre class="flow-code-highlight" data-guid="{guid}" >{param}</pre>');
        $this->definitions[] = $builder->build();



        $builder = new HexBatchCodeBuilder(
            IFlowEntryNode::FLOW_TAG_BB_CODE_NAME,
            '<span class="flow-bb-tag flow-tag-display flow-tag-{tag} d-none" data-tag_guid="{tag}" data-guid="{guid}" ></span>'
        );
        $builder->setUseOption(true)->setOptionValidator(new FnValidator(
            function($input) {
                return WillFunctions::is_valid_guid_format($input);
            }),'tag')->setParseContent(false);
        //flow_tag
        $this->definitions[] = $builder->build();
        


    }

    public function getCodeDefinitions(): array
    {
        return $this->definitions;
    }
}