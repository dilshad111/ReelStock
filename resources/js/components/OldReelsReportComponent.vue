<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1 text-primary fw-bold"><i class="bi bi-calendar-range me-2"></i> Old Reels Report</h2>
        <p class="text-muted small mb-0">Reels available in inventory for over a year, categorized by age.</p>
      </div>
      <div class="d-flex gap-2">
        <button @click="printReport" class="btn btn-primary d-flex align-items-center gap-2">
          <i class="bi bi-printer"></i> Print Report
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Generating aging analysis...</p>
    </div>

    <div v-else>
      <div v-for="(section, index) in reportData" :key="index" class="card shadow-sm border-0 mb-5 overflow-hidden">
        <div class="card-header bg-light py-3 border-bottom">
          <h5 class="mb-0 fw-bold text-dark">{{ index + 1 }}. {{ section.title }}</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
              <thead class="bg-primary text-white">
                <tr>
                  <th class="ps-3" style="width: 60px;">S. No.</th>
                  <th>Receiving Date</th>
                  <th>Reel No.</th>
                  <th>Supplier Name</th>
                  <th>Reel Quality (with GSM)</th>
                  <th class="text-end">Balance Weight (Kgs)</th>
                  <th v-if="canSeeAmounts" class="text-end">Amount (PKR)</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(reel, rIndex) in section.reels" :key="reel.id">
                  <td class="ps-3 text-center text-muted small">{{ rIndex + 1 }}</td>
                  <td>{{ formatDate(reel.receiving_date) }}</td>
                  <td class="fw-bold text-primary">{{ reel.reel_no }}</td>
                  <td>{{ reel.supplier_name }}</td>
                  <td class="small">{{ reel.quality }}</td>
                  <td class="text-end fw-bold">{{ formatNumber(reel.balance_weight) }}</td>
                  <td v-if="canSeeAmounts" class="text-end text-success fw-bold">{{ formatCurrency(reel.amount) }}</td>
                  <td class="text-center">
                    <span class="badge" :class="reel.status === 'Complete' ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning'">
                      {{ reel.status }}
                    </span>
                  </td>
                </tr>
                <tr v-if="section.reels.length === 0">
                  <td colspan="8" class="text-center py-4 text-muted italic">No reels found in this age category.</td>
                </tr>
              </tbody>
              <tfoot class="bg-light-blue fw-bold">
                <tr>
                  <td colspan="5" class="ps-3 text-end text-uppercase small tracking-wider">Section Totals</td>
                  <td class="text-end border-top border-dark">
                    <div class="small text-muted mb-1">Total Weight</div>
                    {{ formatNumber(section.total_weight) }} Kgs
                  </td>
                  <td v-if="canSeeAmounts" class="text-end border-top border-dark">
                    <div class="small text-muted mb-1">Total Amount</div>
                    {{ formatCurrency(section.total_amount) }}
                  </td>
                  <td class="text-center border-top border-dark">
                    <div class="small text-muted mb-1">Reels</div>
                    {{ section.subtotal_reels }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    user: Object,
    canSeeAmounts: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      reportData: [],
      loading: true,
      companySettings: {
        name: 'QUALITY CARTONS (PVT.) LTD.',
        address: 'Plot# 46, Sector 24, Korangi Industrial Area Karachi',
        logo: '/images/quality-cartons-logo.svg'
      }
    };
  },
  mounted() {
    this.fetchData();
    this.fetchSettings();
  },
  methods: {
    fetchData() {
      this.loading = true;
      axios.get('/api/reports/old-reels')
        .then(response => {
          this.reportData = response.data;
          this.loading = false;
        })
        .catch(error => {
          console.error("Error fetching old reels report:", error);
          this.loading = false;
          alert("Failed to load report data.");
        });
    },
    fetchSettings() {
      axios.get('/api/setup/settings').then(response => {
        const s = response.data;
        if (s.company_name) this.companySettings.name = s.company_name;
        if (s.company_address) this.companySettings.address = s.company_address;
        if (s.company_logo) this.companySettings.logo = '/storage/' + s.company_logo;
      });
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const d = new Date(dateString);
      return d.toLocaleDateString('en-GB');
    },
    formatNumber(val) {
      return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatCurrency(val) {
      return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    printReport() {
      const printWindow = window.open('', '_blank');
      
      let sectionsHtml = '';
      this.reportData.forEach((section, idx) => {
        let rowsHtml = section.reels.map((reel, rIdx) => `
          <tr>
            <td style="text-align: center;">${rIdx + 1}</td>
            <td>${this.formatDate(reel.receiving_date)}</td>
            <td style="font-weight: bold;">${reel.reel_no}</td>
            <td>${reel.supplier_name}</td>
            <td>${reel.quality}</td>
            <td style="text-align: right;">${this.formatNumber(reel.balance_weight)}</td>
            ${this.canSeeAmounts ? `<td style="text-align: right;">${this.formatCurrency(reel.amount)}</td>` : ''}
            <td style="text-align: center;">${reel.status}</td>
          </tr>
        `).join('');

        if (section.reels.length === 0) {
          rowsHtml = `<tr><td colspan="${this.canSeeAmounts ? 8 : 7}" style="text-align: center; padding: 20px;">No reels found.</td></tr>`;
        }

        sectionsHtml += `
          <div class="print-section">
            <h3 class="section-title">${idx + 1}. ${section.title}</h3>
            <table>
              <thead>
                <tr>
                  <th>S. No.</th>
                  <th>Receiving Date</th>
                  <th>Reel No.</th>
                  <th>Supplier Name</th>
                  <th>Quality (GSM)</th>
                  <th>Weight (Kgs)</th>
                  ${this.canSeeAmounts ? '<th>Amount (PKR)</th>' : ''}
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                ${rowsHtml}
              </tbody>
              <tfoot>
                <tr class="footer-row">
                  <td colspan="5" style="text-align: right;">SECTION TOTALS:</td>
                  <td style="text-align: right;">${this.formatNumber(section.total_weight)}</td>
                  ${this.canSeeAmounts ? `<td style="text-align: right;">${this.formatCurrency(section.total_amount)}</td>` : ''}
                  <td style="text-align: center;">${section.subtotal_reels} Reels</td>
                </tr>
              </tfoot>
            </table>
          </div>
        `;
      });

      printWindow.document.write(`
        <html>
          <head>
            <title>Old Reels Report - ${new Date().toLocaleDateString('en-GB')}</title>
            <style>
              body { font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; color: #333; }
              .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
              .logo { height: 80px; margin-bottom: 10px; }
              .company-name { font-size: 24px; font-weight: bold; margin: 0; color: #007bff; }
              .company-address { font-size: 14px; margin: 5px 0; }
              .report-title { font-size: 20px; font-weight: bold; margin-top: 15px; text-transform: uppercase; }
              .section-title { background: #f8f9fa; padding: 10px; border-left: 5px solid #007bff; margin-top: 30px; font-size: 16px; }
              table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
              th { background: #007bff; color: white; padding: 8px; border: 1px solid #ddd; text-align: left; }
              td { padding: 8px; border: 1px solid #ddd; }
              tr:nth-child(even) { background: #f2f2f2; }
              .footer-row { font-weight: bold; background: #e9ecef !important; }
              @media print {
                body { padding: 0; }
                .print-section { page-break-inside: avoid; }
                .header { border-bottom-color: #000; }
              }
            </style>
          </head>
          <body>
            <div class="header">
              <img src="${this.companySettings.logo}" class="logo">
              <div class="company-name">${this.companySettings.name}</div>
              <div class="company-address">${this.companySettings.address}</div>
              <div class="report-title">Old Reels Aging Report</div>
              <div class="report-info">Printed on: ${new Date().toLocaleString('en-GB')}</div>
            </div>
            ${sectionsHtml}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
      }, 500);
    }
  }
};
</script>

<style scoped>
.bg-primary {
  background-color: #0d6efd !important;
}
.bg-success-soft {
  background-color: rgba(25, 135, 84, 0.1);
}
.bg-warning-soft {
  background-color: rgba(255, 193, 7, 0.1);
}
.bg-light-blue {
  background-color: #f0f7ff;
}
.tracking-wider {
  letter-spacing: 0.05em;
}
.italic {
  font-style: italic;
}
.badge {
  padding: 0.5em 0.8em;
  font-weight: 600;
  border-radius: 4px;
}
.table > :not(caption) > * > * {
  padding: 0.75rem 0.5rem;
}
.card {
  border-radius: 12px;
}
.card-header {
  background-color: #f8f9fa !important;
}
</style>
