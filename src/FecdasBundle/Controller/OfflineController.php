<?php 
namespace FecdasBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use FecdasBundle\Entity\EntityMetaPersona;
use FecdasBundle\Entity\EntitySaldos;
use FecdasBundle\Entity\EntityCurs;
use FecdasBundle\Entity\EntityStock;
use FecdasBundle\Entity\EntityTitulacio;
use FecdasBundle\Entity\EntityPersona;

class OfflineController extends BaseController {
	
    
    public function checkregistresaldosAction(Request $request) {
        // http://www.fecdas.test/checkregistresaldos?club=CAT514
        
        if (!$this->isCurrentAdmin())
            return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
        
        $em = $this->getDoctrine()->getManager();
            
        $club = $request->query->get('club', '');
        
        $errors     = array();
        $files      = array();
        $zip        = null;        
        $zipFilename = __DIR__.BaseController::PATH_TO_VARIS_FILES."checkregistresaldos".($club!=''?"_".$club:"").".zip";
        $clubs      = array();
        
        try {
            // Obtenir tots els clubs amb codi
            $strQuery  = "SELECT codi, exercici, romanent, totalpagaments, totalllicencies, totalduplicats, totalaltres FROM m_clubs WHERE compte IS NOT NULL AND compte <> 0 ";
            if ($club != '') $strQuery  .= " AND codi = '".$club."' ";
            $strQuery  .= "ORDER BY codi";
                
            $stmt = $em->getConnection()->prepare($strQuery);
            $stmt->execute();
            $clubs = $stmt->fetchAll();
                
            foreach ($clubs as $currentClub) {
                $saldos     = array();
                $club       = $currentClub['codi'];
                $exercici   = $currentClub['exercici'];
                $clubromanent           = $currentClub['romanent'];
                $clubtotalpagaments     = $currentClub['totalpagaments'];
                $clubtotalllicencies    = $currentClub['totalllicencies'];
                $clubtotalduplicats     = $currentClub['totalduplicats'];
                $clubtotalaltres        = $currentClub['totalaltres'];
                
                
                // Obtenir registre saldos des de la primera data disponible
                $strQuery  = "SELECT *  FROM m_saldos WHERE club LIKE '".$club."' ";
                $strQuery .= "AND dataregistre >= '".$exercici."-01-01' ";
                $strQuery .= "ORDER BY dataregistre ASC";
                
                $stmt = $em->getConnection()->prepare($strQuery);
                $stmt->execute();
                $registresaldos = $stmt->fetchAll();
                
                if (count($registresaldos) == 0) {
                    $errors[] = array(
                        'club'  => $club,
                        'data'  => $exercici."-01-01'",
                        'error' => "WARN. CLUB sense registre de saldos"
                    );
                    
                } else {
                
                    $saldoinicial = $registresaldos[0];
                    
                    $dataregistre       = \DateTime::createFromFormat('Y-m-d', $saldoinicial['dataregistre']);
                    $datafins           = new \DateTime();
                    //$exercici           = $saldo['exercici'];
                    $romanent           = $saldoinicial['romanent'];
                    $totalpagaments     = 0; //$saldoinicial['totalpagaments'];
                    $totalllicencies    = 0; //$saldoinicial['totalllicencies'];
                    $totalduplicats     = 0; //$saldoinicial['totalduplicats'];
                    $totalaltres        = 0; //$saldoinicial['totalaltres'];
                    $saldo              = $romanent + $totalpagaments - $totalllicencies - $totalduplicats - $totalaltres; 
                    $saldocomptable     = $saldo;
                    $entrades           = 0;
                    $sortides           = 0;
                    
                    $strQuery  = "  SELECT DATE(dataentrada) as dataentrada, DATE(datapagament) as datapagament, import, databaixa FROM m_rebuts WHERE club LIKE '".$club."' AND 
                                    (DATE(dataentrada) >= '".$saldoinicial['dataregistre']."' OR DATE(datapagament) >= '".$saldoinicial['dataregistre']."') AND databaixa IS NULL 
                                    ORDER BY DATE(dataentrada) ";
                    
                    $stmt = $em->getConnection()->prepare($strQuery);
                    $stmt->execute();
                    $rebuts = $stmt->fetchAll();
                    
                    $strQuery  = "  SELECT DATE(f.dataentrada) as dataentrada, DATE(datafactura) as datafactura, import, tipuscomanda FROM m_comandes c INNER JOIN m_factures f ON c.factura = f.id
                                    WHERE club LIKE '".$club."' AND
                                    (DATE(f.dataentrada) >= '".$saldoinicial['dataregistre']."' OR DATE(datafactura) >= '".$saldoinicial['dataregistre']."')
                                    ORDER BY DATE(f.dataentrada) ";
                    
                    $stmt = $em->getConnection()->prepare($strQuery);
                    $stmt->execute();
                    $factures = $stmt->fetchAll();
        
                    $strQuery  = "  SELECT DATE(f.dataentrada) as dataentrada, DATE(datafactura) as datafactura, import, tipuscomanda FROM m_comandes c INNER JOIN m_factures f ON c.id = f.comandaanulacio
                                    WHERE club LIKE '".$club."' AND
                                    (DATE(f.dataentrada) >= '".$saldoinicial['dataregistre']."' OR DATE(datafactura) >= '".$saldoinicial['dataregistre']."')
                                    ORDER BY DATE(f.dataentrada) ";
                    
                    $stmt = $em->getConnection()->prepare($strQuery);
                    $stmt->execute();
                    $anulacions = $stmt->fetchAll();
                    
                    $ir = count($rebuts)>0?0:-1;
                    $if = count($factures)>0?0:-1;
                    $ia = count($anulacions)>0?0:-1;
                    
                    $total = 0;
                    $max = 2000;
                    
                    while ($total < $max && $dataregistre->format('Y-m-d') <= $datafins->format('Y-m-d')) {
                    // Primer crear moviments a temps real    
                        //error_log("====> ".$total);

                        if ($ir >= 0 && $ir < count($rebuts)) {
                            //error_log("R".$ir."<br/>");
                            
                            while ($ir < count($rebuts) && $rebuts[$ir]['dataentrada'] <= $dataregistre->format('Y-m-d')) {
                                if ($rebuts[$ir]['databaixa'] == null && $rebuts[$ir]['dataentrada'] == $dataregistre->format('Y-m-d')) {
                                    // Rebut pagat data registre
                                    $totalpagaments += $rebuts[$ir]['import'];
                                    $saldo += $rebuts[$ir]['import'];
                                } else {
                                    if ($dataregistre->format('Y-m-d') == '2016-01-01' &&
                                        $rebuts[$ir]['dataentrada'] <= $dataregistre->format('Y-m-d')) {
                                        // LES FACTURES 2016 REGISTRADES ABANS DE 2016 s’ACUMULARAN a 01/01/2016
                                        // JA ESTAN INCLOSES AL SALDO
                                        $totalpagaments += $rebuts[$ir]['import'];
                                    }
                                }
                                $ir++;
                            }
                        }
                        
                        if ($if >= 0 && $if < count($factures)) {
                            //error_log("F".$if."<br/>");
                            
                            while ($if < count($factures) && $factures[$if]['dataentrada'] <= $dataregistre->format('Y-m-d')) {
                                if ($factures[$if]['dataentrada'] == $dataregistre->format('Y-m-d')) {
                                    // Factura generada data registre
                                    switch ($factures[$if]['tipuscomanda']) {
                                        case "P":
                                            $totalllicencies += $factures[$if]['import'];
                                            break;
                                        case "D":
                                            $totalduplicats += $factures[$if]['import'];
                                            break;
                                        case "A":
                                            $totalaltres += $factures[$if]['import'];
                                            break;
                                        default:
                                            throw new \Exception("Tipus de comanda '".$factures[$if]['tipuscomanda']."' desconegut");
                                    }
                                    
                                    $saldo -= $factures[$if]['import'];
                                } else {
                                    if ($dataregistre->format('Y-m-d') == '2016-01-01' &&
                                        $factures[$if]['dataentrada'] <= $dataregistre->format('Y-m-d')) {
                                        // ELS REBUTS 2016 REGISTRATS ABANS DE 2016 S’ACUMULARAN a 01/01/2016
                                        // JA ESTAN INCLOSES AL SALDO
                                        $totalllicencies += $factures[$if]['import'];
                                        
                                    }
                                }
                                $if++;
                            }
                        }
                        
                        if ($ia >= 0 && $ia < count($anulacions)) {
                            //error_log("A".$ia."<br/>");
                            
                            while ($ia < count($anulacions) && $anulacions[$ia]['dataentrada'] <= $dataregistre->format('Y-m-d')) {
                                if ($anulacions[$ia]['dataentrada'] == $dataregistre->format('Y-m-d')) {
                                    // Factura generada data registre
                                    switch ($anulacions[$ia]['tipuscomanda']) {
                                        case "P":
                                            $totalllicencies += $anulacions[$ia]['import'];
                                            break;
                                        case "D":
                                            $totalduplicats += $anulacions[$ia]['import'];
                                            break;
                                        case "A":
                                            $totalaltres += $anulacions[$ia]['import'];
                                            break;
                                        default:
                                            throw new \Exception("Tipus de comanda '".$anulacions[$ia]['tipuscomanda']."' desconegut");
                                    }
                                    
                                    $saldo -= $anulacions[$ia]['import'];
                                }
                                $ia++;
                            }
                        }
                        
                        // Registre inicial
                        $saldos[$dataregistre->format('Y-m-d')] = array(
                            'dataregistre'      => $dataregistre->format('Y-m-d'),
                            'romanent'          => $romanent,
                            'totalpagaments'    => 1*$totalpagaments,     //number_format($row['saldo'], 2, ',', '.')
                            'totalllicencies'   => 1*$totalllicencies,
                            'totalduplicats'    => 1*$totalduplicats,
                            'totalaltres'       => 1*$totalaltres,
                            'saldo'             => 1*$saldo,
                            'entrades'          => 1*$entrades,
                            'sortides'          => 1*$sortides,
                            'saldocomptable'    => 1*$saldocomptable,
                            'error'             => ''
                        );
                        
                        $total++;
                        
                        $dataregistre->add(new \DateInterval('P1D')); // Add 1 Day
                    }
                    
                    // després afegir moviments comptables
                    for ($ir = 0; $ir < count($rebuts); $ir++) {
                        $rebut = $rebuts[$ir];
                        
                        if (isset($saldos[$rebut['datapagament']])) {
                            $saldos[$rebut['datapagament']]['entrades'] += $rebut['import'];
                        } else {
                            error_log("REBUT NO REGISTRAT: ".$rebut['datapagament']." ".$rebut['import']."<br/>");
                        }
                    }
                    
                    for ($if = 0; $if < count($factures); $if++) {
                        $factura = $factures[$if];
                        
                        if (isset($saldos[$factura['datafactura']])) {
                            $saldos[$factura['datafactura']]['sortides'] += $factura['import'];
                        } else {
                            error_log("FACTURA NO REGISTRAT: ".$factura['datafactura']." ".$factura['import']."<br/>");
                        }
                    }
                    
                    for ($ia = 0; $ia < count($anulacions); $ia++) {
                        $anulacio = $anulacions[$ia];
                        
                        if (isset($saldos[$anulacio['datafactura']])) {
                            $saldos[$anulacio['datafactura']]['sortides'] += $anulacio['import'];
                        } else {
                            error_log("ANULACIO NO REGISTRAT: ".$anulacio['datafactura']." ".$anulacio['import']."<br/>");
                        }
                    }
                    
                    // Recalcular saldo comptable
                    foreach ($saldos as $k => $registresaldo) {
                        $saldocomptable += $registresaldo['entrades'];
                        $saldocomptable -= $registresaldo['sortides'];
                        
                        $saldos[$k]['saldocomptable'] = $saldocomptable;
                    }
                    
                    // Comprovació contra registre de saldos de la BBDD
                    for ($i = 0; $i < count($registresaldos); $i++) {
                        $registresaldo = $registresaldos[$i];
                        if (isset($saldos[$registresaldo['dataregistre']])) {
                            $saldocalculat = $saldos[$registresaldo['dataregistre']];
                            
                            $error = '';
                            if (abs($registresaldo['totalpagaments'] - $saldocalculat['totalpagaments']) > 0.01) {
                                $error .= "pagaments no coincideixen. Registrat: ".$registresaldo['totalpagaments'].". Calculat: ".$saldocalculat['totalpagaments'].BR;
                                $error .= "  Check: \"SELECT * FROM m_saldos WHERE club = '".$club."' AND dataregistre >= '".$registresaldo['dataregistre']."' ORDER BY dataregistre;\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_rebuts WHERE club = '".$club."' AND dataentrada >= '".$registresaldo['dataregistre']." 00:00:00' ORDER BY dataentrada;\"".BR;
                                $error .= "  Query: \"UPDATE m_saldos SET totalpagaments = ".$saldocalculat['totalpagaments']." WHERE club = '".$club."' AND dataregistre = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= BR.BR;
                            }
                            if (abs($registresaldo['totalllicencies'] - $saldocalculat['totalllicencies']) > 0.01) {
                                $error .= "llicencies no coincideixen. Registrat: ".$registresaldo['totalllicencies'].". Calculat: ".$saldocalculat['totalllicencies'].BR;
                                $error .= "  Check: \"SELECT * FROM m_saldos WHERE club = '".$club."' AND dataregistre >= '".$registresaldo['dataregistre']."' ORDER BY dataregistre;\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_comandes c INNER JOIN m_factures f ON f.id = c.factura WHERE c.club = '".$club."' AND DATE(f.dataentrada) >= '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_comandes c INNER JOIN m_factures f ON f.comandaanulacio = c.id WHERE c.club = '".$club."' AND DATE(f.dataentrada) >= '".$registresaldo['dataregistre']."';\"".BR;
                                
                                $error .= "  Query: \"UPDATE m_saldos SET totalllicencies = ".$saldocalculat['totalllicencies']." WHERE club = '".$club."' AND dataregistre = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= BR.BR;
                            }
                            if (abs($registresaldo['totalduplicats'] - $saldocalculat['totalduplicats']) > 0.01) {
                                $error .= "duplicats no coincideixen. Registrat: ".$registresaldo['totalduplicats'].". Calculat: ".$saldocalculat['totalduplicats'].BR;
                                $error .= "  Check: \"SELECT * FROM m_saldos WHERE club = '".$club."' AND dataregistre >= '".$registresaldo['dataregistre']."' ORDER BY dataregistre;\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_comandes c INNER JOIN m_factures f ON f.id = c.factura WHERE c.club = '".$club."' AND DATE(f.dataentrada) >= '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_comandes c INNER JOIN m_factures f ON f.comandaanulacio = c.id WHERE c.club = '".$club."' AND DATE(f.dataentrada) >= '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Query: \"UPDATE m_saldos SET totalduplicats = ".$saldocalculat['totalduplicats']." WHERE club = '".$club."' AND dataregistre = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= BR.BR;
                            }
                            if (abs($registresaldo['totalaltres'] - $saldocalculat['totalaltres']) > 0.01) {
                                $error .= "altres no coincideixen. Registrat: ".$registresaldo['totalaltres'].". Calculat: ".$saldocalculat['totalaltres'].BR;
                                $error .= "  Check: \"SELECT * FROM m_saldos WHERE club = '".$club."' AND dataregistre >= '".$registresaldo['dataregistre']."' ORDER BY dataregistre;\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_comandes c INNER JOIN m_factures f ON f.id = c.factura WHERE c.club = '".$club."' AND DATE(f.dataentrada) >= '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_comandes c INNER JOIN m_factures f ON f.comandaanulacio = c.id WHERE c.club = '".$club."' AND DATE(f.dataentrada) >= '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Query: \"UPDATE m_saldos SET totalaltres = ".$saldocalculat['totalaltres']." WHERE club = '".$club."' AND dataregistre = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= BR.BR;
                            }
                            
                            if (abs($registresaldo['entrades'] - $saldocalculat['entrades']) > 0.01) {
                                $error .= "entrades no coincideixen. Registrat: ".$registresaldo['entrades'].". Calculat: ".$saldocalculat['entrades'].BR;
                                $error .= "  Check: \"SELECT * FROM m_saldos WHERE club = '".$club."' AND dataregistre = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_rebuts WHERE club = '".$club."' AND DATE(datapagament) = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Query: \"UPDATE m_saldos SET entrades = ".$saldocalculat['entrades']." WHERE club = '".$club."' AND dataregistre = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= BR.BR;
                            }
                            if (abs($registresaldo['sortides'] - $saldocalculat['sortides']) > 0.01) {
                                $error .= "sortides no coincideixen. Registrat: ".$registresaldo['sortides'].". Calculat: ".$saldocalculat['sortides'].BR;
                                $error .= "  Check: \"SELECT * FROM m_saldos WHERE club = '".$club."' AND dataregistre = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_comandes c INNER JOIN m_factures f ON f.id = c.factura WHERE c.club = '".$club."' AND DATE(f.datafactura) = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Check: \"SELECT * FROM m_comandes c INNER JOIN m_factures f ON f.comandaanulacio = c.id WHERE c.club = '".$club."' AND DATE(f.datafactura) = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= "  Query: \"UPDATE m_saldos SET sortides = ".$saldocalculat['sortides']." WHERE club = '".$club."' AND dataregistre = '".$registresaldo['dataregistre']."';\"".BR;
                                $error .= BR.BR;
                            }
                            
                            if ($error != '') {
                                $saldos[$registresaldo['dataregistre']]['error'] = $error;
                                
                                $errors[] = array(
                                    'club'  => $club,
                                    'data'  => $registresaldo['dataregistre'],
                                    'error' => $error
                                );
                            }
                        } else {
                            error_log("DATA NO REGISTRADA: ".$registresaldo['dataregistre']."<br/>");
                        }
                    }
                    
                    
                    // Comprovació saldo registre final
                    $saldofinal = $registresaldos[count($registresaldos) -1];
                    
                    $error = '';
                    if (abs($clubromanent - $saldofinal['romanent']) > 0.01 ||
                        abs($clubtotalpagaments - $saldofinal['totalpagaments']) > 0.01 ||
                        abs($clubtotalllicencies - $saldofinal['totalllicencies']) > 0.01 ||
                        abs($clubtotalduplicats - $saldofinal['totalduplicats']) > 0.01 ||
                        abs($clubtotalaltres - $saldofinal['totalaltres']) > 0.01) {
                        $error .= "SALDO FINAL DIFERENT SALDO CLUB ".$club.BR;
                        
                        if (abs($clubromanent - $saldofinal['romanent']) > 0.01) $error .= "  ROMANENT. Darrer saldo: ".$saldofinal['romanent'].". club: ".$clubromanent.BR;
                        if (abs($clubtotalpagaments - $saldofinal['totalpagaments']) > 0.01) $error .= "  PAGAMENTS. Darrer saldo: ".$saldofinal['totalpagaments'].". club: ".$clubtotalpagaments.BR;
                        if (abs($clubtotalllicencies - $saldofinal['totalllicencies']) > 0.01) $error .= "  LLICENCIES. Darrer saldo: ".$saldofinal['totalllicencies'].". club: ".$clubtotalllicencies.BR;
                        if (abs($clubtotalduplicats - $saldofinal['totalduplicats']) > 0.01) $error .= "  DUPLICATS. Darrer saldo: ".$saldofinal['totalduplicats'].". club: ".$clubtotalduplicats.BR;
                        if (abs($clubtotalaltres - $saldofinal['totalaltres']) > 0.01) $error .= "  ALTRES. Darrer saldo: ".$saldofinal['totalaltres'].". club: ".$clubtotalaltres.BR;
                        
                        $error .= "  Check: \"SELECT romanent, totalpagaments, totalllicencies, totalduplicats, totalaltres, dataregistre FROM m_saldos WHERE club = '".$club."' ORDER BY dataregistre DESC LIMIT 1;\"".BR;
                        $error .= "  Check: \"SELECT romanent, totalpagaments, totalllicencies, totalduplicats, totalaltres  FROM m_clubs  WHERE codi = '".$club."';\"".BR;
                        
                        if (abs($clubromanent - $saldofinal['romanent']) > 0.01) $error .= "  Query: \"UPDATE m_clubs SET romanent = ".$saldofinal['romanent']." WHERE codi = '".$club."';\"".BR;
                        if (abs($clubtotalpagaments - $saldofinal['totalpagaments']) > 0.01) $error .= "  Query: \"UPDATE m_clubs SET totalpagaments = ".$saldofinal['totalpagaments']." WHERE codi = '".$club."';\"".BR;
                        if (abs($clubtotalllicencies - $saldofinal['totalllicencies']) > 0.01) $error .= "  Query: \"UPDATE m_clubs SET totalllicencies = ".$saldofinal['totalllicencies']." WHERE codi = '".$club."';\"".BR;
                        if (abs($clubtotalduplicats - $saldofinal['totalduplicats']) > 0.01) $error .= "  Query: \"UPDATE m_clubs SET totalduplicats = ".$saldofinal['totalduplicats']." WHERE codi = '".$club."';\"".BR;
                        if (abs($clubtotalaltres - $saldofinal['totalaltres']) > 0.01) $error .= "  Query: \"UPDATE m_clubs SET totalaltres = ".$saldofinal['totalaltres']." WHERE codi = '".$club."';\"".BR;
                        
                        $error .= BR.BR;
                    }
                    if ($error != '') {
                        $saldos[$datafins->format('Y-m-d')]['error'] .= $error;
                        
                        $errors[] = array(
                            'club'  => $club,
                            'data'  => $datafins->format('Y-m-d'),
                            'error' => $error
                        );
                    }
                
                    $filename = "registre_saldos_generat_club_".$club.".csv";
                    $header = array('Data', 'Romanent', 'Pagaments', 'Llicències', 'Duplicats', 'Altres', 'Saldo', 'Entrades', 'Sortides', 'Saldo Comptable', 'Error');
                
                    $files[] = array(
                        'filename'      => $filename,
                        'fullfilename'  => $this->writeCSV($header, $saldos, $filename)
                    );
                }
            }
        
            $zip = new \ZipArchive();
            if ($zip->open($zipFilename, \ZipArchive::CREATE)!==TRUE) throw new \Exception("No es pot crear l'arxiu ".$zipFilename);
            
            foreach ($files as $fil) {
                $zip->addFile($fil['fullfilename'], $fil['filename']);
            }
            
            //$response = $this->exportCSV($request, $header, $saldos, $filename);
            //return $response;
        } catch (\Exception $e) {
            $this->logEntryAuth('CHECK SALDOS KO',	'CLUB ' . $club. ' error '.$e->getMessage());
            
            return new Response( "KO : ".$e->getMessage() );
        }
        
        $result  = "ARXIUS ESCRITS".BR;
        foreach ($files as $fil) {
            $result .= $fil['filename'].BR;
        }
        $result .= BR;
        
        if ($zip != null) {
            $result .= "ARXIUS ZIP: ".$zip->numFiles.BR;
            $result .= "ESTAT ZIP:".$zip->status.BR.BR;
            $zip->close();
        }
        
        $result .= "ERRORS DETECTATS".BR;
        foreach ($errors as $err) {
            $result .= implode(" ",$err).BR;
        }
        
        return new Response( $result );
    }
    
    
    public function historictitolserrorsAction(Request $request) {
        // http://www.fecdas.dev/historictitolserrors?max=10&pag=1
        // http://www.fecdas.dev/historictitolserrors?id=1234
        /*
         * http://www.fecdas.dev/historictitolserrors?max=1000&pag=1
         * http://www.fecdas.dev/historictitolserrors?max=1000&pag=2
         */
        // Script de migració. Executar per migrar errors i desactivar
        
        if (!$this->isAuthenticated())
            return $this->redirect($this->generateUrl('FecdasBundle_login'));
            
        if (!$this->isCurrentAdmin())
            return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
                
        $em = $this->getDoctrine()->getManager();
                
        $id = $request->query->get('id', 0);
        $max = $request->query->get('max', 2000);
        $pag = $request->query->get('pag', 1);
        $offset = $max * ($pag -1);
                
        //$batchSize = 20;
                
        $strQuery  = " SELECT * ";
        $strQuery .= " FROM importtitulacions ";
        $strQuery .= " WHERE error IS NOT NULL AND error <> 'Corregit' ";
        if ($id > 0) $strQuery .= " AND id = ".$id;
        $strQuery .= " ORDER BY numcurso, numtitulo ";
        if ($id == 0) $strQuery .= " LIMIT ".$max. " OFFSET ".$offset;
        $stmt = $em->getConnection()->prepare($strQuery);
        $stmt->execute();
        $titolserrors = $stmt->fetchAll();
                
        $cursosNous = 0;
        $personesNoves = 0;
        $titulacions = 0;
                
        $cursos = array();
        $errors = array();
        $warnings = array();
        $ids = array();
         
        $federacio = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find(BaseController::CODI_FECDAS);
        
        for ($i = 0; $i < count($titolserrors); $i++) {
            $currentTitolError = $titolserrors[$i];
                    
            $id = $currentTitolError['id'];
            $ids[] = $id;
            $dni = $currentTitolError['dni']."";
            $error = $currentTitolError['error']; 
                
            try {
                $pos = strpos($error, 'Persona no trobada');
                if ($pos === false) throw new \Exception($id.'#INFO. Altres errors: '.$error);
                    
                // Cercar títol (NO pot ser null i només pot trobar un)
                $titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->findOneBy(array('codi' => $currentTitolError['abreviatura']));
                
                if ($titol == null || count($titol) != 1) throw new \Exception($id.'#ERROR. Títol no trobat: #'.$currentTitolError['abreviatura']); //ERROR
                
                // Cercar club. Si no es troba no crear dades personals
                $club = null;
                $clubhistoric = '';
                if ($currentTitolError['club'] == '') {
                    // Validar Federació per a cursos Instructors  => Titols tipus 'TE'
                    //if (!$titol->esInstructor()) throw new \Error($id.'#WARN. Club no trobat ("'.$currentTitolError['club'].'") curs no tècnic: '.$currentTitolError['numcurso'].' '.$currentTitolError['club'].' '.$currentTitolError['abreviatura']);
                    if (!$titol->esInstructor()) $clubhistoric = 'Sense informació del club';
                    else $club = $federacio;
                }
                else {        
                    $nomClub = str_replace(".", "", $currentTitolError['club']);
                            
                    $strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
                    $strQuery .= " WHERE  c.nom LIKE :club ";
                    $query = $em->createQuery($strQuery);
                    $query->setParameter('club', '%'.$nomClub.'%' );
                    $clubs = $query->getResult();
                            
                    if ($clubs == null) throw new \Exception($id.'#ERROR. Club no trobat 2: '.$currentTitolError['club']);
                        
                    if (count($clubs) > 1)  throw new \Exception($id.'#ERROR. Varis clubs candidats 2: '.$currentTitolError['club']);
                        
                    $clubs = array_values($clubs);
                    $club = $clubs[0];
                }                    
                // Meta persona no hauria d'existir
                $metapersona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $dni));
                            
                if ($metapersona == null) {
                    $metapersona = new EntityMetaPersona( $dni );
                    $em->persist($metapersona);
                        
                    $persona = new EntityPersona($metapersona, $club);
                    $persona->setVAlidat(true);
                    $nomCognoms = $currentTitolError['federado'];
                    $nomArray = explode(",", $nomCognoms); // Cognoms + Nom
                    
                    if (!isset($nomArray[0])) throw new \Exception($id.'#ERROR. Persona sense cognoms: '.$nomCognoms);
                    if (!isset($nomArray[1])) throw new \Exception($id.'#ERROR. Persona sense nom: '.$nomCognoms);
                    if (count($nomArray) != 2) throw new \Exception($id.'#ERROR. Persona nom incorrecte: '.$nomCognoms);
                    $persona->setCognoms(mb_strtoupper($nomArray[0]));
                    $persona->setNom($nomArray[1]);
                    
                    $persona->setSexe($currentTitolError['sexo']);  // H o M
                    
                    if ($currentTitolError['nacimiento'] == '' || 
                        $currentTitolError['nacimiento'] == null) throw new \Exception($id.'#ERROR. Persona sense data naixement: '.$currentTitolError['nacimiento']);
                    
                    $naixement = \DateTime::createFromFormat('Y-m-d', $currentTitolError['nacimiento']); // YYYY-MM-DD
                    $persona->setDatanaixement($naixement);
                    
                    $persona->setDatamodificacio($this->getCurrentDate('now'));
                    
                    if ($currentTitolError['email'] != '' && 
                        $currentTitolError['email'] != null) $persona->setMail($currentTitolError['email']);
                    
                    if ($currentTitolError['movil'] != '' && 
                        $currentTitolError['movil'] != null &&
                        is_numeric($currentTitolError['movil'])) $persona->setTelefon1($currentTitolError['movil']);
                    
                    if ($currentTitolError['direccion'] != '' &&
                        $currentTitolError['direccion'] != null) $persona->setAddradreca($currentTitolError['direccion']);
                        
                    if ($currentTitolError['cp'] != '' &&
                        $currentTitolError['cp'] != null) {
                        
                        $persona->setAddrcp($currentTitolError['cp']);
                        if (is_numeric($currentTitolError['cp'])) {
                            $persona->setAddrnacionalitat('ESP');
                            
                            // Provincia catalana => cercar comarca
                            if (substr($currentTitolError['cp']."", 0, 2) == '17' ||
                                substr($currentTitolError['cp']."", 0, 2) == '08' ||
                                substr($currentTitolError['cp']."", 0, 2) == '25' ||
                                substr($currentTitolError['cp']."", 0, 2) == '43') {
                                    
                                $municipis = $this->consultaAjaxPoblacions($currentTitolError['cp'], 'cp');
                                
                                if ($municipis == null) throw new \Exception($id.'#ERROR. Municipi / Comarca persona  no trobat: '.$currentTitolError['cp']);
                                $municipi = $municipis[0];
                                
                                $persona->setAddrcomarca($municipi['comarca']);
                            }
                        }
                        else {
                            
                            switch ($currentTitolError['cp']) {
                            case 'AD300':
                                $persona->setAddrnacionalitat('AND');
                                break;
                            case 'BH228':
                                $persona->setAddrnacionalitat('GBR');
                                break;
                            case '3114T':
                                $persona->setAddrnacionalitat('GER');
                                break;
                            case 'L5531':
                                $persona->setAddrnacionalitat('FRA');
                                break;
                            case 'l5531':
                                $persona->setAddrnacionalitat('FRA');
                                break;
                            default:  
                                throw new \Exception($id.'#ERROR. Persona estrangera ?: '.$currentTitolError['cp']);
                            }
                        }
                    } else {
                        $persona->setAddrnacionalitat('ESP');
                    }
                            
                    if ($currentTitolError['poblacion'] != '' &&
                        $currentTitolError['poblacion'] != null) $persona->setAddrpob($currentTitolError['poblacion']);
                            
                    if ($currentTitolError['provincia'] != '' &&
                        $currentTitolError['provincia'] != null) $persona->setAddrprovincia($currentTitolError['provincia']);
                                
                            
                    $em->persist($persona);
                    $personesNoves++;
                }
                            
                $datadesde = $currentTitolError['iniciocurso'] != ''?\DateTime::createFromFormat('Y-m-d', $currentTitolError['iniciocurso']):null;
                $datafins = $currentTitolError['fincurso'] != ''?\DateTime::createFromFormat('Y-m-d', $currentTitolError['fincurso']):null;
                        
                if ($datadesde == null || $datafins == null) throw new \Exception($id.'#ERROR. Dates del curs incorrectes: '.$currentTitolError['iniciocurso'].'-'.$currentTitolError['fincurso']); //ERROR
                        
                // Cercar existent o crear curs sense docents
                $num = $currentTitolError['numcurso'];
                if (!isset($cursos[ $num ])) {
                    $curs = $this->getDoctrine()->getRepository('FecdasBundle:EntityCurs')->findOneBy(array('numfedas' => $num));
                            
                    if ($curs == null) {
                        $curs = new EntityCurs(null, $titol, $datadesde, $datafins, $club, $clubhistoric);
                        $curs->setNumfedas($num);
                        $curs->setValidat(true);
                        $curs->setFinalitzat(true);
                                
                        $em->persist($curs);
                                
                        $cursosNous++;
                    }
                    $cursos[$curs->getNumfedas()] = $curs;  // Afegir als cursos consultats
                            
                } else {
                    $curs = $cursos[ $num ];
                }
                        
                // Crear titulacions
                $titulacio = new EntityTitulacio($persona->getMetapersona(), $curs);
                $titulacio->setNumfedas($currentTitolError['numtitulo']);
                $titulacio->setDatasuperacio($datafins);
                        
                $em->persist($titulacio);
                        
                $titulacions++;
                        
            } catch (\Error $e) {
                
                $warnings[ $id ] = $e->getMessage();
            } catch (\Exception $e) {
                //$em->getConnection()->rollback();
                //echo "Problemes durant la transacció : ". $e->getMessage();
                        
                $errors[ $id ] = $e->getMessage();
            }
                    
        }
                
        $html = "";
        if (count($errors) != 0) {
            $html = "KO, errors ".count($errors)."<br/><br/>".implode("<br/>",$errors);
        }
        
        if (count($warnings) != 0) {
            $html = "WARN, avisos ".count($warnings)."<br/><br/>".implode("<br/>",$warnings);
        }
        
        if ($html != "") {
            $html = "TOTAL registres tractats: ".count($ids)."<br/><hr><br/>".$html;
            //return new Response($html);
        }
        
        
        if (count($ids) != 0) {
            $sql = "UPDATE importtitulacions SET error = 'Corregit' WHERE id IN (".implode(",", $ids).") ";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
        }
        
        $em->flush();
                
        return new Response("OK pesones noves ".$personesNoves." cursos nous ".$cursosNous." i titulacions ".$titulacions."<br/><hr><br/>".$html );
    }
    
    public function historicfederatsAction(Request $request) {
        // http://www.fecdas.dev/historicfederats?max=10&pag=1
        // http://www.fecdas.dev/historicfederats?id=1234
        /*
         * http://www.fecdas.dev/historicfederats?max=1000&pag=1
         * http://www.fecdas.dev/historicfederats?max=1000&pag=2
         */
        // Script de migració. Executar per migrar persones i desactivar
        
        if (!$this->isAuthenticated())
            return $this->redirect($this->generateUrl('FecdasBundle_login'));
            
        if (!$this->isCurrentAdmin())
            return $this->redirect($this->generateUrl('FecdasBundle_homepage'));
                
        $em = $this->getDoctrine()->getManager();
                
        $id = $request->query->get('id', 0);
        $max = $request->query->get('max', 2000);
        $pag = $request->query->get('pag', 1);
        $offset = $max * ($pag -1);
                
        $strQuery  = " SELECT i.* ";
        $strQuery .= " FROM importtitulacions i LEFT OUTER JOIN m_metapersones m ON i.dni = m.dni ";
        if ($id > 0) $strQuery .= " AND id = ".$id;
        $strQuery .= " ORDER BY dni, id ";
        if ($id == 0) $strQuery .= " LIMIT ".$max. " OFFSET ".$offset;
        $stmt = $em->getConnection()->prepare($strQuery);
        $stmt->execute();
        $titolsfederats = $stmt->fetchAll();
        
        $personesNoves = 0;
                
        $federacio = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find(BaseController::CODI_FECDAS);
        
        $club = null;
        $html = '';
        $currentdni = '';
        
        try {
            for ($i = 0; $i < count($titolsfederats); $i++) {

                $currentTitolFederat = $titolsfederats[$i];
                $dni = $currentTitolFederat['dni'];
                
                if ($currentdni == $dni) continue;
                
                $currentdni = $dni;
                $id = $currentTitolFederat['id'];
                
                // Cercar títol (NO pot ser null i només pot trobar un)
                $titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->findOneBy(array('codi' => $currentTitolFederat['abreviatura']));
                
                if ($titol == null || count($titol) != 1) continue; //ERROR
                
                if ($currentTitolFederat['club'] != '') {
                    $club = $this->cercarClubNOM($currentTitolFederat['club']);
                } else {
                    // Mirar si llicència tipus tècnic => club sense informar FECDAS
                    if ($titol->esInstructor()) $club = $federacio;
                }
                
                if ($club == null) continue;    // ERROR
                
                $nacionalitat = 'ESP';
                $comarca = null;
                $cp = null;
                if ($currentTitolFederat['cp'] != '' &&
                    $currentTitolFederat['cp'] != null) {
                    
                    $cp = $currentTitolFederat['cp'];
                    if (is_numeric($currentTitolFederat['cp'])) {
                        
                        // Provincia catalana => cercar comarca
                        if (substr($currentTitolFederat['cp']."", 0, 2) == '17' ||
                            substr($currentTitolFederat['cp']."", 0, 2) == '08' ||
                            substr($currentTitolFederat['cp']."", 0, 2) == '25' ||
                            substr($currentTitolFederat['cp']."", 0, 2) == '43') {
                                
                                $municipis = $this->consultaAjaxPoblacions($currentTitolFederat['cp'], 'cp');
                                
                                if ($municipis == null) throw new \Exception($id.'#ERROR. Municipi / Comarca persona  no trobat: '.$currentTitolFederat['cp']);
                                $municipi = $municipis[0];
                                
                                $comarca = $municipi['comarca'];
                            }
                    } else {
                        switch (strtoupper($currentTitolFederat['cp'])) {
                            case 'AD200':
                            case 'AD300':
                            case 'AD500':
                                $nacionalitat = 'AND';
                                break;
                            case 'BH228':
                                $nacionalitat = 'GBR';
                                break;
                            case '3114T':
                                $nacionalitat = 'GER';
                                break;
                            case 'L5531':
                                $nacionalitat = 'FRA';
                                break;
                            case 'SE50S':
                            case 'GH15':
                                $nacionalitat = 'GBR';
                                break;
                            case 'L8031':
                                $nacionalitat = 'LUX';
                                break;
                            case '2332P':
                                $nacionalitat = 'NLD';
                                break;
                                
                            default:
                                throw new \Exception($id.'#ERROR. Persona estrangera ?: '.$currentTitolFederat['cp']);
                        }
                    }
                }
                
                if (substr($dni."", 0, 1) == 'X' ||
                    substr($dni."", 0, 1) == 'Y' ||
                    substr($dni."", 0, 1) == 'Z') {
                        // NIE empieza por X, Y o Z
                        $dni = str_replace("-", "", $dni);
                        $nacionalitat = 'XXX';  // Sense especificar
                }
                    
                if ($nacionalitat == 'ESP') {
                    // Només DNI vàlid
                    $dniLletra = $dni;
                    if (is_numeric($dni) && $dni < 99999999) $dniLletra = str_pad( substr($dni."", 0, 8), 8, "0", STR_PAD_LEFT ).BaseController::getLletraDNI( (int) $dni );
                        
                    if (strlen($dniLletra) != 9) {
                        $nacionalitat = 'XXX';  // Sense especificar
                        $html .= "DNI incorrecte ".$dni." afegit com estranger. Revisar<br/>";
                        //continue;
                    } else {
                        $dni = $dniLletra;
                    }
                }
                
                // Cercar meta persona
                $persona = $this->cercarPersonaDNI($dni);
                
                if ($persona != null) continue;
                $metapersona = new EntityMetaPersona( $dni );
                $em->persist($metapersona);
                                
                $persona = new EntityPersona($metapersona, $club);
                $persona->setVAlidat(true);
                $persona->setAddrnacionalitat($nacionalitat);
                if ($comarca != null) $persona->setAddrcomarca($comarca);
                if ($cp != null) $persona->setAddrcp($cp);
                
                $nomCognoms = $currentTitolFederat['federado'];
                $nomArray = explode(",", $nomCognoms); // Cognoms + Nom
                                
                if (!isset($nomArray[0])) throw new \Exception($id.'#ERROR. Persona sense cognoms: '.$nomCognoms);
                if (!isset($nomArray[1])) throw new \Exception($id.'#ERROR. Persona sense nom: '.$nomCognoms);
                if (count($nomArray) != 2) throw new \Exception($id.'#ERROR. Persona nom incorrecte: '.$nomCognoms);
                $persona->setCognoms(mb_strtoupper($nomArray[0]));
                $persona->setNom(ucwords(strtolower($nomArray[1])));
                                
                $persona->setSexe($currentTitolFederat['sexo']);  // H o M
                                
                if ($currentTitolFederat['nacimiento'] == '' ||
                    $currentTitolFederat['nacimiento'] == null) throw new \Exception($id.'#ERROR. Persona sense data naixement: '.$currentTitolFederat['nacimiento']);
                                    
                $naixement = \DateTime::createFromFormat('Y-m-d', $currentTitolFederat['nacimiento']); // YYYY-MM-DD
                $persona->setDatanaixement($naixement);
                                    
                $persona->setDatamodificacio($this->getCurrentDate('now'));
                                    
                if ($currentTitolFederat['email'] != '' &&
                    $currentTitolFederat['email'] != null) $persona->setMail($currentTitolFederat['email']);
                                        
                if ($currentTitolFederat['movil'] != '' &&
                    $currentTitolFederat['movil'] != null &&
                    is_numeric($currentTitolFederat['movil'])) $persona->setTelefon1($currentTitolFederat['movil']);
                                            
                if ($currentTitolFederat['direccion'] != '' &&
                    $currentTitolFederat['direccion'] != null) $persona->setAddradreca($currentTitolFederat['direccion']);
                
                if ($currentTitolFederat['poblacion'] != '' &&
                    $currentTitolFederat['poblacion'] != null) $persona->setAddrpob($currentTitolFederat['poblacion']);
                                                        
                if ($currentTitolFederat['provincia'] != '' &&
                    $currentTitolFederat['provincia'] != null) $persona->setAddrprovincia($currentTitolFederat['provincia']);

                    
                    
                $em->persist($persona);
                $personesNoves++;
            }
            
            $em->flush();
            
            $html .= "OK pesones noves ".$personesNoves."<br/><hr><br/>";
            
        } catch (\Exception $e) {
            $html =  $e->getMessage();
        }
                    
        return new Response( $html );
    }
    
    private function cercarPersonaDNI($dni) {
        // Cercar meta persona
        $metapersona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $dni.""));	// Consulta directament com text
        
        if ($metapersona == null) {
            
            $dniLletra = $dni;
            if (is_numeric($dni) && $dni < 99999999) $dniLletra = str_pad( substr($dni."", 0, 8), 8, "0", STR_PAD_LEFT ).BaseController::getLletraDNI( (int) $dni );
            
            if ($dni != $dniLletra) {
                // Consulta amb lletra
                $metapersona = $this->getDoctrine()->getRepository('FecdasBundle:EntityMetaPersona')->findOneBy(array('dni' => $dniLletra));
            }
        }
        return $metapersona;
    }
    
    private function cercarClubNOM($nom) {
        $nomClub = str_replace(".", "", $nom);
        
        $em = $this->getDoctrine()->getManager();
        
        $strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
        $strQuery .= " WHERE  c.nom LIKE :club ";
        $query = $em->createQuery($strQuery);
        $query->setParameter('club', '%'.$nomClub.'%' );
        $clubs = $query->getResult();
        
        if ($clubs != null && count($clubs) == 1) {
            $clubs = array_values($clubs);
            return $clubs[0];
        }
        
        $strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityClub c ";
        $strQuery .= " WHERE  c.nom = :club ";
        $query = $em->createQuery($strQuery);
        $query->setParameter('club', $nomClub );
        $clubs = $query->getResult();
            
        if ($clubs != null && count($clubs) == 1) {
            $clubs = array_values($clubs);
            return $clubs[0];
        }
        
        return null;
    }
    
    /*private function cercarCurs($club, $titol, $datadesde, $datafins) {
        $em = $this->getDoctrine()->getManager();
        
        $strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityCurs c ";
        $strQuery .= " WHERE  c.club = :club ";
        $strQuery .= " AND  c.titol = :titol ";
        //$strQuery .= " AND  c.datadesde < :datafins ";
        //$strQuery .= " AND  c.datafins >= :datadesde ";
        $strQuery .= " AND  (c.datadesde = :datadesde OR c.datafins = :datafins)";
        $strQuery .= " AND  c.databaixa IS NULL ";
        $query = $em->createQuery($strQuery);
        $query->setParameter('club', $club->getCodi() );
        $query->setParameter('titol', $titol->getId() );
        $query->setParameter('datafins', $datafins->format('Y-m-d') );
        $query->setParameter('datadesde', $datadesde->format('Y-m-d') );
        $cursos = $query->getResult();
        
        if ($cursos != null && count($cursos) == 1) {
            $cursos = array_values($cursos);
            return $cursos[0];
        }
        
        return null;
    }*/
    
    private function cercarCursPerFederatClub($id, $club, $titol, $datadesde, $datafins, $metapersona) {
        $em = $this->getDoctrine()->getManager();
        
        $strQuery  = " SELECT c FROM FecdasBundle\Entity\EntityCurs c JOIN c.participants p JOIN p.metapersona m ";
        $strQuery .= " WHERE  c.club = :club ";
        $strQuery .= " AND  c.titol = :titol ";
        /*$strQuery .= " AND  c.datadesde < :datafins ";
        $strQuery .= " AND  c.datafins >= :datadesde ";*/
        $strQuery .= " AND  (c.datadesde = :datadesde AND c.datafins = :datafins)";
        $strQuery .= " AND  m.dni = :dni ";
        $strQuery .= " AND  c.databaixa IS NULL ";
        $query = $em->createQuery($strQuery);
        $query->setParameter('club', $club->getCodi() );
        $query->setParameter('titol', $titol->getId() );
        $query->setParameter('datafins', $datafins->format('Y-m-d') );
        $query->setParameter('datadesde', $datadesde->format('Y-m-d') );
        $query->setParameter('dni', $metapersona->getDni() );
        $cursos = $query->getResult();
        
        if ($cursos != null && count($cursos) == 1) {
            $cursos = array_values($cursos);
            return $cursos[0];
        }
        
        if (count($cursos) > 1) throw new \Exception($id.'#ERROR. Varis canditats a curs existent '.$metapersona->getId().'-'.$club->getCodi().'-'.$titol->getId().'-'.$datadesde->format('Y-m-d'));
        
        return null;
    }
        
	public function historictitolsAction(Request $request) {
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
		$federacio = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->find(BaseController::CODI_FECDAS);
		
		$strQuery  = " SELECT id, federado, dni, numcurso, iniciocurso, fincurso, ";
		$strQuery .= " club, abreviatura, numtitulo ";
		$strQuery .= " FROM importtitulacions ";
		$strQuery .= " WHERE error IS NULL ";
		if ($id > 0) $strQuery .= " AND id = ".$id;
		$strQuery .= " ORDER BY numcurso, numtitulo ";
		if ($id == 0) $strQuery .= " LIMIT ".$max. " OFFSET ".$offset;
		$stmt = $em->getConnection()->prepare($strQuery);
		$stmt->execute();
		$titols = $stmt->fetchAll();

		$cursosNous = 0;
		$cursosExistents = 0;
		$titulacions = 0;
		$titulacionsExistents = 0;
		
		$cursos = array();
		$stock = array();
		$errors = array();
		$missatges = array();
		$ids = array();

		for ($i = 0; $i < count($titols); $i++) {
			$currentTitol = $titols[$i]; 
				
			$id = $currentTitol['id'];
			$ids[] = $id; 
			$dni = $currentTitol['dni'];
			try {
				// Cercar meta persona
			    $metapersona = $this->cercarPersonaDNI($dni);
				
			    if ($metapersona == null) {
				    
				    if (substr($dni."", 0, 1) == 'X' ||
				        substr($dni."", 0, 1) == 'Y' ||
				        substr($dni."", 0, 1) == 'Z') {
				        // NIE empieza por X, Y o Z
				        $dni = str_replace("-", "", $dni);
				
				        $metapersona = $this->cercarPersonaDNI($dni);
				    }
				        
				    if ($metapersona == null) throw new \Exception($id.'#ERROR. Persona no trobada '.$currentTitol['abreviatura'].': #'.$currentTitol['dni'].'#'.$currentTitol['federado']); //ERROR
				}
				
				// Cercar títol (NO pot ser null i només pot trobar un) 
				$titol = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->findOneBy(array('codi' => $currentTitol['abreviatura'], 'online' => 0));
				
				if ($titol == null || count($titol) != 1) throw new \Exception($id.'#ERROR. Títol no trobat: #'.$currentTitol['abreviatura']); //ERROR
				
				// Cercar club (pot ser null i només pot trobar un) 
				//$club = $this->getDoctrine()->getRepository('FecdasBundle:EntityClub')->findOneBy(array('nom' => $currentTitol['club']));	
				$club = null;
				$clubhistoric = '';
				if ($currentTitol['club'] != '') {
					
				    $club = $this->cercarClubNOM($currentTitol['club']);
				    
				    if ($club == null) {
				        throw new \Exception($id.'#ERROR. Club no trobat: '.$currentTitol['club']); //ERROR
  						//$errors[ $id ] = $id.'#WARN. Club no trobat: '.$currentTitol['club'];// WARN CONTINUA
       					//$clubhistoric = $currentTitol['club'];
					}
				} else {
				    // Mirar si llicència tipus tècnic => club sense informar FECDAS
				    // Validar Federació per a cursos Instructors  => Titols tipus 'TE'
				    if (!$titol->esInstructor() && !$titol->esCompeticio()) {
				        //$clubhistoric = 'Sense informació del club';
				        throw new \Exception($id.'#ERROR. Sense informació del club curs no instructors ni competició: '.$titol->getTitol()); // ERROR
				    }
				    else $club = $federacio;
				}
				
				$datadesde = $currentTitol['iniciocurso'] != ''?\DateTime::createFromFormat('Y-m-d', $currentTitol['iniciocurso']):null;
				$datafins = $currentTitol['fincurso'] != ''?\DateTime::createFromFormat('Y-m-d', $currentTitol['fincurso']):null;  
				
				if ($datadesde == null || $datafins == null) throw new \Exception($id.'#ERROR. Dates del curs incorrectes: '.$currentTitol['iniciocurso'].'-'.$currentTitol['fincurso']); //ERROR
				
				// Cercar existent o crear curs sense docents
				$registreStock = false;
				
				if (!isset($cursos[ $currentTitol['numcurso'] ])) {

				    //$curs = $this->cercarCurs($club, $titol, $datadesde, $datafins);
					//$curs = $this->getDoctrine()->getRepository('FecdasBundle:EntityCurs')->findOneBy(array('numfedas' => $num));

					// Cercar curs federat
				    $curs = $this->cercarCursPerFederatClub($id, $club, $titol, $datadesde, $datafins, $metapersona);

				    if ($curs == null) {

				        // Provar titulacions online
				        // 'AO', 'B1E', 'B2E', 'B3E', 'B2S', 'B3O', 'BA', 'BSG', 'BNIB', 'BVS', 'BRE', 'BN', 'GGE', 'IT', 'ML', 'NS', 'ND', 'ENT', 'SR', 'RCP', 'BTN'
				        /*$titolOnline = -1;
				        if ($titol->getId() == 1) $titolOnline = 250;   //	AO
				        if ($titol->getId() == 48) $titolOnline = 251;	//  B1E
				        if ($titol->getId() == 50) $titolOnline = 252;	//  B2E
				        if ($titol->getId() == 51) $titolOnline = 253;    //	B3E
				        if ($titol->getId() == 248) $titolOnline = 254;    //		B2S
				        if ($titol->getId() == 249) $titolOnline = 255;    //		B3O
				        if ($titol->getId() == 10) $titolOnline = 256;    //		BA
				        if ($titol->getId() == 11) $titolOnline = 257;    //		BSG
				        if ($titol->getId() == 6) $titolOnline = 258;    //		BNIB
				        if ($titol->getId() == 9) $titolOnline = 259;    //		BVS
				        if ($titol->getId() == 13) $titolOnline = 260;    //		BRE
				        if ($titol->getId() == 14) $titolOnline = 261;    //		BN
				        if ($titol->getId() == 19) $titolOnline = 262;    //		GGE
				        if ($titol->getId() == 73) $titolOnline = 263;    //		IT
				        if ($titol->getId() == 81) $titolOnline = 264;    //		ML
				        if ($titol->getId() == 41) $titolOnline = 265;    //		NS
				        if ($titol->getId() == 246) $titolOnline = 266;    //		ND
				        if ($titol->getId() == 7) $titolOnline = 267;    //		ENT
				        if ($titol->getId() == 44) $titolOnline = 268;    //		SR
				        if ($titol->getId() == 45) $titolOnline = 269;    //		RCP
				        if ($titol->getId() == 5) $titolOnline = 270;    //		BTN
				        */
				        $titolOnline = $this->getDoctrine()->getRepository('FecdasBundle:EntityTitol')->findOneBy(array('codi' => $currentTitol['abreviatura'], 'online' => 1));
				        
				        if ($titolOnline != null) {
				            $curs = $this->cercarCursPerFederatClub($id, $club, $titolOnline, $datadesde, $datafins, $metapersona);
				        }
					}

					$pos = strpos($currentTitol['numcurso'], "/");
					if ($pos === false) throw new \Exception($id.'#ERROR. Número curs format KO: '.$currentTitol['numcurso']); //ERROR
					$numCurs = substr( $currentTitol['numcurso'], $pos+1 ); // Treure any
					
					if ($curs == null) {
					    $curs = new EntityCurs(null, $numCurs, $datadesde, $datafins, $club, $clubhistoric, $titol);			
					    $curs->setNumfedas($currentTitol['numcurso']);
						$curs->setValidat(true);
						$curs->setFinalitzat(true);
						$curs->setEditable(false);
						
						$em->persist($curs);
						
						$registreStock = $titol->esKitNecessari();
						
						$cursosNous++;
					} else {
					    $missatges[ $id ] = '#SMS Curs existent '.$currentTitol['numcurso']; 
					    
					    if (!$curs->finalitzat()) {
					        // Stock es registra quan el curs es valida
					        //throw new \Exception($id.'#ERROR. El curs no està finalitzat a FECDAS: '.$currentTitol['numcurso'].'-'.$curs->getId()); //ERROR
					        
					        if (!$curs->validat()) $registreStock = $titol->esKitNecessari();
					        
					        $curs->setEditable(false);
					        $curs->setValidat(true);
					        $curs->setFinalitzat(true);
					    }
					    $curs->setNumfedas($currentTitol['numcurso']);
					    $curs->setNum($numCurs);
					    $curs->setDatadesde($datadesde);
					    $curs->setDatafins($datafins);
					    $curs->setDatamodificacio($this->getCurrentDate());
					    $cursosExistents++;
					}
					$cursos[$curs->getNumfedas()] = array('curs' => $curs, 'stock' => $registreStock);  // Afegir als cursos consultats
					
				} else {
				    // Comprovació
				    $cursCheck = $this->cercarCursPerFederatClub($id, $club, $titol, $datadesde, $datafins, $metapersona);
				    
				    if ($cursCheck != null && $cursCheck->getId() != $curs->getId()) throw new \Exception($id.'#ERROR. Participants repartits en diferents cursos: '.$currentTitol['numcurso'].'-'.$cursCheck->getId().'-'.$curs->getId()); //ERROR
				    
				    $curs = $cursos[ $currentTitol['numcurso'] ]['curs'];
				    $registreStock = $cursos[ $currentTitol['numcurso'] ]['stock'];
				}

				$titulacio = $curs->getParticipantByMetaId($metapersona->getId());

				$numTitolArray = preg_split("/[\/-]+/", $currentTitol['numtitulo']);
				if (!is_array($numTitolArray)) throw new \Exception($id.'#ERROR. Format títol incorrecte: '.$currentTitol['numtitulo']); //ERROR
				
				$numTitol = $numTitolArray[count($numTitolArray) - 1];   // Darrer element
				if (!is_numeric($numTitol)) throw new \Exception($id.'#ERROR. Número títol no numèric: '.$numTitol); //ERROR
				
				if ($titulacio == null) {
    				// Crear titulacions 
    				$titulacio = new EntityTitulacio($metapersona, $curs);
    				
    				if ($curs->getId() != 0) throw new \Exception($id.'#ERROR. Titulació no trobada a curs existent: '.$currentTitol['numcurso'].' - '.$currentTitol['numtitulo'].'-'.$club->getCodi().'-'.$curs->getId()); //ERROR
    				
    				$registreStock = $titol->esKitNecessari();
    				
    				$em->persist($titulacio); 
    					
    				$titulacions++;
				} else {
				    if ($curs->getId() == 0) throw new \Exception($id.'#ERROR. Titulació duplicada a curs: '.$currentTitol['numcurso'].' - '.$currentTitol['numtitulo']); //ERROR
				    
				    $missatges[ $id ] = '#SMS titulacio existent '.$currentTitol['numtitulo']; 

				    $titulacio->setDatamodificacio($this->getCurrentDate());
				    $titulacionsExistents++;
				}
				$titulacio->setNumfedas($currentTitol['numtitulo']);
				$titulacio->setNum($numTitol);
				$titulacio->setDatasuperacio($datafins);
			
				// !!!!!!!!!!!!!!!!!!!!!!!!
				//  AFEGIR REGISTRE STOCK SI ESCAU
				// !!!!!!!!!!!!!!!!!!!!!!!!
				// Registrar sortida de kits
				
				if ($registreStock) {
				    $missatges[ $id ] = '#SMS Nova titulació descomptar stock '.$currentTitol['numcurso'].' - '.$currentTitol['numtitulo'];
				    
				    if (!isset($stock[ $currentTitol['numcurso'] ])) {
				        $stock[ $currentTitol['numcurso'] ] = array('curs' => $curs, 'kits' => 1);
				    } else {
				        $stock[ $currentTitol['numcurso'] ]['kits']++;
				    }
				}
				
			} catch (\Exception $e) {
				//$em->getConnection()->rollback();
				//echo "Problemes durant la transacció : ". $e->getMessage();
				
				$errors[ $id ] = $e->getMessage();
			}
		}

		// Registre stock
		foreach ($stock as $num => $registre) {
		    $curs = $registre['curs'];
		    $unitats = $registre['kits'];
		    
		    $titol = $curs->getTitol();
		    $club = $curs->getClub();
		    $kit = $titol->getKit();
		    
		    $stock = $this->consultaStockClubPerProducteData($kit, $club); // stock disponible
		    
		    try {
    		    if ($stock < $unitats) {
    		        //throw new \Exception('#WARN. Curs '.$curs->getNumActa().' l\'stock del club '.($club==null?'NULL':$club->getNom()).' de kits \''.$kit->getDescripcio().'\' és de '.$stock.' disponibles però són necessaris '.$unitats); //ERROR
    		        $missatges[ $id ] = '#SMS Curs '.$num.' l\'stock del club '.($club==null?'NULL':$club->getNom()).' de kits \''.$kit->getDescripcio().'\' és de '.$stock.' disponibles però són necessaris '.$unitats;
    		    } 
    		    $em = $this->getDoctrine()->getManager();
    		    
    		    $descripcio = $kit->getAbreviatura()=='KGG'?'GG Kit  Guia de Grup':$kit->getDescripcio();
    		    $comentaris = 'Tramitació curs '.$curs->getNumActa().'. ';
    		    
    		    $registreStockClub = $curs->getStock();
    		    if ($registreStockClub == null) {
    		        $comentaris .= $unitats.'x'.$descripcio;
    		        
    		        $registreStockClub = new EntityStock($club, $kit, $unitats, $comentaris, $curs->getDatafins(), BaseController::REGISTRE_STOCK_SORTIDA, null, $curs);
    		        
    		        $em->persist($registreStockClub);
    		        
    		        $curs->setStock($registreStockClub);
    		    } else {
    		        $missatges[ $id ] = '#SMS Registre stock existent '.$currentTitol['numcurso'].' - '.$currentTitol['numtitulo'].' - '.$curs->getId().' Increment '.$unitats.' unitats';
    		        
    		        $unitats += $registreStockClub->getUnitats();
    		        $comentaris .= $unitats.'x'.$descripcio;
    		        $registreStockClub->setUnitats($unitats);
    		        $registreStockClub->setComentaris($comentaris);
    		        $registreStockClub->setDatamodificacio($this->getCurrentDate());
    		    }
    		} catch (\Exception $e) {
		        //$em->getConnection()->rollback();
		        //echo "Problemes durant la transacció : ". $e->getMessage();
		        
		        $errors[ $id ] = $e->getMessage();
		    }
		}
		
		$response = '';
		$response .= implode("<br/>",$missatges)."<br/>";
		$response .= "OK cursos existents ".$cursosExistents." i titulacions actualitzades ".$titulacionsExistents;
		$response .= "OK cursos nous ".$cursosNous." i titulacions ".$titulacions;
		
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
				$response .= "KO <br/>".implode("<br/>",$errors)."<br/>";
				
				return new Response( $response );
			}
		}
		$em->flush();
		
		return new Response( $response );
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
		$result .= "        check: SELECT COUNT(*) FROM m_rebuts WHERE databaixa IS NULL AND dataentrada >= '".$desde->format('Y-m-d H:i:s')."' AND dataentrada < '".$current->format('Y-m-d H:i:s')."';<br/>";
		
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
		
		//SELECT COUNT(*) FROM m_rebuts WHERE databaixa IS NULL AND dataentrada < '2016-01-01 00:00:00' AND datapagament >= '2016-01-01 00:00:00'; ==> 0
		/*	
		$strQuery  = " SELECT r FROM FecdasBundle\Entity\EntityRebut r ";
		$strQuery .= " WHERE r.databaixa IS NULL AND r.dataentrada < :desde ";
		$strQuery .= " AND   r.datapagament >= :desde ";
		if ($codiclub != '') $strQuery .= " AND r.club = :club ";
				
		$query = $em->createQuery($strQuery);
		$query->setParameter('desde', $desdeTime );
		if ($codiclub != '') $query->setParameter('club', $codiclub );
			
		$rebutsAcumularInici = $query->getResult();			
			
		$result .= "Total de rebuts per acumular a l'inici ".count($rebutsAcumularInici)."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_rebuts WHERE databaixa IS NULL AND dataentrada < '".$desdeTime."' AND datapagament >= '".$desdeTime."'; <br/>";
		
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
						// Només per a rebuts posteriors al recull dels saldos per inicialitzar 2016-04-01 => SELECT * FROM `m_rebuts` WHERE databaixa IS NULL AND  dataentrada >= '2016-04-01 00:00:00' AND YEAR(datapagament) < 2016; // 0
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
		$result .= "        check: SELECT COUNT(*) FROM m_rebuts WHERE databaixa IS NULL AND dataentrada >= '".$inicirevisio->format('Y-m-d H:i:s')."' AND dataentrada < '".$current->format('Y-m-d H:i:s')."';<br/>";
		$result .= "Total rebuts anteriors ".$totalRebutsAnteriors."<br/>";
		$result .= "        check: SELECT COUNT(*) FROM m_rebuts WHERE databaixa IS NULL AND YEAR(dataentrada) < YEAR(datapagament) AND datapagament >= '".$inicirevisio->format('Y-m-d H:i:s')."' AND datapagament < '".$current->format('Y-m-d H:i:s')."';<br/>";
		
		
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
		$strQuery .= " WHERE r.databaixa IS NULL ";
		$strQuery .= " AND   r.datapagament >= :desde ";
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
				
				//$key = -1; 
				//$ncUpd = 0;
				
				$jsonCarrecsUpd = array();
				foreach ($jsonCarrecs as $value) {
					
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
			
			//$repository = $this->getDoctrine()->getRepository('FecdasBundle:EntityComanda');
			
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
		//$batchSize = 20;
		//$current = $request->query->get('current', date('Y')); 
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
		//$comanda = null;
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
											p.clubparte = :club AND
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
											p.clubparte = :club AND
											p.databaixa IS NULL	")->setParameter('rel', $relacio)
											->setParameter('club', $anulacio['codiclub']);
					
					$parte = $query->setMaxResults(1)->getOneOrNullResult();
					if ($parte == null) {
						// Provar 2014 
						$query = $em->createQuery("SELECT p FROM FecdasBundle:EntityParte p 
											WHERE p.numrelacio = :rel AND 
											p.dataalta >= '2014-01-01 00:00:00' AND
											p.clubparte = :club AND
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
		
		//$batchSize = 20;
	
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
			foreach ($numapuntsClub as $value) {
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
					//$ingres = true; 
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
			
			foreach ($testArrayFactures as $numFactura) {
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
