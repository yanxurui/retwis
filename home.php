<?php
include("retwis.php");
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}
include("header.php");
$r = redisLink();
?>
<div id="postform">
<form method="POST" action="post.php">
<?php echo utf8entities($User['username'])?>, what you are doing?
<br>
<table>
<tr><td><textarea cols="70" rows="3" name="body"></textarea></td></tr>
<tr><td align="right"><input type="submit" name="doit" value="Update"></td></tr>
</table>
</form>
<div id="homeinfobox">
<a href="followers.php" style="text-decoration: none;"><?php echo $r->zcard("followers:".$User['id'])?> followers</a><br>
<a href="following.php" style="text-decoration: none;"><?php echo $r->zcard("following:".$User['id'])?> following</a><br>
</div>
</div>
<?php
$start = gt("start") === false ? 0 : intval(gt("start"));
showUserPostsWithPagination(false,$User['id'],$start,10);
include("footer.php")
?>
