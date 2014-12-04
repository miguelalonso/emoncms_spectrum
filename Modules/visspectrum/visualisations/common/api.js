  
  //-------------------------------------------------------------------------------
  // Get feedspectrum list
  //-------------------------------------------------------------------------------
  function get_feedspectrum_list(apikey)
  {
    var list = [];
    $.ajax({                                      
      type: "GET",
      url: path+"feedspectrum/list.json?apikey="+apikey,
      dataType: 'json',
      async: false,
      success: function(data) { list = data; }
    });
    return list;
  }

  //-------------------------------------------------------------------------------
  // Get feedspectrum data
  //-------------------------------------------------------------------------------
  function get_feedspectrum_data(feedspectrumID,start,end,dp)
  {
    var feedspectrumIn = [];
    var query = "&id="+feedspectrumID+"&start="+start+"&end="+end+"&dp="+dp;
    if (apikey!="") query+= "&apikey="+apikey;

    $.ajax({                                    
      url: path+'feedspectrum/data.json',
      data: query,  
      dataType: 'json',                           
      async: false,
      success: function(datain) { feedspectrumIn = datain; }
    });
    return feedspectrumIn;
  }
  
  //-------------------------------------------------------------------------------
  // Get feedspectrum data async with callback
  //-------------------------------------------------------------------------------
  function get_feedspectrum_data_async(feedspectrumID,start,end,dp,pfn)
  {
    var feedspectrumIn = [];
    var query = "&id="+feedspectrumID+"&start="+start+"&end="+end+"&dp="+dp;
    if (apikey!="") query+= "&apikey="+apikey;

    $.ajax({                                    
      url: path+'feedspectrum/data.json',
      data: query,  
      dataType: 'json',                           
      success: function(datain) { 
        if ( typeof pfn === "function" ) {
            pfn(datain);
        }
      }
    });
  }

  //-------------------------------------------------------------------------------
  // Get histogram data
  //-------------------------------------------------------------------------------
  function get_histogram_data(feedspectrumID,start,end)
  {
    var feedspectrumIn = [];
    $.ajax({                                    
      url: path+'feedspectrum/histogram.json',
      data: "&apikey="+apikey+"&id="+feedspectrumID+"&start="+start+"&end="+end,
      dataType: 'json',                           
      async: false,
      success: function(datain) { feedspectrumIn = datain; }
    });
    return feedspectrumIn;
  }

  //-------------------------------------------------------------------------------
  // Get kwh per day at power range
  //-------------------------------------------------------------------------------
  function get_kwhatpower(feedspectrumid,rmin,rmax)
  {
    var feedspectrumIn = [];
    $.ajax({                                      
      url: path+'feedspectrum/kwhatpower.json',
      data: "&apikey="+apikey+"&id="+feedspectrumid+"&min="+rmin+"&max="+rmax,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedspectrumIn = data_in; }
    });
    return feedspectrumIn;
  }

  //-------------------------------------------------------------------------------
  // Get feedspectrum data
  //-------------------------------------------------------------------------------
  function get_multigraph(apikey)
  {
    var query = path+"visspectrum/multigraphget.json";
    if (apikey!="") query+= "?apikey="+apikey;
    console.log(query);
    var feedspectrumlist = [];
    $.ajax({                                      
      type: "GET",
      url: query, //+apikey_write,     
      async: false,    
      dataType: 'json',     
      success: function(data){feedspectrumlist = data;}
    });
    return feedspectrumlist;
  }

  //-------------------------------------------------------------------------------
  // Get feedspectrum data
  //-------------------------------------------------------------------------------
  function save_multigraph(write_apikey,feedspectrumlist)
  {
    var feedspectrumlist_save = eval(JSON.stringify(feedspectrumlist));
    for(var i in feedspectrumlist_save) { feedspectrumlist_save[i].plot.data = null; }

    $.ajax({                                      
      type: "POST",
      url: path+"visspectrum/multigraphsave.json?apikey="+write_apikey,
      data: "&data="+JSON.stringify(feedspectrumlist_save),
      success: function(msg) {console.log(msg);}
    });
  }
