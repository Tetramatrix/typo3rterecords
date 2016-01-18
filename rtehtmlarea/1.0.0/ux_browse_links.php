<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Chi Hoang (chibox@gmail.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'RTE Records' for the 'ch_rterecords' extension.
 *
 * @author	Chi Hoang <info@chihoang.de>
 */

class TBE_browser_recordListIR extends localRecordList {

	var $script='browse_links.php';
	var $tableName = '';
    
	/**
	 * Creates the URL to this script, including all relevant GPvars
	 * Fixed GPvars are id, table, imagemode, returlUrl, search_field, search_levels and showLimit
	 * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
	 *
	 * @param	string		Alternative id value. Enter blank string for the current id ($this->id)
	 * @param	string		Tablename to display. Enter "-1" for the current table.
	 * @param	string		Commalist of fields NOT to include ("sortField" or "sortRev")
	 * @return	string		URL
	 */
	function listURL($altId='',$table=-1,$exclList='')	{

		if ($table==-1 && $this->table=='') {
			$tablesArray = array();
			foreach ($BE_USER->userTS['linkRecord.'] as $def)
					$tablesArray[] = $def['table'];
			$table = implode(',', $tablesArray);
		}

		return $this->script.
			'?id='.(strcmp($altId,'')?$altId:$this->id).
			'&table='.rawurlencode($table==-1?$this->table==''?$this->tableName:$this->table:$table).
			($this->thumbs?'&imagemode='.$this->thumbs:'').
			($this->returnUrl?'&returnUrl='.rawurlencode($this->returnUrl):'').
			($this->searchString?'&search_field='.rawurlencode($this->searchString):'').
			($this->searchLevels?'&search_levels='.rawurlencode($this->searchLevels):'').
			($this->showLimit?'&showLimit='.rawurlencode($this->showLimit):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortField')) && $this->sortField?'&sortField='.rawurlencode($this->sortField):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortRev')) && $this->sortRev?'&sortRev='.rawurlencode($this->sortRev):'').
            '&mode='.$this->i6lGParams['mode'].'&act='.$this->i6lGParams['act'].'&RTEtsConfigParams='.$this->i6lGParams['RTEtsConfigParams'].'&expandPage='.$this->i6lGParams['expandPage'].'&curUrlInfo='.$this->i6lGParams['curUrlInfo'];
			;
	}
    
                
	/**
	 * Traverses the table(s) to be listed and renders the output code for each:
	 * The HTML is accumulated in $this->HTMLcode
	 * Finishes off with a stopper-gif
	 *
	 * @return	void
	 */
	function generateList()	{
		global $TCA;

			// Traverse the TCA table array:
		reset($TCA);
		while (list($tableName)=each($TCA))	{

				// Checking if the table should be rendered:
			if ((!$this->table || $tableName==$this->table) && (!$this->tableList || t3lib_div::inList($this->tableList,$tableName)) && $GLOBALS['BE_USER']->check('tables_select',$tableName))	{		// Checks that we see only permitted/requested tables:

                		$this->tableName = $tableName;
                
					// Load full table definitions:
				t3lib_div::loadTCA($tableName);

					// iLimit is set depending on whether we're in single- or multi-table mode
				if ($this->table)	{
					$this->iLimit=(isset($TCA[$tableName]['interface']['maxSingleDBListItems'])?intval($TCA[$tableName]['interface']['maxSingleDBListItems']):$this->itemsLimitSingleTable);
				} else {
					$this->iLimit=(isset($TCA[$tableName]['interface']['maxDBListItems'])?intval($TCA[$tableName]['interface']['maxDBListItems']):$this->itemsLimitPerTable);
				}
				if ($this->showLimit)	$this->iLimit = $this->showLimit;

					// Setting fields to select:
				if ($this->allFields)	{
					$fields = $this->makeFieldList($tableName);
					$fields[]='_PATH_';
					$fields[]='_CONTROL_';
					if (is_array($this->setFields[$tableName]))	{
						$fields = array_intersect($fields,$this->setFields[$tableName]);
					} else {
						$fields = array();
					}
				} else {
					$fields = array();
				}

					// Finally, render the list:
				$this->HTMLcode.=$this->getTable($tableName, $this->id,implode(',',$fields));
			}
		}
	}
    
	/**
	 * Creates the button with link to either forward or reverse
	 *
	 * @param	string		Type: "fwd" or "rwd"
	 * @param	integer		Pointer
	 * @param	string		Table name
	 * @return	string
	 * @access private
	 */
	function fwd_rwd_HTML($type,$pointer,$table='')	{

        	$this->i6lGParams['curUrlInfo'] = serialize($this->i6lGParams['curUrlInfo']);
        
		$tParam = $table ? '&table='.rawurlencode($table) : '';
		switch($type)	{
			case 'fwd':
				$href = $this->script.'?id='.$this->id.'&pointer='.($this->eCounter-$this->iLimit).$tParam.'&search_field='.t3lib_div::_GP('search_field').'&mode='.$this->i6lGParams['mode'].'&editorNo='.$this->i6lGParams['editorNo'].'&act='.$this->i6lGParams['act'].'&RTEtsConfigParams='.$this->i6lGParams['RTEtsConfigParams'].'&expandPage='.$this->i6lGParams['expandPage'].'&curUrlInfo='.$this->i6lGParams['curUrlInfo'];
				
				return '<a href="'.htmlspecialchars($href).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/pilup.gif','width="14" height="14"').' alt="" />'.
						'</a> <i>[1 - '.$pointer.']</i>';
			break;
			case 'rwd':
				$href = $this->script.'?id='.$this->id.'&pointer='.$this->eCounter.$tParam.'&search_field='.t3lib_div::_GP('search_field').'&mode='.$this->i6lGParams['mode'].'&editorNo='.$this->i6lGParams['editorNo'].'&act='.$this->i6lGParams['act'].'&RTEtsConfigParams='.$this->i6lGParams['RTEtsConfigParams'].'&expandPage='.$this->i6lGParams['expandPage'].'&curUrlInfo='.$this->i6lGParams['curUrlInfo'];
				
				return '<a href="'.htmlspecialchars($href).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/pildown.gif','width="14" height="14"').' alt="" />'.
						'</a> <i>['.($pointer+1).' - '.$this->totalItems.']</i>';
			break;
		}
	}
  
    
	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
	 *
	 * @param	string		Table name
	 * @param	integer		UID (not used here)
	 * @param	string		Title string
	 * @param	array		Records array (from table name)
	 * @return	string
	 */
	function linkWrapItems($table,$uid,$code,$row)	{
		global $TCA;        
        
		if (!$code) {
			$code = '<i>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</i>';
		} else {
			$code = htmlspecialchars(t3lib_div::fixed_lgd_cs($code,$this->fixedL));
		}

		$titleCol = $TCA[$table]['ctrl']['label'];
		$title = $row[$titleCol];

		$ficon = t3lib_iconWorks::getIcon($table,$row);
		
			// added by ndh
		if(!empty($this->tableParams[$table]['userFunc'])){
                       $params = $this->tableParams[$table];
                       $params['uid'] = $row['uid'];
                       $aOnClick = t3lib_div::callUserFunction($this->tableParams[$table]['userFunc'],$params,$this,$checkPrefix='user_',$silent=0);
               } else {  

			$aOnClick = 'return link_record(\''.$row['uid'].'\',\''.$this->tableParams[$table]['GPparamUID'].'\',\''.$this->tableParams[$table]['GPparamCMD'].'\',\''.$this->tableParams[$table]['singlePID'].'\',\''.$this->tableParams[$table]['GPparambackPid'].'\',\''.$this->tableParams[$table]['backPid'].'\',\''.$this->tableParams[$table]['no_cache'].'\')';		
		}
 
		$ATag = '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">';
		$ATag_e = '</a>';

		return $ATag.$code.$ATag_e;
	}

	/**
	 * Returns the title (based on $code) of a table ($table) without a link
	 *
	 * @param	string		Table name
	 * @param	string		Table label
	 * @return	string		The linked table label
	 */
	function linkWrapTable($table,$code)	{
		return $code;
	}
}
 



