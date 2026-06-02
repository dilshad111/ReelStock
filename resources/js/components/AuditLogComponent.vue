<template>
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0 auth-form-container">
            <div class="card-header bg-white py-4 border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-shield-lock me-2"></i>System Audit Logs
                    </h4>
                    <p class="text-muted small mb-0 mt-1">Track and monitor all user activities across the platform.</p>
                </div>
                
                <div class="d-flex gap-2">
                    <div class="d-flex gap-2 me-3">
                        <el-button type="success" size="small" @click="exportToExcel" plain><i class="bi bi-file-earmark-excel me-1"></i> Excel</el-button>
                        <el-button type="info" size="small" @click="printLogs" plain><i class="bi bi-printer me-1"></i> Print</el-button>
                    </div>
                    <div class="d-flex gap-3 align-items-center bg-light rounded-pill px-3 py-2 border">
                        <div class="d-flex align-items-center border-end pe-3">
                            <i class="bi bi-funnel text-muted me-2"></i>
                            <select v-model="filters.event" class="form-select form-select-sm border-0 bg-transparent shadow-none" style="min-width: 120px;" @change="debouncedFetch">
                                <option value="">All Actions</option>
                                <option value="created">Created</option>
                                <option value="updated">Modified</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                        <div class="d-flex align-items-center border-end pe-3">
                            <i class="bi bi-box text-muted me-2"></i>
                            <input v-model="filters.auditable_type" type="text" class="form-control form-control-sm border-0 bg-transparent shadow-none" placeholder="Module (e.g. Reel)" style="min-width: 140px;" @input="debouncedFetch">
                        </div>
                        <div class="d-flex align-items-center pe-3">
                            <i class="bi bi-search text-muted me-2"></i>
                            <input v-model="filters.user_search" type="text" class="form-control form-control-sm border-0 bg-transparent shadow-none" placeholder="Search User..." style="min-width: 140px;" @input="debouncedFetch">
                        </div>
                        <button class="btn btn-primary btn-sm rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0;" @click="fetchAudits" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 custom-audit-table">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="ps-4 py-3" style="width: 18%;">DATE & TIME</th>
                                <th class="py-3" style="width: 20%;">USER</th>
                                <th class="py-3" style="width: 12%;">ACTION</th>
                                <th class="py-3" style="width: 20%;">MODULE/TARGET</th>
                                <th class="py-3" style="width: 15%;">IP ADDRESS</th>
                                <th class="py-3 text-center pe-4" style="width: 15%;">DETAILS</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <tr v-if="loading">
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner-grow text-primary spinner-grow-sm me-2" role="status"></div>
                                    <div class="spinner-grow text-primary spinner-grow-sm me-2" role="status" style="animation-delay: 0.2s"></div>
                                    <div class="spinner-grow text-primary spinner-grow-sm" role="status" style="animation-delay: 0.4s"></div>
                                    <div class="text-muted mt-2 fw-medium small">Loading audit trail...</div>
                                </td>
                            </tr>
                            <tr v-else-if="audits.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                                        <i class="bi bi-inbox fs-3 text-secondary"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">No Activity Found</h6>
                                    <p class="small mb-0">Try adjusting your filters to find what you're looking for.</p>
                                </td>
                            </tr>
                            <tr v-for="audit in audits" :key="audit.id" class="audit-row">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="date-box me-3 text-center rounded bg-light border">
                                            <span class="d-block fw-bold text-dark" style="font-size: 14px;">{{ getDay(audit.created_at) }}</span>
                                            <span class="d-block text-uppercase text-muted" style="font-size: 10px;">{{ getMonth(audit.created_at) }}</span>
                                        </div>
                                        <div>
                                            <div class="text-dark fw-medium" style="font-size: 13px;">{{ getYear(audit.created_at) }}</div>
                                            <div class="text-muted small" style="font-size: 11px;"><i class="bi bi-clock me-1"></i>{{ formatTime(audit.created_at) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3 flex-shrink-0" :class="getAvatarColor(audit.user ? audit.user.name : 'System')">
                                            {{ audit.user ? audit.user.name.charAt(0).toUpperCase() : 'S' }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 13px;">{{ audit.user ? audit.user.name : 'System Automated' }}</div>
                                            <div class="text-muted" style="font-size: 11px;" v-if="audit.user">{{ audit.user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="custom-badge" :class="'badge-' + audit.event">
                                        <i :class="getEventIcon(audit.event)" class="me-1"></i>
                                        {{ audit.event.toUpperCase() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark" style="font-size: 13px;">{{ formatModelName(audit.auditable_type) }}</div>
                                    <div class="text-muted mt-1" style="font-size: 11px;">
                                        <span class="bg-light px-2 py-1 rounded border">
                                            <template v-if="audit.reel_no">Reel: <strong class="text-dark">{{ audit.reel_no }}</strong></template>
                                            <template v-else>ID: <strong class="text-dark">{{ audit.auditable_id }}</strong></template>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark fw-medium" style="font-size: 13px;">
                                        <i class="bi bi-globe2 me-1 text-muted"></i> {{ audit.ip_address }}
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 11px;">
                                        <i class="bi bi-laptop me-1"></i> {{ audit.parsed_user_agent || 'Unknown' }}
                                    </div>
                                </td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-light btn-sm rounded-pill btn-view-details fw-medium px-3 text-primary" @click="showDetails(audit)">
                                        Inspect <i class="bi bi-arrow-right-short ms-1"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white py-3 border-0 mt-auto" v-if="pagination.last_page > 1">
                <nav class="d-flex justify-content-between align-items-center px-2">
                    <span class="text-muted small">Showing Page <strong>{{ pagination.current_page }}</strong> of <strong>{{ pagination.last_page }}</strong></span>
                    <ul class="pagination pagination-sm mb-0 custom-pagination">
                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                            <a class="page-link shadow-none" href="#" @click.prevent="changePage(pagination.current_page - 1)"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <li v-for="page in getVisiblePages()" :key="page" class="page-item" :class="{ active: pagination.current_page === page, disabled: page === '...' }">
                            <a class="page-link shadow-none" href="#" @click.prevent="page !== '...' && changePage(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                            <a class="page-link shadow-none" href="#" @click.prevent="changePage(pagination.current_page + 1)"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Details Modal -->
        <div class="modal fade" id="auditDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header border-0 bg-primary text-white py-3 px-4">
                        <h5 class="modal-title fw-bold mb-0">
                            <i class="bi bi-journal-code me-2"></i>Audit Trail Snapshot
                        </h5>
                        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 bg-light">
                        <div v-if="selectedAudit">
                            <!-- Metadata Header -->
                            <div class="bg-white p-4 border-bottom">
                                <div class="row g-4">
                                    <div class="col-md-6 border-end">
                                        <div class="d-flex">
                                            <div class="avatar-circle me-3 mt-1 shadow-sm" style="width: 48px; height: 48px; font-size: 20px;" :class="getAvatarColor(selectedAudit.user ? selectedAudit.user.name : 'System')">
                                                {{ selectedAudit.user ? selectedAudit.user.name.charAt(0).toUpperCase() : 'S' }}
                                            </div>
                                            <div>
                                                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">Performed By</div>
                                                <div class="fw-bold text-dark" style="font-size: 15px;">{{ selectedAudit.user ? selectedAudit.user.name : 'System Automated' }}</div>
                                                <div class="text-muted small">{{ selectedAudit.user ? selectedAudit.user.email : 'System Process' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">Date & Time</div>
                                                <div class="fw-semibold text-dark" style="font-size: 13px;">{{ formatDate(selectedAudit.created_at) }}</div>
                                                <div class="text-muted small">{{ formatTime(selectedAudit.created_at) }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">Target Module</div>
                                                <div class="fw-semibold text-dark" style="font-size: 13px;">{{ formatModelName(selectedAudit.auditable_type) }}</div>
                                                <div class="text-muted small">
                                                    ID/Reel: {{ selectedAudit.reel_no || selectedAudit.auditable_id }}
                                                </div>
                                                <div v-if="selectedAudit.url" class="mt-2">
                                                    <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">Action URL</div>
                                                    <a :href="selectedAudit.url" target="_blank" class="text-primary small text-truncate d-inline-block" style="max-width: 100%;">{{ selectedAudit.url }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Changes Breakdown -->
                            <div class="p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="custom-badge me-2" :class="'badge-' + selectedAudit.event">
                                        <i :class="getEventIcon(selectedAudit.event)" class="me-1"></i> {{ selectedAudit.event.toUpperCase() }}
                                    </span>
                                    <h6 class="fw-bold mb-0 text-dark">Data Modifications</h6>
                                </div>

                                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                                    <table class="table table-bordered mb-0">
                                        <thead class="bg-white">
                                            <tr>
                                                <th class="py-3 px-3 w-25 text-muted text-uppercase small fw-bold" style="font-size: 11px;">Property</th>
                                                <th v-if="selectedAudit.event !== 'created'" class="py-3 px-3 w-35 text-muted text-uppercase small fw-bold" style="font-size: 11px;">Previous State</th>
                                                <th v-if="selectedAudit.event !== 'deleted'" class="py-3 px-3 w-40 text-muted text-uppercase small fw-bold" style="font-size: 11px;">New State</th>
                                            </tr>
                                        </thead>
                                        <tbody class="border-top-0 bg-white">
                                            <tr v-if="Object.keys(getDiff(selectedAudit)).length === 0">
                                                <td :colspan="selectedAudit.event === 'updated' ? 3 : 2" class="text-center py-4 text-muted fst-italic">
                                                    No specific field changes tracked for this event.
                                                </td>
                                            </tr>
                                            <tr v-for="(value, field) in getDiff(selectedAudit)" :key="field">
                                                <td class="bg-light fw-medium px-3 text-dark align-middle" style="font-size: 13px;">
                                                    <i class="bi bi-dot text-primary"></i> {{ formatFieldName(field) }}
                                                </td>
                                                <td v-if="selectedAudit.event !== 'created'" class="px-3 position-relative align-middle">
                                                    <div class="change-val old-val text-danger" style="font-size: 13px; font-family: monospace;">
                                                        {{ formatValue(value.old) }}
                                                    </div>
                                                </td>
                                                <td v-if="selectedAudit.event !== 'deleted'" class="px-3 position-relative align-middle">
                                                    <div class="change-val new-val text-success fw-medium" style="font-size: 13px; font-family: monospace;">
                                                        {{ formatValue(value.new) }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Environment Box -->
                                <div class="mt-4 pt-3 border-top">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center text-muted small" style="font-size: 11px;">
                                                <i class="bi bi-geo-alt me-2 text-primary fs-6"></i>
                                                <div>
                                                    <strong class="d-block text-dark">IP Address</strong>
                                                    <code>{{ selectedAudit.ip_address }}</code>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start text-muted small" style="font-size: 11px;">
                                                <i class="bi bi-laptop me-2 text-primary fs-6 mt-1"></i>
                                                <div>
                                                    <strong class="d-block text-dark">Device & Browser</strong>
                                                    <span class="fst-italic d-block">{{ selectedAudit.parsed_user_agent || 'Client Information Unavailable' }}</span>
                                                    <span class="text-muted" style="font-size: 9px; word-break: break-all;">{{ selectedAudit.user_agent }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import * as XLSX from 'xlsx';

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
                user_id: '',
                user_search: ''
            },
            searchTimeout: null,
            pagination: {
                current_page: 1,
                last_page: 1
            },
            selectedAudit: null,
            modal: null,
            avatarColors: ['bg-danger', 'bg-warning', 'bg-info', 'bg-success', 'bg-primary', 'bg-dark', 'bg-secondary']
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
        debouncedFetch() {
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            this.searchTimeout = setTimeout(() => {
                this.fetchAudits(1);
            }, 500);
        },
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.fetchAudits(page);
            }
        },
        getVisiblePages() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            if (last <= 7) {
                return Array.from({ length: last }, (_, i) => i + 1);
            }
            if (current <= 4) {
                return [1, 2, 3, 4, 5, '...', last];
            }
            if (current >= last - 3) {
                return [1, '...', last - 4, last - 3, last - 2, last - 1, last];
            }
            return [1, '...', current - 1, current, current + 1, '...', last];
        },
        getDay(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).getDate().toString().padStart(2, '0');
        },
        getMonth(dateString) {
            if (!dateString) return '-';
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[new Date(dateString).getMonth()];
        },
        getYear(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).getFullYear();
        },
        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            return `${day}/${month}/${date.getFullYear()}`;
        },
        formatTime(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; 
            return `${hours}:${minutes} ${ampm}`;
        },
        getAvatarColor(name) {
            if (name === 'System Automated' || name === 'System') return 'bg-secondary';
            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }
            return this.avatarColors[Math.abs(hash) % this.avatarColors.length] + ' text-white';
        },
        getEventIcon(event) {
            switch (event) {
                case 'created': return 'bi-plus-circle-fill';
                case 'updated': return 'bi-pencil-fill';
                case 'deleted': return 'bi-trash3-fill';
                default: return 'bi-info-circle-fill';
            }
        },
        getEventBadgeClass(event) {
            // used internally by the template wrapper
            return `badge-${event}`;
        },
        formatModelName(type) {
            if (!type) return 'Unknown';
            const parts = type.split('\\');
            let name = parts[parts.length - 1];
            // Format CamelCase to Spaced Words
            return name.replace(/([A-Z])/g, ' $1').trim();
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

            const allKeys = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);
            
            allKeys.forEach(key => {
                if (['updated_at', 'created_at', 'id'].includes(key)) return;
                
                // Only show fields that actually changed
                if (JSON.stringify(oldValues[key]) !== JSON.stringify(newValues[key])) {
                    diff[key] = {
                        old: oldValues[key],
                        new: newValues[key]
                    };
                }
            });
            
            return diff;
        },
        formatFieldName(field) {
            let formatted = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            if (formatted === 'Reel Id') return 'Reel Number';
            if (formatted === 'Reel No') return 'Reel Number';
            return formatted;
        },
        formatValue(val) {
            if (val === null || val === undefined || val === '') return 'NULL';
            if (typeof val === 'boolean') return val ? 'TRUE' : 'FALSE';
            if (typeof val === 'object') return JSON.stringify(val);
            return val;
        },
        exportToExcel() {
            const data = this.audits.map(audit => ({
                'Date & Time': this.formatDate(audit.created_at) + ' ' + this.formatTime(audit.created_at),
                User: audit.user ? audit.user.name : 'System',
                Event: audit.event,
                Module: this.formatModelName(audit.auditable_type),
                Target: audit.reel_no || audit.auditable_id,
                'IP Address': audit.ip_address
            }));
            
            const worksheet = XLSX.utils.json_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "AuditLogs");
            XLSX.writeFile(workbook, "Audit_Logs.xlsx");
        },
        printLogs() {
            window.print();
        }
    }
};
</script>

<style scoped>
/* High-End Professional UI Dashboard Styling */

.auth-form-container {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03) !important;
}

