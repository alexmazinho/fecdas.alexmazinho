<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
		$builder->add('telephone', 'text',  array(
		        'mapped'      => false,
		        'required'    => false  
		));   // Fake antispam. CSS hidden field
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityContact'));
	}
	
	public function getName()
	{
		return 'contact';
	}
	
}
