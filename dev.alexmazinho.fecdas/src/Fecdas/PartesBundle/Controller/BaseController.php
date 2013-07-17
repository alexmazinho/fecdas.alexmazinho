<?php
namespace Fecdas\PartesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fecdas\PartesBundle\Entity\EntityParte;
use Fecdas\PartesBundle\Entity\EntityLlicencia;
use Fecdas\PartesBundle\Entity\EntityPersona;
use Fecdas\PartesBundle\Entity\EntityUserLog;

class BaseController extends Controller {
	protected function getCurrentDate($time = null) {
		//function to fake date, testing purpouse
		$currentdate = is_null($time) ? new \DateTime() : new \DateTime($time); 		
		return $currentdate;
	}
	
	protected function isAuthenticated() {
		if ($this->get('session')->has('username') and $this->get('session')->has('remote_addr')
				and $this->get('session')->has('remote_addr') == $this->getRequest()->server->get('REMOTE_ADDR')) {
			/*if ($this->get('kernel')->getEnvironment() == 'dev' and 
					$this->get('session')->get('username') != 'alexmazinho@gmail.com' and
					$this->get('session')->get('username') != 'amacia22@xtec.cat') {
				$this->get('session')->clear();
				return false;
			}*/
			return true;
		}
		return false;
	}
	
	protected function isCurrentAdmin() {
		if ($this->isAuthenticated() != true) return false;
		
		$em = $this->getDoctrine()->getEntityManager();
		$repository = $em->getRepository('FecdasPartesBundle:EntityUser');
		$user = $repository->findOneByUser($this->get('session')->get('username'));
		if (!$user || $user->getRole() != 'admin')
			return false;
		return true;
	}

	protected function getCurrentClub() {
		if ($this->isAuthenticated() != true) return null;
		
		$em = $this->getDoctrine()->getEntityManager();
		$repository = $em->getRepository('FecdasPartesBundle:EntityUser');
		$user = $repository->findOneByUser($this->get('session')->get('username'));
		if ($user) return $user->getClub();
		return null;
	}

	
	protected function getAdminMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array("alexmazinho@gmail.com");
		
