<?php

include('ssl.inc.php');
include('timezone.inc.php');
require('miner.inc.php');
include('settings.inc.php');


create_graph("mhsav-hour.png", "-1h", "Hourly");
create_graph("mhsav-day.png", "-1d", "Daily");
create_graph("mhsav-week.png", "-1w", "Weekly");
create_graph("mhsav-month.png", "-1m", "Monthly");
create_graph("mhsav-year.png", "-1y", "Yearly");

function create_graph($output, $start, $title) {
  $RRDPATH = '/opt/minepeon/var/rrd/';
  $options = array(
    "--slope-mode",
    "--start", $start,
    "--title=$title",
    "--vertical-label=Hash per second",
    "--lower=0",
    "DEF:hashrate=" . $RRDPATH . "hashrate.rrd:hashrate:AVERAGE",
    "CDEF:realspeed=hashrate,1000,*",
    "LINE2:realspeed#FF0000"
  );

  $ret = rrd_graph("/opt/minepeon/http/rrd/" . $output, $options);
  if (! $ret) {
    echo "<b>Graph error: </b>".rrd_error()."\n";
  }
}

$stats = cgminer("devs", "");
$status = $stats['STATUS'];
$devs = $stats['DEVS'];
$summary = cgminer("summary", "");
$pools = cgminer("pools", "");

include('head.php');
include('menu.php');
?>
	<div class="container">
			<center><h3>Update Time: <?php echo date('D, d M Y H:i:s T') ?></h3><center>
			<div class=graph><img src="rrd/mhsav-hour.png" alt="mhsav.png" /></div>
			<div class=graph><img src="rrd/mhsav-day.png" alt="mhsav.png" /><img src="rrd/mhsav-week.png" alt="mhsav.png" /></div>
		<div class=graph><img src="rrd/mhsav-month.png" alt="mhsav.png" /><img src="rrd/mhsav-year.png" alt="mhsav.png" /></div>
	</div>
	<div class="container">
		<br />
		<table id="status" class="table table-striped table-bordered table-hover table-condensed stats">
			<tr>
				<th colspan="6">MinePeon Status</th>
			</tr>
			<tr>
				<th>MinePeon Version</th>
				<th>Miner Version</th>
				<th>MinePeon Uptime</th>	
				<th>Miner Uptime</th>
				<th>Best Share</th>
				<th>Donation Minutes</th>
			</tr>
			<tr>
				<td><?php echo $version; ?></td>
				<td><?php echo $summary['STATUS'][0]['Description']; ?></td>
				<td><?php echo secondsToWords(round($uptime[0])); ?></td>	
				<td><?php echo secondsToWords($summary['SUMMARY'][0]['Elapsed']); ?></td>
				<td><?php echo $summary['SUMMARY'][0]['BestShare']; ?></td>
				<td><?php echo $donation; if ($donation == 0) { echo ' <marquee direction="left" scrollamount="3" behavior="scroll" style="width: 60px; height: 15px; color: #ff0000; font-size: 11px; text-decoration: blink;">Kitten Killer!</marquee></p>'; } ?></td>
			</tr>
		</table>
	</div>
	<div class="container">
		<table id="pools" class="tablesorter table table-striped table-bordered table-hover table-condensed pools">
			<thead> 
			<tr>
				<th colspan="17">Pool Status</th>
			</tr>
			<tr>
				<th>URL</th>
				<th>User</th>
				<th>Status</th>
				<th>Priority</th>
				<th>Getworks</th>
				<th>Accept</th>
				<th>Reject</th>
				<th>Discard</th>				
				<th>Last <br />Share</th>				
				<th>Diff1 <br />Shares</th>				
				<th>Diff <br />Accept</th>
				<th>Diff <br />Reject</th>
				<th>Last Share <br />Difficulty</th>
				<th>Best <br />Share</th>			
			</tr>
			</thead>
			<tbody>
			<?php echo poolsTable($pools['POOLS']);  ?>
		</table>
	</div>
	<div class="container">
		<table id="stats" class="tablesorter table table-striped table-bordered table-hover table-condensed stats">
			<thead> 
			<tr>
				<th colspan="9">Device Status</th>
			</tr>
			<tr>
				<th>Name</th>
				<th>ID</th>
				<th>Temp</th>
				<th>MH/s</th>
				<th>Accept</th>
				<th>Reject</th>
				<th>Error</th>
				<th>Utility</th>
				<th>Last Share</th>
			</tr>
			</thead>
			<tbody>
			<?php echo statsTable($devs);  ?>
		</table>
	</div>
	<?php if($donation == 0) { echo $plea; } ?>
