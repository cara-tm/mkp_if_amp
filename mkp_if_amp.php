/**
 * mkp_if_amp plugin. Support for Google AMP with Textpattern CMS.
 * @author:  Michael K Pate & Patrick LEFEVRE.
 * @link:    https://github.com/michaelkpate/mkp_if_amp, https://github.com/cara-tm/mkp_if_amp
 * @type:    Public
 * @prefs:   no
 * @order:   5
 * @version: 3.2
 * @license: GPLv2
*/

/**
 * This plugin tag registry
 */
if (class_exists('\Textpattern\Tag\Registry')) {
	Txp::get('\Textpattern\Tag\Registry')
		->register('mkp_if_amp')
		->register('mkp_amp_redirect');
}


/*
 * Register callback when public pages are rendering.
 *
 */
if (txpinterface === 'public') {
	// Loads a callback with init function for public context. 
	register_callback('mkp_if_amp_init', 'textpattern');
}


/*
 * Init function which create the mkp_variable
 *
 * @param
 * @return boolean $variable 
 */
function mkp_if_amp_init()
{
	global $variable;

	$variable['mkp_amp'] = ( preg_match( '/amp/',  $GLOBALS['pretext']['request_uri'] ) || !empty(gps('amp')) ? 1 : 0 );

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
	$variable['mkp_amp'] = ( preg_match( '/amp/',  $GLOBALS['pretext']['request_uri'] ) || !empty(gps('amp')) ? 1 : 0 );

	// Splits URL parts into a 5 max keys array
	$parts = explode('/', preg_replace("|^https?://[^/]+|i", "", $GLOBALS['pretext']['request_uri']), 5);

	// if the url ends in 'amp' this will return true; otherwise fals
	return (end($parts) == 'amp') ? parse(EvalElse($thing, true)) : parse(EvalElse($thing, false));
}


/**
 *
 * Extracts the domain name and redirects to a subdomain
 *
 * @param: $atts array Plugin attribute
 * @return redirection or false
 */
function mkp_amp_redirect($atts)
{
	global $thisarticle;

	extract(lAtts(array(
		'url' 		=> hu,
		'subdomain'	=> 'amp',
		'permlink' 	=> false,
	), $atts));

	// Array of the URL
	$parts = parse_url($url);
	// Verify the host
	$domain = isset($parts['host']) ? $parts['host'] : '';

	// Regex for a well spelling domain name
	if( preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $matches) ) {
		// Redirects to subdomain without or with the current article's url title.
		return header( 'Location: '.$parts['scheme'].'://'.$subdomain.'.'.$matches['domain'].($permlink ? $thisarticle['url_title'] : '') );
	}
	// Otherwise, do nothing
	return false;
}
