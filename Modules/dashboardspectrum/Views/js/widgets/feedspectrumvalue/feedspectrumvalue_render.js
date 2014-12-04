/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */

function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{
  widget["options"    ].push(optionKey);
  widget["optionstype"].push(optionType);
  widget["optionsname"].push(optionName);
  widget["optionshint"].push(optionHint);
  widget["optionsdata"].push(optionData);
}

function feedspectrumvalue_widgetlist()
{
  var widgets =
  {
    "feedspectrumvalue":
    {
      "offsetx":-40,"offsety":-30,"width":80,"height":60,
      "menu":"Widgets",
      "options":    [],
      "optionstype":[],
      "optionsname":[],
      "optionshint":[],
      "optionsdata":[]
    }
  };

  var decimalsDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
		[-1,   "Automatic"],
		[0,    "0"],
		[1,    "1"],
		[2,    "2"],
		[3,    "3"],
		[4,    "4"],
		[5,    "5"],
		[6,    "6"]
	];

  addOption(widgets["feedspectrumvalue"], "feedspectrumname",   "feedspectrum",    _Tr("feedspectrum"),     _Tr("feedspectrum value"),		[]);
  addOption(widgets["feedspectrumvalue"], "units",      "value",   _Tr("Units"),    _Tr("Units to show"),	[]);
  addOption(widgets["feedspectrumvalue"], "decimals",   "dropbox", _Tr("Decimals"), _Tr("Decimals to show"),	decimalsDropBoxOptions);

  return widgets;
}

function feedspectrumvalue_init()
{
  feedspectrumvalue_draw();
}

function feedspectrumvalue_draw()
{
  $('.feedspectrumvalue').each(function(index)
  {
    var feedspectrum = $(this).attr("feedspectrumname");
    if (feedspectrum==undefined) feedspectrum = $(this).attr("feedspectrum");

    var units = $(this).attr("units");
    var val = assoc[feedspectrum];
    var decimals = $(this).attr("decimals");

    if (feedspectrum==undefined) val = 0;
    if (units==undefined) units = '';
    if (val==undefined) val = 0;
    if (decimals==undefined) decimals = -1;
    
    if (isNaN(val))  val = 0;
    
    if (decimals<0)
    {

      if (val>=100)
          val = val.toFixed(0);
      else if (val>=10)
          val = val.toFixed(1);
      else if (val<=-100)
          val = val.toFixed(0);
      else if (val<=-10)
          val = val.toFixed(1);
      else
          val = val.toFixed(2);
    }
    else 
    {
      val = val.toFixed(decimals);
    }

    $(this).html(val+units);
  });
}

function feedspectrumvalue_slowupdate()
{
  feedspectrumvalue_draw();
}

function feedspectrumvalue_fastupdate()
{

}


