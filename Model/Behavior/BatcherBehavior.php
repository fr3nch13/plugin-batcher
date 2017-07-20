<?php

/**
 * Requirements for the associated Models (belongsTo):
 *		It has to have a 'slug' field, and a checkAdd() method that will create a record, 
 *		and slugify the primary key in order to be used with this plugin
 */

class BatcherBehavior extends ModelBehavior 
{
	public $settings = array();
	
	private $_defaults = array(
		'buildAssocFieldMap' => true,
	);
	
	public $fieldMap = array();
	
	public function setup(Model $Model, $config = array()) 
	{
		// merge the default settings with the model specific settings
		$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
	}
	
	public function Batcher_parseData(Model $Model, $data = array())
	{
		$Model->modelError = false;
		if(!isset($data[$Model->alias]))
		{
			$Model->modelError = __('Unable to find the Dump Data, or the EXCEL/CSV File.');
			return false;
		}
		
		$data = $data[$Model->alias];
		
		$content = false;
		$_headers = array();
		$headers = array();
		$headers_map = array();
		$values = array();
		
		$is_csv = false;
		$is_excel = false;
		// import the content from the file
		if(isset($data['file']))
		{
			if($data['file']['error'] == 0)
			{
				// make sure it is a csv, or excel file
				if(preg_match('/openxmlformats/', $data['file']['type']))
					$is_excel = true;
				elseif(preg_match('/text\/csv/', $data['file']['type']))
					$is_csv = true;
			}
			
			if($is_csv)
			{
				$content = file_get_contents($data['file']['tmp_name']);
				$content = trim($content);
				$content = preg_split('/\n|\r\n?|\r/', $content);
				// get the headers
				$_headers = str_getcsv(array_shift($content));
			}
			elseif($is_excel)
			{
				// make sure the behavior is loaded
				if(!$Model->Behaviors->loaded('PhpExcel.PhpExcel'))
					$Model->Behaviors->load('PhpExcel.PhpExcel');
				
				if($results = $Model->Excel_fileToArray($data['file']['tmp_name']))
				{
					// the first row should have the slugged name of the headers
					$_headers = array_keys($results[0]);
					$headers = array();
					foreach($_headers as $i => $header)
					{
						$headers[$header] = Inflector::humanize($header);
					}
		
					$this->Batcher_setHeaders($Model, $headers);
					$this->Batcher_setValues($Model, $results);
		
					return true;
				}
			}
		}
		
		// otherwise import it from the dump
		if(!$_headers and isset($data['dump']))
		{
			$content = $data['dump'];
			$content = trim($content);
			$content = explode("\n", $content);
			// get the headers
			$_headers = str_getcsv(array_shift($content));
		}
		
		if(!$_headers)
		{
			$Model->modelError = __('Unable to read the contents of the Dump Data, or the EXCEL/CSV File.');
			return false;
		}
		
		foreach($_headers as $i => $header)
		{
			$header = trim($header);
			$header_key = strtolower(Inflector::slug($header));
			$header_value = Inflector::humanize($header_key);
			if(!$header_key) unset($headers[$i]);
			$headers[$header_key] = $header_value;
			
			// the first entry in the values array is the header map
			// this maps the position to the header key
			$headers_map[$i] = $header_key;
		}
		
		// get the values mapped properly
		$i = 1;
		foreach($content as $i => $line)
		{
			// see if we have an empty line and filter it out
			$line_test = preg_replace('/,\s*/', '', $line);
			if(!trim($line_test)) continue;
			
			$_values = str_getcsv($line);
			
			foreach($_values as $pos => $value)
			{
				if(isset($headers_map[$pos]))
					$values[$i][$headers_map[$pos]] = $value;
			}
		}
		
		if(!$headers)
		{
			$Model->modelError = __('Unable to read the headers of either the Dump or the File.');
			return false;
		}
		
		if(!$content)
		{
			$Model->modelError = __('Unable to read the data of either the Dump or the File.');
			return false;
		}
		
		$this->Batcher_setHeaders($Model, $headers);
		$this->Batcher_setValues($Model, $values);
		
		return true;
	}
	
