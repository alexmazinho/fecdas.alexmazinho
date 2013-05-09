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
				'property_path' => false,
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
		
		$builder->add('addradrecacorreu', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrpobcorreu', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrcpcorreu', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrprovinciacorreu', 'choice', array(
				'choices' => array('BARCELONA' => 'BARCELONA','GIRONA' => 'GIRONA',
						'TARRAGONA' => 'TARRAGONA','LLEIDA' => 'LLEIDA' ),
				'data' => 'BARCELONA',
				'required'  => false,
		));
		
		$builder->add('tipusparte');
		/*
		 * INSERT INTO m_clubs_tipusparte 
		   SELECT p.codi, t.id FROM m_clubs p, m_tipusparte t WHERE t.id <> 8 AND t.id <> 9  -- Clubs normals
		 * 
		 * INSERT INTO m_clubs_tipusparte 
		   SELECT p.codi, t.id FROM m_clubs p, m_tipusparte t WHERE t.id = 8 AND p.codi IN ('CAT514', 'CAT517', 'CAT520', 'CAT528', 'CAT529')   -- Decathlons
		 * 
		 * */ 
		
		/* Camps nou usuari */
		$builder->add('user', 'email', array(
				'required' => false,
				'property_path' => false,
		));
		
		$builder->add('pwd', 'repeated', array(
    			'type' => 'password',
    			'required' => false,
    			'first_name'  => 'first',
    			'second_name' => 'second',
				'property_path' => false,
				'options' => array('always_empty' => true, 'required' => false),
		));
		
		$builder->add('randompwd', 'text', array(
				'required' => false,
				'property_path' => false,
		));
		
		
		$builder->add('forceupdate', 'checkbox', array(
    	    	'required'  => false,
				'property_path' => false,
				'data' => true,
		));
		
		$builder->add('role', 'choice', array(
				'choices' => array('user'=> 'user'),
				'data' => 'user',
				'property_path' => false,
		));
	}
	
	public function getName()
	{
		return 'club';
	}

}
