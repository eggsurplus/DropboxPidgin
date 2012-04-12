<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2012 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

/*********************************************************************************

 * Description:
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): Jason Eggers (www.eggsurplus.com)
 ********************************************************************************/

require_once('include/externalAPI/Base/OAuthPluginBase.php');

class ExtAPIDropboxPidgin extends OAuthPluginBase {
    public $authMethod = 'oauth';
    public $useAuth = true;
    public $requireAuth = true;
    protected $authData;
    public $needsUrl = true; 
    public $supportedModules = array();
    public $connector = 'ext_rest_dropboxpidgin';
    public $url = '';
    public $content_url = '';

	//Required for OAuth:
	protected $oauthReq = 'oauth/request_token';
    protected $oauthAuth = 'https://www.dropbox.com/1/oauth/authorize';
    protected $oauthAccess = 'oauth/access_token';
    protected $oauthParams = array(
    	'signatureMethod' => 'HMAC-SHA1', //also supports 'PLAINTEXT'
    );

    function __construct() 
	{
        $this->url = $this->getConnectorParam('dropboxpidigin_api_url');
        $this->content_url = $this->getConnectorParam('dropboxpidigin_api_content_url');
        $this->oauthReq = $this->url.$this->oauthReq;
        $this->oauthAccess = $this->url.$this->oauthAccess;

        parent::__construct();
    }

    protected function getClient() 
	{
		return $this->getOauth($this)->getClient();
    }
    
    protected function makeRequest($requestMethod, $urlPath, $urlParams = null, $postData = null) 
	{
    	//to prevent possible error Class 'SugarOAuth' not found in ...\OAuthPluginBase.php
		require_once('include/SugarOauth.php');
    	
		$client = $this->getClient();
        
		$client->setUri($this->url.$urlPath);

		$clientReply = $client->request($requestMethod);
		$rawResponse = $clientReply->getBody();   
		if( !$clientReply->isSuccessful() || empty($rawResponse) ) {
			throw new Exception("Dropbox Error: Failed to provide a response");
		}
        
		$jsonResponse = json_decode($rawResponse,true);
        
		if( !is_array($jsonResponse) ) {
			throw new Exception("Dropbox Error: Failed to provide a response");
		}

		return $jsonResponse;     
    }

    protected function getFileRequest($requestMethod, $urlPath, $urlParams = null, $postData = null) 
	{
    	//to prevent possible error Class 'SugarOAuth' not found in ...\OAuthPluginBase.php
		require_once('include/SugarOauth.php');
    	
		$client = $this->getClient();
        
		$client->setUri($this->content_url.$urlPath);

		$clientReply = $client->request($requestMethod);
		$rawResponse = $clientReply->getBody();  

		if( !$clientReply->isSuccessful() || empty($rawResponse) ) {
			throw new Exception("Dropbox Error: Failed to provide a response");
		}
        
		$metadata = json_decode($clientReply->getHeader('x-dropbox-metadata'));

		return array('metadata'=>$metadata,'content'=>$rawResponse);     
    }
    
    public function checkLogin($eapmBean = null) 
	{
		try {
			$reply = parent::checkLogin($eapmBean);
            
            if ( !$reply['success'] ) {
				return $reply;
			}
            
			$userInfo = $this->makeRequest('account/info');
            
			if( !isset($userInfo['uid']) ) {
				throw new Exception("Dropbox did not accept your secret handshake.");
			}
		} catch (Exception $e) {
			return array('success'=>false,'errorMessage'=>$e->getMessage());
		}
        
		return array('success'=>true);
        
    }

