<?php
namespace FecdasBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Form\FormError;
use FecdasBundle\Classes\Funcions;
use FecdasBundle\Classes\TcpdfBridge;

use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityDuplicat;
use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityUserLog;
use FecdasBundle\Entity\EntityRebut;
use FecdasBundle\Entity\EntityFactura;
use FecdasBundle\Entity\EntityComanda;
use FecdasBundle\Entity\EntitySaldos;
use FecdasBundle\Entity\EntityComandaDetall;
use FecdasBundle\Entity\EntityArxiu;
use FecdasBundle\Entity\EntityStock;
use FecdasBundle\Form\FormLlicenciaImprimir;
use FecdasBundle\Form\FormLlicenciaMail;


include_once (__DIR__.'/../../../vendor/tecnickcom/tcpdf/include/tcpdf_static.php');


define('CR', "\r");          // Carriage Return: Mac
define('LF', "\n");          // Line Feed: Unix
define('CRLF', "\r\n");      // Carriage Return and Line Feed: Windows
define('BR', '<br />' . LF); // HTML Break

class BaseController extends Controller {
	const PER_PAGE_DEFAULT = 10;
	const CLUBS_DEFAULT_STATE = 1;
	const TOTS_CLUBS_DEFAULT_STATE = 0;
	const CLUBS_STATES = 'Tots els tipus;Pagament diferit;Pagament immediat;Sense tramitació';
	const CLUB_SENSE_TRAMITACIO = 'NOTR';
	const CLUB_PAGAMENT_DIFERIT = 'DIFE';
	const CLUB_PAGAMENT_IMMEDIAT = 'IMME';
	const DIES_PENDENT_NOTIFICA = 1;
	const DIES_PENDENT_AVIS = 8;
	const DIES_PENDENT_MAX = 20;
	const PREFIX_MAIL_BCC = '{bcc}';
	const MAILER_RESET_SESSION = 10;
    const INICI_VALIDACIO_MAIL = '2016-09-01'; // A partir d'aquesta data cal indicar mail per tramitar (excepte llicència dia)
	const INICI_TRAMITACIO_ANUAL_DIA = 16; // a partir de 16/12 any en curs
	const INICI_TRAMITACIO_ANUAL_MES = 12; //12; // a partir de 15/12 any en curs
	const INICI_TRAMITACIO_QUATRIMESTRE_DIA = '01'; // a partir de 01/09 any en curs
	const INICI_TRAMITACIO_QUATRIMESTRE_MES = '09'; // a partir de 09/09 any en curs
	const INICI_REVISAR_CLUBS_DAY = '01';
	const INICI_REVISAR_CLUBS_MONTH = '04';
	const DATES_INFORME_TRIMESTRAL = '31/03;30/06;30/09;30/11';
	const PAGAMENT_USUARIS = 'usuari'; // Llicències pesca
	const PAGAMENT_LLICENCIES = 'llicencies';
	const PAGAMENT_DUPLICAT = 'duplicat';
	const PAGAMENT_ALTRES = 'varis';
	const UPLOADS_RELPATH = '/../../../fitxers/';  // Path is __DIR__.self::UPLOADS_RELPATH
	const UPLOADS_FOLDER = 'uploads/';  
	const ALIAS_FILES_FOLDER = '/fitxers/';
	const TMP_FOLDER = '/../../../tmp';
	const SEXE_HOME = 'H';
	const SEXE_DONA = 'M';
	const EDAT_MINIMA = 4;
    const MAX_TELEFON = 999999999;
    const LANG_CA = 'CA';
    const LANG_ES = 'ES';
    const ID_TIPUS_PARTE_LLICENCIES_A = 1;
    const ID_TIPUS_PARTE_LLICENCIES_F = 8;
    
	//const TIPUS_CLUBS_NO_COMANDES = array(6, 7);
    const TIPUS_CLUB_PERSONA_FISICA = 8;
	const REGISTRE_STOCK_ENTRADA = 'E';
	const REGISTRE_STOCK_SORTIDA = 'S';
	
	const TARIFA_MINPES3 = 10000; // 10 Kg
	const TARIFA_MINPES2 = 5000; // 5 Kg
	const PRODUCTE_CORREUS = 7590004;	// Abans juliol 2016 => 6290900 / 6290004
	const PRODUCTE_IMPRESS_PLASTIC_ID = 3;	
	const COMPTE_COMPTA_IVA = 4770000;
	const COMPTE_COMPTA_IVA_RED = 4770001; // 4%	 
	
	const PREFIX_ASSENTAMENTS = 'APU';  // Prefix del fitxer
	// Fitxer domiciliacions
	const PATH_TO_COMPTA_FILES = '/../../../fitxers/assentaments/';
	// Pedent de canviar fora document root
	const PATH_TO_WEB_FILES = '/../../../web/';
	// Varis
	const PATH_TO_VARIS_FILES = '/../../../fitxers/varis/';
	
	const TIPUS_PRODUCTE_LLICENCIES = 1;
	const TIPUS_PRODUCTE_DUPLICATS 	= 2;
	const TIPUS_PRODUCTE_KITS 		= 3;
	const TIPUS_PRODUCTE_MERCHA 	= 4;
	const TIPUS_PRODUCTE_CURSOS 	= 5;
	const TIPUS_PRODUCTE_ALTRES 	= 6;
	const TIPUS_PRODUCTE_MATERIAL   = 7;
	
	const DEBE 		= 'D';
	const HABER 	= 'H';
	
	const CODI_FECDAS 					= 'CAT999';		
	const CODI_CLUBTEST					= 'CAT000';
	const CODI_CLUBINDEFEDE				= 'CAT292';        // Federatives independents
	const CODI_CLUBINDEPENDENT			= 'CAT998';        // Merchandising independents
	const CODI_CLUBLLICWEB				= 'CAT311';        // Llicències WEB
	
	const CODI_PAGAMENT_CASH 			= 5700000;		// 5700000  Metàl·lic
	const CODI_PAGAMENT_CAIXA			= 5720001;		// 5720001  La Caixa
	const CODI_PAGAMENT_ESCOLA			= 5720002;		// 5720002  La Caixa Escola
	
	const CODI_DESPESES_FECDAS			= 6590002;		// 6590002  Despeses FECDAS
	
	const PRODUCTE_262_CARNET_CMAS		= 262;		// 262 Carnet CMAS sense llicència
		
	
	//const CODI_PAGAMENT_SARDENYA			= 5720003;		// 5720003  Sardenya
		
	const TIPUS_PAGAMENT_CASH 				= 1;		// 5700000  Metàl·lic
	const TIPUS_PAGAMENT_TPV				= 2;		// 5720001  La Caixa
	const TIPUS_PAGAMENT_TRANS_LAIETANIA	= 3;		// 5720001  La Caixa
	const TIPUS_PAGAMENT_TRANS_ESCOLA		= 4;		// 5720002  La Caixa Escola
	const TEXT_PAGAMENT_CASH 				= 'METÀL.LIC';
	const TEXT_PAGAMENT_TPV					= 'ON-LINE';
	const TEXT_PAGAMENT_TRANS				= 'TRANSFERÈNCIA GENERAL';	
	const TEXT_PAGAMENT_TRANS_ESCOLA		= 'TRANSFERÈNCIA ESCOLA';

	const TIPUS_COMANDA_LLICENCIES 		= 1;
	const TIPUS_COMANDA_DUPLICATS 		= 2;
	const TIPUS_COMANDA_ALTRES			= 3;
	
	const REBUTS	   = 1;
	const FACTURES	   = 2;
	const COMANDES	   = 3;
	const CURSOS       = 4;
	const TITULACIONS  = 5;
	
	const ANY_INICI_WEB	= 2012;
	
	const PREFIX_ALBARA_DUPLICATS = 'D';
	const PREFIX_ALBARA_LLICENCIES = 'L';
	const PREFIX_ALBARA_ALTRES = 'A';
	
	// Duplicats 
	const DUPLICAT_LLICENCIA = 1;
	const DUPLICAT_CARNET_CMAS = 3;
	
	// Tipus de partes 
	const TIPUS_TECNOCAMPUS_1 = 9;
	const TIPUS_ESCOLAR = 4;
	const TIPUS_D_1DIA = 11;
	const TIPUS_CENTRES_1DIA = 18;
	const TIPUS_FEDE_7DIES = 16;
	const TIPUS_CENTRES_7DIES = 19;
	const TIPUS_FEDE_30DIES = 17;
	const TIPUS_CENTRES_30DIES = 20;

	const TIPUS_TECNOCAMPUS_2 = 12;
	const TIPUS_ESCOLESBUSSEIG = 13;
	const TIPUS_AEBESCOLES = 15;
	
	const TIPUS_COVID_30DIES = 23;
	const TIPUS_TECNOCAMPUS_TM = 24;
	
	
	// Templates plàstic
	const TEMPLATE_GENERAL = 'G0';
	const TEMPLATE_PESCA = 'F0';
	const TEMPLATE_TECNOCAMPUS_1 = 'T1';
	const TEMPLATE_TECNOCAMPUS_2 = 'T2';
	const TEMPLATE_TECNOCAMPUS_MASTER = 'TM';
	const TEMPLATE_ESCOLAR = 'ES';
	const TEMPLATE_ESCOLAR_SUBMARINISME = 'CS';
	
	//const IMATGE_ANVERS_GENERAL        = 'images/fonsgeneral_1024x664.jpg';
	//const IMATGE_ANVERS_GENERAL        = 'images/fonsgeneral_1024x664_2020.jpg';
	const IMATGE_ANVERS_GENERAL        = 'images/fonsgeneral_1024x664_2021.jpg';
	//const IMATGE_REVERS_GENERAL        = 'images/federativa_revers_1024x664.jpg';
	const IMATGE_REVERS_GENERAL        = 'images/federativa_revers_1024x664_2020.jpg';
	//const IMATGE_ANVERS_ESCOLAR        = 'images/fonsanysescolar_1024x664.jpg';
	const IMATGE_ANVERS_ESCOLAR        = 'images/fonsanysescolar_1024x664_2021.jpg';
	const IMATGE_ANVERS_TECNOCAMPUS    = 'images/fonstecnocampus_910x600.jpg';
	//const IMATGE_ANVERS_PESCA          = 'images/fonstipusf_1024x602.jpg';
	//const IMATGE_ANVERS_PESCA          = 'images/fonstipusf_1024x602_2020.jpg';
	const IMATGE_ANVERS_PESCA          = 'images/fonstipusf_1024x664_2021.jpg';
	
	const IMATGE_LOGO_FECDAS       = 'images/fecdaslogopdf.gif';
	const IMATGE_LOGO_GENE         = 'images/logo-generalitat.jpg';
	const IMATGE_LOGO_ESPORT       = 'images/esport-logo.jpg';
	const IMATGE_LOGO_FECDAS_MAIL  = 'images/fecdaslogo-mail.png';
	const IMATGE_BUTTON            = 'images/white_button.png';
	
	// Docs assegurança
	//const POLISSA_BUSSEIG = 'polissa_busseig_2019.pdf';
	const POLISSA_BUSSEIG = 'polissa_busseig_C30_0003109_%%YEAR%%.pdf';
	
	/*const POLISSA_TECNOCAMPUS = 'polissa_tecnocampus_2018-19.pdf';
	const POLISSA_ESCOLAR = 'polissa_escolar_2018-19.pdf';*/
	const POLISSA_TECNOCAMPUS = 'polissa_tecnocampus_2019-20.pdf';     // 121426
	const POLISSA_ESCOLAR = 'polissa_escolar_2019-20.pdf';     // 121425
	
	const COMUNICAT_INCIDENT_POLISSA_BUSSEIG = 'comunicat_incident_polissa_busseig.pdf';
	const COMUNICAT_INCIDENT_POLISSA_TECNOCAMPUS = 'comunicat_incident_polissa_tecnocampus.pdf';
	/*const COMUNICAT_INCIDENT_POLISSA_ESCOLAR = 'comunicat_incident_polissa_escolar.pdf';*/
	const COMUNICAT_INCIDENT_POLISSA_ESCOLAR = 'comunicat_incident_polissa_escolar_121425.pdf';
	
	

	const PROTOCOL_INCIDENTS_POLISSA_BUSSEIG = 'protocol_incidents_polissa_busseig.pdf';
	/*const PROTOCOL_INCIDENTS_POLISSA_TECNOCAMPUS = 'protocol_incidents_polissa_tecnocampus.pdf';
	const PROTOCOL_INCIDENTS_POLISSA_ESCOLAR = 'protocol_incidents_polissa_escolar.pdf';*/
	const PROTOCOL_INCIDENTS_POLISSA_TECNOCAMPUS = 'protocol_incidents_polissa_mutuacat.pdf';
	const PROTOCOL_INCIDENTS_POLISSA_ESCOLAR = 'protocol_incidents_polissa_mutuacat.pdf';

	
	// Templates plàstic
	const CARREC_PRESIDENT = 1;
	const CARREC_VICEPRESIDENT = 2;
	const CARREC_SECRETARI = 3;
	const CARREC_TRESORER = 4;
	const CARREC_VOCAL = 9;
	
	// Duplicats llicències
	//const CODI_DUPLICAT_LLICENCIA = 7090000;
	//const CODI_DUPLICAT_LLICENCIA = 7050102;
	const ID_DUPLICAT_LLICENCIA = 229; 	

	// Diario Contasol
	const INDEX_DIARI_CONTASOL = 1;
	
    const INDEX_DPT_INGRESOS_LLICENCIES    = 100;
    const INDEX_DPT_INGRESOS_KITS          = 120;
	const INDEX_DPT_INGRESOS_VARIS         = 130;
	
	const INDEX_SUBDPT_INGRESOS_LLICENCIES_LLICENCIES   = 1;
	const INDEX_SUBDPT_INGRESOS_LLICENCIES_DUPLICATS    = 2;
    const INDEX_SUBDPT_INGRESOS_LLICENCIES_INSCRIPCIONS = 3;
    const INDEX_SUBDPT_INGRESOS_LLICENCIES_CURSOS       = 4;
    const INDEX_SUBDPT_INGRESOS_LLICENCIES_ARBITRATGE   = 5;
    const INDEX_SUBDPT_INGRESOS_KITS_KITS               = 1;
    const INDEX_SUBDPT_INGRESOS_KITS_MERCHANDISING      = 2;
    const INDEX_SUBDPT_INGRESOS_VARIS_LLIBRES           = 2;
    const INDEX_SUBDPT_INGRESOS_VARIS_TRANSPORT         = 4;
	
    // Estats curs
    const CURS_ANULAT       = array('lletra' => 'A', 'text' => 'Anul·lat');
    const CURS_FINALITZAT   = array('lletra' => 'F', 'text' => 'Finalitzat');
    const CURS_VALIDAT      = array('lletra' => 'V', 'text' => 'Validat pel club. Enviat a la federació');
    const CURS_TANCAT       = array('lletra' => 'T', 'text' => 'Tancat pel director. Pendent de validar pel club');
    const CURS_EDICIO       = array('lletra' => 'E', 'text' => 'El director està omplint les dades');
    
    const CURS_ESTATS = array(0 => 'Tots', 1 => 'En tramitació', 2 => 'Pendent de validació', 3 => 'Finalitzats');
    
    const PROVINCIES_ALTRES = array("Albacete", "Alacant", "Almería", "Araba", "Álava", "Asturias", "Ávila", "Badajoz", "Illes Balears",
                                    "Bizkaia", "Burgos", "Cáceres", "Cádiz", "Cantabria", "Castelló", "Ciudad Real", "Córdoba", "A Coruña", 
                                    "Cuenca", "Gipuzkoa", "Granada", "Guadalajara", "Huelva", "Huesca", "Jaén", "León", "Lugo", "Madrid", 
                                    "Málaga", "Murcia", "Navarra", "Ourense", "Palencia", "Las Palmas", "Pontevedra", "La Rioja", "Salamanca", 
                                    "Santa Cruz de Tenerife", "Segovia", "Sevilla", "Soria", "Teruel", "Toledo", "València", "Valladolid", 
                                    "Zamora", "Zaragoza", "Ceuta", "Melilla");
    
	// Titulacions
	const TIPUS_TITOL_BUSSEIG	= 'BU';
	const TIPUS_TITOL_TECNIC	= 'TE';
	const TIPUS_ESPECIALITAT	= 'ES';
	const TIPUS_COMPETICIO		= 'CO';
	const ORGANISME_CMAS		= 'CMAS';
	
	// Títols cerca números de carnet
	const TITOL_B1EJ	        = 49;
	const TITOL_B1EO	        = 251;
	const TITOL_B1E		        = 48;
	const TITOL_B2EO	        = 252;
	const TITOL_B2ESVBO	        = 254;
	const TITOL_B2ESVB	        = 248;
	const TITOL_B2E		        = 50;
	const TITOL_B3EO	        = 253;
	const TITOL_B3EAOO	        = 255;
	const TITOL_B3EAO	        = 249;
	const TITOL_B3E		        = 51;
	const TITOL_B4E		        = 243;
	const TITOL_I1E		        = 53;
	const TITOL_I2E		        = 54;
	const TITOL_I3E		        = 55;

	
	// Rols docents
	const DOCENT_DIRECTOR		= 'Director';
	const DOCENT_CODIRECTOR		= 'Co-director';
	const DOCENT_INSTRUCTOR		= 'Instructor';
	const DOCENT_COLLABORADOR	= 'col·laborador';
	
	// Contexts dels requeriments títols 
	const CONTEXT_REQUERIMENT_GENERAL	= 'general';	 
	const CONTEXT_REQUERIMENT_ALUMNES	= 'alumnes';	 
	const CONTEXT_REQUERIMENT_DOCENTS	= 'docents';
	// Categories de requeriments títols
	const CATEGORIA_REQUERIMENT_MIN_HORES	= 'Mínim hores';	 
	const CATEGORIA_REQUERIMENT_IMMERSIONS	= 'Immersions curs';	 
	const CATEGORIA_REQUERIMENT_RATIOS		= 'Ratios';
	const CATEGORIA_REQUERIMENT_FORMACIO	= 'Formació esportiva';
	const CATEGORIA_REQUERIMENT_TITULACIONS	= 'Titulacions';
	

	// Rols usuaris aplicació
	const ROLE_ADMIN		= 'administrador';
	const ROLE_CLUB			= 'club';
	const ROLE_INSTRUCTOR	= 'instructor';
	const ROLE_FEDERAT		= 'federat';
	
	// Símbol categoría Tècnic
	const SIMBOL_TECNIC		= 'T';
	
	protected static $tipusproducte; 	// Veure getTipusDeProducte()
	protected static $tipuspagament; 	// Veure getTipusDePagament()
	protected static $tipuscomanda; 	// Veure getTipusDeComanda()
	protected static $carrecs; 			// Veure getCarrecs()
	protected static $estats; 			// Veure getEstats()
	protected static $roles; 			// Veure getRoles()
	
	
	
	public static function getTitolsCercaNumCarnets() {
	    return array(  self::TITOL_I3E, 
	                   self::TITOL_I2E, 
	                   self::TITOL_I1E, 
	                   self::TITOL_B4E, 
	                   self::TITOL_B3E, self::TITOL_B3EAO, self::TITOL_B3EAOO, self::TITOL_B3EO, 
	                   self::TITOL_B2E, self::TITOL_B2ESVB, self::TITOL_B2ESVBO, self::TITOL_B2EO,
	                   self::TITOL_B1E, self::TITOL_B1EO, self::TITOL_B1EJ);
	}
	
	
	public static function getTipusClubsNoComandes() {
	    // Symfony no deixa definir constants com Arrays TIPUS_CLUBS_NO_COMANDES   
	    return array(6, 7);
    }
	
	/**
	 * Array possibles departament/subdepartament dels productes.
	 * 		$dpt => 0 array departaments
	 *  	$dpt => 1 array sub departaments 1
	 * 		$dpt => 2 array sub departaments 2
	 * 		$dpt => (other) array buit
	 */
	public static function getDepartamentsConta( $dpt ) {
		/*
        130 Varis
            2 Libres i documents
            4 Transport 
        120 Kits i merchandising
            1 Kits
            2 Merchandising
        100 Llicències i cursos
            1 Llicències
            2 Duplicats
            3 Inscripcions
            4 Cursos
            5 Arbitratge
         */
        
		switch ($dpt) {
			case self::INDEX_DPT_INGRESOS_LLICENCIES:
				return array (
					self::INDEX_SUBDPT_INGRESOS_LLICENCIES_LLICENCIES      => '1 - Llicències',
					self::INDEX_SUBDPT_INGRESOS_LLICENCIES_DUPLICATS       => '2 - Duplicats',
					self::INDEX_SUBDPT_INGRESOS_LLICENCIES_INSCRIPCIONS	   => '3 - Inscripcions',
					self::INDEX_SUBDPT_INGRESOS_LLICENCIES_CURSOS          => '4 - Cursos',
					self::INDEX_SUBDPT_INGRESOS_LLICENCIES_ARBITRATGE      => '5 - Arbitratge'
				);
				break;
			case self::INDEX_DPT_INGRESOS_KITS:
				return array (
					self::INDEX_SUBDPT_INGRESOS_KITS_KITS          => '1 - Kits',
					self::INDEX_SUBDPT_INGRESOS_KITS_MERCHANDISING => '2 - Merchandising'
				);
				break;
            case self::INDEX_DPT_INGRESOS_VARIS:
                return array (
                    self::INDEX_SUBDPT_INGRESOS_VARIS_LLIBRES   => '2 - Llibres i documents',
                    self::INDEX_SUBDPT_INGRESOS_VARIS_TRANSPORT => '4 - Transport'
                );
                break;    
			case 0:	// dpt 0
				return array (
					self::INDEX_DPT_INGRESOS_LLICENCIES => '100 - Llicències i cursos',
					self::INDEX_DPT_INGRESOS_KITS 	    => '120 - Kits i merchandising',
					self::INDEX_DPT_INGRESOS_VARIS      => '130 - Varis'
				);
		}

		return array();
	}
	
	/**
	 * Array possibles tipus de producte
	 */
	public static function getTipusDeProducte() {
		if (self::$tipusproducte == null) {
			self::$tipusproducte = array(
					self::TIPUS_PRODUCTE_LLICENCIES => 'Llicències',
					self::TIPUS_PRODUCTE_DUPLICATS 	=> 'Duplicats',
					self::TIPUS_PRODUCTE_KITS 		=> 'Kits i material Online',
					self::TIPUS_PRODUCTE_MERCHA 	=> 'Merchandising',
					self::TIPUS_PRODUCTE_CURSOS 	=> 'Cursos',
			        self::TIPUS_PRODUCTE_MATERIAL 	=> 'Material',
					self::TIPUS_PRODUCTE_ALTRES 	=> 'Altres'
			);
		}
		return self::$tipusproducte;
	}
	
	/**
	 * Obté tipus de producte
	 */
	public static function getTipusProducte($index) {
		$tipus = BaseController::getTipusDeProducte();
		if (isset($tipus[$index])) return $tipus[$index];
	
		return '';
	}
	
	/**
	 * Array possibles càrrecs junta
	 */
	public static function getCarrecs() {
		if (self::$carrecs == null) {
			self::$carrecs = array(
					self::CARREC_PRESIDENT 		=> 'President',
					self::CARREC_VICEPRESIDENT 	=> 'Vicepresident',
					self::CARREC_SECRETARI 		=> 'Secretari',
					self::CARREC_TRESORER  		=> 'Tresorer',
					self::CARREC_VOCAL  		=> 'Vocal',
			);
		}
		return self::$carrecs;
	}
	
	/**
	 * Obté compte enviament Comptabilitat segons el tipus de pagament
	 */
	public static function getCarrec($index) {
		$carrecs = BaseController::getCarrecs();
		if (isset($carrecs[$index])) return $carrecs[$index];	
		return 'Desconegut';
	}
	
	/**
	 * Array possibles tipus de pagament
	 */
	public static function getTipusDePagament() {
		if (self::$tipuspagament == null) {
			self::$tipuspagament = array(
					self::TIPUS_PAGAMENT_TRANS_LAIETANIA 	=> 'Transferència. La Caixa',
					self::TIPUS_PAGAMENT_TRANS_ESCOLA 		=> 'Transferència compte Escola',
					self::TIPUS_PAGAMENT_CASH 				=> 'Metàl·lic',
					self::TIPUS_PAGAMENT_TPV 			 	=> 'On-Line TPV',
					//self::TIPUS_PAGAMENT_TRANS_SARDENYA  => 'Transferència Sardenya',
			);
		}
		return self::$tipuspagament;
	}
	
	
	/**
	 * Array possibles tipus de pagament
	 */
	public static function getTextTipusPagament($tipus) {
		$tipuspagament = BaseController::getTipusDePagament();
		
		return isset($tipuspagament[$tipus])?$tipuspagament[$tipus]:'';
	}
	
	/**
	 * Obté compte enviament Comptabilitat segons el tipus de pagament
	 */
	public static function getComptePagament($tipus) {
		if ($tipus == self::TIPUS_PAGAMENT_CASH) return  self::CODI_PAGAMENT_CASH;
		if ($tipus == self::TIPUS_PAGAMENT_TRANS_ESCOLA) return self::CODI_PAGAMENT_ESCOLA; 
		return  self::CODI_PAGAMENT_CAIXA;
	}
	
	/**
	 * Obté compte enviament Comptabilitat segons el tipus de pagament
	 */
	public static function getTextComptePagament($tipus) {
			
		if ($tipus == self::TEXT_PAGAMENT_TPV) return  self::TEXT_PAGAMENT_TPV;
		
		if ($tipus == self::TIPUS_PAGAMENT_TRANS_LAIETANIA) return  self::TEXT_PAGAMENT_TRANS;
		
		if ($tipus == self::TIPUS_PAGAMENT_TRANS_ESCOLA) return  self::TEXT_PAGAMENT_TRANS_ESCOLA;
		
		return  self::TEXT_PAGAMENT_CASH;
	}
	
	
	/**
	 * Obté productes que van per defecte al compte escola
	 */
	public static function esProducteEscola($idProducte) {
			
		if ($idProducte == self::PRODUCTE_262_CARNET_CMAS) return true;	
	}
	
	
	
	/**
	 * Array possibles tipus de comanda
	 */
	public static function getTipusDeComanda() {
		if (self::$tipuscomanda == null) {
			self::$tipuscomanda = array(
					self::TIPUS_COMANDA_LLICENCIES => 'Llicències',
					self::TIPUS_COMANDA_DUPLICATS => 'Duplicats',
					self::TIPUS_COMANDA_ALTRES => 'Altres'
			);
		}
		return self::$tipuscomanda;
	}
	
	/**
	 * Obté tipus de comanda
	 */
	public static function getTipusComanda($index) {
		$tipus = BaseController::getTipusDeComanda();
		if (isset($tipus[$index])) return $tipus[$index];
	
		return '';
	}
	
	
	/**
	 * Array possibles rols d'usuari
	 */
	public static function getRoles( $admin = false ) {
		/*if (self::$roles == null) {*/
			self::$roles = array( 
				self::ROLE_CLUB 		=> mb_strtoupper(self::ROLE_CLUB),
				self::ROLE_FEDERAT		=> mb_strtoupper(self::ROLE_FEDERAT),					
				self::ROLE_INSTRUCTOR	=> mb_strtoupper(self::ROLE_INSTRUCTOR),
			);
			
			//if ($admin) array_unshift(self::$roles, self::ROLE_ADMIN); 	// Afegir el primer
			if ($admin) self::$roles[self::ROLE_ADMIN] = mb_strtoupper(self::ROLE_ADMIN); 	// Afegir el primer
			
			ksort(self::$roles);
		/*}*/
		return self::$roles;
	}
	
	/**
	 * Array header export llicències
	 */
	public static function getHeaderLlicencies() {
		return array ('comanda', 'club', 'llicència', 'dataalta', 'datacaducitat', 'databaixa', 'categoria', 'preu',  					  	
					 'dni', 'estranger', 'nom', 'cognoms', 'naixement', 'edat',	'sexe', 'telefon1', 'telefon2', 'mail',	
					 'adreca', 'poblacio', 'cp', 'provincia', 'comarca', 'nacionalitat');
	}
	
	/**
	 * Obté array anys preus
	 */
	public static function getArrayAnysPreus($inici = self::ANY_INICI_WEB, $final = 0) {
		if ($final == 0) $final = date('Y') + 1;
		
		$anyspreus = array();
		for ($a = $inici; $a <= $final; $a++) $anyspreus[$a] = $a;
	
		return $anyspreus;
	}
	
	/**
	 * Obté array IVA
	 */
	public static function getIVApercents() {
		$ivaArray = array('0' => 'Exempt', '0.04' => '4%', '0.08' => '8%', '0.21' => '21%' );
		return $ivaArray;
	}
	
