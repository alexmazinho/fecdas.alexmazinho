<?php
namespace FecdasBundle\Form;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityParte;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class FormParteRenovar extends AbstractType {

    private $anyrenova;
    private $uncheckpersones;
	
    public function __construct($anyrenova = 0, $uncheckuncheckpersones = array())
	{
	    $this->anyrenova = $anyrenova==0?date('Y'):$anyrenova;
	    $this->uncheckuncheckpersones = $uncheckuncheckpersones;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari.
			// Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$parte = $event->getData();
		
			$currentYear = date('Y');
			
			/* Check we're looking at the right data/form */
			if ($parte instanceof EntityParte) {
				
			    $form->add('anyrenova', 'choice', array(
			        'choices'    => BaseController::getArrayAnysPreus(
			            date('m-d') < BaseController::INICI_TRAMITACIO_QUATRIMESTRE_MES."-".BaseController::INICI_TRAMITACIO_QUATRIMESTRE_DIA?$currentYear - 1:$currentYear,
			            date('m-d') >= BaseController::INICI_TRAMITACIO_QUATRIMESTRE_MES."-".BaseController::INICI_TRAMITACIO_QUATRIMESTRE_DIA?$currentYear:$currentYear
			            ),    // Abans de 01/10 renovar any passat i actual, després de 01/10 només renovar any actual
			        'multiple'   => false,
			        'expanded'   => false,
			        'mapped' 	 => false,
			        'data' 		 => $this->anyrenova,
			    ));
			    
			    $form->add('dataalta', 'datetime', array(
			        'widget' 	=> 'single_text',
			        'format' 	=> 'dd/MM/yyyy',
			        'attr'	    =>	array('readonly' => true),
			        'data'      =>  $parte->getDataalta()
			    ));
			    
			    /*$form->add('llicencies', 'collection', array(
			        'type' 		       => new FormLlicenciaRenew(),
			        'allow_add'    	   => true,
			        //'allow_delete' 	=> false,
			        //'by_reference' 	=> false,
			        'data'	            => $parte->getLlicenciesSortedByName()
			    ));*/
			    
			    //if (false) {
			    
			    $form->add('clubs', 'entity', array(
			        'class' 		=> 'FecdasBundle:EntityClub',
			        'query_builder' => function($repository) {
			             return $repository->createQueryBuilder('c')
			                 ->orderBy('c.nom', 'ASC')
			                 ->where('c.databaixa IS NULL');
			                 //->join('c.tipus', 't', 'WITH', 't.id != \''.BaseController::TIPUS_CLUB_PERSONA_FISICA.'\'');
			        },
			        'choice_label' 	=> 'nom',
			        'mapped' 	    => false,
			        'placeholder' 	=> '',	// Important deixar en blanc pel bon comportament del select2
			        'required'  	=> true,
			        'data' 			=> $parte->getClub(),
			        'attr'          => array('autocomplete' => 'off')
			    ));
			    //}
			    
			    $form->add('uncheckpersones', 'hidden', array(
			        'mapped' 	 => false,
			        'data' 		 => implode(";", $this->uncheckuncheckpersones),
			        'attr'       => array('autocomplete' => 'off')
			    ));
			}
		});
		
		$builder->add('llicencies', 'collection', array('type' => new FormLlicenciaRenew($this->uncheckuncheckpersones), /*'allow_add' => true*/));	
		
		$builder->add('page', 'hidden', array(
		    'mapped' 	 => false,
		));

		$builder->add('checkall', 'checkbox', array(
		    'required'    => false,
		    'mapped' 	  => false,
		    'data'        => true
		));
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityParte'));
	}
	
	public function getName()
	{
		return 'parte_renovar';
	}

}
