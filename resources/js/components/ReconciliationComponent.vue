<template>
    <div class="reconciliation-container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="bi bi-arrow-repeat me-2"></i>
                    Stock Reconciliation
                </h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Stock Reconciliation</strong> ensures all reel balances and statuses are accurate by:
                            <ul class="mb-0 mt-2">
                                <li>Verifying returned reels are properly marked</li>
                                <li>Recalculating balance weights from transaction history</li>
                                <li>Updating statuses (in stock, partially used, fully used)</li>
                                <li>Logging all corrections for audit trail</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Check Discrepancies Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-search me-2"></i>
                                    Check Discrepancies
                                </h5>
                                <p class="text-muted">View current discrepancies without making changes</p>
                                <el-button @click="checkDiscrepancies" :loading="checkingDiscrepancies" type="warning">
                                    <i class="bi bi-search me-2"></i>
                                    Check Discrepancies
                                </el-button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-check2-circle me-2"></i>
                                    Run Full Reconciliation
                                </h5>
                                <p class="text-muted">Automatically fix all stock discrepancies</p>
                                <el-button @click="runReconciliation" :loading="reconciling" type="success">
                                    <i class="bi bi-arrow-repeat me-2"></i>
                                    Run Reconciliation
                                </el-button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Discrepancies Preview -->
                <div v-if="discrepancies.length > 0" class="mb-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Current Discrepancies ({{ discrepancies.length }})
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Reel No</th>
                                            <th>Current Status</th>
                                            <th>Current Balance</th>
                                            <th>Issues Found</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="disc in discrepancies" :key="disc.reel_no">
                                            <td><strong>{{ disc.reel_no }}</strong></td>
                                            <td>{{ disc.current_status }}</td>
                                            <td>{{ disc.current_balance }} kg</td>
                                            <td>
                                                <ul class="mb-0 small">
                                                    <li v-for="(issue, idx) in disc.issues" :key="idx">{{ issue }}</li>
                                                </ul>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reconciliation Results -->
                <div v-if="reconciliationResult" class="mb-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-check-circle me-2"></i>
                                Reconciliation Complete
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded">
                                        <h3 class="text-primary">{{ reconciliationResult.summary.total_reels_checked }}</h3>
                                        <small class="text-muted">Reels Checked</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded">
                                        <h3 class="text-warning">{{ reconciliationResult.summary.discrepancies_found }}</h3>
                                        <small class="text-muted">Discrepancies Found</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded">
                                        <h3 class="text-success">{{ reconciliationResult.summary.corrections_made }}</h3>
                                        <small class="text-muted">Corrections Made</small>
                                    </div>
                                </div>
                            </div>

                            <div v-if="reconciliationResult.details.length > 0">
                                <h6 class="mb-3">Details of Changes:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Reel No</th>
                                                <th>Issue</th>
                                                <th>Changes Made</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(detail, idx) in reconciliationResult.details" :key="idx">
                                                <td><strong>{{ detail.reel_no }}</strong></td>
                                                <td>{{ detail.issue }}</td>
                                                <td>
                                                    <div v-if="detail.old_status && detail.new_status">
                                                        Status: <span class="text-danger">{{ detail.old_status }}</span> 
                                                        → <span class="text-success">{{ detail.new_status }}</span>
                                                    </div>
                                                    <div v-if="detail.old_balance !== undefined && detail.new_balance !== undefined">
                                                        Balance: <span class="text-danger">{{ detail.old_balance }} kg</span> 
                                                        → <span class="text-success">{{ detail.new_balance }} kg</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reconciliation History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Reconciliation History
                        </h5>
                    </div>
                    <div class="card-body">
                        <el-button @click="loadHistory" :loading="loadingHistory" class="mb-3">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            Refresh History
                        </el-button>

                        <div v-if="history.length > 0" class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Run By</th>
                                        <th>Reels Checked</th>
                                        <th>Discrepancies</th>
                                        <th>Corrections</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="log in history" :key="log.id">
                                        <td>{{ formatDate(log.run_date) }}</td>
                                        <td>{{ log.user ? log.user.name : 'System' }}</td>
                                        <td>{{ log.total_reels_checked }}</td>
                                        <td>{{ log.discrepancies_found }}</td>
                                        <td>{{ log.corrections_made }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1"></i>
                            <p>No reconciliation history available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

export default {
    name: 'ReconciliationComponent',
    props: {
        user: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            reconciling: false,
            checkingDiscrepancies: false,
            loadingHistory: false,
            discrepancies: [],
            reconciliationResult: null,
            history: []
        };
    },
    mounted() {
        this.loadHistory();
    },
    methods: {
        async checkDiscrepancies() {
            this.checkingDiscrepancies = true;
            try {
                const response = await axios.get('/api/reconciliation/discrepancies');
                this.discrepancies = response.data.discrepancies;
                
                if (this.discrepancies.length === 0) {
                    ElMessage.success('No discrepancies found! All stock is properly reconciled.');
                } else {
                    ElMessage.warning(`Found ${this.discrepancies.length} discrepancies. Review them below.`);
                }
            } catch (error) {
                console.error('Error checking discrepancies:', error);
                ElMessage.error('Failed to check discrepancies');
            } finally {
                this.checkingDiscrepancies = false;
            }
        },

        async runReconciliation() {
            try {
                await ElMessageBox.confirm(
                    'This will automatically correct all stock discrepancies. All changes will be logged. Continue?',
                    'Confirm Reconciliation',
                    {
                        confirmButtonText: 'Yes, Reconcile',
                        cancelButtonText: 'Cancel',
                        type: 'warning'
                    }
                );

                this.reconciling = true;
                this.reconciliationResult = null;

                const response = await axios.post('/api/reconciliation/run');
                this.reconciliationResult = response.data;
                
                ElMessage.success(`Reconciliation completed! ${response.data.summary.corrections_made} corrections made.`);
                
                // Reload history
                this.loadHistory();
                
                // Clear discrepancies preview
                this.discrepancies = [];
            } catch (error) {
                if (error !== 'cancel') {
                    console.error('Error running reconciliation:', error);
                    ElMessage.error('Failed to run reconciliation');
                }
            } finally {
                this.reconciling = false;
            }
        },

        async loadHistory() {
            this.loadingHistory = true;
            try {
                const response = await axios.get('/api/reconciliation/history');
                this.history = response.data.data || [];
            } catch (error) {
                console.error('Error loading history:', error);
                ElMessage.error('Failed to load reconciliation history');
            } finally {
                this.loadingHistory = false;
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('en-GB');
        }
    }
};
</script>

<style scoped>
.reconciliation-container {
    padding: 20px;
}

.card {
    margin-bottom: 20px;
}

.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}
</style>

