<?php

  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */

  global $user, $path, $session;

?>

<h2><?php echo _("feedspectrum API");?></h2>

<h3><?php echo _("Apikey authentication");?></h3>
<p><?php echo _("If you want to call any of the following action's when your not logged in, add an apikey to the URL of your request: &apikey=APIKEY.");?></p>
<p><b><?php echo _("Read only:");?></b><br>
<spectrum type="text" style="width:230px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>

<p><b><?php echo _("Read & Write:");?></b><br>
<spectrum type="text" style="width:230px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _("Html");?></h3>
<p><a href="<?php echo $path; ?>feedspectrum/list"><?php echo $path; ?>feedspectrum/list</a> - <?php echo _("The feedspectrum list view");?></p>
<p><a href="<?php echo $path; ?>feedspectrum/api"><?php echo $path; ?>feedspectrum/api</a> - <?php echo _("This page");?></p>

<h3><?php echo _("JSON");?></h3>
<p><?php echo _("To use the json api the request url needs to include .json");?></p>
<p><a href="<?php echo $path; ?>feedspectrum/list.json?userid=0"><?php echo $path; ?>feedspectrum/list.json?userid=0</a> - <?php echo _("returns a list of public feedspectrums made public by the given user.");?></p>
<p><a href="<?php echo $path; ?>feedspectrum/value.json?id=0"><?php echo $path; ?>feedspectrum/value.json?id=0</a> - <?php echo _("returns the present value of a given feedspectrum");?></p>
<p><a href="<?php echo $path; ?>feedspectrum/get.json?id=0&field="><?php echo $path; ?>feedspectrum/get.json?id=0&field=</a> - <?php echo _("returns the present value of a given feedspectrum");?></p>
<p><a href="<?php echo $path; ?>feedspectrum/data.json?id=0&start=&end=&dp="><?php echo $path; ?>feedspectrum/data.json?id=0&start=&end=&dp=</a> - <?php echo _("returns feedspectrum data");?></p>
<p><a href="<?php echo $path; ?>feedspectrum/histogram.json?id=0&start=&end="><?php echo $path; ?>feedspectrum/histogram.json?id=0&start=&end=</a> - <?php echo _("returns histogram data");?></p>
<p><a href="<?php echo $path; ?>feedspectrum/kwhatpower.json?id=0&min=&max="><?php echo $path; ?>feedspectrum/kwhatpower.json?id=0&min=&max=</a> - <?php echo _("returns kwh consumed in a given power band using histogram data type");?></p>

<p><a href="<?php echo $path; ?>feedspectrum/getid.json?name="><?php echo $path; ?>feedspectrum/getid.json?name=</a> - <?php echo _("returns id of a feedspectrum given by name");?></p>
<p><a href="<?php echo $path; ?>feedspectrum/list.json"><?php echo $path; ?>feedspectrum/list.json</a></p>

<p><a href='<?php echo $path; ?>feedspectrum/create.json?name=Power&datatype=1&engine=5&options={"interval":10}'><?php echo $path; ?>feedspectrum/create.json?name=Power&datatype=1&engine=5&options={"interval":10}</a></p>

<p><a href="<?php echo $path; ?>feedspectrum/set.json?id=0&fields={'name':'anewname'}"><?php echo $path; ?>feedspectrum/set.json?id=0&fields={'name':'anewname'}</a></p>
<p><a href="<?php echo $path; ?>feedspectrum/insert.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feedspectrum/insert.json?id=0&time=UNIXTIME&value=100.0</a></p>
<p><a href="<?php echo $path; ?>feedspectrum/update.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feedspectrum/update.json?id=0&time=UNIXTIME&value=100.0</a></p>
<p><a href="<?php echo $path; ?>feedspectrum/deletedatapoint.json?id=0&feedspectrumtime=UNIXTIME"><?php echo $path; ?>feedspectrum/deletedatapoint.json?id=0&feedspectrumtime=UNIXTIME</a></p>
<p><a href="<?php echo $path; ?>feedspectrum/delete.json?id=0"><?php echo $path; ?>feedspectrum/delete.json?id=0</a></p>

