<main class="flex-1 py-16">
    <div class="max-w-4xl mx-auto px-4">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h1 class="card-title text-3xl font-extrabold mb-6 pb-4 border-b border-base-200"><?= htmlspecialchars($title) ?></h1>
                <div class="prose max-w-none">
                    <?= $terms ?>
                </div>
            </div>
        </div>
    </div>
</main>
