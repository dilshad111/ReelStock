<template>
  <div class="container">
    <h2>Reports</h2>
    <p>Select a report from the menu above or click on one of the options below.</p>
    <div class="row">
      <div class="col-md-3 mb-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Monthly Consumption Report</h5>
            <p class="card-text flex-grow-1">View consumption data by month and quality.</p>
            <button @click="$parent.currentView = 'monthly-consumption'" class="btn btn-primary mt-auto">View Report</button>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Reel Stock Report</h5>
            <p class="card-text flex-grow-1">Check current stock levels for all reels.</p>
            <button @click="$parent.currentView = 'reel-stock'" class="btn btn-primary mt-auto">View Report</button>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Reel Received Report</h5>
            <p class="card-text flex-grow-1">Review reel receipts and supplier information.</p>
            <button @click="$parent.currentView = 'reel-receipt'" class="btn btn-primary mt-auto">View Report</button>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Monthly Closing Stock Report</h5>
            <p class="card-text flex-grow-1">View stock levels at the end of each month for the selected month-year.</p>
            <button @click="$parent.currentView = 'monthly-closing'" class="btn btn-primary mt-auto">View Report</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ['user']
};
</script>

<style scoped>
.card {
  margin-bottom: 1rem;
}
</style>
