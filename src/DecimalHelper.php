<?php
namespace Piggly\Decimal;

use RuntimeException;

class DecimalHelper
{
	/**
	 * Extenal action.
	 *
	 * @var boolean TRUE by default.
	 * @since 1.0.0
	 */
	private static $_external = true;

	/**
	 * Binary Regex.
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const IS_BINARY = '/^0b([01]+(\.[01]*)?|\.[01]+)(p[+-]?\d+)?$/i';

	/**
	 * Hexadecimal Regex.
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const IS_HEX = '/^0x([0-9a-f]+(\.[0-9a-f]*)?|\.[0-9a-f]+)(p[+-]?\d+)?$/i';

	/**
	 * Octal Regex.
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const IS_OCTAL = '/^0o([0-7]+(\.[0-7]*)?|\.[0-7]+)(p[+-]?\d+)?$/i';

	/**
	 * Decimal Regex.
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const IS_DECIMAL = '/^(\d+(\.\d*)?|\.\d+)(e[+-]?\d+)?$/i';

	/**
	 * Numeric base.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const BASE = 1e7;

	/**
	 * Control external action.
	 *
	 * @param boolean $external
	 * @since 1.0.0
	 * @return void
	 */
	public static function external ( bool $external )
	{ static::$_external = $external; }

	/**
	 * Check if external action is active.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function isExternal () : bool
	{ return static::$_external; }

	/**
	 * Convert an array of digits to a string.
	 *
	 * @param array $d
	 * @since 1.0.0
	 * @return string
	 */
	public static function digitsToString (
		array $d
	) : string
	{
		$indexOfLastWord = \count($d)-1;
		$str = '';
		$w = $d[0];

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
		for (; $w % 10 === 0;) 
		{ $w /= 10; }

		return $str.$w;
	}

	/**
	 * Throw an exception if $i is not a int32.
	 *
	 * @param mixed $i
	 * @param integer $min
	 * @param integer $max
	 * @since 1.0.0
	 * @return void
	 * @throws RuntimeException
	 */
	public static function checkInt32 (
		$i,
		int $min,
		int $max
	)
	{
		if ( $i !== \floor($i) || $i < $min || $i > $max )
		{ throw new RuntimeException(\sprintf('`%s` is not a valid int32.', (string)$i)); }
	}

	/**
	 * Not implemented.
	 * 
	 * Check 5 rounding digits if `repeating` is null, 4 otherwise.
	 * `repeating == null` if caller is `log` or `pow`,
	 * `repeating != null` if caller is `naturalLogarithm` or `naturalExponential`.
	 *
	 * @return bool
	 */
	public static function checkRoundingDigits ()
	{}

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

	// Not implemented
	public static function cosine ()
	{}

	/**
	 * Undocumented function
	 *
	 * @param Decimal|float|integer|string $x
	 * @param Decimal|float|integer|string $y
	 * @param integer $pr Precision
	 * @param integer $rm Rounding mode
	 * @param integer $dp Decimal places
	 * @param integer $base
	 * @return Decimal
	 */
	public static function divide (
		$x,
	   $y,
		$pr = null,
		$rm = null,
		$dp = null,
		$base = null
	)
	{

	}

