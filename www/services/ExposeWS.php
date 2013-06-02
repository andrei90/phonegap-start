<?php

require( "config.php" );
session_start();
$action = isset( $_GET['action'] ) ? $_GET['action'] : "";
$recIsClicked=isset( $_GET['rec'] )? $_GET['rec'] : "";
static $successfulRegistration=FALSE;
$username = isset( $_SESSION['username'] );
if(strcmp($recIsClicked,"Înregistrare")==0){
	$_SESSION['recIsClicked']=$_GET['rec'];
}

if($successfulRegistration)
	unset( $_SESSION['recIsClicked'] );

if ( $action != "login" && $action != "logout" && !$username && $action!="signup") {

	login();
	exit;
}

switch ( $action ) {
	case 'signup':
		signup();
		break;
	case 'login':
		$_SESSION['tab']="login";
		login();
		break;
	case 'logout':
		logout();
		break;
	case 'newArticle':
		$_SESSION['tab']="newArticle";
		newArticle();
		break;
	case 'editArticle':
		$_SESSION['tab']="newArticle";
		editArticle();
		break;
	case 'deleteArticle':
		deleteArticle();
		break;
	case 'viewArticle':
		viewArticle();
		break;
	case 'sort':
		getSortedArticles();
		break;
	case 'permisions':
		$_SESSION['tab']="permissions";
		permission();
		break;
	case 'confirm':
		confirm();
		break;
	case 'makeRequest':
		makeRequest();
		break;
	case 'QuickSearch':
		break;
	case 'AdvancedSearch':
		break;
	default:
		$_SESSION['tab']="listArticles";
		listArticles();
}

/**
 * 
 */
function signup(){
	$results = array();
	if(strcmp($_POST['password'],$_POST['passwordAgain'])==0){
			$user = new User($_POST );
			if($user->createUser()){
				$_SESSION['username'] = $_POST['username'];
				$_SESSION['userObj'] = serialize($user);
				$successfulRegistration = TRUE;
				unset( $_SESSION['recIsClicked'] );
				//header( "Location: admin.php" );
				echo '{"signup": "successful"}'; 
			}else{
				$results['passDontMatch'] = "Userul exista in db";
				//require( TEMPLATE_PATH . "/homepage.php" );
				echo '{"signup": "failed"}';
			}
	}else{
		$results['passDontMatch'] = "Parolele nu coincid";
		//require( TEMPLATE_PATH . "/homepage.php" );
		echo '{"signup": "passDontMatch"}';
	}
}

/**
 * 
 */
function login() {
	$results = array();
	if ( isset( $_POST['login'] ) ) {
		// User has posted the login form: attempt to log the user in
		if(User::isValidUser($_POST['username'],$_POST['password'])){
			// Login successful: Create a session and redirect to the admin homepage
			$_SESSION['username'] = $_POST['username'];
			$userObj = User::getUser($_POST['username']);
			$_SESSION['userObj'] = serialize($userObj);
			echo '{"login": "successful"}';
			//header( "Location: admin.php" );
		} else {
			// Login failed: display an error message to the user
			$results['errorMessage'] = "Datele de autentificare sunt incorecte. Încercaţi din nou.";
			//require( TEMPLATE_PATH . "/homepage.php" );
			echo '{"login": "failed"}';
		}
	} else {
		// User has not posted the login form yet: display the form
		//require( TEMPLATE_PATH . "/homepage.php" );
		echo '{"login": "display"}';
	}
}


/**
 * 
 */
function logout() {
	unset( $_SESSION['username'] );
	session_destroy();
	//header( "Location: admin.php" );
	echo '{"logout": "true"}';
}


/**
 * 
 */
function newArticle() {
	$results = array();
	$results['formAction'] = "newArticle";
	
	// User has posted the article edit form: save the new article
	$article = new Article( $_POST );
	$article->insert($_SESSION['username']);
	//header( "Location: admin.php?status=changesSaved" );
	echo '{"newArticle":"inserted"}'; 
}


/**
 * 
 */
function editArticle() {
	$results = array();
	$results['formAction'] = "editArticle";
	if ( isset( $_GET['saveChanges'] ) ) {
		// User has posted the article edit form: save the article changes
		if ( !$article = Article::getById( (int)$_POST['articleId'] ) ) {
			//header( "Location: admin.php?error=articleNotFound" );
			echo '{"editArticle":"articleNotFound"';
			return;
		}
		$article->storeFormValues( $_POST );
		$article->update();
		echo '{"editArticle":"updated"}';
		//header( "Location: admin.php?status=changesSaved" );
	}  else {
		// User has not posted the article edit form yet: display the form
		$results['article'] = Article::getById( (int)$_GET['articleId'] );
		echo '{"editArticle":'. json_encode($results) .'}';
		//require( TEMPLATE_PATH . "/admin/editArticle.php" );
	}
}


