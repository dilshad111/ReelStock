<template>
    <div class="job-card-management p-3">
        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-3 p-3 glass-header rounded shadow-sm">
            <div>
                <h2 class="h4 mb-1 fw-bold text-dark">
                    <i class="bi bi-file-earmark-ruled-fill text-indigo me-2"></i>{{ headerTitle }}
                </h2>
                <p class="small text-muted mb-0">{{ headerSubtitle }}</p>
            </div>
            <div class="job-header-actions">
                <template v-if="isCreating">
                    <el-button @click="discardCreateForm">Discard</el-button>
                    <el-button type="primary" class="btn-indigo" @click="submitCreateForm" :loading="submitting">
                        <i class="bi bi-check2-circle me-1"></i>{{ submitButtonText }}
                    </el-button>
                </template>
                <el-button v-else type="primary" class="btn-indigo shadow-sm" @click="openCreateForm">
                    <i class="bi bi-plus-circle me-1"></i>Create New Job Card
                </el-button>
            </div>
        </div>

        <!-- Main Glassmorphic Listing Panel -->
        <div v-if="!isCreating" class="glass-card shadow-sm p-3 mb-3">
            <!-- Filter Bar -->
            <div class="row g-3 mb-3 filter-container p-3 rounded border bg-light-soft">
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
                    <label class="small text-muted fw-bold mb-1">Cartons Category</label>
                    <el-select v-model="filters.carton_category" placeholder="All Categories" clearable @change="fetchJobCards" class="w-100">
                        <el-option v-for="category in cartonCategories" :key="category.value" :label="category.label" :value="category.value" />
                    </el-select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <el-button @click="resetFilters" class="w-100 btn-clear-filters"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</el-button>
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
                <el-table-column label="Cartons Category" width="150">
                    <template #default="scope">
                        <span class="fw-bold">{{ cartonCategoryLabel(scope.row) }}</span>
                    </template>
                </el-table-column>
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
                <el-table-column label="Version" width="115" align="center">
                    <template #default="scope">
                        <span class="job-version-badge">{{ currentVersionLabel(scope.row) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="Actions" width="230" align="center">
                    <template #default="scope">
                        <el-button-group>
                            <el-button type="info" size="small" @click="viewDetails(scope.row)" title="View Specifications" circle>
                                <i class="bi bi-eye"></i>
                            </el-button>
                            <el-button type="primary" size="small" @click="openEditForm(scope.row)" title="Edit Current Version" circle class="btn-indigo">
                                <i class="bi bi-pencil"></i>
                            </el-button>
                            <el-button type="primary" size="small" @click="printDirectly(scope.row.id)" title="Print Blueprint" circle class="btn-indigo">
                                <i class="bi bi-printer"></i>
                            </el-button>
                            <el-button type="danger" size="small" @click="openChangeRequest(scope.row)" title="Change Request" circle>
                                <i class="bi bi-pencil-square"></i>
                            </el-button>
                            <el-button type="danger" size="small" @click="deleteJobCard(scope.row)" title="Delete Job Card" circle plain>
                                <i class="bi bi-trash"></i>
                            </el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>

            <div class="d-flex justify-content-center mt-4">
                <el-pagination v-model:current-page="pagination.current_page" :page-size="pagination.per_page" layout="prev, pager, next" :total="pagination.total" @current-change="fetchJobCards" />
            </div>
        </div>

        <div v-else class="job-create-workspace">
            <el-form :model="createForm" :rules="createRules" ref="createFormRef" label-position="top" class="job-card-form">
                <div class="job-form-grid">
                    <section class="job-form-card span-wide basic-info-card">
                        <div class="section-heading">
                            <div>
                                <h3>Basic Information</h3>
                                <p>Identity &amp; Dimensions</p>
                            </div>
                            <div class="basic-info-heading-right">
                                <div class="job-number-pill">{{ nextJobCardNo || 'QC-JC-110001' }}</div>
                                <span class="section-index">01</span>
                            </div>
                        </div>

                        <div class="basic-info-layout">
                            <div class="basic-info-fields">
                                <div class="form-grid two-col">
                                    <el-form-item label="Customer" prop="customer_id">
                                        <el-select v-model="createForm.customer_id" placeholder="Select customer" class="w-100" filterable>
                                            <el-option v-for="customer in customers" :key="customer.id" :label="customer.name" :value="customer.id" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Item Code">
                                        <el-input v-model="createForm.item_code" placeholder="Item Code" :disabled="isChangeRequestMode" />
                                    </el-form-item>
                                </div>

                                <el-form-item label="Item Name" prop="item_name">
                                    <el-input v-model="createForm.item_name" placeholder="Name of the carton..." :disabled="isChangeRequestMode" />
                                </el-form-item>

                                <el-alert v-if="isChangeRequestMode" type="warning" show-icon :closable="false" class="mb-3 job-mode-alert">
                                    Item Code and Item Name are locked. Changes will create a new active version under the same Job Card.
                                </el-alert>
                                <el-alert v-else-if="isEditMode" type="info" show-icon :closable="false" class="mb-3 job-mode-alert">
                                    Editing corrects the current active Job Card only. It will not create a change request or a new version.
                                </el-alert>

                                <div class="carton-selection-layout">
                                    <div class="carton-selection-fields">
                                        <el-form-item label="Carton Type" prop="carton_type_id">
                                            <el-select v-model="createForm.carton_type_id" placeholder="Select carton type" class="w-100" filterable @change="onCartonTypeChange">
                                                <el-option v-for="type in cartonTypes" :key="type.id" :label="cartonTypeLabel(type)" :value="type.id" />
                                            </el-select>
                                        </el-form-item>

                                        <el-form-item label="Carton Quality" class="carton-quality-field">
                                            <el-radio-group v-model="createForm.carton_quality" class="segmented-control" @change="fetchAllTargetSpeeds">
                                                <el-radio-button label="normal">Standard</el-radio-button>
                                                <el-radio-button label="high_quality">Premium</el-radio-button>
                                            </el-radio-group>
                                        </el-form-item>
                                    </div>

                                    <div class="carton-preview-panel">
                                        <div class="preview-panel-header">
                                            <span>Carton Preview</span>
                                            <strong>{{ selectedCartonType?.standard_code || 'FEFCO' }}</strong>
                                        </div>
                                        <div class="carton-preview-stage">
                                            <img v-if="selectedCartonType?.preview_image && !cartonPreviewFailed" :src="selectedCartonType.preview_image" :alt="cartonTypeLabel(selectedCartonType)" @error="cartonPreviewFailed = true">
                                            <div v-else class="carton-preview-fallback">
                                                {{ selectedCartonType ? selectedCartonType.standard_code : 'Select Type' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dieline-panel">
                                <div class="preview-panel-header dieline-header">
                                    <div>
                                        <span class="mini-label">Die-line Structure</span>
                                        <strong>{{ selectedCartonType ? cartonTypeLabel(selectedCartonType) : 'Carton layout preview' }}</strong>
                                    </div>
                                    <el-button size="small" type="primary" plain @click="downloadDieLine">
                                        <i class="bi bi-download me-1"></i>JPEG
                                    </el-button>
                                </div>
                                <div v-if="hasDimensions" class="dieline-canvas" v-html="formDieLineSvg"></div>
                                <div v-else class="dieline-empty">Enter length, width, and height to preview die-line.</div>
                            </div>
                        </div>

                        <div class="sizing-section">
                            <div class="dimension-panel">
                                <div class="form-grid four-col dimension-input-row">
                                    <el-form-item label="Cartons Category">
                                        <el-select v-model="createForm.carton_category" class="w-100">
                                            <el-option label="RSC Carton" value="RSC Carton" />
                                            <el-option label="Top & Bottom Carton" value="Top & Bottom Carton" />
                                            <el-option label="Separator" value="Separator" />
                                            <el-option label="Honeycomb" value="Honeycomb" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="UOM">
                                        <el-select v-model="createForm.uom" class="w-100" @change="recalculateSpecs">
                                            <el-option label="Millimeters" value="mm" />
                                            <el-option label="Inches" value="inch" />
                                            <el-option label="Centimeters" value="cm" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Length">
                                        <el-input-number v-model="createForm.length" :min="0" :precision="2" class="w-100" :controls="false" @input="recalculateSpecs" />
                                    </el-form-item>
                                    <el-form-item label="Width">
                                        <el-input-number v-model="createForm.width" :min="0" :precision="2" class="w-100" :controls="false" @input="recalculateSpecs" />
                                    </el-form-item>
                                    <el-form-item label="Height">
                                        <el-input-number v-model="createForm.height" :min="0" :precision="2" class="w-100" :controls="false" @input="recalculateSpecs" />
                                    </el-form-item>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="job-form-card span-wide">
                        <div class="section-heading">
                            <div>
                                <h3>Carton Configuration</h3>
                                <p>Structure &amp; Routing</p>
                            </div>
                            <span class="section-index">02</span>
                        </div>

                        <div class="form-grid four-col piece-config-row">
                            <el-form-item label="Pieces Count" prop="pieces_count">
                                <el-select v-model="createForm.pieces_count" class="w-100" @change="onPiecesCountChange">
                                    <el-option label="Monolithic (1-Piece)" :value="1" />
                                    <el-option label="Dual Component (2-Pc)" :value="2" />
                                    <el-option label="Multi-Piece (3+ Pc)" :value="3" />
                                </el-select>
                            </el-form-item>
                            <el-form-item v-if="createForm.pieces_count === 1" label="Deckle Specification (in)">
                                <el-input-number v-model="createForm.deckle_size" :min="0" :precision="2" class="w-100" :controls="false" />
                            </el-form-item>
                            <el-form-item v-if="createForm.pieces_count === 1" label="Cut Length / Sheet Length (in)">
                                <el-input-number v-model="createForm.sheet_length" :min="0" :precision="2" class="w-100" :controls="false" />
                            </el-form-item>
                            <el-form-item v-if="createForm.pieces_count === 1" label="Units Per Sheet (UPS)">
                                <el-input-number v-model="createForm.ups" :min="1" class="w-100" :controls="false" @input="recalculateSpecs" />
                            </el-form-item>
                        </div>

                        <div v-if="createForm.pieces_count === 1">
                            <div class="form-grid three-col">
                                <el-form-item label="Ply Type" prop="ply_type">
                                    <el-select v-model="createForm.ply_type" class="w-100" @change="onPlyTypeChange">
                                        <el-option label="3-Ply" :value="3" />
                                        <el-option label="5-Ply" :value="5" />
                                        <el-option label="7-Ply" :value="7" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="Initial Sizing">
                                    <el-select v-model="createForm.slitting_creasing" class="w-100">
                                        <el-option label="Inline Plant Slit" value="Plant Online" />
                                        <el-option label="Secondary Manual" value="Manual" />
                                        <el-option label="Precision Die-Cut" value="Die Cutting" />
                                    </el-select>
                                </el-form-item>
                            </div>
                            <div class="table-wrap">
                                <table class="construction-table">
                                    <thead>
                                        <tr>
                                            <th>Layer</th>
                                            <th>Paper Construction</th>
                                            <th>GSM</th>
                                            <th>Flute Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(layer, index) in createForm.layers" :key="`${layer.layer_type}-${index}`">
                                            <td><strong>{{ layer.layer_type }}</strong></td>
                                            <td>
                                                <el-select v-model="layer.paper_id" placeholder="Select paper" class="w-100" filterable @change="paperId => onPaperChange(layer, paperId)">
                                                    <el-option v-for="paper in papers" :key="paper.id" :label="paperLabel(paper)" :value="paper.id" />
                                                </el-select>
                                            </td>
                                            <td><el-input-number v-model="layer.gsm" :min="0" class="w-100" :controls="false" @input="recalculateSpecs" /></td>
                                            <td>
                                                <el-select v-model="layer.flute_profile" class="w-100" :disabled="!isFluteLayer(layer)" @change="recalculateSpecs">
                                                    <el-option label="B-Flute" value="B" />
                                                    <el-option label="C-Flute" value="C" />
                                                    <el-option label="E-Flute" value="E" />
                                                    <el-option label="Flat Liner" value="Flat" />
                                                </el-select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <el-tabs v-else v-model="activePieceTab" class="component-tabs" type="border-card">
                            <el-tab-pane v-for="(piece, pieceIndex) in createForm.pieces" :key="piece.local_id" :label="piece.piece_name || `Component ${pieceIndex + 1}`" :name="`piece_${pieceIndex}`">
                                <div class="form-grid four-col component-fields-grid">
                                    <el-form-item label="Component Designation">
                                        <el-input v-model="piece.piece_name" />
                                    </el-form-item>
                                    <el-form-item label="Length">
                                        <el-input-number v-model="piece.length" :min="0" :precision="2" class="w-100" :controls="false" @input="recalculatePieceSpecs(piece)" />
                                    </el-form-item>
                                    <el-form-item label="Width">
                                        <el-input-number v-model="piece.width" :min="0" :precision="2" class="w-100" :controls="false" @input="recalculatePieceSpecs(piece)" />
                                    </el-form-item>
                                    <el-form-item label="Height">
                                        <el-input-number v-model="piece.height" :min="0" :precision="2" class="w-100" :controls="false" @input="recalculatePieceSpecs(piece)" />
                                    </el-form-item>
                                    <el-form-item label="Deckle Size">
                                        <el-input-number v-model="piece.deckle_size" :min="0" :precision="2" class="w-100" :controls="false" />
                                    </el-form-item>
                                    <el-form-item label="Sheet Length">
                                        <el-input-number v-model="piece.sheet_length" :min="0" :precision="2" class="w-100" :controls="false" />
                                    </el-form-item>
                                    <el-form-item label="UPS">
                                        <el-input-number v-model="piece.ups" :min="1" class="w-100" :controls="false" @input="recalculatePieceSpecs(piece)" />
                                    </el-form-item>
                                    <el-form-item label="Ply Type">
                                        <el-select v-model="piece.ply_type" class="w-100" @change="onPiecePlyTypeChange(piece)">
                                            <el-option label="3-Ply" :value="3" />
                                            <el-option label="5-Ply" :value="5" />
                                            <el-option label="7-Ply" :value="7" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Print Colors">
                                        <el-select v-model="piece.print_colors" class="w-100" @change="onPieceColorsChange(piece)">
                                            <el-option label="Un-Printed" :value="0" />
                                            <el-option v-for="n in 6" :key="n" :label="`${n} Color Printing`" :value="n" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Finishing">
                                        <el-select v-model="piece.finishing_protocol" class="w-100">
                                            <el-option label="Rotary Slotter" value="Rotary Slotter" />
                                            <el-option label="Flatbed Die-Cut" value="Die Cutting" />
                                            <el-option label="Rotary Slotter + Flatbed Die-cut" value="Rotary Slotter + Flatbed Die-cut" />
                                        </el-select>
                                    </el-form-item>
                                </div>

                                <div v-if="piece.print_colors > 0" class="ink-grid">
                                    <el-select v-for="inkIndex in piece.print_colors" :key="inkIndex" v-model="piece.printing_data.inks[inkIndex - 1]" :placeholder="`Ink ${inkIndex}`" filterable>
                                        <el-option v-for="ink in printingColors" :key="ink.id" :label="inkLabel(ink)" :value="ink.ink_code">
                                            <span class="ink-option"><span class="ink-swatch" :style="{ backgroundColor: ink.ink_code }"></span>{{ inkLabel(ink) }}</span>
                                        </el-option>
                                    </el-select>
                                </div>

                                <div class="table-wrap">
                                    <table class="construction-table">
                                        <thead>
                                            <tr>
                                                <th>Layer</th>
                                                <th>Paper Construction</th>
                                                <th>GSM</th>
                                                <th>Flute Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(layer, layerIndex) in piece.layers" :key="`${piece.local_id}-${layer.layer_type}-${layerIndex}`">
                                                <td><strong>{{ layer.layer_type }}</strong></td>
                                                <td>
                                                    <el-select v-model="layer.paper_id" placeholder="Select paper" class="w-100" filterable @change="paperId => onPaperChange(layer, paperId, piece)">
                                                        <el-option v-for="paper in papers" :key="paper.id" :label="paperLabel(paper)" :value="paper.id" />
                                                    </el-select>
                                                </td>
                                                <td><el-input-number v-model="layer.gsm" :min="0" class="w-100" :controls="false" @input="recalculatePieceSpecs(piece)" /></td>
                                                <td>
                                                    <el-select v-model="layer.flute_profile" class="w-100" :disabled="!isFluteLayer(layer)" @change="recalculatePieceSpecs(piece)">
                                                        <el-option label="B-Flute" value="B" />
                                                        <el-option label="C-Flute" value="C" />
                                                        <el-option label="E-Flute" value="E" />
                                                        <el-option label="Flat Liner" value="Flat" />
                                                    </el-select>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <el-form-item label="Special Instructions">
                                    <el-input v-model="piece.instructions" type="textarea" :rows="2" placeholder="Component-specific production instruction..." />
                                </el-form-item>
                            </el-tab-pane>
                        </el-tabs>
                    </section>

                    <section v-if="createForm.pieces_count === 1" class="job-form-card">
                        <div class="section-heading">
                            <div>
                                <h3>Printing &amp; Finishing</h3>
                                <p>Ink passes, joinery, profile, and final process</p>
                            </div>
                            <span class="section-index">03</span>
                        </div>
                        <div class="form-grid two-col">
                            <el-form-item label="Printing Colors">
                                <el-select v-model="createForm.print_colors" class="w-100" @change="onPrintColorsChange">
                                    <el-option label="Un-Printed" :value="0" />
                                    <el-option v-for="n in 6" :key="n" :label="`${n} Color Printing`" :value="n" />
                                </el-select>
                            </el-form-item>
                        </div>

                        <div v-if="createForm.print_colors > 0" class="ink-grid mb-3">
                            <el-select v-for="inkIndex in createForm.print_colors" :key="inkIndex" v-model="createForm.printing_data.inks[inkIndex - 1]" :placeholder="`Ink ${inkIndex}`" filterable>
                                <el-option v-for="ink in printingColors" :key="ink.id" :label="inkLabel(ink)" :value="ink.ink_code">
                                    <span class="ink-option"><span class="ink-swatch" :style="{ backgroundColor: ink.ink_code }"></span>{{ inkLabel(ink) }}</span>
                                </el-option>
                            </el-select>
                        </div>

                        <div class="form-grid two-col">
                            <el-form-item label="Joinery Technique" prop="pasting_type">
                                <el-select v-model="createForm.pasting_type" class="w-100">
                                    <el-option label="N/A" value="None" />
                                    <el-option label="Adhesive Glue" value="Glue" />
                                    <el-option label="Industrial Staple" value="Staple" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="Profile Complexity">
                                <el-select v-model="createForm.slot_type" class="w-100" @change="fetchAllTargetSpeeds">
                                    <el-option label="Universal/Simple" value="Simple" />
                                    <el-option label="Custom/Complex" value="Complex" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="Finishing Protocol">
                                <el-select v-model="createForm.process_type" class="w-100">
                                    <el-option label="Rotary Slotter" value="Rotary Slotter" />
                                    <el-option label="Flatbed Die-Cut" value="Die Cutting" />
                                    <el-option label="Rotary Slotter + Flatbed Die-cut" value="Rotary Slotter + Flatbed Die-cut" />
                                </el-select>
                            </el-form-item>
                            <el-form-item v-if="createForm.process_type === 'Die Cutting'" label="Die Cutting Machine">
                                <el-select v-model="createForm.die_cutting_machine_id" class="w-100" filterable @change="fetchDieCuttingSpeed">
                                    <el-option v-for="machine in dieCuttingMachines" :key="machine.id" :label="machineLabel(machine)" :value="machine.id" />
                                </el-select>
                                <div v-if="createForm.die_cutting_speed" class="target-speed">Target: {{ createForm.die_cutting_speed }} sh/min</div>
                            </el-form-item>
                            <el-form-item v-if="createForm.process_type === 'Die Cutting'" label="Scope / Method">
                                <el-select v-model="createForm.die_cutting_scope" class="w-100">
                                    <el-option label="Complete Sheet (Whole)" value="sheet_wise" />
                                    <el-option label="Single Piece Carton (Individual)" value="carton_wise" />
                                </el-select>
                            </el-form-item>
                        </div>
                    </section>

                    <section class="job-form-card">
                        <div class="section-heading">
                            <div>
                                <h3>Special Add-ons</h3>
                                <p>Honeycomb &amp; Separators</p>
                            </div>
                            <span class="section-index">04</span>
                        </div>

                        <div class="addon-toggle">
                            <div>
                                <strong>Honeycomb</strong>
                                <span>{{ createForm.special_details.honeycomb.enabled ? 'Include' : 'Disabled' }}</span>
                            </div>
                            <el-switch v-model="createForm.special_details.honeycomb.enabled" />
                        </div>
                        <div v-if="createForm.special_details.honeycomb.enabled" class="form-grid three-col compact-grid">
                            <el-select v-model="createForm.special_details.honeycomb.unit" placeholder="Unit">
                                <el-option label="mm" value="mm" />
                                <el-option label="inch" value="inch" />
                            </el-select>
                            <el-input-number v-model="createForm.special_details.honeycomb.length" :min="0" :controls="false" placeholder="Length" />
                            <el-input-number v-model="createForm.special_details.honeycomb.width" :min="0" :controls="false" placeholder="Width" />
                            <el-input-number v-model="createForm.special_details.honeycomb.height" :min="0" :controls="false" placeholder="Height" />
                            <el-input-number v-model="createForm.special_details.honeycomb.holes" :min="0" :controls="false" placeholder="Holes" />
                            <el-select v-model="createForm.special_details.honeycomb.ply" placeholder="Ply">
                                <el-option label="3-Ply" :value="3" />
                                <el-option label="5-Ply" :value="5" />
                                <el-option label="7-Ply" :value="7" />
                            </el-select>
                            <el-input v-model="createForm.special_details.honeycomb.material" placeholder="Material" />
                            <el-select v-model="createForm.special_details.honeycomb.source" placeholder="Source">
                                <el-option label="In-House Manufacture" value="inhouse" />
                                <el-option label="Outsource Purchase" value="outsource" />
                            </el-select>
                            <el-input v-if="createForm.special_details.honeycomb.source === 'outsource'" v-model="createForm.special_details.honeycomb.supplier_name" placeholder="Supplier Name" />
                            <el-input v-if="createForm.special_details.honeycomb.source === 'inhouse'" v-model="createForm.special_details.honeycomb.inhouse_details" placeholder="Packing" />
                        </div>

                        <div class="addon-toggle mt-3">
                            <div>
                                <strong>Carton Separator</strong>
                                <span>{{ createForm.special_details.separator.enabled ? 'Include' : 'Disabled' }}</span>
                            </div>
                            <el-switch v-model="createForm.special_details.separator.enabled" />
                        </div>
                        <div v-if="createForm.special_details.separator.enabled" class="form-grid three-col compact-grid">
                            <el-select v-model="createForm.special_details.separator.unit" placeholder="Unit">
                                <el-option label="mm" value="mm" />
                                <el-option label="inch" value="inch" />
                            </el-select>
                            <el-input-number v-model="createForm.special_details.separator.length" :min="0" :controls="false" placeholder="Length" />
                            <el-input-number v-model="createForm.special_details.separator.width" :min="0" :controls="false" placeholder="Width" />
                            <el-select v-model="createForm.special_details.separator.ply" placeholder="Ply">
                                <el-option label="3-Ply" :value="3" />
                                <el-option label="5-Ply" :value="5" />
                                <el-option label="7-Ply" :value="7" />
                            </el-select>
                            <el-select v-model="createForm.special_details.separator.source" placeholder="Source">
                                <el-option label="In-House Manufacture" value="inhouse" />
                                <el-option label="Outsource Purchase" value="outsource" />
                            </el-select>
                            <el-input v-if="createForm.special_details.separator.source === 'outsource'" v-model="createForm.special_details.separator.supplier_name" placeholder="Supplier Name" />
                            <el-input v-if="createForm.special_details.separator.source === 'inhouse'" v-model="createForm.special_details.separator.inhouse_details" placeholder="Material" />
                        </div>
                    </section>

                    <section class="job-form-card span-wide">
                        <div class="section-heading">
                            <div>
                                <h3>Special Instructions</h3>
                                <p>Floor Notes &amp; Remarks</p>
                            </div>
                            <span class="section-index">05</span>
                        </div>
                        <div class="form-grid four-col">
                            <el-form-item label="Corrugation Special Instructions">
                                <el-input v-model="createForm.corrugation_instruction" type="textarea" :rows="3" />
                            </el-form-item>
                            <el-form-item label="Printing Special Instructions">
                                <el-input v-model="createForm.printing_instruction" type="textarea" :rows="3" />
                            </el-form-item>
                            <el-form-item label="Finishing Special Instructions">
                                <el-input v-model="createForm.finishing_instruction" type="textarea" :rows="3" />
                            </el-form-item>
                            <el-form-item label="General Remarks">
                                <el-input v-model="createForm.remarks" type="textarea" :rows="3" />
                            </el-form-item>
                        </div>
                    </section>
                </div>
            </el-form>
        </div>

        <!-- View Specifications dialog -->
        <el-dialog v-model="detailsDialogVisible" width="950px" title="Manufacturing Spec Sheet Detailed View">
            <div v-if="selectedJobFull" class="job-details-view" id="printableJobCard">
                <el-descriptions border title="Basic Specs Metadata" :column="3">
                    <el-descriptions-item label="Spec Code">{{ selectedJobFull.job_card_no }}</el-descriptions-item>
                    <el-descriptions-item label="Customer">{{ selectedJobFull.customer?.name }}</el-descriptions-item>
                    <el-descriptions-item label="Current Version">
                        <span class="job-version-badge">{{ currentVersionLabel(selectedJobFull) }}</span>
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

                <div class="mt-4">
                    <div class="fw-bold mb-2 text-indigo"><i class="bi bi-cart4 me-1"></i>Material BOM Requirements</div>
                    <el-table :data="jobCardBomRows(selectedJobFull)" border size="small">
                        <el-table-column prop="layer" label="Layer / Type" width="160" />
                        <el-table-column prop="material" label="Material / Paper Grade" min-width="220" show-overflow-tooltip />
                        <el-table-column prop="gsm" label="GSM" width="90" align="right" />
                        <el-table-column prop="flute" label="Flute" width="100" align="center" />
                        <el-table-column prop="required" label="Required" width="120" align="right" />
                        <el-table-column prop="consumed" label="Consumed" width="120" align="right" class-name="text-danger fw-bold" />
                    </el-table>
                </div>

                <div class="mt-4">
                    <div class="fw-bold mb-2 text-indigo"><i class="bi bi-clock-history me-1"></i>Version History</div>
                    <el-table :data="selectedJobFull.versions || []" border size="small">
                        <el-table-column label="Version" width="90">
                            <template #default="scope">V{{ scope.row.version_no }}</template>
                        </el-table-column>
                        <el-table-column label="Change Request No." min-width="150">
                            <template #default="scope">{{ scope.row.change_request_no || 'Initial Version' }}</template>
                        </el-table-column>
                        <el-table-column label="Date" width="130">
                            <template #default="scope">{{ formatDate(scope.row.created_at) }}</template>
                        </el-table-column>
                        <el-table-column label="Changed By" min-width="130">
                            <template #default="scope">{{ scope.row.creator?.name || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="Status" width="120" align="center">
                            <template #default="scope">
                                <el-tag :type="scope.row.version_status === 'Active' ? 'success' : 'info'" size="small">{{ scope.row.version_status }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="Actions" width="220" align="center">
                            <template #default="scope">
                                <el-button size="small" @click="viewVersion(scope.row)">View</el-button>
                                <el-button size="small" type="primary" plain @click="compareVersion(scope.row)">Compare</el-button>
                                <el-button size="small" type="success" plain @click="printVersion(scope.row)">Print</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
            </div>
            <template #footer>
                <el-button @click="detailsDialogVisible = false">Close</el-button>
                <el-button type="primary" class="btn-indigo animate-gradient" @click="printDirectly(selectedJobFull.id)"><i class="bi bi-printer me-1"></i> Print Spec Sheet Blueprint</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="versionDialogVisible" width="850px" title="Job Card Version Snapshot">
            <div v-if="selectedVersion">
                <el-descriptions border :column="3" size="small">
                    <el-descriptions-item label="Version">V{{ selectedVersion.version_no }}</el-descriptions-item>
                    <el-descriptions-item label="Change Request">{{ selectedVersion.change_request_no || 'Initial Version' }}</el-descriptions-item>
                    <el-descriptions-item label="Status">{{ selectedVersion.version_status }}</el-descriptions-item>
                    <el-descriptions-item label="Reason" :span="3">{{ selectedVersion.change_reason || '-' }}</el-descriptions-item>
                </el-descriptions>
                <div class="mt-3 fw-bold text-indigo">Audit Trail</div>
                <el-table :data="selectedVersion.change_logs || selectedVersion.changeLogs || []" border size="small" class="mt-2">
                    <el-table-column prop="field_name" label="Field" width="180" />
                    <el-table-column prop="old_value" label="Old Value" show-overflow-tooltip />
                    <el-table-column prop="new_value" label="New Value" show-overflow-tooltip />
                    <el-table-column label="Modified" width="150">
                        <template #default="scope">{{ formatDateTime(scope.row.modified_at) }}</template>
                    </el-table-column>
                </el-table>
            </div>
        </el-dialog>

        <el-dialog v-model="compareDialogVisible" width="900px" title="Compare Job Card Versions">
            <div v-if="versionCompare">
                <div class="mb-2 fw-bold">
                    V{{ versionCompare.from?.version_no }} to V{{ versionCompare.to?.version_no }}
                </div>
                <el-table :data="versionCompare.changes || []" border size="small">
                    <el-table-column prop="field_name" label="Field" width="190" />
                    <el-table-column prop="old_value" label="Previous Value" show-overflow-tooltip />
                    <el-table-column prop="new_value" label="Updated Value" show-overflow-tooltip />
                </el-table>
            </div>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const defaultCartonTypes = [
    { id: 1, name: 'Regular Slotted Carton', standard_code: '0201', preview_image: '/images/fefco/0201.png' },
    { id: 2, name: 'Slotted Carton', standard_code: '0200', preview_image: '/images/fefco/0200.png' },
    { id: 3, name: 'Folder Type Carton', standard_code: '0427', preview_image: '/images/fefco/0427.png' },
    { id: 4, name: 'Half Slotted Carton', standard_code: '0201-HSC', preview_image: '/images/fefco/0201-HSC.png' }
];

const cartonCategories = [
    { label: 'RSC Carton', value: 'RSC Carton' },
    { label: 'Top & Bottom Carton', value: 'Top & Bottom Carton' },
    { label: 'Separator', value: 'Separator' },
    { label: 'Honeycomb', value: 'Honeycomb' }
];

const jobCards = ref([]);
const customers = ref([]);
const cartonTypes = ref([...defaultCartonTypes]);
const papers = ref([]);
const printingColors = ref([]);
const productionMachines = ref([]);
const loading = ref(false);
const submitting = ref(false);
const isCreating = ref(false);
const nextJobCardNo = ref('QC-JC-110001');
const cartonPreviewFailed = ref(false);
const detailsDialogVisible = ref(false);
const versionDialogVisible = ref(false);
const compareDialogVisible = ref(false);
const selectedJobFull = ref(null);
const selectedVersion = ref(null);
const versionCompare = ref(null);
const createFormRef = ref(null);
const activePieceTab = ref('piece_0');
const isEditMode = ref(false);
const editJobId = ref(null);
const isChangeRequestMode = ref(false);
const changeRequestJobId = ref(null);
const changeRequestReason = ref('');

const filters = reactive({
    search: '',
    customer_id: null,
    carton_category: ''
});

const pagination = reactive({
    current_page: 1,
    per_page: 15,
    total: 0
});

const todayString = () => new Date().toISOString().slice(0, 10);

const makeSpecialDetails = () => ({
    honeycomb: {
        enabled: false,
        length: null,
        width: null,
        height: null,
        unit: 'mm',
        holes: null,
        ply: 3,
        material: '',
        source: 'inhouse',
        supplier_name: '',
        inhouse_details: ''
    },
    separator: {
        enabled: false,
        length: null,
        width: null,
        unit: 'mm',
        ply: 3,
        source: 'inhouse',
        supplier_name: '',
        inhouse_details: ''
    }
});

const layerTemplates = {
    3: [
        ['Top Layer', 'Flat'],
        ['Flute #1', 'B'],
        ['Inner Layer', 'Flat']
    ],
    5: [
        ['Top Layer', 'Flat'],
        ['Flute #1', 'B'],
        ['Middle Layer', 'Flat'],
        ['Flute #2', 'C'],
        ['Inner Layer', 'Flat']
    ],
    7: [
        ['Top Layer', 'Flat'],
        ['Flute #1', 'B'],
        ['Middle Layer #1', 'Flat'],
        ['Flute #2', 'C'],
        ['Middle Layer #2', 'Flat'],
        ['Flute #3', 'E'],
        ['Inner Layer', 'Flat']
    ]
};

const makeLayer = ([layerType, fluteProfile]) => ({
    layer_type: layerType,
    paper_id: null,
    paper_name: '',
    gsm: 0,
    flute_profile: fluteProfile
});

const layersForPly = (plyType) => (layerTemplates[Number(plyType)] || layerTemplates[3]).map(makeLayer);

const makePiece = (index, source = {}) => ({
    local_id: `${Date.now()}_${index}_${Math.random().toString(36).slice(2, 7)}`,
    piece_name: source.piece_name || `Component ${index + 1}`,
    length: source.length ?? null,
    width: source.width ?? null,
    height: source.height ?? null,
    deckle_size: source.deckle_size ?? 0,
    sheet_length: source.sheet_length ?? 0,
    ups: source.ups ?? 1,
    corrugation_machine_id: null,
    corrugation_speed: null,
    ply_type: 3,
    layers: layersForPly(3),
    print_colors: 0,
    printing_machine_id: null,
    printing_speed: null,
    printing_data: { inks: [] },
    finishing_protocol: 'Rotary Slotter',
    die_cutting_machine_id: null,
    die_cutting_speed: null,
    die_cutting_scope: 'sheet_wise',
    instructions: ''
});

const makeCreateForm = () => ({
    customer_id: null,
    item_code: '',
    item_name: '',
    carton_type_id: 1,
    carton_category: 'RSC Carton',
    carton_quality: 'normal',
    planned_qty: 1,
    planned_date: todayString(),
    delivery_date: null,
    uom: 'mm',
    length: null,
    width: null,
    height: null,
    deckle_size: 0,
    sheet_length: 0,
    est_unit_weight: 0,
    pieces_count: 1,
    ups: 1,
    corrugation_machine_id: null,
    corrugation_speed: null,
    ply_type: 3,
    slitting_creasing: 'Plant Online',
    layers: layersForPly(3),
    print_colors: 0,
    printing_machine_id: null,
    printing_speed: null,
    printing_data: { inks: [] },
    pasting_type: 'None',
    slot_type: 'Simple',
    process_type: 'Rotary Slotter',
    die_cutting_machine_id: null,
    die_cutting_speed: null,
    die_cutting_scope: 'sheet_wise',
    job_type: 'New',
    quality_priority: 'Normal',
    ink_coverage: 20,
    special_details: makeSpecialDetails(),
    corrugation_instruction: '',
    printing_instruction: '',
    finishing_instruction: '',
    remarks: '',
    pieces: []
});

const createForm = reactive(makeCreateForm());

const createRules = {
    customer_id: [{ required: true, message: 'Select customer', trigger: 'change' }],
    carton_type_id: [{ required: true, message: 'Select carton type', trigger: 'change' }],
    item_name: [{ required: true, message: 'Enter item name', trigger: 'blur' }],
    pieces_count: [{ required: true, message: 'Select pieces count', trigger: 'change' }],
    ply_type: [{ required: true, message: 'Select ply type', trigger: 'change' }],
    pasting_type: [{ required: true, message: 'Select joinery technique', trigger: 'change' }]
};

const selectedCartonType = computed(() => cartonTypes.value.find(type => Number(type.id) === Number(createForm.carton_type_id)) || null);
const selectedCustomer = computed(() => customers.value.find(customer => Number(customer.id) === Number(createForm.customer_id)) || null);
const hasDimensions = computed(() => Number(createForm.length) > 0 && Number(createForm.width) > 0 && Number(createForm.height) > 0);
const corrugationMachines = computed(() => machineByDepartment(['corrugation', 'plant']));
const printingMachines = computed(() => machineByDepartment(['printing', 'print', 'flexo']));
const dieCuttingMachines = computed(() => machineByDepartment(['die', 'cut']));
const headerTitle = computed(() => {
    if (!isCreating.value) return 'Manufacturing Specifications & Job Cards';
    if (isChangeRequestMode.value) return 'Job Card Change Request';
    if (isEditMode.value) return 'Edit Job Card';
    return 'New Job Card';
});
const headerSubtitle = computed(() => {
    if (!isCreating.value) return 'Packaging specifications, ply layer compositions, and real-time corrugation calculation engine.';
    if (isChangeRequestMode.value) return 'Revise the current active specification while preserving full version history.';
    if (isEditMode.value) return 'Correct current active Job Card data without creating a customer change request.';
    return 'Complete corrugated carton manufacturing job card.';
});
const submitButtonText = computed(() => {
    if (isChangeRequestMode.value) return 'Save Change Request';
    if (isEditMode.value) return 'Save Job Card';
    return 'Create Job Card';
});

const escapeSvgText = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

const formDieLineSvg = computed(() => {
    const dims = dimensionsToMm(createForm.length, createForm.width, createForm.height, createForm.uom);
    const length = Math.max(dims.length, 1);
    const width = Math.max(dims.width, 1);
    const height = Math.max(dims.height, 1);
    const glue = 35;
    const flap = width * 0.5;
    const totalWidth = glue + (2 * length) + (2 * width);
    const totalHeight = height + (flap * 2);
    const scale = Math.min(760 / totalWidth, 360 / totalHeight);
    const pad = 44;
    const dimensionGutter = 64;
    const headerSpace = 58;
    const footerSpace = 42;
    const y = pad + headerSpace + flap * scale;
    const bodyHeight = height * scale;
    const topFlapHeight = flap * scale;
    const bottomFlapHeight = flap * scale;
    const panelRight = pad + totalWidth * scale;
    const widthPx = totalWidth * scale + (pad * 2) + dimensionGutter;
    const heightPx = totalHeight * scale + (pad * 2) + headerSpace + footerSpace;
    const customerName = escapeSvgText(selectedCustomer.value?.name || 'Customer Name');
    const itemCode = escapeSvgText(createForm.item_code || 'Item Code');
    const itemName = escapeSvgText(createForm.item_name || 'Item Name');
    const dimText = (value) => Number(value || 0).toFixed(2);

    const panels = [
        { width: glue, type: 'glue' },
        { width: length, type: 'length' },
        { width, type: 'width' },
        { width: length, type: 'length' },
        { width, type: 'width' }
    ];

    let cursor = pad;
    const panelMarkup = panels.map((panel, index) => {
        const panelWidth = panel.width * scale;
        const topFlapMarkup = index > 0
            ? `<rect x="${cursor}" y="${y - topFlapHeight}" width="${panelWidth}" height="${topFlapHeight}" class="jc-svg-fill" />`
            : '';
        const bottomFlapMarkup = index > 0
            ? `<rect x="${cursor}" y="${y + bodyHeight}" width="${panelWidth}" height="${bottomFlapHeight}" class="jc-svg-fill" />`
            : '';
        const markup = `
            <rect x="${cursor}" y="${y}" width="${panelWidth}" height="${bodyHeight}" class="jc-svg-fill ${panel.type}" />
            ${topFlapMarkup}
            ${bottomFlapMarkup}
        `;
        cursor += panelWidth;
        return markup;
    }).join('');

    const lPanelX = pad + (glue * scale);
    const wPanelX = lPanelX + (length * scale);
    const heightArrowX = panelRight + 24;
    const heightTextX = heightArrowX + 22;
    const heightTextY = y + (bodyHeight / 2);
    const lengthArrowY = y - topFlapHeight - 16;
    const widthArrowY = y + bodyHeight + bottomFlapHeight + 16;
    const glueRight = pad + glue * scale;
    const mainPanelBreaks = [
        glueRight,
        pad + (glue + length) * scale,
        pad + (glue + length + width) * scale,
        pad + (glue + length + width + length) * scale
    ];
    const topBottomPanelBreaks = mainPanelBreaks.slice(1);

    return `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${widthPx} ${heightPx}" role="img" aria-label="Carton die-line preview">
            <style>
                .jc-svg-paper{fill:#fff;stroke:#d1d5db;stroke-width:1}
                .jc-svg-fill{fill:#fff;stroke:none}
                .jc-svg-cut{stroke:#111;stroke-width:1.2;fill:none}
                .jc-svg-fold{stroke:#111;stroke-width:1;stroke-dasharray:2 4;stroke-linecap:round;fill:none}
                .jc-svg-head{font:700 13px Arial,sans-serif;fill:#111}
                .jc-svg-sub{font:600 11px Arial,sans-serif;fill:#111}
                .jc-svg-foot{font:700 11px Arial,sans-serif;fill:#111}
                .jc-svg-dim{font:700 10px Arial,sans-serif;fill:#111}
                .jc-svg-arrow{stroke:#111;stroke-width:1.1;fill:none;marker-start:url(#arrow);marker-end:url(#arrow)}
                .jc-svg-ext{stroke:#111;stroke-width:1}
            </style>
            <defs>
                <marker id="arrow" markerWidth="8" markerHeight="8" refX="7.2" refY="4" orient="auto-start-reverse">
                    <path d="M0,0 L8,4 L0,8 z" fill="#111"></path>
                </marker>
            </defs>
            <rect x="0.5" y="0.5" width="${widthPx - 1}" height="${heightPx - 1}" rx="10" class="jc-svg-paper" />
            <text x="${widthPx / 2}" y="${pad}" text-anchor="middle" class="jc-svg-head">${customerName}</text>
            <text x="${widthPx / 2}" y="${pad + 18}" text-anchor="middle" class="jc-svg-sub">${itemCode} | ${itemName}</text>
            ${panelMarkup}
            <path d="M${glueRight} ${y - topFlapHeight} H${panelRight} V${y + bodyHeight + bottomFlapHeight} H${glueRight} V${y - topFlapHeight} Z" class="jc-svg-cut" />
            <path d="M${pad} ${y} H${glueRight} V${y + bodyHeight} H${pad} V${y} Z" class="jc-svg-cut" />
            <line x1="${pad}" y1="${y}" x2="${pad + totalWidth * scale}" y2="${y}" class="jc-svg-fold" />
            <line x1="${pad}" y1="${y + bodyHeight}" x2="${pad + totalWidth * scale}" y2="${y + bodyHeight}" class="jc-svg-fold" />
            ${mainPanelBreaks.map(x => `<line x1="${x}" y1="${y}" x2="${x}" y2="${y + bodyHeight}" class="jc-svg-fold" />`).join('')}
            ${topBottomPanelBreaks.map(x => `
                <line x1="${x}" y1="${y - topFlapHeight}" x2="${x}" y2="${y}" class="jc-svg-cut" />
                <line x1="${x}" y1="${y + bodyHeight}" x2="${x}" y2="${y + bodyHeight + bottomFlapHeight}" class="jc-svg-cut" />
            `).join('')}

            <line x1="${lPanelX}" y1="${lengthArrowY}" x2="${lPanelX + (length * scale)}" y2="${lengthArrowY}" class="jc-svg-arrow" />
            <line x1="${lPanelX}" y1="${lengthArrowY + 6}" x2="${lPanelX}" y2="${y - topFlapHeight}" class="jc-svg-ext" />
            <line x1="${lPanelX + (length * scale)}" y1="${lengthArrowY + 6}" x2="${lPanelX + (length * scale)}" y2="${y - topFlapHeight}" class="jc-svg-ext" />
            <text x="${lPanelX + (length * scale / 2)}" y="${lengthArrowY - 8}" text-anchor="middle" class="jc-svg-dim">L = ${dimText(length)} mm</text>

            <line x1="${wPanelX}" y1="${widthArrowY}" x2="${wPanelX + (width * scale)}" y2="${widthArrowY}" class="jc-svg-arrow" />
            <line x1="${wPanelX}" y1="${widthArrowY - 6}" x2="${wPanelX}" y2="${y + bodyHeight + bottomFlapHeight}" class="jc-svg-ext" />
            <line x1="${wPanelX + (width * scale)}" y1="${widthArrowY - 6}" x2="${wPanelX + (width * scale)}" y2="${y + bodyHeight + bottomFlapHeight}" class="jc-svg-ext" />
            <text x="${wPanelX + (width * scale / 2)}" y="${widthArrowY + 15}" text-anchor="middle" class="jc-svg-dim">W = ${dimText(width)} mm</text>

            <line x1="${heightArrowX}" y1="${y}" x2="${heightArrowX}" y2="${y + bodyHeight}" class="jc-svg-arrow" />
            <line x1="${heightArrowX - 8}" y1="${y}" x2="${heightArrowX + 2}" y2="${y}" class="jc-svg-ext" />
            <line x1="${heightArrowX - 8}" y1="${y + bodyHeight}" x2="${heightArrowX + 2}" y2="${y + bodyHeight}" class="jc-svg-ext" />
            <text x="${heightTextX}" y="${heightTextY}" text-anchor="middle" dominant-baseline="middle" transform="rotate(-90 ${heightTextX} ${heightTextY})" class="jc-svg-dim">H = ${dimText(height)} mm</text>

            <text x="${widthPx / 2}" y="${heightPx - 12}" text-anchor="middle" class="jc-svg-foot">Quality Cartons (Pvt.) Ltd.</text>
        </svg>
    `;
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
    const [customerRes, cartonRes, paperRes, configRes] = await Promise.allSettled([
        axios.get('/api/customers'),
        axios.get('/api/carton-types'),
        axios.get('/api/paper-qualities'),
        axios.get('/api/production-config/lookups')
    ]);

    if (customerRes.status === 'fulfilled') {
        customers.value = customerRes.value.data.data || customerRes.value.data;
    }
    if (cartonRes.status === 'fulfilled') {
        const records = cartonRes.value.data.data || cartonRes.value.data;
        cartonTypes.value = records.length ? records : [...defaultCartonTypes];
    }
    if (paperRes.status === 'fulfilled') {
        papers.value = paperRes.value.data.data || paperRes.value.data;
    }
    if (configRes.status === 'fulfilled') {
        printingColors.value = configRes.value.data.printing_colors || [];
        productionMachines.value = configRes.value.data.machines || [];
    }
};

const fetchNextJobCardNo = async () => {
    try {
        const res = await axios.get('/api/job-cards/next-number');
        nextJobCardNo.value = res.data?.next_job_card_no || 'QC-JC-110001';
    } catch (error) {
        nextJobCardNo.value = 'QC-JC-110001';
    }
};

const openCreateForm = () => {
    resetCreateForm();
    isEditMode.value = false;
    editJobId.value = null;
    isChangeRequestMode.value = false;
    changeRequestJobId.value = null;
    changeRequestReason.value = '';
    fetchNextJobCardNo();
    isCreating.value = true;
};

const discardCreateForm = () => {
    isCreating.value = false;
    resetCreateForm();
};

const resetCreateForm = () => {
    Object.assign(createForm, makeCreateForm());
    activePieceTab.value = 'piece_0';
    cartonPreviewFailed.value = false;
    isEditMode.value = false;
    editJobId.value = null;
    isChangeRequestMode.value = false;
    changeRequestJobId.value = null;
    changeRequestReason.value = '';
};

const resetFilters = () => {
    filters.search = '';
    filters.customer_id = null;
    filters.carton_category = '';
    fetchJobCards();
};

const cartonTypeLabel = (type) => type ? `${type.name} (${type.standard_code})` : '';

const cartonCategoryLabel = (job) => job?.special_details?.carton_category || job?.carton_category || 'RSC Carton';

const onCartonTypeChange = () => {
    cartonPreviewFailed.value = false;
};

const paperGsm = (paper) => Number(
    paper.standard_gsm
    ?? paper.std_gsm
    ?? paper.gsm
    ?? paper.min_gsm
    ?? paper.max_gsm
    ?? 0
);

const paperLabel = (paper) => {
    const name = paper.paper_name || paper.quality || paper.name || paper.item_code || 'Paper';
    const gsm = paperGsm(paper);
    return `${name}${gsm ? ` (${gsm.toLocaleString()} g/m2)` : ''}`;
};

const inkLabel = (ink) => `${ink.ink_name} (${ink.ink_code})`;

const machineLabel = (machine) => `${machine.machine_name}${machine.department ? ` - ${machine.department.department_name}` : ''}`;

const machineByDepartment = (keywords) => {
    const matched = productionMachines.value.filter(machine => {
        const department = String(machine.department?.department_name || '').toLowerCase();
        const machineName = String(machine.machine_name || '').toLowerCase();
        return keywords.some(keyword => department.includes(keyword) || machineName.includes(keyword));
    });
    return matched.length ? matched : productionMachines.value;
};

const selectedMachine = (machineId) => productionMachines.value.find(machine => Number(machine.id) === Number(machineId));

const isFluteLayer = (layer) => String(layer.layer_type || '').toLowerCase().includes('flute');

const onPaperChange = (layer, paperId, piece = null) => {
    const paper = papers.value.find(item => Number(item.id) === Number(paperId));
    if (!paper) return;
    layer.paper_name = paper.paper_name || paper.quality || paper.name || paper.item_code || '';
    layer.gsm = paperGsm(paper);
    piece ? recalculatePieceSpecs(piece) : recalculateSpecs();
};

const dimensionsToMm = (length, width, height, uom) => {
    const toNum = (value) => {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    };
    const factor = uom === 'inch' ? 25.4 : (uom === 'cm' ? 10 : 1);
    return {
        length: Math.max(0, toNum(length)) * factor,
        width: Math.max(0, toNum(width)) * factor,
        height: Math.max(0, toNum(height)) * factor
    };
};

const calculateSpecs = (length, width, height, layers, uom, ups = 1) => {
    const dims = dimensionsToMm(length, width, height, uom);
    const mmToInch = 0.03937007874015748;
    const deckle = (dims.width + dims.height + 25) * mmToInch;
    const sheetLength = ((dims.length * 2) + (dims.width * 2) + 75) * mmToInch;
    const fluteFactors = { Flat: 1, B: 1.35, C: 1.45, E: 1.25 };
    const gsmTotal = (layers || []).reduce((sum, layer) => sum + (Number(layer.gsm || 0) * (fluteFactors[layer.flute_profile] || 1)), 0);
    const areaM2 = deckle * sheetLength * 0.00064516;
    const netWeight = ((areaM2 * gsmTotal) / 1000) * 0.94;

    return {
        deckle,
        sheet_length: sheetLength,
        weight: netWeight / Math.max(Number(ups || 1), 1),
        dims
    };
};

const recalculateSpecs = () => {
    const result = calculateSpecs(createForm.length, createForm.width, createForm.height, createForm.layers, createForm.uom, createForm.ups);
    createForm.deckle_size = result.deckle;
    createForm.sheet_length = result.sheet_length;

    if (createForm.pieces_count === 1) {
        createForm.est_unit_weight = result.weight;
    } else {
        let totalWeight = 0;
        createForm.pieces.forEach(piece => {
            recalculatePieceSpecs(piece);
            totalWeight += Number(piece.est_unit_weight || 0);
        });
        createForm.est_unit_weight = totalWeight;
    }

    fetchAllTargetSpeeds();
};

const recalculatePieceSpecs = (piece) => {
    const result = calculateSpecs(piece.length, piece.width, piece.height, piece.layers, createForm.uom, piece.ups);
    piece.deckle_size = result.deckle;
    piece.sheet_length = result.sheet_length;
    piece.est_unit_weight = result.weight;
};

// Keep deckle/sheet formulas always in sync even when UI events do not fire.
watch(
    () => [createForm.length, createForm.width, createForm.height, createForm.uom, createForm.ups, createForm.pieces_count],
    () => recalculateSpecs()
);

const onPlyTypeChange = () => {
    createForm.layers = layersForPly(createForm.ply_type);
    recalculateSpecs();
    fetchCorrugationSpeed();
};

const onPiecePlyTypeChange = (piece) => {
    piece.layers = layersForPly(piece.ply_type);
    recalculatePieceSpecs(piece);
};

const onPiecesCountChange = () => {
    if (createForm.pieces_count === 1) {
        createForm.pieces = [];
        activePieceTab.value = 'piece_0';
        onPlyTypeChange();
        return;
    }

    const desiredCount = Math.min(Math.max(Number(createForm.pieces_count || 2), 2), 5);
    const pieces = [];
    for (let index = 0; index < desiredCount; index++) {
        pieces.push(createForm.pieces[index] || makePiece(index, {
            length: createForm.length,
            width: createForm.width,
            height: createForm.height
        }));
        recalculatePieceSpecs(pieces[index]);
    }
    createForm.pieces = pieces;
    activePieceTab.value = 'piece_0';
    recalculateSpecs();
};

const syncInkArray = (holder, count) => {
    const inks = holder.printing_data?.inks || [];
    holder.printing_data = { ...(holder.printing_data || {}), inks: inks.slice(0, count) };
    while (holder.printing_data.inks.length < count) {
        holder.printing_data.inks.push('');
    }
};

const onPrintColorsChange = () => {
    syncInkArray(createForm, createForm.print_colors);
    fetchPrintingSpeed();
};

const onPieceColorsChange = (piece) => {
    syncInkArray(piece, piece.print_colors);
    fetchPieceSpeed(piece, 'printing');
};

const speedPayload = (baseSpeed = 0, overrides = {}) => ({
    base_speed: Number(baseSpeed || 0),
    minimum_speed: Number(overrides.minimum_speed || 0),
    job: {
        print_colors: overrides.print_colors ?? createForm.print_colors,
        ink_coverage: createForm.ink_coverage,
        slot_type: overrides.slot_type ?? createForm.slot_type,
        job_type: createForm.job_type,
        quantity: createForm.planned_qty,
        quality_priority: createForm.quality_priority
    }
});

const calculateOptimizedSpeed = async (machineId, overrides = {}) => {
    const machine = selectedMachine(machineId);
    if (!machine) return null;
    const baseSpeed = Number(machine.base_speed || machine.target_speed || 0);
    if (!baseSpeed) return null;

    try {
        const { data } = await axios.post('/api/production-config/optimization-rules/apply', speedPayload(baseSpeed, {
            minimum_speed: machine.minimum_speed,
            ...overrides
        }));
        return data.final_speed;
    } catch (error) {
        return baseSpeed;
    }
};

const fetchCorrugationSpeed = async () => {
    createForm.corrugation_speed = await calculateOptimizedSpeed(createForm.corrugation_machine_id);
};

const fetchPrintingSpeed = async () => {
    createForm.printing_speed = await calculateOptimizedSpeed(createForm.printing_machine_id);
};

const fetchDieCuttingSpeed = async () => {
    createForm.die_cutting_speed = await calculateOptimizedSpeed(createForm.die_cutting_machine_id);
};

const fetchPieceSpeed = async (piece, type) => {
    if (type === 'corrugation') {
        piece.corrugation_speed = await calculateOptimizedSpeed(piece.corrugation_machine_id, { print_colors: piece.print_colors });
    } else if (type === 'printing') {
        piece.printing_speed = await calculateOptimizedSpeed(piece.printing_machine_id, { print_colors: piece.print_colors });
    } else {
        piece.die_cutting_speed = await calculateOptimizedSpeed(piece.die_cutting_machine_id, { print_colors: piece.print_colors });
    }
};

const fetchAllTargetSpeeds = () => {
    fetchCorrugationSpeed();
    fetchPrintingSpeed();
    fetchDieCuttingSpeed();
};

const layerPayload = (layer) => ({
    layer_type: layer.layer_type,
    paper_name: layer.paper_name || 'Unspecified',
    gsm: Number(layer.gsm || 0),
    flute_profile: isFluteLayer(layer) ? layer.flute_profile : 'Flat'
});

const piecePayload = (piece, index) => {
    const corrugationMachine = selectedMachine(piece.corrugation_machine_id);
    return {
        piece_name: piece.piece_name || `Component ${index + 1}`,
        length_mm: dimensionsToMm(piece.length, piece.width, piece.height, createForm.uom).length,
        width_mm: dimensionsToMm(piece.length, piece.width, piece.height, createForm.uom).width,
        height_mm: dimensionsToMm(piece.length, piece.width, piece.height, createForm.uom).height,
        deckle_size: piece.deckle_size,
        sheet_length: piece.sheet_length,
        ply_type: piece.ply_type,
        ups: piece.ups,
        machine_name: corrugationMachine?.machine_name || '',
        target_speed: piece.corrugation_speed || 0,
        est_unit_weight: piece.est_unit_weight || 0,
        instructions: piece.instructions,
        print_colors: piece.print_colors,
        printing_data: piece.printing_data,
        printing_machine_id: piece.printing_machine_id,
        printing_speed: piece.printing_speed,
        finishing_protocol: piece.finishing_protocol,
        die_cutting_machine_id: null,
        die_cutting_speed: null,
        die_cutting_scope: null,
        layers: piece.layers.map(layerPayload)
    };
};

const normalizeLayerFromRecord = (layer) => ({
    layer_type: layer.layer_type || 'Layer',
    paper_id: null,
    paper_name: layer.paper_name || '',
    gsm: Number(layer.gsm || 0),
    flute_profile: layer.flute_profile || 'Flat'
});

const inferPlyType = (layers = []) => [3, 5, 7].includes(layers.length) ? layers.length : 3;

const formLengthFromMm = (value, uom) => {
    const num = Number(value || 0);
    if (uom === 'inch') return num / 25.4;
    if (uom === 'cm') return num / 10;
    return num;
};

const hydrateCreateFormFromJob = (job) => {
    const special = job.special_details || {};
    const uom = job.uom || 'mm';
    const layers = (job.layers || []).map(normalizeLayerFromRecord);
    const pieces = (job.pieces || []).map((piece, index) => {
        const pieceLayers = (piece.layers || []).map(normalizeLayerFromRecord);
        return {
            ...makePiece(index),
            piece_name: piece.piece_name || `Component ${index + 1}`,
            length: formLengthFromMm(piece.length_mm, uom),
            width: formLengthFromMm(piece.width_mm, uom),
            height: formLengthFromMm(piece.height_mm, uom),
            deckle_size: Number(piece.deckle_size || 0),
            sheet_length: Number(piece.sheet_length || 0),
            ups: Number(piece.ups || 1),
            ply_type: inferPlyType(pieceLayers),
            layers: pieceLayers.length ? pieceLayers : layersForPly(3),
            print_colors: Number(piece.print_colors ?? special.print_colors ?? 0),
            printing_data: piece.printing_data || { inks: [] },
            finishing_protocol: piece.finishing_protocol || special.process_type || 'Rotary Slotter',
            instructions: piece.instructions || ''
        };
    });

    Object.assign(createForm, makeCreateForm(), {
        customer_id: job.customer_id,
        item_code: job.product?.item_code || '',
        item_name: job.product?.item_name || '',
        carton_type_id: Number(special.carton_type_id || 1),
        carton_category: special.carton_category || 'RSC Carton',
        carton_quality: special.carton_quality || 'normal',
        planned_qty: Number(job.planned_qty || 1),
        planned_date: job.planned_date || todayString(),
        delivery_date: job.delivery_date || null,
        uom,
        length: formLengthFromMm(job.length_mm, uom),
        width: formLengthFromMm(job.width_mm, uom),
        height: formLengthFromMm(job.height_mm, uom),
        deckle_size: Number(job.deckle_size || 0),
        sheet_length: Number(job.sheet_length || 0),
        est_unit_weight: Number(job.est_unit_weight || 0),
        pieces_count: Number(job.pieces_count || 1),
        ups: Number(job.ups || 1),
        ply_type: inferPlyType(layers),
        layers: layers.length ? layers : layersForPly(3),
        print_colors: Number(special.print_colors ?? job.printing_colors_count ?? 0),
        printing_data: special.printing_data || { inks: job.pantone_colors || [] },
        pasting_type: special.pasting_type || job.pasting_closure || 'None',
        slot_type: special.slot_type || 'Simple',
        process_type: special.process_type || 'Rotary Slotter',
        die_cutting_scope: special.die_cutting_scope || 'sheet_wise',
        job_type: special.job_type || 'Repeat',
        quality_priority: special.quality_priority || 'Normal',
        ink_coverage: Number(special.ink_coverage || 20),
        special_details: {
            honeycomb: { ...makeSpecialDetails().honeycomb, ...(special.honeycomb_detail || {}), enabled: Boolean(special.honeycomb) },
            separator: { ...makeSpecialDetails().separator, ...(special.separator_detail || {}), enabled: Boolean(special.separators) }
        },
        corrugation_instruction: special.corrugation_instruction || '',
        printing_instruction: special.printing_instruction || '',
        finishing_instruction: special.finishing_instruction || '',
        remarks: job.notes || '',
        pieces
    });

    activePieceTab.value = createForm.pieces.length ? createForm.pieces[0].local_id : 'piece_0';
    cartonPreviewFailed.value = false;
};

const validateCreateForm = async () => {
    if (!createFormRef.value) return false;
    const valid = await createFormRef.value.validate().catch(() => false);
    if (!valid) return false;

    if (createForm.pieces_count > 1) {
        const missingPiece = createForm.pieces.find(piece => !piece.piece_name || !piece.ply_type);
        if (missingPiece) {
            ElMessage.error('Complete component designation and ply type for every component.');
            return false;
        }
    }

    if (createForm.process_type === 'Die Cutting' && createForm.pieces_count === 1 && !createForm.die_cutting_machine_id) {
        ElMessage.error('Select a die cutting machine.');
        return false;
    }

    return true;
};

const buildJobCardPayload = () => {
    const dims = dimensionsToMm(createForm.length, createForm.width, createForm.height, createForm.uom);
    const cartonType = selectedCartonType.value;
    const corrugationMachine = selectedMachine(createForm.corrugation_machine_id);
    const pieces = createForm.pieces.map(piecePayload);
    const printColors = createForm.pieces_count === 1
        ? createForm.print_colors
        : Math.max(0, ...createForm.pieces.map(piece => Number(piece.print_colors || 0)));
    const inks = createForm.pieces_count === 1
        ? createForm.printing_data.inks.filter(Boolean)
        : [...new Set(createForm.pieces.flatMap(piece => piece.printing_data.inks || []).filter(Boolean))];

    return {
        customer_id: createForm.customer_id,
        fg_product_id: null,
        item_code: createForm.item_code,
        item_name: createForm.item_name,
        planned_qty: createForm.planned_qty,
        planned_date: createForm.planned_date,
        delivery_date: createForm.delivery_date,
        specifications: cartonType ? cartonTypeLabel(cartonType) : '',
        notes: createForm.remarks,
        length_mm: dims.length,
        width_mm: dims.width,
        height_mm: dims.height,
        uom: createForm.uom,
        deckle_size: createForm.deckle_size,
        sheet_length: createForm.sheet_length,
        ups: createForm.ups,
        carton_type: cartonType ? cartonTypeLabel(cartonType) : 'Carton',
        machine_name: corrugationMachine?.machine_name || '',
        target_speed: createForm.corrugation_speed || 0,
        printing_process: printColors > 0 ? 'FLEXOGRAPHIC' : 'UNPRINTED',
        pasting_closure: createForm.pasting_type,
        printing_colors_count: printColors,
        pantone_colors: inks,
        special_details: {
            carton_type_id: createForm.carton_type_id,
            carton_type_code: cartonType?.standard_code,
            carton_category: createForm.carton_category,
            carton_quality: createForm.carton_quality,
            corrugation_machine_id: createForm.corrugation_machine_id,
            corrugation_speed: createForm.corrugation_speed,
            printing_machine_id: createForm.printing_machine_id,
            printing_speed: createForm.printing_speed,
            die_cutting_machine_id: createForm.die_cutting_machine_id,
            die_cutting_speed: createForm.die_cutting_speed,
            die_cutting_scope: createForm.die_cutting_scope,
            slitting_creasing: createForm.slitting_creasing,
            print_colors: printColors,
            printing_data: createForm.printing_data,
            pasting_type: createForm.pasting_type,
            slot_type: createForm.slot_type,
            process_type: createForm.process_type,
            job_type: createForm.job_type,
            ink_coverage: createForm.ink_coverage,
            quality_priority: createForm.quality_priority,
            honeycomb: createForm.special_details.honeycomb.enabled,
            honeycomb_detail: createForm.special_details.honeycomb,
            separators: createForm.special_details.separator.enabled,
            separator_detail: createForm.special_details.separator,
            corrugation_instruction: createForm.corrugation_instruction,
            printing_instruction: createForm.printing_instruction,
            finishing_instruction: createForm.finishing_instruction,
            pieces_detail: pieces
        },
        pieces_count: createForm.pieces_count,
        est_unit_weight: createForm.est_unit_weight,
        layers: createForm.pieces_count === 1 ? createForm.layers.map(layerPayload) : [],
        pieces
    };
};

const submitCreateForm = async () => {
    if (submitting.value) return;
    const valid = await validateCreateForm();
    if (!valid) return;

    submitting.value = true;
    try {
        const payload = buildJobCardPayload();
        if (isChangeRequestMode.value) {
            payload.change_reason = changeRequestReason.value;
            await axios.post(`/api/job-cards/${changeRequestJobId.value}/change-request`, payload);
            ElMessage.success('Change request saved as a new active version');
        } else if (isEditMode.value) {
            await axios.put(`/api/job-cards/${editJobId.value}`, payload);
            ElMessage.success('Job card updated successfully');
        } else {
            await axios.post('/api/job-cards', payload);
            ElMessage.success('Job card created successfully');
        }
        isCreating.value = false;
        resetCreateForm();
        fetchJobCards();
    } catch (error) {
        ElMessage.error(error.response?.data?.error || 'Failed to save job card');
    } finally {
        submitting.value = false;
    }
};

const safeFilePart = (value, fallback) => String(value || fallback)
    .trim()
    .replace(/[<>:"/\\|?*]+/g, '')
    .replace(/\s+/g, ' ')
    .slice(0, 80) || fallback;

const downloadDieLine = async () => {
    if (!hasDimensions.value) {
        ElMessage.warning('Enter length, width, and height before generating the die-line.');
        return;
    }

    const svg = formDieLineSvg.value;
    const viewBox = svg.match(/viewBox="0 0 ([\d.]+) ([\d.]+)"/);
    const width = viewBox ? Math.ceil(Number(viewBox[1])) : 900;
    const height = viewBox ? Math.ceil(Number(viewBox[2])) : 360;
    const image = new Image();
    const encodedSvg = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;

    await new Promise((resolve, reject) => {
        image.onload = resolve;
        image.onerror = reject;
        image.src = encodedSvg;
    }).catch(() => {
        ElMessage.error('Unable to generate JPEG die-line.');
    });

    if (!image.complete || !image.naturalWidth) return;

    const canvas = document.createElement('canvas');
    canvas.width = width * 2;
    canvas.height = height * 2;
    const context = canvas.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(image, 0, 0, canvas.width, canvas.height);

    const customerName = safeFilePart(selectedCustomer.value?.name, 'Customer');
    const itemName = safeFilePart(createForm.item_name, 'Item Name');
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/jpeg', 0.92);
    link.download = `${customerName}_${itemName}.jpeg`;
    document.body.appendChild(link);
    link.click();
    link.remove();
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

const openEditForm = async (job) => {
    try {
        const res = await axios.get(`/api/job-cards/${job.id}`);
        hydrateCreateFormFromJob(res.data);
        isEditMode.value = true;
        editJobId.value = job.id;
        isChangeRequestMode.value = false;
        changeRequestJobId.value = null;
        changeRequestReason.value = '';
        nextJobCardNo.value = res.data.job_card_no;
        isCreating.value = true;
    } catch (error) {
        ElMessage.error('Failed to open job card for editing');
    }
};

const openChangeRequest = async (job) => {
    try {
        const { value: reason } = await ElMessageBox.prompt('Reason for Change', 'Create Job Card Change Request', {
            confirmButtonText: 'Continue',
            cancelButtonText: 'Cancel',
            inputType: 'textarea',
            inputPlaceholder: 'Describe the customer requested specification change...',
            inputPattern: /\S{3,}/,
            inputErrorMessage: 'Reason for change is required'
        });

        const res = await axios.get(`/api/job-cards/${job.id}`);
        hydrateCreateFormFromJob(res.data);
        isEditMode.value = false;
        editJobId.value = null;
        isChangeRequestMode.value = true;
        changeRequestJobId.value = job.id;
        changeRequestReason.value = reason;
        nextJobCardNo.value = res.data.job_card_no;
        isCreating.value = true;
    } catch (error) {
        if (error !== 'cancel' && error !== 'close') {
            ElMessage.error('Failed to open change request');
        }
    }
};

const deleteJobCard = async (job) => {
    try {
        await ElMessageBox.confirm(
            `Permanently remove Job Card ${job.job_card_no} from active records?`,
            'Delete Job Card',
            {
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                type: 'warning',
                confirmButtonClass: 'el-button--danger'
            }
        );

        await axios.delete(`/api/job-cards/${job.id}`);
        ElMessage.success('Job Card deleted successfully');
        fetchJobCards();
    } catch (error) {
        if (error === 'cancel' || error === 'close') return;
        ElMessage.error(error.response?.data?.error || 'Failed to delete Job Card');
    }
};

const viewVersion = async (version) => {
    if (!selectedJobFull.value) return;
    try {
        const res = await axios.get(`/api/job-cards/${selectedJobFull.value.id}/versions/${version.id}`);
        selectedVersion.value = res.data;
        versionDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('Failed to load version details');
    }
};

const compareVersion = async (version) => {
    if (!selectedJobFull.value) return;
    const versions = [...(selectedJobFull.value.versions || [])].sort((a, b) => a.version_no - b.version_no);
    const previous = [...versions].reverse().find(item => item.version_no < version.version_no) || versions.find(item => item.id !== version.id);

    if (!previous) {
        ElMessage.info('No previous version is available for comparison.');
        return;
    }

    try {
        const res = await axios.get(`/api/job-cards/${selectedJobFull.value.id}/versions/compare`, {
            params: { from: previous.id, to: version.id }
        });
        versionCompare.value = res.data;
        compareDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('Failed to compare versions');
    }
};

const printVersion = (version) => {
    if (!selectedJobFull.value) return;
    window.open(`/job-cards/${selectedJobFull.value.id}/versions/${version.id}/print`, '_blank');
};

const printDirectly = (id) => {
    window.open(`/job-cards/${id}/print`, '_blank');
};

const currentVersionLabel = (job) => `V${job?.active_version?.version_no || job?.activeVersion?.version_no || job?.current_version_no || 1}`;

const jobCardBomRows = (job) => {
    if (!job) return [];

    const itemRows = (job.items || []).map((item) => ({
        layer: item.unit || 'Material',
        material: item.item?.rm_name || item.item?.name || item.item?.item_name || item.item?.item_code || '-',
        gsm: '-',
        flute: '-',
        required: `${formatNum(item.required_qty, 2)} ${item.unit || ''}`.trim(),
        consumed: `${formatNum(item.consumed_qty, 2)} ${item.unit || ''}`.trim()
    }));

    if (itemRows.length) {
        return itemRows;
    }

    const singlePieceRows = (job.layers || []).map((layer) => ({
        layer: layer.layer_type || 'Layer',
        material: layer.paper_name || '-',
        gsm: Number(layer.gsm || 0) ? formatNum(layer.gsm, 0) : '-',
        flute: layer.flute_profile && layer.flute_profile !== 'Flat' ? layer.flute_profile : '-',
        required: '-',
        consumed: '-'
    }));

    const pieceRows = (job.pieces || []).flatMap((piece) => (piece.layers || []).map((layer) => ({
        layer: `${piece.piece_name || 'Component'} - ${layer.layer_type || 'Layer'}`,
        material: layer.paper_name || '-',
        gsm: Number(layer.gsm || 0) ? formatNum(layer.gsm, 0) : '-',
        flute: layer.flute_profile && layer.flute_profile !== 'Flat' ? layer.flute_profile : '-',
        required: '-',
        consumed: '-'
    })));

    return singlePieceRows.length ? singlePieceRows : pieceRows;
};

const formatQty = (val) => Number(val || 0).toLocaleString();
const formatNum = (val, dec) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec });
const formatDate = (value) => value ? new Date(value).toLocaleDateString('en-GB') : '-';
const formatDateTime = (value) => value ? new Date(value).toLocaleString('en-GB') : '-';

onMounted(() => {
    fetchJobCards();
    fetchData();
    fetchNextJobCardNo();
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
.job-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.job-card-management > .glass-header {
    min-height: 86px;
    padding: 14px 24px !important;
}
.job-card-management > .glass-header h2 {
    font-size: 1.55rem !important;
    line-height: 1.15;
    margin-bottom: 5px !important;
}
.job-card-management > .glass-header h2 .bi {
    font-size: 1.35rem;
    vertical-align: -0.03em;
}
.job-card-management > .glass-header p {
    font-size: 0.82rem !important;
    line-height: 1.25;
}
.job-card-management > .glass-header :deep(.el-button) {
    border-radius: 8px;
    font-size: 0.92rem;
    font-weight: 800;
    height: 40px;
    padding: 0 18px;
}
.job-card-management > .glass-card {
    border-radius: 9px;
}
.job-version-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 22px;
    padding: 0 10px;
    border-radius: 999px;
    border: 1px solid rgba(79, 70, 229, 0.28);
    background: rgba(79, 70, 229, 0.1);
    color: #3730a3;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.04em;
}
[data-theme="dark"] .job-version-badge {
    border-color: rgba(129, 140, 248, 0.45);
    background: rgba(79, 70, 229, 0.22);
    color: #dbeafe;
}
.job-mode-alert {
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    background: #eff6ff;
}
.job-mode-alert :deep(.el-alert__title),
.job-mode-alert :deep(.el-alert__content),
.job-mode-alert :deep(.el-alert__description) {
    color: #1e3a8a !important;
    font-weight: 800;
    line-height: 1.45;
}
.job-mode-alert :deep(.el-alert__icon) {
    color: #2563eb !important;
}
:global([data-theme="dark"]) .job-card-management .job-mode-alert,
:global(body.dark-mode) .job-card-management .job-mode-alert {
    border-color: rgba(96, 165, 250, 0.42) !important;
    background: rgba(30, 58, 138, 0.32) !important;
}
:global([data-theme="dark"]) .job-card-management .job-mode-alert .el-alert__title,
:global([data-theme="dark"]) .job-card-management .job-mode-alert .el-alert__content,
:global([data-theme="dark"]) .job-card-management .job-mode-alert .el-alert__description,
:global(body.dark-mode) .job-card-management .job-mode-alert .el-alert__title,
:global(body.dark-mode) .job-card-management .job-mode-alert .el-alert__content,
:global(body.dark-mode) .job-card-management .job-mode-alert .el-alert__description {
    color: #e0f2fe !important;
}
:global([data-theme="dark"]) .job-card-management .job-mode-alert .el-alert__icon,
:global(body.dark-mode) .job-card-management .job-mode-alert .el-alert__icon {
    color: #93c5fd !important;
}
.filter-container {
    align-items: end;
    margin-bottom: 10px !important;
    padding: 7px 10px !important;
}
.filter-container label {
    font-size: 0.64rem !important;
    line-height: 1.2;
    margin-bottom: 3px !important;
}
.filter-container .col-md-2,
.filter-container .col-md-3,
.filter-container .col-md-4,
.filter-container .col-md-5 {
    min-width: 0;
}
.job-create-workspace {
    --jc-create-card: #ffffff;
    --jc-create-card-soft: #f1f5f9;
    --jc-create-border: #d8e0eb;
    --jc-create-text: #172033;
    --jc-create-muted: #64748b;
}
.job-card-form {
    color: var(--jc-create-text);
}
.job-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
    gap: 18px;
}
.job-form-card {
    background: var(--jc-create-card);
    border: 1px solid var(--jc-create-border);
    border-radius: 8px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
    min-width: 0;
    padding: 18px;
}
.span-wide {
    grid-column: 1 / -1;
}
.basic-info-layout {
    align-items: start;
    display: grid;
    gap: 18px;
    grid-template-columns: minmax(0, 1.15fr) minmax(360px, 0.85fr);
}
.basic-info-fields {
    min-width: 0;
}
.carton-selection-layout {
    align-items: stretch;
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1.34fr) minmax(208px, 0.66fr);
}
.carton-selection-fields {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.carton-selection-fields :deep(.el-form-item:last-child) {
    margin-bottom: 0;
}
.carton-quality-field {
    margin-top: auto;
}
.basic-info-card .dieline-panel {
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.basic-info-card .dieline-canvas,
.basic-info-card .dieline-empty {
    height: 271px;
    min-height: 271px;
}
.section-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid var(--jc-create-border);
    margin-bottom: 16px;
    padding-bottom: 12px;
}
.section-heading h3 {
    color: var(--jc-create-text);
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.25;
    margin: 0;
}
.section-heading p,
.mini-label {
    color: var(--jc-create-muted);
    font-size: 0.78rem;
    font-weight: 700;
    margin: 3px 0 0;
}
.basic-info-heading-right {
    align-items: center;
    display: flex;
    gap: 10px;
}
.job-number-pill {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border: 1px solid #93c5fd;
    border-radius: 10px;
    color: #1d4ed8;
    font-size: 1.12rem;
    font-weight: 900;
    letter-spacing: 0.02em;
    padding: 7px 14px;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.55);
}
.section-index {
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    border-radius: 8px;
    color: #4f46e5;
    font-size: 0.74rem;
    font-weight: 900;
    padding: 5px 8px;
}
.form-grid {
    display: grid;
    gap: 12px;
}
.two-col {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.three-col {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.four-col {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}
.piece-config-row {
    grid-template-columns: 1fr 1fr 1fr 1fr;
}
.dimension-input-row {
    grid-template-columns: 1.05fr 0.9fr 1fr 1fr 1fr;
}
.align-start {
    align-items: start;
}
.compact-grid,
.dimension-panel,
.dieline-panel,
.carton-preview-panel,
.ink-grid,
.addon-toggle {
    background: var(--jc-create-card-soft);
    border: 1px solid var(--jc-create-border);
    border-radius: 8px;
}
.compact-grid {
    border-style: dashed;
    margin-top: 10px;
    padding: 12px;
}
.dimension-panel,
.dieline-panel,
.carton-preview-panel,
.addon-toggle {
    padding: 12px;
}
.carton-preview-panel {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 184px;
    overflow: hidden;
}
.preview-panel-header {
    align-items: center;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin: 0;
    padding: 9px 11px;
}
.preview-panel-header span,
.carton-preview-panel span {
    color: var(--jc-create-muted);
    display: block;
    font-size: 0.76rem;
    font-weight: 800;
    line-height: 1.1;
    margin: 0;
    text-transform: uppercase;
}
.preview-panel-header strong {
    color: var(--jc-create-text);
    font-size: 0.8rem;
    font-weight: 900;
    letter-spacing: 0;
}
.carton-preview-stage {
    align-items: center;
    background:
        linear-gradient(#ffffff, #ffffff) padding-box,
        repeating-linear-gradient(45deg, #eef2f7 0 1px, transparent 1px 10px);
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    display: flex;
    flex: 1;
    justify-content: center;
    min-height: 128px;
    overflow: hidden;
    padding: 10px;
}
.carton-preview-panel img {
    display: block;
    height: 100%;
    max-height: 146px;
    max-width: 100%;
    object-fit: contain;
    transform: scale(1.22);
    transform-origin: center;
    width: 100%;
}
.carton-preview-fallback {
    align-items: center;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    color: #334155;
    display: flex;
    flex: 1;
    font-weight: 900;
    justify-content: center;
    min-height: 100%;
    width: 100%;
}
.segmented-control {
    width: 100%;
}
.segmented-control :deep(.el-radio-button) {
    width: 50%;
}
.segmented-control :deep(.el-radio-button__inner) {
    width: 100%;
}
.sizing-section {
    display: grid;
    gap: 12px;
    margin-top: 18px;
}
.dieline-panel strong {
    color: var(--jc-create-text);
    display: block;
    font-size: 0.9rem;
}
.dieline-header {
    margin-bottom: 12px;
}
.dieline-canvas,
.dieline-empty {
    align-items: center;
    background:
        linear-gradient(#ffffff, #ffffff) padding-box,
        repeating-linear-gradient(45deg, #eef2f7 0 1px, transparent 1px 12px);
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    min-height: 176px;
    overflow: hidden;
    padding: 10px 12px;
}
.dieline-canvas :deep(svg) {
    display: block;
    height: 100%;
    max-height: 253px;
    min-width: 0;
    width: 100%;
}
.dieline-empty {
    color: var(--jc-create-muted);
    font-weight: 700;
}
.table-wrap {
    border: 1px solid var(--jc-create-border);
    border-radius: 8px;
    overflow-x: auto;
}
.construction-table {
    border-collapse: collapse;
    min-width: 760px;
    width: 100%;
}
.construction-table th {
    background: #e8eef6;
    color: #334155;
    font-size: 0.76rem;
    letter-spacing: 0;
    padding: 10px;
    text-transform: uppercase;
}
.construction-table td {
    background: #ffffff;
    border-top: 1px solid var(--jc-create-border);
    color: var(--jc-create-text);
    padding: 8px;
    vertical-align: middle;
}
.target-speed {
    color: #047857;
    font-size: 0.76rem;
    font-weight: 900;
    margin-top: 5px;
}
.component-tabs {
    border-radius: 8px;
    overflow: hidden;
}
.component-tabs :deep(.el-tabs__content) {
    padding: 16px;
}
.component-fields-grid {
    align-items: start;
    gap: 10px 12px;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}
.component-secondary-grid {
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    margin-bottom: 12px;
}
.component-tabs :deep(.el-form-item) {
    margin-bottom: 8px;
}
.component-tabs :deep(.el-form-item__label) {
    font-size: 0.72rem;
    margin-bottom: 4px;
}
.component-tabs :deep(.el-input),
.component-tabs :deep(.el-select),
.component-tabs :deep(.el-input-number) {
    min-height: 36px;
}
.component-tabs :deep(.el-input__wrapper),
.component-tabs :deep(.el-select__wrapper),
.component-tabs :deep(.el-input-number .el-input__wrapper) {
    border-radius: 7px;
    min-height: 36px;
    padding: 0 10px;
}
.component-tabs :deep(.el-input__inner),
.component-tabs :deep(.el-select__selected-item),
.component-tabs :deep(.el-select__placeholder) {
    font-size: 0.82rem;
    font-weight: 650;
    line-height: 34px;
    min-height: 34px;
}
.component-tabs :deep(.el-select__caret) {
    font-size: 0.82rem;
}
.component-tabs .target-speed {
    font-size: 0.68rem;
    margin-top: 3px;
}
.component-tabs .ink-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    margin: 8px 0 12px;
    padding: 10px;
}
.component-tabs .table-wrap {
    margin-top: 12px;
}
.component-tabs .construction-table th {
    font-size: 0.68rem;
    padding: 7px 8px;
}
.component-tabs .construction-table td {
    padding: 6px;
}
.component-tabs .construction-table :deep(.el-input),
.component-tabs .construction-table :deep(.el-select),
.component-tabs .construction-table :deep(.el-input-number) {
    min-height: 34px;
}
.component-tabs .construction-table :deep(.el-input__wrapper),
.component-tabs .construction-table :deep(.el-select__wrapper),
.component-tabs .construction-table :deep(.el-input-number .el-input__wrapper) {
    min-height: 34px;
}
.component-tabs .construction-table :deep(.el-input__inner),
.component-tabs .construction-table :deep(.el-select__selected-item),
.component-tabs .construction-table :deep(.el-select__placeholder) {
    line-height: 32px;
    min-height: 32px;
}
.filter-container :deep(.el-input),
.filter-container :deep(.el-select),
.filter-container :deep(.el-input__wrapper),
.filter-container :deep(.el-select__wrapper) {
    height: 32px;
    min-height: 32px;
}
.filter-container :deep(.el-input__wrapper),
.filter-container :deep(.el-select__wrapper) {
    border-radius: 7px;
    padding: 0 10px;
}
.filter-container :deep(.el-input__inner),
.filter-container :deep(.el-select__selected-item),
.filter-container :deep(.el-select__placeholder) {
    font-size: 0.76rem;
    font-weight: 700;
    line-height: 30px;
    min-height: 30px;
}
.filter-container :deep(.el-select__caret) {
    font-size: 0.78rem;
}
.filter-container :deep(.el-button),
.filter-container .btn-clear-filters {
    border-radius: 7px;
    font-size: 0.76rem;
    font-weight: 800;
    height: 32px;
    min-height: 32px;
    padding: 0 10px;
}
.ink-grid {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    padding: 12px;
}
.ink-option {
    align-items: center;
    display: inline-flex;
    gap: 8px;
}
.ink-swatch {
    border: 1px solid #94a3b8;
    border-radius: 999px;
    display: inline-block;
    height: 12px;
    width: 12px;
}
.addon-toggle {
    align-items: center;
    display: flex;
    justify-content: space-between;
}
.addon-toggle strong {
    color: var(--jc-create-text);
    display: block;
}
.addon-toggle span {
    color: var(--jc-create-muted);
    font-size: 0.76rem;
    font-weight: 800;
}
.job-card-form :deep(.el-form-item) {
    margin-bottom: 14px;
}
.job-card-form :deep(.el-form-item__label) {
    color: var(--jc-create-muted);
    font-size: 0.78rem;
    font-weight: 800;
    line-height: 1.25;
    margin-bottom: 6px;
    padding-bottom: 0;
}
.job-card-form :deep(.el-input),
.job-card-form :deep(.el-select),
.job-card-form :deep(.el-date-editor.el-input),
.job-card-form :deep(.el-input-number) {
    min-height: 42px;
    width: 100%;
}
.job-card-form :deep(.el-input__wrapper),
.job-card-form :deep(.el-select__wrapper),
.job-card-form :deep(.el-date-editor .el-input__wrapper),
.job-card-form :deep(.el-input-number .el-input__wrapper) {
    border-radius: 8px;
    min-height: 42px;
    padding: 0 12px;
}
.job-card-form :deep(.el-input__inner),
.job-card-form :deep(.el-select__selected-item),
.job-card-form :deep(.el-select__placeholder) {
    font-size: 0.9rem;
    font-weight: 650;
    min-height: 40px;
    line-height: 40px;
}
.job-card-form :deep(.el-input-number .el-input__inner) {
    text-align: left;
}
.job-card-form :deep(.el-radio-button__inner) {
    align-items: center;
    display: inline-flex;
    height: 42px;
    justify-content: center;
    padding: 0 14px;
}
.job-card-form :deep(.el-textarea__inner) {
    border-radius: 8px;
    font-size: 0.9rem;
    line-height: 1.45;
    min-height: 96px;
    padding: 10px 12px;
}
.compact-grid :deep(.el-input),
.compact-grid :deep(.el-select),
.compact-grid :deep(.el-input-number),
.ink-grid :deep(.el-select) {
    min-height: 42px;
    width: 100%;
}
.construction-table :deep(.el-form-item) {
    margin-bottom: 0;
}
.construction-table :deep(.el-input),
.construction-table :deep(.el-select),
.construction-table :deep(.el-input-number) {
    min-height: 38px;
}
.construction-table :deep(.el-input__wrapper),
.construction-table :deep(.el-select__wrapper),
.construction-table :deep(.el-input-number .el-input__wrapper) {
    border-radius: 7px;
    min-height: 38px;
}
.construction-table :deep(.el-input__inner),
.construction-table :deep(.el-select__selected-item),
.construction-table :deep(.el-select__placeholder) {
    min-height: 36px;
    line-height: 36px;
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

:global([data-theme="dark"]) .job-card-management {
    color: #f8fafc;
}

:global([data-theme="dark"]) .job-card-management .glass-header {
    background: #111827;
    border: 1px solid #334155;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.28) !important;
}

:global([data-theme="dark"]) .job-card-management .glass-header h2,
:global([data-theme="dark"]) .job-card-management .glass-header .text-dark {
    color: #f8fafc !important;
}

:global([data-theme="dark"]) .job-card-management .glass-header .text-muted {
    color: #94a3b8 !important;
}

:global([data-theme="dark"]) .job-card-management .glass-card {
    background: #111827;
    border: 1px solid #334155;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.24) !important;
}

:global([data-theme="dark"]) .job-card-management .filter-container {
    background: #1c283b !important;
    border-color: #41516a !important;
}

:global([data-theme="dark"]) .job-card-management .filter-container label {
    color: #cbd5e1 !important;
}

:global([data-theme="dark"]) .job-card-management :deep(.el-input__wrapper),
:global([data-theme="dark"]) .job-card-management :deep(.el-select__wrapper) {
    background-color: #111827 !important;
    border: 1px solid #41516a !important;
    box-shadow: none !important;
    color: #f8fafc !important;
}

:global([data-theme="dark"]) .job-card-management :deep(.el-input__wrapper.is-focus),
:global([data-theme="dark"]) .job-card-management :deep(.el-select__wrapper.is-focused) {
    border-color: #6ea8ff !important;
    box-shadow: 0 0 0 0.18rem rgba(110, 168, 255, 0.16) !important;
}

:global([data-theme="dark"]) .job-card-management :deep(.el-input__inner),
:global([data-theme="dark"]) .job-card-management :deep(.el-select__placeholder),
:global([data-theme="dark"]) .job-card-management :deep(.el-select__selected-item) {
    color: #f8fafc !important;
}

:global([data-theme="dark"]) .job-card-management :deep(.el-input__inner::placeholder),
:global([data-theme="dark"]) .job-card-management :deep(.el-select__placeholder) {
    color: #7f91ad !important;
}

:global([data-theme="dark"]) .job-card-management .modern-table :deep(.el-table__header) th,
:global([data-theme="dark"]) .job-card-management .modern-table :deep(th.el-table__cell) {
    background-color: #1e293b !important;
    color: #cbd5e1 !important;
    border-color: #334155 !important;
}

:global([data-theme="dark"]) .job-card-management .modern-table :deep(.el-table__body),
:global([data-theme="dark"]) .job-card-management .modern-table :deep(.el-table__body-wrapper),
:global([data-theme="dark"]) .job-card-management .modern-table :deep(.el-table__empty-block),
:global([data-theme="dark"]) .job-card-management .modern-table :deep(tr),
:global([data-theme="dark"]) .job-card-management .modern-table :deep(td.el-table__cell) {
    background-color: #1c283b !important;
    color: #e2e8f0 !important;
    border-color: #334155 !important;
}

:global([data-theme="dark"]) .job-card-management .modern-table :deep(.el-table__row:hover td.el-table__cell) {
    background-color: #263449 !important;
}

:global([data-theme="dark"]) .job-card-management .modern-table :deep(.el-table__empty-text) {
    color: #e2e8f0 !important;
}

:global([data-theme="dark"]) .job-card-management .bg-white,
:global([data-theme="dark"]) .job-card-management .bg-light-soft {
    background-color: #1c283b !important;
    border-color: #41516a !important;
    color: #e2e8f0 !important;
}

@media (max-width: 1100px) {
    .job-form-grid,
    .basic-info-layout {
        grid-template-columns: 1fr;
    }
    .carton-selection-layout {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 1101px) and (max-width: 1360px) {
    .four-col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .component-fields-grid {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }
    .dimension-input-row {
        grid-template-columns: 1.05fr 0.9fr 1fr 1fr 1fr;
    }
    .piece-config-row {
        grid-template-columns: 1fr 1fr 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .job-card-management {
        padding: 12px !important;
    }
    .glass-header {
        align-items: flex-start !important;
        flex-direction: column;
        gap: 12px;
    }
    .job-header-actions {
        align-items: stretch;
        flex-direction: column;
        width: 100%;
    }
    .job-form-grid,
    .two-col,
    .three-col,
    .four-col,
    .ink-grid {
        grid-template-columns: 1fr;
    }
    .job-form-card {
        padding: 14px;
    }
    .section-heading {
        align-items: flex-start;
    }
}
</style>

<style>
[data-theme="dark"] .job-card-management,
body.dark-mode .job-card-management {
    --jc-bg: #0f172a;
    --jc-surface: #111827;
    --jc-surface-2: #1e293b;
    --jc-surface-3: #243044;
    --jc-border: #334155;
    --jc-text: #f8fafc;
    --jc-muted: #9fb0c8;
    --jc-accent: #60a5fa;
    color: var(--jc-text) !important;
}

[data-theme="dark"] .job-card-management .glass-header,
body.dark-mode .job-card-management .glass-header {
    background: linear-gradient(135deg, #111827 0%, #172033 100%) !important;
    border: 1px solid var(--jc-border) !important;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28) !important;
}

[data-theme="dark"] .job-card-management .glass-card,
body.dark-mode .job-card-management .glass-card {
    background: #111827 !important;
    border: 1px solid var(--jc-border) !important;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.24) !important;
}

[data-theme="dark"] .job-card-management .filter-container,
body.dark-mode .job-card-management .filter-container {
    background: #172033 !important;
    border-color: var(--jc-border) !important;
}

[data-theme="dark"] .job-card-management h1,
[data-theme="dark"] .job-card-management h2,
[data-theme="dark"] .job-card-management h3,
[data-theme="dark"] .job-card-management h4,
[data-theme="dark"] .job-card-management h5,
[data-theme="dark"] .job-card-management h6,
[data-theme="dark"] .job-card-management .fw-bold,
[data-theme="dark"] .job-card-management .text-dark,
body.dark-mode .job-card-management h1,
body.dark-mode .job-card-management h2,
body.dark-mode .job-card-management h3,
body.dark-mode .job-card-management h4,
body.dark-mode .job-card-management h5,
body.dark-mode .job-card-management h6,
body.dark-mode .job-card-management .fw-bold,
body.dark-mode .job-card-management .text-dark {
    color: var(--jc-text) !important;
}

[data-theme="dark"] .job-card-management .text-muted,
[data-theme="dark"] .job-card-management label,
[data-theme="dark"] .job-card-management .small,
body.dark-mode .job-card-management .text-muted,
body.dark-mode .job-card-management label,
body.dark-mode .job-card-management .small {
    color: var(--jc-muted) !important;
}

[data-theme="dark"] .job-card-management .text-indigo,
body.dark-mode .job-card-management .text-indigo {
    color: #93c5fd !important;
}

[data-theme="dark"] .job-card-management .el-input__wrapper,
[data-theme="dark"] .job-card-management .el-select__wrapper,
[data-theme="dark"] .job-card-management .el-input-number .el-input__wrapper,
body.dark-mode .job-card-management .el-input__wrapper,
body.dark-mode .job-card-management .el-select__wrapper,
body.dark-mode .job-card-management .el-input-number .el-input__wrapper {
    background: var(--jc-surface-2) !important;
    border: 1px solid #475569 !important;
    box-shadow: none !important;
}

[data-theme="dark"] .job-card-management .el-input__inner,
[data-theme="dark"] .job-card-management .el-select__placeholder,
[data-theme="dark"] .job-card-management .el-select__selected-item,
body.dark-mode .job-card-management .el-input__inner,
body.dark-mode .job-card-management .el-select__placeholder,
body.dark-mode .job-card-management .el-select__selected-item {
    color: var(--jc-text) !important;
}

[data-theme="dark"] .job-card-management .el-input__inner::placeholder,
body.dark-mode .job-card-management .el-input__inner::placeholder {
    color: #7f91ad !important;
}

[data-theme="dark"] .job-card-management .el-table,
[data-theme="dark"] .job-card-management .el-table__inner-wrapper,
[data-theme="dark"] .job-card-management .el-table__header-wrapper,
[data-theme="dark"] .job-card-management .el-table__body-wrapper,
[data-theme="dark"] .job-card-management .el-table__empty-block,
[data-theme="dark"] .job-card-management .el-table tr,
body.dark-mode .job-card-management .el-table,
body.dark-mode .job-card-management .el-table__inner-wrapper,
body.dark-mode .job-card-management .el-table__header-wrapper,
body.dark-mode .job-card-management .el-table__body-wrapper,
body.dark-mode .job-card-management .el-table__empty-block,
body.dark-mode .job-card-management .el-table tr {
    background: var(--jc-surface) !important;
    color: var(--jc-text) !important;
}

[data-theme="dark"] .job-card-management .el-table th.el-table__cell,
body.dark-mode .job-card-management .el-table th.el-table__cell {
    background: var(--jc-surface-2) !important;
    color: #dbeafe !important;
    border-color: var(--jc-border) !important;
}

[data-theme="dark"] .job-card-management .el-table td.el-table__cell,
body.dark-mode .job-card-management .el-table td.el-table__cell {
    background: #172033 !important;
    color: var(--jc-text) !important;
    border-color: var(--jc-border) !important;
}

[data-theme="dark"] .job-card-management .el-table__empty-text,
body.dark-mode .job-card-management .el-table__empty-text {
    color: var(--jc-muted) !important;
}

[data-theme="dark"] .job-card-management .el-table--border::after,
[data-theme="dark"] .job-card-management .el-table--border::before,
[data-theme="dark"] .job-card-management .el-table__inner-wrapper::before,
body.dark-mode .job-card-management .el-table--border::after,
body.dark-mode .job-card-management .el-table--border::before,
body.dark-mode .job-card-management .el-table__inner-wrapper::before {
    background-color: var(--jc-border) !important;
}

[data-theme="dark"] .job-card-management .el-pagination button,
[data-theme="dark"] .job-card-management .el-pager li,
body.dark-mode .job-card-management .el-pagination button,
body.dark-mode .job-card-management .el-pager li {
    background: var(--jc-surface-2) !important;
    color: #cbd5e1 !important;
}

[data-theme="dark"] .job-card-management .el-pager li.is-active,
body.dark-mode .job-card-management .el-pager li.is-active {
    background: #4f46e5 !important;
    color: #fff !important;
}

[data-theme="dark"] .job-card-management .bg-white,
[data-theme="dark"] .job-card-management .bg-light-soft,
body.dark-mode .job-card-management .bg-white,
body.dark-mode .job-card-management .bg-light-soft {
    background: #172033 !important;
    border-color: var(--jc-border) !important;
    color: var(--jc-text) !important;
}

[data-theme="dark"] .job-card-management .job-create-workspace,
body.dark-mode .job-card-management .job-create-workspace {
    --jc-create-card: #111827;
    --jc-create-card-soft: #172033;
    --jc-create-border: #334155;
    --jc-create-text: #f8fafc;
    --jc-create-muted: #9fb0c8;
}

[data-theme="dark"] .job-card-management .job-form-card,
[data-theme="dark"] .job-card-management .dimension-panel,
[data-theme="dark"] .job-card-management .dieline-panel,
[data-theme="dark"] .job-card-management .carton-preview-panel,
[data-theme="dark"] .job-card-management .ink-grid,
[data-theme="dark"] .job-card-management .addon-toggle,
[data-theme="dark"] .job-card-management .compact-grid,
body.dark-mode .job-card-management .job-form-card,
body.dark-mode .job-card-management .dimension-panel,
body.dark-mode .job-card-management .dieline-panel,
body.dark-mode .job-card-management .carton-preview-panel,
body.dark-mode .job-card-management .ink-grid,
body.dark-mode .job-card-management .addon-toggle,
body.dark-mode .job-card-management .compact-grid {
    background: var(--jc-create-card-soft) !important;
    border-color: var(--jc-create-border) !important;
    color: var(--jc-create-text) !important;
}

[data-theme="dark"] .job-card-management .job-form-card,
body.dark-mode .job-card-management .job-form-card {
    background: var(--jc-create-card) !important;
}

[data-theme="dark"] .job-card-management .section-heading,
[data-theme="dark"] .job-card-management .table-wrap,
body.dark-mode .job-card-management .section-heading,
body.dark-mode .job-card-management .table-wrap {
    border-color: var(--jc-create-border) !important;
}

[data-theme="dark"] .job-card-management .section-heading h3,
[data-theme="dark"] .job-card-management .dieline-panel strong,
[data-theme="dark"] .job-card-management .addon-toggle strong,
body.dark-mode .job-card-management .section-heading h3,
body.dark-mode .job-card-management .dieline-panel strong,
body.dark-mode .job-card-management .addon-toggle strong {
    color: var(--jc-create-text) !important;
}

[data-theme="dark"] .job-card-management .section-heading p,
[data-theme="dark"] .job-card-management .mini-label,
[data-theme="dark"] .job-card-management .addon-toggle span,
[data-theme="dark"] .job-card-management .carton-preview-panel span,
body.dark-mode .job-card-management .section-heading p,
body.dark-mode .job-card-management .mini-label,
body.dark-mode .job-card-management .addon-toggle span,
body.dark-mode .job-card-management .carton-preview-panel span {
    color: var(--jc-create-muted) !important;
}

[data-theme="dark"] .job-card-management .job-number-pill,
body.dark-mode .job-card-management .job-number-pill {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    border-color: #60a5fa;
    color: #dbeafe;
    text-shadow: none;
}

[data-theme="dark"] .job-card-management .carton-preview-fallback,
[data-theme="dark"] .job-card-management .carton-preview-stage,
[data-theme="dark"] .job-card-management .dieline-canvas,
[data-theme="dark"] .job-card-management .dieline-empty,
[data-theme="dark"] .job-card-management .preview-panel-header,
body.dark-mode .job-card-management .carton-preview-fallback,
body.dark-mode .job-card-management .carton-preview-stage,
body.dark-mode .job-card-management .dieline-canvas,
body.dark-mode .job-card-management .dieline-empty,
body.dark-mode .job-card-management .preview-panel-header {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}

[data-theme="dark"] .job-card-management .construction-table th,
body.dark-mode .job-card-management .construction-table th {
    background: #1e293b !important;
    color: #dbeafe !important;
}

[data-theme="dark"] .job-card-management .dieline-canvas,
body.dark-mode .job-card-management .dieline-canvas {
    background:
        linear-gradient(#f8fafc, #f8fafc) padding-box,
        repeating-linear-gradient(45deg, #e2e8f0 0 1px, transparent 1px 12px) !important;
    border-color: #475569 !important;
}

[data-theme="dark"] .job-card-management .construction-table td,
body.dark-mode .job-card-management .construction-table td {
    background: #111827 !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}

[data-theme="dark"] .job-card-management .component-tabs,
[data-theme="dark"] .job-card-management .component-tabs .el-tabs__content,
[data-theme="dark"] .job-card-management .component-tabs .el-tabs__header,
body.dark-mode .job-card-management .component-tabs,
body.dark-mode .job-card-management .component-tabs .el-tabs__content,
body.dark-mode .job-card-management .component-tabs .el-tabs__header {
    background: #111827 !important;
    border-color: #334155 !important;
}

[data-theme="dark"] .job-card-management .component-tabs .el-tabs__item,
body.dark-mode .job-card-management .component-tabs .el-tabs__item {
    color: #9fb0c8 !important;
}

[data-theme="dark"] .job-card-management .component-tabs .el-tabs__item.is-active,
body.dark-mode .job-card-management .component-tabs .el-tabs__item.is-active {
    color: #93c5fd !important;
}
</style>
