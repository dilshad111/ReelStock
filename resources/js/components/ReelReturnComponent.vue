<template>
  <div class="container">
    <h2>Paper Reel Return</h2>
    <button @click="showForm = !showForm" class="btn btn-primary mb-3">Return Reel</button>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>Return Reel</h5>
        <form @submit.prevent="saveReturn">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label>Reel No.</label>
                <input v-model="returnData.reel_no" type="text" class="form-control" required @blur="fetchReel">
              </div>
              <div v-if="reel" class="mb-3">
                <p>Quality: {{ reel.paperQuality.quality }} - {{ reel.paperQuality.gsm_range }}</p>
                <p>Size: {{ reel.reel_size }}</p>
                <p>Current Balance: {{ reel.balance_weight }} kg</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label>Return Date</label>
                <input v-model="returnData.return_date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Remaining Weight (kg)</label>
                <input v-model="returnData.remaining_weight" type="number" step="0.01" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Condition</label>
                <select v-model="returnData.condition" class="form-control" required>
                  <option value="good">Good</option>
                  <option value="damaged">Damaged</option>
                  <option value="qc_required">QC Required</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Remarks</label>
                <textarea v-model="returnData.remarks" class="form-control"></textarea>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-success">Save</button>
          <button @click="cancel" class="btn btn-secondary">Cancel</button>
        </form>
      </div>
    </div>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>Reel No.</th>
          <th>Return Date</th>
          <th>Remaining Weight</th>
          <th>Condition</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in returns" :key="r.id">
          <td>{{ r.reel.reel_no }}</td>
          <td>{{ r.return_date }}</td>
          <td>{{ r.remaining_weight }} kg</td>
          <td>{{ r.condition }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      returns: [],
      reel: null,
      returnData: {
        reel_no: '',
        return_date: new Date().toISOString().substr(0,10),
        remaining_weight: '',
        condition: 'good',
        remarks: ''
      },
      showForm: false
    };
  },
  mounted() {
    this.fetchReturns();
  },
  methods: {
    fetchReturns() {
      axios.get('/api/reel-returns').then(response => {
        this.returns = response.data;
      });
    },
    fetchReel() {
      if (this.returnData.reel_no) {
        axios.get(`/api/fetch-reel-return/${this.returnData.reel_no}`).then(response => {
          this.reel = response.data;
          this.returnData.remaining_weight = this.reel.balance_weight;
        }).catch(() => {
          this.reel = null;
          this.returnData.remaining_weight = '';
          alert('Reel not found');
        });
      }
    },
    saveReturn() {
      axios.post('/api/reel-returns', this.returnData).then(() => {
        this.fetchReturns();
        this.cancel();
      }).catch(error => {
        alert(error.response?.data?.error || 'Error saving return');
      });
    },
    cancel() {
      this.returnData = {
        reel_no: '',
        return_date: new Date().toISOString().substr(0,10),
        remaining_weight: '',
        condition: 'good',
        remarks: ''
      };
      this.reel = null;
      this.showForm = false;
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
