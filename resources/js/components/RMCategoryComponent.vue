<template>
  <div class="container-fluid px-0 rm-category-master">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pt-2">
      <div>
        <h2 class="dashboard-title">
          <i class="bi bi-diagram-3-fill text-indigo"></i> Raw Material Categories
        </h2>
        <p class="text-muted small mb-0">Maintain ERP material categories and dependent subcategories for raw material masters.</p>
      </div>
      <button type="button" class="btn btn-outline-primary shadow-sm" :disabled="loading" @click="loadAll">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
      </button>
    </div>

    <div class="row g-4">
      <div class="col-xl-5">
        <div class="card shadow-sm border-0 category-admin-card">
          <div class="card-header bg-transparent border-0 pb-0">
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div>
                <h5 class="fw-bold text-slate-800 mb-1">
                  <i class="bi bi-folder-plus text-indigo me-1"></i>
                  {{ categoryEditing ? 'Edit Category' : 'Category Form' }}
                </h5>
                <p class="small text-muted mb-0">Add or change parent material groups.</p>
              </div>
              <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">Parent</span>
            </div>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitCategory" novalidate>
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Category Name <span class="text-danger">*</span></label>
                <input
                  v-model.trim="categoryForm.name"
                  type="text"
                  class="form-control"
                  maxlength="255"
                  placeholder="e.g. Adhesives & Chemicals"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Description</label>
                <textarea
                  v-model.trim="categoryForm.description"
                  class="form-control"
                  rows="3"
                  placeholder="Short usage note for this material group"
                ></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold text-slate-700">Status <span class="text-danger">*</span></label>
                <select v-model="categoryForm.status" class="form-control" required>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success shadow-sm" :disabled="savingCategory">
                  <span v-if="savingCategory" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-save me-1"></i>
                  {{ categoryEditing ? 'Update Category' : 'Create Category' }}
                </button>
                <button type="button" class="btn btn-light border" @click="resetCategoryForm">
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm border-0 mt-4 category-admin-card">
          <div class="card-header bg-transparent border-0 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <h5 class="fw-bold text-slate-800 mb-0">Category Registry</h5>
              <span class="small text-muted">{{ filteredCategories.length }} records</span>
            </div>
            <div class="input-group mt-3">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input v-model.trim="categorySearch" type="search" class="form-control" placeholder="Search categories..." />
            </div>
          </div>
          <div class="card-body pt-3">
            <div class="table-responsive registry-table-wrap">
              <table class="table table-hover align-middle mb-0 registry-table">
                <thead>
                  <tr>
                    <th>Category</th>
                    <th class="text-center">Subcategories</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td colspan="4" class="text-center py-4 text-muted">Loading categories...</td>
                  </tr>
                  <tr v-else-if="!filteredCategories.length">
                    <td colspan="4" class="text-center py-4 text-muted">No categories found.</td>
                  </tr>
                  <tr v-for="category in filteredCategories" :key="category.id">
                    <td>
                      <div class="fw-bold text-slate-800">{{ category.name }}</div>
                      <div class="small text-muted text-truncate category-description">
                        {{ category.description || 'No description' }}
                      </div>
                    </td>
                    <td class="text-center fw-semibold">{{ subcategoryCount(category.id) }}</td>
                    <td class="text-center">
                      <span class="badge status-badge" :class="category.status === 'Active' ? 'bg-success-soft' : 'bg-secondary-soft'">
                        {{ category.status }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" title="Edit category" @click="editCategory(category)">
                          <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" title="Delete category" @click="deleteCategory(category)">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-7">
        <div class="card shadow-sm border-0 category-admin-card">
          <div class="card-header bg-transparent border-0 pb-0">
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div>
                <h5 class="fw-bold text-slate-800 mb-1">
                  <i class="bi bi-tags-fill text-indigo me-1"></i>
                  {{ subcategoryEditing ? 'Edit Subcategory' : 'Subcategory Form' }}
                </h5>
                <p class="small text-muted mb-0">Subcategories are controlled by the selected parent category.</p>
              </div>
              <span class="badge rounded-pill bg-info-subtle text-info-emphasis">Dependent</span>
            </div>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitSubcategory" novalidate>
              <div class="row g-3">
                <div class="col-lg-5">
                  <label class="form-label fw-semibold text-slate-700">Parent Category <span class="text-danger">*</span></label>
                  <select v-model="subcategoryForm.rm_category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                </div>
                <div class="col-lg-5">
                  <label class="form-label fw-semibold text-slate-700">Subcategory Name <span class="text-danger">*</span></label>
                  <input
                    v-model.trim="subcategoryForm.name"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    placeholder="e.g. Corn Starch"
                    required
                  />
                </div>
                <div class="col-lg-2">
                  <label class="form-label fw-semibold text-slate-700">Status</label>
                  <select v-model="subcategoryForm.status" class="form-control">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-success shadow-sm" :disabled="savingSubcategory">
                  <span v-if="savingSubcategory" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-save me-1"></i>
                  {{ subcategoryEditing ? 'Update Subcategory' : 'Create Subcategory' }}
                </button>
                <button type="button" class="btn btn-light border" @click="resetSubcategoryForm">
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm border-0 mt-4 category-admin-card">
          <div class="card-header bg-transparent border-0 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <h5 class="fw-bold text-slate-800 mb-0">Subcategory Registry</h5>
              <span class="small text-muted">{{ filteredSubcategories.length }} records</span>
            </div>
            <div class="row g-2 mt-3">
              <div class="col-md-7">
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input v-model.trim="subcategorySearch" type="search" class="form-control" placeholder="Search subcategories..." />
                </div>
              </div>
              <div class="col-md-5">
                <select v-model="subcategoryCategoryFilter" class="form-control">
                  <option value="">All Categories</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
          <div class="card-body pt-3">
            <div class="table-responsive registry-table-wrap subcategory-table-wrap">
              <table class="table table-hover align-middle mb-0 registry-table">
                <thead>
                  <tr>
                    <th>Subcategory</th>
                    <th>Parent Category</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td colspan="4" class="text-center py-4 text-muted">Loading subcategories...</td>
                  </tr>
                  <tr v-else-if="!filteredSubcategories.length">
                    <td colspan="4" class="text-center py-4 text-muted">No subcategories found.</td>
                  </tr>
                  <tr v-for="subcategory in filteredSubcategories" :key="subcategory.id">
                    <td class="fw-bold text-slate-800">{{ subcategory.name }}</td>
                    <td>{{ subcategory.category?.name || categoryName(subcategory.rm_category_id) || '-' }}</td>
                    <td class="text-center">
                      <span class="badge status-badge" :class="subcategory.status === 'Active' ? 'bg-success-soft' : 'bg-secondary-soft'">
                        {{ subcategory.status }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" title="Edit subcategory" @click="editSubcategory(subcategory)">
                          <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" title="Delete subcategory" @click="deleteSubcategory(subcategory)">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const categories = ref([]);
const subcategories = ref([]);
const loading = ref(false);
const savingCategory = ref(false);
const savingSubcategory = ref(false);
const categoryEditing = ref(false);
const subcategoryEditing = ref(false);
const categorySearch = ref('');
const subcategorySearch = ref('');
const subcategoryCategoryFilter = ref('');

const blankCategoryForm = () => ({
  id: null,
  name: '',
  description: '',
  status: 'Active'
});

const blankSubcategoryForm = () => ({
  id: null,
  rm_category_id: '',
  name: '',
  status: 'Active'
});

const categoryForm = reactive(blankCategoryForm());
const subcategoryForm = reactive(blankSubcategoryForm());

const normalize = value => String(value || '').trim().toLowerCase();

const filteredCategories = computed(() => {
  const term = normalize(categorySearch.value);
  if (!term) {
    return categories.value;
  }

  return categories.value.filter(category => {
    return normalize(category.name).includes(term) || normalize(category.description).includes(term);
  });
});

const filteredSubcategories = computed(() => {
  const term = normalize(subcategorySearch.value);
  const categoryFilter = Number(subcategoryCategoryFilter.value || 0);

  return subcategories.value.filter(subcategory => {
    const matchesCategory = !categoryFilter || Number(subcategory.rm_category_id) === categoryFilter;
    const parentName = subcategory.category?.name || categoryName(subcategory.rm_category_id);
    const matchesSearch = !term
      || normalize(subcategory.name).includes(term)
      || normalize(parentName).includes(term);

    return matchesCategory && matchesSearch;
  });
});

const categoryName = id => {
  return categories.value.find(category => Number(category.id) === Number(id))?.name || '';
};

const subcategoryCount = categoryId => {
  return subcategories.value.filter(subcategory => Number(subcategory.rm_category_id) === Number(categoryId)).length;
};

const errorMessage = (error, fallback) => {
  const errors = error?.response?.data?.errors;
  if (errors) {
    const first = Object.values(errors).flat()[0];
    if (first) {
      return first;
    }
  }

  return error?.response?.data?.error || error?.response?.data?.message || fallback;
};

const fetchCategories = async () => {
  const response = await axios.get('/api/rm-categories');
  categories.value = Array.isArray(response.data) ? response.data : [];
};

const fetchSubcategories = async () => {
  const response = await axios.get('/api/rm-subcategories');
  subcategories.value = Array.isArray(response.data) ? response.data : [];
};

const loadAll = async () => {
  loading.value = true;
  try {
    await Promise.all([fetchCategories(), fetchSubcategories()]);
  } catch (error) {
    ElMessage.error(errorMessage(error, 'Failed to load category records'));
  } finally {
    loading.value = false;
  }
};

const resetCategoryForm = () => {
  Object.assign(categoryForm, blankCategoryForm());
  categoryEditing.value = false;
};

const resetSubcategoryForm = () => {
  Object.assign(subcategoryForm, blankSubcategoryForm());
  subcategoryEditing.value = false;
};

const submitCategory = async () => {
  if (!categoryForm.name) {
    ElMessage.error('Category name is required');
    return;
  }

  savingCategory.value = true;
  try {
    const payload = {
      name: categoryForm.name,
      description: categoryForm.description || null,
      status: categoryForm.status
    };

    if (categoryEditing.value) {
      await axios.put(`/api/rm-categories/${categoryForm.id}`, payload);
      ElMessage.success('Category updated successfully');
    } else {
      await axios.post('/api/rm-categories', payload);
      ElMessage.success('Category created successfully');
    }

    resetCategoryForm();
    await loadAll();
  } catch (error) {
    ElMessage.error(errorMessage(error, 'Failed to save category'));
  } finally {
    savingCategory.value = false;
  }
};

const editCategory = category => {
  Object.assign(categoryForm, {
    id: category.id,
    name: category.name || '',
    description: category.description || '',
    status: category.status || 'Active'
  });
  categoryEditing.value = true;
};

const deleteCategory = async category => {
  try {
    await ElMessageBox.confirm(
      `Delete category "${category.name}"? Categories assigned to raw materials cannot be deleted.`,
      'Delete Category',
      {
        type: 'warning',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
      }
    );

    await axios.delete(`/api/rm-categories/${category.id}`);
    ElMessage.success('Category deleted successfully');
    if (Number(categoryForm.id) === Number(category.id)) {
      resetCategoryForm();
    }
    await loadAll();
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(errorMessage(error, 'Failed to delete category'));
    }
  }
};

const submitSubcategory = async () => {
  if (!subcategoryForm.rm_category_id) {
    ElMessage.error('Parent category is required');
    return;
  }
  if (!subcategoryForm.name) {
    ElMessage.error('Subcategory name is required');
    return;
  }

  savingSubcategory.value = true;
  try {
    const payload = {
      rm_category_id: subcategoryForm.rm_category_id,
      name: subcategoryForm.name,
      status: subcategoryForm.status
    };

    if (subcategoryEditing.value) {
      await axios.put(`/api/rm-subcategories/${subcategoryForm.id}`, payload);
      ElMessage.success('Subcategory updated successfully');
    } else {
      await axios.post('/api/rm-subcategories', payload);
      ElMessage.success('Subcategory created successfully');
    }

    resetSubcategoryForm();
    await loadAll();
  } catch (error) {
    ElMessage.error(errorMessage(error, 'Failed to save subcategory'));
  } finally {
    savingSubcategory.value = false;
  }
};

const editSubcategory = subcategory => {
  Object.assign(subcategoryForm, {
    id: subcategory.id,
    rm_category_id: subcategory.rm_category_id || subcategory.category?.id || '',
    name: subcategory.name || '',
    status: subcategory.status || 'Active'
  });
  subcategoryEditing.value = true;
};

const deleteSubcategory = async subcategory => {
  try {
    await ElMessageBox.confirm(
      `Delete subcategory "${subcategory.name}"? Subcategories assigned to raw materials cannot be deleted.`,
      'Delete Subcategory',
      {
        type: 'warning',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
      }
    );

    await axios.delete(`/api/rm-subcategories/${subcategory.id}`);
    ElMessage.success('Subcategory deleted successfully');
    if (Number(subcategoryForm.id) === Number(subcategory.id)) {
      resetSubcategoryForm();
    }
    await loadAll();
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(errorMessage(error, 'Failed to delete subcategory'));
    }
  }
};

