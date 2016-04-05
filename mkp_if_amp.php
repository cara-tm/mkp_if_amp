// TXP 4.6 tag registration
if (class_exists('\Textpattern\Tag\Registry')) {
Txp::get('\Textpattern\Tag\Registry')
->register('mkp_if_amp')
;
}

function mkp_if_amp($atts, $thing='')
{

	global $variable;

	// Initiates a TXP variable which sniffs for 'amp' (with or without a final backslash) in URLs or a simple query '?amp'
	$variable['amp'] = ( preg_match( '/amp/',  $GLOBALS['pretext']['request_uri'] ) || !empty(gps('amp')) ? true : false );

	$parts = explode('/', preg_replace("|^https?://[^/]+|i", "", serverSet('REQUEST_URI')), 5);

	// if the url ends in 'amp' this will return true; otherwise fals
	return (end($parts) == 'amp') ? parse(EvalElse($thing, true)) : parse(EvalElse($thing, false));
}
