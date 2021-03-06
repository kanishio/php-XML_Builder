<?php
/**
 * テストの起動スクリプト
 * ひたすらtest*.phpとtest*.xmlの比較を行います。
 * PHPUnitが必要です。
 *
 */
class XML_BuilderTest extends PHPUnit_Framework_TestCase
{
    function testDOM() {
        $dir = dirname(__FILE__);
        $tests = glob($dir . '/test*.php');
        $xmls = glob($dir . '/test*.xml');
        $length = min(count($tests), count($xmls));

        for ($i=0; $i<$length; $i++) {
            $builder = xml_builder();

            $php = include $tests[$i];
            $php = (string)$php;
            self::assertXmlStringEqualsXmlFile($xmls[$i], $php, $tests[$i]);
        }
    }

    function testXMLWriter() {
        $dir = dirname(__FILE__);
        $tests = glob($dir . '/test*.php');
        $xmls = glob($dir . '/test*.xml');
        $length = min(count($tests), count($xmls));

        for ($i=0; $i<$length; $i++) {
            $builder = xml_builder(array('class'=>'xmlwriter'));

            $php = include $tests[$i];
            $php = (string)$php;
            self::assertXmlStringEqualsXmlFile($xmls[$i], $php, $tests[$i]);
        }
    }

    /**
     * test*.phpとtest*.php.arrayの比較を行います。
     *
     */
    function testArray() {
        $dir = dirname(__FILE__);
        $tests = glob($dir . '/test*.php');
        $arrays = glob($dir . '/test*.php.array');
        $length = min(count($tests), count($arrays));

        for ($i=0; $i<$length; $i++) {
            $builder = xml_builder(array('class'=>'array'));

            $php = include $tests[$i];
            $arr = include $arrays[$i];
            self::assertEquals($arr, $php->xmlArray, $tests[$i]);
        }
    }

    /**
     * DTDのテスト
     *
     */
    function testDtd() {
        $expect =<<<_XML_
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE HTML>
<html/>
_XML_;
        $builder = xml_builder(array('doctype'=>XML_Builder::$HTML5, 'formatOutput'=>false));
        $builder->html_();
        $generated = $builder->__toString();
        self::assertXmlStringEqualsXmlString($expect, $generated);

        $builder = xml_builder(array('class'=>'xmlwriter','doctype'=>XML_Builder::$HTML5, 'formatOutput'=>false));
        $builder->html_();
        $generated = $builder->__toString();
        self::assertXmlStringEqualsXmlString($expect, $generated);
    }

    /**
     * 制御構文のテスト
     *
     */
    function testControlStructures() {
        xml_builder()
            ->root
                ->xmlPause($b);
                $b
                ->xmlDo(array($this, 'addElem'))
                ->xmlDo(array($this, 'addElem'))
                ->xmlExport($builder);

        xml_builder()
            ->root
                ->elem_('hoge')
                ->elem_('hoge')
                ->xmlPause($builder2);

        self::assertEqualXMLStructure($builder2->xmlCurrentElem, $builder->xmlCurrentElem);

    }
    function addElem($builder) {
        $builder->elem_('hoge');
    }

    /**
     * DOM固有のテスト
     * HTML出力モードについて
     *
     */
    function testDOMRenderingHTML() {
        xml_builder(array('doctype'=>XML_Builder::$HTML5))
            ->html
                ->head
                    ->meta_(array('http-equiv'=>'Content-Type','content'=>'text/html; charset=UTF-8'))
                    ->title_('HTML出力テスト')
                ->_
                ->body
                    ->div(array('id'=>'wrapper'))
                        ->h1_('HTML出力テスト')
                    ->_
                ->_
            ->_
        ->xmlPause($builder);

        ob_start();
        $builder->xmlEcho('html');
        $html = ob_get_clean();

        self::assertStringEqualsFile(
            dirname(__FILE__).'/expect.html',
            $html
        );

    }

    /**
     * XMLWriter固有のテスト
     * ファイルに直接出力するモードについて
     *
     */
    function testXMLWriterWriteTo() {
        xml_builder(array('class'=>'xmlwriter','writeto'=>dirname(__FILE__).'/writeto.xml'))
            ->root_;

        self::assertXmlFileEqualsXmlFile(dirname(__FILE__).'/test001.xml', dirname(__FILE__).'/writeto.xml');
    }

    /**
     * Array固有のテスト
     * json系
     */
    function testArrayToJson() {
        XML_Builder::factory(array('class'=>'array'))
            ->root_('hoge')
        ->xmlPause($builder);

        $this->assertEquals($builder->xmlArray, $builder->jsonSerialize());
        $this->assertEquals('{"root":"hoge"}', $builder->toJSON());

    }

    /**
     * Array固有のテスト
     * echo系
     */
    function testArrayEcho() {
        xml_builder(array('class'=>'array'))
            ->root_('hoge')
        ->xmlPause($builder);

        ob_start();
        $builder->_echo();
        $res = ob_get_clean();

        self::assertEquals((string)$builder, $res);
    }

    /**
     * doに実行できないものを渡し、例外を発生させる
     * @expectedException InvalidArgumentException
     */
    function testException() {
        xml_builder()->xmlDo(array($this,'use800'));
    }
}
