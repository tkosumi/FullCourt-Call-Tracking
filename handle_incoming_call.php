<?php
	require_once('./include/config.php');
	header('Content-type: text/xml');
	$db = new DB();
	$db->save_call();
?>
<Response>
	<Dial action="http://dev.fullcourt.co/FullCourt-Call-Tracking/record_call.php" method="GET">
		<Number><?php echo(AGENT_NUMBER);?></Number>
	</Dial>
</Response>
