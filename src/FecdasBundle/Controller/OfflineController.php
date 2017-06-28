<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use FecdasBundle\Entity\EntityRebut;
use FecdasBundle\Entity\EntityDuplicat;
use FecdasBundle\Entity\EntityComanda;
use FecdasBundle\Entity\EntitySaldos;
use FecdasBundle\Entity\EntityCurs;
use FecdasBundle\Entity\EntityTitulacio;
use FecdasBundle\Controller\BaseController;



class OfflineController extends BaseController {
	
	public function historictitolsAction(Request $request) {
		// http://www.fecdas.dev/historictitols?desde=1&fins=40
		// http://www.fecdas.dev/historictitols?max=10&pag=1
		// http://www.fecdas.dev/historictitols?id=1234
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->query->get('id', 0);
		$max = $request->query->get('max', 1000);
		$pag = $request->query->get('pag', 1);
		$offset = $max * ($pag -1);
		
		//$batchSize = 20;
		
		$strQuery  = " SELECT id, federado, dni, numcurso, iniciocurso, fincurso, ";
		$strQuery .= " club, abreviatura, numtitulo ";
		$strQuery .= " FROM importtitulacions ";
		if ($id > 0) $strQuery .= " WHERE id = ".$id;
		$strQuery .= " ORDER BY numcurso, numtitulo ";
		if ($id == 0) $strQuery .= " LIMIT ".$max. " OFFSET ".$offset;
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$titols = $stmt->fetchAll();

		$cursosNous = 0;
		$titulacions = 0;
		
		$cursos = array();
		$errors = array();
		$ids = array();

		for ($i = 0; $i < count($titols); $i++) {
			$currentTitol = $titols[$i]; 
				
			$id = $currentTitol['id'];
			$ids[] = $id; 
			$dni = $currentTitol['dni'];
			try {
	
				// Cercar meta persona
				$persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $currentTitol['dni'].""));	// Consulta directament com text
				
				if ($persona == null) {
					
					$dniLletra = $dni;
					if (is_numeric($dni) && $dni < 99999999) $dniLletra = str_pad( substr($dni."", 0, 8), 8, "0", STR_PAD_LEFT ).BaseController::getLletraDNI( (int) $dni );
					  
							
					if ($dni != $dniLletra) {
						// Consulta amb lletra
						$persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $dniLletra));	
					}
				}
				
				if ($persona == null) {
					throw new \Exception($id.'#ERROR. Persona no trobada '.$currentTitol['abreviatura'].': #'.$currentTitol['dni'].'#'.$currentTitol['federado']); //ERROR
				}
				
				// Cercar títol (NO pot ser null i només pot trobar un) 
				$titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->findOneBy(array('codi' => $currentTitol['abreviatura']));
				
				if ($titol == null || count($titol) != 1) throw new \Exception($id.'#ERROR. Títol no trobat: #'.$currentTitol['abreviatura']); //ERROR
				
				// Cercar club (pot ser null i només pot trobar un) 
				//$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->findOneBy(array('nom' => $currentTitol['club']));	
				$club = null;
				$clubhistoric = '';
				if ($currentTitol['club'] != '') {
					
					$nomClub = str_replace(".", "", $currentTitol['club']);
					
					$strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
					$strQuery .= " WHERE  c.nom LIKE :club ";
					$query = $em->createQuery($strQuery);
					$query->setParameter('club', '%'.$nomClub.'%' );
					$clubs = $query->getResult();
					
					if ($clubs != null && count($clubs) == 1) {
						$clubs = array_values($clubs);
						$club = $clubs[0];
					} else {
						 $errors[ $id ] = $id.'#WARN. Club no trobat: '.$currentTitol['club'];// WARN CONTINUA
						 $clubhistoric = $currentTitol['club'];
					}
				} else {
					$clubhistoric = 'Sense informació del club';
				}
				
				
				$datadesde = $currentTitol['iniciocurso'] != ''?\DateTime::createFromFormat('Y-m-d', $currentTitol['iniciocurso']):null;
				$datafins = $currentTitol['fincurso'] != ''?\DateTime::createFromFormat('Y-m-d', $currentTitol['fincurso']):null;  
				
				if ($datadesde == null || $datafins == null) throw new \Exception($id.'#ERROR. Dates del curs incorrectes: '.$currentTitol['iniciocurso'].'-'.$currentTitol['fincurso']); //ERROR
				
				// Cercar existent o crear curs sense docents
				$num = $currentTitol['numcurso'];
				if (!isset($cursos[ $num ])) {
					/*$pos = strpos($currentTitol['numcurso'], "/");
					if ($pos === false) throw new \Exception($id.'#ERROR. Número curs format KO: '.$titol); //ERROR
					$num = substr( $currentTitol['numcurso'], $pos+1 ); // Treure any */

					$curs = $this->getDoctrine()->getRepository('FecdasBundle:EntityCurs')->findOneBy(array('num' => $num));
					
					if ($curs == null) {
						$curs = new EntityCurs(null, $titol, $datadesde, $datafins, $club, $clubhistoric);			
						$curs->setNum($num);
						$curs->setValidat(true);
						$curs->setFinalitzat(true);
						
						$em->persist($curs);
						
						$cursosNous++;
					}
					$cursos[$curs->getNumActa()] = $curs;  // Afegir als cursos consultats
					
				} else {
					$curs = $cursos[ $num ];
				}

				// Crear titulacions 
				$titulacio = new EntityTitulacio($persona, $curs);
				$titulacio->setNum($currentTitol['numtitulo']);
				$titulacio->setDatasuperacio($datafins);
				
				$em->persist($titulacio); 
					
				$titulacions++;
			
			} catch (\Exception $e) {
				//$em->getConnection()->rollback();
				//echo "Problemes durant la transacció : ". $e->getMessage();
				
				$errors[ $id ] = $e->getMessage();
			}
			
		}
	
		if (count($ids) != 0) {	
			$sql = "UPDATE importtitulacions SET error = null WHERE id IN (".implode(",", $ids).") ";
			$stmt = $em->getConnection()->prepare($sql);
			$stmt->execute();
			
			if (count($errors) != 0) {
				foreach ($errors as $id => $error) {
						
					$sql = "UPDATE importtitulacions SET error = \"".$error."\" WHERE id = ".$id;
					$stmt = $em->getConnection()->prepare($sql);
					$stmt->execute();
				}
				$em->flush();	
				return new Response("KO <br/>".implode("<br/>",$errors));
			}
		}
		$em->flush();
			
		return new Response("OK cursos nous ".$cursosNous." i titulacions ".$titulacions );
	}
	
	
	public function historicsaldosAction(Request $request) {
		// http://www.fecdas.dev/historicsaldos?desde=2016-01-01&fins=2016-12-31&club=CAT020   => Inici i fi inclosos
		
		// http://www.fecdas.dev/historicsaldos?desde=2016-01-01&club=CAT020					=> Inici i avui inclosos 
			
		
		// http://www.fecdas.dev/historicsaldos?desde=2016-01-01&fins=2016-02-29
		// http://www.fecdas.dev/historicsaldos?desde=2016-03-01&fins=2016-05-31
		// http://www.fecdas.dev/historicsaldos?desde=2016-06-01&fins=2016-08-31
		// http://www.fecdas.dev/historicsaldos?desde=2016-09-01&fins=2016-11-30
		// http://www.fecdas.dev/historicsaldos?desde=2016-12-01&fins=2017-01-31
		// http://www.fecdas.dev/historicsaldos?desde=2017-02-01
			
		// Script de migració. Executar per refer històric de saldos a partir del registre d'una data
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$result = '';
	
		$totalFactures = 0;
		$totalRebuts = 0;
	
		$em = $this->getDoctrine()->getManager();

		// Interval per gestionar
		$strDesde = $request->query->get('desde', '2016-01-01');  
		$desde = \DateTime::createFromFormat('Y-m-d H:i:s', $strDesde . " 00:00:00");
		
		$strFins = $request->query->get('fins', '');  
		if ($strFins == '') $final = $this->getCurrentDate('now');
		else $final = \DateTime::createFromFormat('Y-m-d H:i:s', $strFins . " 23:59:59");

		// Clubs per tractar
		$codiclub = $request->query->get('club', '');  // opcionalment per un club
		
		$strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
		//$strQuery .= " WHERE (c.databaixa IS NULL OR c.databaixa > :current) AND c.activat = 1 ";
		$strQuery .= " WHERE (c.databaixa IS NULL OR c.databaixa > :current) ";
		$strQuery .= " AND c.codi <> :test ";
		if ($codiclub != '') $strQuery .= " AND c.codi = :club ";
		$strQuery .= " ORDER BY c.codi";
		$query = $em->createQuery($strQuery);
		$query->setParameter('current', $desde->format('Y-m-d H:i:s') );
		$query->setParameter('test', BaseController::CODI_CLUBTEST );
		if ($codiclub != '') $query->setParameter('club', $codiclub );
		
		$clubs = $query->getResult();
		
		
		// Saldos del dia anterior de partida
		$anterior = \DateTime::createFromFormat('Y-m-d H:i:s', $desde->format('Y-m-d') . " 00:00:00");
		$anterior->sub(new \DateInterval('P1D')); // Sub 1 Day
		$posterior = \DateTime::createFromFormat('Y-m-d H:i:s', $desde->format('Y-m-d') . " 00:00:00");
		$posterior->add(new \DateInterval('P1D')); // Add 1 Day
		
		$strQuery  = " SELECT s FROM FecdasBundle\Entity\EntitySaldos s ";
		$strQuery .= " WHERE s.dataregistre = :anterior ";
		if ($codiclub != '') $strQuery .= " AND s.club = :club ";
		$strQuery .= " ORDER BY s.club";
		$query = $em->createQuery($strQuery);
		$query->setParameter('anterior', $anterior->format('Y-m-d') );
		if ($codiclub != '') $query->setParameter('club', $codiclub );
		$saldosInici = $query->getResult();
		
		// Consultar últim registre anterior i inicialitzar array
		$clubsArray = array();
		// Preparar dades inicials
		foreach ($clubs as $club) {
					
			$k = $this->getSaldoClubIndex($saldosInici, $club);
			
			if ($k != -1) {
				// trobat
				$saldo = $saldosInici[$k];
				unset($saldosInici[$k]);
				
			} else {
				$saldo = new EntitySaldos($club, $anterior);
				$saldo->setDataentrada($desde);
				$em->persist($saldo);
			}
			
			$clubsArray[ $club->getCodi() ][ $anterior->format('Y-m-d') ] = $saldo;
			
			
		}

		$current = \DateTime::createFromFormat('Y-m-d H:i:s', $desde->format('Y-m-d') . " 00:00:00");
		while($current->format('Y-m-d H:i:s') < $final->format('Y-m-d H:i:s')) {
			
			// Registrar nova entrada taula saldos si no existeix
			foreach ($clubs as $club) {
				
				if (!isset($clubsArray[ $club->getCodi() ][ $anterior->format('Y-m-d') ])) {
						
					// Error. No hi ha dades del dia anterior per al club 		
					$result .= " ERROR *********** SENSE DADES DEL DIA ANTERIOR ".$anterior->format('Y-m-d')." PER AL CLUB ".$club->getCodi()." - ".$club->getNom()." ***************<br/>";
				
				} else {
					$saldoahir = $clubsArray[ $club->getCodi() ][ $anterior->format('Y-m-d') ];

					$saldo = $this->getSaldoClubData($clubsArray, $club, $current);
					
					// inicialitzar amb les dades del dia anterior
					$saldo->setExercici( $saldoahir->getExercici() );
					$saldo->setRomanent( $saldoahir->getRomanent() );
					$saldo->setTotalllicencies( $saldoahir->getTotalllicencies() );
					$saldo->setTotalduplicats( $saldoahir->getTotalduplicats() );
					$saldo->setTotalaltres( $saldoahir->getTotalaltres() );
					$saldo->setTotalpagaments( $saldoahir->getTotalpagaments() ); 
					$saldo->setAjustsubvencions( $saldoahir->getAjustsubvencions() );
					
					$clubsArray[ $club->getCodi() ][ $current->format('Y-m-d') ] = $saldo;
				}
			}	


			// Tractament especial per al dia 01/01/2016. Acumula entrades i sortides moviments entrats al 2015 però amb data 2016
			if ($current->format('Y-m-d') == '2016-01-01') { // Només una vegada a la càrrega inicial des de 01/01/2016
				$result .= $this->acumular2016entrats2015($clubsArray, $codiclub);
			} 

			// Consultar factures i rebuts del dia			
			$desdeCurrent = \DateTime::createFromFormat('Y-m-d H:i:s', $current->format('Y-m-d').' 00:00:00'); 
			$finsCurrent = \DateTime::createFromFormat('Y-m-d H:i:s', $current->format('Y-m-d').' 23:59:59'); 
			
			// Revisar factures del dia i actualitzar entrada/sortida segons datafactura
			$facturesTmp = $this->facturesEntre($desdeCurrent, $finsCurrent);
			
			$factures = $this->getFacturesClub($facturesTmp, $codiclub);
			
			$totalFactures += count($factures);

			foreach ($factures as $factura) {
				$comanda = $factura->esAnulacio()?$factura->getComandaAnulacio():$factura->getComanda();
				$club = $comanda->getClub();
				$import = $factura->getImport();
				$data = $factura->getDatafactura();
				
				$saldo = $clubsArray[ $club->getCodi() ][ $current->format('Y-m-d') ]; // Ha d'existir
				
				// Simular canvi imports per entrada de factura 
				if ($data->format('Y') <= 2015 && $data->format('Y') < $current->format('Y')) {
						// Només per a factures posteriors al recull dels saldos per inicialitzar 2016-04-01 => SELECT * FROM `m_factures` WHERE dataentrada >= '2016-04-01 00:00:00' AND YEAR(datafactura) < 2016; // 0  
					if ($factura->getDataentrada()->format('Y-m-d H:i:s') > '2016-04-01 00:00:00') {
						$result .= " WARNING *********** FACTURA FACTURADA ANY ANTERIOR després del 2016-04-01 ".$factura->getId()." ".$factura->getImport()." ".$factura->getDatafactura()->format('Y-m-d')." PER AL CLUB ".$club->getCodi()." - ".$club->getNom()." ***************<br/>";
						
						$saldo->setRomanent($saldo->getRomanent() - $import);  // Romanent
							
						$clubsArray[ $club->getCodi() ][ $current->format('Y-m-d') ] = $saldo;
					}
				} else {
					if ($comanda->esParte()) $saldo->setTotalllicencies( $saldo->getTotalllicencies() + $import);
					if ($comanda->esDuplicat()) $saldo->setTotalduplicats( $saldo->getTotalduplicats() + $import);
					if ($comanda->esAltre()) $saldo->setTotalaltres( $saldo->getTotalaltres() + $import);
					
					// acumular sortides al dia indicat a datafactura
					$saldo = $this->getSaldoClubData($clubsArray, $club, $data);

					$saldo->setSortides($saldo->getSortides() + $import);
					$clubsArray[ $club->getCodi() ][ $data->format('Y-m-d') ] = $saldo;
				}	
			}
		
			// Revisar rebuts dia  i actualitzar entrada/sortida segons datapagament
			$rebuts   = $this->rebutsEntre($desdeCurrent, $finsCurrent, $codiclub);
			$totalRebuts += count($rebuts);
					
			foreach ($rebuts as $rebut) {
				$club = $rebut->getClub();
				$import = $rebut->getImport();
				$data = $rebut->getDatapagament();
				
				$saldo = $clubsArray[ $club->getCodi() ][ $current->format('Y-m-d') ]; // Ha d'existir
				
				// Simular canvi imports per entrada de rebut 
				if ($data->format('Y') <= 2015 && $data->format('Y') < $current->format('Y')) {
						// Només per a rebuts posteriors al recull dels saldos per inicialitzar 2016-04-01 => SELECT * FROM `m_rebuts` WHERE dataentrada >= '2016-04-01 00:00:00' AND YEAR(datapagament) < 2016; // 0  
					if ($rebut->getDataentrada()->format('Y-m-d H:i:s') > '2016-04-01 00:00:00') {
						$result .= " WARNING *********** REBUT PAGAT ANY ANTERIOR després del 2016-04-01 ".$rebut->getId()." ".$rebut->getImport()." ".$rebut->getDatapagament()->format('Y-m-d')." PER AL CLUB ".$club->getCodi()." - ".$club->getNom()." ***************<br/>";	
							
						$saldo->setRomanent($saldo->getRomanent() + $import);  // Romanent
						
						$clubsArray[ $club->getCodi() ][ $current->format('Y-m-d') ] = $saldo;
					}
				} else {
					$saldo->setTotalpagaments( $saldo->getTotalpagaments() + $import);
					
					// acumular sortides al dia indicat a datafactura
					$saldo = $this->getSaldoClubData($clubsArray, $club, $data);

					$saldo->setEntrades($saldo->getEntrades() + $import);
					$clubsArray[ $club->getCodi() ][ $data->format('Y-m-d') ] = $saldo;
				}	
			}
 		
			$anterior = \DateTime::createFromFormat('Y-m-d H:i:s', $current->format('Y-m-d') . " 00:00:00");
			
			$current =  \DateTime::createFromFormat('Y-m-d H:i:s', $current->format('Y-m-d') . " 00:00:00");  //=> Nova instància perquè sino manté la referència 
			$current->add(new \DateInterval('P1D')); // Add 1 Day 
			
			$posterior =  \DateTime::createFromFormat('Y-m-d H:i:s', $posterior->format('Y-m-d') . " 00:00:00");  //=> Nova instància perquè sino manté la referència 
			$posterior->add(new \DateInterval('P1D')); // Add 1 Day
 
		}
		
		$em->flush();
		
		$result .= "Desde ".$desde->format('Y-m-d H:i:s')."<br/>";
		$result .= "Total factures ".$totalFactures."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_factures WHERE dataentrada >= '".$desde->format('Y-m-d H:i:s')."' AND dataentrada < '".$current->format('Y-m-d H:i:s')."';<br/>";
		$result .= "Total rebuts ".$totalRebuts."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_rebuts WHERE dataentrada >= '".$desde->format('Y-m-d H:i:s')."' AND dataentrada < '".$current->format('Y-m-d H:i:s')."';<br/>";
		
		return new Response($result);
	}
	
	/* Saldos ordenats per codi club */
	private function getSaldoClubIndex($saldos, $club) {
				
		$codi = $club->getCodi();	
		foreach ($saldos as $k => $saldo) {
			
			$currentCodi = $saldo->getClub()->getCodi();
			 
			if ($currentCodi == $codi) return $k;
			
			if ($currentCodi > $codi) return -1; 	
		}
		
		return -1;
	}
	
	/* Obté el saldo d'un club en una data concreta o el crea si no existeix */
	private function getSaldoClubData($clubsArray, $club, $data) {
		$em = $this->getDoctrine()->getManager();	
		
		if (isset($clubsArray[ $club->getCodi() ][ $data->format('Y-m-d') ])) return $clubsArray[ $club->getCodi() ][ $data->format('Y-m-d') ];

		$posterior = \DateTime::createFromFormat('Y-m-d H:i:s', $data->format('Y-m-d').' 00:05:00');
		$posterior->add(new \DateInterval('P1D')); // Add 1 Day

		// Encara no existeix el registre, crear 
		$saldo = new EntitySaldos($club, $data);
		$saldo->setDataentrada($posterior);
		$em->persist($saldo);
				
		return $saldo; 
	}
	
	/* Filtra factures per club  */
	private function getFacturesClub($factures, $codiclub) {
		
		if ($codiclub == '') return $factures;
		
		// Filtrar factures club
		$facturesClub = array();
		foreach ($factures as $factura) {
			$comanda = $factura->esAnulacio()?$factura->getComandaAnulacio():$factura->getComanda();
			$club = $comanda->getClub();
			if ($club->getCodi() == $codiclub) $facturesClub[] = $factura;
		}
		return $facturesClub;
	}
	
	// Consulta moviments entrats amb dataentrada anterior 2016 però amb data moviment 2016 i acumular a les entrades i sortides d'inici d'any
	// Opcionalment per un únic club
	private function acumular2016entrats2015(&$clubsArray, $codiclub = '') {
		$desdeTime = '2016-01-01 00:00:00';
		$desdeDate = \DateTime::createFromFormat('Y-m-d', '2016-01-01');
		$result = "";
			
		$em = $this->getDoctrine()->getManager();
		
		$strQuery  = " SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
		$strQuery .= " WHERE f.dataentrada < :desde ";
		$strQuery .= " AND   f.datafactura >= :desde ";
				
		$query = $em->createQuery($strQuery);
		$query->setParameter('desde', $desdeTime );
			
		$facturesTmp = $query->getResult();

		$facturesAcumularInici = $this->getFacturesClub($facturesTmp, $codiclub);
		
		if ($codiclub == '') {
			$result .= "Total de factures per acumular a l'inici ".count($facturesAcumularInici)."<br/>";
			$result .= "        check: SELECT COUNT(*) FROM m_factures WHERE dataentrada < '".$desdeTime."' AND datafactura >= '".$desdeTime."'; <br/>";
		} else {
			$result .= "Total de factures club ".$codiclub." per acumular a l'inici ".count($facturesAcumularInici)."<br/>";
			$result .= "        check: SELECT COUNT(f.id) FROM m_factures f INNER JOIN m_comandes c ON c.factura = f.id WHERE c.club = '".$codiclub."' AND f.dataentrada < '".$desdeTime."' AND datafactura >= '".$desdeTime."'; <br/>";
			$result .= "               SELECT COUNT(f.id) FROM m_factures f INNER JOIN m_comandes c ON f.comandaanulacio = c.id WHERE c.club = '".$codiclub."' AND f.dataentrada < '".$desdeTime."' AND datafactura >= '".$desdeTime."'; <br/>";
		}		
			
		foreach ($facturesAcumularInici as $facturaAcumularInici) {
			
			$comanda = $facturaAcumularInici->esAnulacio()?$facturaAcumularInici->getComandaAnulacio():$facturaAcumularInici->getComanda();
			$club = $comanda->getClub();
			$import = $facturaAcumularInici->getImport();
			$data = $facturaAcumularInici->getDatafactura();

			$saldo = $this->getSaldoClubData($clubsArray, $club, $desdeDate); // Acumular a dia 1 de gener
			
			if ($comanda->esParte()) $saldo->setTotalllicencies( $saldo->getTotalllicencies() + $import);
			if ($comanda->esDuplicat()) $saldo->setTotalduplicats( $saldo->getTotalduplicats() + $import);
			if ($comanda->esAltre()) $saldo->setTotalaltres( $saldo->getTotalaltres() + $import);
			
			$clubsArray[ $club->getCodi() ][ $desdeDate->format('Y-m-d') ] = $saldo;
						
			$saldo = $this->getSaldoClubData($clubsArray, $club, $data);

			$saldo->setSortides($saldo->getSortides() + $import);
			
			$clubsArray[ $club->getCodi() ][ $data->format('Y-m-d') ] = $saldo;
		}	
		
		//SELECT COUNT(*) FROM m_rebuts WHERE dataentrada < '2016-01-01 00:00:00' AND datapagament >= '2016-01-01 00:00:00'; ==> 0
		/*	
		$strQuery  = " SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.dataentrada < :desde ";
		$strQuery .= " AND   r.datapagament >= :desde ";
		if ($codiclub != '') $strQuery .= " AND r.club = :club ";
				
		$query = $em->createQuery($strQuery);
		$query->setParameter('desde', $desdeTime );
		if ($codiclub != '') $query->setParameter('club', $codiclub );
			
		$rebutsAcumularInici = $query->getResult();			
			
		$result .= "Total de rebuts per acumular a l'inici ".count($rebutsAcumularInici)."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_rebuts WHERE dataentrada < '".$desdeTime."' AND datapagament >= '".$desdeTime."'; <br/>";
		
		foreach ($rebutsAcumularInici as $rebutAcumularInici) {
			$club = $rebutAcumularInici->getClub();
			$import = $rebutAcumularInici->getImport();
			$data = $rebutAcumularInici->getDatapagament();
				
			$saldo = $this->getSaldoClubData($clubsArray, $club, $data);
				
			// Acumular entrades
			$saldo->setEntrades($saldo->getEntrades() + $import);
			$clubsArray[ $club->getCodi() ][ $data->format('Y-m-d') ] = $saldo;
		}	
		*/
		
		return $result;
	}
	
	/*public function historicsaldosAction(Request $request) {
		// http://www.fecdas.dev/historicsaldos?desde=2015-12-31&fins=2016-06-02&club=CAT127
		// http://www.fecdas.dev/historicsaldos?desde=2016-06-01&fins=2016-11-02&club=CAT127
		// http://www.fecdas.dev/historicsaldos?desde=2016-11-01&fins=2017-03-04&club=CAT127
		// http://www.fecdas.dev/historicsaldos?desde=2015-12-31&fins=2016-03-02
		// http://www.fecdas.dev/historicsaldos?desde=2016-03-01&fins=2016-05-02
		// http://www.fecdas.dev/historicsaldos?desde=2016-05-01&fins=2016-07-02
		// http://www.fecdas.dev/historicsaldos?desde=2016-07-01&fins=2016-09-02
		// http://www.fecdas.dev/historicsaldos?desde=2016-09-01&fins=2016-11-02
		// http://www.fecdas.dev/historicsaldos?desde=2016-11-01&fins=2017-01-02
		// http://www.fecdas.dev/historicsaldos?desde=2017-01-01
		// Script de migració. Executar per refer històric de saldos a partir del registre d'una data
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$result = '';
	
		$em = $this->getDoctrine()->getManager();

		$strDesde = $request->query->get('desde', '2015-12-31');  // Hauria d'existir registre saldo en aquesta data
		$desde = \DateTime::createFromFormat('Y-m-d H:i:s', $strDesde . " 00:00:00");
		
		
		$strFins = $request->query->get('fins', '');  
		
		if ($strFins == '') $final = $this->getCurrentDate('now');
		else $final = \DateTime::createFromFormat('Y-m-d H:i:s', $strFins . " 23:59:59");
		
		
		$current = clone $desde;
		$current->add(new \DateInterval('P1D')); // Add 1 Day
		$inicirevisio = clone $current;
		
		$codiclub = $request->query->get('club', '');  // opcionalment per un club

		$strQuery  = " SELECT * FROM m_clubs c LEFT JOIN m_saldos s ON c.codi = s.club  ";
		$strQuery .= " WHERE (c.databaixa IS NULL OR c.databaixa > '".$desde->format('Y-m-d H:i:s')."') ";
		if ($codiclub != '') $strQuery .= " AND c.codi = '".$codiclub."' ";
		$strQuery .= " AND (s.dataentrada IS NULL OR ";
		$strQuery .= " 	   (s.dataentrada >= '".$desde->format('Y-m-d H:i:s')."' ";
		$strQuery .= "      AND s.dataentrada < '".$current->format('Y-m-d H:i:s')."')) ";
		$strQuery .= " ORDER BY c.codi ASC, s.dataentrada DESC";
		
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$clubssaldos = $stmt->fetchAll();

		$i = 0;
		$clubs = array();
		// Preparar dades inicials
		while (isset($clubssaldos[$i])) {
			$clubs[ $clubssaldos[$i]['codi'] ] = array(
				'nom' => $clubssaldos[$i]['nom'],
				'club' => null,
				'databaixa' => $clubssaldos[$i]['databaixa'],
				'entrades' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['entrades']:0,
				'sortides' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['sortides']:0,
				'romanent' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['romanent']:0,
				'totalpagaments' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['totalpagaments']:0,
				'totalllicencies' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['totalllicencies']:0,
				'totalduplicats' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['totalduplicats']:0,
				'totalaltres' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['totalaltres']:0,
				'ajustsubvencions' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['ajustsubvencions']:0,
				'dataentrada' => $clubssaldos[$i]['id'] != null?$clubssaldos[$i]['dataentrada']:'',
			); 
			
			$i++;
		}

		
		// Comernçar pel dia 
		$seguent = clone $current;
		$seguent->add(new \DateInterval('P1D')); // Add 1 Day
		
		$totalFactures = 0;
		$totalFacturesAnteriors = 0;
		$totalRebuts = 0;
		$totalRebutsAnteriors = 0;
		
		while($seguent->format('Y-m-d H:i:s') < $final->format('Y-m-d H:i:s')) {
			
			// Preparar clubs nou dia
			foreach ($clubs as $codi => $club) {
				
				$clubs[$codi]['dataentrada'] = clone $seguent;
				$clubs[$codi]['dataentrada']->sub(new \DateInterval('PT1S')); // Sub 1 segon;
				$clubs[$codi]['sortides'] = 0;
				$clubs[$codi]['entrades'] = 0;
				//echo $codi." => ".($club['dataentrada']==null?"null":$club['dataentrada']->format('Y-m-d H:i:s'))."<br/>";
			}
			
			// Consultar factures entrades entrats dia current
			$factures = $this->facturesEntre($current, $seguent);

			$totalFactures += count($factures);

			// Acumular factures del 2105 amb data factura 2016 
			$facturesAnteriors = $this->facturesAnteriorsEntre($current, $seguent);
			
			$totalFacturesAnteriors += count($facturesAnteriors);
			
			$factures = array_merge($factures, $facturesAnteriors);

			foreach ($factures as $factura) {
				$comanda = $factura->esAnulacio()?$factura->getComandaAnulacio():$factura->getComanda();
				$club = $comanda->getClub();

				if (isset($clubs[$club->getCodi()])) {
					
					if ($clubs[$club->getCodi()]['club'] == null)  $clubs[$club->getCodi()]['club'] = $club;
					
					$import = $factura->getImport();

					$clubs[$club->getCodi()]['sortides'] += $import;
					
					if ($factura->getDatafactura()->format('Y') < $current->format('Y')) {
						// Només per a factures posteriors al recull dels saldos per inicialitzar 2016-04-01 => SELECT * FROM `m_factures` WHERE dataentrada >= '2016-04-01 00:00:00' AND YEAR(datafactura) < 2016; // 0  
						
						if ($factura->getDataentrada()->format('Y-m-d H:i:s') > '2016-04-01 00:00:00') $clubs[$club->getCodi()]['romanent'] -= $import;  // Romanent
					} else {
						if ($comanda->esParte()) $clubs[$club->getCodi()]['totalllicencies'] += $import;
						if ($comanda->esDuplicat()) $clubs[$club->getCodi()]['totalduplicats'] += $import;
						if ($comanda->esAltre()) $clubs[$club->getCodi()]['totalaltres'] += $import;
					}			
				} else {
					if ($codiclub == '') $result .= "F".$factura->getId()." *********** CLUB NO EXISTEIX AL REGISTRE DE SALDOS ".$club->getCodi()." - ".$club->getNom()." ***************<br/>";
				}
			}
			
			// Consultar rebuts entrats dia current
			$rebuts   = $this->rebutsEntre($current, $seguent);
			
			$totalRebuts += count($rebuts);
			
			// Acumular rebuts del 2105 amb data factura 2016 
			$rebutsAnteriors = $this->rebutsAnteriorsEntre($current, $seguent);
			
			$totalRebutsAnteriors += count($rebutsAnteriors);
			
			$rebuts = array_merge($rebuts, $rebutsAnteriors);
			
			foreach ($rebuts as $rebut) {
				
				$club = $rebut->getClub();
				
				if (isset($clubs[$club->getCodi()])) {
					
					if ($clubs[$club->getCodi()]['club'] == null)  $clubs[$club->getCodi()]['club'] = $club;
					
					$import = $rebut->getImport();
					
					$clubs[$club->getCodi()]['entrades'] += $import;
					
					if ($rebut->getDatapagament()->format('Y') < $current->format('Y')) {
						// Només per a rebuts posteriors al recull dels saldos per inicialitzar 2016-04-01 => SELECT * FROM `m_rebuts` WHERE dataentrada >= '2016-04-01 00:00:00' AND YEAR(datapagament) < 2016; // 0
						if ($rebut->getDataentrada()->format('Y-m-d H:i:s') > '2016-04-01 00:00:00') $clubs[$club->getCodi()]['romanent'] += $import;  // Romanent
					} else {
						$clubs[$club->getCodi()]['totalpagaments'] += $import;
					}
					
				} else {
					if ($codiclub == '') $result .= "R".$rebut->getId()." *********** CLUB NO EXISTEIX AL REGISTRE DE SALDOS ".$club->getCodi()." - ".$club->getNom()." ***************<br/>";
				}
			}
			
			// Afegir registre dia current
			foreach ($clubs as $codi => $club) {
				
				if ($clubs[$codi]['club'] == null)  $clubs[$codi]['club'] = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);;

				//echo "fin ". $codi." => ".($club['dataentrada']==null || $club['dataentrada'] == ''?"null":$club['dataentrada']->format('Y-m-d H:i:s'))."<br/>";
			
				$thisclub = $clubs[$codi]['club'];
				// Revisar si registre anterior a data d'alta i amb tots els imports a 0 => no registrar
				if ($club['dataentrada']->format('Y-m-d') >= $thisclub->getDataalta()->format('Y-m-d') ||
					$club['entrades'] != 0 ||
					$club['sortides'] != 0 ||
					$club['romanent'] != 0 ||
					$club['totalllicencies'] != 0 ||
					$club['totalduplicats'] != 0 ||
					$club['totalaltres'] != 0 ||
					$club['totalpagaments'] != 0) {
			
					$saldos = new EntitySaldos($clubs[$codi]['club'], $club['entrades'], $club['sortides']);
					$saldos->setRomanent( $club['romanent'] );
					$saldos->setTotalllicencies( $club['totalllicencies'] );
					$saldos->setTotalduplicats( $club['totalduplicats'] );
					$saldos->setTotalaltres( $club['totalaltres'] );
					$saldos->setTotalpagaments( $club['totalpagaments'] ); 
					
					$saldos->setDataentrada( $club['dataentrada'] ); 
				
					//echo ($saldos->getDataentrada()==null?"null":$saldos->getDataentrada()->format('Y-m-d H:i:s'));
					//echo ('data => '.$saldos->getDataentrada());
				
					$em->persist($saldos);
				}
			}
			$em->flush();
			
			// Següent dia
			$current = clone $seguent;
			$seguent->add(new \DateInterval('P1D')); // Add 1 Day
		}
		
		$result .= "Desde ".$desde->format('Y-m-d H:i:s')."<br/>";
		$result .= "Total factures ".$totalFactures."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_factures WHERE dataentrada >= '".$inicirevisio->format('Y-m-d H:i:s')."' AND dataentrada < '".$current->format('Y-m-d H:i:s')."';<br/>";
		$result .= "Total factures anteriors ".$totalFacturesAnteriors."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_factures WHERE YEAR(dataentrada) < YEAR(datafactura) AND datafactura >= '".$inicirevisio->format('Y-m-d H:i:s')."' AND datafactura < '".$current->format('Y-m-d H:i:s')."';<br/>";
		$result .= "Total rebuts ".$totalRebuts."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_rebuts WHERE dataentrada >= '".$inicirevisio->format('Y-m-d H:i:s')."' AND dataentrada < '".$current->format('Y-m-d H:i:s')."';<br/>";
		$result .= "Total rebuts anteriors ".$totalRebutsAnteriors."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_rebuts WHERE YEAR(dataentrada) < YEAR(datapagament) AND datapagament >= '".$inicirevisio->format('Y-m-d H:i:s')."' AND datapagament < '".$current->format('Y-m-d H:i:s')."';<br/>";
		
		
		return new Response($result);
	}*/
	
	protected function facturesAnteriorsEntre($desde, $fins) {
		$em = $this->getDoctrine()->getManager();
		
		// Consultar factures entrades entrats dia current
		$strQuery  = " SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
		$strQuery .= " WHERE f.datafactura >= :desde ";
		$strQuery .= " AND   f.datafactura <  :fins ";
		$strQuery .= " AND   f.dataentrada <  '2016-01-01 00:00:00' ";
			
		$query = $em->createQuery($strQuery);
		$query->setParameter('desde', $desde->format('Y-m-d H:i:s') );
		$query->setParameter('fins',  $fins->format('Y-m-d H:i:s') );
		
		$factures = $query->getResult();
		
		return $factures;
	}
	
	protected function rebutsAnteriorsEntre($desde, $fins) {
		$em = $this->getDoctrine()->getManager();
		
		// Consultar factures entrades entrats dia current
		$strQuery  = " SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.datapagament >= :desde ";
		$strQuery .= " AND   r.datapagament <  :fins ";
		$strQuery .= " AND   r.dataentrada <  '2016-01-01 00:00:00' ";
			
		$query = $em->createQuery($strQuery);
		$query->setParameter('desde', $desde->format('Y-m-d H:i:s') );
		$query->setParameter('fins',  $fins->format('Y-m-d H:i:s') );
		
		$rebuts = $query->getResult();
		
		return $rebuts;
	}
	
	
	public function updatejuntaAction(Request $request) {
		// http://www.fecdas.dev/updatejunta?persist=0  o 1 
		// Script per afegir el nom als membres de la junta
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
		
		$persist = $request->query->get('persist', 0) == 1?true:false;
		
		
		try {
			
			$clubs = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->findAll();
			
			foreach ($clubs as $club) {
				echo " *********** CLUB ".$club->getNom()." ***************<br/>";
				
				$jsonCarrecs = ($club->getCarrecs() != ''?json_decode($club->getCarrecs()):array());
				
				$key = -1; 
				$ncUpd = 0;
				
				$jsonCarrecsUpd = array();
				foreach ($jsonCarrecs as $k => $value) {
					
					$federat = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($value->id);
					
					if ($federat != null) {
						$jsonCarrecsUpd[] = array('id' 	=>	$value->id, 
													'cid'	=>	$value->cid, 
													'nc' 	=> $value->nc, 
													'nom' 	=> $federat->getNomCognoms());		// upd
													
						echo "upd ok  ".$federat->getNomCognoms().'<br/>';
					} else {
						
						echo "error federat no trobat ".$value->id.'<br/>';
						
					}
				}
				$club->setCarrecs(json_encode($jsonCarrecsUpd));
				
				if ($persist == true) $em->flush();
			}
			$response = new Response("********************* FINAL JUNTES UPD *****************");
			
		} catch (\Exception $e) {
			$response = new Response($e->getMessage()."<br/>********************* FINAL JUNTES UPD *****************");
		}
		return $response;
	
	}	
	
	/********************************************************************************************************************/
	/************************************ INICI SCRIPTS CARREGA *********************************************************/
	/********************************************************************************************************************/
	
	public function resetcomandesAction(Request $request) {
		// http://www.fecdas.dev/resetcomandes?id=106313&detall=149012&factura=8380
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->query->get('id', 106313);	// id històric
		$detall = $request->query->get('detall', 149012);  // detall històric
		$factura = $request->query->get('factura', 8380);  // factures històric
		
		/*$comandes = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda')->findAll();
	
		foreach ($comandes as $c) {
			foreach ($c->getDetalls() as $d) $em->remove($d);
			$em->remove($c);
		}
		$em->flush();
		*/
		
	
		$sql = "DELETE FROM m_comandadetalls WHERE id >= ".$detall;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "UPDATE m_comandes SET factura = NULL, rebut = NULL WHERE id >= ".$id;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_factures WHERE id >= ".$factura;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_comandes WHERE id >= ".$id;
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		$sql = "DELETE FROM m_rebuts WHERE id > 0";
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
		return new Response("");
	}
	
	public function migrahistoricAction(Request $request) {
		// http://www.fecdas.dev/migrahistoric?desde=20XX&fins=2014
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$yeardesde = $request->query->get('desde', 985);
		$yearfins = $request->query->get('fins', 2015);
		$year = $yeardesde;
		
		$batchSize = 20;
		
		$strQuery = "SELECT * FROM m_clubs c ORDER BY c.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$clubs = array();
		while (isset($aux[$i])) {
			$clubs[ $aux[$i]['compte'] ] = $aux[$i];
			$i++;
		}
		
		
		$strQuery = "SELECT p.id, p.importparte, p.dataalta, p.dataentradadel, p.databaixadel,";
		$strQuery .= " p.clubdel, t.descripcio as tdesc, c.categoria as ccat,";
		$strQuery .= " c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament,";
		$strQuery .= " p.dadespagament, p.importpagament,	p.comentari, p.datafacturacio, p.numfactura, ";
		$strQuery .= " COUNT(l.id) as total FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte ";
		$strQuery .= " INNER JOIN m_categories c ON c.id = l.categoria ";
		$strQuery .= " INNER JOIN m_tipusparte t ON c.tipusparte = t.id ";
		$strQuery .= " WHERE p.dataalta < '".($yearfins+1)."-01-01 00:00:00' ";
		$strQuery .= " AND p.dataalta >= '".$yeardesde."-01-01 00:00:00' ";
		$strQuery .= " GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ";
		$strQuery .= " ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, ";
		$strQuery .= " p.comentari, p.datafacturacio, p.numfactura";
		//$strQuery .= " ORDER BY p.id, csim ";
		$strQuery .= " ORDER BY p.dataentradadel, p.id ";

		/*
		 SELECT p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, t.descripcio as tdesc, c.categoria as ccat, 
		 c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, p.comentari, 
		 p.datafacturacio, p.numfactura, COUNT(l.id) as total 
		 FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte INNER JOIN m_categories c ON c.id = l.categoria 
		 INNER JOIN m_tipusparte t ON c.tipusparte = t.id WHERE p.dataalta < '2015-01-01 00:00:00' 
		 AND p.dataalta >= '2013-01-01 00:00:00' GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, 
		 p.clubdel, tdesc, ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, p.comentari, 
		 p.datafacturacio, p.numfactura ORDER BY p.dataentradadel, p.id 
		 */ 
		
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		//$partesAbans2015 = $stmt->fetchAll();
		
		//echo "Total partes: " . count($partesAbans2015) . PHP_EOL;
			
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
		
		// Tractar duplicats primer
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($year, BaseController::COMANDES) + 1 
		);
		
		$ipar = 0;
		
		$parteid = 0;
		$partes = array();
		
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		
		try {
			/***********************************************************************************/
			/****************************   PARTES i DUPLICATS  ********************************/
			/***********************************************************************************/
				
			while ($parte = $stmt->fetch()) {
				
				 if (substr($parte['dataentradadel'], 0, 4) > $year) {
					 $year = substr($parte['dataentradadel'], 0, 4);
					 $maxnums['maxnumcomanda'] = $this->getMaxNumEntity($year, BaseController::COMANDES) + 1;
					 
					 echo '***************************************************************************'.'<br/>';
					 echo '===============================> ANY '.$year.'  <=========================='.'<br/>';
					 echo '***************************************************************************'.'<br/>';
				 }
			
				 if ($parteid == 0) $parteid = $parte['id'];
						
				 if ($parteid != $parte['id']) {
				 	// Agrupar partes, poden venir vàries línies seguides segons categoria 'A', 'T' ...
				 	echo "insert comanda parte => ". $parteid;
				 	
				 	$this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				 	
				 	$parteid = $parte['id'];
				 	$partes = array();
				 }
				 
				 $partes[] = $parte;
				 $ipar++;

				 $em->clear();
			}
			// El darrer parte del dia
			if ($parteid > 0) $this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
			
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
		
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
			
		return new Response("");
	}

	public function migracomandesAction(Request $request) {
		// http://www.fecdas.dev/migracomandes?comanda=xx&duplicat=ss
		// http://www.fecdas.dev/migracomandes?id=xx&&current=2014
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$mincomanda = $request->query->get('comanda', 0); // min comanda
		$minduplicat = $request->query->get('duplicat', 0); // min duplicat
		$idcomanda = $request->query->get('id', 0); // id única comanda
		$current = $request->query->get('current', date('Y')); // any
	
		$batchSize = 20;
	
		$strQuery = "SELECT p.id, p.importparte, p.dataalta, p.dataentradadel, p.databaixadel,";
		$strQuery .= " p.clubdel, t.descripcio as tdesc, c.categoria as ccat,";
		$strQuery .= " c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament,";
		$strQuery .= " p.dadespagament, p.importpagament,	p.comentari, p.datafacturacio, p.numfactura, ";
		$strQuery .= " e.preu, e.iva, ";
		$strQuery .= " COUNT(l.id) as total FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte ";
		$strQuery .= " INNER JOIN m_categories c ON c.id = l.categoria ";
		$strQuery .= " INNER JOIN m_tipusparte t ON c.tipusparte = t.id ";
		$strQuery .= " INNER JOIN m_productes o ON c.producte = o.id ";
		$strQuery .= " INNER JOIN m_preus e ON e.producte = o.id ";
		$strQuery .= " WHERE e.anypreu = ".$current." " ;
		
		if ($idcomanda > 0) {
			$strQuery .= " AND p.id = ".$idcomanda." ";
		} else {
			if ($mincomanda > 0) $strQuery .= " AND p.id >= ".$mincomanda." ";
			$strQuery .= " AND p.dataalta >= '".$current."-01-01 00:00:00' ";
		}
		$strQuery .= " GROUP BY p.id, p.importparte, p.dataalta, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ";
		$strQuery .= " ccat, cpro, csim, p.datapagament, p.estatpagament, p.dadespagament, p.importpagament, ";
		$strQuery .= " p.comentari, p.datafacturacio, p.numfactura, e.preu, e.iva";
		$strQuery .= " ORDER BY p.id, csim ";
	
	
		/*
		"SELECT p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, t.descripcio as tdesc, 
		c.categoria as ccat, c.producte as cpro, c.simbol as csim, p.datapagament, p.estatpagament, p.dadespagament, 
		p.importpagament, p.comentari, p.datafacturacio, p.numfactura, e.preu, e.iva, COUNT(l.id) as total 
		FROM m_partes p LEFT JOIN m_llicencies l ON p.id = l.parte INNER JOIN m_categories c ON c.id = l.categoria 
		INNER JOIN m_tipusparte t ON c.tipusparte = t.id 
		INNER JOIN m_productes o ON c.producte = o.id INNER JOIN m_preus e ON e.producte = o.id    
		
		WHERE e.anypreu = 2015 AND p.dataalta >= '2015-01-01 00:00:00' 
		GROUP BY p.id, p.importparte, p.dataentradadel, p.databaixadel, p.clubdel, tdesc, ccat, cpro, csim, p.datapagament, 
		p.estatpagament, p.dadespagament, p.importpagament, p.comentari, p.datafacturacio, p.numfactura, e.preu, e.iva 
		ORDER BY p.id, csim "
		*/
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$partes2015 = $stmt->fetchAll();
	
		
		if ($idcomanda > 0) {
			if (count($partes2015) == 0) return new Response("CAP");	
			
			$duplicats2015 = array();
		} else {
			$strQuery = "SELECT d.*,  p.id as pid, p.descripcio as pdescripcio, e.preu as epreu, e.iva as eiva ";
			$strQuery .= " FROM m_duplicats d  INNER JOIN m_carnets c ON d.carnet = c.id ";
			$strQuery .= " INNER JOIN m_productes p ON c.producte = p.id ";
			$strQuery .= " INNER JOIN m_preus e ON e.producte = p.id ";
			$strQuery .= " WHERE e.anypreu = 2015 ";
			if ($minduplicat > 0) $strQuery .= " AND d.id >= ".$minduplicat." ";
			$strQuery .= " ORDER BY d.datapeticio ";
		
			$stmt = $em->getConnection()->prepare($strQuery);
			$stmt->execute();
			$duplicats2015 = $stmt->fetchAll();
		}
		
		
		$strQuery = "SELECT * FROM m_clubs c ORDER BY c.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$clubs = array();
		while (isset($aux[$i])) {
			$clubs[ $aux[$i]['compte'] ] = $aux[$i];
			$i++;
		}
		
		echo "Total partes: " . count($partes2015) . PHP_EOL;
		echo "Total dupli: " . count($duplicats2015) . PHP_EOL;
	
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		// Tractar duplicats primer
		$current = date('Y');
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($current, BaseController::COMANDES) + 1
		);
	
		$idup = 0;
		$ipar = 0;
	
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		
		$dataCurrent = \DateTime::createFromFormat('Y-m-d', "2015-01-01");
		
		try {
			 /***********************************************************************************/
			 /****************************   PARTES i DUPLICATS  ********************************/
			 /***********************************************************************************/
			
			if (count($duplicats2015) > 0) error_log('Primer DUPLI '.$duplicats2015[0]['datapeticio']);
			error_log('Primer PARTE '.$partes2015[0]['dataentradadel']);
			if (count($duplicats2015) > 0) 'Primer DUPLI '.$duplicats2015[0]['datapeticio'].'<br/>';
			echo 'Primer PARTE '.$partes2015[0]['dataentradadel'].'<br/>';
			
			$year = 2014;
			
			while ($dataCurrent->format('Y-m-d') < '2016-01-01') {
				error_log('***************************************************************************');
				error_log('============> DIA '.$dataCurrent->format('Y-m-d').'  <=====================');
				error_log('***************************************************************************');
				echo '***************************************************************************'.'<br/>';
				echo '============> DIA '.$dataCurrent->format('Y-m-d').'  <====================='.'<br/>';
				echo '***************************************************************************'.'<br/>';
				
				if ($dataCurrent->format('Y') > $year) {
					$year = $dataCurrent->format('Y');
					$maxnums['maxnumcomanda'] = 1;
				}
				
				
				$parteid = 0;
				$partes = array();
				
				//echo '!!!!!!!!!!!!!!!!! '.substr($duplicats2015[$idup]['datapeticio'],0,10).'-'.$dataCurrent->format('Y-m-d').'!!!!!!!!!<br/>';
				while (isset($duplicats2015[$idup]) && substr($duplicats2015[$idup]['datapeticio'],0,10) <= $dataCurrent->format('Y-m-d')) {
					$duplicat = $duplicats2015[$idup];
					$this->insertComandaDuplicat($clubs, $duplicat, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
					$idup++;
				}
				
				//echo '!!!!!!!!!!!!!!!!! '.substr($partes2015[$ipar]['dataentradadel'],0,10).'-'.$dataCurrent->format('Y-m-d').'!!!!!!!!!<br/>';
				while (isset($partes2015[$ipar]) && substr($partes2015[$ipar]['dataentradadel'],0,10) <= $dataCurrent->format('Y-m-d')) {
					$parte = $partes2015[$ipar];
					if ($parteid == 0) $parteid = $parte['id'];
					
					if ($parteid != $parte['id']) {
						// Agrupar partes, poden venir vàries línies seguides segons categoria 'A', 'T' ...
						$this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
							
						$parteid = $parte['id'];
						$partes = array();
					}
					$partes[] = $parte;
					$ipar++;
				}
				// El darrer parte del dia
				if ($parteid > 0) $this->insertComandaParte($clubs, $partes, $maxnums, ($maxnums['maxnumcomanda'] % $batchSize) == 0);
				
				// Següent dia
				$dataCurrent->add(new \DateInterval('P1D'));
			}
			
			$em->getConnection()->commit();
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("FINAL");
	}
	
	
	public function updatemigrafacturesAction(Request $request) {
		// http://www.fecdas.dev/updatemigrafactures?id=
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		error_log('Inicia updatemigrafacturesAction');
		$batchSize = 20;
		$id = $request->query->get('id', 0); // min id
		try {
			$em = $this->getDoctrine()->getManager();
			
			//$em->getConnection()->beginTransaction(); // suspend auto-commit
			
			$repository = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda');
			
			$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityComanda c ";
			$strQuery .= " WHERE c.id >= :id AND c.factura IS NOT NULL ";
			$strQuery .= " ORDER BY c.id";
	
			$query = $em->createQuery($strQuery);
			$query->setParameter('id', $id);
	
			$comandes = $query->getResult();
			error_log(count($comandes). ' comandes');
			$total = 0;
			foreach ($comandes as $comanda) {
				error_log('comanda => '.$comanda->getId());
				$factura = $comanda->getFactura();
				if ($factura != null && ($factura->getDetalls() == 'null' 	|| 
										$factura->getDetalls() == '' 		||
										$factura->getDetalls() == null)) {
					
					$detalls = $comanda->getDetallsAcumulats();
					//$detalls = json_encode($detalls, JSON_UNESCAPED_UNICODE); // Desar estat detalls a la factura
					$detalls = json_encode($detalls); // Desar estat detalls a la factura
					$factura->setDetalls($detalls);
				}
				$total++;
				if ( ($total % $batchSize) == 0 ) {
					//$em->getConnection()->commit();
					$em->flush();
				}
									
			}
			$em->flush();
			error_log('Acaba updatemigrafacturesAction');
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
		return new Response("");
	}

	public function omplirdetallsfacturesAction(Request $request) {
		// http://www.fecdas.dev/omplirdetallsfactures?current=2015  => Torna a calcular detalls per factures del 2015 amb detalls ''
		// http://www.fecdas.dev/omplirdetallsfactures?id=XXXX  => Torna a calcular detalls per factures id
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		echo 'Inicia omplirdetallsfacturesAction';
		$batchSize = 20;
		$current = $request->query->get('current', date('Y')); 
		$id = $request->query->get('id', 0); // min id
		try {
			$em = $this->getDoctrine()->getManager();

			$em->getConnection()->beginTransaction(); // suspend auto-commit			
			if ($id == 0) {
			
				$desde = date('Y').'-01-01 00:00:00';
				$fins = date('Y').'-12-31 23:59:59';
				
				$strQuery = " SELECT f FROM FecdasBundle\Entity\EntityFactura f ";
				$strQuery .= " WHERE f.datafactura >= :desde AND f.datafactura <= :fins AND f.detalls = '' ";
				$strQuery .= " ORDER BY f.id";
		
				$query = $em->createQuery($strQuery);
				$query->setParameter('desde', $desde);
				$query->setParameter('fins', $fins);
				$factures = $query->getResult();
			} else {
				$factura = $this->getDoctrine()->getRepository('FecdasBundle:EntityFactura')->findOneById($id);
				$factures = array ( $factura );
			}

			echo count($factures). ' factures';
			$total = 0;
			foreach ($factures as $factura) {
				
				if ($factura->esAnulacio()) $comanda = $factura->getComandaanulacio();
				else $comanda = $factura->getComanda();
				
				if ($comanda == null) {
					echo "factura sense comanda " . $factura->getId(). ' ' .$factura->getNum();
					
				} else {
					echo "factura actualitzada: ".$factura->getNumFactura();
					$detalls = $comanda->getDetallsAcumulats();
					//$detalls = json_encode($detalls, JSON_UNESCAPED_UNICODE); // Desar estat detalls a la factura
					$detalls = json_encode($detalls); // Desar estat detalls a la factura
					$factura->setDetalls($detalls);
					$em->flush();
				}
				$total++;
									
			}
			$em->flush();
			echo 'Acaba omplirdetallsfacturesAction';
			$em->getConnection()->commit();
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
		return new Response("");
	}
	
	public function migraanulacomandesAction(Request $request) {
		// http://www.fecdas.dev/migraanulacomandes
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$strQuery = "SELECT a.id, a.dni, a.dataanulacio, a.codiclub,  ";
		$strQuery .= " a.dataparte, a.categoria, a.tipusparte, a.factura, a.relacio, e.preu ";
		$strQuery .= " FROM IMPORT_ANULACIONS a LEFT JOIN m_persones p ON a.dni = p.dni AND a.codiclub = p.club ";
		$strQuery .= " INNER JOIN m_categories c ON c.tipusparte = a.tipusparte AND c.simbol = a.categoria  ";
		$strQuery .= " INNER JOIN m_productes o ON c.producte = o.id ";
		$strQuery .= " INNER JOIN m_preus e ON e.producte = o.id ";
		$strQuery .= " WHERE e.anypreu = 2015 ";
		
		$strQuery .= " ORDER BY a.factura, a.relacio ";
	
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$anulacions2015 = $stmt->fetchAll();
	
		echo "Total anul·la: " . count($anulacions2015) . PHP_EOL;
	
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		$facturanum = '';
		$relacio = 0;
		$comanda = null;
		$detalls = '';
		$dataanulacio = null;
		$import = 0;
		$aficionats = 0;
		$tecnics = 0;
		$infantils = 0;
		$current = date('Y');
		
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		
		try {
			foreach ($anulacions2015 as $anulacio) {
				
				if ($facturanum == '' ) {
					$facturanum = $anulacio['factura'];
					$dataanulacio = \DateTime::createFromFormat('d/m/Y', $anulacio['dataanulacio']);
					$import = $anulacio['preu'];
					
					$aficionats = ($anulacio['categoria']=='A'?1:0);
					$tecnics = ($anulacio['categoria']=='T'?1:0);
					$infantils = ($anulacio['categoria']=='I'?1:0);
					
					$relacio = $anulacio['relacio'];
					
					$query = $em->createQuery("SELECT p FROM FecdasBundle:EntityParte p 
											WHERE p.numrelacio = :rel AND 
											p.dataalta >= '2015-01-01 00:00:00' AND
											p.club = :club AND
											p.databaixa IS NULL	")->setParameter('rel', $relacio)
											->setParameter('club', $anulacio['codiclub']);
					
					$parte = $query->setMaxResults(1)->getOneOrNullResult();
					
					if ($parte == null) {
						echo "ERROR ANULA 1 comanda no trobada => relacio  ".$relacio."<br/>";
						return new Response("");
					}
				}

				if ($facturanum != $anulacio['factura']) {
					// Consolidar factura
					$concepte = 'Anul·lació. ';
					if ($aficionats > 0) $concepte .= $aficionats.'x Aficionats ';
					if ($tecnics > 0) $concepte .= $tecnics.'x Tècnics ';
					if ($infantils > 0) $concepte .= $infantils.'x Infantils ';
					
					echo "***********************<br/>";
					echo "factura  ".$facturanum."<br/>";
					echo "data  ".$dataanulacio->format('Y-m-d')."<br/>";
					echo "import  ".$import."<br/>";
					echo "concepte  ".$concepte."<br/>";
					echo "detalls  ".$detalls."<br/>";
					echo "***********************<br/>";
					echo "<br/>";
					
					
					if ($current < date('Y')) $facturaId = $this->inserirFactura('2014-12-31', str_replace('/2014', '', $facturanum), (-1)*$import, $concepte); // 2014
					else $facturaId = $this->inserirFactura($dataanulacio->format('Y-m-d'), str_replace('/2015', '', $facturanum), (-1)*$import, $concepte);
					
					$factura = $this->getDoctrine()->getRepository('FecdasBundle:EntityFactura')->findOneById($facturaId);
					
					$parte->addFacturaanulacio($factura);
					$factura->setComandaanulacio($parte);
					$factura->setComanda(null);
					//$factura->setDetalls(json_encode($detalls));
					$factura->setDetalls($detalls);
					
					// Actualitzar dades
					$facturanum = $anulacio['factura'];
					$dataanulacio = \DateTime::createFromFormat('d/m/Y', $anulacio['dataanulacio']);
					$import = $anulacio['preu'];
					$aficionats = ($anulacio['categoria']=='A'?1:0);
					$tecnics = ($anulacio['categoria']=='T'?1:0);
					$infantils = ($anulacio['categoria']=='I'?1:0);
				;
				} else {
					$import += $anulacio['preu'];
					$aficionats += ($anulacio['categoria']=='A'?1:0);
					$tecnics += ($anulacio['categoria']=='T'?1:0);
					$infantils += ($anulacio['categoria']=='I'?1:0);
					
					
				}
								
				if ($relacio != $anulacio['relacio'] ) {
					$relacio = $anulacio['relacio'];
					$detalls = '';
					$current = date('Y');
					$query = $em->createQuery("SELECT p FROM FecdasBundle:EntityParte p 
											WHERE p.numrelacio = :rel AND 
											p.dataalta >= '2015-01-01 00:00:00' AND
											p.club = :club AND
											p.databaixa IS NULL	")->setParameter('rel', $relacio)
											->setParameter('club', $anulacio['codiclub']);
					
					$parte = $query->setMaxResults(1)->getOneOrNullResult();
					if ($parte == null) {
						// Provar 2014 
						$query = $em->createQuery("SELECT p FROM FecdasBundle:EntityParte p 
											WHERE p.numrelacio = :rel AND 
											p.dataalta >= '2014-01-01 00:00:00' AND
											p.club = :club AND
											p.databaixa IS NULL	")->setParameter('rel', $relacio)
											->setParameter('club', $anulacio['codiclub']);
					
						$parte = $query->setMaxResults(1)->getOneOrNullResult();
						
						if ($parte == null) {
							echo "ERROR ANULA 3 comanda no trobada => relacio  ".$relacio."-".$anulacio['codiclub']."<br/>";
							//return new Response("");
							continue;
						} 
						$current = date('Y')-1;
					}	
				
				}
				$llicencia = null;
				//echo '?'. $anulacio['dni']."<br/>";
				foreach ($parte->getLlicencies() as $llic) {
					//if (!$llic->esBaixa()) {
						//echo '=>'. $llic->getPersona()->getDni()."<br/>";
						if ($llic->getPersona()->getDni() == $anulacio['dni']) $llicencia = $llic;
					//}
				}
				if ($llicencia == null) {
					echo "ERROR ANULA 4 llicencia no existeix per  ".$anulacio['dni']." relacio ".$relacio."<br/>";
					return new Response("");
				}
				//$detalls = $parte->getDetallsAcumulats(); // sense baixes
				
				if ($detalls == '') $detalls = 'baixes: '.$llicencia->getPersona()->getNomCognoms();
				else $detalls .= ', '.$llicencia->getPersona()->getNomCognoms();
				
			}
			$em->flush();
			
			$em->getConnection()->commit();
			
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("FINAL");
	}
	
	
	public function migraaltresAction(Request $request) {
		// http://www.fecdasnou.dev/migraaltres?num=0
		// Script de migració. Executar per migrar i desactivar
	
		if (!$this->isAuthenticated())
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		if (!$this->isCurrentAdmin())
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();
	
		$num = $request->query->get('num', 0); // min num
		$codi = $request->query->get('codi', 0); // Club
		
		$batchSize = 20;
	
		$strQuery = "SELECT p.*, e.preu, e.iva FROM m_productes p LEFT JOIN m_preus e ON e.producte = p.id WHERE e.anypreu = 2015 ORDER BY p.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$productes = array();
		while (isset($aux[$i])) {
			if ($aux[$i]['preu'] == null) $aux[$i]['preu'] = 0;
			if ($aux[$i]['iva'] == null) $aux[$i]['iva'] = 0;
				
			$productes[ $aux[$i]['codi'] ] = $aux[$i];
			$i++;
		}
	
		$strQuery = " SELECT * FROM m_clubs c ";
		if ($codi != '') $strQuery .= " WHERE c.codi = '".$codi."'";
		else $strQuery .= " ORDER BY c.codi ";
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$aux = $stmt->fetchAll();
		$i = 0;
		$compteClub = '';
		$clubs = array();
		while (isset($aux[$i])) {
			$clubs[ $aux[$i]['compte'] ] = $aux[$i];
			if ($codi != '' && $aux[$i]['codi'] == $codi) $compteClub = $aux[$i]['compte'];  
			$i++;
		}
	
		echo '-'.$compteClub.'-';
		 
		
		
		// Clubs: 4310000 - 4310999
		// Productes: 6230300  - 7590006  (Llicències 7010000 - 7010025) (Duplicats 7090000, 7590000, 7590002, 7590004)
	
		// SELECT num, COUNT(dh) FROM `apunts_2015` WHERE dh = 'D' GROUP BY num HAVING COUNT(dh) > 1	=> 3,5,11,13,16,18
	
		// SELECT num FROM apunts_2015 WHERE (compte >= 4310000 AND compte <= 4310999) OR
		//			(compte >= 6230300 AND compte < 7010000 AND compte > 7010025 AND compte <= 7590006 AND compte NOT IN (7090000, 7590000, 7590002, 7590004) )
	
		if ($compteClub != '') {
			$strQuery = "SELECT DISTINCT num FROM apunts_2015 a WHERE compte = ".$compteClub;
			if ($num > 0) $strQuery .= " AND num = ".$num;
			$strQuery .= " ORDER BY a.num ";
			$stmt = $em->getConnection()->prepare($strQuery);
			$stmt->execute();
			$numapuntsClub = $stmt->fetchAll();
			
			if (count($numapuntsClub) == 0) {
				echo "sense apunts pel compte ".$compteClub;
				exit;
			}
			
			$apuntsClub = array(); 
			foreach ($numapuntsClub as $key => $value) {
				//echo json_encode($value);
				$apuntsClub[] = $value['num'];
			}
				
			$strQuery = "SELECT * FROM apunts_2015 a WHERE num IN (".implode(", ", $apuntsClub).") ORDER BY a.num";
			$stmt = $em->getConnection()->prepare($strQuery);
			$stmt->execute();
			$altress2015 = $stmt->fetchAll();
			
		} else {
			$strQuery = "SELECT * FROM apunts_2015 a ";
			//$strQuery .= " ORDER BY a.data, a.num, a.dh ";
			if ($num > 0) $strQuery .= " WHERE num >= ".$num;
			$strQuery .= " ORDER BY a.num ";
		
			$stmt = $em->getConnection()->prepare($strQuery);
			$stmt->execute();
			$altress2015 = $stmt->fetchAll();
	
		}
	
		echo "Total apunts: " . count($altress2015) . PHP_EOL;
	
		echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		// Tractar duplicats primer
		$current = date('Y');
		$maxnums = array(
				'maxnumcomanda' => $this->getMaxNumEntity($current, BaseController::COMANDES) + 1
		);
	
		$iapu = 0;
		$i = 0;
	
		$em->getConnection()->beginTransaction(); // suspend auto-commit
	
		try {
			/***********************************************************************************/
			/****************************   PARTES i DUPLICATS  ********************************/
			/***********************************************************************************/
				
			error_log('Primer ALTRE '.$altress2015[0]['data']);
			echo 'Primer ALTRE '.$altress2015[0]['data'].'<br/>';
				
			$altrenum = 0;
			$altres = array();
			$sortir = false;
			$persist = true;
			$facturesRebutsPendents = array(); 
			while (isset($altress2015[$iapu]) && $sortir == false) {
				
				$altre = $altress2015[$iapu];
				echo '=>'. $altre['num'].'-'.$iapu.'<br/>';	
				if ($altrenum == 0) $altrenum = $altre['num'];
				if ($altrenum != $altre['num']) {
					// Agrupar apunts
					$this->insertComandaAltre($clubs, $productes, $altres, $maxnums, $facturesRebutsPendents, $persist, true);
					$altrenum = $altre['num'];
					$altres = array();
				}
					
				if ($altre['dh'] == 'D') $altres['D'][] = $altre;
				else $altres['H'][] = $altre;
				$iapu++;
				if ($iapu > 5000 && $altre['dh'] == 'D') $sortir = true;
			}
			// La darrera comanda altre del dia
			if ($altrenum > 0) $this->insertComandaAltre($clubs, $productes, $altres, $maxnums, $facturesRebutsPendents, $persist, true);
			
			echo "Apunt FINAL ".$iapu."<br/>";
			// Mirar factures pendents
			error_log(str_repeat("=", 40))." ".count($facturesRebutsPendents);
			echo str_repeat("=", 40)." ".count($facturesRebutsPendents)."<br/>";
			echo json_encode($facturesRebutsPendents)."<br/>";
			/*foreach ($facturesRebutsPendents as $k =>  $factura) {
				error_log("ERROR REBUTS/INGRESOS 9 Factura -".($k)."- no trobada, concepte =>".$factura['concepte'].
						"<= => id: ".$factura['id'].", anotació: ".$factura['num'].", de compte: ".$factura['compte']);
				echo "ERROR REBUTS/INGRESOS 9 Factura -".($k)."- no trobada, concepte =>".$factura['concepte'].
						"<= => id: ".$factura['id'].", anotació: ".$factura['num'].", de compte: ".$factura['compte'].")<br/>";
			}*/
			$em->getConnection()->commit();
		} catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo "Problemes durant la transacció : ". $e->getMessage();
		}
			
		echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL."<br/>";
	
		return new Response("FINAL");
	}
	
		
	
	
	
	private function insertComandaAltre($clubs, $productes, $altres, &$maxnums, &$facturesRebutsPendents, $persist = false, $warning = true) {
		
		// Poden haber vàris 'H' => un club parte llicències Tècnic + Aficionat per exemple
		// SELECT * FROM apunts_2015 a inner join m_productes p On a.compte = p.codi WHERE 1 ORDER BY data, num, dh
		//
		// SELECT * FROM apunts_2015 WHERE num IN ( 119, 510 ) ORDER BY num, dh
		$em = $this->getDoctrine()->getManager();
		
		$rebutId = 0;
		if (count($altres['D']) == 1) {
			// Un debe. Situació normal un club deutor
			$compteD = $altres['D'][0]['compte']; 
			$id = $altres['D'][0]['id'];
			$num = $altres['D'][0]['num'];
			$data = $altres['D'][0]['data'];
			$anyFactura = substr($data, 0, 4);  // 2015-10-02
			if (count($altres['H']) == 0) {
				error_log("ERROR 1 CAP 'H' = >".$id." ".$num);
				return;
			}
			
			//Mirar Apunts debe entre comptes  4310001 i 4310999 excepte (4310149)
			if ($compteD >= 4310001 && $compteD <= 4310999 && $compteD != 4310149) {
				// Deutor club, Factura 
				if ( !isset( $clubs[$compteD])) {
					error_log($id." ".$num."WARNING - COMANDES 15 Club deutor no existeix=> ".$compteD);
					if  ($warning == true) echo $id." ".$num."WARNING - COMANDES 15 Club deutor no existeix=> ".$compteD."<br/>";
					return;
				}
				$club = $clubs[$compteD];
				$concepteAux = $altres['D'][0]['concepte'];
				$numFactura = $this->extraerFactures(strtolower($concepteAux), 'F');
				
				if (!is_numeric($numFactura)) {
					error_log($id." ".$num."ERROR COMANDA 16 Número de factura no numèrica=> ".$numFactura."(".$compteD.")");
					echo $id." ".$num."ERROR COMANDA 16 Número de factura no numèrica=> ".$numFactura."(".$compteD.")<br/>";
					return;
				}
				
				$import = $altres['D'][0]['importapunt'];

				// Revisió	
				$importDetalls = 0;
				$tipusAnterior = 0;
				$actualCompteDuplicat = '';
				$compteH = '';
				for ($i = 0; $i < count($altres['H']); $i++) {
				
					$compteH = $altres['H'][$i]['compte'];
					
					if (!isset( $productes[$compteH])) {
						error_log($id." ".$num."ERROR COMANDA 17 producte no existeix=> ".$compteH);
						echo $id." ".$num."ERROR COMANDA 17 producte no existeix=> ".$compteH."<br/>";
						return;
					}
						
					$producte = $productes[$compteH];
					//if ($tipusAnterior == 0 && $producte['tipus'] != 6290900) $tipusAnterior = $producte['tipus'];  // Correus no compta
					if ($tipusAnterior == 0 && $compteH != 6290900) $tipusAnterior = $producte['tipus'];  // Correus no compta
					

					//if ($producte['tipus'] != $tipusAnterior && $producte['tipus'] != 6290900) {
					if ($producte['tipus'] != $tipusAnterior && $compteH != 6290900) {
						if ($warning == true) {
							error_log($id." ".$num."WARNING COMANDA 18 tipus productes barrejats:".$tipusAnterior."=> ".$compteH);
							echo $id." ".$num."WARNING COMANDA 18 tipus productes barrejats:".$tipusAnterior."=> ".$compteH."<br/>";
						}
						$tipusAnterior = BaseController::TIPUS_PRODUCTE_ALTRES;
					}
						
					$importDetalls += $altres['H'][$i]['importapunt'];
						
					if ($tipusAnterior == BaseController::TIPUS_PRODUCTE_DUPLICATS) $actualCompteDuplicat = $compteH;
				}
				
				if (abs($import - $importDetalls) > 0.01)  {
					error_log($id." ".$num."ERROR COMANDA 20 suma detalls incorrecte=> D ".$altres['D'][0]['num']." - ".$altres['D'][0]['concepte']." ==> H ".json_encode($altres['H']));
					echo $id." ".$num."ERROR COMANDA 20 suma detalls incorrecte=> D ".$altres['D'][0]['num']." - ".$altres['D'][0]['concepte']."(".$import.")".
							" ==> H ".json_encode($altres['H'])."(".$importDetalls.")"."<br/>";
					return;
				}

				if ($tipusAnterior == 0 && $compteH == '6290900') $tipusAnterior = BaseController::TIPUS_PRODUCTE_ALTRES; // Assentaments només correu
			
				if ($tipusAnterior <= 0 || $tipusAnterior > 6)  {
					error_log($id." ".$num."ERROR 22 tipus producte desconegut:".$tipusAnterior." => ".$compteH);
					echo $id." ".$num."ERROR 22 tipus producte desconegut".$tipusAnterior." => ".$compteH."<br/>";
					$tipusAnterior = BaseController::TIPUS_PRODUCTE_LLICENCIES;
					return;
				}
				
				$textComanda = 'COMANDA '.BaseController::getTipusProducte($tipusAnterior).' '.$concepteAux;
				$textComanda = str_replace("'","''", $textComanda);
				
				$facturaId = 0;
				$comandaId = 0;	
				
				if (is_numeric($numFactura) && isset($facturesRebutsPendents[$numFactura*1])) {
					error_log($id." ".$num."INFO 22 Rebut existent. Associar amb comanda de la factura ".$numFactura." => ".$compteD);
					echo $id." ".$num."INFO 22 Rebut existent. Associar amb comanda de la factura ".$numFactura." => ".$compteD."<br/>";
				}
				
				switch ($tipusAnterior) {
				case BaseController::TIPUS_PRODUCTE_LLICENCIES:
					// Validar que existeix el parte. Si no troba la factura alta parte amb WARNING
					$pos = strpos($concepteAux, '/2014');
					if ($pos !== false) $anyFactura = 2014; 
					$factExistent = $this->consultarFactura($numFactura, $anyFactura);
					if ($factExistent == null) {
						if ($import > 0) {
							error_log($id." ".$num."WARNING COMANDA 9 Factura no trobada. Crear factura i comanda nova llicències per factura ".$numFactura." => ".$compteD);
							if  ($warning == true) echo $id." ".$num."WARNING COMANDA 9 Factura no trobada. Crear factura i comanda nova llicències per factura ".$numFactura." => ".$compteD."<br/>";
	if ($persist == false) return;
							$facturaId = $this->inserirFactura($data, $numFactura, $import, "Factura - ".$textComanda);
							$comandaId = $this->inserirComandaAmbDetalls($data, $club['codi'], $maxnums, $textComanda, 0, $facturaId, $altres['H'], $productes);
							
							// Revisar si existeix rebut pendent anterior
							if (is_numeric($numFactura) && isset($facturesRebutsPendents[$numFactura*1])) {
								$this->updateComandaNumRebut($comandaId, $facturesRebutsPendents[$numFactura*1]);
								unset($facturesRebutsPendents[$numFactura*1]);
							}
							
						} else {
							error_log($id." ".$num."REVISAR 15 Factura anul·lació llicencies. Crear factura i afegir a anul·lació ".$numFactura." => ".$compteD);
							echo $id." ".$num."REVISAR 15 Factura anul·lació llicències. Crear factura i afegir a anul·lació ".$numFactura." => ".$compteD."<br/>";
						return;
						}
						
					} else {
						// Validar factures import diferent
						if ($factExistent['import'] != $import) {
							error_log($id." ".$num."ERROR COMANDA 10 Factura imports incorrectes. Actualitzar import factura??? ".$numFactura." => ".$compteD);
							if  ($warning == true) echo $id." ".$num."ERROR COMANDA 10 Factura imports incorrectes. Actualitzar import factura??? ".$numFactura." => ".$compteD."<br/>";
	if ($persist == false) return;							
							//$query = "UPDATE m_factures SET import = ".$import." WHERE id = ". $facturaId;
							//$em->getConnection()->exec( $query );					
						} else {
							error_log($id." ".$num."INFO 10 Factura existent. No inserida ".$numFactura." => ".$compteD);
							echo $id." ".$num."INFO 10 Factura existent. No inserida ".$numFactura." => ".$compteD."<br/>";
						}
					}
						
					break;
				case BaseController::TIPUS_PRODUCTE_DUPLICATS:  // Duplicats 7590002 7590000 7590004 7090000
				case BaseController::TIPUS_PRODUCTE_KITS:
				case BaseController::TIPUS_PRODUCTE_MERCHA:
				case BaseController::TIPUS_PRODUCTE_CURSOS:
				case BaseController::TIPUS_PRODUCTE_ALTRES:
					// Validar que existeix el parte. Cal inserir la Factura
					$pos = strpos($concepteAux, '/2014');
					if ($pos !== false) $anyFactura = 2014; 
					$factExistent = $this->consultarFactura($numFactura, $anyFactura);
					if ($factExistent == null) {
						// Insertar factura
						if ($import > 0) {
		if ($persist == false) return;
							$facturaId = $this->inserirFactura($data, $numFactura, $import, "Factura - ".$textComanda);
							if ($tipusAnterior != BaseController::TIPUS_PRODUCTE_DUPLICATS) {
								// Insertar comanda
								$comandaId = $this->inserirComandaAmbDetalls($data, $club['codi'], $maxnums, $textComanda, 0, $facturaId, $altres['H'], $productes);
								
								// Revisar si existeix rebut pendent anterior
								if (is_numeric($numFactura) && isset($facturesRebutsPendents[$numFactura*1])) {
									$this->updateComandaNumRebut($comandaId, $facturesRebutsPendents[$numFactura*1]);
									unset($facturesRebutsPendents[$numFactura*1]);
								}
								
							} else {
								//Buscar la comanda del club encara sense factura
								$strQuery = " SELECT c FROM FecdasBundle\Entity\EntityDuplicat c INNER JOIN c.detalls d INNER JOIN d.producte p";
								$strQuery .= " WHERE c.club = :codi ";
								$strQuery .= " AND c.datapeticio <= :data ";
								$strQuery .= " AND c.databaixa IS NULL ";
								$strQuery .= " AND c.factura IS NULL ";
								$strQuery .= " AND d.unitats = 1 ";
								$strQuery .= " AND p.codi = :compte ";
								$strQuery .= " ORDER BY c.datapeticio DESC ";
							
								$query = $em->createQuery($strQuery);
								$query->setParameter('codi', $club['codi']);
								$query->setParameter('data', $data);
								$query->setParameter('compte', $actualCompteDuplicat);  
							
								$result = $query->getResult();
								
								if (count($result) >= 1) $comandaId = $result[0]->getId(); 
								else {
									$comandaId = $this->inserirComandaAmbDetalls($data, $club['codi'], $maxnums, $textComanda, 0, $facturaId, $altres['H'], $productes);
									
									error_log($id." ".$num."WARNING REBUTS/INGRESOS 12 Comanda duplicat ".$actualCompteDuplicat." no trobada => ".$compteD." ".$club['codi']." ".$numFactura);
									if ($warning == true) echo $id." ".$num."WARNING REBUTS/INGRESOS 12 Comanda duplicat ".$actualCompteDuplicat." no trobada => ".$compteD." ".$club['codi']." ".$numFactura."<br/>";
									
									return;
								}
							}
						} else {
							error_log($id." ".$num."REVISAR 17 Factura anul·lació altres. Crear factura i afegir a anul·lació ".$numFactura." => ".$compteD);
							echo $id." ".$num."REVISAR 17 Factura anul·lació altres.  Crear factura i afegir a anul·lació ".$numFactura." => ".$compteD."<br/>";
							return;
						}
					
					} else {
						/*$facturaId = $factExistent['id'];
						$comanda = $this->consultarComanda($factExistent['id']);			
						$comandaId = 0;
						if ($comanda != null) $comandaId = $comanda['id'];	*/
						
						error_log($id." ".$num."INFO 17 Factura existent. No inserida ".$numFactura." => ".$compteD);
						echo $id." ".$num."INFO 17 Factura existent. No inserida ".$numFactura." => ".$compteD."<br/>";
						
					}

					break;
				}
		
				if ($facturaId != 0 && $comandaId != 0) {
if ($persist == false) return;					
					$this->updateComandaNumFactura($comandaId, $facturaId);	
				} else {
					if ($tipusAnterior != BaseController::TIPUS_PRODUCTE_LLICENCIES) {
						error_log($id." ".$num."ERROR REBUTS/INGRESOS 13  insercions facturaId: ".$facturaId." comandaId: ".$comandaId." REVISAR ".$tipusAnterior."=> ".$compteD. " ".$numFactura);
						echo $id." ".$num."ERROR REBUTS/INGRESOS 13  insercions facturaId: ".$facturaId." comandaId: ".$comandaId." REVISAR ".$tipusAnterior."=> ".$compteD. " ".$numFactura."<br/>";
						return;
					}
				}			
			}

			if ($compteD >= 5700000 && $compteD <= 5720005) {
				// Rebut ingrés, següent compte club
				// Camp concepte està la factura:  	FW:00063/2015 o Factura: 00076/2015
				// Camp document està el rebut: 	00010/15
				if (count($altres['H']) != 1) {
					error_log("ERROR 2 => ".$num);
					return;
				}
				$compteH = $altres['H'][0]['compte'];
				
				//Mirar Apunts haber a comptes  4310001 i 4310999 excepte (4310149)  => Clubs
				if ($compteH >= 4310001 && $compteH <= 4310999 && $compteH != 4310149) {
					if ( !isset( $clubs[$compteH])) {
						error_log($id." ".$num."WARNING REBUTS/INGRESOS - 7 Club pagador no existeix=> ".$compteH);	
						if  ($warning == true) echo $id." ".$num."WARNING REBUTS/INGRESOS 7 Club pagador no existeix=> ".$compteH."<br/>";
						return;
					}
					if ($altres['D'][0]['concepte'] != $altres['H'][0]['concepte'] ||
						$altres['D'][0]['document'] != $altres['H'][0]['document'] ||
						$altres['D'][0]['importapunt'] != $altres['H'][0]['importapunt']) {
							error_log($id." ".$num."ERROR 3 CAP 'H'");
							echo $id." ".$num."ERROR 3 CAP 'H'<br/>";
							return;
					}
					$concepteAux = $altres['D'][0]['concepte'];	// FW:00063/2015 o Factura: 00076/2015 o Fra. 2541/2015 o FW:00510/2015 o F. 1456/2014
					$ingres = true; 
					$numFactura = 'NA';
					$datapagament = $data;
					$dataentrada = $data; 
					$dadespagament = null;
					$comentari = str_replace("'","''", $altres['D'][0]['concepte']);
	
					$rebutAux = $altres['D'][0]['document'];		// 00010/15
					$numRebut = substr($rebutAux, 0, 5);
							
					$numRebut = $numRebut * 1;  // Number
					$import = $altres['D'][0]['importapunt'];
					$tipuspagament = null;
					switch ($compteD) {
						case 5700000:  	// CAIXA FEDERACIÓ, PTES.
							$tipuspagament = BaseController::TIPUS_PAGAMENT_CASH;
							break;
						case 5720001: 	// "LA CAIXA"  LAIETANIA
							$tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
							break;
						case 5720003:	// LA CAIXA-SARDENYA
							$tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_SARDENYA;
							break;
						case 5720002:	// LA CAIXA-OF. MALLORCA
						case 5720004:	// CAIXA CATALUNYA
						case 5720005:	// POLISSA DE CREDIT
							error_log($id." ".$num."ERROR REBUTS/INGRESOS 8 Tipus de pagament desconegut => ".$id." ".$num." ".$compteD);
							echo $id." ".$num."ERROR REBUTS/INGRESOS 8 Tipus de pagament desconegut => ".$compteD."<br/>";
							return;
											
							break;
					}
					$numFactures = $this->extraerFactures(strtolower($concepteAux), 'R');

					if ($numFactures != null) {
						// Ok, trobadaes 
					
						$comandesIdPerActualitzar = array();
						$numfacturesNoexistents = array();	
											
						foreach ($numFactures as  $numFactura) {
							$pos = strpos(strtolower($concepteAux), '/2014');
							if ($pos !== false) $anyFactura = 2014; 
							
							$factExistent = $this->consultarFactura($numFactura, $anyFactura);
								
							if ($factExistent == null) {
								//error_log("ERROR REBUTS/INGRESOS 9 Factura -".($numFactura)."- no trobada, concepte =>".$concepteAux."<= => id: ".$id.", anotació: ".$num.", de compte: ".$compteD." a compte: ".$compteH." (".$clubs[$compteH]['nom']);
								//echo "ERROR REBUTS/INGRESOS 9 Factura -".($numFactura)."- no trobada, concepte =>".$concepteAux."<= => id: ".$id.", anotació: ".$num.", de compte: ".$compteD." a compte: ".$compteH." (".$clubs[$compteH]['nom'] .")<br/>";
								
								// S'ha fet l'ingrés la comanda encara no ha arribat, desar a un array
								/*$facturesRebutsPendents[$numFactura] =  array('id' => $id, 'num' => $num, 'compte' => $compteD, 'factura' => $numFactura,
																			'data' => $data, 'import' => $import, 'concepte' => $concepteAux, 
																			'rebut' => $numRebut, 'datapagament' => $datapagament, 'tipuspagament' => $tipuspagament,
																			'club' => $clubs[$compteH]['codi'], 'comandes' => $comandesIdPerActualitzar, 
																			'comentari' => $comentari, 'dadespagament' => $dadespagament );
															 	
								return;*/
								
								// Ingrés a compte
								error_log($id." ".$num."INFO 11 ingrés a compte nou sense factura ".$numFactura." => import ".$import." => ".$compteD);
								echo $id." ".$num."INFO 11 ingrés a compte nou sense factura  ".$numFactura." => import ".$import." ==> ".$compteD."<br/>";
								
								if (is_numeric($numFactura)) $numfacturesNoexistents[] = $numFactura*1;
							} else {
								// Count no funciona bé
								//$em->getConnection()->executeUpdate("UPDATE m_factures SET datapagament = '".$data."' WHERE id = ".$factExistent['id']);
									
								$comanda = $this->consultarComanda($factExistent['id']);			
								//$statement = $em->getConnection()->executeQuery("SELECT * FROM m_comandes WHERE factura = ".$factExistent['id']);
								//$comanda = $statement->fetch();
									
								if ($comanda == null) {
									echo json_encode($factExistent);
									error_log($id." ".$num."ERROR REBUTS/INGRESOS 11 Comanda no trobada => ".$compteD. " ".$numFactura);
									echo $id." ".$num."ERROR REBUTS/INGRESOS 11 Comanda no trobada => ".$compteD. " ".$numFactura."<br/>";
									return;
								}
								$comandesIdPerActualitzar[] = $comanda['id'];	
								
								error_log($id." ".$num."INFO 12 update rebut comanda ".$comanda['id']." factura ".$numFactura." => import ".$import." => ".$compteD);
								echo $id." ".$num."INFO 12 update rebut comanda  ".$comanda['id']." factura ".$numFactura." => import ".$import." ==> ".$compteD."<br/>";
							}
							
							//$comandesIdPerActualitzar[] = $comanda['id'];
						}

						$anyRebut = substr($datapagament, 0, 4);
						$rebutExistent = $this->consultarRebut($numRebut, $anyRebut);

	if ($persist == false) return;	
						if ($rebutExistent == null) $rebutId = $this->inserirRebut($datapagament, $numRebut, $import, $tipuspagament, $clubs[$compteH]['codi'], $dataentrada, $comentari, $dadespagament);
						else $rebutId = $rebutExistent['id'];
						
						foreach ($comandesIdPerActualitzar as  $comandaId) {	
								
							$query = "UPDATE m_comandes SET rebut = ".$rebutId." WHERE id = ". $comandaId;
							$em->getConnection()->exec( $query );
						}

						foreach ($numfacturesNoexistents as $num) {
							// Factures que encara no existeixen. Afegir rebut
							$facturesRebutsPendents[$num] = $rebutId;
						}
						
					} else {
						// cercar ingrés
						
						$concepteAux = strtolower( $altres['D'][0]['concepte'] );	// 	Ingres a compte saldo Club
						$pos1 = strpos($concepteAux, 'ingres');
						$pos2 = strpos($concepteAux, 'ingrés');
						$pos3 = strpos($concepteAux, 'compte');
						$pos4 = strpos($concepteAux, 'liquida');
						
						if ($pos1 === false &&
							$pos2 === false &&
							$pos3 === false &&
							$pos4 === false) {
								error_log($id." ".$num."WARNING REBUTS/INGRESOS 5 no detectat ni factura ni ingrés => ".$compteD." => ".$concepteAux);
								if ($warning == true) echo $id." ".$num."WARNING REBUTS/INGRESOS 5 no detectat ni factura ni ingrés => ".$compteD." => ".$concepteAux."<br/>";
						}
							
						error_log($id." ".$num."INFO 5 ingrés a compte nou ".$numFactura." => import ".$import." => ".$compteD);
						echo $id." ".$num."INFO 5 ingrés a compte nou ".$numFactura." => import ".$import." ==> ".$compteD."<br/>";
		if ($persist == false) return;
						$anyRebut = substr($datapagament, 0, 4);
						$rebutExistent = $this->consultarRebut($numRebut, $anyRebut);
						
						if ($rebutExistent == null)  $rebutId = $this->inserirRebut($datapagament, $numRebut, $import, $tipuspagament, $clubs[$compteH]['codi'], $dataentrada, $comentari, $dadespagament);
						else $rebutId = $rebutExistent['id'];
					}
					$em->getConnection()->commit();
					$em->getConnection()->beginTransaction(); // suspend auto-commit
						
				}
		
			}	
		} else {
			// Varis o ningún 'D'
			/*if (count($altres['D']) == 0) error_log("CAP 'D' = >".$altres['H'][0]['num']." -".count($altres['D'])."-|-".count($altres['H'])."- " );
			else error_log("VARIS = >".$altres['D'][0]['num']." -".count($altres['D'])."-|-".count($altres['H'])."- " );*/
			echo "Varis o ningún 'D' ".$num."<br/>";	
		}
	}
	
	private function consultarFactura($numFactura, $anyFactura) {
		//error_log("****** consultar factura .".$numFactura."/".$anyFactura);
		$em = $this->getDoctrine()->getManager();
		$statement = $em->getConnection()->executeQuery("SELECT * FROM m_factures WHERE num = ".($numFactura*1)." AND YEAR(datafactura) = ".$anyFactura. " ORDER BY id DESC");
		$factExistent = $statement->fetch();
		return $factExistent;
	}

	private function consultarRebut($numRebut, $anyRebut) {
		$em = $this->getDoctrine()->getManager();
		$statement = $em->getConnection()->executeQuery("SELECT * FROM m_rebuts WHERE num = ".($numRebut*1)." AND YEAR(datapagament) = ".$anyRebut. " ORDER BY id DESC");
		$rebutExistent = $statement->fetch();
		return $rebutExistent;
	}

	private function consultarComanda($idFactura) {
		//error_log("****** consultar factura .".$numFactura."/".$anyFactura);
		$em = $this->getDoctrine()->getManager();
		$statement = $em->getConnection()->executeQuery("SELECT * FROM m_comandes WHERE factura = ".$idFactura);
		$comandaExistent = $statement->fetch();
		return $comandaExistent;
	}
	
	
	private function inserirFactura($data, $numFactura, $import, $concepte = '') {
		echo "****************************** inserir factura ".$numFactura." => import ".$import." ==> data ".$data."<br/>";
		
		$em = $this->getDoctrine()->getManager();
		
		$query = "INSERT INTO m_factures (datafactura, num, import, concepte, dataentrada, comptabilitat) VALUES ";
		$query .= "('".$data."',".$numFactura.",".$import.",'".$concepte."'";
		$query .= ",'".$data."', 1)";
						
		$em->getConnection()->exec( $query );
						
		$facturaId = $em->getConnection()->lastInsertId();
		
		$em->getConnection()->commit();
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		echo "****************************** factura id ".$facturaId."<br/>";
		//error_log("****** inserir factura .".$numFactura." => ".$facturaId);		
		return $facturaId;
		
	}
	
	private function inserirRebut($datapagament, $numRebut, $import, $tipuspagament, $codiClub, $dataentrada, $comentari = '', $dadespagament = null) {
		echo "****************************** inserir rebut ".$numRebut." => import ".$import." ==> data pagament ".$datapagament."<br/>";	
			
		$em = $this->getDoctrine()->getManager();
		
		$query = "INSERT INTO m_rebuts (datapagament, num, import, dadespagament, tipuspagament, comentari, dataentrada, club, comptabilitat) VALUES ";
		$query .= "('".$datapagament."',".$numRebut.",".$import;
		$query .= ", ".($dadespagament==null?"NULL":"'".$dadespagament."'").",".$tipuspagament.",'".$comentari."','".$dataentrada."'";
		$query .= ",'".$codiClub."',1)";
		
		$em->getConnection()->exec( $query );
		$rebutId = $em->getConnection()->lastInsertId();
		
		//error_log("****** inserir rebut .".$numRebut." => ".$rebutId);
		echo "****************************** rebut id ".$rebutId."<br/>";
		return $rebutId; 
	}
	
	private function updateComandaNumFactura($comandaId, $facturaId) {
		echo "****************************** update comanda id  ".$comandaId." => num factura id ".$facturaId."<br/>";	
		
		$em = $this->getDoctrine()->getManager();
		$comanda = $em->getRepository('FecdasBundle:EntityComanda')->find($comandaId);
		$detalls = $comanda->getDetallsAcumulats();
		//$detalls = json_encode($detalls, JSON_UNESCAPED_UNICODE); // Desar estat detalls a la factura
		$detalls = json_encode($detalls); // Desar estat detalls a la factura
		
		$factura = $em->getRepository('FecdasBundle:EntityFactura')->find($facturaId);
		$factura->setDetalls($detalls);
		//error_log("****** update comanda .".$comandaId."-".$facturaId);
		$em->flush();
	}

	private function updateComandaNumRebut($comandaId, $rebutId) {
		echo "****************************** update comanda id  ".$comandaId." => num rebut id ".$rebutId."<br/>";	
		
		$em = $this->getDoctrine()->getManager();
		$comanda = $em->getRepository('FecdasBundle:EntityComanda')->find($comandaId);
		
		$rebut = $em->getRepository('FecdasBundle:EntityRebut')->find($rebutId);
		$comanda->setRebut($rebut);
		//error_log("****** update comanda .".$comandaId."-".$facturaId);
		$em->flush(); 
	}

	
	private function inserirComandaAmbDetalls($data, $codiClub, $maxnums, $descripcio = '', $rebutId = 0, $facturaId = 0, $detalls = array(), $productes) {
		// Insertar comanda
		echo "****************************** inserir comanda amb detalls factura id ".$facturaId." => rebut id ".$rebutId." ==> data ".$data."<br/>";
		
		$em = $this->getDoctrine()->getManager();
		
		$query = "INSERT INTO m_comandes (comentaris, dataentrada, databaixa, club, num, rebut,factura, tipuscomanda) VALUES ";
		$query .= "('".$descripcio."', '".$data."'";
		$query .= ",NULL,'".$codiClub."', 0 ";
		$query .= ", ".($rebutId==0?"NULL":$rebutId).", ".($facturaId==0?"NULL":$facturaId).",'A')";
				
		$maxnums['maxnumcomanda']++;
					
		$em->getConnection()->exec( $query );
						
		$comandaId = $em->getConnection()->lastInsertId();
						
		for ($i = 0; $i < count($detalls); $i++) {
			// Insertar detall
			$compteH = $detalls[$i]['compte'];
			$producte = $productes[$compteH];
			$import = $detalls[$i]['importapunt'];
			
			$total = $producte['preu']==0 ? 1 : round($detalls[$i]['importapunt']/$producte['preu']);
			$anota = $total.'x'.str_replace("'","''",$producte['descripcio']);
							
			$preuunitat = ($total == 0?$import:round($import/$total,2));
						
						
			$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada) VALUES ";
			$query .= "(".$comandaId.",".$producte['id'].",".$total.",".$preuunitat.",NULL, 0, '".$anota."',";
			$query .= "'".$data."')"; 
						
			$em->getConnection()->exec( $query );
		}
		
		$query = "UPDATE m_comandes SET num = ".$comandaId." WHERE id = ". $comandaId;		
		
		$em->getConnection()->exec( $query );
		
		$em->getConnection()->commit();
		$em->getConnection()->beginTransaction(); // suspend auto-commit
		//error_log("****** inserir comanda amb detalls .".$comandaId." => ".count($detalls));
		
		echo "****************************** comanda id ".$comandaId."<br/>";
		return $comandaId;
	}				
	
	private function extraerFactures($concepte, $tipus) {
		$numFactura = 'NA';
		if ($tipus == 'R') { // Rebut				
			$pos = strpos($concepte, 'factura');
			if ($pos === false) {
				$pos = strpos($concepte, 'fra');
				if ($pos === false) {
					$pos = strpos($concepte, 'fw');
					if ($pos === false) {
						$pos = strpos($concepte, 'Factura: 1117-1115/2015');
						if ($pos === false) {
							$numFactura = substr($concepte, 3, 4);  // F. 1234/2014
						} else {
							 return array(1117, 1115);
						}
					}
					else $numFactura = substr($concepte, 3, 5);
				}
				else $numFactura = substr($concepte, 5, 4);
			}
			else $numFactura = substr($concepte, 9, 5);
			
			if (is_numeric($numFactura)) return array( $numFactura );  // Trobada
				
			// factura: 1234-1388-1448/2015
			$concepte = str_replace('factura:', '', $concepte);
			$concepte = str_replace('factura', '', $concepte);
			$concepte = str_replace('fra.', '', $concepte);
			$concepte = str_replace('fra', '', $concepte);
			$concepte = str_replace('/2015', '', $concepte);
			$concepte = str_replace('/2014', '', $concepte);
			$concepte = str_replace('/201', '', $concepte);
			$concepte = str_replace('/', '', $concepte);
			$concepte = str_replace('- 150´-', '', $concepte);
			
			$testArrayFactures = explode("-", trim($concepte));
			
			foreach ($testArrayFactures as $key => $numFactura) {
				if (!is_numeric( trim($numFactura) )) return null;
			}
			return $testArrayFactures;
				
		}
			
		// SELECT * FROM `apunts_2015` WHERE `compte` BETWEEN 4310000 AND 4310999 AND dh = 'D'
		// W-F0063_ 10636110MAS 1AFC_  o F0019_1AFC_
		if ($tipus == 'F') { // Factura

			$pos = strpos($concepte, 'w-f');
			if ($pos === false) {
				$pos = strpos($concepte, 'fra');
				if ($pos === false) { 
					$pos = strpos($concepte, 'pago s/fra. ');
					if ($pos === false) { 
						$pos = strpos($concepte, 'retroces pago f.');
						if ($pos === false) {
							$pos = strpos($concepte, 'F1217_1K3E');
							if ($pos === false) { 
								$numFactura = substr($concepte, 1, 4);
							} else {
								return 1217;
							} 
						}
						else {
							$numFactura = substr($concepte, 16, 4);
						}
					}
					else {
						$numFactura = substr($concepte, $pos, 2);
					}
				}	
				else $numFactura = substr($concepte, 5, 4);
			}
			else $numFactura = substr($concepte, 3, 4);
		}				
		if (!is_numeric($numFactura)) return $concepte."-".$numFactura."-";
		return trim($numFactura);
	}
				
	
	
	private function insertComandaDuplicat($clubs, $duplicat, &$maxnums, $flush = false) {
	
		$em = $this->getDoctrine()->getManager();
	
		$preu = $duplicat['epreu'];
		$iva = $duplicat['eiva'];
		
		$desc = str_replace("'","''",$duplicat['pdescripcio']);
		$observa = str_replace("'","''",$duplicat['observacions']);
	
		$query = "INSERT INTO m_comandes (id, comentaris, dataentrada, databaixa, club, num, tipuscomanda) VALUES ";
		$query .= "(".$duplicat['id'].", '".$desc."','".$duplicat['datapeticio']."'";
		$query .= ",".($duplicat['databaixadel']==null?"NULL":"'".$duplicat['databaixadel']."'").",'".$duplicat['clubdel']."',".$duplicat['id'].",'D')";
	
		$em->getConnection()->exec( $query );
	
		$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada) VALUES ";
		$query .= "(".$duplicat['id'].",".$duplicat['pid'].", 1,".$preu.",".($iva!=null?$iva:"NULL").", 0, ".($duplicat['observacions']==null?"NULL":"'".$observa."'").",";
		$query .= "'".$duplicat['datapeticio']."')";

		$maxnums['maxnumcomanda']++;

		$em->getConnection()->exec( $query );
	
		if ($flush) {
			$em->getConnection()->commit();
			$em->getConnection()->beginTransaction(); // suspend auto-commit
		}
	}
	
	private function insertComandaParte($clubs, $partes, &$maxnums, $flush = false) {
		$em = $this->getDoctrine()->getManager();
	
		if (isset($partes[0])) {
			$parte = $partes[0];
			$desc = $parte['total'].'x'.str_replace("'","''",$parte['tdesc']);
			$rebutId = 0;
			$facturaId = 0;
				
			if ($parte['datapagament'] != null) {
				// Rebut ==> Insert des de apunts
				/*$comentari = str_replace("'","''",$parte['comentari']);
	
				$tipuspagament = null;
				if ($parte['estatpagament'] == 'TPV OK' || $parte['estatpagament'] == 'TPV CORRECCIO')  $tipuspagament = BaseController::TIPUS_PAGAMENT_TPV;
				if ($parte['estatpagament'] == 'METALLIC GES' || $parte['estatpagament'] == 'METALLIC WEB')  $tipuspagament = BaseController::TIPUS_PAGAMENT_CASH;
				if ($parte['estatpagament'] == 'TRANS WEB') $tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_LAIETANIA;
				if ($parte['estatpagament'] == 'TRANS GES') $tipuspagament = BaseController::TIPUS_PAGAMENT_TRANS_SARDENYA;
	
				$query = "INSERT INTO m_rebuts (datapagament, num, import, dadespagament, tipuspagament, comentari, dataentrada) VALUES ";
				$query .= "('".$parte['datapagament']."',".$maxnums['maxnumrebut'].",".$parte['importpagament'];
				$query .= ", '".$parte['dadespagament']."',".$tipuspagament.",'".$comentari."','".$parte['dataentradadel']."')";
	
				$maxnums['maxnumrebut']++;
					
				$em->getConnection()->exec( $query );
	
				$rebutId = $em->getConnection()->lastInsertId();
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit*/
			}
				
			if ($parte['numfactura'] != null) {
				// Factura
				$textLlista = 'Llista '.BaseController::PREFIX_ALBARA_LLICENCIES.str_pad($parte['id'],6,'0',STR_PAD_LEFT).". ".$desc;

				$codi = $parte['clubdel'];
				if (isset( $clubs[ $codi ] ) && isset ( $clubs[ $codi ]['nom'] ) ) $textLlista .= " ".$clubs[ $codi ]['nom'];
				
				$factArray = explode("/",$parte['numfactura']);
	
	
				$datafactura = ($parte['datafacturacio'] == '' || 
								$parte['datafacturacio'] == null ||
								substr($parte['datafacturacio'], 0, 4) != substr($parte['dataalta'], 0, 4)?
								$parte['dataalta']:$parte['datafacturacio']); // canvi d'any agafar dataalta
				
	
				$query = "INSERT INTO m_factures (datafactura, num, import, concepte, dataentrada, comptabilitat) VALUES ";
				$query .= "('".$datafactura."',".$factArray[0].",".$parte['importparte'].",'".$textLlista."'";
				$query .= ",'".$parte['dataentradadel']."', 1)";
	
				$em->getConnection()->exec( $query );
	
				$facturaId = $em->getConnection()->lastInsertId();
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
				
			$query = "INSERT INTO m_comandes (id, comentaris, dataentrada, databaixa, club, num, rebut,factura, tipuscomanda) VALUES ";
			$query .= "(".$parte['id'].", '".$desc."', '".$parte['dataentradadel']."'";
			//$query .= ",".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").",'".$parte['clubdel']."',".$maxnums['maxnumcomanda'];
			$query .= ",".($parte['databaixadel']==null?"NULL":"'".$parte['databaixadel']."'").",'".$parte['clubdel']."',".$parte['id'];
			$query .= ", ".($rebutId==0?"NULL":$rebutId).", ".($facturaId==0?"NULL":$facturaId).",'P')";
	
			$em->getConnection()->exec( $query );
			
			$totalComanda = 0;
			
			foreach ($partes as $parte) {
				$total 	= $parte['total'];
				$anota 	= $total.'x'.$parte['ccat'];
				$preu 	= (isset($parte['preu'])?$parte['preu']:0);
				$iva	= (isset($parte['iva'])?$parte['iva']:0);

				$totalComanda += $total * $preu * (1 + $iva);
				
				$query = "INSERT INTO m_comandadetalls (comanda, producte, unitats, preuunitat, ivaunitat, descomptedetall, anotacions, dataentrada) VALUES ";
				/*$query .= "(".$parte['id'].",".$parte['cpro'].",".$total.", ".$preu.", ".($iva > 0?$iva:"NULL") .", 0, '".$anota."',";*/
				$query .= "(".$parte['id'].",".$parte['cpro'].",".$total.", ".$preu.", NULL, 0, '".$anota."',";
				$query .= "'".$parte['dataentradadel']."')";
			
			
				$em->getConnection()->exec( $query );
			}
			
			if ($parte['importparte'] == 0) {
				// Els partes des de gestor tenen a 0 aquest import
				$query = "UPDATE m_factures SET import = ".$totalComanda." ";
				$query .= " WHERE id = ".$facturaId.";";
				$em->getConnection()->exec( $query );
			}
			
			$maxnums['maxnumcomanda']++;
			 
			if ($flush) {
				$em->getConnection()->commit();
				$em->getConnection()->beginTransaction(); // suspend auto-commit
			}
		}
	
		
	}
}
