Drop the files into the root directory of a SugarCRM instance.

Below are the high-level steps needed to get this connector configured:

* Do not use localhost in config.php->site_url. Edit your host file and point something like sugar.dev to 127.0.0.1. This is required for OAuth and Dropbox to play nicely.
* Make sure site_url contains your new host configured in previous step
* Create a dropbox app at https://www.dropbox.com/developers/apps
* Note the [App name], [App key], and [App secret]
* In Sugar go Admin->System->Connectors->Set Connector Properties
* Go to the DropboxPidgin tab, enter [App key], [App secret], and save
* Now go to Enable Connectors, DropboxPidgin tab, enable modules as needed (such as Contacts), and save
* Create a custom yahoo_id field in Studio and add to the detail/edit views
* Go to Admin->System->Connectors->Map Connector Fields, DropboxPidgin tab, and map the Connector's Yahoo! ID to your newly created Yahoo! field. Save.
* Admin->Repair->Quick Repair/Rebuild (so that this connector shows in External Accounts)
* Go to your user account (link at top in Sugar), click on External Accounts, and Create
* Select DropboxPidgin from dropdown. Enter DropboxPidgin for both App User Name and App Password. Enter https://www.dropbox.com/1/oauth/authorize for the url.
* Click Connect - make sure popup blocker is off.
* Grant Access to Dropbox. This creates the Apps/[App name] folder in your Dropbox.
* Status should now be "Connected"
* Go back to your account, set IM Type to "Yahoo!", enter your IM Name, and save.
* Make sure to turn IM logging on in Pidgin and point it to your Apps/[App name] directory that was created automatically above. For example, in Windows:

	http://developer.pidgin.im/wiki/Using%20Pidgin#Wherearemysettingsanddataincludinglogssaved
	Copy C:\Documents and Settings\...\Application Data\.purple and move to DropBox\\Apps\\DropboxPidgin folder
	Rename old .purple to .purple_dropbox (for posterity) (rename .purple .purple_dropbox)
	Add PURPLEHOME environment variable to C:\Documents and Settings\....\My Documents\Dropbox\Apps\DropboxPidgin

* Now grab a beer, because gosh darn it, you deserve it!	
