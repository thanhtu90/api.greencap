<?php
/**
 * Created by PhpStorm.
 * User: thanhtu
 * Date: 12/25/2016
 * Time: 2:57 PM
 */
function sanitize_user($username, $strict = false)
{
    $username = wp_strip_all_tags($username);
    $username = remove_vietnamese_char($username);
    // Kill octets
    $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
    $username = preg_replace('/&.+?;/', '', $username); // Kill entities

    // If strict, reduce to ASCII for max portability.
    if ($strict) {
        $username = preg_replace('|[^a-z0-9 _.\-@]|i', '', $username);
    }

    $username = trim($username);
    // Consolidate contiguous whitespace
    $username = preg_replace('|\s+|', ' ', $username);

    return $username;
}

function wp_strip_all_tags($string, $remove_breaks = false)
{
    $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
    $string = strip_tags($string);

    if ($remove_breaks) {
        $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
    }

    return trim($string);
}

/**
 * Gỡ các ký tự việt nam ra khỏi chuỗi
 *
 * @param $string
 *
 * @return mixed
 */
function remove_vietnamese_char($string)
{
    $default = array(
        '/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ|å/' => 'a',
        '/À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ/' => 'A',
        '/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ|ë/' => 'e',
        '/È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ/' => 'E',
        '/ì|í|ị|ỉ|ĩ|î/' => 'i',
        '/Ì|Í|Ị|Ỉ|Ĩ/' => 'I',
        '/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ|ø/' => 'o',
        '/Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ/' => 'O',
        '/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ|ů|û/' => 'u',
        '/Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ/' => 'U',
        '/ỳ|ý|ỵ|ỷ|ỹ/' => 'y',
        '/Ỳ|Ý|Ỵ|Ỷ|Ỹ/' => 'Y',
        '/đ/' => 'd',
        '/Đ/' => 'D',
        '/ç/' => 'c',
        '/ñ/' => 'n',
        '/ä|æ/' => 'ae',
        '/ö/' => 'oe',
        '/ü/' => 'ue',
        '/Ä/' => 'Ae',
        '/Ü/' => 'Ue',
        '/Ö/' => 'Oe',
        '/ß/' => 'ss'
    );

    return preg_replace(array_keys($default), array_values($default), $string);
}

function wp_html_split($input)
{
    static $regex;

    if (!isset($regex)) {
        $comments =
            '!'           // Start of comment, after the <.
            . '(?:'         // Unroll the loop: Consume everything until --> is found.
            . '-(?!->)' // Dash not followed by end of comment.
            . '[^\-]*+' // Consume non-dashes.
            . ')*+'         // Loop possessively.
            . '(?:-->)?';   // End of comment. If not found, match all input.

        $cdata =
            '!\[CDATA\['  // Start of comment, after the <.
            . '[^\]]*+'     // Consume non-].
            . '(?:'         // Unroll the loop: Consume everything until ]]> is found.
            . '](?!]>)' // One ] not followed by end of comment.
            . '[^\]]*+' // Consume non-].
            . ')*+'         // Loop possessively.
            . '(?:]]>)?';   // End of comment. If not found, match all input.

        $regex =
            '/('              // Capture the entire match.
            . '<'           // Find start of element.
            . '(?(?=!--)'   // Is this a comment?
            . $comments // Find end of comment.
            . '|'
            . '(?(?=!\[CDATA\[)' // Is this a comment?
            . $cdata // Find end of comment.
            . '|'
            . '[^>]*>?' // Find end of element. If not found, match all input.
            . ')'
            . ')'
            . ')/s';
    }

    return preg_split($regex, $input, -1, PREG_SPLIT_DELIM_CAPTURE);
}

function sanitize_html_class($class, $fallback = '')
{
    //Strip out any % encoded octets
    $sanitized = preg_replace('|%[a-fA-F0-9][a-fA-F0-9]|', '', $class);

    //Limit to A-Z,a-z,0-9,_,-
    $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '', $sanitized);

    if ('' == $sanitized)
        $sanitized = $fallback;

    /**
     * Filter a sanitized HTML class string.
     *
     * @since 2.8.0
     *
     * @param string $sanitized The sanitized HTML class.
     * @param string $class HTML class before sanitization.
     * @param string $fallback The fallback string.
     */
    return $sanitized;
}

