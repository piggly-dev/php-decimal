<?php
namespace Piggly\Test\Decimal;

use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Decimal;
use Piggly\Decimal\DecimalConfig;

/**
 * @coversDefaultClass \Piggly\Decimal\Decimal
 */
class ToNumberMethodDecimalTest extends TestCase
{
	/**
	 * Setup Decimal configuration.
	 *
	 * @return void
	 */
	protected function setUp () : void
	{
		DecimalConfig
			::instance()
			->set([
				'precision' => 20,
				'rounding' => 4,
				'toExpNeg' => -7,
				'toExpPos' => 21,
				'maxE' => 9e15,
				'minE' => -9e15,
				'modulo' => 1
			]);
	}

	/**
	 * Assert if is matching the expected data.
	 *
	 * @covers ::toNumber
	 * @test Expecting positive assertion
    * @dataProvider dataSetOne
	 * @param string $expected Expected data.
	 * @param Decimal|integer|float|string $n
	 * @return void
	 */
	public function testSetOne (
		$n,
		$expected
	)
	{ 
		if ( \is_nan($expected) )
		{ 
			$this->assertTrue(\is_nan((new Decimal($n))->toNumber())); 
			return;
		}

		$this->assertEquals($expected, (new Decimal($n))->toNumber()); 
	}
	
	/**
	 * Provider for testSetOne().
	 * @return array
	 */
	public function dataSetOne() : array
	{
		return [
			['0', 0],
			['0.0', 0],
			['0.000000000000', 0],
			['0e+0', 0],
			['0e-0', 0],
			['1e-9000000000000000', 0],
			['-0', 0],
			['-0.0', 0],
			['-0.000000000000', 0],
			['-0e+0', 0],
			['-0e-0', 0],
			['-1e-9000000000000000', -0.0],

			[INF, INF],
			['INF', INF],
			[-INF, -INF],
			['-INF', -INF],
			[NAN, NAN],
			['NAN', NAN],

			[1, 1],
			['1', 1],
			['1.0', 1],
			['1e+0', 1],
			['1e-0', 1],

			[-1, -1],
			['-1', -1],
			['-1.0', -1],
			['-1e+0', -1],
			['-1e-0', -1],

			['123.456789876543', 123.456789876543],
			['-123.456789876543', -123.456789876543],

			['1.1102230246251565e-16', 1.1102230246251565e-16],
			['-1.1102230246251565e-16', -1.1102230246251565e-16],

			['9007199254740991', 9007199254740991],
			['-9007199254740991', -9007199254740991],

			['5e-324', 5e-324],

			['9.999999e+9000000000000000', INF],
			['-9.999999e+9000000000000000', -INF],
			['1e-9000000000000000', 0],
			['-1e-9000000000000000', -0],
			
			// PHP CAN'T HANDLE IT
			// ['1.7976931348623157e+308', 1.7976931348623157e+308],
		];
	}
}