	public function Batcher_mapData(Model $Model, $request_data = array())
	{
		$Model->modelError = false;
		$values = $this->Batcher_getValues($Model);
		$fieldMap = $this->Batcher_getFieldMapRaw($Model);
		
		if(!$values)
		{
			$Model->modelError = __('No known rows could be recovered, You may need to start over.');
			return false;
		}
		// remove any fields that we aren't mapping
		$request_data = Hash::flatten($request_data);
		foreach($request_data as $k => $v)
		{
			if(!trim($v))
			{
				unset($request_data[$k]);
				if(isset($compare[$k]))
					unset($compare[$k]);
			}
		}
		
		$saveManyData = array();
		
		foreach($values as $i => $record)
		{
			$thisSaveData = array();
			
			foreach($request_data as $k => $record_k)
			{
				if(isset($record[$record_k]))
				{
					$thisSaveData[$k] = trim($record[$record_k]);
					
					// see if it has some mapping setting
					if(isset($fieldMap[$k]['type']) and $fieldMap[$k]['type'] == 'match' and isset($fieldMap[$k]['options']))
					{
						$match_slug = Inflector::slug(strtolower($thisSaveData[$k]));
						if(isset($fieldMap[$k]['options'][$match_slug]))
							$thisSaveData[$k] = $fieldMap[$k]['options'][$match_slug];
						elseif(isset($fieldMap[$k]['default']))
							$thisSaveData[$k] = $fieldMap[$k]['default'];
					}
					
					if(isset($fieldMap[$k]['unique']) and $fieldMap[$k]['unique'])
					{
						if(!isset($thisSaveData[$Model->alias.'.'.$Model->primaryKey]))
						{
							if($id = $Model->field($Model->primaryKey, array($k => $thisSaveData[$k])))
								$thisSaveData[$Model->alias.'.'.$Model->primaryKey] = $id;
						}
					}
				}
			}
			
			$emptyCount = 0;
			foreach($thisSaveData as $k => $v)
			{
				if(!trim($v))
					$emptyCount++;
			}
			
			if($emptyCount == count($thisSaveData))
			{
				continue;
			}
			
			// we made it here, so this record is ready to be put in the database
			$saveManyData[] = Hash::expand($thisSaveData);
			
		}
		
		$this->Batcher_setMappedData($Model, $saveManyData);
		
		$return = count($saveManyData);
		unset($saveManyData);
		
		return $return;
	}
	
	public function Batcher_saveData(Model $Model, $data = array())
	{
		if(!$data)
		{
			$data = $this->Batcher_getMappedData($Model);
		}
		
		$fieldMap = $this->Batcher_getFieldMapRaw($Model);
		
		$fixMes = array();
		$addedCnt = 0;
		$assocMap = false;
		foreach($data as $i => $record)
		{
			if(isset($record[$Model->alias][$Model->primaryKey]))
			{
				$Model->id = $record[$Model->alias][$Model->primaryKey];
				
				// if this is the only value left for this record, continue to the next
				if(count($record[$Model->alias]) == 1)
				{
					unset($data[$i]);
					continue;
				}
			}
			else
			{
				$Model->create();
			}
			
			$record = Hash::flatten($record);
			// find the associated models' record and assign the proper unique id to it.
			foreach($fieldMap as $fieldName => $fieldSettings)
			{
				if(!isset($record[$fieldName]))
					continue;
				
				if(!$record[$fieldName])
					continue;
				
				if(!isset($fieldSettings['type']))
					continue;
				
				if($fieldSettings['type'] != 'match')
					continue;
				
				$recordFieldValue = $record[$fieldName];
				
				// make sure this selected exists in the database
				// and set it to the actual foreign key
				$record[$fieldName] = $Model->{$fieldSettings['modelAlias']}->checkAdd($recordFieldValue);
			}
			
			$record = Hash::expand($record);
			
			$Model->set($record);
			if(!$Model->validates())
			{
				$fixMes[$i] = $record;
				$fixMes[$i]['errors'] = $Model->validationErrors;
				continue;
			}
			
			if(!$Model->save($record))
			{
				$fixMes[$i] = $record;
				$fixMes[$i]['errors'] = $Model->validationErrors;
				continue;
			}
			
			// remove it from the records array.
			unset($data[$i]);
			
			$addedCnt++;
		}
		
		$fixMes = array_merge($fixMes, $data);
		
		if($fixMes)
		{
			$this->Batcher_setMappedData($Model, $fixMes);
		}
		// all was good, remove all of the cache
		else
		{
			$this->Batcher_clearCache($Model);
		}
		
		return $addedCnt;
	}
	
