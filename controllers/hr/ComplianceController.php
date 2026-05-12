<?php 
declare(strict_types=1);

class ComplianceController extends BaseController {
	public function index(): void {
		$this->requireRole("hr_admin");
		$audit=(new AuditTrailService())->getRecent(30);
		$integrity=(new DatabaseIntegrityService())->runIntegrityPass();
		$pageTitle="Compliance & Audit";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">Compliance Dashboard</h1>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
				<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Archivable Jobs</p><p class="text-2xl font-bold"><?=$integrity["archivable_jobs"]?></p></div>
							<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Archivable Applications</p><p class="text-2xl font-bold"><?=$integrity["archivable_applications"]?></p></div>
										<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">FK Violations</p><p class="text-2xl font-bold text-emerald-600"><?=$integrity["fk_violations"]?></p></div>
													</div>
													<div class="bg-white rounded-xl border p-6 mb-6">
														<div class="flex justify-between mb-4"><h3 class="font-semibold">Data Retention</h3>
																<a href="index.php?page=hr/compliance&action=retention" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg">Run Retention Pass</a></div>
																	<p class="text-sm text-slate-500">Anonymizes rejected candidates older than <?=RETENTION_MONTHS?> months.</p>
																		</div>
																		<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Recent Audit Trail</h3>
																				<table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="pb-2">Actor</th><th class="pb-2">Action</th><th class="pb-2">Entity</th><th class="pb-2">Time</th></tr></thead><tbody>
																										<?php foreach($audit as $a):?><tr class="border-b border-slate-50"><td class="py-2"><?=htmlspecialchars($a["actor_name"]??"System")?></td><td><span class="px-2 py-0.5 bg-slate-100 rounded text-xs"><?=htmlspecialchars($a["action"])?></span></td><td><?=$a["entity_type"]?> #<?=$a["entity_id"]?></td><td class="text-slate-400"><?=$a["created_at"]?></td></tr>
																														<?php endforeach;
		?></tbody></table></div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function retention(): void {
		$this->requireRole("hr_admin");
		$report=(new DataRetentionService())->runRetentionPass();
		$this->setFlash("success","Retention pass: {$report["anonymized"]} candidates anonymized");
		$this->redirect("index.php?page=hr/compliance");
	}
}