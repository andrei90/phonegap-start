<?php
/**
 * Class to handle articles
 */
class Article
{
	// Properties

	/**
	 * @var int The article ID from the database
	 */
	public $id = null;

	/**
	 * @var int When the article is to be / was first published
	 */
	public $publicationDate = null;

	/**
	 * @var string Full title of the article
	 */
	public $title = null;

	/**
	 * @var string A short summary of the article
	 */
	public $summary = null;

	/**
	 * @var string The HTML content of the article
	 */
	public $content = null;

	public $author=null;


	/**
	 * Sets the object's properties using the values in the supplied array
	 *
	 * @param assoc The property values
	 */

	public function __construct( $data=array() ) {
		if ( isset( $data['id'] ) ) $this->id = (int) $data['id'];
		if ( isset( $data['publicationDate'] ) ) $this->publicationDate = $data['publicationDate'];
		if ( isset( $data['title'] ) ) $this->title = preg_replace ( "/[^\.\,\-\_\'\"\@\?\!\:\$ a-zA-Z0-9ăĂâÂîÎşŞţŢ()]/", "", $data['title'] );
		if ( isset( $data['summary'] ) ) $this->summary = preg_replace ( "/[^\.\,\-\_\'\"\@\?\!\:\$ a-zA-Z0-9()ăĂâÂîÎşŞţŢ]/", "", $data['summary'] );
		if ( isset( $data['content'] ) ) $this->content = $data['content'];
		if ( isset( $data['author']) ) $this->author=$data['author'];

	}


	/**
	 * Returns an Article object matching the given article ID
	 *
	 * @param int The article ID
	 * @return Article|false The article object, or false if the record was not found or there was a problem
	 */

	public static function getById( $id ) {
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sql = "SELECT *, UNIX_TIMESTAMP(publicationDate) AS publicationDate FROM articles WHERE id = :id";
			$st = $conn->prepare( $sql );
			$st->bindValue( ":id", $id, PDO::PARAM_INT );
			$st->execute();
			$row = $st->fetch();
			$conn = null;
			if ( $row ) {
				//return 
				$a = new Article( $row );
				echo '{"article":'. json_encode($a).'}'; 
			}
		} catch (PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}

	/**
	 * Returns all (or a range of) Article objects in the DB
	 *
	 * @param int Optional The number of rows to return (default=all)
	 * @param string Optional column by which to order the articles (default="publicationDate DESC")
	 * @return Array|false A two-element array : results => array, a list of Article objects; totalRows => Total number of articles
	 */

