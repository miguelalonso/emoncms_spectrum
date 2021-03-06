
var processlist_ui =
{
    variableprocesslist: [],
    variableid: 0,
    nodeid: 10,
    
    processlist: [],
    feedspectrumlist:[],
    spectrumlist:[],
    
    enable_mysql_all: false,

    'draw':function()
    {
        var i = 0;
        var out="";

        console.log(this.variableprocesslist);
        if (this.variableprocesslist.length==0) {
            out += "<tr class='alert'><td></td><td></td><td><b>You have no spectrum processes defined</b></td><td></td><td></td><td></td></tr>";
        } else {

            for (z in this.variableprocesslist)
            {
                
                out += '<tr>';

                // Move process up or down
                out += '<td>';
                if (i > 0) {
                    out += '<a class="move-process" href="#" title="Move up" processid='+i+' moveby=-1 ><i class="icon-arrow-up"></i></a>';
                }

                if (i < this.variableprocesslist.length-1) {
                    out += '<a class="move-process" href="#" title="Move up" processid='+i+' moveby=1 ><i class="icon-arrow-down"></i></a>';
                }
                out += '</td>';

                // Process name and argument
                var processid = parseInt(this.variableprocesslist[z][0]);
                var arg = "";
                var lastvalue = "";

                if (this.processlist[processid][1]==0) {
                    arg = this.variableprocesslist[z][1];
                }
                
                if (this.processlist[processid][1]==1) {
                    var inpid = this.variableprocesslist[z][1];
                    arg += "Node "+this.spectrumlist[inpid].nodeid+": ";
                    if (this.spectrumlist[inpid].description!="") arg += this.spectrumlist[inpid].description; else arg += this.spectrumlist[inpid].name;
                    lastvalue = "<span style='color:#888; font-size:12px'>(spectrumvalue:"+(this.spectrumlist[inpid].value*1).toFixed(2)+")</span>";
                }
                
                if (this.processlist[processid][1]==2) {
                    var feedspectrumid = this.variableprocesslist[z][1];
                    if (processlist_ui.feedspectrumlist[feedspectrumid]!=undefined) {
                        arg += "<a class='label label-info' href='"+path+"vis/auto?feedspectrumid="+feedspectrumid+"'>";
                        if (processlist_ui.feedspectrumlist[feedspectrumid].tag) arg += processlist_ui.feedspectrumlist[feedspectrumid].tag+": ";
                        arg += processlist_ui.feedspectrumlist[feedspectrumid].name;
                        arg += "</a>";
                        lastvalue = "<span style='color:#888; font-size:12px'>(feedspectrumvalue:"+(processlist_ui.feedspectrumlist[feedspectrumid].value*1).toFixed(2)+")</span>";
                    } else {
                      // delete feedspectrum
                    }
                }
                
                out += "<td>"+(i+1)+"</td><td>"+this.processlist[processid][0]+"</td><td>"+arg+"</td><td>"+lastvalue+"</td>";
                // Delete process button (icon)
                out += '<td><a href="#" class="delete-process" title="Delete" processid='+i+'><i class="icon-trash"></i></a></td>';

                out += '</tr>';
                
                i++; // process id
            }
        }
        $('#variableprocesslist').html(out);

    },


    'events':function()
    {


        $("#processlist-ui #feedspectrum-engine").change(function(){
            var engine = $(this).val();
            $("#feedspectrum-interval").hide();
            if (engine==6 || engine==5 || engine==4 || engine==1) $("#feedspectrum-interval").show();

        });

        $('#processlist-ui #process-add').click(function() 
        {
            var processid = $('#process-select').val();
            var process = processlist_ui.processlist[processid];
            var arg = '';



            // Type: value (scale, offset)
            if (process[1]==0) arg = $("#value-spectrum").val();
            
            // Type: spectrum (* / + - by spectrum)
            if (process[1]==1) arg = $("#spectrum-select").val();
            
            // Type: feedspectrum
            if (process[1]==2)
            {
                var feedspectrumid = $("#feedspectrum-select").val();
              
                if (feedspectrumid==-1)
                {
                    var feedspectrumname = $('#feedspectrum-name').val();
                    var feedspectrumtag = $('#feedspectrum-tag').val();
                    var engine = $('#feedspectrum-engine').val();
                    var datatype = process[4];
                    
                    var options = {};
                    if (datatype==2) { 
                        options = {interval:3600*24};
                    } else {
                        options = {interval:$('#feedspectrum-interval').val()};
                    }
                    
                    if (feedspectrumname == '') {
                        alert('ERROR: Please enter a feedspectrum name');
                        return false;
                    }
                    
                    var result = feedspectrum.create(feedspectrumname,datatype,engine,options);
                    feedspectrumid = result.feedspectrumid;
                    if (!result.success || feedspectrumid<1) {
                        alert('ERROR: feedspectrum could not be created, '+result.message);
                        return false;
                    }
                    
                    processlist_ui.feedspectrumlist[feedspectrumid] = {'id':feedspectrumid, 'name':feedspectrumname,'value':''};
                    
                    // feedspectrumlist
                    var out = "<option value=-1>CREATE NEW:</option>";
                    for (i in processlist_ui.feedspectrumlist) {
                      out += "<option value="+processlist_ui.feedspectrumlist[i].id+">"+processlist_ui.feedspectrumlist[i].name+"</option>";
                    }
                    $("#feedspectrum-select").html(out);
                    
                    $.ajax({ url: path+"feedspectrum/set.json", data: "id="+feedspectrumid+"&fields="+JSON.stringify({'tag':feedspectrumtag}), async: true, success: function(data){} });

                }
                arg = feedspectrumid;


            }

            processlist_ui.variableprocesslist.push([processid,""+arg]);
            processlist_ui.draw();
            spectrum.add_process(processlist_ui.spectrumid,processid,arg);

            update_main_list(processlist_ui.spectrumid, processlist_ui.variableprocesslist);
            

        });
        
        $('#processlist-ui #process-select').change(function() {
            var processid = $(this).val();
            
            $("#description").html("");
            $("#type-value").hide();
            $("#type-spectrum").hide();
            $("#type-feedspectrum").hide();
            
            if (processlist_ui.processlist[processid][1]==0) $("#type-value").show();
            if (processlist_ui.processlist[processid][1]==1) $("#type-spectrum").show();
            if (processlist_ui.processlist[processid][1]==2) 
            {
                $("#type-feedspectrum").show();
                processlist_ui.showfeedspectrumoptions(processid);
            }
            $("#description").html(process_info[processid]);
        });

        $('#processlist-ui #feedspectrum-select').change(function() {
            var feedspectrumid = $("#feedspectrum-select").val();
            
            if (feedspectrumid!=-1) {
                $("#feedspectrum-name").hide();
                $("#feedspectrum-interval").hide();
                $("#feedspectrum-engine").hide();
                $(".feedspectrum-engine-label").hide();
            } else {
                $("#feedspectrum-name").show();
                $("#feedspectrum-interval").show();
                $("#feedspectrum-engine").show();
                $(".feedspectrum-engine-label").show();
            }
        });

        $('#processlist-ui .table').on('click', '.delete-process', function() {
            processlist_ui.variableprocesslist.splice($(this).attr('processid'),1);
            
            var processid = $(this).attr('processid')*1;
            processlist_ui.draw();
            spectrum.delete_process(processlist_ui.spectrumid,processid+1);
            
            update_main_list(processlist_ui.spectrumid, processlist_ui.variableprocesslist);
        });

        $('#processlist-ui .table').on('click', '.move-process', function() {

            var processid = $(this).attr('processid')*1;
            console.log(processid);
            var curpos = parseInt(processid);
            var moveby = parseInt($(this).attr('moveby'));
            var newpos = curpos + moveby;
            if (newpos>=0 && newpos<processlist_ui.variableprocesslist.length)
            { 
                processlist_ui.variableprocesslist = processlist_ui.array_move(processlist_ui.variableprocesslist,curpos,newpos);
                processlist_ui.draw();
                spectrum.move_process(processlist_ui.spectrumid,processid+1,moveby);
            }

            update_main_list(processlist_ui.spectrumid, processlist_ui.variableprocesslist);
            
        });
        
        function update_main_list(spectrumid, processlist)
        {
            var process_str = "";
            for (z in processlist)
            {
                process_str += processlist[z].join(':') + ",";
            }
            process_str = process_str.slice(0, -1);
            
            // Update spectrum table immedietly
            for (z in table.data) {
                if (table.data[z].id == spectrumid) {
                    table.data[z].processList = process_str;
                }
            }
            table.draw();
        }

    },
    
    'showfeedspectrumoptions':function(processid)
    {
        var prc = processlist_ui.processlist[processid][2];
        var engines = processlist_ui.processlist[processid][6];   // 5:PHPFINA, 6:PHPFIWA
        var datatype = processlist_ui.processlist[processid][4]; // 1:REALTIME, 2:DAILY, 3:HISTOGRAM
        
        if (this.enable_mysql_all) {
            var mysql_found = false;
            for (e in engines) {
                if (engines[e]==0) mysql_found = true;
            }
            if (!mysql_found) engines.push(0);
        }

        if (prc!='histogram')
        {
            // Start by hiding all feedspectrum engine options
            $("#feedspectrum-engine option").hide();

            // Show only the feedspectrum engine options that are available
            for (e in engines) $("#feedspectrum-engine option[value="+engines[e]+"]").show();

            // Select the first feedspectrum engine in the engines array by default
            $("#feedspectrum-engine").val(engines[0]);

            // If there's only one feedspectrum engine to choose from then dont show feedspectrum engine selector
            if (engines.length==1) {
                $("#feedspectrum-engine, .feedspectrum-engine-label").hide();
            } else {
                $("#feedspectrum-engine, .feedspectrum-engine-label").show();
            }

            // If the datatype is daily then the interval is fixed to 3600s x 24h, no need to show interval selector
            if (datatype==2) {
                $("#feedspectrum-interval").hide();
            } else {
                $("#feedspectrum-interval").show();
                $("#feedspectrum-interval").val(10);
            } 
        }
        else
        {
            // Else feedspectrum engine is histogram
            $("#feedspectrum-engine, .feedspectrum-engine-label").hide();
            $("#feedspectrum-interval").hide();
            $("#feedspectrum-engine").val(engines[0]);
        }
    
    },

    // Process list functions
    'decode':function(str)
    {
        var processlist = [];
        if (str!="")
        {
            var tmp = str.split(",");
            for (n in tmp)
            {
                var process = tmp[n].split(":"); 
                processlist.push(process);
            }
        }
        return processlist;
    },

    'encode':function(array)
    {
        var parts = [];
        for (z in array) parts.push(array[z][0]+":"+array[z][1]);
        return parts.join(",");
    },


    'array_move':function(array,old_index, new_index) 
    {
        if (new_index >= array.length) {
            var k = new_index - array.length;
            while ((k--) + 1) {
                array.push(undefined);
            }
        }
        array.splice(new_index, 0, array.splice(old_index, 1)[0]);
        return array; // for testing purposes
    },
    
    'drawinline': function (processliststr) { 

      if (!processliststr) return "";
      
      var processPairs = processliststr.split(",");
      console.log(processPairs);
      var out = "";

      for (var z in processPairs)
      {
        var keyvalue = processPairs[z].split(":");

        var key = parseInt(keyvalue[0]);
        var type = "";
        var color = "";

        switch(key)
        {
          case 1:
            key = 'log'; type = 2; break;
          case 2:  
            key = 'x'; type = 0; break;
          case 3:  
            key = '+'; type = 0; break;
          case 4:    
            key = 'kwh'; type = 2; break;
          case 5:  
            key = 'kwhd'; type = 2; break;
          case 6:
            key = 'x inp'; type = 1; break;
          case 7:
            key = 'ontime'; type = 2; break;
          case 8:
            key = 'kwhinckwhd'; type = 2; break;
          case 9:
            key = 'kwhkwhd'; type = 2; break;
          case 10:  
            key = 'update'; type = 2; break;
          case 11: 
            key = '+ inp'; type = 1; break;
          case 12:
            key = '/ inp'; type = 1; break;
          case 13:
            key = 'phaseshift'; type =2; break;
          case 14:
            key = 'accumulate'; type = 2; break;
          case 15:
            key = 'rate'; type = 2; break;
          case 16:
            key = 'hist'; type = 2; break;
          case 17:  
            key = 'average'; type = 2; break;
          case 18:
            key = 'flux'; type = 2; break;
          case 19:
            key = 'pwrgain'; type = 2; break;
          case 20:
            key = 'pulsdiff'; type = 2; break;
          case 21:
            key = 'kwhpwr'; type = 2; break;
          case 22:
            key = '- inp'; type = 1; break;
          case 23:
            key = 'kwhkwhd'; type = 2; break;
          case 24:
            key = '> 0'; type = 3; break;
          case 25:
            key = '< 0'; type = 3; break;
          case 26:
            key = 'unsign'; type = 3; break;
          case 27:
            key = 'max'; type = 2; break;
          case 28:
            key = 'min'; type = 2; break;
        }  

        value = keyvalue[1];
        
        switch(type)
        {
          case 0:
            type = 'value: '; color = 'important';
            break;
          case 1:
            type = 'spectrum: '; color = 'warning';
            break;
          case 2:
            type = 'feedspectrum: '; color = 'info';
            break;
          case 3:
            type = ''; color = 'important';
            value = ''; // Argument type is NONE, we don't mind the value
            break;
        }

        if (type == 'feedspectrum: ') {
          out += "<a href='"+path+"vis/auto?feedspectrumid="+value+"'<span class='label label-"+color+"' title='"+type+value+"' style='cursor:pointer'>"+key+"</span></a> ";
        } else {
          out += "<span class='label label-"+color+"' title='"+type+value+"' style='cursor:default'>"+key+"</span> ";
        }
        
      }
      
      return out;
    }    
    
}
