<?php
namespace FecdasBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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

include_once (__DIR__.'/../../../vendor/tcpdf/include/tcpdf_static.php');

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
	const DIES_PENDENT_MAX = 10;
	const ID_LLICENCIES_DIA = 11;	
    const INICI_VALIDACIO_MAIL = '2016-09-01'; // A partir d'aquesta data cal indicar mail per tramitar (excepte llicència dia)
	const INICI_TRAMITACIO_ANUAL_DIA = 15; // a partir de 15/12 any en curs
	const INICI_TRAMITACIO_ANUAL_MES = 12; // a partir de 15/12 any en curs
	const INICI_REVISAR_CLUBS_DAY = '01';
	const INICI_REVISAR_CLUBS_MONTH = '04';
	const DATES_INFORME_TRIMESTRAL = '31/03;30/06;30/09;30/11';
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
	const INDEX_ENVIARLLICENCIA = 1;
    const INDEX_NOENVIARLLICENCIA = 0;
    
	//const TIPUS_CLUBS_NO_COMANDES = array(6, 7);
	const REGISTRE_STOCK_ENTRADA = 'E';
	const REGISTRE_STOCK_SORTIDA = 'S';
	
	const TARIFA_MINPES3 = 10000; // 10 Kg
	const TARIFA_MINPES2 = 5000; // 5 Kg
	const PRODUCTE_CORREUS = 7590004;	// Abans juliol 2016 => 6290900 / 6290004
	const TARIFA_TRANSPORT1 = 6.00; // Tarifa <= 5 Kg (6.00 €)
	const TARIFA_TRANSPORT2 = 12.00; // Tarifa > 5 Kg i < 10 Kg (12.00€)
	const TARIFA_TRANSPORT3 = 18.00; // Tarifa > 10 Kg (18.00€)
	
	const PREFIX_ASSENTAMENTS = 'APU';  // Prefix del fitxer
	// Fitxer domiciliacions
	const PATH_TO_COMPTA_FILES = '/../../../fitxers/assentaments/';
	// Pedent de canviar fora document root
	const PATH_TO_WEB_FILES = '/../../../web/';
	
	const TIPUS_PRODUCTE_LLICENCIES = 1;
	const TIPUS_PRODUCTE_DUPLICATS 	= 2;
	const TIPUS_PRODUCTE_KITS 		= 3;
	const TIPUS_PRODUCTE_MERCHA 	= 4;
	const TIPUS_PRODUCTE_CURSOS 	= 5;
	const TIPUS_PRODUCTE_ALTRES 	= 6;
	
	const DEBE 		= 'D';
	const HABER 	= 'H';
	
	const CODI_FECDAS 					= 'CAT999';		
	const CODI_CLUBTEST					= 'CAT000';
	const CODI_PAGAMENT_CASH 			= 5700000;		// 5700000  Metàl·lic
	const CODI_PAGAMENT_CAIXA			= 5720001;		// 5720001  La Caixa
	const CODI_PAGAMENT_ESCOLA			= 5720002;		// 5720002  La Caixa Escola
	
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
	
	const REBUTS	= 1;
	const FACTURES	= 2;
	const COMANDES	= 3;
	
	const ANY_INICI_WEB	= 2012;
	
	const PREFIX_ALBARA_DUPLICATS = 'D';
	const PREFIX_ALBARA_LLICENCIES = 'L';
	const PREFIX_ALBARA_ALTRES = 'A';
	
	// Templates plàstic
	const TEMPLATE_GENERAL = 'G0';
	const TEMPLATE_TIPUS_F = 'F0';
	const TEMPLATE_TECNOCAMPUS_1 = 'T1';
	const TEMPLATE_TECNOCAMPUS_2 = 'T2';
	const TEMPLATE_ESCOLAR = 'ES';
	const TEMPLATE_ESCOLAR_SUBMARINISME = 'CS';
	
	// Docs assegurança
	const POLISSA_BUSSEIG = 'polissa_busseig_2017.pdf';
	const POLISSA_TECNOCAMPUS = 'polissa_tecnocampus.pdf';
	const POLISSA_ESCOLAR = 'polissa_escolar.pdf';
	
	const COMUNICAT_INCIDENT_POLISSA_BUSSEIG = 'comunicat_incident_polissa_busseig.pdf';
	const COMUNICAT_INCIDENT_POLISSA_TECNOCAMPUS = 'comunicat_incident_polissa_tecnocampus.pdf';
	const COMUNICAT_INCIDENT_POLISSA_ESCOLAR = 'comunicat_incident_polissa_escolar.pdf';

	const PROTOCOL_INCIDENTS_POLISSA_BUSSEIG = 'protocol_incidents_polissa_busseig.pdf';
	const PROTOCOL_INCIDENTS_POLISSA_TECNOCAMPUS = 'protocol_incidents_polissa_tecnocampus.pdf';
	const PROTOCOL_INCIDENTS_POLISSA_ESCOLAR = 'protocol_incidents_polissa_escolar.pdf';

	
	// Templates plàstic
	const CARREC_PRESIDENT = 1;
	const CARREC_VICEPRESIDENT = 2;
	const CARREC_SECRETARI = 3;
	const CARREC_TRESORER = 4;
	const CARREC_VOCAL = 9;
	
	// Duplicats llicències
	//const CODI_DUPLICAT_LLICENCIA = 7090000;
	const CODI_DUPLICAT_LLICENCIA = 7050102; 	

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
	
	
	// Titulacions
	const TIPUS_TITOL_BUSSEIG	= 'BU';
	const TIPUS_TITOL_TECNIC	= 'TE';
	const TIPUS_ESPECIALITAT	= 'ES';
	const TIPUS_MONITOR			= 'MO';
	const ORGANISME_CMAS		= 'CMAS';
	
	// Rols docents
	const DOCENT_DIRECTOR		= 'Director';
	const DOCENT_CODIRECTOR		= 'Co-director';
	const DOCENT_INSTRUCTOR		= 'Instructor';
	const DOCENT_COLABORADOR	= 'col·laborador';
	
	// Context requeriments títols 
	const CONTEXT_REQUERIMENT_ALUMNES	= 'alumnes';	// Aplica als alumnes del curs
	const CONTEXT_REQUERIMENT_GENERAL	= 'general';	// Aplica a les dades del curs 
	const CONTEXT_REQUERIMENT_DOCENTS	= 'docents';	// Aplica als docents del curs

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
					self::TIPUS_PRODUCTE_KITS 		=> 'Kits',
					self::TIPUS_PRODUCTE_MERCHA 	=> 'Merchandising',
					self::TIPUS_PRODUCTE_CURSOS 	=> 'Cursos',
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
	public static function getArrayAnysPreus($inici = self::ANY_INICI_WEB) {
		$final = date('Y') + 1;
		
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
	
	public static function getLlistaTipusParte($club, $dataconsulta) {
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
				if ($tipusparte->getEs365() == true) {
					/* 365 directament sempre. Es poden usar en qualsevol moment  */
					array_push($llistatipus, $tipusparte->getId());
				} else {
					$inici = '01-01';
					$final = '12-31';
					if ($tipusparte->getInici() != null) $inici = $tipusparte->getInici();
					if ($tipusparte->getFinal() != null) $final = $tipusparte->getFinal();
						
					if ($currentmonthday >= $inici and $currentmonthday <= $final) {
						array_push($llistatipus, $tipusparte->getId());
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
		$options = array( 	'role' => '', 'authenticated' => false, 'admin' => false, 
							'roleclub' => false, 'roleinstructor' => false, 'rolefederat' => false,
							'userclub' => '', 'currentclubnom' => '',
							'allowcomandes' => false, 'busseig' => false, 
							'enquestausuari' => '', 'enquestausuaripendent' => '',
							'cartItems'		=> 0 );
		
		if ($this->isAuthenticated()) {
			$checkRole = $this->get('fecdas.rolechecker');
			
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
					'choices' => self::getRoles(true),				// Llista de rols sense clubs
					'data' => $checkRole->getCurrentRole()			// Rol actual de l'usuari
				));
				
			} else {
				$formBuilder->add('currentrole', 'choice', array(			
					'choices' => $checkRole->getUserRolesArray(),	// Llista de parelles rol - club
					'data' => $checkRole->getUserRoleKey()			// Rol actual de l'usuari
				));
				
				$userclub = $this->getCurrentClub();
				if ($userclub) $options['userclub'] = $userclub->getNom(); 	
			}
			
			$options['roleform'] = $formBuilder->getForm()->createView();
			$options['currentclubnom'] = $this->getCurrentClub()!=null?$this->getCurrentClub()->getNom():'';
				
			$cart = $this->getSessionCart();
			$options['cartItems'] = count( $cart['productes'] );
		}
		
		return  array_merge($more, $options);
	}
	
	
	protected function addClubsActiusForm($formBuilder, $club, $nom = 'clubs') {
			
		$formBuilder->add($nom, 'entity', array(
				'class' 		=> 'FecdasBundle:EntityClub',
				'query_builder' => function($repository) {
						return $repository->createQueryBuilder('c')
								->orderBy('c.nom', 'ASC')
								->where('c.databaixa IS NULL')
								->where('c.activat = 1');
						}, 
				'choice_label' 	=> 'nom',
				'placeholder' 	=> '',	// Important deixar en blanc pel bon comportament del select2
				'required'  	=> false,
				'data' 			=> $club,
		));
		
	}

	protected function addTitolsForm($formBuilder, $titol, $cmas = true, $nom = 'titols') {
			
		$formBuilder->add($nom, 'entity', array(
				'class' 		=> 'FecdasBundle:EntityTitol',
				'query_builder' => function($repository) use ($cmas) {
						if ($cmas) {
							return $repository->createQueryBuilder('t')
									->orderBy('t.titol', 'ASC')
									->where('t.organisme = \''.BaseController::ORGANISME_CMAS.'\'')
									->where('t.actiu = 1');
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
	
	protected function isAuthenticated() {
		
		$checkRole = $this->get('fecdas.rolechecker');
		
		return $checkRole->isAuthenticated();
	}
	
	protected function isCurrentAdmin() {
			
		$checkRole = $this->get('fecdas.rolechecker');
		
		return $checkRole->isCurrentAdmin();
	}

	public static function esFederacio($club) {
		return $club->getCodi() == self::CODI_FECDAS;
	}

	protected function getCurrentClub() {
		if (!$this->isAuthenticated()) return null;
		
		$em = $this->getDoctrine()->getManager();
		
		$checkRole = $this->get('fecdas.rolechecker');
		
		$codiClub = $checkRole->getCurrentClubRole();
		$club =	$em->getRepository('FecdasBundle:EntityClub')->find( $codiClub );
		
		return $club;
	}

	protected function allowComandes() {
		if ($this->isAuthenticated() != true) return false;
		
		$club = $this->getCurrentClub();
		
		if ($club == null) return false;
		
		if (in_array( $club->getTipus()->getId(), self::getTipusClubsNoComandes() )) return false;
		
		return true;		
	}
	
	protected function getAdminMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array($this->getParameter('MAIL_ADMINTEST'));
		
		$mails = array($this->getParameter('MAIL_LLICENCIES'), $this->getParameter('MAIL_ADMIN'));
		return $mails;
	}
	
	protected function getFacturacioMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array($this->getParameter('MAIL_ADMINTEST'));
		
		$mails = array($this->getParameter('MAIL_FACTURACIO'), $this->getParameter('MAIL_LLICENCIES'));
		return $mails;
	}

	protected function getLlicenciesMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array($this->getParameter('MAIL_ADMINTEST'));
		
		$mails = array($this->getParameter('MAIL_LLICENCIES'));
		return $mails;
	}
	
	protected function getCarnetsMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array($this->getParameter('MAIL_ADMINTEST'));
	
		$mails = array($this->getParameter('MAIL_CARNETS'));
		return $mails;
	}
	
	protected function getContactMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array($this->getParameter('MAIL_ADMINTEST'));
		
		$mails = array($this->getParameter('MAIL_CONTACTE'), $this->getParameter('MAIL_ADMINTEST'), $this->getParameter('MAIL_LLICENCIES'), $this->getParameter('MAIL_CARNETS'));
		return $mails;
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
		
        if ($llicencia->getEnviarllicencia() == null || $llicencia->getEnviarllicencia() == 0) $llicencia->setEnviarllicencia(false); 
        else $llicencia->setEnviarllicencia(true);

		$dataalta = $parte->getDataalta();
		$tipus = $parte->getTipus();
		$current = $this->getCurrentDate();
		$errData = $this->validaDataLlicencia($dataalta, $tipus);
			
		if ($errData != "") throw new \Exception($errData);
		// NO llicències amb data passada. Excepte administradors
		// Data alta molt aprop de la caducitat.

		if ($dataalta->format('y') > $current->format('y')) {
			// Només a partir 10/12 poden fer llicències any següent
			if ($current->format('m') < self::INICI_TRAMITACIO_ANUAL_MES ||
				($current->format('m') == self::INICI_TRAMITACIO_ANUAL_MES &&
				 $current->format('d') < self::INICI_TRAMITACIO_ANUAL_DIA)) {
				throw new \Exception('Encara no es poden tramitar llicències per a l\'any vinent');
			}
		}
		
        /* Validar persones noves (alta > 2016-09-01 si tenen mail informat 
         * Llicències diferents de la diària
         */
        if ($tipus->getId() != self::ID_LLICENCIES_DIA) {
            $persona = $llicencia->getPersona();
            if ($persona->getDataentrada()->format('Y-m-d') > self::INICI_VALIDACIO_MAIL) {
                if ($persona->getMail() == null || $persona->getMail() == "") 
                    throw new \Exception("El federat ".$persona->getNomCognoms()." no té indicada adreça de correu electrònica");
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
		if ($tipus->getActiu() == false) throw new \Exception('Aquest tipus de llicència no es pot tramitar. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
		/* Fi modificacio 12/12/2014. Missatge no es poden tramitar */

		// Comprovar data llicències reduïdes. Alta posterior 01/09 any actual
		$datainici_reduida = new \DateTime(date("Y-m-d", strtotime($dataalta->format('Y') . "-09-01")));
		if (($tipus->getId() == 5 or $tipus->getId() == 6) &&
			($dataalta->format('Y-m-d') < $datainici_reduida->format('Y-m-d'))) { // reduïdes
			throw new \Exception('Les llicències reduïdes només a partir de 1 de setembre');
		}
			
		if ($this->validaLlicenciaInfantil($parte, $llicencia) == false) throw new \Exception('L\'edat de la persona ('.$llicencia->getPersona()->getDni().') no correspon amb el tipus de llicència');
						
		if ($this->validaPersonaRepetida($parte, $llicencia) == false) throw new \Exception('Aquesta persona ('.$llicencia->getPersona()->getDni().') ja té una llicència en aquesta llista');

		// Comprovar que no hi ha llicències vigents 
		// Per la pròpia persona
		$parteoverlap = $this->validaPersonaTeLlicenciaVigent($llicencia, $llicencia->getPersona()); 
		if ($parteoverlap != null) throw new \Exception($llicencia->getPersona()->getNomCognoms(). ' - Aquesta persona ja té una llicència per a l\'any actual en aquest club, en data ' . 
															$parteoverlap->getDataalta()->format('d/m/Y'));

		$datainiciRevisarSaldos = new \DateTime(date("Y-m-d", strtotime(date("Y") . "-".self::INICI_REVISAR_CLUBS_MONTH."-".self::INICI_REVISAR_CLUBS_DAY)));
			
		if ($current->format('Y-m-d') >= $datainiciRevisarSaldos->format('Y-m-d') && 
			$parte->getClub()->controlCredit() == true) {
			// Comprovació de saldos clubs DIFE
/***************  SALDOS ENCARA NO ************************************************************************************************************************/					
			/*if ($parte->getPreuTotal() > $parte->getClub()->getSaldo() + $parte->getClub()->getLimitcredit()) {
					throw new \Exception('L\'import de les tramitacions que heu fet a dèbit en aquest sistema ha arribat als límits establerts.
					Per poder fer noves gestions, cal que contacteu amb la FECDAS');
			}*/
		}
	}
 
	protected function validaDataLlicencia(\DateTime $dataalta, $tipus) {
		$avui = $this->getCurrentDate('now');
		if (!$this->isCurrentAdmin() and $dataalta < $avui) return 'No es poden donar d\'alta ni actualitzar llicències amb data passada';
		
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
		if ($parte->getTipus()->getId() == self::ID_LLICENCIES_DIA) return true; // Llicències Dia no aplica

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
		foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
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
		foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
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
		$fivigencia_nova = $llicencia->getParte()->getDataCaducitat($this->getLogMailUserData("validaPersonaTeLlicenciaVigent outer "));
	
		foreach ($lpersonaarevisar as $c => $llicencia_iter) {
			if ($llicencia_iter->getId() != $llicencia->getId() and
				$llicencia_iter->getDatabaixa() == null ) {
				// No comprovo la pròpia llicència
	
				$inicivigencia_existent = $llicencia_iter->getParte()->getDataalta();
	
				// Cal anar en compte, les llicències importades tenen un dia més
				//$fivigencia_existent = $llicencia_iter->getDatacaducitat();
				$fivigencia_existent = $llicencia_iter->getParte()->getDataCaducitat($this->getLogMailUserData("validaPersonaTeLlicenciaVigent inner "));
	
				// Comprovar si sol·lapen
				if (($fivigencia_nova >= $inicivigencia_existent) &&
					($inicivigencia_nova <= $fivigencia_existent)) {
					return $llicencia_iter->getParte(); // Error, sol·lapen
				}
			}
		}
		return null;
	}
	
	protected function consultaPartesRecents($club, $estat, $baixa, $nopagat, $noimpres, $compta, 
											$numfactura, $anyfactura, $numrebut, $anyrebut, $strOrderBY = '') {
		$em = $this->getDoctrine()->getManager();
	
		$anulaIds = array();
		
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p JOIN p.llicencies l JOIN p.tipus t JOIN p.club c JOIN c.estat e ";
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
		
		if ($club != null) $strQuery .= " AND p.club = '" .$club->getCodi() . "' "	;
		if ($estat != self::TOTS_CLUBS_DEFAULT_STATE) $strQuery .= " AND e.descripcio = :filtreestat ";
		
		
		if ($baixa == false) $strQuery .= " AND p.databaixa IS NULL AND l.databaixa IS NULL ";
		if ($nopagat == true) $strQuery .= " AND p.rebut IS NULL ";
		//if ($noimpres == true) $strQuery .= " AND (p.impres IS NULL OR p.impres = 0) AND p.pendent = 0 AND t.template IN (:templates)";
		// No impreses totes excepte sense template => Llicències de dia
		if ($noimpres == true) $strQuery .= " AND l.impresa = 0 AND l.mailenviat = 0 AND p.pendent = 0 AND t.template <> ''";
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
	
	
	protected function consultaPartesClub($club, $tipus, $desde, $fins, $strOrderBY = '') {
		$em = $this->getDoctrine()->getManager();
	
		// Consultar no només les vigents sinó totes
		$strQuery = "SELECT p, COUNT(l.id) AS HIDDEN numllicencies FROM FecdasBundle\Entity\EntityParte p JOIN p.llicencies l JOIN p.tipus t ";
		$strQuery .= "WHERE p.club = :club ";
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
	
	protected function consultaAssegurats($tots, $dni, $nom, $cognoms, $desde, $fins, $vigent = true, $strOrderBY = '') { 
		$em = $this->getDoctrine()->getManager();
	
		$current = $this->getCurrentDate();
		if ($vigent == true) {
			$strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e JOIN e.llicencies l JOIN l.parte p ";
			$strQuery .= " WHERE e.databaixa IS NULL AND l.databaixa IS NULL AND p.databaixa IS NULL ";
			$strQuery .= " AND p.pendent = 0 ";
			$strQuery .= " AND p.dataalta <= :currenttime ";
			$strQuery .= " AND l.datacaducitat >= :currentdate ";
		} else {
		    if ($desde != null || $fins != null) { 
    			$strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e JOIN e.llicencies l JOIN l.parte p ";
    			$strQuery .= " WHERE e.databaixa IS NULL AND p.databaixa IS NULL ";
    			$strQuery .= " AND p.pendent = 0 ";
    			if ($desde != null) $strQuery .= " AND p.dataalta >= :desde ";
    			if ($fins != null) $strQuery .= " AND p.dataalta <= :fins ";
            } else {
                $strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e ";
                $strQuery .= " WHERE e.databaixa IS NULL ";
            }
		}
		
		if ($tots == false) $strQuery .= " AND e.club = :club ";
		if ($dni != "") $strQuery .= " AND e.dni LIKE :dni ";
		if ($nom != "") $strQuery .= " AND e.nom LIKE :nom ";
		if ($cognoms != "") $strQuery .= " AND e.cognoms LIKE :cognoms ";

		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  // Només per PDF el paginator ho fa sol mentre el mètode de crida sigui POST
		
		$query = $em->createQuery($strQuery);
				
		// Algun filtre
		$query = $em->createQuery($strQuery);
		if ($tots == false) $query->setParameter('club', $this->getCurrentClub()->getCodi());
		if ($dni != "") $query->setParameter('dni', "%" . $dni . "%");
		if ($nom != "") $query->setParameter('nom', "%" . $nom . "%");
		if ($cognoms != "") $query->setParameter('cognoms', "%" . $cognoms . "%");
		if ($vigent == true) {
			$query->setParameter('currenttime', $current->format('Y-m-d').' 00:00:00');
			$query->setParameter('currentdate', $current->format('Y-m-d'));
		} else {
			if ($desde != null) $query->setParameter('desde', $desde->format('Y-m-d').' 00:00:00');
			if ($fins != null) $query->setParameter('fins', $fins->format('Y-m-d').' 23:59:59');
		}
	
		return $query;
	}
	
	protected function getMaxNumEntity($year, $tipus) {
		$em = $this->getDoctrine()->getManager();
	
		$inici = $year."-01-01 00:00:00";
		$final = $year."-12-31 59:59:59";
	
		$strQuery = '';
		switch ($tipus) {
			case BaseController::REBUTS:
				$strQuery = "SELECT MAX(r.num) FROM FecdasBundle\Entity\EntityRebut r ";
				$strQuery .= " WHERE r.datapagament >= '".$inici."' AND r.datapagament <= '".$final."'";
				break;
			case BaseController::FACTURES:
				$strQuery = " SELECT MAX(f.num) FROM FecdasBundle\Entity\EntityFactura f ";
				$strQuery .= " WHERE f.datafactura >= '".$inici."' AND f.datafactura <= '".$final."'";
				break;
			case BaseController::COMANDES:
				$strQuery = " SELECT MAX(c.num) FROM FecdasBundle\Entity\EntityComanda c ";
				$strQuery .= " WHERE c.dataentrada >= '".$inici."' AND c.dataentrada <= '".$final."'";
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

	protected function crearFactura($data, $comanda = null, $concepte = '', $compte = '') { 
		if ($data == null) $data = $this->getCurrentDate();
		
		if ($compte == '') $compte = $this->getIbanGeneral();
		
		$em = $this->getDoctrine()->getManager();

		$maxNumFactura = $this->getMaxNumEntity($data->format('Y'), BaseController::FACTURES) + 1;
		
		$factura = new EntityFactura($data, $maxNumFactura, $comanda, 0, $concepte, null, $compte);
		
		$em->persist($factura);
		if ($comanda != null) {
			$factura->setComanda($comanda);
			$factura->setComandaanulacio(null);
			$comanda->setFactura($factura);
		}
		
		return $factura;
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
		$h_logos = 23;
		$h_fedelogo = 24;
		$w_fedelogo = 20;
		$h_genelogo = 12;
		$w_genelogo = 35;
		$h_esportlogo = 12;
		$w_esportlogo = 30;
		
		$y_fedeinfo = $y_margin;
		$x_fedeinfo = $l_margin + $w_half;
		$h_fedeinfo = 42;
		$y_clubinfo = $y_margin + $h_logos;
		$x_clubinfo = $l_margin;
		$h_clubinfo = 41;
		$y_factuinfo = $y_margin + $h_fedeinfo;
		$offset_factuinfo = 30;
		$x_factuinfo = $l_margin + $w_half+$offset_factuinfo;
		$h_factuinfo = 22;
		$y_taula = $y_margin + 64;
		$x_taula = $l_margin;
		$h_taula = 145;
		$y_taula2 = $y_taula + 78;
		$y_rebut = $y_factuinfo + $h_factuinfo + $h_taula;
		$x_rebut = $l_margin;
		$h_rebut = 55;

		$y = $y_clubinfo; //$pdf->getY();
		$x = $x_clubinfo; //$pdf->getX();

		
		//$showTemplate = !$this->isCurrentAdmin(); // Remei no mostrar elements fixes
		$showTemplate = ($this->get('session')->get('username', '') != $this->getParameter('MAIL_FACTURACIO')) &&
						($this->get('session')->get('username', '') != $this->getParameter('MAIL_FACTURACIO2'));  
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
			$pdf->Image('images/fecdaslogopdf.gif', $x_logos, $y_logos, 
						$w_fedelogo, 0 , 'gif', '', 'LT', true, 320, 
						'', false, false, array(''),
						'LT', false, false);
			$pdf->Image('images/logo-generalitat.jpg', $x_logos+$w_fedelogo+2, $y_logos, 
						$w_genelogo, 0 , 'jpeg', '', 'T', true, 320, 
						'', false, false, array(''),
						'CT', false, false);
			$pdf->Image('images/esport-logo.jpg', $x_logos+$w_fedelogo+4.5, $y_logos+$h_genelogo, 
						$w_esportlogo, 0 , 'jpeg', '', 'B', true, 320, 
						'', false, false, array(''),
						'CB', false, false);
				
			/* FEDE INFO */
			
			$tbl = '<p align="right" style="padding:0;"><span style="font-size:16px;">FEDERACIÓ CATALANA<br/>D\'ACTIVITATS SUBAQUÀTIQUES</span><br/>';
			$tbl .= '<span style="font-size:11px;">Moll de la Vela, 1 (Zona Forum)<br/>';
			$tbl .= '08930 Sant Adrià de Besòs<br/>';
			$tbl .= 'Tel: 93 356 05 43 / Fax: 93 356 30 73<br/>';
			$tbl .= 'Adreça electrònica: info@fecdas.cat<br/>';
			$tbl .= 'www.fecdas.cat<br/>';
			$tbl .= 'NIF: Q5855006B</span></p>';
			$pdf->writeHTMLCell($w_half+5, $h_fedeinfo, $x_fedeinfo, $y_fedeinfo, $tbl, '', 1, false, true, 'R', false);
		}	
		
		/* CLUB INFO */	
		$pdf->SetTextColor(0, 0, 0); // Negre	
		$pdf->SetFontSize(16);
		if ($factura->esAnulacio() == true) {
			$text = '<br/><b>FACTURA ANUL·LACIÓ</b>';
			
			$pdf->writeHTMLCell($w_half, 0, $x_clubinfo, $y_clubinfo, $text, '', 1, false, true, 'L', false);
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
		$pdf->MultiCell($w_half,5,$club->getNom(),0,'L',false, 1, $x_clubinfo, $y_clubinfo + 10, true, 3, false, true, 5, 'M', true);
		$pdf->SetFont('freesans', '', 11, '', true);
		$pdf->MultiCell($w_half,5,$club->getAddradreca(),0,'L',false, 1, $x_clubinfo, $y_clubinfo + 15, true, 3, false, true, 5, 'M', true);
		$pdf->MultiCell($w_half,5,$club->getAddrcp() . " - " . $club->getAddrpob(),0,'L',false, 1, $x_clubinfo, $y_clubinfo + 20, true, 3, false, true, 5, 'M', true);
		$pdf->MultiCell($w_half,5,$club->getAddrprovincia(),0,'L',false, 1, $x_clubinfo, $y_clubinfo + 25, true, 3, false, true, 5, 'M', true);
		if ($club->getTelefon() != null && $club->getTelefon() > 0) $pdf->MultiCell($w_half,5,'Telf: ' . $club->getTelefon(),0,'L',false, 1, $x_clubinfo, $y_clubinfo + 30, true, 3, false, true, 5, 'M', true);
		
		/* FACTU INFO */	
		$pdf->SetFontSize(8);
		if ($showTemplate == true) {
			$tbl = '<p align="left" style="padding:0; color: #003366;">Factura número:</p><br/>';
			$tbl .= '<p align="left" style="padding:0; color: #003366;">Data:<br/>';
			$tbl .= 'NIF:</p>';
			$pdf->writeHTMLCell($w_half - $offset_factuinfo, 0, $x_factuinfo, $y_factuinfo, $tbl, '', 1, false, true, 'R', false);
		}
		
		$tbl  = '<p align="right" style="padding:0;"><b>' . $factura->getNumfactura(). '</p><br/>';
		$tbl .= '<p align="right" style="padding:0;">' . $factura->getDatafactura()->format('d/m/Y') . '<br/>';
		$tbl .= $club->getCif() . '</p>';
		$pdf->writeHTMLCell($w_half - $offset_factuinfo-8, 0, $x_factuinfo+8, $y_factuinfo, $tbl, '', 1, false, true, 'R', false);
		
		/* TAULA DETALL */
		$pdf->SetFontSize(8);
		$facturaSenseIVA = true;
		if ($factura->getDetalls() != null) {
			
			if ($showTemplate == true) {	
				$tbl = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
						<tr>
						<td width="96" align="center" style="border: 1px solid #003366; color:#003366;">REFERÈNCIA</td>
						<td width="240" align="center" style="border: 1px solid #003366; color:#003366;">CONCEPTE</td>
						<td width="58" align="center" style="border: 1px solid #003366; color:#003366;">QUANT.</td>
						<td width="81" align="center" style="border: 1px solid #003366; color:#003366;">PREU</td>
						<td width="86" align="center" style="border: 1px solid #003366; color:#003366;">IMPORT</td>
						</tr>';
				
				// En blanc
				$tbl .= '<tr>';
				$tbl .= '<td style="height: 257px; border: 1px solid #003366;" align="center">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="left">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="center">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="right">&nbsp;</td>';
				$tbl .= '<td style="border: 1px solid #003366;" align="right">&nbsp;</td>';
				$tbl .= '</tr>';
			
				$tbl .= '</table>';
				
				$pdf->writeHTMLCell(0, 0, $x_taula, $y_taula, $tbl, '', 2, false, true, 'L', false);
				
				$tbl = '<table border="0" cellpadding="2" cellspacing="0" style="border-color: #003366; border-collapse: collapse; ">
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
				$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 1, false, true, 'L', false);
			}
			
			// Sistema nou del 2015.
			//$detallsArray = json_decode($factura->getDetalls(), false, 512, JSON_UNESCAPED_UNICODE);
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
			
			if ($detallsArray == true) {
			
				foreach ($detallsArray as $lineafactura) {
					if ($lineafactura['ivaunitat'] > 0) $facturaSenseIVA = false;
						
					$preuSenseIVA = $lineafactura['total'] * $lineafactura['preuunitat'];
					
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
					$pdf->MultiCell($w_preuu, $row_h, number_format($lineafactura['preuunitat'], 2, ',', '.').'€', 0, 'C', true, 0, $x_preuu, $row_y, 
									true, 0, false, true, $row_h, 'T', true);
	
					$pdf->SetFont('', 'B');
					$pdf->MultiCell($w_import, $row_h, number_format($lineafactura['import'], 2, ',', '.').'€', 0, 'C', true, 2, $x_import, $row_y, 
									true, 0, false, true, $row_h, 'T', true);
	
					$pdf->SetFont('', '');
	
					$strExtra = '';
					if (isset($lineafactura['extra']) && is_array($lineafactura['extra'])) {  // Noms persones llicències
						$strExtra = '';
						foreach ($lineafactura['extra'] as $extra) {
							//$strExtra .= '<br/> -&nbsp;'.$extra;
							$strExtra .= $extra.', ';
						}
						if (count($lineafactura['extra']) > 0) $strExtra = substr($strExtra, 0, -2); 
						$strExtra .= '';
					}	
					
					$row_h_extra = $row_h;
					
					if (count($lineafactura['extra']) > 30 || $strExtra == '') {
						$strExtra = '';
					} else {
						if (count($lineafactura['extra']) <= 10) $row_h_extra = 7;	
						else $row_h_extra = max($row_h_extra_max * count($lineafactura['extra']) / 30, 10);
					}				
	
					$row_y = $pdf->getY();
					$pdf->SetFont('', 'I', 7);
					$pdf->SetTextColor(100, 100, 100); //Gris
					
					$pdf->MultiCell($w_producte - 4, $row_h_extra, $strExtra, 0, 'L', true, 2, $x_producte + 2, $row_y, 
									true, 0, false, true, $row_h_extra, 'T', true);
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
				else $strConcepte = 'Comanda '.$comanda->getNumComanda().' '.$comanda->getDataentrada()->format('d/m/Y');
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
			
			$pdf->SetFont('', '', 12);
			//$pdf->SetFontSize(12);
			// PEU 1 TOTAL PARCIAL
			$pdf->setY($y_taula2+5);
			$tbl = '<table border="0" cellpadding="5" cellspacing="0"><tr>
					<td width="96" align="center">'.number_format($factura->getImport(), 2, ',', '.').' €</td>
					<td width="95" align="center">--</td>
					<td width="145" align="center">&nbsp;</td>
					<td width="90" align="center">&nbsp;</td>
					<td width="135" align="center"><span style="font-weight:bold;">'.number_format($factura->getImport(), 2, ',', '.').' €</span></td>
					</tr></table>';	
			
			$pdf->writeHTMLCell($w_half*2 -5, 0, $x_taula, $pdf->getY(), $tbl, '', 2, false, true, 'L', false);
			
			$pdf->SetFontSize(16);
			// PEU 2 - TOTAL FINAL
			$pdf->setY($y_taula2+26);
			$tbl = '<table border="0" cellpadding="5" cellspacing="0"><tr>
					<td width="96">&nbsp;</td><td width="95">&nbsp;</td><td width="145">&nbsp;</td><td width="90">&nbsp;</td>
					<td width="135" align="center"><span style="font-weight:bold;">'.number_format($factura->getImport(), 2, ',', '.').' €</span></td>
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
		
		if ($facturaSenseIVA == true) {
			// set color for text
			$pdf->SetTextColor(0, 51, 102); // Blau
			$pdf->SetFont('dejavusans', '', 7.5, '', true);
			$text = '<p>Factura exempta d\'I.V.A. segons la llei 49/2002</p>';
			$pdf->writeHTMLCell($w_half*2, 0, $x_taula, $y_taula2+26, $text, '', 1, false, true, 'L', false);
		}
		
		$pdf->SetTextColor(0, 0, 0); // Negre
		$pdf->SetFontSize(8);

		$compte = $factura->getNumcompte() != ''?$factura->getNumcompte():$this->getIbanGeneral();

		$text = 'Número de compte corrent LA CAIXA IBAN '.$compte;
		
		$pdf->writeHTMLCell($w_half*2, 0, $x_taula, $y_taula2+31, $text, '', 1, false, true, 'L', false);

		
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
		
		$h_fedelogo = 12;
		$w_fedelogo = 10;
		$h_genelogo = 6;
		$w_genelogo = 16;
		$h_esportlogo = 6;
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
		$showTemplate = ($this->get('session')->get('username', '') != $this->getParameter('MAIL_FACTURACIO')) &&
						($this->get('session')->get('username', '') != $this->getParameter('MAIL_FACTURACIO2'));  
		$showTemplate = true; // De moment imprimir sense plantilla
		
		if ($showTemplate == true) {
			/* LOGOS */		
			// Start Transformation
	    	$pdf->StartTransform();
	    	// Rotate 90 degrees
	   		$pdf->Rotate(90, $l_margin + $rx_corp , $y_corp + $ry_corp);
			
			// file, x, y, w, h, format, link,  alineacio, resize, dpi, palign, mask, mask, border, fit, hidden, fitpage, alt, altimg			
			$pdf->Image('images/fecdaslogopdf.gif', $x_corp, $y_corp, 
							$w_fedelogo, 0 , 'gif', '', 'LT', true, 320, 
							'', false, false, array(''),
							'LT', false, false);
			$pdf->Image('images/logo-generalitat.jpg', $x_corp+$w_fedelogo+2, $y_corp, 
							$w_genelogo, 0 , 'jpeg', '', 'T', true, 320, 
							'', false, false, array(''),
							'CT', false, false);
			$pdf->Image('images/esport-logo.jpg', $x_corp+$w_fedelogo+4.5, $y_corp+$h_genelogo, 
							$w_esportlogo, 0 , 'jpeg', '', 'B', true, 320, 
							'', false, false, array(''),
							'CB', false, false);
			
			/* FEDE INFO */
			$txt = '<p align="left" style="padding:0;line-height: 1"><span style="font-size:12px;">FEDERACIÓ CATALANA<br/>D\'ACTIVITATS SUBAQUÀTIQUES</span><br/>';
			$txt .= '<span style="font-size:6.5px;">Moll de la Vela, 1 (Zona Forum)<br/>';
			$txt .= '08930 Sant Adrià de Besòs<br/>';
			$txt .= 'Tel: 93 356 05 43 / Fax: 93 356 30 73<br/>';
			$txt .= 'Adreça electrònica: info@fecdas.cat<br/>';
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
		
		$pdf->SetTextColor(0, 0, 0); // Negre	
		$pdf->MultiCell(80,0,$club->getNom(),0,'L',true, 1, $x_header_row1 + 25, $y_header_row2 - 0.5, true, 3, false, true, 5, 'M', true); // Amplada variable
		$pdf->SetTextColor(0, 51, 102); // Blau fosc 003366		
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">NIF:&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'.$club->getCif().'</span></p>';
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
		if ($rebut->getComentari()!=null && $rebut->getComentari() != '') { 
			$concepte .= '<br/>'.$rebut->getComentari();
		}
		
		if ($showTemplate == true) {
			$txt = '<p align="left" style="padding:0;">en concepte de:</p>';
			$pdf->writeHTMLCell(0, 0, $x_concepte, $y_concepte, $txt, '', 1, false, true, 'L', false);
			$pdf->Rect($x_concepte + $x_concepte_offset, $y_concepte + $y_concepte_offset, $w_concepte , $h_concepte , '', 
					array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 51, 102))), '' );
		}
		$txt = '<p style="color:#000000; font-size:14px; ">'.$concepte.'</p>';
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
		
		$mesData = $rebut->getDatapagament()->format('m');
		$litDe = "de ";
		if ($mesData == 4 || $mesData == 8 || $mesData == 10) $litDe = "d''";
		
		$formatter = new \IntlDateFormatter('ca_ES.utf8', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		$formatter->setPattern("eeee d '".$litDe."'MMMM "."'de'"." yyyy");
		$dateFormated = $formatter->format($rebut->getDatapagament());


		$pdf->setFontStretching(100);
		
		$txt = '<p align="left" style="padding:0;'.$hideText.'">Sant Adrià del Besòs, &nbsp;&nbsp;&nbsp;';
		$txt .= '<span style="color:#000000; font-size:12px;">'. $dateFormated .'</span></p>';
		$pdf->writeHTMLCell(0, 0, $x_total, $y_total+$y_total_offset_2, $txt, '', 1, false, true, 'L', false);
		
		  
		// reset pointer to the last page
		$pdf->lastPage();
		
		return $pdf;
	
	}
	
	protected function textLlicenciaG0mail( $cursAny ) {
		$subject = "Federació Catalana d'Activitats Subaquàtiques. Llicència federativa ".$cursAny;
		
		$body = "<div style=''><p>Benvolguda, benvolgut,</p>";
		$body .= "<p style='text-align: justify;'>Vull donar-te la benvinguda al nou model de llicència de la FECDAS, que espero que sigui del teu grat.</p>";
		$body .= "<p style='text-align: justify;'>Qualsevol comentari que ens vulguis fer, el tindrem molt en compte!</p>";
		$body .= "<p style='text-align: justify;'>De la nostra banda, t’agraïm un cop mes la confiança que diposites en el teu club i en la FECDAS.</p>";
		$body .= "</div>";
		
		$salutacio = "<p>Rep una abraçada!</p>";
		$salutacio .= "<p>Salvador Punsola<br/>";
		$salutacio .= "President de la FECDAS</p>";
		
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


	private function textLlicenciaFecdasmail( $curs ) {
		$subject = "Federació Catalana d'Activitats Subaquàtiques. Llicència federativa curs ".$curs;
		
		$body = "<div style=''><p>Benvolgut/da esportista</p>";
		$body .= "<p style='text-align: justify;'>Amb aquest mateix correu reps la teva llicència esportiva corresponent a la temporada ".$curs."</p>";
		$body .= "<p style='text-align: justify;'>La FECDAS ha fet un nou pas endavant en el procés constant de millora i ha intensificat la seva relació amb el món digital.</p>";
		$body .= "<p style='text-align: justify;'>Amb la digitalització de la llicència esportiva pretenem facilitar-ne l'ús i, també, posar a la teva disposició de manera senzilla tota la informació que hi està relacionada.</p>";
		$body .= "<p style='text-align: justify;'>La teva llicència digital permet accedir a la pòlissa que et dóna cobertura; al protocol de relació amb l'asseguradora i al full de comunicat d’incidents.</p>";
		$body .= "<p style='text-align: justify;'>Aquests documents els tens a l'abast a través dels hipervincles corresponents.</p>";
		$body .= "<p style='text-align: justify;'>T'agraïm la confiança que diposites en la FECDAS; t'animem a competir amb il·lusió i ens posem a la teva disposició per al que et calgui.</p></div>";
		
		$salutacio = "<p>Cordialment,</p>";
		$salutacio .= "<p>Salvador Punsola<br/>";
		$salutacio .= "President</p>";
		
		return array(
			'subject' 	=> 	$subject,
			'body' 		=>	$body,
			'greeting'	=> 	$salutacio
		);
	}


	protected function textLlicenciaT1mail( $curs ) {
		$subject = "AVÍS IMPORTANT: Assegurança  accidents TecnoCampus ".substr($curs,2);  // p.e. Curs 16-17
		
		$body = "<div style=''><p>Hola,</p>";
		$body .= "<p style='text-align: justify;'>T'enviem en el  document adjunt el teu carnet digital vinculat a la teva assegurança acadèmica d'accidents del curs ".$curs."</p>";
		$body .= "<p style='text-align: justify;'>El carnet, personalitzat t'identifica com a contractant d'una assegurança amb l'empresa Mútuacat";
		$body .= " i et dóna accés als documents que hi estan relacionats: la pòlissa (inclou els centres mèdics on pots adreçar-te),";
		$body .= " el protocol que cal seguir si es produeix algun incident i el comunicat que, un cop emplenat,";
		$body .= " cal fer arribar a la companyia d'assegurances.</p></div>";
		
		$salutacio = "<p>Salutacions cordials,</p>";
		$salutacio .= "FECDAS</p>";
		
		return array(
			'subject' 	=> 	$subject,
			'body' 		=>	$body,
			'greeting'	=> 	$salutacio
		);
	}

	protected function textLlicenciaT2mail( $curs ) {
		$subject = "AVÍS IMPORTANT: Assegurança  accidents TecnoCampus ".substr($curs,2);  // p.e. Curs 16-17
		
		$body = "<div style=''><p>Hola,</p>";
		$body .= "<p style='text-align: justify;'>T'enviem en el  document adjunt el teu carnet digital vinculat a la teva assegurança acadèmica d'accidents del curs ".$curs."</p>";
		$body .= "<p style='text-align: justify;'>El carnet, personalitzat t'identifica com a contractant d'una assegurança amb l'empresa Mútuacat";
		$body .= " i et dóna accés als documents que hi estan relacionats: la pòlissa (inclou els centres mèdics on pots adreçar-te),";
		$body .= " el protocol que cal seguir si es produeix algun incident i el comunicat que, un cop emplenat,";
		$body .= " cal fer arribar a la companyia d'assegurances.</p></div>";
		$body .= "<p style='text-align: justify;'>En el cas que cursis l'assignatura de Subaquàtiques, comptes amb una altra assegurança";
		$body .= " –amb l'empresa Helvetia- vinculada a aquesta pràctica esportiva.</p>";
		$body .= "<p style='text-align: justify;'>En aquest cas, el teu carnet digital compte amb dos jocs d'hipervincles:</p>";
		$body .= "<p style='text-align: justify;'>1.- El primer joc està relacionat amb l'assegurança acadèmica d'accidents bàsica.</p>";
		$body .= "<p style='text-align: justify;'>2.- El segon joc, distingit amb el mot \"busseig\",";
		$body .= " et dóna accés als documents que estan relacionats amb la pràctica de les activitats subaquàtiques:";
		$body .= " la pòlissa d'Helvetia, el protocol amb Helvetia, que cal seguir si es produeix algun incident i el comunicat que,";
		$body .= " un cop emplenat, cal fer arribar a la companyia d'assegurances Helvetia.</p>";
		
		$salutacio = "<p>Salutacions cordials,</p>";
		$salutacio .= "FECDAS</p>";
		
		return array(
			'subject' 	=> 	$subject,
			'body' 		=>	$body,
			'greeting'	=> 	$salutacio
		);
	}
	
	protected function printLlicenciaG0pdf( $llicencia ) {
		$yLinks = 77;
		$links = array(	array('text' => 'pòlissa', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_BUSSEIG)),
				array('text'=> 'protocol', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_BUSSEIG)),
				array('text' => 'comunicat', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_BUSSEIG)));
				
		$pdf = $this->printDigitalFecdas( $llicencia, $links, $yLinks, BaseController::TEMPLATE_GENERAL );
		
		return $pdf;
	}
	
	protected function printLlicenciaESpdf( $llicencia ) {
		$yLinks = 77;
		$links = array(	array('text' => 'pòlissa', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_ESCOLAR)),
				array('text'=> 'protocol', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_ESCOLAR)),
				array('text' => 'comunicat', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_ESCOLAR)));
				
		$pdf = $this->printDigitalFecdas( $llicencia, $links, $yLinks, BaseController::TEMPLATE_ESCOLAR );
		
		return $pdf;
	}
	
	protected function printLlicenciaCSpdf( $llicencia ) {
		$yLinks = 67;
		$links = array(	array('text' => 'pòlissa', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_ESCOLAR)),
						array('text'=> 'protocol', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_ESCOLAR)),
						array('text' => 'comunicat', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_ESCOLAR)),
						array('text'=> 'pòlissa busseig', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_BUSSEIG)),
						array('text'=> 'protocol busseig', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_BUSSEIG)),
						array('text'=> 'comunicat busseig', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_BUSSEIG)));
				
		$pdf = $this->printDigitalFecdas( $llicencia, $links, $yLinks, BaseController::TEMPLATE_ESCOLAR_SUBMARINISME );
		
		return $pdf;
	}
		
	private function printDigitalFecdas( $llicencia, $links, $yLinks, $template ) {
		
		// Paper cordinates are calculated in this way: (inches * 72) where (1 inch = 25.4 mm)
		// Definir paper 13,3'' => 29cmx17cm (WxH) en 16:9
		// Definir paper 7'' => => 15cmx9cm (WxH) en 16:9
		
		$width = 150; 
		$height = 90; 
		$x_titols = 5;
		

		// Posicions
		$xTit = 0;
		$yTit =	($template == BaseController::TEMPLATE_GENERAL?12:8);
		$offset = ($template == BaseController::TEMPLATE_GENERAL?3:5);		
		$yNom =	40-$offset;		
		$yDni =	46-$offset;	
		$yCat =	52-$offset;		
		$yNai =	58-$offset;
		if ($template == BaseController::TEMPLATE_GENERAL) $yCad = 71-$offset;		
		else $yCad = (count($links) <= 3 ? 72-$offset: $yNai);
		$yClu =	64-$offset;		
		$yTlf =	64-$offset;	

		// Links docs
		$x = $x_titols + 5;
		$y = $yLinks;
		$yOffset = 10;
		$hLink = 6.8;
		$buttonsMargin = 5;

		$pageLayout = array($width, $height); //  or array($height, $width) 
		$pdf = new TcpdfBridge('L', PDF_UNIT, $pageLayout, true, 'UTF-8', false);
				
		$pdf->init(array('author' => 'FECDAS',
						'title' => 'Llicència Curs Escolar FECDAS' . date("Y")));

		$pdf->setPrintFooter(false);
		$pdf->setPrintHeader(false);
				
		// zoom - layout - mode
		$pdf->SetDisplayMode('real', 'SinglePage', 'UseNone');
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(5, 5, 5);
		$pdf->SetAutoPageBreak 	(false, 5);
		//$pdf->SetMargins(0, 0, 0);
		//$pdf->SetAutoPageBreak 	(false, 0);
		$pdf->SetTextColor(255, 255, 255); 
			

		$pdf->AddPage('L', $pageLayout);
		
		$srcImatge = ($template == BaseController::TEMPLATE_GENERAL?'images/fonsgeneral_1024x664.jpg':'images/fonsanysescolar_1024x664.jpg');
		
		$pdf->Image($srcImatge, 0, 0, $width, $height , 'jpg', '', '', false, 320, 
						'', false, false, 1, false, false, false);
		
		$parte = $llicencia->getParte();
		$polissa = $parte->getTipus()->getPolissa();
		
		// Dades
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat('printparte');
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);
				
		$pdf->SetTextColor(0, 0, 255);
		$pdf->SetTextColor(255,255,255);
		
