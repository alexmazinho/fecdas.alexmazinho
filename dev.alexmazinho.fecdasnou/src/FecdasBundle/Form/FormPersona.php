<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormPersona extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('id', 'hidden');
		
		$readonly = ! $this->options['edit'];
		
		$builder->add('nom', 'text');
		$builder->add('cognoms', 'text');
		$builder->add('dni', 'text', array('read_only' => $readonly,));
		
		$builder->add('datanaixement', 'genemu_jquerydate',
				array('format' => 'dd/MM/yyyy', 'years' => range(1900, date("Y", strtotime('now')))));
		
		$builder->add('sexe', 'choice', array(
				'choices'   => array('H' => 'Home', 'M' => 'Dona'),
				'multiple'  => false,
				'expanded'  => true,
		));

		$builder->add('telefon1', 'text', array(
    	    'required'  => false,
			));
		
		$builder->add('telefon2', 'text', array(
    	    'required'  => false,
			));
		
		$builder->add('mail', 'email', array(
    	    'required'  => false,
			));
		
		$builder->add('addradreca', 'text', array(
    	    'required'  => false,
			));
		
		$builder->add('addrpob', 'search', array(
    	    'required'  => false,
			));
		/*$builder->add('addrpob', 'genemu_jqueryautocompleter', array(
				'route_name' => 'FecdasBundle_ajaxpoblacions',
		));*/
		$builder->add('addrcp', 'text', array(
    	    'required'  => false,
			));
		/*$builder->add('addrcp', 'genemu_jqueryautocompleter', array(
				'route_name' => 'FecdasBundle_ajaxcodispostals',
		));*/
		
		$builder->add('addrprovincia', 'choice', array(
				'choices' => $this->options['provincies'],
				'preferred_choices' => array('Barcelona','Girona','Tarragona','Lleida' ),
				'empty_value' => '',
				'required'  => false,
		));
		
		$builder->add('addrcomarca', 'choice', array(
				'choices' => $this->options['comarques'],
				'empty_value' => '',
				'required'  => false,
		));
		
		$builder->add('addrnacionalitat', 'choice', array(
				'choices' => $this->options['nacions'],
				'preferred_choices' => array('ESP' ),
				'required'  => false,
		));
		
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityPersona'));
	}
		
	public function getName()
	{
		return 'persona';
	}

}