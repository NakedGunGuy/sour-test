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
    protected Connection $db;
    protected Inspector $inspector;
    protected LabelResolver $labelResolver;
    protected RelationshipDetector $relationshipDetector;

    /** @var array<string, FieldType> Resolved field type instances */
    private static array $fieldTypeInstances = [];

    public function __construct()
    {
        $app = App::getInstance();
        $this->db = $app->db();
        $this->inspector = $app->make(Inspector::class);
        $this->labelResolver = new LabelResolver($this->db);
        $this->relationshipDetector = new RelationshipDetector();
    }

    /**
     * Resolve the controller for a given table.
     * Returns a custom controller if configured, otherwise $this.
     */
    public static function forTable(string $table): self
    {
        $controllerClass = App::getInstance()->config("cms.tables.{$table}.controller");

        if ($controllerClass && class_exists($controllerClass)) {
            $controller = new $controllerClass();
            if (!$controller instanceof self) {
                throw new \RuntimeException("Controller [{$controllerClass}] must extend CmsController.");
            }
            return $controller;
        }

        return new static();
    }

    // --- Route handlers ---

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

        $rows = $this->listQuery($table, $pkName, $perPage, $offset);

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

        // Fetch MTM related records + all available options
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

            $allOptions = $this->db->queryAll(
                "SELECT * FROM \"{$mtm['relatedTable']}\" ORDER BY \"{$labelCol}\""
            );

            $linkedIds = array_column($rows, $relatedPk ? $relatedPk->name : 'rowid');

            $relatedRecords[] = [
                'type' => 'mtm',
                'table' => $mtm['relatedTable'],
                'junctionTable' => $mtm['junctionTable'],
                'localFk' => $mtm['localFk']->column,
                'remoteFk' => $mtm['remoteFk']->column,
                'displayName' => $relatedConfig->displayName(),
                'labelColumn' => $labelCol,
                'pkName' => $relatedPk ? $relatedPk->name : 'rowid',
                'rows' => $rows,
                'allOptions' => $allOptions,
                'linkedIds' => $linkedIds,
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

        $data = array_combine($columns, $values);
        $data = $this->beforeStore($table, $data, $request);

        $columns = array_keys($data);
        $values = array_values($data);

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $colNames = implode(', ', array_map(fn ($c) => "\"{$c}\"", $columns));

        $this->db->execute(
            "INSERT INTO \"{$table}\" ({$colNames}) VALUES ({$placeholders})",
            $values
        );

        $id = $this->db->lastInsertId();

        $this->syncMtmRelationships($table, $id, $request);
        $this->afterStore($table, $id, $data, $request);

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

        $columns = [];
        $values = [];

        foreach ($fields as $field) {
            if ($field->readonly) continue;
            $columns[] = $field->name;
            $values[] = $this->castValue($field, $request->post($field->name));
        }

        $data = array_combine($columns, $values);
        $data = $this->beforeUpdate($table, $id, $data, $request);

        $setClauses = [];
        $values = [];
        foreach ($data as $col => $val) {
            $setClauses[] = "\"{$col}\" = ?";
            $values[] = $val;
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

        $this->syncMtmRelationships($table, $id, $request);
        $this->afterUpdate($table, $id, $data, $request);

        return Response::redirect("/cms/{$table}/{$id}");
    }

    public function delete(Request $request, string $table, string $id): Response
    {
        $this->guardTable($table);
        $schemaTable = $this->inspector->table($table);
        $pk = $schemaTable->primaryKeyColumn();
        $pkName = $pk ? $pk->name : 'rowid';

        $this->beforeDelete($table, $id, $request);

        $this->db->execute(
            "DELETE FROM \"{$table}\" WHERE \"{$pkName}\" = ?",
            [$id]
        );

        $this->afterDelete($table, $id, $request);

        return Response::redirect("/cms/{$table}");
    }

    // --- Lifecycle hooks (override in subclasses) ---

    protected function beforeStore(string $table, array $data, Request $request): array
    {
        return $data;
    }

    protected function afterStore(string $table, string $id, array $data, Request $request): void
    {
    }

    protected function beforeUpdate(string $table, string $id, array $data, Request $request): array
    {
        return $data;
    }

    protected function afterUpdate(string $table, string $id, array $data, Request $request): void
    {
    }

    protected function beforeDelete(string $table, string $id, Request $request): void
    {
    }

    protected function afterDelete(string $table, string $id, Request $request): void
    {
    }

    /**
     * Sync many-to-many relationships from submitted checkbox data.
     */
    protected function syncMtmRelationships(string $table, string $id, Request $request): void
    {
        $schemaTable = $this->inspector->table($table);
        $allTables = $this->inspector->allTables();
        $relationships = $this->relationshipDetector->detect($schemaTable, $allTables);

        foreach ($relationships['mtm'] as $mtm) {
            $inputName = 'mtm_' . $mtm['junctionTable'];

            // Only sync if the field was present in the form (sentinel hidden input)
            if ($request->post($inputName . '_present') === null) {
                continue;
            }

            $submittedIds = $request->post($inputName) ?? [];
            if (!is_array($submittedIds)) {
                $submittedIds = [$submittedIds];
            }

            $junction = $mtm['junctionTable'];
            $localCol = $mtm['localFk']->column;
            $remoteCol = $mtm['remoteFk']->column;

            // Delete existing links
            $this->db->execute(
                "DELETE FROM \"{$junction}\" WHERE \"{$localCol}\" = ?",
                [$id]
            );

            // Insert new links
            foreach ($submittedIds as $remoteId) {
                $this->db->execute(
                    "INSERT INTO \"{$junction}\" (\"{$localCol}\", \"{$remoteCol}\") VALUES (?, ?)",
                    [$id, $remoteId]
                );
            }
        }
    }

    /**
     * Override to customize the list query for a table.
     */
    protected function listQuery(string $table, string $pkName, int $perPage, int $offset): array
    {
        return $this->db->queryAll(
            "SELECT * FROM \"{$table}\" ORDER BY \"{$pkName}\" DESC LIMIT {$perPage} OFFSET {$offset}"
        );
    }

    // --- Field type resolution ---

    public static function resolveFieldType(string $type): ?FieldType
    {
        if (isset(self::$fieldTypeInstances[$type])) {
            return self::$fieldTypeInstances[$type];
        }

        $class = App::getInstance()->config("cms.field_types.{$type}");

        if (!$class || !class_exists($class)) {
            return null;
        }

        $instance = new $class();
        if (!$instance instanceof FieldType) {
            throw new \RuntimeException("Field type [{$class}] must implement FieldType.");
        }

        self::$fieldTypeInstances[$type] = $instance;
        return $instance;
    }

    // --- Helpers ---

    protected function visibleTableNames(): array
    {
        $hidden = App::getInstance()->config('cms.hidden_tables', []);
        $names = $this->inspector->tableNames();

        return array_values(array_filter($names, fn ($n) => !in_array($n, $hidden)));
    }

    protected function guardTable(string $table): void
    {
        $visible = $this->visibleTableNames();
        if (!in_array($table, $visible)) {
            throw new \RuntimeException("Table [{$table}] is not accessible.");
        }
    }

    protected function tableConfig(string $table): TableConfig
    {
        $schemaTable = $this->inspector->table($table);
        $config = App::getInstance()->config("cms.tables.{$table}", []);
        return new TableConfig($schemaTable, $config, $this->labelResolver);
    }

    /** @return Field[] */
    protected function populateFkOptions(array $fields, \Sauerkraut\Database\Schema\Table $schemaTable): array
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

    protected function allTableSummaries(): array
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

    protected function castValue(Field $field, mixed $value): mixed
    {
        // Check for custom field type casting first
        $fieldType = self::resolveFieldType($field->type);
        if ($fieldType) {
            return $fieldType->cast($value, $field);
        }

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
