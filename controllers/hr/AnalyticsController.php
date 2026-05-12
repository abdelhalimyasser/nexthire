<?php 
declare(strict_types=1);

class AnalyticsController extends BaseController {
	public function index(): void {
		$this->requireRole("hr_admin");
		$analytics=(new ThroughputAnalyticsService())->analyze();
		$diversity=(new DiversityReportService())->generateReport();
		$pageTitle="Analytics & Reports";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">Pipeline Analytics</h1>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
				<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Avg Time to Hire</p><p class="text-3xl font-bold text-indigo-600"><?=$analytics["avg_time_to_hire"]?> <span class="text-lg font-normal">days</span></p></div>
								<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Bottleneck Stage</p><p class="text-3xl font-bold text-amber-600"><?=ucfirst(str_replace("_"," ",$analytics["bottleneck"]))?></p></div>
											<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Pipeline Stages</p><p class="text-3xl font-bold text-purple-600"><?=count($analytics["stages"])?></p></div>
														</div>
														<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
															<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Stage Duration (Hours)</h3><canvas id="durationChart" height="200"></canvas>
<script>new Chart(document.getElementById("durationChart"),{type:"bar",data:{labels:<?=json_encode(array_map(fn($s)=>ucfirst($s["from_stage"]??"Start")." → ".ucfirst($s["to_stage"]),$analytics["stages"]))?>,datasets:[{label:"Avg Hours",data:<?=json_encode(array_map(fn($s)=>round((float)($s["avg_hours"]??0),1),$analytics["stages"]))?>,backgroundColor:"#818cf8"}]},options:{responsive:true,indexAxis:"y"}});
		</script></div>
		<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Diversity Report</h3>
				<?php foreach($diversity as $dim=>$groups):?><h4 class="text-sm font-medium text-slate-600 mt-3 mb-2"><?=ucfirst($dim)?></h4>
					<?php foreach($groups as $g):?><div class="flex items-center gap-3 mb-1"><span class="text-sm w-24"><?=htmlspecialchars($g["group"])?></span><div class="flex-1 bg-slate-200 rounded-full h-2"><div class="bg-purple-500 h-2 rounded-full" style="width:<?=$g["percentage"]?>%"></div></div><span class="text-xs text-slate-600"><?=$g["percentage"]?>%</span></div>
										<?php endforeach;
		endforeach;
		?></div>
		</div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}
}