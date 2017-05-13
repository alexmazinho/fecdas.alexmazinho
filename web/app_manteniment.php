<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" content="text/html" http-equiv="Content-Type">
	<title>Aplicació gestió FECDAS</title>
	<link href="css/layout.css" rel="stylesheet" type="text/css">
	<link href="css/style.css" rel="stylesheet" type="text/css">
	<link href="favicon.ico" rel="shortcut icon">
	
	<link href="css/style.css?v=2.35" type="text/css" rel="stylesheet" />
    <link href="css/responsive.css?v=2.01" type="text/css" rel="stylesheet" />
    <link href="css/redmond/jquery.ui.theme.css" type="text/css" rel="stylesheet" />
    <link href="css/redmond/jquery-ui.min.css" type="text/css" rel="stylesheet" />
    <link href="css/bootstrap.min.css" type="text/css" rel="stylesheet" />
	
</head>
<body>
	<section id="wrapper" class="container-fluid">
		<header class="row header-nologin" id="header">
			<div class="logo">
				<a href="http://www.fecdas.cat" target="_blank"><img src="/images/fecdaslogo.png"></a>
			</div>
			<div class="title">
				Aplicació de tramitació de llistes d'assegurats<br>
				<i>Federació Catalana d'Activitats Subaquàtiques</i>
			</div>
			<div class="login">
				<div id="menu-user" style="opacity: 1; display: block;">
					
				</div>
			</div>
		</header>
		<section class="row" id="main">
			<div class="container" id="main-col" style="display: block;">
				<div class="home narrow page-center">
					<nav>
						<ul class="nav nav-pills">
						</ul>
					</nav>
					<div class="home-content content-center">
						<header>
							<h1>Aplicació en manteniment</h1>
							
						</header>
						<section>
							<h3>En breu tornarà a estar disponible, gràcies</h3>
						</section>
						<p>&nbsp;<br/><br/><br/></p>
						<ul class="rslides rslides1" id="slider">
							<!--[if lt IE 8]><div class="fluxslider"><![endif]-->
							<li class="" id="rslides1_s0" style="display: block; float: none; position: absolute; opacity: 0; z-index: 1; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg1" src="/images/header_1.jpg"></li>
							<li class="" id="rslides1_s1" style="float: none; position: absolute; opacity: 0; z-index: 1; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg2" src="/images/header_2.jpg"></li>
							<li class="" id="rslides1_s2" style="float: none; position: absolute; opacity: 0; z-index: 1; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg3" src="/images/header_3.jpg"></li>
							<li class="" id="rslides1_s3" style="float: none; position: absolute; opacity: 0; z-index: 1; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg4" src="/images/header_4.jpg"></li>
							<li class="rslides1_on" id="rslides1_s4" style="float: left; position: relative; opacity: 1; z-index: 2; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg5" src="/images/header_5.jpg"></li>
							<li id="rslides1_s5" style="float: none; position: absolute; opacity: 0; z-index: 1; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg6" src="/images/header_6.jpg"></li>
							<li id="rslides1_s6" style="float: none; position: absolute; opacity: 0; z-index: 1; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg7" src="/images/header_7.jpg"></li>
							<li id="rslides1_s7" style="float: none; position: absolute; opacity: 0; z-index: 1; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg8" src="/images/header_8.jpg"></li>
							<li id="rslides1_s8" style="float: none; position: absolute; opacity: 0; z-index: 1; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg9" src="/images/header_9.jpg"></li>
							<li id="rslides1_s9" style="float: none; position: absolute; opacity: 0; z-index: 1; display: list-item; transition: opacity 500ms ease-in-out 0s;"><img id="sliderimg10" src="/images/header_10.jpg"></li><!--[if IE]></div><![endif]-->
						</ul>
					</div>
				</div>
			</div>
		</section>
		<section class="row" id="footer">
			<div class="powered col-xs-2">
				<div class="footer-text" style="float: left;">
					Powered by<br>
					Symfony2
				</div>
				<div><img src="/images/sf-logo.png" width="25px"></div>
			</div>
			<div class="fecdas-footer col-xs-8">
				<p><a href="http://www.fecdas.cat">FECDAS</a> - copyright 2017<br>
				Federació Catalana d'Activitats Subaquàtiques NIF: Q5855006B</p>
			</div>
			<div class="created-by col-xs-2">
				<span class="footer-text"><br>
				creat per</span> <a href="http://www.ondissenyweb.com" target="_blank">On</a>
			</div>
		</section>
	</section>

	<script src="js/jquery.min.js" type="text/javascript"></script>
	<script src="js/jquery-ui-min.js" type="text/javascript"></script>        	
    <script src="/js/bootstrap.min.js"></script>
       
    <script src="js/responsiveslides.min.js" type="text/javascript"></script>

	<script type="text/javascript">
		if (($.browser.msie && parseInt($.browser.version, 10) < 8) ||
		    ($.browser.webkit && parseInt($.browser.version, 10) < 14) ||
		    ($.browser.mozilla && parseInt($.browser.version, 10) < 11) ||
		    ($.browser.opera && parseInt($.browser.version, 10) < 11)) {
			/* http://browsershots.org
			IE >= 9, Firefox >= 11, Chorme >=14, Opera >= 11, Safari >= 5 */
			location.href="/browsernotsupported.html";
		};
	</script>

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