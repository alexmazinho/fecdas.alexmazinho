<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\FormError;

use FecdasBundle\Form\FormCurs;
use FecdasBundle\Form\FormPersona;
use FecdasBundle\Classes\Funcions;
use FecdasBundle\Entity\EntityCurs;
use FecdasBundle\Entity\EntityDocencia;
use FecdasBundle\Entity\EntityStock;
use FecdasBundle\Entity\EntityTitulacio;
use FecdasBundle\Entity\EntityTitol;
use FecdasBundle\Entity\EntityMetaPersona;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityLlicencia;


class TitulacionsController extends BaseController {
	
	public function dadesfederatsAction(Request $request) {
		// Llista de membres del club amb les dades personals
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
		
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'e.cognoms');
		$direction = $request->query->get('direction', 'asc');
		$format = $request->query->get('format', '');
		$currentDNI = $request->query->get('dni', '');
		$currentNom = $request->query->get('nom', '');
		$currentCognoms = $request->query->get('cognoms', '');
		$currentMail = $request->query->get('mail', '');
		$currentProfessio = $request->query->get('professio', '');
		$currentClub = $request->query->get('clubs', '');
		$currentTitol = $request->query->get('titols', '');
		$currentTitolExtern = $request->query->get('titolsexterns', '');
		$titol = null;
		if ($currentTitol != '') $titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($currentTitol);
		$titolExtern = null;
		if ($currentTitolExtern != '') $titolExtern = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($currentTitolExtern);
		
		$desde = $request->query->get('desde', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->query->get('desde')):null;
		$fins = $request->query->get('fins', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->query->get('fins')):null;
		
		$currentDNI = trim($currentDNI);
		$currentNom = trim($currentNom);
		$currentCognoms = trim($currentCognoms);
		$currentMail = trim($currentMail);
		$currentProfessio = trim($currentProfessio);
		
		$currentVigent = false;
		if ($request->query->has('vigent') && $request->query->get('vigent') == 1) $currentVigent = true;
		    
		$club = $this->getCurrentClub(); // Admins poden cerca tots els clubs
		if ($this->isCurrentAdmin()) {
			if ($currentClub != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($currentClub);
			else $club = null;
		}
			
		// Permetre enviar llicències per mail global d'un club
		$enviarLlicencies = false;
		//if ($currentVigent && $currentClub != '') {
		if ($club != null) {
		    if ($this->isCurrentAdmin() || 
		        ($this->isCurrentClub() && $this->getCurrentClub()->getEnviarllicencia()))  {
		        
		        $enviarLlicencies = true;
		    }
		}
		
		
		$this->logEntryAuth('VIEW PERSONES CLUB', ($format != ''?$format:'')."club: " . $currentClub." ".$currentNom.", ".$currentCognoms . "(".$currentDNI. ") ".
		                                            " mail: ".$currentMail." professio: ".$currentProfessio." ".
													"des de ".($desde != null?$desde->format('Y-m-d'):'--')." fins ".($fins != null?$fins->format('Y-m-d'):'--').
													" titol ".$currentTitol." altres ". $currentTitolExtern);
			
		$query = $this->consultaDadesfederats($currentDNI, $currentNom, $currentCognoms, $currentMail, $currentProfessio, $club, $desde, $fins, $currentVigent, $titol, $titolExtern, $sort.' '.$direction);
		
		
		if ($format == 'mail') {
		    // Obrir form enviament llicencies per mail
		    return $this->forward('FecdasBundle:Page:llicenciespermailbulk', array(
		        'persones'        => $query->getResult(),
		        'club'            => $club
		    ));
		}
		
		if ($format == 'csv') {
			// Generar CSV
			return $this->exportDadesfederats($request, $query->getResult(), $desde, $fins);
		}
		
		if ($format == 'pdf') {
			// Generar PDF
			$print = $request->query->has('print') && $request->query->get('print') == true?true:false;
			
			return $this->forward('FecdasBundle:PDF:dadesfederatstopdf', array(
							        'persones'       => $query->getResult(),
							        'print' 		=> $print,
							        'desde'			=> $desde,
							        'fins'			=> $fins,
							        'vigents'		=> $currentVigent,
							        'dni'			=> $currentDNI,
							        'nom'			=> $currentNom,
							        'cognoms'		=> $currentCognoms,
			                        'mail'          => $currentMail,
			                        'professio'     => $currentProfessio
		    ));
		}
		
		$paginator  = $this->get('knp_paginator');
		$persones = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		); 
		/* Paràmetres URL sort i pagination */
		$persones->setParam('desde',$desde!=null?$desde->format('d/m/Y'):'');
		$persones->setParam('fins',$fins!=null?$fins->format('d/m/Y'):'');
		if ($currentDNI != '') $persones->setParam('dni',$currentDNI);
		if ($currentNom != '') $persones->setParam('nom',$currentNom);
		if ($currentCognoms != '') $persones->setParam('cognoms',$currentCognoms);
		if ($currentMail != '') $persones->setParam('mail',$currentMail);
		if ($currentProfessio != '') $persones->setParam('professio',$currentProfessio);
		if ($currentVigent == true) $persones->setParam('vigent',true);
		if ($currentClub != '') $persones->setParam('club',$club);
		
