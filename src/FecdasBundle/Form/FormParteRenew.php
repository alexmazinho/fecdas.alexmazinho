<?php
namespace FecdasBundle\Form;

use FecdasBundle\Entity\EntityParte;
use FecdasBundle\Controller\BaseController;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class FormParteRenew extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari.
			// Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$parte = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($parte instanceof EntityParte) {
				
				$dataalta = $parte->getDataalta();
				if ($dataalta == null) $dataalta = new \DateTime();
				$llistatipus = BaseController::getLlistaTipusParte($parte->getClub(), $dataalta);
				$tipusparte = $parte->getTipus();
				
				$form->add('tipus', 'entity',	array(
							'class' => 'FecdasBundle:EntityParteType',
							'query_builder' => function($repository) use ($tipusparte) {
								return $repository->createQueryBuilder('t')->orderBy('t.id', 'ASC')
									->where('t.id = :tipusparte')
									->setParameter('tipusparte', $tipusparte);
								}, 
							'choice_label' 	=> 'descripcio', 
							'disabled' 		=> true,
							'data'			=> $parte->getTipus()
					));
				
				$form->add('any', 'text', array(
						'mapped'  => false,
						'read_only' => true,
						'data'		=> $parte->getAny()
				));
				
			}
		});
		
		
		$builder->add('id', 'hidden');
		
		$builder->add('cloneid', 'hidden', array('mapped'  => false,));
		
		$builder->add('llicencies', 'collection', array('type' => new FormLlicenciaRenew()));				

		$builder->add('datapagament', 'hidden', array());
		
		$builder->add('dataalta', 'datetime', array(
				'read_only' => true,
				'widget' => 'single_text',
				'input' => 'datetime',
				'empty_value' => false,
				'format' => 'dd/MM/yyyy HH:mm',
		));
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityParte'));
	}
	
	public function getName()
	{
		return 'parte_renew';
	}

}
