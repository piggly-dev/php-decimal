<?php
namespace Piggly\Decimal;

use RuntimeException;

class Decimal
{ 
	const _IS_BINARY = '/^0b([01]+(\.[01]*)?|\.[01]+)(p[+-]?\d+)?$/i';
	const _IS_HEX = '/^0x([0-9a-f]+(\.[0-9a-f]*)?|\.[0-9a-f]+)(p[+-]?\d+)?$/i';
	const _IS_OCTAL = '/^0o([0-7]+(\.[0-7]*)?|\.[0-7]+)(p[+-]?\d+)?$/i';
	const _IS_DECIMAL = '/^(\d+(\.\d*)?|\.\d+)(e[+-]?\d+)?$/i';

	const BASE = 1e7;
	const LOG_BASE = 7;
	const MAX_SAFE_INTEGER = 9007199254740991;

	// const LN10_PRECISION = (strlen(DecimalConfig::LN10) - 1);
	// const PI_PRECISION = (strlen(DecimalConfig::PI) - 1);
	const LN10_PRECISION = 1025;
	const PI_PRECISION = 1025;

	/**
	 * An array of integers,
	 * each between 0 - 1e7
	 * or null.
	 *
	 * @var array<int>|null
	 * @since 1.0.0
	 */
	protected $_digits;

	/**
	 * An integer between -9e15
	 * to 9e15 or NaN.
	 *
	 * @var integer
	 * @since 1.0.0
	 */
	protected $_exponent;

	/**
	 * An integer limited to -1,
	 * 1 or NaN.
	 *
	 * @var integer
	 * @since 1.0.0
	 */
	protected $_sign;

	/**
	 * Decimal configuration.
	 *
	 * @var DecimalConfig
	 * @since 1.0.0
	 */
	private $_config;

	/**
	 * Decimal constructor.
	 *
	 * @param Decimal|float|int|string $n
	 * @param DecimalConfig|null $config
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct ( $n, DecimalConfig $config = null )
	{
		$config = $config instanceof DecimalConfig ? $config : DecimalConfig::instance();
		$this->_config = $config;

		if ( $n instanceof Decimal )
		{
			$this->_sign = $n->_sign;

			if ( DecimalHelper::isExternal() )
			{
				if ( !$n->hasDigits() || $n->_exponent > $config->maxE )
				{
					$this->_exponent = \NAN;
					$this->_digits = null;
				}
				else if ( $n->_exponent < $config->minE )
				{
					$this->_exponent = 0;
					$this->_digits = [0];
				}
				else
				{
					$this->_exponent = $n->_exponent;
					$this->_digits = $n->_digits;
				}
			}
			else
			{
				$this->_exponent = $n->_exponent;
				$this->_digits = $n->_digits;
			}

			return;
		}

		if ( \is_numeric($n) && !\is_string($n) )
		{
			if ( $n === 0 )
			{
				// PHP does not catch negative zero
				$this->_sign = 1;
				$this->_exponent = 0;
				$this->_digits = [0];

				return;
			}

			if ( $n < 0 )
			{ $n = -$n; $this->_sign = -1; }
			else
			{ $this->_sign = 1; }

			// Small integers
			if ( $n == \floor($n) && $n < 1e7 )
			{
				for ($e = 0, $i = $n; $i >= 10; $i /= 10) 
				{ $e++; }

				if ( DecimalHelper::isExternal() )
				{
					if ( $e > $config->maxE )
					{
						// Infinity
						$this->_exponent = \NAN;
						$this->_digits = null;
					}
					else if ( $e < $config->minE )
					{
						// Zero
						$this->_exponent = 0;
						$this->_digits = [0];
					}
					else
					{
						$this->_exponent = $e;
						$this->_digits = [$n];
					}
				}
				else
				{
					$this->_exponent = $e;
					$this->_digits = [$n];
				}

				return;
			}
			else if ( \is_infinite($n) )
			{
				$this->_exponent = \NAN;
				$this->_digits = null;

				return;
			}
			// Non numeric
			else if ( $n * 0 != 0 || \is_nan($n) )
			{
				$this->_sign = \NAN;
				$this->_exponent = \NAN;
				$this->_digits = null;

				return;
			}

			DecimalHelper::parseDecimal($this, \strval($n));
			return;
		}
		else if ( !\is_string($n) )
		{ throw new RuntimeException('Decimal must be a numeric or string value.'); }

		// Has minus sign?
		if ( \ord($n[0]) === 45 )
		{
			$n = \substr($n, 1);
			$this->_sign = -1;
		}
		// Plus sign
		else
		{
			if ( \ord($n[0]) === 43 )
			{ $n = \substr($n, 1); }

			$this->_sign = 1;
		}

		if ( \preg_match(DecimalHelper::IS_DECIMAL, $n) )
		{ 
			DecimalHelper::parseDecimal($this, $n); 
			return;
		}

		DecimalHelper::parseOther($this, $n);
		return;
	}

	/**
	 * Returns a new Decimal whose value is the absolute value.
	 * i.e. the magnitude, of the value of this Decimal.
	 * 
	 * The return value is not affected by the value of the 
	 * precision setting.
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function absoluteValue () : Decimal
	{ 
		$decimal = new Decimal($this, $this->_config);
		
		if ( $decimal->_sign < 0 )
		{ $decimal->_sign = 1; }

		return DecimalHelper::finalise($decimal);
	}

	/**
	 * Alias to absoluteValue() method.
	 *
	 * @see absoluteValue()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function abs () : Decimal
	{ return $this->absoluteValue(); }

	/**
	 * Return a new Decimal whose value 
	 * is the absolute value of `x`.
	 *
	 * @param Decimal|float|int|string $x
	 * @return Decimal
	 */
	public static function absOf ( $x ) : Decimal
	{ return (new Decimal($x))->abs(); }

	/**
	 * Return a new Decimal whose value is the value 
	 * of this Decimal rounded to a whole number in 
	 * the direction of positive Infinity.
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function ceil () : Decimal
	{ 
		return DecimalHelper::finalise(
			new Decimal($this, $this->_config), 
			$this->_exponent + 1, 
			DecimalConfig::ROUND_CEIL
		); 
	}

	/**
	 * Return a new Decimal whose value is `x` rounded
	 * to an integer using `ROUND_CEIL`.
	 *
	 * @param Decimal|float|int|string $x
	 * @return Decimal
	 */
	public static function ceilOf ( $x ) : Decimal
	{ return (new Decimal($x))->ceil(); }

	/**
	 * Return a new Decimal whose value is `x` rounded
	 * to an integer using `ROUND_CEIL`.
	 *
	 * @param Decimal|float|int|string $x
	 * @return Decimal
	 */
	public static function clone ( $x ) : Decimal
	{ return new Decimal($x, $x->_c()); }

	/**
	 * Return
	 *   1    if the value of this Decimal is greater than the value of `y`,
	 *  -1    if the value of this Decimal is less than the value of `y`,
	 *   0    if they have the same value,
	 *   NAN  if the value of either Decimal is NAN.
	 *
	 * @param Decimal|float|int|string $y
	 * @return float
	 */
	public function comparedTo ( $y ) : float
	{
		$x = $this;
		$xd = $x->_digits;
		$xs = $x->_sign;

		$y = new Decimal($y, $x->_config);
		$yd = $y->_digits;
		$ys = $y->_sign;

		// Either NaN or ±Infinity?
		if ( \is_null($xd) || \is_null($yd) )
		{ 
			if ( \is_nan($xs) || \is_nan($ys) )
			{ return \NAN; }

			if ( $xs !== $ys )
			{ return $xs; }

			if ( $xd === $yd )
			{ return 0; }

			if ( !$xd ^ $xs < 0 )
			{ return 1; }
			
			return -1; 
		}

		// Either zero?
		if ( !$xd[0] || !$yd[0] )
		{ return $xd[0] ? $xs : ($yd[0] ? -$ys : 0); }

		// Signs differ?
		if ($xs !== $ys) 
		{ return $xs; }

		// Compare exponents
		if ( $x->_exponent !== $y->_exponent )
		{ return $x->_exponent > $y->_exponent ^ $xs < 0 ? 1 : -1; }

		$xdC = \count($xd);
		$ydC = \count($yd);

    	// Compare digit by digit
		for (
			$i = 0,
			$j = $xdC < $ydC ? $xdC : $ydC;
			$i < $j;
			++$i
		)
		{
			if ( $xd[$i] !== $yd[$i] ) 
			{ return $xd[$i] > $yd[$i] ^ $xs < 0 ? 1 : -1; }
		}

		// Compare lengths
		return $xdC === $ydC ? 0 : ( $xdC > $ydC ^ $xs < 0 ? 1 : -1 ); 
	}

	/**
	 * Alias to comparedTo() method.
	 *
	 * @see comparedTo()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return float
	 */
	public function cmp ( $y )
	{ return $this->comparedTo($y); }
	
	/**
	 * Return a new Decimal whose value is the cosine 
	 * of the value in radians of this Decimal.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [-1, 1]
	 *
	 *  cos(0)         = 1
	 *  cos(-0)        = 1
	 *  cos(Infinity)  = NaN
	 *  cos(-Infinity) = NaN
	 *  cos(NaN)       = NaN
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function cosine () : Decimal
	{
		$x = $this;
		$c = $this->_c();

		if ( !$x->isFinite() )
		{ return new Decimal(\NAN, $c); }

		// cos(0) = cos(-0) = 1
		if ( $x->isZero() )
		{ return new Decimal(1, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max($x->_e(), $x->sd()) + static::LOG_BASE;
		$c->rounding = 1;

		$x = DecimalHelper::cosine($c, DecimalHelper::toLessThanHalfPi($c, $x));

		$c->precision = $pr;
		$c->rounding = $rm;

		return DecimalHelper::finalise(
			DecimalHelper::_quadrant() == 2 || DecimalHelper::_quadrant() == 3 ? $x->neg() : $x, 
			$pr, 
			$rm, 
			true
		);
	}

	/**
	 * Alias to cosine() method.
	 *
	 * @see cosine()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function cos () : Decimal
	{ return $this->cosine(); }

	/**
	 * Return a new Decimal whose value is the cosine of `x`, 
	 * rounded to `precision` significant digits using rounding
	 * mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @return Decimal
	 */
	public static function cosOf ( $x ) : Decimal
	{ return (new Decimal($x))->cosine(); }

	/**
	 * Return a new Decimal whose value is the cube root 
	 * of the value of this Decimal, rounded to `precision` 
	 * significant digits using rounding mode `rounding`.
	 *
	 *  cbrt(0)  =  0
	 *  cbrt(-0) = -0
	 *  cbrt(1)  =  1
	 *  cbrt(-1) = -1
	 *  cbrt(N)  =  N
	 *  cbrt(-I) = -I
	 *  cbrt(I)  =  I
	 *
	 * Math.cbrt(x) = (x < 0 ? -Math.pow(-x, 1/3) : Math.pow(x, 1/3))
	 * 
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function cubeRoot () : Decimal
	{

		$x = $this;
		$c = $x->_c();

		// NaN/Infinity/zero?
		if ( !$x->isFinite() || $x->isZero() )
		{ return new Decimal($x, $c); }

		DecimalHelper::external(false);

		// Estimate cbrt
		$s = \intval($x->_s() + \pow($x->_s() * $x->toNumber(), 1/3));
		
		// Math.sqrt underflow/overflow?
		// Pass x to Math.pow as integer, 
		// then adjust the exponent of the result.
		if ( $s == 0 || \abs($s) == \INF )
		{
			$n = DecimalHelper::digitsToString($x->_d());
			$e = $x->_e();

			// Adjust n exponent so it is a multiple 
			// of 3 away from x exponent.
			if ( $s = ($e-\strlen($n)+1) % 3 )
			{ $n .= $s == 1 || $s == -2 ? '0' : '00'; }

			$s = \intval(\pow($n, 1/3));
			// Rarely, e may be one less than 
			// the result exponent value.
			$e = (int)\floor(($e+1)/3) - \intval($e % 3 == ( $e < 0 ? -1 : 2 ));

			if ( $s == \INF )
			{ $n = '5e'.$e; }
			else 
			{
				$n = \strval(\exp($s));
				$n = DecimalHelper::slice(0, \strpos($n, 'e')+1) + $e;
			}

			$r = new Decimal($n, $c);
			$r->s($x->_s());
		}
		else
		{ $r = new Decimal((string)$s); }

		$sd = ($e = $c->precision) + 3;
		$rep = null;

		// Halley's method.
		// TODO? Compare Newton's method.
		for (;;)
		{
			$t = $r;
			$t3 = $t->times($t)->times($t);
			$t3plusx = $t3->plus($x);
			$r = DecimalHelper::divide($t3plusx->plus($x)->times($t), $t3plusx->plus($t3), $sd+2, 1);

			// TODO? Replace with for-loop and checkRoundingDigits.
			if (
				DecimalHelper::slice(DecimalHelper::digitsToString($t->_d()), 0, $sd)
				=== ($n = DecimalHelper::slice(DecimalHelper::digitsToString($r->_d()), 0, $sd))
			)
			{
				$n = DecimalHelper::slice($n, $sd-3, $sd+1);
							
				// The 4th rounding digit may be in error by -1 so 
				// if the 4 rounding digits are 9999 or
				// 4999, i.e. approaching a rounding boundary,
				// continue the iteration.
				if ( $n == '9999' || ( !$rep && $n == '4999') )
				{
					// On the first iteration only, check to see 
					// if rounding up gives the exact result as the
					// nines may infinitely repeat.
					if ( !$rep )
					{
						$t = DecimalHelper::finalise($t, $e+1, 0);

						if ( $t->times($t)->times($t)->eq($x) )
						{
							$r = $t;
							break;
						}
					}

					$sd += 4;
					$rep = 1;
				}
				else
				{
					// If the rounding digits are null, 0{0,4} 
					// or 50{0,3}, check for an exact result.
					// If not, then there are further digits 
					// and m will be truthy.
					if ( !(int)$n || (!(int)DecimalHelper::slice($n, 1) && $n[0] == '5') )
					{
						$r = DecimalHelper::finalise($r, $e+1, 0);
						$m = !$r->times($r)->times($r)->eq($x);
					}

					break;
				}
			}
		}

		DecimalHelper::external(true);

		return DecimalHelper::finalise($r, $e, $c->rounding, $m);
	}

	/**
	 * Alias to cubeRoot() method.
	 *
	 * @see cubeRoot()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function cbrt () : Decimal
	{ return $this->cubeRoot(); }

	/**
	 * Return a new Decimal whose value is the cube root of `x`, 
	 * rounded to `precision` significant digits using rounding
	 * mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @return Decimal
	 */
	public static function cbrtOf ( $x ) : Decimal
	{ return (new Decimal($x))->cbrt(); }

