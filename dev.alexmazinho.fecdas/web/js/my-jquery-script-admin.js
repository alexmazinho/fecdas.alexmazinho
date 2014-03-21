/* Error IE. Se esperaba un identificador, una cadena o un número 
 * Cerca expressió regular ",[\s|\t|\n]*[}|\]]"
 * En la definició d'arrasy etc, l'últim no pot acabar amb coma */

(function($){
	
	changeRoleClub = function(url)  {
		$("#menu-user select#form_role").select2({
			minimumInputLength: 2
		});
		$("#menu-user select#form_role").change(function(e) {
			var params = { 	roleclub:e.val };
			$.get(url,	params,
			function(data) {
				window.location = window.location.pathname; 
			}); // Canvi de rol
		});
	}
	
	selectRecentsClub = function() {
		$("select#form_clubs").select2({
			minimumInputLength: 2,
			allowClear: true
		});
		
		// Remove label on Select
		$("select#form_clubs").change(function(e) {
			if (e.val == "") $("#formrecents-club label").show();
			else $("#formrecents-club label").hide();
		});
	};

	
	recentsReload = function(url) {
		var params = []; 
		params.push( {'name':'clubs','value': $('#form_clubs').val()} );
		params.push( {'name':'estat','value': $('#form_estat').val()} );
		params.push( {'name':'baixa','value': ($('#form_baixa').is(':checked'))?1:0} );
		params.push( {'name':'nopagat','value': ($('#form_nopagat').is(':checked'))?1:0} );
		params.push( {'name':'nosincro','value': ($('#form_nosincro').is(':checked'))?1:0} );

		llistaPaginationAndSort(url, params);
	}

	
	confirmarPartePagat = function() {
		// Click des de Partes
		$('#formparte-button-pagat').click(function(e) {
			e.preventDefault();
			confirmarPagament($(this).attr("href"), "Confirmació de pagament");
		});
		
		// Click des de Recents
		$('.parterecents-action-pagament').click(function(e) {
			e.preventDefault();
			confirmarPagament($(this).attr("href"), "Confirmació de pagament");
		});
	};
	
	confirmarPagament = function(url, titol) {
		$("#dialeg").dialog({
			autoOpen: false,
		    modal: true,
          	buttons : {
            	"Confirmar" : function() {
    	        	$(this).dialog("close");
    	        	
    	    		var params = { 	datapagat: $( "#datepicker" ).val(), 
    	    						estatpagat: $( "#pagatestat" ).val(),
    	    						dadespagat: $( "#pagatdades" ).val(),
    	    						comentaripagat: $( "#pagatcomentari" ).val() };
    	    		$.get(url, params,
    	    		function(data, textStatus) {
    	    			location.reload();
    	    		});
    	        },
	            "Cancel·lar" : function() {
	    			//Cancel submit behavior
	            	$(this).dialog("close");
	            }
	        },
	        title: titol,
	        height: 450,
	        width: 350,
	        zIndex:	350
	    });
		
	    $("#dialeg").html("<p>Indica la data <input type='text' id='datepicker' disabled='disabled'/></p>");
	    $("#dialeg").append("<p>Raó del pagament <select type='text' id='pagatestat' required='required'>" +
	    					"<option selected='selected' value='TRANS WEB'>Transferència rebuda</option>" +
	    					"<option value='METALLIC WEB'>Pagament en metàlic</option>" +
	    					"<option value='TPV CORRECCIO'>Correcció errada TPV</option></select></p>");
	    $("#dialeg").append("<p>Número de comanda TPV o rebut <input type='text' id='pagatdades' required='required'/></p>");
	    $("#dialeg").append("<p>Comentari<textarea id='pagatcomentari' required='required'/></p>");
	    
	    $( "#datepicker" ).datepicker({
            showOn: "button",
            buttonImage: "/images/icon-calendar.gif",
            buttonImageOnly: true,
            dateFormat: 'dd/mm/yy'
        });
	    
	    $( "#datepicker" ).datepicker( "setDate", new Date() );
		    
	    $("#dialeg").dialog("open");
	};
	
	
	clubCanviEstat = function() {
		$('.clubs-action-upd').click(function(e) {
			e.preventDefault();
			
			var hrefCanviEstat = $(this).attr('href');
			var mostrarForm = hrefCanviEstat.indexOf('DIF') >= 0; // Form pagamanet diferit
			var estat = $(this).find('img').attr('title');
			
			$("#dialeg").dialog({
	          	buttons : {
	            	"Confirmar" : function() {
	            		
	            		if ($("#club-limitcredit").val() != "" && isNaN($("#club-limitcredit").val())) {
	        	        	alert("El límit de crèdit ha de ser numèric");
	        				return false;
	        	        }

            			var maskHeight = $(document).height();
            	        var maskWidth = $(window).width();

            	        //Set height and width to mask to fill up the whole screen
            	        $('.mask').css({'width':maskWidth,'height':maskHeight});
            	        //transition effect    
            	        $('.mask').fadeTo("slow",0.6); 

            	        var params = { limitcredit: $("#club-limitcredit").val(), imprimir: $("#club-imprimir").is(':checked') };

            	        $.get(hrefCanviEstat, params,
	            		function(data, textStatus) {
	            	    	$('#formclubs').submit();
	            		});

	    	        	$(this).dialog("close");
	    	        },
		            "Cancel·lar" : function() {
		    			//Cancel submit behavior
		            	$(this).dialog("close");
		            }
		        },
		        title: "Confirmació canvi d'estat",
		        width: 400,
		        zIndex:	350
		    });
			
			$("#dialeg").html("<div class='club-dialog-action'>"+$(this).parents().children('.club-nom').html()+"</div>");
		    $("#dialeg").append("<div class='club-dialog-action'>"+estat+"</div>");
		    $("#dialeg").append("<div class='club-dialog-row'><div class='club-dialog-label'>Límit de crèdit per al club (€)</div>" +
		    		" <input id='club-limitcredit' type='text' value='"+$(this).parents().children('.club-limit').attr('data-limit')+"' /></div>");			    
		    $("#dialeg").append("<div class='club-dialog-row'><div class='club-dialog-label'>Impressió web de llicències pendents de pagament?</div>" +
		    					"<input id='club-imprimir' type='checkbox' value=1' /></div>");
			if (!mostrarForm) $("#dialeg").find('.club-dialog-row').hide();
			
		    
		    $("#dialeg").dialog("open");
		});
	};
	
	
	clubsSaldosReload = function(url) {
		var params = []; 
		params.push( {'name':'estat','value': $('#form_estat').val()} );

		llistaPaginationAndSort(url, params);
	}

	
	varisAdminPeticioDuplicat = function() {
		// Click des de Partes
		$('a.duplicat-dades').click(function(e) {
			e.preventDefault();
			var varFactura = $(this).attr('data'); 
			dadesPagamentFactura($(this).attr("href"), "Afegir dades de pagament i/o facturació", varFactura);
		});
		
		$('a.duplicat-impres').click(function(e) {
			e.preventDefault();
			adminConfirm($(this).attr("href"), "Petició impresa", "<p>La petició es marcarà com impresa i s'enviarà un correu al club, vols continuar?</p>", 350, 170);
		});
		
		$('a.duplicat-esborrar').click(function(e) {
			e.preventDefault();
			adminConfirm($(this).attr("href"), "Anul·lar petició", "<p>Segur que vols anul·lar aquesta petició?</p>", 350, 150);
		});

	};
	
	dadesPagamentFactura = function(url, titol, fact) {
		$("#dialeg").dialog({
			autoOpen: false,
		    modal: true,
          	buttons : {
            	"Confirmar" : function() {
            		if ($( "#numfactura" ).val() != '' && isNaN($( "#numfactura" ).val() ))  {
            			$("#numfactura").val("");
            			$("#dialeg #anyfactura").parent().append("<span class='error'>Incorrecte</span>");
            			
            		} else {
            		
    	        	$(this).dialog("close");
    	        	
    	    		var params = {  datafactura: $( "#fdatepicker" ).val(),
    	    						numfactura: $( "#numfactura" ).val(),
    	    						datapagat: $( "#pdatepicker" ).val(), 
    	    						estatpagat: $( "#pagatestat" ).val(),
    	    						dadespagat: $( "#pagatdades" ).val(),
    	    						comentaripagat: $( "#pagatcomentari" ).val() };
    	    		$.get(url, params,
    	    		function(data, textStatus) {
    	    			location.reload();
    	    		});
            		}
    	        },
	            "Cancel·lar" : function() {
	    			//Cancel submit behavior
	            	$(this).dialog("close");
	            }
	        },
	        title: titol,
	        height: 600,
	        width: 350,
	        minWidth: 350,
	        zIndex:	350
	    });
		
		var curYear = (new Date()).getFullYear();
		$("#dialeg").html("<p class='dialeg-titol'>Dades de la factura (opcional)</p>");
		//$("#dialeg").append("<p>Activar? <input type='checkbox' id='chkfactura' value='0'/></p>");
	    $("#dialeg").append("<p>Data <input type='text' id='fdatepicker' disabled='disabled'/></p>");
	    $("#dialeg").append("<p>Número <input type='text' id='numfactura' value='"+fact+"'/>"+
	    					" / <input type='text' id='anyfactura' disabled='disabled' value='"+curYear+"'/></p>");
	    $("#dialeg").append("<p class='dialeg-titol'>Dades del pagament (opcional)</p>");
	    $("#dialeg").append("<p>Data <input type='text' id='pdatepicker' disabled='disabled'/></p>");
	    $("#dialeg").append("<p>Raó del pagament <select type='text' id='pagatestat' required='required'>" +
	    					"<option selected='selected' value=''>--</option>" +			
	    					"<option value='TRANS WEB'>Transferència rebuda</option>" +
	    					"<option value='METALLIC WEB'>Pagament en metàlic</option>" +
	    					"<option value='TPV CORRECCIO'>Correcció errada TPV</option></select></p>");
	    $("#dialeg").append("<p>Número de comanda TPV o rebut <br/><input type='text' id='pagatdades' required='required'/></p>");
	    $("#dialeg").append("<p>Comentari<br/><textarea id='pagatcomentari' required='required'/></p>");
	    
	    $( "#fdatepicker" ).datepicker({
            showOn: "button",
            buttonImage: "/images/icon-calendar.gif",
            buttonImageOnly: true,
            dateFormat: 'dd/mm/yy'
        });
	    $( "#pdatepicker" ).datepicker({
            showOn: "button",
            buttonImage: "/images/icon-calendar.gif",
            buttonImageOnly: true,
            dateFormat: 'dd/mm/yy'
        });
	    
	    
	    $( "#fdatepicker" ).datepicker( "setDate", new Date() );
	    $( "#pdatepicker" ).datepicker( "setDate", new Date() );
		
	    
	    // Checkboxes activació dades 
	    /*
	    $("#chkfactura").click(function(){
			var checked = $(this).is(':checked');
			if (checked) {
				$('#numfactura').removeAttr('disabled'); 	
				$('#anyfactura').removeAttr('disabled');
			} else {
				$('#numfactura').attr('disabled', 'disabled'); 	
				$('#anyfactura').attr('disabled', 'disabled');
			}
		});*/
	    
	    
	    $("#dialeg").dialog("open");
	};
	
	
	adminConfirm  = function(url, titol, text, dwidth, dheight) {
		$("#dialeg").dialog({
			autoOpen: false,
		    modal: true,
          	buttons : {
            	"Confirmar" : function() {
            		$(this).dialog("close");
            		window.location = url;
    	        },
	            "Cancel·lar" : function() {
	    			//Cancel submit behavior
	            	$(this).dialog("close");
	            }
	        },
	        title: titol,
	        height: dheight,
	        width: dwidth,
	        zIndex:	350
	    });
		
	    $("#dialeg").html(text);
		    
	    $("#dialeg").dialog("open");
	};
	
})(jQuery);
