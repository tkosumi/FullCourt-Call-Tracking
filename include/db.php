<?php

	class DB {
		const DB_NAME = 'calls.sqlite';
		protected $db;

		function __construct() {
			$this->db = new PDO('sqlite:'.self::DB_NAME);
		}

		function init() {
			$this->db->exec('CREATE  TABLE IF NOT EXISTS calls ("CallSid" TEXT PRIMARY KEY  NOT NULL  UNIQUE , "DateCreated" DATETIME, "Direction" TEXT, "CallStatus" TEXT, "CallTo" TEXT, "CallFrom" TEXT, "Status" TEXT, "StartTime" DATETIME, "EndTime" DATETIME, "DialCallSid" TEXT, "DialCallStatus" TEXT);');
		}
	
		function save_call() {

			if ($_POST['DialHangupCause'] != "") {
				$CallSid = $_POST['DialALegUUID'];
			  $DialCallSid=$_POST['DialBlegUUID'];
			  $DialCallStatus=$_POST['DialHangupCause'];

			  $stmt = $this->db->prepare('UPDATE calls set DialCallSid=?, DialCallStatus=? WHERE CallSid=?');
			  $stmt->execute(array($DialCallSid, $DialCallStatus, $CallSid));
			} else {
			  //https://www.fullcourt.co/ja/docs/PhoneXML/request
			  $CallSid = $_POST['CallUUID'];
			  $CallFrom=$_POST['From'];
			  $CallTo=$_POST['To'];
			  $CallStatus=$_POST['CallStatus'];
			  $Direction=$_POST['Direction'];
      }

			$stmt = $this->db->prepare('INSERT INTO calls (DateCreated,CallSid,CallFrom,CallTo,CallStatus,Direction) VALUES (DATETIME(\'now\',\'localtime\'),?,?,?,?,?)');
			$vars=array($CallSid,$CallFrom,$CallTo,$CallStatus,$Direction);
			$stmt->execute($vars);
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
