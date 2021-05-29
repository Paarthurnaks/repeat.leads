<?

/**
 * Функционал повторных лидов
 */

namespace BitrixCustom\RepeatLeads;

$eventManager = \Bitrix\Main\EventManager::getInstance();

// Вешаем необходимые события при построении пользовательских полей, а также после создания лида
$eventManager->addEventHandlerCompatible('main', 'OnUserTypeBuildList', array('\BitrixCustom\RepeatLeads\MyUserType', 'GetUserTypeDescription'));
$eventManager->addEventHandlerCompatible('crm', 'OnAfterCrmLeadAdd', array('\BitrixCustom\RepeatLeads\MyUserType', 'updateNewLead'));

// Подключаем свой CSS файл
$APPLICATION->setAdditionalCss('/local/customField/main.css');

class MyUserType extends \Bitrix\Main\UserField\TypeBase
{
	public static $title = "Повторный лид";
	public static $subtitle = "Есть другие лиды с такими контактными данными";

    /**
     * получение html
     * @param $array
     * @return string
     */
	public static function getHtml($array) {
		$html = 
		'<div class="duplicate">
			<p class="duplicate_title">
				<b>'.self::$title.'</b>
			</p>
			<p class="dublicate_subtitle">
				<b>'.self::$subtitle.'</b>
			</p>
			<div class="duplicate_info"><br>';		

		$userList = static::GetUserList();

		foreach ($array as $key => $ind) {
			$date = new \Bitrix\Main\Type\DateTime($ind['DATE_CREATE']);

			$html .= 
			'<div class="duplicate_info_item">
				<p>					
					<b>Лид:</b>
					<a target="_blank" href="/crm/lead/details/'.$key.'/">'.$ind["TITLE"].'</a>
				</p>
				<p>
					<b>Ответственный:</b>
					<a target="_blank" href="/company/personal/user/'.$ind["ASSIGNED_BY_ID"].'/">'
						.$userList[$ind["ASSIGNED_BY_ID"]]['NAME']. ' '. $userList[$ind["ASSIGNED_BY_ID"]]['LAST_NAME'].
					'</a>
				</p>
				<p>
					<b>Дата поступления лида:</b>
					<a>'.$date->toString().'</a>
				</p>
				<br>
			</div>';
		}
		
		$html .= 				
			'</div>
		</div>';
		
		return $html;
		
	}

    /**
     * @return array
     */
	function GetUserTypeDescription() {
		return array(
			'USER_TYPE_ID' => 'myusertype2',
			'CLASS_NAME' => __CLASS__ ,
			'DESCRIPTION' => 'Кастомное поле',
			'BASE_TYPE' => 'string',
			'EDIT_CALLBACK' => array(__CLASS__, 'GetPublicEdit'),
			'VIEW_CALLBACK' => array(__CLASS__, 'GetPublicView'),
			'SETTINGS' => array(
				'DEFAULT_VALUE' => '1',
				'VALUE' => '1'
			),
			'VALUE' => '1'
		);
	}

