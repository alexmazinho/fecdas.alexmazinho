<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use FecdasBundle\Controller\BaseController;
use FecdasBundle\Entity\EntityPersona;

class FormPersona extends AbstractType {

	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
			$form = $event->getForm();
			$persona = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($persona instanceof EntityPersona) {

				$altres = array();
				if ($persona != null) $altres = $persona->getAltrestitulacionsIds();  
								
				$form->add('altrestitolscurrent', 'hidden', array(
					'mapped' 		=> false,
					'data'			=> implode(";", $altres)
				));
			
				$form->add('fotoupld', 'file', array('mapped' => false, 'required'  => false, 'attr' => array('accept' => 'image/*')));
				
				$form->add('foto', 'hidden', array('mapped' => false, 'required'  => false, 'data'	=> ($persona->getFoto()==null?'':$persona->getFoto()->getPath())));
				
				$form->add('arxiuupld', 'file', array('mapped' => false, 'required'  => false, 'attr' => array('accept' => 'application/pdf,application/msword,text/*')));
				
				$form->add('arxiu', 'hidden', array('mapped' => false, 'required'  => false, 'data'	=> ''));
			}
		});	
			
			
		$builder->add('id', 'hidden');

		//$readonly = ! $this->options['edit'];
		
		$builder->add('nom', 'text');
		$builder->add('cognoms', 'text');
		$builder->add('dni', 'text', array(
		    'attr'			=>	array('readonly' => isset($this->options['editdni']) && $this->options['editdni'] == false)
			));
		
		$builder->add('datanaixement', 'date',	array(
				'widget' 	=> 'single_text',
				'format' => 'dd/MM/yyyy', 
				'years' => range(1900, date("Y", strtotime('now') ) )
				)
		);
		
		$builder->add('sexe', 'choice', array(
				'choices'   => array('H' => 'Home', 'M' => 'Dona'),
				'multiple'  => false,
				'expanded'  => true,
		));

		$builder->add('telefon1', 'text', array(
    	    'required'  => false,
			));
		
		$builder->add('telefon2', 'text', array(
    	    'required'  => false,
			));
		
		$builder->add('mail', 'email', array(
    	    'required'  => false,
			));
		
		$builder->add('addradreca', 'text', array(
    	    'required'  => false,
			));
		
		$builder->add('addrpob', 'search', array(
    	    'required'  => false,
			));
		
		$builder->add('addrcp', 'text', array(
    	    'required'  => false,
			));
		
		$builder->add('addrprovincia', 'choice', array(
				'choices' => $this->options['provincies'],
				'preferred_choices' => array('Barcelona','Girona','Tarragona','Lleida' ),
				//'placeholder' => 'Província...',
				'required'  => false,
		));
		
		$builder->add('addrcomarca', 'choice', array(
				'choices' => $this->options['comarques'],
				//'preferred_choices' => array(''),
				//'placeholder' => 'Comarca ...',
				'required'  => false,
		));
		
		$builder->add('addrnacionalitat', 'choice', array(
				'choices' => $this->options['nacions'],
				'preferred_choices' => array('ESP' ),
				'required'  => false,
		));
		
		$builder->add('estranger', 'checkbox', array(
    	    	'required'  => false,
    	    	'mapped'  => false,
		));
		
		$builder->add('altretitol', 'entity', array(
				'class' 		=> 'FecdasBundle:EntityTitol',
				'query_builder' => function($repository) {
						return $repository->createQueryBuilder('t')
							->where('t.organisme <> \''.BaseController::ORGANISME_CMAS.'\'' )
							//->andWhere('t.actiu = 1')
							->orderBy('t.titol', 'ASC');
						}, 
				'choice_label' 	=> 'llistaText',
				'placeholder' 	=> 'Escollir titulació externa',
				'mapped' 		=> false,
				'required'  	=> false
		));	
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityPersona'));
	}
		
	public function getName()
	{
		return 'persona';
	}

}
