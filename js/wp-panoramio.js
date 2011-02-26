
 jQuery(window).load(function($){

		var t='';
		jQuery('img.panoramio-wapi-loaded-img').each(function(){
			t=(t=='')?jQuery(this).attr('src'):(t+';'+jQuery(this).attr('src'));			
			});
						
		var src = jQuery('#loadedImage0').attr('src');
		
		jQuery.ajax(
		{
			type:"post",
			url:WpPanoramioSettings.ajaxurl,
		timeout:30000000,
		data:{
			 'action':'myajax-submit',
			 'image_prefix':src,
			 'nonce':WpPanoramioSettings.panoramio_nonce,
			 'urls':t	 
			}		
		});	

			
})


