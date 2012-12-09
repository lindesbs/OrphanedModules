<?php
if (!defined('TL_ROOT'))
	die('You can not access this file directly!');

/**
 * System configuration
 */
$GLOBALS['TL_DCA']['tl_check_my_extensions'] = array(
	// Config
	'config' => array(
		'dataContainer' => 'Memory',
		'closed' => true,
		'onload_callback' => array( array(
				'tl_check_my_extensions',
				'onload_callback'
			), ),
		'onsubmit_callback' => array( array(
				'tl_check_my_extensions',
				'onsubmit_callback'
			), ),
		'disableSubmit' => true,
		'dcMemory_show_callback' => array( array(
				'tl_check_my_extensions',
				'showAll'
			)),
		'dcMemory_showAll_callback' => array( array(
				'tl_check_my_extensions',
				'showAll'
			)),
	),

	// Palettes
	'palettes' => array('default' => '{areaConfig},checkModule,checkTypes'),

	// Fields
	'fields' => array(
		'checkModule' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_check_my_extensions']['checkModule'],
			'inputType' => 'checkbox',
			'options' => $this -> Config -> getActiveModules(),
			'eval' => array(
				'multiple' => true
			),
			'addSubmit' => true
		),
		
		'checkTypes' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_check_my_extensions']['checkTypes'],
			'inputType' => 'checkbox',
			'options' => array(
				'REPOSITORY' => 'Repository',
				'BE_FFL' => 'Backend formularfields',
				'BE_MOD' => 'Backend modules',
				'TL_FFL' => 'Frontend formularfields',
				'FE_MOD' => 'Frontend modules',
				'TL_PTY' => 'Page types',
				'TEMPLATES' => 'Template usage',
			),
			'eval' => array(
				'multiple' => true
			),
			'addSubmit' => true
		),
		
		'modulesConfigured' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_check_my_extensions']['checkModule'],
			'inputType' => 'statictext',
		),
	)
);

class tl_check_my_extensions extends Backend
{

	public function __construct()
	{
		$this -> import("Config");
		$this -> import("Session");
		$this -> import("Input");
		$this -> import("Database");
		
	}

