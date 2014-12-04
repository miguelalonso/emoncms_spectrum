<?php

class MysqlTimeSeries
{

    private $mysqli;

    /**
     * Constructor.
     *
     * @param api $mysqli Instance of mysqli
     *
     * @api
    */
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Creates a histogram type mysql table.
     *
     * @param integer $feedspectrumid The feedspectrumid of the histogram table to be created
    */
    public function create($feedspectrumid,$options)
    {
        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";

        $result = $this->mysqli->query(
        "CREATE TABLE $feedspectrumname (
    time INT UNSIGNED, data float,
        INDEX ( `time` )) ENGINE=MYISAM");

        return true;
    }

    public function post($feedspectrumid,$time,$value)
    {
        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";
        $this->mysqli->query("INSERT INTO $feedspectrumname (`time`,`data`) VALUES ('$time','$value')");
    }

    public function update($feedspectrumid,$time,$value)
    {
        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";
        // a. update or insert data value in feedspectrum table
        $result = $this->mysqli->query("SELECT * FROM $feedspectrumname WHERE time = '$time'");

        if (!$result) return $value;
        $row = $result->fetch_array();

        if ($row) $this->mysqli->query("UPDATE $feedspectrumname SET data = '$value' WHERE time = '$time'");
        if (!$row) {$value = 0; $this->mysqli->query("INSERT INTO $feedspectrumname (`time`,`data`) VALUES ('$time','$value')");}

        return $value;
    }

