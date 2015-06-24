<?php
namespace FecdasBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


use FecdasBundle\Classes\Funcions;

use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityUserLog;
use FecdasBundle\Entity\EntityRebut;
use FecdasBundle\Entity\EntityFactura;

class BaseController extends Controller {
	const MAIL_ADMINLOG = "logerror@fecdasgestio.cat";  /* Ha d'estar a la taula d'usuaris. CAT000 */
	const MAIL_ADMINTEST = "test@fecdasgestio.cat";  /* Canviar. Crear nou mail  */
	const MAIL_ADMIN = "webadmin@fecdasgestio.cat";  
	const MAIL_FACTURACIO = "remei.s@fecdas.cat";
	const MAIL_LLICENCIES = "remei.s@fecdas.cat";
	const MAIL_CARNETS = "a.batalla@fecdas.cat";
	const MAIL_CONTACTE = "info@fecdas.cat";//o.montferrer@fecdas.cat
	const CLUBS_DEFAULT_STATE = 1;
	const TOTS_CLUBS_DEFAULT_STATE = 0;
	const CLUBS_STATES = 'Tots els tipus;Pagament diferit;Pagament immediat;Sense tramitació';
	const CLUB_SENSE_TRAMITACIO = 'NOTR';
	const CLUB_PAGAMENT_DIFERIT = 'DIFE';
	const DIES_PENDENT_NOTIFICA = 1;
	const DIES_PENDENT_AVIS = 8;
	const DIES_PENDENT_MAX = 10;
	const INICI_TRAMITACIO_ANUAL_DIA = 12; // a partir de 15/12 any en curs
	const INICI_TRAMITACIO_ANUAL_MES = 12; // a partir de 15/12 any en curs
	const INICI_REVISAR_CLUBS_DAY = '01';
	const INICI_REVISAR_CLUBS_MONTH = '04';
	const DATES_INFORME_TRIMESTRAL = '31/03;30/06;30/09;30/11';
	const PAGAMENT_LLICENCIES = 'llicencies';
	const PAGAMENT_DUPLICAT = 'duplicat';
	const UPLOADS_RELPATH = '/../../../../web/uploads/';  // Path is __DIR__.self::UPLOADS_RELPATH
	
	
	const TIPUS_PRODUCTE_LLICENCIES = 1;
	const TIPUS_PRODUCTE_DUPLICATS 	= 2;
	const TIPUS_PRODUCTE_KITS 		= 3;
	const TIPUS_PRODUCTE_MERCHA 	= 4;
	const TIPUS_PRODUCTE_CURSOS 	= 5;
	const TIPUS_PRODUCTE_ALTRES 	= 6;
	
	const TIPUS_PAGAMENT_CASH 			= 1;
	const TIPUS_PAGAMENT_TPV			= 2;
	const TIPUS_PAGAMENT_TRANS_SARDENYA = 3;
	const TIPUS_PAGAMENT_TRANS_LAIETANIA= 4;	// Caixa
	
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
	
	protected static $tipusproducte; // Veure getTipusDeProducte()
	protected static $tipuspagament; // Veure getTipusDePagament()
	protected static $tipuscomanda; // Veure getTipusDeComanda()
	
