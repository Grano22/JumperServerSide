<exclude template="main_template.php">
    <include template="head_base.php">
      <block name="extendedStyles">
        <link rel="stylesheet" type="text/css" href="/app/assets/css/myAwesomeReactAppStyle.css">
      </block>
    </include>
<body>
    <div id="root">
        <div id="preloade"><!-- Yes, you can specify here your awesome preloader for example during preparing SPA app by JS --></div>
        <noscript>This app requires Javascript to run react app</noscript>
    </div>
    $_TEMPLATE["reactAppScript"]
</body>
</exclude>
<?php
/*
Is a example of app which can be SPA with awesome server-side rendering features
exclude - means extension of given template in attribute template (path), content inner will be replaced with excluded in yourAwesomeBase.php
include - include template with given blocks, blocks content will be replaced with included elements with corresponding names, if not named block unnamed will be replaced with unnamed included tags
*/
?>
