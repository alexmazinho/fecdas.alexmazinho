<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\StreamedResponse;
use FecdasBundle\Classes\FlushHelper;
use FecdasBundle\Form\FormLlicenciaRenovar;

class CronController extends BaseController {

	public function registrarsaldosAction(Request $request) {
		// Registre diari de saldos
		/* Planificar cron diari a les 00:05 => entrades dia anterior
		 * wget -O - -q http://fecdas.dev/registrarsaldos?secret=abc...  */
		
		$current = $this->getCurrentDate('now');
		
		$current->sub(new \DateInterval('P1D')); // Sub 1 dia;
	
		try {
			$em = $this->getDoctrine()->getManager();
			
			$strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
			//$strQuery .= " WHERE (c.databaixa IS NULL OR c.databaixa > :current) AND c.activat = 1 ";
			$strQuery .= " WHERE (c.databaixa IS NULL OR c.databaixa > :current) ";
			$strQuery .= " AND c.codi <> :test ";
			$strQuery .= " ORDER BY c.codi";
			$query = $em->createQuery($strQuery);
			$query->setParameter('current', $current->format('Y-m-d H:i:s') );
			$query->setParameter('test', BaseController::CODI_CLUBTEST );
			
			$clubs = $query->getResult();
			
			// Registrar nova entrada taula saldos real per dia anterior
			foreach ($clubs as $club) {
				$this->registrarSaldoClub($club, $current);
			}
			
			$em->flush();
			
			$desde = \DateTime::createFromFormat('Y-m-d H:i:s', $current->format('Y-m-d').' 00:00:00'); 
			$fins = \DateTime::createFromFormat('Y-m-d H:i:s', $current->format('Y-m-d').' 23:59:59'); 
			
			// Revisar factures dia anterior i actualitzar entrada/sortida segons datafactura
			$factures = $this->facturesEntre($desde, $fins);
			foreach ($factures as $factura) {
				$comanda = $factura->esAnulacio()?$factura->getComandaAnulacio():$factura->getComanda();
				
				$import = $factura->getImport();
				$data = $factura->getDatafactura();
				
				$this->registrarMovimentClub($current, $comanda->getClub(), 0, $import, $data);
			}
			// Revisar rebuts dia anterior i actualitzar entrada/sortida segons datapagament
			$rebuts   = $this->rebutsEntre($desde, $fins);
					
			foreach ($rebuts as $rebut) {
				
				$import = $rebut->getImport();
				$data = $rebut->getDatapagament();
				
				$this->registrarMovimentClub($current, $rebut->getClub(), $import, 0, $data);
			}
			
			$em->flush();

			$this->logEntryAuth('REGISTRE SALDOS OK', $current->format('Y-m-d H:i:s'));		
			
		} catch (\Exception $e) {
			
			$this->logEntryAuth('ERROR REGISTRE SALDOS', $current->format('Y-m-d H:i:s').' => '.$e->getMessage());	
        }
		
		return new Response('OK');
	}	

	
				
	/*
	public function registrarsaldosAction(Request $request) {
		// Registre diari de saldos
		// Planificar cron diari a les 23:45
		// wget -O - -q http://fecdas.dev/registrarsaldos?secret=abc...  
		
		$current = $this->getCurrentDate('now');
		
		try {
			$em = $this->getDoctrine()->getManager();
			
			$current->sub(new \DateInterval('PT1S')); // Sub 1 segon;
			
			$strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
			$strQuery .= " WHERE (c.databaixa IS NULL OR c.databaixa > :current) AND c.activat = 1 ";
			$strQuery .= " AND c.codi <> :test ";
			$strQuery .= " ORDER BY c.codi";
			$query = $em->createQuery($strQuery);
			$query->setParameter('current', $current->format('Y-m-d H:i:s') );
			$query->setParameter('test', BaseController::CODI_CLUBTEST );
			
			$clubs = $query->getResult();
			
			foreach ($clubs as $club) {
				
				$strQuery  = " SELECT MAX(s.dataentrada) FROM FecdasBundle\Entity\EntitySaldos s ";
				$strQuery .= " WHERE s.club = :club ";
				$query = $em->createQuery($strQuery);
				$query->setParameter('club', $club->getCodi() );
				
				$ultimRegistre = $query->getSingleScalarResult();
				$comentaris = "";
				
				if ($ultimRegistre != null) {
					$desde = \DateTime::createFromFormat('Y-m-d H:i:s', $ultimRegistre);
				} else {
					// Nou club. Crear registre a 0
					$this->logEntryAuth('REGISTRE SALDOS NOU CLUB', 'Club sense registre anterior => '.$current->format('Y-m-d H:i:s').' => '.$club->getNom());	
					
					$desde = \DateTime::createFromFormat('Y-m-d H:i:s', $current->format('Y-m-d').' 00:00:00'); // Des d'avui
					
					$comentaris = "Registre saldo inicial";
				}
				
				if ($desde->format('Y-m-d') < $current->format('Y-m-d')) {  // Només 1 registre per dia
				
					// Consultar factures entrades entrats dia current
					$entrades = 0;
					$sortides = 0;
					
					$factures = $this->facturesEntre($desde, $current);
					
					foreach ($factures as $factura) {
						$comanda = $factura->esAnulacio()?$factura->getComandaAnulacio():$factura->getComanda();
						
						if ($club->getCodi() == $comanda->getClub()->getCodi()) {
							
							$import = $factura->getImport();
							
							$sortides += $import;
						}
					}
					
					// Consultar rebuts entrats dia current
					$rebuts   = $this->rebutsEntre($desde, $current);
					
					foreach ($rebuts as $rebut) {
						
						if ($club->getCodi() == $rebut->getClub()->getCodi()) {
							
							$import = $rebut->getImport();
							
							$entrades += $import;
						}
					}				
					
					$saldos = new EntitySaldos($club, $entrades, $sortides);
					$saldos->setRomanent( $club->getRomanent() );
					$saldos->setTotalllicencies( $club->getTotalllicencies() );
					$saldos->setTotalduplicats( $club->getTotalduplicats() );
					$saldos->setTotalaltres( $club->getTotalaltres() );
					$saldos->setTotalpagaments( $club->getTotalpagaments() ); 
					$saldos->setAjustsubvencions( $club->getAjustsubvencions() );
					$saldos->setComentaris($comentaris);
					
					$saldos->setDataentrada( $current ); 
				
					$em->persist($saldos);
				} else {
					$this->logEntryAuth('REGISTRE SALDOS EXISTENT', 'Registre club existent => actual: '.$current->format('Y-m-d H:i:s').', existent '.$desde->format('Y-m-d H:i:s').', club: '.$club->getNom());	
				}
			}
			
			$em->flush();

			$this->logEntryAuth('REGISTRE SALDOS OK', $current->format('Y-m-d H:i:s'));		
			
		} catch (\Exception $e) {
			
			$this->logEntryAuth('ERROR REGISTRE SALDOS', $current->format('Y-m-d H:i:s').' => '.$e->getMessage());	
        }
		
		return new Response('');
	}	*/

