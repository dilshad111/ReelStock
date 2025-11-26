<template>
  <div class="container">
    <h2><i class="bi bi-box"></i> Cartons Management</h2>
    <p class="text-muted">Select a cartons module from the menu above.</p>
    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><i class="bi bi-people"></i> Customers</h5>
            <p class="card-text flex-grow-1">Manage customer information for carton orders.</p>
            <button @click="$parent.setView('customers')" class="btn btn-primary mt-auto">Manage Customers</button>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><i class="bi bi-pencil-square"></i> Sketch Generator</h5>
            <p class="card-text flex-grow-1">Generate carton sketches based on FEFCO codes and export to PDF.</p>
            <button @click="$parent.setView('sketch-generator')" class="btn btn-primary mt-auto">Generate Sketches</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CartonsComponent',
  props: {
    user: {
      type: Object,
      default: null
    }
  }
};
</script>

<style scoped>
/*********************************************************
 * Basic placeholder styling so the section doesn’t look
 * empty while functionality is being developed
 *********************************************************/
.container {
  padding: 2rem 0;
}
</style>
