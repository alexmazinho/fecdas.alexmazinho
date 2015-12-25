<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FecdasBundle\Classes\FlushHelper;
use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Form\FormLlicenciaRenovar;

class CronController extends BaseController {

	public function checkrenovacioAction(Request $request) {
		// Avís renovació partes: 30 dies, 15 dies i 2 dies
		/* Planificar cron diari
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
		
		//$sortida = "";
		$sortida = $this->checkRenovacio($request, 30);
		
		$sortida .= $this->checkRenovacio($request, 15);
		
		$sortida .= $this->checkRenovacio($request, 2);
		
		return new Response($sortida);
	}
	
	private function checkRenovacio($request, $dies) {
		$sortida = 'Avís renovació ' . $dies . ', en data '. date('Y-m-d') . '\n';
		$subject = '';
		$body = '';
		
		$em = $this->getDoctrine()->getManager();
		
		$aux = \DateTime::createFromFormat('Y-m-d H:i:s', (date("Y") - 1) . "-" . date("m") . "-" . date("d") . "  00:00:00");
		//echo $aux->format('Y-m-d') . "<br/>";

		$aux->add(new \DateInterval('P'.$dies.'D'));
		//echo $aux->format('Y-m-d H:i:s') . "<br/>";
		$iniNotificacio = $aux->format('Y-m-d H:i:s'); // Format Mysql
		$aux->add(new \DateInterval('P1D'));
		//echo $aux->format('Y-m-d H:i:s') . "<br/>";
		$fiNotificacio = $aux->format('Y-m-d H:i:s'); // Format Mysql
		// Crear índex taula partes per data entrada, tipus 8 i 9 
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p JOIN p.tipus t ";
		$strQuery .= "WHERE p.databaixa IS NULL  ";
		$strQuery .= " AND t.es365 = 1 AND t.id <> 8 AND t.id <> 9 AND t.id <> 12 and t.id <> 4 ";
		$strQuery .= " AND p.dataalta >= :iniNotificacio ";
		$strQuery .= " AND p.dataalta < :fiNotificacio";
		/* Valida tipus actiu --> és la única que es pot fer */
		$strQuery .= " AND t.actiu = 1 ";
		/* Fi modificacio 12/12/2014. Missatge no es poden tramitar */
		
		$query = $em->createQuery($strQuery)
		->setParameter('iniNotificacio', $iniNotificacio)
		->setParameter('fiNotificacio', $fiNotificacio);
		
		$partesrenovar = $query->getResult();
		
		
		/* Valida tipus actiu --> és la única que es pot fer */
		// Llicències curs escolar final 08-31
		/*$strQuery = "SELECT t FROM FecdasBundle\Entity\EntityParteType t ";
		$strQuery .= " WHERE t.es365 = 1 AND t.actiu = 1 AND t.id NOT IN (8, 9, 12) ";
		
		$tipus365actius = $em->createQuery($strQuery)->getResult();
		
		foreach ($tipus365actius as $tipus) {
			
		}*/
		$aux = \DateTime::createFromFormat('Y-m-d H:i:s', (date("Y") - 1) . "-" . date("m") . "-" . date("d") . "  00:00:00");
		$aux->add(new \DateInterval('P'.$dies.'D'));
		$iniNotificacio = $aux->format('Y-m-d H:i:s'); // Format Mysql
		echo 'ini ' . $iniNotificacio . "<br/>";
		
		$aux->add(new \DateInterval('P1Y')); // + 1 any
		$fiNotificacio = $aux->format('m-d'); // Format Mysql
		echo 'fi ' . $fiNotificacio . "<br/>";
		
		
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p JOIN p.tipus t ";
		$strQuery .= "WHERE p.databaixa IS NULL  ";
		$strQuery .= " AND t.es365 = 1 AND t.id = 4 AND t.actiu = 1 ";
		$strQuery .= " AND p.dataalta >= :iniNotificacio ";
		$strQuery .= " AND t.final = :fiNotificacio ";
		
		
		$query = $em->createQuery($strQuery)
		->setParameter('iniNotificacio', $iniNotificacio)
		->setParameter('fiNotificacio', $fiNotificacio);
		
		$partesrenovar = array_merge ($partesrenovar, $query->getResult());
		/* Fi modificacio 12/12/2014. Missatge no es poden tramitar */
		
		foreach ($partesrenovar as $parte_iter) {
			$tomails = array();
			$subject = "Notificació. Renovació llicència FECDAS";
			/* Per cada parte */
			if ($parte_iter->getClub()->getMail() == null) {
				$subject .= ' (Cal avisar aquest club no té adreça de mail al sistema)';
			} else {
				$tomails[] = $parte_iter->getClub()->getMail();
			}
			
			foreach ($parte_iter->getLlicencies() as $llicencia_iter) {
				/* Per cada llicència del parte */
				if ($this->checkSendMail($llicencia_iter) == true) {
					$body = $this->renderView('FecdasBundle:Cron:renovacioEmail.html.twig',
							array('llicencia' => $llicencia_iter, 'dies' => $dies));
					$this->buildAndSendMail($subject, $tomails, $body);
					$sortida .= $body;
						
					$this->logEntry('alexmazinho@gmail.com', 'CRON RENEW',
							$request->server->get('REMOTE_ADDR'),
							$request->server->get('HTTP_USER_AGENT'), 
							'club ' . $parte_iter->getClub()->getNom() . ', llicència ' . $llicencia_iter->getId() . ', dies ' .  $dies);
				}
			}
		}
		
		return $sortida;
	}
	
