<?php
namespace FecdasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use FecdasBundle\Classes\Funcions;
use FecdasBundle\Controller\BaseController;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity 
 * @ORM\Table(name="m_arxius")
 * 
 * @author alex	
 *
 */
class EntityArxiu {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	
	
	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $path;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	protected $titol;
	
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $imatge;
	
	/**
	 * @Assert\File(maxSize="2000000")
	 */
	protected $file;
	
	/**
	 * @ORM\ManyToOne(targetEntity="EntityPersona", inversedBy="arxius")
	 * @ORM\JoinColumn(name="persona", referencedColumnName="id")
	 */
	protected $persona;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $dataentrada;
	
	/**
	 * Constructor
	 */
	public function __construct($file, $imatge = true, $persona = null)
	{
		$this->id = 0;	
		$this->dataentrada = new \DateTime();
		$this->imatge = $imatge;
		$this->file = $file;
		$this->persona = $persona;
	}
	
	public function __toString() {
		return $this->path;
	}

	public function esNova()
	{
		return ($this->id == 0);
	}
	
	public function esBaixa()
	{
		return $this->databaixa != null;
	}
	
	public function esImatge()
	{
		return $this->imatge == true;
	}
	
	public function esPdf()
	{
		return (($temp =  strlen($this->getPath()) - strlen(".pdf")) >= 0  &&  stripos($this->getPath(), ".pdf", $temp) !== false);  // Case insensitive
	}
	
	public function esWord()
	{
		return  (($temp =  strlen($this->getPath()) - strlen(".doc")) >= 0  &&  stripos($this->getPath(), ".doc", $temp) !== false) ||
			  	(($temp =  strlen($this->getPath()) - strlen(".docx")) >= 0  &&  stripos($this->getPath(), ".docx", $temp) !== false) ||
			  	(($temp =  strlen($this->getPath()) - strlen(".odt")) >= 0  &&  stripos($this->getPath(), ".odt", $temp) !== false);  // Case insensitive
	}
	
	public function esExcel()
	{
		return (($temp =  strlen($this->getPath()) - strlen(".xls")) >= 0  &&  stripos($this->getPath(), ".xls", $temp) !== false) ||
				(($temp =  strlen($this->getPath()) - strlen(".xlsx")) >= 0  &&  stripos($this->getPath(), ".xlsx", $temp) !== false) ||
				(($temp =  strlen($this->getPath()) - strlen(".ods")) >= 0  &&  stripos($this->getPath(), ".ods", $temp) !== false);  // Case insensitive
	}
	
	public function getWidth() {
		if (!$this->esImatge() || !file_exists($this->getAbsolutePath())) return 0;
		try {
			$image_info = getimagesize($this->getAbsolutePath());
		} catch (\Exception $e) {
			return 0; 
		}
		return $image_info[0];
	}
	
	public function getHeight() {
		if (!$this->esImatge() || !file_exists($this->getAbsolutePath())) return 0;
		try {
			$image_info = getimagesize($this->getAbsolutePath());
		} catch (\Exception $e) {
			return 0; 
		}
		return $image_info[1];
	}
	
	public function getExtension()
	{
		$pos = strripos($this->getPath(), ".");  // última posició insensible case
		
		if ($pos !== false) return substr($this->getPath(), $pos);
		
		return '';
		 
	}
	
	
	public function upload($name = null)
	{
		// the file property can be empty if the field is not required
		if (null === $this->getFile()) {
			return;
		}
	
		// use the original file name here but you should
		// sanitize it at least to avoid any security issues
	

		$extension = $this->getValidExtension();
		
		if ($extension == null) return false;
		
		// 	set the path property to the filename where you've saved the file
		// Màxim 50 amb extensió. Mida de path  time 10 + _ + nom + .ext 5 => nom <= 50-16= 34
		
		if ($name == null) {
			$nameAjustat = $this->getFile()->getClientOriginalName();
		} else {
			$nameAjustat = $name;
		}
		$nameAjustat = substr($nameAjustat, 0, 33);
		$this->path = time() . "_". Funcions::netejarPath($nameAjustat) . "." . $extension;
		
		$this->getFile()->move($this->getUploadRootDir(), $this->path);

		// clean up the file property as you won't need it anymore
		$this->file = null;
			
		return true;
	}

	public function getValidExtension()
	{
		// move takes the target directory and then the
		// target filename to move to
		$extension = $this->getFile()->guessExtension(); 
		$extension = strtolower($extension);
		
		if ($extension == null ||  ($extension != "pdf" && 
									$extension != "png" && $extension != "jpg" && 
									$extension != "jpeg" && $extension != "gif" &&
									$extension != "doc" && $extension != "odt" && 
									$extension != "docx" && $extension != "pdf" &&
									$extension != "xsl" && $extension != "xlsxf" 
									)) {
			return null;
		} 
								
		return $extension;						
	} 

	public function getAbsolutePath()
	{
		return null === $this->path
		? null
		: $this->getUploadRootDir().$this->path;
	}
	
	public function getWebPath()
	{
		return null === $this->path
		? null
		: BaseController::ALIAS_FILES_FOLDER.BaseController::UPLOADS_FOLDER.$this->path;
	}
	
	protected function getUploadRootDir()
	{
		// the absolute directory path where uploaded
		// documents should be saved
		return __DIR__.BaseController::UPLOADS_RELPATH.BaseController::UPLOADS_FOLDER;
	}
	
    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
    	$this->id = $id;
    }
	
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set path
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set titol
     *
     * @param string $titol
     */
    public function setTitol($titol)
    {
        $this->titol = $titol;
    }

    /**
     * Get titol
     *
     * @return string 
     */
    public function getTitol()
    {
        return $this->titol;
    }
	
	/**
	 * @return boolean
	 */
	public function getImatge() {
		return $this->imatge;
	}

	/**
	 * @param boolean $imatge
	 */
	public function setImatge($imatge) {
		$this->imatge = $imatge;
	}
	
    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
    	$this->file = $file;
    }
    
    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
    	return $this->file;
    }
    
	/**
	 * @return EntityPersona
	 */
	public function getPersona() {
		return $this->persona;
	}

	/**
	 * @param EntityPersona $persona
	 */
	public function setPersona(EntityPersona $persona) {
		$this->persona = $persona;
	}
	
	/**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;
    }

    /**
     * Get dataentrada
     *
     * @return \DateTime 
     */
    public function getDataentrada()
    {
        return $this->dataentrada;
    }
}