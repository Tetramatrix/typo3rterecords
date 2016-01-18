<?php
if (! defined ( 'TYPO3_MODE' ))
	die ( 'Access denied.' );

class tx_rterecords_handler {
	
	function main($linktxt, $conf, $linkHandlerKeyword, $linkHandlerValue, $link_param, &$pObj) {
		$this->pObj = &$pObj;
		
		$lconf = array ();
		
		$linkHandlerValue = t3lib_div::trimExplode ( ':', $linkHandlerValue );
		$res = preg_match ( '/(singlePID=)(\d+)/', $link_param, $matches );
		$link_param = preg_replace ( '/singlePID=\d+/', '', $link_param );
		$link_param = t3lib_div::unQuoteFilenames ( $link_param, true );
		
		$localcObj = t3lib_div::makeInstance ( 'tslib_cObj' );
		$recordRow = $this->getRecordRow ( $linkHandlerValue [0], $linkHandlerValue [1] );
		$localcObj->start ( $recordRow, '' );
		
		$linkClass = trim ( $link_param [3] ); // Link class
		if ($linkClass == '-')
			$linkClass = ''; // The '-' character means 'no class'. Necessary in order to specify a title as fourth parameter without setting the target or class!
		$forceTarget = trim ( $link_param [2] ); // Target value
		$forceTitle = trim ( $link_param [4] ); // Title value
		
		if ($forceTarget == '-')
			$forceTarget = ''; // The '-' character means 'no target'. Necessary in order to specify a class as third parameter without setting the target!
		
			// Check, if the target is coded as a JS open window link:
		$JSwindowParts = array ();
		$JSwindowParams = '';
		$onClick = '';
		if ($forceTarget && ereg ( '^([0-9]+)x([0-9]+)(:(.*)|.*)$', $forceTarget, $JSwindowParts )) {
			// Take all pre-configured and inserted parameters and compile parameter list, including width+height:
			$JSwindow_tempParamsArr = t3lib_div::trimExplode ( ',', strtolower ( $conf ['JSwindow_params'] . ',' . $JSwindowParts [4] ), 1 );
			$JSwindow_paramsArr = array ();
			foreach ( $JSwindow_tempParamsArr as $JSv ) {
				list ( $JSp, $JSv ) = explode ( '=', $JSv );
				$JSwindow_paramsArr [$JSp] = $JSp . '=' . $JSv;
			}
			// Add width/height:
			$JSwindow_paramsArr ['width'] = 'width=' . $JSwindowParts [1];
			$JSwindow_paramsArr ['height'] = 'height=' . $JSwindowParts [2];
			
			// Imploding into string:
			$JSwindowParams = implode ( ',', $JSwindow_paramsArr );
			$forceTarget = ''; // Resetting the target since we will use onClick.
		}
		
		if ($forceTitle) {
			$title = $forceTitle;
		}
		
		if ($JSwindowParams) {
			
			// Rendering the tag.
			$finalTagParts ['url'] = $localcObj->lastTypoLinkUrl;
			$finalTagParts ['targetParams'] = $targetPart;
			$finalTagParts ['TYPE'] = 'page';
			
			// Create TARGET-attribute only if the right doctype is used
			if (! t3lib_div::inList ( 'xhtml_strict,xhtml_11,xhtml_2', $GLOBALS ['TSFE']->xhtmlDoctype )) {
				$target = ' target="FEopenLink"';
			} else {
				$target = '';
			}
			
			$lconf ['target'] = $target;
			
			// Title tag
			if ($link_param [4]) {
				$lconf ['title'] = $link_param [4];
			}
			
			// Class
			if ($linkClass) {
				$lconf ['ATagParams'] = 'class=' . $linkClass;
			}
			
			$lconf ['parameter'] = $matches [2];
			$lconf ['additionalParams'] = $link_param [1];
			$lconf ['additionalParams.'] ['insertData'] = 1;
			
			// Rendering the tag.
			$finalTagParts ['url'] = $localcObj->typoLink_URL ( $lconf );
			#                        $finalTagParts['targetParams']=$targetPart;
			$finalTagParts ['TYPE'] = 'page';
			
			$onClick = "vHWin=window.open('" . $GLOBALS ['TSFE']->baseUrlWrap ( $finalTagParts ['url'] ) . "','FEopenLink','" . $JSwindowParams . "');vHWin.focus();return false;";
			$res = '<a href="' . htmlspecialchars ( $finalTagParts ['url'] ) . '"' . $target . ' onclick="' . htmlspecialchars ( $onClick ) . '"' . ($title ? ' title="' . $title . '"' : '') . ($linkClass ? ' class="' . $linkClass . '"' : '') . $finalTagParts ['aTagParams'] . '>';
			
			if ($lconf ['ATagBeforeWrap']) {
				return $res . $localcObj->wrap ( $linktxt, $lconf ['wrap'] ) . '</a>';
			} else {
				return $localcObj->wrap ( $res . $linktxt . '</a>', $lconf ['wrap'] );
			}
		
		}
		
		// Internal target:
		if ($link_param [2] != '-') {
			$lconf ['target'] = $link_param [2];
		}
		
		// Title tag
		if ($link_param [4]) {
			$lconf ['title'] = $link_param [4];
		}
		
		// Class
		if ($linkClass) {
			$lconf ['ATagParams'] = 'class=' . $linkClass;
		}
		
		$lconf ['parameter'] = $matches [2];
		$lconf ['additionalParams'] = $link_param [1];
		$lconf ['additionalParams.'] ['insertData'] = 1;
		
		return $localcObj->typoLink ( $linktxt, $lconf );
	
	}
	
	function getRecordRow($table, $uid) {
		$res = $GLOBALS ['TYPO3_DB']->exec_SELECTquery ( '*', $table, 'uid=' . intval ( $uid ) . $this->pObj->enableFields ( $table ), '', '' );
		$row = $GLOBALS ['TYPO3_DB']->sql_fetch_assoc ( $res );
		return $row;
	}

}

?>
