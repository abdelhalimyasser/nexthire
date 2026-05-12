<?php 
declare(strict_types=1);

class ApplicationController extends BaseController {

	public function index(): void {
		$this->requireRole("candidate");
		$uid = (int)$this->currentUser["id"];
		$db  = Database::getInstance();
		$appModel = new ApplicationModel();
		$apps = $appModel->findByCandidate($uid);

		// Find any active interview panels for this candidate
		$panelStmt = $db->prepare("
		                          SELECT ip.id as panel_id, ip.scheduled_at, ip.status, ip.duration_minutes,
		                          a.id as app_id, jr.title as job_title
		                          FROM interview_panels ip
		                          JOIN applications a ON ip.application_id = a.id
		                          JOIN job_requisitions jr ON a.job_id = jr.id
		                          WHERE a.candidate_id = :uid AND ip.status IN ('scheduled','active')
		                          ORDER BY ip.scheduled_at ASC
		                          ");
		                          $panelStmt->execute(["uid" => $uid]);
		                          $upcomingPanels = $panelStmt->fetchAll();
		                          // Index by app_id for quick lookup
		                          $panelByApp = [];
		foreach ($upcomingPanels as $p) {
		$panelByApp[$p["app_id"]] = $p;
		}

		$pageTitle = "My Applications";
		ob_start();
		?>
		<div class="flex justify-between items-center mb-6">
		<h1 class="text-2xl font-bold">My Applications</h1>
		<a href="index.php?page=candidate/applications&action=browse" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition">Browse Jobs</a>
		</div>

		<?php if (!empty($upcomingPanels)): ?>

		<div class="mb-6">
		<h2 class="text-base font-semibold text-slate-700 mb-3 flex items-center gap-2">
		<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse inline-block"></span>
		Upcoming Interviews
		</h2>
        
		<div class="space-y-3">
		<?php foreach ($upcomingPanels as $p): ?>
			<div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl border border-indigo-200 p-5 flex justify-between items-center">
			<div>
			<h3 class="font-semibold text-slate-800"><?= htmlspecialchars($p["job_title"]) ?></h3>
			<p class="text-sm text-slate-500 mt-0.5">
			<?= date("D, M j Y g:i A", strtotime($p["scheduled_at"])) ?>
			&middot; <?= $p["duration_minutes"] ?> min
			</p>
			<p class="text-xs text-slate-400 mt-0.5">Panel #<?= $p["panel_id"] ?></p>
			</div>
			<div class="flex items-center gap-3">
			<span class="px-2 py-1 text-xs font-semibold rounded-full <?= $p["status"]==="active" ? "bg-emerald-100 text-emerald-700" : "bg-amber-100 text-amber-700" ?>">
			<?= ucfirst($p["status"]) ?>
			</span>
			<a href="index.php?page=candidate/interview&action=join&id=<?= $p["panel_id"] ?>"
			class="px-5 py-2.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition font-semibold flex items-center gap-2">
			<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
			</svg>
			Join Interview
			</a>
			</div>
			</div>
			<?php endforeach; ?>
			</div>
			</div>
			<?php endif; ?>

			<div class="space-y-4"><?php foreach ($apps as $a):
				$stages = array_filter(APP_STAGES, fn($s) => $s !== "rejected");
				$idx    = array_search($a["stage"], APP_STAGES);
				$panel  = $panelByApp[$a["id"]] ?? null;
				?>
				<div class="bg-white rounded-xl border p-6 card-hover">
				<div class="flex justify-between items-center mb-3">
				<h3 class="font-semibold text-lg"><?= htmlspecialchars($a["job_title"]) ?></h3>
				<span class="px-3 py-1 rounded-full text-xs font-semibold <?= $a["stage"]==="hired" ? "bg-emerald-100 text-emerald-700" : ($a["stage"]==="rejected" ? "bg-red-100 text-red-700" : "bg-indigo-100 text-indigo-700") ?>">
				<?= ucfirst(str_replace("_"," ",$a["stage"])) ?>
				</span>
				</div>
				<p class="text-sm text-slate-500 mb-3"><?= $a["department"] ?> &middot; Applied <?= date("M j, Y", strtotime($a["applied_at"])) ?></p>
				<div class="flex gap-1 mb-3"><?php foreach ($stages as $s): ?>
					<div class="flex-1 h-2 rounded-full <?= array_search($s, APP_STAGES) <= $idx && $a["stage"] !== "rejected" ? "bg-indigo-500" : "bg-slate-200" ?>"></div>
					<?php endforeach; ?></div>
					<?php if ($a["stage"] === "offer"): ?>
						<div class="mt-1">
						<a href="index.php?page=candidate/applications&action=offer&id=<?= $a["id"] ?>" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700">View Offer</a>
						</div>
						<?php endif; ?>
						<?php if ($panel): ?>
							<div class="mt-2">
							<a href="index.php?page=candidate/interview&action=join&id=<?= $panel["panel_id"] ?>"
							class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
							<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
							Join Interview
							</a>
							</div>
							<?php endif; ?>
							</div>
							<?php endforeach;
							if (empty($apps)): ?>
								<div class="text-center py-12 bg-white rounded-xl border">
								<p class="text-slate-500 mb-2">No applications yet</p>
								<a href="index.php?page=candidate/applications&action=browse" class="text-indigo-600 hover:underline">Browse available jobs</a>
								</div>
								<?php endif; ?></div>
								<?php
								$content = ob_get_clean();
								$this->renderLayout($content, compact("pageTitle"));
							}

public function browse(): void {
		$this->requireRole("candidate");
		$jobModel=new JobRequisitionModel(); $jobs=$jobModel->findByStatus("live");
		$pageTitle="Browse Jobs"; ob_start(); ?>
		<h1 class="text-2xl font-bold mb-6">Available Positions</h1>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4"><?php foreach($jobs as $j): $skills=$jobModel->getSkills((int)$j["id"]); ?>
		<div class="bg-white rounded-xl border p-6 card-hover">
			<h3 class="font-semibold text-lg mb-1"><?=htmlspecialchars($j["title"])?></h3>
				<p class="text-sm text-slate-500 mb-3"><?=$j["department"]?> · <?=$j["level"]?> · <?=ucfirst($j["location_tier"])?></p>
					<p class="text-sm text-slate-600 mb-3 line-clamp-2"><?=htmlspecialchars(substr($j["description"],0,150))?>...</p>
						<div class="flex flex-wrap gap-1 mb-3"><?php foreach(array_slice($skills,0,5) as $sk):?><span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-xs"><?=htmlspecialchars($sk["skill_name"])?></span><?php endforeach; ?></div>
		<form method="POST" action="index.php?page=candidate/applications&action=apply"><?=$this->csrfField()?><input type="hidden" name="job_id" value="<?=$j["id"]?>">
		<button class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 w-full">Apply Now</button></form>
			</div>
			<?php endforeach; ?></div>
		<?php $content=ob_get_clean(); $this->renderLayout($content,compact("pageTitle"));
	}

	public function apply(): void {
		$this->requireRole("candidate");
		if($this->validateCsrf()) {
			$jobId=$this->getIntInput("job_id");
			try {
				$appModel=new ApplicationModel();
				$id=$appModel->create(["job_id"=>$jobId,"candidate_id"=>$this->currentUser["id"],"stage"=>"applied","source"=>"direct"]);
				$appModel->logStageChange($id,null,"applied",(int)$this->currentUser["id"],"Initial application");
				// Calculate match score
				$sws=new SkillWeightingService();
				$score=$sws->calculateMatchScore($jobId,(int)$this->currentUser["id"]);
				$appModel->update($id,["match_score"=>$score]);
				// Dedup check
				(new DeduplicationService())->detectAndHandle($id);
				AuditLogger::getInstance()->log((int)$this->currentUser["id"],"application",$id,"applied",[],["job_id"=>$jobId]);
				$this->setFlash("success","Application submitted! Match score: ".round($score,1)."%");
			} catch(\Exception $e) {
				$this->setFlash("error",$e->getMessage());
			}
		}
		$this->redirect("index.php?page=candidate/applications");
	}

	public function offer(): void {
		$this->requireRole("candidate"); $appId=$this->getIntInput("id");
		$om=new OfferModel(); $offer=$om->findByApplication($appId);
		if(!$offer) {
			$this->setFlash("error","No offer found");
			$this->redirect("index.php?page=candidate/applications");
			return;
		}
		$app=(new ApplicationModel())->getWithDetails($appId);
		if($_SERVER["REQUEST_METHOD"]==="POST"&&$this->validateCsrf()) {
			$action=$_POST["offer_action"]??"";
			$ovs=new OfferValidityService();
			if($action==="accept") {
				$ovs->accept((int)$offer["id"]);
				$this->setFlash("success","Offer accepted!");
			}
			elseif($action==="decline") {
				$ovs->decline((int)$offer["id"]);
				$this->setFlash("success","Offer declined");
			}
			$this->redirect("index.php?page=candidate/applications");
			return;
		}
		$pageTitle="Offer Details"; ob_start(); ?>
		<div class="max-w-2xl mx-auto bg-white rounded-xl border p-8">
			           <h2 class="text-xl font-bold mb-4">Offer for <?=htmlspecialchars($app["job_title"]??"")?></h2>
					                     <div class="grid grid-cols-3 gap-4 mb-6"><div class="bg-indigo-50 p-4 rounded-lg text-center"><p class="text-sm text-slate-600">Salary</p><p class="text-2xl font-bold text-indigo-700">$<?=number_format((float)$offer["salary"],0)?></p></div>
									                                <div class="bg-purple-50 p-4 rounded-lg text-center"><p class="text-sm text-slate-600">Bonus</p><p class="text-2xl font-bold text-purple-700">$<?=number_format((float)$offer["signing_bonus"],0)?></p></div>
												                                        <div class="bg-emerald-50 p-4 rounded-lg text-center"><p class="text-sm text-slate-600">Equity</p><p class="text-2xl font-bold text-emerald-700"><?=$offer["equity"]?></p></div></div>
															                                                <p class="text-sm text-slate-500 mb-4">Status: <strong><?=ucfirst($offer["status"])?></strong> <?=$offer["expires_at"]?" · Expires: ".date("M j, Y",strtotime($offer["expires_at"])):""?></p>
																                                                        <?php if($offer["status"]==="sent"):?><form method="POST" class="flex gap-3"><?=$this->csrfField()?>
																		                                                                <button name="offer_action" value="accept" class="flex-1 py-3 bg-emerald-600 text-white rounded-lg font-semibold hover:bg-emerald-700">Accept Offer</button>
																			                                                                        <button name="offer_action" value="decline" class="flex-1 py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700">Decline</button></form><?php endif; ?>
		</div>
		<?php $content=ob_get_clean(); $this->renderLayout($content,compact("pageTitle"));
	}
}