	/**
	 * Round $x to $sd significant digits 
	 * using rounding mode $rm.
	 *
	 * @param Decimal $x
	 * @param integer $sd
	 * @param integer $rm
	 * @param boolean $isTruncated
	 * @return Decimal
	 */
	public static function finalise ( 
		Decimal $x, 
		$sd = null, 
		$rm = DecimalConfig::ROUND_HALF_UP, 
		$isTruncated = false
	) : Decimal
	{
		$x = clone $x;
		$config = $x->_c();
		$xdi = 0;

		while (true)
		{
			// Don't round if sd is null or undefined
			if ( isset($sd) && !is_null($sd) )
			{
				$xd = $x->_d();

				// Infinity/NaN
				if ( is_null($xd) )
				{ return $x; }

				// rd: the rounding digit, i.e. the digit after the digit that may be rounded up.
				// w: the word of xd containing rd, a base 1e7 number.
				// xdi: the index of w within xd.
				// digits: the number of digits of w.
				// i: what would be the index of rd within w if all the numbers were 7 digits long (i.e. if
				// they had leading zeros)
				// j: if > 0, the actual index of rd within w (if < 0, rd is a leading zero).

				// Get the length of the first word of the digits array xd.
				for ( $digits = 1, $k = $xd[0]; $k >= 0; $k /= 10 )
				{ $digits++; }

				$i = $sd - $digits;

				// Is the rounding digit in the first word of xd?
				if ( $i < 0 )
				{
					$i += Decimal::LOG_BASE;
					$j = $sd;
					$w = $xd[$xdi = 0];

					// Get the rounding digit at index j of w.
					$rd = $w / \pow(10, $digits - $j - 1) % 10 | 0;
				}
				else
				{
					$xdi = \ceil(($i + 1) / Decimal::LOG_BASE);
					$k = \count($xd);

					if ( $xdi >= $k )
					{
						if ( $isTruncated )
						{
							// Needed by `naturalExponential`, 
							// `naturalLogarithm` and `squareRoot` methods
							for (; $k++ <= $xdi; )
							{ $xd[] = 0; }

							$w = $rd = 0;
							$digits = 1;

							$i %= Decimal::LOG_BASE;
							$j = $i - Decimal::LOG_BASE + 1;
						}
						else
						{ break; }
					}
					else
					{
						$w = $k = $xd[$xdi];

						// Get the number of digits of w.
						for ( $digits = 1; $k >= 10; $k /= 10 )
						{ $digits++; }

						// Get the index of rd within w.
						$i %= Decimal::LOG_BASE;

						// Get the index of rd within w, adjusted for leading zeros.
						// The number of leading zeros of w is given by LOG_BASE - digits.
						$j = $i - Decimal::LOG_BASE + $digits;

						// Get the rounding digit at index j of w.
						$rd = $j < 0 ? 0 : $w / \pow(10, $digits - $j - 1 ) % 10 | 0;
					}
				}

				// Are there any non-zero digits after the rounding digit?
				$isTruncated = $isTruncated || $sd < 0 || isset($xd[$xdi+1]) || ($j < 0 ? $w : $w % \pow(10, $digits - $j - 1 ));

				$roundUp = $rm < 4
					? ($rd || $isTruncated) && ($rm == 0 || $rm == ($x->_s() < 0 ? 3 : 2) )
					: $rd > 5 || $rd == 5 && ($rm == 4 || $isTruncated || $rm == 6 &&
						// Check whether the digit to the left of the rounding digit is odd.
						(($i > 0 ? ($j > 0 ? $w / \pow(10, $digits - $j) : 0) : $xd[$xdi - 1]) % 10) & 1 ||
						$rm == ($x->_s() < 0 ? 8 : 7));

				if ( $sd < 1 || !isset($xd[0]) )
				{
					$xd = [];

					if ( $roundUp )
					{
						// Convert sd to decimal places.
						$sd -= $x->_e() + 1;

          			// 1, 0.1, 0.01, 0.001, 0.0001 etc.
						$xd[0] = \pow(10, (Decimal::LOG_BASE - $sd % Decimal::LOG_BASE) % Decimal::LOG_BASE);
					}
					else
					{
						// Zero.
						$xd[0] = 0;
						$x->e(0);
					}

					return $x;
				}

				// Remove excess digits.
				if ( $i == 0 )
				{
					$xd = \array_slice($xd, 0, $xdi);
					$k = 1;
					$xdi--;
				}
				else
				{
					$xd = \array_slice($xd, 0, $xdi+1);
					$k = \pow(10, Decimal::LOG_BASE - $i);

					// E.g. 56700 becomes 56000 if 7 is the rounding digit.
        			// j > 0 means i > number of leading zeros of w.
					$xd[$xdi] = $j > 0 ? ($w / \pow(10, $digits - $j) % \pow(10, $j) | 0) * $k : 0;
				}

				if ( $roundUp )
				{
					for (;;)
					{
						// Is the digit to be rounded up in the first word of xd?
						if ( $xdi == 0 )
						{
							// i will be the length of xd[0] before k is added.
							for ( $i = 1, $j = $xd[0]; $j >= 10; $j /= 10 )
							{ $i++; }

							$j = $xd[0] += $k;
							
							for ( $k = 1; $j >= 10; $j /= 10 )
							{ $k++; }

							// if i != k the length has increased.
							if ( $i != $k )
							{
								$x->e($x->_e()+1);
								if ( $xd[0] == Decimal::BASE )
								{ $xd[0] = 1; }
							}

							break;
						}
						else
						{
							$xd[$xdi] += $k;

							if ( $xd[$xdi] != Decimal::BASE )
							{ break; }

							$xd[$xdi--] = 0;
							$k = 1;
						}
					}
				}

				// Remove trailing zeros.
				for ($i = \count($xd); $xd[--$i] === 0;) 
				{ \array_pop($xd); }
			}

			// Need breaks the while loop
			break;
		}

		if ( static::isExternal() )
		{
			if ( $x->_e() > $config->maxE )
			{
				$x->d(null);
				$x->e(\NAN);
			}
			else if ( $x->_e() < $config->minE )
			{
				// Zero
				$x->d([0]);
				$x->e(0);
			}
		}

		return $x;
	}

