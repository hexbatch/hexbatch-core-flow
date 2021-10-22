//for project history

jQuery(function ($){
    let modal;
    let editing_div = $("div#flow-new-tag-dialog");
    let bare_select_control = editing_div.find('select#flow-new-tag-parent-list');
    let tag_name_input = editing_div.find('input#flow-new-tag-name');
    let body = $('body');

    /**
     * @type {?FlowTag}
     */
    let parent_tag = null;


    // noinspection JSPotentiallyInvalidConstructorUsage,JSUnusedGlobalSymbols
    modal = new tingle.modal({
        footer: true,
        stickyFooter: false,
        closeMethods: ['overlay', 'button', 'escape'],
        closeLabel: "Close",
        cssClass: ['flow-tag-new-tingle'],
        onOpen: function() {
            //clear out older values
            tag_name_input.val();
            create_select_2_for_tag_search(bare_select_control,false,"Optionally select a parent",false);

        },
        onClose: function() {
            //do not destroy the tingle but obliterate the select2
           utterly_destroy_select2(bare_select_control);
        },
        beforeClose: function() {
            return true; // close the modal
            // return false; // nothing happens
        }
    });

    modal.setContent(editing_div[0]);

    // add a button
    modal.addFooterBtn('Create', 'tingle-btn tingle-btn--primary', function() {
        let parent_guid = null;
        if (parent_tag) { parent_guid = parent_tag.flow_tag_guid }
        let tag_name = tag_name_input.val();
        if (tag_name) {
            create_tag({flow_tag_name: tag_name,parent_tag_guid:parent_guid}
                ,function(response) {
                    console.log('created tag',response)
                    modal.close();
                });
        }

    });

    bare_select_control.on('select2:select', function () {
        let data_array = bare_select_control.select2("data");
        if (data_array.length) {
            parent_tag = data_array[0];
        } else {
            parent_tag = null;
        }
        console.log('woke',parent_tag);
    });

    bare_select_control.on('select2:unselecting', function () {
        parent_tag = null;
        console.log('sleep',parent_tag);
    });



    $('button.flow-new-tag-show-dialog').on("click", function() {


        // open modal
        modal.open();
    }) ;





    body.on('click', 'button#flow-new-tag-parent-goto', function () {
        //todo add go to parent from new
    });



});

