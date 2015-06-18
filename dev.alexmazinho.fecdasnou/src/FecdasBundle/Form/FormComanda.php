<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormComanda extends AbstractType {

	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$comanda = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($comanda instanceof EntityComanda) {
				$form->add ( 'totaldetalls', 'number', array (
						'required' 	=> true,
						'read_only' => true,
						'mapped' 	=> false,
						'data'		=> $comanda->getTotalDetalls(),
						'precision' => 2
				));
				
				$form->add('numcomanda', 'text', array(
						'required' => true,
						'read_only' => true,
						'mapped'	=> false,
						'data'		=> $comanda->getNumComanda()
				));
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('num', 'hidden');
		
		$builder->add('comptabilitat', 'entity', array(
				'class' => 'FecdasBundle:EntityComptabilitat',
				'property' => 'InfoComptabilitat',
				'empty_value' => 'Pendent d\'enviar a comptabilitat',
				'required'  => false,
				'read_only' => true,
		));
		
		$builder->add('comentaris', 'textarea', array(
				'required' => false,
		));
		
		
		$builder->add ( 'total', 'number', array (
				'required' => true,
				'precision' => 2
		));
		
		$builder->add ( 'descomptetotal', 'percent', array (
				'required' => true,
				'precision' => 2,
				'type'		=> 'fractional',
				'constraints' => array (
						new Type ( array ('type' => 'numeric', 'message' => 'El descompte ha de ser numèric.')),
						new GreaterThanOrEqual ( array ('value' => 0, 'message' => 'El descompte no pot ser negatiu.')),
						new LessThanOrEqual ( array ('value' => 100, 'message' => 'El descompte no és vàlid.'))
				)
		));
		
		$builder->add('databaixa', 'date', array(
				'read_only' => true,
				'widget' => 'single_text',
				'input' => 'date',
				'empty_value' => false,
				'format' => 'dd/MM/yyyy',
		));
		
		$builder->add('factura', 'entity', array(
				'class' => 'FecdasBundle:EntityFactura',
				'property' => 'numfactura',
				'empty_value' => '',
				'required'  => false,
				'read_only' => true,
		));
		
		$builder->add('rebut', 'entity', array(
				'class' => 'FecdasBundle:EntityRebut',
				'property' => 'numrebut',
				'empty_value' => '',
				'required'  => false,
				'read_only' => true,
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