	/**
	 * Undocumented function
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function decimalPlaces () : Decimal
	{
		$digits = $this->_digits;
		$number = \NAN;

		if ( !empty($digits) )
		{
			$w = count($digits) - 1;
			$number = ($w - floor($this->_exponent / self::LOG_BASE)) * self::LOG_BASE;
			$w = $digits[$w];

			if ( isset($digits[$w]) )
			{
				$w = $digits[$w];

				for (; $w % 10 == 0; $w /= 1 )
				{ $number--; }
			}

			if ( $number < 0 ) 
			{ $number = 0; }
		}

		return $number;
	}

	/**
	 * Alias to decimalPlaces() method.
	 *
	 * @see decimalPlaces()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function dp () : Decimal
	{ return $this->decimalPlaces(); }

	/**
	 * Return a new Decimal whose value is the value of 
	 * this Decimal divided by `y`, rounded to `precision` 
	 * significant digits using rounding mode `rounding`.
	 * 
	 *  n / 0 = I
    *  n / N = N
    *  n / I = 0
    *  0 / n = 0
    *  0 / 0 = N
    *  0 / N = N
    *  0 / I = 0
    *  N / n = N
    *  N / 0 = N
    *  N / N = N
    *  N / I = N
    *  I / n = I
    *  I / 0 = I
    *  I / N = N
    *  I / I = N
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function dividedBy ( $y ) : Decimal
	{ return DecimalHelper::divide($this, new Decimal($y, $this->_config)); }

	/**
	 * Alias to dividedBy() method.
	 *
	 * @see dividedBy()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function div ( $y ) : Decimal
	{ return $this->dividedBy($y); }

	/**
	 * Return a new Decimal whose value is `x` divided
	 * by `y`, rounded to `precision` significant digits
	 * using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @param Decimal|float|int|string $y
	 * @return Decimal
	 */
	public static function divOf ( $x, $y ) : Decimal
	{ return (new Decimal($x))->div($y); }

