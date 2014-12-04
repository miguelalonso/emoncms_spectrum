<?php global $path, $session, $user; ?>

<h2><?php echo _('spectrum API'); ?></h2>
<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when your not logged in, add an apikey to the URL of your request: &apikey=APIKEY.'); ?></p>
<p><b><?php echo _('Read only:'); ?></b><br>
<spectrum type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>
<p><b><?php echo _('Read & Write:'); ?></b><br>
<spectrum type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _('Available HTML URLs'); ?></h3>
<table class="table">
    <tr><td><?php echo _('The spectrum list view'); ?></td><td><a href="<?php echo $path; ?>spectrum/view"><?php echo $path; ?>spectrum/node</a></td></tr>
    <tr><td><?php echo _('This page'); ?></td><td><a href="<?php echo $path; ?>spectrum/api"><?php echo $path; ?>spectrum/api</a></td></tr>
    <tr><td><?php echo _('spectrum processing configuration page'); ?></td><td><a href="<?php echo $path; ?>spectrum/process?spectrumid=1"><?php echo $path; ?>spectrum/process?spectrumid=1</a></td></tr>
</table>

<h3><?php echo _('Available JSON commands'); ?></h3>
<p><?php echo _('To use the json api the request url needs to include <b>.json</b>'); ?></p>

<p><b><?php echo _('Post data'); ?></b></p>
<table class="table">
    <tr><td><?php echo _('JSON format:'); ?></td><td><a href="<?php echo $path; ?>spectrum/post.json?json={power:200}"><?php echo $path; ?>spectrum/post.json?json={power:200}</a></td></tr>
    <tr><td><?php echo _('CSV format:'); ?></td><td><a href="<?php echo $path; ?>spectrum/post.json?csv=100,200,300"><?php echo $path; ?>spectrum/post.json?csv=100,200,300</a></td></tr>
    <tr><td><?php echo _('Assign spectrums to a node group'); ?></td><td><a href="<?php echo $path; ?>spectrum/post.json?node=1&csv=100,200,300"><?php echo $path; ?>spectrum/post.json?<b>node=1</b>&csv=100,200,300</a></td></tr>
    <tr><td><?php echo _('Set the spectrum entry time manually'); ?></td><td><a href="<?php echo $path; ?>spectrum/post.json?time=<?php echo time(); ?>&node=1&csv=100,200,300"><?php echo $path; ?>spectrum/post.json?<b>time=<?php echo time(); ?></b>&node=1&csv=100,200,300</a></td></tr>
</table>

<p><b><?php echo _('APIKEY'); ?></b><br>
<?php echo _('To post data from a remote device you will need to include in the request url your write apikey. This give your device write access to your emoncms account, allowing it to post data.'); ?></p>
<table class="table">
    <tr><td><?php echo _('For example using the first json type request above just add the apikey to the end like this:'); ?></td><td><a href="<?php echo $path; ?>spectrum/post.json?json={power:200}&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>"><?php echo $path; ?>spectrum/post.json?json={power:200}<b>&apikey=<?php echo $user->get_apikey_write($session['userid']); ?></b></a></td></tr>
</table>

<p><b><?php echo _('Bulk data'); ?></b>
<table class="table">
<tr><td><?php echo _('You can provide data using bulk mode'); ?></td><td><a href="<?php echo $path; ?>spectrum/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]"><?php echo $path; ?>spectrum/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]</a></td></tr>
</table>
<ul>
<li><?php echo _('The first number of each node is the time offset (see below).'); ?></li>
<li><?php echo _('The second number is the node id, this is the unique identifer for the wireless node.'); ?></li>
<li><?php echo _('All the numbers after the first two are data values. The second node here (node 17) has two data values: 1437 and 3164.'); ?></li>
<li><?php echo _('Optional offset and time parameters allow the sender to set the time reference for the packets. If none is specified, it is assumed that the last packet just arrived. The time for the other packets is then calculated accordingly.'); ?></li>
</ul>
<table class="table">
<tr><td><?php echo _('Legacy default format (4 is now, 2 is -2 seconds and 0 is -4 seconds to now):'); ?></td><td><a href="<?php echo $path; ?>spectrum/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]"><?php echo $path; ?>spectrum/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]</a></td></tr>
<tr><td><?php echo _('Time offset format (-6 is -16 seconds to now):'); ?></td><td><a href="<?php echo $path; ?>spectrum/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10"><?php echo $path; ?>spectrum/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]<b>&offset=-10</b></a></td></tr>
<tr><td><?php echo _('Sentat format: (useful for sending as positive increasing time index)'); ?></td><td><a href="<?php echo $path; ?>spectrum/bulk.json?data=[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]&sentat=543"><?php echo $path; ?>spectrum/bulk.json?data=[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]<b>&sentat=543</b></b></a></td></tr>
<tr><td><?php echo _('Absolute time format (-6 is 1387730121 seconds since 1970-01-01 00:00:00 UTC))'); ?></td><td><a href="<?php echo $path; ?>spectrum/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=<?php echo time(); ?>"><?php echo $path; ?>spectrum/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]<b>&time=<?php echo time(); ?></b></a></td></tr>
</table>

<br>
<p><b><?php echo _('spectrum actions'); ?></b>
<table class="table">
<tr><td><?php echo _('List of spectrums'); ?></td><td><a href="<?php echo $path; ?>spectrum/list.json"><?php echo $path; ?>spectrum/list.json</a></td></tr>
<tr><td><?php echo _('Delete an spectrum'); ?></td><td><a href="<?php echo $path; ?>spectrum/delete.json?id=1"><?php echo $path; ?>spectrum/delete.json?id=1</a></td></tr>
</table>

<p><b><?php echo _('spectrum process actions'); ?></b></p>
<table class="table">
    <tr><td><?php echo _('Add an spectrum process'); ?></td><td><a href="<?php echo $path; ?>spectrum/process/add.json?spectrumid=1&processid=1&arg=-1&newfeedspectrumname=power"><?php echo $path; ?>spectrum/process/add.json?spectrumid=1&processid=1&arg=-1&newfeedspectrumname=power</a></td></tr>
    <tr><td><?php echo _('List spectrum process list'); ?></td><td><a href="<?php echo $path; ?>spectrum/process/list.json?spectrumid=1"><?php echo $path; ?>spectrum/process/list.json?spectrumid=1</a></td></tr>
    <tr><td><?php echo _('Delete spectrum process'); ?></td><td><a href="<?php echo $path; ?>spectrum/process/delete.json?spectrumid=1&processid=1"><?php echo $path; ?>spectrum/process/delete.json?spectrumid=1&processid=1</a></td></tr>
    <tr><td><?php echo _('Move spectrum process'); ?></td><td><a href="<?php echo $path; ?>spectrum/process/move.json?spectrumid=1&processid=1&moveby=1"><?php echo $path; ?>spectrum/process/move.json?spectrumid=1&processid=1&moveby=1</a></td></tr>
    <tr><td><?php echo _('Reset spectrum process list'); ?></td><td><a href="<?php echo $path; ?>spectrum/process/reset.json?spectrumid=1"><?php echo $path; ?>spectrum/process/reset.json?spectrumid=1</a></td></tr>
</table>
