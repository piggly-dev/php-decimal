<?php
namespace Piggly\Decimal;

class Decimal
{
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
	 */
	protected $_exponent;

	/**
	 * An integer limited to -1,
	 * 1 or NaN.
	 *
	 * @var integer
	 */
	protected $_sign;

	/**
	 * Decimal constructor.
	 *
	 * @param Decimal|float|int|string $number
	 */
	public function __construct ( $number )
	{}

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

		return $this->finalize($decimal);
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
	{ return $this->finalize(new Decimal($this), $this->_exponent + 1, DecimalConfig::ROUND_CEIL); }

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
	 * @since 1.0.0
	 * @return float
	 */
	public function cmp ( $y )
	{ return $this->comparedTo($y); }

	public function cbrt () : float
	{}

	public function dp () : Decimal
	{}

	public function div ( $number ) : Decimal
	{}

	public function divToInt ( $number ) : Decimal
	{}

	public function eq ( $number ) : Decimal
	{}

	public function floor () : Decimal
	{}

	public function gt ( $number ) : Decimal
	{}

	public function gte ( $number ) : Decimal
	{}
	
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

	public function isFinity () : bool
	{}

	public function isInt () : bool
	{}

	public function isNaN () : bool
	{}

	public function isNeg () : bool
	{}

	public function isPos () : bool
	{}

	public function isZero () : bool
	{}

	public function lt ( $number ) : Decimal
	{}

	public function lte ( $number ) : Decimal
	{}

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

	protected function finalize ( 
		$number, 
		$significantDigits = null, 
		$rouding = null, 
		$isTruncated = false
	) : Decimal
	{}
}