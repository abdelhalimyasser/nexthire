<?php
declare(strict_types=1);

class OnboardingController extends BaseController {

	public function index(): void {
		$this->requireRole("hr_admin");
		$db=Database::getInstance();
		$stmt=$db->prepare("SELECT a.id,a.candidate_id,u.name,j.title as job_title FROM applications a JOIN users u ON a.candidate_id=u.id JOIN job_requisitions j ON a.job_id=j.id WHERE a.stage='hired'");
		$stmt->execute();
		$hired=$stmt->fetchAll();
		$svc=new OnboardingPortalService();
		$pageTitle="Onboarding";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">Onboarding Portal</h1>
			<?php foreach($hired as $h): $svc->initChecklist((int)$h["id"]);
		$checklist=$svc->getChecklist((int)$h["id"]);
		$ready=$svc->isReady((int)$h["id"]);
		?>
		<div class="bg-white rounded-xl border p-6 mb-4 card-hover">
			<div class="flex justify-between items-center mb-3"><div><h3 class="font-semibold"><?=htmlspecialchars($h["name"])?></h3><p class="text-sm text-slate-500"><?=htmlspecialchars($h["job_title"])?></p></div>
						<span class="px-3 py-1 rounded-full text-xs font-semibold <?=$ready?"bg-emerald-100 text-emerald-700":"bg-amber-100 text-amber-700"?>"><?=$ready?"Ready for Day 1":"In Progress"?></span></div>
							<div class="grid grid-cols-5 gap-2"><?php foreach($checklist as $c): $icons=["pending"=>"bg-slate-100 text-slate-600","uploaded"=>"bg-blue-100 text-blue-600","verified"=>"bg-emerald-100 text-emerald-600"];
		?>
		<div class="p-3 rounded-lg text-center <?=$icons[$c["status"]]?>">
			<p class="text-xs font-medium mt-1"><?=ucfirst(str_replace("_"," ",$c["document_type"]))?></p>
				<p class="text-xs mt-1"><?=ucfirst($c["status"])?></p>
					<?php if($c["status"]==="uploaded"):?><a href="index.php?page=hr/onboarding&action=verify&app_id=<?=$h["id"]?>&doc=<?=$c["document_type"]?>" class="text-xs text-indigo-600">Verify</a><?php endif;
		?>
		</div><?php endforeach;
		?></div>
		</div>
		<?php endforeach;
		if(empty($hired)):?><p class="text-slate-500 text-center py-12">No hired candidates yet</p><?php endif;
		?>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}
	public function verify(): void {
		$this->requireRole("hr_admin");
		$appId=$this->getIntInput("app_id");
		$doc=$this->getInput("doc");
		(new OnboardingPortalService())->verifyDocument($appId,$doc);
		$this->setFlash("success","Document verified");
		$this->redirect("index.php?page=hr/onboarding");
	}
}