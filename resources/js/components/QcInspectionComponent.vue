<template>
<div class="container">
<!-- TABS -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link" :class="{active:tab==='lots'}" href="#" @click.prevent="tab='lots'">Open Lots</a></li>
  <li class="nav-link" :class="{active:tab==='history'}" style="cursor:pointer" @click="tab='history'">Inspection History</li>
</ul>

<!-- OPEN LOTS TAB -->
<div v-if="tab==='lots'">
  <h4><i class="bi bi-clipboard-check"></i> Open Lots Pending QC</h4>
  <div class="row mb-3 g-2">
    <div class="col-md-3">
      <input v-model="lotSearch" class="form-control form-control-sm" placeholder="Search Lot Number...">
    </div>
    <div class="col-md-2">
      <input v-model="lotPoFilter" class="form-control form-control-sm" placeholder="Filter PO #...">
    </div>
    <div class="col-md-2">
      <input v-model="lotGrnFilter" class="form-control form-control-sm" placeholder="Filter GRN #...">
    </div>
    <div class="col-md-3">
      <select v-model="lotStatusFilter" class="form-control form-control-sm">
        <option value="">All Status</option>
        <option value="pending">Pending Review</option>
        <option value="approved">QC Completed</option>
        <option value="rejected">QC Rejected</option>
      </select>
    </div>
  </div>
  <table class="table table-striped table-sm small">
    <thead><tr><th>Lot #</th><th>PO #</th><th>GRN #</th><th>Paper Quality</th><th>Supplier</th><th>Reels</th><th>Weight (kg)</th><th>Date</th><th>Aging (Days)</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <tr v-for="lot in filteredLots" :key="lot.lot_number" :class="openLotRowClass(lot.qc_status)">
        <td><strong>{{lot.lot_number}}</strong></td>
        <td>{{lot.po_number||'-'}}</td><td>{{lot.grn_number||'-'}}</td>
        <td>{{lot.paper_quality}}</td><td>{{lot.supplier_name}}</td>
        <td class="text-center">{{lot.reel_count}}</td>
        <td class="text-center">{{Math.round(lot.total_weight).toLocaleString()}}</td>
        <td>{{formatDate(lot.receiving_date)}}</td>
        <td class="text-center fw-semibold">{{ agingDays(lot) }}</td>
        <td>
          <span class="status-pill" :class="statusBadgeClass(lot.qc_status)">
            {{ statusLabel(lot.qc_status) }}
          </span>
        </td>
        <td>
          <button
            type="button"
            class="btn btn-sm btn-warning me-1"
            :title="lot.inspection_id ? 'Edit Inspection' : 'Inspect'"
            @click="startInspection(lot)"
          >
            <i class="bi bi-pencil-square"></i>
          </button>
          <button
            v-if="lot.inspection_id"
            type="button"
            class="btn btn-sm btn-info"
            title="Print Report"
            @click="printReport(lot.inspection_id)"
          >
            <i class="bi bi-printer"></i>
          </button>
        </td>
      </tr>
      <tr v-if="filteredLots.length===0"><td colspan="11" class="text-center text-muted">No lots found</td></tr>
    </tbody>
  </table>
</div>

