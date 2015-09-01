<?php
namespace FecdasBundle\Form;

use FecdasBundle\Entity\EntityLlicencia;
use FecdasBundle\Entity\EntityPersona;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;


class FormLlicencia extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. 
			// Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$llicencia = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($llicencia instanceof EntityLlicencia) {
		
				$parte = $llicencia->getParte(); 
				$club = $parte->getClub();
				
				$form->add('persona', 'entity', array(
							'class' => 'FecdasBundle:EntityPersona',
							'query_builder' => function($repository) use ($club) {
								return $repository->createQueryBuilder('e')
									->where('e.club = :codiclub')
									->andWhere('e.databaixa is null')
									->orderBy('e.cognoms', 'ASC')
									->setParameter('codiclub', $club->getCodi());
								},
							'choice_label' => 'llistaText',
							'empty_value' => '',
							'required'  => false,
							'read_only' => !$parte->isAllowEdit(),
							'property_path' => 'persona',
				));
				
				$llistacategoria = 'llistaText';
				$current = $parte->getAny();
				if ($current > date('Y')) {  // Seleccionar preu post (any posterior)
					$llistacategoria = 'llistaTextPost';
				}
				
				$tipusparte = $parte->getTipus()->getId();
				$form->add('categoria', 'entity', array(
							'class' => 'FecdasBundle:EntityCategoria',
							'query_builder' => function($repository) use ($tipusparte, $current) {
								return $repository->createQueryBuilder('c')
									->join('c.producte', 'o')
									->join('c.tipusparte', 'tp')
									->join('o.preus', 'p')
									->where('tp.id = :tipusparte')
									->andwhere('p.anypreu = :anypreu')
									->andwhere('p.preu > 0')
									->orderBy('c.simbol', 'ASC')
									->setParameter('anypreu', $current)
									->setParameter('tipusparte', $tipusparte);
								},
							'choice_label' => $llistacategoria,
							'required'  => true,
							'read_only' => !$parte->isAllowEdit(),
				));
				$form->add('pesca', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('escafandrisme', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('natacio', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('orientacio', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('biologia', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('fotocine', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('hockey', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('fotosubapnea', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('videosub', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('apnea', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('rugbi', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('besportiu', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
				$form->add('bampolles', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				$form->add('enviarllicencia', 'checkbox', array(
						'required'  => false,
						'read_only' => !$parte->isAllowEdit(),
				));
				
			}
				
		});
		
		$builder->add('id', 'hidden');
		
		$builder->add('datacaducitat', 'date', array(
			'widget' => 'single_text',
			'format' => 'dd/MM/yyyy',
			'attr'=>array('style'=>'display:none;'), 
		));

		$builder->add('datacaducitatshow', 'date', array(
			'widget' => 'single_text', 
			'format' => 'dd/MM/yyyy',
			'mapped'  => false,
			'read_only' => true,
		));
		
		
		$builder->add('nocmas', 'checkbox', array(
    	    'required'  => false,
			'read_only' => true,
		));
		
		$builder->add('fusell', 'checkbox', array(
    	    'required'  => false,
			'read_only' => true,
		));
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityLlicencia'));
	}
	
	public function getName()
	{
		return 'llicencia';
	}

}