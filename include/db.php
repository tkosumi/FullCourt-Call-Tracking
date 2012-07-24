<?php

	class DB {
		const DB_NAME = 'calls.sqlite';
		protected $db;

		function __construct() {
			$this->db = new PDO('sqlite:'.self::DB_NAME);
		}

		function init() {
			$this->db->exec('CREATE  TABLE IF NOT EXISTS calls ("CallSid" TEXT PRIMARY KEY  NOT NULL  UNIQUE , "DateCreated" DATETIME, "Direction" TEXT, "CallStatus" TEXT, "CallTo" TEXT, "CallFrom" TEXT, "Status" TEXT, "StartTime" DATETIME, "EndTime" DATETIME, "DialCallSid" TEXT, "DialCallStatus" TEXT, "CallerName" TEXT, "DialCallDuration" INTEGER);');
		}
	
		function save_call() {

		  if ($_REQUEST['To'] != '') {
			  //https://www.fullcourt.co/ja/docs/PhoneXML/request
		  	$CallTo = $_REQUEST['To'];
		  	$CallFrom=$_REQUEST['From'];
		  	$DialCallDuration=$_REQUEST['variable_billsec'];
		  	$Direction=$_REQUEST['Direction'];
		  	$CallerName=$_REQUEST['CallerName'];
		  	$CallSid = $_REQUEST['CallUUID'];
		  	$CallStatus=$_REQUEST['CallStatus'];
		  	$DialCallSid='';

  			$stmt = $this->db->prepare('INSERT INTO calls (DateCreated,CallSid,CallFrom,CallTo,CallStatus,Direction,CallerName,DialCallDuration,DialCallSid) VALUES (DATETIME(\'now\',\'localtime\'),?,?,?,?,?,?,?,?)');
		  	$vars=array($CallSid,$CallFrom,$CallTo,$CallStatus,$Direction,$CallerName,$DialCallDuration,$DialCallSid);
				$stmt->execute($vars);
		  } 

		functon saved_call() {
			  //https://www.fullcourt.co/ja/docs/PhoneXML/request
		  	$DialCallDuration=$_POST['variable_billsec'];
				$CallSid = $_POST['CallUUID'];
			  $DialCallSid=$_POST['DialBlegUUID'];
			  $CallStatus=$_POST['DialBLegStatus'];
			  $DialCallStatus=$_POST['DialBLegHangupCause'];

			  $stmt = $this->db->prepare('UPDATE calls set DialCallSid=?, DialCallStatus=?, DialCallDuration=?, CallStatus=? WHERE CallSid=?');
			  $vars=array($DialCallSid, $DialCallStatus, $DialCallDuration, $CallStatus, $CallSid);
				$stmt->execute($vars);
      }
		}

		function get_calls(){
			$result = $this->db->query('SELECT * FROM calls ORDER BY DateCreated DESC');
		
			$calls=array();

			foreach ($result as $row)
			{
				$call['CallSid'] = $row['CallSid'];
				$call['CallFrom'] = $row['CallFrom'];
				$call['CallTo'] = $row['CallTo'];
				$call['DialCallDuration'] = $row['DialCallDuration'];
				$call['DialCallStatus'] = $row['DialCallStatus'];
				$call['DateCreated'] = $row['DateCreated'];
				$calls[] = $call;
			}

			return $calls;
		
		}

		function get_calls_count(){
			$result = $this->db->query('SELECT count(*) as cnt, CallTo FROM calls GROUP BY CallTo ORDER BY cnt DESC');
		
			$calls=array();

			foreach ($result as $row)
			{
				$call['cnt'] = $row['cnt'];
				$call['CallTo'] = $row['CallTo'];
				$calls[] = $call;
			}

			return $calls;
		
		}


	}


	if (file_exists('calls.sqlite') != true)
	{
		$db = new DB();
		$db->init();
	}

?>
