<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormUser extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
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
    	    'required'  => false
		));
		
		$builder->add('recoverytoken', 'hidden');
		$builder->add('usertoken', 'hidden', array('mapped' => false));
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'Fecdas\PartesBundle\Entity\EntityUser'));
	}
	
	public function getName()
	{
		return 'user';
	}

}
