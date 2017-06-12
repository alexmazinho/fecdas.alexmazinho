<?php
namespace FecdasBundle\Entity;

use FecdasBundle\Classes\RedsysAPI;


/**
 * @author alex
 *
 */

class EntityPayment {

	protected $url;
	protected $environment;
	protected $redsysapi;
    protected $version;
    protected $key;
	
	public function __construct($id, $environment, $preu, $desc, $titular, $src, $url, $urlmerchant, 
	                           $fuc, $currency, $trans, $terminal, $name, $lang, $version, $key) {
		/**
		 * Configuració sermepa
		 * host: http://www.fecdasgestio.cat
		 * mail: webadmin@fecdasgestio.cat
		 * Con notificación ON-LINE: HTTP + Email Comercio 
		 * Asíncrona: Enviament al client i comerç del resultat alhora
		 * Parámetros en las URLs : Si
		 * URL_OK: http://www.fecdasgestio.cat/notificacioOK  (Botó continuar)
		 * URL_KO: http://www.fecdasgestio.cat/notificacioKO  (Botó cancel o tancar finestra)
		 * 
		 * Tarjeta test: 4548812049400004
		 *	Fecha de caducidad: 12/12
		 *	Código de Seguridad: 123
		 *	CIP: 123456
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
		
		$this->url = $url;
		$this->version = $version;
        $this->key = $key;
        
		$dades = $id.";".$src.";".$this->environment;  //Ds_Merchant_MerchantData, retorn notificació on-line
		
		// Canvis API RedSys
		// Se crea Objeto
		$this->redsysapi = new RedsysAPI();
		
		// Se Rellenan los campos
		$this->redsysapi->setParameter("DS_MERCHANT_AMOUNT",$total);
		$this->redsysapi->setParameter("DS_MERCHANT_ORDER",$numordre);
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTCODE",$fuc);
		$this->redsysapi->setParameter("DS_MERCHANT_CURRENCY",$currency);
		$this->redsysapi->setParameter("DS_MERCHANT_TRANSACTIONTYPE",$trans);
		$this->redsysapi->setParameter("DS_MERCHANT_TERMINAL",$terminal);
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTURL",$urlmerchant);
		$this->redsysapi->setParameter("DS_MERCHANT_URLOK","");		// Al TPV
		$this->redsysapi->setParameter("DS_MERCHANT_URLKO","");		// Al TPV
        $this->redsysapi->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION", $desc);		// Comanda
		$this->redsysapi->setParameter("DS_MERCHANT_TITULAR", $titular);				// Nom club
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTNAME", $name);	
		$this->redsysapi->setParameter("DS_MERCHANT_CONSUMERLANGUAGE", $lang);		    // Català
		$this->redsysapi->setParameter("DS_MERCHANT_MERCHANTDATA", $dades);												// Dades per notificació de tornada
		
	}

	public function getParams()
    {
    	// Se generan los parámetros de la petición
    	return $this->redsysapi->createMerchantParameters();
    }
    public function getVersion()
    {
    	return $this->version;
    }
	

	public function getSignatura() 
	{
		$signature = $this->redsysapi->createMerchantSignature($this->key);
		return $signature; 
		
	}
	
    public function getUrl()
    {
    	return $this->url;
    }

}