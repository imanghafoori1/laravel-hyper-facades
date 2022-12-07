<?php

namespace Imanghafoori\FacadeTests;

use ArgumentCountError;
use Imanghafoori\FacadeTests\Stubs\ApplicationStub;
use Imanghafoori\FacadeTests\Stubs\ConcreteFacadeStub;
use Imanghafoori\FacadeTests\Stubs\ConcreteFacadeStub2;
use Imanghafoori\FacadeTests\Stubs\FacadeStub;
use Imanghafoori\FacadeTests\Stubs\FacadeStub1;
use Imanghafoori\FacadeTests\Stubs\FacadeStub2;
use Imanghafoori\FacadeTests\Stubs\FooA;
use Imanghafoori\FacadeTests\Stubs\FooB;
use Imanghafoori\FacadeTests\Stubs\FooFacadeStub;
use TestCase;

class TestCases extends TestCase
{
    public function test_does_not_swallow_internal_type_error_of_the_target_class()
    {
        try {
            FacadeStub::faulty();
        } catch (ArgumentCountError $error) {
            $this->assertRegExp('/Too few arguments to function .*?FacadeStub1::method\(\), 0 passed in .* and exactly 1 expected/', $error->getMessage());
        }
    }

    public function test_it_can_inject_for_first_param()
    {
        $this->assertEquals('def1', FacadeStub::m1(new FacadeStub1()));
        $this->assertEquals('def1', FacadeStub::m1());
        $this->assertEquals('def12', FacadeStub::m2('2'));
        $this->assertEquals('def1'.'ab'.'def3', FacadeStub::m3(new FacadeStub1(), 'ab'));
        $this->assertEquals('def1'.'bb'.'def3', FacadeStub::m3('bb'));
        $this->assertEquals('def1'.'bb'.'cc', FacadeStub::m3('bb', 'cc'));
        $this->assertEquals('def1'.'bb'.'cc', FacadeStub::m3('bb', 'cc', 'dd'));
        $this->assertEquals('val1'.'def2'.'def3', FacadeStub::m3(new FacadeStub1('val1')));
        $this->assertEquals('def1'.'def2'.'def3', FacadeStub::m3());
    }

    public function test_pre_call_wildcard()
    {
        $obj = new FacadeStub1();
        FacadeStub::preCall('m*', function ($methodName, $args) use ($obj) {
            $this->assertIsArray($args);
            $this->assertEquals($args[0], $obj);
            $this->assertEquals($methodName, 'm1');
        });

        FacadeStub::postCall('m*', function ($methodName, $args, $result) use ($obj) {
            $this->assertIsArray($args);
            $this->assertEquals($args[0], $obj);
            $this->assertEquals($result, 'def1');
            $this->assertEquals($methodName, 'm1');
        });

        $this->assertEquals('def1', FacadeStub::m1($obj));
    }

    public function test_changing_the_proxy_after_creating_the_object()
    {
        FooFacadeStub::shouldProxyTo(FooA::class);
        $fooA = FooFacadeStub::getFacadeRoot();

        FooFacadeStub::shouldProxyTo(FooB::class);
        $fooB = FooFacadeStub::getFacadeRoot();

        $this->assertInstanceOf(FooA::class, $fooA);
        $this->assertInstanceOf(FooB::class, $fooB);
    }

    public function test_pre_call()
    {
        $obj = new FacadeStub1();
        FacadeStub::preCall('m1', function ($methodName, $args) use ($obj) {
            $this->assertIsArray($args);
            $this->assertEquals($args[0], $obj);
            $this->assertEquals($methodName, 'm1');
        });

        FacadeStub::postCall('m1', function ($methodName, $args, $result) use ($obj) {
            $this->assertIsArray($args);
            $this->assertEquals($args[0], $obj);
            $this->assertEquals($result, 'def1');
            $this->assertEquals($methodName, 'm1');
        });

        $this->assertEquals('def1', FacadeStub::m1($obj));
    }

    public function test_pre_call_is_not_called()
    {
        FacadeStub::preCall('m2', function ($methodName, $args) {
            $a = $args[0];
            $a->a = $a->a + 1;
        });
        FacadeStub::postCall('m2', function ($methodName, $args) {
            $a = $args[0];
            $a->a = $a->a + 1;
        });
        $obj = new FacadeStub1(0);
        $this->assertEquals(0, FacadeStub::m1($obj));
        $this->assertEquals($obj->a, 0);
    }

