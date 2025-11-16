<template>
  <div class="container">
    <h2>Paper Quality Management</h2>
    <button @click="showForm = !showForm" class="btn btn-primary mb-3">Add Paper Quality</button>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>{{ editing ? 'Edit Paper Quality' : 'Add Paper Quality' }}</h5>
        <form @submit.prevent="saveQuality">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label>Quality</label>
                <input v-model="quality.quality" type="text" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label>GSM Range</label>
                <input v-model="quality.gsm_range" type="text" class="form-control" required>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-success">{{ editing ? 'Update' : 'Save' }}</button>
          <button @click="cancel" class="btn btn-secondary">Cancel</button>
        </form>
      </div>
    </div>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>Item Code</th>
          <th>Quality</th>
          <th>GSM Range</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="q in qualities" :key="q.id">
          <td>{{ q.item_code }}</td>
          <td>{{ q.quality }}</td>
          <td>{{ q.gsm_range }}</td>
          <td>
            <button @click="editQuality(q)" class="btn btn-sm btn-warning">Edit</button>
            <button @click="deleteQuality(q.id)" class="btn btn-sm btn-danger">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: ['user'],
  data() {
    return {
      qualities: [],
      quality: {
        quality: '',
        gsm_range: ''
      },
      showForm: false,
      editing: false
    };
  },
  mounted() {
    if (this.user) {
      this.setAuthAndFetch();
    }
  },
  watch: {
    user(newVal) {
      if (newVal) {
        this.setAuthAndFetch();
      }
    }
  },
  methods: {
    setAuthAndFetch() {
      if (localStorage.getItem('token')) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
      }
      this.fetchQualities();
    },
    fetchQualities() {
      axios.get('/api/paper-qualities').then(response => {
        this.qualities = response.data;
      });
    },
    saveQuality() {
      if (this.editing) {
        axios.put(`/api/paper-qualities/${this.quality.id}`, this.quality).then(() => {
          this.fetchQualities();
          this.cancel();
        });
      } else {
        axios.post('/api/paper-qualities', this.quality).then(() => {
          this.fetchQualities();
          this.cancel();
        });
      }
    },
    editQuality(q) {
      this.quality = { ...q };
      this.editing = true;
      this.showForm = true;
    },
    deleteQuality(id) {
      if (confirm('Are you sure?')) {
        axios.delete(`/api/paper-qualities/${id}`).then(() => {
          this.fetchQualities();
        });
      }
    },
    cancel() {
      this.quality = {
        quality: '',
        gsm_range: ''
      };
      this.showForm = false;
      this.editing = false;
    }
  }
};
</script>

<style scoped>
/* Add styles if needed */
</style>
