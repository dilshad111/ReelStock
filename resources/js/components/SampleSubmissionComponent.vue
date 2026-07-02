<template>
  <div class="sample-submission-management">
    <!-- Page Header -->
    <section class="ss-header">
      <div>
        <div class="ss-eyebrow">Finished Goods</div>
        <h2><i class="bi bi-clipboard2-data-fill"></i> Carton Sample Submissions</h2>
        <p>Submit and track corrugated carton samples sent to customers.</p>
      </div>
      <div class="ss-header-actions">
        <el-button type="primary" @click="activeTab = 'form'" :class="{ 'is-active-tab': activeTab === 'form' }">
          <i class="bi bi-plus-circle me-1"></i> New Sample
        </el-button>
        <el-button @click="activeTab = 'report'" :class="{ 'is-active-tab': activeTab === 'report' }">
          <i class="bi bi-table me-1"></i> Report
        </el-button>
      </div>
    </section>

    <!-- ============ TAB 1: SUBMISSION FORM ============ -->
    <section v-if="activeTab === 'form'" class="ss-card animate__animated animate__fadeIn">
      <div class="ss-section-title">
        <span><i class="bi bi-pencil-square me-2"></i>Sample Submission Form</span>
        <small>Fill all required fields and submit</small>
      </div>

      <form @submit.prevent="submitForm">
        <!-- Row 1: Customer, Date, Quantity -->
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="mb-3">
              <v-select
                v-model="form.customer_id"
                :options="customers"
                label="name"
                :reduce="c => c.id"
                placeholder="Search Customer..."
                :clearable="false"
              ></v-select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label fw-bold">Sample Date <span class="text-danger">*</span></label>
              <input v-model="form.sample_date" type="date" class="form-control" required>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label fw-bold">Sample Quantity <span class="text-danger">*</span></label>
              <input v-model.number="form.quantity" type="number" min="1" class="form-control" required placeholder="Quantity">
            </div>
          </div>
        </div>

        <!-- Row 2: UOM and Dimensions (UOM FIRST, then LxWxH) -->
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <div class="mb-3">
              <label class="form-label fw-bold">UOM <span class="text-danger">*</span></label>
              <select v-model="form.uom" class="form-control" required>
                <option value="mm">mm</option>
                <option value="cm">cm</option>
                <option value="inch">inch</option>
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="mb-3">
              <label class="form-label fw-bold">Length (L) <span class="text-danger">*</span></label>
              <div class="input-group">
                <input v-model.number="form.length" type="number" step="0.01" min="0.01" class="form-control" required placeholder="L">
                <span class="input-group-text">{{ form.uom }}</span>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="mb-3">
              <label class="form-label fw-bold">Width (W) <span class="text-danger">*</span></label>
              <div class="input-group">
                <input v-model.number="form.width" type="number" step="0.01" min="0.01" class="form-control" required placeholder="W">
                <span class="input-group-text">{{ form.uom }}</span>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="mb-3">
              <label class="form-label fw-bold">Height (H) <span class="text-danger">*</span></label>
              <div class="input-group">
                <input v-model.number="form.height" type="number" step="0.01" min="0.01" class="form-control" required placeholder="H">
                <span class="input-group-text">{{ form.uom }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Row 3: Print Type, Ply, Size Approval -->
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label fw-bold">Print Type <span class="text-danger">*</span></label>
              <select v-model="form.print_type" class="form-control" required>
                <option value="un-print">Un-Print</option>
                <option value="printed">Printed</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label fw-bold">Ply Selection <span class="text-danger">*</span></label>
              <select v-model="form.ply" class="form-control" required @change="onPlyChange(form.ply)">
                <option value="3">3-Ply</option>
                <option value="5">5-Ply</option>
                <option value="7">7-Ply</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3 d-flex flex-column justify-content-center h-100">
              <label class="form-label fw-bold invisible d-none d-md-block">Approval</label>
              <div class="form-check form-switch pt-2">
                <input class="form-check-input custom-switch-style" type="checkbox" id="sizeApprovalOnly" v-model="form.size_approval_only">
                <label class="form-check-label ms-2 fw-semibold text-slate-700" for="sizeApprovalOnly">Only for Size Approval</label>
              </div>
            </div>
          </div>
        </div>

        <!-- Row: Sample Made By, Joinery Technique -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-bold">Sample Made By</label>
              <input v-model="form.sample_made_by" type="text" class="form-control" placeholder="e.g. John Doe">
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-bold">Joinery Technique</label>
              <select v-model="form.joinery_technique" class="form-control">
                <option value="N/A">N/A</option>
                <option value="Stapling">Stapling</option>
                <option value="Side-Pasting">Side-Pasting</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Row 4: Remarks -->
        <div class="row g-3 mb-3">
          <div class="col-12">
            <div class="mb-3">
              <label class="form-label fw-bold">Remarks</label>
              <textarea v-model="form.remarks" class="form-control" rows="2" placeholder="Any special notes about this sample..."></textarea>
            </div>
          </div>
        </div>

        <!-- ========== PAPER CONSTRUCTION (conditional) ========== -->
        <div v-if="!form.size_approval_only" class="ss-paper-section animate__animated animate__fadeIn">
          <div class="ss-subsection-title">
            <i class="bi bi-layers-fill me-2"></i>
            Paper Material Construction
            <span class="badge bg-secondary ms-2">{{ form.ply }}-Ply</span>
          </div>

          <div class="ss-layer-grid">
            <div
              v-for="(layer, idx) in form.paper_layers"
              :key="idx"
              class="row g-2 align-items-center mb-2 animate__animated animate__fadeIn"
            >
              <div class="col-auto">
                <span class="ss-layer-badge">{{ layer.layer_sequence }}</span>
              </div>
              <div class="col-md-2">
                <input type="text" v-model="layer.paper_type" class="form-control fw-semibold" readonly placeholder="Layer Type">
              </div>
              <div class="col">
                <v-select
                  v-model="layer.paper_quality_id"
                  :options="paperQualities"
                  :get-option-label="q => q.quality + (q.standard_gsm ? ' (' + Math.round(q.standard_gsm) + ' gsm)' : '')"
                  :reduce="q => q.id"
                  placeholder="Select Paper Quality"
                  :clearable="false"
                  @option:selected="onLayerQualityChange(layer)"
                ></v-select>
              </div>
              <div class="col-md-3">
                <div class="input-group">
                  <input type="number" v-model.number="layer.gsm" class="form-control" placeholder="GSM" required min="1">
                  <span class="input-group-text">gsm</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========== ADD-ONS SECTION ========== -->
        <div class="ss-addons-section">
          <div class="ss-subsection-title">
            <i class="bi bi-puzzle-fill me-2"></i>
            Add-Ons (Optional)
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="ss-addon-toggle">
                <div class="form-check form-switch mb-0 d-flex align-items-center">
                  <input class="form-check-input custom-switch-style" type="checkbox" id="includeHoneycomb" v-model="includeHoneycomb" @change="onAddonToggle('honeycomb', includeHoneycomb)">
                  <label class="form-check-label ms-2 fw-bold text-slate-700" for="includeHoneycomb">
                    <i class="bi bi-hexagon me-1"></i> Include Honeycomb
                  </label>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="ss-addon-toggle">
                <div class="form-check form-switch mb-0 d-flex align-items-center">
                  <input class="form-check-input custom-switch-style" type="checkbox" id="includeSeparator" v-model="includeSeparator" @change="onAddonToggle('separator', includeSeparator)">
                  <label class="form-check-label ms-2 fw-bold text-slate-700" for="includeSeparator">
                    <i class="bi bi-grid-3x2 me-1"></i> Include Separator
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Honeycomb Fields -->
          <div v-if="includeHoneycomb" class="ss-addon-card animate__animated animate__fadeIn">
            <h6 class="ss-addon-card-title"><i class="bi bi-hexagon-fill me-2 text-warning"></i>Honeycomb Details</h6>
            <div class="row g-3">
              <div class="col-md-2">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Length</label>
                  <input v-model.number="honeycombData.length" type="number" step="0.01" min="0.01" class="form-control" required placeholder="L">
                </div>
              </div>
              <div class="col-md-2">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Width</label>
                  <input v-model.number="honeycombData.width" type="number" step="0.01" min="0.01" class="form-control" required placeholder="W">
                </div>
              </div>
              <div class="col-md-2">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Height</label>
                  <input v-model.number="honeycombData.height" type="number" step="0.01" min="0.01" class="form-control" required placeholder="H">
                </div>
              </div>
              <div class="col-md-2">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Ply</label>
                  <select v-model="honeycombData.ply" class="form-control" @change="generateAddonLayers('honeycomb')">
                    <option value="3">3-Ply</option>
                    <option value="5">5-Ply</option>
                    <option value="7">7-Ply</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Source</label>
                  <div>
                    <div class="form-check form-check-inline mt-1">
                      <input class="form-check-input" type="radio" v-model="honeycombData.source" id="hcSourceHouse" value="in-house">
                      <label class="form-check-label" for="hcSourceHouse">In-house</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                      <input class="form-check-input" type="radio" v-model="honeycombData.source" id="hcSourceOut" value="outsource">
                      <label class="form-check-label" for="hcSourceOut">Outsource</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Honeycomb Paper Layers -->
            <div v-if="!form.size_approval_only" class="ss-addon-layers mt-2">
              <label class="form-label fw-bold small text-muted mb-2">
                <i class="bi bi-layers me-1"></i> Paper Construction ({{ honeycombData.ply }}-Ply)
              </label>
              <div v-for="(layer, idx) in honeycombData.paper_layers" :key="'hc-' + idx" class="row g-2 align-items-center mb-1">
                <div class="col-auto">
                  <span class="ss-layer-badge-sm">{{ layer.layer_sequence }}</span>
                </div>
                <div class="col-md-2">
                  <input type="text" v-model="layer.paper_type" class="form-control form-control-sm" readonly placeholder="Paper Type">
                </div>
                <div class="col">
                  <v-select
                    v-model="layer.paper_quality_id"
                    :options="paperQualities"
                    :get-option-label="q => q.quality + (q.standard_gsm ? ' (' + Math.round(q.standard_gsm) + ' gsm)' : '')"
                    :reduce="q => q.id"
                    placeholder="Select Paper Quality"
                    :clearable="false"
                    class="v-select-sm"
                    @option:selected="onLayerQualityChange(layer)"
                  ></v-select>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm">
                    <input type="number" v-model.number="layer.gsm" class="form-control" placeholder="GSM" required min="1">
                    <span class="input-group-text">gsm</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Separator Fields -->
          <div v-if="includeSeparator" class="ss-addon-card animate__animated animate__fadeIn">
            <h6 class="ss-addon-card-title"><i class="bi bi-grid-3x2-gap-fill me-2 text-info"></i>Separator Details</h6>
            <div class="row g-3">
              <div class="col-md-2">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Length</label>
                  <input v-model.number="separatorData.length" type="number" step="0.01" min="0.01" class="form-control" required placeholder="L">
                </div>
              </div>
              <div class="col-md-2">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Width</label>
                  <input v-model.number="separatorData.width" type="number" step="0.01" min="0.01" class="form-control" required placeholder="W">
                </div>
              </div>
              <div class="col-md-2">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Height</label>
                  <input v-model.number="separatorData.height" type="number" step="0.01" min="0.01" class="form-control" required placeholder="H">
                </div>
              </div>
              <div class="col-md-2">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Ply</label>
                  <select v-model="separatorData.ply" class="form-control" @change="generateAddonLayers('separator')">
                    <option value="3">3-Ply</option>
                    <option value="5">5-Ply</option>
                    <option value="7">7-Ply</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-2">
                  <label class="form-label fw-bold small text-muted">Source</label>
                  <div>
                    <div class="form-check form-check-inline mt-1">
                      <input class="form-check-input" type="radio" v-model="separatorData.source" id="sepSourceHouse" value="in-house">
                      <label class="form-check-label" for="sepSourceHouse">In-house</label>
                    </div>
                    <div class="form-check form-check-inline mt-1">
                      <input class="form-check-input" type="radio" v-model="separatorData.source" id="sepSourceOut" value="outsource">
                      <label class="form-check-label" for="sepSourceOut">Outsource</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Separator Paper Layers -->
            <div v-if="!form.size_approval_only" class="ss-addon-layers mt-2">
              <label class="form-label fw-bold small text-muted mb-2">
                <i class="bi bi-layers me-1"></i> Paper Construction ({{ separatorData.ply }}-Ply)
              </label>
              <div v-for="(layer, idx) in separatorData.paper_layers" :key="'sep-' + idx" class="row g-2 align-items-center mb-1">
                <div class="col-auto">
                  <span class="ss-layer-badge-sm">{{ layer.layer_sequence }}</span>
                </div>
                <div class="col-md-2">
                  <input type="text" v-model="layer.paper_type" class="form-control form-control-sm" readonly placeholder="Paper Type">
                </div>
                <div class="col">
                  <v-select
                    v-model="layer.paper_quality_id"
                    :options="paperQualities"
                    :get-option-label="q => q.quality + (q.standard_gsm ? ' (' + Math.round(q.standard_gsm) + ' gsm)' : '')"
                    :reduce="q => q.id"
                    placeholder="Select Paper Quality"
                    :clearable="false"
                    class="v-select-sm"
                    @option:selected="onLayerQualityChange(layer)"
                  ></v-select>
                </div>
                <div class="col-md-3">
                  <div class="input-group input-group-sm">
                    <input type="number" v-model.number="layer.gsm" class="form-control" placeholder="GSM" required min="1">
                    <span class="input-group-text">gsm</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="d-flex justify-content-end mt-4 gap-2">
          <button type="button" class="btn btn-secondary py-2 px-4" @click="resetForm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
          </button>
          <button type="submit" class="btn btn-success py-2 px-4" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
            <i v-else class="bi bi-check-circle me-1"></i> Submit Sample
          </button>
        </div>
      </form>
    </section>

    <!-- ============ TAB 2: REPORT ============ -->
    <section v-if="activeTab === 'report'" class="ss-card animate__animated animate__fadeIn">
      <div class="ss-section-title">
        <span><i class="bi bi-table me-2"></i>Sample Submission Report</span>
        <small>Filter and view submitted samples</small>
      </div>

      <!-- Filters -->
      <div class="row g-3 mb-4 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-bold small text-muted">Customer</label>
          <v-select
            v-model="filters.customer_id"
            :options="customers"
            label="name"
            :reduce="c => c.id"
            placeholder="All Customers"
            :clearable="true"
            class="v-select-sm"
            @option:selected="fetchReport"
            @option:deselected="fetchReport"
          ></v-select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small text-muted">From Date</label>
          <input v-model="filters.date_from" type="date" class="form-control form-control-sm" @change="fetchReport">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small text-muted">To Date</label>
          <input v-model="filters.date_to" type="date" class="form-control form-control-sm" @change="fetchReport">
        </div>
        <div class="col-md-2">
          <button type="button" class="btn btn-sm btn-primary w-100" @click="fetchReport">
            <i class="bi bi-search me-1"></i> Search
          </button>
        </div>
      </div>

      <!-- Data Table -->
      <el-table
        :data="reportData"
        stripe
        border
        style="width: 100%"
        highlight-current-row
        :default-sort="{ prop: 'id', order: 'descending' }"
        v-loading="reportLoading"
        @row-click="showDetail"
        empty-text="No samples found. Adjust your filters or submit a new sample."
      >
        <el-table-column prop="id" label="ID" width="70" sortable />
        <el-table-column label="Date" width="130" sortable>
          <template #default="{ row }">
            {{ formatDate(row.sample_date) }}
          </template>
        </el-table-column>
        <el-table-column label="Customer" min-width="180">
          <template #default="{ row }">
            {{ row.customer?.name || '—' }}
          </template>
        </el-table-column>
        <el-table-column label="Dimensions" width="180">
          <template #default="{ row }">
            <span class="fw-semibold">{{ row.length }} × {{ row.width }} × {{ row.height }}</span>
            <span class="text-muted ms-1">{{ row.uom }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Ply" width="80" align="center">
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.ply }}-Ply</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Type" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.print_type === 'printed' ? 'success' : 'info'" size="small" effect="dark">
              {{ row.print_type === 'printed' ? 'Printed' : 'Un-Print' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="quantity" label="Qty" width="80" align="center" />
        <el-table-column label="Made By" width="130" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.sample_made_by || '—' }}
          </template>
        </el-table-column>
        <el-table-column label="Joinery" width="120" align="center">
          <template #default="{ row }">
            <span class="badge bg-light text-dark border">{{ row.joinery_technique || 'N/A' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Add-ons" width="160" align="center">
          <template #default="{ row }">
            <template v-if="row.addons && row.addons.length">
              <el-tag
                v-for="addon in row.addons"
                :key="addon.id"
                size="small"
                :type="addon.type === 'honeycomb' ? 'warning' : 'primary'"
                class="me-1"
                effect="plain"
              >
                {{ addon.type === 'honeycomb' ? '🔶 HC' : '🔷 Sep' }}
              </el-tag>
            </template>
            <span v-else class="text-muted">—</span>
          </template>
        </el-table-column>
        <el-table-column label="Size Only" width="90" align="center">
          <template #default="{ row }">
            <i v-if="row.size_approval_only" class="bi bi-check-circle-fill text-success"></i>
            <i v-else class="bi bi-dash text-muted"></i>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div class="d-flex justify-content-center mt-3" v-if="reportTotal > 0">
        <el-pagination
          v-model:current-page="reportPage"
          :page-size="reportPerPage"
          :total="reportTotal"
          layout="prev, pager, next, total"
          @current-change="fetchReport"
        />
      </div>
    </section>

    <!-- ============ DETAIL DIALOG ============ -->
    <el-dialog v-model="detailVisible" title="Sample Submission Detail" width="720px" destroy-on-close>
      <div v-if="detailRow" class="ss-detail-grid">
        <div class="row g-3 mb-3">
          <div class="col-6"><label class="fw-bold text-muted small">Customer</label><p class="mb-0">{{ detailRow.customer?.name }}</p></div>
          <div class="col-3"><label class="fw-bold text-muted small">Date</label><p class="mb-0">{{ formatDate(detailRow.sample_date) }}</p></div>
          <div class="col-3"><label class="fw-bold text-muted small">Quantity</label><p class="mb-0">{{ detailRow.quantity }}</p></div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-4"><label class="fw-bold text-muted small">Dimensions</label><p class="mb-0 fw-semibold">{{ detailRow.length }} × {{ detailRow.width }} × {{ detailRow.height }} {{ detailRow.uom }}</p></div>
          <div class="col-4"><label class="fw-bold text-muted small">Ply / Type</label><p class="mb-0">{{ detailRow.ply }}-Ply &middot; {{ detailRow.print_type === 'printed' ? 'Printed' : 'Un-Print' }}</p></div>
          <div class="col-4"><label class="fw-bold text-muted small">Size Approval Only</label><p class="mb-0">{{ detailRow.size_approval_only ? 'Yes' : 'No' }}</p></div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-6"><label class="fw-bold text-muted small">Sample Made By</label><p class="mb-0">{{ detailRow.sample_made_by || '—' }}</p></div>
          <div class="col-6"><label class="fw-bold text-muted small">Joinery Technique</label><p class="mb-0">{{ detailRow.joinery_technique || 'N/A' }}</p></div>
        </div>
        <div v-if="detailRow.remarks" class="mb-3">
          <label class="fw-bold text-muted small">Remarks</label>
          <p class="mb-0">{{ detailRow.remarks }}</p>
        </div>

        <!-- Paper Layers -->
        <div v-if="detailRow.paper_layers && detailRow.paper_layers.length" class="mb-3">
          <label class="fw-bold text-muted small d-block mb-1"><i class="bi bi-layers me-1"></i> Paper Construction</label>
          <el-table :data="detailRow.paper_layers" size="small" border stripe>
            <el-table-column prop="layer_sequence" label="#" width="50" />
            <el-table-column prop="paper_type" label="Paper Type" />
            <el-table-column label="Paper Quality">
              <template #default="{ row }">
                {{ row.paper_quality?.quality || '—' }}
              </template>
            </el-table-column>
            <el-table-column prop="gsm" label="GSM" width="80" />
          </el-table>
        </div>

        <!-- Addons -->
        <div v-if="detailRow.addons && detailRow.addons.length" class="mb-3">
          <label class="fw-bold text-muted small d-block mb-1"><i class="bi bi-puzzle me-1"></i> Add-ons</label>
          <div v-for="addon in detailRow.addons" :key="addon.id" class="border rounded p-2 mb-2">
            <p class="mb-1 fw-bold">{{ addon.type === 'honeycomb' ? '🔶 Honeycomb' : '🔷 Separator' }}
              <el-tag size="small" class="ms-2">{{ addon.source }}</el-tag>
            </p>
            <p class="mb-1 text-muted small">
              Size: {{ addon.length || '—' }} × {{ addon.width || '—' }} × {{ addon.height || '—' }}
              &middot; {{ addon.ply }}-Ply
            </p>
            <el-table v-if="addon.paper_layers && addon.paper_layers.length" :data="addon.paper_layers" size="small" border stripe>
              <el-table-column prop="layer_sequence" label="#" width="50" />
              <el-table-column prop="paper_type" label="Paper Type" />
              <el-table-column label="Paper Quality">
                <template #default="{ row }">
                  {{ row.paper_quality?.quality || '—' }}
                </template>
              </el-table-column>
              <el-table-column prop="gsm" label="GSM" width="80" />
            </el-table>
          </div>
        </div>

        <!-- Created By -->
        <div class="text-muted small mt-3">
          Created by: {{ detailRow.creator?.name || '—' }}
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';

const props = defineProps({ user: Object });

// ──── Tab state ────
const activeTab = ref('form');

// ──── Customers & Paper Qualities ────
const customers = ref([]);
const paperQualities = ref([]);

const fetchCustomers = () => {
  axios.get('/api/customers').then(r => {
    customers.value = r.data.data || r.data;
  }).catch(() => {});
};

const fetchPaperQualities = () => {
  axios.get('/api/paper-qualities').then(r => {
    paperQualities.value = r.data.data || r.data;
  }).catch(() => {});
};

// ──── Ply → Layer mapping ────
const PLY_LAYERS = {
  '3': [
    { layer_sequence: 1, paper_type: 'Top Layer', paper_quality_id: null, gsm: null },
    { layer_sequence: 2, paper_type: 'Flute-B', paper_quality_id: null, gsm: null },
    { layer_sequence: 3, paper_type: 'Inner Layer', paper_quality_id: null, gsm: null },
  ],
  '5': [
    { layer_sequence: 1, paper_type: 'Top Layer', paper_quality_id: null, gsm: null },
    { layer_sequence: 2, paper_type: 'Flute-B', paper_quality_id: null, gsm: null },
    { layer_sequence: 3, paper_type: 'Middle Layer', paper_quality_id: null, gsm: null },
    { layer_sequence: 4, paper_type: 'Flute-C', paper_quality_id: null, gsm: null },
    { layer_sequence: 5, paper_type: 'Inner Layer', paper_quality_id: null, gsm: null },
  ],
  '7': [
    { layer_sequence: 1, paper_type: 'Top Layer', paper_quality_id: null, gsm: null },
    { layer_sequence: 2, paper_type: 'Flute-B', paper_quality_id: null, gsm: null },
    { layer_sequence: 3, paper_type: 'Middle Layer 1', paper_quality_id: null, gsm: null },
    { layer_sequence: 4, paper_type: 'Flute-C', paper_quality_id: null, gsm: null },
    { layer_sequence: 5, paper_type: 'Middle Layer 2', paper_quality_id: null, gsm: null },
    { layer_sequence: 6, paper_type: 'Flute-B', paper_quality_id: null, gsm: null },
    { layer_sequence: 7, paper_type: 'Inner Layer', paper_quality_id: null, gsm: null },
  ],
};

const generateLayers = (ply) => PLY_LAYERS[ply].map(l => ({ ...l }));

// ──── Form state ────
const saving = ref(false);

const getDefaultForm = () => ({
  customer_id: null,
  sample_date: new Date().toISOString().split('T')[0],
  length: null,
  width: null,
  height: null,
  uom: 'mm',
  quantity: 1,
  print_type: 'un-print',
  ply: '3',
  size_approval_only: false,
  remarks: '',
  sample_made_by: 'Zain Ul Abideen',
  joinery_technique: 'N/A',
  paper_layers: generateLayers('3'),
});

const form = reactive(getDefaultForm());

const onPlyChange = (ply) => {
  form.paper_layers = generateLayers(ply);
};

const onLayerQualityChange = (layer) => {
  const selected = paperQualities.value.find(q => q.id === layer.paper_quality_id);
  if (selected) {
    layer.gsm = selected.standard_gsm ? Math.round(Number(selected.standard_gsm)) : null;
  } else {
    layer.gsm = null;
  }
};

// ──── Add-on toggles & data ────
const includeHoneycomb = ref(false);
const includeSeparator = ref(false);

const createAddonData = () => ({
  length: null,
  width: null,
  height: null,
  ply: '3',
  source: 'in-house',
  paper_layers: generateLayers('3'),
});

const honeycombData = reactive(createAddonData());
const separatorData = reactive(createAddonData());

const onAddonToggle = (type, val) => {
  if (!val) {
    const defaults = createAddonData();
    const target = type === 'honeycomb' ? honeycombData : separatorData;
    Object.assign(target, defaults);
  }
};

const generateAddonLayers = (type) => {
  const target = type === 'honeycomb' ? honeycombData : separatorData;
  target.paper_layers = generateLayers(target.ply);
};

// ──── Submit ────
const submitForm = async () => {
  // Manual Validation
  if (!form.customer_id) {
    ElMessage.warning('Please select a customer.');
    return;
  }
  if (!form.sample_date) {
    ElMessage.warning('Please select a sample date.');
    return;
  }
  if (!form.length || form.length <= 0) {
    ElMessage.warning('Please enter a valid length.');
    return;
  }
  if (!form.width || form.width <= 0) {
    ElMessage.warning('Please enter a valid width.');
    return;
  }
  if (!form.height || form.height <= 0) {
    ElMessage.warning('Please enter a valid height.');
    return;
  }
  if (!form.quantity || form.quantity <= 0) {
    ElMessage.warning('Please enter a valid quantity.');
    return;
  }

  // Check paper layers if not size approval only
  if (!form.size_approval_only) {
    for (const layer of form.paper_layers) {
      if (!layer.paper_quality_id) {
        ElMessage.warning(`Please select paper quality for layer: ${layer.paper_type}.`);
        return;
      }
      if (!layer.gsm || layer.gsm <= 0) {
        ElMessage.warning(`Please enter valid GSM for paper layer: ${layer.paper_type}.`);
        return;
      }
    }
  }

  // Honeycomb validation
  if (includeHoneycomb.value) {
    if (!honeycombData.length || honeycombData.length <= 0) {
      ElMessage.warning('Please enter valid length for Honeycomb.');
      return;
    }
    if (!honeycombData.width || honeycombData.width <= 0) {
      ElMessage.warning('Please enter valid width for Honeycomb.');
      return;
    }
    if (!honeycombData.height || honeycombData.height <= 0) {
      ElMessage.warning('Please enter valid height for Honeycomb.');
      return;
    }
    if (!form.size_approval_only) {
      for (const layer of honeycombData.paper_layers) {
        if (!layer.paper_quality_id) {
          ElMessage.warning(`Please select paper quality for Honeycomb layer: ${layer.paper_type}.`);
          return;
        }
        if (!layer.gsm || layer.gsm <= 0) {
          ElMessage.warning(`Please enter valid GSM for Honeycomb layer: ${layer.paper_type}.`);
          return;
        }
      }
    }
  }

  // Separator validation
  if (includeSeparator.value) {
    if (!separatorData.length || separatorData.length <= 0) {
      ElMessage.warning('Please enter valid length for Separator.');
      return;
    }
    if (!separatorData.width || separatorData.width <= 0) {
      ElMessage.warning('Please enter valid width for Separator.');
      return;
    }
    if (!separatorData.height || separatorData.height <= 0) {
      ElMessage.warning('Please enter valid height for Separator.');
      return;
    }
    if (!form.size_approval_only) {
      for (const layer of separatorData.paper_layers) {
        if (!layer.paper_quality_id) {
          ElMessage.warning(`Please select paper quality for Separator layer: ${layer.paper_type}.`);
          return;
        }
        if (!layer.gsm || layer.gsm <= 0) {
          ElMessage.warning(`Please enter valid GSM for Separator layer: ${layer.paper_type}.`);
          return;
        }
      }
    }
  }

  saving.value = true;
  try {
    const payload = {
      customer_id: form.customer_id,
      sample_date: form.sample_date,
      length: form.length,
      width: form.width,
      height: form.height,
      uom: form.uom,
      quantity: form.quantity,
      print_type: form.print_type,
      ply: form.ply,
      size_approval_only: form.size_approval_only,
      remarks: form.remarks,
      sample_made_by: form.sample_made_by,
      joinery_technique: form.joinery_technique,
      paper_layers: form.size_approval_only ? [] : form.paper_layers,
      addons: [],
    };

    if (includeHoneycomb.value) {
      payload.addons.push({
        type: 'honeycomb',
        length: honeycombData.length,
        width: honeycombData.width,
        height: honeycombData.height,
        ply: honeycombData.ply,
        source: honeycombData.source,
        paper_layers: form.size_approval_only ? [] : honeycombData.paper_layers,
      });
    }

    if (includeSeparator.value) {
      payload.addons.push({
        type: 'separator',
        length: separatorData.length,
        width: separatorData.width,
        height: separatorData.height,
        ply: separatorData.ply,
        source: separatorData.source,
        paper_layers: form.size_approval_only ? [] : separatorData.paper_layers,
      });
    }

    await axios.post('/api/sample-submissions', payload);
    ElMessage.success('Sample submitted successfully!');
    resetForm();
    activeTab.value = 'report';
    fetchReport();
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to submit sample.';
    ElMessage.error(msg);
  } finally {
    saving.value = false;
  }
};

const resetForm = () => {
  Object.assign(form, getDefaultForm());
  includeHoneycomb.value = false;
  includeSeparator.value = false;
  Object.assign(honeycombData, createAddonData());
  Object.assign(separatorData, createAddonData());
};

// ──── Report ────
const reportData = ref([]);
const reportLoading = ref(false);
const reportPage = ref(1);
const reportPerPage = ref(50);
const reportTotal = ref(0);

const filters = reactive({
  customer_id: null,
  date_from: null,
  date_to: null,
});

const fetchReport = () => {
  reportLoading.value = true;
  const params = { page: reportPage.value, per_page: reportPerPage.value };
  if (filters.customer_id) params.customer_id = filters.customer_id;
  if (filters.date_from) params.date_from = filters.date_from;
  if (filters.date_to) params.date_to = filters.date_to;

  axios.get('/api/sample-submissions', { params })
    .then(r => {
      reportData.value = r.data.data || [];
      reportTotal.value = r.data.total || 0;
    })
    .catch(() => ElMessage.error('Failed to load report.'))
    .finally(() => { reportLoading.value = false; });
};

// ──── Detail dialog ────
const detailVisible = ref(false);
const detailRow = ref(null);

const showDetail = (row) => {
  detailRow.value = row;
  detailVisible.value = true;
};

// ──── Helpers ────
const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

// ──── Init ────
onMounted(() => {
  fetchCustomers();
  fetchPaperQualities();
  fetchReport();
});
</script>

<style scoped>
.sample-submission-management {
  padding: 0;
}

/* ── Header ── */
.ss-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}
.ss-header h2 {
  font-size: 1.6rem;
  font-weight: 700;
  margin-bottom: 4px;
  background: linear-gradient(135deg, #6366f1, #a78bfa);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.ss-header p {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}
.ss-eyebrow {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: #6366f1;
  margin-bottom: 4px;
}
.ss-header-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}
.is-active-tab {
  box-shadow: 0 0 12px rgba(99, 102, 241, 0.4) !important;
}

/* ── Card ── */
.ss-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 8px 32px rgba(31, 38, 135, 0.06);
  margin-bottom: 24px;
}

.ss-section-title {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 24px;
  padding-bottom: 12px;
  border-bottom: 2px solid rgba(99, 102, 241, 0.15);
}
.ss-section-title span {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1e293b;
}
.ss-section-title small {
  font-size: 0.8rem;
  color: #94a3b8;
}

/* ── Paper Layers ── */
.ss-paper-section {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.04), rgba(168, 85, 247, 0.04));
  border: 1px solid rgba(99, 102, 241, 0.12);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.ss-subsection-title {
  font-size: 1rem;
  font-weight: 700;
  color: #4f46e5;
  margin-bottom: 16px;
}

