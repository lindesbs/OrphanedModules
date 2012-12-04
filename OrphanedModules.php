<?php


class OrphanedModules extends Backend
{
	public function __construct()
	{
		$this->import("Config");
		$this->import("Database");
		
		
		
	}
	
	public function checkTemplates()
	{
		$arrTemplates = array();
		$arrTemplatesList = array();
		
		$arrOwnTemplates = scan(TL_ROOT."/templates");
		
		$arrAllowedExtension = array("tpl","html5","xhtml");
		
		// search pathes
		foreach ($arrOwnTemplates as $file)
		{
			$arrBasename = pathinfo($file);
			if (in_array($arrBasename['extension'],$arrAllowedExtension))
			{
				$arrTemplates['ownTemplates'][$arrBasename['filename']][$arrBasename['extension']]['filename'] = "/templates/".$file;
				
				$arrTemplatesList[$arrBasename['filename']] = $arrBasename['filename'];
			}
		}
		
		foreach ($this->Config->getActiveModules() as $module)
		{
			$strTemplatePath = sprintf(TL_ROOT."/system/modules/%s/templates",$module);
			
			if (file_exists($strTemplatePath))
			{
				$arrModuleTemplates = scan($strTemplatePath);
				
				foreach ($arrModuleTemplates as $file)
				{
					$arrBasename = pathinfo($file);
					if (in_array($arrBasename['extension'],$arrAllowedExtension))
					{
						$arrTemplates['moduleTemplates'][$arrBasename['filename']][$arrBasename['extension']]['filename'] = sprintf("/system/modules/%s/templates/%s",$module,$file);
						$arrTemplatesList[$arrBasename['filename']] = $arrBasename['filename'];
					}
				}
				
			}
		}
		
		unset($arrTemplatesList['form']);
		unset($arrTemplatesList['subpalettes']);
		
		
		
		// serach database
		
		$arrAllTables = $this->Database->listTables();
		
		$arrDCAFields = array();
		$arrDCAFieldsOnly = array();
		$arrDisallowedDCAFields = array('checkbox','date','radio','inputUnit','checkboxWizard','moduleWizard','imageSize','radioTable','pageTree','trbl','timePeriod');
		
		// fetch all inputTypes of extensions which could contain readable data i.e. Templates
		foreach ($arrAllTables as $table)
		{
			$arrFieldsConfig = $this->Database->listFields($table);
				
			$this->loadDataContainer($table);
				
			$arrDCATable = $GLOBALS['TL_DCA'][$table]['fields'];
			
			
			if (is_array($arrDCATable))
			{
				foreach ($arrDCATable as $dcaFieldKey=>$dcaField)
				{
					if (array_key_exists('inputType',$dcaField))
					{
						if ((!in_array($dcaField['inputType'],$arrDisallowedDCAFields)) && ($dcaField['eval']['rgxp']!='time'))
						{
							$arrDCAFields[$table][$dcaField['inputType']][] = $dcaFieldKey;
							$arrDCAFieldsOnly[$table][$dcaFieldKey] = $dcaFieldKey;
						}
					}
				}
			}
		}
			
			
		$arrIgnoreTables = array('tl_style');
			
		foreach ($arrTemplatesList as $template)
		{
			foreach ($arrAllTables as $table)
			{
				if (in_array($table,$arrIgnoreTables))
					continue;
					
					
				$arrFieldsConfig = $this->Database->listFields($table);
				
				
				$arrFields = array();
				foreach ($arrFieldsConfig as $field)
				{
					$arrFields[$field['name']] = $field['name'];
				}
				
				if (array_key_exists("id",$arrFields))
				{
					unset($arrFields['id']);
					unset($arrFields['pid']);
					unset($arrFields['tstamp']);
					unset($arrFields['sorting']);
					
					unset($arrFields['ref_table']);				
					unset($arrFields['author']);
					unset($arrFields['start']);
					unset($arrFields['stop']);
					
					unset($arrFields['PRIMARY']);
								
					$arrFieldSQL = array();
					
					if (!is_array($arrDCAFieldsOnly[$table]))
						continue;
						
						
					$arrBoth = array_intersect_assoc($arrDCAFieldsOnly[$table],$arrFields);
					
					
					foreach ($arrBoth as $field)
					{
						//if ($field!=$template)
							$arrFieldSQL[] = $field." LIKE \"%".$template."\"";
					}
					
					$strSQL = "SELECT id FROM ".$table." WHERE (".implode(" OR ", $arrFieldSQL).")";					
					
					
					$objTemplateExists = $this->Database->query($strSQL);
					
					
					if ($objTemplateExists->numRows>0)
					{
						while ($objTemplateExists->next())
							$arrTemplates['database'][$template][$table][$objTemplateExists->id] = true;
					}
					
					unset($objTemplateExists);
					
				}
			}
		}
		return $arrTemplates;
	
	}
	
	public function checkModule($strModule)
	{
		
		if (!in_array($strModule, $this->Config->getActiveModules()))
		{
			throw new Exception("Module ".$strModule." not exists or enabled", 1);
			
		}
		
		$arrCheckGlobals = array(
			'BE_FFL' => false,
			'TL_PTY' => false,
			'TL_FFL' => false,
			'FE_MOD' => true,
			'TL_CTE' => true
		);
		
		$arrGlobalsBackup = array();
		foreach ($arrCheckGlobals as $check=>$removeArray)
		{		
			
			$arrGlobalsBackup[$check] = $GLOBALS[$check];
			unset($GLOBALS[$check]);
			
		}
		
		$arrCheck = array();
		
		$objRepInstall = $this->Database->prepare("SELECT * FROM tl_repository_installs WHERE extension=?")->limit(1)->execute($strModule);
		$arrCheck['repository']['install'] = (bool) ($objRepInstall->numRows==1);
		$arrCheck['repository']['data'] = $objRepInstall->fetchAssoc();	
		
		
		$strConfigFile = sprintf("/system/modules/%s/config/config.php",$strModule);
		if (file_exists(TL_ROOT.$strConfigFile))
		{
			include(TL_ROOT.$strConfigFile);
			
		}
		
		foreach ($arrCheckGlobals as $check=>$removeArray)
		{
			if (is_array($GLOBALS[$check]))
			{
				if ($removeArray)
				{
					if (!is_array($arrCheck[$check]['provided']))
					{
						$arrCheck[$check]['provided'] = array();
						
					}
					foreach ($GLOBALS[$check] as $modKey=>$modData)
					{
						
							$arrCheck[$check]['provided'] = array_merge($arrCheck[$check]['provided'],$modData);
					}
				}
				else
				{
					$arrCheck[$check]['provided'] = $GLOBALS[$check];
				}
			}
		}
		
		// restore old GLOBALS
		
		foreach ($arrCheckGlobals as $check=>$removeArray)
		{
			$GLOBALS[$check] = $arrGlobalsBackup[$check];
			
		}
		
		return $arrCheck;
		
	}

}