	/**
	 * Alias to dividedToIntegerBy() method.
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function dividedToIntegerBy ( $y ) : Decimal
	{ 
		return DecimalHelper::finalise(
			DecimalHelper::divide( $this, new Decimal($y, $this->_config), 0, 1, 1),
			$this->_config->precision,
			$this->_config->rounding
		); 
	}

	/**
	 * Alias to dividedToIntegerBy() method.
	 *
	 * @see dividedToIntegerBy()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function divToInt ( $y ) : Decimal
	{ return $this->dividedToIntegerBy($y); }

	/**
	 * Return true if the value of this Decimal is equal 
	 * to the value of `y`, otherwise return false.
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function equals ( $y ) : bool
	{ return $this->cmp($y) === 0; }

	/**
	 * Alias to equals() method.
	 *
	 * @see equals()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function eq ( $y ) : bool
	{ return $this->equals($y); }

	/**
	 * Return a new Decimal whose value is the value of this 
	 * Decimal rounded to a whole number in the direction 
	 * of negative Infinity.
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function floor () : Decimal
	{ return DecimalHelper::finalise(new Decimal($this, $this->_config), $this->_exponent + 1, 3); }

	/**
	 * Return a new Decimal whose value is `x` 
	 * round to an integer using `ROUND_FLOOR`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function floorOf ( $x ) : Decimal
	{ return (new Decimal($x))->floor(); }

	/**
	 * Undocumented function
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function greaterThan ( $y ) : bool
	{ return $this->cmp($y) > 0; }

	/**
	 * Alias to greaterThan() method.
	 *
	 * @see greaterThan()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function gt ( $y ) : bool
	{ return $this->greaterThan($y); }

	/**
	 * Undocumented function
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function greaterThanOrEqualTo ( $y ) : bool
	{
		$k = $this->cmp($y);
		return $k == 1 || $k === 0;
	}

	/**
	 * Alias to greaterThanOrEqualTo() method.
	 *
	 * @see greaterThanOrEqualTo()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function gte ( $y ) : bool
	{ return $this->greaterThanOrEqualTo($y); }

	/**
	 * Return a new Decimal whose value is the hyperbolic cosine 
	 * of the value in radians of this Decimal.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [1, Infinity]
	 *
	 * cosh(x) = 1 + x^2/2! + x^4/4! + x^6/6! + ...
	 *
	 * cosh(0)         = 1
	 * cosh(-0)        = 1
	 * cosh(Infinity)  = Infinity
	 * cosh(-Infinity) = Infinity
	 * cosh(NaN)       = NaN
	 *
	 *  x        time taken (ms)   result
	 * 1000      9                 9.8503555700852349694e+433
	 * 10000     25                4.4034091128314607936e+4342
	 * 100000    171               1.4033316802130615897e+43429
	 * 1000000   3817              1.5166076984010437725e+434294
	 * 10000000  abandoned after 2 minute wait
	 *
	 * @todo (?) Compare performance of cosh(x) = 0.5 * (exp(x) + exp(-x))
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function hyperbolicCosine () : Decimal
	{
		$x = $this;
		$c = $this->_c();

		if ( !$x->isFinite() )
		{ return new Decimal($x->isNaN() ? \NAN : \INF, $c); }

		// cos(0) = cos(-0) = 1
		if ( $x->isZero() )
		{ return new Decimal(1, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max($x->_e(), $x->sd()) + 4;
		$c->rounding = 1;
		$len = \count($x->_d());

		// Argument reduction: cos(4x) = 1 - 8cos^2(x) + 8cos^4(x) + 1
		// i.e. cos(x) = 1 - cos^2(x/4)(8 - 8cos^2(x/4))

		// Estimate the optimum number of times to use the argument reduction.
		// TODO? Estimation reused from cosine() and may not be optimal here.
		if ( $len < 32 )
		{
			$k = \ceil($len / 3);
			$n = (string)(1/DecimalHelper::tinyPow(4, $k));
		}
		else
		{
			$k = 16;
			$n = '2.3283064365386962890625e-10';
		}

		$x = DecimalHelper::taylorSeries($c, 1, $x->times($n), new Decimal(1,$c), true);

		// Reverse argument reduction
		$i = $k;
		$d8 = new Decimal(8, $c);
		$one = new Decimal(1, $c);

		for (; $i--;)
		{
			$cosh2_x = $x->times($x);
			$x = $one->minus($cosh2_x->times($d8->minus($cosh2_x->times($d8))));
		}

		$c->precision = $pr;
		$c->rounding = $rm;

		return DecimalHelper::finalise(
			$x, 
			$pr, 
			$rm, 
			true
		);
	}

	/**
	 * Alias to hyperbolicCosine() method.
	 *
	 * @see hyperbolicCosine()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function cosh () : Decimal
	{ return $this->hyperbolicCosine(); }

	/**
	 * Return a new Decimal whose value is the hyperbolic
	 * cosine of `x`, rounded to precision significant digits
	 * using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function coshOf ( $x ) : Decimal
	{ return (new Decimal($x))->cosh(); }

	/**
	 * Return a new Decimal whose value is the hyperbolic 
	 * sine of the value in radians of this Decimal.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [-Infinity, Infinity]
	 *
	 * sinh(x) = x + x^3/3! + x^5/5! + x^7/7! + ...
	 *
	 * sinh(0)         = 0
	 * sinh(-0)        = -0
	 * sinh(Infinity)  = Infinity
	 * sinh(-Infinity) = -Infinity
	 * sinh(NaN)       = NaN
	 *
	 * x        time taken (ms)
	 * 10       2 ms
	 * 100      5 ms
	 * 1000     14 ms
	 * 10000    82 ms
	 * 100000   886 ms            1.4033316802130615897e+43429
	 * 200000   2613 ms
	 * 300000   5407 ms
	 * 400000   8824 ms
	 * 500000   13026 ms          8.7080643612718084129e+217146
	 * 1000000  48543 ms
	 *
	 * TODO? Compare performance of sinh(x) = 0.5 * (exp(x) - exp(-x))
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function hyperbolicSine ()
	{
		$x = $this;
		$c = $this->_c();

		if ( !$x->isFinite() || $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max($x->_e(), $x->sd()) + 4;
		$c->rounding = 1;
		$len = \count($x->_d());

		if ( $len < 3 )
		{ $x = DecimalHelper::taylorSeries($c, 2, $x, $x, true); }
		else
		{
			// Alternative argument reduction: sinh(3x) = sinh(x)(3 + 4sinh^2(x))
			// i.e. sinh(x) = sinh(x/3)(3 + 4sinh^2(x/3))
			// 3 multiplications and 1 addition

			// Argument reduction: sinh(5x) = sinh(x)(5 + sinh^2(x)(20 + 16sinh^2(x)))
			// i.e. sinh(x) = sinh(x/5)(5 + sinh^2(x/5)(20 + 16sinh^2(x/5)))
			// 4 multiplications and 2 additions

			// Estimate the optimum number of times to use the argument reduction.
			$k = 1.4 * \sqrt($len);
			$k = $k > 16 ? 16 : $k | 0;

			$x = $x->times(1/DecimalHelper::tinyPow(5,$k));
			$x = DecimalHelper::taylorSeries($c, 2, $x, $x, true);

			// Reverse argument reduction
			$d5 = new Decimal(5, $c);
			$d16 = new Decimal(16, $c);
			$d20 = new Decimal(20, $c);

			for ( ; $k--; )
			{
				$sinh2_x = $x->times($x);
				$x = $x->times($d5->plus($sinh2_x->times($d16->times($sinh2_x)->plus($d20))));
			}
		}

		$c->precision = $pr;
		$c->rounding = $rm;

		return DecimalHelper::finalise(
			$x, 
			$pr, 
			$rm, 
			true
		);
	}

	/**
	 * Alias to hyperbolicSine() method.
	 *
	 * @see hyperbolicSine()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function sinh () : Decimal
	{ return $this->hyperbolicSine(); }
	
	/**
	 * Return a new Decimal whose value is the hyperbolic
	 * sine of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function sinhOf ( $x ) : Decimal
	{ return (new Decimal($x))->sinh(); }

	/**
	 * Return a new Decimal whose value is the hyperbolic 
	 * tangent of the value in radians of this Decimal.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [-1, 1]
	 *
	 * tanh(x) = sinh(x) / cosh(x)
	 *
	 * tanh(0)         = 0
	 * tanh(-0)        = -0
	 * tanh(Infinity)  = 1
	 * tanh(-Infinity) = -1
	 * tanh(NaN)       = NaN
	 * 
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function hyperbolicTangent ()
	{
		$x = $this;
		$c = $this->_c();

		if ( !$x->isFinite() )
		{ return new Decimal($x->_s(), $c); }

		if ( $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+7;
		$c->rounding = 1;

		return DecimalHelper::finalise(
			$x->sinh(),
			$x->cosh(), 
			($c->precision = $pr), 
			($c->rounding = $rm), 
			true
		);
	}

	/**
	 * Alias to hyperbolicTangent() method.
	 *
	 * @see hyperbolicTangent()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function tanh () : Decimal
	{ return $this->hyperbolicTangent(); }
	
	/**
	 * Return a new Decimal whose value is the hyperbolic
	 * tangent of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function tanhOf ( $x ) : Decimal
	{ return (new Decimal($x))->tanh(); }

	/**
	 * Return a new Decimal whose value is the square root
	 * of the sum of the squares of the arguments, rounded to
	 * `precision` significant digits using rounding mode `rounding`.
	 *
	 * hypot(a, b, ...) = sqrt(a^2 + b^2 + ...)
	 * 
	 * @param array<Decimal|float|int|string> ...$args
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function hypot () : Decimal
	{
		$args = \func_get_args();

		$c = DecimalConfig::instance();
		$t = new Decimal(0, $c);

		DecimalHelper::external(false);

		for ( $i = 0; $i < \count($args); )
		{
			$n = new Decimal($args[$i++]);
			
			if ( !$n->isFinite() )
			{
				if ( !$n->isNaN() )
				{
					DecimalHelper::external(true);
					return new Decimal(\INF, $c);
				}

				$t = $n;
			}
			else if ( $t->isFinite() )
			{ $t = $t->plus($n->times($n)); }
		}

		DecimalHelper::external(true);

		return $t->sqrt();
	}

	/**
	 * Return a new Decimal whose value is the arccosine
	 * (inverse cosine) in radians of the value of this Decimal.
	 *
	 * Domain: [-1, 1]
	 * Range: [0, pi]
	 *
	 * acos(x) = pi/2 - asin(x)
	 *
	 * acos(0)       = pi/2
	 * acos(-0)      = pi/2
	 * acos(1)       = 0
	 * acos(-1)      = pi
	 * acos(1/2)     = pi/3
	 * acos(-1/2)    = 2*pi/3
	 * acos(|x| > 1) = NaN
	 * acos(NaN)     = NaN
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function inverseCosine ()
	{
		$x = $this;
		$c = $this->_c();
		$k = $x->abs()->cmp(1);
		$pr = $c->precision;
		$rm = $c->rounding;

		if ( $k !== -1 )
		{
			// |x| > 1 or x is NaN
			$n = \NAN;

			// |x| is 1
			if ( $k === 0 )
			{ 
				if ( $x->isNeg() )
				{ return DecimalHelper::getPi($c, $pr, $rm); }
				else 
				{ $n = 0; }
			}

			return new Decimal($n, $c); 
		}

		if ( $x->isZero() )
		{ return DecimalHelper::getPi($c, $pr+4, $rm)->times(0.5); }

		// TODO? Special case acos(0.5) = pi/3 and acos(-0.5) = 2*pi/3

		$c->precision = $pr+6;
		$c->rounding = 1;

		$x = $x->asin();
		$halfPi = DecimalHelper::getPi($c, $pr+4, $rm)->times(0.5);

		$c->precision = $pr;
		$c->rounding = $rm;

		return $halfPi->minus($x);
	}

	/**
	 * Alias to inverseCosine() method.
	 *
	 * @see inverseCosine()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function acos ()
	{ return $this->inverseCosine(); }
	
	/**
	 * Return a new Decimal whose value is the
	 * arccosine in radians of `x`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function acosOf ( $x ) : Decimal
	{ return (new Decimal($x))->acos(); }

	/**
	 * Return a new Decimal whose value is the inverse of the 
	 * hyperbolic cosine in radians of the value of this Decimal.
	 *
	 * Domain: [1, Infinity]
	 * Range: [0, Infinity]
	 *
	 * acosh(x) = ln(x + sqrt(x^2 - 1))
	 *
	 * acosh(x < 1)     = NaN
	 * acosh(NaN)       = NaN
	 * acosh(Infinity)  = Infinity
	 * acosh(-Infinity) = NaN
	 * acosh(0)         = NaN
	 * acosh(-0)        = NaN
	 * acosh(1)         = 0
	 * acosh(-1)        = NaN
	 *
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function inverseHyperbolicCosine () : Decimal
	{
		$x = $this;
		$c = $this->_c();

		if ( $x->lte(1) )
		{ return new Decimal($x->eq(1) ? 0 : \NAN, $c); }

		if ( !$x->isFinite() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max((int)\abs($x->_e()), $x->sd()) + 4;
		$c->rounding = 1;

		DecimalHelper::external(false);
		$x = $x->times($x)->minus(1)->sqrt()->plus($x);
		DecimalHelper::external(true);

		$c->precision = $pr;
		$c->rounding = $rm;

		return $x->ln();
	}

	/**
	 * Alias to inverseHyperbolicCosine() method.
	 *
	 * @see inverseHyperbolicCosine()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function acosh () : Decimal
	{ return $this->inverseHyperbolicCosine(); }
	
	/**
	 * Return a new Decimal whose value is the inverse of
	 * the hyperbolic cosine of `x`, rounded to `precision`
	 * significant digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function acoshOf ( $x ) : Decimal
	{ return (new Decimal($x))->acosh(); }

	/**
	 * Return a new Decimal whose value is the inverse of 
	 * the hyperbolic sine in radians of the value of this Decimal.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [-Infinity, Infinity]
	 *
	 * asinh(x) = ln(x + sqrt(x^2 + 1))
	 *
	 * asinh(NaN)       = NaN
	 * asinh(Infinity)  = Infinity
	 * asinh(-Infinity) = -Infinity
	 * asinh(0)         = 0
	 * asinh(-0)        = -0
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function inverseHyperbolicSine () : Decimal
	{
		$x = $this;
		$c = $this->_c();

		if ( !$x->isFinite() || $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+2+(int)\max((int)\abs($x->_e()), $x->sd()) + 6;
		$c->rounding = 1;

		DecimalHelper::external(false);
		$x = $x->times($x)->plus(1)->sqrt()->plus($x);
		DecimalHelper::external(true);

		$c->precision = $pr;
		$c->rounding = $rm;

		return $x->ln();
	}

	/**
	 * Alias to inverseHyperbolicSine() method.
	 *
	 * @see inverseHyperbolicSine()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function asinh () : Decimal
	{ return $this->inverseHyperbolicSine(); }
	
	/**
	 * Return a new Decimal whose value is the inverse
	 * of the hyperbolic sine of `x`, rounded to `precision`
	 * significant digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function asinhOf ( $x ) : Decimal
	{ return (new Decimal($x))->asinh(); }

	/**
	 * Return a new Decimal whose value is the inverse 
	 * of the hyperbolic tangent in radians of the
	 * value of this Decimal.
	 *
	 * Domain: [-1, 1]
	 * Range: [-Infinity, Infinity]
	 *
	 * atanh(x) = 0.5 * ln((1 + x) / (1 - x))
	 * 
	 * atanh(|x| > 1)   = NaN
 	 * atanh(NaN)       = NaN
	 * atanh(Infinity)  = NaN
	 * atanh(-Infinity) = NaN
	 * atanh(0)         = 0
	 * atanh(-0)        = -0
	 * atanh(1)         = Infinity
	 * atanh(-1)        = -Infinity
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function inverseHyperbolicTangent () : Decimal
	{
		$x = $this;
		$c = $this->_c();

		if ( !$x->isFinite() )
		{ return new Decimal(\NAN, $c); }

		if ( $x->_e() >= 0 )
		{
			$n = 0;
			
			if ( $x->abs()->eq(1) )
			{ $n = $x->isNaN() ? \NAN : \INF; }
			else if ( $x->isZero() )
			{ $n = $x; }
			else 
			{ $n = \NAN; } 

			return new Decimal($n, $c); 
		}

		$pr = $c->precision;
		$rm = $c->rounding;

		$xsd = $x->sd();

		if ( (int)\max($xsd, $pr) < 2 * -$x->_e() - 1 )
		{ return DecimalHelper::finalise(new Decimal($x, $c), $pr, $rm, true); }

		$c->precision = $wpr = $xsd - $x->_e();
		$x = DecimalHelper::divide($x->plus(1), (new Decimal(1, $c))->minus($x), $wpr + $pr, 1);

		$c->precision = $pr + 4;
		$c->rounding = 1;

		$x = $x->ln();

		$c->precision = $pr;
		$c->rounding = $rm;

		return $x->times(0.5);
	}

	/**
	 * Alias to inverseHyperbolicTangent() method.
	 *
	 * @see inverseHyperbolicTangent()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function atanh () : Decimal
	{ return $this->inverseHyperbolicTangent(); }
	
	/**
	 * Return a new Decimal whose value is the arctangent 
	 * in radians of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function atanhOf ( $x ) : Decimal
	{ return (new Decimal($x))->atanh(); }

	/**
	 * Alias to hyperbolicTangent() method.
	 *
	 * @see hyperbolicTangent()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function inverseSine () : Decimal
	{
		$x = $this;
		$c = $this->_c();

		if ( $x->isZero() )
		{ return new Decimal($x, $c); }

		$k = $x->abs()->cmp(1);
		$pr = $c->precision;
		$rm = $c->rounding;

		if ( $k !== -1 )
		{
			// |x| is 1
			if ( $k === 0 )
			{ 
				$halfPi = DecimalHelper::getPi($c, $pr+4, $rm)->times(0.5);
				$halfPi->s($x->_s());
				return $halfPi;
			}

			// |x| > 1 or x is NaN
			return new Decimal(\NAN, $c); 
		}

		// TODO? Special case asin(1/2) = pi/6 and asin(-1/2) = -pi/6

		$c->precision = $pr+6;
		$c->rounding = 1;

		$x = $x->div((new Decimal(1, $c))->minus($x->times($x))->sqrt()->plus(1))->atan();

		$c->precision = $pr;
		$c->rounding = $rm;

		return $x->times(2);
	}

	/**
	 * Alias to inverseSine() method.
	 *
	 * @see inverseSine()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function asin () : Decimal
	{ return $this->inverseSine(); }
	
	/**
	 * Return a new Decimal whose value is the arcsine in
	 * radians of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function asinOf ( $x ) : Decimal
	{ return (new Decimal($x))->asin(); }
	
	/**
	 * Return a new Decimal whose value is the arctangent
	 * (inverse tangent) in radians of the value of this Decimal.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [-pi/2, pi/2]
	 *
	 * atan(x) = x - x^3/3 + x^5/5 - x^7/7 + ...
	 *
	 * atan(0)         = 0
	 * atan(-0)        = -0
	 * atan(1)         = pi/4
	 * atan(-1)        = -pi/4
	 * atan(Infinity)  = pi/2
	 * atan(-Infinity) = -pi/2
	 * atan(NaN)       = NaN
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function inverseTangent () : Decimal
	{
		$x = $this;
		$c = $this->_c();
		$pr = $c->precision;
		$rm = $c->rounding;

		if ( !$x->isFinite() )
		{
			if ( $x->isNaN() )
			{ return new Decimal(\NAN, $c); }

			if ( $pr + 4 <= static::PI_PRECISION )
			{
				$r = DecimalHelper::getPi($c, $pr+4, $rm)->times(0.5);
				$r->s($x->_s());
				return $r;
			}
		}
		else if ( $x->isZero() )
		{ return new Decimal($x, $c); }
		else if ( $x->abs()->eq(1) && $pr+4 <= static::PI_PRECISION )
		{
			$r = DecimalHelper::getPi($c, $pr+4, $rm)->times(0.25);
			$r->s($x->_s());
			return $r;
		}
		
		$c->precision = $wpr = $pr+10;
		$c->rounding = 1;

		// TODO? if (x >= 1 && pr <= PI_PRECISION) atan(x) = halfPi * x.s - atan(1 / x);
	
		// Argument reduction
		// Ensure |x| < 0.42
		// atan(x) = 2 * atan(x / (1 + sqrt(1 + x^2)))

		$k = \min(28, ($wpr/static::LOG_BASE+2) | 0);
		
		for ( $i = $k; $i; --$i )
		{ $x = $x->div($x->times($x)->plus(1)->sqrt()->plus(1)); }

		DecimalHelper::external(false);

		$j = (int)\ceil($wpr/static::LOG_BASE);
		$n = 1;
		$x2 = $x->times($x);
		$r = new Decimal($x,$c);
		$px = $x;

		// atan(x) = x - x^3/3 + x^5/5 - x^7/7 + ...
		for (; $i !== -1; )
		{
			$px = $px->times($x2);
			$t = $r->minus($px->div(($n+=2)));
			
			$px = $px->times($x2);
			$r = $t->plus($px->div(($n+=2)));

			if ( isset($r->_d()[$j]) )
			{ for ( $i = $j; $r->_d()[$i] == $t->_d()[$i] && $i--; ); }
		}

		if ( $k )
		{ $r = $r->times(3 << ($k -1)); }

		DecimalHelper::external(true);
		
		$c->precision = $pr;
		$c->rounding = $rm;

		return DecimalHelper::finalise($r, $pr, $rm, true);
	}

	/**
	 * Alias to hyperbolicTangent() method.
	 *
	 * @see hyperbolicTangent()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function atan () : Decimal
	{ return $this->inverseTangent(); }
	
	/**
	 * Return a new Decimal whose value is the arcsine in
	 * radians of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function atanOf ( $x ) : Decimal
	{ return (new Decimal($x))->atan(); }

	/**
	 * Return a new Decimal whose value is the arctangent
	 * in radians of `y/x` in the range -pi to pi (inclusive),
	 * rounded to `precision` significant digits using rounding
	 * mode `rounding`.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [-pi, pi]
	 *
	 * atan2(±0, -0)               = ±pi
	 * atan2(±0, +0)               = ±0
	 * atan2(±0, -x)               = ±pi for x > 0
	 * atan2(±0, x)                = ±0 for x > 0
	 * atan2(-y, ±0)               = -pi/2 for y > 0
	 * atan2(y, ±0)                = pi/2 for y > 0
	 * atan2(±y, -Infinity)        = ±pi for finite y > 0
	 * atan2(±y, +Infinity)        = ±0 for finite y > 0
	 * atan2(±Infinity, x)         = ±pi/2 for finite x
	 * atan2(±Infinity, -Infinity) = ±3*pi/4
	 * atan2(±Infinity, +Infinity) = ±pi/4
	 * atan2(NaN, x) = NaN
	 * atan2(y, NaN) = NaN
	 *
	 * @param Decimal|float|int|string $y The y-coordinate.
	 * @param Decimal|float|int|string $x The x-coordinate.
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function atan2Of ( $y, $x ) : Decimal
	{ 
		$c = DecimalConfig::instance();
		$y = new Decimal($y, $c);
		$x = new Decimal($x, $c);

		$pr = $c->precision;
		$rm = $c->rounding;
		$wpr = $pr + 4;

		// Either NaN
		if ( $y->isNaN() || $x->isNaN() )
		{ $r = new Decimal(\NAN); }
		// Both ±Infinity
		else if ( !$y->isFinite() && !$x->isFinite() )
		{
			$r = DecimalHelper::getPi($c, $wpr, 1)->times($x->isPos() ? 0.25 : 0.75);
			$r->s($y->_s());
		}
		// x is ±Infinity or y is ±0
		else if ( !$x->isFinite() || $y->isZero() )
		{
			$r = $x->_s() < 0 ? DecimalHelper::getPi($c, $pr, $rm) : new Decimal(0, $c);
			$r->s($y->_s());
		}
		// y is ±Infinity or x is ±0
		else if ( !$y->isFinite() || $x->isZero() )
		{
			$r = DecimalHelper::getPi($c, $wpr, 1)->times(0.5);
			$r->s($y->_s());
		}
		// Both non-zero and finite
		// x is neg
		else if ( $x->isNeg() )
		{
			$c->precision = $wpr;
			$c->rounding = 1;

			$r = static::atanOf(DecimalHelper::divide($y, $x, $wpr, 1));
			$x = DecimalHelper::getPi($c, $wpr, 1);

			$c->precision = $pr;
			$c->rounding = $rm;

			$r = $y->isNeg() ? $r->minus($x) : $r->plus($x);
		}
		// x is pos
		else
		{ $r = static::atanOf(DecimalHelper::divide($y, $x, $wpr, 1)); }

		return $r;
	}

	/**
	 * Return true if object is a Decimal instance
	 * otherwise return false.
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function isDecimal ( $x ) : bool
	{ return $x instanceof Decimal; }

	/**
	 * Return true if the value of this Decimal 
	 * is a finite number, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function isFinite () : bool
	{ return !empty($this->_digits); }

	/**
	 * Return true if the value of this Decimal 
	 * is an integer, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function isInt () : bool
	{ return $this->isFinite() && \floor($this->_exponent/self::LOG_BASE) > count($this->_digits) - 2; }

	/**
	 * Return true if the value of this Decimal 
	 * is NaN, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function isNaN () : bool
	{ return \is_nan($this->_sign); }

	/**
	 * Return true if the value of this Decimal
	 * is negative, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function isNegative () : bool
	{ return $this->_sign < 0; }

	/**
	 * Alias to isNegative() method.
	 *
	 * @see isNegative()
	 * @since 1.0.0
	 * @return bool
	 */
	public function isNeg () : bool
	{ return $this->isNegative(); }

