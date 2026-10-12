<?php
/**
 * Website: http://sourceforge.net
 * Оригинальный одиночный парсер HTML DOM для чистой работы без Composer.
 */

define('HDOM_TYPE_ELEMENT', 1);
define('HDOM_TYPE_COMMENT', 2);
define('HDOM_TYPE_TEXT', 3);
define('HDOM_TYPE_ENDTAG', 4);
define('HDOM_TYPE_ROOT', 5);
define('HDOM_TYPE_UNKNOWN', 6);
define('HDOM_QUOTE_DOUBLE', 0);
define('HDOM_QUOTE_SINGLE', 1);
define('HDOM_QUOTE_NO', 3);
define('HDOM_INFO_BEGIN', 0);
define('HDOM_INFO_END', 1);
define('HDOM_INFO_QUOTE', 2);
define('HDOM_INFO_SPACE', 3);
define('HDOM_INFO_TEXT', 4);
define('HDOM_INFO_INNER', 5);
define('HDOM_INFO_OUTER', 6);

define('MAX_FILE_SIZE', 6000000);
define('DEFAULT_TARGET_CHARSET', 'UTF-8');
define('DEFAULT_BR_TEXT', "\r\n");
define('DEFAULT_SPAN_TEXT', " ");

function str_get_html($str, $lowercase=true, $forceTagsClosed=true, $target_charset=DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT) {
    $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
    if (empty($str) || strlen($str) > MAX_FILE_SIZE) {
        $dom->clear();
        return false;
    }
    $dom->load($str);
    return $dom;
}

class simple_html_dom_node {
    public $nodetype = HDOM_TYPE_UNKNOWN;
    public $tag = 'text';
    public $attr = array();
    public $children = array();
    public $nodes = array();
    public $parent = null;
    public $_ = array();

    function __construct($dom) {
        $this->dom = $dom;
        $dom->nodes[] = $this;
    }

    function __toString() { return $this->outertext(); }
    function clear() { $this->dom = null; $this->parent = null; $this->children = array(); $this->nodes = array(); }
    function find($selector, $idx=null) {
        $selectors = $this->parse_selector($selector);
        if (($count = count($selectors)) === 0) return array();
        $found_keys = array();

        for ($c=0; $c<$count; ++$c) {
            if (!isset($this->_[HDOM_INFO_BEGIN])) return array();
            $head = array($this->_[HDOM_INFO_BEGIN] => 1);

            $ret = array();
            foreach ($head as $k=>$v) {
                $n = ($k === 0) ? $this->dom->root : $this->dom->nodes[$k];
                $n->seek($selectors[$c], $ret);
            }
            $head = $ret;

            foreach ($head as $k=>$v) {
                if (!isset($found_keys[$k])) $found_keys[$k] = 1;
            }
        }

        $ret = array();
        foreach ($found_keys as $k=>$v) $ret[] = $this->dom->nodes[$k];
        if ($idx !== null) { if ($idx < 0) $idx = count($ret) + $idx; return (isset($ret[$idx])) ? $ret[$idx] : null; }
        return $ret;
    }

    protected function seek($selector, &$ret) {
        list($tag, $key, $val) = $selector;
        $end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
        $start = $this->_[HDOM_INFO_BEGIN] + 1;

        for ($i=$start; $i<$end; ++$i) {
            $node = $this->dom->nodes[$i];
            $pass = true;
            if ($tag && $tag !== $node->tag && $tag !== '*') $pass = false;
            if ($pass && $key) {
                if (!isset($node->attr[$key])) $pass = false;
                elseif ($val && $node->attr[$key] !== $val && strpos($node->attr[$key], $val) === false) $pass = false;
            }
            if ($pass) $ret[$node->_[HDOM_INFO_BEGIN]] = 1;
        }
    }

    protected function parse_selector($selector_string) {
        $pattern = '/([\w\-:\*]+)?(?:\.([\w\-]+))?(?:#([\w\-]+))?/is';
        preg_match_all($pattern, trim($selector_string), $matches, PREG_SET_ORDER);
        $selectors = array();
        foreach ($matches as $m) {
            if (empty($m[0])) continue;
            $tag = !empty($m[1]) ? $m[1] : '*';
            $key = null; $val = null;
            if (!empty($m[2])) { $key = 'class'; $val = $m[2]; }
            if (!empty($m[3])) { $key = 'id'; $val = $m[3]; }
            $selectors[] = array($tag, $key, $val);
        }
        return $selectors;
    }

