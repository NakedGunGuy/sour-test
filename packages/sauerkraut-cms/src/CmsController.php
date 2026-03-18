<?php

declare(strict_types=1);

namespace Sauerkraut\CMS;

use App\Controllers\Controller;
use Sauerkraut\App;
use Sauerkraut\Database\Connection;
use Sauerkraut\Database\Schema\DetectedRelationships;
use Sauerkraut\Database\Schema\Inspector;
use Sauerkraut\Database\Schema\LabelResolver;
use Sauerkraut\Database\Schema\ManyToManyRelation;
use Sauerkraut\Database\Schema\OneToManyRelation;
use Sauerkraut\Database\Schema\RelationshipDetector;
use Sauerkraut\Database\Schema\Table;
use Sauerkraut\Request;
use Sauerkraut\Response;
use Sauerkraut\View\View;

class CmsController extends Controller
{
    private const PER_PAGE = 25;

    protected Connection $db;
    protected Inspector $inspector;
    protected LabelResolver $labelResolver;
    protected RelationshipDetector $relationshipDetector;

    /** @var array<string, FieldType> Resolved field type instances */
    private static array $fieldTypeInstances = [];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->db = $app->db();
        $this->inspector = $app->make(Inspector::class);
        $this->labelResolver = new LabelResolver($this->db);
        $this->relationshipDetector = new RelationshipDetector();
    }

    /**
     * Resolve the controller for a given table.
     * Returns a custom controller if configured, otherwise a new instance.
     */
    public static function forTable(App $app, string $table): self
    {
        $controllerClass = $app->config("cms.tables.{$table}.controller");

        if ($controllerClass && class_exists($controllerClass)) {
            $controller = new $controllerClass($app);
            if (!$controller instanceof self) {
                throw new \RuntimeException("Controller [{$controllerClass}] must extend CmsController.");
            }
            return $controller;
        }

        return new static($app);
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
        $pkName = $schemaTable->primaryKeyName();

        $page = max(1, (int) ($request->query('page') ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $totalRow = $this->db->queryOne("SELECT COUNT(*) as cnt FROM \"{$table}\"");
        $total = $totalRow['cnt'] ?? 0;
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        $rows = $this->listQuery($table, $pkName, self::PER_PAGE, $offset);
        $mtoMap = $this->resolveMtoLabels($schemaTable, $rows);

        return Response::html(View::render('list', [
            'title' => $tableConfig->displayName(),
            'table' => $table,
            'tableConfig' => $tableConfig,
            'columns' => $tableConfig->listColumns(),
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
        $fields = $this->populateFkOptions($tableConfig->formFields(), $schemaTable);

        return Response::html(View::render('edit', [
            'title' => 'Create ' . $tableConfig->displayName(),
            'table' => $table,
            'tableConfig' => $tableConfig,
            'fields' => $fields,
            'record' => null,
            'isEdit' => false,
            'relationships' => new DetectedRelationships([], [], []),
            'relatedRecords' => [],
            'allTables' => $this->allTableSummaries(),
        ]));
    }

    public function edit(Request $request, string $table, string $id): Response
    {
        $this->guardTable($table);
        $tableConfig = $this->tableConfig($table);
        $schemaTable = $this->inspector->table($table);
        $pkName = $schemaTable->primaryKeyName();

        $record = $this->db->queryOne(
            "SELECT * FROM \"{$table}\" WHERE \"{$pkName}\" = ?",
            [$id]
        );

        if (!$record) {
            return Response::html('<h1>404 Not Found</h1>', 404);
        }

        $fields = $this->populateFkOptions($tableConfig->formFields(), $schemaTable);
        $relationships = $this->detectRelationships($schemaTable);
        $relatedRecords = $this->loadRelatedRecords($relationships, $id);

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

        $data = $this->extractFieldData($tableConfig->formFields(), $request);
        $data = $this->beforeStore($table, $data, $request);

        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $colNames = implode(', ', array_map(fn ($column) => "\"{$column}\"", $columns));

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
        $pkName = $schemaTable->primaryKeyName();

        $data = $this->extractFieldData($tableConfig->formFields(), $request);
        $data = $this->beforeUpdate($table, $id, $data, $request);

        $setClauses = [];
        $values = [];
        foreach ($data as $col => $val) {
            $setClauses[] = "\"{$col}\" = ?";
            $values[] = $val;
        }

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
        $pkName = $schemaTable->primaryKeyName();

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

    // --- Data extraction ---

    /**
     * Extract and cast field values from the request.
     * Shared between store() and update().
     *
     * @param Field[] $fields
     * @return array<string, mixed>
     */
    private function extractFieldData(array $fields, Request $request): array
    {
        $data = [];

        foreach ($fields as $field) {
            if ($field->readonly) {
                continue;
            }
            $data[$field->name] = $this->castValue($field, $request->post($field->name));
        }

        return $data;
    }

    // --- Relationship handling ---

    private function detectRelationships(Table $schemaTable): DetectedRelationships
    {
        $allTables = $this->inspector->allTables();
        return $this->relationshipDetector->detect($schemaTable, $allTables);
    }

    /**
     * Resolve MTO labels — replace FK IDs with related record labels for the list view.
     */
    private function resolveMtoLabels(Table $schemaTable, array $rows): array
    {
        $relationships = $this->detectRelationships($schemaTable);
        $mtoMap = [];

        foreach ($relationships->manyToOne as $fk) {
            $relatedTable = $this->inspector->table($fk->referencesTable);
            $labelCol = $this->labelResolver->labelColumn($relatedTable);
            $refPk = $relatedTable->primaryKeyColumn();

            if (!$refPk) {
                continue;
            }

            $ids = array_filter(array_unique(array_column($rows, $fk->column)));
            if (empty($ids)) {
                continue;
            }

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

        return $mtoMap;
    }

    /**
     * Load OTM and MTM related records for the edit view.
     */
    private function loadRelatedRecords(DetectedRelationships $relationships, string $id): array
    {
        $relatedRecords = [];

        foreach ($relationships->oneToMany as $otm) {
            $relatedRecords[] = $this->loadOneToManyRecords($otm, $id);
        }

        foreach ($relationships->manyToMany as $mtm) {
            $relatedRecords[] = $this->loadManyToManyRecords($mtm, $id);
        }

        return $relatedRecords;
    }

    private function loadOneToManyRecords(OneToManyRelation $otm, string $id): array
    {
        $relatedTable = $this->inspector->table($otm->table);
        $relatedConfig = $this->tableConfig($otm->table);

        $rows = $this->db->queryAll(
            "SELECT * FROM \"{$otm->table}\" WHERE \"{$otm->foreignKey->column}\" = ?",
            [$id]
        );

        return [
            'type' => 'otm',
            'table' => $otm->table,
            'displayName' => $relatedConfig->displayName(),
            'labelColumn' => $this->labelResolver->labelColumn($relatedTable),
            'pkName' => $relatedTable->primaryKeyName(),
            'rows' => $rows,
        ];
    }

    private function loadManyToManyRecords(ManyToManyRelation $mtm, string $id): array
    {
        $relatedTable = $this->inspector->table($mtm->relatedTable);
        $relatedConfig = $this->tableConfig($mtm->relatedTable);
        $labelCol = $this->labelResolver->labelColumn($relatedTable);
        $pkName = $relatedTable->primaryKeyName();

        $rows = $this->db->queryAll(
            "SELECT r.* FROM \"{$mtm->relatedTable}\" r
             JOIN \"{$mtm->junctionTable}\" j ON j.\"{$mtm->remoteFk->column}\" = r.\"{$mtm->remoteFk->referencesColumn}\"
             WHERE j.\"{$mtm->localFk->column}\" = ?",
            [$id]
        );

        $allOptions = $this->db->queryAll(
            "SELECT * FROM \"{$mtm->relatedTable}\" ORDER BY \"{$labelCol}\""
        );

        return [
            'type' => 'mtm',
            'table' => $mtm->relatedTable,
            'junctionTable' => $mtm->junctionTable,
            'localFk' => $mtm->localFk->column,
            'remoteFk' => $mtm->remoteFk->column,
            'displayName' => $relatedConfig->displayName(),
            'labelColumn' => $labelCol,
            'pkName' => $pkName,
            'rows' => $rows,
            'allOptions' => $allOptions,
            'linkedIds' => array_column($rows, $pkName),
        ];
    }

    /**
     * Sync many-to-many relationships from submitted checkbox data.
     */
    protected function syncMtmRelationships(string $table, string $id, Request $request): void
    {
        $schemaTable = $this->inspector->table($table);
        $relationships = $this->detectRelationships($schemaTable);

        foreach ($relationships->manyToMany as $mtm) {
            $inputName = 'mtm_' . $mtm->junctionTable;

            if ($request->post($inputName . '_present') === null) {
                continue;
            }

            $submittedIds = $request->post($inputName) ?? [];
            if (!is_array($submittedIds)) {
                $submittedIds = [$submittedIds];
            }

            $this->db->execute(
                "DELETE FROM \"{$mtm->junctionTable}\" WHERE \"{$mtm->localFk->column}\" = ?",
                [$id]
            );

            foreach ($submittedIds as $remoteId) {
                $this->db->execute(
                    "INSERT INTO \"{$mtm->junctionTable}\" (\"{$mtm->localFk->column}\", \"{$mtm->remoteFk->column}\") VALUES (?, ?)",
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

    protected function resolveFieldType(string $type): ?FieldType
    {
        if (isset(self::$fieldTypeInstances[$type])) {
            return self::$fieldTypeInstances[$type];
        }

        $class = $this->app->config("cms.field_types.{$type}");

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
        $hidden = $this->app->config('cms.hidden_tables', []);
        $names = $this->inspector->tableNames();

        return array_values(array_filter($names, fn ($tableName) => !in_array($tableName, $hidden)));
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
        $config = $this->app->config("cms.tables.{$table}", []);
        return new TableConfig($schemaTable, $config, $this->labelResolver);
    }

    /** @return Field[] */
    protected function populateFkOptions(array $fields, Table $schemaTable): array
    {
        $result = [];

        foreach ($fields as $field) {
            $fk = $schemaTable->foreignKeyFor($field->name);

            if (!$fk || $field->type !== 'select' || !empty($field->options)) {
                $result[] = $field;
                continue;
            }

            $relatedTable = $this->inspector->table($fk->referencesTable);
            $labelCol = $this->labelResolver->labelColumn($relatedTable);
            $relatedPk = $relatedTable->primaryKeyColumn();

            if (!$relatedPk) {
                $result[] = $field;
                continue;
            }

            $rows = $this->db->queryAll(
                "SELECT \"{$relatedPk->name}\", \"{$labelCol}\" FROM \"{$fk->referencesTable}\" ORDER BY \"{$labelCol}\""
            );

            $options = array_map(
                fn ($row) => $row[$relatedPk->name] . ':' . $row[$labelCol],
                $rows
            );

            $result[] = new Field(
                name: $field->name,
                label: $field->label,
                type: 'select',
                required: $field->required,
                default: $field->default,
                options: $options,
                readonly: $field->readonly,
            );
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
        $fieldType = $this->resolveFieldType($field->type);
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
