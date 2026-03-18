<?php

declare(strict_types=1);

namespace Sauerkraut\Database\Schema;

class RelationshipDetector
{
    private const IGNORED_COLUMNS = ['id', 'created_at', 'updated_at', 'sort_order', 'position'];

    /**
     * @param Table[] $allTables
     */
    public function detect(Table $table, array $allTables): DetectedRelationships
    {
        $tableIndex = [];
        foreach ($allTables as $table) {
            $tableIndex[$table->name] = $table;
        }

        return new DetectedRelationships(
            manyToOne: $this->manyToOne($table),
            oneToMany: $this->oneToMany($table, $tableIndex),
            manyToMany: $this->manyToMany($table, $tableIndex),
        );
    }

    /**
     * @return ForeignKey[]
     */
    private function manyToOne(Table $table): array
    {
        return $table->foreignKeys;
    }

    /**
     * @return OneToManyRelation[]
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
                    $result[] = new OneToManyRelation($otherTable->name, $fk);
                }
            }
        }

        return $result;
    }

    /**
     * @return ManyToManyRelation[]
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
                $result[] = new ManyToManyRelation(
                    $candidate->name,
                    $remoteFk->referencesTable,
                    $localFk,
                    $remoteFk,
                );
            }
        }

        return $result;
    }

    private function isJunctionTable(Table $table): bool
    {
        if (count($table->foreignKeys) !== 2) {
            return false;
        }

        $fkColumns = array_map(fn (ForeignKey $fk) => $fk->column, $table->foreignKeys);

        foreach ($table->columns as $col) {
            if ($col->primaryKey) {
                continue;
            }
            if (in_array($col->name, self::IGNORED_COLUMNS)) {
                continue;
            }
            if (in_array($col->name, $fkColumns)) {
                continue;
            }

            return false;
        }

        return true;
    }
}