	//override
   public function oauthLogin()
   {
        global $sugar_config;
        $oauth = $this->getOauth();
        //eggsurplus: dropbox does not return oauth_verifier. Use uid instead
        //if(isset($_SESSION['eapm_oauth_secret']) && isset($_SESSION['eapm_oauth_token']) && isset($_REQUEST['oauth_token']) && isset($_REQUEST['oauth_verifier'])) {
        if(isset($_SESSION['eapm_oauth_secret']) && isset($_SESSION['eapm_oauth_token']) && isset($_REQUEST['oauth_token']) && isset($_REQUEST['uid'])) {
            $stage = 1;
        } else {
            $stage = 0;
        }
        if($stage == 0) {
            $oauthReq = $this->getOauthRequestURL();
            //eggsurplus: urlencode the params..change the order
            $callback_url = '&action=oauth&record='.$this->eapmBean->id;
            $callback_url = $this->formatCallbackURL($callback_url);
            $callback_url = $sugar_config['site_url'].'/index.php?module=EAPM'.urlencode($callback_url);

            $GLOBALS['log']->debug("OAuth request token: {$oauthReq} callback: $callback_url");

            $request_token_info = $oauth->getRequestToken($oauthReq, $callback_url);

            $GLOBALS['log']->debug("OAuth token: ".var_export($request_token_info, true));
            if(empty($request_token_info['oauth_token_secret']) || empty($request_token_info['oauth_token'])){
                return false;
            }else{
                // FIXME: error checking here
                $_SESSION['eapm_oauth_secret'] = $request_token_info['oauth_token_secret'];
                $_SESSION['eapm_oauth_token'] = $request_token_info['oauth_token'];
                $authReq = $this->getOauthAuthURL();
				//eggsurplus: adding oauth_callback
                SugarApplication::redirect("{$authReq}?oauth_token={$request_token_info['oauth_token']}&oauth_callback=".$callback_url);
            }
        } else {
            $accReq = $this->getOauthAccessURL();
            $oauth->setToken($_SESSION['eapm_oauth_token'],$_SESSION['eapm_oauth_secret']);
            $GLOBALS['log']->debug("OAuth access token: {$accReq}");
            $access_token_info = $oauth->getAccessToken($accReq);
            $GLOBALS['log']->debug("OAuth token: ".var_export($access_token_info, true));
            // FIXME: error checking here
            $this->oauth_token = $access_token_info['oauth_token'];
            $this->oauth_secret = $access_token_info['oauth_token_secret'];
            $this->eapmBean->oauth_token = $this->oauth_token;
            $this->eapmBean->oauth_secret = $this->oauth_secret;
            $oauth->setToken($this->oauth_token, $this->oauth_secret);
            $this->eapmBean->validated = 1;
            $this->eapmBean->save();
            unset($_SESSION['eapm_oauth_token']);
            unset($_SESSION['eapm_oauth_secret']);
            return true;
        }
        return false;
	}
	
    //add all functions in this class below that interact with the Dropbox API
    //For additional help:
    //http://developers.sugarcrm.com/docs/OS/6.3/-docs-Developer_Guides-Sugar_Developer_Guide_6.3.0-Sugar_Developer_Guide_6.3.0.html
    //search for Cloud Connectors Framework and OAuthPluginBase
    
	public function getLogs($yahoo_id)
	{
		global $current_user;
		if($current_user->messenger_type != 'Yahoo!' || empty($current_user->messenger_id)) {
			return array('success'=>FALSE,'errorMessage'=>'Set your Yahoo! information on your User record');
		}
		try {
			$params = array('file_limit'=>'10');
			//ignore Apps/DropboxPidgin as it assumes that for the Dropbox application
			$urlPath = 'metadata/sandbox/.purple/logs/yahoo/'.$current_user->messenger_id.'/'.$yahoo_id.'/';
			$response = $this->makeRequest("GET", $urlPath, $params);
			$logs = $this->formatLogList($response);
			return array('success'=>TRUE,'logs'=> $logs);
		} catch (Exception $e) {
			$errorMessage = "Unable to retrieve item list for DropboxPidgin connector.";
			$GLOBALS['log']->fatal($errorMessage.': '.$e->getMessage());
			return array('success'=>FALSE,'errorMessage'=>$errorMessage);
		}		
	}

	//See following for response format:
	//https://www.dropbox.com/developers/reference/api#metadata
	public function formatLogList($response)
	{
		$logs = array();
		foreach($response['contents'] as $content) {
			//reformat data here as needed
			$logs[] = array(
				'path' => $content['path'],
				'size' => $content['size'],
				'modified' => $content['modified'],
			);
		}
		//flip to newest first
		$logs = array_reverse($logs);
		
		return $logs;
	}

	public function archiveLog($module, $record, $path) 
	{
		$response = array();
		try {
			//get the file from Dropbox
			//See https://www.dropbox.com/developers/reference/api#files-GET
			$params = array();
			$urlPath = 'files/sandbox'.$path;
			$response = $this->getFileRequest("GET", $urlPath, $params);
		} catch (Exception $e) {
			$GLOBALS['log']->fatal($e->getMessage());
			return array('success'=>FALSE,'errorMessage'=>$e->getMessage());
		}	
		
		//save as a note and attached to the appropriate record
		
		//first load the parent bean
		global $beanList, $current_user;
		//Generic method of loading a bean
		$parent_bean = new $beanList[$_REQUEST['archiveModule']];

		require_once('modules/Notes/Note.php');
		
		$note = new Note();
		$note->parent_id = $record;
		//Need to capture special handling for a Contact note
		if($parent_bean->module_dir == 'Contacts') {
			$note->contact_id = $record;
		}
		$note->assigned_user_id = $current_user->id;
		$note->parent_type = $parent_bean->module_dir;
		$note->name = $path;
		$note->description = $response['content'];
		$note->save();

		if(!empty($note->id)) {
			return array('success'=>TRUE);
		} else {
			return array('success'=>FALSE,'errorMessage'=>'Archived note failed to save.');
		}
	}	

}
