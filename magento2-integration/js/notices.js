jQuery('.notice').click(function (event) {
    var $el = jQuery(event.target);
    console.log($el.parent().attr('id'));
    if ($el.hasClass('notice-dismiss')) {
        jQuery.post(ajaxurl, {
            'action': 'm2i_notices',
            'id': $el.parent().attr('id')
        });
    }
});