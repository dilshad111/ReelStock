<template>
    <div class="best-ui-showcase">
        <!-- Hero Section -->
        <div class="hero-section text-center py-5 mb-5">
            <h1 class="display-4 fw-bold mb-3 gradient-text">Modern UI Showcase</h1>
            <p class="lead text-muted mb-4">Experiencing the next generation of user interface design.</p>
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm hover-lift">Get Started</button>
                <button class="btn btn-outline-secondary btn-lg rounded-pill px-5 hover-lift">Learn More</button>
            </div>
        </div>

        <div class="container">
            <!-- Stats Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3" v-for="(stat, index) in stats" :key="index">
                    <div class="card border-0 shadow-sm h-100 glass-card hover-scale">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box rounded-circle p-3 me-3" :class="stat.colorClass">
                                    <i :class="stat.icon"></i>
                                </div>
                                <h6 class="card-subtitle text-muted text-uppercase mb-0">{{ stat.title }}</h6>
                            </div>
                            <h2 class="card-title fw-bold mb-0">{{ stat.value }}</h2>
                            <p class="card-text text-success small mt-2">
                                <i class="fas fa-arrow-up me-1"></i> {{ stat.growth }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="row g-4">
                <!-- Chart Section (Placeholder) -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm glass-card h-100">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0">Revenue Overview</h5>
                        </div>
                        <div class="card-body p-4 d-flex align-items-center justify-content-center" style="min-height: 300px;">
                            <div class="text-center text-muted">
                                <i class="fas fa-chart-area fa-3x mb-3 opacity-50"></i>
                                <p>Interactive Chart Component Placeholder</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm glass-card h-100">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item border-0 px-4 py-3 d-flex align-items-center" v-for="i in 5" :key="i">
                                    <img :src="`https://ui-avatars.com/api/?name=User+${i}&background=random`" class="rounded-circle me-3" width="40" height="40" alt="User">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 text-dark">User {{ i }} completed a task</h6>
                                        <small class="text-muted">2 mins ago</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Table -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm glass-card">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Recent Transactions</h5>
                            <button class="btn btn-sm btn-light rounded-pill px-3">View All</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 py-3 border-0 text-muted small text-uppercase">ID</th>
                                            <th class="py-3 border-0 text-muted small text-uppercase">Customer</th>
                                            <th class="py-3 border-0 text-muted small text-uppercase">Date</th>
                                            <th class="py-3 border-0 text-muted small text-uppercase">Amount</th>
                                            <th class="py-3 border-0 text-muted small text-uppercase">Status</th>
                                            <th class="pe-4 py-3 border-0 text-muted small text-uppercase text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="i in 5" :key="i">
                                            <td class="ps-4 fw-bold">#TRX-00{{ i }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <span class="text-primary fw-bold">C{{ i }}</span>
                                                    </div>
                                                    <span>Customer {{ i }}</span>
                                                </div>
                                            </td>
                                            <td class="text-muted">Oct 24, 2023</td>
                                            <td class="fw-bold">$1,20{{ i }}.00</td>
                                            <td><span class="badge bg-soft-success text-success rounded-pill px-3">Completed</span></td>
                                            <td class="pe-4 text-end">
                                                <button class="btn btn-icon btn-sm btn-ghost-secondary"><i class="fas fa-ellipsis-h"></i></button>
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
    </div>
</template>

<script>
export default {
    name: 'BestUiShowcaseComponent',
    data() {
        return {
            stats: [
                { title: 'Total Revenue', value: '$54,230', growth: '12.5%', icon: 'fas fa-dollar-sign', colorClass: 'bg-soft-primary text-primary' },
                { title: 'Active Users', value: '2,450', growth: '5.2%', icon: 'fas fa-users', colorClass: 'bg-soft-success text-success' },
                { title: 'New Orders', value: '1,205', growth: '8.1%', icon: 'fas fa-shopping-cart', colorClass: 'bg-soft-warning text-warning' },
                { title: 'Satisfaction', value: '98%', growth: '2.4%', icon: 'fas fa-heart', colorClass: 'bg-soft-danger text-danger' },
            ]
        }
    }
}
</script>

<style scoped>
/* Custom Styles for this component */
.best-ui-showcase {
    background-color: #f8f9fa;
    min-height: 100vh;
    font-family: 'Inter', sans-serif; /* Assuming Inter or similar is available or falls back */
    padding-bottom: 5rem;
}

.gradient-text {
    background: linear-gradient(45deg, #2563eb, #9333ea);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.glass-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.hover-scale:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.bg-soft-primary { background-color: rgba(37, 99, 235, 0.1); }
.bg-soft-success { background-color: rgba(16, 185, 129, 0.1); }
.bg-soft-warning { background-color: rgba(245, 158, 11, 0.1); }
.bg-soft-danger { background-color: rgba(239, 68, 68, 0.1); }

.text-primary { color: #2563eb !important; }
.text-success { color: #10b981 !important; }
.text-warning { color: #f59e0b !important; }
.text-danger { color: #ef4444 !important; }

.btn-primary {
    background: linear-gradient(45deg, #2563eb, #1d4ed8);
    border: none;
}

.avatar-sm {
    font-size: 0.8rem;
}

.btn-ghost-secondary {
    color: #6c757d;
    background: transparent;
    border: none;
}
.btn-ghost-secondary:hover {
    background-color: rgba(108, 117, 125, 0.1);
    color: #343a40;
}
</style>
