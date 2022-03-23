
jQuery(function($) {
    $(`body`).on('click','.flow-project-list button.flow-project-delete-action', function() {
        let me = $(this);
        let title = me.data('title');
        let url = me.data('url');

        my_swal.fire({
            title: 'Are you sure?',
            text: `Going to delete the Project ${title}`,
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
    function() {
                        toggle_action_spinner(me, 'normal');
                        let parent = me.closest('div.flow-project-list-item');
                        parent.remove();
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

});
