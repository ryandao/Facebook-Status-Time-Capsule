$(document).ready(function(){
	$(".accordion h3:first").addClass("active");
	$(".accordion p:not(:first)").hide();
	
	$("h3").click(function(){
		$(this).next().slideToggle();
		$(this).toggleClass("active");
	})

})