	/**
	 * Array possibles tipus de producte
	 */
	public static function getTipusDeProducte() {
		if (self::$tipusproducte == null) {
			self::$tipusproducte = array(
					self::TIPUS_PRODUCTE_LLICENCIES => 'Llicències',
					self::TIPUS_PRODUCTE_DUPLICATS => 'Duplicats',
					self::TIPUS_PRODUCTE_KITS => 'Kits',
					self::TIPUS_PRODUCTE_MERCHA => 'Merchandising',
					self::TIPUS_PRODUCTE_CURSOS => 'Cursos',
					self::TIPUS_PRODUCTE_ALTRES => 'Altres'
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
	 * Array possibles tipus de pagament
	 */
	public static function getTipusDePagament() {
		if (self::$tipuspagament == null) {
			self::$tipuspagament = array(
					self::TIPUS_PAGAMENT_CASH => 'Metàl·lic',
					self::TIPUS_PAGAMENT_TPV => 'Pagament On-Line TPV',
					self::TIPUS_PAGAMENT_TRANS_SARDENYA => 'Transferència Sardenya',
					self::TIPUS_PAGAMENT_TRANS_LAIETANA => 'La Caixa Laietana',
			);
		}
		return self::$tipuspagament;
	}
	
	/**
	 * Obté tipus de pagament
	 */
	public static function getTipusPagament($index) {
		$tipus = BaseController::getTipusDePagament();
		if (isset($tipus[$index])) return $tipus[$index];
	
		return '';
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
	
	protected function getCommonRenderArrayOptions($more = array()) {
		$options = array();
		if ($this->isCurrentAdmin()) {
			$roleSelectOptions = array('class' => 'FecdasBundle:EntityClub',
					'choice_label' => 'nom',
					'label' => 'El teu rol actual és: ',
					'required'  => true );
			
			$roleSelectOptions['data'] = $this->getCurrentClub();
			$formbuilder = $this->createFormBuilder();
			$formbuilder->add('role', 'genemu_jqueryselect2_entity', $roleSelectOptions);
			$options['roleform'] = $formbuilder->getForm()->createView();
		} else {
			$userclub = $this->getCurrentClub();
			if ($userclub) $options['userclub'] = $userclub->getNom(); 	
		}
		$options['admin'] = $this->isCurrentAdmin();
		$options['authenticated'] = $this->isAuthenticated();
		$options['busseig'] = $this->isCurrentBusseig();
		$options['enquestausuari'] = $this->get('session')->has('enquesta');
		$options['enquestausuaripendent'] = $this->get('session')->has('enquestapendent');
		
		return  array_merge($more, $options);
	}
	
	protected function getCurrentDate($time = null) {
		//function to fake date, testing purpouse
		$currentdate = is_null($time) ? new \DateTime() : new \DateTime($time); 		
		return $currentdate;
	}
	
	protected function isAuthenticated() {
		$request = $this->container->get('request_stack')->getCurrentRequest();
		if ($this->get('session')->has('username') and $this->get('session')->has('remote_addr')
				and $this->get('session')->has('remote_addr') == $request->server->get('REMOTE_ADDR')) {
			return true;
		}
		return false;
	}
	
	protected function isCurrentAdmin() {
		if ($this->isAuthenticated() != true) return false;
		
		$em = $this->getDoctrine()->getManager();
		$repository = $em->getRepository('FecdasBundle:EntityUser');
		$user = $repository->findOneByUser($this->get('session')->get('username'));
		if (!$user || $user->getRole() != 'admin')
			return false;
		return true;
	}

	protected function getCurrentClub() {
		if ($this->isAuthenticated() != true) return null;
		
		$em = $this->getDoctrine()->getManager();
		
		if ($this->isCurrentAdmin() and $this->get('session')->has('roleclub')) {
			return 	$em->getRepository('FecdasBundle:EntityClub')->find($this->get('session')->get('roleclub'));		
		}
		$user = $em->getRepository('FecdasBundle:EntityUser')->findOneByUser($this->get('session')->get('username'));
		if ($user) return $user->getClub();
		return null;
	}

	
	protected function getAdminMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array(self::MAIL_ADMINTEST);
		
		$mails = array(self::MAIL_LLICENCIES, self::MAIL_ADMIN);
		return $mails;
	}
	
	protected function getFacturacioMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array(self::MAIL_ADMINTEST);
		
		$mails = array(self::MAIL_FACTURACIO, self::MAIL_LLICENCIES);
		return $mails;
	}

	protected function getLlicenciesMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array(self::MAIL_ADMINTEST);
		
		$mails = array(self::MAIL_LLICENCIES);
		return $mails;
	}
	
	protected function getCarnetsMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array(self::MAIL_ADMINTEST);
	
		$mails = array(self::MAIL_CARNETS);
		return $mails;
	}
	
	protected function getContactMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array(self::MAIL_ADMINTEST);
		
