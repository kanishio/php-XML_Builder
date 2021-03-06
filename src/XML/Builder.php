<?php
/**
 * XML_Builder
 *
 * @author    Hiraku NAKANO <hiraku@tojiru.net>
 * @license   https://github.com/hirak/php-XML_Builder/blob/master/LICENSE.md MIT License
 * @link      https://packagist.org/packages/hiraku/xml_builder
 */
if (!class_exists('XML_Builder_Interface', false)) {
    require_once dirname(__FILE__).'/Builder/Interface.php';
}
if (!class_exists('XML_Builder_Abstract', false)) {
    require_once dirname(__FILE__).'/Builder/Abstract.php';
}
if (!class_exists('XML_Builder_DOM', false)) {
    require_once dirname(__FILE__).'/Builder/DOM.php';
}
if (!class_exists('XML_Builder_XMLWriter', false)) {
    require_once dirname(__FILE__).'/Builder/XMLWriter.php';
}
if (!class_exists('XML_Builder_Array', false)) {
    require_once dirname(__FILE__).'/Builder/Array.php';
}
if (!class_exists('XML_Builder_Lint', false)) {
    require_once dirname(__FILE__).'/Builder/Lint.php';
}
if (!class_exists('XML_Builder_Json', false)) {
    require_once dirname(__FILE__).'/Builder/Json.php';
}
if (!class_exists('XML_Builder_Jsonp', false)) {
    require_once dirname(__FILE__).'/Builder/Jsonp.php';
}
if (!class_exists('XML_Builder_Serialize', false)) {
    require_once dirname(__FILE__).'/Builder/Serialize.php';
}
if (!class_exists('XML_Builder_Atom', false)) {
    require_once dirname(__FILE__).'/Builder/Atom.php';
}

/**
 * XML_Builder
 *
 * utility & constants
 *
 * @author    Hiraku NAKANO <hiraku@tojiru.net>
 * @license   https://github.com/hirak/php-XML_Builder/blob/master/LICENSE.md MIT License
 * @link      https://packagist.org/packages/hiraku/xml_builder
 */
abstract class XML_Builder
{
    static $sigil = '_';

    static $HTML4_STRICT = array(
        'html',
        '-//W3C//DTD HTML 4.01//EN',
        'http://www.w3.org/TR/html4/strict.dtd',
    );
    static $HTML4_TRANSITIONAL = array(
        'html',
        '-//W3C//DTD HTML 4.01 Transitional//EN',
        'http://www.w3.org/TR/html4/loose.dtd',
    );
    static $HTML4_FRAMESET = array(
        'html',
        '-//W3C//DTD HTML 4.01 Frameset//EN',
        'http://www.w3.org/TR/html4/frameset.dtd',
    );
    static $HTML5 = array('html', null, null);
    static $XHTML1_STRICT = array(
        'html',
        '-//W3C//DTD XHTML 1.0 Strict//EN',
        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd',
    );
    static $XHTML1_TRANSITIONAL = array(
        'html',
        '-//W3C//DTD XHTML 1.0 Transitional//EN',
        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd',
    );
    static $XHTML1_FRAMESET = array(
        'html',
        '-//W3C//DTD XHTML 1.0 Frameset//EN',
        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd'
    );
    static $XHTML11 = array(
        'html',
        '-//W3C//DTD XHTML 1.1//EN',
        'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'
    );

    const
        NS_XHTML = 'http://www.w3.org/1999/xhtml'
      , NS_XML = 'http://www.w3.org/XML/1998/namespace'
      , NS_ATOM = 'http://www.w3.org/2005/Atom'
      , NS_ATOM_PUB = 'http://www.w3.org/2007/app'
      , NS_ATOM_THREAD = 'http://purl.org/syndication/thread/1.0'
      , NS_ATOM_HISTORY = 'http://purl.org/syndication/history/1.0'
      , NS_OPENSEARCH = 'http://a9.com/-/spec/opensearch/1.1/'
      , NS_GDATA = 'http://schemas.google.com/g/2005'
      , NS_RSS_10 = 'http://purl.org/rss/1.0/'
      , NS_DC = 'http://purl.org/dc/elements/1.1/'
      , NS_XSLT = 'http://www.w3.org/1999/XSL/Transform'
      , NS_RELAXNG = 'http://relaxng.org/ns/structure/1.0'
      , NS_XSD = 'http://www.w3.org/2001/XMLSchema'
      , NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance'
    ;