	/**
	 * Return true if the value of this Decimal
	 * is positive, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function isPositive () : bool
	{ return $this->_sign > 0; }

	/**
	 * Alias to isPositive() method.
	 *
	 * @see isPositive()
	 * @since 1.0.0
	 * @return bool
	 */
	public function isPos () : bool
	{ return $this->isPositive(); }

	/**
	 * Return true if the value of this Decimal 
	 * is 0 or -0, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function isZero () : bool
	{ return $this->isFinite() && $this->_digits[0] === 0; }

	/**
	 * Undocumented function
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function lessThan ( $y ) : bool
	{ return $this->cmp($y) < 0; }

	/**
	 * Alias to lessThan() method.
	 *
	 * @see lessThan()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function lt ( $y ) : bool
	{ return $this->lessThan($y); }

	/**
	 * Undocumented function
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function lessThanOrEqualTo ( $y ) : bool
	{ return $this->cmp($y) < 1; }

	/**
	 * Alias to lessThanOrEqualTo() method.
	 *
	 * @see lessThanOrEqualTo()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return bool
	 */
	public function lte ( $y ) : bool
	{ return $this->lessThanOrEqualTo($y); }

	/**
	 * Return the logarithm of the value of this Decimal 
	 * to the specified base, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * If no base is specified, return log[10](arg).
	 *
	 * log[base](arg) = ln(arg) / ln(base)
	 *
	 * The result will always be correctly rounded if the base
	 * of the log is 10, and 'almost always' otherwise:
	 *
	 * Depending on the rounding mode, the result may be incorrectly
	 * rounded if the first fifteen rounding digits are [49]99999999999999
	 * or [50]00000000000000. In that case, the maximum error between
	 * the result and the correctly rounded result will be one ulp
	 * (unit in the last place).
	 *
	 * log[-b](a)       = NaN
	 * log[0](a)        = NaN
	 * log[1](a)        = NaN
	 * log[NaN](a)      = NaN
	 * log[Infinity](a) = NaN
	 * log[b](0)        = -Infinity
	 * log[b](-0)       = -Infinity
	 * log[b](-a)       = NaN
	 * log[b](1)        = 0
	 * log[b](Infinity) = Infinity
	 * log[b](NaN)      = NaN
	 *
	 * @param Decimal|float|int|string $base The base of the logarithm.
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function logarithm ( $base = null ) : Decimal
	{
		$x = $this;
		$c = $this->_c();
		$pr = $c->precision;
		$rm = $c->rounding;
		$guard = 5;

		// Default base is 10.
		if ( \is_null($base) )
		{
			$base = new Decimal(10,$c);
			$isBase10 = true;
		}
		else
		{
			$base = new Decimal($base);
			$d = $base->_d();

			if ( $base->isNeg() || $base->isZero() || !$base->isFinite() || $base->eq(1) )
			{ return new Decimal(\NAN,$c); }

			$isBase10 = $base->eq(10);
		}

		$d = $x->_d();

		// Is arg negative, non-finite, 0 or 1?
		// TODO? May up this if to before "base if"
		if ( $x->isNeg() || $x->isZero() || !$x->isFinite() || $x->eq(1) )
		{
			$n = 0;

			if ( !$x->isZero() )
			{ $n = -\INF; }
			else if ( $x->_s() !== 1 )
			{ $n = \NAN; }
			else if ( !$x->isFinite() )
			{ $n = \INF; }

			return new Decimal($n, $c);
		}

		// The result will have a non-terminating decimal
		// expansion if base is 10 and arg is not an
		// integer power of 10.
		if ( $isBase10 )
		{
			if ( \count($d) > 1 )
			{ $inf = true; }
			else 
			{
				for ( $k = $d[0]; $k % 10 === 0; )
				{ $k /= 10; }

				$inf = $k !== 1;
			}
		}

		DecimalHelper::external(false);

		$sd = $pr+$guard;
		$num = DecimalHelper::naturalLogarithm($x, $sd);
		$denominator = $isBase10 
			? DecimalHelper::getLn10($c, $sd + 10) 
			: DecimalHelper::naturalLogarithm($base, $sd);

		$r = DecimalHelper::divide($num, $denominator, $sd, 1);

		// If at a rounding boundary, i.e. the result's rounding
		// digits are [49]9999 or [50]0000, calculate 10 further digits.
		//
		// If the result is known to have an infinite decimal expansion,
		// repeat this until it is clear that the result is above or below
		// the boundary. Otherwise, if after calculating the 10 further
		// digits, the last 14 are nines, round up and assume the result is exact.
		// Also assume the result is exact if the last 14 are zero.
		//
		// Example of a result that will be incorrectly rounded:
		// log[1048576](4503599627370502) = 2.60000000000000009610279511444746...
		// The above result correctly rounded using ROUND_CEIL to 1 decimal place
		// should be 2.7, but it will be given as 2.6 as there are 15 zeros 
		// immediately after the requested decimal place, so the exact result
		// would be assumed to be 2.6, which rounded using ROUND_CEIL to 1 decimal
		// place is still 2.6.
		if ( DecimalHelper::checkRoundingDigits($r->_d(), ($k = $pr), $rm) )
		{
			do
			{
				$sd += 10;
				$num = DecimalHelper::naturalLogarithm($x, $sd);
				$denominator = $isBase10 
					? DecimalHelper::getLn10($c, $sd + 10) 
					: DecimalHelper::naturalLogarithm($base, $sd);

				$r = DecimalHelper::divide($num, $denominator, $sd, 1);

				if ( !$inf )
				{
					// Check for 14 nines from the 2nd rounding
					// digit, as the first may be 4.
					if ( \intval(DecimalHelper::slice(DecimalHelper::digitsToString($r->_d()), $k+1, $k+15)) + 1 == 1e14 )
					{ $r = DecimalHelper::finalise($r, $pr+1, 0); }

					break;
				}
			}
			while (DecimalHelper::checkRoundingDigits($r->_d(), ($k += 10), $rm));
		}

		DecimalHelper::external(true);
		return DecimalHelper::finalise($r, $pr, $rm);
	}

	/**
	 * Alias to logarithm() method.
	 *
	 * @see logarithm()
	 * @param Decimal|float|int|string $base
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function log ( $base = null ) : Decimal
	{ return $this->logarithm($base); }

	/**
	 * Return a new Decimal whose value is the log of `x` 
	 * to the base `y`, or to base 10 if no base is specified,
	 * rounded to `precision` significant digits using rounding
	 * mode `rounding`.
	 *
	 * log[y](x)
	 * 
	 * @param array<Decimal|float|int|string> $x
	 * @param array<Decimal|float|int|string> $base The base of the logarithm.
	 * @since 1.0.0
	 * @return bool
	 */
	public static function logOf ( $x, $base = null )
	{ return (new Decimal($x))->log($base); }

	/**
	 * Return a new Decimal whose value is the base 2 
	 * logarithm of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * log[2](x)
	 * 
	 * @param array<Decimal|float|int|string> $x
	 * @since 1.0.0
	 * @return bool
	 */
	public static function log2Of ( $x )
	{ return (new Decimal($x))->log(2); }

	/**
	 * Return a new Decimal whose value is the base 10
	 * logarithm of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * log[10](x)
	 * 
	 * @param array<Decimal|float|int|string> $x
	 * @since 1.0.0
	 * @return bool
	 */
	public static function log10Of ( $x )
	{ return (new Decimal($x))->log(10); }

	/**
	 * Return a new Decimal whose value is 
	 * the maximum of the arguments.
	 * 
	 * @param array<Decimal|float|int|string> ...$args
	 * @since 1.0.0
	 * @return bool
	 */
	public static function maxOf ()
	{ return DecimalHelper::maxOrMin(DecimalConfig::instance(), 'lt', \func_get_args()); }

	/**
	 * Return a new Decimal whose value is 
	 * the minimum of the arguments.
	 * 
	 * @param array<Decimal|float|int|string> ...$args
	 * @since 1.0.0
	 * @return bool
	 */
	public static function minOf ()
	{ return DecimalHelper::maxOrMin(DecimalConfig::instance(), 'gt', \func_get_args()); }

