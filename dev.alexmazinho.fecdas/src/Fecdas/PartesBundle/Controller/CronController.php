<?php 
namespace Fecdas\PartesBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CronController extends BaseController {

	public function checkupdatepreuAction($maxid) {
		
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
		
		// Crear índex taula partes per data entrada, tipus 8 i 9 sense renovar encara
		$strQuery = "SELECT p FROM Fecdas\PartesBundle\Entity\EntityParte p JOIN p.tipus t ";
		$strQuery .= "WHERE p.databaixa IS NULL  ";
		$strQuery .= " AND t.es365 = 1 AND t.id <> 8 AND t.id <> 9";
		$strQuery .= " AND p.renovat = 0 ";
		$strQuery .= " AND p.dataalta >= :iniNotificacio ";
		$strQuery .= " AND p.dataalta < :fiNotificacio";
		
		$query = $em->createQuery($strQuery)
		->setParameter('iniNotificacio', $iniNotificacio)
		->setParameter('fiNotificacio', $fiNotificacio);
		
		$partesrenovar = $query->getResult();
		
		foreach ($partesrenovar as $c => $parte_iter) {
			$bccmails = array('alexmazinho@gmail.com');
			$tomails = array();
			
			$subject = '::Renovació Llista FECDAS::';
				
			if ($parte_iter->getClub()->getMail() == null) $subject .= ' (club sense adreça de correu)';
			else {
				if ($this->get('kernel')->getEnvironment() == 'prod') {
					$bccmails[] = $this->getAdminMails();
					$tomails[] = $parte_iter->getClub()->getMail();
				} else {
					$bccmails[] = "alexmazinho@gmail.com";
				}
			}
				
			$message = \Swift_Message::newInstance()
			->setSubject($subject)
			->setFrom($this->container->getParameter('fecdas_partes.emails.contact_email'))
			->setBcc($bccmails)
			->setTo($tomails);
				
			$logosrc = $message->embed(\Swift_Image::fromPath('images/fecdaslogo.png'));
				
			$body = $this->renderView('FecdasPartesBundle:Cron:renovacioEmail.html.twig',
					array('parte' => $parte_iter, 'dies' => $dies, 'logo' => $logosrc));
				
			$message->setBody($body, 'text/html');
				
			$sortida .= $body;
			$this->get('mailer')->send($message);
				
			$this->logEntry('alexmazinho@gmail.com', 'CRON RENEW',
					$this->getRequest()->server->get('REMOTE_ADDR'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'), 
					'club ' . $parte_iter->getClub()->getNom() . ', parte ' . $parte_iter->getId() . ', dies ' .  $dies);
		}
		
		return $sortida;
	}
}