	public function Batcher_getFieldMapRaw(Model $Model)
	{
		$fieldMap = $this->settings[$Model->alias]['fieldMap'];
		
		if($this->settings[$Model->alias]['buildAssocFieldMap'])
		{
			foreach($Model->belongsTo as $assocModelAlias => $assocModelDetails)
			{
				$foreignKey = $assocModelDetails['foreignKey'];
				if(isset($fieldMap[$Model->alias.'.'.$foreignKey]))
					continue;
				
				if(!isset($assocModelDetails['plugin_batcher']))
					continue;
				
				if(!$assocModelDetails['plugin_batcher'])
					continue;
				
				if(!method_exists($Model->{$assocModelAlias}, 'checkAdd'))
					continue;
				
				if(!is_array($assocModelDetails['plugin_batcher']))
					$assocModelDetails['plugin_batcher'] = array();
				
				if(!isset($assocModelDetails['plugin_batcher']['label']))
					$assocModelDetails['plugin_batcher']['label'] = Inflector::humanize(Inflector::underscore($assocModelAlias));
					
				if(!isset($assocModelDetails['plugin_batcher']['field']))
					$assocModelDetails['plugin_batcher']['field'] = $Model->{$assocModelAlias}->displayField;
					
				if(!isset($assocModelDetails['plugin_batcher']['modelAlias']))
					$assocModelDetails['plugin_batcher']['modelAlias'] = $Model->{$assocModelAlias}->alias;
					
				if(!isset($assocModelDetails['plugin_batcher']['foreignKey']))
					$assocModelDetails['plugin_batcher']['foreignKey'] = $assocModelDetails['foreignKey'];
				
				$assocModelDetails['plugin_batcher']['type'] = 'match';
				
				$assocModelDetails['plugin_batcher']['options'] = $Model->{$assocModelAlias}->find('list', array(
					'order' => array($Model->{$assocModelAlias}->displayField => 'ASC'),
					'fields' => array($Model->{$assocModelAlias}->alias.'.slug', $Model->{$assocModelAlias}->alias.'.'.$Model->{$assocModelAlias}->displayField),
				));
					
				if(!isset($assocModelDetails['plugin_batcher']['preserve_options']))
					$assocModelDetails['plugin_batcher']['preserve_options'] = true;
				
				$fieldMap[$Model->alias.'.'.$foreignKey] = $assocModelDetails['plugin_batcher'];
			}
		}
		
		$this->settings[$Model->alias]['fieldMap'] = $fieldMap;
		
		// if the data has been mapped, only return the mapped fields
		if($fieldMap and $mappedData = $this->Batcher_getMappedData($Model))
		{
			$mappedData = array_pop($mappedData);
			$mappedData = Hash::flatten($mappedData);
			
			foreach($fieldMap as $k => $v)
			{
				if(!isset($mappedData[$k]))
					unset($fieldMap[$k]);
			}
		}
		
		return $fieldMap;
	}
	
	public function Batcher_getFieldMap(Model $Model)
	{
		$fieldMap = $this->Batcher_getFieldMapRaw($Model);
		return $fieldMap;
		
		/*
		$out = array();
		foreach($fieldMap as $k => $v)
		{
			if(is_array($v) and isset($v['label']))
				$v = $v['label'];
			
			$out[$k] = $v;
		}
		return $out;
		*/
	}
	
	public function Batcher_setHeaders(Model $Model, $headers = array())
	{
		return Cache::write('Batcher_headers', $headers);
	}
	
	public function Batcher_getHeaders(Model $Model)
	{
		return Cache::read('Batcher_headers');
	}
	
	public function Batcher_deleteHeaders(Model $Model)
	{
		return Cache::delete('Batcher_headers');
	}
	
	public function Batcher_setValues(Model $Model, $values = array())
	{
		return Cache::write('Batcher_values', $values);
	}
	
	public function Batcher_getValues(Model $Model)
	{
		return Cache::read('Batcher_values');
	}
	
	public function Batcher_deleteValues(Model $Model)
	{
		return Cache::delete('Batcher_values');
	}
	
	public function Batcher_setMappedData(Model $Model, $data = array())
	{
		return Cache::write('Batcher_mappedData', $data);
	}
	
	public function Batcher_getMappedData(Model $Model)
	{
		return Cache::read('Batcher_mappedData');
	}
	
	public function Batcher_deleteMappedData(Model $Model, $data = array())
	{
		return Cache::delete('Batcher_mappedData');
	}
	
	public function Batcher_clearCache(Model $Model)
	{
		$this->Batcher_deleteHeaders($Model);
		$this->Batcher_deleteValues($Model);
		$this->Batcher_deleteMappedData($Model);
	}
}