	/**
	 * Return a new Decimal whose value is the value of this 
	 * Decimal minus `y`, rounded to `precision` significant 
	 * digits using rounding mode `rounding`.
	 * 
	 *  n - 0 = n
	 *  n - N = N
	 *  n - I = -I
	 *  0 - n = -n
	 *  0 - 0 = 0
	 *  0 - N = N
	 *  0 - I = -I
	 *  N - n = N
	 *  N - 0 = N
	 *  N - N = N
	 *  N - I = N
	 *  I - n = I
	 *  I - 0 = I
	 *  I - N = N
	 *  I - I = N
	 * 
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	// Not implemented
	public function minus ( $y )
	{
		$x = $this;
		$c = $this->_config;
		$y = new Decimal($y, $c);

		// If either is not finite...
		if ( !$x->isFinite() || !$y->isFinite() || $x->isNan() || $y->isNaN() )
		{
			// NaN is NaN
			if ( $x->isNan() || $y->isNaN() )
			{ return new Decimal(\NAN, $c); }
			// Return y negated if x is finite and y is ±Infinity.
			else if ( $x->isFinite() )
			{ $y->s(-$y->_s()); }
			// Return x if y is finite and x is ±Infinity.
			// Return x if both are ±Infinity with different signs.
			// Return NaN if both are ±Infinity with the same sign.
			else
			{ $y = new Decimal( $y->isFinite() || $x->_s() !== $y->_s() ? $x : \NAN, $c ); }

			return $y;
		}

		// If signs differ...
		if ( $x->_s() != $y->_s() ) 
		{
			$y->s(-$y->_s());
			return $x->plus($y);
		}

		$xd = $x->_d();
		$yd = $x->_d();
		$pr = $c->precision;
		$rm = $c->rounding;

		// If either is zero...
		if ( empty($xd[0]) || empty($yd[0]) )
		{
			// Return y negated if x is zero and y is non-zero.
			if ( !empty($yd[0]) )
			{ $y->s(!$y->_s()); }
			// Return x if y is zero and x is non-zero.
			else if ( !empty($xd[0]) )
			{ $y = new Decimal($x, $c); }
			// Return zero if both are zero.
			// From IEEE 754 (2008) 6.3: 0 - 0 = -0 - -0 = -0 when rounding to -Infinity.
			else
			{ return new Decimal($rm === 3 ? -0 : 0); }

			return DecimalHelper::isExternal() ? DecimalHelper::finalise($y, $pr, $rm) : $y;
		}

		// x and y are finite, non-zero numbers with the same sign.
	
		// Calculate base 1e7 exponents.
		$e = (int)\floor($y->_e()/static::LOG_BASE);
		$xe = (int)\floor($x->_e()/static::LOG_BASE);
		$k = $xe - $e;

		// If base 1e7 exponents differ...
		if ( $k )
		{
			$xlty = $k < 0;

			if ( $xlty )
			{
				$d = $xd;
				$k = -$k;
				$len = \count($yd);
			}
			else
			{
				$d = $yd;
				$e = $xe;
				$len = \count($yd);
			}
			
			// Numbers with massively different exponents 
			// would result in a very high number of zeros 
			// needing to be prepended, but this can be avoided 
			// while still ensuring correct rounding by limiting 
			// the number of zeros to `Math.ceil(pr / LOG_BASE) + 2`.
			$i = \max((int)\ceil($pr/static::LOG_BASE), $len) + 2;

			if ( $k > $i )
			{ 
				$k = $i; 
				$d = $d[0];
			}

			// Prepend zeros to equalise exponents.
			$d = \array_reverse($d);
			
			for ( $i = $k; $i--; )
			{ $d[] = 0; }

			$d = \array_reverse($d);

			// Base 1e7 exponents equal.
		}
		else
		{
			// Check digits to determine which is the bigger number.
			$i = \count($xd);
			$len = \count($yd);

			$xlty = $i < $len;

			if ( $xlty )
			{ $len = $i; }

			for ( $i = 0; $i < $len; $i++ )
			{
				if ( $xd[$i]??null !== $yd[$i]??null )
				{ $xlty = $xd[$i] < $yd[$i]; }
			}

			$k = 0;
		}

		if ( $xlty )
		{
			$d = $xd;
			$xd = $yd;
			$yd = $d;
			$y->s($y->_s());
		} 

		$len = \count($xd);

		// Append zeros to `xd` if shorter.
		// Don't add zeros to `yd` if shorter as subtraction
		// only needs to start at `yd` length.
		for ( $i = \count($yd) - $len; $i > 0; --$i )
		{ $xd[$len++] = 0; }

		// Subtract yd from xd.
		for ( $i = \count($yd); $i > $k; )
		{
			if ( $xd[--$i] < $yd[$i] )
			{
				for ( $j = $i; $j && $xd[--$j] === 0; )
				{ $xd[$j] = static::BASE - 1; }

				--$xd[$j];
				$xd[$i] += static::BASE;
			}
			
			$xd[$i] -= $yd[$i];
		}

		// Remove trailing zeros.
		for ( ; $xd[--$len]??null === 0; )
		{ \array_pop($xd); }

		// Remove trailing zeros.
		for ( ; $xd[0]??null === 0; \array_shift($xd) )
		{ --$e; }

		// Zero?
		if ( $xd[0] === 0 )
		{ return new Decimal($rm === 3 ? '-0' : 0, $c); }

		$y->d($xd);
		$y->e(DecimalHelper::getBase10Exponent($xd,$e));

		return DecimalHelper::isExternal() ? DecimalHelper::finalise($y, $pr, $rm) : $y;
	}

	/**
	 * Alias to minus() method.
	 *
	 * @see minus()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function sub ( $y ) : Decimal
	{ return $this->minus($y); }
	
	/**
	 * Return a new Decimal whose value is `x` minus `y`,
	 * rounded to `precision` significant digits using
	 * rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function subOf ( $x, $y ) : Decimal
	{ return (new Decimal($x))->sub($y); }

	/**
	 * Return a new Decimal whose value is the value of this 
	 * Decimal modulo `y`, rounded to `precision` significant 
	 * digits using rounding mode `rounding`.
	 *
	 * The result depends on the modulo mode.
	 * 
	 *   n % 0 =  N
	 *   n % N =  N
	 *   n % I =  n
	 *   0 % n =  0
	 *  -0 % n = -0
	 *   0 % 0 =  N
	 *   0 % N =  N
	 *   0 % I =  0
	 *   N % n =  N
	 *   N % 0 =  N
	 *   N % N =  N
	 *   N % I =  N
	 *   I % n =  N
	 *   I % 0 =  N
	 *   I % N =  N
	 *   I % I =  N
	 * 
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function modulo ( $y )
	{
		$x = $this;
		$c = $this->_config;
		$y = new Decimal($y, $c);

		// Return NaN if x is ±Infinity or NaN, or y is NaN or ±0.
		if ( !$x->isFinite() || $x->isNaN() || ( $y->isNaN() || $y->isZero() ) )
		{ return new Decimal(\NAN, $c); }

		// Return x if y is ±Infinity or x is ±0.
		if ( !$y->isFinite() || ( $x->isNaN() || $x->isZero() ) )
		{ return DecimalHelper::finalise(new Decimal($x, $c), $c->precision, $c->rounding); }

		// Prevent rounding of intermediate calculations.
		DecimalHelper::external(false);

		if ( $c->modulo == 9 )
		{
			// Euclidian division: q = sign(y) * floor(x / abs(y))
			// result = x - q * y    where  0 <= result < abs(y)
			$q = DecimalHelper::divide($x, $y->abs(), 0, 3, 1);
			$q->s($q->_s()*$y->_s());
		}
		else
		{ $q = DecimalHelper::divide($x, $y, 0, $c->modulo, 1); }

		$q = $q->times($y);

		DecimalHelper::external(true);
		return $x->minus($q);
	}

	/**
	 * Alias to modulo() method.
	 *
	 * @see modulo()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function mod ( $y ) : Decimal
	{ return $this->modulo($y); }
	
	/**
	 * Return a new Decimal whose value is `x` modulo `y`,
	 * rounded to `precision` significant digits using
	 * rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function modOf ( $x, $y ) : Decimal
	{ return (new Decimal($x))->mod($y); }

	/**
	 * Return a new Decimal whose value is the natural 
	 * exponential of the value of this Decimal, i.e. the 
	 * base e raised to the power the value of this Decimal, 
	 * rounded to `precision` significant digits using 
	 * rounding mode `rounding`.
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function naturalExponential () : Decimal
	{ return DecimalHelper::naturalExponential($this); }

	/**
	 * Alias to naturalExponential() method.
	 *
	 * @see naturalExponential()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function exp () : Decimal
	{ return $this->naturalExponential(); }
	
	/**
	 * Return a new Decimal whose value is the natural
	 * exponential of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function expOf ( $x ) : Decimal
	{ return (new Decimal($x))->exp(); }

	/**
	 * Return a new Decimal whose value is the natural 
	 * logarithm of the value of this Decimal, rounded 
	 * to `precision` significant digits using rounding 
	 * mode `rounding`.
	 *
	 * @see naturalLogarithm()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function naturalLogarithm () : Decimal
	{ return DecimalHelper::naturalLogarithm($this); }

	/**
	 * Alias to naturalLogarithm() method.
	 *
	 * @see naturalLogarithm()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function ln () : Decimal
	{ return $this->naturalLogarithm(); }
	
	/**
	 * Return a new Decimal whose value is the natural
	 * logarithm of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function lnOf ( $x ) : Decimal
	{ return (new Decimal($x))->exp(); }

	/**
	 * Return a new Decimal whose value is the value 
	 * of this Decimal negated, i.e. as if multiplied by
    * -1.
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function negated () : Decimal
	{
		$x = new Decimal($this, $this->_config);
		$x->_sign = -$x->_sign;
		return DecimalHelper::finalise($x);
	}
	
	/**
	 * Alias to negated() method.
	 *
	 * @see negated()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function neg () : Decimal
	{ return $this->negated(); }

	/**
	 * Return a new Decimal whose value is the value of 
	 * this Decimal plus `y`, rounded to `precision`
	 * significant digits using rounding mode `rounding`.
	 * 
	 *  n + 0 = n
	 *  n + N = N
	 *  n + I = I
	 *  0 + n = n
	 *  0 + 0 = 0
	 *  0 + N = N
	 *  0 + I = I
	 *  N + n = N
	 *  N + 0 = N
	 *  N + N = N
	 *  N + I = N
	 *  I + n = I
	 *  I + 0 = I
	 *  I + N = N
	 *  I + I = I
	 * 
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function plus ( $y )
	{
		$x = $this;
		$c = $this->_c();
		$y = new Decimal($y, $c);

		// If either is not finite...
		if ( !$x->isFinite() || !$y->isFinite() )
		{
			// Return NaN if either is NaN.
			if ( $x->isNaN() || $y->isNaN() )
			{ return new Decimal(\NAN, $c); }
			// Return x if y is finite and x is ±Infinity.
			// Return x if both are ±Infinity with the same sign.
			// Return NaN if both are ±Infinity with different signs.
			// Return y if x is finite and y is ±Infinity.
			else if ( !$x->isFinite() )
			{ $y = new Decimal($y->isFinite() || $x->_s() === $y->_s() ? $x : \NAN, $c); }

			return $y;
		}

		// If signs differ...
		if ( $x->_s() !== $y->_s() )
		{
			$y->s(-$y->_s());
			return $x->minus($y);
		}

		$xd = $x->_d();
		$yd = $y->_d();
		$pr = $c->precision;
		$rm = $c->rounding;

		// If either is zero...
		if ( $x->isZero() || $y->isZero() )
		{
			// Return x if y is zero.
			// Return y if y is non-zero.
			if ( $y->isZero() )
			{ return new Decimal($x, $c); }

			return DecimalHelper::isExternal() ? DecimalHelper::finalise($y, $pr, $rm) : $y;
		}

		// x and y are finite, non-zero numbers with the same sign.
	
		// Calculate base 1e7 exponents.
		$k = (int)\floor($x->_e()/static::LOG_BASE);
		$e = (int)\floor($y->_e()/static::LOG_BASE);

		$i = $k-$e;

		// If base 1e7 exponents differ...
		if ( $i )
		{
			if ( $i < 0 )
			{
				$d = $xd;
				$i = -$i;
				$len = \count($yd);
			}
			else
			{
				$d = $yd;
				$e = $k;
				$len = \count($xd);
			}
			
			// Limit number of zeros prepended to max(ceil(pr / LOG_BASE), len) + 1.
			$k = (int)\ceil($pr/static::LOG_BASE);
			$len = $k > $len ? $k + 1 : $len + 1;

			if ( $i > $len )
			{
				$i = $len;
				$d = [$d[0]];
			}
			
			// Prepend zeros to equalise exponents. 
			// Note: Faster to use reverse then do unshifts.
			$d = \array_reverse($d);

			for ( ; $i--; )
			{ $d[] = 0; }

			$d = \array_reverse($d);
		}

		$len = \count($xd);
		$i = \count($yd);

		// If yd is longer than xd, swap xd and yd so 
		// xd points to the longer array.
		if ( $len - $i < 0 )
		{
			$i = $len;
			$d = $yd;
			$yd = $xd;
			$xd = $d;
		}

		// Only start adding at yd.length - 1 as the further 
		// digits of xd can be left as they are.
		for ( $carry = 0; $i; )
		{
			$carry = (($xd[--$i] = $xd[$i] + $yd[$i] + $carry) / static::BASE) | 0;
			$xd[$i] %= static::BASE;
		}

		if ( $carry )
		{
			\array_unshift($xd, $carry);
			++$e;
		}

		// Remove trailing zeros.
		// No need to check for zero, as +x + +y != 0 && -x + -y != 0
		for ( $len = \count($xd); $xd[--$len] == 0; )
		{ \array_pop($xd); }

		$y->d($xd);
		$y->e(DecimalHelper::getBase10Exponent($xd, $e));

		return DecimalHelper::isExternal() ? DecimalHelper::finalise($y, $pr, $rm) : $y;
	}

	/**
	 * Alias to plus() method.
	 * 
	 * @see plus()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function add ( $y ) : Decimal
	{ return $this->plus($y); }
	
	/**
	 * Return a new Decimal whose value is the sum
	 * of `x` and `y`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function addOf ( $x, $y ) : Decimal
	{ return (new Decimal($x))->add($y); }

	/**
	 * Return the number of significant 
	 * digits of the value of this Decimal.
	 *
	 * @param bool|int $z Whether to count integer-part trailing zeros: true, false, 1 or 0.
	 * @since 1.0.0
	 * @return int
	 */
	public function precision ( $z = false ) : int
	{
		$x = $this;
		$z = (bool)$z;

		if ( !\is_bool($z) )
		{ throw new RuntimeException('Argument must be a bool or an integer 0 or 1 value.'); }

		if ( $x->isFinite() )
		{
			$k = DecimalHelper::getPrecision($x->_d());

			if ( $z && $x->_e() + 1 > $k )
			{ $k = $x->_e() + 1; }
		}
		else
		{ $k = \NAN; }

		return $k;
	}

