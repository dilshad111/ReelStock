<template>
    <section class="production-config">
        <div class="config-header">
            <div>
                <span class="config-eyebrow">Production Menu</span>
                <h1><i class="bi bi-sliders2-vertical me-2"></i>Configuration</h1>
                <p>Industrial production master data, machine assignments, and speed optimization logic registry.</p>
            </div>
            <el-button type="primary" class="config-primary-action" @click="refreshActiveTab">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </el-button>
        </div>

        <el-tabs v-model="activeTab" class="config-tabs" @tab-change="handleTabChange">
            <el-tab-pane label="Printing Colors" name="printing-colors">
                <master-panel
                    title="Printing Colors"
                    subtitle="Ink and color definitions used in production job cards."
                    :columns="masterTabs.printingColors.columns"
                    :rows="printingColors.rows"
                    :pagination="printingColors.pagination"
                    :filters="printingColors.filters"
                    :loading="printingColors.loading"
                    :empty-text="'No printing colors found.'"
                    @search="fetchPrintingColors"
                    @sort="payload => handleSort(printingColors, payload, fetchPrintingColors)"
                    @page="page => handlePage(printingColors, page, fetchPrintingColors)"
                    @create="openMasterForm('printingColors')"
                    @edit="row => openMasterForm('printingColors', row)"
                    @delete="row => deleteRecord('printingColors', row)"
                />
            </el-tab-pane>

            <el-tab-pane label="Departments" name="departments">
                <master-panel
                    title="Departments"
                    subtitle="Production departments used for machine grouping and accountability."
                    :columns="masterTabs.departments.columns"
                    :rows="departments.rows"
                    :pagination="departments.pagination"
                    :filters="departments.filters"
                    :loading="departments.loading"
                    :empty-text="'No departments found.'"
                    @search="fetchDepartments"
                    @sort="payload => handleSort(departments, payload, fetchDepartments)"
                    @page="page => handlePage(departments, page, fetchDepartments)"
                    @create="openMasterForm('departments')"
                    @edit="row => openMasterForm('departments', row)"
                    @delete="row => deleteRecord('departments', row)"
                />
            </el-tab-pane>

            <el-tab-pane label="Machine Names" name="machines">
                <master-panel
                    title="Machine Names"
                    subtitle="Machine registry with department mapping and optional speed boundaries."
                    :columns="masterTabs.machines.columns"
                    :rows="machines.rows"
                    :pagination="machines.pagination"
                    :filters="machines.filters"
                    :loading="machines.loading"
                    :empty-text="'No machines found.'"
                    :department-options="lookups.departments"
                    show-department-filter
                    @search="fetchMachines"
                    @sort="payload => handleSort(machines, payload, fetchMachines)"
                    @page="page => handlePage(machines, page, fetchMachines)"
                    @create="openMasterForm('machines')"
                    @edit="row => openMasterForm('machines', row)"
                    @delete="row => deleteRecord('machines', row)"
                />
            </el-tab-pane>

            <el-tab-pane label="Machine Operators" name="operators">
                <master-panel
                    title="Machine Operators"
                    subtitle="Operators assigned to machine records. One machine can have multiple operators."
                    :columns="masterTabs.operators.columns"
                    :rows="operators.rows"
                    :pagination="operators.pagination"
                    :filters="operators.filters"
                    :loading="operators.loading"
                    :empty-text="'No machine operators found.'"
                    :machine-options="lookups.machines"
                    show-machine-filter
                    @search="fetchOperators"
                    @sort="payload => handleSort(operators, payload, fetchOperators)"
                    @page="page => handlePage(operators, page, fetchOperators)"
                    @create="openMasterForm('operators')"
                    @edit="row => openMasterForm('operators', row)"
                    @delete="row => deleteRecord('operators', row)"
                />
            </el-tab-pane>

            <el-tab-pane label="Machine Optimization Rules" name="optimization-rules">
                <div class="rules-layout">
                    <section class="logic-form-panel">
                        <div class="panel-title-row">
                            <div>
                                <span class="config-eyebrow">Machine Optimization Rules</span>
                                <h2>Define New Logic Rule</h2>
                            </div>
                            <el-tag :type="ruleForm.is_active ? 'success' : 'info'" effect="dark">
                                {{ ruleForm.is_active ? 'Rule Active' : 'Disabled' }}
                            </el-tag>
                        </div>

                        <el-form ref="ruleFormRef" :model="ruleForm" :rules="ruleRules" label-position="top" class="config-form">
                            <el-form-item label="Rule Identifier" prop="parameter_name">
                                <el-input v-model="ruleForm.parameter_name" maxlength="255" show-word-limit placeholder="e.g. Multi-Color Precision Adjustment" />
                            </el-form-item>

                            <div class="form-grid-2">
                                <el-form-item label="Condition Parameter" prop="condition_field">
                                    <el-select v-model="ruleForm.condition_field" class="w-100" placeholder="Select parameter">
                                        <el-option v-for="option in conditionFieldOptions" :key="option.value" :label="option.label" :value="option.value" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="Operator" prop="operator">
                                    <el-select v-model="ruleForm.operator" class="w-100" placeholder="Select operator">
                                        <el-option v-for="option in operatorOptions" :key="option.value" :label="option.label" :value="option.value" />
                                    </el-select>
                                </el-form-item>
                            </div>

                            <el-form-item label="Condition Value" prop="condition_value">
                                <el-input v-model="ruleForm.condition_value" placeholder="Val" />
                            </el-form-item>

                            <div class="form-grid-2">
                                <el-form-item label="Adjustment Strategy" prop="adjustment_type">
                                    <el-select v-model="ruleForm.adjustment_type" class="w-100" placeholder="Select strategy">
                                        <el-option label="Percentage Reduction (%)" value="percentage_reduction" />
                                        <el-option label="Absolute Unit Reduction" value="absolute_reduction" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="Impact Magnitude" prop="adjustment_value">
                                    <el-input-number v-model="ruleForm.adjustment_value" :min="0.1" :step="0.5" :controls="false" class="w-100" />
                                    <div class="form-helper">Magnitude of speed reduction applied when rule is triggered.</div>
                                </el-form-item>
                            </div>

                            <el-form-item label="Rule Status" prop="is_active">
                                <div class="rule-status-toggle">
                                    <el-switch v-model="ruleForm.is_active" />
                                    <span>{{ ruleForm.is_active ? 'Rule Active' : 'Disabled' }}</span>
                                </div>
                            </el-form-item>

                            <div class="form-actions">
                                <el-button @click="resetRuleForm">Clear</el-button>
                                <el-button type="primary" class="config-primary-action" :loading="rules.submitting" @click="submitRule">
                                    <i class="bi bi-rocket-takeoff me-1"></i> Deploy Optimization Rule
                                </el-button>
                            </div>
                        </el-form>
                    </section>

                    <section class="logic-table-panel">
                        <div class="panel-title-row">
                            <div>
                                <span class="config-eyebrow">Active Logic Registry</span>
                                <h2>Machine Optimization Rules</h2>
                            </div>
                            <el-input v-model="rules.filters.search" class="rules-search" placeholder="Search rules..." clearable @input="fetchRules">
                                <template #prefix><i class="bi bi-search"></i></template>
                            </el-input>
                        </div>

                        <el-table :data="rules.rows" v-loading="rules.loading" class="config-table" border @sort-change="payload => handleSort(rules, payload, fetchRules)">
                            <el-table-column label="Logic Definition" min-width="280" sortable prop="parameter_name">
                                <template #default="{ row }">
                                    <div class="logic-name">{{ row.parameter_name }}</div>
                                    <code>IF {{ row.condition_field }} {{ row.operator }} {{ row.condition_value }}</code>
                                </template>
                            </el-table-column>
                            <el-table-column label="Speed Refinement" width="170" align="center" sortable prop="adjustment_value">
                                <template #default="{ row }">
                                    <div class="impact-value">{{ formatImpact(row) }}</div>
                                    <span class="impact-label">Reduction Impact</span>
                                </template>
                            </el-table-column>
                            <el-table-column label="State" width="120" align="center">
                                <template #default="{ row }">
                                    <el-tag :type="row.is_active ? 'success' : 'info'" effect="dark">
                                        {{ row.is_active ? 'Active' : 'Disabled' }}
                                    </el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column label="Actions" width="120" align="center">
                                <template #default="{ row }">
                                    <el-button-group>
                                        <el-button size="small" type="primary" plain @click="editRule(row)"><i class="bi bi-pencil"></i></el-button>
                                        <el-button size="small" type="danger" plain @click="deleteRule(row)"><i class="bi bi-trash"></i></el-button>
                                    </el-button-group>
                                </template>
                            </el-table-column>
                            <template #empty>
                                <div class="empty-state">No active optimization logic found in the global registry.</div>
                            </template>
                        </el-table>

                        <div class="pagination-row">
                            <el-pagination
                                v-model:current-page="rules.pagination.current_page"
                                :page-size="rules.pagination.per_page"
                                layout="prev, pager, next"
                                :total="rules.pagination.total"
                                @current-change="page => handlePage(rules, page, fetchRules)"
                            />
                        </div>
                    </section>
                </div>
            </el-tab-pane>
        </el-tabs>

        <el-dialog v-model="masterDialog.visible" :title="masterDialogTitle" width="560px" destroy-on-close>
            <el-form ref="masterFormRef" :model="masterForm" :rules="masterRules" label-position="top" class="config-form">
                <template v-if="masterDialog.type === 'printingColors'">
                    <el-form-item label="Ink Code" prop="ink_code">
                        <el-input v-model="masterForm.ink_code" placeholder="e.g. CYAN-01" />
                    </el-form-item>
                    <el-form-item label="Ink Name" prop="ink_name">
                        <el-input v-model="masterForm.ink_name" placeholder="e.g. Process Cyan" />
                    </el-form-item>
                </template>

                <template v-if="masterDialog.type === 'departments'">
                    <el-form-item label="Department Name" prop="department_name">
                        <el-input v-model="masterForm.department_name" placeholder="e.g. Corrugation" />
                    </el-form-item>
                </template>

                <template v-if="masterDialog.type === 'machines'">
                    <el-form-item label="Machine Name" prop="machine_name">
                        <el-input v-model="masterForm.machine_name" placeholder="e.g. Flexo Slotter 01" />
                    </el-form-item>
                    <el-form-item label="Department Selection" prop="department_id">
                        <el-select v-model="masterForm.department_id" class="w-100" filterable placeholder="Select department">
                            <el-option v-for="department in lookups.departments" :key="department.id" :label="department.department_name" :value="department.id" />
                        </el-select>
                    </el-form-item>
                    <div class="form-grid-2">
                        <el-form-item label="Base Speed">
                            <el-input-number v-model="masterForm.base_speed" :min="0" :controls="false" class="w-100" />
                        </el-form-item>
                        <el-form-item label="Minimum Speed">
                            <el-input-number v-model="masterForm.minimum_speed" :min="0" :controls="false" class="w-100" />
                        </el-form-item>
                    </div>
                </template>

                <template v-if="masterDialog.type === 'operators'">
                    <el-form-item label="Operator Name" prop="operator_name">
                        <el-input v-model="masterForm.operator_name" placeholder="e.g. Muhammad Ali" />
                    </el-form-item>
                    <el-form-item label="Assigned Machine" prop="machine_id">
                        <el-select v-model="masterForm.machine_id" class="w-100" filterable placeholder="Select machine">
                            <el-option v-for="machine in lookups.machines" :key="machine.id" :label="machineLabel(machine)" :value="machine.id" />
                        </el-select>
                    </el-form-item>
                </template>
            </el-form>

            <template #footer>
                <el-button @click="masterDialog.visible = false">Cancel</el-button>
                <el-button type="primary" :loading="masterDialog.submitting" @click="submitMasterForm">
                    {{ masterDialog.id ? 'Update' : 'Create' }}
                </el-button>
            </template>
        </el-dialog>
    </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { ElButton, ElButtonGroup, ElInput, ElMessage, ElMessageBox, ElOption, ElPagination, ElSelect, ElTable, ElTableColumn } from 'element-plus';

