/**
 * @desc 		Cursus Webshop JS 
 * @author  	Luuk Verhoeven
 * @copyright 	Sebsoft.nl 
 * @link 		http://www.sebsoft.nl
 * @version 	1.0.1
 * @since		2011
 * 
 * Todo for Next version:
 * @todo 	Afmaken van opmaak van de formulieren line:20
 */
$(function() 
{
	//INIT
	var code   = $("#cw_code");
	var allFields = $( [] ).add( code );
	var tips = $( ".validateTips" );
	$(".cw_button").button();
	//
	$(".cw_form_jquery").form();
	//var jform = $(".cw_form_jquery");
	//jform.find("input[type=text]").toggleClass('ui-state-focus');
	//jform.find("fieldset").addClass("ui-widget-content");
	//jform.find("legend").addClass("ui-widget-header ui-corner-all");
	//jform.addClass("ui-widget");	
	
	$("#cw_refresh" ).click(function() { 
		
		$('#cw_shopping_cart').submit();
	});
	//
	function updateTips( t ) {
		tips
			.text( t )
			.addClass( "ui-state-highlight" );
	}

	function checkLength( o, n, min, max ) {
		if ( o.val().length > max || o.val().length < min ) {
			o.addClass( "ui-state-error" );
			updateTips( "Aantal characters van de: " + n + " moet tussen de " +
				min + " en " + max + " zijn." );
			return false;
		} else {
			return true;
		}
	}

	function checkRegexp( o, regexp, n ) {
		if ( !( regexp.test( o.val() ) ) ) {
			o.addClass( "ui-state-error" );
			updateTips( n );
			return false;
		} else {
			return true;
		}
	}
	
	$("#actioncode" ).click(function() { 
		
		//Prevent pressing enter now
		$(document).bind("keydown keypress", function(e){
			 var code = (e.keyCode ? e.keyCode : e.which);
			 if(code == 13)
			 { //Enter keycode
				e.preventDefault();
			 }	
		});
		
		$( "#cw_dialog" ).dialog({
			resizable: false,
			height:400,
			width: 350,
			modal: true,
			buttons: {
				"Enter": function() 
				{
					checkActionCode();
				},
				"Sluiten": function() {
					allFields.val( "" ).removeClass( "ui-state-error" );
					$( this ).dialog( "close" );
				}
			},
		 	close: function (event, target) 
		 	{
		 		console.log('close');
		 		
		 		window.location.reload();
		    }

		});
	});
	
	function checkActionCode()
	{
		var bValid = true;
		allFields.removeClass( "ui-state-error" );
		bValid = bValid && checkLength( code, "code", 1, 16 );
		
		//Ajax request checking the code
		var $form 		= $("#cw_code_form");
		var url 		= $form.attr( 'action' );
		var input 		= $("#cw_code").val()
		var productid	= $("#cw_code_product").val()
		
		 if ( bValid ) 
		 {
			$.post(url, {  code: input, product: productid },function(data)
			{
				if(typeof(data.ok) != 'undefined') 
				{
					updateTips( data.ok );
				}
				else
				{
					updateTips( data.error );
				}
			}, "json");
		 }	
	}
	//Select bank
	$('.paymentProfile').change(function() {

			$('.submitBank').show();
				$("select option:selected").each(function () {
					if ($(this).val() == "10")
					{
						$('.cw_idealbank').show();
					}
					else
					{
						$('.cw_idealbank').hide();
					}
				});
			});	
});
/*
 * jQuery UI Widget 1.8rc1
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Widget
 */