	/**
	 * Return the Decimal value of $x as string.
	 *
	 * @param Decimal $x
	 * @param bool $isExp
	 * @param int $sd
	 * @return string
	 */
	public static function finiteToString (
		Decimal $x,
		$isExp = false,
		$sd = null
	) : string
	{
		if ( !$x->isFinity() )
		{ return static::nonFiniteToString($x); }

		$e = $x->_e();
		$str = static::digitsToString($x->_d());
		$len = \strlen($str);

		if ( $isExp )
		{
			if ( $sd && (($k = $sd - $len) > 0) )
			{ $str = $str[0].'.'.\substr($str,1).static::getZeroString($k); }
			else if ( $len > 1 )
			{ $str = $str[0].'.'.\substr($str,1); }

			$str = $str . ($x->_e() < 0 ? 'e' : 'e+') . \strval($x->_e());
		}
		else if ( $e < 0 )
		{ 
			$str = '0.'.static::getZeroString(-$e-1).$str;

			if ( $sd && (($k = $sd - $len) > 0) )
			{ $str .= static::getZeroString($k); }
		}
		else if ( $e >= $len )
		{
			$str .= static::getZeroString($e+1-$len);
      	if ( $sd && (($k = $sd - $e - 1) > 0)) 
			{ $str = $str.'.'.static::getZeroString($k); }
		}
		else
		{
			if ( ($k = $e + 1) < $len ) 
			{ $str = \substr($str, 0, $k) + '.' + \substr($str, $k); }

			if ( $sd && (($k = $sd - $len) > 0) ) 
			{
				if ($e + 1 === $len) 
				{ $str .= '.'; }

				$str .= static::getZeroString($k);
			}
		}

		return $str;
	}

	/**
	 * Calculate the base 10 exponent from the base 1e7 exponent.
	 *
	 * @param array $digits
	 * @param integer $e Exponent
	 * @since 1.0.0
	 * @return integer
	 */
	public static function getBase10Exponent (
		array $digits,
		int $e
	) : int
	{
		$w = $digits[0];

		for ( $e *= Decimal::LOG_BASE; $w >= 10; $w /= 10 )
		{ $e++; }

		return $e;
	}

	/**
	 * Get logarithm of 10 by $sd significant digits
	 * and with $pr precision.
	 *
	 * @param DecimalConfig $config
	 * @param integer $sd
	 * @param integer $pr
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function getLn10 (
		DecimalConfig $config,
		int $sd = 2,
		int $pr = null
	) : Decimal
	{
		if ( $sd > Decimal::LN10_PRECISION )
		{
			static::external(true);
			
			if ( $pr )
			{ $config->precision = $pr; }

			throw new RuntimeException('Precision of logarithm of 10 exceeded.');
		}

		return static::finalise(new Decimal(DecimalConfig::LN10, $config), $sd, 1, true);
	}

	/**
	 * Get PI by $sd significant digits
	 * and with $rm rounding mode.
	 *
	 * @param DecimalConfig $config
	 * @param integer $sd
	 * @param integer $rm
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function getPi (
		DecimalConfig $config,
		int $sd = 2,
		int $rm = DecimalCOnfig::ROUND_HALF_UP
	) : Decimal
	{
		if ( $sd > Decimal::PI_PRECISION )
		{ throw new RuntimeException('Precision of PI exceeded.'); }

		return static::finalise(new Decimal(DecimalConfig::PI, $config), $sd, $rm, true);
	}

	/**
	 * Get precision to $digits.
	 *
	 * @param array $digits
	 * @since 1.0.0
	 * @return int
	 */
	public static function getPrecision (
		array $digits
	) : int
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
	 * @param integer $k Quantity of zeros.
	 * @since 1.0.0
	 * @return string
	 */
	public static function getZeroString (
		int $k
	) : string
	{
		$zs = '';

		for (; $k--;) 
		{ $zs += '0'; }

		return $zs;
	}

