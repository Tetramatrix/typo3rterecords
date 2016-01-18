<?php

if (!defined ("TYPO3_MODE")) die ("Access denied.");

	// add linkhandler for "record"
if (t3lib_div::int_from_ver(TYPO3_version) >= t3lib_div::int_from_ver('4.2.0')) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = t3lib_extMgm::extPath($_EXTKEY).'class.tx_rterecords_handler.php:&tx_rterecords_handler';
}

 	// support for tinyrte
if( t3lib_extMgm::isLoaded('tinyrte') ) {	
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tinyrte/mod1/browse_links.php']=t3lib_extMgm::extPath($_EXTKEY).'tinyrte/ux_browse_links.php';
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php']=t3lib_extMgm::extPath($_EXTKEY).'rtehtmlarea/1.7.7/class.ux_t3lib_parsehtml_proc.php';
}

	// support for rtehtmlarea
if( t3lib_extMgm::isLoaded('rtehtmlarea') ) {
	if (t3lib_div::int_from_ver(TYPO3_version) <= t3lib_div::int_from_ver('3.8.2')) {	
    		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/browse_links.php']=t3lib_extMgm::extPath($_EXTKEY).'rtehmtarea/1.0.0/ux_browse_links.php';
			$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php']=t3lib_extMgm::extPath($_EXTKEY).'rtehmtarea/1.0.0/class.ux_t3lib_parsehtml_proc.php';
   	} else {
    		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/browse_links.php']=t3lib_extMgm::extPath($_EXTKEY).'rtehtmlarea/1.7.7/class.ux_tx_rtehtmlarea_browse_links.php';
    		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php']=t3lib_extMgm::extPath($_EXTKEY).'rtehtmlarea/1.7.7/class.ux_t3lib_parsehtml_proc.php';
    }
}

?>
