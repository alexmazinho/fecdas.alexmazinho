<?php 
namespace Fecdas\PartesBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fecdas\PartesBundle\Entity\EntityParte;
use Fecdas\PartesBundle\Entity\EntityLlicencia;
use Fecdas\PartesBundle\Entity\EntityPersona;
use Fecdas\PartesBundle\Form\FormLlicenciaRenovar;

class CronController extends BaseController {

	public function checkupdatepreuAction($maxid) {
		return new Response("");  // Funció desactivada
		
		
		if ($this->isCurrentAdmin() != true) return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
		
		/* Update preu partes web */
		// Actualitzar tots els importparte a 0, per a què no dongui error la sincro
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityParte p ";
		$strQuery .= "WHERE p.databaixa IS NULL  ";
		$strQuery .= " AND p.importparte = 0 ";
		$strQuery .= " AND p.id <= :maxid ";
		$strQuery .= " AND p.web = 1 ORDER BY p.id ";
		
		$em = $this->getDoctrine()->getEntityManager();
		
		$query = $em->createQuery($strQuery)->setParameter('maxid', $maxid);
		$partesweb = $query->getResult();
		
		foreach ($partesweb as $c => $parte) {
			
			$parte->setImportparte($parte->getPreuTotalIVA());
			
			if ($parte->getImportpagament() != null) {
				if ($parte->getImportparte() != $parte->getImportpagament()) {
					$this->logEntry('alexmazinho@gmail.com', 'UPD PREU ERROR',
							$this->getRequest()->server->get('REMOTE_ADDR'),
							$this->getRequest()->server->get('HTTP_USER_AGENT'),
							$parte->getId() . " . calculat: " . $parte->getImportparte() . "  pagament: " .$parte->getImportpagament());
				}
			} 
		}
		
		/* Commit final */
		$em->flush(); 
		
		return new Response("");
	}
	
	public function checkrenovacioAction() {
		// Avís renovació partes: 30 dies, 15 dies i 2 dies
		// Preguntar si inclou tipus 8 (Decathlon), en cas que si recordar que aquests no es poden renovar automàticament
		/* Planificar cron
		 * wget -O - -q http://fecdas.dev/app_dev.php/checkrenovacio >> mailsrenovacio.txt*/
		
		/*
		 * (SELECT p.id, (SELECT c.nom FROM m_clubs c WHERE c.codi = p.club), DATE_FORMAT(p.dataalta, '%d/%m/%Y'), 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 365 DAY, '%d/%m/%Y') as datacaducitat, 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 335 DAY, '%d/%m/%Y') as datanotificacio,
		 		 p.numrelacio, t.id, t.descripcio  
		 		 FROM m_partes p INNER JOIN m_tipusparte t ON p.tipus = t.id 
		 		 WHERE p.tipus IN (7,10) AND 
				(YEAR(p.dataalta) = YEAR(now()) OR (YEAR(p.dataalta) = YEAR(now()) - 1 AND MONTH(p.dataalta) >= MONTH(now())))
			) UNION
			(SELECT p.id, (SELECT c.nom FROM m_clubs c WHERE c.codi = p.club), DATE_FORMAT(p.dataalta, '%d/%m/%Y'), 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 365 DAY, '%d/%m/%Y') as datacaducitat, 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 350 DAY, '%d/%m/%Y') as datanotificacio,
		 		 p.numrelacio, t.id, t.descripcio  
		 		 FROM m_partes p INNER JOIN m_tipusparte t ON p.tipus = t.id 
		 		 WHERE p.tipus IN (7,10) AND 
				(YEAR(p.dataalta) = YEAR(now()) OR (YEAR(p.dataalta) = YEAR(now()) - 1 AND MONTH(p.dataalta) >= MONTH(now())))
			) UNION
			(SELECT p.id, (SELECT c.nom FROM m_clubs c WHERE c.codi = p.club), DATE_FORMAT(p.dataalta, '%d/%m/%Y'), 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 365 DAY, '%d/%m/%Y') as datacaducitat, 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 363 DAY, '%d/%m/%Y') as datanotificacio,
		 		 p.numrelacio, t.id, t.descripcio  
		 		 FROM m_partes p INNER JOIN m_tipusparte t ON p.tipus = t.id 
		 		 WHERE p.tipus IN (7,10) AND 
				(YEAR(p.dataalta) = YEAR(now()) OR (YEAR(p.dataalta) = YEAR(now()) - 1 AND MONTH(p.dataalta) >= MONTH(now())))
			) ORDER BY datanotificacio

		 * */
		
		$request = $this->getRequest();

		$sortida = $this->checkRenovacio(30);
		
		$sortida .= $this->checkRenovacio(15);
		
		$sortida .= $this->checkRenovacio(2);
		
		return new Response($sortida);
	}
	
