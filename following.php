<?php
include('retwis.php');
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}
include("header.php");
echo("<h2>People who you are following</h2>");
$r = redisLink();
$following = $r->zrange("following:".$User['id'],0,-1);
showUsers($following);
include("footer.php")
?>