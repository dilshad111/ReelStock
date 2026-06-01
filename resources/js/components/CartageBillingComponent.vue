<template>
    <div class="cartage-billing">
        <el-card class="box-card shadow-sm" v-if="!printing">
            <template #header>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold">
                        <i class="bi" :class="viewHistory ? 'bi-list-ul' : 'bi-receipt'"></i> 
                        {{ viewHistory ? 'Cartage Bill List' : 'Cartage Entry & Billing' }}
                        <el-tag v-if="!viewHistory && nextBillId" type="success" effect="dark" class="ms-3 fs-6">BILL #{{ formatBillId(nextBillId) }}</el-tag>
                    </span>
                    <el-button :type="viewHistory ? 'primary' : 'info'" @click="toggleHistory" plain>
                        <i class="bi" :class="viewHistory ? 'bi-plus-lg' : 'bi-clock-history'"></i>
                        {{ viewHistory ? ' New Entry' : ' View History' }}
                    </el-button>
                </div>
            </template>

            <div v-if="!viewHistory">
                <el-form :model="mainForm" label-position="top" :rules="mainRules" ref="mainFormRef">
                    <div class="row main-form-row p-3 rounded mb-4 border">
                        <div class="col-md-4">
                            <el-form-item label="Transporter Name" prop="transporter_id">
                                <el-select v-model="mainForm.transporter_id" placeholder="Select Transporter" class="w-100" @change="onTransporterChange" filterable>
                                    <el-option v-for="t in transporters" :key="t.id" :label="t.name" :value="t.id" />
                                </el-select>
                            </el-form-item>
                        </div>
                        <div class="col-md-3">
                            <el-form-item label="Billing Date" prop="bill_date">
                                <input v-model="mainForm.bill_date" type="date" class="form-control" required />
                            </el-form-item>
                        </div>
                        <div class="col-md-5">
                            <el-form-item label="Bill To Entity" prop="bill_to">
                                <el-input v-model="mainForm.bill_to" placeholder="Entity name" />
                            </el-form-item>
                        </div>
                    </div>

                    <div class="table-responsive">
                    <el-table :data="mainForm.entries" border class="mb-4 entry-table" :row-class-name="tableRowClassName" :span-method="arraySpanMethod">
                        <el-table-column label="S. No." width="60" align="center">
                            <template #default="scope">
                                {{ scope.$index + 1 }}
                            </template>
                        </el-table-column>
                        <el-table-column label="Date" width="160">
                            <template #default="scope">
                                <input v-model="scope.row.entry_date" type="date" class="form-control form-control-sm w-100" required />
                            </template>
                        </el-table-column>
                        <el-table-column label="Customer" min-width="180">
                            <template #default="scope">
                                <el-select v-model="scope.row.customer_id" placeholder="Select Customer" size="small" class="w-100" @change="onRowCustomerChange(scope.row)" filterable>
                                    <el-option v-for="c in customers" :key="c.id" :label="c.name" :value="c.id" />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="Shipping Address" min-width="180">
                            <template #default="scope">
                                <el-select v-model="scope.row.shipping_address_id" placeholder="Select Address" size="small" class="w-100" :disabled="!scope.row.customer_id" @change="fetchRateForRow(scope.row)" filterable>
                                    <el-option v-for="a in getAddressesForCustomer(scope.row.customer_id)" :key="a.id" :label="a.address_name" :value="a.id" />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="Vehicle #" min-width="130">
                            <template #default="scope">
                                <el-select v-model="scope.row.vehicle_id" placeholder="Vehicle" size="small" class="w-100" :disabled="!mainForm.transporter_id" @change="onVehicleChange(scope.row)" filterable>
                                    <el-option v-for="v in filteredVehicles" :key="v.id" :label="v.vehicle_number" :value="v.id" />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="DC #" width="110">
                            <template #default="scope">
                                <el-input v-model="scope.row.dc_number" placeholder="DC #" size="small" />
                            </template>
                        </el-table-column>
                        <el-table-column label="Slip #" width="110">
                            <template #default="scope">
                                <el-input v-model="scope.row.slip_no" placeholder="Slip #" size="small" />
                            </template>
                        </el-table-column>
                        <el-table-column label="Options" width="220" v-if="!isEditing || true">
                            <template #default="scope">
                                <div v-if="!scope.row.is_sub_row" class="d-flex flex-column gap-1">
                                    <el-checkbox v-model="scope.row.is_return_checkbox" size="small" @change="handleReturnChange(scope.row, scope.$index)">Return Cartage</el-checkbox>
                                    <el-checkbox v-model="scope.row.is_second_location_checkbox" size="small" @change="handleSecondLocationChange(scope.row, scope.$index)">Second Location</el-checkbox>
                                </div>
                                <div v-else class="ps-2">
                                    <el-input v-model="scope.row.remarks" size="small" placeholder="Remarks" />
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="Amount (Rs.)" width="130">
                            <template #default="scope">
                                <el-input-number v-model="scope.row.amount" :min="0" :controls="false" size="small" class="w-100 amount-input" />
                            </template>
                        </el-table-column>
                        <el-table-column label="Action" width="60" align="center">
                            <template #default="scope">
                                <el-button type="danger" circle size="small" @click="removeRow(scope.$index)" plain :disabled="scope.row.is_sub_row">
                                    <i class="bi bi-trash"></i>
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 bg-white border rounded">
                        <el-button type="primary" @click="addRow" plain>
                            <i class="bi bi-plus-lg me-1"></i> Add Record
                        </el-button>
                        <div class="text-end">
                            <span class="text-muted me-3">Total Entries: {{ mainForm.entries.length }}</span>
                            <span class="fs-4 fw-bold text-primary grand-total-text">GRAND TOTAL: Rs. {{ grandTotal.toLocaleString() }}</span>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <el-button type="success" size="large" @click="saveAndPrint" :loading="submitting" class="px-5 shadow-sm">
                            <i class="bi bi-printer-fill me-2"></i> SAVE & GENERATE BILL
                        </el-button>
                    </div>
                </el-form>
            </div>

            <!-- History View -->
            <div v-else>
                <div class="filters-bar mb-3 p-3 bg-light rounded border d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2 flex-grow-1">
                        <el-input v-model="historyFilters.bill_id" placeholder="Filter by Bill #" clearable style="width: 150px">
                            <template #prefix><i class="bi bi-search"></i></template>
                        </el-input>
                        <el-input v-model="historyFilters.transporter" placeholder="Transporter Name..." clearable style="width: 220px" />
                        <el-input v-model="historyFilters.bill_to" placeholder="Bill To Entity..." clearable style="width: 220px" />
                    </div>
                    <div class="d-flex gap-2">
                        <el-button type="success" @click="exportHistoryExcel" plain title="Export to Excel">
                            <i class="bi bi-file-earmark-excel"></i>
                        </el-button>
                        <el-button type="info" @click="printHistory" plain title="Print List">
                            <i class="bi bi-printer"></i>
                        </el-button>
                    </div>
                </div>
                <el-table :data="filteredHistory" style="width: 100%" v-loading="loading" class="entry-table">
                    <el-table-column label="S. No." width="60" align="center">
                        <template #default="scope">
                            {{ scope.$index + 1 }}
                        </template>
                    </el-table-column>
                    <el-table-column prop="id" label="Bill #" width="100" sortable>
                        <template #default="scope">
                            <span class="bill-badge">{{ formatBillId(scope.row.id) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="Billing Date" width="110" sortable>
                        <template #default="scope">
                            <span class="text-slate-600">{{ formatDate(scope.row.bill_date) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="transporter.name" label="Transporter" min-width="180" show-overflow-tooltip>
                         <template #default="scope">
                            <span class="fw-600">{{ scope.row.transporter?.name }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="total_amount" label="Bill Amount" width="140" align="right">
                        <template #default="scope">
                            <span class="amount-text">Rs. {{ scope.row.total_amount.toLocaleString() }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="tax_amount" label="Tax Deducted" width="130" align="right">
                        <template #default="scope">
                            <span class="text-danger fw-600" v-if="scope.row.tax_amount > 0">Rs. {{ scope.row.tax_amount.toLocaleString() }}</span>
                            <span class="text-muted" v-else>—</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="approver.name" label="Approval User" width="160" show-overflow-tooltip>
                        <template #default="scope">
                            <div v-if="scope.row.approver" class="d-flex align-items-center">
                                <i class="bi bi-person-check-fill text-success me-2"></i>
                                <span class="small fw-500">{{ scope.row.approver.name }}</span>
                            </div>
                            <span v-else class="text-muted small italic">Pending Approval</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="status" label="Status" width="100" align="center">
                        <template #default="scope">
                            <el-tag :type="scope.row.status === 'Approved' ? 'success' : 'warning'" effect="dark" round size="small" class="status-tag">
                                {{ scope.row.status }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="Actions" width="110" align="center">
                        <template #default="scope">
                            <el-dropdown trigger="click">
                                <el-button type="primary" size="small" class="action-dropdown-btn">
                                    Actions <i class="bi bi-chevron-down ms-1"></i>
                                </el-button>
                                <template #dropdown>
                                    <el-dropdown-menu class="modern-dropdown">
                                        <el-dropdown-item v-if="scope.row.status === 'Pending' && canApprove" @click="openApproveDialog(scope.row)">
                                            <i class="bi bi-check-circle-fill text-success me-2"></i> Approve Bill
                                        </el-dropdown-item>
                                        <el-dropdown-item v-if="scope.row.status === 'Approved' && canUnapprove" @click="unapproveBill(scope.row)">
                                            <i class="bi bi-arrow-counterclockwise text-danger me-2"></i> Unapprove Bill
                                        </el-dropdown-item>
                                        <el-dropdown-item @click="viewBillDetails(scope.row)">
                                            <i class="bi bi-eye-fill text-info me-2"></i> View Details
                                        </el-dropdown-item>
                                        <el-dropdown-item :disabled="scope.row.status === 'Approved'" @click="editBill(scope.row)">
                                            <i class="bi bi-pencil-fill text-warning me-2"></i> Edit Bill
                                        </el-dropdown-item>
                                        <el-dropdown-item @click="printExisting(scope.row)">
                                            <i class="bi bi-printer-fill text-primary me-2"></i> Print Bill
                                        </el-dropdown-item>
                                        <el-dropdown-item divided :disabled="scope.row.status === 'Approved'" @click="deleteBill(scope.row)" class="text-danger">
                                            <i class="bi bi-trash-fill me-2"></i> Delete Bill
                                        </el-dropdown-item>
                                    </el-dropdown-menu>
                                </template>
                            </el-dropdown>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
        </el-card>

        <div id="print-preview-overlay" v-if="printing">
            <div class="preview-actions py-3 px-4 bg-dark text-white d-flex justify-content-between align-items-center">
                <span class="fs-5 fw-bold"><i class="bi bi-eye me-2"></i> Print Preview (A4 Portrait)</span>
                <el-button @click="printing = false" type="danger" plain>Close Preview</el-button>
            </div>
            <div class="preview-scroll-area">
                <div id="print-area" class="print-container">
            <div class="print-header position-relative text-center mb-4">
                <div class="logo-box-print" v-if="currentBill.transporter?.logo_url">
                    <img :src="currentBill.transporter.logo_url" alt="Logo">
                </div>
                <div class="transporter-name-container">
                    <div class="transporter-name">{{ currentBill.transporter?.name }}</div>
                    <div class="transporter-meta">
                        <span v-if="currentBill.transporter?.address" class="me-3"><i class="bi bi-geo-alt"></i> {{ currentBill.transporter.address }}</span>
                        <span v-if="currentBill.transporter?.phone"><i class="bi bi-telephone"></i> {{ currentBill.transporter.phone }}</span>
                    </div>
                </div>
            </div>
            
            <div class="bill-title">CARTAGE BILLING</div>

            <div class="print-meta">
                <div class="bill-to-box">
                    <div class="label text-muted mb-1">BILL TO:</div>
                    <div class="entity-name">{{ currentBill.bill_to }}</div>
                    <div class="entity-address text-muted small">Plot #46, Sector 24, Korangi Industrial Area Karachi.</div>
                </div>
                <div class="bill-info-box">
                    <div class="info-row d-flex justify-content-between gap-3"><span>Bill No:</span> <strong class="fs-5">{{ formatBillId(currentBill.id) }}</strong></div>
                    <div class="info-row d-flex justify-content-between gap-3"><span>Date:</span> <strong>{{ formatDate(currentBill.bill_date) }}</strong></div>
                    <div class="info-row d-flex justify-content-between gap-3 mt-1 pt-1 border-top"><span>Status:</span> <span class="badge-print">ORIGINAL</span></div>
                </div>
            </div>

            <table class="print-table">
                <thead>
                    <tr>
                        <th width="60">S. No.</th>
                        <th>Date</th>
                        <th>Customer / Location</th>
                        <th>Vehicle #</th>
                        <th>DC #</th>
                        <th>Slip #</th>
                        <th class="text-end">Amount (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(entry, index) in currentBill.entries" :key="entry.id || entry.temp_id" :class="{'sub-row-print': isSameAsParent(entry)}">
                        <td align="center" :style="isSameAsParent(entry) ? 'border-top: none;' : ''">{{ index + 1 }}</td>
                        <td :style="isSameAsParent(entry, 'entry_date') ? 'border-top: none;' : ''">
                            <span v-if="!isSameAsParent(entry, 'entry_date')">{{ formatDate(entry.entry_date) }}</span>
                        </td>
                        <td>
                            <div class="fw-bold">{{ entry.customer?.name }}</div>
                            <div class="small opacity-75">
                                {{ entry.shipping_address?.address_name }}
                                <span v-if="entry.is_return" class="ms-2 px-1 fw-bold" style="background: #000; color: #fff; border-radius: 2px;">
                                    ({{ entry.remarks || 'Return Cartage' }})
                                </span>
                                <span v-else-if="entry.is_second_location" class="ms-2 px-1 fw-bold" style="background: #000; color: #fff; border-radius: 2px;">
                                    ({{ entry.remarks || 'Second Location' }})
                                </span>
                            </div>
                        </td>
                        <td :style="isSameAsParent(entry, 'vehicle_number') ? 'border-top: none;' : ''">
                            <span v-if="!isSameAsParent(entry, 'vehicle_number')">{{ entry.vehicle_number }}</span>
                        </td>
                        <td :style="isSameAsParent(entry, 'dc_number') ? 'border-top: none;' : ''">
                            <span v-if="!isSameAsParent(entry, 'dc_number')">{{ entry.dc_number }}</span>
                        </td>
                        <td :style="isSameAsParent(entry, 'slip_no') ? 'border-top: none;' : ''">
                            <span v-if="!isSameAsParent(entry, 'slip_no')">{{ entry.slip_no }}</span>
                        </td>
                        <td class="text-end fw-bold">{{ entry.amount.toLocaleString() }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="print-footer-container px-4">
                <!-- Summary Section: Positioned ~15mm above signatures -->
                <div class="print-summary-section mb-2">
                    <div class="row g-0 justify-content-end">
                        <div class="col-5">
                            <div class="summary-box border p-2">
                                <div class="d-flex justify-content-between mb-0 small">
                                    <span>Gross Amount:</span>
                                    <span class="fw-bold">Rs. {{ currentBill.total_amount?.toLocaleString() }}</span>
                                </div>
                                <div v-if="currentBill.tax_amount > 0" class="d-flex justify-content-between mb-0 text-danger small">
                                    <span>{{ currentBill.tax_type }} ({{ currentBill.tax_percentage }}%):</span>
                                    <span>- Rs. {{ currentBill.tax_amount?.toLocaleString() }}</span>
                                </div>
                                <div class="d-flex justify-content-between pt-1 border-top">
                                    <span class="fw-800 small">NET PAYABLE:</span>
                                    <span class="fw-800 small">Rs. {{ (currentBill.net_amount || currentBill.total_amount)?.toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="total-words-box border p-1 mb-2">
                    <strong>In Words:</strong> <span class="small">{{ amountToWords(currentBill.net_amount || currentBill.total_amount) }}</span>
                </div>

                <!-- Signatures -->
                <div class="print-signatures d-flex justify-content-between pt-2">
                    <div class="signature-section">
                        <div class="sig-line"></div>
                        <p class="small fw-bold mb-0">Prepared By</p>
                    </div>
                    <div class="signature-section text-center">
                        <div class="sig-line"></div>
                        <p class="small fw-bold mb-0">Receiver's Sig</p>
                    </div>
                    <div class="signature-section text-end">
                        <div class="sig-line"></div>
                        <p class="small fw-bold mb-0">Authorized Sign</p>
                    </div>
                </div>
            </div>

            <div class="print-actions no-print mt-4">
                <el-button type="primary" size="large" @click="executePrint" class="px-5">
                    <i class="bi bi-printer me-2"></i> Print Document
                </el-button>
            </div>
        </div>
        </div>
        </div>

        <!-- Approval Dialog -->
        <el-dialog v-model="approveDialogVisible" title="Approve Cartage Bill" width="500px" class="professional-dialog">
            <div class="mb-4">
                <p class="fs-5 fw-bold text-slate-800 mb-1">Bill #{{ formatBillId(activeBill?.id) }}</p>
                <p class="text-muted">Total Amount: <span class="fw-bold text-dark">Rs. {{ activeBill?.total_amount?.toLocaleString() }}</span></p>
            </div>

            <el-form label-position="top">
                <el-form-item label="Is any Tax Deduction applicable?">
                    <el-radio-group v-model="approvalForm.is_taxable">
                        <el-radio :label="true">Yes, Apply Tax</el-radio>
                        <el-radio :label="false">No Tax Deduction</el-radio>
                    </el-radio-group>
                </el-form-item>

                <div v-if="approvalForm.is_taxable" class="p-3 bg-light rounded border border-warning">
                    <el-form-item label="WHT Deduction Type">
                        <el-select v-model="approvalForm.tax_type" placeholder="Select WHT Type" class="w-100">
                            <el-option label="WHT on Services (3%)" value="WHT on Services" @click="approvalForm.tax_percentage = 3" />
                            <el-option label="WHT on Transport (4%)" value="WHT on Transport" @click="approvalForm.tax_percentage = 4" />
                            <el-option label="WHT on Services (10%)" value="WHT on Services" @click="approvalForm.tax_percentage = 10" />
                            <el-option label="Income Tax (4.5%)" value="Income Tax" @click="approvalForm.tax_percentage = 4.5" />
                            <el-option label="Other / Custom" value="Tax Deduction" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="Tax Percentage (%)">
                        <el-input-number v-model="approvalForm.tax_percentage" :min="0" :max="100" class="w-100" :precision="2" @change="calculateNet" />
                    </el-form-item>
                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="text-muted">Calculated Tax Amount:</span>
                        <span class="fw-bold text-danger">Rs. {{ calculatedTax.toLocaleString() }}</span>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-dark text-white rounded d-flex justify-content-between align-items-center">
                    <span class="fs-6">NET PAYABLE AMOUNT:</span>
                    <span class="fs-4 fw-bold text-warning">Rs. {{ calculatedNetTotal.toLocaleString() }}</span>
                </div>
            </el-form>

            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="approveDialogVisible = false">Cancel</el-button>
                    <el-button type="success" @click="submitApproval" :loading="submittingApproval">
                        <i class="bi bi-check-circle me-2"></i> Confirm Approval
                    </el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import * as XLSX from 'xlsx';

const props = defineProps({
    user: Object,
    initialHistory: {
        type: Boolean,
        default: false
    }
});

const canApprove = computed(() => {
    if (!props.user) return false;
    // Superadmin and Admin always have access, others need explicit 'approve_cartage' view permission
    const roleName = props.user.role?.name;
    if (roleName === 'Super Admin' || roleName === 'Admin') return true;
    return !!(props.user.permissions?.approve_cartage?.can_view);
});

const canUnapprove = computed(() => {
    if (!props.user) return false;
    return props.user.role?.name === 'Super Admin';
});

const transporters = ref([]);
const customers = ref([]);
const vehicles = ref([]);
const history = ref([]);
const loading = ref(false);
const submitting = ref(false);
const viewHistory = ref(false);
const printing = ref(false);
const currentBill = ref(null);
const mainFormRef = ref(null);
const isEditing = ref(false);
const editId = ref(null);
const nextBillId = ref(null);
const approveDialogVisible = ref(false);
const submittingApproval = ref(false);
const activeBill = ref(null);
const approvalForm = reactive({
    is_taxable: false,
    tax_type: 'WHT on Services',
    tax_percentage: 3
});

const historyFilters = reactive({
    bill_id: '',
    transporter: '',
    bill_to: ''
});

const filteredHistory = computed(() => {
    return history.value.filter(bill => {
        const idMatch = !historyFilters.bill_id || formatBillId(bill.id).toLowerCase().includes(historyFilters.bill_id.toLowerCase());
        const transporterMatch = !historyFilters.transporter || bill.transporter?.name.toLowerCase().includes(historyFilters.transporter.toLowerCase());
        const billToMatch = !historyFilters.bill_to || bill.bill_to.toLowerCase().includes(historyFilters.bill_to.toLowerCase());
        return idMatch && transporterMatch && billToMatch;
    });
});

let rowCounter = 0;

const emit = defineEmits(['update-pending-count']);

onMounted(() => {
    fetchBaseData();
    updateViewMode();
});

watch(() => props.initialHistory, () => {
    updateViewMode();
});

const updateViewMode = () => {
    if (props.initialHistory) {
        viewHistory.value = true;
        fetchHistory();
    } else {
        viewHistory.value = false;
        fetchNextBillId();
        if (mainForm.value.entries.length === 0) {
            addRow();
        }
    }
};

const formatBillId = (id) => {
    if (!id) return '';
    return 'CTG' + String(id).padStart(4, '0');
};

const fetchNextBillId = async () => {
    try {
        const res = await axios.get('/api/cartage-bills/next-id');
        nextBillId.value = res.data.next_id;
        emit('update-pending-count');
    } catch (e) {}
};

const mainForm = ref({
    transporter_id: null,
    bill_date: new Date().toISOString().substr(0, 10),
    bill_to: 'M/S QUALITY CARTONS (Pvt.) LTD.',
    entries: []
});

const mainRules = {
    transporter_id: [{ required: true, message: 'Please select transporter', trigger: 'change' }],
    bill_to: [{ required: true, message: 'Please enter Bill To', trigger: 'blur' }]
};

const grandTotal = computed(() => {
    return mainForm.value.entries.reduce((sum, row) => sum + (Number(row.amount) || 0), 0);
});

watch(() => mainForm.value.entries, (newEntries) => {
    newEntries.forEach(row => {
        if (!row.is_sub_row) {
            const children = newEntries.filter(r => r.parent_temp_id === row.temp_id);
            children.forEach(child => {
                if (child.entry_date !== row.entry_date) child.entry_date = row.entry_date;
                if (child.vehicle_id !== row.vehicle_id) child.vehicle_id = row.vehicle_id;
                if (child.vehicle_number !== row.vehicle_number) child.vehicle_number = row.vehicle_number;
                if (child.vehicle_type !== row.vehicle_type) child.vehicle_type = row.vehicle_type;
                if (!child.is_second_location && child.dc_number !== row.dc_number) child.dc_number = row.dc_number;
                if (child.slip_no !== row.slip_no) child.slip_no = row.slip_no;
                
                if (child.is_return) {
                    const expectedAmount = (Number(row.amount) || 0) * 0.5;
                    if (child.amount !== expectedAmount) {
                        child.amount = expectedAmount;
                    }
                }
            });
        }
    });
}, { deep: true });

const filteredVehicles = computed(() => {
    if (!mainForm.value.transporter_id) return [];
    return vehicles.value.filter(v => v.transporter_id === mainForm.value.transporter_id);
});

const isAuthError = (error) => error?.response?.status === 401;

const fetchBaseData = async () => {
    try {
        const [t, c, v] = await Promise.all([
            axios.get('/api/transporters'),
            axios.get('/api/customers'),
            axios.get('/api/vehicles')
        ]);
        transporters.value = t.data;
        customers.value = c.data;
        vehicles.value = v.data;
    } catch (error) {
        if (isAuthError(error)) return;
        ElMessage.error('Error fetching requirements');
    }
};

const fetchHistory = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/cartage-bills');
        history.value = response.data;
    } catch (error) {
        if (!isAuthError(error)) {
            ElMessage.error('Error fetching cartage bills');
        }
    } finally {
        loading.value = false;
    }
};

const addRow = () => {
    mainForm.value.entries.push({
        temp_id: 'row_' + Date.now() + '_' + (rowCounter++),
        entry_date: new Date().toISOString().substr(0, 10),
        customer_id: null,
        shipping_address_id: null,
        vehicle_id: null,
        vehicle_number: '',
        vehicle_type: '',
        dc_number: '',
        slip_no: '',
        amount: 0,
        is_return_checkbox: false,
        is_second_location_checkbox: false,
        is_return: false,
        is_second_location: false,
        remarks: '',
        parent_temp_id: null
    });
};

const handleReturnChange = (row, index) => {
    if (row.is_return_checkbox) {
        const returnRow = {
            temp_id: 'row_' + Date.now() + '_' + (rowCounter++),
            entry_date: row.entry_date,
            customer_id: row.customer_id,
            shipping_address_id: row.shipping_address_id,
            vehicle_id: row.vehicle_id,
            vehicle_number: row.vehicle_number,
            vehicle_type: row.vehicle_type,
            dc_number: row.dc_number,
            slip_no: row.slip_no,
            amount: (Number(row.amount) || 0) * 0.5,
            is_return: true,
            is_second_location: false,
            remarks: 'Cartage for Return',
            parent_temp_id: row.temp_id,
            is_sub_row: true
        };
        mainForm.value.entries.splice(index + 1, 0, returnRow);
    } else {
        const subRowIndex = mainForm.value.entries.findIndex(r => r.parent_temp_id === row.temp_id && r.is_return);
        if (subRowIndex !== -1) {
            mainForm.value.entries.splice(subRowIndex, 1);
        }
    }
};

const handleSecondLocationChange = (row, index) => {
    if (row.is_second_location_checkbox) {
        const secondLocRow = {
            temp_id: 'row_' + Date.now() + '_' + (rowCounter++),
            entry_date: row.entry_date,
            customer_id: null,
            shipping_address_id: null,
            vehicle_id: row.vehicle_id,
            vehicle_number: row.vehicle_number,
            vehicle_type: row.vehicle_type,
            dc_number: row.dc_number,
            amount: 0,
            is_return: false,
            is_second_location: true,
            remarks: 'Second Location Cartage',
            parent_temp_id: row.temp_id,
            is_sub_row: true
        };
        mainForm.value.entries.splice(index + 1, 0, secondLocRow);
    } else {
        const subRowIndex = mainForm.value.entries.findIndex(r => r.parent_temp_id === row.temp_id && r.is_second_location);
        if (subRowIndex !== -1) {
            mainForm.value.entries.splice(subRowIndex, 1);
        }
    }
};

const removeRow = (index) => {
    mainForm.value.entries.splice(index, 1);
    if (mainForm.value.entries.length === 0) addRow();
};

const toggleHistory = () => {
    viewHistory.value = !viewHistory.value;
    if (viewHistory.value) fetchHistory();
};

const onTransporterChange = () => {
    mainForm.value.entries.forEach(row => {
        row.vehicle_id = null;
        row.vehicle_number = '';
        row.vehicle_type = '';
    });
};

const onRowCustomerChange = (row) => {
    row.shipping_address_id = null;
    row.amount = 0;
};

const onVehicleChange = (row) => {
    const vehicle = vehicles.value.find(v => v.id === row.vehicle_id);
    if (vehicle) {
        row.vehicle_number = vehicle.vehicle_number;
        row.vehicle_type = vehicle.vehicle_type;
        fetchRateForRow(row);
    }
};

const getAddressesForCustomer = (customerId) => {
    if (!customerId) return [];
    const customer = customers.value.find(c => c.id === customerId);
    return customer ? customer.shipping_addresses : [];
};

const fetchRateForRow = async (row) => {
    if (row.shipping_address_id && row.vehicle_type) {
        try {
            const res = await axios.get('/api/cartage-rates/fetch', {
                params: {
                    shipping_address_id: row.shipping_address_id,
                    vehicle_type: row.vehicle_type
                }
            });
            if (res.data && res.data.rate) {
                row.amount = Number(res.data.rate);
                ElMessage.success(`Pre-saved rate loaded for ${row.vehicle_type}`);
            }
        } catch (e) {}
    }
};

const saveAndPrint = async () => {
    if (!mainFormRef.value) return;
    
    await mainFormRef.value.validate(async (valid) => {
        if (!valid) return;
        
        if (mainForm.value.entries.length === 0) {
            ElMessage.warning('Add at least one entry');
            return;
        }

        const invalid = mainForm.value.entries.some(e => {
            // Second location doesn't need customer/address, others do
            const needsCustomer = !e.is_second_location;
            return (needsCustomer && (!e.customer_id || !e.shipping_address_id)) || !e.vehicle_id;
        });
        if (invalid) {
            ElMessage.warning('Please complete all row fields (Customer, Address, and Vehicle).');
            return;
        }

        submitting.value = true;
        
        const data = { ...mainForm.value };
        data.entries = mainForm.value.entries.map((row, idx) => {
            const entry = { ...row };
            if (entry.parent_temp_id) {
                entry.parent_index = mainForm.value.entries.findIndex(r => r.temp_id === entry.parent_temp_id);
            } else {
                entry.parent_index = null;
            }
            return entry;
        });

        try {
            let res;
            if (isEditing.value) {
                res = await axios.put(`/api/cartage-bills/${editId.value}`, data);
                ElMessage.success('Bill updated successfully');
            } else {
                res = await axios.post('/api/cartage-bills', data);
                ElMessage.success('Bill generated successfully');
            }
            
            currentBill.value = res.data;
            printing.value = true;
            resetForm();
        } catch (error) {
            ElMessage.error('Error saving bill');
        } finally {
            submitting.value = false;
        }
    });
};

const resetForm = () => {
    isEditing.value = false;
    editId.value = null;
    mainForm.value = {
        transporter_id: null,
        bill_date: new Date().toISOString().substr(0, 10),
        bill_to: 'M/S QUALITY CARTONS (Pvt.) LTD.',
        entries: []
    };
    addRow();
};

const editBill = (bill) => {
    isEditing.value = true;
    editId.value = bill.id;
    viewHistory.value = false;
    
    const entriesWithTemp = bill.entries.map(e => {
        const vehicle = vehicles.value.find(v => v.vehicle_number === e.vehicle_number);
        return {
            ...e,
            temp_id: 'row_' + Date.now() + '_' + (rowCounter++),
            vehicle_id: vehicle ? vehicle.id : null
        };
    });

    entriesWithTemp.forEach(e => {
        if (e.parent_entry_id) {
            const parent = entriesWithTemp.find(p => p.id === e.parent_entry_id);
            if (parent) {
                e.parent_temp_id = parent.temp_id;
                e.is_sub_row = true;
            }
        }
    });

    entriesWithTemp.forEach(e => {
        if (!e.parent_entry_id) {
            const hasReturn = entriesWithTemp.some(child => child.parent_temp_id === e.temp_id && child.is_return);
            const hasSecond = entriesWithTemp.some(child => child.parent_temp_id === e.temp_id && child.is_second_location);
            e.is_return_checkbox = hasReturn;
            e.is_second_location_checkbox = hasSecond;
        }
    });

    const finalEntries = [];
    entriesWithTemp.filter(e => !e.parent_temp_id).forEach(parent => {
        finalEntries.push(parent);
        const children = entriesWithTemp.filter(child => child.parent_temp_id === parent.temp_id);
        children.forEach(child => finalEntries.push(child));
    });

    mainForm.value = {
        transporter_id: bill.transporter_id,
        bill_date: bill.bill_date,
        bill_to: bill.bill_to,
        entries: finalEntries
    };
};

const viewBillDetails = (bill) => {
    currentBill.value = bill;
    printing.value = true;
};

const printExisting = (bill) => {
    currentBill.value = bill;
    printing.value = true;
};

const executePrint = () => {
    window.print();
};

const deleteBill = async (bill) => {
    try {
        await ElMessageBox.confirm('Delete this bill? This action cannot be undone.', 'Warning', { type: 'warning' });
        await axios.delete(`/api/cartage-bills/${bill.id}`);
        ElMessage.success('Bill deleted');
        fetchHistory();
        emit('update-pending-count');
    } catch (e) {}
};

const openApproveDialog = (bill) => {
    activeBill.value = bill;
    approvalForm.is_taxable = false;
    approvalForm.tax_type = 'WHT on Services';
    approvalForm.tax_percentage = 3;
    approveDialogVisible.value = true;
};

const calculatedTax = computed(() => {
    if (!approvalForm.is_taxable || !activeBill.value) return 0;
    return (activeBill.value.total_amount * approvalForm.tax_percentage) / 100;
});

const calculatedNetTotal = computed(() => {
    if (!activeBill.value) return 0;
    return activeBill.value.total_amount - calculatedTax.value;
});

const submitApproval = async () => {
    submittingApproval.value = true;
    try {
        await axios.post(`/api/cartage-bills/${activeBill.value.id}/approve`, {
            tax_type: approvalForm.is_taxable ? approvalForm.tax_type : null,
            tax_percentage: approvalForm.is_taxable ? approvalForm.tax_percentage : 0,
            tax_amount: calculatedTax.value,
            net_amount: calculatedNetTotal.value
        });
        ElMessage.success('Bill Approved Successfully');
        approveDialogVisible.value = false;
        fetchHistory();
        emit('update-pending-count');
    } catch (e) {
        ElMessage.error('Error approving bill');
    } finally {
        submittingApproval.value = false;
    }
};

const unapproveBill = async (bill) => {
    try {
        await ElMessageBox.confirm('Are you sure you want to unapprove this bill? This will set its status back to Pending.', 'Warning', { 
            confirmButtonText: 'Yes, Unapprove',
            cancelButtonText: 'No',
            type: 'warning' 
        });
        await axios.post(`/api/cartage-bills/${bill.id}/unapprove`);
        ElMessage.success('Bill unapproved successfully');
        fetchHistory();
        emit('update-pending-count');
    } catch (e) {
        if (e !== 'cancel') {
            ElMessage.error('Error unapproving bill');
        }
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const d = date.getDate().toString().padStart(2, '0');
    const m = (date.getMonth() + 1).toString().padStart(2, '0');
    const y = date.getFullYear();
    return `${d}/${m}/${y}`;
};

const isSameAsParent = (entry, field) => {
    const entries = (currentBill.value && currentBill.value.entries) ? currentBill.value.entries : mainForm.value.entries;
    if (!entries || entries.length === 0) return false;
    
    const index = entries.findIndex(e => (e.id && e.id === entry.id) || (e.temp_id && e.temp_id === entry.temp_id));
    if (index <= 0) return false;
    
    const prevEntry = entries[index - 1];
    const isChild = (entry.parent_entry_id && String(entry.parent_entry_id) === String(prevEntry.id)) || 
                    (entry.parent_temp_id && entry.parent_temp_id === prevEntry.temp_id) ||
                    (entry.is_sub_row) || (entry.is_return) || (entry.is_second_location);

    if (!isChild) return false;
    if (!field) return true;
    
    const val1 = String(prevEntry[field] || '').trim();
    const val2 = String(entry[field] || '').trim();

    if (field === 'entry_date') {
        return formatDate(prevEntry.entry_date) === formatDate(entry.entry_date);
    }
    
    return val1 === val2;
};

const amountToWords = (num) => {
    if (num === 0) return "Zero";
    
    const a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ', 'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ', 'Nineteen '];
    const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    const inWords = (n) => {
        if (n < 20) return a[n];
        if (n < 100) return b[Math.floor(n / 10)] + (n % 10 !== 0 ? "-" + a[n % 10] : "");
        if (n < 1000) return a[Math.floor(n / 100)] + "Hundred " + (n % 100 !== 0 ? "and " + inWords(n % 100) : "");
        if (n < 100000) return inWords(Math.floor(n / 1000)) + "Thousand " + (n % 1000 !== 0 ? inWords(n % 1000) : "");
        if (n < 10000000) return inWords(Math.floor(n / 100000)) + "Lakh " + (n % 100000 !== 0 ? inWords(n % 100000) : "");
        return "";
    };
    
    return "Rupees " + inWords(Math.floor(num)).trim() + " Only";
};

const arraySpanMethod = ({ row, column, rowIndex, columnIndex }) => {
    // Merge Date (1), Vehicle # (4), and Slip # (6)
    if (columnIndex === 1 || columnIndex === 4 || columnIndex === 6) {
        if (row.is_sub_row) {
            return { rowspan: 0, colspan: 0 };
        } else {
            const children = mainForm.value.entries.filter(r => r.parent_temp_id === row.temp_id);
            if (children.length > 0) {
                return { rowspan: 1 + children.length, colspan: 1 };
            }
        }
    }
};

const tableRowClassName = ({ row }) => {
    if (row.is_sub_row) {
        return 'sub-row';
    }
    return '';
};

const exportHistoryExcel = () => {
    const data = filteredHistory.value.map((bill, index) => ({
        'S. No.': index + 1,
        'Bill #': formatBillId(bill.id),
        Date: formatDate(bill.bill_date),
        Transporter: bill.transporter?.name,
        'Gross Amount': bill.total_amount,
        'Tax Deducted': bill.tax_amount || 0,
        'Net Amount': bill.net_amount || bill.total_amount,
        'Approved By': bill.approver?.name || 'Pending',
        Status: bill.status
    }));
    
    const worksheet = XLSX.utils.json_to_sheet(data);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Bill_History");
    XLSX.writeFile(workbook, "Cartage_Bill_History.xlsx");
    ElMessage.success('History exported to Excel');
};

const printHistory = () => {
    // Basic print of the list
    window.print();
};
</script>

<style scoped>
/* Compact styling for inputs in entry table */
.entry-table :deep(.el-input__wrapper) {
    height: 28px !important;
    padding: 0 8px !important;
}
.entry-table :deep(.el-input__inner) {
    font-size: 13px !important;
    height: 28px !important;
    line-height: 28px !important;
}

/* Vertically center all elements in the table rows */
.entry-table :deep(.el-table__cell) {
    vertical-align: middle !important;
}

/* Light mode styles for native datepicker inputs to match Element Plus */
input[type="date"] {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #0f172a !important;
    border-radius: 4px !important;
    padding: 0 8px !important;
    font-size: 13px !important;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none !important;
}
input[type="date"]:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
}

/* Height and spacing for native date picker in table row */
.entry-table input[type="date"] {
    height: 28px !important;
    line-height: 28px !important;
}

/* Form Header Row Styling */
.main-form-row {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0 !important;
}

/* Header row elements unified height of 36px */
.main-form-row :deep(.el-input__wrapper) {
    height: 36px !important;
}
.main-form-row input[type="date"] {
    height: 36px !important;
    padding: 0 12px !important;
    font-size: 14px !important;
    line-height: 36px !important;
}

/* Dark Mode specific overrides for native date element (which has scoped attributes) */
[data-theme="dark"] input[type="date"] {
    color-scheme: dark !important;
    background-color: #1e293b !important;
    border: 1px solid #475569 !important;
    color: #e2e8f0 !important;
}

[data-theme="dark"] input[type="date"]:focus {
    border-color: #818cf8 !important;
    box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25) !important;
}

[data-theme="dark"] .amount-text {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .amount-input :deep(input) {
    color: #34d399 !important; /* Brighter emerald green for stunning dark contrast */
}

[data-theme="dark"] .grand-total-text {
    color: #818cf8 !important; /* Indigo glow in dark mode */
}

.cartage-billing {
    padding: 20px;
    font-family: 'Montserrat', sans-serif;
    color: #1a1a1a;
}
.cartage-billing :deep(.el-form-item__label) {
    font-weight: 700;
    color: #111;
    font-size: 15px;
}
.entry-table :deep(.el-table__header) th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 800 !important;
    font-size: 11px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    padding: 12px 0 !important;
    border-bottom: 2px solid #e2e8f0 !important;
}
.entry-table :deep(.el-table__row) td {
    padding: 6px 0 !important;
    color: #1e293b !important;
    font-size: 13px !important;
}
.entry-table :deep(.el-table__cell) {
    white-space: nowrap !important;
}
.entry-table :deep(.el-table__row:hover) td {
    background-color: #f1f5f9 !important;
}
.entry-table :deep(.sub-row) {
    background-color: #f8fafc;
}
.entry-table :deep(.sub-row) td {
    border-top: 1px dashed #cbd5e1;
}
.amount-input :deep(input) {
    text-align: right;
    font-weight: 800;
    font-size: 15px;
    color: #059669;
}
.grand-total-text {
    font-size: 24px;
    color: #4f46e5;
    font-weight: 800;
}

:deep(.el-input__wrapper) {
    box-shadow: 0 0 0 1px #cbd5e1 inset;
}
:deep(.el-input__inner) {
    color: #0f172a !important;
    font-weight: 600;
}

:deep(.el-date-editor .el-input__wrapper) {
    flex-direction: row-reverse;
}
:deep(.el-date-editor .el-input__prefix) {
    margin-left: 8px;
    margin-right: 0;
}

.bill-badge {
    background-color: #eff6ff;
    color: #2563eb;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 12px;
    border: 1px solid #dbeafe;
}
.amount-text {
    font-weight: 800;
    color: #0f172a;
    font-size: 14px;
}
.status-tag {
    font-weight: 700 !important;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    font-size: 10px !important;
}
.action-dropdown-btn {
    font-weight: 700 !important;
    border-radius: 6px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.fw-600 { font-weight: 600; }
.fw-500 { font-weight: 500; }
.text-slate-600 { color: #475569; }
.italic { font-style: italic; }

.modern-dropdown :deep(.el-dropdown-menu__item) {
    font-weight: 600 !important;
    font-size: 13px !important;
    padding: 10px 16px !important;
}
.modern-dropdown :deep(.el-dropdown-menu__item i) {
    font-size: 14px;
}

/* Print & Preview Styles */
#print-preview-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.85);
    z-index: 9999;
    display: flex;
    flex-direction: column;
}

.preview-scroll-area {
    flex-grow: 1;
    overflow-y: auto;
    padding: 40px 0;
    background: #525659;
}

.print-container {
    background: #fff;
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 15mm;
    box-sizing: border-box;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    position: relative;
    color: #000;
}

.print-header {
    margin-bottom: 3mm;
    min-height: 22mm; /* Ensure header is tall enough for 20mm logo */
}

.logo-box-print {
    position: absolute;
    left: 0;
    top: 0;
    width: 20mm;
    height: 20mm;
}

.logo-box-print img {
    width: 20mm;
    height: 20mm;
    object-fit: contain;
}

.transporter-name {
    font-size: 28px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #000;
}

.transporter-meta {
    font-size: 11px;
    color: #475569;
}

.bill-title {
    text-align: center;
    font-size: 16px;
    font-weight: 800;
    background: #f1f5f9;
    border: 1.2px solid #000;
    padding: 2px;
    margin-bottom: 3mm;
}

.print-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3mm;
    border-bottom: 1.5px solid #000;
    padding-bottom: 2mm;
}

.bill-to-box {
    width: 60%;
}

.bill-to-box .entity-name {
    font-size: 16px;
    font-weight: 800;
}

.bill-info-box {
    width: 35%;
    font-size: 13px;
}

.info-row {
    padding: 2px 0;
}

.print-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2mm;
}

.print-table th, .print-table td {
    border: 1px solid #000;
    padding: 3px 6px;
    font-size: 11px;
}

.print-table th {
    background: #f1f5f9;
    font-weight: 800;
    text-transform: uppercase;
}
.sub-row-print td {
    border-top: 1px dashed #eee !important; /* Lighter dashed line for sub-rows */
}
.sub-row-print td[style*="border-top: none"] {
    border-top: none !important;
}

.print-footer-container {
    position: absolute;
    bottom: 5mm;
    left: 8mm;
    right: 8mm;
}

.summary-box {
    border: 1px solid #000 !important;
}

.total-words-box {
    border: 1px dashed #000 !important;
    padding: 4px;
    margin-top: 5mm;
    margin-bottom: 5mm;
    font-size: 12px;
}

.print-signatures {
    display: flex;
    justify-content: space-between;
    margin-top: 15mm; /* 15mm space for physical signatures */
}

.signature-section {
    width: 25%;
    text-align: center;
}

.sig-line {
    border-top: 1.5px solid #000;
    margin-bottom: 5px;
}

@media print {
    #print-preview-overlay {
        position: static !important;
        background: none !important;
        height: auto !important;
        display: block !important;
    }
    .preview-actions, .no-print, .el-button {
        display: none !important;
    }
    .preview-scroll-area {
        padding: 0 !important;
        background: none !important;
        overflow: visible !important;
    }
    .print-container {
        width: 100% !important;
        min-height: 280mm !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 8mm !important;
    }
    body {
        margin: 0;
        padding: 0;
    }
    @page {
        size: A4 portrait;
        margin: 0;
    }
}
</style>

<style>
/* Non-scoped styles for theme-specific overrides on Element Plus inputs and components in Cartage Billing */
[data-theme="dark"] .cartage-billing .main-form-row {
    background-color: rgba(15, 23, 42, 0.4) !important;
    border: 1px solid #334155 !important;
}

[data-theme="dark"] .cartage-billing .entry-table .el-table__header th {
    background-color: #1e293b !important;
    color: #cbd5e1 !important;
    border-bottom: 2px solid #334155 !important;
}

[data-theme="dark"] .cartage-billing .entry-table .el-table__row td {
    color: #cbd5e1 !important;
}

[data-theme="dark"] .cartage-billing .entry-table .el-table__row:hover td {
    background-color: rgba(99, 102, 241, 0.08) !important;
}

[data-theme="dark"] .cartage-billing .entry-table .sub-row {
    background-color: rgba(30, 41, 59, 0.3) !important;
}

[data-theme="dark"] .cartage-billing .el-input__wrapper {
    background-color: #1e293b !important;
    border: 1px solid #475569 !important;
    box-shadow: none !important;
}

[data-theme="dark"] .cartage-billing .el-input__wrapper.is-focus {
    border-color: #818cf8 !important;
    box-shadow: 0 0 10px rgba(129, 140, 248, 0.25) !important;
}

[data-theme="dark"] .cartage-billing .el-input__inner {
    color: #e2e8f0 !important;
    background-color: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

[data-theme="dark"] .cartage-billing .el-select .el-input__inner {
    background-color: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

[data-theme="dark"] .cartage-billing .el-form-item__label {
    color: #cbd5e1 !important;
}
</style>