	/**
	 * Alias to precision() method.
	 *
	 * @param bool|int $z Whether to count integer-part trailing zeros: true, false, 1 or 0.
	 * @see precision()
	 * @since 1.0.0
	 * @return int
	 */
	public function sd ( $z = false ) : int
	{ return $this->precision($z); }

	/**
	 * Returns a new Decimal with a random value equal to or
	 * greater than 0 and less than 1, and with `sd`, or
	 * `Decimal.precision` if `sd` is omitted, significant
	 * digits (or less if trailing zeros are produced).
	 *
	 * @todo Implement random bytes
	 * @param int $sd Significant digits. Integer, 0 to MAX_DIGITS inclusive.
	 * @since 1.0.0
	 * @return void
	 */
	public static function random ( int $sd = null )
	{
		$i = 0;
		$c = DecimalConfig::instance();
		$r = new Decimal(1, $c);
		$rd = [];

		if ( \is_null($sd) )
		{ $sd = $c->precision; }
		else
		{ DecimalHelper::checkInt32($sd, 1, DecimalConfig::MAX_DIGITS); }

		$k = (int)\ceil($sd/static::LOG_BASE);

		if ( \function_exists('random_int') )
		{
			$d = static::__randomValues($k);

			for ( ; $i < $k; )
			{
				$n = $d[$i];

				// 0 <= n < 4294967296
				// Probability n >= 4.29e9, is 4967296 / 4294967296 = 0.00116 (1 in 865).
				if ( $n >= 4.29e9 )
				{ $d[$i] = $d = static::__randomValues(1)[0]; }
				else
				// 0 <= n <= 4289999999
				// 0 <= (n % 1e7) <= 9999999
				{ $rd[$i++] = $n % 1e7; }
			}
		}
		else
		{
			for (; $i < $k;) 
			{ $rd[$i++] = (\rand(0,1) * 1e7) | 0; }
		}

		$k = $rd[--$i];
		$sd %= static::LOG_BASE;

		// Convert trailing digits to zeros according to sd.
		if ( $k && $sd )
		{
			$n = \pow(10, static::LOG_BASE - $sd);
			$rd[$i] = (($k/$n) | 0) * $n;
		}

		// Remove trailing words which are zero.
		for ( ; $rd[$i] === 0; $i-- )
		{ \array_pop($rd); }

		// Zero?
		if ( $i < 0 )
		{
			$e = 0;
			$rd = [0];
		}
		else
		{
			$e = -1;

			// Remove leading words which are zero and adjust exponent accordingly.
			for (; $rd[0] === 0; $e -= static::LOG_BASE)
			{ \array_shift($rd); }

			// Count the digits of the first word of rd to determine leading zeros.
			for ( $k = 1, $n = $rd[0]; $n >= 10; $n /= 10) 
			{ $k++; }

			// Adjust the exponent for leading zeros of the first word of rd.
			if ( $k < static::LOG_BASE ) 
			{ $e -= static::LOG_BASE - $k; } 
		}

		$r->e($e);
		$r->d($rd);

		return $r;
	}

	/**
	 * Return a new Decimal whose value is the value of this 
	 * Decimal rounded to a whole number using rounding 
	 * mode `rounding`.
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function round () : Decimal
	{ 
		return DecimalHelper::finalise(
			new Decimal($this, $this->_config),
			$this->_exponent + 1,
			$this->_config->rounding
		); 
	}
	
	/**
	 * Return a new Decimal whose value is `x` rounded to an
	 * integer using rounding mode `rounding`.
	 *
	 * To emulate PHP default, set rounding to 4 (ROUND_HALF_UP).
	 *
	 * @param Decimal|float|int|string $x
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function roundOf ( $x ) : Decimal
	{ return (new Decimal($x))->round(); }
	
	/**
	 * Return
	 *   1    if x > 0,
	 *  -1    if x < 0,
	 *   0    if x is 0,
	 *   0    if x is -0,
	 *   NaN  otherwise
	 * 
	 * @since 1.0.0
	 * @return int|float
	 */
	public static function signOf ( $x )
	{
		$x = new Decimal($x);
		
		if ( $x->isFinite() )
		{
			if ( !$x->isZero() )
			{ return $x->_s(); }
			else 
			{ return 0; }
		}
		
		return $x->_s() ?? \NAN;
	}

	/**
	 * Return a new Decimal whose value is the sine 
	 * of the value in radians of this Decimal.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [-1, 1]
	 *
	 * sin(x) = x - x^3/3! + x^5/5! - ...
	 *
	 * sin(0)         = 0
	 * sin(-0)        = -0
	 * sin(Infinity)  = NaN
	 * sin(-Infinity) = NaN
	 * sin(NaN)       = NaN
	 * 
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function sine ()
	{
		$x = $this;
		$c = $this->_c();

		if ( !$x->isFinite() )
		{ return new Decimal(\NAN, $c); }

		if ( $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max($x->_e(), $x->sd()) + static::LOG_BASE;
		$c->rounding = 1;

		$x = DecimalHelper::sine($c, DecimalHelper::toLessThanHalfPi($c, $x));

		$c->precision = $pr;
		$c->rounding = $rm;

		return DecimalHelper::finalise(
			DecimalHelper::_quadrant() > 2 ? $x->neg() : $x, 
			$pr, 
			$rm, 
			true
		);
	}

	/**
	 * Alias to sine() method.
	 *
	 * @see sine()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function sin () : Decimal
	{ return $this->sine(); }
	
	/**
	 * Return a new Decimal whose value is the sine of `x`,
	 * rounded to `precision` significant digits using
	 * rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function sinOf ( $x ) : Decimal
	{ return (new Decimal($x))->sine(); }

	/**
	 * Return a new Decimal whose value is the square root 
	 * of this Decimal, rounded to `precision`
	 * significant digits using rounding mode `rounding`.
	 *
	 *  sqrt(-n) =  N
	 *  sqrt(N)  =  N
	 *  sqrt(-I) =  N
	 *  sqrt(I)  =  I
	 *  sqrt(0)  =  0
	 *  sqrt(-0) = -0
	 * 
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function squareRoot ()
	{
		$x = $this;
		$c = $x->_c();
		$d = $x->_d();
		$e = $x->_e();
		$s = $x->_s();

		// Negative/NaN/Infinity/zero?
		if ( $x->isNeg() || $x->isNaN() || !$x->isFinite() || $x->isZero() )
		{
			$n = \INF;

			if ( $x->isNeg() || $x->isNaN() )
			{ $n = \NAN; }
			else if ( $x->isZero() )
			{ $n = $x; }
		
			return new Decimal($n, $c);
		}

		DecimalHelper::external(false);

		// Estimate sqrt
		$s = \sqrt($x->toInt());

		// Math.sqrt underflow/overflow?
		// Pass x to Math.sqrt as integer, 
		// then adjust the exponent of the result.
		if ( $s == 0 || $s == \INF )
		{
			$n = DecimalHelper::digitsToString($d);

			if ( (\strlen($n)+$e) % 2 === 0 )
			{ $n .= '0'; }

			$s = \sqrt((int)$n);
			$e = (int)\floor(($e+1)/2) - \intval($e < 0 || $e % 2);

			if ( $s == \INF )
			{ $n = '5e'.$e; }
			else 
			{
				$n = \strval(\exp($s));
				$n = DecimalHelper::slice(0, \strpos($n, 'e')+1) + $e;
			}

			$r = new Decimal($n, $c);
		}

		$sd = ($e = $c->precision) + 3;
		$rep = null;

		// Newton-Raphson iteration.
		for (;;)
		{
			$t = $r;
			$r = $t->plus(DecimalHelper::divide($x, $t, $sd+2, 1))->times(0.5);

			// TODO? Replace with for-loop and checkRoundingDigits.
			if (
				DecimalHelper::slice(DecimalHelper::digitsToString($t->_d()), 0, $sd)
				=== ($n = DecimalHelper::slice(DecimalHelper::digitsToString($r->_d()), 0, $sd))
			)
			{
				$n = DecimalHelper::slice($n, $sd-3, $sd+1);
							
				// The 4th rounding digit may be in error by -1 so 
				// if the 4 rounding digits are 9999 or
				// 4999, i.e. approaching a rounding boundary,
				// continue the iteration.
				if ( $n == '9999' || ( !$rep && $n == '4999') )
				{
					// On the first iteration only, check to see 
					// if rounding up gives the exact result as the
					// nines may infinitely repeat.
					if ( !$rep )
					{
						$t = DecimalHelper::finalise($t, $e+1, 0);

						if ( $t->times($t)->eq($x) )
						{
							$r = $t;
							break;
						}
					}

					$sd += 4;
					$rep = 1;
				}
				else
				{
					// If the rounding digits are null, 0{0,4} 
					// or 50{0,3}, check for an exact result.
					// If not, then there are further digits 
					// and m will be truthy.
					if ( !(int)$n || (!(int)DecimalHelper::slice($n, 1) && $n[0] == '5') )
					{
						$r = DecimalHelper::finalise($r, $e+1, 0);
						$m = !$r->times($r)->eq($x);
					}

					break;
				}
			}
		}

		DecimalHelper::external(true);

		return DecimalHelper::finalise($r, $e, $c->rounding, $m);
	}

	/**
	 * Alias to squareRoot() method.
	 *
	 * @see squareRoot()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function sqrt () : Decimal
	{ return $this->squareRoot(); }
	
	/**
	 * Return a new Decimal whose value is the square
	 * root of `x`, rounded to `precision` significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function sqrtOf ( $x ) : Decimal
	{ return (new Decimal($x))->sqrt(); }

	/**
	 * Return a new Decimal whose value is the tangent
	 * of the value in radians of this Decimal.
	 *
	 * Domain: [-Infinity, Infinity]
	 * Range: [-Infinity, Infinity]
	 *
	 * tan(0)         = 0
	 * tan(-0)        = -0
	 * tan(Infinity)  = NaN
	 * tan(-Infinity) = NaN
	 * tan(NaN)       = NaN
	 * 
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function tangent ()
	{
		$x = $this;
		$c = $this->_c();

		if ( !$x->isFinite() )
		{ return new Decimal(\NAN, $c); }

		if ( $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+10;
		$c->rounding = 1;

		$x = $x->sin();
		$x->s(1);
		$x = DecimalHelper::divide($x, (new Decimal(1, $c))->minus($x->times($x))->sqrt(), $pr + 10, 0);

		$c->precision = $pr;
		$c->rounding = $rm;

		return DecimalHelper::finalise(
			DecimalHelper::_quadrant() == 2 || DecimalHelper::_quadrant() == 4 ? $x->neg() : $x, 
			$pr, 
			$rm, 
			true
		);
	}

	/**
	 * Alias to tangent() method.
	 *
	 * @see tangent()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function tan () : Decimal
	{ return $this->tangent(); }
	
	/**
	 * Return a new Decimal whose value is the tangent
	 * of `x`, rounded to `precision` significant digits
	 * using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function tanOf ( $x ) : Decimal
	{ return (new Decimal($x))->tan(); }

	/**
	 * Return a new Decimal whose value is this Decimal 
	 * times `y`, rounded to `precision` significant 
	 * digits using rounding mode `rounding`.
	 * 
	 *  n * 0 = 0
	 *  n * N = N
	 *  n * I = I
	 *  0 * n = 0
	 *  0 * 0 = 0
	 *  0 * N = N
	 *  0 * I = N
	 *  N * n = N
	 *  N * 0 = N
	 *  N * N = N
	 *  N * I = N
	 *  I * n = I
	 *  I * 0 = N
	 *  I * N = N
	 *  I * I = I
	 * 
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function times ( $y )
	{
		$x = $this;
		$c = $this->_config;
		$xd = $x->_d();
		$y  = new Decimal($y, $c);
		$yd = $y->_d();

		// Multiply signer
		$y->s($y->_s()*$x->_s());

		// If either is NaN, ±Infinity or ±0...
		if ( !$x->isFinite() || $x->isNaN() || !$y->isFinite() || $y->isNaN() )
		{
			// TODO @ FIX IT
			return new Decimal(
				!$y->signed() || (!empty($xd) && empty($yd)) || (!empty($yd) && empty($xd))
				? \NAN
				: (empty($xd) || empty($yd) ? 'INF' : 'INF' ), // TODO signal to infinity
				$c 
			);
		}

		$e = (int)\floor($x->_e()/static::LOG_BASE) + (int)\floor($y->_e()/static::LOG_BASE);
		$xdl = \count($xd);
		$ydl = \count($yd);

		// Ensure xd points to the longer array
		if ( $xdl < $ydl )
		{
			$r = $xd;
			$xd = $yd;
			$yd = $r;
			$rl = $xdl;
			$xdl = $ydl;
			$ydl = $rl;
		}

		$r = [];
		$rl = $xdl + $ydl;

		for ( $i = $rl; $i--; )
		{ $r[] = 0; }

		// Multiply
		for ( $i = $ydl; --$i >= 0; )
		{
			$carry = 0;

			for ( $k = $xdl + $i; $k > $i; )
			{
				$t = $r[$k] + $yd[$i] * $xd[$k-$i-1] + $carry;
				$r[$k--] = $t % static::BASE | 0;
				$carry = $t / static::BASE | 0;
			}

			$r[$k] = ($r[$k] + $carry) % static::BASE | 0;
		}

		// Remove trailing zeros
		for (; !$r[--$rl]; )
		{ \array_pop($r); }

		if ($carry) 
		{ $e++; }
		else
		{ \array_shift($r); }
		
		$y->d($r);
		$y->e(DecimalHelper::getBase10Exponent($r, $e));

		return DecimalHelper::isExternal() ? DecimalHelper::finalise($y, $c->precision, $c->rounding) : $y;
	}

	/**
	 * Alias to times() method.
	 *
	 * @see times()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function mul ( $y ) : Decimal
	{ return $this->times($y); }
	
	/**
	 * Return a new Decimal whose value is `x` multiplied
	 * by `y`, rounded to `precision` significant digits
	 * using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function mulOf ( $x, $y ) : Decimal
	{ return (new Decimal($x))->mul($y); }

	/**   
	 * Return a string representing the value of this 
	 * Decimal in base 2, round to `sd` significant
	 * digits using rounding mode `rm`.
	 *
	 * If the optional `sd` argument is present then 
	 * return binary exponential notation.
	 *
	 * [sd] {number} Significant digits. Integer, 1 to MAX_DIGITS inclusive.
	 * [rm] {number} Rounding mode. Integer, 0 to 8 inclusive.
	 *
	 * @param integer $sd
	 * @param integer $rm
	 * @since 1.0.0
	 * @return string
	 * @throws RuntimeException
	 */
	public function toBinary ( $sd = null, $rm = null ) : string
	{ return DecimalHelper::toStringBinary($this, 2, $sd, $rm); }

