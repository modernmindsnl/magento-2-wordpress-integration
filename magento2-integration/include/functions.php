<?php
/**
 *  @global array Options setted on the settings page in the admin panel
 */
global $m2i_options;

$m2i_options = M2I_Settings::get_instance()->get_options();

/**
 *  Return true if Magento2 returns content
 *  
 *  @return bool
 */
function m2i_is_success() {
    $response = M2I_External::get_response();
    return (is_object($response) && $response instanceof Magento\Framework\App\ResponseInterface && !empty($response->getContent())) ? true : false;
}

/**
 *  An functional alias for  <i>M2I_Settings::get_options()</i>
 * 
 *  @return array Options setted on the settings page in the admin panel 
 */
function m2i_get_options() {
    global $m2i_options;
    return $m2i_options;
}

/**
 *  Content of default Magento2 page
 * 
 *  @return string
 */
function m2i_get_content() {
    static $content = null;
    if (is_null($content)) {
        if (m2i_is_success()) {
            $content = M2I_External::get_response()->getContent();
        } else {
            $content = '<!DOCTYPE HTML><HTML><HEAD></HEAD><BODY></BODY></HTML>';
        }
    }
    return $content;
}

/**
 *  Header html content
 * 
 *  @param bool $return_html
 *  @return string|DOMElemnet
 */
function m2i_get_header($return_html = true) {
    static $header_el = null;
    global $m2i_options;

    if (is_null($header_el)) {
        if ($m2i_options['use_mage_layout_names'] === 'on') {
            try {
                M2I_External::$needs_mage_translate = true;
                $header_el = m2i_get_dom_el(M2I_External::get_layout()->renderElement($m2i_options['mage_header_block_name']));
                M2I_External::$needs_mage_translate = false;
            } catch (OutOfBoundsException $e) {
            }
        } else {
            $header_el = m2i_get_el_by_class($m2i_options['mage_header_class'], $m2i_options['mage_header_tag']);
        }
    }

    return $return_html ? ($m2i_options['mage_header_flag'] === 'on' ? m2i_dom_el_to_html($header_el) : '') : $header_el;
}

/**
 * Concatenated css links from Magento2 default page
 * 
 * @return string
 */
function m2i_get_links_css_tags() {
    static $links_css_content = null;

    if (is_null($links_css_content)) {
        $html = '';
        $list = m2i_get_els_by_tag('link');
        foreach ($list as $item) {
            if ($item->getAttribute('type') === 'text/css') {
                $html .= m2i_dom_el_to_html($item);
            }
        }
        $links_css_content = $html;
    }

    return $links_css_content;
}

/**
 *  Select DOM elements by tag & class
 * 
 *  @param string $class_name
 *  @param string $tag_name
 *  @return DOMELment 
 */
function m2i_get_el_by_class($class_name, $tag_name = 'div') {
    $list = m2i_get_els_by_tag($tag_name);
    foreach ($list as $item) {
        if (strpos($item->getAttribute('class'), $class_name) !== false) {
            return $item;
        }
    }
    return null;
}

/**
 *  Create DOM element from html
 *  
 *  @param string $html
 *  @return DOMElement|null
 */
function m2i_get_dom_el($html) {
    if (empty($html)) {
        return null;
    }
    $doc = new DOMDocument;
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();
    $body = $doc->getElementsByTagName('body')->item(0);
    return $body ? $body->childNodes->item(0) : null;
}

/**
 *  Select DOM elements by tag
 * 
 *  @param string $tag_name
 *  @return DOMNodeList
 */
function m2i_get_els_by_tag($tag_name) {
    $doc = new DOMDocument;

    libxml_use_internal_errors(true);
    $doc->loadHTML(m2i_get_content());
    libxml_clear_errors();

    $list = $doc->getElementsByTagName($tag_name);
    return $list;
}

function m2i_get_els_by_tag_from($tag_name, $from_tag) {
    $doc = new DOMDocument;
    $from = m2i_get_els_by_tag($from_tag);
    if (empty($from))
        return array();
    $html = m2i_dom_el_to_html($from->item(0));
    if (empty($html))
        return array();
    $doc->loadHTML($html);
    libxml_clear_errors();
    $list = $doc->getElementsByTagName($tag_name);
    return $list;
}

/**
 *  DOMElement html content
 * 
 *  @param DOMElement $item 
 *  @return string
 */
function m2i_dom_el_to_html($item) {
    return is_object($item) ? ($item instanceof DOMElement ? $item->ownerDocument->saveHTML($item) : $item->saveHTML($item) ) : '';
}

/**
 *  Get Magento 2 stores array: code => name
 *  
 *  @return array
 */
function m2i_get_stores() {
    $res = array();
    if (!m2i_is_success()) return $res;
    
    $obj = M2I_External::get_bootstrap()->getObjectManager();
    $stores = $obj->get("\Magento\Store\Model\StoreManager")->getStores();
    foreach ($stores as $store) {
        $res[$store->getCode()] = $store->getName();
    }
    
    return $res;
}

/**
 *  return array of all available blocks from Mage layout
 *
 *  @return array
 */
function m2i_get_blocks() {
    $blocks = array();
    if (!m2i_is_success()) return $blocks;
    
    $layout = M2I_External::get_layout();
    if (empty($layout)) return $blocks;
    $blocks = $layout->getAllBlocks();
    
    return array_keys($blocks);
}



