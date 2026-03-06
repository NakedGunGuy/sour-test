<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

class RelationshipDetector
{
    private const IGNORED_COLUMNS = ['id', 'created_at', 'updated_at', 'sort_order', 'position'];

    /**
     * @param Table[] $allTables
     * @return array{mto: array, otm: array, mtm: array}
     */
    public function detect(Table $table, array $allTables): array
    {
        $tableIndex = [];
        foreach ($allTables as $t) {
            $tableIndex[$t->name] = $t;
        }

        return [
            'mto' => $this->manyToOne($table),
            'otm' => $this->oneToMany($table, $tableIndex),
            'mtm' => $this->manyToMany($table, $tableIndex),
        ];
    }

    /**
     * MTO: This table has FKs pointing to other tables.
     * @return ForeignKey[]
     */
    private function manyToOne(Table $table): array
    {
        return $table->foreignKeys;
    }

    /**
     * OTM: Other tables have FKs pointing to this table (excluding junction tables).
     * @return array<array{table: string, foreignKey: ForeignKey}>
     */
    private function oneToMany(Table $table, array $tableIndex): array
    {
        $pk = $table->primaryKeyColumn();
        if (!$pk) {
            return [];
        }

        $result = [];
        foreach ($tableIndex as $otherTable) {
            if ($otherTable->name === $table->name) {
                continue;
            }
            if ($this->isJunctionTable($otherTable)) {
                continue;
            }
            foreach ($otherTable->foreignKeys as $fk) {
                if ($fk->referencesTable === $table->name && $fk->referencesColumn === $pk->name) {
                    $result[] = ['table' => $otherTable->name, 'foreignKey' => $fk];
                }
            }
        }

        return $result;
    }

    /**
     * MTM: Junction tables connecting this table to another.
     * @return array<array{junctionTable: string, relatedTable: string, localFk: ForeignKey, remoteFk: ForeignKey}>
     */
    private function manyToMany(Table $table, array $tableIndex): array
    {
        $pk = $table->primaryKeyColumn();
        if (!$pk) {
            return [];
        }

        $result = [];
        foreach ($tableIndex as $candidate) {
            if (!$this->isJunctionTable($candidate)) {
                continue;
            }

            $localFk = null;
            $remoteFk = null;

            foreach ($candidate->foreignKeys as $fk) {
                if ($fk->referencesTable === $table->name && $fk->referencesColumn === $pk->name) {
                    $localFk = $fk;
                } else {
                    $remoteFk = $fk;
                }
            }

            if ($localFk && $remoteFk) {
                $result[] = [
                    'junctionTable' => $candidate->name,
                    'relatedTable' => $remoteFk->referencesTable,
                    'localFk' => $localFk,
                    'remoteFk' => $remoteFk,
                ];
            }
        }

        return $result;
    }

    public function isJunctionTable(Table $table): bool
    {
        if (count($table->foreignKeys) !== 2) {
            return false;
        }

        $meaningfulColumns = 0;
        foreach ($table->columns as $col) {
            if ($col->primaryKey) {
                continue;
            }
            if (in_array($col->name, self::IGNORED_COLUMNS)) {
                continue;
            }
            // Check if this column is one of the FK columns
            $isFk = false;
            foreach ($table->foreignKeys as $fk) {
                if ($fk->column === $col->name) {
                    $isFk = true;
                    break;
                }
            }
            if (!$isFk) {
                $meaningfulColumns++;
            }
        }

        return $meaningfulColumns === 0;
    }
}