	public static function getLlistaTipusParte($club, $dataconsulta, $admin = false) { 
		$llistatipus = array();
	
		$day = $dataconsulta->format('d');
		$month = $dataconsulta->format('m');
		$any = $dataconsulta->format('Y');
		 
		$currentmonthday = sprintf("%02d", $month) . "-" . sprintf("%02d", $day);
	
		/* Llista tipus parte administrador en funció del club seleccionat. Llista d'un club segons club de l'usuari */
		if ($club == null) return $llistatipus;  // Sense info del club!!?

		$tipuspartes = $club->getTipusparte();
		foreach ($tipuspartes as $tipusparte) {
            if ($tipusparte->getActiu() == true &&
				$tipusparte->validarPreusAny($any) == true) {
				    
				if ($tipusparte->getAdmin()) {
				    if ($admin) array_push($llistatipus, $tipusparte->getId());
				} else {
    				if ($tipusparte->getEs365() == true) {
    					/* 365 directament sempre. Es poden usar en qualsevol moment  */
    					array_push($llistatipus, $tipusparte->getId());
    				} else {
    					$inici = '01-01';
    					$final = '12-31';
    					if ($tipusparte->getInici() != null) $inici = $tipusparte->getInici();
    					if ($tipusparte->getFinal() != null) $final = $tipusparte->getFinal();

    					if ($currentmonthday >= $inici && $currentmonthday <= $final) {
    						array_push($llistatipus, $tipusparte->getId());
    					}
    				}
				}
				
			}
		}
	
		return $llistatipus;
	}
	
	/**
	 * Obté IBAN la caixa 
	 */
	public function getIbanGeneral() {
		return $this->getParameter('iban');
	}
	
	/**
	 * Obté IBAN afers escola
	 */
	public function getIbanEscola() {
		return $this->getParameter('ibanescola');
	}
	
	/**
	 * Check IBAN i pagament 
	 */
	public function checkIbanTipusPagament($tipuspagament, $iban) {
		if ($tipuspagament == self::TIPUS_PAGAMENT_TRANS_LAIETANIA && $iban == $this->getIbanGeneral()) return true;
		if ($tipuspagament == self::TIPUS_PAGAMENT_TRANS_ESCOLA && $iban == $this->getIbanEscola()) return true;
		return false;
	}
	
    protected function getComercRedsysParam( $param ) {
        //if (!$this->hasParameter( $param )) return '';
        return $this->getParameter( $param, '' );
    }
	
	protected function getCommonRenderArrayOptions($more = array()) {
		$options = array( 	'currentuser' => null, 'role' => '', 'authenticated' => false, 'admin' => false, 
							'roleclub' => false, 'roleinstructor' => false, 'rolefederat' => false, 'licpendents' => false,
							'userclub' => '', 'currentrolenom' => '',
							'allowcomandes' => false, 'busseig' => false, 
							'enquestausuari' => '', 'enquestausuaripendent' => '',
							'cartItems'		=> 0 );
		
		if ($this->isAuthenticated()) {
			$checkRole = $this->get('fecdas.rolechecker');
			
			$options['currentuser'] = $checkRole->getCurrentUser();
			$options['role'] = $checkRole->getCurrentRole();
			$options['admin'] = $checkRole->isCurrentAdmin();
			$options['roleclub'] = $checkRole->isCurrentClub();
			$options['roleinstructor'] = $checkRole->isCurrentInstructor();
			$options['rolefederat'] = $checkRole->isCurrentFederat();
			
			$options['authenticated'] = $checkRole->isAuthenticated();
			$options['allowcomandes'] = $this->allowComandes();
			$options['busseig'] = $this->isCurrentBusseig();
			$options['enquestausuari'] = $checkRole->getCurrentEnquestaActiva();
			$options['enquestausuaripendent'] = $checkRole->getCurrentEnquestaPendent();

			$formBuilder = $this->createFormBuilder();
			if ($this->isCurrentAdmin()) {
				$this->addClubsActiusForm($formBuilder, $this->getCurrentClub(), 'currentclub');
				
				$formBuilder->add('currentrole', 'choice', array(			
					'choices'  => self::getRoles(true),				// Llista de rols sense clubs
					'data'     => $checkRole->getCurrentRole()		// Rol actual de l'usuari
				));
				
			} else {
				$formBuilder->add('currentrole', 'choice', array(			
					'choices'  => $checkRole->getUserRolesArray(),	// Llista de parelles rol - club
					'data'     => $checkRole->getUserRoleKey(),     // Rol actual de l'usuari
				    'attr'     => array('readonly' => count($checkRole->getUserRolesArray()) <= 1)
				));
				
				$userclub = $this->getCurrentClub();
				if ($userclub) $options['userclub'] = $userclub->getNom(); 	
			}
			
			$options['roleform'] = $formBuilder->getForm()->createView();
			if ($checkRole->isCurrentFederat() || $checkRole->isCurrentInstructor()) {
			    $user = $this->getCurrentUser();
			    $options['currentrolenom'] = $user!=null&&$user->getMetapersona()!=null?$user->getMetapersona()->getNomCognoms():'';
	
			    $this->frontEndMissatgesUsuari($options, $user);
			} else {
			    $options['currentrolenom'] = $this->getCurrentClub()!=null?$this->getCurrentClub()->getNom():'';
			}
			
			$cartcheckout = $this->get('fecdas.cartcheckout');
			
			$cart = $cartcheckout->getSessionCart();

			$options['cartItems'] = count( $cart['productes'] );
			
		}
		
		return  array_merge($more, $options);
	}
	
	protected function frontEndMissatgesUsuari(&$options, $user = null) {
	    // Comprovar si té cap llicència pendent de pagament
	    if ($user == null) return;
	    
	    $query = $this->consultaPartesUsuari($user, true);
	    
	    $partesPendentsPagament = $query->getResult();
	    foreach ($partesPendentsPagament as $parte) {
	        if ($parte->isActual() && $parte->comandaUsuari() && !$parte->comandaPagada()) {
	            $options['licpendents'] = true;
	            $this->get('session')->getFlashBag()->add('user-notice',"Cal finalitzar la tramitació d'una llicència amb data ".$parte->getDataalta()->format('d/m/Y').", tingueu en compte que no serà vàlida fins que se'n confirmi el pagament");
	        }
	    }
	}
	
	protected function addClubsActiusForm($formBuilder, $club, $nom = 'clubs', $clear = true) {
			
	    $opcions = array(
	        'class' 		=> 'FecdasBundle:EntityClub',
	        'query_builder' => function($repository) {
    	        return $repository->createQueryBuilder('c')
    	        ->orderBy('c.nom', 'ASC')
    	        ->where('c.databaixa IS NULL');
    	        //->join('c.tipus', 't', 'WITH', 't.id != \''.BaseController::TIPUS_CLUB_PERSONA_FISICA.'\'');
    	        //->where('c.activat = 1');
	        },
	        'choice_label' 	=> 'nom',
	        'required'  	=> false,
	        'data' 			=> $club,
	    );

	    if ($clear || $club == null) $opcions['placeholder'] = '';	// Important deixar en blanc pel bon comportament del select2
	    
	    $formBuilder->add($nom, 'entity', $opcions);
	    
	}

	protected function addTitolsFilterForm($formBuilder, $titol, $cmas = true, $nom = 'titols') {
			
		$formBuilder->add($nom, 'entity', array(
				'class' 		=> 'FecdasBundle:EntityTitol',
				'query_builder' => function($repository) use ($cmas) {
						if ($cmas) {
							return $repository->createQueryBuilder('t')
									->orderBy('t.titol', 'ASC')
									->where('t.organisme = \''.BaseController::ORGANISME_CMAS.'\'')
									->where('t.curs = 1');
						}
						return $repository->createQueryBuilder('t')
								->orderBy('t.titol', 'ASC')
								->where('t.organisme <>  \''.BaseController::ORGANISME_CMAS.'\'');
						}, 
				'choice_label' 	=> 'LlistaText',
				'placeholder' 	=> '',	// Important deixar en blanc pel bon comportament del select2
				'required'  	=> false,
				'data' 			=> $titol,
		));
		
	}
	
	protected function getOrganismesTitols() {
		$em = $this->getDoctrine()->getManager();
		
		$query = $em->createQuery('SELECT DISTINCT t.organisme FROM FecdasBundle\Entity\EntityTitol t ORDER BY t.organisme');
		
		return $query->getResult(); 
	}
	
	protected function getTitolsByOrganisme($organisme = '', $notorganisme = '') {
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = 'SELECT t FROM FecdasBundle\Entity\EntityTitol t WHERE 1=1 ';
		if ($organisme != '') 		$strQuery .= ' AND t.organisme = :organisme ';
		if ($notorganisme != '') 	$strQuery .= ' AND t.organisme <> :notorganisme ';
		$strQuery .= 'ORDER BY t.organisme, t.codi';
		
		$query = $em->createQuery($strQuery);
		if ($organisme != '') $query->setParameter('organisme', $organisme);
		if ($notorganisme != '') $query->setParameter('notorganisme', $notorganisme);
	
		$titols = array();	
		foreach ($query->getResult() as $titol) {
			$org = $titol->getOrganisme();
			
			if ( !isset($titols[$org]) ) $titols[ $org ] = array();			
			
			$titols[$org][] = array( 'codi' => $titol->getCodi(), 'titol' => $titol->getTitol() );
		}
		
		return $titols;
	}
	
	protected function getCurrentDate($time = null) {
		//function to fake date, testing purpouse
		$currentdate = is_null($time) ? new \DateTime() : new \DateTime($time); 
		
		return $currentdate;
	}
	
	public static function getIntervalConsolidacio() {
		global $kernel;

		if ('AppCache' == get_class($kernel)) $kernel = $kernel->getKernel();
		// Interval manté el parte editable. A desenvolupament només 2 minuts
		//if ($this->get('kernel')->getEnvironment() == 'dev')  return new \DateInterval('PT120S'); // 2 minuts
		if ($kernel->getEnvironment() == 'dev')  return new \DateInterval('PT120S'); // 2 minuts
		
		return new \DateInterval('PT1200S'); // 20 minuts 
	}
	
	protected function frontEndLoginCheck($isXmlHttpRequest = false, $accessFederats = false, $admin = false) {
	    // No autenticat reenvia a login
	    if (!$this->isAuthenticated()) {
	        $this->logEntryAuth('LOGIN CHECK', "Error no authenticated");
	        
	        return $this->frontEndLoginCheckReturn($isXmlHttpRequest, 'error-notice', 'FecdasBundle_login', "Cal indicar les credencials per accedir a l\'Aplicació");
	    }
	    
	    $checkRole = $this->get('fecdas.rolechecker');
	    
	    $user = $checkRole->getCurrentUser();

	    if ($user == null) {
	        $this->logEntryAuth('LOGIN CHECK', "User null");
	        
	        return $this->frontEndLoginCheckReturn($isXmlHttpRequest, 'error-notice', 'FecdasBundle_login', "No s'ha trobat l'usuari, poseu-vos en contacte amb la Federació");
	    }

	    /* Administradors */
	    if ($admin && !$this->isCurrentAdmin()) {
	        $this->logEntryAuth('LOGIN CHECK', "Error no admin ".$user->getUser());
	        
	        return $this->frontEndLoginCheckReturn($isXmlHttpRequest, 'error-notice', 'FecdasBundle_home', "Acció no permesa. L\'esdeveniment a quedat registrat");
	    }
	    
	    // Comprova usuari pendent d'indicar dades personals
	    if ($checkRole->isCurrentFederat() && $user->isPendentDadesPersonals()) {
	        return $this->frontEndLoginCheckReturn($isXmlHttpRequest, 'sms-notice', 'FecdasBundle_dadespersonals', "Cal omplir les dades personals");
	    }
	    
	    if ($accessFederats) {
	        if ($this->isCurrentAdmin()) {
	            $this->logEntryAuth('ACCESS NO CLUB', "Error accés Admin");
	            
	            return $this->frontEndLoginCheckReturn($isXmlHttpRequest, 'error-notice', 'FecdasBundle_homepage', "Només es pot accedir a aquesta funcionalitat amb rol Federat");
	        }
	        
	    
	        if (!$checkRole->isCurrentFederat() && !$checkRole->isCurrentInstructor()) {
	            
	            $this->logEntryAuth('ACCESS NO CLUB', "Error accés club ".$user->getUser());
	                
	            return $this->frontEndLoginCheckReturn($isXmlHttpRequest, 'error-notice', 'FecdasBundle_homepage', "L\'usuari no disposa de permisos per realitzar aquesta acció, poseu-vos en contacte amb la Federació");
	        }

	        if ($user->getMetapersona() == null) {
	            $this->logEntryAuth('ACCESS NO CLUB', "Error usuari sense metapersona ".$user->getUser());
	            
	            return $this->frontEndLoginCheckReturn($isXmlHttpRequest, 'error-notice', 'FecdasBundle_homepage', $user->getUser().": "."No s'han trobat les dades personals de l'usuari, poseu-vos en contacte amb la Federació");
	        }
	            
	        if ($user->getMetapersona()->getUltimesDadesPersonals() == null) {
	            $this->logEntryAuth('ACCESS NO CLUB', "Error metapersona sense dades personals ".$user->getUser()." ".$user->getMetapersona()->getId());
	            
	            return $this->frontEndLoginCheckReturn($isXmlHttpRequest, 'error-notice', 'FecdasBundle_homepage', $user->getUser().": "."No s'han trobat les dades personals corresponents a l'usuari, poseu-vos en contacte amb la Federació");
	        }
	    }
	    
	    // Continua
	    return null;
	}
	
	private function frontEndLoginCheckReturn($isXmlHttpRequest = false, $notice = 'error-notice', $url = 'FecdasBundle_homepage', $message = '') {
	
	    if ($isXmlHttpRequest) {
	       $response = new Response($message);
	       $response->setStatusCode(500);
	       return $response;
	    }
	
	    $this->get('session')->getFlashBag()->clear();
	    $this->get('session')->getFlashBag()->add($notice, $message);
	
    	return $this->redirect($this->generateUrl($url));
	
	}

	protected function isAuthenticated() {
		
		$checkRole = $this->get('fecdas.rolechecker');
		
		return $checkRole->isAuthenticated();
	}
	
	protected function isCurrentAdmin() {
			
		$checkRole = $this->get('fecdas.rolechecker');
		
		return $checkRole->isCurrentAdmin();
	}
	
	protected function isCurrentClub() {
	    
	    $checkRole = $this->get('fecdas.rolechecker');
	    
	    return $checkRole->isCurrentClub();
	}
	
	protected function isCurrentInstructor() {
	    
	    $checkRole = $this->get('fecdas.rolechecker');
	    
	    return $checkRole->isCurrentInstructor();
	}

	protected function isCurrentFederat() {
	    
	    $checkRole = $this->get('fecdas.rolechecker');
	    
	    return $checkRole->isCurrentFederat();
	}
	
	protected function getCurrentClub() {
		$checkRole = $this->get('fecdas.rolechecker');
		
		return $checkRole->getCurrentClub();
	}

	protected function getCurrentUser() {
	    $checkRole = $this->get('fecdas.rolechecker');
	    
	    return $checkRole->getCurrentUser();
	}
	
	protected function allowComandes() {
		if ($this->isAuthenticated() != true) return false;
		
		$club = $this->getCurrentClub();
		
		if ($club == null) return false;
		
		if ($club->getCodi() == self::CODI_CLUBINDEFEDE ||
		    $club->getCodi() == self::CODI_CLUBINDEPENDENT || 
		    $club->getCodi() == self::CODI_CLUBLLICWEB) return true;
		
		if (in_array( $club->getTipus()->getId(), self::getTipusClubsNoComandes() )) return false;
		
		return true;		
	}
	
	protected function validateMails($mails = array()) {
	    for ($i = 0; $i < count($mails); $i++) {
	        $mails[$i] = trim($mails[$i]);
	        
	        $mail = $mails[$i];
	        
	        $posArroba = strpos($mail, '@');
	        $posGuio = strpos($mail, '-');
	        
	        
	        if ($posArroba !== false && $posGuio !== false && $posGuio < $posArroba) $mail = str_replace('-', '', $mail); // Reemplazar "-" abans de @ perquè surten invàlids
	        
	        if ($mail != "" && filter_var($mail, FILTER_VALIDATE_EMAIL) === false) throw new \Exception("L'adreça de correu -".$mail."- no és vàlida");
	    }
	    return implode(";", $mails);
	}
	
	protected function validateMailsContainsUser($mails = array(), $username) {
	    foreach ($mails as $mail) {
	        if ($mail == $username) return true;
	    }
	    
	    return false;
	}
	
	protected function isCurrentBusseig() {
		$club = $this->getCurrentClub();
		if ($this->isCurrentAdmin()) return true;
		if ($club == null) return false;
		if ($club->getTipus()->getId() >= 0 && $club->getTipus()->getId() <= 7) return true; 
		return false;
	}
	
	protected function getFormOptions() {
		return array('edit' => false, 'admin' => false, 'nova' => false,
				'codiclub' => '', 'tipusparte' => 1, 'llistatipus' => array(), 'any' => Date('Y'));
	}
	
	protected function getSQLIniciAnual() {
		/* Normal 31/12  	dataalta >= 01/01/current year */
		$inianual = \DateTime::createFromFormat('Y-m-d H:i:s', date("Y") . "-01-01 00:00:00");
		$inianual = $inianual->format('Y-m-d H:i:s');
		return $inianual;
	}
	
	protected function getSQLInici365() {
		/* 365	dataalta >= avui / (current year - 1) */
		$ini365 = \DateTime::createFromFormat('Y-m-d H:i:s', (date("Y") - 2) . "-" . date("m") . "-" . date("d") . "  00:00:00");
		$ini365 = $ini365->format('Y-m-d H:i:s');
		return $ini365;
	}
	
