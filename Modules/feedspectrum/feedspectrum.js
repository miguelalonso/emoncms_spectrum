
var feedspectrum = {

  apikey: "",
  
  'create':function(name, datatype, engine, options)
  {
    var result = {};
    $.ajax({ url: path+"feedspectrum/create.json", data: "name="+name+"&datatype="+datatype+"&engine="+engine+"&options="+JSON.stringify(options), dataType: 'json', async: false, success: function(data){result = data;} });
    return result;
  },
  
  'list':function()
  {
    var result = {};
    var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "?apikey="+feedspectrum.apikey;
    
    $.ajax({ url: path+"feedspectrum/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'list_assoc':function()
  {
    var result = {};
    var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "?apikey="+feedspectrum.apikey;
    
    $.ajax({ url: path+"feedspectrum/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {result = data;} });
    
    var feedspectrums = {};
    for (z in result) feedspectrums[result[z].id] = result[z];
    
    return feedspectrums;
  },
  
  'list_by_id':function()
  {
    var feedspectrums = {};
    var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "?apikey="+feedspectrum.apikey;
    
    $.ajax({ url: path+"feedspectrum/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {feedspectrums = data;} });
    
    var tmp = {};
    for (z in feedspectrums)
    {
      tmp[feedspectrums[z]['id']] = parseFloat(feedspectrums[z]['value']);
    }
    var feedspectrums = tmp;
    
    return feedspectrums;
  },
  
  'list_by_name':function()
  {
    var feedspectrums = {};
    var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "?apikey="+feedspectrum.apikey;
    
    $.ajax({ url: path+"feedspectrum/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {feedspectrums = data;} });
    
    var tmp = {};
    for (z in feedspectrums)
    {
      tmp[feedspectrums[z]['name']] = parseFloat(feedspectrums[z]['value']);
    }
    var feedspectrums = tmp;
    
    return feedspectrums;
  },

  'set':function(id, fields)
  {
    var result = {};
    $.ajax({ url: path+"feedspectrum/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){} });
    return result;
  },

  'remove':function(id)
  {
    $.ajax({ url: path+"feedspectrum/delete.json", data: "id="+id, async: false, success: function(data){} });
  },


  // if ($route->action == 'data') $result = $feedspectrum->get_data(get('id'),get('start'),get('end'),get('dp'));
  'get_data':function(feedspectrumid,start,end,dp)
  {
    var feedspectrumIn = [];
    var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "&apikey="+feedspectrum.apikey;
    $.ajax({                                      
      url: path+'feedspectrum/data.json',
      data: apikeystr+"&id="+feedspectrumid+"&start="+start+"&end="+end+"&dp="+dp,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedspectrumIn = data_in; }
    });
    return feedspectrumIn;
  },
  
  'get_average':function(feedspectrumid,start,end,interval)
  {
    var feedspectrumIn = [];
    var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "&apikey="+feedspectrum.apikey;
    $.ajax({                                      
      url: path+'feedspectrum/average.json',
      data: apikeystr+"&id="+feedspectrumid+"&start="+start+"&end="+end+"&interval="+interval,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedspectrumIn = data_in; }
    });
    return feedspectrumIn;
  },

  'get_kwhatpowers':function(feedspectrumid,points)
  {
    var feedspectrumIn = [];
    var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "&apikey="+feedspectrum.apikey;
    $.ajax({                                      
      url: path+'feedspectrum/kwhatpowers.json',
      data: apikeystr+"&id="+feedspectrumid+"&points="+JSON.stringify(points),
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedspectrumIn = data_in; }
    });
    return feedspectrumIn;
  },

  'histogram':function(feedspectrumid,start,end)
  {
    var feedspectrumIn = [];
    var apikeystr = ""; if (feedspectrum.apikey!="") apikeystr = "&apikey="+feedspectrum.apikey;
    $.ajax({                                      
      url: path+'feedspectrum/histogram.json',
      data: apikeystr+"&id="+feedspectrumid+"&start="+start+"&end="+end+"&res=1",
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedspectrumIn = data_in; }
    });
    return feedspectrumIn;
  }

}

