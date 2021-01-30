<head>
    <meta charset="utf-8">
    <!-- Title -->
    <title>$_TEMPLATE["title"]</title>
    <!-- Base -->
    <base href="$_TEMPLATE['PUBLIC_URL']">
    <!-- Favicons -->
    <!-- Meta -->
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Global Styles -->
    <included block="extendedStyles"></included>
    <!-- Scripts -->
    $_TEMPLATE['myAwesomeExampleScript']
</head>
<?php
/*
$_TEMPLATE is a super variable (can be passed in HTML code and PHP also in template) which contains local vars keys in render() method and global routing data.
included - is a element which can contains passed data while including in template (look yourAwesomeIndex.php), if nothing passed the element will removed automically
*/
?>