	protected function validaParteLlicencia($parte, $llicencia) {
		
		$dataalta = $parte->getDataalta();
		$tipus = $parte->getTipus();
		$errData = $this->validaDataLlicencia($dataalta, $tipus);
			
		if ($errData != "") throw new \Exception($errData);
		// NO llicències amb data passada. Excepte administradors
		// Data alta molt aprop de la caducitat.

		if (!$this->validaTramitacioAnySeguent($dataalta)) throw new \Exception('Encara no es poden tramitar llicències per a l\'any vinent');
		
        /* Validar persones noves (alta > 2016-09-01 si tenen mail informat 
         * Llicències diferents de la diària
         */
		if (!$tipus->esLlicenciaDia()) {
            $persona = $llicencia->getPersona();
            if ($persona->getDataentrada()->format('Y-m-d') > self::INICI_VALIDACIO_MAIL) {
                if ($persona->getMail() == null || $persona->getMail() == "") 
                    throw new \Exception("El federat ".$persona->getNomCognoms()." no té indicada adreça de correu electrònica");
            }     
        }
        
        if ($tipus->getLimittramit()) {
            // Tipus llicències limitades a un cop a la vida (per DNI a qualsevol club) per a qualsevol tipus limitat
            $metapersona = $llicencia->getPersona()->getMetapersona();

            foreach ($metapersona->getLlicencies() as $lic) {
             
                if (!$lic->isBaixa() && $lic->getParte()->getTipus()->getLimittramit()) 
                    throw new \Exception('Aquest tipus de llicència no es pot tramitar novament per a '.
                        $metapersona->getDni().' ('.$llicencia->getPersona()->getNomCognoms().'). '.
                        ' Ja es va tramitar amb anterioritat per al periode  '.$lic->getParte()->getDataalta()->format('d/m/Y').
                        ' fins '.$lic->getDatacaducitat()->format('d/m/Y'));
                                
            }
        }
        
        
		/* Modificacio 10/10/2014. Missatge no es poden tramitar 365 */
		/* id 4 - Competició --> és la única que es pot fer */
		/* id 9 i 12 - Tecnocampus també es pot fer */
		/*if ($parte->getTipus()->getEs365() == true && $parte->getTipus()->getId() != 4
			&& $parte->getTipus()->getId() != 9 && $parte->getTipus()->getId() != 12) {					
			
			$this->get('session')->getFlashBag()->clear();
			$this->get('session')->getFlashBag()->add('sms-notice','El procés de contractació d’aquesta modalitat d’assegurances està suspès temporalment.
				Si us plau, contacteu amb la FECDAS –93 356 05 43– per dur a terme la contractació de la llicència.
				Gràcies per la vostra comprensió.');
			$valida = false;
		}*/
		/* Fi modificacio 10/10/2014. Missatge no es poden tramitar 365 */
		/* Valida tipus actiu --> és la única que es pot fer */
		if (!$tipus->getActiu()) throw new \Exception('Aquest tipus de llicència no es pot tramitar. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
		/* Fi modificacio 12/12/2014. Missatge no es poden tramitar */

		// Comprovar data llicències reduïdes. Alta posterior 01/09 any actual
		$datainici_reduida = new \DateTime(date("Y-m-d", strtotime($dataalta->format('Y') . "-".self::INICI_TRAMITACIO_QUATRIMESTRE_MES."-".self::INICI_TRAMITACIO_QUATRIMESTRE_DIA."")));
		if (($tipus->getId() == 5 or $tipus->getId() == 6) &&
			($dataalta->format('Y-m-d') < $datainici_reduida->format('Y-m-d'))) { // reduïdes
			throw new \Exception('Les llicències reduïdes només a partir de 1 de setembre');
		}
			
		if ($this->validaLlicenciaInfantil($parte, $llicencia) == false) throw new \Exception('L\'edat de la persona ('.$llicencia->getPersona()->getDni().') no correspon amb el tipus de llicència');
						
		if ($this->validaPersonaRepetida($parte, $llicencia) == false) throw new \Exception('Aquesta persona ('.$llicencia->getPersona()->getDni().') ja té una llicència en aquesta llista');

		// Comprovar que no hi ha llicències vigents 
		// Per la pròpia persona
		$parteoverlap = $this->validaPersonaTeLlicenciaVigent($llicencia, $llicencia->getPersona()); 
		if ($parteoverlap != null) throw new \Exception($llicencia->getPersona()->getNomCognoms(). ' - Té una llicència per a l\'any actual, en data ' . 
															$parteoverlap->getDataalta()->format('d/m/Y'));

	}
 
	protected function validaTramitacioAnySeguent(\DateTime $dataalta) {
	    $current = $this->getCurrentDate();
	    
	    if ($dataalta->format('Y') > $current->format('Y')) {
	        // Només a partir 10/12 poden fer llicències any següent
	        if ($current->format('m') < self::INICI_TRAMITACIO_ANUAL_MES ||
	           ($current->format('m') == self::INICI_TRAMITACIO_ANUAL_MES &&
	            $current->format('d') < self::INICI_TRAMITACIO_ANUAL_DIA)) {
	             return false;
	        }
	    }
	    return true;
	}
	
	protected function validaDataLlicencia(\DateTime $dataalta, $tipus) {
		$avui = $this->getCurrentDate('now');
		if (!$this->isCurrentAdmin() && $dataalta < $avui) return 'No es poden donar d\'alta ni actualitzar llicències amb data passada';
		
		/* ALEX. Validació eliminada 13/07/2016
		  if ($tipus->getEs365() == true and $tipus->getFinal() != null) {
			// Llicències anuals per curs. Si dataalta + 2 mesos > datafinal del tipus vol dir que la intenten donar d'alta quasi quan caduca per error
			$dataClone = clone $dataalta;
			$dataClone->add(new \DateInterval('P2M')); // Add 2 Months
			if ($dataalta->format('m-d') <= $tipus->getFinal() and $dataClone->format('m-d') > $tipus->getFinal()) {
				return 'L\'inici de les llicències està molt proper a la caducitat';
			}
		}*/
		
		return ''; 
	} 
	
	
	protected function validaLlicenciaInfantil(EntityParte $parte, EntityLlicencia $llicencia) {
		// Valida menors, nascuts després del 01-01 any actual - 12
	    if ($parte->getTipus()->esLlicenciaDia()) return true; // Llicències Dia no aplica

		$nascut = $llicencia->getPersona()->getDatanaixement();
	
		/*$nascut = new \DateTime(date("Y-m-d", strtotime($llicencia->getPersona()->getDatanaixement()->format('Y-m-d'))));
		 echo $nascut->format("Y-m-d");*/
		$limit = \DateTime::createFromFormat('Y-m-d', ($parte->getAny()-12) . "-01-01");
		if ($llicencia->getCategoria()->getSimbol() == "I" && $nascut < $limit) return false;
		if ($llicencia->getCategoria()->getSimbol() != "I" && $nascut > $limit) return false;
		return true;
	}
	
	protected function validaPersonaRepetida(EntityParte $parte, EntityLlicencia $llicencia) {
		// Parte ja té llicència aquesta persona
		foreach ($parte->getLlicencies() as $llicencia_iter) {
			//if ($llicencia_iter->getId() != $llicencia->getId() &&
			if ($llicencia_iter !== $llicencia &&
				$llicencia_iter->getDatabaixa() == null) {
				// NO valido la pròpia llicència, en cas d'update
				if ($llicencia_iter->getPersona()->getId()  != 0 && 
					$llicencia->getPersona()->getId() != 0 &&
					$llicencia_iter->getPersona()->getId() == $llicencia->getPersona()->getId()) return false;
				if ($llicencia_iter->getPersona()->getDni() == $llicencia->getPersona()->getDni()) return false;
			}
		}
		return true;
	}
	
	protected function validaDNIRepetit(EntityParte $parte, EntityLlicencia $llicencia) {
		// Parte ja té aquest dni. Comprovar abans d'afegir la llicència
		foreach ($parte->getLlicencies() as $llicencia_iter) {
			if ($llicencia_iter->getDatabaixa() == null) {
				if ($llicencia_iter->getPersona()->getDni() == $llicencia->getPersona()->getDni()) return false;
			}
		}
		return true;
	}
	
	protected function validaPersonaTeLlicenciaVigent(EntityLlicencia $llicencia, EntityPersona $persona) {
		// Comprovar que no hi ha altres llicències vigents per a la persona
		// Que solapin amb la llicència
		$em = $this->getDoctrine()->getManager();
	
		// Consulta actives i futures de la persona
		// Pot ser que es coli alguna llicència un dia any actual anterior data d'avui
		$strQuery = "SELECT l FROM FecdasBundle\Entity\EntityLlicencia l ";
		$strQuery .= " JOIN l.parte p JOIN p.tipus t";
		$strQuery .= " WHERE l.persona = :persona ";
		$strQuery .= " AND p.databaixa IS NULL ";
		$strQuery .= " AND ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";
					
		$query = $em->createQuery($strQuery)
			->setParameter('persona', $persona->getId())
			->setParameter('ininormal', $this->getSQLIniciAnual())  // 01/01 de l'any actual
			->setParameter('ini365', $this->getSQLInici365());		// Avui fa un any
	
		$lpersonaarevisar = $query->getResult();
	
		$inicivigencia_nova = $llicencia->getParte()->getDataalta();
		$fivigencia_nova = $llicencia->getParte()->getDataCaducitat();
	
		foreach ($lpersonaarevisar as $llicencia_iter) {
			if ($llicencia_iter->getId() != $llicencia->getId() and
				$llicencia_iter->getDatabaixa() == null ) {
				// No comprovo la pròpia llicència
	
				$inicivigencia_existent = $llicencia_iter->getParte()->getDataalta();
	
				// Cal anar en compte, les llicències importades tenen un dia més
				//$fivigencia_existent = $llicencia_iter->getDatacaducitat();
				$fivigencia_existent = $llicencia_iter->getParte()->getDataCaducitat();
	
				// Comprovar si sol·lapen
				if (($fivigencia_nova >= $inicivigencia_existent) &&
					($inicivigencia_nova <= $fivigencia_existent)) {
					return $llicencia_iter->getParte(); // Error, sol·lapen
				}
			}
		}
		return null;
	}
	
	protected function consultaPartesRecents($club, $estat, $baixa, $nopagat, $noimpres, $noenviat, $compta, 
											$numfactura, $anyfactura, $numrebut, $anyrebut, 
	                                        $dni, $nom, $mail, $strOrderBY = '') {
		$em = $this->getDoctrine()->getManager();
	
		$anulaIds = array();
		
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p JOIN p.llicencies l JOIN l.persona o JOIN o.metapersona m ";
		$strQuery .= " JOIN p.tipus t JOIN p.clubparte c JOIN c.estat e ";
		$strQuery .= " LEFT JOIN p.rebut r LEFT JOIN p.factura f WHERE ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365)) AND ( 1 = 1 ";

		
		if ($numrebut == '' && $numfactura == '') {
			// Dates normals
			$inianual = $this->getSQLIniciAnual();
			$ini365 = $this->getSQLInici365();
		} else {
			// dates dels anys escollits a les factures / rebuts
			$inianual = min($anyrebut, $anyfactura).'-01-01 00:00:00';
			$ini365 = $inianual;
		
			if ($numrebut != '') $strQuery .= " AND r.num = :numrebut ";
			if ($numfactura != '') $strQuery .= " AND f.num = :numfactura ";
		}
		
		if ($dni != '') $strQuery .= " AND m.dni LIKE '%" .$dni . "%' "	;
		if ($nom != '') $strQuery .= " AND (CONCAT(o.nom, ' ', o.cognoms) LIKE '%" .$nom . "%') "	;
		if ($mail != '') $strQuery .= " AND o.mail LIKE '%" .$mail . "%' "	;
		
		if ($club != null) $strQuery .= " AND p.clubparte = '" .$club->getCodi() . "' "	;
		if ($estat != self::TOTS_CLUBS_DEFAULT_STATE) $strQuery .= " AND e.descripcio = :filtreestat ";
		
		
		if ($baixa == false) $strQuery .= " AND p.databaixa IS NULL AND l.databaixa IS NULL ";
		if ($nopagat == true) $strQuery .= " AND p.rebut IS NULL ";
		//if ($noimpres == true) $strQuery .= " AND (p.impres IS NULL OR p.impres = 0) AND p.pendent = 0 AND t.template IN (:templates)";
		// No impreses totes excepte sense template => Llicències de dia
		if ($noimpres == true) $strQuery .= " AND l.imprimir = 1 AND l.impresa = 0 AND p.pendent = 0 ";
		if ($noenviat == true) $strQuery .= " AND l.mailenviat = 0 AND p.pendent = 0 AND t.template <> ''";
		if ($compta == true) $strQuery .= " AND f.comptabilitat IS NULL AND p.pendent = 0 ";

		if (is_numeric($numfactura) && $numfactura > 0) { 
		// Obté anul·lacions amb aquest número			
			$anulacions = $this->consultaFacturesAnulacio($numfactura, $anyfactura);
			foreach ($anulacions as $factura) {
				$anulaIds[] = $factura->getComandaAnulacio()->getId();
			}
			
			if (count($anulaIds) > 0) $strQuery .= " OR ( p.id IN (:anulacions) ) ";
		}
		$strQuery .= " ) ";
		
		$strQuery .= " ORDER BY ".$strOrderBY; 
		
		$query = $em->createQuery($strQuery)
			->setParameter('ininormal', $inianual)
			->setParameter('ini365', $ini365);
		

		if ($numrebut == '' && $numfactura == '') {
			// Dates normals
		} else {
			// dates dels anys escollits a les factures / rebuts
			/*if ($currentNumrebut == true) $partesrecents->setParam('numrebut',$currentNumrebut);
			if ($currentNumfactura == true) $partesrecents->setParam('numfactura',$currentNumfactura);*/
			if ($numrebut == true) $query->setParameter('numrebut',$numrebut);
			if ($numfactura == true) $query->setParameter('numfactura',$numfactura);
		}
		
		$states = explode(";", self::CLUBS_STATES);
		if ($estat != self::TOTS_CLUBS_DEFAULT_STATE) $query->setParameter('filtreestat', $states[$estat]);
	
		if (is_numeric($numfactura) && $numfactura > 0) { 
			if (count($anulaIds) > 0) {
				$query->setParameter('anulacions', $anulaIds);
			}
		}
	
		return $query;
	}
	
	protected function consultaFacturesAnulacio($nf, $af) {
		$em = $this->getDoctrine()->getManager();
		
		if (!is_numeric($nf)) $nf = 0;
		
		$strQuery = " SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
		$strQuery .= " WHERE f.num = :num AND f.comandaanulacio IS NOT NULL ";

		if (is_numeric($af) && $af > 0) {
			$datainicifactura = \DateTime::createFromFormat('Y-m-d H:i:s', $af."-01-01 00:00:00");
			$datafinalfactura = \DateTime::createFromFormat('Y-m-d H:i:s', $af."-12-31 23:59:59");
			$strQuery .= " AND f.datafactura >= :fini AND f.datafactura <= :ffi ";
		}
		
		$query = $em->createQuery($strQuery);
		
		$query->setParameter('num', $nf);
		
		if (is_numeric($af) && $af > 0) {
			$query->setParameter('fini', $datainicifactura);
			$query->setParameter('ffi', $datafinalfactura);
		}
			
		$anulacions = $query->getResult();
		
		return $anulacions;	
	}
	
	protected function consultaPartesUsuari($usuari, $pendents = false) {
	    $em = $this->getDoctrine()->getManager();
	    
	    // Consultar comandes usuari opcionalment pendents de pagament
	    $strQuery = "SELECT c FROM FecdasBundle\Entity\EntityParte c LEFT OUTER JOIN c.rebut r ";
	    $strQuery .= "WHERE c.usuari = :usuari ";
	    $strQuery .= " AND c.databaixa IS NULL ";
	    if (!$pendents) $strQuery .= " AND c.rebut IS NOT NULL AND r.databaixa IS NULL ";
	    $strQuery .= " ORDER BY c.dataalta DESC";
   
	    $query = $em->createQuery($strQuery)->setParameter('usuari', $usuari->getId());
	    
	    return $query;
	}
	
	
	protected function consultaPartesClub($club, $tipus, $desde, $fins, $strOrderBY = '') {
		$em = $this->getDoctrine()->getManager();
	
		// Consultar no només les vigents sinó totes
		$strQuery = "SELECT p, COUNT(l.id) AS HIDDEN numllicencies FROM FecdasBundle\Entity\EntityParte p JOIN p.llicencies l JOIN p.tipus t ";
		$strQuery .= "WHERE p.clubparte = :club ";
		$strQuery .= " AND p.databaixa IS NULL AND l.databaixa IS NULL ";
		if ($desde != null) $strQuery .= " AND p.dataalta >= :ininormal";
		if ($fins != null) $strQuery .= " AND p.dataalta <= :finormal";
		if ($tipus == 0) $strQuery .= " AND t.id <> :tipus";
		else $strQuery .= " AND t.id = :tipus";
		$strQuery .= " GROUP BY p ";

		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  // Només per PDF el paginator ho fa sol mentre el mètode de crida sigui POST
		
		$query = $em->createQuery($strQuery)
			->setParameter('club', $club)
			->setParameter('tipus', $tipus);
			
		if ($desde != null) $query->setParameter('ininormal', $desde->format('Y-m-d'));
		if ($fins != null)  $query->setParameter('finormal', $fins->format('Y-m-d'));
			
		return $query;
	}
	
	protected function intervalDatesPerDefecte(Request $request) {
		// Interval de dates
		$interval = array('desde' => null, 'fins' => null);
		
		$desdeDefault = "01/01/".(date("Y"));
		$interval['desde'] = \DateTime::createFromFormat('d/m/Y', $request->query->get('desde', $desdeDefault));
		
		$finsDefault = "31/12/".(date("Y"));
		if (date("m") == self::INICI_TRAMITACIO_ANUAL_MES and date("d") >= self::INICI_TRAMITACIO_ANUAL_DIA) $finsDefault = "31/12/".(date("Y")+1);		
		$interval['fins'] = \DateTime::createFromFormat('d/m/Y', $request->query->get('fins', $finsDefault));
		
		return $interval;
	}
	
	protected function getMaxNumEntity($year, $tipus) {
		$em = $this->getDoctrine()->getManager();
	
		$inici = $year."-01-01 00:00:00";
		$final = $year."-12-31 23:59:59";
	
		$strQuery = '';
		switch ($tipus) {
			case BaseController::REBUTS:
				$strQuery = "SELECT MAX(r.num) FROM FecdasBundle\Entity\EntityRebut r ";
				$strQuery .= " WHERE r.databaixa IS NULL AND r.datapagament >= '".$inici."' AND r.datapagament <= '".$final."'";
				break;
			case BaseController::FACTURES:
				$strQuery = " SELECT MAX(f.num) FROM FecdasBundle\Entity\EntityFactura f ";
				$strQuery .= " WHERE f.datafactura >= '".$inici."' AND f.datafactura <= '".$final."'";
				break;
			case BaseController::COMANDES:
				$strQuery = " SELECT MAX(c.num) FROM FecdasBundle\Entity\EntityComanda c ";
				$strQuery .= " WHERE c.dataentrada >= '".$inici."' AND c.dataentrada <= '".$final."'";
				break;
			case BaseController::CURSOS:
			    $strQuery = " SELECT MAX(c.num) FROM FecdasBundle\Entity\EntityCurs c ";
			    $strQuery .= " WHERE c.dataentrada >= '".$inici."' AND c.dataentrada <= '".$final."'";
			    break;
			case BaseController::TITULACIONS:
			    $strQuery = " SELECT MAX(t.num) FROM FecdasBundle\Entity\EntityTitulacio t ";
			    $strQuery .= " WHERE t.dataentrada >= '".$inici."' AND t.dataentrada <= '".$final."'";
			    break;
			default:
				return -1;
		}

		$query = $em->createQuery($strQuery);
		$result = $query->getSingleScalarResult();
		if ($result == null) return 0; // Primer de l'any
			
		return $result;
	}
	
	protected function crearIngres($data, $tipuspagament, $club, $import = 0, $dades = '', $comentari = '') {
		if ($data == null) $data = $this->getCurrentDate('now');
		
		if ($tipuspagament == null) $tipuspagament = self::TIPUS_PAGAMENT_CASH;
		
		if ($club == null) $club = $this->getCurrentClub();
		
		$em = $this->getDoctrine()->getManager();
		
		$maxNumRebut = $this->getMaxNumEntity($data->format('Y'), BaseController::REBUTS) + 1;
		
		$rebut = new EntityRebut($data, $tipuspagament, $maxNumRebut, null, $club, $import); 
		if (trim($dades) != '') $rebut->setDadespagament($dades);
		if (trim($comentari) != '') $rebut->setComentari($comentari);
		
		$em->persist($rebut);
		
		return $rebut;
	}
	
	protected function crearRebut($data, $tipuspagament, $comanda = null, $dades = '', $comentari = '') {
		if ($data == null) $data = $this->getCurrentDate();
		
		if ($tipuspagament == null) $tipuspagament = self::TIPUS_PAGAMENT_CASH;
		
		$em = $this->getDoctrine()->getManager();
		
		$maxNumRebut = $this->getMaxNumEntity($data->format('Y'), BaseController::REBUTS) + 1;
		
		$rebut = new EntityRebut($data, $tipuspagament, $maxNumRebut, $comanda); // Import i club agafat de la comanda
		if (trim($dades) != '') $rebut->setDadespagament($dades);
		if (trim($comentari) != '') $rebut->setComentari($comentari);
		
		$em->persist($rebut);
		
		if ($comanda != null) {
			$comanda->setDatamodificacio(new \DateTime());
			if ($comanda->esParte()) $comanda->setPendent(false);
		}
		
		return $rebut;
	}

	protected function crearFactura($comanda, $datafactura= null, $concepte = '', $compte = '') { 
		/* Data factura
		 *    - Si les dates son del mateix any posar datafactura = datacomanda : Comandes, duplicats i partes
		 *    - Si "any data llicències" > "any entrada" => Posar datafactura 01/01/any llicencies: Partes
		 */   
	    if ($comanda == null) throw new \Exception('No es pot crear la factura. Contacti amb l\'administrador');
	    
	    $current = $this->getCurrentDate();
	    $anyFactura = $comanda->getAny();
	    if ($datafactura == null) $datafactura = $comanda->getDataentrada();  
	    else $anyFactura = $datafactura->format('Y'); // Només admins
        
        if ($anyFactura > $current->format('Y')) {
            // Factura a 1 de gener
            $datafactura = \DateTime::createFromFormat('Y-m-d H:i:s', $anyFactura.'-01-01 00:00:00');
        }
		
		if ($compte == '') $compte = $this->getIbanGeneral();
		
		$em = $this->getDoctrine()->getManager();

		$maxNumFactura = $this->getMaxNumEntity($datafactura->format('Y'), BaseController::FACTURES) + 1;
		
		$factura = new EntityFactura($datafactura, $maxNumFactura, $comanda, 0, 0, $concepte, null, $compte);
		
		$em->persist($factura);
		
		$factura->setComanda($comanda);
		$factura->setComandaanulacio(null);
		$comanda->setFactura($factura);
		
		return $factura;
	}
	
	protected function createFormSortidaLlicencies($action = 'mail', $parte = null, $llicenciaid = 0, $llicenciesId = array(), $filtre = '', $checkall = true) {
	    $llicencies = array();
	    $parteid = 0;
	    
	    if ($parte != null) {
	        $parteid = $parte->getId();
	        if ($llicenciaid == 0) $llicencies = $parte->getLlicenciesSortedByName( $filtre );
	        else $llicencies[] = $parte->getLlicenciaById($llicenciaid);
	    } else {
	        foreach ($llicenciesId as $licId) {
	            $llicencia = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($licId);
	            
	            if ($filtre == '' ||
	                ($filtre != '' && strpos( mb_strtolower ($llicencia->getPersona()->getNomCognoms()),  mb_strtolower($filtre)) !== false)) {
	                    
	                    $llicencies[] = $llicencia;
	                }
	        }
	    }
	    
	    $formBuilder = $this->createFormBuilder();
	    
	    $formBuilder->add('action', 'hidden', array(
	        'data'	=> $action
	    ));
	    
	    $formBuilder->add('id', 'hidden', array(
	        'data'	=> $parteid
	    ));
	    
	    $formBuilder->add('llicenciaid', 'hidden', array(
	        'data'	=> $llicenciaid
	    ));
	    
	    $formBuilder->add('llicenciesid', 'hidden', array(
	        'data'	=> json_encode($llicenciesId)
	    ));
	    
	    $formBuilder->add('filtre', 'text', array(
	        'data'	=> $filtre
	    ));
	    
	    $formBuilder->add('checkall', 'checkbox', array(
	        'required'  => false,
	        'data'	=> $checkall
	    ));
	    
	    if ($action == 'mail') {
	        $formBuilder->add('llicencies', 'collection', array(
	            'type' 	=> new FormLlicenciaMail(array('checkall' => $checkall)),
	            'data'	=> $llicencies
	        ));
	    } else {
	        $formBuilder->add('llicencies', 'collection', array(
	            'type' 	=> new FormLlicenciaImprimir(array('checkall' => $checkall)),
	            'data'	=> $llicencies
	        ));
	    }
	    
	    return $formBuilder;
	}
	
	
	protected function facturatopdf($factura) {
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		$comanda = $factura->getComandaFactura();
		
		$club = $comanda->getClub();
		
		// Per veure-ho => acroread 86,3 %
		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			
		$pdf->init(array('author' => 'FECDAS', 'title' => $factura->getConcepte()));
			
		$pdf->setPrintFooter(false);
		$pdf->setPrintHeader(false);
		
		// set image scale factor
		//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->AddPage();
		$pdf->setCellPaddings(0,0,0,0);
		$pdf->SetFont('freesans');	
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 51, 102); // Blau fosc 003366		
		
		$y_margin = 18;
		$l_margin = 26;
		$r_margin = 21;
		
		//$dim = $pdf->getPageDimensions();  //$dim['w'] = 595.276  $dim['h']
		//$w_half = $dim['w']/2; // 300 aprox
		
		$w_half = 105 - $l_margin;
		
		$y_logos = $y_margin;
		$x_logos = $l_margin;
		$h_logos = 16;
		//$h_fedelogo = 24;
		$w_fedelogo = 20;
		$h_genelogo = 12;
		$w_genelogo = 35;
		//$h_esportlogo = 12;
		$w_esportlogo = 30;
		
		$y_fedeinfo = $y_margin;
		$x_fedeinfo = $l_margin + $w_half;
		$h_fedeinfo = 42;
		$y_clubinfo = $y_margin + $h_logos;
		$x_clubinfo = $l_margin;
		//$h_clubinfo = 41;
		$y_factuinfo = $y_margin + $h_fedeinfo;
		$offset_factuinfo = 30;
		$x_factuinfo = $l_margin + $w_half+$offset_factuinfo;
		//$h_factuinfo = 22;
		$y_taula = $y_margin + 64;
		$x_taula = $l_margin;
		//$h_taula = 145;
		$y_taula2 = $y_taula + 78;
		//$y_rebut = $y_factuinfo + $h_factuinfo + $h_taula;
		//$x_rebut = $l_margin;
		//$h_rebut = 55;

		//$y = $y_clubinfo; //$pdf->getY();
		//$x = $x_clubinfo; //$pdf->getX();

		
		//$showTemplate = !$this->isCurrentAdmin(); // Remei no mostrar elements fixes
		$showTemplate = ($this->get('session')->get('username', '') != $this->getParameter('MAIL_FACTURACIO'));  
		$showTemplate = true; // De moment imprimir sense plantilla
		
		
		//$pdf->Rect($x_logos, $y_logos, $w_half, $h_logos - 1, 'F', '', array(255, 0, 0) );  // red 				
		//$pdf->Rect($x_fedeinfo, $y_fedeinfo, $w_half+5, $h_fedeinfo - 1, 'F', '', array(0, 255, 0) ); // green
		//$pdf->Rect($x_clubinfo, $y_clubinfo, $w_half, $h_clubinfo - 1, 'F', '', array(0, 0, 255) ); // blue
		//$pdf->Rect($x_factuinfo, $y_factuinfo, $w_half - $offset_factuinfo, $h_factuinfo - 1, 'F', '', array(255, 255, 0) ); // groc
		//$pdf->Rect($x_taula, $y_taula, $w_half*2, $h_taula - 1, 'F', '', array(0, 255, 255) ); // cyan
		//$pdf->Rect($x_rebut, $y_rebut + 1, $w_half*2, $h_rebut, 'F', '', array(240, 240, 240) ); // cyan
		
		
		$pdf->SetMargins($l_margin, $y_margin, $r_margin);
		$pdf->SetAutoPageBreak 	(false, 15);
		$pdf->SetFontSize(12);
		
		if($showTemplate == true) {
			/* LOGOS */		
			// file, x, y, w, h, format, link alineacio, resize, dpi, palign, mask, mask, border, fit, hidden, fitpage, alt, altimg			
		    $pdf->Image(BaseController::IMATGE_LOGO_FECDAS, $x_logos, $y_logos, 
						$w_fedelogo, 0 , 'gif', '', 'LT', true, 320, 
						'', false, false, array(''),
						'LT', false, false);
			$pdf->Image(BaseController::IMATGE_LOGO_GENE, $x_logos+$w_fedelogo+2, $y_logos, 
						$w_genelogo, 0 , 'jpeg', '', 'T', true, 320, 
						'', false, false, array(''),
						'CT', false, false);
			$pdf->Image(BaseController::IMATGE_LOGO_ESPORT, $x_logos+$w_fedelogo+4.5, $y_logos+$h_genelogo, 
						$w_esportlogo, 0 , 'jpeg', '', 'B', true, 320, 
						'', false, false, array(''),
						'CB', false, false);
				
			/* FEDE INFO */
			
			$tbl = '<p align="right" style="padding:0;"><span style="font-size:16px;">FEDERACIÓ CATALANA<br/>D\'ACTIVITATS SUBAQUÀTIQUES</span><br/>';
			$tbl .= '<span style="font-size:11px;">Moll de la Vela, 1 (Zona Fòrum)<br/>';
			$tbl .= '08930 Sant Adrià de Besòs<br/>';
			$tbl .= 'Tel: 93 356 05 43 / Fax: 93 356 30 73<br/>';
			$tbl .= 'Adreça electrònica: '.$this->getParameter('MAIL_FECDAS').'<br/>';
			$tbl .= 'www.fecdas.cat<br/>';
			$tbl .= 'NIF: Q5855006B</span></p>';
			$pdf->writeHTMLCell($w_half+5-5, $h_fedeinfo, $x_fedeinfo, $y_fedeinfo, $tbl, '', 1, false, true, 'R', false);
		}	
		
		/* CLUB INFO */	
		$pdf->SetTextColor(0, 0, 0); // Negre	
		$pdf->SetFontSize(16);
		if ($factura->esAnulacio() == true) {
			$text = '<br/><b>FACTURA ANUL·LACIÓ</b>';
			
			$pdf->writeHTMLCell($w_half * 2, 0, $x_clubinfo, $y_clubinfo+40, $text, '', 1, false, true, 'C', false);
		}
		
		$nomFactura = $club->getNomfactura();
		$adrecaFactura = $club->getAddradreca();
		$poblacioFactura = $club->getAddrpob().($club->getAddrcp()!=''?' - '.$club->getAddrcp():'');
		$provinciaFactura = $club->getAddrprovincia();
		$cifFactura = $club->getCif();
		$tlfFactura = $club->getTelefon();
		if ($comanda->comandaUsuari()) {
		    $usuari = $comanda->getUsuari();
		    $metapersona = $usuari->getMetapersona();
		    $nomFactura = $metapersona->getNomCognoms();
		    $cifFactura =  $metapersona->getDni();
		    
		    $persona = $metapersona->getPersona(BaseController::CODI_CLUBLLICWEB);
		    if ($persona != null) {
		        $adrecaFactura  = $persona->getAddradreca();
		        $poblacioFactura = $persona->getAddrpob().($persona->getAddrcp()!=''?' - '.$persona->getAddrcp():'');
		        $provinciaFactura = $persona->getAddrprovincia();
		        $tlfFactura = $persona->getTelefons();
		    }
		} 
		
		
		/*$pdf->SetFontSize(11);
		$tbl = '<p align="left" style="padding:0;"><b>' . $club->getNom(). '</b><br/>';
		$tbl .= '' . $club->getAddradreca() . '<br/>';
		$tbl .= '' . $club->getAddrcp() . " - " . $club->getAddrpob() . '<br/>';
		$tbl .= '' . $club->getAddrprovincia() . '<br/>';
		$tbl .= 'Telf: ' . $club->getTelefon();

		$pdf->writeHTMLCell($w_half, 0, $x_clubinfo, $y_clubinfo + 10, $tbl, '', 1, false, true, 'L', false);*/

		//$w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true,
		// 	$maxh = 0, $valign = 'T', $fitcell = false 
		$pdf->SetFont('freesans', 'B', 11, '', true);
		$pdf->MultiCell($w_half,5,$nomFactura,0,'L',false, 1, $x_clubinfo, $y_clubinfo + 10, true, 3, false, true, 5, 'M', true);
		$pdf->SetFont('freesans', '', 11, '', true);
		$pdf->MultiCell($w_half,5,$adrecaFactura,0,'L',false, 1, $x_clubinfo, $y_clubinfo + 15, true, 3, false, true, 5, 'M', true);
		$pdf->MultiCell($w_half,5,$poblacioFactura,0,'L',false, 1, $x_clubinfo, $y_clubinfo + 20, true, 3, false, true, 5, 'M', true);
		$pdf->MultiCell($w_half,5,$provinciaFactura,0,'L',false, 1, $x_clubinfo, $y_clubinfo + 25, true, 3, false, true, 5, 'M', true);
		$pdf->MultiCell($w_half,5,$cifFactura,0,'L',false, 1, $x_clubinfo, $y_clubinfo + 30, true, 3, false, true, 5, 'M', true);
		if ($tlfFactura != null && $tlfFactura > 0) $pdf->MultiCell($w_half,5,'Telf: ' . $tlfFactura,0,'L',false, 1, $x_clubinfo, $y_clubinfo + 35, true, 3, false, true, 5, 'M', true);
		
		/* FACTU INFO */	
		$pdf->SetFontSize(10);
		if ($showTemplate == true) {
			$tbl = '<p align="left" style="padding:0; color: #003366; line-height: 1.5;">Factura número:<br/>';
			$tbl .= 'Data:</p>';
			$pdf->writeHTMLCell($w_half - $offset_factuinfo, 0, $x_factuinfo, $y_factuinfo, $tbl, '', 1, false, true, 'R', false);
		}
		
		$tbl  = '<p align="right" style="padding:0; line-height: 1.5;"><b>'.$factura->getNumfactura().'</b><br/>';
		$tbl .= ''.$factura->getDatafactura()->format('d/m/Y').'</p>';
		$pdf->writeHTMLCell($w_half - $offset_factuinfo-8, 0, $x_factuinfo+8, $y_factuinfo, $tbl, '', 1, false, true, 'R', false);
		
		/* TAULA DETALL */
		$pdf->SetFontSize(8);
		
		if ($factura->getDetalls() != null) {
			
			if ($showTemplate == true) {	
				$tbl = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
						<tr>
						<td width="96" align="center" style="border: 1px solid #003366; color:#003366;">REFERÈNCIA</td>
						<td width="240" align="center" style="border: 1px solid #003366; color:#003366;">CONCEPTE</td>
						<td width="58" align="center" style="border: 1px solid #003366; color:#003366;"> QUANT.</td>
						<td width="81" align="center" style="border: 1px solid #003366; color:#003366;">PREU</td>
						<td width="86" align="right" style="border: 1px solid #003366; color:#003366;">TOTAL</td>
						</tr>';
				
				// En blanc
				$tbl .= '<tr>';
				$tbl .= '<td style="height: 257px; border: 1px solid #003366;" align="center">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="left">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="center">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="center">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="right">&nbsp;</td>';
				$tbl .= '</tr>';
			
				$tbl .= '</table>';
				
				$pdf->writeHTMLCell(0, 0, $x_taula, $y_taula, $tbl, '', 2, false, true, 'L', false);
				
				/*$tbl = '<table border="0" cellpadding="2" cellspacing="0" style="border-color: #003366; border-collapse: collapse; ">
						<tr>
						<td width="96" align="left" style="height: 41px;border: 1px solid #003366; color:#003366;">&nbsp;&nbsp;&nbsp;TOTAL</td>
						<!-- 240 entre els dos -->
						<td width="95" align="center" style="border: 1px solid #003366; color:#003366;">DTE</td>
						<td width="145" align="center" style="border: 1px solid #003366; color:#003366;">BASE IMP.</td>
						<!-- 58 + 81 + 86 = 225 entre els dos -->
						<td width="90" align="center" style="border: 1px solid #003366; color:#003366;">IVA</td>
						<td width="135" align="center" style="border: 1px solid #003366; color:#003366;">TOTAL FACTURA</td>
						</tr>';
				$tbl .= '<tr><td colspan="4" align="left" style="height: 41px; border: 1px solid #003366; color:#003366;">&nbsp;&nbsp;&nbsp;Altres càrrecs</td>';
				$tbl .= '<td align="center" style="border: 1px solid #003366; color:#003366;">Import</td></tr>';
				$tbl .= '<tr style="border-bottom: none;"><td colspan="4" style="height: 38px;">&nbsp;</td>';
				$tbl .= '<td align="center" style="border: 1px solid #003366; color:#003366;">TOTAL A PAGAR<span style="font-weight:bold;font-size:12px;">&nbsp;</span></td></tr>';
				$tbl .= '</table>';
				
				//$y_taula2 = $pdf->getY();
				$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 1, false, true, 'L', false);*/
			}
			
			// Sistema nou del 2015.
			//$detallsArray = json_decode($factura->getDetalls(), true, 512, JSON_UNESCAPED_UNICODE);
			$detallsArray = json_decode($factura->getDetalls(), true); 
			
			$pdf->SetTextColor(0, 0, 0); // Negre	
			//$pdf->setY($y_taula + 10);
			$pdf->SetFontSize(10);
			
			// Columnes
			$w_codi = 27;
			$x_codi = $x_taula;
			$x_producte = $x_codi + $w_codi;
			$w_producte = 68;
			$x_total = $x_producte + $w_producte;
			$w_total = 16;
			$x_preuu = $x_total + $w_total;
			$w_preuu = 23;
			$x_import = $x_preuu + $w_preuu;
			$w_import = 23;
			$row_h = 5;
			$row_h_extra_max = 14;
			$pdf->setY($y_taula + 8);
			
			if ($detallsArray) {
			
				foreach ($detallsArray as $lineafactura) {
						
					//$preuSenseIVA = $lineafactura['total'] * $lineafactura['preuunitat'];
					
					/*$tbl = '<table border="0" cellpadding="5" cellspacing="0"><tr>
						<td width="96" align="center">'.$lineafactura['codi'].'</td>
						<td width="240" align="left">'.$lineafactura['producte'].$strExtra.'</td>
						<td width="58" align="center">'.$lineafactura['total'].'</td>
						<td width="81" align="center">'.number_format($lineafactura['preuunitat'], 2, ',', '.').'€</td>
						<td width="86" align="center"><span style="font-weight:bold;">'.number_format($lineafactura['import'], 2, ',', '.').'€</span></td>
						</tr></table>';	*/
					
					// 	$w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 0(dreta) 1 o 2, $x = '', $y = '',
			  		// $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false 
			  		// $pdf->SetFillColor(100, 100, 100); //Gris
					
					$row_y = $pdf->getY() + 1;
					$pdf->MultiCell($w_codi, $row_h, $lineafactura['codi'], 0, 'C', true, 0, $x_codi, $row_y, 
									true, 0, false, true, $row_h, 'T', true);
					$pdf->MultiCell($w_producte - 4, $row_h, $lineafactura['producte'], 0, 'L', true, 0, $x_producte + 2, $row_y, 
									true, 0, false, true, $row_h, 'T', true);
					$pdf->MultiCell($w_total, $row_h, $lineafactura['total'], 0, 'C', true, 0, $x_total, $row_y, 
									true, 0, false, true, $row_h, 'T', true);
					
				    $pdf->MultiCell($w_preuu, $row_h, number_format($lineafactura['preuunitat'], 2, ',', '.').' €', 0, 'C', true, 0, $x_preuu, $row_y, 
									true, 0, false, true, $row_h, 'T', true);
	
					$pdf->SetFont('', 'B');
					/*$pdf->MultiCell($w_import, $row_h, number_format($lineafactura['import'], 2, ',', '.').'€', 0, 'C', true, 2, $x_import, $row_y, 
									true, 0, false, true, $row_h, 'T', true);*/
					$pdf->MultiCell($w_import, $row_h, number_format(self::getImportNetDetall($lineafactura), 2, ',', '.').' €', 0, 'R', true, 2, $x_import, $row_y,
					    true, 0, false, true, $row_h, 'T', true);
					
					/*$pdf->SetFont('', 'I', 7);
					if (is_numeric($lineafactura['ivaunitat']) && $lineafactura['ivaunitat'] > 0) {
					    $row_y = $pdf->getY() + 0.8;
					    $preuivaunitat =  number_format($lineafactura['preuunitat']*$lineafactura['ivaunitat'], 2, ',', '.').'€';
					    $pdf->MultiCell($w_preuu, $row_h, 'IVA '.$preuivaunitat, 0, 'C', true, 0, $x_preuu, $row_y,
					        true, 0, false, true, $row_h, 'T', true);
					}*/
					$pdf->SetFont('', 'I', 7);
					if (is_numeric($lineafactura['descompte']) && $lineafactura['descompte'] > 0) {
					    $row_y = $pdf->getY() + 0.8;
					    $descompte =  number_format($lineafactura['descompte']*100, 0, ',', '.').'%';
					    $pdf->MultiCell($w_preuu, $row_h, 'Descompte '.$descompte, 0, 'R', true, 0, $x_preuu, $row_y,
					        true, 0, false, true, $row_h, 'T', true);
					}
					
					
					$strExtra = '';
					$row_h_extra = $row_h;
					$row_y = $pdf->getY();
					$pdf->SetTextColor(100, 100, 100); //Gris
					
					if (isset($lineafactura['extra'])) { 
					    if (is_array($lineafactura['extra'])) {  // Noms persones llicències
					
    						$strExtra = '';
    						foreach ($lineafactura['extra'] as $extra) {
    							//$strExtra .= '<br/> -&nbsp;'.$extra;
    							$strExtra .= $extra.', ';
    						}
    						if (count($lineafactura['extra']) > 0) $strExtra = substr($strExtra, 0, -2); 
    						$strExtra .= '';
        					
        					if (count($lineafactura['extra']) > 30 || $strExtra == '') {
        						$strExtra = '';
        					} else {
        						if (count($lineafactura['extra']) <= 10) $row_h_extra = 7;	
        						else $row_h_extra = max($row_h_extra_max * count($lineafactura['extra']) / 30, 10);
        					}	
        					$pdf->MultiCell($w_producte - 4, $row_h_extra, $strExtra, 0, 'L', true, 2, $x_producte + 2, $row_y,
        					    true, 0, false, true, $row_h_extra, 'T', true);
    					} else {
    					    // Possible personalització factures != llicències
    					    
    					    // Javascript UNICODE escape (Opcional)
    					    // https://r12a.github.io/apps/conversion/
    					    
    					    $strExtra = $lineafactura['extra'];
    					    
    					    $row_h_extra = 0;
    					    
    					    // $w, $h, $txt, $border = 0, $align = 'J',                      $fill = false, $ln = 1, $x = '', $y = '',
    					    // $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false )
    					    
    					    $pdf->MultiCell($w_producte - 4, $row_h_extra, $strExtra, 0, 'L', true, 2, $x_producte + 2, $row_y,
    					        true, 0, true, true, $row_h_extra, 'T', false);
    					}
					}
					
					$pdf->SetTextColor(0, 0, 0); //Negre
					$pdf->SetFont('', '', 10);				
				}
			} else {
				
				
				/*$tbl = '<table border="0" cellpadding="5" cellspacing="0"><tr>
					<td width="96" align="center">&nbsp;</td>
					<td width="240" align="left">'.$factura->getDetalls().'</td>
					<td width="58" align="center">&nbsp;</td>
					<td width="81" align="center">&nbsp;</td>
					<td width="86" align="center">&nbsp;</td>
					</tr></table>';	*/
				
				$pdf->MultiCell($w_producte - 4, $row_h_extra_max, $factura->getDetalls(), 0, 'L', true, 2, $x_producte + 2, $pdf->getY(), 
									true, 0, false, true, $row_h_extra_max, 'T', true);
										
				//$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 2, false, true, 'L', false);		// Màxim y => 150	
			}
			// Concepte
			if ($factura->esAnulacio()) $strConcepte = ($factura->getConcepte() != ''?$factura->getConcepte():'Anul·lació ').' factura '.$comanda->getFactura()->getNumFactura().' '.$comanda->getFactura()->getDatafactura()->format('d/m/Y');
			else {
				if ($factura->getImport() < 0) $strConcepte = 'Factura anul·lació '.$comanda->getComentaris();
				else {
				    //$strConcepte = 'Comanda '.$comanda->getNumComanda().' '.$comanda->getDataentrada()->format('d/m/Y');
				    $strConcepte = 'Comanda '.$comanda->getNumComanda();
				    if ($comanda->esParte()) {
				        $strConcepte .= BR.'<span style="font-style:italic; font-size:10px; color: #646464; ">'.$comanda->getPeriode().'</span>';
				    }
				}
			}
			
			$tbl = '<table border="0" cellpadding="5" cellspacing="0"><tr>
					<td width="96" align="center">&nbsp;</td>
					<td width="240" align="left">'.$strConcepte.'</td>
					<td width="58" align="center">&nbsp;</td>
					<td width="81" align="center">&nbsp;</td>
					<td width="86" align="center">&nbsp;</td>
					</tr></table>';	
				
			$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 2, false, true, 'L', false);		// Màxim y => 150

			
			if ($comanda->getComentaris()!=null && $comanda->getComentaris() != '' && !$comanda->esParte()) {  // Mostrar comentari per altres i duplicats
				$pdf->SetFont('', 'I', 10);
				$pdf->MultiCell($w_producte - 4, $row_h, $comanda->getComentaris(), 0, 'L', true, 0, $x_producte + 2, $pdf->getY(), 
								true, 0, false, true, $row_h, 'T', true);
			}
			
			$pdf->SetFont('', '', 11);
			//$pdf->SetFontSize(12);
			
			// PEU 1 BASE IMPONIBLE
			$pdf->setY($y_taula2+1);

			$baseimponible = $factura->getImport();
			$ivaDetalls = array();
			
			if ($factura->getIva() != 0 && $detallsArray) {
		        $baseimponible = self::getTotalNetDetalls($detallsArray);
		        $ivaDetalls = self::getIVADetalls($detallsArray);
			}
			
			$tbl = '<table border="0" cellpadding="5" cellspacing="0">';
			// 96 + 240 = 336     58 + 81 = 139
            $tbl .= '<tr><td colspan="2" width="336" align="center">&nbsp;</td>
					<td colspan="2" width="139" align="right" style="height: 41px; line-height: 30px; border: 1px solid #003366; color:#003366;">B.Imposable</td>
					<td width="86" align="right" style="height: 41px; line-height: 30px; border: 1px solid #003366;">'.number_format($baseimponible, 2, ',', '.').' €</td>
					</tr>';	
                
            if (count($ivaDetalls) > 0) {
                foreach ($ivaDetalls as $iva => $acumulatIva) {
                    $tbl .= '<tr><td colspan="2" width="336" align="center">&nbsp;</td>
    		          	<td colspan="2" width="139" align="right" style="height: 41px; line-height: 30px; border: 1px solid #003366; color:#003366;">IVA '.number_format($iva*100, 0, ',', '.').' %</td>
    					<td width="86" align="right" style="height: 41px; line-height: 30px; border: 1px solid #003366; ">'.number_format($iva*$acumulatIva, 2, ',', '.').' €</td>
    					</tr>';	
                }
            } else {
                $tbl .= '<tr><td colspan="2" width="336" align="center">&nbsp;</td>
    					<td colspan="2" width="139" align="right" style="height: 41px; line-height: 30px; border: 1px solid #003366; color:#003366;">IVA</td>
    					<td width="86" align="right" style="height: 41px; line-height: 30px; border: 1px solid #003366; ">--</td>
    					</tr>';	
            }
            $tbl .= '</table>';
                
            $pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 2, false, true, 'L', false);
               
			
			//$pdf->SetFontSize(13);
			// PEU 2 - TOTAL FINAL
			//$pdf->setY($y_taula2+26);
			$tbl = '<table border="0" cellpadding="5" cellspacing="0"><tr>
					<td colspan="2" width="336">&nbsp;</td><td colspan="2" align="right" width="139" style="height: 41px; line-height: 30px; border: 1px solid #003366; color:#003366;"><span style="font-weight:bold; ">TOTAL FACTURA</span></td>
					<td width="86" align="right" style="height: 41px; line-height: 30px; border: 1px solid #003366;"><span style="font-weight:bold; font-size: large;">'.number_format($factura->getImport(), 2, ',', '.').' €</span></td>
					</tr></table>';	
			