//		$pdf->SetFillColor(224,224,224);
//		$pdf->SetAlpha(0.7);
	
		$pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.5, 'depth_h' => 0.5, 'color' => array(53,153,179), 'opacity' => 0.75, 'blend_mode' => 'Normal'));
		if ($template == BaseController::TEMPLATE_GENERAL) {
			$pdf->SetFont('dejavusans', 'B', 14, '', true);
		} else {
			$pdf->SetFont('dejavusans', 'B', 15, '', true);
		}
		$pdf->setFontStretching(100);		
		//$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C', 0, 0, $xTit, $yTit);

//		$pdf->SetAlpha(1);
		$pdf->SetFont('dejavusans', 'B', 11);
		$pdf->SetTextColor(255, 255, 255);

		$pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.3, 'depth_h' => 0.3, 'color' => array(53,153,179), 'opacity' => 0.6, 'blend_mode' => 'Normal'));		
		
		$pdf->writeHTMLCell(0, 0, $x_titols, $yNom, '<span style="font-size: small;">Nom: </span>'.$persona->getNomCognoms(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yDni, '<span style="font-size: small;">DNI/Passaport: </span>'.$persona->getDni(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yCat, '<span style="font-size: small;">Categoria/Nivell: </span>'.$llicencia->getCategoria()->getCategoria(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yNai, '<span style="font-size: small;">Data Naixement: </span>'.$persona->getDatanaixement()->format('d/m/Y'), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yClu, '<span style="font-size: small;">Entitat: </span>'.$parte->getClub()->getNom(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yCad, '<span style="font-size: small;">Vàlida fins/Valid until: </span>'. $datacaduca->format('d/m/Y'), 0, 0, false, true, 'R', true);
		
		$pdf->SetFont('dejavusans', 'B', 10);
		
		$pdf->writeHTMLCell(0, 0, $x_titols, $yTlf, '<span style="font-size: small;">Telf. Entitat: </span>'.$parte->getClub()->getTelefon(), 0, 0, false, true, 'R', true);

		//$pdf->setFontSpacing(0.5);
    	//$pdf->setFontStretching(80);

		//Disable
		$pdf->setTextShadow(array('enabled'=>false));
				
		$margins = $pdf->getMargins();
		$width = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
		
		$pdf->SetTextColor(53,153,179);
		$pdf->SetFont('helvetica', 'B', 10, '', true);

		$margins = $pdf->getMargins();
		$width = $pdf->getPageWidth() - $margins['left'] - $margins['right']; 
		$wLink = $width/3 - 2 * $buttonsMargin;


		for ($i=0; $i < count($links); $i++) {
			$pdf->Image('images/white_button.png', $x, $y, $wLink, $hLink , 'png', $links[$i]['link'], 
					'', true, 320, '', false, false, 0, false, false, false);
			//$pdf->setPageMark(); 
			$pdf->MultiCell($wLink, $hLink, $links[$i]['text'], 0, 'C', 0, 0, $x, $y, true, 0, false, true, $hLink, 'M', true);

			if ($i == 2) {
				$y += $yOffset;
				$x = $x_titols + 5;
			} else {
				$x += $wLink + (2 * $buttonsMargin);
			}
		}
		
		
		// reset pointer to the last page
		$pdf->lastPage();
		
		return $pdf;
	}	
		
		
	protected function printLlicenciaT1pdf( $llicencia ) {
	
		$yLinks = 70;
		$links = array(	array('text' => 'pòlissa', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_TECNOCAMPUS)),
						array('text'=> 'protocol', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_TECNOCAMPUS)),
						array('text' => 'comunicat', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_TECNOCAMPUS)));
						
		$pdf = $this->printDigitalTecnocampus( $llicencia, $links, $yLinks );
		
		return $pdf;
	}
	
	protected function printLlicenciaT2pdf( $llicencia ) {
	
		$yLinks = 67;
		$links = array(	array('text' => 'pòlissa', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_TECNOCAMPUS)),
						array('text'=> 'protocol', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_TECNOCAMPUS)),
						array('text' => 'comunicat', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_TECNOCAMPUS)),
						array('text'=> 'pòlissa busseig', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::POLISSA_BUSSEIG)),
						array('text'=> 'protocol busseig', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::PROTOCOL_INCIDENTS_POLISSA_BUSSEIG)),
						array('text'=> 'comunicat busseig', 'link'=> $this->getRequest()->getUriForPath('/media/asseguranca/'.BaseController::COMUNICAT_INCIDENT_POLISSA_BUSSEIG)));
		
	
		$pdf = $this->printDigitalTecnocampus( $llicencia, $links, $yLinks );
		
		return $pdf;
	}
	
	private function printDigitalTecnocampus( $llicencia, $links, $yLinks ) {
		// Paper cordinates are calculated in this way: (inches * 72) where (1 inch = 25.4 mm)
		// Definir paper 13,3'' => 29cmx17cm (WxH) en 16:9
		// Definir paper 7'' => => 15cmx9cm (WxH) en 16:9

		// Posicions
		$xTit = 0;
		$yTit =	18;		
		$yNom =	35;		
		$yDni =	40;		
		$yCad =	56.2;
		
		// Títols
		$x_titols = 11;

		// Links docs
		$x = $x_titols;
		$y = $yLinks;
		$yOffset = 10;
		$hLink = 6.8;
		$buttonsMargin = 5;
				
		$width = 150; 
		$height = 90; 
		
		$pageLayout = array($width, $height); //  or array($height, $width) 
		$pdf = new TcpdfBridge('L', PDF_UNIT, $pageLayout, true, 'UTF-8', false);
				
		$pdf->init(array('author' => 'FECDAS',
						'title' => 'Llicència Tecnocampus FECDAS' . date("Y")));

		$pdf->setPrintFooter(false);
		$pdf->setPrintHeader(false);
				
		// zoom - layout - mode
		$pdf->SetDisplayMode('real', 'SinglePage', 'UseNone');
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(5, 5, 5);
		$pdf->SetAutoPageBreak 	(false, 5);
		//$pdf->SetMargins(0, 0, 0);
		//$pdf->SetAutoPageBreak 	(false, 0);
		$pdf->SetTextColor(255, 255, 255); 
			

		$pdf->AddPage('L', $pageLayout);
		
		$pdf->Image('images/fonstecnocampus_910x600.jpg', 0, 0, 
						$width, $height , 'jpg', '', '', false, 320, 
						'', false, false, 1, false, false, false);
		
		$parte = $llicencia->getParte();
		$polissa = $parte->getTipus()->getPolissa();
		
		// Dades
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat('printparte');
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);
				
		$pdf->SetFont('dejavusans', 'B', 15, '', true);
		$pdf->setFontStretching(100);		
		$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

		$pdf->SetFont('dejavusans', 'B', 12);

		$pdf->writeHTMLCell(0, 0, $x_titols, $yNom, '<span style="font-size: small;">Nom: </span>'.$persona->getNomCognoms(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yDni, '<span style="font-size: small;">DNI/Passaport: </span>'.$persona->getDni(), 0, 0, false, true, 'L', true);
		$pdf->writeHTMLCell(0, 0, $x_titols, $yCad, '<span style="font-size: small;">Vàlida fins/Valid until: </span>'. $datacaduca->format('d/m/Y'), 0, 0, false, true, 'R', true);
		
		//$pdf->setFontSpacing(0.5);
    	//$pdf->setFontStretching(80);
		
		$pdf->SetTextColor(199,132,1);
		$pdf->SetFont('helvetica', 'B', 10, '', true);

		$margins = $pdf->getMargins();
		$width = $pdf->getPageWidth() - $margins['left'] - $margins['right']; 
		$wLink = $width/3 - 2 * $buttonsMargin;


		for ($i=0; $i < count($links); $i++) {
			$pdf->Image('images/white_button.png', $x, $y, $wLink, $hLink , 'png', $links[$i]['link'], 
					'', true, 320, '', false, false, 0, false, false, false);
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
		if ($datacaduca == null) $datacaduca = $parte->getDatacaducitat('titolPlastic');
		$anyFinalLlicencia = $datacaduca->format('Y');
		$tipus = $parte->getTipus();
	
		$titolPlastic = mb_strtoupper($tipus->getTitol(), 'UTF-8');
	
		$titolPlastic = str_replace("__DESDE__", $anyLlicencia, $titolPlastic);
		$titolPlastic = str_replace("__FINS__", $anyFinalLlicencia, $titolPlastic);*/
	
		$tipus = $parte->getTipus();
		$titolPlastic = mb_strtoupper($tipus->getTitol(), 'UTF-8');
	
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
						'title' => 'Llicència FECDAS' . date("Y")));

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
			
		$width = 86; //Original
		$height = 54; //Original

		foreach ($llicencies as $llicencia) {
			$parte = $llicencia->getParte();
								
			// Add a page
			$pdf->AddPage('L', 'BUSINESS_CARD_ISO7810');

			if ($parte->getTipus()->getTemplate() == BaseController::TEMPLATE_GENERAL ||
				$parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TIPUS_F ||
				$parte->getTipus()->getTemplate() == BaseController::TEMPLATE_ESCOLAR) $this->printPlasticGeneral($pdf, $llicencia);
				
			if ($parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_1 ||
				$parte->getTipus()->getTemplate() == BaseController::TEMPLATE_TECNOCAMPUS_2) {
					//$this->printPlasticGeneral($pdf, $llicencia);
					$this->printPlasticTecnocampus($pdf, $llicencia);
			}
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
		$yTit =	12+3.2;		
		$xNom = 10-1.5;
		$yNom =	27.4+0.7;		
		$xDni = 18;
		$yDni =	32.1;		
		$xCat = 20.5-1;
		$yCat =	36.7-0.3;		
		$xNai = 19.5;
		$yNai =	41.1-0.5;		
		$xClu = 12-2.5;
		$yClu =	45.6-1.0;		
		$xTlf = 15;
		$yTlf =	50.1-1.3;		
		$xCad = 61-4;
		$yCad =	50.1-1.3;
		
		/*********** Alex Test  *************/
		/*$srcImatge = 'images/federativa_2017_anvers_test.jpg';
		$width = 86; //Original
		$height = 54; //Original
		$pdf->Image($srcImatge, 0, 0, $width, $height , 'jpg', '', '', false, 320, 
						'', false, false, 1, false, false, false);
		//$pdf->SetTextColor(255, 0, 0); 
		$pdf->setCellHeightRatio(1.1);
		// php  vendor/tcpdf/tools/tcpdf_addfont.php -i RobotoCondensed-Regular.ttf,RobotoCondensed-Italic.ttf
		// ls -l vendor/tcpdf/fonts

		//$fontname = TCPDF_FONTS::addTTFfont('/home/alex/Android/Sdk/platforms/android-23/data/fonts/DroidSans.ttf', 'OpenTypeUnicode', '', 32);
		//$pdf->SetFont($fontname, 'B', 10);	*/
		/*********** Alex Test Fi *************/
		
		$parte = $llicencia->getParte();
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat('printparte');
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);

		//$pdf->SetFont('helvetica', 'B', 10, '', true);
		$pdf->SetFont('helvetica', 'B', 10);
		//$pdf->setFontSpacing(0.5);
    	$pdf->setFontStretching(90);
				
		$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

		//$pdf->SetFont('dejavusans', 'B', 8);
		$pdf->SetFont('helvetica', 'B', 9);
		$pdf->setFontStretching(100);

		$pdf->SetXY($xNom, $yNom);
		$pdf->Cell(0, 0, $persona->getNomCognoms(), 0, 1, 'L');

		$pdf->SetXY($xDni, $yDni);
		$pdf->Cell(0, 0, $persona->getDni(), 0, 1, 'L');

		$pdf->SetXY($xCat, $yCat);
		$pdf->Cell(0, 0, $llicencia->getCategoria()->getCategoria(), 0, 1, 'L');
				
		$pdf->SetXY($xNai, $yNai);
		$pdf->Cell(0, 0, $persona->getDatanaixement()->format('d/m/Y'), 0, 1, 'L');
				
		$pdf->SetXY($xClu, $yClu);
		$pdf->Cell(0, 0, $parte->getClub()->getNom(), 0, 1, 'L');

		$pdf->SetXY($xTlf, $yTlf);
		$pdf->Cell(0, 0, $parte->getClub()->getTelefon(), 0, 1, 'L');
				
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
		$polissa = $parte->getTipus()->getPolissa();
		
		// Dades
		$pdf->SetFont('helvetica', 'B', 9, '', true);
		$pdf->setFontStretching(90);		
		$pdf->SetXY($xPol, $yPol);
		$pdf->MultiCell(0,0,'Número de pòlissa: '.$polissa,0,'C',false);		
		
		$persona = $llicencia->getPersona();
		if ( $persona == null) return;
		
		$datacaduca = $parte->getDatacaducitat('printparte');
		$titolPlastic = $this->getTitolPlastic($parte, $datacaduca);
				
		$pdf->SetFont('helvetica', 'B', 10, '', true);
		$pdf->setFontStretching(100);		
		$pdf->SetXY($xTit, $yTit);
		$pdf->MultiCell(0,0,$titolPlastic,0,'C',false);

		$pdf->SetFont('dejavusans', 'B', 8);

		$pdf->SetXY($xNom, $yNom);
		$pdf->Cell(0, 0, $persona->getNomCognoms(), 0, 1, 'L');

		$pdf->SetXY($xDni, $yDni);
		$pdf->Cell(0, 0, $persona->getDni(), 0, 1, 'L');

		$pdf->SetXY($xCat, $yCat);
		$pdf->Cell(0, 0, $llicencia->getCategoria()->getCategoria(), 0, 1, 'L');
				
		$pdf->SetXY($xNai, $yNai);
		$pdf->Cell(0, 0, $persona->getDatanaixement()->format('d/m/Y'), 0, 1, 'L');
				
		$pdf->SetXY($xClu, $yClu);
		$pdf->Cell(0, 0, $parte->getClub()->getNom(), 0, 1, 'L');

		$pdf->SetFont('dejavusans', 'B', 7);
		$pdf->SetXY($xTlf, $yTlf);
		$pdf->Cell(0, 0, $parte->getClub()->getTelefon(), 0, 1, 'L');
				
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
	
	protected function crearComanda($data, $comentaris = '', $factura = null) {
		if ($data == null) $data = $this->getCurrentDate();
	
		$em = $this->getDoctrine()->getManager();
	
		$maxNumComanda = $this->getMaxNumEntity($data->format('Y'), BaseController::COMANDES) + 1;

		$comanda = new EntityComanda($maxNumComanda, $factura, $this->getCurrentClub(), $comentaris);

		$em->persist($comanda);

		if ($factura != null) {
			$factura->setComanda($comanda);
			$comanda->setFactura($factura);
		}
	
		return $comanda;
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
		}

		$comanda->setDatamodificacio($this->getCurrentDate());
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
		
		
		// Actualitzar import i detalls factura
		$factura->setImport($parte->getTotalDetalls());
				
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
					
				$detalls = $parte->getDetallsAcumulats();
				//$factura->setDetalls(json_encode($detalls, JSON_UNESCAPED_UNICODE)); // Desar estat detalls a la factura
				$factura->setDetalls(json_encode($detalls)); // Desar estat detalls a la factura
				$factura->setConcepte($parte->getConcepteComanda());
			}
		}
	}

    protected function baixaComanda($comanda, $dataFacturacio = null) {
        if ($dataFacturacio == null) $dataFacturacio = $this->getCurrentDate();
        
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
            $detallsBaixa = array();
            $extra = array();
            foreach ($comanda->getDetalls() as $detall) {
                if (!$detall->esBaixa()) {
                    $detallsBaixa[] = $this->removeComandaDetall($comanda, $detall->getProducte(), $detall->getUnitats());
                }
            }
            
            if (count($detallsBaixa) > 0) {
                $maxNumFactura = $this->getMaxNumEntity($dataFacturacio->format('Y'), BaseController::FACTURES) + 1;
                $maxNumRebut = $this->getMaxNumEntity($dataFacturacio->format('Y'), BaseController::REBUTS) + 1;
            
                $this->crearFacturaRebutAnulacio($dataFacturacio, $comanda, $detallsBaixa, $maxNumFactura, $maxNumRebut, $extra); 
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
				$factura->setComanda(null);
				$parte->setFactura(null);
				//$parte->setDatabaixa($current);
				$em->remove($factura);
				//$em->flush();
			}
			 
			// Actualitzar import i detalls factura
			if ($factura != null) {
				$factura->setImport($parte->getTotalDetalls());
				
				$detalls = $parte->getDetallsAcumulats();
				//$factura->setDetalls(json_encode($detalls, JSON_UNESCAPED_UNICODE)); // Desar estat detalls a la factura
				$factura->setDetalls(json_encode($detalls)); // Desar estat detalls a la factura
				$factura->setConcepte($parte->getConcepteComanda());
			}
			
		} else {
			// Consolidada, crear factura anul·lació					
			if (count($detallsBaixa) == 0) throw new \Exception('No ha estat possible esborrar les llicències. Si us plau, contacteu amb la FECDAS –93 356 05 43– per a més informació');
		
			if ($dataFacturacio == null) $dataFacturacio = $current;
			$maxNumFactura = $this->getMaxNumEntity($dataFacturacio->format('Y'), BaseController::FACTURES) + 1;
			$maxNumRebut = $this->getMaxNumEntity($dataFacturacio->format('Y'), BaseController::REBUTS) + 1;
							
			$this->crearFacturaRebutAnulacio($dataFacturacio, $parte, $detallsBaixa, $maxNumFactura, $maxNumRebut, $extra);
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

		// Restaurar stock
		if ($producte->getStockable() == true) $producte->setStock($producte->getStock() + $unitats);
				
		return $detallBaixa;
	}
	
	protected function crearFacturaRebutAnulacio($datafactura, $comanda, $detalls, &$maxNumFactura = 0, &$maxNumRebut = 0, $extra = '') {

		if ($comanda == null || !is_array($detalls) || count($detalls) == 0) return null; // Error
				 
		if ($datafactura == null) $datafactura = $this->getCurrentDate();
		
		$em = $this->getDoctrine()->getManager();

		$import = 0;
		$concepte = 'Anul·lació. ';
		$detallsFactura = array();
		foreach ($detalls as $detall) {
			$producte = $detall->getProducte();
			$unitats = $detall->getUnitats();
			
			$import += $detall->getTotal(true);
			
			$concepte .= $unitats.'x'.$producte->getDescripcio().' ';
			
			//$detallsFactura[$producte->getCodi()] = $detall->getDetallsArray(true);
			$detallsFactura[$producte->getId()] = $detall->getDetallsArray(true);
	
			if ($extra != '' && is_array($extra) && isset($extra[$producte->getId()])) {
			//if ($extra != '' && is_array($extra) && isset($extra[$producte->getCodi()])) {
				//$detallsFactura[$producte->getCodi()]['extra'] = $extra[$producte->getCodi()];
				$detallsFactura[$producte->getId()]['extra'] = $extra[$producte->getId()];
			}
		}

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

		if ($maxNumFactura == 0) {
			$maxNumFactura = $this->getMaxNumEntity($datafactura->format('Y'), BaseController::FACTURES) + 1;
			//$persist = true;
		}
		
		$factura = new EntityFactura($datafactura, $maxNumFactura, $comanda, $import, $concepte, $detallsFactura, $this->getIbanGeneral());
		
		$em->persist($factura);
		
		$maxNumFactura++;
				
		$comanda->addFacturaanulacio($factura);
		$factura->setComandaanulacio($comanda);
		$factura->setComanda(null);

		/*if ($comanda->comandaPagada()) {

			$tipuspagament = $comanda->getRebut()->getTipuspagament();
			
			$em = $this->getDoctrine()->getManager();
			
			if ($maxNumRebut == 0) $maxNumRebut = $this->getMaxNumEntity($data->format('Y'), BaseController::REBUTS) + 1;
			$rebut = new EntityRebut($data, $tipuspagament, $maxNumRebut, $comanda, $comanda->getClub(), $import); 
			
			$maxNumRebut++;
			
			$em->persist($rebut);
		}*/
		$comanda->setDatamodificacio(new \DateTime());
		
		//if ($persist == true) $em->flush();	// Si d'ha canviat el num factura	
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
	
		$data = $this->getCurrentDate();
		$maxNumFactura = $this->getMaxNumEntity($data->format('Y'), BaseController::FACTURES) + 1;
		$maxNumRebut = $this->getMaxNumEntity($data->format('Y'), BaseController::REBUTS) + 1;
		
		// Nous detalls, baixes i validació
		$formdetalls = null;				
		if ($form != null) $formdetalls = $form->get('detalls');
		
		$productesNotificacio = array();
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

			if ($comanda->esNova() == true) {
				
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
				// Només es pot treure productes (anul·lacions) de la comanda. No cal controlar stock
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
					
					$detallsPerAnulacio[] = new EntityComandaDetall($comanda, $producte, $unitatsDiferencia*(-1), $detall->getDescomptedetall(), $anotacioModificada);
				}
			}
			
			$detall->setDatamodificacio(new \DateTime());
			
		}

		if (count($detallsPerAnulacio) > 0) {
			$this->crearFacturaRebutAnulacio($this->getCurrentDate(), $comanda, $detallsPerAnulacio, $maxNumFactura, $maxNumRebut);
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
	
	protected function addDuplicatDetall($duplicat, $datafacturacio = null) {
		if ($duplicat == null || $duplicat->getCarnet() == null) return null;
		
		$producte = $duplicat->getCarnet()->getProducte();
		
		if ($producte == null) return null;
		
		$anotacions = '1x'.$producte->getDescripcio();  
		
		$detall = $this->addComandaDetall($duplicat, $producte, 1, 0, $anotacions);

		if ($detall != null) $duplicat->setComentaris($duplicat->getComentariDefault());
		
		// Actualitzar import i detalls factura
		if ($datafacturacio == null) $datafacturacio = $this->getCurrentDate();
		
		$factura = $this->crearFactura($datafacturacio, $duplicat, $duplicat->getComentariDefault());
		
		return $detall;
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
	
	protected function getTarifaTransport($pes)
    {
		if (!is_numeric($pes)) return 0;
		if ($pes <= 0) return 0;

    	if ($pes > self::TARIFA_MINPES3) return self::TARIFA_TRANSPORT3;
		if ($pes > self::TARIFA_MINPES2) return self::TARIFA_TRANSPORT2;
		
        return self::TARIFA_TRANSPORT1;
    }

	protected function getPesComandaCart($cart)
    {
		$pesComanda = 0;
		foreach ($cart['productes'] as $id => $info) {
			if (isset($info['transport']) && $info['transport'] == true)	{
				$pesComanda += $info['pes'];
			}
		}
		return $pesComanda;
    }
	
	protected function getTotalComandaCart($cart)
    {
		$total = 0;
		foreach ($cart['productes'] as $info) {
			$total += $info['unitats']*$info['import'];
		}
		return $total;
    }
	
	protected function getSessionCart()
    {
		// Recollir cistella de la sessió
		$session = $this->get('session');
		$cart = $session->get('cart', array('productes' => array(), 'tarifatransport' => 0)); // Crear cistella buida per defecte
		
        return $cart;
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
		
		$saldosComptables = array();
		
		if ($data == null || $club == null) return $saldosComptables;
		
		if ($data->format('Y') < $club->getExercici()) return $saldosComptables; // No es pot consultar per dates anteriors a l'inici de l'exercici
		
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
			$currentClub = $variacio['codi'];
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
	

	protected function saldosEntre($desde, $fins, $club) {
		$em = $this->getDoctrine()->getManager();
		
		// Consultar saldos entre dates per un club
		$strQuery  = " SELECT s FROM FecdasBundle\Entity\EntitySaldos s ";
		$strQuery .= " WHERE s.dataregistre >= :desde ";
		$strQuery .= " AND   s.dataregistre <=  :fins ";
		if ($club != null) $strQuery .= " AND   s.club = :club ";
		$strQuery .= " ORDER BY s.dataregistre ASC ";
			
		$query = $em->createQuery($strQuery);
		if ($club != null) $query->setParameter('club', $club->getCodi() );
		$query->setParameter('desde', $desde->format('Y-m-d') );
		$query->setParameter('fins',  $fins->format('Y-m-d') );
		
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
		$strQuery .= " WHERE r.dataentrada >= :desde ";
		$strQuery .= " AND   r.dataentrada <=  :fins ";
		if ($codiclub != '') $strQuery .= " AND   r.club = :club ";
			
		$query = $em->createQuery($strQuery);
		$query->setParameter('desde', $desde->format('Y-m-d H:i:s') );
		$query->setParameter('fins',  $fins->format('Y-m-d H:i:s') );
		if ($codiclub != '') $query->setParameter('club', $codiclub  );
		
		$rebuts = $query->getResult();
		
		return $rebuts;
	}

	protected function rebuts2016pagats2015($codiclub = '') {
		$em = $this->getDoctrine()->getManager();
		
		$iniciSaldos = '2016-01-01 00:00:00';
		
		// Consultar factures entrades entrats dia current
		$strQuery  = " SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.dataentrada >= :canviany ";
		$strQuery .= " AND   r.datapagament < :canviany ";
		if ($codiclub != '') $strQuery .= " AND r.club = :club ";
		$strQuery .= " ORDER BY r.num ";
			
		$query = $em->createQuery($strQuery);
		$query->setParameter('canviany', $iniciSaldos );
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
		
		// Comprovar si existeix registre
		$registre = $this->getDoctrine()->getRepository('FecdasBundle:EntitySaldos')->findOneBy(array('club' => $club->getCodi(), 'dataregistre' => $data ));
				
		if ($registre == null) {
			if ($data->format('Y-m-d') < $current->format('Y-m-d')) {
				// Moviment a data passada que no està registrada no es crea registre.
				return;
			} 		

			$registre = new EntitySaldos($club, $data);
			$em->persist($registre);
		}

		$registre->setEntrades( $registre->getEntrades() + $entrada);
		$registre->setSortides( $registre->getSortides() + $sortida);
	}

	protected function exportCSV($request, $header, $data, $filename) {

    	$csvTxt = '"'.iconv('UTF-8', 'ISO-8859-1//TRANSLIT',implode('";"',$header)).'"'.CRLF;
    	
    	$infoseccionsCSV = array();
    	foreach ($data as $k => $row) {
    		$row = '"'.implode('";"', $row).'"';
    		
    		$csvTxt .= iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row.CRLF);
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

	protected function getProvincies() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT distinct m.provincia FROM FecdasBundle\Entity\EntityMunicipi m
				ORDER BY m.provincia");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$provincies[$res['provincia']] = $res['provincia'];
		return $provincies;
	}
	
	protected function getComarques() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT distinct m.comarca FROM FecdasBundle\Entity\EntityMunicipi m
				ORDER BY m.comarca");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$comarques[$res['comarca']] = $res['comarca'];
		return $comarques;
	}

	protected function getMunicipis() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT distinct m.municipi FROM FecdasBundle\Entity\EntityMunicipi m
				ORDER BY m.municipi");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$municipis[$res['municipi']] = $res['municipi'];
		return $municipis;
	}
	
	protected function getNacions() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT n FROM FecdasBundle\Entity\EntityNacio n
				ORDER BY n.codi");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$nacions[$res->getCodi()] = $res->getCodi() . ' - ' . $res->getPais();
		return $nacions;
	}
	
	protected function getClubsSelect() {
		$em = $this->getDoctrine()->getManager();
	
		$query = $em->createQuery("SELECT c FROM FecdasBundle\Entity\EntityClub c
				ORDER BY c.nom");
		$clubs = $query->getResult();
	
		$clubsvalues = array();
		foreach ($clubs as $c => $v) $clubsvalues[$v->getCodi()] = $v->getLlistaText();
	
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
			
			foreach ($result as $c => $res) {
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
				
			foreach ($result as $c => $res) {
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
		
		if (strtoupper($cadena[strlen($cadena) - 1]) != $lletra) return false;
			
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
	
	protected function getTempUploadDir()
	{
		/* Temporary upload folder. Variable __DIR__ és el directori del fitxer */
		return __DIR__.self::TMP_FOLDER;
	}
	
	protected function logEntryAuth($accio = null, $extrainfo = '') {
		$request = $this->container->get('request_stack')->getCurrentRequest();
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
		$logentry = new EntityUserLog(substr($user,0,50), substr($accio,0,20), substr($remoteaddr,0,20), substr($useragent,0,100), substr($extrainfo,0,100));
		$em->persist($logentry);
		try {
			$em->flush();
		} catch (\Exception $e) {
			error_log ("APP FECDAS > Error saving app log to mysql: ".$e->getMessage(), 0);
		}
	}
	
	protected function getLogMailUserData($source = null) {
		$request = $this->container->get('request_stack')->getCurrentRequest();
		$checkRole = $this->get('fecdas.rolechecker');
		
		return $source." ".$checkRole->getCurrentUserName()." (".$checkRole->getCurrentHTTPAgent().")";
	}
	
    protected function validateCronAuth($request, $action = '') {
        $cronsecret = $this->getParameter( 'secret', '' ); 
        $secret = $request->query->get('secret', '');
        
        if ($secret == '' || $secret != $cronsecret) {
            // mail admin
            $this->logEntryAuth('AUTH ERROR NOTIFICA', "ERROR ".$action.": ".$secret.' <> '.$cronsecret);
            $errorAuth = " Error auth ".$action." ".$secret." <> ".$cronsecret."<br/>";
            echo $errorAuth;
            
            $tomails = self::getAdminMails();
            $subject = "Federació Catalana d'Activitats Subaquàtiques. ERROR ".$action;
        
            $this->buildAndSendMail($subject, $tomails, $errorAuth);
            
            throw new \Exception("ERROR ".$action);
        }
    }
	
	protected function buildAndSendMail($subject, $tomails, $innerbody, $bccmails = array(), $attachmentPath = null, $attachments = array(), $width = 600, $salutacio = '') {
		$bccmails[] = $this->getParameter('MAIL_ADMINTEST');
		if ($this->get('kernel')->getEnvironment() != 'prod') {
			$tomails = array($this->getParameter('MAIL_ADMINTEST'));  // Entorns de test
		}
		
		$from = $this->container->getParameter('fecdas_partes.emails.contact_email');
		
		$message = \Swift_Message::newInstance()
		->setSubject($subject)
		->setFrom($from)
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
		$logosrc = $message->embed(\Swift_Image::fromPath('images/fecdaslogo-mail.png'));
		
		/*$footer .= "<div style='float:left;padding-right:20px'><img src=".$logosrc." alt='FECDAS' /></div>";
		$footer .= "<div style='float:left;text-align:right'>";
		$footer .= "<small><b>FEDERACIÓ CATALANA D’ACTIVITATS SUBAQUÀTIQUES</b></small><br/>";
		$footer .= "<span style='font-size: 10px;'>Moll de la Vela, 1 (Zona Fòrum)<br/>";
		$footer .= "08930  Sant Adrià de Besòs<br/>";
		$footer .= "Tel. 93 356 05 43<br/>";
		$footer .= "Fax: 93 356 30 73<br/>";
		$footer .= "Adreça electrònica: ".$this->getParameter('MAIL_CONTACTE')."<br/>";
		$footer .= "</span></div>";*/ 
		
		$footer = "<table border='0' cellpadding='0' cellspacing='0' width='100%'>";
		$footer .= "<tr><td><img src=".$logosrc." alt='FECDAS' width='82' height='78' /></td>";
		$footer .= "<td style='padding: 0 0 0 20px;'>";
		$footer .= "<small><b>FEDERACIÓ CATALANA D’ACTIVITATS SUBAQUÀTIQUES</b></small><br/>";
		$footer .= "<span style='font-size: 10px;'>Moll de la Vela, 1 (Zona Fòrum)<br/>";
		$footer .= "08930  Sant Adrià de Besòs<br/>";
		$footer .= "Tel. 93 356 05 43<br/>";
		$footer .= "Fax: 93 356 30 73<br/>";
		$footer .= "Adreça electrònica: ".$this->getParameter('MAIL_CONTACTE')."<br/>";
		$footer .= "</span></td></tr></table>";
		
		$body = "<html style='font-family: Helvetica,Arial,sans-serif;'><head></head><body>";
		$body .= "<table align='left' border='0' cellpadding='0' cellspacing='0' width='".$width."' style='border-collapse: collapse;'>";
		$body .= "<tr><td style='padding: 10px 0 10px 0;'>".$innerbody."</td></tr>";
		$body .= "<tr><td style='padding: 10px 0 10px 0;'>".$footer."</td></tr></table></body></html>";
		
		$message->setBody($body, 'text/html');
		
		$this->get('mailer')->send($message); 
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
	
		$i = 0;
		/*while ($thumb->getImageLength() > 35840 and $i < 10 ) {  /// getImageLength no funciona
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

	public function jsonpersonesAction(Request $request) {
		//fecdas.dev/jsonpersones?cerca=alex
		$response = new Response();
	
		$cerca = $request->get('cerca', ''); 
		$id = $request->get('id', ''); // id federat

		$em = $this->getDoctrine()->getManager();
		
		if ($id != '') {
			$persona = $em->getRepository('FecdasBundle:EntityPersona')->find($id);
				
			if ($persona != null) {
				$response->headers->set('Content-Type', 'application/json');
				$response->setContent(json_encode(array('id' => $id, 'text' => $persona->getNomCognoms(), 
														'nom' => $persona->getNom(), 'cognoms' => $persona->getCognoms(), 
														'dni' => $persona->getDni(), 'mail'	 => $persona->getMail())));
				return $response;
			}
		}
	
		$strQuery = " SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
		$strQuery .= " WHERE p.databaixa IS NULL ";
		$strQuery .= " AND (CONCAT(p.nom , ' ', p.cognoms ) LIKE :cerca";
		$strQuery .= " OR p.dni LIKE :cerca ) ";
		$strQuery .= " ORDER BY p.cognoms, p.nom";
	
		$query = $em->createQuery($strQuery);
		$query->setParameter('cerca', '%'.$cerca.'%');
	
		$search = array();
		if ($query != null) {
			$result = $query->getResult();
			foreach ($result as $persona) {
				$search[] = array('id' => $persona->getId(), 'text' 	=> $persona->getNomCognoms(), 
								'nom' => $persona->getNom(), 'cognoms' 	=> $persona->getCognoms(), 
								'dni' => $persona->getDni(), 'mail'	 	=> $persona->getMail());
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
