<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visspectrumualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    */

    global $path, $embed;
//echo "path $path";
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/visspectrum/visualisations/visspectrum.helper.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.js"></script>

<div id="visspectrum-title"></div>

<div id="placeholder_bound" style="width:100%; height:400px; position:relative; ">
    <div id="placeholder" style="position:absolute; top:0px;"></div>
    <div id="graph_buttons" style="position:absolute; top:18px; left:32px; opacity:0.5;">

        <div class='btn-group'>
            <button class='btn time' type='button' time='1'>D</button>
            <button class='btn time' type='button' time='7'>W</button>
            <button class='btn time' type='button' time='30'>M</button>
            <button class='btn time' type='button' time='365'>Y</button>
        </div>

        <div class='btn-group'>
            <button id='zoomin' class='btn' >+</button>
            <button id='zoomout' class='btn' >-</button>
            <button id='left' class='btn' ><</button>
            <button id='right' class='btn' >></button>
        </div>

    </div>

    <h3 style="position:absolute; top:0px; right:25px;"><span id="stats"></span></h3>
</div>

<div id="info" style="padding:20px; margin:25px; background-color:rgb(245,245,245); font-style:italic; display:none">
    <p><b>Mean:</b> <span id="stats-mean"></span></p>
    <p><b>Min:</b> <span id="stats-min"></span></p>
    <p><b>Max:</b> <span id="stats-max"></span></p>
    <p><b>Standard deviation:</b> <span id="stats-stdev"></span></p>
    <p><b>Datapoints in view:</b> <span id="stats-npoints"></span></p>
</div>


<script id="source" language="javascript" type="text/javascript">
console.log(urlParams);

var feedspectrumname = "<?php echo $feedspectrumidname; ?>";
var path = "<?php echo $path; ?>";
var apikey = "<?php echo $apikey; ?>";
var embed = <?php echo $embed; ?>;
var valid = "<?php echo $valid; ?>";
var feedspectrumid = urlParams.feedspectrumid;
var interval = urlParams.interval;
    if (interval==undefined || interval=='') interval = 3600*24;
var plotColour = urlParams.colour;
    if (plotColour==undefined || plotColour=='') plotColour = "EDC240";
var units = urlParams.units;
    if (units==undefined || units=='') units = "";
var dp = urlParams.dp;
    if (dp==undefined || dp=='') dp = 2;
var scale = urlParams.scale;
    if (scale==undefined || scale=='') scale = 1;
var fill = +urlParams.fill;
    if (fill==undefined || fill=='') fill = 0;
if (fill>0) fill = true;
// Some browsers want the colour codes to be prepended with a "#". Therefore, we
// add one if it's not already there
if (plotColour.indexOf("#") == -1) {
    plotColour = "#" + plotColour;
}

var top_offset = 0;
var placeholder_bound = $('#placeholder_bound');
var placeholder = $('#placeholder');

var width = placeholder_bound.width();
var height = width * 0.5;

placeholder.width(width);
placeholder_bound.height(height);
placeholder.height(height-top_offset);

if (embed) placeholder.height($(window).height()-top_offset);

var timeWindow = (3600000*24.0*7);
view.start = +new Date - timeWindow;
view.end = +new Date;

