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
class isNMethodDecimalTest extends TestCase
{
	/**
	 * Setup Decimal configuratio$n->
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
	 * @covers ::isFinite
	 * @covers ::isInt
	 * @covers ::isNaN
	 * @covers ::isNegative
	 * @covers ::isZero
	 * @covers ::isDecimal
	 * @covers ::eq
	 * @covers ::lt
	 * @covers ::lte
	 * @covers ::gt
	 * @covers ::gte
	 * @covers ::equals
	 * @covers ::greaterThan
	 * @covers ::greaterThanOrEqualTo
	 * @covers ::lessThan
	 * @covers ::lessThanOrEqualTo
	 * @covers ::toString
	 * @test Expecting positive assertion
    * @dataProvider dataSetOne
	 * @param bool $bool
	 * @return void
	 */
	public function testSetOne ( $bool )
	{ $this->assertTrue($bool); }
	
	/**
	 * Provider for testSetOne().
	 * @return array
	 */
	public function dataSetOne() : array
	{
		$r = [];
		$n = new Decimal(1);

		$arr = [
			[$n->isFinite()],
			[!$n->isNaN()],
			[!$n->isNegative()],
			[!$n->isZero()],
			[$n->isInt()],
			[$n->equals($n)],
			[$n->equals(1)],
			[$n->equals('1.0')],
			[$n->equals('1.00')],
			[$n->equals('1.000')],
			[$n->equals('1.0000')],
			[$n->equals('1.00000')],
			[$n->equals('1.000000')],
			[$n->equals(new Decimal(1))],
			[$n->equals('0x1')],
			[$n->equals('0o1')],
			[$n->equals('0b1')],
			[$n->greaterThan(0.99999)],
			[!$n->greaterThanOrEqualTo(1.1)],
			[$n->lessThan(1.001)],
			[$n->lessThanOrEqualTo(2)],
			[$n->toString() === $n->valueOf()],
		];

		$r = \array_merge($r, $arr);
		$n = new Decimal('-0.1');

		$arr = [
			[$n->isFinite()],
			[!$n->isNaN()],
			[$n->isNeg()],
			[!$n->isZero()],
			[!$n->isInt()],
			[!$n->equals(0.1)],
			[!$n->greaterThan('-0.1')],
			[$n->greaterThanOrEqualTo(-1)],
			[$n->lessThan('-0.01')],
			[!$n->lessThanOrEqualTo(-1)],
			[$n->toString() === $n->valueOf()],
		];

		$r = \array_merge($r, $arr);
		$n = new Decimal(\INF);

		$arr = [
			[!$n->isFinite()],
			[!$n->isNaN()],
			[!$n->isNegative()],
			[!$n->isZero()],
			[!$n->isInt()],
			[$n->eq('INF')],
			[$n->eq(\INF)],
			[$n->gt('9e999')],
			[$n->gte(\INF)],
			[!$n->lt(\INF)],
			[$n->lte(\INF)],
			[$n->toString() === $n->valueOf()],
		];

		$r = \array_merge($r, $arr);
		$n = new Decimal(-\INF);

		$arr = [
			[!$n->isFinite()],
			[!$n->isNaN()],
			[$n->isNeg()],
			[!$n->isZero()],
			[!$n->isInt()],
			[!$n->equals(INF)],
			[$n->equals(-INF)],
			[!$n->greaterThan(-INF)],
			[$n->greaterThanOrEqualTo('-INF', 8)],
			[$n->lessThan(0)],
			[$n->lessThanOrEqualTo(INF)],
			[$n->toString() === $n->valueOf()],
		];
		
		$r = \array_merge($r, $arr);
		$n = new Decimal('0.0000000');

		$arr = [
			[$n->isFinite()],
			[!$n->isNaN()],
			[!$n->isNegative()],
			[$n->isZero()],
			[$n->isInt()],
			[$n->eq(-0)],
			[$n->gt(-0.000001)],
			[!$n->gte(0.1)],
			[$n->lt(0.0001)],
			[$n->lte(-0)],
			[$n->toString() === $n->valueOf()],
		];
		
		$r = \array_merge($r, $arr);
		$n = new Decimal('-0');

		$arr = [
			[$n->isFinite()],
			[!$n->isNaN()],
			[$n->isNeg()],
			[$n->isZero()],
			[$n->isInt()],
			[$n->equals('0.000')],
			[$n->greaterThan(-1)],
			[!$n->greaterThanOrEqualTo(0.1)],
			[!$n->lessThan(0)],
			[!$n->lessThan(0, 36)],
			[$n->lessThan(0.1)],
			[$n->lessThanOrEqualTo(0)],
			[$n->valueOf() === '-0' && $n->toString() === '0'],
		];
		
		$r = \array_merge($r, $arr);
		$n = new Decimal(\NAN);

		$arr = [
			[!$n->isFinite()],
			[$n->isNaN()],
			[!$n->isNegative()],
			[!$n->isZero()],
			[!$n->isInt()],
			[!$n->eq(\NAN)],
			[!$n->eq(\INF)],
			[!$n->gt(0)],
			[!$n->gte(0)],
			[!$n->lt(1)],
			[!$n->lte(-0)],
			[!$n->lte(-1)],
			[$n->toString() === $n->valueOf()],
		];
		
		$r = \array_merge($r, $arr);
		$n = new Decimal('-1.234e+2');

		$arr = [
			[$n->isFinite()],
			[!$n->isNaN()],
			[$n->isNeg()],
			[!$n->isZero()],
			[!$n->isInt()],
			[$n->eq(-123.4)],
			[$n->gt('-0xff')],
			[$n->gte('-1.234e+3')],
			[$n->lt(-123.39999)],
			[$n->lte('-123.4e+0')],
			[$n->toString() === $n->valueOf()],
		];
		
		$r = \array_merge($r, $arr);
		$n = new Decimal('5e-200');

		$arr = [
			[$n->isFinite()],
			[!$n->isNaN()],
			[!$n->isNegative()],
			[!$n->isZero()],
			[!$n->isInt()],
			[$n->equals(5e-200)],
			[$n->greaterThan(5e-201)],
			[!$n->greaterThanOrEqualTo(1)],
			[$n->lessThan(6e-200)],
			[$n->lessThanOrEqualTo(5.1e-200)],
			[$n->toString() === $n->valueOf()],
		];
		
		$r = \array_merge($r, $arr);
		$n = new Decimal('1');

		$arr = [
			[$n->equals($n)],
			[$n->equals($n->toString())],
			[$n->equals($n->toString())],
			[$n->equals($n->valueOf())],
			[$n->equals($n->toFixed())],
			[$n->equals(1)],
			[$n->equals('1e+0')],
			[!$n->equals(-1)],
			[!$n->equals(0.1)],
		];
		
		$r = \array_merge($r, $arr);
		$n = new Decimal('1');

		$arr = [
			[!(new Decimal(NAN))->equals(0)],
			[!(new Decimal(INF))->equals(0)],
			[!(new Decimal(0.1))->equals(0)],
			[!(new Decimal(1e9 + 1))->equals(1e9)],
			[!(new Decimal(1e9 - 1))->equals(1e9)],
			[(new Decimal(1e9 + 1))->equals(1e9 + 1)],
			[(new Decimal(1))->equals(1)],
			[!(new Decimal(1))->equals(-1)],
			[!(new Decimal(NAN))->equals(NAN)],
			[!(new Decimal('NAN'))->equals('NAN')],

			[!(new Decimal(NAN))->greaterThan(NAN)],
			[!(new Decimal(NAN))->lessThan(NAN)],
			[(new Decimal('0xa'))->lessThanOrEqualTo('0xff')],
			[(new Decimal('0xb'))->greaterThanOrEqualTo('0x9')],

			[!(new Decimal(10))->greaterThan(10)],
			[!(new Decimal(10))->lessThan(10)],
			[!(new Decimal(NAN))->lessThan(NAN)],
			[!(new Decimal(INF))->lessThan(-INF)],
			[!(new Decimal(INF))->lessThan(INF)],
			[(new Decimal(INF))->lessThanOrEqualTo(INF)],
			[!(new Decimal(NAN))->greaterThanOrEqualTo(NAN)],
			[(new Decimal(INF))->greaterThanOrEqualTo(INF)],
			[(new Decimal(INF))->greaterThanOrEqualTo(-INF)],
			[!(new Decimal(NAN))->greaterThanOrEqualTo(-INF)],
			[(new Decimal(-INF))->greaterThanOrEqualTo(-INF)],

			[!(new Decimal(2))->greaterThan(10)],
			[!(new Decimal(10))->lessThan(2)],
			[(new Decimal(255))->lessThanOrEqualTo('0xff')],
			[(new Decimal('0xa'))->greaterThanOrEqualTo('0x9')],
			[!(new Decimal(0))->lessThanOrEqualTo('NAN')],
			[!(new Decimal(0))->greaterThanOrEqualTo(NAN)],
			[!(new Decimal(NAN))->lessThanOrEqualTo('NAN')],
			[!(new Decimal(NAN))->greaterThanOrEqualTo(NAN)],
			[!(new Decimal(0))->lessThanOrEqualTo(-INF)],
			[(new Decimal(0))->greaterThanOrEqualTo(-INF)],
			[(new Decimal(0))->lessThanOrEqualTo('INF')],
			[!(new Decimal(0))->greaterThanOrEqualTo('INF')],
			[(new Decimal(10))->lessThanOrEqualTo(20)],
			[!(new Decimal(10))->greaterThanOrEqualTo(20)],

			[!(new Decimal(1.23001e-2))->lessThan(1.23e-2)],
			[(new Decimal(1.23e-2))->lt(1.23001e-2)],
			[!(new Decimal(1e-2))->lessThan(9.999999e-3)],
			[(new Decimal(9.999999e-3))->lt(1e-2)],

			[!(new Decimal(1.23001e+2))->lessThan(1.23e+2)],
			[(new Decimal(1.23e+2))->lt(1.23001e+2)],
			[(new Decimal(9.999999e+2))->lessThan(1e+3)],
			[!(new Decimal(1e+3))->lt(9.9999999e+2)],

			[!(new Decimal(1.23001e-2))->lessThanOrEqualTo(1.23e-2)],
			[(new Decimal(1.23e-2))->lte(1.23001e-2)],
			[!(new Decimal(1e-2))->lessThanOrEqualTo(9.999999e-3)],
			[(new Decimal(9.999999e-3))->lte(1e-2)],

			[!(new Decimal(1.23001e+2))->lessThanOrEqualTo(1.23e+2)],
			[(new Decimal(1.23e+2))->lte(1.23001e+2)],
			[(new Decimal(9.999999e+2))->lessThanOrEqualTo(1e+3)],
			[!(new Decimal(1e+3))->lte(9.9999999e+2)],

			[(new Decimal(1.23001e-2))->greaterThan(1.23e-2)],
			[!(new Decimal(1.23e-2))->gt(1.23001e-2)],
			[(new Decimal(1e-2))->greaterThan(9.999999e-3)],
			[!(new Decimal(9.999999e-3))->gt(1e-2)],

			[(new Decimal(1.23001e+2))->greaterThan(1.23e+2)],
			[!(new Decimal(1.23e+2))->gt(1.23001e+2)],
			[!(new Decimal(9.999999e+2))->greaterThan(1e+3)],
			[(new Decimal(1e+3))->gt(9.9999999e+2)],

			[(new Decimal(1.23001e-2))->greaterThanOrEqualTo(1.23e-2)],
			[!(new Decimal(1.23e-2))->gte(1.23001e-2)],
			[(new Decimal(1e-2))->greaterThanOrEqualTo(9.999999e-3)],
			[!(new Decimal(9.999999e-3))->gte(1e-2)],

			[(new Decimal(1.23001e+2))->greaterThanOrEqualTo(1.23e+2)],
			[!(new Decimal(1.23e+2))->gte(1.23001e+2)],
			[!(new Decimal(9.999999e+2))->greaterThanOrEqualTo(1e+3)],
			[(new Decimal(1e+3))->gte(9.9999999e+2)],

			[!(new Decimal('1.0000000000000000000001'))->isInt()],
			[!(new Decimal('0.999999999999999999999'))->isInt()],
			[(new Decimal('4e4'))->isInt()],
			[(new Decimal('-4e4'))->isInt()],

  			// Decimal::isDecimal

			[Decimal::isDecimal(new Decimal(1))],
			[Decimal::isDecimal(new Decimal('-2.3'))],
			[Decimal::isDecimal(new Decimal(NAN))],
			[Decimal::isDecimal(new Decimal('INF'))],

			[!Decimal::isDecimal(0)],
			[!Decimal::isDecimal(1)],
			[!Decimal::isDecimal('-2.3')],
			[!Decimal::isDecimal(NAN)],
			[!Decimal::isDecimal(INF)],
			[!Decimal::isDecimal(null)],
			[!Decimal::isDecimal(new stdClass())],
		];
		
		$r = \array_merge($r, $arr);

		return $r;
	}
}