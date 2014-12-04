<?php
    global $path;
?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feedspectrum/feedspectrum.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>

<style>
input[type="text"] {
         width: 88%;
}

.icon-circle-arrow-down {
cursor:pointer
}
</style>

<br>

<div id="apihelphead"><div style="float:right;"><a href="api"><?php echo _('feedspectrum API Help'); ?></a></div></div>

<div class="container">
        <div id="localheading"><h2><?php echo _('feedspectrums'); ?></h2></div>

        <div id="table"></div>

        <div id="nofeedspectrums" class="alert alert-block hide">
                <h4 class="alert-heading"><?php echo _('No feedspectrums created'); ?></h4>
                <p><?php echo _('feedspectrums are where your monitoring data is stored. The recommended route for creating feedspectrums is to start by creating spectrums (see the spectrums tab). Once you have spectrums you can either log them straight to feedspectrums or if you want you can add various levels of spectrum processing to your spectrums to create things like daily average data or to calibrate spectrums before storage. You may want to follow the link as a guide for generating your request.'); ?><a href="api"><?php echo _('feedspectrum API helper'); ?></a></p>
        </div>

        <hr>
        <button id="refreshfeedspectrumsize" class="btn btn-small" >Refresh feedspectrum size <i class="icon-refresh" ></i></button>

        <hr>
        <button id="checkfeedspectrumsize" class="btn btn-small" >Check feedspectrum size <i class="icon-refresh" ></i></button>
        <!--<FORM>Max. Size <input type=text name=maxsize size=4 value=  1000000 > <input type=submit value=Enter> </form> <br/><br/>-->

