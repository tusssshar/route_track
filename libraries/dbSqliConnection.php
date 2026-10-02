<?php

//if ( extension_loaded('zlib') ){  ob_start('ob_gzhandler');   }
//error_reporting(E_ALL);

class dbSqlConnection {

    var $conn;

    function __construct() {
        $this->dbSqlConnection();
    }

    function dbSqlConnection() {

    }

    function connect($HST = HST, $USR = USR, $PWD = PWD, $DBN = DBN) {

        $this->conn = new mysqli($HST, $USR, $PWD, $DBN);
        /* check connection */
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", $this->conn->connect_error);
            exit();
        }
        // NOW() / CURDATE() follow the company timezone (see company_settings::apply)
        $this->conn->query("SET time_zone='" . date('P') . "'");
        return $this->conn;
    }

    function disconnect() {
        $this->conn->close();
    }

}

//	create a global connection	
$objGlobalConnection = new dbSqlConnection();
$oGlobalConnection = $objGlobalConnection->Connect(HST, USR, PWD, DBN);

class database extends dbSqlConnection {
    #	Class Public attributes

    var $conn = "";    #	Store dbSqlConnection string
    var $field_array = array();  #	Array Type varibale that's Stores database table column names
    var $isUpdate = 0;
    var $row_count = 0;
    var $result;
    var $id = 0;
    var $where = "";   #	Store where condition
    var $stmt = "";   #	Store SQL Statement
    var $donot_stripslashes = "";

    #	database Constructor.
    # when we create object of datbase, then Constructor will auto run and initialize 		

    function __construct() {
        $this->database();
    }

    function database() {
        global $oGlobalConnection;
        $this->conn = $oGlobalConnection;
        #	Call connect method whenever database object created written in __dbSqlConnection class
        //$this->Connect($HST,$USR,$PWD,$DBN);
    }

    #	Private Functions
    #	Private Function excuteSQLStatement useing excuterSQL function we can excute SQL statements.

    function excuteSQLStatement() {
        $this->field_array = array();
        $this->row_count = 0;
        //print_r(get_class_methods($this->conn));
        $this->result = $this->conn->query($this->stmt);
        if (!$this->result) {
            trigger_error($this->result->error . "<br>" . $this->stmt, E_USER_ERROR);
        }

        if ($this->isUpdate <> 1) {
            $this->row_count = $this->result->num_rows;

            // Get field information for all fields
            $count = 0;
            while ($finfo = $this->result->fetch_field()) {
                $this->field_array[$count][0] = $finfo->name;
                $this->field_array[$count][2] = $finfo->name;
                $count++;
            }
            /*
              $columns = $this->result->num_fields;
              for ($count = 0; $count < $columns; $count++){
              $this->field_array[$count][0] = mysql_field_name($this->result, $count);
              $this->field_array[$count][2] = mysql_field_name($this->result, $count);
              }
             */
            if ($this->row_count > 0) {
                $this->moveto(0);
            }
        }



        return 1;
    }

    #	Private Function Excute SQL Statement

    function excuteQueryStatement($instype) {
        $this->field_array = array();
        $this->row_count = 0;
        $this->id = 0;
        $this->result = $this->conn->query($this->stmt);
        if (!$this->result) {
            trigger_error($this->result->error . "<br>" . $this->stmt, E_USER_ERROR);
        }

        if ($instype == 1) {
            $this->id = $this->conn->insert_id;
        }

        return 1;
    }

    #	Private function movefirst using movefirst function we can move record pointer to dirct first position

    function movefirst() {
        $this->moveto(0);
    }

    #	Private function movefirst using movefirst function we can move record pointer to dirct last position

    function movelast() {
        $this->moveto(($this->row_count - 1));
    }

    #	Private function searchDirectory useing searchDirectory function we can serach directory phycilly exits or not

    function searchDirectory($dirr) {
        $counter = 0;
        $dir = $dirr;
        while (1) {
            $counter++;
            if (!is_dir($dir)) {
                $dir = $del . $dir;
                $del = "../";
            }
            if ($counter == 5)
                break;
        }
        return $dir;
    }

    #	--------------------------------------		End of Private Fucntions	--------------------------------------
    #	Public Functions
    #	Setting where condition	default parameter is "1".

    function where($condition = "1") {
        $this->where = $condition;
    }

    #	Using addnew function we can add new record which is store in the database table its take one parameter $tablename.

    function insert($tablename) {
        #	Getting all table fields name in the $fieldname array from public $this->field_array
        $fieldname = array_keys($this->field_array);
        #	Getting all textbox (HTML objects) values in the $allvalues array from public $this->field_array
        $allvalues = array_values($this->field_array);
        #	Createing SQL statement for insert record into database table. 
        $this->stmt = "insert into " . $tablename . " (" . join(",", $fieldname) . ") values('" . join("','", $allvalues) . "')";
        #	Excute SQL statement
        $this->excuteQueryStatement(1);
    }

    #	Using update function we can update record which is store in the database table its take 2 parameter
    #	$tablename and $fieldlist
    #	$fieldlist is the optional parameter it's take "" value defualt

    function update($tablename, $fieldlist = "") {
        global $class_main;
        if (strlen(trim($this->where)) > 0) {
            if ((strlen(trim($fieldlist)) > 0)) {
                $working_data = ",";
            } else {
                $working_data = "";
            }

            foreach ((array) $this->field_array as $key => $value) {
                $fieldlist .= $working_data . $key . "='" . $value . "' ";
                $working_data = ",";
            }

            $this->stmt = "update " . $tablename . " set $fieldlist where " . $this->where;
            $this->excuteQueryStatement(0);
        } else {
            trigger_error("Delete can only excuted if the where condition is provided", E_USER_ERROR);
        }
    }

