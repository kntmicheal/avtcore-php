<?php
require_once dirname(__FILE__).'/code/avorium/core/persistence/MySqlPersistenceAdapter.php';
$GLOBALS['PersistenceAdapter'] = new avorium_core_persistence_MySqlPersistenceAdapter('127.0.0.1', 'avtcoretest', 'avtcoretest', 'avtcoretest');