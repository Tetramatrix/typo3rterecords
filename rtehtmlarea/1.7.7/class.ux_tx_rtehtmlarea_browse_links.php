<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2008 Chi Hoang (info@chihoang.de)
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

/**
 * Local version of the record list.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */

require_once (PATH_t3lib . 'class.t3lib_div.php');

class TBE_browser_recordListIR extends localRecordList {
	var $script = 'browse_links.php';
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
	function listURL($altId = '', $table = -1, $exclList = '') {
		global $BE_USER;
		
		if ($table == - 1 && $this->table == '') {
			$tablesArray = array ();
			foreach ( $BE_USER->userTS ['ux_linkRecord.'] as $def )
				$tablesArray [] = $def ['table'];
			$table = implode ( ',', $tablesArray );
		}
				
		return $this->script . '?id=' . (strcmp ( $altId, '' ) ? $altId : $this->id) . '&table=' . rawurlencode ( $table == - 1 ? $this->table == '' ? $this->tableName : $this->table : $table ) . ($this->thumbs ? '&imagemode=' . $this->thumbs : '') . ($this->returnUrl ? '&returnUrl=' . rawurlencode ( $this->returnUrl ) : '') . ($this->searchString ? '&search_field=' . rawurlencode ( $this->searchString ) : '') . ($this->searchLevels ? '&search_levels=' . rawurlencode ( $this->searchLevels ) : '') . ($this->showLimit ? '&showLimit=' . rawurlencode ( $this->showLimit ) : '') . ((! $exclList || ! t3lib_div::inList ( $exclList, 'sortField' )) && $this->sortField ? '&sortField=' . rawurlencode ( $this->sortField ) : '') . ((! $exclList || ! t3lib_div::inList ( $exclList, 'sortRev' )) && $this->sortRev ? '&sortRev=' . rawurlencode ( $this->sortRev ) : '') . '&mode=' . $this->i6lGParams ['mode'] . '&act=' . $this->i6lGParams ['act'] . '&RTEtsConfigParams=' . $this->i6lGParams ['RTEtsConfigParams'] . '&expandPage=' . $this->i6lGParams ['expandPage'] . '&curUrlInfo=' . $this->i6lGParams ['curUrlInfo'] . '&SEL=' . $this->i6lGParams ['SEL'];
	}
	
