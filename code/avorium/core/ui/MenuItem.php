<?php

/// <summary>
/// A MenuItem represents a single item in a menu.</summary>
/// <remarks>A MenuItem is a single "item" in a menu.  Typically a MenuItem will have some <see cref="Text"/>
/// associated with it, and often a <see cref="Url"/> or <see cref="CommandName"/>.  MenuItems can also optionally
/// have a set of <see cref="SubItems"/>, which represents a nested submenu.</remarks>
class avorium_core_ui_MenuItem {
	
	/**
	 * Identifizierer des MenuItems. Dient dazu, in der localconfig einem
	 * bestehenden Element einen neuen Eintrag anzuhängen
	 * @var type String
	 */
	public $id;
	
	/**
	 * Priorität des Menüelements innerhalb derselben Menühierarchie. Bestimmt Reihenfolge in der
	 * Anzeige. Wenn bereits ein Menüelement mit derselben Priorität in der gleichen Ebene existiert,
	 * wird das neue Element dahinter gehangen.
	 * @var type int
	 */
	public $priority;

	/// <summary>
	/// Name des Menüeintrages, welcher auf der Seite angezeigt wird
	/// </summary>
	public $title;

	/// <summary>
	/// Pfad der Seite, z.B. "/BWA/DeckungsbeitragAnzeigen.aspx"
	/// </summary>
	public $page;
	
	/// <summary>
	/// Liste von Untermenüelelementen
	/// </summary>
	public $items = array();
	
	/// <summary>
	/// Is used to configure visibility
	/// </summary>
	public $visible;

	/// <summary>
	/// Referenz auf übergeordnetes Menüelement. Wird gesetzt, wenn AddMenuItem()
	/// aufgerufen wird und dient der Selektion und Sichtbarmachung bei Seitenwechsel.
	/// Beim Root-Element ist diese Property null.
	/// </summary>
	public $parentMenuItem;

	/// <summary>
	/// Gibt an, ob das MenuItem selektiert ist. Wird in Brotkrümeln und in Menüs verwendet.
	/// Das Setzen erfolgt beim Instanziiern in MenuFactory.getFilteredRootMenuItemForUser()
	/// </summary>
	public $selected;

	public function __construct(array $properties = NULL) {
		$this->visible = true;
		if ($properties == null) {
			return;
		}
		foreach($properties as $property => $value) {
			if (property_exists($this, $property)) {
				$this->$property = $value;
			}
		}
	}

	/// <summary>
	/// Add a sub menu item to this one and defines this one as parent
	/// of the sub menu item. needed to recursively select menu structures.
	/// Do not use Items.add() directly!
	/// </summary>
	/// <param name="menuItem">Menu item to add.</param>
	// OBSOLETE: MenuFactory::addMenuItem() verwenden!
	public function addMenuItem(avorium_core_ui_MenuItem $menuItem)
	{
		$menuItem->parentMenuItem = $this;
		$this->items[] = $menuItem;
	}

}

?>