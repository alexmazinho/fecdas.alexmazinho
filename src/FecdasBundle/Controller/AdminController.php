<?php 
namespace FecdasBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;

use FecdasBundle\Classes\CSVReader;

use FecdasBundle\Classes\MysqlYear;
use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Entity\EntityClub;


class AdminController extends BaseController {
	
	public function changeroleAction(Request $request) {
		if (!$this->isCurrentAdmin()) return new Response(""); 
			
		// Canviar Club Administrador	
		if ($request->query->has('roleclub')) $this->get('session')->set('roleclub', $request->query->get('roleclub'));
		
		return new Response("");
	}
	
	public function imprimircarnetsAction(Request $request) {
		// Formulari per imprimir carnet CMAS
		 
		if ($this->isAuthenticated() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		/* De moment administradors */
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_home'));
				 
		$em = $this->getDoctrine()->getManager();
		
		$current = $this->getCurrentDate();
		$emissio = $current; 
		$caducitat = $this->getCurrentDate();
		$caducitat->add(new \DateInterval('P1Y'));
		
		if ($request->getMethod() == 'POST') $formdata = $request->request->get('form');
		else $formdata = array('nom' => '',	'cognoms' => '', 'federat' => '', 
							'nif' => '', 'dataemissio' => $current, 'datacaducitat' => $caducitat, 
							'num' => '', 'logo' => '', 'extension' => '');
		
		// Crear formulari
		$formBuilder = $this->createFormBuilder($formdata)->add('nom', 'text', array('required' 	=> false, 'data' =>  mb_strtoupper(mb_substr($formdata['nom'], 0, 1)).mb_substr($formdata['nom'], 1)));
					
		$formBuilder->add('cognoms', 'text', array('required' => false, 'data' => mb_strtoupper($formdata['cognoms'], 'UTF-8')));
					
		$formBuilder->add('federat', 'hidden', array('data' => $formdata['federat']));  // Cerca federat
					
		$formBuilder->add('nif', 'text', array('required' => false, 'data' => $formdata['nif']));
			
		$formBuilder->add('estranger', 'checkbox', array('required'  => false ));
				
		$formBuilder->add('dataemissio', 'datetime', array(
								'required' 		=> false,
								'mapped'		=> false,
								'widget' 		=> 'single_text',
								'input' 		=> 'datetime',
								'empty_value' 	=> false,
								'format' 		=> 'dd/MM/yyyy',
								'data'			=> $emissio
						));	
					
		$formBuilder->add('datacaducitat', 'datetime', array(
								'required' 		=> false,
								'mapped'		=> false,
								'widget' 		=> 'single_text',
								'input' 		=> 'datetime',
								'empty_value' 	=> false,
								'format' 		=> 'dd/MM/yyyy',
								'data'			=> $caducitat
						));	
					
		$formBuilder->add('num', 'text', array('required' => false, 'data' => $formdata['num'])); // Número de certificat
					
		$atributs = array('accept' => '.csv');
		$formBuilder->add('importfile', 'file', array('attr' => $atributs, 'required' => false));
					
		/*$formBuilder->add('logo', 'file', array('required' 	=> false,'attr' => array('accept' => 'image/*')));*/
			
		$form = $formBuilder->getForm();
		
		$carnets = array();
		
		if ($request->getMethod() == 'POST') {
			try {
				
				// Carrega del fitxer
				$form->handleRequest($request);
				 	
				$file = $form->get('importfile')->getData();
			
				if ($file == null) throw new \Exception('Cal escollir un fitxer');
				
				if (!$file->isValid()) throw new \Exception('La mida màxima del fitxer és ' . $file->getMaxFilesize());
					
				if ($file->guessExtension() != 'txt'
					|| $file->getMimeType() != 'text/plain' ) throw new \Exception('El fitxer no té el format correcte');
					
				$temppath = $file->getPath()."/".$file->getFileName();
					
				$carnets = $this->importCarnetsCSVData($temppath);					
					
					
				/*$logo = $form['logo']->getData();
				
				if ($logo == null) throw new \Exception('Cal escollir el logo del club ' );
				
				if (!$logo->isValid()) throw new \Exception('La mida màxima del fitxer és ' . $logo->getMaxFilesize());
				
				$temppath = $logo->getPath()."/".$logo->getFileName();
				
				$tempname = $this->getCurrentDate()->format('Ymd')."_".$formdata['nif']."_".$logo->getClientOriginalName();
				
				$extension = $logo->guessExtension();
				if (!$extension) $extension = 'jpg';// extension cannot be guessed
				$formdata['extension'] = $extension;
				
				// Copy file for future confirmation 
				$logo->move($this->getTempUploadDir(), $tempname);
				// Generate URL to send CSV confirmation 

				$formdata['logo'] = $tempname;
				*/	
				
				$this->logEntryAuth('CARNETS POST OK',	count($carnets));
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				$this->logEntryAuth('CARNETS POST ERROR',	$e->getMessage());
				$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
			}
			
			//return $this->redirect($this->generateUrl('FecdasBundle_imprimircarnets', array('form' => $form, 'carnets' => $carnets)));
				
		} else {
			$this->logEntryAuth('CARNETS VIEW',	'');
		}
		
		return $this->render('FecdasBundle:Admin:imprimircarnets.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'carnets' => $carnets)));

	}
	
	private function importCarnetsCSVData ($file) {
		$reader = new CSVReader();
		$reader->setCsv($file);
		$reader->readLayoutFromFirstRow();
		//$reader->setLayout(array('nom', 'cognoms', 'dni', 'estranger', 'expedicio', 'emissio', 'caducitat'));
		
		$carnets = array();
		
		$fila = 0;
		
		//$header = implode($reader->getLayout());

		while($reader->process()) {
			$fila++;
				
			$row = $reader->getRow();
			//our logic here
				
			if (!isset($row['nom']) || trim($row['nom']) == '') throw new \Exception('Falta el camp \'nom\'');
			if (!isset($row['cognoms'])) throw new \Exception('Falta el camp \'cognoms\'');
			if (!isset($row['dni'])) throw new \Exception('Falta el camp \'dni\'');
			if (!isset($row['estranger'])) throw new \Exception('Falta el camp \'estranger\'');
			
			if (mb_strtoupper($row['estranger'], "utf-8") != 'S' && mb_strtoupper($row['estranger'], "utf-8") != 'N') throw new \Exception('Al camp \'estranger\' cal indicar \'S\' o \'N\'');
				
			if (!isset($row['expedicio'])) throw new \Exception('Falta el camp \'expedicio\'');
			if (!isset($row['emissio'])) throw new \Exception('Falta el camp \'emissio\'');
			if (!isset($row['caducitat'])) throw new \Exception('Falta el camp \'caducitat\'');

			$emissio = \DateTime::createFromFormat('d/m/Y', $row['emissio']);
			if ($emissio == false)  throw new \Exception('Format incorrecte de la data d\'emissió. Cal indicar el formar \'dd/mm/YYYY\'');
				
			$caducitat = \DateTime::createFromFormat('d/m/Y', $row['caducitat']);
			if ($caducitat == false)  throw new \Exception('Format incorrecte de la data de caducita. Cal indicar el formar \'dd/mm/YYYY\'');

			$row['nom'] = mb_convert_case($row['nom'], MB_CASE_TITLE, "utf-8");
			$row['cognoms'] = mb_convert_case($row['cognoms'], MB_CASE_TITLE, "utf-8"); //mb_strtoupper($row['cognoms'], "utf-8")		
			$row['dni'] = mb_convert_case($row['dni'], MB_CASE_UPPER, "utf-8");
			
			$carnets[] = $row;
				
			$estranger = mb_strtoupper($row['estranger'], "utf-8") == 'S';
				
			if ($estranger == false) {
				/* Només validar DNI nacionalitat espanyola */
				$dnivalidar = $row['dni'];
				/* Tractament fills sense dni, prefix M o P + el dni del progenitor */
				if ( substr ($dnivalidar, 0, 1) == 'P' or substr ($dnivalidar, 0, 1) == 'M' ) $dnivalidar = substr ($dnivalidar, 1,  strlen($dnivalidar) - 1);
						
				if (BaseController::esDNIvalid($dnivalidar) != true) throw new \Exception('El DNI '.$dnivalidar.' és incorrecte');
			}
	 	} 
		
		return 	$carnets;	 
	}
	
	public function recentsAction(Request $request) {
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));*/
		
		$em = $this->getDoctrine()->getManager();
	
		$states = explode(";", self::CLUBS_STATES);
		$defaultEstat = self::TOTS_CLUBS_DEFAULT_STATE; // Tots normal
		if ($this->get('session')->get('username', '') == self::MAIL_FACTURACIO)  $defaultEstat = self::CLUBS_DEFAULT_STATE; // Diferits Remei

		// Cerca
		$currentBaixa = false; // Inclou Baixes
		if ($request->query->has('baixa') && $request->query->get('baixa') == 1) $currentBaixa = true;
		$currentNoPagat = false;// No pagats
		if ($request->query->has('nopagat') && $request->query->get('nopagat') == 1) $currentNoPagat = true;
		$currentNoImpres = false;// No impres
		if ($request->query->has('noimpres') && $request->query->get('noimpres') == 1) $currentNoImpres = true;
		$currentCompta = false;// Pendents compta
		if ($request->query->has('compta') && $request->query->get('compta') == 1) $currentCompta = true;


		$currentNumfactura = $request->query->get('numfactura', '');
		$currentNumrebut = $request->query->get('numrebut', '');
		$currentAnyfactura = $request->query->get('anyfactura', '');
		$currentAnyrebut = $request->query->get('anyrebut', '');
		
		//$currentClub = null;
		$currentClub = $em->getRepository('FecdasBundle:EntityClub')->find($request->query->get('clubs', ''));
		
		$currentEstat = $request->query->get('estat', $defaultEstat);
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'p.dataentrada');
		$direction = $request->query->get('direction', 'asc');
		
		if ($request->getMethod() == 'POST') {
			// Criteris de cerca.Desactivat JQuery  
			/*if ($request->request->has('form')) { 
				
				$formdata = $request->request->get('form');
				
				$page = 1; // Submit sempre comença per 1
				$sort = $formdata['sort'];
				$direction = $formdata['direction'];
				
				if (isset($formdata['clubs'])) $currentClub = $em->getRepository('FecdasBundle:EntityClub')->find($formdata['clubs']);
				if (isset($formdata['estat'])) $currentEstat = $formdata['estat'];
				if (isset($formdata['nopagat'])) $currentNoPagat = true; // Tots
				else $currentNoPagat = false;
				if (isset($formdata['baixa'])) $currentBaixa = true;
				else $currentBaixa = false;
				if (isset($formdata['nosincro'])) $currentNoSincro = true;
				else $currentNoSincro = false;

				
				
			}*/
			$this->logEntryAuth('ADMIN PARTES POST', "club: " . ($currentClub==null)?"":$currentClub->getNom() . " filtre estat: " . $states[$currentEstat] .
					" factura " .$currentNumfactura . " rebut " .$currentNumrebut . " pagament: " . $currentNoPagat . " baixa: " . $currentBaixa );
		} else {
			$this->logEntryAuth('ADMIN PARTES');
		}
		
