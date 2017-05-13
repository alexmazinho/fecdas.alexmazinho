<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityTitulacio;
use FecdasBundle\Entity\EntityPersona;
use FecdasBundle\Entity\EntityMetaPersona;
use FecdasBundle\Entity\EntityCurs;
use FecdasBundle\Form\FormDocencia;


class FormCurs extends AbstractType  implements EventSubscriberInterface {

	public static function getSubscribedEvents() {

		// Tells the dispatcher that you want to listen on the form.pre_set_data
		// event and that the preSetData method should be called.
		return array (
				
				//FormEvents::POST_SUBMIT => array('postSubmitData', 900),  // Desactiva validació
				FormEvents::SUBMIT => array('submitData', 900),
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
			
			$editable = false;
			if (!$curs->omplert()) {
				// Curs en procés
				$editable = true;	
			}
			
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
							'placeholder' 	=> 'Escollir Club',
							'required'  	=> true,
							'attr'			=> array('readonly' => true)	// Current club. No es pot editar	
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
						'attr'			=>	array('readonly' => !$editable)
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
				'read_only' 	=> false,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> false,
				'format' 		=> 'dd/MM/yyyy',
				'attr'			=>	array('readonly' => !$editable)
			));
			
			$form->add('auxdirector', 'text', array(
				'mapped'	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));	

			$form->add('auxcarnet', 'text', array(
				'mapped'	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			$form->add('auxcodirector', 'text', array(
				'mapped'	=> false,
				'required' 	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));	
			
			$form->add('auxcocarnet', 'text', array(
				'mapped'	=> false,
				'attr'		=>	array('readonly' => !$editable)
			));
				
				
			$metapersona = new EntityMetaPersona('');
			$persona = new EntityPersona($metapersona, $curs->getClub());
			$persona->setNom(''); 
			$persona->setCognoms(''); 
			$persona->setDatanaixement(new \DateTime());
				
			$form->add('formalumne', new FormAlumne(), array(
					'mapped'   	=> false,
					'data'		=> $persona
		   	));	
						
			/*$preu = $curs->getPreu($this->anypreu);
			
			$form->add ( 'preu', 'number', array (
					'required' 	=> true,
					'mapped' 	=> false,
					'scale' 	=> 2,
					'data' 		=> ($preu == null?0:$preu->getPreu()),
					'mapped' 	=> false
			));
					
			$form->add('iva', 'choice', array(
					'choices'   => BaseController::getIVApercents(),
					'multiple'  => false,
					'expanded'  => false,
					'mapped' 	=> false,
					'data' 		=> ($preu == null?0:$preu->getIva()),
					
			));
			
			
			
			$form->add ('pes', 'integer', array (
					'required' 		=> false,
					'scale' 		=> 0,
					//'disabled' 	=> $curs->getTransport() != true
					'read_only' 	=> $curs->getTransport() != true
			));
			
			$form->add('stockable', 'checkbox', array(
					'required' 		=> false,
					'data' 			=> $curs->getStockable() == true
			));
			
			
			$this->subdepartamentFormModifier($form, $curs);
			
			
			if ($curs->getTipus() == BaseController::TIPUS_PRODUCTE_LLICENCIES && !$curs->esNou()) {
					$activat = true;
					if ($curs->getCategoria() != null && 
						$curs->getCategoria()->getTipusparte() != null) $activat =  $curs->getCategoria()->getTipusparte()->getActiu();
					
					$form->add('activat', 'checkbox', array(
							'required' 	=> false,
							'mapped' 	=> false,
							'data' 		=> $activat
					));
			} else {
			    $form->add('activat', 'hidden', array(
                            'required'  => false,
                            'mapped'    => false,
                    ));
			}*/
		}
	}
	
	/*public function submitData(FormEvent $event) {
		// It's important here to fetch $event->getForm()->getData(), as
		// $event->getData() will get you the client data (that is, the ID)
		$curs = $event->getForm()->getData();
		$form = $event->getForm ();
		$origen = $form->get('anypreus')->getData(); // Detectar origen, si és selector anys refrescar els valors del preu, iva...
	
	}*/
	
// Afegir subdepartament en funció del valor escollit a departament
	/*public function subdepartamentFormModifier(FormInterface $form, EntityProducte $curs = null) {
		$subdepartaments = array();
		if ($curs == null || $curs->getDepartament() == 0) $subdepartaments = BaseController::getDepartamentsConta( -1  );
		else $subdepartaments = BaseController::getDepartamentsConta( $curs->getDepartament()  );	
			
		// Selector subdepartament compta
		$form->add('subdepartament', 'choice', array(
				'choices'   => $subdepartaments,
				'multiple'  => false,
				'expanded'  => false,
				'data' 		=> $curs->getSubdepartament(),
		));
			
    }*/
    	
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventSubscriber ( new FormCurs () );
		
		$builder->add('id', 'hidden');
		
		$builder->add('instructors', 'collection', array(
			'mapped' 	   => false,
	        'type' 		   => new FormDocencia(),
	        'allow_add'    => true,
	        'allow_delete' => true,
	        'by_reference' => false
   		));

		$builder->add('colaboradors', 'collection', array(
			'mapped' 	   => false,
	        'type' 		   => new FormDocencia(),
	        'allow_add'    => true,
	        'allow_delete' => true,
	        'by_reference' => false
   		));
		
		
		
		/*
		$builder->add('abreviatura', 'text', array(
				'required' => true,
				'attr' => array('maxlength' => '3'),
		));
		
		$builder->add('descripcio', 'textarea', array(
				'required' => true,
				'attr' => array('rows' => '4'),
		));
		
		$builder->add('tipus', 'choice', array(
				'choices'   => BaseController::getTipusDeProducte(),
				'multiple'  => false,
				'expanded'  => false,
		));
		
		$builder->add ('minim', 'integer', array (
				'required' => false,
				'scale' => 0
		));
		
		$builder->add('databaixa', 'datetime', array(
				'required' => false,
				'read_only' => false,
				'widget' => 'single_text',
				'input' => 'datetime',
				'placeholder' => false,
				'format' => 'dd/MM/yyyy HH:mm',
		));
		
		$builder->add('departament', 'choice', array(
				'choices'   => BaseController::getDepartamentsConta( 0 ),
				'multiple'  => false,
				'expanded'  => false,
				'placeholder' => '',
				//'data' 		=> $curs->getDepartament(),
		));
		
		$current = $this;
		
		$builder->get('departament')->addEventListener(
	            FormEvents::POST_SUBMIT,
	            function (FormEvent $event) use ($current) {
	                // It's important here to fetch $event->getForm()->getData(), as
	                // $event->getData() will get you the client data (that is, the ID)
	                $departament = $event->getForm()->getData(); // => Dada de l'esdeveniment
					
					$curs = $event->getForm()->getParent()->getData(); // Dades del formulari pare

					$curs->setDepartament($departament);	
	                // since we've added the listener to the child, we'll have to pass on
	                // the parent to the callback functions!
	                //$formModifier($event->getForm()->getParent(), $sport);
					$current->subdepartamentFormModifier($event->getForm()->getParent(), $curs);
	            }
	    );*/
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