var data = [];
$(function() {

    if (embed==false) {
        $("#visspectrum-title").html("<br><h2>Graph: "+feedspectrumname+"<h2>");
        $("#info").show();
    }
    draw();
    
    $("#zoomout").click(function () {view.zoomout(); draw();});
    $("#zoomin").click(function () {view.zoomin(); draw();});
    $('#right').click(function () {view.panright(); draw();});
    $('#left').click(function () {view.panleft(); draw();});
    $('.time').click(function () {view.timewindow($(this).attr("time")); draw();});
    
    placeholder.bind("plotselected", function (event, ranges)
    {
        view.start = ranges.xaxis.from;
        view.end = ranges.xaxis.to;
        draw();
    });

    placeholder.bind("plothover", function (event, pos, item)
    {
        if (item) {
            var datestr = (new Date(item.datapoint[0])).format("ddd, mmm dS, yyyy");
            var fecha=(new Date(item.datapoint[0])).format("ddd, mmm dS, yyyy hh.MM:ss.l");
            $("#stats").html(fecha);
            if (previousPoint != item.datapoint)
            {
                previousPoint = item.datapoint;

                $("#tooltip").remove();
                var itemTime = item.datapoint[0];
                var itemVal = item.datapoint[1];

                // I'd like to eventually add colour hinting to the background of the tooltop.
                // This is why showTooltip has the bgColour parameter.
               // tooltip(item.pageX, item.pageY, itemVal.toFixed(dp) + " " + units, "#DDDDDD");
                tooltip(item.pageX, item.pageY, fecha+" Value:"+itemVal.toFixed(3) + " " + units, "#DDDDDD");
            }
        }
        else
        {
            $("#tooltip").remove();
            previousPoint = null;
        }
    });

    function draw()
    {
        data = [];
        var npoints = 50000;
        //interval = Math.round(((view.end - view.start)/npoints)/1000);
        interval = ((view.end - view.start)/npoints)/1000;
        //alert (interval);
        $.ajax({
            url: path+'feedspectrum/average.json',
            data: "id="+feedspectrumid+"&start="+view.start+"&end="+view.end+"&interval="+interval,
            dataType: 'json',
            async: false,                      
            success: function(data_in) { data = data_in; } 
        });

        //datos=data[0][3][55];
        //alert(datos);
//ahora reordenamos los datos para poner el tiempo y los valores de I
// en el tiempo están los valores de X separados 1 ms
//[[1416504166000,
// "numero de serie, tab   por ejemplo",
// [1.1,2.2,3.3,4.4,5.5],
// [6.6,7.7,8.8,9.9,10.1]],
// [1416504255000,"numero de serie, tab   por ejemplo",[1.1,2.2,3.3,4.4,5.5],[6.6,7.7,8.8,9.9,10.1]],
        var salida = [];
        var delta=0;
        for (var z=0; z<data.length; z++) {
            var tiempo = data[z][0];
            var t_ini=tiempo;
            var array_V = data [z][2];

            for (var i = 0; i < array_V.length; i++) {
                delta= Math.abs(data[z][2][i+1]-data[z][2][i]);
                tiempo=tiempo+delta;
                salida.push([tiempo, data[z][3][i]]);
            }
            var t_fin=tiempo;
        }
        data = salida;
        //datos=data[0][0];
        //alert(t_ini);
        //alert(t_fin);

        out = [];
        if (scale!=1) {
            for (var z=0; z<data.length; z++) {
                var val = data[z][1] * scale;
                out.push([data[z][0],val]);
            }
            data = out;
        } 
       
        stats.calc(data);
        
        $("#stats-mean").html(stats.mean.toFixed(dp)+units);
        $("#stats-min").html(stats.min.toFixed(dp)+units);
        $("#stats-max").html(stats.max.toFixed(dp)+units);
        $("#stats-stdev").html(stats.stdev.toFixed(dp)+units);
        $("#stats-npoints").html(data.length);
        plot();
    }
    
    function plot()
    {
        var options = {
            lines: { show: true,fill: fill },
            points: { show: true },
           // xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end, minTickSize: [interval, "second"] },
            xaxis: { mode: "time", timezone: "browser", min: view.start, max: view.end ,minTickSize: [interval, "second"]},
            yaxis: { tickDecimals:"2" },
            //yaxis: { min: 0 },
            grid: {hoverable: true, clickable: true},
            selection: { mode: "x" }
        }

        $.plot(placeholder, [{data:data,color: plotColour}], options);
    }

    placeholder.click(function(){
      $("#graph_buttons").css('opacity',0.5);
    });
            
    // Fade in/out the control buttons on mouse-over the plot container
    placeholder_bound.mouseenter(function(){
        $("#graph_buttons").stop().fadeIn();
        $("#stats").stop().fadeIn();
    }).mouseleave(function(){
        $("#graph_buttons").stop().fadeOut();
        $("#stats").stop().fadeOut();
    });
    
    $(window).resize(function(){
        var width = placeholder_bound.width();
        var height = width * 0.5;

        placeholder.width(width);
        placeholder_bound.height(height);
        placeholder.height(height-top_offset);

        if (embed) placeholder.height($(window).height()-top_offset);
        plot();
    });
    
});
</script>

