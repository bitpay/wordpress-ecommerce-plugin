jQuery(document).ready(function($){
	$("#generate_keys").click(function() { 

	});
  $(function() {
	$( "#dialog" ).dialog({
		autoOpen: false,
		show: {
			effect: "blind",
			duration: 1000
		},
		hide: {
			effect: "explode",
			duration: 1000
		}
	});
	
	$( "#opener" ).click(function() {
		$( "#dialog" ).dialog( "open" );
		});
	});

	$( "#accordion" ).accordion();
});


	
