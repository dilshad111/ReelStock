<template>
    <div class="job-card-management p-4">
        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 p-3 glass-header rounded shadow-sm">
            <div>
                <h2 class="h4 mb-1 fw-bold text-dark">
                    <i class="bi bi-file-earmark-ruled-fill text-indigo me-2"></i>Manufacturing Specifications &amp; Job Cards
                </h2>
                <p class="small text-muted mb-0">Packaging specifications, ply layer compositions, and real-time corrugation calculation engine.</p>
            </div>
            <el-button type="primary" class="btn-indigo shadow-sm" @click="openCreateDialog">
                <i class="bi bi-plus-circle me-1"></i> Create Spec Job Card
            </el-button>
        </div>

        <!-- Main Glassmorphic Listing Panel -->
        <div class="glass-card shadow-sm p-4 mb-4">
            <!-- Filter Bar -->
            <div class="row g-3 mb-4 filter-container p-3 rounded border bg-light-soft">
                <div class="col-md-4">
                    <label class="small text-muted fw-bold mb-1">Search Job Card</label>
                    <el-input v-model="filters.search" placeholder="JC-XXXX-XXXX..." clearable @input="fetchJobCards" />
                </div>
                <div class="col-md-3">
                    <label class="small text-muted fw-bold mb-1">Customer Filter</label>
                    <el-select v-model="filters.customer_id" placeholder="All Customers" clearable @change="fetchJobCards" class="w-100" filterable>
                        <el-option v-for="c in customers" :key="c.id" :label="c.name" :value="c.id" />
                    </el-select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted fw-bold mb-1">Status Filter</label>
                    <el-select v-model="filters.status" placeholder="All Statuses" clearable @change="fetchJobCards" class="w-100">
                        <el-option label="Open" value="Open" />
                        <el-option label="In-Progress" value="In-Progress" />
                        <el-option label="Completed" value="Completed" />
                        <el-option label="Cancelled" value="Cancelled" />
                    </el-select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <el-button @click="resetFilters" class="w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</el-button>
                </div>
            </div>

            <!-- Job Card Master Table -->
            <el-table :data="jobCards" border v-loading="loading" style="width: 100%" class="modern-table shadow-xs">
                <el-table-column prop="job_card_no" label="Job Card #" width="155">
                    <template #default="scope">
                        <span class="fw-bold text-indigo">{{ scope.row.job_card_no }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="customer.name" label="Customer Name" min-width="180" show-overflow-tooltip />
                <el-table-column prop="product.item_name" label="FG Product" min-width="200" show-overflow-tooltip />
                <el-table-column label="Box Type" width="130" prop="carton_type" />
                <el-table-column label="Outer Dimensions (mm)" min-width="160">
                    <template #default="scope">
                        <span class="small font-mono fw-bold">
                            {{ formatNum(scope.row.length_mm, 1) }}x{{ formatNum(scope.row.width_mm, 1) }}x{{ formatNum(scope.row.height_mm, 1) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="Est. Unit Wt (kg)" width="130" align="right">
                    <template #default="scope">
                        <span class="fw-bold text-dark">{{ formatNum(scope.row.est_unit_weight, 4) }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="planned_qty" label="Planned Qty" width="120" align="right">
                    <template #default="scope">
                        {{ formatQty(scope.row.planned_qty) }}
                    </template>
                </el-table-column>
                <el-table-column prop="status" label="Status" width="115" align="center">
                    <template #default="scope">
                        <el-tag :type="getStatusTag(scope.row.status)" effect="dark" size="small">{{ scope.row.status }}</el-tag>
                    </template>
                </el-table-column>
                <th width="190">Actions</th>
                <el-table-column label="Actions" width="190" align="center">
                    <template #default="scope">
                        <el-button-group>
                            <el-button type="info" size="small" @click="viewDetails(scope.row)" title="View Specifications" circle>
                                <i class="bi bi-eye"></i>
                            </el-button>
                            <el-button type="primary" size="small" @click="printDirectly(scope.row.id)" title="Print Blueprint" circle class="btn-indigo">
                                <i class="bi bi-printer"></i>
                            </el-button>
                            <el-button type="success" size="small" @click="openProductionDialog(scope.row)" title="Record Production" circle :disabled="scope.row.status === 'Completed' || scope.row.status === 'Cancelled'">
                                <i class="bi bi-play-circle"></i>
                            </el-button>
                            <el-button type="warning" size="small" @click="changeStatus(scope.row)" title="Change Status" circle>
                                <i class="bi bi-arrow-repeat"></i>
                            </el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>

            <div class="d-flex justify-content-center mt-4">
                <el-pagination v-model:current-page="pagination.current_page" :page-size="pagination.per_page" layout="prev, pager, next" :total="pagination.total" @current-change="fetchJobCards" />
            </div>
        </div>

        <!-- Create spec Job Card Dialog -->
        <el-dialog v-model="createDialogVisible" title="Create Packaging Spec Job Card" width="90%" top="30px" destroy-on-close class="glass-dialog">
            <el-form :model="form" :rules="rules" ref="formRef" label-position="top" class="high-density-form">
                <el-row :gutter="20">
                    <!-- Column 1: Basic Info & Dimensions -->
                    <el-col :lg="10" :md="24">
                        <div class="form-section p-3 mb-3 border rounded shadow-xs bg-white">
                            <h4 class="h6 fw-bold border-bottom pb-2 mb-3 text-indigo"><i class="bi bi-info-circle-fill me-1"></i>Basic Specifications</h4>
                            
                            <el-row :gutter="10">
                                <el-col :span="12">
                                    <el-form-item label="Job Card #" prop="job_card_no">
                                        <el-input v-model="form.job_card_no" placeholder="Auto-generated" />
                                    </el-form-item>
                                </el-col>
                                <el-col :span="12">
                                    <el-form-item label="Carton/FEFCO Code" prop="carton_type">
                                        <el-input v-model="form.carton_type" placeholder="e.g. FEFCO 0201" />
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <el-form-item label="Customer" prop="customer_id">
                                <el-select v-model="form.customer_id" placeholder="Select Customer" class="w-100" filterable @change="onCustomerChange">
                                    <el-option v-for="c in customers" :key="c.id" :label="c.name" :value="c.id" />
                                </el-select>
                            </el-form-item>

                            <el-form-item label="Finished Good Product" prop="fg_product_id">
                                <el-select v-model="form.fg_product_id" placeholder="Select Product" class="w-100" filterable>
                                    <el-option v-for="p in filteredProducts" :key="p.id" :label="p.item_name" :value="p.id" />
                                </el-select>
                            </el-form-item>

                            <el-row :gutter="10">
                                <el-col :span="8">
                                    <el-form-item label="Planned Qty" prop="planned_qty">
                                        <el-input-number v-model="form.planned_qty" :min="1" class="w-100" :controls="false" />
                                    </el-form-item>
                                </el-col>
                                <el-col :span="8">
                                    <el-form-item label="Start Date" prop="planned_date">
                                        <el-date-picker v-model="form.planned_date" type="date" class="w-100" format="DD/MM/YYYY" value-format="YYYY-MM-DD" />
                                    </el-form-item>
                                </el-col>
                                <el-col :span="8">
                                    <el-form-item label="Delivery Date">
                                        <el-date-picker v-model="form.delivery_date" type="date" class="w-100" format="DD/MM/YYYY" value-format="YYYY-MM-DD" />
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <!-- Size metrics -->
                            <div class="row g-2 mb-2 p-2 bg-light-soft rounded border">
                                <div class="col-4">
                                    <label class="small text-muted fw-bold">Length</label>
                                    <el-input-number v-model="form.length_mm" :min="0" :precision="1" class="w-100" :controls="false" @input="recalculateSpecs" />
                                </div>
                                <div class="col-4">
                                    <label class="small text-muted fw-bold">Width</label>
                                    <el-input-number v-model="form.width_mm" :min="0" :precision="1" class="w-100" :controls="false" @input="recalculateSpecs" />
                                </div>
                                <div class="col-4">
                                    <label class="small text-muted fw-bold">Height</label>
                                    <el-input-number v-model="form.height_mm" :min="0" :precision="1" class="w-100" :controls="false" @input="recalculateSpecs" />
                                </div>
                                <div class="col-6 mt-2">
                                    <label class="small text-muted fw-bold">UOM</label>
                                    <el-select v-model="form.uom" class="w-100" @change="recalculateSpecs">
                                        <el-option label="Millimeters (mm)" value="mm" />
                                        <el-option label="Inches (inch)" value="inch" />
                                        <el-option label="Centimeters (cm)" value="cm" />
                                    </el-select>
                                </div>
                                <div class="col-6 mt-2">
                                    <label class="small text-muted fw-bold">Layout Ups (Outs)</label>
                                    <el-input-number v-model="form.ups" :min="1" class="w-100" :controls="false" @input="recalculateSpecs" />
                                </div>
                            </div>
                        </div>
                    </el-col>

                    <!-- Column 2: Corrugation & Dynamic Layers -->
                    <el-col :lg="14" :md="24">
                        <div class="form-section p-3 mb-3 border rounded shadow-xs bg-white">
                            <h4 class="h6 fw-bold border-bottom pb-2 mb-3 text-indigo d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-layers-half me-1"></i>Structural Configuration &amp; Ply Layers</span>
                                <el-select v-model="form.pieces_count" size="small" style="width: 130px;" @change="onPiecesCountChange">
                                    <el-option label="Single Piece" :value="1" />
                                    <el-option label="2-Piece Box" :value="2" />
                                    <el-option label="3-Piece Box" :value="3" />
                                </el-select>
                            </h4>

                            <!-- Single Piece ply layout selection -->
                            <div v-if="form.pieces_count === 1">
                                <div class="d-flex gap-2 mb-3">
                                    <el-radio-group v-model="selectedPlyStructure" size="small" @change="onPlyCountChange">
                                        <el-radio-button label="3">3-Ply Structure</el-radio-button>
                                        <el-radio-button label="5">5-Ply Structure</el-radio-button>
                                        <el-radio-button label="7">7-Ply Structure</el-radio-button>
                                    </el-radio-group>
                                </div>

                                <table class="table table-sm table-bordered vertical-align-middle">
                                    <thead>
                                        <tr class="table-light text-secondary small">
                                            <th>Ply Layer</th>
                                            <th>Paper grade / substrate name</th>
                                            <th style="width: 100px;">GSM</th>
                                            <th style="width: 110px;">Flute Wave</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(l, i) in form.layers" :key="i">
                                            <td class="fw-bold small">{{ l.layer_type }}</td>
                                            <td>
                                                <el-input v-model="l.paper_name" placeholder="Kraft / Testliner..." size="small" />
                                            </td>
                                            <td>
                                                <el-input-number v-model="l.gsm" :min="0" class="w-100" size="small" :controls="false" @input="recalculateSpecs" />
                                            </td>
                                            <td>
                                                <el-select v-model="l.flute_profile" size="small" class="w-100" @change="recalculateSpecs">
                                                    <el-option label="Flat Liner" value="Flat" />
                                                    <el-option label="B Flute (1.35)" value="B" />
                                                    <el-option label="C Flute (1.45)" value="C" />
                                                    <el-option label="E Flute (1.25)" value="E" />
                                                </el-select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Multi Component configuration tab views -->
                            <div v-else>
                                <el-tabs v-model="activePieceTab" class="piece-tabs">
                                    <el-tab-pane v-for="(p, pIndex) in form.pieces" :key="pIndex" :label="p.piece_name" :name="'piece_' + pIndex">
                                        <div class="row g-2 mb-3 bg-light-soft p-2 rounded border">
                                            <div class="col-md-3">
                                                <label class="small text-muted fw-bold">Piece Name</label>
                                                <el-input v-model="p.piece_name" size="small" />
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small text-muted fw-bold">Length (mm)</label>
                                                <el-input-number v-model="p.length_mm" :min="0" size="small" class="w-100" :controls="false" @input="recalculateSpecs" />
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small text-muted fw-bold">Width (mm)</label>
                                                <el-input-number v-model="p.width_mm" :min="0" size="small" class="w-100" :controls="false" @input="recalculateSpecs" />
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small text-muted fw-bold">Height (mm)</label>
                                                <el-input-number v-model="p.height_mm" :min="0" size="small" class="w-100" :controls="false" @input="recalculateSpecs" />
                                            </div>
                                            <div class="col-md-3">
                                                <label class="small text-muted fw-bold">Layout Ups</label>
                                                <el-input-number v-model="p.ups" :min="1" size="small" class="w-100" :controls="false" @input="recalculateSpecs" />
                                            </div>
                                        </div>

                                        <table class="table table-sm table-bordered vertical-align-middle">
                                            <thead>
                                                <tr class="table-light text-secondary small">
                                                    <th>Ply</th>
                                                    <th>Paper grade</th>
                                                    <th style="width: 90px;">GSM</th>
                                                    <th style="width: 105px;">Flute</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(l, lIndex) in p.layers" :key="lIndex">
                                                    <td class="fw-bold small">{{ l.layer_type }}</td>
                                                    <td><el-input v-model="l.paper_name" size="small" /></td>
                                                    <td><el-input-number v-model="l.gsm" :min="0" size="small" :controls="false" class="w-100" @input="recalculateSpecs" /></td>
                                                    <td>
                                                        <el-select v-model="l.flute_profile" size="small" class="w-100" @change="recalculateSpecs">
                                                            <el-option label="Flat Liner" value="Flat" />
                                                            <el-option label="B Flute (1.35)" value="B" />
                                                            <el-option label="C Flute (1.45)" value="C" />
                                                            <el-option label="E Flute (1.25)" value="E" />
                                                        </el-select>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </el-tab-pane>
                                </el-tabs>
                            </div>

                            <!-- Real-time calculation output displays -->
                            <div class="row g-2 mt-2 p-3 bg-dark text-white rounded font-mono shadow-xs">
                                <div class="col-md-4 text-center border-end border-secondary">
                                    <div class="small text-gray-400">CALCULATED DECKLE</div>
                                    <div class="fs-5 fw-bold text-success">{{ formatNum(form.deckle_size, 2) }}"</div>
                                </div>
                                <div class="col-md-4 text-center border-end border-secondary">
                                    <div class="small text-gray-400">SHEET LENGTH</div>
                                    <div class="fs-5 fw-bold text-success">{{ formatNum(form.sheet_length, 2) }}"</div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="small text-gray-400">EST. UNIT WEIGHT</div>
                                    <div class="fs-5 fw-bold text-warning">{{ formatNum(form.est_unit_weight, 4) }} kg</div>
                                </div>
                            </div>
                        </div>
                    </el-col>
                </el-row>

                <el-row :gutter="20">
                    <!-- Column 3: Inks & Printing operations -->
                    <el-col :md="12">
                        <div class="form-section p-3 mb-3 border rounded bg-white">
                            <h4 class="h6 fw-bold border-bottom pb-2 mb-3 text-indigo"><i class="bi bi-palette-fill me-1"></i>Aesthetic &amp; Printing Operations</h4>
                            
                            <el-row :gutter="10">
                                <el-col :span="12">
                                    <el-form-item label="Printing Process">
                                        <el-select v-model="form.printing_process" class="w-100">
                                            <el-option label="Flexographic Printing" value="FLEXOGRAPHIC" />
                                            <el-option label="Offset Lithography" value="OFFSET" />
                                            <el-option label="Digital Print" value="DIGITAL" />
                                        </el-select>
                                    </el-form-item>
                                </el-col>
                                <el-col :span="12">
                                    <el-form-item label="Pasting/Closure closure">
                                        <el-select v-model="form.pasting_closure" class="w-100">
                                            <el-option label="Flap Gluing" value="GLUING" />
                                            <el-option label="Wire Stitching" value="STITCHING" />
                                            <el-option label="Glue + Stitch Joint" value="GLUE_STITCH" />
                                        </el-select>
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <el-row :gutter="10">
                                <el-col :span="12">
                                    <el-form-item label="Target Machine Name">
                                        <el-input v-model="form.machine_name" placeholder="e.g. Flexo-Slotter-01" />
                                    </el-form-item>
                                </el-col>
                                <el-col :span="12">
                                    <el-form-item label="Target Speed (PCS/Hour)">
                                        <el-input-number v-model="form.target_speed" :min="0" class="w-100" :controls="false" />
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <el-form-item label="Number of Print Colors">
                                <el-input-number v-model="form.printing_colors_count" :min="0" :max="6" class="w-100" @change="onColorsCountChange" />
                            </el-form-item>

                            <!-- Pantone visual inputs -->
                            <div v-if="form.printing_colors_count > 0" class="mb-3 p-3 bg-light-soft rounded border">
                                <label class="small text-muted fw-bold d-block mb-2">Pantone shade codes matched to color passes</label>
                                <div class="row g-2">
                                    <div class="col-md-6" v-for="index in form.printing_colors_count" :key="index">
                                        <el-input v-model="form.pantone_colors[index-1]" :placeholder="'Color Pass ' + index + ' (e.g. Pantone Red 032)'" size="small" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </el-col>

                    <!-- Column 4: Special Add-ons & instructions -->
                    <el-col :md="12">
                        <div class="form-section p-3 mb-3 border rounded bg-white">
                            <h4 class="h6 fw-bold border-bottom pb-2 mb-3 text-indigo"><i class="bi bi-box-seam me-1"></i>Special Packaging Add-ons</h4>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="p-2 border rounded d-flex justify-content-between align-items-center bg-light-soft">
                                        <div>
                                            <span class="small fw-bold d-block">Inner Honeycomb Cushioning</span>
                                            <span class="text-muted xs-text">Insert high strength honeycomb blocks</span>
                                        </div>
                                        <el-switch v-model="form.special_details.honeycomb" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 border rounded d-flex justify-content-between align-items-center bg-light-soft">
                                        <div>
                                            <span class="small fw-bold d-block">Inner Sheet Separators</span>
                                            <span class="text-muted xs-text">Include divider slots inside standard carton</span>
                                        </div>
                                        <el-switch v-model="form.special_details.separators" />
                                    </div>
                                </div>
                            </div>

                            <!-- Raw materials mapping -->
                            <div class="section-title mt-3 mb-2 small fw-bold text-secondary text-uppercase border-bottom pb-1">Raw Material Allocation (BOM)</div>
                            <el-table :data="form.items" border size="small" class="mb-3">
                                <el-table-column label="Allocated Material Roll/Flute" min-width="250">
                                    <template #default="scope">
                                        <el-select v-model="scope.row.rm_item_id" placeholder="Select Material" class="w-100" filterable @change="onRMItemChange(scope.row)">
                                            <el-option v-for="i in rmItems" :key="i.id" :label="i.name" :value="i.id" />
                                        </el-select>
                                    </template>
                                </el-table-column>
                                <el-table-column label="Required Qty" width="150">
                                    <template #default="scope">
                                        <el-input-number v-model="scope.row.required_qty" :min="0" class="w-100" :controls="false" />
                                    </template>
                                </el-table-column>
                                <el-table-column prop="unit" label="Unit" width="80" />
                                <el-table-column label="" width="50">
                                    <template #default="scope">
                                        <el-button type="danger" size="small" circle @click="removeItemRow(scope.$index)" plain><i class="bi bi-trash"></i></el-button>
                                    </template>
                                </el-table-column>
                            </el-table>
                            <el-button type="primary" size="small" @click="addItemRow" plain class="mb-3"><i class="bi bi-plus-lg me-1"></i> Allocate Material</el-button>

                            <el-form-item label="Floor Production & Quality Instructions">
                                <el-input v-model="form.notes" type="textarea" rows="3" placeholder="Enter custom cautions, tolerances, pasting speeds, slotting matrix details..." />
                            </el-form-item>
                        </div>
                    </el-col>
                </el-row>
            </el-form>
            <template #footer>
                <el-button @click="createDialogVisible = false">Close Specs</el-button>
                <el-button type="primary" class="btn-indigo" @click="submitCreateForm" :loading="submitting">Generate Manufacturing Blueprint</el-button>
            </template>
        </el-dialog>

        <!-- Record daily production popup -->
        <el-dialog v-model="prodDialogVisible" title="Record Production Log" width="500px" destroy-on-close>
            <el-form :model="prodForm" :rules="prodRules" ref="prodFormRef" label-position="top" v-if="selectedJob">
                <div class="mb-3 p-3 bg-light rounded border">
                    <div class="fw-bold text-indigo">{{ selectedJob.job_card_no }}</div>
                    <div class="small text-muted">{{ selectedJob.product?.item_name }}</div>
                </div>

                <el-form-item label="Process Step" prop="job_card_step_id">
                    <el-select v-model="prodForm.job_card_step_id" placeholder="Select Step" class="w-100">
                        <el-option v-for="step in selectedJob.steps" :key="step.id" :label="`${step.step_name} (Produced: ${formatQty(step.produced_qty)})`" :value="step.id" />
                    </el-select>
                </el-form-item>

                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="Production Date" prop="date">
                            <el-date-picker v-model="prodForm.date" type="date" class="w-100" format="DD/MM/YYYY" value-format="YYYY-MM-DD" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="Shift" prop="shift">
                            <el-select v-model="prodForm.shift" class="w-100">
                                <el-option label="Day" value="Day" />
                                <el-option label="Night" value="Night" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>

                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="Quantity Produced" prop="quantity">
                            <el-input-number v-model="prodForm.quantity" :min="0" class="w-100" :controls="false" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="Wastage" prop="wastage">
                            <el-input-number v-model="prodForm.wastage" :min="0" class="w-100" :controls="false" />
                        </el-form-item>
                    </el-col>
                </el-row>

                <el-form-item label="Operator Name">
                    <el-input v-model="prodForm.operator_name" placeholder="Name of operator/worker" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="prodDialogVisible = false">Cancel</el-button>
                <el-button type="success" @click="submitProdForm" :loading="submitting">Save Entry</el-button>
            </template>
        </el-dialog>

        <!-- View Specifications dialog -->
        <el-dialog v-model="detailsDialogVisible" width="950px" title="Manufacturing Spec Sheet Detailed View">
            <div v-if="selectedJobFull" class="job-details-view" id="printableJobCard">
                <el-descriptions border title="Basic Specs Metadata" :column="3">
                    <el-descriptions-item label="Spec Code">{{ selectedJobFull.job_card_no }}</el-descriptions-item>
                    <el-descriptions-item label="Customer">{{ selectedJobFull.customer?.name }}</el-descriptions-item>
                    <el-descriptions-item label="Spec Status">
                        <el-tag :type="getStatusTag(selectedJobFull.status)" size="small">{{ selectedJobFull.status }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="FG Item Name" :span="2">{{ selectedJobFull.product?.item_name }}</el-descriptions-item>
                    <el-descriptions-item label="Outer Box Size">
                        {{ selectedJobFull.length_mm }}x{{ selectedJobFull.width_mm }}x{{ selectedJobFull.height_mm }} mm
                    </el-descriptions-item>
                    <el-descriptions-item label="Deckle size">{{ selectedJobFull.deckle_size }}"</el-descriptions-item>
                    <el-descriptions-item label="Sheet length">{{ selectedJobFull.sheet_length }}"</el-descriptions-item>
                    <el-descriptions-item label="Est. Unit Weight">{{ selectedJobFull.est_unit_weight }} kg</el-descriptions-item>
                </el-descriptions>

                <!-- Dynamic Ply layers table inside detailed view -->
                <div class="mt-4" v-if="selectedJobFull.layers && selectedJobFull.layers.length > 0">
                    <div class="fw-bold mb-2 text-indigo"><i class="bi bi-layers-half me-1"></i>Ply Composition Table (Single-Piece Structure)</div>
                    <el-table :data="selectedJobFull.layers" border size="small">
                        <el-table-column prop="layer_type" label="Ply Layer Type" />
                        <el-table-column prop="paper_name" label="Substrate Grade" />
                        <el-table-column prop="gsm" label="GSM" width="100" align="right" />
                        <el-table-column prop="flute_profile" label="Flute wave Profile" width="140" />
                    </el-table>
                </div>

                <!-- Pieces details -->
                <div class="mt-4" v-if="selectedJobFull.pieces && selectedJobFull.pieces.length > 0">
                    <div class="fw-bold mb-2 text-indigo"><i class="bi bi-puzzle me-1"></i>Multi-Piece Configuration</div>
                    <div v-for="piece in selectedJobFull.pieces" :key="piece.id" class="mb-3 p-3 border rounded bg-light-soft shadow-xs">
                        <div class="fw-bold border-bottom pb-1 mb-2 d-flex justify-content-between small">
                            <span>Piece: {{ piece.piece_name }}</span>
                            <span>Dimensions: {{ piece.length_mm }}x{{ piece.width_mm }}x{{ piece.height_mm }} mm | Deckle: {{ piece.deckle_size }}" | Length: {{ piece.sheet_length }}"</span>
                        </div>
                        <el-table :data="piece.layers" border size="small">
                            <el-table-column prop="layer_type" label="Ply Type" />
                            <el-table-column prop="paper_name" label="Paper Grade" />
                            <el-table-column prop="gsm" label="GSM" width="100" align="right" />
                            <el-table-column prop="flute_profile" label="Flute wave" width="140" />
                        </el-table>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="fw-bold mb-2 text-indigo"><i class="bi bi-activity me-1"></i>Process Progress Tracking</div>
                        <el-table :data="selectedJobFull.steps" border size="small">
                            <el-table-column prop="step_name" label="Step" />
                            <el-table-column prop="status" label="Status" width="100">
                                <template #default="scope">
                                    <el-tag :type="scope.row.status === 'Completed' ? 'success' : 'warning'" size="small">{{ scope.row.status }}</el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column prop="produced_qty" label="Produced" width="100" align="right" />
                            <el-table-column label="%" width="120">
                                <template #default="scope">
                                    <el-progress :percentage="Math.min(100, Math.round((scope.row.produced_qty / selectedJobFull.planned_qty) * 100))" />
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-bold mb-2 text-indigo"><i class="bi bi-cart4 me-1"></i>Material BOM Requirements</div>
                        <el-table :data="selectedJobFull.items" border size="small">
                            <el-table-column prop="item.name" label="Material Roll" />
                            <el-table-column prop="required_qty" label="Required" width="100" align="right" />
                            <el-table-column prop="consumed_qty" label="Consumed" width="100" align="right" class-name="text-danger fw-bold" />
                        </el-table>
                    </div>
                </div>
            </div>
            <template #footer>
                <el-button @click="detailsDialogVisible = false">Close</el-button>
                <el-button type="primary" class="btn-indigo animate-gradient" @click="printDirectly(selectedJobFull.id)"><i class="bi bi-printer me-1"></i> Print Spec Sheet Blueprint</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const jobCards = ref([]);
const customers = ref([]);
const products = ref([]);
const rmItems = ref([]);
const loading = ref(false);
const submitting = ref(false);
const createDialogVisible = ref(false);
const prodDialogVisible = ref(false);
const detailsDialogVisible = ref(false);
const selectedJob = ref(null);
const selectedJobFull = ref(null);
const formRef = ref(null);
const prodFormRef = ref(null);

const selectedPlyStructure = ref('3');
const activePieceTab = ref('piece_0');

const filters = reactive({
    search: '',
    customer_id: null,
    status: ''
});

const pagination = reactive({
    current_page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    job_card_no: '',
    customer_id: null,
    fg_product_id: null,
    planned_qty: 1,
    planned_date: new Date().toISOString().substr(0, 10),
    delivery_date: null,
    specifications: '',
    notes: '',
    items: [],
    
    // Spec fields
    length_mm: 0,
    width_mm: 0,
    height_mm: 0,
    uom: 'mm',
    deckle_size: 0,
    sheet_length: 0,
    ups: 1,
    carton_type: 'FEFCO 0201',
    machine_name: '',
    target_speed: 0,
    printing_process: 'FLEXOGRAPHIC',
    pasting_closure: 'GLUING',
    printing_colors_count: 0,
    pantone_colors: [],
    special_details: { honeycomb: false, separators: false },
    pieces_count: 1,
    est_unit_weight: 0,
    layers: [],
    pieces: []
});

const prodForm = reactive({
    job_card_id: null,
    job_card_step_id: null,
    date: new Date().toISOString().substr(0, 10),
    shift: 'Day',
    machine_no: '',
    quantity: 0,
    wastage: 0,
    operator_name: '',
    remarks: ''
});

const rules = {
    customer_id: [{ required: true, message: 'Select customer', trigger: 'change' }],
    fg_product_id: [{ required: true, message: 'Select product', trigger: 'change' }],
    planned_qty: [{ required: true, message: 'Enter planned qty', trigger: 'blur' }],
    planned_date: [{ required: true, message: 'Select start date', trigger: 'change' }]
};

const prodRules = {
    job_card_step_id: [{ required: true, message: 'Select step', trigger: 'change' }],
    date: [{ required: true, message: 'Select date', trigger: 'change' }],
    quantity: [{ required: true, message: 'Enter quantity', trigger: 'blur' }]
};

const filteredProducts = computed(() => {
    if (!form.customer_id) return [];
    return products.value.filter(p => p.customer_id === form.customer_id);
});

const fetchJobCards = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/job-cards', { params: { ...filters, page: pagination.current_page } });
        jobCards.value = res.data.data;
        pagination.total = res.data.total;
    } catch (error) {
        ElMessage.error('Failed to fetch job cards');
    } finally {
        loading.value = false;
    }
};

const fetchData = async () => {
    try {
        const [cRes, pRes, rmRes] = await Promise.all([
            axios.get('/api/customers'),
            axios.get('/api/products'),
            axios.get('/api/rm-items')
        ]);
        customers.value = cRes.data;
        products.value = pRes.data.data || pRes.data;
        rmItems.value = rmRes.data;
    } catch (error) {}
};

const resetFilters = () => {
    filters.search = '';
    filters.customer_id = null;
    filters.status = '';
    fetchJobCards();
};

const openCreateDialog = () => {
    Object.assign(form, {
        job_card_no: '',
        customer_id: null,
        fg_product_id: null,
        planned_qty: 1,
        planned_date: new Date().toISOString().substr(0, 10),
        delivery_date: null,
        specifications: '',
        notes: '',
        items: [],
        length_mm: 0,
        width_mm: 0,
        height_mm: 0,
        uom: 'mm',
        deckle_size: 0,
        sheet_length: 0,
        ups: 1,
        carton_type: 'FEFCO 0201',
        machine_name: '',
        target_speed: 0,
        printing_process: 'FLEXOGRAPHIC',
        pasting_closure: 'GLUING',
        printing_colors_count: 0,
        pantone_colors: [],
        special_details: { honeycomb: false, separators: false },
        pieces_count: 1,
        est_unit_weight: 0,
        layers: [],
        pieces: []
    });
    selectedPlyStructure.value = '3';
    onPlyCountChange();
    createDialogVisible.value = true;
};

// Handle dynamic structural layers pre-population
const onPlyCountChange = () => {
    if (form.pieces_count !== 1) return;
    const count = parseInt(selectedPlyStructure.value);
    const layers = [];
    if (count === 3) {
        layers.push({ layer_type: 'Liner 1', paper_name: 'Kraft', gsm: 125, flute_profile: 'Flat' });
        layers.push({ layer_type: 'Fluting 1', paper_name: 'Fluting Medium', gsm: 120, flute_profile: 'B' });
        layers.push({ layer_type: 'Liner 2', paper_name: 'Testliner', gsm: 125, flute_profile: 'Flat' });
    } else if (count === 5) {
        layers.push({ layer_type: 'Liner 1', paper_name: 'Kraft', gsm: 125, flute_profile: 'Flat' });
        layers.push({ layer_type: 'Fluting 1', paper_name: 'Fluting Medium', gsm: 120, flute_profile: 'B' });
        layers.push({ layer_type: 'Liner 2', paper_name: 'Testliner', gsm: 125, flute_profile: 'Flat' });
        layers.push({ layer_type: 'Fluting 2', paper_name: 'Fluting Medium', gsm: 120, flute_profile: 'C' });
        layers.push({ layer_type: 'Liner 3', paper_name: 'Craft', gsm: 125, flute_profile: 'Flat' });
    } else if (count === 7) {
        layers.push({ layer_type: 'Liner 1', paper_name: 'Kraft', gsm: 125, flute_profile: 'Flat' });
        layers.push({ layer_type: 'Fluting 1', paper_name: 'Fluting Medium', gsm: 120, flute_profile: 'B' });
        layers.push({ layer_type: 'Liner 2', paper_name: 'Testliner', gsm: 125, flute_profile: 'Flat' });
        layers.push({ layer_type: 'Fluting 2', paper_name: 'Fluting Medium', gsm: 120, flute_profile: 'C' });
        layers.push({ layer_type: 'Liner 3', paper_name: 'Craft', gsm: 125, flute_profile: 'Flat' });
        layers.push({ layer_type: 'Fluting 3', paper_name: 'Fluting Medium', gsm: 120, flute_profile: 'E' });
        layers.push({ layer_type: 'Liner 4', paper_name: 'Testliner', gsm: 125, flute_profile: 'Flat' });
    }
    form.layers = layers;
    recalculateSpecs();
};

const onPiecesCountChange = () => {
    if (form.pieces_count === 1) {
        form.pieces = [];
        onPlyCountChange();
    } else {
        form.layers = [];
        const pieces = [];
        for (let i = 0; i < form.pieces_count; i++) {
            const pieceLayers = [
                { layer_type: 'Liner 1', paper_name: 'Kraft', gsm: 125, flute_profile: 'Flat' },
                { layer_type: 'Fluting 1', paper_name: 'Fluting', gsm: 120, flute_profile: 'B' },
                { layer_type: 'Liner 2', paper_name: 'Testliner', gsm: 125, flute_profile: 'Flat' }
            ];
            pieces.push({
                piece_name: `Piece Component ${String.fromCharCode(65 + i)}`,
                length_mm: form.length_mm,
                width_mm: form.width_mm,
                height_mm: form.height_mm,
                deckle_size: 0,
                sheet_length: 0,
                ups: 1,
                machine_name: '',
                target_speed: 0,
                est_unit_weight: 0,
                instructions: '',
                layers: pieceLayers
            });
        }
        form.pieces = pieces;
        activePieceTab.value = 'piece_0';
        recalculateSpecs();
    }
};

// Dynamic color matching dropdowns
const onColorsCountChange = () => {
    const current = form.pantone_colors.length;
    if (form.printing_colors_count > current) {
        for (let i = current; i < form.printing_colors_count; i++) {
            form.pantone_colors.push('');
        }
    } else {
        form.pantone_colors = form.pantone_colors.slice(0, form.printing_colors_count);
    }
};

// Calculations Engine
const recalculateSpecs = () => {
    const fluteFactors = { 'Flat': 1.0, 'B': 1.35, 'C': 1.45, 'E': 1.25 };

    const calculateWeight = (l_mm, w_mm, h_mm, layersArray) => {
        // Metric conversions
        let length_mm = l_mm || 0;
        let width_mm = w_mm || 0;
        let height_mm = h_mm || 0;

        if (form.uom === 'inch') {
            length_mm = length_mm * 25.4;
            width_mm = width_mm * 25.4;
            height_mm = height_mm * 25.4;
        } else if (form.uom === 'cm') {
            length_mm = length_mm * 10;
            width_mm = width_mm * 10;
            height_mm = height_mm * 10;
        }

        // 1. Calculated Deckle in inches
        const deckle_in = (width_mm + height_mm + 25) / 25.4;

        // 2. Calculated Sheet Length in inches
        const sheet_length_in = ((length_mm * 2) + (width_mm * 2) + 75) / 25.4;

        // 3. Sum of GSM * factors
        let sumGsm = 0;
        layersArray.forEach(l => {
            const factor = fluteFactors[l.flute_profile] || 1.0;
            sumGsm += (l.gsm || 0) * factor;
        });

        // 4. Area M2
        const area_m2 = deckle_in * sheet_length_in * 0.00064516;

        // 5. Weight in KG (includes 6% trim waste deduction)
        const net_weight = ((area_m2 * sumGsm) / 1000) * 0.94;

        return {
            deckle: deckle_in,
            sheet_length: sheet_length_in,
            weight: net_weight
        };
    };

    if (form.pieces_count === 1) {
        const result = calculateWeight(form.length_mm, form.width_mm, form.height_mm, form.layers);
        form.deckle_size = result.deckle;
        form.sheet_length = result.sheet_length;
        form.est_unit_weight = result.weight / (form.ups || 1);
    } else {
        let totalWeight = 0;
        form.pieces.forEach(p => {
            const result = calculateWeight(p.length_mm, p.width_mm, p.height_mm, p.layers);
            p.deckle_size = result.deckle;
            p.sheet_length = result.sheet_length;
            p.est_unit_weight = result.weight / (p.ups || 1);
            totalWeight += p.est_unit_weight;
        });
        form.est_unit_weight = totalWeight;
        
        // Also update main deckle/sheet length for summary displays to show tab 0 specs
        if (form.pieces[0]) {
            form.deckle_size = form.pieces[0].deckle_size;
            form.sheet_length = form.pieces[0].sheet_length;
        }
    }
};

const addItemRow = () => {
    form.items.push({ rm_item_id: null, required_qty: 0, unit: '' });
};

const removeItemRow = (index) => {
    form.items.splice(index, 1);
};

const onRMItemChange = (row) => {
    const item = rmItems.value.find(i => i.id === row.rm_item_id);
    if (item) row.unit = item.unit_type;
};

const onCustomerChange = () => {
    form.fg_product_id = null;
};

const submitCreateForm = async () => {
    if (!formRef.value) return;
    await formRef.value.validate(async (valid) => {
        if (!valid) return;
        submitting.value = true;
        try {
            await axios.post('/api/job-cards', form);
            ElMessage.success('Job Card manufacturing specs created successfully');
            createDialogVisible.value = false;
            fetchJobCards();
        } catch (error) {
            ElMessage.error(error.response?.data?.error || 'Failed to create job card');
        } finally {
            submitting.value = false;
        }
    });
};

const openProductionDialog = (job) => {
    selectedJob.value = job;
    Object.assign(prodForm, {
        job_card_id: job.id,
        job_card_step_id: null,
        date: new Date().toISOString().substr(0, 10),
        shift: 'Day',
        machine_no: '',
        quantity: 0,
        wastage: 0,
        operator_name: '',
        remarks: ''
    });
    prodDialogVisible.value = true;
};

const submitProdForm = async () => {
    if (!prodFormRef.value) return;
    await prodFormRef.value.validate(async (valid) => {
        if (!valid) return;
        submitting.value = true;
        try {
            await axios.post('/api/job-cards/record-production', prodForm);
            ElMessage.success('Production log recorded successfully');
            prodDialogVisible.value = false;
            fetchJobCards();
        } catch (error) {
            ElMessage.error(error.response?.data?.error || 'Failed to record production');
        } finally {
            submitting.value = false;
        }
    });
};

const viewDetails = async (job) => {
    try {
        const res = await axios.get(`/api/job-cards/${job.id}`);
        selectedJobFull.value = res.data;
        detailsDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('Failed to load job card specifications');
    }
};

const changeStatus = async (job) => {
    try {
        const { value: status } = await ElMessageBox.prompt('Update Status', 'Change Job Card Status', {
            confirmButtonText: 'Update',
            cancelButtonText: 'Cancel',
            inputPattern: /^(Open|In-Progress|Completed|Cancelled)$/,
            inputErrorMessage: 'Status must be Open, In-Progress, Completed, or Cancelled',
            inputValue: job.status
        });
        
        await axios.put(`/api/job-cards/${job.id}/status`, { status });
        ElMessage.success('Status updated successfully');
        fetchJobCards();
    } catch (error) {}
};

const printDirectly = (id) => {
    window.open(`/job-cards/${id}/print`, '_blank');
};

const getStatusTag = (status) => {
    const map = { 'Open': 'info', 'In-Progress': 'primary', 'Completed': 'success', 'Cancelled': 'danger' };
    return map[status] || 'info';
};

const formatQty = (val) => Number(val || 0).toLocaleString();
const formatNum = (val, dec) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec });

onMounted(() => {
    fetchJobCards();
    fetchData();
});
</script>

<style scoped>
.glass-header {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.4);
}
.glass-card {
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 12px;
}
.bg-light-soft {
    background-color: rgba(248, 250, 252, 0.7) !important;
}
.text-indigo {
    color: #4f46e5 !important;
}
.btn-indigo {
    background-color: #4f46e5 !important;
    border-color: #4f46e5 !important;
    color: white !important;
}
.btn-indigo:hover {
    background-color: #4338ca !important;
    border-color: #4338ca !important;
}
.form-section {
    border-color: #e2e8f0 !important;
}
.font-mono {
    font-family: 'Courier New', Courier, monospace;
}
.xs-text {
    font-size: 11px;
}
.modern-table :deep(.el-table__header) th {
    background-color: #f1f5f9 !important;
    color: #334155 !important;
    font-weight: 700 !important;
}
.piece-tabs :deep(.el-tabs__item.is-active) {
    color: #4f46e5 !important;
    font-weight: bold;
}
.piece-tabs :deep(.el-tabs__active-bar) {
    background-color: #4f46e5 !important;
}
</style>
