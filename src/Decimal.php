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
}