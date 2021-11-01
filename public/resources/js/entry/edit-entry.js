jQuery(function($) {

    $(`#fl-entry-create-action`).click(function(){
        let data =  {
            flow_entry_title : $("input#flow_entry_title").val(),
            flow_entry_blurb : $("textarea#flow_entry_blurb").val(),
            flow_entry_body_bb_code : $("input#flow_entry_body_bb_code").val(),

        };
        do_flow_ajax_action(create_entry_url,data,function(ret) {
            console.log(ret);
        },function(ret) {
            console.log(ret);
        } ,"Created Entry","Issue creating entry");
    });

});