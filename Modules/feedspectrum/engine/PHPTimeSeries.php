<?php

class PHPTimeSeries
{

    /**
     * Constructor.
     *
     *
     * @api
     * modificado por Miguel Alonso para guardar curvas I-V o datos XY en general
    */

    private $timestoreApi;

    private $dir = "/var/lib/phptimeseries/";
    private $log;


    public function __construct($settings)
    {
        if (isset($settings['datadir'])) $this->dir = $settings['datadir'];
        $this->log = new EmonLogger(__FILE__);
    }

    public function create($feedspectrumid,$options)
    {
        $fh = fopen($this->dir."feedspectrum_$feedspectrumid.dat", 'a');
        if (!$fh) {
            $this->log->warn("PHPTimeSeries:create could not create data *.dat file feedspectrumid=$feedspectrumid");
        }
        fclose($fh);

        if (file_exists($this->dir."feedspectrum_$feedspectrumid.dat")) return true;
        return false;
    }

    // POST OR UPDATE
    //
    // - fix if filesize is incorrect (not a multiple of 9)
    // - append if file is empty
    // - append if datapoint is in the future
    // - update if datapoint is older than last datapoint value
   //http://emoncms.sytes.net/emoncms_spectrum/spectrum/postspectrum.json?json={power:250} hay que modificarlo para
    //incluir los datos correcto en formato I_V
    public function check_size($feedspectrumid,$id){
        //http://163.117.157.189/emoncms_spectrum/feedspectrum/checksize.json?apikey=92731de3e6389ebb7a731b43c70407df
        //hace una copia del fichero al limite en $max_file_size definido en  feedspectrum_model.php
        $size= filesize($this->dir."feedspectrum_$feedspectrumid.dat");
            //create and copy the file to a new feedspectrum
            //$fecha=date("Y-m-d_H_i_s",time());
            $fichero = $this->dir . "feedspectrum_$feedspectrumid.dat";
            $fichero_nuevo = $this->dir . "feedspectrum_$id.dat";
            if (!copy($fichero, $fichero_nuevo)) {
                echo "Error al copiar $fichero...\n";
                echo "tamaño del fichero:  $size\r\n";
            }else{
                unlink($this->dir."feedspectrum_$feedspectrumid.dat");
                $options="";
                $this->create($feedspectrumid,$options);
            }
    }

