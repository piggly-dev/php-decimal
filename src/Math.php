<?php
namespace Piggly\Decimal;

use RuntimeException;

/**
 * Handle native PHP Math functions to
 * control data which will be returned.
 * 
 * There "alias" prevent to use all 
 * transformations required in main code
 * at Decimal class.
 * 
 * It also important to ajust global 
 * functions when requiring different
 * permissions.
 *
 * @since 1.0.0
 * @package Piggly\Decimal
 * @subpackage Piggly\Decimal
 * @author Caique Araujo <caique@piggly.com.br>
 */
class Math
{
	/**
	 * Any number type to a float value.
	 *
	 * @param Decimal|double|float|integer $num
	 * @since 1.0.0
	 * @return int
	 */
	public static function asFloat ( $num ) : float
	{ 
		if ( $num instanceof Decimal )
		{ return $num->toFloat(); }

		return \floatval($num); 
	}

	/**
	 * Any number type to an integer value.
	 *
	 * @param Decimal|double|float|integer $num
	 * @since 1.0.0
	 * @return int
	 */
	public static function asInt ( $num ) : int
	{ 
		if ( $num instanceof Decimal )
		{ return $num->toInt(); }

		return \intval($num); 
	}

	/**
	 * Any number type to a string value.
	 *
	 * @param Decimal|double|float|integer $num
	 * @since 1.0.0
	 * @return string
	 */
	public static function asStr ( $num ) : string
	{ 
		if ( $num instanceof Decimal )
		{ return $num->toString(); }

		return \strval($num); 
	}

	/**
	 * Check 5 rounding digits if `repeating` 
	 * is null, 4 otherwise.
	 * 
	 * `repeating == null` if caller is `log` or `pow`,
	 * `repeating != null` if caller is `naturalLogarithm` or `naturalExponential`.
	 *
	 * @param array $d Digits.
	 * @param integer $i Length.
	 * @param integer $rm Rounding mode.
	 * @param bool $repeating
	 * @since 1.0.0
	 * @return bool
	 */
	public static function checkRoundingDigits (
		$d,
		$i,
		$rm,
		$repeating = null
	) : bool
	{
		//  Get the length of the first word of the array d.
		for ( $k = $d[0]; $k >= 10; $k /= 10 )
		{ --$i; }

		// Is the rounding digit in the first word of d?
		if ( --$i < 0 )
		{
			$i += Decimal::LOG_BASE;
			$di = 0;
		}
		else
		{
			$di = static::intCeil(($i+1)/Decimal::LOG_BASE);
			$i %= Decimal::LOG_BASE;
		}

		// i is the index (0 - 6) of the rounding digit.
		// E.g. if within the word 3487563 the first rounding digit is 5,
		// then i = 4, k = 1000, rd = 3487563 % 1000 = 563
		$k = static::intPow(10, Decimal::LOG_BASE - $i);
		$rd = isset($d[$di]) && $d[$di] !== 0 ? $d[$di] % $k : 0;

		if ( \is_null($repeating) )
		{
			if ( $i < 3 )
			{
				if ( $i == 0 )
				{ $rd = ($rd/100) | 0; }
				else if ( $i == 1 )
				{ $rd = ($rd/10) | 0; }

				$r = (
					($rm < 4 && $rd == 99999) || 
					($rm > 3 && $rd == 49999) ||
					$rd == 50000 || 
					$rd == 0
				);
			}
			else 
			{
				$digit = isset($d[$di + 1]) ? ($d[$di + 1] / $k / 100) : 0;

				$r = (
					(($rm < 4 && $rd + 1 == $k) || ($rm > 3 && $rd + 1 == $k / 2)) &&
					(($digit) == static::intPow(10, $i - 2) - 1)
				) ||
				(($rd == $k / 2 || $rd == 0) && (($digit) == 0));
			}
		}
		else
		{
			if ( $i < 4 )
			{
				if ( $i == 0 )
				{ $rd = ($rd/1000) | 0; }
				else if ( $i == 1 )
				{ $rd = ($rd/100) | 0; }
				else if ( $i == 2 )
				{ $rd = ($rd/10) | 0; }

				$r = (($repeating || $rm < 4) && $rd == 9999) 
						|| (!$repeating && $rm > 3 && $rd == 4999);
			}
			else 
			{
				$digit = isset($d[$di + 1]) ? ($d[$di + 1] / $k / 1000) : 0;

				$r = ((($repeating || $rm < 4) && $rd + 1 == $k) 
						|| (!$repeating && $rm > 3 && $rd + 1 == $k / 2)) 
						&& ($digit) == static::intPow(10, $i - 3) - 1;
			}
		}
		
		return $r;
	}

