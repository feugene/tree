<?php

class Tree
{

	function _get_node($id) {
		return Db::getInstance()->getRow("SELECT * FROM `tree` WHERE id=".(int) $id);
	}
	
	function _get_children($id, $recursive = false) {
		if($recursive) {
			$node = $this->_get_node($id);
			$sql = "SELECT * FROM `tree` 
					WHERE `left` >= ".(int) $node["left"]." AND `right` <= ".(int) $node["right"]." 
					ORDER BY `left` ASC";
		} else {
			$sql = "SELECT * FROM `tree` 
					WHERE `pid` = ".(int) $id."
					ORDER BY `position` ASC";
		}
		return Db::getInstance()->executeS($sql);
	}
	
	function _move($id, $ref_id, $position = 0, $is_copy = false) {
		if ((int)$ref_id === 0 || (int)$id === 1)
			return false;
		$sql		= array();						
		$node		= $this->_get_node($id);		
		$nchildren	= $this->_get_children($id);	
		$ref_node	= $this->_get_node($ref_id);	
		$rchildren	= $this->_get_children($ref_id);

		$ndif = 2;
		$node_ids = array(-1);
		
		if($node !== false) {
			$node_ids = array_keys($this->_get_children($id, true));
			if(in_array($ref_id, $node_ids)) return false;
			$ndif = $node["right"] - $node["left"] + 1;
		}
		

		if($position >= count($rchildren)) {
			$position = count($rchildren);
		}
		
		// Очищение старого Родителя
		if($node !== false && $is_copy == false) {
			
			$row = "UPDATE `tree` SET " . 
					"`position` = `position` - 1 " . 
				"WHERE `pid` = ".$node["pid"]." AND `position` > ".$node["position"];
			Db::getInstance()->execute($row);
			
			$row = "UPDATE `tree` SET " . 
					"`left` = `left` - ".$ndif.
				" WHERE `left` > `right`";
			Db::getInstance()->execute($row);
			
			$row = "UPDATE `tree` SET " . 
					"`right` = `right` - ".$ndif.
				" WHERE `right` > `left`  AND `id` NOT IN(".implode(",", $node_ids).")";
			Db::getInstance()->execute($row);
			
		}
		
		// Подготовка нового родителя
		$row = "UPDATE `tree` SET " . 
				"`position` = `position` + 1 ".
			" WHERE `pid` = ".$ref_id." AND `position` >= ".$position." ".
			( $is_copy ? "" : " AND `id` NOT IN (".implode(",", $node_ids).") ");
		Db::getInstance()->execute($row);
				
		$ref_ind = $ref_id === 0 ? (int)$rchildren[count($rchildren) - 1]["right"] + 1 : (int)$ref_node["right"];		
		$ref_ind = max($ref_ind, 1);
		
		$self = ($node !== false && !$is_copy && (int)$node["pid"] == $ref_id && $position > $node["position"]) ? 1 : 0;
		
		foreach($rchildren as $k => $v) {
			if($v["position"] - $self == $position) {
				$ref_ind = (int)$v["left"];
				break;
			}
		}
		
		if($node !== false && !$is_copy && $node["left"] < $ref_ind) {
			$ref_ind -= $ndif;
		}
		
		$row = "UPDATE `tree` SET " . 
				"`left` = `left` + ".$ndif."
				WHERE `left`>= ".$ref_ind." "
				.( $is_copy ? "" : " AND `id` NOT IN (".implode(",", $node_ids).") ");
		Db::getInstance()->execute($row);
		
		$row = "UPDATE `tree` SET " . 
				"`right` = `right` + ".$ndif."
				WHERE `right`>= ".$ref_ind." "
				.( $is_copy ? "" : " AND `id` NOT IN (".implode(",", $node_ids).") ");
		Db::getInstance()->execute($row);
		
		$ldif = $ref_id == 0 ? 0 : $ref_node["depth"] + 1;
		$idif = $ref_ind;
		
		if($node !== false) {// перемещение существующего
			
			$ldif = $node["depth"] - ($ref_node["depth"] + 1);
			$idif = $node["depth"] - $ref_ind;
			if($is_copy) {
				
				$row = "INSERT INTO `tree` (" . 
						"`pid`, " . 
						"`position`, " . 
						"`left`, " . 
						"`right`, " . 
						"`depth`" . 
					") " . 
						"SELECT " . 
							"".$ref_id.", " . 
							"`position`, " . 
							"`left` - (".($idif + ($node["left"] >= $ref_ind ? $ndif : 0))."), " . 
							"`right` - (".($idif + ($node["left"] >= $ref_ind ? $ndif : 0))."), " . 
							"`depth` - (".$ldif.") " . 
						"FROM `tree` " . 
						"WHERE `id` IN (".implode(",", $node_ids).") " . 
						"ORDER BY `depth` ASC";
				
				Db::getInstance()->execute($row);			
			}
			else { 
				
				$row = "UPDATE `tree` SET " . 
					"`pid` = ".$ref_id."
					`position` = ".$position."
				WHERE `id`= ".$id;
				Db::getInstance()->execute($row);
				
				$row = "UPDATE `tree` SET " . 
						"`left` = `left` - (".$idif."), " . 
						"`right` = `right` - (".$idif."), " . 
						"`depth` = `depth` - (".$ldif.") " . 
					"WHERE `id` IN (".implode(",", $node_ids).") ";
				Db::getInstance()->execute($row);		
			}
		}
		else { // вставка нового
			
			$row = "INSERT INTO `tree` (`pid`,`position`,`left`,`right`,`depth`) VALUES ('".$ref_id."','".$position."','".$idif."','".($idif + 1)."','".$ldif."')";
			Db::getInstance()->execute($row);
			
		}
		
		$ind = Db::getInstance()->Insert_ID();
		
		if($is_copy) 
			$this->_fix_copy($ind, $position);
		return $node === false || $is_copy ? $ind : true;
	}
	
