<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use FecdasBundle\Entity\EntityFactura;

class FormFactura extends AbstractType {

	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$factura = $event->getData();
		
			error_log("1".get_class($factura));
			
			/* Check we're looking at the right data/form */
			if ($factura instanceof EntityFactura) {
				
				error_log("2");
				
				$form->add('comandatext', 'text', array(
						/*'class' 		=> 'FecdasBundle:EntityComanda',
						'choice_label' 	=> 'InfoComanda',
						'required'  	=> false,*/
						'disabled' 		=> true,
						'mapped'		=> false,
						'data'			=> ($factura->getComanda()!=null?$factura->getComanda()->getConcepteComanda():'')
				));
				
				$form->add('numfactura', 'text', array(
						'required' 	=> true,
						'disabled' 	=> true,
						'mapped' 	=> false,
						'data'		=> $factura->getNumFactura()
				));
				
				$form->add('club', 'text', array(
						'required' 	=> true,
						'disabled' 	=> true,
						'mapped' 	=> false,
						'data'		=> ($factura->getComanda()!=null && $factura->getComanda()->getClub() != null?$factura->getComanda()->getClub()->getNom():'')
				));
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('num', 'hidden');
		
		$builder->add('datafactura', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
		));
		
		$builder->add('datapagament', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
		));
		
		$builder->add ( 'import', 'number', array (
				'required' 		=> true,
				'scale' 		=> 2,
		));
		
		$builder->add('concepte', 'textarea', array(
				'required' => true,
		));
		
		$builder->add('dataanulacio', 'date', array(
				'disabled' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
		));
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityFactura'));
	}
		
	public function getName()
	{
		return 'factura';
	}

}