const activeTab = ref('printing-colors');
const masterFormRef = ref(null);
const ruleFormRef = ref(null);

const createStore = (perPage = 15) => reactive({
    rows: [],
    loading: false,
    submitting: false,
    filters: {
        search: '',
        department_id: '',
        machine_id: '',
        sort_by: '',
        sort_direction: '',
    },
    pagination: {
        current_page: 1,
        per_page: perPage,
        total: 0,
    },
});

const printingColors = createStore();
const departments = createStore();
const machines = createStore();
const operators = createStore();
const rules = createStore(10);

const lookups = reactive({
    departments: [],
    machines: [],
    printing_colors: [],
});

const masterDialog = reactive({
    visible: false,
    type: '',
    id: null,
    submitting: false,
});

const masterForm = reactive({});

const ruleForm = reactive(defaultRuleForm());

const conditionFieldOptions = [
    { label: 'Print Colors', value: 'print_colors' },
    { label: 'Ink Coverage (%)', value: 'ink_coverage' },
    { label: 'Slot Type', value: 'slot_type' },
    { label: 'Job Type', value: 'job_type' },
    { label: 'Quantity', value: 'quantity' },
    { label: 'Priority Level', value: 'quality_priority' },
];

const operatorOptions = [
    { label: 'Greater Than (>)', value: '>' },
    { label: 'Greater Than or Equal (>=)', value: '>=' },
    { label: 'Equals (=)', value: '=' },
    { label: 'Less Than (<)', value: '<' },
    { label: 'Less Than or Equal (<=)', value: '<=' },
];

