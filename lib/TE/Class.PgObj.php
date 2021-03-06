<?php
/**
 * This class is a generic DB Class that can be used to create objects
 * based on the description of a DB Table. 
 *
 * @author Anakeen 2000 
 * @version $Id: Class.PgObj.php,v 1.2 2007/06/06 18:12:01 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package TE
 */
 /**
 */




/**
 * This class is a generic DB Class that can be used to create objects
 * based on the description of a DB Table. More Complex Objects will 
 * inherit from this basic Class.
 *
 */
Class PgObj {

  /**
   * the database connection resource
   * @var resource 
   */
  var $dbid = -1;
  /**
   * coordinates to access to database
   * @var string 
   */
  var $dbaccess = '';

  /**
   * array of SQL fields use for the object 
   * @var array 
   */
  var $fields=array ('*');

  /**
   * name of the SQL table
   * @var string  
   */
  var $dbtable='';

  var $criterias=array();
  /**
   * array of other SQL fields, not in attribute of object
   * @var array 
   */
  var $sup_fields=array ();
  var $sup_where=array ();
  var $sup_tables=array ();
  var $fulltextfields=array ();

  /**
   * sql field to order
   * @var string 
   */
  var $order_by="";
  /**
   * indicates if fields has been affected 
   * @var string 
   * @see Affect()
   */
  var $isset = false; // indicate if fields has been affected (call affect methods)

  //----------------------------------------------------------------------------
  /** 
   * Database Object constructor
   * 
   * 
   * @param string $dbaccess database specification
   * @param int $id identificator of the object
   * @param array $res array of result issue to QueryDb {@link QueryDb::Query()}
   * @param resource $dbid the database connection resource
   * @return bool false if error occured
   */
  function __construct($dbaccess='', $id='',$res='',$dbid=0)
  {
    
    $this->dbaccess = $dbaccess;
    $this->init_dbid();


  
    //global ${$this->oname};

    

    if ($this->dbid == 0) {
      $this->dbid = -1;      
    }

    $this->selectstring="";
    // SELECTED FIELDS
    reset($this->fields);
    while(list($k,$v) = each($this->fields)) {
      $this->selectstring=$this->selectstring.$this->dbtable.".".$v.",";
      $this->$v="";
    }

    reset($this->sup_fields);
    while (list($k,$v) = each($this->sup_fields)) {
      $this->selectstring=$this->selectstring."".$v.",";
      $this->$v="";
    }  
    $this->selectstring=substr($this->selectstring,0,strlen($this->selectstring)-1);

    // select with the id
    if (($id!='') || (is_array($id)) || (!isset($this->id_fields[0])) ) {
      $ret=$this->Select($id);

      return($ret);
    }
    // affect with a query result
    if (is_array($res)) {
      $this->Affect($res);
    }


    return TRUE;
  }






  function Select($id)
  {
    if ($this->dbid == -1) return FALSE;
    
    $msg=$this->PreSelect($id);
    if ($msg!='') return $msg;
    
    if ($this->dbtable=='') {
      return("error : No Tables");
    }
    $fromstr="{$this->dbtable}"; 
    if (is_array($this->sup_tables)) {
      reset($this->sup_tables);
      while(list($k,$v) = each($this->sup_tables)) {
	$fromstr.=",".$v;
      }
    } 
    $sql = "select {$this->selectstring} from {$fromstr} ";
    
    $count=0;
    if (is_array($id)) {
      $count=0;
      $wherestr=" where "; 
      reset($this->id_fields);
      while(list($k,$v) = each($this->id_fields)) {
	if ($count >0) {
	  $wherestr=$wherestr." AND ";
	}
	$wherestr=$wherestr."( ".$this->dbtable.".".$v."='".pg_escape_string($id[$k])."' )";
	$count=$count+1;
	
	//$this->$v = $id[$k];
      }
    } else {
      if (isset($this->id_fields[0])) {
	$k = $this->id_fields[0];
	//$this->$k = $id;
	$wherestr= "where ".$this->dbtable.".".$this->id_fields[0]."='".pg_escape_string($id)."'" ;
      } else {
	$wherestr="";
      }
    }
    if (is_array($this->sup_where)) {
      reset($this->sup_where);
      while(list($k,$v) = each($this->sup_where)) {
	$wherestr=$wherestr." AND ";
	$wherestr=$wherestr."( ".$v." )";
	$count=$count+1;
      }
    } 
    
    $sql=$sql." ".$wherestr;
    
    $resultat = $this->exec_query($sql);
    
    if ($this->numrows() > 0) {
      $res = $this->fetch_array(0);
      $retour = $this->Affect($res);
      
    } else {
      return FALSE;
    }
    $msg=$this->PostSelect($id);
    if ($msg!='') return $msg;
    return TRUE;
  }


  function Affect($array)
  {
    reset($array);
    while(list($k,$v) = each($array)) {
      if (!is_integer($k)) {
	$this->$k = $v;
      }
    }
    $this->Complete();
    $this->isset = true;
  }
  /**
   * verify that the object exists 
   *
   * if true values of the object has been set
   * @return bool
   */
  function isAffected()
  {
    return $this->isset;
  }

  function Complete()
  {
    // This function should be replaced by the Child Class
  }

  /** 
   * Method use before Add method
   * This method should be replaced by the Child Class
   * 
   * @return string error message, if no error empty string
   * @see Add()
   */
  function PreInsert()
  {
    // This function should be replaced by the Child Class
  }
  /** 
   * Method use after Add method
   * This method should be replaced by the Child Class
   * 
   * @return string error message, if no error empty string, if message
   * error not empty the Add method is not completed
   * @see Add()
   */
  function PostInsert()
  {
    // This function should be replaced by the Child Class
  }
  /** 
   * Method use before Modify method
   * This method should be replaced by the Child Class
   * 
   * @return string error message, if no error empty string
   * @see Modify()
   */
  function PreUpdate()
  {
    // This function should be replaced by the Child Class
  }
  /** 
   * Method use after Modify method
   * This method should be replaced by the Child Class
   * 
   * @return string error message, if no error empty string, if message
   * error not empty the Modify method is not completed
   * @see Modify()
   */
  function PostUpdate()
  {
    // This function should be replaced by the Child Class
  }
  function PreDelete()
  {
    // This function should be replaced by the Child Class
  }
  function PostDelete()
  {
    // This function should be replaced by the Child Class
  }
  function PreSelect($id)
  {
    // This function should be replaced by the Child Class
  }
  function PostSelect($id)
  {
    // This function should be replaced by the Child Class
  }

  /** 
   * Add the object to the database
   * @param bool $nopost PostInsert method not apply if true
   * @return string error message, if no error empty string
   * @see PreInsert()
   * @see PostInsert()
   */
  function Add($nopost=false)
  {
    if ($this->dbid == -1) return FALSE;
    
    $msg=$this->PreInsert();
    if ($msg!='') return $msg;
    
    $sfields = implode(",",$this->fields);
    $sql = "insert into ".$this->dbtable. "($sfields) values (";
    
    $valstring = "";
    reset($this->fields);
    while (list($k,$v) = each($this->fields)) {
      $valstring = $valstring.$this->lw($this->$v).",";
    }
    $valstring=substr($valstring,0,strlen($valstring)-1);
    $sql=$sql.$valstring.")";
    
    // requery execution
    $msg_err = $this->exec_query($sql);
    
    if ($msg_err!=''){
      return $msg_err;
    }
    $this->isset=true;
    if (!$nopost) $msg=$this->PostInsert();
    if ($msg!='') return $msg;
  }
  /** 
   * Add the object to the database
   * @param bool $nopost PostUpdate() and method not apply if true
   * @param string $sfields only this column will ne updated if empty all fields
   * @param bool $nopre PreUpdate() method not apply if true
   * @return string error message, if no error empty string
   * @see PreUpdate()
   * @see PostUpdate()
   */
  function Modify($nopost=false,$sfields="",$nopre=false)
  {
    if ($this->dbid == -1) return FALSE;
    if (!$nopre) $msg=$this->PreUpdate();
    if ($msg!='') return $msg;
    $sql = "update ".$this->dbtable." set ";
    
    
   
    $nb_keys=0;
    foreach ($this->id_fields as $k=>$v) {
      $notset[$v]="Y";
      $nb_keys++;
    }

    if (! is_array($sfields)) $fields=$this->fields;
    else {
      $fields=$sfields;
      foreach ($this->id_fields as $k=>$v) $fields[]=$v;
    }
    

    $setstr="";
    $wstr="";
    foreach ($fields as $k=>$v) {
      if (!isset($notset[$v])) {
        $setstr=$setstr." ".$v."=".$this->lw($this->$v).",";
      } else {
	$val=pg_escape_string($this->$v);
        $wstr=$wstr." ".$v."='".$val."' AND";
      } 
    }
    $setstr=substr($setstr,0,strlen($setstr)-1);
    $wstr=substr($wstr,0,strlen($wstr)-3);
    $sql.=$setstr;
    if ($nb_keys>0) {
      $sql.=" where ".$wstr.";";
    }
    
    $msg_err = $this->exec_query($sql);

    // sortie
    if ($msg_err!=''){
      return $msg_err;
    }
    
    if (!$nopost) $msg=$this->PostUpdate();
    
    if ($msg!='') return $msg;
  }	

  function Delete($nopost=false)
  {
    $msg=$this->PreDelete();
    if ($msg!='') return $msg;
    $wherestr="";
    $count=0;
    
    reset($this->id_fields);
    while(list($k,$v) = each($this->id_fields)) {
      if ($count >0) {
        $wherestr=$wherestr." AND ";
      }
      $wherestr=$wherestr."( ".$v."='".AddSlashes($this->$v)."' )";
      $count++;
    }
    
    // suppression de l'enregistrement
    $sql = "delete from ".$this->dbtable." where ".$wherestr.";";
    
    $msg_err = $this->exec_query($sql);
    
    if ($msg_err!=''){
      return $msg_err;
    }
    
    if (!$nopost) $msg=$this->PostDelete();
    if ($msg!='') return $msg;
  }
  /** 
   * Add several objects to the database
   * no post neither preInsert are called
   * @param bool $nopost PostInsert method not apply if true
   * @return string error message, if no error empty string
   * @see PreInsert()
   * @see PostInsert()
   */
  function Adds(&$tcopy, $nopost=false)
  {
    if ($this->dbid == -1) return FALSE;
    if (! is_array($tcopy)) return FALSE;
    
    $sfields = implode(",",$this->fields);
    $sql = "copy ".$this->dbtable. "($sfields) from STDIN;\n";
    
    $trow=array();
    foreach ($tcopy as $kc=>$vc) {
      $trow[$kc]="";
      foreach($this->fields as $k=>$v) {
	$trow[$kc] .= "".((isset($vc[$v]))?$vc[$v]:((($this->$v)!='')?$this->$v:'\N'))."\t";
	//$trow[$kc][$k] .= ((isset($vc[$v]))?$vc[$v]:$this->$v);
      }
      $trow[$kc]=substr($trow[$kc],0,-1);
    }
    // query execution
    $berr= pg_copy_from($this->dbid,$this->dbtable,$trow,"\t");
	 
    if (! $berr) return sprintf(_("Pgobj::Adds error in multiple insertion"));

    
    if (!$nopost) $msg=$this->PostInsert();
    if ($msg!='') return $msg;
  }
  function lw($prop)
  {
    $result = ($prop==''?"null":"'".pg_escape_string($prop)."'");
    return $result;
  }
  function CloseConnect()
  {
    pg_close($this->dbid);
    return TRUE;
  }

  function Create($nopost=false)
  {
    $msg = "";
    if (isset($this->sqlcreate)) {
      // step by step
      if (is_array($this->sqlcreate)) {
	while (list($k,$sqlquery)=each($this->sqlcreate)) {
	  $msg.=$this->exec_query($sqlquery,1);
	}
      } else {	
	$sqlcmds = explode(";",$this->sqlcreate);
	while (list($k,$sqlquery)=each($sqlcmds)) {
	  $msg.=$this->exec_query($sqlquery,1);
	}
      }

    }
    if (isset($this->sqlinit)) {
      $msg=$this->exec_query($this->sqlinit,1);

    }
    if ($msg != '') {

      return $msg;
    }
    if (!$nopost) $this->PostInit();
    return($msg);
  }  

  function PostInit() {
  }

  function close_my_pg_connections() {
      global $_DBID;

      $pid = getmypid();

      if (!isset($_DBID[$pid])) {
          return;
      }
      foreach ($_DBID[$pid] as $conn) {
          @pg_close($conn);
      }
      unset($_DBID[$pid]);
  }

  function init_dbid() {
    global $_DBID;

    $pid = getmypid();

    if (isset($_DBID[$pid][$this->dbaccess]) && is_resource($_DBID[$pid][$this->dbaccess])) {
        $status = pg_connection_status($_DBID[$pid][$this->dbaccess]);
        if ($status !== PGSQL_CONNECTION_OK) {
            pg_connection_reset($_DBID[$pid][$this->dbaccess]);
        }
    } else {
        $_DBID[$pid][$this->dbaccess] = pg_connect($this->dbaccess, PGSQL_CONNECT_FORCE_NEW);
    }
    $this->dbid = $_DBID[$pid][$this->dbaccess];

    return $this->dbid;
  }

  function exec_query($sql,$lvl=0)
  {
    global $SQLDELAY,$SQLDEBUG;

    if ($sql == "") return;

    if ($SQLDEBUG) $sqlt1=microtime(); // to test delay of request
    //   $mb=microtime();
    $this->init_dbid();

    
    $this->res=@pg_query($this->dbid,$sql);
   


    $pgmess = pg_last_error($this->dbid);
    //    if ($pgmess != "") print "[$sql]";
    
    $this->msg_err = chop(preg_replace("/ERROR:  /","",$pgmess));
    
    // HERER HERER HERE
    // Use Postgresql error codes instead of localized text messages
    $action_needed= "";
    if ($lvl==0) { // to avoid recursivity
      if ($this->msg_err != "") {
	if ((preg_match("/Relation ['\"]([a-zA-Z_]*)['\"] does not exist/i",$this->msg_err) ||
	     preg_match("/Relation (.*) n'existe pas/i",$this->msg_err) ||
	     preg_match("/class \"([a-zA-Z_]*)\" not found/i",$this->msg_err)) ) {
	  $action_needed = "create";
	} else if ((preg_match("/No such attribute or function '([a-zA-Z_0-9]*)'/i",$this->msg_err)) ||
		   (preg_match("/Attribute ['\"]([a-zA-Z_0-9]*)['\"] not found/i",$this->msg_err))) {
	  $action_needed = "update";
	} else if (preg_match("/relation ['\"](.*)['\"] already exists/i",$this->msg_err) ||
		   preg_match("/relation (.*) existe d/i",$this->msg_err)){
	  $action_needed = "none";		       
	}
	//		     print "\n\t\t".$this->dbaccess."[".$this->msg_err."]:$action_needed\n";
	error_log("Pgobj::exec_query [".$this->msg_err."]:$action_needed.[$sql]");
		     
      }
    }
    
    switch ($action_needed)
      {
      case "create":
	$st = $this->Create();
	if ($st == "") {
	  $this->msg_err = $this->exec_query($sql);
	} else {
	  return "Table {$this->dbtable} doesn't exist and can't be created"; 
	}
	break;
      case "update":

	$st = $this->Update();
	if ($st == "") {
	  $this->msg_err = $this->exec_query($sql);
	} else {
	  return "Table {$this->dbtable} cannot be updated"; 
	}
	break;
      case "none":
	$this->msg_err = "";
	break;
      default:
	break;
      }
    if ($this->msg_err != "") {

    }
    
    if ($SQLDEBUG) {
      global $TSQLDELAY;
      $SQLDELAY+=microtime_diff(microtime(),$sqlt1);// to test delay of request
      $TSQLDELAY[]=array("t"=>sprintf("%.04f",microtime_diff(microtime(),$sqlt1)),"s"=>str_replace("from","<br/>from",$sql));
    }
    return ($this->msg_err);
  }

  function numrows()
  {
    if ($this->msg_err == "") {
      return(pg_num_rows($this->res));
    } else {
      return(0);
    }
  }

  function fetch_array($c,$type=PGSQL_ASSOC)
  {
    
    return(pg_fetch_array($this->res,$c,$type));
  }

  function Update()
  {
    print $this->msg_err;
    print(" - need update table ".$this->dbtable);

    exit;

    
    // need to exec altering queries
    $objupdate = new Pgobj($this->dbaccess);
    
    // ------------------------------
    // first : save table to updated
    $dumpfile = uniqid("/tmp/".$this->dbtable);
    $err = $objupdate-> exec_query("COPY ".$this->dbtable.
				   "  TO '".$dumpfile."'");

    
    if ($err != "") return ($err);
    
    
    
    
    // ------------------------------
    // second : rename table to save data
    //$err = $objupdate-> exec_query("CREATE  TABLE ".$this->dbtable."_old ( ) INHERITS (".$this->dbtable.")",1);
    //$err = $objupdate-> exec_query("COPY ".$this->dbtable."_old FROM '".$dumpfile."'",				1 );
    $err = $objupdate-> exec_query("ALTER TABLE ".$this->dbtable.
				   " RENAME TO ".$this->dbtable."_old",
				   1 );
    
    
    if ($err != "") return ($err);
    
    // remove index : will be recreated in the following step (create)
    $err = $this-> exec_query("select indexname from pg_indexes where tablename='".$this->dbtable."_old'",1);
    $nbidx = $this->numrows();
    for ($c=0; $c < $nbidx; $c++) {
      
      $row = $this->fetch_array($c,PGSQL_ASSOC);
      $err = $objupdate-> exec_query("DROP INDEX ".$row["indexname"],
				     1 );
      
    }
    
    
    // --------------------------------------------
    // third : Create new table with new attributes
    $this->Create(true);
    
    
    
    // ---------------------------------------------------
    // 4th : copy compatible data from old table to new table
    $first=true;
    
    $this->exec_query("SELECT * FROM ".$this->dbtable."_old");
    $nbold = $this->numrows();
    for ($c=0; $c<$nbold;$c++) {
      
      
      $row = $this->fetch_array($c,PGSQL_ASSOC);
      
      if ($first) {
	// compute compatible fields
	$inter_fields = array_intersect(array_keys($row),$this->fields);
	reset($this->fields);
	$fields = "(";
	while (list($k,$v)=each($inter_fields)) {
	  $fields .= $v.",";
	}
	$fields=substr($fields,0,strlen($fields)-1); // remove last comma
	$fields .= ")";
	$first=false;
      }
      
      // compute compatible values
      $values = "(";
      reset($inter_fields);
      while (list($k,$v)=each($inter_fields)) {
	$values.= "'".addslashes($row[$v])."',";
      }
      $values=substr($values,0,strlen($values)-1); // remove last comma
      $values .= ")";
      
      // copy compatible values
      $err = $objupdate-> exec_query ("INSERT INTO ".$this->dbtable." ".$fields.
				      " VALUES ".$values,1);
      if ($err != "") return ($err);
      
    }
    
    // ---------------------------------------------------
    // 5th :delete old table (has been saved before - dump file)
    $err = $objupdate-> exec_query ("DROP TABLE ".$this->dbtable."_old",1);
    
    return ($err);
  }

  // FIN DE CLASSE
}
?>
