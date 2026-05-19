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
  <div class="row mb-3"><div class="col-md-4">
    <input v-model="lotSearch" class="form-control form-control-sm" placeholder="Search Lot Number...">
  </div></div>
  <table class="table table-striped table-sm small">
    <thead><tr><th>Lot #</th><th>PO #</th><th>GRN #</th><th>Paper Quality</th><th>Supplier</th><th>Reels</th><th>Weight (kg)</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
      <tr v-for="lot in filteredLots" :key="lot.lot_number">
        <td><strong>{{lot.lot_number}}</strong></td>
        <td>{{lot.po_number||'-'}}</td><td>{{lot.grn_number||'-'}}</td>
        <td>{{lot.paper_quality}}</td><td>{{lot.supplier_name}}</td>
        <td class="text-center">{{lot.reel_count}}</td>
        <td class="text-center">{{Math.round(lot.total_weight).toLocaleString()}}</td>
        <td>{{formatDate(lot.receiving_date)}}</td>
        <td><button @click="startInspection(lot)" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Inspect</button></td>
      </tr>
      <tr v-if="filteredLots.length===0"><td colspan="9" class="text-center text-muted">No open lots found</td></tr>
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
    <div class="col-md-6">
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
        <td><span class="badge" :class="insp.qc_status==='rejected'?'bg-danger':insp.qc_status==='approved'?'bg-success':'bg-warning'">{{insp.qc_status}}</span></td>
        <td>
          <button @click="editInspection(insp)" class="btn btn-sm btn-warning me-1">Edit</button>
          <button @click="printReport(insp.id)" class="btn btn-sm btn-info me-1"><i class="bi bi-printer"></i></button>
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
</div>
</template>

