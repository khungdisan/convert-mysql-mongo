<?php
require 'vendor/autoload.php';
/*Created by
Abhishek Gahlot On  March 2013
http://www.abhishek.it
Mysql To Mongodb Importer
*/
define('dbname', 'iptv_platform_services'); //Enter database name of mysql. Mongo will also create the database of this name.
// db connection class using singleton pattern
class dbConn
{
  private $dbhost = 'localhost'; //Enter mysql database host
  private $dbusername = 'root'; //Enter mysql database username
  private $dbpassword = ''; //Enter mysql database password
  // variable to hold connection object.
  protected static $db;
  // private construct
  private function __construct()
  {
    try {
      // assign PDO object to db variable
      self::$db = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . dbname, $this->dbusername, $this->dbpassword);
      self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(PDOException $e) {
      // Output error - would normally log this to error file rather than output to user.
      echo "Connection Error: " . $e->getMessage();
    }
  }
  // get connection function. Static method - accessible without instantiation
  public static function getConnection()
  {
    // Guarantees single instance, if no connection object exists then create one.
    if (!self::$db) {
      // new connection object.
      new dbConn();
    }
    // return connection.
    return self::$db;
  }
} //end class
class Mysql
{
  public function showTables()
  {
    $dbname = dbname;
    $db = dbConn::getConnection();
    $query = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='$dbname'");
    // var_dump($query);die;
    // $query=$db->query('SELECT * FROM users');
    while ($results = $query->fetch(PDO::FETCH_ASSOC)) {
      foreach($results as $key => $val) {
        // Tables name
        $tableArray[] = $val;
      }
    }
    // print_r($tableArray);die;
    // Send tables name data to columns function
    $this->showColumns($tableArray);
  }
  private function showColumns($tableArray)
  {
    $db = dbConn::getConnection();
    $dbname = dbname;
    foreach($tableArray as $table => $tableName) { //foreach for every table name fetch table name
      $query = $db->query("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='$dbname'  AND `TABLE_NAME`='$tableName'");
      unset($columnArray); //unset columnarray so that previous values dont get mixed
      while ($columnQuery = $query->fetch(PDO::FETCH_ASSOC)) {
        foreach($columnQuery as $column => $columnname) { //foreach for every table name fetch column name
          // columns name
          $columnArray[] = $columnname;
        }
      } //Send columns name and Table name to Data function
      $this->showData($tableName, $columnArray);
    }
  } //function show columns ends here
  private function showData($tableName, $columnArray)
  {
    // $mongoName = "Localhost";//mongo server name
    $mongoHost = "127.0.0.1";//mongo host
    $mongoPort = "27017";//mongo port
    $mongoUser = "admin";//mongo authentication user name, works only if mongo_auth=false
    $mongoPass = "1234567890";

    $uri = "mongodb://${mongoUser}:${mongoPass}@${mongoHost}:${mongoPort}";
    // echo $uri;die;
    $dbname = dbname;
    // echo $dbname;die;
    $MongoConnect = new \MongoDB\Client($uri);
    // var_dump($MongoConnect);die;
    $db = $MongoConnect->$dbname;
    // echo $tableName;die;
    try {
      $tableCreation = $db->createCollection($tableName);
    } catch (\Exception $e) {
      echo "<pre>";
      print_r($e);
      echo  "</pre>";
      die;
    }
    $db = dbConn::getConnection();
    $query = $db->query("SELECT * from `$tableName`");
    while ($results = $query->fetch(PDO::FETCH_ASSOC)) {
      $rowColumn_combined = array_combine($columnArray, $results); //combining column and data to insert
      // print_r($rowColumn_combined);die;
      $tableCreation->insert($rowColumn_combined);
    }
    echo 'Successfully Imported Table ' . $tableName . "\n";
  } //showdata function bracket
}
$obj = new mysql;
$obj->showTables();
?>