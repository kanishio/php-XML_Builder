<?php
/**
 * XML_Builder_Array
 *
 * XML_Builderの具象クラス・配列版
 *
 * @author    Hiraku NAKANO <hiraku@tojiru.net>
 * @license   https://github.com/hirak/php-XML_Builder/blob/master/LICENSE MIT License
 * @link      https://packagist.org/packages/hiraku/xml_builder
 */

if (!class_exists('XML_Builder_Abstract', false)) {
    require_once dirname(__FILE__).'/Abstract.php';
}
// polyfill for version < PHP5.4
if (!interface_exists('JsonSerializable', false)) {
    interface JsonSerializable
    {
        function jsonSerialize();
    }
}

/**
 * XML_Builder_Array
 *
 * XML_Builderと見せかけてPHPの配列を作るだけのクラス。
 * 同じインターフェースで出力を差し替えたい場合にどうぞ。
 *
 * @author    Hiraku NAKANO <hiraku@tojiru.net>
 * @license   https://github.com/hirak/php-XML_Builder/blob/master/LICENSE.md MIT License
 * @link      https://packagist.org/packages/hiraku/xml_builder
 */
class XML_Builder_Array extends XML_Builder_Abstract implements JsonSerializable
{
    public $xmlArray, $xmlCurrentElem, $xmlParent;

    //高速化のため、タイプ判定をキャッシュして使いまわす。
    private $_type = self::TYPE_NULL, $_lastKey = null;
    protected $_serializer, $_removeRootNode = true;

    const TYPE_NULL       = 0 //null
        , TYPE_STRING     = 1 //"str"
        , TYPE_D_STRING   = 2 //"$":"str"
        , TYPE_D_ARRAY    = 3 //"$":[]
        , TYPE_ATTR_ARRAY = 4 //"@attr":"attr",...
        , TYPE_ARRAY      = 5 //"hoge":"fuga",...
        ;

    /**
     * constructor
     *
     * @param array $array オプションもしくは大元の配列
     * @param mixed $elem  現在編集中の配列
     * @param mixed $parent 現在編集中の配列
     *
     * @return null
     */
    function __construct(array $option)
    {
        if (!empty($option['serializer'])) {
            $this->_serializer = $option['serializer'];
        }
        if (isset($option['removeRootNode'])) {
            $this->_removeRootNode = (bool)$option['removeRootNode'];
        }
        $newelem = null;
        $this->xmlArray =& $newelem;
        $this->xmlCurrentElem =& $newelem;
    }

    /**
     * 現在の文脈を終了して親の編集に戻る
     *
     * @return XML_Builder_Array
     */
    function xmlEnd()
    {
        return $this->xmlParent;
    }

    /**
     * 属性を追加
     *
     * @param array $attr 属性名=>値 の配列
     *
     * @return XML_Builder_Array
     */
    function xmlAttr(array $attr=array())
    {
        if (empty($attr)) {
            return $this;
        }
        $elem =& $this->xmlCurrentElem;
        switch ($this->_type) {
        case self::TYPE_NULL:
            $elem = array();
            $this->_type = self::TYPE_ATTR_ARRAY;
            break;
        case self::TYPE_STRING:
            $string = $elem;
            $elem = array('$'=>$string);
            $this->_type = self::TYPE_D_STRING;
            break;
        }
        foreach ($attr as $label => $value) {
            $value = $this->xmlFilter($value);
            $elem["@$label"] = $value;
        }
        $this->_lastKey = "@$label";
        return $this;
    }

    /**
     * テキストノードの追加
     *
     * @param string $str 追加したい文字列
     *
     * @return XML_Builder_Array
     */
    function xmlText($str)
    {
        $str = $this->xmlFilter($str);
        $elem =& $this->xmlCurrentElem;
        switch ($this->_type) {
        case self::TYPE_NULL:
            $elem = $str;
            $this->_type = self::TYPE_STRING;
            break;
        case self::TYPE_STRING:
            $elem .= $str;
            break;
        case self::TYPE_D_ARRAY:
            $elem['$'][] = $str;
            break;
        case self::TYPE_D_STRING:
            $elem['$'] .= $str;
            break;
        case self::TYPE_ATTR_ARRAY:
            $elem['$'] = $str;
            $this->_type = self::TYPE_D_STRING;
            break;
        case self::TYPE_ARRAY:
            $darray = array();
            foreach ($elem as $key => $val) {
                if ($key[0] !== '@') {
                    $darray[] = array($key=>$val);
                    unset($elem[$key]);
                }
            }
            $darray[] = $str;
            $elem['$'] = $darray;
            $this->_type = self::TYPE_D_ARRAY;
            break;
        }
        return $this;
    }

    /**
     * テキストノードの追加と同義
     */
    function xmlCdata($str)
    {
        $this->xmlText($str);
        return $this;
    }