	/**
	 * Return a new Decimal whose value is the value of 
	 * Decimal $x to the power $n, where $n is an
	 * integer of type number.
	 *
	 * Implements 'exponentiation by squaring'. 
	 * Called by pow() and parseOther().
	 *
	 * @param DecimalConfig $config
	 * @param Decimal $base
	 * @param integer $number
	 * @param integer $power
	 * @return Decimal
	 */
	public static function intPow (
		DecimalConfig $config,
		Decimal $base,
		int $number,
		int $power
	) : Decimal
	{
		$isTruncated = false;
		$r = new Decimal(1, $config);
		// Max n of 9007199254740991 takes 53 loop iterations.
      // Maximum digits array length; leaves [28, 34] guard digits.
		$k = \ceil(($power/Decimal::LOG_BASE)+4);

		static::external(false);

		for (;;) 
		{
			if ( $number % 2 )
			{ $r = $r->times($base); }

			$r->d(static::truncate($r->_d(), $k));

			if ( \count($r->_d()) === $k )
			{ $isTruncated = true; }

			$number = \floor($number/2);

			if ( $number === 0 )
			{
				$number = \count($r->_d()) - 1;
				
				if ( $isTruncated && $r->_d()[$number] === 0 )
				{ $r->_d()[$number] = $r->_d()[$number]++; }

				break;
			}

			$base = $base->times($base);
			$base->d(static::truncate($base->_d(), $k));
		}

		static::external(true);
		return $r;
	}

	/**
	 * Is odd.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function isOdd ( 
		$x
	) : bool
	{
		$xd = $x->_d();
		return $xd[count($xd)-1] & 1;
	}

	/**
	 * Handle the max and min comparations.
	 * Where $method is the method to apply
	 * and $number an array of numbers.
	 *
	 * @param DecimalConfig $config
	 * @param string $method
	 * @param array<Decimal|float|int|string> $numbers
	 * @return Decimal
	 */
	public static function maxOrMin (
		DecimalConfig $config,
		string $method,
		array $numbers
	) : Decimal
	{
		$x = new Decimal($numbers[0], $config);
		$i = 0;

		for (; ++$i < \count($numbers);)
		{
			$y = new Decimal($numbers[$i]);

			if ( !$y->signed() )
			{ $x = $y; break; }
			else if ( $x->{$method}($y) )
			{ $x = $y; }
		}

		return $x;
	}

