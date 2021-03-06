<?php
namespace FecdasBundle\Form;

use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Controller\BaseController;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class FormParte extends AbstractType {

	private $admin;
	
	public function __construct($admin = false)
	{
		$this->admin = $admin;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari.
			// Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$parte = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($parte instanceof EntityParte) {
				
				$tipusparte = $parte->getTipus();
				$dataalta = $parte->getDataalta();
				if ($dataalta == null) $dataalta = new \DateTime();
				$llistatipus = BaseController::getLlistaTipusParte($parte->getClubparte(), $dataalta, $this->admin);
				
				if ($parte->esNova() && $parte->isAllowEdit()) {
					// Mostra només la llista dels permesos
					$tipusparteoptions = array('class' => 'FecdasBundle:EntityParteType',
							'query_builder' => function($repository) use ($llistatipus) {
								return $repository->createQueryBuilder('t')->orderBy('t.descripcio', 'ASC')
									->where($repository->createQueryBuilder('t')->expr()->in('t.id', ':llistatipus'))
									->setParameter('llistatipus', $llistatipus);
								}, 
							'choice_label' => 'descripcio', 
							'required'  => count($llistatipus) == 1, 
							'placeholder' => 'Selecciona una...',
					);
				} else {
					$tipusparteoptions = array('class' => 'FecdasBundle:EntityParteType',
							'query_builder' => function($repository) use ($tipusparte) {
								return $repository->createQueryBuilder('t')->orderBy('t.id', 'ASC')
									->where('t.id = :tipusparte')
									->setParameter('tipusparte', $tipusparte);
								}, 
							'choice_label' => 'descripcio',
							'attr'		=>	array('readonly' => true) 
							
					);
					
				}
				
				$form->add('tipus', 'entity', $tipusparteoptions);
				
				$club = $parte->getClubparte();
				if (!$this->admin || !$parte->esNova() || !$parte->isAllowEdit()) {
				    $form->add('club', 'entity', array(
        				        'class' 		=> 'FecdasBundle:EntityClub',
				                'query_builder' => function($repository) use ($club) {
            				        return $repository->createQueryBuilder('c')
            				        ->where('c.codi = :club')
            				        ->setParameter('club', $club->getCodi());
        				        },
        				        'choice_label' 	=> 'nom',
        				        'data' 			=> $club,
        				        'mapped' 	    => false,
        				        'attr'		    => array('readonly' => true)
				        ));
				} else {
    				$form->add('club', 'entity', array(
        				    'class' 		=> 'FecdasBundle:EntityClub',
        				    'query_builder' => function($repository) {
            				    return $repository->createQueryBuilder('c')
            				    ->orderBy('c.nom', 'ASC')
            				    ->where('c.databaixa IS NULL');
            				    //->join('c.tipus', 't', 'WITH', 't.id != \''.BaseController::TIPUS_CLUB_PERSONA_FISICA.'\'');
        				    },
        				    'choice_label' 	=> 'nom',
        				    'placeholder' 	=> '',	// Important deixar en blanc pel bon comportament del select2
        				    'required'  	=> false,
        				    'data' 			=> $club,
        				    'mapped' 	    => false,
        				    'attr'		    => array('readonly' => false)
    				));
				}
				
				$form->add('any', 'text', array(
						'mapped'  => false,
						'attr'		=>	array('readonly' => true),
						'data'		=> $parte->getAny()
				));

				$form->add('dataalta', 'datetime', array(
				    'widget' 	=> 'single_text',
				    'format' 	=> 'dd/MM/yyyy HH:mm',
				    'attr'		=>	array('readonly' => !($parte->esNova() && $parte->isAllowEdit()))
				));
				
				if ($parte->getRebut() != null) {
					$form->add('datapagament', 'date', array(
							'widget'	=> 'single_text',
							'disabled' 	=> true,
							'format' 	=> 'dd/MM/yyyy',
							'data'		=> $parte->getRebut()->getDatapagament(),
							'mapped' 	=> false,
							'attr'		=>	array('readonly' => !$this->admin)
					));
				} else {
					$form->add('datapagament', 'hidden', array());
				}
			}
		});
				
		
		$builder->add('id', 'hidden');
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityParte'));
	}
	
	public function getName()
	{
		return 'parte';
	}

}
