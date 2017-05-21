<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Entity\EntityClub;
use FecdasBundle\Controller\BaseController;

class FormClub extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
	    $options = $this->options; 
        
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$club = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($club instanceof EntityClub) {
				
				// Cerca federats club => junta
				$form->add('federats', 'entity', array(
					'class' => 'FecdasBundle:EntityPersona',
					'query_builder' => function($repository) use ($club) {
						return $repository->createQueryBuilder('e')
							->where('e.club = :codiclub')
							->andWhere('e.databaixa is null')
							->orderBy('e.cognoms', 'ASC')
							->setParameter('codiclub', $club->getCodi());
						},
					'choice_label' 	=> 'nomCognoms',
					'placeholder' 	=> '',
					'required'  	=> false,
					'mapped'		=> false,
					'property_path' => 'persona',
				));
				
				$form->add('carrec', 'choice', array(
					'required' 		=> false,
					'mapped'		=> false,
					'choices' 		=> BaseController::getCarrecs(),
					'placeholder' 	=> 'Escollir càrrec...',
				));

				$form->add('nommembre', 'text', array(
					'required'  	=> false,
					'mapped'		=> false,
				));

				$form->add('carrecs', 'hidden');
				
				// Per poder gestionar Camel Case 
				$form->add('addrcomarca', 'choice', array(
					'choices' 	=> $options['comarques'],
					'required'  => false,
					'data'		=> mb_convert_case($club->getAddrcomarca(), MB_CASE_TITLE, "utf-8")
				));
		
				$form->add('addrprovincia', 'choice', array(
					'choices'   => $options['provincies'],
					'required'  => false,
					'data'		=> mb_convert_case($club->getAddrprovincia(), MB_CASE_TITLE, "utf-8")
				));

				$form->add('addrcomarcacorreu', 'choice', array(
					'choices'   => $options['comarques'],
					'required'  => false,
					'data'		=> mb_convert_case($club->getAddrcomarcacorreu(), MB_CASE_TITLE, "utf-8")
				));
				
				$form->add('addrprovinciacorreu', 'choice', array(
					'choices'   => $options['provincies'],
					'required'  => false,
					'data'		=> mb_convert_case($club->getAddrprovinciacorreu(), MB_CASE_TITLE, "utf-8")
				));
				
				$form->add('role', 'choice', array(
					'choices' => BaseController::getRoles( $this->options['admin'] ),
					'data' => BaseController::ROLE_FEDERAT,
					'mapped' => false,
				));
	   				
			}
		});
		
		$builder->add('codi', 'hidden');
		
		$builder->add('nom', 'text', array('attr' =>	array('readonly' => true)));
		
		$builder->add('telefon', 'text', array(
				'required'  => false,
		));
		
		$builder->add('fax', 'text', array(
				'required'  => false,
		));
		
		$builder->add('mobil', 'text', array(
				'required'  => false,
		));
		
		$builder->add('mail', 'email', array('attr'			=>	array('readonly' => true)));

		$builder->add('web', 'text', array(
				'required'  => false,
		));
		
		$builder->add('cif', 'text', array('attr'			=>	array('readonly' => true)));
				
		$builder->add('addradreca', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrpob', 'text', array(
				'required'  => false,
		));

		$builder->add('addrcp', 'text', array(
				'required'  => false,
		));
		
		
		$builder->add('addradrecacorreu', 'text', array(
				'required'  => false,
		));
		
		$builder->add('addrpobcorreu', 'text', array(
				'required'  => false,
		));

		$builder->add('addrcpcorreu', 'text', array(
				'required'  => false,
		));
		
				// Junta
		$builder->add('dataalta', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> '',
				'format' 		=> 'dd/MM/yyyy',
				'attr'			=>	array('readonly' => true)
		));

		$builder->add('databaixa', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> '',
				'format' 		=> 'dd/MM/yyyy',
				'attr'			=>	array('readonly' => true)
		));

		$builder->add('datacreacio', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> '',
				'format' 		=> 'dd/MM/yyyy'
		));

		$builder->add('datajunta', 'date', array(
				'required'  	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'placeholder' 	=> '',
				'format' 		=> 'dd/MM/yyyy'
		));
		
		$builder->add('estatuts', 'checkbox', array(
				'required'  => false,
		));
		
		$builder->add('registre', 'text', array(
				'required'  => false,
		));
		
				/* Camps nou usuari */
		$builder->add('user', 'email', array(
				'required' => false,
				'mapped' => false,
		));
		
		$builder->add('pwd', 'repeated', array(
    			'type' => 'password',
    			'required' => false,
    			'first_name'  => 'first',
    			'second_name' => 'second',
				'mapped' => false,
				'options' => array('always_empty' => true, 'required' => false),
		));
		
		$builder->add('randompwd', 'text', array(
				'required' 	=> false,
				'mapped' 	=> false,
				'attr'		=>	array('readonly' => true)
		));
		
		// EScollir persona tècnic
		$builder->add('auxinstructordni', 'text', array(
				'mapped'	=> false,
				'attr'		=>	array('readonly' => false)
		));	
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityClub'));
	}
	
	public function getName()
	{
		return 'club';
	}

}