.ss-layer-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ss-layer-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #818cf8);
  color: #fff;
  font-weight: 700;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.ss-layer-badge-sm {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #818cf8;
  color: #fff;
  font-weight: 700;
  font-size: 0.75rem;
  flex-shrink: 0;
}

/* ── Addons ── */
.ss-addons-section {
  margin-bottom: 16px;
}

.ss-addon-toggle {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: rgba(241, 245, 249, 0.7);
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.ss-addon-card {
  background: rgba(241, 245, 249, 0.5);
  border: 1px dashed rgba(99, 102, 241, 0.25);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
}

.ss-addon-card-title {
  font-weight: 700;
  font-size: 0.95rem;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.ss-addon-layers {
  background: rgba(255, 255, 255, 0.6);
  border-radius: 8px;
  padding: 12px;
  border: 1px solid rgba(0, 0, 0, 0.04);
}

/* ── Switch style ── */
.custom-switch-style {
  width: 2.4em !important;
  height: 1.2em !important;
  cursor: pointer;
}

/* ── Detail dialog ── */
.ss-detail-grid label {
  display: block;
  margin-bottom: 2px;
}

/* ── Dark theme support ── */
[data-theme="dark"] .ss-card {
  background: rgba(30, 41, 59, 0.8) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-theme="dark"] .ss-section-title span,
[data-theme="dark"] .ss-header h2 {
  color: #e2e8f0;
  background: none;
  -webkit-text-fill-color: #c7d2fe;
}
[data-theme="dark"] .ss-paper-section {
  background: rgba(99, 102, 241, 0.08);
  border-color: rgba(99, 102, 241, 0.2);
}
[data-theme="dark"] .ss-addon-toggle {
  background: rgba(30, 41, 59, 0.5);
  border-color: #334155;
}
[data-theme="dark"] .ss-addon-card {
  background: rgba(30, 41, 59, 0.4);
  border-color: rgba(99, 102, 241, 0.2);
}
[data-theme="dark"] .ss-subsection-title {
  color: #a5b4fc;
}
</style>
