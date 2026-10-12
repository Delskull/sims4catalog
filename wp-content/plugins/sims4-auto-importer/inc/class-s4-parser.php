<?php
/**
 * Website: http://sourceforge.net
 * Легендарный одиночный парсер HTML DOM для чистой работы без Composer.
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
define('HDOM_INFO_DISPLAY', 7);
define('HDOM_INFO_BLOCK', 8);

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
    public $tag_start = 0;
    private $dom = null;

    function __construct($dom) {
        $this->dom = $dom;
        $dom->nodes[] = $this;
    }

    function __toString() { return $this->outertext(); }

    function clear() {
        $this->dom = null;
        $this->parent = null;
        $this->children = array();
        $this->nodes = array();
    }

    function parent($parent=null) {
        if ($parent !== null) {
            $this->parent = $parent;
            $this->parent->children[] = $this;
        }
        return $this->parent;
    }

    function has_child() { return !empty($this->children); }

    function children($idx=-1) {
        if ($idx === -1) return $this->children;
        if (isset($this->children[$idx])) return $this->children[$idx];
        return null;
    }

    function first_child() { if (count($this->children) > 0) return $this->children[0]; return null; }
    function last_child() { if (($count = count($this->children)) > 0) return $this->children[$count - 1]; return null; }

    function next_sibling() {
        if ($this->parent === null) return null;
        $idx = 0;
        $count = count($this->parent->children);
        while ($idx < $count && $this->parent->children[$idx] !== $this) { ++$idx; }
        if (++$idx >= $count) return null;
        return $this->parent->children[$idx];
    }

    function prev_sibling() {
        if ($this->parent === null) return null;
        $idx = 0;
        $count = count($this->parent->children);
        while ($idx < $count && $this->parent->children[$idx] !== $this) { ++$idx; }
        if (--$idx < 0) return null;
        return $this->parent->children[$idx];
    }

    function find($selector, $idx=null, $lowercase=false) {
        $selectors = $this->parse_selector($selector);
        if (($count = count($selectors)) === 0) return array();
        $found_keys = array();
        for ($c=0; $c<$count; ++$c) {
            if (($lev = count($selectors[$c])) === 0) return array();
            $head = array($this->_[HDOM_INFO_BEGIN] => 1);
            for ($l=0; $l<$lev; ++$l) {
                $ret = array();
                foreach ($head as $k=>$v) {
                    $n = ($k === 0) ? $this->dom->root : $this->dom->nodes[$k];
                    $n->seek($selectors[$c][$l], $ret, $lowercase);
                }
                $head = $ret;
            }
            foreach ($head as $k=>$v) {
                if (!isset($found_keys[$k])) $found_keys[$k] = 1;
            }
        }
        $ret = array();
        foreach ($found_keys as $k=>$v) $ret[] = $this->dom->nodes[$k];
        if ($idx !== null) {
            if ($idx < 0) $idx = count($ret) + $idx;
            return (isset($ret[$idx])) ? $ret[$idx] : null;
        }
        return $ret;
    }

    protected function seek($selector, &$ret, $lowercase=false) {
        list($tag, $key, $val, $exp, $no_key) = $selector;
        $end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
        if ($end == 0) {
            $parent = $this->parent;
            while ($parent !== null && !isset($parent->_[HDOM_INFO_END])) { $end -= 1; $parent = $parent->parent; }
            if ($parent !== null) $end += $parent->_[HDOM_INFO_END];
        }
        $start = $this->_[HDOM_INFO_BEGIN] + 1;
        for ($i=$start; $i<$end; ++$i) {
            if (!isset($this->dom->nodes[$i])) continue;
            $node = $this->dom->nodes[$i];
            $pass = true;
            if ($tag && $tag !== '*' && strcasecmp($tag, $node->tag) !== 0) $pass = false;
            if ($pass && $key) {
                if ($no_key) {
                    if (isset($node->attr[$key])) $pass = false;
                } else {
                    if (!isset($node->attr[$key])) $pass = false;
                    else {
                        $check = $this->match($exp, $val, $node->attr[$key]);
                        if (!$check && strcasecmp($key, 'class') === 0) {
                            foreach (explode(' ', $node->attr[$key]) as $k) {
                                if (!empty($k)) {
                                    $check = $this->match($exp, $val, $k);
                                    if ($check) break;
                                }
                            }
                        }
                        if (!$check) $pass = false;
                    }
                }
            }
            if ($pass) $ret[$node->_[HDOM_INFO_BEGIN]] = 1;
            unset($node);
        }
    }

    protected function match($exp, $pattern, $value) {
        switch ($exp) {
            case '=':  return ($value === $pattern);
            case '!=': return ($value !== $pattern);
            case '^=': return preg_match("/^" . preg_quote($pattern, '/') . "/", $value) ? true : false;
            case '$=': return preg_match("/" . preg_quote($pattern, '/') . "$/", $value) ? true : false;
            case '*=': return preg_match("/" . preg_quote($pattern, '/') . "/", $value) ? true : false;
        }
        return false;
    }

    protected function parse_selector($selector_string) {
        // Поддержка: tag, .class, #id, tag.class, tag#id, tag[attr], tag[attr=val], tag[attr^=val] и т.д.
        $pattern = '/([\w\-:\*]+)?'
            . '(?:\[@?([\w\-]+)(?:([!^$*~]?=)[\'"]?(.*?)[\'"]?)?\])?'
            . '(?:([\.#])([\w\-]+))?/is';
        preg_match_all($pattern, trim($selector_string), $matches, PREG_SET_ORDER);
        $selectors = array();
        foreach ($matches as $m) {
            if (empty($m[0])) continue;
            $tag = (!empty($m[1])) ? $m[1] : '*';
            $key = null; $val = null; $exp = null; $no_key = false;
            if (!empty($m[2])) { // [attr...]
                $key = $m[2];
                if (isset($m[3]) && $m[3] !== '') {
                    $exp = $m[3];
                    $val = isset($m[4]) ? $m[4] : '';
                } else {
                    $no_key = true;
                }
            } elseif (!empty($m[5]) && !empty($m[6])) {
                if ($m[5] === '.') { $key = 'class'; $val = $m[6]; $exp = '='; }
                if ($m[5] === '#') { $key = 'id';    $val = $m[6]; $exp = '='; }
            }
            $selectors[] = array($tag, $key, $val, $exp, $no_key);
        }
        if (empty($selectors)) {
            $selectors[] = array(trim($selector_string), null, null, null, false);
        }
        return array($selectors);
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
        if ($this->nodetype === HDOM_TYPE_TEXT && isset($this->_[HDOM_INFO_TEXT])) {
            return $this->_[HDOM_INFO_TEXT];
        }
        $ret = '';
        foreach ($this->children as $n) $ret .= $n->text();
        return $ret;
    }

    function innertext() {
        if ($this->nodetype === HDOM_TYPE_TEXT && isset($this->_[HDOM_INFO_TEXT])) {
            return $this->_[HDOM_INFO_TEXT];
        }
        $ret = '';
        foreach ($this->children as $n) $ret .= $n->outertext();
        return $ret;
    }

    function outertext() {
        if ($this->nodetype === HDOM_TYPE_TEXT) {
            return isset($this->_[HDOM_INFO_TEXT]) ? $this->_[HDOM_INFO_TEXT] : '';
        }
        if ($this->nodetype !== HDOM_TYPE_ELEMENT) {
            $ret = '';
            foreach ($this->children as $n) $ret .= $n->outertext();
            return $ret;
        }
        $ret = '<' . $this->tag;
        foreach ($this->attr as $k=>$v) {
            if ($v === null || $v === '') $ret .= ' ' . $k;
            else $ret .= ' ' . $k . '="' . htmlspecialchars($v, ENT_QUOTES) . '"';
        }
        $ret .= '>';
        foreach ($this->children as $n) $ret .= $n->outertext();
        if (!in_array(strtolower($this->tag), array('img', 'br', 'input', 'hr', 'meta', 'link'))) {
            $ret .= '</' . $this->tag . '>';
        }
        return $ret;
    }
}

class simple_html_dom {
    public $root = null;
    public $nodes = array();
    protected $lowercase = true;
    protected $pos;
    protected $char;
    protected $size;
    protected $doc;
    protected $stack = array();

    function __construct($str=null, $lowercase=true, $forceTagsClosed=true, $target_charset='UTF-8', $stripRN=true, $defaultBRText="\r\n", $defaultSpanText=" ") {
        if ($str) $this->load($str);
    }

    function load($str) {
        $this->prepare($str);
        $this->root = new simple_html_dom_node($this);
        $this->root->nodetype = HDOM_TYPE_ROOT;
        $this->root->tag = 'root';
        $this->root->_[HDOM_INFO_BEGIN] = 0;
        $this->stack = array($this->root);
        $this->parse();
        $this->root->_[HDOM_INFO_END] = count($this->nodes);
    }

    protected function prepare($str) {
        $this->doc = $str;
        $this->size = strlen($str);
        $this->pos = 0;
        $this->char = ($this->size > 0) ? $this->doc[0] : null;
    }

    protected function parse() {
        while ($this->char !== null) {
            if ($this->char === '<') {
                $this->read_tag();
            } else {
                $this->read_text();
            }
        }
    }

    protected function read_text() {
        $pos = $this->pos;
        while ($this->char !== '<' && $this->char !== null) { $this->move_next(); }
        $text = substr($this->doc, $pos, $this->pos - $pos);
        if ($text === '') return;

        $node = new simple_html_dom_node($this);
        $node->nodetype = HDOM_TYPE_TEXT;
        $node->_[HDOM_INFO_TEXT] = $text;

        // Прикрепляем к текущему открытому элементу (верхушка стека)
        $parent = end($this->stack);
        $node->parent($parent);
    }

    protected function read_tag() {
        $this->move_next(); // skip '<'

        // Закрывающий тег
        if ($this->char === '/') {
            $this->move_next();
            $pos = $this->pos;
            while ($this->char !== '>' && $this->char !== null) $this->move_next();
            $tag = strtolower(trim(substr($this->doc, $pos, $this->pos - $pos)));
            $this->move_next();

            // Закрываем соответствующий элемент в стеке
            for ($i = count($this->stack) - 1; $i >= 1; $i--) {
                if (strcasecmp($this->stack[$i]->tag, $tag) === 0) {
                    $this->stack[$i]->_[HDOM_INFO_END] = count($this->nodes) - 1;
                    array_splice($this->stack, $i);
                    break;
                }
            }
            return;
        }

        // Имя тега
        $pos = $this->pos;
        while ($this->char !== '>' && !ctype_space($this->char) && $this->char !== '/' && $this->char !== null) {
            $this->move_next();
        }
        $tag = substr($this->doc, $pos, $this->pos - $pos);
        if ($tag === '') {
            // Просто '<' в тексте — пропускаем
            $this->move_next();
            return;
        }

        $node = new simple_html_dom_node($this);
        $node->nodetype = HDOM_TYPE_ELEMENT;
        $node->tag = strtolower($tag);
        $node->_[HDOM_INFO_BEGIN] = count($this->nodes) - 1;

        // Атрибуты
        while ($this->char !== '>' && $this->char !== '/' && $this->char !== null) {
            if (ctype_space($this->char)) { $this->move_next(); continue; }

            $pos = $this->pos;
            while ($this->char !== '=' && !ctype_space($this->char) && $this->char !== '>' && $this->char !== '/' && $this->char !== null) {
                $this->move_next();
            }
            $attr_name = substr($this->doc, $pos, $this->pos - $pos);
            if ($attr_name === '') { $this->move_next(); continue; }

            while (ctype_space($this->char)) $this->move_next();

            if ($this->char === '=') {
                $this->move_next();
                while (ctype_space($this->char)) $this->move_next();
                if ($this->char === '"' || $this->char === "'") {
                    $quote = $this->char; $this->move_next(); $pos = $this->pos;
                    while ($this->char !== $quote && $this->char !== null) $this->move_next();
                    $attr_val = substr($this->doc, $pos, $this->pos - $pos);
                    $this->move_next();
                } else {
                    $pos = $this->pos;
                    while (!ctype_space($this->char) && $this->char !== '>' && $this->char !== '/' && $this->char !== null) {
                        $this->move_next();
                    }
                    $attr_val = substr($this->doc, $pos, $this->pos - $pos);
                }
                $node->attr[strtolower($attr_name)] = $attr_val;
            } else {
                // Атрибут без значения (например, disabled)
                $node->attr[strtolower($attr_name)] = null;
            }
        }

        // Прикрепляем к текущему родителю
        $parent = end($this->stack);
        $node->parent($parent);

        $void = in_array($node->tag, array('img','br','input','hr','meta','link','area','base','col','embed','param','source','track','wbr'));

        if ($this->char === '/') {
            // самозакрывающийся <tag/>
            $this->move_next();
            if ($this->char === '>') $this->move_next();
            $node->_[HDOM_INFO_END] = count($this->nodes) - 1;
        } else {
            if ($this->char === '>') $this->move_next();
            if ($void) {
                $node->_[HDOM_INFO_END] = count($this->nodes) - 1;
            } else {
                // Открываем элемент — кладём в стек
                $this->stack[] = $node;
            }
        }
    }

    protected function move_next() {
        if ($this->pos < $this->size) {
            $this->pos++;
            $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null;
        } else {
            $this->char = null;
        }
    }

    function is_block($tag) { return in_array(strtolower($tag), array('p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6')); }

    function clear() {
        foreach ($this->nodes as $n) $n->clear();
        $this->nodes = array();
        $this->root = null;
        $this->stack = array();
    }
}