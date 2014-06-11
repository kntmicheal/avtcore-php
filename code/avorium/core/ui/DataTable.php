<?php

require_once($GLOBALS['AvtRolePersistenceHandler']);

/**
 * Renderbare Tabelle, die HTML ausspuckt, sortierbar ist und mit
 * Listen von PersistentObject umgehen kann
 */
class avorium_core_ui_DataTable {

	private static function renderCell($datarow, $key, $type = 'string') {
		switch($type) {
			case 'bool':
				return '<input type="checkbox" disabled="disabled"'.($datarow->$key ? ' checked="checked"' : '').' />';
			case 'datetime':
				return DateTime::createFromFormat('U', '0'.$datarow->$key)->format('d.m.Y H:i'); // '0'. für evtl. leere Datumsfelder
			default:
				return isset($datarow->$key) ? $datarow->$key : '';
		}
	}
	
	private static function renderHead($listid, &$columns, $sortable, $sorting, $descending) {
		$html = '<table border="0" cellspacing="0" cellpadding="0"'
		       .($listid != null ? ' id="'.$listid.'"' : '')
		       .'><thead><tr>';
		foreach ($columns as $key => $value) {
			if ($sortable) {
				$url = '?'.$listid.'_sort='.$key.'&'.$listid.'_sortdesc='.($key === $sorting ? !$descending : '');
				$thclass = ($key === $sorting) ? ' class="sorted'.($descending ? ' desc' : '').'"' : '';
				$html .= '<th'.$thclass.'><a href="'.$url.'">'.__($value['title']).'</a></th>';
			} else {
				$html .= '<th>'.__($value['title']).'</th>';
			}
			// Rollenrechte vorbelegen, damit die nicht für jede Zelle abgefragt werden müssen
			if (isset($value['urlformat']) && isset($value['urlfield'])) {
				$columns[$key]['canread'] = !isset($value['accessurl']) 
					|| AvtRolePersistenceHandler::canRoleReadPage($_SESSION['roleuuid'], $value['accessurl']);
			}
		}
		$html .= '</tr></thead><tbody>';
		return $html;
	}
	
	private static function renderBody($datarows, $columns) {
		$html = '';
		foreach ($datarows as $po) {
			$html .= '<tr>';
			foreach ($columns as $key => $value) {
				if (isset($value['urlformat']) && isset($value['urlfield']) && $value['canread']) {
					$url = str_replace('{0}', urlencode($po->$value['urlfield']), $value['urlformat']);
					$html .=  '<td><a href="'.$url.'">'.$po->$key.'</a></td>';
				} else {
					$html .= '<td'.(isset($value['align']) ? ' style="text-align:'.$value['align'].'"' : '').'>'
					        .static::renderCell($po, $key, isset($value['type']) ? $value['type'] : null)
					        .'</td>';
				}
			}
			$html .= '</tr>';
		}
		return $html;
	}
	
	private static function renderFoot() {
		return '</tbody></table>';
	}

	/**
	 * Rendert eine Tabelle auf Basis der übergebenen Objektliste und Sortierung und gibt HTML zurück.
	 * Dabei werden die Sitzungsvariablen $listid.'_sort' und $listid.'_sortdesc' verwendet.
	 * 
	 * @param array $datarows Array von Datenobjekten. Alle Objekte müssen denselben Typ haben.
	 * @param array $columns Spaltendefinitionen.
	 * @param type $listid ID der Liste. Wird für Referenz der Sortierungsvariablen und als ID für die HTML-Tabelle
	 *                     verwendet.
	 * @param type $sortable Gibt an, ob die Tabelle über deren Spaltenköpfe sortierbar sein soll. Default: true.
	 * @return string HTML der Tabelle.
	 */
	public static function render(array $datarows, array $columns, $listid = null, $sortable = true) {
		if (count($datarows) < 1) {
			return '<span class="noitems">'.__('Keine Elemente zum Anzeigen').'</span>';
		}
		if ($sortable) {
			// Sortierung aus Request in Sitzung speichern
			if (($getsort = filter_input(INPUT_GET, $listid.'_sort'))) {
				$_SESSION[$listid.'_sort'] = $getsort;
			}
			$getsortdesc = filter_input(INPUT_GET, $listid.'_sortdesc', FILTER_VALIDATE_BOOLEAN);
			if ($getsortdesc !== null) {
				$_SESSION[$listid.'_sortdesc'] = $getsortdesc;
			}
		}
		$sorting = isset($_SESSION[$listid.'_sort']) ? $_SESSION[$listid.'_sort'] : null;
		$descending = isset($_SESSION[$listid.'_sortdesc']) ? $_SESSION[$listid.'_sortdesc'] : false;
		$html = static::renderHead($listid, $columns, $sortable, $sorting, $descending);
		// Sortieren
		if ($sortable && $sorting != null) {
			usort($datarows, function($a, $b) use ($sorting, $descending) {
				$result = strcmp($a->$sorting, $b->$sorting);
				return $descending ? -$result : $result;
			});
		}
		$html .= static::renderBody($datarows, $columns);
		$html .= static::renderFoot();
		return $html;
	}
}