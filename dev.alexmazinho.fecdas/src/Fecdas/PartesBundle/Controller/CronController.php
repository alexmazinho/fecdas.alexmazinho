<?php 
namespace Fecdas\PartesBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CronController extends BaseController {

	public function checkrenovacioAction() {
		// Avís renovació partes: 30 dies, 15 dies i 2 dies
		// Preguntar si inclou tipus 8 (Decathlon), en cas que si recordar que aquests no es poden renovar automàticament
		/* Planificar cron
		 * wget -O - -q http://fecdas.dev/app_dev.php/checkrenovacio >> mailsrenovacio.txt*/
		
		/*
		 * SELECT * FROM `m_partes` WHERE tipus IN (7,10) AND 
			(
			YEAR(`dataalta`) = YEAR(now()) OR
			(YEAR(`dataalta`) = YEAR(now()) - 1 AND 
			MONTH(`dataalta`) >= MONTH(now()))
			)
			ORDER BY dataalta
		 * */
		
		$request = $this->getRequest();

		$sortida = '';
		$subject = '';
		$tomails = array("alexmazinho@gmail.com");
		$body = '';
		
		$em = $this->getDoctrine()->getEntityManager();
		
		// 30 dies
		$aux = \DateTime::createFromFormat('Y-m-d H:i:s', (date("Y") - 1) . "-" . date("m") . "-" . date("d") . "  00:00:00");
		//echo $aux->format('Y-m-d') . "<br/>";
		$aux->add(new \DateInterval('P30D'));
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
			$subject = '::Renovació Llista FECDAS::';
			if ($parte_iter->getClub()->getMail() != null) $subject .= ' (club sense adreça de correu)';
			
			$message = \Swift_Message::newInstance()
				->setSubject($subject)
				->setFrom($this->container->getParameter('fecdas_partes.emails.contact_email'))
				->setTo($tomails);
			
			$logosrc = $message->embed(\Swift_Image::fromPath('images/fecdaslogo.png'));
			
			$body = $this->renderView('FecdasPartesBundle:Cron:renovacioEmail.html.twig',
					array('parte' => $parte_iter, 'dies' => 30, 'logo' => $logosrc));
			
			$message->setBody($body, 'text/html');
			
			$sortida .= $body;  
			$this->get('mailer')->send($message);
			
			$this->logEntry('alexmazinho@gmail.com', 'CRON RENEW',
					$this->getRequest()->server->get('REMOTE_ADDR'),
					$this->getRequest()->server->get('HTTP_USER_AGENT'));
		}
		
		return new Response($sortida);
	}
}