<!-- Non-scoped dark mode overrides for Stock Reconciliation -->
<style>
/* ── Reconciliation: Alert Info Box (the invisible text) ── */
[data-theme="dark"] .reconciliation-container .alert-info {
    background-color: rgba(99, 102, 241, 0.12) !important;
    border-color: rgba(99, 102, 241, 0.25) !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .reconciliation-container .alert-info strong {
    color: #e2e8f0 !important;
}
[data-theme="dark"] .reconciliation-container .alert-info ul li {
    color: #94a3b8 !important;
}
[data-theme="dark"] .reconciliation-container .alert-info i {
    color: #818cf8 !important;
}

/* ── Reconciliation: Main Card & Header ── */
[data-theme="dark"] .reconciliation-container > .card {
    background-color: rgba(30, 41, 59, 0.95) !important;
    border: 1px solid #334155 !important;
}
[data-theme="dark"] .reconciliation-container > .card > .card-header.bg-primary {
    background: linear-gradient(135deg, #6366f1, #a78bfa) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}
[data-theme="dark"] .reconciliation-container > .card > .card-body {
    background-color: transparent !important;
}

/* ── Reconciliation: Inner Cards (Check Discrepancies / Run Reconciliation) ── */
[data-theme="dark"] .reconciliation-container .card .card-body .card {
    background-color: rgba(30, 41, 59, 0.7) !important;
    border-color: #475569 !important;
}
[data-theme="dark"] .reconciliation-container .card .card-body .card.border-warning {
    border-color: rgba(245, 158, 11, 0.4) !important;
}
[data-theme="dark"] .reconciliation-container .card .card-body .card.border-success {
    border-color: rgba(16, 185, 129, 0.4) !important;
}
[data-theme="dark"] .reconciliation-container .card-title {
    color: #f1f5f9 !important;
}
[data-theme="dark"] .reconciliation-container .card-body .text-muted {
    color: #94a3b8 !important;
}

/* ── Reconciliation: Discrepancies Card ── */
[data-theme="dark"] .reconciliation-container .bg-warning {
    background-color: rgba(245, 158, 11, 0.85) !important;
}
[data-theme="dark"] .reconciliation-container .bg-warning.text-dark,
[data-theme="dark"] .reconciliation-container .bg-warning h5 {
    color: #1e293b !important;
}

/* ── Reconciliation: Results Card ── */
[data-theme="dark"] .reconciliation-container .bg-success {
    background-color: rgba(16, 185, 129, 0.85) !important;
}

/* ── Reconciliation: Summary Stat Boxes ── */
[data-theme="dark"] .reconciliation-container .p-3.border.rounded {
    background-color: rgba(30, 41, 59, 0.6) !important;
    border-color: #475569 !important;
}
[data-theme="dark"] .reconciliation-container .p-3.border.rounded small.text-muted {
    color: #94a3b8 !important;
}

/* ── Reconciliation: Tables ── */
[data-theme="dark"] .reconciliation-container .table {
    --bs-table-bg: transparent !important;
    --bs-table-color: #e2e8f0 !important;
    --bs-table-striped-bg: rgba(51, 65, 85, 0.3) !important;
    --bs-table-striped-color: #e2e8f0 !important;
    --bs-table-hover-bg: rgba(99, 102, 241, 0.08) !important;
    --bs-table-hover-color: #e2e8f0 !important;
    --bs-table-border-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] .reconciliation-container .table thead th {
    background-color: #1e293b !important;
    color: #94a3b8 !important;
    border-bottom: 2px solid #334155 !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 12px;
}
[data-theme="dark"] .reconciliation-container .table td {
    border-color: rgba(51, 65, 85, 0.5) !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .reconciliation-container .table td strong {
    color: #f1f5f9 !important;
}

/* ── Reconciliation: History Card ── */
[data-theme="dark"] .reconciliation-container .card .card-header:not(.bg-primary):not(.bg-warning):not(.bg-success) {
    background-color: rgba(30, 41, 59, 0.8) !important;
    border-bottom: 1px solid #334155 !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] .reconciliation-container .card .card-header h5 {
    color: #f1f5f9 !important;
}

/* ── Reconciliation: Empty State ── */
[data-theme="dark"] .reconciliation-container .text-center.text-muted {
    color: #64748b !important;
}
</style>