	public static function getList($var,$order="publicationDate DESC") {
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sql = "SELECT SQL_CALC_FOUND_ROWS *, UNIX_TIMESTAMP(publicationDate) AS publicationDate FROM articles
					WHERE user_fk=(SELECT user_id FROM users WHERE username=:user)
					ORDER BY " . mysql_real_escape_string($order);
			$st = $conn->prepare( $sql );
			$st->bindValue( ":user", $var, PDO::PARAM_STR );
			$st->execute();
			$list = array();

			while ( $row = $st->fetch() ) {
				$article = new Article( $row );
				$list[] = $article;
			}

			// Now get the total number of articles that matched the criteria
			$sql = "SELECT FOUND_ROWS() AS totalRows";
			$totalRows = $conn->query( $sql )->fetch();
			$conn = null;
			return ( array ( "results" => $list, "totalRows" => $totalRows[0] ) );
		} catch (PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
			return null;
		}
	}

	public static function getAllVisibleArticles($readerName){
		try {
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sql = "SELECT SQL_CALC_FOUND_ROWS *, UNIX_TIMESTAMP(publicationDate) AS publicationDate FROM articles
					WHERE (user_fk IN
					(SELECT article_owner_id FROM permision
					WHERE reader_id=(SELECT user_id FROM users WHERE username=:readerName)
					AND allow=1))
					OR 	user_fk=(SELECT user_id FROM users WHERE username=:readerName)
					";
			$st = $conn->prepare( $sql );
			$st->bindValue( ":readerName", $readerName, PDO::PARAM_STR );
			$st->execute();
			$list = array();
			while ( $row = $st->fetch() ) {
				$article = new Article( $row );
				$list[] = $article;
			}

			$sql = "SELECT FOUND_ROWS() AS totalRows";
			$totalRows = $conn->query( $sql )->fetch();
			$conn = null;
			return ( array ( "results" => $list, "totalRows" => $totalRows[0] ) );
		} catch (PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
			return null;
		}
	}


	/**
	 * Inserts the current Article object into the database, and sets its ID property.
	 */

	public function insert($var) {
		// Does the Article object already have an ID?
		if ( !is_null( $this->id ) ) trigger_error ( "Article::insert(): Attempt to insert an Article object that already has its ID property set (to $this->id).", E_USER_ERROR );

		try {
			// Insert the Article
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			echo "Hello World";
			$sql = "INSERT INTO articles ( author,publicationDate, title, summary, content, user_fk ) VALUES (:author, DATE(NOW()), :title, :summary, :content , (SELECT user_id FROM users WHERE username=:fk))";
			$st = $conn->prepare ( $sql );
			$st->bindValue( ":author",$var,PDO::PARAM_STR);
			$st->bindValue( ":title", $this->title, PDO::PARAM_STR );
			$st->bindValue( ":summary", $this->summary, PDO::PARAM_STR );
			$st->bindValue( ":content", $this->content, PDO::PARAM_STR );
			$st->bindValue( ":fk",$var,PDO::PARAM_STR );

			$st->execute();
			$this->id = $conn->lastInsertId();
			$conn = null;
		} catch (PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}


	/**
	 * Updates the current Article object in the database.
	 */

	public function update() {
		// Does the Article object have an ID?
		if ( is_null( $this->id ) ) trigger_error ( "Article::update(): Attempt to update an Article object that does not have its ID property set.", E_USER_ERROR );
		try {
			// Update the Article
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$sql = "UPDATE articles SET publicationDate=DATE(NOW()), title=:title, summary=:summary, content=:content WHERE id = :id";
			$st = $conn->prepare ( $sql );
			$st->bindValue( ":title", $this->title, PDO::PARAM_STR );
			$st->bindValue( ":summary", $this->summary, PDO::PARAM_STR );
			$st->bindValue( ":content", $this->content, PDO::PARAM_STR );
			$st->bindValue( ":id", $this->id, PDO::PARAM_INT );
			$st->execute();
			$conn = null;
		} catch (PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}


	/**
	 * Deletes the current Article object from the database.
	 */
	public function delete() {
		// Does the Article object have an ID?
		if ( is_null( $this->id ) ) trigger_error ( "Article::delete(): Attempt to delete an Article object that does not have its ID property set.", E_USER_ERROR );
		try {
			// Delete the Article
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$st = $conn->prepare ( "DELETE FROM articles WHERE id = :id LIMIT 1" );
			$st->bindValue( ":id", $this->id, PDO::PARAM_INT );
			$st->execute();
			$conn = null;
		} catch (PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}

	public function storeFormValues ( $params ) {
		// Store all the parameters
		$this->__construct( $params );
	}

	public function searchArticle($inputValue){
		$keyword = "%".$inputValue."%";
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$sql = "SELECT SQL_CALC_FOUND_ROWS *, UNIX_TIMESTAMP(publicationDate) AS publicationDate
			FROM articles
			WHERE publicationDate LIKE :search
			OR title LIKE :search
			OR summary LIKE :search
			OR content LIKE :search";
		$st = $conn->prepare( $sql );
		$st->bindValue( ":search", $keyword, PDO::PARAM_STR );
		$st->execute();
		$list = array();

		while ( $row = $st->fetch() ) {
			$article = new Article( $row );
			$list[] = $article;
		}

		// Now get the total number of articles that matched the criteria
		$sql = "SELECT FOUND_ROWS() AS totalRows";
		$totalRows = $conn->query( $sql )->fetch();
		$conn = null;
		return ( array ( "results" => $list, "totalRows" => $totalRows[0] ) );
	}

	public static function advancedSearch($arrayFields,$arrayInputs,$numberOfCriterias){

		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$sql="SELECT SQL_CALC_FOUND_ROWS *, UNIX_TIMESTAMP(publicationDate) AS publicationDate
		  FROM articles";
		$type=PDO::PARAM_STR;
		$whereClause=" WHERE ";
		for($i=0;$i<$numberOfCriterias;$i++){
			$tmpField=$arrayFields[$i];
			$tmpValue=$arrayInputs[$i];
			if($tmpField!=null){
				$whereClause.=$tmpField;
				//append conditional operator
				if	($tmpField=="publicationDate"){
					$whereClause.=" = ";
				}else{
					$whereClause.=" LIKE ";
				}
				$whereClause.=' :search'.$i;
			}
			if($i!=($numberOfCriterias-1))
				$whereClause.=" AND ";
		}

			
		$sql.=$whereClause;

		$st = $conn->prepare( $sql );

		for($i=0;$i<$numberOfCriterias;$i++){
			$tmpField=$arrayFields[$i];
			$tmpValue=$arrayInputs[$i];
			if($tmpField!="publicationDate")
				$keyword = "%".$tmpValue."%";
			else
				$keyword =$tmpValue;
			$st->bindValue( ":search".$i, $keyword, $type );
		}

		$st->execute();
		$list = array();

		while ( $row = $st->fetch() ) {
			$article = new Article( $row );
			$list[] = $article;
		}

		// Now get the total number of articles that matched the criteria
		$sql = "SELECT FOUND_ROWS() AS totalRows";
		$totalRows = $conn->query( $sql )->fetch();
		$conn = null;
		return ( array ( "results" => $list, "totalRows" => $totalRows[0] ) );
	}

}
?>