    /**
     * create instance
     *
     * @param array $option parameters for XML_Builder_Interface
     *
     * @return XML_Builder_Interface
     */
    static function factory(array $option=array())
    {
        $option += array(
            'version' => '1.0',
            'encoding' => 'UTF-8',
            'formatOutput' => true,
            'doctype' => null,
            'filter' => null,
            'class' => 'XML_Builder_DOM'
        );

        $classmap = array(
            'dom' => 'XML_Builder_DOM',
            'xmlwriter' => 'XML_Builder_XMLWriter',
            'array' => 'XML_Builder_Array',
        );
        if (isset($classmap[$option['class']])) {
            $option['class'] = $classmap[$option['class']];
        }
        $classname = 'XML_Builder_' . ucfirst($option['class']);
        if (class_exists($classname)) {
            $option['class'] = $classname;
        }

        $class = $option['class'];
        $builder = new $class($option);
        if (is_callable($option['filter'])) {
            $builder->xmlFilter = $option['filter'];
        }
        return $builder;
    }

    /**
     * convert "XML string" to Array
     *
     */
    static function xmlToArray($xmlString, $schema=array()) {

        if (is_string($schema)) {
            $schema = parse_ini_file($schema, true);
        }
        if ($schema) {
            return self::_xmlToArrayWithSchema($xmlString, $schema);
        }

        $builder = self::factory(array('class'=>'XML_Builder_Array'));
        $cursor = new XMLReader;
        $cursor->XML($xmlString, null, LIBXML_NOBLANKS);

        while ($cursor->read()) switch ($cursor->nodeType) {
            case XMLReader::ELEMENT:
                $builder = $builder->xmlElem($cursor->name);
                if ($cursor->hasAttributes) {
                    $attr = array();
                    $cursor->moveToFirstAttribute();
                    do {
                        $attr[$cursor->name] = $cursor->value;
                    } while ($cursor->moveToNextAttribute());

                    $builder->xmlAttr($attr);
                    $cursor->moveToElement();
                }
                if ($cursor->isEmptyElement) {
                    $builder = $builder->xmlEnd();
                }
                break;

            case XMLReader::END_ELEMENT:
                $builder = $builder->xmlEnd();
                break;

            case XMLReader::TEXT:
            case XMLReader::CDATA:
                $builder->xmlText($cursor->value);
                break;
        }

        return $builder->xmlArray;
    }

    /**
     * SchemaでBoxingしながらArray化する
     *
     */
    private static function _xmlToArrayWithSchema($xmlString, array $schema) {
        $builder = self::factory(array('class'=>'XML_Builder_Array'));
        $cursor = new XMLReader;
        $cursor->XML($xmlString, null, LIBXML_NOBLANKS);

        $stack = array();
        $l = -1;

        while ($cursor->read()) {
            switch ($cursor->nodeType) {
            case XMLReader::ELEMENT:
                $stack[] = array($cursor->namespaceURI, $cursor->localName);
                ++$l;

                $builder = $builder->xmlElem($cursor->name);
                if ($cursor->hasAttributes) {
                    $tagName = $cursor->localName;
                    $tagNs = $cursor->namespaceURI;
                    $attr = array();
                    $cursor->moveToFirstAttribute();
                    do {
                        if (isset($schema[$cursor->namespaceURI]['@'.$cursor->localName])) {
                            $s = $schema[$cursor->namespaceURI]['@'.$cursor->localName];
                        } elseif (isset($schema[$tagNs][$tagName.'@'.$cursor->localName])) {
                            $s = $schema[$tagNs][$tagName.'@'.$cursor->localName];
                        } elseif (empty($tagNs) && isset($schema[$tagName.'@'.$cursor->localName])) {
                            $s = $schema[$tagName.'@'.$cursor->localName];
                        } else {
                            $s = null;
                        }

                        $val = self::_schemaCast($s, $cursor->value);
                        $attr[$cursor->name] = $val;
                    } while ($cursor->moveToNextAttribute());

                    $builder->xmlAttr($attr);
                    $cursor->moveToElement();
                }
                if ($cursor->isEmptyElement) {
                    $s = self::_schemaFind($schema, $stack, $l);

                    if ($s) {
                        if ($s === 'string' || $s === 'str') {
                            $builder->xmlText('');
                        } elseif (preg_match('/^complex (.*)/', $s, $m)) {
                            $complex = str_replace(' ', '', $m[1]);
                            $complex = explode(',', $complex);
                            foreach ($complex as $c) {
                                if (preg_match('/^(.*)\[\]$/', $c, $m)) {
                                    $builder->xmlMarkArray($m[1]);
                                } elseif (! array_key_exists($c, (array)$builder->xmlCurrentElem)) {
                                    $builder->xmlElem($c)->xmlEnd();
                                }
                            }
                        }
                    }
                    $builder = $builder->xmlEnd();
                    array_pop($stack);
                    --$l;
                }
                break;

            case XMLReader::END_ELEMENT:
                $s = self::_schemaFind($schema, $stack, $l);

                if ($s) {
                    if ($s === 'string' || $s === 'str') {
                        $builder->xmlText('');
                    } elseif (preg_match('/^complex (.*)/', $s, $m)) {
                        $complex = str_replace(' ', '', $m[1]);
                        $complex = explode(',', $complex);
                        foreach ($complex as $c) {
                            if (preg_match('/^(.*)\[\]$/', $c, $m)) {
                                $builder->xmlMarkArray($m[1]);
                            } elseif (! array_key_exists($c, (array)$builder->xmlCurrentElem)) {
                                $builder->xmlElem($c)->xmlEnd();
                            }
                        }
                    }
                }
                $builder = $builder->xmlEnd();
                array_pop($stack);
                --$l;
                break;

            case XMLReader::TEXT:
            case XMLReader::CDATA:
                $s = self::_schemaFind($schema, $stack, $l);
                $val = self::_schemaCast($s, $cursor->value);
                $builder->xmlText($val);
                break;
            }
        }

        return $builder->xmlArray;
    }