    #	Excuter SQL Queries on demand

    function myCustomQuery($query, $isUpdate = 0) {
        $this->stmt = $query;
        $this->isUpdate = $isUpdate;
        $this->excuteSQLStatement();
    }

    #	Function for get found rows when you use SQL_CALC_FOUND_ROWS in your query

    function FOUND_ROWS() {
        $rs = mysqli_query($this->conn, 'SELECT FOUND_ROWS() as row_count');
        $row = mysqli_fetch_assoc($rs);
        return $row['row_count'];
    }

    /*
      function quotes_to_entities($str){
      //return str_replace(array("\'","\"","'",'"','<', '>'), array("&#39;","&quot;","&#39;","&quot;","&lt;","&gt;"), $str);
      return str_replace("<script>","", $str);
      }
     */

    #	Geathering fields names and values in public field_array variable

    function data($fieldname, $value, $xss = 1) {
        $k = "";
        $k = @trim(str_replace(array("<script>", "</script>"), array("script", "script"), $value));
        $this->field_array[$fieldname] = addslashes($k);

        if ($xss == 1) {
            $k = @trim(str_replace(array("<script>", "</script>"), array("script", "script"), $value));
            $this->field_array[$fieldname] = strip_tags(addslashes($k));
        }
    }

    #	Function delete using delete function we can delete database table records it's take one paramenter $tablename
    #	Other setting set by public varibales and other public methods like $db->where() etc.

    function delete($tablename) {
        $table_data = explode(",", $tablename);
        for ($count = 0; $count < sizeof($table_data); $count++) {
            if (strlen(trim($this->where)) > 0) {
                $this->stmt = "delete from " . DB_PREFIX . trim($table_data[$count]) . " where " . $this->where;
                $this->excuteQueryStatement(0);
            } else {
                trigger_error("Delete can only excuted if the where condition is provided", E_USER_ERROR);
            }
        }
    }

    #	Function cDelete useing cDelete function we can quick delete database table records it's take tow paramenter $tablename and $where condition

    function cDelete($tablename, $where) {
        $dd = new database();
        $dd->where = $where;
        $dd->delete($tablename);
    }

    function selectstmt($table, $fields = "*") {
        $table_data = explode(",", $table);
        for ($count = 0; $count < sizeof($table_data); $count++) {
            $data_work = explode(" as ", $table_data[$count]);
            if (sizeof($data_work) > 1) {
                $table_data[$count] = DB_PREFIX . $data_work[0] . " as " . $data_work[1];
            } else {
                $table_data[$count] = DB_PREFIX . $data_work[0] . " as " . $data_work[0];
            }
        } #	end for loop

        if (strlen(trim($this->where)) > 0) {
            $this->stmt = "select $fields from " . join(",", $table_data) . " where " . $this->where;
        } else {
            $this->stmt = "select $fields from " . join(",", $table_data) . " where 1 ";
        }

        return $this->excuteSQLStatement();
    }

    function moveto($row_num) {
        // Out of range (e.g. empty result): leave the f_ fields untouched instead of aborting the page
        if ($row_num < 0 || $row_num >= $this->row_count) {
            return false;
        }
        $this->result->data_seek($row_num);
        $datarow = $this->result->fetch_array();
        for ($count = 0; $count < sizeof($this->field_array); $count++) {
            if ($this->donot_stripslashes == 0) {
                $value = stripslashes((string) $datarow[$this->field_array[$count][2]]);
            } else {
                $value = $datarow[$this->field_array[$count][2]];
            }
            //$value = str_replace(array('I','i','�','�','�','�','�','�','G','g','S','s','�'),array('&#304;','&#305;','&#214;','&#246;','&#220;','&#252;','&#199;','&#231;','&#286;','&#287;','&#350;','&#351;','&#8356;'),$value);
            $this->{"f_" . trim($this->field_array[$count][2])} = $value;
        }
    }

    function mysqli_result($res, $row, $field = 0) {
        $res->data_seek($row);
        $datarow = $res->fetch_array();
        return $datarow[$field];
    }

    function free_result($result) {
        mysqli_free_result($result);
    }

    #	--------------------------------------		End of Public Fucntions	--------------------------------------

    function close() {
        $this->conn->close();
    }

}

//end class

function Encode($data, $pwd) {
    $pwd_length = strlen($pwd);
    for ($i = 0; $i < 255; $i++) {
        $key[$i] = ord(substr($pwd, ($i % $pwd_length) + 1, 1));
        $counter[$i] = $i;
    }
    for ($i = 0; $i < 255; $i++) {
        $x = ($x + $counter[$i] + $key[$i]) % 256;
        $temp_swap = $counter[$i];
        $counter[$i] = $counter[$x];
        $counter[$x] = $temp_swap;
    }
    for ($i = 0; $i < strlen($data); $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $counter[$a]) % 256;
        $temp = $counter[$a];
        $counter[$a] = $counter[$j];
        $counter[$j] = $temp;
        $k = $counter[(($counter[$a] + $counter[$j]) % 256)];
        $Zcipher = ord(substr($data, $i, 1)) ^ $k;
        $Zcrypt .= chr($Zcipher);
    }
    return $Zcrypt;
}

//	Note - script carry from 	-	http://awesomephp.com/?Tutorials*3/Encode-Decode-in-PHP.html
//	msyql_real_escape for sql injection
//	strip_tags() for XSS

function sanitize($data) {
    global $oGlobalConnection;
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $data = stripcslashes($data);
    }
    if ($oGlobalConnection instanceof mysqli) {
        $data = mysqli_real_escape_string($oGlobalConnection, (string) $data);
    } else {
        $data = addslashes((string) $data);
    }
    return $data;
}

// end function
?>