<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class FormClub extends AbstractType {

	protected $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilder $builder, array $options)
	{
		
		if ($this->options['admin'] == true) {
			$builder->add('clubs', 'choice', array('choices' => $this->options['clubs'],
					'data' => $this->options['codiclub'],
					'property_path'  => false,
					));
			
			$builder->add('codishow', 'text', array(
				'property_path'  => false,
			));
			
			$tipuscluboptions = array('class' => 'FecdasPartesBundle:EntityClubType', 'property' => 'tipus',
					'query_builder' => function($repository) {
					return $repository->createQueryBuilder('t')->orderBy('t.id', 'ASC');
				},
			);
				
			$builder->add('tipus', 'entity', $tipuscluboptions);
			
			$builder->add('activat', 'checkbox', array(
					'required'  => false,
			));
		}

		$builder->add('codi', 'hidden');
		
		$builder->add('nom', 'text');
		
		$builder->add('telefon', 'text', array(
				'required'  => false,
		));
		
		$builder->add('mail', 'email');

		$builder->add('web', 'text', array(
				'required'  => false,
		));
		
		$builder->add('cif', 'text');
				
		$builder->add('addradreca', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrpob', 'text', array(
				'required'  => false,
		));

		$builder->add('addrcp', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrprovincia', 'choice', array(
				'choices' => array('BARCELONA' => 'BARCELONA','GIRONA' => 'GIRONA',
						'TARRAGONA' => 'TARRAGONA','LLEIDA' => 'LLEIDA' ),
				'data' => 'BARCELONA',
				'required'  => false,
		));
		
		$builder->add('decathlon', 'checkbox', array(
				'required'  => false,
		));
	}
	
	public function getName()
	{
		return 'club';
	}

}
