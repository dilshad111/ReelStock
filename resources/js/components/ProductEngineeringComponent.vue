<template>
    <section class="pe-module">
        <div class="pe-header">
            <div>
                <span class="pe-eyebrow">Production Master</span>
                <h1><i class="bi bi-diagram-3 me-2"></i>Product Engineering & Job Card Master</h1>
                <p>Permanent product definitions with component specifications, paper BOM, routing, process instructions, and revision control.</p>
            </div>
            <div class="pe-actions">
                <el-button class="pe-muted-btn" @click="refreshAll">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </el-button>
                <el-button type="primary" class="pe-primary-btn" @click="openEditor()">
                    <i class="bi bi-plus-circle me-1"></i> New Product Master
                </el-button>
            </div>
        </div>

        <div class="pe-filter-card">
            <el-input v-model="filters.search" clearable placeholder="Search product code, name, category, customer..." @keyup.enter="fetchProducts">
                <template #prefix><i class="bi bi-search"></i></template>
            </el-input>
            <el-select v-model="filters.customer_id" filterable clearable placeholder="All Customers">
                <el-option v-for="customer in lookups.customers" :key="customer.id" :label="customer.name" :value="customer.id" />
            </el-select>
            <el-select v-model="filters.product_category" filterable clearable allow-create default-first-option placeholder="All Categories">
                <el-option v-for="category in lookups.categories" :key="category" :label="category" :value="category" />
            </el-select>
            <el-select v-model="filters.status" clearable placeholder="All Statuses">
                <el-option label="Active" value="Active" />
                <el-option label="Inactive" value="Inactive" />
            </el-select>
            <el-button type="primary" class="pe-primary-btn" @click="fetchProducts">Apply</el-button>
            <el-button class="pe-clear-btn" @click="clearFilters">Clear</el-button>
        </div>

        <div class="pe-content-grid">
            <section class="pe-panel pe-list-panel">
                <div class="pe-panel-head">
                    <div>
                        <span class="pe-eyebrow">Product Master Screen</span>
                        <h2>Engineering Products</h2>
                    </div>
                    <span class="pe-count">{{ pagination.total }} records</span>
                </div>

                <el-table :data="products" v-loading="loading" border class="pe-table" highlight-current-row @row-click="selectProduct">
                    <el-table-column prop="product_number" label="Product No." min-width="145" />
                    <el-table-column prop="product_code" label="Product Code" min-width="140" />
                    <el-table-column prop="product_name" label="Product Name" min-width="220" show-overflow-tooltip />
                    <el-table-column label="Customer" min-width="170" show-overflow-tooltip>
                        <template #default="{ row }">{{ row.customer?.name || 'General Master' }}</template>
                    </el-table-column>
                    <el-table-column prop="product_category" label="Category" min-width="150" />
                    <el-table-column label="Rev." width="80" align="center">
                        <template #default="{ row }"><span class="pe-version">R{{ row.revision_number }}</span></template>
                    </el-table-column>
                    <el-table-column label="Components" width="110" align="center">
                        <template #default="{ row }">{{ row.components_count || 0 }}</template>
                    </el-table-column>
                    <el-table-column label="Status" width="105" align="center">
                        <template #default="{ row }">
                            <span :class="['pe-status', row.status === 'Active' ? 'is-active' : 'is-inactive']">{{ row.status }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="Actions" width="125" align="center">
                        <template #default="{ row }">
                            <el-dropdown trigger="click">
                                <button class="pe-dots-btn" @click.stop><i class="bi bi-three-dots-vertical"></i></button>
                                <template #dropdown>
                                    <el-dropdown-menu>
                                        <el-dropdown-item @click="selectProduct(row)"><i class="bi bi-eye me-2"></i>View</el-dropdown-item>
                                        <el-dropdown-item @click="openEditor(row)"><i class="bi bi-pencil me-2"></i>Edit</el-dropdown-item>
                                        <el-dropdown-item @click="openRevision(row)"><i class="bi bi-arrow-up-right-square me-2"></i>Revision</el-dropdown-item>
                                        <el-dropdown-item divided @click="deleteProduct(row)" class="text-danger"><i class="bi bi-trash me-2"></i>Delete</el-dropdown-item>
                                    </el-dropdown-menu>
                                </template>
                            </el-dropdown>
                        </template>
                    </el-table-column>
                    <template #empty>
                        <div class="pe-empty">No product engineering masters found.</div>
                    </template>
                </el-table>

                <div class="pe-pagination">
                    <el-pagination
                        background
                        layout="prev, pager, next"
                        :total="pagination.total"
                        :page-size="pagination.per_page"
                        :current-page="pagination.current_page"
                        @current-change="page => { pagination.current_page = page; fetchProducts(); }"
                    />
                </div>
            </section>

            <section class="pe-panel pe-detail-panel">
                <template v-if="selectedProduct">
                    <div class="pe-panel-head">
                        <div>
                            <span class="pe-eyebrow">Product Structure View</span>
                            <h2>{{ selectedProduct.product_code }} | {{ selectedProduct.product_name }}</h2>
                            <p>{{ selectedProduct.product_number || '-' }} &middot; Revision R{{ selectedProduct.revision_number }} &middot; {{ selectedProduct.customer?.name || 'General Master' }}</p>
                        </div>
                        <div class="pe-detail-actions">
                            <el-button plain @click="openEditor(selectedProduct)">Edit</el-button>
                            <el-button type="primary" plain @click="openRevision(selectedProduct)">Revision</el-button>
                        </div>
                    </div>

                    <el-tabs v-model="activeDetailTab" class="pe-detail-tabs">
                        <el-tab-pane label="Structure Tree" name="tree">
                            <div class="pe-tree">
                                <div class="pe-tree-node pe-tree-product">
                                    <i class="bi bi-box-seam"></i>
                                    <strong>{{ selectedProduct.product_name }}</strong>
                                    <span>{{ selectedProduct.product_code }}</span>
                                </div>
                                <div v-for="component in selectedProduct.components" :key="component.id" class="pe-tree-branch">
                                    <div class="pe-tree-node">
                                        <i class="bi bi-layers"></i>
                                        <strong>{{ component.component_name }}</strong>
                                        <span>{{ component.component_code }} · Qty {{ formatNumber(component.quantity_per_product, 2) }}</span>
                                    </div>
                                    <div class="pe-tree-leaves">
                                        <span><i class="bi bi-rulers"></i> Specifications</span>
                                        <span><i class="bi bi-list-check"></i> BOM {{ component.bom_layers?.length || 0 }}</span>
                                        <span><i class="bi bi-signpost-split"></i> Routing {{ component.routings?.length || 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </el-tab-pane>

                        <el-tab-pane label="Components" name="components">
                            <el-table :data="selectedProduct.components" border class="pe-table compact">
                                <el-table-column prop="component_code" label="Code" width="120" />
                                <el-table-column prop="component_name" label="Component" min-width="180" />
                                <el-table-column prop="component_type" label="Type" min-width="130" />
                                <el-table-column label="Qty/Product" width="120" align="right">
                                    <template #default="{ row }">{{ formatNumber(row.quantity_per_product, 4) }}</template>
                                </el-table-column>
                                <el-table-column label="Ply / Flute" width="150">
                                    <template #default="{ row }">{{ row.specification?.ply_type || '-' }} / {{ row.specification?.flute_type || '-' }}</template>
                                </el-table-column>
                                <el-table-column label="Printed" width="95" align="center">
                                    <template #default="{ row }">{{ row.specification?.is_printed ? 'Yes' : 'No' }}</template>
                                </el-table-column>
                            </el-table>
                        </el-tab-pane>

                        <el-tab-pane label="BOM & Routing" name="bom-routing">
                            <div v-for="component in selectedProduct.components" :key="component.id" class="pe-component-summary">
                                <h3>{{ component.component_name }}</h3>
                                <div class="pe-two-col">
                                    <div>
                                        <h4>Paper BOM</h4>
                                        <table class="pe-mini-table">
                                            <thead><tr><th>Seq</th><th>Layer</th><th>Paper Name</th><th>GSM</th><th>Supplier</th></tr></thead>
                                            <tbody>
                                                <tr v-for="layer in component.bom_layers" :key="layer.id">
                                                    <td>{{ layer.layer_sequence }}</td>
                                                    <td>{{ layer.layer_label || '-' }}</td>
                                                    <td>{{ layer.paper_type }}</td>
                                                    <td>{{ layer.gsm || '-' }}</td>
                                                    <td>{{ layer.supplier?.name || '-' }}</td>
                                                </tr>
                                                <tr v-if="!component.bom_layers?.length"><td colspan="5">No BOM layers.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div>
                                        <h4>Manufacturing Routing</h4>
                                        <table class="pe-mini-table">
                                            <thead><tr><th>Seq</th><th>Process</th><th>Instruction</th></tr></thead>
                                            <tbody>
                                                <tr v-for="routing in component.routings" :key="routing.id">
                                                    <td>{{ routing.sequence_no }}</td>
                                                    <td>{{ routing.process_name }}</td>
                                                    <td>{{ routing.process_instructions || '-' }}</td>
                                                </tr>
                                                <tr v-if="!component.routings?.length"><td colspan="3">No routing configured.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </el-tab-pane>

                        <el-tab-pane label="Revision History" name="revisions">
                            <el-table :data="selectedProduct.revisions" border class="pe-table compact">
                                <el-table-column label="Revision" width="100">
                                    <template #default="{ row }">R{{ row.revision_number }}</template>
                                </el-table-column>
                                <el-table-column label="Date" width="120">
                                    <template #default="{ row }">{{ formatDate(row.revision_date || row.created_at) }}</template>
                                </el-table-column>
                                <el-table-column prop="change_notes" label="Change Notes" min-width="260" />
                                <el-table-column label="Changed By" width="140">
                                    <template #default="{ row }">{{ row.creator?.name || '-' }}</template>
                                </el-table-column>
                            </el-table>
                        </el-tab-pane>

                        <el-tab-pane label="Production Explosion" name="explode">
                            <div class="pe-explode-bar">
                                <el-input-number v-model="explodeQty" :min="0" :controls="false" placeholder="Product Qty" />
                                <el-button type="primary" class="pe-primary-btn" @click="explodeProduct">Calculate Components</el-button>
                            </div>
                            <el-table :data="explosion.components || []" border class="pe-table compact">
                                <el-table-column prop="component_code" label="Component Code" width="145" />
                                <el-table-column prop="component_name" label="Component" min-width="190" />
                                <el-table-column prop="quantity_per_product" label="Qty/Product" width="120" align="right" />
                                <el-table-column prop="required_quantity" label="Required Qty" width="130" align="right" />
                                <el-table-column label="Routing" min-width="220">
                                    <template #default="{ row }">{{ (row.routing || []).map(r => r.process_name).join(' → ') || '-' }}</template>
                                </el-table-column>
                            </el-table>
                        </el-tab-pane>
                    </el-tabs>
                </template>
                <div v-else class="pe-detail-empty">
                    <i class="bi bi-diagram-3"></i>
                    <h2>Select a product master</h2>
                    <p>View its permanent component structure, BOM, routing, process instructions, revision history, and production-order explosion preview.</p>
                </div>
            </section>
        </div>

        <el-dialog v-model="editorVisible" :title="editorTitle" width="96%" top="2vh" destroy-on-close class="pe-editor-dialog">
            <el-form ref="formRef" :model="form" :rules="rules" label-position="top" class="pe-form">
                <section class="pe-editor-section">
                    <div class="pe-editor-title">
                        <div>
                            <span class="pe-eyebrow">Product Master Screen</span>
                            <h2>Product Definition</h2>
                        </div>
                        <span class="pe-version">R{{ form.revision_number || 1 }}</span>
                    </div>
                    <div class="pe-master-grid">
                        <div class="pe-master-form">
                    <div class="pe-form-grid four">
                        <el-form-item label="Product Master No.">
                            <el-input v-model="form.product_number" disabled placeholder="Auto generated on save" />
                        </el-form-item>
                        <el-form-item label="Creation Date">
                            <el-date-picker v-model="form.product_created_date" type="date" value-format="YYYY-MM-DD" format="DD/MM/YYYY" disabled class="w-100" />
                        </el-form-item>
                        <el-form-item label="Product Code" prop="product_code">
                            <el-input v-model="form.product_code" placeholder="e.g. PRD-MC-0001" />
                        </el-form-item>
                        <el-form-item label="Product Name" prop="product_name">
                            <el-input v-model="form.product_name" placeholder="Product / master carton name" />
                        </el-form-item>
                        <el-form-item label="Customer">
                            <el-select v-model="form.customer_id" filterable clearable placeholder="Optional customer" class="w-100">
                                <el-option v-for="customer in lookups.customers" :key="customer.id" :label="customer.name" :value="customer.id" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="Product Category">
                            <el-select v-model="form.product_category" filterable clearable allow-create default-first-option placeholder="Carton category" class="w-100">
                                <el-option label="RSC Carton" value="RSC Carton" />
                                <el-option label="Top & Bottom Carton" value="Top & Bottom Carton" />
                                <el-option label="Separator" value="Separator" />
                                <el-option label="Honeycomb" value="Honeycomb" />
                                <el-option v-for="category in lookups.categories" :key="category" :label="category" :value="category" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="Revision Number">
                            <el-input-number v-model="form.revision_number" :min="1" :controls="false" disabled class="w-100" />
                        </el-form-item>
                        <el-form-item label="Status" prop="status">
                            <el-select v-model="form.status" class="w-100">
                                <el-option label="Active" value="Active" />
                                <el-option label="Inactive" value="Inactive" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="Revision Date">
                            <el-date-picker v-model="form.revision_date" type="date" value-format="YYYY-MM-DD" format="DD/MM/YYYY" disabled class="w-100" />
                        </el-form-item>
                    </div>
                    <div class="pe-form-grid two">
                        <el-form-item label="Remarks">
                            <el-input v-model="form.remarks" type="textarea" :rows="2" placeholder="Permanent product notes" />
                        </el-form-item>
                        <el-form-item label="Change Notes">
                            <el-input v-model="form.change_notes" type="textarea" :rows="2" placeholder="Why this version/revision is being saved" />
                        </el-form-item>
                    </div>
                        </div>
                        <div class="pe-master-visuals">
                            <div class="pe-visual-card">
                                <div class="pe-visual-head">
                                    <span>Carton Preview</span>
                                    <small>{{ previewComponent.component_name || 'Primary Component' }}</small>
                                </div>
                                <svg class="pe-carton-svg" viewBox="0 0 420 230" role="img" aria-label="Carton preview">
                                    <path d="M116 78 L236 44 L335 86 L212 126 Z" class="pe-svg-panel pe-svg-top" />
                                    <path d="M116 78 L212 126 L212 190 L116 140 Z" class="pe-svg-panel" />
                                    <path d="M212 126 L335 86 L335 150 L212 190 Z" class="pe-svg-panel" />
                                    <path d="M236 44 L282 22 L378 63 L335 86 Z" class="pe-svg-flap" />
                                    <path d="M116 78 L76 54 L196 22 L236 44 Z" class="pe-svg-flap" />
                                    <path d="M212 190 L164 207 L72 158 L116 140 Z" class="pe-svg-flap" />
                                    <text x="276" y="176">L {{ dimensionText(previewSpec.length) }}</text>
                                    <text x="87" y="134">W {{ dimensionText(previewSpec.width) }}</text>
                                    <text x="345" y="130">H {{ dimensionText(previewSpec.height) }}</text>
                                </svg>
                            </div>
                            <div class="pe-visual-card">
                                <div class="pe-visual-head">
                                    <span>Die-Line Structure</span>
                                    <small>{{ form.product_category || 'Carton structure' }}</small>
                                </div>
                                <svg class="pe-dieline-svg" viewBox="0 0 560 250" role="img" aria-label="Die-line structure">
                                    <rect x="36" y="84" width="28" height="82" class="pe-svg-flap" />
                                    <rect x="64" y="46" width="116" height="158" class="pe-svg-panel" />
                                    <rect x="180" y="46" width="88" height="158" class="pe-svg-panel" />
                                    <rect x="268" y="46" width="116" height="158" class="pe-svg-panel" />
                                    <rect x="384" y="46" width="88" height="158" class="pe-svg-panel" />
                                    <line x1="64" y1="84" x2="472" y2="84" class="pe-svg-dash" />
                                    <line x1="64" y1="166" x2="472" y2="166" class="pe-svg-dash" />
                                    <line x1="180" y1="46" x2="180" y2="204" class="pe-svg-dash" />
                                    <line x1="268" y1="46" x2="268" y2="204" class="pe-svg-dash" />
                                    <line x1="384" y1="46" x2="384" y2="204" class="pe-svg-dash" />
                                    <path d="M64 32 L180 32" class="pe-svg-arrow" marker-start="url(#peArrow)" marker-end="url(#peArrow)" />
                                    <path d="M180 220 L268 220" class="pe-svg-arrow" marker-start="url(#peArrow)" marker-end="url(#peArrow)" />
                                    <path d="M498 84 L498 166" class="pe-svg-arrow" marker-start="url(#peArrow)" marker-end="url(#peArrow)" />
                                    <defs>
                                        <marker id="peArrow" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                            <path d="M 0 0 L 10 5 L 0 10 z" />
                                        </marker>
                                    </defs>
                                    <text x="88" y="27">L {{ dimensionText(previewSpec.length) }}</text>
                                    <text x="195" y="238">W {{ dimensionText(previewSpec.width) }}</text>
                                    <text x="512" y="129" transform="rotate(90 512 129)">H {{ dimensionText(previewSpec.height) }}</text>
                                </svg>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pe-editor-section">
                    <div class="pe-editor-title">
                        <div>
                            <span class="pe-eyebrow">Component Management Screen</span>
                            <h2>Product Components</h2>
                        </div>
                        <el-button type="primary" plain @click="addComponent">
                            <i class="bi bi-plus me-1"></i>Add Component
                        </el-button>
                    </div>

                    <el-tabs v-model="activeComponentKey" type="border-card" class="pe-component-tabs">
                        <el-tab-pane v-for="(component, cIndex) in form.components" :key="component.local_id" :name="component.local_id">
                            <template #label>
                                {{ component.component_name || `Component ${cIndex + 1}` }}
                            </template>

                            <div class="pe-component-toolbar">
                                <strong>Component {{ cIndex + 1 }}</strong>
                                <el-button v-if="form.components.length > 1" type="danger" plain @click="removeComponent(cIndex)">Remove Component</el-button>
                            </div>

                            <div class="pe-form-grid five">
                                <el-form-item label="Component Code" required>
                                    <el-input v-model="component.component_code" placeholder="CMP-001" />
                                </el-form-item>
                                <el-form-item label="Component Name" required>
                                    <el-input v-model="component.component_name" placeholder="Top Lid / Bottom Tray / Main Carton" />
                                </el-form-item>
                                <el-form-item label="Quantity per Product" required>
                                    <el-input-number v-model="component.quantity_per_product" :min="0.0001" :step="1" :controls="false" class="w-100" />
                                </el-form-item>
                                <el-form-item label="Component Type">
                                    <el-select v-model="component.component_type" allow-create filterable clearable placeholder="Type" class="w-100">
                                        <el-option label="Main Carton" value="Main Carton" />
                                        <el-option label="Top Lid" value="Top Lid" />
                                        <el-option label="Bottom Tray" value="Bottom Tray" />
                                        <el-option label="Partition" value="Partition" />
                                        <el-option label="Pad Sheet" value="Pad Sheet" />
                                        <el-option label="Separator" value="Separator" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="Active">
                                    <el-switch v-model="component.is_active" />
                                </el-form-item>
                            </div>

                            <div class="pe-subsection">
                                <h3>Component Specifications</h3>
                                <div class="pe-form-grid six">
                                    <el-form-item label="Length">
                                        <el-input-number v-model="component.specification.length" :min="0" :controls="false" class="w-100" />
                                    </el-form-item>
                                    <el-form-item label="Width">
                                        <el-input-number v-model="component.specification.width" :min="0" :controls="false" class="w-100" />
                                    </el-form-item>
                                    <el-form-item label="Height">
                                        <el-input-number v-model="component.specification.height" :min="0" :controls="false" class="w-100" />
                                    </el-form-item>
                                    <el-form-item label="UOM">
                                        <el-select v-model="component.specification.uom" class="w-100">
                                            <el-option label="mm" value="mm" />
                                            <el-option label="inch" value="inch" />
                                            <el-option label="cm" value="cm" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Ply Type">
                                        <el-select v-model="component.specification.ply_type" class="w-100" @change="applyPlyTemplate(component)">
                                            <el-option label="3 Ply" value="3 Ply" />
                                            <el-option label="5 Ply" value="5 Ply" />
                                            <el-option label="7 Ply" value="7 Ply" />
                                            <el-option label="Custom" value="Custom" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Flute Type">
                                        <el-select v-model="component.specification.flute_type" allow-create filterable clearable class="w-100">
                                            <el-option label="B-Flute" value="B-Flute" />
                                            <el-option label="C-Flute" value="C-Flute" />
                                            <el-option label="E-Flute" value="E-Flute" />
                                            <el-option label="B+C Flute" value="B+C Flute" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Board Grade">
                                        <el-input v-model="component.specification.board_grade" placeholder="e.g. 5 Ply B+C 150/120/150" />
                                    </el-form-item>
                                    <el-form-item label="Joint Type">
                                        <el-select v-model="component.specification.joint_type" clearable class="w-100">
                                            <el-option label="N/A" value="N/A" />
                                            <el-option label="Glue" value="Glue" />
                                            <el-option label="Stitching" value="Stitching" />
                                            <el-option label="Tape" value="Tape" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Printed">
                                        <el-switch v-model="component.specification.is_printed" active-text="Yes" inactive-text="No" />
                                    </el-form-item>
                                    <el-form-item label="Printing Colors">
                                        <el-select v-model="component.specification.printing_colors" class="w-100" @change="syncColorSlots(component)">
                                            <el-option v-for="option in colorCountOptions" :key="option.value" :label="option.label" :value="option.value" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="Bundle Quantity">
                                        <el-input-number v-model="component.specification.bundle_quantity" :min="0" :controls="false" placeholder="No. of Cartons in Each Bundle" class="w-100" />
                                    </el-form-item>
                                </div>
                                <div v-if="Number(component.specification.printing_colors || 0) > 0" class="pe-color-grid">
                                    <el-form-item
                                        v-for="slot in Number(component.specification.printing_colors || 0)"
                                        :key="`${component.local_id}_color_${slot}`"
                                        :label="`Color ${slot}`"
                                    >
                                        <el-select v-model="component.specification.printing_color_codes[slot - 1]" filterable clearable placeholder="Select configured color" class="w-100">
                                            <el-option v-for="color in lookups.printing_colors" :key="color.id" :label="printingColorLabel(color)" :value="color.ink_code">
                                                <span class="pe-color-option">
                                                    <span class="pe-color-chip" :style="{ backgroundColor: color.ink_code }"></span>
                                                    {{ printingColorLabel(color) }}
                                                </span>
                                            </el-option>
                                        </el-select>
                                    </el-form-item>
                                </div>
                                <el-form-item label="Special Instructions">
                                    <el-input v-model="component.specification.special_instructions" type="textarea" :rows="2" placeholder="Component technical remarks and floor instructions" />
                                </el-form-item>
                            </div>

                            <div class="pe-subsection">
                                <div class="pe-subsection-head">
                                    <h3>Board Construction (Paper BOM)</h3>
                                    <div>
                                        <el-button size="small" @click="applyPlyTemplate(component)">Generate Ply Layers</el-button>
                                        <el-button size="small" type="primary" plain @click="addBomLayer(component)">Add Layer</el-button>
                                    </div>
                                </div>
                                <div class="pe-row-list">
                                    <div v-for="(layer, lIndex) in component.bom_layers" :key="layer.local_id" class="pe-bom-row">
                                        <el-input-number v-model="layer.layer_sequence" :min="1" :controls="false" placeholder="Seq" />
                                        <el-input v-model="layer.layer_label" placeholder="Layer e.g. Top Paper" />
                                        <el-select v-model="layer.paper_quality_id" filterable clearable placeholder="Select paper reel item" @change="syncBomPaper(layer)">
                                            <el-option v-for="paper in lookups.paper_qualities" :key="paper.id" :label="paperQualityLabel(paper)" :value="paper.id" />
                                        </el-select>
                                        <el-input v-model="layer.paper_type" readonly placeholder="Paper Name" />
                                        <el-input-number v-model="layer.gsm" :min="1" :controls="false" disabled placeholder="GSM" />
                                        <el-select v-model="layer.supplier_id" filterable clearable placeholder="Supplier optional">
                                            <el-option v-for="supplier in lookups.suppliers" :key="supplier.id" :label="supplier.name" :value="supplier.id" />
                                        </el-select>
                                        <button type="button" class="pe-icon-danger" @click="removeBomLayer(component, lIndex)"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="pe-subsection">
                                <div class="pe-subsection-head">
                                    <h3>Manufacturing Routing & Process Instructions</h3>
                                    <el-button size="small" type="primary" plain @click="addRouting(component)">Add Process</el-button>
                                </div>
                                <div v-for="(routing, rIndex) in component.routings" :key="routing.local_id" class="pe-routing-card">
                                    <div class="pe-form-grid five">
                                        <el-form-item label="Sequence">
                                            <el-input-number v-model="routing.sequence_no" :min="1" :controls="false" class="w-100" />
                                        </el-form-item>
                                        <el-form-item label="Process Name">
                                            <el-select v-model="routing.process_name" filterable allow-create default-first-option placeholder="Process" class="w-100">
                                                <el-option v-for="process in lookups.processes" :key="process.value" :label="process.label" :value="process.value" />
                                            </el-select>
                                        </el-form-item>
                                        <el-form-item label="Process Order">
                                            <el-input-number v-model="routing.process_order" :min="1" :controls="false" class="w-100" />
                                        </el-form-item>
                                        <el-form-item label="Active">
                                            <el-switch v-model="routing.is_active" />
                                        </el-form-item>
                                        <el-form-item label="Remove">
                                            <button type="button" class="pe-icon-danger" @click="removeRouting(component, rIndex)"><i class="bi bi-trash"></i></button>
                                        </el-form-item>
                                    </div>
                                    <el-form-item label="Process Instructions">
                                        <el-input v-model="routing.process_instructions" type="textarea" :rows="2" placeholder="Detailed process instructions" />
                                    </el-form-item>
                                    <div class="pe-parameters">
                                        <div class="pe-subsection-head">
                                            <h4>Unlimited Process Parameters</h4>
                                            <el-button size="small" @click="addParameter(routing)">Add Parameter</el-button>
                                        </div>
                                        <div v-for="(param, pIndex) in routing.parameter_rows" :key="param.local_id" class="pe-param-row">
                                            <el-input v-model="param.key" placeholder="Parameter name e.g. Reel Width" />
                                            <el-input v-model="param.value" placeholder="Value e.g. 47 inch" />
                                            <button type="button" class="pe-icon-danger" @click="removeParameter(routing, pIndex)"><i class="bi bi-x"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </el-tab-pane>
                    </el-tabs>
                </section>
            </el-form>

            <template #footer>
                <el-button @click="editorVisible = false">Discard</el-button>
                <el-button type="primary" class="pe-primary-btn" :loading="saving" @click="saveProduct">
                    <i class="bi bi-check2-circle me-1"></i>{{ editingId ? 'Save Engineering Master' : 'Create Engineering Master' }}
                </el-button>
            </template>
        </el-dialog>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const loading = ref(false);
const saving = ref(false);
const products = ref([]);
const selectedProduct = ref(null);
const editorVisible = ref(false);
const editingId = ref(null);
const editorMode = ref('create');
const activeDetailTab = ref('tree');
const activeComponentKey = ref('');
const formRef = ref(null);
const explodeQty = ref(0);
const explosion = ref({ components: [] });

const filters = reactive({
    search: '',
    customer_id: null,
    product_category: '',
    status: '',
});

const pagination = reactive({
    current_page: 1,
    per_page: 15,
    total: 0,
});

const lookups = reactive({
    customers: [],
    suppliers: [],
    paper_qualities: [],
    printing_colors: [],
    processes: [],
    categories: [],
});

const colorCountOptions = [
    { label: 'Un-Printed', value: 0 },
    { label: '1 Color', value: 1 },
    { label: '2 Colors', value: 2 },
    { label: '3 Colors', value: 3 },
    { label: '4 Colors', value: 4 },
    { label: '5 Colors', value: 5 },
    { label: '6 Colors', value: 6 },
];

const editorTitle = computed(() => {
    if (editorMode.value === 'revision') return 'Create Product Master Revision';
    if (editingId.value) return 'Edit Product Master';
    return 'Create Product Master';
});

const rules = {
    product_code: [{ required: true, message: 'Product Code is required', trigger: 'blur' }],
    product_name: [{ required: true, message: 'Product Name is required', trigger: 'blur' }],
    status: [{ required: true, message: 'Status is required', trigger: 'change' }],
};

const makeId = () => `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;

const defaultSpecification = () => ({
    length: null,
    width: null,
    height: null,
    uom: 'mm',
    ply_type: '3 Ply',
    flute_type: 'B-Flute',
    board_grade: '',
    joint_type: 'Glue',
    is_printed: false,
    printing_colors: 0,
    printing_color_codes: [],
    bundle_quantity: null,
    special_instructions: '',
});

const bomLayer = (sequence = 1, layerLabel = '', paperType = '') => ({
    local_id: makeId(),
    layer_sequence: sequence,
    layer_label: layerLabel,
    paper_type: paperType,
    paper_quality_id: null,
    gsm: null,
    supplier_id: null,
});

const routingRow = (sequence = 1, processName = 'Corrugation') => ({
    local_id: makeId(),
    sequence_no: sequence,
    process_name: processName,
    process_order: sequence,
    is_active: true,
    process_instructions: '',
    parameter_rows: defaultParametersForProcess(processName),
});

const componentRow = (index = 0) => ({
    local_id: makeId(),
    component_code: `CMP-${String(index + 1).padStart(3, '0')}`,
    component_name: `Component ${index + 1}`,
    quantity_per_product: 1,
    component_type: index === 0 ? 'Main Carton' : '',
    is_active: true,
    sort_order: index + 1,
    specification: defaultSpecification(),
    bom_layers: [bomLayer(1, 'Top Layer'), bomLayer(2, 'Flute-B'), bomLayer(3, 'Inner Layer')],
    routings: [
        routingRow(1, 'Corrugation'),
        routingRow(2, 'Packing'),
    ],
});

const defaultForm = () => ({
    product_number: '',
    product_created_date: new Date().toISOString().slice(0, 10),
    product_code: '',
    product_name: '',
    customer_id: null,
    product_category: 'RSC Carton',
    revision_number: 1,
    revision_date: new Date().toISOString().slice(0, 10),
    status: 'Active',
    remarks: '',
    change_notes: '',
    create_revision: false,
    components: [componentRow(0)],
});

const form = reactive(defaultForm());

const previewComponent = computed(() => form.components[0] || componentRow(0));
const previewSpec = computed(() => previewComponent.value.specification || defaultSpecification());

function dimensionText(value) {
    const numeric = Number(value || 0);
    return numeric > 0 ? `${numeric.toLocaleString(undefined, { maximumFractionDigits: 2 })} ${previewSpec.value.uom || 'mm'}` : '--';
}

function defaultParametersForProcess(processName) {
    const presets = {
        Corrugation: ['Reel Width', 'Sheet Width', 'Sheet Length', 'Online Slitting', 'Number of Outs', 'Trim Allowance'],
        'Corrugation + Online Slitting': ['Reel Width', 'Sheet Width', 'Sheet Length', 'Online Slitting', 'Number of Outs', 'Trim Allowance'],
        'Manual Scoring': ['Score Width', 'Score Depth', 'Operator Note'],
        Printing: ['Artwork Code', 'Number of Colors', 'Print Side', 'Print Position'],
        'Printing + Slotting': ['Artwork Code', 'Number of Colors', 'Print Side', 'Slot Width', 'Slot Depth'],
        'Printing + Slotting + Gluing + Packing': ['Artwork Code', 'Number of Colors', 'Slot Width', 'Glue Type', 'Bundle Quantity'],
        'Die Cutting': ['Die Number', 'Crease Layout', 'Slot Dimensions'],
        Gluing: ['Joint Width', 'Glue Type'],
        Stitching: ['Joint Width', 'Stitch Count'],
        Packing: ['Bundle Quantity', 'Pallet Quantity', 'Label Format'],
    };

    return (presets[processName] || ['Parameter']).map(key => ({ local_id: makeId(), key, value: '' }));
}

function resetForm() {
    Object.assign(form, defaultForm());
    activeComponentKey.value = form.components[0].local_id;
    editingId.value = null;
    editorMode.value = 'create';
}

async function fetchLookups() {
    const { data } = await axios.get('/api/product-engineering/lookups');
    Object.assign(lookups, data);
}

async function fetchProducts() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/product-engineering', {
            params: { ...filters, page: pagination.current_page, per_page: pagination.per_page },
        });
        products.value = data.data || [];
        pagination.current_page = data.current_page || 1;
        pagination.per_page = data.per_page || 15;
        pagination.total = data.total || 0;
    } finally {
        loading.value = false;
    }
}

async function refreshAll() {
    await Promise.all([fetchLookups(), fetchProducts()]);
    if (selectedProduct.value?.id) {
        await loadProduct(selectedProduct.value.id);
    }
}

async function selectProduct(row) {
    await loadProduct(row.id);
}

async function loadProduct(id) {
    const { data } = await axios.get(`/api/product-engineering/${id}`);
    selectedProduct.value = data;
    activeDetailTab.value = 'tree';
}

function clearFilters() {
    filters.search = '';
    filters.customer_id = null;
    filters.product_category = '';
    filters.status = '';
    pagination.current_page = 1;
    fetchProducts();
}

async function openEditor(row = null, mode = 'edit') {
    resetForm();
    if (row?.id) {
        const { data } = await axios.get(`/api/product-engineering/${row.id}`);
        hydrateForm(data);
        editingId.value = data.id;
        editorMode.value = mode;
        form.create_revision = mode === 'revision';
        if (mode === 'revision') {
            form.revision_number = Number(data.revision_number || 1) + 1;
            form.revision_date = new Date().toISOString().slice(0, 10);
            form.change_notes = '';
        }
    } else {
        editorMode.value = 'create';
    }
    editorVisible.value = true;
}

function openRevision(row) {
    openEditor(row, 'revision');
}

function hydrateForm(product) {
    Object.assign(form, {
        product_number: product.product_number || '',
        product_created_date: product.product_created_date ? String(product.product_created_date).slice(0, 10) : new Date().toISOString().slice(0, 10),
        product_code: product.product_code || '',
        product_name: product.product_name || '',
        customer_id: product.customer_id || null,
        product_category: product.product_category || '',
        revision_number: product.revision_number || 1,
        revision_date: product.revision_date ? String(product.revision_date).slice(0, 10) : new Date().toISOString().slice(0, 10),
        status: product.status || 'Active',
        remarks: product.remarks || '',
        change_notes: '',
        create_revision: false,
        components: (product.components || []).map((component, index) => ({
            local_id: makeId(),
            component_code: component.component_code,
            component_name: component.component_name,
            quantity_per_product: Number(component.quantity_per_product || 1),
            component_type: component.component_type || '',
            is_active: Boolean(component.is_active),
            sort_order: component.sort_order || index + 1,
            specification: {
                ...defaultSpecification(),
                ...(component.specification || {}),
                printing_color_codes: Array.isArray(component.specification?.printing_color_codes)
                    ? [...component.specification.printing_color_codes]
                    : [],
            },
            bom_layers: (component.bom_layers || []).map(layer => ({
                local_id: makeId(),
                layer_sequence: layer.layer_sequence,
                layer_label: layer.layer_label || '',
                paper_type: layer.paper_type,
                paper_quality_id: layer.paper_quality_id || null,
                gsm: layer.gsm,
                supplier_id: layer.supplier_id,
            })),
            routings: (component.routings || []).map(routing => ({
                local_id: makeId(),
                sequence_no: routing.sequence_no,
                process_name: routing.process_name,
                process_order: routing.process_order,
                is_active: Boolean(routing.is_active),
                process_instructions: routing.process_instructions || '',
                parameter_rows: Object.entries(routing.parameters || {}).map(([key, value]) => ({
                    local_id: makeId(),
                    key,
                    value,
                })),
            })),
        })),
    });
    if (!form.components.length) addComponent();
    form.components.forEach(syncColorSlots);
    activeComponentKey.value = form.components[0].local_id;
}

function addComponent() {
    const component = componentRow(form.components.length);
    form.components.push(component);
    activeComponentKey.value = component.local_id;
}

function removeComponent(index) {
    form.components.splice(index, 1);
    form.components.forEach((component, cIndex) => {
        component.sort_order = cIndex + 1;
    });
    activeComponentKey.value = form.components[0]?.local_id || '';
}

function applyPlyTemplate(component) {
    const ply = component.specification.ply_type;
    const templates = {
        '3 Ply': ['Top Layer', 'Flute-B', 'Inner Layer'],
        '5 Ply': ['Top Layer', 'Flute-B', 'Middle Layer', 'Flute-C', 'Inner Layer'],
        '7 Ply': ['Top Layer', 'Flute-B', 'Middle Layer 1', 'Flute-C', 'Middle Layer 2', 'Flute-B', 'Inner Layer'],
    };
    if (!templates[ply]) return;
    component.bom_layers = templates[ply].map((name, index) => bomLayer(index + 1, name));
}

function addBomLayer(component) {
    component.bom_layers.push(bomLayer(component.bom_layers.length + 1, 'Custom Layer'));
}

function removeBomLayer(component, index) {
    component.bom_layers.splice(index, 1);
    component.bom_layers.forEach((layer, lIndex) => {
        layer.layer_sequence = lIndex + 1;
    });
}

function paperQualityLabel(paper) {
    return `${paper.quality} (${paper.gsm_range || standardGsm(paper) + ' gsm'})`;
}

function standardGsm(paper) {
    return Number(paper?.standard_gsm || paper?.min_gsm || paper?.max_gsm || 0);
}

function syncBomPaper(layer) {
    const paper = lookups.paper_qualities.find(item => item.id === layer.paper_quality_id);
    if (!paper) {
        layer.paper_type = '';
        layer.gsm = null;
        return;
    }
    layer.paper_type = paperQualityLabel(paper);
    layer.gsm = Math.round(standardGsm(paper)) || null;
}

function syncColorSlots(component) {
    const count = Number(component.specification.printing_colors || 0);
    const colors = Array.isArray(component.specification.printing_color_codes)
        ? component.specification.printing_color_codes
        : [];
    component.specification.printing_color_codes = Array.from({ length: count }, (_, index) => colors[index] || '');
    component.specification.is_printed = count > 0;
}

function printingColorLabel(color) {
    return `${color.ink_name} (${color.ink_code})`;
}

function addRouting(component) {
    component.routings.push(routingRow(component.routings.length + 1, 'Corrugation'));
}

function removeRouting(component, index) {
    component.routings.splice(index, 1);
    component.routings.forEach((routing, rIndex) => {
        routing.sequence_no = rIndex + 1;
        routing.process_order = rIndex + 1;
    });
}

function addParameter(routing) {
    routing.parameter_rows.push({ local_id: makeId(), key: '', value: '' });
}

function removeParameter(routing, index) {
    routing.parameter_rows.splice(index, 1);
}

function parametersObject(routing) {
    return (routing.parameter_rows || []).reduce((params, row) => {
        if (row.key) params[row.key] = row.value || '';
        return params;
    }, {});
}

function validateNested() {
    if (editorMode.value === 'revision' && !String(form.change_notes || '').trim()) {
        ElMessage.error('Revision notes are required when creating a Product Master revision.');
        return false;
    }
    for (const component of form.components) {
        if (!component.component_code || !component.component_name) {
            ElMessage.error('Every component requires Component Code and Component Name.');
            return false;
        }
        if (!component.quantity_per_product || Number(component.quantity_per_product) <= 0) {
            ElMessage.error('Every component requires Quantity per Product greater than zero.');
            return false;
        }
        for (const layer of component.bom_layers) {
            if (!layer.layer_sequence || !layer.paper_type) {
                ElMessage.error('Every BOM layer requires sequence and a selected paper reel item.');
                return false;
            }
        }
        for (const routing of component.routings) {
            if (!routing.sequence_no || !routing.process_name) {
                ElMessage.error('Every routing process requires sequence and process name.');
                return false;
            }
        }
    }
    return true;
}

function payload() {
    return {
        product_number: form.product_number,
        product_created_date: form.product_created_date,
        product_code: form.product_code,
        product_name: form.product_name,
        customer_id: form.customer_id,
        product_category: form.product_category,
        revision_number: form.revision_number,
        revision_date: form.revision_date,
        status: form.status,
        remarks: form.remarks,
        change_notes: form.change_notes,
        create_revision: form.create_revision,
        components: form.components.map((component, index) => ({
            component_code: component.component_code,
            component_name: component.component_name,
            quantity_per_product: component.quantity_per_product,
            component_type: component.component_type,
            is_active: component.is_active,
            sort_order: index + 1,
            specification: {
                ...component.specification,
                printing_color_codes: (component.specification.printing_color_codes || [])
                    .slice(0, Number(component.specification.printing_colors || 0))
                    .filter(Boolean),
            },
            bom_layers: component.bom_layers.map((layer, lIndex) => ({
                layer_sequence: layer.layer_sequence || lIndex + 1,
                layer_label: layer.layer_label,
                paper_type: layer.paper_type,
                paper_quality_id: layer.paper_quality_id,
                gsm: layer.gsm,
                supplier_id: layer.supplier_id,
            })),
            routings: component.routings.map((routing, rIndex) => ({
                sequence_no: routing.sequence_no || rIndex + 1,
                process_name: routing.process_name,
                process_order: routing.process_order || rIndex + 1,
                is_active: routing.is_active,
                process_instructions: routing.process_instructions,
                parameters: parametersObject(routing),
            })),
        })),
    };
}

async function saveProduct() {
    const valid = await formRef.value?.validate().catch(() => false);
    if (!valid || !validateNested()) return;

    saving.value = true;
    try {
        const request = editingId.value
            ? axios.put(`/api/product-engineering/${editingId.value}`, payload())
            : axios.post('/api/product-engineering', payload());
        const { data } = await request;
        ElMessage.success(editorMode.value === 'revision'
            ? 'Product Master revision created.'
            : (editingId.value ? 'Product Master updated.' : 'Product Master created.'));
        editorVisible.value = false;
        await fetchProducts();
        selectedProduct.value = data;
    } catch (error) {
        ElMessage.error(error.response?.data?.message || error.response?.data?.error || 'Failed to save product engineering master.');
    } finally {
        saving.value = false;
    }
}

async function deleteProduct(row) {
    try {
        await ElMessageBox.confirm(`Delete Product Engineering Master ${row.product_code}?`, 'Delete Product Master', {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning',
        });
        await axios.delete(`/api/product-engineering/${row.id}`);
        ElMessage.success('Product engineering master deleted.');
        if (selectedProduct.value?.id === row.id) selectedProduct.value = null;
        fetchProducts();
    } catch (error) {
        if (error !== 'cancel' && error !== 'close') {
            ElMessage.error(error.response?.data?.message || 'Failed to delete product engineering master.');
        }
    }
}

async function explodeProduct() {
    if (!selectedProduct.value?.id) return;
    const { data } = await axios.get(`/api/product-engineering/${selectedProduct.value.id}/explode`, {
        params: { quantity: explodeQty.value || 0 },
    });
    explosion.value = data;
}

const formatNumber = (value, decimals = 2) => Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
});

const formatDate = (value) => value ? new Date(value).toLocaleDateString('en-GB') : '-';

onMounted(async () => {
    await refreshAll();
});
</script>

<style scoped>
.pe-module {
    --pe-bg: #f4f7fb;
    --pe-panel: #ffffff;
    --pe-panel-soft: #eef3f9;
    --pe-border: #d6e0ec;
    --pe-text: #0f172a;
    --pe-muted: #66758b;
    --pe-primary: #2f6fed;
    --pe-primary-dark: #1d4ed8;
    min-height: 100%;
    color: var(--pe-text);
}
.pe-header,
.pe-panel,
.pe-filter-card,
.pe-editor-section {
    background: var(--pe-panel);
    border: 1px solid var(--pe-border);
    border-radius: 9px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
}
.pe-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 14px;
    padding: 18px 22px;
}
.pe-eyebrow {
    color: var(--pe-primary);
    display: block;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.pe-header h1,
.pe-panel h2,
.pe-editor-title h2 {
    color: var(--pe-text);
    font-size: 1.45rem;
    font-weight: 900;
    margin: 3px 0;
}
.pe-header p,
.pe-panel-head p {
    color: var(--pe-muted);
    font-weight: 700;
    margin: 0;
}
.pe-actions,
.pe-panel-head,
.pe-editor-title,
.pe-subsection-head,
.pe-component-toolbar,
.pe-explode-bar {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: space-between;
}
.pe-primary-btn {
    background: var(--pe-primary);
    border-color: var(--pe-primary);
    font-weight: 900;
}
.pe-muted-btn,
.pe-clear-btn {
    background: #e8eef6;
    border-color: #d6e0ec;
    color: #19304f;
    font-weight: 900;
}
.pe-filter-card {
    display: grid;
    grid-template-columns: 1.45fr 1fr 1fr 0.8fr auto auto;
    gap: 10px;
    margin-bottom: 14px;
    padding: 14px;
}
.pe-filter-card :deep(.el-input__wrapper),
.pe-filter-card :deep(.el-select__wrapper),
.pe-form :deep(.el-input__wrapper),
.pe-form :deep(.el-select__wrapper),
.pe-form :deep(.el-textarea__inner) {
    border-radius: 8px;
    min-height: 42px;
}
.pe-content-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: minmax(0, 1.05fr) minmax(420px, 0.95fr);
}
.pe-panel {
    padding: 14px;
}
.pe-panel-head {
    margin-bottom: 12px;
}
.pe-count,
.pe-version {
    align-items: center;
    background: #e8f0ff;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    color: #1d4ed8;
    display: inline-flex;
    font-size: 0.76rem;
    font-weight: 900;
    min-height: 26px;
    padding: 0 10px;
}
.pe-table {
    border-radius: 8px;
    overflow: hidden;
}
.pe-table :deep(th.el-table__cell) {
    background: #eaf0f7 !important;
    color: #26364d;
    font-size: 0.78rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.pe-table :deep(td.el-table__cell) {
    color: var(--pe-text);
    font-size: 0.86rem;
    font-weight: 700;
}
.pe-table.compact :deep(td.el-table__cell) {
    font-size: 0.8rem;
}
.pe-status {
    border-radius: 999px;
    display: inline-flex;
    font-size: 0.72rem;
    font-weight: 900;
    padding: 4px 10px;
}
.pe-status.is-active {
    background: #dcfce7;
    color: #166534;
}
.pe-status.is-inactive {
    background: #e5e7eb;
    color: #374151;
}
.pe-dots-btn,
.pe-icon-danger {
    align-items: center;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    display: inline-flex;
    height: 34px;
    justify-content: center;
    width: 34px;
}
.pe-dots-btn {
    background: #edf2f7;
    color: #1e293b;
}
.pe-icon-danger {
    background: #fff1f2;
    color: #dc2626;
}
.pe-pagination {
    display: flex;
    justify-content: center;
    margin-top: 12px;
}
.pe-detail-empty {
    align-items: center;
    color: var(--pe-muted);
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 420px;
    text-align: center;
}
.pe-detail-empty i {
    color: var(--pe-primary);
    font-size: 3rem;
}
.pe-tree {
    display: grid;
    gap: 10px;
}
.pe-tree-node {
    align-items: center;
    background: var(--pe-panel-soft);
    border: 1px solid var(--pe-border);
    border-radius: 8px;
    display: grid;
    gap: 3px 10px;
    grid-template-columns: auto 1fr;
    padding: 12px;
}
.pe-tree-node i {
    color: var(--pe-primary);
    font-size: 1.25rem;
    grid-row: span 2;
}
.pe-tree-node span,
.pe-tree-leaves span {
    color: var(--pe-muted);
    font-size: 0.78rem;
    font-weight: 800;
}
.pe-tree-product {
    background: #e8f0ff;
}
.pe-tree-branch {
    border-left: 3px solid #93c5fd;
    margin-left: 18px;
    padding-left: 12px;
}
.pe-tree-leaves {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 8px 0 4px;
}
.pe-tree-leaves span {
    background: #f8fafc;
    border: 1px solid var(--pe-border);
    border-radius: 999px;
    padding: 5px 9px;
}
.pe-component-summary {
    border: 1px solid var(--pe-border);
    border-radius: 8px;
    margin-bottom: 12px;
    padding: 12px;
}
.pe-component-summary h3,
.pe-subsection h3 {
    font-size: 1rem;
    font-weight: 900;
    margin: 0 0 10px;
}
.pe-two-col {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.pe-mini-table {
    border-collapse: collapse;
    width: 100%;
}
.pe-mini-table th,
.pe-mini-table td {
    border: 1px solid var(--pe-border);
    padding: 7px 8px;
}
.pe-mini-table th {
    background: #edf2f7;
    color: #334155;
    font-size: 0.72rem;
    text-transform: uppercase;
}
.pe-editor-section {
    margin-bottom: 14px;
    padding: 14px;
}
.pe-form-grid {
    display: grid;
    gap: 10px 12px;
}
.pe-form-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.pe-form-grid.four { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.pe-form-grid.five { grid-template-columns: repeat(5, minmax(0, 1fr)); }
.pe-form-grid.six { grid-template-columns: repeat(6, minmax(0, 1fr)); }
.pe-master-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: minmax(0, 1.18fr) minmax(360px, 0.82fr);
}
.pe-master-form {
    min-width: 0;
}
.pe-master-visuals {
    display: grid;
    gap: 12px;
}
.pe-visual-card {
    background: #f8fafc;
    border: 1px solid var(--pe-border);
    border-radius: 8px;
    min-height: 214px;
    padding: 12px;
}
.pe-visual-head {
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}
.pe-visual-head span {
    color: #26364d;
    font-size: 0.82rem;
    font-weight: 900;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.pe-visual-head small {
    color: var(--pe-muted);
    font-size: 0.72rem;
    font-weight: 800;
}
.pe-carton-svg,
.pe-dieline-svg {
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    height: 174px;
    width: 100%;
}
.pe-carton-svg text,
.pe-dieline-svg text {
    fill: #0f172a;
    font-size: 13px;
    font-weight: 900;
}
.pe-svg-panel,
.pe-svg-flap {
    fill: #ffffff;
    stroke: #111827;
    stroke-linejoin: round;
    stroke-width: 2;
}
.pe-svg-flap {
    fill: #f8fafc;
}
.pe-svg-dash {
    fill: none;
    stroke: #334155;
    stroke-dasharray: 4 4;
    stroke-width: 1.5;
}
.pe-svg-arrow {
    fill: none;
    stroke: #111827;
    stroke-width: 1.8;
}
.pe-dieline-svg marker path {
    fill: #111827;
}
.pe-detail-actions {
    align-items: center;
    display: flex;
    gap: 8px;
}
.pe-color-grid {
    display: grid;
    gap: 10px 12px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.pe-color-option {
    align-items: center;
    display: inline-flex;
    gap: 8px;
}
.pe-color-chip {
    border: 1px solid #94a3b8;
    border-radius: 999px;
    display: inline-block;
    height: 12px;
    width: 12px;
}
.pe-form :deep(.el-form-item__label) {
    color: #52627a;
    font-size: 0.78rem;
    font-weight: 900;
    line-height: 1.2;
}
.pe-component-tabs {
    border-radius: 8px;
    overflow: hidden;
}
.pe-subsection {
    background: #f8fafc;
    border: 1px solid var(--pe-border);
    border-radius: 8px;
    margin-top: 12px;
    padding: 12px;
}
.pe-row-list {
    display: grid;
    gap: 8px;
}
.pe-bom-row {
    display: grid;
    gap: 8px;
    grid-template-columns: 74px 1fr 1.35fr 1.25fr 92px 1fr 42px;
}
.pe-routing-card {
    background: #fff;
    border: 1px solid var(--pe-border);
    border-radius: 8px;
    margin-top: 10px;
    padding: 12px;
}
.pe-parameters {
    background: #eef3f9;
    border-radius: 8px;
    padding: 10px;
}
.pe-parameters h4 {
    font-size: 0.86rem;
    font-weight: 900;
    margin: 0;
}
.pe-param-row {
    display: grid;
    gap: 8px;
    grid-template-columns: 1fr 1fr 38px;
    margin-top: 8px;
}
.pe-empty {
    color: var(--pe-muted);
    font-weight: 800;
    padding: 26px;
}
.pe-explode-bar {
    justify-content: flex-start;
    margin-bottom: 12px;
}

:global([data-theme="dark"]) .pe-module,
:global(body.dark-mode) .pe-module {
    --pe-bg: #0f172a;
    --pe-panel: #182235;
    --pe-panel-soft: #223047;
    --pe-border: #40516c;
    --pe-text: #f8fafc;
    --pe-muted: #b7c5da;
    --pe-primary: #60a5fa;
    --pe-primary-dark: #3b82f6;
}
:global([data-theme="dark"]) .pe-header,
:global([data-theme="dark"]) .pe-panel,
:global([data-theme="dark"]) .pe-filter-card,
:global([data-theme="dark"]) .pe-editor-section,
:global(body.dark-mode) .pe-header,
:global(body.dark-mode) .pe-panel,
:global(body.dark-mode) .pe-filter-card,
:global(body.dark-mode) .pe-editor-section {
    background: var(--pe-panel);
    border-color: var(--pe-border);
    box-shadow: none;
}
:global([data-theme="dark"]) .pe-muted-btn,
:global([data-theme="dark"]) .pe-clear-btn,
:global(body.dark-mode) .pe-muted-btn,
:global(body.dark-mode) .pe-clear-btn {
    background: #26344c;
    border-color: #40516c;
    color: #e2e8f0;
}
:global([data-theme="dark"]) .pe-table,
:global([data-theme="dark"]) .pe-table :deep(.el-table__inner-wrapper),
:global([data-theme="dark"]) .pe-table :deep(tr),
:global(body.dark-mode) .pe-table,
:global(body.dark-mode) .pe-table :deep(.el-table__inner-wrapper),
:global(body.dark-mode) .pe-table :deep(tr) {
    background: #182235 !important;
}
:global([data-theme="dark"]) .pe-table :deep(th.el-table__cell),
:global(body.dark-mode) .pe-table :deep(th.el-table__cell) {
    background: #223047 !important;
    color: #dbeafe;
}
:global([data-theme="dark"]) .pe-table :deep(td.el-table__cell),
:global(body.dark-mode) .pe-table :deep(td.el-table__cell) {
    background: #182235 !important;
    border-color: #40516c;
    color: #f8fafc;
}
:global([data-theme="dark"]) .pe-form :deep(.el-input__wrapper),
:global([data-theme="dark"]) .pe-form :deep(.el-select__wrapper),
:global([data-theme="dark"]) .pe-filter-card :deep(.el-input__wrapper),
:global([data-theme="dark"]) .pe-filter-card :deep(.el-select__wrapper),
:global([data-theme="dark"]) .pe-form :deep(.el-textarea__inner),
:global(body.dark-mode) .pe-form :deep(.el-input__wrapper),
:global(body.dark-mode) .pe-form :deep(.el-select__wrapper),
:global(body.dark-mode) .pe-filter-card :deep(.el-input__wrapper),
:global(body.dark-mode) .pe-filter-card :deep(.el-select__wrapper),
:global(body.dark-mode) .pe-form :deep(.el-textarea__inner) {
    background: #1d293d !important;
    border-color: #40516c !important;
    box-shadow: 0 0 0 1px #40516c inset !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"]) .pe-form :deep(.el-input__inner),
:global([data-theme="dark"]) .pe-form :deep(.el-select__selected-item),
:global([data-theme="dark"]) .pe-filter-card :deep(.el-input__inner),
:global([data-theme="dark"]) .pe-filter-card :deep(.el-select__selected-item),
:global(body.dark-mode) .pe-form :deep(.el-input__inner),
:global(body.dark-mode) .pe-form :deep(.el-select__selected-item),
:global(body.dark-mode) .pe-filter-card :deep(.el-input__inner),
:global(body.dark-mode) .pe-filter-card :deep(.el-select__selected-item) {
    color: #f8fafc !important;
}
:global([data-theme="dark"]) .pe-form :deep(.el-form-item__label),
:global(body.dark-mode) .pe-form :deep(.el-form-item__label) {
    color: #cbd5e1;
}
:global([data-theme="dark"]) .pe-subsection,
:global([data-theme="dark"]) .pe-parameters,
:global([data-theme="dark"]) .pe-routing-card,
:global([data-theme="dark"]) .pe-component-summary,
:global([data-theme="dark"]) .pe-tree-node,
:global([data-theme="dark"]) .pe-tree-leaves span,
:global(body.dark-mode) .pe-subsection,
:global(body.dark-mode) .pe-parameters,
:global(body.dark-mode) .pe-routing-card,
:global(body.dark-mode) .pe-component-summary,
:global(body.dark-mode) .pe-tree-node,
:global(body.dark-mode) .pe-tree-leaves span {
    background: #1d293d;
    border-color: #40516c;
}
:global([data-theme="dark"]) .pe-mini-table th,
:global(body.dark-mode) .pe-mini-table th {
    background: #223047;
    color: #e2e8f0;
}
:global([data-theme="dark"]) .pe-mini-table td,
:global(body.dark-mode) .pe-mini-table td {
    border-color: #40516c;
    color: #f8fafc;
}
:global([data-theme="dark"]) .pe-visual-card,
:global(body.dark-mode) .pe-visual-card {
    background: #1d293d;
    border-color: #40516c;
}
:global([data-theme="dark"]) .pe-visual-head span,
:global(body.dark-mode) .pe-visual-head span {
    color: #dbeafe;
}
:global([data-theme="dark"]) .pe-carton-svg,
:global([data-theme="dark"]) .pe-dieline-svg,
:global(body.dark-mode) .pe-carton-svg,
:global(body.dark-mode) .pe-dieline-svg {
    background: #f8fafc;
    border-color: #64748b;
}
:global([data-theme="dark"]) .pe-carton-svg text,
:global([data-theme="dark"]) .pe-dieline-svg text,
:global(body.dark-mode) .pe-carton-svg text,
:global(body.dark-mode) .pe-dieline-svg text {
    fill: #0f172a !important;
}
:global([data-theme="dark"]) .pe-svg-panel,
:global([data-theme="dark"]) .pe-svg-flap,
:global(body.dark-mode) .pe-svg-panel,
:global(body.dark-mode) .pe-svg-flap {
    stroke: #111827;
}

:global([data-theme="dark"] .pe-module),
:global(body.dark-mode .pe-module) {
    background: #0f172a;
    color: #f8fafc;
}
:global([data-theme="dark"] .pe-module .pe-header),
:global([data-theme="dark"] .pe-module .pe-panel),
:global([data-theme="dark"] .pe-module .pe-filter-card),
:global([data-theme="dark"] .pe-module .pe-editor-section),
:global(body.dark-mode .pe-module .pe-header),
:global(body.dark-mode .pe-module .pe-panel),
:global(body.dark-mode .pe-module .pe-filter-card),
:global(body.dark-mode .pe-module .pe-editor-section) {
    background: #172033 !important;
    border-color: #334155 !important;
    box-shadow: none !important;
}
:global([data-theme="dark"] .pe-module h1),
:global([data-theme="dark"] .pe-module h2),
:global([data-theme="dark"] .pe-module h3),
:global([data-theme="dark"] .pe-module h4),
:global([data-theme="dark"] .pe-module strong),
:global(body.dark-mode .pe-module h1),
:global(body.dark-mode .pe-module h2),
:global(body.dark-mode .pe-module h3),
:global(body.dark-mode .pe-module h4),
:global(body.dark-mode .pe-module strong) {
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-module p),
:global([data-theme="dark"] .pe-module .pe-panel-head p),
:global([data-theme="dark"] .pe-module .pe-tree-node span),
:global([data-theme="dark"] .pe-module .pe-tree-leaves span),
:global(body.dark-mode .pe-module p),
:global(body.dark-mode .pe-module .pe-panel-head p),
:global(body.dark-mode .pe-module .pe-tree-node span),
:global(body.dark-mode .pe-module .pe-tree-leaves span) {
    color: #b7c5da !important;
}
:global([data-theme="dark"] .pe-module .el-input__wrapper),
:global([data-theme="dark"] .pe-module .el-select__wrapper),
:global([data-theme="dark"] .pe-module .el-input-number .el-input__wrapper),
:global([data-theme="dark"] .pe-module .el-textarea__inner),
:global(body.dark-mode .pe-module .el-input__wrapper),
:global(body.dark-mode .pe-module .el-select__wrapper),
:global(body.dark-mode .pe-module .el-input-number .el-input__wrapper),
:global(body.dark-mode .pe-module .el-textarea__inner) {
    background: #1f2b3f !important;
    border: 1px solid #40516c !important;
    box-shadow: none !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-module .el-input__inner),
:global([data-theme="dark"] .pe-module .el-select__selected-item),
:global([data-theme="dark"] .pe-module .el-select__placeholder),
:global([data-theme="dark"] .pe-module .el-textarea__inner),
:global(body.dark-mode .pe-module .el-input__inner),
:global(body.dark-mode .pe-module .el-select__selected-item),
:global(body.dark-mode .pe-module .el-select__placeholder),
:global(body.dark-mode .pe-module .el-textarea__inner) {
    color: #f8fafc !important;
    font-weight: 800;
}
:global([data-theme="dark"] .pe-module .el-input__inner::placeholder),
:global([data-theme="dark"] .pe-module .el-textarea__inner::placeholder),
:global(body.dark-mode .pe-module .el-input__inner::placeholder),
:global(body.dark-mode .pe-module .el-textarea__inner::placeholder) {
    color: #8ea0ba !important;
    opacity: 1;
}
:global([data-theme="dark"] .pe-module .el-form-item__label),
:global(body.dark-mode .pe-module .el-form-item__label) {
    color: #cbd5e1 !important;
}
:global([data-theme="dark"] .pe-module .el-table),
:global([data-theme="dark"] .pe-module .el-table__inner-wrapper),
:global([data-theme="dark"] .pe-module .el-table__header-wrapper),
:global([data-theme="dark"] .pe-module .el-table__body-wrapper),
:global([data-theme="dark"] .pe-module .el-table__empty-block),
:global([data-theme="dark"] .pe-module .el-table tr),
:global(body.dark-mode .pe-module .el-table),
:global(body.dark-mode .pe-module .el-table__inner-wrapper),
:global(body.dark-mode .pe-module .el-table__header-wrapper),
:global(body.dark-mode .pe-module .el-table__body-wrapper),
:global(body.dark-mode .pe-module .el-table__empty-block),
:global(body.dark-mode .pe-module .el-table tr) {
    background: #172033 !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-module .el-table th.el-table__cell),
:global(body.dark-mode .pe-module .el-table th.el-table__cell) {
    background: #223047 !important;
    border-color: #40516c !important;
    color: #dbeafe !important;
}
:global([data-theme="dark"] .pe-module .el-table td.el-table__cell),
:global(body.dark-mode .pe-module .el-table td.el-table__cell) {
    background: #172033 !important;
    border-color: #40516c !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-module .el-table__row:hover td.el-table__cell),
:global(body.dark-mode .pe-module .el-table__row:hover td.el-table__cell) {
    background: #223047 !important;
}
:global([data-theme="dark"] .pe-module .el-table__empty-text),
:global(body.dark-mode .pe-module .el-table__empty-text) {
    color: #b7c5da !important;
}
:global([data-theme="dark"] .pe-module .pe-tree-node),
:global([data-theme="dark"] .pe-module .pe-tree-product),
:global([data-theme="dark"] .pe-module .pe-tree-leaves span),
:global([data-theme="dark"] .pe-module .pe-component-summary),
:global([data-theme="dark"] .pe-module .pe-subsection),
:global([data-theme="dark"] .pe-module .pe-routing-card),
:global([data-theme="dark"] .pe-module .pe-parameters),
:global(body.dark-mode .pe-module .pe-tree-node),
:global(body.dark-mode .pe-module .pe-tree-product),
:global(body.dark-mode .pe-module .pe-tree-leaves span),
:global(body.dark-mode .pe-module .pe-component-summary),
:global(body.dark-mode .pe-module .pe-subsection),
:global(body.dark-mode .pe-module .pe-routing-card),
:global(body.dark-mode .pe-module .pe-parameters) {
    background: #1f2b3f !important;
    border-color: #40516c !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-module .pe-mini-table th),
:global(body.dark-mode .pe-module .pe-mini-table th) {
    background: #223047 !important;
    border-color: #40516c !important;
    color: #dbeafe !important;
}
:global([data-theme="dark"] .pe-module .pe-mini-table td),
:global(body.dark-mode .pe-module .pe-mini-table td) {
    background: #172033 !important;
    border-color: #40516c !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-module .pe-version),
:global([data-theme="dark"] .pe-module .pe-count),
:global(body.dark-mode .pe-module .pe-version),
:global(body.dark-mode .pe-module .pe-count) {
    background: rgba(59, 130, 246, 0.18) !important;
    border-color: rgba(96, 165, 250, 0.45) !important;
    color: #dbeafe !important;
}
:global([data-theme="dark"] .pe-module .pe-status.is-active),
:global(body.dark-mode .pe-module .pe-status.is-active) {
    background: rgba(34, 197, 94, 0.18) !important;
    border: 1px solid rgba(74, 222, 128, 0.4);
    color: #bbf7d0 !important;
}
:global([data-theme="dark"] .pe-module .pe-status.is-inactive),
:global(body.dark-mode .pe-module .pe-status.is-inactive) {
    background: rgba(148, 163, 184, 0.18) !important;
    border: 1px solid rgba(148, 163, 184, 0.35);
    color: #cbd5e1 !important;
}
:global([data-theme="dark"] .pe-module .pe-dots-btn),
:global(body.dark-mode .pe-module .pe-dots-btn) {
    background: #26344c !important;
    border-color: #4b5f7d !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-module .pe-icon-danger),
:global(body.dark-mode .pe-module .pe-icon-danger) {
    background: rgba(239, 68, 68, 0.14) !important;
    border-color: rgba(248, 113, 113, 0.42) !important;
    color: #fecaca !important;
}
:global([data-theme="dark"] .pe-module .el-tabs__header),
:global([data-theme="dark"] .pe-module .el-tabs__nav-wrap),
:global([data-theme="dark"] .pe-module .el-tabs__content),
:global([data-theme="dark"] .pe-module .el-tabs--border-card),
:global(body.dark-mode .pe-module .el-tabs__header),
:global(body.dark-mode .pe-module .el-tabs__nav-wrap),
:global(body.dark-mode .pe-module .el-tabs__content),
:global(body.dark-mode .pe-module .el-tabs--border-card) {
    background: #172033 !important;
    border-color: #40516c !important;
}
:global([data-theme="dark"] .pe-module .el-tabs__item),
:global(body.dark-mode .pe-module .el-tabs__item) {
    color: #b7c5da !important;
    font-weight: 900;
}
:global([data-theme="dark"] .pe-module .el-tabs__item.is-active),
:global(body.dark-mode .pe-module .el-tabs__item.is-active) {
    color: #93c5fd !important;
}
:global([data-theme="dark"] .pe-module .el-pagination button),
:global([data-theme="dark"] .pe-module .el-pager li),
:global(body.dark-mode .pe-module .el-pagination button),
:global(body.dark-mode .pe-module .el-pager li) {
    background: #223047 !important;
    color: #e2e8f0 !important;
}
:global([data-theme="dark"] .pe-module .el-pager li.is-active),
:global(body.dark-mode .pe-module .el-pager li.is-active) {
    background: #4f46e5 !important;
    color: #fff !important;
}
:global([data-theme="dark"] .pe-editor-dialog),
:global(body.dark-mode .pe-editor-dialog) {
    background: #172033 !important;
    border: 1px solid #40516c !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.42) !important;
}
:global([data-theme="dark"] .pe-editor-dialog .el-dialog__header),
:global([data-theme="dark"] .pe-editor-dialog .el-dialog__body),
:global([data-theme="dark"] .pe-editor-dialog .el-dialog__footer),
:global(body.dark-mode .pe-editor-dialog .el-dialog__header),
:global(body.dark-mode .pe-editor-dialog .el-dialog__body),
:global(body.dark-mode .pe-editor-dialog .el-dialog__footer) {
    background: #172033 !important;
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-editor-dialog .el-dialog__title),
:global([data-theme="dark"] .pe-editor-dialog .el-dialog__close),
:global(body.dark-mode .pe-editor-dialog .el-dialog__title),
:global(body.dark-mode .pe-editor-dialog .el-dialog__close) {
    color: #f8fafc !important;
}
:global([data-theme="dark"] .pe-editor-dialog .el-tabs--border-card),
:global([data-theme="dark"] .pe-editor-dialog .el-tabs__header),
:global([data-theme="dark"] .pe-editor-dialog .el-tabs__content),
:global(body.dark-mode .pe-editor-dialog .el-tabs--border-card),
:global(body.dark-mode .pe-editor-dialog .el-tabs__header),
:global(body.dark-mode .pe-editor-dialog .el-tabs__content) {
    background: #172033 !important;
    border-color: #40516c !important;
}
:global([data-theme="dark"] .pe-editor-dialog .el-tabs__item),
:global(body.dark-mode .pe-editor-dialog .el-tabs__item) {
    color: #b7c5da !important;
}
:global([data-theme="dark"] .pe-editor-dialog .el-tabs__item.is-active),
:global(body.dark-mode .pe-editor-dialog .el-tabs__item.is-active) {
    background: #1f2b3f !important;
    color: #93c5fd !important;
}

@media (max-width: 1200px) {
    .pe-content-grid,
    .pe-master-grid,
    .pe-two-col,
    .pe-form-grid.four,
    .pe-form-grid.five,
    .pe-form-grid.six {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .pe-filter-card {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .pe-header,
    .pe-actions,
    .pe-panel-head,
    .pe-editor-title {
        align-items: flex-start;
        flex-direction: column;
    }
    .pe-content-grid,
    .pe-master-grid,
    .pe-filter-card,
    .pe-form-grid.two,
    .pe-form-grid.four,
    .pe-form-grid.five,
    .pe-form-grid.six,
    .pe-color-grid,
    .pe-bom-row,
    .pe-param-row {
        grid-template-columns: 1fr;
    }
}
</style>
