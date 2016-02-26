<?php
/**
 * Color Calculations class for Kirki
 * Initially built for the Shoestrap-3 theme and then tweaked for Kirki.
 *
 * @package     Kirki
 * @category    Core
 * @author      Aristeides Stathopoulos
 * @copyright   Copyright (c) 2015, Aristeides Stathopoulos
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Kirki_WP_Color' ) ) {
	class Kirki_WP_Color {

		public static $instances = array();

		public $color;
		public $mode = 'hex';

		public $word_colors = array();

		public $hex;

		public $red   = 0;
		public $green = 0;
		public $blue  = 0;
		public $alpha = 1;

		public $hue;
		public $saturation;
		public $lightness;
		public $chroma;

		public $brightness = array();

		/**
		 * The class constructor
		 */
		public function __construct( $color = '', $mode = 'auto' ) {
			$this->color = $color;
			$this->mode  = $mode;
			if ( method_exists( $this, 'from_' . $mode ) ) {
				$method = 'from_' . $mode;
			} else {
				if ( null === $this->get_mode( $color ) ) {
					return;
				}
				$this->mode = $this->get_mode( $color );
				$method = 'from_' . $this->mode;
			}
			$this->$method();
		}

		public static function get_instance( $color, $mode = 'auto' ) {
			if ( is_array( $color ) ) {
				$color_md5 = md5( json_encode( $color ) . $mode );
			} else {
				$color_md5 = md5( $color . $mode );
			}
			if ( ! isset( self::$instances[ $color_md5 ] ) ) {
				self::$instances[ $color_md5 ] = new self( $color, $mode );
			}
			return self::$instances[ $color_md5 ];
		}

		public function get_new_object_by( $property = '', $value = '' ) {
			if ( in_array( $property, array( 'red', 'green', 'blue', 'alpha' ) ) ) {
				$this->$property = $value;
				return self::get_instance( 'rgba(' . $this->red . ',' . $this->green . ',' . $this->blue . ',' . $this->alpha . ')' );
			}
			if ( 'brightness' == $property ) {
				if ( $value < $this->brightness['total'] ) {
					$this->red   = max( 0, min( 255, $this->red - ( $this->brightness['total'] - $value ) ) );
					$this->green = max( 0, min( 255, $this->green - ( $this->brightness['total'] - $value ) ) );
					$this->blue  = max( 0, min( 255, $this->blue - ( $this->brightness['total'] - $value ) ) );
				} elseif ( $value > $this->brightness['total'] ) {
					$this->red   = max( 0, min( 255, $this->red + ( $value - $this->brightness['total'] ) ) );
					$this->green = max( 0, min( 255, $this->green + ( $value - $this->brightness['total'] ) ) );
					$this->blue  = max( 0, min( 255, $this->blue + ( $value - $this->brightness['total'] ) ) );
				} else {
					// if it's not smaller and it's not greater, then it's equal.
					return $this;
				}
				return self::get_instance( 'rgba(' . $this->red . ',' . $this->green . ',' . $this->blue . ',' . $this->alpha . ')' );
			}
		}

		public function get_mode( $color ) {
			if ( is_array( $color ) ) {
				if ( isset( $color['rgba'] ) ) {
					$this->color = $color['rgba'];
					return 'rgba';
				} elseif ( isset( $color['color'] ) ) {
					$this->color = $color['color'];
					return 'hex';
				} else {
					if ( 4 == count( $color ) && isset( $color[0] ) && isset( $color[1] ) && isset( $color[2] ) && isset( $color[3] ) ) {
						$this->color = 'rgba(' . intval( $color[0] ) . ',' . intval( $color[1] ) . ',' . intval( $color[2] ) . ',' . intval( $color[3] ) . ')';
						return 'rgba';
					} elseif ( 3 == count( $color ) && isset( $color[0] ) && isset( $color[1] ) && isset( $color[2] ) ) {
						$this->color = 'rgb(' . intval( $color[0] ) . ',' . intval( $color[1] ) . ',' . intval( $color[2] ) . ')';
					}
					$finders_keepers = array(
						'r'       => 'red',
						'g'       => 'green',
						'b'       => 'blue',
						'a'       => 'alpha',
						'red'     => 'red',
						'green'   => 'green',
						'blue'    => 'blue',
						'alpha'   => 'alpha',
						'opacity' => 'alpha',
					);
					$found = false;
					foreach( $finders_keepers as $finder => $keeper ) {
						if ( isset( $color[ $finder ] ) ) {
							$found = true;
							$this->$keeper = $color[ $finder ];
						}
					}
					if ( ! $found ) {
						return null;
					}
					$this->color = 'rgba(' . $this->red . ',' . $this->green . ',' . $this->blue . ',' . $this->alpha . ')';
					return 'rgba';
				}
			} else {
				$finders_keepers = array(
					'#'    => 'hex',
					'rgba' => 'rgba',
					'rgb'  => 'rgb',
					'hsla' => 'hsla',
					'hsl'  => 'hsl',
				);
				foreach ( $finders_keepers as $finder => $keeper ) {
					if ( false !== strrpos( $color, $finder ) ) {
						return $keeper;
					}
				}
			}
			return 'hex';
		}

		/**
		 * Sets the class properties
		 * using a hex color as base for all calculations.
		 */
		public function from_hex() {

			if ( ! function_exists( 'sanitize_hex_color' ) ) {
				require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
			}
			// Is this perhaps a word-color?
			$word_colors = $this->get_word_colors();
			if ( array_key_exists( $this->color, $word_colors ) ) {
				$this->color = '#' . $word_colors[ $this->color ];
			}
			// Sanitize color
			$this->hex = maybe_hash_hex_color( $this->color );
			$this->hex = sanitize_hex_color( $this->hex );

			$hex = ltrim( $this->hex, '#' );
			// Make sure we have 6 digits for the below calculations
			if ( 3 == strlen( $hex ) ) {
				$hex = ltrim( $this->hex, '#' );
				$hex = substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) . substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) . substr( $hex, 2, 1 ) . substr( $hex, 2, 1 );
			}
			$this->red   = hexdec( substr( $hex, 0, 2 ) );
			$this->green = hexdec( substr( $hex, 2, 2 ) );
			$this->blue  = hexdec( substr( $hex, 4, 2 ) );
			$this->alpha = 1;

			$this->set_brightness();
			$this->set_hsl();

		}

		/**
		 * Sets the class properties
		 * using an rgba color as base for all calculations.
		 */
		public function from_rgb() {
			$value = explode( ',', str_replace( array( ' ', 'rgb', '(', ')' ), '', $this->color ) );
			$this->red   = ( isset( $value[0] ) ) ? intval( $value[0] ) : 255;
			$this->green = ( isset( $value[1] ) ) ? intval( $value[1] ) : 255;
			$this->blue  = ( isset( $value[2] ) ) ? intval( $value[2] ) : 255;
			$this->alpha = 1;
			$this->color = 'rgba(' . $this->red . ',' . $this->green . ',' . $this->blue . ',' . $this->alpha . ')';
			$this->from_rgba();
		}

		/**
		 * Sets the class properties
		 * using an rgba color as base for all calculations.
		 */
		public function from_rgba() {
			// Set r, g, b, a properties
			$value = explode( ',', str_replace( array( ' ', 'rgba', '(', ')' ), '', $this->color ) );
			$this->red   = ( isset( $value[0] ) ) ? intval( $value[0] ) : 255;
			$this->green = ( isset( $value[1] ) ) ? intval( $value[1] ) : 255;
			$this->blue  = ( isset( $value[2] ) ) ? intval( $value[2] ) : 255;
			$this->alpha = ( isset( $value[3] ) ) ? filter_var( $value[3], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) : 1;
			// limit minimum values
			$this->red   = ( 0 > $this->red ) ? 0 : $this->red;
			$this->green = ( 0 > $this->green ) ? 0 : $this->green;
			$this->blue  = ( 0 > $this->blue ) ? 0 : $this->blue;
			$this->alpha = ( 0 > $this->alpha ) ? 0 : $this->alpha;
			// limit maximum values
			$this->red   = ( 255 < $this->red ) ? 255 : $this->red;
			$this->green = ( 255 < $this->green ) ? 255 : $this->green;
			$this->blue  = ( 255 < $this->blue ) ? 255 : $this->blue;
			$this->alpha = ( 1 < $this->alpha ) ? 1 : $this->alpha;

			$this->hex = $this->rgb_to_hex( $this->red, $this->green, $this->blue );

			$this->set_brightness();
			$this->set_hsl();
		}

		/**
		 * Convert to hex using red, green, blue values
		 */
		public function rgb_to_hex( $red, $green, $blue ) {
			// get hex values
			$hex_red   = ( 9 >= $this->red ) ? '0' . $this->red : dechex( $this->red );
			$hex_green = ( 9 >= $this->green ) ? '0' . $this->green : dechex( $this->green );
			$hex_blue  = ( 9 >= $this->blue ) ? '0' . $this->blue : dechex( $this->blue );
			// make sure all hex values are 2 digits
			$hex_red   = ( 1 == strlen( $hex_red ) ) ? $hex_red . $hex_red : $hex_red;
			$hex_green = ( 1 == strlen( $hex_green ) ) ? $hex_green . $hex_green : $hex_green;
			$hex_blue  = ( 1 == strlen( $hex_blue ) ) ? $hex_blue . $hex_blue : $hex_blue;
			// set the hex property of the class
			return '#' . $hex_red . $hex_green . $hex_blue;
		}

		/**
		 * Sets the class properties
		 * using an hsl color as base for all calculations
		 */
		public function from_hsl() {
			$value = explode( ',', str_replace( array( ' ', 'hsl', '(', ')', '%' ), '', $this->color ) );
			$this->hue        = $value[0];
			$this->saturation = $value[1];
			$this->lightness  = $value[2];
			$this->from_hsl_array();
		}

		/**
		 * Sets the class properties
		 * using an hsla color as base for all calculations
		 */
		public function from_hsla() {
			$value = explode( ',', str_replace( array( ' ', 'hsla', '(', ')', '%' ), '', $this->color ) );
			$this->hue        = $value[0];
			$this->saturation = $value[1];
			$this->lightness  = $value[2];
			$this->alpha      = $value[3];
			$this->from_hsl_array();
		}

		public function from_hsl_array() {
			$h = $this->hue /360;
			$s = $this->saturation / 100;
			$l = $this->lightness /100;

			$r = $l;
			$g = $l;
			$b = $l;
			$v = ( $l <= 0.5 ) ? ( $l * ( 1.0 + $s ) ) : ( $l + $s - $l * $s );
			if ( $v > 0 ) {
				$m = $l + $l - $v;
				$sv = ( $v - $m ) / $v;
				$h *= 6.0;
				$sextant = floor( $h );
				$fract = $h - $sextant;
				$vsf = $v * $sv * $fract;
				$mid1 = $m + $vsf;
				$mid2 = $v - $vsf;
				switch ( $sextant ) {
					case 0:
						$r = $v;
						$g = $mid1;
						$b = $m;
						break;
					case 1:
						$r = $mid2;
						$g = $v;
						$b = $m;
						break;
					case 2:
						$r = $m;
						$g = $v;
						$b = $mid1;
						break;
					case 3:
						$r = $m;
						$g = $mid2;
						$b = $v;
						break;
					case 4:
						$r = $mid1;
						$g = $m;
						$b = $v;
						break;
					case 5:
						$r = $v;
						$g = $m;
						$b = $mid2;
						break;
				}
			}
			$this->red   = round( $r * 255, 0 );
			$this->green = round( $g * 255, 0 );
			$this->blue  = round( $b * 255, 0 );

			$this->hex = $this->rgb_to_hex( $this->red, $this->green, $this->blue );
		}

		/**
		 * Returns a CSS-formatted value for colors.
		 *
		 * @param $mode string
		 * @return string
		 */
		public function get_css( $mode = 'hex' ) {

			$value = '';

			switch ( $mode ) {
				case 'hex':
					$value = strtoupper( $this->hex );
					break;
				case 'rgba':
					$value = 'rgba(' . $this->red . ',' . $this->green . ',' . $this->blue . ',' . $this->alpha . ')';
					break;
				case 'rgb':
					$value = 'rgb(' . $this->red . ',' . $this->green . ',' . $this->blue . ')';
					break;
				case 'hsl':
					$value = 'hsl(' . $this->hue . ',' . round( $this->saturation ) . '%,' . round( $this->lightness ) . '%)';
					break;
				case 'hsla':
					$value = 'hsla(' . $this->hue . ',' . round( $this->saturation ) . '%,' . round( $this->lightness ) . '%,' . $this->alpha . ')';
					break;
			}
			return $value;
		}

		public function set_hsl() {
			$red   = $this->red / 255;
			$green = $this->green / 255;
			$blue  = $this->blue / 255;

			$max = max( $red, $green, $blue );
			$min = min( $red, $green, $blue );

			$lightness  = ( $max + $min ) / 2;
			$difference = $max - $min;

			if ( 0 == $difference ) {
				$hue = $saturation = 0; // achromatic
			} else {
				$saturation = $difference / ( 1 - abs( 2 * $lightness - 1 ) );
				switch ( $max ) {
					case $red:
						$hue = 60 * fmod( ( ( $green - $blue ) / $difference ), 6 );
						if ( $blue > $green ) {
							$hue += 360;
						}
						break;
					case $green:
						$hue = 60 * ( ( $blue - $red ) / $difference + 2 );
						break;
					case $blue:
						$hue = 60 * ( ( $red - $green ) / $difference + 4 );
						break;
				}
			}

			$this->hue        = round( $hue );
			$this->saturation = round( $saturation * 100 );
			$this->lightness  = round( $lightness * 100 );
		}

		public function set_brightness() {
			$this->brightness = array(
				'red'   => round( $this->red * .299 ),
				'green' => round( $this->green * .587 ),
				'blue'  => round( $this->blue * .114 ),
				'total' => intval( ( $this->red * .299 ) + ( $this->green * .587 ) + ( $this->blue * .114 ) )
			);
		}

		public function get_word_colors() {
			return array(
				'aliceblue'            => 'F0F8FF',
				'antiquewhite'         => 'FAEBD7',
				'aqua'                 => '00FFFF',
				'aquamarine'           => '7FFFD4',
				'azure'                => 'F0FFFF',
				'beige'                => 'F5F5DC',
				'bisque'               => 'FFE4C4',
				'black'                => '000000',
				'blanchedalmond'       => 'FFEBCD',
				'blue'                 => '0000FF',
				'blueviolet'           => '8A2BE2',
				'brown'                => 'A52A2A',
				'burlywood'            => 'DEB887',
				'cadetblue'            => '5F9EA0',
				'chartreuse'           => '7FFF00',
				'chocolate'            => 'D2691E',
				'coral'                => 'FF7F50',
				'cornflowerblue'       => '6495ED',
				'cornsilk'             => 'FFF8DC',
				'crimson'              => 'DC143C',
				'cyan'                 => '00FFFF',
				'darkblue'             => '00008B',
				'darkcyan'             => '008B8B',
				'darkgoldenrod'        => 'B8860B',
				'darkgray'             => 'A9A9A9',
				'darkgreen'            => '006400',
				'darkgrey'             => 'A9A9A9',
				'darkkhaki'            => 'BDB76B',
				'darkmagenta'          => '8B008B',
				'darkolivegreen'       => '556B2F',
				'darkorange'           => 'FF8C00',
				'darkorchid'           => '9932CC',
				'darkred'              => '8B0000',
				'darksalmon'           => 'E9967A',
				'darkseagreen'         => '8FBC8F',
				'darkslateblue'        => '483D8B',
				'darkslategray'        => '2F4F4F',
				'darkslategrey'        => '2F4F4F',
				'darkturquoise'        => '00CED1',
				'darkviolet'           => '9400D3',
				'deeppink'             => 'FF1493',
				'deepskyblue'          => '00BFFF',
				'dimgray'              => '696969',
				'dimgrey'              => '696969',
				'dodgerblue'           => '1E90FF',
				'firebrick'            => 'B22222',
				'floralwhite'          => 'FFFAF0',
				'forestgreen'          => '228B22',
				'fuchsia'              => 'FF00FF',
				'gainsboro'            => 'DCDCDC',
				'ghostwhite'           => 'F8F8FF',
				'gold'                 => 'FFD700',
				'goldenrod'            => 'DAA520',
				'gray'                 => '808080',
				'green'                => '008000',
				'greenyellow'          => 'ADFF2F',
				'grey'                 => '808080',
				'honeydew'             => 'F0FFF0',
				'hotpink'              => 'FF69B4',
				'indianred'            => 'CD5C5C',
				'indigo'               => '4B0082',
				'ivory'                => 'FFFFF0',
				'khaki'                => 'F0E68C',
				'lavender'             => 'E6E6FA',
				'lavenderblush'        => 'FFF0F5',
				'lawngreen'            => '7CFC00',
				'lemonchiffon'         => 'FFFACD',
				'lightblue'            => 'ADD8E6',
				'lightcoral'           => 'F08080',
				'lightcyan'            => 'E0FFFF',
				'lightgoldenrodyellow' => 'FAFAD2',
				'lightgray'            => 'D3D3D3',
				'lightgreen'           => '90EE90',
				'lightgrey'            => 'D3D3D3',
				'lightpink'            => 'FFB6C1',
				'lightsalmon'          => 'FFA07A',
				'lightseagreen'        => '20B2AA',
				'lightskyblue'         => '87CEFA',
				'lightslategray'       => '778899',
				'lightslategrey'       => '778899',
				'lightsteelblue'       => 'B0C4DE',
				'lightyellow'          => 'FFFFE0',
				'lime'                 => '00FF00',
				'limegreen'            => '32CD32',
				'linen'                => 'FAF0E6',
				'magenta'              => 'FF00FF',
				'maroon'               => '800000',
				'mediumaquamarine'     => '66CDAA',
				'mediumblue'           => '0000CD',
				'mediumorchid'         => 'BA55D3',
				'mediumpurple'         => '9370D0',
				'mediumseagreen'       => '3CB371',
				'mediumslateblue'      => '7B68EE',
				'mediumspringgreen'    => '00FA9A',
				'mediumturquoise'      => '48D1CC',
				'mediumvioletred'      => 'C71585',
				'midnightblue'         => '191970',
				'mintcream'            => 'F5FFFA',
				'mistyrose'            => 'FFE4E1',
				'moccasin'             => 'FFE4B5',
				'navajowhite'          => 'FFDEAD',
				'navy'                 => '000080',
				'oldlace'              => 'FDF5E6',
				'olive'                => '808000',
				'olivedrab'            => '6B8E23',
				'orange'               => 'FFA500',
				'orangered'            => 'FF4500',
				'orchid'               => 'DA70D6',
				'palegoldenrod'        => 'EEE8AA',
				'palegreen'            => '98FB98',
				'paleturquoise'        => 'AFEEEE',
				'palevioletred'        => 'DB7093',
				'papayawhip'           => 'FFEFD5',
				'peachpuff'            => 'FFDAB9',
				'peru'                 => 'CD853F',
				'pink'                 => 'FFC0CB',
				'plum'                 => 'DDA0DD',
				'powderblue'           => 'B0E0E6',
				'purple'               => '800080',
				'red'                  => 'FF0000',
				'rosybrown'            => 'BC8F8F',
				'royalblue'            => '4169E1',
				'saddlebrown'          => '8B4513',
				'salmon'               => 'FA8072',
				'sandybrown'           => 'F4A460',
				'seagreen'             => '2E8B57',
				'seashell'             => 'FFF5EE',
				'sienna'               => 'A0522D',
				'silver'               => 'C0C0C0',
				'skyblue'              => '87CEEB',
				'slateblue'            => '6A5ACD',
				'slategray'            => '708090',
				'slategrey'            => '708090',
				'snow'                 => 'FFFAFA',
				'springgreen'          => '00FF7F',
				'steelblue'            => '4682B4',
				'tan'                  => 'D2B48C',
				'teal'                 => '008080',
				'thistle'              => 'D8BFD8',
				'tomato'               => 'FF6347',
				'turquoise'            => '40E0D0',
				'violet'               => 'EE82EE',
				'wheat'                => 'F5DEB3',
				'white'                => 'FFFFFF',
				'whitesmoke'           => 'F5F5F5',
				'yellow'               => 'FFFF00',
				'yellowgreen'          => '9ACD32'
			);

		}

	}
}

function kirki_wp_color( $color = '' ) {
	return Kirki_WP_Color::get_instance( $color );
}