    public function post($feedspectrumid,$time,$value)
    {
        //$this->check_size($feedspectrumid,$time);
        $this->log->info("PHPTimeSeries:post feedspectrumid=$feedspectrumid time=$time value=$value");
        //modificación para guardar los datos de una curva, en lugar de un solo valor
        //ahora $value trae los datos de la curva I-V o datos XY con el formato de abajo
        echo "\r\nPHPTimeSeries:post guardando datos en feedspectrum_$feedspectrumid.dat\r\n";
        $fecha=date("Y-m-d H:i:s",$time);
        echo "\r\nFecha : $fecha \r\n";
        //ejemplo de cadena que se pasará en $value
       // $time=1416412502;
        //$value="[numero de serie, tab   por ejemplo][1.1,2.2,3.3,4.4,5.5][6.6,7.7,8.8,9.9,10.1]";
        //echo "entrada:$value";
        $vars = explode("[", $value);
        foreach($vars as &$cad) {
            $cad =rtrim($cad,"]");
        }
        $vars[3]=rtrim($vars[3],"]");
        $datos_IV =array(
                "tiempo"=>$time,
                "referencia"=>$vars[1],
                "datos_V"=>str_getcsv($vars[2]),
                "datos_I"=>str_getcsv($vars[3]),
        );
        //echo "\r\ndatos IV :\r\n";
        //echo ("ahora:\r\n");
        //print_r(array_values($datos_IV));
/*
        $datos_IV = array(
            "tiempo"=>$time,
            "referencia" => "numero de serie, tab   por ejemplo",
            "datos_V" => [1.1,2.2,3.3,4.4,5.5],
            "datos_I"   => [6.6,7.7,8.8,9.9,10.10],
        );
        echo"otros \r\n";
        print_r(array_values($datos_IV));
 */
        $cadena="[".$datos_IV["tiempo"]."]";
        $cadena.="[".$datos_IV["referencia"]."]";
        $cadena.="[".implode(",",$datos_IV["datos_V"])."]";
        $cadena.= "[".implode(",",$datos_IV["datos_I"])."]";
        $cadena.= "\r\n";
        //echo "cadena: $cadena";



        /*
                $datos_IV = array(
                    "tiempo"=>$time,
                    "referencia" => "numero de serie, tab   por ejemplo",
                    "datos_V" => [1.1,2.2,3.3,4.4,5.5],
                    "datos_I"   => [6.6,7.7,8.8,9.9,10.10],
                );
                $cadena="[".$datos_IV["tiempo"]."]";
                $cadena.="[".$datos_IV["referencia"]."]";
                $cadena.="[".implode(",",$datos_IV["datos_V"])."]";
                $cadena.= "[".implode(",",$datos_IV["datos_I"])."]";
                $cadena.= "\r\n";
                echo "datos_IV : $cadena\r\n";
        */

        $fichero = $this->dir . "feedspectrum_$feedspectrumid.dat";

        /*
        $fd = fopen($fichero, 'a');
        fwrite($fd, $cadena);
        fclose($fd);
*/
                /*// ejemlo de lectrura y decodificación del fichero datos I_V
                $fd = fopen($fichero, 'r');
                while($linea = fgets($fd)) {
                    //echo "linea $i: $linea";
                    $vars = explode("[", $linea);
                       foreach($vars as &$cad) {
                            $cad =rtrim($cad,"]");
                        }
                        $vars[4]=rtrim($vars[4],"]\r\n");
                        $datos_IV2 =array(
                                "tiempo"=>$vars[1],
                                "referencia"=>$vars[2],
                                "datos_V"=>$vars[3],
                                "datos_I"=>$vars[4],
                        );

                }
                fclose($fd);
                //echo " -- tiempo: $vars[1] \r\n--";
                //$cadena=$datos_IV2["datos_I"];
                //echo " -- datos_I: $cadena \r\n--";

                //$array_datos_I=str_getcsv($vars[4]);
                //print_r(array_values($array_datos_I));
                */
                //fin de ejemplo de lectura y decodificación del fichero de datos_IV
        // Get last value
        $fh = fopen($fichero, 'rb');
        if (!$fh) {
            $this->log->warn("PHPTimeSeries:post could not open *.dat data file feedspectrumid=$feedspectrumid");
            return false;
        }
        clearstatcache($fichero);
        $filesize = filesize($fichero);


       // If there is data then read last value
       /* ejemplo: leel las últimas 5 lineas
        $data = array_slice(file($fichero), -5);
        foreach ($data as $line) {
            echo $line;
        }*/
        if ($filesize>=9) {
            // read the last value appended to the file
            $data = array_slice(file($fichero), -1);
            $linea=$data[0];
            $vars = explode("[", $linea);
            foreach($vars as &$cad) {
                $cad =rtrim($cad,"]");
            }
            $vars[4]=rtrim($vars[4],"]\r\n");
            $datos_IV2 =array(
                "tiempo"=>$vars[1],
                "referencia"=>$vars[2],
                "datos_V"=>str_getcsv($vars[3]),
                "datos_I"=>str_getcsv($vars[4]),
            );
            //print_r(array_values($datos_IV2));
            $cadena2="[".$datos_IV2["tiempo"]."]"."[".$datos_IV2["referencia"]."]";
            $cadena2.="[".implode(",",$datos_IV2["datos_V"])."]";
            $cadena2.= "[".implode(",",$datos_IV2["datos_I"])."]\r\n";
            //print_r(array_values($datos_IV2));
          //  echo "cadena ultimo: $cadena2";
           // echo "time: $time\r\n";
            $tiempo_ultimo=$datos_IV2['tiempo'];
           // echo "tiempo_ultimo dato fichero: $tiempo_ultimo\r\n";
            if ($time>$datos_IV2['tiempo'])
            {
                // append
                fclose($fh);
                if (!$fh = $this->fopendata($fichero, 'a')) return false;
                fwrite($fh, $cadena);
                fclose($fh);
            }
            else
            {
                // if its not in the future then to update the feedspectrum
                // the datapoint needs to exist with the given time
                // - search for the datapoint
                // - if it exits update
                //no es posible insertar texto en un fichero salvo leerlo entero
                fclose($fh); //ceramos el fichero para poder buscar en él
                $pos = $this->binarysearch_exact($fh,$time,$filesize,$fichero);
                echo "posición: $pos\r\n";

                if ($pos!=-1)
                {
                    /*if (!$fh = $this->fopendata($fichero, 'a')) return false;
                    fwrite($fh, $cadena);
                    fclose($fh);*/
                    //se sustituye el valor ya existente, no estoy seguro que funcione
                    $file = new SplFileObject($fichero, 'c+');
                    $file->seek($pos);     // Seek to line no. 10,000
                    $file->fwrite($cadena);
                }
            }
        }
        else
        {
            // If theres no data in the file then we just append a first datapoint
            // append
            fclose($fh);
            if (!$fh = $this->fopendata($fichero, 'a')) return false;
            fwrite($fh, $cadena);
            fclose($fh);
        }

        return $value;
    }