		$formBuilder = $this->createFormBuilder();
		
		$this->addClubsActiusForm($formBuilder, $currentClub);
		
		
		$formBuilder->add('estat', 'choice', array(
				'choices'   => $states,
				'preferred_choices' => array($defaultEstat),  // Estat per defecte sempre 
				'data' => $currentEstat
		));
		
		$current = date('Y');
		$formBuilder->add('numfactura', 'text', array(
					'required'  => false,
					'data' => $currentNumfactura,
				));
		$formBuilder->add('anyfactura', 'choice', array(
				'choices'   => array($current => $current, $current-1 => $current-1),
				'preferred_choices' => array($current),  // Any actual i anterior 
				'data' => $currentAnyfactura
		));
		
		$formBuilder->add('numrebut', 'text', array(
					'required'  => false,
					'data' => $currentNumrebut,
				));
		$formBuilder->add('anyrebut', 'choice', array(
				'choices'   => array($current => $current, $current-1 => $current-1),
				'preferred_choices' => array($current),  // Any actual i anterior 
				'data' => $currentAnyrebut
		));
		
		$formBuilder->add('nopagat', 'checkbox', array(
					'required'  => false,
					'data' => $currentNoPagat,
				));
		$formBuilder->add('noimpres', 'checkbox', array(
					'required'  => false,
					'data' => $currentNoImpres,
				));
		$formBuilder->add('baixa', 'checkbox', array(
    				'required'  => false,
					'data' => $currentBaixa,
				));
		$formBuilder->add('compta', 'checkbox', array(
    				'required'  => false,
					'data' => $currentCompta,
				));
		$form = $formBuilder->getForm();
		
		/*
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityParte p JOIN p.tipus t JOIN p.club c JOIN c.estat e ";
		$strQuery .= " LEFT JOIN p.rebut r LEFT JOIN p.factura f WHERE ";
		$strQuery .= " ((t.es365 = 0 AND p.dataalta >= :ininormal) OR ";
		$strQuery .= " (t.es365 = 1 AND p.dataalta >= :ini365))";

		
		if ($currentNumrebut == '' && $currentNumfactura == '') {
			// Dates normals
			$inianual = $this->getSQLIniciAnual();
			$ini365 = $this->getSQLInici365();
		} else {
			// dates dels anys escollits a les factures / rebuts
			$inianual = min($currentAnyrebut, $currentAnyfactura).'-01-01 00:00:00';
			$ini365 = $inianual;
		
			if ($currentNumrebut != '') $strQuery .= " AND r.num = :numrebut ";
			if ($currentNumfactura != '') $strQuery .= " AND f.num = :numfactura ";
		}
		
		if ($currentClub != null) $strQuery .= " AND p.club = '" .$currentClub->getCodi() . "' "	;
		if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $strQuery .= " AND e.descripcio = :filtreestat ";
		
		
		if ($currentBaixa == false) $strQuery .= " AND p.databaixa IS NULL ";
		if ($currentNoPagat == true) $strQuery .= " AND p.rebut IS NULL ";
		if ($currentNoImpres == true) $strQuery .= " AND (p.impres IS NULL OR p.impres = 0) AND p.pendent = 0 ";
		if ($currentCompta == true) $strQuery .= " AND f.comptabilitat <> 1 ";
		*/
		/* Quan es sincronitza es posa la data modificació a NULL de partes i llicències (No de persones que funcionen amb el check validat). 
		 * Els canvis des del gestor també deixen la data a NULL per detectar canvis del web que calgui sincronitzar */ 
		//if ($currentNoSincro == true) $strQuery .= " AND (p.idparte_access IS NULL OR (p.idparte_access IS NOT NULL AND p.datamodificacio IS NOT NULL) ) ";

		/*$strQuery .= " ORDER BY ".$sort; 

		$query = $em->createQuery($strQuery)
			->setParameter('ininormal', $inianual)
			->setParameter('ini365', $ini365);
		

		if ($currentNumrebut == '' && $currentNumfactura == '') {
			// Dates normals
		} else {
			// dates dels anys escollits a les factures / rebuts
			//if ($currentNumrebut == true) $partesrecents->setParam('numrebut',$currentNumrebut);
			//if ($currentNumfactura == true) $partesrecents->setParam('numfactura',$currentNumfactura);
			if ($currentNumrebut == true) $query->setParameter('numrebut',$currentNumrebut);
			if ($currentNumfactura == true) $query->setParameter('numfactura',$currentNumfactura);
		}
		*/
		
		/*if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $query->setParameter('filtreestat', $states[$currentEstat]);
	
		// Paràmetres URL sort i pagination 
		if ($currentClub != null) $partesrecents->setParam('clubs',$currentClub->getCodi());
		if ($currentEstat != self::TOTS_CLUBS_DEFAULT_STATE) $partesrecents->setParam('estat',$currentEstat);
		
		if ($currentBaixa == true) $partesrecents->setParam('baixa',true);
		//if ($currentNoSincro == false) $partesrecents->setParam('nosincro',false);
		if ($currentNoPagat == true) $partesrecents->setParam('nopagat',true);
		if ($currentNoImpres == true) $partesrecents->setParam('noimpres',true);*/
				
		$sortparams = array('sort' => $sort,'direction' => $direction, 
							/*'numrebut' => $currentNumrebut, 'anyrebut' => $currentAnyrebut,
							'numfactura' => $currentNumfactura, 'anyfactura' => $currentAnyfactura,
							'estat' => $currentEstat, 'baixa' => true, 'nopagat' => true, 'noimpres' => 'true' */);

		$query = $this->consultaPartesRecents($currentClub, $currentEstat, $currentBaixa, 
											$currentNoPagat, $currentNoImpres, $currentCompta, 
											$currentNumfactura, $currentAnyfactura,
											$currentNumrebut, $currentAnyrebut, $sort.' '.$direction);

