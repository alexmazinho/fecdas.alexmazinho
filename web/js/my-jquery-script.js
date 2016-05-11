/* Error IE. Se esperaba un identificador, una cadena o un número 
 * Cerca expressió regular ",[\s|\t|\n]*[}|\]]"
 * En la definició d'arrasy etc, l'últim no pot acabar amb coma */

(function($){
	
	
	var matched, browser;

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
	    var pattern = new RegExp(/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
	    // alert( pattern.test(emailAddress) );
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
	
	
	showModalDiv = function(id) {
        //Get the window height and width
        //var winH = $(window).height();
        var winW = $(window).width();
               
        //Set the popup window to center
        //$(id).css('top',  winH/2-$(id).height()/2);
        $(id).css('top',  30);
        $(id).css('left', winW/2-$(id).width()/2);
     
        //transition effect
        $(id).fadeIn(2000);
	};
	
	helpBubbles = function(t, ht) {
		//create a bubble popup for each DOM element
		//with class attribute as "button"
		$(t).CreateBubblePopup({
 			themePath : '/css/jquerybubblepopup-themes',	 
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
				themePath : '/css/jquerybubblepopup-themes',	 
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
	    });    
	     
	    //if mask is clicked
	    $('.mask').click(function () {
	        $(this).hide();
	        $('.finestra-overlay').hide();
	        $('.finestra-overlay').html('');
	    });       
		
		$('.form-button-cancel').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $('.mask, .finestra-overlay').hide();
	        $('.finestra-overlay').html('');
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
	
	/********** Selectors de dates ***************/
	
	loadCalendar = function(elem, callback) {
		$.datepicker.setDefaults( $.datepicker.regional[ "ca" ] );
		
		elem.datepicker({
			 showOn: "button",
			 buttonImage: "../images/calendar.gif",
			 buttonImageOnly: true,
			 changeMonth: true,
			 changeYear: true,
			 onSelect: callback
		});

	};
	
	loadTimeCalendar = function(elem, min, max, fonSelect) {
		//$.datepicker.setDefaults( $.datepicker.regional[ 'ca' ] );
		$.timepicker.setDefaults($.timepicker.regional['ca']);
		
		elem.datetimepicker({
			 showOn: "button",
			 buttonImage: "../images/calendar.gif",
			 buttonImageOnly: true,
			 changeMonth: true,
			 changeYear: true,
			 minDate: min,
			 maxDate: max,
			 controlType: 'select',
			 timeFormat: 'HH:mm',
			 onSelect: fonSelect 
		});

	};
	
	initDateTimePicker = function (elem, min, max, current, id, showtime, callback ) {

		var curformat = 'd/m/Y';
		if (showtime) curformat = 'd/m/Y H:i';
		
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
			 lang:'ca',
			 id:  id,
			 //className: 'pickerclass',
			 format: curformat, // '',
			 minDate: min,
			 maxDate: max,
			 defaultDate: current,
			 //startDate: current,
			 yearStart: min.getFullYear(),
			 yearEnd: max.getFullYear()
			 
		});
	};
	
	dialegError = function(titol, strError, dwidth, dheight) {
		$("#dialeg").dialog({
	    	modal: true,
	    	resizable: false,
	    	width: dwidth,
	    	height: (dheight !== undefined?dheight:"auto"),
	    	title: titol,
	        buttons: {
	            Ok: function() {
	              $( this ).dialog( "close" );
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
	            Ok: function() {
	              $( this ).dialog( "close" );
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
	            Ok: function() {
	              $( this ).dialog( "close" );
	              callbackko();
	            }
	        }
    	});
		
		$("#dialeg").html("<div class='alert alert-info'>"+
				"<ul><li><span class='fa fa-exclamation-circle fa-1x'></span>"+
				strInfo+"</li></ul></div>");
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
			 	"Continuar": function() {
			 		
			 		callbackok();

			 		$( '#dialeg' ).html('');
			 		$( this ).dialog( "destroy" );
			 	},
			 	"Cancel·lar": function() {
			 		callbackko();

			 		$( '#dialeg' ).html('');
			 		$( this ).dialog( "destroy" );
			 	}
			 },
			 open: callbackopen
		});
		
		
	}; 
	
	smsResultAjax = function(result, sms) {

		var classAlert = '';
		if (result != 'OK')	classAlert = 'alert-danger';
		else classAlert = 'alert-info';
		
		var errorRemove = '<div class="alert '+classAlert+' form-alert alert-dismissible">';
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
	
	/* Mètode obsolet */
	/*
	llistaPaginationAndSort  = function(formElement) {
	    // Paginació en llista o ordenació de columna
		$('.navigation .pagination a, #list-header .col-listheader-sortable a')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        
	        var url = $(this).url();

	        $('#form_page').val(url.param('page'));
	        $('#form_sort').val(url.param('sort'));
	        $('#form_direction').val(url.param('direction'));
	        
	        formElement.submit();
	    });
	};*/

	llistaPaginationAndSort = function(url, params) {
		for ( var i in params ) url=url+'&'+params[i].name+'='+params[i].value;
		window.location = url; 
	};

	
	/*****************************************************************************************************************/
	
	/*************************************************** Menu ********************************************************/
	
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
		    	else subMenu.slideDown('fast');
		        
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
    	else subMenu.slideUp('fast');
		
		mainAction.removeClass("left-menu-active");
		mainAction.find('.menu-icon').addClass('ui-icon-triangle-1-e'); // East
		mainAction.find('.menu-icon').removeClass('ui-icon-triangle-1-s');
	};
	
	/*****************************************************************************************************************/
	
	/*************************************************** Home ********************************************************/	
	
	login = function() {
		//$('loginbox').show(); 
		if ($('#loginbox').is(':visible')) {
			//alert("not hidden");
	    	$('#loginbox').hide();
	    	$("#login_user").RemoveBubblePopup();
	    	$("#login_pwd").RemoveBubblePopup();
	    	//alert("not hidden");
	   	} else {
	   		//alert("hidden");
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
			            	"Desar i continuar després" : function() {
			            		$(this).dialog("destroy");
			            		if (!$('#enquesta-preview').length) submitEnquesta('desar');
			            		else alert("Mode previsualització, les dades no es desaran");
			        		},
			            	"Finalitzar" : function() {
			            		$(this).dialog("destroy");
			            		if (!$('#enquesta-preview').length) submitEnquesta('final');
			            		else alert("Mode previsualització, les dades no es desaran");
			        		},
			            	"Cancel·lar" : function() {
			              		$(this).dialog("destroy");
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
			//alert(data);
			//$( ".selector" ).dialog( "destroy" );
			dialegInfo("Enquesta desada", data, 350, 100);
		});
	};

	
	/*****************************************************************************************************************/
	
	reloadParteTipus = function() {
		/* Inicialment selecció de cap tipus. Obligar usuari escollir*/
		//$('#parte_tipus').val('');
		
		/* canvi de tipus */
		$('#parte_tipus').change(function() {
			if ($('#parte_tipus').val() == "") {
		    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
		    	else $('#formparte-llicencia').slideUp('fast');
			} else {
				loadLlicencia(0);				
			}
	    });
	};
	
	asseguratsReload = function(url) {
		var params = []; 
		params.push( {'name':'tots','value': ($('#form_tots').is(':checked'))?1:0} );
		params.push( {'name':'vigent','value': ($('#form_vigent').is(':checked'))?1:0} );
		params.push( {'name':'dni','value': $('#form_dni').val()} );
		params.push( {'name':'nom','value': $('#form_nom').val()} );
		params.push( {'name':'cognoms','value': $('#form_cognoms').val()} );
		params.push( {'name':'desde','value': $('#form_desde').val()} );
		params.push( {'name':'fins','value': $('#form_fins').val()} );

		llistaPaginationAndSort(url, params);
	};
	
	showHistorialLlicencies = function() {
	    //Carrega i mostra historial per un assegurat
		$('.llicencia-historial')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();

	        var current = $(this).parents(".data-detall");
	        
	        if (current.hasClass('data-detall-historic')) {
	        	current.removeClass('data-detall-historic');
		        if ($.browser.msie) current.children('.assegurat-historial').hide(); 
		    	else current.children('.assegurat-historial').slideUp('slow');

	        } else {
	        	current.children('.assegurat-historial').remove();
		        var url = $(this).attr("href");
		        $.get(url, function(data, textStatus) {
		        	
		        	current.addClass('data-detall-historic');
		        	current.append(data);
			        if ($.browser.msie) current.children('.assegurat-historial').show(); 
			    	else current.children('.assegurat-historial').slideDown('slow');

			        //if close button is clicked
				    $('.assegurat-historial .close').click(function (e) {
				        //Cancel the link behavior
				        e.preventDefault();
				        current.removeClass('data-detall-historic');
				        if ($.browser.msie) current.children('.assegurat-historial').hide(); 
				    	else current.children('.assegurat-historial').slideUp('slow');
				    }); 
	        	});
	        };
	    });
		
		  
	};
	
	showPersonClickLlicencia = function() {
	    //select all the a tag with name equal to modal
		$('#formllicencia-openmodal')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        var url = $(this).attr("href");
	        
	        var id = $("#parte_llicencies_persona_select").val();
	        if (id == "") id = 0;
	        showPersonModal(id, url, 'llicencia');
	    });
	};
	
	showPersonClickAssegurats = function() {
	    //select all the a tag with name equal to modal
		$('.assegurats-openmodal')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        
	        var id = $(this).parent().parent().find(".assegurat-id").html(); 
	        var url = $(this).attr("href");
	        showPersonModal(id, url, 'assegurats');
	    });
	};
	
	showPersonClickClubs = function() {
	    //select all the a tag with name equal to modal
		$('#formclub-openmodal')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        
	        var id = 0;
	        var url = $(this).attr("href");
	        showPersonModal(id, url, 'clubs');
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
        $('.mask').fadeTo("slow",0.6); 
	};

	hideMask = function() {
        //transition effect    
        $('.mask').fadeOut("slow"); 
	};

	obrirMascaraBlock = function(block) {
		$(block).prepend('<div class="block-mask"><div><span class="fa fa-spinner fa-spin fa-2x green"></span></div></div>');
		$(block).css({'min-height':'200px'});
		$('.block-mask').fadeTo("slow",1); 
	};

	tancarMascaraBlock = function(block) {
		$('.block-mask').remove();
	};
	
	showPersonModal = function(id, url, origen) {
        // Show mask before overlay
        //Get the screen height and width
		var maskHeight = $(document).height();
        var maskWidth = $(window).width();
        //Set height and width to mask to fill up the whole screen
        $('.mask').css({'width':maskWidth,'height':maskHeight});
        //transition effect    
        $('.mask').fadeTo("slow",0.6); 
        
		var params = { 	persona: id };
		
		$.get(url,	params,
		function(data, textStatus) {
			
			$("#edicio-persona").html(data);
			// Reload DOM events. Add handlers again. Only inside reloaded divs
			var current = new Date();
			var mindate = new Date( current.getFullYear() - 100, 1 - 1, 1);
			var maxdate = new Date ( current.getFullYear() - 4, 1 - 1, 1);
			initDateTimePicker ( 
				$( '#persona_datanaixement' ), 
				mindate, 
				maxdate, 
				new Date (), 
				'datanaixement-picker', 
				false
			);
			
			formFocus();
			autocompleters();
			actionsModalOverlay();
			actionsPersonaForm(origen);
			
			$("select#parte_persona_addrprovincia").select2({
				minimumInputLength: 2,
				allowClear: true,
				placeholder: "Província",
			});
			$("select#parte_persona_addrcomarca").select2({
				minimumInputLength: 2,
				allowClear: true,
				placeholder: "Comarca",
			});
			$("select#parte_persona_addrnacionalitat").select2({
				minimumInputLength: 1,
				allowClear: true,
				placeholder: "ESP",
			});
			/* Check estranger */
			if ($('#parte_persona_id').val() != 0) {
				//$("#formpersona-estranger").hide();
			}
			// Show Div
			showModalDiv('#edicio-persona');
			helpBubbles("#help-dni", '<p align="left">El format del DNI ha de ser <b>12345678X</b></p>\
					<p align="left">En cas de menors que no disposin de DNI</p>\
					<p align="left">cal afegir el prèfix \'P\' o \'M\' al DNI del</p>\
					<p align="left">pare o la mare respectivament. P.e. <b>P12345678X.</b></p>\
					<p align="left">Per estrangers indicar el número d\'identificació equivalet</p>');
		});
	};
	
	autocompletersConfig = function(camp, open) {
		
		var route = $("#formpersona-autocompleters").attr("href");
		var $configs = {
			source: function(request, response) {
				var $data = { term: request.term, tipus:camp };
				$.getJSON(route, $data, response);
			},
			position: open,
			appendTo: "#edicio-persona",
			select: function(event, ui){
				$("#parte_persona_addrpob").val(ui.item.municipi);
				$("#parte_persona_addrcp").val(ui.item.cp);	
				$("#parte_persona_addrcp").trigger("blur.labelFx");
				$("#parte_persona_addrprovincia").val(ui.item.provincia);
				$("select#parte_persona_addrprovincia").select2({
					minimumInputLength: 2,
					allowClear: true,
					placeholder: "Província",
				});
				
				//$("#parte_persona_addrprovincia").trigger("blur.labelFx");
				$("#parte_persona_addrcomarca").val(ui.item.comarca);
				$("select#parte_persona_addrcomarca").select2({
					minimumInputLength: 2,
					allowClear: true,
					placeholder: "Comarca",
				});
				//$("#parte_persona_addrcomarca").trigger("blur.labelFx");
			}
		};

		return $configs;
	};
	
	
	autocompleters = function() {
		
		$('#parte_persona_addrpob').autocomplete(autocompletersConfig('poblacio'),  { my : "left bottom", at: "left top", collision: "none" });
		$('#parte_persona_addrcp').autocomplete(autocompletersConfig('cp'), { my : "right top", at: "right bottom", collision: "flip" });
	};
	
	actionsPersonaForm = function(origen) {
		$('#formpersona-button-remove').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $("#error-persona").html();
	        //var targetUrl = $(this).attr("href");
	
	        $("#dialeg").dialog({
	          	buttons : {
	            	"Confirmar" : function() {
		              //window.location.href = targetUrl;
	    	        	$(this).dialog("close");
	    	        	//$("#formpersona").submit();	 // Submit form
	    	        	submitPerson("remove", origen);
	        		},
	            	"Cancel·lar" : function() {
	              		$(this).dialog("close");
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
	        
	        $("#dialeg").dialog({
	          	buttons : {
	            	"Confirmar" : function() {
	    	        	$(this).dialog("close");
	    	        	if ($('#parte_persona_id').val() != 0) {
	    	        		// Modificació no valida DNI
	    	        		submitPerson("save", origen);
	    	        	} else {
	    	        		/* Alex. Validar nou check estranger */
	    	        		/*var error = validarDadesPersona($("#persona_dni").val(), $("#parte_persona_addrnacionalitat").val());*/
	    	        		var error = validarDadesPersona($("#persona_dni").val(), $("#persona_estranger").is(':checked'));
	    	        		if (error == "") {
	    	        			submitPerson("save", origen);
	    	        		} else {
	    	        			dialegError("Error", error, 400, 0);
	    	        		};
	    	        	};
	        	    	return false;  
	        		},
	            	"Cancel·lar" : function() {
	              		$(this).dialog("close");
	            	}
	          	},
	        	title: "Valida les dades",
	        	height: 'auto',
	        	width: 400,
	        	zIndex:	350
	        });
	
	        $("#dialeg").html("<p>Comprova que el DNI és correcte i està ben escrit.</p><p>Després no el podràs modificar.</p>");
	        $("#dialeg").dialog("open");
	        
	    });   
	};
	
	submitPerson = function(action, origen) {
		
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
	};
	
	
	loadLlicencia = function(n) {
		if ($('#parte_tipus').val() == null || $('#parte_tipus').val() == '') {
			dialegError("Error", "Cal indicar un tipus de llista", 350, 100);
			return;
		}
		
		$('#main-col .alert').remove();
		
		var url = $("#formllicencia").attr("action");
        var tipusparte = $("#parte_tipus").val();
        var altadata = $("#parte_dataalta").val();

    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
    	else $('#formparte-llicencia').slideUp('fast');
        $('#progressbar').show();  // Rellotge
        var part = { 'id' : $("#parte_id").val(), 'dataalta': altadata, 'tipus': tipusparte };
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
			//$("#formparte-llicencia").slideUp('fast');
			//$("#formparte-llicencia").fadeTo("slow",0.6); 
			//$("#formparte-llicencia").fadeIn(2000);
		};
		
    	$('#formparte-llicencia').html(data);
    	$('#progressbar').hide();  // Rellotge
    	if ($.browser.msie) $('#formparte-llicencia').show(); 
    	else $('#formparte-llicencia').slideDown('fast');
    	
    	// Reload DOM events. Add handlers again
    	$("select#parte_llicencies_persona_select").select2({
			minimumInputLength: 2,
			allowClear: true,
			placeholder: "Cercar federat",
		});
    	
    	$("select#parte_llicencies_persona_select").change(function(e) {
			if (e.val == "") $('#formllicencia-openmodal').html('nou assegurat <i class="fa fa-users"></i>');
			else $('#formllicencia-openmodal').html('modifica assegurat <i class="fa fa-user"></i>');
		});
    	
    	showPersonClickLlicencia();

    	addLlicenciaClick();
    	
    	selectAllChecks();
    	
	    $("#formparte-llicencia .close").click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').slideUp('fast');
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
	    	else $('#summary-data-hidden').slideDown('fast');*/
	    	$(this).hide();
	    	$("#parte-resum-more-hide").show();
	    });    

	    $("#parte-resum-more-hide").click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	    	if ($.browser.msie) $('#summary-data-hidden').hide(); 
	    	else $('#summary-data-hidden').slideUp('fast');
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
			strHtml += "   <p>Data factura anul·lació<br/>";
			strHtml += "	  <input type='text' id='datafacturacio' disabled='disabled'/>";
			strHtml += "   </p>";
			
			dialegConfirmacio(strHtml, 'Confirmació baixa llicència', 'auto', 400, function() { 
				
	        	$('#progressbar').show();  // Rellotge
	        	
	        	params += '&'+$.param({ 'datafacturacio': $('#datafacturacio').val() });
	        	
	 	        $.post(url, params,
	 	        	function(data, textStatus) {
	 	        	
	 	        	$('#progressbar').hide();
	 	        	
	 	        	$("#llista-llicencies").html(data);
	 	        	removeLlicenciaClick();
	 	        	showResumParteDetall();
	 	        	sortLlista("col-listheader", "list-data");
	 	        	// Hide llicencia
	 		    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	 		    	else $('#formparte-llicencia').slideUp('fast');
	 		    	
	 	        }).fail( function(xhr, status, error) {
					 // xhr.status + " " + xhr.statusText, status, error
					 var sms = smsResultAjax('KO', xhr.responseText);

					 $('div.alert').remove();
					 
					 $('#progressbar').hide();  // Rellotge
				    
				     $("#llista-llicencies").prepend(sms);
				});
				 
			}, function() { }, function() { 

				$( "#datafacturacio" ).datepicker({
		      		showOn: "button",
		            //buttonImage: "/images/icon-calendar.gif",
		            buttonText: "<span class='fa fa-calendar fa-1x'></span>",
		            //buttonImageOnly: true,
		            dateFormat: 'dd/mm/yy'
		      	});
			    
			  	$( "#datafacturacio" ).datepicker( "setDate", new Date() );
			});
	        
	    });
	};
	
	addLlicenciaClick = function() {
	    $('#formllicencia-add')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        
	        var url = $("#formllicencia").attr("action");
			if ($("#parte_llicencies_persona_select").val() == "") {
				dialegError("Error", "cal seleccionar una persona de la llista d'assegurats", 400);
				return false;
			}
			
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').slideUp('fast');

			$('#progressbar').show();  // Rellotge
			
			var paramsParte = $('#formparte').serializeArray();
			var paramsLlicencia = $('#formllicencia').serializeArray();
			var params = $.merge(paramsParte, paramsLlicencia);
			params.push( {'name':'action','value': 'persist'} );
			
			$.post(url, params, function(data, textStatus) {
				
				$('#progressbar').hide();  // Rellotge
		    	
		    	$("#llista-llicencies").html(data);
				
	        	if ($("#parte_id").val() == 0 && $("#header-llicenciaparteid").length)	{
	        		
	        		url = $("#formparte").attr("action")+'?id='+$("#header-llicenciaparteid").html();
	        		
	        		window.location = url;
	        		
	        		/*
	        		// Creació del parte si no hi ha error. reload
					$("#parte_id").val($("#header-llicenciaparteid").html());
	        		
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
					//window.location = window.location.pathname + '?id=' + $("#header-llicenciaparteid").html(); 
	        	};
		    	
				removeLlicenciaClick();
	        	
				showResumParteDetall();
	        	
				sortLlista("col-listheader", "list-data");
			}).fail( function(xhr, status, error) {
				 // xhr.status + " " + xhr.statusText, status, error
				
				 var sms = smsResultAjax('KO', xhr.responseText);
				 
				 $('#progressbar').hide();  // Rellotge
			    	
			     $("#llista-llicencies").prepend(sms);
			});
	    });
	};

	pagamentComandaSMS = function(admin) {
		
		var dialegHtml = '';
				
		dialegHtml += "<div class='sms-pagament'><p>Per pagar la comanda ";
		dialegHtml += " pot fer la transferència en qualsevol moment al número de compte:</p> "; 
		dialegHtml += "   <p>2100 0900 95 0211628657</p> ";
		dialegHtml += "   <ul><li>Amb targeta de crèdit o dèbit</li> ";
		dialegHtml += "      <li>Amb un compte de \'La Caixa\'</li> ";
		dialegHtml += "      <li>Mitjançant transferència des d\'una altra entitat</li></ul> ";
		dialegHtml += "   <p>Gràcies</p>";
		
		if (admin == true) {
			dialegHtml += "   <div class='form-group'>";
			dialegHtml += "      <label for='comanda_datafactura'>Data facturació</label>";
			dialegHtml += "      <div id='formcomanda-datafactura'>";
			dialegHtml += "	         <input type='text' id='datafacturacio' disabled='disabled'/>";
			dialegHtml += "      </div>";
			dialegHtml += "   </div>";
		}
		
		dialegHtml += "   <div class='form-group'>";
		dialegHtml += "      <label for='comanda_comentaris'>Comentaris</label>";
		dialegHtml += "      <div id='formcomanda-comentaris'>";
		dialegHtml += "	        <textarea class='form-control' rows='3' name='comanda[comentaris]' id='comanda_comentaris'></textarea>";
		dialegHtml += "      </div>";
		dialegHtml += "   </div>";
		dialegHtml += "</div>";
		
		return dialegHtml;
	};
	
	
	pagamentLlicenciesSMS = function() {
		return "<div class='sms-pagament'><p>Si <b>NO</b> té intenció de pagar la totalitat de les llicències ara, "+ 
    		"no continuï, pot fer la transferència en qualsevol moment al número de compte:</p> "+ 
			"<p>2100 0900 95 0211628657</p> "+
			"<p>I rebrà al seu club les llicències i la factura.</p> "+
    		"<p>Si vol realitzar el pagament ara, ho pot fer</p> "+
    		"<ul><li>Amb targeta de crèdit o dèbit</li> "+
    		"<li>Amb un compte de \'La Caixa\'</li> "+
    		"<li>Mitjançant transferència des d\'una altra entitat</li></ul> "+
    		"<p>Gràcies</p></div>";
	};
	
	tramitarPagamentButton = function(selector) {
		
		$( selector ).click(function(e) {
			e.preventDefault();
			
			var url = $(this).attr("href");
			$("#dialeg").dialog({
	          	buttons : {
	            	"Continuar" : function() {
	    	        	$(this).dialog("close");
	    	        	
	    	        	window.location = url;
	    	        },
		            "Sortir" : function() {
		    			//Cancel submit behavior
		            	$(this).dialog("close");
		            }
		        },
		        title: "Abans de continuar...",
		        height: 'auto',
		        width: 550,
		        zIndex:	350
		    });
		
		    $("#dialeg").html( pagamentLlicenciesSMS() );
		    
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
		    	else current.children('.parte-llicencies').slideUp('slow');

	        } else {
	        	current.children('.parte-llicencies').remove();
		        var url = $(this).attr("href");
		        $.get(url, function(data, textStatus) {
		        	
		        	current.addClass('data-detall-llicencies');
		        	current.append(data);
			        if ($.browser.msie) current.children('.parte-llicencies').show(); 
			    	else current.children('.parte-llicencies').slideDown('slow');

			        //if close button is clicked
				    $('.parte-llicencies .close').click(function (e) {
				        //Cancel the link behavior
				        e.preventDefault();
				        current.removeClass('data-detall-llicencies');
				        if ($.browser.msie) current.children('.parte-llicencies').hide(); 
				    	else current.children('.parte-llicencies').slideUp('slow');
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
		            	"Confirmar" : function() {
		    	        	$(this).dialog("close");
		    	        	document.location = source.attr('href');
		    	        },
			            "Cancel·lar" : function() {
			    			//Cancel submit behavior
			            	$(this).dialog("close");
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

	selectAllChecks = function() {
		$("#llicencia_seleccionat-tot").click(function(){
			var checked = $(this).is(':checked');
			if (checked) {
				$('.formcheckbox-right').not("#llicencia_renovar_enviarllicencia").each(function(){ this.checked = true; });				
			} else {
				$('.formcheckbox-right').not("#llicencia_renovar_enviarllicencia").each(function(){ this.checked = false; });
			};
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

	validarConsultaBusseig = function() {
	    $("#form-consultadni").submit(function () {
	    	$(".sms-notice").hide();
	    	/*if (validarDadesPersona("#form_dni")) {
	    		return true;  
	    	};
	    	alert("El DNI ha de tenir 8 dígits numèrics sense lletra (p.e. '12345678')");
	    	return false;*/
	    	return true;
	    });  
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
			alert($(this).html());
			
			SORTER.sort(".list-data");
		});
	};
	
	
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
	        $("#club_addrcomarcacorreu").val( $("#club_addrcomarca").val() );
	        $("#club_addrprovinciacorreu").val( $("#club_addrprovincia").val() );
	        $("#club_addradrecacorreu").val( $("#club_addradreca").val() );
	    });
	};
	
	
	saveClub = function() {
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
	        console.log($("#club_compte").val());
	        if ($("#club_compte").val() == "" || isNaN($("#club_compte").val())) {
	        	dialegError("Error", "cal indicar el compte contable", 400, 0);
	        	$( "#tabs-club" ).tabs( "option", "active", 2 );
				return false;
	        } else {
	        	console.log($("#club_compte").val().length);
		        if ($("#club_compte").val().length != 7 ) {
		        	dialegError("Error", "el compte comptable té un format incorrecte", 400, 0);
		        	$( "#tabs-club" ).tabs( "option", "active", 2 );
					return false;
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
	       
	        if ($("#club_tipusparte :selected").length < 1) {
	        	dialegConfirmacio( "El club no té assignat cap tipus de llicència", "Abans de continuar...", 0, 400, function() {
	        		$('#formclub').submit();
		        }, function() {
		        	$( "#tabs-club" ).tabs( "option", "active", 1 );
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
	
	addUserClick = function() {
	    $('#formuserclub-add')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	    	
	        e.preventDefault();
	        
	        var url = $(this).attr("href");
	        
	        if ($("#club_user").val() == "") {
	        	dialegError("Error", "cal indicar el mail de l'usuari", 400);
				return false;
	        } else {
	        	if( !isValidEmailAddress( $("#club_user").val() ) ) {
		        	dialegError("Error", "El mail no té un format correcte", 400);
					return false;
	        	}
	        }
	        if ($("#club_pwd_first").val() == "" || $("#club_pwd_second").val() == "") {
	        	dialegError("Error", "cal indicar la clau l'usuari", 400);
				return false;
	        }
	        if ($("#club_pwd_first").val() != $("#club_pwd_second").val()) {
	        	dialegError("Error", "Les claus no coincideixen", 400);
				return false;
	        }
			
			var params = $('#formclub').serializeArray();
			
			$.post(url, params,
			function(data, textStatus) {
		    	$("#llista-usuarisclub").html(data);
			});
	    });
	};
	
	resetPwdUserClick = function(struser) {
		
        var url = $("#formuserclub-add").attr("href");
		var params = { 	action: 'resetpwd', user: struser };
		$.get(url, params,
		function(data, textStatus) {
	    	$("#llista-usuarisclub").html(data);
		});
	};
	
	removedUserClick = function(struser) {
		
        var url = $("#formuserclub-add").attr("href");
		var params = { 	action: 'remove', user: struser };
		$.get(url, params,
		function(data, textStatus) {
	    	$("#llista-usuarisclub").html(data);
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
	        $('#club_randompwd').val(password);
	        $('#club_pwd_first').val(password);
	        $('#club_pwd_second').val(password);
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
			    else $('select#duplicat_carnet').slideLeftShow('slow');
			}
		});
		
		$("select#duplicat_carnet").change(function(e) {
			initFormDuplicats(false);
			if ($(this).val() != "") {
				var params = { 	persona:$("select#duplicat_persona").val(), carnet:$("select#duplicat_carnet").val() };
				$.get(url,	params,
				function(data) {
					$("#formduplicats-titols").remove();
					
					$("#formduplicats-dades").replaceWith( data );

					var titols = $("#formduplicats-titols").detach(); // Colocar titols als camps superiors
					$("#formduplicats .form-row").first().append(titols);
					
					if ($.browser.msie) $('select#duplicat_titol').show(); 
				    else $('select#duplicat_titol').slideLeftShow('slow');
					
					if ($.browser.msie) $('#formduplicats-dades').show(); 
				    else $('#formduplicats-dades').slideDown('slow');
					
					imageUploadForm($("#duplicat_fotoupld"), 104);
					
					
					$('#duplicat_submit').click(function(e) {
						e.preventDefault();
						
						
						
						// Validacions
						if ($('select#duplicat_titol').length > 0 && $('select#duplicat_titol').val() == "") {
							dialegError("Error", "Cal escollir un títol", 300, 100);
							return false;
						}
						if ($('input#duplicat_fotoupld').length > 0 && $('#formduplicats-foto .file-input-thumb').length == 0) {
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
			if ($.browser.msie) $('select#duplicat_carnet').hide(); 
		    else $('select#duplicat_carnet').slideLeftHide('slow');
		}
		
		if ($.browser.msie) $('select#duplicat_titol').hide(); 
	    else $('select#duplicat_titol').slideLeftHide('slow');
		
		if ($.browser.msie) $('#formduplicats-dades').hide(); 
	    else $('#formduplicats-dades').slideUp('slow');
	};

	imageUploadForm = function(formel, imgwidth) {
		$(".galeria-upload").click(function(e) {
		    e.preventDefault();
		    // Make as the real input was clicked
		    formel.click();
	    });
		
		formel.imagePreview({ selector : '.galeria-upload', multiple: false, textover: 'Canviar imatge', width: imgwidth });
	};
	
	
	$.fn.imagePreview = function(params){
		$(this).change(function(evt){
			if(typeof FileReader == "undefined") return true; // File reader not available.

			var fileInput = $(this);
			var files = evt.target.files; // FileList object
			//var total = 0;

			$(params.selector).find(".image-uploaded").remove();  // Removes previous preview 

			// Loop through the FileList and render image files as thumbnails.
			for (var i = 0, f; f = files[i]; i++) {

				// Only process image files.
				if (!f.type.match('image.*')) {
					continue;
				}
				var reader = new FileReader();
				
				// Closure to capture the file information.
				reader.onload = (function(theFile) {
					return function(e) {
						// Render thumbnail.
						var imgHTML = '<img width="'+params.width+'" title="'+params.textover+'" alt="'+params.textover+'" class="file-input-thumb" src="' + e.target.result + '" title="' + theFile.name + '"/>';

						if( typeof params.selector != 'undefined' ){
							if (params.multiple == true) {
								/*
								$novaimatge = $('<div class="image-preview image-upload">' + imgHTML +'</div>');
								$(params.selector).append($novaimatge);
								
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
								//$(params.selector).replaceWith( data );
								$(params.selector).html('<div class="image-upload image-uploaded">' + imgHTML +'</div>');
								hoverPortada($(".image-uploaded"));
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
	
	/*****************************************************************************************************************/
	
	/********************************************* import CSV ********************************************************/
	
	prepareFileInput = function (elem) {
		elem.change(function() {
	        var info  = '';

	   		// Display filename (without fake path)
	        var path = $(this).val().split('\\');
	        info     = path[path.length - 1];

	        $("#upload-file-info").val(info);
	    });

		$(".input-append").click(function(e) {
	        e.preventDefault();
	        // Make as the real input was clicked
	        elem.click();
	    });
	};
	
})(jQuery);
