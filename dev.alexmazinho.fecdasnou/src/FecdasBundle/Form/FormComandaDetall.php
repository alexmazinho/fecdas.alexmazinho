<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormComandaDetall extends AbstractType {

	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$detall = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($detall instanceof EntityComandaDetall) {
				
				$form->add ( 'preuunitat', 'number', array (
						'required' => true,
						'read_only' => true,
						'mapped' 	=> false,
						'precision' => 2
				));

				$form->add ( 'totalnet', 'number', array (
						'required' => true,
						'read_only' => true,
						'mapped' 	=> false,
						'precision' => 2
				));

				$form->add ( 'totaliva', 'number', array (
						'required' => true,
						'read_only' => true,
						'mapped' 	=> false,
						'precision' => 2
				));

				$form->add ( 'total', 'number', array (
						'required' => true,
						'read_only' => true,
						'mapped' 	=> false,
						'precision' => 2
				));
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('comanda', 'hidden');
		
		$builder->add('producte', 'hidden'); // Select2
		
		$builder->add ( 'unitats', 'integer', array (
				'required' => true,
				'precision' => 0,
				'constraints' => array (
						new NotBlank ( array ( 'message' => 'Cal indicar les unitats del producte.' )),
						new Type ( array ('type' => 'numeric', 'message' => 'Les unitats del producte ha de ser numèric.')),
						new GreaterThanOrEqual ( array ('value' => 0, 'message' => 'Les unitats del producte no són vàlides.'))
				)
		));
		
		$builder->add ( 'descomptedetall', 'percent', array (
				'required' => true,
				'precision' => 2,
				'type'		=> 'fractional',
				'constraints' => array (
						new Type ( array ('type' => 'numeric', 'message' => 'El descompte ha de ser numèric.')),
						new GreaterThanOrEqual ( array ('value' => 0, 'message' => 'El descompte no pot ser negatiu.')),
						new LessThanOrEqual ( array ('value' => 100, 'message' => 'El descompte no és vàlid.'))
				)
		));
		
		$builder->add('anotacions', 'textarea', array(
				'required' => false,
		));
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityComandaDetall'));
	}
		
	public function getName()
	{
		return 'detallcomanda';
	}

}