	private function checkRenovacio($dies) {
		$sortida = 'Avís renovació ' . $dies . ', en data '. date('Y-m-d') . '\n';
		$subject = '';
		$body = '';
		
		$em = $this->getDoctrine()->getEntityManager();
		
		// 30 dies
		$aux = \DateTime::createFromFormat('Y-m-d H:i:s', (date("Y") - 1) . "-" . date("m") . "-" . date("d") . "  00:00:00");
		//echo $aux->format('Y-m-d') . "<br/>";
		$aux->add(new \DateInterval('P'.$dies.'D'));
		//echo $aux->format('Y-m-d H:i:s') . "<br/>";
		$iniNotificacio = $aux->format('Y-m-d H:i:s'); // Format Mysql
		$aux->add(new \DateInterval('P1D'));
		//echo $aux->format('Y-m-d H:i:s') . "<br/>";
		$fiNotificacio = $aux->format('Y-m-d H:i:s'); // Format Mysql
		
		// Crear índex taula partes per data entrada, tipus 8 i 9 
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityParte p JOIN p.tipus t ";
		$strQuery .= "WHERE p.databaixa IS NULL  ";
		$strQuery .= " AND t.es365 = 1 AND t.id <> 8 AND t.id <> 9";
		$strQuery .= " AND p.dataalta >= :iniNotificacio ";
		$strQuery .= " AND p.dataalta < :fiNotificacio";
		
		$query = $em->createQuery($strQuery)
		->setParameter('iniNotificacio', $iniNotificacio)
		->setParameter('fiNotificacio', $fiNotificacio);
		
		$partesrenovar = $query->getResult();
		
		$tomails = array();
		$bccmails = array("alexmazinho@gmail.com");  // Test
		
		foreach ($partesrenovar as $c => $parte_iter) {
			/* Per cada parte */
			$subject = '::Renovació Llicència FECDAS::';
			if ($parte_iter->getClub()->getMail() == null) {
				$subject .= ' (club sense adreça de correu)';
				$tomails = array("alexmazinho@gmail.com");
			} else {
				if ($this->get('kernel')->getEnvironment() == 'prod') {  // Només a producció
					$tomails[] = $parte_iter->getClub()->getMail();
				}
			}
			
			foreach ($parte_iter->getLlicencies() as $c => $llicencia_iter) {
				/* Per cada llicència del parte */
				if ($this->checkSendMail($llicencia_iter) == true) {
					$message = \Swift_Message::newInstance()
						->setSubject($subject)
						->setFrom($this->container->getParameter('fecdas_partes.emails.contact_email'))
						->setBcc($bccmails)
						->setTo($tomails);
					
					$logosrc = $message->embed(\Swift_Image::fromPath('images/fecdaslogo.png'));
					
					$body = $this->renderView('FecdasPartesBundle:Cron:renovacioEmail.html.twig',
							array('llicencia' => $llicencia_iter, 'dies' => $dies, 'logo' => $logosrc));
						
					$message->setBody($body, 'text/html');
						
					$sortida .= $body;
					$this->get('mailer')->send($message);
						
					$this->logEntry('alexmazinho@gmail.com', 'CRON RENEW',
							$this->getRequest()->server->get('REMOTE_ADDR'),
							$this->getRequest()->server->get('HTTP_USER_AGENT'), 
							'club ' . $parte_iter->getClub()->getNom() . ', llicència ' . $llicencia_iter->getId() . ', dies ' .  $dies);
				}
			}
		}
		
		return $sortida;
	}
	
