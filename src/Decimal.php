<?php
namespace Piggly\Decimal;

use Exception;
use RuntimeException;

class Decimal
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
	 * Logarithm base.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const LOG_BASE = 7;

	/**
	 * Max safe integer.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const MAX_SAFE_INTEGER = \PHP_INT_MAX;

	/**
	 * The decimal digits.
	 * An array of integers,
	 * each between 0 - 1e7
	 * or null.
	 *
	 * @var array<int>|null
	 * @since 1.0.0
	 */
	protected $_digits;

	/**
	 * The decimal expoent.
	 * An integer between -9e15
	 * to 9e15 or NaN.
	 *
	 * @var integer
	 * @since 1.0.0
	 */
	protected $_exponent;

	/**
	 * The decimal sign.
	 * An integer limited to -1,
	 * 1 or NaN.
	 *
	 * @var integer
	 * @since 1.0.0
	 */
	protected $_sign;

	/**
	 * Decimal configuration.
	 * By default, will be global
	 * instance.
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

			if ( static::$_external )
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
			if ( $n == 0 )
			{
				$this->_sign = \strval($n) === '0' ? 1 : -1;
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

				if ( static::$_external )
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

			static::__parseDecimal($this, \strval($n));
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

		if ( \preg_match(static::IS_DECIMAL, $n) )
		{ 
			static::__parseDecimal($this, $n); 
			return;
		}

		static::__parseOther($this, $n);
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

		return static::finalise($decimal);
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
		return static::finalise(
			new Decimal($this, $this->_config), 
			$this->_exponent+1, 
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
	{ return new Decimal($x, $x->_c); }

	/**
	 * Return
	 *   1    if the value of this Decimal is greater than the value of `y`,
	 *  -1    if the value of this Decimal is less than the value of `y`,
	 *   0    if they have the same value,
	 *   NAN  if the value of either Decimal is NAN.
	 *
	 * @param Decimal|float|int|string $y
	 * @return int|float
	 */
	public function comparedTo ( $y )
	{
		$x = $this;
		$xd = $x->_digits;
		$xs = $x->_sign;

		$y = new Decimal($y, $x->_config);
		$yd = $y->_digits;
		$ys = $y->_sign;

		// Either NaN or ±Infinity?
		if ( $x->isNaN() || $y->isNaN() || !$x->isFinite() || !$y->isFinite() )
		{ 
			if ( $x->isNaN() || $y->isNaN() )
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
		if ( $x->isZero() || $y->isZero() )
		{ return $xd[0] ? $xs : ($yd[0] ? -$ys : 0); }

		// Signs differ?
		if ($xs !== $ys) 
		{ return $xs; }

		// Compare exponents
		if ( $x->_exponent !== $y->_exponent )
		{ return ($x->_exponent > $y->_exponent) ^ ($xs < 0) ? 1 : -1; }

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
			if ( $xd[$i] != $yd[$i] ) 
			{ return $xd[$i] > $yd[$i] ^ $xs < 0 ? 1 : -1; }
		}

		return $xdC === $ydC ? 0 : ( $xdC > $ydC ^ $xs < 0 ? 1 : -1 ); 
	}

	/**
	 * Alias to comparedTo() method.
	 *
	 * @see comparedTo()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return int|float
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
		$c = $this->_c;

		if ( !$x->isFinite() )
		{ return new Decimal(\NAN, $c); }

		// cos(0) = cos(-0) = 1
		if ( $x->isZero() )
		{ return new Decimal(1, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max($x->_e, $x->sd()) + static::LOG_BASE;
		$c->rounding = 1;

		$x = static::__cosine($c, static::__toLessThanHalfPi($c, $x));

		$c->precision = $pr;
		$c->rounding = $rm;

		$x = static::$_quadrant == 2 || static::$_quadrant == 3 ? $x->neg() : $x;
		return static::finalise($x, $pr, $rm, true);
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
		$m = false;
		$x = $this;
		$c = $x->_c;

		// NaN/Infinity/zero?
		if ( !$x->isFinite() || $x->isZero() )
		{ return new Decimal($x, $c); }

		static::$_external = false;

		// Estimate cbrt
		$s = $x->_s * \pow($x->_s * $x->toNumber(), 1/3);
		
		// Math.sqrt underflow/overflow?
		// Pass x to Math.pow as integer, 
		// then adjust the exponent of the result.
		if ( $s == 0 || \abs($s) == \INF )
		{
			$n = Math::digitsToStr($x->_d);
			$e = $x->_e;

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
				$n = \floatval(Utils::sliceStr(0, \strpos($n, 'e')+1)) + $e;
			}

			$r = new Decimal($n, $c);
			$r->s($x->_s);
		}
		else
		{ $r = new Decimal((string)$s); }

		$sd = ($e = $c->precision) + 3;
		$rep = null;

		// Halley's method.
		// TODO? Compare Newton's method.
		while (true)
		{
			$t = $r;
			$t3 = $t->times($t)->times($t);
			$t3plusx = $t3->plus($x);
			$r = static::__divide($t3plusx->plus($x)->times($t), $t3plusx->plus($t3), $sd+2, 1);

			// TODO? Replace with for-loop and checkRoundingDigits.
			if (
				Utils::sliceStr(Math::digitsToStr($t->_d), 0, $sd)
				=== ($n = Utils::sliceStr(Math::digitsToStr($r->_d), 0, $sd))
			)
			{
				$n = Utils::sliceStr($n, $sd-3, $sd+1);
							
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
						$t = static::finalise($t, $e+1, 0);

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
					if ( !(int)$n || (!(int)Utils::sliceStr($n, 1) && $n[0] == '5') )
					{
						$r = static::finalise($r, $e+1, 0);
						$m = !$r->times($r)->times($r)->eq($x);
					}

					break;
				}
			}
		}

		static::$_external = true;
		return static::finalise($r, $e, $c->rounding, $m);
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
	 * Return the number of decimal places 
	 * of the value of this Decimal.
	 *
	 * @since 1.0.0
	 * @return int|float
	 */
	public function decimalPlaces ()
	{
		$digits = $this->_digits;
		$number = \NAN;

		if ( !empty($digits) )
		{
			$w = count($digits) - 1;
			$number = ($w - floor($this->_exponent / static::LOG_BASE)) * static::LOG_BASE;
			$w = $digits[$w];

			if ( $w )
			{
				for (; $w % 10 == 0; $w /= 10 )
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
	 * @return int|float
	 */
	public function dp ()
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
	{ return static::__divide($this, new Decimal($y, $this->_config)); }

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
		return static::finalise(
			static::__divide( $this, new Decimal($y, $this->_config), 0, 1, 1),
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
	{ return static::finalise(new Decimal($this, $this->_config), $this->_exponent + 1, 3); }

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
		$c = $this->_c;

		if ( !$x->isFinite() )
		{ return new Decimal($x->isNaN() ? \NAN : \INF, $c); }

		// cos(0) = cos(-0) = 1
		if ( $x->isZero() )
		{ return new Decimal(1, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max($x->_e, $x->sd()) + 4;
		$c->rounding = 1;
		$len = \count($x->_d);

		// Argument reduction: cos(4x) = 1 - 8cos^2(x) + 8cos^4(x) + 1
		// i.e. cos(x) = 1 - cos^2(x/4)(8 - 8cos^2(x/4))

		// Estimate the optimum number of times to use the argument reduction.
		// TODO? Estimation reused from cosine() and may not be optimal here.
		if ( $len < 32 )
		{
			$k = \ceil($len / 3);
			$n = (string)(1/Math::tinyPow(4, $k));
		}
		else
		{
			$k = 16;
			$n = '2.3283064365386962890625e-10';
		}

		$x = static::__taylorSeries($c, 1, $x->times($n), new Decimal(1,$c), true);

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

		return static::finalise(
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
		$c = $this->_c;

		if ( !$x->isFinite() || $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max($x->_e, $x->sd()) + 4;
		$c->rounding = 1;
		$len = \count($x->_d);

		if ( $len < 3 )
		{ $x = static::__taylorSeries($c, 2, $x, $x, true); }
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

			$x = $x->times(1/Math::tinyPow(5,$k));
			$x = static::__taylorSeries($c, 2, $x, $x, true);

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

		return static::finalise(
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
		$c = $this->_c;

		if ( !$x->isFinite() )
		{ return new Decimal($x->_s, $c); }

		if ( $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+7;
		$c->rounding = 1;

		return static::__divide(
			$x->sinh(),
			$x->cosh(), 
			($c->precision = $pr), 
			($c->rounding = $rm)
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
	public static function hypotOf () : Decimal
	{
		$args = \func_get_args();

		$c = DecimalConfig::instance();
		$t = new Decimal(0, $c);

		static::$_external = false;

		for ( $i = 0; $i < \count($args); )
		{
			$n = new Decimal($args[$i++]);
			
			if ( !$n->isFinite() )
			{
				if ( !$n->isNaN() )
				{
					static::$_external = true;
					return new Decimal(\INF, $c);
				}

				$t = $n;
			}
			else if ( $t->isFinite() )
			{ $t = $t->plus($n->times($n)); }
		}

		static::$_external = true;

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
		$c = $this->_c;
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
				{ return static::__getPi($c, $pr, $rm); }
				else 
				{ $n = 0; }
			}

			return new Decimal($n, $c); 
		}

		if ( $x->isZero() )
		{ return static::__getPi($c, $pr+4, $rm)->times(0.5); }

		// TODO? Special case acos(0.5) = pi/3 and acos(-0.5) = 2*pi/3

		$c->precision = $pr+6;
		$c->rounding = 1;

		$x = $x->asin();
		$halfPi = static::__getPi($c, $pr+4, $rm)->times(0.5);

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
		$c = $this->_c;

		if ( $x->lte(1) )
		{ return new Decimal($x->eq(1) ? 0 : \NAN, $c); }

		if ( !$x->isFinite() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max((int)\abs($x->_e), $x->sd()) + 4;
		$c->rounding = 1;

		static::$_external = false;
		$x = $x->times($x)->minus(1)->sqrt()->plus($x);
		static::$_external = true;

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
		$c = $this->_c;

		if ( !$x->isFinite() || $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+2*(int)\max((int)\abs($x->_e), $x->sd()) + 6;
		$c->rounding = 1;

		static::$_external = false;
		$x = $x->times($x)->plus(1)->sqrt()->plus($x);
		static::$_external = true;

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
		$c = $this->_c;

		if ( !$x->isFinite() )
		{ return new Decimal(\NAN, $c); }

		if ( $x->_e >= 0 )
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

		if ( (int)\max($xsd, $pr) < 2 * -$x->_e - 1 )
		{ return static::finalise(new Decimal($x, $c), $pr, $rm, true); }

		$c->precision = $wpr = $xsd - $x->_e;
		$x = static::__divide($x->plus(1), (new Decimal(1, $c))->minus($x), $wpr + $pr, 1);

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
		$c = $this->_c;

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
				$halfPi = static::__getPi($c, $pr+4, $rm)->times(0.5);
				$halfPi->s($x->_s);
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
		$c = $this->_c;
		$pr = $c->precision;
		$rm = $c->rounding;

		if ( !$x->isFinite() )
		{
			if ( $x->isNaN() )
			{ return new Decimal(\NAN, $c); }

			if ( $pr + 4 <= DecimalConfig::PI_PRECISION )
			{
				$r = static::__getPi($c, $pr+4, $rm)->times(0.5);
				$r->s($x->_s);
				return $r;
			}
		}
		else if ( $x->isZero() )
		{ return new Decimal($x, $c); }
		else if ( $x->abs()->eq(1) && $pr+4 <= DecimalConfig::PI_PRECISION )
		{
			$r = static::__getPi($c, $pr+4, $rm)->times(0.25);
			$r->s($x->_s);
			return $r;
		}
		
		$c->precision = $wpr = $pr+10;
		$c->rounding = 1;

		// TODO? if (x >= 1 && pr <= PI_PRECISION) atan(x) = halfPi * x.s - atan(1 / x);
	
		// Argument reduction
		// Ensure |x| < 0.42
		// atan(x) = 2 * atan(x / (1 + sqrt(1 + x^2)))

		$k = \min(28, (($wpr/static::LOG_BASE+2) | 0));
		
		for ( $i = $k; $i; --$i )
		{ $x = $x->div($x->times($x)->plus(1)->sqrt()->plus(1)); }

		static::$_external = false;

		$j = (int)\ceil($wpr/static::LOG_BASE);
		$n = 1;
		$x2 = $x->times($x);
		$r = new Decimal($x,$c);
		$px = static::clone($x);

		// atan(x) = x - x^3/3 + x^5/5 - x^7/7 + ...
		for (; $i !== -1; )
		{
			$px = $px->times($x2);
			$t = $r->minus($px->div(($n+=2)));
			
			$px = $px->times($x2);
			$r = $t->plus($px->div(($n+=2)));

			if ( isset($r->_d[$j]) )
			{ for ( $i = $j; isset($t->_d[$i]) && $r->_d[$i] == $t->_d[$i] && $i--; ); }
		}

		if ( $k )
		{ $r = $r->times(2 << ($k -1)); }

		static::$_external = true;
		
		$c->precision = $pr;
		$c->rounding = $rm;

		return static::finalise($r, $pr, $rm, true);
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
			$r = static::__getPi($c, $wpr, 1)->times($x->isPos() ? 0.25 : 0.75);
			$r->s($y->_s);
		}
		// x is ±Infinity or y is ±0
		else if ( !$x->isFinite() || $y->isZero() )
		{
			$r = $x->_s < 0 ? static::__getPi($c, $pr, $rm) : new Decimal(0, $c);
			$r->s($y->_s);
		}
		// y is ±Infinity or x is ±0
		else if ( !$y->isFinite() || $x->isZero() )
		{
			$r = static::__getPi($c, $wpr, 1)->times(0.5);
			$r->s($y->_s);
		}
		// Both non-zero and finite
		// x is neg
		else if ( $x->isNeg() )
		{
			$c->precision = $wpr;
			$c->rounding = 1;

			$r = static::atanOf(static::__divide($y, $x, $wpr, 1));
			$x = static::__getPi($c, $wpr, 1);

			$c->precision = $pr;
			$c->rounding = $rm;

			$r = $y->isNeg() ? $r->minus($x) : $r->plus($x);
		}
		// x is pos
		else
		{ $r = static::atanOf(static::__divide($y, $x, $wpr, 1)); }

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
	{ return $this->isFinite() && \floor($this->_exponent/static::LOG_BASE) > count($this->_digits) - 2; }

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
		$inf = false;
		$x = $this;
		$c = $this->_c;
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
			$d = $base->_d;

			if ( $base->isNeg() || $base->isZero() || !$base->isFinite() || $base->eq(1) )
			{ return new Decimal(\NAN,$c); }

			$isBase10 = $base->eq(10);
		}

		$d = $x->_d;

		// Is arg negative, non-finite, 0 or 1?
		// TODO? May up this if to before "base if"
		if ( $x->isNeg() || $x->isZero() || !$x->isFinite() || $x->eq(1) )
		{
			$n = 0;

			if ( $x->isZero() )
			{ $n = -\INF; }
			else if ( $x->_s !== 1 )
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

		static::$_external = false;

		$sd = $pr+$guard;
		$num = static::__naturalLogarithm($x, $sd);
		$denominator = $isBase10 ? static::__getLn10($c, $sd + 10) : static::__naturalLogarithm($base, $sd);

		$r = static::__divide($num, $denominator, $sd, 1);

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
		if ( Math::checkRoundingDigits($r->_d, ($k = $pr), $rm) )
		{
			do
			{
				$sd += 10;
				$num = static::__naturalLogarithm($x, $sd);
				$denominator = $isBase10 
					? static::__getLn10($c, $sd + 10) 
					: static::__naturalLogarithm($base, $sd);

				$r = static::__divide($num, $denominator, $sd, 1);

				if ( !$inf )
				{
					// Check for 14 nines from the 2nd rounding
					// digit, as the first may be 4.
					if ( \intval(Utils::sliceStr(Math::digitsToStr($r->_d), $k+1, $k+15)) + 1 == 1e14 )
					{ $r = static::finalise($r, $pr+1, 0); }

					break;
				}
			}
			while (Math::checkRoundingDigits($r->_d, ($k += 10), $rm));
		}

		static::$_external = true;
		return static::finalise($r, $pr, $rm);
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
	 * @return Decimal
	 */
	public static function logOf ( $x, $base = null ) : Decimal
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
	 * @return Decimal
	 */
	public static function log2Of ( $x ) : Decimal
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
	 * @return Decimal
	 */
	public static function log10Of ( $x ) : Decimal
	{ return (new Decimal($x))->log(10); }

	/**
	 * Return a new Decimal whose value is 
	 * the maximum of the arguments.
	 * 
	 * @param array<Decimal|float|int|string> $nums
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function maxOf ( array $nums ) : Decimal
	{ return static::__maxOrMin(DecimalConfig::instance(), 'lt', $nums); }

	/**
	 * Return a new Decimal whose value is 
	 * the minimum of the arguments.
	 * 
	 * @param array<Decimal|float|int|string> $nums
	 * @since 1.0.0
	 * @return Decimal
	 */
	public static function minOf ( array $nums ) : Decimal
	{ return static::__maxOrMin(DecimalConfig::instance(), 'gt', $nums); }

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
			{ $y->s(-$y->_s); }
			// Return x if y is finite and x is ±Infinity.
			// Return x if both are ±Infinity with different signs.
			// Return NaN if both are ±Infinity with the same sign.
			else
			{ $y = new Decimal( $y->isFinite() || $x->_s !== $y->_s ? $x : \NAN, $c ); }

			return $y;
		}

		// If signs differ...
		if ( $x->_s != $y->_s ) 
		{
			$y->s(-$y->_s);
			return $x->plus($y);
		}

		$xd = $x->_digits;
		$yd =& $y->_digits; // Any changes to $yd should applies to $y
		$pr = $c->precision;
		$rm = $c->rounding;

		// If either is zero...
		if ( $x->isZero() || $y->isZero() )
		{
			// Return y negated if x is zero and y is non-zero.
			if ( !$y->isZero() )
			{ $y->s(-$y->_s); }
			// Return x if y is zero and x is non-zero.
			else if ( !$x->isZero() )
			{ $y = new Decimal($x, $c); }
			// Return zero if both are zero.
			// From IEEE 754 (2008) 6.3: 0 - 0 = -0 - -0 = -0 when rounding to -Infinity.
			else
			{ return new Decimal($rm === 3 ? '-0' : 0); }

			return static::$_external ? static::finalise($y, $pr, $rm) : $y;
		}

		// x and y are finite, non-zero numbers with the same sign.
	
		// Calculate base 1e7 exponents.
		$e = (int)\floor($y->_e/static::LOG_BASE);
		$xe = (int)\floor($x->_e/static::LOG_BASE);
		$k = $xe - $e;

		// If base 1e7 exponents differ...
		if ( $k )
		{
			$xlty = $k < 0;

			if ( $xlty )
			{
				$d =& $xd;
				$k = -$k;
				$len = \count($yd);
			}
			else
			{
				$d =& $yd;
				$e = $xe;
				$len = \count($xd);
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

				for ( $j = \count($d); $j > 1; $j-- )
				{ \array_pop($d); }
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
				if ( isset($xd[$i],$yd[$i]) && $xd[$i] !== $yd[$i] )
				{ 
					$xlty = $xd[$i] < $yd[$i]; 
					break; 
				}
			}

			$k = 0;
		}

		if ( $xlty )
		{
			$d =& $xd;
			$xd =& $yd;
			$yd =& $d;
			$y->s(-$y->_s);
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
			if ( isset($xd[--$i], $yd[$i]) && $xd[$i] < $yd[$i] )
			{
				for ( $j = $i; $j && $xd[--$j] === 0; )
				{ $xd[$j] = static::BASE - 1; }

				--$xd[$j];
				$xd[$i] += static::BASE;
			}
			
			$xd[$i] -= $yd[$i];
		}

		// Remove trailing zeros.
		for ( ; isset($xd[--$len]) && $xd[$len] === 0; )
		{ \array_pop($xd); }

		// Remove trailing zeros.
		for ( ; isset($xd[0]) && $xd[0] == 0; \array_shift($xd) )
		{ --$e; }

		// Zero?
		if ( empty($xd[0]) )
		{ return new Decimal($rm === 3 ? '-0' : 0, $c); }

		$y->d($xd);
		$y->e(Math::getBase10Exponent($xd,$e));

		return static::$_external ? static::finalise($y, $pr, $rm) : $y;
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
		{ return static::finalise(new Decimal($x, $c), $c->precision, $c->rounding); }

		// Prevent rounding of intermediate calculations.
		static::$_external = false;

		if ( $c->modulo == 9 )
		{
			// Euclidian division: q = sign(y) * floor(x / abs(y))
			// result = x - q * y    where  0 <= result < abs(y)
			$q = static::__divide($x, $y->abs(), 0, 3, 1);
			$q->s($q->_s*$y->_s);
		}
		else
		{ $q = static::__divide($x, $y, 0, $c->modulo, 1); }

		$q = $q->times($y);

		static::$_external = true;
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
	{ return static::__naturalExponential($this); }

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
	{ return static::__naturalLogarithm($this); }

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
	{ return (new Decimal($x))->ln(); }

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
		$x->_sign = $x->_sign*-1;
		return static::finalise($x);
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
		$c = $this->_c;
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
			{ $y = new Decimal($y->isFinite() || $x->_s === $y->_s ? $x : \NAN, $c); }

			return $y;
		}

		// If signs differ...
		if ( $x->_s !== $y->_s )
		{
			$y->s(-$y->_s);
			return $x->minus($y);
		}

		$xd = $x->_digits;
		$yd =& $y->_digits;
		$pr = $c->precision;
		$rm = $c->rounding;

		// If either is zero...
		if ( $x->isZero() || $y->isZero() )
		{
			// Return x if y is zero.
			// Return y if y is non-zero.
			if ( $y->isZero() )
			{ $y = new Decimal($x, $c); }

			return static::$_external ? static::finalise($y, $pr, $rm) : $y;
		}

		// x and y are finite, non-zero numbers with the same sign.
	
		// Calculate base 1e7 exponents.
		$k = (int)\floor($x->_e/static::LOG_BASE);
		$e = (int)\floor($y->_e/static::LOG_BASE);

		$i = $k-$e;

		// If base 1e7 exponents differ...
		if ( $i )
		{
			if ( $i < 0 )
			{
				$d =& $xd;
				$i = -$i;
				$len = \count($yd);
			}
			else
			{
				$d =& $yd;
				$e = $k;
				$len = \count($xd);
			}
			
			// Limit number of zeros prepended to max(ceil(pr / LOG_BASE), len) + 1.
			$k = (int)\ceil($pr/static::LOG_BASE);
			$len = $k > $len ? $k + 1 : $len + 1;

			if ( $i > $len )
			{
				$i = $len;

				for ( $j = \count($d); $j > 1; $j-- )
				{ \array_pop($d); }
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
			$d =& $yd;
			$yd =& $xd;
			$xd =& $d;
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
		$y->e(Math::getBase10Exponent($xd, $e));

		return static::$_external ? static::finalise($y, $pr, $rm) : $y;
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
	 * @return int|float
	 */
	public function precision ( $z = false )
	{
		$x = $this;

		if ( $z !== false && $z !== true && $z !== 1 && $z !== 0 )
		{ throw new RuntimeException('Argument must be a bool or an integer 0 or 1 value.'); }

		$z = (bool)$z;

		if ( $x->isFinite() )
		{
			$k = Math::getPrecision($x->_d);

			if ( $z && $x->_e + 1 > $k )
			{ $k = $x->_e + 1; }
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
	 * @return int|float
	 */
	public function sd ( $z = false )
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
	 * @return Decimal
	 */
	public static function random ( int $sd = null ) : Decimal
	{
		$i = 0;
		$c = DecimalConfig::instance();
		$r = new Decimal(1, $c);
		$rd = [];

		if ( \is_null($sd) )
		{ $sd = $c->precision; }
		else
		{ Math::isInt32($sd, 1, DecimalConfig::MAX_DIGITS); }

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
			{ $rd[$i++] = ((\mt_rand() / \mt_getrandmax()) * 1e7) | 0; }
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
		for ( ; isset($rd[$i]) && $rd[$i] === 0; $i-- )
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
		return static::finalise(
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
			{ return $x->_s; }
			else 
			{ return $x->_s < 0 ? -0.0 : 0; }
		}
		
		return $x->_s ?? \NAN;
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
		$c = $this->_c;

		if ( !$x->isFinite() )
		{ return new Decimal(\NAN, $c); }

		if ( $x->isZero() )
		{ return new Decimal($x, $c); }

		$pr = $c->precision;
		$rm = $c->rounding;
		$c->precision = $pr+(int)\max($x->_e, $x->sd()) + static::LOG_BASE;
		$c->rounding = 1;

		$x = static::__sine($c, static::__toLessThanHalfPi($c, $x));

		$c->precision = $pr;
		$c->rounding = $rm;

		return static::finalise(
			static::$_quadrant > 2 ? $x->neg() : $x, 
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
	 * @todo It need performance improvements, see at SqrtMethodDecimalTest class
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function squareRoot ()
	{
		$x = $this;
		$c = $x->_c;
		$d = $x->_d;
		$e = $x->_e;
		$s = $x->_s;
		$m = false;

		// Negative/NaN/Infinity/zero?
		if ( $x->isNeg() || $x->isNaN() || !$x->isFinite() || $x->isZero() )
		{
			$n = \INF;

			if ( $x->isNaN() || ( $x->isNeg() && !$x->isZero() ) )
			{ $n = \NAN; }
			else if ( $x->isZero() )
			{ $n = $x; }
		
			return new Decimal($n, $c);
		}

		static::$_external = false;

		// Estimate sqrt
		$s = \sqrt($x->toNumber());

		// sqrt underflow/overflow?
		// Pass x to sqrt as integer, 
		// then adjust the exponent of the result.
		if ( $s == 0 || $s == \INF )
		{
			$n = Math::digitsToStr($d);

			if ( (\strlen($n)+$e) % 2 === 0 )
			{ $n .= '0'; }

			// PHP does not support long integers
			// should it use bcsqrt when infinity
			$s = \bcsqrt($n, $c->precision+1);
			$e = (int)\floor(($e+1)/2) - \intval($e < 0 || $e % 2);

			// TODO? May never be infinity ??
			if ( $s == \INF )
			{ $n = '5e'.$e; }
			else 
			{
				$s = new Decimal($s, $c);
				$n = $s->toExponential();
				$n = (Utils::sliceStr($n, 0, \strpos($n, 'e')+1)) . $e;
			}

			$r = new Decimal($n, $c);
		}
		else
		{ 
			$_x = $x->toString();

			if ( \stripos($_x, 'e') === false && \stripos($_x, '.') === false )
			{ $s = \bcsqrt($_x, $c->precision+4); }

			$r = new Decimal($s, $c); 
		}

		$sd = ($e = $c->precision) + 3;
		$rep = null;

		// Newton-Raphson iteration.
		while ( true )
		{
			$t = $r;
			$r = $t->plus(static::__divide($x, $t, $sd+2, 1))->times(0.5);

			// TODO? Replace with for-loop and checkRoundingDigits.
			if (
				Utils::sliceStr(Math::digitsToStr($t->_d), 0, $sd)
				=== ($n = Utils::sliceStr(Math::digitsToStr($r->_d), 0, $sd))
			)
			{
				$n = Utils::sliceStr($n, $sd-3, $sd+1);
							
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
						$t = static::finalise($t, $e+1, 0);

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
					if ( !(int)$n || (!(int)Utils::sliceStr($n, 1) && $n[0] == '5') )
					{
						$r = static::finalise($r, $e+1, 0);
						$m = !$r->times($r)->eq($x);
					}

					break;
				}
			}
		}

		static::$_external = true;

		return static::finalise($r, $e, $c->rounding, $m);
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
		$c = $this->_c;

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
		$x = static::__divide($x, (new Decimal(1, $c))->minus($x->times($x))->sqrt(), $pr + 10, 0);

		$c->precision = $pr;
		$c->rounding = $rm;

		return static::finalise(
			static::$_quadrant == 2 || static::$_quadrant == 4 ? $x->neg() : $x, 
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
		$xd = $x->_digits;
		$y  = new Decimal($y, $c);
		$yd =& $y->_digits;

		// Multiply signer
		$y->s($y->_s*$x->_s);

		// If either is NaN, ±Infinity or ±0...
		if ( 
				!$x->isFinite() 
				|| $x->isNaN() 
				|| !$y->isFinite() 
				|| $y->isNaN()
				|| $x->isZero()
				|| $y->isZero() )
		{
			$n = \NAN;

			if ( $y->isNaN() 
					|| $x->isNaN() 
					|| ( $x->isZero() && !$y->isFinite() )
					|| ( $y->isZero() && !$x->isFinite() )
			)
			{ $n = \NAN; }
			else if ( !$y->isFinite() || !$x->isFinite() )
			{
				if ( $y->_s > 0 )
				{ $n = \INF; }
				else if ( $y->_s < 0 )
				{ $n = -\INF; }
			}
			else 
			{
				if ( $y->_s > 0 )
				{ $n = 0; }
				else if ( $y->_s < 0 )
				{ $n = '-0'; }
			}

			return new Decimal( $n, $c );
		}

		$e = (int)\floor($x->_e/static::LOG_BASE) + (int)\floor($y->_e/static::LOG_BASE);
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
		for ( ; isset($r[--$rl]) && !$r[$rl]; )
		{ \array_pop($r); }

		if ($carry) 
		{ $e++; }
		else
		{ \array_shift($r); }
		
		$y->d($r);
		$y->e(Math::getBase10Exponent($r, $e));

		return static::$_external ? static::finalise($y, $c->precision, $c->rounding) : $y;
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
	{ return static::__toStringBinary($this, 2, $sd, $rm); }

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
		
		Math::isInt32($dp, 0, DecimalConfig::MAX_DIGITS);
		
		if ( \is_null($rm) )
		{ $rm = $c->rounding; }
		else
		{ Math::isInt32($rm, 0, 8); }

		return static::finalise($x, $dp + $x->_e + 1, $rm);
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
		{ $str = static::__finiteToString($x, true); }
		else
		{
			Math::isInt32($dp, 0, DecimalConfig::MAX_DIGITS);
			
			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else
			{ Math::isInt32($rm, 0, 8); }

			$x = static::finalise($x, $dp + 1, $rm);
			$str = static::__finiteToString($x, true, $dp + 1);
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
		{ $str = static::__finiteToString($x); }
		else
		{
			Math::isInt32($dp, 0, DecimalConfig::MAX_DIGITS);
			
			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else
			{ Math::isInt32($rm, 0, 8); }

			$y = static::finalise(new Decimal($x, $c), $dp + $x->_e + 1, $rm);
			$str = static::__finiteToString($y, false, $dp + $y->_e + 1);
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
		$xd = $this->_d;
		$c = $this->_config;

		if ( empty($xd) )
		{ return new Decimal($x, $c); }

		$n1 = new Decimal(1, $c);
		$d1 = new Decimal(0, $c);
		$d0 = new Decimal(1, $c);
		$n0 = new Decimal(0, $c);

		$d = new Decimal($d1, $c);
		$e = Math::getPrecision($xd) - $x->_e - 1; $d->e($e);
		$k = $e % static::LOG_BASE;

		$dd = $d->_d;
		$dd[0] = \pow(10, $k < 0 ? static::LOG_BASE + $k : $k);
		$d->d($dd);

		if ( \is_null($maxD) )
		{ $maxD = $e > 0 ? $d : $n1; }
		else
		{
			try
			{ $n = new Decimal($maxD, $c); }
			catch ( Exception $e )
			{ throw new RuntimeException('Invalid maximum denominator argument.'); }

			if ( !$n->isInt() || $n->lt($n1) )
			{ throw new RuntimeException('Invalid maximum denominator argument.'); }

			$maxD = $n->gt($d) ? ($e > 0 ? $d : $n1) : $n;
		}

		static::$_external = false;

		$n = new Decimal(Math::digitsToStr($xd), $c);
		$pr = $c->precision;
		$c->precision = $e = \count($xd) * static::LOG_BASE * 2;

		while ( true )
		{
			$q = static::__divide($n, $d, 0, 1, 1);
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

		$d2 = static::__divide($maxD->minus($d0), $d1, 0, 1, 1);
		$n0 = $n0->plus($d2->times($n1));
		$d0 = $d0->plus($d2->times($d1));
		$n0->s($x->_s); $n1->s($x->_s);

		$__div = static::__divide($n1, $d1, $e, 1);

		$r = 
			static::__divide($n1, $d1, $e, 1)
				->minus($x)
				->abs()
				->cmp(
					static::__divide($n0, $d0, $e, 1)->minus($x)->abs()
				) < 1 ? [$n1, $d1] : [$n0, $d0];

		$c->precision = $pr;
		static::$_external = true;

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
	{ return static::__toStringBinary($this, 16, $sd, $rm); }

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

		$str = static::__finiteToString($x, $x->_e <= $c->toExpNeg || $x->_e >= $c->toExpPos);
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
		$c = $this->_c;

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
			{ Math::isInt32($rm, 0, 8); }

			// If x is not finite, 
			// return x if y is not NaN, else NaN.
			if ( !$x->isFinite() )
			{ return !$y->isNaN() ? $x : $y; }

			// If y is not finite, return Infinity 
			// with the sign of x if y is Infinity, else NaN.
			if ( !$y->isFinite() )
			{
				if ( !$y->isNaN() )
				{ $y->s($x->_s); }

				return $y;
			}
		}
		
		// If y is not zero, calculate the nearest multiple of y to x.
		if ( !$y->isZero() )
		{
			static::$_external = false;
			$x = static::__divide($x, $y, 0, $rm, 1)->times($y);
			static::$_external = true;
			static::finalise($x);
		}
		// If y is zero, return zero with the sign of x.
		else
		{
			$y->s($x->_s);
			$x = $y;
		}

		return $x;
	}

	/**
	 * Decimal object to integer.
	 * Must check php limits to convert to
	 * INF.
	 *
	 * @todo Experimental
	 * @since 1.0.0
	 * @return int|float INTEGER or FLOAT when INF.
	 */
	public function toInt ()
	{ 
		if ( $this->_s > 0 )
		{ return $this->greaterThan(\PHP_INT_MAX) ? \INF : \intval($this->toString()); }

		return $this->greaterThan(\PHP_INT_MIN) ? \intval($this->toString()) : \INF; 
	}

	/**
	 * Decimal object to float.
	 * Must check php limits to convert to
	 * INF.
	 *
	 * @todo Experimental
	 * @since 1.0.0
	 * @return float
	 */
	public function toFloat () : float
	{ 
		if ( $this->_s > 0 )
		{ return $this->greaterThan(\PHP_FLOAT_MAX) ? \INF : \floatval($this->toString()); }
		
		return $this->greaterThan(-\PHP_FLOAT_MAX) ? \floatval($this->toString()) : \INF; 
	}

	/**
	 * Decimal object to number.
	 *
	 * @todo Experimental
	 * @since
	 * @return float|int
	 */
	public function toNumber ()
	{ 
		if ( $this->isNaN() )
		{ return \NAN; }
		else if ( !$this->isFinite() )
		{ return $this->_sign < 0 ? -\INF : \INF; }

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
	{ return static::__toStringBinary($this, 8, $sd, $rm); }

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
		$c = $this->_c;
		$y = new Decimal($y, $c);
		$yn = $y->toNumber();

		if ( !$x->isFinite() || $x->isNaN() || $x->isZero()
				|| !$y->isFinite() || $y->isNaN() || $y->isZero() )
		{ return new Decimal(\pow($x->toNumber(), $yn), $c); }

		$x = new Decimal($x,$c);

		if ( $x->eq(1) )
		{ return $x; }

		$pr = $c->precision;
		$rm = $c->rounding;

		if ( $y->eq(1) )
		{ return static::finalise($x, $pr, $rm); }

		// y exponent
		$e = \intval(\floor($y->_e/static::LOG_BASE));

		// If y is a small integer use the 
		// 'exponentiation by squaring' algorithm.
		if ( 
			$e >= (\count($y->_d) - 1) && 
			($k = ($yn < 0 ? -$yn : $yn)) <= static::MAX_SAFE_INTEGER
		)
		{
			$r = static::__intPow($c, $x, $k, $pr);
			return $y->_s < 0 ? (new Decimal(1,$c))->div($r) : static::finalise($r, $pr, $rm);
		}

		$s = $x->_s;

		// if x is negative
		if ( $s < 0 )
		{
			// if y is not an integer
			if ($e < \count($y->_d) - 1)
			{ return new Decimal(\NAN); }

			// Result is positive if x is negative and the 
			// last digit of integer y is even.
			if ( (($y->_d[$e]??0) & 1) == 0 )
			{ $s = 1; } 

			// if x.eq(-1)
			if ( $x->_e == 0 && $x->_d[0] == 1 && \count($x->_d) == 1 )
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
		$e = 
				$k == 0 || $k == \INF
				? (int)\floor($yn * (\log(\floatval('0.'.Math::digitsToStr($x->_d))) / \M_LN10 + $x->_e + 1))
				: (new Decimal($k.''))->_e;

		// Exponent estimate may be incorrect 
		// e.g. x: 0.999999999999999999, y: 2.29, e: 0, r.e: -1.

		// Overflow/underflow?
		if ( $e > $c->maxE+1 || $e < $c->minE-1 )
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

		static::$_external = false;

		$c->rounding = 1;
		$x->s(1);
		
		// Estimate the extra guard digits needed to ensure five correct rounding digits from
		// naturalLogarithm(x). Example of failure without these extra digits (precision: 10):
		// new Decimal(2.32456).pow('2087987436534566.46411')
		// should be 1.162377823e+764914905173815, but is 1.162355823e+764914905173815
		$k = (int)\min(12, \strlen($e.''));
		// r = x^y = exp(y*ln(x))
		$r = static::__naturalExponential($y->times(static::__naturalLogarithm($x, $pr+$k)), $pr);
		
		// r may be Infinity, e.g. (0.9999999999999999).pow(-1e+40)
		if ( $r->isFinite() )
		{
			// Truncate to the required precision plus five rounding digits.
			$r = static::finalise($r, $pr+5, 1);

			// If the rounding digits are [49]9999 or [50]0000
			// increase the precision by 10 and recalculate
			// the result.
			if ( Math::checkRoundingDigits($r->_d, $pr, $rm) )
			{
				$e = $pr+10;

				// Truncate to the increased precision 
				// plus five rounding digits.
				$r = static::finalise(
					static::__naturalExponential($y->times(static::__naturalLogarithm($x, $e+$k)), $e),
					$e+5,
					1
				);

				// Check for 14 nines from the 2nd rounding digit
				// (the first rounding digit may be 4 or 9).
				if ( \intval(Utils::sliceStr(Math::digitsToStr($r->_d), $pr+1, $pr+15)) + 1 == 1e14 )
				{ $r = static::finalise($r, $pr + 1, 0); }
			}
		}

		$r->s($s);
		static::$_external = true;
		$c->rounding = $rm;

		return static::finalise($r, $pr, $rm);
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
		{ $str = static::__finiteToString($x, $x->_e <= $c->toExpNeg || $x->_e >= $c->toExpPos); }
		else
		{
			Math::isInt32($sd, 1, DecimalConfig::MAX_DIGITS);
			
			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else
			{ Math::isInt32($rm, 0, 8); }

			$x = static::finalise(new Decimal($x, $c), $sd, $rm);
			$str = static::__finiteToString($x, $sd <= $x->_e || $x->_e <= $c->toExpNeg, $sd);
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
			Math::isInt32($sd, 1, DecimalConfig::MAX_DIGITS);
			
			if ( \is_null($rm) )
			{ $rm = $c->rounding; }
			else
			{ Math::isInt32($rm, 0, 8); }
		}

		return static::finalise(new Decimal($x, $c), $sd, $rm);
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
		$str = static::__finiteToString($x, $x->_e <= $c->toExpNeg || $x->_e >= $c->toExpPos);
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
	{ return static::finalise(new Decimal($this, $this->_config), $this->_e+1, 1); }

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
	 * Set Decimal sign.
	 *
	 * @param int|float $sign
	 * @since 1.0.0
	 * @return self
	 */
	public function s ( $sign )
	{ $this->_sign = $sign; return $this; }

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
		$x = clone $x;
		$config = $x->_c;
		$xdi = 0;

		while (true)
		{
			// Don't round if sd is null or undefined
			if ( isset($sd) && !is_null($sd) )
			{
				$xd = $x->_d;

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
					$i += static::LOG_BASE;
					$j = $sd;
					$w = $xd[($xdi = 0)];

					// Get the rounding digit at index j of w.
					$rd = $w / \pow(10, $digits - $j - 1) % 10 | 0;
				}
				else
				{
					$xdi = \ceil(($i + 1) / static::LOG_BASE);
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

							$i %= static::LOG_BASE;
							$j = $i - static::LOG_BASE + 1;
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
						$i %= static::LOG_BASE;

						// Get the index of rd within w, adjusted for leading zeros.
						// The number of leading zeros of w is given by LOG_BASE - digits.
						$j = $i - static::LOG_BASE + $digits;

						// Get the rounding digit at index j of w.
						$rd = $j < 0 ? 0 : $w / \pow(10, $digits - $j - 1 ) % 10 | 0;
					}
				}

				// Are there any non-zero digits after the rounding digit?
				// EXPRESSION ERROR
				$isTruncated = $isTruncated || $sd < 0 || isset($xd[$xdi+1]) || ($j < 0 ? $w : $w % \pow(10, $digits - $j - 1 ));

				$roundUp =
					$rm < 4 ?
					($rd || $isTruncated) && ($rm == 0 || $rm == ($x->_s < 0 ? 3 : 2)) :
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
							$rm == ($x->_s < 0 ? 8 : 7)));

				if ( $sd < 1 || !isset($xd[0]) )
				{
					$xd = [];

					if ( $roundUp )
					{
						// Convert sd to decimal places.
						$sd -= $x->_e + 1;

          			// 1, 0.1, 0.01, 0.001, 0.0001 etc.
						$xd[0] = \pow(10, (static::LOG_BASE - $sd % static::LOG_BASE) % static::LOG_BASE);
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
					$k = \pow(10, static::LOG_BASE - $i);

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
								$x->e($x->_e+1);
								if ( $xd[0] == static::BASE )
								{ $xd[0] = 1; }
							}

							break;
						}
						else
						{
							$xd[$xdi] += $k;

							if ( $xd[$xdi] != static::BASE )
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
			if ( $x->_e > $config->maxE )
			{
				$x->d(null);
				$x->e(\NAN);
			}
			else if ( $x->_e < $config->minE )
			{
				// Zero
				$x->d([0]);
				$x->e(0);
			}
		}

		return $x;
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
		$len = \count($x->_d);

		// Argument reduction: cos(4x) = 8*(cos^4(x) - cos^2(x)) + 1
		// i.e. cos(x) = 8*(cos^4(x/4) - cos^2(x/4)) + 1

		// Estimate the optimum number of times to use the argument reduction.
		if ( $len < 32 )
		{
			$k = \ceil($len/3);
			$y = \strval((1 / Math::tinyPow(4, $k)));
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

	/**
	 * Perform division in the specified base.
	 *
	 * @param Decimal|float|integer|string $x
	 * @param Decimal|float|integer|string $y
	 * @param integer $pr Precision
	 * @param integer $rm Rounding mode
	 * @param integer $dp Decimal places
	 * @param integer $base
	 * @since 1.0.0
	 * @return Decimal
	 */
	protected static function __divide (
		$x,
	   $y,
		$pr = null,
		$rm = null,
		$dp = null,
		$base = null
	) : Decimal
	{
		$c = $x->_c;
		$sign = $x->_s === $y->_s ? 1 : -1;
		$xd = $x->_d;
		$yd = $y->_d;

		// If either is NaN, ±Infinity or ±0...
		if ( empty($xd) || empty($yd) || !$xd[0] || !$yd[0] )
		{
			// NaN or ±0
			if ( !$x->signed() || !$y->signed() || ($xd ? $yd && $xd[0] == $yd[0] : !$yd) )
			{ return new Decimal(\NAN, $c); }

			// ±Infinity
			if ( $xd && $xd[0] == 0 || empty($yd) )
			{ 
				$num = $sign > 0 ? '0' : '-0';
				return new Decimal($num, $c); 
			}

			return new Decimal($sign > 0 ? \INF : -\INF, $c);
		}

		if ( $base )
		{
			$logBase = 1;
			$e = $x->_e - $y->_e;
		}
		else
		{
			$base = Decimal::BASE;
			$logBase = Decimal::LOG_BASE;
			$e = \intval(\floor($x->_e / $logBase) - \floor($y->_e / $logBase));
		}

		$yl = \count($yd);
      $xl = \count($xd);
      $q = new Decimal($sign, $c);
      $qd = [];

		// Result exponent may be one less than e.
      // The digit array of a Decimal from 
		// toStringBinary may have trailing zeros.
		for ( $i = 0; isset($yd[$i]) && $yd[$i] == ($xd[$i] ?? 0); $i++ );

      if (isset($yd[$i]) && $yd[$i] > ($xd[$i] ?? 0)) 
		{ $e--; }

      if ( \is_null($pr) ) 
		{
			$sd = $pr = $c->precision;
			$rm = $c->rounding;
      } 
		else if ($dp) 
		{ $sd = $pr + ($x->_e - $y->_e) + 1; } 
		else 
		{ $sd = $pr; }

		$more = false;

		if ( $sd < 0 )
		{
			$qd[] = 1;
			$more = true;
		}
		else
		{
			// Convert precision in number of base 
			// 10 digits to base 1e7 digits
			$sd = $sd / $logBase + 2 | 0;
			$i = 0;

			// divisor < 1e7
			if ( $yl == 1 )
			{
				$k = 0;
				$yd = $yd[0];
				$sd++;

				// k is the carry
				for (; ( $i < $xl || $k ) && $sd--; $i++ )
				{
					$t = $k * $base + \intval($xd[$i] ?? 0);
					$qd[$i] = $t / $yd | 0;
					$k = $t % $yd | 0;
				}

				$more = $k || $i < $xl;
			}
			// divisor >= 1e7
			else
			{
				// Normalise xd and yd so highest 
				// order digit of yd is >= base/2
				$k = $base / ($yd[0] + 1) | 0;

				if ( $k > 1 ) 
				{
					$yd = Math::multiplyInt($yd, $k, $base);
					$xd = Math::multiplyInt($xd, $k, $base);
					$yl = \count($yd);
					$xl = \count($xd);
				}

				$xi = $yl;

				$rem = Utils::sliceArray($xd, 0, $yl);
				$reml = \count($rem);

				// Add zeros to make remainder as long as divisor.
				for (; $reml < $yl;) 
				{ $rem[$reml++] = 0; }

				$yz = $yd;
				\array_unshift($yz, 0);
				$yd0 = $yd[0];

				if ( $yd[1] >= $base / 2 )
				{ ++$yd0; }

				do
				{
					$k = 0;
					
					// Compare divisor and remainder
					$cmp = Math::compare($yd, $rem, $yl, $reml);

					// If divisor < remainder
					if ( $cmp < 0 )
					{
						// Calculate trial digit, k.
						$rem0 = $rem[0];

						if ( $yl != $reml ) 
						{ $rem0 = $rem0 * $base + ($rem[1] ?? 0); }

						// k will be how many times the divisor goes into the current remainder.
						$k = $rem0 / $yd0 | 0;

						//  Algorithm:
						//  1. product = divisor * trial digit (k)
						//  2. if product > remainder: product -= divisor, k--
						//     3. remainder -= product
						//  4. if product was < remainder at 2:
						//     5. compare new remainder and divisor
						//     6. If remainder > divisor: remainder -= divisor, k++

						if ($k > 1) 
						{
							if ( $k >= $base ) 
							{ $k = $base - 1; }

							// product = divisor * trial digit.
							$prod = Math::multiplyInt($yd, $k, $base);
							$prodl = \count($prod);
							$reml = \count($rem);

							// Compare product and remainder.
							$cmp = Math::compare($prod, $rem, $prodl, $reml);

							// product > remainder.
							if ($cmp == 1) 
							{
								$k--;

								// Subtract divisor from product.
								$prod = Math::subtract($prod, $yl < $prodl ? $yz : $yd, $prodl, $base);
							}
						} 
						else 
						{
							// cmp is -1.
							// If k is 0, there is no need to compare yd and rem again below, so change cmp to 1
							// to avoid it. If k is 1 there is a need to compare yd and rem again below.
							if ( $k == 0 )
							{ $cmp = $k = 1; }

							$prod = $yd;
						}

						$prodl = \count($prod);

						if ($prodl < $reml)
						{ \array_unshift($prod, 0); }

						// Subtract product from remainder.
						$rem = Math::subtract($rem, $prod, $reml, $base);

						// If product was < previous remainder.
						if ( $cmp == -1 ) 
						{
							$reml = \count($rem);

							// Compare divisor and new remainder.
							$cmp = Math::compare($yd, $rem, $yl, $reml);

							// If divisor < new remainder, subtract divisor from remainder.
							if ( $cmp < 1 ) 
							{
								$k++;

								// Subtract divisor from remainder.
								$rem = Math::subtract($rem, $yl < $reml ? $yz : $yd, $reml, $base);
							}
						}

						$reml = \count($rem);
					} 
					else if ($cmp === 0) 
					{
						$k++;
						$rem = [0];
					}
					
					// if cmp === 1, k will be 0
					// Add the next digit, k, to the result array.
					$qd[$i++] = $k;

					// Update the remainder.
					if ($cmp && $rem[0]) 
					{ $rem[$reml++] = $xd[$xi] ?? 0; } 
					else 
					{
						$rem = isset($xd[$xi]) ? [$xd[$xi]] : [];
						$reml = 1;
					}

				} 
				while (($xi++ < $xl || isset($rem[0])) && $sd--);

				$more = isset($rem[0]);
			}

			// Leading zero?
			if ( !$qd[0] )
			{ \array_shift($qd); }
		}

		// logBase is 1 when divide is being used for base conversion.
		if ( $logBase == 1 )
		{
			$q->d($qd);
			$q->e($e);
			static::$_inexact = $more;
		}
		else
		{
			// To calculate q.e, first get the number of digits of qd[0].
			for ($i = 1, $k = $qd[0]; $k >= 10; $k /= 10) 
			{ $i++; }

			$q->d($qd);
			$q->e($i + $e * $logBase - 1);
			$qy = static::finalise($q, $dp ? $pr + $q->_e + 1 : $pr, $rm, $more);

			$q->e($qy->_e);
			$q->d($qy->_d);
			$q->s($qy->_s);
		}

		return $q;
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

		$e = $x->_e;
		$str = Math::digitsToStr($x->_d);
		$len = \strlen($str);

		if ( $isExp )
		{
			if ( $sd && (($k = $sd - $len) > 0) )
			{ $str = $str[0].'.'.Utils::sliceStr($str,1).Math::getZeroString($k); }
			else if ( $len > 1 )
			{ $str = $str[0].'.'.Utils::sliceStr($str,1); }

			$str = $str . ($x->_e < 0 ? 'e' : 'e+') . \strval($x->_e);
		}
		else if ( $e < 0 )
		{ 
			$str = '0.'.Math::getZeroString(-$e-1).$str;

			if ( $sd && (($k = $sd - $len) > 0) )
			{ $str .= Math::getZeroString($k); }
		}
		else if ( $e >= $len )
		{
			$str .= Math::getZeroString($e+1-$len);
      	if ( $sd && (($k = $sd - $e - 1) > 0)) 
			{ $str = $str.'.'.Math::getZeroString($k); }
		}
		else
		{
			if ( ($k = $e + 1) < $len ) 
			{ $str = Utils::sliceStr($str, 0, $k).'.'.Utils::sliceStr($str, $k); }

			if ( $sd && (($k = $sd - $len) > 0) ) 
			{
				if ($e + 1 === $len) 
				{ $str .= '.'; }

				$str .= Math::getZeroString($k);
			}
		}

		return $str;
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
		if ( $sd > DecimalConfig::LN10_PRECISION )
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
		if ( $sd > DecimalConfig::PI_PRECISION )
		{ throw new RuntimeException('Precision of PI exceeded.'); }

		return static::finalise(new Decimal(DecimalConfig::PI, $config), $sd, $rm, true);
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
		$k = \intval(\ceil(($power/static::LOG_BASE)+4));

		static::$_external = false;

		while ( true )
		{
			if ( $number % 2 === 1 )
			{ 
				$r = $r->times($base); 
				
				$r->d(Utils::truncate($r->_d, $k));

				if ( \count($r->_d) === $k )
				{ $isTruncated = true; }
			}

			$number = (int)\floor($number/2);

			if ( $number === 0 )
			{
				$number = \count($r->_d) - 1;
				$rd = $r->_d;
				
				if ( $isTruncated && $rd[$number] === 0 )
				{ $rd[$number] = $rd[$number]++; }

				$r->d($rd);
				break;
			}

			$base = $base->times($base);
			$base->d(Utils::truncate($base->_d, $k));
		}

		static::$_external = true;
		return $r;
	}

	/**
	 * Is Decimal object odd.
	 *
	 * @param Decimal $x
	 * @since 1.0.0
	 * @return boolean
	 */
	protected static function __isOdd ( Decimal $x ) : bool
	{ return Math::isOdd($x->_digits[count($x->_digits)-1]); }

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
		$c = $x->_c;
		$rm = $c->rounding;
		$pr = $c->precision;

		// 0/NaN/Infinity?
		if ( !$x->isFinite() || $x->isNaN() || $x->isZero() || $x->_e > 17 )
		{
			$n = 0;

			if ( $x->isFinite() )
			{
				if ( !$x->_d[0] )
				{ $n = 1; }
				else if ( $x->_s < 0 )
				{ $n = 0; }
				else 
				{ $n = \INF; }
			}
			else if ( !$x->isNaN() )
			{
				if ( $x->_s < 0 )
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
		while ( $x->_e > -2 )
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

		while ( true )
		{
			$pow = static::finalise($pow->times($x), $wpr, 1);
			$denominator = $denominator->times(++$i);
			$t = $sum->plus(static::__divide($pow, $denominator, $wpr, 1));

			if ( 
				Utils::sliceStr(Math::digitsToStr($t->_d), 0, $wpr) 
				=== Utils::sliceStr(Math::digitsToStr($sum->_d), 0, $wpr) 
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
					if ( $rep < 3 && Math::checkRoundingDigits($sum->_d, $wpr - $guard, $rm, $rep) )
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
		$xd = $x->_d;
		$c = $x->_c;
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
			else if ( $x->_s !== 1 )
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
		$ds = Math::digitsToStr($xd);
		$ds0 = (int)$ds[0];
		
		if ( \abs(($e = $x->_e)) < 1.5e15 )
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
				$ds = Math::digitsToStr($x->_d);
				$ds0 = (int)$ds[0];
				$n++;
			}

			$e = $x->_e;

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
		$sum = $numerator = $x = static::__divide($x->minus(1), $x->plus(1), $wpr, 1);
		$x2 = static::finalise($x->times($x), $wpr, 1);
		$denominator = 3;

		while ( true )
		{
			$numerator = static::finalise($numerator->times($x2), $wpr, 1);
			$t = $sum->plus(static::__divide($numerator, new Decimal($denominator,$c), $wpr, 1));

			if ( 
				Utils::sliceStr(Math::digitsToStr($t->_d), 0, $wpr) 
				===  Utils::sliceStr(Math::digitsToStr($sum->_d), 0, $wpr) 
			)
			{
				$sum = $sum->times(2);

				// Reverse the argument reduction. Check that e is 
				// not 0 because, besides preventing an unnecessary 
				// calculation, -0 + 0 = +0 and to ensure correct 
				// rounding -0 needs to stay -0.
				if ( $e !== 0 )
				{ $sum = $sum->plus(static::__getLn10($c, $wpr+2, $pr)->times($e.'')); }

				$sum = static::__divide($sum, new Decimal($n, $c), $wpr, 1 );

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
					if ( Math::checkRoundingDigits($sum->_d, $wpr - $guard, $rm, $rep??null) )
					{
						$c->precision = $wpr += $guard;
						$t = $numerator = $x = static::__divide($x1->minus(1), $x1->plus(1), $wpr, 1);
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

			$i = ($e + 1) % static::LOG_BASE;

			if ( $e < 0 )
			{ $i += static::LOG_BASE; }

			if ( $i < $len )
			{
				if ( $i )
				{ $x->dpush(+\intval(Utils::sliceStr($str, 0, $i))); }

				for ( $len -= static::LOG_BASE; $i < $len; )
				{ $x->dpush(+\intval(Utils::sliceStr($str, $i, $i+=static::LOG_BASE))); }

				$str = Utils::sliceStr($str, $i);
				$i = static::LOG_BASE - \strlen($str);
			}
			else
			{ $i -= $len; }

			for (; $i--;)
			{ $str .= '0'; }

			$x->dpush(+\intval($str));

			if ( static::$_external )
			{
				if ( $x->_e > $x->_c->maxE )
				{
					$x->d(null);
					$x->e(\NAN);
				}
				else if ( $x->_e < $x->_c->minE )
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

			$divisor = static::__intPow($x->_c, new Decimal($base, $x->_c), $i, $i * 2);
		}

		$xd = Math::convertBase($str, $base, static::BASE);
		$xe = \count($xd) - 1;

		// Remove trailing zeros
		for ( $i = $xe; $i >= 0 && $xd[$i] === 0; --$i )
		{ \array_pop($xd); }

		if ( $i < 0 )
		{ 
			$y = new Decimal($x->_s * 0, $x->_c); 

			$x->e($y->_e);
			$x->s($y->_s);
			$x->d($y->_d);

			return $x;
		}

		$x->e(Math::getBase10Exponent($xd, $xe));
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
			$y = static::__divide($x, $divisor, $len * 4);

			$x->e($y->_e);
			$x->s($y->_s);
			$x->d($y->_d);
		}

		if ( $p )
		{ 
			$y = $x->times( \abs($p) < 54 ? \pow(2, $p) : (new Decimal(2))->pow(2) ); 

			$x->e($y->_e);
			$x->s($y->_s);
			$x->d($y->_d);
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
		$len = \count($x->_d);

		if ( $len < 3 )
		{ return static::__taylorSeries($c, 2, $x, $x); }

		// Argument reduction: sin(5x) = 16*sin^5(x) - 20*sin^3(x) + 5*sin(x)
		// i.e. sin(x) = 16*sin^5(x/5) - 20*sin^3(x/5) + 5*sin(x/5)
		// and  sin(x) = sin(x/5)(5 + sin^2(x/5)(16sin^2(x/5) - 20))

		// Estimate the optimum number of times to use the argument reduction.
		$k = 1.4 * \sqrt($len);
		$k = $k > 16 ? 16 : $k | 0;

		$x = $x->times(1/Math::tinyPow(5, $k));
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
		$k = (int)\ceil($pr/static::LOG_BASE);

		static::$_external = false;

		$x2 = $x->times($x);
		$u = new Decimal($y, $c);

		while ( true )
		{
			$t = static::__divide($u->times($x2), new Decimal($n++ * $n++, $c), $pr, 1);
			$u = $isHyperbolic ? $y->plus($t) : $y->minus($t);
			$y = static::__divide($t->times($x2), new Decimal($n++ * $n++, $c), $pr, 1);
			$t = $u->plus($y);

			if ( isset($t->_d[$k]) )
			{
				for ( 
					$j = $k; 
					isset($t->_d[$j], $u->_d[$j]) 
						&& $t->_d[$j] === $u->_d[$j] 
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
		$t->d(Utils::sliceArray($t->_d, 0, $k+1));

		return $t;
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
		$c = $x->_c;
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
				$y->d(Math::convertBase(static::__finiteToString($y), 10, $base));
				$y->e(\count($y->_d));
			}

			$xd = Math::convertBase($str, 10, $base);
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
					$x = static::__divide($x, $y, $sd, $rm, 0, $base);
					$xd = $x->_d;
					$e = $x->_e;
					$roundUp = static::$_inexact;
				}

				$i = $xd[$sd]??null;
				$k = $base/2;
				$roundUp = $roundUp || isset($xd[$sd+1]);

				$roundUp =
					$rm < 4 ?
					(!\is_null($i) || $roundUp) && ( $rm === 0 || $rm === ($x->_s < 0 ? 3 : 2) ) :
						$i > $k || 
						( $i === $k && 
							($rm === 4 || 
								$roundUp ||
									($rm === 6 && $xd[$sd-1] & 1) ||
										$rm === ($x->_s < 0 ? 8 : 7)));

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

							$xd = Math::convertBase($str, $base, $baseOut);

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

		return $x->_s < 0 ? '-'.$str : $str;
	}

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