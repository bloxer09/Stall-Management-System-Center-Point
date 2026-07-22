<?php
$gridRows = $currentLocation['grid_rows'] ?? 6;
$gridCols = $currentLocation['grid_cols'] ?? 8;

// Group stalls by row and col
$gridStalls = [];
$unassignedStalls = [];

foreach ($stalls as $stall) {
    if ($stall['grid_row'] !== null && $stall['grid_col'] !== null) {
        $gridStalls[(int)$stall['grid_row']][(int)$stall['grid_col']] = $stall;
    } else {
        $unassignedStalls[] = $stall;
    }
}
?>

<div class="space-y-4">
    <!-- Top Filter Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-base-100 p-4 rounded-xl shadow-sm border border-base-200">
        <div>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
            <p class="text-xs opacity-75 mt-0.5">Floor location: <span class="font-bold text-primary"><?= htmlspecialchars($currentLocation['name'] ?? 'None') ?></span> (<?= $gridRows ?> x <?= $gridCols ?> grid)</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <!-- Floor select -->
            <form method="GET" action="" class="flex items-center gap-2 shrink-0">
                <select name="location_id" onchange="this.form.submit()" class="select select-bordered select-sm">
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['id'] ?>" <?= (int)$loc['id'] === (int)$locationId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="divider divider-horizontal hidden sm:inline-flex m-0 w-0"></div>
            <a href="<?= \App\Config\App::basePath() ?>/admin/stalls" class="btn btn-ghost btn-sm">
                <i data-lucide="list" class="w-4 h-4"></i> List View
            </a>
        </div>
    </div>

    <!-- Main Builder Layout -->
    <div class="flex flex-col xl:flex-row gap-6">
        <!-- Visual Grid -->
        <div class="flex-1 bg-base-100 p-5 rounded-2xl shadow-sm border border-base-200 flex flex-col justify-between">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <i data-lucide="grid" class="w-5 h-5 text-primary"></i> Floor Layout Plan
                </h3>
                
                <!-- Status Legend -->
                <div class="flex flex-wrap gap-3 text-xs">
                    <span class="flex items-center gap-1.5 font-medium"><span class="w-3 h-3 bg-success/20 border border-success rounded-sm"></span> Available</span>
                    <span class="flex items-center gap-1.5 font-medium"><span class="w-3 h-3 bg-error/20 border border-error rounded-sm"></span> Occupied</span>
                    <span class="flex items-center gap-1.5 font-medium"><span class="w-3 h-3 bg-warning/20 border border-warning rounded-sm"></span> Maintenance</span>
                </div>
            </div>

            <!-- SVG Grid Container -->
            <!-- Mobile scroll indicator helper -->
            <div class="inline-flex md:hidden items-center gap-1.5 alert alert-info py-1.5 px-3 text-xs mb-3 shadow-sm w-full">
                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                <span>Swipe left or right to pan the floor directory plan.</span>
            </div>

            <div class="grid-wrap overflow-auto p-1 bg-base-200/50 rounded-xl border border-base-200">
                <div class="grid gap-3 p-4 select-none"
                     style="display: grid; grid-template-rows: repeat(<?= $gridRows ?>, minmax(110px, auto)); grid-template-columns: repeat(<?= $gridCols ?>, minmax(140px, 1fr)); min-width: 800px;">
                    <?php for ($r = 1; $r <= $gridRows; $r++): ?>
                        <?php for ($c = 1; $c <= $gridCols; $c++): ?>
                            <?php 
                            $stall = $gridStalls[$r][$c] ?? null;
                            $mapping = $stall ? ($activeMappings[(int)$stall['id']] ?? null) : null;
                            ?>
                            <div class="grid-cell border border-base-300/60 bg-base-100 rounded-xl flex items-center justify-center relative transition-all duration-200 min-h-[110px]"
                                 data-row="<?= $r ?>" data-col="<?= $c ?>"
                                 ondragover="allowDrop(event)" ondrop="handleDrop(event)">
                                
                                <?php if ($stall): ?>
                                    <!-- Stall block -->
                                    <div class="stall-card p-4 rounded-lg shadow-sm cursor-pointer w-full h-full flex flex-col justify-between transition-all duration-200 hover:-translate-y-1 hover:shadow-md
                                        <?= $stall['status'] === 'available' ? 'bg-success/10 border-2 border-success text-success-content hover:bg-success/15' : '' ?>
                                        <?= $stall['status'] === 'occupied' ? 'bg-error/10 border-2 border-error text-error-content hover:bg-error/15' : '' ?>
                                        <?= $stall['status'] === 'maintenance' ? 'bg-warning/10 border-2 border-warning text-warning-content hover:bg-warning/15' : '' ?>"
                                         id="stall-<?= $stall['id'] ?>"
                                         data-stall-id="<?= $stall['id'] ?>"
                                         data-stall-number="<?= htmlspecialchars($stall['stall_number']) ?>"
                                         data-status="<?= $stall['status'] ?>"
                                         data-price="<?= number_format($stall['price'], 2) ?>"
                                         data-description="<?= htmlspecialchars($stall['description'] ?? '') ?>"
                                         data-tenant-name="<?= htmlspecialchars($mapping['user_name'] ?? '') ?>"
                                         data-tenant-email="<?= htmlspecialchars($mapping['user_email'] ?? '') ?>"
                                         data-tenant-phone="<?= htmlspecialchars($mapping['user_phone'] ?? '') ?>"
                                         data-start-date="<?= htmlspecialchars($mapping['start_date'] ?? '') ?>"
                                         data-end-date="<?= htmlspecialchars($mapping['end_date'] ?? '') ?>"
                                         data-contract-doc="<?= htmlspecialchars($mapping['contract_document'] ?? '') ?>"
                                         draggable="false"
                                         ondragstart="handleDragStart(event)"
                                         onclick="showStallDetails(this)">
                                        
                                        <div class="flex justify-between items-start gap-1">
                                            <span class="font-bold text-sm tracking-wider uppercase flex items-center gap-1">
                                                <i data-lucide="grip-horizontal" class="w-3.5 h-3.5 opacity-40 drag-handle hidden"></i>
                                                <?= htmlspecialchars($stall['stall_number']) ?>
                                            </span>
                                            <span class="badge badge-xs uppercase font-semibold
                                                <?= $stall['status'] === 'available' ? 'badge-success text-white' : '' ?>
                                                <?= $stall['status'] === 'occupied' ? 'badge-error text-white' : '' ?>
                                                <?= $stall['status'] === 'maintenance' ? 'badge-warning text-white' : '' ?>">
                                                <?= $stall['status'] ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mt-2 text-xs">
                                            <div class="font-bold text-sm">PHP <?= number_format($stall['price'], 0) ?></div>
                                            <?php if ($mapping): ?>
                                                <div class="mt-1 text-[11px] font-semibold truncate flex items-center gap-1 opacity-90">
                                                    <i data-lucide="user" class="w-3 h-3 shrink-0"></i>
                                                    <span class="truncate"><?= htmlspecialchars($mapping['user_name']) ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-1 text-[10px] opacity-55">Vacant</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Empty cell indicators -->
                                    <span class="empty-indicator opacity-20 text-[10px] font-mono pointer-events-none select-none"><?= $r ?>-<?= $c ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar controls & drag container -->
        <div class="w-full xl:w-80 flex flex-col gap-4 shrink-0">
            <!-- Edit layout toggle -->
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5">
                    <h2 class="card-title text-base flex justify-between items-center mb-1">
                        <span>Layout Builder</span>
                        <i data-lucide="layout" class="w-4 h-4 opacity-60"></i>
                    </h2>
                    <p class="text-xs opacity-75">Rearrange and position stalls interactively. Change rows and cols dimensions inside location settings.</p>
                    
                    <div class="form-control mt-4">
                        <label class="label cursor-pointer justify-between bg-base-200/50 p-3 rounded-lg border border-base-300">
                            <span class="label-text font-bold flex items-center gap-2">
                                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Layout Mode
                            </span>
                            <input type="checkbox" id="edit-layout-toggle" class="toggle toggle-primary" onchange="toggleEditMode(this.checked)" />
                        </label>
                    </div>
                </div>
            </div>

            <!-- Remove Dropzone -->
            <div id="remove-dropzone" class="hidden card bg-error/10 border-2 border-dashed border-error/30 text-error p-6 rounded-xl flex flex-col items-center justify-center gap-2 transition-all duration-200 hover:bg-error/20"
                 ondragover="allowDrop(event)" ondrop="handleRemoveDrop(event)">
                <i data-lucide="trash-2" class="w-8 h-8"></i>
                <span class="text-sm font-semibold">Remove from Grid</span>
                <span class="text-xs opacity-75">Drag here to unassign from map</span>
            </div>

            <!-- Unassigned Stalls -->
            <div class="card bg-base-100 shadow-sm border border-base-200 flex-1">
                <div class="card-body p-5 flex flex-col h-full min-h-[300px]">
                    <h2 class="card-title text-base flex justify-between items-center mb-1">
                        <span>Unassigned Stalls</span>
                        <span class="badge badge-sm" id="unassigned-count"><?= count($unassignedStalls) ?></span>
                    </h2>
                    <p class="text-xs opacity-75 mb-3">Stalls without coordinates. Drag them onto grid cells.</p>
                    
                    <div id="unassigned-container" class="flex flex-col gap-2 overflow-y-auto max-h-[400px] p-2 border border-dashed border-base-300 bg-base-200/40 rounded-xl min-h-[200px]"
                         ondragover="allowDrop(event)" ondrop="handleUnassignedDrop(event)">
                        <?php if (empty($unassignedStalls)): ?>
                            <div class="text-center py-12 opacity-50 text-xs empty-msg">All stalls placed.</div>
                        <?php else: ?>
                            <?php foreach ($unassignedStalls as $stall): ?>
                                <div class="stall-card p-3 rounded-lg border border-base-200 bg-base-100 flex justify-between items-center transition-all duration-200 hover:shadow-sm"
                                     id="stall-<?= $stall['id'] ?>"
                                     data-stall-id="<?= $stall['id'] ?>"
                                     data-stall-number="<?= htmlspecialchars($stall['stall_number']) ?>"
                                     data-status="<?= $stall['status'] ?>"
                                     data-price="<?= number_format($stall['price'], 2) ?>"
                                     data-description="<?= htmlspecialchars($stall['description'] ?? '') ?>"
                                     draggable="false"
                                     ondragstart="handleDragStart(event)"
                                     onclick="showStallDetails(this)">
                                    
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="grip-vertical" class="w-4 h-4 opacity-40 drag-handle hidden shrink-0"></i>
                                        <div>
                                            <div class="font-bold text-sm uppercase"><?= htmlspecialchars($stall['stall_number']) ?></div>
                                            <div class="text-xs opacity-70">PHP <?= number_format($stall['price'], 0) ?>/mo</div>
                                        </div>
                                    </div>
                                    <span class="badge badge-sm uppercase font-semibold
                                        <?= $stall['status'] === 'available' ? 'badge-success text-white' : '' ?>
                                        <?= $stall['status'] === 'occupied' ? 'badge-error text-white' : '' ?>
                                        <?= $stall['status'] === 'maintenance' ? 'badge-warning text-white' : '' ?>">
                                        <?= $stall['status'] ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dialog -->