    private function fopendata($filename,$mode)
    {
        $fh = fopen($filename,$mode);

        if (!$fh) {
            $this->log->warn("PHPTimeSeries:fopendata could not open $filename");
            return false;
        }
        
        if (!flock($fh, LOCK_EX)) {
            $this->log->warn("PHPTimeSeries:fopendata $filename locked by another process");
            fclose($fh);
            return false;
        }
        
        return $fh;
    }
    
    public function update($feedspectrumid,$time,$value)
    {
      return $this->post($feedspectrumid,$time,$value);
    }

    public function delete($feedspectrumid)
    {
        unlink($this->dir."feedspectrum_$feedspectrumid.dat");
    }

    public function get_feedspectrum_size($feedspectrumid)
    {
        return filesize($this->dir."feedspectrum_$feedspectrumid.dat");
    }

    public function get_data($feedspectrumid,$start,$end,$outinterval)
    {
        //http://emoncms.sytes.net/emoncms_spectrum/feedspectrum/data.json?id=50&start=1416476851000&end=1416476909000&dp=550
        $start = $start/1000; $end = $end/1000;

        $fh = fopen($this->dir."feedspectrum_$feedspectrumid.dat", 'rb');
        $filesize = filesize($this->dir."feedspectrum_$feedspectrumid.dat");
        $fichero=$this->dir."feedspectrum_$feedspectrumid.dat";
        $lines = count(file($fichero));
        $pos = $this->binarysearch($fh,$start,$filesize,$fichero);
        $pos_end = $this->binarysearch($fh,$end,$filesize,$fichero);
        $file = new SplFileObject($fichero);
        $lines = count(file($fichero));
//echo "--pos $pos\r\n";
//echo "--pos_end $pos_end\r\n";
//echo "--lines $lines\r\n";
        //if ($pos==$pos_end){$pos=$lines-50;$pos_end=$lines;}
        $data = array();
        $time = 0;
        if($outinterval<=0)$outinterval=1;
        if($outinterval>$lines) $outinterval=$lines-2;

//echo("interv: $outinterval\r\n");
        for ($pos=$pos; $pos<$pos_end; $pos=$pos+$outinterval)
        {
            $file->seek($pos);
            // Read the datapoint at this position
            $linea=$file->current();
            //    echo $linea;
            //$linea = fgets($fh);
            $vars = explode("[", $linea);
            foreach($vars as &$cad) {
                $cad =rtrim($cad,"]");
            }
            $vars[4]=rtrim($vars[4],"]\r\n");
            $datos_IV =array(
                "tiempo"=>$vars[1],
                "referencia"=>$vars[2],
                "datos_V"=>str_getcsv($vars[3]),
                "datos_I"=>str_getcsv($vars[4]),
            );
            $array =$datos_IV ;//unpack("x/Itime/fvalue",$d);
            $last_time = $time;
            $time = $datos_IV['tiempo'];
            //echo ("ahora:\r\n");
            //print_r(($datos_IV['datos_I']));
            //echo ($datos_IV['referencia']);
            //echo("\r\n");
            // $last_time = 0 only occur in the first run
            if (($time!=$last_time && $time>$last_time) || $last_time==0) {
                $data[] = array($time*1000,$datos_IV['referencia'],array_map('floatval', $datos_IV['datos_V']),array_map('floatval',$datos_IV['datos_I']));
            }
        }
        $file=NULL;
        return $data;
    }

// fin de get_data

