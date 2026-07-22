<?php
/**
 * Pagination Component (DaisyUI)
 * Needs variables: $page, $pages, $search, $sortBy, $sortOrder
 */

function buildUrl($newPage, $search, $sortBy, $sortOrder) {
    $params = [
        'page' => $newPage,
        'search' => $search,
        'sort' => $sortBy,
        'order' => $sortOrder
    ];
    return '?' . http_build_query($params);
}
?>

<?php if ($pages > 1): ?>
    <div class="flex items-center justify-between px-4 py-3 border-t border-base-200">
        <p class="text-sm opacity-60">Page <span class="font-medium"><?= $page ?></span> of <span class="font-medium"><?= $pages ?></span></p>
        <div class="join">
            <?php if ($page > 1): ?>
                <a href="<?= buildUrl($page - 1, $search ?? '', $sortBy ?? '', $sortOrder ?? '') ?>" class="join-item btn btn-sm">Â«</a>
            <?php else: ?>
                <button class="join-item btn btn-sm btn-disabled">Â«</button>
            <?php endif; ?>

            <?php
                $start = max(1, $page - 2);
                $end = min($pages, $page + 2);
                if ($start > 1) echo '<button class="join-item btn btn-sm btn-disabled">...</button>';
                for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="<?= buildUrl($i, $search ?? '', $sortBy ?? '', $sortOrder ?? '') ?>" class="join-item btn btn-sm <?= $i === $page ? 'btn-active btn-primary' : '' ?>"><?= $i ?></a>
            <?php 
                endfor;
                if ($end < $pages) echo '<button class="join-item btn btn-sm btn-disabled">...</button>';
            ?>

            <?php if ($page < $pages): ?>
                <a href="<?= buildUrl($page + 1, $search ?? '', $sortBy ?? '', $sortOrder ?? '') ?>" class="join-item btn btn-sm">Â»</a>
            <?php else: ?>
                <button class="join-item btn btn-sm btn-disabled">Â»</button>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