	public function onload_callback(DataContainer $dc)
	{
	

		$sessionData = $this -> Session -> getData();

		$dc -> setDataArray($sessionData['checkmyextensions']);

		$arrGlobalBackup = array();
		$arrGlobals = array();

		$arrErrors = array();
		

		$objCheck = new OrphanedModules();
		
		
		$arrCheckModules = $sessionData['checkmyextensions']['checkModule'];
		
		if ((is_array($sessionData['checkmyextensions']["checkTypes"])) && (in_array("TEMPLATES", $sessionData['checkmyextensions']["checkTypes"])))
		{
		
		
			$arrBELinks = array(
				'tl_layout'	=> 'contao/main.php?do=themes&table=tl_layout&act=edit&id=%s',
				'tl_content'	=> 'contao/main.php?do=article&table=tl_content&act=edit&id=%s',
				'tl_module'	=> 'contao/main.php?do=themes&table=tl_module&act=edit&id=%s',
				'tl_page' => 'contao/main.php?do=page&act=edit&id=%s',
				
			);
		
			$strImageNonExist = '<td><img src="/system/themes/default/images/error.gif" width="16" height="16"></td>';
			$strImageExists = '<td><img src="/system/themes/default/images/help.gif" width="16" height="16"></td>';
		
			$arrTemplates = $objCheck->checkTemplates();
			
			$strOutput.="TEMPLATES";
			
			
			$strOutput.="<h3>ownTemplates</h3>";
			
			$strOutput.="<table>";
			$strOutput.="<tr>";
			$strOutput.='<th style="width:300px;">template</th>';
			$strOutput.='<th style="width:100px;">tpl</th>';
			$strOutput.='<th style="width:100px;">html5</th>';
			$strOutput.='<th style="width:100px;">xhtml</th>';
			$strOutput.="</tr>";
			
			if (is_array($arrTemplates['ownTemplates']))
			{
				foreach ($arrTemplates['ownTemplates'] as $templateName=>$templateData)
				{
					$strOutput.="<tr>";
					$strOutput.="<td>".$templateName;
					
					
					
					if (array_key_exists($templateName,$arrTemplates['database']))
					{
						$strOutput.="<ul><li>Database</li><ul>";
						
						foreach ($arrTemplates['database'][$templateName] as $keyDBTemplate=>$strDBTemplate)
						{
							$strOutput.=sprintf("<li><pre>%s</pre></li>",$keyDBTemplate);
							$strOutput.="<ul>";
							
							foreach ($strDBTemplate as $id=>$bla)
							{
							
								if (array_key_exists($keyDBTemplate,$arrBELinks))
								{
									$strLink = sprintf($arrBELinks[$keyDBTemplate],$id);
									$strOutput.=sprintf('<li>%s <a href="%s"><img src="/system/themes/default/images/edit.gif"></a></li>',$id,$strLink);
								}
								else
								{
									$strOutput.=sprintf('<li>%s</li>',$id);
								}
							}
							
							$strOutput.="</ul>";
						}
						
						$strOutput.="</ul></ul>";
					}
					
					$strOutput.="</td>";
					
					$strOutput.=array_key_exists("tpl",$templateData) ? $strImageExists : $strImageNonExist;
					$strOutput.=array_key_exists("html5",$templateData) ? $strImageExists : $strImageNonExist;
					$strOutput.=array_key_exists("xhtml",$templateData) ? $strImageExists : $strImageNonExist;
					
					$strOutput.="</tr>";	
				}
			}
			
			$strOutput.="</table>";
		
			$strOutput.="<h3>Module templates</h3>";
			
			$strOutput.='
			<style type="text/css">
<!--
			tr:nth-child(odd) {
  background-color: #eeeeee;
}
-->
</style>  ';
			
			$strOutput.="<table>";
			$strOutput.="<tr>";
			$strOutput.='<th style="width:300px;">template</th>';
			$strOutput.='<th style="width:100px;">tpl</th>';
			$strOutput.='<th style="width:100px;">html5</th>';
			$strOutput.='<th style="width:100px;">xhtml</th>';
			$strOutput.="</tr>";
			foreach ($arrTemplates['moduleTemplates'] as $templateName=>$templateData)
			{
			
				$strOutput.="<tr>";
				$strOutput.="<td><pre>".$templateName.'</pre>';
				
				
				if (array_key_exists($templateName,$arrTemplates['database']))
				{
					$strOutput.="<ul><li>Database</li><ul>";
					
					foreach ($arrTemplates['database'][$templateName] as $keyDBTemplate=>$strDBTemplate)
					{
						$strOutput.=sprintf("<li><pre>%s</pre></li>",$keyDBTemplate);
						$strOutput.="<ul>";
						
						foreach ($strDBTemplate as $id=>$bla)
						{
						
							if (array_key_exists($keyDBTemplate,$arrBELinks))
							{
								$strLink = sprintf($arrBELinks[$keyDBTemplate],$id);
								$strOutput.=sprintf('<li>%s <a href="%s"><img src="/system/themes/default/images/edit.gif"></a></li>',$id,$strLink);
							}
							else
							{
								$strOutput.=sprintf('<li>%s</li>',$id);
							}
						}
						
						$strOutput.="</ul>";
					}
					
					$strOutput.="</ul></ul>";
				}
				
				$strOutput.="</td>";
				$strOutput.=array_key_exists("tpl",$templateData) ? $strImageExists : $strImageNonExist;
				$strOutput.=array_key_exists("html5",$templateData) ? $strImageExists : $strImageNonExist;
				$strOutput.=array_key_exists("xhtml",$templateData) ? $strImageExists : $strImageNonExist;
				
				$strOutput.="</tr>";	
			}
			
			$strOutput.="</table>";
		
		
		
		}
		
		
		if (count($arrCheckModules)>0)
		{
		
			foreach ($arrCheckModules as $module)
			{
				$arrData = $objCheck->checkModule($module);
				
				$strOutput .= '<hr>';
				$strOutput .= '<ul>';
				$strOutput .= '<h2>'.$module.'</h2>';
				$strOutput .= '<ul>';
				
				$strOutput .= '<li>';
				//print_a($arrData);
				
				if ($arrData['repository']['install'])
				{
					$strOutput .= 'Installed via extension repository<br>';
					$strOutput .= '<pre>Install date      : '.$this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'],$arrData['repository']['data']['tstamp']).'</pre>';
					$strOutput .= '<pre>Installed version : '. Repository::formatVersion($arrData['repository']['data']['version']).' build '.$arrData['repository']['data']['build'].'</pre>';
				}
				else
				{
					$strOutput .= 'Not installed via extension repository';
				}
				$strOutput .= '</li>';
				
				
				if (array_key_exists("BE_FFL",$arrData))
				{
					$strOutput .= '<li>';
					$strOutput .= 'Backend formfields';
					$strOutput .= '</li>';
				}
				
				if (array_key_exists("TL_PTY",$arrData))
				{
					$strOutput .= '<li>';
					$strOutput .= 'Page types';
					$strOutput .= '<ul>';
					
					foreach ($arrData['TL_PTY']['provided'] as $ceKey=>$ce)
					{
						$strOutput .=  sprintf("<li>%s</li>",$ce);
					
						$objPageType = $this->Database->prepare("SELECT title, id FROM tl_page WHERE type=?")->execute($ceKey);
						
						if ($objPageType ->numRows>0)
						{
							$strOutput .= '<ul>';
							while ($objPageType ->next())
							{
								$strOutput .=  sprintf("<li>Link to ID %s ::  %s",$objPageType ->id,$objPageType ->title);
								$strOutput .= sprintf('<a href="contao/main.php?do=page&act=edit&id=%s"><img src="/system/themes/default/images/edit.gif"></a>',$objCEUsed ->id);
								$strOutput .= '</li>';
							}
							
							$strOutput .= '</ul>';
						}
					}
					
					$strOutput .= '</ul>';
					$strOutput .= '</li>';
				}
				
				if (array_key_exists("TL_FFL",$arrData))
				{
					$strOutput .= '<li>';
					$strOutput .= 'TL_FFL modules';
					$strOutput .= '</li>';
				}
				
				if (array_key_exists("FE_MOD",$arrData))
				{
					$strOutput .= '<li>';
					$strOutput .= 'Frontend modules';
					
					$strOutput .= '<ul>';
					foreach ($arrData['FE_MOD']['provided'] as $ceKey=>$ce)
					{
						$strOutput .=  sprintf("<li>%s</li>",$ce);
					
						$objCEUsed = $this->Database->prepare("SELECT name, id FROM tl_module WHERE type=?")->execute($ceKey);
						
						if ($objCEUsed ->numRows>0)
						{
							$strOutput .= '<ul>';
							while ($objCEUsed ->next())
							{
								$strOutput .=  sprintf("<li>Link to ID %s ::  %s",$objCEUsed ->id,$objCEUsed ->name);
								$strOutput .= sprintf('<a href="contao/main.php?do=themes&table=tl_module&act=edit&id=%s"><img src="/system/themes/default/images/edit.gif"></a>',$objCEUsed ->id);
								$strOutput .= '</li>';
							}
							
							$strOutput .= '</ul>';
						}
					}
					
					$strOutput .= '</ul>';
					$strOutput .= '</li>';
				}
				
				if (array_key_exists("TL_CTE",$arrData))
				{
					$strOutput .= '<li>';
					$strOutput .= 'Content elements';
					$strOutput .= '<ul>';
					foreach ($arrData['TL_CTE']['provided'] as $ceKey=>$ce)
					{
						$strOutput .=  sprintf("<li>%s</li>",$ce);
					
						$objCEUsed = $this->Database->prepare("SELECT id,headline FROM tl_content WHERE type=?")->execute($ceKey);
						
						if ($objCEUsed ->numRows>0)
						{
							$strOutput .= '<ul>';
							while ($objCEUsed ->next())
							{
								$strOutput .=  sprintf("<li>Link to ID %s",$objCEUsed ->id);
								
								$arrHeadline = deserialize($objCEUsed ->headline);
								if ($arrHeadline['value'])
									$strOutput .=  sprintf("<br>Headline : %s",$arrHeadline['value']);
								$strOutput .= sprintf('<a href="contao/main.php?do=article&table=tl_content&act=edit&id=%s"><img src="/system/themes/default/images/edit.gif"></a>',$objCEUsed ->id);
								$strOutput .= '</li>';
							}
							
							$strOutput .= '</ul>';
						}
					}
					
					$strOutput .= '</ul>';
					$strOutput .= '</li>';
				}
				
				$strOutput .= '</ul>';
				$strOutput .= '</ul>';
				
				
			}
			
			
			$GLOBALS['TL_DCA']['tl_check_my_extensions']['palettes']['default'] .=";{areaChekInfo},modulesConfigured";
			$dc->setData("modulesConfigured",$strOutput);
		}
		
		
		
	}

	public function onsubmit_callback(DataContainer $dc)
	{
		$sessionData = $this -> Session -> getData();
		$sessionData['checkmyextensions'] = $dc -> getDataArray();
		$this -> Session -> setData($sessionData);

	}

	public function showAll($dc, $strReturn)
	{
		return $strReturn . $dc -> edit();
	}

}
?>