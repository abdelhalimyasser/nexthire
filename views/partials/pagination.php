<?php if (($totalPages ?? 0) > 1): ?>
<div class="flex justify-center gap-2 mt-6">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
<a href="?page=<?= $currentPage ?? "" ?>&p=<?= $i ?>" class="px-3 py-1 rounded <?= $i === ($p ?? 1) ? "bg-indigo-600 text-white" : "bg-white text-slate-600 border hover:bg-slate-50" ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
<?php endif; ?>