<script>
import axios from 'axios';
export default {
  props:['user'],
  data(){return{
    tab:'lots',lots:[],lotSearch:'',formData:{lot_number:'',paper_quality:'',paper_quality_id:'',supplier_id:'',supplier_name:'',po_number:'',grn_number:'',received_date:'',paper_color:'',inspection_date:new Date().toISOString().substr(0,10),inspector_name:'',remarks:'',details:[]},
    criteria:null,editingId:null,showRejectionModal:false,
    historyData:[],histSearch:'',histStatus:'',
    settings: {}
  }},
  computed:{
    filteredLots(){const s=this.lotSearch.toLowerCase();return s?this.lots.filter(l=>l.lot_number.toLowerCase().includes(s)):this.lots},
    overallStatus(){const d=this.formData.details;if(!d.length)return'Pending';if(d.some(r=>r.is_passed===false))return'Rejected';if(d.some(r=>r.gsm||r.bursting||r.moisture||r.cobb))return'Approved';return'Pending'},
    historyList(){const s=this.histSearch.toLowerCase();return s?this.historyData.filter(h=>h.lot_number.toLowerCase().includes(s)):this.historyData},
  },
  mounted(){
    if(localStorage.getItem('token'))axios.defaults.headers.common['Authorization']=`Bearer ${localStorage.getItem('token')}`;
    this.fetchLots();
    this.fetchHistory();
    this.fetchSettings();
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
          this.formData={lot_number:d.lot_number,paper_quality:d.paper_quality,paper_quality_id:d.paper_quality_id,supplier_id:d.supplier_id,supplier_name:d.supplier_name,po_number:d.po_number,grn_number:d.grn_number,received_date:d.receiving_date,paper_color:d.paper_color,inspection_date:ei.inspection_date,inspector_name:ei.inspector_name,remarks:ei.remarks,
            details:d.reels.map(rl=>{const ed=ei.details?.find(dd=>dd.reel_id===rl.reel_id);return{reel_id:rl.reel_id,reel_no:rl.reel_no,reel_size:rl.reel_size,original_weight:rl.original_weight,gsm:ed?.gsm||'',bursting:ed?.bursting||'',moisture:ed?.moisture||'',ash:ed?.ash||'',cobb:ed?.cobb||'',is_passed:ed?.is_passed!==false,failed_params:ed?.failed_params||[]}})};
        }else{
          this.formData={lot_number:d.lot_number,paper_quality:d.paper_quality,paper_quality_id:d.paper_quality_id,supplier_id:d.supplier_id,supplier_name:d.supplier_name,po_number:d.po_number,grn_number:d.grn_number,received_date:d.receiving_date,paper_color:d.paper_color,inspection_date:new Date().toISOString().substr(0,10),inspector_name:this.settings.qc_default_inspector || '',remarks:'',
            details:d.reels.map(rl=>({reel_id:rl.reel_id,reel_no:rl.reel_no,reel_size:rl.reel_size,original_weight:rl.original_weight,gsm:'',bursting:'',moisture:'',ash:'',cobb:'',is_passed:true,failed_params:[]}))};
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
      const payload={lot_number:this.formData.lot_number,paper_quality_id:this.formData.paper_quality_id,supplier_id:this.formData.supplier_id,po_number:this.formData.po_number,grn_number:this.formData.grn_number,received_date:this.formData.received_date,inspection_date:this.formData.inspection_date,inspector_name:this.formData.inspector_name,remarks:this.formData.remarks,
        details:this.formData.details.map(d=>({reel_id:d.reel_id,reel_size:d.reel_size||null,reel_weight:d.reel_weight||null,gsm:d.gsm||null,bursting:d.bursting||null,moisture:d.moisture||null,ash:d.ash||null,cobb:d.cobb||null}))};
      const req=this.editingId?axios.put(`/api/qc-inspections/${this.editingId}`,payload):axios.post('/api/qc-inspections',payload);
      req.then(r=>{
        if(r.data.qc_status==='rejected')this.showRejectionModal=true;
        else alert('QC Inspection saved successfully!');
        this.fetchLots();this.fetchHistory();if(!this.showRejectionModal)this.tab='history';
      }).catch(e=>{alert('Error: '+(e.response?.data?.error||e.message))});
    },
    editInspection(insp){
      axios.get(`/api/qc-inspections/lot-details/${insp.lot_number}`).then(r=>{
        const d=r.data;this.criteria=d.criteria;this.editingId=insp.id;
        this.formData={lot_number:insp.lot_number,paper_quality:insp.paper_quality?.quality+' '+insp.paper_quality?.gsm_range,paper_quality_id:insp.paper_quality_id,supplier_id:insp.supplier_id,supplier_name:insp.supplier?.name,po_number:insp.po_number,grn_number:insp.grn_number,received_date:insp.received_date,paper_color:d.paper_color,inspection_date:insp.inspection_date,inspector_name:insp.inspector_name,remarks:insp.remarks,
          details:d.reels.map(rl=>{const ed=insp.details?.find(dd=>dd.reel_id===rl.reel_id);return{reel_id:rl.reel_id,reel_no:rl.reel_no,reel_size:rl.reel_size,original_weight:rl.original_weight,gsm:ed?.gsm||'',bursting:ed?.bursting||'',moisture:ed?.moisture||'',ash:ed?.ash||'',cobb:ed?.cobb||'',is_passed:ed?.is_passed!==false,failed_params:ed?.failed_params||[]}})};
        this.tab='form';
      })
    },
    deleteInspection(id){if(confirm('Delete this inspection?'))axios.delete(`/api/qc-inspections/${id}`).then(()=>this.fetchHistory())},
    printReport(id){
      axios.get(`/api/qc-inspections/${id}/report`).then(r=>{
        const d=r.data;
        const details = d.details || [];
        const companyName = this.settings?.company_name || 'QUALITY CARTONS (PVT.) LTD.';
        const companyAddress = this.settings?.company_address || '';

        // Determine which test columns have at least one entered value
        const testCols = [
          { key: 'gsm', label: 'GSM', avg: d.avg_gsm, criteriaMin: d.criteria?.min_gsm, criteriaMax: d.criteria?.max_gsm },
          { key: 'bursting', label: 'Bursting (KSC)', avg: d.avg_bursting, criteriaMin: d.criteria?.min_bursting, criteriaMax: d.criteria?.max_bursting },
          { key: 'moisture', label: 'Moisture (%)', avg: d.avg_moisture, criteriaMin: d.criteria?.min_moisture, criteriaMax: d.criteria?.max_moisture },
          { key: 'ash', label: 'Ash (%)', avg: null, criteriaMin: null, criteriaMax: null },
          { key: 'cobb', label: 'Cobb (GSM)', avg: d.avg_cobb, criteriaMin: d.criteria?.min_cobb, criteriaMax: d.criteria?.max_cobb },
        ];
        const activeCols = testCols.filter(col => details.some(det => det[col.key] !== null && det[col.key] !== undefined && det[col.key] !== ''));

        // Check if reel_size or reel_weight have data
        const hasSize = details.some(det => det.reel_size);
        const hasWeight = details.some(det => det.reel_weight);

        // Build table header
        let thead = '<th style="width:40px">#</th><th>Reel No</th>';
        if (hasSize) thead += '<th>Size</th>';
        if (hasWeight) thead += '<th>Weight (kg)</th>';
        activeCols.forEach(col => thead += `<th>${col.label}</th>`);
        thead += '<th style="width:70px">Result</th>';

        // Build table body rows
        let tbody = '';
        details.forEach((det, idx) => {
          let cls = det.is_passed === false ? 'fail-row' : '';
          tbody += `<tr class="${cls}"><td style="text-align:center">${idx + 1}</td><td class="reel-no">${det.reel_no || ''}</td>`;
          if (hasSize) tbody += `<td style="text-align:center">${det.reel_size ? det.reel_size + '"' : '-'}</td>`;
          if (hasWeight) tbody += `<td style="text-align:right">${det.reel_weight ? Number(det.reel_weight).toLocaleString() : '-'}</td>`;
          activeCols.forEach(col => {
            const val = det[col.key];
            const isFail = det.failed_params?.includes(col.key);
            tbody += `<td style="text-align:center" class="${isFail ? 'fail-val' : ''}">${val !== null && val !== undefined && val !== '' ? val : '-'}</td>`;
          });
          tbody += `<td style="text-align:center">${det.is_passed === false ? '<span class="fail-badge">FAIL</span>' : '<span class="pass-badge">PASS</span>'}</td></tr>`;
        });

        // Average row
        let avgRow = `<td colspan="${1 + 1 + (hasSize ? 1 : 0) + (hasWeight ? 1 : 0)}" style="text-align:right;font-weight:700;padding-right:12px">Average</td>`;
        activeCols.forEach(col => {
          avgRow += `<td style="text-align:center;font-weight:700">${col.avg || '-'}</td>`;
        });
        avgRow += `<td></td>`;

        // Build criteria section (only if criteria exists)
        let criteriaHtml = '';
        if (d.criteria) {
          const criteriaItems = activeCols.filter(col => col.criteriaMin || col.criteriaMax).map(col => {
            let parts = [];
            if (col.criteriaMin) parts.push(`Min: ${col.criteriaMin}`);
            if (col.criteriaMax) parts.push(`Max: ${col.criteriaMax}`);
            return `<div class="criteria-item"><span class="criteria-label">${col.label}</span><span class="criteria-value">${parts.join(' | ')}</span></div>`;
          });
          if (criteriaItems.length > 0) {
            criteriaHtml = `<div class="criteria-section"><div class="criteria-title">Quality Acceptance Criteria</div><div class="criteria-grid">${criteriaItems.join('')}</div></div>`;
          }
        }

        // Status
        const statusClass = d.qc_status === 'approved' ? 'status-approved' : 'status-rejected';
        const statusText = d.qc_status?.toUpperCase() || 'PENDING';

        const w = window.open('', '_blank', 'width=900,height=700');
        w.document.write(`<html><head><title>QC Report - ${d.lot_number}</title>
<style>
  @page { size: A4 portrait; margin: 12mm 15mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; font-size: 11px; line-height: 1.4; padding: 0; }

  .report-page { width: 100%; max-width: 210mm; margin: 0 auto; padding: 20px; }

  /* Header */
  .report-header { text-align: center; border-bottom: 3px solid #1e40af; padding-bottom: 12px; margin-bottom: 15px; }
  .company-name { font-size: 22px; font-weight: 800; color: #1e293b; letter-spacing: 1px; margin-bottom: 2px; }
  .company-address { font-size: 10px; color: #64748b; margin-bottom: 8px; }
  .report-title { font-size: 16px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 2px; margin-top: 6px; background: #eff6ff; padding: 6px 0; border-radius: 4px; }

  /* Info Grid */
  .info-section { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; margin-bottom: 15px; }
  .info-item { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; display: flex; gap: 6px; }
  .info-item:nth-child(3n) { border-right: none; }
  .info-item:nth-last-child(-n+3) { border-bottom: none; }
  .info-label { font-weight: 700; color: #475569; font-size: 10px; text-transform: uppercase; min-width: 70px; white-space: nowrap; }
  .info-value { font-weight: 600; color: #1e293b; }

  /* Table */
  .results-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 11px; }
  .results-table th { background: #1e293b; color: #fff; font-weight: 700; padding: 8px 6px; text-align: center; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
  .results-table th:first-child { border-radius: 6px 0 0 0; }
  .results-table th:last-child { border-radius: 0 6px 0 0; }
  .results-table td { padding: 6px; border: 1px solid #e2e8f0; }
  .results-table tbody tr:nth-child(even) { background: #f8fafc; }
  .results-table tbody tr:hover { background: #eff6ff; }
  .reel-no { font-weight: 700; color: #1e40af; }
  .fail-row { background: #fef2f2 !important; }
  .fail-val { color: #dc2626; font-weight: 700; }
  .pass-badge { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-weight: 700; font-size: 9px; }
  .fail-badge { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-weight: 700; font-size: 9px; }
  .avg-row { background: #f1f5f9 !important; border-top: 2px solid #94a3b8; }

  /* Criteria */
  .criteria-section { margin-bottom: 12px; border: 1px solid #bfdbfe; border-radius: 6px; overflow: hidden; }
  .criteria-title { background: #eff6ff; color: #1e40af; font-weight: 700; padding: 6px 12px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #bfdbfe; }
  .criteria-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); }
  .criteria-item { padding: 6px 12px; border-right: 1px solid #e2e8f0; text-align: center; }
  .criteria-item:last-child { border-right: none; }
  .criteria-label { display: block; font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; }
  .criteria-value { display: block; font-size: 11px; font-weight: 600; color: #1e293b; margin-top: 2px; }

  /* Status */
  .overall-status { text-align: center; margin: 15px 0; padding: 12px; border-radius: 8px; font-size: 18px; font-weight: 800; letter-spacing: 2px; }
  .status-approved { background: #dcfce7; color: #166534; border: 2px solid #86efac; }
  .status-rejected { background: #fee2e2; color: #991b1b; border: 2px solid #fca5a5; }

  /* Remarks */
  .remarks-section { padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 15px; }
  .remarks-label { font-weight: 700; color: #475569; font-size: 10px; text-transform: uppercase; }
  .remarks-text { margin-top: 3px; color: #1e293b; }

  /* Signatures */
  .signature-section { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 30px; margin-top: 40px; padding-top: 10px; }
  .signature-block { text-align: center; }
  .signature-line { border-top: 1px solid #94a3b8; margin-top: 40px; padding-top: 6px; font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; }
  .signature-name { font-size: 10px; color: #94a3b8; margin-top: 2px; }

  /* Print */
  @media print {
    body { padding: 0; }
    .report-page { padding: 0; max-width: 100%; }
  }
</style></head><body>
<div class="report-page">
  <div class="report-header">
    <div class="company-name">${companyName}</div>
    ${companyAddress ? `<div class="company-address">${companyAddress}</div>` : ''}
    <div class="report-title">QC Inspection Report</div>
  </div>

  <div class="info-section">
    <div class="info-item"><span class="info-label">Lot No:</span><span class="info-value">${d.lot_number}</span></div>
    <div class="info-item"><span class="info-label">Paper:</span><span class="info-value">${d.paper_quality?.quality || ''} ${d.paper_quality?.gsm_range ? '(' + d.paper_quality.gsm_range + ')' : ''}</span></div>
    <div class="info-item"><span class="info-label">Color:</span><span class="info-value">${d.paper_color || 'N/A'}</span></div>
    <div class="info-item"><span class="info-label">Supplier:</span><span class="info-value">${d.supplier?.name || ''}</span></div>
    <div class="info-item"><span class="info-label">PO No:</span><span class="info-value">${d.po_number || 'N/A'}</span></div>
    <div class="info-item"><span class="info-label">GRN No:</span><span class="info-value">${d.grn_number || 'N/A'}</span></div>
    <div class="info-item"><span class="info-label">Received:</span><span class="info-value">${d.received_date || '-'}</span></div>
    <div class="info-item"><span class="info-label">Inspected:</span><span class="info-value">${d.inspection_date || '-'}</span></div>
    <div class="info-item"><span class="info-label">Inspector:</span><span class="info-value">${d.inspector_name || '-'}</span></div>
  </div>

  ${criteriaHtml}

  <table class="results-table">
    <thead><tr>${thead}</tr></thead>
    <tbody>${tbody}
      <tr class="avg-row">${avgRow}</tr>
    </tbody>
  </table>

  <div class="overall-status ${statusClass}">OVERALL RESULT: ${statusText}</div>

  ${d.remarks ? `<div class="remarks-section"><div class="remarks-label">Remarks</div><div class="remarks-text">${d.remarks}</div></div>` : ''}

  <div class="signature-section">
    <div class="signature-block"><div class="signature-line">QC Inspector</div><div class="signature-name">${d.inspector_name || ''}</div></div>
    <div class="signature-block"><div class="signature-line">QC Manager</div><div class="signature-name"></div></div>
    <div class="signature-block"><div class="signature-line">Plant Head</div><div class="signature-name"></div></div>
  </div>
</div>
</body></html>`);
        w.document.close();
        setTimeout(() => w.print(), 300);
      })
    },
    formatDate(d){if(!d)return'-';const dt=new Date(d);return isNaN(dt)?'-':`${String(dt.getDate()).padStart(2,'0')}/${String(dt.getMonth()+1).padStart(2,'0')}/${dt.getFullYear()}`},
    n(v){return v?parseFloat(v).toFixed(2):'-'},
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
</style>