		$mails = array(self::MAIL_CONTACTE, self::MAIL_ADMINTEST, self::MAIL_LLICENCIES);
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
		$ini365 = \DateTime::createFromFormat('Y-m-d H:i:s', (date("Y") - 1) . "-" . date("m") . "-" . date("d") . "  00:00:00");
		$ini365 = $ini365->format('Y-m-d H:i:s');
		return $ini365;
	}
	
	protected function validaLlicenciaInfantil(EntityLlicencia $llicencia) {
		// Valida menors, nascuts després del 01-01 any actual - 12
		if ($llicencia->getParte()->getTipus()->getId() == 11) return true; // Llicències Dia no aplica

		$nascut = $llicencia->getPersona()->getDatanaixement();
	
		/*$nascut = new \DateTime(date("Y-m-d", strtotime($llicencia->getPersona()->getDatanaixement()->format('Y-m-d'))));
		 echo $nascut->format("Y-m-d");*/
		$limit = \DateTime::createFromFormat('Y-m-d', ($llicencia->getParte()->getAny()-12) . "-01-01");
		if ($llicencia->getCategoria()->getSimbol() == "I" && $nascut < $limit) return false;
		if ($llicencia->getCategoria()->getSimbol() != "I" && $nascut > $limit) return false;
		return true;
	}
	
	protected function validaPersonaRepetida(EntityParte $parte, EntityLlicencia $llicencia) {
		// Parte ja té llicència aquesta persona
		foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
			if ($llicencia_iter->getId() != $llicencia->getId() and
				$llicencia_iter->getDatabaixa() == null) {
				// NO valido la pròpia llicència, en cas d'update
				if ($llicencia_iter->getPersona()->getId() == $llicencia->getPersona()->getId()) return false;
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
	
	protected function consultaPartesClub($club, $tipus, $desde, $fins, $strOrderBY = '') {
		$em = $this->getDoctrine()->getManager();
	
		// Consultar no només les vigents sinó totes
		$strQuery = "SELECT p, COUNT(l.id) AS HIDDEN numllicencies FROM FecdasBundle\Entity\EntityParte p JOIN p.llicencies l JOIN p.tipus t ";
		$strQuery .= "WHERE p.club = :club ";
		$strQuery .= " AND p.databaixa IS NULL AND l.databaixa IS NULL ";
		$strQuery .= " AND p.dataalta >= :ininormal";
		$strQuery .= " AND p.dataalta <= :finormal";
		if ($tipus == 0) $strQuery .= " AND t.id <> :tipus";
		else $strQuery .= " AND t.id = :tipus";
		$strQuery .= " GROUP BY p ";

		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  // Només per PDF el paginator ho fa sol mentre el mètode de crida sigui POST
		
		$query = $em->createQuery($strQuery)
			->setParameter('club', $club)
			->setParameter('tipus', $tipus)
			->setParameter('ininormal', $desde->format('Y-m-d'))
			->setParameter('finormal', $fins->format('Y-m-d'));
			
		return $query;
	}
	
	protected function consultaAssegurats($tots, $dni, $nom, $cognoms, $vigent = true, $strOrderBY = '') { 
		$em = $this->getDoctrine()->getManager();
	
		if ($vigent == true) {
			$strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e";
			$strQuery .= " JOIN e.llicencies l JOIN l.parte p ";
			$strQuery .= "WHERE e.databaixa IS NULL AND l.databaixa IS NULL AND p.databaixa IS NULL ";
			$strQuery .= " AND p.pendent = 0 ";
			$strQuery .= "AND p.dataalta <= CURRENT_DATE() ";
			$strQuery .= "AND l.datacaducitat >= CURRENT_DATE() ";
		} else { 
			$strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e ";
			$strQuery .= " WHERE e.databaixa IS NULL ";
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
		
	
		return $query;
	}
	
	protected function getTotalsFactura($detallfactura) {
		$totalfactura = array('totalparcial' => 0, 'iva' => 0, 'total' => 0);
		foreach ($detallfactura as $c => $lineafactura) {
			$totalfactura['totalparcial'] += $lineafactura['preusiva'];
			$totalfactura['iva'] = $lineafactura['iva'];
			$totalfactura['total'] += $lineafactura['totaldetall'];
		}
		return $totalfactura;
	}
	
	protected function crearPagament($data, $import, $estat, $dades, $comentari = '') {
		$em = $this->getDoctrine()->getManager();
		
		if (trim($dades) == '') $dades = null;
		if (trim($comentari) == '') $comentari = null;
		$pagament = new EntityRebut($this->getCurrentDate());
		$pagament->setDatapagament($data);
		$pagament->setEstat($estat);
		$pagament->setImport($import);
		$pagament->setDades($dades);
		$pagament->setComentari($comentari);
		
		$em->persist($pagament);
		
		return $pagament;
	}

	protected function crearFactura($data, $num, $import, $concepte) {
		$em = $this->getDoctrine()->getManager();
		
		$factura = new EntityFactura($this->getCurrentDate());
		$factura->setDatafactura($data);
		$factura->setNum($num);
		$factura->setImport($import);
		$factura->setConcepte($concepte);
		
		$em->persist($factura);
		
		return $factura;
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
	
	protected function consultaAjaxPoblacions($value) {
		// http://fecdas.dev/app_dev.php/ajaxpoblacions?term=abx   ==> For debug
		// Cerques només per a >= 3 lletres
		$search = array();
		if (strlen($value) >= 3) {
			$em = $this->getDoctrine()->getManager();
			$query = $em
			->createQuery(
					"SELECT DISTINCT m.municipi, m.cp, m.provincia, m.comarca
					FROM FecdasBundle\Entity\EntityMunicipi m
					WHERE m.municipi LIKE :value ORDER BY m.municipi")
						->setParameter('value', '%' . $value . '%');
			$result = $query->getResult();
			
			foreach ($result as $c => $res) {
				$muni = array();
				//$search[] = $res['municipi'];
				$muni['value'] = $res['municipi'];
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
	
	protected function esDNIvalid ($cadena)
	{
		// longitud
		if (strlen($cadena) != 9) return false;
	
		// valors letra
		$lletres = array(
				0 => 'T', 1 => 'R', 2 => 'W', 3 => 'A', 4 => 'G', 5 => 'M',
				6 => 'Y', 7 => 'F', 8 => 'P', 9 => 'D', 10 => 'X', 11 => 'B',
				12 => 'N', 13 => 'J', 14 => 'Z', 15 => 'S', 16 => 'Q', 17 => 'V',
				18 => 'H', 19 => 'L', 20 => 'C', 21 => 'K',22 => 'E'
		);
	
		//Comprovar DNI
		if (preg_match('/^[0-9]{8}[A-Z]$/i', $cadena))
		{
			//Comprovar lletra
			$dnisenselletra = (int) substr($cadena, 0, strlen($cadena) - 1);
			$illetra =  $dnisenselletra % 23 ; 
			
			if (strtoupper($cadena[strlen($cadena) - 1]) != $lletres[$illetra]) return false;
				
			//Ok
			return true;
		}
		//ko
		return false;
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
		foreach ($enquestes as $c => $enquesta) return $enquesta; // Només una
		return null;
	}
	
	protected function getTempUploadDir()
	{
		/* Temporary upload folder. Variable __DIR__ és el directori del fitxer */
		return __DIR__.'/../../../../tmp';
	}
	
	protected function logEntryAuth($accio = null, $extrainfo = null) {
		$request = $this->container->get('request_stack')->getCurrentRequest();
		$this->logEntry($this->get('session')->get('username'), $accio, $this->get('session')->get('remote_addr'), 
				$request->server->get('HTTP_USER_AGENT'), $extrainfo);
	}
	
	protected function logEntry($user = null, $accio = null, $remoteaddr = null, $useragent = null, $extrainfo = null) {
		if (!$user) {
			if ($this->get('session')->has('username')) $user = $this->get('session')->get('username');
			else $user = self::MAIL_ADMINLOG;
		}
		
		$em = $this->getDoctrine()->getManager();
		$logentry = new EntityUserLog($user, $accio, $remoteaddr, $useragent, $extrainfo);
		$em->persist($logentry);
		try {
			$em->flush();
		} catch (\Exception $e) {
			error_log ("APP FECDAS > Error saving app log to mysql: ".$e->getMessage(), 0);
		}
	}
	
	protected function getLogMailUserData($source = null) {
		$request = $this->container->get('request_stack')->getCurrentRequest();
		return $source." ".$this->get('session')->get('username')." (".$request->server->get('HTTP_USER_AGENT').")";
	}
	
	
	protected function buildAndSendMail($subject, $tomails, $body, $bccmails = array(), $attachmentPath = null) {
		$bccmails[] = self::MAIL_ADMINTEST;
		if ($this->get('kernel')->getEnvironment() != 'prod') {
			$tomails = array(self::MAIL_ADMINTEST);  // Entorns de test
		}
		
		$from = $this->container->getParameter('fecdas_partes.emails.contact_email');
		
		$message = \Swift_Message::newInstance()
		->setSubject($subject)
		->setFrom($from)
		->setBcc($bccmails)
		->setTo($tomails);

		if ($attachmentPath != null) $message->attach(\Swift_Attachment::fromPath($attachmentPath));
		
		$logosrc = $message->embed(\Swift_Image::fromPath('images/fecdaslogo-mail.png'));
		
		$footer = "<p>Atentament<br/>";
		$footer .= "FECDAS, ".$this->getCurrentDate()->format("d/m/Y")."</p><br/>";
		
		$footer .= "<div style='float:left;padding-right:20px'><img src=".$logosrc." alt='FECDAS' /></div>";
		$footer .= "<div style='float:left;text-align:right'>";
		$footer .= "<small><b>FEDERACIÓ CATALANA D’ACTIVITATS SUBAQUÀTIQUES</b></small><br/>";
		$footer .= "<span style='font-size: 10px;'>Moll de la Vela, 1 (Zona Fòrum)<br/>";
		$footer .= "08930  Sant Adrià de Besòs<br/>";
		$footer .= "Tel. 93 356 05 43<br/>";
		$footer .= "Fax: 93 356 30 73<br/>";
		$footer .= "Adreça electrònica: ".self::MAIL_CONTACTE."<br/>";
		$footer .= "</span></div>"; 
		
		
		$body = "<html style='font-family: Helvetica,Arial,sans-serif;'><head></head><body>".$body.$footer."</body></html>";
		
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
		$strPath = __DIR__.self::UPLOADS_RELPATH.$nameAjustat;
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
	
		error_log($cerca . " " .$codi);
		
		
		if ($codi != '') {
			$club = $em->getRepository('FecdasBundle:EntityClub')->find($codi);
				
			if ($club != null) {
				$response->headers->set('Content-Type', 'application/json');
				$response->setContent(json_encode(array("id" => $club->getCodi(), "text" => $club->getNom()) ));
				return $response;
			}
		}
	
	
		$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
		$strQuery .= " WHERE c.activat = 1 ";
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
}
