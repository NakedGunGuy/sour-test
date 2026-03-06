<?php

declare(strict_types=1);

namespace Sauerkraut\CMS;

use App\Controllers\Controller;
use Sauerkraut\App;
use Sauerkraut\Database\Connection;
use Sauerkraut\Database\Schema\Inspector;
use Sauerkraut\Database\Schema\LabelResolver;
use Sauerkraut\Database\Schema\RelationshipDetector;
use Sauerkraut\Request;
use Sauerkraut\Response;
use Sauerkraut\View\View;

class CmsController extends Controller
{
    private Connection $db;
    private Inspector $inspector;
    private LabelResolver $labelResolver;
    private RelationshipDetector $relationshipDetector;

    public function __construct()
    {
        $app = App::getInstance();
        $this->db = $app->db();
        $this->inspector = $app->make(Inspector::class);
        $this->labelResolver = new LabelResolver($this->db);
        $this->relationshipDetector = new RelationshipDetector();
    }

    public function index(Request $request): Response
    {
        $tableNames = $this->visibleTableNames();

        $tables = [];
        foreach ($tableNames as $name) {
            $count = $this->db->queryOne("SELECT COUNT(*) as cnt FROM \"{$name}\"");
            $tableConfig = $this->tableConfig($name);
            $tables[] = [
                'name' => $name,
                'displayName' => $tableConfig->displayName(),
                'count' => $count['cnt'] ?? 0,
            ];
        }

        return Response::html(View::render('index', [
            'title' => 'CMS',
            'tables' => $tables,
            'allTables' => $this->allTableSummaries(),
        ]));
    }

