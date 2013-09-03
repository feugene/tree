$(document).ready(function() {
	var curItem;
	// обновить
	$("#reload").on("click",function () {
		$.ajax({
			url : "tree_server.php",
			data : {"operation" : "reload"},
			type : "POST"			
		}).done(function ( data ) {
			var list='';
			data = JSON.parse(data);
			if (data.length) {
				$.each(data, function(){
					list += '<li id="node_'+this.id+'" data-state="'+this.state+'">'+(this.state=='closed'?'<span class="glyphicon glyphicon-folder-close"></span>':'<span class="glyphicon glyphicon-file"></span>')+' <span class="name">'+this.title+'</span></li>';
				});
			}
			$("#tree ul").html(list);
		});
	});
	
	// выбор итема
	$("#tree").on("click","li .name", function () {
		$("#tree li .name").css('background','none');
		curItem = $(this).closest('li').attr('id');
		$(this).css('background','#999');
		
		$('#addChild, #delChild, #editChild').removeAttr('disabled');
	});
	
	// щелчок на папку
	$("#tree").on("click","li[data-state=closed] .glyphicon", function () {
		var me = $(this),
			li = $(this).parent('li'),
			citem = li.attr('id'),
			icon = li.children('.glyphicon');
		
		if (!citem) return false;
		
		if (icon.hasClass('glyphicon-file'))
			return false;
		
		if (icon.hasClass('glyphicon-folder-open')){
			$('.list',li).remove();
			icon.removeClass('glyphicon-folder-open').addClass('glyphicon-folder-close');
			return;
		}
		icon.removeClass('glyphicon-folder-close glyphicon-folder-open').addClass('glyphicon-refresh');
		
		$.ajax({
			url : "tree_server.php",
			data : {"operation" : "getChildren","item":citem.replace('node_','')},
			type : "POST"			
		}).done(function ( data ) {
			$("#tree li").css('background','none');
			var list='';
			data = JSON.parse(data);
			if (data.length) {
				$.each(data, function(){
					list += '<li id="node_'+this.id+'" data-state="'+this.state+'">'+(this.state=='closed'?'<span class="glyphicon glyphicon-folder-close"></span>':'<span class="glyphicon glyphicon-file"></span>')+' <span class="name">'+this.title+'</span></li>';
				});
			}
			if ( $('.list',li).length ) {
				$('.list',li).html(list);
			} else {
				li.append('<div class="list"><ul>'+list+'</ul></div>');
			}
			icon.removeClass('glyphicon-folder-close glyphicon-refresh').addClass('glyphicon-folder-open');
		});
	});
	
		
	// Добавить
	$("#addChild").on("click",function () {
		if (!curItem)
			return false;
		var name=prompt('Название?','');
		
		if (!name.length) {
			alert('Введите название');
			return false;
		}
		
		$.ajax({
			url : "tree_server.php",
			data : {"operation" : "addChild","item":curItem.replace('node_',''),"name":name},
			type : "POST"			
		}).done(function ( data ) {
			data = JSON.parse(data);
			if (data.status)
				$("#reload").click();
		});
	});
	
	// Добавить в корень
	$("#addRoot").on("click",function () {
		var name=prompt('Название?','');
		
		if (!name.length) {
			alert('Введите название');
			return false;
		}
		
		$.ajax({
			url : "tree_server.php",
			data : {"operation" : "addRoot","name":name},
			type : "POST"			
		}).done(function ( data ) {
			data = JSON.parse(data);
			if (data.status)
				$("#reload").click();
		});
	});
	
	
	// Удалить
	$("#delChild").on("click",function () {
		if (!curItem)
			return false;
		if (!confirm('Удалить нод? Удаляться также все вложенные папки и файлы')){
			return false;
		};
		$.ajax({
			url : "tree_server.php",
			data : {"operation" : "delChild","item":curItem.replace('node_','')},
			type : "POST"			
		}).done(function ( data ) {
			data = JSON.parse(data);	
			if (data.status)
				$("#reload").click();
		});
	});
	
	// Редактировать
	$("#editChild").on("click",function () {
		if (!curItem)
			return false;
		var oldName = $('#'+curItem+' .name').text(),
			newName=prompt('Новое название',oldName);
		
		if (!newName.length) {
			alert('Введите название');
			return false;
		}
		
		$.ajax({
			url : "tree_server.php",
			data : {"operation" : "editChild","item":curItem.replace('node_',''),"name":newName},
			type : "POST"			
		}).done(function ( data ) {
			data = JSON.parse(data);	
			if (data.status)
				$("#reload").click();
		});
	});
	
});