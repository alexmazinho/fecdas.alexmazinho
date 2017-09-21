<?php 
namespace FecdasBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ServeisController extends BaseController {
	
	/**
	 * Entrada GET
	 * Paràmetres: 'dni', 'estranger'
	 * 		'dni': format 12345678T
	 * 		'estranger'  (Opcional): valor 1 indica que 'dni' no és nacional i no es valida el format 
	 * 
	 * 		exemple 1: https://www.fecdasgestio.cat/dadescompeticio?dni=12345678Z 
	 * 		exemple 2: https://www.fecdasgestio.cat/dadescompeticio?dni=87654321Z&estranger=1 
	 * 
	 * Resposta: Format JSON 
	 * 		resultat: 'OK' | 'KO' | 'ERROR'
	 * 		text:	text de la resposta	en format UTF-8
	 * 
	 * 		'OK' => Llicència de competició existeix i està vigent
	 * 		'KO' => Llicència no és de competició o no està vigent
	 * 		'ERROR'	=> Error en les dades d'entrada o altres 
	 * 
	 * 		exemple OK: {"result":"OK","text":"Llicencia vigent fins 2015-12-31 11:48:38"}
	 * 		exemple KO: {"result":"KO","text":"Darrera llicencia finalitzada des de 2014-12-31 11:55:22"}
	 * 		exemple ERROR: {"result":"ERROR","text":"Entrada incorrecta. DNI incorrecte, format 12345678Z."}
	 * 
	 * Errors:
	 * 		'Entrada incorrecta. Dades per GET, p.e. https://www.fecdasgestio.cat/dadescompeticio?dni=12345678Z'
	 * 		'Entrada incorrecta. Cal indicar el DNI, p.e. https://www.fecdasgestio.cat/dadescompeticio?dni=12345678Z'
	 * 		'Entrada incorrecta. DNI incorrecte, format 12345678Z.'
	 * 	   
	 */
	public function dadescompeticioAction(Request $request) {

		$response = new JsonResponse();

		try {
			if ($request->getMethod() == 'POST') throw new \Exception('Entrada incorrecta. Dades per GET, p.e. https://www.fecdasgestio.cat/dadescompeticio?dni=12345678Z');
		
			$dni = $request->query->get('dni', '');
			$dni = trim($dni);
			$estranger = $request->query->get('estranger', 0)==1?true:false;

			if ($dni == '') throw new \Exception('Entrada incorrecta. Cal indicar el DNI, p.e. https://www.fecdasgestio.cat/dadescompeticio?dni=12345678Z');

			if ($estranger == false && !BaseController::esDNIvalid($dni))  throw new \Exception('Entrada incorrecta. DNI incorrecte, format 12345678Z.');

			$em = $this->getDoctrine()->getManager();
				
			$strQuery = "SELECT p FROM FecdasBundle\Entity\EntityPersona p ";
			$strQuery .= " WHERE p.dni = :dni ";
			$strQuery .= " AND p.databaixa IS NULL ";
				
			$query = $em->createQuery($strQuery)->setParameter('dni', $dni);
			$persones = $query->getResult();
	
			$current = $this->getCurrentDate()->format('Y-m-d H:i:s');

			if (count($persones) > 0) {
				foreach ($persones as $persona) {
					/* Obtenir llicències competició encara no caducades per aquesta persona */
					$strQuery = "SELECT l FROM FecdasBundle\Entity\EntityLlicencia l INNER JOIN l.parte p ";
					$strQuery .= " WHERE p.tipus IN (1, 4, 5, 7) ";
					$strQuery .= " AND p.databaixa IS NULL ";
					$strQuery .= " AND p.dataalta <= :avui ";
					$strQuery .= " AND l.persona = :persona ";
					$strQuery .= " AND l.databaixa IS NULL ";
					$strQuery .= " ORDER BY p.dataalta DESC ";

					//$datainici = \DateTime::createFromFormat('d/m/Y H:i:s', (date('Y')-1).'-01-01 00:00:00');
					
					$query = $em->createQuery($strQuery)
							->setParameter('avui', $current)
							->setParameter('persona', $persona->getId());
					$llicencies = $query->getResult();
					
					if (count($llicencies) > 0) {
						foreach ($llicencies as $llicencia) {
							$parte = $llicencia->getParte();	
							
							$finalvigencia = $parte->getDataCaducitat()->format('Y-m-d H:i:s');

							if ($finalvigencia >= $current) {
								// OK. vigència correcte	
								$this->logEntryAuth('COMPETICIO OK', $dni.' '.($estranger == true?'estranger':'') );
																
								$response->setData(array('result' => 'OK', 'text' => 'Llicencia vigent fins '.$finalvigencia));
								return $response;
							} else {
								// KO. caducada
								$this->logEntryAuth('COMPETICIO KO', $dni.' '.($estranger == true?'estranger':'') );
								
								$response->setData(array('result' => 'KO', 'text' => 'Darrera llicencia finalitzada des de '.$finalvigencia));
								return $response;
							}
						}
					}
				}
			}
		} catch (\Exception $e) {
			$this->logEntryAuth('COMPETICIO ERROR', $e->getMessage() );
			
			$response->setStatusCode(Response::HTTP_OK);	
			$response->setData(array('result' => 'ERROR', 'text' => $e->getMessage()));
			return $response;
		}

		$this->logEntryAuth('COMPETICIO KO', $dni.' '.($estranger == true?'estranger':'') );
		
		$response->setData(array('result' => 'KO', 'text' => 'Cap llicencia trobada amb les dades indicades, dni '.$dni));
		return $response;
	}
	
}
