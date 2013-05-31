<?php
ini_set( "display_errors", true );
define( "DB_DSN", "mysql:host=localhost;dbname=cms" );
define( "DB_USERNAME", "root" );
define( "DB_PASSWORD", "" );
define( "CLASS_PATH", "classes" );
//define( "TEMPLATE_PATH", "templates" );

require( CLASS_PATH . "/Article.php" );
require( CLASS_PATH . "/User.php" );
?>
