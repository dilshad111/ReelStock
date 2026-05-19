<template>
  <div class="container">
    <h2><i class="bi bi-palette"></i> Paper Colors Management</h2>
    <button @click="showForm = !showForm" class="btn btn-primary mb-3">Add Paper Color</button>

    <div v-if="showForm" class="card mb-3">
      <div class="card-body">
        <h5>{{ editing ? 'Edit Paper Color' : 'Add Paper Color' }}</h5>
        <form @submit.prevent="saveColor">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label>Color Name</label>
                <input v-model="color.name" type="text" class="form-control" placeholder="e.g. White" required>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-success">{{ editing ? 'Update' : 'Save' }}</button>
          <button @click="cancel" class="btn btn-secondary">Cancel</button>
        </form>
      </div>
    </div>

    <table class="table table-striped table-sm small">
      <thead>
        <tr>
          <th>S.No.</th>
          <th>Color Name</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(c, index) in colors" :key="c.id">
          <td>{{ index + 1 }}</td>
          <td>{{ c.name }}</td>
          <td>
            <button @click="editColor(c)" class="btn btn-sm btn-warning">Edit</button>
            <button @click="deleteColor(c.id)" class="btn btn-sm btn-danger">Delete</button>
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
      colors: [],
      color: { name: '' },
      showForm: false,
      editing: false
    };
  },
  mounted() { if (this.user) this.setAuthAndFetch(); },
  watch: { user(v) { if (v) this.setAuthAndFetch(); } },
  methods: {
    setAuthAndFetch() {
      if (localStorage.getItem('token')) axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
      this.fetchColors();
    },
    fetchColors() { axios.get('/api/paper-colors').then(r => { this.colors = r.data; }); },
    saveColor() {
      const action = this.editing
        ? axios.put(`/api/paper-colors/${this.color.id}`, this.color)
        : axios.post('/api/paper-colors', this.color);
      action.then(() => { this.fetchColors(); this.cancel(); })
        .catch(err => {
          if (err.response && err.response.data.errors) {
            alert(Object.values(err.response.data.errors).flat().join('\n'));
          }
        });
    },
    editColor(c) { this.color = { ...c }; this.editing = true; this.showForm = true; },
    deleteColor(id) { if (confirm('Are you sure?')) axios.delete(`/api/paper-colors/${id}`).then(() => this.fetchColors()); },
    cancel() {
      this.color = { name: '' };
      this.showForm = false; this.editing = false;
    }
  }
};
</script>
