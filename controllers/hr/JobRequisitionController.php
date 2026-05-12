<?php 
declare(strict_types=1);

class JobRequisitionController extends BaseController {
    
	public function index(): void {
		$this->requireRole("hr_admin");
		$jobModel=new JobRequisitionModel();
		$jobs=$jobModel->findAll();
		$pageTitle="Job Requisitions";
		ob_start();
		?>
		<div class="flex justify-between items-center mb-6">
			<div><h1 class="text-2xl font-bold text-slate-800">Job Requisitions</h1><p class="text-slate-500 text-sm">Manage all job postings</p></div>
					<a href="index.php?page=hr/jobs&action=create" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:shadow-lg transition">+ New Job</a>
						</div>
						<div class="bg-white rounded-xl border">
							<table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="p-4">Title</th><th class="p-4">Department</th><th class="p-4">Status</th><th class="p-4">Level</th><th class="p-4">Created</th><th class="p-4">Actions</th></tr></thead><tbody>
															<?php foreach($jobs as $j): $sc=["draft"=>"bg-slate-100 text-slate-700","pending_approval"=>"bg-amber-100 text-amber-700","live"=>"bg-emerald-100 text-emerald-700","closed"=>"bg-red-100 text-red-700","cancelled"=>"bg-slate-200 text-slate-600"];
		?>
		<tr class="border-b hover:bg-slate-50 transition"><td class="p-4 font-medium"><?=htmlspecialchars($j["title"])?></td><td class="p-4"><?=htmlspecialchars($j["department"])?></td><td class="p-4"><span class="px-2 py-1 rounded-full text-xs font-semibold <?=$sc[$j["status"]]??""?>"><?=ucfirst(str_replace("_"," ",$j["status"]))?></span></td><td class="p-4"><?=$j["level"]?></td><td class="p-4 text-slate-400"><?=date("M j",strtotime($j["created_at"]))?></td>
									<td class="p-4 flex gap-2"><a href="index.php?page=hr/jobs&action=view&id=<?=$j["id"]?>" class="text-indigo-600 hover:underline text-xs">View</a>
											<?php if($j["status"]==="draft"):?><a href="index.php?page=hr/jobs&action=submit_approval&id=<?=$j["id"]?>" class="text-amber-600 hover:underline text-xs">Submit</a><?php endif;
		?>
		<?php if($j["status"]==="live"):?><a href="index.php?page=hr/jobs&action=sync&id=<?=$j["id"]?>" class="text-purple-600 hover:underline text-xs">Sync Boards</a><?php endif;
		?>
		</td></tr>
		<?php endforeach;
		?></tbody></table>
		</div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function create(): void {
		$this->requireRole("hr_admin");
		$error="";
		if($_SERVER["REQUEST_METHOD"]==="POST" && $this->validateCsrf()) {
			$jm=new JobRequisitionModel();
			$id=$jm->create(["title"=>$this->getInput("title"),"department"=>$this->getInput("department"),"description"=>$_POST["description"]??"","requirements"=>$_POST["requirements"]??"","level"=>$this->getInput("level","L3"),"location_tier"=>$this->getInput("location_tier","tier1"),"role_type"=>$this->getInput("role_type"),"created_by"=>$this->currentUser["id"]]);
			$skills=explode(",",($_POST["skills"]??""));
			foreach($skills as $sk) {
				$sk=trim($sk);
				if($sk)$jm->addSkill($id,$sk,1.0,false);
			}
			AuditLogger::getInstance()->log((int)$this->currentUser["id"],"job_requisition",$id,"created",[],["title"=>$this->getInput("title")]);
			$this->setFlash("success","Job requisition created successfully");
			$this->redirect("index.php?page=hr/jobs");
			return;
		}
		$pageTitle="Create Job Requisition";
		ob_start();
		?>
		<div class="max-w-3xl mx-auto bg-white rounded-xl border p-8">
			           <h2 class="text-xl font-bold mb-6">New Job Requisition</h2>
				                     <form method="POST" class="space-y-5"><?=$this->csrfField()?>
					                                  <div class="grid grid-cols-2 gap-4">
						                                          <div><label class="block text-sm font-medium text-slate-700 mb-1">Job Title</label><input name="title" required class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-indigo-500 outline-none"></div>
								                                                  <div><label class="block text-sm font-medium text-slate-700 mb-1">Department</label><input name="department" required class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-indigo-500 outline-none"></div>
										                                                          </div>
										                                                          <div class="grid grid-cols-3 gap-4">
											                                                                  <div><label class="block text-sm font-medium mb-1">Level</label><select name="level" class="w-full px-4 py-2 rounded-lg border"><?php foreach(["L1","L2","L3","L4","L5","L6"] as $l):?><option><?=$l?></option><?php endforeach;
		?></select></div>
		<div><label class="block text-sm font-medium mb-1">Location Tier</label><select name="location_tier" class="w-full px-4 py-2 rounded-lg border"><option value="tier1">Tier 1</option><option value="tier2">Tier 2</option><option value="tier3">Tier 3</option></select></div>
				                  <div><label class="block text-sm font-medium mb-1">Role Type</label><input name="role_type" class="w-full px-4 py-2 rounded-lg border" placeholder="e.g. Engineering"></div>
						                                    </div>
						                                    <div><label class="block text-sm font-medium mb-1">Description</label><textarea name="description" rows="5" class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-indigo-500 outline-none"></textarea></div>
								                                            <div><label class="block text-sm font-medium mb-1">Requirements</label><textarea name="requirements" rows="3" class="w-full px-4 py-2 rounded-lg border"></textarea></div>
										                                                    <div><label class="block text-sm font-medium mb-1">Skills (comma-separated)</label><input name="skills" class="w-full px-4 py-2 rounded-lg border" placeholder="PHP, MySQL, JavaScript"></div>
												                                                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">Create Job</button>
													                                                                    </form>
													                                                                    </div>
													                                                                    <?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function view(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		$jm=new JobRequisitionModel();
		$job=$jm->findWithCreator($id);
		if(!$job) {
			$this->setFlash("error","Job not found");
			$this->redirect("index.php?page=hr/jobs");
			return;
		}
		$skills=$jm->getSkills($id);
		$apps=(new ApplicationModel())->findByJob($id);
		$pageTitle=$job["title"];
		ob_start();
		?>

		<div class="max-w-4xl mx-auto">
			<div class="bg-white rounded-xl border p-6 mb-6">
				<div class="flex justify-between"><div><h2 class="text-2xl font-bold"><?=htmlspecialchars($job["title"])?></h2><p class="text-slate-500"><?=$job["department"]?> · <?=$job["level"]?> · <?=ucfirst(str_replace("_"," ",$job["location_tier"]))?></p></div>
							<span class="px-3 py-1 rounded-full text-sm font-semibold h-fit <?=$job["status"]==="live"?"bg-emerald-100 text-emerald-700":"bg-slate-100 text-slate-700"?>"><?=ucfirst($job["status"])?></span></div>
								<div class="mt-4 prose max-w-none text-slate-700"><?=nl2br(htmlspecialchars($job["description"]))?></div>
									<div class="mt-4 flex flex-wrap gap-2"><?php foreach($skills as $sk):?><span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-medium"><?=htmlspecialchars($sk["skill_name"])?> (<?=$sk["weight"]?>)</span><?php endforeach;
		?></div>
		</div>
		<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold text-lg mb-4">Applications (<?=count($apps)?>)</h3>
				<table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="pb-2">Candidate</th><th class="pb-2">Stage</th><th class="pb-2">Match</th><th class="pb-2">Applied</th><th class="pb-2">Actions</th></tr></thead><tbody>
											<?php foreach($apps as $a):?><tr class="border-b border-slate-50"><td class="py-2 font-medium"><?=htmlspecialchars($a["candidate_name"])?></td><td><span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700"><?=ucfirst($a["stage"])?></span></td><td><?=round((float)($a["match_score"]??0),1)?>%</td><td class="text-slate-400"><?=date("M j",strtotime($a["applied_at"]))?></td><td><a href="index.php?page=hr/pipeline&action=view&id=<?=$a["id"]?>" class="text-indigo-600 hover:underline text-xs">Manage</a></td></tr>
																<?php endforeach;
		?></tbody></table></div>
		</div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function submit_approval(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		$aws=new ApprovalWorkflowService();
		$aws->submitForApproval($id);
		$this->setFlash("success","Job submitted for approval");
		$this->redirect("index.php?page=hr/jobs");
	}

	public function approve(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		$level=$this->getInput("level","department_head");
		$aws=new ApprovalWorkflowService();
		$aws->approve($id,(int)$this->currentUser["id"],$level);
		$this->setFlash("success","Approval step completed");
		$this->redirect("index.php?page=hr/jobs&action=view&id=$id");
	}

	public function sync(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		$svc=new JobBoardSyncService();
		$results=$svc->syncToAll($id);
		$this->setFlash("success","Synced to ".count($results)." job boards");
		$this->redirect("index.php?page=hr/jobs&action=view&id=$id");
	}
}
