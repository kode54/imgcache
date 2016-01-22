<?php

/**
 * Images cache
 *
 * @name      Images cache
 * @copyright Images cache contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.1
 *
 */

function imageNeedsCache($img)
{
	global $boardurl, $txt;
	static $js_loaded = false;

	$parseboard = parse_url($boardurl);
	$parseimg = parse_url($img);

	if (!($parseboard['scheme'] === 'https') || ($parseboard['scheme'] === $parseimg['scheme']))
		return false;

	if ($js_loaded === false)
	{
		$js_loaded = true;
		loadJavascriptFile('imgcache.js', array('defer' => true));
		loadLanguage('imgcache');
	}

	require_once(SUBSDIR . '/Graphics.subs.php');
	$destination = CACHEDIR . '/img_cache_' . md5($img);
	if (!file_exists($destination))
		resizeImageFile($img, $destination, 200, 200, 3);

	return $boardurl . '/imgcache.php?id=' . md5($img) . '" rel="cached" data-warn="' . Util::htmlspecialchars($txt['httpimgcache_warn_ext']) . '" data-url="' . Util::htmlspecialchars($img);
}

function imgcache_integrate_loadCss()
{
	loadCSSFile('imgcache.css');
}

function imgcache_integrate_loadBBC(&$codes, &$no_autolink_tags, &$itemcodes)
{
	foreach ($codes as $key => $code)
	{
		if ($code['tag'] == 'img')
		{
			$codes[$key]['validate'] = function (&$tag, &$data, $disabled) use ($code) {
				$response = imageNeedsCache($data);

				if ($response)
					$data = $response;

				return $code['validate']($tag, $data, $disabled);
			};
		}
	}
}
