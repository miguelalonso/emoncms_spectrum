
var spectrum = {

    'list':function()
    {
        var result = {};
        $.ajax({ url: path+"spectrum/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    },

    'list_assoc':function()
    {
        var result = {};
        $.ajax({ url: path+"spectrum/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });

        var spectrums = {};
        for (z in result) spectrums[result[z].id] = result[z];

        return spectrums;
    },

    'set':function(id, fields)
    {
        var result = {};
        $.ajax({ url: path+"spectrum/set.json", data: "spectrumid="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){} });
        return result;
    },

    'remove':function(id)
    {
        $.ajax({ url: path+"spectrum/delete.json", data: "spectrumid="+id, async: false, success: function(data){} });
    },

    // Process

    'add_process':function(spectrumid,processid,arg)
    {
        var result = {};
        $.ajax({ url: path+"spectrum/process/add.json", data: "spectrumid="+spectrumid+"&processid="+processid+"&arg="+arg, async: false, success: function(data){result = data;} });
        return result;
    },

    'processlist':function(spectrumid)
    {
        var result = {};
        $.ajax({ url: path+"spectrum/process/list.json", data: "spectrumid="+spectrumid, async: false, dataType: 'json', success: function(data){result = data;} });
        var processlist = [];
        if (result!="")
        {
            var tmp = result.split(",");
            for (n in tmp)
            {
                var process = tmp[n].split(":"); 
                processlist.push(process);
            }
        }
        return processlist;
    },

    'getallprocesses':function(spectrumid)
    {
        var result = {};
        $.ajax({ url: path+"spectrum/getallprocesses.json", data: "spectrumid="+spectrumid, async: false, dataType: 'json', success: function(data){result = data;} });
        return result;
    },

    'delete_process':function(spectrumid,processid)
    {
        var result = {};
        $.ajax({ url: path+"spectrum/process/delete.json", data: "spectrumid="+spectrumid+"&processid="+processid, async: false, success: function(data){result = data;} });
        return result;
    },

    'move_process':function(spectrumid,processid,moveby)
    {
        var result = {};
        $.ajax({ url: path+"spectrum/process/move.json", data: "spectrumid="+spectrumid+"&processid="+processid+"&moveby="+moveby, async: false, success: function(data){result = data;} });
        return result;
    },

    'reset_processlist':function(spectrumid,processid,moveby)
    {
        var result = {};
        $.ajax({ url: path+"spectrum/process/reset.json", data: "spectrumid="+spectrumid, async: false, success: function(data){result = data;} });
        return result;
    }

}

