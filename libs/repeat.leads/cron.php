<? 
require_once "/home/bitrix/www/local/db.php";

$connect->query("
	UPDATE b_uts_crm_lead 
	SET UF_CRM_DUPLICATE = '1'
	WHERE UF_CRM_DUPLICATE = ''
");

file_put_contents(__DIR__ . "/cronlog.txt", date("c"));