<?php
 namespace Doctrine\DBAL\Schema; use Doctrine\DBAL\Types\Type; class SQLAnywhereSchemaManager extends AbstractSchemaManager { public function createDatabase($database) { parent::createDatabase($database); $this->startDatabase($database); } public function dropDatabase($database) { $this->tryMethod('stopDatabase', $database); parent::dropDatabase($database); } public function startDatabase($database) { $this->_execSql($this->_platform->getStartDatabaseSQL($database)); } public function stopDatabase($database) { $this->_execSql($this->_platform->getStopDatabaseSQL($database)); } protected function _getPortableDatabaseDefinition($database) { return $database['name']; } protected function _getPortableSequenceDefinition($sequence) { return new Sequence($sequence['sequence_name'], $sequence['increment_by'], $sequence['start_with']); } protected function _getPortableTableColumnDefinition($tableColumn) { $type = $this->_platform->getDoctrineTypeMapping($tableColumn['type']); $type = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type); $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type); $precision = null; $scale = null; $fixed = false; $default = null; if (null !== $tableColumn['default']) { $default = preg_replace(array("/^'(.*)'$/", "/''/"), array("$1", "'"), $tableColumn['default']); if ('autoincrement' == $default) { $default = null; } } switch ($tableColumn['type']) { case 'binary': case 'char': case 'nchar': $fixed = true; } switch ($type) { case 'decimal': case 'float': $precision = $tableColumn['length']; $scale = $tableColumn['scale']; } return new Column( $tableColumn['column_name'], Type::getType($type), array( 'length' => $type == 'string' ? $tableColumn['length'] : null, 'precision' => $precision, 'scale' => $scale, 'unsigned' => (bool) $tableColumn['unsigned'], 'fixed' => $fixed, 'notnull' => (bool) $tableColumn['notnull'], 'default' => $default, 'autoincrement' => (bool) $tableColumn['autoincrement'], 'comment' => isset($tableColumn['comment']) && '' !== $tableColumn['comment'] ? $tableColumn['comment'] : null, )); } protected function _getPortableTableDefinition($table) { return $table['table_name']; } protected function _getPortableTableForeignKeyDefinition($tableForeignKey) { return new ForeignKeyConstraint( $tableForeignKey['local_columns'], $tableForeignKey['foreign_table'], $tableForeignKey['foreign_columns'], $tableForeignKey['name'], $tableForeignKey['options'] ); } protected function _getPortableTableForeignKeysList($tableForeignKeys) { $foreignKeys = array(); foreach ($tableForeignKeys as $tableForeignKey) { if (!isset($foreignKeys[$tableForeignKey['index_name']])) { $foreignKeys[$tableForeignKey['index_name']] = array( 'local_columns' => array($tableForeignKey['local_column']), 'foreign_table' => $tableForeignKey['foreign_table'], 'foreign_columns' => array($tableForeignKey['foreign_column']), 'name' => $tableForeignKey['index_name'], 'options' => array( 'notnull' => $tableForeignKey['notnull'], 'match' => $tableForeignKey['match'], 'onUpdate' => $tableForeignKey['on_update'], 'onDelete' => $tableForeignKey['on_delete'], 'check_on_commit' => $tableForeignKey['check_on_commit'], 'clustered' => $tableForeignKey['clustered'], 'for_olap_workload' => $tableForeignKey['for_olap_workload'] ) ); } else { $foreignKeys[$tableForeignKey['index_name']]['local_columns'][] = $tableForeignKey['local_column']; $foreignKeys[$tableForeignKey['index_name']]['foreign_columns'][] = $tableForeignKey['foreign_column']; } } return parent::_getPortableTableForeignKeysList($foreignKeys); } protected function _getPortableTableIndexesList($tableIndexRows, $tableName = null) { foreach ($tableIndexRows as &$tableIndex) { $tableIndex['primary'] = (boolean) $tableIndex['primary']; $tableIndex['flags'] = array(); if ($tableIndex['clustered']) { $tableIndex['flags'][] = 'clustered'; } if ($tableIndex['with_nulls_not_distinct']) { $tableIndex['flags'][] = 'with_nulls_not_distinct'; } if ($tableIndex['for_olap_workload']) { $tableIndex['flags'][] = 'for_olap_workload'; } } return parent::_getPortableTableIndexesList($tableIndexRows, $tableName); } protected function _getPortableViewDefinition($view) { return new View( $view['table_name'], preg_replace('/^.*\s+as\s+SELECT(.*)/i', "SELECT$1", $view['view_def']) ); } } 