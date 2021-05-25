<?php
namespace Piggly\Decimal;

/**
 * Handle all decimal global properties.
 * 
 * DecimalConfig is a static instance which can be 
 * called by instance() method.
 * 
 * When set any properties to global instance, it
 * will applies properties to all Decimal classes
 * associated to global instance.
 * 
 * You may set a Decimal and its heirs with a custom
 * DecimalConfig, you can do it:
 * 
 * First, cloning the global instance:
 * 	DecimalConfig::clone($props);
 * 
 * Then, setting it to Decimal object:
 * 	new Decimal($num, $config)
 * 
 * All methods of object above will inherit its settings
 * instead of global settings.
 * 
 * Anytime you change instance() properties, it will
 * reflect to all methods executed after. * 
 *
 * @since 1.0.0
 * @package Piggly\Decimal
 * @subpackage Piggly\Decimal
 * @author Caique Araujo <caique@piggly.com.br>
 */
class DecimalConfig
{
	/**
	 * Global Decimal
	 * configuration instance
	 *
	 * @var DecimalConfig
	 * @since 1.0.0
	 */
	private static $_instance;

	/** Prevent to construct it outside this class */
	private function __construct () {}

	/**
	 * Get global Decimal configuration instance.
	 *
	 * @since 1.0.0
	 * @return DecimalConfig
	 */
	public static function instance () : DecimalConfig
	{
		if ( !(static::$_instance instanceof DecimalConfig) )
		{ static::$_instance = new DecimalConfig(); }

		return static::$_instance;
	}

	/**
	 * Clone current global Decimal configuration instance.
	 *
	 * @param array $props Overwrite new props.
	 * @since 1.0.0
	 * @return DecimalConfig
	 */
	public static function clone ( array $props = [] ) : DecimalConfig
	{
		$config = clone static::instance();
		return $config->set($props);
	}

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
	 * The maximum exponent magnitude.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const EXP_LIMIT = 9e15;

	/**
	 * The limit on the value of `precision`.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const MAX_DIGITS = 1e9;

	/**
	 * Base conversion alphabet.
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const NUMERALS = '0123456789abcdef';

	/**
	 * The natural logarithm of 10 (1025 digits).
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const LN10 = '2.3025850929940456840179914546843642076011014886287729760333279009675726096773524802359972050895982983419677840422862486334095254650828067566662873690987816894829072083255546808437998948262331985283935053089653777326288461633662222876982198867465436674744042432743651550489343149393914796194044002221051017141748003688084012647080685567743216228355220114804663715659121373450747856947683463616792101806445070648000277502684916746550586856935673420670581136429224554405758925724208241314695689016758940256776311356919292033376587141660230105703089634572075440370847469940168269282808481184289314848524948644871927809676271275775397027668605952496716674183485704422507197965004714951050492214776567636938662976979522110718264549734772662425709429322582798502585509785265383207606726317164309505995087807523710333101197857547331541421808427543863591778117054309827482385045648019095610299291824318237525357709750539565187697510374970888692180205189339507238539205144634197265287286965110862571492198849978748873771345686209167058';

	/**
	 * The natural logarithm precision.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const LN10_PRECISION = 1025;

	/**
	 * Pi Number (1025 digits).
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const PI = '3.1415926535897932384626433832795028841971693993751058209749445923078164062862089986280348253421170679821480865132823066470938446095505822317253594081284811174502841027019385211055596446229489549303819644288109756659334461284756482337867831652712019091456485669234603486104543266482133936072602491412737245870066063155881748815209209628292540917153643678925903600113305305488204665213841469519415116094330572703657595919530921861173819326117931051185480744623799627495673518857527248912279381830119491298336733624406566430860213949463952247371907021798609437027705392171762931767523846748184676694051320005681271452635608277857713427577896091736371787214684409012249534301465495853710507922796892589235420199561121290219608640344181598136297747713099605187072113499999983729780499510597317328160963185950244594553469083026425223082533446850352619311881710100031378387528865875332083814206171776691473035982534904287554687311595628638823537875937519577818577805321712268066130019278766111959092164201989380952572010654858632789';

	/**
	 * The PI number precision.
	 * 
	 * @var integer
	 * @since 1.0.0
	 */
	const PI_PRECISION = 1025;

	/**
	 * The maximum number of significant 
	 * digits of the result of an operation.
	 *
	 * @var integer 1 to 1e9
	 * @since 1.0.0
	 */
	public $precision = 20;

	/**
	 * The default rounding mode used when 
	 * rounding the result of an operation 
	 * to precision significant digits, 
	 * and when rounding the return value.
	 *
	 * @var integer 0 to 8
	 * @since 1.0.0
	 */
	public $rounding = self::ROUND_HALF_UP;

	/**
	 * The negative exponent value when 
	 * returns exponential notation.
	 *
	 * @var integer -9e15 to 0
	 * @since 1.0.0
	 */
	public $toExpNeg = -7;
	
	/**
	 * The positive exponent value when 
	 * returns exponential notation.
	 *
	 * @var integer 0 to 9e15
	 * @since 1.0.0
	 */
	public $toExpPos = 21;

	/**
	 * The negative exponent limit, i.e. 
	 * the exponent value below which 
	 * underflow to zero occurs.
	 *
	 * @var integer -9e15 to 0
	 * @since 1.0.0
	 */
	public $minE = -self::EXP_LIMIT;

	/**
	 * The positive exponent limit, i.e. 
	 * the exponent value above which 
	 * overflow to Infinity occurs.
	 *
	 * @var integer 0 to 9e15
	 * @since 1.0.0
	 */
	public $maxE = self::EXP_LIMIT;

	/**
	 * The value that determines whether 
	 * cryptographically-secure pseudo-random 
	 * number generation is used.
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	public $crypto;

	/**
	 * The modulo mode used when calculating 
	 * the modulus: a mod n.
	 *
	 * @var integer 0 to 9
	 * @since 1.0.0
	 */
	public $modulo = self::ROUND_DOWN;

	/**
	 * Set all properties once.
	 *
	 * @param array $props
	 * @since 1.0.0
	 * @return DecimalConfig
	 */
	public function set ( array $props )
	{
		foreach ( $props as $prop => $value )
		{
			if ( \property_exists($this,$prop) )
			{ $this->{$prop} = $value; }
		}

		return $this;
	}
}