<?php
namespace Piggly\Test\Decimal;

use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Decimal;
use Piggly\Decimal\DecimalConfig;

/**
 * @coversDefaultClass \Piggly\Decimal\Decimal
 */
class MinAndMaxMethodDecimalTest extends TestCase
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
				'minE' => -9e15
			]);
	}

	/**
	 * Assert if is matching the expected data.
	 *
	 * @covers ::minOf
	 * @covers ::maxOf
	 * @test Expecting positive assertion
    * @dataProvider dataSetOne
	 * @param Decimal|integer|float|string $min
	 * @param Decimal|integer|float|string $max
	 * @param array<Decimal|integer|float|string> $args
	 * @param string $expected
	 * @param integer $sd Significant Digits
	 * @param integer $rm Rounding mode
	 * @return void
	 */
	public function testSetOne (
		$min,
		$max,
		array $args
	)
	{ 
		$this->assertEquals(
			[
				(new Decimal($min))->valueOf(),
				(new Decimal($max))->valueOf()
			],
			[
				Decimal::minOf($args)->valueOf(),
				Decimal::maxOf($args)->valueOf()
			]
		);
	}

	/**
	 * Provider for testSetOne().
	 * @return array
	 */
	public function dataSetOne() : array
	{
		return [
			[NAN, NAN, [NAN]],
			[NAN, NAN, [-2, 0, -1, NAN]],
			[NAN, NAN, [-2, NAN, 0, -1]],
			[NAN, NAN, [NAN, -2, 0, -1]],
			[NAN, NAN, [NAN, -2, 0, -1]],
			[NAN, NAN, [-2, 0, -1, new Decimal(NAN)]],
			[NAN, NAN, [-2, 0, -1, new Decimal(NAN)]],
			[NAN, NAN, [INF, -2, 'NAN', 0, -1, -INF]],
			[NAN, NAN, ['NAN', INF, -2, 0, -1, -INF]],
			[NAN, NAN, [INF, -2, NAN, 0, -1, -INF]],

			[0, 0, [0, 0, 0]],
			[-2, INF, [-2, 0, -1, INF]],
			[-INF, 0, [-2, 0, -1, -INF]],
			[-INF, INF, [-INF, -2, 0, -1, INF]],
			[-INF, INF, [INF, -2, 0, -1, -INF]],
			[-INF, INF, [-INF, -2, 0, new Decimal(INF)]],

			[-2, 0, [-2, 0, -1]],
			[-2, 0, [-2, -1, 0]],
			[-2, 0, [0, -2, -1]],
			[-2, 0, [0, -1, -2]],
			[-2, 0, [-1, -2, 0]],
			[-2, 0, [-1, 0, -2]],

			[-1, 1, [-1, 0, 1]],
			[-1, 1, [-1, 1, 0]],
			[-1, 1, [0, -1, 1]],
			[-1, 1, [0, 1, -1]],
			[-1, 1, [1, -1, 0]],
			[-1, 1, [1, 0, -1]],

			[0, 2, [0, 1, 2]],
			[0, 2, [0, 2, 1]],
			[0, 2, [1, 0, 2]],
			[0, 2, [1, 2, 0]],
			[0, 2, [2, 1, 0]],
			[0, 2, [2, 0, 1]],

			[-1, 1, ['-1', 0, new Decimal(1)]],
			[-1, 1, ['-1', new Decimal(1)]],
			[-1, 1, [0, '-1', new Decimal(1)]],
			[0, 1, [0, new Decimal(1)]],
			[1, 1, [new Decimal(1)]],
			[-1, -1, [new Decimal(-1)]],

			[0.0009999, 0.0010001, [0.001, 0.0009999, 0.0010001]],
			['-0.0010001', '-0.0009999', ['-0.001', '-0.0009999', '-0.0010001']],
			['-0.000001', 999.001, [2, '-0', '1e-9000000000000000', 324.32423423, '-0.000001', '999.001', 10]],
			['-9.99999e+9000000000000000', INF, [10, '-9.99999e+9000000000000000', new Decimal(INF), '9.99999e+9000000000000000', 0]],
			['-9.999999e+9000000000000000', '1.01e+9000000000000000', ['-9.99998e+9000000000000000', '-9.999999e+9000000000000000', '9e+8999999999999999', '1.01e+9000000000000000', 1e+300]],
			[1, INF, [1, '1e+9000000000000001', 1e200]],
			[-INF, 1, [1, '-1e+9000000000000001', -1e200]],
			[0, 1, [1, '1e-9000000000000001', 1e-200]],
			['-0', 1, [1, '-1e-9000000000000001', 1e-200]],
			[-3, 3, [1, '2', 3, '-1', -2, '-3']],
		];
	}
}