	/**
	 * Return a new Decimal whose value is the value 
	 * of this Decimal rounded to a maximum of `dp`
	 * decimal places using rounding mode `rm` or 
	 * `rounding` if `rm` is omitted.
	 *
	 * If `dp` is omitted, return a new Decimal whose 
	 * value is the value of this Decimal.
	 * 
	 * 'toDecimalPlaces() digits out of range: {dp}'
	 * 'toDecimalPlaces() digits not an integer: {dp}'
	 * 'toDecimalPlaces() rounding mode not an integer: {rm}'
	 * 'toDecimalPlaces() rounding mode out of range: {rm}'
	 * 
	 * @param int $dp Decimal places. Integer, 1 to MAX_DIGITS inclusive.
	 * @param int $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return Decimal
	 * @throws RuntimeException
	 */
	public function toDecimalPlaces ( 
		$dp = null, 
		$rm = null 
	) : Decimal
	{
		$x = $this;
		$c = $this->_config;
		$x = new Decimal($x, $c);

		if ( \is_null($dp) )
		{ return $x; }
		
		DecimalHelper::checkInt32($dp, 0, DecimalConfig::MAX_DIGITS);
		
		if ( \is_null($rm) )
		{ $rm = $c->rounding; }
		else
		{ DecimalHelper::checkInt32($rm, 0, 8); }

		return DecimalHelper::finalise($x, $dp + $x->_e() + 1, $rm);
	}

	/**
	 * Alias to toDecimalPlaces() method.
	 *
	 * @see toDecimalPlaces()
	 * @param int $dp Decimal places. Integer, 1 to MAX_DIGITS inclusive.
	 * @param int $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return Decimal
	 * @throws RuntimeException
	 */
	public function toDP ( 
		$dp = null, 
		$rm = null 
	) : Decimal
	{ return $this->toDecimalPlaces($dp, $rm); }

	/**
	 * Return a string representing the value of this 
	 * Decimal in exponential notation rounded to
	 * `dp` fixed decimal places using rounding mode 
	 * `rounding`.
	 * 
	 * @param int $dp Decimal places. Integer, 1 to MAX_DIGITS inclusive.
	 * @param int $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return string
	 * @throws RuntimeException
	 */
	public function toExponential ( 
		$dp = null, 
		$rm = null 
	) : string
	{
		$x = $this;
		$c = $this->_config;

		if ( \is_null($dp) )
		{ $str = DecimalHelper::finiteToString($x, true); }
		else
		{
			DecimalHelper::checkInt32($dp, 0, DecimalConfig::MAX_DIGITS);
			
			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else
			{ DecimalHelper::checkInt32($rm, 0, 8); }

			$x = DecimalHelper::finalise($x, $dp + 1, $rm);
			$str = DecimalHelper::finiteToString($x, true, $dp + 1);
		}

		return $x->isNeg() && !$x->isZero() ? '-'.$str : $str;
	}

	/**
	 * Return a string representing the value of this 
	 * Decimal in normal (fixed-point) notation to
	 * `dp` fixed decimal places and rounded using 
	 * rounding mode `rm` or `rounding` if `rm` is
	 * omitted.
	 *
	 *  (-0).toFixed(0) is '0', but (-0.1).toFixed(0) is '-0'.
	 *  (-0).toFixed(1) is '0.0', but (-0.01).toFixed(1) is '-0.0'.
	 *  (-0).toFixed(3) is '0.000'.
	 *  (-0.5).toFixed(0) is '-0'.
	 * 
	 * @param int $dp Decimal places. Integer, 1 to MAX_DIGITS inclusive.
	 * @param int $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return string
	 * @throws RuntimeException
	 */
	public function toFixed ( 
		$dp = null, 
		$rm = null 
	) : string
	{
		$x = $this;
		$c = $this->_config;

		if ( \is_null($dp) )
		{ $str = DecimalHelper::finiteToString($x); }
		else
		{
			DecimalHelper::checkInt32($dp, 0, DecimalConfig::MAX_DIGITS);
			
			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else
			{ DecimalHelper::checkInt32($rm, 0, 8); }

			$y = DecimalHelper::finalise(new Decimal($x, $c), $dp + $x->_e() + 1, $rm);
			$str = DecimalHelper::finiteToString($y, false, $dp + $y->_e() + 1);
		}
		
		// To determine whether to add the minus 
		// sign look at the value before it was rounded,
		// i.e. look at `x` rather than `y`.
		return $x->isNeg() && !$x->isZero() ? '-'.$str : $str;
	}

	/**
	 * Return an array representing the value of this 
	 * Decimal as a simple fraction with an integer
	 * numerator and an integer denominator.
	 *
	 * The denominator will be a positive non-zero value 
	 * less than or equal to the specified maximum
	 * denominator. If a maximum denominator is not specified, 
	 * the denominator will be the lowest value necessary 
	 * to represent the number exactly.
	 *
	 * @param Decimal|int|float|string $maxD Maximum denominator. Integer >= 1 and < Infinity.
	 * @since 1.0.0
	 * @return Decimal|array<Decimal>
	 * @throws RuntimeException
	 */
	public function toFraction ( $maxD = null )
	{
		$x = $this;
		$xd = $this->_d();
		$c = $this->_config;

		if ( empty($xd) )
		{ return new Decimal($x, $c); }

		$n1 = new Decimal(1, $c);
		$d1 = new Decimal(0, $c);
		$d0 = new Decimal(1, $c);
		$n0 = new Decimal(0, $c);

		$d = new Decimal($d1, $c);
		$e = DecimalHelper::getPrecision($xd) - $x->_e() - 1; $d->e($e);
		$k = $e % static::LOG_BASE;

		$dd = $d->_d();
		$dd[0] = \pow(10, $k < 0 ? static::LOG_BASE + $k : $k);
		$d->d($dd);

		if ( \is_null($maxD) )
		{ $maxD = $e > 0 ? $d : $n1; }
		else
		{
			$n = new Decimal($maxD, $c);

			if ( !$n->isInt() || $n->lt($n1) )
			{ throw new RuntimeException('Invalid maximum denominator argument.'); }

			$maxD = $n->gt($d) ? ($e > 0 ? $d : $n1) : $n;
		}

		DecimalHelper::external(false);

		$n = new Decimal(DecimalHelper::digitsToString($xd), $c);
		$pr = $c->precision;

		for (;;) 
		{
			$q = DecimalHelper::divide($n, $d, 0, 1, 1);
			$d2 = $d0->plus($q->times($d1));

			if ( $d2->cmp($maxD) == 1 )
			{ break; }

			$d0 = $d1;
			$d1 = $d2;
			$d2 = $n1;
			$n1 = $n0->plus($q->times($d2));
			$n0 = $d2;
			$d2 = $d;
			$d = $n->minus($q->times($d2));
			$n = $d2;
		}

		$d2 = DecimalHelper::divide($maxD->minus($d0), $d1, 0, 1, 1);
		$n0 = $n0->plus($d2->times($n1));
		$d0 = $d0->plus($d2->times($d1));
		$n0->s($x->_s()); $n1->s($x->_s());

		$r = DecimalHelper::divide($n1, $d1, $e, 1)
				->minus($x)
				->abs()
				->cmp(
					DecimalHelper::divide($n0, $d0, $e, 1)->minus($x)->abs()
				) < 1 ? [$n1, $d1] : [$n0, $d0];

		$c->precision = $pr;
		DecimalHelper::external(true);

		return $r;
	}

	/**   
	 * Return a string representing the value of this 
	 * Decimal in base 16, round to `sd` significant
	 * digits using rounding mode `rm`.
	 *
	 * If the optional `sd` argument is present then 
	 * return binary exponential notation.
	 *
	 * @param integer $sd Significant digits. Integer, 1 to MAX_DIGITS inclusive.
	 * @param integer $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return string
	 * @throws RuntimeException
	 */
	public function toHexadecimal ( $sd = null, $rm = null ) : string
	{ return DecimalHelper::toStringBinary($this, 16, $sd, $rm); }

	/**
	 * Alias to toHexadecimal() method.
	 *
	 * @see toHexadecimal()
	 * @param integer $sd Significant digits. Integer, 1 to MAX_DIGITS inclusive.
	 * @param integer $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function toHex ( $sd = null, $rm = null ) : string
	{ return $this->toHexadecimal($sd, $rm); }

	/**
	 * Return a string representing the value of this Decimal.
	 * Unlike `toString()` method, negative zero will include 
	 * the minus sign.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function toJSON () : string
	{
		$x = $this;
		$c = $this->_config;

		$str = DecimalHelper::finiteToString($x, $x->_e() <= $c->toExpNeg || $x->_e() >= $c->toExpPos);
		return $x->isNeg() ? '-'.$str : $str;
	}

	/**
	 * Returns a new Decimal whose value is the nearest 
	 * multiple of `y` in the direction of rounding mode `rm`, 
	 * or `Decimal.rounding` if `rm` is omitted, to the value
	 * of this Decimal.
	 *
	 * The return value will always have the same sign as this
	 * Decimal, unless either this Decimal or `y` is NaN, in
	 * which case the return value will be also be NaN.
	 *
	 * The return value is not affected by the value of `precision`.
	 *
	 * 'toNearest() rounding mode not an integer: {rm}'
	 * 'toNearest() rounding mode out of range: {rm}'
	 *
	 * @todo Experimental
	 * @param Decimal|int|float|string $y The magnitude to round to a multiple of.
	 * @param integer $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function toNearest ( 
		$y = null, 
		int $rm = null 
	) : Decimal
	{
		$x = $this;
		$c = $this->_c();

		$x = new Decimal($x, $c);

		if ( \is_null($y) )
		{
			// If x is not finite, return x.
			if ( !$x->isFinite() )
			{ return $x; }

			$y = new Decimal(1, $c);
			$rm = $c->rounding;
		}
		else
		{
			$y = new Decimal($y, $c);

			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else 
			{ DecimalHelper::checkInt32($rm, 0, 8); }

			// If x is not finite, 
			// return x if y is not NaN, else NaN.
			if ( !$x->isFinite() )
			{ return !$y->isNaN() ? $x : $y; }

			// If y is not finite, return Infinity 
			// with the sign of x if y is Infinity, else NaN.
			if ( !$y->isFinite() )
			{
				if ( !$y->isNaN() )
				{ $y->s($x->_s()); }

				return $y;
			}
		}
		
		// If y is not zero, calculate the nearest multiple of y to x.
		if ( !$y->isZero() )
		{
			DecimalHelper::external(false);
			$x = DecimalHelper::divide($x, $y, 0, $rm, 1)->times($y);
			DecimalHelper::external(true);
			DecimalHelper::finalise($x);
		}
		// If y is zero, return zero with the sign of x.
		else
		{
			$y->s($x->_s());
			$x = $y;
		}

		return $x;
	}

	/**
	 * Decimal object to integer.
	 *
	 * @todo Experimental
	 * @since 1.0.0
	 * @return int
	 */
	public function toInt () : int
	{ return \intval($this->toString()); }

	/**
	 * Decimal object to float.
	 *
	 * @todo Experimental
	 * @since 1.0.0
	 * @return float
	 */
	public function toFloat () : float
	{ return \floatval($this->toString()); }