</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo _('WARNING deleting a feedspectrum is permanent'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Are you sure you want to delete this feedspectrum?'); ?></p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
    </div>
</div>

<div id="ExportModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="ExportModalLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="ExportModalLabel">CSV export: </h3>
    </div>
    <div class="modal-body">
    <p>Selected feedspectrum: <b><span id="SelectedExportfeedspectrum"></span></b></p>
    <p>Select the time range and interval that you wish to export: </p>
    
        <table class="table">
        <tr>
            <td>
                <p><b>Start date & time</b></p>
                <div id="datetimepicker1" class="input-append date">
                    <input id="export-start" data-format="dd/MM/yyyy hh:mm:ss" type="text" />
                    <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
                </div>
            </td>
            <td>
                <p><b>End date & time</b></p>
                <div id="datetimepicker2" class="input-append date">
                    <input id="export-end" data-format="dd/MM/yyyy hh:mm:ss" type="text" />
                    <span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <p><b>Interval</b></p>
                <select id="export-interval" >
                    <option value="">Select interval</option>
                    <option value=5>5s</option>
                    <option value=10>10s</option>
                    <option value=30>30s</option>
                    <option value=60>1 mi</option>
                    <option value=300>5 mins</option>
                    <option value=600>10 mins</option>
                    <option value=1800>30 mins</option>
                    <option value=3600>1 hour</option>
                    <option value=21600>6 hour</option>
                    <option value=43200>12 hour</option>
                    <option value=86400>Daily</option>
                    <option value=604800>Weekly</option>
                    <option value=2678400>Monthly</option>
                    <option value=31536000>Annual</option>
                </select>
            </td>
            <td>
                <p><b>Timezone (for day export):</b></p>
                <input id="export-timezone" type="text" />
            </td>
        </tr>
        <tr>
            <td><br><button class="btn" id="export">Export</button></td><td><br>Estimated download size: <span id="downloadsize">0</span>kB</td>
        </tr>
        </table>
        <p>feedspectrum intervals: if the selected interval is shorter than the feedspectrum interval the feedspectrum interval will be used instead</p>
        <p>Averages are only returned for feedspectrum engines with built in averaging.</p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
    </div>
</div>

<script>

    var path = "<?php echo $path; ?>";

    // Extemd table library field types
    for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];

    table.element = "#table";

    table.fields = {
        'id':{'title':"<?php echo _('Id'); ?>", 'type':"fixed"},
        'name':{'title':"<?php echo _('Name'); ?>", 'type':"text"},
        'tag':{'title':"<?php echo _('Tag'); ?>", 'type':"text"},
        'datatype':{'title':"<?php echo _('Datatype'); ?>", 'type':"select", 'options':['','REALTIME','DAILY','HISTOGRAM']},
        'engine':{'title':"<?php echo _('Engine'); ?>", 'type':"fixedselect", 'options':['MYSQL','TIMESTORE','PHPTIMESERIES','GRAPHITE','PHPTIMESTORE','PHPFINA','PHPFIWA']},
        'public':{'title':"<?php echo _('Public'); ?>", 'type':"icon", 'trueicon':"icon-globe", 'falseicon':"icon-lock"},
        'size':{'title':"<?php echo _('Size'); ?>", 'type':"fixed"},

        'time':{'title':"<?php echo _('Updated'); ?>", 'type':"updated"},
        'value':{'title':"<?php echo _('Value'); ?>",'type':"value"},

        // Actions
        'edit-action':{'title':'', 'type':"edit"},
        'delete-action':{'title':'', 'type':"delete"},
        'view-action':{'title':'', 'type':"iconlink", 'link':path+"visspectrum/auto?feedspectrumid="},
        'icon-basic':{'title':'', 'type':"iconbasic", 'icon':'icon-circle-arrow-down'}

    }

    table.groupby = 'tag';
    table.deletedata = false;

    table.draw();

    update();

    function update()
    {   
        var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "?apikey="+feedspectrum.apikey;
        
        $.ajax({ url: path+"feedspectrum/list.json"+apikeystr, dataType: 'json', async: true, success: function(data) {
        
            table.data = data;
        
            for (z in table.data)
            {
                if (table.data[z].size<1024*100) {
                    table.data[z].size = (table.data[z].size/1024).toFixed(1)+"kb";
                } else if (table.data[z].size<1024*1024) {
                    table.data[z].size = Math.round(table.data[z].size/1024)+"kb";
                } else if (table.data[z].size>=1024*1024) {
                    table.data[z].size = Math.round(table.data[z].size/(1024*1024))+"Mb";
                }
            }
            table.draw();
            if (table.data.length != 0) {
                $("#nofeedspectrums").hide();
                $("#apihelphead").show();
                $("#localheading").show();
            } else {
                $("#nofeedspectrums").show();
                $("#localheading").hide();
                $("#apihelphead").hide();
            }
        } });
    }

    var updater = setInterval(update, 5000);

    $("#table").bind("onEdit", function(e){
        clearInterval(updater);
    });

    $("#table").bind("onSave", function(e,id,fields_to_update){
        feedspectrum.set(id,fields_to_update);
        updater = setInterval(update, 5000);
    });

    $("#table").bind("onDelete", function(e,id,row){
        clearInterval(updater);
        $('#myModal').modal('show');
        $('#myModal').attr('feedspectrumid',id);
        $('#myModal').attr('feedspectrumrow',row);
    });

    $("#confirmdelete").click(function()
    {
        var id = $('#myModal').attr('feedspectrumid');
        var row = $('#myModal').attr('feedspectrumrow');
        feedspectrum.remove(id);
        table.remove(row);
        update();

        $('#myModal').modal('hide');
        updater = setInterval(update, 5000);
    });

    $("#refreshfeedspectrumsize").click(function(){
        $.ajax({ url: path+"feedspectrum/updatesize.json", success: function(data){update();} });
    });
    $("#checkfeedspectrumsize").click(function(){
        $.ajax({ url: path+"feedspectrum/checksize.json", success: function(data){update();} });
    });
    
    // feedspectrum Export feature
    
    $("#table").on("click",".icon-circle-arrow-down", function(){
        var row = $(this).attr('row');
        $("#SelectedExportfeedspectrum").html(table.data[row].tag+": "+table.data[row].name);
        $("#export").attr('feedspectrumid',table.data[row].id);
        
        if ($("#export-timezone").val()=="") {
            var u = user.get();
            if (u.timezone==null) u.timezone = 0;
            $("#export-timezone").val(parseInt(u.timezone));
        }
        
        $('#ExportModal').modal('show');
    });

    $('#datetimepicker1').datetimepicker({
        language: 'en-EN'
    });
    
    $('#datetimepicker2').datetimepicker({
        language: 'en-EN'
    });

    $('#export-interval').on('change', function(e) 
    {
        var export_start = parse_timepicker_time($("#export-start").val());
        var export_end = parse_timepicker_time($("#export-end").val());
        var export_interval = $("#export-interval").val();
        var downloadsize = ((export_end - export_start) / export_interval) * 17; // 17 bytes per dp
        console.log(downloadsize);
        $("#downloadsize").html((downloadsize/1024).toFixed(0));
    });
        
    $('#datetimepicker1, #datetimepicker2').on('changeDate', function(e) 
    {
        var export_start = parse_timepicker_time($("#export-start").val());
        var export_end = parse_timepicker_time($("#export-end").val());
        var export_interval = $("#export-interval").val();
        var downloadsize = ((export_end - export_start) / export_interval) * 17; // 17 bytes per dp
        $("#downloadsize").html((downloadsize/1024).toFixed(0));
    });
    
    $("#export").click(function()
    {
        var feedspectrumid = $(this).attr('feedspectrumid');
        var export_start = parse_timepicker_time($("#export-start").val());
        var export_end = parse_timepicker_time($("#export-end").val());
        var export_interval = $("#export-interval").val();
        var export_timezone = parseInt($("#export-timezone").val());
        
        if (!export_start) {alert("Please enter a valid start date"); return false; }
        if (!export_end) {alert("Please enter a valid end date"); return false; }
        if (export_start>=export_end) {alert("Start date must be further back in time than end date"); return false; }
        if (export_interval=="") {alert("Please select interval to download"); return false; }
        var downloadsize = ((export_end - export_start) / export_interval) * 17; // 17 bytes per dp
        
        if (downloadsize>(10*1048576)) {alert("Download file size to large (download limit: 10Mb)"); return false; }
        
        window.open(path+"feedspectrum/csvexport.json?id="+feedspectrumid+"&start="+(export_start+(export_timezone*3600))+"&end="+(export_end+(export_timezone*3600))+"&interval="+export_interval);
    });
    
    function parse_timepicker_time(timestr)
    {
        var tmp = timestr.split(" ");
        if (tmp.length!=2) return false;
        
        var date = tmp[0].split("/");
        if (date.length!=3) return false;
        
        var time = tmp[1].split(":");
        if (time.length!=3) return false;
        
        return new Date(date[2],date[1]-1,date[0],time[0],time[1],time[2],0).getTime() / 1000;
    }

</script>
