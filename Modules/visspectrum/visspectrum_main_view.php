<!--
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visspectrumualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
-->

<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

global $path;
?>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.selection.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/visspectrum/visualisations/multigraph.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/visspectrum/visualisations/visspectrum.helper.js"></script>

<h2><?php echo _("visualisations"); ?></h2>

<div id="visspectrumpage">
<div style="float:left">

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
    <div style="padding:10px;  border-top: 1px solid #fff">
        <div style="float:left; padding-top:2px; font-weight:bold;">1) <?php echo _("Select visspectrumualisation:")?> </div>
        <div style="float:right;">
            <span id="select"></span>
        </div>
        <div style="clear:both"></div>
    </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
    <div style="padding:10px;  border-top: 1px solid #fff">
        <div style="padding-top:2px; font-weight:bold;">2) <?php echo _("Set options:")?> </div><br>
        <div id="box-options" ></div><br>
        <p style="font-size:12px; color:#444;"><b><?php echo _("Note:");?></b> <?php echo _("If a feedspectrum does not appear in the selection box, check that the type has been set on the feedspectrums page."); ?></p>
    </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
    <div style="padding:10px;  border-top: 1px solid #fff">
        <div style="float:left; padding-top:2px; font-weight:bold;">3) </div>
        <div style="float:right;">
        <input id="viewbtn" type="submit" value="<?php echo _("View"); ?>" class="btn btn-info" />
        <input id="fullscreen" type="submit" value="<?php echo _("Full screen"); ?>" class="btn btn-info" />
        </div>
        <div style="clear:both"></div>
    </div>
</div>

<div style="width:320px; background-color:#efefef; margin-bottom:10px; border: 1px solid #ddd;">
    <div style="padding:10px;  border-top: 1px solid #fff">
        <div style="padding-top:2px; font-weight:bold;"><?php echo _("Embed in your website:"); ?> </div><br>
        <textarea id="embedcode" style="width:290px; height:120px;" readonly="readonly"></textarea>
    </div>
</div>

</div>

<div id="visspectrum_bound" style="width:600px; height:420px; float:right">
        <div id="visspectrumiframe"><div style="height:400px; border: 1px solid #ddd; " ></div></div>
</div>

<div id="visspectrumurl"></div>

</div>

