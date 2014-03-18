<?php
namespace Fecdas\PartesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fecdas\PartesBundle\Classes\Funcions;

use Fecdas\PartesBundle\Entity\EntityParte;
use Fecdas\PartesBundle\Entity\EntityLlicencia;
use Fecdas\PartesBundle\Entity\EntityPersona;
use Fecdas\PartesBundle\Entity\EntityUserLog;
use Fecdas\PartesBundle\Entity\EntityPagament;
use Fecdas\PartesBundle\Entity\EntityFactura;

class BaseController extends Controller {
	const MAIL_ADMINLOG = "logerror@fecdasgestio.cat";  /* Ha d'estar a la taula d'usuaris. CAT000 */
	const MAIL_ADMINTEST = "test@fecdasgestio.cat";  /* Canviar. Crear nou mail  */
	const MAIL_ADMIN = "webadmin@fecdasgestio.cat";  
	const MAIL_FACTURACIO = "remei@fecdas.cat";
	const MAIL_LLICENCIES = "secretaria@fecdas.cat";
	const MAIL_CONTACTE = "info@fecdas.cat";
	const CLUBS_DEFAULT_STATE = 1;
	const TOTS_CLUBS_DEFAULT_STATE = 0;
	const CLUBS_STATES = 'Tots els clubs;Pagament diferit;Pagament immediat;Sense tramitació';
	const CLUB_SENSE_TRAMITACIO = 'NOTR';
	const CLUB_PAGAMENT_DIFERIT = 'DIFE';
	const DIES_PENDENT_NOTIFICA = 1;
	const DIES_PENDENT_AVIS = 8;
	const DIES_PENDENT_MAX = 10;
	const INICI_TRAMITACIO_ANUAL_DIA = 10; // a partir de 10/12 any en curs
	const INICI_TRAMITACIO_ANUAL_MES = 12; // a partir de 10/12 any en curs
	const INICI_REVISAR_CLUBS_DAY = '01';
	const INICI_REVISAR_CLUBS_MONTH = '04';
	const DATES_INFORME_TRIMESTRAL = '31/03;30/06;30/09;30/11';
	const PAGAMENT_LLICENCIES = 'llicencies';
	const PAGAMENT_DUPLICAT = 'duplicat';
	const UPLOADS_RELPATH = '/../../../../web/uploads/';  // Path is __DIR__.self::UPLOADS_RELPATH
	
	protected function getCommonRenderArrayOptions($more = array()) { 
		if ($this->isCurrentAdmin()) {
			$roleSelectOptions = array('class' => 'FecdasPartesBundle:EntityClub',
					'property' => 'nom',
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
		if ($this->get('session')->has('username') and $this->get('session')->has('remote_addr')
				and $this->get('session')->has('remote_addr') == $this->getRequest()->server->get('REMOTE_ADDR')) {
			return true;
		}
		return false;
	}
	
	protected function isCurrentAdmin() {
		if ($this->isAuthenticated() != true) return false;
		
		$em = $this->getDoctrine()->getManager();
		$repository = $em->getRepository('FecdasPartesBundle:EntityUser');
		$user = $repository->findOneByUser($this->get('session')->get('username'));
		if (!$user || $user->getRole() != 'admin')
			return false;
		return true;
	}

	protected function getCurrentClub() {
		if ($this->isAuthenticated() != true) return null;
		
		$em = $this->getDoctrine()->getManager();
		
		if ($this->isCurrentAdmin() and $this->get('session')->has('roleclub')) {
			return 	$em->getRepository('FecdasPartesBundle:EntityClub')->find($this->get('session')->get('roleclub'));		
		}
		$user = $em->getRepository('FecdasPartesBundle:EntityUser')->findOneByUser($this->get('session')->get('username'));
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
	
	protected function getContactMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array(self::MAIL_ADMINTEST);
		
		$mails = array(self::MAIL_CONTACTE, self::MAIL_ADMINTEST);
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
		$strQuery = "SELECT l FROM Fecdas\PartesBundle\Entity\EntityLlicencia l ";
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
	
	protected function consultaPartesClub($club, $desde, $strOrderBY = '') {
		$em = $this->getDoctrine()->getManager();
	
		// Consultar no només les vigents sinó totes
		$strQuery = "SELECT p, COUNT(l.id) AS HIDDEN numllicencies FROM Fecdas\PartesBundle\Entity\EntityParte p JOIN p.llicencies l JOIN p.tipus t ";
		$strQuery .= "WHERE p.club = :club ";
		$strQuery .= " AND p.databaixa IS NULL AND l.databaixa IS NULL ";
		$strQuery .= " AND p.dataalta >= :ininormal";
		$strQuery .= " GROUP BY p ";

		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  // Només per PDF el paginator ho fa sol mentre el mètode de crida sigui POST
		
		$query = $em->createQuery($strQuery)
			->setParameter('club', $club)
			->setParameter('ininormal', $desde->format('Y-m-d'));
			
		return $query;
	}
	
	protected function consultaAssegurats($tots, $dni, $nom, $cognoms, $vigent = true, $strOrderBY = '') { 
		$em = $this->getDoctrine()->getManager();
	
		if ($vigent == true) {
			$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\EntityPersona e";
			$strQuery .= " JOIN e.llicencies l JOIN l.parte p ";
			$strQuery .= "WHERE e.databaixa IS NULL AND l.databaixa IS NULL AND p.databaixa IS NULL ";
			$strQuery .= " AND p.pendent = 0 ";
			$strQuery .= "AND p.dataalta <= CURRENT_DATE() ";
			$strQuery .= "AND l.datacaducitat >= CURRENT_DATE() ";
		} else { 
			$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\EntityPersona e ";
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
		$pagament = new EntityPagament($this->getCurrentDate());
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
		$query = $em->createQuery("SELECT distinct m.provincia FROM Fecdas\PartesBundle\Entity\EntityMunicipi m
				ORDER BY m.provincia");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$provincies[$res['provincia']] = $res['provincia'];
		return $provincies;
	}
	
	protected function getComarques() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT distinct m.comarca FROM Fecdas\PartesBundle\Entity\EntityMunicipi m
				ORDER BY m.comarca");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$comarques[$res['comarca']] = $res['comarca'];
		return $comarques;
	}
	
	protected function getNacions() {
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT n FROM Fecdas\PartesBundle\Entity\EntityNacio n
				ORDER BY n.codi");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$nacions[$res->getCodi()] = $res->getCodi() . ' - ' . $res->getPais();
		return $nacions;
	}
	
	protected function getClubsSelect() {
		$em = $this->getDoctrine()->getManager();
	
		$query = $em->createQuery("SELECT c FROM Fecdas\PartesBundle\Entity\EntityClub c
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
					FROM Fecdas\PartesBundle\Entity\EntityMunicipi m
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
					FROM Fecdas\PartesBundle\Entity\EntityClub c
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
		
		$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta e";
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
		$this->logEntry($this->get('session')->get('username'), $accio, $this->get('session')->get('remote_addr'), 
				$this->getRequest()->server->get('HTTP_USER_AGENT'), $extrainfo);
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
		return $source." ".$this->get('session')->get('username')." (".$this->getRequest()->server->get('HTTP_USER_AGENT').")";
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
}