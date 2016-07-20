<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use FecdasBundle\Entity\EntityClub;

class FormClubAdmin extends FormClub {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);
		
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
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

				$form->add('activat', 'checkbox', array(
					'required'  => false,
					'data'		=> ($club->getActivat()?true:false)
				));

				$form->add('nouclub', 'hidden', array(
					'mapped' => false,
				));
			}
		});
		
		$builder->add('nom', 'text', array( 'read_only' => false ));
		$builder->add('mail', 'email', array('read_only' => false));
		$builder->add('cif', 'text', array('read_only' => false));
		$builder->add('databaixa', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> '',
				'format' 		=> 'dd/MM/yyyy',
				'read_only' 	=> false
		));
		
		
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
		
		$builder->add('tipusparte', 'entity', array('class' => 'FecdasBundle:EntityParteType', 'choice_label' => 'descripcio', 'multiple' => true, 'required' => false,
				'query_builder' => function($repository) {
					return $repository->createQueryBuilder('e')->where('e.actiu = true')->orderBy('e.id', 'ASC');
				})
		);
		
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
		
		$builder->add('role', 'choice', array(
				'choices' => array('user'=> 'user'),
				'data' => 'user',
				'mapped' => false,
		));
		
		
		// Saldos. Comptabilitat
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
		
	}
	
	
}
