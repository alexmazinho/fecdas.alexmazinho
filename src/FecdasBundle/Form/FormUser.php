<?php
namespace FecdasBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use FecdasBundle\Entity\EntityUser;

class FormUser extends AbstractType {

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
	        $user = $event->getData();
	        
	        /* Check we're looking at the right data/form */
	        $newsletter = true;
	        $userDisabled = false;
	        $usertoken = '';
	        if ($user instanceof EntityUser) {
	            $newsletter = $user->getNewsletter();
	            $userDisabled = true;
	            $usertoken = $user->getUser();
	        } else {
	            if (isset($this->options['dni'])) {
	                // Registre usuaris, varis usuaris amb el mateix mail
	                $form->add('dni', 'text', array(
	                   'required'  => false,
	                   'mapped'    => false,
	                   'data'      => $this->options['dni']
	                ));
	            }
	            if (isset($this->options['newsletter'])) {
	                $newsletter = $this->options['newsletter'];
	            }
	        }

	        $form->add('usertoken', 'hidden', array(
	            'mapped'    => false,
	            'data'      => $usertoken
	        ));
	        
	        $form->add('newsletter', 'checkbox', array(
	            'required'  => false,
	            'data'      => $newsletter,
	            'mapped'    => false
	        ));
	        
	        $form->add('user', 'email',array(
	            'attr'		=>	array('readonly' => $userDisabled)
	        ));
	    });
	    
		$builder->add('pwd', 'repeated', array(
    		'type'        => 'password',
    		'required'    => false,
    		'first_name'  => 'first',
    		'second_name' => 'second',
		));
		
		/*$builder->add('forceupdate', 'checkbox', array(
    	    'required'  => false
		));*/
		
		$builder->add('recoverytoken', 'hidden');
		
		$builder->add('terms', 'checkbox', array(
		    'required'  => false,
		    'data'      => true,
		    'mapped'    => false
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