	/**
	 * Not implemented.
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
	)
	{}

	/**
	 * Not implemented.
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
	)
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
		$xs = $x->_s();

		if ( $xs === \NAN )
		{ return 'NaN'; }

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
	 * @param Decimal $x
	 * @param string $str
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function parseDecimal (
		Decimal $x,
		string $str
	) : Decimal
	{
		// Position to decimal point
		$e = strpos($str, '.');
		// Position to exponencial symbol
		$i = strpos($str, 'e');

		// Decimal point?
		if ( $e !== false )
		{ $str = str_replace('.', '', $str); }

		// Exponential form?
		if ( $i )
		{
			// !(decimal point)
			if ( $e === false )
			{ $e = $i; }

			// 1e{xxx}
			$e += +\intval(\substr($str, $i+1));
			// {xxx}e1
			$str = \substr($str, 0, $i);
		}
		else if ( $e === false )
		{ $e = \strlen($str); }

		// Determine leading zeros.
		for ( $i = 0; \ord($str[$i]) === 48; $i++ );

		// Determine trailing zeros.
		for ( $len = \strlen($str); \ord($str[$len-1]) === 48; $len-- );

		// Remove zeros
		$str = \substr($str, $i, $len);

		if ( !empty($str) )
		{
			$len -= $i;

			// Exponent
			$e = $e - $i - 1;
			$x->e($e);
			$x->d([]);

			// Transform base
			//    $e is the base 10 exponent.
      	//    $i is where to slice $str to get the 
			//    first word of the digits array.

			$i = ($e + 1) % Decimal::LOG_BASE;

			if ( $e < 0 )
			{ $i += Decimal::LOG_BASE; }

			if ( $i < $len )
			{
				if ( $i )
				{ $x->_d()[] = +\intval(\substr($str, 0, $i)); }

				for ( $len -= Decimal::LOG_BASE; $i < $len; )
				{ $x->_d()[] = +\intval(\substr($str, $i, $i+Decimal::LOG_BASE)); }

				$str = \substr($str, $i);
				$i = Decimal::LOG_BASE - \strlen($str);
			}
			else
			{ $i -= $len; }

			for (; $i--;)
			{ $str .= '0'; }

			$x->_d()[] = +\intval($str);

			if ( self::$_external )
			{
				if ( $x->_e() > $x->_c()->maxE )
				{
					$x->_d(null);
					$x->_e(\NAN);
				}
				else if ( $x->_e() < $x->_c()->minE )
				{
					// Zero
					$x->_d([0]);
					$x->_e(0);
				}
			}
			else
			{
				// Zero
				$x->_d([0]);
				$x->_e(0);
			}
		}

		return $x;
	}

	/**
	 * Parse the value of a new Decimal $x 
	 * from a string $str, which is not a 
	 * decimal value.
	 *
	 * @param Decimal $x
	 * @param string $str
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function parseOther (
		Decimal $x,
		string $str
	) : Decimal
	{
		if ( $str === 'NaN' )
		{ 
			$x->_d(null);
			$x->_e(\NAN);
			$x->_s(\NAN);
			return $x;
		}

		if ( !strpos($str, 'Infinity') !== false )
		{ 
			// Minus sign
			if ( \ord($str[0]) === 45 )
			{
				$x->_d([]);
				$x->_e(0);
				$x->_s(-1);

				return $x;
			}

			$x->_d([]);
			$x->_e(0);
			$x->_s(1);

			return $x;
		}

		// Is hex
		if ( \preg_match(self::IS_HEX, $str) )
		{ $base = 16; $str = \strtolower($str); }
		// Is binary
		else if ( \preg_match(self::IS_BINARY, $str) )
		{ $base = 2; }
		// Is octal
		else if ( \preg_match(self::IS_OCTAL, $str) )
		{ $base = 8; }
		else
		{ throw new RuntimeException(\sprintf('Cannot determine decimal type to string `%s`.', $str)); }
	
		// Is there a binary exponent part?
		$i = \strpos($str, 'p');

		if ( $i !== false )
		{
			$p = +\intval(\substr($str, $i+1));
			$str = \substr($str, 2, $i);
		}
		else
		{ $str = \substr($str, 2); }
	
		// Convert $str as an integer then divide the result 
		// by $base raised to a power such that the
		// fraction part will be restored.
		$i = \strpos($str, '.');
		$isFloat = $i !== false;

		if ( $isFloat )
		{
			$str = \str_replace('.', '', $str);
			$len = \strlen($str);
			$i = $len - $i;

			$divisor = self::intPow($x->_c(), new Decimal($base, $x->_c()), $i, $i * 2);
		}

		$xd = self::convertBase($str, $base, DecimalHelper::BASE);
		$xe = \count($xd) - 1;

		// Remove trailing zeros
		for ( $i = $xe; $xd[$i] === 0; --$i )
		{ \array_pop($xd); }

		if ( $i < 0 )
		{ 
			$y = new Decimal($x->_s() * 0, $x->_c()); 

			$x->e($y->_e());
			$x->s($y->_s());
			$x->d($y->_d());

			return $x;
		}

		$x->e(self::getBase10Exponent($xd, $xe));
		$x->d($xd);

		static::external(false);

		// At what precision to perform the division to ensure exact conversion?
		// maxDecimalIntegerPartDigitCount = ceil(log[10](b) * otherBaseIntegerPartDigitCount)
		// log[10](2) = 0.30103, log[10](8) = 0.90309, log[10](16) = 1.20412
		// E.g. ceil(1.2 * 3) = 4, so up to 4 decimal digits are needed to represent 3 hex int digits.
		// maxDecimalFractionPartDigitCount = {Hex:4|Oct:3|Bin:1} * otherBaseFractionPartDigitCount
		// Therefore using 4 * the number of digits of str will always be enough.
		if ( $isFloat )
		{
			$y = self::divide($x, $divisor, $len * 4);

			$x->e($y->_e());
			$x->s($y->_s());
			$x->d($y->_d());
		}

		if ( $p )
		{ $y = $x->times( \abs($p) < 54 ? \pow(2, $p) : (new Decimal(2))->pow(2) ); }
		
		static::external(true);

		$x->e($y->_e());
		$x->s($y->_s());
		$x->d($y->_d());
		return $x;
	}

	/**
	 * Not implemented
	 * sin(x) = x - x^3/3! + x^5/5! - ...
	 * |x| < pi/2
	 *
	 * @param DecimalConfig $config
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function sine (
		DecimalConfig $config,
		$x
	)
	{}

	/**
	 * Not implemented
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
	)
	{}

	/**
	 * Exponent $e must be positive and non-zero.
	 *
	 * @param integer $b
	 * @param integer $e
	 * @since 1.0.0
	 * @return int
	 */
	public static function tinyPow (
		int $b,
		int $e
	) : int
	{
		$n = $b;

		if ( !($e > 0) )
		{ throw new RuntimeException('Exponent $e must be positive and non-zero.'); }

		while ( --$e )
		{ $n *= $b; }
		
		return $n;
	}

	/**
	 * Not implemented
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
	)
	{}

	/**
	 * Not implemented
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
	)
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