{*
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
 *}
<script type="text/javascript" src="{sugar_getjspath file='include/connectors/formatters/default/company_detail.js'}"></script>
{literal}
<style type="text/css">
#dropboxpidgin_popup_div {
	min-width: 300px;
}
#dropboxpidgin_container {
	padding-right: 20px;
}
#dropboxpidgin_loading {
	text-align: center;
}
.dp_listitem {
	border-bottom: 1px solid #333333;
}
</style>
{/literal}
<script type="text/javascript">
var dropboxpidginModule = '{{$module}}';
var dropboxpidginRecord = '{{$record}}';
var dropboxpidginYahooIdValue = '{$fields.{{$yahoo_id_field_name}}.value|trim}';
var dropboxpidginHoverActivated = false;
{literal}

var Y_DBP = YUI().use('node', function (Y) {});

function show_ext_rest_dropboxpidgin(event)
{
	if(dropboxpidginHoverActivated == true) return;
	
	dropboxpidginHoverActivated = true; //prevent multiple requests in a row
	var xCoordinate = event.clientX;
	var yCoordinate = event.clientY;
	var isIE = document.all?true:false;
      
	if(isIE) 
	{
		xCoordinate = xCoordinate + document.body.scrollLeft;
		yCoordinate = yCoordinate + document.body.scrollTop;
	}


	cd = new CompanyDetailsDialog("dropboxpidgin_popup_div", '<div id="dropboxpidgin_container"></div><div id="dropboxpidgin_loading">{/literal}{sugar_image name="loading"}{literal}</div><div id="dropboxpidgin_container"></div>', xCoordinate, yCoordinate);
	cd.setHeader(dropboxpidginYahooIdValue);
	cd.display();
	
	//get the list of IM logs
	YAHOO.util.Connect.asyncRequest('GET', 'index.php?module=Connectors&action=CallConnectorFunc&source_id=ext_rest_dropboxpidgin&source_func=getLogs&yahoo_id='+dropboxpidginYahooIdValue, {
		success: function (o) {
			var data = YAHOO.lang.JSON.parse(o.responseText);

			Y_DBP.one("#dropboxpidgin_loading").hide();
			dropboxpidginHoverActivated = false;

			//build out the popup list
			var html = '';
			if(data.logs) {
				var logLength = data.logs.length;
				for(var i=0; i < logLength; i++) {
					//if(i % 50 == 0) {alert(data.logs[i].path);}
					html += '<li class="dp_listitem">';
					html += '<strong>Conversation:</strong>'+data.logs[i].path+'<br/>';
					html += '<strong>Size:</strong>'+data.logs[i].size+'<br/>';
					html += '<button onclick="archiveLog(\''+data.logs[i].path+'\')">Archive</button>';
					html += '</li>';
				}
			}
				
			document.getElementById("dropboxpidgin_container").innerHTML = '<ul id="dropboxpidgin_list"></ul>';	
			document.getElementById("dropboxpidgin_list").innerHTML += html;
		},
		failure: function(){
			Y_DBP.one("#dropboxpidgin_loading").hide();
			dropboxpidginHoverActivated = false;
		}
	});
	
}

function archiveLog(logPath){
	YAHOO.util.Connect.asyncRequest('POST', 'index.php', {
		success: function(o){
			var data = YAHOO.lang.JSON.parse(o.responseText);

			if (data.success == true){
				alert('Log has been archived as a note.');
				//reload subpanel - the true forces a reload
				showSubPanel('history',null,true,dropboxpidginModule);
			} else {
				alert('Error archiving log: '+data.errorMessage);
			}
			document.getElementById('dropboxpidgin_popup_div').style.display = 'none';
		},
		failure: function(o){
			alert('Error archiving log');
		}
	} 
	,"module=Connectors&action=CallConnectorFunc&source_id=ext_rest_dropboxpidgin&source_func=archiveLog&archiveModule="+dropboxpidginModule+"&archiveRecord="+dropboxpidginRecord+"&archivePath="+logPath);

}

{/literal}
</script>