		$formBuilder = $this->createFormBuilder()->add('dni', 'search', array('required'  => false, 'data' => $currentDNI)); 
		$formBuilder->add('nom', 'search', array('required'  => false, 'data' => $currentNom));
		$formBuilder->add('cognoms', 'search', array('required'  => false, 'data' => $currentCognoms));
		$formBuilder->add('mail', 'search', array('required'  => false, 'data' => $currentMail));
		$formBuilder->add('professio', 'text', array('required'  => false, 'data' => $currentProfessio));
		$formBuilder->add('vigent', 'checkbox', array('required'  => false, 'data' => $currentVigent));
		$formBuilder->add('desde', 'text', array('required'  => false, 'data' => ($desde != null?$desde->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--', 'readonly' => false)));
		$formBuilder->add('fins', 'text', array('required'  => false, 'data' => ($fins != null?$fins->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--', 'readonly' => false)));

		$this->addClubsActiusForm($formBuilder, $club);
		
		$this->addTitolsFilterForm($formBuilder, $titol, true, 'titols');
		
		$this->addTitolsFilterForm($formBuilder, $titolExtern, false, 'titolsexterns');
		
		$form = $formBuilder->getForm(); 
	
		return $this->render('FecdasBundle:Titulacions:dadesfederats.html.twig',
		      $this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'persones' => $persones, 'enviarLlicencies' => $enviarLlicencies,
						'sortparams' => array('sort' => $sort,'direction' => $direction)) 
						));

	}
	
	private function exportDadesfederats($request, $persones, $desde, $fins) {
		/* CSV Llistat de dades personals filtrades */
		$filename = "export_dadesfederats_".BaseController::getInfoTempsNomFitxer($desde, $fins).".csv";
			
		$header = EntityPersona::csvHeader( $this->isCurrentAdmin(), BaseController::getInfoTempsNomFitxer($desde, $fins, " ", "/") );
			
		$data = array(); // Get only data matrix
		$i = 1;
		
		foreach ($persones as $persona) {
			$data[] = $persona->csvRow($i, $this->isCurrentAdmin(), $desde != null?$desde->format("Y-m-d"):'', $fins != null?$fins->format("Y-m-d"):'');
			$i ++; 
		}
			
		$response = $this->exportCSV($request, $header, $data, $filename);
		return $response;
	}
	
	private function exportLlicencies($request, $llicencies) {
	    /* CSV Llistat de llicències filtrades */
	    $filename = "export_historial_llicencies_".date("Ymd").".csv";
	    
	    $header = EntityLlicencia::csvHeader();
	    
	    $data = array(); // Get only data matrix
	    $i = 1;
	    foreach ($llicencies as $llicencia) {
	        $data[] = $llicencia->csvRow($i);
	        $i ++;
	    }
	    
	    $response = $this->exportCSV($request, $header, $data, $filename);
	    return $response;
	}
	
	private function exportTitulacions($request, $titulacions, $altrestitulacions) {
	    /* CSV Llistat de titulacions filtrades */
	    $filename = "export_historial_titulacions_".date("Ymd").".csv";
	    
	    $header = EntityTitulacio::csvHeader();
	    
	    $data = array(); // Get only data matrix
	    $i = 1;
	    foreach ($titulacions as $titulacio) {
	        $data[] = $titulacio->csvRow($i);
	        $i ++;
	    }
	    foreach ($altrestitulacions as $titol) {
	        $data[] = $titol->csvRow($i);
	        $i ++;
	    }
	    
	    $response = $this->exportCSV($request, $header, $data, $filename);
	    return $response;
	}
	
	private function exportCursos($request, $cursos, $club, $titol, $cerca, $desde, $fins) {
	    /* CSV Llistat de cursos filtrats */
	    $filename = "export_cursos";
	    if ($club != null) $filename .= "_".Funcions::netejarPath($club->getNom());
	    if ($titol != null) $filename .= "_".Funcions::netejarPath($titol->getTitol());
	    if ($cerca != null) $filename .= "_".Funcions::netejarPath($cerca);
	    if ($desde != null || $fins != null) $filename .= "_".BaseController::getInfoTempsNomFitxer($desde, $fins);
	    
	    $filename .= ".csv";
	    
	    $header = EntityCurs::csvHeader( );
	    
	    $data = array(); // Get only data matrix
	    $i = 1;
	    
	    foreach ($cursos as $curs) {
	        $data[] = $curs->csvRow($i);
	        $i++;
	    }
	    
	    $response = $this->exportCSV($request, $header, $data, $filename);
	    return $response;
	}
	
	
	public function llicenciesfederatAction(Request $request) {
	    
	    if ($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest(), true)) return $redirect;
	    
	    $checkRole = $this->get('fecdas.rolechecker');
	    $user = $checkRole->getCurrentUser();

	    $metapersona = $user->getMetapersona();
	    
	    $page = $request->query->get('page', 1);
	    $sort = $request->query->get('sort', 'datacaducitat');
	    $direction = $request->query->get('direction', 'desc');
	    $format = $request->query->get('format', '');
	    $currentVigent = false;
	    if ($request->query->has('vigent') && $request->query->get('vigent') == 1) $currentVigent = true;
	    $currentClub = $request->query->get('club', '');
	    $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($currentClub);
	    $total = 0;
	    $llicencies = array();
	    $llicenciesPaginated = array();
	    $response = null;
	    
	    $clubs = $metapersona->getClubs();
	    if (count($clubs) == 1 && $clubs[0]->getCodi() == BaseController::CODI_CLUBLLICWEB) {
	        // Ocultar club Inde F
	        $formBuilder = $this->createFormBuilder();
	    } else {
    	    $formBuilder = $this->createFormBuilder()->add(
    	        'club', 'entity', array(
    	            'class' 	   => 'FecdasBundle:EntityClub',
    	            'choices'      => $metapersona->getClubs(),
    	            'choice_label' => 'nom',
    	            'placeholder'  => '',	// Important deixar en blanc pel bon comportament del select2
    	            'required'     => false,
    	            'data'         => ($club != null?$club:null)
    	        ));
    	    
    	    $formBuilder->add('vigent', 'checkbox', array('required'  => false, 'data' => $currentVigent));
	    }
	    
	    try {
	        foreach ($metapersona->getLlicenciesSortedByDate(false, true) as $llicencia) { // Incloure llicències pendents de pagament tramitades per l'usuari
                $parte = $llicencia->getParte();
                
                if ((!$currentVigent || $llicencia->isVigent()) &&  
                    (!$parte->getPendent() || $parte->comandaUsuari()) &&
                    ($currentClub == '' || $currentClub == $parte->getClub()->getCodi())) {
                        
                        $llicencies[] = $llicencia;
                }
	        }
	        $total = count($llicencies);

	        EntityLlicencia::getLlicenciesSortedBy($llicencies, $sort, $direction);
	        
	        $paginator  = $this->get('knp_paginator');
	        
	        $llicenciesPaginated = $paginator->paginate(
	            $llicencies,
	            $page,
	            5/*limit per page*/
	            );
	        
	        if ($format == 'csv') {
	            // Generar CSV
	            $this->logEntryAuth('LLICENCIES FEDERAT CSV', $user->getUser().': '
	                .$page.','.$sort.','.$direction.','.$currentVigent.','.$currentClub);
	            return $this->exportLlicencies($request, $llicencies);
	        }
	        
	        if ($format == 'pdf') {
	            // Generar PDF
	            $this->logEntryAuth('LLICENCIES FEDERAT PDF', $user->getUser().': '
	                .$page.','.$sort.','.$direction.','.$currentVigent.','.$currentClub);
	            	            
	            return $this->forward('FecdasBundle:PDF:llicenciesfederattopdf', array(
	                'llicencies'    => $llicencies,
	                'metapersona' 	=> $metapersona
	            ));
	        }
	        
	        $this->logEntryAuth('LLICENCIES FEDERAT', $user->getUser().': '
	                           .$page.','.$sort.','.$direction.','.$currentVigent.','.$currentClub);

	        if ($request->isXmlHttpRequest()) {
    	        return $this->render('FecdasBundle:Titulacions:llicenciesfederatdades.html.twig',
    	            $this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
    	                'metapersona' => $metapersona, 'llicencies' => $llicenciesPaginated, 'total' => $total,
    	                'sortparams' => array('sort' => $sort,'direction' => $direction) 
    	                )));
	        }
	        return $this->render('FecdasBundle:Titulacions:llicenciesfederat.html.twig',
	            $this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
	                'metapersona' => $metapersona, 'llicencies' => $llicenciesPaginated, 'total' => $total,
	                'sortparams' => array('sort' => $sort,'direction' => $direction)
	            )));
	        
	    } catch (\Exception $e) {

	        // Ko, mostra form amb errors
	        $this->logEntryAuth('LLICENCIES FEDERAT ERROR', $user->getUser().': '
	            .$e->getMessage().'('.$page.','.$sort.','.$direction.','.$currentVigent.','.$currentClub.')');
	        
	        if ($request->isXmlHttpRequest()) {
	            $response = new Response($e->getMessage());
	            $response->setStatusCode(500);
	            
	        } else {
    	        $this->get('session')->getFlashBag()->clear();
    	        $this->get('session')->getFlashBag()->add('error-notice', $e->getMessage());

    	        $response = $this->render('FecdasBundle:Titulacions:llicenciesfederat.html.twig',
    	            $this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
    	                'metapersona' => $metapersona, 'llicencies' => $llicenciesPaginated, 'total' => $total,
    	                'sortparams' => array('sort' => $sort,'direction' => $direction))
    	                ));
	        }
	    }
	    return $response;
	}
	
	public function titulacionsfederatAction(Request $request) {
	    
	    if ($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest(), true)) return $redirect;
	    
	    $checkRole = $this->get('fecdas.rolechecker');
	    $user = $checkRole->getCurrentUser();
	    
	    $metapersona = $user->getMetapersona();
	        
	    $page = $request->query->get('page', 1);
	    $tab = $request->query->get('tab', 'cmas');    //  cmas o altres
	    $sort = $request->query->get('sort', 'datasuperacio');
	    $direction = $request->query->get('direction', 'desc');
	    $format = $request->query->get('format', '');
	    $currentClub = $request->query->get('club', '');
	    $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($currentClub);
	    $total = 0;
	    $totalaltres = 0;
	    $titulacions = array();
	    $titulacionsPaginated = array();
	    $altrestitulacions = array();
	    $altrestitulacionsPaginated = array();
	    $response = null;
	    
	    $clubs = $metapersona->getClubs();
	    if (count($clubs) == 1 && $clubs[0]->getCodi() == BaseController::CODI_CLUBLLICWEB) {
	        // Ocultar club Inde F
	        $formBuilder = $this->createFormBuilder();
	    } else {
    	    $formBuilder = $this->createFormBuilder()->add(
    	                'club', 'entity', array(
    	                'class' 	   => 'FecdasBundle:EntityClub',
    	                'choices'      => $clubs,
    	                'choice_label' => 'nom',
    	                'placeholder'  => '',	// Important deixar en blanc pel bon comportament del select2
    	                'required'     => false,
    	                'data'         => ($club != null?$club:null)
    	    ));
	    }
    	    
	    try {
	        foreach ($metapersona->getTitulacionsSortedByDate() as $titulacio) {
	            if ($currentClub == '' || 
	               ($titulacio->getCurs()->getClub() != null &&
	                $currentClub == $titulacio->getCurs()->getClub()->getCodi())) $titulacions[] = $titulacio;
	        }
	        $total = count($titulacions);
	        
	        $paginator  = $this->get('knp_paginator');
	        
	        EntityTitulacio::getTitulacionsSortedBy($titulacions, $sort, $direction);

	        $titulacionsPaginated = $paginator->paginate(
	            $titulacions,
	            $page,
	            5/*limit per page*/
	        );
	        $titulacionsPaginated->setParam('tab', 'cmas');
	        
	        $altrestitulacions = $metapersona->getAltresTitulacionsSortedByTitol();
	        
	        $totalaltres = count($altrestitulacions);

	        EntityTitol::getTitolsSortedBy($altrestitulacions, $sort, $direction);
	        
	        $altrestitulacionsPaginated = $paginator->paginate(
	            $altrestitulacions,
	            $page,
	            5/*limit per page*/
	        );
	        $altrestitulacionsPaginated->setParam('tab', 'altres');
	        
	        if ($format == 'csv') {
	            // Generar CSV
	            $this->logEntryAuth('TITULACIONS FEDERAT CSV', $user->getUser().': '
	                        .$page.','.$sort.','.$direction.','.$currentClub);
	            return $this->exportTitulacions($request, $titulacions, $altrestitulacions);
	        }
	                
	        if ($format == 'pdf') {
	            // Generar PDF
	            $this->logEntryAuth('TITULACIONS FEDERAT PDF', $user->getUser().': '
	                        .$page.','.$sort.','.$direction.','.$currentClub);
	                    
	            return $this->forward('FecdasBundle:PDF:titulacionsfederattopdf', array(
	                        'titulacions'          => $titulacions,
	                        'altrestitulacions'    => $altrestitulacions,
	                        'metapersona' 	       => $metapersona
	            ));
	        }
	                
	        $this->logEntryAuth('TITULACIONS FEDERAT', $user->getUser().': '
	                    .$page.','.$sort.','.$direction.','.$currentClub);
	                
	        if ($request->isXmlHttpRequest()) {
	            if ($tab == 'cmas') {
	               return $this->render('FecdasBundle:Titulacions:titulacionsfederatdades.html.twig',
	                    $this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
	                        'metapersona' => $metapersona, 
	                        'total' => $total,
	                        'totalaltres' => $totalaltres,
	                        'titulacions' => $titulacionsPaginated,
	                        'altrestitulacions' => $altrestitulacionsPaginated,
	                        'sortparams' => array('tab'=> $tab, 'sort' => $sort,'direction' => $direction)
	                    )));
	            } else {
	                return $this->render('FecdasBundle:Titulacions:titulacionsfederataltres.html.twig',
	                    $this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
	                        'metapersona' => $metapersona,
	                        'total' => $total,
	                        'totalaltres' => $totalaltres,
	                        'titulacions' => $titulacionsPaginated,
	                        'altrestitulacions' => $altrestitulacionsPaginated,
	                        'sortparams' => array('tab'=> $tab, 'sort' => $sort,'direction' => $direction)
	                    )));
	            }
	        }
	        return $this->render('FecdasBundle:Titulacions:titulacionsfederat.html.twig',
	                    $this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
	                        'metapersona' => $metapersona, 
	                        'total' => $total,
	                        'totalaltres' => $totalaltres,
	                        'titulacions' => $titulacionsPaginated,
	                        'altrestitulacions' => $altrestitulacionsPaginated,
	                        'sortparams' => array('tab'=> $tab, 'sort' => $sort,'direction' => $direction)
	                    )));
	                
	    } catch (\Exception $e) {
	                
	         // Ko, mostra form amb errors
	        $this->logEntryAuth('TITULACIONS FEDERAT ERROR', $user->getUser().': '
	                    .$e->getMessage().'('.$page.','.$sort.','.$direction.','.$currentClub.')');
	                
	         if ($request->isXmlHttpRequest()) {
	              $response = new Response($e->getMessage());
	              $response->setStatusCode(500);
	                    
	         } else {
	              $this->get('session')->getFlashBag()->clear();
	              $this->get('session')->getFlashBag()->add('error-notice', $e->getMessage());
	                   
	              $response = $this->render('FecdasBundle:Titulacions:titulacionsfederat.html.twig',
	                        $this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
	                            'metapersona' => $metapersona, 
	                            'total' => $total,
	                            'totalaltres' => $totalaltres,
	                            'titulacions' => $titulacionsPaginated, 
	                            'altrestitulacions' => $altrestitulacionsPaginated, 
	                            'sortparams' => array('tab'=> $tab, 'sort' => $sort,'direction' => $direction))
	              ));
	         }
	     }
	     return $response;
	}
	
	public function dadespersonalsAction(Request $request) {
	    
	    $checkRole = $this->get('fecdas.rolechecker');
	    $user = $checkRole->getCurrentUser();
	    
	    if ($user == null || !$user->isPendentDadesPersonals()) {
	        if ($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest(), true)) return $redirect;
	    }
	    
	    $metapersona = $user->getMetapersona();
	    
	    $em = $this->getDoctrine()->getManager();

	    $options = array();
	    /* Get provincies, comarques, nacions*/
	    $options['edit'] = false;
	    $options['provincies'] = $this->getProvincies();
	    $options['comarques'] = $this->getComarques();
	    $options['nacions'] = $this->getNacions();
	    
	    $persona = null;
	    if ($metapersona == null) {
	        $clubs = $user->getClubsRole(BaseController::ROLE_FEDERAT);
	        if (count($clubs) == 0) {
	            $clubs[] = $em->getRepository('FecdasBundle:EntityClub')->findOneByCodi(BaseController::CODI_CLUBLLICWEB);
	        }
	        
	        $metapersona = new EntityMetaPersona();
	        $metapersona->setUsuari($user);
	        $user->setMetapersona($metapersona);
	        $persona = new EntityPersona($metapersona, $clubs[0]);
	        $persona->setMail($user->getUser());
	        $em->persist($persona);
	        $em->persist($metapersona);
	        
	        $options['editdni'] = true;
	    } else {
	        $options['editdni'] = false;
    	    $persona = $metapersona->getUltimesDadesPersonals();
	    }
	    
	    $formpersona = $this->createForm(new FormPersona($options), $persona);
	    
	    try {
	        if ($request->getMethod() == 'POST') {
	            
	            $formpersona->handleRequest($request);
	            
	            if (!$formpersona->isValid())  throw new \Exception("Les dades no són vàlides: ".$formpersona->getErrors(true, true).", poseu-vos en contacte amb la Federació.");
	                
	            $this->validarDadesPersona($persona, $options['editdni'], $formpersona);

	            if ($persona->getMail() == null || $persona->getMail() == "") $persona->setMail($user->getUser());
	            
	            if (!$this->validateMailsContainsUser($persona->getMails(), $user->getUser())) {
                    // Al mail mínim ha d'estar l'usuari d'accés
	                $persona->setMail($user->getUser().';'.$persona->getMail());
	            }

	            // Fer els canvis a la resta de persones associades a la metapersona
	            $foto = $formpersona->get('fotoupld')->getData();
	            $this->gestionarArxiuPersona($persona, false, $foto, true); 
	            
	            $arxiu = $formpersona->get('arxiuupld')->getData();
	            $this->gestionarArxiuPersona($persona, false, $arxiu);
	            
	            $em->flush();
	            
	            $this->get('session')->getFlashBag()->add('sms-notice',	"Dades personals actualitzades correctament");
	            $this->logEntryAuth('DADES FEDERAT UPD OK', 'metapersona '. $metapersona->getDni().', persona '. $persona->getId());
	            
	            return $this->redirect($this->generateUrl('FecdasBundle_dadespersonals'));
	        }

	    } catch (\Exception $e) {
	        if (!$metapersona->nova()) $em->refresh($metapersona);
	        if (!$persona->nova()) $em->refresh($persona);

	        $this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
	        
	        if (!$metapersona->nova() && !$persona->nova()) {
    	        $this->logEntryAuth('DADES FEDERAT UPD KO', 'error: '.$e->getMessage(). '. Metapersona '. $metapersona->getDni().', persona '. $persona->getId());
	        }
	    }
	    
	    //$formpersona = $this->createForm(new FormPersona($options), $persona);
	    
	    return $this->render('FecdasBundle:Titulacions:dadespersonals.html.twig',
	        $this->getCommonRenderArrayOptions(array('formpersona' => $formpersona->createView(), 'persona' => $persona) ) 
	    );
	}
	
	public function esborrararxiufederatAction(Request $request) {
	    
	    $personaId = $request->query->get('persona', 0);
	    try {
	        if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;
	        
	        $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($personaId);
	        
	        if ($persona == null) throw new \Exception('Dades personals no trobades, poseu-vos en contacte amb la Federació');
	        
	        if ($this->isCurrentAdmin()) {
	            // Ok
	            
	        } else {
	            $checkRole = $this->get('fecdas.rolechecker');
	            
	            if ($checkRole->isCurrentClub()) {
	               // Accés Club
	               if ($persona->getClub() != $this->getCurrentClub()) throw new \Exception('El club no disposa de permisos per realitzar aquesta acció');
	               
	            } else {
	                // Accés federat o instructor
	                $checkRole = $this->get('fecdas.rolechecker');
	                
	                if (!$checkRole->isCurrentFederat() && !$checkRole->isCurrentInstructor())
	                    throw new \Exception('El rol actual de l\'usuari no disposa de permisos per realitzar aquesta acció');
	                
	                $user = $checkRole->getCurrentUser();
	                    
	                // Persona diferent
	                if ($user == null || $persona->getMetapersona() != $user->getMetapersona()) throw new \Exception('L\'usuari no disposa de permisos per realitzar aquesta acció');
	            }
	        }
	        
	        $em = $this->getDoctrine()->getManager();
	        
	        $foto = $request->query->get('foto', 0);
	        $foto = $foto==1?true:false;
	        $certificat = $request->query->get('foto', 0);
	        $certificat = $certificat==1?true:false;
	        $arxiu = null;
	        if (!$foto && !$certificat) {
	           $arxiuId = $request->query->get('arxiu', 0);

	           $arxiu = $this->getDoctrine()->getRepository('FecdasBundle:EntityArxiu')->find($arxiuId);
	           
	           if ($arxiu == null) throw new \Exception('Arxiu no trobat, poseu-vos en contacte amb la Federació');
	           
	           // Fitxer no és de la persona
	           if ($arxiu->getPersona() != $persona) throw new \Exception('L\'arxiu no es correspon amb aquestes dades personals');
	        }
	        
	        $this->gestionarArxiuPersona($persona, true, $arxiu, $foto, $certificat);
	        
	        $em->flush();
	        
	        $this->logEntryAuth('DEL FILE OK', 'persona '. $persona->getId().($foto?", delete foto":"").($certificat?", delete certificat":"").($arxiu!=null?", arxiu ".$arxiu->getId():""));
	        
	    } catch (\Exception $e) {
	        $this->logEntryAuth('DEL FILE NO ACCESS', $e->getMessage());
	        
	        $response = new Response($e->getMessage());
	        $response->setStatusCode(500);
	        return $response;
	    }
	    
	    // Ok
	    return new Response();
	}
	
	
	public function historialllicenciesAction(Request $request) {
		
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

		if (!$request->query->has('id')) return new Response("");
		
		//$em = $this->getDoctrine()->getManager();
				
		$id = $request->query->get('id');
			
		if ($this->isCurrentAdmin()) {
			/* !!!!!!!!!!!! Administradors historia de tots els clubs per DNI !!!!!!!!!!!!!!!!!!!! */
			/*$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
			$strQuery .= " WHERE p.dni = :dni ";
			$strQuery .= " AND p.databaixa IS NULL ";
				
			$query = $em->createQuery($strQuery)->setParameter('dni', $persona->getDni()); 
			$persones = $query->getResult();

			$llicencies = array();
			foreach ($persones as $persona_iter) {
				$llicencies = array_merge($llicencies, $persona_iter->getLlicenciesSortedByDate(true, true));  // Incloure baixes
			}
			*/
			/* Ordenades de última a primera 
			 * SELECT e.dni, COUNT(DISTINCT p.clubparte) FROM m_partes p 
			 * INNER JOIN m_llicencies l ON p.id = l.parte 
			 * INNER JOIN m_persones e ON l.persona = e.id 
			 * GROUP BY e.dni HAVING COUNT(DISTINCT p.clubparte) > 1
			 * */
			 
			 $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->find($id);
		} else {
			 $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($id);
		}
		
		if (!$persona) return new Response("");
		
		$llicencies = $persona->getLlicenciesSortedByDate($this->isCurrentAdmin(), false); // Incloure baixes
		
		// Ordre
		usort($llicencies, function($a, $b) {
			if ($a === $b) {
				return 0;
			}
			return ($a->getParte()->getDatacaducitat() > $b->getParte()->getDatacaducitat())? -1:1;;
		});

		return $this->render('FecdasBundle:Titulacions:llicencieshistorial.html.twig', array('llicencies' => $llicencies, 'persona' => $persona, 'admin' => $this->isCurrentAdmin()));
		
	}

	public function historialtitulacionsAction(Request $request) {
		
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

		if (!$request->query->has('id')) return new Response("");
		
		//$em = $this->getDoctrine()->getManager();
				
		$id = $request->query->get('id');
		
		if ($this->isCurrentAdmin()) {
			 $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->find($id);
		} else {
			 $persona = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($id);
		}
			
		if (!$persona) return new Response("");
		
		$titulacions = $persona->getTitulacionsSortedByDate( $this->isCurrentAdmin() );  // Administradors veuen baixes
		
		$altrestitulacions = $persona->getAltrestitulacions();
		
		return $this->render('FecdasBundle:Titulacions:titulacionshistorial.html.twig', 
				array('titulacions' => $titulacions, 'altrestitulacions' => $altrestitulacions, 
				    'club' => $this->getCurrentClub(), 'persona' => $persona, 'admin' => $this->isCurrentAdmin()));
		
	}

	public function cursosAction(Request $request) {
		// Llista de cursos
	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

		$checkRole = $this->get('fecdas.rolechecker');
		$rol = $checkRole->getCurrentRole();
		
		if (!$checkRole->isCurrentAllowedCursos()) {
		    $this->logEntryAuth('VIEW CURSOS NOT ALLOWED', "rol ".$rol);
		    return $this->redirect($this->generateUrl('FecdasBundle_homepage')); 
		}

		$format = $request->query->get('format', '');
		$page = $request->query->get('page', 1);
		$sort = $request->query->get('sort', 'c.datadesde');
		$direction = $request->query->get('direction', 'desc');
		
		$cerca = $request->query->get('participant', '');
		$currentClub = $request->query->get('clubs', ''); // Admin filtra club
		
		$club = $this->getCurrentClub(); // Admins poden cerca tots els clubs

		if ($this->isCurrentAdmin() || $checkRole->isCurrentInstructor()) {
			if ($currentClub != '') $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($currentClub);
			else $club = null;
		}
		
		$desde = $request->query->get('desde', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->query->get('desde')):null;
		if ($desde == null) $desde = \DateTime::createFromFormat('Y-m-d', date("Y") . "-01-01");
		
		$fins = $request->query->get('fins', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->query->get('fins')):null;

		$currentTitol = $request->query->get('titols', '');
		$titol = null;
		if ($currentTitol != '') $titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($currentTitol);
		
		$currentEstat = $request->query->get('estat', 0);
		
		$this->logEntryAuth('VIEW CURSOS', "rol ".$rol. " club: " . $currentClub." "." titol ".$currentTitol.
											"des de ".($desde != null?$desde->format('Y-m-d'):'--')." fins ".($fins != null?$fins->format('Y-m-d'):'--'));
		
		$query = $this->consultaCursos($rol, $checkRole->getCurrentUser(), $club, $titol, $cerca, $desde, $fins, $currentEstat, $sort.' '.$direction);
		
		
		if ($format == 'csv') {
		    // Generar CSV
		    return $this->exportCursos($request, $query->getResult(), $club, $titol, $cerca, $desde, $fins);
		}
		
		if ($format == 'pdf') {
		    // Generar PDF
		    return $this->forward('FecdasBundle:PDF:cursostopdf', array(
		        'cursos'        => $query->getResult(),
		        'club' 		    => $club,
		        'titol' 		=> $titol,
		        'alumne' 		=> $cerca,
		        'desde'			=> $desde,
		        'fins'			=> $fins,
		        'estat'         => $currentEstat
		    ));
		}
		
		
		$paginator  = $this->get('knp_paginator');
		$cursos = $paginator->paginate(
				$query,
				$page,
				10 /*limit per page*/
		); 

		$formBuilder = $this->createFormBuilder()->add('estat', 'choice', array(
		    'choices'   => BaseController::CURS_ESTATS,
		    'preferred_choices' => array(0),  // tots
		    'data' => $currentEstat
		));
		$formBuilder->add('desde', 'text', array('required'  => false, 'data' => ($desde != null?$desde->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--', 'readonly' => false)));
		$formBuilder->add('fins', 'text', array('required'  => false, 'data' => ($fins != null?$fins->format('d/m/Y'):''), 'attr' => array( 'placeholder' => '--', 'readonly' => false)));
		$formBuilder->add('participant', 'text', array('required'  => false, 'data' => $cerca, 'attr' => array( 'placeholder' => 'Alumne: dni, nom o mail', 'readonly' => false)));
		if ($this->isCurrentAdmin()) $this->addClubsActiusForm($formBuilder, $club);
		else {
		    if ($checkRole->isCurrentInstructor()) {
		        $formBuilder->add('clubs', 'entity', array(
		            'class' 		=> 'FecdasBundle:EntityClub',
		            'choices'       => $checkRole->getCurrentUser()->getClubsRole(BaseController::ROLE_INSTRUCTOR),
		            'choice_label' 	=> 'nom',
		            'placeholder' 	=> '',	// Important deixar en blanc pel bon comportament del select2
		            'required'  	=> false,
		            'data' 			=> $club,
		        ));
		    }
		}
		
		$this->addTitolsFilterForm($formBuilder, $titol, true, 'titols');
		
		return $this->render('FecdasBundle:Titulacions:cursos.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $formBuilder->getForm()->createView(),
						'cursos' => $cursos, 'sortparams' => array('sort' => $sort,'direction' => $direction))
				));
	}

	public function cursAction(Request $request) {
		// Consulta/edicio curs 

	    if($redirect = $this->frontEndLoginCheck($request->isXmlHttpRequest())) return $redirect;

		$checkRole = $this->get('fecdas.rolechecker');
		$rol = $checkRole->getCurrentRole();
			
		if (!$checkRole->isCurrentAllowedCursos()) {
		    $this->logEntryAuth('VIEW CURS NOT ALLOWED', "rol ".$rol);
		    return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
		}
			
    	$em = $this->getDoctrine()->getManager();
    	
    	$curs = null;
		/*$auxdirector = null;
		$auxcarnet = null;
		$auxcodirector = null;
		$auxcocarnet = null;
		$participants = null;
		$instructors = null;
		$collaboradors = null;*/
		$action = '';
		$titol = null;
		$kit = null;
		$stock = '';  // stock desconegut
		$requeriments = array( 'titol' => '', 'errors' => array( 'total' => 0 ));
		
		//$participantscurrent = null;
		$club = null;
		$codi = '';
    	if ($request->getMethod() != 'POST') {
    		$id = $request->query->get('id', 0);
			$action = $request->query->get('action', '');
			$codi = $request->query->get('club', '');
			
			if ($id == 0 && !$checkRole->isCurrentInstructor() && !$this->isCurrentAdmin() && !$this->isCurrentClub()) {
				$this->get('session')->getFlashBag()->add('error-notice', 'Només els instructors poden crear un nou curs');
				return $this->redirect($this->generateUrl('FecdasBundle_cursos'));
			}
			
			if ($codi == '' && $this->isCurrentClub()) {
			    $club = $this->getCurrentClub();
			    $codi = $club->getCodi();
			}
			
    	} else {
   			/* Alta o modificació de preus */
    		$data = $request->request->get('curs');
    		$id = (isset($data['id'])?$data['id']:0);
			$action = (isset($data['action'])?$data['action']:'');
			$codi = (isset($data['club'])?$data['club']:'');
    	}

    	if ($id > 0) $curs = $this->getDoctrine()->getRepository('FecdasBundle:EntityCurs')->find($id);

		if ($codi != '' && $club == null && $curs == null) $club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find($codi);
		
    	if ($curs == null) {
    		$this->logEntryAuth('CURS NOU',	($request->getMethod() != 'POST'?'GET':'POST').' action: '.$action);

    		if ($club == null) {
    		    $this->get('session')->getFlashBag()->add('error-notice', 'Cal escollir un club');
    		    return $this->redirect($this->generateUrl('FecdasBundle_cursos'));
    		}
    		
    		$maxNumCurs = $this->getMaxNumEntity($this->getCurrentDate()->format('Y'), BaseController::CURSOS) + 1;
    		
    		//$curs = new EntityCurs($checkRole->getCurrentUser(), $maxNumCurs, $this->getCurrentDate(), $this->getCurrentDate(), $club);
    		// Sense indicar les dates per obligar a que les indiquin els instructors 
    		$curs = new EntityCurs($checkRole->getCurrentUser(), $maxNumCurs, null, null, $club);
    		$em->persist($curs);
    		
    		if ($checkRole->isCurrentInstructor()) {
    			$this->novaDocenciaCurs($checkRole->getCurrentUser()->getMetapersona(), array(), $curs, BaseController::DOCENT_DIRECTOR);
    	    }
    	} else {
    	    $club = $curs->getClub();

	    	$this->logEntryAuth($request->getMethod() != 'POST'?'CURS VIEW':'CURS EDIT', ($request->getMethod() != 'POST'?'GET':'POST').' curs : ' . $curs->getId().' '.$curs->getTitol().' '.$curs->getClubInfo());

			if (!$curs->finalitzat()) {			
				$titol = $curs->getTitol();
				if ($titol != null && $titol->getKit() != null) $kit = $titol->getKit();
				
				if ($kit != null) {
				    $stock = $this->consultaStockClubPerProducteData($kit, $club); // stock disponible
				}
			}
    	}

		if ($request->getMethod() == 'POST') {
		    if ($curs->editable()) $this->initDadesPostCurs($request->request->get('curs'), $curs);
		}

		$form = $this->createForm(new FormCurs( array('currentuser' => $checkRole->getCurrentUser(), 'admin' => $this->isCurrentAdmin(), 'stock' => $stock )), $curs);
    	try {
    		if ($request->getMethod() == 'POST') {

    		    // Refer numeració seqüencial docencies pq handle funciona malament si s'esborra una docencia del mig
    		    $arrayInstructors = array();
    		    if (isset($data['instructors'])) {
    		        foreach ($data['instructors'] as $instructor) {
    		            $arrayInstructors[] = $instructor;
    		        }
    		    }
    		    $data['instructors'] = $arrayInstructors;
    		    
    		    $arrayCollaboradors = array();
    		    if (isset($data['collaboradors'])) {
    		        foreach ($data['collaboradors'] as $collaborador) {
    		            $arrayCollaboradors[] = $collaborador;
    		        }
    		    }
    		    $data['collaboradors'] = $arrayCollaboradors;
    		    
    		    $request->request->set('curs', $data);
    		    
    		    
    		    // Handle no gestiona bé les dades del formulari
			 	$form->handleRequest($request);
			 	
			 	if (!$form->isValid()) throw new \Exception('Dades del formulari incorrectes '.$form->getErrors(true, true) );

				// Comprovacions genèriques
				$this->validacionsCurs($curs, $stock, $form, $action);

				// Fotos i arxius
				foreach ($form->get('participants') as $formparticipant) {
					$idMeta = $formparticipant->get('metapersona')->getData();
					$fotoPath = $formparticipant->get('foto')->getData();
					$certificatPath = $formparticipant->get('certificat')->getData();
					$foto = $formparticipant->get('fotoupld')->getData();
					$certificat = $formparticipant->get('certificatupld')->getData();
					
					
					$participant = $curs->getParticipantByMetaId($idMeta);
					if ($participant == null || $participant->getMetapersona() == null) throw new \Exception('Alumne no trobat '.$idMeta);
					$persona = $participant->getMetapersona()->getPersona($curs->getClub());

					$fotoTitulacio = $this->gestionarFotoPersona($persona, $fotoPath, $foto);
					$certificatTitulacio = $this->gestionarCertificatPersona($persona, $certificatPath, $certificat);
					
					if ($fotoTitulacio != null) $participant->setFoto($fotoTitulacio);
					if ($certificatTitulacio != null) $participant->setCertificat($certificatTitulacio);
				}

				$this->accionsPostValidacions($curs, $action);
				
				$curs->setDatamodificacio(new \DateTime('now'));
				
	    		$em->flush();

	    		$this->get('session')->getFlashBag()->add('sms-notice',	'Canvis desats correctament');
    			
    			return $this->redirect($this->generateUrl('FecdasBundle_curs', 
    					array( 'id' => $curs->getId() )));
    		} else {
	   			
				if ($action == 'remove') {
				    
				    // Comprovacions genèriques
				    $this->validacionsCurs($curs, $stock, $form, $action);
				    
				    // Baixa curs, alumnes i docents. Restaura kits si escau 
				    $this->accionsPostValidacions($curs, $action);
				    
				    $em->flush();
				    
				    $this->get('session')->getFlashBag()->add('sms-notice',	'Curs esborrat correctament');
				    
				    return $this->redirect($this->generateUrl('FecdasBundle_cursos'));
				}
	   		}
		} catch (\Exception $e) {
    		// Ko, mostra form amb errors
    		$this->get('session')->getFlashBag()->add('error-notice',	$e->getMessage());
    	}
  	
    	//if (!$curs->finalitzat() && $action != 'remove') {
    	if ($action != 'remove') {
			// Resultat check Requeriments titulació
			$resultat = $this->comprovaRequerimentsCurs($curs);
			// Dades estructurades requeriments
			$requeriments = $this->getRequerimentsEstructuraInforme($curs->getTitol(), $resultat);
		}
		return $this->render('FecdasBundle:Titulacions:curs.html.twig',
				$this->getCommonRenderArrayOptions(array('form' => $form->createView(), 'curs' => $curs, 'requeriments' => $requeriments)));
	}

	private function initDadesPostCurs($data, $curs) {
		if (isset($data['auxdirector']) && isset($data['auxdirector']) > 0) $auxdirector = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($data['auxdirector']);
		$auxcarnet = isset($data['auxcarnet'])?$data['auxcarnet']:'';
		
		if (isset($data['auxcodirector']) && isset($data['auxcodirector']) > 0) $auxcodirector = $this->getDoctrine()->getRepository('FecdasBundle:EntityPersona')->find($data['auxcodirector']);
		$auxcocarnet = isset($data['auxcocarnet'])?$data['auxcocarnet']:'';
			
		$instructors = isset($data['instructors'])?$data['instructors']:array();
		$collaboradors = isset($data['collaboradors'])?$data['collaboradors']:array();;
		$participants = isset($data['participants'])?$data['participants']:array();
		
		$currentDocencia = $curs->getDirector();
		if ($auxdirector != null && $auxdirector->getMetapersona() != null) {
		    $meta = $auxdirector->getMetapersona();
			$currentMeta = ($currentDocencia == null?null:$currentDocencia->getMetadocent());
			$docent = array('carnet' => $auxcarnet);
			
			if ($meta !== $currentMeta) {
				if ($currentDocencia != null) $currentDocencia->baixa();
                $this->novaDocenciaCurs($meta, $docent, $curs, BaseController::DOCENT_DIRECTOR);
			} else {
                $this->updateDocenciaCurs($docent, $currentDocencia);
			}
		} else {
			// Esborrar?
			if ($currentDocencia != null) $currentDocencia->baixa();
		}
			
		$currentDocencia = $curs->getCodirector();
		if ($auxcodirector != null && $auxcodirector->getMetapersona() != null) {
			$meta = $auxcodirector->getMetapersona();
			$currentMeta = ($currentDocencia == null?null:$currentDocencia->getMetadocent());
			$docent = array('carnet' => $auxcocarnet);
			
			if ($meta !== $currentMeta) {
				if ($currentDocencia != null) $currentDocencia->baixa();
				$this->novaDocenciaCurs($meta, $docent, $curs, BaseController::DOCENT_CODIRECTOR);
			} else {
			    $this->updateDocenciaCurs($docent, $currentDocencia);
			} 
		} else {
			// Esborrar?
			if ($currentDocencia != null) $currentDocencia->baixa(); 
		}
			
		$currentInstructorsIds = $curs->getDocenciesIds(BaseController::DOCENT_INSTRUCTOR);
		foreach ($instructors as $docent) {		// Afegir/treure/modificar instructors
			$index = array_search(isset($docent['id'])?$docent['id']:0, $currentInstructorsIds);
			if ($index !== false) {
			    array_splice($currentInstructorsIds, $index, 1);		// Treu els existents de l'array
			}
				
			$this->gestionarDocenciaCurs($docent, $curs, BaseController::DOCENT_INSTRUCTOR);		
		}

		$currentCollaboradorsIds = $curs->getDocenciesIds(BaseController::DOCENT_COLLABORADOR);
		foreach ($collaboradors as $docent) { 	// Afegir/treure/modificar colaboradors
			$index = array_search(isset($docent['id'])?$docent['id']:0, $currentCollaboradorsIds);
			if ($index !== false) array_splice($currentCollaboradorsIds, $index, 1);		// Treu els existents de l'array
			
			$this->gestionarDocenciaCurs($docent, $curs, BaseController::DOCENT_COLLABORADOR);
		}
		
		$docenciesIdsEsborrar = array_merge($currentInstructorsIds, $currentCollaboradorsIds);
		foreach ($docenciesIdsEsborrar as $id) {  // Esborrar
			$docencia = $curs->getDocenciaById($id);
			if ($docencia != null) {
			    $docencia->baixa();	
			}
		} 		
			
		$currentParticipantsIds = $curs->getParticipantsIds();
		foreach ($participants as $participant) {	// Afegir/treure/modificar participants
			$index = array_search(isset($participant['id'])?$participant['id']:0, $currentParticipantsIds);
			if ($index !== false) array_splice($currentParticipantsIds, $index, 1);		// Treu els existents de l'array
		
			$this->gestionarParticipacioCurs($participant, $curs);
		}

		foreach ($currentParticipantsIds as $id) {  // Esborrar
			$participant = $curs->getParticipantById($id);
			if ($participant != null) $participant->baixa();
		} 		
	} 
	
	
	private function gestionarDocenciaCurs($docent, $curs, $rol) { 
		$meta = isset($docent['metadocent'])?$docent['metadocent']:0;
		$id = isset($docent['id'])?$docent['id']:0;
		
		if ($id != 0) {
			$docencia = $curs->getDocenciaByMetaId($meta);
		
			if ($docencia == null) throw new \Exception('No existeix l\'instructor '.$meta );
			
			$this->updateDocenciaCurs($docent, $docencia);
			$docencia->setRol( $rol ); 
			
		} else {
			$metadocent = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->find($meta);	
			if ($metadocent == null) throw new \Exception('Instructor no trobat '.$meta );
			$this->novaDocenciaCurs($metadocent, $docent, $curs, $rol);
		}	
	}
	
	private function updateDocenciaCurs($docent, $docencia) {
	    $carnet = isset($docent['carnet'])?$docent['carnet']:'';
        if ($carnet != '') $docencia->setCarnet( $carnet );

		if ($docencia->esInstructor()) {
    		$docencia->setHteoria( isset($docent['hteoria'])&&is_numeric($docent['hteoria'])?$docent['hteoria']:0 );
    		$docencia->setHaula( isset($docent['haula'])&&is_numeric($docent['haula'])?$docent['haula']:0 );
    		$docencia->setHpiscina( isset($docent['hpiscina'])&&is_numeric($docent['hpiscina'])?$docent['hpiscina']:0 );
    		$docencia->setHmar( isset($docent['hmar'])&&is_numeric($docent['hmar'])?$docent['hmar']:0 );
		}
		if ($docencia->esCollaborador()) {
		    $docencia->setIpiscina( isset($docent['ipiscina'])&&is_numeric($docent['ipiscina'])?$docent['ipiscina']:0 );
		    $docencia->setImar( isset($docent['imar'])&&is_numeric($docent['imar'])?$docent['imar']:0 );
		}
	}
	
	private function novaDocenciaCurs($metadocent, $docent = array(), $curs, $rol) {
		$em = $this->getDoctrine()->getManager();
		
		$docencia = new EntityDocencia($metadocent, $curs, $rol);
		
		$this->updateDocenciaCurs($docent, $docencia); 			

		$curs->addDocencia($docencia);
		$metadocent->addDocencia($docencia);
		
		$em->persist($docencia);
		
		return $docencia;
	}

	
	private function consultarNumCarnetDocent($metadocent) {

	    if ($metadocent == null) return null;
	    /* Num. carnet major disponible */
	    /* Obtenir num carnet instructor superior */
	    /* Bussejador 1 Estrella => B1E  48 */
	    /* Bussejador 2 Estrelles => B2E  50 */
	    /* Bussejador 3 Estrelles => B3E  51 */
	    /* Bussejador 4 Estrella => B4E 243  */
	    /* Instructor 1 Estrella => I1E  53 */
	    /* Instructor 2 Estrelles => I2E  54 */
	    /* Instructor 3 Estrelles => I3E  55 */
	    /* Director => requeriment 300 titols suficients o 301 títols necessaris */
	    /* Profe teòriques => requeriment 302 titols suficients o 303 títols necessaris */
	    /* Profe pràctiques => requeriment 304 titols suficients o 305 títols necessaris */
	    /* Busse. seguretat => requeriment 306 titols suficients o 307 títols necessaris */

	    foreach (BaseController::getTitolsCercaNumCarnets() as $titol) {
	        $titulacions = $metadocent->getTitulacionsByTitolId($titol);
            if (count($titulacions) > 0) {
                return $titulacions[0]->getNumfedas()!=''?$titulacions[0]->getNumfedas():$titulacions[0]->getNumTitulacio();
            }
	    }
	    return null;
	}
	
	private function gestionarParticipacioCurs($participant, $curs) { 
		$em = $this->getDoctrine()->getManager();
		$id = isset($participant['metapersona'])?$participant['metapersona']:0;
		
		$titulacio = $curs->getParticipantByMetaId($id);
		if ($titulacio == null) {
			$metapersona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->find($id);	
			if ($metapersona == null) throw new \Exception('Alumne no trobat '.$id );

			$titulacio = new EntityTitulacio($metapersona, $curs);	
	
			$em->persist($titulacio);
			$curs->addParticipant($titulacio);
			$metapersona->addTitulacions($titulacio);
		}
	}

	
	private function validacionsCurs($curs, $stock, $form, $action) {
		$checkRole = $this->get('fecdas.rolechecker');
		
		if ($action == 'save' 	&& !$this->isCurrentAdmin() && !$checkRole->isCurrentInstructor() && !$checkRole->isCurrentClub()) throw new \Exception('Només els instructors o el club poden desar les dades del curs');
		if ($action == 'close' 	&& !$this->isCurrentAdmin() && !$checkRole->isCurrentInstructor() && !$checkRole->isCurrentClub()) throw new \Exception('Només els instructors o el club poden tancar el curs');
		
		if ($action == 'unclose' 	&& !$this->isCurrentAdmin() && !$checkRole->isCurrentClub()) throw new \Exception('Només els clubs poden tornar a obrir el curs per editar-lo');
		if ($action == 'validate' 	&& !$this->isCurrentAdmin() && !$checkRole->isCurrentClub()) throw new \Exception('Només els clubs poden confirmar la validesa de les dades del curs');
		
		if ($action == 'finalize' 	&& !$this->isCurrentAdmin()) throw new \Exception('Només des de la Federació es pot finalitzar el curs');
		
		if ($action == 'remove' 	&& !$this->isCurrentAdmin()) throw new \Exception('Només els administradors poden esborrar el curs' );
		
		if ($action == 'remove') {
		    if ($curs->esNou())  throw new \Exception('No s\'ha pogut esborrar el curs' );
		    
		    return;
		}
		
		// Generals 
		$titol = $curs->getTitol();
		if ($titol == null) {
			$form->get('titol')->addError(new FormError('Obligatori'));
			throw new \Exception('Cal escollir el títol que s\'impartirà en aquest curs');
		}
		$desde = $curs->getDatadesde();
		$fins = $curs->getDatafins();
		if ($desde == null || $fins == null) throw new \Exception('Cal indicar les dates d\'inici i final del curs');  // Per validar llicència tècnic 

		if ($fins->format('Y-m-d') < $desde->format('Y-m-d')) throw new \Exception('La data d\'inici del curs ha de ser anterior o igual a la data de finalització');
		
		// Validar instructor repetit
		$director = $curs->getDirector();
		$codirector = $curs->getCodirector();
		if ($director == null) {
			$form->get('auxdirector')->addError(new FormError('Obligatori'));
			throw new \Exception('Cal indicar un director per al curs');
		} 
		
		$this->validaCarnetDocent($director, $form->get('auxcarnet'));
		if ($codirector != null) {
		    if ($codirector->getMetadocent() === $director->getMetadocent()) {
        		$form->get('auxcodirector')->addError(new FormError('Duplicat'));
			    throw new \Exception('El director i el co-director no poden ser el mateix');
		    }
		    
		    $this->validaCarnetDocent($codirector, $form->get('auxcocarnet'));
		}
		// Director / Co-director poden ser instructors també?

		$docentIds = array();
		$docenciesInstructors = $curs->getDocentsByRole(BaseController::DOCENT_INSTRUCTOR);
		
		foreach ($docenciesInstructors as $docencia) {
		    $child = $this->getFormDocencia('instructors', $docencia, $form);
		    
		    $this->validaCarnetDocent($docencia, $child!=null?$child->get('carnet'):null);
		    
			$meta = $docencia->getMetadocent();
			if (in_array($meta->getId(), $docentIds)) throw new \Exception('L\'instructor '.$meta->getNomCognoms().' està repetit');
			$docentIds[] = $meta->getId();

			$this->validaDocenciaHoresImmersions($docencia);
			
			// Validació llicència federativa tècnic vigent 
			$finsVariable = clone $fins;
			$llicenciesPersonaPeriode = $meta->getLlicenciesSortedByDate(false, false, $desde, $fins); /* Ordenades de última a primera */
	
			foreach ($llicenciesPersonaPeriode as $llicencia) {
				// Totes les llicencies haurien d'estar parcialment dins el periode
				if (!$llicencia->esTecnic()) throw new \Exception('L\'instructor '.$meta->getDni().' no té llicència tècnic');
				
				$parte = $llicencia->getParte();
								
				if ($parte->getDatacaducitat()->format('Y-m-d') >= $finsVariable->format('Y-m-d') && 
					$parte->getDataalta()->format('Y-m-d') 		<= $finsVariable->format('Y-m-d')) {
							 			
					$finsVariable = clone $parte->getDataalta();
					$finsVariable->sub(new \DateInterval('P1D')); // Minus 1 Day
				} 
			}

			// Si $fins <= $desde està tot el periode cobert
			if ($finsVariable->format('Y-m-d') > $desde->format('Y-m-d')) throw new \Exception('L\'instructor '.$meta->getDni().' no té llicència tècnic durant tot el periode del curs');
		}
		
		$docentIds = array();
		$docenciesCollaboradors = $curs->getDocentsByRole(BaseController::DOCENT_COLLABORADOR);
		foreach ($docenciesCollaboradors as $docencia) {
		    $child = $this->getFormDocencia('collaboradors', $docencia, $form);
		    
		    $this->validaCarnetDocent($docencia, $child!=null?$child->get('carnet'):null);
			
		    $meta = $docencia->getMetadocent();
			if (in_array($meta->getId(), $docentIds)) throw new \Exception('El col·laborador '.$meta->getNomCognoms().' està repetit');
			$docentIds[] = $meta->getId();
			
			$this->validaDocenciaHoresImmersions($docencia);
		}
		
		// Validar alumne repetit
		$alumnesIds = array();
		$participants = $curs->getParticipantsSortedByCognomsNom();
		foreach ($participants as $participant) {
			$meta = $participant->getMetapersona();
			if (in_array($meta->getId(), $alumnesIds)) throw new \Exception('L\'alumne '.$meta->getNomCognoms().' està repetit');
			$alumnesIds[] = $meta->getId();
		}

		// Valida stock
		if ($titol->esKitNecessari() && !$this->isCurrentAdmin()) {
			$kit = $titol->getKit();
			// El club no té prous kits per tots els alumnes del curs. No es pot validar
			if ($action == 'validate' && count($participants) > $stock) throw new \Exception('El club no disposa de prou kits "'.$kit->getDescripcio().'" per a tots els alumnes. Cal demanar-ne més per poder validar el curs ');
		}
		
	}

	private function getFormDocencia($tipus = 'instructors', $docencia, $form) {
	    if (!$form->has($tipus)) return null;
	    
	    foreach ($form->get($tipus)->all() as $child) {
	        if ($docencia === $child->getData()) {
	            return $child;
	        }
	    }
	    return null;
	}
	
	
	private function validaCarnetDocent($docencia, $field) {
	    if ($docencia == null) return;

	    if ($docencia->getCarnet() == null || trim($docencia->getCarnet()) == "") {
	        if ($field != null) $field->addError(new FormError('Obligatori'));
	        throw new \Exception('Cal indicar el número d\'instructor ('.$docencia->getRol().')');
	    }
	}
	
	private function validaDocenciaHoresImmersions($docencia) {
		if ($docencia == null) return;
		
		$rol = '';
		if ($docencia->esInstructor()) {
		    $rol = 'instructors';
		    if ($docencia->getHteoria() < 0) throw new \Exception('Les hores de teoria dels '.$rol.' no poden ser negatives');
		    if ($docencia->getHaula() < 0) throw new \Exception('Les hores de pràctiques fora de l\'aigua dels '.$rol.' no poden ser negatives');
		    if ($docencia->getHpiscina() < 0) throw new \Exception('Les hores de pràctiques a aigües confinades dels '.$rol.' no poden ser negatives');
		    if ($docencia->getHmar() < 0) throw new \Exception('Les hores de pràctiques a la mar dels '.$rol.' no poden ser negatives');
		}
		if ($docencia->esCollaborador()) {
		    $rol = 'col·laboradors';
		    if ($docencia->getIpiscina() < 0) throw new \Exception('Les immersions a aigües confinades dels '.$rol.' no poden ser negatives');
		    if ($docencia->getImar() < 0) throw new \Exception('Les immersions a la mar dels '.$rol.' no poden ser negatives');
		}
	}

	private function accionsPostValidacions($curs, $action) {
		
		$current = new \DateTime('now');
		$club = $curs->getClub();
		$titol = $curs->getTitol();
			
		switch ($action) {
			case 'save':	// Instructor.
				$curs->setEditable(true);
				$curs->setValidat(false);
				$curs->setFinalitzat(false);
				
				break;
	
			case 'close':	// Instructor -> club
			
				$curs->setEditable(false);
				$curs->setValidat(false);
				$curs->setFinalitzat(false);
				
				if (count($curs->getParticipantsSortedByCognomsNom()) == 0) throw new \Exception('No es pot tancar el curs sense cap alumne');
				
				// Enviar mail al club
				$subject = "Federació Catalana d'Activitats Subaquàtiques. Curs pendent de validació ";
				$bccmails = array( $this->getParameter('MAIL_FECDAS') );
				$tomails = $club->getMails();
				if (count($tomails) == 0) {
				    $subject .= ' (Cal avisar aquest club no té adreça de mail al sistema)';
				    $tomails = array( $this->getParameter('MAIL_FECDAS') );
				    $bccmails = array();
				}
				
				
				$body = "<p>Benvolgut club ".$club->getNom()."</p>";
				$body .= "<p>Les dades d'un nou curs han estat introduïdes per un dels instructors capacitats a tal efecte, ";
				$body .= "i resta pendent de la teva validació per notificar-lo a la Federació</p>";
				$body .= "<p>Curs: <b>".$curs->getTitol()->getLlistaText()."</b></p>";
				
				$this->buildAndSendMail($subject, $tomails, $body, $bccmails);
						
				break;
	
			case 'unclose':		// club -> instructor 
				
			    if (!$curs->tancat()) throw new \Exception('Aquest curs no es pot tornar a editar, poseu-vos en contacte amb la Federació');
				$curs->setEditable(true);
				$curs->setValidat(false);
				$curs->setFinalitzat(false);
				
				// Enviar mail a l'instructor ¿?	
				break;
					
			case 'validate':	// Club  -> federació
				
				$curs->setEditable(false);		
				$curs->setValidat(true);	
				$curs->setFinalitzat(false);
				
				
				// Registrar sortida de kits
				if ($titol->esKitNecessari()) {
					$kit = $titol->getKit();
					
					$stock = $this->consultaStockClubPerProducteData($kit, $club); // stock disponible

					$unitats = count($curs->getParticipantsSortedByCognomsNom());
					
					if ($stock < $unitats) {
					    if (!$this->isCurrentAdmin()) throw new \Exception('No hi ha prou kits "'.$kit->getDescripcio().'" disponibles per a tots els alumnes. Cal demanar-ne més per poder validar el curs ');
					} else {
					    $em = $this->getDoctrine()->getManager();
					    
					    $comentaris = 'Tramitació curs '.$curs->getNumActa().'. '.$unitats.'x'.$kit->getDescripcio();
					    
					    $registreStockClub = new EntityStock($club, $kit, $unitats, $comentaris, new \DateTime('today'), BaseController::REGISTRE_STOCK_SORTIDA, null, $curs);
					    
					    $em->persist($registreStockClub);
					    
					    $curs->setStock($registreStockClub);
					}
				}
				
				// Enviar mail a Federació
				$club = $curs->getClub();
				
				$subject = ":: Nou curs validat :: ";
				$tomails = array($this->getParameter('MAIL_FECDAS'));  
				$body = "<p>Hola</p>";
				$body .= "<p>El club ".$club->getNom()." ha validat les dades d'un nou curs en data ".$current->format('d/m/Y');
				$body .= "<p>Curs: <b>".$curs->getTitol()->getLlistaText()."</b></p>";
				
				$this->buildAndSendMail($subject, $tomails, $body);
						
				break;
					
			case 'finalize':	// Federació
				
				$curs->setEditable(false);		
				$curs->setValidat(true);	
				$curs->setFinalitzat(true);
				
				$maxNumTitulacio = $this->getMaxNumEntity($curs->getDatafins()->format('Y'), BaseController::TITULACIONS) + 1;
				
				foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {	
				    // Finalitzar. Calcular núm alumne i data superació
				    $participant->setDatasuperacio($curs->getDatafins());
				    $participant->setNum($maxNumTitulacio);
				    $maxNumTitulacio++;
				}
				
				break;
			
			case 'remove':	// Federació
			    
				// Esborrar alumnes
			    foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {
			        $participant->setDatabaixa($this->getCurrentDate());
			    }

				// Esborrar docents: director, co-director, instructors i col·laboradors
			    foreach ($curs->getDocentsByRoleSortedByCognomsNom() as $docent) {
			        $docent->setDatabaixa($this->getCurrentDate());
			    }
				
			    $stock = $curs->getStock();
			    if ($stock != null) $stock->setDatabaixa($this->getCurrentDate());

			    $curs->setDatabaixa($this->getCurrentDate());
			    
			    break;
				
			default:
				throw new \Exception('Acció incorrecte '.$action );
						
						
				break;
		}
	}

	private function comprovaRequerimentsCurs($curs) {
		// Generals
		$titol =  $curs->getTitol();
		$resultat = array();  
		//if ($titol == null) throw new \Exception('Cal escollir el títol que s\'impartirà per poder comprovar els requeriments');
		if ($titol == null) return $resultat;
		
		$requeriments = $titol->getRequerimentsSortedByContextCategoria();
		 
		foreach ($requeriments as $requeriment) {
			$tipus = $requeriment->getRequeriment();
			
			if (isset($resultat[$tipus->getId()])) throw new \Exception('Error de configuració a la comprovació dels requeriments'); 
			
			$res = array('result' => 'OK', 'errors' => array());
			switch ($tipus->getCategoria()) {
				case BaseController::CATEGORIA_REQUERIMENT_MIN_HORES:
					$res = $this->comprovaRequerimentsMinimHores($requeriment, $curs);
					
					break;

				case BaseController::CATEGORIA_REQUERIMENT_IMMERSIONS:
				    $res = $this->comprovaRequerimentsImmersions($requeriment, $curs);  // No es poden comprovar
					
					break;
				
				case BaseController::CATEGORIA_REQUERIMENT_RATIOS:
					$res = $this->comprovaRequerimentsRatios($requeriment, $curs);
					
					break;
				
				case BaseController::CATEGORIA_REQUERIMENT_FORMACIO:
					$res = $this->comprovaRequerimentsFormacio($requeriment, $curs);
					
					break;
				
				case BaseController::CATEGORIA_REQUERIMENT_TITULACIONS:
					$res = $this->comprovaRequerimentsTitulacions($requeriment, $curs);
					
					break;
				
				default:
					// Altres validacions categoria NULL
					$res = $this->comprovaRequerimentsAltres($requeriment, $curs);
					
					break;
			}
			
			if ($res['result'] != 'OK') $resultat[$tipus->getId()] = $res;
		}
		return $resultat;
	}

	private function comprovaRequerimentsMinimHores($requeriment, $curs) {
		$tipus = $requeriment->getRequeriment();	
			
		$res = array('result' => 'OK', 'errors' => array());   
	
		$text = $requeriment->getText();
		//$text = substr($text, 0, strpos($text, ":"));
		
		switch ($tipus->getId()) {
			case 100:  // teoria.
			case 101:  // aula.
			case 102:  // piscina.
			case 103:  // mar.
			
				$horesMin = $requeriment->getValor();
				
				// Recompte total hores instructors 
				$totals = array(100 => 0, 101 => 0, 102 => 0, 103 => 0);
				foreach ($curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR) as $instructor) {
					$totals[100] += $instructor->getHteoria();
					$totals[101] += $instructor->getHaula();
					$totals[102] += $instructor->getHpiscina();
					$totals[103] += $instructor->getHmar();
				}
				
				if ($totals[$tipus->getId()] <  $horesMin) {
				    $res['errors'][] = $text.'. El total d\'hores <span>'.$totals[$tipus->getId()].'</span> és inferior al mínim ('.$horesMin.')';
				}
				break;
				
			case 104:	// funcio docent
				
			    $res['errors'][] = $text.'. 104 - No es pot comprovar per manca de dades ';
					
				break;	
			case 105:	// Experiència docent (Escola Nacional de Busseig Autònom Esportiu)

			    $res['errors'][] = $text.'. 105 - No es pot comprovar per manca de dades ';
					
				break;	
				
			default:

				break;
		}
		
		if (count($res['errors']) > 0) $res['result'] = 'KO';
		
		return $res; 
	}

	private function comprovaRequerimentsImmersions($requeriment, $curs) {
	    $tipus = $requeriment->getRequeriment();
	    
	    $res = array('result' => 'OK', 'errors' => array());
	    
	    $text = $requeriment->getText();
	    
	    switch ($tipus->getId()) {
	        case 120:	// Immersiona Piscina
	        case 121:	// Immersions Mar
	            
	            $immersionsMin = $requeriment->getValor();
	            
	            // Recompte total hores instructors
	            $totals = array(120 => 0, 121 => 0);
	            foreach ($curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLLABORADOR) as $colaborador) {
	                $totals[120] += $colaborador->getIpiscina();
	                $totals[121] += $colaborador->getImar();
	            }
	            
	            if ($totals[$tipus->getId()] <  $immersionsMin) {
	                $res['errors'][] = $text.'. El total d\'immersions <span>'.$totals[$tipus->getId()].'</span> és inferior al mínim ('.$immersionsMin.')';
	            }
	            
	            break;

	        case 122:	// Fent funció
	            
	            // No es pot comprovar
	            
	            break;
	        default:
	            
	            break;
	    }
	    
	    if (count($res['errors']) > 0) $res['result'] = 'KO';
	    
	    return $res;
	}
	
	private function comprovaRequerimentsRatios($requeriment, $curs) {
		$tipus = $requeriment->getRequeriment();	
			
		$res = array('result' => 'OK', 'errors' => array());
		
		$valors = explode(";",$requeriment->getValor());
		$valor0 = isset($valors[0])?$valors[0]:''; // Alumnes 
		$valor1 = isset($valors[1])?$valors[1]:''; // Professors
		$valor2 = isset($valors[2])?$valors[2]:''; // Bussejador seguretat

		// Obtenir valors relacionats
		$titol =  $curs->getTitol();
		
		$alumnes = count($curs->getParticipantsSortedByCognomsNom());
		
		if ($alumnes > 0 && is_numeric($valor0) && $valor0 > 0) {
			// Recompte instructors 
			$profeTeoria = 0;
			$profePiscina = 0;
			$profeMar = 0;
			$profeAula = 0;
			foreach ($curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR) as $instructor) {
				if ($instructor->getHteoria() > 0) $profeTeoria++;
				if ($instructor->getHaula() > 0) $profeAula++;
				if ($instructor->getHpiscina() > 0) $profePiscina++;
				if ($instructor->getHmar() > 0) $profeMar++;
			}
			
			$seguretatPiscina = 0;
			$seguretatMar = 0;
			$immersionsPiscina = $titol->getRequerimentByNum(120);   // 120 immersions piscina
			$immersionsPiscina = $immersionsPiscina != null?$immersionsPiscina->getValor():0;  
			$immersionsMar = $titol->getRequerimentByNum(121);   // 121 immersions mar
			$immersionsMar = $immersionsMar != null?$immersionsMar->getValor():0;
			if ($valor2 != '' && $valor2 > 0) {
				foreach ($curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLLABORADOR) as $instructor) {
				    if ($instructor->getIpiscina() >= $immersionsPiscina) $seguretatPiscina++;  // Col·laborador fa tantes o més immersions que les necessàries
				    if ($instructor->getImar() >= $immersionsMar) $seguretatMar++;               // Si fa menos de les necessàries no compta per a ratio
				}
			}
			
			$profes = 0;
			$seguretat = 0;		
			$idReqHores = 0;
			switch ($tipus->getId()) {
				case 150:  // Ratio teoria
					$profes = $profeTeoria;
					$seguretat = 0;
					$idReqHores = 100; //100 min hores teoria
					
					break;
				case 151:  // Ratio piscina
					$profes = $profePiscina;
					$seguretat = $seguretatPiscina;
					$idReqHores = 102; //102 min hores piscina
					
					break;
				case 152:  // Ratio mar
				case 153:  // Ratio mar recomanat
					$profes = $profeMar;
					$seguretat = $seguretatMar;	
					$idReqHores = 103; //103 min hores mar
					
					break;
				
				case 154:  // Ratio aula
					$profes = $profeAula;
					$seguretat = 0;
					$idReqHores = 101; //101 min hores fora d'aigua
					
					break;
				default:
						
					break;
			}
		
			$text = $requeriment->getText();
			//$text = substr($text, 0, strpos($text, ":"));
			
			
			$reqHores = $titol->getRequerimentByNum($idReqHores);
			$reqHores = $reqHores != null?$reqHores->getValor():0;   
			if ($reqHores > 0 || $profes > 0) {
    			if (($profes/$alumnes) < ($valor1 / $valor0)) $res['errors'][] = $text.'. Ratio professor/alumnes <span>'.$profes.'/'.$alumnes.'</span> inferior al valor requerit '.$valor1.'/'.$valor0; 
    			
    			if (is_numeric($valor2) && $valor2 > 0) {
    				$aux = 'requerit';
    				if ($tipus->getId() == 153) $aux = 'recomanat';
    				
    				if (($seguretat/$alumnes) < ($valor2 / $valor0)) $res['errors'][] = $text.'. Ratio bussejador seguretat/alumnes <span>'.$seguretat.'/'.$alumnes.'</span> inferior al valor '.$aux.' '.$valor2.'/'.$valor0;
    			}
			}
			
			if (count($res['errors']) > 0) $res['result'] = 'KO';
		}
		
		return $res;
	}

	private function comprovaRequerimentsFormacio($requeriment, $curs) {
		$tipus = $requeriment->getRequeriment();	
		$valors = explode(";",$requeriment->getValor());
		
		$text = $requeriment->getText();
		//$text = substr($text, 0, strpos($text, ":"));
		
		$res = array('result' => 'OK', 'errors' => array());
		
		switch ($tipus->getId()) {
			case 201:  // Títols alumne suficients
			    foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {
			        $metapersona = $participant->getMetapersona();
			        
			        $resultat = $this->comprovarTitulacionsSuficients($metapersona, $valors);
			        if ($resultat != '') $res['errors'][] = $text.'. Alumne amb DNI '.$metapersona->getDni().', '.$resultat.'; suficients per fer el curs. ';
			    }
			    
			    break; 
			    
			case 202:  // Títols alumne necessaris
			    foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {
			        $metapersona = $participant->getMetapersona();
			        
			        $resultat = $this->comprovarTitulacionsNecessaries($metapersona, $valors);
			        if ($resultat != '') $res['errors'][] = $text.'. Alumne amb DNI '.$metapersona->getDni().', '.$resultat.'; necessàries per fer el curs. ';
			    }
			    
				break;
				
			case 203:  // Experiència immersions
			case 204:  
			case 205:
			case 206:
			case 207:			
			case 208:

			    $res['errors'][] = $text.'. No es pot comprovar per manca de dades ('.$tipus->getId().') ';
					
				break;	
				
			default:
					
				break;
		}
		
		if (count($res['errors']) > 0) $res['result'] = 'KO';
		
		return $res;
	}
	
	private function comprovaRequerimentsTitulacions($requeriment, $curs) {
		$tipus = $requeriment->getRequeriment();	
			
		$valors = explode(";",$requeriment->getValor());
		
		$text = $requeriment->getText();
		//$text = substr($text, 0, strpos($text, ":"));
		
		$res = array('result' => 'OK', 'errors' => array());
		
		switch ($tipus->getId()) {
			case 300:  // Director títols suficients
			    if ($curs->getDirector() != null) {
    			    $metapersona = $curs->getDirector()->getMetadocent();
    			        
    			    $resultat = $this->comprovarTitulacionsSuficients($metapersona, $valors);
    			    if ($resultat != '') $res['errors'][] = $text.'. Director del curs, '.$resultat.'; suficients per la direcció del curs. ';
			    }
			    
			    if ($curs->getCodirector() != null) {
    			    $metapersona = $curs->getCodirector()->getMetadocent();
    			    $resultat = $this->comprovarTitulacionsSuficients($metapersona, $valors);
    			    if ($resultat != '') $res['errors'][] = $text.'. Co-director del curs, '.$resultat.'; suficients per la direcció del curs. ';
			    }
			    
			    break;
			    
			case 301:  // Director títols necessaris
			    if ($curs->getDirector() != null) {
    			    $metapersona = $curs->getDirector()->getMetadocent();
    			    
    			    $resultat = $this->comprovarTitulacionsNecessaries($metapersona, $valors);
    			    if ($resultat != '') $res['errors'][] = $text.'. Director del curs, '.$resultat.'; necessàries per la direcció del curs. ';
			    }
			    
			    if ($curs->getCodirector() != null) {
			        $metapersona = $curs->getCodirector()->getMetadocent();
			        $resultat = $this->comprovarTitulacionsNecessaries($metapersona, $valors);
			        if ($resultat != '') $res['errors'][] = $text.'. Co-director del curs, '.$resultat.'; suficients per la direcció del curs. ';
			    }
			    
				break;
				
			case 302:  // Prof. teoria títols suficients
			    
			    $docents = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR);
			    foreach ($docents as $docent) {
			        if ($docent->esDocentTeoriques()) {
			            $metapersona = $docent->getMetadocent();
                        $resultat = $this->comprovarTitulacionsSuficients($metapersona, $valors);
                        if ($resultat != '') $res['errors'][] = $text.'. Instructor amb DNI '.$metapersona->getDni().', '.$resultat.'; suficients fer el curs. ';
			        }
			    }
			    
			    break;
			    
			case 303:  // Prof. teoria títols necessaris
			
			    $docents = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR);
			    foreach ($docents as $docent) {
			        if ($docent->esDocentTeoriques()) {
			            $metapersona = $docent->getMetadocent();
			            $resultat = $this->comprovarTitulacionsNecessaries($metapersona, $valors);
			            if ($resultat != '') $res['errors'][] = $text.'. Instructor amb DNI '.$metapersona->getDni().', '.$resultat.'; suficients fer el curs. ';
			        }
			    }
				
				break;

			case 304:  // Prof. pràctica títols suficients
			    
			    $docents = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR);
			    foreach ($docents as $docent) {
			        if ($docent->esDocentPractiques()) {
			            $metapersona = $docent->getMetadocent();
			            $resultat = $this->comprovarTitulacionsSuficients($metapersona, $valors);
			            if ($resultat != '') $res['errors'][] = $text.'. Instructor amb DNI '.$metapersona->getDni().', '.$resultat.'; suficients fer el curs. ';
			        }
			    }
			    
			    break;
			    
			case 305:  // Prof. pràctica títols necessaris
			
			    $docents = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR);
			    foreach ($docents as $docent) {
			        if ($docent->esDocentPractiques()) {
			            $metapersona = $docent->getMetadocent();
			            $resultat = $this->comprovarTitulacionsNecessaries($metapersona, $valors);
			            if ($resultat != '') $res['errors'][] = $text.'. Instructor amb DNI '.$metapersona->getDni().', '.$resultat.'; suficients fer el curs. ';
			        }
			    }
				
				break;

			case 304:  // Buss. seguretat títols suficients
			    
			    $colaboradors = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLLABORADOR);
			    foreach ($colaboradors as $colaborador) {
			        $metapersona = $colaborador->getMetadocent();
		            $resultat = $this->comprovarTitulacionsSuficients($metapersona, $valors);
		            if ($resultat != '') $res['errors'][] = $text.'. Col·laborador amb DNI '.$metapersona->getDni().', '.$resultat.'; suficients fer el curs. ';
			    }
			    
			    break;
			    
			case 305:  // Buss. seguretat títols necessaris
			
			    $colaboradors = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLLABORADOR);
			    foreach ($colaboradors as $colaborador) {
			        $metapersona = $colaborador->getMetadocent();
			        $resultat = $this->comprovarTitulacionsNecessaries($metapersona, $valors);
			        if ($resultat != '') $res['errors'][] = $text.'. Col·laborador amb DNI '.$metapersona->getDni().', '.$resultat.'; suficients fer el curs. ';
			    }
				
				break;
				
			default:
					
				break;
		}
		
		if (count($res['errors']) > 0) $res['result'] = 'KO';
		
		return $res;
	}

	private function comprovarTitulacionsSuficients($metapersona, $idsTitols) {
	    $trobat = false;
	    $titolsSuficients = array();
	    foreach ($idsTitols as $idTitolRequeriment) {
	        $currentTitol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($idTitolRequeriment);
	        $titolsSuficients[] = ($currentTitol != null?$currentTitol->getTitol():"títol desconegut id ".$idTitolRequeriment);
	        
	        if (!$trobat && count($metapersona->getTitulacionsByTitolId($idTitolRequeriment, true)) > 0) $trobat = true;
	    }
	    if (!$trobat) return ' no s\'ha trobat cap de les següents titulacions '.implode(",",$titolsSuficients);
	    return '';
	}
	
	private function comprovarTitulacionsNecessaries($metapersona, $idsTitols) {
	    $titolsNecessarisFalten = array();
	    foreach ($idsTitols as $idTitolRequeriment) {
	        
	        $cursosTitol = $metapersona->getTitulacionsByTitolId($idTitolRequeriment, true); // Consolidats
	        
	        if (count($cursosTitol) == 0) {
	            $currentTitol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($idTitolRequeriment);
	            
	            $titolsNecessarisFalten[] = ($currentTitol != null?$currentTitol->getTitol():"títol desconegut id ".$idTitolRequeriment);
	        }
	    }
	    if (count($titolsNecessarisFalten) > 0) return ' manquen les següents titulacions: '.implode(",",$titolsNecessarisFalten);
	    return '';
	}
	
	private function comprovaRequerimentsAltres($requeriment, $curs) {
		
		$tipus = $requeriment->getRequeriment();
		
		$valors = explode(";",$requeriment->getValor());

		$text = $requeriment->getText();
		$text = substr($text, 0, strpos($text, ":"));
		
		$res = array('result' => 'OK', 'errors' => array());
		
		switch ($tipus->getId()) {
			case 200:  // Edat mín.
					
				$edat = $requeriment->getValor();
				foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {
					$metapersona = $participant->getMetapersona();
					$persona = $metapersona->getPersona($curs->getClub());
					
					if ($persona != null) {
						if ($persona->getEdat() < $edat) $res['errors'][] = 'L\'alumne <span>'.$metapersona->getDni().'</span> no té l\'edat mínima';
					} else {
						$res['errors'][] = 'No es pot comprovar l\'edat de l\'alumne <span>'.$metapersona->getDni().'</span>'; 
					}
				}
				break;

			case 207:	// Federativa 
			
				$desde = $curs->getDatadesde();
				$fins = $curs->getDatafins();
				if ($desde == null || $fins == null) {
					$res['errors'][] = 'No es pot comprovar la vigencia de les llicències dels participants';
				} else {
					foreach ($curs->getParticipantsSortedByCognomsNom() as $participant) {
						$metapersona = $participant->getMetapersona();
						
						$finsVariable = clone $fins;
						$llicenciesPersonaPeriode = $metapersona->getLlicenciesSortedByDate(false, false, $desde, $fins); /* Ordenades de última a primera */
	
						foreach ($llicenciesPersonaPeriode as $llicencia) {
							// Totes les llicencies haurien d'estar parcialment dins el periode
							$parte = $llicencia->getParte();
							
							if ($parte->getDatacaducitat()->format('Y-m-d') >= $finsVariable->format('Y-m-d') && 
								$parte->getDataalta()->format('Y-m-d') 		<= $finsVariable->format('Y-m-d')) {
						 			
								$finsVariable = clone $parte->getDataalta();
						 		$finsVariable->sub(new \DateInterval('P1D')); // Minus 1 Day
							} 
						}

						// Si $fins <= $desde està tot el periode cobert
						if ($finsVariable->format('Y-m-d') > $desde->format('Y-m-d')) {
							$res['errors'][] = 'L\'alumne <span>'.$metapersona->getDni().'</span> no té llicència durant tot el periode del curs';
						} 
					}

				}	
				break;
				
			case 308:	// Instructor: Llicència federativa tècnic vigent 
			    
			    // La validació es fa anteriorment i és necessària per desar el curs
			    
			    break;
			    
			case 309:	// Director. # se exige haber dirigido como mínimo dos cursos de buceador de esa misma especialidad
				
			    $minimCursos = isset($valors[0])?$valors[0]:''; // Nombre de cursos dirigits prèviament
			    $idTitolCurs = isset($valors[1])?$valors[1]:''; // Titol del curs
			    
			    if (is_numeric($minimCursos) && $minimCursos > 0) {
			        $titolEspecialitat = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($idTitolCurs); 
			        $titol = $titolEspecialitat!=null?$titolEspecialitat->getTitol():'';
			        $metapersona = $curs->getDirector()->getMetadocent();
			        if (count($metapersona->getDocenciesByTitolId($idTitolCurs)) < $minimCursos) {
			            $res['errors'][] = $text.'. Respecte al Director, no hi ha registre del mínim de '.$minimCursos.' cursos dirigits de la especialitat '.$titol;
			        }
			        
			        if ($curs->getCodirector() != null) {
			            $metapersona = $curs->getCodirector()->getMetadocent();
			            if (count($metapersona->getDocenciesByTitolId($idTitolCurs)) < $minimCursos) {
			                $res['errors'][] = $text.'. Respecte al Director, no hi ha registre del mínim de '.$minimCursos.' cursos dirigits de la especialitat '.$titol;
			            }
			        }
			    }
					
				break;	
				
			default:
				
					
				break;
		}
		
		if (count($res['errors']) > 0) $res['result'] = 'KO';
		
		return $res; 
	}

	public function getRequerimentsEstructuraInforme($titol, $resultat)
    {
    	if ($titol == null) return array('titol' => '', 'errors' => array( 'total' => 0 ),
    	                                BaseController::CONTEXT_REQUERIMENT_ALUMNES => array(),
                                	    BaseController::CONTEXT_REQUERIMENT_GENERAL => array(),
                                	    BaseController::CONTEXT_REQUERIMENT_DOCENTS => array()
                                	);
		
    	// Format tipus fitxa per poder fer el render en alguna vista funcionalment
		$dades = array(
			'titol' => $titol->getLlistaText(),
			'errors' => array(
				'total' 	=> count($resultat),
				'alumnes' 	=> array(),
				'hores'		=> array(),
			    'immersions'=> array(),
				'docents'	=> array(),
				'ratios'	=> array() 
			),
			BaseController::CONTEXT_REQUERIMENT_ALUMNES => array(
				'edat' 			=> array('num' => 200, 'text' => 'Sense requeriments d\'edat', 'valor' => '', 'resultat' => ''),
				'llicencia'		=> array('num' => 207, 'text' => 'No cal llicència federativa', 'valor' => '', 'resultat' => ''),
				'immersions'	=> array('num' => array(203, 204, 205, 206, 208), 'text' => 'Immersions', 'valor' => '', 'resultat' => ''),
				'titols'		=> array('num' => array(201, 202), 'text' => 'Títols', 'valor' => '', 'resultat' => '')
			),
			BaseController::CONTEXT_REQUERIMENT_GENERAL => array(
				'hores' 		=> array(),
			    'immersions'    => array(), 
				'ratios' 		=> array()
			),
			
			BaseController::CONTEXT_REQUERIMENT_DOCENTS => array(
				'docents'		=> array(),
				'director'		=> array('num' => 309, 'text' => '', 'valor' => '', 'resultat' => ''),
			)
		);
		
		/********** LLISTA ERRORS ************/
		foreach ($resultat as $num => $error) {
		    if ($num >= 300) $key = 'docents';
		    	
            if ($num >= 200 && $num < 300) $key = 'alumnes';
            			
            if ($num >= 150 && $num < 200) $key = 'ratios'; 
			
            if ($num >= 120 && $num < 150) $key = 'immersions';  
            
            if ($num < 120) $key = 'hores';
			
			foreach ($error['errors'] as $err) {
			    $dades['errors'][$key][] = $err;
			}
		}
		
		/********** ALUMNE *************/
		$reqAlumnes = &$dades[BaseController::CONTEXT_REQUERIMENT_ALUMNES]; 
		
		if (isset($resultat[$reqAlumnes['edat']['num']])) $reqAlumnes['edat']['resultat'] = 'KO'; // error edat
		
		$reqEdat = $titol->getRequerimentByNum($reqAlumnes['edat']['num']);  // Edat
		if ($reqEdat != null) {
			$reqAlumnes['edat']['text'] = $reqEdat->getText();
			$reqAlumnes['edat']['valor'] = $reqEdat->getValor();
		}

		if (isset($resultat[$reqAlumnes['llicencia']['num']])) $reqAlumnes['llicencia']['resultat'] = 'KO'; // error llicencia
		$reqLlicencia = $titol->getRequerimentByNum($reqAlumnes['llicencia']['num']);  // Llicencia
		if ($reqLlicencia != null) {
			$reqAlumnes['llicencia']['text'] = $reqLlicencia->getText();
			$reqAlumnes['llicencia']['valor'] = $reqLlicencia->getValor();
		}

		$reqImmersions = array();
		$error = false;
		foreach ($reqAlumnes['immersions']['num'] as $num) {
		    $reqImmersio = $titol->getRequerimentByNum($num);  // Immersions
			
			if ($reqImmersio != null) $reqImmersions[] = $reqImmersio->getValor().' ('.$reqImmersio->getText().')';
			
			if (isset($resultat[$num])) $error = true;
		} 
		if (count($reqImmersions) > 0) {
			$reqAlumnes['immersions']['valor'] = implode(PHP_EOL, $reqImmersions);
		}
		
		if ($error) $reqAlumnes['immersions']['resultat'] = 'KO'; // error immersions
		
		$reqTitols = $titol->getRequerimentByNum($reqAlumnes['titols']['num'][0]);  // Titols suficients
		$separador = " o ";
		if ($reqTitols == null) {
		    $reqTitols = $titol->getRequerimentByNum($reqAlumnes['titols']['num'][1]);  // Titols necessaris
			$separador = " + ";	
		}		
		
		if (isset($resultat[$reqAlumnes['titols']['num'][0]]) ||
			isset($resultat[$reqAlumnes['titols']['num'][1]])) $reqAlumnes['titols']['resultat'] = 'KO'; // error titols
		
		if ($reqTitols != null) {
			$titolsIdsArray = explode(";",$reqTitols->getValor()); // llista ids XX;YY;ZZ
			
			$titolsAbrevArray = array(); 
			foreach ($titolsIdsArray as $id) {
				$altretitol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($id);
				if ($altretitol != null) $titolsAbrevArray[] = $altretitol->getCodi(); 
			}
			
			$reqAlumnes['titols']['valor'] = implode($separador, $titolsAbrevArray);
		}
		/********** GENERAL *************/
		$reqGeneral = &$dades[BaseController::CONTEXT_REQUERIMENT_GENERAL];
		foreach ($titol->getRequerimentsSortedByContextCategoria(BaseController::CONTEXT_REQUERIMENT_GENERAL) as $req) {
			$tipus = $req->getRequeriment();
			$num = $tipus->getId();
			
			switch ($tipus->getCategoria()) {
				case BaseController::CATEGORIA_REQUERIMENT_MIN_HORES:
					
					$reqGeneral['hores'][$num] = array(
						'text' => $req->getText(), 'valor1' => $req->getValor(), 'valor2' => '', 'resultat' => ''
					);
					
					if (isset($resultat[$num])) $reqGeneral['hores'][$num]['resultat'] = 'KO'; // error hores
					
					break;
					
				case BaseController::CATEGORIA_REQUERIMENT_IMMERSIONS:

				    $reqGeneral['immersions'][$num] = array(
				            'text' => $req->getText(), 'valor1' => $req->getValor(), 'valor2' => '', 'resultat' => ''
				    );
				    
					if ($num == 120 && isset($reqGeneral['hores'][102])) $num = 102; // piscina
						
					if ($num == 121 && isset($reqGeneral['hores'][103])) $num = 103; // mar
						
					if ($num == 122 && isset($reqGeneral['hores'][104])) $num = 104; // fent una funcio

					if (isset($resultat[$num])) $reqGeneral['hores'][$num]['resultat'] = 'KO'; // error hores
					
					//if (!isset($reqGeneral['hores'][102]) && !isset($reqGeneral['hores'][103]) && !isset($reqGeneral['hores'][104])) {
					if (!isset($reqGeneral['hores'][$num])) {	
						// Nova fila
						$reqGeneral['hores'][$num] = array(
							'text' => $req->getText(), 'valor1' => '', 'valor2' => $req->getValor(), 'resultat' => ''
						);
					} else {
						$reqGeneral['hores'][$num]['valor2'] = $req->getValor();
					}
					
					// ES CORRECTE FER SERVIR $tipus->getId() en comptes de $num !!
					if (isset($resultat[$tipus->getId()])) $reqGeneral['hores'][$num]['resultat'] = 'KO'; // error immersions
					
					break;
				
				case BaseController::CATEGORIA_REQUERIMENT_RATIOS:
					// 150 teoriques  | 151 piscina | 152 mar | 153 recomanat | 154 aula
					
					$valors = explode(";",$req->getValor());
					$valor0 = isset($valors[0])?$valors[0]:''; // Alumnes 
					$valor1 = isset($valors[1])?$valors[1]:''; // Professors
					$valor2 = isset($valors[2])?$valors[2]:''; // Bussejador seguretat
					
					
					if (!isset($reqGeneral['ratios'][$num])) {
						$reqGeneral['ratios'][$num] = array(
							'text' => $req->getText(), 'valor0' => $valor0, 'valor1' => $valor1, 'valor2' => $valor2, 'resultat' => ''
						);
					} else {
						$reqGeneral['ratios'][$num]['valor0'] = $valor0;
						$reqGeneral['ratios'][$num]['valor1'] = $valor1;
						$reqGeneral['ratios'][$num]['valor2'] = $valor2;
					}
					
					if (isset($resultat[$num])) $reqGeneral['ratios'][$num]['resultat'] = 'KO'; // error ratios
					
					break;
				
				default:
					
					break;
			}
			
		}	
		/********** DOCENTS *************/
		$reqDocents = &$dades[BaseController::CONTEXT_REQUERIMENT_DOCENTS];
		foreach ($titol->getRequerimentsSortedByContextCategoria(BaseController::CONTEXT_REQUERIMENT_DOCENTS) as $req) {
			$tipus = $req->getRequeriment();
			$num = $tipus->getId();
			
			switch ($tipus->getCategoria()) {
				case BaseController::CATEGORIA_REQUERIMENT_TITULACIONS:

					$titolsIdsArray = explode(";",$req->getValor()); // llista ids XX;YY;ZZ
					
					if (count($titolsIdsArray) > 0) {
						$titolsAbrevArray = array(); 
						foreach ($titolsIdsArray as $id) {
							$altretitol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($id);
							if ($altretitol != null) $titolsAbrevArray[] = $altretitol->getCodi(); 
						}
						
						$reqDocents['docents'][$num] = array(
							'text' => $req->getText(), 'valor' => implode($separador, $titolsAbrevArray), 'resultat' => ''
						);
						if (isset($resultat[$num])) $reqDocents['docents'][$num]['resultat'] = 'KO'; // error ratios
					}
											
					break;
			
				default:
				
					// req.  309 
					if ($num == $reqDocents['director']['num']) {  //  Director: Cursos de la especialitat dirigits prèviament
					
					    $valors = explode(";",$req->getValor()); // llista ids XX;YY;ZZ
					    $minCursos = isset($valors[0])?$valors[0]:'--';
					    $idTitol = isset($valors[1])?$valors[1]:0;
					    
					    $titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->find($idTitol);
					    $codi = $titol!=null?$titol->getCodi():'';
					    
						$reqDocents['director']['text'] = $req->getText();
						$reqDocents['director']['valor1'] = $minCursos;
						$reqDocents['director']['valor2'] = $codi;
						if (isset($resultat[$reqDocents['director']['num']])) $reqDocents['director']['resultat'] = 'KO'; // error director
					}
					
				break;
			}
		}
		
		return $dades;
	}

	private function consultaDadesfederats($dni, $nom, $cognoms, $mail = "", $professio = "", $club = null, $desde = null, $fins = null, $vigent = true, $titol = null, $titolExtern = null, $strOrderBY = '') { 
		$em = $this->getDoctrine()->getManager();
	
		$current = $this->getCurrentDate();
		
		//if ($this->isCurrentAdmin()) $strQuery = "SELECT m FROM FecdasBundle\Entity\EntityMetaPersona m JOIN m.persones e ";
		//else $strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e ";
		
		//$strQuery = "SELECT m FROM FecdasBundle\Entity\EntityMetaPersona m JOIN m.persones e ";
		
		$strQuery = "SELECT e FROM FecdasBundle\Entity\EntityPersona e JOIN e.metapersona m ";
		
		if ($titol != null) $strQuery .= " JOIN m.titulacions t JOIN t.curs c ";
		if ($titolExtern != null) $strQuery .= " JOIN m.altrestitulacions at ";
		
		if ($vigent == true) {
			$strQuery .= " JOIN e.llicencies l JOIN l.parte p ";
			$strQuery .= " WHERE e.databaixa IS NULL AND l.databaixa IS NULL AND p.databaixa IS NULL ";
			$strQuery .= " AND p.pendent = 0 ";
			$strQuery .= " AND p.dataalta <= :currenttime ";
			$strQuery .= " AND l.datacaducitat >= :currentdate ";
		} else {
		    if ($desde != null || $fins != null) { 
    			$strQuery .= " JOIN e.llicencies l JOIN l.parte p ";
    			$strQuery .= " WHERE e.databaixa IS NULL AND p.databaixa IS NULL ";
    			$strQuery .= " AND p.pendent = 0 ";
    			if ($desde != null) $strQuery .= " AND p.dataalta >= :desde ";
    			if ($fins != null) $strQuery .= " AND p.dataalta <= :fins ";
            } else {
                $strQuery .= " WHERE e.databaixa IS NULL ";
            }
		}
		
		if ($titol != null) $strQuery .= " AND t.databaixa IS NULL AND t.datasuperacio IS NOT NULL AND c.titol = :titol ";
		if ($titolExtern != null) $strQuery .= " AND at = :titolextern ";
		
		if ($club != null) $strQuery .= " AND e.club = :club ";
		if ($dni != "") $strQuery .= " AND m.dni LIKE :dni ";
		if ($nom != "") $strQuery .= " AND e.nom LIKE :nom ";
		if ($cognoms != "") $strQuery .= " AND e.cognoms LIKE :cognoms ";
		if ($mail != "") $strQuery .= " AND e.mail LIKE :mail ";
		if ($professio != "") $strQuery .= " AND e.professio LIKE :professio ";
		
		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  // Només per PDF el paginator ho fa sol mentre el mètode de crida sigui POST
		
		$query = $em->createQuery($strQuery);
				 
		// Algun filtre
		if ($club != null) $query->setParameter('club', $club->getCodi());
		if ($titol != null) $query->setParameter('titol', $titol->getId());
		if ($titolExtern != null) $query->setParameter('titolextern', $titolExtern->getId());
		if ($dni != "") $query->setParameter('dni', "%" . $dni . "%");
		if ($nom != "") $query->setParameter('nom', "%" . $nom . "%");
		if ($cognoms != "") $query->setParameter('cognoms', "%" . $cognoms . "%");
		if ($mail != "") $query->setParameter('mail', "%" . $mail . "%");
		if ($professio != "") $query->setParameter('professio', "%" . $professio . "%");
		if ($vigent == true) {
			$query->setParameter('currenttime', $current->format('Y-m-d').' 00:00:00');
			$query->setParameter('currentdate', $current->format('Y-m-d'));
		} else {
			if ($desde != null) $query->setParameter('desde', $desde->format('Y-m-d').' 00:00:00');
			if ($fins != null) $query->setParameter('fins', $fins->format('Y-m-d').' 23:59:59');
		}
	
		return $query;
	}


	private function consultaCursos($rol = '', $editor = null, $club = null, $titol = null, $participant = '', $desde = null, $fins = null, $estat = 0, $strOrderBY = '') {

		$em = $this->getDoctrine()->getManager();
	
		//$current = $this->getCurrentDate();
		
		$strQuery = "SELECT c FROM FecdasBundle\Entity\EntityCurs c JOIN c.titol t  ";
		
		if ($participant != '') $strQuery .= " JOIN c.participants p JOIN p.metapersona m JOIN m.persones e ";
		
		$strQuery .= " WHERE 1 = 1 ";
		if (!$this->isCurrentAdmin() || $estat > 0) {
			$strQuery .= " AND c.databaixa IS NULL ";
		}
		
		// Estats 
		// 0 - Tots
		// 1 - En tramitació  => editable == 1 || (editable == 0 && validat == 0)
		// 2 - Pendent de validació  => validat == 1 && finalitzat == 0
		// 3 - Finalitzat => finalitzat == 1
		switch ($estat) {
		    case 1:
		        $strQuery .= " AND (c.editable = 1 OR (c.editable = 0 AND c.validat = 0)) ";
		        break;
		    case 2:
		        $strQuery .= " AND c.validat = 1 AND c.finalitzat = 0 ";
		        break;
		    case 3:
		        $strQuery .= " AND c.finalitzat = 1 ";
		        break;
		}
		
		if ($desde != null) $strQuery .= " AND c.datafins >= :desde ";
		if ($fins != null) $strQuery .= " AND c.datadesde <= :fins ";
		
		if ($titol != null) $strQuery .= " AND t.id = :titol ";
		
		if ($club != null) $strQuery .= " AND c.club = :club ";
		
		if ($participant != '') $strQuery .= " AND (m.dni LIKE :participant OR CONCAT(e.nom, ' ', e.cognoms) LIKE :participant OR e.mail LIKE :participant) ";
		    
		if ($rol == BaseController::ROLE_INSTRUCTOR)  $strQuery .= " AND c.editor = :editor ";

		if ($strOrderBY != "") $strQuery .= " ORDER BY " .$strOrderBY;  
		
		$query = $em->createQuery($strQuery);
				
		// Algun filtre
		$query = $em->createQuery($strQuery);
		if ($club != null) $query->setParameter('club', $club->getCodi());
		if ($rol == BaseController::ROLE_INSTRUCTOR) $query->setParameter('editor', $editor!=null?$editor->getId():0);
		if ($titol != null) $query->setParameter('titol', $titol->getId());
		if ($desde != null) $query->setParameter('desde', $desde->format('Y-m-d'));
		if ($fins != null) $query->setParameter('fins', $fins->format('Y-m-d'));
		if ($participant != '') $query->setParameter('participant', "%".$participant."%");
	
		return $query;
	}

	public function jsonpersonaAction(Request $request) {
		//fecdas.dev/jsonfactures?cerca=dni&admin=1&club=CATXXX

		$response = new Response();
		$em = $this->getDoctrine()->getManager();
	
		$cerca = $request->get('cerca', '');
		$cercanom = $request->get('nom', 0) == 1?true:false;
		$cercamail = $request->get('mail', 0) == 1?true:false;
		//$admin = $request->get('admin', 0) == 1?true:false;
		$codi = $request->get('club', '');
		// Validació de llicències
		$tecnic = $request->get('tecnic', 0) == 1?true:false;
		$desde = $request->get('desde', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->get('desde')):null;
		$fins = $request->get('fins', '')!=''?\DateTime::createFromFormat('d/m/Y', $request->get('fins')):null;
		
		$club = $em->getRepository('FecdasBundle:EntityClub')->find($codi); // Per filtrar la persona correcta 
		
		$id = $request->get('id', 0);
		
		if ($id > 0) {
			$persona = $em->getRepository('FecdasBundle:EntityPersona')->find($id);
			if ($persona != null) {
				$response->headers->set('Content-Type', 'application/json');
				
				$telf  = $persona->getTelefon1()!=null?$persona->getTelefon1():'';
				$telf .= $persona->getTelefon2()!=null&&$telf=''?$persona->getTelefon2():'';
				
				$numCarnet = null;
				if ($tecnic && $persona->getMetapersona() != null) {
				    $numCarnet = $this->consultarNumCarnetDocent($persona->getMetapersona());
				}
				
				$response->setContent(json_encode(array(
							"id" => $persona->getId(), 
							"text" => $persona->getDni(),
							"meta" => ($persona->getMetapersona()!=null?$persona->getMetapersona()->getId():0),
							"nom" => $persona->getNom(),
							"cognoms" => $persona->getCognoms(),
							"nomcognoms" => $persona->getNomcognoms(), 
				            "mail" => ($persona->getMail()==null?"":$persona->getMail()),
							"telf" => $telf,
							"nascut" => $persona->getDatanaixement()->format('d/m/Y'),
							"poblacio" => $persona->getAddrpob(),
							"nacionalitat" => $persona->getAddrnacionalitat(),
				            "numcarnet" => $numCarnet == null?'':$numCarnet
				)));
				return $response;
			}
		}
		
		if ($id == 0 && $cerca == "") {
			// Res a cercar
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent(json_encode(array()));
			return $response;
		}

		if ($tecnic) {

            // CAL AFEGIR LLIÈNCIA VIGENT TêCNIC !!!!!!!!!!!!!!!!	
			// Consulta persones amb llicència durant tot el periode desde -> fins
			// iniciada abans de la data desde i acabada després de la data fins
			$strQuery = " SELECT p FROM FecdasBundle\Entity\EntityMetaPersona p INNER JOIN p.persones e  ";
			$strQuery .= " JOIN e.llicencies l JOIN l.parte r JOIN l.categoria a WHERE ";
			$strQuery .= " l.databaixa IS NULL ";
			$strQuery .= " AND r.dataalta <= :desde ";
			$strQuery .= " AND l.datacaducitat >= :fins ";
			$strQuery .= " AND a.simbol = :categoria ";
			$strQuery .= " AND ((p.dni LIKE :cerca) ";
			if ($cercanom) $strQuery .= " OR (CONCAT(e.nom,' ',e.cognoms) LIKE :cerca) ";
			if ($cercamail) $strQuery .= " OR (e.mail LIKE :cerca) ";
			$strQuery .= " ) ORDER BY e.cognoms, e.nom";  
			$query = $em->createQuery($strQuery);
			$query->setParameter('cerca', '%'.$cerca.'%');
			$query->setParameter('desde', $desde->format('Y-m-d H:i:s'));
			$query->setParameter('fins', $fins->format('Y-m-d'));
			$query->setParameter('categoria', BaseController::SIMBOL_TECNIC);
		} else {	
			$strQuery = " SELECT p FROM FecdasBundle\Entity\EntityMetaPersona p INNER JOIN p.persones e WHERE ";
			$strQuery .= " (p.dni LIKE :cerca) ";
			if ($cercanom) $strQuery .= " OR (CONCAT(e.nom,' ',e.cognoms) LIKE :cerca) ";
			if ($cercamail) $strQuery .= " OR (e.mail LIKE :cerca) ";
			$strQuery .= " ORDER BY e.cognoms, e.nom";  
		
			$query = $em->createQuery($strQuery);
			$query->setParameter('cerca', '%'.$cerca.'%');
		}	

		$search = array( );

		if ($query != null) {
			$result = $query->getResult();

			foreach ($result as $metapersona) {
				$persona = $metapersona->getPersona($club);
				
				if ($persona != null) {
				    $mail = ($persona->getMail()==null?"":$persona->getMail());
				    if ($tecnic) $mail = implode(";", $metapersona->getMails()); 
				    
					$telf  = $persona->getTelefon1()!=null?$persona->getTelefon1():'';
					$telf .= $persona->getTelefon2()!=null&&$telf=''?$persona->getTelefon2():'';
					
					$numCarnet = null;
					if ($tecnic) {
					    $numCarnet = $this->consultarNumCarnetDocent($metapersona);
					}
					
					$search[] = array("id" => $persona->getId(), 
									"text" => $persona->getDni(),
									"meta" => $metapersona->getId(),
									"nom" => $persona->getNom(),
									"cognoms" => $persona->getCognoms(),
									"nomcognoms" => $persona->getNomcognoms(),  
					                "mail" => $mail,
									"telf" => $telf,
									"nascut" => $persona->getDatanaixement()->format('d/m/Y'),
									"poblacio" => $persona->getAddrpob(),
									"nacionalitat" => $persona->getAddrnacionalitat(),
					                "numcarnet" => $numCarnet == null?'':$numCarnet
						);
				}
			}
		}
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($search));
		
		return $response;
	}

}
