/* Error IE. Se esperaba un identificador, una cadena o un número 
 * Cerca expressió regular ",[\s|\t|\n]*[}|\]]"
 * En la definició d'arrasy etc, l'últim no pot acabar amb coma */

(function($){
	
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
		if (isBrowserOk() == true && !($.browser.webkit || $.browser.mozilla)) {
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
		$('#'+menuid).addClass("left-menu-active");
	};
	
	
	showModalDiv = function(id) {
        //Get the window height and width
        var winH = $(window).height();
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
	
	formFocus = function() {
		/*$("form.appform input, form.appform textarea, #loginbox form input")
			.not('input[type=button], input[type=hidden]')*/
		$(".forminput-inside")
		.bind("focus.labelFx", function(){
			$(this).prev().hide();
		})
		.bind("blur.labelFx", function(){
			/*if ($(this).val().length == 0) {
				$(this).prev().show();
			} else {
				$(this).prev().hide();
			}*/
			$(this).prev()[!this.value ? "show" : "hide"]();
		})
		.trigger("blur.labelFx");
		$(".forminput-inside.ui-autocomplete-input")
		.bind("focus.labelFx", function(){
			$(this).prev().prev().hide();
		})
		.bind("blur.labelFx", function(){
			$(this).prev().prev()[!this.value ? "show" : "hide"]();
		})
		.trigger("blur.labelFx");
	};
	
	actionsModalOverlay = function() {
	    //if close button is clicked
	    $('.finestra-overlay .close').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $('.mask, .finestra-overlay').hide();
	        $('finestra-overlay').html('');
	    });    
	     
	    //if mask is clicked
	    $('.mask').click(function () {
	        $(this).hide();
	        $('.finestra-overlay').hide();
	        $('finestra-overlay').html('');
	    });       
		
		$('.form-button-cancel').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $('.mask, .finestra-overlay').hide();
	        $('finestra-overlay').html('');
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
	
	/*****************************************************************************************************************/
	
	/*************************************************** Menu ********************************************************/
	
	menuAdmClick = function() {
		$( "#menu-adm" ).click(function(e) {
	        //Cancel the link behavior
	        e.preventDefault();

	        if ($('.menu-adm-item').is(':visible')) {
	        	if ($.browser.msie) $('.menu-adm-item').hide(); 
		    	else $('.menu-adm-item').slideUp('fast');

	        	$(this).removeClass("left-menu-active");

	        	$(this).find('.menu-icon').addClass('ui-icon-triangle-1-e'); /* East*/
	        	$(this).find('.menu-icon').removeClass('ui-icon-triangle-1-s');
	        	
	        } else {
	        	if ($.browser.msie) $('.menu-adm-item').show(); 
		    	else $('.menu-adm-item').slideDown('fast');
		        
	        	$(this).addClass("left-menu-active");

	        	$(this).find('.menu-icon').addClass('ui-icon-triangle-1-s');
	        	$(this).find('.menu-icon').removeClass('ui-icon-triangle-1-e');
		    }
		});
	};
	
	/*****************************************************************************************************************/
	
	/*************************************************** Home ********************************************************/	
	
	fluxhomepage = function()  {
		//if(flux.browser.supportsTransitions) {
		if (isBrowserOk() == true) { 
			window.myFlux = new flux.slider('#slider', {
			autoplay: true,
			pagination: true,
			//controls:true,
			//captions: true,
			transitions: new Array("bars3d", "zip", "blinds3d", "cube", "tiles3d", "bars", "slide"),
			onTransitionEnd: function(data) {
		        var img = data.currentImage;
			    if (img.id == "sliderimg10") {
			    	window.myFlux.stop();
			    };
		    }
			});
		};
	};

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
	       		
	   		helpBubbles("#login_user", '<p>L\'usuari és una adreça de correu electrònic. p.e. </p>\
	       					<p>maildelclub@domini.com</p>');

	       	helpBubbles("#login_pwd", '<p>Indica la teva paraula clau d\'accés al sistema</p>');
	   	}
	};
	
	/*****************************************************************************************************************/
	
	
	reloadParte = function() {
		/* Inicialment selecció de cap tipus. Obligar usuari escollir*/
		$('#parte_tipus').val('');
		
		/* canvi de tipus */
		$('#parte_tipus').change(function() {
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').slideUp('fast');
	    });

		/* Canvi Club */
		$('#parte_club').change(function() {
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').slideUp('fast');
		});
		
		/* Canvi Data */
		$('#parte_dataalta_date_day').change(function() {
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').slideUp('fast');

	    	// Update select tipus parte	
			var url = $('#formparte-tipus').data('ajax-route');
			var params = { 	day: $("#parte_dataalta_date_day").val(), month: $("#parte_dataalta_date_month").val() }
			$.get(url,	params,
			function(data) {
				$('select#parte_tipus').html(data); 
			});
		});

		$('#parte_dataalta_date_month').change(function() {
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').slideUp('fast');
	    	
	    	// Update select tipus parte	
			var url = $('#formparte-tipus').data('ajax-route');
			var params = { 	day: $("#parte_dataalta_date_day").val(), month: $("#parte_dataalta_date_month").val() }
			$.get(url,	params,
			function(data) {
				$('select#parte_tipus').html(data); 
			});
		});

		$('#parte_dataalta_date_year').change(function() {
			$('#parte_any').val($('#parte_dataalta_date_year').val());
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').slideUp('fast');
		});

	};
	
	selectpersona = function() {
		var oldvalue;
		$('#parte_llicencies_persona_select').focus(function() {
			oldvalue = $(this).val();
		});
			
		$('#parte_llicencies_persona_select').change(function() {
			var persona = $(this).val();
	
			if (persona == "") {
				//alert("cal seleccionar una persona de la llista d'assegurats");
				//$(this).val(oldvalue);
				$('#formllicencia-openmodal').html('nou assegurat <img src=\"/images/icon_add.png\">');
				return false;
			};
			// Si selecció persona, canvia text
			if (persona == "") $('#formllicencia-openmodal').html('nou assegurat <img src=\"/images/icon_add.png\">');
			else $('#formllicencia-openmodal').html('modifica assegurat <img src=\"/images/icon_add.png\">');
		});
	};
	
	showPersonClick = function(id) {
	    //select all the a tag with name equal to modal
		$('#formllicencia-openmodal')
	    .off('click')
	    .click(function(e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        
	        // Show mask before overlay
	        //Get the screen height and width
			var maskHeight = $(document).height();
	        var maskWidth = $(window).width();
	        //Set height and width to mask to fill up the whole screen
	        $('.mask').css({'width':maskWidth,'height':maskHeight});
	        //transition effect    
	        $('.mask').fadeTo("slow",0.6); 
	        
	        var url = $(this).attr("href");
			var params = { 	persona: id.val() }

			$.get(url,	params,
			function(data, textStatus) {
				$("#edicio-persona").html(data);
				// Reload DOM events. Add handlers again. Only inside reloaded divs
				formFocus();
				autocompleters();
				actionsModalOverlay();
				actionsPersonaForm();
				// Show Div
				showModalDiv('#edicio-persona');
				helpBubbles("#help-dni", '<p align="left">El format del DNI ha de ser <b>12345678X</b></p>\
						<p align="left">En cas de menors que no disposin de DNI</p>\
						<p align="left">cal afegir el prèfix \'P\' o \'M\' al DNI del</p>\
						<p align="left">pare o la mare respectivament. P.e. <b>P12345678X.</b></p>\
						<p align="left">Per estrangers indicar el número d\'identificació equivalet</p>');

			});
	    });
	};
	
	autocompleters = function() {
		var route = $("#formpersona-autocompleters").attr("href");
		var $configs = {
			source: function(request, response) {
				var $data = {term: request.term};
				$.getJSON(route, $data, response);
			},
			position: { my : "left bottom", at: "left top", collision: "none" },
			appendTo: "#edicio-persona",
			select: function(event, ui){
				$("#parte_persona_addrpob").val(ui.item.municipi);
				$("#parte_persona_addrcp").val(ui.item.cp);	
				$("#parte_persona_addrcp").trigger("blur.labelFx");
				$("#parte_persona_addrprovincia").val(ui.item.provincia);
				$("#parte_persona_addrprovincia").trigger("blur.labelFx");
				$("#parte_persona_addrcomarca").val(ui.item.comarca);
				$("#parte_persona_addrcomarca").trigger("blur.labelFx");
			}
		};

		$('#parte_persona_addrpob').autocomplete($configs);
	};
	
	actionsPersonaForm = function() {
		$("#dialog").dialog({
			autoOpen: false,
		    modal: true
	    });
		
		$('#formpersona-button-remove').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $("#error-persona").html();
	        //var targetUrl = $(this).attr("href");
	
	        $("#dialog").dialog({
	          	buttons : {
	            	"Confirmar" : function() {
		              //window.location.href = targetUrl;
	    	        	$(this).dialog("close");
	    	        	//$("#formpersona").submit();	 // Submit form
	    	        	submitPerson("remove");
	        		},
	            	"Cancel·lar" : function() {
	              		$(this).dialog("close");
	            	}
	          	},
	        	title: "Confirmació per esborrar",
	        	height: 180,
	        	zIndex:	2999
	        });
	
	        $("#dialog").html("Segur que vols esborrar <br/>aquestes dades personals?");
	        $("#dialog").dialog("open");
	    });   
		
		$('#formpersona-button-save').click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	        $("#error-persona").html();
	        
	        $("#dialog").dialog({
	          	buttons : {
	            	"Confirmar" : function() {
	    	        	$(this).dialog("close");
	    	        	if ($('#parte_persona_id').val() != "") {
	    	        		// Modificació no valida DNI
	    	        		submitPerson("save");
	    	        	} else {
	    	        		if (validarDadesPersona($("#persona_dni").val(), $("#parte_persona_addrnacionalitat").val())) {
	    	        			submitPerson("save");
	    	        		} else {
	    	        			alert("Format DNI incorrecte 12345678X o P12345678X o M12345678X");
	    	        		};
	    	        	};
	        	    	return false;  
	        		},
	            	"Cancel·lar" : function() {
	              		$(this).dialog("close");
	            	}
	          	},
	        	title: "Valida les dades",
	        	height: 180,
	        	width: 320,
	        	zIndex:	2999
	        });
	
	        $("#dialog").html("Comprova que les dades són correctes.<br/> Després no les podràs modificar.");
	        $("#dialog").dialog("open");
	        
	    });   
	};
	
	submitPerson = function(action) {
		
		$('#edicio-persona').hide();
		
		var url = $('#formpersona').attr("action");
		var params = $('#formpersona').serializeArray();
			
		 if ($("#parte_dataalta_date_day").length ) {
	        	// Existeix "parte_dataalta_date_day"
	        	var alta_data = $("#parte_dataalta_date_day").val();
	        	alta_data += "/" + $("#parte_dataalta_date_month").val();
	        	alta_data += "/" + $("#parte_dataalta_date_year").val();
	        	alta_data += " " + $("#parte_dataalta_time_hour").val();
	        	alta_data += ":" + $("#parte_dataalta_time_minute").val();
	        	alta_data += ":00";
	    } else {
	        	var alta_data = $("#parte_dataalta_date").val() + " " + $("#parte_dataalta_time").val();
	    }
		
		params.push( {'name':'action','value': action} );
		params.push( {'name':'dataalta','value': alta_data} );
		params.push( {'name':'tipusparte','value': $('#parte_tipus').val()} );
		params.push( {'name':'codiclub','value': $('#parte_club').val()} );
		params.push( {'name':'llicenciaId','value': $('#parte_llicencies_id').val()} );

		$.post(url, params,
		function(data, textStatus) {
			var error = false;
			if (data == "nomerror") {
				$("#error-persona").html("<div class=\"sms-notice\">Cal indicar nom i cognoms</div>");
				error = true;
			};
			if (data == "dnierror") {
				$("#error-persona").html("<div class=\"sms-notice\">Cal indicar el DNI</div>");
				error = true;
			};
			if (data == "telefonerror") {
				$("#error-persona").html("<div class=\"sms-notice\">Telèfon incorrecte</div>");
				error = true;
			};
			if (data == "mailerror") {
				$("#error-persona").html("<div class=\"sms-notice\">Mail incorrecte</div>");
				error = true;
			};
			if (data == "dnicluberror") {
				$("#error-persona").html("<div class=\"sms-notice\">Aquest dni ja existeix per aquest club</div>");
				error = true;
			};
			if (data == "novaliderror") {
				$("#error-persona").html("<div class=\"sms-notice\">Dades invàlides</div>");
				error = true;
			};

			if (error == true) {
				$('#edicio-persona').show();
				return false;
			}
			
			$('.mask').hide();
			
			$("#edicio-persona").html("");
			
			loadLlicenciaData(data);
		});
	};
	
	loadLlicencia = function(n) {
		if ($('#parte_tipus').val() == '') {
			alert('Cal indicar un tipus de llista');
			return;
		}
		
		var url = $("#formllicencia").attr("action");
        var tipusparte = $("#parte_tipus").val();
        var codiclub = $("#parte_club").val();
        
        if ($("#parte_dataalta_date_day").length ) {
        	// Existeix "parte_dataalta_date_day"
        	var alta_data = $("#parte_dataalta_date_day").val();
        	alta_data += "/" + $("#parte_dataalta_date_month").val();
        	alta_data += "/" + $("#parte_dataalta_date_year").val();
        	alta_data += " " + $("#parte_dataalta_time_hour").val();
        	alta_data += ":" + $("#parte_dataalta_time_minute").val();
        	alta_data += ":00";
        } else {
        	var alta_data = $("#parte_dataalta_date").val() + " " + $("#parte_dataalta_time").val();
        }

    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
    	else $('#formparte-llicencia').slideUp('fast');
        $('#progressbar').show();  // Rellotge
        $.get(url, {source_ajax: 'edit-llicencia', codiclub: codiclub, tipusparte: tipusparte, dataalta: alta_data, llicenciaId: n},
     	function(data, textStatus) {
        	loadLlicenciaData(data);
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
    	selectpersona();
    	showPersonClick($("#parte_llicencies_persona_select"));
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
	    	if ($.browser.msie) $('#parte-resum-more').show(); 
	    	else $('#parte-resum-more').slideDown('fast');
	    	$(this).hide();
	    	$("#parte-resum-more-hide").show();
	    });    

	    $("#parte-resum-more-hide").click(function (e) {
	        //Cancel the link behavior
	        e.preventDefault();
	    	if ($.browser.msie) $('#parte-resum-more').hide(); 
	    	else $('#parte-resum-more').slideUp('fast');
	    	$(this).hide();
	    	$("#parte-resum-more-show").show();
	    });    
	};
	
	
	removeLlicenciaClick = function() {
	    $('a[name^=remove-llicencia]')
	    .off('click')
	    .click(function(e) {
	    	//Cancel the link behavior
	        e.preventDefault();
	        
	        var id = $(this).attr('value');
	        
	        $("#dialog").dialog({
	          	buttons : {
	            	"Confirmar" : function() {
		              //window.location.href = targetUrl;
	    	        	$(this).dialog("close");
	    	        	var url = $("#formllicencia").attr("action");
	    	 	        var params = $('#formparte').serializeArray();
	    	 	        params.push( {'name':'action','value': 'remove'} );
	    	 	        params.push( {'name':'llicenciaId','value': id} );
	    	 	        
	    	 	        $.post(url, params,
	    	 	        	function(data, textStatus) {
	    	 	        	$("#llista-llicencies").html(data);
	    	 	        	removeLlicenciaClick();
	    	 	        	showResumParteDetall();
	    	 	        	// Hide llicencia
	    	 		    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	 		    	else $('#formparte-llicencia').slideUp('fast');
	    	 	        });
	            	
	            	
	            	},
	            	"Cancel·lar" : function() {
	              		$(this).dialog("close");
	            	}
	          	},
	        	title: "Confirmació per esborrar",
	        	height: 180,
	        	width: 280,
	        	zIndex:	2999
	        });
	
	        $("#dialog").html("Segur que vols esborrar <br/>aquesta llicència?");
	        $("#dialog").dialog("open");
	    
	    });
	};
	
	addLlicenciaClick = function() {
	    $('#formllicencia-add')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        
	        //$("#formllicencia").submit();
	        
	        var url = $("#formllicencia").attr("action");
			if ($("#parte_llicencies_persona_select").val() == "") {
				alert("cal seleccionar una persona de la llista d'assegurats");
				return false;
			}
			
	    	if ($.browser.msie) $('#formparte-llicencia').hide(); 
	    	else $('#formparte-llicencia').slideUp('fast');

			$('#progressbar').show();  // Rellotge
			 
			
			var paramsParte = $('#formparte').serializeArray();
			var paramsLlicencia = $('#formllicencia').serializeArray();
			var params = $.merge(paramsParte, paramsLlicencia);
			params.push( {'name':'action','value': 'persist'} );
			
			$.post(url, params,
			function(data, textStatus) {
		    	$('#progressbar').hide();  // Rellotge
				
		    	if ($.browser.msie) $('#formparte-llicencia').show(); 
		    	else $('#formparte-llicencia').slideDown('fast');
				$("#llista-llicencies").html(data);
	        	removeLlicenciaClick();
	        	showResumParteDetall();
	        	
		    	
	        	// Parte nou creat, deixa només el tipus de parte seleccionat
	        	$("#parte_tipus option:not(:selected)").each(function(i, item){
	        		$(item).remove()
	        	});
	        	
	        	// Parte nou creat, deixa només el club seleccionat
	        	$("#parte_club option:not(:selected)").each(function(i, item){
	        		$(item).remove()
	        	});

	        	// Parte nou creta, desactiva data
	        	$("#parte_dataalta_date_day option:not(:selected)").each(function(i, item){
	        		$(item).remove()
	        	});
	        	$("#parte_dataalta_date_month option:not(:selected)").each(function(i, item){
	        		$(item).remove()
	        	});
	        	$("#parte_dataalta_date_year option:not(:selected)").each(function(i, item){
	        		$(item).remove()
	        	});
	        	$("#parte_dataalta_time_hour option:not(:selected)").each(function(i, item){
	        		$(item).remove()
	        	});
	        	$("#parte_dataalta_time_minute option:not(:selected)").each(function(i, item){
	        		$(item).remove()
	        	});
	        	
	        	if ($("#parte_id").val() == "")	{  // Set form parte_id
	        		var hrefprint = $("#formparte-print a").attr("href");
	        		$("#parte_id").val($("#header-parteid").html());
	        		if ($("#parte_id").val() != "") {
	        			hrefprint += "?id=" + $("#parte_id").val();
	        			$("#formparte-buttons").show();
	        			$("#formparte-print a").attr("href", hrefprint);
	        		}
	        	};
			});
	        
	    });
	};

	partePagatButton = function() {
		$("#dialog").dialog({
			autoOpen: false,
		    modal: true
	    });
		
		$('#formparte-button-pagat').click(function(e) {
			e.preventDefault();
			$("#dialog").dialog({
	          	buttons : {
	            	"Confirmar" : function() {
	    	        	$(this).dialog("close");
	    	        	
	    	        	$('<input>').attr({
	    	        	    type: 'hidden',
	    	        	    id: 'datapagat',
	    	        	    name: 'parte[datapagat]',
	    	        	    value: $( "#datepicker" ).val(),  
	    	        	}).appendTo('#formparte');
	    	        	
	    	        	$('#formparte').submit();
	    	        },
		            "Cancel·lar" : function() {
		    			//Cancel submit behavior
		            	$(this).dialog("close");
		            }
		        },
		        title: "Confirmació pagament del parte",
		        height: 190,
		        zIndex:	2999
		    });
		
		    $("#dialog").html("<p>Indica la data <input type='text' id='datepicker' disabled='disabled'/></p>");
		    
		    $( "#datepicker" ).datepicker({
	            showOn: "button",
	            buttonImage: "/images/icon-calendar.gif",
	            buttonImageOnly: true,
	            dateFormat: 'dd/mm/yy'
	        });
		    
		    $( "#datepicker" ).datepicker( "setDate", new Date() );
		    
		    $("#dialog").dialog("open");
		});
	};
	
	
	partePagamentButton = function() {
		$("#dialog").dialog({
			autoOpen: false,
		    modal: true
	    });
		
		$('#formparte-button-payment').click(function(e) {
			e.preventDefault();
			$("#dialog").dialog({
	          	buttons : {
	            	"Continuar" : function() {
	    	        	$(this).dialog("close");
	    	        	
	    	        	$('#formparte').submit();
	    	        },
		            "Sortir" : function() {
		    			//Cancel submit behavior
		            	$(this).dialog("close");
		            }
		        },
		        title: "Abans de continuar...",
		        height: 350,
		        width: 550,
		        zIndex:	2999
		    });
		
		    $("#dialog").html("<p>Si <b>NO</b> té intenció de pagar la totalitat de les llicències ara, " +
		    		"no continuï, pot fer la transferència en qualsevol moment al número de compte:</p>" +
		    		"<p>2100 0900 95 0211628657</p>" +
		    		"<p>I rebrà al seu club les llicències i la factura.</p>" +
		    		"<p>Si vol realitzar el pagament ara, ho pot fer</p>" +
		    		"<ul><li>Amb targeta de crèdit o dèbit</li>" +
		    		"<li>Amb un compte de \'La Caixa\'</li>" +
		    		"<li>Mitjançant transferència des d'una altra entitat</li></ul>" +
		    		"<p>Gràcies</p>");
		    
		    $("#dialog").dialog("open");
		});
	};
	
	
	removeParteLink = function () {
		$(".parte-action-remove").each(function() {
			var source = $(this);
			$(this).click(function(e) {
				e.preventDefault();
				
				$("#dialog").dialog({
					autoOpen: false,
				    modal: true
			    });

				$("#dialog").dialog({
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
				
			    $("#dialog").html("Segur que vols esborrar <br/>aquesta llista?");
			    $("#dialog").dialog("open");
			});
		});
		
	};

	removeParteButton = function() {
		$("#dialog").dialog({
			autoOpen: false,
		    modal: true
	    });
		
		$('#formparte-button-delete').click(function(e) {
			e.preventDefault();
			$("#dialog").dialog({
	          	buttons : {
	            	"Confirmar" : function() {
		              //window.location.href = targetUrl;
	    	        	$(this).dialog("close");
	    	        	$('#formparte').submit();
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
		
		    $("#dialog").html("Segur que vols esborrar <br/>aquesta llista?");
		    $("#dialog").dialog("open");
		});
	};

	selectAllChecks = function() {
		$("#llicencia_seleccionat-tot").click(function(){
			var checked = $(this).is(':checked');
			if (checked) {
				$('.formfield-outside-multicheck input').each(function(){ this.checked = true; });				
			} else {
				$('.formfield-outside-multicheck input').each(function(){ this.checked = false; });
			};
		});
	};
	
	validarDadesPersona = function(dni, nacionalitat) {
		/* Només valida si nacionalitat és Espanyola */
		if (nacionalitat == "ESP") {
			/* Si comença per X acceptar tot */
			if (dni.substring(0,1) == 'X' || dni.substring(0,1) == 'x') return true;  
			
			if(dni.length < 9) return false;  
			if(dni.length > 10) return false;
			
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
				if (dniprefix != 'P' && dniprefix != 'M') return false
			}
			if (isNaN(dninum )) return false;
			if (!isNaN(dnillletra )) return false;
			
	        return true;
		}
		return true;
		
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
	
	reloadClub = function() {
		/* canvi de club */
		$('#club_clubs').change(function() {
			var url = $("#formclub").attr("action");
			window.location = url + '?codiclub=' + this.value;
		});
	};

	saveClub = function() {
		/* desar club */
		$('#formclub-save')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        if ($("#club_codishow").val() == "") {
	        	alert("cal indicar el codi del club");
				return false;
	        }
	        if ($("#club_nom").val() == "") {
	        	alert("cal indicar el nom del club");
				return false;
	        }
	        if ($("#club_cif").val() == "") {
	        	alert("cal indicar el cif del club");
				return false;
	        }
	        if ($("#club_mail").val() == "") {
	        	alert("cal indicar el mail del club");
				return false;
	        }
	        if ($("#club_telefon").val() != "" && isNaN($("#club_telefon").val())) {
	        	alert("El telèfon ha de ser numèric");
				return false;
	        }
	        if ($("#club_addrcp").val() != "") {
	        	if (isNaN( $("#club_addrcp").val() ) ) {
	        		alert("El codi postal ha de ser numèric");
	        		return false;
	        	} else {
	        		if ($("#club_addrcp").val().length != 5) {
		        		alert("El codi postal ha de tenir 5 dígits");
		        		return false;
	        		}
	        	}
	        }
	        
    	    $('#formclub').submit();	
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
	
	loadUserclub = function() {
		var url = $("#formusuariclub").attr("action");
        var codiclub = $("#club_codi").val();

    	if ($.browser.msie) $('#formclub-usuari').hide(); 
    	else $('#formclub-usuari').slideUp('fast');
        $('#progressbar').show();  // Rellotge
        $.get(url, {codiclub: codiclub},
     	function(data, textStatus) {
        	$('#formclub-usuari').html(data);
        	$('#progressbar').hide();  // Rellotge
        	if ($.browser.msie) $('#formclub-usuari').show(); 
        	else $('#formclub-usuari').slideDown('fast');
        	
        	addUserClick();
        	randomPwdClick();
        	
    	    $("#formclub-usuari .close").click(function (e) {
    	        //Cancel the link behavior
    	        e.preventDefault();
    	    	if ($.browser.msie) $('#formclub-usuari').hide(); 
    	    	else $('#formclub-usuari').slideUp('fast');
    	    }); 
		});
	};
	
	addUserClick = function() {
	    $('#formuserclub-add')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	        e.preventDefault();
	        if ($("#user_club_user").val() == "") {
	        	alert("cal indicar el mail de l'usuari");
				return false;
	        } else {
	        	if( !isValidEmailAddress( $("#user_club_user").val() ) ) {
	        		alert("El mail no té un format correcte");
					return false;
	        	}
	        }
	        if ($("#user_club_pwd_first").val() == "" || $("#user_club_pwd_second").val() == "") {
	        	alert("cal indicar la clau l'usuari");
				return false;
	        }
	        if ($("#user_club_pwd_first").val() != $("#user_club_pwd_second").val()) {
	        	alert("Les claus no coincideixen");
				return false;
	        }
	        
	        var url = $("#formusuariclub").attr("action");
			
	    	if ($.browser.msie) $('#formclub-usuari').hide(); 
	    	else $('#formclub-usuari').slideUp('fast');

			$('#progressbar').show();  // Rellotge
			
			var codiclub = $("#club_codi").val();
			
			var params = $('#formusuariclub').serializeArray();
			params.push( {'name':'codiclub','value': codiclub} );
			
			$.post(url, params,
			function(data, textStatus) {
		    	$('#progressbar').hide();  // Rellotge
				
		    	$("#llista-usuarisclub").html(data);
			});
	    });
	};
	
	enableUserClick = function(struser, action) {
        var url = $("#formusuariclub").attr("action");
		$('#progressbar').show();  // Rellotge
		var params = { 	action: action, user: struser };
		$.get(url, params,
		function(data, textStatus) {
	    	$('#progressbar').hide();  // Rellotge
			
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
	        $('#formuserclub-passwords').hide(); 
	        $('#formuserclub-autopasswords').show();
	        var password = randomPassword(8);
	        $('#formuserclub-randompassword').val(password);
	        $('#user_club_pwd_first').val(password);
	        $('#user_club_pwd_second').val(password);
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
	
	
	/************ Pedent ************************
	
	var SORTER = {};
	SORTER.sort = function(which, dir) {
		alert ("Hola");
	  SORTER.dir = (dir == "desc") ? -1 : 1;
	  $(which).each(function() {
		  alert ("Hola2");
	    // Find the list items and sort them
	    var sorted = $(this).find("> li").sort(function(a, b) {
	    	alert ($(a).html());
	      return $(a).text().toLowerCase() > $(b).text().toLowerCase() ?
	        SORTER.dir : -SORTER.dir;
	    });
	    $(this).append(sorted);
	  });
	};
	
	sortList = function(ul, sortDescending) {
		ul = document.getElementById(ul);

		// Get the list items and setup an array for sorting
		var lis = ul.getElementsByTagName("LI");
		var vals = [];

		// Populate the array
		for(var i = 0, l = lis.length; i < l; i++)
			vals.push(lis[i].innerHTML);

		// Sort it
		vals.sort();

		// Sometimes you gotta DESC
		if(sortDescending)
			vals.reverse();

		// Change the list on the page
		for(var i = 0, l = lis.length; i < l; i++)
			lis[i].innerHTML = vals[i];
	};*/
	
	
})(jQuery);
