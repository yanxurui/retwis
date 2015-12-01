<?php
include("retwis.php");

$r = redisLink();
if (!isLoggedIn() || !gt("uid") || gt("f") === false ||
    !($username = $r->hget("user:".gt("uid"),"username"))) {
    header("Location:index.php");
    exit;
}

$f = intval(gt("f"));
$uid = intval(gt("uid"));
if ($uid != $User['id']) {
    if ($f) {
        $r->zadd("followers:".$uid,time(),$User['id']);
        $r->zadd("following:".$User['id'],time(),$uid);
        //update my timeline
        $posts="posts:".$User['id'];
        $arr1=$r->lrange($posts,0,-1);
        $r->del($posts);
        $arr2=$r->lrange("posts_self:".$uid,0,-1);
        $arr3=OrderedListUnion($arr1,$arr2);
        foreach($arr3 as $cid)
            $r->rpush($posts,$cid);
    } else {
        $r->zrem("followers:".$uid,$User['id']);
        $r->zrem("following:".$User['id'],$uid);
        //update my timeline
        $posts="posts:".$User['id'];
        $arr1=$r->lrange($posts,0,-1);
        $r->del($posts);
        $arr2=$r->lrange("posts_self:".$uid,0,-1);
        $arr3=OrderedListDiff($arr1,$arr2);
        foreach($arr3 as $cid)
            $r->rpush($posts,$cid);
    }
}
header("Location: profile.php?u=".urlencode($username));
?>
