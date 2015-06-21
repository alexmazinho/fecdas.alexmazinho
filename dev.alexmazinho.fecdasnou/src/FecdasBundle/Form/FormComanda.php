<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Entity\EntityComanda;
use FecdasBundle\Controller\BaseController;

class FormComanda extends AbstractType {

	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$comanda = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($comanda instanceof EntityComanda) {
				
				$form->add('numcomanda', 'text', array(
						'required' 	=> true,
						'disabled' 	=> true,
						'mapped'	=> false,
						'data'		=> $comanda->getNumComanda()
				));
				
				$form->add('numfactura', 'text', array(
						'required' 	=> false,
						'mapped'	=> false,
						'disabled' 	=> true,
						'data'		=> $comanda->getNumFactura()
				));
				
				$form->add('numrebut', 'text', array(
						'required' 	=> false,
						'mapped'	=> false,
						'disabled' 	=> true,
						'data'		=> $comanda->getNumRebut()
				));
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('num', 'hidden');
		
		$builder->add('club', 'entity', array(
				'class' 		=> 'FecdasBundle:EntityClub',
				'property' 		=> 'nom',
				'empty_value' 	=> 'Seleccionar Club',
				'required'  	=> false,
				'read_only' 	=> true,
		));
		
		$builder->add('comptabilitat', 'entity', array(
				'class' 		=> 'FecdasBundle:EntityComptabilitat',
				'property' 		=> 'InfoComptabilitat',
				'empty_value' 	=> 'Pendent d\'enviar a comptabilitat',
				'required'  	=> false,
				'disabled' 		=> true,
		));
		
		$builder->add('comentaris', 'textarea', array(
				'required' 	=> false,
				'attr' 		=> array('rows' => '2')
		));
		
		
		$builder->add ( 'total', 'number', array (
				'required' 		=> true,
				'precision' 	=> 2
		));
		
		$builder->add('databaixa', 'datetime', array(
				'required' 		=> false,
				'read_only' 	=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy HH:mm',
		));
		
		$builder->add('detalls', 'collection', array(
				'type' 			=> new FormComandaDetall(),
				'allow_add'    	=> true,
				'allow_delete' 	=> true,
				'by_reference' 	=> false,
		));
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityComanda'));
	}
		
	public function getName()
	{
		return 'comanda';
	}

}