(function(b){var a=b.fn.remove;b.fn.remove=function(c,d){if(!d){b("*",this).add(this).each(function(){b(this).triggerHandler("remove")})}return a.apply(this,arguments)};b.widget=function(d,f,c){var e=d.split(".")[0],h;d=d.split(".")[1];h=e+"-"+d;if(!c){c=f;f=b.Widget}b.expr[":"][h]=function(i){return !!b.data(i,d)};b[e]=b[e]||{};b[e][d]=function(i,j){if(arguments.length){this._createWidget(i,j)}};var g=new f();g.options=b.extend({},g.options);b[e][d].prototype=b.extend(true,g,{namespace:e,widgetName:d,widgetEventPrefix:b[e][d].prototype.widgetEventPrefix||d,widgetBaseClass:h},c);b.widget.bridge(d,b[e][d])};b.widget.bridge=function(d,c){b.fn[d]=function(g){var e=typeof g==="string",f=Array.prototype.slice.call(arguments,1),h=this;g=!e&&f.length?b.extend.apply(null,[true,g].concat(f)):g;if(e&&g.substring(0,1)==="_"){return h}if(e){this.each(function(){var i=b.data(this,d),j=i&&b.isFunction(i[g])?i[g].apply(i,f):i;if(j!==i&&j!==undefined){h=j;return false}})}else{this.each(function(){var i=b.data(this,d);if(i){if(g){i.option(g)}i._init()}else{b.data(this,d,new c(g,this))}})}return h}};b.Widget=function(c,d){if(arguments.length){this._createWidget(c,d)}};b.Widget.prototype={widgetName:"widget",widgetEventPrefix:"",options:{disabled:false},_createWidget:function(d,e){this.element=b(e).data(this.widgetName,this);this.options=b.extend(true,{},this.options,b.metadata&&b.metadata.get(e)[this.widgetName],d);var c=this;this.element.bind("remove."+this.widgetName,function(){c.destroy()});this._create();this._init()},_create:function(){},_init:function(){},destroy:function(){this.element.unbind("."+this.widgetName).removeData(this.widgetName);this.widget().unbind("."+this.widgetName).removeAttr("aria-disabled").removeClass(this.widgetBaseClass+"-disabled "+this.namespace+"-state-disabled")},widget:function(){return this.element},option:function(e,f){var d=e,c=this;if(arguments.length===0){return b.extend({},c.options)}if(typeof e==="string"){if(f===undefined){return this.options[e]}d={};d[e]=f}b.each(d,function(g,h){c._setOption(g,h)});return c},_setOption:function(c,d){this.options[c]=d;if(c==="disabled"){this.widget()[d?"addClass":"removeClass"](this.widgetBaseClass+"-disabled "+this.namespace+"-state-disabled").attr("aria-disabled",d)}return this},enable:function(){return this._setOption("disabled",false)},disable:function(){return this._setOption("disabled",true)},_trigger:function(d,e,f){var h=this.options[d];e=b.Event(e);e.type=(d===this.widgetEventPrefix?d:this.widgetEventPrefix+d).toLowerCase();f=f||{};if(e.originalEvent){for(var c=b.event.props.length,g;c;){g=b.event.props[--c];e[g]=e.originalEvent[g]}}this.element.trigger(e,f);return !(b.isFunction(h)&&h.call(this.element[0],e,f)===false||e.isDefaultPrevented())}}})(jQuery);