.custom-audit-table th {
    font-size: 10px;
    letter-spacing: 1px;
    font-weight: 700;
    color: #6b7280;
    border-bottom-width: 2px !important;
}

.audit-row {
    transition: all 0.2s ease;
}

.audit-row:hover {
    background-color: #f9fafb !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    position: relative;
    z-index: 10;
}

/* Date Box Styling */
.date-box {
    width: 44px;
    padding: 4px 0;
    background: #f3f4f6;
    border-color: #e5e7eb !important;
}

/* Avatar Styling */
.avatar-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Custom Badges */
.custom-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.4em 0.85em;
    font-size: 11px;
    font-weight: 600;
    border-radius: 6px;
    letter-spacing: 0.5px;
}

.badge-created {
    background-color: #def7ec;
    color: #03543f;
    border: 1px solid #bcdecb;
}

.badge-updated {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.badge-deleted {
    background-color: #fde8e8;
    color: #9b1c1c;
    border: 1px solid #f8d0d0;
}

/* Button Styling */
.btn-view-details {
    transition: all 0.2s;
    background-color: #f3f4f6;
    border: 1px solid transparent;
}

.audit-row:hover .btn-view-details {
    background-color: #e0e7ff;
    color: #4f46e5 !important;
    border-color: #c7d2fe;
}

/* Custom Pagination */
.custom-pagination .page-link {
    border-radius: 6px;
    margin: 0 2px;
    color: #4b5563;
    border: none;
    font-weight: 500;
}

.custom-pagination .page-item.active .page-link {
    background-color: #4f46e5;
    color: white;
    box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
}

/* Change value text coloring inside modal */
.change-val {
    padding: 4px 8px;
    border-radius: 4px;
    background: rgba(0,0,0,0.02);
}
.old-val {
    background: #fff5f5;
    text-decoration: line-through;
    opacity: 0.8;
}
.new-val {
    background: #f0fff4;
}

/* Fix for overlapping text in the header search fields */
.form-select, .form-control {
    font-size: 13px;
    color: #374151;
}

.form-select:focus, .form-control:focus {
    box-shadow: none !important;
}

/* Dark Mode handling if app uses it */
[data-theme="modern"] .auth-form-container {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.5);
}

[data-theme="dark"] .auth-form-container,
[data-theme="dark"] .card-header,
[data-theme="dark"] .card-footer {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .custom-audit-table thead,
[data-theme="dark"] .custom-audit-table thead th,
[data-theme="dark"] .custom-audit-table tbody td {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .audit-row:hover {
    background: #334155 !important;
    box-shadow: none;
}

[data-theme="dark"] .date-box,
[data-theme="dark"] .btn-view-details,
[data-theme="dark"] .bg-light,
[data-theme="dark"] .bg-white {
    background: #334155 !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .custom-pagination .page-link {
    background: #334155 !important;
    color: #e2e8f0 !important;
}

[data-theme="dark"] .modal-content,
[data-theme="dark"] .modal-body {
    background: #1e293b !important;
    color: #e2e8f0 !important;
}
</style>
