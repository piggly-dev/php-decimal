<?php
namespace Piggly\Test\Decimal;

use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Decimal;
use Piggly\Decimal\DecimalConfig;
use RuntimeException;
use stdClass;

/**
 * @coversDefaultClass \Piggly\Decimal\Decimal
 */
class DpSpMethodDecimalTest extends TestCase
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
				'rounding' => 7,
				'toExpNeg' => -9e15,
				'toExpPos' => 300,
				'maxE' => 9e15,
				'minE' => -9e15
			]);
	}

	/**
	 * Assert if is matching the expected data.
	 *
	 * @covers ::dp
	 * @covers ::decimalPlaces
	 * @covers ::sd
	 * @covers ::precision
	 * @test Expecting positive assertion
    * @dataProvider dataSetOne
	 * @param Decimal|integer|float|string $n
	 * @param integer $dp Decimal Places
	 * @param integer $sd Significant Digits
	 * @param bool $zn
	 * @return void
	 */
	public function testSetOne (
		$n,
		$dp,
		$sd,
		$zn = false
	)
	{ 
		$this->assertEquals(
			[
				(string)$dp,
				(string)$dp,
				(string)$sd,
				(string)$sd
			],
			[
				(string)(new Decimal($n))->dp(),
				(string)(new Decimal($n))->decimalPlaces(),
				(string)(new Decimal($n))->sd($zn),
				(string)(new Decimal($n))->precision($zn),
			]
		);
	}

	/**
	 * Assert if is matching the expected data.
	 *
	 * @covers ::dp
	 * @covers ::decimalPlaces
	 * @covers ::sd
	 * @covers ::precision
	 * @test Expecting positive assertion
    * @dataProvider dataSetTwo
	 * @param callable $fn
	 * @return void
	 */
	public function testSetTwo (
		$fn
	)
	{ 
		$this->expectException(RuntimeException::class);
		$fn();
	}
	
	/**
	 * Provider for testSetOne().
	 * @return array
	 */
	public function dataSetOne() : array
	{
		return [
			[0, 0, 1],
			[-0, 0, 1],
			[NAN, NAN, NAN],
			[INF, NAN, NAN],
			[-INF, NAN, NAN],
			[1, 0, 1],
			[-1, 0, 1],

			[100, 0, 1],
			[100, 0, 1, 0],
			[100, 0, 1, false],
			[100, 0, 3, 1],
			[100, 0, 3, true],

			['0.0012345689', 10, 8],
			['0.0012345689', 10, 8, 0],
			['0.0012345689', 10, 8, false],
			['0.0012345689', 10, 8, 1],
			['0.0012345689', 10, 8, true],

			['987654321000000.0012345689000001', 16, 31, 0],
			['987654321000000.0012345689000001', 16, 31, 1],

			['1e+123', 0, 1],
			['1e+123', 0, 124, 1],
			['1e-123', 123, 1],
			['1e-123', 123, 1, 1],

			['9.9999e+9000000000000000', 0, 5, false],
			['9.9999e+9000000000000000', 0, 9000000000000001, true],
			['-9.9999e+9000000000000000', 0, 5, false],
			['-9.9999e+9000000000000000', 0, 9000000000000001, true],

			['1e-9000000000000000', 9e15, 1, false],
			['1e-9000000000000000', 9e15, 1, true],
			['-1e-9000000000000000', 9e15, 1, false],
			['-1e-9000000000000000', 9e15, 1, true],

			['55325252050000000000000000000000.000000004534500000001', 21, 53],
		];
	}
	
	/**
	 * Provider for testSetTwo().
	 * @return array
	 */
	public function dataSetTwo() : array
	{
		return [
			[function () {(new Decimal(1))->precision(null);}],
			[function () {(new Decimal(1))->sd(null);}],
			[function () {(new Decimal(1))->sd(2);}],
			[function () {(new Decimal(1))->sd('3');}],
			[function () {(new Decimal(1))->sd(new stdClass());}],
		];
	}
}