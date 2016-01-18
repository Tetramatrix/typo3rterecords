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

class ux_t3lib_parsehtml_proc extends t3lib_parsehtml_proc {

    	/**
	 * Parse <A>-tag href and return status of email,external,file or page
	 *
	 * @param	string		URL to analyse.
	 * @return	array		Information in an array about the URL
	 */
	function urlInfoForLinkTags($url,$forceEXT=0)	{
		$info = array();
		$url = trim($url);
		if (substr(strtolower($url),0,7)=='mailto:')	{
			$info['url']=trim(substr($url,7));
			$info['type']='email';
		
        	} else {
        
			if ($forceEXT) {
				$info['url']=$url;
				$info['type']='ext';
				return $info;
			}
		
			$curURL = $this->siteUrl(); 	// 100502, removed this: 'http://'.t3lib_div::getThisUrl(); Reason: The url returned had typo3/ in the end - should be only the site's url as far as I see...
			
			for($a=0;$a<strlen($url);$a++)	{
				if ($url[$a]!=$curURL[$a]) {
					break;
				}
			}

			$info['relScriptPath']=substr($curURL,$a);
			$info['relUrl']=substr($url,$a);
			$info['url']=$url;
			$info['type']='ext';

			$siteUrl_parts = parse_url($url);
			$curUrl_parts = parse_url($curURL);

				// Hosts should match
				// If the script path seems to match or is empty (FE-EDIT)
			if ($siteUrl_parts['host']==$curUrl_parts['host'] 	
				&& (!$info['relScriptPath']	|| (defined('TYPO3_mainDir') && substr($info['relScriptPath'],0,strlen(TYPO3_mainDir))==TYPO3_mainDir))) {	
		
						// New processing order 100502
					$uP=parse_url($info['relUrl']);
		
					if (!strcmp('#'.$siteUrl_parts['fragment'],$info['relUrl'])) {
						$info['url']=$info['relUrl'];
						$info['type']='anchor';
					} elseif (!trim($uP['path']) || !strcmp($uP['path'],'index.php'))	{
						$pp = explode('id=',$uP['query']);
						$id = trim($pp[1]);
						if ($id)	{
							$info['pageid']=$id;
							$info['cElement']=$uP['fragment'];
							$info['url']=$id.($info['cElement']?'#'.$info['cElement']:'');
							$info['type']='page';
						}
					} else {
						$info['url']=$info['relUrl'];
						$info['type']='file';
					}
			} else {

				unset($info['relScriptPath']);
				unset($info['relUrl']);
			}
		}
		return $info;
	}



