<?
class APICRM extends BaseObject{
	private $user_table = 'user';
	private $user_id_field = 'id';
	private $login_field = 'login';
	private $password_field = 'password';

	function __construct()
	{
		parent::__construct($this->getTableStructure());
	}

	protected function getTableStructure()
	{
		return array(
			'table' => "mod_apicrm_session",
			'cols' => array(
				'time_create' => array(
					'title' => "Дата создания",
					"type" => "timestamp",
					"default" => "CURRENT_TIMESTAMP",
				),
				'user_id' => array(
					'title' => "ID пользователя",
					'type' => "varchar",
					'length' => "100",
				),
				'ttl' => array(
					'title' => "Время жизни",
					'type' => "int",
					'null' => "NOT NULL",
					'default' => "0",
				),
			),
		);
	}

	public function setUserTableParams($table_name = 'user', $user_id_field = 'id', $login_field = 'login', $password_field = 'password')
	{
		$this->user_table = $table_name;
		$this->user_id_field = $user_id_field;
		$this->login_field = $login_field;
		$this->password_field = $password_field;
	}

	public function auth($login, $password_hash, $ttl = 0)
	{
		$login = addslashes($login);
		$password_hash = addslashes($password_hash);

		$result = SQL::returnByFilter(array(
			'select' => "{$this->user_id_field} as user_id",
			'from' => $this->user_table,
			'where' => array(
				$this->login_field => array(
					'value' => $login,
					'comparison' => '=',
					'logic' => 'AND'
				),
				$this->password_field => array(
					'value' => $password_hash,
					'comparison' => '=',
					'logic' => 'AND'
				),
			)
		));

		if ($result != -1)
		{
			$user_id = $result[0]['user_id'];

			$token = getHash();

			$query = parent::insert(array(
				'id' => $token,
				'user_id' => $user_id,
				'ttl' => $ttl
			));

			$result = SQL::query($query);

			LOG::debug(get_class($this)."::".__FUNCTION__."::query", $query);
			LOG::debug(get_class($this)."::".__FUNCTION__."::data", $data);

			if ($result)
			{
				return $token;
			}
		}

		return false;
	}

	public function checkToken($token)
	{
		$result = $this->getByColOne('id', $token);
		if ($result == -1)
		{
			return false;
		}

		$this->setCol($token, 'time_create', date('Y-m-d H:i:s'));

		return $result;
	}

	public function logout($token)
	{
		$result = $this->deleteById($token);

		if ($result)
		{
			return 1;
		}

		return 0;
	}

	public function getError($code)
	{
		$errors = array(
			400 => 'Incorrect request data',
			401 => 'Wrong login or password',
			402 => 'Invalid old password',
			403 => 'Old password is used',
			404 => 'Old phone number not found',
			405 => 'The old mailbox is not specified correctly or the new one is not specified'
		);

		if (isset($errors[$code]))
		{
			return array(
				'code' => $code,
				'message' => $errors[$code]
			);
		}

		return false;
	}
}
?>