const masterTabs = {
    printingColors: {
        endpoint: '/api/production-config/printing-colors',
        title: 'Printing Color',
        columns: [
            { prop: 'ink_code', label: 'Ink Code', sortable: true, code: true },
            { prop: 'ink_name', label: 'Ink Name', sortable: true },
        ],
    },
    departments: {
        endpoint: '/api/production-config/departments',
        title: 'Department',
        columns: [
            { prop: 'department_name', label: 'Department Name', sortable: true },
            { prop: 'machines_count', label: 'Machines', align: 'center' },
        ],
    },
    machines: {
        endpoint: '/api/production-config/machines',
        title: 'Machine',
        columns: [
            { prop: 'machine_name', label: 'Machine Name', sortable: true },
            { prop: 'department.department_name', label: 'Department' },
            { prop: 'base_speed', label: 'Base Speed', sortable: true, align: 'right' },
            { prop: 'minimum_speed', label: 'Minimum Speed', sortable: true, align: 'right' },
            { prop: 'operators_count', label: 'Operators', align: 'center' },
        ],
    },
    operators: {
        endpoint: '/api/production-config/operators',
        title: 'Machine Operator',
        columns: [
            { prop: 'operator_name', label: 'Operator Name', sortable: true },
            { prop: 'machine.machine_name', label: 'Assigned Machine' },
            { prop: 'machine.department.department_name', label: 'Department' },
        ],
    },
};

const ruleRules = {
    parameter_name: [{ required: true, message: 'Rule identifier is required', trigger: 'blur' }, { max: 255, message: 'Max 255 characters', trigger: 'blur' }],
    condition_field: [{ required: true, message: 'Select condition parameter', trigger: 'change' }],
    operator: [{ required: true, message: 'Select operator', trigger: 'change' }],
    condition_value: [{ required: true, message: 'Condition value is required', trigger: 'blur' }],
    adjustment_type: [{ required: true, message: 'Select adjustment strategy', trigger: 'change' }],
    adjustment_value: [{ required: true, message: 'Impact magnitude is required', trigger: 'blur' }],
};

const masterRules = computed(() => {
    if (masterDialog.type === 'printingColors') {
        return {
            ink_code: [{ required: true, message: 'Ink code is required', trigger: 'blur' }],
            ink_name: [{ required: true, message: 'Ink name is required', trigger: 'blur' }],
        };
    }
    if (masterDialog.type === 'departments') {
        return { department_name: [{ required: true, message: 'Department name is required', trigger: 'blur' }] };
    }
    if (masterDialog.type === 'machines') {
        return {
            machine_name: [{ required: true, message: 'Machine name is required', trigger: 'blur' }],
            department_id: [{ required: true, message: 'Select department', trigger: 'change' }],
        };
    }
    return {
        operator_name: [{ required: true, message: 'Operator name is required', trigger: 'blur' }],
        machine_id: [{ required: true, message: 'Select machine', trigger: 'change' }],
    };
});