    /**
     * @return string
     */
	function GetDBColumnType() {
		global $DB; 
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "text";
			case "oracle":
				return "varchar2(2000 char)";
			case "mssql":
				return "varchar (2000)";
		}
	}

    /**
     * функция для логирования
     * @param $array
     */
	public static function Log($array) {
		file_put_contents(__DIR__ . "/Logs.txt", print_r($array,true));
	}

    /**
     * @param $arUserField
     * @param array $arAdditionalParameters
     * @return string
     */
	public static function GetPublicView($arUserField, $arAdditionalParameters = array()) {
		$LeadId = static::GetLeadId(); // Получить ID сделки
		$LeadInfo = static::GetLeadInfo($LeadId); // Получить информацию о сделке
		$GetDuplicate = static::GetDuplicate($LeadInfo); // Получить все дубликаты сделки

		if (count($GetDuplicate) > 0) {
			$html = static::getHtml($GetDuplicate);	// ХТМЛ блока
		}
		else {
			$html = '<p>Повторные лиды не найдены</p>.';
		}
		static::updateLead($LeadId);
		return $html; 
	}

    /**
     * @param $arUserField
     * @param array $arAdditionalParameters
     * @return string
     */
	public static function GetPublicEdit($arUserField, $arAdditionalParameters = array()) {			
		$name = $arUserField['FIELD_NAME'];

		$LeadId = static::GetLeadId(); // Получить ID сделки
		$LeadInfo = static::GetLeadInfo($LeadId); // Получить информацию о сделке
		$GetDuplicate = static::GetDuplicate($LeadInfo); // Получить все дубликаты сделки

		if (count($GetDuplicate) > 0) {			
			$html = static::getHtml($GetDuplicate);	// ХТМЛ блока

		}
		else {
			$html = '<p>Повторные лиды не найдены</p>.';
		}
		static::updateLead($LeadId);
		return '<input type="hidden" name="UF_CRM_DUPLICATE" value="1" />' . $html; 
	}

    /**
     * @param $values
     */
	public static function updateNewLead($values) {

		if (\Bitrix\Main\Loader::includeModule('crm')) 
		{ 
			$entity = new \CCrmLead(false);
			$fields = array( 
				'UF_CRM_DUPLICATE' => '1' 
			); 
			$entity->update($values['ID'], $fields); 			
		}
	}

    /**
     * @param $LeadId
     */
	public static function updateLead($LeadId) {
		if (\Bitrix\Main\Loader::includeModule('crm')) 
		{ 
			$entity = new \CCrmLead(false);
			$fields = array( 
				'UF_CRM_DUPLICATE' => '1' 
			); 
			$entity->update($LeadId, $fields); 			
		}
	}

    /**
     * @return false|string
     */
	public static function GetLeadId() {
		$url = $_SERVER['HTTP_REFERER'];

		if ($url != '') {
			if (strpos($url, '/crm/lead/details/') != false) {
				$pos = strpos($url, '/crm/lead/details/');
				$leadId = substr($url, $pos + 18 );
				$leadId = substr($leadId, 0, -1);

				return $leadId;
			}
		}
	}

    /**
     * @param $LeadInfo
     * @return array
     */
	public static function GetDuplicate($LeadInfo) {
		$email = $LeadInfo['EMAIL'];
		$phone = str_replace(' ', '',$LeadInfo['PHONE']);
		$id = $LeadInfo['ID'];

		$Leads = array();

		if ($email != '') {
			if ( \Bitrix\Main\Loader::IncludeModule('crm') )
			{
				$resContacts = \Bitrix\Crm\LeadTable::getList(array(
					'select' => array('*', "PHONE", "EMAIL"),
					'filter' => array(
						"EMAIL" => $email,
						"!ID" => $id
					),
					'order' => array('ID' => 'DESC')
				));

				while( $arContact = $resContacts->fetch() )
				{
					$Leads[$arContact['ID']] = $arContact;
				}				

			}
		}

		if ($phone != '') {
			if ( \Bitrix\Main\Loader::IncludeModule('crm') )
			{
				$resContacts = \Bitrix\Crm\LeadTable::getList(array(
					'select' => array('*', "PHONE", "EMAIL"),
					'filter' => array(
						"PHONE" => $phone,
						"!ID" => $id
					),
					'order' => array('ID' => 'DESC')
				));

				while( $arContact = $resContacts->fetch() )
				{
					$Leads[$arContact['ID']] = $arContact;
				}
			}
		}

		return $Leads;
	}

    /**
     * @param $LeadId
     * @return mixed
     */
	public static function GetLeadInfo($LeadId) {	

		/* @var array Список контактов */
		$arContacts = array();

		if ( \Bitrix\Main\Loader::IncludeModule('crm') )
		{
			$resContacts = \Bitrix\Crm\LeadTable::getList(array(
				'select' => array('*', "PHONE", "EMAIL", 'UF_*'),
				'filter' => array(
					"ID" => $LeadId,
				),
				'order' => array('ID' => 'DESC')
			));

			while( $arContact = $resContacts->fetch() )
			{
				$arContacts[] = $arContact;
			}

			$LeadInfo = $arContacts[0];

			return $LeadInfo;
		}
	}

    /**
     * @return array
     */
	public static function GetUserList() {
		$result = \Bitrix\Main\UserTable::getList(array(
			'select' => array('ID', 'NAME', 'LAST_NAME'), 
			'order' => array('ID'=>'ASC'), 			
		));

		$userList = array();

		while ($arUser = $result->fetch()) {

			$userList[ $arUser['ID'] ] = $arUser;

		}

		return $userList;		
	}

    /**
     * По умолчанию Битрикс не выводит поля если они не заполнены. Данный метод требуется выполнить
     * при первой установке, чтобы он вручную проставил у всех лидов значение '1' у всех лидов.
     */
	public static function updateAllLeads ()
    {
        $connection = \Bitrix\Main\Application::getConnection();

        $connection->query("
        	UPDATE b_uts_crm_lead 
        	SET UF_CRM_DUPLICATE = '1'
        	WHERE UF_CRM_DUPLICATE = ''
        ");
    }
}
