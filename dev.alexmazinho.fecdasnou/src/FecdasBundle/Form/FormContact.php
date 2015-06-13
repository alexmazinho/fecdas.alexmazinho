<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormContact extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('name', 'text');
		$builder->add('email', 'email');
		if ($this->options['disable_subject']) $builder->add('subject','text',  array(
				'attr' => (array('readonly'=>"readonly")))); 
		else $builder->add('subject', 'text');
		$builder->add('body', 'textarea');
		
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityContact'));
	}
	
	public function getName()
	{
		return 'contact';
	}
	
}
