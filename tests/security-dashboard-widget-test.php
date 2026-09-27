<?php
$initialize=(string)file_get_contents(__DIR__.'/../security_center/initialize.php');
foreach(array('security-center-dashboard-widget','wbce-dashboard-grid','grid.insertBefore(widget,grid.firstChild)','border:1px solid var(--wbce-admin-border','grid-column:1/-1') as $needle){
    if(strpos($initialize,$needle)===false){fwrite(STDERR,"Security Center dashboard widget check failed: {$needle}\n");exit(1);}
}
echo "Security Center dashboard placement and frame checks passed.\n";
