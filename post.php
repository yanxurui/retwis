<?php
include("retwis.php");

if (!isLoggedIn() || !gt("body")) {
    header("Location:index.php");
    exit;
}

$r = redisLink();
$postid = $r->incr("next_post_id");
$body = str_replace("\n"," ",gt("body"));
$ref=-1;
if(gt("postid"))//repost
	$ref=gt("postid");
$r->hmset("post:$postid","user_id",$User['id'],"time",time(),"body",$body,"ref",$ref);
$followers = $r->zrange("followers:".$User['id'],0,-1);
$followers[] = $User['id']; /* Add the post to our own posts too */
$r->lpush("posts_self:".$User['id'],$postid);
foreach($followers as $fid) {
    $r->lpush("posts:$fid",$postid);
}
# Push the post on the timeline, and trim the timeline to the
# newest 1000 elements.
$r->lpush("timeline",$postid);
$r->ltrim("timeline",0,1000);

header("Location: index.php");
?>
