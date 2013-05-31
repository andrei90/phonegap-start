<?php

/**
 * @author admin
 *
 */
class User{

	public $userDB_ID=null;

	public $userName=null;

	public $pass=null;

	public $email=null;

	public $allArticles=array();

	public function __construct( $data=array() ) {

		if ( isset( $data['userDB_ID'])) $this->userDB_ID= $data['user_id'];
		if ( isset( $data['username'])) $this->userName= $data['username'];
		if ( isset( $data['password'])) $this->pass= $data['password'];
		if ( isset( $data['email'])) $this->email= $data['email'];
	}
	/**
	 * Return true if user exist and is valid the password
	 * @param username,password
	 * @return True if valid false otherwise
	 **/
	public static function isValidUser($userName, $pass){
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sql = "SELECT * FROM users WHERE username =:username AND password=:password";
			$st = $conn->prepare( $sql );
			$st->bindValue( ":username",$userName , PDO::PARAM_STR );
			$st->bindValue( ":password", $pass, PDO::PARAM_STR );
			$st->execute();
			$row = $st->fetch();
			$conn = null;
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
			$conn = null;
			return FALSE;
		}
		if ( $row ){
		 return TRUE;
		}
		return FALSE;
	}


	public function createUser(){
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sqlSelect="SELECT username FROM users WHERE username=:username";
			$st = $conn->prepare( $sqlSelect );
			$st->bindValue( ":username", $this->userName, PDO::PARAM_STR );
			$st->execute();
			$row = $st->fetch();
			if(!$row){
				$sql = "INSERT INTO users ( username, password, email ) VALUES ( :username, :password, :email )";
				$st = $conn->prepare( $sql );
				$st->bindValue( ":username", $this->userName, PDO::PARAM_STR );
				$st->bindValue( ":password", $this->pass, PDO::PARAM_STR );
				$st->bindValue( ":email", $this->email, PDO::PARAM_STR );
				$st->execute();
				$conn = null;
				return TRUE;
			}else{
				$conn = null;
				return FALSE;
			}
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
			$conn = null;
			return FALSE;
		}
	}
	
	/**
	 * @param unknown_type $username
	 * @return User
	 */
	public static function getUser($username){
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sqlSelect="SELECT * FROM users WHERE username=:username";
			$st = $conn->prepare( $sqlSelect );
			$st->bindValue( ":username", $username, PDO::PARAM_STR );
			$st->execute();
			$row = $st->fetch();
			if($row){
				return new User( $row );
			} else {
				return null;
			}
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
			$conn = null;
			return null;
		}
	}
	
	
	/**
	 * @param unknown_type $user
	 * @param unknown_type $reader
	 */
	public function update($user,$reader){
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sql = "UPDATE permision SET allow=1
					WHERE article_owner_id=(SELECT user_id FROM users WHERE username=:currentUser)
					AND reader_id=( SELECT user_id FROM users WHERE username=:reader) ";
			$st = $conn->prepare ( $sql );
			$st->bindValue( ":currentUser", $user, PDO::PARAM_STR );
			$st->bindValue( ":reader", $reader, PDO::PARAM_STR );
			$st->execute();
			$conn = null;
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
			$conn = null;
		}
	}

	/**
	 * Add permission
	 * @param unknown_type $user
	 * @param unknown_type $reader
	 */
	public function insert($user,$reader){
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sql = "INSERT INTO  permision( article_owner_id , reader_id )
					VALUES ( (SELECT user_id FROM users WHERE username=:currentUser),
					(SELECT user_id FROM users WHERE username=:reader) )";
			$st = $conn->prepare ( $sql );
			$st->bindValue( ":currentUser", $user, PDO::PARAM_STR );
			$st->bindValue( ":reader", $reader, PDO::PARAM_STR );
			$st->execute();
			//$_SESSION['sql']=$sql;
			$conn = null;
		}catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
			$conn = null;
		}
	}


	/**
	 * @param unknown_type $user
	 * @return multitype:multitype:User  mixed 
	 */
	public function getReaders(){
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sqlSelect="SELECT SQL_CALC_FOUND_ROWS * FROM users
					WHERE user_id=( SELECT article_owner_id FROM permision
					WHERE reader_id=( SELECT user_id FROM users WHERE username=:currentUser )
					AND allow=1	)";
			$st = $conn->prepare( $sqlSelect );
			$st->bindValue( ":currentUser", $this->userName, PDO::PARAM_STR );
			$st->execute();
			$list = array();
			while ( $row = $st->fetch() ) {
				$reader = new User( $row );
				$list[] = $reader;
			}
			$sql = "SELECT FOUND_ROWS() AS totalRows";
			$totalRows = $conn->query( $sql )->fetch();
			$conn = null;

			return ( array ( "results" => $list, "totalRows" => $totalRows[0] ) );
	 }catch(PDOException $e) {
	 	echo '{"error":{"text":'. $e->getMessage() .'}}';
	 	$conn = null;
	 	return null;
	 }

	}

	/**
	 * @param unknown_type $user
	 * @return multitype:multitype:User  mixed 
	 */
	public function getPendingRequests(){
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sqlSelect="SELECT SQL_CALC_FOUND_ROWS * FROM users
					WHERE user_id IN ( SELECT reader_id FROM permision
					WHERE article_owner_id = ( SELECT user_id FROM users WHERE username=:currentUser )
					AND allow=0  )";
			$st = $conn->prepare( $sqlSelect );
			$st->bindValue( ":currentUser", $this->userName, PDO::PARAM_STR );
			$st->execute();
			$list = array();
			while ( $row = $st->fetch() ) {
				$reader = new User( $row );
				$list[] = $reader;
			}
			$sql = "SELECT FOUND_ROWS() AS totalRows";
			$totalRows = $conn->query( $sql )->fetch();
			$conn = null;

			return ( array ( "results" => $list, "totalRows" => $totalRows[0] ) );
	 }catch(PDOException $e) {
	 	echo '{"error":{"text":'. $e->getMessage() .'}}';
	 	$conn = null;
	 	return null;
	 }
	}
		
}
?>