onMounted(loadAll);
</script>

<style scoped>
.rm-category-master {
  color: #1e293b;
}

.dashboard-title {
  color: #1e293b;
  font-weight: 800;
  margin-bottom: 0;
}

.text-indigo {
  color: #4f46e5;
}

.text-slate-800 {
  color: #1e293b;
}

.text-slate-700 {
  color: #334155;
}

.category-admin-card {
  border-radius: 12px;
  background: #ffffff;
}

.category-description {
  max-width: 260px;
}

.registry-table-wrap {
  max-height: 440px;
  overflow: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.subcategory-table-wrap {
  max-height: 520px;
}

.registry-table thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: #f8fafc;
  color: #475569;
  font-size: 0.78rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border-bottom: 1px solid #cbd5e1;
}

.registry-table td {
  padding: 0.78rem 0.75rem;
}

.status-badge {
  min-width: 74px;
  padding: 0.45rem 0.7rem;
}

.bg-success-soft {
  background: #dcfce7;
  color: #15803d;
}

.bg-secondary-soft {
  background: #e2e8f0;
  color: #475569;
}

.form-control,
.input-group-text {
  min-height: 42px;
}

:global([data-theme="dark"]) .rm-category-master,
:global(body.dark-mode) .rm-category-master {
  color: #e5edf8;
}

