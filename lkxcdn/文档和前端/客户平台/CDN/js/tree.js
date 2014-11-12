// JavaScript Document
// Name:Javie Chan 
// Date:2013-05-27 
// CDN-new

$(document).ready(function() {
    $('.tree h1').click(function(){
		/*alert('JV');*/
		if($(this).next().is(":visible")==false){
			$(this).next().slideDown();
		}else{
			$(this).next().slideUp();
		}
	});
	
	$('.tree ul li a').click(function(){
		$('.tree ul li a').removeClass('active');
		$(this).addClass('active');
	});
	
	$('.nav ul li a').click(function(){
		$('.nav ul li a').removeClass('atvd');
		$(this).addClass('atvd');
	});
});

function changefooter(){
	if(window.innerHeight < 300){
		alert('JV');
	}
}