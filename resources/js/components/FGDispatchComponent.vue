<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="page-title"><i class="bi bi-arrow-up-circle-fill text-primary"></i> FG Dispatch Entry</h2>
      <div class="d-flex gap-2">
        <button @click="printPage" class="btn btn-outline-primary shadow-sm px-3">
          <i class="bi bi-printer-fill me-1"></i> Print List
        </button>
        <button @click="printPage" class="btn btn-outline-danger shadow-sm px-3">
          <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
        </button>
        <button @click="showForm = true; editing = false; resetForm(); $nextTick(() => { if (typeof window !== 'undefined' && typeof window.scrollTo === 'function') window.scrollTo({ top: 0, behavior: 'smooth' }); })" class="btn btn-purple shadow-sm px-4 py-2">
          <i class="bi bi-plus-circle-fill me-1"></i> Add Dispatch
        </button>
      </div>
    </div>

    <div v-if="showForm" class="row mb-5 fade-in">
      <div :class="jobInfo.job_number ? 'col-md-9' : 'col-md-12'">
        <div class="card shadow border-0">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ editing ? 'Edit' : 'Add' }} Dispatch Details</h6>
            <button @click="showForm = false" class="btn-close btn-close-white btn-sm"></button>
          </div>
          <div class="card-body p-3">
            <form @submit.prevent="saveDispatch" novalidate>
              <div class="row g-3">
                <!-- Column 1: Core Identifiers -->
                <div class="col-md-3">
                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Job # (Primary) <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input v-model="form.job_number" type="text" class="form-control form-control-sm border-primary" 
                             placeholder="Enter Job #..." @keyup.enter="fetchJobData" @input="onJobNumberInput">
                      <button class="btn btn-sm btn-primary px-2" type="button" @click="fetchJobData" :disabled="loadingJob">
                        <i class="bi bi-search" v-if="!loadingJob"></i>
                        <span v-else class="spinner-border spinner-border-sm" style="width: 12px; height: 12px;"></span>
                      </button>
                      <button v-if="form.job_number" class="btn btn-sm btn-outline-danger px-2" type="button" @click="clearJobData">
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Dispatch Date <span class="text-danger">*</span></label>
                    <input v-model="form.date" type="date" class="form-control form-control-sm" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">DC # <span class="text-danger">*</span></label>
                    <input v-model="form.dc_number" type="text" class="form-control form-control-sm" required placeholder="DC No.">
                  </div>
                </div>

                <!-- Column 2: Data Display (Expanded) -->
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Customer</label>
                    <select v-model="form.customer_id" @change="onCustomerChange" class="form-select form-select-sm" :disabled="jobInfo.job_number && !isSharedDispatchJob">
                      <option value="">Select Customer</option>
                      <option v-for="c in dispatchCustomers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Dispatch Item</label>
                    <select v-model="form.product_option_key" @change="onProductOptionChange" class="form-select form-select-sm" :disabled="!form.customer_id || (jobInfo.job_number && !isSharedDispatchJob)">
                      <option value="">Select Product</option>
                      <option v-for="p in productOptions" :key="p.option_key" :value="p.option_key">
                        {{ p.display_item_code }} - {{ p.display_item_name }}{{ productPolicySuffix(p) }}
                      </option>
                    </select>
                    <div v-if="selectedProductOption" class="x-small text-muted mt-1">
                      Rate: {{ formatRate(selectedProductOption.display_rate) }} | Stock Product: {{ selectedProductOption.item_name }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1 text-primary">Qty to Dispatch <span class="text-danger">*</span></label>
                    <input v-model="form.quantity_dispatched" type="number" step="0.01" class="form-control form-control-sm border-primary fw-bold text-primary" required>
                  </div>
                </div>

                <!-- Column 3: Summary & Actions (Shrunk) -->
                <div class="col-md-3 border-start ps-3">
                  <div class="d-flex flex-column h-100 justify-content-between">
                    <div>
                      <label class="form-label fw-bold text-muted x-small uppercase mb-1">Job Balance</label>
                      <div class="balance-display py-2 px-3 rounded text-center mb-2" :class="jobInfo.balance > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'">
                        <span class="h3 fw-bold mb-0 d-block line-height-1">{{ formatNumber(jobInfo.balance) }}</span>
                        <div class="x-small opacity-75">Available Qty</div>
                      </div>
                      <div v-if="jobInfo.balance !== null && form.quantity_dispatched > jobInfo.balance" class="text-danger x-small fw-bold animate-pulse">
                        <i class="bi bi-exclamation-triangle-fill"></i> Exceeds balance!
                      </div>
                    </div>
                    
                    <div class="d-flex flex-column gap-2 mt-2">
                      <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 shadow-sm" :disabled="!jobInfo.product || (jobInfo.balance !== null && form.quantity_dispatched > jobInfo.balance)">
                        <i class="bi bi-check-circle-fill me-1"></i> Save Dispatch
                      </button>
                      <button @click="showForm = false" type="button" class="btn btn-outline-secondary btn-sm w-100 py-1">Cancel</button>
                    </div>
                  </div>
                </div>

                <!-- Footer Row: Remarks -->
                <div class="col-md-12 mt-1">
                  <div class="border-top pt-2">
                    <label class="form-label fw-bold text-muted x-small uppercase mb-1">Remarks / Notes</label>
                    <textarea v-model="form.remarks" class="form-control form-control-sm bg-light border-0" rows="1" placeholder="Add any notes here..."></textarea>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Right Panel: Job History (Shrunk) -->
      <div class="col-md-3" v-if="jobInfo.job_number">
        <div class="card shadow border-0 h-100 dispatch-history-card">
          <div class="card-header dispatch-header text-white py-2 px-3">
            <h6 class="mb-0 d-flex align-items-center gap-2 fw-bold" style="font-size: 0.85rem;">
              <i class="bi bi-clock-history fs-5"></i> 
              Dispatch History: Job # {{ jobInfo.job_number }}
            </h6>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0 custom-history-table">
                <thead>
                  <tr class="bg-light text-muted uppercase x-small">
                    <th class="ps-3 py-2">Date</th>
                    <th class="py-2 text-center">DC #</th>
                    <th class="text-end pe-3 py-2">Qty</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in jobInfo.history" :key="h.id" class="align-middle">
                    <td class="ps-3 py-1 text-muted small">{{ formatDate(h.date) }}</td>
                    <td class="fw-bold text-dark text-center small">{{ h.dc_number }}</td>
                    <td class="text-end pe-3 fw-bold text-dark small">{{ formatNumber(h.quantity_dispatched) }}</td>
                  </tr>
                  <tr v-if="!jobInfo.history || jobInfo.history.length === 0">
                    <td colspan="3" class="text-center text-muted py-4">
                      <div class="small opacity-50">No history found.</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer p-0 border-0 overflow-hidden rounded-bottom">
            <div class="d-flex justify-content-between align-items-center p-2 bg-white border-top border-bottom">
              <span class="fw-bold text-muted uppercase x-small ps-1">Total Dispatched</span>
              <span class="h5 fw-bold mb-0 text-dark pe-1">{{ formatNumber(jobInfo.total_dispatched) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center p-2 bg-cyan-light">
              <span class="fw-bold text-dark uppercase x-small ps-1">Total Produced</span>
              <span class="h5 fw-bold mb-0 text-dark pe-1">{{ formatNumber(jobInfo.total_produced) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
      <div class="card-body p-3">
        <div class="dispatch-filter-grid">
          <div>
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-person"></i> Customer</label>
            <select v-model="filters.customer_id" @change="fetchDispatches" class="form-select border-0 bg-light">
              <option value="">All Customers</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-hash"></i> Job #</label>
            <input v-model="filters.job_number" @input="debouncedFetch" type="text" class="form-control border-0 bg-light" placeholder="Search...">
          </div>
          <div>
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-box-seam"></i> Item / Code</label>
            <input v-model="filters.item_search" @input="debouncedFetch" type="text" class="form-control border-0 bg-light" placeholder="Code or name...">
          </div>
          <div>
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-file-earmark-text"></i> DC #</label>
            <input v-model="filters.dc_number" @input="debouncedFetch" type="text" class="form-control border-0 bg-light" placeholder="Search...">
          </div>
          <div>
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-calendar"></i> From</label>
            <input v-model="filters.date_from" type="date" class="form-control border-0 bg-light" @change="fetchDispatches">
          </div>
          <div>
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-calendar"></i> To</label>
            <input v-model="filters.date_to" type="date" class="form-control border-0 bg-light" @change="fetchDispatches">
          </div>
          <div>
            <label class="small text-muted fw-bold uppercase mb-1 d-block"><i class="bi bi-list-ol"></i> Show</label>
            <select v-model="filters.per_page" @change="fetchDispatches(1)" class="form-control border-0 bg-light">
              <option value="50">50</option>
              <option value="100">100</option>
              <option value="200">200</option>
              <option value="500">500</option>
            </select>
          </div>
          <div class="dispatch-clear-filter">
            <button @click="clearFilters" class="btn btn-clear-filters border-0" title="Clear Filters"><i class="bi bi-eraser-fill"></i></button>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 custom-main-table">
          <thead class="table-dark">
            <tr>
              <th class="ps-4">S.No.</th>
              <th>Date</th>
              <th class="text-center">Job #</th>
              <th>Customer</th>
              <th>Product</th>
              <th class="text-end">Rate</th>
              <th class="text-end">Amount</th>
              <th class="text-center">DC #</th>
              <th class="text-end">Qty Dispatched</th>
              <th class="text-center pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(d, index) in dispatches" :key="d.id">
              <td class="ps-4 text-muted small">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
              <td class="fw-bold">{{ formatDate(d.date) }}</td>
              <td class="text-center">
                <button
                  v-if="d.job_number"
                  type="button"
                  class="badge bg-primary-subtle text-primary px-3 border-0 job-link"
                  title="View selected job report"
                  @click="openJobMovement(d)"
                >
                  {{ d.job_number }}
                </button>
                <span v-else class="badge bg-light text-muted px-3">-</span>
              </td>
              <td><span class="text-dark">{{ d.customer?.name }}</span></td>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-bold">{{ d.dispatch_item_name || d.product?.item_name }}</span>
                  <span class="badge bg-light text-muted border-0 p-0 text-start">Code: {{ d.dispatch_item_code || d.product?.item_code }}</span>
                </div>
              </td>
              <td class="text-end fw-bold">{{ formatRate(d.dispatch_rate) }}</td>
              <td class="text-end fw-bold text-success">{{ formatRate(d.dispatch_amount) }}</td>
              <td class="text-center fw-bold text-dark">{{ d.dc_number }}</td>
              <td class="text-end fw-bold text-danger mb-0">{{ formatNumber(d.quantity_dispatched) }}</td>
              <td class="text-center pe-4">
                <div class="btn-group shadow-sm rounded">
                  <button @click="showDetail(d)" class="btn btn-sm btn-white text-info border-end" title="View"><i class="bi bi-eye-fill"></i></button>
                  <button @click="editDispatch(d)" class="btn btn-sm btn-white text-warning border-end" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                  <button @click="initReversal(d)" class="btn btn-sm btn-white text-danger border-end" title="Reverse"><i class="bi bi-arrow-counterclockwise"></i></button>
                  <button @click="deleteDispatch(d)" class="btn btn-sm btn-white text-danger" title="Delete"><i class="bi bi-trash-fill"></i></button>
                </div>
              </td>
            </tr>
            <tr v-if="dispatches.length === 0">
              <td colspan="10" class="text-center py-5">
                <div class="py-4">
                  <i class="bi bi-inbox fs-1 text-muted opacity-25"></i>
                  <h5 class="text-muted mt-3">No dispatch entries found matching your criteria.</h5>
                </div>
              </td>
            </tr>
          </tbody>
          <tfoot v-if="dispatches.length > 0">
            <tr class="table-info fw-bold">
              <td colspan="8" class="text-end ps-4">Page Total:</td>
              <td class="text-end text-danger mb-0">{{ formatNumber(pageTotal) }}</td>
              <td></td>
            </tr>
            <tr class="table-secondary fw-bold" v-if="grandTotal > 0">
              <td colspan="8" class="text-end ps-4">Grand Total:</td>
              <td class="text-end text-danger mb-0">{{ formatNumber(grandTotal) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedEntry" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-dark text-white py-2">
            <h5 class="modal-title small fw-bold uppercase">Dispatch Details: {{ selectedEntry.dc_number }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="selectedEntry = null"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="detail-item mb-3">
                  <label class="x-small text-muted uppercase fw-bold d-block">Dispatch Date</label>
                  <span class="fw-bold">{{ formatDate(selectedEntry.date) }}</span>
                </div>
                <div class="detail-item mb-3">
                  <label class="x-small text-muted uppercase fw-bold d-block">DC Number</label>
                  <span class="fw-bold fs-5 text-primary">{{ selectedEntry.dc_number }}</span>
                </div>
                <div class="detail-item mb-3">
                  <label class="x-small text-muted uppercase fw-bold d-block">Customer</label>
                  <span class="fw-bold">{{ selectedEntry.customer?.name }}</span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="detail-item mb-3">
                  <label class="x-small text-muted uppercase fw-bold d-block">Job Number</label>
                  <span class="badge bg-primary px-3">{{ selectedEntry.job_number || '-' }}</span>
                </div>
                <div class="detail-item mb-3">
                  <label class="x-small text-muted uppercase fw-bold d-block">Product</label>
                  <span class="fw-bold">{{ selectedEntry.dispatch_item_code || selectedEntry.product?.item_code }} - {{ selectedEntry.dispatch_item_name || selectedEntry.product?.item_name }}</span>
                  <div class="small text-muted">Rate: {{ formatRate(selectedEntry.dispatch_rate) }} | Amount: {{ formatRate(selectedEntry.dispatch_amount) }}</div>
                </div>
                <div class="detail-item mb-3">
                  <label class="x-small text-muted uppercase fw-bold d-block">Quantity Dispatched</label>
                  <span class="fw-bold fs-4 text-danger">{{ formatNumber(selectedEntry.quantity_dispatched) }}</span>
                </div>
              </div>
              <div class="col-md-12 border-top pt-3">
                <div class="detail-item mb-3">
                  <label class="x-small text-muted uppercase fw-bold d-block">Remarks</label>
                  <p class="mb-0 text-muted">{{ selectedEntry.remarks || 'No remarks provided.' }}</p>
                </div>
                <div class="detail-item">
                  <label class="x-small text-muted uppercase fw-bold d-block">Created By</label>
                  <span class="small text-muted">{{ selectedEntry.creator?.name || 'Unknown' }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" @click="selectedEntry = null">Close</button>
            <button type="button" class="btn btn-primary btn-sm" @click="printDispatch(selectedEntry)">
              <i class="bi bi-printer me-1"></i> Print
            </button>
            <button type="button" class="btn btn-danger btn-sm" @click="printDispatch(selectedEntry)">
              <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
            <button type="button" class="btn btn-warning btn-sm" @click="editDispatch(selectedEntry); selectedEntry = null">Edit Entry</button>
            <button type="button" class="btn btn-danger btn-sm" @click="initReversal(selectedEntry); selectedEntry = null">Reverse Entry</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reversal Modal -->
    <div v-if="reversalTarget" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-danger text-white py-2">
            <h5 class="modal-title small fw-bold">Reverse Dispatch: {{ reversalTarget.dc_number }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="reversalTarget = null"></button>
          </div>
          <div class="modal-body p-4">
            <p class="text-slate-800 small mb-3">
              This action will create a signed reversal ledger entry to restore the dispatched inventory (+{{ formatNumber(reversalTarget.quantity_dispatched) }} units).
            </p>
            <div class="mb-3">
              <label class="form-label fw-bold small">Reason for Reversal <span class="text-danger">*</span></label>
              <textarea v-model="reversalReason" class="form-control" rows="2" placeholder="e.g. Logged incorrect DC number by mistake..."></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end">
              <button @click="confirmReversal" class="btn btn-danger btn-sm px-4" :disabled="reversing">
                <span v-if="reversing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                Confirm Reversal
              </button>
              <button @click="reversalTarget = null" class="btn btn-secondary btn-sm px-4">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Selected Job Report Modal -->
    <div v-if="selectedJobDetail" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-primary text-white py-2">
            <h5 class="modal-title small fw-bold uppercase">Selected Job Report: {{ selectedJobDetail.job_number }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="selectedJobDetail = null"></button>
          </div>
          <div class="modal-body p-4">
            <div v-if="loadingJobDetail" class="text-center py-5">
              <span class="spinner-border text-primary"></span>
            </div>
            <template v-else>
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="x-small text-muted uppercase fw-bold d-block">Customer</label>
                  <span class="fw-bold">{{ selectedJobDetail.customer?.name || '-' }}</span>
                </div>
                <div class="col-md-4">
                  <label class="x-small text-muted uppercase fw-bold d-block">Item</label>
                  <span class="fw-bold">{{ selectedJobDetail.product?.item_code || '-' }} - {{ selectedJobDetail.product?.item_name || '-' }}</span>
                </div>
                <div class="col-md-4">
                  <label class="x-small text-muted uppercase fw-bold d-block">Balance</label>
                  <span class="fw-bold fs-5 text-primary">{{ formatNumber(selectedJobDetail.remaining_balance) }}</span>
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-md-4">
                  <div class="job-summary-box bg-cyan-light">
                    <span>Total Receipt</span>
                    <strong>{{ formatNumber(selectedJobDetail.total_produced) }}</strong>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="job-summary-box bg-light">
                    <span>Total Dispatch</span>
                    <strong>{{ formatNumber(selectedJobDetail.total_dispatched) }}</strong>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="job-summary-box bg-primary-subtle">
                    <span>Remaining</span>
                    <strong>{{ formatNumber(selectedJobDetail.remaining_balance) }}</strong>
                  </div>
                </div>
              </div>

              <div class="table-responsive">
                <h6 class="fw-bold mb-2">Receipt & Dispatch Ledger</h6>
                <table class="table table-sm table-bordered align-middle mb-0 job-movement-table">
                  <thead class="table-light">
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Customer</th>
                      <th>Item</th>
                      <th class="text-center">DC #</th>
                      <th class="text-end">Receipt Qty</th>
                      <th class="text-end">Dispatch Qty</th>
                      <th class="text-end">Balance</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="movement in selectedJobDetail.movements" :key="`${movement.type}-${movement.source_id}`">
                      <td>{{ formatDate(movement.date) }}</td>
                      <td>
                        <span class="badge" :class="movement.type === 'receipt' ? 'bg-success' : 'bg-danger'">
                          {{ movement.type === 'receipt' ? 'Receipt' : 'Dispatch' }}
                        </span>
                      </td>
                      <td>{{ movement.customer_name || '-' }}</td>
                      <td>
                        <div class="fw-bold">{{ movement.item_name || '-' }}</div>
                        <div class="x-small text-muted">Code: {{ movement.item_code || '-' }}</div>
                      </td>
                      <td class="text-center fw-bold">{{ movement.dc_number || '-' }}</td>
                      <td class="text-end text-success fw-bold">{{ movement.receipt_qty ? formatNumber(movement.receipt_qty) : '-' }}</td>
                      <td class="text-end text-danger fw-bold">{{ movement.dispatch_qty ? formatNumber(movement.dispatch_qty) : '-' }}</td>
                      <td class="text-end fw-bold text-primary">{{ formatNumber(movement.balance) }}</td>
                    </tr>
                    <tr v-if="!selectedJobDetail.movements || selectedJobDetail.movements.length === 0">
                      <td colspan="8" class="text-center text-muted py-4">No receipt or dispatch entries found.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" @click="selectedJobDetail = null">Close</button>
            <button type="button" class="btn btn-primary btn-sm" :disabled="loadingJobDetail" @click="printJobMovement">
              <i class="bi bi-printer me-1"></i> Print
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4" v-if="pagination.total > 0">
      <div class="text-muted small">Showing {{ dispatches.length }} of {{ pagination.total }} entries</div>
      <nav v-if="pagination.last_page > 1">
        <ul class="pagination pagination-sm shadow-sm mb-0">
          <li class="page-item" :class="{ disabled: pagination.current_page == 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">Previous</a>
          </li>
          <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page == pagination.current_page }">
            <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page == pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">Next</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  props: { user: { type: Object, default: null } },
  data() {
    return {
      dispatches: [], 
      customers: [], 
      customerProducts: [],
      loadingJob: false,
      manualMode: false,
      showForm: false, 
      editing: false,
      jobInfo: { job_number: '', customer: null, product: null, balance: null, total_produced: 0, total_dispatched: 0, history: [] },
      form: { id: null, date: new Date().toISOString().substr(0, 10), customer_id: '', product_id: '', fg_product_customer_link_id: '', product_option_key: '', job_number: '', dc_number: '', quantity_dispatched: '', remarks: '' },
      filters: { customer_id: '', job_number: '', item_search: '', dc_number: '', date_from: '', date_to: '', per_page: 50 },
      searchTimeout: null,
      pagination: { current_page: 1, last_page: 1, per_page: 50, total: 0 },
      grandTotal: 0,
      selectedEntry: null,
      selectedJobDetail: null,
      loadingJobDetail: false,
      companyName: 'QUALITY CARTONS (PVT.) LTD.',
      companyAddress: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
      companyLogo: window.location.origin + '/images/quality-cartons-logo.svg',
      originalQty: null,
      reversalTarget: null,
      reversalReason: '',
      reversing: false
    };
  },
  computed: {
    pages() { const c = this.pagination.current_page, l = this.pagination.last_page; let s = Math.max(1, c - 2), e = Math.min(l, c + 2), p = []; for (let i = s; i <= e; i++) p.push(i); return p; },
    pageTotal() {
      return this.dispatches.reduce((acc, d) => acc + Number(d.quantity_dispatched || 0), 0);
    },
    isSharedDispatchJob() {
      return this.jobInfo.product?.dispatch_policy === 'shared_product';
    },
    dispatchCustomers() {
      if (!this.isSharedDispatchJob) return this.customers;
      const owner = this.jobInfo.customer ? [{ id: this.jobInfo.customer.id, name: this.jobInfo.customer.name, items_count: 1 }] : [];
      const linked = this.jobInfo.linked_customers || [];
      const customersById = new Map();
      [...owner, ...linked].forEach(customer => {
        if (customer?.id) customersById.set(Number(customer.id), customer);
      });
      return customersById.size ? Array.from(customersById.values()) : this.customers;
    },
    productOptions() {
      const selectedCustomerId = Number(this.form.customer_id || 0);
      const baseProducts = this.isSharedDispatchJob && this.jobInfo.product ? [this.jobInfo.product] : this.customerProducts;
      const options = [];

      baseProducts.forEach(product => {
        if (product.dispatch_policy === 'shared_product') {
          if (Number(product.customer_id) === selectedCustomerId) {
            options.push({
              ...product,
              option_key: `${product.id}:`,
              dispatch_link_id: '',
              display_item_code: product.item_code,
              display_item_name: product.item_name,
              display_rate: product.rate,
              is_original_item: true,
            });
          }

          (product.customer_links || [])
            .filter(link => Number(link.customer_id) === selectedCustomerId && (link.status || 'Active') === 'Active')
            .forEach(link => {
              options.push({
                ...product,
                option_key: `${product.id}:${link.id}`,
                dispatch_link_id: link.id,
                display_item_code: link.customer_item_code,
                display_item_name: link.customer_item_name,
                display_rate: link.customer_rate,
              });
            });
        } else {
          options.push({
            ...product,
            option_key: `${product.id}:`,
            dispatch_link_id: '',
            display_item_code: product.item_code,
            display_item_name: product.item_name,
            display_rate: product.rate,
          });
        }
      });

      return options;
    },
    selectedProductOption() {
      return this.productOptions.find(p => p.option_key === this.form.product_option_key) || null;
    }
  },
  mounted() {
    if (localStorage.getItem('token')) axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
    this.fetchCustomers(); this.fetchDispatches(); this.fetchSettings();
  },
  methods: {
    fetchSettings() {
      axios.get('/api/setup/settings').then(response => {
        const data = response.data || {};
        if (data.company_name) this.companyName = data.company_name;
        if (data.company_address) this.companyAddress = data.company_address;
        if (data.company_logo) {
          this.companyLogo = window.location.origin + '/storage/' + data.company_logo;
        } else {
          this.companyLogo = window.location.origin + '/images/quality-cartons-logo.svg';
        }
      }).catch(error => { console.error('Error fetching settings:', error); });
    },
    fetchCustomers() { axios.get('/api/customers').then(r => { this.customers = r.data; }); },
    
    fetchJobData() {
      if (!this.form.job_number) return;
      this.loadingJob = true;
      this.manualMode = false;
      axios.get(`/api/fg-dispatches/job-details/${this.form.job_number}`)
        .then(r => {
          this.jobInfo = r.data;
          if (this.editing && this.originalQty) {
            this.jobInfo.balance = (parseFloat(this.jobInfo.balance) || 0) + parseFloat(this.originalQty);
          }
          this.form.customer_id = r.data.customer.id;
          this.form.product_id = r.data.product.id;
          this.form.fg_product_customer_link_id = '';
          this.form.product_option_key = r.data.product.dispatch_policy === 'shared_product' ? '' : `${r.data.product.id}:`;
          if (r.data.product.dispatch_policy === 'shared_product') {
            if (!this.editing) {
              this.form.customer_id = '';
              this.form.product_option_key = '';
            }
            this.customerProducts = [];
            this.$message.success('Shared product found. Select linked customer and item for dispatch.');
          } else {
            this.fetchProductsByCustomer();
            this.$message.success('Job details fetched.');
          }
          this.manualMode = false;
        })
        .catch(err => {
          this.jobInfo = { job_number: '', customer: null, product: null, balance: null, history: [] };
          this.manualMode = true;
          this.$message.warning('Job not found. You can now select customer/product manually.');
        })
        .finally(() => { this.loadingJob = false; });
    },

    clearJobData() {
        this.form.job_number = '';
        this.form.customer_id = '';
        this.form.product_id = '';
        this.form.fg_product_customer_link_id = '';
        this.form.product_option_key = '';
        this.jobInfo = { job_number: '', customer: null, product: null, balance: null, total_produced: 0, total_dispatched: 0, history: [] };
        this.manualMode = true;
        this.customerProducts = [];
    },

    onJobNumberInput() {
        if (!this.form.job_number) {
            this.clearJobData();
        }
    },

    onCustomerChange() {
        this.form.product_id = '';
        this.form.fg_product_customer_link_id = '';
        this.form.product_option_key = '';
        if (this.isSharedDispatchJob) {
          return;
        }
        if (!this.form.job_number) {
          this.clearJobData();
        }
        this.fetchProductsByCustomer();
    },

    fetchProductsByCustomer() {
      if (!this.form.customer_id) {
          this.customerProducts = [];
          return;
      }
      axios.get(`/api/products/by-customer/${this.form.customer_id}`).then(r => {
        this.customerProducts = r.data;
      });
    },

    productPolicySuffix(product) {
      if (!product) return '';
      if (product.dispatch_policy === 'shared_product') {
        const owner = product.customer?.name ? ` / Owner: ${product.customer.name}` : '';
        return ` (Shared${owner})`;
      }
      return ' (Restricted)';
    },

    onProductOptionChange() {
      const option = this.selectedProductOption;
      if (!option) {
        this.form.product_id = '';
        this.form.fg_product_customer_link_id = '';
        return;
      }

      this.form.product_id = option.id;
      this.form.fg_product_customer_link_id = option.dispatch_link_id || '';

      if (!this.isSharedDispatchJob) {
        this.fetchProductData();
      }
    },

    fetchProductData() {
        if (!this.form.product_id) return;
        if (this.form.job_number && this.form.job_number !== 'MANUAL/OPENING') return;

        this.loadingJob = true;
        axios.get(`/api/fg-dispatches/product-details/${this.form.product_id}`)
            .then(r => {
                this.jobInfo = r.data;
                if (this.editing && this.originalQty) {
                  this.jobInfo.balance = (parseFloat(this.jobInfo.balance) || 0) + parseFloat(this.originalQty);
                }
                this.form.job_number = 'MANUAL/OPENING';
                this.manualMode = false;
                this.$message.success('Stock details fetched.');
            })
            .catch(err => {
                this.$message.error('Failed to fetch product details.');
            })
            .finally(() => { this.loadingJob = false; });
    },

    fetchDispatches(page = 1) {
      const params = { page, ...this.filters };
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
      axios.get('/api/fg-dispatches', { params }).then(r => {
        const data = r.data.pagination || r.data;
        this.dispatches = data.data;
        this.pagination = { current_page: data.current_page, last_page: data.last_page, per_page: data.per_page, total: data.total };
        if (r.data.totals) {
          this.grandTotal = r.data.totals.total_quantity_dispatched;
        }
      }).catch(err => {
        if (axios.isCancel(err)) return;
        if (err.response?.data?.errors) {
          const msgs = Object.values(err.response.data.errors).flat().join('\n');
          this.$message.error(msgs);
        } else {
          this.$message.error(err.response?.data?.error || err.response?.data?.message || 'Error fetching dispatches.');
        }
      });
    },
    showDetail(d) {
      this.selectedEntry = d;
    },
    openJobMovement(d) {
      if (!d.job_number) return;

      this.loadingJobDetail = true;
      this.selectedJobDetail = {
        job_number: d.job_number,
        customer: d.customer || null,
        product: d.product || null,
        movements: [],
        total_produced: 0,
        total_dispatched: 0,
        remaining_balance: 0,
      };

      axios.get('/api/fg-dispatches/job-movement/detail', {
        params: { job_number: d.job_number, product_id: d.product_id }
      }).then(r => {
        this.selectedJobDetail = r.data;
      }).catch(err => {
        this.selectedJobDetail = null;
        this.$message.error(err.response?.data?.error || 'Unable to fetch job movement.');
      }).finally(() => {
        this.loadingJobDetail = false;
      });
    },
    debouncedFetch() { clearTimeout(this.searchTimeout); this.searchTimeout = setTimeout(() => this.fetchDispatches(), 400); },
    goToPage(p) { if (p >= 1 && p <= this.pagination.last_page) this.fetchDispatches(p); },
    clearFilters() { this.filters = { customer_id: '', job_number: '', item_search: '', dc_number: '', date_from: '', date_to: '', per_page: 50 }; this.fetchDispatches(); },
    
    resetForm() { 
      this.originalQty = null;
      this.form = { id: null, date: new Date().toISOString().substr(0, 10), customer_id: '', product_id: '', fg_product_customer_link_id: '', product_option_key: '', job_number: '', dc_number: '', quantity_dispatched: '', remarks: '' }; 
      this.jobInfo = { job_number: '', customer: null, product: null, balance: null, total_produced: 0, total_dispatched: 0, history: [] };
      this.manualMode = true;
      this.customerProducts = [];
    },

    saveDispatch() {
      if (!this.form.customer_id || !this.form.product_id || !this.form.dc_number || !this.form.quantity_dispatched) { 
        this.$message.error('Fill all required fields.'); return; 
      }
      const action = this.editing ? axios.put(`/api/fg-dispatches/${this.form.id}`, this.form) : axios.post('/api/fg-dispatches', this.form);
      action.then(() => { 
        this.$message.success(this.editing ? 'Updated successfully!' : 'Dispatch saved successfully!'); 
        this.showForm = false; 
        this.fetchDispatches(); 
      })
      .catch(err => { this.$message.error(err.response?.data?.error || 'Operation failed.'); });
    },

    editDispatch(d) {
      this.originalQty = d.quantity_dispatched;
      this.form = { 
        id: d.id, 
        date: d.date?.split('T')[0], 
        customer_id: d.customer_id, 
        product_id: d.product_id, 
        fg_product_customer_link_id: d.fg_product_customer_link_id || '',
        product_option_key: `${d.product_id}:${d.fg_product_customer_link_id || ''}`,
        job_number: d.job_number, 
        dc_number: d.dc_number, 
        quantity_dispatched: d.quantity_dispatched, 
        remarks: d.remarks 
      };
      if (this.form.job_number && this.form.job_number !== 'MANUAL/OPENING') {
        this.fetchJobData();
      } else if (this.form.product_id) {
        this.fetchProductData();
      }
      this.editing = true; 
      this.showForm = true;
      this.$nextTick(() => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    },

    deleteDispatch(d) {
      if (!confirm('Are you sure you want to delete this dispatch entry?')) return;
      axios.delete(`/api/fg-dispatches/${d.id}`).then(() => { this.$message.success('Deleted.'); this.fetchDispatches(); })
        .catch(err => { this.$message.error(err.response?.data?.error || 'Cannot delete.'); });
    },
    initReversal(d) {
      this.reversalTarget = d;
      this.reversalReason = '';
      this.reversing = false;
    },
    confirmReversal() {
      if (!this.reversalReason) {
        this.$message.warning('Please provide a reason for the reversal.');
        return;
      }
      this.reversing = true;
      axios.post(`/api/fg-dispatches/${this.reversalTarget.id}/reverse`, { reason: this.reversalReason })
        .then(() => {
          this.$message.success('Dispatch reversed successfully.');
          this.reversalTarget = null;
          this.fetchDispatches();
        })
        .catch(err => {
          const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to reverse dispatch.';
          this.$message.error(msg);
        })
        .finally(() => {
          this.reversing = false;
        });
    },
    formatDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-GB'); },
    formatNumber(v) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }); },
    formatRate(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },
    printJobMovement() {
      if (!this.selectedJobDetail) return;

      const detail = this.selectedJobDetail;
      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        alert('Please allow pop-ups to print.');
        return;
      }

      const rows = (detail.movements || []).map(movement => `
        <tr>
          <td>${this.escapeHtml(this.formatDate(movement.date))}</td>
          <td>${movement.type === 'receipt' ? 'Receipt' : 'Dispatch'}</td>
          <td>${this.escapeHtml(movement.customer_name || '-')}</td>
          <td>${this.escapeHtml(movement.item_code || '-')} - ${this.escapeHtml(movement.item_name || '-')}</td>
          <td class="text-center">${this.escapeHtml(movement.dc_number || '-')}</td>
          <td class="text-end">${movement.receipt_qty ? this.escapeHtml(this.formatNumber(movement.receipt_qty)) : '-'}</td>
          <td class="text-end">${movement.dispatch_qty ? this.escapeHtml(this.formatNumber(movement.dispatch_qty)) : '-'}</td>
          <td class="text-end">${this.escapeHtml(this.formatNumber(movement.balance))}</td>
        </tr>
      `).join('');

      const now = new Date();
      const timestamp = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

      printWindow.document.write(`
        <html>
          <head>
            <title>Selected Job Report ${this.escapeHtml(detail.job_number)} - ${this.escapeHtml(this.companyName)}</title>
            <style>
              @page { size: A4 landscape; margin: 10mm; }
              body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 10px; color: #000; }
              .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
              .company-name { font-size: 22px; font-weight: bold; }
              .report-title { font-size: 16px; margin-top: 5px; text-transform: uppercase; font-weight: bold; }
              .meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; font-size: 11px; }
              .meta-box { border: 1px solid #000; padding: 6px; }
              .label { font-size: 9px; text-transform: uppercase; color: #444; }
              .value { font-weight: bold; margin-top: 2px; }
              table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }
              th, td { border: 1px solid #000; padding: 5px 6px; word-wrap: break-word; }
              th { background: #f2f2f2; text-align: center; text-transform: uppercase; }
              .text-end { text-align: right; }
              .text-center { text-align: center; }
              .footer { margin-top: 12px; font-size: 9px; text-align: center; }
            </style>
          </head>
          <body>
            <div class="header">
              <div class="company-name">${this.escapeHtml(this.companyName)}</div>
              <div class="report-title">FG Selected Job Report</div>
            </div>
            <div class="meta">
              <div class="meta-box"><div class="label">Job #</div><div class="value">${this.escapeHtml(detail.job_number)}</div></div>
              <div class="meta-box"><div class="label">Customer</div><div class="value">${this.escapeHtml(detail.customer?.name || '-')}</div></div>
              <div class="meta-box"><div class="label">Item</div><div class="value">${this.escapeHtml(detail.product?.item_code || '-')} - ${this.escapeHtml(detail.product?.item_name || '-')}</div></div>
              <div class="meta-box"><div class="label">Balance</div><div class="value">${this.escapeHtml(this.formatNumber(detail.remaining_balance))}</div></div>
            </div>
            <div class="meta">
              <div class="meta-box"><div class="label">Total Receipt</div><div class="value">${this.escapeHtml(this.formatNumber(detail.total_produced))}</div></div>
              <div class="meta-box"><div class="label">Total Dispatch</div><div class="value">${this.escapeHtml(this.formatNumber(detail.total_dispatched))}</div></div>
              <div class="meta-box"><div class="label">Remaining</div><div class="value">${this.escapeHtml(this.formatNumber(detail.remaining_balance))}</div></div>
              <div class="meta-box"><div class="label">Printed</div><div class="value">${this.escapeHtml(timestamp)}</div></div>
            </div>
            <table>
              <thead>
                <tr>
                  <th width="70">Date</th>
                  <th width="65">Type</th>
                  <th width="130">Customer</th>
                  <th>Item</th>
                  <th width="70">DC #</th>
                  <th width="85">Receipt Qty</th>
                  <th width="85">Dispatch Qty</th>
                  <th width="85">Balance</th>
                </tr>
              </thead>
              <tbody>${rows}</tbody>
            </table>
            <div class="footer">Printed on: ${this.escapeHtml(timestamp)}</div>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    printDispatch(d) {
      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        alert('Please allow pop-ups to print.');
        return;
      }
      const now = new Date();
      const timestamp = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
      printWindow.document.write(`
        <html>
          <head>
            <title>DC #${d.dc_number} - ${this.companyName}</title>
            <style>
              @page { size: A4 portrait; margin: 10mm; }
              body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; color: #333; }
              .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #444; padding-bottom: 15px; margin-bottom: 25px; }
              .logo { width: 80px; height: 80px; object-fit: contain; }
              .company-info { text-align: center; flex-grow: 1; }
              .company-name { font-size: 24px; font-weight: bold; color: #1a2a6c; margin-bottom: 5px; }
              .company-address { font-size: 12px; color: #666; }
              .dc-title { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin-bottom: 20px; text-transform: uppercase; }
              .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
              .info-box { border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
              .info-label { font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 4px; }
              .info-value { font-size: 14px; font-weight: bold; }
              table { width: 100%; border-collapse: collapse; margin-bottom: 50px; }
              th, td { border: 1px solid #ddd; padding: 12px 15px; text-align: left; }
              th { background-color: #f8f9fa; font-size: 12px; text-transform: uppercase; }
              .text-end { text-align: right; }
              .footer { margin-top: 50px; display: flex; justify-content: space-between; }
              .signature-box { width: 200px; border-top: 1px solid #333; text-align: center; padding-top: 5px; font-size: 12px; }
            </style>
          </head>
          <body>
            <div class="header">
              <img src="${this.companyLogo}" alt="Logo" class="logo">
              <div class="company-info">
                <div class="company-name">${this.companyName}</div>
                <div class="company-address">${this.companyAddress}</div>
              </div>
              <div style="width: 80px;"></div>
            </div>
            <div class="dc-title">Delivery Challan</div>
            <div class="info-grid">
              <div class="info-box">
                <div class="info-label">Customer</div>
                <div class="info-value">${d.customer?.name || 'N/A'}</div>
              </div>
              <div class="info-box">
                <div class="info-label">DC Details</div>
                <div class="info-value">DC #: ${d.dc_number}</div>
                <div class="info-value">Date: ${this.formatDate(d.date)}</div>
                <div class="info-value">Job #: ${d.job_number || 'N/A'}</div>
              </div>
            </div>
            <table>
              <thead>
                <tr>
                  <th width="50">S#</th>
                  <th>Product Description</th>
                  <th class="text-end">Quantity</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>1</td>
                  <td>
                    <strong>${d.dispatch_item_name || d.product?.item_name || 'N/A'}</strong><br>
                    <small>Code: ${d.dispatch_item_code || d.product?.item_code || 'N/A'} | Rate: ${this.formatRate(d.dispatch_rate)} | Amount: ${this.formatRate(d.dispatch_amount)}</small>
                  </td>
                  <td class="text-end"><strong>${this.formatNumber(d.quantity_dispatched)}</strong></td>
                </tr>
              </tbody>
            </table>
            <div style="margin-bottom: 40px;">
              <strong>Remarks:</strong> ${d.remarks || 'No remarks.'}
            </div>
            <div class="footer">
              <div class="signature-box">Receiver's Signature</div>
              <div class="signature-box">For ${this.companyName}</div>
            </div>
            <div style="margin-top: 30px; font-size: 10px; color: #999; text-align: center;">
              Printed on: ${timestamp}
            </div>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    },
    printPage() {
      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        alert('Please allow pop-ups to print.');
        return;
      }
      const now = new Date();
      const timestamp = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
      
      const sortedDispatches = [...this.dispatches].sort((a, b) => new Date(a.date) - new Date(b.date));
      
      let tableRows = '';
      sortedDispatches.forEach((d, index) => {
        tableRows += `
          <tr>
            <td class="text-center">${(this.pagination.current_page - 1) * this.pagination.per_page + index + 1}</td>
            <td class="text-center">${this.formatDate(d.date)}</td>
            <td class="text-center">${d.job_number || '-'}</td>
            <td class="text-start">${d.customer?.name || '-'}</td>
            <td class="text-start">${d.dispatch_item_code || d.product?.item_code || ''} - ${d.dispatch_item_name || d.product?.item_name || '-'}</td>
            <td class="text-center">${d.dc_number}</td>
            <td class="text-end">${this.formatNumber(d.quantity_dispatched)}</td>
          </tr>
        `;
      });

      printWindow.document.write(`
        <html>
          <head>
            <title>FG Dispatch List - ${this.companyName}</title>
            <style>
              @page { size: A4 portrait; margin: 10mm; }
              body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 10px; color: #000; }
              .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
              .company-name { font-size: 22px; font-weight: bold; color: #000; }
              .report-title { font-size: 16px; margin-top: 5px; text-transform: uppercase; font-weight: bold; color: #000; }
              table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9px; table-layout: fixed; border: 1px solid #000; }
              th, td { border: 1px solid #000; padding: 4px 6px; word-wrap: break-word; color: #000; }
              th { background-color: #f2f2f2; font-weight: bold; text-align: center; text-transform: uppercase; color: #000; }
              .text-end { text-align: right; }
              .text-start { text-align: left; }
              .text-center { text-align: center; }
              .footer { margin-top: 20px; font-size: 9px; color: #000; text-align: center; }
              .summary-row { background-color: #f2f2f2; font-weight: bold; color: #000; }
              .grand-total-row { background-color: #e9ecef; font-weight: bold; font-size: 10px; color: #000; }
            </style>
          </head>
          <body>
            <div class="header">
              <div class="company-name">${this.companyName}</div>
              <div class="report-title">Finished Goods Dispatch List</div>
            </div>
            <table>
              <thead>
                <tr>
                  <th width="25">S#</th>
                  <th width="60">Date</th>
                  <th width="55">Job #</th>
                  <th width="110">Customer</th>
                  <th>Item Name</th>
                  <th width="50">DC #</th>
                  <th width="50">Qty</th>
                </tr>
              </thead>
              <tbody>
                ${tableRows}
              </tbody>
              <tfoot>
                ${(this.pageTotal !== this.grandTotal && this.grandTotal > 0) ? `
                <tr class="summary-row">
                  <td colspan="6" class="text-end">Page Total:</td>
                  <td class="text-end">${this.formatNumber(this.pageTotal)}</td>
                </tr>` : ''}
                ${this.grandTotal > 0 ? `
                <tr class="grand-total-row">
                  <td colspan="6" class="text-end">Grand Total:</td>
                  <td class="text-end">${this.formatNumber(this.grandTotal)}</td>
                </tr>` : ''}
              </tfoot>
            </table>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  }
};
</script>

<style scoped>
.page-title { font-weight: 800; color: #2d3436; letter-spacing: -0.5px; }
.uppercase { text-transform: uppercase; letter-spacing: 1px; }
.card { border-radius: 12px; transition: transform 0.2s; }
.btn-purple { background: #7048e8; color: white; border: none; font-weight: 600; border-radius: 8px; }
.btn-purple:hover { background: #5f3dc4; color: white; transform: translateY(-1px); }
.btn-white { background: white; border: 1px solid #eee; }
.btn-white:hover { background: #f8f9fa; }
.dispatch-filter-grid {
  align-items: end;
  display: grid;
  gap: 0.6rem;
  grid-template-columns:
    minmax(170px, 1.2fr)
    minmax(150px, 1fr)
    minmax(170px, 1.15fr)
    minmax(95px, 0.55fr)
    minmax(150px, 0.85fr)
    minmax(150px, 0.85fr)
    minmax(95px, 0.55fr)
    42px;
}
.dispatch-filter-grid .form-control,
.dispatch-filter-grid .form-select {
  font-size: 0.88rem;
  min-height: 38px;
  padding-bottom: 0.35rem;
  padding-top: 0.35rem;
}
.dispatch-clear-filter {
  align-self: end;
  display: flex;
  justify-content: flex-end;
}
.dispatch-clear-filter .btn-clear-filters {
  align-items: center;
  display: inline-flex;
  height: 38px;
  justify-content: center;
  min-height: 38px;
  padding: 0;
  width: 38px;
}
.job-link { cursor: pointer; font: inherit; line-height: 1.2; }
.job-link:hover { text-decoration: underline; }
.job-summary-box {
  align-items: center;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  padding: 0.65rem 0.8rem;
}
.job-summary-box span { color: #64748b; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
.job-summary-box strong { color: #0f172a; font-size: 1.05rem; }
.job-movement-table th { font-size: 0.7rem; text-transform: uppercase; }
.job-movement-table td { font-size: 0.82rem; }

.dispatch-header { background: #00BCD4; border-radius: 12px 12px 0 0 !important; }
.bg-cyan-light { background-color: #E0F7FA; }
.balance-display { background: #f1f3f5; border: 1px solid rgba(0,0,0,0.05); }

.custom-history-table thead th { background: transparent; border-bottom: 2px solid #f1f3f5; }
.custom-history-table tbody tr:last-child td { border-bottom: none; }

.custom-main-table thead th { font-weight: 700; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.5px; padding: 0.75rem 0.5rem; }
.custom-main-table tbody td { padding: 0.5rem 0.5rem; font-size: 0.8rem; }

.fade-in { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.25rem rgba(112, 72, 232, 0.15); border-color: #7048e8; }

@media (max-width: 1199.98px) {
  .dispatch-filter-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 767.98px) {
  .dispatch-filter-grid {
    grid-template-columns: 1fr;
  }

  .dispatch-clear-filter {
    justify-content: flex-start;
  }
}

[data-theme="dark"] .page-title {
  color: #e2e8f0;
}

[data-theme="dark"] .dispatch-header {
  background: #334155 !important;
}

[data-theme="dark"] .btn-white {
  background: #1e293b !important;
  border-color: #475569 !important;
}

[data-theme="dark"] .btn-white:hover {
  background: #334155 !important;
}

[data-theme="dark"] .bg-cyan-light,
[data-theme="dark"] .balance-display,
[data-theme="dark"] .custom-history-table thead th,
[data-theme="dark"] .card-footer .bg-white {
  background: #1e293b !important;
  color: #e2e8f0 !important;
}

[data-theme="dark"] .custom-main-table td,
[data-theme="dark"] .custom-main-table th,
[data-theme="dark"] .custom-history-table td,
[data-theme="dark"] .custom-history-table th {
  color: #e2e8f0 !important;
  border-color: #475569 !important;
}

[data-theme="dark"] .text-muted {
  color: #9fb0c6 !important;
}
</style>