	/**
	 * Traverses the table(s) to be listed and renders the output code for each:
	 * The HTML is accumulated in $this->HTMLcode
	 * Finishes off with a stopper-gif
	 *
	 * @return	void
	 */
	function generateList() {
		global $TCA;
		
		// Set page record in header
		$this->pageRecord = t3lib_BEfunc::getRecordWSOL ( 'pages', $this->id );
		
		// Traverse the TCA table array:
		reset ( $TCA );
		while ( list ( $tableName ) = each ( $TCA ) ) {
			
			// Checking if the table should be rendered:
			if ((! $this->table || $tableName == $this->table) && (! $this->tableList || t3lib_div::inList ( $this->tableList, $tableName )) && $GLOBALS ['BE_USER']->check ( 'tables_select', $tableName )) { // Checks that we see only permitted/requested tables:
				
				$this->tableName = $tableName;
				
				// Load full table definitions:
				t3lib_div::loadTCA ( $tableName );
				
				// Hide tables which are configured via TSConfig not to be shown (also works for admins):
				if (t3lib_div::inList ( $this->hideTables, $tableName ))
					continue;
					
				// iLimit is set depending on whether we're in single- or multi-table mode
				if ($this->table) {
					$this->iLimit = (isset ( $TCA [$tableName] ['interface'] ['maxSingleDBListItems'] ) ? intval ( $TCA [$tableName] ['interface'] ['maxSingleDBListItems'] ) : $this->itemsLimitSingleTable);
				} else {
					$this->iLimit = (isset ( $TCA [$tableName] ['interface'] ['maxDBListItems'] ) ? intval ( $TCA [$tableName] ['interface'] ['maxDBListItems'] ) : $this->itemsLimitPerTable);
				}
				if ($this->showLimit)
					$this->iLimit = $this->showLimit;
					
				// Setting fields to select:
				if ($this->allFields) {
					$fields = $this->makeFieldList ( $tableName );
					$fields [] = 'tstamp';
					$fields [] = 'crdate';
					$fields [] = '_PATH_';
					$fields [] = '_CONTROL_';
					if (is_array ( $this->setFields [$tableName] )) {
						$fields = array_intersect ( $fields, $this->setFields [$tableName] );
					} else {
						$fields = array ();
					}
				} else {
					$fields = array ();
				}
				
				// Find ID to use (might be different for "versioning_followPages" tables)
				if (intval ( $this->searchLevels ) == 0) {
					if ($TCA [$tableName] ['ctrl'] ['versioning_followPages'] && $this->pageRecord ['_ORIG_pid'] == - 1 && $this->pageRecord ['t3ver_swapmode'] == 0) {
						$this->pidSelect = 'pid=' . intval ( $this->pageRecord ['_ORIG_uid'] );
					} else {
						$this->pidSelect = 'pid=' . intval ( $this->id );
					}
				}
				
				// Finally, render the list:
				$this->HTMLcode .= $this->getTable ( $tableName, $this->id, implode ( ',', $fields ) );
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
	function fwd_rwd_HTML($type, $pointer, $table = '') {
		
		$this->i6lGParams ['curUrlInfo'] = serialize ( $this->i6lGParams ['curUrlInfo'] );
		$tParam = $table ? '&table=' . rawurlencode ( $table ) : '';
		
		switch ($type) {
			case 'fwd' :
				
				$params = array ( 	"id" => $this->id,
									"pointer" =>  ($this->eCounter - $this->iLimit) . $tParam,
									"search_field" => t3lib_div::_GP ( 'search_field' ) ,
									"mode" => $this->i6lGParams ['mode'],
									"editor_no" => $this->i6lGParams ['editorNo'],
									"act" => $this->i6lGParams ['act'],
									"RTEtsConfigParams" => $this->i6lGParams ['RTEtsConfigParams'], 
									"expandPage" => $this->i6lGParams ['expandPage'], 
									"curUrlInfo" => $this->i6lGParams ['curUrlInfo'], 
									"SEL" => $this->i6lGParams ['SEL'],
								);
						
				$href = $this->script . '?id=' . $this->id;
				array_shift($params);
				foreach ( $params as $k => $l) {
					$href .= '&' . $k . '=' . $l;					
				}
				return '<a href="' . htmlspecialchars ( $href ) . '">' . '<img' . t3lib_iconWorks::skinImg ( $this->backPath, 'gfx/pilup.gif', 'width="14" height="14"' ) . ' alt="" />' . '</a> <i>[1 - ' . $pointer . ']</i>';
				break;
				
			case 'rwd' :
				
				$params = array ( 	"id" => $this->id,
									"pointer" =>  $this->eCounter . $tParam,
									"search_field" => t3lib_div::_GP ( 'search_field' ) ,
									"mode" => $this->i6lGParams ['mode'],
									"editor_no" => $this->i6lGParams ['editorNo'],
									"act" => $this->i6lGParams ['act'],
									"RTEtsConfigParams" => $this->i6lGParams ['RTEtsConfigParams'], 
									"expandPage" => $this->i6lGParams ['expandPage'], 
									"curUrlInfo" => $this->i6lGParams ['curUrlInfo'], 
									"SEL" => $this->i6lGParams ['SEL'],
								);
								
				$href = $this->script . '?id=' . $this->id;
				array_shift($params);
				foreach ( $params as $k => $l) {
					$href .= '&' . $k . '=' . $l;					
				}
				return '<a href="' . htmlspecialchars ( $href ) . '">' . '<img' . t3lib_iconWorks::skinImg ( $this->backPath, 'gfx/pildown.gif', 'width="14" height="14"' ) . ' alt="" />' . '</a> <i>[' . ($pointer + 1) . ' - ' . $this->totalItems . ']</i>';
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
	function linkWrapItems($table, $uid, $code, $row) {
		global $TCA;
		
		if (! $code) {
			$code = '<i>[' . $GLOBALS ['LANG']->sL ( 'LLL:EXT:lang/locallang_core.php:labels.no_title', 1 ) . ']</i>';
		} else {
			$code = htmlspecialchars ( t3lib_div::fixed_lgd_cs ( $code, $this->fixedL ) );
		}
		
		$titleCol = $TCA [$table] ['ctrl'] ['label'];
		$title = $row [$titleCol];
		
		$ficon = t3lib_iconWorks::getIcon ( $table, $row );
		
		// added by ndh
		if (! empty ( $this->tableParams [$table] ['userFunc'] )) {
			$params = $this->tableParams [$table];
			$params ['uid'] = $row ['uid'];
			$aOnClick = t3lib_div::callUserFunction ( $this->tableParams [$table] ['userFunc'], $params, $this, $checkPrefix = 'user_', $silent = 0 );
		} else {
			$aOnClick = 'return link_record(\'record:' . $table . ':' . $row ['uid'] . '\',\'' . $this->tableParams [$table] ['GPparamUID'] . '\',\'' . $this->tableParams [$table] ['GPparamCMD'] . '\',\'' . $this->tableParams [$table] ['singlePID'] . '\',\'' . $this->tableParams [$table] ['GPparambackPid'] . '\',\'' . $this->tableParams [$table] ['backPid'] . '\',\'' . $this->tableParams [$table] ['no_cache'] . '\')';
		}
		
		$ATag = '<a href="#" onclick="' . htmlspecialchars ( $aOnClick ) . '">';
		$ATag_e = '</a>';
		
		return $ATag . $code . $ATag_e;
	}
	
	/**
	 * Returns the title (based on $code) of a table ($table) without a link
	 *
	 * @param	string		Table name
	 * @param	string		Table label
	 * @return	string		The linked table label
	 */
	function linkWrapTable($table, $code) {
		return $code;
	}
}

class ux_tx_rtehtmlarea_browse_links extends tx_rtehtmlarea_browse_links {
	
	/******************************************************************
	 *
	 * Main functions
	 *
	 ******************************************************************/
	
	function getJSCode_bioversity() {
		global $BACK_PATH;
		
		// BEGIN accumulation of header JavaScript:
		$JScode = '';
		$JScode .= '
			var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("TYPO3Link");
			var HTMLArea = window.parent.HTMLArea;

			function initDialog() {
				window.plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("TYPO3Link");
				window.HTMLArea = window.parent.HTMLArea;
			}
			
				// This JavaScript is primarily for RTE/Link. jumpToUrl is used in the other cases as well...
			var add_href="' . ($this->curUrlArray ['href'] ? '&curUrl[href]=' . rawurlencode ( $this->curUrlArray ['href'] ) : '') . '";
			var add_target="' . ($this->setTarget ? '&curUrl[target]=' . rawurlencode ( $this->setTarget ) : '') . '";
			var add_class="' . ($this->setClass ? '&curUrl[class]=' . rawurlencode ( $this->setClass ) : '') . '";
			var add_title="' . ($this->setTitle ? '&curUrl[title]=' . rawurlencode ( $this->setTitle ) : '') . '";
			var add_params="' . ($this->bparams ? '&bparams=' . rawurlencode ( $this->bparams ) : '') . '";

			var cur_href="' . ($this->curUrlArray ['href'] ? $this->curUrlArray ['href'] : '') . '";
			var cur_target="' . ($this->setTarget ? $this->setTarget : '') . '";
			var cur_class="' . ($this->setClass ? $this->setClass : '') . '";
			var cur_title="' . ($this->setTitle ? $this->setTitle : '') . '";
			var cur_bak="' . ($this->setTarget ? $this->setTarget : '') . '";
			
			function browse_links_setTarget(value)	{
				cur_target=cur_bak+" "+value;
				add_target="&curUrl[target]="+encodeURIComponent(value);
			}
			function browse_links_setClass(value)	{
				cur_class=value;
				add_class="&curUrl[class]="+encodeURIComponent(value);
			}
			function browse_links_setTitle(value)	{
				cur_title=value;
				add_title="&curUrl[title]="+encodeURIComponent(value);
			}
			function browse_links_setHref(value)	{
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
		';
		
			// Normaly when called from RTE the mode is not "wizard"
		if ($this->mode == 'wizard') { // Functions used, if the link selector is in wizard mode (= TCEforms fields)
			
			unset ( $this->P ['fieldChangeFunc'] ['alert'] );
			reset ( $this->P ['fieldChangeFunc'] );
			$update = '';
			while ( list ( $k, $v ) = each ( $this->P ['fieldChangeFunc'] ) ) {
				$update .= '
				window.opener.' . $v;
			}
			
			$P2 = array ();
			$P2 ['itemName'] = $this->P ['itemName'];
			$P2 ['formName'] = $this->P ['formName'];
			$P2 ['fieldChangeFunc'] = $this->P ['fieldChangeFunc'];
			$addPassOnParams .= t3lib_div::implodeArrayForUrl ( 'P', $P2 );
			
			$JScode .= '
				function link_typo3Page(id,anchor)	{	//
					updateValueInMainForm(id+(anchor?anchor:"")+" "+cur_target);
					close();
					return false;
				}
				function link_folder(folder)	{	//
					updateValueInMainForm(folder+" "+cur_target);
					close();
					return false;
				}
				function link_current()	{	//
					if (cur_href!="http://" && cur_href!="mailto:")	{
						var browse_links_setHref = cur_href+" "+cur_target+" "+cur_class+" "+cur_title;
						if (browse_links_setHref.substr(0,7)=="http://")	browse_links_setHref = browse_links_setHref.substr(7);
						if (browse_links_setHref.substr(0,7)=="mailto:")	browse_links_setHref = browse_links_setHref.substr(7);
						updateValueInMainForm(browse_links_setHref);
						close();
					}
					return false;
				}
				function checkReference()	{	//
					if (window.opener && window.opener.document && window.opener.document.' . $this->P ['formName'] . ' && window.opener.document.' . $this->P ['formName'] . '["' . $this->P ['itemName'] . '"] )	{
						return window.opener.document.' . $this->P ['formName'] . '["' . $this->P ['itemName'] . '"];
					} else {
						close();
					}
				}
				function updateValueInMainForm(input)	{	//
					var field = checkReference();
					if (field)	{
						field.value = input;
						' . $update . '
					}
				}
			';
		} else { // Functions used, if the link selector is in RTE mode:
			$JScode .= '
				function link_typo3Page(id,anchor)	{
					var theLink = \'' . $this->siteURL . '?id=\'+id+(anchor?anchor:"");
					if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
					plugin.createLink(theLink,cur_target,cur_class,cur_title);
					return false;
				}
				function link_folder(folder)	{	//
					var theLink = \'' . $this->siteURL . '\'+folder;
					if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
					plugin.createLink(theLink,cur_target,cur_class,cur_title);
					return false;
				}
				function link_spec(theLink)	{	//
					if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
					plugin.createLink(theLink,cur_target,cur_class,cur_title);
					return false;
				}
				function link_current()	{	//
					if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
					if (cur_href!="http://" && cur_href!="mailto:")	{
						plugin.createLink(cur_href,cur_target,cur_class,cur_title);
					}
					return false;
				}
			';
		}
		
		// General "jumpToUrl" function:
		$JScode .= '
			function jumpToUrl(URL,anchor)	{	//
				var add_editorNo = URL.indexOf("editorNo=")==-1 ? "&editorNo=' . $this->editorNo . '" : "";
				var add_contentTypo3Language = URL.indexOf("contentTypo3Language=")==-1 ? "&contentTypo3Language=' . $this->contentTypo3Language . '" : "";
				var add_contentTypo3Charset = URL.indexOf("contentTypo3Charset=")==-1 ? "&contentTypo3Charset=' . $this->contentTypo3Charset . '" : "";
				var add_act = URL.indexOf("act=")==-1 ? "&act=' . $this->act . '" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode=' . $this->mode . '" : "";
				var add_SEL = URL.indexOf("SEL=")==-1 ? "&SEL=' . $this->SEL . '" : "";
				var add_title = URL.indexOf("title=")==-1 ? "&title=' . $this->title . '" : "";
				var add_class = URL.indexOf("class=")==-1 ? "&class=' . $this->class . '" : "";
				var theLocation = URL+add_act+add_editorNo+add_contentTypo3Language+add_contentTypo3Charset+add_mode+add_href+add_target+add_class+add_title+add_params+add_SEL' . ($addPassOnParams ? '+"' . $addPassOnParams . '"' : '') . '+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
		';
		
		// This is JavaScript especially for the TBE Element Browser!
		$pArr = explode ( '|', $this->bparams );
		$formFieldName = 'data[' . $pArr [0] . '][' . $pArr [1] . '][' . $pArr [2] . ']';
		$JScode .= '
			var elRef="";
			var targetDoc="";

			function launchView(url)	{	//
				var thePreviewWindow="";
				thePreviewWindow = window.open("' . $BACK_PATH . 'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function setReferences()	{	//
				if (parent.window.opener
				&& parent.window.opener.content
				&& parent.window.opener.content.document.editform
				&& parent.window.opener.content.document.editform["' . $formFieldName . '"]
						) {
					targetDoc = parent.window.opener.content.document;
					elRef = targetDoc.editform["' . $formFieldName . '"];
					return true;
				} else {
					return false;
				}
			}
			function insertElement(table, uid, type, filename,fp,filetype,imagefile,action, close)	{	//
				if (1==' . ($pArr [0] && ! $pArr [1] && ! $pArr [2] ? 1 : 0) . ')	{
					addElement(filename,table+"_"+uid,fp,close);
				} else {
					if (setReferences())	{
						parent.window.opener.group_change("add","' . $pArr [0] . '","' . $pArr [1] . '","' . $pArr [2] . '",elRef,targetDoc);
					} else {
						alert("Error - reference to main window is not set properly!");
					}
					if (close)	{
						parent.window.opener.focus();
						parent.close();
					}
				}
				return false;
			}
			function addElement(elName,elValue,altElValue,close)	{	//
				if (parent.window.opener && parent.window.opener.setFormValueFromBrowseWin)	{
					parent.window.opener.setFormValueFromBrowseWin("' . $pArr [0] . '",altElValue?altElValue:elValue,elName);
					if (close)	{
						parent.window.opener.focus();
						parent.close();
					}
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			}
		';
		return $JScode;
	}
	
	/**
	 * Rich Text Editor (RTE) link selector (MAIN function)
	 * Generates the link selector for the Rich Text Editor.
	 * Can also be used to select links for the TCEforms (see $wiz)
	 *
	 * @param	boolean		If set, the "remove link" is not shown in the menu: Used for the "Select link" wizard which is used by the TCEforms
	 * @return	string		Modified content variable.
	 */
	function main_rte($wiz = 0) {
		global $LANG, $BE_USER;
		
		$needle = ('/&amp;/');
		$replace = ('&');
		
		$this->curUrlArray ['href'] = preg_replace($needle,$replace,$this->curUrlArray ['href']);
		$this->curUrlArray ['target'] = preg_replace($needle,$replace,$this->curUrlArray ['target']);
		$this->setTarget = preg_replace($needle,$replace,$this->setTarget);
		
		$JScode = $this->getJSCode_bioversity ();
		
		if (t3lib_div::int_from_ver ( TYPO3_version ) <= t3lib_div::int_from_ver ( '3.8.2' )) {
			
			$JScode .= 'function link_record(uid,GPparamUID,GPparamCMD,singlePID,GPparambackPid,backPID,no_cache)	{	//
					var theLink = uid;
					if (singlePID) {
						theLink += \' singlePID=\'+singlePID;
					}
					if (GPparamUID) {
                    	theLink += \'&\'+GPparamUID+\'={field:uid}\';
                    }
					if (GPparamCMD) {
						theLink += \'&\'+GPparamCMD;
					}
					if (GPparambackPid) {
						theLink += \'&\'+GPparambackPid+\'=\'+backPID;
					}
					if (no_cache) {
						theLink += \'&no_cache=1\';
					}
					if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
					editor.renderPopup_addLink(theLink,cur_target,cur_class,cur_title);
				}';
		
		} else {
			
			$JScode .= 'function link_record(uid,GPparamUID,GPparamCMD,singlePID,GPparambackPid,backPID,no_cache)	{	//
					var theLink = uid;
					if (singlePID) {
                    	theLink += \' singlePID=\'+singlePID;
                    }
					if (GPparamUID) {
						theLink += \'&\'+GPparamUID+\'={field:uid}\';
					}
					if (GPparamCMD) {
						theLink += \'&\'+GPparamCMD;
					}
					if (GPparambackPid) {
						theLink += \'&\'+GPparambackPid+\'=\'+backPID;
					}
					if (no_cache) {
						theLink += \'&no_cache=1\';
					}
					if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
					plugin.createLink(theLink,cur_target,cur_class,cur_title);
				}';
		}
		
		$this->doc->JScodeArray [] = $JScode;
		
		// Starting content:
		$content = $this->doc->startPage ( 'RTE link' );
		
		// Initializing the action value, possibly removing blinded values etc:
		$allowedItems = array_diff ( explode ( ',', 'page,file,url,mail,spec,linkRecord' ), t3lib_div::trimExplode ( ',', $this->thisConfig ['blindLinkOptions'], 1 ) );
		reset ( $allowedItems );
		
		if (! in_array ( $this->act, $allowedItems ))
			$this->act = current ( $allowedItems );
			
		// Making menu in top:
		$menuDef = array ();
		$lrKeys = array_keys ( $BE_USER->userTS ['ux_linkRecord.'] );
		
		if (count ( $lrKeys ) > 0) {
			foreach ( $lrKeys as $k ) {
				
				$browseTabLabel = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['browseTabLabel'];
				$defaultExpandPage = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['defaultExpandPage'];
				$tabNumber = preg_replace ( '/\./', '', $k );
				$this->SEL = t3lib_div::_GP ( 'SEL' );
				
				if ($this->SEL == $tabNumber) {
					$menuDef ['linkRecord' . $tabNumber] ['isActive'] = $this->act == 'linkRecord';
				} else {
					$menuDef ['linkRecord' . $tabNumber] ['isActive'] = '';
				}
				
				$menuDef ['linkRecord' . $tabNumber] ['label'] = 'Link record';
				$menuDef ['linkRecord' . $tabNumber] ['url'] = '#';
				
				// added by ndh
				if (! empty ( $browseTabLabel )) {
					$menuDef ['linkRecord' . $tabNumber] ['label'] = $browseTabLabel;
				} else {
					$menuDef ['linkRecord' . $tabNumber] ['label'] = 'Link record ';
					$menuDef ['linkRecord' . $tabNumber] ['url'] = '#';
				}
				
				// added by ndh
				if (! empty ( $defaultExpandPage )) {
					$expandPage = '&amp;expandPage=' . $defaultExpandPage;
				} else {
					$expandPage = '';
				}
				
				$menuDef ['linkRecord' . $tabNumber] ['addParams'] = 'onclick="jumpToUrl(\'?act=linkRecord&amp;SEL=' . $tabNumber . '&amp;mode=rte' . $expandPage . '\');return false;"';
			}
		}
		
		if (! $wiz) {
			$menuDef ['removeLink'] ['isActive'] = $this->act == 'removeLink';
			$menuDef ['removeLink'] ['label'] = $LANG->getLL ( 'removeLink', 1 );
			$menuDef ['removeLink'] ['url'] = '#';
			if (t3lib_div::int_from_ver ( TYPO3_version ) <= t3lib_div::int_from_ver ( '3.8.2' )) {
				$menuDef ['removeLink'] ['addParams'] = 'onclick="editor.renderPopup_unLink();return false;"';
			} else {
				$menuDef ['removeLink'] ['addParams'] = 'onclick="plugin.unLink();return false;"';
			}
		}
		
		if (in_array ( 'page', $allowedItems )) {
			$menuDef ['page'] ['isActive'] = $this->act == 'page';
			$menuDef ['page'] ['label'] = $LANG->getLL ( 'page', 1 );
			$menuDef ['page'] ['url'] = '#';
			$menuDef ['page'] ['addParams'] = 'onclick="jumpToUrl(\'?act=page\');return false;"';
		}
		if (in_array ( 'file', $allowedItems )) {
			$menuDef ['file'] ['isActive'] = $this->act == 'file';
			$menuDef ['file'] ['label'] = $LANG->getLL ( 'file', 1 );
			$menuDef ['file'] ['url'] = '#';
			$menuDef ['file'] ['addParams'] = 'onclick="jumpToUrl(\'?act=file\');return false;"';
		}
		if (in_array ( 'url', $allowedItems )) {
			$menuDef ['url'] ['isActive'] = $this->act == 'url';
			$menuDef ['url'] ['label'] = $LANG->getLL ( 'extUrl', 1 );
			$menuDef ['url'] ['url'] = '#';
			$menuDef ['url'] ['addParams'] = 'onclick="jumpToUrl(\'?act=url\');return false;"';
		}
		if (in_array ( 'mail', $allowedItems )) {
			$menuDef ['mail'] ['isActive'] = $this->act == 'mail';
			$menuDef ['mail'] ['label'] = $LANG->getLL ( 'email', 1 );
			$menuDef ['mail'] ['url'] = '#';
			$menuDef ['mail'] ['addParams'] = 'onclick="jumpToUrl(\'?act=mail\');return false;"';
		}
		if (is_array ( $this->thisConfig ['userLinks.'] ) && in_array ( 'spec', $allowedItems )) {
			$menuDef ['spec'] ['isActive'] = $this->act == 'spec';
			$menuDef ['spec'] ['label'] = $LANG->getLL ( 'special', 1 );
			$menuDef ['spec'] ['url'] = '#';
			$menuDef ['spec'] ['addParams'] = 'onclick="jumpToUrl(\'?act=spec\');return false;"';
		}
		$content .= $this->doc->getTabMenuRaw ( $menuDef );
		
		// Adding the menu and header to the top of page:
		if (preg_match('/record/',$this->curUrlArray['href'])) {
			$content .= $this->printCurrentUrl ( $this->curUrlArray ['href'] . $this->curUrlArray ['target'] ) . '<br />';
		} else {
			$content .= $this->printCurrentUrl ( $this->curUrlInfo ['info'] ) . '<br />';
		}
		
		// Depending on the current action we will create the actual module content for selecting a link:
		switch ($this->act) {
			
			case 'linkRecord' :
				
				if (preg_match('/record/',$this->setTarget)) {
					$this->setTarget = '';
					$this->curUrlArray['href'] = $this->siteUrl;
					$this->curUrlArray['target'] = '';
				}
				
				// Making the browsable pagetree:
				$pagetree = t3lib_div::makeInstance ( 'TBE_PageTree' );
				$pagetree->script = 'browse_links.php';
				$pagetree->ext_pArrPages = ! strcmp ( $pArr [3], 'pages' ) ? 1 : 0;
				$tree = $pagetree->getBrowsableTree ();
				
				$lrKeys = array_keys ( $BE_USER->userTS ['ux_linkRecord.'] );
				
				foreach ( $lrKeys as $k ) {
					$tables [] = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['table'];
				}
				
				$tables = implode ( ',', $tables );
				
				// Making the list of elements, if applicable:
				$cElements = $this->TBE_expandPageIR ( $tables );
				
				// Putting the things together, side by side:
				$content .= '
					<!--
					Wrapper table for page tree / record list:
					-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBrecords">
					<tr>
						<td class="c-wCell" valign="top">' . $this->barheader ( $GLOBALS ['LANG']->getLL ( 'pageTree' ) . ':' ) . $tree . '</td>
						<td class="c-wCell" valign="top">' . $cElements . $this->addAttributesForm () . '</td>
					</tr>
					</table>
					';
				
				// Add some space
				$content .= '<br /><br />';
				break;
			
			case 'mail' :
				$extUrl = '
			<!--
				Enter mail address:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkMail">
							<tr>
								<td>' . $LANG->getLL ( 'emailAddress', 1 ) . ':</td>
								<td><input type="text" name="lemail"' . $this->doc->formWidth ( 20 ) . ' value="' . htmlspecialchars ( $this->curUrlInfo ['act'] == 'mail' ? $this->curUrlInfo ['info'] : '' ) . '" /> ' . '<input type="submit" value="' . $LANG->getLL ( 'setLink', 1 ) . '" onclick="browse_links_setTarget(\'\');browse_links_setHref(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content .= $extUrl . $this->addAttributesForm ();
				break;
			case 'url' :
				$extUrl = '
			<!--
				Enter External URL:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
							<tr>
								<td>URL:</td>
								<td><input type="text" name="lurl"' . $this->doc->formWidth ( 20 ) . ' value="' . htmlspecialchars ( $this->curUrlInfo ['act'] == 'url' ? $this->curUrlInfo ['info'] : 'http://' ) . '" /> ' . '<input type="submit" value="' . $LANG->getLL ( 'setLink', 1 ) . '" onclick="browse_links_setHref(document.lurlform.lurl.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content .= $extUrl . $this->addAttributesForm ();
				break;
			case 'file' :
				$foldertree = t3lib_div::makeInstance ( 'tx_rtehtmlarea_folderTree' );
				$tree = $foldertree->getBrowsableTree ();
				
				if (! $this->curUrlInfo ['value'] || $this->curUrlInfo ['act'] != 'file') {
					$cmpPath = '';
				} elseif (substr ( trim ( $this->curUrlInfo ['info'] ), - 1 ) != '/') {
					$cmpPath = PATH_site . dirname ( $this->curUrlInfo ['info'] ) . '/';
					if (! isset ( $this->expandFolder ))
						$this->expandFolder = $cmpPath;
				} else {
					$cmpPath = PATH_site . $this->curUrlInfo ['info'];
				}
				
				list ( , , $specUid ) = explode ( '_', $this->PM );
				$files = $this->expandFolder ( $foldertree->specUIDmap [$specUid] );
				
				// Create upload/create folder forms, if a path is given:
				if ($BE_USER->getTSConfigVal ( 'options.uploadFieldsInTopOfEB' )) {
					$path = $this->expandFolder;
					if (! $path || ! @is_dir ( $path )) {
						$path = $this->fileProcessor->findTempFolder () . '/'; // The closest TEMP-path is found
					}
					if ($path != '/' && @is_dir ( $path )) {
						$uploadForm = $this->uploadForm ( $path );
						$createFolder = $this->createFolder ( $path );
					} else {
						$createFolder = $uploadForm = '';
					}
					$content .= $uploadForm;
					if ($BE_USER->isAdmin () || $BE_USER->getTSConfigVal ( 'options.createFoldersInEB' )) {
						$content .= $createFolder;
					}
				}
				
				$content .= '
			<!--
			Wrapper table for folder tree / file list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">' . $this->barheader ( $LANG->getLL ( 'folderTree' ) . ':' ) . $tree . '</td>
							<td class="c-wCell" valign="top">' . $files . $this->addAttributesForm () . '</td>
						</tr>
					</table>
					';
				break;
			case 'spec' :
				if (is_array ( $this->thisConfig ['userLinks.'] )) {
					$subcats = array ();
					$v = $this->thisConfig ['userLinks.'];
					reset ( $v );
					while ( list ( $k2 ) = each ( $v ) ) {
						$k2i = intval ( $k2 );
						if (substr ( $k2, - 1 ) == '.' && is_array ( $v [$k2i . '.'] )) {
							
							// Title:
							$title = trim ( $v [$k2i] );
							if (! $title) {
								$title = $v [$k2i . '.'] ['url'];
							} else {
								$title = $LANG->sL ( $title );
							}
							// Description:
							$description = $v [$k2i . '.'] ['description'] ? $LANG->sL ( $v [$k2i . '.'] ['description'], 1 ) . '<br />' : '';
							
							// URL + onclick event:
							$onClickEvent = '';
							if (isset ( $v [$k2i . '.'] ['target'] ))
								$onClickEvent .= "browse_links_setTarget('" . $v [$k2i . '.'] ['target'] . "');";
							$v [$k2i . '.'] ['url'] = str_replace ( '###_URL###', $this->siteURL, $v [$k2i . '.'] ['url'] );
							if (substr ( $v [$k2i . '.'] ['url'], 0, 7 ) == "http://" || substr ( $v [$k2i . '.'] ['url'], 0, 7 ) == 'mailto:') {
								$onClickEvent .= "cur_href=unescape('" . rawurlencode ( $v [$k2i . '.'] ['url'] ) . "');link_current();";
							} else {
								$onClickEvent .= "link_spec(unescape('" . $this->siteURL . rawurlencode ( $v [$k2i . '.'] ['url'] ) . "'));";
							}
							
							// Link:
							$A = array ('<a href="#" onclick="' . htmlspecialchars ( $onClickEvent ) . 'return false;">', '</a>' );
							
							// Adding link to menu of user defined links:
							$subcats [$k2i] = '
								<tr>
									<td class="bgColor4">' . $A [0] . '<strong>' . htmlspecialchars ( $title ) . ($this->curUrlInfo ['info'] == $v [$k2i . '.'] ['url'] ? '<img' . t3lib_iconWorks::skinImg ( $BACK_PATH, 'gfx/blinkarrow_right.gif', 'width="5" height="9"' ) . ' class="c-blinkArrowR" alt="" />' : '') . '</strong><br />' . $description . $A [1] . '</td>
								</tr>';
						}
					}
					
					// Sort by keys:
					ksort ( $subcats );
					
					// Add menu to content:
					$content .= '
			<!--
				Special userdefined menu:
			-->
						<table border="0" cellpadding="1" cellspacing="1" id="typo3-linkSpecial">
							<tr>
								<td class="bgColor5" class="c-wCell" valign="top"><strong>' . $LANG->getLL ( 'special', 1 ) . '</strong></td>
							</tr>
							' . implode ( '', $subcats ) . '
						</table>
						';
				}
				break;
			case 'page' :
				
				$pagetree = t3lib_div::makeInstance ( 'tx_rtehtmlarea_pageTree' );
				$pagetree->ext_showNavTitle = $GLOBALS ['BE_USER']->getTSConfigVal ( 'options.pageTree.showNavTitle' );
				$pagetree->addField ( 'nav_title' );
				$cElements = $this->expandPage ();
				$content .= '
			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">' . $this->barheader ( $LANG->getLL ( 'pageTree' ) . ':' ) . $pagetree->getBrowsableTree () . '</td>
							<td class="c-wCell" valign="top">' . $cElements . $this->addAttributesForm () . '</td>
						</tr>
					</table>
					';
				break;
		}
		
		// End page, return content:
		$content .= $this->doc->endPage ();
		return $content;
	}
	
	function addAttributesForm() {
		$ltargetForm = '';
		// Add page id, target, class selector box and title field:
		$lpageId = $this->addPageIdSelector ();
		$ltarget = $this->addTargetSelector ();
		$lclass = $this->addClassSelector ();
		$ltitle = $this->addTitleSelector ();
		if ($lpageId || $ltarget || $lclass || $ltitle) {
			$ltargetForm = $this->wrapInForm ( $lpageId . $ltarget . $lclass . $ltitle );
		}
		return $ltargetForm;
	}
	
	function wrapInForm($string) {
		global $LANG;
		
		$form = '
			<!--
				Selecting target for link:
			-->
				<form action="" name="ltargetform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">' . $string;
		if ((($this->act == 'page' && $this->curUrlInfo ['act'] == 'page') || ($this->act == 'file' && $this->curUrlInfo ['act'] == 'file') || ($this->act == 'url' && $this->curUrlInfo ['act'] != 'page')) && $this->curUrlArray ['href']) {
			$form .= '
						<tr>
							<td>
							</td>
							<td colspan="3">
								<input type="submit" value="' . $LANG->getLL ( 'update', 1 ) . '" onclick="return link_current();" />
							</td>
						</tr>';
		}
		$form .= '
					</table>
				</form>';
		return $form;
	}
	
	function addTargetSelector() {
		global $LANG;
		
		$targetSelectorConfig = $popupSelectorConfig = array ();
		if (is_array ( $this->buttonConfig ['targetSelector.'] )) {
			$targetSelectorConfig = $this->buttonConfig ['targetSelector.'];
		}
		if (is_array ( $this->buttonConfig ['popupSelector.'] )) {
			$popupSelectorConfig = $this->buttonConfig ['popupSelector.'];
		}
		
		$ltarget = '';
		if ($this->act != 'mail') {
			if (! ($targetSelectorConfig ['disabled'] && $popupSelectorConfig ['disabled'])) {
				$ltarget .= '
						<tr>
							<td>' . $LANG->getLL ( 'target', 1 ) . ':</td>
							<td><input type="text" name="ltarget" onchange="browse_links_setTarget(this.value);" value="' . htmlspecialchars ( $this->setTarget ) . '"' . $this->doc->formWidth ( 10 ) . ' /></td>';
				$ltarget .= '
							<td colspan="2">';
				if (! $targetSelectorConfig ['disabled']) {
					$ltarget .= '
								<select name="ltarget_type" onchange="browse_links_setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
									<option></option>
									<option value="_top">' . $LANG->getLL ( 'top', 1 ) . '</option>
									<option value="_blank">' . $LANG->getLL ( 'newWindow', 1 ) . '</option>
								</select>';
				}
				$ltarget .= '
							</td>';
			}
			
			$ltarget .= '
						</tr>';
			if (! $popupSelectorConfig ['disabled']) {
				
				$selectJS = 'if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0)	{
					document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+\'x\'+document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
					browse_links_setTarget(document.ltargetform.ltarget.value);
					document.ltargetform.popup_width.selectedIndex=0;
					document.ltargetform.popup_height.selectedIndex=0;
				}';
				
				$ltarget .= '
						<tr>
							<td>' . $LANG->getLL ( 'target_popUpWindow', 1 ) . ':</td>
							<td colspan="3">
								<select name="popup_width" onchange="' . $selectJS . '">
									<option value="0">' . $LANG->getLL ( 'target_popUpWindow_width', 1 ) . '</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
									<option value="700">700</option>
									<option value="800">800</option>
								</select>
								x
								<select name="popup_height" onchange="' . $selectJS . '">
									<option value="0">' . $LANG->getLL ( 'target_popUpWindow_height', 1 ) . '</option>
									<option value="200">200</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
								</select>
							</td>
						</tr>';
			}
		}
		return $ltarget;
	}
	
	function addClassSelector() {
		global $LANG;
		
		$selectClass = '';
		$act = $this->act == 'linkRecord' ? 'url' : $act = $this->act;
		
		if ($this->classesAnchorJSOptions [$act]) {
			$selectClassJS = '
					document.ltargetform.anchor_class.value = document.ltargetform.anchor_class.options[document.ltargetform.anchor_class.selectedIndex].value;
					if(document.ltargetform.anchor_class.value && editor.classesAnchorSetup) {
						for (var i = editor.classesAnchorSetup.length; --i >= 0;) {
							var anchorClass = editor.classesAnchorSetup[i];
							if (anchorClass[\'name\'] == document.ltargetform.anchor_class.value) {
								if(anchorClass[\'titleText\'] && document.ltargetform.anchor_title) document.ltargetform.anchor_title.value = anchorClass[\'titleText\'];
								break;
							}
						}
					}
					browse_links_setClass(document.ltargetform.anchor_class.value);
				';
			$selectClass = '
						<tr>
							<td>' . $LANG->getLL ( 'anchor_class', 1 ) . ':</td>
							<td colspan="3">
								<select name="anchor_class" onchange="' . $selectClassJS . '">
									' . $this->classesAnchorJSOptions [$act] . '
								</select>
							</td>
						</tr>';
		}
		return $selectClass;
	}
	
	function addTitleSelector() {
		global $LANG;
		
		return '
				<tr>
					<td>' . $LANG->getLL ( 'anchor_title', 1 ) . ':</td>
					<td colspan="3">
						<input type="text" name="anchor_title" value="' . ($this->setTitle ? $this->setTitle : ($this->thisConfig ['classesAnchor'] ? $this->classesAnchorDefaultTitle [$this->act] : '')) . '" ' . $this->doc->formWidth ( 30 ) . ' />
					</td>
				</tr>';
	}
	
	/**
	 * For TYPO3 Element Browser: This lists all content elements from the given list of tables
	 *
	 * @param	string		Commalist of tables. Set to "*" if you want all tables.
	 * @return	string		HTML output.
	 */
	function TBE_expandPageIR($tables) {
		global $TCA, $BE_USER, $BACK_PATH;
		
		$out = '';
		if ($this->expandPage >= 0 && t3lib_div::testInt ( $this->expandPage ) && $BE_USER->isInWebMount ( $this->expandPage )) {
			
			// Set array with table names to list:
			if (! strcmp ( trim ( $tables ), '*' )) {
				$tablesArr = array_keys ( $TCA );
			} else {
				$tablesArr = t3lib_div::trimExplode ( ',', $tables, 1 );
			}
			
			reset ( $tablesArr );
			
			// Headline for selecting records:
			$out .= $this->barheader ( $GLOBALS ['LANG']->getLL ( 'selectRecords' ) . ':' );
			
			// Create the header, showing the current page for which the listing is. Includes link to the page itself, if pages are amount allowed tables.
			$titleLen = intval ( $GLOBALS ['BE_USER']->uc ['titleLen'] );
			$mainPageRec = t3lib_BEfunc::getRecord ( 'pages', $this->expandPage );
			$ATag = $ATag_e = $ATag2 = '';
			if (in_array ( 'pages', $tablesArr )) {
				$ficon = t3lib_iconWorks::getIcon ( 'pages', $mainPageRec );
				$ATag = '<a href="#" onclick="return link_typo3Page(\'' . $expPageId . '\',\'#' . rawurlencode ( $mainPageRec ) . '\');">';
				$ATag_alt = substr ( $ATag, 0, - 4 ) . ",'',1);\">";
				$ATag_e = '</a>';
			}
			$picon = t3lib_iconWorks::getIconImage ( 'pages', $mainPageRec, $BACK_PATH, '' );
			$pBicon = $ATag2 ? '<img' . t3lib_iconWorks::skinImg ( '', 'gfx/plusbullet2.gif', 'width="18" height="16"' ) . ' alt="" />' : '';
			$pText = htmlspecialchars ( t3lib_div::fixed_lgd_cs ( $mainPageRec ['title'], $titleLen ) );
			$out .= $picon . $ATag2 . $pBicon . $ATag_e . $ATag . $pText . $ATag_e . '<br />';
			
			// Initialize the record listing:
			$id = $this->expandPage;
			$pointer = t3lib_div::intInRange ( $this->pointer, 0, 100000 );
			$perms_clause = $GLOBALS ['BE_USER']->getPagePermsClause ( 1 );
			$pageinfo = t3lib_BEfunc::readPageAccess ( $id, $perms_clause );
			$table = '';
			
			// Generate the record list:
			$dblist = t3lib_div::makeInstance ( 'TBE_browser_recordListIR' );
			$dblist->script = 'browse_links.php';
			$dblist->backPath = $BACK_PATH;
			$dblist->thumbs = 0;
			$dblist->calcPerms = $GLOBALS ['BE_USER']->calcPerms ( $pageinfo );
			$dblist->noControlPanels = 1;
			$dblist->clickMenuEnabled = 0;
			$dblist->tableList = implode ( ',', $tablesArr );
			
			$lrKeys = array_keys ( $BE_USER->userTS ['ux_linkRecord.'] );
			
			if (count ( $lrKeys ) > 0) {
				
				foreach ( $lrKeys as $k ) {
					$params [$BE_USER->userTS ['ux_linkRecord.'] [$k] ['table']] ['GPparamUID'] = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['GPparamUID'];
					$params [$BE_USER->userTS ['ux_linkRecord.'] [$k] ['table']] ['GPparamCMD'] = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['GPparamCMD'];
					$params [$BE_USER->userTS ['ux_linkRecord.'] [$k] ['table']] ['singlePID'] = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['singlePID'];
					$params [$BE_USER->userTS ['ux_linkRecord.'] [$k] ['table']] ['GPparambackPid'] = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['GPparambackPid'];
					$params [$BE_USER->userTS ['ux_linkRecord.'] [$k] ['table']] ['backPid'] = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['backPid'];
					$params [$BE_USER->userTS ['ux_linkRecord.'] [$k] ['table']] ['no_cache'] = $BE_USER->userTS ['ux_linkRecord.'] [$k] ['no_cache'];
				}
				
				$dblist->tableParams = $params;				
				$dblist->i6lGParams = array ('pointer' => $this->pointer, 'act' => $this->act, 'mode' => $this->mode, 'editorNo' => $this->editorNo, 'curUrlInfo' => $this->curUrlInfo, 'curUrlArray' => $this->curUrlArray, 'P' => $this->P, 'bparams' => $this->bparams, 'RTEtsConfigParams' => $this->RTEtsConfigParams, 'expandPage' => $this->expandPage, 'expandFolder' => $this->expandFolder, 'PM' => $this->PM, 'SEL' => $this->SEL );
				$dblist->start ( $id, t3lib_div::_GP ( 'table' ), $pointer, t3lib_div::_GP ( 'search_field' ), t3lib_div::_GP ( 'search_levels' ), t3lib_div::_GP ( 'showLimit' ) );
				
				$dblist->setDispFields ();
				$dblist->generateList ( $id, $table );
				$dblist->writeBottom ();
			}
			
			// Add the HTML for the record list to output variable:
			$out .= $dblist->HTMLcode . $dblist->getSearchBox ();
		}
		
		// Return accumulated content:
		return $out;
	}
}
?>