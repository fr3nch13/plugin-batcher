<?php
/**
 * The Batcher plugin.
 * To use this component view the README.md file.
 */

class BatcherComponent extends Component 
{
	public $Controller = null;
	public $Model = null;
	
	public $objectName = false;
	public $objectsName = false;

	public function initialize(Controller $Controller) 
	{
		$this->Controller = & $Controller;
		$this->Model = & $this->Controller->{$this->Controller->modelClass};
	}
	
	//// Methods that are used as Controller actions
	//// basically they just get a wrapper on the Controller
	
	//// STEP 1
	// Allows the user to add a csv/excel file, or a csv dump into a text area
	public function batcher_step1()
	{
		$this->Controller->set('objectName', $this->objectName);
		$this->Controller->set('objectsName', $this->objectsName);
		
		if($this->Controller->request->is('post'))
		{
			if($this->Model->Batcher_parseData($this->Controller->request->data))
			{
				$this->Controller->Session->setFlash(__('Your data was successfully parsed.'));
				$this->Controller->bypassReferer = true;
				return $this->Controller->redirect(array('action' => 'batcher_step2'));
			}
			else
			{
				$this->Controller->Session->setFlash(__('The %s could not be parsed. Cause: %s', $this->objectsName, $this->Model->modelError));
			}
		}
		else
		{
			$this->Model->Batcher_clearCache();
		}
		
		return $this->Controller->render('Batcher.Elements/batcher_step1');
	}
	
	public function batcher_step2()
	{
		// make sure we went through step one
		if(!$this->Model->Batcher_getHeaders())
		{
			$this->Controller->Session->setFlash(__('Please begin at the first step.'));
			$this->Controller->bypassReferer = true;
			return $this->Controller->redirect(array('action' => 'batcher_step1'));
		}
		
		$this->Controller->set('objectName', $this->objectName);
		$this->Controller->set('objectsName', $this->objectsName);
		
		if($this->Controller->request->is('post'))
		{
			$records_count = $this->Model->Batcher_mapData($this->Controller->request->data);
			if($records_count !== false)
			{
				$this->Controller->Session->setFlash(__('Your data was successfully mapped. We found %s records.', $records_count));
				
				if(isset($this->Controller->request->data['review']))
				{
					if($this->Controller->request->data['review'] == 'save')
					{
						if($saveCnt = $this->Model->Batcher_saveData())
						{
							$this->Controller->Session->setFlash(__('Your data was successfully mapped and saved. We found %s records. %s saved.', $records_count, $saveCnt));
							$this->Controller->bypassReferer = true;
							return $this->Controller->redirect(array('action' => 'batcher_step4'));
						}
					}
				}
				
				$this->Controller->bypassReferer = true;
				return $this->Controller->redirect(array('action' => 'batcher_step3'));
			}
			else
			{
				$this->Controller->Session->setFlash(__('The %s could not be mapped. Cause: %s', $this->objectsName, $this->Model->modelError));
			}
		}
		
		$this->Model->validate = array();
		$this->Controller->set('fieldMap', $this->Model->Batcher_getFieldMap());
		$this->Controller->set('batcherHeaders', $this->Model->Batcher_getHeaders());
		
		return $this->Controller->render('Batcher.Elements/batcher_step2');
	}
	
	public function batcher_step3()
	{
		// make sure we went through step one
		if(!$this->Model->Batcher_getHeaders())
		{
			$this->Controller->Session->setFlash(__('Please begin at the first step.'));
			$this->Controller->bypassReferer = true;
			return $this->Controller->redirect(array('action' => 'batcher_step1'));
		}
		
		// make sure we went through step two
		if(!$records = $this->Model->Batcher_getMappedData())
		{
			$this->Controller->Session->setFlash(__('Please return the the second step'));
			$this->Controller->bypassReferer = true;
			return $this->Controller->redirect(array('action' => 'batcher_step2'));
		}
		// find the existing records if they have one
		foreach($records as $i => $record)
		{
			$records[$i]['existing'] = array();
			
			if(isset($record[$this->Model->alias][$this->Model->primaryKey]))
			{
				$records[$i]['existing'] = $this->Model->find('first', array(
					'recursive' => 0,
					'conditions' => array(
						$this->Model->alias.'.'.$this->Model->primaryKey => $record[$this->Model->alias][$this->Model->primaryKey],
					),
				));
			}
		}
		
		$this->Controller->set('objectName', $this->objectName);
		$this->Controller->set('objectsName', $this->objectsName);
		
		if($this->Controller->request->is('post'))
		{
			if($saveCnt = $this->Model->Batcher_saveData($this->Controller->request->data))
			{
				$this->Controller->Session->setFlash(__('Your data was successfully saved. %s records were added/updated.', $saveCnt));
				$this->Controller->bypassReferer = true;
				return $this->Controller->redirect(array('action' => 'batcher_step4'));
			}
			else
			{
				$this->Controller->Session->setFlash(__('Some of the %s could not be saved. Cause: %s', $this->objectsName, $this->Model->modelError));
			}
		}
		
		$this->Controller->set('records', $records);
		$this->Controller->set('fieldMap', $this->Model->Batcher_getFieldMapRaw());
		$this->Controller->set('modelAlias', $this->Model->alias);
		$this->Controller->set('primaryKey', $this->Model->primaryKey);
		
		return $this->Controller->render('Batcher.Elements/batcher_step3');
	}
}