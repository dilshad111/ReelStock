<template>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2><i class="bi bi-pencil-square"></i> Carton Sketch Generator</h2>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h5>Sketch Parameters</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="generateSketch">
              <div class="mb-3">
                <label for="customer" class="form-label">Customer</label>
                <select class="form-select" id="customer" v-model="form.customer_id">
                  <option value="">Select Customer (Optional)</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.customer_name }}
                  </option>
                </select>
              </div>

              <div class="mb-3">
                <label for="fefco" class="form-label">FEFCO Code</label>
                <select class="form-select" id="fefco" v-model="form.fefco_code" required>
                  <option value="">Select FEFCO Code</option>
                  <option value="0201">0201 – Regular Slotted Container (RSC)</option>
                  <option value="0200">0200 – Slotted Box</option>
                  <option value="0300">0300 – Telescope Box</option>
                  <option value="0401">0401 – Folder Type</option>
                  <option value="0501">0501 – Slide Type</option>
                  <option value="0713">0713 – Die-Cut Box</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="length" class="form-label">Length (mm)</label>
                <input type="number" class="form-control" id="length" v-model="form.length" min="1" required>
              </div>

              <div class="mb-3">
                <label for="width" class="form-label">Width (mm)</label>
                <input type="number" class="form-control" id="width" v-model="form.width" min="1" required>
              </div>

              <div class="mb-3">
                <label for="height" class="form-label">Height (mm)</label>
                <input type="number" class="form-control" id="height" v-model="form.height" min="1" required>
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-brush"></i> Generate Sketch
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Carton Sketch</h5>
            <button @click="exportToPDF" class="btn btn-danger btn-sm" :disabled="!sketchSvg">
              <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </button>
          </div>
          <div class="card-body text-center">
            <div v-if="!sketchSvg" class="text-muted">
              <i class="bi bi-image" style="font-size: 3rem;"></i>
              <p>Select parameters and generate sketch</p>
            </div>
            <div v-else>
              <div v-html="sketchSvg" class="sketch-container"></div>
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
  name: 'SketchGeneratorComponent',
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      customers: [],
      form: {
        customer_id: '',
        fefco_code: '',
        length: '',
        width: '',
        height: ''
      },
      sketchSvg: ''
    };
  },
  mounted() {
    this.fetchCustomers();
  },
  methods: {
    fetchCustomers() {
      axios.get('/api/customers').then(response => {
        this.customers = response.data;
      }).catch(error => {
        console.error('Error fetching customers:', error);
      });
    },
    generateSketch() {
      // Generate SVG based on FEFCO code and dimensions
      this.sketchSvg = this.createSketchSvg(this.form.fefco_code, this.form.length, this.form.width, this.form.height);
    },
    createSketchSvg(fefco, length, width, height) {
      if (fefco !== '0201') {
        // Default simple box for other FEFCO codes
        const scale = 0.5;
        const l = length * scale;
        const w = width * scale;
        let svg = `<svg width="${l + 100}" height="${w + 100}" xmlns="http://www.w3.org/2000/svg">`;
        svg += `<rect x="50" y="50" width="${l}" height="${w}" fill="none" stroke="black" stroke-width="2"/>`;
        svg += `<text x="${50 + l/2}" y="${50 + w/2 + 5}" text-anchor="middle" font-size="12">FEFCO ${fefco}</text>`;
        svg += `<text x="${50 + l/2}" y="${50 + w/2 + 20}" text-anchor="middle" font-size="10">L=${length}, W=${width}, H=${height}</text>`;
        svg += '</svg>';
        return svg;
      }

      const glueFlap = 35;
      const L = parseFloat(length);
      const W = parseFloat(width);
      const H = parseFloat(height);
      const flapH = W / 2;
      const totalWidth = glueFlap + L + W + L + W;
      const totalHeight = H + 2 * flapH;
      const scale = Math.min(600 / totalWidth, 400 / totalHeight);
      const scaledGlue = glueFlap * scale;
      const scaledL = L * scale;
      const scaledW = W * scale;
      const scaledH = H * scale;
      const scaledFlapH = flapH * scale;
      const scaledTotalW = totalWidth * scale;
      const scaledTotalH = totalHeight * scale;

      let svg = `<svg width="${scaledTotalW + 200}" height="${scaledTotalH + 200}" xmlns="http://www.w3.org/2000/svg">`;

      svg += `<defs>
        <marker id="arrow" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto" markerUnits="strokeWidth">
          <path d="M0,0 L0,6 L9,3 z" fill="black" />
        </marker>
      </defs>`;

      const yTop = 50;
      const yTopBody = yTop + scaledFlapH;
      const yBottomBody = yTopBody + scaledH;
      const yBottom = yBottomBody + scaledFlapH;

      const x0 = 50;
      const x1 = x0 + scaledGlue;
      const x2 = x1 + scaledL;
      const x3 = x2 + scaledW;
      const x4 = x3 + scaledL;
      const x5 = x4 + scaledW;

      // Vertical lines (cuts and creases)
      svg += `<line x1="${x0}" y1="${yTop}" x2="${x0}" y2="${yBottom}" stroke="black" stroke-width="2"/>`; // cut
      svg += `<line x1="${x1}" y1="${yTop}" x2="${x1}" y2="${yBottom}" stroke="black" stroke-width="2" stroke-dasharray="5,5"/>`; // crease
      svg += `<line x1="${x2}" y1="${yTop}" x2="${x2}" y2="${yBottom}" stroke="black" stroke-width="2" stroke-dasharray="5,5"/>`; // crease
      svg += `<line x1="${x3}" y1="${yTop}" x2="${x3}" y2="${yBottom}" stroke="black" stroke-width="2" stroke-dasharray="5,5"/>`; // crease
      svg += `<line x1="${x4}" y1="${yTop}" x2="${x4}" y2="${yBottom}" stroke="black" stroke-width="2" stroke-dasharray="5,5"/>`; // crease
      svg += `<line x1="${x5}" y1="${yTop}" x2="${x5}" y2="${yBottom}" stroke="black" stroke-width="2"/>`; // cut

      // Horizontal cuts
      svg += `<line x1="${x0}" y1="${yTop}" x2="${x5}" y2="${yTop}" stroke="black" stroke-width="2"/>`;
      svg += `<line x1="${x0}" y1="${yTopBody}" x2="${x5}" y2="${yTopBody}" stroke="black" stroke-width="2"/>`;
      svg += `<line x1="${x0}" y1="${yBottomBody}" x2="${x5}" y2="${yBottomBody}" stroke="black" stroke-width="2"/>`;
      svg += `<line x1="${x0}" y1="${yBottom}" x2="${x5}" y2="${yBottom}" stroke="black" stroke-width="2"/>`;

      // Glue area dotted line
      svg += `<line x1="${x1}" y1="${yTopBody}" x2="${x1}" y2="${yBottomBody}" stroke="black" stroke-width="1" stroke-dasharray="2,2"/>`;


      // Panel labels
      svg += `<text x="${(x0 + x1)/2}" y="${yTopBody + scaledH/2}" text-anchor="middle" font-size="12">Glue Flap</text>`;
      svg += `<text x="${(x1 + x2)/2}" y="${yTopBody + scaledH/2}" text-anchor="middle" font-size="12">Panel 1 (L)</text>`;
      svg += `<text x="${(x2 + x3)/2}" y="${yTopBody + scaledH/2}" text-anchor="middle" font-size="12">Panel 2 (W)</text>`;
      svg += `<text x="${(x3 + x4)/2}" y="${yTopBody + scaledH/2}" text-anchor="middle" font-size="12">Panel 3 (L)</text>`;
      svg += `<text x="${(x4 + x5)/2}" y="${yTopBody + scaledH/2}" text-anchor="middle" font-size="12">Panel 4 (W)</text>`;

      // Dimension arrows and labels
      // Total width
      svg += `<line x1="${x0}" y1="${yBottom + 20}" x2="${x5}" y2="${yBottom + 20}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${(x0 + x5)/2}" y="${yBottom + 15}" text-anchor="middle" font-size="10">Total Width = ${totalWidth} mm</text>`;

      // L
      svg += `<line x1="${x1}" y1="${yBottom + 40}" x2="${x2}" y2="${yBottom + 40}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${(x1 + x2)/2}" y="${yBottom + 35}" text-anchor="middle" font-size="10">L = ${L} mm</text>`;

      // W
      svg += `<line x1="${x2}" y1="${yBottom + 60}" x2="${x3}" y2="${yBottom + 60}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${(x2 + x3)/2}" y="${yBottom + 55}" text-anchor="middle" font-size="10">W = ${W} mm</text>`;

      // L
      svg += `<line x1="${x3}" y1="${yBottom + 80}" x2="${x4}" y2="${yBottom + 80}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${(x3 + x4)/2}" y="${yBottom + 75}" text-anchor="middle" font-size="10">L = ${L} mm</text>`;

      // W
      svg += `<line x1="${x4}" y1="${yBottom + 100}" x2="${x5}" y2="${yBottom + 100}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${(x4 + x5)/2}" y="${yBottom + 95}" text-anchor="middle" font-size="10">W = ${W} mm</text>`;

      // Glue flap
      svg += `<line x1="${x0}" y1="${yBottom + 120}" x2="${x1}" y2="${yBottom + 120}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${(x0 + x1)/2}" y="${yBottom + 115}" text-anchor="middle" font-size="10">Glue Flap = 35 mm</text>`;

      // Overall height
      svg += `<line x1="${x5 + 20}" y1="${yTop}" x2="${x5 + 20}" y2="${yBottom}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${x5 + 35}" y="${(yTop + yBottom)/2}" text-anchor="middle" font-size="10" transform="rotate(90 ${x5 + 35} ${(yTop + yBottom)/2})">Overall Height = ${totalHeight} mm</text>`;

      // H
      svg += `<line x1="${x5 + 50}" y1="${yTopBody}" x2="${x5 + 50}" y2="${yBottomBody}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${x5 + 65}" y="${(yTopBody + yBottomBody)/2}" text-anchor="middle" font-size="10" transform="rotate(90 ${x5 + 65} ${(yTopBody + yBottomBody)/2})">H = ${H} mm</text>`;

      // Top flap
      svg += `<line x1="${x5 + 80}" y1="${yTop}" x2="${x5 + 80}" y2="${yTopBody}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${x5 + 95}" y="${(yTop + yTopBody)/2}" text-anchor="middle" font-size="10" transform="rotate(90 ${x5 + 95} ${(yTop + yTopBody)/2})">Top Flap = ${flapH} mm</text>`;

      // Bottom flap
      svg += `<line x1="${x5 + 110}" y1="${yBottomBody}" x2="${x5 + 110}" y2="${yBottom}" stroke="black" stroke-width="1" marker-start="url(#arrow)" marker-end="url(#arrow)"/>`;
      svg += `<text x="${x5 + 125}" y="${(yBottomBody + yBottom)/2}" text-anchor="middle" font-size="10" transform="rotate(90 ${x5 + 125} ${(yBottomBody + yBottom)/2})">Bottom Flap = ${flapH} mm</text>`;

      svg += '</svg>';
      return svg;
    },
    exportToPDF() {
      if (!this.sketchSvg) return;

      const data = {
        customer_id: this.form.customer_id,
        fefco_code: this.form.fefco_code,
        length: this.form.length,
        width: this.form.width,
        height: this.form.height,
        sketch_svg: this.sketchSvg
      };

      axios.post('/api/carton-sketch/export-pdf', data, {
        responseType: 'blob'
      }).then(response => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'carton_sketch.pdf');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }).catch(error => {
        console.error('Error exporting PDF:', error);
        alert('Error exporting PDF');
      });
    }
  }
};
</script>

<style scoped>
.sketch-container {
  border: 1px solid #ccc;
  padding: 10px;
  display: inline-block;
  background: #f9f9f9;
}
</style>
