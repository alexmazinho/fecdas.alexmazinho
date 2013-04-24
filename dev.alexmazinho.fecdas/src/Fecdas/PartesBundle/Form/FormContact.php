<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class FormContact extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder->add('name', 'text');
		$builder->add('email', 'email');
		if ($this->options['disable_subject']) $builder->add('subject','text',  array(
				'attr' => (array('readonly'=>"readonly")))); 
		else $builder->add('subject', 'text');
		$builder->add('body', 'textarea');
		
	}
	
	public function getName()
	{
		return 'contact';
	}
	
}