:global([data-theme="dark"]) .rm-category-master .dashboard-title,
:global([data-theme="dark"]) .rm-category-master .text-slate-800,
:global([data-theme="dark"]) .rm-category-master .text-slate-700,
:global(body.dark-mode) .rm-category-master .dashboard-title,
:global(body.dark-mode) .rm-category-master .text-slate-800,
:global(body.dark-mode) .rm-category-master .text-slate-700 {
  color: #f8fafc !important;
}

:global([data-theme="dark"]) .rm-category-master .category-admin-card,
:global(body.dark-mode) .rm-category-master .category-admin-card {
  background: #111827;
  border: 1px solid #334155 !important;
  color: #f8fafc;
}

:global([data-theme="dark"]) .rm-category-master .form-control,
:global([data-theme="dark"]) .rm-category-master .input-group-text,
:global(body.dark-mode) .rm-category-master .form-control,
:global(body.dark-mode) .rm-category-master .input-group-text {
  background: #1e293b;
  border-color: #475569;
  color: #f8fafc;
}

:global([data-theme="dark"]) .rm-category-master .form-control::placeholder,
:global(body.dark-mode) .rm-category-master .form-control::placeholder {
  color: #94a3b8;
}

:global([data-theme="dark"]) .rm-category-master .registry-table-wrap,
:global(body.dark-mode) .rm-category-master .registry-table-wrap {
  border-color: #334155;
}

:global([data-theme="dark"]) .rm-category-master .registry-table,
:global(body.dark-mode) .rm-category-master .registry-table {
  color: #e5edf8;
}

:global([data-theme="dark"]) .rm-category-master .registry-table thead th,
:global(body.dark-mode) .rm-category-master .registry-table thead th {
  background: #1e293b;
  border-color: #334155;
  color: #cbd5e1;
}

:global([data-theme="dark"]) .rm-category-master .registry-table td,
:global(body.dark-mode) .rm-category-master .registry-table td {
  border-color: #263449;
}

:global([data-theme="dark"]) .rm-category-master .btn-light,
:global(body.dark-mode) .rm-category-master .btn-light {
  background: #1e293b;
  border-color: #475569 !important;
  color: #f8fafc;
}
</style>