<dialog id="stallDetailsModal" class="modal">
    <div class="modal-box max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-xl mb-2 flex items-center gap-2">
            <span>Stall Details</span>
            <span id="md-stall-number" class="text-primary font-mono font-bold"></span>
        </h3>
        
        <div class="space-y-4 mt-4">
            <div class="grid grid-cols-2 gap-4 bg-base-200/50 p-4 rounded-xl border border-base-200">
                <div>
                    <div class="text-xs opacity-60 font-semibold">Rent Price</div>
                    <div class="text-lg font-bold text-primary" id="md-price"></div>
                </div>
                <div>
                    <div class="text-xs opacity-60 font-semibold">Status</div>
                    <span class="badge badge-sm font-semibold uppercase mt-1" id="md-status"></span>
                </div>
            </div>

            <div>
                <div class="text-xs opacity-60 font-semibold mb-1">Description</div>
                <p class="text-sm bg-base-100 p-3 rounded-lg border border-base-200 min-h-[50px] whitespace-pre-wrap" id="md-description"></p>
            </div>

            <!-- Tenant Section -->
            <div id="md-tenant-section" class="hidden card bg-base-200/30 border border-base-200">
                <div class="card-body p-4">
                    <h4 class="font-bold text-sm flex items-center gap-2 border-b border-base-200 pb-2 mb-2">
                        <i data-lucide="user" class="w-4 h-4 text-primary"></i> Tenant Lease Agreement
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="opacity-60 block">Tenant Name</span>
                            <span class="font-semibold" id="md-tenant-name"></span>
                        </div>
                        <div>
                            <span class="opacity-60 block">Phone Number</span>
                            <span class="font-semibold" id="md-tenant-phone"></span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="opacity-60 block">Email Address</span>
                            <span class="font-semibold" id="md-tenant-email"></span>
                        </div>
                        <div>
                            <span class="opacity-60 block">Lease Start</span>
                            <span class="font-semibold text-success" id="md-start-date"></span>
                        </div>
                        <div>
                            <span class="opacity-60 block">Lease End</span>
                            <span class="font-semibold text-error" id="md-end-date"></span>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex gap-2 border-t border-base-200 pt-3" id="md-tenant-actions">
                        <!-- Filled in dynamically -->
                    </div>
                </div>
            </div>

            <div class="modal-action flex justify-between gap-2 border-t border-base-200 pt-4 mt-4">
                <div class="flex gap-2" id="md-admin-actions">
                    <!-- Filled dynamically -->
                </div>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('stallDetailsModal').close()">Close</button>
            </div>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    const basePath = '<?= \App\Config\App::basePath() ?>';

    function allowDrop(ev) {
        if (document.getElementById('edit-layout-toggle').checked) {
            ev.preventDefault();
        }
    }

    function handleDragStart(ev) {
        ev.dataTransfer.setData("stallId", ev.currentTarget.dataset.stallId);
        ev.dataTransfer.setData("sourceElementId", ev.currentTarget.id);
    }

    function handleDrop(ev) {
        ev.preventDefault();
        const stallId = ev.dataTransfer.getData("stallId");
        const sourceElementId = ev.dataTransfer.getData("sourceElementId");
        const dragElement = document.getElementById(sourceElementId);
        if (!dragElement) return;

        const targetCell = ev.currentTarget.closest('.grid-cell');
        if (!targetCell) return;

        // Block if cell is already occupied
        if (targetCell.querySelector('.stall-card')) {
            return;
        }

        const row = targetCell.dataset.row;
        const col = targetCell.dataset.col;

        // Setup drop styling
        dragElement.style.borderWidth = '';
        dragElement.classList.add('w-full', 'h-full');
        targetCell.appendChild(dragElement);

        saveLayout(stallId, row, col);
        updateUnassignedCount();
    }

    function handleRemoveDrop(ev) {
        ev.preventDefault();
        const stallId = ev.dataTransfer.getData("stallId");
        const sourceElementId = ev.dataTransfer.getData("sourceElementId");
        const dragElement = document.getElementById(sourceElementId);
        if (!dragElement) return;

        const unassignedContainer = document.getElementById('unassigned-container');
        dragElement.classList.remove('w-full', 'h-full');
        
        // Remove empty state message
        const emptyMsg = unassignedContainer.querySelector('.empty-msg');
        if (emptyMsg) {
            emptyMsg.remove();
        }

        unassignedContainer.appendChild(dragElement);
        saveLayout(stallId, '', '');
        updateUnassignedCount();
    }

    function handleUnassignedDrop(ev) {
        ev.preventDefault();
        const stallId = ev.dataTransfer.getData("stallId");
        const sourceElementId = ev.dataTransfer.getData("sourceElementId");
        const dragElement = document.getElementById(sourceElementId);
        if (!dragElement) return;

        const unassignedContainer = document.getElementById('unassigned-container');
        if (!unassignedContainer.contains(dragElement)) {
            dragElement.classList.remove('w-full', 'h-full');
            
            const emptyMsg = unassignedContainer.querySelector('.empty-msg');
            if (emptyMsg) {
                emptyMsg.remove();
            }

            unassignedContainer.appendChild(dragElement);
            saveLayout(stallId, '', '');
            updateUnassignedCount();
        }
    }

    function saveLayout(stallId, row, col) {
        const formData = new FormData();
        formData.append('stall_id', stallId);
        formData.append('grid_row', row);
        formData.append('grid_col', col);
        formData.append('csrf_token', '<?= \App\Core\Session::csrfToken() ?>');

        fetch(basePath + '/admin/stalls/update-layout', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Failed to save layout: ' + (data.error || 'Unknown error'));
                window.location.reload();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Connection error occurred while saving layout.');
            window.location.reload();
        });
    }

    function updateUnassignedCount() {
        const container = document.getElementById('unassigned-container');
        const countBadge = document.getElementById('unassigned-count');
        const cards = container.querySelectorAll('.stall-card');
        
        countBadge.textContent = cards.length;

        // Show empty message if container has no cards
        const emptyMsg = container.querySelector('.empty-msg');
        if (cards.length === 0 && !emptyMsg) {
            const div = document.createElement('div');
            div.className = 'text-center py-12 opacity-50 text-xs empty-msg';
            div.textContent = 'All stalls placed.';
            container.appendChild(div);
        } else if (cards.length > 0 && emptyMsg) {
            emptyMsg.remove();
        }
    }

    function toggleEditMode(enabled) {
        const cards = document.querySelectorAll('.stall-card');
        const cells = document.querySelectorAll('.grid-cell');
        const removeZone = document.getElementById('remove-dropzone');
        const dragHandles = document.querySelectorAll('.drag-handle');

        cards.forEach(card => {
            card.setAttribute('draggable', enabled ? 'true' : 'false');
            if (enabled) {
                card.classList.add('border-primary', 'shadow-md');
            } else {
                card.classList.remove('border-primary', 'shadow-md');
            }
        });

        cells.forEach(cell => {
            if (enabled) {
                cell.classList.add('border-dashed', 'border-primary/20', 'bg-primary/5');
            } else {
                cell.classList.remove('border-dashed', 'border-primary/20', 'bg-primary/5');
            }
        });

        dragHandles.forEach(h => {
            if (enabled) h.classList.remove('hidden');
            else h.classList.add('hidden');
        });

        if (removeZone) {
            if (enabled) removeZone.classList.remove('hidden');
            else removeZone.classList.add('hidden');
        }
    }

    function showStallDetails(element) {
        // Prevent opening modal when layout builder editing mode is active
        if (document.getElementById('edit-layout-toggle').checked) {
            return;
        }

        const data = element.dataset;
        
        document.getElementById('md-stall-number').textContent = data.stallNumber;
        document.getElementById('md-price').textContent = 'PHP ' + data.price + '/mo';
        document.getElementById('md-description').textContent = data.description || 'No description provided.';
        
        // Status Badge
        const statusBadge = document.getElementById('md-status');
        statusBadge.textContent = data.status;
        statusBadge.className = 'badge badge-sm font-semibold uppercase mt-1';
        if (data.status === 'available') statusBadge.classList.add('badge-success', 'text-white');
        else if (data.status === 'occupied') statusBadge.classList.add('badge-error', 'text-white');
        else if (data.status === 'maintenance') statusBadge.classList.add('badge-warning', 'text-white');

        // Admin Edit Button
        const adminActions = document.getElementById('md-admin-actions');
        adminActions.innerHTML = `
            <a href="${basePath}/admin/stalls/edit/${data.stallId}" class="btn btn-outline btn-sm gap-1">
                <i data-lucide="edit" class="w-4 h-4"></i> Edit Stall Profiles
            </a>
        `;

        // Tenant Area
        const tenantSection = document.getElementById('md-tenant-section');
        const tenantActions = document.getElementById('md-tenant-actions');
        
        if (data.status === 'occupied' && data.tenantName) {
            tenantSection.classList.remove('hidden');
            document.getElementById('md-tenant-name').textContent = data.tenantName;
            document.getElementById('md-tenant-phone').textContent = data.tenantPhone || 'N/A';
            document.getElementById('md-tenant-email').textContent = data.tenantEmail || 'N/A';
            document.getElementById('md-start-date').textContent = data.startDate || 'N/A';
            document.getElementById('md-end-date').textContent = data.endDate || 'N/A';

            let actionsHtml = `
                <a href="${basePath}/admin/payments" class="btn btn-primary btn-sm flex-1">
                    <i data-lucide="credit-card" class="w-4 h-4"></i> View Payments
                </a>
            `;
            if (data.contractDoc) {
                actionsHtml += `
                    <a href="${basePath}/files/contract/${data.stallId}" target="_blank" class="btn btn-outline btn-sm flex-1">
                        <i data-lucide="file-text" class="w-4 h-4"></i> Open Contract
                    </a>
                `;
            }
            tenantActions.innerHTML = actionsHtml;
        } else if (data.status === 'available') {
            tenantSection.classList.add('hidden');
            tenantActions.innerHTML = '';
            // Allow assigning user directly from modal
            adminActions.innerHTML += `
                <a href="${basePath}/admin/mappings/create?stall_id=${data.stallId}" class="btn btn-primary btn-sm gap-1">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Assign Tenant
                </a>
            `;
        } else {
            tenantSection.classList.add('hidden');
            tenantActions.innerHTML = '';
        }

        // Initialize lucide icons in dynamic markup
        lucide.createIcons();

        document.getElementById('stallDetailsModal').showModal();
    }
</script>
