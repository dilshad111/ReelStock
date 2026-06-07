<template>
    <section class="job-issue-workspace">
        <div class="ji-header">
            <div>
                <span class="ji-eyebrow">Production Workflow</span>
                <h1><i class="bi bi-clipboard2-pulse me-2"></i>Job Issue</h1>
                <p>Issue job cards to production, track process stages, downtime, reel usage, wastage, and FG completion.</p>
            </div>
            <div class="ji-header-actions">
                <button class="ji-btn ji-btn-secondary" type="button" @click="fetchIssues">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <button class="ji-btn ji-btn-primary" type="button" @click="activeTab = 'issue'">
                    <i class="bi bi-plus-circle"></i> Issue Job
                </button>
            </div>
        </div>

        <div class="ji-kpis">
            <div class="ji-kpi">
                <span>Total Jobs</span>
                <strong>{{ report.summary.total_jobs || 0 }}</strong>
            </div>
            <div class="ji-kpi">
                <span>Active Jobs</span>
                <strong>{{ report.summary.active_jobs || 0 }}</strong>
            </div>
            <div class="ji-kpi">
                <span>Sheets Produced</span>
                <strong>{{ formatQty(report.summary.sheets_produced) }}</strong>
            </div>
            <div class="ji-kpi">
                <span>Downtime Min</span>
                <strong>{{ formatQty(report.summary.total_downtime_minutes) }}</strong>
            </div>
        </div>

        <el-tabs v-model="activeTab" class="ji-tabs">
            <el-tab-pane label="Issue Job" name="issue">
                <div class="ji-card">
                    <div class="ji-card-head">
                        <div>
                            <h2>Create Production Issue</h2>
                            <p>Select customer, job card, P.O. number, required quantity, and production route.</p>
                        </div>
                    </div>

                    <div class="ji-issue-grid">
                        <el-form label-position="top" class="ji-form">
                            <div class="ji-form-grid">
                                <el-form-item label="Customer" required>
                                    <el-select v-model="issueForm.customer_id" filterable placeholder="Select customer" class="w-100" @change="loadJobCards">
                                        <el-option v-for="customer in lookups.customers" :key="customer.id" :label="customer.name" :value="customer.id" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="Job Card" required>
                                    <el-select v-model="issueForm.job_card_id" filterable placeholder="Select job card" class="w-100" :disabled="!issueForm.customer_id">
                                        <el-option v-for="job in customerJobCards" :key="job.id" :label="jobCardLabel(job)" :value="job.id" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="P.O. Number">
                                    <el-input v-model="issueForm.purchase_order_no" placeholder="Purchase order number" />
                                </el-form-item>
                                <el-form-item label="Required Carton Quantity" required>
                                    <el-input-number v-model="issueForm.required_carton_qty" :min="1" class="w-100" :controls="false" />
                                </el-form-item>
                                <el-form-item label="Issue Date">
                                    <el-date-picker v-model="issueForm.issued_date" type="date" value-format="YYYY-MM-DD" placeholder="Issue date" class="w-100" />
                                </el-form-item>
                                <el-form-item label="Production Route" required>
                                    <el-select v-model="issueForm.production_route" class="w-100">
                                        <el-option v-for="route in lookups.production_routes" :key="route.value" :label="route.label" :value="route.value" />
                                    </el-select>
                                </el-form-item>
                            </div>
                            <div class="ji-actions">
                                <button class="ji-btn ji-btn-light" type="button" @click="resetIssueForm">Discard</button>
                                <button class="ji-btn ji-btn-primary" type="button" :disabled="submittingIssue" @click="submitIssue">
                                    <i class="bi bi-printer"></i> Issue & Print
                                </button>
                            </div>
                        </el-form>

                        <aside class="ji-preview">
                            <span class="ji-preview-label">Selected Job Card</span>
                            <template v-if="selectedJobCard">
                                <h3>{{ selectedJobCard.job_card_no }}</h3>
                                <p>{{ selectedJobCard.product?.item_name || '-' }}</p>
                                <div class="ji-spec-grid">
                                    <span>Item Code</span><strong>{{ selectedJobCard.product?.item_code || '-' }}</strong>
                                    <span>Carton Type</span><strong>{{ selectedJobCard.carton_type || '-' }}</strong>
                                    <span>Dimensions</span><strong>{{ formatNum(selectedJobCard.length_mm) }} x {{ formatNum(selectedJobCard.width_mm) }} x {{ formatNum(selectedJobCard.height_mm) }} mm</strong>
                                    <span>Deckle</span><strong>{{ formatNum(selectedJobCard.deckle_size) }}"</strong>
                                    <span>Sheet Length</span><strong>{{ formatNum(selectedJobCard.sheet_length) }}"</strong>
                                </div>
                            </template>
                            <div v-else class="ji-empty">Select a job card to preview production specifications.</div>
                        </aside>
                    </div>
                </div>
            </el-tab-pane>

            <el-tab-pane label="Active Jobs" name="active">
                <div class="ji-card ji-active-list-card">
                    <div class="ji-filter-row">
                        <el-input v-model="filters.search" placeholder="Search Job #, PO #, Item..." clearable @keyup.enter="fetchIssues" />
                        <el-select v-model="filters.customer_id" clearable filterable placeholder="All Customers">
                            <el-option v-for="customer in lookups.customers" :key="customer.id" :label="customer.name" :value="customer.id" />
                        </el-select>
                        <el-select v-model="filters.status" clearable placeholder="All Statuses">
                            <el-option label="Issued" value="Issued" />
                            <el-option label="In-Progress" value="In-Progress" />
                            <el-option label="Corrugation" value="Corrugation" />
                            <el-option label="Printing" value="Printing" />
                            <el-option label="Die-Cutting" value="Die-Cutting" />
                            <el-option label="Bundling" value="Bundling" />
                            <el-option label="Completed" value="Completed" />
                        </el-select>
                        <button class="ji-btn ji-btn-primary" type="button" @click="fetchIssues">Apply</button>
                        <button class="ji-btn ji-btn-clear" type="button" @click="clearFilters">Clear</button>
                    </div>

                    <el-table :data="issues" v-loading="loadingIssues" class="ji-table" border>
                        <el-table-column prop="job_no" label="Job #" width="120">
                            <template #default="{ row }"><strong>{{ row.job_no }}</strong></template>
                        </el-table-column>
                        <el-table-column prop="customer.name" label="Customer" min-width="180" />
                        <el-table-column label="Item" min-width="220">
                            <template #default="{ row }">{{ row.product?.item_name || row.job_card?.product?.item_name || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="Req. Qty" width="105" align="right">
                            <template #default="{ row }">{{ formatQty(row.required_carton_qty) }}</template>
                        </el-table-column>
                        <el-table-column label="Stage" width="120" align="center">
                            <template #default="{ row }"><span class="ji-stage-badge">{{ row.current_stage }}</span></template>
                        </el-table-column>
                        <el-table-column label="Status" width="120" align="center">
                            <template #default="{ row }"><span :class="['ji-status', statusClass(row.status)]">{{ row.status }}</span></template>
                        </el-table-column>
                        <el-table-column label="Action" width="62" align="center" fixed="right">
                            <template #default="{ row }">
                                <el-dropdown trigger="click" placement="bottom-end" popper-class="ji-actions-dropdown">
                                    <button class="ji-action-menu-btn" type="button" title="Actions">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <template #dropdown>
                                        <el-dropdown-menu>
                                            <el-dropdown-item @click="printIssue(row.id)">
                                                <i class="bi bi-printer me-2"></i>Print Issue
                                            </el-dropdown-item>
                                            <el-dropdown-item v-if="row.status === 'Issued'" @click="startJob(row)">
                                                <i class="bi bi-play-fill me-2"></i>Start Job
                                            </el-dropdown-item>
                                            <el-dropdown-item @click="selectIssue(row)">
                                                <i class="bi bi-clipboard-data me-2"></i>Track Production
                                            </el-dropdown-item>
                                        </el-dropdown-menu>
                                    </template>
                                </el-dropdown>
                            </template>
                        </el-table-column>
                    </el-table>

                    <div class="ji-pagination">
                        <el-pagination v-model:current-page="pagination.current_page" :page-size="pagination.per_page" layout="prev, pager, next" :total="pagination.total" @current-change="fetchIssues" />
                    </div>
                </div>
            </el-tab-pane>

            <el-tab-pane label="Production Entry" name="tracking">
                <div v-if="!selectedIssue" class="ji-card ji-empty-state">
                    Select a job from Active Jobs to start recording production activity.
                </div>

                <div v-else class="ji-tracking-grid">
                    <div class="ji-card">
                        <div class="ji-card-head">
                            <div>
                                <h2>{{ selectedIssue.job_no }}</h2>
                                <p>{{ selectedIssue.customer?.name }} | {{ selectedIssue.product?.item_name || selectedIssue.job_card?.product?.item_name }}</p>
                            </div>
                            <span :class="['ji-status', statusClass(selectedIssue.status)]">{{ selectedIssue.status }}</span>
                        </div>

                        <div class="ji-stage-flow">
                            <span v-for="stage in visibleStages(selectedIssue.production_route)" :key="stage" :class="{ active: selectedIssue.current_stage === stage, done: stageDone(stage) }">
                                {{ stage }}
                            </span>
                        </div>

                        <template v-if="selectedIssue.current_stage !== 'Bundling' && selectedIssue.status !== 'Completed'">
                            <h3 class="ji-subtitle">{{ selectedIssue.current_stage }} Production Entry</h3>
                            <div class="ji-form-grid">
                                <el-form-item label="Machine Name">
                                    <el-select v-model="entryForm.machine_id" filterable clearable placeholder="Select machine" class="w-100">
                                        <el-option v-for="machine in lookups.machines" :key="machine.id" :label="machineLabel(machine)" :value="machine.id" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="Operator / Employee">
                                    <el-select v-model="entryForm.operator_id" filterable clearable placeholder="Select operator" class="w-100">
                                        <el-option v-for="operator in filteredOperators" :key="operator.id" :label="operator.operator_name" :value="operator.id" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="Start Date & Time" required>
                                    <el-input v-model="entryForm.start_at" type="datetime-local" />
                                </el-form-item>
                                <el-form-item label="End Date & Time">
                                    <el-input v-model="entryForm.end_at" type="datetime-local" />
                                </el-form-item>
                                <el-form-item label="Quantity Produced" required>
                                    <el-input-number v-model="entryForm.quantity_produced" :min="0" class="w-100" :controls="false" />
                                </el-form-item>
                                <el-form-item label="Remarks">
                                    <el-input v-model="entryForm.remarks" placeholder="Production notes..." />
                                </el-form-item>
                            </div>

                            <div v-if="selectedIssue.current_stage === 'Corrugation'" class="ji-subpanel">
                                <div class="ji-line-title">
                                    <strong>Reel Consumption</strong>
                                    <button class="ji-mini-btn" type="button" @click="addReel">Add Reel</button>
                                </div>
                                <div v-for="(reel, index) in entryForm.reels" :key="index" class="ji-line-grid reels">
                                    <div class="ji-field">
                                        <label>Paper Layer</label>
                                        <el-select v-model="reel.layer_label" placeholder="Layer" filterable class="w-100" @change="applyReelLayer(reel)">
                                            <el-option
                                                v-for="layer in reelLayerOptions"
                                                :key="layer.key"
                                                :label="layer.label"
                                                :value="layer.label"
                                            />
                                        </el-select>
                                    </div>
                                    <div class="ji-field ji-reel-no-field">
                                        <label>Reel No.</label>
                                        <el-input v-model="reel.reel_no" placeholder="e.g. 112742" @blur="fetchReelQuality(reel)" />
                                    </div>
                                    <div class="ji-field ji-quality-field">
                                        <label>Paper Quality</label>
                                        <div class="ji-reel-quality" :class="{ empty: !reel.quality_name }">
                                            <strong>{{ reel.quality_name || 'Paper quality will fetch from reel' }}</strong>
                                            <span v-if="reel.layer_label">{{ reel.layer_label }}</span>
                                        </div>
                                    </div>
                                    <div class="ji-field ji-consumed-field">
                                        <label>Consumed KG</label>
                                        <el-input-number v-model="reel.consumed_weight" :min="0" :controls="false" placeholder="KG" class="w-100" />
                                    </div>
                                    <button class="ji-icon-btn danger" type="button" @click="entryForm.reels.splice(index, 1)"><i class="bi bi-x-lg"></i></button>
                                </div>

                                <div class="ji-consumption-summary">
                                    <div class="ji-summary-head">
                                        <strong>Consumption Comparison</strong>
                                        <span>Estimated vs actual paper consumption</span>
                                    </div>
                                    <div class="ji-summary-table">
                                        <div class="ji-summary-row header">
                                            <span>Layer</span>
                                            <span>Est. KG</span>
                                            <span>Actual KG</span>
                                            <span>Variance</span>
                                            <span>Var. %</span>
                                        </div>
                                        <div v-for="row in liveConsumptionSummary.rows" :key="row.layer" class="ji-summary-row">
                                            <span>{{ row.layer }}</span>
                                            <span>{{ formatNum(row.estimated_kg) }}</span>
                                            <span>{{ formatNum(row.actual_kg) }}</span>
                                            <span :class="varianceClass(row.variance_kg)">{{ signedNum(row.variance_kg) }}</span>
                                            <span :class="varianceClass(row.variance_kg)">{{ signedNum(row.variance_percent) }}%</span>
                                        </div>
                                        <div class="ji-summary-row total">
                                            <span>Grand Total</span>
                                            <span>{{ formatNum(liveConsumptionSummary.totals.estimated_kg) }}</span>
                                            <span>{{ formatNum(liveConsumptionSummary.totals.actual_kg) }}</span>
                                            <span :class="varianceClass(liveConsumptionSummary.totals.variance_kg)">{{ signedNum(liveConsumptionSummary.totals.variance_kg) }}</span>
                                            <span :class="varianceClass(liveConsumptionSummary.totals.variance_kg)">{{ signedNum(liveConsumptionSummary.totals.variance_percent) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="ji-subpanel">
                                <div class="ji-line-title">
                                    <strong>Machine Breakdown</strong>
                                    <button class="ji-mini-btn" type="button" @click="addBreakdown">Add Breakdown</button>
                                </div>
                                <div v-for="(row, index) in entryForm.breakdowns" :key="index" class="ji-line-grid breakdowns">
                                    <el-input v-model="row.breakdown_start_at" type="datetime-local" />
                                    <el-input v-model="row.breakdown_end_at" type="datetime-local" />
                                    <el-select v-model="row.reason" placeholder="Reason" class="w-100">
                                        <el-option v-for="reason in lookups.breakdown_reasons" :key="reason" :label="reason" :value="reason" />
                                    </el-select>
                                    <button class="ji-icon-btn danger" type="button" @click="entryForm.breakdowns.splice(index, 1)"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>

                            <div class="ji-subpanel">
                                <div class="ji-line-title">
                                    <strong>Wastage Recording</strong>
                                    <button class="ji-mini-btn" type="button" @click="addWastage">Add Wastage</button>
                                </div>
                                <div v-for="(row, index) in entryForm.wastages" :key="index" class="ji-line-grid wastages">
                                    <el-select v-model="row.wastage_type" placeholder="Wastage Type" class="w-100">
                                        <el-option v-for="type in lookups.wastage_types" :key="type" :label="type" :value="type" />
                                    </el-select>
                                    <el-input-number v-model="row.quantity" :min="0" :controls="false" placeholder="Qty" class="w-100" />
                                    <el-select v-model="row.reason" placeholder="Reason" class="w-100">
                                        <el-option v-for="reason in lookups.wastage_reasons" :key="reason" :label="reason" :value="reason" />
                                    </el-select>
                                    <button class="ji-icon-btn danger" type="button" @click="entryForm.wastages.splice(index, 1)"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>

                            <div class="ji-actions">
                                <button class="ji-btn ji-btn-light" type="button" @click="resetEntryForm">Clear Entry</button>
                                <button class="ji-btn ji-btn-primary" type="button" :disabled="submittingEntry" @click="saveEntry">Save Production Entry</button>
                                <button class="ji-btn ji-btn-success" type="button" @click="completeStage">Complete Process</button>
                            </div>
                        </template>

                        <template v-else-if="selectedIssue.current_stage === 'Bundling' && selectedIssue.status !== 'Completed'">
                            <h3 class="ji-subtitle">Bundling & Finished Goods Verification</h3>
                            <div class="ji-form-grid">
                                <el-form-item label="Final Finished Carton Quantity" required>
                                    <el-input-number v-model="completeForm.final_finished_qty" :min="1" class="w-100" :controls="false" />
                                </el-form-item>
                                <el-form-item label="Rejected Cartons Quantity">
                                    <el-input-number v-model="completeForm.rejected_cartons_qty" :min="0" class="w-100" :controls="false" />
                                </el-form-item>
                                <el-form-item label="Wastage Reason">
                                    <el-select v-model="completeForm.final_wastage_reason" clearable placeholder="Select reason" class="w-100">
                                        <el-option v-for="reason in lookups.wastage_reasons" :key="reason" :label="reason" :value="reason" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="Completion Remarks">
                                    <el-input v-model="completeForm.completion_remarks" placeholder="Final remarks..." />
                                </el-form-item>
                            </div>
                            <div class="ji-actions">
                                <button class="ji-btn ji-btn-success" type="button" @click="completeJob">
                                    <i class="bi bi-check2-circle"></i> Complete Job & Transfer to FG
                                </button>
                            </div>
                        </template>

                        <div v-else class="ji-complete-box">
                            <i class="bi bi-check-circle"></i>
                            Job completed and transferred to Finished Goods Inventory.
                        </div>
                    </div>

                    <div class="ji-card">
                        <div class="ji-card-head">
                            <div>
                                <h2>Job Log</h2>
                                <p>Accumulated production records under the same Job #.</p>
                            </div>
                        </div>
                        <div class="ji-log-list">
                            <div v-for="entry in selectedIssue.stage_entries || []" :key="entry.id" class="ji-log-item">
                                <div>
                                    <strong>{{ entry.stage }}</strong>
                                    <span>{{ formatDateTime(entry.start_at) }} - {{ formatDateTime(entry.end_at) }}</span>
                                </div>
                                <b>{{ formatQty(entry.quantity_produced) }}</b>
                            </div>
                            <div v-if="!selectedIssue.stage_entries?.length" class="ji-empty">No production entries recorded yet.</div>
                        </div>

                        <div class="ji-side-report" v-if="selectedIssue.paper_consumption_comparison">
                            <div class="ji-side-title">
                                <strong>Paper Consumption Report</strong>
                                <span :class="varianceStatusClass(selectedIssue.paper_consumption_comparison.totals?.variance_kg)">
                                    {{ selectedIssue.paper_consumption_comparison.totals?.status }}
                                </span>
                            </div>
                            <div class="ji-side-metrics">
                                <div>
                                    <span>Estimated</span>
                                    <strong>{{ formatNum(selectedIssue.paper_consumption_comparison.totals?.estimated_kg) }} KG</strong>
                                </div>
                                <div>
                                    <span>Actual</span>
                                    <strong>{{ formatNum(selectedIssue.paper_consumption_comparison.totals?.actual_kg) }} KG</strong>
                                </div>
                                <div>
                                    <span>Variance</span>
                                    <strong :class="varianceClass(selectedIssue.paper_consumption_comparison.totals?.variance_kg)">
                                        {{ signedNum(selectedIssue.paper_consumption_comparison.totals?.variance_kg) }} KG
                                    </strong>
                                </div>
                                <div>
                                    <span>Efficiency</span>
                                    <strong>{{ formatNum(selectedIssue.paper_consumption_comparison.totals?.efficiency_percent) }}%</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </el-tab-pane>

            <el-tab-pane label="Reports" name="reports">
                <div class="ji-card">
                    <div class="ji-filter-row report">
                        <el-date-picker v-model="reportFilters.date_from" type="date" value-format="YYYY-MM-DD" placeholder="From date" />
                        <el-date-picker v-model="reportFilters.date_to" type="date" value-format="YYYY-MM-DD" placeholder="To date" />
                        <el-select v-model="reportFilters.customer_id" clearable filterable placeholder="Customer">
                            <el-option v-for="customer in lookups.customers" :key="customer.id" :label="customer.name" :value="customer.id" />
                        </el-select>
                        <button class="ji-btn ji-btn-primary" type="button" @click="fetchReport">Generate Report</button>
                    </div>

                    <el-table :data="report.jobs" class="ji-table" border>
                        <el-table-column prop="job_no" label="Job #" min-width="130" />
                        <el-table-column prop="customer" label="Customer" min-width="180" />
                        <el-table-column prop="product" label="Product" min-width="220" />
                        <el-table-column prop="sheets_produced" label="Sheets" width="115" align="right" />
                        <el-table-column prop="cartons_produced" label="Cartons" width="115" align="right" />
                        <el-table-column prop="downtime_minutes" label="Downtime" width="115" align="right" />
                        <el-table-column prop="efficiency_percent" label="Efficiency %" width="120" align="right" />
                        <el-table-column prop="wastage_qty" label="Wastage" width="115" align="right" />
                        <el-table-column prop="reel_weight_consumed" label="Reel KG" width="115" align="right" />
                        <el-table-column prop="estimated_paper_consumed" label="Est. KG" width="115" align="right" />
                        <el-table-column prop="paper_consumption_variance" label="Var. KG" width="115" align="right">
                            <template #default="{ row }">
                                <span :class="varianceClass(row.paper_consumption_variance)">{{ signedNum(row.paper_consumption_variance) }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="paper_variance_percent" label="Var. %" width="110" align="right">
                            <template #default="{ row }">
                                <span :class="varianceClass(row.paper_consumption_variance)">{{ signedNum(row.paper_variance_percent) }}%</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="material_efficiency_percent" label="Material Eff. %" width="135" align="right" />
                    </el-table>
                </div>
            </el-tab-pane>
        </el-tabs>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';

const activeTab = ref('active');
const loadingIssues = ref(false);
const submittingIssue = ref(false);
const submittingEntry = ref(false);
const issues = ref([]);
const customerJobCards = ref([]);
const selectedIssue = ref(null);

const pagination = reactive({
    current_page: 1,
    per_page: 15,
    total: 0,
});

const filters = reactive({
    search: '',
    customer_id: null,
    status: '',
});

const lookups = reactive({
    customers: [],
    machines: [],
    operators: [],
    production_routes: [],
    breakdown_reasons: [],
    wastage_types: [],
    wastage_reasons: [],
    stages: [],
    flute_factors: [],
});

const issueForm = reactive({
    customer_id: null,
    job_card_id: null,
    purchase_order_no: '',
    required_carton_qty: null,
    issued_date: new Date().toISOString().slice(0, 10),
    production_route: 'print_die_cut',
});

const entryForm = reactive({
    machine_id: null,
    operator_id: null,
    start_at: localDateTime(),
    end_at: '',
    quantity_produced: null,
    remarks: '',
    breakdowns: [],
    wastages: [],
    reels: [],
});

const completeForm = reactive({
    final_finished_qty: null,
    rejected_cartons_qty: 0,
    final_wastage_reason: '',
    completion_remarks: '',
});

const reportFilters = reactive({
    date_from: '',
    date_to: '',
    customer_id: null,
});

const report = reactive({
    summary: {},
    jobs: [],
    wastage_by_reason: [],
    reel_quality_usage: [],
    production_by_operator: [],
    production_by_machine: [],
});

const selectedJobCard = computed(() => customerJobCards.value.find(job => job.id === issueForm.job_card_id));

const filteredOperators = computed(() => {
    if (!entryForm.machine_id) return lookups.operators;
    return lookups.operators.filter(operator => operator.machine_id === entryForm.machine_id);
});

const reelLayerOptions = computed(() => {
    const layers = selectedIssue.value?.job_card?.layers?.length
        ? selectedIssue.value.job_card.layers
        : selectedIssue.value?.jobCard?.layers || [];

    return [...layers]
        .sort((a, b) => Number(a.sequence || 0) - Number(b.sequence || 0))
        .map((layer, index) => {
            const label = productionLayerLabel(layer, index, layers);
            return {
                key: `${index}-${label}`,
                label,
                layer_type: layer.layer_type || '',
                flute_profile: layer.flute_profile || 'Flat',
                layer_gsm: Number(layer.gsm || 0),
            };
        });
});

const fluteFactorMap = computed(() => {
    const defaults = { FLAT: 1, B: 1.35, C: 1.45, E: 1.25 };
    (lookups.flute_factors || []).forEach(row => {
        defaults[String(row.flute_type || '').toUpperCase()] = Number(row.factor || 1);
    });
    return defaults;
});

const liveConsumptionSummary = computed(() => buildConsumptionComparison(
    Number(entryForm.quantity_produced || 0),
    entryForm.reels || []
));

function localDateTime() {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
}

async function fetchLookups() {
    const { data } = await axios.get('/api/job-issues/lookups');
    Object.assign(lookups, data);
}

async function fetchIssues() {
    loadingIssues.value = true;
    try {
        const { data } = await axios.get('/api/job-issues', {
            params: {
                ...filters,
                page: pagination.current_page,
                per_page: pagination.per_page,
            },
        });
        issues.value = data.data || [];
        pagination.total = data.total || 0;
        pagination.current_page = data.current_page || 1;
    } finally {
        loadingIssues.value = false;
    }
}

async function fetchReport() {
    const { data } = await axios.get('/api/job-issues/reports/summary', { params: reportFilters });
    Object.assign(report, data);
}

async function loadJobCards() {
    issueForm.job_card_id = null;
    customerJobCards.value = [];
    if (!issueForm.customer_id) return;
    const { data } = await axios.get(`/api/job-issues/customer/${issueForm.customer_id}/job-cards`);
    customerJobCards.value = data;
}

async function submitIssue() {
    if (!issueForm.customer_id || !issueForm.job_card_id || !issueForm.required_carton_qty) {
        ElMessage.error('Customer, Job Card, and Required Quantity are mandatory.');
        return;
    }

    submittingIssue.value = true;
    try {
        const { data } = await axios.post('/api/job-issues', issueForm);
        ElMessage.success(`Job issued successfully: ${data.job_no}`);
        resetIssueForm();
        await fetchIssues();
        await fetchReport();
        printIssue(data.id);
        activeTab.value = 'active';
    } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Unable to issue job.');
    } finally {
        submittingIssue.value = false;
    }
}

function resetIssueForm() {
    issueForm.customer_id = null;
    issueForm.job_card_id = null;
    issueForm.purchase_order_no = '';
    issueForm.required_carton_qty = null;
    issueForm.issued_date = new Date().toISOString().slice(0, 10);
    issueForm.production_route = 'print_die_cut';
    customerJobCards.value = [];
}

function clearFilters() {
    filters.search = '';
    filters.customer_id = null;
    filters.status = '';
    pagination.current_page = 1;
    fetchIssues();
}

function printIssue(id) {
    window.open(`/job-issues/${id}/print`, '_blank');
}

async function startJob(row) {
    const { data } = await axios.post(`/api/job-issues/${row.id}/start`);
    ElMessage.success(`${data.job_no} started at ${data.current_stage}.`);
    await fetchIssues();
    selectIssue(data);
}

async function selectIssue(row) {
    const { data } = await axios.get(`/api/job-issues/${row.id}`);
    selectedIssue.value = data;
    completeForm.final_finished_qty = data.final_finished_qty || null;
    completeForm.rejected_cartons_qty = data.rejected_cartons_qty || 0;
    completeForm.final_wastage_reason = data.final_wastage_reason || '';
    completeForm.completion_remarks = data.completion_remarks || '';
    resetEntryForm();
    activeTab.value = 'tracking';
}

function resetEntryForm() {
    entryForm.machine_id = null;
    entryForm.operator_id = null;
    entryForm.start_at = localDateTime();
    entryForm.end_at = '';
    entryForm.quantity_produced = null;
    entryForm.remarks = '';
    entryForm.breakdowns = [];
    entryForm.wastages = [];
    entryForm.reels = selectedIssue.value?.current_stage === 'Corrugation' ? [makeReelRow()] : [];
}

function addBreakdown() {
    entryForm.breakdowns.push({
        breakdown_start_at: localDateTime(),
        breakdown_end_at: '',
        reason: '',
        remarks: '',
    });
}

function addWastage() {
    entryForm.wastages.push({
        wastage_type: 'Paper Waste',
        quantity: null,
        reason: '',
        remarks: '',
    });
}

function addReel() {
    entryForm.reels.push(makeReelRow());
}

function makeReelRow() {
    const firstLayer = reelLayerOptions.value[0];

    return {
        reel_no: '',
        quality_name: '',
        layer_label: firstLayer?.label || '',
        layer_type: firstLayer?.layer_type || '',
        layer_gsm: firstLayer?.layer_gsm || null,
        consumed_weight: null,
    };
}

function productionLayerLabel(layer, index, layers) {
    const type = String(layer.layer_type || '').toLowerCase();
    const middleNumber = layers.slice(0, index + 1).filter(item => String(item.layer_type || '').toLowerCase().includes('middle')).length;

    if (type.includes('top') || type.includes('outer')) return 'Outer Paper';
    if (type.includes('inner') || type.includes('bottom')) return 'Inner Paper';
    if (type.includes('middle')) return middleNumber > 1 ? `Middle Paper ${middleNumber}` : 'Middle Paper';
    if (type.includes('flute')) {
        const profile = layer.flute_profile && layer.flute_profile !== 'Flat' ? String(layer.flute_profile).trim() : '';
        return profile ? `Flute-${profile}` : 'Flute';
    }

    return layer.layer_type || `Layer ${index + 1}`;
}

function applyReelLayer(reel) {
    const layer = reelLayerOptions.value.find(option => option.label === reel.layer_label);
    reel.layer_type = layer?.layer_type || '';
    reel.layer_gsm = layer?.layer_gsm || null;
}

function buildConsumptionComparison(sheetsProduced, reelRows) {
    const rows = reelLayerOptions.value.map(layer => {
        const actual = (reelRows || [])
            .filter(row => row.layer_label === layer.label)
            .reduce((sum, row) => sum + Number(row.consumed_weight || 0), 0);
        const estimated = estimatedLayerKg(layer, sheetsProduced);
        const variance = actual - estimated;
        const variancePercent = estimated > 0 ? (variance / estimated) * 100 : 0;

        return {
            layer: layer.label,
            estimated_kg: round2(estimated),
            actual_kg: round2(actual),
            variance_kg: round2(variance),
            variance_percent: round2(variancePercent),
        };
    });

    const extraActual = {};
    (reelRows || []).forEach(row => {
        const label = row.layer_label || 'Unassigned';
        if (!rows.some(item => item.layer === label)) {
            extraActual[label] = (extraActual[label] || 0) + Number(row.consumed_weight || 0);
        }
    });
    Object.entries(extraActual).forEach(([layer, actual]) => {
        rows.push({
            layer,
            estimated_kg: 0,
            actual_kg: round2(actual),
            variance_kg: round2(actual),
            variance_percent: 0,
        });
    });

    const estimatedTotal = rows.reduce((sum, row) => sum + Number(row.estimated_kg || 0), 0);
    const actualTotal = rows.reduce((sum, row) => sum + Number(row.actual_kg || 0), 0);
    const varianceTotal = actualTotal - estimatedTotal;

    return {
        rows,
        totals: {
            estimated_kg: round2(estimatedTotal),
            actual_kg: round2(actualTotal),
            variance_kg: round2(varianceTotal),
            variance_percent: estimatedTotal > 0 ? round2((varianceTotal / estimatedTotal) * 100) : 0,
            efficiency_percent: actualTotal > 0 ? round2((estimatedTotal / actualTotal) * 100) : 0,
        },
    };
}

function estimatedLayerKg(layer, sheetsProduced) {
    const area = sheetAreaM2();
    const gsm = Number(layer.layer_gsm || 0);
    const factor = factorForLayer(layer);
    return Number(sheetsProduced || 0) * area * gsm * factor / 1000;
}

function sheetAreaM2() {
    const jobCard = selectedIssue.value?.job_card || selectedIssue.value?.jobCard || selectedJobCard.value || {};
    const deckleIn = Number(jobCard.deckle_size || 0);
    const sheetLengthIn = Number(jobCard.sheet_length || 0);
    return Math.max(deckleIn, 0) * Math.max(sheetLengthIn, 0) * 0.00064516;
}

function factorForLayer(layer) {
    const type = String(layer.layer_type || '').toLowerCase();
    const profile = String(layer.flute_profile || 'Flat').toUpperCase();

    if (!type.includes('flute')) {
        return fluteFactorMap.value.FLAT || 1;
    }

    return fluteFactorMap.value[profile] || 1;
}

function round2(value) {
    return Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;
}

function normalizeReelNo(value) {
    const normalized = String(value || '').trim().toUpperCase();
    if (!normalized) return '';
    return normalized.startsWith('RL') ? normalized : `RL${normalized}`;
}

async function fetchReelQuality(reel) {
    if (!reel.reel_no) return;
    reel.reel_no = normalizeReelNo(reel.reel_no);
    try {
        const { data } = await axios.get(`/api/fetch-reel/${encodeURIComponent(reel.reel_no)}`);
        const payload = data.data || data;
        const paperQuality = payload.paper_quality || payload.paperQuality || {};
        const quality = paperQuality.quality || '';
        const gsm = paperQuality.gsm_range || paperQuality.standard_gsm || paperQuality.gsm || '';
        reel.quality_name = [quality, gsm ? `(${gsm})` : ''].filter(Boolean).join(' ');
    } catch (error) {
        reel.quality_name = '';
        ElMessage.warning('Reel not found in master inventory.');
    }
}

async function saveEntry() {
    if (!selectedIssue.value) return;
    if (!entryForm.start_at || !entryForm.quantity_produced) {
        ElMessage.error('Start Date & Time and Quantity Produced are required.');
        return;
    }

    submittingEntry.value = true;
    try {
        await axios.post(`/api/job-issues/${selectedIssue.value.id}/entries`, {
            ...entryForm,
            stage: selectedIssue.value.current_stage,
            reels: entryForm.reels
                .filter(row => row.reel_no && row.consumed_weight)
                .map(row => ({ ...row, reel_no: normalizeReelNo(row.reel_no) })),
            breakdowns: entryForm.breakdowns.filter(row => row.breakdown_start_at && row.reason),
            wastages: entryForm.wastages.filter(row => row.wastage_type && row.quantity),
        });
        ElMessage.success('Production entry saved.');
        await selectIssue(selectedIssue.value);
        await fetchIssues();
        await fetchReport();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Unable to save production entry.');
    } finally {
        submittingEntry.value = false;
    }
}

async function completeStage() {
    if (!selectedIssue.value) return;
    try {
        const { data } = await axios.post(`/api/job-issues/${selectedIssue.value.id}/complete-stage`, {
            stage: selectedIssue.value.current_stage,
        });
        ElMessage.success(`Process completed. Next stage: ${data.current_stage}.`);
        selectedIssue.value = data;
        resetEntryForm();
        await fetchIssues();
        await fetchReport();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Unable to complete process.');
    }
}

async function completeJob() {
    if (!selectedIssue.value) return;
    if (!completeForm.final_finished_qty) {
        ElMessage.error('Final Finished Carton Quantity is required.');
        return;
    }

    try {
        const { data } = await axios.post(`/api/job-issues/${selectedIssue.value.id}/complete-job`, completeForm);
        ElMessage.success('Job completed and transferred to Finished Goods Inventory.');
        selectedIssue.value = data;
        await fetchIssues();
        await fetchReport();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Unable to complete job.');
    }
}

function visibleStages(route) {
    if (route === 'print_only') return ['Corrugation', 'Printing', 'Bundling'];
    if (route === 'die_cut_only') return ['Corrugation', 'Die-Cutting', 'Bundling'];
    if (route === 'die_cut_print') return ['Corrugation', 'Die-Cutting', 'Printing', 'Bundling'];
    if (route === 'corrugation_only') return ['Corrugation', 'Bundling'];
    return ['Corrugation', 'Printing', 'Die-Cutting', 'Bundling'];
}

function stageDone(stage) {
    if (!selectedIssue.value) return false;
    const stages = visibleStages(selectedIssue.value.production_route);
    return stages.indexOf(stage) < stages.indexOf(selectedIssue.value.current_stage) || selectedIssue.value.status === 'Completed';
}

function jobCardLabel(job) {
    return `${job.job_card_no} - ${job.product?.item_name || 'Unnamed Item'}`;
}

function machineLabel(machine) {
    const department = machine.department?.department_name ? ` / ${machine.department.department_name}` : '';
    return `${machine.machine_name}${department}`;
}

function statusClass(status) {
    if (status === 'Completed') return 'success';
    if (status === 'Issued') return 'info';
    if (status === 'Bundling') return 'warning';
    return 'primary';
}

function formatQty(value) {
    return Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
}

function formatNum(value) {
    return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function signedNum(value) {
    const number = Number(value || 0);
    const formatted = Math.abs(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (number > 0) return `+${formatted}`;
    if (number < 0) return `-${formatted}`;
    return formatted;
}

function varianceClass(value) {
    const number = Number(value || 0);
    if (number > 0) return 'variance-over';
    if (number < 0) return 'variance-under';
    return 'variance-ok';
}

function varianceStatusClass(value) {
    return ['ji-consumption-status', varianceClass(value)];
}

function formatDateTime(value) {
    if (!value) return 'Open';
    return new Date(value).toLocaleString();
}

onMounted(async () => {
    await fetchLookups();
    await fetchIssues();
    await fetchReport();
});
</script>

<style scoped>
.job-issue-workspace {
    --ji-bg: #f4f7fb;
    --ji-panel: #ffffff;
    --ji-panel-soft: #eef3f8;
    --ji-border: #d7e1ec;
    --ji-text: #102033;
    --ji-muted: #60738d;
    --ji-primary: #2f74ed;
    --ji-success: #13a66b;
    --ji-warning: #d99100;
    --ji-field-bg: #f8fafc;
    --ji-field-text: #102033;
    --ji-field-muted: #60738d;
    --ji-summary-panel-bg: rgba(15, 23, 42, 0.03);
    --ji-summary-head-bg: #edf2f7;
    --ji-summary-head-text: #102033;
    --ji-summary-head-muted: #60738d;
    --ji-summary-row-bg: transparent;
    --ji-summary-header-bg: #f8fafc;
    --ji-summary-total-bg: #f8fafc;
    --ji-summary-text: #102033;
    --ji-metric-card-bg: #eef3f8;
    --ji-metric-label: #60738d;
    --ji-metric-value: #102033;
    color: var(--ji-text);
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
    padding: 16px;
}
.ji-header,
.ji-card,
.ji-kpi {
    background: var(--ji-panel);
    border: 1px solid var(--ji-border);
    border-radius: 8px;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
}
.ji-header {
    align-items: center;
    display: flex;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 16px;
    padding: 18px 22px;
}
.ji-eyebrow {
    color: var(--ji-primary);
    display: block;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.ji-header h1 {
    font-size: 1.85rem;
    font-weight: 900;
    line-height: 1.1;
    margin: 4px 0 5px;
}
.ji-header p,
.ji-card-head p {
    color: var(--ji-muted);
    font-size: 0.9rem;
    font-weight: 700;
    margin: 0;
}
.ji-header-actions,
.ji-actions,
.ji-row-actions {
    align-items: center;
    display: flex;
    gap: 10px;
}
.ji-btn {
    align-items: center;
    border: 0;
    border-radius: 8px;
    display: inline-flex;
    font-size: 0.94rem;
    font-weight: 900;
    gap: 7px;
    height: 46px;
    justify-content: center;
    padding: 0 18px;
}
.ji-btn:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}
.ji-btn-primary {
    background: var(--ji-primary);
    color: #fff;
}
.ji-btn-secondary {
    background: #7b8794;
    color: #fff;
}
.ji-btn-light {
    background: #e8eef6;
    color: #25364b;
}
.ji-btn-success {
    background: var(--ji-success);
    color: #fff;
}
.ji-btn-clear {
    background: #dfe8f3;
    color: #1e334c;
}
.ji-kpis {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-bottom: 14px;
}
.ji-kpi {
    padding: 14px 16px;
}
.ji-kpi span {
    color: var(--ji-muted);
    display: block;
    font-size: 0.78rem;
    font-weight: 900;
    text-transform: uppercase;
}
.ji-kpi strong {
    color: var(--ji-primary);
    display: block;
    font-size: 1.4rem;
    font-weight: 900;
    margin-top: 4px;
}
.ji-tabs :deep(.el-tabs__header) {
    margin-bottom: 12px;
}
.ji-tabs :deep(.el-tabs__item) {
    color: var(--ji-muted);
    font-size: 0.88rem;
    font-weight: 900;
    height: 38px;
    line-height: 38px;
    padding: 0 18px;
}
.ji-tabs :deep(.el-tabs__item.is-active) {
    color: var(--ji-primary);
}
.ji-card {
    padding: 16px;
}
.ji-active-list-card {
    padding: 8px;
}
.ji-card-head {
    align-items: flex-start;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
}
.ji-card-head h2 {
    font-size: 1.25rem;
    font-weight: 900;
    margin: 0 0 4px;
}
.ji-issue-grid,
.ji-tracking-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr);
}
.ji-form-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.ji-form :deep(.el-form-item),
.ji-card :deep(.el-form-item) {
    margin-bottom: 14px;
}
.ji-form :deep(.el-form-item__label),
.ji-card :deep(.el-form-item__label) {
    color: var(--ji-muted);
    font-size: 0.84rem;
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: 8px;
    padding-bottom: 0;
}
.ji-form :deep(.el-input),
.ji-form :deep(.el-select),
.ji-form :deep(.el-input-number),
.ji-form :deep(.el-date-editor),
.ji-card :deep(.el-input),
.ji-card :deep(.el-select),
.ji-card :deep(.el-input-number),
.ji-card :deep(.el-date-editor) {
    min-height: 48px;
    width: 100%;
}
.ji-form :deep(.el-input__wrapper),
.ji-form :deep(.el-select__wrapper),
.ji-form :deep(.el-input-number .el-input__wrapper),
.ji-card :deep(.el-input__wrapper),
.ji-card :deep(.el-select__wrapper),
.ji-card :deep(.el-input-number .el-input__wrapper) {
    border-radius: 8px;
    min-height: 48px;
    padding: 0 16px;
}
.ji-form :deep(.el-input__inner),
.ji-form :deep(.el-select__selected-item),
.ji-form :deep(.el-select__placeholder),
.ji-card :deep(.el-input__inner),
.ji-card :deep(.el-select__selected-item),
.ji-card :deep(.el-select__placeholder) {
    font-size: 1rem;
    font-weight: 800;
    line-height: 46px;
    min-height: 46px;
}
.ji-form :deep(.el-input__inner::placeholder),
.ji-card :deep(.el-input__inner::placeholder) {
    color: #8da0b8;
    font-size: 1rem;
    font-weight: 700;
    opacity: 1;
}
.ji-form :deep(.el-select__placeholder),
.ji-card :deep(.el-select__placeholder) {
    color: #8da0b8;
    font-size: 1rem;
    font-weight: 700;
}
.ji-preview {
    background: var(--ji-panel-soft);
    border: 1px solid var(--ji-border);
    border-radius: 8px;
    padding: 14px;
}
.ji-preview-label {
    color: var(--ji-muted);
    font-size: 0.74rem;
    font-weight: 900;
    text-transform: uppercase;
}
.ji-preview h3 {
    font-size: 1.25rem;
    font-weight: 900;
    margin: 8px 0 2px;
}
.ji-preview p {
    color: var(--ji-muted);
    font-weight: 800;
    margin-bottom: 12px;
}
.ji-spec-grid {
    display: grid;
    gap: 8px 12px;
    grid-template-columns: 110px minmax(0, 1fr);
}
.ji-spec-grid span {
    color: var(--ji-muted);
    font-weight: 900;
}
.ji-spec-grid strong {
    color: var(--ji-text);
}
.ji-filter-row {
    align-items: end;
    display: grid;
    gap: 7px;
    grid-template-columns: minmax(190px, 1fr) 150px 135px 70px 66px;
    margin-bottom: 8px;
}
.ji-filter-row.report {
    grid-template-columns: 180px 180px 240px 170px;
}
.ji-table {
    border-radius: 8px;
    overflow: hidden;
}
.ji-table :deep(th.el-table__cell) {
    background: #edf2f7 !important;
    color: #26384f !important;
    font-size: 0.7rem;
    font-weight: 900;
    letter-spacing: 0.04em;
    padding: 8px 0;
    text-transform: uppercase;
}
.ji-table :deep(td.el-table__cell) {
    color: var(--ji-text);
    font-size: 0.78rem;
    font-weight: 780;
    padding: 9px 0;
}
.ji-filter-row :deep(.el-input),
.ji-filter-row :deep(.el-select) {
    min-height: 32px;
}
.ji-filter-row :deep(.el-input__wrapper),
.ji-filter-row :deep(.el-select__wrapper) {
    border-radius: 7px;
    min-height: 32px;
    padding: 0 9px;
}
.ji-filter-row :deep(.el-input__inner),
.ji-filter-row :deep(.el-select__selected-item),
.ji-filter-row :deep(.el-select__placeholder) {
    font-size: 0.72rem;
    font-weight: 800;
    line-height: 30px;
    min-height: 30px;
}
.ji-filter-row .ji-btn {
    border-radius: 7px;
    font-size: 0.72rem;
    height: 32px;
    padding: 0 8px;
}
.ji-stage-badge,
.ji-status {
    border-radius: 999px;
    display: inline-flex;
    font-size: 0.66rem;
    font-weight: 900;
    justify-content: center;
    min-width: 72px;
    padding: 5px 7px;
}
.ji-stage-badge {
    background: #e8eef8;
    color: #24456f;
}
.ji-status.primary { background: #dbeafe; color: #1d4ed8; }
.ji-status.info { background: #e0f2fe; color: #0369a1; }
.ji-status.warning { background: #fef3c7; color: #92400e; }
.ji-status.success { background: #dcfce7; color: #047857; }
.ji-icon-btn {
    align-items: center;
    background: #e8eef6;
    border: 0;
    border-radius: 8px;
    color: #1f334d;
    display: inline-flex;
    font-size: 1rem;
    height: 40px;
    justify-content: center;
    transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
    width: 40px;
}
.ji-icon-btn:hover {
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.16);
    transform: translateY(-1px);
}
.ji-icon-btn.ji-icon-primary {
    background: var(--ji-primary);
    color: #fff;
}
.ji-icon-btn.ji-icon-success {
    background: var(--ji-success);
    color: #fff;
}
.ji-icon-btn.danger {
    background: #fee2e2;
    color: #b91c1c;
}
.ji-action-menu-btn {
    align-items: center;
    background: #e8eef6;
    border: 1px solid #d6e0ed;
    border-radius: 8px;
    color: #1f334d;
    display: inline-flex;
    font-size: 0.98rem;
    height: 32px;
    justify-content: center;
    transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
    width: 32px;
}
.ji-action-menu-btn:hover,
.ji-action-menu-btn:focus {
    background: #dce7f5;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
    outline: none;
    transform: translateY(-1px);
}
.ji-pagination {
    display: flex;
    justify-content: center;
    margin-top: 14px;
}
.ji-stage-flow {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-bottom: 14px;
}
.ji-stage-flow span {
    background: #e8eef6;
    border: 1px solid var(--ji-border);
    border-radius: 8px;
    color: var(--ji-muted);
    font-size: 0.78rem;
    font-weight: 900;
    padding: 9px;
    text-align: center;
}
.ji-stage-flow span.active {
    background: #2f74ed;
    color: #fff;
}
.ji-stage-flow span.done {
    background: #dcfce7;
    color: #047857;
}
.ji-subtitle {
    font-size: 1rem;
    font-weight: 900;
    margin: 10px 0 12px;
}
.ji-subpanel {
    background: var(--ji-panel-soft);
    border: 1px solid var(--ji-border);
    border-radius: 8px;
    margin-top: 12px;
    padding: 12px;
}
.ji-line-title {
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}
.ji-mini-btn {
    background: var(--ji-primary);
    border: 0;
    border-radius: 7px;
    color: #fff;
    font-size: 0.78rem;
    font-weight: 900;
    height: 34px;
    padding: 0 12px;
}
.ji-line-grid {
    align-items: end;
    display: grid;
    gap: 10px;
    margin-bottom: 10px;
    min-width: 0;
}
.ji-line-grid.reels {
    grid-template-columns: minmax(108px, 0.74fr) minmax(96px, 0.58fr) minmax(154px, 1fr) minmax(72px, 0.42fr) 30px;
}
.ji-line-grid.breakdowns {
    grid-template-columns: 1fr 1fr 180px 40px;
}
.ji-line-grid.wastages {
    grid-template-columns: 1fr 130px 1fr 40px;
}
.ji-tracking-grid .ji-subtitle {
    font-size: 1.06rem;
    margin: 8px 0 10px;
}
.ji-tracking-grid .ji-form-grid {
    gap: 10px 12px;
}
.ji-tracking-grid :deep(.el-form-item) {
    display: block;
    margin-bottom: 10px;
}
.ji-tracking-grid :deep(.el-form-item__label) {
    display: block;
    font-size: 0.78rem;
    height: auto;
    justify-content: flex-start;
    line-height: 1.2;
    margin-bottom: 6px;
    text-align: left;
    width: auto !important;
}
.ji-tracking-grid :deep(.el-form-item__content) {
    display: block;
    line-height: normal;
    margin-left: 0 !important;
    width: 100%;
}
.ji-tracking-grid :deep(.el-input),
.ji-tracking-grid :deep(.el-select),
.ji-tracking-grid :deep(.el-input-number),
.ji-tracking-grid :deep(.el-date-editor) {
    min-height: 42px;
}
.ji-tracking-grid :deep(.el-input__wrapper),
.ji-tracking-grid :deep(.el-select__wrapper),
.ji-tracking-grid :deep(.el-input-number .el-input__wrapper) {
    border-radius: 7px;
    min-height: 42px;
    padding: 0 12px;
}
.ji-tracking-grid :deep(.el-input__inner),
.ji-tracking-grid :deep(.el-select__selected-item),
.ji-tracking-grid :deep(.el-select__placeholder) {
    font-size: 0.9rem;
    font-weight: 750;
    line-height: 40px;
    min-height: 40px;
}
.ji-tracking-grid :deep(.el-input__inner::placeholder),
.ji-tracking-grid :deep(.el-select__placeholder) {
    font-size: 0.9rem;
    font-weight: 700;
}
.ji-tracking-grid .ji-subpanel {
    margin-top: 10px;
    padding: 10px;
}
.ji-tracking-grid .ji-line-title {
    margin-bottom: 8px;
}
.ji-tracking-grid .ji-line-title strong {
    font-size: 0.96rem;
}
.ji-tracking-grid .ji-mini-btn {
    height: 32px;
    padding: 0 11px;
}
.ji-tracking-grid .ji-line-grid {
    gap: 8px;
    margin-bottom: 8px;
}
.ji-tracking-grid .ji-line-grid.reels {
    gap: 7px;
}
.ji-tracking-grid .ji-line-grid > * {
    min-width: 0;
}
.ji-tracking-grid .ji-line-grid .ji-icon-btn {
    align-self: end;
    height: 34px;
    width: 34px;
}
.ji-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}
.ji-field label {
    color: var(--ji-muted);
    font-size: 0.72rem;
    font-weight: 900;
    line-height: 1.1;
    margin: 0;
    text-transform: uppercase;
}
.ji-line-grid.reels .ji-field {
    gap: 4px;
}
.ji-line-grid.reels .ji-field label {
    font-size: 0.66rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ji-line-grid.reels .ji-reel-no-field label,
.ji-line-grid.reels .ji-consumed-field label {
    font-size: 0.58rem;
    letter-spacing: 0.01em;
}
.ji-line-grid.reels :deep(.el-input),
.ji-line-grid.reels :deep(.el-select),
.ji-line-grid.reels :deep(.el-input-number) {
    min-height: 36px;
}
.ji-line-grid.reels :deep(.el-input__wrapper),
.ji-line-grid.reels :deep(.el-select__wrapper),
.ji-line-grid.reels :deep(.el-input-number .el-input__wrapper) {
    border-radius: 7px;
    min-height: 36px;
    padding: 0 9px;
}
.ji-line-grid.reels :deep(.el-input__inner),
.ji-line-grid.reels :deep(.el-select__selected-item),
.ji-line-grid.reels :deep(.el-select__placeholder) {
    font-size: 0.8rem;
    font-weight: 800;
    line-height: 34px;
    min-height: 34px;
}
.ji-line-grid.reels :deep(.el-input__inner::placeholder),
.ji-line-grid.reels :deep(.el-select__placeholder) {
    font-size: 0.78rem;
    font-weight: 800;
}
.ji-line-grid.reels .ji-reel-no-field :deep(.el-input__wrapper),
.ji-line-grid.reels .ji-consumed-field :deep(.el-input-number .el-input__wrapper) {
    padding: 0 7px;
}
.ji-line-grid.reels .ji-reel-no-field :deep(.el-input__inner),
.ji-line-grid.reels .ji-reel-no-field :deep(.el-input__inner::placeholder) {
    font-size: 0.74rem;
}
.ji-line-grid.reels .ji-consumed-field :deep(.el-input__inner),
.ji-line-grid.reels .ji-consumed-field :deep(.el-input__inner::placeholder) {
    font-size: 0.68rem;
    font-weight: 900;
    text-align: center;
}
.ji-line-grid.reels .ji-consumed-field :deep(.el-input-number) {
    width: 100%;
}
.ji-line-grid.reels .ji-icon-btn.danger {
    height: 34px;
    width: 30px;
}
.ji-quality-field {
    min-width: 0;
}
.ji-reel-quality {
    align-items: flex-start;
    background: var(--ji-field-bg);
    border: 1px solid var(--ji-border);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    justify-content: center;
    min-height: 36px;
    min-width: 0;
    padding: 5px 8px;
}
.ji-reel-quality strong {
    color: var(--ji-field-text);
    display: -webkit-box;
    font-size: 0.76rem;
    font-weight: 900;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-height: 1.12;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
}
.ji-reel-quality span {
    color: var(--ji-field-muted);
    font-size: 0.6rem;
    font-weight: 900;
    line-height: 1.1;
    text-transform: uppercase;
}
.ji-reel-quality.empty strong {
    color: #8da0b8;
    font-weight: 800;
}
.ji-consumption-summary {
    background: var(--ji-summary-panel-bg);
    border: 1px solid var(--ji-border);
    border-radius: 8px;
    margin-top: 10px;
    overflow: hidden;
}
.ji-summary-head {
    align-items: center;
    background: var(--ji-summary-head-bg);
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 10px;
}
.ji-summary-head strong {
    color: var(--ji-summary-head-text);
    font-size: 0.86rem;
    font-weight: 900;
}
.ji-summary-head span {
    color: var(--ji-summary-head-muted);
    font-size: 0.72rem;
    font-weight: 800;
}
.ji-summary-table {
    display: grid;
}
.ji-summary-row {
    align-items: center;
    background: var(--ji-summary-row-bg);
    display: grid;
    gap: 8px;
    grid-template-columns: minmax(120px, 1.2fr) repeat(4, minmax(72px, 0.7fr));
    padding: 7px 10px;
}
.ji-summary-row:not(:last-child) {
    border-bottom: 1px solid var(--ji-border);
}
.ji-summary-row span {
    color: var(--ji-summary-text);
    font-size: 0.78rem;
    font-weight: 800;
}
.ji-summary-row span:not(:first-child) {
    text-align: right;
}
.ji-summary-row.header,
.ji-summary-row.total {
    background: var(--ji-summary-header-bg);
}
.ji-summary-row.total {
    background: var(--ji-summary-total-bg);
}
.ji-summary-row.header span,
.ji-summary-row.total span {
    font-weight: 950;
}
.variance-over {
    color: #dc2626 !important;
}
.variance-under {
    color: #0f766e !important;
}
.variance-ok {
    color: #2563eb !important;
}
.ji-side-report {
    border-top: 1px solid var(--ji-border);
    margin-top: 14px;
    padding-top: 14px;
}
.ji-side-title {
    align-items: center;
    display: flex;
    gap: 8px;
    justify-content: space-between;
    margin-bottom: 10px;
}
.ji-side-title strong {
    font-size: 0.95rem;
    font-weight: 900;
}
.ji-consumption-status {
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 950;
    padding: 4px 8px;
}
.ji-consumption-status.variance-over {
    background: #fee2e2;
}
.ji-consumption-status.variance-under {
    background: #ccfbf1;
}
.ji-consumption-status.variance-ok {
    background: #dbeafe;
}
.ji-side-metrics {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.ji-side-metrics div {
    background: var(--ji-metric-card-bg);
    border: 1px solid var(--ji-border);
    border-radius: 8px;
    padding: 8px;
}
.ji-side-metrics span {
    color: var(--ji-metric-label);
    display: block;
    font-size: 0.68rem;
    font-weight: 900;
    text-transform: uppercase;
}
.ji-side-metrics strong {
    color: var(--ji-metric-value);
    display: block;
    font-size: 0.92rem;
    font-weight: 950;
    margin-top: 3px;
}
.ji-log-list {
    display: grid;
    gap: 8px;
}
.ji-log-item {
    align-items: center;
    background: var(--ji-panel-soft);
    border: 1px solid var(--ji-border);
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    padding: 10px;
}
.ji-log-item span {
    color: var(--ji-muted);
    display: block;
    font-size: 0.78rem;
    font-weight: 700;
}
.ji-empty,
.ji-empty-state {
    color: var(--ji-muted);
    font-weight: 800;
    text-align: center;
}
.ji-complete-box {
    align-items: center;
    background: #dcfce7;
    border: 1px solid #86efac;
    border-radius: 8px;
    color: #047857;
    display: flex;
    font-weight: 900;
    gap: 8px;
    justify-content: center;
    padding: 18px;
}

:global([data-theme="dark"]) .job-issue-workspace,
:global(body.dark-mode) .job-issue-workspace {
    --ji-bg: #0f172a;
    --ji-panel: #172033;
    --ji-panel-soft: #1f2c42;
    --ji-border: #41516a;
    --ji-text: #f8fafc;
    --ji-muted: #9db0ca;
    --ji-primary: #3478f6;
    --ji-field-bg: #142033;
    --ji-field-text: #f8fafc;
    --ji-field-muted: #9bb4d3;
    --ji-summary-panel-bg: #121d30;
    --ji-summary-head-bg: #1d2a40;
    --ji-summary-head-text: #f8fafc;
    --ji-summary-head-muted: #b8c9e5;
    --ji-summary-row-bg: #162238;
    --ji-summary-header-bg: #223149;
    --ji-summary-total-bg: #263b5a;
    --ji-summary-text: #f1f7ff;
    --ji-metric-card-bg: #17243a;
    --ji-metric-label: #a9bedc;
    --ji-metric-value: #f8fafc;
    background: #0f172a;
}
:global([data-theme="dark"] .job-issue-workspace),
:global(body.dark-mode .job-issue-workspace) {
    background: #0f172a !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-header),
:global([data-theme="dark"] .job-issue-workspace .ji-card),
:global([data-theme="dark"] .job-issue-workspace .ji-kpi),
:global(body.dark-mode .job-issue-workspace .ji-header),
:global(body.dark-mode .job-issue-workspace .ji-card),
:global(body.dark-mode .job-issue-workspace .ji-kpi) {
    background: #172033 !important;
    border-color: #41516a !important;
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.18) !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-header h1),
:global([data-theme="dark"] .job-issue-workspace .ji-card-head h2),
:global([data-theme="dark"] .job-issue-workspace .ji-kpi strong),
:global(body.dark-mode .job-issue-workspace .ji-header h1),
:global(body.dark-mode .job-issue-workspace .ji-card-head h2),
:global(body.dark-mode .job-issue-workspace .ji-kpi strong) {
    color: #f8fafc !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-header p),
:global([data-theme="dark"] .job-issue-workspace .ji-card-head p),
:global([data-theme="dark"] .job-issue-workspace .ji-kpi span),
:global([data-theme="dark"] .job-issue-workspace .ji-form .el-form-item__label),
:global([data-theme="dark"] .job-issue-workspace .ji-card .el-form-item__label),
:global(body.dark-mode .job-issue-workspace .ji-header p),
:global(body.dark-mode .job-issue-workspace .ji-card-head p),
:global(body.dark-mode .job-issue-workspace .ji-kpi span),
:global(body.dark-mode .job-issue-workspace .ji-form .el-form-item__label),
:global(body.dark-mode .job-issue-workspace .ji-card .el-form-item__label) {
    color: #b7c7dd !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-eyebrow),
:global(body.dark-mode .job-issue-workspace .ji-eyebrow) {
    color: #70a8ff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-kpi strong),
:global(body.dark-mode .job-issue-workspace .ji-kpi strong) {
    color: #70f0b3 !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-preview),
:global([data-theme="dark"] .job-issue-workspace .ji-subpanel),
:global([data-theme="dark"] .job-issue-workspace .ji-log-item),
:global(body.dark-mode .job-issue-workspace .ji-preview),
:global(body.dark-mode .job-issue-workspace .ji-subpanel),
:global(body.dark-mode .job-issue-workspace .ji-log-item) {
    background: #1f2c42 !important;
    border-color: #41516a !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-tabs .el-tabs__item),
:global(body.dark-mode .job-issue-workspace .ji-tabs .el-tabs__item) {
    color: #a9bad1 !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-tabs .el-tabs__item.is-active),
:global(body.dark-mode .job-issue-workspace .ji-tabs .el-tabs__item.is-active) {
    color: #8ea2ff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-tabs .el-tabs__nav-wrap::after),
:global(body.dark-mode .job-issue-workspace .ji-tabs .el-tabs__nav-wrap::after) {
    background-color: #41516a !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-btn-clear),
:global(body.dark-mode .job-issue-workspace .ji-btn-clear) {
    background: #263449 !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-btn-secondary),
:global(body.dark-mode .job-issue-workspace .ji-btn-secondary) {
    background: #53647b !important;
    border: 1px solid #71839d !important;
    color: #ffffff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-btn-secondary:hover),
:global(body.dark-mode .job-issue-workspace .ji-btn-secondary:hover) {
    background: #64748b !important;
    color: #ffffff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-btn-light),
:global(body.dark-mode .job-issue-workspace .ji-btn-light) {
    background: #263449 !important;
    border: 1px solid #41516a !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-btn-light:hover),
:global(body.dark-mode .job-issue-workspace .ji-btn-light:hover) {
    background: #334155 !important;
    color: #ffffff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-stage-badge),
:global(body.dark-mode .job-issue-workspace .ji-stage-badge) {
    background: #263449 !important;
    color: #dbeafe !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-status),
:global(body.dark-mode .job-issue-workspace .ji-status) {
    border: 1px solid transparent !important;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-status.primary),
:global(body.dark-mode .job-issue-workspace .ji-status.primary) {
    background: #1e3a8a !important;
    border-color: #3b82f6 !important;
    color: #eff6ff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-status.info),
:global(body.dark-mode .job-issue-workspace .ji-status.info) {
    background: #164e63 !important;
    border-color: #22d3ee !important;
    color: #ecfeff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-status.warning),
:global(body.dark-mode .job-issue-workspace .ji-status.warning) {
    background: #78350f !important;
    border-color: #f59e0b !important;
    color: #fffbeb !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-status.success),
:global(body.dark-mode .job-issue-workspace .ji-status.success) {
    background: #065f46 !important;
    border-color: #34d399 !important;
    color: #ecfdf5 !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-icon-btn),
:global(body.dark-mode .job-issue-workspace .ji-icon-btn) {
    background: #2b3950 !important;
    border: 1px solid #43546d !important;
    color: #e5edf8 !important;
    box-shadow: none !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-icon-btn:hover),
:global(body.dark-mode .job-issue-workspace .ji-icon-btn:hover) {
    background: #354760 !important;
    border-color: #5b6f8d !important;
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.28) !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-icon-btn.ji-icon-primary),
:global(body.dark-mode .job-issue-workspace .ji-icon-btn.ji-icon-primary) {
    background: #2563eb !important;
    border-color: #60a5fa !important;
    color: #ffffff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-icon-btn.ji-icon-success),
:global(body.dark-mode .job-issue-workspace .ji-icon-btn.ji-icon-success) {
    background: #059669 !important;
    border-color: #34d399 !important;
    color: #ffffff !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-icon-btn:disabled),
:global([data-theme="dark"] .job-issue-workspace .ji-icon-btn[disabled]),
:global(body.dark-mode .job-issue-workspace .ji-icon-btn:disabled),
:global(body.dark-mode .job-issue-workspace .ji-icon-btn[disabled]) {
    background: #243044 !important;
    border-color: #334155 !important;
    color: #7f91ad !important;
    cursor: not-allowed !important;
    opacity: 1 !important;
    transform: none !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-action-menu-btn),
:global(body.dark-mode .job-issue-workspace .ji-action-menu-btn) {
    background: #2b3950 !important;
    border-color: #43546d !important;
    color: #f8fafc !important;
    box-shadow: none !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-action-menu-btn:hover),
:global([data-theme="dark"] .job-issue-workspace .ji-action-menu-btn:focus),
:global(body.dark-mode .job-issue-workspace .ji-action-menu-btn:hover),
:global(body.dark-mode .job-issue-workspace .ji-action-menu-btn:focus) {
    background: #354760 !important;
    border-color: #5b6f8d !important;
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.28) !important;
}
:global(.ji-actions-dropdown) {
    border-radius: 8px !important;
    overflow: hidden;
}
:global(.ji-actions-dropdown .el-dropdown-menu__item) {
    font-size: 0.92rem;
    font-weight: 800;
    min-width: 170px;
    padding: 10px 14px;
}
:global([data-theme="dark"] .ji-actions-dropdown),
:global(body.dark-mode .ji-actions-dropdown) {
    background: #172033 !important;
    border: 1px solid #41516a !important;
}
:global([data-theme="dark"] .ji-actions-dropdown .el-dropdown-menu),
:global(body.dark-mode .ji-actions-dropdown .el-dropdown-menu) {
    background: #172033 !important;
}
:global([data-theme="dark"] .ji-actions-dropdown .el-dropdown-menu__item),
:global(body.dark-mode .ji-actions-dropdown .el-dropdown-menu__item) {
    color: #f8fafc !important;
}
:global([data-theme="dark"] .ji-actions-dropdown .el-dropdown-menu__item:hover),
:global(body.dark-mode .ji-actions-dropdown .el-dropdown-menu__item:hover) {
    background: #263449 !important;
    color: #ffffff !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-header,
:global([data-theme="dark"]) .job-issue-workspace .ji-card,
:global([data-theme="dark"]) .job-issue-workspace .ji-kpi,
:global(body.dark-mode) .job-issue-workspace .ji-header,
:global(body.dark-mode) .job-issue-workspace .ji-card,
:global(body.dark-mode) .job-issue-workspace .ji-kpi {
    box-shadow: none;
}
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input__wrapper),
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-select__wrapper),
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input-number .el-input__wrapper),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input__wrapper),
:global(body.dark-mode) .job-issue-workspace :deep(.el-select__wrapper),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input-number .el-input__wrapper) {
    background: #111827 !important;
    border: 1px solid var(--ji-border) !important;
    box-shadow: none !important;
}
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input__wrapper.is-focus),
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-select__wrapper.is-focused),
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input-number .el-input__wrapper.is-focus),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input__wrapper.is-focus),
:global(body.dark-mode) .job-issue-workspace :deep(.el-select__wrapper.is-focused),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input-number .el-input__wrapper.is-focus) {
    border-color: #6ea8ff !important;
    box-shadow: 0 0 0 0.18rem rgba(110, 168, 255, 0.16) !important;
}
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input.is-disabled .el-input__wrapper),
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input-number.is-disabled .el-input__wrapper),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input.is-disabled .el-input__wrapper),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input-number.is-disabled .el-input__wrapper) {
    background: #1f2937 !important;
    border-color: #334155 !important;
}
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input__inner),
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-select__selected-item),
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-select__placeholder),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input__inner),
:global(body.dark-mode) .job-issue-workspace :deep(.el-select__selected-item),
:global(body.dark-mode) .job-issue-workspace :deep(.el-select__placeholder) {
    color: #f8fafc !important;
}
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input__inner::placeholder),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input__inner::placeholder) {
    color: #94a3b8 !important;
}
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input.is-disabled .el-input__inner),
:global([data-theme="dark"]) .job-issue-workspace :deep(.el-input-number.is-disabled .el-input__inner),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input.is-disabled .el-input__inner),
:global(body.dark-mode) .job-issue-workspace :deep(.el-input-number.is-disabled .el-input__inner) {
    color: #cbd5e1 !important;
    -webkit-text-fill-color: #cbd5e1 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-table :deep(th.el-table__cell),
:global(body.dark-mode) .job-issue-workspace .ji-table :deep(th.el-table__cell) {
    background: #1f2c42 !important;
    color: #dbeafe !important;
    border-color: var(--ji-border) !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-reel-quality,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-reel-quality,
:global(body.dark-mode) .job-issue-workspace .ji-reel-quality {
    background: #17243a !important;
    border-color: #4b5f7a !important;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.08) !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-reel-quality strong,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-reel-quality strong,
:global(body.dark-mode) .job-issue-workspace .ji-reel-quality strong {
    color: #eaf2ff !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-reel-quality span,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-reel-quality span,
:global(body.dark-mode) .job-issue-workspace .ji-reel-quality span {
    color: #8fb3e8 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-reel-quality.empty strong,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-reel-quality.empty strong,
:global(body.dark-mode) .job-issue-workspace .ji-reel-quality.empty strong {
    color: #aebdd1 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-field label,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-field label,
:global(body.dark-mode) .job-issue-workspace .ji-field label {
    color: #b7c7dd !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-icon-btn.danger,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-icon-btn.danger,
:global(body.dark-mode) .job-issue-workspace .ji-icon-btn.danger {
    background: #7f1d1d !important;
    border-color: #ef4444 !important;
    color: #fee2e2 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-icon-btn.danger:hover,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-icon-btn.danger:hover,
:global(body.dark-mode) .job-issue-workspace .ji-icon-btn.danger:hover {
    background: #991b1b !important;
    border-color: #f87171 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-consumption-summary,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-consumption-summary,
:global(body.dark-mode) .job-issue-workspace .ji-consumption-summary {
    background: #172033 !important;
    border-color: #4b5f7a !important;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18) !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-summary-head,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-summary-head,
:global(body.dark-mode) .job-issue-workspace .ji-summary-head,
:global(body.dark-mode) .job-issue-workspace .ji-summary-head {
    background: #24344d !important;
    border-bottom: 1px solid #4b5f7a !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-summary-row.header,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-summary-row.header,
:global(body.dark-mode) .job-issue-workspace .ji-summary-row.header {
    background: #101a2b !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-summary-row.total,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-summary-row.total,
:global(body.dark-mode) .job-issue-workspace .ji-summary-row.total {
    background: #21314b !important;
    border-top: 1px solid #6b7f99 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-summary-head strong,
:global([data-theme="dark"]) .job-issue-workspace .ji-summary-row span,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-summary-head strong,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-summary-row span,
:global(body.dark-mode) .job-issue-workspace .ji-summary-head strong,
:global(body.dark-mode) .job-issue-workspace .ji-summary-row span {
    color: #eaf2ff !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-summary-head span,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-summary-head span,
:global(body.dark-mode) .job-issue-workspace .ji-summary-head span {
    color: #b8c9e5 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-summary-row:not(:last-child),
:global(html[data-theme="dark"]) .job-issue-workspace .ji-summary-row:not(:last-child),
:global(body.dark-mode) .job-issue-workspace .ji-summary-row:not(:last-child) {
    border-bottom-color: #40536e !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-summary-row.total span,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-summary-row.total span,
:global(body.dark-mode) .job-issue-workspace .ji-summary-row.total span {
    color: #f8fafc !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-side-metrics div,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-side-metrics div,
:global(body.dark-mode) .job-issue-workspace .ji-side-metrics div {
    background: #17243a !important;
    border-color: #4b5f7a !important;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.07) !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-side-metrics span,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-side-metrics span,
:global(body.dark-mode) .job-issue-workspace .ji-side-metrics span {
    color: #a9bedc !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-side-metrics strong,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-side-metrics strong,
:global(body.dark-mode) .job-issue-workspace .ji-side-metrics strong {
    color: #eaf2ff !important;
}
:global([data-theme="dark"]) .job-issue-workspace .variance-over,
:global(html[data-theme="dark"]) .job-issue-workspace .variance-over,
:global(body.dark-mode) .job-issue-workspace .variance-over {
    color: #ff8a8a !important;
}
:global([data-theme="dark"]) .job-issue-workspace .variance-under,
:global(html[data-theme="dark"]) .job-issue-workspace .variance-under,
:global(body.dark-mode) .job-issue-workspace .variance-under {
    color: #5eead4 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .variance-ok,
:global(html[data-theme="dark"]) .job-issue-workspace .variance-ok,
:global(body.dark-mode) .job-issue-workspace .variance-ok {
    color: #93c5fd !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-consumption-status.variance-ok,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-consumption-status.variance-ok,
:global(body.dark-mode) .job-issue-workspace .ji-consumption-status.variance-ok {
    background: #1e3a8a !important;
    border: 1px solid #60a5fa !important;
    color: #eff6ff !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-consumption-status.variance-over,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-consumption-status.variance-over,
:global(body.dark-mode) .job-issue-workspace .ji-consumption-status.variance-over {
    background: #7f1d1d !important;
    border: 1px solid #f87171 !important;
    color: #fee2e2 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-consumption-status.variance-under,
:global(html[data-theme="dark"]) .job-issue-workspace .ji-consumption-status.variance-under,
:global(body.dark-mode) .job-issue-workspace .ji-consumption-status.variance-under {
    background: #134e4a !important;
    border: 1px solid #5eead4 !important;
    color: #ccfbf1 !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-table .el-table),
:global([data-theme="dark"] .job-issue-workspace .ji-table .el-table__inner-wrapper),
:global([data-theme="dark"] .job-issue-workspace .ji-table .el-table__header-wrapper),
:global([data-theme="dark"] .job-issue-workspace .ji-table .el-table__body-wrapper),
:global([data-theme="dark"] .job-issue-workspace .ji-table .el-table__empty-block),
:global([data-theme="dark"] .job-issue-workspace .ji-table tr),
:global(body.dark-mode .job-issue-workspace .ji-table .el-table),
:global(body.dark-mode .job-issue-workspace .ji-table .el-table__inner-wrapper),
:global(body.dark-mode .job-issue-workspace .ji-table .el-table__header-wrapper),
:global(body.dark-mode .job-issue-workspace .ji-table .el-table__body-wrapper),
:global(body.dark-mode .job-issue-workspace .ji-table .el-table__empty-block),
:global(body.dark-mode .job-issue-workspace .ji-table tr) {
    background: #172033 !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-table th.el-table__cell),
:global(body.dark-mode .job-issue-workspace .ji-table th.el-table__cell) {
    background: #1f2c42 !important;
    border-color: #41516a !important;
    color: #dbeafe !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-table td.el-table__cell),
:global(body.dark-mode .job-issue-workspace .ji-table td.el-table__cell) {
    background: #172033 !important;
    border-color: #41516a !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-table .el-table__row:hover td.el-table__cell),
:global(body.dark-mode .job-issue-workspace .ji-table .el-table__row:hover td.el-table__cell) {
    background: #263449 !important;
}
:global([data-theme="dark"] .job-issue-workspace .ji-table .el-table__empty-text),
:global(body.dark-mode .job-issue-workspace .ji-table .el-table__empty-text) {
    color: #cbd5e1 !important;
}
:global([data-theme="dark"]) .job-issue-workspace .ji-table :deep(td.el-table__cell),
:global([data-theme="dark"]) .job-issue-workspace .ji-table :deep(.el-table__body-wrapper),
:global([data-theme="dark"]) .job-issue-workspace .ji-table :deep(.el-table__empty-block),
:global([data-theme="dark"]) .job-issue-workspace .ji-table :deep(tr),
:global(body.dark-mode) .job-issue-workspace .ji-table :deep(td.el-table__cell),
:global(body.dark-mode) .job-issue-workspace .ji-table :deep(.el-table__body-wrapper),
:global(body.dark-mode) .job-issue-workspace .ji-table :deep(.el-table__empty-block),
:global(body.dark-mode) .job-issue-workspace .ji-table :deep(tr) {
    background: #172033 !important;
    border-color: var(--ji-border) !important;
    color: #f8fafc !important;
}

@media (max-width: 1100px) {
    .ji-issue-grid,
    .ji-tracking-grid {
        grid-template-columns: 1fr;
    }
    .ji-filter-row,
    .ji-filter-row.report {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .ji-line-grid.reels {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .job-issue-workspace {
        padding: 10px;
    }
    .ji-header,
    .ji-card-head,
    .ji-header-actions,
    .ji-actions {
        align-items: stretch;
        flex-direction: column;
    }
    .ji-kpis,
    .ji-form-grid,
    .ji-filter-row,
    .ji-filter-row.report,
    .ji-stage-flow,
    .ji-line-grid.reels,
    .ji-line-grid.breakdowns,
    .ji-line-grid.wastages {
        grid-template-columns: 1fr;
    }
    .ji-summary-row {
        grid-template-columns: minmax(95px, 1.1fr) repeat(4, minmax(58px, 0.7fr));
    }
}
</style>