	/**
	 * Convert string of $baseIn to an array of 
	 * numbers of $baseOut.
	 *
	 * Eg. convertBase('255', 10, 16) returns [15, 15].
	 * Eg. convertBase('ff', 16, 10) returns [2, 5, 5].
	 * 
	 * @param string $str
	 * @param integer $baseIn
	 * @param integer $baseOut
	 * @since 1.0.0
	 * @return array
	 */
	public static function convertBase (
		string $str,
		int $baseIn,
		int $baseOut
	) : array
	{
		$arr = [0];
		$i = 0;
		$strL = \strlen($str);

		for (; $i < $strL;)
		{
			for ( $arrL = \count($arr); $arrL--; )
			{ $arr[$arrL] *= $baseIn; }

			$arr[0] += \intval(\strpos(DecimalConfig::NUMERALS, $str[$i++]));

			for ( $j = 0; $j < \count($arr); $j++ )
			{
				if ( $arr[$j] > $baseOut-1 )
				{
					if ( !isset($arr[$j+1]) )
					{ $arr[$j+1] = 0; }

					$arr[$j+1] += \intval($arr[$j] / $baseOut | 0);
					$arr[$j] %= $baseOut;
				}
			}
		}

		return array_reverse($arr);
	}

	/**
	 * Convert an array of digits to a string.
	 *
	 * @param array|null $d
	 * @since 1.0.0
	 * @return string
	 */
	public static function digitsToStr (
		?array $d
	) : string
	{
		$indexOfLastWord = \is_null($d) ? 0 : \count($d)-1;
		$str = '';
		$w = $d[0]??0;

		if ( $indexOfLastWord > 0 )
		{
			$str .= \strval($w);

			for ( $i = 1; $i < $indexOfLastWord; $i++ )
			{
				$ws = $d[$i].'';
				$k = Decimal::LOG_BASE - \strlen($ws);

				if ( $k )
				{ $str .= static::getZeroString($k); }

				$str .= $ws;
			}

			$w = $d[$i];
			$ws = $w.'';
			$k = Decimal::LOG_BASE - \strlen($ws);

			if ( $k )
			{ $str .= static::getZeroString($k); }
		}
		else if ( $w === 0 )
		{ return '0'; }

		// Remove trailing zeros of last w.
		for (; $w != 0 && $w % 10 === 0;) 
		{ $w /= 10; }

		return $str.$w;
	}

	/**
	 * Calculate the base 10 exponent from 
	 * the base 1e7 exponent.
	 * It expects an array with $digits at
	 * Decimal::LOG_BASE ordened.
	 *
	 * @param array $digits
	 * @param integer $e Exponent
	 * @since 1.0.0
	 * @return integer
	 */
	public static function getBase10Exponent ( array $digits, int $e ) : int
	{
		$w = $digits[0];

		for ( $e *= Decimal::LOG_BASE; $w >= 10; $w /= 10 )
		{ $e++; }

		return $e;
	}

	/**
	 * Get precision to $digits.
	 * It expects an array with $digits at
	 * Decimal::LOG_BASE ordened.
	 * 
	 * e.g.
	 * [1250000, 2540000, ...]
	 *
	 * @param array $digits
	 * @since 1.0.0
	 * @return int
	 */
	public static function getPrecision ( array $digits ) : int
	{
		$w = \count($digits)-1;
		$len = $w * Decimal::LOG_BASE+1;

		$w = $digits[$w];

		// Non-zero
		if ( $w )
		{
			// Subtract the number of trailing zeros of the last word
			for (; $w % 10 == 0; $w /= 10) 
			{ $len--; }

			// Add the number of digits of the first word
			for ($w = $digits[0]; $w >= 10; $w /= 10)
			{ $len++; }
		}

		return $len;
	}