    public function get_data($feedspectrumid,$start,$end,$outinterval)
    {
        //echo $feedspectrumid;
        $outinterval = intval($outinterval);
        $feedspectrumid = intval($feedspectrumid);
        $start = floatval($start/1000);
        $end = floatval($end/1000);
                
        if ($outinterval<1) $outinterval = 1;
        $dp = ceil(($end - $start) / $outinterval);
        $end = $start + ($dp * $outinterval);
        if ($dp<1) return false;

        // Check if datatype is daily so that select over range is used rather than 
        // skip select approach
        $result = $this->mysqli->query("SELECT datatype FROM feedspectrums WHERE `id` = '$feedspectrumid'");
        $row = $result->fetch_array();
        $datatype = $row['datatype'];
        if ($datatype==2) $dp = 0;

        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";

        $data = array();
        $range = $end - $start;
        if ($range > 180000 && $dp > 0) // 50 hours
        {
            $td = $range / $dp;
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $feedspectrumname WHERE time BETWEEN ? AND ? ORDER BY time ASC LIMIT 1");
            $t = $start; $tb = 0;
            $stmt->bind_param("ii", $t, $tb);
            $stmt->bind_result($dataTime, $dataValue);
            for ($i=0; $i<$dp; $i++)
            {
                $tb = $start + intval(($i+1)*$td);
                $stmt->execute();
                if ($stmt->fetch()) {
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $dataTime * 1000;
                        $data[] = array($time, (float)$dataValue);
                    }
                }
                $t = $tb;
            }
        } else {
            if ($range > 5000 && $dp > 0)
            {
                $td = intval($range / $dp);
                $sql = "SELECT FLOOR(time/$td) AS time, AVG(data) AS data".
                    " FROM $feedspectrumname WHERE time BETWEEN $start AND $end".
                    " GROUP BY 1 ORDER BY time ASC";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $feedspectrumname".
                    " WHERE time BETWEEN $start AND $end ORDER BY time ASC";
            }

            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $dataValue = $row['data'];
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * 1000 * $td;
                        $data[] = array($time , (float)$dataValue);
                    }
                }
            }
        }

        return $data;
    }

    public function lastvalue($feedspectrumid)
    {
        $feedspectrumid = (int) $feedspectrumid;
        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";

        $result = $this->mysqli->query("SELECT time, data FROM $feedspectrumname ORDER BY time Desc LIMIT 1");
        if ($result && $row = $result->fetch_array()){
            $row['time'] = date("Y-n-j H:i:s", $row['time']);
            return array('time'=>$row['time'], 'value'=>$row['data']);
        } else {
            return false;
        }
    }

    public function export($feedspectrumid,$start)
    {
        // feedspectrum id and start time of feedspectrum to export
        $feedspectrumid = intval($feedspectrumid);
        $start = intval($start)-1;

        // Open database etc here
        // Extend timeout limit from 30s to 2mins
        set_time_limit (120);

        // Regulate mysql and apache load.
        $block_size = 400;
        $sleep = 80000;

        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";
        $fileName = $feedspectrumname.'.csv';

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$fileName}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $fh = @fopen( 'php://output', 'w' );

        // Load new feedspectrum blocks until there is no more data
        $moredata_available = 1;
        while ($moredata_available)
        {
            // 1) Load a block
            $result = $this->mysqli->query("SELECT * FROM $feedspectrumname WHERE time>$start
            ORDER BY time Asc Limit $block_size");

            $moredata_available = 0;
            while($row = $result->fetch_array())
            {
                // Write block as csv to output stream
                if (!isset($row['data2'])) {
                    fputcsv($fh, array($row['time'],$row['data']));
                } else {
                    fputcsv($fh, array($row['time'],$row['data'],$row['data2']));
                }

                // Set new start time so that we read the next block along
                $start = $row['time'];
                $moredata_available = 1;
            }
            // 2) Sleep for a bit
            usleep($sleep);
        }

        fclose($fh);
        exit;
    }

    public function delete_data_point($feedspectrumid,$time)
    {
        $feedspectrumid = intval($feedspectrumid);
        $time = intval($time);

        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";
        $this->mysqli->query("DELETE FROM $feedspectrumname where `time` = '$time' LIMIT 1");
    }

    public function deletedatarange($feedspectrumid,$start,$end)
    {
        $feedspectrumid = intval($feedspectrumid);
        $start = intval($start/1000.0);
        $end = intval($end/1000.0);

        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";
        $this->mysqli->query("DELETE FROM $feedspectrumname where `time` >= '$start' AND `time`<= '$end'");

        return true;
    }

    public function delete($feedspectrumid)
    {
        $this->mysqli->query("DROP TABLE feedspectrum_".$feedspectrumid);
    }

    public function get_feedspectrum_size($feedspectrumid)
    {
        $feedspectrumname = "feedspectrum_".$feedspectrumid;
        $result = $this->mysqli->query("SHOW TABLE STATUS LIKE '$feedspectrumname'");
        $row = $result->fetch_array();
        $tablesize = $row['Data_length']+$row['Index_length'];
        return $tablesize;
    }
    
    public function get_meta($feedspectrumid)
    {
    
    }
    
    public function csv_export($feedspectrumid,$start,$end,$outinterval)
    {
        //echo $feedspectrumid;
        $outinterval = intval($outinterval);
        $feedspectrumid = intval($feedspectrumid);
        $start = floatval($start/1000);
        $end = floatval($end/1000);
        
        if ($outinterval<1) $outinterval = 1;
        $dp = ceil(($end - $start) / $outinterval);
        $end = $start + ($dp * $outinterval);
        if ($dp<1) return false;

        if ($end == 0) $end = time();

        $feedspectrumname = "feedspectrum_".trim($feedspectrumid)."";

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        $filename = $feedspectrumid.".csv";
        header("Content-Disposition: attachment; filename={$filename}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $exportfh = @fopen( 'php://output', 'w' );

        $data = array();
        $range = $end - $start;
        if ($range > 180000 && $dp > 0) // 50 hours
        {
            $td = $range / $dp;
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $feedspectrumname WHERE time BETWEEN ? AND ? LIMIT 1");
            $t = $start; $tb = 0;
            $stmt->bind_param("ii", $t, $tb);
            $stmt->bind_result($dataTime, $dataValue);
            for ($i=0; $i<$dp; $i++)
            {
                $tb = $start + intval(($i+1)*$td);
                $stmt->execute();
                if ($stmt->fetch()) {
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $dataTime * 1000;
                        fwrite($exportfh, $dataTime.",".number_format($dataValue,2)."\n");
                    }
                }
                $t = $tb;
            }
        } else {
            if ($range > 5000 && $dp > 0)
            {
                $td = intval($range / $dp);
                $sql = "SELECT FLOOR(time/$td) AS time, AVG(data) AS data".
                    " FROM $feedspectrumname WHERE time BETWEEN $start AND $end".
                    " GROUP BY 1 ORDER BY time ASC";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $feedspectrumname".
                    " WHERE time BETWEEN $start AND $end ORDER BY time ASC";
            }

            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $dataValue = $row['data'];
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * 1000 * $td;
                        fwrite($exportfh, $dataTime.",".number_format($dataValue,2)."\n");
                    }
                }
            }
        }
        
        fclose($exportfh);
        exit;
    }

}
