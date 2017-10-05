<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use FecdasBundle\Entity\EntityUser;
use FecdasBundle\Controller\BaseController;

class FormClubUser extends AbstractType {

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
			$user = $event->getData();
			
			/* Check we're looking at the right data/form */
			if ($user instanceof EntityUser) {
				
				$form->add('role', 'choice', array(
					'choices' => BaseController::getRoles( $this->options['admin'] ),
					'data' => BaseController::ROLE_CLUB,
					'required' => false,
					'mapped' => false
				));
			}
		});
		
		/* Camps nou usuari */
		$builder->add('id', 'hidden');

		$builder->add('club', 'hidden', array(
		    'data' => $this->options['club'],
		    'mapped' => false
		));
		
		$builder->add('user', 'email', array(
				'required' => false
		));
		
		$builder->add('pwd', 'repeated', array(
    			'type' => 'password',
    			'required' => false,
    			'first_name'  => 'first',
    			'second_name' => 'second',
				'options' => array('always_empty' => true, 'required' => false),
		));
		
		$builder->add('randompwd', 'text', array(
				'required' 	=> false,
				'mapped' 	=> false,
				'attr'		=>	array('readonly' => true)
		));
		
		// Escollir persona tècnic
		$builder->add('auxinstructordni', 'text', array(
				'mapped'	=> false,
				'required' => false,
				'attr'		=>	array('readonly' => false)
		));	
		
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array('data_class' => 'FecdasBundle\Entity\EntityUser'));
	}
	
	public function getName()
	{
		return 'user';
	}

}
