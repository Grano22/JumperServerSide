<!doctype HTML>
<html lang="$_TEMPLATE['lang']">
  <excluded replaceInto="body">Failed to load template..</excluded>
</html>
<?php
/*
$_ROUTE - will be available in future, parsed route data (now accessible in $_TEMPLATE["currRoute"])
$_ROUTER is a super variable which contains JumperRouter object to manipulate routing or extensible routing in subpath
$_TEMPLATE is a super variable (can be passed in HTML code and PHP also in template) which contains local vars keys in render() method and global routing data.
excluded - a special template elements, that allows extending templates
replaceInto attribute means replace <excluded> tag into body when excluding template fails
In excluded element you can specify alternative HTML code if replacing this element fails
*/
?>
