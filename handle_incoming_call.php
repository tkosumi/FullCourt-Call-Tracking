<?php
	require_once('./include/config.php');
	header('Content-type: text/xml');
?>
<Response>
	<Dial callbackUrl="http://dev.fullcourt.co/FullCourt-Call-Tracking/record_call.php" method="GET" callerId="815058381227">
		<Number><?php echo(AGENT_NUMBER);?></Number>
	</Dial>
</Response>
