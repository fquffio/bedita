<?
/**
 * 
 * Estende la classe AppModel.
 * 
 * PHP versions 4 
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * @filesource
 * @copyright		Copyright (c) 2006
 * @link			
 * @package			
 * @subpackage		
 * @since			
 * @version			
 * @modifiedby		
 * @lastmodified	
 * @license			
 * @author 		giangi giangi@qwerg.com			
 * 
 * Ridefinisce la funzione save() per i model che rappresentano gli oggetti.
 * Permette il setup automatico del campo object_type_id.
 * Implementa la funzione di clonazione di un oggetto
 * 
*/

class BEAppObjectModel extends BEAppModel {
	
	/**
 	* Sovrascrive e poi chiama la funzione del parent xch� deve settare 
 	* ove necessario, il tipo di oggetto da salvare
 	*/
	function save($data = null, $validate = true, $fieldList = array()) {
		$conf = Configure::getInstance() ;		
		
		if(isset($data['Object']) && !isset($data['Object']['object_type_id'])) {
			$data['Object']['object_type_id'] = $conf->objectTypes[strtolower($this->name)] ;
		} else if(!isset($data['object_type_id'])) {
			$data['object_type_id'] = $conf->objectTypes[strtolower($this->name)] ;
		}

		// Se c'e' la chiave primaria vuota la toglie
		if(isset($data[$this->primaryKey]) && empty($data[$this->primaryKey])) {
			unset($data[$this->primaryKey]) ;		
		}
		
		return parent::save($data, $validate, $fieldList) ;
	}

	/**
	 * Un oggetto crea un clone di se stesso.
	 *
	 */
	function __clone() {
		$className 	= get_class($this) ;
		$_this		= new $className() ;
		
		// Se non e' settato nessun oggetto, esce
		if(@empty($this->{$this->primaryKey})) {
			$this->copyPropertiesFromObj($_this);
			
			return ;
		}
		
		$i = 0 ;
		
		// Preleva i dati dell'oggetto
		$data = $this->findById($this->{$this->primaryKey}) ;
		
		// Formatta i dati
		$_this->_formatDataForClone($data, $this) ;
		
		/**
		 * NOTA.
		 * Se il primo campo dati non e' un array, il salvataggio funziona correttamente.
		 * funzioni model:save -->  model::set --> model::countDim
		 */
		$tmp = array("title" => $data["title"]) ;
		$data = am($tmp, $data) ;
			
		// Salva i dati, in caso d'errore, esce
		if(!$_this->save($data)) {
			$this->copyPropertiesFromObj($_this);
			
			return ;
		}
		
		// copia i permessi
		if(!isset($this->Permission)) {
			if(!class_exists('Permission')){
				loadModel('Permission');
			}
			$this->Permission = new Permission() ;
		}
		$this->Permission->clonePermissions($this->{$this->primaryKey}, $this->{$_this->primaryKey}) ;
		
		// copia le proprieta' oggetto clonato		
		$this->copyPropertiesFromObj($_this);
	}
	
	private function copyPropertiesFromObj(&$obj) {
		foreach ($obj as $key => $item) {
			$this->{$key} = $item ;
		}
	}
	
