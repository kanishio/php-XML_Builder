<?php
/**
 * XML_Builder_Arrayに限定したテスト
 *
 */

//引数付きのシリアライザのテスト
function serializer_dummy($data, $offset)
{
    $data = $data + $offset;
    return json_encode($data);
}

class XMLBuilderArrayTest extends PHPUnit_Framework_TestCase
{
    function testRender() {
        $builder = xml_builder(array(
            'class' => 'array',
            'serializer' => 'XML_Builder::json'
        ));
        $builder->root->hogehoge_->_;

        self::assertEquals('{"hogehoge":null}', $builder->_render());

        $builder = xml_builder(array(
            'class' => 'array',
            'serializer' => array('serializer_dummy', array('additional'=>'hoge')),
        ));
        $builder->root->hogehoge_->_;

        self::assertEquals('{"root":{"hogehoge":null},"additional":"hoge"}', $builder->_render());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testRender2() {
        $builder = xml_builder(array(
            'class' => 'array',
            'serializer' => 'uso800',
        ));

        $builder->root->hoge_->_->_render();
    }

    function testRaw() {
        $builder = xml_builder(array(
            'class' => 'array',
            'serializer' => 'XML_Builder::json',
        ));

        $builder->root->_raw('<hoge>fuga</hoge>')->_;

        self::assertEquals('"<hoge>fuga<\/hoge>"', (string)$builder);
    }

    function testMarkArray() {
        $builder = xml_builder(array(
            'class' => 'array',
            'serializer' => 'XML_Builder::json',
        ));

        $builder
            ->root(array('foo'=>'foo'))
                ->_markArray('foo')
            ->_;
        self::assertEquals('{"@foo":"foo","foo":[]}', (string)$builder);

        $builder = xml_builder(array(
            'class' => 'array',
            'serializer' => 'XML_Builder::json',
        ));

        $builder
            ->root
                ->hoge_
                ->fuga_
                ->_markArray('foo')
            ->_;
        self::assertEquals('{"hoge":null,"fuga":null,"foo":[]}', (string)$builder);

        $builder = xml_builder(array(
            'class' => 'array',
            'serializer' => 'XML_Builder::json',
        ));

        $builder
            ->root
                ->_markArray('foo')
                ->foo_
            ->_;
        self::assertEquals('{"foo":[null]}', (string)$builder);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testMarkArray2() {
        //mixed contentはmarkArrayにできない
        $builder = xml_builder(array(
            'class' => 'array',
            'serializer' => 'XML_Builder::json',
        ));

        $builder
            ->root
                ->_text('str')
                ->foo_
                ->_markArray('foo')
            ->_;
    }
}