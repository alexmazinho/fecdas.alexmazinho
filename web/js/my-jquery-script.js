/* Error IE. Se esperaba un identificador, una cadena o un número 
 * Cerca expressió regular ",[\s|\t|\n]*[}|\]]"
 * En la definició d'arrasy etc, l'últim no pot acabar amb coma */

(function($){
	
	
	var matched, browser;

	$.ui.dialog.prototype._focusTabbable = $.noop; /* Prevent first Select2 inside Dialog focus problems */
	
	// Use of jQuery.browser is frowned upon.
	// More details: http://api.jquery.com/jQuery.browser
	// jQuery.uaMatch maintained for back-compat
	jQuery.uaMatch = function( ua ) {
	    ua = ua.toLowerCase();

	    var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
	        /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
	        /(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
	        /(msie) ([\w.]+)/.exec( ua ) ||
	        ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
	        [];

	    return {
	        browser: match[ 1 ] || "",
	        version: match[ 2 ] || "0"
	    };
	};

	matched = jQuery.uaMatch( navigator.userAgent );
	browser = {};

	if ( matched.browser ) {
	    browser[ matched.browser ] = true;
	    browser.version = matched.version;
	}

	// Chrome is Webkit, but Webkit is also Safari.
	if ( browser.chrome ) {
	    browser.webkit = true;
	} else if ( browser.webkit ) {
	    browser.safari = true;
	}

	jQuery.browser = browser;
	
	
	
	/*************************************************** Utils *******************************************************/		
	isValidEmailAddress = function(emailAddress) {
	    //var pattern = new RegExp(/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
	    var pattern = new RegExp(/^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i);
	    return pattern.test(emailAddress);
	};
	
	browserAdvice = function() {
		/* Avís firefox chrome recommended pàgina inici*/
		$("#browser").dialog({
			autoOpen: false,
	    	modal: true,
	    	width: 400,
	    	height: 170,
	    	title: "Avís. Suport de navegadors"
    	});
		/*if (isBrowserOk() == true && !($.browser.webkit || $.browser.mozilla)) {*/
		if (isBrowserOk() == false) {
			var strHtml = "<p>Navegadors recomanats:</p>";
			strHtml += "<p><a href=\"http://www.mozilla.org/ca/firefox/fx/\" target=\"_blank\">Firefox 11+</a></p>";
			strHtml += "<p><a href=\"http://google-chrome.softonic.com/descargar\" target=\"_blank\">Chrome 14+</a></p>";
	    	$("#browser").html(strHtml);
	    	$("#browser").dialog("open");
		};
	};
	
	isBrowserOk = function() {
		/* http://browsershots.org
		IE >= 8, Firefox >= 11, Chorme >=14, Opera >= 11, Safari >= 5 */
		if (($.browser.msie && parseInt($.browser.version, 10) < 8) ||
		    ($.browser.webkit && parseInt($.browser.version, 10) < 14) ||
		    ($.browser.mozilla && parseInt($.browser.version, 10) < 11) ||
		    ($.browser.opera && parseInt($.browser.version, 10) < 11)) {
			return false;
		} 
		return true;
	};
	
	setMenuActive = function(menuid) {
		
		var menuItem = $('nav ul.nav ' + '#'+menuid);
		//$('nav ul.nav li').removeClass('active');
		menuItem.addClass('active');
		menuItem.parents('li.dropdown').addClass('active');
		/*
		var menuItem = $('#'+menuid); 
		
		menuItem.children('a').addClass("left-menu-active"); // <a a/> under menu li
		menuItem.find('span').show();
		
		// Drop down main parent main menu 
		var parentMenuItem = $('#'+menuid).parents('li');
		var mainAction = parentMenuItem.children('.main-menu-action');
		var subMenu = parentMenuItem.children('.submenu');
		
		mainAction.addClass("left-menu-active");	
		mainAction.find('.menu-icon').removeClass('ui-icon-triangle-1-e');
		mainAction.find('.menu-icon').addClass('ui-icon-triangle-1-s');
		subMenu.show(); */
	};
	
	
	helpBubbles = function(t, ht) {
		//create a bubble popup for each DOM element
		//with class attribute as "button"
		$(t).CreateBubblePopup({
 			themePath : '/css/bubble-popup/jquerybubblepopup-themes',	 
 			themeName : 'all-orange',           
 			position: 'right',   
         	innerHtml: 	ht
		});
	};
	
	helpBubblesLlista = function(t, ht) {
		$(t).CreateBubblePopup();
		
		//set customized mouseover event for each button
		$(t).mouseover(function(){
			$(this).ShowBubblePopup({
				themePath : '/css/bubble-popup/jquerybubblepopup-themes',	 
	 			themeName : 'all-orange',           
	 			position: 'right',   
				innerHtml: $(this).children(ht).html()					 
		  });
		}); //end mouseover event

	};
	
	formFocus = function() {
		$(".forminput-inside")
		.bind("focus.labelFx", function(){
			$(this).parent().find("label").hide();
		})
		.bind("blur.labelFx", function(){
			$(this).parent().find("label")[!this.value ? "show" : "hide"]();
		})
		.trigger("blur.labelFx");
	};
	
	actionsModalOverlay = function() {
	    //if close button is clicked
	    $('.finestra-overlay .close').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $('.mask, .finestra-overlay').hide();
	        $('.finestra-overlay').html('');
	        $( window ).off('resize');
	    });    
	     
	    //if mask is clicked
	    $('.mask').click(function () {
	        $(this).hide();
	        $('.finestra-overlay').hide();
	        $('.finestra-overlay').html('');
	        $( window ).off('resize');
	    });       
		
		$('.form-button-cancel').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $('.mask, .finestra-overlay').hide();
	        $('.finestra-overlay').html('');
	        $( window ).off('resize');
	    });   
		
		$('.modalform').submit( function(){
		  // Send the form via Ajax
			return false;
		});
		/*
		 * Disable enter on form */
		$(".modalform").keypress(function(e) {
	        if (e.which == 13) {
	        	if ($("form :submit").is(":focus")) return true;
	        	if ($("form :textarea").is(":focus")) return true;
	            return false;
	        }
	    });
	};
	
	twoDigit = function (n){
	    return n > 9 ? "" + n: "0" + n;
	};
	
	// proper case string prptotype (JScript 5.5+)
	String.prototype.toProperCase = function()
	{
	  return this.toLowerCase().replace(/^(.)|\s(.)/g, 
	      function($1) { return $1.toUpperCase(); });
	};
	
	sortLlista = function(listheaderid, llistaid) {
	    $('.'+listheaderid)
	    .off('click')
	    .click(function(e) {
	    	if ($(this).hasClass('listheader-noorder')) return true; // Comportament normal. Permet posar links a la capçalera per exemple	 
	    	
			//Cancel the link behavior
	        e.preventDefault();

	        var current = $(this);
	        /* first remove all other order icons */
	        $(this).parent().children().not(current).each(function() {
		        $(this).find(".listheader-order").removeClass("ui-icon ui-icon-triangle-1-s");
		        $(this).find(".listheader-order").removeClass("ui-icon ui-icon-triangle-1-n");
	        });
	        
	        var orderasc = true;
	        var ordericon = $(this).find(".listheader-order");
	        
	        if (ordericon.hasClass("ui-icon-triangle-1-s")) {
	        	ordericon.removeClass("ui-icon-triangle-1-s");
	        	ordericon.addClass("ui-icon-triangle-1-n");
	        	orderasc = false;
	        } else {
	        	ordericon.addClass("ui-icon ui-icon-triangle-1-s");
	        }
	        
	        var index = $(this).index();  // Indexs elements dins <ul><ol> començant per 1
	        
	        var items = $('#'+llistaid+' li').get();
			
			items.sort(function(a,b){
				var keyA = 0;
				var keyB = 0;
				var elechild;
				
				var eleA = $(a).children().eq(index);
				var eleB = $(b).children().eq(index);
				
				if (eleA.children('a').length > 0) {
					elechild = eleA.children('a').first().clone();
					$(elechild).find('img').remove();
					keyA = $(elechild).html();
				} else {
					keyA = eleA.html();
				}
				if (eleB.children('a').length > 0) {
					elechild = eleB.children('a').first().clone();
					$(elechild).find('img').remove();
					keyB = $(elechild).html();
				} else {
					keyB = eleB.html();
				}
				
				if ( !isNaN( parseInt( keyA.replace(/,/g, '') ) ) ) {
					keyA =  parseInt( keyA.replace(/,/g, '') );
				}

				if ( !isNaN( parseInt( keyB.replace(/,/g, '') ) ) ) {
					keyB =  parseInt( keyB.replace(/,/g, '') );
				}
				
				if (keyA < keyB) return (orderasc == true)?-1:1;
				if (keyA > keyB) return (orderasc == true)?1:-1;
				return 0;
			});
			
			var ul = $('#'+llistaid);
			
			$.each(items, function(i, li){
				ul.append(li);
			});
	    });
	};
	
	$.fn.hasOverflowY = function() {
	    var $this = $(this);
	    var $children = $this.find('li');
	    var len = $children.length;
	
	    if (len) {
	    	if (len > 20) return true; // segur que hi ha scroll
	    	
	        var maxHeight = 0;
	        $children.map(function(){
	            maxHeight = Math.max(maxHeight, $(this).outerHeight(true));
	        });
	        return maxHeight > $this.height();
	    }
	
	    return false;
	};
	
	$.fn.hasScrollBar = function() {
		var $this = $(this);
		
		if ($this.get(0).scrollHeight == 0) return false;
		
        return $this.get(0) ? $this.get(0).scrollHeight > this.innerHeight() : false;
    };
    
    reloadScrollTable = function( scroll, header, colHeader, lastColHeader  ) { 
    					// per exemple $('.table-scroll'), $('.table-header'), $('.col-listheader'), $('#header-userclubactions') 
 	
		//if ( scroll.hasScrollBar() == true ) {
    	if ( scroll.hasOverflowY() == true ) {
			var totalWidth = header.width();
			var barWidth = 15;
			
			colHeader.not('#header-apunt-entrada').each( function() {
				var ratio = $(this).width( ) / totalWidth;
				
				$(this).width( $(this).width( ) - ( ratio * barWidth) );
			});

			lastColHeader.width( lastColHeader.width( ) + barWidth );
			//header.width( $('.table-header').width( ) - 15 );
		};
	};
	
	
	//Cercador select2 genèric cal que existeixi mètode al Controller que gestioni params 'cerca' i 'id'
	init_cercagenerica_JSON = function(elem_sel, placeholder_txt, url) {
		
		// Inicialitza el control de cerca (input hidden) 
		$(elem_sel).select2({
			minimumInputLength: 2,
			allowClear: true,
			multiple: false,
			placeholder: placeholder_txt,
	
			query: function (query) {
				var data = { results: [] };
				var params = { 	'cerca': query.term };
				// Consulta activitats %desc% que no tingui assignades la persona o no sigui alguna de les excepcions 
				$.get(url,	params, function(jdata) {
					data.results = jdata;
					query.callback(data);
				}).fail(function() {
					query.callback(data);
				});
			},
			initSelection: function(element, callback) {  // value del input ==> carrega per defecte
				//if (element.val() !== undefined && element.val() > 0) {
					var data = [];
					var params = { 	'id': element.val() };
					$.get(url,	params, function(jdata) {
						//callback(jdata['id']);
						callback(jdata);
					}).fail(function() {
						callback(data);
					});
					
			        callback(data);
				//}
			} 
		});
	};
	
	
	//Cercador select2 genèric cal que existeixi mètode al Controller que gestioni params 'cerca' i 'id'
	init_cercapernomdnimail_JSON = function(elem_sel, placeholder_txt, minInput, allowclear, url, 
											callbackPropagateValues, selectionFormat, resultFormat, onclearingFunction, 
											loadedFunction, openingFuncion) {

		if (typeof selectionFormat === "undefined") {
			selectionFormat = function(item) {
		        return item.text;
		    };
		}
		
		if (typeof resultFormat === "undefined") {
			resultFormat = function(item) {
		        return item.text+"-"+item.nomcognoms;
		    };
		}
		
		if (typeof loadedFunction === "undefined") {
			//e.val, e.added, e.removed
			loadedFunction = function( e ) { };
		}
		
		if (typeof onclearingFunction === "undefined") {
			//e.val, e.added, e.removed
			onclearingFunction = function( e ) { };	// e.preventDefault() to avoid clearing
		}
		
		if (typeof openingFuncion === "undefined") {
			//e.val, e.added, e.removed
			openingFuncion = function( e ) { };	
		}
		
		// Inicialitza el control de cerca (input hidden) 
		$(elem_sel).select2({
			minimumInputLength: minInput,
			allowClear: allowclear,
			multiple: false,
			placeholder: placeholder_txt,
	
			query: function (query) {
				var data = { results: [] };
				var params = { 	'cerca': query.term };
				// Consulta activitats %desc% que no tingui assignades la persona o no sigui alguna de les excepcions 
				$.get(url,	params, function(jdata) {
					data.results = jdata;

					query.callback(data);
				}).fail(function() {
					query.callback(data); 
				});
			},
			initSelection: function(element, callback) {  // value del input ==> carrega per defecte
				//if (element.val() !== undefined && element.val() > 0) {
					var data = [];
					var params = { 	'id': element.val() };
					$.get(url,	params, function(jdata) {
						//callback(jdata['id']);
						callback(jdata);
					}).fail(function() {
						callback(data);
					});
					
			        callback(data);
				//}
			},
		    formatResult: resultFormat,
		    formatSelection: selectionFormat,
		}).on("change", function ( e ) { 
			if (typeof callbackPropagateValues !== "undefined"
				&& typeof e.added  !== "undefined") {
				//e.val, e.added, e.removed
				callbackPropagateValues(elem_sel, e.added);
			}
		})
		.on("select2-clearing", onclearingFunction)
		.on("select2-loaded", loadedFunction)
		.on("select2-opening", openingFuncion);		
	};
    
	/********** Selectors de dates ***************/
	
	initDateTimePicker = function (elem, min, max, current, id, showtime, callback ) {
		
		if ( elem.attr("readonly") === true || elem.attr("readonly") === "readonly") {
			return;
		}
		
		var curformat = 'd/m/Y';
		if (showtime) curformat = 'd/m/Y H:i';
	
		//$.datetimepicker.setLocale('ca');
		
		elem.datetimepicker({
			 onGenerate:function( ct, $input ) {
				$input.parent().on('click', '.open-calendar', function () {
					if ( ! $(id).is(":visible") ) {
						//$input.datetimepicker('hide');
	 	 			//} else {
	 	 				$input.datetimepicker('show');
	 	 			}
				});
			 },
			 onChangeDateTime:callback,
			 closeOnDateSelect: true,
			 timepicker: showtime,
			 lang: 'ca',
			 id:  id,
			 className: 'pickerclass',
			 format: curformat, // '',
			 /*minDate: min,
			 maxDate: max,
			 formatDate: curformat,*/
			 minDate: min.getFullYear()+"-"+min.getMonthFormatted()+"-"+min.getDayFormatted(),
			 maxDate: max.getFullYear()+"-"+max.getMonthFormatted()+"-"+max.getDayFormatted(),
			 formatDate: 'Y-m-d',
			 defaultDate: current,
			 startDate: current,
			 yearStart: min.getFullYear(),
			 yearEnd: max.getFullYear()
			 
		});
	};
	
	function getCurrentDate($separador) {
		var current = new Date();
		var currentFormatted = current.getDayFormatted() + $separador + current.getMonthFormatted() + $separador + current.getFullYear();
		return currentFormatted;
	};

	Date.prototype.getMonthFormatted = function() {
	    var month = this.getMonth();
	    return month < 9 ? '0' + (month+1) : month+1; // ('' + month) for string result
	};

	Date.prototype.getDayFormatted = function() {
	    var day = this.getDate();
	    return day < 10 ? '0' + day : day;
	};
	
	dialegError = function(titol, strError, dwidth, dheight) {
		$("#dialeg").dialog({
	    	modal: true,
	    	resizable: false,
	    	width: dwidth,
	    	height: (dheight !== undefined?dheight:"auto"),
	    	title: titol,
	        buttons: {
	            Ok: {
	            	click: function() {
	            		$( this ).dialog( "close" );
	            	},
	            	text: "Ok",
	                class: "btn btn-default"
	            }
	        }
    	});
		
		$("#dialeg").html("<div class='alert alert-danger'>"+
				"<ul><li><span class='fa fa-exclamation-circle fa-1x'></span>"+
				strError+"</li></ul></div>");
	};
	
	dialegInfo = function(titol, strInfo, dwidth, dheight) {
		$("#dialeg").dialog({
	    	modal: true,
	    	resizable: false,
	    	width: dwidth,
	    	height: (dheight !== undefined?dheight:"auto"),
	    	title: titol,
	        buttons: {
	            Ok: {
	            	click: function() {
	            		$( this ).dialog( "close" );
	            	},
	            	text: "Ok",
	                class: "btn btn-default"
	            }
	        }
    	});
		
		$("#dialeg").html("<div class='alert alert-info'>"+
				"<ul><li><span class='fa fa-exclamation-circle fa-1x'></span>"+
				strInfo+"</li></ul></div>");
	};

	dialegInfoCallback = function(titol, strInfo, dwidth, dheight, callbackko) {
		$("#dialeg").dialog({
	    	modal: true,
	    	resizable: false,
	    	width: dwidth,
	    	height: (dheight !== undefined?dheight:"auto"),
	    	title: titol,
	        buttons: {
	            Ok: {
	            	click: function() {
	            		$( this ).dialog( "close" );
	            		callbackko();
	            	},
	            	text: "Ok",
	                class: "btn btn-default"
	            }
	        }
    	});
		
		$("#dialeg").html("<div class='alert alert-info'>"+
				"<ul><li><span class='fa fa-exclamation-circle fa-1x'></span>"+
				strInfo+"</li></ul></div>");
	};
	
	closeDialegConfirmacio = function() {
		$( '#dialeg' ).html('');
		$( '#dialeg' ).dialog( "destroy" );
	};
	
	dialegConfirmacio = function(strHtml, titol, h, w, callbackok, callbackko, callbackopen) {
		
		$( '#dialeg' ).html(strHtml);
		
		$( '#dialeg' ).dialog({
			 resizable: false,
			 title: titol,
			 height: (h !== undefined?h:"auto"),
			 width:  (w !== undefined?w:300),
			 modal: true,
			 buttons: {
				 "Continuar": {
					 click: function() {
						 callbackok();
						 // El retorn de callback no funciona després de crida asíncrona $.get o $.post
					 },
					 text: "Continuar",
	                 class: "btn btn-default"
				 },
				 "Cancel·lar": {
					 click: function() {
						 callbackko();
					 },
					 text: "Cancel·lar",
	                 class: "btn btn-default"
				 }
			 },
			 open: callbackopen
		});
	}; 
	
	dialegInformacio = function(strHtml, titol, h, w, callbackclose, callbackopen) {
		
		$( '#dialeg' ).html(strHtml);
		
		$( '#dialeg' ).dialog({
			 resizable: false,
			 title: titol,
			 height: (h !== undefined?h:"auto"),
			 width:  (w !== undefined?w:300),
			 modal: true,
			 buttons: {
				"Tancar": { 
					 click: function() {
						 callbackclose();
					 },
					 text: "Tancar",
	                 class: "btn btn-default"
			 	}
			 },
			 open: callbackopen
		});
	}; 
	
	smsResultAjax = function(result, sms) {

		var classAlert = '',errorRemove = ''; 
		if (result !== 'OK') { classAlert = 'alert-danger'; }
		else { classAlert = 'alert-info'; }
		
		errorRemove += '<div class="alert '+classAlert+' form-alert alert-dismissible">';
		errorRemove += '<button data-dismiss="alert" class="close" type="button">';
		errorRemove += '<span aria-hidden="true">×</span><span class="sr-only">Close</span></button>';
		errorRemove += '<ul><li><span class="fa fa-exclamation-circle fa-1x"></span>';
		errorRemove += sms+'</li></ul></div>';

		return errorRemove;
	};
	
	jQuery.fn.extend({
		slideRightShow: function() {
		    return this.each(function() {
		    	$(this).show('slide', {direction: 'right'}, 1000);
		    });
		},
		slideLeftHide: function() {
		    return this.each(function() {
		    	$(this).hide('slide', {direction: 'left'}, 1000);
		    });
		},
		slideRightHide: function() {
		    return this.each(function() {
		    $(this).hide('slide', {direction: 'right'}, 1000);
		    });
		},
		slideLeftShow: function() {
		    return this.each(function() {
		    	$(this).show('slide', {direction: 'left'}, 1000);
			});
		}
	});
	
	redirectLocationUrlParams = function(url, params) {
		var i = 0;
		for ( i in params ) {
			url=url+'&'+params[i].name+'='+params[i].value;
		}
		window.location = url; 
	};

	
	/*****************************************************************************************************************/
	
	/*************************************************** Menu ********************************************************/
	
	
	reloadCurrentClub = function( url, urlCallback, role, club ) {
		var params = { 	currentrole: role, currentclub: club };
		$.get(url,	params,
		function(data) {
			if (data !== "reload") {
				location.reload();
			} else {
				window.location = urlCallback;
			}
			
		}); // Canvi de rol
	};
	
	mainMenuClick = function() {
		$( ".main-menu-action" ).on('click', function(e, t){
			e.preventDefault();

	        var subMenu = $(this).parent('li').children('.submenu');
	        
	        if (subMenu.is(':visible')) {
	        	subMenuHide($(this).parent('li'));
	        } else {
	        	/* Close all submenus */
	        	$('.submenu').each( function() {
	        		subMenuHide($('.navigation').children());
	        	});
	        	
	        	if ($.browser.msie) subMenu.show(); 
		    	else subMenu.stop().slideDown('fast');
		        
	        	$(this).addClass("left-menu-active");

	        	$(this).find('.menu-icon').addClass('ui-icon-triangle-1-s');
	        	$(this).find('.menu-icon').removeClass('ui-icon-triangle-1-e');
		    }
		});
	};
	
	subMenuHide = function(mainMenuItem) {
		var mainAction = mainMenuItem.children('.main-menu-action');
		var subMenu = mainMenuItem.children('.submenu');
		
		if ($.browser.msie) subMenu.hide(); 
    	else subMenu.stop().slideUp('fast');
		
		mainAction.removeClass("left-menu-active");
		mainAction.find('.menu-icon').addClass('ui-icon-triangle-1-e'); // East
		mainAction.find('.menu-icon').removeClass('ui-icon-triangle-1-s');
	};
	
	/*****************************************************************************************************************/
	
	/*************************************************** Home ********************************************************/	
	
	login = function() {
		//$('loginbox').show(); 
		if ($('#loginbox').is(':visible')) {
	    	$('#loginbox').hide();
	    	$("#login_user").RemoveBubblePopup();
	    	$("#login_pwd").RemoveBubblePopup();
	   	} else {
	       	$('#loginbox').show();  
	       		
	       	//autofillLoginCheck();
	       	
	   		helpBubbles("#login_user", '<p>L\'usuari és una adreça de correu electrònic. p.e. </p>\
	       					<p>maildelclub@domini.com</p>');

	       	helpBubbles("#login_pwd", '<p>Indica la teva paraula clau d\'accés al sistema</p>');
	   	}
	};
	
	/*autofillLoginCheck = function()  {
	   	if($('#login_user').val() != '') {
	   		$('#login_user').parent().find("label").hide();
	    };
	   	if($('#login_pwd').val() != '') {
	   		$('#login_pwd').parent().find("label").hide();
	    }
	};*/
	
	/*********************************************************************************************************************/
	
	/*************************************************** Enquesta ********************************************************/	
	
	showEnquestaClick = function() {
	    //select all the a tag with name equal to modal
		$('.enquesta-action-open')
	    .off('click')
	    .click(function(e) {
	        //Cancel the link behavior
	        e.preventDefault();

			var url = $(this).attr("href");

			$.get(url, function(data) {
				if (data == "error") location.reload();
				else {
					$("#dialeg").html(data);
					
					var ewidth = $(window).width()*0.8;
					if (ewidth > 800) ewidth = 800;
					var eheight = $(document).height()*0.8;
					
					var etitle = 'Enquesta';
					if ($('#enquesta-preview').length) etitle = 'Enquesta (Previsualització)'; 
					
					$("#dialeg").dialog({
						buttons : {
			            	"Desar i continuar després" : {
			            		click: function() {
				            		$(this).dialog("destroy");
				            		if (!$('#enquesta-preview').length) submitEnquesta('desar');
				            		else alert("Mode previsualització, les dades no es desaran");
				        		},
				        		text: "Desar i continuar després",
				                class: "btn btn-default"
			            	},
			            	"Finalitzar" : {
			            		click: function() {
				            		$(this).dialog("destroy");
				            		if (!$('#enquesta-preview').length) submitEnquesta('final');
				            		else alert("Mode previsualització, les dades no es desaran");
				        		},
			            		text: "Finalitzar",
				                class: "btn btn-default"
			            	},
			            	"Cancel·lar" : {
			            		click: function() {
			            			$(this).dialog("destroy");
			            		},
			            		text: "Cancel·lar",
				                class: "btn btn-default"
			            	}
			          	},
			          	show: 1000,
				    	modal: true,
				    	resizable: true,
				    	width: ewidth,
				    	height: eheight,
				    	minWidth: 400,
				    	title: etitle
			    	});
					
				}
			});
	    });
	};
	
	submitEnquesta = function(action) {
		var url = $('#formenquesta').attr("action");
		var params = $('#formenquesta').serializeArray();
		params.push( {'name':'submitaction','value': action} );
		$.post(url, params, function(data) {
			//$( ".selector" ).dialog( "destroy" );
			dialegInfo("Enquesta desada", data, 350, 100);
		});
	};

	
	/*****************************************************************************************************************/
	
	queryParteTipus = function(url, club, date, callbackOk ) {

    	// Update select tipus parte	
		var params = { 'day': date.getDate(), 'month': (date.getMonth()+1), 'year': date.getFullYear(), 'club': club  };
		$.get(url,	params,
		function(data) {
			
			callbackOk( data );
			
		}).fail( function(xhr, status, error) {
			
			return false;
    	});
	};
	
	reloadParteTipus = function() {
		/* Inicialment selecció de cap tipus. Obligar usuari escollir*/
		//$('#parte_tipus').val('');
		
		/* canvi de tipus */
		$('#parte_tipus').change(function() {
			if ($('#parte_tipus').val() == "") {
		    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
		    	else $('#formparte-llicencia').stop().slideUp('fast');
			} else {
				loadLlicencia(0);				
			}
	    });
	};
	
	showHistorialLlicencies = function() {
	    //Carrega i mostra historial per un assegurat
		$('.link-historial')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();

	        $('.alert').remove();
	        
	        var current = $(this).parents(".data-detall");
	        var index = $( "li.data-detall" ).index( current );
	        index++;
	        
	        if ($.browser.msie) $('#historial-overlay').hide(); 
	    	else $('#historial-overlay').stop().fadeOut('fast');
	        $('#historial-overlay').html('');

	        tancarMascaraBlock( '' );
	        
	        var url = $(this).attr("href");
		        
	        $.get(url, function(data, textStatus) {
	        	$('#historial-overlay').append(data);
		        	
	        	if ($.browser.msie) $('#historial-overlay').show(); 
		    	else $('#historial-overlay').stop().fadeIn('fast');
		        	
	        	var cssTop = (index * 35);
	        	if ($('#historial-overlay').height() + cssTop > $('#llista-dadesfederats').height() ) {
	        		cssTop = $('#llista-dadesfederats').height() - ($('#historial-overlay').height() + cssTop); // Negatiu
	        	}
	        	$('#historial-overlay').css({'top': cssTop });
	        	
		        obrirMascaraBlock(  $('.llista-dadesfederats .table-scroll') );
		        
		        actionsModalOverlay();
			        
		        //if close button is clicked
			    $('.titulacions-historial .close').click(function (e) {
			        //Cancel the link behavior
			        e.preventDefault();
			        
			        tancarMascaraBlock( '' );
			        
			        if ($.browser.msie) $('#historial-overlay').hide(); 
			    	else $('#historial-overlay').stop().fadeOut('fast');
			        $('#historial-overlay').html('');
			    }); 
        	}).fail( function(xhr, status, error) {
        		// xhr.status + " " + xhr.statusText, status, error
	        	var sms = smsResultAjax('KO', xhr.responseText);
	    			 
	   			$('#form_dadesfederats').append(sms);
        	});
	    });
	};
	
	showMask = function() {
        // Show mask before overlay
        //Get the screen height and width
		var maskHeight = $(document).height();
        var maskWidth = $(window).width();
        //Set height and width to mask to fill up the whole screen
        $('.mask').css({'width':maskWidth,'height':maskHeight});
        //transition effect    
        $('.mask').stop().fadeTo("slow",0.6); 
	};

	hideMask = function() {
        //transition effect    
        $('.mask').stop().fadeOut("slow"); 
	};

	
	showElementMask = function(element, showImage) {
        // Show mask before overlay
        //Get the screen height and width
		var maskHeight = element.height();
        var maskWidth = element.width();
        $('.mask').offset( { top: 0, left: 0 } );
        
        //$('.mask').css({'width':maskWidth,'height':maskHeight, 'position': 'absolute', 'left': 0, 'top': 0});
        $('.mask').css({'width':maskWidth,'height':maskHeight});

        //Set height and width to mask to fill up the whole screen
        
        //$('.mask').offset(element.offset());
        var offsetTop = element.offset().top - $(window).scrollTop();
        var offsetLeft = element.offset().left - $(window).scrollLeft();

        //transition effect    
        $('.mask').stop().fadeTo("slow",0.6);
        $('.mask').offset( { top: offsetTop, left: offsetLeft } );
        if ( showImage == false ) {
        	$( '.mask-progress' ).hide();
        }
	};
	
	
	obrirMascaraBlock = function(block) {
		$(block).prepend('<div class="block-mask"><div><span class="fa fa-spinner fa-spin fa-2x green"></span></div></div>');
		$(block).css({'min-height':'200px'});
		$(block).css({'position':'relative'});
		$('.block-mask').stop().fadeTo("slow",0.5); 
	};

	tancarMascaraBlock = function(block) {
		$('.block-mask').remove();
	};
	
	showPersonModal = function(url, origen, callbackOk) {
        // Show mask before overlay
        //Get the screen height and width
		var maskHeight = $(document).height();
        var maskWidth = $(window).width();
        //Set height and width to mask to fill up the whole screen
        $('.mask').css({'width':maskWidth,'height':maskHeight});
        //transition effect    
        $('.mask').stop().fadeTo("slow",0.6); 
        
		$.get(url, function(data, textStatus) {

			$("#edicio-persona").html(data);
			// Reload DOM events. Add handlers again. Only inside reloaded divs
			var current = new Date();
			var mindate = new Date( current.getFullYear() - 100, 1 - 1, 1);
			var maxdate = new Date ( current.getFullYear() - 4, 1 - 1, 1);
			var opendate = new Date ( current.getFullYear() - 30, 1 - 1, 1);
			initDateTimePicker ( 
				$( '#persona_datanaixement' ), 
				mindate, 
				maxdate, 
				opendate, 
				'datanaixement-picker', 
				false
			);
			
			
			formFocus();
			autocompleters( $('#formpersona-autocompleters').attr('href'), $('#persona_addrpob'), $('#persona_addrcp'), $('#persona_addrprovincia'), $('#persona_addrcomarca'), "#edicio-persona" );
			actionsModalOverlay();
			actionsPersonaForm(origen);
			
			imageUploadForm("#persona_fotoupld", 104);
			
			/*prepareRemoveFotoGaleria( "#edicio-persona", function() {
				// Accions addicionals
				$('#persona_foto').val( '' );
			});*/
			
			prepareRemoveFotoGaleria();
			
			prepareRemoveFile( "#edicio-persona", '' );
			
			
			prepareFileInput( $("#persona_arxiuupld") );
			
			$( "#tabs-persona" ).tabs();
			
			$("select#persona_addrprovincia").select2({
				minimumInputLength: 2,
				allowClear: true,
				placeholder: "Província",
			});
			$("select#persona_addrcomarca").select2({
				minimumInputLength: 2,
				allowClear: true,
				placeholder: "Comarca",
			});
			$("select#persona_addrnacionalitat").select2({
				minimumInputLength: 1,
				allowClear: true,
				placeholder: "ESP",
			});
			/* Check estranger */
			if ($('#persona_id').val() != 0) {
				//$("#formpersona-estranger").hide();
				var error = validarDadesPersona($("#persona_dni").val(), $("#persona_estranger").is(':checked'));
	    		if (error != "") {
	    			$("#persona_estranger").prop("checked", true);
	    		};
			}
			
			$("select#persona_altretitol").select2({
				minimumInputLength: 4,
				allowClear: true,
				width: 'off',
				placeholder: 'Escollir títol extern'
			});
			
			
			$('.add-titol-extern').click(function (e) {
		        //Cancel the link behavior
		        e.preventDefault();
		        var dataSelected = $("#persona_altretitol").select2("data");
		        
		        var idTitol = dataSelected.id;
		        var textTitol = dataSelected.text;
		        
		        if (idTitol != "") {
		        	// Afegir a la llista
		        	$('.titulacions-historial .alert.alert-success').addClass('hidden');  //<div class="alert alert-success hidden" role="alert">Cap titulació</div>  
		        	
		        	var nouItem = $('.item-historial.item-blank').clone();
		        	nouItem.removeClass('hidden item-blank');
		        	nouItem.html( nouItem.html().replace('ID_REPLACE', idTitol ) );
		        	nouItem.find( '.historial-titulacio').html( textTitol );
		        	nouItem.find( '.titol-action-remove').attr( 'data-id', idTitol );
		        	$('.historial-altrestitols').append(nouItem);

		        	// Afegir al camp ocult
		        	if ($('#persona_altrestitolscurrent').val().trim() == '') {
		        		$('#persona_altrestitolscurrent').val( $("select#persona_altretitol").val() );
		        	} else {
			        	var current = $('#persona_altrestitolscurrent').val().trim().split(";");
			        	current.push( $("select#persona_altretitol").val() );
			        	$('#persona_altrestitolscurrent').val( current.join(";") );
		        	}
		        	$("#persona_altretitol").select2("data", "");
		        }
		        
			});
			// Delegate
			$( ".titulacions-historial" ).on( "click", ".remove-titol-extern", function( e ) {
		        //Cancel the link behavior
		        e.preventDefault();
		        
		        var idTitol = $(this).attr( 'data-id' );
		        var parentRow = $(this).parents('.item-historial');	
		        
		        // Treure de la llista
		        var current = $('#persona_altrestitolscurrent').val().trim().split(";");
				if (current.indexOf(idTitol) !== -1) {
					current.splice( current.indexOf(idTitol) , 1);
				}
				$('#persona_altrestitolscurrent').val( current.join(";") );
				
		        parentRow.remove();

		        if ($('#persona_altrestitolscurrent').val() == '') {
		        	$('.titulacions-historial .alert.alert-success').removeClass('hidden');
		        }
		        
			});
			
			$( window ).resize(function() {
				if ( $('#edicio-persona') !== undefined && $('#edicio-persona').is(':visible') ) {
					showModalDiv('#edicio-persona');
				}
			});
			
			// Show Div
			showModalDiv('#edicio-persona');
			helpBubbles("#help-dni", '<p align="left">El format del DNI ha de ser <b>12345678X</b></p>\
					<p align="left">En cas de menors que no disposin de DNI</p>\
					<p align="left">cal afegir el prèfix \'P\' o \'M\' al DNI del</p>\
					<p align="left">pare o la mare respectivament. P.e. <b>P12345678X.</b></p>\
					<p align="left">Per estrangers indicar el número d\'identificació equivalet</p>');
		});
	};
	
	showModalDiv = function(id) {
        //Get the window height and width
        var winH = $(window).height();
        var winW = $(window).width();
        
        $(id).css('width',  '');
        if ($(id).width() > winW - 10) {
        	// Per defecte modal de persona 950px
        	$(id).css('width',  winW - 30);
	        $(id).css('left', 10);
        } else {
	        //Set the popup window to center
	        //$(id).css('top',  winH/2-$(id).height()/2);
	        $(id).css('left', winW/2-$(id).width()/2);
        }
     
        $(id).css('top',  5);
        
        //transition effect
        $(id).stop().fadeIn(1500);
        
        // Vertical scroll
        var formHeight = $('.errors-persona').height()+$('.dades-persona').height()+$('.adreca-persona').height();
    	        
        $(id).css({'max-height': '', 'overflow-y': '', 'overflow-x': ''} );
        $('#tabs-persona .taula-historial').css({'max-height': '200px', 'overflow-y': 'auto'} );
   	
        if (winH < formHeight + 450) {	// 350 mida mínima tabs forma persona. Scroll 200 llistes tabs
        	$(id).css({'max-height': (winH - 30)+'px', 'overflow-y': 'auto', 'overflow-x': 'hidden'} );
        	
            $('#tabs-persona .taula-historial').css({'max-height': 'none', 'overflow': 'unset'} );
        }
        
        
	};
	
	autocompletersConfig = function(url, camp, pob, cp, prov, comarca, open, appendSel) {
		
		comarca.select2({
			minimumInputLength: 2,
			allowClear: true,
			placeholder: "Comarca",
		});
		
		prov.select2({
			minimumInputLength: 2,
			allowClear: true,
			placeholder: "Província",
		});
		
		var route = url;
		var $configs = {
			source: function(request, response) {
				var $data = { term: request.term, tipus:camp };
				$.getJSON(route, $data, response);
			},
			position: open,
			appendTo: appendSel,
			select: function( event, ui ){
				pob.val(ui.item.municipi);
				cp.val(ui.item.cp);	
				cp.trigger("blur.labelFx");

				//comarca.trigger("blur.labelFx");
				comarca.select2("destroy").select2( {} );
				comarca.val(ui.item.comarca);
				comarca.select2({
					minimumInputLength: 2,
					allowClear: true,
					placeholder: "Comarca",
				});
				//comarca.trigger("blur.labelFx");

				prov.select2("destroy").select2( {} );
				prov.val(ui.item.provincia);
				prov.select2({
					minimumInputLength: 2,
					allowClear: true,
					placeholder: "Província",
				});
				
			}
		};

		return $configs;
	};
	
	
	autocompleters = function( url, pob, cp, prov, comarca, appendSel ) {
		pob.autocomplete(autocompletersConfig(url, 'poblacio', pob, cp, prov, comarca,  { my : "left bottom", at: "left top", collision: "none" }, appendSel ));
		cp.autocomplete(autocompletersConfig(url, 'cp', pob, cp, prov, comarca, { my : "right top", at: "right bottom", collision: "flip" }, appendSel ));
	};
	
	actionsPersonaForm = function(origen) {
		$('#formpersona-button-remove').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $("#error-persona").html();
	        //var targetUrl = $(this).attr("href");
	
	        $("#dialeg").dialog({
	          	buttons : {
	            	"Confirmar" : {
	            		click: function() {
		    	        	$(this).dialog("close");
		    	        	//$("#formpersona").submit();	 // Submit form
		    	        	submitPerson("remove", origen);
	            		},
	            		text: "Confirmar",
		                class: "btn btn-default"
	            	},
	            	"Cancel·lar" : {
	            		click: function() {
		              		$(this).dialog("close");
		            	},
	            		text: "Cancel·lar",
		                class: "btn btn-default"
	            	}
	          	},
	        	title: "Confirmació per esborrar",
	        	height: 180,
	        	zIndex:	350
	        });
	
	        $("#dialeg").html("Segur que vols esborrar <br/>aquestes dades personals?");
	        $("#dialeg").dialog("open");
	    });   
		
		$('#formpersona-button-save').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $("#error-persona").html();
	        var i = '';
	        $("#dialeg").dialog({
	          	buttons : {
	            	"Confirmar" : {
	            		click: function() {
		    	        	$(this).dialog("close");
		    	        	if ($('#persona_mail').val() != "") {  // Múltiples adreces acceptades mail 1; mail 2; ...
		    	        		var mails = $('#persona_mail').val().split(";");
	
		    	        		for (i in mails) {
		    	        			if (mails[i].trim() != "" && !isValidEmailAddress( mails[i].trim() ) ) {
							        	dialegError("Error", "L'adreça de correu -"+mails[i]+"- no té un format correcte", 400);
										return false;
					   	        	}
								}
		    	        	}	
		    	        	if ($('#persona_id').val() != 0) {
		    	        		// Modificació no valida DNI
		    	        		submitPerson("save", origen);
		    	        	} else {
		    	        		/* Alex. Validar nou check estranger */
		    	        		/*var error = validarDadesPersona($("#persona_dni").val(), $("#persona_addrnacionalitat").val());*/
		    	        		var error = validarDadesPersona($("#persona_dni").val(), $("#persona_estranger").is(':checked'));
		    	        		if (error == "") {
		    	        			submitPerson("save", origen);
		    	        		} else {
		    	        			dialegError("Error", error, 400, 0);
		    	        		};
		    	        	};
		        	    	return false;  
	            		},
	            		text: "Confirmar",
	            		class: "btn btn-default"
	        		},
	            	"Cancel·lar" : {
	            		click: function() {
	            			$(this).dialog("close");
	            		},
	            		text: "Cancel·lar",
	            		class: "btn btn-default"
	            	}
	          	},
	        	title: "Desar les dades",
	        	height: "auto",
	        	width: 400,
	        	zIndex:	350
	        });
			
			var htmlRecordatori = "";
			if ($('#persona_id').val() == 0) {
				htmlRecordatori += "<p>Comproveu que el <strong>DNI és correcte</strong> i està ben escrit.<br/>Després no el podreu modificar.</p>";	
			}
			if ($('#persona_mail').val() == "") {
				htmlRecordatori += "<p>Recordeu que cal omplir <strong>l'adreça de correu</strong> per poder fer la tramitació</p>";	
			}
			if (htmlRecordatori == "") {
				htmlRecordatori = "<p>Confirmeu per desar les dades del federat</p>";
			}
	        $("#dialeg").html(htmlRecordatori);
	        $("#dialeg").dialog("open");
	        
	    });   
	};
	
	submitPerson = function(action, origen) {
		var form = $('#formpersona')[ 0 ];  // Equivalent to document.getElementById( "formpersona" )
		var formData = new FormData( form );
		
		if (origen === 'llicencia') {
			var part = { id : $("#parte_id").val(), dataalta: $("#parte_dataalta").val(), tipus: $('#parte_tipus').val(), club: $('#parte_club').val() };
	        var llic = { id : $('#parte_llicencies_id').val() };
	        formData.append( "parte",  JSON.stringify(part));
	        formData.append( "llicencia", JSON.stringify(llic));
	        formData.append( "club", $('#parte_club').val());
		}

		formData.append('action', action);
		formData.append('origen', origen);
	
	    $.ajax({
	        url: $('#formpersona').attr("action"),
	        type: $('#formpersona').attr("method"),
	        //dataType: "text",
	        data: formData,
	        processData: false,
	        contentType: false,
	        success: function (data, status)
	        {
	        	$('.mask').hide();

	        	$('#edicio-persona').hide();
	        	
				$("#edicio-persona").html("");
				
				$( window ).off('resize');
				
				if (origen === 'llicencia') loadLlicenciaData(data);
				else location.reload();  
				
	        },
	        error: function (xhr, status, error)
	        {
	        	var sms = smsResultAjax('KO', xhr.responseText);
	 			 
				$('#edicio-persona').show();
				 
				$("#error-persona").html(sms);
	        }
	    });  
		// CANVI PER ADAPTAR CÀRREGA FITXER
/*		
		$('#edicio-persona').hide();
		
		var url = $('#formpersona').attr("action");
		//var params = $('#formpersona').serializeArray();
		var params = $('#formpersona').serialize();

		if (origen == 'llicencia') {
			var part = { 'id' : $("#parte_id").val(), 'dataalta': $("#parte_dataalta").val(), 'tipus': $('#parte_tipus').val() };
	        var llic = { 'id' : $('#parte_llicencies_id').val() };
			
			//params.push( {'name':'parte','value':  part } );
			//params.push( {'name':'llicencia','value': llic} );
			params += '&'+$.param({ 'parte': part });
			params += '&'+$.param({ 'llicencia': llic });
		}
		
		//params.push( {'name':'action','value': action} );
		//params.push( {'name':'origen','value': (origenLlicencia?'llicencia':'assegurats')} );
		params += '&'+$.param({'action': action} );
		params += '&'+$.param({'origen': origen} );

		$.post(url, params,
		function(data, textStatus) {

			$('.mask').hide();
			
			$("#edicio-persona").html("");
			
			if (origen == 'llicencia') loadLlicenciaData(data);
			else location.reload();  
		}).fail( function(xhr, status, error) {
			 // xhr.status + " " + xhr.statusText, status, error
			 var sms = smsResultAjax('KO', xhr.responseText);
 			 
			 $('#edicio-persona').show();
			 
			 $("#error-persona").html(sms);
		     
		});
*/		
	};
	
	
	loadLlicencia = function(n) {
		if ($('#parte_tipus').val() == null || $('#parte_tipus').val() == '') {
			dialegError("Error", "Cal indicar un tipus de llista", 350, 100);
			return;
		}
		
		$('#main-col .alert').remove();
		
		var url = $("#formllicencia").attr("action");
        var tipusparte = $("#parte_tipus").val();
        var club = $("#parte_club").val();
        var altadata = $("#parte_dataalta").val();

        $('#formparte-llicencia').hide();
        
    	//if ($.browser.msie) $('#formparte-llicencia').hide(); 
    	//else $('#formparte-llicencia').stop().slideUp('fast');
    	
        $('#progressbar').show();  // Rellotge
        var part = { 'id' : $("#parte_id").val(), 'dataalta': altadata, 'tipus': tipusparte, 'club': club };
        var llic = { 'id' : n };
        
        $.get(url, { source_ajax: 'edit-llicencia', parte: part, llicencia: llic},
     	function(data, textStatus) {
        	
        	loadLlicenciaData(data);
		
        	
        }).fail( function(xhr, status, error) {
        	
			 // xhr.status + " " + xhr.statusText, status, error
			 var sms = smsResultAjax('KO', xhr.responseText);
			 
			 $('#progressbar').hide();  // Rellotge
		    	
			 $('#parte_tipus').val('');
			 
			 $('#formparte-llicencia').html(sms).show();
		     
		});
	};
	
	loadLlicenciaData = function(data) {
		if ($('#formparte-llicencia').not(':hidden')) {
			//$("#formparte-llicencia").stop().slideUp('fast');
			//$("#formparte-llicencia").stop().fadeTo("slow",0.6); 
			//$("#formparte-llicencia").stop().fadeIn(2000);
		};
		
    	$('#formparte-llicencia').html(data);
   	
    	$('#progressbar').hide();  // Rellotge
    	if ($.browser.msie) $('#formparte-llicencia').show(); 
    	else $('#formparte-llicencia').stop().slideDown('fast');
    	
    	// Reload DOM events. Add handlers again
    	$("select#parte_llicencies_persona_select").select2({
			minimumInputLength: 2,
			allowClear: true,
			placeholder: "Cercar federat",
		});

    	$("select#parte_llicencies_persona_select").change(function(e) {
			if (e.val == "") $('.formpersona-openmodal').html('nou assegurat <i class="fa fa-users"></i>');
			else $('.formpersona-openmodal').html('modifica assegurat <i class="fa fa-user"></i>');
		});
    	
    	//select all the a tag with name equal to modal
		$('.formpersona-openmodal')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        var url = $(this).attr("href");
	        var id = $("#parte_llicencies_persona_select").val();
	        if (id == "") id = 0;
	        url += '?id='+id+'&club='+$("select#parte_club").val();
	        
	        showPersonModal(url, 'llicencia', function () {
		        // Reload 
	        	$('#form_assegurats').submit();
	        });
	    });

    	addLlicenciaClick();
    	
    	//selectAllChecks();
    	
	    $("#formparte-llicencia .close").click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').stop().slideUp('fast');
	    });    
	};
	
	showResumParteDetall = function() {

	    $("#parte-resum-more-show").click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        
	        //$('#summary-data-hidden').toggle();
	        $('#summary-data-hidden').css('display', 'table');
/*
	    	if ($.browser.msie) $('#summary-data-hidden').show(); 
	    	else $('#summary-data-hidden').stop().slideDown('fast');*/
	    	$(this).hide();
	    	$("#parte-resum-more-hide").show();
	    });    

	    $("#parte-resum-more-hide").click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	    	if ($.browser.msie) $('#summary-data-hidden').hide(); 
	    	else $('#summary-data-hidden').stop().slideUp('fast');
	    	$(this).hide();
	    	$("#parte-resum-more-show").show();
	    });    
	};
	
	
	removeLlicenciaClick = function() {
	    $('a.remove-llicencia')
	    .off('click')
	    .click(function(e) {
	    	//Cancel the link behavior
	        e.preventDefault();
	        var llic = { id: $(this).attr('value') };
			
			//params.push( {'name':'parte','value':  part } );
			//params.push( {'name':'llicencia','value': llic} );
	        var params = $.param({ 'action' : 'remove' });
			params += '&'+$('#formparte').serialize();
			params += '&'+$.param({ 'llicencia': llic });
	        
	        var url = $(this).attr('href');
	        
	        var strHtml = '<p>Segur que vols esborrar aquesta llicència?</p>';
			/*strHtml += "	<div class='row'>";
			strHtml += "		<div class='col-md-12'>";
			strHtml += "			Data factura anul·lació";
			strHtml += "		</div>";
			strHtml += "	</div>";
			strHtml += "	<div class='row'>";
			strHtml += "		<div class='col-md-8'>";
			strHtml += "			<div class='input-group'>";	
			strHtml += "				<span class='input-group-addon'></span>";
			strHtml += "				<input type='text' id='datafacturacio' name='datafacturacio' class='form-control form-control-center'>";
			strHtml += "				<span class='input-group-addon input-group-addon-icon open-calendar'><span class='fa fa-calendar fa-1x'></span></span>";
			strHtml += "			</div'>";
			strHtml += "		</div>";
			strHtml += "	</div>";*/
			
			
			dialegConfirmacio(strHtml, 'Confirmació baixa llicència', 'auto', 400, function() { 
				
	        	$('#progressbar').show();  // Rellotge
	        	
	        	//params += '&'+$.param({ 'datafacturacio': $('#datafacturacio').val() });
	        	
	 	        $.post(url, params,
	 	        	function(data, textStatus) {
	 	        	
	 	        	$('#progressbar').hide();
	 	        	
	 	        	$("#llista-llicencies").html(data);
	 	        	removeLlicenciaClick();
	 	        	showResumParteDetall();
	 	        	//sortLlista("col-listheader", "list-data");
	 	        	// Hide llicencia
	 		    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	 		    	else $('#formparte-llicencia').stop().slideUp('fast');
	 		    	
	 		    	//closeDialegConfirmacio();
	 		    	if ( $('#llicencia-id').length == 0) {
	 		    		// Cap llicència, tornar a partes
	 		    		window.location = $(".link-parent").attr('href');
	 		    	} else {
	 		    		location.reload();
	 		    	}
	 		    	
	 	        }).fail( function(xhr, status, error) {
					 // xhr.status + " " + xhr.statusText, status, error
					 var sms = smsResultAjax('KO', xhr.responseText);

					 $('div.alert').remove();
					 
					 $('#progressbar').hide();  // Rellotge
				    
				     $("#llista-llicencies").prepend(sms);
				});
				 
			}, function() { closeDialegConfirmacio(); }, function() { 

				/*var current = new Date();
				
	    		initDateTimePicker ( 
	    			$( '#datafacturacio' ), 
	    			new Date( current.getFullYear(), 1, 1), // min
	    			new Date( current.getFullYear(), current.getMonth(), current.getDate() + 2), // max
	    			current, 
	    			'datafacturacio-picker', 
	    			false,
	    			function () { }
	    		);
				
	    		$('#datafacturacio').datetimepicker({value: current.getDayFormatted()+'/'+current.getMonthFormatted()+'/'+current.getFullYear()});*/
			});
	        
	    });
	};
	
	addLlicenciaClick = function() {
	    $('#formllicencia-add')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        
	        $('.alert-dismissible').remove();
	        
	        var url = $("#formllicencia").attr("action");
			if ($("#parte_llicencies_persona_select").val() == "") {
				dialegError("Error", "cal seleccionar una persona de la llista d'assegurats", 400);
				return false;
			}
			
			$('#progressbar').show();  // Rellotge
			
			var paramsParte = $('#formparte').serializeArray();
			var paramsLlicencia = $('#formllicencia').serializeArray();
			var params = $.merge(paramsParte, paramsLlicencia);
			params.push( {'name':'action','value': 'persist'} );
			
			$.post(url, params, function(data, textStatus) {
				
				$('#formparte-llicencia').hide(); 
				
				$('#progressbar').hide();  // Rellotge
		    	
		    	$("#llista-llicencies").html(data);
			
	        	if ($("#parte_id").val() == 0 && $(".header-llicenciaparteid").length)	{
	        		
	        		url = $("#formparte").attr("action")+'?id='+$(".header-llicenciaparteid").html();
	        		
	        		window.location = url;
	        		
	        		/*
	        		// Creació del parte si no hi ha error. reload
					$("#parte_id").val($(".header-llicenciaparteid").html());
	        		
	        		// Parte nou creat, deixa només el tipus de parte seleccionat
					$("#parte_tipus option:not(:selected)").each(function(i, item){
						$(item).remove();
					});
					$('#formparte-novallicencia').show();

					// Parte nou creat, desactiva data
					$("#formparte-dataalta img").hide();
					
					var hrefpagament = $("#parte-pagament a").attr("href") + "?id=" + $("#parte_id").val();
					var hrefpartetopdf = $("#parte-to-pdf a").attr("href") + "?id=" + $("#parte_id").val();
					var hrefalbaratopdf = $("#albara-to-pdf a").attr("href") + "?id=" + $("#parte_id").val();

					$("#parte-pagament a").attr("href", hrefpagament);
					$("#parte-to-pdf a").attr("href", hrefpartetopdf);
					$("#albara-to-pdf a").attr("href", hrefalbaratopdf);

					$(".buttons-top").show();
	        		 	*/
					//window.location = window.location.pathname + '?id=' + $(".header-llicenciaparteid").html(); 
	        	};
		    	
				removeLlicenciaClick();
	        	
				showResumParteDetall();
	        	
				//sortLlista("col-listheader", "list-data");
			}).fail( function(xhr, status, error) {
				 // xhr.status + " " + xhr.statusText, status, error
				
				 var sms = smsResultAjax('KO', xhr.responseText);
				 
				 $('#progressbar').hide();  // Rellotge
			    	
				 $("select#parte_llicencies_persona_select").select2("data", "");

				 $('.formpersona-openmodal').html('nou assegurat <i class="fa fa-users"></i>'); // Updates botó modifica => nou
				 
			     $("#edicio-llicencies").prepend(sms);
			});
	    });
	};

	
	transportCistella = function() {
		$( ".cistella-resum" ).on( "click", "#form_transport_0", function(e) {
			var totalNet = $('#form_importnetcomanda').val()*1 + $('#form_tarifatransport').val()*1; 
			$('#totalnetcomanda').html(  totalNet.toFixed(2) );
			var total = $('#form_importcomanda').val()*1 + $('#form_tarifatransport').val()*1; 
			$('#totalcomanda').html(  total.toFixed(2) );
		});
		
		$( ".cistella-resum" ).on( "click", "#form_transport_1", function(e) {
			//e.preventDefault();			
			var totalNet = $('#form_importnetcomanda').val()*1;
			$('#totalnetcomanda').html( totalNet.toFixed(2) );
			var total = $('#form_importcomanda').val()*1;
			$('#totalcomanda').html( total.toFixed(2) );
		});
	};
	
	
	pagamentComandaSMS = function(admin, iban, ibanescola) {
		
		var dialegHtml = '';
				
		dialegHtml += "<div class='sms-pagament row'> ";
		
		if (admin == true) {
			dialegHtml += "   <div class='col-md-6'>";
			dialegHtml += "   	<div class='form-group'>";
			dialegHtml += "     	<label for='comanda_datafactura'>Factura</label>";
			dialegHtml += "      	<div class='input-group'>";
			dialegHtml += "				<span class='input-group-addon'>Data</span>";
			dialegHtml += "	        	<input type='text' id='datafacturacio' class='form-control form-control-center' />";
			dialegHtml += "	        	<span class='input-group-addon input-group-addon-icon open-calendar'><span class='fa fa-calendar fa-1x'></span></span>";
			dialegHtml += "      	</div>";
			dialegHtml += "   	</div>";
			dialegHtml += "   </div>";
			dialegHtml += "   <div class='col-md-12'>";
			dialegHtml += "   	<div class='form-group'>";
			dialegHtml += "     	<label for='comanda_comptefactura'>Núm. Compte</label>";
			dialegHtml += "      	<div class='input-group'>";
			dialegHtml += "				<span class='input-group-addon'>IBAN</span>";
			dialegHtml += "	         	<select id='comptefactura' class='form-control'>";
			dialegHtml += "	            	<option value='"+iban+"'>Núm compte general: "+iban+"</option>";
			dialegHtml += "	              	<option value='"+ibanescola+"'>Núm compte escola: "+ibanescola+"</option>";
			dialegHtml += "          	</select>";
			dialegHtml += "      	</div>";
			dialegHtml += "   	</div>";
			dialegHtml += "   </div>";
		} else {
			dialegHtml += "   <div class='col-md-12'>";	
			dialegHtml += "		<p>Per pagar la comanda ";
			dialegHtml += " 	pot fer la transferència en qualsevol moment al número de compte:</p> "; 
			dialegHtml += "   	<p>"+iban+"</p> ";
			dialegHtml += "   	<ul><li>Amb targeta de crèdit o dèbit</li> ";
			dialegHtml += "     	<li>Amb un compte de \'La Caixa\'</li> ";
			dialegHtml += "      	<li>Mitjançant transferència des d\'una altra entitat</li></ul> ";
			dialegHtml += "   	<p>Gràcies</p>";
			dialegHtml += "   </div>";
		}
		
		dialegHtml += "   <div class='col-md-12'>";	
		dialegHtml += "   	<div class='form-group'>";
		dialegHtml += "     	<label for='comanda_comentaris'>Comentaris</label>";
		dialegHtml += "      	<div id='formcomanda-comentaris'>";
		dialegHtml += "	        	<textarea class='form-control' rows='3' name='comanda[comentaris]' id='comanda_comentaris'></textarea>";
		dialegHtml += "      	</div>";
		dialegHtml += "   	</div>";
		dialegHtml += "   </div>";
		dialegHtml += "</div>";
		
		return dialegHtml;
	};
	
	
	pagamentLlicenciesSMS = function(iban) {
		return "<div class='sms-pagament'><p>Si <b>NO</b> té intenció de pagar la totalitat de les llicències ara, "+ 
    		"no continuï, pot fer la transferència en qualsevol moment al número de compte:</p> "+ 
			"<p>"+iban+"</p> "+
			"<p>I rebrà al seu club les llicències i la factura.</p> "+
    		"<p>Si vol realitzar el pagament ara, ho pot fer</p> "+
    		"<ul><li>Amb targeta de crèdit o dèbit</li> "+
    		"<li>Amb un compte de \'La Caixa\'</li> "+
    		"<li>Mitjançant transferència des d\'una altra entitat</li></ul> "+
    		"<p>Gràcies</p></div>";
	};
	
	tramitarPagamentButton = function(selector, iban) {
		
		$( selector ).click(function(e) {
			e.preventDefault();
			
			var url = $(this).attr("href");
			$("#dialeg").dialog({
	          	buttons : {
	            	"Continuar" : { 
	            		click: function() {
		    	        	$(this).dialog("close");
		    	        	
		    	        	window.location = url;
		            	},
		            	text: "Continuar",
		                class: "btn btn-default"
		            },
		            "Sortir" : {
		            	click: function() {
			    			//Cancel submit behavior
			            	$(this).dialog("close");
			            },
		            	text: "Sortir",
		                class: "btn btn-default"
		            }	
		        },
		        title: "Abans de continuar...",
		        height: 'auto',
		        width: 550,
		        zIndex:	350
		    });
		
		    $("#dialeg").html( pagamentLlicenciesSMS( iban ) );
		    
		    $("#dialeg").dialog("open");
		});
	};
	
	showLlicenciesParte = function() {
	    //Carrega i mostra historial per un assegurat
		$('.llista-llicencies')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();

	        var current = $(this).parents(".data-detall");
	        
	        if (current.hasClass('data-detall-llicencies')) {
	        	current.removeClass('data-detall-llicencies');
		        if ($.browser.msie) current.children('.parte-llicencies').hide(); 
		    	else current.children('.parte-llicencies').stop().slideUp('slow');

	        } else {
	        	current.children('.parte-llicencies').remove();
		        var url = $(this).attr("href");
		        $.get(url, function(data, textStatus) {
		        	
		        	current.addClass('data-detall-llicencies');
		        	current.append(data);
			        if ($.browser.msie) current.children('.parte-llicencies').show(); 
			    	else current.children('.parte-llicencies').stop().slideDown('slow');

			        //if close button is clicked
				    $('.parte-llicencies .close').click(function (e) {
				        //Cancel the link behavior
				        e.preventDefault();
				        current.removeClass('data-detall-llicencies');
				        if ($.browser.msie) current.children('.parte-llicencies').hide(); 
				    	else current.children('.parte-llicencies').stop().slideUp('slow');
				    }); 
	        	});
	        };
	    });
	};
	
	removeParteLink = function () {
		$(".parte-action-remove").each(function() {
			var source = $(this);
			$(this).click(function(e) {
				e.preventDefault();
				
				$("#dialeg").dialog({
					autoOpen: false,
				    modal: true
			    });

				$("#dialeg").dialog({
		          	buttons : {
		            	"Confirmar" : {
		            		click: function() {
			    	        	$(this).dialog("close");
			    	        	document.location = source.attr('href');
			    	        },
			    	        text: "Confirmar",
			                class: "btn btn-default"
		            	},
			            "Cancel·lar" : {
			            	click: function() {
				    			//Cancel submit behavior
				            	$(this).dialog("close");
				            },
				            text: "Cancel·lar",
			                class: "btn btn-default"
			            }
			        },
			        title: "Confirmació per esborrar",
			        height: 180,
			        zIndex:	2999
			    });
				
			    $("#dialeg").html("Segur que vols esborrar <br/>aquesta llista?");
			    $("#dialeg").dialog("open");
			});
		});
		
	};

	validarDadesPersona = function(dni, estranger) {
		/* Només valida si nacionalitat és Espanyola */
		/*if (nacionalitat == "ESP") {*/
		
		if (!estranger) {
			var JuegoCaracteres="TRWAGMYFPDXBNJZSQVHLCKET";
			
			/* Si comença per X acceptar tot */
			if (dni.substring(0,1) == 'X' || dni.substring(0,1) == 'x')
				return "Cal indicar que es tracta d'un document de nacionalitat estranger";  
			
			if(dni.length < 9) return "Format DNI incorrecte, menor de 9 dígits, potser cal omplir a zeros per l'esquerra \nExemples:12345678X o P12345678X o M12345678X";  
			if(dni.length > 10) return "Format DNI incorrecte, major de 10 dígits \nExemples:12345678X o P12345678X o M12345678X";
			
			var dniprefix;
			var dninum;
			var dnillletra;
			
			if(dni.length == 9) {  // DNI normal
				dninum = dni.substring(0,8);
				dnillletra = dni.substring(8);
			} else { // DNI menor
				dniprefix = dni.substring(0,1);
				dninum = dni.substring(1,9);
				dnillletra = dni.substring(9);
				if (dniprefix != 'P' && dniprefix != 'M') return "Format DNI incorrecte, per a menors indicar prefix 'P' o 'M'" +
																"\nsegons sigui del pare o de la mare\nExemples:P12345678X o M12345678X";
			}
			if (isNaN(dninum )) return  "Format DNI incorrecte, error en la part numèrica\nExemples:12345678X o P12345678X o M12345678X";
			
			dnilletracalculada = JuegoCaracteres.charAt(dninum % 23);
			
			if (!isNaN(dnillletra ) || dnillletra != dnilletracalculada) 
				return "Format DNI incorrecte, error en la lletra.\n" +
						"El valor esperat era " + dnilletracalculada;
			
	        return "";
		}
		return "";
		
	};

	validarRenovarNoBuida = function() {
		$("#formrenovar-button-renovar").click(function(){
			if ($(".renovar-checkbox:checked").length == 0) {
				alert("Cal seleccionar alguna llicència per renovar");
				return false;
			}
		});
	};
	
	calcularPreuRenovar = function() {
		reloadPreuRenovar();
		
		$(".renovar-checkbox").click(function(){
			reloadPreuRenovar();
		});
	};

	reloadPreuRenovar = function() {
		var total = 0;
		$(".renovar-checkbox:checked").each(function() {
			var preu = $(this).attr("preu");
			if (!isNaN(preu)) total += parseFloat(preu);
		});
		$("#parte-preu-valor").html(total.toFixed(2));

		return total;
	};  
	
	ordenarLlista = function() {
		$(".col-listheader").click(function(){
			SORTER.sort(".list-data");
		});
	};
	
	/*****************************************************************************************************************/
	
	
	/************************************** Recents i  Enviament Llicències Digitals *********************************/
	
	recentsParams = function() {
    	var params = []; 
    	params.push( {'name':'clubs','value': $('#form_clubs').val()} );
    	params.push( {'name':'estat','value': $('#form_estat').val()} );
    	
    	params.push( {'name':'numrebut','value': $('#form_numrebut').val()} );
    	params.push( {'name':'anyrebut','value': $('#form_anyrebut').val()} );
    	params.push( {'name':'numfactura','value': $('#form_numfactura').val()} );
    	params.push( {'name':'anyfactura','value': $('#form_anyfactura').val()} );

    	params.push( {'name':'dni','value': $('#form_dni').val()} );
    	params.push( {'name':'nom','value': $('#form_nom').val()} );
    	params.push( {'name':'mail','value': $('#form_mail').val()} );
    	
    	params.push( {'name':'baixa','value': ($('#form_baixa').is(':checked'))?1:0} );
    	params.push( {'name':'nopagat','value': ($('#form_nopagat').is(':checked'))?1:0} );
    	params.push( {'name':'noimpres','value': ($('#form_noimpres').is(':checked'))?1:0} );
    	params.push( {'name':'noenviat','value': ($('#form_noenviat').is(':checked'))?1:0} );
    	params.push( {'name':'compta','value': ($('#form_compta').is(':checked'))?1:0} );
    	
    	//params.push( {'name':'nosincro','value': ($('#form_nosincro').is(':checked'))?1:0} );
    	
    	return params;
    };


    obrirTaulaSortidaLlicencies = function(url, urlFiltre, title, txtSubmit, callbackSubmit) {
    	$('.alert.alert-dismissible').remove();
    	
    	$.get(url, function(data) {
    		
    		$("#dialeg").html(data);
    					
    		var ewidth = $(window).width()*0.8;
    		if (ewidth > 840) ewidth = 840;
    		var eheight = $(document).height()*0.8;
    					
    		$("#dialeg").dialog({
    			buttons :[
    			            {
    			              text: txtSubmit,
    			              click: callbackSubmit
    						},
    						{
    				          text: "Cancel·lar",
    				          click: function() {
    				        	  $(this).dialog("destroy");
    				          }
    						}
    			          ], 
    		    show: 1000,
    		    modal: true,
    		    resizable: true,
    		    width: ewidth,
    		    height: eheight,
    		    minWidth: 400,
    		    title: title
    		 });

    		eventsTaulaSortidaLlicencies();
    		
    		eventFilterTable("#formfederatssortida", "#table-federats", urlFiltre);
    		
    	}).fail( function(xhr, status, error) {
    		 var sms = smsResultAjax('KO', xhr.responseText);
    		 
    		 $("#list-forms").prepend(sms);
    	});
    
    };
    
    eventsTaulaSortidaLlicencies = function() {
    	$( "#dialeg" ).on( "click", "#form_checkall", function() {
    		var checked = $(this).is(':checked');
    	
    		if (checked) {
    			$('.checkbox-federat').not(".checkbox-federat[disabled='disabled']").each(function(){ this.checked = true; });				
    		} else {
    			$('.checkbox-federat').not(".checkbox-federat[disabled='disabled']").each(function(){ this.checked = false; });
    		};
    	});

    	$( "#dialeg" ).on( "click", ".desar-email", function(e) {
    		e.preventDefault();
    		
    		$('.alert.alert-dismissible').remove();

			var input = $(this).parent().prev().find('input');
        	var email = input.val();
        	var url = $(this).attr("href")+'&mail='+email;
        	input.removeClass('form-control-changed');
        	
        	$.get(url, function(data) {
        		input.attr('data-value-init', email);
        		input.addClass('form-control-saved');
        		input.removeClass('form-control-error');

        		var sms = smsResultAjax('OK', 'Adreça de correu actualitzada correctament');
          		 
        		$("#table-federats").prepend(sms);
        	}).fail( function(xhr, status, error) {
        		 // xhr.status + " " + xhr.statusText, status, error
        		input.addClass('form-control-error');
        		input.removeClass('form-control-saved');
        		 
        		var sms = smsResultAjax('KO', xhr.responseText);
        		 
        		$("#table-federats").prepend(sms);
        	});
    		
    	});

    	$( "#dialeg" ).on( "change", "input.form-control", function() {

		//$( '#dialeg input.form-control' ).change(function(e) {
			var initValue = $(this).attr('data-value-init');
			if (typeof initValue === 'undefined') initValue = "";
			
			if ($(this).val() == initValue) $(this).removeClass('form-control-changed');
			else $(this).addClass('form-control-changed');
		});
    	
    	
    	//sortLlista("col-listheader", "list-data");
    
    	var tableScroll = $('.table-scroll');
    	if (tableScroll.hasOverflowY()) {
    		$('.table-scroll').css({"width":"101.5%"});
    	}
    };
    
    submitEnviarLllicencies = function( urlCallback, callbackOk ) {
    	var url = $('#formfederatssortida').attr("action");
    	var params = $('#formfederatssortida').serializeArray();
    
    	obrirMascaraBlock( '#table-federats' );
    	
    	$.post(url, params, function(data) {
    
    		var sms = smsResultAjax('OK', data);
    
    		callbackOk( urlCallback );
    
    		$("#list-forms").prepend(sms);
    
    		$("#dialeg").dialog("destroy");
    	}).fail( function(xhr, status, error) {
    		 // xhr.status + " " + xhr.statusText, status, error
    		 var sms = smsResultAjax('KO', xhr.responseText);
    		 
    		 $("#table-federats").prepend(sms);
    
    		 tancarMascaraBlock( '#table-federats' );
    	});
    };
    
    
    eventFilterTable = function (containerSel, taulaSel, url) {
    
    	var timeout;
    	// Camp de text per a filtre. Detect input filtre. Si text = '' o text >= 3 sends ajax call		
    	$(containerSel).on( 'input', '#form_filtre', function(e) {
    		if(timeout) {
    		    clearTimeout(timeout);
    		    timeout = null;
    		}
    					
    		var filtre = $(this).val(); 
    		var llicenciesId = $('#form_llicenciesid').val(); 
    					  
    		if ( $(this).data('lastval') != filtre ) {
    			 
    			$(this).data('lastval', filtre);
    
    		    if (filtre == '' || filtre.length >= 3) {
    		    	obrirMascaraBlock(  taulaSel );
    
    		    	$('.alert.alert-dismissible').remove();
    		    	
    		    	timeout = setTimeout( function() {
    					
    					//url += '&filtre='+filtre+'&llicenciesid'+llicenciesId;
    
    					var params = $('#formfederatssortida').serializeArray();
					
    					$.post(url, params, function(data) {
    
    						$(taulaSel).remove();
    						$(containerSel).append(data);
    
    						tancarMascaraBlock( taulaSel );
    					}).fail( function(xhr, status, error) {
    						 var sms = smsResultAjax('KO', xhr.responseText);
    						 
    						 $(containerSel).prepend(sms);
    
    						 tancarMascaraBlock( taulaSel );
    					});	
    					
    				}, 1000);
    			}
    		};
    	});
    }

    printTaulaRecents = function( url ) {
    
    	$('.alert.alert-dismissible').remove();
    	
    	var params = recentsParams();
    
    	for ( var i in params ) url=url+'&'+params[i].name+'='+params[i].value;
    	
    	$.get(url, function(data) {
    
    		$("#list-recents").html(data);
    
    	}).fail( function(xhr, status, error) {
    		 // xhr.status + " " + xhr.statusText, status, error
    		 var sms = smsResultAjax('KO', xhr.responseText);
    		 
    		 $("#list-forms").prepend(sms);
    	});
    };
	
    /*****************************************************************************************************************/	
	
	/*************************************************** Clubs *******************************************************/	
	
	autocompletersNomsClub = function(routeid, clubid, codiid) {
		/* Funció obsoleta */
		var route = $("#"+routeid).attr("href");
		var $configs = {
			source: function(request, response) {
				var $data = {term: request.term};
				$.getJSON(route, $data, response);
			},
			position: { my : "left top", at: "left bottom", collision: "none" },
			appendTo: "#list-forms",
			select: function(event, ui) {
				$("#"+clubid).val(ui.item.nom);
				$("#"+codiid).val(ui.item.codi);
			}
		};
		$("#"+clubid).autocomplete($configs);
	};
	
	reloadClub = function() {
		/* canvi de club */
		
		$('#club_clubs').change(function() {
			var url = $("#formclub").attr("action");
			window.location = url + '?codiclub=' + this.value;
		});
	};

	
	copiarAdrecaClub = function() {
		
		$('#formclub-copiaradreca')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        
	        $("#club_addrpobcorreu").val( $("#club_addrpob").val() );
	        $("#club_addrcpcorreu").val( $("#club_addrcp").val() );
	        $("#club_addrcomarcacorreu").val( $("#club_addrcomarca").val() ).trigger("change");
	        $("#club_addrprovinciacorreu").val( $("#club_addrprovincia").val() ).trigger("change");
	        $("#club_addradrecacorreu").val( $("#club_addradreca").val() );
	    });
	};
	
	
	saveClub = function( admin, personaFisica ) {
		/* desar club */
		$('.formclub-save')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        
	        if ($("#club_codi").val() == "") {
	        	dialegError("Error", "cal indicar el codi del club", 400, 0);
				return false;
	        }
	        if ($("#club_nom").val() == "") {
	        	dialegError("Error", "cal indicar el nom del club", 400, 0);
				return false;
	        }
	        if ($("#club_cif").val() == "") {
	        	dialegError("Error", "cal indicar el cif del club", 400, 0);
				return false;
	        }

	        if (admin == true) {
		        if ($("#club_compte").val() == "" || isNaN($("#club_compte").val())) {
		        	dialegError("Error", "cal indicar el compte comptable", 400, 0);
		        	$( "#tabs-club" ).tabs( "option", "active", 2 );
					return false;
		        } else {
			        if ($("#club_compte").val().length != 7 ) {
			        	dialegError("Error", "el compte comptable té un format incorrecte", 400, 0);
			        	$( "#tabs-club" ).tabs( "option", "active", 2 );
						return false;
			        }
		        }
	        }
	        
	        if ($("#club_mail").val() == "") {
	        	dialegError("Error", "cal indicar el mail del club", 400, 0);
				return false;
	        }
	        if ($("#club_telefon").val() != "" && isNaN($("#club_telefon").val())) {
	        	dialegError("Error", "El telèfon ha de ser numèric", 400, 0);
				return false;
	        }
	        if ($("#club_fax").val() != "" && isNaN($("#club_fax").val())) {
	        	dialegError("Error", "El fax ha de ser numèric", 400, 0);
				return false;
	        }
	        if ($("#club_mobil").val() != "" && isNaN($("#club_mobil").val())) {
	        	dialegError("Error", "El mòbil ha de ser numèric", 400, 0);
				return false;
	        }
	        if ($("#club_addrcp").val() != "") {
	        	if (isNaN( $("#club_addrcp").val() ) ) {
    	        	dialegError("Error", "El codi postal ha de ser numèric", 400, 0);
	        		return false;
	        	} else {
	        		if ($("#club_addrcp").val().length != 5) {
	    	        	dialegError("Error", "El codi postal ha de tenir 5 dígits", 400, 0);
		        		return false;
	        		}
	        	}
	        }
	       
	        if (admin && !personaFisica && $("#club_tipusparte :selected").length < 1) {
	        	dialegConfirmacio( "El club no té assignat cap tipus de llicència", "Abans de continuar...", 0, 400, function() {
	        		$('#formclub').submit();
	        		
	        		closeDialegConfirmacio();
	        		
		        }, function() {
		        	$( "#tabs-club" ).tabs( "option", "active", 1 );
		        	closeDialegConfirmacio();
		        	
		        }, function() { });
	        	
			} else {
				$('#formclub').submit();
			}
	    });
	};

	nouClub = function() {
		/* desar club */
		$('#formclub-nou')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        var url = $("#formclub").attr("action");
			window.location = url + '?action=nouclub';
		});
	};
	
	
	addUserRoleClick = function( urlJSONpersona, keysCercaPersona, roltecnic ) {
		// delegate
		$( "#gestio-usuarisclub" ).on( "click", "#add-userclub, .add-userroleclub", function( e ) {
			//Cancel the link behavior
	        e.preventDefault();

	        $('.alert.alert-dismissible').remove();
	        
	        var url = $(this).attr("href")+'&club='+$('#club_codi').val();
	        var userid = $(this).attr("data-id");
	        var role = $(this).attr("data-role");
	        var userMail = $(this).attr("data-user");
	        
	        var afegirRolUserExistent = userid != ''?true:false; 
    		var afegirUserNou = !afegirRolUserExistent;
	        
	        $.get(url, function(data, textStatus) {
	        	// Open dialog New User
	        	
	        	dialegConfirmacio(data, 'Afegir usuari', 'auto', 740, function() {
	        		//callbackok

					$('.alert').remove();

	        		if (afegirRolUserExistent) {
	        			// Afegir role a usuari existent
	        			
	        			/*if ($("#user_auxinstructordni").val().indexOf( $("#user_user").val() ) === -1) {
	        				$("#formuserclub").prepend(smsResultAjax('KO', "No coincideixen les adreces de correu"));
							return false;
		        		}*/
	        			$("#user_pwd_first").val("");
	        			$("#user_pwd_second").val("");
	        			
	        		} else {
	        			// Afegir usuari
		        			
		        		if ($("#user_user").val() == "") {
		        			if ($("#user_user").attr("readonly")) $("#formuserclub").prepend(smsResultAjax('KO', "Cal escollir una persona"));
		        			else $("#formuserclub").prepend(smsResultAjax('KO', "Cal indicar el mail de l'usuari"));
        			
		        			return;
		        		}			
							
				        if ( !isValidEmailAddress( $("#user_user").val() ) ) {
				        	$("#formuserclub").prepend(smsResultAjax('KO', "L'adreça de correu "+$("#user_user").val()+" no té un format correcte"));

				        	
				        	return;
			        	}
				        
				        if ($("#user_pwd_first").val() == "" || $("#user_pwd_second").val() == "") {
				        	$("#formuserclub").prepend(smsResultAjax('KO', "cal indicar la clau l'usuari"));

				        	
				        	return;
				        }
				        if ($("#user_pwd_first").val() != $("#user_pwd_second").val()) {
				        	$("#formuserclub").prepend(smsResultAjax('KO', "Les claus no coincideixen"));

				        	
				        	return;
				        }
	        		}

	        		var params = $('#formuserclub').serializeArray();
	        		
	        		var urlSubmit = $('#formuserclub').attr('action'); 
	        		
					$.post(urlSubmit, params,
					function(data, textStatus) {
				    	$("#llista-usuarisclub").html(data);

				    	reloadScrollTable($('.table-scroll'), $('.table-header'), $('.col-listheader'), $('#header-userclubactions'));
				    	
	        			closeDialegConfirmacio();
				    	
					}).fail( function(xhr, status, error) {
		        		// xhr.status + " " + xhr.statusText, status, error
			        	var sms = smsResultAjax('KO', xhr.responseText);
			    			 
			        	$("#formuserclub").prepend(sms);
			        	return;
		        	});

					
					
	        	},function() {
	        		//callbackko
	        		
	        		closeDialegConfirmacio();
	        		
	        	},function() {
	        		//callbackopen
		    		
	        		$("#user_auxinstructordni").removeAttr('readonly');
		    		
		    		initSelectorPersones(urlJSONpersona, afegirRolUserExistent, afegirUserNou, roltecnic);		    		
		    		
		    		if (afegirRolUserExistent) {
		    			// Afegir role a usuari existent
		    			$('#user_id').val( userid );
		    			$('#user_user').val( userMail );
		    			$("select#user_role").val( role );
		    			$("select#user_role").attr('readonly', 'readonly');  
		    			
	        			$("#user_pwd_first").val("");
	        			$("#user_pwd_second").val("");
		    			
		    			if (keysCercaPersona.includes( role )) {
		    				$("#user_user").attr('readonly', 'readonly');	// Cerca no es pot canviar
		    				$("#user_auxinstructordni").select2("readonly", false);			// // Cerca no es pot canviar
		    				
							$("#user_auxinstructordni").select2("search", userMail);  // Executar cerca persones pel mail de l'usuari

		    			} else {
		    				$("#user_user").removeAttr('readonly');
		    				$("#user_auxinstructordni").select2("readonly", true);
		    				
		    			}
		    			
		    			
		    			
		    			$('.form-user-password-manual, .form-user-password-random, #formuserclub-random, #formuserclub-manual').hide();

		    		} else {
		    			// Afegir usuari
		    			randomPwdClick();

			    		manualPwdClick();
		    			
			    		$("#user_user").removeAttr('readonly');
			    		//$("#user_auxinstructordni").attr('readonly', 'readonly');
			    		$("#user_auxinstructordni").select2("readonly", true);
			    		
			    		$("select#user_role").on("change", function(e) {
			    			$(".alert").remove();	
	
			    			$("#user_auxinstructordni").val('');
			    			
			    			initSelectorPersones(urlJSONpersona, afegirRolUserExistent, afegirUserNou, roltecnic);	
			    			
			    			$('#user_user').val('');
			    			// Canvi role 
			    			// 	Instructors 			=> activa selecció persona, desactiva mail
			    			//	Altres (Club, Admin)	=> desactiva selecció persona, activa mail
			    			if (keysCercaPersona.includes($(this).val())) {
			    				$("#user_user").attr('readonly', 'readonly');
			    				$("#user_auxinstructordni").select2("readonly", false);				
			    			} else {
			    				$("#user_user").removeAttr('readonly');
			    				$("#user_auxinstructordni").select2("readonly", true);
			    				
			    			}
			    		});
		    		
			    		setTimeout(function() {
			    			
			    			$('#formuserclub input.form-control').each( function( i ) {
			        			$(this).val('');
			        		});
			    	    },200);
		    		}
	        	});
	        
	        }).fail( function(xhr, status, error) {
        		// xhr.status + " " + xhr.statusText, status, error
	        	var sms = smsResultAjax('KO', xhr.responseText);
	    			 
	        	$("#llista-usuarisclub").prepend(sms);
        	});
	    });
	};
	
	
	initSelectorPersones = function( url, allowClear, userNou, roltecnic ) {

		// Crear select2
		var tecnic = $('#user_role').val() === roltecnic?1:0;
		url += '&club='+$('#club_codi').val();
		url += '&nom=1&mail=1&tecnic='+tecnic;  // Cercar per nom i mail
		url += '&desde='+getCurrentDate('/');  // data actual, per validar llicència
		url += '&fins='+getCurrentDate('/');  // data actual, per validar llicència
		
		//								elem_sel, 				placeholder, 			minInput, 	allowclear, 		url, callbackPropagateValues, selectionFunction, onclearingFunction, loadedFunction		    		
		init_cercapernomdnimail_JSON('#user_auxinstructordni', 'Cercar instructor per mail', 4, allowClear, url, 	// Cerca per mail sense opció clear
		function ( elem_sel, added ) {
			/*
			{"id":52052,"text":"52628669F-Alex2 MACIA PEREZ","nomcognoms":"Alex2 MACIA PEREZ","mail":null,"telf":"","nascut":"21/12/1972","poblacio":null,"nacionalitat":"ESP"}
			*/
			$(".alert.alert-dismissible").remove();
			if (added.mail == "") {  // Aquest instructor no té mail. => Avís
				var sms = smsResultAjax('KO', 'Cal indicar una adreça electrònica per aquesta persona');
					 
				 $("#formuserclub").prepend(sms);
				 
				 if (userNou) {
					 $("#user_auxinstructordni").val("");
				 }
				  
			} else {
				if (userNou) {
					$("#user_user").removeAttr('readonly'); // Permetre editar mail, poden existir múltiples
					$("#user_user").val(added.mail);
				}
			}
		}, function( item ) {
	    	//Selection format Function
	        return item.text+"-"+item.nomcognoms+(item.mail != ""?" ("+item.mail+")":"");
	    }, function( item ) {
	    	//Result format Function
	        return item.text+"-"+item.nomcognoms+(item.mail != ""?" ("+item.mail+")":"");
	    }, function( e ) {
			//  select2-clearing
	    	$("#user_user").attr('readonly', 'readonly');
	    	
		}, function( e ) {
			//  select2-loaded search for user mail and when loaded opens
			if (typeof e.items === "undefined") {
				// No hi ha resultats
				if (userNou) {
					var sms = smsResultAjax('KO', 'No s\'ha trobat cap persona amb aquest mail');
					$("#formuserclub").prepend(sms);
				}
			} else {
				/*
				 * e.items
				 * {"results":[{"id":30981,"text":"Oscar MONTEVERDE LIZANDRA","nom":"Oscar","cognoms":"MONTEVERDE LIZANDRA","dni":"44417698Y","mail":null},
				 * */
				if (!userNou) {
					$("#user_auxinstructordni").select2("open");
				}
			}
			
		});
	};
	
	
	actionsUserRolePwdClick = function( ) {
		//delegated
		$( "#llista-usuarisclub" ).on( "click", ".remove-userroleclub, .del-userclub, .reset-pwduserclub", function( e ) {
			//Cancel the link behavior
	        e.preventDefault();
	        var url = $(this).attr("href")+'&club='+$('#club_codi').val();
	        
	        var strHtml = '<p>Segur que vols '+$(this).attr('title').toLowerCase()+'?';	
	        dialegConfirmacio(strHtml, 'Confirmació', 'auto', 500, function() {
        		//callbackok
	        	
				$.get(url, function(data, textStatus) {
			    	$("#llista-usuarisclub").html(data);
			    	
			    	reloadScrollTable($('.table-scroll'), $('.table-header'), $('.col-listheader'), $('#header-userclubactions'));
			    	
			    	closeDialegConfirmacio();
			    	
				}).fail( function(xhr, status, error) {
	        		// xhr.status + " " + xhr.statusText, status, error
					closeDialegConfirmacio();
					
		        	var sms = smsResultAjax('KO', xhr.responseText);
		    			 
		        	$("#llista-usuarisclub").prepend(sms);
	        	});
        		
        	},function() {
        		//callbackko
        		
        		closeDialegConfirmacio();
        	},function() {
        		//callbackopen
        		
        	});

	    });
	};
	
	randomPwdClick = function() {
		/* Generate random Password */
	    $('#formuserclub-random')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        $('.form-user-password-manual').hide();
	        $('.form-user-password-random').show();
	        var password = randomPassword(8);
	        $('#user_randompwd').val(password);
	        $('#user_pwd_first').val(password);
	        $('#user_pwd_second').val(password);  
	        
	        $('#formuserclub-manual').show();
	    });
	};
	
	randomPassword = function (length) {
	    var iteration = 0;
	    var password = "";
	    var randomNumber;
	    
		if (length < 6) length = 6;
	    while(iteration < 3) {
	    	randomNumber = Math.floor(Math.random()*(122-97+1)+97); // minuscules
	        iteration++;
	        password += String.fromCharCode(randomNumber);
	    }
	    while(iteration < 6){
	    	randomNumber = Math.floor(Math.random()*(57-48+1)+48); // numeros
	        iteration++;
	        password += String.fromCharCode(randomNumber);
	    }
	    while(iteration < length){
	    	randomNumber = Math.floor(Math.random()*(90-65+1)+65); // majuscules
	        iteration++;
	        password += String.fromCharCode(randomNumber);
	    }
	    return password;
	};
	
	
	manualPwdClick = function() {
		/*  Allow manual Password */
	    $('#formuserclub-manual')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        $('.form-user-password-manual').show();
	        $('.form-user-password-random').hide();
	        
	        $('#user_randompwd').val('');
	        $('#user_pwd_first').val('');
	        $('#user_pwd_second').val('');
	        
	        $('#formuserclub-manual').hide();
	    });
	};
	/*****************************************************************************************************************/
	
	/*************************************************** Duplicats *******************************************************/	
	
	actionsFormDuplicats = function(url) {
		initFormDuplicats(true);
		
		$("select#duplicat_persona").select2({
			minimumInputLength: 2,
			allowClear: true,
			placeholder: "Seleccionar federat",
		});
		
		$("select#duplicat_persona").change(function(e) {
			initFormDuplicats(true);
			if (e.val != "") {
				/* Persona escollida. Carregar dades: dni, nom, cognoms */
				if ($.browser.msie) $('select#duplicat_carnet').show(); 
			    else $('select#duplicat_carnet').stop().slideLeftShow('slow');
			}
		});
		
		$("select#duplicat_carnet").change(function(e) {
			initFormDuplicats(false);
			if ($(this).val() != "") {
				var params = { 	persona:$("select#duplicat_persona").val(), carnet:$("select#duplicat_carnet").val() };
				$.get(url,	params,
				function(data) {
					//$("#formduplicats-titols").remove();
					
					$("#formduplicats-dades").replaceWith( data );

					/*var titols = $("#formduplicats-titols").detach(); // Colocar titols als camps superiors
					$("#formduplicats .row").first().append(titols);
					
					if ($.browser.msie) $('select#duplicat_titol').show(); 
				    else $('select#duplicat_titol').stop().slideLeftShow('slow');*/
					transportCistella();
					
					if ($.browser.msie) {
						$('#formduplicats .hidden-field').show();
					}
				    else {
						$('#formduplicats .hidden-field').stop().slideDown('slow');
				    }
					
					imageUploadForm("#duplicat_fotoupld", 104);
					
					prepareRemoveFotoGaleria();
					
					/*prepareRemoveFotoGaleria( "#formduplicats", function() {
						// Accions addicionals
						$('#duplicat_fotoupld').val( '' );
					});*/
					
					$('#duplicat_submit').click(function(e) {
						e.preventDefault();
						// Afegir transport
						if ($("#form_transport_0").is(":checked")) {
							$("#duplicat_transport_0").prop("checked", true);
						} else {
							$("#duplicat_transport_1").prop("checked", true);
						}
						
						// Validacions
						if ($('select#duplicat_titol').length > 0 && $('select#duplicat_titol').val() == "") {
							dialegError("Error", "Cal escollir un títol", 300, 100);
							return false;
						}
						if ($('input#duplicat_fotoupld').length > 0 && $('.galeria-upload .file-input-thumb').length == 0) {
							dialegError("Error", "Cal carregar una foto", 300, 100);
							return false;
						}
						if ($('input#duplicat_nom').val().trim() == "") {
							dialegError("Error", "Cal indicar el nom", 270, 100);
							return false;
						}
						if ($('input#duplicat_cognoms').val().trim() == "") {
							dialegError("Error", "Cal indicar els cognoms", 300, 100);
							return false;
						}
						
						$('#formduplicats').submit();
					});	
				}); 
			}
		});
	};

	
	initFormDuplicats = function(tot) {
		if (tot) {
			$("select#duplicat_carnet").val("");
			
			if ($.browser.msie) $('select#duplicat_carnet').hide(); 
		    else $('select#duplicat_carnet').stop().slideLeftHide('slow');
		}
		
		/*if ($.browser.msie) $('select#duplicat_titol').hide(); 
	    else $('select#duplicat_titol').stop().slideLeftHide('slow');*/
		
		if ($.browser.msie) $('#formduplicats .hidden-field').hide(); 
	    else $('#formduplicats .hidden-field').stop().slideUp('slow');
	};
	
	prepareRemoveFotoGaleria = function() {
		
		$( "#edicio-persona, #formduplicats" ).on( "click", ".remove-foto", function( e ) {
			//Cancel the link behavior
	        e.preventDefault();

	        $('.alert').remove();
	        
	        var url = $(this).attr('href');
			var galeria = $(this).parent('.galeria-remove-foto'); 

			if ($('#persona_foto').val() == '') {
				// Not saved
		        galeria.prev('input[type="file"]').val('');
	        
		        galeria.prev('.galeria-upload').html('<div class="image-upload"><span class="box-center-txt">Pujar foto<br/>(click)</span></div>');
	        
		        galeria.remove();

		        return false;
			}
			
			var strHtml = '<p>Segur que vols treure la foto?</p>';
			dialegConfirmacio(strHtml, 'Esborrar arxiu', 'auto', 400, function() {

				$.get(url, function(data) {
					// Remove
			        galeria.prev('input[type="file"]').val('');
		        
			        galeria.prev('.galeria-upload').html('<div class="image-upload"><span class="box-center-txt">Pujar foto<br/>(click)</span></div>');
		        
			        galeria.remove();

					$('.foto-persona').val( '' );
					
				}).fail( function(xhr, status, error) {
	        		// xhr.status + " " + xhr.statusText, status, error
		        	var sms = smsResultAjax('KO', xhr.responseText);
		    			 
		   			$('.block-errors').append(sms);
	        	});
				closeDialegConfirmacio();

				
			}, function() { closeDialegConfirmacio(); }, function() { });
		});
	};
	
	prepareRemoveFile = function() {
		
		$( "#edicio-persona" ).on( "click", ".remove-file", function( e ) {
	        //Cancel the link behavior
	        e.preventDefault();

	        var url = $(this).attr('href');
			var parentRow = $(this).parents('div.item-historial'); 

			var strHtml = '<p>Segur que vols esborrar aquest arxiu definitivament?</p>';
			dialegConfirmacio(strHtml, 'Esborrar arxiu', 'auto', 400, function() {

				$('.alert').remove();
				 
				$.get(url, function(data) {
					// Remove
					parentRow.remove();
					
				}).fail( function(xhr, status, error) {
	        		// xhr.status + " " + xhr.statusText, status, error
		        	var sms = smsResultAjax('KO', xhr.responseText);
		    			 
		   			$('.block-errors').append(sms);
	        	});
				closeDialegConfirmacio();
				 
			}, function() { closeDialegConfirmacio(); }, function() { });
		});
	};
	
	prepareFileInput = function (elem) {
		var parent = elem.parent();
		
		elem.change(function() {
	        var info  = '';

	   		// Display filename (without fake path)
	        var path = $(this).val().split('\\');
	        info     = path[path.length - 1];

	        parent.find(".upload-file-info").val(info);
	        
	        $(this).addClass('form-control-updated');
	       
	        // Add remove file option 
        	parent.find(".file-info").html('<a class="remove-file link" href="javascript:void(0);"><span class="fa fa-trash fa-1x red"></span></a>');
	        
	    });

		parent.find(".input-append").click(function(e) {
	        e.preventDefault();
	        // Make as the real input was clicked
	        elem.click();
	    });
		
		
	};
	
	imageUploadForm = function(formsel, imgwidth) {
		var galeria = $(formsel).next(".galeria-upload");
		galeria.click(function(e) {
		    e.preventDefault();
		    // Make as the real input was clicked
		    $(formsel).click();
	    });
		
		$(formsel).imagePreview({ galeria : galeria, multiple: false, textover: 'Canviar imatge', width: imgwidth });
	};
	
	
	$.fn.imagePreview = function(params){
		$(this).change(function(evt){
			if(typeof FileReader == "undefined") return true; // File reader not available.

			var fileInput = $(this), i = 0, f, files = evt.target.files; // FileList object
			//var total = 0;

			params.galeria.find(".image-uploaded").remove();  // Removes previous preview 

			// Loop through the FileList and render image files as thumbnails.
			for (i = 0, f; f = files[i]; i++) {

				// Only process image files.
				if (!f.type.match('image.*')) {
					continue;
				}
				var reader = new FileReader();
				
				// Closure to capture the file information.
				reader.onload = (function(theFile) {
					return function(e) {
						// Render thumbnail.
						//var imgHTML = '<img width="'+params.width+'" title="'+params.textover+'" alt="'+params.textover+'" class="file-input-thumb" src="' + e.target.result + '" title="' + theFile.name + '"/>';
						var imgHTML = '<img title="'+params.textover+'" alt="'+params.textover+'" class="file-input-thumb" src="' + e.target.result + '" title="' + theFile.name + '"/>';

						if( typeof params.galeria !== 'undefined' ){
							if (params.multiple === true) {
								/*
								$novaimatge = $('<div class="image-preview image-upload">' + imgHTML +'</div>');
								params.galeria.append($novaimatge);
								
								// Les imatges que encara no han pujat al servidor no es poden posar a la portada 
								$novaimatge.find("img").draggable({	
									 cancel: "a.ui-icon", // clicking an icon won't initiate dragging
									 revert: "valid", // when not dropped, the item will revert back to its initial position
									 containment: ".drag-container",  // Contenidor
									 helper: function( event ) {
										 return $( "<div class='ui-widget-header'>Esta imagen aún no se ha subido al servidor</div>" );
									 },
									 opacity: 0.7,
									 cursor: "move"
								});*/
								
								
							} else {
								params.galeria.html('<div class="image-upload image-uploaded">' + imgHTML +'</div>');
								hoverPortada( params.galeria.find(".image-uploaded") );
								
								addFotoActionsBottom(params.galeria);
							}
						}else{
							fileInput.before(imgHTML);
						}
					};
				})(f);

				// Read in the image file as a data URL.
				reader.readAsDataURL(f);
			}
		});
	};
	
	hoverPortada = function(hoverobject) {
		
		hoverobject.mouseenter( function(){
			$(this).addClass("border-highlight-blue");
		});
	
		hoverobject.mouseleave( function(){
			$(this).removeClass("border-highlight-blue");
		});
	};
	
	addFotoActionsBottom = function( galeria ) {
		//if ( galeria.next('.galeria-remove-foto').length === 0) {
		if ( galeria.siblings('.galeria-remove-foto').length === 0) {
			var htmlRemove = Array();
			htmlRemove.push('<div class="galeria-remove-foto">');
			htmlRemove.push(' 	<a class="remove-foto link" href="javascript:void(0);"><span class="fa fa-trash fa-1x gray"></span></a>');
			htmlRemove.push('</div>');
			galeria.parent().append( htmlRemove.join( '' ) );
		}
	};
	
	nouCursSelectClub = function( urlForm ) {
		$( ".nou-curs" ).click( function(e) {
			e.preventDefault();	
			var url = $(this).attr("href");
			
			$.get(urlForm, function(data) {
	        	
				$( '#dialeg' ).html(data);
				
				$( '#dialeg' ).dialog({
					 resizable: false,
					 title: "Nou curs",
					 height: "auto",
					 width: 300,
					 modal: true,
					 buttons: {
						 "Crear curs": {
							 click: function(e) {
								 e.preventDefault();	
								 
								 var club = $('#forminstructor_clubs').val();
								 
								 url += '?club='+club;

								 window.location = url;
							 },
							 text: "Crear curs",
			                 class: "btn btn-default"
						 },
						 "Cancel·lar": {
							 click: function() {
								 closeDialegConfirmacio();
							 },
							 text: "Cancel·lar",
			                 class: "btn btn-default"
						 }
					 },
					 //open: callbackopen
				});
				
        	}).fail( function(xhr, status, error) {
        		// xhr.status + " " + xhr.statusText, status, error
	        	//var sms = smsResultAjax('KO', xhr.responseText);
	    		
	        	dialegError("Error", "No es pot crear el nou curs, poseu-vos en contacte amb la Federació ", 350, 100);
	        	
	        	return false;	
        	});

		});
		
	};
	
	/*****************************************************************************************************************/
	
	/********************************************* import CSV ********************************************************/
	
	
	
})(jQuery);
