<?php
/**
* @Service()
* @Tag("cartcheckout")
*/
namespace FecdasBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\FormFactory;
use FecdasBundle\Controller\BaseController;

class CartCheckOut
{
	protected $session;
	protected $requestStack;
	protected $formfactory;
	protected $cart;
	protected $checkRole;
	protected static $em;
	

	public function __construct(Session $session, RequestStack $requestStack, Registry $doctrine, FormFactory $formfactory, RoleChecker $checkRole)
    {
        $this->session = $session;	
        $this->requestStack = $requestStack;
        $this->formfactory = $formfactory;
        $this->checkRole = $checkRole;
        $this->getSessionCart();
		self::$em = $doctrine->getManager();
    }

    
    public function getSessionCart()
    {
        // Recollir cistella de la sessió
        $this->cart = $this->session->get('cart', array('productes' => array(), 'tarifatransport' => 0)); // Crear cistella buida per defecte
        return $this->cart;
    }
    
    public function initSessionCart()
    {
        $this->session->remove('cart');
        $this->getSessionCart();
    }
    
    public function addProducteToCart($idProducte, $unitats, $extra = array())
    {
        $this->getSessionCart();
        
        $producte = self::$em->getRepository('FecdasBundle:EntityProducte')->find($idProducte);
        
        if ($producte == null) throw new \Exception("Producte no trobat");
        
        if ($unitats == 0) throw new \Exception("Cal indicar el nombre d'unitats del producte");
        
        // if ($unitats < 0 && !$this->isCurrentAdmin()) throw new \Exception("El nombre d'unitats és incorrecte");
        if ($unitats < 0 && !$this->checkRole->isCurrentAdmin()) throw new \Exception("El nombre d'unitats és incorrecte");
        
        // Comprovar que tots els detalls siguin d'abonament o normals
        if (count($this->cart['productes']) > 0) {
            $abonament = false;
            foreach ($this->cart['productes'] as $info) {
                if ($info['unitats'] < 0) $abonament = true;
            }
            
            if (($abonament == true && $unitats > 0) ||
                ($abonament == false && $unitats < 0)) throw new \Exception("No es poden barrejar abonaments i comandes normals");
        }
        
        $import = $producte->getPreuAny(date('Y'));
        $iva = $producte->getIvaAny(date('Y')); /* % IVA aplicar */
        
        if ( !isset( $this->cart['productes'][$idProducte] ) ) {
            $this->cart['productes'][$idProducte] = array(
                'abreviatura' 	=> $producte->getAbreviatura(),
                'descripcio' 	=> $producte->getDescripcio(),
                'transport'		=> $producte->getTransport(),
                'pes'			=> 0,
                'unitats' 		=> $unitats,
                'extra'         => count($extra) > 0?$extra:'',
                'import' 		=> $import,
                'iva'           => $iva
            );
        } else {
            $this->cart['productes'][$idProducte]['unitats'] += $unitats;
            if (count($extra) > 0) $this->cart['productes'][$idProducte]['extra'] = $extra;
        }
        
        $unitats = $this->cart['productes'][$idProducte]['unitats'];
        
        if ($producte->getTransport() && $unitats > 0) $this->cart['productes'][$idProducte]['pes'] = $unitats * $producte->getPes();
        
        if ($this->cart['productes'][$idProducte]['unitats'] == 0 ||
            ($this->cart['productes'][$idProducte]['unitats'] < 0  && !$this->checkRole->isCurrentAdmin())) {
                // Afegir unitats < 0
            unset( $this->cart['productes'][$idProducte] );
        }
            
        if (count($this->cart['productes']) <= 0) {
            $this->session->remove('cart');
            $this->getSessionCart();
        } else {
            $this->session->set('cart', $this->cart);
        }
    }
    
    public function formulariTransport() {
        // Revisar si cal transport
        $this->getSessionCart();
        $tarifa = 0;
        $total = BaseController::getTotalNetDetalls($this->convertCartToDetalls());
        $iva = BaseController::getTotalIVADetalls($this->convertCartToDetalls());
        if (count($this->cart['productes']) > 0) {
            $producte = self::$em->getRepository('FecdasBundle:EntityProducte')->findOneByCodi(BaseController::PRODUCTE_CORREUS);
            $unitats = $this->getUnitatsTarifaTransport();
            $tarifa = $unitats * ($producte != null?$producte->getCurrentPreu():0);
 
            $this->cart['tarifatransport'] = $tarifa;
            
            $this->session->set('cart', $this->cart);
        }
        
        $formBuilder = $this->formfactory->createBuilder()
        ->add('tarifatransport', 'hidden', array(
            'data' => $tarifa
        ));
        $formBuilder->add('importnetcomanda', 'hidden', array(
            'data' => $total
        ));
        $formBuilder->add('ivacomanda', 'hidden', array(
            'data' => $iva
        ));
        $formBuilder->add('importcomanda', 'hidden', array(
            'data' => $total + $iva
        ));
        $formBuilder->add('transport', 'choice', array(
            'choices'   => array(0 => 'Incloure enviament', 1 => 'Recollir a la federació'),
            'multiple'  => false,
            'expanded'  => true,
            'data' 		=> 0
        ));
        
        return $formBuilder->getForm()->createView();
    }
    
    public function getPesComandaCart()
    {
        $this->getSessionCart();
        
        $pesComanda = 0;
        foreach ($this->cart['productes'] as $info) {
            if (isset($info['transport']) && $info['transport'] == true)	{
                $pesComanda += $info['pes'];
            }
        }
        return $pesComanda;
    }
    
    private function convertCartToDetalls()
    {
        $this->getSessionCart();
        
        $detalls = array();
        foreach ($this->cart['productes'] as $info) {
            $detalls[] = array('total' => $info['unitats'],
                'preuunitat' => $info['import'],
                'ivaunitat' => $info['iva'],
                'descompte' => 0);
        }
        
        return $detalls;
    }
    
    public function getUnitatsTarifaTransport()
    {
        /*  Anul·lat, sempre tarifa 1
        $pes = $this->getPesComandaCart();
        if (!is_numeric($pes)) return 1;
        if ($pes <= 0) return 1;
        
        if ($pes > BaseController::TARIFA_MINPES3) return 3;
        if ($pes > BaseController::TARIFA_MINPES2) return 2;*/
        
        return 1;
    }
    
}