<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Entity\EntityClub;
use FecdasBundle\Controller\BaseController;

class FormClub extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$nou = $this->options['nou'];
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($nou) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$club = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($club instanceof EntityClub) {
				
				$form->add('clubs', 'genemu_jqueryselect2_entity', array(
					'class' 	=> 'FecdasBundle:EntityClub',
					'choice_label' => 'nom',
					'label' 	=> 'Filtre per club: ',
					'mapped' 	=> false,
					'required'  => false,
					'data'		=> $club
				));
				
				$form->add('saldoclub', 'number', array(
					'read_only'  => true,
					'grouping' => true,
					'precision' => 2,
					'mapped' => false,
					'data'	=> $club->getSaldo()
				));

				// Cerca federats club
				$form->add('federats', 'entity', array(
					'class' => 'FecdasBundle:EntityPersona',
					'query_builder' => function($repository) use ($club) {
						return $repository->createQueryBuilder('e')
							->where('e.club = :codiclub')
							->andWhere('e.databaixa is null')
							->orderBy('e.cognoms', 'ASC')
							->setParameter('codiclub', $club->getCodi());
						},
					'choice_label' 	=> 'nomCognoms',
					'empty_value' 	=> '',
					'required'  	=> false,
					'mapped'		=> false,
					'property_path' => 'persona',
					'disabled'		=> $nou == true
				));
				
				$form->add('carrec', 'choice', array(
					'required' 		=> false,
					'mapped'		=> false,
					'choices' 		=> BaseController::getCarrecs(),
					'empty_value' 	=> 'Escollir càrrec...',
					'disabled'		=> $nou == true
				));

				$form->add('nommembre', 'text', array(
					'required'  => false,
					'mapped'		=> false,
					'disabled'		=> $nou == true
				));

				$form->add('carrecs', 'hidden');

				$form->add('activat', 'checkbox', array(
					'required'  => false,
					'data'		=> ($club->getActivat()?true:false)
				));
   				
			}
		});
		
		
		if ($this->options['admin'] == true) { 
			if ($this->options['nou'] == true) {
				$builder->add('nouclub', 'hidden', array(
					'mapped' => false,
				));
			}
		
			$builder->add('codi', 'text', array(
				'required'  => true,
				'read_only' => true
			));
			
			$tipuscluboptions = array('class' => 'FecdasBundle:EntityClubType', 'choice_label' => 'tipus',
					'query_builder' => function($repository) {
					return $repository->createQueryBuilder('t')->orderBy('t.id', 'ASC');
				},
			);
				
			$builder->add('tipus', 'entity', $tipuscluboptions);
			
			$builder->add('estat', 'entity', array('class' => 'FecdasBundle:EntityClubEstat', 'choice_label' => 'descripcio',
					'query_builder' => function($repository) {
						return $repository->createQueryBuilder('e')->orderBy('e.codi', 'ASC');
					})
			);
				
			$builder->add('impressio', 'checkbox', array(
					'required'  => false,
			));
			
			$builder->add('limitcredit', 'number', array(
					'grouping' => true,
					'precision' => 2
			));
			
			$builder->add('limitnotificacio', 'datetime', array(
					'read_only' => true,
					'widget' => 'single_text',
					'input' => 'datetime',
					'empty_value' => false,
					'format' => 'dd/MM/yyyy',
			));
			/*
			$builder->add('limitnotificacio', 'text', array(
					'read_only'  => true,
			));*/
			
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

		$builder->add('addrcomarca', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrcp', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrprovincia', 'choice', array(
				'choices' => array('BARCELONA' => 'BARCELONA','GIRONA' => 'GIRONA',
						'TARRAGONA' => 'TARRAGONA','LLEIDA' => 'LLEIDA' ),
				//'data' => 'BARCELONA',
				'required'  => false,
		));
		
		$builder->add('addradrecacorreu', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrpobcorreu', 'text', array(
				'required'  => false,
		));

		$builder->add('addrcomarcacorreu', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrcpcorreu', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrprovinciacorreu', 'choice', array(
				'choices' => array('BARCELONA' => 'BARCELONA','GIRONA' => 'GIRONA',
						'TARRAGONA' => 'TARRAGONA','LLEIDA' => 'LLEIDA' ),
				//'data' => 'BARCELONA',
				'required'  => false,
		));
		
		//$builder->add('tipusparte');
		$builder->add('tipusparte', 'entity', array('class' => 'FecdasBundle:EntityParteType', 'choice_label' => 'descripcio', 'multiple' => true, 'required' => false,
				'query_builder' => function($repository) {
					return $repository->createQueryBuilder('e')->where('e.actiu = true')->orderBy('e.id', 'ASC');
				})
		);
		/*$builder->add('tipusparte', 'EntityParteType', array('data_class' => 'FecdasBundleEntityParteType', 'choice_label' => 'descripcio', 'multiple' => true,
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
		
		
		/*$builder->add('forceupdate', 'checkbox', array(
    	    	'required'  => false,
				'mapped' => false,
				'data' => true,
		));*/
		
		$builder->add('role', 'choice', array(
				'choices' => array('user'=> 'user'),
				'data' => 'user',
				'mapped' => false,
		));
		
		$builder->add('compte', 'text', array(
				'required'  => true,
		));
		
		$builder->add('romanent', 'number', array(
				'read_only'  => true,
				'grouping' => true,
				'precision' => 2
		));
			
		$builder->add('totalpagaments', 'number', array(
				'read_only'  => true,
				'grouping' => true,
				'precision' => 2
		));
		
		$builder->add('totalllicencies', 'number', array(
				'read_only'  => true,
				'grouping' => true,
				'precision' => 2
		));
		
		$builder->add('totalduplicats', 'number', array(
				'read_only'  => true,
				'grouping' => true,
				'precision' => 2
		));
		
		$builder->add('totalaltres', 'number', array(
				'read_only'  => true,
				'grouping' => true,
				'precision' => 2
		));
		
		$builder->add('ajustsubvencions', 'number', array(
				'read_only'  => true,
				'grouping' => true,
				'precision' => 2
		));
		
		// Junta
		$builder->add('dataalta', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> '',
				'format' 		=> 'dd/MM/yyyy'
		));

		$builder->add('databaixa', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> '',
				'format' 		=> 'dd/MM/yyyy'
		));

		$builder->add('datacreacio', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> '',
				'format' 		=> 'dd/MM/yyyy'
		));

		$builder->add('datajunta', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> '',
				'format' 		=> 'dd/MM/yyyy'
		));
		
		$builder->add('estatuts', 'checkbox', array(
				'required'  => false,
		));
		
		$builder->add('registre', 'text', array(
				'required'  => false,
		));
		
		
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityClub'));
	}
	
	public function getName()
	{
		return 'club';
	}

}
