#!/usr/bin/php
<?php
/**
 * Initialization of databse transformation server engine
 *
 * @author Anakeen 2007
 * @version $Id: te_server_init.php,v 1.4 2007/06/11 14:46:14 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package TE
 */
/**
 */
include_once("TE/Class.TEServer.php");

$targ=getArgv($argv);
$dbaccess=$targ["db"];
$dbid=@pg_connect($dbaccess);
if ($dbid) {
  $o=new Task($dbaccess);
  pg_query($dbid,$o->sqlcreate);
  $o=new Engine($dbaccess);
  pg_query($dbid,$o->sqlcreate);
  $sqlinit=file_get_contents("TE/engine_init.sql",true);
  pg_query($dbid,$sqlinit);
  exit(0);
}
echo sprintf("Error: could not connect to database '%s'!", $dbaccess);
exit(1);

?>