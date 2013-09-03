<?
require_once('php.classes/class.Db.php');
require_once('php.classes/class.Tree.php');

switch ($_REQUEST["operation"]) {
	case 'reload': reloadTree(); 
		break;
	case 'getChildren': getChildren((int)$_REQUEST["item"]); 
		break;
	case 'addChild': addChild((int)$_REQUEST["item"],$_REQUEST["name"]); 
		break;
	case 'delChild': delChild((int)$_REQUEST["item"]); 
		break;
	case 'editChild': editNode((int)$_REQUEST["item"],$_REQUEST["name"]); 
		break;
	case 'addRoot': addRoot($_REQUEST["name"]); 
		break;
		
}
	
	
function reloadTree () {
	$tree = new Tree();
	$tmp = $tree->_get_children(1);
	$result = array();
	foreach($tmp as $k => $v) {
		$result[] = array(
			"id" => (int) $v['id'],
			"title" => $v['name'],
			"state" => ((int)$v["right"] - (int)$v["left"] > 1) ? "closed" : "opened"
		);
	}
	echo json_encode($result);
}

function getChildren ($curItem) {
	$tree = new Tree();
	$tmp = $tree->_get_children((int)$curItem);
	$result = array();
	foreach($tmp as $k => $v) {
		$result[] = array(
			"id" => (int) $v['id'],
			"title" => $v['name'],
			"state" => ((int)$v["right"] - (int)$v["left"] > 1) ? "closed" : "opened"
		);
	}
	echo json_encode($result);
}

function addChild ($item,$name) {
	if (!$item || empty(trim($name)))
		die('{"status":false}');
	
	$tree = new Tree();
	$id = (int)$tree->_create($item);
	$res = false;
	if ($id) {
		$row = "UPDATE `tree` SET " . 
					"`name` = '".$name."' WHERE `id` = ".$id;
		$res = Db::getInstance()->execute($row);
	}
	echo $res ? '{"status":true}':'{"status":false}';
}

function addRoot ($name) {
	addChild (1,$name);
}

function editNode ($item,$name) {
	if (!$item || empty(trim($name)))
		die('{"status":false}');
	
	$tree = new Tree();
	$row = "UPDATE `tree` SET " . 
					"`name` = '".$name."' WHERE `id` = ".$item;
	$res = Db::getInstance()->execute($row);
	echo $res ? '{"status":true}':'{"status":false}';
}

function delChild ($item) {
	if (!$item)
		die('{"status":false}');
	
	$tree = new Tree();
	$tree->_remove($item);
	echo '{"status":true}';
	
}
?>