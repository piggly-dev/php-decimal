<?php
namespace Piggly\Decimal;

class Math
{
	public static function digitsToString ()
	{}

	public static function checkInt32 ()
	{}

	public static function checkRoundingDigits ()
	{}

	public static function convertBase ()
	{}

	public static function cos ()
	{}

	public static function divide ()
	{}

	public static function finalise ( 
		$number, 
		$significantDigits = null, 
		$rounding = null, 
		$isTruncated = false
	) : Decimal
	{}

	public static function finiteToString ()
	{}

	public static function getBase10Exponent ()
	{}

	public static function getLn10 ()
	{}

	public static function getPi ()
	{}

	public static function getPrecision ()
	{}

	public static function getZeroString ()
	{}

	public static function intPow ()
	{}

	/**
	 * Undocumented function
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function isOdd ( 
		$x
	) : bool
	{
		$xd = $x->getDigits();
		return $xd[count($xd)-1] & 1;
	}

	/**
	 * Handle the max and min comparations.
	 * Where $method is the method to apply
	 * and $number an array of numbers.
	 *
	 * @param DecimalConfig $config
	 * @param string $method
	 * @param [type] $numbers
	 * @param array<Decimal|float|int|string> $numbers
	 * @return Decimal
	 */
	public static function maxOrMin (
		DecimalConfig $config,
		string $method,
		array $numbers
	) : Decimal
	{}

	/**
	 * Return a new Decimal whose value is the natural 
	 * exponential of `x` rounded to `sd` significant
	 * digits.
	 *
	 * Taylor/Maclaurin series.
	 *
	 * exp(x) = x^0/0! + x^1/1! + x^2/2! + x^3/3! + ...
	 *
	 * Argument reduction:
	 *   Repeat x = x / 32, k += 5, until |x| < 0.1
	 *   exp(x) = exp(x / 2^k)^(2^k)
	 *
	 * Previously, the argument was initially reduced by
	 * exp(x) = exp(r) * 10^k  where r = x - k * ln10, k = floor(x / ln10)
	 * to first put r in the range [0, ln10], before dividing 
	 * by 32 until |x| < 0.1, but this was found to be slower 
	 * than just dividing repeatedly by 32 as above.
	 *
	 * Max integer argument: exp('20723265836946413') = 6.3e+9000000000000000
	 * Min integer argument: exp('-20723265836946411') = 1.2e-9000000000000000
	 * (Math object integer min/max: Math.exp(709) = 8.2e+307, Math.exp(-745) = 5e-324)
	 *
	 *  exp(Infinity)  = Infinity
	 *  exp(-Infinity) = 0
	 *  exp(NaN)       = NaN
	 *  exp(±0)        = 1
	 *
	 *  exp(x) is non-terminating for any finite, non-zero x.
	 *
	 *  The result will always be correctly rounded.
	 *
	 * @param Decimal|float|int|string $x
	 * @param int|null $sd Significant digits
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function naturalExponential ( 
		$x, 
		$sd = null 
	) : Decimal
	{}

	/**
	 * Return a new Decimal whose value is the natural 
	 * logarithm of $x rounded to $sd significant digits.
	 * 
	 *  ln(-n)        = NaN
	 *  ln(0)         = -Infinity
	 *  ln(-0)        = -Infinity
	 *  ln(1)         = 0
	 *  ln(Infinity)  = Infinity
	 *  ln(-Infinity) = NaN
	 *  ln(NaN)       = NaN
	 *
	 *  ln(n) (n != 1) is non-terminating.
	 *
	 * @param Decimal|float|int|string $x
	 * @param int|null $sd Significant digits
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function naturalLogarithm ( 
		$x, 
		$sd = null 
	) : Decimal
	{}

	/**
	 * Return the value of Decimal $x as ±Infinity 
	 * or NaN.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return string
	 */
	public static function nonFiniteToString (
		$x
	) : string
	{
		$xs = $x->getSign(0);

		if ( $xs === 1 )
		{ return 'Infinity'; }

		if ( $xs === -1 )
		{ return '-Infinity'; }

		return 'NaN';
	}

	/**
	 * Parse the value of a new Decimal $x 
	 * from string $str.
	 *
	 * @param Decimal|float|int|string $x
	 * @param string $str
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function parseDecimal (
		$x,
		string $str
	) : Decimal
	{}

	/**
	 * Parse the value of a new Decimal $x 
	 * from a string $str, which is not a 
	 * decimal value.
	 *
	 * @param Decimal|float|int|string $x
	 * @param string $str
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function parseOther (
		$x,
		string $str
	) : Decimal
	{}

	/**
	 * sin(x) = x - x^3/3! + x^5/5! - ...
	 * |x| < pi/2
	 *
	 * @param DecimalConfig $config
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function sin (
		DecimalConfig $config,
		$x
	) : Decimal
	{}

	/**
	 * Calculate Taylor series for `cos`, `cosh`, 
	 * `sin` and `sinh`.
	 *
	 * @param DecimalConfig $config
	 * @param Decimal|float|int|string $n
	 * @param Decimal|float|int|string $x
	 * @param Decimal|float|int|string $y
	 * @param bool $isHyperbolic
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function taylorSeries (
		DecimalConfig $config,
		$n,
		$x,
		$y,
		$isHyperbolic
	) : Decimal
	{}

	/**
	 * Exponent e must be positive and non-zero.
	 *
	 * @param integer $b
	 * @param integer $e
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function tinyPow (
		int $b,
		int $e
	) : Decimal
	{}

	/**
	 * Return the absolute value of $x reduced to 
	 * less than or equal to half pi.
	 *
	 * @param DecimalConfig $config
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function toLessThanHalfPi (
		DecimalConfig $config,
		$x
	) : Decimal
	{}

	/**
	 * Return the value of Decimal $x as a string 
	 * in base $bo.
	 * 
	 * If the optional $sd argument is present 
	 * include a binary exponent suffix.
	 *
	 * @param Decimal|float|int|string $x
	 * @param [type] $bo
	 * @param integer $sd
	 * @param integer $rm
	 * @since 1.0.0
	 * @return string
	 */
	public static function toStringBinary ( 
		$x, 
		$bo, 
		$sd = null, 
		$rm = null 
	) : string
	{}

	/**
	 * Truncate array to $length limit.
	 *
	 * @param array $arr
	 * @param integer $length
	 * @since 1.0.0
	 * @return array
	 */
	public static function truncate ( array $arr, int $length ) : array
	{
		if ( count($arr) > $length )
		{ return array_slice($arr, 0, $length); }

		return $arr;
	}
}