    public function list(Request $request, string $table): Response
    {
        $this->guardTable($table);
        $tableConfig = $this->tableConfig($table);
        $schemaTable = $this->inspector->table($table);
        $allTables = $this->inspector->allTables();
        $relationships = $this->relationshipDetector->detect($schemaTable, $allTables);

        $page = max(1, (int) ($request->query('page') ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $pk = $schemaTable->primaryKeyColumn();
        $pkName = $pk ? $pk->name : 'rowid';

        $totalRow = $this->db->queryOne("SELECT COUNT(*) as cnt FROM \"{$table}\"");
        $total = $totalRow['cnt'] ?? 0;
        $totalPages = max(1, (int) ceil($total / $perPage));

        $rows = $this->db->queryAll(
            "SELECT * FROM \"{$table}\" ORDER BY \"{$pkName}\" DESC LIMIT {$perPage} OFFSET {$offset}"
        );

        // Resolve MTO labels (replace FK IDs with related record labels)
        $mtoMap = [];
        foreach ($relationships['mto'] as $fk) {
            $relatedTable = $this->inspector->table($fk->referencesTable);
            $labelCol = $this->labelResolver->labelColumn($relatedTable);
            $refPk = $relatedTable->primaryKeyColumn();
            if (!$refPk) continue;

            // Collect all FK IDs for batch query
            $ids = array_filter(array_unique(array_column($rows, $fk->column)));
            if (empty($ids)) continue;

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $labels = $this->db->queryAll(
                "SELECT \"{$refPk->name}\", \"{$labelCol}\" FROM \"{$fk->referencesTable}\" WHERE \"{$refPk->name}\" IN ({$placeholders})",
                array_values($ids)
            );

            $labelMap = [];
            foreach ($labels as $row) {
                $labelMap[$row[$refPk->name]] = $row[$labelCol];
            }
            $mtoMap[$fk->column] = [
                'labels' => $labelMap,
                'referencesTable' => $fk->referencesTable,
            ];
        }

        $listColumns = $tableConfig->listColumns();

        return Response::html(View::render('list', [
            'title' => $tableConfig->displayName(),
            'table' => $table,
            'tableConfig' => $tableConfig,
            'columns' => $listColumns,
            'rows' => $rows,
            'pkName' => $pkName,
            'mtoMap' => $mtoMap,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'allTables' => $this->allTableSummaries(),
        ]));
    }

    public function create(Request $request, string $table): Response
    {
        $this->guardTable($table);
        $tableConfig = $this->tableConfig($table);
        $schemaTable = $this->inspector->table($table);
        $fields = $tableConfig->formFields();

        // Populate FK select options
        $fields = $this->populateFkOptions($fields, $schemaTable);

        return Response::html(View::render('edit', [
            'title' => 'Create ' . $tableConfig->displayName(),
            'table' => $table,
            'tableConfig' => $tableConfig,
            'fields' => $fields,
            'record' => null,
            'isEdit' => false,
            'relationships' => ['otm' => [], 'mtm' => []],
            'relatedRecords' => [],
            'allTables' => $this->allTableSummaries(),
        ]));
    }

    public function edit(Request $request, string $table, string $id): Response
    {
        $this->guardTable($table);
        $tableConfig = $this->tableConfig($table);
        $schemaTable = $this->inspector->table($table);
        $pk = $schemaTable->primaryKeyColumn();
        $pkName = $pk ? $pk->name : 'rowid';

        $record = $this->db->queryOne(
            "SELECT * FROM \"{$table}\" WHERE \"{$pkName}\" = ?",
            [$id]
        );

        if (!$record) {
            return Response::html('<h1>404 Not Found</h1>', 404);
        }

        $fields = $tableConfig->formFields();
        $fields = $this->populateFkOptions($fields, $schemaTable);

        // Get relationships
        $allTables = $this->inspector->allTables();
        $relationships = $this->relationshipDetector->detect($schemaTable, $allTables);

        // Fetch OTM related records
        $relatedRecords = [];
        foreach ($relationships['otm'] as $otm) {
            $relatedTable = $this->inspector->table($otm['table']);
            $relatedConfig = $this->tableConfig($otm['table']);
            $labelCol = $this->labelResolver->labelColumn($relatedTable);
            $relatedPk = $relatedTable->primaryKeyColumn();

            $rows = $this->db->queryAll(
                "SELECT * FROM \"{$otm['table']}\" WHERE \"{$otm['foreignKey']->column}\" = ?",
                [$id]
            );

            $relatedRecords[] = [
                'type' => 'otm',
                'table' => $otm['table'],
                'displayName' => $relatedConfig->displayName(),
                'labelColumn' => $labelCol,
                'pkName' => $relatedPk ? $relatedPk->name : 'rowid',
                'rows' => $rows,
            ];
        }

        // Fetch MTM related records
        foreach ($relationships['mtm'] as $mtm) {
            $relatedTable = $this->inspector->table($mtm['relatedTable']);
            $relatedConfig = $this->tableConfig($mtm['relatedTable']);
            $labelCol = $this->labelResolver->labelColumn($relatedTable);
            $relatedPk = $relatedTable->primaryKeyColumn();

            $rows = $this->db->queryAll(
                "SELECT r.* FROM \"{$mtm['relatedTable']}\" r
                 JOIN \"{$mtm['junctionTable']}\" j ON j.\"{$mtm['remoteFk']->column}\" = r.\"{$mtm['remoteFk']->referencesColumn}\"
                 WHERE j.\"{$mtm['localFk']->column}\" = ?",
                [$id]
            );

            $relatedRecords[] = [
                'type' => 'mtm',
                'table' => $mtm['relatedTable'],
                'displayName' => $relatedConfig->displayName(),
                'labelColumn' => $labelCol,
                'pkName' => $relatedPk ? $relatedPk->name : 'rowid',
                'rows' => $rows,
            ];
        }

        return Response::html(View::render('edit', [
            'title' => 'Edit ' . $tableConfig->displayName(),
            'table' => $table,
            'tableConfig' => $tableConfig,
            'fields' => $fields,
            'record' => $record,
            'isEdit' => true,
            'id' => $id,
            'pkName' => $pkName,
            'relationships' => $relationships,
            'relatedRecords' => $relatedRecords,
            'allTables' => $this->allTableSummaries(),
        ]));
    }

    public function store(Request $request, string $table): Response
    {
        $this->guardTable($table);
        $tableConfig = $this->tableConfig($table);
        $fields = $tableConfig->formFields();

        $columns = [];
        $values = [];

        foreach ($fields as $field) {
            if ($field->readonly) continue;
            $columns[] = $field->name;
            $values[] = $this->castValue($field, $request->post($field->name));
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $colNames = implode(', ', array_map(fn ($c) => "\"{$c}\"", $columns));

        $this->db->execute(
            "INSERT INTO \"{$table}\" ({$colNames}) VALUES ({$placeholders})",
            $values
        );

        $id = $this->db->lastInsertId();
        return Response::redirect("/cms/{$table}/{$id}");
    }

    public function update(Request $request, string $table, string $id): Response
    {
        $this->guardTable($table);
        $tableConfig = $this->tableConfig($table);
        $schemaTable = $this->inspector->table($table);
        $pk = $schemaTable->primaryKeyColumn();
        $pkName = $pk ? $pk->name : 'rowid';

        $fields = $tableConfig->formFields();

        $setClauses = [];
        $values = [];

        foreach ($fields as $field) {
            if ($field->readonly) continue;
            $setClauses[] = "\"{$field->name}\" = ?";
            $values[] = $this->castValue($field, $request->post($field->name));
        }

        // Update updated_at if column exists
        if ($schemaTable->column('updated_at')) {
            $setClauses[] = '"updated_at" = CURRENT_TIMESTAMP';
        }

        $values[] = $id;

        $this->db->execute(
            "UPDATE \"{$table}\" SET " . implode(', ', $setClauses) . " WHERE \"{$pkName}\" = ?",
            $values
        );

        return Response::redirect("/cms/{$table}/{$id}");
    }

    public function delete(Request $request, string $table, string $id): Response
    {
        $this->guardTable($table);
        $schemaTable = $this->inspector->table($table);
        $pk = $schemaTable->primaryKeyColumn();
        $pkName = $pk ? $pk->name : 'rowid';

        $this->db->execute(
            "DELETE FROM \"{$table}\" WHERE \"{$pkName}\" = ?",
            [$id]
        );

        return Response::redirect("/cms/{$table}");
    }

    // --- Helpers ---

    private function visibleTableNames(): array
    {
        $hidden = App::getInstance()->config('cms.hidden_tables', []);
        $names = $this->inspector->tableNames();

        return array_values(array_filter($names, fn ($n) => !in_array($n, $hidden)));
    }

    private function guardTable(string $table): void
    {
        $visible = $this->visibleTableNames();
        if (!in_array($table, $visible)) {
            throw new \RuntimeException("Table [{$table}] is not accessible.");
        }
    }

    private function tableConfig(string $table): TableConfig
    {
        $schemaTable = $this->inspector->table($table);
        $config = App::getInstance()->config("cms.tables.{$table}", []);
        return new TableConfig($schemaTable, $config, $this->labelResolver);
    }

    /** @return Field[] */
    private function populateFkOptions(array $fields, \Sauerkraut\Database\Schema\Table $schemaTable): array
    {
        $result = [];
        foreach ($fields as $field) {
            $fk = $schemaTable->foreignKeyFor($field->name);
            if ($fk && $field->type === 'select' && empty($field->options)) {
                $relatedTable = $this->inspector->table($fk->referencesTable);
                $labelCol = $this->labelResolver->labelColumn($relatedTable);
                $relatedPk = $relatedTable->primaryKeyColumn();

                if ($relatedPk) {
                    $rows = $this->db->queryAll(
                        "SELECT \"{$relatedPk->name}\", \"{$labelCol}\" FROM \"{$fk->referencesTable}\" ORDER BY \"{$labelCol}\""
                    );

                    $options = array_map(
                        fn ($row) => $row[$relatedPk->name] . ':' . $row[$labelCol],
                        $rows
                    );

                    $field = new Field(
                        name: $field->name,
                        label: $field->label,
                        type: 'select',
                        required: $field->required,
                        default: $field->default,
                        options: $options,
                        readonly: $field->readonly,
                    );
                }
            }
            $result[] = $field;
        }
        return $result;
    }

    private function allTableSummaries(): array
    {
        $tableNames = $this->visibleTableNames();
        $summaries = [];
        foreach ($tableNames as $name) {
            $tableConfig = $this->tableConfig($name);
            $summaries[] = [
                'name' => $name,
                'displayName' => $tableConfig->displayName(),
            ];
        }
        return $summaries;
    }

    private function castValue(Field $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $field->required ? '' : null;
        }

        return match ($field->type) {
            'number' => is_numeric($value) ? $value + 0 : 0,
            'boolean' => $value ? 1 : 0,
            default => (string) $value,
        };
    }
}
