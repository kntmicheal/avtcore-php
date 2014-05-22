<?
/// <summary>
/// Erzeugt die Nachrichtenzeile
/// </summary>
class avorium_core_ui_Notification {

	// Rendert die Nachrichtenzeile und gibt sie als HTML-Struktur zurück
	public static function render() {
		if (isset($_SESSION["notificationtype"]) && isset($_SESSION["notificationtext"])) {
			$html = "<div class=\"notification ".$_SESSION["notificationtype"]."\">".$_SESSION["notificationtext"]."</div>";
			unset($_SESSION["notificationtype"]);
			unset($_SESSION["notificationtext"]);
			return $html;
		}
		return "";
	}

	// Merkt sich die Nachricht für die nächste Anzeige bzw. Hängt diese an eine bereits anzuzeigene Nachricht an.
	// Wird in utils verwendet.
	public static function shownotification($type, $text) {
		$_SESSION["notificationtype"] = $type; // Session wird verwendet, damit bei Umleitungen hinterher noch Nachrichten angezeigt werden
		if (isset($_SESSION["notificationtext"])) {
			$_SESSION["notificationtext"] .= "<br/>".$text;
		} else {
			$_SESSION["notificationtext"] = $text;
		}
	}

}
?>
