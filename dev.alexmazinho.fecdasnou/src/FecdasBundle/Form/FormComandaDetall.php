<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class FormComandaDetall extends AbstractType {

	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{

		$builder->add('id', 'hidden');
		
		$builder->add('producte', 'hidden'); // Select2
		
		$builder->add ( 'unitats', 'integer', array (
				'required' => true,
				'precision' => 0
		));
		
		$builder->add ( 'descomptedetall', 'number', array (
				'required' 	=> true,
				/*'disabled' 	=> true,*/
				'mapped' 	=> false,
				'precision' => 2,
				'data'		=> 0
		));
		
		$builder->add ( 'preuunitat', 'number', array (
				'required' 	=> true,
				'disabled' 	=> true,
				'mapped' 	=> false,
				'precision' => 2,
				'data'		=> 0
		));
		
		$builder->add ( 'ivaproducte', 'number', array (
				'required' => true,
				'disabled' 	=> true,
				'mapped' 	=> false,
				'precision' => 2,
				'data'		=> 0
		));
		
		$builder->add ( 'totalnet', 'number', array (
				'required' => true,
				'disabled' 	=> true,
				'mapped' 	=> false,
				'precision' => 2,
				'data'		=> 0
		));
		
		$builder->add ( 'totaliva', 'number', array (
				'required' => true,
				'disabled' 	=> true,
				'mapped' 	=> false,
				'precision' => 2,
				'data'		=> 0
		));
		
		$builder->add ( 'total', 'number', array (
				'required' => true,
				'disabled' 	=> true,
				'mapped' 	=> false,
				'precision' => 2,
				'data'		=> 0
		));
		
		$builder->add('anotacions', 'textarea', array(
				'required' 	=> false,
				'attr'		=> array( 'rows' => 1, 'resize' => 'vertical' )	
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