			$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 2, false, true, 'L', false);

			$pdf->SetTextColor(0, 51, 102); // Blau fosc 003366	
			
		} else {
			
			$pdf->SetFontSize(16);	
				
			$tbl = '<table border="1" cellpadding="50" cellspacing="0" style="color:#555555;">';
			$tbl .= '<tr><td><p style="line-height: 2; color:#000000;"><b>Factura corresponent a la llista de llicències ' . $comanda->getNumComanda();
			$tbl .= ' amb un import de '.number_format($factura->getImport(), 2, ',', '.') .  ' €</b></p></td></tr>';
			$tbl .= '</table>';
		
			$pdf->writeHTMLCell(0, 0, $x_taula, $y_taula, $tbl, '', 1, false, true, 'L', false);
		}
		
		if ($factura->getIva() == 0) {
			// set color for text
			$pdf->SetTextColor(0, 51, 102); // Blau
			$pdf->SetFont('dejavusans', '', 7, '', true);
			//$text = '<p>Factura exempta d\'I.V.A. segons la llei 49/2002</p>';
			$text = '<p>Factura exempta d\'I.V.A. d\'acord a l\'article UNO 20.13 de la llei de l\'IVA</p>';
			$pdf->writeHTMLCell($w_codi + $w_producte - 5, 0, $x_taula, $y_taula2+2, $text, '', 1, false, true, 'L', false);
		}
		
		$pdf->SetTextColor(0, 0, 0); // Negre
		$pdf->SetFontSize(10);

		$compte = $factura->getNumcompte() != ''?$factura->getNumcompte():$this->getIbanGeneral();

		$text = 'Número de compte corrent<br/>LA CAIXA IBAN '.$compte;
		
		$pdf->writeHTMLCell($w_codi + $w_producte - 5, 0, $x_taula, $y_taula2+20, $text, '', 1, false, true, 'L', false);

		
		/* ESPAI REBUT */	
		if ($comanda->comandaPagada() == true && !$factura->esAnulacio()) { 
			$pdf = $this->rebuttopdf($comanda->getRebut(), $pdf);
			//$pdf->writeHTMLCell($w_half*2, $h_rebut, $x_rebut, $y_rebut, '', '', 1, false, true, 'L', false);
		}

		// reset pointer to the last page
		$pdf->lastPage();
			
		return $pdf;
	}
	 
	protected function rebuttopdf($rebut, $pdf = null) {
		/* Printar rebut */
		$club = $rebut->getClub();
		
		// Per veure-ho => acroread 86,3 %
		if ($pdf == null) {
			// Nou rebut format 1/3 de A4 =>  array(  210,  297);
			// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
			// Papers => 		/vendor/tcpdf/includes/tcpdf_static.php
			$format = array(210, 99);
			$pdf = new TcpdfBridge('L', PDF_UNIT, $format, true, 'UTF-8', false);
				
			$pdf->init(array('author' => 'FECDAS', 'title' => $rebut->getConcepteRebutLlarg()));
				
			$pdf->setPrintFooter(false);
			$pdf->setPrintHeader(false);
			
			// set image scale factor
			//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
			//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
			$pdf->AddPage();
			
			$y_margin = 5; // 18 -> 10

		} else {

			$y_margin = 14 + $pdf->getY(); 
		}
		
		$r_margin = 26;
		$l_margin = 35; // 26 -> 32
		
		//$dim = $pdf->getPageDimensions();  //$dim['w'] = 595.276  $dim['h']
		//$w_half = $dim['w']/2; // 300 aprox
		
		//$w_half = 105 - $l_margin;
		
		$y_corp = $y_margin;
		$x_corp = 12;
		$ry_corp = 45;
		$rx_corp = 20;
		
		//$h_fedelogo = 12;
		$w_fedelogo = 10;
		$h_genelogo = 6;
		$w_genelogo = 16;
		//$h_esportlogo = 6;
		$w_esportlogo = 13;
		$w_fedeinfo = 80;
		$h_fedeinfo = 20;
		
		$y_header_row1 = $y_margin; 				// 					Núm: xxx
		$y_header_row2 = $y_margin + 8;				// Hem rebut: xxxx	NIF: xxxx
		$x_header_row1 = $l_margin;
		//$x_header_col2 = $pdf->getPageWidth() - ($r_margin - 20);
		$x_header_col2 = $l_margin +115;
		
		$y_quantitat = $y_header_row2 + 11;
		$x_quantitat = $l_margin;
		$x_quantitat_offset = 26;
		$y_quantitat_offset = -3;
		$h_quantitat = 13;
		$w_quantitat = 122;
		
		$y_concepte = $y_quantitat + 16;
		$x_concepte = $l_margin;
		$x_concepte_offset = 29;
		$y_concepte_offset = -3;
		$h_concepte = 22;
		$w_concepte = 119;
		
		$y_total = $y_concepte + $h_concepte + 3; 		// Mitjançant
		$x_total = $l_margin;
		$y_total_offset_1 = 6; 						// Import
		$x_total_col2 = $l_margin +60;
		$y_total_offset_2 = 9; 						// Lloc i data
		
		//$pdf->Rect($x_rebut, $y_rebut + 1, $w_half*2, $h_rebut, 'F', '', array(240, 240, 240) ); // cyan
		
		$pdf->setCellPaddings(0,0,0,0);
		$pdf->SetFont('freesans');	
		// set color for background
		$pdf->SetFillColor(255, 255, 255); //Blanc
		// set color for text
		$pdf->SetTextColor(0, 51, 102); // Blau fosc 003366		
		
		$pdf->SetMargins($l_margin, $y_margin, $r_margin);
		$pdf->SetAutoPageBreak 	(false, 25);
		$pdf->SetFontSize(8.5);
		
		//$showTemplate = !$this->isCurrentAdmin(); // Remei no mostrar elements fixes
		$showTemplate = ($this->get('session')->get('username', '') != $this->getParameter('MAIL_FACTURACIO'));  
		$showTemplate = true; // De moment imprimir sense plantilla
		
		if ($showTemplate == true) {
			/* LOGOS */		
			// Start Transformation
	    	$pdf->StartTransform();
	    	// Rotate 90 degrees
	   		$pdf->Rotate(90, $l_margin + $rx_corp , $y_corp + $ry_corp);
			
			// file, x, y, w, h, format, link,  alineacio, resize, dpi, palign, mask, mask, border, fit, hidden, fitpage, alt, altimg			
	   		$pdf->Image(BaseController::IMATGE_LOGO_FECDAS, $x_corp, $y_corp, 
							$w_fedelogo, 0 , 'gif', '', 'LT', true, 320, 
							'', false, false, array(''),
							'LT', false, false);
			$pdf->Image(BaseController::IMATGE_LOGO_GENE, $x_corp+$w_fedelogo+2, $y_corp, 
							$w_genelogo, 0 , 'jpeg', '', 'T', true, 320, 
							'', false, false, array(''),
							'CT', false, false);
			$pdf->Image(BaseController::IMATGE_LOGO_ESPORT, $x_corp+$w_fedelogo+4.5, $y_corp+$h_genelogo, 
							$w_esportlogo, 0 , 'jpeg', '', 'B', true, 320, 
							'', false, false, array(''),
							'CB', false, false);
			
			/* FEDE INFO */
			$txt = '<p align="left" style="padding:0;line-height: 1"><span style="font-size:12px;">FEDERACIÓ CATALANA<br/>D\'ACTIVITATS SUBAQUÀTIQUES</span><br/>';
			$txt .= '<span style="font-size:6.5px;">Moll de la Vela, 1 (Zona Fòrum)<br/>';
			$txt .= '08930 Sant Adrià de Besòs<br/>';
			$txt .= 'Tel: 93 356 05 43 / Fax: 93 356 30 73<br/>';
			$txt .= 'Adreça electrònica: '.$this->getParameter('MAIL_FECDAS').'<br/>';
			$txt .= 'www.fecdas.cat<br/>';
			$txt .= 'NIF: Q5855006B</span></p>';
			$pdf->writeHTMLCell($w_fedeinfo, $h_fedeinfo, $x_corp+$w_fedelogo+$w_genelogo+5, $y_corp, $txt, '', 1, false, true, 'L', false);
			
			
	   		// Stop Transformation
	   		$pdf->StopTransform();
	   	}
		
		//$pdf->setFontSpacing(0.5);
    	$pdf->setFontStretching(125);
		
		/* REBUT INFO */	
		$hideText = '';
		if ($showTemplate != true) $hideText = 'color:white;'; 
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Rebut núm.&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'.$rebut->getNumRebutCurt().'</span></p>';
		$pdf->writeHTMLCell(50, 0, $x_header_col2, $y_header_row1, $txt, '', 1, false, true, 'L', false);
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Hem rebut de:&nbsp;&nbsp;&nbsp;</p>';
		//$txt .= '<span style="color:#000000; font-size:12px;">'.$club->getNom().'</span></p>';
		$pdf->writeHTMLCell(0, 0, $x_header_row1, $y_header_row2, $txt, '', 1, false, true, 'L', false);
		
		$nomRebut = $club->getNomfactura();
		$cifRebut = $club->getCif();
		$usuari = $rebut->usuariComanda();
		if ($usuari != null) {
		    $metapersona = $usuari->getMetapersona();
		    $nomRebut = $metapersona->getNomCognoms();
		    $cifRebut =  $metapersona->getDni();
		} 
		
		$pdf->SetTextColor(0, 0, 0); // Negre	
		$pdf->MultiCell(80,0,$nomRebut,0,'L',true, 1, $x_header_row1 + 25, $y_header_row2 - 0.5, true, 3, false, true, 5, 'M', true); // Amplada variable
		$pdf->SetTextColor(0, 51, 102); // Blau fosc 003366		
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">NIF:&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'.$cifRebut.'</span></p>';
		$pdf->writeHTMLCell(50, 0, $x_header_col2, $y_header_row2, $txt, '', 1, false, true, 'L', false);
		
		/* REBUT QUANTITAT */	
		$f = new \NumberFormatter("ca_ES.utf8", \NumberFormatter::SPELLOUT);
    	$importFloor = floor($rebut->getImport());
    	$importDec = round($rebut->getImport() - $importFloor, 2)*100;
    	$importTxt = $f->format($importFloor);// . ($importDec < 0.001)?'':' amb '. $f->format($importDec*100);
    	$importTxt .= ($importDec == 0)?'':' amb '. $f->format($importDec);
		$importTxt .= ' Euros';
		
		if ($showTemplate == true) {
			$txt = '<p align="left">la quantitat de</p>';
			$pdf->writeHTMLCell(0, 0, $x_quantitat, $y_quantitat, $txt, '', 1, false, true, 'L', false);
			$pdf->Rect($x_quantitat + $x_quantitat_offset, $y_quantitat + $y_quantitat_offset, $w_quantitat, $h_quantitat, '', 
					array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 51, 102))), '' );
		}
		$txt = '<p style="color:#000000; font-size:14px; ">'.ucfirst($importTxt).'</p>';
		$pdf->writeHTMLCell($w_quantitat-10, 0, $x_quantitat + $x_quantitat_offset + 5, $y_quantitat + $y_quantitat_offset + 2, $txt, '', 1, false, true, 'L', false);
		
		/* REBUT CONCEPTE */	
		/*$concepte = '';
		$comandes = $rebut->getComandes(); 
		
		if ($rebut->getComentari()!=null && $rebut->getComentari() != '') { 
			$concepte .= $rebut->getComentari();
		} else {
			if (count($comandes) == 0 || $rebut->esAnulacio()) {  // Rebut no associat a cap comanda
				if ($rebut->esAnulacio()) $concepte .= 'Rebut s\'anul·lació, import acumulat al saldo del club';
				else $concepte .= 'Ingrés acumulat al saldo del club';
			} else {
				if ($rebut->getNumFactures() == 1) $concepte .= 'FACTURA: ';
				else $concepte .= 'FACTURES: ';
				$concepte .= $rebut->getLlistaNumsFactures();
	
				if ($rebut->getRomanent() > 0) {
					$concepte .= '<br/>Amb un romanent acumulat a favor del club de ';
					$concepte .= '<b>'.number_format($rebut->getRomanent(), 2, ',', '.').' €</b>';
				}
			}
		}	*/
		
		$concepte = '';
		$comandes = $rebut->getComandes(); 
		if (count($comandes) > 0) {
			if ($rebut->getNumFactures() == 1) $concepte .= 'FACTURA: ';
			else $concepte .= 'FACTURES: ';
			$concepte .= $rebut->getLlistaNumsFactures();
		}
		if (strlen($concepte) < 50 && $rebut->getComentari()!=null && $rebut->getComentari() != '') { 
			$concepte .= '<br/>'.$rebut->getComentari();
		}
		
		if ($showTemplate == true) {
			$txt = '<p align="left" style="padding:0;">en concepte de:</p>';
			$pdf->writeHTMLCell(0, 0, $x_concepte, $y_concepte, $txt, '', 1, false, true, 'L', false);
			$pdf->Rect($x_concepte + $x_concepte_offset, $y_concepte + $y_concepte_offset, $w_concepte , $h_concepte , '', 
					array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 51, 102))), '' );
		}
		
		$font = "font-size:14px;";
		
		if (strlen($concepte) > 300) $font = "font-size:8px;";
		else {
		    if (strlen($concepte) > 150) $font = "font-size:10px;";
		    else {
		        if (strlen($concepte) > 100) $font = "font-size:12px;";
		    }
		}
		
		
		$txt = '<p style="color:#000000; '.$font.' ">'.$concepte.'</p>';
		$pdf->writeHTMLCell($w_concepte - 10, 0, $x_concepte + $x_concepte_offset + 5, $y_concepte + $y_concepte_offset + 2, $txt, '', 1, false, true, 'L', false);
		
		
		/* REBUT FOOTER */	
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Mitjançant:&nbsp;&nbsp;&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'.BaseController::getTextTipusPagament($rebut->getTipuspagament()) .'</span></p>';
		$pdf->writeHTMLCell(0, 0, $x_total, $y_total, $txt, '', 1, false, true, 'L', false);
				
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Són:</p>';
		$pdf->writeHTMLCell(10, 0, $x_total + $x_total_col2, $y_total + $y_total_offset_1, $txt, '', 1, false, true, 'L', false);
		$txt = '<p align="right" style="padding:0;'.$hideText.'"><span style="color:#000000; font-size:14px;">'.number_format($rebut->getImport(), 2, ',', '.');
		$txt .= '</span>&nbsp;&nbsp;&nbsp; Euros</p>';
		$pdf->writeHTMLCell(0, 0, $x_total + $x_total_col2+11, $y_total + $y_total_offset_1 - 1, $txt, '', 1, false, true, 'L', false);
		
		/*$oldLocale = setlocale(LC_TIME, 'ca_ES.utf8');
		$mesData = $rebut->getDatapagament()->format('m');
		$litDe = 'de ';
		if ($mesData == 4 || $mesData == 8 || $mesData == 10) $litDe = 'd\'';
		
		$dateFormated = utf8_encode( strftime('%A %e '.$litDe.'%B de %Y', $rebut->getDatapagament()->format('U') ) );
		setlocale(LC_TIME, $oldLocale);*/
		
		/*$mesData = $rebut->getDatapagament()->format('m');
		$litDe = "de ";
		if ($mesData == 4 || $mesData == 8 || $mesData == 10) $litDe = "d''";*/
		
		$formatter = new \IntlDateFormatter('ca_ES.utf8', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		//$formatter->setPattern("eeee d '".$litDe."'MMMM "."'de'"." yyyy");
		$formatter->setPattern("eeee d MMMM "."'de'"." yyyy");
		$dateFormated = $formatter->format($rebut->getDatapagament());


		$pdf->setFontStretching(100);
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Sant Adrià del Besòs, &nbsp;&nbsp;&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'. $dateFormated .'</span></p>';
		$pdf->writeHTMLCell(0, 0, $x_total, $y_total+$y_total_offset_2, $txt, '', 1, false, true, 'L', false);
		
		  
		// reset pointer to the last page
		$pdf->lastPage();
		
		return $pdf;
	
	}
	
	protected function enviarMailLlicenciesBulk($request, $llicencies) {
	
	    // Use AntiFlood to re-connect after 10 emails And specify a time in seconds to pause for (20 secs)
	    $this->get('mailer')->registerPlugin(new \Swift_Plugins_AntiFloodPlugin(10));
	    //$this->get('mailer')->registerPlugin(new \Swift_Plugins_AntiFloodPlugin(10, 20));
	    
	    foreach ($llicencies as $llicencia) {
   	        $this->enviarMailLlicencia($request, $llicencia);
    	}
	}
	
	protected function enviarMailLlicencia($request, $llicencia) {
	    
	    if ($llicencia == null) throw new \Exception("Error en les dades de la llicència");
	    
	    $parte = $llicencia->getParte();
	    
	    if ($parte == null) throw new \Exception("Error en les dades de la llista");
	    
	    $club = $parte->getClub();
	    
	    if ($club == null) throw new \Exception("Error en les dades del club");
	    
	    if ($parte->getTipus()->getEs365()) $cursAny = $parte->getCurs();
	    else $cursAny = $parte->getAny();
	    $template = $parte->getTipus()->getTemplate();
	    
	    $persona = $llicencia->getPersona();
	    
	    if ($persona == null) throw new \Exception("Error en les dades de la persona");
	    
	    $tomails = array(); 
	    if ($persona->getMail() == '' || $persona->getMail() == null) {
	        // Si la persona no té mail s'envia al club
	        if ($club->getMail() == '' || $club->getMail() == null) throw new \Exception($persona->getNomCognoms().' i club sense mail');
	        
	        $tomails = $club->getMails();
	    } else {
	        $tomails = $persona->getMails();
	    }
	    
	    $attachments = array();
	    
	    $method = "textLlicencia".$template."mail";
	    
	    if (!method_exists($this, $method)) throw new \Exception("Error generant el text del correu de la llicència. No existeix la plantilla");
	    
	    $textMail = $this->$method( $cursAny );
	    
	    if (!isset($textMail['subject']) || !isset($textMail['body']) || !isset($textMail['greeting'])) throw new \Exception("Error generant el text del correu de la llicència");
	        
	    $subject = $textMail['subject'];
	    $body = $textMail['body'];
	    $salutacio = $textMail['greeting'];
	    
	    $method = "printLlicencia".$template."pdf";
	    
	    if (!method_exists($this, $method)) throw new \Exception("Error generant la llicència. No existeix la plantilla");
	    
	    $pdf = $this->$method( $request, $llicencia );
	    
	    $nom =  "llicencia_".$cursAny."_".$llicencia->getId()."_".$llicencia->getPersona()->getDni().".pdf";
	    
	    $attachments[] = array( 'name' => $nom,
	        //'data' => $attachmentData = $pdf->Output($attachmentName, "E") 	// E: return the document as base64 mime multi-part email attachment (RFC 2045)
	        'data' => $pdf->Output($nom, "S")  // S: return the document as a string (name is ignored).)
	    );
	    
	    $bccmails = array($this->getParameter('MAIL_LLICENCIES'));
	    $this->buildAndSendMail($subject, $tomails, $body, $bccmails, null, $attachments, 600, $salutacio);
	    
	    $llicencia->setMailenviat( 1 );
	    $llicencia->setDatamail( new \DateTime() );
	}
	
	protected function textLlicenciaG0mail( $cursAny ) {
		$subject = "Federació Catalana d'Activitats Subaquàtiques. Llicència federativa ".$cursAny;
		
		$body  = "<p>Us donem la benvinguda, esportista</p>";
		$body .= "<p style='text-align: justify;'>Amb aquest mateix correu reps la teva llicència esportiva corresponent a la <b>temporada ".$cursAny."</b>.</p>";
		$body .= "<p style='text-align: justify;'>La FECDAS ha fet un nou pas endavant en el procés constant de millora i ha intensificat la seva relació amb el món digital. ";
		$body .= "Amb la digitalització de la llicència esportiva pretenem facilitar-ne l'ús i, també, posar a la teva disposició de manera senzilla tota la informació que hi està relacionada.</p>";
		$body .= "<p style='text-align: justify;'>La teva llicència digital permet accedir a la pòlissa que et dóna cobertura, al protocol d'actuació en el cas d'accidents i al comunicat d'accidents. ";
		$body .= "Aquests documents els tens a l'abast a la teva llicència federativa digital a través dels hipervincles corresponents.</p>";
		$body .= "<p style='text-align: justify;'>A més a més, tenir la federativa et dóna molts avantatges i descomptes. Mira'ls en aquest enllaç: <a href='http://www.fecdas.cat/docs/centres2019.pdf' target='_blank'>http://www.fecdas.cat/docs/centres2019.pdf</a></p>";
		$body .= "<p style='text-align: justify;'><b>Recordeu que cal notificar els viatges a l'estranger</b>. enviant un e-mail a ";
		$body .= "<a href='mailto:".$this->getParameter('MAIL_FECDAS')."'>".$this->getParameter('MAIL_FECDAS')."</a>";
		$body .= " indicant: el vostre nom complet, DNI, dies d'anada i tornada i el país. <b>Termini de notificació</b>: fins 7 dies abans de la sortida. No cal notificar els viatges a Portugal i França.</p>";
		$body .= "<p style='text-align: justify;'>T'agraïm la confiança que diposites en la FECDAS; t'animem a competir i gaudir de les activitats subaquàtiques amb il·lusió, i ens posem a la teva disposició per al que et calgui.</p>";
		
		$salutacio  = "<p>Cordialment,</p>";
		$salutacio .= "<p>Equip FECDAS</p>";
		
		return array(
			'subject' 	=> 	$subject,
			'body' 		=>	$body,
			'greeting'	=> 	$salutacio
		);
	}
	
	protected function textLlicenciaESmail( $curs ) {
		return $this->textLlicenciaFecdasmail($curs); 
	}

	protected function textLlicenciaCSmail( $curs ) {
		return $this->textLlicenciaFecdasmail($curs); 
	}

	protected function textLlicenciaF0mail( $curs ) {
	    return $this->textLlicenciaFecdasmail($curs);
	}

	private function textLlicenciaFecdasmail( $curs ) {
		$subject = "Federació Catalana d'Activitats Subaquàtiques. Llicència federativa curs ".$curs;
		
		$body  = "<p>Us donem la benvinguda, esportista</p>";
		$body .= "<p style='text-align: justify;'>Amb aquest mateix correu reps la teva llicència esportiva corresponent a la <b>temporada ".$curs."</b></p>";
		$body .= "<p style='text-align: justify;'>La FECDAS ha fet un nou pas endavant en el procés constant de millora i ha intensificat la seva relació amb el món digital. ";
		$body .= "Amb la digitalització de la llicència esportiva pretenem facilitar-ne l'ús i, també, posar a la teva disposició de manera senzilla tota la informació que hi està relacionada.</p>";
		$body .= "<p style='text-align: justify;'>La teva llicència digital permet accedir a la pòlissa que et dóna cobertura, al protocol d'actuació en el cas d'accidents i al comunicat d'accidents. ";
		$body .= "Aquests documents els tens a l'abast a la teva llicència federativa digital a través dels hipervincles corresponents.</p>";
		$body .= "<p style='text-align: justify;'>T'agraïm la confiança que diposites en la FECDAS; t'animem a competir i gaudir de les activitats subaquàtiques amb il·lusió, i ens posem a la teva disposició per al que et calgui.</p>";

		$salutacio  = "<p>Cordialment,</p>";
		$salutacio .= "<p>Equip FECDAS</p>";
		
		return array(
			'subject' 	=> 	$subject,
			'body' 		=>	$body,
			'greeting'	=> 	$salutacio
		);
	}


	protected function textLlicenciaT1mail( $curs ) {
		$subject = "AVÍS IMPORTANT: Assegurança d'Accidents TecnoCampus ".substr($curs,2);  // p.e. Curs 16-17
		
		$body  = "<p>Hola,</p>";
		$body .= "<p style='text-align: justify;'>T'enviem en el document adjunt el teu carnet digital vinculat a la teva assegurança acadèmica d'accidents del curs ".$curs."</p>";
		$body .= "<p style='text-align: justify;'>El carnet personalitzat t'identifica com a contractant d'una assegurança amb l'empresa Mútuacat";
		$body .= " i et dóna accés als documents que hi estan relacionats: la <b>pòlissa</b> (inclou els centres mèdics on pots adreçar-te),";
		$body .= " el <b>protocol</b> que cal seguir si es produeix algun accident i el <b>comunicat</b> que, un cop emplenat,";
		$body .= " cal fer arribar a la companyia d'assegurances.</p>";
		
		$salutacio  = "<p>Salutacions cordials,</p>";
		$salutacio .= "<p>Equip FECDAS</p>";
		
		return array(
			'subject' 	=> 	$subject,
			'body' 		=>	$body,
			'greeting'	=> 	$salutacio
		);
	}

	protected function textLlicenciaT2mail( $curs ) {
		$subject = "AVÍS IMPORTANT: Assegurança d'Accidents TecnoCampus ".substr($curs,2);  // p.e. Curs 16-17
		
		$body  = "<p>Hola,</p>";
		$body .= "<p style='text-align: justify;'>T'enviem en el document adjunt el teu carnet digital vinculat a la teva assegurança acadèmica d'accidents del curs ".$curs."</p>";
		$body .= "<p style='text-align: justify;'>El carnet personalitzat t'identifica com a contractant d'una assegurança amb l'empresa Mútuacat";
		$body .= " i et dóna accés als documents que hi estan relacionats: la <b>pòlissa</b> (inclou els centres mèdics on pots adreçar-te),";
		$body .= " el <b>protocol</b> que cal seguir si es produeix algun accident i el <b>comunicat</b> que, un cop emplenat,";
		$body .= " cal fer arribar a la companyia d'assegurances.</p>";
		$body .= "<p style='text-align: justify;'>En el cas que cursis l'assignatura de <b>Subaquàtiques</b>, comptes amb una altra assegurança ";
		$body .= "-amb l'empresa Helvetia- vinculada a aquesta pràctica esportiva.</p>";
		$body .= "<p style='text-align: justify;'>En aquest cas, el teu carnet digital compte amb dos jocs d'hipervincles:</p>";
		$body .= "<p style='text-align: justify;'>1.- El primer joc està relacionat amb l'<b>assegurança acadèmica</b> d'accidents bàsica.</p>";
		$body .= "<p style='text-align: justify;'>2.- El segon joc, distingit amb el mot \"<b>busseig</b>\",";
		$body .= " et dóna accés als documents que estan relacionats amb la pràctica de les activitats subaquàtiques:";
		$body .= " la <b>pòlissa</b> d'Helvetia, el <b>protocol d'accidents</b> que cal seguir si es produeix algun accident i el <b>comunicat</b> que,";
		$body .= " un cop emplenat, cal fer arribar a la companyia d'assegurances <b>Helvetia</b>.</p>";
		
		$salutacio  = "<p>Salutacions cordials,</p>";
		$salutacio .= "<p>Equip FECDAS</p>";
		
		return array(
			'subject' 	=> 	$subject,
			'body' 		=>	$body,
			'greeting'	=> 	$salutacio
		);
	}
	
	protected function textLlicenciaTMmail( $curs ) {
	    return $this->textLlicenciaG0mail( $curs );
	    
	}
	
	private function getAnyPolissa($llicencia) {
	    if ($llicencia == null || $llicencia->getParte() == null) return date('Y');
	    
	    $parte = $llicencia->getParte();
	    $anyAlta = $parte->getDataalta()->format('Y');
	    $anyCaduca = $parte->getDatacaducitat()->format('Y');
    
	    if ($anyAlta == $anyCaduca) return $anyAlta;

	    if (date('Y') >= $anyCaduca) return $anyCaduca;
	    
	    return $anyAlta;
	}
	
	protected function printLlicenciaG0pdf( $request, $llicencia ) {
		$yLinks = 77;
		$links = array(	array('text' => 'pòlissa', 'link'=> $request->getUriForPath('/media/asseguranca/'.str_replace("%%YEAR%%", $this->getAnyPolissa($llicencia), BaseController::POLISSA_BUSSEIG))),
				array('text'=> 'protocol', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_BUSSEIG)),
				array('text' => 'comunicat', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_BUSSEIG)));
				
		$pdf = $this->printDigitalFecdas( $llicencia, $links, $yLinks, BaseController::TEMPLATE_GENERAL );
		
		return $pdf;
	}
	
	protected function printLlicenciaESpdf( $request, $llicencia ) {
		$yLinks = 77;
		$links = array(	array('text' => 'pòlissa', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_ESCOLAR)),
				array('text'=> 'protocol', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_ESCOLAR)),
				array('text' => 'comunicat', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_ESCOLAR)));
				
		$pdf = $this->printDigitalFecdas( $llicencia, $links, $yLinks, BaseController::TEMPLATE_ESCOLAR );
		
		return $pdf;
	}
	
	protected function printLlicenciaCSpdf( $request, $llicencia ) {
		$yLinks = 67;
		$links = array(	array('text' => 'pòlissa', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_ESCOLAR)),
						array('text'=> 'protocol', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_ESCOLAR)),
						array('text' => 'comunicat', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_ESCOLAR)),
		    array('text'=> 'pòlissa busseig', 'link'=> $request->getUriForPath('/media/asseguranca/'.str_replace("%%YEAR%%", $this->getAnyPolissa($llicencia), BaseController::POLISSA_BUSSEIG))),
						array('text'=> 'protocol busseig', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_BUSSEIG)),
						array('text'=> 'comunicat busseig', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_BUSSEIG)));
				
		$pdf = $this->printDigitalFecdas( $llicencia, $links, $yLinks, BaseController::TEMPLATE_ESCOLAR_SUBMARINISME );
		
		return $pdf;
	}
	
	protected function printLlicenciaF0pdf( $request, $llicencia ) {
	    $yLinks = 77;
	    $links = array(	array('text' => 'pòlissa', 'link'=> $request->getUriForPath('/media/asseguranca/'.str_replace("%%YEAR%%", $this->getAnyPolissa($llicencia), BaseController::POLISSA_BUSSEIG))),
	        array('text'=> 'protocol', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_BUSSEIG)),
	        array('text' => 'comunicat', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_BUSSEIG)));
	    
	    $pdf = $this->printDigitalFecdas( $llicencia, $links, $yLinks, BaseController::TEMPLATE_PESCA );
	    
	    return $pdf;
	}
	
		
	private function printDigitalFecdas( $llicencia, $links, $yLinks, $template ) {
		
		// Paper cordinates are calculated in this way: (inches * 72) where (1 inch = 25.4 mm)
		// Definir paper 13,3'' => 29cmx17cm (WxH) en 16:9
		// Definir paper 7'' => => 15cmx9cm (WxH) en 16:9
		
	    //$width = 150; => 86         Mida nova = Mida actual * 86/150
	    //$height = 90; => 54         Mida nova = Mida actual * 54/90
	    
	    $factor_w = 86/150;    //  0.573
	    $factor_h = 54/90;     //  0.6
	    
	    $x_titols = 5*$factor_w;
	    
	    // Posicions
	    $xTit = 0;
	    $yTit =	0;
	    $yCad = 0;
	    $yNai =	0;
	    $offset = 0;
	    $title = "";
	    $image = "";
	    $textShadow1 = array();
	    $textShadow2 = array();
	    $fontSize = 8.5;
	    $marginRight = "";
	    $linksColor = array();
	    
	    switch ($template) {
	        case BaseController::TEMPLATE_ESCOLAR:
	        case BaseController::TEMPLATE_ESCOLAR_SUBMARINISME:
	            $yTit =	16*$factor_h;
	            $offset = 7*$factor_h;
	            $yNai =	58*$factor_h-$offset;
	            $title = 'Llicència Curs Escolar FECDAS' . date("Y");
	            $image = BaseController::IMATGE_ANVERS_ESCOLAR;
	            $fontSize = 9;
	            $textShadow1 = array('enabled' => true, 'depth_w' => 0.5, 'depth_h' => 0.5, 'color' => array(53,153,179), 'opacity' => 0.75, 'blend_mode' => 'Normal');
	            $textShadow2 = array('enabled' => true, 'depth_w' => 0.3, 'depth_h' => 0.3, 'color' => array(53,153,179), 'opacity' => 0.6, 'blend_mode' => 'Normal');
	            $linksColor  = array(53,153,179);
	            $yCad = (count($links) <= 3 ? 70*$factor_h-$offset: $yNai-0.2);
	            
	            break;
	            
	        case BaseController::TEMPLATE_PESCA:
	            $yTit =	20*$factor_h;
	            $offset = 3*$factor_h;
	            $yCad = 71*$factor_h-$offset;
	            $yNai =	58*$factor_h-$offset;
	            $title = 'Llicència Pesca submarina' . date("Y");
	            $image = BaseController::IMATGE_ANVERS_PESCA;
	            $textShadow1 = array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(39,65,140), 'opacity' => 0.75, 'blend_mode' => 'Normal');
	            $textShadow2 = array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(39,65,140), 'opacity' => 0.6, 'blend_mode' => 'Normal');
	            $marginRight = 10*$factor_w;
	            $linksColor = array(39,65,140);
	            
	            break;
	            
	        default:       //  case BaseController::TEMPLATE_GENERAL:
	            $yTit =	19*$factor_h;
	            $offset = 3*$factor_h;
	            $yCad = 71*$factor_h-$offset;
	            $yNai =	58*$factor_h-$offset;
	            $title = 'Llicència FECDAS' . date("Y");
	            $image = BaseController::IMATGE_ANVERS_GENERAL;
	            $textShadow1 = array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(39,65,140), 'opacity' => 0.75, 'blend_mode' => 'Normal');
	            $textShadow2 = array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(39,65,140), 'opacity' => 0.6, 'blend_mode' => 'Normal');
	            $marginRight = 10*$factor_w;
	            $linksColor = array(39,65,140);
	            
	            break;
	    }
	    
		//$yTit =	($template == BaseController::TEMPLATE_GENERAL?12:8)*$factor_h;
		//$offset = ($template == BaseController::TEMPLATE_GENERAL?3:5)*$factor_h;		
		$yNom =	40*$factor_h-$offset;		
		$yDni =	46*$factor_h-$offset;	
		$yCat =	52*$factor_h-$offset;		
		$yNai =	58*$factor_h-$offset;
		//if ($template == BaseController::TEMPLATE_GENERAL) $yCad = 71*$factor_h-$offset;		
		//else $yCad = (count($links) <= 3 ? 70*$factor_h-$offset: $yNai-0.2);
		$yClu =	65*$factor_h-$offset;		
		$yTlf =	$yClu-0.3;	
		

		// Links docs
		$x = $x_titols + 5*$factor_w;
		$y = $yLinks*$factor_h;
		$yOffset = 10*$factor_h;
		$hLink = 6.8*$factor_h;
		$buttonsMargin = 5*$factor_w;

		//$pageLayout = array($width, $height); //  or array($height, $width) 
		// Printer EVOLIS PEBBLE 4 - ISO 7810, paper size CR80 BUSINESS_CARD_ISO7810 => 54x86 mm 2.13x3.37 in
		$format = \TCPDF_STATIC::getPageSizeFromFormat('BUSINESS_CARD_ISO7810');
		$pdf = new TcpdfBridge('L', PDF_UNIT, $format, true, 'UTF-8', false);
		
		$pdf->init(array('author' => 'FECDAS', 'title' => $title));

		$pdf->setPrintFooter(false);
		$pdf->setPrintHeader(false);
				
		// zoom - layout - mode
		$pdf->SetDisplayMode('real', 'SinglePage', 'UseNone');
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(5*$factor_w, 5*$factor_h, 5*$factor_w);
		$pdf->SetAutoPageBreak 	(false, 5*$factor_h);
		//$pdf->SetMargins(0, 0, 0);
		//$pdf->SetAutoPageBreak 	(false, 0);
		$pdf->SetTextColor(255, 255, 255); 
			
		$pdf->AddPage('L', 'BUSINESS_CARD_ISO7810');
		
		$srcImatge = $image;
		
		$pdf->Image($srcImatge, 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), 'jpg', '', '', false, 320, 
						'', false, false, 1, false, false, false);
		
		$parte = $llicencia->getParte();
		$club = $parte->getClubparte();
		//$polissa = $parte->getTipus()->getPolissa();
		
		// Dades
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat();
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);
				
		$pdf->SetTextColor(0, 0, 255);
		$pdf->SetTextColor(255,255,255);
		
