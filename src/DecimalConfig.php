<?php
namespace Piggly\Decimal;

class DecimalConfig
{
	/**
	 * Rounds away from zero.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_UP = 0;

	/**
	 * Rounds towards zero.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_DOWN = 1;

	/**
	 * Rounds towards Infinity.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_CEIL = 2;

	/**
	 * Rounds towards -Infinity.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_FLOOR = 3;

	/**
	 * Rounds towards nearest neighbour. 
	 * If equidistant, rounds away from zero.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_HALF_UP = 4;

	/**
	 * Rounds towards nearest neighbour.
	 * If equidistant, rounds towards zero.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_HALF_DOWN = 5;

	/**
	 * Rounds towards nearest neighbour.
	 * If equidistant, rounds towards even neighbour.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_HALF_EVEN = 6;

	/**
	 * Rounds towards nearest neighbour.
	 * If equidistant, rounds towards Infinity.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_HALF_CEIL = 7;

	/**
	 * Rounds towards nearest neighbour.
	 * If equidistant, rounds towards -Infinity.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const ROUND_HALF_FLOOR = 8;

	/**
	 * Not a rounding mode, see modulo.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const EUCLID = 9;

	/**
	 * The maximum number of significant 
	 * digits of the result of an operation.
	 *
	 * @var integer 1 to 1e+9
	 * @since 1.0.0
	 */
	protected $precision = 20;

	/**
	 * The default rounding mode used when 
	 * rounding the result of an operation 
	 * to precision significant digits, 
	 * and when rounding the return value.
	 *
	 * @var integer 0 to 8
	 * @since 1.0.0
	 */
	protected $rounding = self::ROUND_HALF_UP;

	/**
	 * The negative exponent value when 
	 * returns exponential notation.
	 *
	 * @var integer -9e15 to 0
	 * @since 1.0.0
	 */
	protected $toExpNeg = -7;
	
	/**
	 * The positive exponent value when 
	 * returns exponential notation.
	 *
	 * @var integer 0 to 9e15
	 * @since 1.0.0
	 */
	protected $toExpPos = 20;

	/**
	 * The negative exponent limit, i.e. 
	 * the exponent value below which 
	 * underflow to zero occurs.
	 *
	 * @var integer -9e15 to 0
	 * @since 1.0.0
	 */
	protected $minE = -9e15;

	/**
	 * The positive exponent limit, i.e. 
	 * the exponent value above which 
	 * overflow to Infinity occurs.
	 *
	 * @var integer 0 to 9e15
	 * @since 1.0.0
	 */
	protected $maxE = 9e15;

	/**
	 * The value that determines whether 
	 * cryptographically-secure pseudo-random 
	 * number generation is used.
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	protected $crypto;

	/**
	 * The modulo mode used when calculating 
	 * the modulus: a mod n.
	 *
	 * @var integer 0 to 9
	 */
	protected $modulo = self::ROUND_DOWN;
}