		$paginator  = $this->get('knp_paginator');
		$partesrecents = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		);
		
		$partesrecents->setParam('sortparams',$sortparams);
		
		return $this->render('FecdasBundle:Admin:recents.html.twig', 
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'partes' => $partesrecents,
						'sortparams' => $sortparams
				)));
	}
	
	public function consultaadminAction(Request $request) {
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$em = $this->getDoctrine()->getManager();
	
		// Afegir funció YEAR de mysql a Doctrine DQL
		$config = $em->getConfiguration();
		$config->addCustomDatetimeFunction('YEAR', 'FecdasBundle\Classes\MysqlYear');
	
		// GET OPCIONS DE FILTRE
		$action = $request->query->get('action', '');
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'l.id');
		$direction = $request->query->get('direction', 'asc');

		$clubs = $request->query->get('clubs', array()); // Per defecte sense filtre de clubs
		if ($clubs == '') $clubs = array();
		
		$activats = true;
		if ($request->query->has('activats') && $request->query->get('activats') == 0) $activats = false;
		
		$tipusparte = $request->query->get('tipusparte', array()); // Per defecte sense filtre de tipus
		if ($tipusparte == '') $tipusparte = array();
		
		$categoria = $request->query->get('categoria', array()); // Per defecte sense filtre de categoria
		if ($categoria == '') $categoria = array();

		$strDatainici = $request->query->get('datainici', '');
		if ($strDatainici == '') $datainici = \DateTime::createFromFormat('Y-m-d', date("Y") . "-01-01"); 
		else $datainici = \DateTime::createFromFormat('d/m/Y', $strDatainici);
		
		$strDatafinal = $request->query->get('datafinal', '');
		if ($strDatafinal == '') $datafinal = $this->getCurrentDate();
		else $datafinal = \DateTime::createFromFormat('d/m/Y', $strDatafinal); 
		
		$intervals = false;// Per defecte consulta periode
		if ($request->query->has('intervals') && $request->query->get('intervals') == 1) $intervals = true;
		
		$intervaldata = $request->query->get('intervaldata', 'M');

		$edats = false; 
		if ($request->query->has('edats') && $request->query->get('edats') == 1) $edats = true;
		
		$edatsdata = $request->query->get('edatsdata', '10');
		
		$groupclub = false;
		if ($request->query->has('groupclub') && $request->query->get('groupclub') == 1) $groupclub = true;
		
		$grouptipus = false;
		if ($request->query->has('grouptipus') && $request->query->get('grouptipus') == 1) $grouptipus = true;

		$groupcategoria = false;
		if ($request->query->has('groupcategoria') && $request->query->get('groupcategoria') == 1) $groupcategoria = true;
		
		$groupsexe = false;
		if ($request->query->has('groupsexe') && $request->query->get('groupsexe') == 1) $groupsexe = true;

		$groupmunicipi = false;
		if ($request->query->has('groupmunicipi') && $request->query->get('groupmunicipi') == 1) $groupmunicipi = true;

		$groupcomarca = false;
		if ($request->query->has('groupcomarca') && $request->query->get('groupcomarca') == 1) $groupcomarca = true;

		$groupprovincia = false;
		if ($request->query->has('groupprovincia') && $request->query->get('groupprovincia') == 1) $groupprovincia = true;

		$baixes = $request->query->get('baixes', '0');;

		$groupQuey = $intervals || $edats || $groupclub || $grouptipus || $groupcategoria || $groupsexe || $groupmunicipi || $groupcomarca || $groupprovincia;

		$queryparams = array ('action' => $action, 'clubs' => $clubs, 'activats' => $activats, 'tipusparte' => $tipusparte, 'categoria' => $categoria,
								'datainici' => $strDatainici, 'datafinal' => $strDatafinal, 'intervals' => ($intervals == true?1:0),
								'intervaldata' => ($intervals == true?$intervaldata:''), 'edats' => ($edats == true?1:0),
								'edatsdata' => ($edats == true?$edatsdata:''), 'groupclub' => ($groupclub == true?1:0),
								'grouptipus' => ($grouptipus == true?1:0),
								'groupcategoria' => ($groupcategoria == true?1:0), 'groupsexe' => ($groupsexe == true?1:0),
								'groupmunicipi' => ($groupmunicipi == true?1:0), 'groupcomarca' => ($groupcomarca == true?1:0),
								'groupprovincia' => ($groupprovincia == true?1:0),
								);


		
		$sortparams = array('sort' => $sort, 'direction' => $direction, 'inverse' => ($direction=='asc'?'desc':'asc') );
		$resultat = array();
		$params = array();
		$pageSize = 20; // limit
		$offset = ($page - 1) * $pageSize; 
		
		$agrupats = array();
		$colsHeaderIntervals = array();
		$campsHeaderAgrupats = array();
		$campsHeader = array (		'num' 			=> array('hidden' => false, 'nom' => 'Num.', 'width' => '60px', 'sort' => ''),
									'comandaid' 	=> array('hidden' => true, 	'nom' => 'Id Comanda', 'width' => '0', 'sort' => 'p.id'), 
									'comandanum' 	=> array('hidden' => false, 'nom' => 'Comanda', 'width' => '110px', 'sort' => 'p.num'),
									'club' 			=> array('hidden' => false, 'nom' => 'Club', 'width' => '150px', 'sort' => 'c.nom'),
									'tipus'			=> array('hidden' => false, 'nom' => 'Llicència', 'width' => '100px', 'sort' => 't.codi'),
									'dataalta' 		=> array('hidden' => false, 'nom' => 'Alta', 'width' => '80px', 'sort' => 'p.dataalta'),      
									'datacaducitat' => array('hidden' => false, 'nom' => 'Caduca', 'width' => '80px', 'sort' => 'p.dataalta'),
									'databaixa'		=> array('hidden' => false, 'nom' => 'Baixa', 'width' => '80px', 'sort' => 'p.databaixa'), 
									'categoria'		=> array('hidden' => false, 'nom' => 'Categoria', 'width' => '80px', 'sort' => 'l.categoria'), 
									'preu'			=> array('hidden' => false, 'nom' => 'Preu', 'width' => '60px', 'sort' => ''),
									'llicenciaid'	=> array('hidden' => true, 	'nom' => 'Id Llicència', 'width' => '0', 'sort' => 'l.id'),  					  	
					 				'dni'			=> array('hidden' => false, 'nom' => 'DNI', 'width' => '90px', 'sort' => 'e.dni'), 
					 				'estranger'		=> array('hidden' => false, 'nom' => 'Estra.?', 'width' => '60px', 'sort' => ''), 
					 				'nom'			=> array('hidden' => false, 'nom' => 'Nom', 'width' => '90px', 'sort' => 'e.nom'), 
					 				'cognoms'		=> array('hidden' => false, 'nom' => 'Cognoms', 'width' => '150px', 'sort' => 'e.cognoms'), 
					 				'naixement'		=> array('hidden' => false, 'nom' => 'D. Naix.', 'width' => '80px', 'sort' => 'e.datanaixement'), 
					 				'edat'			=> array('hidden' => false, 'nom' => 'Edat', 'width' => '50px', 'sort' => 'e.datanaixement'),	
					 				'sexe'			=> array('hidden' => false, 'nom' => 'Sexe', 'width' => '50px', 'sort' => 'e.sexe'), 
					 				'telefon1'		=> array('hidden' => false, 'nom' => 'Telf1', 'width' => '80px', 'sort' => 'e.telefon1'), 
					 				'telefon2'		=> array('hidden' => false, 'nom' => 'Telf2', 'width' => '80px', 'sort' => 'e.telefon2'), 
					 				'mail'			=> array('hidden' => false, 'nom' => 'eMail', 'width' => '170px', 'sort' => 'e.mail'),	
					 				'adreca'		=> array('hidden' => false, 'nom' => 'Adreça', 'width' => '200px', 'sort' => 'e.addradreca'), 
					 				'poblacio'		=> array('hidden' => false, 'nom' => 'Problació', 'width' => '150px', 'sort' => 'e.addrpob'), 
					 				'cp'			=> array('hidden' => false, 'nom' => 'CP', 'width' => '60px', 'sort' => 'e.addrcp'), 
					 				'comarca'		=> array('hidden' => false, 'nom' => 'Comarca', 'width' => '150px', 'sort' => 'e.addrcomarca'),
					 				'provincia'		=> array('hidden' => false, 'nom' => 'Província', 'width' => '100px', 'sort' => 'e.addrprovincia'), 
					 				'nacionalitat'	=> array('hidden' => false, 'nom' => 'Nacionalitat', 'width' => '50px', 'sort' => ''),
									);
		$total = 0;
		if ( $action == 'query' || $action == 'csv' ) {
			if ($groupQuey != true) {
				// PREPARAR CONSULTA SENSE AGRUPAR
				$strQuery = "SELECT p, t, l, a, e, c FROM FecdasBundle\Entity\EntityLlicencia l 
								JOIN l.parte p JOIN p.tipus t JOIN l.categoria a JOIN l.persona e JOIN p.club c WHERE 1 = 1 ";
				
			} else {
				//$groupQuey = $intervals || $edats || $grouptipus || $groupcategoria || $groupsexe || $groupmunicipi || $groupcomarca || $groupprovincia;
				// PREPARAR CONSULTA AGRUPADA
				$campsHeaderAgrupats['num'] = $campsHeader['num'];
				if ($groupclub == true) {
					$agrupats[] = 'c.nom';
					$campsHeaderAgrupats['club'] = $campsHeader['club'];
				}
				if ($grouptipus == true) {
					$agrupats[] = 't.codi';
					$campsHeaderAgrupats['tipus'] = $campsHeader['tipus'];
				}
				if ($groupcategoria == true) {
					$agrupats[] = 'a.categoria';
					$campsHeaderAgrupats['categoria'] = $campsHeader['categoria'];
				}
				if ($groupsexe == true) {
					$agrupats[] = 'e.sexe';
					$campsHeaderAgrupats['sexe'] = $campsHeader['sexe'];
				}
				if ($groupmunicipi == true) {
					$agrupats[] = 'e.addrpob';
					$campsHeaderAgrupats['poblacio'] = $campsHeader['poblacio'];
				}
				if ($groupcomarca == true) {
					$agrupats[] = 'e.addrcomarca';
					$campsHeaderAgrupats['comarca'] = $campsHeader['comarca'];
				}
				if ($groupprovincia == true) {
					$agrupats[] = 'e.addrprovincia';
					$campsHeaderAgrupats['provincia'] = $campsHeader['provincia'];
				}

				if ($intervals == true) {
					// Les dades venen agrupades per data d'alta. Afegir les columnes corresponents pels intervals
					$agrupats[] = 'p.dataalta';
					$anyinici = $datainici->format('Y');
					$mesinici = $datainici->format('m');
					$anyfinal = $datafinal->format('Y');
					$mesfinal = $datafinal->format('m');
			
					if ($intervaldata == 'A') { // Anys
						if ($anyinici <= $anyfinal) {
							for ($i = $anyinici; $i <= $anyfinal; $i++) $colsHeaderIntervals[] = $i;
						}
					} else { // Mesos
						if ($anyinici < $anyfinal || ($anyinici == $anyfinal && $mesinici <= $mesfinal)) {
							$interval = $datainici->diff($datafinal);
							$mesos = $interval->format('%y')*12 + $interval->format('%m');
							for ($i = 0; $i <= $mesos; $i++) {
								$colsHeaderIntervals[] = $anyinici.'-'.str_pad($mesinici, 2, '0', STR_PAD_LEFT); 
								
								$mesinici++;
								if ($mesinici > 12) {
									$mesinici = 1;
									$anyinici++;
								}
							}	
						}
					}
					foreach ($colsHeaderIntervals as $col) {
						$campsHeaderAgrupats[$col] = array('hidden' => false, 'nom' => $col, 'width' => '100px; font-style: italic; font-size: 0.8em; font-weight: normal;', 'sort' => '');
					}					
				}
				
				if ($edats == true) {
					// Les dades venen agrupades per data de naixement. Afegir les columnes corresponents pels intervals
					$agrupats[] = 'e.datanaixement';
					for ($i = 1; $i <= 95; $i+=$edatsdata) $colsHeaderIntervals[] = $i.' a '.($i+$edatsdata-1);
					
					foreach ($colsHeaderIntervals as $col) {
						$campsHeaderAgrupats[$col] = array('hidden' => false, 'nom' => $col, 'width' => '100px; font-style: italic; font-size: 0.8em; font-weight: normal;', 'sort' => '');
					}					
				}
				
				// Totals de l'agrupació
				$campsHeaderAgrupats['total'] = array('hidden' => false, 'nom' => 'Total', 'width' => '100px', 'sort' => 'total');
				$campsHeaderAgrupats['import'] = array('hidden' => false, 'nom' => 'Import', 'width' => '100px', 'sort' => 'import');
				
				$campsHeader = $campsHeaderAgrupats;
				
				// Només consultar preus a partir de l'any 2012
				if ($datainici->format('Y') > 2012 && $datafinal->format('Y') > 2012) {	
					$strQuery = "SELECT ".implode(', ', $agrupats).", COUNT(l.id) AS total, SUM(r.preu) AS import FROM FecdasBundle\Entity\EntityLlicencia l 
									JOIN l.parte p JOIN p.tipus t JOIN l.categoria a JOIN l.persona e JOIN p.club c 
									JOIN a.producte o JOIN o.preus r
									WHERE (YEAR(p.dataalta) = r.anypreu AND o.id = r.producte) ";  // Funció YEAR afegida a la configuració de $em => addCustomDatetimeFunction('YEAR', 'FecdasBundle\Classes\MysqlYear');
				} else {
					$strQuery = "SELECT ".implode(', ', $agrupats).", COUNT(l.id) AS total, 'NS/NC' AS import FROM FecdasBundle\Entity\EntityLlicencia l 
									JOIN l.parte p JOIN p.tipus t JOIN l.categoria a JOIN l.persona e JOIN p.club c 
									WHERE 1 = 1 ";
				}		
								
				if ( !in_array($sort, $agrupats) && $sort != 'total' && $sort != 'import') $sort =  $agrupats[0]; // Si l'ordre indicat no coincideix amb cap dels camps agrupats
				
			}

			if (count($clubs) > 0) {
				$strQuery .= " AND p.club IN (:clubs) ";
				$params['clubs'] = $clubs;
			}
			if ($activats == true) {
				$strQuery .= " AND c.activat = 1 ";
			}
			if (count($tipusparte) > 0) {
				$strQuery .= " AND p.tipus IN (:tipus) ";
				$params['tipus'] = $tipusparte;
			}
			if (count($categoria) > 0) {
				$strQuery .= " AND a.simbol IN (:categoria) ";
				$params['categoria'] = $categoria;
			}
			if ($datainici != null) {
				$strQuery .= " AND p.dataalta >= :datainici ";
				$params['datainici'] = $datainici->format('Y-m-d');
			}
			if ($datafinal != null) {
				$strQuery .= " AND p.dataalta <= :datafinal ";
				$params['datafinal'] = $datafinal->format('Y-m-d');
			}
			if ($baixes == 0) { // Excloure
				$strQuery .= " AND p.databaixa IS NULL AND l.databaixa IS NULL ";
			}
			if ($baixes == 2) { // Excloure
				$strQuery .= " AND (p.databaixa IS NOT NULL OR l.databaixa IS NOT NULL) ";
			}
			
			if ($groupQuey == true) $strQuery .= " GROUP BY ".implode(', ', $agrupats);


			/*
			
			 Total 19.618 DQL => Total SQL 20849
SELECT *
FROM m_llicencies l JOIN m_partes p ON l.parte = p.id JOIN m_comandes d ON d.id = p.id 
JOIN m_tipusparte t ON p.tipus = t.id JOIN m_categories a ON l.categoria = a.id JOIN m_persones e ON l.persona = e.id JOIN m_clubs c ON d.club = c.codi
WHERE 1 = 1  AND c.nom = 'ADAS CAVALLDEMAR'
AND a.simbol IN ('A')  AND p.dataalta >= '2014-01-01 00:00:00'  
AND p.dataalta <= '2015-11-24 00:00:00'  AND d.databaixa IS NULL AND l.databaixa IS NULL			
ORDER BY l.id asc			  
			 
SELECT *
FROM m_llicencies l JOIN m_partes p ON l.parte = p.id JOIN m_comandes d ON d.id = p.id 
JOIN m_tipusparte t ON p.tipus = t.id JOIN m_categories a ON l.categoria = a.id JOIN m_persones e ON l.persona = e.id JOIN m_clubs c ON d.club = c.codi
JOIN m_productes o ON a.producte = o.id LEFT JOIN m_preus r ON r.producte = o.id 
WHERE (YEAR(p.dataalta) = r.anypreu OR r.preu IS NULL)  AND c.nom = 'ADAS CAVALLDEMAR'
AND a.simbol IN ('A')  AND p.dataalta >= '2014-01-01 00:00:00'  
AND p.dataalta <= '2015-11-24 00:00:00'  AND d.databaixa IS NULL AND l.databaixa IS NULL
ORDER BY l.id asc 				 
			  
			  Total 19 DQL => Total SQL 122
SELECT c.nom, COUNT(l.id) AS total, SUM(r.preu) AS import 
FROM m_llicencies l JOIN m_partes p ON l.parte = p.id JOIN m_comandes d ON d.id = p.id
JOIN m_tipusparte t ON p.tipus = t.id JOIN m_categories a ON l.categoria = a.id JOIN m_persones e ON l.persona = e.id JOIN m_clubs c ON d.club = c.codi 
JOIN m_productes o ON a.producte = o.id LEFT JOIN m_preus r ON r.producte = o.id 
WHERE (YEAR(p.dataalta) = r.anypreu OR r.preu IS NULL)  
AND a.simbol IN ('A')  AND p.dataalta >= '2004-01-01 00:00:00' 
AND p.dataalta <= '2005-11-24 00:00:00'  AND d.databaixa IS NULL AND l.databaixa IS NULL  
GROUP BY c.nom 
			  
			  
			 */  	 
			$strQuery .= " ORDER BY ".$sort." ".$direction;
			$query = $em->createQuery($strQuery);
				
			foreach ($params as $k => $p) $query->setParameter($k, $p);
				
			$total = count($query->getResult());

			if ($action == 'csv' || $intervals == true || $edats == true) {
				$resultat = $query->getResult(); // intervals i edats encara no estan agrupats, no paginar. Export CSV tampoc pagina
			} else {
				$resultat = $query->setMaxResults($pageSize)->setFirstResult($offset)->getResult();
			}
			
		}

		$campsDades = array ();
		$index = 1;

		if ($groupQuey != true) {
			// Dades sense agrupar	
			foreach ($resultat as $llicencia) {
				$parte = $llicencia->getParte();
				$url = $this->generateUrl('FecdasBundle_editarcomanda', array('id' => $parte->getId()));
				
				$numcomanda = ($action == 'csv'?$parte->getNumComanda():'<a href="'.$url.'">'.$parte->getNumComanda().'</a>');
				
				$databaixa = '';
				if ($parte->getDatabaixa() != null) $databaixa = $parte->getDatabaixa()->format('d/m/y');
				else {
					if ($llicencia->getDatabaixa() != null) $databaixa = $llicencia->getDatabaixa()->format('d/m/y');
				}
				$persona = $llicencia->getPersona();
				 
				$campsDades[$llicencia->getId()] = array (	
										'num' 			=> array('hidden' => false, 'val' => $offset + $index, 'align' => 'left'),
										'comandaid' 	=> array('hidden' => true, 	'val' => $parte->getId(), 'align' => 'center'), 
										'comandanum' 	=> array('hidden' => false, 'val' => $numcomanda, 'align' => 'center'),
										'club' 			=> array('hidden' => false, 'val' => $parte->getClub()->getNom(), 'align' => 'left'),
										'tipus'			=> array('hidden' => false, 'val' => $parte->getTipus()->getCodi(), 'align' => 'left'),
										'dataalta' 		=> array('hidden' => false, 'val' => $parte->getDataalta()->format('d/m/y'), 'align' => 'center'),      
										'datacaducitat' => array('hidden' => false, 'val' => $parte->getDatacaducitat('')->format('d/m/y'), 'align' => 'center'),
										'databaixa'		=> array('hidden' => false, 'val' => $databaixa, 'align' => 'center'), 
										'categoria'		=> array('hidden' => false, 'val' => $llicencia->getCategoria()->getCategoria(), 'align' => 'center'), 
										'preu'			=> array('hidden' => false, 'val' => number_format($llicencia->getCategoria()->getPreuAny($parte->getAny()), 2, ',', '.').'€', 'align' => 'right'),
										'llicenciaid'	=> array('hidden' => true, 	'val' => $llicencia->getId(), 'align' => 'center'),  					  	
						 				'dni'			=> array('hidden' => false, 'val' => $persona->getDni(), 'align' => 'center'), 
						 				'estranger'		=> array('hidden' => false, 'val' => ($persona->esEstranger()?'Si':''), 'align' => 'center'), 
						 				'nom'			=> array('hidden' => false, 'val' => $persona->getNom(), 'align' => 'left'), 
						 				'cognoms'		=> array('hidden' => false, 'val' => $persona->getCognoms(), 'align' => 'left'), 
						 				'naixement'		=> array('hidden' => false, 'val' => $persona->getDatanaixement()->format('d/m/y'), 'align' => 'center'), 
						 				'edat'			=> array('hidden' => false, 'val' => $persona->getEdat(), 'align' => 'center'),	
						 				'sexe'			=> array('hidden' => false, 'val' => $persona->getSexe(), 'align' => 'center'),	
						 				'telefon1'		=> array('hidden' => false, 'val' => $persona->getTelefon1(), 'align' => 'center'),	
						 				'telefon2'		=> array('hidden' => false, 'val' => $persona->getTelefon2(), 'align' => 'center'),
						 				'mail'			=> array('hidden' => false, 'val' => $persona->getMail(), 'align' => 'left'),	
						 				'adreca'		=> array('hidden' => false, 'val' => $persona->getAddradreca(), 'align' => 'left'),	
						 				'poblacio'		=> array('hidden' => false, 'val' => $persona->getAddrpob(), 'align' => 'left'),	
						 				'cp'			=> array('hidden' => false, 'val' => $persona->getAddrcp(), 'align' => 'center'),
						 				'comarca'		=> array('hidden' => false, 'val' => $persona->getAddrcomarca(), 'align' => 'left'),
						 				'provincia'		=> array('hidden' => false, 'val' => $persona->getAddrprovincia(), 'align' => 'center'),
						 				'nacionalitat'	=> array('hidden' => false, 'val' => $persona->getAddrnacionalitat(), 'align' => 'center'),
										);
				
				$index++;
			}
		} else {
			// Dades agrupades
			foreach ($resultat as $grup) {
				
				$num = ($offset + $index);
				if ($intervals == true || $edats == true) $num = $index;	
					
				$arrayGrup = array ( 'num' => array('hidden' => false, 'val' => $num, 'align' => 'left') );
			
				$groupKey = '';
				$keyInterval = '';
				foreach ($agrupats as $camp) {
					// Truere el prefixe		
					$pos = strrpos($camp, '.');
					if ($pos !== false)  $camp = substr($camp, $pos+1);  
									
					$valor = 'NS/NC';
					
					if (isset($grup[$camp])) {
						
						if ($intervals == true && $camp == 'dataalta') {
							$keyInterval = $grup[$camp]->format('Y');
							if ($intervaldata == 'M') $keyInterval .= '-'.$grup[$camp]->format('m'); // Data alta el camp encara no està agrupat
						}  
						
						if ($edats == true && $camp == 'datanaixement') {
							$datanaixement = $grup[$camp];  // Data naixement el camp encara no està agrupat
							$current = new \DateTime();
    						$interval = $current->diff($datanaixement);	
							$edatInterval = $interval->format('%y');
							
							$edatMaxInterval = ceil($edatInterval/$edatsdata)*$edatsdata;
							$edatMinInterval = $edatMaxInterval - $edatsdata + 1; 
							
							$keyInterval = $edatMinInterval.' a '.$edatMaxInterval;
						}
						
						if ($camp != 'datanaixement' && $camp != 'dataalta') {  // Camp agrupar, construir la clau
							
							//$valor = mb_strtoupper(mb_substr($grup[$camp], 0, 1)).mb_strtolower(mb_substr($grup[$camp], 1));	
							$valor = $grup[$camp];
							$groupKey .= str_replace(' ', '_', $grup[$camp]).'_';
							
							$arrayGrup[$camp] = array('hidden' => false, 'val' => $valor, 'align' => 'left');
						} 
						
						
					}
				}

				if (isset($campsDades[$groupKey])) {
					// Registre existent, afegir dades	
					$campsDades[$groupKey]['total']['val'] += $grup['total'];
					$campsDades[$groupKey]['import']['val'] += $grup['import'];
					
					if ($intervals == true || $edats == true) {
						// Afegir intervals a les dades						
						if (isset($campsDades[$groupKey][$keyInterval])) $campsDades[$groupKey][$keyInterval]['val'] += $grup['total'];				
					}
					
				} else {
					// Crear nou registre
					if ($intervals == true || $edats == true) {
						// Afegir intervals a les dades						
						foreach ($colsHeaderIntervals as $col) {
							$arrayGrup[$col] = array('hidden' => false, 'val' => 0, 'align' => 'center');
						}	
						
						if (isset($arrayGrup[$keyInterval])) $arrayGrup[$keyInterval]['val'] = $grup['total'];				
					}
					$arrayGrup['total'] = array('hidden' => false, 'val' => $grup['total'], 'align' => 'right');
					$arrayGrup['import'] = array('hidden' => false, 'val' => $grup['import'], 'align' => 'right');
					
					$campsDades[$groupKey] = $arrayGrup;
					$index++;
				}
			}

			if ($action == 'query' && ($intervals == true || $edats == true)) {
				// En cas d'intervals i edats cal recalcular la paginació
				$total = count($campsDades);
				$campsDades = array_slice($campsDades, $offset, $pageSize, true);
			}

		} 
		
		if ($action == 'csv') {
			$filename = "export_consulta_".date("Y_m_d_His").".csv";
			
			$header = array(); // Get only header fields
			foreach ($campsHeader as $camp) $header[] = $camp['nom'];
			
			$data = array(); // Get only data matrix
			foreach ($campsDades as $row) {
				$rowdata = array(); 
				foreach ($row as $camp) {
					$rowdata[] = $camp['val'];
				}
				$data[] = $rowdata;
			}
			
			
			$response = $this->exportCSV($request, $header, $data, $filename);
			
			return $response;
		}
		
		// CREAR FORMULARI
		$formBuilder = $this->createFormBuilder();
		
		
		// Selector múltiple de clubs
		$clubsO = array();
		foreach ($clubs as $codi) {
			$clubsO[] = $em->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
		$formBuilder->add('clubs', 'entity', array(
				'class' 		=> 'FecdasBundle:EntityClub',
		 		'choice_label' 	=> 'llistaText',
				'required'  	=> false,
				'data'			=> $clubsO,
				'multiple'		=> true));
		
		$formBuilder->add('activats', 'checkbox', array(
    			'required'  	=> false,
				'data' 			=> $activats,
		));
		
		$tipusO = array();
		foreach ($tipusparte as $id) {
			$tipusO[] = $em->getRepository('FecdasBundle:EntityParteType')->find($id);
		}
		// Selector tipus de llicència
		$formBuilder->add('tipusparte', 'entity', array('class' => 'FecdasBundle:EntityParteType', 
				'choice_label' 	=> 'descripcio', 
				'multiple' 		=> true, 
				'required' 		=> false,
				'data'			=> $tipusO,
				'query_builder' => function($repository) {
					return $repository->createQueryBuilder('e')->where('e.actiu = true')->orderBy('e.id', 'ASC');
				})
		);
		
		$formBuilder->add('categoria', 'choice', array(
				'choices'   	=> array('A' => 'Aficionat', 'T' => 'Tècnic', 'I' => 'Infantil'),
				'required' 		=> false,
				'expanded'		=> false,
				'multiple'		=> true,
				'empty_value' 	=> false,
				'data' 			=> $categoria
		));
		
		// Selectors de dates: rang entre dates i per intervals mesos / anys
		$formBuilder->add('datainici', 'datetime', array(
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'required' 		=> false,
				'empty_value' 	=> null,
				'format' 		=> 'dd/MM/yyyy',
				'data' 			=> $datainici
		));

		$formBuilder->add('datafinal', 'datetime', array(
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'required' 		=> false,
				'empty_value' 	=> null,
				'format' 		=> 'dd/MM/yyyy',
				'data' 			=> $datafinal
		));
		
		$formBuilder->add('intervals', 'checkbox', array(
    			'required'  	=> false,
				'data' 			=> $intervals,
		));
		
		
		$formBuilder->add('intervaldata', 'choice', array(
				'choices'   	=> array('M' => 'Mensual', 'A' => 'Anual'),
				'required' 		=> false,
				'expanded'		=> true,
				'multiple'		=> false,
				'empty_value' 	=> false,
				'disabled'		=> $intervals == false,
				'data' 			=> $intervaldata
		));
		
		// Selectors edats: rang entre edats i per intervals: 5, 10, 20
		$formBuilder->add('edats', 'checkbox', array(
    			'required'  	=> false,
				'data' 			=> $edats,
		));
		
		
		$formBuilder->add('edatsdata', 'choice', array(
				'choices'   	=> array('5' => '5 anys', '10' => '10 anys', '20' => '20 anys'),
				'required' 		=> false,
				'expanded'		=> true,
				'multiple'		=> false,
				'empty_value' 	=> false,
				'disabled'		=> $edats == false,
				'data' 			=> $edatsdata
		));


		// Agrupar per club
		$formBuilder->add('groupclub', 'checkbox', array(
    			'required'  => false,
				'data' => $groupclub,
		));
		
		// Agrupar per tipus de llicència
		$formBuilder->add('grouptipus', 'checkbox', array(
    			'required'  => false,
				'data' => $grouptipus,
		));
		
		// Agrupar per categoria
		$formBuilder->add('groupcategoria', 'checkbox', array(
    			'required'  => false,
				'data' => $groupcategoria,
		));

		// Agrupar per sexe
		$formBuilder->add('groupsexe', 'checkbox', array(
    			'required'  => false,
				'data' => $groupsexe,
		));
			
		// Agrupar per municipi
		$formBuilder->add('groupmunicipi', 'checkbox', array(
    			'required'  => false,
				'data' => $groupmunicipi,
		));	

		// Agrupar per comarca
		$formBuilder->add('groupcomarca', 'checkbox', array(
    			'required'  => false,
				'data' => $groupcomarca,
		));	

		// Agrupar per provincia
		$formBuilder->add('groupprovincia', 'checkbox', array(
    			'required'  => false,
				'data' => $groupprovincia,
		));	
				
		// Baixes
		$formBuilder->add('baixes', 'choice', array(
				'choices'   	=> array('0' => 'Excloure baixes', '1' => 'Incloure baixes', '2' => 'Només baixes'),
				'required' 		=> false,
				'expanded'		=> true,
				'multiple'		=> false,
				'empty_value' 	=> false,
				'data' 			=> $baixes
		));
		
		
		// Temps des de la darrera llicència
		$form = $formBuilder->getForm();
		
		
		
		return $this->render('FecdasBundle:Admin:consultaadmin.html.twig', 
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'header' => $campsHeader, 'dades' => $campsDades,
						'total' => $total, 'page' => $page, 'pages' => ceil($total/$pageSize), 'perpage' => $pageSize, 'offset' => $offset,  
						'sortparams' => $sortparams, 'queryparams' => $queryparams
				)));
	}
	
	
	public function consultaclubsAction(Request $request) {
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$em = $this->getDoctrine()->getManager();
	
		// Afegir funció YEAR de mysql a Doctrine DQL
		$config = $em->getConfiguration();
		$config->addCustomDatetimeFunction('YEAR', 'FecdasBundle\Classes\MysqlYear');
	
		// GET OPCIONS DE FILTRE
		$action = $request->query->get('action', '');
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.nom');
		$direction = $request->query->get('direction', 'asc');

		$activats = true;
		if ($request->query->has('activats') && $request->query->get('activats') == 0) $activats = false;

		$baixes = $request->query->get('baixes', '0');

		$municipi = $request->query->get('municipi', '');;
		$comarca = $request->query->get('comarca', '');
		$provincia = $request->query->get('provincia', '');

		$grouptipuspagament = false;
		if ($request->query->has('grouptipuspagament') && $request->query->get('grouptipuspagament') == 1) $grouptipuspagament = true;

		$grouptipusclub = false;
		if ($request->query->has('grouptipusclub') && $request->query->get('grouptipusclub') == 1) $grouptipusclub = true;

		$groupmunicipi = false;
		if ($request->query->has('groupmunicipi') && $request->query->get('groupmunicipi') == 1) $groupmunicipi = true;

		$groupcomarca = false;
		if ($request->query->has('groupcomarca') && $request->query->get('groupcomarca') == 1) $groupcomarca = true;

		$groupprovincia = false;
		if ($request->query->has('groupprovincia') && $request->query->get('groupprovincia') == 1) $groupprovincia = true;

		$groupmunicipicorreu = false;
		if ($request->query->has('groupmunicipicorreu') && $request->query->get('groupmunicipicorreu') == 1) $groupmunicipicorreu = true;

		$groupcomarcacorreu = false;
		if ($request->query->has('groupcomarcacorreu') && $request->query->get('groupcomarcacorreu') == 1) $groupcomarcacorreu = true;

		$groupprovinciacorreu = false;
		if ($request->query->has('groupprovinciacorreu') && $request->query->get('groupprovinciacorreu') == 1) $groupprovinciacorreu = true;

		$tipuspagament = $request->query->get('tipuspagament', array()); // Per defecte sense filtre de tipus
		if ($tipuspagament == '') $tipuspagament = array();

		$tipusclub = $request->query->get('tipusclub', array()); // Per defecte sense filtre de tipus
		if ($tipusclub == '') $tipusclub = array();

		$strDataalta = $request->query->get('dataalta', '');
		if ($strDataalta == '') $dataalta = null; 
		else $dataalta = \DateTime::createFromFormat('d/m/Y', $strDataalta);

		$strDatajunta = $request->query->get('datajunta', '');
		if ($strDatajunta == '') $datajunta = null; 
		else $datajunta = \DateTime::createFromFormat('d/m/Y', $strDatajunta);
		

		$groupQuey = $grouptipuspagament || $grouptipusclub || $groupmunicipi || $groupcomarca || $groupprovincia || $groupmunicipicorreu || $groupcomarcacorreu || $groupprovinciacorreu;

		$queryparams = array ('action' => $action, 'activats' => $activats, 'baixes' => $baixes, 
								'tipuspagament' => $tipuspagament, 'tipusclub' => $tipusclub,
								'dataalta' => $strDataalta, 'datajunta' => $strDatajunta,
								'municipi' => $municipi, 'comarca' => $comarca,	'provincia' => $provincia,
								'grouptipuspagament' => $grouptipuspagament, 'grouptipusclub' => $grouptipusclub,
								'groupmunicipi' => ($groupmunicipi == true?1:0), 'groupcomarca' => ($groupcomarca == true?1:0),
								'groupprovincia' => ($groupprovincia == true?1:0), 'groupmunicipicorreu' => ($groupmunicipicorreu == true?1:0), 
								'groupcomarcacorreu' => ($groupcomarcacorreu == true?1:0), 'groupprovinciacorreu' => ($groupprovinciacorreu == true?1:0)
								);


		
		$sortparams = array('sort' => $sort, 'direction' => $direction, 'inverse' => ($direction=='asc'?'desc':'asc') );
		$resultat = array();
		$params = array();
		$pageSize = 20; // limit
		$offset = ($page - 1) * $pageSize; 
		
		$agrupats = array();
		$colsHeaderIntervals = array();
		$campsHeaderAgrupats = array();
		$campsHeader = array (		'num' 			=> array('hidden' => false, 'nom' => 'Num.', 'width' => '40px', 'sort' => ''),
									'codi' 			=> array('hidden' => false, 'nom' => 'Codi', 'width' => '80px', 'sort' => 'c.codi'),
									'tipusclub' 	=> array('hidden' => false, 'nom' => 'Tipus', 'width' => '120px', 'sort' => 't.tipus'),
									'club' 			=> array('hidden' => false, 'nom' => 'Club', 'width' => '170px', 'sort' => 'c.nom'),
									'actiu'			=> array('hidden' => false, 'nom' => 'Actiu', 'width' => '60px', 'sort' => 'c.activat'),
									'telefon' 		=> array('hidden' => false, 'nom' => 'Telèfon', 'width' => '80px', 'sort' => 'c.telefon'),
									'fax' 			=> array('hidden' => false, 'nom' => 'Fax', 'width' => '80px', 'sort' => 'c.fax'),
									'mobil' 		=> array('hidden' => false, 'nom' => 'Mòbil', 'width' => '80px', 'sort' => 'c.mobil'),         
									'mail'			=> array('hidden' => false, 'nom' => 'eMail', 'width' => '170px', 'sort' => 'c.mail'),
									'web'			=> array('hidden' => false, 'nom' => 'Web', 'width' => '170px', 'sort' => 'c.web'),
									'cif'			=> array('hidden' => false, 'nom' => 'CIF', 'width' => '100px', 'sort' => 'c.cif'),
									'compte'		=> array('hidden' => false, 'nom' => 'Compte', 'width' => '100px', 'sort' => 'c.compte'),
					 				'adreca'		=> array('hidden' => false, 'nom' => 'Adreça', 'width' => '200px', 'sort' => 'c.addradreca'), 
					 				'poblacio'		=> array('hidden' => false, 'nom' => 'Problació', 'width' => '150px', 'sort' => 'c.addrpob'), 
					 				'cp'			=> array('hidden' => false, 'nom' => 'CP', 'width' => '60px', 'sort' => 'c.addrcp'), 
					 				'comarca'		=> array('hidden' => false, 'nom' => 'Comarca', 'width' => '150px', 'sort' => 'c.addrcomarca'),
					 				'provincia'		=> array('hidden' => false, 'nom' => 'Província', 'width' => '110px', 'sort' => 'c.addrprovincia'), 
					 				'adrecacorreu'	=> array('hidden' => false, 'nom' => 'Adreça correu', 'width' => '200px', 'sort' => 'c.addradrecacorreu'), 
					 				'poblaciocorreu'=> array('hidden' => false, 'nom' => 'Pob. correu', 'width' => '150px', 'sort' => 'c.addrpobcorreu'), 
					 				'cpcorreu'		=> array('hidden' => false, 'nom' => 'CP correu', 'width' => '60px', 'sort' => 'c.addrcpcorreu'), 
					 				'comarcacorreu'	=> array('hidden' => false, 'nom' => 'Comarca correu', 'width' => '150px', 'sort' => 'c.addrcomarcacorreu'),
					 				'provinciacorreu'	=> array('hidden' => false, 'nom' => 'Prov. correu', 'width' => '110px', 'sort' => 'c.addrprovinciacorreu'),
									'tipuspagament'	=> array('hidden' => false, 'nom' => 'Pagament', 'width' => '130px', 'sort' => 'e.descripcio'),
					 				'limitcredit'	=> array('hidden' => false, 'nom' => 'Crèdit', 'width' => '100px', 'sort' => 'c.limitcredit'), 
									'romanent'		=> array('hidden' => false, 'nom' => 'Romanent '.(date('Y')-1), 'width' => '100px', 'sort' => 'c.romanent'), 
									'totalpagaments'=> array('hidden' => false, 'nom' => 'Pagament', 'width' => '100px', 'sort' => 'c.totalpagaments'),
									'totalllicencies'=> array('hidden' => false, 'nom' => 'T. llicències', 'width' => '100px', 'sort' => 'c.totalllicencies'),
									'totalduplicats'=> array('hidden' => false, 'nom' => 'T. duplicats', 'width' => '100px', 'sort' => 'c.totalduplicats'),
									'totalaltres'	=> array('hidden' => false, 'nom' => 'T. altres', 'width' => '100px', 'sort' => 'c.totalaltres'),
									'ajustsubvencions'	=> array('hidden' => false, 'nom' => 'Ajust subv.', 'width' => '100px', 'sort' => 'c.ajustsubvencions'),
									'dataalta' 		=> array('hidden' => false, 'nom' => 'Alta', 'width' => '80px', 'sort' => 'c.dataalta'),
									'databaixa'		=> array('hidden' => false, 'nom' => 'Baixa', 'width' => '80px', 'sort' => 'c.databaixa'), 
									'datacreacio' 	=> array('hidden' => false, 'nom' => 'Creació', 'width' => '80px', 'sort' => 'c.datacreacio'),
									'datajunta'		=> array('hidden' => false, 'nom' => 'Últ. Junta', 'width' => '80px', 'sort' => 'c.datajunta'), 
									'estatus'		=> array('hidden' => false, 'nom' => 'Estatuts', 'width' => '80px', 'sort' => 'c.estatuts'),
									'registre'		=> array('hidden' => false, 'nom' => 'Núm. Registre', 'width' => '80px', 'sort' => 'c.registre'),
									'president'		=> array('hidden' => false, 'nom' => 'President', 'width' => '180px', 'sort' => ''),
									'vicepresident'	=> array('hidden' => false, 'nom' => 'Vicepresident', 'width' => '180px', 'sort' => ''),
									'secretari'		=> array('hidden' => false, 'nom' => 'Secretari', 'width' => '180px', 'sort' => ''),
									'tresorer'		=> array('hidden' => false, 'nom' => 'Tresorer', 'width' => '180px', 'sort' => ''),
									'vocals'		=> array('hidden' => false, 'nom' => 'Vocals', 'width' => '260px', 'sort' => ''),
									);
		$total = 0;
		if ($action == 'query' || $action == 'csv') {
			if ($groupQuey != true) {
				// PREPARAR CONSULTA SENSE AGRUPAR
				$strQuery = "SELECT c, t FROM FecdasBundle\Entity\EntityClub c 
								LEFT JOIN c.tipus t LEFT JOIN c.estat e WHERE 1 = 1 ";
				
			} else {
				//$groupQuey = $grouptipuspagament || $grouptipusclub || $groupmunicipi || $groupcomarca || $groupprovincia || $groupmunicipicorreu || $groupcomarcacorreu || $groupprovinciacorreu;
				// PREPARAR CONSULTA AGRUPADA
				$campsHeaderAgrupats['num'] = $campsHeader['num'];
				
				if ($grouptipuspagament == true) {
					$agrupats[] = 'e.descripcio';
					$campsHeaderAgrupats['tipuspagament'] = $campsHeader['tipuspagament'];
				}
				if ($grouptipusclub == true) {
					$agrupats[] = 't.tipus';
					$campsHeaderAgrupats['tipusclub'] = $campsHeader['tipusclub'];
				}
				if ($groupmunicipi == true) {
					$agrupats[] = 'c.addrpob';
					$campsHeaderAgrupats['poblacio'] = $campsHeader['poblacio'];
				}
				if ($groupcomarca == true) {
					$agrupats[] = 'c.addrcomarca';
					$campsHeaderAgrupats['comarca'] = $campsHeader['comarca'];
				}
				if ($groupprovincia == true) {
					$agrupats[] = 'c.addrprovincia';
					$campsHeaderAgrupats['provincia'] = $campsHeader['provincia'];
				}
				if ($groupmunicipicorreu == true) {
					$agrupats[] = 'c.addrpobcorreu';
					$campsHeaderAgrupats['poblaciocorreu'] = $campsHeader['poblaciocorreu'];
				}
				if ($groupcomarcacorreu == true) {
					$agrupats[] = 'c.addrcomarcacorreu';
					$campsHeaderAgrupats['comarcacorreu'] = $campsHeader['comarcacorreu'];
				}
				if ($groupprovinciacorreu == true) {
					$agrupats[] = 'c.addrprovinciacorreu';
					$campsHeaderAgrupats['provinciacorreu'] = $campsHeader['provinciacorreu'];
				}

				// Totals de l'agrupació
				$campsHeaderAgrupats['total'] = array('hidden' => false, 'nom' => 'Total', 'width' => '100px', 'sort' => 'total');

				
				$campsHeader = $campsHeaderAgrupats;
				
				$strQuery = "SELECT ".implode(', ', $agrupats).", COUNT(c.codi) AS total FROM FecdasBundle\Entity\EntityClub c 
								LEFT JOIN c.tipus t LEFT JOIN c.estat e WHERE 1 = 1 ";
				
				if ( !in_array($sort, $agrupats) && $sort != 'total' && $sort != 'import') $sort =  $agrupats[0]; // Si l'ordre indicat no coincideix amb cap dels camps agrupats
				
			}

			if (count($tipuspagament) > 0) {
				$strQuery .= " AND c.estat IN (:estat) ";
				$params['estat'] = $tipuspagament;
			}
			if (count($tipusclub) > 0) {
				$strQuery .= " AND c.tipus IN (:tipus) ";
				$params['tipus'] = $tipusclub;
			}
			if ($activats == true) {
				$strQuery .= " AND c.activat = 1 ";
			}

			if ($dataalta != null) {
				$strQuery .= " AND c.dataalta >= :dataalta ";
				$params['dataalta'] = $dataalta->format('Y-m-d');
			}
			if ($datajunta != null) {
				$strQuery .= " AND c.datajunta <= :datajunta ";
				$params['datajunta'] = $datajunta->format('Y-m-d');
			}
			if ($municipi != '') {
				$strQuery .= " AND c.addrpob LIKE :municipi ";
				$params['municipi'] = '%'.$municipi.'%';
			}
			if ($comarca != '') {
				$strQuery .= " AND c.addrcomarca LIKE :comarca ";
				$params['comarca'] = '%'.$comarca.'%';
			}
	
			if ($provincia != '') {
				$strQuery .= " AND c.addrprovincia LIKE :provincia ";
				$params['provincia'] = '%'.$provincia.'%';
			}			
			if ($baixes == 0) { // Excloure
				$strQuery .= " AND c.databaixa IS NULL AND c.databaixa IS NULL ";
			}
			if ($baixes == 2) { // Excloure
				$strQuery .= " AND (c.databaixa IS NOT NULL OR c.databaixa IS NOT NULL) ";
			}
			
			if ($groupQuey == true) $strQuery .= " GROUP BY ".implode(', ', $agrupats);

			$strQuery .= " ORDER BY ".$sort." ".$direction;
			
			$query = $em->createQuery($strQuery);
				
			foreach ($params as $k => $p) $query->setParameter($k, $p);
				
			$total = count($query->getResult());

			if ($action == 'csv') {
				$resultat = $query->getResult(); // intervals i edats encara no estan agrupats, no paginar. Export CSV tampoc pagina
			} else {
				$resultat = $query->setMaxResults($pageSize)->setFirstResult($offset)->getResult();
			}
		}

		$campsDades = array ();
		$index = 1;

		if ($groupQuey != true) {
			// Dades sense agrupar	
			foreach ($resultat as $club) {
				$tipus = $club->getTipus();
				$estat = $club->getEstat();
				
				$url = $this->generateUrl('FecdasBundle_club', array('codi' => $club->getCodi()));
				
				$codi = ($action == 'csv'?$club->getCodi():'<a href="'.$url.'">'.$club->getCodi().'</a>');
				
				$president = '';
				$vicepresident = '';
				$secretari = '';
				$tresorer = '';
				$vocals = '';

				$jsonCarrecs = ($club->getCarrecs() != ''?json_decode($club->getCarrecs()):array());
	
				foreach ($jsonCarrecs as $value) {
					
					if ($value->cid == BaseController::CARREC_PRESIDENT) $president .= $value->nom;
					
					if ($value->cid == BaseController::CARREC_VICEPRESIDENT) $vicepresident .= $value->nom;
					
					if ($value->cid == BaseController::CARREC_SECRETARI) $secretari .= $value->nom;
						
					if ($value->cid == BaseController::CARREC_TRESORER) $tresorer .= $value->nom;	
						
					if ($value->cid == BaseController::CARREC_VOCAL) $vocals .= $value->nom.', ';
				
				}
				
				if ($vocals != '') $vocals = substr($vocals, 0, -2);
				
				$campsDades[$codi] = array (	
										'num' 			=> array('hidden' => false, 'val' => $offset + $index, 'align' => 'left'),
										'codi' 			=> array('hidden' => false, 'val' => $codi, 'align' => 'left'),
										'tipusclub' 	=> array('hidden' => false, 'val' => $tipus->getTipus(), 'align' => 'center'),
										'club' 			=> array('hidden' => false, 'val' => $club->getNom(), 'align' => 'left'),
										'actiu' 		=> array('hidden' => false, 'val' => ($club->getActivat() == true?'Si':'No'), 'align' => 'center'),      
										'telefon' 		=> array('hidden' => false, 'val' => $club->getTelefon(), 'align' => 'center'),
										'fax'			=> array('hidden' => false, 'val' => $club->getFax(), 'align' => 'center'), 
										'mobil'			=> array('hidden' => false, 'val' => $club->getMobil(), 'align' => 'center'), 
										'mail'			=> array('hidden' => false, 'val' => $club->getMail(), 'align' => 'left'),
										'web'			=> array('hidden' => false, 'val' => $club->getWeb(), 'align' => 'left'),  					  	
						 				'cif'			=> array('hidden' => false, 'val' => $club->getCif(), 'align' => 'center'), 
						 				'compte'		=> array('hidden' => false, 'val' => $club->getCompte(), 'align' => 'center'), 
						 				'adreca'		=> array('hidden' => false, 'val' => $club->getAddradreca(), 'align' => 'left'), 
						 				'poblacio'		=> array('hidden' => false, 'val' => $club->getAddrpob(), 'align' => 'left'), 
						 				'cp'			=> array('hidden' => false, 'val' => $club->getAddrcp(), 'align' => 'center'), 
						 				'comarca'			=> array('hidden' => false, 'val' => $club->getAddrcomarca(), 'align' => 'left'),	
						 				'provincia'			=> array('hidden' => false, 'val' => $club->getAddrprovincia(), 'align' => 'center'),	
						 				'adrecacorreu'		=> array('hidden' => false, 'val' => $club->getAddradrecacorreu(), 'align' => 'left'),	
						 				'poblaciocorreu'	=> array('hidden' => false, 'val' => $club->getAddrpobcorreu(), 'align' => 'left'),	
						 				'cpcorreu'			=> array('hidden' => false, 'val' => $club->getAddrcpcorreu(), 'align' => 'center'),
						 				'comarcacorreu'		=> array('hidden' => false, 'val' => $club->getAddrcomarcacorreu(), 'align' => 'left'),
						 				'provinciacorreu'	=> array('hidden' => false, 'val' => $club->getAddrprovinciacorreu(), 'align' => 'center'),
						 				'tipuspagament'		=> array('hidden' => false, 'val' => $estat->getDescripcio(), 'align' => 'center'),
						 				'limitcredit'		=> array('hidden' => false, 'val' => number_format($club->getLimitcredit(), 2, ',', '.').'€', 'align' => 'right'),
						 				'romanent'			=> array('hidden' => false, 'val' => number_format($club->getRomanent(), 2, ',', '.').'€', 'align' => 'right'),	
						 				'totalpagaments'	=> array('hidden' => false, 'val' => number_format($club->getTotalpagaments(), 2, ',', '.').'€', 'align' => 'right'),
										'totalllicencies'	=> array('hidden' => false, 'val' => number_format($club->getTotalllicencies(), 2, ',', '.').'€', 'align' => 'right'),	
										'totalduplicats'	=> array('hidden' => false, 'val' => number_format($club->getTotalduplicats(), 2, ',', '.').'€', 'align' => 'right'),
										'totalaltres'		=> array('hidden' => false, 'val' => number_format($club->getTotalaltres(), 2, ',', '.').'€', 'align' => 'right'),
										'ajustsubvencions'	=> array('hidden' => false, 'val' => number_format($club->getAjustsubvencions(), 2, ',', '.').'€', 'align' => 'right'),
										'dataalta'		=> array('hidden' => false, 'val' => ($club->getDataalta() != null?$club->getDataalta()->format('d/m/y'):''), 'align' => 'center'),																																								
						 				'databaixa'		=> array('hidden' => false, 'val' => ($club->getDatabaixa() != null?$club->getDatabaixa()->format('d/m/y'):''), 'align' => 'center'),
										'datacreacio'	=> array('hidden' => false, 'val' => ($club->getDatacreacio() != null?$club->getDatacreacio()->format('d/m/y'):''), 'align' => 'center'),	
										'datajunta'		=> array('hidden' => false, 'val' => ($club->getDatajunta() != null?$club->getDatajunta()->format('d/m/y'):''), 'align' => 'center'),
										'estatus'		=> array('hidden' => false, 'val' => ($club->getEstatuts() == true?'Si':'No'), 'align' => 'center'),
										'registre'		=> array('hidden' => false, 'val' => $club->getRegistre() , 'align' => 'center'),
										'president'		=> array('hidden' => false, 'val' => $president, 'align' => 'left'),																																								
										'vicepresident'	=> array('hidden' => false, 'val' => $vicepresident, 'align' => 'left'),
										'secretari'		=> array('hidden' => false, 'val' => $secretari, 'align' => 'left'),																																								
										'tresorer'		=> array('hidden' => false, 'val' => $tresorer, 'align' => 'left'),
										'vocals'		=> array('hidden' => false, 'val' => $vocals, 'align' => 'left'),																																								
										);

				$index++;
			}
		} else {
			// Dades agrupades
			foreach ($resultat as $grup) {
					
				$campsDades[$index] = array ('num' => array('hidden' => false, 'val' => $offset + $index, 'align' => 'left'),);
				
				foreach ($grup as $camp => $valor) {
					
					if ($camp == 'total') $campsDades[$index][$camp] =  array('hidden' => false, 'val' => $valor, 'align' => 'right');
					else $campsDades[$index][$camp] =  array('hidden' => false, 'val' => $valor, 'align' => 'left');
					
				}		
				$index++;
			}

		} 
		
		if ($action == 'csv') {
			$filename = "export_consulta_clubs_".date("Y_m_d_His").".csv";
			
			$header = array(); // Get only header fields
			foreach ($campsHeader as $camp) $header[] = $camp['nom'];
			
			$data = array(); // Get only data matrix
			foreach ($campsDades as $row) {
				$rowdata = array(); 
				foreach ($row as $camp) {
					$rowdata[] = $camp['val'];
				}
				$data[] = $rowdata;
			}
			
			
			$response = $this->exportCSV($request, $header, $data, $filename);
			
			return $response;
		}
		
		// CREAR FORMULARI
		$formBuilder = $this->createFormBuilder();

		$formBuilder->add('activats', 'checkbox', array(
    			'required'  	=> false,
				'data' 			=> $activats,
		));
		
		// Selector múltiple de tipus de clubs
		$tipusO = array();
		foreach ($tipusclub as $tip) {
			$tipusO[] = $em->getRepository('FecdasBundle:EntityClubType')->find($tip);
		}
		$formBuilder->add('tipusclub', 'entity', array(
				'class' 		=> 'FecdasBundle:EntityClubType',
		 		'choice_label' 	=> 'tipus',
				'required'  	=> false,
				'data'			=> $tipusO,
				'multiple'		=> true
		));
		
		// Selector múltiple de tipus de pagament
		$pagamentO = array();
		foreach ($tipuspagament as $tip) {
			$pagamentO[] = $em->getRepository('FecdasBundle:EntityClubEstat')->find($tip);
		}

		$formBuilder->add('tipuspagament', 'entity', array(
				'class'   		=> 'FecdasBundle:EntityClubEstat',
				'choice_label' 	=> 'descripcio',
				'required' 		=> false,
				'data' 			=> $pagamentO,
				'multiple'		=> true,
		));
		
		// Selectors de dates
		$formBuilder->add('dataalta', 'datetime', array(
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'required' 		=> false,
				'empty_value' 	=> null,
				'format' 		=> 'dd/MM/yyyy',
				'data' 			=> $dataalta
		));

		$formBuilder->add('datajunta', 'datetime', array(
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'required' 		=> false,
				'empty_value' 	=> null,
				'format' 		=> 'dd/MM/yyyy',
				'data' 			=> $datajunta
		));
		
		$formBuilder->add('municipi', 'choice', array(
				'choices' => $this->getMunicipis(),
				//'preferred_choices' => array(''),
				'empty_value' 	=> 'Municipi ...',
				'required'  	=> false,
				'data' 			=> $municipi
		));
		
		$formBuilder->add('comarca', 'choice', array(
				'choices' => $this->getComarques(),
				//'preferred_choices' => array(''),
				'empty_value' 	=> 'Comarca ...',
				'required'  	=> false,
				'data' 			=> $comarca
		));
		
		$formBuilder->add('provincia', 'choice', array(
				'choices' => array('Barcelona' => 'Barcelona','Girona' => 'Girona','Tarragona' => 'Tarragona','Lleida' => 'Lleida' ),
				//'preferred_choices' => array('Barcelona','Girona','Tarragona','Lleida' ),
				'empty_value' 	=> 'Província...',
				'required'  	=> false,
				'data' 			=> $provincia
		));
		
		// Agrupar per tipus pagament
		$formBuilder->add('grouptipuspagament', 'checkbox', array(
    			'required'  => false,
				'data' => $grouptipuspagament,
		));
		
		// Agrupar per tipus de club
		$formBuilder->add('grouptipusclub', 'checkbox', array(
    			'required'  => false,
				'data' => $grouptipusclub,
		));
		
		// Agrupar per municipi
		$formBuilder->add('groupmunicipi', 'checkbox', array(
    			'required'  => false,
				'data' => $groupmunicipi,
		));	

		// Agrupar per comarca
		$formBuilder->add('groupcomarca', 'checkbox', array(
    			'required'  => false,
				'data' => $groupcomarca,
		));	

		// Agrupar per provincia
		$formBuilder->add('groupprovincia', 'checkbox', array(
    			'required'  => false,
				'data' => $groupprovincia,
		));	
		
		// Agrupar per municipi correu
		$formBuilder->add('groupmunicipicorreu', 'checkbox', array(
    			'required'  => false,
				'data' => $groupmunicipicorreu,
		));	

		// Agrupar per comarca correu
		$formBuilder->add('groupcomarcacorreu', 'checkbox', array(
    			'required'  => false,
				'data' => $groupcomarcacorreu,
		));	

		// Agrupar per provincia correu
		$formBuilder->add('groupprovinciacorreu', 'checkbox', array(
    			'required'  => false,
				'data' => $groupprovinciacorreu,
		));	
						
		// Baixes
		$formBuilder->add('baixes', 'choice', array(
				'choices'   	=> array('0' => 'Excloure baixes', '1' => 'Incloure baixes', '2' => 'Només baixes'),
				'required' 		=> false,
				'expanded'		=> true,
				'multiple'		=> false,
				'empty_value' 	=> false,
				'data' 			=> $baixes
		));
		
		
		// Temps des de la darrera llicència
		$form = $formBuilder->getForm();
		
		return $this->render('FecdasBundle:Admin:consultaclubs.html.twig', 
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'header' => $campsHeader, 'dades' => $campsDades,
						'total' => $total, 'page' => $page, 'pages' => ceil($total/$pageSize), 'perpage' => $pageSize, 'offset' => $offset,  
						'sortparams' => $sortparams, 'queryparams' => $queryparams
				)));
	}
	
	
	
	public function sincroaccessAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_login'));
	
		$em = $this->getDoctrine()->getManager();

		
		$parteid = $request->query->get("id");
		
		$parte = $this->getDoctrine()->getRepository('FecdasBundle:EntityParte')->find($parteid);
		
		if ($parte != null) {
			$interval = \DateInterval::createfromdatestring('+15 minute');
			$current = $this->getCurrentDate();
			$current->add($interval);
			
			$parte->setDatamodificacio($current);
			
			foreach ($parte->getLlicencies() as $llicencia_iter) {
				if ($llicencia_iter->getDatabaixa() == null) {
					$llicencia_iter->setDatamodificacio($current);
					$llicencia_iter->getPersona()->setValidat(false);
				}
			}
			
			$em->flush();

			$this->get('session')->getFlashBag()->add('error-notice', 'Llista '.$parteid.' preparada per tornar a sincronitzar');
			
			$this->logEntryAuth('SINCRO ACCESS', $parteid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error en el procés de sincronització');
			
			$this->logEntryAuth('SINCRO ACCESS ERROR', $parteid);
		}
		
		$response = $this->forward('FecdasBundle:Admin:recents');
		return $response;
	}
	
	public function canviestatclubAction (Request $request) {
		if ($this->isCurrentAdmin() != true) return new Response("no admin");
		
		$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($request->query->get('codiclub'));
		$estat = $request->query->get('action');
		$limitcredit = $request->query->get('limitcredit', 0);
		//if ($limitcredit == "") $limitcredit = null;
		
		if ($club == null) {
			$this->logEntryAuth('CLUB STATE ERROR', $request->query->get('codiclub'));
			return new Response("ko");
		}
		
		$em = $this->getDoctrine()->getManager();
		
		switch ($estat) {
			case BaseController::CLUB_PAGAMENT_DIFERIT:  // Pagament diferit

				if ($request->query->get('imprimir') == 'true') $club->setImpressio(true);
				else $club->setImpressio(false);

				break;
			case BaseController::CLUB_PAGAMENT_IMMEDIAT:  // Pagament immediat
				
				break;
			case BaseController::CLUB_SENSE_TRAMITACIO:  // Sense tramitació
				
				// Enviar notificació mail
				$subject = "Notificació. Federació Catalana d'Activitats Subaquàtiques";
				if ($club->getMail() == null) $subject = "Notificació. Cal avisar aquest club no té adreça de mail al sistema";
				
				$bccmails = $this->getFacturacioMails();
				$tomails = array($club->getMail());
				$body = "<p>Benvolgut club ".$club->getNom()."</p>";
				$body .= "<p>Us fem saber que, a partir de la recepció d’aquest correu, 
						per a la realització de tràmits en el sistema de gestió de 
						llicències federatives i assegurances de la FECDAS us caldrà 
						contactar prèviament amb la federació</p>";
				
				$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			
				break;
		}

		$estatAnterior = $club->getEstat();
		$estat = $this->getDoctrine()->getRepository('FecdasBundle:EntityClubEstat')->find($estat);
		$club->setEstat($estat);
		$club->setLimitcredit($limitcredit);
		$em->flush();
		
		$this->logEntryAuth('CLUB STATE OK', $club->getNom()." ".$estatAnterior->getCodi()." -> ".$estat->getCodi());
		
		return new Response("ok");
	}
	
	public function clubsAction(Request $request) {
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		// Només jo
		/*if ($this->get('session')->get('username') != 'alexmazinho@gmail.com')
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));*/
	
		$em = $this->getDoctrine()->getManager();
	
		$states = explode(";", self::CLUBS_STATES);
		$currentEstat = self::CLUBS_DEFAULT_STATE;
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.nom');
		$direction = $request->query->get('direction', 'asc');
		$currentEstat = $request->query->get('estat', $currentEstat);
		$codi = $request->query->get('codi', '');
		$club = null;
		
		if ($codi != '') {
			$currentEstat = BaseController::TOTS_CLUBS_DEFAULT_STATE;
			$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		}
		
		if ($request->getMethod() == 'POST') {
		// Criteris de cerca.Desactivat JQuery 
			$this->logEntryAuth('SALDO CLUBS POST', "Filtre estat: " . $states[$currentEstat]);
			
		} else {
			$this->logEntryAuth('SALDO CLUBS');
		}
			
		$formBuilder = $this->createFormBuilder()->add('estat', 'choice', array(
				'choices'   => $states,
				'preferred_choices' => array(self::CLUBS_DEFAULT_STATE),  // Estat per defecte sempre
				'data' => $currentEstat
		));
		
		$this->addClubsActiusForm($formBuilder, $club);
		
		$form = $formBuilder->getForm();
	
		// Crear índex taula partes per data entrada
		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityClub c JOIN c.estat e ";
		//$strQuery .= " WHERE c.activat = true AND c.codi <> 'CAT000' ";
		$strQuery .= " WHERE c.databaixa IS NULL AND c.codi <> 'CAT000' ";
		if ($currentEstat != 0) $strQuery .= " AND e.descripcio = :filtreestat ";
		if ($codi != '') $strQuery .= " AND c.codi = :codi ";
		$strQuery .= " ORDER BY ". $sort;
		$query = $em->createQuery($strQuery);
		if ($currentEstat != 0) $query->setParameter('filtreestat', $states[$currentEstat]);
		if ($codi != '') $query->setParameter('codi', $codi);
		
		$paginator  = $this->get('knp_paginator');
		$clubs = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		);
		$clubs->setParam('estat', $currentEstat);
		
		//$form->get('estat')->setData($currentEstat);  // Mantenir estat darrera consulta

		return $this->render('FecdasBundle:Admin:clubs.html.twig',  
			$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'clubs' => $clubs,
					'sortparams' => array('sort' => $sort,'direction' => $direction)
			))); 
	}
	
	public function anularpeticioAction(Request $request) {
		/* Anular petició duplicat */
				
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$em = $this->getDoctrine()->getManager();
		
		$duplicatid = $request->query->get("id");
		
		$duplicat = $this->getDoctrine()->getRepository('FecdasBundle:EntityDuplicat')->find($duplicatid);
		
		if ($duplicat != null && $duplicat->getCarnet() != null && $producte = $duplicat->getCarnet()->getProducte() != null) {
			$producte = $duplicat->getCarnet()->getProducte();

			$data = $this->getCurrentDate();
			$maxNumFactura = $this->getMaxNumEntity($data->format('Y'), BaseController::FACTURES) + 1;
			$maxNumRebut = $this->getMaxNumEntity($data->format('Y'), BaseController::REBUTS) + 1;

			$detallsBaixa = array();
			$detallsBaixa[] = $this->removeComandaDetall($duplicat, $producte, 1);	
			
			$this->crearFacturaRebutAnulacio($this->getCurrentDate(), $duplicat, $detallsBaixa, $maxNumFactura, $maxNumRebut);
			
			$em->flush();
		
			$this->get('session')->getFlashBag()->add('sms-notice', 'Petició de duplicat anul·lada correctament');
			
			$this->logEntryAuth('ANULA DUPLI OK', 'duplicat ' . $duplicatid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error anulant la petició');

			$this->logEntryAuth('ANULA DUPLI ERROR', 'duplicat ' . $duplicatid);
		}
		
		return $this->forward('FecdasBundle:Page:duplicats');
		//return $this->redirect($this->generateUrl('FecdasBundle_duplicats'));
	}
	
	public function imprespeticioAction(Request $request) {
		/* Marca petició duplicat com impressa i enviar un correu */
	
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
	
		$em = $this->getDoctrine()->getManager();

		$duplicatid = $request->query->get("id");
	
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'd.datapeticio');
		$direction = $request->query->get('direction', 'desc');
	
		$duplicat = $this->getDoctrine()->getRepository('FecdasBundle:EntityDuplicat')->find($duplicatid);
	
		if ($duplicat != null) {
			$duplicat->setDataimpressio($this->getCurrentDate());
	
			$em->flush();
	
			// Enviar notificació mail
			$fedeMail = array();
			if ($duplicat->getCarnet()->esLlicencia() == true) $fedeMail = $this->getLlicenciesMails(); // Llicències Remei
			else $fedeMail = $this->getCarnetsMails(); // Carnets Albert
			
			if ($duplicat->getClub()->getMail() != null) {
				$subject = "Petició de duplicat. " . $duplicat->getCarnet()->getTipus();
				$tomails = array($duplicat->getClub()->getMail());
				$bccmails = $fedeMail;
			} else {
				$subject = "Petició de duplicat. " . $duplicat->getCarnet()->getTipus() . " CLUB SENSE CORREU!! ";
				$tomails = $fedeMail;
				$bccmails = array();
			}
			
			$body = "<p>Benvolgut club ".$duplicat->getClub()->getNom()."</p>";
			$body .= "<p>Us fem saber que la petició de duplicat per ";
			$body .= "<strong>".$duplicat->getPersona()->getNom() . " " . $duplicat->getPersona()->getCognoms() . "</strong> (<i>".$duplicat->getTextCarnet()."</i>),";
			$body .= " ha estat impresa i es pot passar a recollir per la Federació.</p>";
			
			$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
			
			$this->get('session')->getFlashBag()->add('sms-notice', 'S\'ha enviat un mail al club');
				
			$this->logEntryAuth('PRINT DUPLI OK', 'duplicat ' . $duplicatid);
		} else {
			$this->get('session')->getFlashBag()->add('error-notice', 'Error indicant impressió de la petició');
	
			$this->logEntryAuth('PRINT DUPLI ERROR', 'duplicat ' . $duplicatid);
		}
	
		return $this->redirect($this->generateUrl('FecdasBundle_duplicats', array('sort' => $sort,'direction' => $direction, 'page' => $page)));
	}
	
	public function duplicatllicenciaAction(Request $request) {
		/* Anular petició duplicat */
				
		if ($this->isCurrentAdmin() != true)
			return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		
		$em = $this->getDoctrine()->getManager();
		
		$llicenciaid = $request->query->get("id");
		
		$llicencia = $this->getDoctrine()->getRepository('FecdasBundle:EntityLlicencia')->find($llicenciaid);
		
		$producte = $this->getDoctrine()->getRepository('FecdasBundle:EntityProducte')->findOneByCodi(BaseController::CODI_DUPLICAT_LLICENCIA);
		
		$duplicat = null;
		$detall = null;
		
		try {
		
			if ($llicencia == null) throw new \Exception('Llicència '.$llicenciaid.' no trobada' );
			
			if ($producte == null) throw new \Exception('Producte '.BaseController::CODI_DUPLICAT_LLICENCIA.' no trobat' );
			
			$carnet = $this->getDoctrine()->getRepository('FecdasBundle:EntityCarnet')->findOneByProducte($producte);
			
			if ($carnet == null) throw new \Exception('Tipus de duplicat: '.$producte->getDescripcio().', no trobat' );
			
			$persona = $llicencia->getPersona();
			
			$duplicat = $this->crearComandaDuplicat('Petició duplicat de llicència '.$persona->getNomCognoms(), $llicencia->getParte()->getClub());
			
			$duplicat->setPersona($persona);
			$duplicat->setCarnet($carnet);
					
			$detall = $this->addDuplicatDetall($duplicat);
			
			// Si tot Ok, obrir pdf per imprimir	
			$duplicat->setDataimpressio($this->getCurrentDate());

			$em->flush();
			
			$this->logEntryAuth('DUPLI LLICENCIA OK', 'duplicat ' . $duplicat->getNumComanda() . ' de la llicència ' . $llicenciaid  );
			
			$response = $this->redirect($this->generateUrl('FecdasBundle_imprimirllicencia', array( 'id' => $llicenciaid)));
			
		} catch (\Exception $e) {
			
			if ($duplicat != null) $em->detach($duplicat);
			if ($detall != null) $em->detach($detall);
			
			$this->logEntryAuth('DUPLI LLICENCIA KO', 'duplicat de la llicència ' . $llicenciaid  );
					
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
		}
		
		return $response;;
	}
	
	
	public function ajaxclubsnomsAction(Request $request) {
		$search = $this->consultaAjaxClubs($request->get('term'));
		$response = new Response();
		$response->setContent(json_encode($search));
	
		return $response;
	}
	
}
