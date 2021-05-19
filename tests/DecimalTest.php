<?php
namespace Piggly\Test\Decimal;

use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Decimal;
use Piggly\Decimal\DecimalConfig;

class DecimalTest extends TestCase
{
	protected $_config;

	protected function setUp () : void
	{
		$this->_config = (new DecimalConfig())->set([
			'precision' => 40,
			'rounding' => 4,
			'toExpNeg' => -7,
			'toExpPos' => 20,
			'maxE' => 9e15,
			'minE' => -9e15,
			'crypto' => false,
			'modulo' => 1
		]);
	}

	/**
	 * Assert if constructor is same as expected data.
	 *
	 * @test Expecting positive assertion
    * @dataProvider constructorProvider
	 * @param array $coefficient
	 * @param integer $exponent
	 * @param integer $sign
	 * @param Decimal|integer|float|string $n
	 * @return void
	 */
	public function testIsMatchingConstructor ( 
		array $coefficient, 
		int $exponent, 
		int $sign, 
		$n 
	)
	{
		$d = new Decimal($n, $this->_config);

		$this->assertSame(
			[
				'coefficient' => $coefficient,
				'exponent' => $exponent,
				'sign' => $sign,
			],
			[
				'coefficient' => $d->_d(),
				'exponent' => $d->_e(),
				'sign' => $d->_s(),
			]
		);
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
	 * Provider for testIsMatchingConstructor().
	 * @return array
	 */
	public function constructorProvider() : array
	{
		return [
			[[0], 0, 1, 0],
			[[0], 0, 1, -0], // TODO :: Need to solve it, SHOULD BE -1 SIGN
			[[1], 0, -1, -1],
			[[10], 1, -1, -10],

			[[1], 0, 1, 1],
			[[10], 1, 1, 10],
			[[100], 2, 1, 100],
			[[1000], 3, 1, 1000],
			[[10000], 4, 1, 10000],
			[[100000], 5, 1, 100000],
			[[1000000], 6, 1, 1000000],

			[[1], 7, 1, 10000000],
			[[10], 8, 1, 100000000],
			[[100], 9, 1, 1000000000],
			[[1000], 10, 1, 10000000000],
			[[10000], 11, 1, 100000000000],
			[[100000], 12, 1, 1000000000000],
			[[1000000], 13, 1, 10000000000000],

			[[1], 14, -1, -100000000000000],
			[[10], 15, -1, -1000000000000000],
			[[100], 16, -1, -10000000000000000],
			[[1000], 17, -1, -100000000000000000],
			[[10000], 18, -1, -1000000000000000000],
			[[100000], 19, -1, -10000000000000000000],
			[[1000000], 20, -1, -100000000000000000000],

			[[1000000], -1, 1, 1e-1],
			[[100000], -2, -1, -1e-2],
			[[10000], -3, 1, 1e-3],
			[[1000], -4, -1, -1e-4],
			[[100], -5, 1, 1e-5],
			[[10], -6, -1, -1e-6],
			[[1], -7, 1, 1e-7],

			[[1000000], -8, 1, 1e-8],
			[[100000], -9, -1, -1e-9],
			[[10000], -10, 1, 1e-10],
			[[1000], -11, -1, -1e-11],
			[[100], -12, 1, 1e-12],
			[[10], -13, -1, -1e-13],
			[[1], -14, 1, 1e-14],

			[[1000000], -15, 1, 1e-15],
			[[100000], -16, -1, -1e-16],
			[[10000], -17, 1, 1e-17],
			[[1000], -18, -1, -1e-18],
			[[100], -19, 1, 1e-19],
			[[10], -20, -1, -1e-20],
			[[1], -21, 1, 1e-21],

			[[9], 0, 1, '9'],
			[[99], 1, -1, '-99'],
			[[999], 2, 1, '999'],
			[[9999], 3, -1, '-9999'],
			[[99999], 4, 1, '99999'],
			[[999999], 5, -1, '-999999'],
			[[9999999], 6, 1, '9999999'],

			[[9, 9999999], 7, -1, '-99999999'],
			[[99, 9999999], 8, 1, '999999999'],
			[[999, 9999999], 9, -1, '-9999999999'],
			[[9999, 9999999], 10, 1, '99999999999'],
			[[99999, 9999999], 11, -1, '-999999999999'],
			[[999999, 9999999], 12, 1, '9999999999999'],
			[[9999999, 9999999], 13, -1, '-99999999999999'],

			[[9, 9999999, 9999999], 14, 1, '999999999999999'],
			[[99, 9999999, 9999999], 15, -1, '-9999999999999999'],
			[[999, 9999999, 9999999], 16, 1, '99999999999999999'],
			[[9999, 9999999, 9999999], 17, -1, '-999999999999999999'],
			[[99999, 9999999, 9999999], 18, 1, '9999999999999999999'],
			[[999999, 9999999, 9999999], 19, -1, '-99999999999999999999'],
			[[9999999, 9999999, 9999999], 20, 1, '999999999999999999999']
		];
	}

	
	/**
	 * Provider for testIsMatchingWithExpected().
	 * @return array
	 */
	public function stringMatching() : array
	{
		return [
			// Binary.
			['0', '0b0'],
			['0', '0B0'],
			['-5', '-0b101'],
			['5', '+0b101'],
			// ['1.5', '0b1.1'],
			// ['-1.5', '-0b1.1'],

			// ['18181', '0b100011100000101.00'],
			// ['-12.5', '-0b1100.10'],
			// ['343872.5', '0b1010011111101000000.10'],
			// ['-328.28125', '-0b101001000.010010'],
			// ['-341919.144535064697265625', '-0b1010011011110011111.0010010100000000010'],
			// ['97.10482025146484375', '0b1100001.000110101101010110000'],
			// ['-120914.40625', '-0b11101100001010010.01101'],
			// ['8080777260861123367657', '0b1101101100000111101001111111010001111010111011001010100101001001011101001'],

			// // Octal.
			// ['8', '0o10'],
			// ['-8.5', '-0O010.4'],
			// ['8.5', '+0O010.4'],
			// ['-262144.000000059604644775390625', '-0o1000000.00000001'],
			// ['572315667420.390625', '0o10250053005734.31'],

			// // Hex.
			// ['1', '0x00001'],
			// ['255', '0xff'],
			// ['-15.5', '-0Xf.8'],
			// ['15.5', '+0Xf.8'],
			// ['-16777216.00000000023283064365386962890625', '-0x1000000.00000001'],
			// ['325927753012307620476767402981591827744994693483231017778102969592507', '0xc16de7aa5bf90c3755ef4dea45e982b351b6e00cd25a82dcfe0646abb']
		];
	}

	/**
	 * Get random integer.
	 *
	 * @return int
	 */
	private function randInt () : int
	{ return (int)\floor( $this->random() * 0x20000000000000 / \pow(10, $this->random() * 16 | 0) ); }

	/**
	 * Get random between 0 and 1.
	 *
	 * @return float
	 */
	private function random () : float
	{ return \mt_rand() / \mt_getrandmax(); }

	/**
	 * Prepare random to base.
	 *
	 * @param integer $base
	 * @return array
	 */
	private function prepareRandom ( $base = 10 ) : array
	{
		$r = randInt();
		return [\strval($r), \strval(\base_convert($r, 10, $base))];
	}
}