class ux_SC_browse_links extends SC_browse_links {


	/******************************************************************
	 *
	 * Main functions
	 *
	 ******************************************************************/

	/**
	 * Rich Text Editor (RTE) link selector (MAIN function)
	 * Generates the link selector for the Rich Text Editor.
	 * Can also be used to select links for the TCEforms (see $wiz)
	 *
	 * @param	boolean		If set, the "remove link" is not shown in the menu: Used for the "Select link" wizard which is used by the TCEforms
	 * @return	string		Modified content variable.
	 */
	function main_rte($wiz=0)	{
		global $LANG, $BE_USER;

        $JScode = 'function link_record(uid,GPparamUID,GPparamCMD,singlePID,GPparambackPid,backPID,no_cache)	{	//
                    var theLink = \'index.php?id=\'+singlePID+\'&\'+GPparamUID+\'=\'+uid; 
                    if (GPparamCMD) {
                        theLink += \'&\'+GPparamCMD;
                    }
                    if (GPparambackPid) {
                        theLink += \'&\'+GPparambackPid+\'=\'+backPID;
                    }
                    if (no_cache == \'1\') {
                        theLink += \'&no_cache=1\';
                    }
					self.parent.parent.renderPopup_addLink(theLink,cur_target);
					return false;
                  }';
                  