	private function checkSendMail($llicencia) {
		/* Comprovació per si cal enviar mail renovació */
		
		if ($llicencia->getDatabaixa() != null) return false;

		/* Comprovar si és la darrera llicència */
		$persona = $llicencia->getPersona();
		if (!($llicencia === $persona->getLastLlicencia())) return false;
		
		$em = $this->getDoctrine()->getManager();
		// Comprovar que no hi ha llicències d'altres clubs posteriors. Si canvi de club no s'envia mail 
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
		$strQuery .= " WHERE p.dni = :dni ";
		$strQuery .= " AND p.club <> :club ";
		$strQuery .= " AND p.databaixa IS NULL";
				
		$query = $em->createQuery($strQuery)
				->setParameter('dni', $llicencia->getPersona()->getDni())
				->setParameter('club', $llicencia->getPersona()->getClub()->getCodi());
										
		$personaaltresclubs = $query->getResult();
										
		foreach ($personaaltresclubs as $persona_iter) {
			//->setParameter('dataalta', $llicencia->getParte()->getDataalta()->format('yyyy-mm-dd'))
			foreach ($persona_iter->getLlicencies() as $llicencia_iter) {
				if ($llicencia_iter->getDatabaixa() == null and 
					 $llicencia_iter->getParte()->getDataalta() >  $llicencia->getParte()->getDataalta()) {
					// Nova llicència posterior altre club
					return false;
				}
			}
		}
		
		return true;
	}
	
	
	public function renovarllicenciaAction(Request $request) {
		/* Entra una id de llicència i se li renova la llicència vigent o la darrera llicència des de data d'avui */
		/* p.e. fecdas.dev/renovarllicencia?id=5897 */
		
		$this->get('session')->getFlashBag()->clear();
		
		if ($this->isAuthenticated() != true) {
			// keep url. Redirect after login
			$url_request = $request->server->get('REQUEST_URI');
			$this->get('session')->set('url_request', $url_request);
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
		}

		if (!$this->getCurrentClub()->potTramitar()) {
			$this->get('session')->getFlashBag()->add('error-notice',$this->getCurrentClub()->getInfoLlistat());
			$response = $this->redirect($this->generateUrl('FecdasBundle_assegurats'));
			return $response;
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
		
		$llicenciaarenovar = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($llicenciaid);
		
		if ($llicenciaarenovar == null) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		/* Validació impedir modificacions altres clubs */
		if ($this->isCurrentAdmin() != true and $llicenciaarenovar->getParte()->getClub()->getCodi() != $currentClub)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		
		/*
		 * Validacions  de les llicències
		*/
		$form = null;
		try {
			/* Si abans data caducitat renovació per tot el periode
			 * En cas contrari només des d'ara
			*/
			$dataalta = $this->getCurrentDate('now');
			if ($llicenciaarenovar->getParte()->getDataCaducitat($this->getLogMailUserData("renovarllicenciaAction 1 ")) > $dataalta) {
				$dataalta = $llicenciaarenovar->getParte()->getDataCaducitat($this->getLogMailUserData("renovarllicenciaAction 2 "));
				$dataalta->setTime(00, 00);
				$dataalta->add(new \DateInterval('P1D')); // Add 1 dia
			}
			/* Crear el nou parte */
			$parte = $this->crearComandaParte($dataalta, $llicenciaarenovar->getParte()->getTipus());

			// Afegir llicència		
			$cloneLlicencia = clone $llicenciaarenovar;
			
			/* Init camps */
			$cloneLlicencia->setDatacaducitat($parte->getDataCaducitat($this->getLogMailUserData("renovarllicenciaAction 3 ")));
			$cloneLlicencia->setDatamodificacio($this->getCurrentDate());

			/* Preparar formulari */
			$form = $this->createForm(new FormLlicenciaRenovar(),$cloneLlicencia);
			
			$form->get('cloneid')->setData($llicenciaid);  // Posar id
			$form->get('personashow')->setData($cloneLlicencia->getPersona()->getLlistaText());  // Nom + cognoms
			$form->get('datacaducitatshow')->setData($parte->getDataCaducitat($this->getLogMailUserData("renovarllicenciaAction 4 "))); 
			
			$parte->addLlicencia($cloneLlicencia);
	
		    // Crear factura
			$factura = $this->crearFactura($dataalta, $parte);
			
			$this->addParteDetall($parte, $cloneLlicencia);
			
			$this->validaParteLlicencia($parte, $cloneLlicencia);
		
			if ($request->getMethod() == 'POST') {
				$form->bind($request);
			
				if ($form->isValid() && $request->request->has('llicencia_renovar')) {
					// Marquem com renovat
					$parte->setRenovat(true);
			
					$em->persist($cloneLlicencia);
					$em->persist($parte);
					$em->flush();
			
					$this->logEntryAuth('RENOVAR LLICENCIA OK',	$llicenciaarenovar->getParte()->getId().' renovat a '.$parte->getId());
					
					$this->get('session')->getFlashBag()->add('error-notice',	'Llicència enviada correctament');
			
					return $this->redirect($this->generateUrl('FecdasBundle_parte', array('id' => $parte->getId(), 'action' => 'view', 'source' => 'renovacio')));
			
				} else {
					throw new \Exception('Error validant les dades. Contacta amb l\'adminitrador');
				}
			} else {
				$this->logEntryAuth('RENOVAR LLICENCIA VIEW',	$llicenciaarenovar->getParte()->getId());
			}			
		} catch (\Exception $e) {
				
			$this->get('session')->getFlashBag()->add('error-notice',$e->getMessage());
			
			$this->logEntryAuth('RENOVAR LLICENCIA ERROR',	$llicenciaarenovar->getParte()->getId().' => '. $e->getMessage());
			
		}
			
		return $this->render('FecdasBundle:Cron:renovarllicencia.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'parte' => $parte)));
	}
	
	public function checkclubsAction(Request $request) {
		/*
		 * http://www.fecdas.dev/checkclubs?page=1&update=0&club=CATXXX 	=> consulta, club opcional
		 * http://www.fecdas.dev/checkclubs?page=1&update=1&club=CATXXX 	=> modificar, club opcional
		 * 
		 * Revisar diferències saldos calculats
		 * Detectar si un club de pagament diferit supera el límit 
		 * Detectar clubs amb partes sense factura 
		 * Planificar cron diari
		 * wget -O - -q http://fecdas.dev/app_dev.php/checkclubs >> checkclubs.txt*/
		
		
		$update = ($request->query->get('update', 0) == 0?false:true);
		$page = $request->query->get('page', 1);
		$club = $request->query->get('club', '');
		
		$sortida = "<style type='text/css'>";
		$sortida .= "table	{ border-collapse:collapse; font-family: Arial; font-size: 13px; }";
		$sortida .= "table, th, td { border: 1px solid black; }";
		$sortida .= "th { padding: 5px; background-color: #E7F2FB; color: #2281CF; font-weight: bold; }";
		$sortida .= "td { padding: 5px; color: #555555 }";
		$sortida .= ".comment { font-style:italic; }";
		$sortida .= ".codi { display:none }";
		$sortida .= ".club { width:170px; }";
		$sortida .= "td.club { font-weight: bold; }";
		$sortida .= ".importclub { width:70px; text-align: right; }";
		$sortida .= ".totalclub { width:50px; text-align: center; }";
		$sortida .= ".importclub.totalclub { width:90px; text-align: right; }";
		$sortida .= ".saldo { font-weight: bold; }";
		$sortida .= "td.importerror { width:500px; color:white; background-color: red }";
		$sortida .= "</style>";
		
		$sortida .= "<h1>Informe diari de clubs en data " . $this->getCurrentDate()->format('d/m/Y') .  "</h1>";
		
		$em = $this->getDoctrine()->getManager();
		
		$states = explode(";", self::CLUBS_STATES);
		
		//$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityClub c WHERE c.activat = 1";
		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityClub c WHERE c.databaixa IS NULL";
		
		if ($club != '') $strQuery .= " AND c.codi = :club "; 
		$query = $em->createQuery($strQuery);
		if ($club != '') $query->setParameter('club', $club);

		//$clubs = $query->getResult();
		$paginator  = $this->get('knp_paginator');
		$clubs = $paginator->paginate(
				$query,
				$page,
				50 /*limit per page*/
		);

		$sortida .= "<h2>Nombre de clubs: " . count($clubs) . "</h2>";
		$nclubsincidencias = 0;
		$sortida .= "<h2>Clubs amb incidència: -updinci- </h2>";
		$sortida .= "<p class='comment'>*Romanent: Deute del club acumulat en anys anteriors. Valors negatius indiquen saldo favorable al club</p>";

		$sortida .= "<table>";
		
		$sortida .= "<tr><th class='codi' rowspan='2'>Codi</th>";
		$sortida .= "<th class='club' rowspan='2'>Club</th>";
		$sortida .= "<th class='text' colspan='7'>Totals registrats</th>";
		$sortida .= "<th class='text' colspan='9'>Totals calculats</th>";
		$sortida .= "<th class='text' rowspan='2'>Incidències</th></tr>";
		
		$sortida .= "<tr><th class='importclub'>Llicències</th>";
		$sortida .= "<th class='importclub'>Duplicats</th>";
		$sortida .= "<th class='importclub'>Altres (num.)</th>";
		$sortida .= "<th class='importclub'>Pagaments</th>";
		$sortida .= "<th class='importclub'>Subven.</th>";
		$sortida .= "<th class='importclub'>Romanent* ant. ".date('Y')."</th>";
		$sortida .= "<th class='importclub saldo'>Saldo</th>";
		$sortida .= "<th class='totalclub'>Comandes</th>";
		$sortida .= "<th class='totalclub'>Pagades<br/>(web/manual)</th>";
		$sortida .= "<th class='totalclub'>Partes</th>";
		$sortida .= "<th class='importclub totalclub'>Llicències<br/>(num.)</th>";
		$sortida .= "<th class='importclub totalclub'>Duplicats<br/>(num.)</th>";
		$sortida .= "<th class='importclub totalclub'>Altres<br/>(num.)</th>";
		$sortida .= "<th class='importclub'>Total comandes</th>";
		$sortida .= "<th class='importclub'>Total pagaments</th>";
		$sortida .= "<th class='importclub saldo'>Saldo calculat</th></tr>";
		
		$datainiciRevisarSaldos = new \DateTime(date("Y-m-d", strtotime(date("Y") . "-".self::INICI_REVISAR_CLUBS_MONTH."-".self::INICI_REVISAR_CLUBS_DAY)));
		$index = 1;
		foreach ($clubs as $c => $club_iter) {
			$incidencies = "";
			
			$filaClub = "<tr><td class='codi'>" . $club_iter->getCodi() . "</td>";
			$filaClub .= "<td class='club'>" . $index."-".$club_iter->getNom() . "</td>";

			$dadesClub = $club_iter->getDadesCurrent(true, $update);

			$filaClub .= "<td class='importclub'>" . number_format($club_iter->getTotalllicencies(), 2, ',', '.') . "€</td>"; // Import llicències valor
			$filaClub .= "<td class='importclub'>" . number_format($club_iter->getTotalduplicats(), 2, ',', '.') . "€</td>"; // Import Kits valor
			$filaClub .= "<td class='importclub'>" . number_format($club_iter->getTotalaltres(), 2, ',', '.') . "€</td>"; // Import altres valor
			$filaClub .= "<td class='importclub'>" . number_format($club_iter->getTotalpagaments(), 2, ',', '.') . "€</td>"; // Import pagaments valor
			$filaClub .= "<td class='importclub'>" . number_format($club_iter->getAjustsubvencions(), 2, ',', '.') . "€</td>"; // Import ajust per subvencions valor
			$filaClub .= "<td class='importclub'>" . number_format($club_iter->getRomanent(), 2, ',', '.') . "€</td>"; // Romanent valor
			$filaClub .= "<td class='importclub saldo'>" . number_format($club_iter->getSaldo(), 2, ',', '.') . "€</td>"; // Saldo valor
			$filaClub .= "<td class='totalclub'>" . number_format($dadesClub['comandes'], 0, ',', '.') . "</td>"; // total comandes
			$filaClub .= "<td class='totalclub'>" . number_format($dadesClub['pagats'], 0, ',', '.');
			$filaClub .= " (".number_format($dadesClub['pagatsweb'], 0, ',', '.')."/".number_format($dadesClub['pagatsmanual'], 0, ',', '.').")</td>"; // total pagades
			$filaClub .= "<td class='totalclub'>" . number_format($dadesClub['partes'], 0, ',', '.') . "</td>"; // total partes
			$filaClub .= "<td class='importclub totalclub'>" . number_format($dadesClub['importpartes'], 2, ',', '.')."€";
			$filaClub .= " (".number_format($dadesClub['llicencies'], 0, ',', '.').")</td>"; // total partes
			$filaClub .= "<td class='importclub totalclub'>" . number_format($dadesClub['importduplicats'], 2, ',', '.')."€";
			$filaClub .= " (".number_format($dadesClub['duplicats'], 0, ',', '.').")</td>"; // total partes
			$filaClub .= "<td class='importclub totalclub'>" . number_format($dadesClub['importaltres'], 2, ',', '.')."€";
			$filaClub .= " (".number_format($dadesClub['altres'], 0, ',', '.').")</td>"; // total partes
	
			$filaClub .= "<td class='importclub'>" . number_format($dadesClub['import'], 2, ',', '.') . "€</td>"; // import comandes								
			$filaClub .= "<td class='importclub'>" . number_format($dadesClub['pagaments'], 2, ',', '.') . "€</td>"; // pagaments
			$filaClub .= "<td class='importclub saldo'>" . number_format($dadesClub['saldocalculat'], 2, ',', '.') . "€</td>"; // SAldo calculat
			
			if (false) { // Desactivat
				if ($club_iter->controlCredit() &&
					$this->getCurrentDate()->format('Y-m-d') >= $datainiciRevisarSaldos->format('Y-m-d')) {
						
					if ($club_iter->getLimitcredit() == null || $club_iter->getLimitcredit() <= 0) {
						// Init data notificació
						$club_iter->setLimitnotificacio(null);
						$dadesClub['errors'][] = ">> (Incidència) Límit de crèdit del club incorrecte " . $club_iter->getLimitcredit();
					} else {
						if ($club_iter->getSaldo() > $club_iter->getLimitcredit()) {
							// Comprovar si ja s'ha enviat la notificació
							if ($club_iter->getLimitnotificacio() == null) {
								$dadesClub['errors'][] = ">> (Notificació) Superat el límit de dèbit, s'envia la notificació al club per correu";
								
								// Enviar notificació mail
								$subject = "Notificació. Federació Catalana d'Activitats Subaquàtiques";
								//if ($club_iter->getMail() == null) $subject = "Notificació. Cal avisar aquest club no té adreça de mail al sistema";
									
								/*$bccmails = $this->getFacturacioMails();
								$tomails = array($club_iter->getMail());
								$body = "<p>Benvolgut club ".$club_iter->getNom()."</p>";
								$body .= "<p>Us fem saber que l'import de les tramitacions que heu fet a dèbit en aquest sistema ha arribat als límits establerts.
								Per poder fer noves gestions, cal que contacteu amb la FECDAS</p>";*/
									
								$tomails = $this->getFacturacioMails();
								$body = "<p>Club ".$club_iter->getNom()."</p>";
								$body .= "<p>L'import de les tramitacions que ha fet a dèbit en aquest sistema ha arribat als límits establerts</p>";
								$body .= "<p>El saldo actual del club és ".number_format($club_iter->getSaldo(), 2, ',', '.')." €</p>";
								
								$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
								
								$club_iter->setLimitnotificacio($this->getCurrentDate());
							} else {
								$dadesClub['errors'][] = ">> (Notificació) Límit de dèbit superat des del dia " . $club_iter->getLimitnotificacio()->format('d-m-Y') . "<br/>"; 
							}
							// Estat -> sense tramitació 
							$club_iter->setEstat($estat = $this->getDoctrine()->getRepository('FecdasBundle:EntityClubEstat')->find(self::CLUB_SENSE_TRAMITACIO));
						} else {
							$club_iter->setLimitnotificacio(null);
						}
					}
				}
			}
			
			if (count($dadesClub['errors']) > 0) { // Afegir club a la taula
				$filaClub .= "<td class='importerror'>" . implode(PHP_EOL, $dadesClub['errors']) . "</td>"; // Errors
				$nclubsincidencias++;
			} else {					 
				$filaClub .= "<td>" . $incidencies . "</td></tr>";
			}
			$sortida .= $filaClub;
			$em->flush();
			$index++;
		}
		$sortida .= "</table>";
		
		$sortida = str_replace("-updinci-", $nclubsincidencias, $sortida);
		
		$subject = "Informe diari de l'estat dels clubs";
		$bccmails = array();
		$tomails = array(self::MAIL_ADMINTEST);
		$body = $sortida;
		$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
		
		/*$this->logEntry(self::MAIL_ADMINLOG, 'CRON CLUBS',
				$this->get('session')->get('remote_addr'),
				$request->server->get('HTTP_USER_AGENT'));*/
		
		
		/*return new Response($sortida);*/
		
		$helper = new FlushHelper();
				
		return new StreamedResponse(function() use ($helper, $sortida) {
            $helper->out($sortida);
        });
	}
	
	
	public function informesaldosAction(Request $request) {
		/* Informe trimestral de saldos als clubs 
		 * 31 de març, 30 de juny, 30 setembre i 30 novembre
		 * Planificar cron diari
		 * wget -O - -q http://fecdas.dev/app_dev.php/informesaldos >> informesaldos.txt*/

		$sortida = "";
		
		return new Response(""); // Desactivat de moment
		
		// Comprovar les dates de l'enviament 
		$datesinforme = explode(";", self::DATES_INFORME_TRIMESTRAL);
		$current_dm = $this->getCurrentDate()->format('d/m');
		if (!in_array($current_dm, $datesinforme)) return new Response("N/A");
		
		$em = $this->getDoctrine()->getManager();
	
		$states = explode(";", self::CLUBS_STATES);
	
		//$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityClub c WHERE c.activat = 1";
		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityClub c WHERE c.databaixa IS NULL";
		$query = $em->createQuery($strQuery);
		$clubs = $query->getResult();

		
		$bccmails = array();
		$tomails = array(self::MAIL_ADMINTEST);
		
		foreach ($clubs as $c => $club_iter) {
			if ($club_iter->getMail() == null) $subject = "Notificació. Cal avisar aquest club no té adreça de mail al sistema";
			else $subject = "Notificació trimestral de l'estat de comptes";
			
			$body = "<p>Benvolgut club ".$club_iter->getNom()."</p>";
			$body .= "<p>Us fem arribar l'estat dels comptes del vostre club amb la Federació a data " . $this->getCurrentDate()->format('d/m/Y') . "</p>";
			$body .= "<div style='display:table;border-collapse: collapse;'>";
			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Deute acumulat de l'any anterior:</div>";
			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club_iter->getRomanent(), 2, ',', '.') . " €</div></div>";
			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Despesa total en llicències durant l'any actual:</div>";
			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club_iter->getTotalllicencies(), 2, ',', '.') . " €</div></div>";
			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Despesa total en kits durant l'any actual:</div>";
			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club_iter->getTotalduplitas(), 2, ',', '.') . " €</div></div>";
			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Altres despeses durant l'any actual:</div>";
			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club_iter->getTotalaltres(), 2, ',', '.') . " €</div></div>";
			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Pagaments realitzats durant l'any actual:</div>";
			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club_iter->getTotalpagaments(), 2, ',', '.') . " €</div></div>";
			$body .= "<div style='display: table-row'><div style='display: table-cell; '></div></div>";
			$body .= "<div style='display: table-row;'><div style='display: table-cell;height: 20px;'></div>";
			$body .= "<div style='display: table-cell;'></div></div>";
			$body .= "<div style='display: table-row;border-top:1px solid #eeeeee;'><div style='display: table-cell;height: 20px;'></div>";
			$body .= "<div style='display: table-cell;'></div></div>";
			$body .= "<div style='display: table-row;'><div style='display: table-cell; padding-right: 10px'>Saldo total amb la Federació:</div>";
			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club_iter->getSaldo(), 2, ',', '.') . " €</div></div>";
			$body .= "</div>";
				
				
			$body .= "<p>Per a qualsevol dubte, us podeu posar en contacte amb la Federació Catalana d'Activitats Subaquàtiques (FECDAS)</p>";
			
		
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			
			$sortida .= $body;
		}
	
	
	
		$this->logEntry(self::MAIL_ADMINLOG, 'INFORME TRIM CLUBS',
				$this->get('session')->get('remote_addr'),
				$request->server->get('HTTP_USER_AGENT'));
	
		return new Response($sortida);
	}
	
	
	public function checkpendentsAction(Request $request) {
		/* Revisar partes pendents
		 * Donar de baixa si pendents i fa més de 10 dies que van entrar al sistema  
		 * Avisar per mail si falten 2 dies per donar de baixa (fa 8 dies de l'entrada)
		 * */
		/* Planificar cron diari
		 * wget -O - -q http://fecdas.dev/app_dev.php/checkpendents >> checkpendents.txt*/
		
		$sortida = "";

		$current = $this->getCurrentDate();
		
		$em = $this->getDoctrine()->getManager();
		
		/* Update preu partes web */
		// Actualitzar tots els importparte a 0, per a què no dongui error la sincro
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p ";
		$strQuery .= "WHERE p.databaixa IS NULL  ";
		$strQuery .= " AND p.pendent = 1 ";
		$query = $em->createQuery($strQuery);
	
		$partespendents = $query->getResult();
	
		foreach ($partespendents as $c => $parte_iter) {
			$interval = $current->diff($parte_iter->getDataentrada());
			$diesPendent = $interval->format('%a');  //r 	Sign "-" when negative, empty when positive

			// Revisar incidències. Parte sincronitzat  o pagat 
			if ($this->incidenciesPendents($parte_iter) == false) { 
				if ($diesPendent <= self::DIES_PENDENT_NOTIFICA) {
					// Enviar mail notificació parte nou pendent
					$subject = ":: Notificació. Parte pendent ::";
					$tomails = $this->getFacturacioMails();
					$body = "<p>Parte pendent de pagament del club ".$parte_iter->getClub()->getNom();
					$body .= " en data del " . $parte_iter->getDataentrada()->format('d-m-Y') . "</p>";
					
					$this->buildAndSendMail($subject, $tomails, $body);
					$sortida .= " Parte pendent >> Notificació Federació ". $parte_iter->getClub()->getNom();
					$sortida .= " (Parte " .  $parte_iter->getId() . " entrat el dia ". $parte_iter->getDataentrada()->format('d-m-Y') .")</br>";
				} else {
					if ($diesPendent == self::DIES_PENDENT_AVIS) {
						// Enviar mail falten 2 dies
						$subject = "Notificació. Federació Catalana d'Activitats Subaquàtiques";
						if ($parte_iter->getClub()->getMail() == null) $subject = "Notificació. Cal avisar aquest club no té adreça de mail al sistema";
						
						$bccmails = $this->getFacturacioMails();
						$tomails = array($parte_iter->getClub()->getMail());
						$body = "<p>Benvolgut club ".$parte_iter->getClub()->getNom()."</p>";
						$body .= "<p>Us fem saber que la tramitació de llicències/assegurances 
								feta en la data del " . $parte_iter->getDataentrada()->format('d-m-Y') . " s'anul·larà en 48 hores a partir de l'enviament d'aquest correu 
								tret que se'n faci efectiu el pagament</p>";
						
						$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
						$sortida .= " Parte pendent >> Notificació per mail falten 2 dies ". $parte_iter->getClub()->getNom();
						$sortida .= " (Parte " .  $parte_iter->getId() . " entrat el dia ". $parte_iter->getDataentrada()->format('d-m-Y') .")</br>";
					} else {
						if ($diesPendent > self::DIES_PENDENT_MAX) {
							// Esborrar
							$parte_iter->setDatamodificacio($current);
							$parte_iter->setDatabaixa($current);
							foreach ($parte_iter->getLlicencies() as $c => $llicencia_iter) {
								$llicencia_iter->setDatamodificacio($current);
								$llicencia_iter->setDatabaixa($current);
							}
							$em->flush();
							
							$sortida .= " Parte pendent >> Baixa més de 10 dies ". $parte_iter->getClub()->getNom();
							$sortida .= " (Parte " .  $parte_iter->getId() . " entrat el dia ". $parte_iter->getDataentrada()->format('d-m-Y') .")</br>";
						} else {
							// Esperar
							$sortida .= " Parte pendent >> ". $parte_iter->getClub()->getNom();
							$sortida .= " (Parte " .  $parte_iter->getId() . " entrat el dia ". $parte_iter->getDataentrada()->format('d-m-Y') .")</br>";
						}
					}
				}
			}
		}
	
		$this->logEntry(self::MAIL_ADMINLOG, 'CRON PENDENTS',
				$this->get('session')->get('remote_addr'),
				$request->server->get('HTTP_USER_AGENT'), $this->get('kernel')->getEnvironment());
		
		return new Response($sortida);
	}
	
	private function incidenciesPendents($parte) {
		// Revisar incidències. Parte sincronitzat  o pagat. Enviar mail
		$subject = ":: Incidència revisió partes pendents ::";
		$bccmails = array();
		$tomails = array(self::MAIL_ADMINTEST);
		
		if ($parte->getIdparteAccess() != null) {
			$body = "<h1>Parte pendent sincronitzat</h1>";
			$body .= "<h2>Club : " . $parte->getClub()->getNom() . "</h2>";
			$body .= "<p>Parte " .  $parte->getId() . " entrat el dia ". $parte->getDataentrada()->format('d-m-Y') ."</p>";
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			return true;
		}
		
		if ($parte->getDatapagament() != null || $parte->getImportpagament() != null) {
			$body = "<h1>Parte pendent pagat</h1>";
			$body .= "<h2>Club : " . $parte->getClub()->getNom() . "</h2>";
			$body .= "<h3>Valor 'pendent' no atualitzat  correctament</h3>";
			$body .= "<p>Parte " .  $parte->getId() . " entrat el dia ". $parte->getDataentrada()->format('d-m-Y') ."</p>";
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			return true;
		}
		return false;
	}
	
	public function checkpartesdiaAction(Request $request) {
		/* Revisar partes tramitats durant el dia
		 * Validar llicències mateix dni diferents clubs */
		/* Planificar cron diari
		 * wget -O - -q http://fecdas.dev/app_dev.php/checkpartesdia >> partesdia.txt*/
	
		$sortida = "";
	
		$em = $this->getDoctrine()->getManager();
	
		/* Update preu partes web */
		// Actualitzar tots els importparte a 0, per a què no dongui error la sincro
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p ";
		$strQuery .= " WHERE p.dataentrada 	 >= :dataavui ";
		$strQuery .= " AND p.databaixa IS NULL  ";
		
		$dataavui = $this->getCurrentDate('today');
		
		/*
		echo $dataavui->format('Y-m-d H:i:s') . "<br/>";
		$interval = \DateInterval::createfromdatestring('-2 day');
		$dataavui->add($interval);
		echo $dataavui->format('Y-m-d H:i:s') . "<br/>";*/
		
		$query = $em->createQuery($strQuery)->setParameter('dataavui', $dataavui);
	
		$partesavui = $query->getResult();
	
		foreach ($partesavui as $c => $parte_iter) {
			foreach ($parte_iter->getLlicencies() as $c => $llicencia_iter) {
				if ($llicencia_iter->getDatabaixa() == null) {
					// Comprovar que no hi ha llicències vigents de la persona en difents clubs, per DNI
					// Les persones s'associen a un club, mirar si existeix a un altre club
					$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
					$strQuery .= " WHERE p.dni = :dni ";
					$strQuery .= " AND p.club <> :club ";
					$strQuery .= " AND p.databaixa IS NULL";
														
					$query = $em->createQuery($strQuery)
						->setParameter('dni', $llicencia_iter->getPersona()->getDni())
						->setParameter('club', $llicencia_iter->getPersona()->getClub()->getCodi());
												
					$personaaltresclubs = $query->getResult();
												
					foreach ($personaaltresclubs as $c => $persona_iter) {
						$parteoverlap = $this->validaPersonaTeLlicenciaVigent($llicencia_iter, $persona_iter);
						if ($parteoverlap != null) {
							// Enviar mail a FECDAS
							
							$subject = "::Llicència Duplicada Diferents Clubs::";
							$bccmails = array();
							$tomails = $this->getLlicenciesMails();
							
							$body = "<h1>Detectada una llicència duplicada, en data ".$dataavui->format('Y-m-d')."</h1>";
							$body .= "<h2>Tramitació nova</h2>";
							$body .= "<p><strong>Club</strong> : " . $parte_iter->getClub()->getNom() . "</p>";
							$body .= "<p><strong>Llicència per</strong> : ";
							$body .= $llicencia_iter->getPersona()->getNom() . " " . $llicencia_iter->getPersona()->getCognoms();
							$body .= " (" . $llicencia_iter->getPersona()->getDni() . ")</p>";
							$body .= "<h2>Dades de la llicència existent</h2>";
							$body .= "<p><strong>Club</strong> : " . $persona_iter->getClub()->getNom() . "</p>";
							
							if ($parteoverlap->getNumrelacio() != null)
								$body .= "<p><strong>Relació </strong> : " . $parteoverlap->getNumrelacio() . "</p>";
							else 
								$body .= "<p><strong>En data </strong> : " . $parteoverlap->getDataalta()->format('Y-m-d') . "</p>";
							$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
						}
					}
				}	
			}
		}
	
		$this->logEntry(self::MAIL_ADMINLOG, 'CRON PARTES DIA',
				$this->get('session')->get('remote_addr'),
				$request->server->get('HTTP_USER_AGENT'));
	
		return new Response($sortida);
	}
	
}
