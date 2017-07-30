<?php 	
	class DB{
		private $pdo = null;
		private $tabName;
		private $dsn;
		private $field;
		private $where = '';
		private $map = array();
		private $w_save = array();
		private $sql = array(
			'way' => '*',
			'limit'=>'',
			'order'=>''
			);
		public function __construct($dsn,$tabName)
		{
			$this->dsn = $dsn;
			try {
				$this->pdo = new PDO($this->dsn,'jack','123456'); 
			} catch (PDOException $pe) {
				die('连接失败' . $pe->getMessage());
			}
			// 设置错误模式
			$this->pdo->exec("SET NAMES 'utf8';");
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			$this->tabName = $tabName;
			$this->desc(); // 获取字段
		}
		// 查询
		public function select($row = false){
			$sql = "select {$this->sql['way']} from `{$this->tabName}` {$this->where} {$this->sql['order']} {$this->sql['limit']}";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($this->map);
			$list = $stmt->fetchAll(2);
			// 清空条件，以免下次操作被污染
			$this->map = array();
			$this->where = '';
			$this->w_save = array();
			foreach ($this->sql as $key => $val) {
				if ($key == 'way') {
					$this->sql[$key] = '*';
				}else{
					$this->sql[$key] = '';
				}
			}
			if ($row) {
				return $stmt->rowCount();
			}
			return $list;
		}
		public function rowsCount(){
			return $this->pdo->rowCount();
		}
		// 添加数据
		public function add(array $data){
			$data = $this->fie($data);
			$key = array_keys($data);
			$val = join(" , :",$key);
			$key = join("`,`",$key);
			$sql = "insert into `{$this->tabName}` (`{$key}`) values(:{$val})";
			$stmt = $this->pdo->prepare($sql);
			try {
				$stmt->execute($data);
			} catch (PDOException $e) {
				return $e->getMessage();
			}
			return $this->pdo->lastInsertId();
		}
		// 更新字段
		public function save(array $data){
			$data = $this->fie($data);
			$map = '';
			foreach ($data as $key => $val) {
				$map .= "`{$key}` = :{$key} , ";
			}
			$map = rtrim($map , ' , ');
			$sql = "update `{$this->tabName}` set {$map} {$this->w_save}";
			$stmt = $this->pdo->prepare($sql);
			/* 清空条件，以免下次操作被污染 */
			$this->w_save = array();
			$this->where = '';
			$this->map = array();
			try {
				$stmt->execute($data);
			} catch (PDOException $pe) {
				return $pe->getMessage();
			}
			return $stmt->rowCount();
		}

		// 获取总数
		public function count(){
			$sql = "select count(*) from `{$this->tabName}` {$this->where}";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($this->map);
			$list = $stmt->fetchAll(2)[0]['count(*)'];
			$this->map = array();
			$this->where = '';
			$this->w_save = array();
			return $list;
		}
		// 删除
		public function delete(){

			$sql = "delete from `{$this->tabName}` {$this->where} ";
			$stmt = $this->pdo->prepare($sql);
			$this->where = '';
			$this->w_save = array();
			try {
				$stmt->execute($this->map);
			} catch (PDOException $e) {
				$this->where = '';
				$this->w_save = array();
				$this->map = array();
				return $e->getMessage();
			}
			// 清空条件，以免下次操作被污染
			$this->map = array();
			return $stmt->rowCount();
		}
		// 开启一个事务
		public function beginTransaction(){
			$this->pdo->beginTransaction();
		}
		// 提交事务
		public function commit(){
			if ($this->pdo->commit()) {
				return true;
			}else{
				return false;
			}
		}
		// 回滚事务
		public function rollBack(){
			if ($this->pdo->rollBack()) {
				return true;
			}else{
				return false;
			}
		}
		// 条件
		public function where(array $map,$par = ''){
			if(!empty($map)){
			// 过滤字段
			$map = $this->fie($map);
			$k = [];
			foreach ($map as $key => $val) {
				$val[0] = strtolower($val[0]);
				switch ($val[0]) {
					case 'eq':
						$k[] = "`{$key}` = :{$key}";
						$this->w_save[] = "`{$key}` = '{$val[1]}'";
						$this->map[$key] = $val[1];
						break;
					case 'gt':
						$k[] = "`{$key}` > :{$key}";
						$this->w_save[] = "`{$key}` > '{$val[1]}'";
						$this->map[$key] = $val[1];
						break;
					case 'egt':
						$k[] = "`{$key}` >= :{$key}";
						$this->w_save[] = "`{$key}` >= '{$val[1]}'";
						$this->map[$key] = $val[1];
						break;
					case 'lt':
						$k[] = "`{$key}` < :{$key}";
						$this->w_save[] = "`{$key}` < '{$val[1]}'";
						$this->map[$key] = $val[1];
						break;
					case 'elt':
						$k[] = "`{$key}` <= :{$key}";
						$this->w_save[] = "`{$key}` <= '{$val[1]}'";
						$this->map[$key] = $val[1];
						break;
					case 'neq':
						$k[] = "`{$key}` <> :{$key}"; 
						$this->w_save[] = "`{$key}` <> '{$val[1]}'";
						$this->map[$key] = $val[1];
						break;
					case 'like':
						$k[] = "`{$key}` like :{$key}";
						$this->map[$key] = $val[1];
						break;
				}
			}
			$this->where =  ' where ' . join(' '. $par .' ',$k);
			$this->w_save =  ' where ' . join(' '. $par .' ',$this->w_save);
		}
			return $this;
		}
		// 过滤字段
		private function fie(array $data){
			foreach ($data as $key => $val) {
				if (!in_array($key,$this->field)) {
					unset($data[$key]);
				}
			}
			return $data;
		}
		// 查询字段名
		public function desc(){
			$sql = "desc `{$this->tabName}`";
			$smit = $this->pdo->query($sql);
			$list = $smit->fetchAll(2);
			foreach ($list as $key => $val) {
				$this->field[] = $val['Field'];
			}
			return $list;
		}
		public function __call($method,$params){
			if (!empty($params[0])) {
				if (array_key_exists($method,$this->sql)) {
					switch ($method) {
						case 'limit':
							$this->sql['limit'] = ' limit ' . $params[0];
							break;
						case 'order':
							$this->sql['order'] = ' order by ' . $params[0];
							break;
						case 'way' :
							$this->sql['way'] = $params[0];
							break;
					}	
				}else{
					return '没有这个方法';
				}
			}
			return $this;
		}
		public function __destruct(){
			unset($this->pdo);
		}
	}