<?php

include('foot.php');

function statsTable($devs) {

	$devices = 0;
	$MHSav = 0;
	$Accepted = 0;
	$Rejected = 0;
	$HardwareErrors = 0;
	$Utility = 0;

	$tableRow = "";
	
	foreach ($devs as $dev) {
		
		if ($dev['MHS5s'] > 0) {
		$tableRow = $tableRow . "

			<tr>
				<td>" . $dev['Name'] . "</td>
				<td>" . $dev['ID'] . "</td>
				<td>" . $dev['Temperature'] . "</td>
				<td><a href='http://mineforeman.com/bitcoin-mining-calculator/?hash=" . $dev['MHSav'] . "' target='_blank'>" . $dev['MHSav'] . "</a></td>
				<td>" . $dev['Accepted'] . "</td>
				<td>" . $dev['Rejected'] . " ["  . round($dev['Accepted'] / $dev['Rejected'] / 100, 2) .  "%]</td>
				<td>" . $dev['HardwareErrors'] . " ["  . round($dev['Accepted'] / $dev['HardwareErrors'] / 100, 2) .  "%]</td>
				<td>" . $dev['Utility'] . "</td>
				<td>" . date('H:i:s', $dev['LastShareTime']) . "</td>
			</tr>";
			
		$devices++;
		$MHSav = $MHSav + $dev['MHSav'];
		$Accepted = $Accepted + $dev['Accepted'];
		$Rejected = $Rejected + $dev['Rejected'];
		$HardwareErrors = $HardwareErrors + $dev['HardwareErrors'];
		$Utility = $Utility + $dev['Utility'];
		
		}
	}
	
	$tableRow = $tableRow . "
		</tbody>
		<tfoot>
			<tr>
				<th>Totals</th>
				<th>" . $devices . "</th>
				<th></td>
				<th><a href='http://mineforeman.com/bitcoin-mining-calculator/?hash=" . $MHSav . "' target='_blank'>" . $MHSav . "</a></th>
				<th>" . $Accepted . "</th>
				<th>" . $Rejected . " ["  . round($Accepted / $Rejected / 100, 2) .  "%]</th>
				<th>" . $HardwareErrors . " ["  . round($Accepted / $HardwareErrors / 100, 2) .  "%]</th>
				<th>" . $Utility . "</th>
				<th></th>
			</tr>
		</tfoot>
";
		
	

	return $tableRow;

}

function secondsToWords($seconds)
{
    $ret = "";

    /*** get the days ***/
    $days = intval(intval($seconds) / (3600*24));
    if($days> 0)
    {
        $ret .= "$days days ";
    }

    /*** get the hours ***/
    $hours = (intval($seconds) / 3600) % 24;
    if($hours > 0)
    {
        $ret .= "$hours hours ";
    }

    /*** get the minutes ***/
    $minutes = (intval($seconds) / 60) % 60;
    if($minutes > 0)
    {
        $ret .= "$minutes minutes ";
    }

    /*** get the seconds ***/
    $seconds = intval($seconds) % 60;
    if ($seconds > 0) {
        $ret .= "$seconds seconds";
    }

    return $ret;
}

function poolsTable($pools) {

// class="success" error warning info

	$table = "";
	foreach ($pools as $pool) {

		if ($pool['Status'] <> "Alive") {
		
			$rowclass = 'error';
			
		} else {
		
			$rowclass = '';
			
		}
	
		$table = $table . "
			<tr  class='" . $rowclass . "'>
				<td>" . $pool['URL'] . "</td>
				<td>" . $pool['User'] . "</td>
				<td>" . $pool['Status'] . "</td>
				<td>" . $pool['Priority'] . "</td>
				<td>" . $pool['Getworks'] . "</td>
				<td>" . $pool['Accepted'] . "</td>
				<td>" . $pool['Rejected'] . " ["  . round($pool['Accepted'] / $pool['Rejected'] / 100, 2) .  "%]</td>
				<td>" . $pool['Discarded'] . " ["  . round($pool['Accepted'] / $pool['Discarded'] / 100, 2) .  "%]</td>				
				<td>" . date('H:i:s', $pool['LastShareTime']) . "</td>				
				<td>" . $pool['Diff1Shares'] . "</td>				
				<td>" . $pool['DifficultyAccepted'] . "</td>
				<td>" . $pool['DifficultyRejected'] . "</td>
				<td>" . $pool['LastShareDifficulty'] . "</td>
				<td>" . $pool['BestShare'] . "</td>			
			</tr>";
			
	}
	
	return $table;
			
}