    public function lastvalue($feedspectrumid)
    {
        if (!file_exists($this->dir."feedspectrum_$feedspectrumid.dat"))  return false;

        $fh = fopen($this->dir."feedspectrum_$feedspectrumid.dat", 'rb');
        $filesize = filesize($this->dir."feedspectrum_$feedspectrumid.dat");
        $fichero=$this->dir."feedspectrum_$feedspectrumid.dat";
        if ($filesize>=9) {
            $data = array_slice(file($fichero), -1);
            $linea = $data[0];
            $vars = explode("[", $linea);
            foreach ($vars as &$cad) {
                $cad = rtrim($cad, "]");
            }
            $vars[4] = rtrim($vars[4], "]\r\n");
            $datos_IV2 = array(
                "tiempo" => $vars[1],
                "referencia" => $vars[2],
                "datos_V" => str_getcsv($vars[3]),
                "datos_I" => str_getcsv($vars[4]),
            );
            $array['tiempo'] = date("Y-n-j H:i:s", $array['tiempo']);
            return $array;
        }
        else
        {
            return false;
        }
    }
    public function inicio($feedspectrumid)
    {
        if (!file_exists($this->dir."feedspectrum_$feedspectrumid.dat"))  return false;

        $fh = fopen($this->dir."feedspectrum_$feedspectrumid.dat", 'rb');
        $filesize = filesize($this->dir."feedspectrum_$feedspectrumid.dat");
        $fichero=$this->dir."feedspectrum_$feedspectrumid.dat";
        if ($filesize>=9) {
            $linea = (new SplFileObject($fichero))->fgets();
            $vars = explode("[", $linea);
            foreach ($vars as &$cad) {
                $cad = rtrim($cad, "]");
            }
            $vars[4] = rtrim($vars[4], "]\r\n");
            $datos_IV2 = array(
                "tiempo" => $vars[1],
                "referencia" => $vars[2],
                "datos_V" => str_getcsv($vars[3]),
                "datos_I" => str_getcsv($vars[4]),
            );
            //$array['tiempo'] = date("Y-n-j H:i:s", $array['tiempo']);
            return $datos_IV2['tiempo'];
        }
        else
        {
            return false;
        }
    }
    public function fin($feedspectrumid)
    {
        if (!file_exists($this->dir."feedspectrum_$feedspectrumid.dat"))  return false;

        $fh = fopen($this->dir."feedspectrum_$feedspectrumid.dat", 'rb');
        $filesize = filesize($this->dir."feedspectrum_$feedspectrumid.dat");
        $fichero=$this->dir."feedspectrum_$feedspectrumid.dat";
        if ($filesize>=9) {
            $data = array_slice(file($fichero), -1);
            $linea = $data[0];
            $vars = explode("[", $linea);
            foreach ($vars as &$cad) {
                $cad = rtrim($cad, "]");
            }
            $vars[4] = rtrim($vars[4], "]\r\n");
            $datos_IV2 = array(
                "tiempo" => $vars[1],
                "referencia" => $vars[2],
                "datos_V" => str_getcsv($vars[3]),
                "datos_I" => str_getcsv($vars[4]),
            );
            //$array['tiempo'] = date("Y-n-j H:i:s", $array['tiempo']);
            return $datos_IV2['tiempo'];
        }
        else
        {
            return false;
        }
    }
    private function binarysearch($fh,$time,$filesize,$fichero)
    {

        $lines = count(file($fichero));
        //echo "\r\nbinarysearch There are $lines lines in $fichero\r\n";
//echo("$time\r\n");

        $file = new SplFileObject($fichero);
        // $file->seek(9);     // Seek to line no. 10,000
        // $caa="sadfadfasdf";
        // $file->fwrite($caa);
        //echo "file: \r\n";
        //echo $file->current();

        if ($filesize==0) return -1;
        $start = 0; $end = $lines;
        for ($i=0; $i<30; $i++)
        {
            $mid = $start + round(($end-$start)/2);
            if($mid>=$lines) return $lines;
            $file->seek($mid);
            $linea=$file->current();
               // echo($mid);
                //echo $linea;
            //$linea = fgets($fh);
            $vars = explode("[", $linea);

                foreach ($vars as &$cad) {
                    $cad = rtrim($cad, "]");
                }
                $vars[4] = rtrim($vars[4], "]\r\n");
                $datos_IV = array(
                    "tiempo" => $vars[1],
                    "referencia" => $vars[2],
                    "datos_V" => $vars[3],
                    "datos_I" => $vars[4],
                );

            $tiempo=$datos_IV['tiempo'];
           // echo "time: $time tiempo:$tiempo mid: $mid start: $start end: $end\r\n";
            ///echo "$mid\r\n";
           // echo ("time: $time tiempo: $tiempo start: $start\r\n");
            if ($time==$datos_IV['tiempo']) return $mid;
            if (($end-$start)<=1) return $mid;
            if ($time>$datos_IV['tiempo']) $start = $mid; else $end = $mid;
        }
        //echo "$linea\r\n";
        $file=NULL;
        return $lines;

    }

