<? 
require_once('php.classes/class.Db.php');
require_once('php.classes/class.Tree.php');
?>
<!DOCTYPE html><html lang="ru">
<head>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="http://getbootstrap.com/dist/css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
</head>
<style>
ul {list-style:none; margin-left:10px; padding-left:0px !important;}
.tree {width:200px; height:100%; position:fixed; background:#eee;}
.manual li{cursor:pointer; margin-left:10px;}
li .name{font-family:Cambria, Segoe, Verdana, sans-serif;}
#alog {width:400px; margin-left:640px; border:1px solid #999; font-size:10px;}
</style>
<body>
	<input type="button" value="Обновить" id="reload" />
    <input type="button" value="Add Child" id="addChild" disabled />
    <input type="button" value="Add in Root" id="addRoot" />
    <input type="button" value="Del Node" id="delChild" disabled />
    <input type="button" value="Edit" id="editChild" disabled />
	    
    <div id="tree" class="tree manual"><ul>
<?
	$tree = new Tree();
	
	foreach ($tree->_get_children(1) as $row){
		$state = ((int)$row["right"] - (int)$row["left"]);
		echo '<li id="node_'.$row['id'].'" data-state="'.($state > 1 ?'closed':'opened').'">'.( $state > 1?'<span class="glyphicon glyphicon-folder-close"></span>':'<span class="glyphicon glyphicon-file"></span>').' <span class="name">'.$row['name'].'</span></li>';
	}	
?>
    </ul></div>
    <div id="alog">
    	<p>Нажмите на "папку" - чтобы открыть папку</p>
        <p>Нажмите на "название нода" - чтобы произвести действия над ним</p>
        <p>Также в классе "Tree" предусмотрена возможность D'n'D нодов из папки в в папку, но на фронте не организована.</p>
        <p>Классы "Tree" и "DB" упрощены практически до минимума с которым можно работать. убраны все проверки и санация, оставлены только методы с которыми работает данный пример.</p>
    </div>
    <script type="text/javascript" src="http://feugene.org/resources/lib/jquery/jquery-2.0.3.min.js"></script>
	<script type="text/javascript" src="http://feugene.org/tree.js"></script>
</body>
</html>