    public function test_it_can_inject_for_second_param()
    {
        $this->assertEquals('abc'.FacadeStub1::class.'def3', FacadeStub::m5('abc'));
        $this->assertEquals('abc'.FacadeStub1::class.'def3', FacadeStub::m5('abc', new FacadeStub1()));
        $this->assertEquals('bb'.FacadeStub1::class.'cc', FacadeStub::m5('bb', 'cc'));
        $this->assertEquals('bb'.FacadeStub1::class.'cc', FacadeStub::m5('bb', new FacadeStub1, 'cc'));
    }

    public function test_it_can_inject_two_dependencies()
    {
        $this->assertEquals('val1'.'def2'.'x_default', FacadeStub::m6(new FacadeStub1('val1'), 'x_'));
        $this->assertEquals('def1'.'val2'.'x_default', FacadeStub::m6(new FacadeStub2('val2'), 'x_'));
        $this->assertEquals('val1'.'val2'.'x_default', FacadeStub::m6(new FacadeStub1('val1'), new FacadeStub2('val2'), 'x_'));
        $this->assertEquals('val1'.'def2'.'x_y', FacadeStub::m6(new FacadeStub1('val1'), 'x_', 'y'));
        $this->assertEquals('def1'.'val2'.'x_y', FacadeStub::m6(new FacadeStub2('val2'), 'x_', 'y'));
        $this->assertEquals('def1'.'def2'.'x_default', FacadeStub::m6('x_'));
        $this->assertEquals('def1'.'def2'.'x_y', FacadeStub::m6('x_', 'y'));
    }

    public function _test_it_can_inject_two_dependencies2()
    {
        FacadeStub::setFacadeApplication(app());
        FacadeStub::shouldProxyTo(ConcreteFacadeStub::class);
        $this->assertEquals('def1'.'x_default'.'def2', FacadeStub::m7('x_'));
        $this->assertEquals('val1'.'x_default'.'def2', FacadeStub::m7(new FacadeStub1('val1'), 'x_'));
        $this->assertEquals('val1'.'x_y'.'def2', FacadeStub::m7(new FacadeStub1('val1'), 'x_', 'y'));
        $this->assertEquals('val1'.'x_y'.'val2',
            FacadeStub::m7(new FacadeStub1('val1'), 'x_', 'y', new FacadeStub2('val2')));
    }

    public function test_dynamic_proxy_changing_by_with_driver()
    {
        $this->assertEquals(ConcreteFacadeStub::class, get_class(FacadeStub::getFacadeRoot()));

        FacadeStub::withDriver(ConcreteFacadeStub2::class);
        $this->assertEquals(ConcreteFacadeStub2::class, get_class(FacadeStub::getFacadeRoot()));

        FacadeStub::withDriver(ConcreteFacadeStub::class);
        $this->assertEquals(ConcreteFacadeStub::class, get_class(FacadeStub::getFacadeRoot()));
    }

    public function test_dynamic_proxy_changing()
    {
        $this->assertEquals(ConcreteFacadeStub::class, get_class(FacadeStub::getFacadeRoot()));

        FacadeStub::changeProxyTo(ConcreteFacadeStub2::class);
        $this->assertEquals(ConcreteFacadeStub2::class, get_class(FacadeStub::getFacadeRoot()));

        // since changeProxyTo sets a temporary driver, it should get rolled back to the last driver after being retrieved once
        FacadeStub::m1(new FacadeStub1('val1'));

        $this->assertEquals(ConcreteFacadeStub::class, get_class(FacadeStub::getFacadeRoot()));
    }

    public function setUp(): void
    {
        parent::setUp();
        $app = new ApplicationStub;
        $app->setAttributes([
            'foo' => new ConcreteFacadeStub,
            FacadeStub1::class => new FacadeStub1(),
            FacadeStub2::class => new FacadeStub2(),
        ]);

        $app = app();
        FacadeStub::setFacadeApplication($app);
        FacadeStub::shouldProxyTo(ConcreteFacadeStub::class);
    }
}