<!-- INSPECTION FORM -->
<div v-if="tab==='form'">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
      <h4 class="mb-0"><i class="bi bi-clipboard2-pulse"></i> QC Inspection Entry</h4>
      <span v-if="formData.lot_number" class="badge bg-primary fs-5 px-3 py-2 shadow-sm animate__animated animate__pulse animate__infinite">
        Lot #: {{formData.lot_number}}
      </span>
    </div>
    <button @click="tab='lots'" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</button>
  </div>

  <!-- Header Info Area -->
  <div class="qc-entry-header mb-4 animate__animated animate__fadeInDown">
    <div class="row g-3">

      <!-- Paper Name Card -->
      <div class="col-md-3">
        <div class="info-card">
          <label>Paper Name</label>
          <div class="value">{{formData.paper_quality}}</div>
        </div>
      </div>
      <!-- PO/GRN Inputs -->
      <div class="col-md-2">
        <div class="info-card input-card">
          <label>PO #</label>
          <input v-model="formData.po_number" type="text" class="form-control form-control-sm" placeholder="Enter PO #">
        </div>
      </div>
      <div class="col-md-2">
        <div class="info-card input-card">
          <label>GRN #</label>
          <input v-model="formData.grn_number" type="text" class="form-control form-control-sm" placeholder="Enter GRN #">
        </div>
      </div>
      <!-- Supplier Card -->
      <div class="col">
        <div class="info-card">
          <label>Supplier</label>
          <div class="value text-truncate" :title="formData.supplier_name">{{formData.supplier_name}}</div>
        </div>
      </div>
      <!-- Paper Color Card -->
      <div class="col">
        <div class="info-card">
          <label>Paper Color</label>
          <div class="value">{{formData.paper_color||'N/A'}}</div>
        </div>
      </div>
      <!-- Received Date Card -->
      <div class="col">
        <div class="info-card">
          <label>Received Date</label>
          <div class="value">{{formatDate(formData.received_date)}}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="form-floating shadow-sm">
        <input v-model="formData.inspection_date" type="date" class="form-control" id="inspDate" required>
        <label for="inspDate">Inspection Date</label>
      </div>
    </div>
    <div class="col-md-3">
      <div class="form-floating shadow-sm">
        <input v-model="formData.inspector_name" type="text" class="form-control" id="inspName" placeholder="Inspector Name" required>
        <label for="inspName">Inspector Name</label>
      </div>
    </div>
    <div class="col-md-3">
      <div class="form-floating shadow-sm">
        <select v-model="formData.decision_type" class="form-control" id="decisionType">
          <option value="lot_accept">Lot Accept</option>
          <option value="lot_reject">Lot Reject</option>
          <option value="temporary_accept">Temporary Accept</option>
          <option value="partial_accept">Partial Accept</option>
        </select>
        <label for="decisionType">Decision</label>
      </div>
    </div>
    <div class="col-md-3">
      <div class="form-floating shadow-sm">
        <input v-model="formData.remarks" type="text" class="form-control" id="remarks" placeholder="Remarks">
        <label for="remarks">Remarks</label>
      </div>
    </div>
  </div>

  <!-- Criteria Reference -->
  <div v-if="criteria" class="criteria-bar shadow-sm mb-3">
    <span class="criteria-label"><i class="bi bi-info-circle-fill me-1"></i> Quality Criteria:</span>
    <span class="criteria-item">Min GSM: <strong>{{criteria.min_gsm||'N/A'}}</strong></span>
    <span class="criteria-divider">|</span>
    <span class="criteria-item">Min Bursting: <strong>{{criteria.min_bursting||'N/A'}}</strong></span>
    <span class="criteria-divider">|</span>
    <span class="criteria-item">Max Moisture: <strong>{{criteria.max_moisture||'N/A'}}</strong></span>
    <span class="criteria-divider">|</span>
    <span class="criteria-item">Max Cobb: <strong>{{criteria.max_cobb||'N/A'}}</strong></span>
  </div>

  <!-- Reel Test Results Grid -->
  <div class="card shadow-sm mb-4 border-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover table-sm small align-middle mb-0">
        <thead class="table-dark gradient-header">
          <tr>
            <th class="px-3">Reel No</th>
            <th>Size</th>
            <th>Weight</th>
            <th width="120">GSM</th>
            <th width="120">Bursting</th>
            <th width="120">Moisture</th>
            <th width="120">Ash</th>
            <th width="120">Cobb</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(d,i) in formData.details" :key="i">
            <td class="px-3"><span class="reel-no-badge">{{d.reel_no}}</span></td>
            <td><input v-model.number="d.reel_size" type="number" step="0.01" min="0" class="form-control form-control-sm text-center" style="width:80px"></td>
            <td><input v-model.number="d.reel_weight" type="number" step="0.01" min="0" class="form-control form-control-sm text-center" style="width:100px"></td>
            <td><input v-model.number="d.gsm" type="number" step="0.01" min="0" class="form-control form-control-sm text-center" :class="{'is-invalid':isFailed(d,'gsm')}" @input="validateRow(d)"></td>
            <td><input v-model.number="d.bursting" type="number" step="0.01" min="0" class="form-control form-control-sm text-center" :class="{'is-invalid':isFailed(d,'bursting')}" @input="validateRow(d)"></td>
            <td><input v-model.number="d.moisture" type="number" step="0.01" min="0" class="form-control form-control-sm text-center" :class="{'is-invalid':isFailed(d,'moisture')}" @input="validateRow(d)"></td>
            <td><input v-model.number="d.ash" type="number" step="0.01" min="0" class="form-control form-control-sm text-center" @input="validateRow(d)"></td>
            <td><input v-model.number="d.cobb" type="number" step="0.01" min="0" class="form-control form-control-sm text-center" :class="{'is-invalid':isFailed(d,'cobb')}" @input="validateRow(d)"></td>
            <td class="text-center">
              <span class="status-badge" :class="d.is_passed===false?'failed':'passed'">
                {{d.is_passed===false?'FAIL':'PASS'}}
              </span>
            </td>
          </tr>
        </tbody>
        <tfoot class="table-light fw-bold border-top-2">
          <tr>
            <td colspan="3" class="text-end px-3">Averages:</td>
            <td class="text-center">{{avg('gsm')}}</td>
            <td class="text-center">{{avg('bursting')}}</td>
            <td class="text-center">{{avg('moisture')}}</td>
            <td class="text-center">{{avg('ash')}}</td>
            <td class="text-center">{{avg('cobb')}}</td>
            <td class="text-center">
              <span class="overall-status-badge" :class="overallStatus.toLowerCase()">
                {{overallStatus}}
              </span>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end mb-5">
    <button @click="tab='lots'" class="btn btn-outline-secondary px-4">Cancel</button>
    <button @click="saveInspection" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-cloud-arrow-up me-1"></i> {{editingId?'Update':'Save'}} Inspection</button>
  </div>
</div>