function deleteArticle() {
	if ( !$article = Article::getById( (int)$_GET['articleId'] ) ) {
		header( "Location: admin.php?error=articleNotFound" );
		echo '{"deleteArticle":"articleNotFound"}';
		return;
	}
	$article->delete();
	echo '{"deleteArticle":"delete"}';
	//header( "Location: admin.php?status=articleDeleted" );
}


function listArticles() {
	$results = array();
	$data = Article::getList($_SESSION['username']);
	$results['articles'] = $data['results'];
	$results['totalRows'] = $data['totalRows'];
	if ( isset( $_GET['error'] ) ) {
		if ( $_GET['error'] == "articleNotFound" ){
			$results['errorMessage'] = "Eroare: Articolul nu a fost găsit.";
		}
	}
	if ( isset( $_GET['status'] ) ) {
		if ( $_GET['status'] == "changesSaved" ) {
			$results['statusMessage'] = "Modificarile tale au fost salvate.";
		}
		if ( $_GET['status'] == "articleDeleted" ) {
			$results['statusMessage'] = "Articol şters.";
		}
	}
	echo '{"listArticles":'. json_encode($results).'}';
	//require( TEMPLATE_PATH . "/admin/listArticles.php" );
}

function viewArticle() {
	if ( !isset($_GET["articleId"]) || !$_GET["articleId"] ) {
		echo '{"viewArticle":"articleNotFound"';
		return;
	}
	$results = array();
	$results['article'] = Article::getById( (int)$_GET["articleId"] );
	echo '{"viewArticle":'. json_encode($results).'}';
	//require( TEMPLATE_PATH . "/viewArticle.php" );
}

function getSortedArticles(){
	$results = array();
	$column=$_GET['column'];
	$order=$_GET['order'];
	switch ( $column ){
		case 'publicationDate':
			unset($_SESSION['titleOrder']);
			if(!isset($_SESSION['pubDateOrder']))
				$_SESSION['pubDateOrder']="DESC";
			else
				if($_SESSION['pubDateOrder']=="ASC")
				$_SESSION['pubDateOrder']="DESC";
			else
	   $_SESSION['pubDateOrder']="ASC";
			break;
		case 'title':
			unset($_SESSION['pubDateOrder']);
			if(!isset($_SESSION['titleOrder']))
				$_SESSION['titleOrder']="DESC";
			else
				if($_SESSION['titleOrder']=="ASC")
				$_SESSION['titleOrder']="DESC";
			else
				$_SESSION['titleOrder']="ASC";
			break;
	}
	$value=$column." ".$order;
	$data = Article::getList($_SESSION['username'],$value);
	$results['articles'] = $data['results'];
	$results['totalRows'] = $data['totalRows'];
	echo '{"getSortedArticles":'. json_encode($results).'}';
	//require( TEMPLATE_PATH . "/admin/listArticles.php" );
}

function permission(){
	$results = array();
	$user = unserialize($_SESSION['userObj']);
	$data =$user->getReaders();
	$results['readers']= $data['results'];
	$results['totalRows'] = $data['totalRows'];
	$data =$user->getPendingRequests();
	$results['possibleReaders']=$data['results'];
	$results['totalPendingRequests'] = $data['totalRows'];
	echo '{"permission":'. json_encode($results).'}';
	//require( TEMPLATE_PATH . "/admin/permissions.php" );
}

function confirm(){
	$results = array();
	if ( isset( $_POST['nameOfRequest'] ) ) {
		$reader=$_POST['nameOfRequest'];
		$user = unserialize($_SESSION['userObj']);
		$user->update($_SESSION['username'],$reader);
		echo '{"confirm":'. json_encode($results).'}';
		permission();
	 //require( TEMPLATE_PATH . "/admin/permissions.php" );
	}
	else{
		//header( "Location: index.php" );
	}
}

function makeRequest(){
	$reader=$_POST['makeReq'];
	if ( isset( $_POST['makeReq'] ) ) {

		$user = unserialize($_SESSION['userObj']);
		$user->insert($reader,$_SESSION['username']);
		permission();
		echo '{"makeRequest":'. json_encode($results).'}';
		//require( TEMPLATE_PATH . "/admin/permissions.php" );
	}else{
		//header( "Location: index.php" );
	}
}

?>
