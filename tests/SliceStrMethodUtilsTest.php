<?php
namespace Piggly\Test\Decimal;

use PHPUnit\Framework\TestCase;
use Piggly\Decimal\Utils;

/**
 * @coversDefaultClass \Piggly\Decimal\Utils
 */
class SliceStrMethodUtilsTest extends TestCase
{
	/**
	 * Assert if is matching the expected data.
	 *
	 * @covers ::sliceStr
	 * @test Expecting positive assertion
    * @dataProvider dataSetOne
	 * @param string $expected
	 * @param integer $si Start index.
	 * @param integer $ei End index.
	 * @return void
	 */
	public function testSetOne (
		string $expected,
		int $si,
		int $ei
	)
	{ $this->assertEquals($expected, Utils::sliceStr('0123456789abcdefghijklmnopqrstuvwxyz', $si, $ei)); }
	
	/**
	 * Provider for testSetOne().
	 * @return array
	 */
	public function dataSetOne() : array
	{
		return [
			['', 52 , -20 ],
			['', -16 , -37 ],
			['x', -3 , -2 ],
			['', -8 , -25 ],
			['', 50 , -62 ],
			['', 10 , -61 ],
			['', -14 , -30 ],
			['01234567', -60 , -28 ],
			['89abcdefghijklmnopqrstuvwxyz', 8 , 42 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -38 , 42 ],
			['', -8 , -42 ],
			['ghijklmnopqrstu', -20 , -5 ],
			['', 21 , -57 ],
			['0123456789abcdefghijklmn', -56 , -12 ],
			['456789abcdefghijklmnopqrstu', 4 , -5 ],
			['0123456789abcdefghijklmnopqrstuvwx', -37 , -2 ],
			['', 61 , -45 ],
			['3456789abcdefghijklmnopqrst', 3 , -6 ],
			['cdefghijklmnopqrstuvwxyz', -24 , 47 ],
			['', 53 , 32 ],
			['0123456789abcdefghijklmnopqrstuvwx', -48 , -2 ],
			['', 43 , -5 ],
			['', 54 , -18 ],
			['', 56 , -33 ],
			['abcdefghijklmnopqrstuvwxyz', 10 , 52 ],
			['', 22 , -69 ],
			['abcdefghijklmnopqrstuvwxyz', -26 , 60 ],
			['', 44 , -20 ],
			['', 28 , 2 ],
			['', 62 , 56 ],
			['', 50 , -45 ],
			['', 62 , 60 ],
			['456789abcdefghijklmnopqrstuvwxyz', -32 , 68 ],
			['', 51 , 51 ],
			['', -46 , -45 ],
			['', -7 , 15 ],
			['', 65 , 41 ],
			['mnopqrstuvwxyz', 22 , 44 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -55 , 60 ],
			['23456789abcdefghijklmnopqrstuvwxyz', -34 , 53 ],
			['6789abcdef', 6 , -20 ],
			['', -20 , -43 ],
			['yz', 34 , 47 ],
			['012345', -42 , 6 ],
			['', 31 , -16 ],
			['23456789abcdefghijklmn', 2 , -12 ],
			['0123456789abcdef', -71 , 16 ],
			['0123456789abcdefghijklmnop', -44 , 26 ],
			['0123456789ab', -60 , -24 ],
			['', 51 , 20 ],
			['', 18 , -47 ],
			['01234567', -45 , -28 ],
			['0123456789abcdefghijklmnopqrs', -63 , -7 ],
			['z', 35 , 69 ],
			['', -9 , -34 ],
			['', -61 , -45 ],
			['456789abcdefghijklm', 4 , -13 ],
			['', 70 , -45 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -56 , 43 ],
			['', 25 , -13 ],
			['0123456789abcdefghijklmno', -65 , 25 ],
			['0123456789abcdefghijkl', -45 , -14 ],
			['89abcdefghijklmnopqrstuvwxyz', -28 , 71 ],
			['0123456789abcdefghijklmnop', -43 , 26 ],
			['0123456789', -42 , -26 ],
			['', -29 , 7 ],
			['', 33 , -20 ],
			['', 2 , -52 ],
			['jklm', 19 , 23 ],
			['0123', -64 , -32 ],
			['', 69 , -22 ],
			['z', -1 , 63 ],
			['', 56 , -5 ],
			['', -16 , -39 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -54 , 63 ],
			['0123456789abcdefghijklmnopqrstuvwx', -40 , 34 ],
			['z', -1 , 62 ],
			['0123456789abcdefghijklmno', -57 , 25 ],
			['', 39 , 57 ],
			['', 62 , 56 ],
			['', -18 , -21 ],
			['6789abcdefghijklmnop', 6 , 26 ],
			['', -9 , -10 ],
			['abc', -26 , -23 ],
			['0123456789abcdefg', -72 , 17 ],
			['', 67 , 17 ],
			['', 47 , -66 ],
			['', -12 , 12 ],
			['', 36 , -55 ],
			['', -9 , -50 ],
			['', 52 , 51 ],
			['9abcdefghijklmnopqrstuvwxyz', -27 , 68 ],
			['12345678', 1 , -27 ],
			['', -3 , -67 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -36 , 65 ],
			['', -72 , -46 ],
			['lmnopqrstuvw', -15 , -3 ],
			['', 68 , 15 ],
			['', 5 , -54 ],
			['', 8 , -40 ],
			['', 57 , 56 ],
			['', 71 , 5 ],
			['hijklmnopq', -19 , -9 ],
			['0123456789abcde', -68 , -21 ],
			['0123456789abcdefghijklmnopqrstuv', 0 , 32 ],
			['', 6 , -42 ],
			['abc', 10 , 13 ],
			['', -66 , -44 ],
			['', 55 , -9 ],
			['opqrst', 24 , 30 ],
			['', -56 , -38 ],
			['0123456789abcdefghijk', -70 , 21 ],
			['z', 35 , 49 ],
			['0123456789abcdefghijk', -37 , 21 ],
			['', 50 , -4 ],
			['', -53 , -43 ],
			['', -32 , -47 ],
			['efghijklmno', 14 , -11 ],
			['', 48 , 13 ],
			['', -41 , -66 ],
			['qrstuvwxyz', -10 , 69 ],
			['', 51 , 3 ],
			['', 43 , 72 ],
			['defghijklmnop', -23 , 26 ],
			['', 67 , -54 ],
			['', -9 , 6 ],
			['', 55 , 28 ],
			['nopq', 23 , 27 ],
			['', 68 , 40 ],
			['vwx', -5 , 34 ],
			['', -72 , -66 ],
			['01234567', -70 , -28 ],
			['1234567', -35 , 8 ],
			['0123456789abcdefghijklmnopqrst', -59 , 30 ],
			['', -4 , -19 ],
			['abcdefghijklmnopqrstuvw', 10 , -3 ],
			['defghijklmnopqrstuvwxyz', 13 , 72 ],
			['56789abcdefghijklmno', -31 , -11 ],
			['', 47 , 56 ],
			['', -64 , -38 ],
			['456789abcdefghijklmnopqrst', -32 , -6 ],
			['', 61 , 39 ],
			['', 32 , -66 ],
			['', 28 , 7 ],
			['0123456789abcdefghijklmnopqrstuvwx', -51 , 34 ],
			['st', 28 , -6 ],
			['', -1 , -27 ],
			['', 34 , -51 ],
			['', 26 , -20 ],
			['klmnopqrstuvwxyz', 20 , 53 ],
			['', -1 , -49 ],
			['', -68 , -54 ],
			['', 20 , -66 ],
			['', 52 , -66 ],
			['', 71 , 17 ],
			['', 58 , 53 ],
			['', 54 , -20 ],
			['0123456789abcdef', -70 , 16 ],
			['rstuvw', 27 , 33 ],
			['', 43 , 7 ],
			['', 43 , -21 ],
			['', -45 , -48 ],
			['', 53 , 40 ],
			['', 31 , -26 ],
			['0123456789abcdefghijklmno', -42 , 25 ],
			['012', -62 , -33 ],
			['01234567', -56 , -28 ],
			['tuvwxyz', 29 , 61 ],
			['0123456789abcdefghijklmnopqrstuvwxy', -54 , -1 ],
			['cdefghijklmnopqr', 12 , -8 ],
			['', 71 , 69 ],
			['', 68 , 72 ],
			['', -29 , 6 ],
			['', -1 , -31 ],
			['', -19 , 2 ],
			['', 48 , 4 ],
			['0123456789a', -41 , 11 ],
			['', -36 , -71 ],
			['', -15 , -65 ],
			['bcdefghijklmnopqrstuvwxyz', -25 , 46 ],
			['', 71 , 8 ],
			['', -14 , -54 ],
			['012', -54 , 3 ],
			['', 30 , 29 ],
			['', -6 , -47 ],
			['', -58 , -54 ],
			['', 40 , 6 ],
			['', 52 , -37 ],
			['', 28 , -39 ],
			['0123456789abcd', -45 , -22 ],
			['tuv', -7 , -4 ],
			['', 53 , -17 ],
			['', -11 , -36 ],
			['', -71 , -38 ],
			['', -11 , 0 ],
			['', 21 , -31 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -56 , 51 ],
			['789abcdefghijklmnopqrstuvwxyz', -29 , 61 ],
			['789abcdefghijklmnopqrstuvwxyz', -29 , 71 ],
			['abcdefghijklmnopqrstuvwxyz', -26 , 47 ],
			['6789abcdefghijk', 6 , 21 ],
			['', 61 , 70 ],
			['', -19 , -33 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -64 , 72 ],
			['', 59 , 16 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -69 , 41 ],
			['', -22 , -58 ],
			['0123456789abcdefghijklmnop', -45 , 26 ],
			['0123456789abcdef', -59 , 16 ],
			['', 72 , 28 ],
			['', 56 , -22 ],
			['mnopqrstuvwxyz', 22 , 69 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -46 , 72 ],
			['0123456789abcdefghijklmnopq', -46 , 27 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -45 , 46 ],
			['pqrstuvwxy', 25 , 35 ],
			['', 72 , 26 ],
			['', 24 , -27 ],
			['89abcdefgh', 8 , -18 ],
			['', 53 , 52 ],
			['', 56 , -20 ],
			['', 67 , 66 ],
			['ijklmnopqrstuvwxyz', 18 , 41 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -67 , 70 ],
			['67', 6 , -28 ],
			['', 49 , 19 ],
			['', 53 , 32 ],
			['0123456789abc', -47 , 13 ],
			['34', 3 , 5 ],
			['', -17 , -37 ],
			['hijklmnopqrstuvwxy', -19 , 35 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', 0 , 60 ],
			['56789abcdefghijklmnopqrstuvwxyz', 5 , 50 ],
			['0123456789abcdefghij', -65 , 20 ],
			['', 39 , 44 ],
			['', -4 , 20 ],
			['', 3 , -50 ],
			['efghijklmno', 14 , 25 ],
			['', 43 , 38 ],
			['wxyz', 32 , 62 ],
			['bcdefghijklmnopqrs', 11 , -7 ],
			['m', -14 , -13 ],
			['', -8 , -11 ],
			['', 69 , 50 ],
			['0123456789abcde', -58 , 15 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -61 , 64 ],
			['', -60 , -45 ],
			['', -44 , -54 ],
			['klmno', -16 , -11 ],
			['vwxyz', 31 , 59 ],
			['', -3 , -31 ],
			['', 71 , -64 ],
			['0123456789abcd', -64 , 14 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -46 , 68 ],
			['0', -56 , 1 ],
			['', 0 , -46 ],
			['', 33 , -6 ],
			['', -27 , -69 ],
			['', -29 , -61 ],
			['0123456789abcdefghijklmnopqrstuvwxyz', -45 , 68 ],
			['', 42 , 53 ],
			['', -2 , -61 ],
			['', 66 , 60 ],
			['', 70 , 60 ],
			['', -31 , -36 ],
			['fghijklmnopqrstuvwxyz', 15 , 59 ],
			['', 58 , 46 ],
			['0123456789ab', -63 , -24 ],
			['', 52 , -37 ],
			['', -43 , -69 ],
			['', 61 , 65 ],
			['', 52 , 54 ],
			['', -61 , -39 ],
			['0123456789', -46 , 10 ],
			['opqrstuvwxyz', -12 , 71 ],
			['vwxyz', 31 , 50 ],
			['0123456789abcdefghijklmnop', -54 , 26 ],
			['klmnopqrstuvwxyz', 20 , 55 ],
			['0123456789abcdefghij', -49 , 20 ],
			['', 24 , -55 ],
			['789abcdefghijklmnopqrstuvwxyz', -29 , 56 ],
			['0123', -70 , -32 ],
			['01234567', -57 , 8 ],
			['nopqrstuvw', 23 , -3 ],
			['', 34 , -21 ],
			['6789abcdefghijklmno', -30 , -11 ],
			['0123456789abcdefghijklmn', -56 , 24 ],
			['0123456789abcdefghijklmnopqrstuvwx', -60 , 34 ],
			['', -32 , -70 ],
			['', 40 , 64 ],
			['', 64 , -25 ],
			['123456789abcdefghijk', -35 , -15 ],
			['', 42 , 62 ],
			['0123456789abcd', -67 , -22 ],
			['', 62 , -42 ],
			['', 59 , 10 ],
			['', 48 , -41 ],
			['', 3 , -71 ],
			['', -7 , -45 ],
			['012', -48 , 3 ],
			['bcdefghijkl', 11 , -14 ],
			['', 22 , 9 ],
			['ghijklmnopqrstuvwx', -20 , 34 ],
			['', 9 , 7 ],
			['', -5 , 7 ],
			['', 1 , -35 ],
			['', -17 , -25 ],
			['defghijklmnopqrstu', 13 , 31 ],
			['23456789abcdefghijklmn', -34 , -12 ],
			['', -24 , -27 ],
			['', -4 , 1 ],
			['', 30 , -12 ],
			['efghijklmnopqrstuvwxy', -22 , -1 ],
			['', 9 , 4 ],
			['', 9 , 5 ],
			['lmnopqrstuvwxy', -15 , -1 ],
			['3456789abcdefghijk', -33 , 21 ],
			['ef', -22 , -20 ],
			['efghijk', -22 , -15 ],
			['', 10 , -36 ],
			['defghijklmnopqrstuvwx', 13 , 34 ],
			['fghijklmnopq', -21 , -9 ],
			['', 34 , 31 ],
			['89abcdefg', 8 , -19 ],
			['', 22 , -27 ],
			['', 28 , -33 ],
			['123456789abcdefghijklmn', 1 , 24 ],
			['456789abcdefghijklmnopqrstuvwxyz', 4 , 36 ],
			['', 22 , -23 ],
			['', -8 , -25 ],
			['', 27 , -32 ],
			['', -12 , -24 ],
			['456789abcdefghijklmnopqrstuv', -32 , -4 ],
			['', -24 , -24 ],
			['', -1 , 25 ],
			['', 35 , 8 ],
			['', 21 , -23 ],
			['', 35 , 25 ],
			['', -5 , 14 ],
			['', -2 , 11 ],
			['', 33 , 11 ],
			['klmnopqrstuvwxy', -16 , 35 ],
			['45678', 4 , -27 ],
			['', 33 , 22 ],
			['klmnopqrstuv', -16 , 32 ],
			['', -7 , 12 ],
			['', 34 , 3 ],
			['lmnopq', -15 , -9 ],
			['jklm', -17 , -13 ],
			['efg', 14 , 17 ],
			['567', -31 , 8 ],
			['', 20 , 15 ],
			['', 34 , 27 ],
			['0123456789abcdefghijklmnopqrstuvw', 0 , -3 ],
			['', 30 , 11 ],
			['pqrstuvwxy', 25 , 35 ],
			['123456789abcdefghijklmnopqrstuvwx', -35 , -2 ],
			['', 29 , 2 ],
			['qrstuvwxy', -10 , -1 ],
			['0123456789abcdefghijklmnopqrstuvwx', 0 , -2 ],
			['', -2 , 32 ],
			['56789abcdefghijk', -31 , 21 ],
			['456789abcdefghijklmnop', 4 , -10 ],
			['', 19 , -33 ],
			['34567', 3 , -28 ],
			['', -7 , 19 ],
			['', 27 , -21 ],
			['ghijklmno', 16 , -11 ],
			['', -14 , -26 ],
			['9abcdefghijklmnopq', -27 , 27 ],
			['ghijklmnopqrstuv', 16 , -4 ],
			['', -1 , -17 ],
			['', -7 , 29 ],
			['', 32 , -12 ],
			['0123456789abcdefghijklmnopqr', -36 , -8 ],
			['', -7 , -9 ],
			['ijklmnopqrstuvwxy', 18 , 35 ],
			['', -2 , -19 ],
			['', -10 , -13 ],
			['', -4 , -22 ],
			['', -17 , -32 ],
			['', 32 , 27 ],
			['0123456789', 0 , 10 ],
			['efghijklm', -22 , -13 ],
			['', 9 , -36 ],
			['', 30 , 24 ],
			['', -1 , -5 ],
			['123456789abcdefgh', 1 , -18 ],
			['', -18 , -27 ],
			['', 26 , -13 ],
			['123456789a', 1 , 11 ],
			['', -12 , 21 ],
			['', 24 , 19 ],
			['ef', -22 , 16 ],
			['hijklmnopqrstu', 17 , -5 ],
			['', 22 , 0 ],
			['', -22 , -25 ],
			['56789abcdefghijklm', -31 , 23 ],
			['yz', 34 , 36 ],
			['23456789abcdef', 2 , -20 ],
			['', -18 , -32 ],
			['cdefghijklmnopqrstuv', -24 , -4 ],
			['', 28 , -33 ],
			['opqr', 24 , -8 ],
			['pqr', 25 , 28 ],
			['', 15 , -29 ],
			['abcdefghijklmnopq', -26 , 27 ],
			['', 33 , 28 ],
			['789abcdefg', -29 , 17 ],
			['', 9 , 9 ],
			['23456789abcdefghijklmnopqrstu', -34 , 31 ],
			['123456789abcdefghijklmnopqrstuvw', -35 , -3 ],
			['', 36 , 15 ],
			['', 12 , -27 ],
			['5', -31 , 6 ],
			['hijklmno', 17 , -11 ],
			['', -9 , -22 ],
			['', -26 , 0 ],
			['78', 7 , 9 ],
			['qrstuvwx', -10 , 34 ],
			['abcd', -26 , 14 ],
			['lmnop', 21 , 26 ],
			['', -4 , -26 ],
			['89abcdef', -28 , -20 ],
			['mnopqrst', -14 , -6 ],
			['', 1 , -35 ],
			['', 35 , 4 ],
			['', -15 , 4 ],
			['', -27 , 4 ],
			['', 22 , -36 ],
			['', -14 , 4 ],
			['', 31 , -20 ],
			['', 30 , 5 ],
			['', -29 , -29 ],
			['56789abcdefgh', -31 , -18 ],
			['123456789abcdefghijklmn', 1 , -12 ],
			['y', 34 , -1 ],
			['234', -34 , -31 ],
			['9', 9 , 10 ],
			['', -19 , -28 ],
			['', 35 , -31 ],
			['defghijklmno', 13 , -11 ],
			['', 34 , -19 ],
			['', -3 , 20 ],
			['', -19 , -33 ],
			['', 27 , -11 ],
			['', 32 , -14 ],
			['', -7 , 3 ],
			['', 32 , 4 ],
			['', 27 , 9 ],
			['', -18 , 10 ],
			['mno', 22 , -11 ],
			['tuvwxyz', 29 , 36 ],
			['', -2 , 23 ],
			['', -7 , 26 ],
			['ghijklmnopq', -20 , 27 ],
			['', 26 , 6 ],
			['', -19 , 2 ],
			['', 35 , -16 ],
			['', 32 , 14 ],
			['56789abcdefghijklmnopqr', -31 , -8 ],
			['', -23 , -33 ],
			['hijklmnop', 17 , -10 ],
			['', -12 , 7 ],
			['123456789abcdefghijklmnopqrst', -35 , -6 ],
			['', 16 , -27 ],
			['bcdefghijklmnopqr', 11 , -8 ],
			['abcdefghijklmnopqrst', -26 , -6 ],
			['', -4 , -21 ],
			['', 24 , 19 ],
			['', 36 , -34 ],
			['89abcdefghijklmnopqrst', 8 , 30 ],
			['56789abcdefghijklmnopqrstuvwx', 5 , 34 ],
			['bcdefghijklmnopqrstuvwxyz', -25 , 36 ],
			['', -21 , -35 ],
			['', -36 , 0 ],
			['', -17 , 11 ],
			['5', -31 , 6 ],
			['', -7 , -36 ],
			['01234', 0 , -31 ],
			['a', 10 , -25 ],
			['rstuv', 27 , -4 ],
			['', -23 , -31 ],
			['123456789abcdefghijklmnopqrs', -35 , -7 ],
			['cdefgh', -24 , 18 ],
			['345678', -33 , 9 ],
			['23456789abcdefghijklmno', 2 , -11 ],
			['', 27 , -30 ],
			['', -18 , 12 ],
			['3', -33 , 4 ],
			['456789abcdefghijklmn', -32 , -12 ],
			['', 22 , 9 ],
			['', 19 , 11 ],
			['vwx', 31 , 34 ],
			['', 20 , -21 ],
			['', 19 , -17 ],
			['', -14 , -36 ],
			['f', -21 , 16 ],
			['nopqrstuvwxyz', -13 , 36 ],
			['', 8 , 6 ],
			['efghijklmnop', 14 , -10 ],
			['', 29 , -35 ],
			['56789abcd', -31 , 14 ],
			['', 2 , 1 ],
			['', -19 , -28 ],
			['fghijklmnopqrstuvwx', 15 , -2 ],
			['', -16 , -18 ],
			['cdef', -24 , 16 ],
			['', -8 , 8 ],
			['', -29 , 2 ],
			['', -7 , -23 ],
			['012345', -36 , -30 ],
			['', -1 , -14 ],
			['hijklmnopqr', 17 , -8 ],
			['a', 10 , -25 ],
			['xyz', -3 , 36 ],
			['789abc', 7 , 13 ],
			['', 31 , 6 ],
			['0123456', 0 , -29 ],
			['', 28 , 3 ],
			['', -19 , 14 ],
			['', 12 , -36 ],
			['', 31 , 9 ],
			['456789abcde', 4 , 15 ],
			['9abcdefghij', 9 , 20 ],
			['', -27 , 4 ],
			['', -9 , -33 ],
			['6789abcde', 6 , 15 ],
			['bc', 11 , -23 ],
			['cdefgh', 12 , -18 ],
			['', 25 , -19 ],
			['6789abcdefghijklmnopqrstuvwx', 6 , 34 ],
			['bcdefgh', 11 , -18 ],
			['23', 2 , 4 ],
			['', -7 , -32 ],
			['', 29 , 8 ],
			['', 15 , -28 ],
			['', -17 , -35 ],
			['89abcdefghijklmnopqrstuv', -28 , -4 ],
			['', 23 , -21 ],
			['', 30 , -32 ],
			['', -12 , -17 ],
			['', 35 , -24 ],
			['no', -13 , 25 ],
			['', -1 , -5 ],
			['6789abcdefghijklmnopqrs', -30 , -7 ],
			['', -24 , 4 ],
			['', 19 , -21 ],
			['', -11 , -29 ],
			['123456789abcde', -35 , 15 ],
			['', 11 , 10 ],
			['', 17 , 3 ],
			['', 12 , 4 ],
			['9abcdefghijklmnopqrstuvw', -27 , -3 ],
			['456789abcdefghijklmnopqrstuvwx', 4 , 34 ],
			['hi', -19 , 19 ],
			['efghi', 14 , 19 ],
			['', -17 , -21 ],
			['123456789a', 1 , 11 ],
			['6789abcdefghijklmnopqrs', 6 , 29 ],
			['abcdefghijklmnopq', -26 , 27 ],
			['123456789abcdefghijklmnopqrstu', -35 , -5 ],
			['', -20 , 3 ],
			['456789abcdefghijklmnopqrstuvw', 4 , 33 ],
			['', 22 , 18 ],
			['', -13 , -23 ],
			['', 24 , 18 ],
			['789', 7 , 10 ],
			['', -23 , 2 ],
			['123456789abcdefghijklmnopqrst', -35 , -6 ],
			['', -5 , 8 ],
			['789abcdefghijklmnopq', 7 , 27 ],
			['0123456789abcdefghijklmnop', 0 , 26 ],
			['bcdefghi', 11 , -17 ],
			['3456789abcdefghij', 3 , 20 ],
			['', -20 , -21 ],
			['bcdefghijklmnopqr', -25 , -8 ],
			['bcdefghijk', 11 , -15 ],
			['hijklmn', -19 , 24 ],
			['23456789abcdefghijk', -34 , -15 ],
			['abcdefghijklmnopqrstuvwx', -26 , -2 ],
			['', -3 , -11 ],
			['rstuvwxyz', 27 , 36 ],
			['ef', 14 , 16 ],
			['', -24 , -30 ],
			['0123456789abcdefghijklmnopqrstuvwx', 0 , 34 ],
			['', 31 , 5 ],
			['', -15 , 1 ],
			['345678', -33 , 9 ],
			['', -12 , -19 ],
			['rstu', 27 , 31 ],
			['789abcdefghijklmnopqrstuv', 7 , -4 ],
			['', 33 , 1 ],
			['', -17 , 0 ],
			['', -8 , -17 ],
			['789abcdefghijklmno', -29 , 25 ],
			['', 15 , -36 ],
			['', 10 , 8 ],
			['efghijklmnopqrstuvwxy', 14 , 35 ],
			['lmnopqrstuv', -15 , 32 ],
			['', 22 , 7 ],
			['', 30 , 24 ],
			['', 29 , 24 ],
			['9', 9 , 10 ],
			['', 35 , 27 ],
			['lmnopqrst', 21 , 30 ],
			['ijklmnopqr', 18 , 28 ],
			['', 1 , 0 ],
			['', 11 , 9 ],
			['ghijklmn', 16 , 24 ],
			['', 30 , 7 ],
			['123456789abcdef', 1 , 16 ],
			['', 30 , 23 ],
			['cd', 12 , 14 ],
			['', 34 , 1 ],
			['', 17 , 17 ],
			['', 2 , 1 ],
			['', 4 , 2 ],
			['89abcdefghi', 8 , 19 ],
			['', 8 , 5 ],
			['', 32 , 12 ],
			['89abcdefghijklm', 8 , 23 ],
			['', 22 , 3 ],
			['cdefghijklmnopqrstuvwx', 12 , 34 ],
			['y', 34 , 35 ],
			['abcdefghijklmnop', 10 , 26 ],
			['', 36 , 8 ],
			['9abcdefghijklmno', 9 , 25 ],
			['b', 11 , 12 ],
			['hij', 17 , 20 ],
			['', 30 , 2 ],
			['defghijklmnopq', 13 , 27 ],
			['23456789abcdefghijkl', 2 , 22 ],
			['89abcdefghijklmnop', 8 , 26 ],
			['8', 8 , 9 ],
			['cdefghijklmno', 12 , 25 ],
			['ij', 18 , 20 ],
			['cdefghijklmnop', 12 , 26 ],
			['', 27 , 5 ],
			['lmnopqrstuvwxy', 21 , 35 ],
			['', 31 , 20 ],
			['3456789abcdefghijklm', 3 , 23 ],
			['lm', 21 , 23 ],
			['rst', 27 , 30 ],
			['', 33 , 14 ],
			['', 35 , 10 ],
			['', 28 , 10 ],
			['', 27 , 19 ],
			['9abcdefghijklmnopqrstu', 9 , 31 ],
			['23456789abcdefghijklmnopqrstuvwxyz', 2 , 36 ],
			['456789abcdefghijklmnopqrstuvwxyz', 4 , 36 ],
			['bcdefghi', 11 , 19 ],
			['', 30 , 10 ],
			['hijklmnopqrs', 17 , 29 ],
			['pqrstu', 25 , 31 ],
			['', 20 , 14 ],
			['ijk', 18 , 21 ],
			['opqrstuvw', 24 , 33 ],
			['456789abcdefghijklmn', 4 , 24 ],
			['', 6 , 1 ],
			['012345678', 0 , 9 ],
			['56789abcdefghijk', 5 , 21 ],
			['stuvw', 28 , 33 ],
			['', 20 , 15 ],
			['fghijklmnopqrstuvw', 15 , 33 ],
			['a', 10 , 11 ],
			['', 29 , 14 ],
			['ijklmnopqrstu', 18 , 31 ],
			['ijklmnopqr', 18 , 28 ],
			['', 28 , 17 ],
			['', 19 , 2 ],
			['', 6 , 4 ],
			['', 29 , 7 ],
			['', 36 , 34 ],
			['bcdefghijklmnopq', 11 , 27 ],
			['89abcdefghijklmnopqrstu', 8 , 31 ],
			['', 35 , 15 ],
			['23456789abcdefghi', 2 , 19 ],
			['ghijklmnopqrstuvwxyz', 16 , 36 ],
			['', 32 , 0 ],
			['', 28 , 28 ],
			['', 30 , 1 ],
			['56789abcdefghijklmnopqrstuvwx', 5 , 34 ],
			['', 29 , 27 ],
			['ghijklm', 16 , 23 ],
			['u', 30 , 31 ],
			['', 27 , 22 ],
			['bcd', 11 , 14 ],
			['', 14 , 5 ],
			['', 33 , 27 ],
			['9abcdefghijklmnopqrstuvw', 9 , 33 ],
			['cd', 12 , 14 ],
			['456789abcde', 4 , 15 ],
			['', 35 , 12 ],
			['456789abcdefghijklmnopqrst', 4 , 30 ],
			['123', 1 , 4 ],
			['pqrstuvwxy', 25 , 35 ],
			['rstuvwxy', 27 , 35 ],
			['fghijklmno', 15 , 25 ],
			['', 24 , 24 ],
			['cdefghijklmnopqr', 12 , 28 ],
			['qrstuvwxyz', 26 , 36 ],
			['', 35 , 5 ],
			['89abcdefghijklmnopqrstuv', 8 , 32 ],
			['', 26 , 6 ],
			['', 33 , 6 ],
			['bcdefghijklmnopqrstuv', 11 , 32 ],
			['bcdefghijklmnopqrstuvwxy', 11 , 35 ],
			['', 36 , 3 ],
			['bcdefg', 11 , 17 ],
			['3456789abcdef', 3 , 16 ],
			['9abcdefghij', 9 , 20 ],
			['kl', 20 , 22 ],
			['', 33 , 1 ],
			['', 27 , 12 ],
			['', 33 , 26 ],
			['', 20 , 2 ],
			['456789abcdefghijklmnopqr', 4 , 28 ],
			['ghijklmno', 16 , 25 ],
			['mnopqr', 22 , 28 ],
			['cdefghi', 12 , 19 ],
			['3456789', 3 , 10 ],
			['', 32 , 8 ],
			['', 27 , 13 ],
			['', 34 , 25 ],
			['e', 14 , 15 ],
			['lmnopqrstuvwxy', 21 , 35 ],
			['abcdefghijklmnopqr', 10 , 28 ],
			['89abcdefghijklmnop', 8 , 26 ],
			['', 29 , 7 ],
			['', 16 , 9 ],
			['', 7 , 4 ],
			['', 14 , 5 ],
			['', 17 , 14 ],
			['def', 13 , 16 ],
			['efghij', 14 , 20 ],
			['pqrstuvw', 25 , 33 ],
			['', 32 , 14 ],
			['', 36 , 22 ],
			['', 34 , 34 ],
			['123456789abcdefghijklmnopq', 1 , 27 ],
			['qr', 26 , 28 ],
			['efgh', 14 , 18 ],
			['ghijklmnopqrstuvwxyz', 16 , 36 ],
			['lmnop', 21 , 26 ],
			['', 4 , 2 ],
			['123456789abcdefghijklmnopqrstu', 1 , 31 ],
			['lmnopqrstuvwx', 21 , 34 ],
			['456789abcdefgh', 4 , 18 ],
			['', 23 , 8 ],
			['89abcdefghijklmnopqrstuv', 8 , 32 ],
			['', 27 , 21 ],
			['', 30 , 19 ],
			['', 15 , 5 ],
			['h', 17 , 18 ],
			['123456789abc', 1 , 13 ],
			['78', 7 , 9 ],
			['9abcdefghijklm', 9 , 23 ],
			['789abcdefgh', 7 , 18 ],
			['', 20 , 20 ],
			['', 4 , 1 ],
			['789abcdefghijklmno', 7 , 25 ],
			['', 30 , 28 ],
			['', 13 , 2 ],
			['', 18 , 10 ],
			['', 23 , 16 ],
			['', 29 , 18 ],
			['', 27 , 10 ],
			['', 13 , 2 ],
			['0123456789abcdefghijklmnopqrstuvwxy', 0 , 35 ],
			['', 27 , 27 ],
			['jklmnopqrs', 19 , 29 ],
			['abcdefghijklmnopqrstuvwxyz', 10 , 36 ],
			['ijklm', 18 , 23 ],
			['cdefghijklmnopq', 12 , 27 ],
			['56789abc', 5 , 13 ],
			['', 18 , 11 ],
			['', 9 , 5 ],
			['', 6 , 5 ],
			['lmno', 21 , 25 ],
			['', 30 , 10 ],
			['', 32 , 14 ],
			['', 13 , 10 ],
			['', 25 , 20 ],
			['', 27 , 1 ],
			['3456789abcdefghijklmnopqr', 3 , 28 ],
			['', 29 , 18 ],
			['q', 26 , 27 ],
			['', 36 , 14 ],
			['efghijkl', 14 , 22 ],
			['01', 0 , 2 ],
			['', 21 , 5 ],
			['', 36 , 26 ],
			['', 11 , 2 ],
			['', 22 , 5 ],
			['hijklmnopqrstuvwx', 17 , 34 ],
			['', 18 , 11 ],
			['ghijklmno', 16 , 25 ],
			['fghijklmnopqrstu', 15 , 31 ],
			['3456789abcdefghijklmn', 3 , 24 ],
			['', 31 , 23 ],
			['', 34 , 29 ],
			['', 15 , 13 ],
			['', 14 , 1 ],
			['w', 32 , 33 ],
			['g', 16 , 17 ],
			['mno', 22 , 25 ],
			['89ab', 8 , 12 ],
			['3456', 3 , 7 ],
			['', 10 , 7 ],
			['', 25 , 21 ],
			['', 13 , 1 ],
			['efghijklmnopq', 14 , 27 ],
			['', 25 , 24 ],
			['', 35 , 1 ],
			['', 33 , 8 ],
			['mnopqrstuv', 22 , 32 ],
			['456789ab', 4 , 12 ],
			['', 4 , 2 ],
			['efghijklmnopqrstuvwx', 14 , 34 ],
			['', 29 , 5 ],
			['', 21 , 21 ],
			['', 33 , 33 ],
			['ijklmnopqrs', 18 , 29 ],
			['abc', 10 , 13 ],
			['', 14 , 5 ],
			['', 23 , 3 ],
			['89abcdefghijklmnopqrstuv', 8 , 32 ],
			['', 23 , 6 ],
			['', 27 , 7 ],
			['', 17 , 11 ],
			['jklmnopq', 19 , 27 ],
			['', 16 , 13 ],
			['', 20 , 12 ],
			['klmnopqrstuv', 20 , 32 ],
			['9abcdefghijklmnopqrst', 9 , 30 ],
			['', 29 , 10 ],
			['0123456789abcdefghijklmnopqrstuvwxy', 0 , 35 ],
			['', 23 , 7 ],
			['89abc', 8 , 13 ],
			['789abcdefghijklmnopqr', 7 , 28 ],
			['', 26 , 24 ],
			['3456789abcdefghijklmnopqr', 3 , 28 ],
			['', 29 , 15 ],
			['ij', 18 , 20 ],
			['', 24 , 24 ],
			['', 10 , 7 ],
			['ghijk', 16 , 21 ],
			['', 34 , 26 ],
			['1234567', 1 , 8 ],
			['89abcdefghijklmnopqrstuvwx', 8 , 34 ],
			['', 26 , 19 ],
			['efgh', 14 , 18 ],
			['678', 6 , 9 ],
			['', 33 , 22 ],
			['hijk', 17 , 21 ],
			['', 35 , 8 ],
			['', 3 , 2 ],
			['6789abc', 6 , 13 ],
			['456789abcdefghijklmn', 4 , 24 ],
			['789abcdefghijklmnopqrst', 7 , 30 ],
			['', 36 , 33 ],
			['nopqrstuvwxyz', 23 , 36 ],
			['9abcdefghijk', 9 , 21 ],
			['tuvwx', 29 , 34 ],
			['', 28 , 25 ],
			['', 28 , 16 ],
			['345678', 3 , 9 ],
			['cdefghijklmn', 12 , 24 ],
			['', 25 , 4 ],
			['123456789abcdefghijklmnopqrstuv', 1 , 32 ],
			['', 14 , 11 ],
			['', 3 , 3 ],
			['abcdefghi', 10 , 19 ],
			['56789ab', 5 , 12 ],
			['', 33 , 13 ],
			['', 9 , 6 ],
			['', 31 , 26 ],
			['', 30 , 16 ],
			['', 35 , 10 ],
			['', 18 , 9 ],
			['456789a', 4 , 11 ],
			['klmnopqrs', 20 , 29 ],
			['9abcdefghijk', 9 , 21 ],
			['jkl', 19 , 22 ],
			['j', 19 , 20 ],
			['', 13 , 13 ],
			['56789abcdefghijklmnopqrstuv', 5 , 32 ],
			['', 13 , 13 ],
			['ghijklmnopqrst', 16 , 30 ],
			['', 24 , 18 ],
			['0123456789ab', 0 , 12 ],
			['56789abcdefghij', 5 , 20 ],
			['', 15 , 9 ],
			['123456789abcdefghijk', 1 , 21 ],
			['', 13 , 2 ],
			['jklmnopqrstu', 19 , 31 ],
			['', 13 , 6 ],
			['bcdef', 11 , 16 ],
			['defghijklmno', 13 , 25 ],
			['6789abcdefghijklmnopq', 6 , 27 ],
			['3456789abcdefghijklmnopqr', 3 , 28 ],
			['6789abcdefghijklm', 6 , 23 ],
			['6789abcdefghijklmnopqrs', 6 , 29 ],
			['3456789abcdefghijk', 3 , 21 ],
			['defghijklmnopqrstuvw', 13 , 33 ],
			['bcdefghijk', 11 , 21 ],
			['defghijklmnopqr', 13 , 28 ],
			['56789abcdefghijklmnopqrstuvwxy', 5 , 35 ],
			['89abcdefghijklmnopqrstuvwx', 8 , 34 ],
			['ghijklmnopqr', 16 , 28 ],
			['defghijkl', 13 , 22 ],
			['9abcdefghijklmn', 9 , 24 ],
			['hijklmnopqrst', 17 , 30 ],
			['ijklmnopqrstu', 18 , 31 ],
			['789abcdefghijklmnopqrstuvwxy', 7 , 35 ],
			['fghijklmnopqrstu', 15 , 31 ],
			['cdefghijklmnopqrstuvw', 12 , 33 ],
			['efghijklmnopq', 14 , 27 ],
			['cdefghijklmnopqrstuvwxyz', 12 , 36 ],
			['bcdefghijklmnop', 11 , 26 ],
			['789abcdefghijklmnopqrstu', 7 , 31 ],
			['bcdefghijklmnopqrst', 11 , 30 ],
			['ijklmno', 18 , 25 ],
			['9abcdefghijklmnopq', 9 , 27 ],
			['23456789abcdefghijklmn', 2 , 24 ],
			['fghijklmnopqrstuvw', 15 , 33 ],
			['456789abcdefghijklmnopqrst', 4 , 30 ],
			['3456789abcdefghijklmnopqrstuvwxyz', 3 , 36 ],
			['cdefghijklmnopqrs', 12 , 29 ],
			['23456789abcdefghijklmnopqrstuvwx', 2 , 34 ],
			['efghi', 14 , 19 ],
			['6789abcdefghijk', 6 , 21 ],
			['56789abcdefghijklmnopqrs', 5 , 29 ],
			['23456789abcdefghijklmn', 2 , 24 ],
			['23456789abcdefghijklmnop', 2 , 26 ],
			['3456789abcdefghijklmnop', 3 , 26 ],
			['789abcdefghijklmnopqrstuvw', 7 , 33 ],
			['fghijklmn', 15 , 24 ],
			['efghijklm', 14 , 23 ],
			['6789abcdefghijkl', 6 , 22 ],
			['ijklmnopqrstuvwxyz', 18 , 36 ],
			['cdefghijklmno', 12 , 25 ],
			['3456789abcdefghi', 3 , 19 ],
			['123456789abcdefghijklmnopqrstuvwx', 1 , 34 ],
			['efgh', 14 , 18 ],
			['ghijk', 16 , 21 ],
			['6789abcdefghijklmno', 6 , 25 ],
			['23456789abcdefghijk', 2 , 21 ],
			['23456789abcdefghijklmnopqrstuvwxy', 2 , 35 ],
			['89abcdefghijklmnopqrstuvwxyz', 8 , 36 ],
			['0123456789abcdefghijklmnopqrstuv', 0 , 32 ],
			['ijklmnopqrstu', 18 , 31 ],
			['456789abcdefghi', 4 , 19 ],
			['56789abcdefghijklmnopqr', 5 , 28 ],
			['fghijklmnopqr', 15 , 28 ],
			['3456789abcdefghijklmnop', 3 , 26 ],
			['cdefghijklmnopqrstuvwxyz', 12 , 36 ],
			['3456789abcdefghij', 3 , 20 ],
			['ijklmnopqrs', 18 , 29 ],
			['0123456789abcdefghijklmnop', 0 , 26 ],
			['789abcdefgh', 7 , 18 ],
			['ghijklmnopqrstuvw', 16 , 33 ],
			['cdefghijklmnopqrstuvwxyz', 12 , 36 ],
			['456789abcdefghijklmnopqrs', 4 , 29 ],
			['bcdefghijklmnopqr', 11 , 28 ],
			['efghijklmnopqrs', 14 , 29 ],
			['23456789abcdefghijkl', 2 , 22 ],
			['fghijklmnopqrstuvwx', 15 , 34 ],
			['bcdefgh', 11 , 18 ],
			['9abcdefghijklmnop', 9 , 26 ],
			['hijklmnopqrstuvwxy', 17 , 35 ],
			['56789abcdefghijklmnopqr', 5 , 28 ],
			['123456789abcdefghijkl', 1 , 22 ],
			['56789abcdefghi', 5 , 19 ],
			['6789abcdefghijklmnop', 6 , 26 ],
			['6789abcdefghijklmnopqrs', 6 , 29 ],
			['bcdefghijklmn', 11 , 24 ],
			['efghij', 14 , 20 ],
			['123456789abcdefghijklmnopqrs', 1 , 29 ],
			['9abcdefghijklmnopqrstuvwxyz', 9 , 36 ],
			['ghijklmnopqrstuvwxyz', 16 , 36 ],
			['ijklmnopqrstuvwx', 18 , 34 ],
			['defghijklmnopqrstuvw', 13 , 33 ],
			['hijklmnopqrstuvwxy', 17 , 35 ],
			['bcdefghijklmnopqrstu', 11 , 31 ],
			['23456789abcdefghij', 2 , 20 ],
			['789abcdefghijkl', 7 , 22 ],
			['6789abcdefghijklmnopqrstuvw', 6 , 33 ],
			['defghij', 13 , 20 ],
			['hijklmnopqrstuvwx', 17 , 34 ],
			['456789abcdefghijklmnopqrstuvwx', 4 , 34 ],
			['23456789abcdefghi', 2 , 19 ],
			['3456789abcdefghijklmnopqrstuvw', 3 , 33 ],
			['6789abcdefghijklmnopqr', 6 , 28 ],
			['23456789abcdefghijklmnopqrs', 2 , 29 ],
			['bcdefghijklmnopqrstuv', 11 , 32 ],
			['456789abcdefghijklmnopqr', 4 , 28 ],
			['ghijklmnop', 16 , 26 ],
			['3456789abcdefghijklmnopqrst', 3 , 30 ],
			['cdefghij', 12 , 20 ],
			['abcdefghijklmnopqrstuvwx', 10 , 34 ],
			['abcdefghijk', 10 , 21 ],
			['123456789abcdefghijklmnopqrstuv', 1 , 32 ],
			['defghijklmnopqrs', 13 , 29 ],
			['89abcdefghijklmnopqrs', 8 , 29 ],
			['cdefghijk', 12 , 21 ],
			['123456789abcdefghijklmnop', 1 , 26 ],
			['defghijk', 13 , 21 ],
			['0123456789abcdefghijklmn', 0 , 24 ],
			['0123456789abcdefghijklmn', 0 , 24 ],
			['9abcdefghijklmnop', 9 , 26 ],
			['3456789abcdefghijk', 3 , 21 ],
			['0123456789abcdefghijklmnopqr', 0 , 28 ],
			['bcdefghijklmnopqrs', 11 , 29 ],
			['123456789abcdefghijklmnopqrstuvwxyz', 1 , 36 ],
			['23456789abcdefghijklmnopqrstuvw', 2 , 33 ],
			['ijklmnopqrstuvwx', 18 , 34 ],
			['456789abcdefghijklmnopqrstuvwx', 4 , 34 ],
			['defghijkl', 13 , 22 ],
			['56789abcdefghijklmnop', 5 , 26 ],
			['bcdefghijklmnopq', 11 , 27 ],
			['89abcdefghijklmnopqrstu', 8 , 31 ],
			['56789abcdefghijklm', 5 , 23 ],
			['456789abcdefghijklmnopqr', 4 , 28 ],
			['ijklmnop', 18 , 26 ],
			['bcdefgh', 11 , 18 ],
			['23456789abcdefghijklmnopqrst', 2 , 30 ],
			['89abcdefghijklmnopqrstuvwx', 8 , 34 ],
			['56789abcdefghijklm', 5 , 23 ],
			['hij', 17 , 20 ],
			['6789abcdefghijklmnopqr', 6 , 28 ],
			['hijklm', 17 , 23 ],
			['3456789abcdefghij', 3 , 20 ],
			['hijklmno', 17 , 25 ],
			['hijklmnopqrstuvwxyz', 17 , 36 ],
			['ijklmno', 18 , 25 ],
			['ghijklmnopqr', 16 , 28 ],
			['hijklmnop', 17 , 26 ],
			['hijklmnopqr', 17 , 28 ],
			['9abcdefghijklmn', 9 , 24 ],
			['89abcdefghijkl', 8 , 22 ],
			['ijklmnopqrstuvwxy', 18 , 35 ],
			['abcdefghijklmnopqrstuvwxy', 10 , 35 ],
			['123456789abcdefghijklmnopqrs', 1 , 29 ],
			['bcdefghijklmnopqrstu', 11 , 31 ],
			['ijklmnopqrstuvwxy', 18 , 35 ],
			['456789abcdefghijklmnopqrstuvwxyz', 4 , 36 ],
			['fghijklmnopqrstuvwx', 15 , 34 ],
			['ghijklmnopqrstuv', 16 , 32 ],
			['789abcdefghijklmnopqrstuvwxy', 7 , 35 ],
			['cdefghijklmnop', 12 , 26 ],
			['ghij', 16 , 20 ],
			['defghijklmnopq', 13 , 27 ],
			['9abcdefgh', 9 , 18 ],
			['789abcdefgh', 7 , 18 ],
			['efgh', 14 , 18 ],
			['bcdefgh', 11 , 18 ],
			['9abcdefghijk', 9 , 21 ],
			['123456789abcdefghijklmnopqrstuv', 1 , 32 ],
			['efghijklm', 14 , 23 ],
			['9abcdefghijklmn', 9 , 24 ],
			['56789abcdefghijkl', 5 , 22 ],
			['abcdefghijklmnop', 10 , 26 ],
			['123456789abcdefghijklm', 1 , 23 ],
			['789abcdefghijklmnop', 7 , 26 ],
			['hijklmnop', 17 , 26 ],
			['9abcdefgh', 9 , 18 ],
			['9abcdefghijk', 9 , 21 ],
			['3456789abcdefghijklmnopqrstuvwxy', 3 , 35 ],
			['56789abcdefghijkl', 5 , 22 ],
			['6789abcdefghijklmnopqrstuv', 6 , 32 ],
			['789abcdefghijklmnopqr', 7 , 28 ],
			['abcdefghij', 10 , 20 ],
			['23456789abcdefghijkl', 2 , 22 ],
			['6789abcdefghijkl', 6 , 22 ],
			['23456789abcdefghijklmnopqrstuvwx', 2 , 34 ],
			['23456789abcdefghijklmnopqrstu', 2 , 31 ],
			['bcdefghijklmn', 11 , 24 ],
			['bcdefghijklmnopqrstuvw', 11 , 33 ],
			['ijklmno', 18 , 25 ],
			['23456789abcdefghijklmnopqrstuvwx', 2 , 34 ],
			['123456789abcdefghijklmnopq', 1 , 27 ],
			['456789abcdefghijklmnopqrstuvwxy', 4 , 35 ],
			['9abcdefghijklmn', 9 , 24 ],
			['456789abcdefghijklmnopqrstu', 4 , 31 ],
			['456789abcdefghijklmnop', 4 , 26 ],
			['ijklmnopqrstu', 18 , 31 ],
			['bcdefghijkl', 11 , 22 ],
			['9abcdefghijklmnopqrstuvw', 9 , 33 ],
			['56789abcdefghijklmnopqrstu', 5 , 31 ],
			['fghij', 15 , 20 ],
			['9abcdefghijklmnopqrstuvwxyz', 9 , 36 ],
			['789abcdefghijklmnopqrst', 7 , 30 ],
			['cdefghijklm', 12 , 23 ],
			['defghijklmnopqrs', 13 , 29 ],
			['bcdefghijklm', 11 , 23 ],
			['fghijklmnopqrstuvwx', 15 , 34 ],
			['cdefghijklmnopqrstuvwx', 12 , 34 ],
			['ghijklmn', 16 , 24 ],
			['3456789abcdefghij', 3 , 20 ],
			['0123456789abcdefgh', 0 , 18 ],
			['89abcdefghijk', 8 , 21 ],
			['89abcdefghijklmnop', 8 , 26 ],
			['efghijkl', 14 , 22 ],
			['89abcdefghijklmn', 8 , 24 ],
			['456789abcdefghijklmnopq', 4 , 27 ],
			['89abcdefghijklm', 8 , 23 ],
			['456789abcdefghijk', 4 , 21 ],
			['hijklmno', 17 , 25 ],
			['efghijklm', 14 , 23 ],
			['56789abcdefghij', 5 , 20 ],
			['9abcdefghij', 9 , 20 ],
			['efghijklmnopqrstuvw', 14 , 33 ],
			['cdefghijklmnopqrs', 12 , 29 ],
			['123456789abcdefghijklmnopqrstu', 1 , 31 ],
			['bcdefghijklmnopqrstuvwxyz', 11 , 36 ],
			['hijklmnop', 17 , 26 ],
			['56789abcdefghijklmn', 5 , 24 ],
			['hijklmnopqrstuv', 17 , 32 ],
			['fghijklmnopqrstuvwxyz', 15 , 36 ],
			['efgh', 14 , 18 ],
			['9abcdefghijklmnopqrstuvwxy', 9 , 35 ],
			['defghijklmno', 13 , 25 ],
			['456789abcdefghijklmnopqrstuvw', 4 , 33 ],
			['3456789abcdefghijklmno', 3 , 25 ],
			['bcdefghijkl', 11 , 22 ],
			['fghijklmnopq', 15 , 27 ],
			['23456789abcdefghijklm', 2 , 23 ],
			['89abcdefghijklmno', 8 , 25 ],
			['456789abcdefghijklmno', 4 , 25 ],
			['ijklmnopqrs', 18 , 29 ],
			['efghijklmnopqrstuvwxyz', 14 , 36 ],
			['3456789abcdefghijklmnopqrstuvwx', 3 , 34 ],
			['89abcdefghijklmnop', 8 , 26 ],
			['abcdefghijklmn', 10 , 24 ],
			['9abcdefghijklmn', 9 , 24 ],
			['hijklmnopqrstuv', 17 , 32 ],
			['23456789abcdefghijklmn', 2 , 24 ],
			['9abcdefghijklmnop', 9 , 26 ],
			['abcdefghijklmnopqrstuvwxy', 10 , 35 ],
			['789abcdefghijkl', 7 , 22 ],
			['efghijkl', 14 , 22 ],
			['ghijklmnopqrstu', 16 , 31 ],
			['23456789abcdefghi', 2 , 19 ],
			['hijklmnopqrstuvwxyz', 17 , 36 ],
			['789abcdefghijklmnopqrst', 7 , 30 ],
			['efghijklm', 14 , 23 ],
			['56789abcdefghijklmnopqrstu', 5 , 31 ],
			['fghijklmnopqrstu', 15 , 31 ],
			['ghijklmnopqrstuvwxyz', 16 , 36 ],
			['456789abcdefgh', 4 , 18 ],
			['6789abcdefgh', 6 , 18 ],
			['hi', 17 , 19 ],
			['fghijklmnopqrstuvwxy', 15 , 35 ],
			['789abcdefghij', 7 , 20 ],
			['0123456789abcdefghijklmn', 0 , 24 ],
			['fghijklm', 15 , 23 ],
			['defghijklmn', 13 , 24 ],
			['6789abcdefghijkl', 6 , 22 ],
			['h', 17 , 18 ],
			['6789abcdefghi', 6 , 19 ],
			['abcdefghijklm', 10 , 23 ],
			['23456789abcdefghijklmnopqrstu', 2 , 31 ],
			['bcdefghijkl', 11 , 22 ],
			['efghijklmnopqrstuvwx', 14 , 34 ],
			['89abcdefghijkl', 8 , 22 ],
			['bcdefghijkl', 11 , 22 ],
			['abcdefgh', 10 , 18 ],
			['ghijklmnopqrs', 16 , 29 ],
			['3456789abcdefghijklmnopqrstuvwxyz', 3 , 36 ],
			['6789abcdefghijklmnop', 6 , 26 ],
			['fghijklmnopqrs', 15 , 29 ],
			['123456789abcdefghijklmnopqrstuvwxy', 1 , 35 ],
			['89abcdefgh', 8 , 18 ],
			['fgh', 15 , 18 ],
			['abcdefgh', 10 , 18 ],
			['6789abcdefghijkl', 6 , 22 ],
			['56789abcdefghijklmnopqrst', 5 , 30 ],
			['789abcdefghijklmnopqrstuvwxy', 7 , 35 ],
			['cdefghijklmnopqrstuvw', 12 , 33 ],
			['456789abcdefghijklmnopqrstuvw', 4 , 33 ],
			['cdefghijklm', 12 , 23 ],
			['9abcdefghijklmno', 9 , 25 ],
			['123456789abcdefghi', 1 , 19 ],
			['9abcdefghijk', 9 , 21 ],
			['bcdefghijklmnopqr', 11 , 28 ],
			['fghijklmnopqrstuvwxy', 15 , 35 ],
			['56789abcdefghijklmnopqr', 5 , 28 ],
			['23456789abcdefghijklmnopqrstuvwx', 2 , 34 ],
			['23456789abcdefghijk', 2 , 21 ],
			['6789abcdefghijklmnopqrs', 6 , 29 ],
			['456789abcdefghijklmno', 4 , 25 ],
			['defghijklmnopqr', 13 , 28 ],
			['789abcdefghijklmnop', 7 , 26 ],
			['fghijklm', 15 , 23 ],
			['abcdefghijklmnop', 10 , 26 ],
			['9abcdefghijklm', 9 , 23 ],
			['789abcdefghijklmno', 7 , 25 ],
			['23456789abcdefghijklmnopq', 2 , 27 ],
		];
	}
}