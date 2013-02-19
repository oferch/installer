<?php

logMessage(L_USER, "Creating databases and database users");

$dir = __DIR__ . '/../dbSchema';
if(!OsUtils::phing($dir))
	return 'Failed creating databases.';
	
return true;
