<?php
$skroutzMappings = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/../storage/data/skroutz_api_mappings.json');
return json_decode($skroutzMappings, true);
