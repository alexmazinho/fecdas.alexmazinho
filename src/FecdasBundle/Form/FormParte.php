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
				$llistatipus = BaseController::getLlistaTipusParte($parte->getClub(), $dataalta);
				
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
							'empty_value' => 'Selecciona una...',
					);
				} else {
					$tipusparteoptions = array('class' => 'FecdasBundle:EntityParteType',
							'query_builder' => function($repository) use ($tipusparte) {
								return $repository->createQueryBuilder('t')->orderBy('t.id', 'ASC')
									->where('t.id = :tipusparte')
									->setParameter('tipusparte', $tipusparte);
								}, 
							'choice_label' => 'descripcio', 
							'read_only' => true,
					);
					
					//$builder->add('tipus', 'text', array('mapped'  => false, 'data' => $llistatipus[0], 'read_only' => true,));
				}
				
				$form->add('tipus', 'entity', $tipusparteoptions);
				
				$form->add('any', 'text', array(
						'mapped'  => false,
						'read_only' => true,
						'data'		=> $parte->getAny()
				));

				if ($parte->getRebut() != null) {
					$form->add('datapagament', 'date', array(
							'widget' 	=> 'single_text',
							'format' 	=> 'dd/MM/yyyy',
							'disabled' 	=> true,
							'data'		=> $parte->getRebut()->getDatapagament(),
							'mapped' 	=> false,
					));
				} else {
					$form->add('datapagament', 'hidden', array());
				}
				
				
			}
		});
				
		
		$builder->add('id', 'hidden');

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
		return 'parte';
	}

}
