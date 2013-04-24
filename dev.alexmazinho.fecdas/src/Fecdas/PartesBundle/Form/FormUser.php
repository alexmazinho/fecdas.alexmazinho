<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class FormUser extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder->add('user', 'email',array(
    	    'read_only'  => true,
		));
		
		$builder->add('pwd', 'repeated', array(
    		'type' => 'password',
    		'required' => true,
    		'first_name'  => 'first',
    		'second_name' => 'second',
		));
		
		$builder->add('forceupdate', 'checkbox', array(
    	    'required'  => false,
		));
		
		$builder->add('recoverytoken', 'hidden');
		$builder->add('usertoken', 'hidden', array('property_path' => false));
	}
	
	public function getDefaultOptions(array $options)
	{
		return array(
				'data_class' => 'Fecdas\PartesBundle\Entity\EntityUser',
		);
	}
	
	public function getName()
	{
		return 'user';
	}

}
