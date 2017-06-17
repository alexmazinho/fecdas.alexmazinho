<?php
namespace FecdasBundle\Form\Enquestes;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormEnquesta extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('id', 'hidden');
		
		$builder->add('descripcio', 'textarea');
		
		$current_year = date("Y", strtotime('now'));
		
		$builder->add('datainici', 'datetime', array(
				'widget' 	=> 'single_text',
				'input' 	=> 'datetime',
				'format' 	=> 'dd/MM/yyyy'
				
		));

		$builder->add('datafinal', 'datetime', array(
				'widget' 	=> 'single_text',
				'input' 	=> 'datetime',
				'format' 	=> 'dd/MM/yyyy'
				
		));
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\Enquestes\EntityEnquesta'));
	}
	
	public function getName()
	{
		return 'enquesta';
	}

}
