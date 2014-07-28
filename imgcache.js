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

$(document).ready(function() {
	$('.bbc_img').each(function() {
		var $img = $(this),
			relattr = $img.attr('rel');
		if (relattr == 'cached')
		{
			var $a = $('<span class="cached_img"><a class="external"></a></span>'),
				$warn = $('<span class="infobox" />').text($img.data('warn'));

			$a.find('.external').attr('target', '_blank').attr('href', $img.data('url'));
			$img.wrap($a);
			$img.after($warn);
		}
	});
});