	/**
	 * Get an string with $k zeros.
	 *
	 * old style (too slow)
	 * 
	 * for ( ; $k--; )
	 * { $zn .= '0'; }
	 * 
	 * new style
	 * 
	 * str_repeat is faster than a for-loop
	 * 
	 * However, str_repeat becames slow with integers 
	 * greater than 999999999, then, first we decrease 
	 * 999999999 from $i till $i < 999999999, then we
	 * concat 0x999999999 $nin times and concat $i if
	 * is needed. It will give us power to concat
	 * 999999999+5000000000 of zeros.
	 * 
	 * Numbers <= 9...0.000 seconds
	 * Numbers <= 99...0.000 seconds
	 * Numbers <= 999...0.000 seconds
	 * Numbers <= 9999...0.000 seconds
	 * Numbers <= 99999...0.000 seconds
	 * Numbers <= 999999...0.001 seconds
	 * Numbers <= 9999999...0.007 seconds
	 * Numbers <= 99999999...0.037 seconds
	 * Numbers <= 999999999...0.349 seconds
	 * Numbers <= 1999999999...1.670 seconds
	 * Numbers <= 2999999999...2.432 seconds
	 * Numbers <= 3999999999...4.840 seconds
	 * Numbers <= 4999999999...5.420 seconds
	 * Numbers <= 5999999999...6.529 seconds
	 * 
	 * After 5999999999, however, memory usage is too
	 * heavy, should we fix it?
	 * 
	 * @todo 2? Try to be faster when $k is greater than 5999999999.
	 * @param integer $k Quantity of zeros.
	 * @since 1.0.0
	 * @return string
	 */
	public static function getZeroString (
		int $k
	) : string
	{ 
		$zn  = '';

		while ( $k > 999999999 )
		{
			$k = $k > PHP_INT_MAX ? \bcsub($k, 999999999) : $k-999999999;
			$zn .= str_repeat('0', 999999999);
		}
	
		if ( $k != 0 )
		{ $zn .= str_repeat('0', $k); }

		return $zn; 
	}

	/**
	 * Alias to native abs() function.
	 * But, always returning an integer.
	 *
	 * @param mixed $num
	 * @since 1.0.0
	 * @return integer
	 */
	public static function intAbs ( $num ) : int
	{ return \intval(\abs($num)); }

	/**
	 * Alias to native ceil() function.
	 * But, always returning an integer.
	 *
	 * @param float $num
	 * @since 1.0.0
	 * @return integer
	 */
	public static function intCeil ( float $num ) : int
	{ return \intval(\ceil($num)); }

	/**
	 * Alias to native floor() function.
	 * But, always returning an integer.
	 *
	 * @param float $num
	 * @since 1.0.0
	 * @return integer
	 */
	public static function intFloor ( float $num ) : int
	{ return \intval(\floor($num)); }

	/**
	 * Alias to native max() function.
	 * But, always returning an integer.
	 *
	 * @param array $nums
	 * @since 1.0.0
	 * @return integer
	 */
	public static function intMax ( array $nums ) : int
	{ return \intval(\max($nums)); }

	/**
	 * Alias to native max() function.
	 * But, always returning an integer.
	 *
	 * @param array $nums
	 * @since 1.0.0
	 * @return integer
	 */
	public static function intMin ( array $nums ) : int
	{ return \intval(\min($nums)); }

	/**
	 * Alias to native floor() function.
	 * But, always returning an integer.
	 *
	 * @param double|float|integer $base
	 * @param double|float|integer $exp
	 * @since 1.0.0
	 * @return integer
	 */
	public static function intPow ( $base, $exp ) : int
	{ return \intval(\pow($base, $exp)); }

	/**
	 * Throw an exception if $i is not a int32.
	 *
	 * @param mixed $i
	 * @param integer $min
	 * @param integer $max
	 * @since 1.0.0
	 * @return bool TRUE when is int32, throw an Exception when FALSE.
	 * @throws RuntimeException If $i is not a int32.
	 */
	public static function isInt32 ( $i, int $min, int $max ) : bool
	{
		// Handle with zeros
		if ( $i === 0 || $i === '0' || ($i === '-0' && $min <= 0) )
		{ return true; }

		if ( !\is_int($i) || $i < $min || $i > $max )
		{ throw new RuntimeException(\sprintf('`%s` is not a valid int32.', gettype($i))); }

		return true;
	}

