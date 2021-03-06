<?php
//IMathAS:  Delete a drill assessment
//(c) 2015 David Lippman

/*** master php includes *******/
require("../init.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Drill";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=". Sanitize::courseId($_GET['cid']) ."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Delete Drill";

if (!(isset($_GET['cid'])) || !(isset($_GET['block']))) { //if the cid is missing go back to the index page
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (!(isset($teacherid))) {  //there is a cid but the user isn't a teacher
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_REQUEST['remove'])) { // a valid delete request loaded the page
	$cid = Sanitize::courseId($_GET['cid']);
	$block = Sanitize::stripHtmlTags($_GET['block']);

	if ($_POST['remove']=="really") {
		$daid = $_GET['id'];
		
		//DB $query = "SELECT id FROM imas_items WHERE typeid='$daid' AND itemtype='Drill' AND courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemid = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='Drill' AND courseid=:courseid");
		$stm->execute(array(':typeid'=>$daid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$itemid = $stm->fetchColumn(0);
			$DBH->beginTransaction();
			//DB $query = "DELETE FROM imas_items WHERE id='$itemid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$itemid));

			//DB $query = "DELETE FROM imas_drillassess WHERE id='$daid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_drillassess WHERE id=:id");
			$stm->execute(array(':id'=>$daid));

			//DB $query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid='$daid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid");
			$stm->execute(array(':drillassessid'=>$daid));

			//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $items = unserialize(mysql_result($result,0,0));
			$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$cid));
			$items = unserialize($stm->fetchColumn(0));

			$blocktree = explode('-',$block);
			$sub =& $items;
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
			$key = array_search($itemid,$sub);
			if ($key!==false) {
				array_splice($sub,$key,1);
				//DB $itemorder = addslashes(serialize($items));
				$itemorder = serialize($items);
				//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
				$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
			}
		}
		$DBH->commit();
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']));

		exit;
	} else {
		//DB $query = "SELECT name FROM imas_drillassess WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemname = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_drillassess WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$itemname = $stm->fetchColumn(0);
	}
}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<h3><?php echo Sanitize::encodeStringForDisplay($itemname); ?></h3>
	Are you SURE you want to delete this Drill Assessment and all associated student work?
	<form method="POST" action="deletedrillassess.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>&block=<?php echo Sanitize::encodeUrlParam($block) ?>&id=<?php echo Sanitize::onlyInt($_GET['id']) ?>">
	<p>
	<button type=submit name="remove" value="really">Yes, Delete</button>		
	<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>'">
	</p>
	</form>

<?php
}
	require("../footer.php");
?>