	function _fix_copy($id, $position) {
		$node = $this->_get_node($id);
		$children = $this->_get_children($id, true);

		$map = array();
		for($i = $node["left"] + 1; $i < $node["right"]; $i++) {
			$map[$i] = $id;
		}
		
		foreach($children as $cid => $child) {
			if((int)$cid == (int)$id) {
				$row = "UPDATE `tree` SET " . 
						"`position` = ".$position."
						WHERE `id` = ".$cid;
				Db::getInstance()->execute($row);
				continue;
			}
			$row = "UPDATE `tree` SET " . 
						"`pid` = ".$map[(int)$child["left"]]."
						WHERE `id` = ".$cid;
			Db::getInstance()->execute($row);
			
			for($i = $child["left"] + 1; $i < $child["right"]; $i++) {
				$map[$i] = $cid;
			}
		}
	}
	
	function _create($parent, $position=0) {
		return $this->_move(0, $parent, $position);
	}
	
	function _remove($id) {
		if((int)$id === 1) 
			return false;
		$data = $this->_get_node($id);
		$lft = (int)$data["left"];
		$rgt = (int)$data["right"];
		$dif = $rgt - $lft + 1;

		// Удаляем нод и его детей		
		$row = "DELETE FROM `tree` WHERE `left` >= ".$lft." AND `right` <= ".$rgt;
		Db::getInstance()->execute($row);
		
		// сдвигаем левые индексы нодов, находящихся справа от Нода.
		$row = "UPDATE `tree` SET " . 
					"`left` = `left` - ".$dif." 
				WHERE `left` > ".$rgt;
		Db::getInstance()->execute($row);
		
		// сдвигаем правые индексы нодов, находящихся слева от Нода.
		$row = "UPDATE `tree` SET " . 
					"`right` = `right` - ".$dif." 
				WHERE `right` > ".$lft;
		Db::getInstance()->execute($row);
		
		$pid = (int)$data["pid"];
		$pos = (int)$data["position"];

		// обновляем позицию соседей ниже уделенного нода
		$row = "UPDATE `tree` SET " . 
					"`position` = `position` - 1
				WHERE `pid` = ".$pid." AND `position` > ".$pos;
		Db::getInstance()->execute($row);
		
		return true;
	}
	
	
		
}