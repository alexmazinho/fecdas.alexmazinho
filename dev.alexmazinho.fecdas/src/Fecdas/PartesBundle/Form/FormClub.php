<?php
namespace Fecdas\PartesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormClub extends AbstractType {

	protected $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		
		if ($this->options['admin'] == true) {
			if ($this->options['nou'] == true) {
				$builder->add('nouclub', 'hidden', array(
					'mapped' => false,
				));
			}
			
			$builder->add('codi', 'text', array(
					'required'  => true,
					'read_only' => !$this->options['nou']
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
		
			$builder->add('estat', 'entity', array('class' => 'FecdasPartesBundle:EntityClubEstat', 'property' => 'descripcio',
					'query_builder' => function($repository) {
						return $repository->createQueryBuilder('e')->orderBy('e.codi', 'ASC');
					})
			);
				
			$builder->add('impressio', 'checkbox', array(
					'required'  => false,
			));
			
			$builder->add('limitcredit', 'money', array(
					'grouping' => true,
			));
			
			$builder->add('limitnotificacio', 'text', array(
					'read_only'  => true,
			));

		}
		
		$builder->add('nom', 'text');
		
		$builder->add('telefon', 'text', array(
				'required'  => false,
		));
		
		$builder->add('fax', 'text', array(
				'required'  => false,
		));
		
		$builder->add('mobil', 'text', array(
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
		
		//$builder->add('tipusparte');
		$builder->add('tipusparte', 'entity', array('class' => 'FecdasPartesBundle:EntityParteType', 'property' => 'descripcio', 'multiple' => true, 'required' => false,
				'query_builder' => function($repository) {
					return $repository->createQueryBuilder('e')->where('e.actiu = true')->orderBy('e.id', 'ASC');
				})
		);
		/*$builder->add('tipusparte', 'EntityParteType', array('data_class' => 'FecdasPartesBundleEntityParteType', 'property' => 'descripcio', 'multiple' => true,
				'query_builder' => function($repository) {
					return $repository->createQueryBuilder('e')->where('e.actiu = true')->orderBy('e.id', 'ASC');
				})
		);*/
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
				'mapped' => false,
		));
		
		$builder->add('pwd', 'repeated', array(
    			'type' => 'password',
    			'required' => false,
    			'first_name'  => 'first',
    			'second_name' => 'second',
				'mapped' => false,
				'options' => array('always_empty' => true, 'required' => false),
		));
		
		$builder->add('randompwd', 'text', array(
				'required' => false,
				'mapped' => false,
		));
		
		
		$builder->add('forceupdate', 'checkbox', array(
    	    	'required'  => false,
				'mapped' => false,
				'data' => true,
		));
		
		$builder->add('role', 'choice', array(
				'choices' => array('user'=> 'user'),
				'data' => 'user',
				'mapped' => false,
		));
		
		
		$builder->add('romanent', 'money', array(
				'read_only'  => true,
				'grouping' => true,
		));
			
		$builder->add('totalpagaments', 'money', array(
				'read_only'  => true,
				'grouping' => true,
		));
		
		$builder->add('totalllicencies', 'money', array(
				'read_only'  => true,
				'grouping' => true,
		));
		
		$builder->add('totalllicenciesweb', 'money', array(
				'read_only'  => true,
				'grouping' => true,
				'mapped' => false,
		));
			
		$builder->add('totalkits', 'money', array(
				'read_only'  => true,
				'grouping' => true,
		));
		
		$builder->add('totalaltres', 'money', array(
				'read_only'  => true,
				'grouping' => true,
		));
		
		$builder->add('ajustsubvencions', 'money', array(
				'read_only'  => true,
				'grouping' => true,
		));
		$builder->add('saldoclub', 'money', array(
				'read_only'  => true,
				'grouping' => true,
				'mapped' => false,
		));
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'Fecdas\PartesBundle\Entity\EntityClub'));
	}
	
	public function getName()
	{
		return 'club';
	}

}
