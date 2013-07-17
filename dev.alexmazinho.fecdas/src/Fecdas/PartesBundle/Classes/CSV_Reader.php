<?php
namespace Fecdas\PartesBundle\Classes;
class CSV_Reader {
	protected $_handle = null; //file handle
	protected $_init = false; //have we initalized ourselves
	protected $_csv = null; //  CSV file
	protected $_layout = array(); //array that defines layout of CSV
	protected $_row = null; //current row of the CSV we are on
	
	/**
	 * Set our CSV Layout, what fields correspond to what
	 * @param array $layout
	 */
	public function setLayout(array $layout){
		$this->_layout = $layout;
	}
	
	/**
	 * Return our CSV Layout
	 * @return array
	 */
	public function getLayout(){
		return $this->_layout;
	}
	
	/**
	 *
	 * @param string $csv
	 */
	public function setCsv($csv){
		$this->_csv = $csv;
	}
	
	/**
	 * Returns the current row
	 * @return array
	 */
	public function getRow(){
		return $this->_row;
	}
	
	/**
	 * Read the layout from the values in the first row, i.e,
	 * first_name, last_name,
	 */
	public function readLayoutFromFirstRow(){
		$this->_init(); 
		$this->_layout = array(); //reset for multiple
		$line = fgetcsv($this->_handle, 4096, ';');
		if(!$line){
			fclose($this->_handle);
			throw new \Exception('Fitxer invàlid, manca la capcelera');
		}
	
		foreach($line as $key){
			$this->_layout[] = strtolower($key);
		}
	}
	
	/**
	 * Initialize CSV, open file and get it ready for reading
	 * @throws Exception
	 */
	protected function _init(){
		ini_set('auto_detect_line_endings', 1);
		$this->_init = true;
		$this->_handle = fopen($this->_csv, "r");
		if(!$this->_handle){
			throw new \Exception('No s\'ha pogut obrir el fitxer: ' . $this->_csv);
		}
	}
	
	/**
	 *
	 */
	public function process(){
		if(!$this->_init){
			$this->_init();
		}
		$line = fgetcsv($this->_handle, 4096, ';');
		if(!$line){
			fclose($this->_handle);
			return false;
		}
		$i = 0;
		$row = array();
		foreach($this->_layout as $key){
			if(isset($line[$i])){
				$row[$key] = $line[$i];
			} else {
				$row[$key] = NULL;
			}
			$i++;
		}
		$this->_row = $row;
		return true;
	}
	
	
}