    private static function _schemaCast($s, $value) {
        switch ($s) {
            case 'integer': case 'int':
                return (int) $value;
            case 'double': case 'float': case 'real':
                return (float) $value;
            case 'boolean': case 'bool':
                return ($value == 'true' || $value == '1');
            case 'dateTime':
                return new DateTime($value);
            case 'string': case 'text':
                return (string)$value;
            default:
                return $value;
        }
    }

    private static function _schemaFind($schema, $stack, $l) {
        if (isset($schema[$stack[$l][0]], $schema[$stack[$l][0]][$stack[$l][1]])) {
            return $schema[$stack[$l][0]][$stack[$l][1]];
        } elseif (isset($schema[$stack[$l][1]])) {
            return $schema[$stack[$l][1]];
        } else {
            return null;
        }
    }

    /**
     * convert "DOMDocument" to Array
     *
     */
    static function domToArray(DOMNode $node, $builder='array') {
        static $nsList=array();

        if ($node instanceof DOMDocument) {
            $xpath = new DOMXPath($node);
            $namespaces = $xpath->query('namespace::*');
            $nsList = array();
            foreach ($namespaces as $n) {
                $nsList[] = $n->nodeName;
            }

            if (is_string($builder) || !($builder instanceof XML_Builder_Interface)) {
                $builder = self::factory(array('class'=>$builder));
            }

            $result = self::domToArray($node->documentElement, $builder);
            return $result->xmlArray;
        }

        $b = $builder->xmlElem($node->nodeName);

        $array = array();
        //名前空間を復元
        foreach ($nsList as $ns) {
            $xmlns = $node->getAttribute($ns);
            if ('' !== $xmlns) {
                $array[$ns] = $xmlns;
            }
        }
        //属性がある場合
        if ($node->hasAttributes()) {
            $attr = $node->attributes;
            for ($i=0, $len=$attr->length; $i<$len; $i++) {
                $currentAttr = $attr->item($i);
                $array[$currentAttr->nodeName] = $currentAttr->nodeValue;
            }
        }
        if (!empty($array)) {
            $b->xmlAttr($array);
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $cn) {
                switch ($cn->nodeType) {
                    case XML_TEXT_NODE:
                        $b->xmlText($cn->nodeValue);
                        break;
                    case XML_CDATA_SECTION_NODE:
                        $b->xmlCdata($cn->nodeValue);
                        break;
                    case XML_COMMENT_NODE:
                        $b->xmlComment($cn->data);
                        break;
                    case XML_ELEMENT_NODE:
                        $b = self::domToArray($cn, $b);
                }
            }
        }

        return $b->xmlEnd();
    }

    //without root node
    static function json($data) {
        return json_encode(current($data));
    }

    //without root node && prettyprint
    static function jsonDebug($data) {
        return json_encode(current($data), 448);
    }

    //without root
    static function serialize($data) {
        return serialize(current($data));
    }

    //without root
    static function yaml($data) {
        return yaml_emit(current($data));
    }
}

function XML_Builder(array $option=array()) {
    return XML_Builder::factory($option);
}