const masterDialogTitle = computed(() => {
    const config = masterTabs[masterDialog.type];
    return `${masterDialog.id ? 'Edit' : 'Create'} ${config?.title || 'Record'}`;
});

function defaultRuleForm() {
    return {
        id: null,
        parameter_name: '',
        condition_field: '',
        operator: '',
        condition_value: '',
        adjustment_type: 'percentage_reduction',
        adjustment_value: 0.5,
        is_active: true,
    };
}

function paramsFor(store) {
    return {
        search: store.filters.search || undefined,
        department_id: store.filters.department_id || undefined,
        machine_id: store.filters.machine_id || undefined,
        sort_by: store.filters.sort_by || undefined,
        sort_direction: store.filters.sort_direction || undefined,
        page: store.pagination.current_page,
        per_page: store.pagination.per_page,
    };
}

async function fetchStore(store, endpoint) {
    store.loading = true;
    try {
        const { data } = await axios.get(endpoint, { params: paramsFor(store) });
        store.rows = data.data || [];
        store.pagination.total = data.total || 0;
        store.pagination.current_page = data.current_page || 1;
    } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Failed to load records');
    } finally {
        store.loading = false;
    }
}

const fetchPrintingColors = () => fetchStore(printingColors, masterTabs.printingColors.endpoint);
const fetchDepartments = async () => {
    await fetchStore(departments, masterTabs.departments.endpoint);
    await fetchLookups();
};
const fetchMachines = async () => {
    await fetchStore(machines, masterTabs.machines.endpoint);
    await fetchLookups();
};
const fetchOperators = () => fetchStore(operators, masterTabs.operators.endpoint);
const fetchRules = () => fetchStore(rules, '/api/production-config/optimization-rules');

async function fetchLookups() {
    try {
        const { data } = await axios.get('/api/production-config/lookups');
        lookups.departments = data.departments || [];
        lookups.machines = data.machines || [];
        lookups.printing_colors = data.printing_colors || [];
    } catch (error) {}
}

function handleTabChange() {
    refreshActiveTab();
}

function refreshActiveTab() {
    if (activeTab.value === 'printing-colors') fetchPrintingColors();
    if (activeTab.value === 'departments') fetchDepartments();
    if (activeTab.value === 'machines') fetchMachines();
    if (activeTab.value === 'operators') fetchOperators();
    if (activeTab.value === 'optimization-rules') fetchRules();
}

function handleSort(store, payload, fetcher) {
    store.filters.sort_by = payload.prop || '';
    store.filters.sort_direction = payload.order === 'descending' ? 'desc' : payload.order === 'ascending' ? 'asc' : '';
    fetcher();
}

function handlePage(store, page, fetcher) {
    store.pagination.current_page = page;
    fetcher();
}

function openMasterForm(type, row = null) {
    masterDialog.type = type;
    masterDialog.id = row?.id || null;
    Object.keys(masterForm).forEach(key => delete masterForm[key]);

    if (type === 'printingColors') {
        Object.assign(masterForm, { ink_code: row?.ink_code || '', ink_name: row?.ink_name || '' });
    } else if (type === 'departments') {
        Object.assign(masterForm, { department_name: row?.department_name || '' });
    } else if (type === 'machines') {
        Object.assign(masterForm, {
            machine_name: row?.machine_name || '',
            department_id: row?.department_id || null,
            base_speed: row?.base_speed ?? null,
            minimum_speed: row?.minimum_speed ?? null,
        });
    } else {
        Object.assign(masterForm, {
            operator_name: row?.operator_name || '',
            machine_id: row?.machine_id || null,
        });
    }

    masterDialog.visible = true;
}

async function submitMasterForm() {
    if (!masterFormRef.value) return;
    await masterFormRef.value.validate(async valid => {
        if (!valid) return;

        const config = masterTabs[masterDialog.type];
        masterDialog.submitting = true;

        try {
            if (masterDialog.id) {
                await axios.put(`${config.endpoint}/${masterDialog.id}`, masterForm);
                ElMessage.success('Record updated successfully');
            } else {
                await axios.post(config.endpoint, masterForm);
                ElMessage.success('Record created successfully');
            }
            masterDialog.visible = false;
            refreshActiveTab();
            fetchLookups();
        } catch (error) {
            ElMessage.error(error.response?.data?.message || 'Validation failed. Please check duplicate values.');
        } finally {
            masterDialog.submitting = false;
        }
    });
}

async function deleteRecord(type, row) {
    const config = masterTabs[type];
    try {
        await ElMessageBox.confirm('Delete this configuration record?', 'Confirm Delete', { type: 'warning' });
        await axios.delete(`${config.endpoint}/${row.id}`);
        ElMessage.success('Record deleted successfully');
        refreshActiveTab();
        fetchLookups();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || 'Unable to delete this record');
        }
    }
}

function resetRuleForm() {
    Object.assign(ruleForm, defaultRuleForm());
    ruleFormRef.value?.clearValidate();
}

function editRule(row) {
    Object.assign(ruleForm, {
        id: row.id,
        parameter_name: row.parameter_name,
        condition_field: row.condition_field,
        operator: row.operator,
        condition_value: row.condition_value,
        adjustment_type: row.adjustment_type,
        adjustment_value: Number(row.adjustment_value),
        is_active: Boolean(row.is_active),
    });
}