<!-- HISTORY TAB -->
<div v-if="tab==='history'">
  <h4><i class="bi bi-clock-history"></i> Inspection History</h4>
  <div class="row mb-3 g-2">
    <div class="col-md-3"><input v-model="histSearch" class="form-control form-control-sm" placeholder="Search lot..."></div>
    <div class="col-md-2"><input v-model="histPoFilter" class="form-control form-control-sm" placeholder="Filter PO #..."></div>
    <div class="col-md-2"><input v-model="histGrnFilter" class="form-control form-control-sm" placeholder="Filter GRN #..."></div>
    <div class="col-md-2"><select v-model="histStatus" class="form-control form-control-sm" @change="fetchHistory"><option value="">All Status</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="pending">Pending</option></select></div>
  </div>
  <table class="table table-striped table-sm small">
    <thead><tr><th>Lot #</th><th>Paper</th><th>Supplier</th><th>Inspection Date</th><th>Inspector</th><th>Avg GSM</th><th>Avg Burst</th><th>Avg Moist</th><th>Avg Cobb</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <tr v-for="insp in historyList" :key="insp.id">
        <td class="fw-bold">{{insp.lot_number}}</td>
        <td>{{insp.paper_quality?.quality}} {{insp.paper_quality?.gsm_range}}</td>
        <td>{{insp.supplier?.name}}</td>
        <td>{{formatDate(insp.inspection_date)}}</td>
        <td>{{insp.inspector_name}}</td>
        <td>{{n(insp.avg_gsm)}}</td><td>{{n(insp.avg_bursting)}}</td><td>{{n(insp.avg_moisture)}}</td><td>{{n(insp.avg_cobb)}}</td>
        <td><span class="status-pill" :class="statusBadgeClass(insp.qc_status)">{{ statusLabel(insp.qc_status) }}</span></td>
        <td>
          <button @click="editInspection(insp)" class="btn btn-sm btn-warning me-1">Edit</button>
          <button @click="printReport(insp.id)" class="btn btn-sm btn-info me-1"><i class="bi bi-printer"></i></button>
          <button @click="downloadPdf(insp.id)" class="btn btn-sm btn-danger me-1"><i class="bi bi-file-earmark-pdf"></i></button>
          <button @click="deleteInspection(insp.id)" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
        </td>
      </tr>
      <tr v-if="historyList.length===0"><td colspan="11" class="text-center text-muted">No records found</td></tr>
    </tbody>
  </table>
</div>

<!-- REJECTION MODAL -->
<div v-if="showRejectionModal" class="modal d-block" style="background:rgba(0,0,0,0.5);z-index:9999">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger border-3">
      <div class="modal-body text-center p-4">
        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:3rem"></i>
        <h4 class="text-danger mt-3 fw-bold">Paper Rejected</h4>
        <p class="text-danger fw-bold">QC Results Do Not Match Quality Criteria.</p>
        <button @click="showRejectionModal=false" class="btn btn-danger">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- SUBMISSION SUCCESS MODAL -->
<div v-if="showSubmissionModal" class="modal d-block" style="background:rgba(0,0,0,0.5);z-index:9999">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
        <h4 class="mt-3 fw-bold">Inspection Submitted</h4>
        <p class="text-muted mb-3">Your QC inspection has been saved successfully.</p>
        <div class="d-flex gap-2 justify-content-center">
          <button @click="handleAfterSubmitPrint" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Print</button>
          <button @click="handleAfterSubmitPdf" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
          <button @click="closeSubmissionModal" class="btn btn-outline-secondary">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</template>