function esc_attr( $text ) {
    $safe_text = wp_check_invalid_utf8( $text );
    $safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
    /**
     * Filter a string cleaned and escaped for output in an HTML attribute.
     *
     * Text passed to esc_attr() is stripped of invalid or special characters
     * before output.
     *
     * @since 2.0.6
     *
     * @param string $safe_text The text after it has been escaped.
     * @param string $text      The text prior to being escaped.
     */

    return $safe_text;
}

function wp_check_invalid_utf8( $string, $strip = false ) {
    $string = (string) $string;

    if ( 0 === strlen( $string ) ) {
        return '';
    }

    // Store the site charset as a static to avoid multiple calls to get_option()
    /*static $is_utf8 = null;
    if ( ! isset( $is_utf8 ) ) {
        $is_utf8 = in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) );
    }
    if ( ! $is_utf8 ) {
        return $string;
    }*/

    // Check for support for utf8 in the installed PCRE library once and store the result in a static
    static $utf8_pcre = null;
    if ( ! isset( $utf8_pcre ) ) {
        $utf8_pcre = @preg_match( '/^./u', 'a' );
    }
    // We can't demand utf8 in the PCRE installation, so just return the string in those cases
    if ( !$utf8_pcre ) {
        return $string;
    }

    // preg_match fails when it encounters invalid UTF8 in $string
    if ( 1 === @preg_match( '/^./us', $string ) ) {
        return $string;
    }

    // Attempt to strip the bad chars if requested (not recommended)
    if ( $strip && function_exists( 'iconv' ) ) {
        return iconv( 'utf-8', 'utf-8', $string );
    }

    return '';
}

function _wp_specialchars( $string, $quote_style = ENT_NOQUOTES, $charset = 'UTF-8', $double_encode = false ) {
    $string = (string) $string;

    if ( 0 === strlen( $string ) )
        return '';

    // Don't bother if there are no specialchars - saves some processing
    if ( ! preg_match( '/[&<>"\']/', $string ) )
        return $string;

    // Account for the previous behaviour of the function when the $quote_style is not an accepted value
    if ( empty( $quote_style ) )
        $quote_style = ENT_NOQUOTES;
    elseif ( ! in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), true ) )
        $quote_style = ENT_QUOTES;

    if ( in_array( $charset, array( 'utf8', 'utf-8', 'UTF8' ) ) )
        $charset = 'UTF-8';

    $_quote_style = $quote_style;

    if ( $quote_style === 'double' ) {
        $quote_style = ENT_COMPAT;
        $_quote_style = ENT_COMPAT;
    } elseif ( $quote_style === 'single' ) {
        $quote_style = ENT_NOQUOTES;
    }

    if ( ! $double_encode ) {
        // Guarantee every &entity; is valid, convert &garbage; into &amp;garbage;
        // This is required for PHP < 5.4.0 because ENT_HTML401 flag is unavailable.
        $string = wp_kses_normalize_entities( $string );
    }
    $string = @htmlspecialchars( $string, $quote_style, $charset, $double_encode );

    // Backwards compatibility
    if ( 'single' === $_quote_style )
        $string = str_replace( "'", '&#039;', $string );

    return $string;
}
function cut_tring_by_char($string, $max_length)
{
    if (mb_strlen($string, "UTF-8") > $max_length) {
        $max_length = $max_length - 3;
        $string = mb_substr($string, 0, $max_length, "UTF-8");
        $pos = strrpos($string, " ");
        if ($pos === false) {
            return substr($string, 0, $max_length) . "...";
        }
        return substr($string, 0, $pos) . "...";
    } else {
        return $string;
    }
}

function valueOrNull(&$value, $default_data = null)
{
    return empty( $value ) ? $default_data : $value;
}