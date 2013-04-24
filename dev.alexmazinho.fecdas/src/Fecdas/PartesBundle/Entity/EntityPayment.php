<?php
namespace Fecdas\PartesBundle\Entity;

/**
 * @author alex
 *
 */
use Symfony\Component\Validator\Constraints\All;

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
	
	protected $environment;
	
	public function __construct($preu, $desc, $titular, $dades) {
		/**
		 * Configuració sermepa
		 * host: http://www.fecdasgestio.cat
		 * mail: webadmin@fecdasgestio.cat
		 * Con notificación ON-LINE: HTTP + Email Comercio 
		 * Asíncrona: Enviament al client i comerç del resultat alhora
		 * Parámetros en las URLs : Si
		 * URL_OK: http://www.fecdasgestio.cat/notificacio  (Botó continuar)
		 * URL_KO: http://www.fecdasgestio.cat/notificacio  (Botó cancel o tancar finestra)
		 */
		
		$total=$preu;
		$this->preu = round($total,2);
		$this->preu = number_format($this->preu, 2, '.', '');
		$this->preu = preg_replace('/\./', '', $this->preu);

		//$this->numordre = date('ymdHis');

		$dades_array = explode("&", $dades);
		$parteId = $dades_array[0];
		$this->environment = $dades_array[1];
		
		if ($parteId < 1000) $parteId = sprintf("%04d", $parteId);
		
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
		$str = $parteId . date('s') . $strtitular;
		
		$this->numordre = sprintf("%'x-12s", $str);
		$this->numordre = substr($this->numordre, 0, 11);
		
		$this->codi = '322483330';
		$this->terminal = '1';
		$this->moneda = '978';
		$this->tipusTx = '0';

		if ($this->environment == 'dev') {
			$this->url = 'https://sis-t.sermepa.es:25443/sis/realizarPago'; // Test
			$this->urlmerchant = 'http://www.fecdasgestio.cat/app_dev.php/notificacio';
		} else {
			$this->url = 'https://sis.sermepa.es/sis/realizarPago'; // Real
			$this->urlmerchant = 'http://www.fecdasgestio.cat/app.php/notificacio';
		}
		$this->paymethods = 'TR';
		$this->lang = '3';
		$this->desc = $desc;
		$this->titular = $titular;
		$this->fecdas =  "FECDAS";//"Federació Catalana d'Activitats Subaquàtiques";
		$this->dades = $dades;  //Ds_Merchant_MerchantData, retorn notificació on-line
		
	}

	public function getSignatura() 
	{
		if ($this->environment == 'dev') {
			$clau = 'qwertyasdf0123456789';  // Clau test
		} else {
			$clau = '4P996LR506200O24';  // Clau Real			
		}
		$message = $this->preu.$this->numordre.$this->codi.$this->moneda.$this->tipusTx.$this->urlmerchant.$clau;
		return strtoupper(sha1($message));
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
    
}