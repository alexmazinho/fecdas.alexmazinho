<?php
/**
* @Service()
* @Tag("twig.extension")
*/
namespace FecdasBundle\Twig;

class FecdasExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            'cut' => new \Twig_Filter_Method($this, 'cutFilter', array('length' => false, $wordCut = false, 'appendix' => false)),
        );
    }
   
    /**
     * @param string $text
     * @param integer $length
     * @param boolean $wordCut
     * @param string $appendix
     * @return string
     */
    public function cutFilter($text, $length = 20, $wordCut = true, $appendix = ' ...')
    {
        $maxLength = (int)$length - strlen($appendix);
        if (strlen($text) > $maxLength) {
            if($wordCut){
                $text = substr($text, 0, $maxLength + 1);
                $text = substr($text, 0, strrpos($text, ' '));
            }
            else {
                $text = substr($text, 0, $maxLength);
            }
            $text .= $appendix;
        }
       
        return $text;
    }
    
    public function getName()
    {
    	return 'fecdas_extension';
    }
}