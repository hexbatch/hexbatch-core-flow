let da_bb_code_editor = null;

jQuery(function($) {
    let body = $(`body`);

    body.on('click','.flow-entry-delete-action', function() {
        let me = $(this);
        let title = me.data('title');
        let url = me.data('url');
        if (!url) {return;}

        my_swal.fire({
            title: 'Are you sure?',
            html: `Going to delete the Entry <u>${title}</u>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                toggle_action_spinner(me, 'loading');
                let data = {};
                do_flow_ajax_action(url,data,
                    /**
                     *
                     * @param {FlowEntryLifetimeResponse} ret
                     */
                    function(ret) {
                        toggle_action_spinner(me, 'normal');
                        let parent = me.closest('div.flow-entry-listed');
                        if (parent.length) {
                            parent.remove();
                        } else {
                            location.href = ret.list_url;
                        }

                    },
                    function(ret) {
                        toggle_action_spinner(me, 'normal');
                        my_swal.fire(
                            `Cannot Delete ${title} :-(`,
                            ret.message,
                            'error'
                        )
                    },
                    `Project ${title} deleted!`,
                    `Cannot Delete ${title} :-(`
                );


            }
        });
    });

    body.on('click','.flow-entry-edit-action', function() {
        let me = $(this);
        let url = me.data('url');
        if (!url) {return;}
        location.href = url;
    });

    let bare_select_control = $('select#flow-tags-for-bb-list');

    /**
     * @type {?FlowTag}
     */
    let insert_this_tag = null;
    
    if (bare_select_control.length) {
        //script can run without it on the page
        create_select_2_for_tag_search(bare_select_control,false,"Select a Tag to insert",
            false,null,null,null );

        bare_select_control.on('select2:select', function () {
            let data_array = bare_select_control.select2("data");
            if (data_array.length) {
                insert_this_tag = data_array[0];
            } else {
                insert_this_tag = null;
            }
            console.log('tag to insert is ',insert_this_tag);
        });

        bare_select_control.on('select2:unselecting', function () {
            insert_this_tag = null;
        });
    }

    $(`#flow-new-tag-into-bb-action`).on('click',function(e) {
        e.preventDefault();
        if (!da_bb_code_editor) {
            do_toast({title:`Cannot insert tag`,subtitle:'',
                content: 'bb code editor is not created',delay:10000,type:'warning'});
            return;
        }
        if (!insert_this_tag) {
            do_toast({title:`Cannot insert tag`,subtitle:'',
                content: 'No tag selected',delay:10000,type:'warning'});
            return;
        }
        let bb_tag = `[flow_tag tag=${insert_this_tag.flow_tag_guid}]`
        da_bb_code_editor.insert(
            bb_tag, null,
            true, // filter
            true, // convert emoticons
            true  // allow mixed
        );
    });


});

function create_living_bb_editor(textarea_id,hidden_id) {
    let flow_tag_bb_struct = {

        tags: {
            "span": {
                "data-tag_guid": null
            }
        },
        isSelfClosing: true,
        isInline: true,
        isHtmlInline: undefined,
        allowedChildren: false,
        allowsEmpty: true,
        excludeClosing: true,
        skipLastLineBreak: true,
        strictMatch: false,

        breakBefore: false,
        breakStart: false,
        breakEnd: false,
        breakAfter: false,

        format: function(element, content) {
            let el_dom_tag = $(element);
            if(!el_dom_tag.data('tag_guid'))
                return content;

            return `[flow_tag tag=${el_dom_tag.data('tag_guid')}]`;
        },
        html: function(token, attrs, content) {
            if(  attrs.hasOwnProperty('tag') ) {
                if (attrs.tag) {
                    content = `<span class="flow-bb-tag flow-tag-display flow-tag-no-pointer-events flow-tag-${attrs.tag}" data-tag_guid="${attrs.tag}"></span>`;
                    setTimeout(function() {
                        update_tags_in_display(false);
                    },100)

                }
            }


            return content;
        },

        quoteType: sceditor.BBCodeParser.QuoteType.auto
    }

    sceditor.formats.bbcode.set('flow_tag',flow_tag_bb_struct);



    let textarea_jq = $(`textarea#${ textarea_id }`);
    let textarea = textarea_jq[0];
    let hidden_input = $(`input#${ hidden_id }`);

    sceditor.create(textarea, {
        format: 'bbcode',
        style:  SCEDITOR_STYLE_LINK,
        toolbarExclude: 'email,unlink,youtube,date,time,ltr,rtl',//,source,emoticon,cut,copy,paste,pastetext,maximize,
        emoticonsRoot: SCEDITOR_EMOTICONS_ROOT,
        emoticonsEnabled: false,
        bbcodeTrim: false,
        height:400,

    });
    da_bb_code_editor = textarea._sceditor;

    //insert styles
    let editor_body_node = da_bb_code_editor.getBody();
    let da_body = $(editor_body_node);
    let da_head = da_body.siblings('head');
    da_head.append(`<link rel="preload"
          href="https://fonts.googleapis.com/css?family=Roboto|Open+Sans|Space+Mono|Lato|Montserrat|Oswald|Raleway|Merriweather|Inconsolata|Allura|Sigmar+One|Gloria+Hallelujah|Montez|Lobster|Josefin+Sans|Shadows+Into+Light|Pacifico|Amatic+SC:700|Orbitron:400,900|Rokkitt|Righteous|Dancing+Script:700|Bangers|Chewy|Sigmar+One|Architects+Daughter|Abril+Fatface|Covered+By+Your+Grace|Kaushan+Script|Gloria+Hallelujah|Satisfy|Lobster+Two:700|Comfortaa:700|Cinzel|Courgette"
          as="style"
          onload="this.onload=null;this.rel='stylesheet'">`);

    da_head.append(`<link rel="stylesheet" href="${TAG_STYLE_LINK}" />`);
    da_head.append(`<link rel="stylesheet" href="${BOOTSTRAP_ICONS_STYLE_LINK}" />`);
    console.log('dis is what I found',da_head);


    da_bb_code_editor.bind('valuechange  keyup blur paste pasteraw',function(/* e */) {
        let words = da_bb_code_editor.val();

        if (words.length > MAX_LENGTH_FOR_CHAPTER) {
            do_toast({title:`Body is too long, Max size is ${ MAX_LENGTH_FOR_CHAPTER }`,subtitle:'',
                content: 'There was an issue saving the body',delay:10000,type:'warning'});
            return;
        }
        words = words.replace('%','&percnt;');
        hidden_input.val( words);
    },false,false);
}