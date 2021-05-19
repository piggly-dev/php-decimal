<?php
namespace Piggly\Test\Decimal;

use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Decimal;
use Piggly\Decimal\DecimalConfig;

class AbsMethodDecimalTest extends TestCase
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
			'toExpNeg' => -7,
			'toExpPos' => 21,
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
	{ $this->assertEquals($expected, (new Decimal($n, $this->_config))->abs()->valueOf()); }

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
		$this->assertEquals($expected, (new Decimal($n, $this->_config))->abs()->valueOf()); 
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
			['0', -0],
			['0', '-0'],
			['1', 1],
			['1', -1],
			['1', '-1'],
			['0.5', '0.5'],
			['0.5', '-0.5'],
			['0.1', 0.1],
			['0.1', -0.1],
			['1.1', 1.1],
			['1.1', -1.1],
			['1.5', '1.5'],
			['1.5', '-1.5'],
			
			['0.00001', '-1e-5'],
			['9000000000', '-9e9'],
			['123456.7891011', '123456.7891011'],
			['123456.7891011', -123456.7891011],
			['99', '99'],
			['99', -99],
			['999.999', 999.999],
			['999.999', '-999.999'],
			['1', new Decimal(-1)],
			['1', new Decimal('-1')],
			['0.001', new Decimal(0.001)],
			['0.001', new Decimal('-0.001')],
			
			['Infinity', \INF],
			['Infinity', -\INF],
			['Infinity', 'INF'],
			['Infinity', '-INF'],
			['NaN', \NAN],
			['NaN', -\NAN],
			['NaN', 'NAN'],
			['NaN', '-NAN'],
			
			['11.121', '11.121'],
			['0.023842', '-0.023842'],
			['1.19', '-1.19'],
			['9.622e-11', '-0.00000000009622'],
			['5.09e-10', '-0.000000000509'],
			['3838.2', '3838.2'],
			['127', '127.0'],
			['4.23073', '4.23073'],
			['2.5469', '-2.5469'],
			['29949', '-29949'],
			['277.1', '-277.10'],
			['4.97898e-15', '-0.00000000000000497898'],
			['53.456', '53.456'],
			['100564', '-100564'],
			['12431.9', '-12431.9'],
			['97633.7', '-97633.7'],
			['220', '220'],
			['18.72', '18.720'],
			['2817', '-2817'],
			['44535', '-44535']
		];
	}

	/**
	 * Provider for testIsExpZeroMatchingWithExpected().
	 * @return array
	 */
	public function stringMatchingExpZero() : array
	{
		return [
			['5.2452468128e+1', '-5.2452468128e+1'],
			['1.41525905257189365008396e+16', '1.41525905257189365008396e+16'],
			['2.743068083928e+11', '2.743068083928e+11'],
			['1.52993064722314247378724599e+26', '-1.52993064722314247378724599e+26'],
			['3.7205576746e+10', '3.7205576746e+10'],
			['2.663e-10', '-2.663e-10'],
			['1.26574209965030360615518e+17', '-1.26574209965030360615518e+17'],
			['1.052e+3', '1.052e+3'],
			['4.452945872502e+6', '-4.452945872502e+6'],
			['2.95732460816619226e+13', '2.95732460816619226e+13'],
			['1.1923100194288654481424e+18', '-1.1923100194288654481424e+18'],
			['8.99315449050893705e+6', '8.99315449050893705e+6'],
			['5.200726538434486963e+8', '5.200726538434486963e+8'],
			['1.182618278949368566264898065e+18', '1.182618278949368566264898065e+18'],
			['3.815873266712e-20', '-3.815873266712e-20'],
			['1.316675370382742615e+6', '-1.316675370382742615e+6'],
			['2.1032502e+6', '-2.1032502e+6'],
			['1.8e+1', '1.8e+1'],
			['1.033525906631680944018544811261e-13', '1.033525906631680944018544811261e-13'],
			['1.102361746443461856816e+14', '-1.102361746443461856816e+14'],
			['8.595358491143959e+1', '8.595358491143959e+1'],
			['1.226806049797304683867e-18', '1.226806049797304683867e-18'],
			['5e+0', '-5e+0'],
			['1.091168788407093537887970016e+15', '-1.091168788407093537887970016e+15'],
			['3.87166413612272027e+12', '3.87166413612272027e+12'],
			['1.411514e+5', '1.411514e+5'],
			['1.0053454672509859631996e+22', '1.0053454672509859631996e+22'],
			['6.9265714e+0', '6.9265714e+0'],
			['1.04627709e+4', '1.04627709e+4'],
			['2.285650225267766689304972e+5', '2.285650225267766689304972e+5'],
			['4.5790517211306242e+7', '4.5790517211306242e+7'],
			['3.0033340092338313923473428e+16', '-3.0033340092338313923473428e+16'],
			['2.83879929283797623e+1', '-2.83879929283797623e+1'],
			['4.5266377717178121183759377414e-5', '4.5266377717178121183759377414e-5'],
			['5.3781e+4', '-5.3781e+4'],
			['6.722035208213298413522819127e-18', '-6.722035208213298413522819127e-18'],
			['3.02865707828281230987116e+23', '-3.02865707828281230987116e+23'],

			['1e-9000000000000000', '1e-9000000000000000'],
			['1e-9000000000000000', '-1e-9000000000000000'],
			['0e+0', '-9.9e-9000000000000001'],
			['9.999999e+9000000000000000', '9.999999e+9000000000000000'],
			['9.999999e+9000000000000000', '-9.999999e+9000000000000000'],
			['Infinity', '1E9000000000000001'],
			['Infinity', '-1e+9000000000000001'],
			['5.5879983320336874473209567979e+287894365', '-5.5879983320336874473209567979e+287894365']
		];
	}
}