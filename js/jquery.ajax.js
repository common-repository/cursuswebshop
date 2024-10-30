jQuery(document).ready(function($) {
	$('.cw_commentcount').click(function(e) 
	{
		e.preventDefault();
		var url = $(this).attr('href');
		
		if(url!=undefined)
		{
			var elementnr =$('.cw_commentcount').index(this)+1; 
			if($("#cw_rev"+elementnr).html()==null||$("#cw_rev"+elementnr).html().length == 0)
			{
					var jqxhr = $.get(url, function(response)
					{ 
						$("#cw_rev"+elementnr).html(response).fadeIn("slow");
					}, "html");
			}
			else
			{
				$("#cw_rev"+elementnr).fadeOut("slow",function(){$("#cw_rev"+elementnr).html('')});	
			}
		}
	});
});