<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityCurs;

class FormCurs extends AbstractType  implements EventSubscriberInterface {

	private $editor;
	private $stock;
	private $admin;
	
	public function __construct( $options )
	{
		$this->editor = isset($options['editor'])?$options['editor']:false;
		$this->admin = isset($options['admin'])?$options['admin']:false;
		$this->stock = isset($options['stock'])?$options['stock']:'';
	}

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
			
			$club = $curs->getClub();
			
			$editable = $curs->editable() && ($this->editor || $this->admin);
		
			$form->add('num', 'text', array(
				'required' 	=> true,
				'attr'		=>	array('readonly' => !$curs->finalitzat()),
			    'mapped'	=> false,
			    'data'  	=> $curs->getNumActa()
			));
			
			$form->add('numfedas', 'text', array(
			    'required' 	=> true,
			    'attr'		=>	array('readonly' => !$curs->finalitzat()),
			    'data'  	=> $curs->getNumActa()
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
			        'data'  	    => $curs->getClub(),
			        'choices'       => array($curs->getClub()),
			        'choice_label' 	=> 'nom',
			        'attr'		    =>	array('readonly' => true)	// clubs històric
			    ));
				/*$form->add('club', 'entity', array(
							'class' 		=> 'FecdasBundle:EntityClub',
							'query_builder' => function($repository) {
									return $repository->createQueryBuilder('c')
										->where('c.databaixa IS NULL')
										->orderBy('c.nom', 'ASC');
									}, 
							'choice_label' 	=> 'nom',
							//'placeholder' 	=> '',
							'required'  	=> true,
							'attr'		=>	array('readonly' => true)  // Current club. No es pot editar	
				));	*/
			}	
			
			$tipusTitols = '\''.BaseController::TIPUS_TITOL_BUSSEIG.'\', \''.BaseController::TIPUS_ESPECIALITAT.'\'';
			if ($club->esFederacio()) $tipusTitols .= ', \''.BaseController::TIPUS_TITOL_TECNIC.'\'';
			// Només federació pot fer cursos tècnic / instructor
			$form->add('titol', 'entity', array(
						'class' 		=> 'FecdasBundle:EntityTitol',
						'query_builder' => function($repository) use($tipusTitols) {
								return $repository->createQueryBuilder('t')
									->where('t.organisme = \''.BaseController::ORGANISME_CMAS.'\'' )
									->andWhere('t.curs = 1')
									->andWhere('t.tipus IN ('.$tipusTitols.')')
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
			$collaboradors = $curs->getDocentsByRoleSortedByCognomsNom(BaseController::DOCENT_COLLABORADOR);
			
			$persona = null;
			if ($director != null && $director->getMetadocent() != null) $persona = $director->getMetadocent()->getPersona($club);
			$form->add('auxdirector', 'text', array(
				'mapped'	=> false,
				'data'  	=> $persona != null?$persona->getId():'',
			    'attr'		=>	array('readonly' => !$editable || !$this->admin)  // Admin pot tramitar cursos instructor escollint el director
			));	

			$form->add('auxcarnet', 'text', array(
				'mapped'	=> false,
			    'data'  	=> $director != null?$director->getCarnet():'',
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			$persona = null;
			if ($codirector != null && $codirector->getMetadocent() != null) $persona = $codirector->getMetadocent()->getPersona($club);
			$form->add('auxcodirector', 'text', array(
				'mapped'	=> false,
				'required' 	=> false,
				'data'  	=> $persona != null?$persona->getId():'',
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			$form->add('auxcocarnet', 'text', array(
				'mapped'	=> false,
			    'data'  	=> $codirector!=null?$codirector->getCarnet():'',
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
		        'type' 		   	=> new FormDocencia($editable),
		        'allow_add'    	=> true,
		        'allow_delete' 	=> true,
		        'by_reference' 	=> false
	   		));

			$form->add('collaboradors', 'collection', array(
				'mapped' 	   	=> false,
				'data'			=> $collaboradors,
		        'type' 			=> new FormDocencia($editable),
		        'allow_add'    	=> true,
		        'allow_delete' 	=> true,
		        'by_reference' 	=> false
	   		));
			
			
			$form->add('auxalumne', 'text', array(		/* Per la cerca. Sense valor */
				'mapped'	=> false,
				'required' 	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			$participants = $curs->getParticipantsSortedByCognomsNom();
		
			$form->add('participants', 'collection', array(
				'mapped' 	   	=> false,
				'data'			=> $participants,
		        'type' 			=> new FormTitulacio($editable),
		        'allow_add'    	=> true,
		        'allow_delete' 	=> true,
		        'by_reference' 	=> false
	   		));	
			
		}
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
	    $builder->addEventSubscriber ( new FormCurs ( array('editor' => $this->editor, 'admin' => $this->admin, 'stock' => $this->stock ) ));
	
		$builder->add('id', 'hidden');
		
		$builder->add('action', 'hidden', array('mapped' => false) );
		
		$builder->add('stock', 'hidden', array('mapped' => false, 'data' => $this->stock) );
		
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
