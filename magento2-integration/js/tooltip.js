/**
 *  @author Maxim Tkachuk <dev@modernminds.com>
 */


/**
 *  input_name => tooltip
 */
var tooltips = {
    m2i_mage_auto_adding: "Should plugin automatically add header/footer to your theme?",
    m2i_use_mage_layout_names: "The best approach is to make sure this box is checked",
};




for (var name in tooltips) {
    jQuery("[name='" + name + "']").after('<img src="' + m2i_urls.img + '/grey_question.png" class="tooltip" title="' + tooltips[name] + '">')
}
jQuery(function () {
    jQuery(document).tooltip();
});