    /**
     * ignored
     *
     * @return $this
     */
    function xmlComment($str)
    {
        return $this;
    }

    /**
     * ignored
     *
     * @return $this
     */
    function xmlPi($target, $data)
    {
        return $this;
    }

    function __toString()
    {
        return $this->xmlRender();
    }

    function xmlRender()
    {
        if ($this->_removeRootNode) {
            $array = current($this->xmlArray);
        } else {
            $array = $this->xmlArray;
        }
        if (empty($this->_serializer)) {
            return var_export($array, true);

        } elseif (is_callable($this->_serializer)) {
            return call_user_func($this->_serializer, $array);

        }

        throw new InvalidArgumentException('invalid serializer');
    }

    function xmlEcho()
    {
        echo $this->xmlRender();
        return $this;
    }

    /**
     * この後配列を作る
     */
    function xmlMarkArray($name)
    {
        $dom =& $this->xmlArray;
        $elem =& $this->xmlCurrentElem;

        switch ($this->_type) {
        case self::TYPE_NULL:
            $elem = array($name => array());
            $this->_type = self::TYPE_ARRAY;
            $this->_lastKey = $name;
            break;
        case self::TYPE_ATTR_ARRAY:
            $elem[$name] = array();
            $this->_type = self::TYPE_ARRAY;
            $this->_lastKey = $name;
            break;
        case self::TYPE_ARRAY:
            if ($name === $this->_lastKey) {
                //配列とみなせる場合
                if (is_array($elem[$name]) && (count($elem[$name]) === 0 || key($elem[$name]) === 0)) {
                    //何もしない

                //配列以外の場合
                } else {
                    //配列に直す
                    $original = $elem[$name];
                    $elem[$name] = array($original);
                }
            } elseif (array_key_exists($name, $elem)) {
                //何もしない
            } else {
                $elem[$name] = array();
                $this->_lastKey = $name;
            }
            break;
        default:
            throw new InvalidArgumentException('xmlMarkArray()');
        }

        return $this;
    }

    function xmlElem($name)
    {
        $dom =& $this->xmlArray;
        $elem =& $this->xmlCurrentElem;

        $newelem = null;

        switch ($this->_type) {
        case self::TYPE_NULL:
            $elem = array($name => &$newelem);
            $this->_type = self::TYPE_ARRAY;
            $this->_lastKey = $name;
            break;
        case self::TYPE_STRING:
            $str = $elem;
            $elem = array('$' => array($str, array($name => &$newelem)));
            $this->_type = self::TYPE_D_ARRAY;
            $this->_lastKey = '$';
            break;
        case self::TYPE_D_STRING:
            $str = $elem['$'];
            $elem['$'] = array($str, array($name => &$newelem));
            $this->_type = self::TYPE_D_ARRAY;
            $this->_lastKey = '$';
            break;
        case self::TYPE_ATTR_ARRAY:
            $elem[$name] =& $newelem;
            $this->_type = self::TYPE_ARRAY;
            $this->_lastKey = $name;
            break;
        case self::TYPE_ARRAY:
            if ($name === $this->_lastKey) {
                //数値配列とみなす
                if (is_array($elem[$name]) && (count($elem[$name]) === 0 || key($elem[$name]) === 0)) {
                    $elem[$name][] =& $newelem;
                } else {
                    $original = $elem[$name];
                    $elem[$name] = array($original, &$newelem);
                }
            } elseif (array_key_exists($name, $elem)) {
                //TYPE_D_ARRAYへ変更する
                $darray = array();
                foreach ($elem as $key => $val) {
                    if ($key[0] !== '@') {
                        //hoge:[1,2,3]は展開する
                        if (is_array($val) && key($val) === 0) {
                            foreach ($val as $v) {
                                $darray[] = array($key => $v);
                            }
                        } else {
                            $darray[] = array($key=>$val);
                        }
                        unset($elem[$key]);
                    }
                }
                $darray[] = array($name => &$newelem);
                $elem['$'] = $darray;
                $this->_type = self::TYPE_D_ARRAY;
                $this->_lastKey = '$';
            } else {
                $elem[$name] =& $newelem;
                $this->_lastKey = $name;
            }
            break;
        case self::TYPE_D_ARRAY:
            $elem['$'][] = array($name => &$newelem);
            break;
        }

        $next = clone $this;
        $next->xmlArray =& $dom;
        $next->xmlCurrentElem =& $newelem;
        $next->xmlParent =& $this;
        $next->_type = self::TYPE_NULL;
        $next->_lastKey = null;

        return $next;
    }

    function xmlRaw($xml)
    {
        return $this->xmlText($xml);
    }

    /**
     * for PHP5.4(json_encode)
     *
     * @return array
     */
    function jsonSerialize()
    {
        return $this->xmlArray;
    }

    /**
     * for Zend_Json
     *
     * @return string JSON
     */
    function toJson()
    {
        return json_encode($this->xmlArray);
    }
}
