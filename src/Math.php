<?php
namespace Piggly\Decimal;

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
	public static function asInteger ( $num ) : int
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
}