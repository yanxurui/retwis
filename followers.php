<?php
include('retwis.php');
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}
include("header.php");
echo("<h2>People who are following you</h2>");
$r = redisLink();
$followers = $r->zrange("followers:".$User['id'],0,-1);
showUsers($followers);
include("footer.php")
?>