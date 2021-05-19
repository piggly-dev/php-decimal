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
	 * Return a new Decimal whose value is the value 
	 * of this Decimal rounded to a whole number in 
	 * the direction of positive Infinity.
	 *
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function ceil () : Decimal
	{ return DecimalHelper::finalise(new Decimal($this), $this->_exponent + 1, DecimalConfig::ROUND_CEIL); }

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

	// Not implemented
	public function cbrt ()
	{}

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
	{ return DecimalHelper::divide($this, new Decimal($y)); }

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
	{ return DecimalHelper::finalise(new Decimal($this), $this->_exponent + 1, 3); }

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
	
	// Not implemented
	public function cos ()
	{}

	// Not implemented
	public function sin ()
	{}

	// Not implemented
	public function tan ()
	{}

	// Not implemented
	public function cosh ()
	{}

	// Not implemented
	public function sinh ()
	{}

	// Not implemented
	public function tanh ()
	{}

	// Not implemented
	public function acos ()
	{}

	// Not implemented
	public function asin ()
	{}

	// Not implemented
	public function atan ()
	{}

	// Not implemented
	public function acosh ()
	{}

	// Not implemented
	public function asinh ()
	{}

	// Not implemented
	public function atanh ()
	{}

	/**
	 * Return true if the value of this Decimal 
	 * is a finite number, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function isFinity () : bool
	{ return !empty($this->_digits); }

	/**
	 * Return true if the value of this Decimal 
	 * is an integer, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function isInt () : bool
	{ return $this->isFinity() && \floor($this->_exponent/self::LOG_BASE) > count($this->_digits) - 2; }

	/**
	 * Return true if the value of this Decimal 
	 * is NaN, otherwise return false.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function isNaN () : bool
	{ return $this->_sign === \NAN || is_null($this->_sign); }

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
	{ return $this->isFinity() && $this->_digits[0] === 0; }

	// Not implemented
	public function log ( $number = null )
	{}

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
	{}

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
	 * Not implemented.
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
	{}

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
	 * Alias to naturalLogarithm() method.
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
	 * Not implemented
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function plus ( $y )
	{}

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
	 * Not implemented
	 *
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function precision ( $y )
	{}

	/**
	 * Alias to precision() method.
	 *
	 * @see precision()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function sd ( $y ) : Decimal
	{ return $this->precision($y); }

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
	 * Not implemented
	 * 
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function squareRoot ( $y )
	{}

	/**
	 * Alias to squareRoot() method.
	 *
	 * @see squareRoot()
	 * @param Decimal|float|int|string $y
	 * @since 1.0.0
	 * @return Decimal
	 */
	public function sqrt ( $y ) : Decimal
	{ return $this->squareRoot($y); }

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
		if ( empty($xd) || empty($yd) )
		{
			return new Decimal(
				!$y->signed() || (!empty($xd) && empty($yd)) || (!empty($yd) && empty($xd))
				? \NAN
				: (empty($xd) || empty($yd) ? 'Infinity' : 'Infinity' ), // TODO signal to infinity
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

		$n1 = new Decimal(1);
		$d1 = new Decimal(0);
		$d0 = new Decimal(1);
		$n0 = new Decimal(0);

		$d = new Decimal($d1);
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

		$n = new Decimal(DecimalHelper::digitsToString($xd));
		$pr = $c->precision;

		for (;;) 
		{
			$q = DecimalHelper::divide($n, $d, 0, 1, 1);
			$d2 = $d0->plus($q->times($d1));

			if ( $d2->cmp($maxD) == 1 )
			{ break; }

			$d0 = clone $d1;
			$d1 = clone $d2;
			$d2 = clone $n1;
			$n1 = $n0->plus($q->times($d2));
			$n0 = clone $d2;
			$d2 = clone $d;
			$d = $n->minus($q->times($d2));
			$n = clone $d2;
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

	// Not implemented
	public function toNearest ( $y, $rm = null )
	{}

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
	{}

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
	{ return $this->_sign !== \NAN; }

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
}