async function submitRule() {
    if (!ruleFormRef.value) return;
    await ruleFormRef.value.validate(async valid => {
        if (!valid) return;
        rules.submitting = true;

        try {
            if (ruleForm.id) {
                await axios.put(`/api/production-config/optimization-rules/${ruleForm.id}`, ruleForm);
                ElMessage.success('Optimization rule updated successfully');
            } else {
                await axios.post('/api/production-config/optimization-rules', ruleForm);
                ElMessage.success('Optimization rule deployed successfully');
            }
            resetRuleForm();
            fetchRules();
        } catch (error) {
            ElMessage.error(error.response?.data?.message || 'Failed to save optimization rule');
        } finally {
            rules.submitting = false;
        }
    });
}

async function deleteRule(row) {
    try {
        await ElMessageBox.confirm('Permanently remove this optimization logic?', 'Confirm Delete', { type: 'warning' });
        await axios.delete(`/api/production-config/optimization-rules/${row.id}`);
        ElMessage.success('Optimization rule removed');
        fetchRules();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || 'Unable to delete rule');
        }
    }
}

function formatImpact(row) {
    return row.adjustment_type === 'percentage_reduction'
        ? `-${Number(row.adjustment_value).toLocaleString()}%`
        : `-${Number(row.adjustment_value).toLocaleString()} Units`;
}

function machineLabel(machine) {
    return `${machine.machine_name}${machine.department ? ` - ${machine.department.department_name}` : ''}`;
}

function nestedValue(row, path) {
    return path.split('.').reduce((value, key) => value?.[key], row);
}

const MasterPanel = defineComponent({
    name: 'MasterPanel',
    props: {
        title: String,
        subtitle: String,
        columns: Array,
        rows: Array,
        pagination: Object,
        filters: Object,
        loading: Boolean,
        emptyText: String,
        departmentOptions: { type: Array, default: () => [] },
        machineOptions: { type: Array, default: () => [] },
        showDepartmentFilter: Boolean,
        showMachineFilter: Boolean,
    },
    emits: ['search', 'sort', 'page', 'create', 'edit', 'delete'],
    setup(props, { emit }) {
        return () => h('section', { class: 'master-panel' }, [
            h('div', { class: 'panel-title-row' }, [
                h('div', [
                    h('span', { class: 'config-eyebrow' }, props.title),
                    h('h2', props.title),
                    h('p', props.subtitle),
                ]),
                h(ElButton, { type: 'primary', onClick: () => emit('create') }, () => [h('i', { class: 'bi bi-plus-circle me-1' }), ' New Record']),
            ]),
            h('div', { class: 'master-filters' }, [
                h(ElInput, {
                    modelValue: props.filters.search,
                    'onUpdate:modelValue': value => props.filters.search = value,
                    placeholder: 'Search records...',
                    clearable: true,
                    onInput: () => emit('search'),
                }, { prefix: () => h('i', { class: 'bi bi-search' }) }),
                props.showDepartmentFilter ? h(ElSelect, {
                    modelValue: props.filters.department_id,
                    'onUpdate:modelValue': value => props.filters.department_id = value,
                    placeholder: 'All departments',
                    clearable: true,
                    filterable: true,
                    onChange: () => emit('search'),
                }, () => props.departmentOptions.map(department => h(ElOption, { key: department.id, label: department.department_name, value: department.id }))) : null,
                props.showMachineFilter ? h(ElSelect, {
                    modelValue: props.filters.machine_id,
                    'onUpdate:modelValue': value => props.filters.machine_id = value,
                    placeholder: 'All machines',
                    clearable: true,
                    filterable: true,
                    onChange: () => emit('search'),
                }, () => props.machineOptions.map(machine => h(ElOption, { key: machine.id, label: machineLabel(machine), value: machine.id }))) : null,
            ]),
            h(ElTable, {
                data: props.rows,
                loading: props.loading,
                border: true,
                class: 'config-table',
                onSortChange: payload => emit('sort', payload),
            }, () => [
                ...props.columns.map(column => h(ElTableColumn, {
                    prop: column.prop,
                    label: column.label,
                    sortable: column.sortable ? 'custom' : false,
                    align: column.align || 'left',
                    minWidth: column.minWidth || 150,
                }, {
                    default: ({ row }) => {
                        const value = nestedValue(row, column.prop);
                        if (column.code) {
                            return h('span', { class: 'code-badge' }, value || '-');
                        }
                        return h('span', value ?? '-');
                    },
                })),
                h(ElTableColumn, { label: 'Actions', width: 120, align: 'center' }, {
                    default: ({ row }) => h(ElButtonGroup, [
                        h(ElButton, { size: 'small', type: 'primary', plain: true, onClick: () => emit('edit', row) }, () => h('i', { class: 'bi bi-pencil' })),
                        h(ElButton, { size: 'small', type: 'danger', plain: true, onClick: () => emit('delete', row) }, () => h('i', { class: 'bi bi-trash' })),
                    ]),
                }),
            ]),
            props.rows.length === 0 && !props.loading ? h('div', { class: 'empty-state' }, props.emptyText) : null,
            h('div', { class: 'pagination-row' }, [
                h(ElPagination, {
                    currentPage: props.pagination.current_page,
                    'onUpdate:currentPage': value => props.pagination.current_page = value,
                    pageSize: props.pagination.per_page,
                    layout: 'prev, pager, next',
                    total: props.pagination.total,
                    onCurrentChange: page => emit('page', page),
                }),
            ]),
        ]);
    },
});