    private function binarysearch_exact($fh,$time,$filesize,$fichero)
    {

        $lines = count(file($fichero));
      //  echo "\r\nThere are $lines lines in $fichero\r\n";


        $file = new SplFileObject($fichero);
       // $file->seek(9);     // Seek to line no. 10,000
       // $caa="sadfadfasdf";
       // $file->fwrite($caa);
        //echo "file: \r\n";
        //echo $file->current();

        if ($filesize==0) return -1;
        $start = 0; $end = $lines;
        for ($i=0; $i<30; $i++)
        {
            $mid = $start + round(($end-$start)/2);
            $file->seek($mid);
            $linea=$file->current();
        //    echo $linea;
            //$linea = fgets($fh);
            $vars = explode("[", $linea);
            foreach($vars as &$cad) {
                $cad =rtrim($cad,"]");
            }
            $vars[4]=rtrim($vars[4],"]\r\n");
            $datos_IV =array(
                   "tiempo"=>$vars[1],
                   "referencia"=>$vars[2],
                   "datos_V"=>$vars[3],
                   "datos_I"=>$vars[4],
            );
            $tiempo=$datos_IV['tiempo'];
            //echo "$tiempo\r\n";
            ///echo "$mid\r\n";
            if ($time==$datos_IV['tiempo']) return $mid;
            if (($end-$start)<=1) return $mid;
            if ($time>$datos_IV['tiempo']) $start = $mid; else $end = $mid;
        }
        //echo "$linea\r\n";
        $file=NULL;
        return -1;
    }

