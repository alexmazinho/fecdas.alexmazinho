<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityCarnet;


class FormDuplicat extends AbstractType {

	protected $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$currentClub = null;
		if (isset($this->options['club'])) $currentClub = $this->options['club']; 

		$builder->add('carnet', 'entity', array('class' => 'FecdasBundle:EntityCarnet',
				'choice_label' => 'llistaText',
				'multiple' => false,
				'required'  => false,
				'preferred_choices' => array(),
				'placeholder' => ' ... selecciona el carnet ',
		));
		
		$personesSelectOptions = array('class' => 'FecdasBundle:EntityPersona',
				'choice_label' => 'llistaText',
				'multiple' => false,
				'required'  => false,
				'query_builder' => function($repository)  use ($currentClub) {
					return $repository->createQueryBuilder('p')
					->where('p.club = :codiclub')
					->setParameter('codiclub', $currentClub)
					->orderby('p.cognoms');
				},
		);
		
		$builder->add('persona', 'entity', $personesSelectOptions);
		$builder->add('dni', 'text', array('mapped' => false, 'disabled' => true));
		$builder->add('nom', 'text', array('mapped' => false, 'required' => true));
		$builder->add('cognoms', 'text', array('mapped' => false, 'required' => true));
		$builder->add('observacions', 'textarea', array('required' => false));
		
		$builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
			// Abans del submit (del bind de les dades de la request¿? al form). Permet afegir els canvis introduit a PRE_SET_DATA modificar el form. Ajax per exemple			
			$duplicat = $event->getData();
			$form = $event->getForm();
			
			if (is_array($duplicat) && isset($duplicat['titol'])) {
				$form->add('titol', 'entity', array('class' => 'FecdasBundle:EntityTitol',
						'choice_label' => 'llistaText',
						'multiple' => false,
						'required'  => false,
						'placeholder' => ' ... escull un títol ', 
							'query_builder' => function($repository)  use ($duplicat) {
								return $repository->createQueryBuilder('t')
									->where('t.carnet = :carnet AND t.organisme = \''.BaseController::ORGANISME_CMAS.'\'')
									->setParameter('carnet', $duplicat['carnet'])
									->orderby('t.titol');
								},
				));
			}
			
			if (is_array($duplicat) && isset($duplicat['fotoupld'])) {
				$form->add('fotoupld', 'file', array('mapped' => false, 'attr' => array('accept' => 'image/*')));
			}
		});
		
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($currentClub) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple  
			$duplicat = $event->getData();
			$form = $event->getForm();
			if ($duplicat->getCarnet() != null) {  // Formulari creat amb un duplicat que té carnet seleccionat (Ajax)
				if ($duplicat->getCarnet()->getId() != 1) { // Llicències federatives 	
					$form->add('titol', 'entity', array('class' => 'FecdasBundle:EntityTitol',
								'choice_label' => 'llistaText',
								'multiple' => false,
								'required'  => false,
								'placeholder' => ' ... escull un títol ',
								'query_builder' => function($repository)  use ($duplicat) {
									return $repository->createQueryBuilder('t')
										->where('t.carnet = :carnet AND t.organisme = \''.BaseController::ORGANISME_CMAS.'\'')
										->setParameter('carnet', $duplicat->getCarnet())
										->orderby('t.titol');
									},
					));
						
					if ($duplicat->getCarnet()->getFoto() == true) {
						//$form->add('fotoupld', 'file', array('mapped' => false, 'required' => true, 'attr' => array('accept' => 'image/*')));
						$form->add('fotoupld', 'file', array('mapped' => false, 'attr' => array('accept' => 'image/*')));
					}
				}
			}
		});
		
		$builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event)  {
			// Després de posar els valors de la entitat al formulari. Abans a PRE_SET_DATA no es poden posar valors setData()
			$duplicat = $event->getData();
			$form = $event->getForm();

			if ($duplicat->getPersona() != null) { // Formulari creat amb un duplicat que té persona seleccionada (Ajax)
				$form->get('dni')->setData($duplicat->getPersona()->getDni());
				$form->get('nom')->setData($duplicat->getPersona()->getNom());
				$form->get('cognoms')->setData($duplicat->getPersona()->getCognoms());
			}
		});
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityDuplicat', 'csrf_protection'   => false,));
		//$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityDuplicat',));
	}
	
	public function getName()
	{
		return 'duplicat';
	}

}
