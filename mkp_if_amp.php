/**
 * mkp_if_amp plugin. Support for Google AMP with Textpattern CMS.
 * @author:  Michael K Pate & Patrick LEFEVRE.
 * @link:    https://github.com/michaelkpate/mkp_if_amp, https://github.com/cara-tm/mkp_if_amp
 * @type:    Public
 * @prefs:   no
 * @order:   5
 * @version: 0.2.0
 * @license: GPLv2
*/

/**
 * This plugin tag registry
 */
if (class_exists('\Textpattern\Tag\Registry')) {
Txp::get('\Textpattern\Tag\Registry')
->register('mkp_if_amp')
;
}

/**
 * Main plugin function
 *
 * @param  $atts   string This plugin attributes
 * @param  $thing  string
 * @return string 
 */
function mkp_if_amp($atts, $thing='')
{

	global $variable;

	// Initiates a TXP variable which sniffs for 'amp' (with or without a final backslash) in URLs or a simple query '?amp'
	$variable['amp'] = ( preg_match( '/amp/',  $GLOBALS['pretext']['request_uri'] ) || !empty(gps('amp')) ? true : false );

	// Splits URL parts into a 5 max keys array
	$parts = explode('/', preg_replace("|^https?://[^/]+|i", "", $GLOBALS['pretext']['request_uri']), 5);

	// if the url ends in 'amp' this will return true; otherwise fals
	return (end($parts) == 'amp') ? parse(EvalElse($thing, true)) : parse(EvalElse($thing, false));
}
