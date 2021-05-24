<?php
namespace Piggly\Decimal;

use Exception;
use RuntimeException;

class DecimalHelper
{
	/**
	 * Prevent rounding of 
	 * intermediate calculations
	 *
	 * @var boolean TRUE by default.
	 * @since 1.0.0
	 */
	private static $_external = true;

	/**
	 * Prevent rounding of 
	 * intermediate calculations
	 *
	 * @var int TRUE by default.
	 * @since 1.0.0
	 */
	private static $_quadrant = null;

	/**
	 * Inexact operation.
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	private static $_inexact = false;

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
	 * Convert an array of digits to a string.
	 *
	 * @param array|null $d
	 * @since 1.0.0
	 * @return string
	 */
	protected static function __digitsToString (
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
				{ $str .= static::__getZeroString($k); }

				$str .= $ws;
			}

			$w = $d[$i];
			$ws = $w.'';
			$k = Decimal::LOG_BASE - \strlen($ws);

			if ( $k )
			{ $str .= static::__getZeroString($k); }
		}
		else if ( $w === 0 )
		{ return '0'; }

		// Remove trailing zeros of last w.
		for (; $w != 0 && $w % 10 === 0;) 
		{ $w /= 10; }

		return $str.$w;
	}

	// /**
	//  * Throw an exception if $i is not a int32.
	//  *
	//  * @param mixed $i
	//  * @param integer $min
	//  * @param integer $max
	//  * @since 1.0.0
	//  * @return void
	//  * @throws RuntimeException
	//  */
	// protected static function __checkInt32 (
	// 	$i,
	// 	int $min,
	// 	int $max
	// )
	// {
	// 	if ( $i === 0 || $i === '0' || $i === '-0' && $min <= 0 )
	// 	{ return; }

	// 	if ( !\is_int($i) || $i < $min || $i > $max )
	// 	{ throw new RuntimeException(\sprintf('`%s` is not a valid int32.', gettype($i))); }
	// }

	/**
	 * Check 5 rounding digits if `repeating` is null, 4 otherwise.
	 * `repeating == null` if caller is `log` or `pow`,
	 * `repeating != null` if caller is `naturalLogarithm` or `naturalExponential`.
	 *
	 * @param array $d Digits.
	 * @param integer $i Length.
	 * @param integer $rm Rounding mode.
	 * @param bool $repeating
	 * @return bool
	 */
	protected static function __checkRoundingDigits (
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
			$di = (int)\ceil(($i+1)/Decimal::LOG_BASE);
			$i %= Decimal::LOG_BASE;
		}

		// i is the index (0 - 6) of the rounding digit.
		// E.g. if within the word 3487563 the first rounding digit is 5,
		// then i = 4, k = 1000, rd = 3487563 % 1000 = 563
		$k = (int)\pow(10, Decimal::LOG_BASE - $i);
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
					(($digit) == \intval(\pow(10, $i - 2)) - 1)
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
						&& ($digit) == (int)\pow(10, $i - 3) - 1;
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
	protected static function __convertBase (
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
	 * cos(x) = 1 - x^2/2! + x^4/4! - ...
	 * |x| < pi/2
	 *
	 * @param DecimalConfig $c,
	 * @param Decimal $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	protected static function __cosine (
		DecimalConfig $c,
		Decimal $x
	) : Decimal
	{
		$len = \count($x->_d());

		// Argument reduction: cos(4x) = 8*(cos^4(x) - cos^2(x)) + 1
		// i.e. cos(x) = 8*(cos^4(x/4) - cos^2(x/4)) + 1

		// Estimate the optimum number of times to use the argument reduction.
		if ( $len < 32 )
		{
			$k = \ceil($len/3);
			$y = \strval((1 / static::__tinyPow(4, $k)));
		}
		else
		{
			$k = 16;
			$y = '2.3283064365386962890625e-10';
		}

		$c->precision += $k;
		$x = static::__taylorSeries($c, 1, $x->times($y), new Decimal(1, $c));

		// Reverse argument reduction
		for ( $i = $k; $i--; )
		{
			$cos2x = $x->times($x);
			$x = $cos2x->times($cos2x)->minus($cos2x)->times(8)->plus(1);
		}

		$c->precision -= $k;
		return $x;
	}

	// /**
	//  * Perform division in the specified base.
	//  *
	//  * @param Decimal|float|integer|string $x
	//  * @param Decimal|float|integer|string $y
	//  * @param integer $pr Precision
	//  * @param integer $rm Rounding mode
	//  * @param integer $dp Decimal places
	//  * @param integer $base
	//  * @since 1.0.0
	//  * @return Decimal
	//  */
	// protected static function __divide (
	// 	$x,
	//    $y,
	// 	$pr = null,
	// 	$rm = null,
	// 	$dp = null,
	// 	$base = null
	// )
	// {
	// 	$c = $x->_c();
	// 	$sign = $x->_s() === $y->_s() ? 1 : -1;
	// 	$xd = $x->_d();
	// 	$yd = $y->_d();

	// 	// If either is NaN, ±Infinity or ±0...
	// 	if ( empty($xd) || empty($yd) || !$xd[0] || !$yd[0] )
	// 	{
	// 		// NaN or ±0
	// 		if ( !$x->signed() || !$y->signed() || ($xd ? $yd && $xd[0] == $yd[0] : !$yd) )
	// 		{ return new Decimal(\NAN, $c); }

	// 		// ±Infinity
	// 		if ( $xd && $xd[0] == 0 || empty($yd) )
	// 		{ 
	// 			$num = $sign > 0 ? '0' : '-0';
	// 			return new Decimal($num, $c); 
	// 		}

	// 		return new Decimal($sign > 0 ? \INF : -\INF, $c);
	// 	}

	// 	if ( $base )
	// 	{
	// 		$logBase = 1;
	// 		$e = $x->_e() - $y->_e();
	// 	}
	// 	else
	// 	{
	// 		$base = Decimal::BASE;
	// 		$logBase = Decimal::LOG_BASE;
	// 		$e = \intval(\floor($x->_e() / $logBase) - \floor($y->_e() / $logBase));
	// 	}

	// 	$yl = \count($yd);
   //    $xl = \count($xd);
   //    $q = new Decimal($sign, $c);
   //    $qd = [];

	// 	// Result exponent may be one less than e.
   //    // The digit array of a Decimal from 
	// 	// toStringBinary may have trailing zeros.
	// 	for ( $i = 0; isset($yd[$i]) && $yd[$i] == ($xd[$i] ?? 0); $i++ );

   //    if (isset($yd[$i]) && $yd[$i] > ($xd[$i] ?? 0)) 
	// 	{ $e--; }

   //    if ( \is_null($pr) ) 
	// 	{
	// 		$sd = $pr = $c->precision;
	// 		$rm = $c->rounding;
   //    } 
	// 	else if ($dp) 
	// 	{ $sd = $pr + ($x->_e() - $y->_e()) + 1; } 
	// 	else 
	// 	{ $sd = $pr; }

	// 	$more = false;

	// 	if ( $sd < 0 )
	// 	{
	// 		$qd[] = 1;
	// 		$more = true;
	// 	}
	// 	else
	// 	{
	// 		// Convert precision in number of base 
	// 		// 10 digits to base 1e7 digits
	// 		$sd = $sd / $logBase + 2 | 0;
	// 		$i = 0;

	// 		// divisor < 1e7
	// 		if ( $yl == 1 )
	// 		{
	// 			$k = 0;
	// 			$yd = $yd[0];
	// 			$sd++;

	// 			// k is the carry
	// 			for (; ( $i < $xl || $k ) && $sd--; $i++ )
	// 			{
	// 				$t = $k * $base + \intval($xd[$i] ?? 0);
	// 				$qd[$i] = $t / $yd | 0;
	// 				$k = $t % $yd | 0;
	// 			}

	// 			$more = $k || $i < $xl;
	// 		}
	// 		// divisor >= 1e7
	// 		else
	// 		{
	// 			// Normalise xd and yd so highest 
	// 			// order digit of yd is >= base/2
	// 			$k = $base / ($yd[0] + 1) | 0;

	// 			if ( $k > 1 ) 
	// 			{
	// 				$yd = static::__multiplyInteger($yd, $k, $base);
	// 				$xd = static::__multiplyInteger($xd, $k, $base);
	// 				$yl = \count($yd);
	// 				$xl = \count($xd);
	// 			}

	// 			$xi = $yl;

	// 			$rem = Utils::sliceArray($xd, 0, $yl);
	// 			$reml = \count($rem);

	// 			// Add zeros to make remainder as long as divisor.
	// 			for (; $reml < $yl;) 
	// 			{ $rem[$reml++] = 0; }

	// 			$yz = $yd;
	// 			\array_unshift($yz, 0);
	// 			$yd0 = $yd[0];

	// 			if ( $yd[1] >= $base / 2 )
	// 			{ ++$yd0; }

	// 			do
	// 			{
	// 				$k = 0;
					
	// 				// Compare divisor and remainder
	// 				$cmp = static::__compare($yd, $rem, $yl, $reml);

	// 				// If divisor < remainder
	// 				if ( $cmp < 0 )
	// 				{
	// 					// Calculate trial digit, k.
	// 					$rem0 = $rem[0];

	// 					if ( $yl != $reml ) 
	// 					{ $rem0 = $rem0 * $base + ($rem[1] ?? 0); }

	// 					// k will be how many times the divisor goes into the current remainder.
	// 					$k = $rem0 / $yd0 | 0;

	// 					//  Algorithm:
	// 					//  1. product = divisor * trial digit (k)
	// 					//  2. if product > remainder: product -= divisor, k--
	// 					//     3. remainder -= product
	// 					//  4. if product was < remainder at 2:
	// 					//     5. compare new remainder and divisor
	// 					//     6. If remainder > divisor: remainder -= divisor, k++

	// 					if ($k > 1) 
	// 					{
	// 						if ( $k >= $base ) 
	// 						{ $k = $base - 1; }

	// 						// product = divisor * trial digit.
	// 						$prod = static::__multiplyInteger($yd, $k, $base);
	// 						$prodl = \count($prod);
	// 						$reml = \count($rem);

	// 						// Compare product and remainder.
	// 						$cmp = static::__compare($prod, $rem, $prodl, $reml);

	// 						// product > remainder.
	// 						if ($cmp == 1) 
	// 						{
	// 							$k--;

	// 							// Subtract divisor from product.
	// 							$prod = static::__subtract($prod, $yl < $prodl ? $yz : $yd, $prodl, $base);
	// 						}
	// 					} 
	// 					else 
	// 					{
	// 						// cmp is -1.
	// 						// If k is 0, there is no need to compare yd and rem again below, so change cmp to 1
	// 						// to avoid it. If k is 1 there is a need to compare yd and rem again below.
	// 						if ( $k == 0 )
	// 						{ $cmp = $k = 1; }

	// 						$prod = $yd;
	// 					}

	// 					$prodl = \count($prod);

	// 					if ($prodl < $reml)
	// 					{ \array_unshift($prod, 0); }

	// 					// Subtract product from remainder.
	// 					$rem = static::__subtract($rem, $prod, $reml, $base);

	// 					// If product was < previous remainder.
	// 					if ( $cmp == -1 ) 
	// 					{
	// 						$reml = \count($rem);

	// 						// Compare divisor and new remainder.
	// 						$cmp = static::__compare($yd, $rem, $yl, $reml);

	// 						// If divisor < new remainder, subtract divisor from remainder.
	// 						if ( $cmp < 1 ) 
	// 						{
	// 							$k++;

	// 							// Subtract divisor from remainder.
	// 							$rem = static::__subtract($rem, $yl < $reml ? $yz : $yd, $reml, $base);
	// 						}
	// 					}

	// 					$reml = \count($rem);
	// 				} 
	// 				else if ($cmp === 0) 
	// 				{
	// 					$k++;
	// 					$rem = [0];
	// 				}
					
	// 				// if cmp === 1, k will be 0
	// 				// Add the next digit, k, to the result array.
	// 				$qd[$i++] = $k;

	// 				// Update the remainder.
	// 				if ($cmp && $rem[0]) 
	// 				{ $rem[$reml++] = $xd[$xi] ?? 0; } 
	// 				else 
	// 				{
	// 					$rem = isset($xd[$xi]) ? [$xd[$xi]] : [];
	// 					$reml = 1;
	// 				}

	// 			} 
	// 			while (($xi++ < $xl || isset($rem[0])) && $sd--);

	// 			$more = isset($rem[0]);
	// 		}

	// 		// Leading zero?
	// 		if ( !$qd[0] )
	// 		{ \array_shift($qd); }
	// 	}

	// 	// logBase is 1 when divide is being used for base conversion.
	// 	if ( $logBase == 1 )
	// 	{
	// 		$q->d($qd);
	// 		$q->e($e);
	// 		static::$_inexact = $more;
	// 	}
	// 	else
	// 	{
	// 		// To calculate q.e, first get the number of digits of qd[0].
	// 		for ($i = 1, $k = $qd[0]; $k >= 10; $k /= 10) 
	// 		{ $i++; }

	// 		$q->d($qd);
	// 		$q->e($i + $e * $logBase - 1);
	// 		$qy = static::finalise($q, $dp ? $pr + $q->_e() + 1 : $pr, $rm, $more);

	// 		$q->e($qy->_e());
	// 		$q->d($qy->_d());
	// 		$q->s($qy->_s());
	// 	}

	// 	return $q;
	// }

	/**
	 * Round $x to $sd significant digits 
	 * using rounding mode $rm.
	 *
	 * @param Decimal $x
	 * @param integer|float $sd
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
		$x = Decimal::clone($x);
		$config = $x->_c();
		$xdi = 0;

		while (true)
		{
			// Don't round if sd is null or undefined
			if ( isset($sd) && !is_null($sd) )
			{
				$xd = $x->_d();

				// Infinity/NaN
				if ( !$x->isFinite() || $x->isNaN() )
				{ return $x; }

				// rd: the rounding digit, i.e. the digit after the digit that may be rounded up.
				// w: the word of xd containing rd, a base 1e7 number.
				// xdi: the index of w within xd.
				// digits: the number of digits of w.
				// i: what would be the index of rd within w if all the numbers were 7 digits long (i.e. if
				// they had leading zeros)
				// j: if > 0, the actual index of rd within w (if < 0, rd is a leading zero).

				// Get the length of the first word of the digits array xd.
				for ( $digits = 1, $k = $xd[0]; $k >= 10; $k /= 10 )
				{ $digits++; }

				$i = $sd - $digits;

				// Is the rounding digit in the first word of xd?
				if ( $i < 0 )
				{
					$i += Decimal::LOG_BASE;
					$j = $sd;
					$w = $xd[($xdi = 0)];

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
				// EXPRESSION ERROR
				$isTruncated = $isTruncated || $sd < 0 || isset($xd[$xdi+1]) || ($j < 0 ? $w : $w % \pow(10, $digits - $j - 1 ));

				$roundUp =
					$rm < 4 ?
					($rd || $isTruncated) && ($rm == 0 || $rm == ($x->_s() < 0 ? 3 : 2)) :
					$rd > 5 ||
					($rd == 5 &&
						($rm == 4 ||
							$isTruncated ||
							($rm == 6 &&
								// Check whether the digit to the left of the rounding digit is odd.
								($i > 0 ?
									($j > 0 ?
									$w / \pow(10, $digits - $j) :
									0) :
									$xd[$xdi - 1]??0) %
								10 &
								1) ||
							$rm == ($x->_s() < 0 ? 8 : 7)));

				if ( $sd < 1 || !isset($xd[0]) )
				{
					$xd = [];

					if ( $roundUp )
					{
						// Convert sd to decimal places.
						$sd -= $x->_e() + 1;

          			// 1, 0.1, 0.01, 0.001, 0.0001 etc.
						$xd[0] = \pow(10, (Decimal::LOG_BASE - $sd % Decimal::LOG_BASE) % Decimal::LOG_BASE);
						$x->e(-$sd??0);
					}
					else
					{
						// Zero.
						$xd[0] = 0;
						$x->e(0);
					}

					$x->d($xd);
					return $x;
				}

				// Remove excess digits.
				if ( $i == 0 )
				{
					$xd = Utils::sliceArray($xd, 0, $xdi);
					$k = 1;
					$xdi--;
				}
				else
				{
					$xd = Utils::sliceArray($xd, 0, $xdi+1);
					$k = \pow(10, Decimal::LOG_BASE - $i);

					// E.g. 56700 becomes 56000 if 7 is the rounding digit.
        			// j > 0 means i > number of leading zeros of w.
					$xd[$xdi] = $j > 0 ? ($w / \pow(10, $digits - $j) % \pow(10, $j) | 0) * $k : 0;
				}

				if ( $roundUp )
				{
					while ( true )
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
				for ($i = \count($xd); empty($xd[--$i]);) 
				{ \array_pop($xd); }

				$x->d($xd);
			}

			// Need breaks the while loop
			break;
		}

		if ( static::$_external )
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
	protected static function __finiteToString (
		Decimal $x,
		$isExp = false,
		$sd = null
	) : string
	{
		if ( !$x->isFinite() )
		{ return static::__nonFiniteToString($x); }

		$e = $x->_e();
		$str = static::__digitsToString($x->_d());
		$len = \strlen($str);

		if ( $isExp )
		{
			if ( $sd && (($k = $sd - $len) > 0) )
			{ $str = $str[0].'.'.Utils::sliceStr($str,1).static::__getZeroString($k); }
			else if ( $len > 1 )
			{ $str = $str[0].'.'.Utils::sliceStr($str,1); }

			$str = $str . ($x->_e() < 0 ? 'e' : 'e+') . \strval($x->_e());
		}
		else if ( $e < 0 )
		{ 
			$str = '0.'.static::__getZeroString(-$e-1).$str;

			if ( $sd && (($k = $sd - $len) > 0) )
			{ $str .= static::__getZeroString($k); }
		}
		else if ( $e >= $len )
		{
			$str .= static::__getZeroString($e+1-$len);
      	if ( $sd && (($k = $sd - $e - 1) > 0)) 
			{ $str = $str.'.'.static::__getZeroString($k); }
		}
		else
		{
			if ( ($k = $e + 1) < $len ) 
			{ $str = Utils::sliceStr($str, 0, $k).'.'.Utils::sliceStr($str, $k); }

			if ( $sd && (($k = $sd - $len) > 0) ) 
			{
				if ($e + 1 === $len) 
				{ $str .= '.'; }

				$str .= static::__getZeroString($k);
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
	protected static function __getBase10Exponent (
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
	protected static function __getLn10 (
		DecimalConfig $config,
		int $sd = 2,
		int $pr = null
	) : Decimal
	{
		if ( $sd > Decimal::LN10_PRECISION )
		{
			static::$_external = true;;
			
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
	protected static function __getPi (
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
	protected static function __getPrecision (
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
	 * @todo Need to test performance.
	 * @todo ? Try to be faster when $k is greater than 5999999999.
	 * @param integer $k Quantity of zeros.
	 * @since 1.0.0
	 * @return string
	 */
	protected static function __getZeroString (
		int $k
	) : string
	{ 
		$zn  = '';

		// old style (too slow):
		// for ( ; $k--; )
		// { $zn .= '0'; }

		// new style:

		// str_repeat is faster than a for-loop
		
		// However, str_repeat becames slow with integers 
		// greater than 999999999, then, first we decrease 
		// 999999999 from $i till $i < 999999999, then we
		// concat 0x999999999 $nin times and concat $i if
		// is needed. It will give us power to concat
		// 999999999+5000000000 of zeros.

		// Numbers <= 9...0.000 seconds
		// Numbers <= 99...0.000 seconds
		// Numbers <= 999...0.000 seconds
		// Numbers <= 9999...0.000 seconds
		// Numbers <= 99999...0.000 seconds
		// Numbers <= 999999...0.001 seconds
		// Numbers <= 9999999...0.007 seconds
		// Numbers <= 99999999...0.037 seconds
		// Numbers <= 999999999...0.349 seconds
		// Numbers <= 1999999999...1.670 seconds
		// Numbers <= 2999999999...2.432 seconds
		// Numbers <= 3999999999...4.840 seconds
		// Numbers <= 4999999999...5.420 seconds
		// Numbers <= 5999999999...6.529 seconds

		// After 5999999999, however, memory usage is too
		// heavy, should we fix it?

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
	protected static function __intPow (
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
		$k = \intval(\ceil(($power/Decimal::LOG_BASE)+4));

		static::$_external = false;

		while ( true )
		{
			if ( $number % 2 === 1 )
			{ 
				$r = $r->times($base); 
				
				$r->d(static::__truncate($r->_d(), $k));

				if ( \count($r->_d()) === $k )
				{ $isTruncated = true; }
			}

			$number = (int)\floor($number/2);

			if ( $number === 0 )
			{
				$number = \count($r->_d()) - 1;
				$rd = $r->_d();
				
				if ( $isTruncated && $rd[$number] === 0 )
				{ $rd[$number] = $rd[$number]++; }

				$r->d($rd);
				break;
			}

			$base = $base->times($base);
			$base->d(static::__truncate($base->_d(), $k));
		}

		static::$_external = true;
		return $r;
	}

	/**
	 * Is odd.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return boolean
	 */
	protected static function __isOdd ( 
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
	protected static function __maxOrMin (
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
	 * @param Decimal $x
	 * @param int|null $sd Significant digits
	 * @since 1.0.0
	 * @return Decimal
	 */
	protected static function __naturalExponential ( 
		Decimal $x, 
		$sd = null 
	)
	{
		$rep = 0;
		$i = 0;
		$k = 0;
		$c = $x->_c();
		$rm = $c->rounding;
		$pr = $c->precision;

		// 0/NaN/Infinity?
		if ( !$x->isFinite() || $x->isNaN() || $x->isZero() || $x->_e() > 17 )
		{
			$n = 0;

			if ( $x->isFinite() )
			{
				if ( !$x->_d()[0] )
				{ $n = 1; }
				else if ( $x->_s() < 0 )
				{ $n = 0; }
				else 
				{ $n = \INF; }
			}
			else if ( !$x->isNaN() )
			{
				if ( $x->_s() < 0 )
				{ $n = 0; }
				else 
				{ $n = $x; }
			}
			else
			{ $n = \NAN; }
			
			return new Decimal($n, $c);
		}

		if ( \is_null($sd) )
		{
			static::$_external = false;
			$wpr = $pr;
		}
		else 
		{ $wpr = $sd; }

		$t = new Decimal(0.03125, $c);

		// while abs(x) >= 0.1
		while ( $x->_e() > -2 )
		{
			// x = x / 2^5
			$x = $x->times($t);
			$k += 5;
		}

		// Use 2 * log10(2^k) + 5 (empirically derived) 
		// to estimate the increase in precision
		// necessary to ensure the first 4 rounding 
		// digits are correct.
		$guard = ((\log(\pow(2, $k))/\M_LN10) * 2 + 5 ) | 0;

		$wpr += $guard;

		$denominator = $pow = $sum = new Decimal(1, $c);
		$c->precision = $wpr;

		for ( ;; )
		{
			$pow = static::finalise($pow->times($x), $wpr, 1);
			$denominator = $denominator->times(++$i);
			$t = $sum->plus(Math::div($pow, $denominator, $wpr, 1));

			if ( 
				Utils::sliceStr(static::__digitsToString($t->_d()), 0, $wpr) 
				=== Utils::sliceStr(static::__digitsToString($sum->_d()), 0, $wpr) 
			)
			{
				$j = $k;

				while ( $j-- ) 
				{ $sum = static::finalise($sum->times($sum), $wpr, 1); }

				// Check to see if the first 4 rounding digits are [49]999.
				// If so, repeat the summation with a higher precision, otherwise
				// e.g. with precision: 18, rounding: 1
				// exp(18.404272462595034083567793919843761) = 98372560.1229999999 (should be 98372560.123)
				// `wpr - guard` is the index of first rounding digit.

				if ( \is_null($sd) )
				{
					if ( $rep < 3 && static::__checkRoundingDigits($sum->_d(), $wpr - $guard, $rm, $rep) )
					{
						$c->precision = $wpr += 10;
						$denominator = $pow = $t = new Decimal(1,$c);
						$i = 0;
						$rep++;
					}
					else
					{ 
						static::$_external = true;
						return static::finalise($sum, ($c->precision = $pr), $rm, true);
					}
				}
				else
				{
					$c->precision = $pr;
					return $sum;
				}
			}
			
			$sum = $t;
		}
	}

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
	 * @param Decimal|float|int|string $y
	 * @param int|null $sd Significant digits
	 * @since 1.0.0
	 * @return Decimal
	 */
	protected static function __naturalLogarithm ( 
		Decimal $y, 
		$sd = null 
	)
	{
		$x = $y;
		$n = 1;
		$guard = 10;
		$xd = $x->_d();
		$c = $x->_c();
		$rm = $c->rounding;
		$pr = $c->precision;

		// Is x negative or Infinity, NaN, 0 or 1?
		if ( 
			$x->isNegative() 
			|| !$x->isFinite() 
			|| $x->isNaN() 
			|| $x->isZero() 
			|| (($xd[0]??null) === 1 && \count($xd) === 1) 
		)
		{
			$n = 0;

			if ( $x->isZero() )
			{ $n = -\INF; }
			else if ( $x->_s() !== 1 )
			{ $n = \NAN; }
			else if ( $x->isFinite() )
			{ $n = 0; }
			else
			{ $n = $x; }
			
			return new Decimal($n, $c);
		}

		if ( \is_null($sd) )
		{
			static::$_external = false;
			$wpr = $pr;
		}
		else 
		{ $wpr = $sd; }

		$c->precision = $wpr += $guard;
		$ds = static::__digitsToString($xd);
		$ds0 = (int)$ds[0];
		
		if ( \abs(($e = $x->_e())) < 1.5e15 )
		{
			// Argument reduction.
			// The series converges faster the closer 
			// the argument is to 1, so using
			// ln(a^b) = b * ln(a),   ln(a) = ln(a^b) / b
			// multiply the argument by itself until the 
			// leading digits of the significand are 7, 8, 9,
			// 10, 11, 12 or 13, recording the number of 
			// multiplications so the sum of the series can
			// later be divided by this number, then separate
			// out the power of 10 using
			// ln(a*10^b) = ln(a) + b*ln(10).

			// max n is 21 (gives 0.9, 1.0 or 1.1) (9e15 / 21 = 4.2e14).
			// max n is 6 (gives 0.7 - 1.3)
			while ( ($ds0 < 7 && $ds0 != 1) || ( $ds0 == 1 && (int)($ds[1]??0) > 3 ) )
			{
				$x = $x->times($y);
				$ds = static::__digitsToString($x->_d());
				$ds0 = (int)$ds[0];
				$n++;
			}

			$e = $x->_e();

			if ( $ds0 > 1 )
			{
				$x = new Decimal('0.'.$ds, $c);
				$e++;
			}
			else
			{ $x = new Decimal($ds0.'.'.Utils::sliceStr($ds,1), $c); }
		}
		else 
		{
			// The argument reduction method above may result in overflow if the argument y is a massive
			// number with exponent >= 1500000000000000 (9e15 / 6 = 1.5e15), so instead recall this
			// function using ln(x*10^e) = ln(x) + e*ln(10).
			$t = (static::__getLn10($c, $wpr+2, $pr))->times($e??'NAN'.'');
			$x = static::__naturalLogarithm(new Decimal($ds0.'.'.Utils::sliceStr($ds,1), $c), $wpr-$guard)->plus($t);
			$c->precision = $pr;

			if ( \is_null($sd) )
			{
				static::$_external = true;
				return static::finalise($x, $pr, $rm, true);
			}

			return $x;
		}

		// x1 is x reduced to a value near 1.
		$x1 = $x;
		
		// Taylor series.
		// ln(y) = ln((1 + x)/(1 - x)) = 2(x + x^3/3 + x^5/5 + x^7/7 + ...)
		// where x = (y - 1)/(y + 1)    (|x| < 1)
		$sum = $numerator = $x = Math::div($x->minus(1), $x->plus(1), $wpr, 1);
		$x2 = static::finalise($x->times($x), $wpr, 1);
		$denominator = 3;

		while ( true )
		{
			$numerator = static::finalise($numerator->times($x2), $wpr, 1);
			$t = $sum->plus(Math::div($numerator, new Decimal($denominator,$c), $wpr, 1));

			if ( 
				Utils::sliceStr(static::__digitsToString($t->_d()), 0, $wpr) 
				===  Utils::sliceStr(static::__digitsToString($sum->_d()), 0, $wpr) 
			)
			{
				$sum = $sum->times(2);

				// Reverse the argument reduction. Check that e is 
				// not 0 because, besides preventing an unnecessary 
				// calculation, -0 + 0 = +0 and to ensure correct 
				// rounding -0 needs to stay -0.
				if ( $e !== 0 )
				{ $sum = $sum->plus(static::__getLn10($c, $wpr+2, $pr)->times($e.'')); }

				$sum = Math::div($sum, new Decimal($n, $c), $wpr, 1 );

				// Is rm > 3 and the first 4 rounding digits 4999, 
				// or rm < 4 (or the summation has been repeated previously) 
				// and the first 4 rounding digits 9999?
				// If so, restart the summation with a higher precision, otherwise
				// e.g. with precision: 12, rounding: 1
				// ln(135520028.6126091714265381533) = 18.7246299999
				// when it should be 18.72463.
				// `wpr - guard` is the index of first rounding digit.
				if ( \is_null($sd) )
				{
					if ( static::__checkRoundingDigits($sum->_d(), $wpr - $guard, $rm, $rep??null) )
					{
						$c->precision = $wpr += $guard;
						$t = $numerator = $x = Math::div($x1->minus(1), $x1->plus(1), $wpr, 1);
						$x2 = static::finalise($x->times($x), $wpr, 1);
						$denominator = $rep = 1;
					}
					else
					{ 
						static::$_external = true;
						return static::finalise($sum, ($c->precision = $pr), $rm, true);
					}
				}
				else
				{
					$c->precision = $pr;
					return $sum;
				}
			}

			$sum = $t;
			$denominator += 2;
		}
	}

	/**
	 * Return the value of Decimal $x as ±Infinity 
	 * or NaN.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return string
	 */
	protected static function __nonFiniteToString (
		$x
	) : string
	{ return $x->isNaN() ? 'NAN' : 'INF'; }

	/**
	 * Parse the value of a new Decimal $x 
	 * from string $str.
	 *
	 * @param Decimal $x
	 * @param string $str
	 * @since 1.0.0
	 * @return Decimal
	 */
	protected static function __parseDecimal (
		Decimal $x,
		string $str
	) : Decimal
	{
		// Position to decimal point
		$e = \strpos($str, '.');

		// Decimal point?
		if ( $e !== false )
		{ $str = \str_replace('.', '', $str); }
		
		// Position to exponencial symbol
		$i = \stripos($str, 'e');

		// Exponential form?
		if ( $i )
		{
			// !(decimal point)
			if ( $e === false )
			{ $e = $i; }

			// 1e{xxx}
			$e += +\intval(Utils::sliceStr($str, $i+1));
			// {xxx}e1
			$str = Utils::sliceStr($str, 0, $i);
		}
		else if ( $e === false )
		{ $e = \strlen($str); }

		// Determine leading zeros.
		for ( $i = 0; isset($str[$i]) && \ord($str[$i]) === 48; $i++ );

		// Determine trailing zeros.
		for ( $len = \strlen($str); $len > 0 && \ord($str[$len-1]) === 48; --$len );

		// Remove zeros
		$str = Utils::sliceStr($str, $i, $len);

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
				{ $x->dpush(+\intval(Utils::sliceStr($str, 0, $i))); }

				for ( $len -= Decimal::LOG_BASE; $i < $len; )
				{ $x->dpush(+\intval(Utils::sliceStr($str, $i, $i+=Decimal::LOG_BASE))); }

				$str = Utils::sliceStr($str, $i);
				$i = Decimal::LOG_BASE - \strlen($str);
			}
			else
			{ $i -= $len; }

			for (; $i--;)
			{ $str .= '0'; }

			$x->dpush(+\intval($str));

			if ( static::$_external )
			{
				if ( $x->_e() > $x->_c()->maxE )
				{
					$x->d(null);
					$x->e(\NAN);
				}
				else if ( $x->_e() < $x->_c()->minE )
				{
					// Zero
					$x->d([0]);
					$x->e(0);
				}
			}
		}
		else
		{
			// Zero
			$x->d([0]);
			$x->e(0);
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
	protected static function __parseOther (
		Decimal $x,
		string $str
	) : Decimal
	{
		if ( $str === 'NAN' || $str === 'INF' )
		{ 
			if ( $str === 'NAN' )
			{ $x->s(\NAN); }

			$x->d(null);
			$x->e(\NAN);
			return $x;
		}

		// Is hex
		if ( \preg_match(static::IS_HEX, $str) )
		{ $base = 16; $str = \strtolower($str); }
		// Is binary
		else if ( \preg_match(static::IS_BINARY, $str) )
		{ $base = 2; }
		// Is octal
		else if ( \preg_match(static::IS_OCTAL, $str) )
		{ $base = 8; }
		else
		{ throw new RuntimeException(\sprintf('Cannot determine decimal type to string `%s`.', $str)); }
	
		// Is there a binary exponent part?
		$i = \strpos($str, 'p');
		$p = null;

		if ( $i !== false )
		{
			$p = +\intval(Utils::sliceStr($str, $i+1));
			$str = Utils::sliceStr($str, 2, $i);
		}
		else
		{ $str = Utils::sliceStr($str, 2); }
	
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

			$divisor = static::__intPow($x->_c(), new Decimal($base, $x->_c()), $i, $i * 2);
		}

		$xd = static::__convertBase($str, $base, static::BASE);
		$xe = \count($xd) - 1;

		// Remove trailing zeros
		for ( $i = $xe; $i >= 0 && $xd[$i] === 0; --$i )
		{ \array_pop($xd); }

		if ( $i < 0 )
		{ 
			$y = new Decimal($x->_s() * 0, $x->_c()); 

			$x->e($y->_e());
			$x->s($y->_s());
			$x->d($y->_d());

			return $x;
		}

		$x->e(static::__getBase10Exponent($xd, $xe));
		$x->d($xd);

		static::$_external = false;

		// At what precision to perform the division to ensure exact conversion?
		// maxDecimalIntegerPartDigitCount = ceil(log[10](b) * otherBaseIntegerPartDigitCount)
		// log[10](2) = 0.30103, log[10](8) = 0.90309, log[10](16) = 1.20412
		// E.g. ceil(1.2 * 3) = 4, so up to 4 decimal digits are needed to represent 3 hex int digits.
		// maxDecimalFractionPartDigitCount = {Hex:4|Oct:3|Bin:1} * otherBaseFractionPartDigitCount
		// Therefore using 4 * the number of digits of str will always be enough.
		if ( $isFloat )
		{
			$y = Math::div($x, $divisor, $len * 4);

			$x->e($y->_e());
			$x->s($y->_s());
			$x->d($y->_d());
		}

		if ( $p )
		{ 
			$y = $x->times( \abs($p) < 54 ? \pow(2, $p) : (new Decimal(2))->pow(2) ); 

			$x->e($y->_e());
			$x->s($y->_s());
			$x->d($y->_d());
		}
		
		static::$_external = true;
		return $x;
	}

	/**
	 * sin(x) = x - x^3/3! + x^5/5! - ...
	 * |x| < pi/2
	 *
	 * @param DecimalConfig $c
	 * @param Decimal $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	protected static function __sine (
		DecimalConfig $c,
		Decimal $x
	) : Decimal
	{
		$len = \count($x->_d());

		if ( $len < 3 )
		{ return static::__taylorSeries($c, 2, $x, $x); }

		// Argument reduction: sin(5x) = 16*sin^5(x) - 20*sin^3(x) + 5*sin(x)
		// i.e. sin(x) = 16*sin^5(x/5) - 20*sin^3(x/5) + 5*sin(x/5)
		// and  sin(x) = sin(x/5)(5 + sin^2(x/5)(16sin^2(x/5) - 20))

		// Estimate the optimum number of times to use the argument reduction.
		$k = 1.4 * \sqrt($len);
		$k = $k > 16 ? 16 : $k | 0;

		$x = $x->times(1/static::__tinyPow(5, $k));
		$x = static::__taylorSeries($c, 2, $x, $x);

		// Reverse argument reduction
		$d5 = new Decimal(5, $c);
		$d16 = new Decimal(16, $c);
		$d20 = new Decimal(20, $c);

		for ( ; $k--; )
		{
			$sin2_x = $x->times($x);
			$x = $x->times($d5->plus($sin2_x->times($d16->times($sin2_x)->minus($d20))));
		}

		return $x;
	}

	/**
	 * Calculate Taylor series for `cos`, `cosh`, 
	 * `sin` and `sinh`.
	 *
	 * @param DecimalConfig $c
	 * @param integer $n
	 * @param Decimal $x
	 * @param Decimal $y
	 * @param bool $isHyperbolic
	 * @since 1.0.0
	 * @return Decimal
	 */
	protected static function __taylorSeries (
		DecimalConfig $c,
		int $n,
		Decimal $x,
		Decimal $y,
		$isHyperbolic = false
	)
	{
		$i = 1;
		$pr = $c->precision;
		$k = (int)\ceil($pr/Decimal::LOG_BASE);

		static::$_external = false;

		$x2 = $x->times($x);
		$u = new Decimal($y, $c);

		for ( ;; )
		{
			$t = Math::div($u->times($x2), new Decimal($n++ * $n++, $c), $pr, 1);
			$u = $isHyperbolic ? $y->plus($t) : $y->minus($t);
			$y = Math::div($t->times($x2), new Decimal($n++ * $n++, $c), $pr, 1);
			$t = $u->plus($y);

			if ( isset($t->_d()[$k]) )
			{
				for ( 
					$j = $k; 
					isset($t->_d()[$j], $u->_d()[$j]) 
						&& $t->_d()[$j] === $u->_d()[$j] 
						&& $j--; 
				);

				if ( $j == -1 )
				{ break; }
			}

			$j = $u;
			$u = $y;
			$y = $t;
			$t = $j;
			$i++;
		}

		static::$_external = true;
		$t->d(Utils::sliceArray($t->_d(), 0, $k+1));

		return $t;
	}

	/**
	 * Exponent $e must be positive and non-zero.
	 *
	 * @param integer $b
	 * @param integer $e
	 * @since 1.0.0
	 * @return int
	 */
	protected static function __tinyPow (
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
	 * Return the absolute value of $x reduced to 
	 * less than or equal to half pi.
	 *
	 * @param DecimalConfig $c
	 * @param Decimal $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	protected static function __toLessThanHalfPi (
		DecimalConfig $c,
		Decimal $x
	)
	{
		$isNeg = $x->isNeg();
		$pi = static::__getPi($c, $c->precision, 1);
		$halfPi = $pi->times(0.5);

		$x = $x->abs();

		if ( $x->lte($halfPi) )
		{
			static::$_quadrant = $isNeg ? 4 : 1;
			return $x;
		}

		$t = $x->divToInt($pi);

		if ( $t->isZero() )
		{ static::$_quadrant = $isNeg ? 3 : 2; }
		else 
		{
			$x = $x->minus($t->times($pi));

			// 0 <= x < pi
			if ( $x->lte($halfPi) )
			{ 
				static::$_quadrant = static::__isOdd($t) ? ($isNeg ? 2 : 3) : ($isNeg ? 4 : 1);
				return $x;
			}

			static::$_quadrant = static::__isOdd($t) ? ($isNeg ? 1 : 4) : ($isNeg ? 3 : 2);
		}

		return $x->minus($pi)->abs();
	}

	/**
	 * Return the value of Decimal $x as a string 
	 * in base $bo.
	 * 
	 * If the optional $sd argument is present 
	 * include a binary exponent suffix.
	 *
	 * @param Decimal $x
	 * @param integer $baseOut Base out.
	 * @param integer $sd Significant digits.
	 * @param integer $rm Rounding mode.
	 * @since 1.0.0
	 * @return string
	 */
	protected static function __toStringBinary ( 
		Decimal $x, 
		int $baseOut, 
		$sd = null, 
		int $rm = null 
	)
	{
		$c = $x->_c();
		$isExp = !\is_null($sd);

		if ( $isExp )
		{
			Math::isInt32($sd, 1, DecimalConfig::MAX_DIGITS);

			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else 
			{ Math::isInt32($rm, 0, 8); }
		}
		else 
		{
			$sd = $c->precision;
			$rm = $c->rounding;
		}

		if ( !$x->isFinite() )
		{ $str = static::__nonFiniteToString($x); }
		else
		{
			$str = static::__finiteToString($x);
			$i = \strpos($str, '.');

			// Use exponential notation according to `toExpPos` and `toExpNeg`? No, but if required:
			// maxBinaryExponent = floor((decimalExponent + 1) * log[2](10))
			// minBinaryExponent = floor(decimalExponent * log[2](10))
			// log[2](10) = 3.321928094887362347870319429489390175864

			if ( $isExp )
			{
				$base = 2;
	
				if ( $baseOut === 16 )
				{ $sd = ($sd * 4) - 3; }
				else if ( $baseOut === 8 )
				{ $sd = ($sd * 3) - 2; }
			}
			else 
			{ $base = $baseOut; }

			// Convert the number as an integer then divide the 
			// result by its base raised to a power such
			// that the fraction part will be restored.

			// Non-integer.
			if ( $i !== false )
			{
				$str = \str_replace('.', '', $str);
				$y = new Decimal(1, $c);
				
				$y->e(\strlen($str)-$i);
				$y->d(static::__convertBase(static::__finiteToString($y), 10, $base));
				$y->e(\count($y->_d()));
			}

			$xd = static::__convertBase($str, 10, $base);
			$e = $len = \count($xd);

			// Remove trailing zeros
			for ( ; isset($xd[--$len]) && $xd[$len] == 0; )
			{ \array_pop($xd); }

			if ( empty($xd[0]) )
			{ $str = $isExp ? '0p+0' : '0'; }
			else
			{
				$roundUp = false; 

				if ( $i === false )
				{ $e--; }
				else
				{
					$x = new Decimal($x, $c);
					$x->d($xd);
					$x->e($e);
					$x = Math::div($x, $y, $sd, $rm, 0, $base);
					$xd = $x->_d();
					$e = $x->_e();
					$roundUp = static::$_inexact;
				}

				$i = $xd[$sd]??null;
				$k = $base/2;
				$roundUp = $roundUp || isset($xd[$sd+1]);

				$roundUp =
					$rm < 4 ?
					(!\is_null($i) || $roundUp) && ( $rm === 0 || $rm === ($x->_s() < 0 ? 3 : 2) ) :
						$i > $k || 
						( $i === $k && 
							($rm === 4 || 
								$roundUp ||
									($rm === 6 && $xd[$sd-1] & 1) ||
										$rm === ($x->_s() < 0 ? 8 : 7)));

				$xd = Utils::sliceArray($xd, 0, $sd);

				if ( $roundUp )
				{
					// Rounding up may mean the previous digit 
					// has to be rounded up and so on.
					for ( ; isset($xd[--$sd]) && ++$xd[$sd] > $base - 1; )
					{
						$xd[$sd] = 0;

						if ( !$sd )
						{
							++$e;
							\array_unshift($xd, 1);
						}
					}
				}
				
				// Determine trailing zeros.
				for ($len = \count($xd); empty($xd[$len - 1]); --$len);

				// E.g. [4, 11, 15] becomes 4bf.
				for ($i = 0, $str = ''; $i < $len; $i++) 
				{ $str .= DecimalConfig::NUMERALS[$xd[$i]]; }

				// Add binary exponent suffix?
				if ( $isExp )
				{
					if ( $len > 1 )
					{
						if ( $baseOut == 16 || $baseOut == 8 )
						{
							$i = $baseOut == 16 ? 4 : 3;

							for (--$len; $len % $i; $len++) 
							{ $str .= '0'; }

							$xd = static::__convertBase($str, $base, $baseOut);

							for ($len = \count($xd); isset($xd[$len - 1]) && !$xd[$len - 1]; --$len);

							// xd[0] will always be be 1
							for ( $i = 1, $str = '1.'; $i < $len; $i++ )
							{ $str .= DecimalConfig::NUMERALS[$xd[$i]]; }
						}
						else
						{ $str = $str[0].'.'.Utils::sliceStr($str, 1); }
					}

					$str = $str . ($e < 0 ? 'p':'p+') . $e;
				}
				else if ( $e < 0 )
				{
					for (; ++$e;)
					{ $str = '0'.$str; }

					$str = '0.'.$str;
				}
				else
				{
					if ( ++$e > $len )
					{
						for ( $e -= $len; $e--; )
						{ $str .= '0'; }
					}
					else if ( $e < $len )
					{ $str = Utils::sliceStr($str, 0, $e).'.'.Utils::sliceStr($str, $e); }
				}
			}

			$str = ($baseOut == 16 ? '0x' : ($baseOut == 2 ? '0b' : ($baseOut == 8 ? '0o' : ''))) . $str;
		}

		return $x->_s() < 0 ? '-'.$str : $str;
	}

	/**
	 * Truncate array to $length limit.
	 *
	 * @param array $arr
	 * @param integer $length
	 * @since 1.0.0
	 * @return array
	 */
	protected static function __truncate ( array $arr, int $length ) : array
	{
		if ( count($arr) > $length )
		{ return array_slice($arr, 0, $length); }

		return $arr;
	}

	// /**
	//  * Slice string from $startIndex at $endIndex.
	//  * Similar to Javascript String.slice().
	//  *
	//  * @param string $str
	//  * @param integer $startIndex
	//  * @param integer|null $endIndex
	//  * @return string
	//  */
	// protected static function __slice ( 
	// 	string $str, 
	// 	int $startIndex, 
	// 	int $endIndex = null 
	// ) : string
	// {
	// 	$len = \strlen($str);
	// 	$startIndex = $startIndex < 0 ? 0 : ($startIndex > $len ? $len : $startIndex);
	// 	$endIndex = \is_null($endIndex) ? null : ($endIndex < 0 ? 0 : ($endIndex > $len ? $len : $endIndex));

	// 	if ( $endIndex === $startIndex )
	// 	{ return ''; }

	// 	if ( \is_null($endIndex) )
	// 	{ return \substr($str, $startIndex); }

	// 	if ( $startIndex === 0 && $endIndex === $len )
	// 	{ return $str; }

	// 	$endIndex = $endIndex - $startIndex;
	// 	return \substr($str, $startIndex, $endIndex);
	// }

	// /**
	//  * Slice array from $si at $ei.
	//  * Similar to Javascript Array.prototype.slice().
	//  *
	//  * @param array $arr
	//  * @param integer $startIndex
	//  * @param integer $ei
	//  * @return array
	//  */
	// protected static function __arraySlice ( 
	// 	array $arr, 
	// 	int $si = null, 
	// 	int $ei = null
	// ) : array
	// {
	// 	$len = \count($arr);
	// 	$si = \is_null($si) ? 0 : ($si >= $len ? $len : ($si <= -$len ? -$len : $si));
	// 	$ei = \is_null($ei) || $ei >= $len ? $len : ($ei <= -$len ? -$len : $ei);

	// 	if ( $si === $len || $si === $ei )
	// 	{ return []; }

	// 	if ( $si >= 0 )
	// 	{
	// 		if ( $ei >= 0 && $ei <= $si )
	// 		{ return []; }
	// 		// $ei > $si
	// 		else if ( $ei > $si )
	// 		{
	// 			$_arr = [];

	// 			for ( $i = $si; $i < $ei; $i++ )
	// 			{ $_arr[] = $arr[$i]; }

	// 			return $_arr;
	// 		}
	// 		// $ei < 0 && $ei < si
	// 		else
	// 		{
	// 			$ei += $len;

	// 			if ( $ei <= $si )
	// 			{ return []; }

	// 			// $ei > $si
	// 			$_arr = [];

	// 			for ( $i = $si; $i < $ei; $i++ )
	// 			{ $_arr[] = $arr[$i]; }

	// 			return $_arr;
	// 		}
	// 	}

	// 	// si < 0
	// 	$si += $len;
	// 	$ei = $ei < 0 ? $ei+$len : $ei;

	// 	if ( $ei <= $si )
	// 	{ return []; }

	// 	$_arr = [];

	// 	for ( $i = $si; $i < $ei; $i++ )
	// 	{ $_arr[] = $arr[$i]; }

	// 	return $_arr;
	// } 

	// protected static function __arrayCopy ( $arr ) : array
	// {
	// 	$_arr = [];

	// 	foreach ( $arr as $key => $value )
	// 	{ $_arr[$key] = $value; }

	// 	return $_arr;
	// }
}