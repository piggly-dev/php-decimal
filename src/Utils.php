<?php
namespace Piggly\Decimal;

use RuntimeException;

/**
 * All functions required to manages
 * the expected behavior from input
 * to output.
 *
 * @since 1.0.0
 * @package Piggly\Decimal
 * @subpackage Piggly\Decimal
 * @author Caique Araujo <caique@piggly.com.br>
 */
class Utils
{
	/**
	 * Does a simple copy of $arr.
	 *
	 * @param array $arr
	 * @since 1.0.0
	 * @return array
	 */
	public static function copyArray ( array $arr ) : array
	{
		$_arr = [];

		foreach ( $arr as $key => $value )
		{ $_arr[$key] = $value; }

		return $_arr;
	}

	/**
	 * Slice string from $si at $ei.
	 * 
	 * This functions handle string a little bit
	 * different of native substr() function.
	 * 
	 * While substr() requires $start and $length,
	 * this requires $start and $end. Any of both
	 * can be negative and whatever.
	 * 
	 * It produces an output different from substr()
	 * working similar to javascript native function
	 * String.prototype.slice().
	 *
	 * @see SliceStrMethodUtilsTest
	 * @see https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Global_Objects/String/slice
	 * @param string $str
	 * @param integer $si Start index.
	 * @param integer|null $ei End index.
	 * @since 1.0.0
	 * @return string
	 */
	public static function sliceStr ( 
		string $str, 
		int $si, 
		int $ei = null 
	) : string
	{
		$len = \strlen($str);
		$si = $si < 0 ? $len+$si : ($si > $len ? $len : $si);
		$ei = \is_null($ei) ? null : ($ei < 0 ? $len+$ei : ($ei > $len ? $len : $ei));
		$si = $si <= 0 ? 0 : $si;

		if ( $ei === $si )
		{ return ''; }

		if ( \is_null($ei) )
		{ return \substr($str, $si); }

		if ( $si === 0 && $ei === $len )
		{ return $str; }

		$ei = $ei - $si;

		if ( $ei < 0 )
		{ return ''; }

		return \substr($str, $si, $ei);
	}

	/**
	 * Slice string from $si at $ei.
	 * 
	 * This functions handle string a little bit
	 * different of native array_slice() function.
	 * 
	 * While substr() requires $start and $length,
	 * this requires $start and $end. Any of both
	 * can be negative and whatever.
	 * 
	 * It produces an output different from array_slice()
	 * working similar to javascript native function
	 * Array.prototype.slice().
	 *
	 * @see SliceArrayMethodUtilsTest
	 * @see https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Global_Objects/Array/slice
	 * @param array $arr
	 * @param integer $si
	 * @param integer $ei
	 * @since 1.0.0
	 * @return array
	 */
	public static function sliceArray ( 
		array $arr, 
		int $si = null, 
		int $ei = null
	) : array
	{
		$len = \count($arr);
		$si = \is_null($si) ? 0 : ($si >= $len ? $len : ($si <= -$len ? -$len : $si));
		$ei = \is_null($ei) || $ei >= $len ? $len : ($ei <= -$len ? -$len : $ei);

		if ( $si === $len || $si === $ei )
		{ return []; }

		if ( $si >= 0 )
		{
			if ( $ei >= 0 && $ei <= $si )
			{ return []; }
			// $ei > $si
			else if ( $ei > $si )
			{
				$_arr = [];

				for ( $i = $si; $i < $ei; $i++ )
				{ $_arr[] = $arr[$i]; }

				return $_arr;
			}
			// $ei < 0 && $ei < si
			else
			{
				$ei += $len;

				if ( $ei <= $si )
				{ return []; }

				// $ei > $si
				$_arr = [];

				for ( $i = $si; $i < $ei; $i++ )
				{ $_arr[] = $arr[$i]; }

				return $_arr;
			}
		}

		// si < 0
		$si += $len;
		$ei = $ei < 0 ? $ei+$len : $ei;

		if ( $ei <= $si )
		{ return []; }

		$_arr = [];

		for ( $i = $si; $i < $ei; $i++ )
		{ $_arr[] = $arr[$i]; }

		return $_arr;
	} 

	/**
	 * Truncate array to $length limit.
	 *
	 * @param array $arr
	 * @param integer $length
	 * @since 1.0.0
	 * @return array Array truncated when $length > count($arr).
	 */
	public static function truncate ( array $arr, int $length ) : array
	{
		if ( count($arr) > $length )
		{ return array_slice($arr, 0, $length); }

		return $arr;
	}
}