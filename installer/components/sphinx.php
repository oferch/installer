<?php

if(AppConfig::get(AppConfigAttribute::UPGRADE_FROM_VERSION))
{
	Logger::logMessage(Logger::LEVEL_INFO, "Populating old content to the sphinx");
	$appDir = AppConfig::get(AppConfigAttribute::APP_DIR);
	$populateScripts = array(
		"$appDir/deployment/base/scripts/populateSphinxCategories.php",
		"$appDir/deployment/base/scripts/populateSphinxEntries.php",
		"$appDir/deployment/base/scripts/populateSphinxKusers.php",
		"$appDir/deployment/base/scripts/populateSphinxCaptionAssetItem.php",
		"$appDir/deployment/base/scripts/populateSphinxCategoryKusers.php",
		"$appDir/deployment/base/scripts/populateSphinxCuePoints.php",
		"$appDir/deployment/base/scripts/populateSphinxEntryDistributions.php",
		"$appDir/deployment/base/scripts/populateSphinxTags.php",
	);
	
	foreach($populateScripts as $populateScript)
	{
		if (!OsUtils::execute($populateScript)){
			return "Failed running sphinx populate script [$populateScript]";
		}
	}
}

return true;
