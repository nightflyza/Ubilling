function highlightTableRows(tableId, hoverClass, clickClass, multiple)
{
	var table = document.getElementById(tableId);
	
	//если не был передан четвертый аргумент, то по умолчанию принимаем его как true
	if (typeof multiple == 'undefined') multiple = true;
	
	if (hoverClass)
	{
		//регулярное выражение для поиска среди значений атрибута class элемента, имени класса обеспечивающего подсветку по наведению мыши на строку.
		//Данное рег. выражение используется и в обработчике onclick
		var hoverClassReg = new RegExp("\\b"+hoverClass+"\\b");
		
		table.onmouseover = table.onmouseout = function(e)
		{
			if (!e) e = window.event;
			var elem = e.target || e.srcElement;
			while (!elem.tagName || !elem.tagName.match(/td|th|table/i)) elem = elem.parentNode;

			//Если событие связано с элементом TD или TH из раздела TBODY
			if (elem.parentNode.tagName == 'TR' && elem.parentNode.parentNode.tagName == 'TBODY')
			{
				var row = elem.parentNode;//ряд содержащий ячейку таблицы в которой произошло событие
				//Если текущий ряд не "кликнутый" ряд, то в разисимости от события либо применяем стиль, назначая класс, либо убираем.
				if (!row.getAttribute('clickedRow')) row.className = e.type=="mouseover"?row.className+" "+hoverClass:row.className.replace(hoverClassReg," ");
			}
		};
	}

	
	if (clickClass) table.onclick = function(e)
	{
		if (!e) e = window.event;
		var elem = e.target || e.srcElement;
		while (!elem.tagName || !elem.tagName.match(/td|th|table/i)) elem = elem.parentNode;

		//Если событие связано с элементом TD или TH из раздела TBODY
		if (elem.parentNode.tagName == 'TR' && elem.parentNode.parentNode.tagName == 'TBODY')
		{
			//регулярное выражение для поиска среди значений атрибута class элемента, имени класса обеспечивающего подсветку по клику на строке.
			var clickClassReg = new RegExp("\\b"+clickClass+"\\b");
			var row = elem.parentNode;//ряд содержащий ячейку таблицы в которой произошло событие
			
			//Если текущий ряд уже помечен стилем как "кликнутый"
			if (row.getAttribute('clickedRow'))
			{
				row.removeAttribute('clickedRow');//убираем флаг того что ряд "кликнут"
				row.className = row.className.replace(clickClassReg, "");//убираем стиль для выделения кликом
				row.className += " "+hoverClass;//назначаем класс для выделения строки по наведею мыши, т.к. курсор мыши в данный момент на строке, а выделение по клику уже снято
			}
			else //ряд не подсвечен
			{
				//если задана подсветка по наведению на строку, то убираем её
				if (hoverClass) row.className = row.className.replace(hoverClassReg, "");
				row.className += " "+clickClass;//применяем класс подсветки по клику
				row.setAttribute('clickedRow', true);//устанавливаем флаг того, что ряд кликнут и подсвечен
				
				//если разрешена подсветка только последней кликнутой строки
				if (!multiple)
				{
					var lastRowI = table.getAttribute("lastClickedRowI");
					//Если то текущей строки была кликнута другая строка, то снимаем с неё подсветку и флаг "кликнутости"
					if (lastRowI!==null && lastRowI!=='' && row.sectionRowIndex!=lastRowI)
					{
						var lastRow = table.tBodies[0].rows[lastRowI];
						lastRow.className = lastRow.className.replace(clickClassReg, "");//снимаем подсветку с предыдущей кликнутой строки
						lastRow.removeAttribute('clickedRow');//удаляем флаг "кликнутости" с предыдущей кликнутой строки
					}
				}
				//запоминаем индекс последнего кликнутого ряда
				table.setAttribute("lastClickedRowI", row.sectionRowIndex);
			}
		}
	};
}