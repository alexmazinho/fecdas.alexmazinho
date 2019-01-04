<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Entity\EntityLlicencia;

class FormLlicencia extends AbstractType {

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
			$llicencia = $event->getData();
		
			/* Check we're looking at the right data/form */
			if ($llicencia instanceof EntityLlicencia) {
		
				$parte = $llicencia->getParte(); 
				$club = $parte->getClubparte();
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
							'placeholder' => '',
							'required'  => false,
							'property_path' => 'persona',
							'data'          => ($llicencia->getPersona()!=null?$llicencia->getPersona():null),
							'attr'			=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				
				$current = $parte->getAny();
				$llistacategoria = function ($value, $key, $index) use ($current) {
					if ($current > date('Y')) {  // Seleccionar preu post (any posterior)
					    return $value->getLlistaTextPost($this->admin);
					}
					
					return $value->getLlistaTextAny($current, $this->admin);
			    };
				
				$tipusparte = $parte->getTipus()->getId();
				if ($parte->isAllowEdit()) {
					
					$form->add('categoria', 'entity', array(
								'class' => 'FecdasBundle:EntityCategoria',
								'query_builder' => function($repository) use ($tipusparte, $current) {
									return $repository->createQueryBuilder('c')
										->join('c.producte', 'o')
										->join('c.tipusparte', 'tp')
										->where('tp.id = :tipusparte')
										->orderBy('c.simbol', 'ASC')
										->setParameter('tipusparte', $tipusparte);
									},
								'choice_label' => $llistacategoria,
								'required'  => true
					));
				} else {
					$currentCategoria = $llicencia->getCategoria();
					$form->add('categoria', 'entity', array(
								'class' => 'FecdasBundle:EntityCategoria',
								'query_builder' => function($repository) use ($currentCategoria) {
									return $repository->createQueryBuilder('c')
										->where('c.id = :categoria')
										->setParameter('categoria', $currentCategoria);
									},
								'choice_label' => $llistacategoria,
								'required'  => true,
								'attr'		=>	array('readonly' => !$parte->isAllowEdit())
					));
					
					
				}


				$form->add('pesca', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('escafandrisme', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('natacio', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('orientacio', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('biologia', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('fotocine', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('hockey', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('fotosubapnea', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('videosub', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('apnea', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('rugbi', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('besportiu', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
				));
				
				$form->add('bampolles', 'checkbox', array(
						'required'  => false,
						'attr'		=>	array('readonly' => !$parte->isAllowEdit())
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
			'mapped' => false,
			'attr'	 =>	array('readonly' => true)
		));
		
		
		$builder->add('nocmas', 'checkbox', array(
    	    'required'  => false,
			'attr'		=>	array('readonly' => true)
		));
		
		$builder->add('fusell', 'checkbox', array(
    	    'required'  => false,
			'attr'		=>	array('readonly' => true)
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