<script>
import axios from 'axios';
export default {
  props:['user'],
  data(){return{
    tab:'lots',lots:[],lotSearch:'',lotPoFilter:'',lotGrnFilter:'',formData:{lot_number:'',paper_quality:'',paper_quality_id:'',supplier_id:'',supplier_name:'',po_number:'',grn_number:'',received_date:'',paper_color:'',inspection_date:new Date().toISOString().substr(0,10),inspector_name:'',decision_type:'lot_accept',remarks:'',details:[]},
    criteria:null,editingId:null,showRejectionModal:false,showSubmissionModal:false,lastSavedInspectionId:null,
    historyData:[],histSearch:'',histPoFilter:'',histGrnFilter:'',histStatus:'',lotStatusFilter:'',openLotActionsFor:null,
    settings: {}
  }},
  computed:{
    filteredLots(){
      const s=this.lotSearch.toLowerCase();
      const po=this.lotPoFilter.toLowerCase();
      const grn=this.lotGrnFilter.toLowerCase();
      return this.lots.filter(l => {
        const byText = !s || String(l.lot_number || '').toLowerCase().includes(s);
        const byPo = !po || String(l.po_number || '').toLowerCase().includes(po);
        const byGrn = !grn || String(l.grn_number || '').toLowerCase().includes(grn);
        const byStatus = !this.lotStatusFilter || l.qc_status === this.lotStatusFilter;
        return byText && byPo && byGrn && byStatus;
      });
    },
    overallStatus(){const d=this.formData.details;if(!d.length)return'Pending';if(d.some(r=>r.is_passed===false))return'Rejected';if(d.some(r=>r.gsm||r.bursting||r.moisture||r.cobb))return'Approved';return'Pending'},
    historyList(){
      const s=this.histSearch.toLowerCase();
      const po=this.histPoFilter.toLowerCase();
      const grn=this.histGrnFilter.toLowerCase();
      return this.historyData.filter(h => {
        const byLot = !s || String(h.lot_number || '').toLowerCase().includes(s);
        const byPo = !po || String(h.po_number || '').toLowerCase().includes(po);
        const byGrn = !grn || String(h.grn_number || '').toLowerCase().includes(grn);
        return byLot && byPo && byGrn;
      });
    },
  },
  mounted(){
    if(localStorage.getItem('token'))axios.defaults.headers.common['Authorization']=`Bearer ${localStorage.getItem('token')}`;
    this.fetchLots();
    this.fetchHistory();
    this.fetchSettings();
    document.addEventListener('click', this.closeLotActions);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeLotActions);
  },
  methods:{
    fetchSettings() {
      axios.get('/api/setup/settings').then(r => {
        this.settings = r.data;
      }).catch(e => console.error('Error fetching settings:', e));
    },
    fetchLots(){axios.get('/api/qc-inspections/open-lots').then(r=>{this.lots=r.data}).catch(e=>console.error(e))},
    fetchHistory(){
      const params={};if(this.histStatus)params.qc_status=this.histStatus;
      axios.get('/api/qc-inspections',{params}).then(r=>{this.historyData=r.data.data||r.data}).catch(e=>console.error(e))
    },
    startInspection(lot){
      axios.get(`/api/qc-inspections/lot-details/${lot.lot_number}`).then(r=>{
        const d=r.data;
        this.criteria=d.criteria;this.editingId=null;
        if(d.existing_inspection){
          const ei=d.existing_inspection;
          this.editingId=ei.id;
          this.formData={lot_number:d.lot_number,paper_quality:d.paper_quality,paper_quality_id:d.paper_quality_id,supplier_id:d.supplier_id,supplier_name:d.supplier_name,po_number:d.po_number,grn_number:d.grn_number,received_date:d.receiving_date,paper_color:d.paper_color,inspection_date:ei.inspection_date,inspector_name:ei.inspector_name,decision_type:ei.decision_type || 'lot_accept',remarks:ei.remarks,
            details:d.reels.map(rl=>{const ed=ei.details?.find(dd=>dd.reel_id===rl.reel_id);return{reel_id:rl.reel_id,reel_no:rl.reel_no,reel_size:rl.reel_size,reel_weight:ed?.reel_weight ?? rl.original_weight,gsm:ed?.gsm||'',bursting:ed?.bursting||'',moisture:ed?.moisture||'',ash:ed?.ash||'',cobb:ed?.cobb||'',is_passed:ed?.is_passed!==false,failed_params:ed?.failed_params||[]}})};
        }else{
          this.formData={lot_number:d.lot_number,paper_quality:d.paper_quality,paper_quality_id:d.paper_quality_id,supplier_id:d.supplier_id,supplier_name:d.supplier_name,po_number:d.po_number,grn_number:d.grn_number,received_date:d.receiving_date,paper_color:d.paper_color,inspection_date:new Date().toISOString().substr(0,10),inspector_name:this.settings.qc_default_inspector || '',decision_type:'lot_accept',remarks:'',
            details:d.reels.map(rl=>({reel_id:rl.reel_id,reel_no:rl.reel_no,reel_size:rl.reel_size,reel_weight:rl.original_weight,gsm:'',bursting:'',moisture:'',ash:'',cobb:'',is_passed:true,failed_params:[]}))};
        }
        this.tab='form';
      })
    },
    validateRow(d){
      const c=this.criteria;if(!c){d.is_passed=true;d.failed_params=[];return}
      const f=[];
      if(c.min_gsm&&d.gsm&&d.gsm<c.min_gsm)f.push('gsm');
      if(c.min_bursting&&d.bursting&&d.bursting<c.min_bursting)f.push('bursting');
      if(c.max_moisture&&d.moisture&&d.moisture>c.max_moisture)f.push('moisture');
      if(c.max_cobb&&d.cobb&&d.cobb>c.max_cobb)f.push('cobb');
      d.failed_params=f;d.is_passed=f.length===0;
    },
    isFailed(d,param){return d.failed_params&&d.failed_params.includes(param)},
    avg(field){const vals=this.formData.details.map(d=>parseFloat(d[field])).filter(v=>!isNaN(v)&&v>0);if(!vals.length)return'-';return(vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(2)},
    saveInspection(){
      if(!this.formData.inspection_date||!this.formData.inspector_name){alert('Please fill inspection date and inspector name');return}
      const payload={lot_number:this.formData.lot_number,paper_quality_id:this.formData.paper_quality_id,supplier_id:this.formData.supplier_id,po_number:this.formData.po_number,grn_number:this.formData.grn_number,received_date:this.formData.received_date,inspection_date:this.formData.inspection_date,inspector_name:this.formData.inspector_name,decision_type:this.formData.decision_type || 'lot_accept',remarks:this.formData.remarks,
        details:this.formData.details.map(d=>({reel_id:d.reel_id,reel_size:d.reel_size||null,reel_weight:d.reel_weight||null,gsm:d.gsm||null,bursting:d.bursting||null,moisture:d.moisture||null,ash:d.ash||null,cobb:d.cobb||null}))};
      const req=this.editingId?axios.put(`/api/qc-inspections/${this.editingId}`,payload):axios.post('/api/qc-inspections',payload);
      req.then(r=>{
        this.lastSavedInspectionId = r.data?.id || this.editingId || null;
        if(r.data.qc_status==='rejected')this.showRejectionModal=true;
        this.showSubmissionModal = true;
        this.fetchLots();
        this.fetchHistory();
      }).catch(e=>{alert('Error: '+(e.response?.data?.error||e.message))});
    },
    editInspection(insp){
      axios.get(`/api/qc-inspections/lot-details/${insp.lot_number}`).then(r=>{
        const d=r.data;this.criteria=d.criteria;this.editingId=insp.id;
        this.formData={lot_number:insp.lot_number,paper_quality:insp.paper_quality?.quality+' '+insp.paper_quality?.gsm_range,paper_quality_id:insp.paper_quality_id,supplier_id:insp.supplier_id,supplier_name:insp.supplier?.name,po_number:insp.po_number,grn_number:insp.grn_number,received_date:insp.received_date,paper_color:d.paper_color,inspection_date:insp.inspection_date,inspector_name:insp.inspector_name,decision_type:insp.decision_type || 'lot_accept',remarks:insp.remarks,
          details:d.reels.map(rl=>{const ed=insp.details?.find(dd=>dd.reel_id===rl.reel_id);return{reel_id:rl.reel_id,reel_no:rl.reel_no,reel_size:rl.reel_size,reel_weight:ed?.reel_weight ?? rl.original_weight,gsm:ed?.gsm||'',bursting:ed?.bursting||'',moisture:ed?.moisture||'',ash:ed?.ash||'',cobb:ed?.cobb||'',is_passed:ed?.is_passed!==false,failed_params:ed?.failed_params||[]}})};
        this.tab='form';
      })
    },
    deleteInspection(id){if(confirm('Delete this inspection?'))axios.delete(`/api/qc-inspections/${id}`).then(()=>this.fetchHistory())},
    printReport(id){
      axios.get(`/api/qc-inspections/${id}/report`).then(r=>{
        const d=r.data;
        const details = d.details || [];
        const companyName = this.settings?.company_name || 'QUALITY CARTONS (PVT.) LTD.';
        const approvedByName = this.settings?.qc_default_approved_by || '-';
        const companyAddress = this.settings?.company_address || '';

        const pageSize = 13;
        const pages = [];
        for (let i = 0; i < details.length; i += pageSize) {
          pages.push(details.slice(i, i + pageSize));
        }
        if (pages.length === 0) pages.push([]);

        const allCols = [
          { key: 'gsm', label: 'GSM' },
          { key: 'bursting', label: 'Bursting (PSI)' },
          { key: 'moisture', label: 'Moisture' },
          { key: 'ash', label: 'Ash' },
          { key: 'cobb', label: 'Cobb' }
        ];
        const hasMeaningfulValue = (value, optional = false) => {
          if (value === null || value === undefined || value === '' || value === '-') return false;
          if (optional && Number(value) === 0) return false;
          return true;
        };
        const cols = [
          ...allCols.slice(0, 2),
          ...allCols.slice(2).filter(col =>
            details.some(det => hasMeaningfulValue(det[col.key], true))
          )
        ];

        const avgForPage = (rows, key) => {
          const nums = rows
            .map(r => Number(r[key]))
            .filter(v => Number.isFinite(v));
          if (!nums.length) return '-';
          return (nums.reduce((a, b) => a + b, 0) / nums.length).toFixed(2);
        };

        const sampleHeightForRows = (rowCount) => {
          const n = Math.min(Math.max(rowCount, 1), 13);
          return Math.max(180, Math.round(560 - ((n - 1) * 30)));
        };

        const valueOrDash = (value) => (value !== null && value !== undefined && value !== '' ? value : '-');
        const criteria = d.criteria || null;
        const criteriaMap = {
          gsm: { label: 'GSM', min: 'min_gsm', max: 'max_gsm' },
          bursting: { label: 'Bursting (PSI)', min: 'min_bursting', max: 'max_bursting' },
          moisture: { label: 'Moisture', min: 'min_moisture', max: 'max_moisture' },
          cobb: { label: 'Cobb', min: 'min_cobb', max: 'max_cobb' }
        };
        const criteriaItems = criteria
          ? cols
              .filter(col => criteriaMap[col.key])
              .map(col => {
                const item = criteriaMap[col.key];
                return `<div class="criteria-item"><span class="criteria-label">${item.label}</span><span class="criteria-value">${valueOrDash(criteria[item.min])} - ${valueOrDash(criteria[item.max])}</span></div>`;
              })
              .join('')
          : '';
        const criteriaHtml = criteriaItems ? `
  <div class="criteria-section">
    <div class="criteria-title">Quality Acceptance Criteria</div>
    <div class="criteria-grid">
      ${criteriaItems}
    </div>
  </div>` : '';

        // Status
        const statusClass = d.qc_status === 'approved' ? 'status-approved' : d.qc_status === 'rejected' ? 'status-rejected' : 'status-pending';
        const statusText = d.qc_status === 'approved'
          ? 'APPROVED (LOT ACCEPTED)'
          : d.qc_status === 'rejected'
            ? 'REJECTED'
            : 'PENDING';
        const statusMarkup = d.qc_status === 'approved'
          ? '<span style="font-weight:700;text-decoration:underline;">APPROVED (LOT ACCEPTED)</span>'
          : `<span style="font-weight:700;">${statusText}</span>`;
        const decisionType = d.decision_type || 'lot_accept';
        const decisionCell = (value, label) => {
          const selected = decisionType === value;
          return `<td class="${selected ? 'selected' : ''}">${label}${selected ? ' <span class="decision-check">&#10003;</span>' : ''}</td>`;
        };

        const formatDisplayDate = (value) => {
          if (!value) return '-';
          const date = new Date(value);
          if (Number.isNaN(date.getTime())) return '-';
          const day = String(date.getDate()).padStart(2, '0');
          const month = String(date.getMonth() + 1).padStart(2, '0');
          const year = date.getFullYear();
          return `${day}/${month}/${year}`;
        };

        const renderPage = (rows, pageIndex, pageCount) => {
          const sampleHeight = sampleHeightForRows(rows.length);
          let tbody = '';
          rows.forEach((det, idx) => {
            tbody += `<tr><td style="text-align:center;color:#000;">${(pageIndex * pageSize) + idx + 1}</td><td class="reel-no">${det.reel_no || ''}</td>`;
            tbody += `<td style="text-align:center;color:#000;">${det.reel_size ? det.reel_size + '"' : '-'}</td>`;
            tbody += `<td class="weight-cell" style="text-align:center;color:#000;">${det.reel_weight ? Number(det.reel_weight).toLocaleString() : '-'}</td>`;
            cols.forEach(col => {
              const val = det[col.key];
              tbody += `<td style="text-align:center;color:#000;">${val !== null && val !== undefined && val !== '' ? val : '-'}</td>`;
            });
            tbody += `<td class="result-cell" style="text-align:center;color:#000;">${det.is_passed === false ? 'FAIL' : 'PASS'}</td></tr>`;
          });
          let avgRow = `<td colspan="4" style="text-align:right;font-weight:700;padding-right:12px">Average</td>`;
          cols.forEach(col => avgRow += `<td style="text-align:center;font-weight:700">${avgForPage(rows, col.key)}</td>`);
          avgRow += '<td></td>';

          return `
<div class="report-page${pageIndex < pageCount - 1 ? ' page-break' : ''}">
  <table class="header-table">
    <tr>
      <td class="header-logo" rowspan="2">
        <img src="/images/quality-cartons-logo.png" alt="Quality Cartons Logo">
      </td>
      <td class="header-title" colspan="8">INCOMING MATERIAL (REEL) INSPECTION REPORT</td>
    </tr>
    <tr>
      <td class="meta-label">Document I.D. #</td>
      <td class="meta-value">QC/DI3A/045</td>
      <td class="meta-label">Rev. #</td>
      <td class="meta-value">01</td>
      <td class="meta-label">Rev. Date</td>
      <td class="meta-value">25/10/2016</td>
      <td class="meta-label">Page #</td>
      <td class="meta-value">${pageIndex + 1} of ${pageCount}</td>
    </tr>
  </table>

  <div class="info-section">
    <div class="info-item lot-item"><span class="info-label">Lot No:</span><span class="info-value nowrap">${d.lot_number}</span></div>
    <div class="info-item paper-item"><span class="info-label">Paper:</span><span class="info-value" style="white-space:nowrap;">${d.paper_quality?.quality || ''} ${d.paper_quality?.gsm_range ? '(' + d.paper_quality.gsm_range + ')' : ''}</span></div>
    <div class="info-item"><span class="info-label">Color:</span><span class="info-value">${d.paper_color || 'N/A'}</span></div>
    <div class="info-item"><span class="info-label">Supplier:</span><span class="info-value">${d.supplier?.name || ''}</span></div>
    <div class="info-item"><span class="info-label">PO No:</span><span class="info-value">${d.po_number || 'N/A'}</span></div>
    <div class="info-item"><span class="info-label">GRN No:</span><span class="info-value">${d.grn_number || 'N/A'}</span></div>
    <div class="info-item"><span class="info-label">Received:</span><span class="info-value">${formatDisplayDate(d.received_date)}</span></div>
    <div class="info-item"><span class="info-label">Inspected:</span><span class="info-value">${formatDisplayDate(d.inspection_date)}</span></div>
  </div>

  ${criteriaHtml}

  <table class="results-table">
    <thead><tr><th style="width:56px;white-space:nowrap;text-transform:none;">S. No.</th><th>Reel No</th><th>Size</th><th>Weight (kg)</th>${cols.map(col => `<th>${col.label}</th>`).join('')}<th style="width:70px">Result</th></tr></thead>
    <tbody>${tbody}<tr class="avg-row">${avgRow}</tr></tbody>
  </table>

  <table class="decision-table">
    <tr>
      ${decisionCell('lot_accept', 'Lot Accept')}
      ${decisionCell('lot_reject', 'Lot Reject')}
      ${decisionCell('temporary_accept', 'Temporary Accept')}
      ${decisionCell('partial_accept', 'Partial Accept')}
    </tr>
  </table>

  <div class="all-remarks">
    <div>Remarks: ${statusMarkup}</div>
    ${d.remarks ? `<div class="remarks-text">${d.remarks}</div>` : ''}
  </div>

  <div class="footer-section">
    <div class="signature-only">
      <div class="signature-row">
        <div class="signature-block">
          <div class="signature-name">${d.inspector_name || '-'}</div>
          <div class="signature-line"></div>
          <div class="signature-label">Checked By</div>
        </div>
        <div class="signature-block">
          <div class="signature-name">${approvedByName}</div>
          <div class="signature-line"></div>
          <div class="signature-label">Approved By</div>
        </div>
      </div>
    </div>
    <div class="sample-box" style="height:${sampleHeight}px;min-height:${sampleHeight}px;">
      <div class="watermark">SAMPLE ATTACH HERE</div>
    </div>
  </div>
</div>`;
        };

        const pagesHtml = pages.map((rows, i) => renderPage(rows, i, pages.length)).join('');

        const w = window.open('', '_blank', 'width=900,height=700');
        w.document.write(`<html><head><title>QC Report - ${d.lot_number}</title>
<style>
  @page { size: A4 portrait; margin: 8mm 10mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #000; font-size: 11px; line-height: 1.4; padding: 0; }

  .report-page { width: 100%; max-width: 210mm; margin: 0 auto; padding: 8px; min-height: calc(297mm - 16mm); display: flex; flex-direction: column; }
  .page-break { page-break-after: always; }

  /* Header */
  .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; break-inside: avoid; page-break-inside: avoid; }
  .header-table td { border: 1px solid #000; }
  .header-logo { width: 150px; text-align: center; vertical-align: middle; padding: 6px; }
  .header-logo img { max-width: 130px; max-height: 70px; object-fit: contain; }
  .header-title { text-align: center; font-size: 24px; font-weight: 700; color: #000; padding: 10px 6px; white-space: nowrap; border: 1px solid #000; }
  .meta-label { width: 115px; text-align: center; font-size: 11px; padding: 6px 4px; white-space: nowrap; }
  .meta-value { width: 100px; text-align: center; font-size: 12px; font-weight: 700; padding: 6px 4px; white-space: nowrap; }

  /* Info Grid */
  .info-section { display: grid; grid-template-columns: 1.25fr 1.85fr 1fr; gap: 0; border: 1px solid #000; margin-bottom: 8px; break-inside: avoid; page-break-inside: avoid; }
  .info-item { padding: 5px 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; display: flex; gap: 5px; }
  .info-item.paper-item { grid-column: span 2; }
  .info-item:nth-child(3n) { border-right: none; }
  .info-item:nth-last-child(-n+3) { border-bottom: none; }
  .info-label { font-weight: 700; color: #000; font-size: 10px; text-transform: uppercase; min-width: 70px; white-space: nowrap; }
  .info-value { font-weight: 600; color: #000; }
  .nowrap { white-space: nowrap; }

  /* Table */
  .results-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 11px; break-inside: avoid; page-break-inside: avoid; }
  .results-table th { background: #e5e7eb; color: #000; font-weight: 700; padding: 5px 4px; text-align: center; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.2px; border: 1px solid #000; }
  .results-table td { padding: 4px; border: 1px solid #000; font-size: 11px; color: #000; }
  .results-table tbody tr:nth-child(even) { background: #fff; }
  .results-table tbody tr:hover { background: #fff; }
  .reel-no { font-weight: 700; color: #000; }
  .fail-row { background: #fff !important; }
  .fail-val { color: #000; font-weight: 700; }
  .weight-cell { font-weight: 700; text-align: center; color: #000; }
  .result-cell { font-weight: 700; color: #000; }
  .avg-row { background: #fff !important; border-top: 2px solid #000; }

  /* Criteria */
  .criteria-section { margin-bottom: 8px; border: 1px solid #000; break-inside: avoid; page-break-inside: avoid; }
  .criteria-title { background: #e5e7eb; color: #000; font-weight: 700; padding: 4px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #000; text-align: center; }
  .criteria-grid { display: grid; grid-template-columns: repeat(${Math.min(Math.max(cols.filter(col => criteriaMap[col.key]).length, 1), 4)}, 1fr); }
  .criteria-item { padding: 4px 8px; border-right: 1px solid #000; text-align: center; }
  .criteria-item:last-child { border-right: none; }
  .criteria-label { display: block; font-size: 8.5px; font-weight: 700; color: #000; text-transform: uppercase; }
  .criteria-value { display: block; font-size: 10px; font-weight: 700; color: #000; margin-top: 1px; }

  /* Status */
  .overall-status { text-align: center; margin: 15px 0; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 800; letter-spacing: 0.5px; }
  .status-approved { background: #dcfce7; color: #166534; border: 2px solid #86efac; }
  .status-rejected { background: #fee2e2; color: #991b1b; border: 2px solid #fca5a5; }
  .status-pending { background: #fef3c7; color: #92400e; border: 2px solid #fcd34d; }

  /* Remarks */
  .remarks-section { padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 15px; }
  .remarks-label { font-weight: 700; color: #475569; font-size: 10px; text-transform: uppercase; }
  .remarks-text { margin-top: 3px; color: #1e293b; }

  .decision-table { width: 100%; border-collapse: collapse; margin: 6px 0 8px; break-inside: avoid; page-break-inside: avoid; }
  .decision-table td { border: 1px solid #000; text-align: center; font-size: 12px; font-weight: 500; padding: 5px 4px; color: #000; background: #fff; }
  .decision-table td.selected { font-weight: 700; }
  .decision-check { font-size: 2em; line-height: 0; vertical-align: -0.12em; }
  .all-remarks { border: 1px solid #111827; min-height: 30px; padding: 6px 8px; font-size: 10.5px; color: #000; margin-bottom: 8px; break-inside: avoid; page-break-inside: avoid; }
  .all-remarks .title { font-weight: 700; margin-bottom: 6px; font-size: 11px; }
  .all-remarks .remarks-text { margin-top: 3px; color: #000; }

  /* Signatures + Sample Area */
  .footer-section { margin-top: 0; break-inside: avoid; page-break-inside: avoid; flex: 1; display: flex; flex-direction: column; min-height: 0; }
  .signature-only { margin-top: 8px; border: 1px solid #1f2937; padding: 6px 8px; break-inside: avoid; page-break-inside: avoid; }
  .signature-row { display: grid; grid-template-columns: 1fr 1fr; gap: 36px; }
  .signature-block { display: flex; flex-direction: column; }
  .signature-name { min-height: 18px; font-size: 11px; font-weight: 700; color: #000; padding-left: 2px; }
  .signature-line { border-bottom: 1px solid #111827; height: 14px; }
  .signature-label { margin-top: 2px; font-size: 10px; font-weight: 700; color: #000; text-align: left; }
  .sample-box { height: auto !important; flex: 1; min-height: 180px; position: relative; background: #fff; border: 1px solid #1f2937; margin-top: 8px; break-inside: avoid; page-break-inside: avoid; overflow: hidden; }
  .sample-box .watermark { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #d1d5db; font-weight: 800; font-size: clamp(22px, 6vw, 56px); letter-spacing: 0.5px; transform: none; text-align: center; width: 90%; margin: 0 auto; line-height: 0.95; white-space: normal; overflow-wrap: anywhere; }

  /* Print */
  @media print {
    body { padding: 0; }
    .report-page { padding: 0; max-width: 100%; }
  }
</style></head><body>
${pagesHtml}
</body></html>`);
        w.document.close();
        setTimeout(() => w.print(), 350);
      })
    },
    downloadPdf(id) {
      axios.get(`/api/qc-inspections/${id}/report-pdf`, { responseType: 'blob' }).then((response) => {
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `qc_inspection_${id}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      });
    },
    handleAfterSubmitPrint() {
      if (this.lastSavedInspectionId) this.printReport(this.lastSavedInspectionId);
    },
    handleAfterSubmitPdf() {
      if (this.lastSavedInspectionId) this.downloadPdf(this.lastSavedInspectionId);
    },
    closeSubmissionModal() {
      this.showSubmissionModal = false;
      this.tab = 'history';
    },
    formatDate(d){if(!d)return'-';const dt=new Date(d);return isNaN(dt)?'-':`${String(dt.getDate()).padStart(2,'0')}/${String(dt.getMonth()+1).padStart(2,'0')}/${dt.getFullYear()}`},
    n(v){return v?parseFloat(v).toFixed(2):'-'},
    agingDays(lot) {
      if (!lot?.receiving_date || lot?.qc_status !== 'pending') return '-';
      const recv = new Date(lot.receiving_date);
      if (Number.isNaN(recv.getTime())) return '-';
      const today = new Date();
      const start = new Date(recv.getFullYear(), recv.getMonth(), recv.getDate());
      const end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
      const days = Math.floor((end - start) / 86400000);
      return days >= 0 ? days : 0;
    },
    toggleLotActions(lotNumber) {
      this.openLotActionsFor = this.openLotActionsFor === lotNumber ? null : lotNumber;
    },
    closeLotActions() {
      this.openLotActionsFor = null;
    },
    statusLabel(status) {
      if (status === 'approved') return 'QC Completed';
      if (status === 'rejected') return 'QC Rejected';
      return 'Pending Review';
    },
    statusBadgeClass(status) {
      if (status === 'approved') return 'status-completed';
      if (status === 'rejected') return 'status-rejected';
      return 'status-pending';
    },
    openLotRowClass(status) {
      if (status === 'approved') return 'open-lot-row open-lot-approved';
      if (status === 'rejected') return 'open-lot-row open-lot-rejected';
      return 'open-lot-row open-lot-pending';
    },
  }
};
</script>

<style scoped>
.qc-entry-header .info-card {
  background: white;
  border-radius: 12px;
  padding: 12px 15px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  height: 100%;
  border-left: 4px solid #e2e8f0;
}
.qc-entry-header .info-card.lot-card {
  border-left-color: #3b82f6;
}
.qc-entry-header .info-card.input-card {
  background: #f8fafc;
  border-left-color: #64748b;
}
.qc-entry-header .info-card label {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  font-weight: 700;
  margin-bottom: 4px;
}
.qc-entry-header .info-card .value {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
}
.qc-entry-header .lot-card .value {
  color: #3b82f6;
}

.criteria-bar {
  background: #ecfeff;
  border: 1px solid #cffafe;
  border-radius: 8px;
  padding: 10px 15px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}
.criteria-label {
  color: #0891b2;
  font-weight: 700;
  font-size: 0.9rem;
}
.criteria-item {
  color: #155e75;
  font-size: 0.85rem;
}
.criteria-divider {
  color: #bae6fd;
}

.gradient-header {
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
}

.reel-no-badge {
  background: #f1f5f9;
  color: #475569;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: 600;
  border: 1px solid #e2e8f0;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.status-badge.passed {
  background: #dcfce7;
  color: #166534;
}
.status-badge.failed {
  background: #fee2e2;
  color: #991b1b;
}

.overall-status-badge {
  padding: 6px 16px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 700;
}
.overall-status-badge.approved { background: #22c55e; color: white; }
.overall-status-badge.rejected { background: #ef4444; color: white; }
.overall-status-badge.pending { background: #f59e0b; color: white; }

.form-floating > label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
}

.border-top-2 {
  border-top: 2px solid #e2e8f0 !important;
}

/* Subtle scan-friendly status rows for Open Lots table */
:deep(.open-lot-row.open-lot-pending) {
  background-color: rgba(251, 191, 36, 0.08) !important;
}
:deep(.open-lot-row.open-lot-approved) {
  background-color: rgba(34, 197, 94, 0.08) !important;
}
:deep(.open-lot-row.open-lot-rejected) {
  background-color: rgba(239, 68, 68, 0.08) !important;
}
:deep(.open-lot-row:hover) {
  filter: brightness(0.99);
}

.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 148px;
  padding: 0.42rem 0.8rem;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1;
  border: 1px solid transparent;
}

.status-pending {
  color: #8a5a00;
  background: #fff4cf;
  border-color: #f1d185;
}

.status-completed {
  color: #166534;
  background: #dcfce7;
  border-color: #86efac;
}

.status-rejected {
  color: #991b1b;
  background: #fee2e2;
  border-color: #fca5a5;
}

.qc-action-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px !important;
  border: none !important;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff !important;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.2);
}

.qc-action-btn i {
  font-size: 1rem;
}

.qc-action-primary { background: #2563eb !important; }
.qc-action-info { background: #0891b2 !important; }
.qc-action-danger { background: #dc2626 !important; }

.qc-action-primary:hover { background: #1d4ed8 !important; }
.qc-action-info:hover { background: #0e7490 !important; }
.qc-action-danger:hover { background: #b91c1c !important; }

.lot-actions-menu {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  left: auto;
  width: 188px;
  border: 1px solid #dbe1ea;
  border-radius: 8px;
  background: #fff;
  z-index: 1000;
  overflow: hidden;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.16);
}

.lot-actions-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  border: 0;
  background: transparent;
  text-align: left;
  font-size: 0.86rem;
  color: #1f2937;
  padding: 8px 10px;
  line-height: 1.25;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  position: relative;
  z-index: 1;
}

.lot-actions-item:hover {
  background: #f3f6fb;
}

/* Prevent table/action cell clipping of the popup menu */
td.position-relative {
  overflow: visible !important;
}

.table,
.table-responsive,
.container {
  overflow: visible;
}
</style>
