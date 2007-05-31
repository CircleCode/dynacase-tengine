<?php
/**
 * Transformation server engine
 *
 * @author Anakeen 2007
 * @version $Id: Class.TERendering.php,v 1.2 2007/05/31 16:20:04 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */

include_once("Lib.TEUtil.php");
include_once("Class.Task.php");
include_once("Class.QueryPg.php");
include_once("Class.Engine.php");

// for signal handler function
declare (ticks = 1);

Class TERendering {
  public $cur_client=0;
  public $max_client=2;
  public $dbaccess="dbname=te user=postgres";
  public $password="anakeen";
  public $login="admin";
  public $tmppath="/var/tmp";

  function decrease_child($sig) {
    $this->cur_client--;
        echo "One Less [$sig]  ".$this->cur_client."\n";
    pcntl_wait($status); // to suppress zombies
  
  }
   function rewaiting($sig) {
    if ($this->task) {
      $this->task->status='W'; // waiting 
      $this->task->comment='Interrupted'; 
      $this->task->Modify();
    }
    exit(0);
  }

  /**
   * main loop to listen socket
   */
  function listenLoop() {
   

    error_reporting(E_ALL);

    /* Autorise l'exécution infinie du script, en attente de connexion. */
    set_time_limit(0);

    /* Active le vidage implicite des buffers de sortie, pour que nous
     * puissions voir ce que nous lisons au fur et à mesure. */
    ob_implicit_flush();


    pcntl_signal(SIGCHLD, array(&$this,"decrease_child"));
    pcntl_signal(SIGPIPE, array(&$this,"decrease_child"));




    while (true) {
      $this->task=$this->getNextTask();
     
      echo "Wait [".$this->cur_client."]\n";
      if ($this->task) {

      if ($this->cur_client> $this->max_client) {
	echo "Too many [".$this->cur_client."]\n";
	  
      } else {
	echo "Accept [".$this->cur_client."]\n";
	  $this->cur_client++;
	  $pid = pcntl_fork();
            
	  if ( $pid == -1 ) {       
	    // Fork failed           
	    exit(1);
	  } else if ( $pid ) {
	    // We are the parent
    
	    echo "Parent Waiting Accept:".$this->cur_client."\n";
    

	  } else {
	    // We are the child
	    // Do something with the inherited connection here
	    // It will get closed upon exit
	    /* Send instructions. */
	   
	    pcntl_signal(SIGINT,  array(&$this,"rewaiting"));
	    $this->task->status='P'; // Processing
	    $err=$this->task->modify();

	    echo "Processing :".$this->task->tid."\n";
	    sleep(3);
	    $eng=new Engine($this->dbaccess,array($this->task->engine,$this->task->inmime));
	    if (! $eng->isAffected()) {
	      $eng=$eng->GetNearEngine($this->task->engine,$this->task->inmime);	
	    }
	    if ($eng && $eng->isAffected()) {
	      if ($eng->command) {
		$orifile = $this->task->infile;
		$outfile= $this->tmppath."/out-".posix_getpid().".".$eng->name;
		$errfile=$outfile.".err";
		$tc=sprintf("%s %s %s 2>%s",
			    $eng->command,
			    $orifile,
			    $outfile,
			    $errfile);
		print "execute $tc\n";
		system($tc,$retval);
		if (! file_exists($outfile)) $retval=-1;
		if ($retval!=0) {
		  //error mode
		  $err=file_get_contents($errfile);
		  $this->task->comment=str_replace('<','',$err);
		  $this->task->status='K';
		} else {
		  $warcontent=str_replace('<','',file_get_contents($errfile));
		  $this->task->outfile=$outfile;
		  $this->task->status='D';
		  $this->task->comment=sprintf(_("generated by [%s] command"),$eng->command)."\n$warcontent";
		  
		}
		
		$err=$this->task->modify();

		$callback=$this->task->callback;
		if ($callback) {
		  $turl=parse_url($callback);
		  $turl["pass"]=$this->password;
		  $turl["user"]=$this->login;
		  $turl["query"].="&tid=".$this->task->tid;
		  $url=$this->implode_url($turl);
		  $response = @file_get_contents($url);

		 
		  $this->task->callreturn=utf8_encode(str_replace('<','',$response));
		  $err=$this->task->modify();
		  print "ERROE:$err\n";
		}	      
	      } 
	    } else {
	      $this->task->comment=_("no compatible engine found");
	      $this->task->status='K'; // KO
	      $err=$this->task->modify();	      
	    
	    }
	    exit(0);
	  }
	}
      }
      sleep(1);
    } 

  }
  
  function getNextTask() {
    $q=new QueryPg($this->dbaccess,"Task");
    $q->AddQuery("status='W'");
    $l=$q->Query(0,1);
    if ($q->nb >0) return $l[0];
    return false;    

  }


  function implode_url($turl) {    
    print_r($turl);
    if (isset($turl["scheme"])) $url=$turl["scheme"]."://";
    else $url="http://";
    if (isset($turl["user"]) && isset($turl["pass"]))  $url.=$turl["user"].':'.$turl["pass"].'@';
    if (isset($turl["host"])) $url.=$turl["host"];
    else $url.="localhost";
    if (isset($turl["port"])) $url.=':'.$turl["port"];
    if (isset($turl["path"]) && ($turl["path"][0]=='&')) {
      $turl["query"]=$turl["path"].$turl["query"];
      $turl["path"]='';
    }
    if (isset($turl["path"])) $url.=$turl["path"];
    if (isset($turl["query"])) $url.='?'.$turl["query"];
    if (isset($turl["fragment"])) $url.='#'.$turl["fragment"];
    print $url;
    return $url;
  }

  
}



?>