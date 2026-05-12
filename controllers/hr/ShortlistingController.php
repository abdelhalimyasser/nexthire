<?php 
declare(strict_types=1);

class ShortlistingController extends BaseController {
    
	public function index(): void {
		$this->requireRole("hr_admin");
		$jobModel=new JobRequisitionModel();
		$jobs=$jobModel->findByStatus("live");
		$pageTitle="AI Shortlisting";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">AI-Ranked Shortlisting</h1>
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
				<?php foreach($jobs as $j):?>
					<div class="bg-white rounded-xl border p-5 card-hover">
						<h3 class="font-semibold"><?=htmlspecialchars($j["title"])?></h3><p class="text-sm text-slate-500 mb-3"><?=$j["department"]?></p>
								<a href="index.php?page=hr/shortlisting&action=generate&id=<?=$j["id"]?>" class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg inline-block hover:bg-purple-700">Generate Shortlist</a>
									</div>
									<?php endforeach;
		?></div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function generate(): void {
		$this->requireRole("hr_admin");
		$jobId=$this->getIntInput("id");
		$svc=new ShortlistingService();
		$shortlist=$svc->generateShortlist($jobId);
		$job=(new JobRequisitionModel())->findById($jobId);
		$pageTitle="Shortlist: ".($job["title"]??"");
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-2">Shortlist Results</h1><p class="text-slate-500 mb-6"><?=htmlspecialchars($job["title"]??"")?> — Top <?=count($shortlist)?> candidates</p>
				<div class="bg-white rounded-xl border"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="p-4">#</th><th class="p-4">Candidate</th><th class="p-4">Match Score</th><th class="p-4">Stage</th></tr></thead><tbody>
											<?php foreach($shortlist as $i=>$s):?><tr class="border-b hover:bg-slate-50"><td class="p-4 font-bold text-indigo-600"><?=$i+1?></td><td class="p-4 font-medium"><?=htmlspecialchars($s["candidate_name"]??"")?></td><td class="p-4"><div class="flex items-center gap-2"><div class="w-20 bg-slate-200 rounded-full h-2"><div class="bg-indigo-500 h-2 rounded-full" style="width:<?=min(100,$s["computed_score"])?>%"></div></div><span class="font-semibold text-indigo-600"><?=round($s["computed_score"],1)?>%</span></div></td><td class="p-4"><span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-xs"><?=ucfirst($s["stage"])?></span></td></tr>
																					<?php endforeach;
		?></tbody></table></div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}
}