//		$pdf->SetFillColor(224,224,224);
//		$pdf->SetAlpha(0.7);
	
		$pdf->setTextShadow($textShadow1);
		$pdf->SetFont('dejavusans', 'B', $fontSize, '', true); 
		$pdf->setFontStretching(100);		
		//$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C', 0, 0, $xTit, $yTit);

//		$pdf->SetAlpha(1);
		$pdf->SetFont('dejavusans', 'B', 7);
		$pdf->SetTextColor(255, 255, 255);

		$pdf->setTextShadow($textShadow2);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yNom, '<span style="font-size: small;">Nom: </span>'.$persona->getNomCognoms(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yDni, '<span style="font-size: small;">DNI/Passaport: </span>'.$persona->getDni(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yCat, '<span style="font-size: small;">Categoria/Nivell: </span>'.$llicencia->getCategoria()->getCategoria(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yNai, '<span style="font-size: small;">Data Naixement: </span>'.$persona->getDatanaixement()->format('d/m/Y'), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yClu, '<span style="font-size: small;">Entitat: </span>', 0, 0, false, true, 'L', true);
		
		/* Ajustar nom del club */
		$pdf->SetY($yClu-0.5);
		$pdf->SetX(20*$factor_w);
		$pdf->Cell(75*$factor_w, 0, $club->getNom(), 0, 0, 'L', false, '', 1);
		
		if ($marginRight != "") $pdf->SetRightMargin($marginRight);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yCad, '<span style="font-size: small;">Vàlida fins/Valid until: </span>'. $datacaduca->format('d/m/Y'), 0, 0, false, true, 'R', true);
		
		if ($club->getTelefon() != null) {
    		$pdf->SetFont('dejavusans', 'B', 6);
    		
    		$pdf->writeHTMLCell(0, 0, $x_titols, $yTlf, '<span style="font-size: small;">Telf. Entitat: </span>'.$club->getTelefon(), 0, 0, false, true, 'R', true);
    	}
		//$pdf->setFontSpacing(0.5);
    	//$pdf->setFontStretching(80);

		//Disable
		$pdf->setTextShadow(array('enabled'=>false));
				
		$margins = $pdf->getMargins();
		$width = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
		
		//if ($template == BaseController::TEMPLATE_GENERAL) $pdf->SetTextColor(39,65,140);
		//else  $pdf->SetTextColor(53,153,179);
		$pdf->SetTextColor($linksColor);
		$pdf->SetFont('helvetica', 'B', 6, '', true);

		$margins = $pdf->getMargins();
		$width = $pdf->getPageWidth() - $margins['left'] - $margins['right']; 
		$wLink = $width/3 - 2 * $buttonsMargin;

		for ($i=0; $i < count($links); $i++) {
		    $pdf->Image(BaseController::IMATGE_BUTTON, $x, $y, $wLink, $hLink , 'png', $links[$i]['link'], 
					'', false, 300, '', false, false, 0, false, false, false);
		    
			//$pdf->setPageMark(); 
			$pdf->MultiCell($wLink, $hLink, $links[$i]['text'], 0, 'C', 0, 0, $x, $y, true, 0, false, true, $hLink, 'M', true);

			if ($i == 2) {
				$y += $yOffset;
				$x = $x_titols + 5*$factor_w;
			} else {
				$x += $wLink + (2 * $buttonsMargin);
			}
		}
		
		
		// reset pointer to the last page
		$pdf->lastPage();
		
		return $pdf;
	}	
		
		
	protected function printLlicenciaT1pdf( $request, $llicencia ) {
	
		$yLinks = 70;
		$links = array(	array('text' => 'pòlissa', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_TECNOCAMPUS)),
						array('text'=> 'protocol', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_TECNOCAMPUS)),
						array('text' => 'comunicat', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_TECNOCAMPUS)));
						
		$pdf = $this->printDigitalTecnocampus( $llicencia, $links, $yLinks );
		
		return $pdf;
	}
	
	protected function printLlicenciaT2pdf( $request, $llicencia ) {
	
		$yLinks = 67;
		$links = array(	array('text' => 'pòlissa', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_TECNOCAMPUS)),
						array('text'=> 'protocol', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_TECNOCAMPUS)),
						array('text' => 'comunicat', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_TECNOCAMPUS)),
		                array('text'=> 'pòlissa busseig', 'link'=> $request->getUriForPath('/media/asseguranca/'.str_replace("%%YEAR%%", $this->getAnyPolissa($llicencia), BaseController::POLISSA_BUSSEIG))),
						array('text'=> 'protocol busseig', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_BUSSEIG)),
						array('text'=> 'comunicat busseig', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_BUSSEIG)));
		
	
		$pdf = $this->printDigitalTecnocampus( $llicencia, $links, $yLinks );
		
		return $pdf;
	}
	
	protected function printLlicenciaTMpdf( $request, $llicencia ) {
	    
	    $yLinks = 70;
	    $links = array(	array('text' => 'pòlissa', 'link'=> $request->getUriForPath('/media/asseguranca/'.str_replace("%%YEAR%%", $this->getAnyPolissa($llicencia), BaseController::POLISSA_BUSSEIG))),
	        array('text'=> 'protocol', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_BUSSEIG)),
	        array('text' => 'comunicat', 'link'=> $request->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_BUSSEIG)));
	    
	    $pdf = $this->printDigitalTecnocampus( $llicencia, $links, $yLinks );
	    
	    return $pdf;
	}
	
	private function printDigitalTecnocampus( $llicencia, $links, $yLinks ) {
		// Paper cordinates are calculated in this way: (inches * 72) where (1 inch = 25.4 mm)
		// Definir paper 13,3'' => 29cmx17cm (WxH) en 16:9
		// Definir paper 7'' => => 15cmx9cm (WxH) en 16:9

	    $factor_w = 86/150;    //  0.573
	    $factor_h = 54/90;     //  0.6
	    
		// Posicions
		$xTit = 0;
		$yTit =	18*$factor_h;		
		$yNom =	35*$factor_h;		
		$yDni =	40*$factor_h;		
		$yCad =	56.2*$factor_h;
		
		// Títols
		$x_titols = 11*$factor_w;

		// Links docs
		$x = $x_titols;
		$y = $yLinks*$factor_h;
		$yOffset = 10*$factor_h;
		$hLink = 6.8*$factor_h;
		$buttonsMargin = 3;
				
		$pdf = new TcpdfBridge('L', PDF_UNIT, 'BUSINESS_CARD_ISO7810', true, 'UTF-8', false);
				
		$pdf->init(array('author' => 'FECDAS',
						'title' => 'Llicència Tecnocampus FECDAS' . date("Y")));

		$pdf->setPrintFooter(false);
		$pdf->setPrintHeader(false);
				
		// zoom - layout - mode
		$pdf->SetDisplayMode('real', 'SinglePage', 'UseNone');
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(3, 3, 3);
		$pdf->SetAutoPageBreak 	(false, 3);
		//$pdf->SetMargins(0, 0, 0);
		//$pdf->SetAutoPageBreak 	(false, 0);
		$pdf->SetTextColor(255, 255, 255); 
			

		$pdf->AddPage('L', 'BUSINESS_CARD_ISO7810');
		
		$pdf->Image(BaseController::IMATGE_ANVERS_TECNOCAMPUS, 0, 0, 
		                $pdf->getPageWidth(), $pdf->getPageHeight() , 'jpg', '', '', false, 320, 
						'', false, false, 1, false, false, false);
		
		$parte = $llicencia->getParte();
		//$polissa = $parte->getTipus()->getPolissa();
		
		// Dades
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat();
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);
				
		$pdf->SetFont('dejavusans', 'B', 9, '', true);
		$pdf->setFontStretching(100);		
		$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

		$pdf->SetFont('dejavusans', 'B', 7);

		$pdf->writeHTMLCell(0, 0, $x_titols, $yNom, '<span style="font-size: small;">Nom: </span>'.$persona->getNomCognoms(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yDni, '<span style="font-size: small;">DNI/Passaport: </span>'.$persona->getDni(), 0, 0, false, true, 'L', true);
		
		$pdf->writeHTMLCell(0, 0, $x_titols, $yCad, '<span style="font-size: small;">Vàlida fins/Valid until: </span>'. $datacaduca->format('d/m/Y'), 0, 0, false, true, 'R', true);
		
		//$pdf->setFontSpacing(0.5);
    	//$pdf->setFontStretching(80);
		
		$pdf->SetTextColor(199,132,1);
		$pdf->SetFont('helvetica', 'B', 6, '', true);

		$margins = $pdf->getMargins();
		$width = $pdf->getPageWidth() - $margins['left'] - $margins['right']; 
		$wLink = $width/3 - 2 * $buttonsMargin;


		for ($i=0; $i < count($links); $i++) {
		    $pdf->Image(BaseController::IMATGE_BUTTON, $x, $y, $wLink, $hLink , 'png', $links[$i]['link'], 
					'', false, 320, '', false, false, 0, false, false, false);
			//$pdf->setPageMark(); 
			$pdf->MultiCell($wLink, $hLink, $links[$i]['text'], 0, 'C', 0, 0, $x, $y, true, 0, false, true, $hLink, 'M', true);

			if ($i == 2) {
				$y += $yOffset;
				$x = $x_titols;
			} else {
				$x += $wLink + (2 * $buttonsMargin);
			}
		}
		
		// reset pointer to the last page
		$pdf->lastPage();
			
		return $pdf;
	}


	protected function getTitolPlastic($parte, $datacaduca = null) {
		/*if ($parte == null) return '';
		$anyLlicencia = $parte->getDataalta()->format('Y');
		if ($datacaduca == null) $datacaduca = $parte->getDatacaducitat();
		$anyFinalLlicencia = $datacaduca->format('Y');
		$tipus = $parte->getTipus();
	
		$titolPlastic = mb_strtoupper($tipus->getTitol(), 'UTF-8');
	
		$titolPlastic = str_replace("__DESDE__", $anyLlicencia, $titolPlastic);
		$titolPlastic = str_replace("__FINS__", $anyFinalLlicencia, $titolPlastic);*/
	
		$tipus = $parte->getTipus();
		$titolPlastic = mb_strtoupper($tipus->getTitol(), 'UTF-8');
	
		if (strpos($titolPlastic, "__CLUB__") !== false) {
		    $titolPlastic = str_replace("__CLUB__", $parte->getClub()->getNom(), $titolPlastic);
		    
		    return $titolPlastic;
		}
		    
		if (strpos($titolPlastic, "__DESDE__-__FINS__") === false) {
			$titolPlastic = str_replace("__DESDE__", $parte->getDataalta()->format('Y'), $titolPlastic);
		} else {
			$titolPlastic = str_replace("__DESDE__-__FINS__", $parte->getCurs(), $titolPlastic);
		}
		
		return $titolPlastic;
	}
	
	protected function printLlicencies( $llicencies ) {
	
		// Printer EVOLIS PEBBLE 4 - ISO 7810, paper size CR80 BUSINESS_CARD_ISO7810 => 54x86 mm 2.13x3.37 in
		// Altres opcions BUSINESS_CARD_ES   55x85 mm ; 2.17x3.35 in ¿?
		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
		// Papers => 		/vendor/tcpdf/includes/tcpdf_static.php
		$format = \TCPDF_STATIC::getPageSizeFromFormat('BUSINESS_CARD_ISO7810');
		$pdf = new TcpdfBridge('L', PDF_UNIT, $format, true, 'UTF-8', false);
				
		$pdf->init(array('author' => 'FECDAS',
		    'title' => 'Llicència FECDAS ' . date("Y")));

		$pdf->setPrintFooter(false);
		$pdf->setPrintHeader(false);
				
		// zoom - layout - mode
		$pdf->SetDisplayMode('real', 'SinglePage', 'UseNone');
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(5, 5, 5);
		$pdf->SetAutoPageBreak 	(false, 5);
		//$pdf->SetMargins(0, 0, 0);
		//$pdf->SetAutoPageBreak 	(false, 0);
		$pdf->SetTextColor(0, 0, 0); 
			
		//$width = 86; //Original
		//$height = 54; //Original

		foreach ($llicencies as $llicencia) {
			$parte = $llicencia->getParte();
								
			// Add a page
			$pdf->AddPage('L', 'BUSINESS_CARD_ISO7810');
			if ($parte->getTipus()->getTemplate() == BaseController::TEMPLATE_GENERAL ||
			    $parte->getTipus()->getTemplate() == BaseController::TEMPLATE_ESCOLAR ||
			    $parte->getTipus()->getTemplate() == BaseController::TEMPLATE_ESCOLAR_SUBMARINISME) {
			        $this->printPlasticGeneral($pdf, $llicencia);
			}
			
			if ($parte->getTipus()->getTemplate() == BaseController::TEMPLATE_PESCA) {
			    $this->printPlasticPesca($pdf, $llicencia);
			}
				
			if ($parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_1 ||
				$parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_2 ||
			    $parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_MASTER) {
					//$this->printPlasticGeneral($pdf, $llicencia);
					$this->printPlasticTecnocampus($pdf, $llicencia);
			}
			$llicencia->setImprimir(0);
			$llicencia->setImpresa(1);
			$llicencia->setDataimpressio( new \DateTime() );
		}
		// reset pointer to the last page
		$pdf->lastPage();
			
		return $pdf;
	}

	protected function printPlasticGeneral($pdf, $llicencia) {
		// Posicions
		$xTit = 0;
		$yTit =	14.1 + 0.8;		
		$xCat = 0.0;
		$yCat =	23.0 - 3.5;
		$xNom = 7.3 + 2.0;
		$yNom =	30.6 - 2.8;		
		$xDni = 15.6 + 2.8;
		$yDni =	35.1 - 3.6;		
		$xNai = 16.8 + 4.6;
		$yNai =	39.6 - 3.8;	
		$xClu = 8.4 + 2.4;
		$yClu =	44.0 - 4.4;		
		$xTlf = 14.5 + 0.8;
		$yTlf =	45.0 - 1.4;		
		$xCad = 65.0 - 0.6;
		$yCad =	47.4 - 3.7;
		
		/*********** Alex Test  *************/
		
		/*$srcImatge = 'images/federativa_impressio_anvers_test.jpg';
		$width = 86; //Original
		$height = 54; //Original
		$pdf->Image($srcImatge, 0, 0, $width, $height , 'jpg', '', '', false, 320, '', false, false, 1, false, false, false);*/
		
		/*
		//$pdf->SetTextColor(255, 0, 0); 
		$pdf->setCellHeightRatio(1.1);
		// php  vendor/tcpdf/tools/tcpdf_addfont.php -i RobotoCondensed-Regular.ttf,RobotoCondensed-Italic.ttf
		// ls -l vendor/tcpdf/fonts

		//$fontname = TCPDF_FONTS::addTTFfont('/home/alex/Android/Sdk/platforms/android-23/data/fonts/DroidSans.ttf', 'OpenTypeUnicode', '', 32);
		//$pdf->SetFont($fontname, 'B', 10);	
		 */
		/*********** Alex Test Fi *************/
		
		$parte = $llicencia->getParte();
		$club = $parte->getClubparte();
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat();
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);

		// Treure la primera frase que ja està impresa
		$titolPlasticArray = explode(LF, $titolPlastic);
		array_shift($titolPlasticArray);
		$titolPlastic = implode(LF, $titolPlasticArray);
		
		//$pdf->SetFont('helvetica', 'B', 10, '', true);
		$pdf->SetFont('helvetica', 'B', 10);
		//$pdf->setFontSpacing(0.5);
    	$pdf->setFontStretching(90);
				
		$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

		$pdf->SetXY($xCat, $yCat);
		//$pdf->Cell(0, 0, $llicencia->getCategoria()->getCategoria(), 0, 1, 'L');
		$pdf->MultiCell(0,0,$llicencia->getCategoria()->getCategoria(),0,'C',false);
		
		//$pdf->SetFont('dejavusans', 'B', 8);
		$pdf->SetFont('helvetica', 'B', 9);
		$pdf->setFontStretching(100);

		$pdf->SetXY($xNom, $yNom);
		$pdf->Cell(0, 0, $persona->getNomCognoms(), 0, 1, 'L', 0, '', 3);

		$pdf->SetXY($xDni, $yDni);
		$pdf->Cell(0, 0, $persona->getDni(), 0, 1, 'L');

		$pdf->SetXY($xNai, $yNai);
		$pdf->Cell(0, 0, $persona->getDatanaixement()->format('d/m/Y'), 0, 1, 'L');
				
		$pdf->SetXY($xClu, $yClu);
		// $w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M'
		// $stretch 0  no stretch
		//          1  scaling        OK
		//          2  force scaling
		//          3  spacing        OK
		//          4  force spacing
		$pdf->Cell(0, 0, $club->getNom(), 0, 1, 'L', 0, '', 3);

		$pdf->SetXY($xTlf, $yTlf);
		$pdf->Cell(0, 0, $club->getTelefonFormat(), 0, 1, 'L');
		
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->setFontStretching(90);
		
		$pdf->SetXY($xCad, $yCad);
		$pdf->Cell(0, 0, $datacaduca->format('d/m/Y'), 0, 1, 'L');
		
	}
	
	protected function printPlasticPesca($pdf, $llicencia) {
	    // Posicions
	    $xTit = 0;
	    $yTit =	15.2;
	    
	    $xCat = 0.0;
	    $yCat =	23.0;
	    $xNom = 7.3;
	    $yNom =	30.6 + 0.9;
	    $xDni = 15.6;
	    $yDni =	35.1 + 1.0;
	    $xNai = 16.8;
	    $yNai =	39.6 + 1.0;
	    $xClu = 8.4;
	    $yClu =	44.0 + 1.6;
	    $xCad = 66.0;
	    $yCad =	49.0 - 0.2;
	    
	    /*********** Alex Test  *************/
	    
	    /*$srcImatge = 'images/federativa_impressio_anvers_botiga_test.jpg';
	    $width = 86; //Original
	    $height = 54; //Original
	    $pdf->Image($srcImatge, 0, 0, $width, $height , 'jpg', '', '', false, 320, '', false, false, 1, false, false, false);*/
	    
	    /*
	     //$pdf->SetTextColor(255, 0, 0);
	     $pdf->setCellHeightRatio(1.1);
	     // php  vendor/tcpdf/tools/tcpdf_addfont.php -i RobotoCondensed-Regular.ttf,RobotoCondensed-Italic.ttf
	     // ls -l vendor/tcpdf/fonts
	     
	     //$fontname = TCPDF_FONTS::addTTFfont('/home/alex/Android/Sdk/platforms/android-23/data/fonts/DroidSans.ttf', 'OpenTypeUnicode', '', 32);
	     //$pdf->SetFont($fontname, 'B', 10);
	     */
	    /*********** Alex Test Fi *************/
	    
	    $parte = $llicencia->getParte();
	    $club = $parte->getClubparte();
	    $persona = $llicencia->getPersona();
	    if ( $persona == null) return;
	    
	    $datacaduca = $parte->getDatacaducitat();
	    $titolPlastic = $this->getTitolPlastic($parte, $datacaduca);
	    
	    //$pdf->SetFont('helvetica', 'B', 10, '', true);
	    $pdf->SetFont('helvetica', 'B', 10);
	    //$pdf->setFontSpacing(0.5);
	    $pdf->setFontStretching(90);
	    
	    $pdf->SetXY($xTit, $yTit);
	    $pdf->MultiCell(0,0,$titolPlastic,0,'C',false);
	    
	    $pdf->SetXY($xCat, $yCat);
	    $pdf->Cell(0, 0, $llicencia->getCategoria()->getCategoria(), 0, 1, 'C');
	    
	    //$pdf->SetFont('dejavusans', 'B', 8);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->setFontStretching(100);

	    $pdf->SetXY($xNom, $yNom);
	    $pdf->Cell(0, 0, $persona->getNomCognoms(), 0, 1, 'L', 0, '', 3);
	    
	    $pdf->SetXY($xDni, $yDni);
	    $pdf->Cell(0, 0, $persona->getDni(), 0, 1, 'L');

	    $pdf->SetXY($xNai, $yNai);
	    $pdf->Cell(0, 0, $persona->getDatanaixement()->format('d/m/Y'), 0, 1, 'L');
	    
	    $pdf->SetFont('helvetica', 'B', 8);
	    $pdf->setFontStretching(90);

	    $pdf->SetXY($xClu, $yClu);
	    $pdf->Cell(0, 0, $club->getNom(), 0, 1, 'L', 0, '', 3);
	    
	    /*$pdf->SetXY($xTlf, $yTlf);
	    $pdf->Cell(0, 0, $club->getTelefon(), 0, 1, 'L');*/
	    
	    $pdf->SetXY($xCad, $yCad);
	    $pdf->Cell(0, 0, $datacaduca->format('d/m/Y'), 0, 1, 'L');
	    
	}
	
	protected function printPlasticTecnocampus($pdf, $llicencia) {
		// Posicions
		$xPol = 0;
		$yPol =	2;		
		
		$xTit = 0;
		$yTit =	12;		
		$xNom = 10;
		$yNom =	27.4;		
		$xDni = 18+5;
		$yDni =	32.1;		
		$xCat = 20.5+6;
		$yCat =	36.7;		
		$xNai = 19.5+7;
		$yNai =	41.1;		
		$xClu = 12;
		$yClu =	45.6;		
		$xTlf = 15+4;
		$yTlf =	49.1+0.5;		
		$xCad = 61+7;
		$yCad =	49.1+0.5;
		
		$parte = $llicencia->getParte();
		$club = $parte->getClubparte();
		$polissa = $parte->getTipus()->getPolissa();
		
		// Dades
		$pdf->SetFont('helvetica', 'B', 9, '', true);
		$pdf->setFontStretching(90);		
		$pdf->SetXY($xPol, $yPol);
		$pdf->MultiCell(0,0,'Número de pòlissa: '.$polissa,0,'C',false);		
		
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat();
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);
				
		$pdf->SetFont('helvetica', 'B', 10, '', true);
		$pdf->setFontStretching(100);		
		$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

		$pdf->SetFont('dejavusans', 'B', 8);

		$pdf->SetXY($xNom, $yNom);
		$pdf->Cell(0, 0, $persona->getNomCognoms(), 0, 1, 'L', 0, '', 3);

		$pdf->SetXY($xDni, $yDni);
		$pdf->Cell(0, 0, $persona->getDni(), 0, 1, 'L');

		$pdf->SetXY($xCat, $yCat);
		$pdf->Cell(0, 0, $llicencia->getCategoria()->getCategoria(), 0, 1, 'L');
				
		$pdf->SetXY($xNai, $yNai);
		$pdf->Cell(0, 0, $persona->getDatanaixement()->format('d/m/Y'), 0, 1, 'L');
				
		$pdf->SetXY($xClu, $yClu);
		$pdf->Cell(0, 0, $club->getNom(), 0, 1, 'L', 0, '', 3);

		$pdf->SetFont('dejavusans', 'B', 7);
		$pdf->SetXY($xTlf, $yTlf);
		$pdf->Cell(0, 0, $club->getTelefon(), 0, 1, 'L');
				
		$pdf->SetXY($xCad, $yCad);
		$pdf->Cell(0, 0, $datacaduca->format('d/m/Y'), 0, 1, 'L');

		// Títols
		$xTit = 0;
		$yTit =	12;		
		$y_offset = 0.3;
		$xCad_tit = 37;
		$x_titols = 0.5;

		$pdf->SetFont('helvetica', 'B', 10, '', true);
				
		$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

		$pdf->SetFont('dejavusans', 'B', 7);
		//$pdf->setFontSpacing(0.5);
    	//$pdf->setFontStretching(80);

		$pdf->SetXY($x_titols, $yNom+$y_offset);
		$pdf->Cell(0, 0, 'Nom:', 0, 1, 'L');

		$pdf->SetXY($x_titols, $yDni+$y_offset);
		$pdf->Cell(0, 0, 'DNI/Passaport:', 0, 1, 'L');

		$pdf->SetXY($x_titols, $yCat+$y_offset);
		$pdf->Cell(0, 0, 'Categoria/Nivell:', 0, 1, 'L');
				
		$pdf->SetXY($x_titols, $yNai+$y_offset);
		$pdf->Cell(0, 0, 'Data Naixement:', 0, 1, 'L');
				
		$pdf->SetXY($x_titols, $yClu+$y_offset);
		$pdf->Cell(0, 0, 'Entitat:', 0, 1, 'L');

		$pdf->SetXY($x_titols, $yTlf+$y_offset-0.3);
		$pdf->Cell(0, 0, 'Telf. Entitat:', 0, 1, 'L');
				
		$pdf->SetXY($xCad_tit, $yCad+$y_offset-0.3);
		$pdf->Cell(0, 0, 'Vàlida fins/Valid until:', 0, 1, 'L');
		
	}
	
	protected function validarDadesPersona($persona, $checkdni = true, $form = null) {
	    if ($persona == null || $persona->getClub() == null) throw new \Exception("Les dades no són correctes");
	    
	    $club = $persona->getClub();
	    
	    if ($persona->getNom() == null || $persona->getNom() == "") {
	        if ($form != null) $form->get('nom')->addError(new FormError('Falta el nom'));
	        throw new \Exception("Cal indicar el nom");
	    }
	    
	    if ($persona->getCognoms() == null || $persona->getCognoms() == "") {
	        if ($form != null) $form->get('cognoms')->addError(new FormError('Falten els cognoms'));
	        throw new \Exception("Cal indicar els cognoms");
	    }
	    
	    if ($persona->getDni() == "") {
	        if ($form != null) $form->get('dni')->addError(new FormError('Falta el DNI'));
	        throw new \Exception("Cal indicar el DNI");
	    }
	    
	    $em = $this->getDoctrine()->getManager();
	    
	    $nacio = $em->getRepository('FecdasBundle:EntityNacio')->findOneByCodi($persona->getAddrnacionalitat());
	    
	    if ($nacio == null) $persona->setAddrnacionalitat('ESP');
	    
	    
	    if ($checkdni) {
	        /* Només validar DNI nacionalitat espanyola */
	        $dnivalidar = $persona->getDni();
	        /* Tractament fills sense dni, prefix M o P + el dni del progenitor */
	        if ( substr ($dnivalidar, 0, 1) == 'P' or substr ($dnivalidar, 0, 1) == 'M' ) $dnivalidar = substr ($dnivalidar, 1,  strlen($dnivalidar) - 1);
	        
	        if (BaseController::esDNIvalid($dnivalidar) != true) {
	            if ($form != null) $form->get('dni')->addError(new FormError('DNI incorrecte'));
	            throw new \Exception('El DNI és incorrecte ');
	        }
	    }
	    
	    /* Check persona amb dni no repetida al mateix club */
	    if ($persona->getId() == 0) {
	        
	        $metapersona = $persona->getMetapersona();
	        
	        $personaClub = $metapersona->getPersonaClub($club);
	        
	        if ($personaClub != null && $personaClub->getId() > 0) throw new \Exception("Existeix una altra persona al club amb aquest DNI");
	    }
	    
	        
	    $currentMin = $this->getCurrentDate();
	    $currentMin->sub(new \DateInterval('P'.BaseController::EDAT_MINIMA.'Y')); // -4 anys
	        
	    if ($persona->getDatanaixement() == null || $persona->getDatanaixement() == "") {
	        if ($form != null) $form->get('datanaixement')->addError(new FormError('Falta la data'));
	        throw new \Exception('Cal indicar la data de naixement');
	    }
	        
	    if ($persona->getDatanaixement()->format('Y-m-d') > $currentMin->format('Y-m-d')) {
	        if ($form != null) $form->get('datanaixement')->addError(new FormError('Data incorrecte'));
	        throw new \Exception('La data de naixement és incorrecte');
	    }
	        
	    if ($persona->getSexe() != BaseController::SEXE_HOME && $persona->getSexe() != BaseController::SEXE_DONA)
	        throw new \Exception('Manca indicar el sexe');
	        
	    if ($persona->getTelefon1() > BaseController::MAX_TELEFON) {
	        if ($form != null) $form->get('telefon1')->addError(new FormError('Telèfon incorrecte'));
	        throw new \Exception("El número de telèfon no és correcte");
	    }
	    if ($persona->getTelefon2() > BaseController::MAX_TELEFON) {
	        if ($form != null) $form->get('telefon2')->addError(new FormError('Mòbil incorrecte'));
	        throw new \Exception("El número de mòbil no és correcte");
	    }
	        
	    /*if ($persona->getId() == 0 &&
	         ($persona->getTelefon1() == null || $persona->getTelefon1() == 0 || $persona->getTelefon1() == "") &&
	         ($persona->getTelefon2() == null || $persona->getTelefon2() == 0 || $persona->getTelefon2() == "") &&
	         ($persona->getMail() == null || $persona->getMail() == "")) throw new \Exception("Cal indicar alguna dada de contacte");*/
	        
	    /*if ($persona->getId() == 0 &&
	         ($persona->getMail() == null || $persona->getMail() == "")) throw new \Exception("Cal indicar l'adreça de correu electrònica");*/
	        
	    if ($persona->getMail() == "") $persona->setMail(null);
	        
	    if ($persona->getMail() != null) {
	        $strMails = $this->validateMails($persona->getMails());
	        $persona->setMail($strMails);
	    }
	        
	        
	    // Canviar format Nom i COGNOMS
	    // Specials chars ñ, à, etc...
	    $persona->setCognoms(mb_strtoupper($persona->getCognoms(), "utf-8"));
	    $persona->setNom(mb_convert_case($persona->getNom(), MB_CASE_TITLE, "utf-8"));
	        
	    $persona->setDatamodificacio($this->getCurrentDate());
	        
	}
	
	protected function crearComanda($data, $comentaris = '', $club = null, $factura = null) {
		if ($data == null) $data = $this->getCurrentDate();
	
		if ($club == null) $club = $this->getCurrentClub();
		
		$em = $this->getDoctrine()->getManager();
	
		$maxNumComanda = $this->getMaxNumEntity($data->format('Y'), BaseController::COMANDES) + 1;

		$comanda = new EntityComanda($maxNumComanda, $factura, $club, $comentaris);
		
		$em->persist($comanda);

		if ($factura != null) {
			$factura->setComanda($comanda);
			$comanda->setFactura($factura);
		}
	
		return $comanda;
	}

	protected function crearComandaDuplicat($comentaris = '', $club = null, $factura = null) {
	    $data = $this->getCurrentDate();
	    if ($club == null) $club = $this->getCurrentClub();
	    
	    $em = $this->getDoctrine()->getManager();
	    
	    $maxNumComanda = $this->getMaxNumEntity($data->format('Y'), BaseController::COMANDES) + 1;
	    
	    $duplicat = new EntityDuplicat($maxNumComanda, $factura, $club, $comentaris);
	    $duplicat->setDatapeticio($data);
	    
	    $em->persist($duplicat);
	    
	    return $duplicat;
	}
	
	protected function crearComandaParte($data, $tipus = null, $club = null, $comentaris = '', $factura = null) {
	    if ($data == null) $data = $this->getCurrentDate();
	    if ($tipus == null) $tipus = $tipus = $this->getDoctrine()->getRepository('FecdasBundle:EntityParteType')->find(1); // Per defecte 1
	    
	    if ($club == null) $club = $this->getCurrentClub();
	    
	    $em = $this->getDoctrine()->getManager();
	    
	    $maxNumComanda = $this->getMaxNumEntity($this->getCurrentDate()->format('Y'), BaseController::COMANDES) + 1;
	    
	    $parte = new EntityParte($maxNumComanda, $factura, $club, $comentaris);
	    $parte->setDataalta($data);
	    $parte->setTipus($tipus);
	    if ($club != null && $club->pendentPagament()) $parte->setPendent(true);
	    
	    $em->persist($parte);
	    
	    return $parte;
	}
	
	protected function addComandaDetall($comanda, $producte = null, $unitats = 1, $descomptedetall = 0, $anotacions = '') {
		if ($comanda == null) return null;
		$em = $this->getDoctrine()->getManager();

		$detall = $comanda->getDetallComanda($producte);
		if ($detall == null) {
			
			$detall = new EntityComandaDetall($comanda, $producte, $unitats, $descomptedetall, $anotacions);
			
			$comanda->addDetall($detall);// Sempre afegir un detall si comanda nova
			
			$em->persist($detall);
			
		} else {
			
			//$detall->setUnitats($detall->getUnitats() + 1);
			$detall->setUnitats($detall->getUnitats() + $unitats);
			
			if ($anotacions != '') $detall->setAnotacions($anotacions);
		}

		$comanda->setDatamodificacio($this->getCurrentDate());
		return $detall;
	}

	protected function addDuplicatDetall($duplicat) {
	    if ($duplicat == null || $duplicat->getCarnet() == null) return null;
	    
	    $producte = $duplicat->getCarnet()->getProducte();
	    
	    if ($producte == null) return null;
	    
	    $anotacions = '1x'.$producte->getDescripcio();
	    
	    $detall = $this->addComandaDetall($duplicat, $producte, 1, 0, $anotacions);
	    
	    if ($detall != null) $duplicat->setComentaris($duplicat->getComentariDefault());
	    
	    return $detall;
	}
	
	protected function addTransportToComanda($comanda) {
	    $cartcheckout = $this->get('fecdas.cartcheckout');
	    
	    $pesComanda = $cartcheckout->getPesComandaCart();
	    
	    $producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->findOneByCodi(BaseController::PRODUCTE_CORREUS);
	    
	    if ($producte == null) throw new \Exception("No es pot afegir el transport a la comanda, poseu-vos en contacte amb la Federació");
	    $unitats = $cartcheckout->getUnitatsTarifaTransport();
	    
	    $anotacions = $producte->getDescripcio().' '.$pesComanda.'g';
	    $detall = $this->addComandaDetall($comanda, $producte, 1, 0, $anotacions);
	    $detall->setPreuunitat($producte->getCurrentPreu() * $unitats);
	    
	    return $detall;
	}
	
	
	protected function addParteDetall($parte, $llicencia, $anotacions = '') {
		// Canviar factura. No hi ha rebut perquè sinó estaria consolidat el parte
		$factura = $parte->getFactura();

		if ($parte == null || $llicencia == null || $factura == null) throw new \Exception('Dades incorrectes');
		
		
		if ($this->isCurrentAdmin() != true) {
			// Clubs
			if ($parte->comandaConsolidada() == true) throw new \Exception('No es poden fer canvis sobre les llicències d\'aquesta llista, només anul·lar-les');
			
		} else {
			// Admins
			if ($parte->comandaPagada() == true) throw new \Exception('La llista està pagada només es poden fer anul·lacions');
			
			if ($factura->getComptabilitat() != null) throw new \Exception('La llista s\'ha enviat a comptabilitat. No es poden afegir les llicències només anul·lar-les');
		}
		
		// Comprovar si existeix 
		/*$existeix = $parte->getLlicenciaById($llicencia->getId());
		if ($existeix == null) $parte->addLlicencia($llicencia);*/
		
		$producte = ($llicencia->getCategoria() != null?$llicencia->getCategoria()->getProducte():null);
		
		if ($producte == null) return null;
		
		if ($anotacions == '') {
			$numCat = $parte->getNumLlicenciesCategoria($llicencia->getCategoria()->getSimbol());
			$anotacions = $numCat.'x'.$producte->getDescripcio();  
		}
		
		$detall = $this->addComandaDetall($parte, $producte, 1, 0, $anotacions);

		if ($detall != null) $parte->setComentaris($parte->getComentaris().' '.$parte->getComentariDefault());
	
		$import = $parte->getTotalDetalls();
		$iva = $parte->getTotalIVADetalls();  // 0
        $club = $parte->getClub(); 		

        if ($club != null && $club->pendentPagament() && $club->getSaldo() >= $import) $parte->setPendent(false);
		    // Revisar saldo clubs pagament immediat, si tenen saldo el parte no es queda pendent, va contra el saldo del club.
		
		// Actualitzar import i detalls factura
		$factura->setImport($import);
		$factura->setIva($iva);
				
		$detalls = $parte->getDetallsAcumulats();
		//$factura->setDetalls(json_encode($detalls, JSON_UNESCAPED_UNICODE)); // Desar estat detalls a la factura
		$factura->setDetalls(json_encode($detalls)); // Desar estat detalls a la factura
		$factura->setConcepte($parte->getConcepteComanda());
	
		return $detall;
	}

	protected function updateParteDetall($parte, $llicencia, $llicenciaOriginal) {
		$factura = $parte->getFactura();	
			
		if ($parte == null || $llicencia == null || $factura == null) throw new \Exception('No es pot fer la modificació, les dades són incorrectes 1');
		
		if ($this->isCurrentAdmin() != true) {
			// Clubs
			if ($parte->comandaConsolidada() == true) throw new \Exception('No es poden fer canvis sobre les llicències d\'aquesta llista, només anul·lar-les');
			
		} else {
			// Admins
			if ($parte->comandaPagada() == true) throw new \Exception('La llista està pagada només es poden fer anul·lacions');
			
			if ($factura->getComptabilitat() != null) throw new \Exception('La llista s\'ha enviat a comptabilitat. No es poden afegir les llicències només anul·lar-les');
		}
		
		//$personaOriginal = clone $llicencia->getPersona();
		//$categoriaOriginal = clone $llicencia->getCategoria();
		//$personaOriginal = $llicenciaOriginal->getPersona();
		//$categoriaOriginal = $llicenciaOriginal->getCategoria();

		$categoriaOriginal = $llicenciaOriginal->getCategoria();
		$categoriaActual = $llicencia->getCategoria();
		
		if ($categoriaOriginal == null || $categoriaActual == null) throw new \Exception('No es pot fer la modificació, dades incorrectes 2');
		
		if ($categoriaOriginal->getSimbol() != $categoriaActual->getSimbol()) {
			// Canvi de categoria, actualitzar comanda detall i factura
			
			$producteOriginal = $categoriaOriginal->getProducte();
			$producteActual = $categoriaActual->getProducte();
			
			if ($producteOriginal == null || $producteActual == null) throw new \Exception('No es pot fer la modificació, dades incorrectes 3');
			
			// Afegir una llicència nova
			$numCat = $parte->getNumLlicenciesCategoria($categoriaActual->getSimbol());
			$anotacions = $numCat.'x'.$producteActual->getDescripcio();  
			
			$detall = $this->addComandaDetall($parte, $producteActual, 1, 0, $anotacions);
			
			if ($detall == null) throw new \Exception('No es pot fer la modificació, dades incorrectes 4 '.$producteActual->getId());
			
			// Treure la llicència original
			$numCat = $parte->getNumLlicenciesCategoria($categoriaOriginal->getSimbol());
			$anotacions = $numCat.'x'.$producteOriginal->getDescripcio();  
			
			
			$detallBaixa = $this->removeComandaDetall($parte, $producteOriginal, 1);
			
			if ($detallBaixa == null) throw new \Exception('No es pot fer la modificació, dades incorrectes 5 '.$producteOriginal->getId());
			
			$parte->setComentaris($parte->getComentariDefault());
			
			// Canviar factura. No hi ha rebut perquè sinó estaria consolidat el parte
			
				 
			// Actualitzar import i detalls factura
			if ($factura != null) {
				$factura->setImport($parte->getTotalDetalls());
				$factura->setIva($parte->getTotalIVADetalls());  // 0
					
				$detalls = $parte->getDetallsAcumulats();
				//$factura->setDetalls(json_encode($detalls, JSON_UNESCAPED_UNICODE)); // Desar estat detalls a la factura
				$factura->setDetalls(json_encode($detalls)); // Desar estat detalls a la factura
				$factura->setConcepte($parte->getConcepteComanda());
			}
		}
	}

    protected function baixaComanda($comanda, $dataFacturacio = null) {

        if ($comanda->esParte()) {
            $parte = $comanda;
            $llicenciesBaixa = array();
            foreach ($parte->getLlicencies() as $llicencia) {
                if (!$llicencia->esBaixa()) {
                    $llicenciesBaixa[] = $llicencia;
                }
            }

            $this->removeParteDetalls($parte, $llicenciesBaixa, $dataFacturacio); // Crea factura si escau (comanda consolidada)
            
        } else {
			$originalDetalls = new \Doctrine\Common\Collections\ArrayCollection();
            $detallsBaixa = array();
            $extra = array();
            foreach ($comanda->getDetalls() as $detall) {
            	$originalDetalls->add(clone $detall);
                if (!$detall->esBaixa()) {
                    $detallsBaixa[] = $this->removeComandaDetall($comanda, $detall->getProducte(), $detall->getUnitats());
                }
            }
            
            if (count($detallsBaixa) > 0) {
                $this->crearFacturaRebutAnulacio($comanda, $detallsBaixa, $dataFacturacio, $extra); 
				// Gestionar stock
                $this->registreStockComanda($comanda, $originalDetalls);
            }
        }

        $comanda->setDatamodificacio(new \DateTime());
        $comanda->setDatabaixa(new \DateTime());
        
    }


	protected function removeParteDetalls($parte, $llicencies, $dataFacturacio = null) {
		if ($parte == null || !is_array($llicencies) || count($llicencies) == 0) throw new \Exception('Dades incorrectes');

		$current = $this->getCurrentDate();
		$parte->setDatamodificacio($current);		
		
		$detallsBaixa = array();
		$extra = array();
		foreach ($llicencies as $llicencia) {
		
			// Persistència
			$llicencia->setDatamodificacio($current);
			$llicencia->setDatabaixa($current);
			
			// Baixa partes sense llicències
			//if ($parte->getNumLlicencies() == 0) $parte->setDatabaixa($current);

			$detallBaixa = $this->removeComandaDetall($parte, $llicencia->getCategoria()->getProducte(), 1);
		
			if ($detallBaixa != null) {
				//$codi = $llicencia->getCategoria()->getProducte()->getCodi();
				$id = $llicencia->getCategoria()->getProducte()->getId();
				$nomLlicencia = $llicencia->getPersona()->getNomCognoms();
							
				/*if (!isset($detallsBaixa[$codi]) && !isset($extra[$codi])) {
					$detallsBaixa[$codi] = $detallBaixa;
					$extra[$codi] = array( $nomLlicencia );
				} else {
					$detallsBaixa[$codi]->setUnitats($detallsBaixa[$codi]->getUnitats() - 1);
					$extra[$codi][] = $nomLlicencia;
				}*/
				if (!isset($detallsBaixa[$id]) && !isset($extra[$id])) {
					$detallsBaixa[$id] = $detallBaixa;
					$extra[$id] = array( $nomLlicencia );
				} else {
					$detallsBaixa[$id]->setUnitats($detallsBaixa[$id]->getUnitats() - 1);
					$extra[$id][] = $nomLlicencia;
				}			
			} 	
		}
		
		$prefix = ''; // Mantenir prefix comentaris i actualitzar totals llicències
		$pos = strpos($parte->getComentaris(), ':');
		if ($pos !== false) {
			$prefix = substr($parte->getComentaris(), 0, $pos);
		}
		
		$parte->setComentaris($prefix.': '.$parte->getComentariDefault());
		
		if ($parte->comandaConsolidada() != true) {
			// Canviar factura. No hi ha rebut perquè sinó estaria consolidat el parte
			$factura = $parte->getFactura();
			// Partes no consolidats
			if ($parte->getNumDetalls() == 0) {
				$em = $this->getDoctrine()->getManager();
				$factura->setImport(0); // Actualitza saldo club
				$factura->setIva(0);
				$factura->setComanda(null);
				$parte->setFactura(null);
				//$parte->setDatabaixa($current);
				$em->remove($factura);
				//$em->flush();
			}
			 
			// Actualitzar import i detalls factura
			if ($factura != null) {
				$factura->setImport($parte->getTotalDetalls());
				$factura->setIva($parte->getTotalIVADetalls());  // 0
				
				$detalls = $parte->getDetallsAcumulats();
				//$factura->setDetalls(json_encode($detalls, JSON_UNESCAPED_UNICODE)); // Desar estat detalls a la factura
				$factura->setDetalls(json_encode($detalls)); // Desar estat detalls a la factura
				$factura->setConcepte($parte->getConcepteComanda());
			}
			
		} else {
			// Consolidada, crear factura anul·lació					
			if (count($detallsBaixa) == 0) throw new \Exception('No ha estat possible esborrar les llicències. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
		
			$this->crearFacturaRebutAnulacio($parte, $detallsBaixa, $dataFacturacio, $extra);
		}		
	}


	protected function removeComandaDetall($comanda, $producte = null, $unitats = 1) {
		if ($comanda == null) return null;
		
		$detall = $comanda->getDetallComanda($producte);

		if ($detall == null) throw new \Exception('No s\'ha pogut realitzar aquesta acció ' );

		$restants = $detall->getUnitats() - $unitats;
		if ($restants <= 0) {
			$restants = 0;  // Baixa detall
			$unitats = $detall->getUnitats();
		}
		
		$detallBaixa = clone $detall;
		$detallBaixa->setUnitats($unitats * (-1));
		$detallBaixa->setUnitatsBaixa(0);
		$detall->setUnitats($restants);
		$detall->setUnitatsBaixa($detall->getUnitatsbaixa() + $unitats);
		$detall->setDatamodificacio($this->getCurrentDate());
		$detall->setAnotacions($restants.'x'.($producte != null?$producte->getDescripcio():''));
		
		$comanda->setDatamodificacio($this->getCurrentDate());

		if ($comanda->getNumDetalls() == 0) $comanda->setDatabaixa($this->getCurrentDate());

		return $detallBaixa;
	}
	
	protected function crearFacturaRebutAnulacio($comanda, $detalls, $datafactura = null, $extra = '') {

	    if ($comanda == null || !is_array($detalls) || count($detalls) == 0) throw new \Exception('No es pot crear la factura. Contacti amb l\'administrador');
		
		$current = $this->getCurrentDate();   // Per defecte data actual
		$datafacturaComanda = $comanda->getFactura()->getDatafactura();
		if ($datafactura == null) $datafactura = $current;
		
		if ($datafactura->format('Y-m-d') < $datafacturaComanda->format('Y-m-d')) $datafactura = $datafacturaComanda; // Baixa amb data igual o posterior
		
		$em = $this->getDoctrine()->getManager();

		$concepte = 'Anul·lació. ';
		$detallsFactura = array();
		foreach ($detalls as $detall) {
			$producte = $detall->getProducte();
			$unitats = $detall->getUnitats();
			
			// Calcular des de la comanda  
			//$import += $detall->getTotal(true);
			//$iva += $detall->getIva(true);
			
			$concepte .= $unitats.'x'.$producte->getDescripcio().' ';
			
			//$detallsFactura[$producte->getCodi()] = $detall->getDetallsArray(true);
			//$detallsFactura[$producte->getId()] = $detall->getDetallsArray(true);
			$detallsFactura[$producte->getId()] = $detall->getDetallsArray();
	
			if ($extra != '' && is_array($extra) && isset($extra[$producte->getId()])) {
			//if ($extra != '' && is_array($extra) && isset($extra[$producte->getCodi()])) {
				//$detallsFactura[$producte->getCodi()]['extra'] = $extra[$producte->getCodi()];
				$detallsFactura[$producte->getId()]['extra'] = $extra[$producte->getId()];
			}
		}

		$totalNet = round(BaseController::getTotalNetDetalls($detallsFactura),2); 
		$iva = round(BaseController::getTotalIVADetalls($detallsFactura),2);
		$import = round($totalNet + $iva, 2);
		
		// Revisar data factura és posterior a $data i posar data factura a la factura d'anul·lació
		/*$datafactura = $data;
		$persist = false;
		if ($comanda->getFactura() != null) {
			$datafacturaoriginal = $comanda->getFactura()->getDatafactura();
			if ($datafacturaoriginal->format('Y-m-d') > $datafactura->format('Y-m-d')) {
				// Data futura
				$datafactura = $datafacturaoriginal;
				$maxNumFactura = $this->getMaxNumEntity($datafactura->format('Y'), BaseController::FACTURES) + 1;
				$persist = true;
				
			} else {
				// Data passada. Any passat data factura a 31-12
				if ($datafacturaoriginal->format('Y') < $datafactura->format('Y')) {
					$datafactura = \DateTime::createFromFormat('Y-m-d H:i:s', $datafacturaoriginal->format('Y') . "-12-31 00:00:00");
					$maxNumFactura = $this->getMaxNumEntity($datafactura->format('Y'), BaseController::FACTURES) + 1;
					$persist = true;
				}
			}
		} */

		$maxNumFactura = $this->getMaxNumEntity($datafactura->format('Y'), BaseController::FACTURES) + 1;

		$factura = new EntityFactura($datafactura, $maxNumFactura, $comanda, $import, $iva, $concepte, $detallsFactura, $this->getIbanGeneral());
		
		$em->persist($factura);
		
		$maxNumFactura++;
				
		$comanda->addFacturaanulacio($factura);
		$factura->setComandaanulacio($comanda);
		$factura->setComanda(null);
		
		/*if ($comanda->comandaPagada() && $comanda->getRebut() != null) {
		    // Crear rebut import negatiu
		    $datapagament = $current;
		    $rebut = $comanda->getRebut();
		    if ($datapagament->format('Y-m-d') < $comanda->getRebut()->getDatapagament()->format('Y-m-d')) $datapagament = $comanda->getRebut()->getDatapagament(); // Baixa amb data igual o posterior
		    
		    $rebutanulacio = $this->crearIngres($datapagament, $rebut->getTipuspagament(), $comanda->getClub(), $import, $rebut->getDadespagament(), 'Rebut factura anul·lació '.$factura->getNumFactura());

		    $rebutanulacio->setComandaanulacio($comanda);
		    $comanda->addrebutsanulacions($rebutanulacio);
		}*/

		$comanda->setDatamodificacio(new \DateTime());
		
		//if ($persist == true) $em->flush();	// Si d'ha canviat el num factura	
	}
	
	protected function consultaStockClubPerProducteData($producte, $club, $fins = null, $baixes = false) {
	    $em = $this->getDoctrine()->getManager();
	    
	    if ($producte == null || $club == null) return 0;
	    
	    $strQuery  = " SELECT SUM(s.unitats) FROM FecdasBundle\Entity\EntityStock s ";
	    $strQuery .= " WHERE s.tipus = 'E'";
	    $strQuery .= " AND s.club = :club";
	    $strQuery .= " AND s.producte = :producte";
	    if ($fins != null) $strQuery .= " AND s.dataregistre <= :fins";
	    if (!$baixes) $strQuery .= " AND s.databaixa IS NULL";
	    
	    $query = $em->createQuery($strQuery);
	    
	    $query->setParameter('club', $club->getCodi());
	    $query->setParameter('producte', $producte->getId());
	    if ($fins != null) $query->setParameter('fins', $fins->format('Y-m-d'));
	    
	    $result = $query->getSingleScalarResult();
	    $total = $result == null?0:$result;
	    
	    $strQuery  = " SELECT SUM(s.unitats) FROM FecdasBundle\Entity\EntityStock s ";
	    $strQuery .= " WHERE s.tipus = 'S'";
	    $strQuery .= " AND s.club = :club";
	    $strQuery .= " AND s.producte = :producte";
	    if ($fins != null) $strQuery .= " AND s.dataregistre <= :fins";
	    if (!$baixes) $strQuery .= " AND s.databaixa IS NULL";
	    
	    $query = $em->createQuery($strQuery);
	    
	    $query->setParameter('club', $club->getCodi());
	    $query->setParameter('producte', $producte->getId());
	    if ($fins != null) $query->setParameter('fins', $fins->format('Y-m-d'));
	    
	    $result = $query->getSingleScalarResult();
	    $total -= $result == null?0:$result;
	    
	    return $total;
	}

	protected function consultaStock($idproducte, $club, $baixes = true, $desde = null, $order = 'ASC') {
		$em = $this->getDoctrine()->getManager();
	
		$codi = $club != null?$club->getCodi():'';
	
		$strQuery  = " SELECT s FROM FecdasBundle\Entity\EntityStock s ";
		$strQuery .= " WHERE 1 = 1 ";
		$strQuery .= " AND s.club = :codi";
		if ($idproducte > 0) $strQuery .= " AND s.producte = :idproducte ";
		if (! $baixes) $strQuery .= " AND s.databaixa IS NULL ";
		if ($desde != null) $strQuery .= " AND s.dataregistre >= :desde ";
		$strQuery .= " ORDER BY s.dataregistre ".$order.", s.id ".$order." ";
		$query = $em->createQuery($strQuery);
		$query->setParameter('codi', $codi);
		if ($idproducte > 0) $query->setParameter('idproducte', $idproducte);
		if ($desde != null) $query->setParameter('desde', $desde->format('Y-m-d'));
		return $query;
	}
	
	protected function registreStockComanda($comanda, $originalDetalls = null) {
		$em = $this->getDoctrine()->getManager();
		
		if ($originalDetalls == null) $originalDetalls = new \Doctrine\Common\Collections\ArrayCollection();

		$productesNotificacio = array();
		foreach ($comanda->getDetalls() as $detall) {

			$producte = $detall->getProducte();	
			$unitats = $detall->getUnitats();

			if ($producte->getStockable()) {

				$factura = $comanda->getFactura();
				if (!$comanda->esNova()) {
					$ultimafactura = $comanda->getFacturaAnulacioNova();
					if ($ultimafactura != null) $factura = $ultimafactura; // Cercar factura anul·lació corresponent
					
					// Cercar original corresponent per veure canvis unitats comanda
					foreach ($originalDetalls as $detallOriginal) {
						if ($detallOriginal->getId() == $detall->getId()) $unitats = $detall->getUnitats() - $detallOriginal->getUnitats();
					}
				}
				
				if ($unitats != 0) {
				    $fede = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find(BaseController::CODI_FECDAS);
				    
					$comentaris = 'Sortida stock '.$unitats.'x'.$producte->getDescripcio();
						
					$registreStock = new EntityStock($fede, $producte, $unitats, $comentaris, $comanda->getDataentrada(), BaseController::REGISTRE_STOCK_SORTIDA, $factura);
					$em->persist($registreStock);
						
					$club = $comanda->getClub();
					
					if ($producte->esKit() && $club != $fede) {
						$comentaris = 'Comanda KITS '.$unitats.'x'.$producte->getDescripcio();
						$registreStockClub = new EntityStock($club, $producte, $unitats, $comentaris, $comanda->getDataentrada(), BaseController::REGISTRE_STOCK_ENTRADA, $factura);
						$em->persist($registreStockClub);
					}
					
					$stock = $this->consultaStockClubPerProducteData($producte, $fede);
					
					// Control notificació stock
					if ($stock < $producte->getLimitnotifica()) {
						$productesNotificacio[] = $producte; // Afegir a la llista de productes per notificar manca stock
					}
				}
			}
		}

		if (count($productesNotificacio) > 0) {

			// Enviar notificacions
			$body = '';
			foreach ($productesNotificacio as $producte) {
				$body .= '<li>El producte \''.$producte->getDescripcio().'\' té '.$stock.
							' en stock (notificació inferior a '.$producte->getLimitNotifica().'). </li>'; 
			}
			$body = '<p>Cal revisar l\'stock dels següents productes</p>'. 
					 '<ul>'.$body.'</ul>';
	
			$subject = "Revisió stock. Federació Catalana d'Activitats Subaquàtiques";
				
			$tomails =  array($this->getParameter('MAIL_FECDAS'));
				
			$this->buildAndSendMail($subject, $tomails, $body);
		}		
	}

	protected function tramitarComanda($comanda, $originalDetalls = null, $informarPagament = false, $form = null) {
		$em = $this->getDoctrine()->getManager();
		
		if ($originalDetalls == null) $originalDetalls = new \Doctrine\Common\Collections\ArrayCollection();

		if ($comanda->getClub() == null) {
			$form->get('club')->addError(new FormError('Falta el club'));
				throw new \Exception('Cal escollir un club ' );
		}
		
		//if ($comanda->esNova()) {
			if (!$comanda->detallsEditables())
				throw new \Exception('No es poden editar els detalls d\'aquest tipus de comandes');
				
			if ($comanda->getNumDetalls() <= 0) {
				throw new \Exception('La comanda ha de tenir algún producte'  );
			}
			
			if ($comanda->getTotalComanda() == 0) {
				throw new \Exception('L\'import de la comanda ha de ser diferent de 0'  );
			}
		//} 		
	
		// Nous detalls, baixes i validació
		$formdetalls = null;				
		if ($form != null) $formdetalls = $form->get('detalls');
		
		//$productesNotificacio = array();
		$detallsPerAnulacio = array();
		foreach ($comanda->getDetalls() as $detall) {
			// Nou detall
			if ($comanda->esNova() == true) $em->persist($detall);
			
			$producte = $detall->getProducte();	
			$unitats = $detall->getUnitats();
			
			if ($producte == null) {
				$camp = $this->cercarCampColleccio($formdetalls, $detall, 'producte');
				if ($camp != null) $camp->addError(new FormError('Escollir producte'));
				throw new \Exception('Cal escollir algun producte de la llista'  );
			}

			if ($comanda->esNova()) {
				
				if ($unitats == 0) {
					$camp = $this->cercarCampColleccio($formdetalls, $detall, 'unitats');
					if ($camp != null) $camp->addError(new FormError('?'));
					throw new \Exception('Cal afegir mínim una unitat del producte'  );
				}
				
				if ($unitats > 0 && $producte->getMinim() != null && $unitats < $producte->getMinim()) {
					$camp = $this->cercarCampColleccio($formdetalls, $detall, 'unitats');
					if ($camp != null) $camp->addError(new FormError('?'));
					throw new \Exception('El mínim d\'unitats que cal demanar del producte \''.
							$producte->getDescripcio().'\' es '.$producte->getMinim());
				}
				
			} else {
				// Cercar original corresponent per veure canvis
				// Només es pot treure productes (anul·lacions) de la comanda. 
				$detallOriginal = null;
				
				foreach ($originalDetalls as $d) {
					if ($d->getId() == $detall->getId()) $detallOriginal = $d;
				}
				if ($detallOriginal == null) throw new \Exception('No es poden afegir nous productes a la comanda'  );
				
				if ($unitats > 0 && $producte->getMinim() != null && $unitats < $producte->getMinim()) {
					$camp = $this->cercarCampColleccio($formdetalls, $detall, 'unitats');
					if ($camp != null) $camp->addError(new FormError('?'));
					throw new \Exception('El mínim d\'unitats que cal demanar del producte \''.
							$producte->getDescripcio().'\' es '.$producte->getMinim());
				}
				
				// Només treure productes (anul·lar)
				$unitatsDiferencia = $detallOriginal->getUnitats() - $detall->getUnitats();

				if ($unitatsDiferencia < 0) throw new \Exception('No es poden afegir més productes a la comanda'  );
				if ($unitatsDiferencia != 0 && $informarPagament == true) throw new \Exception('No es pot afegir el pagament i alhora anul·lar productes'  );
				
				// La diferència retorna a l'stock
				if ($unitatsDiferencia > 0) { 
					$detall->setUnitatsBaixa($detall->getUnitatsbaixa() + $unitatsDiferencia);
					$anotacioModificada = $detall->getUnitats().'x'.($producte != null?$producte->getDescripcio():'');
					$detall->setAnotacions($anotacioModificada);
					
					$detallAnulat = new EntityComandaDetall($comanda, $producte, $unitatsDiferencia*(-1), $detall->getDescomptedetall(), $anotacioModificada);
					$detallAnulat->setPreuunitat($detall->getPreuunitat());
					$detallsPerAnulacio[] = $detallAnulat;
				}
			}
			
			$detall->setDatamodificacio(new \DateTime());
			
		}
		
		// Gestionar stock
		$this->registreStockComanda($comanda, $originalDetalls);
		
		if (count($detallsPerAnulacio) > 0) {
		    $this->crearFacturaRebutAnulacio($comanda, $detallsPerAnulacio);
		}
		
		return $comanda;
	}

	private function cercarCampColleccio($colleccio, $data, $camp) {
		if ($colleccio == null) return null;
		foreach ($colleccio as $fill) {
			if ($fill->getData() === $data)  {
				$camps = $fill->all();
				
				if (isset($camps[$camp])) return $camps[$camp];
				else return null;
			}
		}
		return null;		
	}
	
    protected function gestionarFotoPersona($persona, $fotoPath, $foto) {
        
        if ($foto == null) {
            if ($fotoPath == '' && $persona->getFoto() != null) {
                // Desar la foto a arxius de la persona i esborrar foto
                $persona->addArxius($persona->getFoto());
                $persona->setFoto(null);
            }
        } else {
            
            if (!($foto instanceof UploadedFile) or !is_object($foto))  throw new \Exception('No s\'ha pogut carregar la foto (1)');
            
            if (!$foto->isValid()) throw new \Exception('No s\'ha pogut carregar la foto (2-'.$foto->isValid().')'); // Codi d'error
            
            $em = $this->getDoctrine()->getManager();
            
            $uploaded = $this->uploadAndScale($foto, $persona->getDni(), 300, 200);
            
            $foto = new EntityArxiu($uploaded['path'], true);
            $foto->setPath($uploaded['name']);
            $foto->setTitol("Foto federat " . $persona->getNomCognoms());
            $em->persist($foto);
            
            if ($persona->getFoto() != null) {
                // Desar la foto a arxius de la persona
                $persona->addArxius($persona->getFoto());
            }
            $persona->setFoto($foto);
        }
        return $foto;
    }
    
    protected function gestionarArxiuPersona($persona, $esborrar = true, $arxiu, $foto = false, $certificat = false) {
       
        if ($esborrar) {
            if ($foto && $persona->getFoto() != null) {
                // Esborrar foto
                $persona->addArxius($persona->getFoto());
                $persona->setFoto(null);
            }
            if ($certificat && $persona->getCertificat() != null) {
                // Esborrar foto
                $persona->addArxius($persona->getCertificat());
                $persona->setCertificat(null);
            }
            if (!$foto && !$certificat && $arxiu != null) {
                // Esborrar altres arxius
                $persona->removeArxius($arxiu);
            }
            return;
        }
   
        if ($arxiu == null) return;
            
        if (!($arxiu instanceof UploadedFile) or !is_object($arxiu))  throw new \Exception('No s\'ha pogut carregar l\'arxiu (1)');
            
        if (!$arxiu->isValid()) throw new \Exception('No s\'ha pogut carregar l\'arxiu (2-'.$arxiu->isValid().')'); // Codi d'error
            
        $em = $this->getDoctrine()->getManager();
        
        $nouArxiu = null;
 
        if ($foto) {
            $titol = "Foto federat " . $persona->getNomCognoms();
            
            $uploaded = $this->uploadAndScale($arxiu, $persona->getDni(), 300, 200);
            
            $nouArxiu = new EntityArxiu($uploaded['path'], true);
            $nouArxiu->setPath($uploaded['name']);
        } else {
            $titol = $arxiu->getClientOriginalName()." ".date('d/m/Y');
            
            $nouArxiu = new EntityArxiu($arxiu, false, $persona);
            $nouArxiu->upload($persona->getDni()."_".$arxiu->getClientOriginalName());
        }

        $nouArxiu->setTitol($titol);
        $em->persist($nouArxiu);
        
        if ($foto) $persona->setFoto($nouArxiu);
        if ($certificat) $persona->setCertificat($nouArxiu);
        if (!$foto && !$certificat) $persona->addArxius($nouArxiu);
    }
    
    protected function gestionarCertificatPersona($persona, $certificatPath, $certificat) {
        if ($certificat == null) {
            if ($certificatPath == '' && $persona->getCertificat() != null) {
                // Desar el certificat a arxius de la persona i esborrar certificat
                $persona->addArxius($persona->getCertificat());
                $persona->setCertificat(null);
            }
        } else {
            
            if (!($certificat instanceof UploadedFile) or !is_object($certificat))  throw new \Exception('No s\'ha pogut carregar l\'arxiu (1)');
            
            if (!$certificat->isValid()) throw new \Exception('No s\'ha pogut carregar l\'arxiu (2-'.$certificat->isValid().')'); // Codi d'error
            
            $em = $this->getDoctrine()->getManager();
            
            $nameAjustat = $persona->getDni()."_".substr($certificat->getClientOriginalName(), -20);
            $nameAjustat = time() . "_". Funcions::netejarPath($nameAjustat);
            
            $certificat = new EntityArxiu($certificat, true);
            $certificat->upload($nameAjustat);
            $certificat->setTitol("Certificat mèdic federat " . $persona->getNomCognoms());
            $em->persist($certificat);
            
            if ($persona->getCertificat() != null) {
                // Desar el certificat a arxius de la persona
                $persona->addArxius($persona->getCertificat());
            }
            $persona->setCertificat($certificat);
        }
        return $certificat;
    }
    
	/*
	 * El saldo comptable per a dates posteriors a l'inici de l'exercici (Saldo al dia abans de la data indicada)
	 * 
	 * El romanent actual del club (aplicats tots els possibles moviments fins a data actual, p.e. factures amb data anterior a l'inici de l'exercici)
	 * + Suma entrades comptables fins a la data (datapagament rebut) 
	 * - Suma sortides comptables fins a la data (datafactura factura)
	 * 
	 * Aquestes entrades i sortides es troben a la taula de saldos
	 */ 
	protected function saldosComptablesData($data, $club) {
		$em = $this->getDoctrine()->getManager();
		
		$saldosComptables = array($club->getCodi() => 0);
		
		if ($data == null || $club == null) return $saldosComptables;
		
		$iniciExercici = \DateTime::createFromFormat('d/m/Y', "01/01/".$club->getExercici());
		
		if ($data->format('Y-m-d') <= $iniciExercici->format('Y-m-d')) {
		    $saldosComptables[$club->getCodi()] = $club->getRomanent();
		    return $saldosComptables; // Saldo és el romanent
		}
		
		/*$iniciExercici = \DateTime::createFromFormat('d/m/Y', "01/01/".$club->getExercici()); 
		
		if ($data->format('Y-m-d') < $iniciExercici->format('Y-m-d')) return null;  // No es pot consultar per dates anteriors a l'inici de l'exercici
		
		// Obtenir saldo comptable exercici actual d'un club a una data
		$strQuery  = " SELECT c, SUM(s.entrades) - SUM(s.sortides) as variacio FROM FecdasBundle\Entity\EntitySaldos s JOIN s.club c ";
		$strQuery .= " WHERE s.dataregistre >= :iniciexercici ";
		$strQuery .= " AND   s.dataregistre <  :data ";
		if ($club != null) $strQuery .= " AND   s.club = :club ";
		$strQuery .= " GROUP BY c.codi ";
		$strQuery .= " ORDER BY c.codi ";
		$query = $em->createQuery($strQuery);
		if ($club != null) $query->setParameter('club', $club->getCodi() );
		$query->setParameter('iniciexercici', $iniciExercici->format('Y-m-d') );
		$query->setParameter('data',  $data->format('Y-m-d') );
		
		//$variacions = $query->getArrayResult();
		$variacions = $query->getResult(); 
		*/ 

		
		// Consulta dades exercici actual o encara no tancat. Acumular des de romanent actual del club
			
		$strQuery  = " SELECT c.codi, c.romanent, SUM(s.entrades) - SUM(s.sortides) as variacio FROM m_clubs c INNER JOIN m_saldos s ON c.codi = s.club ";
		$strQuery .= " WHERE YEAR(s.dataregistre) >= c.exercici ";
		$strQuery .= " AND   s.dataregistre <  '".$data->format('Y-m-d')."' ";
		$strQuery .= " AND   s.club = '".$club->getCodi()."' ";
		$strQuery .= " GROUP BY c.codi, c.romanent ";
		$strQuery .= " ORDER BY c.codi ";
			
	    $stmt = $em->getConnection()->prepare($strQuery);
	    $stmt->execute();
    	$variacions = $stmt->fetchAll();
		
		foreach ($variacions as $variacio) {
			//$currentClub = $variacio['codi'];
			$saldosComptables[$variacio['codi']] = $variacio['romanent'] + $variacio['variacio'];
		}		
		
		return $saldosComptables;
	}
	
	protected function acumulatsEntreMensuals($fins, $club) {
		$em = $this->getDoctrine()->getManager();
		
		// Consultar saldos entre dates acumulats per mes per a l'exercici en curs
		$strQuery  = " SELECT c.codi, c.romanent, c.exercici, c.nom, c.compte, YEAR(s.dataregistre) as anyregistre, MONTH(s.dataregistre) as mesregistre, SUM(s.entrades) as entrades, SUM(s.sortides) as sortides FROM m_clubs c INNER JOIN m_saldos s ON c.codi = s.club ";
		$strQuery .= " WHERE (c.databaixa IS NULL OR c.databaixa > '".$fins->format('Y-m-d H:i:s')."') AND c.compte IS NOT NULL AND YEAR(s.dataregistre) >= c.exercici ";
		$strQuery .= " AND   s.dataregistre <=  '".$fins->format('Y-m-d')."' ";
		if ($club != null) $strQuery .= " AND   s.club = '".$club->getCodi()."' ";
		$strQuery .= " GROUP BY c.codi, c.romanent, c.exercici, c.nom, c.compte, YEAR(s.dataregistre), MONTH(s.dataregistre) ";
		$strQuery .= " ORDER BY c.compte, YEAR(s.dataregistre), MONTH(s.dataregistre) ";
	    
	    $stmt = $em->getConnection()->prepare($strQuery);
	    $stmt->execute();
    	$acumulats = $stmt->fetchAll();
		
		return $acumulats; 
	}
	

	protected function saldosEntre($desde, $fins = null, $club = null, $max = 0) {
		$em = $this->getDoctrine()->getManager();
		
		// Consultar saldos entre dates per un club
		$strQuery  = " SELECT s FROM FecdasBundle\Entity\EntitySaldos s ";
		$strQuery .= " WHERE s.dataregistre >= :desde ";
		if ($fins != null) $strQuery .= " AND   s.dataregistre <=  :fins ";
		if ($club != null) $strQuery .= " AND   s.club = :club ";
		$strQuery .= " ORDER BY s.dataregistre ASC ";
			
		$query = $em->createQuery($strQuery);
		if ($max > 0) $query->setMaxResults($max);
		
		$query->setParameter('desde', $desde->format('Y-m-d') );
		if ($fins != null) $query->setParameter('fins',  $fins->format('Y-m-d') );
		if ($club != null) $query->setParameter('club', $club->getCodi() );
		
		$saldos = $query->getResult();
		
		return $saldos;
	}


	protected function facturesEntre($desde, $fins) {
		$em = $this->getDoctrine()->getManager();
		
		// Consultar factures entrades entrats dia current
		$strQuery  = " SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
		$strQuery .= " WHERE f.dataentrada >= :desde ";
		$strQuery .= " AND   f.dataentrada <=  :fins ";
			
		$query = $em->createQuery($strQuery);
		$query->setParameter('desde', $desde->format('Y-m-d H:i:s') );
		$query->setParameter('fins',  $fins->format('Y-m-d H:i:s') );
		
		$factures = $query->getResult();
		
		return $factures;
	}

	protected function rebutsEntre($desde, $fins, $codiclub = '') {
		$em = $this->getDoctrine()->getManager();
		
		// Consultar factures entrades entrats dia current
		$strQuery  = " SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.databaixa IS NULL ";
		$strQuery .= " AND   r.dataentrada >= :desde ";
		$strQuery .= " AND   r.dataentrada <=  :fins ";
		if ($codiclub != '') $strQuery .= " AND   r.club = :club ";
			
		$query = $em->createQuery($strQuery);
		$query->setParameter('desde', $desde->format('Y-m-d H:i:s') );
		$query->setParameter('fins',  $fins->format('Y-m-d H:i:s') );
		if ($codiclub != '') $query->setParameter('club', $codiclub  );
		
		$rebuts = $query->getResult();
		
		return $rebuts;
	}

	/**
	 * $club
	 * $dia del registre, desar els valors actuals 
	 */
	protected function registrarSaldoClub($club, $current = null) {
		
		if ($current == null) $current = new \DateTime('today');
		
		$em = $this->getDoctrine()->getManager();

		// Comprovar si existeix registre
		$registre = $this->getDoctrine()->getRepository('FecdasBundle:EntitySaldos')->findOneBy(array('club' => $club->getCodi(), 'dataregistre' => $current ));
		
		if ($registre == null) {
			$registre = new EntitySaldos($club, $current);
			$em->persist($registre);
		} 

		if (count($registre) > 1) $registre = $registre[0];
		
		// Indicar valors dels saldos actuals del club
		$registre->setExercici( $club->getExercici() );
		$registre->setRomanent( $club->getRomanent() );
		$registre->setTotalllicencies( $club->getTotalllicencies() );
		$registre->setTotalduplicats( $club->getTotalduplicats() );
		$registre->setTotalaltres( $club->getTotalaltres() );
		$registre->setTotalpagaments( $club->getTotalpagaments() ); 
		$registre->setAjustsubvencions( $club->getAjustsubvencions() );
		$registre->setComentaris('');

	}

	/**
	 * 
	 * $current => dia de procés
	 * $club
	 * $entrada / $sortida => imports (rebuts / factures)
	 * $data => data del moviment
	 * 
	 */ 
	protected function registrarMovimentClub($current, $club, $entrada, $sortida, $data) {
		$em = $this->getDoctrine()->getManager();
		
		if ($club == null || $club->getCodi() == BaseController::CODI_CLUBTEST) return null;
		
		// Comprovar si existeix registre
		$registre = $this->getDoctrine()->getRepository('FecdasBundle:EntitySaldos')->findOneBy(array('club' => $club->getCodi(), 'dataregistre' => $data ));
				
		if ($registre == null) {
			if ($data->format('Y-m-d') < $current->format('Y-m-d')) {
				// Moviment a data passada que no està registrada no es crea registre.
				return null;
			} 		

			$registre = new EntitySaldos($club, $data);
			$em->persist($registre);
		}

		$registre->setEntrades( $registre->getEntrades() + $entrada);
		$registre->setSortides( $registre->getSortides() + $sortida);
		return $registre;
	}

	protected function exportCSV($request, $header, $data, $filename) {

	    //$csvTxt = '"'.iconv('UTF-8', 'ISO-8859-1//TRANSLIT',implode('";"',$header)).'"'.CRLF;
    	$csvTxt = '"'.iconv('UTF-8', 'ISO-8859-1//IGNORE',implode('";"',$header)).'"'.CRLF;
    	
    	//$infoseccionsCSV = array();
    	foreach ($data as $row) {
    		$row = '"'.implode('";"', $row).'"';
    		$csvTxt .= iconv('UTF-8', 'ISO-8859-1//IGNORE', $row.CRLF);
    	}

    	$response = new Response($csvTxt);
    	
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/csv; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Export CSV');
    	
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    	
    	$response->prepare($request);
    	
    	return $response;
    }
    
    protected function writeCSV($header, $data, $filename) {
        
        $fs = new Filesystem();
        
        if (!$fs->exists(__DIR__.BaseController::PATH_TO_VARIS_FILES)) throw new \Exception("No existeix el directori " .__DIR__.BaseController::PATH_TO_VARIS_FILES);

        $csvTxt = '"'.iconv('UTF-8', 'ISO-8859-1//IGNORE',implode('";"',$header)).'"'.CRLF;
                
        foreach ($data as $row) {
            $row = '"'.implode('";"', $row).'"';
            $csvTxt .= iconv('UTF-8', 'ISO-8859-1//IGNORE', $row.CRLF);
        }
                
        $file = __DIR__.BaseController::PATH_TO_VARIS_FILES.$filename;
                
        //if ($fs->exists($file)) throw new \Exception("El fitxer ja existeix ".$file);
                
        $fs->dumpFile($file, $csvTxt);
        return $file;
    }
    
    
	protected function getProvincies() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT distinct m.provincia FROM FecdasBundle\Entity\EntityMunicipi m
				ORDER BY m.provincia");
		$result = $query->getResult();
		$provincies = array();
		foreach ($result as $res)
			$provincies[$res['provincia']] = $res['provincia'];
		
		// Add altres províncies espanyoles	
		foreach (self::PROVINCIES_ALTRES as $prov) $provincies[$prov] = $prov;
			
		return $provincies;
	}
	
	protected function getComarques() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT distinct m.comarca FROM FecdasBundle\Entity\EntityMunicipi m
				ORDER BY m.comarca");
		$result = $query->getResult();
		$comarques = array();
		foreach ($result as $res)
			$comarques[$res['comarca']] = $res['comarca'];
		return $comarques;
	}

	protected function getMunicipis() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT distinct m.municipi FROM FecdasBundle\Entity\EntityMunicipi m
				ORDER BY m.municipi");
		$result = $query->getResult();
		$municipis = array();
		foreach ($result as $res)
			$municipis[$res['municipi']] = $res['municipi'];
		return $municipis;
	}
	
	protected function getNacions() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT n FROM FecdasBundle\Entity\EntityNacio n
				ORDER BY n.codi");
		$result = $query->getResult();
		$nacions = array();
		foreach ($result as $res)
			$nacions[$res->getCodi()] = $res->getCodi() . ' - ' . $res->getPais();
		return $nacions;
	}
	
	protected function getClubsSelect() {
		$em = $this->getDoctrine()->getManager();
	
		$query = $em->createQuery("SELECT c FROM FecdasBundle\Entity\EntityClub c
				ORDER BY c.nom");
		$clubs = $query->getResult();
	
		$clubsvalues = array();
		foreach ($clubs as $v) $clubsvalues[$v->getCodi()] = $v->getLlistaText();
	
		return $clubsvalues;
	}
	
	protected function consultaAjaxPoblacions($value, $tipus) {
		// http://fecdas.dev/app_dev.php/ajaxpoblacions?term=abx&tipus=cp   ==> For debug
		// Cerques només per a >= 3 lletres
		
		$search = array();
		if (strlen($value) >= 3) {
			$em = $this->getDoctrine()->getManager();
			$camp = 'm.municipi';
			if ($tipus == 'cp') $camp = 'm.cp';
			$query = $em->createQuery("SELECT DISTINCT m.municipi, m.cp, m.provincia, m.comarca
										FROM FecdasBundle\Entity\EntityMunicipi m
										WHERE ".$camp." LIKE :value ORDER BY m.municipi")
										->setParameter('value', '%' . $value . '%');
			$result = $query->getResult();
			
			foreach ($result as $res) {
				$muni = array();
				//$search[] = $res['municipi'];
				$muni['value'] = ($tipus == 'cp'?$res['cp']:$res['municipi']);
				$muni['label'] = "{$res['municipi']}, {$res['cp']}, {$res['provincia']}, {$res['comarca']}";
				$muni['municipi'] = $res['municipi'];
				$muni['cp'] = $res['cp'];
				$muni['provincia'] = $res['provincia'];
				$muni['comarca'] = $res['comarca'];
				$search[] = $muni;
			}
			//$search = array_slice($search, 0, 6);
			// per exemple $search = array('Abrera', 'Agramunt', 'Agullana');
		}
		return $search;	
	}

	protected function consultaAjaxClubs($value) {
		// http://fecdas.dev/app_dev.php/ajaxpoblacions?term=abx   ==> For debug
		// Cerques només per a >= 3 lletres
		$search = array();
		if (strlen($value) >= 3) {
			$em = $this->getDoctrine()->getManager();
			$query = $em
			->createQuery(
					"SELECT DISTINCT c.codi, c.nom
					FROM FecdasBundle\Entity\EntityClub c
					WHERE c.nom LIKE :value ORDER BY c.nom")
						->setParameter('value','%' . $value . '%');
			$result = $query->getResult();
				
			foreach ($result as $res) {
				$clubnom = array();
				$clubnom['value'] = $res['nom'];
				$clubnom['label'] = $res['nom'];
				$clubnom['codi'] = $res['codi'];
				$search[] = $clubnom;
			}
			//$search = array_slice($search, 0, 6);
			// per exemple $search = array('Abrera', 'Agramunt', 'Agullana');
		}
		return $search;
	}
	
	
	protected function generateRandomPassword() {
		$password = '';
		$desired_length = rand(8, 12);
		for($length = 0; $length <= 2; $length++) {
			$password .= chr(rand(97, 122));  // 3 minuscules
		}
		for($length = 3; $length <= 5; $length++) {
			$password .= chr(rand(48, 57));  // 3 numeros
		}
		for($length = 6; $length < $desired_length; $length++) {
			$password .= chr(rand(65, 90));  // 2 a 6 majuscules
		}
		return $password;
	} 
	
	public static function esDNIvalid ($cadena)
	{
		//Comprovar DNI
	    if (strlen($cadena) != 9 || !preg_match('/^[0-9]{8}[A-Z]$/i', $cadena)) return false;	// Format incorrecte
		
		$dnisenselletra = (int) substr($cadena, 0, strlen($cadena) - 1);		
			
		// Lletra
		$lletra = BaseController::getLletraDNI ($dnisenselletra); 		
	
		if (strtoupper($cadena[strlen($cadena) - 1]) != $lletra) {
		    return false;
		}
			
		//Ok
		return true;
	}
	
	public static function getLletraDNI ($dnisenselletra)
	{
		// longitud
		if ($dnisenselletra > 99999999) return '';
	
		// valors letra
		$lletres = array(
				0 => 'T', 1 => 'R', 2 => 'W', 3 => 'A', 4 => 'G', 5 => 'M',
				6 => 'Y', 7 => 'F', 8 => 'P', 9 => 'D', 10 => 'X', 11 => 'B',
				12 => 'N', 13 => 'J', 14 => 'Z', 15 => 'S', 16 => 'Q', 17 => 'V',
				18 => 'H', 19 => 'L', 20 => 'C', 21 => 'K',22 => 'E'
		);
	
		return $lletres[ $dnisenselletra % 23 ];  //Calcular lletra
	}
	
	
	protected function getActiveEnquesta() {
		/* Obté enquesta activa pendent de realitzar de l'usuari registrat */
		if ($this->isAuthenticated() != true) return null;
		
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = "SELECT e FROM FecdasBundle\Entity\Enquestes\EntityEnquesta e";
		$strQuery .= " WHERE e.datainici <= :avui ";
		$strQuery .= " AND (e.datafinal >= :avui OR e.datafinal IS NULL)";
		$strQuery .= " ORDER BY e.datainici DESC";
		
		$avui = $this->getCurrentDate();
		$avui = $avui->format('Y-m-d H:i:s');
		
		$query = $em->createQuery($strQuery)
			->setParameter('avui', $avui)
			->setMaxResults(1);
			
		$enquestes = $query->getResult();
		foreach ($enquestes as $enquesta) return $enquesta; // Només una
		return null;
	}
	
	public static  function getInfoTempsNomFitxer($desde, $fins, $space = '_', $datasep = '_') {
		$dateFormat = "d".$datasep."m".$datasep."Y";
		$strTemps = "a".$space."data".$space.date($dateFormat);
		
		if ($desde != null || $fins != null) {
			$strTemps = "";
			if ($desde != null) $strTemps .= $space."des".$space."de".$space.$desde->format($dateFormat);
				
			if ($fins != null) $strTemps .= $space."fins".$space.$fins->format($dateFormat);
		}
		return $strTemps;
	}
	
	protected function getTempUploadDir()
	{
		/* Temporary upload folder. Variable __DIR__ és el directori del fitxer */
		return __DIR__.self::TMP_FOLDER;
	}
	
	protected function logEntryAuth($accio = null, $extrainfo = '') {
		//$request = $this->container->get('request_stack')->getCurrentRequest();
		$checkRole = $this->get('fecdas.rolechecker');

		$this->logEntry($checkRole->getCurrentUserName(), $accio, $checkRole->getCurrentRemoteAddr(), $checkRole->getCurrentHTTPAgent(), $extrainfo);
	}
	
	protected function logEntry($user = null, $accio = '', $remoteaddr = '', $useragent = '', $extrainfo = '') {
		$checkRole = $this->get('fecdas.rolechecker');
		if (!$user) {
			if ($checkRole->getCurrentUserName() != '') $user = $checkRole->getCurrentUserName();
			else $user = $this->getParameter('MAIL_ADMINLOG');
		}
		
		if ($remoteaddr == '') $remoteaddr = $checkRole->getCurrentRemoteAddr();
		if ($useragent == '') $useragent =  $checkRole->getCurrentHTTPAgent();
		
		$em = $this->getDoctrine()->getManager();
		
		if (!$em->isOpen()) {
			//$em = $this->getDoctrine()->resetManager();
			//$em = $this->getDoctrine()->getManager();
		} 
		//$logentry = new EntityUserLog(substr($user,0,50), substr($accio,0,20), substr($remoteaddr,0,20), substr($useragent,0,100), substr($extrainfo,0,100));
        $logentry = new EntityUserLog(substr($user,0,50), substr($accio,0,20), substr($remoteaddr,0,20), mb_substr($useragent,0,100), mb_substr($extrainfo,0,100));
		$em->persist($logentry);
		try {
			$em->flush();
		} catch (\Exception $e) {
			error_log ("APP FECDAS > Error saving app log to mysql: ".$e->getMessage(), 0);
		}
	}
	
	protected function getLogMailUserData($source = null) {
		//$request = $this->container->get('request_stack')->getCurrentRequest();
		$checkRole = $this->get('fecdas.rolechecker');
		
		return $source." ".$checkRole->getCurrentUserName()." (".$checkRole->getCurrentHTTPAgent().")";
	}
	
    protected function validateCronAuth($request, $action = '') {
        $cronsecret = $this->getParameter( 'secret', '' ); 
        $secret = $request->query->get('secret', '');
        
        if ($secret == '' || $secret != $cronsecret) {
            // mail admin
            $this->logEntryAuth('AUTH ERROR NOTIFICA', "ERROR ".$action.": ".$secret.' clau incorrecta ');
            $errorAuth = " Error auth ".$action." ".$secret." clau incorrecta <br/>";
            //echo $errorAuth;
            
            $tomails = array($this->getParameter('MAIL_LLICENCIES'));
            $subject = "Federació Catalana d'Activitats Subaquàtiques. ERROR ".$action;
        
            $this->buildAndSendMail($subject, $tomails, $errorAuth);
            
            throw new \Exception("ERROR ".$action);
        }
    }
	
	protected function buildAndSendMail($subject, $tomails, $innerbody, $bccmails = array(), $attachmentPath = null, $attachments = array(), $width = 600, $salutacio = '') {
		
		if ($this->get('kernel')->getEnvironment() != 'prod') {
			$tomails = array($this->getParameter('MAIL_ADMIN'));  // Entorns de test
			$bccmails = array(); // Entorns de test
		} else {
		    // Producció
		    $bccmails[] = $this->getParameter('MAIL_ADMIN');
		    // Mails producció prefix [bcc] es mouen tomails => bcc
		    $tomails = array_filter($tomails, function($e) use (&$bccmails) {
		        $bcc = strpos($e, BaseController::PREFIX_MAIL_BCC);
		        
		        if ($bcc !== false && $bcc ==  0) { 
		            $bccmails[] = str_replace(BaseController::PREFIX_MAIL_BCC, "", $e);
		            return false;
		        }
		        
		        return true;  // si retorna true, l'element s'afegirà al vector resultant  
		    });
		}
		
		$message = \Swift_Message::newInstance()
		->setSubject($subject)
		->setFrom($this->getParameter('MAIL_FECDASGESTIO'))
		->setBcc($bccmails)
		->setTo($tomails);

		if ($attachmentPath != null) $message->attach(\Swift_Attachment::fromPath($attachmentPath));
		
		foreach ($attachments as $attachment) {
			if (isset($attachment['name']) && isset($attachment['data'])) {
				// $message->attach(\Swift_Attachment::newInstance($attachment['data'], $attachment['name'], 'application/octet-stream'));  // ERROR per output tcpdf 'E'
				$message->attach(\Swift_Attachment::newInstance($attachment['data'], $attachment['name'], 'application/pdf')); // OK per output tcpdf 'S' String
			}		
		}
		
		
		if ($salutacio != '') {
			$innerbody .= $salutacio;
		} else {
			$innerbody .= "<p>Atentament<br/>";
			$innerbody .= "FECDAS, ".$this->getCurrentDate()->format("d/m/Y")."</p><br/>";
		}
		$logosrc = $message->embed(\Swift_Image::fromPath(BaseController::IMATGE_LOGO_FECDAS_MAIL));
		
		$footer = "<table border='0' cellpadding='0' cellspacing='0' width='100%'>";
		$footer .= "<tr><td><img src=".$logosrc." alt='FECDAS' width='82' height='78' /></td>";
		$footer .= "<td style='padding: 0 0 0 20px;'>";
		$footer .= "<small><b>FEDERACIÓ CATALANA D’ACTIVITATS SUBAQUÀTIQUES</b></small><br/>";
		$footer .= "<span style='font-size: 10px;'>Moll de la Vela, 1 (Zona Fòrum)<br/>";
		$footer .= "08930  Sant Adrià de Besòs<br/>";
		$footer .= "Tel. 93 356 05 43 | 620 28 29 39<br/>";
		//$footer .= "Fax: 93 356 30 73<br/>";
		$footer .= "Adreça electrònica: ".$this->getParameter('MAIL_FECDAS')."<br/>";
		$footer .= "</span></td></tr></table>";
		
		$body = "<html style='font-family: Helvetica,Arial,sans-serif;'><head></head><body>";
		$body .= "<table align='left' border='0' cellpadding='0' cellspacing='0' width='".$width."' style='border-collapse: collapse;'>";
		$body .= "<tr><td style='padding: 10px 0 10px 0;'>".$innerbody."</td></tr>";
		$body .= "<tr><td style='padding: 10px 0 10px 0;'>".$footer."</td></tr></table></body></html>";
		
		$message->setBody($body, 'text/html');
		
		$this->get('mailer')->send($message);
/*		error_log('========');
		error_log('to mails '.print_r($tomails, true));
		error_log('bcc mails '.print_r($bccmails, true));
		error_log('');*/
	}
	
	protected function uploadAndScale($file, $name, $maxwidth, $maxheight) {
		/*
		 *   Imagick
		*   sudo apt-get install php-pear
		*   apt-get install php5-dev
		*   pear channel-update pear.php.net  ¿?
		*   pear upgrade PEAR					¿?
		*	 sudo apt-get install imagemagick libmagickwand-dev
		*	 sudo pecl install imagick
	
		configuration option "php_ini" is not set to php.ini location
		You should add "extension=imagick.so" to php.ini
	
		*   sudo apt-get install php5-imagick
		*	 sudo service apache2 restart
		*
		*/
	
		//http://jan.ucc.nau.edu/lrm22/pixels2bytes/calculator.htm
	
		/* Format jpeg mida inferior a 35k */
	
		$thumb = new \Imagick($file->getPathname());
		//$thumb->readImage($file->getPathname());
		$thumb->setImageFormat("jpeg");
		$thumb->setImageCompressionQuality(85);
		$thumb->setImageResolution(72,72);
		//$thumb->resampleImage(72,72,\Imagick::FILTER_UNDEFINED,1);
	
		// Inicialment escalar a una mida raonable
		if($thumb->getImageWidth() > $maxwidth || $thumb->getImageHeight() > $maxheight) {
			if($thumb->getImageWidth() > $maxwidth) $thumb->scaleImage($maxwidth, 0);
			else $thumb->scaleImage(0, $maxheight);
		}
	
		/*$i = 0;
		while ($thumb->getImageLength() > 35840 and $i < 10 ) {  /// getImageLength no funciona
		 $width = $image->getImageWidth();
		$width = $width*0.8; // 80%
		$thumb->scaleImage($width,0);
		$i++;
		}*/
			
		$nameAjustat = substr($name, 0, 33);
		$nameAjustat = time() . "_". Funcions::netejarPath($nameAjustat) . ".jpg";
		$strPath = __DIR__.self::UPLOADS_RELPATH.self::UPLOADS_FOLDER.$nameAjustat;
		$uploadReturn = $thumb->writeImage($strPath);
		$thumb->clear();
		$thumb->destroy();
	
		if ($uploadReturn != true) {
			throw new \Exception('3.No s\'ha pogut carregar la foto');
		}
	
		return array('name' => $nameAjustat, 'path' => $strPath);
	}
	
	public function jsonclubsAction(Request $request) {
		//foment.dev/jsonclubs?cerca=textcerca
		
		$response = new Response();
	
		$cerca = $request->get('cerca', '');
		$codi = $request->get('id', '');
	
		$em = $this->getDoctrine()->getManager();
	
		if ($codi != '') {
			$club = $em->getRepository('FecdasBundle:EntityClub')->find($codi);
				
			if ($club != null) {
				$response->headers->set('Content-Type', 'application/json');
				$response->setContent(json_encode(array("id" => $club->getCodi(), "text" => $club->getNom()) ));
				return $response;
			}
		}
	
		$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
		//$strQuery .= " WHERE c.activat = 1 ";
		$strQuery .= " WHERE c.databaixa IS NULL ";
		$strQuery .= " AND c.nom LIKE :cerca";
		$strQuery .= " ORDER BY c.nom";
	
		$query = $em->createQuery($strQuery);
		$query->setParameter('cerca', '%'.$cerca.'%');
	
	
		$search = array();
		if ($query != null) {
			$result = $query->getResult();
			foreach ($result as $c) {
				$search[] = array("id" => $c->getCodi(), "text" => $c->getNom());
			}
		}
	
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($search));
	
		return $response;
	}

	public function jsontipuspagamentsAction(Request $request) {
		//foment.dev/jsontipuspagaments
		$response = new Response();
	
		$tipus = self::getTipusDePagament();
	
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($tipus));
	
		return $response;
	}
	
	public function jsonformseleccioclubcursAction(Request $request) {
	    //foment.dev/jsonformseleccioclubcurs
	    
	    $formBuilder = $this->createFormBuilder();
	    $checkRole = $this->get('fecdas.rolechecker');
	    $clubs = array();
	    if ($checkRole->isCurrentInstructor()) $clubs = $checkRole->getCurrentUser()->getClubsRole(BaseController::ROLE_INSTRUCTOR);
	    if ($this->isCurrentAdmin()) {
	        $clubs[] = $this->getCurrentClub();
	        if (!$this->getCurrentClub()->esFederacio()) {
	            $fede = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find(BaseController::CODI_FECDAS);
	            $clubs[] = $fede;
	        }
	    }
	    
        $formBuilder->add('clubs', 'entity', array(
            'class' 		=> 'FecdasBundle:EntityClub',
            'choices'       => $clubs,
            'choice_label' 	=> 'nom',
            'placeholder' 	=> false,	// Important deixar en blanc pel bon comportament del select2
            'required'  	=> false,
            //'data' 			=> $club,
        ));
	    
        return $this->render('FecdasBundle:Titulacions:formclubscurso.html.twig', array( 'form' => $formBuilder->getForm()->createView() ));
	}
	
	
	public function formatPhoneNumber($phone) {
	    // note: strip out everything but numbers
	    $phone = preg_replace("/[^0-9]/", "", $phone);
	    $length = strlen($phone);
	    switch($length) {
	        /*case 7:
	            // 372 56 19
	            return preg_replace("/([0-9]{3})([0-9]{2})([0-9]{2})/", "$1 $2 $3", $phone);
	            break;*/
	        case 9:

	            if ($phone[0] == "9") {
	                // 93 372 56 19
	                if (substr($phone, 0, 2) == "93") return preg_replace("/([0-9]{2})([0-9]{3})([0-9]{2})([0-9]{2})/", "($1) $2 $3 $4", $phone);
	                // 972 257 003
	                return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{3})/", "$1 $2 $3", $phone);
	            }
	            // 620 39 83 23
	            if ($phone[0] == "6") return preg_replace("/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{2})/", "$1 $2 $3 $4", $phone);
	            
	            return $phone;
	            break;
	        default:
	            return $phone;
	            break;
	    }
	}
	
	/**
	 * Retorna array detalls agrupats/acumulats per IVA
	 * 
	 * $detalls = array(
	 *   array('total' => 999,
	 *		 'preuunitat' => 9.99,
	 *		 'ivaunitat' => 9.99,
	 *     	 'descompte' => 9.99,
	 *       ....
     *	 ))
	 * 
	 * Get array detalls agrupats per IVA
	 *
	 *     {
	 *         '0.4' => 9.999
	 *         '0.21' => 9.999,
	 *     }
	 *
	 * @return array()
	 */
	public static function getIVADetalls($detalls) {
	    
	    $acumulat = array();
	    foreach ($detalls as $d) {
	        $iva = isset($d['ivaunitat'])?$d['ivaunitat']:0;
	        if ($iva != 0) {
	            if (isset($acumulat[$iva])) {
	                // Iva existent
	                $acumulat[$iva] += self::getImportNetDetall($d);
	            } else {
	                $acumulat[$iva] = self::getImportNetDetall($d);
	            }
	        }
	    }
	    return $acumulat;
	}
	
	/**
	 * Retorna IVA total dels detalls calculat agrupant IVA productes
	 *
	 * $detalls = array(
	 *   array('total' => 999,
	 *		 'preuunitat' => 9.99,
	 *		 'ivaunitat' => 9.99,
	 *     	 'descompte' => 9.99,
	 *       ....
     *	 ))
	 *
	 * @return double
	 */
	public static function getTotalIVADetalls($detalls) {
	    
	    $acumulat = self::getIVADetalls($detalls);
	    
	    $total = 0;
	    foreach ($acumulat as $iva => $net) {
	        $total += $net * $iva;
	    }

	    return $total;
	}
	
	
    /**
	 * Retorna import tota net dels detalls incloent descompte
	 * 
	 * $detalls = array(
	 *   array('total' => 999,
	 *		 'preuunitat' => 9.99,
	 *		 'ivaunitat' => 9.99,
	 *     	 'descompte' => 9.99,
	 *       ....
     *	 ))
     *
	 * @return double
	 */
	public static function getTotalNetDetalls($detalls) {
	    
	    $total = 0;
	    foreach ($detalls as $d) {
	        $total += self::getImportNetDetall($d);
	    }
        return $total;	    
	}
	
	/**
	 * Retorna import tota net detall
     * $detall =
	 *   array('total' => 999,
	 *		 'preuunitat' => 9.99,
	 *		 'ivaunitat' => 9.99,
	 *     	 'descompte' => 9.99,
	 *       ....
     *	 )
	 * @return double
	 */
	public static function getImportNetDetall($detall) {
	    if (!is_array($detall)) return 0;
	    $preuunitat = isset($detall['preuunitat'])?$detall['preuunitat']:0;
	    $total = isset($detall['total'])?$detall['total']:0;
	    $descompte = isset($detall['descompte'])?$detall['descompte']:0;
	    return $preuunitat * $total * (1 - $descompte);
	}
	
	
	public static function netejarNom($string, $sense_espais = true)
	{
		$string = trim($string);
	
		$string = str_replace(array("\r\n", "\r", "\n"), " ", $string);
	
		$string = str_replace(
				array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
				array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
				$string
		);
	
		$string = str_replace(
				array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
				array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
				$string
		);
	
		$string = str_replace(
				array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
				array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
				$string
		);
	
		$string = str_replace(
				array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
				array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
				$string
		);
	
		$string = str_replace(
				array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
				array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
				$string
		);
	
		$string = str_replace(
				array('ñ', 'Ñ', 'ç', 'Ç'),
				array('n', 'N', 'c', 'C',),
				$string
		);
	
		if ($sense_espais == true) {
			//Esta parte se encarga de eliminar cualquier caracter extraño
			$string = str_replace(
					array("\\", "¨", "º", "-", "~",
							"#", "@", "|", "!", "\"",
							"·", "$", "%", "&", "/",
							"(", ")", "?", "'", "¡",
							"¿", "[", "^", "`", "]",
							"+", "}", "{", "¨", "´",
							">", "< ", ";", ",", ":",
							".", " "),
					"_",
					$string
			);
		}
	
	
		return $string;
	}
}
