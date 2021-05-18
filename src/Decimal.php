<?php
namespace Piggly\Decimal;

class Decimal
{
	const _IS_BINARY = '/^0b([01]+(\.[01]*)?|\.[01]+)(p[+-]?\d+)?$/i';
	const _IS_HEX = '/^0x([0-9a-f]+(\.[0-9a-f]*)?|\.[0-9a-f]+)(p[+-]?\d+)?$/i';
	const _IS_OCTAL = '/^0o([0-7]+(\.[0-7]*)?|\.[0-7]+)(p[+-]?\d+)?$/i';
	const _IS_DECIMAL = '/^(\d+(\.\d*)?|\.\d+)(e[+-]?\d+)?$/i';

	const BASE = 1e7;
	const LOG_BASE = 7;
	const MAX_SAFE_INTEGER = 9007199254740991;

	const LN10_PRECISION = strlen(DecimalConfig::LN10) - 1;
	const PI_PRECISION = strlen(DecimalConfig::PI) - 1;

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
	 * @param Decimal|float|int|string $number
	 * @param DecimalConfig|null $config
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct ( $number, DecimalConfig $config = null )
	{
		$this->_config = !is_null($config) ? $config : new DecimalConfig();
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
		$decimal = new Decimal($this);
		
		if ( $decimal->_sign < 0 )
		{ $decimal->_sign = 1; }

		return $this->finalise($decimal);
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
	{ return $this->finalise(new Decimal($this), $this->_exponent + 1, DecimalConfig::ROUND_CEIL); }

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

		$y = new Decimal($y);
		$yd = $y->_digits;
		$ys = $y->_sign;

		// Either NaN or ±Infinity?
		if ( is_null($xd) || is_null($yd) )
		{ return is_null($xs) || is_null($ys) ? \NAN : ($xs !== $ys ? 0 : ($xs === $ys ? 0 : (!$xd ^ $xs < 0 ? 1 : -1))); }

		// Either zero?
		if ( !isset($xd[0]) || !isset($yd[0]) )
		{ return isset($xd[0]) ? $xs : (isset($yd[0]) ? -$ys : 0); }

		// Signs differ?
		if ($xs !== $ys) 
		{ return $xs; }

		// Compare exponents
		if ( $x->_exponent !== $y->_exponent )
		{ return $x->_exponent > $y->_exponent ^ $xs < 0 ? 1 : -1; }

		$xdC = count($xd);
		$ydC = count($yd);

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

	public function cbrt () : float
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
	{ return $this->divide($this, new Decimal($y)); }

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
		return $this->finalise(
			$this->divide( $this, new Decimal($y, $this->_config), 0, 1, 1),
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
	{ return $this->finalise(new Decimal($this), $this->_exponent + 1, 3); }

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
	
	public function cos () : Decimal
	{}

	public function sin () : Decimal
	{}

	public function tan () : Decimal
	{}

	public function cosh () : Decimal
	{}

	public function sinh () : Decimal
	{}

	public function tanh () : Decimal
	{}

	public function acos () : Decimal
	{}

	public function asin () : Decimal
	{}

	public function atan () : Decimal
	{}

	public function acosh () : Decimal
	{}

	public function asinh () : Decimal
	{}

	public function atanh () : Decimal
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

	public function log ( $number = null ) : Decimal
	{}

	public function sub ( $number ) : Decimal
	{}

	public function mod ( $number ) : Decimal
	{}

	public function exp () : Decimal
	{}

	public function ln () : Decimal
	{}

	public function neg () : Decimal
	{}

	public function add ( $number ) : Decimal
	{}

	public function sd ( $number ) : Decimal
	{}

	public function round () : Decimal
	{}

	public function sqrt () : Decimal
	{}

	public function mul () : Decimal
	{}

	public function toBinary ( $significantDigits = null, $rounding = null ) : string
	{}

	public function toDP ( $significantDigits = null, $rounding = null ) : Decimal
	{}

	public function toExponential ( $decimalPlaces = null, $rounding = null ) : string
	{}

	public function toFixed ( $decimalPlaces = null, $rounding = null ) : string
	{}

	public function toFraction ( $max_denominator = null ) : array
	{}

	public function toHex ( $significantDigits = null, $rounding = null ) : string
	{}

	public function toJSON () : string
	{}

	public function toNearest ( $number, $rounding = null ) : Decimal
	{}

	public function toInt () : int
	{}

	public function toFloat () : float
	{}

	public function toOctal ( $significantDigits = null, $rounding = null ) : string
	{}

	public function pow ( $number ) : Decimal
	{}

	public function toSD ( $significantDigits = null, $rounding = null ) : Decimal
	{}

	public function toString () : string
	{}

	public function trunc () : Decimal
	{}

	public function valueOf () : string
	{}

	protected function finalise ( 
		$number, 
		$significantDigits = null, 
		$rouding = null, 
		$isTruncated = false
	) : Decimal
	{}
}