<?php

namespace Tygh\Addons\VendorRating\Tests\Unit;

use Tygh\Addons\VendorRating\Calculator\EvalBackend;
use Tygh\Addons\VendorRating\Calculator\Variable;
use Tygh\Tests\Unit\ATestCase;

class EvalBackendTest extends ATestCase
{
    /**
     * @var \Tygh\Addons\VendorRating\Calculator\EvalBackend
     */
    protected $backend;

    public function setUp()
    {
        $this->backend = new EvalBackend();
    }

    public function testGeneral()
    {
        $result = $this->backend->evaluate('40 + 2', []);
        $this->assertEquals(42, $result);

        $result = $this->backend->evaluate('a + b + c', [
            new Variable('a', 'foo', 1),
            new Variable('b', 'bar', 2),
            new Variable('c', 'baz', 3),
            new Variable('d', 'bad', 4),
        ]);
        $this->assertEquals(6, $result);

        $result = $this->backend->evaluate('(a + b) * c', [
            new Variable('a', 'foo', 1),
            new Variable('b', 'bar', 2),
            new Variable('c', 'baz', 3),
            new Variable('d', 'bad', 4),
        ]);
        $this->assertEquals(9, $result);

        $result = $this->backend->evaluate('(a + b) * c + d', [
            new Variable('a', 'foo', 1),
            new Variable('b', 'bar', 2),
            new Variable('c', 'baz', 30),
            new Variable('d', 'bad', 4),
        ]);
        $this->assertEquals(94, $result);

        $this->expectException(\DivisionByZeroError::class);
        $this->backend->evaluate('1 / 0', []);
    }
}
