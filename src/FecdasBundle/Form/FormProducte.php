<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityProducte;


class FormProducte extends AbstractType  implements EventSubscriberInterface {

	public static function getSubscribedEvents() {

		// Tells the dispatcher that you want to listen on the form.pre_set_data
		// event and that the preSetData method should be called.
		return array (
				
				//FormEvents::POST_SUBMIT => array('postSubmitData', 900),  // Desactiva validació
				FormEvents::SUBMIT => array('submitData', 900),
				FormEvents::PRE_SET_DATA => 'preSetData'
		);
	}
	
	private $anypreu;
	private $stock;
	
	public function __construct($anypreu = 0, $stock = 0)
	{
		if ($anypreu == 0) $anypreu = date('Y');
		
		$this->anypreu = $anypreu;
		$this->stock = $stock;
	}
	
	
	// Mètode del subscriptor => implements EventSubscriberInterface
	public function preSetData(FormEvent $event) {
		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
		$form = $event->getForm();
		$producte = $event->getData();
	
		/* Check we're looking at the right data/form */
		if ($producte instanceof EntityProducte) {
				
			$preu = $producte->getPreu($this->anypreu);
			
			$form->add ( 'idpreu', 'hidden', array (
					'mapped' 	=> false,
					'data' 		=> ($preu == null?0:$preu->getId())
			));
			
			$form->add('abreviatura', 'text', array(
			        'required'  => true,
			        'attr'      => array('maxlength' => '3'),
			        'data' 		=> $producte->getAbreviatura(),
			        'disabled' 	=> !$producte->esNou()
			));
				
			// Selector anys
			$form->add('anypreus', 'choice', array(
					'choices'   => BaseController::getArrayAnysPreus($this->anypreu),
					'multiple'  => false,
					'expanded'  => false,
					'mapped' 	=> false,
					'data' 		=> ($preu == null?0:$preu->getAnypreu()),
			));
			
			$form->add ( 'preu', 'number', array (
					'required' 	=> true,
					'mapped' 	=> false,
					'scale' 	=> 2,
					'data' 		=> ($preu == null?0:$preu->getPreu()),
					'mapped' 	=> false
			));
					
			$form->add('iva', 'choice', array(
					'choices'   => BaseController::getIVApercents(),
					'multiple'  => false,
					'expanded'  => false,
					'mapped' 	=> false,
					'data' 		=> ($preu == null?0:$preu->getIva()),
					
			));
			
			$form->add('stockable', 'checkbox', array(
    			    'required' 		=> false,
    			    'data' 			=> $producte->getStockable(),
			        'disabled' 	    => !$producte->esNou()    // No deixar modificar aquest valor
			));
			
			$form->add ('limitnotifica', 'integer', array (
					'required' 		=> false,
					'scale' 		=> 0,
			        'data' 			=> $producte->getLimitnotifica(),
					'disabled' 		=> !$producte->getStockable()
			));
			
			$form->add ('pes', 'integer', array (
					'required' 		=> false,
					'scale' 		=> 0,
			        'data' 			=> $producte->getPes(),
					'attr'			=>	array('readonly' => $producte->getTransport() != true)
			));
			
			$form->add('transport', 'checkbox', array(
					'required' 		=> false,
					'data' 			=> $producte->getTransport() == true
			));
		
			$form->add('visible', 'checkbox', array(
					'required' 		=> false,
					'data' 			=> $producte->getVisible() == true
			));
			
			// Selector departament compta
			/*$form->add('departament', 'choice', array(
					'choices'   => BaseController::getDepartamentsConta( 0 ),
					'multiple'  => false,
					'expanded'  => false,
					'placeholder' => '',
					'data' 		=> $producte->getDepartament(),
			));*/
			
			$this->subdepartamentFormModifier($form, $producte);
			
			/*$subdepartaments = array();
			if ($producte->getDepartament() == 0) $subdepartaments = BaseController::getDepartamentsConta( -1 );
			else $subdepartaments = BaseController::getDepartamentsConta( $producte->getDepartament() ); 
			
			// Selector subdepartament compta
			$form->add('subdepartament', 'choice', array(
					'choices'   => $subdepartaments,
					'multiple'  => false,
					'expanded'  => false,
					'data' 		=> $producte->getSubdepartament(),
			));*/
			
			if ($producte->getTipus() == BaseController::TIPUS_PRODUCTE_LLICENCIES && !$producte->esNou()) {
					$activat = true;
					if ($producte->getCategoria() != null && 
						$producte->getCategoria()->getTipusparte() != null) $activat =  $producte->getCategoria()->getTipusparte()->getActiu();
					
					$form->add('activat', 'checkbox', array(
							'required' 	=> false,
							'mapped' 	=> false,
							'data' 		=> $activat
					));
			} else {
			    $form->add('activat', 'hidden', array(
                            'required'  => false,
                            'mapped'    => false,
                    ));
			}
			/*$form->add ( 'iva', 'number', array (
					'required' => true,
					'mapped' => false,
					'scale' => 2,
					'data' => $preu->getIva(),
					'mapped' => false,
					'constraints' => array (
							new NotBlank ( array ( 'message' => 'Cal indicar l\'IVA.' )),
							new Type ( array ('type' => 'numeric', 'message' => 'L\'IVA ha de ser numèric.')),
							new GreaterThanOrEqual ( array ('value' => 0, 'message' => 'l\'IVA no és vàlid.'))
					)
			));
			*/
		}
	}
	
