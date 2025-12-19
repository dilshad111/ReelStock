<template>
    <div class="container-fluid">
        <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="bi bi-clock-history me-2"></i>User Activity / Audit Logs
                </h5>
                <div class="d-flex gap-2">
                    <select v-model="filters.event" class="form-select form-select-sm" style="width: 150px;" @change="fetchAudits">
                        <option value="">All Events</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" @click="fetchAudits">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Date & Time</th>
                                <th>User</th>
                                <th>Event</th>
                                <th>Module</th>
                                <th>Details</th>
                                <th class="pe-4 text-end">Device Info</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <tr v-if="loading">
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <span class="text-muted">Loading logs...</span>
                                </td>
                            </tr>
                            <tr v-else-if="audits.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No activity logs found.
                                </td>
                            </tr>
                            <tr v-for="audit in audits" :key="audit.id">
                                <td class="ps-4 small">
                                    <div class="fw-bold">{{ formatDate(audit.created_at) }}</div>
                                    <div class="text-muted">{{ formatTime(audit.created_at) }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2 bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                                            {{ audit.user ? audit.user.name.charAt(0) : 'S' }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ audit.user ? audit.user.name : 'System' }}</div>
                                            <div class="text-muted small">{{ audit.user ? audit.user.email : '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span :class="getEventBadgeClass(audit.event)">
                                        {{ audit.event.toUpperCase() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ formatModelName(audit.auditable_type) }}</div>
                                    <div class="text-muted small">ID: {{ audit.auditable_id }}</div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-link text-decoration-none p-0" @click="showDetails(audit)">
                                        View Changes <i class="bi bi-chevron-right small"></i>
                                    </button>
                                </td>
                                <td class="pe-4 text-end small text-muted">
                                    <div>IP: {{ audit.ip_address }}</div>
                                    <div class="text-truncate" style="max-width: 150px;" :title="audit.user_agent">
                                        {{ audit.user_agent }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3 border-0 mt-auto" v-if="pagination.last_page > 1">
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">Previous</a>
                        </li>
                        <li v-for="page in pagination.last_page" :key="page" class="page-item" :class="{ active: pagination.current_page === page }">
                            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Details Modal -->
        <div class="modal fade" id="auditDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 bg-light py-3">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="bi bi-info-circle me-2"></i>Change Details
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div v-if="selectedAudit" class="row">
                            <div class="col-12 mb-4">
                                <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted small d-block">Action performed by</span>
                                        <span class="fw-bold">{{ selectedAudit.user ? selectedAudit.user.name : 'System' }}</span>
                                    </div>
                                    <div class="text-end">
                                        <span class="text-muted small d-block">Performed on</span>
                                        <span class="fw-bold">{{ formatDate(selectedAudit.created_at) }} {{ formatTime(selectedAudit.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Fields {{ selectedAudit.event === 'deleted' ? 'at time of deletion' : 'Changed' }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="bg-light small">
                                            <tr>
                                                <th>Field</th>
                                                <th v-if="selectedAudit.event !== 'created'">Old Value</th>
                                                <th v-if="selectedAudit.event !== 'deleted'">New Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(value, field) in getDiff(selectedAudit)" :key="field">
                                                <td class="fw-bold bg-light" style="width: 30%;">{{ formatFieldName(field) }}</td>
                                                <td v-if="selectedAudit.event !== 'created'" class="text-danger">
                                                    {{ formatValue(value.old) }}
                                                </td>
                                                <td v-if="selectedAudit.event !== 'deleted'" class="text-success">
                                                    {{ formatValue(value.new) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'AuditLogComponent',
    props: ['user'],
    data() {
        return {
            audits: [],
            loading: false,
            filters: {
                event: '',
                auditable_type: '',
                user_id: ''
            },
            pagination: {},
            selectedAudit: null,
            modal: null
        };
    },
    mounted() {
        this.fetchAudits();
        if (window.bootstrap) {
            this.modal = new window.bootstrap.Modal(document.getElementById('auditDetailsModal'));
        }
    },
    methods: {
        async fetchAudits(page = 1) {
            this.loading = true;
            try {
                const response = await axios.get('/api/reports/audits', {
                    params: {
                        page,
                        ...this.filters
                    }
                });
                this.audits = response.data.data;
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    total: response.data.total
                };
            } catch (error) {
                console.error('Error fetching audits:', error);
            } finally {
                this.loading = false;
            }
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.fetchAudits(page);
            }
        },
        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '-';
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
        },
        formatTime(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '-';
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; 
            return `${hours}:${minutes} ${ampm}`;
        },
        getEventBadgeClass(event) {
            switch (event) {
                case 'created': return 'badge bg-soft-success text-success rounded-pill';
                case 'updated': return 'badge bg-soft-warning text-warning rounded-pill';
                case 'deleted': return 'badge bg-soft-danger text-danger rounded-pill';
                default: return 'badge bg-soft-info text-info rounded-pill';
            }
        },
        formatModelName(type) {
            if (!type) return 'Unknown';
            const parts = type.split('\\');
            return parts[parts.length - 1];
        },
        showDetails(audit) {
            this.selectedAudit = audit;
            if (this.modal) {
                this.modal.show();
            } else if (window.bootstrap) {
                this.modal = new window.bootstrap.Modal(document.getElementById('auditDetailsModal'));
                this.modal.show();
            }
        },
        getDiff(audit) {
            const diff = {};
            const oldValues = audit.old_values || {};
            const newValues = audit.new_values || {};

            // For updates, we usually only care about fields that actually changed
            // Laravel-Auditing already provides only the changed fields in new_values/old_values
            
            const allKeys = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);
            
            allKeys.forEach(key => {
                // Skip common system fields if they are noise
                if (['updated_at', 'created_at'].includes(key)) return;
                
                diff[key] = {
                    old: oldValues[key],
                    new: newValues[key]
                };
            });
            
            return diff;
        },
        formatFieldName(field) {
            return field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        formatValue(val) {
            if (val === null || val === undefined) return '-';
            if (typeof val === 'boolean') return val ? 'Yes' : 'No';
            if (typeof val === 'object') return JSON.stringify(val);
            return val;
        }
    }
};
</script>

<style scoped>
.bg-soft-primary { background-color: rgba(99, 102, 241, 0.1); }
.bg-soft-success { background-color: rgba(16, 185, 129, 0.1); }
.bg-soft-warning { background-color: rgba(245, 158, 11, 0.1); }
.bg-soft-danger { background-color: rgba(239, 68, 68, 0.1); }
.bg-soft-info { background-color: rgba(6, 182, 212, 0.1); }

.avatar-sm { font-weight: 600; font-family: sans-serif; }

.table > :not(caption) > * > * {
    padding: 1rem 0.5rem;
}

[data-theme="modern"] .card {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.5);
}
</style>