		$mails = array("secretari@fecdas.cat", "alexmazinho@gmail.com");
		return $mails;
	}
	
	protected function getFacturacioMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array("alexmazinho@gmail.com");
		
		$mails = array("remei@fecdas.cat", "alexmazinho@gmail.com");
		return $mails;
	}
	
	protected function getContactMails() {
		if ($this->get('kernel')->getEnvironment() == 'dev') return array("alexmazinho@gmail.com");
		
		$mails = array("info@fecdas.cat", "alexmazinho@gmail.com");
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
	
	protected function consultaPartesClub($club) {
		$em = $this->getDoctrine()->getEntityManager();
	
		// Consultar no només les vigents sinó totes
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityParte p JOIN p.tipus t ";
		$strQuery .= "WHERE p.club = :club ";
		$strQuery .= " AND p.databaixa IS NULL ";
		$strQuery .= " AND p.dataalta >= :ininormal";
		$strQuery .= " ORDER BY p.dataalta DESC, p.numrelacio DESC";
	
		$inianual = \DateTime::createFromFormat('Y-m-d H:i:s', date("Y") - 1 . "-01-01 00:00:00");
		$inianual = $inianual->format('Y-m-d H:i:s');
	
		$query = $em->createQuery($strQuery)
		->setParameter('club', $club)
		->setParameter('ininormal', $inianual);
			
		return $query->getResult();
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
		$em = $this->getDoctrine()->getEntityManager();
	
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
		$fivigencia_nova = $llicencia->getParte()->getDataCaducitat();
	
		foreach ($lpersonaarevisar as $c => $llicencia_iter) {
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
					return $llicencia_iter->getParte()->getDataalta(); // Error, sol·lapen
				}
			}
		}
		return null;
	}
	
	
	protected function getDetallFactura(EntityParte $parte) {
		$detallfactura = array();
		//$iva = $parte->getTipus()->getIVA() + 100;
		$iva = $parte->getTipus()->getIVA();
		foreach ($parte->getLlicencies() as $c => $llicencia_iter) {
			if ($llicencia_iter->getDatabaixa() == null) {
				$codi = $llicencia_iter->getCategoria()->getCodisortida();
				
				$preu = $llicencia_iter->getCategoria()->getPreuAny($parte->getAny());
				
				if (isset($detallfactura[$codi])) {
					$detallfactura[$codi]['quant'] += 1;
					$detallfactura[$codi]['preusiva'] += $preu;
					$detallfactura[$codi]['iva'] += $preu*$iva/100;
					$detallfactura[$codi]['totaldetall'] = $detallfactura[$codi]['preusiva'] + $detallfactura[$codi]['iva'];
				} else {
					$detallfactura[$codi] = array(
							'codi' => $codi,
							'desc' => $llicencia_iter->getCategoria()->getDescripcio(),
							'quant' => 1,
							'preuunitat' => $preu,
							'preusiva' => $preu,
							'iva' => $preu*$iva/100,
							'totaldetall' => $preu + $preu*$iva/100);
				}
			}
		}
		ksort($detallfactura); // Ordenada per codi
		return $detallfactura;
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
	
	protected function getProvincies() {
		$em = $this->getDoctrine()->getEntityManager();
		$query = $em->createQuery("SELECT distinct m.provincia FROM Fecdas\PartesBundle\Entity\EntityMunicipi m
				ORDER BY m.provincia");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$provincies[$res['provincia']] = $res['provincia'];
		return $provincies;
	}
	
	protected function getComarques() {
		$em = $this->getDoctrine()->getEntityManager();
		$query = $em->createQuery("SELECT distinct m.comarca FROM Fecdas\PartesBundle\Entity\EntityMunicipi m
				ORDER BY m.comarca");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$comarques[$res['comarca']] = $res['comarca'];
		return $comarques;
	}
	
	protected function getNacions() {
		$em = $this->getDoctrine()->getEntityManager();
		$query = $em->createQuery("SELECT n FROM Fecdas\PartesBundle\Entity\EntityNacio n
				ORDER BY n.codi");
		$result = $query->getResult();
		foreach ($result as $c => $res)
			$nacions[$res->getCodi()] = $res->getCodi() . ' - ' . $res->getPais();
		return $nacions;
	}
	
	protected function getClubsSelect() {
		$em = $this->getDoctrine()->getEntityManager();
	
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
			$em = $this->getDoctrine()->getEntityManager();
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
			$em = $this->getDoctrine()->getEntityManager();
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
		
		$em = $this->getDoctrine()->getEntityManager();
		
		$strQuery = "SELECT e FROM Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta e";
		$strQuery .= " WHERE e.datainici <= :avui ";
		$strQuery .= " AND (e.datafinal >= :avui OR e.datafinal IS NULL)";
		$strQuery .= " ORDER BY e.datainici DESC";
		
		$avui = $this->getCurrentDate();
		$avui = $avui->format('Y-m-d H:i:s');
		
		$query = $em->createQuery($strQuery)
		->setParameter('avui', $avui);
			
		$enquestes = $query->getResult();
		foreach ($enquestes as $c => $enquesta) {
			$realitzada = $enquesta->getRealitzada($this->get('session')->get('username'));
			if ($realitzada == null) return $enquesta;
		}
		return null;
	}
	
	protected function getTempUploadDir()
	{
		/* Temporary upload folder. Variable __DIR__ és el directori del fitxer */
		return __DIR__.'/../../../../tmp';
	}
	
	protected function getErrorMessages(\Symfony\Component\Form\Form $form) {
		$errors = array();
		foreach ($form->getErrors() as $key => $error) {
			$template = $error->getMessageTemplate();
			$parameters = $error->getMessageParameters();
	
			foreach($parameters as $var => $value){
				$template = str_replace($var, $value, $template);
			}
	
			$errors[$key] = $template;
		}
		if ($form->hasChildren()) {
			foreach ($form->getChildren() as $child) {
				if (!$child->isValid()) {
					$errors[$child->getName()] = $this->getErrorMessages($child);
				}
			}
		}
	
		return $errors;
	}
	
	
	protected function logEntry($user, $accio, $remoteaddr = null, $useragent = null, $extrainfo = null) {
		$em = $this->getDoctrine()->getEntityManager();
		$logentry = new EntityUserLog($user, $accio, $remoteaddr, $useragent, $extrainfo);
		$em->persist($logentry);
		try {
			$em->flush();
		} catch (\Exception $e) {
			/* No es pot diu que EM està tancat
			$em = $this->getDoctrine()->getEntityManager();
			$logentry->setUser("alexmazinho@gmail.com");
			$logentry->setAccio("LOG ERROR");
			$logentry->setExtrainfo($e->getMessage());
			$em->persist($logentry);
			$em->flush();
			*/
		}
	}
}