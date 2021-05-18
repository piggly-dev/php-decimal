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
	protected $_digit;

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

	public function abs () : Decimal
	{}

	public function ceil () : Decimal
	{}

	public function cmp ( $number ) : Decimal
	{}

	public function cbrt () : Decimal
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
}