<?php
namespace FecdasBundle\Entity;

use FecdasBundle\Classes\RedsysAPI;
use FecdasBundle\Controller\BaseController; 

/**
 * @author alex
 *
 */

class EntityPayment {

	protected $url;
	protected $environment;
	protected $redsysapi;
	
	public function __construct($id, $environment, $preu, $desc, $titular, $src) {
		/**
		 * Configuració sermepa
		 * host: http://www.fecdasgestio.cat
		 * mail: webadmin@fecdasgestio.cat
		 * Con notificación ON-LINE: HTTP + Email Comercio 
		 * Asíncrona: Enviament al client i comerç del resultat alhora
		 * Parámetros en las URLs : Si
		 * URL_OK: http://www.fecdasgestio.cat/notificacioOK  (Botó continuar)
		 * URL_KO: http://www.fecdasgestio.cat/notificacioKO  (Botó cancel o tancar finestra)
		 */
		//$total=$preu;
		$total = round($preu,2);
		$total = number_format($total, 2, '.', '');
		$total = preg_replace('/\./', '', $total);

		$this->environment = $environment;
		
		if ($id < 1000) $id = sprintf("%04d", $id);
		
		/* Substituir tot allò que no siguin lletres  " " "-" "'" "(" ")"
		 */
		$strtitular = $titular;  // Club
		$strtitular = str_replace(' ', '', $strtitular);
		$strtitular = str_replace('-', '', $strtitular);
		$strtitular = str_replace("'", '', $strtitular);
		$strtitular = str_replace('(', '', $strtitular);
		$strtitular = str_replace(')', '', $strtitular);
		$strtitular = str_replace('.', '', $strtitular);

		// Això genera num d'ordres duplicats. Afegir segons
		//$str = $parteId . $strtitular;
		$str = $id . date('s') . $strtitular;
		
		// Ordre 12 Alfanumèric  4 num + 8 ascii Format numordre 9999AAAAAAAA
		$numordre = sprintf("%'x-12s", $str);
		$numordre = substr($numordre, 0, 11);
		
		if ($this->environment == 'dev') {
			$this->url = BaseController::COMERC_REDSYS_URL_TEST; // Test
			$urlmerchant = BaseController::COMERC_REDSYS_URLMER_TEST;
		} else {
			$this->url = BaseController::COMERC_REDSYS_URL; // Real
			$urlmerchant = BaseController::COMERC_REDSYS_URLMER;
		}
		
		$dades = $id.";".$src.";".$this->environment;  //Ds_Merchant_MerchantData, retorn notificació on-line
		
		// Canvis API RedSys
		// Se crea Objeto
		$this->redsysapi = new RedsysAPI();
		
		// Se Rellenan los campos
		$this->redsysapi->setParameter("DS_MERCHANT_AMOUNT",$total);
		$this->redsysapi->setParameter("DS_MERCHANT_ORDER",$numordre);
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTCODE",BaseController::COMERC_REDSYS_FUC);
		$this->redsysapi->setParameter("DS_MERCHANT_CURRENCY",BaseController::COMERC_REDSYS_CURRENCY);
		$this->redsysapi->setParameter("DS_MERCHANT_TRANSACTIONTYPE",BaseController::COMERC_REDSYS_TRANS);
		$this->redsysapi->setParameter("DS_MERCHANT_TERMINAL",BaseController::COMERC_REDSYS_TERMINAL);
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTURL",$urlmerchant);
		$this->redsysapi->setParameter("DS_MERCHANT_URLOK","");		// Al TPV
		$this->redsysapi->setParameter("DS_MERCHANT_URLKO","");		// Al TPV
		$this->redsysapi->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION", $desc);								// Comanda
		$this->redsysapi->setParameter("DS_MERCHANT_TITULAR", $titular);										// Nom club
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTNAME", BaseController::COMERC_REDSYS_MERCHANTNAME);	// FECDAS
		$this->redsysapi->setParameter("DS_MERCHANT_CONSUMERLANGUAGE", BaseController::COMERC_REDSYS_LANG);		// Català - 3
		$this->redsysapi->setParameter("DS_MERCHANT_DATA", $dades);												// Dades per notificació de tornada
		
	}

	public function getParams()
    {
    	// Se generan los parámetros de la petición
    	return $this->redsysapi->createMerchantParameters();
    }
    public function getVersion()
    {
    	return BaseController::COMERC_REDSYS_SHA_256_VERSION;
    }
	

	public function getSignatura() 
	{
		if ($this->environment == 'dev') $signature = $this->redsysapi->createMerchantSignature(BaseController::COMERC_REDSYS_SHA_256_KEY_TEST);
		else $signature = $this->redsysapi->createMerchantSignature(BaseController::COMERC_REDSYS_SHA_256_KEY);
 
		return $signature; 
		
	}
	
    public function getUrl()
    {
    	return $this->url;
    }

}