	/**
	 * Decimal object to number.
	 *
	 * @todo Experimental
	 * @since
	 * @return float|int
	 */
	public function toNumber ()
	{ 
		if ( !$this->isInt() )
		{ return $this->toFloat(); }

		return $this->toInt();
	}

	/**
	 * Return a string representing the value of this 
	 * Decimal in base 8, round to `sd` significant
	 * digits using rounding mode `rm`.
	 *
	 * If the optional `sd` argument is present then 
	 * return binary exponential notation.
	 *
	 * [sd] {number} Significant digits. Integer, 1 to MAX_DIGITS inclusive.
	 * [rm] {number} Rounding mode. Integer, 0 to 8 inclusive.
	 *
	 * @param integer $sd
	 * @param integer $rm
	 * @since 1.0.0
	 * @return string
	 * @throws RuntimeException
	 */
	public function toOctal ( $sd = null, $rm = null ) : string
	{ return DecimalHelper::toStringBinary($this, 8, $sd, $rm); }

	/**
	 * Not implemented
	 *
	 * @see toPower()
	 * @param Decimal|float|integer|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function toPower ( $y )
	{
		$x = $this;
		$c = $this->_c();
		$y = new Decimal($y, $c);
		$yn = $y->toNumber();

		if ( $x->isFinite() || $x->isNaN() || $x->isZero()
				|| $y->isFinite() || $y->isNaN() || $y->isZero() )
		{ return new Decimal(\pow($x->toNumber(), $yn), $c); }

		$x = new Decimal($x,$c);

		if ( $x->eq(1) )
		{ return $x; }

		$pr = $c->precision;
		$rm = $c->rounding;

		if ( $y->eq(1) )
		{ return DecimalHelper::finalise($x, $pr, $rm); }

		// y exponent
		$e = (int)\floor($y->_e()/static::LOG_BASE);

		// If y is a small integer use the 
		// 'exponentiation by squaring' algorithm.
		if ( $e >= \count($y->_d()) - 1 && ($k = $yn < 0 ? -$yn : $yn) <= static::MAX_SAFE_INTEGER)
		{
			$r = DecimalHelper::intPow($c, $x, $k, $pr);
			return $y->_s() < 0 ? (new Decimal(1,$c))->div($r) : DecimalHelper::finalise($r, $pr, $rm);
		}

		$s = $x->_s();

		// if x is negative
		if ( $s < 0 )
		{
			// if y is not an integer
			if ($e < \count($y->_d()) - 1)
			{ return new Decimal(\NAN); }

			// Result is positive if x is negative and the 
			// last digit of integer y is even.
			if ( ($y->_d()[$e] & 1) == 0 )
			{ $s = 1; } 

			// if x.eq(-1)
			if ( $x->_e() == 0 && $x->_d()[0] == 1 && \count($x->_d()) == 1 )
			{
				$x->s($s);
				return $x;
			}
		}

		// Estimate result exponent.
		// x^y = 10^e,  where e = y * log10(x)
		// log10(x) = log10(x_significand) + x_exponent
		// log10(x_significand) = ln(x_significand) / ln(10)
		$k = \pow($x->toNumber(), $yn);
		$e = $k == 0 || (new Decimal($k, $c))->isFinite()
				? \floor($yn * (\log(\floatval('0.'.DecimalHelper::digitsToString($x->_d())))) / \M_LN10 + $x->_e() + 1)
				: (new Decimal($k.''))->_e();

		// Exponent estimate may be incorrect 
		// e.g. x: 0.999999999999999999, y: 2.29, e: 0, r.e: -1.

		// Overflow/underflow?
		if ( $e > $c->maxE+1 || $e < $c->minE - 1 )
		{ 
			$n = 0;

			if ( $e > 0 )
			{
				if ( $s === -1 )
				{ $n = -\INF; }
				else if ( $s === 0 )
				{ $n = \NAN; }
				else if ( $s === 1 )
				{ $n = \INF; }
			}

			return new Decimal($n); 
		}

		DecimalHelper::external(false);

		$c->rounding = 1;
		$x->s(1);
		
		// Estimate the extra guard digits needed to ensure five correct rounding digits from
		// naturalLogarithm(x). Example of failure without these extra digits (precision: 10):
		// new Decimal(2.32456).pow('2087987436534566.46411')
		// should be 1.162377823e+764914905173815, but is 1.162355823e+764914905173815
		$k = (int)\min(12, \strval($e.''));
		// r = x^y = exp(y*ln(x))
		$r = DecimalHelper::naturalExponential($y->times(DecimalHelper::naturalLogarithm($x, $pr+$k)), $pr);
		
		// r may be Infinity, e.g. (0.9999999999999999).pow(-1e+40)
		if ( $r->isFinite() )
		{
			// Truncate to the required precision plus five rounding digits.
			$r = DecimalHelper::finalise($r, $pr+5, 1);

			// If the rounding digits are [49]9999 or [50]0000
			// increase the precision by 10 and recalculate
			// the result.
			if ( DecimalHelper::checkRoundingDigits($r->_d(), $pr, $rm) )
			{
				$e = $pr+10;

				// Truncate to the increased precision 
				// plus five rounding digits.
				$r = DecimalHelper::finalise(
					DecimalHelper::naturalExponential($y->times(DecimalHelper::naturalLogarithm($x, $e+$k)), $e),
					$e+5,
					1
				);

				// Check for 14 nines from the 2nd rounding digit
				// (the first rounding digit may be 4 or 9).
				if ( \intval(DecimalHelper::slice(DecimalHelper::digitsToString($r->_d()), $pr+1, $pr+15)) + 1 == 1e14 )
				{ $r = DecimalHelper::finalise($r, $pr + 1, 0); }
			}
		}

		$r->s($s);
		DecimalHelper::external(true);
		$c->rounding = $rm;

		return DecimalHelper::finalise($r, $pr, $rm);
	}

	/**
	 * Alias to toPower() method.
	 *
	 * @see toPower()
	 * @param Decimal|float|integer|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function pow ( $y ) : Decimal
	{ return $this->toPower($y); }
	
	/**
	 * Return a new Decimal whose value is `x` raised 
	 * to the power `y`, rounded to precision significant
	 * digits using rounding mode `rounding`.
	 *
	 * @param Decimal|float|int|string $x
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function powOf ( $x, $y ) : Decimal
	{ return (new Decimal($x))->pow($y); }

	/**
	 * Return a string representing the value of this Decimal 
	 * rounded to `sd` significant digits using rounding mode 
	 * `rounding`.
	 *
	 * Return exponential notation if `sd` is less than the 
	 * number of digits necessary to represent the integer part 
	 * of the value in normal notation.
	 *
	 * [sd] {number} Significant digits. Integer, 1 to MAX_DIGITS inclusive.
	 * [rm] {number} Rounding mode. Integer, 0 to 8 inclusive.
	 *
	 * @param int $sd SignificantDigits. Integer, 1 to MAX_DIGITS inclusive.
	 * @param int $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return string
	 * @throws RuntimeException
	 */
	public function toPrecision ( 
		$sd = null, 
		$rm = null 
	) : string
	{
		$x = $this;
		$c = $this->_config;

		if ( \is_null($sd) )
		{ $str = DecimalHelper::finiteToString($x, $x->_e() <= $c->toExpNeg || $x->_e() >= $c->toExpPos); }
		else
		{
			DecimalHelper::checkInt32($sd, 1, DecimalConfig::MAX_DIGITS);
			
			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else
			{ DecimalHelper::checkInt32($rm, 0, 8); }

			$x = DecimalHelper::finalise(new Decimal($x, $c), $sd, $rm);
			$str = DecimalHelper::finiteToString($x, $sd <= $x->_e() || $x->_e() <= $c->toExpNeg, $sd);
		}

		return $x->isNeg() && !$x->isZero() ? '-'.$str : $str;
	}

	/**
	 * Return a new Decimal whose value is the value 
	 * of this Decimal rounded to a maximum of `sd`
	 * significant digits using rounding mode `rm`, 
	 * or to `precision` and `rounding` respectively if
	 * omitted.
	 * 
	 * 'toSignificantDigits() digits out of range: {sd}'
	 * 'toSignificantDigits() digits not an integer: {sd}'
	 * 'toSignificantDigits() rounding mode not an integer: {rm}'
	 * 'toSignificantDigits() rounding mode out of range: {rm}'
	 * 
	 * @param int $sd SignificantDigits. Integer, 1 to MAX_DIGITS inclusive.
	 * @param int $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return Decimal
	 * @throws RuntimeException
	 */
	public function toSignificantDigits ( 
		$sd = null, 
		$rm = null 
	) : Decimal
	{
		$x = $this;
		$c = $this->_config;

		if ( \is_null($sd) )
		{
			$sd = $c->precision;
			$rm = $c->rounding;
		}
		else
		{
			DecimalHelper::checkInt32($sd, 1, DecimalConfig::MAX_DIGITS);
			
			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else
			{ DecimalHelper::checkInt32($rm, 0, 8); }
		}

		return DecimalHelper::finalise(new Decimal($x, $c), $sd, $rm);
	}

	/**
	 * Alias to toSignificantDigits() method.
	 *
	 * @see toSignificantDigits()
	 * @param int $sd SignificantDigits. Integer, 1 to MAX_DIGITS inclusive.
	 * @param int $rm Rounding mode. Integer, 0 to 8 inclusive.
	 * @since 1.0.0
	 * @return Decimal
	 * @throws RuntimeException
	 */
	public function toSD ( 
		$sd = null, 
		$rm = null 
	) : Decimal
	{ return $this->toSignificantDigits($sd, $rm); }

	/**
	 * Return a string representing the value of this Decimal.
	 * 
	 * Return exponential notation if this Decimal has a 
	 * positive exponent equal to or greater than `toExpPos`, 
	 * or a negative exponent equal to or less than `toExpNeg`.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function toString () : string
	{
		$x = $this;
		$c = $this->_config;
		$str = DecimalHelper::finiteToString($x, $x->_e() <= $c->toExpNeg || $x->_e() >= $c->toExpPos);
		return $x->isNeg() && !$x->isZero() ? '-'.$str : $str;
	}

	/**
	 * Return a new Decimal whose value is the value of 
	 * this Decimal truncated to a whole number.
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function truncated () : Decimal
	{ return DecimalHelper::finalise(new Decimal($this, $this->_config), $this->_e()+1, 1); }

	/**
	 * Alias to truncated() method.
	 *
	 * @see truncated()
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function trunc () : Decimal
	{ return $this->truncated(); }
	
	/**
	 * Return a new Decimal whose value is `x`
	 * truncated to an integer.
	 *
	 * @param Decimal|float|int|string $x
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function truncOf ( $x ) : Decimal
	{ return (new Decimal($x))->truncated(); }

	/**
	 * Alias to toJSON() method.
	 *
	 * @see toJSON()
	 * @since 1.0.0
	 * @return bool
	 */
	public function valueOf () : string
	{ return $this->toJSON(); }

	/**
	 * Get Decimal exponent.
	 *
	 * @param integer|float $exponent
	 * @since 1.0.0
	 * @return self
	 */
	public function e ( $exponent )
	{ $this->_exponent = $exponent; return $this; }

	/**
	 * Get Decimal exponent.
	 *
	 * @param mixed $default
	 * @since 1.0.0
	 * @return integer|float Integer or NAN
	 */
	public function _e ()
	{ return $this->_exponent ?? \NAN; }

	/**
	 * Set Decimal sign.
	 *
	 * @param int|float $sign
	 * @since 1.0.0
	 * @return self
	 */
	public function s ( $sign )
	{ $this->_sign = $sign; return $this; }

	/**
	 * Get Decimal sign.
	 *
	 * @param mixed $default
	 * @since 1.0.0
	 * @return integer|float Integer or NAN
	 */
	public function _s ()
	{ return $this->_sign ?? \NAN; }

	/**
	 * If Decimal is signed.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function signed () : bool
	{ return !\is_nan($this->_sign); }

	/**
	 * Set Decimal digits.
	 *
	 * @param array|null $digits
	 * @since 1.0.0
	 * @return self
	 */
	public function d ( ?array $digits )
	{ $this->_digits = $digits; return $this; }

	/**
	 * Get Decimal digits.
	 *
	 * @since 1.0.0
	 * @return array|null
	 */
	public function _d () : ?array
	{ return $this->_digits ?? null; }

	/**
	 * Push to digits.
	 *
	 * @param integer $i
	 * @since 1.0.0
	 * @return self
	 */
	public function dpush ( $i )
	{ $this->_digits[] = $i; return $this; }

	/**
	 * If Decimal has digits.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function hasDigits () : bool
	{ return !empty($this->_digits); }

	/**
	 * Get Decimal configuration.
	 * 
	 * @since 1.0.0
	 * @return DecimalConfig
	 */
	public function _c () : DecimalConfig
	{ return $this->_config; }

	/**
	 * Get $k random values of int32.
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	private static function __randomValues ( int $k ) : array
	{
		$d = [];

		for ( $i = 0; $i < $k; $i++ )
		{ $d[] = \random_int(0, 0xffff); }

		return $d;
	}
}