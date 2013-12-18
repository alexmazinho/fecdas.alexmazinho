/* Error IE. Se esperaba un identificador, una cadena o un número 
 * Cerca expressió regular ",[\s|\t|\n]*[}|\]]"
 * En la definició d'arrasy etc, l'últim no pot acabar amb coma */

(function($){
	
	
	confirmarPartePagat = function() {
		// Click des de Partes
		$('#formparte-button-pagat').click(function(e) {
			e.preventDefault();
			confirmarParteRecentPagat($(this).attr("href"));
		});
		
		// Click des de Recents
		$('.parterecents-action-pagament').click(function(e) {
			e.preventDefault();
			confirmarParteRecentPagat($(this).attr("href"));
		});
	};
	
	confirmarParteRecentPagat = function(url) {
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
	        title: "Confirmació pagament del parte",
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
		$("#dialeg").dialog({
			autoOpen: false,
		    modal: true
	    });
		
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
	
})(jQuery);
