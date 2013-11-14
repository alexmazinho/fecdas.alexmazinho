/* Error IE. Se esperaba un identificador, una cadena o un número 
 * Cerca expressió regular ",[\s|\t|\n]*[}|\]]"
 * En la definició d'arrasy etc, l'últim no pot acabar amb coma */

(function($){
	
	/************************************************* Enquestes *****************************************************/	
	gestionarSortables = function() {
		$( '#sortablesi' ).sortable({
			connectWith: '.connectedSortable',
			remove: function( event, ui ) {
				if ($(this).children().length == 0) {
					$(this).sortable('cancel');
					alert('L\'enquesta ha de tenir almenys una pregunta');
				};
			}
	 	});
		$( '#sortableno' ).sortable({
			connectWith: '.connectedSortable',
			receive: function( event, ui ) {
				$('.pregunta-excluded').hide(); 
			},
			remove: function( event, ui ) {
				if ($(this).children().length == 1) {
					$('.pregunta-excluded').show(); 
				};
			}
	 	});
		
		$( '#sortablesi, #sortableno' ).disableSelection();
	};
	
	submitEnquestes = function() {
		$('#formenquesta').submit( function(){
			var url = $("#formenquesta").attr("action");
			var params = $('#formenquesta').serializeArray();
			var preguntes = $('#sortablesi').sortable('serialize');
			params.push( {'name':'preguntes','value': preguntes} );
			
			url = url + '?' + preguntes;
			
			$.post(url, params,
			function(data, textStatus) {
				location.reload();
			});	
			return false;
		});
	};
	
	showEnquestaClick = function() {
	    //select all the a tag with name equal to modal
		$('.enquesta-action-open')
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

			$.get(url, function(data) {
				if (data == "error") location.reload();
				else {
					$("#enquesta").html(data);
					// Prepare modal
					actionsModalOverlay();
					
					$('.form-button-save').click(function (e) {
				        //Guargar
				        e.preventDefault();
				        submitEnquesta();
					});

					$('.form-button-submit').click(function (e) {
				        //Guardar i tancar
				        e.preventDefault();
				        submitEnquesta();
				        $('#enquesta').hide();
				        $('.mask').hide();
						$("#enquesta").html("");
					});

					// 	Show Div
					showModalDiv('#enquesta');
				}
			});
	    });
	};
	
	submitEnquesta = function() {
		var url = $('#formenquesta').attr("action");
		var params = $('#formenquesta').serializeArray();
		$.post(url, params, function(data) {
			alert(data);
		});
	};
	

	loadProgressEnquestes = function() {
		$( ".enquesta-enviaments-progressbar" ).each(function( index ) {
			var progressbar = $(this);
			var valorinicial = $(this).parent().prev().html();
			var progresslabel = $(this).parent().next();

			$(this).progressbar({
				value: 0,	
				change: function() {
					progresslabel.text( $(this).progressbar( "value" ) + " %" );
				}
			});

			var progress = function() {
				var val = progressbar.progressbar("value") || 0;

				if ( val < valorinicial ) {
					progressbar.progressbar( "value", val + 1 );
					setTimeout( progress, 100 ); // Recursive
				}
			}
			setTimeout( progress, 1000 ); // Start 1 second after
		});

	};
	
	showEnquestaPlot = function() {
		$('.enquesta-action-stats')
		.off('click')
		.click(function(e) {
			//Cancel the link behavior
			e.preventDefault();

			var url = $(this).attr("href");

			$('#enquesta-plots').html('');
			if ($.browser.msie) $('#enquestes-llista').hide(); 
			else $('#enquestes-llista').slideUp('slow');
			if ($.browser.msie) $('#enquesta-stats').show(); 
			else $('#enquesta-stats').slideDown('slow');

			$.get(url, function(data) {
				
				//alert(data);
				
				
				$("#enquesta-plots").html(data);
				
				$( ".resultat-pregunta-data" ).each(function(index ) {
					var s1 = $.parseJSON($(this).html());
					
					// s1= (10, 3, 7, 20, 15)   ['gens', 'poc', 'suficient', 'bastant', 'molt'] pregunta 1
					// s2= (10, 3, 7, 20, 15)	['gens', 'poc', 'suficient', 'bastant', 'molt'] pregunta 2

					//s1 = [[10],[2]];
					
					var plotdiv = $(this).next();
					var seriesRang =  [ { label: 'gens' },
						                { label: 'poc' },
						                { label: 'suficient' },
						                { label: 'bastant' },
						                { label: 'molt' }];
					var seriesBool =  [ { label: 'Si' },
						                { label: 'No' }];
					
					createPlotEnquesta(plotdiv.attr("id"), s1, (s1.length == 5) ? seriesRang : seriesBool);
				});
				
			});
		});
	};

	createPlotEnquesta = function(id, dades, serieslabel) {
		var plot1 = $.jqplot(id,dades,{
			title: {
				text: '',
				show: false
			},
			stackSeries: true,
			animate: true,  // ??
			axes: {
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    rendererOptions: {
                    	tickRenderer: $.jqplot.AxisTickRenderer,
                    	tickOptions: {
                    		showLabel: false,
                    		fontSize: '14px',
                    		mark: null
                    	}
	                },
	                ticks: ['']
                },
                xaxis: {min: 0, max: 150, numberTicks:11},
            },
			seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                rendererOptions: {
                    highlightMouseDown: true,
                    barDirection: 'horizontal'
                },
                pointLabels: { show: true }
            },
            legend: {
            	renderer: $.jqplot.EnhancedLegendRenderer,
                show: true,
                location: 'n',
                rendererOptions: {
                    numberRows: 1
                },
                placement: 'outsideGrid'
            },  
            grid: {
            	backgroundColor: 'transparent',
                drawBorder: false,
                drawGridlines: false,
                background: '#ffffff',
                shadow:false
            },
            series: serieslabel
             
		});
	};
	
	amagarEnquesta = function() {
		$('#enquesta-plots').html('');

		if ($.browser.msie) $('#enquesta-stats').hide(); 
	    else $('#enquesta-stats').slideUp('slow');
		
    	if ($.browser.msie) $('#enquestes-llista').show(); 
    	else $('#enquestes-llista').slideDown('slow');
		
	};
	
	descarregarEnquesta = function() {
		/*
		 * var imgData = $('#chart1').jqplotToImageStr({}); // given the div id of your plot, get the img data
var imgElem = $('<img/>').attr('src',imgData); // create an img and add the data to it
$('#imgChart1').append(imgElem);
		 * 
		 */
	};
	
	createPlotResGlobal = function() {
		var dades = $.parseJSON($("#resultats-dades-globals").html());
		var enunciats = $.parseJSON($("#resultats-enunciats").html());
		//alert(enunciats);
		
		var plot1 = $.jqplot('resultats-plot-global',dades,{
			title: {
				text: '',
				show: false
			},
			animate: true,  // ??
			animateReplot: true,
			seriesDefaults:{
				//showMarker: false,
                rendererOptions: {
                	//smooth: true,
                    animation: {
                        show: true
                    }
                }
            },
            series: enunciats,
            axesDefaults: {
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                tickOptions: {
                    fontSize: '14px'
                }
            },
            axes: {
                // These options will set up the x axis like a category axis.
                xaxis: {
                	renderer: $.jqplot.CategoryAxisRenderer,
                	tickOptions: {
                		mark : 'cross',
                    }
                },
                yaxis: {
                	min: 0,
                	max: 5,
                	numberTicks: 6,
                	tickOptions: {
                		showMark : true,
                        labelPosition: 'start',
                        markSize: 4,
                    }
                },
            },
            legend: {
                show: true,
                renderer: $.jqplot.EnhancedLegendRenderer,
                placement: 'outsideGrid',
                /*labels: ["enunciat 1 enunciat 1 enunciat 1 enunciat 1", "enunciat 2 enunciat 2 enunciat 2 enunciat 2	"],*/
                showLabels: true,
                showSwatches: true,
                location: 'e',
                rendererOptions: {
                    numberCols: 1
                },
                //rowSpacing: '0px'
            },
            grid: {
            	backgroundColor: 'transparent',
                drawBorder: false,
            }
             
		});
	};

	createPlotEvolucio = function() {
		var dades = $.parseJSON($("#resultats-dades-evolucio").html());
		var valors = $.parseJSON($("#resultats-valors").html());
		var mesures = $.parseJSON($("#resultats-mesures").html());
		//var totals = array(80, 76, 76, 50);
		
		var plot1 = $.jqplot('resultats-plot-evolucio', dades,{
			title: {
				text: '',
				show: false
			},
			//animate: true,  // ??
			stackSeries: true,
			seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                rendererOptions: {
                    highlightMouseDown: true,
                    barMargin: 25,
                    barDirection: 'vertical',
                    dataLabels: ['value', 'percent'],
                    dataLabelFormatString: "%d %d%%",
                    animation: {
                        show: true
                    }
                },
                pointLabels: { 
                	show: true, 
                	formatString: "%#.2f %%"
                }
            },
            series: valors,
			axesDefaults: {
	                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	                tickRenderer: $.jqplot.CanvasAxisTickRenderer,
	                tickOptions: {
	                    fontSize: '14px'
	                }
	            },
			axes: {
				xaxis: {
                	renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: mesures
                },
                yaxis: {
                	min: 0,
                	max: 150,
                	numberTicks: 11,
                	tickOptions: {
                		showMark : true,
                        labelPosition: 'start',
                        markSize: 4,
                    }
                },
				/*
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    rendererOptions: {
                    	tickRenderer: $.jqplot.AxisTickRenderer,
                    	tickOptions: {
                    		showLabel: false,
                    		mark: null
                    	}
	                },
	                ticks: ['']
                },
                xaxis: {min: 0, max: 150, numberTicks:11},*/
            },
            legend: {
            	renderer: $.jqplot.EnhancedLegendRenderer,
                show: true,
                location: 'n',
                rendererOptions: {
                    numberRows: 1
                },
                placement: 'outsideGrid'
            },  
            grid: {
            	backgroundColor: 'transparent',
                drawBorder: false,
                //drawGridlines: false,
                //background: '#ffffff',
                //shadow:false
            }
           
             
		});
	};
	
	
	/*****************************************************************************************************************/
	
})(jQuery);
