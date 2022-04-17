<!DOCTYPE html>
<HTML lang="en">
<html>

<head>
    <META HTTP-EQUIV="Refresh" CONTENT=2>
    <!--Pentru a da refresh automat paginii si a afla datele automat-->
    <link rel="stylesheet" href="sheet.css">
</head>


<?php
// Returns server load in percent (just number, without percent sign)
function _getServerLoadLinuxData()
{
    if (is_readable("/proc/stat")) {
        $stats = @file_get_contents("/proc/stat");

        if ($stats !== false) {
            // Remove double spaces to make it easier to extract values with explode()
            $stats = preg_replace("/[[:blank:]]+/", " ", $stats);

            // Separate lines
            $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
            $stats = explode("\n", $stats);

            // Separate values and find line for main CPU load
            foreach ($stats as $statLine) {
                $statLineData = explode(" ", trim($statLine));

                // Found!
                if (
                    (count($statLineData) >= 5) &&
                    ($statLineData[0] == "cpu")
                ) {
                    return array(
                        $statLineData[1],
                        $statLineData[2],
                        $statLineData[3],
                        $statLineData[4],
                    );
                }
            }
        }
    }

    return null;
}

function getServerLoad()
{
    $load = null;

    if (stristr(PHP_OS, "win")) {
        $cmd = "wmic cpu get loadpercentage /all";
        @exec($cmd, $output);

        if ($output) {
            foreach ($output as $line) {
                if ($line && preg_match("/^[0-9]+\$/", $line)) {
                    $load = $line;
                    break;
                }
            }
        }
    } else {
        if (is_readable("/proc/stat")) {
            // Collect 2 samples - each with 1 second period
            // See: https://de.wikipedia.org/wiki/Load#Der_Load_Average_auf_Unix-Systemen
            $statData1 = _getServerLoadLinuxData();
            sleep(1);
            $statData2 = _getServerLoadLinuxData();

            if (
                (!is_null($statData1)) &&
                (!is_null($statData2))
            ) {
                // Get difference
                $statData2[0] -= $statData1[0];
                $statData2[1] -= $statData1[1];
                $statData2[2] -= $statData1[2];
                $statData2[3] -= $statData1[3];

                // Sum up the 4 values for User, Nice, System and Idle and calculate
                // the percentage of idle time (which is part of the 4 values!)
                $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];

                // Invert percentage to get CPU time, not idle time
                $load = 100 - ($statData2[3] * 100 / $cpuTime);
            }
        }
    }

    return $load;
}

//----------------------------

function shapeSpace_server_memory_usage()
{

    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    $memory_usage = $mem[2] / $mem[1] * 100;
    $memory_list = array($mem[2], $memory_usage, $mem[1]);
    return $memory_list;
}

function shapeSpace_disk_usage()
{

    $disktotal = disk_total_space('/');
    $diskfree  = disk_free_space('/');
    $diskuse   = round(100 - (($diskfree / $disktotal) * 100)) . '%';
    $diskused = $disktotal - $diskfree;
    $disk_list = array($disktotal, $diskuse, $diskused);
    return $disk_list;
}

function get_browser_name($user_agent)
{
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
   
    return 'Other';
}


// Memory usage: 4.55 GiB / 23.91 GiB (19.013557664178%)
$memUsage_list = shapeSpace_server_memory_usage();
$mem_percent = round($memUsage_list[1], 2);
$mem_value = round($memUsage_list[0] / pow(1024, 2), 2);
$mem_total = round($memUsage_list[2] / pow(1024, 2), 2);
$diskUsage_list = shapeSpace_disk_usage();
$disk_percent = round($diskUsage_list[1], 2);
$disk_usage = round($diskUsage_list[2] / pow(1024, 3), 2);
$disk_total = round($diskUsage_list[0] / pow(1024, 3), 2);
$cpuLoad = getServerLoad();
$up_time = shell_exec('uptime -p');
$adresa_server = $_SERVER['SERVER_ADDR'];
$adresa_client = $_SERVER['REMOTE_ADDR'];
$browser_name = get_browser_name($_SERVER['HTTP_USER_AGENT']);

?>


<body>

    <div class="container">
        <div class="header">
            <center>Bun venit pe server!</center>
        </div>
        <div class="content-large">
            <center>On-Premise Server </center>
            <br></br>
            <center>
                <table border=1 class="center">
                    <tr>
                        <td><b>Server</b></td>
                        <td><b>IP Address</b></td>
                        <td><b>Port</b></td>
                        <td><b>CPU Load</b></td>
                        <td><b>RAM</b></td>
                        <td><b>Disk Usage</b></td>
                    </tr>
                    <tr>
                        <td> <?php echo $_SERVER['SERVER_NAME'] ?> </td>
                        <td> <?php echo $_SERVER['SERVER_ADDR'] ?> </td>
                        <td> <?php echo $_SERVER['SERVER_PORT'] ?> </td>
                        <td> <?php echo round($cpuLoad, 2) . "%" ?> </td>
                        <td> <?php echo sprintf(
                                    "%s GiB / %s GiB (%s%%)",
                                    $mem_value,
                                    $mem_total,
                                    $mem_percent
                                ) ?> </td>
                        <td> <?php echo sprintf(
                                    "%s GiB / %s Gib (%s%%)",
                                    $disk_usage,
                                    $disk_total,
                                    $disk_percent
                                ) ?> </td>
                    </tr>
                </table>
            </center>
            <br></br>
            <br></br>
            Server Uptime: <?php echo $up_time; ?>
            <br></br>
            IP Client: <?php echo $adresa_client; ?>
            <br></br>
            Browser Client: <?php echo $browser_name?>
            <br></br>
            Request: <?php echo $_SERVER['SERVER_PROTOCOL']; ?>
            <br></br>
            Request Method: <?php echo $_SERVER['REQUEST_METHOD']; ?>
            <br></br>
            Server Specification:
            <ul>
                <li>CPU: Ryzen 5 5500U @ 2.1 GHz </li>
                <!-- <li>Memory: <?php $file = file('/proc/meminfo');
                                    $mem_details = $file[0];
                                    $mem_details = substr($mem_details, 13, 12);
                                    $mem_value = round(floatval($mem_details) / (1024 * 1024), 3);
                                    echo $mem_value;
                                    ?> GiB</li> -->
                <li>Memory: 8 GiB </li>
                <li>Storage: 512 GiB, NVMe SSD</li>
            </ul>
            
        </div>
        <div class="content-small">
            <center>Detalii Server Amazon EC2</center>
            <ul>
                <li>CPU: Intel(R) Xeon(R) CPU E5-2676 @ 2.40 GHz</li>
                <li>Memory: 1 Gib</li>
                <li>Storage: 10 GiB, EBS</li>
            </ul>
        </div>
        <div class="content-small">
            <center>Detalii Server Google Cloud Platform</center>
            <ul>
                <li>CPU: Intel(R) Xeon(R) CPU @ 2.20 GHz</li>
                <li>Memory:4 Gib</li>
                <li>Storage:10 GiB, SCSI Disk</li>
            </ul>
        </div>
        <div class="footer">
            <center>
                <a href="https://www.k-business.com/en/kbc/about-us/international" target="_blank" rel="noopener noreferrer">
                    <img src="kapsch-logo-removebg-preview.png" width="80" height="80" /> </a>
                &nbsp; &nbsp;&nbsp;&nbsp;
                <a href="https://upb.ro/" target="_blank" rel="noopener noreferrer">
                    <img src="upb-logo-removebg-preview.png" width="80" height="80" /></a>
            </center>
        </div>
    </div>

</body>

</html>