onMounted(async () => {
    await fetchLookups();
    fetchPrintingColors();
});
</script>

<style scoped>
.production-config {
    color: var(--bs-body-color, #0f172a);
}

.config-header,
.master-panel,
.logic-form-panel,
.logic-table-panel {
    border: 1px solid var(--bs-border-color, #dbe3ef);
    border-radius: 8px;
    background: var(--bs-body-bg, #fff);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.config-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 22px;
    margin-bottom: 18px;
}

.config-eyebrow {
    display: inline-flex;
    margin-bottom: 5px;
    color: #2563eb;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.config-header h1,
.panel-title-row h2 {
    margin: 0;
    color: var(--bs-body-color, #0f172a);
    font-weight: 850;
}

.config-header p,
.panel-title-row p {
    margin: 5px 0 0;
    color: var(--bs-secondary-color, #64748b);
}

.config-primary-action {
    font-weight: 800;
}

.config-tabs {
    border: 1px solid var(--bs-border-color, #dbe3ef);
    border-radius: 8px;
    background: var(--bs-tertiary-bg, #f8fafc);
    padding: 14px;
}

.master-panel,
.logic-form-panel,
.logic-table-panel {
    padding: 18px;
}

.panel-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}

.master-filters {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) minmax(180px, 260px) minmax(180px, 260px);
    gap: 10px;
    margin-bottom: 14px;
}

.config-table {
    width: 100%;
}

.code-badge {
    display: inline-flex;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #f8fafc;
    color: #334155;
    font-family: Consolas, monospace;
    font-weight: 800;
    padding: 3px 7px;
}

.pagination-row {
    display: flex;
    justify-content: flex-end;
    margin-top: 14px;
}

.rules-layout {
    display: grid;
    grid-template-columns: minmax(320px, 0.85fr) minmax(420px, 1.15fr);
    gap: 16px;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.form-helper {
    margin-top: 5px;
    color: var(--bs-secondary-color, #64748b);
    font-size: 0.78rem;
}

.rule-status-toggle {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--bs-body-color, #0f172a);
    font-weight: 800;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.rules-search {
    width: 260px;
}

.logic-name {
    color: var(--bs-body-color, #0f172a);
    font-weight: 850;
    margin-bottom: 3px;
}

code {
    color: #1d4ed8;
    background: rgba(37, 99, 235, 0.09);
    border-radius: 5px;
    padding: 3px 6px;
}

.impact-value {
    color: #dc2626;
    font-weight: 900;
    font-size: 1rem;
}

.impact-label {
    color: var(--bs-secondary-color, #64748b);
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
}

.empty-state {
    padding: 26px;
    color: var(--bs-secondary-color, #64748b);
    text-align: center;
    font-weight: 700;
}

:global([data-theme="dark"]) .production-config,
:global(body.dark-mode) .production-config {
    --config-bg: #0f172a;
    --config-surface: #111827;
    --config-surface-2: #1f2937;
    --config-border: #334155;
    --config-text: #f8fafc;
    --config-muted: #9fb0c8;
    --config-accent: #60a5fa;
    color: var(--config-text);
}

:global([data-theme="dark"]) .config-header,
:global([data-theme="dark"]) .master-panel,
:global([data-theme="dark"]) .logic-form-panel,
:global([data-theme="dark"]) .logic-table-panel,
:global(body.dark-mode) .config-header,
:global(body.dark-mode) .master-panel,
:global(body.dark-mode) .logic-form-panel,
:global(body.dark-mode) .logic-table-panel {
    background: var(--config-surface);
    border-color: var(--config-border);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
}

:global([data-theme="dark"]) .config-tabs,
:global(body.dark-mode) .config-tabs {
    background: var(--config-bg);
    border-color: var(--config-border);
}

:global([data-theme="dark"]) .config-header h1,
:global([data-theme="dark"]) .panel-title-row h2,
:global([data-theme="dark"]) .logic-name,
:global([data-theme="dark"]) .rule-status-toggle,
:global(body.dark-mode) .config-header h1,
:global(body.dark-mode) .panel-title-row h2,
:global(body.dark-mode) .logic-name,
:global(body.dark-mode) .rule-status-toggle {
    color: var(--config-text);
}

:global([data-theme="dark"]) .config-header p,
:global([data-theme="dark"]) .panel-title-row p,
:global([data-theme="dark"]) .form-helper,
:global([data-theme="dark"]) .impact-label,
:global([data-theme="dark"]) .empty-state,
:global(body.dark-mode) .config-header p,
:global(body.dark-mode) .panel-title-row p,
:global(body.dark-mode) .form-helper,
:global(body.dark-mode) .impact-label,
:global(body.dark-mode) .empty-state {
    color: var(--config-muted);
}

:global([data-theme="dark"]) .config-eyebrow,
:global(body.dark-mode) .config-eyebrow {
    color: var(--config-accent);
}

:global([data-theme="dark"]) .code-badge,
:global(body.dark-mode) .code-badge {
    background: var(--config-surface-2);
    border-color: #475569;
    color: #e2e8f0;
}

:global([data-theme="dark"]) .config-tabs :deep(.el-tabs__header),
:global(body.dark-mode) .config-tabs :deep(.el-tabs__header) {
    margin-bottom: 18px;
}

:global([data-theme="dark"]) .config-tabs :deep(.el-tabs__nav-wrap::after),
:global(body.dark-mode) .config-tabs :deep(.el-tabs__nav-wrap::after) {
    background-color: var(--config-border);
}

:global([data-theme="dark"]) .config-tabs :deep(.el-tabs__item),
:global(body.dark-mode) .config-tabs :deep(.el-tabs__item) {
    color: var(--config-muted);
    font-weight: 800;
}

:global([data-theme="dark"]) .config-tabs :deep(.el-tabs__item.is-active),
:global(body.dark-mode) .config-tabs :deep(.el-tabs__item.is-active) {
    color: #93c5fd;
}

:global([data-theme="dark"]) .config-tabs :deep(.el-tabs__active-bar),
:global(body.dark-mode) .config-tabs :deep(.el-tabs__active-bar) {
    background-color: #3b82f6;
}

:global([data-theme="dark"]) .config-table,
:global([data-theme="dark"]) .config-table :deep(.el-table__inner-wrapper),
:global([data-theme="dark"]) .config-table :deep(.el-table__empty-block),
:global([data-theme="dark"]) .config-table :deep(.el-table__body-wrapper),
:global(body.dark-mode) .config-table,
:global(body.dark-mode) .config-table :deep(.el-table__inner-wrapper),
:global(body.dark-mode) .config-table :deep(.el-table__empty-block),
:global(body.dark-mode) .config-table :deep(.el-table__body-wrapper) {
    background: var(--config-surface);
    color: var(--config-text);
}

:global([data-theme="dark"]) .config-table :deep(th.el-table__cell),
:global(body.dark-mode) .config-table :deep(th.el-table__cell) {
    background: #1e293b !important;
    color: #cbd5e1 !important;
    border-color: var(--config-border) !important;
}

:global([data-theme="dark"]) .config-table :deep(td.el-table__cell),
:global(body.dark-mode) .config-table :deep(td.el-table__cell) {
    background: var(--config-surface) !important;
    color: var(--config-text) !important;
    border-color: var(--config-border) !important;
}

:global([data-theme="dark"]) .config-table :deep(.el-table__empty-text),
:global(body.dark-mode) .config-table :deep(.el-table__empty-text) {
    color: var(--config-muted);
}

:global([data-theme="dark"]) .master-filters :deep(.el-input__wrapper),
:global([data-theme="dark"]) .master-filters :deep(.el-select__wrapper),
:global([data-theme="dark"]) .rules-search :deep(.el-input__wrapper),
:global(body.dark-mode) .master-filters :deep(.el-input__wrapper),
:global(body.dark-mode) .master-filters :deep(.el-select__wrapper),
:global(body.dark-mode) .rules-search :deep(.el-input__wrapper) {
    background: #1e293b;
    border: 1px solid #475569;
    box-shadow: none;
}

:global([data-theme="dark"]) .master-filters :deep(.el-input__inner),
:global([data-theme="dark"]) .rules-search :deep(.el-input__inner),
:global(body.dark-mode) .master-filters :deep(.el-input__inner),
:global(body.dark-mode) .rules-search :deep(.el-input__inner) {
    color: var(--config-text);
}

:global([data-theme="dark"]) .pagination-row :deep(.el-pager li),
:global([data-theme="dark"]) .pagination-row :deep(.btn-prev),
:global([data-theme="dark"]) .pagination-row :deep(.btn-next),
:global(body.dark-mode) .pagination-row :deep(.el-pager li),
:global(body.dark-mode) .pagination-row :deep(.btn-prev),
:global(body.dark-mode) .pagination-row :deep(.btn-next) {
    background: #1e293b;
    color: #cbd5e1;
}

:global([data-theme="dark"]) .pagination-row :deep(.el-pager li.is-active),
:global(body.dark-mode) .pagination-row :deep(.el-pager li.is-active) {
    background: #4f46e5;
    color: #fff;
}

@media (max-width: 1100px) {
    .rules-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .config-header,
    .panel-title-row {
        flex-direction: column;
        align-items: stretch;
    }

    .master-filters,
    .form-grid-2 {
        grid-template-columns: 1fr;
    }

    .rules-search {
        width: 100%;
    }
}
</style>

<style>
[data-theme="dark"] .production-config,
body.dark-mode .production-config {
    --pc-bg: #0f172a;
    --pc-surface: #111827;
    --pc-surface-2: #1e293b;
    --pc-border: #334155;
    --pc-text: #f8fafc;
    --pc-muted: #9fb0c8;
    --pc-accent: #60a5fa;
    color: var(--pc-text) !important;
}

[data-theme="dark"] .production-config .config-header,
[data-theme="dark"] .production-config .config-tabs,
[data-theme="dark"] .production-config .master-panel,
[data-theme="dark"] .production-config .logic-form-panel,
[data-theme="dark"] .production-config .logic-table-panel,
body.dark-mode .production-config .config-header,
body.dark-mode .production-config .config-tabs,
body.dark-mode .production-config .master-panel,
body.dark-mode .production-config .logic-form-panel,
body.dark-mode .production-config .logic-table-panel {
    background: var(--pc-surface) !important;
    border-color: var(--pc-border) !important;
    color: var(--pc-text) !important;
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.24) !important;
}

[data-theme="dark"] .production-config .config-tabs,
body.dark-mode .production-config .config-tabs {
    background: var(--pc-bg) !important;
}

[data-theme="dark"] .production-config h1,
[data-theme="dark"] .production-config h2,
[data-theme="dark"] .production-config h3,
[data-theme="dark"] .production-config h4,
[data-theme="dark"] .production-config h5,
[data-theme="dark"] .production-config h6,
[data-theme="dark"] .production-config .logic-name,
[data-theme="dark"] .production-config .rule-status-toggle,
body.dark-mode .production-config h1,
body.dark-mode .production-config h2,
body.dark-mode .production-config h3,
body.dark-mode .production-config h4,
body.dark-mode .production-config h5,
body.dark-mode .production-config h6,
body.dark-mode .production-config .logic-name,
body.dark-mode .production-config .rule-status-toggle {
    color: var(--pc-text) !important;
}

[data-theme="dark"] .production-config p,
[data-theme="dark"] .production-config .panel-title-row p,
[data-theme="dark"] .production-config .form-helper,
[data-theme="dark"] .production-config .impact-label,
[data-theme="dark"] .production-config .empty-state,
[data-theme="dark"] .production-config .el-table__empty-text,
body.dark-mode .production-config p,
body.dark-mode .production-config .panel-title-row p,
body.dark-mode .production-config .form-helper,
body.dark-mode .production-config .impact-label,
body.dark-mode .production-config .empty-state,
body.dark-mode .production-config .el-table__empty-text {
    color: var(--pc-muted) !important;
}

[data-theme="dark"] .production-config .config-eyebrow,
body.dark-mode .production-config .config-eyebrow {
    color: var(--pc-accent) !important;
}

[data-theme="dark"] .production-config .el-tabs__nav-wrap::after,
body.dark-mode .production-config .el-tabs__nav-wrap::after {
    background-color: var(--pc-border) !important;
}

[data-theme="dark"] .production-config .el-tabs__item,
body.dark-mode .production-config .el-tabs__item {
    color: var(--pc-muted) !important;
    font-weight: 800 !important;
}

[data-theme="dark"] .production-config .el-tabs__item.is-active,
body.dark-mode .production-config .el-tabs__item.is-active {
    color: #93c5fd !important;
}

[data-theme="dark"] .production-config .el-tabs__active-bar,
body.dark-mode .production-config .el-tabs__active-bar {
    background-color: #3b82f6 !important;
}

[data-theme="dark"] .production-config .el-input__wrapper,
[data-theme="dark"] .production-config .el-select__wrapper,
[data-theme="dark"] .production-config .el-input-number .el-input__wrapper,
body.dark-mode .production-config .el-input__wrapper,
body.dark-mode .production-config .el-select__wrapper,
body.dark-mode .production-config .el-input-number .el-input__wrapper {
    background: var(--pc-surface-2) !important;
    border: 1px solid #475569 !important;
    box-shadow: none !important;
}

[data-theme="dark"] .production-config .el-input__inner,
[data-theme="dark"] .production-config .el-select__placeholder,
[data-theme="dark"] .production-config .el-select__selected-item,
body.dark-mode .production-config .el-input__inner,
body.dark-mode .production-config .el-select__placeholder,
body.dark-mode .production-config .el-select__selected-item {
    color: var(--pc-text) !important;
}

[data-theme="dark"] .production-config .el-table,
[data-theme="dark"] .production-config .el-table__inner-wrapper,
[data-theme="dark"] .production-config .el-table__header-wrapper,
[data-theme="dark"] .production-config .el-table__body-wrapper,
[data-theme="dark"] .production-config .el-table__empty-block,
[data-theme="dark"] .production-config .el-table tr,
body.dark-mode .production-config .el-table,
body.dark-mode .production-config .el-table__inner-wrapper,
body.dark-mode .production-config .el-table__header-wrapper,
body.dark-mode .production-config .el-table__body-wrapper,
body.dark-mode .production-config .el-table__empty-block,
body.dark-mode .production-config .el-table tr {
    background: var(--pc-surface) !important;
    color: var(--pc-text) !important;
}

[data-theme="dark"] .production-config .el-table th.el-table__cell,
body.dark-mode .production-config .el-table th.el-table__cell {
    background: var(--pc-surface-2) !important;
    border-color: var(--pc-border) !important;
    color: #cbd5e1 !important;
}

[data-theme="dark"] .production-config .el-table td.el-table__cell,
body.dark-mode .production-config .el-table td.el-table__cell {
    background: var(--pc-surface) !important;
    border-color: var(--pc-border) !important;
    color: var(--pc-text) !important;
}

[data-theme="dark"] .production-config .el-table--border::after,
[data-theme="dark"] .production-config .el-table--border::before,
[data-theme="dark"] .production-config .el-table__inner-wrapper::before,
body.dark-mode .production-config .el-table--border::after,
body.dark-mode .production-config .el-table--border::before,
body.dark-mode .production-config .el-table__inner-wrapper::before {
    background-color: var(--pc-border) !important;
}

[data-theme="dark"] .production-config .code-badge,
body.dark-mode .production-config .code-badge {
    background: var(--pc-surface-2) !important;
    border-color: #475569 !important;
    color: #e2e8f0 !important;
}

[data-theme="dark"] .production-config .el-pagination button,
[data-theme="dark"] .production-config .el-pager li,
body.dark-mode .production-config .el-pagination button,
body.dark-mode .production-config .el-pager li {
    background: var(--pc-surface-2) !important;
    color: #cbd5e1 !important;
}

[data-theme="dark"] .production-config .el-pager li.is-active,
body.dark-mode .production-config .el-pager li.is-active {
    background: #4f46e5 !important;
    color: #fff !important;
}
</style>
