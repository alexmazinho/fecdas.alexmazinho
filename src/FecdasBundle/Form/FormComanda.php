<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Entity\EntityComanda;
use FecdasBundle\Controller\BaseController;

class FormComanda extends AbstractType {

	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$comanda = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($comanda instanceof EntityComanda) {
				
				$form->add('num', 'text', array(
						'required' 	=> true,
						'disabled' 	=> true,
						'mapped'	=> false,
						'data'		=> $comanda->getNumComanda()
				));

				$form->add('club', 'entity', array(
						'class' 		=> 'FecdasBundle:EntityClub',
						'query_builder' => function($repository) {
								return $repository->createQueryBuilder('c')
									->orderBy('c.nom', 'ASC')
									->where('c.databaixa IS NULL');
									//->join('c.tipus', 't', 'WITH', 't.id != \''.BaseController::TIPUS_CLUB_PERSONA_FISICA.'\'');
								}, 
						'choice_label' 	=> 'nom',
						'placeholder' 	=> 'Seleccionar Club',
						'required'  	=> false,
						'disabled' 		=> !$comanda->esNova(),
				));
				
				if ($comanda->getRebut() == null) { // Comanda sense rebut
					$form->add('datapagament', 'datetime', array(
							'required' 		=> false,
							'mapped'		=> false,
							'widget' 		=> 'single_text',
							'input' 		=> 'datetime',
							'placeholder' 	=> false,
							'format' 		=> 'dd/MM/yyyy',
					));
					$form->add('tipuspagament', 'choice', array(
							'required' 		=> false,
							'mapped'		=> false,
							'choices' 		=> BaseController::getTipusDePagament(),
							'placeholder' 	=> ''
					));
				} else {
					$form->add('datapagament', 'hidden', array(
							'required' 		=> false,
							'mapped'	=> false,
					));
					$form->add('tipuspagament', 'hidden', array(
							'required' 		=> false,
							'mapped'	=> false,
					));
				}
					
					
				$form->add('detalls', 'collection', array(
						'type' 			=> new FormComandaDetall(),
						'allow_add'    	=> true,
						//'allow_delete' 	=> false,
						//'by_reference' 	=> false,
						//'data'			=> $comanda->getDetalls()
				));
				
				$form->add ( 'totalsuma', 'number', array (
						'required' 	=> false,
						'disabled' 	=> true,
						'mapped' 	=> false,
						'scale' 	=> 2,
						'data'		=> $comanda->getTotalDetalls()
				));
				
			}
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('comentaris', 'textarea', array(
				'required' 	=> false,
				'attr' 		=> array('rows' => '1')
		));
		
		$builder->add('databaixa', 'datetime', array(
				'required' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
				'attr'			=>	array('readonly' => false)						
		));
		
		
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityComanda'));
	}
		
	public function getName()
	{
		return 'comanda';
	}

}
