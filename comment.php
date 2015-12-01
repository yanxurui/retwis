<?php
include("retwis.php");

if (!isLoggedIn() || !gt("body")) {
    header("Location:index.php");
    exit;
}

$r = redisLink();
$cid = $r->incr("next_comment_id");
$body = str_replace("\n"," ",gt("body"));
$postid=gt("postid");
$r->hmset("comment:$cid","user_id",$User['id'],"time",time(),"body",$body);
$r->lpush("comments:".$postid,$cid);
header("Location:".$_SERVER['HTTP_REFERER']);
?>