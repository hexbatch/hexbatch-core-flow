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


});