	private function checkSendMail($llicencia) {
		if ($llicencia->getDatabaixa() != null) return false;

		/* Comprovar si és la darrera llicència */
		$persona = $llicencia->getPersona();
		if (!($llicencia === $persona->getLastLlicencia())) return false;
		
		$em = $this->getDoctrine()->getEntityManager();
		// Comprovar que no hi ha llicències d'altres clubs posteriors. Si canvi de club no s'envia mail 
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityPersona p ";
		$strQuery .= " WHERE p.dni = :dni ";
		$strQuery .= " AND p.club <> :club ";
		$strQuery .= " AND p.databaixa IS NULL";
				
		$query = $em->createQuery($strQuery)
				->setParameter('dni', $llicencia->getPersona()->getDni())
				->setParameter('club', $llicencia->getPersona()->getClub()->getCodi());
										
		$personaaltresclubs = $query->getResult();
										
		foreach ($personaaltresclubs as $c => $persona_iter) {
			//->setParameter('dataalta', $llicencia->getParte()->getDataalta()->format('yyyy-mm-dd'))
			foreach ($persona_iter->getLlicencies() as $c => $llicencia_iter) {
				if ($llicencia_iter->getDatabaixa() == null and 
					 $llicencia_iter->getParte()->getDataalta() >  $llicencia->getParte()->getDataalta()) {
					// Nova llicència posterior altre club
					return false;
				}
			}
		}
		
		return true;
	}
	
	
	public function renovarllicenciaAction() {
		/* Entra una id de llicència i se li renova la llicència vigent o la darrera llicència des de data d'avui */
		/* p.e. fecdas.dev/renovarllicencia?id=5897 */
		
		$this->get('session')->clearFlashes();
		
		$request = $this->getRequest();
		
		if ($this->isAuthenticated() != true) {
			// keep url. Redirect after login
			$url_request = $this->getRequest()->server->get('REQUEST_URI');
			$this->get('session')->set('url_request', $url_request);
			return $this->redirect($this->generateUrl('FecdasPartesBundle_login'));
		}

		$llicenciaid = 0;
		
		$currentClub = $this->getCurrentClub()->getCodi();
		if ($request->getMethod() == 'POST') {
			if ($request->request->has('llicencia_renovar')) {
				$l = $request->request->get('llicencia_renovar');
				$llicenciaid = $l['cloneid'];
			}
		} else {
			if ($request->query->has('id') and $request->query->get('id') != "")
				$llicenciaid = $request->query->get('id');
		}
		
		$llicenciaarenovar = $this->getDoctrine()->getRepository('FecdasPartesBundle:EntityLlicencia')->find($llicenciaid);
		
		if ($llicenciaarenovar == null) return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	
		/* Validació impedir modificacions altres clubs */
		if ($this->isCurrentAdmin() != true and $llicenciaarenovar->getParte()->getClub()->getCodi() != $currentClub)
			return $this->redirect($this->generateUrl('FecdasPartesBundle_homepage'));
	
		/* Crear el nou parte */
		$parte = new EntityParte($this->getCurrentDate());
		$data_alta = $this->getCurrentDate('now');
		/* Si abans data caducitat renovació per tot el periode
		 * En cas contrari només des d'ara
		*/
		if ($llicenciaarenovar->getParte()->getDataCaducitat($this->getLogMailUserData("renovarllicenciaAction 1 ")) > $data_alta) {
			$data_alta = $llicenciaarenovar->getParte()->getDataCaducitat($this->getLogMailUserData("renovarllicenciaAction 2 "));
			$data_alta->setTime(00, 00);
			$data_alta->add(new \DateInterval('P1D')); // Add 1 dia
		}
		$parte->setDataalta($data_alta);
		$parte->setDatamodificacio($this->getCurrentDate());
		$parte->setClub($llicenciaarenovar->getParte()->getClub());
		$parte->setTipus($llicenciaarenovar->getParte()->getTipus());
		
		// Afegir llicència		
		$cloneLlicencia = clone $llicenciaarenovar;
		
		/* Init camps */
		$cloneLlicencia->setDataEntrada($this->getCurrentDate());
		$cloneLlicencia->setDatamodificacio($this->getCurrentDate());
		$cloneLlicencia->setDatacaducitat($parte->getDataCaducitat($this->getLogMailUserData("renovarllicenciaAction 3 ")));
		$cloneLlicencia->setIdparteAccess(null);
		$cloneLlicencia->getIdpartedetall_access(null);
		
		$parte->addEntityLlicencia($cloneLlicencia);
		$parte->setImportparte($parte->getPreuTotalIVA());   // Actualitza preu si escau

		/* Preparar formulari */
		$options = $this->getFormOptions();
			
		$options['codiclub'] = $parte->getClub()->getCodi();
		$options['tipusparte'] = $parte->getTipus()->getId();
		array_push($options['llistatipus'], $parte->getTipus()->getId()); // No es pot canviar de tipus
		$options['any'] = $parte->getAny(); // Mostrar preu segons any parte
		$options['edit'] = true;
		$options['admin'] = true;
		$options['nova'] = true;
			
		$form = $this->createForm(new FormLlicenciaRenovar($options),$cloneLlicencia);
		
		$form->get('cloneid')->setData($llicenciaid);  // Posar id
		$form->get('personashow')->setData($cloneLlicencia->getPersona()->getLlistaText());  // Nom + cognoms
		$form->get('datacaducitatshow')->setData($parte->getDataCaducitat($this->getLogMailUserData("renovarllicenciaAction 4 "))); 
		
		/*
		 * Validacions  de les llicències
		*/
		try {
			if ($this->validaLlicenciaInfantil($cloneLlicencia) == false) {
				// Comprova que no sigui infantil amb llicència aficionat, etc...
				throw new \Exception('L\'edat d\'una de les persones no correspon amb el tipus de llicència');
			}
		
			$dataoverlapllicencia = $this->validaPersonaTeLlicenciaVigent($cloneLlicencia, $cloneLlicencia->getPersona());
			if ($dataoverlapllicencia != null) {
				// Comprovar que no hi ha llicències vigents,per la pròpia persona
				throw new \Exception('Aquesta persona ja té una llicència al club en aquests periode, en data ' .
						$dataoverlapllicencia->format('d/m/Y'));
			}
		
			if ($request->getMethod() == 'POST') {
				$form->bindRequest($request);
			
				if ($form->isValid() && $request->request->has('llicencia_renovar')) {
					$em = $this->getDoctrine()->getEntityManager();
			
					// Marquem com renovat
					$parte->setRenovat(true);
			
					$em->persist($cloneLlicencia);
					$em->persist($parte);
					$em->flush();
			
					$this->logEntry($this->get('session')->get('username'), 'RENOVAR LLICENCIA OK',
							$this->get('session')->get('remote_addr'),
							$this->getRequest()->server->get('HTTP_USER_AGENT'), $parte->getId());
			
					$this->get('session')->setFlash('error-notice',	'Llicència enviada correctament');
			
					return $this->redirect($this->generateUrl('FecdasPartesBundle_parte', array('id' => $parte->getId(), 'action' => 'view', 'source' => 'renovacio')));
			
				} else {
					throw new \Exception('Error validant les dades. Contacta amb l\'adminitrador');
				}
			} else {
				$this->logEntry($this->get('session')->get('username'), 'RENOVAR LLICENCIA VIEW',
						$this->get('session')->get('remote_addr'),
						$this->getRequest()->server->get('HTTP_USER_AGENT'), $parte->getId());
			}			
		} catch (\Exception $e) {
			$this->get('session')->setFlash('error-notice',$e->getMessage());
			
			$this->logEntry($this->get('session')->get('username'), 'RENOVAR LLICENCIA ERROR',
					$this->get('session')->get('remote_addr'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), $parte->getId());
				
		}
			
		return $this->render('FecdasPartesBundle:Cron:renovarllicencia.html.twig',
				array('form' => $form->createView(), 'parte' => $parte, 'admin' => $this->isCurrentAdmin(),
						'authenticated' => $this->isAuthenticated(), 'busseig' => $this->isCurrentBusseig(),
						'enquestausuari' => $this->get('session')->has('enquestapendent')));
	}
	
}
