<?php if (!empty($flash)): ?>
<div class="mb-4 p-4 rounded-lg <?= $flash["type"]==="success" ? "bg-emerald-50 text-emerald-800 border border-emerald-200" : "bg-red-50 text-red-800 border border-red-200" ?>"><?= htmlspecialchars($flash["message"]) ?></div>
<?php endif; ?>