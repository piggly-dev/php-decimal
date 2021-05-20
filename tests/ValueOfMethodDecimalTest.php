<?php
namespace Piggly\Test\Decimal;

use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Decimal;
use Piggly\Decimal\DecimalConfig;

class ValueOfMethodDecimalTest extends TestCase
{
	/**
	 * Configuration
	 *
	 * @var DecimalConfig
	 */
	protected $_config;

	protected function setUp () : void
	{
		$this->_config = DecimalConfig::clone([
			'precision' => 40,
			'rounding' => 4,
			'toExpNeg' => -9e15,
			'toExpPos' => 9e15,
			'maxE' => 9e15,
			'minE' => -9e15,
			'crypto' => false,
			'modulo' => 1
		]);
	}

	/**
	 * Assert if is matching the expected data.
	 *
	 * @test Expecting positive assertion
    * @dataProvider stringMatching
	 * @param array $coefficient
	 * @param integer $exponent
	 * @param integer $sign
	 * @param Decimal|integer|float|string $n
	 * @return void
	 */
	public function testIsMatchingWithExpected (
		string $expected,
		$n 
	)
	{ $this->assertEquals($expected, (new Decimal($n, $this->_config))->valueOf()); }

	/**
	 * Assert if is matching the expected data.
	 *
	 * @test Expecting positive assertion
    * @dataProvider stringMatchingExpZero
	 * @param array $coefficient
	 * @param integer $exponent
	 * @param integer $sign
	 * @param Decimal|integer|float|string $n
	 * @return void
	 */
	public function testIsExpZeroMatchingWithExpected (
		string $expected,
		$n 
	)
	{ 
		$this->_config->toExpNeg = $this->_config->toExpPos = 0;
		$this->assertEquals($expected, (new Decimal($n, $this->_config))->valueOf()); 
	}
	
	/**
	 * Provider for testIsMatchingWithExpected().
	 * @return array
	 */
	public function stringMatching() : array
	{
		return [
			['0', 0],
			['0', '0'],
			['NAN', \NAN],
			['NAN', 'NAN'],
			['INF', \INF],
			['INF', 'INF'],
			['1', 1],
			['9', 9],
			['90', 90],
			['90.12', 90.12],
			['0.1', 0.1],
			['0.01', 0.01],
			['0.0123', 0.0123],
			['111111111111111111111',   '111111111111111111111'],
			['0.00001', 0.00001],

			['-0', '-0'],
			['-INF', -\INF],
			['-INF', '-INF'],
			['-1', -1],
			['-9', -9],
			['-90', -90],
			['-90.12', -90.12],
			['-0.1', -0.1],
			['-0.01', -0.01],
			['-0.0123', -0.0123],
			['-111111111111111111111',  '-111111111111111111111'],
			['-0.00001', -0.00001],
		];
	}

	/**
	 * Provider for testIsExpZeroMatchingWithExpected().
	 * @return array
	 */
	public function stringMatchingExpZero() : array
	{
		return [
			['1e-7', 0.0000001],
			['1.23e-7', 0.000000123],
			['1.2e-8', 0.000000012],
			['-1e-7', -0.0000001],
			['-1.23e-7', -0.000000123],
			['-1.2e-8', -0.000000012],

			['5.73447902457635174479825134e+14', '573447902457635.174479825134'],
			['1.07688e+1', '10.7688'],
			['3.171194102379077141557759899307946350455841e+27', '3171194102379077141557759899.307946350455841'],
			['4.924353466898191177698653319742594890634579e+37', '49243534668981911776986533197425948906.34579'],
			['6.85558243926569397328633907445409866949445343654692955e+18', '6855582439265693973.28633907445409866949445343654692955'],
			['1e+0', '1'],
		];
	}
}