/* Error IE. Se esperaba un identificador, una cadena o un número 
 * Cerca expressió regular ",[\s|\t|\n]*[}|\]]"
 * En la definició d'arrasy etc, l'últim no pot acabar amb coma */

(function($){
	
	selectRecentsClub = function() {
		$("select#form_clubs").select2({
			minimumInputLength: 2,
			allowClear: true,
			placeholder: "Filtre per club... ",
		});
		
		// Remove label on Select
		$("select#form_clubs").change(function(e) {
			if (e.val == "") $("#formrecents-club label").show();
			else $("#formrecents-club label").hide();
		});
	};

	//Cercador de productes
	init_cercaproducte_JSON = function(elem_sel, tipus, placeholder_txt, url, baixes) {
		
		/* Inicialitza el control de cerca (input hidden) */
		$(elem_sel).select2({
			minimumInputLength: 2,
			allowClear: true,
			multiple: false,
			placeholder: placeholder_txt,
	
			query: function (query) {
				var data = { results: [] };
				var params = { 	'cerca': query.term, 'tipus': tipus, 'baixes': baixes };
				// Consulta activitats %desc% que no tingui assignades la persona o no sigui alguna de les excepcions 
				$.get(url,	params, function(jdata) {
					data.results = jdata;
					query.callback(data);
				}).fail(function() {
					query.callback(data);
				});
			},
			initSelection: function(element, callback) {  // value del input ==> carrega per defecte llista de persones. (Retorn del POST per exemple)
				//if (element.val() !== undefined && element.val() > 0) {
					var data = [];
					var params = { 	'id': element.val(), 'tipus': tipus };
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
	
	//Cercador de clubs
	init_cercaclub_JSON = function(elem_sel, placeholder_txt, url) {
		
		/* Inicialitza el control de cerca (input hidden) */
		$(elem_sel).select2({
			minimumInputLength: 3,
			allowClear: true,
			multiple: false,
			placeholder: placeholder_txt,
	
			query: function (query) {
				var data = {results: []};
				var params = { 	cerca: query.term };
				// Consulta activitats %desc% que no tingui assignades la persona o no sigui alguna de les excepcions 
				$.get(url,	params, function(jdata) {
					data.results = jdata;
					query.callback(data);
				}).fail(function() {
					query.callback(data);
				});
			},
			initSelection: function(element, callback) {  // value del input ==> carrega per defecte llista de persones. (Retorn del POST per exemple) 
				var data = [];
				var params = { 	id: element.val() };
				$.get(url,	params, function(jdata) {
					callback(jdata);
				}).fail(function() {
					callback(data);
				});
				
		        callback(data);
			} 
		});
	};
	
	//Cercador de federats
	init_cercapersones_JSON = function(elem_sel, placeholder_txt, url, callbackform) {
		
		/* Inicialitza el control de cerca (input hidden) */
		$(elem_sel).select2({
			minimumInputLength: 3,
			allowClear: true,
			multiple: false,
			placeholder: placeholder_txt,
	
			query: function (query) {
				var data = { results: [] };
				var params = { 	'cerca': query.term };
				$.get(url,	params, function(jdata) {
					data.results = jdata;
					//console.log(JSON.stringify(data.results));
					
					query.callback(data);
				}).fail(function() {
					query.callback(data);
				});
			},
			initSelection: function(element, callback) {  // value del input ==> carrega per defecte llista de persones. (Retorn del POST per exemple)
				//if (element.val() !== undefined && element.val() > 0) {
					var data = [];
					var params = { 	'id': element.val() };
					$.get(url,	params, function(jdata) {
						// Selecció inicial de persona
						callbackform(jdata);
						
						callback(jdata);
					}).fail(function() {
						callback(data);
					});
					
			        callback(data);
				//}
			} 
		});
		
		$(elem_sel).on("change", function(e) {
			// Canvi de persona
			callbackform(e.added);
		});
	};
	
	confirmarPagament = function(url, titol, urlok) {
		
		$(".alert").remove();
		
		$("#dialeg").dialog({
			autoOpen: false,
		    modal: true,
          	buttons : {
            	"Confirmar" : function() {
    	        	$(this).dialog("close");
    	        	
    	        	$('#progressbar').show();  // Rellotge
        	
    	    		var params = { 	datapagament: $( "#datapagament" ).val(), 
    	    						tipuspagament: $( "#tipuspagament" ).val(),
    	    						dadespagament: $( "#dadespagament" ).val(),
    	    						pagatcomentari: $( "#pagatcomentari" ).val() };
    	    		$.get(url, params,
    	    		function(data, textStatus) {
    	    			$('#progressbar').hide();
    	    	        	
    	    			if (urlok !== undefined) window.location = urlok; 
    	    			else location.reload();
    	    		}).fail( function(xhr, status, error) {
    	   			 // xhr.status + " " + xhr.statusText, status, error
	    	   			//var sms = smsResultAjax('KO', xhr.responseText);
	    	   			$('#progressbar').hide();  // Rellotge
	    	   		    
	    	   			var sms = smsResultAjax('KO', xhr.responseText);
	   			    	
	    	   			$("#main-col").prepend(sms);
	    	   			
	    	   			//$('#parte_tipus').val('');
	    	   			 
	    	   			//$('#formparte-llicencia').html(sms);
	    	   			$( '#dialeg' ).html('');
	    	   			$(this).dialog( "destroy" );
    	    		});
    	        },
	            "Cancel·lar" : function() {
	    			//Cancel submit behavior
	            	
	            	$( '#dialeg' ).html('');
	            	$(this).dialog( "destroy" );
	            }
	        },
	        title: titol,
	        height: 'auto',
	        width: 350,
	        zIndex:	350
	    });
		
	    $("#dialeg").html("<p>Indica la data <input type='text' id='datapagament' disabled='disabled'/></p>");
	    $("#dialeg").append("<p>Raó del pagament <select type='text' id='tipuspagament' required='required'></select></p>");
	    $("#dialeg").append("<p>Dades opcionals. Núm. comanda TPV, etc... <input type='text' id='dadespagament' required='required'/></p>");
	    $("#dialeg").append("<p>Comentaris<textarea id='pagatcomentari' required='required'/></p>");
	    
	    $( "#datapagament" ).datepicker({
            showOn: "button",
            //buttonImage: "/images/icon-calendar.gif",
            buttonText: "<i class='fa fa-calendar fa-1x blue'></i>",
            //buttonImageOnly: true,
            dateFormat: 'dd/mm/yy'
        });
	    
	    $( "#datapagament" ).datepicker( "setDate", new Date() );

		$.get( "/jsontipuspagaments" , function( data ) {
			  var tipusPagament = JSON.parse( JSON.stringify(data), function (k, v) {
				    return v; 
			  });
			  var htmlOpcio = ''; 
			  $.each(tipusPagament, function(i, item) {
			       htmlOpcio = "<option value='"+i+"' "+(i==3?"selected":"")+">"+item+"</option>";  // Select per defecte trans la Caixa
			       $('#tipuspagament').append( htmlOpcio );
			  });
		});
		
	    $("#dialeg").dialog("open");
	};
	
	clubCanviEstat = function() {
		$('.clubs-action-upd').click(function(e) {
			e.preventDefault();
			
			var hrefCanviEstat = $(this).attr('href');
			var mostrarForm = hrefCanviEstat.indexOf('DIF') >= 0; // Form pagamanet diferit
			//var estat = $(this).find('img').attr('title');
			var estat = $(this).attr('title');
			
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

		redirectLocationUrlParams(url, params);
	};

	
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
	
	
	baixaClub = function( urlCallback ) {
		/* baixa club */
		$('.formclub-baixa')
	    .off('click')
	    .click(function(e) {
			//Cancel the link behavior
	    	e.preventDefault();
	    	
			var url = $(this).attr('href');
			
			var strHtml = '<p>Segur que vols donar de baixa el club?</p>';
			dialegConfirmacio(strHtml, 'Baixa club', 'auto', 400, function() { 

				$('#progressbar').show();
				
				$.get(url, function(data, textStatus) {
	    	    	$('#progressbar').hide();
	    	    	
	    	    	window.location = urlCallback;
	    	    			
	    	    }).fail( function(xhr, status, error) {
	    	   			 // xhr.status + " " + xhr.statusText, status, error
		    	   			//var sms = smsResultAjax('KO', xhr.responseText);
		    		$('#progressbar').hide();  // Rellotge
		    	   		    
		    		var sms = smsResultAjax('KO', xhr.responseText);
		   			    	
		    	   	$("#main-col").prepend(sms);
   	    		});
				 
			}, function() { }, function() { });
	    	
		});
	};
	
})(jQuery);
