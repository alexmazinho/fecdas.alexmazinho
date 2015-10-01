<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityProducte;


class FormProducte extends AbstractType  implements EventSubscriberInterface {

	public static function getSubscribedEvents() {

		// Tells the dispatcher that you want to listen on the form.pre_set_data
		// event and that the preSetData method should be called.
		return array (
				
				FormEvents::POST_SUBMIT => array('postSubmitData', 900),  // Desactiva validació
				FormEvents::SUBMIT => array('submitData', 900),
				FormEvents::PRE_SET_DATA => 'preSetData'
		);
	}
	
	private $anypreu;
	
	public function __construct($anypreu = 0)
	{
		if ($anypreu == 0) $anypreu = date('Y');
		
		$this->anypreu = $anypreu;
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
			
			
			$form->add ('limitnotifica', 'integer', array (
					'required' 		=> false,
					'scale' 		=> 0,
					'disabled' 		=> $producte->getStockable() != true
			));
			
			$form->add ('stock', 'integer', array (
					'required' 		=> false,
					'scale' 		=> 0,
					'disabled' 		=> $producte->getStockable() != true
			));
			
			$form->add ('canvitarifa', 'integer', array (
					'required' 		=> false,
					'scale' 		=> 0,
					'disabled' 		=> $producte->getTransport() != true
			));
			error_log("stocable => ".$producte->getStockable()."-");
			$form->add('stockable', 'checkbox', array(
					'required' 		=> false,
					'data' 			=> $producte->getStockable() == true
			));
			error_log("transport => ".$producte->getTransport()."-");
			$form->add('transport', 'checkbox', array(
					'required' 		=> false,
					'data' 			=> $producte->getTransport() == true
			));
		
			
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
	public function postSubmitData(FormEvent $event) {
	
		//$event->stopPropagation();
	}
	
	public function submitData(FormEvent $event) {
		// It's important here to fetch $event->getForm()->getData(), as
		// $event->getData() will get you the client data (that is, the ID)
		$producte = $event->getForm()->getData();
		$form = $event->getForm ();
	
		$origen = $form->get('anypreus')->getData(); // Detectar origen, si és selector anys refrescar els valors del preu, iva...
	
	}
	
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventSubscriber ( new FormProducte () );
		
		$builder->add('id', 'hidden');
		
		
		$builder->add('codi', 'text', array(
				'required' => true,
		));
		
		$builder->add('abreviatura', 'text', array(
				'required' => true,
				'attr' => array('maxlength' => '3'),
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
		
		$builder->add('databaixa', 'datetime', array(
				'required' => false,
				'read_only' => false,
				'widget' => 'single_text',
				'input' => 'datetime',
				'empty_value' => false,
				'format' => 'dd/MM/yyyy HH:mm',
		));
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