<script type="application/javascript">
    var path = "<?php echo $path; ?>";
    var feedspectrumlist = <?php echo json_encode($feedspectrumlist); ?>;
    var widgets = <?php echo json_encode($visualisations); ?>;


      //alert(feedspectrumlist[0]['datatype']);


      var embed = 0;

      //var apikey = "<?php echo $apikey; ?>";
    var apikey = "";

    var out = '<select id="visspectrumselect" style="width:120px; margin:0px;">';
    for (z in widgets)
    {
        // If widget action specified: use action otherwise override with widget key
        var action = z;
        if (widgets[z]['action']!=undefined) action = widgets[z]['action'];
        out += "<option value='"+action+"' >"+z+"</option>";
    }
    out += '</select>';
    $("#select").html(out);

    draw_options(widgets['rawdata']['options']);

    // 1) ON CLICK OF visspectrumUALISATION OPTION:

    $("#visspectrumselect").change(function() {
        //alert("hola");
            $("#viewbtn").show();
            // Normal visspectrumualisation items
            draw_options(widgets[$(this).val()]['options'], widgets[$(this).val()]['optionstype']);

    });

    $("#viewbtn").click(function(){
        var visspectrumurl = "";
        var visspectrumtype = $("#visspectrumselect").val();
        visspectrumurl += path+"visspectrum/"+visspectrumtype;

        // Here we go through all the options that are set and get their values creating a url string that gets the
        // visspectrumualisation. We also check for each feedspectrum if the feedspectrum is a public feedspectrum or not.
        // If the feedspectrum is not public then we include the read apikey in the embed code box.

        var publicfeedspectrum = 1;
        var options = [];
        $(".options").each(function() {
            if ($(this).val()) {
                options.push($(this).attr("id")+"="+$(this).val());
                if ($(this).attr("otype")=='feedspectrum') publicfeedspectrum = $('option:selected', this).attr('public');
            }
        });
        
        visspectrumurl += "?"+options.join("&");
        var width = $("#visspectrum_bound").width();
        var height = width * 0.58;
        $("#visspectrumiframe").html('<iframe style="width:'+width+'px; height:'+height+'px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visspectrumurl+'&embed=1"></iframe>');

        if (publicfeedspectrum == 1) $("#embedcode").val('<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visspectrumurl+'&embed=1"></iframe>'); else $("#embedcode").val('<?php echo addslashes(_("Some of the feedspectrums selected are not public, to embed a visspectrumualisation publicly first make the feedspectrums that you want to use public."));?>\n\n<?php echo _("To embed privately:");?>\n\n<iframe style="width:580px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'+visspectrumurl+'&embed=1&apikey='+apikey+'"></iframe>');

    });

    $("#fullscreen").click(function(){
        var visspectrumurl = "";
        var visspectrumtype = $("#visspectrumselect").val();


            visspectrumurl += path+"visspectrum/"+visspectrumtype;
            var options = [];
            $(".options").each(function() {
                if ($(this).val())
                {
                    options.push($(this).attr("id")+"="+$(this).val());
                }
            });

        if (options) visspectrumurl += "?"+options.join("&");
        $(window.location).attr('href',visspectrumurl);
    });

    function draw_options(box_options)
    {
        // Build options table html
        var options_html = "<table>";
        for (z in box_options)
        {
            options_html += "<tr><td style='width:100px'><b>"+box_options[z][0]+":</b></td>";

            var type = box_options[z][1];

            if (type == 0 || type == 1 || type == 2 || type == 3)
            {
                options_html += "<td>"+select_feedspectrum(box_options[z][0], feedspectrumlist, type)+"</td>";
            }
            else
            {
                options_html += "<td><input style='width:120px' class='options' id='"+box_options[z][0]+"' type='text' value='"+box_options[z][2]+"' / ></td>";
            }
            options_html += "</tr>";
        }
        options_html += "</table>";
        $("#box-options").html(options_html);
    }

    // Create a drop down select box with a list of feedspectrums.
    function select_feedspectrum(id, feedspectrumlist, type)
    {
        var out = "<select style='width:120px' id='"+id+"' class='options' otype='feedspectrum'>";
        for (i in feedspectrumlist)
        {
            
            if (feedspectrumlist[i]['datatype']==type) out += "<option value='"+feedspectrumlist[i]['id']+"' public='"+feedspectrumlist[i]['public']+"'>"+feedspectrumlist[i]['id']+": "+feedspectrumlist[i]['name']+"</option>";
            if (type==0 && feedspectrumlist[i]['datatype']==1) out += "<option value='"+feedspectrumlist[i]['id']+"' public='"+feedspectrumlist[i]['public']+"'>"+feedspectrumlist[i]['id']+": "+feedspectrumlist[i]['name']+"</option>";
            if (type==0 && feedspectrumlist[i]['datatype']==2) out += "<option value='"+feedspectrumlist[i]['id']+"' public='"+feedspectrumlist[i]['public']+"'>"+feedspectrumlist[i]['id']+": "+feedspectrumlist[i]['name']+"</option>";
        }
        out += "</select>";
        return out;
    }

    visspectrum_resize();
    $(window).resize(function(){visspectrum_resize();});

    function visspectrum_resize()
    {
        var visspectrumwidth = $("#visspectrumpage").width() - 340;
        var visspectrumheight = visspectrumwidth * (3/4);

        $("#visspectrum_bound").width(visspectrumwidth);
        $("#visspectrum_bound").height(visspectrumheight);
        $("#visspectrumiframe").width(visspectrumwidth);
        $("#visspectrumiframe").height(visspectrumheight);
    }

</script>
