<?php
namespace FecdasBundle\Entity;

use FecdasBundle\Classes\RedsysAPI;
use FecdasBundle\Controller\BaseController; 

/**
 * @author alex
 *
 */

class EntityPayment {

	/* Calcul signatura */
	protected $preu;
	
	protected $numordre;

	protected $codi;
	
	protected $terminal;
	
	protected $moneda;
	
	protected $tipusTx;
	
	protected $urlmerchant;
	
	protected $paymethods;
	
	/* Altres opcionals */
	
	protected $url;
	
	protected $lang;
	
	protected $desc;
	
	protected $titular;
	
	protected $fecdas;
	
	protected $dades;
	
	protected $params;
	
	protected $version;
	
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
		$total=$preu;
		$this->preu = round($total,2);
		$this->preu = number_format($this->preu, 2, '.', '');
		$this->preu = preg_replace('/\./', '', $this->preu);

		$this->environment = $environment;
		
		if ($id < 1000) $id = sprintf("%04d", $id);
		
		/* Substituir tot allò que no siguin lletres  " " "-" "'" "(" ")"
		 * Format numordre 9999AAAAAAAA
		 */
		$strtitular = $titular;
		$strtitular = str_replace(' ', '', $strtitular);
		$strtitular = str_replace('-', '', $strtitular);
		$strtitular = str_replace("'", '', $strtitular);
		$strtitular = str_replace('(', '', $strtitular);
		$strtitular = str_replace(')', '', $strtitular);
		$strtitular = str_replace('.', '', $strtitular);

		// Això genera num d'ordres duplicats. Afegir segons
		//$str = $parteId . $strtitular;
		$str = $id . date('s') . $strtitular;
		
		$this->numordre = sprintf("%'x-12s", $str);
		$this->numordre = substr($this->numordre, 0, 11);
		
		$this->codi = BaseController::COMERC_REDSYS_FUC;
		$this->terminal = BaseController::COMERC_REDSYS_TERMINAL;
		$this->moneda = BaseController::COMERC_REDSYS_CURRENCY;
		$this->tipusTx = BaseController::COMERC_REDSYS_TRANS;

		if ($this->environment == 'dev') {
			$this->url = BaseController::COMERC_REDSYS_URL_TEST; // Test
			$this->urlmerchant = BaseController::COMERC_REDSYS_URLMER_TEST;
		} else {
			$this->url = BaseController::COMERC_REDSYS_URL; // Real
			$this->urlmerchant = BaseController::COMERC_REDSYS_URLMER;
		}
		
		
		$this->paymethods = 'TR';
		$this->lang = '3';
		$this->desc = $desc;
		$this->titular = $titular;
		$this->fecdas =  "FECDAS";//"Federació Catalana d'Activitats Subaquàtiques";
		$this->dades = $id.";".$src.";".$this->environment;  //Ds_Merchant_MerchantData, retorn notificació on-line
		
		// Canvis API RedSys
		// Se crea Objeto
		$this->redsysapi = new RedsysAPI();
		
		// Se Rellenan los campos
		$this->redsysapi->setParameter("DS_MERCHANT_AMOUNT",$this->preu);
		$this->redsysapi->setParameter("DS_MERCHANT_ORDER",$this->numordre);
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTCODE",BaseController::COMERC_REDSYS_FUC);
		$this->redsysapi->setParameter("DS_MERCHANT_CURRENCY",BaseController::COMERC_REDSYS_CURRENCY);
		$this->redsysapi->setParameter("DS_MERCHANT_TRANSACTIONTYPE",BaseController::COMERC_REDSYS_TRANS);
		$this->redsysapi->setParameter("DS_MERCHANT_TERMINAL",BaseController::COMERC_REDSYS_TERMINAL);
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTURL",$this->urlmerchant);
		$this->redsysapi->setParameter("DS_MERCHANT_URLOK",$this->url);		
		$this->redsysapi->setParameter("DS_MERCHANT_URLKO",$this->url);

		// Se generan los parámetros de la petición
		$this->version = BaseController::COMERC_REDSYS_SHA_256_VERSION;
		$this->params = $this->redsysapi->createMerchantParameters();
		
	}

	public function getSignatura() 
	{
		/*if ($this->environment == 'dev') {
			$clau = 'qwertyasdf0123456789';  // Clau test
		} else {
			$clau = '4P996LR506200O24';  // Clau Real			
		}
		
		// SHA-256 clau  s4jFKC+wH4PR648I8JH6V1sk8yXe6glz
		$message = $this->preu.$this->numordre.$this->codi.$this->moneda.$this->tipusTx.$this->urlmerchant.$clau;
		
		return strtoupper(sha1($message));*/
		
		$signature = $this->redsysapi->createMerchantSignature(BaseController::COMERC_REDSYS_SHA_256_KEY);
		return $signature; 
		
	}
	
    public function getPreu()
    {
        return $this->preu;
    }

    public function getNumordre()
    {
    	return $this->numordre;
    }
    
    public function getCodi()
    {
    	return $this->codi;
    }

    public function getTerminal()
    {
    	return $this->terminal;
    }
    
    public function getMoneda()
    {
    	return $this->moneda;
    }

    public function getTipusTx()
    {
    	return $this->tipusTx;
    }

    public function getUrlmerchant()
    {
    	return $this->urlmerchant;
    }

    public function getPaymethods()
    {
    	return $this->paymethods;
    }
    
    public function getUrl()
    {
    	return $this->url;
    }
    
    public function getLang()
    {
    	return $this->lang;
    }
    
    public function getDesc()
    {
    	return $this->desc;
    }
    
    public function getTitular()
    {
    	return $this->titular;
    }
    
    public function getFecdas()
    {
    	return $this->fecdas;
    }
    
    public function getDades()
    {
    	return $this->dades;
    }

    public function getParams()
    {
    	return $this->params;
    }
    public function getVersion()
    {
    	return $this->version;
    }
    
}