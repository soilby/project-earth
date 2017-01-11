$(function () {
    function autoHeight()
    {
		$('#contentMainWrap').animate({'min-height':($(window).height() - $('header').outerHeight(true) - $('footer').outerHeight(true))+'px'},500);
    }
	$('#contentMainWrap').css('min-height',($(window).height() - $('header').outerHeight(true) - $('footer').outerHeight(true))+'px');
	setInterval(function () {autoHeight();}, 2000);
/*
	if ($('.parallaxWrap').length > 0)
	{
		$('body').on('mousemove', function (e)
		{
			var centerX = $('.parallaxWrap').offset().left + $('.parallaxWrap').width() / 2;
			var centerY = $('.parallaxWrap').offset().top + $('.parallaxWrap').height() / 2;
			
			var movementX = (e.pageX - centerX) / 50;
			var movementY = (e.pageY - centerY) / 50;
			if (movementX > 20) movementX = 20;
			if (movementX < -20) movementX = -20;
			if (movementY > 20) movementY = 20;
			if (movementY < -20) movementY = -20;
			$('.parallaxLayer').each(function (index, element) 
			{
				$(element).css('margin-top', Math.round(movementY * (index + 1))+'px');
				$(element).css('margin-left', Math.round(movementX * (index + 1))+'px');
			});
		});
	}

*/
});


var backgroundImages = [
'http://elga.ecoby.info/wp-content/uploads/80-на-100.jpg',
'http://elga.ecoby.info/wp-content/uploads/DSC0328а.jpg',
'http://elga.ecoby.info/wp-content/uploads/l-7l4MX7QR8.jpg',
'http://elga.ecoby.info/wp-content/uploads/DSC0332ро.jpg',
'http://elga.ecoby.info/wp-content/uploads/DSC0332о.jpg',
'http://elga.ecoby.info/wp-content/uploads/DSC0335а.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_1837.jpg',
'http://elga.ecoby.info/wp-content/uploads/4iIklQuAXZY.jpg',
'http://elga.ecoby.info/wp-content/uploads/85-на-90.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_4941.jpg',
'http://elga.ecoby.info/wp-content/uploads/beR6yUDlGR4.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_7658.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_5263а.jpg',
'http://elga.ecoby.info/wp-content/uploads/lG2XdokQChE.jpg',
'http://elga.ecoby.info/wp-content/uploads/NXPBqTdWnc4.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_5baf94b5.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_8efb9bab.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_e95e2c90.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_4038ао.jpg',
'http://elga.ecoby.info/wp-content/uploads/7r5MeKCO4mw.jpg',
'http://elga.ecoby.info/wp-content/uploads/szBb2EHMZFE.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_1e28c435.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_6e3bc6e6.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_7eb86002.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_39ddf41a.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_98d6e807.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_621ba67f.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_9607c228.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_4796627d.jpg',
'http://elga.ecoby.info/wp-content/uploads/y_fd4499c6.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_7483a.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_8375.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_7489a.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_1919.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_2027.jpg',
'http://elga.ecoby.info/wp-content/uploads/MG_6107о.jpg',
'http://elga.ecoby.info/wp-content/uploads/MG_6110.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_1692.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0340f.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0343.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0348f.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0586а.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_5090.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0337f.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_1939.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_2813.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_1933.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_1927.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_1922.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_1842.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_2815.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_2817.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_2395.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_2766.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_2772.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_2274.jpg',
'http://elga.ecoby.info/wp-content/uploads/2014-04-17-17-40-30.jpg',
'http://elga.ecoby.info/wp-content/uploads/2014-04-17-17-42-07.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_5853.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_9040.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_4966.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_3837.jpg',
'http://elga.ecoby.info/wp-content/uploads/20141124_101534.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0281.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0259-kopiya.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0545.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0535f.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0323.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0321.jpg',
'http://elga.ecoby.info/wp-content/uploads/IMG_0540.jpg'];

function parseGetParams() { 
   var $_GET = {}; 
   var __GET = window.location.search.substring(1).split("&"); 
   for(var i=0; i<__GET.length; i++) { 
      var getVar = __GET[i].split("="); 
      $_GET[getVar[0]] = typeof(getVar[1])=="undefined" ? "" : getVar[1]; 
   } 
   return $_GET; 
}
$(function () {
	var $_GET = parseGetParams();
	console.log($_GET['image']);
	if (typeof backgroundImages[$_GET['image']] != 'undefined') {
		console.log(backgroundImages[$_GET['image']]);
		$('body').css('background', 'url('+backgroundImages[$_GET['image']]+') center no-repeat fixed #000');
		$('body').css('-webkit-background-size', 'contain');
		$('body').css('-moz-background-size', 'contain');
		$('body').css('-o-background-size', 'contain');
		$('body').css('background-size', 'contain');
		$('body').prepend('<div style="position:fixed;top:0;left:0;right:0;bottom:0;background:#fff;opacity:0.5;z-index:-1;"></div>');
	}
});