	/**
	 * Return if number is odd or not.
	 *
	 * @param float $i
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function isOdd ( $i )
	{ return $i & 1; }

	/**
	 * Enhanced native sqrt() function.
	 * 
	 * sqrt() cannot handle higher scales number,
	 * which can make some decimals calculations
	 * weird (with less precision than required).
	 * 
	 * Other side, bcsqrt() can handle with scale
	 * but cannot handle with float or exponential
	 * values.
	 * 
	 * This function analyzes $num value to determine
	 * if will be better to a bcsqrt() or, if can't, 
	 * do a sqrt() instead.
	 * 
	 * Even we are calling two Decimal functions as
	 * toString() and toNumber() it makes more fast
	 * then only do sqrt() to estimate square root.
	 * 
	 * Since some values may return INF value at
	 * sqrt() but when using bcsqrt() it will return
	 * a regular number.
	 * 
	 * But, even with this approach, sqrt() it is still
	 * the slower method to Decimals and may need some
	 * improvements soon.
	 *
	 * @param Decimal $num
	 * @param integer $scale
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function sqrt ( Decimal $num, int $scale = null ) : Decimal
	{
		$_s = Math::asStr($num);

		if ( \stripos($_s, 'e') === false && \stripos($_s, '.') === false )
		{ $s = \bcsqrt($_s, $scale+5); }
		else
		{ $s = \sqrt($num->toNumber()); }

		return new Decimal($s, $num->_c());
	}
	
	/**
	 * Exponent $e must be positive and non-zero.
	 * It does use times symbol (*) instead pow()
	 *
	 * @param integer $base
	 * @param integer $exp Integer > 0.
	 * @since 1.0.0
	 * @return int
	 */
	public static function tinyPow ( int $base, int $exp ) : int
	{
		$n = $base;

		if ( !($exp > 0) )
		{ throw new RuntimeException('Exponent $e must be positive and non-zero.'); }

		while ( --$exp )
		{ $n *= $base; }
		
		return $n;
	}

	/**
	 * Compare two arrays $a and $b. By checking
	 * if $a has any value greater than $b.
	 *
	 * @param array $a
	 * @param array $b
	 * @param integer $al $a length
	 * @param integer $bl $b length
	 * @since 1.0.0
	 * @return integer
	 */
	public static function compare ( 
		array $a, 
		array $b, 
		int $al, 
		int $bl 
	) : int
	{
		if ( $al != $bl )
		{ $r = $al > $bl ? 1 : -1; }
		else
		{
			for ( $i = $r = 0; $i < $al; $i++ )
			{
				if ( $a[$i] != $b[$i] )
				{
					$r = $a[$i] > $b[$i] ? 1 : -1;
					break;
				}
			}
		}

		return $r;
	}

	/**
	 * Used by divide() function.
	 * Multiplies all integers at $x array by $k adding $carry
	 * and, after, normalize it to $base.
	 * 
	 * Assumes non-zero x and k, and hence non-zero result.
	 *
	 * @param array $x
	 * @param integer $k
	 * @param integer $base
	 * @since 1.0.0
	 * @return array
	 */
	public static function multiplyInt ( 
		array $x, 
		int $k, 
		int $base 
	) : array
	{
		$carry = 0;
		$i = \count($x);

		for ( $x = $x; $i--; )
		{
			$temp = $x[$i] * $k + $carry;
			$x[$i] = $temp % $base | 0;
			$carry = $temp / $base | 0;
		}

		if ( $carry )
		{ \array_unshift($x, $carry); }

		return $x;
	}

	/**
	 * Used by divide() function.
	 * Subtract $b from $a.
	 *
	 * @param array $a
	 * @param array $b
	 * @param integer $al $a length
	 * @param integer $base
	 * @since 1.0.0
	 * @return array
	 */
	public static function subtract ( 
		array $a, 
		array $b, 
		int $al, 
		int $base 
	) : array
	{
		$i = 0;

		for (; $al--;)
		{
			$a[$al] -= $i;
			$i = isset($a[$al], $b[$al]) && $a[$al] < $b[$al] ? 1 : 0;
			$a[$al] = $i * $base + ($a[$al]??0) - ($b[$al]??0);
		}

		for(; !$a[0] && \count($a)-1; )
		{ \array_shift($a); }

		return $a;
	}
}