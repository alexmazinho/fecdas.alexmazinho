<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityMetaPersona;
use FecdasBundle\Entity\EntityCurs;
use FecdasBundle\Form\FormDocencia;
use FecdasBundle\Entity\EntityTitulacio;


class FormCurs extends AbstractType  implements EventSubscriberInterface {

	public static function getSubscribedEvents() {

		// Tells the dispatcher that you want to listen on the form.pre_set_data
		// event and that the preSetData method should be called.
		return array (
				
				//FormEvents::POST_SUBMIT => array('postSubmitData', 900),  // Desactiva validació
				//FormEvents::SUBMIT => array('submitData', 900),
				FormEvents::PRE_SET_DATA => 'preSetData'
		);
	}
	
	// Mètode del subscriptor => implements EventSubscriberInterface
	public function preSetData(FormEvent $event) {
		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
		$form = $event->getForm();
		$curs = $event->getData();
	
		/* Check we're looking at the right data/form */
		if ($curs instanceof EntityCurs) {
			
			$editable = $curs->editable();
			
			$form->add('num', 'text', array(
				'required' 	=> true,
				'attr'		=>	array('readonly' => !$curs->finalitzat())
			));
			
			if ($curs->historic() && !$curs->esNou()) {
				$form->add('club', 'text', array(
					'data'  	=> $curs->getClubhistoric(),
					'mapped'	=> false,
					'attr'		=>	array('readonly' => true)	// clubs històric
				));
			} else {
				$form->add('club', 'entity', array(
							'class' 		=> 'FecdasBundle:EntityClub',
							'query_builder' => function($repository) {
									return $repository->createQueryBuilder('c')
										->where('c.databaixa IS NULL')
										->orderBy('c.nom', 'ASC');
									}, 
							'choice_label' 	=> 'nom',
							//'placeholder' 	=> '',
							'required'  	=> true,
							'disabled'		=> true  // Current club. No es pot editar	
				));	
			}	
			
			$form->add('titol', 'entity', array(
						'class' 		=> 'FecdasBundle:EntityTitol',
						'query_builder' => function($repository) {
								return $repository->createQueryBuilder('t')
									->where('t.organisme = \''.BaseController::ORGANISME_CMAS.'\'' )
									->andWhere('t.actiu = 1')
									->orderBy('t.titol', 'ASC');
								}, 
						'choice_label' 	=> 'llistaText',
						'placeholder' 	=> 'Indicar titulació',
						'required'  	=> true,
						'disabled'		=> !$editable  		// No es pot canviar el títol
			));	
				
				
			$form->add('datadesde', 'datetime', array(
				'required' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
				'attr'			=>	array('readonly' => !$editable)
			));
			
			$form->add('datafins', 'datetime', array(
				'required' 		=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
				'attr'			=>	array('readonly' => !$editable)
			));
			
			$director = $curs->getDirector();
			$codirector = $curs->getCodirector();
			$instructors = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_INSTRUCTOR);
			$collaboradors = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLABORADOR);
			
			$form->add('auxdirector', 'text', array(
				'mapped'	=> false,
				'data'  	=> $director != null?$director->getId():'',
				'attr'		=>	array('readonly' => !$editable)
			));	

			$form->add('auxcarnet', 'text', array(
				'mapped'	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			$form->add('auxcodirector', 'text', array(
				'mapped'	=> false,
				'required' 	=> false,
				'data'  	=> $codirector != null?$codirector->getId():'',
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			$form->add('auxcocarnet', 'text', array(
				'mapped'	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));
			
			$form->add('auxinstructor', 'text', array(		/* Per la cerca. Sense valor */
				'mapped'	=> false,
				'required' 	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));	
				
			$form->add('auxcollaborador', 'text', array(		/* Per la cerca. Sense valor */
				'mapped'	=> false,
				'required' 	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			$form->add('instructors', 'collection', array(
				'mapped' 	   	=> false,
				'data'		 	=> $instructors,
		        'type' 		   	=> new FormDocencia(),
		        'allow_add'    	=> true,
		        'allow_delete' 	=> true,
		        //'prototype' 	=> true,
		        'by_reference' 	=> false
	   		));

			$form->add('collaboradors', 'collection', array(
				'mapped' 	   	=> false,
				'data'			=> $collaboradors,
		        'type' 			=> new FormDocencia(),
		        'allow_add'    	=> true,
		        'allow_delete' 	=> true,
		        //'prototype' 	=> true,
		        'by_reference' 	=> false
	   		));
			
			
			$form->add('auxalumne', 'text', array(		/* Per la cerca. Sense valor */
				'mapped'	=> false,
				'required' 	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			/*
			$idsAlumnes = $curs->getParticipantsIds();  
			
								
			$form->add('participantscurrent', 'hidden', array(
				'mapped' 		=> false,
				'data'			=> implode(";", $idsAlumnes)
			));
				
			$metapersona = new EntityMetaPersona('');
			$persona = new EntityPersona($metapersona, $curs->getClub());
			$persona->setNom(''); 
			$persona->setCognoms(''); 
			$titulacio = new EntityTitulacio($persona, $curs);	
				
			$form->add('formalumne', new FormTitulacio(), array(
					'mapped'   	=> false,
					'data'		=> $titulacio
		   	));
			*/
			$alumnes = $curs->getParticipantsSortedByCognomsNom();
			$form->add('participants', 'collection', array(
				'data'			=> $alumnes,
		        'type' 			=> new FormTitulacio(),
		        'allow_add'    	=> true,
		        'allow_delete' 	=> true,
		        //'prototype' 	=> true,
		        'by_reference' 	=> false
	   		));	
			
		}
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventSubscriber ( new FormCurs () );
		
		$builder->add('id', 'hidden');
		
		$builder->add('action', 'hidden', array('mapped' => false) );
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityCurs'));
	}
		
	public function getName()
	{
		return 'curs';
	}

}