	// No propagar, evita validacions
	/*public function postSubmitData(FormEvent $event) {
	
		//$event->stopPropagation();
	}*/
	
	public function submitData(FormEvent $event) {
		// It's important here to fetch $event->getForm()->getData(), as
		// $event->getData() will get you the client data (that is, the ID)
		/*$producte = $event->getForm()->getData();
		$form = $event->getForm ();
		$origen = $form->get('anypreus')->getData(); // Detectar origen, si és selector anys refrescar els valors del preu, iva...
		*/
	
	}
	
// Afegir subdepartament en funció del valor escollit a departament
	public function subdepartamentFormModifier(FormInterface $form, EntityProducte $producte = null) {
		$subdepartaments = array();
		if ($producte == null || $producte->getDepartament() == 0) $subdepartaments = BaseController::getDepartamentsConta( -1  );
		else $subdepartaments = BaseController::getDepartamentsConta( $producte->getDepartament()  );	
			
		// Selector subdepartament compta
		$form->add('subdepartament', 'choice', array(
				'choices'   => $subdepartaments,
				'multiple'  => false,
				'expanded'  => false,
				'data' 		=> $producte->getSubdepartament(),
		));
			
    }
    	
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventSubscriber ( new FormProducte () );
		
		$builder->add('id', 'hidden');
		
		
		$builder->add('codi', 'text', array(
				'required' => true,
		));
		
		$builder->add('descripcio', 'textarea', array(
				'required' => true,
				'attr' => array('rows' => '4'),
		));
		
		$builder->add('tipus', 'choice', array(
				'choices'   => BaseController::getTipusDeProducte(),
				'multiple'  => false,
				'expanded'  => false,
		));
		
		$builder->add ('minim', 'integer', array (
				'required' => false,
				'scale' => 0
		));
		
		$builder->add ('stock', 'integer', array (
		    'required' 		=> false,
		    'scale' 		=> 0,
		    'disabled' 	    => true,
		    'mapped' 	    => false,
		    'data' 			=> $this->stock
		));

		$builder->add('databaixa', 'datetime', array(
				'required' => false,
				'attr'			=>	array('readonly' => false),
				'widget' => 'single_text',
				'input' => 'datetime',
				'placeholder' => false,
				'format' => 'dd/MM/yyyy HH:mm',
		));
		
		$builder->add('departament', 'choice', array(
				'choices'   => BaseController::getDepartamentsConta( 0 ),
				'multiple'  => false,
				'expanded'  => false,
				'placeholder' => '',
				//'data' 		=> $producte->getDepartament(),
		));
		
		$current = $this;
		
		$builder->get('departament')->addEventListener(
	            FormEvents::POST_SUBMIT,
	            function (FormEvent $event) use ($current) {
	                // It's important here to fetch $event->getForm()->getData(), as
	                // $event->getData() will get you the client data (that is, the ID)
	                $departament = $event->getForm()->getData(); // => Dada de l'esdeveniment
					
					$producte = $event->getForm()->getParent()->getData(); // Dades del formulari pare

					$producte->setDepartament($departament);	
	                // since we've added the listener to the child, we'll have to pass on
	                // the parent to the callback functions!
	                //$formModifier($event->getForm()->getParent(), $sport);
					$current->subdepartamentFormModifier($event->getForm()->getParent(), $producte);
	            }
	    );
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityProducte'));
	}
		
	public function getName()
	{
		return 'producte';
	}

}
