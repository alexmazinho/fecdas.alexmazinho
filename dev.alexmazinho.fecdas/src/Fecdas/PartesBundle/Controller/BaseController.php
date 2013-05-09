<?php
namespace Fecdas\PartesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fecdas\PartesBundle\Entity\EntityParte;
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
		$mails = array("secretari@fecdas.cat", "alexmazinho@gmail.com");
		return $mails;
	}
	
	protected function getFacturacioMails() {
		$mails = array("remei@fecdas.cat", "alexmazinho@gmail.com");
		return $mails;
	}
	
	protected function getContactMails() {
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