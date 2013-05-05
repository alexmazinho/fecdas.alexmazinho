<?php
namespace Fecdas\PartesBundle\Form\Enquestes;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class FormEnquesta extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder->add('id', 'hidden');
		
		$builder->add('descripcio', 'textarea');
		
		$current_year = date("Y", strtotime('now'));
		
		$builder->add('datainici', 'genemu_jquerydate',
				array('format' => 'dd/MM/yyyy', 'years' => range($current_year, $current_year+1)));

		$builder->add('datafinal', 'genemu_jquerydate',
				array('format' => 'dd/MM/yyyy', 'years' => range($current_year, $current_year+1), 'required'  => false));
	}
	
	public function getDefaultOptions(array $options)
	{
		return array(
				'data_class' => 'Fecdas\PartesBundle\Entity\Enquestes\EntityEnquesta',
		);
	}
	
	public function getName()
	{
		return 'enquesta';
	}

}
