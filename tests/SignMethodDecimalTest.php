<?php
namespace Piggly\Test\Decimal;

use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Decimal;
use Piggly\Decimal\DecimalConfig;

/**
 * @coversDefaultClass \Piggly\Decimal\Decimal
 */
class SignMethodDecimalTest extends TestCase
{
	/**
	 * Assert if is matching the expected data.
	 *
	 * @covers ::signOf
	 * @test Expecting positive assertion
    * @dataProvider dataSetOne
	 * @param Decimal|integer|float|string $n
	 * @param string $expected
	 * @return void
	 */
	public function testSetOne (
		$n,
		$expected
	)
	{ $this->assertEquals(\strval($expected), \strval(Decimal::signOf($n))); }
	
	/**
	 * Provider for testSetOne().
	 * @return array
	 */
	public function dataSetOne() : array
	{
		return [
			[NAN, NAN],
			['NAN', NAN],
			[INF, 1],
			[-INF, -1],
			['INF', 1],
			['-INF', -1],

			['0', 0],
			['-0', '-0'],
			['1', 1],
			['-1', -1],
			['9.99', 1],
			['-9.99', -1],
		];
	}
}