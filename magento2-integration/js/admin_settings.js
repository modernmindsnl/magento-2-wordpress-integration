var $m2i_tds = {
    dir: jQuery('#m2i_mage_dir').parent()
};

var m2i_imgs_src = {
    loading: m2i_urls.img + '/loading.gif',
    question: m2i_urls.img + '/question.png',
    success: m2i_urls.img + '/success.png',
    exclam: m2i_urls.img + '/exclam.png'
};

if($m2i_tds.dir.length){
    $m2i_tds.dir.append('<img class="m2i_status_check" src="" />');

    var m2i_check_ajax = function () {
        $m2i_tds.dir.find('img.m2i_status_check').attr('src', m2i_imgs_src.loading);

        var dir_input = $m2i_tds.dir.find('input[type="text"]');
        var submit_button = jQuery('#submit');

        var dir_already_disabled = dir_input.hasClass('disabled');

        if(!dir_already_disabled){
            dir_input.addClass('disabled').attr('disabled', 'disabled');
            submit_button.addClass('disabled').attr('disabled', 'disabled');
        }

        jQuery.post(ajaxurl, {
            'action': 'm2i_check_magento',
            'm2i_mage_dir': dir_input.val()
        }, function (response) {
            var dir_src = m2i_imgs_src.question, title = '';

            switch (parseInt(response)) {
                case 0:
                    dir_src = m2i_imgs_src.success;
                    title = 'Magento 2 Integration has done all steps successfully!';
                    break;
                case 1:
                    dir_src = m2i_imgs_src.exclam;
                    title = 'Magento 2 Integration cannot find autoloader file for start point.';
                    break;
                case 2:
                    dir_src = m2i_imgs_src.exclam;
                    title = 'Magento 2 Integration cannot find file with Bootstrap class.';
                    break;
                case 3:
                    title = 'Magento 2 Integration cannot find selected store view.';
                    break;
                case 4:
                    title = 'Magento 2 Integration has done all steps, but unfortunately, something went wrong...';
                    break;
            }
            
            var $img_status = $m2i_tds.dir.find('img.m2i_status_check');
            $img_status.attr('src', dir_src);
            $img_status.attr('title', title);

            if(!dir_already_disabled){
                dir_input.removeClass('disabled').removeAttr('disabled');
                submit_button.removeClass('disabled').removeAttr('disabled');
            }

        });
    };

    m2i_check_ajax();

    $m2i_tds.dir.find('input[type="text"]').change(function () {
        m2i_check_ajax();
    });
}

jQuery.fn.flag_dependencies = function() {
    var ids_hide = (this.data('dependencies-hide') || '').split(',');
    var ids_show = (this.data('dependencies-show') || '').split(',');
    var ids_hide_selector = '';
    var ids_show_selector = '';
    var $this = this;
    
    for(var i = 0; i < ids_hide.length; i++) ids_hide_selector += '#' + ids_hide[i] + ',';
    if(ids_hide_selector !== '') ids_hide_selector = ids_hide_selector.slice(0, -1);
    
    for(var i = 0; i < ids_show.length; i++) ids_show_selector += '#' + ids_show[i] + ',';
    if(ids_show_selector !== '') ids_show_selector = ids_show_selector.slice(0, -1);
    
    var action = function() {
        if($this.get(0).checked) {
            jQuery(ids_hide_selector).parent().parent().hide(250);
            jQuery(ids_show_selector).parent().parent().show(250);
        } else {
            jQuery(ids_hide_selector).parent().parent().show(250);
            jQuery(ids_show_selector).parent().parent().hide(250);
        }
    };
    
    action();
    this.change(action);
};

jQuery('input[type="checkbox"]').each( function() {jQuery(this).flag_dependencies() });
