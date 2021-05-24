<?php
namespace Piggly\Test\Decimal;

use Exception;
use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Decimal;
use Piggly\Decimal\DecimalConfig;
use Piggly\Decimal\DecimalHelper;

/**
 * @coversDefaultClass \Piggly\Decimal\Decimal
 */
class PowAgainstSqrtMethodDecimalTest extends TestCase
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
				'toExpNeg' => -7,
				'toExpPos' => 21,
				'maxE' => 9e15,
				'minE' => -9e15
			]);
	}

	/**
	 * Assert if is matching the expected data.
	 *
	 * @covers ::pow
	 * @covers ::powOf
	 * @covers ::toPower
	 * @covers ::sqrt
	 * @covers ::sqrtOf
	 * @covers ::squareRoot
	 * @test Expecting positive assertion
    * @dataProvider dataSetOne
	 * @param string $base
	 * @param string $exp
	 * @return void
	 */
	public function testSetOne (
		string $in,
		string $out
	)
	{ $this->assertEquals($in, $out); }
	
	/**
	 * Provider for testSetOne().
	 * @return array
	 */
	public function dataSetOne() : array
	{
		$arr = [];

		// Integers between -1e7 and 1e7
		for ( $i = 0; $i < 500; $i++ )
		{
			/** @var Decimal $r */ 
			$r = new Decimal(\random_int(-1e7, 1e7));

			DecimalConfig::instance()->set([
				'precision'=>\rand(1,40),
				'rounding'=>\rand(0,9)
			]);

			/** @var Decimal $p */
			$p = $r->pow(0.5);
			$s = $r->sqrt();

			$arr[] = [$p->valueOf(), $s->valueOf()];
		}

		// LONG INT
		for ( $i = 0; $i < 500; $i++ )
		{
			// Get a random value in the range [0,1]
			// with a random number of significant digits
			// in the range [1, 40], as a string in exponential
			// format
			/** @var string $e */
			$e = Decimal::random(\rand(1,40))->toExponential();
			$epos = \strpos($e, 'e') === false ? 0 : \strpos($e, 'e');

			// Change exponent to a non-zero value of random 
			// length in the range (-9e15, 9e15).
			// After 1e9 cames performance issues see getZeroString function.
			/** @var string $n */
			$n = \strval(\random_int(0, 1e9));
			$n = DecimalHelper::slice($e, 0, $epos)
					.'e'.(\rand(0,10) < 5 ? '-' : '')
					.DecimalHelper::slice($n, \rand(0, \strlen($n)-1));

			/** @var Decimal $r */ 
			$r = new Decimal($n);

			DecimalConfig::instance()->set([
				'precision'=>\rand(1,40),
				'rounding'=>\rand(0,8)
			]);

			/** @var Decimal $p */
			$p = $r->pow(0.5);
			$s = $r->sqrt();

			$arr[] = [$p->valueOf(), $s->valueOf()];
		}

		return $arr;
	}
}