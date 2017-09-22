<?php
if(!defined("__XE__"))
	exit();
/**
* @file to_sns.addon.php
* @author Wincomi (admin@wincomi.com)
* @brief Send article to sns
*/	

if($addon_info->only_admin != "N")
{
	$logged_info = Context::get("logged_info");
	if($logged_info->is_admin!="Y") return; // 관리자가 아니면 return
}

if(Context::get("act") == "procBoardInsertDocument" && $called_position == "after_module_proc" && $this->toBool())
{
	$document_srl = $this->get("document_srl");
	$oDocumentModel = &getModel("document");
	$oDocument = $oDocumentModel->getDocument($document_srl);
	$oDocument->setDocument($document_srl); // 첨부파일이 있는 글의 정보를 가져올 수 없는 문제 해결
	
	if($oDocument->isExists() && $oDocument->get('status') !== 'TEMP') {
		return;
	}
	
	$document_title = $oDocument->variables["title"];
	$document_url = getFullUrl("", "document_srl", $oDocument->variables["document_srl"]);
		
		
	// 글자 수 자르기
	function strcut_utf8($str, $len, $checkmb = false, $tail = "") {
		preg_match_all("/[\xE0-\xFF][\x80-\xFF]{2}|./", $str, $match); // target for BMP
		
		$m = $match[0];
		$slen = strlen($str); // length of source string
		$tlen = strlen($tail); // length of tail string
		$mlen = count($m); // length of matched characters
		
		if ($slen <= $len) return $str;
		if (!$checkmb && $mlen <= $len) return $str;
		
		$ret = array();
		$count = 0;
		for ($i=0; $i < $len; $i++) {
		    $count += ($checkmb && strlen($m[$i]) > 1)?2:1;
		    if ($count + $tlen > $len) break;
		    $ret[] = $m[$i];
		}
		
		return join("", $ret).$tail;
	}
	
	if(mb_strlen($document_title, "UTF-8") + strlen($document_url) >= 139)
	{
		// 공백 포함
		$document_title = strcut_utf8($document_title, 139 - strlen($document_url));
	}
	
	$message = $document_title." ".$document_url;

	// Twitter
	if($addon_info->use_twitter == "Y" && $addon_info->tt_consumer_key && $addon_info->tt_consumer_secret && $addon_info->tt_access_token && $addon_info->tt_access_token_secret)
	{
		require_once('twitteroauth/twitteroauth.php');
		require_once('twitteroauth/OAuth.php');
		$tt_oauth = new TwitterOAuth($addon_info->tt_consumer_key, $addon_info->tt_consumer_secret, $addon_info->tt_access_token, $addon_info->tt_access_token_secret);
		$tt_oauth->post("statuses/update", array("status" => $message));
	}
}

/* End of file to_sns.addon.php */
/* Location: ./addons/to_sns */