	/**
	 * Transformation handler: 'ts_links' / direction: "db"
	 * Converting <A>-tags to <link tags>
	 *
	 * @param	string		Content input
	 * @return	string		Content output
	 * @see TS_links_rte()
	 */
	function TS_links_db($value)	{
        
			// Split content into <a> tag blocks and process:
		$blockSplit = $this->splitIntoBlock('A',$value);
        
		foreach($blockSplit as $k => $v)	{
			if ($k % 2){	// If an A-tag was found:
				
			$attribArray = $this->get_tag_attributes_classic($this->getFirstTag($v),1);
                
			$getParams = explode('&',$attribArray['href']);
	
			if (count($getParams)>1) {
				$info = $this->urlInfoForLinkTags($attribArray['href'],1);          
			} else {
				$info = $this->urlInfoForLinkTags($attribArray['href']);
			}
			
				// Check options:
			$attribArray_copy = $attribArray;
			unset($attribArray_copy['href']);
			unset($attribArray_copy['target']);
			unset($attribArray_copy['class']);
			unset($attribArray_copy['title']);
			
			if ($attribArray_copy['rteerror'])	{	// Unset "rteerror" and "style" attributes if "rteerror" is set!
				unset($attribArray_copy['style']);
				unset($attribArray_copy['rteerror']);
			}
			if (!count($attribArray_copy))	{	// Only if href, target and class are the only attributes, we can alter the link!
					// Creating the TYPO3 pseudo-tag "<LINK>" for the link (includes href/url, target and class attributes):
				$bTag='<link '.$info['url'].($attribArray['target']?' '.$attribArray['target']:(($attribArray['class'] || $attribArray['title'])?' -':'')).($attribArray['class']?' '.$attribArray['class']:($attribArray['title']?' -':'')).($attribArray['title']?' "'.$attribArray['title'].'"':'').'>';
				$eTag='</link>';
				$blockSplit[$k] = $bTag.$this->TS_links_db($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
			} else {	// ... otherwise store the link as a-tag.
					// Unsetting 'rtekeep' attribute if that had been set.
				unset($attribArray['rtekeep']);
					// If the url is local, remove url-prefix
				$siteURL = $this->siteUrl();
				if ($siteURL && substr($attribArray['href'],0,strlen($siteURL))==$siteURL)	{
					$attribArray['href']=$this->relBackPath.substr($attribArray['href'],strlen($siteURL));
				}
				$bTag='<a '.t3lib_div::implodeAttributes($attribArray,1).'>';
				$eTag='</a>';
				$blockSplit[$k] = $bTag.$this->TS_links_db($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
			}
		}
	}
	return implode('',$blockSplit);
}
    
    
	/**
	 * Transformation handler: 'ts_links' / direction: "rte"
	 * Converting <LINK tags> to <A>-tags
	 *
	 * @param	string		Content input
	 * @return	string		Content output
	 * @see TS_links_rte()
	 */
	function TS_links_rte($value)	{
		$value = $this->TS_AtagToAbs($value);

			// Split content by the TYPO3 pseudo tag "<LINK>":
		$blockSplit = $this->splitIntoBlock('link',$value,1);
		foreach($blockSplit as $k => $v) {
			$error = '';
			if ($k%2){	// block:
				$tagCode = t3lib_div::trimExplode(' ',trim(substr($this->getFirstTag($v),0,-1)),1);
				$link_param = $tagCode[1];
				$href = '';
				$siteUrl = $this->siteUrl();
				
					// Parsing the typolink data. This parsing is roughly done like in tslib_content->typolink()
				if(strstr($link_param,'@'))	{		// mailadr
					$href = 'mailto:'.eregi_replace('^mailto:','',$link_param);
				} elseif (substr($link_param,0,1)=='#') {	// check if anchor
					$href = $siteUrl.$link_param;
				} else {
					$fileChar=intval(strpos($link_param, '/'));
					$urlChar=intval(strpos($link_param, '.'));

						// Detects if a file is found in site-root OR is a simulateStaticDocument.
					list($rootFileDat) = explode('?',$link_param);
					$rFD_fI = pathinfo($rootFileDat);
					if (trim($rootFileDat) && !strstr($link_param,'/') && (@is_file(PATH_site.$rootFileDat) || 	t3lib_div::inList('php,html,htm',strtolower($rFD_fI['extension']))))	{
						$href = $siteUrl.$link_param;
					} elseif($urlChar && (strstr($link_param,'//') || !$fileChar || $urlChar<$fileChar))	{	// url (external): If doubleSlash or if a '.' comes before a '/'.
					
					if (!ereg('^[a-z]*://',trim(strtolower($link_param))))	{$scheme='http://';} else {$scheme='';}
						$href = $scheme.$link_param;
					
					} elseif($fileChar){	// file (internal)
						
						$href = $siteUrl.$link_param;
					
					} else {	

						// integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to tables.php!!)
						$link_params_parts = explode('#',$link_param);
						$idPart = trim($link_params_parts[0]);		// Link-data del
						if (!strcmp($idPart,'')) { $idPart=$this->recPid; }	// If no id or alias is given, set it to class record pid
						if ($link_params_parts[1] && !$sectionMark)	{
							$sectionMark = '#'.trim($link_params_parts[1]);
						}
							// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/? pair
						$pairParts = t3lib_div::trimExplode(',',$idPart);
						if (count($pairParts)>1)	{
							$idPart = $pairParts[0];
							// Type ? future support for?
						}
							// Checking if the id-parameter is an alias.
						if (!t3lib_div::testInt($idPart))	{
							list($idPartR) = t3lib_BEfunc::getRecordsByField('pages','alias',$idPart);
							$idPart = intval($idPartR['uid']);
						}                        
						$href = $siteUrl.'?id='.$link_param;
						
					}
				}

				// Setting the A-tag:
				$bTag = '<a href="'.htmlspecialchars($href).'"'.
							($tagCode[2]&&$tagCode[2]!='-' ? ' target="'.htmlspecialchars($tagCode[2]).'"' : '').
							($tagCode[3] ? ' class="'.htmlspecialchars($tagCode[3]).'"' : '').
							($error ? ' rteerror="'.htmlspecialchars($error).'" style="background-color: yellow; border:2px red solid; color: black;"' : '').	// Should be OK to add the style; the transformation back to databsae will remove it...
							'>';
				$eTag = '</a>';
				$blockSplit[$k] = $bTag.$this->TS_links_rte($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
			}
		}

			// Return content:
		return implode('',$blockSplit);
	}

}

?>