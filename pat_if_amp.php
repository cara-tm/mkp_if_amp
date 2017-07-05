<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'pat_if_amp';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.3.6-beta';
$plugin['author'] = 'Patrick LEFEVRE (original idea by Michael K Pate)';
$plugin['author_uri'] = 'https://github.com/cara-tm/';
$plugin['description'] = 'AMP pages for Textpattern CMS';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * pat_if_amp plugin. Support for Google AMP with Textpattern CMS.
 * @author:  Michael K Pate & Patrick LEFEVRE.
 * @link:    https://github.com/cara-tm/pat_if_amp, https://github.com/michaelkpate/mkp_if_amp
 * @type:    Public
 * @prefs:   no
 * @order:   5
 * @version: 0.3.6
 * @license: GPLv2
*/

/**
 * This plugin tag registry.
 */
if (class_exists('\Textpattern\Tag\Registry')) {
	Txp::get('\Textpattern\Tag\Registry')
		->register('pat_if_amp')
		->register('pat_amp_sanitize')
		->register('pat_amp_redirect');
}

/**
 * Register callback when public pages are rendering.
 *
 */
if (txpinterface === 'public') {
	// Loads a callback with init function for public context.
	register_callback('pat_if_amp_init', 'textpattern');
}

/**
 * Init function which create the mkp_variable.
 *
 * @param
 * @return boolean $variable
 */
function pat_if_amp_init()
{
	global $variable;

	// Initiates a TXP variable which sniffs for 'amp' (with or without a final backslash) in URLs or a simple query '?amp'
	$variable['pat_amp'] = (preg_match( '/amp/',  $GLOBALS['pretext']['request_uri'] ) || !empty(gps('amp')) ? 1 : 0 );
}

/**
 * Main plugin function.
 *
 * @param  $atts   string This plugin attributes
 * @param  $thing  string
 * @return string
 */
function pat_if_amp($atts, $thing='')
{
	global $variable;

	extract(lAtts(array(
		'redirect'  => false,
		'url'       => hu,
		'subdomain' => 'amp',
		'permlink'  => true,
	), $atts));

	$path = parse_url($GLOBALS['pretext']['request_uri'], PHP_URL_PATH);
	$els = explode('/', $path);

	// Splits URL parts
	$parts = explode( '/', preg_replace("|^https?://[^/]+|i", "", $GLOBALS['pretext']['request_uri']), count($els));

	if ($redirect && '1' == $variable['pat_amp']) {
		// Redirect to same article's title within the subdomain.
		pat_amp_redirect(array('url'=>$url,'subdomain'=>$subdomain,'permlink'=>$permlink));
	} else {
		// If the URL ends in 'amp' this will return true; otherwise false.
		return (end($parts) == 'amp') ? parse(EvalElse($thing, true)) : parse(EvalElse($thing, false));
	}
}

/**
 * Sanitize all inline CSS styles within body/excerpt text content.
 *
 * @param:  $atts array Plugin attribute
 * @return: string      Text content
 */
function pat_amp_sanitize($atts)
{
	extract(lAtts(array(
		'content' => 'body',
	), $atts));

	$out = '';

	if (in_array($content, array('body', 'excerpt')) ) {
		$out = preg_replace('/(<[^>]+) style=".*?"/i','$1', $content());
	} else {
		$out = trigger_error( gTxt('invalid_attribute_value', array('{name}' => 'content')), E_USER_WARNING );;
	}

	return $out;
}

/**
 * Extracts the domain name and redirects to a subdomain.
 *
 * @param: $atts array Plugin attribute
 * @return redirection or false
 */
function pat_amp_redirect($atts)
{
	global $pretext, $thisarticle;

	extract(lAtts(array(
		'url'       => hu,
		'subdomain' => 'amp',
		'permlink'  => false,
	), $atts));

	// Array of the URL.
	$parts = parse_url($url);
	// Verify the host.
	$domain = isset($parts['host']) ? $parts['host'] : '';

	// Regex for a well spelling domain name.
	if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $matches) ) {
		// Redirects to subdomain without or with the current article's URL title.
		return header('Location: '.$parts['scheme'].'://'.$subdomain.'.'.$matches['domain'].($permlink ? '/'.str_replace(hu, '', permlinkurl($thisarticle)) : ''));
	}

	// Otherwise, do nothing.
	return false;
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. pat_if_amp

AMP pages for "Textpattern CMS":http://textpattern.io/.

This conditional tag examines the URL of the current page and determines if the URL ends in 'amp.' This allows for a custom page to be rendered using the standards for Google's Accelerated Mobile Pages (AMP) project.

h2. Links