    public function export($feedspectrumid,$start)
    {
        $feedspectrumid = (int) $feedspectrumid;
        $start = (int) $start;

        $feedspectrumname = "feedspectrum_$feedspectrumid.dat";

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename={$feedspectrumname}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $fh = @fopen( 'php://output', 'w' );

        $primaryfeedspectrumname = $this->dir.$feedspectrumname;
        $primary = fopen($primaryfeedspectrumname, 'rb');
        $primarysize = filesize($primaryfeedspectrumname);

        //$localsize = intval((($start - $meta['start']) / $meta['interval']) * 4);

        $localsize = $start;
        $localsize = intval($localsize / 9) * 9;
        if ($localsize<0) $localsize = 0;

        fseek($primary,$localsize);
        $left_to_read = $primarysize - $localsize;
        if ($left_to_read>0){
            do
            {
                if ($left_to_read>8192) $readsize = 8192; else $readsize = $left_to_read;
                $left_to_read -= $readsize;

                $data = fread($primary,$readsize);
                fwrite($fh,$data);
            }
            while ($left_to_read>0);
        }
        fclose($primary);
        fclose($fh);
        exit;
    }
    
    public function get_meta($feedspectrumid)
    {
    
    }
    
    public function csv_export($feedspectrumid,$start,$end,$outinterval)
    {
        $feedspectrumid = (int) $feedspectrumid;
        $start = (int) $start;
        $end = (int) $end;
        $outinterval = (int) $outinterval;
        
        $tinicio=$this->inicio($feedspectrumid);
        $tfin=$this->fin($feedspectrumid);
        $fecha1=date("Y-n-j H:i:s", $tinicio);
        $fecha2=date("Y-n-j H:i:s", $tfin);
        echo "datos disponibles desde :$fecha1 tiempo INIX: $tinicio\r\n";
        echo "datos disponibles hasta :$fecha2 tiempo INIX: $tfin\r\n";
        if($start <$tinicio)$start <$tinicio;
        if($end >$tfin ||$end==0)$end <$tfin;

        $fh = fopen($this->dir."feedspectrum_$feedspectrumid.dat", 'rb');
        $filesize = filesize($this->dir."feedspectrum_$feedspectrumid.dat");
        fclose($fh);
        $fichero= $this->dir."feedspectrum_$feedspectrumid.dat";


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
        $posinicio = $this->binarysearch($fh,$start,$filesize,$fichero);
        $posfin = $this->binarysearch($fh,$end,$filesize,$fichero);
        echo "Fila inicio: $posinicio\r\n";
        echo "Fila fin: $posfin\r\n";
        $file = new SplFileObject($fichero);

        for ($i=$posinicio; $i<$posfin; $i++) {
            $file->seek($i);     // Seek to line no. 10,000
            $linea = $file->current();
            /*
            $vars = explode("[", $linea);
            foreach($vars as &$cad) {
                $cad =rtrim($cad,"]");
            }
            $vars[4]=rtrim($vars[4],"]\r\n");
            $datos_IV =array(
                "tiempo"=>$vars[1],
                "referencia"=>$vars[2],
                "datos_V"=>str_getcsv($vars[3]),
                "datos_I"=>str_getcsv($vars[4]),
            );
            $array = $datos_IV;

            $last_time = $time;
            $time = $array['tiempo'];

            // $last_time = 0 only occur in the first run
            if (($time!=$last_time && $time>$last_time) || $last_time==0) {

                $cadena="[".$datos_IV["tiempo"]."]";
                $cadena.="[".$datos_IV["referencia"]."]";
                $cadena.="[".implode(",",$datos_IV["datos_V"])."]";
                $cadena.= "[".implode(",",$datos_IV["datos_I"])."]";
                $cadena.= "\r\n";
                */
            fwrite($exportfh, $linea);
        }
        $file=NULL;
        fclose($exportfh);
        exit;
    }

}
