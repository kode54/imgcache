<?php

/**
 * Provides ways to add information to your website by linking to and capturing output
 * from ElkArte
 *
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 1.0 Release Candidate 2
 *
 */


require_once(dirname(__FILE__) . '/SSI.php');

$real_filename = $filename = '';

if (isset($_GET['id']))
	$real_filename = $filename = CACHEDIR . '/img_cache_' . $_GET['id'];

// This is done to clear any output that was made before now.
while (ob_get_level() > 0)
	@ob_end_clean();

$filesize = @filesize($filename);

if (!empty($modSettings['enableCompressedOutput']) && $filesize <= 4194304)
	ob_start('ob_gzhandler');
else
{
	ob_start();
	header('Content-Encoding: none');
}

// No point in a nicer message, because this is supposed to be an attachment anyway...
if (!file_exists($filename))
{
	loadLanguage('Errors');

	header((preg_match('~HTTP/1\.[01]~i', $_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 404 Not Found');
	header('Content-Type: text/plain; charset=UTF-8');

	// We need to die like this *before* we send any anti-caching headers as below.
	die('404 - ' . $txt['attachment_not_found']);
}

// If it hasn't been modified since the last time this attachment was retrieved, there's no need to display it again.
if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
{
	list ($modified_since) = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
	if (strtotime($modified_since) >= filemtime($filename))
	{
		@ob_end_clean();

		// Answer the question - no, it hasn't been modified ;).
		header('HTTP/1.1 304 Not Modified');
		exit;
	}
}

// Check whether the ETag was sent back, and cache based on that...
$eTag = '"' . substr($filename . filemtime($filename), 0, 64) . '"';
if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && strpos($_SERVER['HTTP_IF_NONE_MATCH'], $eTag) !== false)
{
	@ob_end_clean();

	header('HTTP/1.1 304 Not Modified');
	exit;
}

// Send the attachment headers.
header('Pragma: ');
if (!isBrowser('gecko'))
	header('Content-Transfer-Encoding: binary');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 525600 * 60) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filename)) . ' GMT');
header('Accept-Ranges: bytes');
header('Connection: close');
header('ETag: ' . $eTag);
header('Content-Type: image/png');

$disposition = 'inline';

// Different browsers like different standards...
if (isBrowser('firefox'))
	header('Content-Disposition: ' . $disposition . '; filename*=UTF-8\'\'' . rawurlencode(preg_replace_callback('~&#(\d{3,8});~', 'fixchar__callback', $real_filename)));
elseif (isBrowser('opera'))
	header('Content-Disposition: ' . $disposition . '; filename="' . preg_replace_callback('~&#(\d{3,8});~', 'fixchar__callback', $real_filename) . '"');
elseif (isBrowser('ie'))
	header('Content-Disposition: ' . $disposition . '; filename="' . urlencode(preg_replace_callback('~&#(\d{3,8});~', 'fixchar__callback', $real_filename)) . '"');
else
	header('Content-Disposition: ' . $disposition . '; filename="' . $real_filename . '"');

header('Cache-Control: max-age=' . (525600 * 60) . ', private');

if (empty($modSettings['enableCompressedOutput']) || $filesize > 4194304)
	header('Content-Length: ' . $filesize);

// Try to buy some time...
@set_time_limit(600);

// Since we don't do output compression for files this large...
if ($filesize > 4194304)
{
	// Forcibly end any output buffering going on.
	while (ob_get_level() > 0)
		@ob_end_clean();

	$fp = fopen($filename, 'rb');
	while (!feof($fp))
	{
		if (isset($callback))
			echo $callback(fread($fp, 8192));
		else
			echo fread($fp, 8192);

		flush();
	}
	fclose($fp);
}
// On some of the less-bright hosts, readfile() is disabled.  It's just a faster, more byte safe, version of what's in the if.
elseif (isset($callback) || @readfile($filename) === null)
	echo isset($callback) ? $callback(file_get_contents($filename)) : file_get_contents($filename);

obExit(false);
