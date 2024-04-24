<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Json_Server
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @category   Zend
 * @package    Zend_Json_Server
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Json
 * @group      Zend_Json_Server
 */
class Zend_Json_Server_RequestTest extends PHPUnit\Framework\TestCase
{
    protected $request;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->request = new Zend_Json_Server_Request();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    public function testShouldHaveNoParamsByDefault()
    {
        $params = $this->request->getParams();
        $this->assertEmpty($params);
    }

    public function testShouldBeAbleToAddAParamAsValueOnly()
    {
        $this->request->addParam('foo');
        $params = $this->request->getParams();
        $this->assertCount(1, $params);
        $test = array_shift($params);
        $this->assertEquals('foo', $test);
    }

    public function testShouldBeAbleToAddAParamAsKeyValuePair()
    {
        $this->request->addParam('bar', 'foo');
        $params = $this->request->getParams();
        $this->assertCount(1, $params);
        $this->assertArrayHasKey('foo', $params);
        $this->assertEquals('bar', $params['foo']);
    }

    public function testInvalidKeysShouldBeIgnored()
    {
        $count = 0;
        foreach (array(array('foo', true), array('foo', new stdClass), array('foo', array())) as $spec) {
            $this->request->addParam($spec[0], $spec[1]);
            $this->assertNull($this->request->getParam('foo'));
            $params = $this->request->getParams();
            ++$count;
            $this->assertCount($count, $params);
        }
    }

    public function testShouldBeAbleToAddMultipleIndexedParamsAtOnce()
    {
        $params = array(
            'foo',
            'bar',
            'baz',
        );
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertSame($params, $test);
    }

    public function testShouldBeAbleToAddMultipleNamedParamsAtOnce()
    {
        $params = array(
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
        );
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertSame($params, $test);
    }

    public function testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce()
    {
        $params = array(
            'foo' => 'bar',
            'baz',
            'baz' => 'bat',
        );
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertEquals(array_values($params), array_values($test));
        $this->assertArrayHasKey('foo', $test);
        $this->assertArrayHasKey('baz', $test);
        $this->assertTrue(in_array('baz', $test));
    }

    public function testSetParamsShouldOverwriteParams()
    {
        $this->testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce();
        $params = array(
            'one',
            'two',
            'three',
        );
        $this->request->setParams($params);
        $this->assertSame($params, $this->request->getParams());
    }

    public function testShouldBeAbleToRetrieveParamByKeyOrIndex()
    {
        $this->testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce();
        $params = $this->request->getParams();
        $this->assertEquals('bar', $this->request->getParam('foo'), var_export($params, 1));
        $this->assertEquals('baz', $this->request->getParam(1), var_export($params, 1));
        $this->assertEquals('bat', $this->request->getParam('baz'), var_export($params, 1));
    }

    public function testMethodShouldBeNullByDefault()
    {
        $this->assertNull($this->request->getMethod());
    }

    public function testMethodErrorShouldBeFalseByDefault()
    {
        $this->assertFalse($this->request->isMethodError());
    }

    public function testMethodAccessorsShouldWorkUnderNormalInput()
    {
        $this->request->setMethod('foo');
        $this->assertEquals('foo', $this->request->getMethod());
    }

    public function testSettingMethodWithInvalidNameShouldSetError()
    {
        foreach (array('1ad', 'abc-123', 'ad$$832r#@') as $method) {
            $this->request->setMethod($method);
            $this->assertNull($this->request->getMethod());
            $this->assertTrue($this->request->isMethodError());
        }
    }

    public function testIdShouldBeNullByDefault()
    {
        $this->assertNull($this->request->getId());
    }

    public function testIdAccessorsShouldWorkUnderNormalInput()
    {
        $this->request->setId('foo');
        $this->assertEquals('foo', $this->request->getId());
    }

    public function testVersionShouldBeJsonRpcV1ByDefault()
    {
        $this->assertEquals('1.0', $this->request->getVersion());
    }

    public function testVersionShouldBeLimitedToV1AndV2()
    {
        $this->testVersionShouldBeJsonRpcV1ByDefault();
        $this->request->setVersion('2.0');
        $this->assertEquals('2.0', $this->request->getVersion());
        $this->request->setVersion('foo');
        $this->assertEquals('1.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToLoadRequestFromJsonString()
    {
        $options = $this->getOptions();
        $json    = Zend_Json::encode($options);
        $this->request->loadJson($json);

        $this->assertEquals('foo', $this->request->getMethod());
        $this->assertEquals('foobar', $this->request->getId());
        $this->assertEquals($options['params'], $this->request->getParams());
    }

    public function testLoadingFromJsonShouldSetJsonRpcVersionWhenPresent()
    {
        $options            = $this->getOptions();
        $options['jsonrpc'] = '2.0';
        $json               = Zend_Json::encode($options);
        $this->request->loadJson($json);
        $this->assertEquals('2.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToCastToJson()
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json = $this->request->toJson();
        $this->validateJson($json, $options);
    }

    public function testCastingToStringShouldCastToJson()
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json = $this->request->__toString();
        $this->validateJson($json, $options);
    }

    /**
     * @group ZF-6187
     */
    public function testMethodNamesShouldAllowDotNamespacing()
    {
        $this->request->setMethod('foo.bar');
        $this->assertEquals('foo.bar', $this->request->getMethod());
    }

    public function getOptions()
    {
        return array(
            'method' => 'foo',
            'params' => array(
                5,
                'four',
                true,
            ),
            'id' => 'foobar'
        );
    }

    public function validateJson($json, array $options)
    {
        $test = Zend_Json::decode($json);
        $this->assertIsArray($test, var_export($json, 1));

        $this->assertArrayHasKey('id', $test);
        $this->assertArrayHasKey('method', $test);
        $this->assertArrayHasKey('params', $test);

        $this->assertIsString($test['id']);
        $this->assertIsString($test['method']);
        $this->assertIsArray($test['params']);

        $this->assertEquals($options['id'], $test['id']);
        $this->assertEquals($options['method'], $test['method']);
        $this->assertSame($options['params'], $test['params']);
    }
}
