<!DOCTYPE html>
<html lang="ca">
    <head>
        <meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta http-equiv="CACHE-CONTROL" CONTENT="NO-CACHE" >
        <meta http-equiv="PRAGMA" CONTENT="NO-CACHE" >
        <meta http-equiv="EXPIRES" content="0" >
        <meta content="Aplicació gestió de la Federació Catalana d'Activitats Subaquàtiques - FECDAS" name="description">
		<meta content="index, follow" name="robots">
		<meta content="Copyright © 2012 FECDAS" name="copyright">
		<meta content="version 2.0" name="version">
		<meta content="OnDisseny Web. Alex Macia" name="author">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Aplicació gestió FECDAS</title>
        <!--[if lt IE 9]>
            <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        <link href="css/layout.css?v=3.01" type="text/css" rel="stylesheet" />
    	<link href="css/font-awesome/font-awesome.min.css" type="text/css" rel="stylesheet" />
    	<link href="css/bootstrap/bootstrap-theme.min.css" type="text/css" rel="stylesheet" />
    	<link href="css/bootstrap/bootstrap.min.css" type="text/css" rel="stylesheet" />	
        <link href="css/jquery-ui/jquery-ui.structure.css" type="text/css" rel="stylesheet" />
        <link href="css/jquery-ui/jquery-ui.theme.css" type="text/css" rel="stylesheet" />
        <link href="css/jquery-ui/jquery-ui.css" type="text/css" rel="stylesheet" />
        <link href="css/style.css?v=3.12" type="text/css" rel="stylesheet" />
        <link href="css/responsive.css?v=3.01" type="text/css" rel="stylesheet" />
    
    
        <link rel="shortcut icon" href="favicon.ico" />
        <script type="text/javascript">
		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-37415197-1']);
		  _gaq.push(['_trackPageview']);
		
		  (function() {
		    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();
		</script> 
    </head>
    <body>
    	<section id="wrapper" class="container-fluid">
            <header id="header"  class="row header-nologin">
                <div class="col-lg-1 col-md-1 col-sm-1 col-xs-2 logo"><a href="http://www.fecdas.cat" target="_blank"><img src="/images/fecdaslogo.png"></a></div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-10 title">Aplicació de tramitació de llistes d'assegurats<br>
    				<i>Federació Catalana d'Activitats Subaquàtiques</i></div>
                <div class="clearfix visible-xs-block">&nbsp;</div>
                <div class="col-lg-4 col-md-offset-0 col-md-4 col-xs-offset-2 col-xs-4 notifications"><div class="notifications"></div></div>
                <div class="col-lg-offset-0 col-lg-3 col-md-offset-0 col-md-3 col-sm-offset-0 col-sm-5 col-xs-offset-4 col-xs-8 col-last-right login"></div>
            </header>
            <section id="main" class="row">
		  		<div id="main-col" class="container">
		  			<nav>
    					<ul class="nav nav-pills">
    					</ul>
    				</nav>
	           		<div class="home narrow page-center"> 
                		<div class="home-content content-center"> 
                			<header>
    							<h1>Aplicació en manteniment</h1>
    							
    						</header>
    						<section>
    							
    						</section>
                		
                			<section>
                		        <div class="row">
                		    		
                        		    <div class="col-md-12">
                        		        <h3>En breu tornarà a estar disponible, gràcies</h3>
                					</div>
                				</div>
                		    </section>
                		
                			<ul id="slider">
                				<!--[if lt IE 8]><div class="fluxslider"><![endif]-->
                		    	<li><img id="sliderimg1" src="images/header_1.jpg" /></li>
                		    	<li><img id="sliderimg2" src="images/header_2.jpg" /></li>
                		    	<li><img id="sliderimg3" src="images/header_3.jpg" /></li>
                		    	<li><img id="sliderimg4" src="images/header_4.jpg" /></li>
                		    	<li><img id="sliderimg5" src="images/header_5.jpg" /></li>
                		    	<li><img id="sliderimg6" src="images/header_6.jpg" /></li>
                		    	<li><img id="sliderimg7" src="images/header_7.jpg" /></li>
                		    	<li><img id="sliderimg8" src="images/header_8.jpg" /></li>
                		    	<li><img id="sliderimg9" src="images/header_9.jpg" /></li>
                		    	<li><img id="sliderimg10" src="images/header_10.jpg" /></li>
                		    	<!--[if IE]></div><![endif]-->
                			</ul>
                		</div>
                	</div>
	    		</div>
	    	</section>
            <section id="footer" class="row">
                	<div class="powered col-xs-2">
                		<div class="footer-text" style="float: left;">Powered by<br>Symfony2 </div>
                		<div><img src="images/sf-logo.png" width="25px"/></div>
                	</div>
                	<div class="fecdas-footer col-xs-8"><p><a href="http://www.fecdas.cat">FECDAS</a> - Copyright {{ "now"|date("Y") }}.
                	<a class="link footer-link" href="{{ path('FecdasBundle_termesicondicions">Termes i condicions d'ús</a><br/>
                	Federació Catalana d'Activitats Subaquàtiques NIF: Q5855006B</p>
                    </div>
                    <div class="created-by col-xs-2">
					<span class="footer-text"><br/>creat per </span><a href="http://www.ondissenyweb.com" target="_blank">On</a>
					</div>
            </section>
			<div id="browser" style="display: none;"></div>           
        </section>

    	<script src="js/jquery3/jquery-3.2.1.js" type="text/javascript"></script>
    
        <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script> -->
        <script src="/js/bootstrap/bootstrap.min.js"></script>
    
    	<script src="js/jquery-ui/jquery-ui.js" type="text/javascript"></script>        	
        <script src="js/jquery-bubble-popup/jquery-bubble-popup-v3.min.js" type="text/javascript"></script>
        
        <script src="js/select2-3.4.5/select2.min.js" type="text/javascript"></script>
    	<script src="js/select2-3.4.5/select2_locale_ca.js" type="text/javascript"></script>
    	
    	<script src="js/my-jquery-script.js?v=3.12" type="text/javascript"></script>	
        <script src="js/my-jquery-script-admin.js?v=3.00" type="text/javascript"></script>
    
    	<script src="js/responsiveslides/responsiveslides.min.js" type="text/javascript"></script>
        
       	<script type="text/javascript" charset="utf-8">
        	$(document).ready(function() {
        		//setMenuActive("menu-home");
        
          		$('#main-col').css('display', 'block');
        
        		$("#slider").responsiveSlides();
        
        		browserAdvice();
        	});
             
       	</script>	
    </body>
</html>