* "GitHub Code Repository":https://github.com/cara-tm/pat_if_amp
* "Download":https://github.com/cara-tm/mkp_if_amp/blob/master/pat_if_amp_v3.5_zip.txt
* "Demo Site for initial plugin":http://ampdemo.cmsstyles.com/

Don't forget to check out my other plugin that's also Google AMP compatible: "pat_article_social":https://github.com/cara-tm/pat_article_social

h2. Installing

Using "Composer":https://getcomposer.org:

bc. $ composer require cara-tm/pat_if_amp:*

Or download the latest version of the plugin from "the GitHub project page":https://github.com/cara-tm/pat_if_amp/releases, paste the code into the Textpattern Plugins administration panel, install and enable the plugin.

h2. pat_if_amp

h3. Attributes

* @url@ _string_ (optional): The location (URL) of the website. Default: @hu@ (website URL as set in the Textpattern preferences panel).
* @subdomain@ _string_ (optional): Sets the subdomain name of where to redirect to. Default: @amp@.
* @permlink@ _boolean_ (optional): if set to @0@ (false) the redirection for the same article's URL mode within the subdomain will be not made. Default: @1@ (true).

h3. Example

Place code similar to below within your individual article template header, so that the AMP page can be properly detected:

bc. <txp:if_individual_article>
    <link rel="canonical" href="<txp:permlink />">
    <link rel="amphtml" href="<txp:permlink />/amp/">
</txp:if_individual_article>

Place something similar to below within your individual article template to display the alternative formatting:

bc. <txp:pat_if_amp>
    <txp:output_form form="amp_page" />
<txp:else />
    <txp:output_form form="standard_page" />
</txp:mkp_if_amp>

h2. Textattern variable usage

A @<txp:variable name="pat_amp" />@ with a true/false value (@1@ or @0@) is automatically generated, for testing when this is an AMP context or not.

h3. Usage

bc. <txp:if_variable name="pat_amp" value="1">
    AMP context
<txp:else />
    Normal context
</txp:if_variable>

h3. Example

bc. <txp:if_variable name="pat_amp" value="1">
    ...Do some AMP things...
    <a href="<txp:site_url />category/<txp:category name='<txp:category1 />' />?amp">AMP Category 1 Link</a>
<txp:else />
    ...Do some normal (non-AMP) things...
</txp:if_variable>

This example concerns categories, use kind of the same model for @pages@ and/or @archives@.

h2. pat_amp_sanitize

A tag to render article text content without any inline styles. To use as a drop-in replacement of the standard Textpattern @<txp:body />@ tag and/or @<txp:excerpt />@ tag.

h3. Attributes

* @content@ _string_ (optional): choose either @body@ or @excerpt@ article content to sanitize. Default: @body@.

h3. Usage

In an article Form context:

bc. <txp:pat_amp_sanitize content="excerpt" />

h2. pat_amp_redirect

A simple tag for web redirection to a subdomain name. Same behaviour as used in _The Guardian_ website. Must be used with a mobiles detection script (i.e. adi_mobile plugin). Some web designers could choose to use a subdomain for their mobiles websites (may be not recommended, but the official AMP Blog is located in a subdomain, see: "https://amphtml.wordpress.com":https://amphtml.wordpress.com). In this case, **the Textpattern multi-sites capacities makes things easier**.

*Note:* Doesn't work on local server installations, only on live servers.

h3. Usage

bc. <txp:pat_amp_redirect url="" subdomain="" permlink="" />

*Good advice*: Copy the @config.php@ file of the main site into the subdomain configuration folder in order to share the same database. So, all articles written by the Copy Editors will be sent automatically to the 'AMP powered website' from only one Textpattern administration interface attached to the 'normal' website. But designers have a separate and cleaner space to build their AMP pages.

h3. Attributes

* @url@ _string_ (optional): The location (URL) of the website. Default: @hu@ (website URL as set in the Textpattern preferences panel).
* @subdomain@ _string_ (optional): Sets the subdomain name of where to redirect to. Default: @amp@.
* @permlink@ _boolean_ (optional): Choose to redirect the same individual article's title URL within the subdomain. Default: @0@ (false).

h3. Example

bc. <txp:pat_amp_redirect subdomain="amp" permlink="1" />

In this case, if your main domain is @example.com@, it redirects to the @amp.example.com@ subdomain and uses the same individual article's URL from the main domain.

h3. Acknowledgments

Many thanks to "Phil Wareham":http://www.designhive.com/contact/phil-wareham who tidied up this plugin code and its help file.
Thank you to "Michael K. Pate":https://github.com/michaelkpate, for its original idea, who accepted to distribute my entire rewriting code as a 'pat' prefixed plugin.
# --- END PLUGIN HELP ---
-->
<?php
}
?>
