<?php
 $endTime = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());
 $time = round($endTime - $startTime, 4);
 print "Total execution time: $time<P>";
?>
  <img style='float:left;' src='images/php-power-micro2.png'>
&nbsp; <a href=http://prb.sourceforge.net/>p|r|b</a> (c) 2006 - 2007 Guillaume Fontaine. 
<span class=bugs>Follow this <a href=http://sourceforge.net/tracker/?func=add&group_id=176562&atid=877745>link to submit bugs</a> and help us improve the project. </span>