	/**
	 * Formatta i dati per la creazione di un clone, ogni tipo
	 * di oggetto esegue operazioni specifiche richiamando, sempre
	 * parent::_formatDataForClone.
	 *
	 * @param array $data		Dati da formattare
	 * @param object $source	Oggetto sorgente
	 */
	protected function _formatDataForClone(&$data, $source = null) {
		$data['fundo'] = 0 ;
		
		$labels = array($this->primaryKey, 'user_created', 'user_modified', 'Index') ;
		foreach($labels as $label) unset($data[$label]) ; 
		
		/**
		 * Gestione di dati specifici a diversi tipi di oggetti.
	 	*/
		for($i=0; isset($data['calendars']) && $i < count($data['calendars']) ; $i++) {
			unset($data['calendars'][$i]['id']) ;
		}
		
		for($i=0; isset($data['links']) && $i < count($data['links']) ; $i++) {
			unset($data['links'][$i]['id']) ;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Classe utilizzata per gestire la clonazione di oggetti che contengono altri oggetti
 * e son inseriti nell'albero dei contenuti
 *
 */
class BEAppCollectionModel extends BEAppObjectModel {
	/**
	 * Se true esegue la clonazione anche dei figli, altrimenti no
	 *
	 * @var boolean
	 */
	var $recursionClone = true ;
	
	/**
	 * ID del'oggetto da clonare
	 *
	 * @var integer
	 */
	var $oldID ;
	
	
	/**
	 * Preleva i figli di cui id e' radice.
	 * Se l'userid e' presente, preleva solo gli oggetti di cui ha i permessi, se '' � un utente anonimo,
	 * altrimenti li prende tutti.
	 * Si possono selezionare i tipi di oggetti da prelevare.
	 * 
	 * @param integer $id		id della radice da selezionare. 
	 * @param string $userid	l'utente che accede. Se null: non controlla i permessi. Se '': utente guest.
	 * 							Default: non verifica i permessi.
	 * @param string $status	Prende oggetti solo con lo status passato
	 * @param array $filter		definisce i tipi gli oggetti da prelevare. Es.:
	 * 							1 | 3 | 22 ... aree, sezioni, documenti.
	 * 							Default: tutti.
	 * @param integer $page		Numero di pagina da selezionare
	 * @param integer $dim		Dimensione della pagina
	 */
	function getChildren($id = null, $userid = null, $status = null, $filter = 0xFF, $page = 1, $dim = 100000) {
		if(!class_exists('Tree')) loadModel('Tree');
		$tree 	=& new Tree();
		
		$tree->setRoot($this->id);
		return $tree->getChildren($id, $userid, $status, $filter, $page, $dim)  ;
	}
	
	
	function __clone() {
		if(!class_exists('Tree')) loadModel('Tree');
		
		$oldID 		= $this->id ;
		$recursion 	= (isset($this->recursionClone)) ? $this->recursionClone : true ;
		
		// Clona l'oggetto
		parent::__clone();
		$this->oldID 			= $oldID ;
		$this->recursionClone 	= $recursion ;
		
		// Clona ricorsivamente i figli
		if($this->recursionClone) {
			$this->insertChildrenClone() ;
		}
	}
	
	protected function insertChildrenClone() {
		$conf  	= Configure::getInstance() ;
		
		$tree 	=& new Tree();
		
		// Preleva l'elenco dei contenuti
		if(!($queries = $tree->getAll($this->oldID))) throw new BEditaErrorCloneException("BEAppCollectionModel::getItems") ;

		// crea le nuove associazioni
		for ($i=0; $i < count($queries[0]["children"]) ; $i++) {
			$className	= $conf->objectTypeModels[$queries[0]["children"][$i]['object_type_id']] ;
			$item 		= new $className() ;
			$item->id	= $queries[0]['children'][$i]['id'] ;
				
			$clone = clone $item ;
				
			if(!$tree->appendChild($clone->id, $this->id)) {
				throw new BEditaErrorCloneException("BEAppCollectionModel::appendChild") ;
			}
		}
		
	}
}

////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * 
 * Eccezione sollevata quando i tenta di clonare un oggetto che non si puo' clonare
 * 
 */
class BEditaCloneModelException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($model, $code  = 0) {
        
        // make sure everything is assigned properly
        parent::__construct($model->name, $code);
    }
}

/**
 * 
 * Eccezione sollevata quando avviene un errore nella clonazione
 * 
 */
class BEditaErrorCloneException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($msg, $code  = 0) {
        
        // make sure everything is assigned properly
        parent::__construct($model->name, $code);
    }
}

?>