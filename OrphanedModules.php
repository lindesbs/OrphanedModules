<?php


class OrphanedModules extends Backend
{
	public function __construct()
	{
		$this->import("Config");
		$this->import("Database");
		
		
		
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