	public function checkrenovacioAction(Request $request) {
		// Avís renovació partes: 30 dies, 15 dies i 2 dies
		/* Planificar cron diari
		 * wget -O - -q http://fecdas.dev/checkrenovacio?secret=abc... >> mailsrenovacio.txt*/
		
		/*
		 * (SELECT p.id, (SELECT c.nom FROM m_clubs c WHERE c.codi = p.clubparte), DATE_FORMAT(p.dataalta, '%d/%m/%Y'), 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 365 DAY, '%d/%m/%Y') as datacaducitat, 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 335 DAY, '%d/%m/%Y') as datanotificacio,
		 		 p.numrelacio, t.id, t.descripcio  
		 		 FROM m_partes p INNER JOIN m_tipusparte t ON p.tipus = t.id 
		 		 WHERE p.tipus IN (7,10) AND 
				(YEAR(p.dataalta) = YEAR(now()) OR (YEAR(p.dataalta) = YEAR(now()) - 1 AND MONTH(p.dataalta) >= MONTH(now())))
			) UNION
			(SELECT p.id, (SELECT c.nom FROM m_clubs c WHERE c.codi = p.clubparte), DATE_FORMAT(p.dataalta, '%d/%m/%Y'), 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 365 DAY, '%d/%m/%Y') as datacaducitat, 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 350 DAY, '%d/%m/%Y') as datanotificacio,
		 		 p.numrelacio, t.id, t.descripcio  
		 		 FROM m_partes p INNER JOIN m_tipusparte t ON p.tipus = t.id 
		 		 WHERE p.tipus IN (7,10) AND 
				(YEAR(p.dataalta) = YEAR(now()) OR (YEAR(p.dataalta) = YEAR(now()) - 1 AND MONTH(p.dataalta) >= MONTH(now())))
			) UNION
			(SELECT p.id, (SELECT c.nom FROM m_clubs c WHERE c.codi = p.clubparte), DATE_FORMAT(p.dataalta, '%d/%m/%Y'), 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 365 DAY, '%d/%m/%Y') as datacaducitat, 
		 		 DATE_FORMAT(p.dataalta + INTERVAL 363 DAY, '%d/%m/%Y') as datanotificacio,
		 		 p.numrelacio, t.id, t.descripcio  
		 		 FROM m_partes p INNER JOIN m_tipusparte t ON p.tipus = t.id 
		 		 WHERE p.tipus IN (7,10) AND 
				(YEAR(p.dataalta) = YEAR(now()) OR (YEAR(p.dataalta) = YEAR(now()) - 1 AND MONTH(p.dataalta) >= MONTH(now())))
			) ORDER BY datanotificacio

		 * */
		$sortida = "";
		try {
    		$this->validateCronAuth($request, "check renovació");

    		$sortida .= $this->checkRenovacio($request, 30);
    		
    		$sortida .= $this->checkRenovacio($request, 15);
    		
    		$sortida .= $this->checkRenovacio($request, 2);
		
        } catch (\Exception $e) {
            return new Response($e->getMessage());
        }
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
		
		foreach ($partesrenovar as $parte) {
			$tomails = array();
			$subject = "Notificació. Renovació llicència FECDAS";
			$club = $parte->getClubparte();
			/* Per cada parte */
			if ($club->getMail() == null || $club->getMail() == '') { 
				$subject .= ' (Cal avisar aquest club no té adreça de mail al sistema)';
			} else {
			    $tomails = $club->getMails();
			}
			
			foreach ($parte->getLlicencies() as $llicencia) {
				/* Per cada llicència del parte */
				if ($this->checkSendMail($llicencia) == true) {
					$body = $this->renderView('FecdasBundle:Cron:renovacioEmail.html.twig',
							array('llicencia' => $llicencia, 'dies' => $dies));
					$this->buildAndSendMail($subject, $tomails, $body);
					$sortida .= $body;
						
					$this->logEntry('alexmazinho@gmail.com', 'CRON RENEW',
							$request->server->get('REMOTE_ADDR'),
							$request->server->get('HTTP_USER_AGENT'), 
					    'club ' . $club->getNom() . ', llicència ' . $llicencia->getId() . ', dies ' .  $dies);
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
	
	public function enviamentfacturesrebutsAction(Request $request) {
		// Planificar cron cada 5/10 minuts
		//    */5 * * * * /usr/bin/wget -O - -q http://www.fecdasgestio.cat/enviamentfacturesrebuts?periode=1&secret=abc...
		
        try {
            $this->validateCronAuth($request, "enviament factures i rebuts");
    
            // Enviament de les factures dels partes consolidats del darrer dia (o periode indicat)
    		$periode = $request->query->get('periode', 1); // 1 dia endarrera per defecte 
    		
    		$datadesde = $this->getCurrentDate()->sub(new \DateInterval('P'.$periode.'D')); // Mirar endarrera els dies indicats a periode
    		
    		$em = $this->getDoctrine()->getManager();
    		
    		$strQuery  = " SELECT f FROM FecdasBundle\Entity\EntityFactura f "; 
    		$strQuery .= " WHERE f.enviada = 0 ";
    		$strQuery .= " AND f.dataentrada >= :datadesde ";
    		$strQuery .= " ORDER BY f.dataentrada ";
    		
    		$query = $em->createQuery($strQuery)->setParameter('datadesde', $datadesde->format('Y-m-d H:i:s'));
    										
    		$facturesNoEnviades = $query->getResult();
    		
    
    		$strQuery  = " SELECT r FROM FecdasBundle\Entity\EntityRebut r "; 
    		$strQuery .= " WHERE r.enviat = 0 ";
    		$strQuery .= " AND r.databaixa IS NULL ";
    		$strQuery .= " AND r.dataentrada >= :datadesde ";
    		$strQuery .= " ORDER BY r.dataentrada ";
    		
    		$query = $em->createQuery($strQuery)->setParameter('datadesde', $datadesde->format('Y-m-d H:i:s'));
    										
    		$rebutsNoEnviats = $query->getResult();
    		
    		
    		$comandes = array();
    		foreach ($facturesNoEnviades as $factura) {
    			$comanda = $factura->getComandaFactura();
    			
    			if ($comanda->comandaConsolidada() == true && !isset($comandes[$comanda->getId()])) $comandes[$comanda->getId()] = $comanda;
    		}
    		
            echo " Ingressos per notificar ".count($rebutsNoEnviats)."<br/>";
            
    		foreach ($rebutsNoEnviats as $rebut) {
    			// Ingressos i rebuts de vàries comandes s'envien a part
    			$comandesRebut = $rebut->getComandes();
    			
    			if ($rebut->esIngres() == true || ($comandesRebut != null && count($comandesRebut) > 1)) {
    
    				$log = $this->notificarIngressosPerMail($rebut);
    				
    				$this->logEntryAuth('NOTIF. INGRES OK', $rebut->getNumRebut(). ': ' .$log);
    			
    				echo " Ingrès notificat ".$rebut->getNumRebut(). ': ' .$log."<br/>";
    			
    			} else {
    				$comanda = null;
    				if ($comandesRebut != null && count($comandesRebut) == 1) $comanda = $comandesRebut[0];
    				else $comanda = $rebut->getComandaanulacio();
    
    				if ($comanda != null && $comanda->comandaConsolidada() == true && !isset($comandes[$comanda->getId()]))	$comandes[$comanda->getId()] = $comanda;			
    			}				
    		}
    		
            echo " Comandes per notificar ".count($comandes)."<br/>";
    		foreach ($comandes as $comanda) {
    			
    			$log = $this->notificarFacturesRebutsPerMail($comanda);
    			
    			$this->logEntryAuth('NOTIF. COMANDA OK', $comanda->getNumComanda(). ': ' .$log);
    			
    			echo " Comanda notificada ".$comanda->getNumComanda(). ': ' .$log."<br/>";
    		}
		} catch (\Exception $e) {
            return new Response($e->getMessage());
        }
        
		return new Response("FINAL ENVIAMENT FACTURES i REBUTS");
	}

	private function notificarIngressosPerMail($rebut) {
		// Ingressos no associats a cap comanda o ingressos associats a múltiples comandes
		
		/*
		 * Ingrés cap factura
		 * ingrés múltiples factures
		 */ 
		
		if ($rebut == null) return;
		
		$club = $rebut->getClub();
		
		$subject = ""; 
		if ($club->getMail() != null && $club->getMail() != '') $tomails = $club->getMails();
		else {
			$tomails = self::getCarnetsMails();
			$subject .= " (CLUB SENSE CORREU DE CONTACTE) ";
		}
		
		$subject .= "Federació Catalana d'Activitats Subaquàtiques. Notificació ingrès ".$rebut->getNumRebut();
		
		$log = 'ingrès: '.$rebut->getNumRebut();
		
		$body = "<p>Benvolgut club ".$club->getNom()."</p>";
		$body .= "<p>Us fem arribar el rebut <b>".$rebut->getNumRebut()."</b> corresponent a l'ingrès tramitat en data <b>".$rebut->getDatapagament()->format('d/m/Y')."</b></p>";
		
		if (count($rebut->getComandes()) > 0) {
			// Ingrés múltiples comandes
			$body .= "<p>En concepte d'abonament de les factures ".$rebut->getLlistaNumsFactures()."</p>";
			$log .= ', abonament factures: '.$rebut->getLlistaNumsFactures();
		}
		
		$attachments = array();
		
		$pdf = $this->rebuttopdf($rebut);
			
		$nom =  "rebut_". str_replace("/", "-", $rebut->getNumRebut()) . "_" . $club->getCodi() . ".pdf";
			
		$attachments[] = array( 'name' => $nom,
									//'data' => $attachmentData = $pdf->Output($attachmentName, "E") 	// E: return the document as base64 mime multi-part email attachment (RFC 2045)
									'data' => $pdf->Output($nom, "S")  // S: return the document as a string (name is ignored).)
							);
		
		$this->buildAndSendMail($subject, $tomails, $body, array(), null, $attachments);

		$rebut->setEnviat(true);
		// Si tot correcte actualitzar rebut a enviat		
		$em = $this->getDoctrine()->getManager();
		
		$em->flush();
		
		return $log;
	}


	private function notificarFacturesRebutsPerMail($comanda) {
		/*
		 * Factura no pagada
		 * Factura (pagada) amb rebut 
		 * Rebut factura enviada anteriorment 
		 * Factura amb anul·lacions
		 * Factura (pagada) amb rebut i anul·lacions
		 * Anul·lacions
		 * Anul·lacions amb pagaments
		 * 
		 */ 
		
		if ($comanda == null || $comanda->getFactura() == null) return;
		
		$club = $comanda->getClub();
		
		$subject = "Federació Catalana d'Activitats Subaquàtiques. Notificació comanda ".$comanda->getNumComanda();
		
		if ($club->getMail() != null && $club->getMail() != '') $tomails = $club->getMails();
		else {
			$tomails = self::getCarnetsMails();
			$subject .= ' (CLUB SENSE CORREU DE CONTACTE)';
		}
				
		$body = "<p>Benvolgut club ".$club->getNom()."</p>";
		$body .= "<p>Us fem arribar els següents documents relacionats amb la comanda <b>".$comanda->getNumComanda()."</b> tramitada en data <b>".$comanda->getDataentrada()->format('d/m/Y')."</b></p>";
		
		$log = '';
		$factures = array();
		$strNumsFactures = '';
		$rebuts = array();
		$strNumsRebuts = '';
		$attachments = array();
		
		// Revisar factura i rebuts normals
		$factura = $comanda->getFactura();
		$rebut = $comanda->getRebut();
		if ($factura->getEnviada() != true) {
			$factures[] = $factura;  
			$strNumsFactures .= $factura->getNumFactura();
			$log .= 'F.'.$factura->getNumFactura().' ';

			$factura->setEnviada(true);
			if ($rebut != null) {
				$rebut->setEnviat(true);// El rebut normal s'imprimeix junt amb la factura 
				$strNumsRebuts .= $rebut->getNumRebut();
				$log .= 'R.'.$rebut->getNumRebut().' '; 
			}
		} else {
			if ($rebut != null && $rebut->getEnviat() != true) {
				$rebut->setEnviat(true);
				$rebuts[] = $rebut; // La factura ja estava enviada
				$strNumsRebuts .= $rebut->getNumRebut();
				$log .= 'R.'.$rebut->getNumRebut().' ';
			}
		}
		if ($strNumsFactures != '') $body .= "<p>Factura: ".$strNumsFactures."</p>";
		if ($strNumsRebuts != '') $body .= "<p>Rebut: ".$strNumsRebuts."</p>";
	
		// Revisar anul·lacions
		$strNumsFactures = '';
		$strNumsRebuts = '';
		
 		foreach ($comanda->getFacturesanulacions() as $factura) {
			if ($factura->getEnviada() != true) {
				$strNumsFactures .= $factura->getNumFactura().', ';
				$log .= 'F.'.$factura->getNumFactura().' ';
				$factures[] = $factura;
				$factura->setEnviada(true);
			}
		}
		
		foreach ($comanda->getRebutsanulacions() as $rebut) {
			if ($rebut->getEnviat() != true) {
				$strNumsRebuts .= $rebut->getNumRebut().', ';
				$log .= 'R.'.$rebut->getNumRebut().' ';
				$rebuts[] = $rebut;
				$rebut->setEnviat(true);
			}
		}
		
		if ($strNumsFactures != '' || $strNumsRebuts != '') $body .= "<br/><h3>Anul·lacions</h3>";
		
		if ($strNumsFactures != '') $body .= "<p>Factures: ".substr($strNumsFactures, 0, -2)."</p>";
		if ($strNumsRebuts != '') $body .= "<p>Rebuts: ".substr($strNumsRebuts, 0, -2)."</p>";  
		
		// Crear adjunts
		foreach ($factures as $factura) {
			$pdf = $this->facturatopdf($factura);
			
			$nom = ($factura->esAnulacio()?"factura_anul_lacio_":"factura_") .  str_replace("/", "-", $factura->getNumFactura()) . "_" . $club->getCodi() . ".pdf";
			
			$attachments[] = array( 'name' => $nom,
									//'data' => $attachmentData = $pdf->Output($attachmentName, "E") 	// E: return the document as base64 mime multi-part email attachment (RFC 2045)
									'data' => $pdf->Output($nom, "S")  // S: return the document as a string (name is ignored).)
									);
		}
		
		foreach ($rebuts as $rebut) {
			$pdf = $this->rebuttopdf($rebut);
			
			$nom =  ($rebut->esAnulacio()?"rebut_anul_lacio_":"rebut_") .  str_replace("/", "-", $rebut->getNumRebut()) . "_" . $club->getCodi() . ".pdf";
			
			$attachments[] = array( 'name' => $nom,
									//'data' => $attachmentData = $pdf->Output($attachmentName, "E") 	// E: return the document as base64 mime multi-part email attachment (RFC 2045)
									'data' => $pdf->Output($nom, "S")  // S: return the document as a string (name is ignored).)
									);
		}
		
		$this->buildAndSendMail($subject, $tomails, $body, array(), null, $attachments);

		// Si tot correcte actualitzar factures i rebuts a enviats		
		$em = $this->getDoctrine()->getManager();
		
		$em->flush();
		
		return $log;
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
			$response = $this->redirect($this->generateUrl('FecdasBundle_dadespersonals'));
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

		$parterenovar = $llicenciaarenovar->getParte();
		if ($request->getMethod() != 'POST')  $this->logEntryAuth('RENOVAR LLICENCIA VIEW',	$parterenovar->getId());
		
		/* Validació impedir modificacions altres clubs */
		if ($this->isCurrentAdmin() != true && $parterenovar->getClubparte()->getCodi() != $currentClub) return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
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
			if ($llicenciaarenovar->getParte()->getDataCaducitat() > $dataalta) {
				$dataalta = $llicenciaarenovar->getParte()->getDataCaducitat();
				$dataalta->setTime(00, 00);
				$dataalta->add(new \DateInterval('P1D')); // Add 1 dia
			}
			/* Crear el nou parte */
			$parte = $this->crearComandaParte($dataalta, $llicenciaarenovar->getParte()->getTipus(), $parterenovar->getClubparte(), 'Renovació llicència');

			// Afegir llicència		
			$cloneLlicencia = clone $llicenciaarenovar;
			
	
			/* Init camps */
			$cloneLlicencia->setDatacaducitat($parte->getDataCaducitat());
			$cloneLlicencia->setDatamodificacio($this->getCurrentDate());
			
			$cloneLlicencia->setImpresa(false);
			$cloneLlicencia->setDataimpressio(null);
			$cloneLlicencia->setMailenviat(false);
			$cloneLlicencia->setDatamail(null);	
			
			$parte->addLlicencia($cloneLlicencia);
			
			$em->persist($cloneLlicencia);

			/* Preparar formulari */
			$form = $this->createForm(new FormLlicenciaRenovar($this->isCurrentAdmin()),$cloneLlicencia);
			
			$form->get('cloneid')->setData($llicenciaid);  // Posar id
			$form->get('personashow')->setData($cloneLlicencia->getPersona()->getLlistaText());  // Nom + cognoms
			$form->get('datacaducitatshow')->setData($parte->getDataCaducitat()); 
			
			
			if ($request->getMethod() == 'POST') {
				$form->handleRequest($request);
			
				if ($form->isValid() && $request->request->has('llicencia_renovar')) {
	
				    // Crear factura
				    $this->crearFactura($parte);
			
					$this->addParteDetall($parte, $cloneLlicencia);
					$this->validaParteLlicencia($parte, $cloneLlicencia);
				
					// Marquem com renovat
					$parte->setRenovat(true);
					$parte->setComentaris('Renovació llicència:'.' '.$parte->getComentariDefault().' '.$cloneLlicencia->getPersona()->getNomCognoms());
					
					$em->flush();
			
					$this->logEntryAuth('RENOVAR LLICENCIA OK',	$llicenciaarenovar->getParte()->getId().' renovat a '.$parte->getId());
					
					$this->get('session')->getFlashBag()->add('sms-notice',	'Llicència enviada correctament');
			
					return $this->redirect($this->generateUrl('FecdasBundle_parte', array('id' => $parte->getId(), 'action' => 'view', 'source' => 'renovacio')));
			
				} else {
					throw new \Exception('Error validant les dades. Contacta amb l\'adminitrador');
				}
			}			
		} catch (\Exception $e) {

			$em->clear();
			
			$this->logEntryAuth('RENOVAR LLICENCIA KO',	$llicenciaarenovar->getParte()->getId().' '.$e->getMessage());
							
			$this->get('session')->getFlashBag()->add('error-notice',$e->getMessage());
		}
			
		return $this->render('FecdasBundle:Cron:renovarllicencia.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'parte' => $parte)));
	}
	
	
	public function tancamentanysaldosAction(Request $request) {
		/*
		 * http://www.fecdas.dev/tancamentanysaldos?secret=abc...&format=html&page=1&perpage=50&current=2015&update=0&clubs[]=CATXXX&clubs[]=CATYYY 	=> consulta varis clubs opcional
		 * 
		 * Planificar cron anual 31 de desembre ¿?
		 * wget -O - -q http://fecdas.dev/app_dev.php/tancamentanysaldos?secret=abc... >> tancamentanysaldos.html*/
		$em = $this->getDoctrine()->getManager();
		
		$sortidaHtml = ""; 
		try { 
    		$this->validateCronAuth($request, "tancament any saldos");
			
			$current = $request->query->get('current', 0);
			$format = $request->query->get('format', 'html');  // formats => html o csv 
			$page = $request->query->get('page', 1);   
			$perpage = $request->query->get('perpage', 1);
			
						
			if ($current < 2000) throw new \Exception('Cal indicar l\'any de tancament');
			
			$update = ($request->query->get('update', 0) == 0?false:true);
    		
    		$clubsCodisArray = $request->query->get('clubs', array());
			
			$clubs = array();
			if (count($clubsCodisArray) > 0) {
				foreach ($clubsCodisArray as $codi) {
					$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
					if ($club != null) $clubs[] = $club;
				}
			} else {
				// Tots els clubs no baixa
				$clubs = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->findBy(array('databaixa' => null), array('codi' => 'ASC'), $perpage, ($page - 1) * $perpage + 1);// Suma un per no mostrar CAT000
			}
			
			
			if ($format == 'csv') $linebreak = PHP_EOL;
			else $linebreak = '<br/>';
			
			

			if ($format == 'html') {
				$header = array('codi', 'nom', 'saldo ACTUAL', 'romanent '.$current, 'total pagaments '.$current.' + '.($current+1), 
							'total '.$current.' + '.($current+1).' (llicencies + duplicats + altres) = total comandes',
							'suma '.$current.' (llicencies + duplicats + altres + correccio anul·lacions) = suma comandes',
							'suma '.$current.' (factures - anul·lacions) = suma facturat', 'error facturacio '.$current,  
							'error facturació següent '.($current +1), 'suma pagaments '.$current.' + '.($current+1), 'error pagaments '.$current.' + '.($current+1), 
							'suma comandes current + següent '.$current.' + '.($current+1), 'error comandes suma != total', 
							'romanent '.($current+1), 'total pagaments '.($current+1), 'total llicencies '.($current+1), 
							'total duplicats '.($current+1), 'total altres '.($current+1), 'ajust subvencions '.($current+1),
							'saldo resultant '.($current+1), 'altres errors'  ); 
							
				$sortidaHtml .= "<style>
									.saldos-tancament { table-layout: fixed; width: 3650px;  }
									thead {  }
									tbody {  }
									.container-scrollable { height: 800px; overflow-y: auto; overflow-x: hidden; width: 3648px; display: block;  }
									/*thead, tbody { display: block; width: 100%;  }
									tbody { height: 200px; width: 100%; overflow-x: auto; overflow-y: hidden; }*/
									.saldos-tancament tr th:nth-child(1), .saldos-tancament tr td:nth-child(1) { width: 60px; }
									.saldos-tancament tr th:nth-child(2), .saldos-tancament tr td:nth-child(2) { width: 150px; }
									.saldos-tancament tr th:nth-child(3), .saldos-tancament tr td:nth-child(3),
									.saldos-tancament tr th:nth-child(4), .saldos-tancament tr td:nth-child(4),
									.saldos-tancament tr th:nth-child(5), .saldos-tancament tr td:nth-child(5) { width: 80px; }
									.saldos-tancament tr th:nth-child(6), .saldos-tancament tr td:nth-child(6) { width: 300px; } 
									.saldos-tancament tr th:nth-child(7), .saldos-tancament tr td:nth-child(7) { width: 350px; } 
									.saldos-tancament tr th:nth-child(8), .saldos-tancament tr td:nth-child(8) { width: 300px; }
									.saldos-tancament tr th:nth-child(9), .saldos-tancament tr td:nth-child(9), 
									.saldos-tancament tr th:nth-child(10), .saldos-tancament tr td:nth-child(10) { width: 100px; } 
									.saldos-tancament tr th:nth-child(11), .saldos-tancament tr td:nth-child(11) { width: 250px; } 
									.saldos-tancament tr th:nth-child(12), .saldos-tancament tr td:nth-child(12) { width: 100px; }
									.saldos-tancament tr th:nth-child(13), .saldos-tancament tr td:nth-child(13) { width: 250px; }
									.saldos-tancament tr th:nth-child(14), .saldos-tancament tr td:nth-child(14) { width: 100px; }
									.saldos-tancament tr th:nth-child(15), .saldos-tancament tr td:nth-child(15), 
									.saldos-tancament tr th:nth-child(16), .saldos-tancament tr td:nth-child(16),
									.saldos-tancament tr th:nth-child(17), .saldos-tancament tr td:nth-child(17),
									.saldos-tancament tr th:nth-child(18), .saldos-tancament tr td:nth-child(18),
									.saldos-tancament tr th:nth-child(19), .saldos-tancament tr td:nth-child(19),
									.saldos-tancament tr th:nth-child(20), .saldos-tancament tr td:nth-child(20),
									.saldos-tancament tr th:nth-child(21), .saldos-tancament tr td:nth-child(21),
									.saldos-tancament tr th:nth-child(22), .saldos-tancament tr td:nth-child(23) { width: 350px; }

									
									.saldos-tancament tr td:nth-child(3),
									.saldos-tancament tr td:nth-child(21) { color: blue;  }

									.saldos-tancament tr td:nth-child(5),
									.saldos-tancament tr td:nth-child(11) { color: green;  }
									
									.saldos-tancament tr td:nth-child(9) { color: blue;  }
									
									.saldos-tancament tr td:nth-child(9),
									.saldos-tancament tr td:nth-child(10),
									.saldos-tancament tr td:nth-child(12),
									.saldos-tancament tr td:nth-child(14),
									.saldos-tancament tr td:nth-child(22) { color: red;  }
									
									
									.saldos-tancament { font-size: 12px; border-collapse: collapse; }
									.saldos-tancament tr th, .saldos-tancament tr td { text-align: right; /*white-space: nowrap;*/ }
									.saldos-tancament tr td:first-child, .saldos-tancament tr th:first-child { text-align: left; }
									.saldos-tancament tr td:first-child { font-size: 0.9em; }
									.saldos-tancament tr td:last-child, .saldos-tancament tr th:last-child { text-align: left; }
									.saldos-tancament tr td:last-child { font-size: 0.9em; /*min-width: 500px;*/  }
								</style>";
				$sortidaHtml .= "<table class='saldos-tancament taula-cap' cellpadding='8' cellspacing='0' border='1'><thead><tr><th>".implode("</th><th>", $header)."</th></tr></thead></table>
								<div class='container-scrollable'><table class='saldos-tancament taula-cos' cellpadding='8' cellspacing='0' border='1'><tbody>";
			} else {
				$header = array('codi', 'nom', 'saldo', 'romanent '.$current, 'total pagaments '.$current.' + '.($current+1),  
							'total llicencies '.$current.' + '.($current+1), 'total duplicats '.$current.' + '.($current+1), 
							'total altres '.$current.' + '.($current+1), 'total comandes '.$current.' + '.($current+1),
							'suma llicencies '.$current, 'suma duplicats '.$current, 'suma altres '.$current, 'correccio anul·lacions', 
							'suma comandes calculades '.$current, 'suma comandes '.$current,
							'suma factures '.$current, 'suma anul·lacions '.$current, 'suma facturat '.$current, 'error facturacio '.$current,  
							'suma comandes següent '.($current+1), 'suma factures següent '.($current+1), 
							'suma anul·lacions següent '.($current+1), 'error facturació següent '.($current+1),
							'suma pagaments '.$current, 'suma pagaments següent '.($current+1), 'error pagaments', 
							'suma comandes '.($current+1), 'error comandes suma != total', 
							'romanent '.$current.' + '.($current+1), 'total pagaments '.($current+1), 'total llicencies '.($current+1), 
							'total duplicats '.($current+1), 'total altres '.($current+1), 'ajust subvencions '.($current+1),
							'saldo resultant '.($current+1), 'altres errors' );  
			}
			
			// Per cada club
			$data = array();
			$total = 0;
			
			//$start = ($page - 1) * $perpage;
			
			//if ($start > 0) $clubs = array_slice($clubs, $start);
			
			foreach ($clubs as $club) {
				//if ($club->getCodi() == BaseController::CODI_CLUBTEST) continue;

				//if ($total >= $perpage) continue;	

				// $dadesAnyAnterior => comandes entrades 2016 però amb factura 2015 ¿?
				
				$dades = $club->getDadesCurrent(true, false, $current);  // Generar errors
			
				//$dadesAnySeguent = $club->getDadesAnySeguent($current);  // Només poden ser comandes de llicències
				$dadesAnySeguent = $club->getDadesCurrent(true, false, $current + 1);  // Només poden ser comandes de llicències
				
				
				$totalComandes = $club->getTotalllicencies()+$club->getTotalduplicats()+$club->getTotalaltres();
				
				$row = array(
					'codi'				=> $club->getCodi().($format == 'html'?'<br/>':'-').$club->getCompte(),
					'nom' 				=> $club->getNom(),
					'saldo'				=> number_format($club->getSaldo(), 2, ',', '.'),
					'romanent'			=> number_format($club->getRomanent(), 2, ',', '.'),
					
					'totalpagaments'	=> number_format($club->getTotalpagaments(), 2, ',', '.'),
					);

				if ($format == 'html') {
					$row['totalcomandes'] = number_format($club->getTotalllicencies(), 2, ',', '.') .' + '.
											number_format($club->getTotalduplicats(), 2, ',', '.') .' + '.
											number_format($club->getTotalaltres(), 2, ',', '.') .' = '.
											number_format($totalComandes, 2, ',', '.');
				} else {
					$row['totalllicencies'] = number_format($club->getTotalllicencies(), 2, ',', '.');
					$row['totalduplicats'] 	= number_format($club->getTotalduplicats(), 2, ',', '.');
					$row['totalaltres'] 	= number_format($club->getTotalaltres(), 2, ',', '.');
					$row['totalcomandes'] 	= number_format($totalComandes, 2, ',', '.');
				}								

				if ($format == 'html') {
					$row['sumacomandescalc'] =	number_format($dades['importpartes'], 2, ',', '.') .' + '.
											number_format($dades['importduplicats'], 2, ',', '.').' + '.
											number_format($dades['importaltres'], 2, ',', '.') .' + '.
											number_format($dades['correccioanulacions'], 2, ',', '.') .' = '.
											number_format($dades['importpartes']+$dades['importduplicats']+$dades['importaltres']+$dades['correccioanulacions'], 2, ',', '.') .' ('.
											number_format($dades['import'], 2, ',', '.').')';
				} else {
					$row['sumallicencies'] 		= number_format($dades['importpartes'], 2, ',', '.');
					$row['sumaduplicats'] 		= number_format($dades['importduplicats'], 2, ',', '.');
					$row['sumaaltres'] 			= number_format($dades['importaltres'], 2, ',', '.');
					$row['correccioanulacions'] = number_format($dades['correccioanulacions'], 2, ',', '.');
					$row['sumacomandescalc']	= number_format($dades['importpartes']+$dades['importduplicats']+$dades['importaltres'], 2, ',', '.');
					$row['sumacomandes'] 		= number_format($dades['import'], 2, ',', '.');
				}

				if ($format == 'html') {
					$row['sumafactures']	= number_format($dades['importfactures'], 2, ',', '.') .' '.
											number_format($dades['importanulacions'], 2, ',', '.').' = '.
											number_format($dades['importfactures']+$dades['importanulacions'], 2, ',', '.');
				} else {
					$row['sumafactures']	= number_format($dades['importfactures'], 2, ',', '.');
					$row['sumaanulacions']	= number_format($dades['importanulacions'], 2, ',', '.');
					$row['sumafacturat']	= number_format($dades['importfactures']+$dades['importanulacions'], 2, ',', '.');
				}

				// Comprovació => comandes facturades == suma factures
				$row['errorfacturacio']		=  abs($dades['import'] - ($dades['importfactures']+$dades['importanulacions'])) < 0.01?'':
											number_format(abs($dades['import'] - ($dades['importfactures']+$dades['importanulacions'])), 2, ',', '.');
											
				if ($format == 'html') {
				} else {
					$row['sumacomandesseguent']		= number_format($dadesAnySeguent['import'], 2, ',', '.');
					$row['sumafacturesseguent'] 	= number_format($dadesAnySeguent['importfactures'], 2, ',', '.');
					$row['sumaanulacionsseguent'] 	= number_format($dadesAnySeguent['importanulacions'], 2, ',', '.');
				}	

				// Comprovació => comandes facturades any següent == suma factures any següent 
				$row['errorfacturacioseguent']	= abs($dadesAnySeguent['import'] - ($dadesAnySeguent['importfactures']+$dadesAnySeguent['importanulacions'])) < 0.01?'':
														number_format(abs($dadesAnySeguent['import'] - ($dadesAnySeguent['importfactures']+$dadesAnySeguent['importanulacions'])), 2, ',', '.');

				if ($format == 'html') {
					$row['sumapagaments']	= number_format($dades['pagaments'], 2, ',', '.') .' '.
											number_format($dadesAnySeguent['pagaments'], 2, ',', '.').' = '.
											number_format($dades['pagaments']+$dadesAnySeguent['pagaments'], 2, ',', '.');
				} else {
					$row['sumapagaments']		= number_format($dades['pagaments'], 2, ',', '.');
					$row['sumapagamentsseguent']= number_format($dadesAnySeguent['pagaments'], 2, ',', '.');
				}					

				// Comprovacions => totalpagaments == sumapagaments + sumapagaments any següent
				$row['errorpagaments']	= abs($club->getTotalpagaments() - $dades['pagaments'] - $dadesAnySeguent['pagaments']) < 0.01?'':number_format(abs($club->getTotalpagaments() - $dades['pagaments'] - $dadesAnySeguent['pagaments']), 2, ',', '.');


				if ($format == 'html') {		
					$row['totalcomandescurrentseguent']	= number_format($dades['import'], 2, ',', '.').' + '.
														number_format($dadesAnySeguent['import'], 2, ',', '.').' = '.
														number_format($dades['import'] + $dadesAnySeguent['import'], 2, ',', '.');
																			
				} else {
					$row['totalcomandescurrentseguent']	= number_format($dades['import'] + $dadesAnySeguent['import'], 2, ',', '.');
				}	 

				// Comprovació => total comandes (llicencies + duplicats + altres) == suma comandes current + comandes any següent  
				$row['errorcomandes']		= abs($dades['import'] + $dadesAnySeguent['import'] - $totalComandes) < 0.01?'':
												number_format( abs($dades['import'] + $dadesAnySeguent['import'] - $totalComandes), 2, ',', '.');		
					 
				
				// Càlcul valors per actualitzar a l'any següent
				$nouromanent 		= $club->getSaldo() + $dadesAnySeguent['import'] - $dadesAnySeguent['pagaments'];
				$noutotalanyseguent = $dadesAnySeguent['import'];
				$noutotalllicencies	= $dadesAnySeguent['importpartes'];
				$noutotalduplicats 	= $dadesAnySeguent['importduplicats'];
				$noutotalaltres 	= $dadesAnySeguent['importaltres'];
				$nousaldocalculat 	= $nouromanent + $dadesAnySeguent['pagaments'] - $noutotalllicencies - $noutotalduplicats - $noutotalaltres;

				  
				if (abs($noutotalanyseguent - $noutotalllicencies - $noutotalduplicats - $noutotalaltres) > 0.01) {
					$noutotalllicencies	= 'ERROR REVISAR';
					$noutotalduplicats 	= 'ERROR REVISAR';
					$noutotalaltres 	= 'ERROR REVISAR';
				}

				$row['updateromanent'] = number_format($nouromanent, 2, ',', '.');
				$row['updatetotalpagaments'] = number_format($dadesAnySeguent['pagaments'], 2, ',', '.');
                $row['updatetotalllicencies'] = is_numeric($noutotalllicencies)?number_format($noutotalllicencies, 2, ',', '.'):$noutotalllicencies;
                $row['updatetotalduplicats'] = is_numeric($noutotalduplicats)?number_format($noutotalduplicats, 2, ',', '.'):$noutotalduplicats;
                $row['updatetotalaltres'] = is_numeric($noutotalaltres)?number_format($noutotalaltres, 2, ',', '.'):$noutotalaltres;
				$row['updateajustsubvencions'] = 0;
				$row['saldoresultant'] = number_format( $nousaldocalculat, 2, ',', '.'); // romanent + pagaments + subvencions - llicencies - duplicats - altres 
				
				if (abs($club->getSaldo() - $nousaldocalculat) > 0.01) {
					$row['saldoresultant']	= 'ERROR REVISAR';
				}
				
				$row['errors']				= implode($linebreak,array_merge($dades['errors'], $dadesAnySeguent['errors']));
				
				
				$data[] = $row;
				
				if ($format == 'html') $sortidaHtml .= "<tr><td>".implode("</td><td>", $row)."</td></tr>"; 
				
				$total++;

				if ($update) {
					$club->setRomanent($nouromanent);
					$club->setTotalpagaments($dadesAnySeguent['pagaments']);
					$club->setTotalllicencies($noutotalllicencies);
					$club->setTotalduplicats($noutotalduplicats);
					$club->setTotalaltres($noutotalaltres);	
					$club->setAjustsubvencions(0);				
				}				
			}

			if ($format == 'html') {
				$sortidaHtml .= "</tbody></table></div>";
			} 

			if ($update) {
				$em->flush();				
			}

			if ($format == 'csv') {
				$filename = "saldos_tancament_any_".$current.".csv";
				$response = $this->exportCSV($request, $header, $data, $filename);
			} else {
				$response = new Response( $sortidaHtml );
			}
			
		} catch (\Exception $e) {
            $response = new Response($e->getMessage());
        }
		return $response;
	}
	
	public function checkclubsAction(Request $request) {
		/*
		 * http://www.fecdas.dev/checkclubs?secret=abc...&page=1&update=0&club=CATXXX 	=> consulta, club opcional
		 * http://www.fecdas.dev/checkclubs?secret=abc...&page=1&update=1&club=CATXXX 	=> modificar, club opcional
		 * 
		 * Revisar diferències saldos calculats
		 * Detectar si un club de pagament diferit supera el límit 
		 * Detectar clubs amb partes sense factura 
		 * Planificar cron diari
		 * wget -O - -q http://fecdas.dev/app_dev.php/checkclubs?secret=abc... >> checkclubs.txt*/
		
		$sortida = ""; 
		try { 
    		$this->validateCronAuth($request, "check clubs");
            
    		
    		$update = ($request->query->get('update', 0) == 0?false:true);
    		$page = $request->query->get('page', 1);
    		$club = $request->query->get('club', '');
    		
    		$sortida .= "<style type='text/css'>";
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
    		
    		//$states = explode(";", self::CLUBS_STATES);
    		
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
    		$sortida .= "<p class='comment'>*Romanent: Deute del club acumulat en anys anteriors. Valors negatius indiquen el deute del club club</p>";
    
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
    		foreach ($clubs as $club) {
    			$incidencies = "";
    			
    			$filaClub = "<tr><td class='codi'>" . $club->getCodi() . "</td>";
    			$filaClub .= "<td class='club'>" . $index."-".$club->getNom() . "</td>";
    
    			$dadesClub = $club->getDadesCurrent(true, $update);
    
    			$filaClub .= "<td class='importclub'>" . number_format($club->getTotalllicencies(), 2, ',', '.') . "€</td>"; // Import llicències valor
    			$filaClub .= "<td class='importclub'>" . number_format($club->getTotalduplicats(), 2, ',', '.') . "€</td>"; // Import Kits valor
    			$filaClub .= "<td class='importclub'>" . number_format($club->getTotalaltres(), 2, ',', '.') . "€</td>"; // Import altres valor
    			$filaClub .= "<td class='importclub'>" . number_format($club->getTotalpagaments(), 2, ',', '.') . "€</td>"; // Import pagaments valor
    			$filaClub .= "<td class='importclub'>" . number_format($club->getAjustsubvencions(), 2, ',', '.') . "€</td>"; // Import ajust per subvencions valor
    			$filaClub .= "<td class='importclub'>" . number_format($club->getRomanent(), 2, ',', '.') . "€</td>"; // Romanent valor
    			$filaClub .= "<td class='importclub saldo'>" . number_format($club->getSaldo(), 2, ',', '.') . "€</td>"; // Saldo valor
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
    			    if (!$club->pendentPagament() &&     // Clubs amb crèdit
    					$this->getCurrentDate()->format('Y-m-d') >= $datainiciRevisarSaldos->format('Y-m-d')) {
    						
    					if ($club->getLimitcredit() == null || $club->getLimitcredit() <= 0) {
    						// Init data notificació
    						$club->setLimitnotificacio(null);
    						$dadesClub['errors'][] = ">> (Incidència) Límit de crèdit del club incorrecte " . $club->getLimitcredit();
    					} else {
    						if ($club->getSaldo() > $club->getLimitcredit()) {
    							// Comprovar si ja s'ha enviat la notificació
    							if ($club->getLimitnotificacio() == null) {
    								$dadesClub['errors'][] = ">> (Notificació) Superat el límit de dèbit, s'envia la notificació al club per correu";
    								
    								// Enviar notificació mail
    								$subject = "Notificació. Federació Catalana d'Activitats Subaquàtiques";
    								//if ($club->getMail() == null || $club->getMail() == '') $subject = "Notificació. Cal avisar aquest club no té adreça de mail al sistema";
    									
    								/*$bccmails = $this->getFacturacioMails();
    								$tomails = $club->getMails();
    								$body = "<p>Benvolgut club ".$club->getNom()."</p>";
    								$body .= "<p>Us fem saber que l'import de les tramitacions que heu fet a dèbit en aquest sistema ha arribat als límits establerts.
    								Per poder fer noves gestions, cal que contacteu amb la FECDAS</p>";*/
    									
    								$tomails = $this->getFacturacioMails();
                                    $bccmails = $this->getAdminMails(); 
    								$body = "<p>Club ".$club->getNom()."</p>";
    								$body .= "<p>L'import de les tramitacions que ha fet a dèbit en aquest sistema ha arribat als límits establerts</p>";
    								$body .= "<p>El saldo actual del club és ".number_format($club->getSaldo(), 2, ',', '.')." €</p>";
    								
    								$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
    								
    								$club->setLimitnotificacio($this->getCurrentDate());
    							} else {
    								$dadesClub['errors'][] = ">> (Notificació) Límit de dèbit superat des del dia " . $club->getLimitnotificacio()->format('d-m-Y') . "<br/>"; 
    							}
    							// Estat -> sense tramitació 
    							$club->setEstat($this->getDoctrine()->getRepository('FecdasBundle:EntityClubEstat')->find(self::CLUB_SENSE_TRAMITACIO));
    						} else {
    							$club->setLimitnotificacio(null);
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
    		$tomails = array($this->getParameter('MAIL_ADMINTEST'));
    		$body = $sortida;
    		$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
		
        } catch (\Exception $e) {
            return new Response($e->getMessage());
        }
        
		$helper = new FlushHelper();
				
		return new StreamedResponse(function() use ($helper, $sortida) {
            $helper->out($sortida);
        });
	}
	
	
	public function informesaldosAction(Request $request) {
		/* Informe trimestral de saldos als clubs 
		 * 31 de març, 30 de juny, 30 setembre i 30 novembre
		 * Planificar cron diari
		 * wget -O - -q http://fecdas.dev/app_dev.php/informesaldos?secret=abc... >> informesaldos.txt*/

		$sortida = "";
        
        return new Response(""); // Desactivat de moment
        
		try {
    		$this->validateCronAuth($request, "informe saldos"); 
    		 
    		// Comprovar les dates de l'enviament 
    		$datesinforme = explode(";", self::DATES_INFORME_TRIMESTRAL);
    		$current_dm = $this->getCurrentDate()->format('d/m');
    		if (!in_array($current_dm, $datesinforme)) return new Response("N/A");
    		
    		$em = $this->getDoctrine()->getManager();
    	
    		//$states = explode(";", self::CLUBS_STATES);
    	
    		//$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityClub c WHERE c.activat = 1";
    		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityClub c WHERE c.databaixa IS NULL";
    		$query = $em->createQuery($strQuery);
    		$clubs = $query->getResult();
    
    		
    		$bccmails = array();
    		$tomails = array($this->getParameter('MAIL_ADMINTEST'));
    		
    		foreach ($clubs as $club) {
    		    if ($club->getMail() == null || $club->getMail() == '') $subject = "Notificació. Cal avisar aquest club no té adreça de mail al sistema";
    			else $subject = "Notificació trimestral de l'estat de comptes";
    			
    			$body = "<p>Benvolgut club ".$club->getNom()."</p>";
    			$body .= "<p>Us fem arribar l'estat dels comptes del vostre club amb la Federació a data " . $this->getCurrentDate()->format('d/m/Y') . "</p>";
    			$body .= "<div style='display:table;border-collapse: collapse;'>";
    			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Deute acumulat de l'any anterior:</div>";
    			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club->getRomanent(), 2, ',', '.') . " €</div></div>";
    			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Despesa total en llicències durant l'any actual:</div>";
    			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club->getTotalllicencies(), 2, ',', '.') . " €</div></div>";
    			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Despesa total en kits durant l'any actual:</div>";
    			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club->getTotalduplitas(), 2, ',', '.') . " €</div></div>";
    			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Altres despeses durant l'any actual:</div>";
    			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club->getTotalaltres(), 2, ',', '.') . " €</div></div>";
    			$body .= "<div style='display: table-row'><div style='display: table-cell; padding-right: 10px'>Pagaments realitzats durant l'any actual:</div>";
    			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club->getTotalpagaments(), 2, ',', '.') . " €</div></div>";
    			$body .= "<div style='display: table-row'><div style='display: table-cell; '></div></div>";
    			$body .= "<div style='display: table-row;'><div style='display: table-cell;height: 20px;'></div>";
    			$body .= "<div style='display: table-cell;'></div></div>";
    			$body .= "<div style='display: table-row;border-top:1px solid #eeeeee;'><div style='display: table-cell;height: 20px;'></div>";
    			$body .= "<div style='display: table-cell;'></div></div>";
    			$body .= "<div style='display: table-row;'><div style='display: table-cell; padding-right: 10px'>Saldo total amb la Federació:</div>";
    			$body .= "<div style='display: table-cell;text-align:right'>" . number_format($club->getSaldo(), 2, ',', '.') . " €</div></div>";
    			$body .= "</div>";
    				
    				
    			$body .= "<p>Per a qualsevol dubte, us podeu posar en contacte amb la Federació Catalana d'Activitats Subaquàtiques (FECDAS)</p>";
    			
    		
    			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
    			
    			$sortida .= $body;
    		}
    	
	    } catch (\Exception $e) {
            return new Response($e->getMessage());
        }
	
		$this->logEntry($this->getParameter('MAIL_ADMINLOG'), 'INFORME TRIM CLUBS',
				$this->get('session')->get('remote_addr'),
				$request->server->get('HTTP_USER_AGENT'));
	
		return new Response($sortida);
	}
	
	
	public function checkduplipendentsAction(Request $request) {
        /* Revisar duplicats pendents clubs sense pagament a crèdit
         * Donar de baixa si pendents i fa més de 10 dies que van entrar al sistema  
         * Avisar per mail si falten 2 dies per donar de baixa (fa 8 dies de l'entrada)
         * */
        /* Planificar cron diari
         * wget -O - -q http://fecdas.dev/checkduplipendents?secret=abc >> checkduplipendents.txt*/
        
        $sortida = "";

        try {
            $this->validateCronAuth($request, "duplicats pendents"); 
            
            $em = $this->getDoctrine()->getManager();
            
            /* Update preu partes web */
            // Actualitzar tots els importparte a 0, per a què no dongui error la sincro
            $strQuery = "SELECT d FROM FecdasBundle\Entity\EntityDuplicat d JOIN d.club c ";
            $strQuery .= "WHERE d.databaixa IS NULL AND d.rebut IS NULL AND d.dataentrada >= '2016-08-01 23:59:59'";
            $strQuery .= " AND c.estat <> '".self::CLUB_PAGAMENT_DIFERIT."' ";
            $query = $em->createQuery($strQuery);
       
            $duplicatspendents = $query->getResult();
    
            $sortida = $this->revisioPendents($duplicatspendents, "Duplicat");
        
        } catch (\Exception $e) {
            return new Response($e->getMessage());
        }
        
        $this->logEntry($this->getParameter('MAIL_ADMINLOG'), 'CRON DUPLI PENDENTS',
                $this->get('session')->get('remote_addr'),
                $request->server->get('HTTP_USER_AGENT'), $this->get('kernel')->getEnvironment());
        
        return new Response($sortida);
    }
    
	public function checkpendentsAction(Request $request) {
		/* Revisar partes pendents
		 * Donar de baixa si pendents i fa més de 10 dies que van entrar al sistema  
		 * Avisar per mail si falten 2 dies per donar de baixa (fa 8 dies de l'entrada)
		 * */
		/* Planificar cron diari
		 * wget -O - -q http://fecdas.dev/checkpendents?secret=abc >> checkpendents.txt*/
		
		$sortida = "";
        
        try {
            $this->validateCronAuth($request, "partes pendents"); 
    		
    		$em = $this->getDoctrine()->getManager();
    		
    		/* Update preu partes web */
    		// Actualitzar tots els importparte a 0, per a què no dongui error la sincro
    		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p ";
    		$strQuery .= "WHERE p.databaixa IS NULL  ";
    		$strQuery .= " AND p.pendent = 1 ";
    		$query = $em->createQuery($strQuery);
    	
    		$partespendents = $query->getResult();
    	
    		$sortida = $this->revisioPendents($partespendents, "Parte");
	
        } catch (\Exception $e) {
            return new Response($e->getMessage());
        }
		$this->logEntry($this->getParameter('MAIL_ADMINLOG'), 'CRON PENDENTS',
				$this->get('session')->get('remote_addr'),
				$request->server->get('HTTP_USER_AGENT'), $this->get('kernel')->getEnvironment());
		
		return new Response($sortida);
	}
	
    private function revisioPendents($pendents, $tipus) {
        $sortida = "";    
        $em = $this->getDoctrine()->getManager();
        $current = $this->getCurrentDate();
        
        foreach ($pendents as $comanda) {
            $club = $comanda->getClub();
            $dataentrada = $comanda->getDataentrada()->format('d-m-Y');
            
            $interval = $current->diff($comanda->getDataentrada());
            $diesPendent = $interval->format('%a');  //r    Sign "-" when negative, empty when positive
    
            if ($diesPendent <= self::DIES_PENDENT_NOTIFICA) {
                // Enviar mail notificació duplicat nou pendent a Federació
                $subject = ":: Notificació. ".$tipus." pendent ::";
                $tomails = $this->getFacturacioMails();
                $body = "<p>".$tipus." pendent de pagament del club ".$club->getNom();
                $body .= " en data del " . $dataentrada . "</p>";
                        
                $this->buildAndSendMail($subject, $tomails, $body);
                $sortida .= " ".$tipus." pendent >> Notificació Federació ". $club->getNom();
                $sortida .= " (".$tipus." ". $comanda->getId() . " entrat el dia ". $dataentrada .")</br>";
                
                continue;  // següent iteració
            } 
            
            if ($diesPendent == self::DIES_PENDENT_AVIS) {
                // Enviar mail falten 2 dies
                $databaixa = clone $comanda->getDataentrada();
                $databaixa->add(new \DateInterval('P'.self::DIES_PENDENT_MAX.'D')); // Add 10 dies
                            
                $subject = "Notificació. Federació Catalana d'Activitats Subaquàtiques";
                if ($club->getMail() == null || $club->getMail() == '') $subject = "Notificació. Cal avisar aquest club no té adreça de mail al sistema";
                            
                $bccmails = $this->getFacturacioMails();
                $tomails = $club->getMails();
                $body = "<p>Benvolgut club ".$club->getNom()."</p>";
                    
                $itemPendent = "";
                if ($comanda->esParte()) $itemPendent = "de llicències/assegurances";
                if ($comanda->esDuplicat()) {
                    $nomPersona = $comanda->getPersona()->getNomCognoms();
                    $peticioDupli = $comanda->getCarnet()->getProducte()->getDescripcio();
                    $itemPendent = " del ".$peticioDupli." de ".$nomPersona;
                }
                    
                $body .= "<p>Per motius de seguretat administrativa, ens veiem en l’obligació de fer-vos saber que no 
                         podem validar la tramitació ".$itemPendent." feta en la data " . $dataentrada . " 
                         si no se’n fa el pagament abans de la data " . $databaixa->format('d-m-Y') . " 
                         perquè el marge que se’ns permet per a validar-les abans de procedir 
                         és de 10 dies a partir del moment de la tramitació. Gràcies per la vostra comprensió</p>";
                              
                $this->buildAndSendMail($subject, $tomails, $body, $bccmails);
                $sortida .= " ".$tipus." pendent >> Notificació per mail falten 2 dies ". $club->getNom();
                $sortida .= " (".$tipus." ".  $comanda->getId() . " entrat el dia ". $dataentrada .")</br>";
                    
                continue;  // següent iteració
            }
            
            if ($diesPendent > self::DIES_PENDENT_MAX) {
                // anul·lació de la comanda 
                try {    
                    $this->baixaComanda($comanda);
                    $em->flush();
                                    
                    $sortida .= " ".$tipus." pendent >> Baixa més de 10 dies ". $club->getNom();
                    $sortida .= " (".$tipus." ".  $comanda->getId() . " entrat el dia ". $dataentrada .")</br>";
                                
                } catch (\Exception $e) {
                    $em->clear();
                                    
                    $sortida .= " ERROR Baixa ".$tipus." pendent ". $club->getNom();
                    $sortida .= " (".$tipus." " .  $comanda->getId() . " entrat el dia ". $dataentrada .")</br>";
                    $sortida .= " (error: " . $e->getMessage() .")</br>";
                }
                        
                continue;  // següent iteració
            }
            
            // Esperar
            $sortida .= " ".$tipus." pendent >> ". $club->getNom();
            $sortida .= " (".$tipus." ". $comanda->getId() . " entrat el dia ". $dataentrada .")</br>";
        }
        return $sortida;
    }
    
    
	/*private function incidenciesPendents($parte) {
		// Revisar incidències. Parte sincronitzat  o pagat. Enviar mail
		$subject = ":: Incidència revisió partes pendents ::";
		$bccmails = array();
		$tomails = array($this->getParameter('MAIL_ADMINTEST'));
		$club = $parte->getClubparte();
		
		if ($parte->getIdparteAccess() != null) {
			$body = "<h1>Parte pendent sincronitzat</h1>";
			$body .= "<h2>Club : " . $club->getNom() . "</h2>";
			$body .= "<p>Parte " .  $parte->getId() . " entrat el dia ". $parte->getDataentrada()->format('d-m-Y') ."</p>";
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			return true;
		}
		
		if ($parte->getDatapagament() != null || $parte->getImportpagament() != null) {
			$body = "<h1>Parte pendent pagat</h1>";
			$body .= "<h2>Club : " . $club->getNom() . "</h2>";
			$body .= "<h3>Valor 'pendent' no atualitzat  correctament</h3>";
			$body .= "<p>Parte " .  $parte->getId() . " entrat el dia ". $parte->getDataentrada()->format('d-m-Y') ."</p>";
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			return true;
		}
		return false;
	}*/
	
	public function checkpartesdiaAction(Request $request) {
		/* Revisar partes tramitats durant el dia
		 * Validar llicències mateix dni diferents clubs */
		/* Planificar cron diari
		 * wget -O - -q http://fecdas.dev/checkpartesdia?secret=abc >> partesdia.txt*/
	
		$sortida = "";
	
        try {
            $this->validateCronAuth($request, "partes diaris"); 
            
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
    	
    		foreach ($partesavui as $parte) {
    			foreach ($parte->getLlicencies() as $llicencia) {
    			    $club = $parte->getClubparte();
    				if ($llicencia->getDatabaixa() == null) {
    					// Comprovar que no hi ha llicències vigents de la persona en difents clubs, per DNI
    					// Les persones s'associen a un club, mirar si existeix a un altre club
    					$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
    					$strQuery .= " WHERE p.dni = :dni ";
    					$strQuery .= " AND p.club <> :club ";
    					$strQuery .= " AND p.databaixa IS NULL";
    														
    					$query = $em->createQuery($strQuery)
    						->setParameter('dni', $llicencia->getPersona()->getDni())
    						->setParameter('club', $llicencia->getPersona()->getClub()->getCodi());
    												
    					$personaaltresclubs = $query->getResult();
    												
    					foreach ($personaaltresclubs as $persona) {
    						$parteoverlap = $this->validaPersonaTeLlicenciaVigent($llicencia, $persona);
    						if ($parteoverlap != null) {
    							// Enviar mail a FECDAS
    							
    							$subject = "::Llicència Duplicada Diferents Clubs::";
    							$bccmails = array();
    							$tomails = $this->getLlicenciesMails();
    							
    							$body = "<h1>Detectada una llicència duplicada, en data ".$dataavui->format('Y-m-d')."</h1>";
    							$body .= "<h2>Tramitació nova</h2>";
    							$body .= "<p><strong>Club</strong> : " . $club->getNom() . "</p>";
    							$body .= "<p><strong>Llicència per</strong> : ";
    							$body .= $llicencia->getPersona()->getNom() . " " . $llicencia->getPersona()->getCognoms();
    							$body .= " (" . $llicencia->getPersona()->getDni() . ")</p>";
    							$body .= "<h2>Dades de la llicència existent</h2>";
    							$body .= "<p><strong>Club</strong> : " . $persona->getClub()->getNom() . "</p>";
    							
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
    	
        } catch (\Exception $e) {
            return new Response($e->getMessage());
        }
		$this->logEntry($this->getParameter('MAIL_ADMINLOG'), 'CRON PARTES DIA',
				$this->get('session')->get('remote_addr'),
				$request->server->get('HTTP_USER_AGENT'));
	
		return new Response($sortida);
	}
	
}