//JavaScript Document 
$.widget("ui.form",{
		 _init:function(){
			 var object = this;
			 var form = this.element;
			 var inputs = form.find("input , select ,textarea");
			 
			  form.find("fieldset").addClass("ui-widget-content");
			  form.find("legend").addClass("ui-widget-header ui-corner-all");
			  form.addClass("ui-widget");
			
			  $.each(inputs,function(){
				$(this).addClass('ui-state-default ui-corner-all');
				$(this).wrap("<label />");
				
				if($(this).is(":reset ,:submit"))
				object.buttons(this);
				else if($(this).is(":checkbox"))
				object.checkboxes(this);
				else if($(this).is("input[type='text']")||$(this).is("textarea")||$(this).is("input[type='password']"))
				object.textelements(this);
				else if($(this).is(":radio"))
				object.radio(this);
				else if($(this).is("select"))
				object.selector(this);
				
				if($(this).hasClass("date"))
				{
					$(this).datepicker();
					
					
				}
				form.find(":submit").removeClass('ui-state-disabled').unbind('click');
				});
	
			 $(".hover").hover(function(){
						  $(this).addClass("ui-state-hover"); 
						   },function(){ 
						  $(this).removeClass("ui-state-hover");  
						   });
			 
			 },
		 textelements:function(element){
			
			$(element).bind({
  			
 			  focusin: function() {
 			   $(this).toggleClass('ui-state-focus');
 				 },
			   focusout: function() {
 			    $(this).toggleClass('ui-state-focus');
 				 }	 
			  });
			 
			 },
		 buttons:function(element)
		 {
			if($(element).is(":submit"))
			{
				$(element).addClass("ui-priority-primary ui-corner-all ui-state-disabled hover");
			 $(element).bind("click",function(event)
			   {
				   event.preventDefault();
			   }); 
			}
			else if($(element).is(":reset"))
			$(element).addClass("ui-priority-secondary ui-corner-all hover");
			$(element).bind('mousedown mouseup', function() {
 			   $(this).toggleClass('ui-state-active');
 				 }
			  			 
			  ); 
		 },
		 
		 checkboxes:function(element)
		 {
			 $(element).parent("label").after("<span />");
			 var parent =  $(element).parent("label").next();
			 $(element).addClass("ui-helper-hidden");
			 parent.css({width:16,height:16,display:"block"});
				
			 parent.wrap("<span class='ui-state-default ui-corner-all' style='display:inline-block;width:16px;height:16px;margin-right:5px;'/>");
			 
			 parent.parent().addClass('hover');
			 
			 parent.parent("span").click(function(event){
						 $(this).toggleClass("ui-state-active");
						 parent.toggleClass("ui-icon ui-icon-check");
						$(element).click();
					
						});
			 
		 },
		 radio:function(element){
			 
			 $(element).parent("label").after("<span />");
			 var parent =  $(element).parent("label").next();
			 
			 $(element).addClass("ui-helper-hidden");
			 parent.addClass("ui-icon ui-icon-radio-off");
			 parent.wrap("<span class='ui-state-default ui-corner-all' style='display:inline-block;width:16px;height:16px;margin-right:5px;'/>");
			 
			 parent.parent().addClass('hover');
		  
			 parent.parent("span").click(function(event){
				$(this).toggleClass("ui-state-active");
				parent.toggleClass("ui-icon-radio-off ui-icon-bullet");
				$(element).click();
				});
			 },
			 selector:function(element){
				 var parent = $(element).parent();
				 parent.css({"display":"block",width:140,height:21}).addClass("ui-state-default ui-corner-all");
				 $(element).addClass("ui-helper-hidden");
				 parent.append("<span id='labeltext' style='float:left;'></span><span style='float:right;display:inline-block' class='ui-icon ui-icon-triangle-1-s' ></span>");
				 parent.after("<ul class='ui-helper-reset ui-widget-content ui-helper-hidden selector-ul' style='position:absolute;z-index:50;width:140px;' ></ul>");
				 
				 $.each($(element).find("option"),function(){								   
					 $(parent).next("ul").append("<li class='hover'>"+$(this).html()+"</li>"); 
				 });
				 
				 $(parent).next("ul").find("li").click(function(){ 
					
					$("#labeltext").html($(this).html());
					$(element).val($(this).html());
			
					//TODO use numbers instead
			
					console.log($(this).html());
					if ($(this).html() == "iDeal")
					{
						$("#paymentProfile").val(10);
						$('.submitBank').show();
						$('.cw_idealbank').show();
					}
					else if($(this).html()=="Overboeking")
					{
						$("#paymentProfile").val(136);
						$('.submitBank').show();
						$('.cw_idealbank').hide();
					}
					//Hide dropdown
					$('.selector-ul').hide(); 
					 
				 });
				 
				 $(parent).click(function(event){ 
					 $(this).next().slideToggle('fast'); 
					 event.preventDefault();						
				});
				                
				}
		 
		 
		 });
