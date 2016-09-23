<?php

/**
 * Template for auto-overriding "<header>" and "<footer>" tags in all pages for current theme
 * 
 * @since 1.0.1
 */

/** 
 * @param DOMNode $el
 * @param DOMNode $el_for_insertion
 */
function _m2i_replace_with_el($el, $el_for_insertion) {
    if ($el && $el_for_insertion) {
        $ready_el = $el->ownerDocument->importNode($el_for_insertion, true);
        $parent = $el->parentNode;
        $parent->insertBefore($ready_el, $el);
        $parent->removeChild($el);
    }
}

/** 
 * Filter all content for replacing header and footer if possible
 * 
 * @param string $html
 */
function m2i_replace_header_and_footer($html) {
    global $m2i_options;

    $doc = new DOMDocument;
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    $body = $doc->getElementsByTagName('body')->item(0);
    preg_match("%<body[^>]*>%", $html, $body_tag);

    if ($body) {
        
        $footers = $body->getElementsByTagName('footer');
        if ($m2i_options['mage_header_flag'] === 'on') {
            _m2i_replace_with_el($body->getElementsByTagName('header')->item(0), m2i_get_header(false));
        }
        
        $html = $doc->saveHTML();
        $html = preg_replace("%<body[^>]*>%", $body_tag[0], $html);
    }

    libxml_clear_errors();

    return $html;
}

ob_start();

include(M2I_Content::$template);

$html = ob_get_clean();
echo m2i_replace_header_and_footer($html);