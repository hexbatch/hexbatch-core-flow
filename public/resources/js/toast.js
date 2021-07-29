
$(function (){
    $.toastDefaults.position = 'bottom-right'
});
/**
 *
 * @param {object} options
 * @param {string} [options.title]
 * @param {string} [options.subtitle]
 * @param {string} [options.content]
 * @param {string} [options.type] ['info', 'warning', 'success', 'error']
 * @param {number} [options.delay]  0 for always there, number for ms, anything else gets 5 seconds
 * @param {object} [options.image]  must have scr, class, alt
 * @param {string} options.image.src the url to the image
 * @param {string} [options.image.class] optional class to add to the image
 * @param {string} [options.image.alt] optional alt for the image
 * @param {string} [options.position] ['top-right', 'top-left', 'top-center', 'bottom-right', 'bottom-left', 'bottom-center']
 */
function do_toast(options) {

    let thing = {
        title : 'something happened',
        type : 'info',
        subtitle : '',
        message : '',
    };


    if ('title' in options) { thing.title = options.title;}
    if ('type' in options) { thing.type = options.type;}
    if ('subtitle' in options) { thing.subtitle = options.subtitle;}
    if ('content' in options) { thing.content = options.content;}
    if ('image' in options) { thing.img = options.image;}
    if ('delay' in options) {
        if ($.isNumeric(options.delay)) {
            let da_int = parseInt(options.delay.toString());
            if (da_int > 0) {
                thing.delay = da_int;
            }
        } else {
            thing.delay = 5000;
        }
    }

    $.toast(thing);
}