        $this->doc->JScodeArray[] = $JScode;        
   
			// Starting content:
		$content=$this->doc->startPage('RTE link');

			// Initializing the action value, possibly removing blinded values etc:
		$allowedItems = array_diff(explode(',','page,file,url,mail,spec,linkRecord'),t3lib_div::trimExplode(',',$this->thisConfig['blindLinkOptions'],1));
		reset($allowedItems);
        
		if (!in_array($this->act,$allowedItems))	$this->act = current($allowedItems);

			// Making menu in top:
		$menuDef = array();
		if (!$wiz)	{
			$menuDef['removeLink']['isActive'] = $this->act=='removeLink';
			$menuDef['removeLink']['label'] = $LANG->getLL('removeLink',1);
			$menuDef['removeLink']['url'] = '#';
			$menuDef['removeLink']['addParams'] = 'onclick="self.parent.parent.renderPopup_unLink();return false;"';
		}        
  
 	       		$menuDef['linkRecord']['isActive'] = $this->act=='linkRecord';
        		$menuDef['linkRecord']['label'] = 'Link Record';
        		$menuDef['linkRecord']['url'] = '#';
        		$menuDef['linkRecord']['addParams'] = 'onclick="jumpToUrl(\'?act=linkRecord\');return false;"';

		if (in_array('page',$allowedItems)) {
			$menuDef['page']['isActive'] = $this->act=='page';
			$menuDef['page']['label'] = $LANG->getLL('page',1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onclick="jumpToUrl(\'?act=page\');return false;"';
		}
		if (in_array('file',$allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='file';
			$menuDef['file']['label'] = $LANG->getLL('file',1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onclick="jumpToUrl(\'?act=file\');return false;"';
		}
		if (in_array('url',$allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='url';
			$menuDef['url']['label'] = $LANG->getLL('extUrl',1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onclick="jumpToUrl(\'?act=url\');return false;"';
		}
		if (in_array('mail',$allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='mail';
			$menuDef['mail']['label'] = $LANG->getLL('email',1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onclick="jumpToUrl(\'?act=mail\');return false;"';
		}
		if (is_array($this->thisConfig['userLinks.']) && in_array('spec',$allowedItems)) {
			$menuDef['spec']['isActive'] = $this->act=='spec';
			$menuDef['spec']['label'] = $LANG->getLL('special',1);
			$menuDef['spec']['url'] = '#';
			$menuDef['spec']['addParams'] = 'onclick="jumpToUrl(\'?act=spec\');return false;"';
		}
		$content .= $this->doc->getTabMenuRaw($menuDef);

			// Adding the menu and header to the top of page:
		$content.=$this->printCurrentUrl($this->curUrlInfo['info']).'<br />';      

			// Depending on the current action we will create the actual module content for selecting a link:
		switch($this->act)	{
        
            		case 'linkRecord':
            
					// Making the browsable pagetree:
				$pagetree = t3lib_div::makeInstance('TBE_PageTree');
				$pagetree->script='browse_links.php';
				$pagetree->ext_pArrPages = !strcmp($pArr[3],'pages')?1:0;
				$tree=$pagetree->getBrowsableTree();                    
			
				$lrKeys = array_keys($BE_USER->userTS['ux_linkRecord.']);
			
				foreach ($lrKeys as $k) {
					$tables[] = $BE_USER->userTS['ux_linkRecord.'][$k]['table'];
				}
			
				$tables = implode(',',$tables);
			
					// Making the list of elements, if applicable:
				$cElements = $this->TBE_expandPageIR($tables);
	
				$ltarget='
				<!--
					Selecting target for link:
				-->
					<form action="" name="ltargetform" id="ltargetform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
							<tr>
								<td>'.$GLOBALS['LANG']->getLL('target',1).':</td>
								<td><input type="text" name="ltarget" onchange="setTarget(this.value);" value="'.htmlspecialchars($this->setTarget).'"'.$this->doc->formWidth(10).' /></td>
								<td>
									<select name="ltarget_type" onchange="setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
										<option></option>
										<option value="_top">'.$GLOBALS['LANG']->getLL('top',1).'</option>
										<option value="_blank">'.$GLOBALS['LANG']->getLL('newWindow',1).'</option>
									</select>
								</td>
								<td>';
	
				if (($this->curUrlInfo['act']=="page" || $this->curUrlInfo['act']=='file') && $this->curUrlArray['href'])	{
					$ltarget.='
								<input type="submit" value="'.$GLOBALS['LANG']->getLL('update',1).'" onclick="return link_current();" />';
				}
	
				$selectJS = '
					if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0)	{
						document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+"x"+document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
						setTarget(document.ltargetform.ltarget.value);
						document.ltargetform.popup_width.selectedIndex=0;
						document.ltargetform.popup_height.selectedIndex=0;
					}
				';
	
				$ltarget.='		</td>
							</tr>
							<tr>
								<td>'.$GLOBALS['LANG']->getLL('target_popUpWindow',1).':</td>
								<td colspan="3">
									<select name="popup_width" onchange="'.htmlspecialchars($selectJS).'">
										<option value="0">'.$GLOBALS['LANG']->getLL('target_popUpWindow_width',1).'</option>
										<option value="300">300</option>
										<option value="400">400</option>
										<option value="500">500</option>
										<option value="600">600</option>
										<option value="700">700</option>
										<option value="800">800</option>
									</select>
									x
									<select name="popup_height" onchange="'.htmlspecialchars($selectJS).'">
										<option value="0">'.$GLOBALS['LANG']->getLL('target_popUpWindow_height',1).'</option>
										<option value="200">200</option>
										<option value="300">300</option>
										<option value="400">400</option>
										<option value="500">500</option>
										<option value="600">600</option>
									</select>
								</td>
							</tr>
						</table>
					</form>';
				
					
					// Putting the things together, side by side:
				$content.= '            
				<!--
				Wrapper table for page tree / record list:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBrecords">
				<tr>
					<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('pageTree').':').$tree.'</td>
					<td class="c-wCell" valign="top">'.$cElements.$ltarget.'</td>
				</tr>
				</table>
				';
		
					// Add some space    
				$content.='<br /><br />';
        
                	break;
        
			case 'mail':
				$extUrl='

			<!--
				Enter mail address:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkMail">
							<tr>
								<td>'.$GLOBALS['LANG']->getLL('emailAddress',1).':</td>
								<td><input type="text" name="lemail"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='mail'?$this->curUrlInfo['info']:'').'" /> '.
									'<input type="submit" value="'.$GLOBALS['LANG']->getLL('setLink',1).'" onclick="setTarget(\'\');setValue(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
			break;
			case 'url':
				$extUrl='

			<!--
				Enter External URL:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
							<tr>
								<td>URL:</td>
								<td><input type="text" name="lurl"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='url'?$this->curUrlInfo['info']:'http://').'" /> '.
									'<input type="submit" value="'.$GLOBALS['LANG']->getLL('setLink',1).'" onclick="setValue(document.lurlform.lurl.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
			break;
			case 'file':
				$foldertree = t3lib_div::makeInstance('rteFolderTree');
				$tree=$foldertree->getBrowsableTree();

				if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act']!='file')	{
					$cmpPath='';
				} elseif (substr(trim($this->curUrlInfo['info']),-1)!='/')	{
					$cmpPath=PATH_site.dirname($this->curUrlInfo['info']).'/';
					if (!isset($this->expandFolder))			$this->expandFolder = $cmpPath;
				} else {
					$cmpPath=PATH_site.$this->curUrlInfo['info'];
				}

				list(,,$specUid) = explode('_',$this->PM);
				$files = $this->expandFolder($foldertree->specUIDmap[$specUid]);

				$content.= '

			<!--
				Wrapper table for folder tree / file list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('folderTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$files.'</td>
						</tr>
					</table>
					';
			break;
			case 'spec':
				if (is_array($this->thisConfig['userLinks.']))	{
					$subcats=array();
					$v=$this->thisConfig['userLinks.'];
					reset($v);
					while(list($k2)=each($v))	{
						$k2i = intval($k2);
						if (substr($k2,-1)=='.' && is_array($v[$k2i.'.']))	{

								// Title:
							$title = trim($v[$k2i]);
							if (!$title)	{
								$title=$v[$k2i.'.']['url'];
							} else {
								$title=$LANG->sL($title);
							}
								// Description:
							$description=$v[$k2i.'.']['description'] ? $LANG->sL($v[$k2i.'.']['description'],1).'<br />' : '';

								// URL + onclick event:
							$onClickEvent='';
							if (isset($v[$k2i.'.']['target']))	$onClickEvent.="setTarget('".$v[$k2i.'.']['target']."');";
							$v[$k2i.'.']['url'] = str_replace('###_URL###',$this->siteURL,$v[$k2i.'.']['url']);
							if (substr($v[$k2i.'.']['url'],0,7)=="http://" || substr($v[$k2i.'.']['url'],0,7)=='mailto:')	{
								$onClickEvent.="cur_href=unescape('".rawurlencode($v[$k2i.'.']['url'])."');link_current();";
							} else {
								$onClickEvent.="link_spec(unescape('".$this->siteURL.rawurlencode($v[$k2i.'.']['url'])."'));";
							}

								// Link:
							$A=array('<a href="#" onclick="'.htmlspecialchars($onClickEvent).'return false;">','</a>');

								// Adding link to menu of user defined links:
							$subcats[$k2i]='
								<tr>
									<td class="bgColor4">'.$A[0].'<strong>'.htmlspecialchars($title).($this->curUrlInfo['info']==$v[$k2i.'.']['url']?'<img'.t3lib_iconWorks::skinImg('','gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" />':'').'</strong><br />'.$description.$A[1].'</td>
								</tr>';
						}
					}

						// Sort by keys:
					ksort($subcats);

						// Add menu to content:
					$content.= '

			<!--
				Special userdefined menu:
			-->
						<table border="0" cellpadding="1" cellspacing="1" id="typo3-linkSpecial">
							<tr>
								<td class="bgColor5" class="c-wCell" valign="top"><strong>'.$LANG->getLL('special',1).'</strong></td>
							</tr>
							'.implode('',$subcats).'
						</table>
						';
				}
			break;
			case 'page':
			default:
				$pagetree = t3lib_div::makeInstance('rtePageTree');
				$tree=$pagetree->getBrowsableTree();
				$cElements = $this->expandPage();
				$content.= '

			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('pageTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$cElements.'</td>
						</tr>
					</table>
					';
			break;
		}

			// Target:
		if ($this->act!='mail' && $this->act != 'linkRecord' )	{
			$ltarget='
            
			<!--
				Selecting target for link:
			-->
				<form action="" name="ltargetform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('target',1).':</td>
							<td><input type="text" name="ltarget" onchange="setTarget(this.value);" value="'.htmlspecialchars($this->setTarget).'"'.$this->doc->formWidth(10).' /></td>
							<td>
								<select name="ltarget_type" onchange="setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
									<option></option>
									<option value="_top">'.$GLOBALS['LANG']->getLL('top',1).'</option>
									<option value="_blank">'.$GLOBALS['LANG']->getLL('newWindow',1).'</option>
								</select>
							</td>
							<td>';

			if (($this->curUrlInfo['act']=="page" || $this->curUrlInfo['act']=='file') && $this->curUrlArray['href'])	{
				$ltarget.='
							<input type="submit" value="'.$GLOBALS['LANG']->getLL('update',1).'" onclick="return link_current();" />';
			}

			$selectJS = '
				if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0)	{
					document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+"x"+document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
					setTarget(document.ltargetform.ltarget.value);
					document.ltargetform.popup_width.selectedIndex=0;
					document.ltargetform.popup_height.selectedIndex=0;
				}
			';

			$ltarget.='		</td>
						</tr>
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('target_popUpWindow',1).':</td>
							<td colspan="3">
								<select name="popup_width" onchange="'.htmlspecialchars($selectJS).'">
									<option value="0">'.$GLOBALS['LANG']->getLL('target_popUpWindow_width',1).'</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
									<option value="700">700</option>
									<option value="800">800</option>
								</select>
								x
								<select name="popup_height" onchange="'.htmlspecialchars($selectJS).'">
									<option value="0">'.$GLOBALS['LANG']->getLL('target_popUpWindow_height',1).'</option>
									<option value="200">200</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
								</select>
							</td>
						</tr>
					</table>
				</form>';

				// Add "target selector" box to content:
			$content.=$ltarget;

				// Add some space
			$content.='<br /><br />';
		}

			// End page, return content:
		$content.= $this->doc->endPage();
		return $content;
	}


	/**
	 * For TYPO3 Element Browser: This lists all content elements from the given list of tables
	 *
	 * @param	string		Commalist of tables. Set to "*" if you want all tables.
	 * @return	string		HTML output.
	 */
	function TBE_expandPageIR($tables)	{
		global $TCA,$BE_USER,$BACK_PATH;

		$out='';
		if ($this->expandPage>=0 && t3lib_div::testInt($this->expandPage) && $BE_USER->isInWebMount($this->expandPage))	{

				// Set array with table names to list:
			if (!strcmp(trim($tables),'*'))	{
				$tablesArr = array_keys($TCA);
			} else {
				$tablesArr = t3lib_div::trimExplode(',',$tables,1);
			}
			reset($tablesArr);

				// Headline for selecting records:
			$out.=$this->barheader($GLOBALS['LANG']->getLL('selectRecords').':');

				// Create the header, showing the current page for which the listing is. Includes link to the page itself, if pages are amount allowed tables.
			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
			$mainPageRec = t3lib_BEfunc::getRecord('pages',$this->expandPage);
			$ATag='';
			$ATag_e='';
			$ATag2='';
			if (in_array('pages',$tablesArr))	{
				$ficon=t3lib_iconWorks::getIcon('pages',$mainPageRec);
                		$ATag='<a href="#" onclick="return link_typo3Page(\''.$expPageId.'\',\'#'.rawurlencode($mainPageRec).'\');">';
				$ATag_alt=substr($ATag,0,-4).",'',1);\">";
				$ATag_e='</a>';
			}
			$picon=t3lib_iconWorks::getIconImage('pages',$mainPageRec,$BACK_PATH,'');
			$pBicon=$ATag2?'<img'.t3lib_iconWorks::skinImg('','gfx/plusbullet2.gif','width="18" height="16"').' alt="" />':'';
			$pText=htmlspecialchars(t3lib_div::fixed_lgd_cs($mainPageRec['title'],$titleLen));
			$out.=$picon.$ATag2.$pBicon.$ATag_e.$ATag.$pText.$ATag_e.'<br />';

				// Initialize the record listing:
			$id = $this->expandPage;
			$pointer = t3lib_div::intInRange($this->pointer,0,100000);
			$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$pageinfo = t3lib_BEfunc::readPageAccess($id,$perms_clause);
			$table='';

				// Generate the record list:
			$dblist = t3lib_div::makeInstance('TBE_browser_recordListIR');
			$dblist->script='browse_links.php';
			$dblist->backPath = $BACK_PATH;
			$dblist->thumbs = 0;
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($pageinfo);
			$dblist->noControlPanels=1;
			$dblist->clickMenuEnabled=0;
			$dblist->tableList=implode(',',$tablesArr);

            		$lrKeys = array_keys($BE_USER->userTS['ux_linkRecord.']);
            
            		if (count($lrKeys)>0) { 
            	       
				foreach ($lrKeys as $k) {
					$params[$BE_USER->userTS['ux_linkRecord.'][$k]['table']]['GPparamUID'] = $BE_USER->userTS['ux_linkRecord.'][$k]['GPparamUID'];
					$params[$BE_USER->userTS['ux_linkRecord.'][$k]['table']]['GPparamCMD'] = $BE_USER->userTS['ux_linkRecord.'][$k]['GPparamCMD'];
					$params[$BE_USER->userTS['ux_linkRecord.'][$k]['table']]['singlePID'] = $BE_USER->userTS['ux_linkRecord.'][$k]['singlePID'];
					$params[$BE_USER->userTS['ux_linkRecord.'][$k]['table']]['GPparambackPid'] = $BE_USER->userTS['ux_linkRecord.'][$k]['GPparambackPid'];
					$params[$BE_USER->userTS['ux_linkRecord.'][$k]['table']]['backPid'] = $BE_USER->userTS['ux_linkRecord.'][$k]['backPid'];
					$params[$BE_USER->userTS['ux_linkRecord.'][$k]['table']]['no_cache'] = $BE_USER->userTS['ux_linkRecord.'][$k]['no_cache'];
				}
			
				$dblist->tableParams = $params;
			
				$dblist->i6lGParams = array(
							'pointer' => $this->pointer,
							'act' => $this->act,
							'mode' => $this->mode,
							'curUrlInfo' => $this->curUrlInfo,
							'curUrlArray' => $this->curUrlArray,
							'P' => $this->P,
							'bparams' => $this->bparams,
							'RTEtsConfigParams' => $this->RTEtsConfigParams,
							'expandPage' => $this->expandPage,
							'expandFolder' => $this->expandFolder,
							'PM' => $this->PM
							);
			
			
				$dblist->start($id,t3lib_div::_GP('table'),$pointer,
						t3lib_div::_GP('search_field'),
						t3lib_div::_GP('search_levels'),
						t3lib_div::_GP('showLimit')
					);
				$dblist->setDispFields();
				$dblist->generateList($id,$table);
				$dblist->writeBottom();
			}

				//	Add the HTML for the record list to output variable:
			$out.=$dblist->HTMLcode;
			$out.=$dblist->getSearchBox();
		}

			// Return accumulated content:
		return $out;
	}
    
}


?>
