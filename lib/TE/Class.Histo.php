<?php
/**
 * Tranformation Engine Historic
 *
 * @author Anakeen 2005
 * @version $Id: Class.Histo.php,v 1.2 2007/06/06 18:12:01 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package TE
 */
/**
 */


include_once("Class.PgObj.php");
Class Histo extends PgObj {
  public $fields = array ( "tid", 
			   "comment"// comment text
			   );
  public $sup_fields = array ("date");
  /**
   * task identificator
   * @public string
   */
  public $tid;  
  		  
		  
  /**
   * description of the action
   * @public string
   */
  public $comment;
  


  public $id_fields = array ("tid","date");

  public $dbtable = "histo";


  public $sqlcreate = "
create table histo ( tid int not null,   
                   date timestamp default now(),
                   comment text  );";


}
?>