    function __get($name) {
        if (isset($this->attr[$name])) return $this->attr[$name];
        switch ($name) {
            case 'outertext': return $this->outertext();
            case 'innertext': return $this->innertext();
            case 'plaintext': return $this->text();
        }
        return null;
    }

    function text() {
        if ($this->nodetype === HDOM_TYPE_TEXT) return $this->_[HDOM_INFO_TEXT];
        $ret = '';
        foreach ($this->nodes as $n) $ret .= $n->text();
        return $ret;
    }

    function innertext() {
        $ret = '';
        foreach ($this->nodes as $n) $ret .= $n->outertext();
        return $ret;
    }

    function outertext() {
        if ($this->nodetype === HDOM_TYPE_TEXT) return $this->_[HDOM_INFO_TEXT];
        $ret = '';
        $ret .= '<' . $this->tag;
        foreach ($this->attr as $k=>$v) { $ret .= ' ' . $k . '="' . $v . '"'; }
        $ret .= '>';
        foreach ($this->nodes as $n) $ret .= $n->outertext();
        if (!in_array($this->tag, array('img', 'br', 'input', 'hr'))) { $ret .= '</' . $this->tag . '>'; }
        return $ret;
    }
}
class simple_html_dom {
    public $root = null;
    public $nodes = array();
    protected $pos;
    protected $char;
    protected $size;
    protected $doc;

    function load($str) {
        $this->doc = $str;
        $this->size = strlen($str);
        $this->pos = 0;
        $this->char = ($this->size > 0) ? $this->doc[0] : null;
        $this->root = new simple_html_dom_node($this);
        $this->root->nodetype = HDOM_TYPE_ROOT;
        $this->root->tag = 'root';
        $this->root->_[HDOM_INFO_BEGIN] = 0;
        $this->parse();
        $this->root->_[HDOM_INFO_END] = count($this->nodes);
    }

    protected function parse() {
        while (true) {
            if ($this->char === null) break;
            if ($this->char === '<') { $this->read_tag(); } else { $this->read_text(); }
        }
    }

    protected function read_text() {
        $pos = $this->pos;
        while ($this->char !== '<' && $this->char !== null) { $this->move_next(); }
        $text = substr($this->doc, $pos, $this->pos - $pos);
        $node = new simple_html_dom_node($this);
        $node->nodetype = HDOM_TYPE_TEXT;
        $node->_[HDOM_INFO_TEXT] = $text;
    }

    protected function read_tag() {
        $this->move_next();
        if ($this->char === '/') {
            $this->move_next();
            while ($this->char !== '>' && $this->char !== null) $this->move_next();
            $this->move_next();
            return;
        }
        $pos = $this->pos;
        while ($this->char !== '>' && !ctype_space($this->char) && $this->char !== null) $this->move_next();
        $tag = substr($this->doc, $pos, $this->pos - $pos);

        $node = new simple_html_dom_node($this);
        $node->nodetype = HDOM_TYPE_ELEMENT;
        $node->tag = strtolower($tag);
        $node->_[HDOM_INFO_BEGIN] = count($this->nodes) - 1;

        while ($this->char !== '>' && $this->char !== null) {
            if (ctype_space($this->char)) { $this->move_next(); continue; }
            $pos = $this->pos;
            while ($this->char !== '=' && !ctype_space($this->char) && $this->char !== '>' && $this->char !== null) $this->move_next();
            $attr_name = substr($this->doc, $pos, $this->pos - $pos);
            if (empty($attr_name)) { $this->move_next(); continue; }

            while (ctype_space($this->char)) $this->move_next();
            if ($this->char === '=') {
                $this->move_next();
                while (ctype_space($this->char)) $this->move_next();
                if ($this->char === '"' || $this->char === "'") {
                    $quote = $this->char; $this->move_next(); $pos = $this->pos;
                    while ($this->char !== $quote && $this->char !== null) $this->move_next();
                    $attr_val = substr($this->doc, $pos, $this->pos - $pos); $this->move_next();
                } else {
                    $pos = $this->pos;
                    while (!ctype_space($this->char) && $this->char !== '>' && $this->char !== null) $this->move_next();
                    $attr_val = substr($this->doc, $pos, $this->pos - $pos);
                }
                $node->attr[strtolower($attr_name)] = $attr_val;
            }
        }
        $this->move_next();
    }

    protected function move_next() {
        if ($this->pos < $this->size) {
            $this->pos++;
            $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null;
        }
    }

    function clear() { foreach ($this->nodes as $n) $n->clear(); $this->nodes = array(); $this->root = null; }
}
