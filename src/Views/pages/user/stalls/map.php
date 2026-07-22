<?php
$gridRows = $currentLocation['grid_rows'] ?? 6;
$gridCols = $currentLocation['grid_cols'] ?? 8;

// Group stalls by row and col
$gridStalls = [];
foreach ($stalls as $stall) {
    if ($stall['grid_row'] !== null && $stall['grid_col'] !== null) {
        $gridStalls[(int)$stall['grid_row']][(int)$stall['grid_col']] = $stall;
    }
}
?>

<div class="space-y-4">
    <!-- Top Filter Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-base-100 p-4 rounded-xl shadow-sm border border-base-200">
        <div>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
            <p class="text-xs opacity-75 mt-0.5">Location: <span class="font-bold text-primary"><?= htmlspecialchars($currentLocation['name'] ?? 'None') ?></span></p>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="" class="flex items-center gap-2">
                <select name="location_id" onchange="this.form.submit()" class="select select-bordered select-sm">
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['id'] ?>" <?= (int)$loc['id'] === (int)$locationId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Main directory grid -->
    <div class="card bg-base-100 shadow-sm border border-base-200 p-5 rounded-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i data-lucide="map" class="w-5 h-5 text-primary"></i> Floor Directory Plan
            </h3>
            
            <!-- Status Legend -->
            <div class="flex gap-3 text-xs">
                <span class="flex items-center gap-1.5 font-medium"><span class="w-3 h-3 bg-success/20 border border-success rounded-sm"></span> Available</span>
                <span class="flex items-center gap-1.5 font-medium"><span class="w-3 h-3 bg-error/20 border border-error rounded-sm"></span> Occupied</span>
                <span class="flex items-center gap-1.5 font-medium"><span class="w-3 h-3 bg-warning/20 border border-warning rounded-sm"></span> Maintenance</span>
            </div>
        </div>

        <!-- Mobile scroll indicator helper -->
        <div class="inline-flex md:hidden items-center gap-1.5 alert alert-info py-1.5 px-3 text-xs mb-3 shadow-sm">
            <i data-lucide="info" class="w-3.5 h-3.5"></i>
            <span>Swipe left or right to pan the floor directory plan.</span>
        </div>

        <!-- Grid Container -->
        <div class="grid-wrap overflow-auto p-1 bg-base-200/50 rounded-xl border border-base-200">
            <div class="grid gap-3 p-4 select-none"
                 style="display: grid; grid-template-rows: repeat(<?= $gridRows ?>, minmax(110px, auto)); grid-template-columns: repeat(<?= $gridCols ?>, minmax(140px, 1fr)); min-width: 800px;">
                <?php for ($r = 1; $r <= $gridRows; $r++): ?>
                    <?php for ($c = 1; $c <= $gridCols; $c++): ?>
                        <?php 
                        $stall = $gridStalls[$r][$c] ?? null;
                        ?>
                        <div class="grid-cell border border-base-300/40 bg-base-100/10 rounded-xl flex items-center justify-center min-h-[110px]">
                            <?php if ($stall): ?>
                                <!-- Stall block -->
                                <div class="stall-card p-4 rounded-lg shadow-sm cursor-pointer w-full h-full flex flex-col justify-between transition-all duration-200 hover:-translate-y-1 hover:shadow-md
                                    <?= $stall['status'] === 'available' ? 'bg-success/10 border-2 border-success text-success-content hover:bg-success/15' : '' ?>
                                    <?= $stall['status'] === 'occupied' ? 'bg-error/10 border-2 border-error text-error-content hover:bg-error/15' : '' ?>
                                    <?= $stall['status'] === 'maintenance' ? 'bg-warning/10 border-2 border-warning text-warning-content hover:bg-warning/15' : '' ?>"
                                     data-stall-id="<?= $stall['id'] ?>"
                                     data-stall-number="<?= htmlspecialchars($stall['stall_number']) ?>"
                                     data-status="<?= $stall['status'] ?>"
                                     data-price="<?= number_format($stall['price'], 2) ?>"
                                     data-description="<?= htmlspecialchars($stall['description'] ?? '') ?>"
                                     onclick="showStallDetails(this)">
                                    
                                    <div class="flex justify-between items-start gap-1">
                                        <span class="font-bold text-sm tracking-wider uppercase"><?= htmlspecialchars($stall['stall_number']) ?></span>
                                        <span class="badge badge-xs uppercase font-semibold
                                            <?= $stall['status'] === 'available' ? 'badge-success text-white' : '' ?>
                                            <?= $stall['status'] === 'occupied' ? 'badge-error text-white' : '' ?>
                                            <?= $stall['status'] === 'maintenance' ? 'badge-warning text-white' : '' ?>">
                                            <?= $stall['status'] ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mt-2 text-xs">
                                        <div class="font-bold text-sm">PHP <?= number_format($stall['price'], 0) ?></div>
                                        <div class="mt-1 opacity-70">
                                            <?= $stall['status'] === 'available' ? 'Available to Rent' : ($stall['status'] === 'occupied' ? 'Rented' : 'Maintenance') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Empty space indicator -->
                                <span class="empty-indicator opacity-20 text-[9px] font-mono select-none">Empty space</span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                <?php endfor; ?>
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

            <div class="modal-action border-t border-base-200 pt-4 mt-4 flex justify-between items-center">
                <div id="md-tenant-action-wrapper">
                    <!-- Dynamic button for contact/inquire -->
                </div>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('stallDetailsModal').close()">Close</button>
            </div>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    const basePath = '<?= \App\Config\App::basePath() ?>';

    function showStallDetails(element) {
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

        // Dynamic action button
        const actionWrapper = document.getElementById('md-tenant-action-wrapper');
        if (data.status === 'available') {
            actionWrapper.innerHTML = `
                <a href="${basePath}/user/tickets/create" class="btn btn-primary btn-sm gap-1">
                    <i data-lucide="send" class="w-4 h-4"></i> Inquire About Stall
                </a>
            `;
        } else {
            actionWrapper.innerHTML = '';
        }

        // Initialize icons
        lucide.createIcons();

        document.getElementById('stallDetailsModal').showModal();
    }
</script>
